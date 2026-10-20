@extends('layouts.app')

@section('titulo', 'Usuários')

@section('conteudo')
    <x-cabecalho titulo="Usuários" subtitulo="Quem pode acessar o sistema e com qual perfil">
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Novo usuário</a>
    </x-cabecalho>

    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr><th>Usuário</th><th>Perfil</th><th>2 etapas</th><th>Situação</th><th>Último acesso</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($usuarios as $usuario)
                    <tr @class(['text-muted' => ! $usuario->ativo])>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar">{{ $usuario->iniciais() }}</span>
                                <div>
                                    <div class="fw-semibold">{{ $usuario->name }} @if ($usuario->is(auth()->user()))<span class="badge suave-info ms-1">você</span>@endif</div>
                                    <div class="small text-muted">{{ $usuario->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge {{ $usuario->ehAdmin() ? 'suave-primary' : 'suave-secondary' }}">{{ $usuario->papel->rotulo() }}</span></td>
                        <td>
                            @if ($usuario->temDoisFatoresAtivo())
                                <span class="badge suave-success"><i class="bi bi-shield-check me-1"></i>Ativa</span>
                            @else
                                <span class="badge suave-warning"><i class="bi bi-shield-exclamation me-1"></i>Desligada</span>
                            @endif
                        </td>
                        <td>
                            @if ($usuario->ativo)
                                <span class="badge suave-success">Ativo</span>
                            @else
                                <span class="badge suave-secondary">Desativado</span>
                            @endif
                        </td>
                        <td class="small tabular">
                            {{ $usuario->ultimo_acesso_em?->format('d/m/Y H:i') ?? 'nunca' }}
                            @if ($usuario->ultimo_acesso_ip)
                                <div class="text-muted">IP {{ $usuario->ultimo_acesso_ip }}</div>
                            @endif
                        </td>
                        <td class="text-end"><a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-sm btn-light" title="Editar"><i class="bi bi-pencil"></i></a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
