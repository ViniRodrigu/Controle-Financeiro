@extends('layouts.app')

@section('titulo', 'Meu perfil')

@section('conteudo')
    <x-cabecalho titulo="Meu perfil" subtitulo="Seus dados de acesso e segurança da conta" />

    <div class="row g-4">
        <div class="col-lg-5 d-flex flex-column gap-4">
            <div class="card">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar fs-5">{{ $usuario->iniciais() }}</span>
                    <div>
                        <div class="fw-semibold">{{ $usuario->name }}</div>
                        <div class="text-muted small">{{ $usuario->email }}</div>
                        <span class="badge suave-primary mt-1">{{ $usuario->papel->rotulo() }}</span>
                    </div>
                </div>
                <div class="card-footer bg-white small text-muted">
                    Último acesso: {{ $usuario->ultimo_acesso_em?->format('d/m/Y H:i') ?? '—' }}
                    @if ($usuario->ultimo_acesso_ip) · IP {{ $usuario->ultimo_acesso_ip }} @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <span class="kpi-icone {{ $usuario->temDoisFatoresAtivo() ? 'suave-success' : 'suave-warning' }} rounded-3 p-2 d-inline-grid">
                            <i class="bi {{ $usuario->temDoisFatoresAtivo() ? 'bi-shield-check' : 'bi-shield-exclamation' }} fs-5"></i>
                        </span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">Verificação em duas etapas</div>
                            <p class="small text-muted mb-2">
                                {{ $usuario->temDoisFatoresAtivo()
                                    ? 'Ativa. Além da senha, o acesso exige um código do seu celular.'
                                    : 'Recomendado: protege a conta mesmo que a senha vaze.' }}
                            </p>
                            <a href="{{ route('perfil.seguranca') }}" class="btn btn-sm {{ $usuario->temDoisFatoresAtivo() ? 'btn-light' : 'btn-primary' }}">
                                {{ $usuario->temDoisFatoresAtivo() ? 'Gerenciar' : 'Ativar agora' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">Alterar senha</div>
                <div class="card-body p-4">
                    @if (session('status') === 'password-updated')
                        <div class="alert alert-success suave-success small"><i class="bi bi-check-circle me-1"></i>Senha alterada. As demais sessões abertas foram encerradas.</div>
                    @endif

                    <form method="POST" action="{{ route('user-password.update') }}" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Senha atual</label>
                            <input type="password" id="current_password" name="current_password" autocomplete="current-password" required
                                   @class(['form-control', 'is-invalid' => $errors->updatePassword->has('current_password')])>
                            @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Nova senha</label>
                            <input type="password" id="password" name="password" autocomplete="new-password" required
                                   @class(['form-control', 'is-invalid' => $errors->updatePassword->has('password')])>
                            @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Mínimo de 10 caracteres, com maiúsculas, minúsculas, números e símbolos.</div>
                        </div>
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Confirmar nova senha</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-key me-1"></i>Alterar senha</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
