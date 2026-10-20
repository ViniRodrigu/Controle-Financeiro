<?php

namespace App\Services;

use App\Enums\StatusLancamento;
use App\Enums\TipoLancamento;
use App\Models\Lancamento;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Consultas consolidadas do painel e do resumo mensal.
 * Assim como na planilha, os resultados consideram o regime de caixa:
 * entradas pelo valor que entrou no caixa (líquido de taxas) e saídas pelo valor pago.
 */
final class RelatorioFinanceiro
{
    public const MESES = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
        7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

    /**
     * @return array{receita: float, bruto: float, taxas: float, despesas: float, lucro: float, margem: ?float, a_receber: float, a_pagar: float, atendimentos: int, ticket_medio: ?float}
     */
    public function indicadores(CarbonInterface $inicio, CarbonInterface $fim): array
    {
        [$entrada, $saida, $pago, $pendente] = $this->chaves();

        $linha = Lancamento::query()
            ->periodo($inicio, $fim)
            ->selectRaw('COALESCE(SUM(CASE WHEN tipo = ? AND status = ? THEN valor_caixa END), 0) AS receita', [$entrada, $pago])
            ->selectRaw('COALESCE(SUM(CASE WHEN tipo = ? AND status = ? THEN valor END), 0) AS bruto', [$entrada, $pago])
            ->selectRaw('COALESCE(SUM(CASE WHEN tipo = ? AND status = ? THEN taxa_valor END), 0) AS taxas', [$entrada, $pago])
            ->selectRaw('COALESCE(SUM(CASE WHEN tipo = ? AND status = ? THEN valor END), 0) AS despesas', [$saida, $pago])
            ->selectRaw('COALESCE(SUM(CASE WHEN tipo = ? AND status = ? THEN valor_caixa END), 0) AS a_receber', [$entrada, $pendente])
            ->selectRaw('COALESCE(SUM(CASE WHEN tipo = ? AND status = ? THEN valor END), 0) AS a_pagar', [$saida, $pendente])
            ->selectRaw('COALESCE(SUM(CASE WHEN tipo = ? AND status = ? THEN 1 ELSE 0 END), 0) AS atendimentos', [$entrada, $pago])
            ->toBase()
            ->first();

        $receita = round((float) $linha->receita, 2);
        $despesas = round((float) $linha->despesas, 2);
        $lucro = round($receita - $despesas, 2);
        $atendimentos = (int) $linha->atendimentos;

        return [
            'receita' => $receita,
            'bruto' => round((float) $linha->bruto, 2),
            'taxas' => round((float) $linha->taxas, 2),
            'despesas' => $despesas,
            'lucro' => $lucro,
            'margem' => $receita > 0 ? round($lucro / $receita * 100, 2) : null,
            'a_receber' => round((float) $linha->a_receber, 2),
            'a_pagar' => round((float) $linha->a_pagar, 2),
            'atendimentos' => $atendimentos,
            'ticket_medio' => $atendimentos > 0 ? round((float) $linha->bruto / $atendimentos, 2) : null,
        ];
    }

    /**
     * Linhas do resumo mensal (equivalente à aba "Resumo mensal", já corrigida).
     *
     * @return array{meses: list<array<string, mixed>>, total: array<string, mixed>}
     */
    public function serieMensal(int $ano): array
    {
        [$entrada, $saida, $pago, $pendente] = $this->chaves();
        $mes = $this->expressaoMes();

        $linhas = Lancamento::query()
            ->periodo(Carbon::create($ano, 1, 1), Carbon::create($ano, 12, 31))
            ->selectRaw("{$mes} AS mes, tipo, status, SUM(valor) AS bruto, SUM(valor_caixa) AS caixa")
            ->groupByRaw("{$mes}, tipo, status")
            ->toBase()
            ->get();

        $meses = [];
        foreach (self::MESES as $numero => $nome) {
            $doMes = $linhas->where('mes', $numero);
            $soma = fn (string $tipo, string $status, string $campo) => round((float) $doMes
                ->where('tipo', $tipo)->where('status', $status)->sum($campo), 2);

            $entradas = $soma($entrada, $pago, 'caixa');
            $saidas = $soma($saida, $pago, 'bruto');

            $meses[] = [
                'mes' => $numero,
                'nome' => $nome,
                'entradas' => $entradas,
                'saidas' => $saidas,
                'lucro' => round($entradas - $saidas, 2),
                'a_receber' => $soma($entrada, $pendente, 'caixa'),
                'a_pagar' => $soma($saida, $pendente, 'bruto'),
                'margem' => $entradas > 0 ? round(($entradas - $saidas) / $entradas * 100, 2) : null,
            ];
        }

        $colecao = collect($meses);
        $entradasAno = round($colecao->sum('entradas'), 2);
        $saidasAno = round($colecao->sum('saidas'), 2);

        return [
            'meses' => $meses,
            'total' => [
                'entradas' => $entradasAno,
                'saidas' => $saidasAno,
                'lucro' => round($entradasAno - $saidasAno, 2),
                'a_receber' => round($colecao->sum('a_receber'), 2),
                'a_pagar' => round($colecao->sum('a_pagar'), 2),
                'margem' => $entradasAno > 0 ? round(($entradasAno - $saidasAno) / $entradasAno * 100, 2) : null,
            ],
        ];
    }

