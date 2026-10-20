<?php

namespace Database\Factories;

use App\Enums\FormaPagamento;
use App\Enums\StatusLancamento;
use App\Enums\TipoLancamento;
use App\Models\Categoria;
use App\Models\Lancamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lancamento>
 */
class LancamentoFactory extends Factory
{
    public function definition(): array
    {
        $valor = number_format(fake()->randomFloat(2, 50, 3000), 2, '.', '');

        return [
            'data' => fake()->dateTimeBetween('-2 months')->format('Y-m-d'),
            'tipo' => TipoLancamento::Saida,
            'categoria_id' => Categoria::factory(),
            'descricao' => fake()->sentence(3),
            'forma_pagamento' => FormaPagamento::Pix,
            'status' => StatusLancamento::Pago,
            'valor' => $valor,
            'valor_caixa' => $valor,
        ];
    }

    public function entrada(): static
    {
        return $this->state(fn () => [
            'tipo' => TipoLancamento::Entrada,
            'categoria_id' => Categoria::factory()->entrada(),
        ]);
    }

    public function pendente(): static
    {
        return $this->state(fn () => ['status' => StatusLancamento::Pendente]);
    }
}
