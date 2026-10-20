<?php

namespace App\Http\Controllers;

use App\Services\RelatorioFinanceiro;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResumoController extends Controller
{
    public function __invoke(Request $request, RelatorioFinanceiro $relatorio): View
    {
        $validado = $request->validate(['ano' => ['nullable', 'integer', 'between:2000,2099']]);
        $ano = (int) ($validado['ano'] ?? now()->year);

        return view('resumo.index', [
            'ano' => $ano,
            'anos' => $relatorio->anosDisponiveis(),
            'serie' => $relatorio->serieMensal($ano),
            'matriz' => $relatorio->matrizCategorias($ano),
            'meses' => RelatorioFinanceiro::MESES,
        ]);
    }
}
