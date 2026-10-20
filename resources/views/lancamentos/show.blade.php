@extends('layouts.app')

@section('titulo', 'Lançamento')

@use('App\Models\AuditLog')

@php
    $titulo = $lancamento->descricao ?: $lancamento->categoria->nome;
    $diferenca = $lancamento->diferenca();
@endphp

@section('conteudo')
    <x-cabecalho :titulo="$titulo" :subtitulo="$lancamento->tipo->rotulo().' · '.$lancamento->data->format('d/m/Y')">
        <a href="{{ route('lancamentos.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
        @if ($lancamento->trashed())
            @can('admin')
                <form method="POST" action="{{ route('lancamentos.restaurar', $lancamento) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success"><i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar</button>
                </form>
            @endcan
        @else
            @can('editar')
                @if ($lancamento->estaPendente())
                    <button type="button" class="btn btn-success"
                            data-baixar="{{ route('lancamentos.baixar', $lancamento) }}"
                            data-titulo="{{ $lancamento->ehEntrada() ? 'Confirmar recebimento' : 'Confirmar pagamento' }}"
                            data-descricao="{{ $titulo.' — '.moeda($lancamento->valor_caixa) }}">
                        <i class="bi bi-check2-circle me-1"></i>{{ $lancamento->ehEntrada() ? 'Confirmar recebimento' : 'Confirmar pagamento' }}
                    </button>
                @endif
                <a href="{{ route('lancamentos.create', ['copiar' => $lancamento->id]) }}" class="btn btn-light"><i class="bi bi-copy me-1"></i>Duplicar</a>
                <a href="{{ route('lancamentos.edit', $lancamento) }}" class="btn btn-light"><i class="bi bi-pencil me-1"></i>Editar</a>
                <form method="POST" action="{{ route('lancamentos.destroy', $lancamento) }}"
                      data-confirmar="Excluir este lançamento? Ele irá para a lixeira e poderá ser restaurado por um administrador."
                      data-confirmar-botao="Excluir">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Excluir</button>
                </form>
            @endcan
        @endif
    </x-cabecalho>

    @if ($lancamento->trashed())
        <div class="alert alert-warning suave-warning"><i class="bi bi-trash3 me-1"></i>Este lançamento foi excluído em {{ $lancamento->deleted_at->format('d/m/Y H:i') }} e não entra nos relatórios.</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8 d-flex flex-column gap-4">
            <div class="card">
                <div class="card-header">Detalhes</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted fw-medium">Tipo</dt>
                        <dd class="col-sm-8"><span class="badge suave-{{ $lancamento->tipo->cor() }}"><i class="bi {{ $lancamento->tipo->icone() }} me-1"></i>{{ $lancamento->tipo->rotulo() }}</span></dd>

                        <dt class="col-sm-4 text-muted fw-medium">Status</dt>
                        <dd class="col-sm-8"><x-status-lancamento :lancamento="$lancamento" /></dd>

                        <dt class="col-sm-4 text-muted fw-medium">Data</dt>
                        <dd class="col-sm-8">{{ $lancamento->data->format('d/m/Y') }}</dd>

                        <dt class="col-sm-4 text-muted fw-medium">Categoria</dt>
                        <dd class="col-sm-8">
                            <span class="d-inline-flex align-items-center gap-2"><span class="ponto-cor" data-cor="{{ $lancamento->categoria->cor }}"></span>{{ $lancamento->categoria->nome }}</span>
                            <span class="text-muted small">· {{ $lancamento->categoria->grupo }}</span>
                        </dd>

                        <dt class="col-sm-4 text-muted fw-medium">{{ $lancamento->ehEntrada() ? 'Paciente' : 'Fornecedor' }}</dt>
                        <dd class="col-sm-8">
                            @if ($lancamento->contato)
                                <a href="{{ route('contatos.show', $lancamento->contato) }}">{{ $lancamento->contato->nome }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted fw-medium">Forma de pagamento</dt>
                        <dd class="col-sm-8">
                            <i class="bi {{ $lancamento->forma_pagamento->icone() }} text-muted me-1"></i>{{ $lancamento->forma_pagamento->rotulo() }}
                            @if ($lancamento->parcelas_cartao)
                                · {{ $lancamento->parcelas_cartao }}x no cartão
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted fw-medium">Descrição</dt>
                        <dd class="col-sm-8">{{ $lancamento->descricao ?: '—' }}</dd>

                        <dt class="col-sm-4 text-muted fw-medium">Observações</dt>
                        <dd class="col-sm-8 mb-0">{!! $lancamento->observacoes ? nl2br(e($lancamento->observacoes)) : '—' !!}</dd>
                    </dl>
                </div>
            </div>

            @if ($parcelas->isNotEmpty())
                <div class="card table-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Parcelamento / recorrência ({{ $parcelas->count() }})</span>
                        @can('editar')
                            @if ($parcelas->contains(fn ($p) => $p->estaPendente()))
                                <form method="POST" action="{{ route('lancamentos.destroy', $lancamento) }}"
                                      data-confirmar="Excluir todas as parcelas ainda pendentes deste grupo?" data-confirmar-botao="Excluir pendentes">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="pendentes_do_grupo" value="1">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir pendentes</button>
                                </form>
                            @endif
                        @endcan
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Parcela</th><th>Data</th><th class="text-end">Valor</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach ($parcelas as $parcela)
                                <tr @class(['table-active' => $parcela->is($lancamento)])>
                                    <td><a href="{{ route('lancamentos.show', $parcela) }}">{{ $parcela->parcela_numero }}/{{ $parcela->parcela_total }}</a></td>
                                    <td class="tabular">{{ $parcela->data->format('d/m/Y') }}</td>
                                    <td class="text-end tabular">{{ moeda($parcela->valor) }}</td>
                                    <td><x-status-lancamento :lancamento="$parcela" /></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4 d-flex flex-column gap-4">
            <div class="card">
                <div class="card-header">Valores</div>
                <div class="card-body">
                    <dl class="row resumo-calculo mb-0">
                        <dt class="col-7">Valor bruto</dt>
                        <dd class="col-5">{{ moeda($lancamento->valor) }}</dd>
                        @if ($lancamento->ehEntrada())
                            <dt class="col-7">Taxa (%)</dt>
                            <dd class="col-5">{{ percentual($lancamento->taxa_percentual, 2) }}</dd>
                            <dt class="col-7">Taxa (R$)</dt>
                            <dd class="col-5 text-danger">{{ $lancamento->taxa_valor !== null ? '− '.moeda($lancamento->taxa_valor) : '—' }}</dd>
                            <dt class="col-7">Taxa efetiva</dt>
                            <dd class="col-5">{{ percentual($lancamento->taxaEfetiva(), 2) }}</dd>
                            <dt class="col-7">Líquido informado</dt>
                            <dd class="col-5">{{ moeda($lancamento->liquido_informado) }}</dd>
                            <dt class="col-7">Diferença vs. previsto</dt>
                            <dd @class(['col-5', 'text-danger' => $diferenca !== null && (float) $diferenca < 0, 'text-success' => $diferenca !== null && (float) $diferenca > 0])>{{ moeda($diferenca) }}</dd>
                        @endif
                        <dt class="col-5 total">No caixa</dt>
                        <dd @class(['col-7 total fs-5', 'valor-entrada' => $lancamento->ehEntrada(), 'valor-saida' => ! $lancamento->ehEntrada()])>{{ moeda($lancamento->valor_caixa) }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Histórico</div>
                <div class="card-body small">
                    <p class="text-muted mb-3">
                        Criado {{ $lancamento->criador ? 'por '.$lancamento->criador->name : 'pela importação' }}
                        em {{ $lancamento->created_at?->format('d/m/Y H:i') }}.
                    </p>
                    @foreach ($lancamento->historico as $registro)
                        <div class="border-start border-2 ps-3 pb-3 position-relative">
                            <div class="d-flex justify-content-between">
                                <span class="badge suave-{{ $registro->corAcao() }}">{{ $registro->rotuloAcao() }}</span>
                                <span class="text-muted">{{ $registro->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="text-muted mt-1">{{ $registro->user?->name ?? 'Sistema' }}</div>
                            @if ($registro->acao === 'atualizado' && $registro->valores_novos)
                                <ul class="list-unstyled mt-1 mb-0">
                                    @foreach ($registro->valores_novos as $campo => $novo)
                                        <li>
                                            <strong>{{ AuditLog::rotuloCampo($campo) }}:</strong>
                                            <span class="text-decoration-line-through text-muted">{{ AuditLog::formatarValor($registro->valores_antigos[$campo] ?? null) }}</span>
                                            → {{ AuditLog::formatarValor($novo) }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
