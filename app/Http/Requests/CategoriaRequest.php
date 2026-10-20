<?php

namespace App\Http\Requests;

use App\Enums\TipoLancamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => Str::squish((string) $this->input('nome')),
            'grupo' => Str::squish((string) $this->input('grupo')),
            'cor' => Str::lower((string) $this->input('cor', '#7b3f6e')),
            'ativo' => $this->boolean('ativo'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nome' => [
                'required', 'string', 'max:80',
                Rule::unique('categorias', 'nome')->where('tipo', (string) $this->input('tipo'))->ignore($this->route('categoria')),
            ],
            'tipo' => ['required', Rule::enum(TipoLancamento::class)],
            'grupo' => ['required', 'string', 'max:60'],
            'cor' => ['required', 'regex:/^#[0-9a-f]{6}$/'],
            'ativo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['nome' => 'nome', 'tipo' => 'tipo', 'grupo' => 'grupo', 'cor' => 'cor'];
    }

    public function messages(): array
    {
        return ['nome.unique' => 'Já existe uma categoria com esse nome para esse tipo.'];
    }
}
