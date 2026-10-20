<?php

namespace App\Listeners;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;

/** Trilha de auditoria dos eventos de autenticação. */
class RegistrarAcessos
{
    public function handleLogin(Login $evento): void
    {
        if (! $evento->user instanceof User) {
            return;
        }

        // Atualização direta (sem eventos de model) para não gerar um registro de "alteração" a cada login.
        User::query()->whereKey($evento->user->getKey())->update([
            'ultimo_acesso_em' => now(),
            'ultimo_acesso_ip' => request()->ip(),
        ]);

        AuditLog::registrar('login', $evento->user, userId: $evento->user->getKey());
    }

    public function handleLogout(Logout $evento): void
    {
        if ($evento->user instanceof User) {
            AuditLog::registrar('logout', $evento->user, userId: $evento->user->getKey());
        }
    }

    public function handleFailed(Failed $evento): void
    {
        AuditLog::registrar(
            'login_falhou',
            $evento->user instanceof User ? $evento->user : null,
            depois: ['email' => mb_substr((string) ($evento->credentials['email'] ?? ''), 0, 150)],
        );
    }

    public function handleLockout(Lockout $evento): void
    {
        AuditLog::registrar('bloqueio', depois: ['email' => mb_substr((string) $evento->request->input('email'), 0, 150)]);
    }

    public function handlePasswordUpdated(PasswordUpdatedViaController $evento): void
    {
        AuditLog::registrar('senha_alterada', $evento->user, userId: $evento->user->getKey());
    }

    public function handleTwoFactorConfirmed(TwoFactorAuthenticationConfirmed $evento): void
    {
        AuditLog::registrar('2fa_ativado', $evento->user, userId: $evento->user->getKey());
    }

    public function handleTwoFactorDisabled(TwoFactorAuthenticationDisabled $evento): void
    {
        AuditLog::registrar('2fa_desativado', $evento->user, userId: $evento->user->getKey());
    }
}
