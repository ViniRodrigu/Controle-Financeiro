<?php

namespace Tests\Unit;

use App\Support\Csv;
use App\Support\Dinheiro;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DinheiroTest extends TestCase
{
    public static function valoresDigitados(): array
    {
        return [
            'vírgula decimal' => ['1234,56', '1234.56'],
            'milhar e vírgula' => ['1.234,56', '1234.56'],
            'com símbolo' => ['R$ 2.980,00', '2980.00'],
            'milhar sem decimais' => ['1.234', '1234'],
            'ponto decimal' => ['12.5', '12.5'],
            'percentual' => ['3,05%', '3.05'],
            'inteiro' => ['800', '800'],
            'vazio' => ['   ', null],
            'nulo' => [null, null],
        ];
    }

    #[DataProvider('valoresDigitados')]
    public function test_normaliza_valores_no_padrao_brasileiro(?string $entrada, ?string $esperado): void
    {
        $this->assertSame($esperado, Dinheiro::normalizar($entrada));
    }

    public function test_formata_moeda_e_percentual(): void
    {
        $this->assertSame('R$ 1.234,56', Dinheiro::formatar('1234.56'));
        $this->assertSame('-R$ 10,00', Dinheiro::formatar(-10));
        $this->assertSame('—', Dinheiro::formatar(null));
        $this->assertSame('9,3%', Dinheiro::percentual(9.27));
    }

    public function test_csv_neutraliza_formulas(): void
    {
        $this->assertSame("'=HYPERLINK(\"http://x\")", Csv::texto('=HYPERLINK("http://x")'));
        $this->assertSame("'+5", Csv::texto('+5'));
        $this->assertSame("'@SOMA(A1)", Csv::texto('@SOMA(A1)'));
        $this->assertSame('Botox', Csv::texto('Botox'));
        $this->assertSame('1234,50', Csv::numero('1234.5'));
    }
}
