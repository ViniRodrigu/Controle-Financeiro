<?php

namespace App\Enums;

enum TipoLancamento: string
{
    case Entrada = 'entrada';
    case Saida = 'saida';

    public function rotulo(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Saida => 'Saída',
        };
    }

    public function cor(): string
    {
        return match ($this) {
            self::Entrada => 'success',
            self::Saida => 'danger',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::Entrada => 'bi-arrow-down-left',
            self::Saida => 'bi-arrow-up-right',
        };
    }

    /** @return array<string, string> */
    public static function opcoes(): array
    {
        return array_column(array_map(fn (self $c) => [$c->value, $c->rotulo()], self::cases()), 1, 0);
    }
}
