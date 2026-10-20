<?php

namespace Tests\Feature;

use App\Models\Lancamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExportacaoSqlTest extends TestCase
{
    use RefreshDatabase;

    private string $arquivo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->arquivo = tempnam(sys_get_temp_dir(), 'exportacao').'.sql';
    }

    protected function tearDown(): void
    {
        @unlink($this->arquivo);
        parent::tearDown();
    }

    public function test_recusa_bancos_que_nao_sao_mysql_ou_mariadb(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Cenário só se aplica fora do MySQL/MariaDB.');
        }

        $this->artisan('financeiro:exportar-sql', ['arquivo' => $this->arquivo])->assertFailed();
    }

    public function test_gera_estrutura_e_dados_importaveis_mais_de_uma_vez(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('A exportação SQL exige MySQL/MariaDB (rode a suíte apontando para um banco de testes MariaDB).');
        }

        User::factory()->create(['remember_token' => 'token-que-nao-pode-sair']);
        Lancamento::factory()->create(['descricao' => "Material d'Água"]);
        DB::table('sessions')->insert(['id' => 'sessao-teste', 'payload' => 'segredo', 'last_activity' => time()]);

        $this->artisan('financeiro:exportar-sql', ['arquivo' => $this->arquivo])->assertSuccessful();
        $sql = file_get_contents($this->arquivo);

        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `lancamentos`', $sql);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `sessions`', $sql);
        $this->assertStringContainsString('ON DUPLICATE KEY UPDATE `id` = `id`;', $sql);
        $this->assertStringContainsString("SET time_zone = '+00:00';", $sql);
        $this->assertStringContainsString("Material d\\'Água", $sql);
        $this->assertStringNotContainsString('AUTO_INCREMENT=', $sql);
        $this->assertStringNotContainsString('json_valid', $sql);
        $this->assertStringNotContainsString('INSERT INTO `sessions`', $sql);
        $this->assertStringNotContainsString('token-que-nao-pode-sair', $sql);

        // Tabelas referenciadas vêm antes das dependentes (estrutura e dados), para funcionar
        // mesmo com a verificação de chaves estrangeiras ligada no phpMyAdmin.
        $posicao = fn (string $trecho) => strpos($sql, $trecho);
        $this->assertLessThan($posicao('CREATE TABLE IF NOT EXISTS `audit_logs`'), $posicao('CREATE TABLE IF NOT EXISTS `users`'));
        $this->assertLessThan($posicao('CREATE TABLE IF NOT EXISTS `lancamentos`'), $posicao('CREATE TABLE IF NOT EXISTS `categorias`'));
        $this->assertLessThan($posicao('CREATE TABLE IF NOT EXISTS `lancamentos`'), $posicao('CREATE TABLE IF NOT EXISTS `contatos`'));
        $this->assertLessThan($posicao('INSERT INTO `lancamentos`'), $posicao('INSERT INTO `categorias`'));
        $this->assertLessThan($posicao('INSERT INTO `audit_logs`'), $posicao('INSERT INTO `users`'));
    }
}
