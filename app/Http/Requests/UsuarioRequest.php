<?php

namespace App\Http\Requests;

use App\Enums\Papel;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'ativo' => $this->boolean('ativo'),
            'resetar_2fa' => $this->boolean('resetar_2fa'),
        ]);
    }

    public function rules(): array
    {
        /** @var User|null $usuario */
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:150', Rule::unique('users', 'email')->ignore($usuario)],
            'papel' => ['required', Rule::enum(Papel::class)],
            'ativo' => ['boolean'],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'confirmed', Password::default()],
            'resetar_2fa' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var User|null $usuario */
                $usuario = $this->route('usuario');

                if (! $usuario || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $continuaAdminAtivo = $this->input('papel') === Papel::Admin->value && $this->boolean('ativo');

                if ($usuario->is($this->user()) && ! $continuaAdminAtivo) {
                    $validator->errors()->add('papel', 'Você não pode remover seu próprio acesso de administrador nem se desativar.');
                }

                $outrosAdmins = User::query()->where('papel', Papel::Admin)->where('ativo', true)->whereKeyNot($usuario->getKey())->exists();

                if ($usuario->ehAdmin() && $usuario->ativo && ! $continuaAdminAtivo && ! $outrosAdmins) {
                    $validator->errors()->add('papel', 'O sistema precisa ter pelo menos um administrador ativo.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nome', 'email' => 'e-mail', 'papel' => 'perfil de acesso', 'password' => 'senha'];
    }
}
