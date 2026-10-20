@extends('layouts.app')

@section('titulo', 'Verificação em duas etapas')

@php
    $status = session('status');
    $aguardandoConfirmacao = $usuario->two_factor_secret && ! $usuario->temDoisFatoresAtivo();
    $mostrarCodigos = $usuario->temDoisFatoresAtivo() && in_array($status, ['two-factor-authentication-confirmed', 'recovery-codes-generated'], true);
@endphp

@section('conteudo')
    <x-cabecalho titulo="Verificação em duas etapas" subtitulo="Proteja sua conta com um código do celular além da senha">
        <a href="{{ route('perfil') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </x-cabecalho>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body p-4">
                    @if ($usuario->temDoisFatoresAtivo())
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge suave-success fs-6"><i class="bi bi-shield-check me-1"></i>Ativa</span>
                            <span class="text-muted small">desde {{ $usuario->two_factor_confirmed_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="text-muted">Ao entrar, depois da senha, será pedido o código de 6 dígitos do seu aplicativo autenticador.</p>

                        @if ($mostrarCodigos)
                            <div class="alert alert-warning suave-warning small">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Guarde estes <strong>códigos de recuperação</strong> em local seguro (fora do computador). Cada um pode ser usado uma única vez se você perder o celular. Eles não serão exibidos novamente.
                            </div>
                            <ul class="codigos-recuperacao list-unstyled mb-4">
                                @foreach ($usuario->recoveryCodes() as $codigo)
                                    <li>{{ $codigo }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="d-flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                                @csrf
                                <button type="submit" class="btn btn-light"><i class="bi bi-arrow-repeat me-1"></i>Gerar novos códigos de recuperação</button>
                            </form>
                            <form method="POST" action="{{ route('two-factor.disable') }}"
                                  data-confirmar="Desativar a verificação em duas etapas? Sua conta ficará protegida apenas pela senha." data-confirmar-botao="Desativar">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger"><i class="bi bi-shield-x me-1"></i>Desativar</button>
                            </form>
                        </div>
                    @elseif ($aguardandoConfirmacao)
                        <h2 class="h6">1. Escaneie o QR code</h2>
                        <p class="text-muted small">No aplicativo autenticador (Google Authenticator, Microsoft Authenticator, Authy…), adicione uma conta e escaneie a imagem abaixo.</p>
                        <div class="d-flex flex-wrap align-items-center gap-4 mb-4">
                            <div class="qr-2fa bg-white p-2 rounded-3 border">{!! $usuario->twoFactorQrCodeSvg() !!}</div>
                            <div class="small">
                                <div class="text-muted mb-1">Ou digite esta chave manualmente:</div>
                                <code class="fs-6 user-select-all">{{ decrypt($usuario->two_factor_secret) }}</code>
                            </div>
                        </div>

                        <h2 class="h6">2. Digite o código gerado</h2>
                        <form method="POST" action="{{ route('two-factor.confirm') }}" class="row g-2 align-items-start" novalidate>
                            @csrf
                            <div class="col-sm-5">
                                <input type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" autofocus
                                       aria-label="Código de 6 dígitos" placeholder="000000"
                                       @class(['form-control form-control-lg text-center tabular', 'is-invalid' => $errors->confirmTwoFactorAuthentication->has('code')])>
                                @error('code', 'confirmTwoFactorAuthentication')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-auto">
                                <button type="submit" class="btn btn-primary btn-lg">Confirmar e ativar</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-link btn-sm px-0 text-muted">Cancelar configuração</button>
                        </form>
                    @else
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge suave-warning fs-6"><i class="bi bi-shield-exclamation me-1"></i>Desligada</span>
                        </div>
                        <p class="text-muted">
                            Com a verificação em duas etapas, mesmo que alguém descubra sua senha não conseguirá entrar sem o código
                            que muda a cada 30 segundos no seu celular. É altamente recomendada para quem acessa dados financeiros.
                        </p>
                        <form method="POST" action="{{ route('two-factor.enable') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary"><i class="bi bi-shield-lock me-1"></i>Ativar verificação em duas etapas</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
