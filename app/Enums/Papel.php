<?php

namespace App\Enums;

enum Papel: string
{
    case Admin = 'admin';
    case Financeiro = 'financeiro';
    case Visualizador = 'visualizador';

    public function rotulo(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Financeiro => 'Financeiro',
            self::Visualizador => 'Somente leitura',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::Admin => 'Acesso total, incluindo usuários e auditoria.',
            self::Financeiro => 'Registra e edita lançamentos, contatos, categorias e taxas.',
            self::Visualizador => 'Consulta painel, relatórios e lançamentos, sem alterar nada.',
        };
    }

    public function podeEditar(): bool
    {
        return $this === self::Admin || $this === self::Financeiro;
    }

    /** @return array<string, string> */
    public static function opcoes(): array
    {
        return array_column(array_map(fn (self $c) => [$c->value, $c->rotulo()], self::cases()), 1, 0);
    }
}
