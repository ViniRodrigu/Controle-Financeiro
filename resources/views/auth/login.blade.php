@extends('layouts.guest')

@section('titulo', 'Entrar')

@section('conteudo')
    <h2 class="h5 mb-1">Entrar</h2>
    <p class="text-muted small mb-4">Use o e-mail e a senha cadastrados pelo administrador.</p>

    @if (session('status'))
        <div class="alert alert-success suave-success small">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   @class(['form-control', 'is-invalid' => $errors->has('email')])
                   autocomplete="username" required autofocus maxlength="150">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">Senha</label>
            <input type="password" id="password" name="password"
                   @class(['form-control', 'is-invalid' => $errors->has('password')])
                   autocomplete="current-password" required>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
        </button>
    </form>

    <p class="text-muted small mt-3 mb-0">
        Esqueceu a senha? Peça ao administrador para redefini-la.
    </p>
@endsection
