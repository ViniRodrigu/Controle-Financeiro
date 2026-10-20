<?php

namespace App\Http\Requests;

use App\Enums\FormaPagamento;
use App\Enums\StatusLancamento;
use App\Enums\TipoLancamento;
use App\Support\Dinheiro;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LancamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valor' => Dinheiro::normalizar($this->input('valor')),
            'taxa_percentual' => Dinheiro::normalizar($this->input('taxa_percentual')),
            'liquido_informado' => Dinheiro::normalizar($this->input('liquido_informado')),
            'contato_nome' => Str::squish((string) $this->input('contato_nome')) ?: null,
            'descricao' => Str::squish((string) $this->input('descricao')) ?: null,
            'parcelas_cartao' => $this->filled('parcelas_cartao') ? $this->input('parcelas_cartao') : null,
            'repetir' => $this->boolean('repetir'),
        ]);
    }

    public function rules(): array
    {
        $categoriaAtual = $this->route('lancamento')?->categoria_id;

        $regras = [
            'data' => ['required', 'date', 'after_or_equal:2000-01-01', 'before:2100-01-01'],
            'tipo' => ['required', Rule::enum(TipoLancamento::class)],
            'categoria_id' => [
                'required',
                'integer',
                // A categoria precisa ser do mesmo tipo e estar ativa (ou ser a categoria atual na edição).
                Rule::exists('categorias', 'id')->where(function (Builder $query) use ($categoriaAtual) {
                    $query->where('tipo', (string) $this->input('tipo'))
                        ->where(fn (Builder $q) => $q->where('ativo', true)->when($categoriaAtual, fn (Builder $q) => $q->orWhere('id', $categoriaAtual)));
                }),
            ],
            'forma_pagamento' => ['required', Rule::enum(FormaPagamento::class)],
            'status' => ['required', Rule::enum(StatusLancamento::class)],
            'valor' => ['required', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'contato_nome' => ['nullable', 'string', 'max:120'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'parcelas_cartao' => ['nullable', 'integer', 'between:1,12'],
            'taxa_percentual' => ['nullable', 'decimal:0,3', 'min:0', 'max:100'],
            'liquido_informado' => ['nullable', 'decimal:0,2', 'min:0', 'lte:valor'],
        ];

        // Parcelamento/recorrência só existe na criação.
        if (! $this->route('lancamento')) {
            $regras += [
                'repetir' => ['boolean'],
                'repetir_quantidade' => ['exclude_unless:repetir,true', 'required', 'integer', 'between:2,60'],
                'repetir_modo' => ['exclude_unless:repetir,true', 'required', Rule::in(['dividir', 'repetir'])],
                'repetir_frequencia' => ['exclude_unless:repetir,true', 'required', Rule::in(['mensal', 'semanal'])],
            ];
        }

        return $regras;
    }

    public function attributes(): array
    {
        return [
            'data' => 'data',
            'tipo' => 'tipo',
            'categoria_id' => 'categoria',
            'forma_pagamento' => 'forma de pagamento',
            'valor' => 'valor',
            'contato_nome' => 'paciente / fornecedor',
            'descricao' => 'descrição',
            'observacoes' => 'observações',
            'parcelas_cartao' => 'parcelas',
            'taxa_percentual' => 'taxa (%)',
            'liquido_informado' => 'líquido informado',
            'repetir_quantidade' => 'quantidade',
            'repetir_modo' => 'forma de repetição',
            'repetir_frequencia' => 'frequência',
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.exists' => 'Selecione uma categoria ativa compatível com o tipo do lançamento.',
            'liquido_informado.lte' => 'O líquido informado não pode ser maior que o valor bruto.',
            'valor.decimal' => 'Informe um valor válido, por exemplo 1.250,00.',
        ];
    }
}
