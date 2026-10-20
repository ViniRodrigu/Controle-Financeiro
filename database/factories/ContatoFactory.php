<?php

namespace Database\Factories;

use App\Enums\TipoContato;
use App\Models\Contato;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contato>
 */
class ContatoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'tipo' => TipoContato::Paciente,
            'telefone' => fake()->numerify('(11) 9####-####'),
            'ativo' => true,
        ];
    }
}
