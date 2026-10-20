<?php

namespace App\Models;

use App\Enums\Papel;
use App\Models\Concerns\Auditavel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'papel', 'ativo'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditavel, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'papel' => Papel::class,
            'ativo' => 'boolean',
            'ultimo_acesso_em' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function ehAdmin(): bool
    {
        return $this->papel === Papel::Admin;
    }

    public function podeEditar(): bool
    {
        return $this->papel?->podeEditar() ?? false;
    }

    public function temDoisFatoresAtivo(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    public function iniciais(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
            ->implode('');
    }
}
