@use('App\Enums\Papel')

<form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" novalidate autocomplete="off">
    @csrf
    @if ($usuario->exists)
        @method('PUT')
    @endif

    <div class="card mb-4">
        <div class="card-header">Dados de acesso</div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Nome</label>
                    <input type="text" id="name" name="name" maxlength="120" required value="{{ old('name', $usuario->name) }}"
                           @class(['form-control', 'is-invalid' => $errors->has('name')])>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" id="email" name="email" maxlength="150" required value="{{ old('email', $usuario->email) }}"
                           @class(['form-control', 'is-invalid' => $errors->has('email')])>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <span class="form-label d-block">Perfil de acesso</span>
                    <div class="row g-2">
                        @foreach (Papel::cases() as $papel)
                            <div class="col-md-4">
                                <input type="radio" class="btn-check" name="papel" id="papel_{{ $papel->value }}" value="{{ $papel->value }}"
                                       @checked(old('papel', $usuario->papel?->value) === $papel->value)>
                                <label class="btn btn-outline-primary w-100 h-100 text-start p-3" for="papel_{{ $papel->value }}">
                                    <span class="d-block fw-semibold">{{ $papel->rotulo() }}</span>
                                    <span class="d-block small fw-normal opacity-75">{{ $papel->descricao() }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    @error('papel')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="ativo" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" @checked(old('ativo', $usuario->ativo))>
                        <label class="form-check-label" for="ativo">Acesso ativo</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">{{ $usuario->exists ? 'Redefinir senha' : 'Senha inicial' }}</div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="password" class="form-label">{{ $usuario->exists ? 'Nova senha' : 'Senha' }}</label>
                    <input type="password" id="password" name="password" autocomplete="new-password" @if (! $usuario->exists) required @endif
                           @class(['form-control', 'is-invalid' => $errors->has('password')])>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="password_confirmation" class="form-label">Confirmar senha</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="form-control">
                </div>
                <div class="col-12 form-text mt-1">
                    Mínimo de 10 caracteres, com letras maiúsculas e minúsculas, números e símbolos.
                    @if ($usuario->exists) Deixe em branco para manter a senha atual. Ao trocar, as sessões abertas do usuário são encerradas. @endif
                </div>
            </div>

            @if ($usuario->exists && $usuario->temDoisFatoresAtivo())
                <hr>
                <div class="form-check">
                    <input type="hidden" name="resetar_2fa" value="0">
                    <input class="form-check-input" type="checkbox" id="resetar_2fa" name="resetar_2fa" value="1" @checked(old('resetar_2fa'))>
                    <label class="form-check-label" for="resetar_2fa">
                        Desligar a verificação em duas etapas deste usuário
                        <span class="d-block small text-muted">Use se ele perdeu o celular e os códigos de recuperação. Ele poderá ativar de novo depois.</span>
                    </label>
                </div>
            @endif
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('usuarios.index') }}" class="btn btn-light">Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Salvar</button>
    </div>
</form>
