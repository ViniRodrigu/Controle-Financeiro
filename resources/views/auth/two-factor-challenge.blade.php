@extends('layouts.guest')

@section('titulo', 'Verificação em duas etapas')

@section('conteudo')
    <h2 class="h5 mb-1">Verificação em duas etapas</h2>
    <p class="text-muted small mb-4">Digite o código de 6 dígitos do seu aplicativo autenticador (Google Authenticator, Microsoft Authenticator, Authy…).</p>

    <form method="POST" action="{{ route('two-factor.login.store') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="code" class="form-label">Código do aplicativo</label>
            <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                   @class(['form-control form-control-lg text-center tabular', 'is-invalid' => $errors->has('code')])
                   autocomplete="one-time-code" autofocus>
            @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <details class="mb-4">
            <summary class="small text-muted">Perdeu o acesso ao aplicativo? Use um código de recuperação</summary>
            <div class="mt-2">
                <label for="recovery_code" class="form-label small">Código de recuperação</label>
                <input type="text" id="recovery_code" name="recovery_code" maxlength="64"
                       @class(['form-control', 'is-invalid' => $errors->has('recovery_code')]) autocomplete="off">
                @error('recovery_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </details>

        <button type="submit" class="btn btn-primary w-100 py-2">Confirmar</button>
    </form>
@endsection
