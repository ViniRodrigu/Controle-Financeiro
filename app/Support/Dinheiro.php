<?php

namespace App\Support;

final class Dinheiro
{
    /**
     * Converte valores digitados no padrão brasileiro ("R$ 1.234,56", "3,05%")
     * para o formato decimal com ponto ("1234.56"). Retorna null para vazio.
     * Se o texto não for reconhecido, é devolvido como está para a validação recusar.
     */
    public static function normalizar(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (is_int($valor) || is_float($valor)) {
            return (string) $valor;
        }

        $texto = str_replace(['R$', ' ', "\u{00A0}", '%'], '', trim((string) $valor));

        if ($texto === '') {
            return null;
        }

        if (str_contains($texto, ',')) {
            return str_replace(',', '.', str_replace('.', '', $texto));
        }

        // "1.234" ou "1.234.567" sem vírgula = separador de milhar.
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $texto)) {
            return str_replace('.', '', $texto);
        }

        return $texto;
    }

    public static function formatar(mixed $valor, bool $simbolo = true): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        $numero = (float) $valor;
        $texto = number_format(abs($numero), 2, ',', '.');

        return ($numero < 0 ? '-' : '').($simbolo ? 'R$ ' : '').$texto;
    }

    public static function percentual(mixed $valor, int $casas = 1): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        return number_format((float) $valor, $casas, ',', '.').'%';
    }
}
