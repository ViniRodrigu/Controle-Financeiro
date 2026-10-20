<?php

namespace App\Http\Requests;

use App\Enums\FormaPagamento;
use App\Enums\TipoLancamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FiltroLancamentosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'busca' => Str::squish((string) $this->query('busca')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date', Rule::when($this->filled('de'), 'after_or_equal:de')],
            'tipo' => ['nullable', Rule::enum(TipoLancamento::class)],
            'status' => ['nullable', Rule::in(['pago', 'pendente', 'vencido'])],
            'categoria_id' => ['nullable', 'integer'],
            'contato_id' => ['nullable', 'integer'],
            'forma_pagamento' => ['nullable', Rule::enum(FormaPagamento::class)],
            'busca' => ['nullable', 'string', 'max:100'],
            'lixeira' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['de' => 'data inicial', 'ate' => 'data final', 'busca' => 'busca'];
    }

    /** Filtro inválido volta para a listagem limpa (evita laço de redirecionamento). */
    protected function getRedirectUrl(): string
    {
        return route('lancamentos.index');
    }

    /** @return array<string, mixed> */
    public function filtros(): array
    {
        return collect($this->validated())->except('lixeira')->filter(fn ($v) => $v !== null && $v !== '')->all();
    }
}
