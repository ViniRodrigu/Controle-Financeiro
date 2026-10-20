<?php

namespace Database\Seeders;

use App\Enums\FormaPagamento;
use App\Models\TaxaCartao;
use Illuminate\Database\Seeder;

/** Tabela da aba "Taxas da maquininha" da planilha original (Mastercard). */
class TaxaCartaoSeeder extends Seeder
{
    public function run(): void
    {
        $mastercard = 'Mastercard · repasse em 1 dia útil';

        $taxas = [
            ['debito', 1, '1.180', $mastercard],
            ['pix', 1, '0.000', 'Taxa Pix não informada; confirmar se há cobrança'],
            ['credito', 1, '3.050', $mastercard],
            ['credito', 2, '5.200', $mastercard],
            ['credito', 3, '5.900', $mastercard],
            ['credito', 4, '6.600', $mastercard],
            ['credito', 5, '7.300', $mastercard],
            ['credito', 6, '8.000', $mastercard],
            ['credito', 7, '8.730', $mastercard],
            ['credito', 8, '9.430', $mastercard],
            ['credito', 9, '10.130', $mastercard],
            ['credito', 10, '10.830', $mastercard],
            ['credito', 11, null, 'Taxa não informada · não calcular até preencher'],
            ['credito', 12, null, 'Taxa não informada · não calcular até preencher'],
        ];

        foreach ($taxas as [$forma, $parcelas, $taxa, $observacao]) {
            TaxaCartao::query()->updateOrCreate(
                ['forma_pagamento' => FormaPagamento::from($forma), 'parcelas' => $parcelas],
                ['taxa_percentual' => $taxa, 'observacao' => $observacao],
            );
        }
    }
}