    /**
     * Totais pagos/recebidos por categoria no período, com participação percentual.
     *
     * @return Collection<int, object>
     */
    public function porCategoria(TipoLancamento $tipo, CarbonInterface $inicio, CarbonInterface $fim): Collection
    {
        $campo = $tipo === TipoLancamento::Entrada ? 'lancamentos.valor_caixa' : 'lancamentos.valor';

        $linhas = Lancamento::query()
            ->periodo($inicio, $fim)
            ->join('categorias', 'categorias.id', '=', 'lancamentos.categoria_id')
            ->where('lancamentos.tipo', $tipo)
            ->where('lancamentos.status', StatusLancamento::Pago)
            ->groupBy('categorias.id', 'categorias.nome', 'categorias.grupo', 'categorias.cor')
            ->select('categorias.id', 'categorias.nome', 'categorias.grupo', 'categorias.cor')
            ->selectRaw("SUM({$campo}) AS total, COUNT(*) AS quantidade")
            ->orderByDesc('total')
            ->toBase()
            ->get();

        $soma = (float) $linhas->sum('total');

        return $linhas->map(function (object $linha) use ($soma) {
            $linha->total = round((float) $linha->total, 2);
            $linha->percentual = $soma > 0 ? round($linha->total / $soma * 100, 2) : 0.0;

            return $linha;
        });
    }

    /**
     * Matriz categoria x mês (regime de caixa) para o relatório anual.
     *
     * @return array{receitas: list<array<string, mixed>>, despesas: array<string, array<string, mixed>>, totais: array<string, array<int|string, float>>}
     */
    public function matrizCategorias(int $ano): array
    {
        $mes = $this->expressaoMes();

        $linhas = Lancamento::query()
            ->periodo(Carbon::create($ano, 1, 1), Carbon::create($ano, 12, 31))
            ->join('categorias', 'categorias.id', '=', 'lancamentos.categoria_id')
            ->where('lancamentos.status', StatusLancamento::Pago)
            ->selectRaw("{$mes} AS mes")
            ->addSelect('lancamentos.tipo', 'categorias.id AS categoria_id', 'categorias.nome', 'categorias.grupo', 'categorias.cor')
            ->selectRaw('SUM(lancamentos.valor_caixa) AS caixa, SUM(lancamentos.valor) AS bruto, SUM(COALESCE(lancamentos.taxa_valor, 0)) AS taxas')
            ->groupByRaw("{$mes}, lancamentos.tipo, categorias.id, categorias.nome, categorias.grupo, categorias.cor")
            ->toBase()
            ->get();

        $vazio = array_fill_keys(array_keys(self::MESES), 0.0) + ['total' => 0.0];
        $linhaDe = function (Collection $itens, string $campo) use ($vazio): array {
            $valores = $vazio;
            foreach ($itens as $item) {
                $valores[(int) $item->mes] = round($valores[(int) $item->mes] + (float) $item->{$campo}, 2);
            }
            $valores['total'] = round(array_sum(array_intersect_key($valores, self::MESES)), 2);

            return $valores;
        };

        $entradas = $linhas->where('tipo', TipoLancamento::Entrada->value);
        $saidas = $linhas->where('tipo', TipoLancamento::Saida->value);

        $receitas = $entradas->groupBy('categoria_id')
            ->map(fn (Collection $itens) => ['nome' => $itens->first()->nome, 'cor' => $itens->first()->cor, 'valores' => $linhaDe($itens, 'caixa')])
            ->sortByDesc(fn ($l) => $l['valores']['total'])
            ->values()
            ->all();

        $despesas = $saidas->groupBy('grupo')
            ->map(fn (Collection $doGrupo) => [
                'valores' => $linhaDe($doGrupo, 'bruto'),
                'categorias' => $doGrupo->groupBy('categoria_id')
                    ->map(fn (Collection $itens) => ['nome' => $itens->first()->nome, 'cor' => $itens->first()->cor, 'valores' => $linhaDe($itens, 'bruto')])
                    ->sortByDesc(fn ($l) => $l['valores']['total'])
                    ->values()
                    ->all(),
            ])
            ->sortByDesc(fn ($g) => $g['valores']['total'])
            ->all();

        $totalReceitas = $linhaDe($entradas, 'caixa');
        $totalDespesas = $linhaDe($saidas, 'bruto');
        $resultado = [];
        foreach ($vazio as $chave => $_) {
            $resultado[$chave] = round($totalReceitas[$chave] - $totalDespesas[$chave], 2);
        }

        return [
            'receitas' => $receitas,
            'despesas' => $despesas,
            'totais' => [
                'receitas' => $totalReceitas,
                'despesas' => $totalDespesas,
                'resultado' => $resultado,
                'taxas' => $linhaDe($entradas, 'taxas'),
            ],
        ];
    }

