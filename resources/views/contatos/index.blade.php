@extends('layouts.app')

@section('titulo', 'Pacientes e fornecedores')

@use('App\Enums\TipoContato')

@section('conteudo')
    <x-cabecalho titulo="Pacientes e fornecedores" subtitulo="Cadastro criado automaticamente ao registrar lançamentos">
        @can('editar')
            <a href="{{ route('contatos.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Novo cadastro</a>
        @endcan
    </x-cabecalho>

    <div class="card mb-3 filtros">
        <div class="card-body">
            <form method="GET" action="{{ route('contatos.index') }}" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label for="busca" class="form-label">Buscar</label>
                    <input type="search" id="busca" name="busca" value="{{ $filtros['busca'] }}" class="form-control" placeholder="Nome, telefone ou e-mail" maxlength="100">
                </div>
                <div class="col-md-4">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select id="tipo" name="tipo" class="form-select" data-auto-enviar>
                        <option value="">Todos</option>
                        @foreach (TipoContato::opcoes() as $valor => $rotulo)
                            <option value="{{ $valor }}" @selected(($filtros['tipo'] ?? '') === $valor)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i></button>
                    <a href="{{ route('contatos.index') }}" class="btn btn-light" title="Limpar"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Contato</th>
                    <th class="text-end">Lançamentos</th>
                    <th class="text-end">Total recebido</th>
                    <th class="text-end">Total pago</th>
                    <th>Último</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($contatos as $contato)
                    <tr @class(['text-muted' => ! $contato->ativo])>
                        <td><a href="{{ route('contatos.show', $contato) }}" class="fw-semibold text-body">{{ $contato->nome }}</a></td>
                        <td><span class="badge {{ $contato->tipo === TipoContato::Paciente ? 'suave-primary' : 'suave-secondary' }}">{{ $contato->tipo->rotulo() }}</span></td>
                        <td class="small">
                            {{ $contato->telefone ?? '' }}
                            @if ($contato->email)
                                <div class="text-muted">{{ $contato->email }}</div>
                            @endif
                        </td>
                        <td class="text-end tabular">{{ $contato->lancamentos_count }}</td>
                        <td class="text-end tabular valor-entrada">{{ $contato->total_recebido ? moeda($contato->total_recebido) : '—' }}</td>
                        <td class="text-end tabular valor-saida">{{ $contato->total_pago ? moeda($contato->total_pago) : '—' }}</td>
                        <td class="tabular small">{{ $contato->ultimo_lancamento ? \Illuminate\Support\Carbon::parse($contato->ultimo_lancamento)->format('d/m/Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-vazio icone="bi-people" mensagem="Nenhum cadastro encontrado." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($contatos->hasPages())
            <div class="card-footer bg-white border-top py-3">{{ $contatos->links() }}</div>
        @endif
    </div>
@endsection
