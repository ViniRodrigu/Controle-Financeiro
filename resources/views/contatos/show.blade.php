@extends('layouts.app')

@section('titulo', $contato->nome)

@php
    $atendimentos = (int) $totais->atendimentos;
    $ticket = $atendimentos > 0 ? (float) $totais->recebido / $atendimentos : null;
@endphp

@section('conteudo')
    <x-cabecalho :titulo="$contato->nome" :subtitulo="$contato->tipo->rotulo().($contato->ativo ? '' : ' · inativo')">
        <a href="{{ route('contatos.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
        <a href="{{ route('lancamentos.index', ['contato_id' => $contato->id]) }}" class="btn btn-light"><i class="bi bi-list-ul me-1"></i>Ver na lista</a>
        @can('editar')
            <a href="{{ route('contatos.edit', $contato) }}" class="btn btn-light"><i class="bi bi-pencil me-1"></i>Editar</a>
            <form method="POST" action="{{ route('contatos.destroy', $contato) }}"
                  data-confirmar="Excluir o cadastro de {{ $contato->nome }}? Os lançamentos continuam no histórico." data-confirmar-botao="Excluir">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Excluir</button>
            </form>
        @endcan
    </x-cabecalho>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-kpi compacto rotulo="Total recebido" :valor="moeda($totais->recebido)" icone="bi-wallet2" cor="success" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-kpi compacto rotulo="Atendimentos pagos" :valor="$atendimentos" icone="bi-calendar-check" cor="primary" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-kpi compacto rotulo="Ticket médio" :valor="moeda($ticket)" icone="bi-graph-up" cor="info" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-kpi compacto rotulo="Total pago a este contato" :valor="moeda($totais->pago)" icone="bi-receipt" cor="danger" />
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Dados</div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="text-muted fw-medium small">Telefone</dt>
                        <dd>{{ $contato->telefone ?: '—' }}</dd>
                        <dt class="text-muted fw-medium small">E-mail</dt>
                        <dd>{{ $contato->email ?: '—' }}</dd>
                        <dt class="text-muted fw-medium small">Observações</dt>
                        <dd class="mb-0">{!! $contato->observacoes ? nl2br(e($contato->observacoes)) : '—' !!}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card table-card">
                <div class="card-header">Lançamentos</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Data</th><th>Descrição</th><th class="text-end">Valor</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse ($lancamentos as $lancamento)
                            <tr>
                                <td class="tabular">{{ $lancamento->data->format('d/m/Y') }}</td>
                                <td>
                                    <a href="{{ route('lancamentos.show', $lancamento) }}" class="text-body fw-semibold">{{ $lancamento->descricao ?: $lancamento->categoria->nome }}</a>
                                    <div class="small text-muted">{{ $lancamento->categoria->nome }}</div>
                                </td>
                                <td @class(['text-end tabular fw-semibold', 'valor-entrada' => $lancamento->ehEntrada(), 'valor-saida' => ! $lancamento->ehEntrada()])>{{ moeda($lancamento->valor) }}</td>
                                <td><x-status-lancamento :lancamento="$lancamento" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-vazio mensagem="Nenhum lançamento para este cadastro." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($lancamentos->hasPages())
                    <div class="card-footer bg-white border-top py-3">{{ $lancamentos->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
