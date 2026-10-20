<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Lancamento;
use App\Models\User;
use App\Services\RelatorioFinanceiro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RelatoriosTest extends TestCase
{
    use RefreshDatabase;

    private function cenario(): void
    {
        $receita = Categoria::factory()->entrada()->create(['nome' => 'Procedimentos']);
        $marketing = Categoria::factory()->create(['nome' => 'Marketing / agência', 'grupo' => 'Fixos']);

        // Entrada de R$ 2.980 que rendeu R$ 2.848,34 no caixa.
        Lancamento::factory()->entrada()->create([
            'categoria_id' => $receita->id, 'data' => '2026-09-04', 'valor' => '2980.00',
            'taxa_valor' => '131.66', 'valor_liquido' => '2848.34', 'valor_caixa' => '2848.34',
        ]);
        Lancamento::factory()->create(['categoria_id' => $marketing->id, 'data' => '2026-09-09', 'valor' => '1110.94', 'valor_caixa' => '1110.94']);
        Lancamento::factory()->pendente()->create(['categoria_id' => $marketing->id, 'data' => '2026-10-09', 'valor' => '1110.94', 'valor_caixa' => '1110.94']);
        // Excluído não entra nos relatórios.
        Lancamento::factory()->create(['categoria_id' => $marketing->id, 'data' => '2026-09-10', 'valor' => '500.00', 'valor_caixa' => '500.00'])->delete();
    }

    public function test_indicadores_usam_regime_de_caixa(): void
    {
        $this->cenario();

        $r = app(RelatorioFinanceiro::class)->indicadores(Carbon::create(2026, 9, 1), Carbon::create(2026, 9, 30));

        $this->assertSame(2848.34, $r['receita']);
        $this->assertSame(2980.0, $r['bruto']);
        $this->assertSame(131.66, $r['taxas']);
        $this->assertSame(1110.94, $r['despesas']);
        $this->assertSame(1737.4, $r['lucro']);
        $this->assertSame(0.0, $r['a_pagar']);
    }

    public function test_resumo_mensal_corrige_o_bug_da_planilha(): void
    {
        // Na planilha, o resumo comparava o número do mês com o nome ("Setembro") e zerava tudo.
        $this->cenario();

        $serie = app(RelatorioFinanceiro::class)->serieMensal(2026);

        $this->assertSame(2848.34, $serie['meses'][8]['entradas']);
        $this->assertSame(1110.94, $serie['meses'][8]['saidas']);
        $this->assertSame(1110.94, $serie['meses'][9]['a_pagar']);
        $this->assertSame(1737.4, $serie['total']['lucro']);
    }

    public function test_matriz_por_categoria(): void
    {
        $this->cenario();

        $matriz = app(RelatorioFinanceiro::class)->matrizCategorias(2026);

        $this->assertSame(2848.34, $matriz['totais']['receitas'][9]);
        $this->assertSame(1110.94, $matriz['despesas']['Fixos']['valores']['total']);
        $this->assertSame(131.66, $matriz['totais']['taxas']['total']);
    }

    public function test_paginas_de_relatorio_carregam(): void
    {
        $this->cenario();
        $usuario = User::factory()->visualizador()->create();

        $this->actingAs($usuario)->get('/painel?ano=2026&mes=9')
            ->assertOk()
            ->assertSee('R$ 2.848,34')
            ->assertSee('Setembro de 2026');

        $this->actingAs($usuario)->get('/resumo?ano=2026')
            ->assertOk()
            ->assertSee('R$ 1.737,40');

        $this->actingAs($usuario)->get('/painel?ano=abc')->assertSessionHasErrors('ano');
    }
}
