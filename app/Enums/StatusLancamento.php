<?php

namespace App\Enums;

enum StatusLancamento: string
{
    case Pago = 'pago';
    case Pendente = 'pendente';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pago => 'Pago / recebido',
            self::Pendente => 'Pendente',
        };
    }

    /** Rótulo contextual: "Recebido" para entradas e "Pago" para saídas. */
    public function rotuloPara(TipoLancamento $tipo): string
    {
        if ($this === self::Pendente) {
            return 'Pendente';
        }

        return $tipo === TipoLancamento::Entrada ? 'Recebido' : 'Pago';
    }

    /** @return array<string, string> */
    public static function opcoes(): array
    {
        return array_column(array_map(fn (self $c) => [$c->value, $c->rotulo()], self::cases()), 1, 0);
    }
}
