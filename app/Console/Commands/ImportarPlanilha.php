<?php

namespace App\Console\Commands;

use App\Enums\FormaPagamento;
use App\Enums\StatusLancamento;
use App\Enums\TipoLancamento;
use App\Models\Categoria;
use App\Models\Contato;
use App\Models\Lancamento;
use App\Services\CalculadoraLancamento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as DataExcel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * Importa a aba "Lançamentos" da planilha original. Pode ser executado mais de uma vez:
 * linhas idênticas já importadas são ignoradas.
 */
class ImportarPlanilha extends Command
{
    protected $signature = 'financeiro:importar-planilha
        {arquivo : Caminho do arquivo .xlsx}
        {--simular : Mostra o que seria importado, sem gravar nada}';

    protected $description = 'Importa os lançamentos da planilha Excel (aba "Lançamentos")';

    private const ABA = 'Lançamentos';

    /** @var array<string, Categoria> */
    private array $categorias = [];

    public function handle(CalculadoraLancamento $calculadora): int
    {
        $arquivo = (string) $this->argument('arquivo');

        if (! is_file($arquivo) || ! is_readable($arquivo) || Str::lower(pathinfo($arquivo, PATHINFO_EXTENSION)) !== 'xlsx') {
            $this->components->error('Informe um arquivo .xlsx existente.');

            return self::FAILURE;
        }

        try {
            $leitor = IOFactory::createReader('Xlsx');
            $leitor->setReadDataOnly(true);
            $leitor->setLoadSheetsOnly([self::ABA]);
            $aba = $leitor->load($arquivo)->getSheetByName(self::ABA);
        } catch (Throwable $e) {
            $this->components->error('Não foi possível ler a planilha: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $aba) {
            $this->components->error('A aba "'.self::ABA.'" não foi encontrada.');

            return self::FAILURE;
        }

        $this->categorias = Categoria::all()->keyBy(fn (Categoria $c) => $c->tipo->value.'|'.$this->chave($c->nome))->all();

        $importados = [];
        $ignorados = [];
        $duplicados = 0;

        DB::beginTransaction();

        try {
            for ($linha = 2; $linha <= $aba->getHighestDataRow(); $linha++) {
                $dados = $this->lerLinha($aba, $linha);

                if ($dados === null) {
                    continue;
                }

                if (is_string($dados)) {
                    $ignorados[] = [$linha, $dados];

                    continue;
                }

                if ($this->jaExiste($dados)) {
                    $duplicados++;

                    continue;
                }

                $calculo = $calculadora->calcular(
                    $dados['tipo'], $dados['valor'], $dados['forma_pagamento'], $dados['parcelas'], $dados['taxa_percentual'], $dados['liquido_informado'],
                );

                $lancamento = Lancamento::create([
                    'data' => $dados['data'],
                    'tipo' => $dados['tipo'],
                    'categoria_id' => $dados['categoria']->id,
                    'contato_id' => Contato::encontrarOuCriar($dados['contato'], $dados['tipo'])?->id,
                    'descricao' => $dados['descricao'],
                    'forma_pagamento' => $dados['forma_pagamento'],
                    'status' => $dados['status'],
                    'valor' => $dados['valor'],
                    'observacoes' => 'Importado da planilha (linha '.$linha.')',
                ] + $calculo);

                $importados[] = [
                    $linha,
                    $lancamento->data->format('d/m/Y'),
                    $lancamento->tipo->rotulo(),
                    $dados['categoria']->nome,
                    moeda($lancamento->valor),
                    moeda($lancamento->valor_caixa),
                ];
            }

            $this->option('simular') ? DB::rollBack() : DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $this->components->error("Importação cancelada, nada foi gravado: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($importados) {
            $this->table(['Linha', 'Data', 'Tipo', 'Categoria', 'Valor', 'No caixa'], $importados);
        }

        foreach ($ignorados as [$linha, $motivo]) {
            $this->components->warn("Linha {$linha} ignorada: {$motivo}");
        }

        $acao = $this->option('simular') ? 'seriam importados (simulação, nada gravado)' : 'importados';
        $this->components->info(count($importados)." lançamento(s) {$acao}. {$duplicados} já existiam e foram ignorados.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>|string|null Dados da linha, motivo para ignorar, ou null se vazia.
     */
    private function lerLinha(Worksheet $aba, int $linha): array|string|null
    {
        $valorCelula = function (string $coluna) use ($aba, $linha) {
            $celula = $aba->getCell($coluna.$linha);
            $valor = $celula->isFormula() ? $celula->getOldCalculatedValue() : $celula->getValue();

            return is_string($valor) ? trim($valor) : $valor;
        };

        $data = $valorCelula('A');
        $tipoTexto = $valorCelula('B');
        $valor = $valorCelula('G');

        if (($data === null || $data === '') && ($tipoTexto === null || $tipoTexto === '') && ($valor === null || $valor === '')) {
            return null;
        }

        $tipo = match ($this->chave((string) $tipoTexto)) {
            'entrada' => TipoLancamento::Entrada,
            'saida' => TipoLancamento::Saida,
            default => null,
        };

        if (! $tipo) {
            return 'tipo inválido ("'.$tipoTexto.'")';
        }

        if (! is_numeric($data)) {
            return 'data ausente ou inválida';
        }

        if (! is_numeric($valor) || (float) $valor <= 0) {
            return 'valor ausente ou inválido';
        }

        $valor = number_format((float) $valor, 2, '.', '');
        $taxa = $valorCelula('L');
        $taxaPercentual = is_numeric($taxa) ? number_format((float) $taxa <= 1 ? (float) $taxa * 100 : (float) $taxa, 3, '.', '') : null;
        $taxaValor = $valorCelula('M');
        $liquidoInformado = is_numeric($valorCelula('O')) ? number_format((float) $valorCelula('O'), 2, '.', '') : null;

        // Na planilha, quando a "Taxa (R$)" foi digitada à mão e não bate com o percentual,
        // ela representa o desconto real: guardamos o líquido efetivamente recebido.
        if ($tipo === TipoLancamento::Entrada && $liquidoInformado === null && is_numeric($taxaValor)) {
            $previsto = $taxaPercentual !== null ? round((float) $valor * (float) $taxaPercentual / 100, 2) : null;

            if ($previsto === null || abs($previsto - (float) $taxaValor) > 0.009) {
                $liquidoInformado = number_format((float) $valor - (float) $taxaValor, 2, '.', '');
            }
        }

        $parcelas = $valorCelula('K');

        return [
            'data' => DataExcel::excelToDateTimeObject((float) $data)->format('Y-m-d'),
            'tipo' => $tipo,
            'categoria' => $this->categoria($tipo, (string) $valorCelula('C')),
            'descricao' => Str::squish((string) $valorCelula('D')) ?: null,
            'contato' => Str::squish((string) $valorCelula('E')) ?: null,
            'forma_pagamento' => $this->formaPagamento((string) $valorCelula('F')),
            'status' => str_starts_with($this->chave((string) $valorCelula('H')), 'pendente') ? StatusLancamento::Pendente : StatusLancamento::Pago,
            'valor' => $valor,
            'parcelas' => is_numeric($parcelas) ? (int) $parcelas : null,
            'taxa_percentual' => $taxaPercentual,
            'liquido_informado' => $liquidoInformado,
        ];
    }

    private function categoria(TipoLancamento $tipo, string $nome): Categoria
    {
        $nome = Str::squish($nome) ?: ($tipo === TipoLancamento::Entrada ? 'Outras receitas' : 'Outras despesas');
        $chave = $tipo->value.'|'.$this->chave($nome);

        return $this->categorias[$chave] ??= Categoria::create([
            'nome' => $nome,
            'tipo' => $tipo,
            'grupo' => 'Importadas',
            'cor' => '#8a94a6',
        ]);
    }

    private function formaPagamento(string $texto): FormaPagamento
    {
        return FormaPagamento::tryFrom($this->chave($texto)) ?? FormaPagamento::Outro;
    }

    /** @param  array<string, mixed>  $dados */
    private function jaExiste(array $dados): bool
    {
        return Lancamento::withTrashed()
            ->where('data', '>=', $dados['data'])
            ->where('data', '<', date('Y-m-d', strtotime($dados['data'].' +1 day')))
            ->where('tipo', $dados['tipo'])
            ->where('categoria_id', $dados['categoria']->id)
            ->where('valor', $dados['valor'])
            ->where(fn ($q) => $dados['descricao'] === null ? $q->whereNull('descricao') : $q->where('descricao', $dados['descricao']))
            ->exists();
    }

    /** Texto em minúsculas, sem acentos e espaços extras, para comparação. */
    private function chave(string $texto): string
    {
        return Str::of($texto)->squish()->lower()->ascii()->toString();
    }
}
