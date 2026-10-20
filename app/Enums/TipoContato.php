<?php

namespace App\Enums;

enum TipoContato: string
{
    case Paciente = 'paciente';
    case Fornecedor = 'fornecedor';
    case Outro = 'outro';

    public function rotulo(): string
    {
        return match ($this) {
            self::Paciente => 'Paciente',
            self::Fornecedor => 'Fornecedor',
            self::Outro => 'Outro',
        };
    }

    public static function paraTipoLancamento(TipoLancamento $tipo): self
    {
        return $tipo === TipoLancamento::Entrada ? self::Paciente : self::Fornecedor;
    }

    /** @return array<string, string> */
    public static function opcoes(): array
    {
        return array_column(array_map(fn (self $c) => [$c->value, $c->rotulo()], self::cases()), 1, 0);
    }
}
