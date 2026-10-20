<?php

namespace App\Http\Requests;

use App\Enums\FormaPagamento;
use App\Support\Dinheiro;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxaCartaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $forma = FormaPagamento::tryFrom((string) $this->input('forma_pagamento'));

        $this->merge([
            'taxa_percentual' => Dinheiro::normalizar($this->input('taxa_percentual')),
            // Só o crédito tem parcelas; as demais modalidades ficam sempre em 1x.
            'parcelas' => $forma?->permiteParcelas() ? $this->input('parcelas') : 1,
        ]);
    }

    public function rules(): array
    {
        return [
            'forma_pagamento' => ['required', Rule::enum(FormaPagamento::class)],
            'parcelas' => [
                'required', 'integer', 'between:1,12',
                Rule::unique('taxas_cartao', 'parcelas')
                    ->where('forma_pagamento', (string) $this->input('forma_pagamento'))
                    ->ignore($this->route('taxa')),
            ],
            'taxa_percentual' => ['nullable', 'decimal:0,3', 'min:0', 'max:100'],
            'observacao' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['forma_pagamento' => 'modalidade', 'parcelas' => 'parcelas', 'taxa_percentual' => 'taxa (%)', 'observacao' => 'observação'];
    }

    public function messages(): array
    {
        return ['parcelas.unique' => 'Já existe uma taxa cadastrada para essa modalidade e quantidade de parcelas.'];
    }
}
