<?php

namespace Tests\Unit;

use App\Enums\FormaPagamento;
use App\Enums\TipoLancamento;
use App\Services\CalculadoraLancamento;
use PHPUnit\Framework\TestCase;

/** Casos reais da planilha original (aba Lançamentos). */
class CalculadoraLancamentoTest extends TestCase
{
    private CalculadoraLancamento $calculadora;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculadora = new CalculadoraLancamento;
    }

    public function test_entrada_com_taxa_percentual_linha_2_da_planilha(): void
    {
        // R$ 800 no crédito 6x a 8% => taxa R$ 64, líquido R$ 736.
        $r = $this->calculadora->calcular(TipoLancamento::Entrada, '800', FormaPagamento::Credito, 6, '8');

        $this->assertSame('64.00', $r['taxa_valor']);
        $this->assertSame('736.00', $r['valor_liquido']);
        $this->assertSame('736.00', $r['valor_caixa']);
        $this->assertSame(6, $r['parcelas_cartao']);
    }

    public function test_liquido_informado_prevalece_sobre_a_taxa_linha_3_da_planilha(): void
    {
        // R$ 2.980 com 11% informado, mas recebeu de fato R$ 2.848,34.
        $r = $this->calculadora->calcular(TipoLancamento::Entrada, '2980', FormaPagamento::Credito, 10, '11', '2848.34');

        $this->assertSame('131.66', $r['taxa_valor']);
        $this->assertSame('2848.34', $r['valor_caixa']);
        $this->assertSame('196.14', $this->calculadora->diferenca('2980', '11', '2848.34'));
    }

    public function test_entrada_sem_taxa_entra_pelo_valor_bruto(): void
    {
        $r = $this->calculadora->calcular(TipoLancamento::Entrada, '150', FormaPagamento::Dinheiro);

        $this->assertNull($r['taxa_valor']);
        $this->assertNull($r['parcelas_cartao']);
        $this->assertSame('150.00', $r['valor_caixa']);
    }

    public function test_saida_ignora_campos_de_taxa(): void
    {
        $r = $this->calculadora->calcular(TipoLancamento::Saida, '459.3', FormaPagamento::Credito, 2, '5.2', '400');

        $this->assertNull($r['taxa_percentual']);
        $this->assertNull($r['liquido_informado']);
        $this->assertSame('459.30', $r['valor_caixa']);
    }

    public function test_arredonda_meio_para_cima_sem_erro_de_ponto_flutuante(): void
    {
        // 0,1 + 0,2 em float daria 0,30000000000000004; aqui tudo é decimal exato.
        $r = $this->calculadora->calcular(TipoLancamento::Entrada, '1000.10', FormaPagamento::Debito, null, '1.18');

        $this->assertSame('11.80', $r['taxa_valor']);
        $this->assertSame('988.30', $r['valor_caixa']);

        $r = $this->calculadora->calcular(TipoLancamento::Entrada, '12.50', FormaPagamento::Credito, 1, '1');
        $this->assertSame('0.13', $r['taxa_valor']);
    }
}
