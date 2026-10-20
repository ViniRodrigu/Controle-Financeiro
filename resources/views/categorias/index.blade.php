@extends('layouts.app')

@section('titulo', 'Categorias')

@use('App\Enums\TipoLancamento')

@section('conteudo')
    <x-cabecalho titulo="Categorias" subtitulo="Classificação das entradas e saídas usada nos relatórios">
        <a href="{{ route('categorias.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nova categoria</a>
    </x-cabecalho>

    <div class="row g-4">
        @foreach (TipoLancamento::cases() as $tipo)
            <div class="col-xl-6">
                <div class="card table-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi {{ $tipo->icone() }} text-{{ $tipo->cor() }} me-1"></i>{{ $tipo === TipoLancamento::Entrada ? 'Receitas' : 'Despesas' }}</span>
                        <a href="{{ route('categorias.create', ['tipo' => $tipo->value]) }}" class="small fw-normal"><i class="bi bi-plus"></i> Adicionar</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Categoria</th><th>Grupo</th><th class="text-end">Lançamentos</th><th>Situação</th><th></th></tr></thead>
                            <tbody>
                            @forelse ($porTipo->get($tipo->value, collect()) as $categoria)
                                <tr @class(['text-muted' => ! $categoria->ativo])>
                                    <td><span class="d-inline-flex align-items-center gap-2"><span class="ponto-cor" data-cor="{{ $categoria->cor }}"></span>{{ $categoria->nome }}</span></td>
                                    <td class="small">{{ $categoria->grupo }}</td>
                                    <td class="text-end tabular">
                                        <a href="{{ route('lancamentos.index', ['categoria_id' => $categoria->id]) }}">{{ $categoria->lancamentos_count }}</a>
                                    </td>
                                    <td>
                                        @if ($categoria->ativo)
                                            <span class="badge suave-success">Ativa</span>
                                        @else
                                            <span class="badge suave-secondary">Inativa</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('categorias.edit', $categoria) }}" class="btn btn-sm btn-light" title="Editar"><i class="bi bi-pencil"></i></a>
                                        @if ($categoria->lancamentos_count === 0)
                                            <form method="POST" action="{{ route('categorias.destroy', $categoria) }}" class="d-inline"
                                                  data-confirmar="Excluir a categoria “{{ $categoria->nome }}”?" data-confirmar-botao="Excluir">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light" title="Excluir"><i class="bi bi-trash3 text-danger"></i></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><x-vazio icone="bi-tags" mensagem="Nenhuma categoria." /></td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
