<?php

namespace App\Http\Controllers;

use App\Enums\FormaPagamento;
use App\Enums\StatusLancamento;
use App\Enums\TipoLancamento;
use App\Http\Requests\FiltroLancamentosRequest;
use App\Http\Requests\LancamentoRequest;
use App\Models\Categoria;
use App\Models\Contato;
use App\Models\Lancamento;
use App\Models\TaxaCartao;
use App\Services\RegistroLancamentos;
use App\Support\Csv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LancamentoController extends Controller implements HasMiddleware
{
    public function __construct(private readonly RegistroLancamentos $registro) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:editar', except: ['index', 'show', 'exportar']),
            new Middleware('can:admin', only: ['restaurar']),
        ];
    }

    public function index(FiltroLancamentosRequest $request): View
    {
        $filtros = $request->filtros();
        $lixeira = $request->boolean('lixeira') && $request->user()->can('admin');

        $consulta = Lancamento::query()->filtrar($filtros)->when($lixeira, fn ($q) => $q->onlyTrashed());

        $totais = (clone $consulta)->toBase()
            ->selectRaw('COALESCE(SUM(CASE WHEN tipo = ? THEN valor_caixa END), 0) AS entradas', [TipoLancamento::Entrada->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN tipo = ? THEN valor_caixa END), 0) AS saidas', [TipoLancamento::Saida->value])
            ->selectRaw('COUNT(*) AS quantidade')
            ->first();

        return view('lancamentos.index', [
            'lancamentos' => $consulta->with(['categoria', 'contato'])
                ->orderByDesc('data')->orderByDesc('id')
                ->paginate(25)->withQueryString(),
            'totais' => $totais,
            'filtros' => $filtros,
            'lixeira' => $lixeira,
            'categorias' => Categoria::query()->ordenadas()->get(),
            'contatoFiltrado' => isset($filtros['contato_id']) ? Contato::withTrashed()->find($filtros['contato_id']) : null,
        ]);
    }

    public function create(Request $request): View
    {
        $lancamento = new Lancamento([
            'data' => today(),
            'tipo' => TipoLancamento::tryFrom((string) $request->query('tipo')) ?? TipoLancamento::Entrada,
            'status' => StatusLancamento::Pago,
            'forma_pagamento' => FormaPagamento::Pix,
        ]);

        // "Duplicar": reaproveita os dados de um lançamento existente com a data de hoje.
        if ($request->filled('copiar') && $origem = Lancamento::with('contato')->find($request->integer('copiar'))) {
            $lancamento = $origem->replicate(['grupo_parcelamento', 'parcela_numero', 'parcela_total', 'criado_por', 'atualizado_por']);
            $lancamento->data = today();
            $lancamento->setRelation('contato', $origem->contato);
        }

        return view('lancamentos.create', $this->dadosFormulario($lancamento));
    }

    public function store(LancamentoRequest $request): RedirectResponse
    {
        $criados = $this->registro->criar($request->validated(), $request->user());

        $mensagem = $criados->count() > 1
            ? "{$criados->count()} lançamentos criados (parcelamento/recorrência)."
            : 'Lançamento registrado com sucesso.';

        if ($request->boolean('continuar')) {
            return redirect()->route('lancamentos.create', ['tipo' => $criados->first()->tipo->value])->with('sucesso', $mensagem);
        }

        return redirect()->route('lancamentos.index')->with('sucesso', $mensagem);
    }

    public function show(Request $request, Lancamento $lancamento): View
    {
        // A rota aceita itens da lixeira, mas só o administrador pode vê-los.
        abort_if($lancamento->trashed() && ! $request->user()->can('admin'), 404);

        $lancamento->load(['categoria', 'contato', 'criador', 'atualizador', 'historico.user']);

        return view('lancamentos.show', [
            'lancamento' => $lancamento,
            'parcelas' => $lancamento->grupo_parcelamento ? $lancamento->parcelas()->get() : collect(),
        ]);
    }

    public function edit(Lancamento $lancamento): View
    {
        $lancamento->load('contato');

        return view('lancamentos.edit', $this->dadosFormulario($lancamento));
    }

    public function update(LancamentoRequest $request, Lancamento $lancamento): RedirectResponse
    {
        $this->registro->atualizar($lancamento, $request->validated(), $request->user());

        return redirect()->route('lancamentos.show', $lancamento)->with('sucesso', 'Lançamento atualizado.');
    }

    public function destroy(Request $request, Lancamento $lancamento): RedirectResponse
    {
        if ($request->boolean('pendentes_do_grupo') && $lancamento->grupo_parcelamento) {
            $pendentes = Lancamento::query()
                ->where('grupo_parcelamento', $lancamento->grupo_parcelamento)
                ->where('status', StatusLancamento::Pendente)
                ->get();

            // Exclusão um a um para que cada registro passe pela auditoria.
            $pendentes->each->delete();

            return redirect()->route('lancamentos.index')->with('sucesso', "{$pendentes->count()} parcela(s) pendente(s) excluída(s).");
        }

        $lancamento->delete();

        return redirect()->route('lancamentos.index')->with('sucesso', 'Lançamento excluído. Um administrador pode restaurá-lo pela lixeira.');
    }

    public function baixar(Request $request, Lancamento $lancamento): RedirectResponse
    {
        $dados = $request->validate([
            'data_pagamento' => ['required', 'date', 'after_or_equal:2000-01-01', 'before:2100-01-01'],
        ], [], ['data_pagamento' => 'data do pagamento']);

        if (! $lancamento->estaPendente()) {
            return back()->with('erro', 'Este lançamento já está quitado.');
        }

        $this->registro->baixar($lancamento, $dados['data_pagamento'], $request->user());

        return back()->with('sucesso', $lancamento->ehEntrada() ? 'Recebimento confirmado.' : 'Pagamento confirmado.');
    }

    public function restaurar(Lancamento $lancamento): RedirectResponse
    {
        $lancamento->restore();

        return redirect()->route('lancamentos.show', $lancamento)->with('sucesso', 'Lançamento restaurado.');
    }

    public function exportar(FiltroLancamentosRequest $request): StreamedResponse
    {
        $consulta = Lancamento::query()
            ->filtrar($request->filtros())
            ->with(['categoria', 'contato'])
            ->orderBy('data')
            ->orderBy('id');

        $cabecalho = [
            'Data', 'Tipo', 'Categoria', 'Grupo', 'Descrição', 'Paciente / fornecedor', 'Forma de pagamento',
            'Valor bruto (R$)', 'Status', 'Parcelas', 'Taxa (%)', 'Taxa (R$)', 'Valor líquido (R$)',
            'Líquido informado (R$)', 'Diferença (R$)', 'Valor no caixa (R$)', 'Observações',
        ];

        return response()->streamDownload(function () use ($consulta, $cabecalho) {
            $saida = fopen('php://output', 'w');
            fwrite($saida, "\xEF\xBB\xBF"); // BOM para o Excel reconhecer UTF-8.
            fputcsv($saida, $cabecalho, ';', '"', '');

            foreach ($consulta->lazy(500) as $l) {
                fputcsv($saida, [
                    $l->data->format('d/m/Y'),
                    $l->tipo->rotulo(),
                    Csv::texto($l->categoria?->nome),
                    Csv::texto($l->categoria?->grupo),
                    Csv::texto($l->descricao),
                    Csv::texto($l->contato?->nome),
                    $l->forma_pagamento->rotulo(),
                    Csv::numero($l->valor),
                    $l->status->rotuloPara($l->tipo),
                    $l->parcelas_cartao,
                    Csv::numero($l->taxa_percentual, 3),
                    Csv::numero($l->taxa_valor),
                    Csv::numero($l->valor_liquido),
                    Csv::numero($l->liquido_informado),
                    Csv::numero($l->diferenca()),
                    Csv::numero($l->valor_caixa),
                    Csv::texto($l->observacoes),
                ], ';', '"', '');
            }

            fclose($saida);
        }, 'lancamentos-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array<string, mixed> */
    private function dadosFormulario(Lancamento $lancamento): array
    {
        return [
            'lancamento' => $lancamento,
            'categorias' => Categoria::query()
                ->where(fn ($q) => $q->where('ativo', true)->when($lancamento->categoria_id, fn ($q) => $q->orWhere('id', $lancamento->categoria_id)))
                ->ordenadas()
                ->get(),
            'contatos' => Contato::query()->where('ativo', true)->orderBy('nome')->limit(2000)->pluck('nome'),
            'taxas' => TaxaCartao::mapa(),
        ];
    }
}