    /**
     * Pendências mais próximas (vencidas primeiro).
     *
     * @return array{itens: Collection<int, Lancamento>, vencidos: int, total_receber: float, total_pagar: float}
     */
    public function pendencias(int $limite = 8): array
    {
        $base = Lancamento::query()->where('status', StatusLancamento::Pendente);

        $totais = (clone $base)
            ->selectRaw('COALESCE(SUM(CASE WHEN tipo = ? THEN valor_caixa END), 0) AS receber', [TipoLancamento::Entrada->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN tipo = ? THEN valor END), 0) AS pagar', [TipoLancamento::Saida->value])
            ->toBase()
            ->first();

        return [
            'itens' => (clone $base)->with(['categoria', 'contato'])->orderBy('data')->orderBy('id')->limit($limite)->get(),
            'vencidos' => (clone $base)->where('data', '<', today()->toDateString())->count(),
            'total_receber' => round((float) $totais->receber, 2),
            'total_pagar' => round((float) $totais->pagar, 2),
        ];
    }

    /**
     * Pacientes que mais geraram receita no período.
     *
     * @return Collection<int, object>
     */
    public function principaisPacientes(CarbonInterface $inicio, CarbonInterface $fim, int $limite = 5): Collection
    {
        return Lancamento::query()
            ->periodo($inicio, $fim)
            ->join('contatos', 'contatos.id', '=', 'lancamentos.contato_id')
            ->where('lancamentos.tipo', TipoLancamento::Entrada)
            ->where('lancamentos.status', StatusLancamento::Pago)
            ->groupBy('contatos.id', 'contatos.nome')
            ->select('contatos.id', 'contatos.nome')
            ->selectRaw('SUM(lancamentos.valor) AS total, COUNT(*) AS quantidade')
            ->orderByDesc('total')
            ->limit($limite)
            ->toBase()
            ->get();
    }

    /** @return list<int> */
    public function anosDisponiveis(): array
    {
        $atual = (int) now()->year;
        $primeiro = Lancamento::query()->min('data');
        $ultimo = Lancamento::query()->max('data');

        $inicio = $primeiro ? min((int) Carbon::parse($primeiro)->year, $atual) : $atual;
        $fim = $ultimo ? max((int) Carbon::parse($ultimo)->year, $atual) : $atual;

        return range($fim, $inicio);
    }

    /** @return array{0: string, 1: string, 2: string, 3: string} */
    private function chaves(): array
    {
        return [
            TipoLancamento::Entrada->value,
            TipoLancamento::Saida->value,
            StatusLancamento::Pago->value,
            StatusLancamento::Pendente->value,
        ];
    }

    /** Expressão SQL fixa (sem entrada do usuário) para extrair o mês da data em cada banco. */
    private function expressaoMes(): string
    {
        return match ((new Lancamento)->getConnection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%m', lancamentos.data) AS INTEGER)",
            'pgsql' => 'CAST(EXTRACT(MONTH FROM lancamentos.data) AS INTEGER)',
            default => 'MONTH(lancamentos.data)',
        };
    }
}
