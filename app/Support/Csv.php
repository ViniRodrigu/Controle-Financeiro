<?php

namespace App\Support;

final class Csv
{
    /**
     * Neutraliza "CSV/Formula injection": textos iniciados por = + - @ tab ou CR
     * seriam interpretados como fórmula pelo Excel ao abrir o arquivo.
     */
    public static function texto(?string $valor): string
    {
        $valor = (string) $valor;

        if ($valor !== '' && in_array($valor[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$valor;
        }

        return $valor;
    }

    /** Número no padrão do Excel em português (vírgula decimal, sem milhar). */
    public static function numero(mixed $valor, int $casas = 2): string
    {
        return $valor === null || $valor === '' ? '' : number_format((float) $valor, $casas, ',', '');
    }
}
