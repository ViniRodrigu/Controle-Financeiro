<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'acao', 'auditable_type', 'auditable_id', 'valores_antigos', 'valores_novos', 'ip', 'user_agent'])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const ACOES = [
        'criado' => 'Criação',
        'atualizado' => 'Alteração',
        'excluido' => 'Exclusão',
        'restaurado' => 'Restauração',
        'login' => 'Login',
        'logout' => 'Logout',
        'login_falhou' => 'Falha de login',
        'bloqueio' => 'Bloqueio por tentativas',
        'senha_alterada' => 'Senha alterada',
        '2fa_ativado' => '2FA ativado',
        '2fa_desativado' => '2FA desativado',
    ];

    /** Nomes legíveis dos campos exibidos no histórico. */
    public const CAMPOS = [
        'id' => 'ID', 'data' => 'Data', 'tipo' => 'Tipo', 'categoria_id' => 'Categoria (ID)', 'contato_id' => 'Contato (ID)',
        'descricao' => 'Descrição', 'forma_pagamento' => 'Forma de pagamento', 'status' => 'Status', 'valor' => 'Valor',
        'parcelas_cartao' => 'Parcelas', 'taxa_percentual' => 'Taxa (%)', 'taxa_valor' => 'Taxa (R$)',
        'valor_liquido' => 'Valor líquido', 'liquido_informado' => 'Líquido informado', 'valor_caixa' => 'Valor no caixa',
        'grupo_parcelamento' => 'Grupo de parcelas', 'parcela_numero' => 'Parcela nº', 'parcela_total' => 'Total de parcelas',
        'observacoes' => 'Observações', 'observacao' => 'Observação', 'criado_por' => 'Criado por (ID)', 'nome' => 'Nome',
        'name' => 'Nome', 'email' => 'E-mail', 'password' => 'Senha', 'papel' => 'Perfil', 'ativo' => 'Ativo', 'grupo' => 'Grupo',
        'cor' => 'Cor', 'ordem' => 'Ordem', 'telefone' => 'Telefone', 'parcelas' => 'Parcelas',
        'two_factor_secret' => 'Segredo 2FA', 'two_factor_recovery_codes' => 'Códigos de recuperação',
        'two_factor_confirmed_at' => '2FA confirmado em',
    ];

    public static function rotuloCampo(string $campo): string
    {
        return self::CAMPOS[$campo] ?? $campo;
    }

    public static function formatarValor(mixed $valor): string
    {
        return match (true) {
            $valor === null, $valor === '' => '—',
            is_bool($valor) => $valor ? 'Sim' : 'Não',
            is_array($valor) => json_encode($valor, JSON_UNESCAPED_UNICODE),
            default => (string) $valor,
        };
    }

    protected function casts(): array
    {
        return [
            'valores_antigos' => 'array',
            'valores_novos' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public static function registrar(
        string $acao,
        ?Model $alvo = null,
        ?array $antes = null,
        ?array $depois = null,
        ?int $userId = null,
    ): self {
        // Em comandos de terminal (ex.: importação) não há requisição HTTP real.
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'acao' => $acao,
            'auditable_type' => $alvo?->getMorphClass(),
            'auditable_id' => $alvo?->getKey(),
            'valores_antigos' => $antes ?: null,
            'valores_novos' => $depois ?: null,
            'ip' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : 'console',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }

    public function rotuloAcao(): string
    {
        return self::ACOES[$this->acao] ?? $this->acao;
    }

    public function corAcao(): string
    {
        return match ($this->acao) {
            'criado', 'restaurado', 'login', '2fa_ativado' => 'success',
            'atualizado', 'senha_alterada' => 'primary',
            'excluido', 'login_falhou', 'bloqueio', '2fa_desativado' => 'danger',
            default => 'secondary',
        };
    }
}
