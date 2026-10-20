<?php

use App\Support\Dinheiro;

if (! function_exists('moeda')) {
    function moeda(mixed $valor, bool $simbolo = true): string
    {
        return Dinheiro::formatar($valor, $simbolo);
    }
}

if (! function_exists('percentual')) {
    function percentual(mixed $valor, int $casas = 1): string
    {
        return Dinheiro::percentual($valor, $casas);
    }
}
