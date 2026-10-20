<?php

namespace App\Http\Controllers;

use App\Enums\Papel;
use App\Http\Requests\UsuarioRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('usuarios.index', [
            'usuarios' => User::query()->orderByDesc('ativo')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('usuarios.create', ['usuario' => new User(['papel' => Papel::Financeiro, 'ativo' => true])]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        User::create(Arr::only($request->validated(), ['name', 'email', 'papel', 'ativo', 'password']));

        return redirect()->route('usuarios.index')->with('sucesso', 'Usuário criado. Envie a senha inicial por um canal seguro.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.edit', compact('usuario'));
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $dados = $request->validated();

        $usuario->fill(Arr::only($dados, ['name', 'email', 'papel', 'ativo']));

        if (! empty($dados['password'])) {
            $usuario->password = $dados['password'];
        }

        if (! empty($dados['resetar_2fa'])) {
            $usuario->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ]);
        }

        // Mudanças de acesso derrubam as sessões abertas do usuário.
        $encerrarSessoes = $usuario->isDirty(['password', 'papel', 'email', 'two_factor_secret'])
            || ($usuario->isDirty('ativo') && ! $usuario->ativo);

        $usuario->save();

        if ($encerrarSessoes && $usuario->isNot($request->user()) && config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $usuario->getKey())->delete();
        }

        return redirect()->route('usuarios.index')->with('sucesso', 'Usuário atualizado.');
    }
}
