<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Registra automaticamente criação, alteração, exclusão e restauração do model
 * na tabela audit_logs, guardando o usuário, IP e os valores antes/depois.
 */
trait Auditavel
{
    public static function bootAuditavel(): void
    {
        static::created(function (Model $model) {
            AuditLog::registrar('criado', $model, null, $model->valoresAuditaveis($model->getAttributes()));
        });

        static::updated(function (Model $model) {
            $depois = $model->valoresAuditaveis($model->getChanges());

            if ($depois === []) {
                return;
            }

            $antes = array_intersect_key($model->valoresAuditaveis($model->getRawOriginal()), $depois);

            AuditLog::registrar('atualizado', $model, $antes, $depois);
        });

        static::deleted(function (Model $model) {
            AuditLog::registrar('excluido', $model, $model->valoresAuditaveis($model->getRawOriginal()));
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $model) => AuditLog::registrar('restaurado', $model));
        }
    }

    public function historico(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest('id');
    }

    /**
     * Remove campos de controle e mascara campos sensíveis (senhas, segredos de 2FA).
     *
     * @param  array<string, mixed>  $valores
     * @return array<string, mixed>
     */
    protected function valoresAuditaveis(array $valores): array
    {
        $ignorar = ['created_at', 'updated_at', 'deleted_at', 'remember_token', 'atualizado_por', 'ultimo_acesso_em', 'ultimo_acesso_ip'];
        $mascarar = ['password', 'two_factor_secret', 'two_factor_recovery_codes'];
        $resultado = [];

        foreach ($valores as $campo => $valor) {
            if (in_array($campo, $ignorar, true)) {
                continue;
            }

            $resultado[$campo] = in_array($campo, $mascarar, true) && $valor !== null ? '[protegido]' : $valor;
        }

        return $resultado;
    }
}
