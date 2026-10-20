<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_e_redirecionado_para_o_login(): void
    {
        $this->get('/painel')->assertRedirect('/login');
        $this->get('/lancamentos')->assertRedirect('/login');
        $this->get('/usuarios')->assertRedirect('/login');
    }

    public function test_nao_existe_cadastro_publico_nem_recuperacao_por_email(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();
    }

    public function test_login_com_credenciais_validas(): void
    {
        $usuario = User::factory()->create(['email' => 'ana@clinica.test']);

        $this->post('/login', ['email' => 'ANA@clinica.test', 'password' => 'Senha-Teste-123'])
            ->assertRedirect('/painel');

        $this->assertAuthenticatedAs($usuario);
        $this->assertNotNull($usuario->fresh()->ultimo_acesso_em);
        $this->assertDatabaseHas('audit_logs', ['acao' => 'login', 'user_id' => $usuario->id]);
    }

    public function test_senha_errada_e_usuario_inexistente_tem_a_mesma_mensagem(): void
    {
        User::factory()->create(['email' => 'ana@clinica.test']);

        $erroSenha = $this->post('/login', ['email' => 'ana@clinica.test', 'password' => 'errada'])
            ->assertSessionHasErrors('email')->getSession()->get('errors')->first('email');

        $erroEmail = $this->post('/login', ['email' => 'ninguem@clinica.test', 'password' => 'errada'])
            ->assertSessionHasErrors('email')->getSession()->get('errors')->first('email');

        $this->assertSame($erroSenha, $erroEmail);
        $this->assertGuest();
        $this->assertSame(2, AuditLog::where('acao', 'login_falhou')->count());
    }

    public function test_usuario_inativo_nao_consegue_entrar(): void
    {
        User::factory()->inativo()->create(['email' => 'ex@clinica.test']);

        $this->post('/login', ['email' => 'ex@clinica.test', 'password' => 'Senha-Teste-123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_desativado_durante_a_sessao_e_desconectado(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario)->get('/painel')->assertOk();

        $usuario->update(['ativo' => false]);

        $this->actingAs($usuario->fresh())->get('/painel')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_bloqueia_forca_bruta_no_login(): void
    {
        User::factory()->create(['email' => 'ana@clinica.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'ana@clinica.test', 'password' => 'tentativa'.$i]);
        }

        $this->post('/login', ['email' => 'ana@clinica.test', 'password' => 'Senha-Teste-123'])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_cabecalhos_de_seguranca(): void
    {
        $resposta = $this->get('/login')->assertOk();

        $resposta->assertHeader('X-Frame-Options', 'DENY');
        $resposta->assertHeader('X-Content-Type-Options', 'nosniff');
        $resposta->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = $resposta->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
    }

    public function test_paginas_autenticadas_nao_ficam_em_cache(): void
    {
        $this->actingAs(User::factory()->create())->get('/painel')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_troca_de_senha_exige_a_senha_atual_e_senha_forte(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->put('/user/password', [
            'current_password' => 'errada',
            'password' => 'Nova-Senha-Forte-1',
            'password_confirmation' => 'Nova-Senha-Forte-1',
        ])->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->actingAs($usuario)->put('/user/password', [
            'current_password' => 'Senha-Teste-123',
            'password' => 'fraca',
            'password_confirmation' => 'fraca',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->actingAs($usuario)->put('/user/password', [
            'current_password' => 'Senha-Teste-123',
            'password' => 'Nova-Senha-Forte-1',
            'password_confirmation' => 'Nova-Senha-Forte-1',
        ])->assertSessionHasNoErrors();
    }

    public function test_gestao_do_2fa_exige_confirmar_a_senha(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/perfil/seguranca')
            ->assertRedirect('/user/confirm-password');
    }
}
