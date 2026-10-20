<?php

namespace App\Models;

use App\Enums\FormaPagamento;
use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('taxas_cartao')]
#[Fillable(['forma_pagamento', 'parcelas', 'taxa_percentual', 'observacao'])]
class TaxaCartao extends Model
{
    use Auditavel, HasFactory;

    protected function casts(): array
    {
        return [
            'forma_pagamento' => FormaPagamento::class,
            'parcelas' => 'integer',
            'taxa_percentual' => 'decimal:3',
        ];
    }

    /**
     * Mapa "forma:parcelas" => taxa, usado pelo formulário de lançamentos para sugerir a taxa.
     *
     * @return array<string, float>
     */
    public static function mapa(): array
    {
        return static::query()
            ->whereNotNull('taxa_percentual')
            ->get()
            ->mapWithKeys(fn (self $taxa) => [
                $taxa->forma_pagamento->value.':'.$taxa->parcelas => (float) $taxa->taxa_percentual,
            ])
            ->all();
    }
}
