<?php

namespace App\Http\Controllers;

use App\Enums\StatusLancamento;
use App\Enums\TipoContato;
use App\Enums\TipoLancamento;
use App\Http\Requests\ContatoRequest;
use App\Models\Contato;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContatoController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:editar', except: ['index', 'show'])];
    }

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'busca' => ['nullable', 'string', 'max:100'],
            'tipo' => ['nullable', Rule::enum(TipoContato::class)],
        ]);
        $busca = Str::squish((string) ($filtros['busca'] ?? ''));

        $contatos = Contato::query()
            ->when($busca !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('nome', 'like', "%{$busca}%")
                ->orWhere('telefone', 'like', "%{$busca}%")
                ->orWhere('email', 'like', "%{$busca}%")))
            ->when($filtros['tipo'] ?? null, fn (Builder $q, $tipo) => $q->where('tipo', $tipo))
            ->withCount('lancamentos')
            ->withSum(['lancamentos as total_recebido' => fn (Builder $q) => $q
                ->where('tipo', TipoLancamento::Entrada)->where('status', StatusLancamento::Pago)], 'valor')
            ->withSum(['lancamentos as total_pago' => fn (Builder $q) => $q
                ->where('tipo', TipoLancamento::Saida)->where('status', StatusLancamento::Pago)], 'valor')
            ->withMax('lancamentos as ultimo_lancamento', 'data')
            ->orderBy('nome')
            ->paginate(25)
            ->withQueryString();

        return view('contatos.index', ['contatos' => $contatos, 'filtros' => $filtros + ['busca' => $busca]]);
    }

    public function show(Contato $contato): View
    {
        $lancamentos = $contato->lancamentos()->with('categoria')->orderByDesc('data')->orderByDesc('id')->paginate(20);

        $totais = $contato->lancamentos()->where('status', StatusLancamento::Pago)
            ->toBase()
            ->selectRaw('COALESCE(SUM(CASE WHEN tipo = ? THEN valor END), 0) AS recebido', [TipoLancamento::Entrada->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN tipo = ? THEN valor END), 0) AS pago', [TipoLancamento::Saida->value])
            ->selectRaw('SUM(CASE WHEN tipo = ? THEN 1 ELSE 0 END) AS atendimentos', [TipoLancamento::Entrada->value])
            ->first();

        return view('contatos.show', compact('contato', 'lancamentos', 'totais'));
    }

    public function create(): View
    {
        return view('contatos.create', ['contato' => new Contato(['tipo' => TipoContato::Paciente, 'ativo' => true])]);
    }

    public function store(ContatoRequest $request): RedirectResponse
    {
        $contato = Contato::create($request->validated());

        return redirect()->route('contatos.show', $contato)->with('sucesso', 'Cadastro criado.');
    }

    public function edit(Contato $contato): View
    {
        return view('contatos.edit', compact('contato'));
    }

    public function update(ContatoRequest $request, Contato $contato): RedirectResponse
    {
        $contato->update($request->validated());

        return redirect()->route('contatos.show', $contato)->with('sucesso', 'Cadastro atualizado.');
    }

    public function destroy(Contato $contato): RedirectResponse
    {
        $contato->delete();

        return redirect()->route('contatos.index')->with('sucesso', 'Cadastro excluído. Os lançamentos dele continuam no histórico.');
    }
}
