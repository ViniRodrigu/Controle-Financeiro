<?php

namespace App\Services;

use App\Enums\FormaPagamento;
use App\Enums\TipoLancamento;

/**
 * Regras de cálculo da planilha (colunas M, N, P e Q da aba Lançamentos),
 * feitas sempre no servidor e com bcmath para não haver erro de arredondamento.
 */
final class CalculadoraLancamento
{
    private const ESCALA = 6;

    /**
     * @return array{parcelas_cartao: ?int, taxa_percentual: ?string, taxa_valor: ?string, valor_liquido: ?string, liquido_informado: ?string, valor_caixa: string}
     */
    public function calcular(
        TipoLancamento $tipo,
        string $valor,
        ?FormaPagamento $forma = null,
        ?int $parcelas = null,
        ?string $taxaPercentual = null,
        ?string $liquidoInformado = null,
    ): array {
        $valor = $this->decimal($valor, 2);

        // Saídas não têm taxa de maquininha: o valor no caixa é o próprio valor pago.
        if ($tipo === TipoLancamento::Saida) {
            return [
                'parcelas_cartao' => null,
                'taxa_percentual' => null,
                'taxa_valor' => null,
                'valor_liquido' => null,
                'liquido_informado' => null,
                'valor_caixa' => $valor,
            ];
        }

        $taxaPercentual = $taxaPercentual === null ? null : $this->decimal($taxaPercentual, 3);
        $liquidoInformado = $liquidoInformado === null ? null : $this->decimal($liquidoInformado, 2);
        $taxaValor = null;
        $liquido = null;

        if ($liquidoInformado !== null) {
            // O líquido real recebido prevalece sobre a taxa estimada.
            $liquido = $liquidoInformado;
            $taxaValor = bcsub($valor, $liquidoInformado, 2);
        } elseif ($taxaPercentual !== null) {
            $taxaValor = $this->valorDaTaxa($valor, $taxaPercentual);
            $liquido = bcsub($valor, $taxaValor, 2);
        }

        return [
            'parcelas_cartao' => $forma?->permiteParcelas() ? max(1, min(12, (int) ($parcelas ?: 1))) : null,
            'taxa_percentual' => $taxaPercentual,
            'taxa_valor' => $taxaValor,
            'valor_liquido' => $liquido,
            'liquido_informado' => $liquidoInformado,
            'valor_caixa' => $liquido ?? $valor,
        ];
    }

    /**
     * Diferença entre o líquido informado e o previsto pela taxa (positivo = recebeu mais que o previsto).
     */
    public function diferenca(?string $valor, ?string $taxaPercentual, ?string $liquidoInformado): ?string
    {
        if ($valor === null || $taxaPercentual === null || $liquidoInformado === null) {
            return null;
        }

        $previsto = bcsub($this->decimal($valor, 2), $this->valorDaTaxa($valor, $taxaPercentual), 2);

        return bcsub($this->decimal($liquidoInformado, 2), $previsto, 2);
    }

    private function valorDaTaxa(string $valor, string $taxaPercentual): string
    {
        $bruto = bcdiv(bcmul($valor, $taxaPercentual, self::ESCALA), '100', self::ESCALA);

        return $this->arredondar($bruto);
    }

    /** Arredondamento "meio para cima" em 2 casas (valores aqui nunca são negativos). */
    private function arredondar(string $numero): string
    {
        return bcadd($numero, '0.005', 2);
    }

    private function decimal(string $numero, int $casas): string
    {
        return bcadd($numero, '0', $casas);
    }
}
