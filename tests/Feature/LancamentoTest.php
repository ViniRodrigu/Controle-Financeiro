<?php

namespace Tests\Feature;

use App\Enums\StatusLancamento;
use App\Models\AuditLog;
use App\Models\Categoria;
use App\Models\Contato;
use App\Models\Lancamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LancamentoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = User::factory()->create();
    }

    private function dados(array $extra = []): array
    {
        return array_merge([
            'tipo' => 'saida',
            'data' => '2026-09-04',
            'categoria_id' => Categoria::factory()->create()->id,
            'forma_pagamento' => 'pix',
            'status' => 'pago',
            'valor' => '39,90',
        ], $extra);
    }

    public function test_registra_entrada_no_credito_calculando_taxa_no_servidor(): void
    {
        $categoria = Categoria::factory()->entrada()->create(['nome' => 'Pacotes']);

        $this->actingAs($this->usuario)->post('/lancamentos', [
            'tipo' => 'entrada',
            'data' => '2026-09-02',
            'categoria_id' => $categoria->id,
            'forma_pagamento' => 'credito',
            'parcelas_cartao' => 6,
            'taxa_percentual' => '8,00',
            'status' => 'pago',
            'valor' => '800,00',
            'contato_nome' => '  Edinalva  ',
            'descricao' => '3 sessões de peeling',
            // Valores calculados vindos do navegador são ignorados.
            'valor_caixa' => '999999',
            'taxa_valor' => '0',
        ])->assertRedirect('/lancamentos');

        $lancamento = Lancamento::firstOrFail();
        $this->assertSame('64.00', $lancamento->taxa_valor);
        $this->assertSame('736.00', $lancamento->valor_caixa);
        $this->assertSame('Edinalva', $lancamento->contato->nome);
        $this->assertSame($this->usuario->id, $lancamento->criado_por);
        $this->assertDatabaseHas('audit_logs', ['acao' => 'criado', 'auditable_type' => 'lancamento', 'user_id' => $this->usuario->id]);
    }

    public function test_reaproveita_o_contato_existente_pelo_nome(): void
    {
        Contato::factory()->create(['nome' => 'Karina']);
        $categoria = Categoria::factory()->entrada()->create();

        $this->actingAs($this->usuario)->post('/lancamentos', $this->dados([
            'tipo' => 'entrada', 'categoria_id' => $categoria->id, 'contato_nome' => 'karina',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, Contato::count());
    }

    public function test_aceita_valor_no_formato_brasileiro(): void
    {
        $this->actingAs($this->usuario)->post('/lancamentos', $this->dados(['valor' => '1.110,94']))->assertSessionHasNoErrors();

        $this->assertSame('1110.94', Lancamento::firstOrFail()->valor);
    }

    public function test_recusa_categoria_de_outro_tipo(): void
    {
        $receita = Categoria::factory()->entrada()->create();

        $this->actingAs($this->usuario)->post('/lancamentos', $this->dados(['categoria_id' => $receita->id]))
            ->assertSessionHasErrors('categoria_id');

        $this->assertSame(0, Lancamento::count());
    }

    public function test_recusa_valores_invalidos(): void
    {
        foreach (['0', '-10', 'abc', '1e5', '10,999'] as $valor) {
            $this->actingAs($this->usuario)->post('/lancamentos', $this->dados(['valor' => $valor]))
                ->assertSessionHasErrors('valor');
        }

        $this->assertSame(0, Lancamento::count());
    }

    public function test_liquido_informado_nao_pode_superar_o_bruto(): void
    {
        $categoria = Categoria::factory()->entrada()->create();

        $this->actingAs($this->usuario)->post('/lancamentos', $this->dados([
            'tipo' => 'entrada', 'categoria_id' => $categoria->id, 'valor' => '100', 'liquido_informado' => '150',
        ]))->assertSessionHasErrors('liquido_informado');
    }

    public function test_parcelamento_divide_o_valor_e_gera_pendentes_mensais(): void
    {
        $this->actingAs($this->usuario)->post('/lancamentos', $this->dados([
            'valor' => '100,00',
            'data' => '2026-01-31',
            'descricao' => 'Material',
            'repetir' => '1',
            'repetir_quantidade' => 3,
            'repetir_modo' => 'dividir',
            'repetir_frequencia' => 'mensal',
        ]))->assertSessionHasNoErrors();

        $parcelas = Lancamento::orderBy('parcela_numero')->get();

        $this->assertCount(3, $parcelas);
        $this->assertSame(['33.34', '33.33', '33.33'], $parcelas->pluck('valor')->all());
        $this->assertSame(['2026-01-31', '2026-02-28', '2026-03-31'], $parcelas->map(fn ($p) => $p->data->toDateString())->all());
        $this->assertSame(StatusLancamento::Pago, $parcelas[0]->status);
        $this->assertSame(StatusLancamento::Pendente, $parcelas[2]->status);
        $this->assertSame('Material (2/3)', $parcelas[1]->descricao);
        $this->assertSame(1, $parcelas->pluck('grupo_parcelamento')->unique()->count());
    }

    public function test_dar_baixa_marca_como_pago_na_data_efetiva(): void
    {
        $lancamento = Lancamento::factory()->pendente()->create(['data' => '2026-10-01']);

        $this->actingAs($this->usuario)->patch("/lancamentos/{$lancamento->id}/baixar", ['data_pagamento' => '2026-10-05'])
            ->assertSessionHas('sucesso');

        $lancamento->refresh();
        $this->assertSame(StatusLancamento::Pago, $lancamento->status);
        $this->assertSame('2026-10-05', $lancamento->data->toDateString());
        $this->assertSame($this->usuario->id, $lancamento->atualizado_por);
    }

    public function test_edicao_registra_antes_e_depois_na_auditoria(): void
    {
        $lancamento = Lancamento::factory()->create(['valor' => '100.00', 'valor_caixa' => '100.00']);

        $this->actingAs($this->usuario)->put("/lancamentos/{$lancamento->id}", $this->dados([
            'categoria_id' => $lancamento->categoria_id, 'valor' => '120,00',
        ]))->assertRedirect("/lancamentos/{$lancamento->id}");

        $registro = AuditLog::where('acao', 'atualizado')->where('auditable_id', $lancamento->id)->firstOrFail();
        // Comparação numérica: SQLite devolve 100 e MySQL "100.00" para o valor original.
        $this->assertEquals(100.0, (float) $registro->valores_antigos['valor']);
        $this->assertEquals(120.0, (float) $registro->valores_novos['valor']);
    }

    public function test_exclusao_vai_para_a_lixeira_e_admin_restaura(): void
    {
        $lancamento = Lancamento::factory()->create();

        $this->actingAs($this->usuario)->delete("/lancamentos/{$lancamento->id}")->assertRedirect('/lancamentos');
        $this->assertSoftDeleted($lancamento);

        // Financeiro não restaura nem vê o item excluído.
        $this->actingAs($this->usuario)->patch("/lancamentos/{$lancamento->id}/restaurar")->assertForbidden();
        $this->actingAs($this->usuario)->get("/lancamentos/{$lancamento->id}")->assertNotFound();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch("/lancamentos/{$lancamento->id}/restaurar")->assertRedirect();
        $this->assertNotSoftDeleted($lancamento);
    }

    public function test_conteudo_digitado_e_escapado_contra_xss(): void
    {
        Lancamento::factory()->create(['descricao' => '<script>alert("x")</script>']);

        $this->actingAs($this->usuario)->get('/lancamentos')
            ->assertOk()
            ->assertDontSee('<script>alert("x")</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_exportacao_csv_respeita_filtros_e_neutraliza_formulas(): void
    {
        Lancamento::factory()->create(['descricao' => '=HYPERLINK("http://malicioso")', 'data' => '2026-09-10']);
        Lancamento::factory()->create(['descricao' => 'Fora do período', 'data' => '2025-01-10']);

        $resposta = $this->actingAs($this->usuario)->get('/lancamentos/exportar?de=2026-09-01&ate=2026-09-30')->assertOk();
        $csv = $resposta->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Valor no caixa (R$)', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('Fora do período', $csv);
    }

    public function test_filtro_invalido_volta_para_a_lista_limpa(): void
    {
        $this->actingAs($this->usuario)->get('/lancamentos?tipo=hackeado&de=nao-e-data')
            ->assertRedirect('/lancamentos');
    }
}
