<?php

namespace App\Http\Controllers;

use App\Enums\TipoLancamento;
use App\Services\RelatorioFinanceiro;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PainelController extends Controller
{
    public function __invoke(Request $request, RelatorioFinanceiro $relatorio): View
    {
        $validado = $request->validate([
            'ano' => ['nullable', 'integer', 'between:2000,2099'],
            'mes' => ['nullable', 'integer', 'between:0,12'],
        ]);

        $ano = (int) ($validado['ano'] ?? now()->year);
        $mes = (int) ($validado['mes'] ?? 0);

        if ($mes > 0) {
            $inicio = Carbon::create($ano, $mes, 1)->startOfDay();
            $fim = $inicio->copy()->endOfMonth();
            $inicioAnterior = $inicio->copy()->subMonthNoOverflow();
            $fimAnterior = $inicioAnterior->copy()->endOfMonth();
            $rotuloPeriodo = RelatorioFinanceiro::MESES[$mes].' de '.$ano;
            $rotuloComparacao = 'vs. mês anterior';
        } else {
            $inicio = Carbon::create($ano, 1, 1)->startOfDay();
            $fim = $inicio->copy()->endOfYear();
            $inicioAnterior = $inicio->copy()->subYear();
            $fimAnterior = $inicioAnterior->copy()->endOfYear();
            $rotuloPeriodo = 'Ano de '.$ano;
            $rotuloComparacao = 'vs. ano anterior';
        }

        $indicadores = $relatorio->indicadores($inicio, $fim);
        $anterior = $relatorio->indicadores($inicioAnterior, $fimAnterior);
        $serie = $relatorio->serieMensal($ano);

        return view('painel.index', [
            'ano' => $ano,
            'mes' => $mes,
            'anos' => $relatorio->anosDisponiveis(),
            'rotuloPeriodo' => $rotuloPeriodo,
            'rotuloComparacao' => $rotuloComparacao,
            'indicadores' => $indicadores,
            'variacoes' => $this->variacoes($indicadores, $anterior),
            'serie' => $serie,
            'despesasPorCategoria' => $relatorio->porCategoria(TipoLancamento::Saida, $inicio, $fim),
            'receitasPorCategoria' => $relatorio->porCategoria(TipoLancamento::Entrada, $inicio, $fim),
            'pendencias' => $relatorio->pendencias(),
            'principaisPacientes' => $relatorio->principaisPacientes($inicio, $fim),
        ]);
    }

    /**
     * @param  array<string, mixed>  $atual
     * @param  array<string, mixed>  $anterior
     * @return array<string, ?float>
     */
    private function variacoes(array $atual, array $anterior): array
    {
        $variacao = fn (string $chave) => ($anterior[$chave] ?? 0) != 0
            ? round(($atual[$chave] - $anterior[$chave]) / abs($anterior[$chave]) * 100, 1)
            : null;

        return [
            'receita' => $variacao('receita'),
            'despesas' => $variacao('despesas'),
            'lucro' => $variacao('lucro'),
        ];
    }
}
