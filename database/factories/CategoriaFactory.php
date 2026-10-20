<?php

namespace Database\Factories;

use App\Enums\TipoLancamento;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->words(2, true),
            'tipo' => TipoLancamento::Saida,
            'grupo' => 'Operacionais',
            'cor' => '#7b3f6e',
            'ativo' => true,
            'ordem' => 0,
        ];
    }

    public function entrada(): static
    {
        return $this->state(fn () => ['tipo' => TipoLancamento::Entrada, 'grupo' => 'Receitas']);
    }
}
