<?php

namespace App\Http\Controllers;

use App\Enums\TipoLancamento;
use App\Http\Requests\CategoriaRequest;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function index(): View
    {
        $categorias = Categoria::query()->withCount('lancamentos')->ordenadas()->get();

        return view('categorias.index', [
            'porTipo' => $categorias->groupBy(fn (Categoria $c) => $c->tipo->value),
        ]);
    }

    public function create(Request $request): View
    {
        return view('categorias.create', [
            'categoria' => new Categoria([
                'tipo' => TipoLancamento::tryFrom((string) $request->query('tipo')) ?? TipoLancamento::Saida,
                'cor' => '#7b3f6e',
                'ativo' => true,
            ]),
            'grupos' => $this->grupos(),
        ]);
    }

    public function store(CategoriaRequest $request): RedirectResponse
    {
        Categoria::create($request->validated());

        return redirect()->route('categorias.index')->with('sucesso', 'Categoria criada.');
    }

    public function edit(Categoria $categoria): View
    {
        return view('categorias.edit', ['categoria' => $categoria, 'grupos' => $this->grupos()]);
    }

    public function update(CategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        if ($categoria->lancamentos()->exists() && $request->input('tipo') !== $categoria->tipo->value) {
            return back()->withInput()->with('erro', 'Esta categoria já tem lançamentos; não é possível trocar o tipo.');
        }

        $categoria->update($request->validated());

        return redirect()->route('categorias.index')->with('sucesso', 'Categoria atualizada.');
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        if ($categoria->lancamentos()->withTrashed()->exists()) {
            return back()->with('erro', 'Esta categoria possui lançamentos. Desative-a em vez de excluir, para preservar o histórico.');
        }

        $categoria->delete();

        return redirect()->route('categorias.index')->with('sucesso', 'Categoria excluída.');
    }

    /** @return list<string> */
    private function grupos(): array
    {
        return collect(Categoria::GRUPOS)
            ->merge(Categoria::query()->distinct()->pluck('grupo'))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
