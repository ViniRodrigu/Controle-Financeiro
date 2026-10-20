@extends('layouts.app')

@section('titulo', 'Lançamentos')

@use('App\Enums\FormaPagamento')
@use('App\Enums\TipoLancamento')

@php
    $hoje = now();
    $atalhos = [
        'Este mês' => ['de' => $hoje->copy()->startOfMonth()->toDateString(), 'ate' => $hoje->copy()->endOfMonth()->toDateString()],
        'Mês passado' => ['de' => $hoje->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), 'ate' => $hoje->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        'Este ano' => ['de' => $hoje->copy()->startOfYear()->toDateString(), 'ate' => $hoje->copy()->endOfYear()->toDateString()],
    ];
    $saldo = (float) $totais->entradas - (float) $totais->saidas;
@endphp

@section('conteudo')
    <x-cabecalho :titulo="$lixeira ? 'Lixeira de lançamentos' : 'Lançamentos'" subtitulo="Entradas e saídas registradas, com filtros e exportação">
        @can('admin')
            @if ($lixeira)
                <a href="{{ route('lancamentos.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
            @else
                <a href="{{ route('lancamentos.index', ['lixeira' => 1]) }}" class="btn btn-light"><i class="bi bi-trash3 me-1"></i>Lixeira</a>
            @endif
        @endcan
        @unless ($lixeira)
            <a href="{{ route('lancamentos.exportar', request()->query()) }}" class="btn btn-light"><i class="bi bi-filetype-csv me-1"></i>Exportar CSV</a>
        @endunless
        @can('editar')
            <a href="{{ route('lancamentos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Novo lançamento</a>
        @endcan
    </x-cabecalho>

    <div class="card mb-3 filtros">
        <div class="card-body">
            <form method="GET" action="{{ route('lancamentos.index') }}" class="row g-2 align-items-end">
                @if ($lixeira)
                    <input type="hidden" name="lixeira" value="1">
                @endif
                @if ($contatoFiltrado)
                    <input type="hidden" name="contato_id" value="{{ $contatoFiltrado->id }}">
                @endif
                <div class="col-12 col-lg-4">
                    <label for="busca" class="form-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" id="busca" name="busca" value="{{ $filtros['busca'] ?? '' }}" class="form-control" placeholder="Descrição ou paciente" maxlength="100">
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select id="tipo" name="tipo" class="form-select">
                        <option value="">Todos</option>
                        @foreach (TipoLancamento::opcoes() as $valor => $rotulo)
                            <option value="{{ $valor }}" @selected(($filtros['tipo'] ?? '') === $valor)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="pago" @selected(($filtros['status'] ?? '') === 'pago')>Pago / recebido</option>
                        <option value="pendente" @selected(($filtros['status'] ?? '') === 'pendente')>Pendente</option>
                        <option value="vencido" @selected(($filtros['status'] ?? '') === 'vencido')>Vencido</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label for="categoria_id" class="form-label">Categoria</label>
                    <select id="categoria_id" name="categoria_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($categorias->groupBy(fn ($c) => $c->tipo->rotulo()) as $tipoRotulo => $lista)
                            <optgroup label="{{ $tipoRotulo }}">
                                @foreach ($lista as $categoria)
                                    <option value="{{ $categoria->id }}" @selected((int) ($filtros['categoria_id'] ?? 0) === $categoria->id)>{{ $categoria->nome }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label for="forma_pagamento" class="form-label">Forma</label>
                    <select id="forma_pagamento" name="forma_pagamento" class="form-select">
                        <option value="">Todas</option>
                        @foreach (FormaPagamento::opcoes() as $valor => $rotulo)
                            <option value="{{ $valor }}" @selected(($filtros['forma_pagamento'] ?? '') === $valor)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label for="de" class="form-label">De</label>
                    <input type="date" id="de" name="de" value="{{ $filtros['de'] ?? '' }}" class="form-control">
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label for="ate" class="form-label">Até</label>
                    <input type="date" id="ate" name="ate" value="{{ $filtros['ate'] ?? '' }}" class="form-control">
                </div>
                <div class="col-12 col-md-6 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill" title="Filtrar"><i class="bi bi-funnel"></i></button>
                    <a href="{{ route('lancamentos.index', $lixeira ? ['lixeira' => 1] : []) }}" class="btn btn-light" title="Limpar filtros"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>

            <div class="d-flex flex-wrap align-items-center gap-2 mt-3 small">
                <span class="text-muted">Períodos:</span>
                @foreach ($atalhos as $rotulo => $periodo)
                    <a href="{{ route('lancamentos.index', array_merge(request()->except(['de', 'ate', 'page']), $periodo)) }}"
                       @class(['badge rounded-pill text-decoration-none', 'text-bg-primary' => ($filtros['de'] ?? null) === $periodo['de'] && ($filtros['ate'] ?? null) === $periodo['ate'], 'suave-secondary' => ! (($filtros['de'] ?? null) === $periodo['de'] && ($filtros['ate'] ?? null) === $periodo['ate'])])>{{ $rotulo }}</a>
                @endforeach
                @if ($contatoFiltrado)
                    <span class="badge suave-primary ms-2">
                        <i class="bi bi-person me-1"></i>{{ $contatoFiltrado->nome }}
                        <a href="{{ route('lancamentos.index', request()->except(['contato_id', 'page'])) }}" class="text-reset ms-1" aria-label="Remover filtro de contato"><i class="bi bi-x"></i></a>
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card"><div class="card-body py-3 d-flex justify-content-between align-items-center">
                <span class="text-muted small">Entradas (no caixa)</span>
                <span class="fw-bold tabular valor-entrada">{{ moeda($totais->entradas) }}</span>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body py-3 d-flex justify-content-between align-items-center">
                <span class="text-muted small">Saídas</span>
                <span class="fw-bold tabular valor-saida">{{ moeda($totais->saidas) }}</span>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body py-3 d-flex justify-content-between align-items-center">
                <span class="text-muted small">Saldo · {{ $totais->quantidade }} lançamento(s)</span>
                <span @class(['fw-bold tabular', 'valor-entrada' => $saldo >= 0, 'valor-saida' => $saldo < 0])>{{ moeda($saldo) }}</span>
            </div></div>
        </div>
    </div>

    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th>Forma</th>
                    <th class="text-end">Valor bruto</th>
                    <th class="text-end">No caixa</th>
                    <th>Status</th>
                    <th class="text-end"><span class="visually-hidden">Ações</span></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($lancamentos as $lancamento)
                    <tr>
                        <td class="tabular text-nowrap">{{ $lancamento->data->format('d/m/Y') }}</td>
                        <td>
                            <a href="{{ route('lancamentos.show', $lancamento) }}" class="fw-semibold text-body">
                                {{ $lancamento->descricao ?: $lancamento->categoria->nome }}
                            </a>
                            <div class="small text-muted">
                                <i class="bi {{ $lancamento->tipo->icone() }} text-{{ $lancamento->tipo->cor() }}"></i>
                                {{ $lancamento->contato?->nome ?? $lancamento->tipo->rotulo() }}
                                @if ($lancamento->parcela_total)
                                    <span class="badge suave-secondary ms-1">{{ $lancamento->parcela_numero }}/{{ $lancamento->parcela_total }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="text-nowrap">
                            <span class="d-inline-flex align-items-center gap-2">
                                <span class="ponto-cor" data-cor="{{ $lancamento->categoria->cor }}"></span>{{ $lancamento->categoria->nome }}
                            </span>
                        </td>
                        <td class="text-nowrap small">
                            <i class="bi {{ $lancamento->forma_pagamento->icone() }} text-muted me-1"></i>{{ $lancamento->forma_pagamento->rotulo() }}
                            @if ($lancamento->parcelas_cartao > 1)
                                <span class="text-muted">{{ $lancamento->parcelas_cartao }}x</span>
                            @endif
                        </td>
                        <td class="text-end tabular text-nowrap">{{ moeda($lancamento->valor) }}</td>
                        <td @class(['text-end tabular fw-semibold text-nowrap', 'valor-entrada' => $lancamento->ehEntrada(), 'valor-saida' => ! $lancamento->ehEntrada()])>
                            {{ $lancamento->ehEntrada() ? '+' : '−' }} {{ moeda($lancamento->valor_caixa) }}
                        </td>
                        <td><x-status-lancamento :lancamento="$lancamento" /></td>
                        <td class="text-end text-nowrap">
                            @if ($lixeira)
                                <form method="POST" action="{{ route('lancamentos.restaurar', $lancamento) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-light"><i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar</button>
                                </form>
                            @else
                                @can('editar')
                                    @if ($lancamento->estaPendente())
                                        <button type="button" class="btn btn-sm btn-light" title="{{ $lancamento->ehEntrada() ? 'Confirmar recebimento' : 'Confirmar pagamento' }}"
                                                data-baixar="{{ route('lancamentos.baixar', $lancamento) }}"
                                                data-titulo="{{ $lancamento->ehEntrada() ? 'Confirmar recebimento' : 'Confirmar pagamento' }}"
                                                data-descricao="{{ ($lancamento->descricao ?: $lancamento->categoria->nome).' — '.moeda($lancamento->valor_caixa) }}">
                                            <i class="bi bi-check2-circle text-success"></i>
                                        </button>
                                    @endif
                                    <a href="{{ route('lancamentos.edit', $lancamento) }}" class="btn btn-sm btn-light" title="Editar"><i class="bi bi-pencil"></i></a>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-vazio icone="bi-journal-x" mensagem="Nenhum lançamento encontrado com esses filtros.">
                                @can('editar')
                                    <a href="{{ route('lancamentos.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Registrar lançamento</a>
                                @endcan
                            </x-vazio>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($lancamentos->hasPages())
            <div class="card-footer bg-white border-top py-3">{{ $lancamentos->links() }}</div>
        @endif
    </div>
@endsection
