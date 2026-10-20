<?php

namespace Database\Seeders;

use App\Enums\TipoLancamento;
use App\Models\Categoria;
use Illuminate\Database\Seeder;

/** Categorias da aba "Categorias" da planilha original. */
class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            // [tipo, nome, grupo, cor]
            ['entrada', 'Procedimentos', 'Receitas', '#7b3f6e'],
            ['entrada', 'Consultas', 'Receitas', '#3a86c8'],
            ['entrada', 'Pacotes', 'Receitas', '#1e9e6a'],
            ['entrada', 'Outras receitas', 'Receitas', '#8a94a6'],
            ['saida', 'Coworking / sala', 'Fixos', '#6d5bd0'],
            ['saida', 'Marketing / agência', 'Fixos', '#e0607e'],
            ['saida', 'Contabilidade', 'Fixos', '#3a86c8'],
            ['saida', 'Sistemas / assinaturas', 'Fixos', '#2bb3c0'],
            ['saida', 'Internet / telefone', 'Fixos', '#5a8dee'],
            ['saida', 'Pró-labore', 'Fixos', '#b0743c'],
            ['saida', 'Impostos', 'Fixos', '#c0392b'],
            ['saida', 'Taxas da maquininha', 'Variáveis', '#8a94a6'],
            ['saida', 'Materiais de procedimento', 'Variáveis', '#e8a33d'],
            ['saida', 'Descartáveis', 'Variáveis', '#d4a72c'],
            ['saida', 'Skincare / pós-procedimento', 'Experiência da paciente', '#c86b98'],
            ['saida', 'Café / bebidas', 'Experiência da paciente', '#8d6e63'],
            ['saida', 'Chocolates / brindes', 'Experiência da paciente', '#a1665e'],
            ['saida', 'Cartões / papelaria', 'Operacionais', '#7f8c8d'],
            ['saida', 'Limpeza / higiene', 'Operacionais', '#26a69a'],
            ['saida', 'Equipamentos / manutenção', 'Operacionais', '#546e7a'],
            ['saida', 'Cursos / capacitação', 'Operacionais', '#8e44ad'],
            ['saida', 'Outras despesas', 'Operacionais', '#95a5a6'],
        ];

        foreach ($categorias as $ordem => [$tipo, $nome, $grupo, $cor]) {
            Categoria::query()->updateOrCreate(
                ['nome' => $nome, 'tipo' => TipoLancamento::from($tipo)],
                ['grupo' => $grupo, 'cor' => $cor, 'ordem' => $ordem, 'ativo' => true],
            );
        }
    }
}
