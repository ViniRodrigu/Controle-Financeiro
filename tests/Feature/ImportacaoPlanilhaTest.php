<?php

namespace Tests\Feature;

use App\Models\Lancamento;
use Database\Seeders\CategoriaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportacaoPlanilhaTest extends TestCase
{
    use RefreshDatabase;

    private string $arquivo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategoriaSeeder::class);

        $planilha = new Spreadsheet;
        $aba = $planilha->getActiveSheet()->setTitle('Lançamentos');
        $aba->fromArray([
            ['Data', 'Tipo', 'Categoria', 'Descrição', 'Paciente / fornecedor', 'Forma de pagamento', 'Valor (R$)', 'Status', 'Mês', 'Ano', 'Parcelas', 'Taxa (%)', 'Taxa (R$)', 'Valor líquido (R$)', 'Líquido informado (R$)'],
            [Date::stringToExcel('2026-09-02'), 'Entrada', 'Pacotes', '3 sessoes de peeling', 'Edinalva ', 'Crédito', 800, 'Pago / recebido', null, null, 6, 0.08, 64],
            [Date::stringToExcel('2026-09-04'), 'Entrada', 'Procedimentos', 'Botox', 'Karina', 'Crédito', 2980, 'Pago / recebido', null, null, 10, 0.11, 131.66],
            [Date::stringToExcel('2026-09-26'), 'Entrada', 'Procedimentos', 'Ácido', 'Isabela', 'Crédito', 3710, 'Pago / recebido', null, null, 10, null, 401.79],
            [null, null, null, null, null, null, null, null],
            [Date::stringToExcel('2026-09-04'), 'Saída', 'Internet / telefone', null, 'Vivo', 'Pix', 39.9, 'Pago / recebido'],
            [Date::stringToExcel('2026-09-30'), 'Saída', 'Categoria Nova', 'Teste', null, 'Boleto', 50, 'Pendente'],
            [null, 'Saída', 'Descartáveis', null, null, 'Pix', 10, 'Pago / recebido'],
        ]);

        $this->arquivo = tempnam(sys_get_temp_dir(), 'planilha').'.xlsx';
        (new Xlsx($planilha))->save($this->arquivo);
    }

    protected function tearDown(): void
    {
        @unlink($this->arquivo);
        parent::tearDown();
    }

    public function test_importa_lancamentos_com_as_regras_da_planilha(): void
    {
        $this->artisan('financeiro:importar-planilha', ['arquivo' => $this->arquivo])
            ->expectsOutputToContain('Linha 8 ignorada: data ausente ou inválida')
            ->assertSuccessful();

        $this->assertSame(5, Lancamento::count());

        $caixa = Lancamento::orderBy('id')->pluck('valor_caixa')->all();
        $this->assertSame(['736.00', '2848.34', '3308.21', '39.90', '50.00'], $caixa);

        $pendente = Lancamento::where('descricao', 'Teste')->firstOrFail();
        $this->assertSame('pendente', $pendente->status->value);
        $this->assertSame('Importadas', $pendente->categoria->grupo);

        // Reimportar não duplica.
        $this->artisan('financeiro:importar-planilha', ['arquivo' => $this->arquivo])->assertSuccessful();
        $this->assertSame(5, Lancamento::count());
    }

    public function test_simulacao_nao_grava_nada(): void
    {
        $this->artisan('financeiro:importar-planilha', ['arquivo' => $this->arquivo, '--simular' => true])->assertSuccessful();

        $this->assertSame(0, Lancamento::count());
    }

    public function test_recusa_arquivo_que_nao_e_xlsx(): void
    {
        $this->artisan('financeiro:importar-planilha', ['arquivo' => __FILE__])->assertFailed();
    }
}
