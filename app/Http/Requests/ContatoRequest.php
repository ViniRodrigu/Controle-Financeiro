<?php

namespace App\Http\Requests;

use App\Enums\TipoContato;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContatoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => Str::squish((string) $this->input('nome')),
            'email' => Str::lower(trim((string) $this->input('email'))) ?: null,
            'telefone' => Str::squish((string) $this->input('telefone')) ?: null,
            'ativo' => $this->boolean('ativo'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:120'],
            'tipo' => ['required', Rule::enum(TipoContato::class)],
            'telefone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9()+\-\s]+$/'],
            'email' => ['nullable', 'email:rfc', 'max:150'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'ativo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['nome' => 'nome', 'tipo' => 'tipo', 'telefone' => 'telefone', 'email' => 'e-mail', 'observacoes' => 'observações'];
    }
}
