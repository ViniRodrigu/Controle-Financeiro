<?php

namespace App\Enums;

enum FormaPagamento: string
{
    case Pix = 'pix';
    case Dinheiro = 'dinheiro';
    case Debito = 'debito';
    case Credito = 'credito';
    case Transferencia = 'transferencia';
    case Boleto = 'boleto';
    case Outro = 'outro';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pix => 'Pix',
            self::Dinheiro => 'Dinheiro',
            self::Debito => 'Débito',
            self::Credito => 'Crédito',
            self::Transferencia => 'Transferência',
            self::Boleto => 'Boleto',
            self::Outro => 'Outro',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::Pix => 'bi-qr-code',
            self::Dinheiro => 'bi-cash-stack',
            self::Debito, self::Credito => 'bi-credit-card',
            self::Transferencia => 'bi-bank',
            self::Boleto => 'bi-upc',
            self::Outro => 'bi-three-dots',
        };
    }

    /** Somente o crédito pode ser parcelado na maquininha. */
    public function permiteParcelas(): bool
    {
        return $this === self::Credito;
    }

    /** @return array<string, string> */
    public static function opcoes(): array
    {
        return array_column(array_map(fn (self $c) => [$c->value, $c->rotulo()], self::cases()), 1, 0);
    }
}
