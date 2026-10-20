<?php

namespace App\Services;

use App\Enums\FormaPagamento;
use App\Enums\StatusLancamento;
use App\Enums\TipoLancamento;
use App\Models\Contato;
use App\Models\Lancamento;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RegistroLancamentos
{
    public function __construct(private readonly CalculadoraLancamento $calculadora) {}

    /**
     * Cria um lançamento ou, se pedido, a série de parcelas/recorrências.
     *
     * @param  array<string, mixed>  $dados  Dados já validados.
     * @return Collection<int, Lancamento>
     */
    public function criar(array $dados, ?User $autor): Collection
    {
        return DB::transaction(function () use ($dados, $autor) {
            $base = $this->montar($dados) + ['criado_por' => $autor?->id];
            $quantidade = (int) ($dados['repetir_quantidade'] ?? 1);

            if (empty($dados['repetir']) || $quantidade < 2) {
                return collect([Lancamento::create($base)]);
            }

            return $this->criarSerie($base, $dados, $quantidade);
        });
    }

    /** @param  array<string, mixed>  $dados */
    public function atualizar(Lancamento $lancamento, array $dados, ?User $autor): Lancamento
    {
        DB::transaction(function () use ($lancamento, $dados, $autor) {
            $lancamento->fill($this->montar($dados) + ['atualizado_por' => $autor?->id])->save();
        });

        return $lancamento;
    }

    /** Marca como pago/recebido usando a data efetiva do pagamento. */
    public function baixar(Lancamento $lancamento, string $data, ?User $autor): void
    {
        $lancamento->fill([
            'status' => StatusLancamento::Pago,
            'data' => $data,
            'atualizado_por' => $autor?->id,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function montar(array $dados): array
    {
        $tipo = TipoLancamento::from($dados['tipo']);
        $forma = FormaPagamento::from($dados['forma_pagamento']);
        $contato = Contato::encontrarOuCriar($dados['contato_nome'] ?? null, $tipo);

        $calculo = $this->calculadora->calcular(
            $tipo,
            (string) $dados['valor'],
            $forma,
            isset($dados['parcelas_cartao']) ? (int) $dados['parcelas_cartao'] : null,
            $dados['taxa_percentual'] ?? null,
            $dados['liquido_informado'] ?? null,
        );

        return [
            'data' => Carbon::parse($dados['data'])->toDateString(),
            'tipo' => $tipo,
            'categoria_id' => (int) $dados['categoria_id'],
            'contato_id' => $contato?->id,
            'descricao' => $dados['descricao'] ?? null,
            'forma_pagamento' => $forma,
            'status' => StatusLancamento::from($dados['status']),
            'valor' => bcadd((string) $dados['valor'], '0', 2),
            'observacoes' => $dados['observacoes'] ?? null,
        ] + $calculo;
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $dados
     * @return Collection<int, Lancamento>
     */
    private function criarSerie(array $base, array $dados, int $quantidade): Collection
    {
        $grupo = (string) Str::uuid();
        $semanal = ($dados['repetir_frequencia'] ?? 'mensal') === 'semanal';
        $valores = ($dados['repetir_modo'] ?? 'dividir') === 'dividir'
            ? $this->dividir($base['valor'], $quantidade)
            : array_fill(0, $quantidade, $base['valor']);
        $dataInicial = Carbon::parse($base['data']);
        $descricao = $base['descricao'] ? mb_substr($base['descricao'], 0, 240).' ' : '';
        $criados = collect();

        for ($i = 0; $i < $quantidade; $i++) {
            $data = $semanal ? $dataInicial->copy()->addWeeks($i) : $dataInicial->copy()->addMonthsNoOverflow($i);

            // O líquido informado só faz sentido para um recebimento único, por isso é descartado na série.
            $calculo = $this->calculadora->calcular(
                $base['tipo'], $valores[$i], $base['forma_pagamento'], $base['parcelas_cartao'], $base['taxa_percentual'], null,
            );

            $criados->push(Lancamento::create(array_merge($base, $calculo, [
                'data' => $data->toDateString(),
                'valor' => $valores[$i],
                'status' => $i === 0 ? $base['status'] : StatusLancamento::Pendente,
                'descricao' => $descricao.'('.($i + 1).'/'.$quantidade.')',
                'grupo_parcelamento' => $grupo,
                'parcela_numero' => $i + 1,
                'parcela_total' => $quantidade,
            ])));
        }

        return $criados;
    }

    /**
     * Divide em centavos; a sobra do arredondamento fica na primeira parcela.
     *
     * @return list<string>
     */
    private function dividir(string $valor, int $quantidade): array
    {
        $centavos = (int) bcmul($valor, '100', 0);
        $parcela = intdiv($centavos, $quantidade);
        $sobra = $centavos - ($parcela * $quantidade);

        return array_map(
            fn (int $i) => bcdiv((string) ($parcela + ($i === 0 ? $sobra : 0)), '100', 2),
            range(0, $quantidade - 1),
        );
    }
}
