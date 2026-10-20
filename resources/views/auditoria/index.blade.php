@extends('layouts.app')

@section('titulo', 'Auditoria')

@use('App\Models\AuditLog')

@section('conteudo')
    <x-cabecalho titulo="Auditoria" subtitulo="Quem fez o quê e quando: acessos, criações, alterações e exclusões" />

    <div class="card mb-3 filtros">
        <div class="card-body">
            <form method="GET" action="{{ route('auditoria') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="user_id" class="form-label">Usuário</label>
                    <select id="user_id" name="user_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" @selected((int) ($filtros['user_id'] ?? 0) === $usuario->id)>{{ $usuario->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="acao" class="form-label">Ação</label>
                    <select id="acao" name="acao" class="form-select">
                        <option value="">Todas</option>
                        @foreach (AuditLog::ACOES as $valor => $rotulo)
                            <option value="{{ $valor }}" @selected(($filtros['acao'] ?? '') === $valor)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="de" class="form-label">De</label>
                    <input type="date" id="de" name="de" value="{{ $filtros['de'] ?? '' }}" class="form-control">
                </div>
                <div class="col-6 col-md-2">
                    <label for="ate" class="form-label">Até</label>
                    <input type="date" id="ate" name="ate" value="{{ $filtros['ate'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-funnel"></i></button>
                    <a href="{{ route('auditoria') }}" class="btn btn-light" title="Limpar"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Quando</th><th>Usuário</th><th>Ação</th><th>Registro</th><th>Origem</th><th>Detalhes</th></tr></thead>
                <tbody>
                @forelse ($registros as $registro)
                    <tr>
                        <td class="tabular small text-nowrap">{{ $registro->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="small">{{ $registro->user?->name ?? 'Sistema' }}</td>
                        <td><span class="badge suave-{{ $registro->corAcao() }}">{{ $registro->rotuloAcao() }}</span></td>
                        <td class="small text-nowrap">
                            @if ($registro->auditable_type === 'lancamento')
                                <a href="{{ route('lancamentos.show', $registro->auditable_id) }}">Lançamento #{{ $registro->auditable_id }}</a>
                            @elseif ($registro->auditable_type)
                                {{ ucfirst(str_replace('_', ' ', $registro->auditable_type)) }} #{{ $registro->auditable_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="small text-muted text-nowrap" title="{{ $registro->user_agent }}">{{ $registro->ip ?? '—' }}</td>
                        <td class="small">
                            @php $campos = array_unique(array_merge(array_keys($registro->valores_antigos ?? []), array_keys($registro->valores_novos ?? []))); @endphp
                            @if ($campos)
                                <details>
                                    <summary class="text-primary">{{ count($campos) }} campo(s)</summary>
                                    <ul class="list-unstyled mt-2 mb-0">
                                        @foreach ($campos as $campo)
                                            <li>
                                                <strong>{{ AuditLog::rotuloCampo($campo) }}:</strong>
                                                @if ($registro->valores_antigos !== null && array_key_exists($campo, $registro->valores_antigos))
                                                    <span class="text-decoration-line-through text-muted">{{ AuditLog::formatarValor($registro->valores_antigos[$campo]) }}</span>
                                                @endif
                                                @if ($registro->valores_novos !== null && array_key_exists($campo, $registro->valores_novos))
                                                    → {{ AuditLog::formatarValor($registro->valores_novos[$campo]) }}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-vazio icone="bi-shield-check" mensagem="Nenhum registro encontrado." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($registros->hasPages())
            <div class="card-footer bg-white border-top py-3">{{ $registros->links() }}</div>
        @endif
    </div>
@endsection
