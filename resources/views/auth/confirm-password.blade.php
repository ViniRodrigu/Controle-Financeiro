@extends('layouts.guest')

@section('titulo', 'Confirme sua senha')

@section('conteudo')
    <h2 class="h5 mb-1">Confirme sua senha</h2>
    <p class="text-muted small mb-4">Esta é uma área de segurança. Por favor, confirme sua senha para continuar.</p>

    <form method="POST" action="{{ route('password.confirm.store') }}" novalidate>
        @csrf

        <div class="mb-4">
            <label for="password" class="form-label">Senha</label>
            <input type="password" id="password" name="password"
                   @class(['form-control', 'is-invalid' => $errors->has('password')])
                   autocomplete="current-password" required autofocus>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('perfil') }}" class="btn btn-light flex-fill">Voltar</a>
            <button type="submit" class="btn btn-primary flex-fill">Confirmar</button>
        </div>
    </form>
@endsection
