<?php

namespace Tests\Feature;

use App\Enums\Papel;
use App\Models\AuditLog;
use App\Models\Categoria;
use App\Models\Lancamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissoesTest extends TestCase
{
    use RefreshDatabase;

    public function test_somente_leitura_consulta_mas_nao_altera(): void
    {
        $leitor = User::factory()->visualizador()->create();
        $lancamento = Lancamento::factory()->create();

        $this->actingAs($leitor)->get('/painel')->assertOk();
        $this->actingAs($leitor)->get('/lancamentos')->assertOk();
        $this->actingAs($leitor)->get("/lancamentos/{$lancamento->id}")->assertOk();
        $this->actingAs($leitor)->get('/lancamentos/exportar')->assertOk();

        $this->actingAs($leitor)->get('/lancamentos/novo')->assertForbidden();
        $this->actingAs($leitor)->post('/lancamentos', [])->assertForbidden();
        $this->actingAs($leitor)->get("/lancamentos/{$lancamento->id}/editar")->assertForbidden();
        $this->actingAs($leitor)->delete("/lancamentos/{$lancamento->id}")->assertForbidden();
        $this->actingAs($leitor)->get('/categorias')->assertForbidden();
        $this->actingAs($leitor)->get('/taxas')->assertForbidden();

        $this->assertNotSoftDeleted($lancamento);
    }

    public function test_financeiro_nao_acessa_usuarios_nem_auditoria(): void
    {
        $financeiro = User::factory()->create();

        $this->actingAs($financeiro)->get('/categorias')->assertOk();
        $this->actingAs($financeiro)->get('/usuarios')->assertForbidden();
        $this->actingAs($financeiro)->get('/auditoria')->assertForbidden();
        $this->actingAs($financeiro)->post('/usuarios', [
            'name' => 'Invasor', 'email' => 'x@x.test', 'papel' => 'admin', 'ativo' => 1,
            'password' => 'Senha-Forte-123!', 'password_confirmation' => 'Senha-Forte-123!',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'x@x.test']);
    }

    public function test_admin_cria_usuario_com_senha_forte(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/usuarios', [
            'name' => 'Recepção', 'email' => 'Recepcao@Clinica.test', 'papel' => 'visualizador', 'ativo' => 1,
            'password' => 'curta', 'password_confirmation' => 'curta',
        ])->assertSessionHasErrors('password');

        $this->actingAs($admin)->post('/usuarios', [
            'name' => 'Recepção', 'email' => 'Recepcao@Clinica.test', 'papel' => 'visualizador', 'ativo' => 1,
            'password' => 'Senha-Forte-123!', 'password_confirmation' => 'Senha-Forte-123!',
        ])->assertRedirect('/usuarios');

        $novo = User::where('email', 'recepcao@clinica.test')->firstOrFail();
        $this->assertSame(Papel::Visualizador, $novo->papel);
        $this->assertNotSame('Senha-Forte-123!', $novo->password);
    }

    public function test_admin_nao_pode_se_rebaixar_nem_se_desativar(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put("/usuarios/{$admin->id}", [
            'name' => $admin->name, 'email' => $admin->email, 'papel' => 'financeiro', 'ativo' => 1,
        ])->assertSessionHasErrors('papel');

        $this->assertTrue($admin->fresh()->ehAdmin());
    }

    public function test_auditoria_nao_expoe_senhas(): void
    {
        $admin = User::factory()->admin()->create();
        $alvo = User::factory()->create();

        $this->actingAs($admin)->put("/usuarios/{$alvo->id}", [
            'name' => $alvo->name, 'email' => $alvo->email, 'papel' => 'financeiro', 'ativo' => 1,
            'password' => 'Outra-Senha-456!', 'password_confirmation' => 'Outra-Senha-456!',
        ])->assertRedirect('/usuarios');

        $registro = AuditLog::where('auditable_type', 'usuario')->where('auditable_id', $alvo->id)->where('acao', 'atualizado')->firstOrFail();
        $this->assertSame('[protegido]', $registro->valores_novos['password']);
        $this->assertStringNotContainsString('$2y$', json_encode($registro->valores_antigos));

        $this->actingAs($admin)->get('/auditoria')->assertOk()->assertDontSee('$2y$', false);
    }

    public function test_categoria_com_lancamentos_nao_pode_ser_excluida(): void
    {
        $financeiro = User::factory()->create();
        $categoria = Categoria::factory()->create();
        Lancamento::factory()->create(['categoria_id' => $categoria->id]);

        $this->actingAs($financeiro)->delete("/categorias/{$categoria->id}")->assertSessionHas('erro');
        $this->assertModelExists($categoria);
    }
}
