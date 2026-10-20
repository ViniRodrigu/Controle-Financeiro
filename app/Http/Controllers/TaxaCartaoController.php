<?php

namespace App\Http\Controllers;

use App\Enums\FormaPagamento;
use App\Http\Requests\TaxaCartaoRequest;
use App\Models\TaxaCartao;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxaCartaoController extends Controller
{
    public function index(): View
    {
        $taxas = TaxaCartao::query()->orderBy('forma_pagamento')->orderBy('parcelas')->get();

        return view('taxas.index', [
            'porForma' => $taxas->groupBy(fn (TaxaCartao $t) => $t->forma_pagamento->value),
        ]);
    }

    public function create(): View
    {
        return view('taxas.create', ['taxa' => new TaxaCartao(['forma_pagamento' => FormaPagamento::Credito, 'parcelas' => 1])]);
    }

    public function store(TaxaCartaoRequest $request): RedirectResponse
    {
        TaxaCartao::create($request->validated());

        return redirect()->route('taxas.index')->with('sucesso', 'Taxa cadastrada.');
    }

    public function edit(TaxaCartao $taxa): View
    {
        return view('taxas.edit', compact('taxa'));
    }

    public function update(TaxaCartaoRequest $request, TaxaCartao $taxa): RedirectResponse
    {
        $taxa->update($request->validated());

        return redirect()->route('taxas.index')->with('sucesso', 'Taxa atualizada. Lançamentos antigos mantêm a taxa da época.');
    }

    public function destroy(TaxaCartao $taxa): RedirectResponse
    {
        $taxa->delete();

        return redirect()->route('taxas.index')->with('sucesso', 'Taxa excluída.');
    }
}
