@extends('layouts.app')

@section('titulo', 'Painel')

@use('App\Services\RelatorioFinanceiro')

@php
    $dadosFluxo = [
        'rotulos' => array_map(fn ($m) => mb_substr($m['nome'], 0, 3), $serie['meses']),
        'entradas' => array_column($serie['meses'], 'entradas'),
        'saidas' => array_column($serie['meses'], 'saidas'),
        'lucro' => array_column($serie['meses'], 'lucro'),
    ];

    $dadosDespesas = [
        'rotulos' => $despesasPorCategoria->pluck('nome')->all(),
        'valores' => $despesasPorCategoria->pluck('total')->all(),
        'cores' => $despesasPorCategoria->pluck('cor')->all(),
    ];

    $percentualTaxas = $indicadores['bruto'] > 0 ? $indicadores['taxas'] / $indicadores['bruto'] * 100 : null;
@endphp

@section('conteudo')
    <x-cabecalho titulo="Painel" :subtitulo="$rotuloPeriodo.' · regime de caixa (valores pagos e recebidos)'">
        <form method="GET" action="{{ route('painel') }}" class="d-flex gap-2">
            <select name="mes" class="form-select form-select-sm w-auto" data-auto-enviar aria-label="Mês">
                <option value="0">Ano inteiro</option>
                @foreach (RelatorioFinanceiro::MESES as $numero => $nome)
                    <option value="{{ $numero }}" @selected($mes === $numero)>{{ $nome }}</option>
                @endforeach
            </select>
            <select name="ano" class="form-select form-select-sm w-auto" data-auto-enviar aria-label="Ano">
                @foreach ($anos as $opcao)
                    <option value="{{ $opcao }}" @selected($ano === $opcao)>{{ $opcao }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="btn btn-sm btn-primary">Ver</button></noscript>
        </form>
    </x-cabecalho>

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <x-kpi rotulo="Receita recebida" :valor="moeda($indicadores['receita'])" icone="bi-wallet2" cor="success"
                   :variacao="$variacoes['receita']" :rodape="$variacoes['receita'] !== null ? $rotuloComparacao : 'sem base no período anterior'"
                   ajuda="Entradas recebidas já descontadas as taxas da maquininha: o que de fato entrou no caixa." />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-kpi rotulo="Despesas pagas" :valor="moeda($indicadores['despesas'])" icone="bi-receipt" cor="danger"
                   :variacao="$variacoes['despesas']" :rodape="$variacoes['despesas'] !== null ? $rotuloComparacao : 'sem base no período anterior'" inverter />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-kpi rotulo="Lucro / saldo" :valor="moeda($indicadores['lucro'])" icone="bi-piggy-bank" cor="primary"
                   :variacao="$variacoes['lucro']" :rodape="$variacoes['lucro'] !== null ? $rotuloComparacao : 'sem base no período anterior'"
                   ajuda="Recebimentos menos pagamentos do período. Não é o lucro contábil." />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-kpi rotulo="Margem" :valor="percentual($indicadores['margem'])" icone="bi-graph-up-arrow" cor="info"
                   rodape="lucro ÷ receita recebida" />
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-kpi compacto rotulo="Faturamento bruto" :valor="moeda($indicadores['bruto'])" icone="bi-cash-coin" cor="secondary"
                   :rodape="$indicadores['atendimentos'].' recebimento(s) · ticket médio '.moeda($indicadores['ticket_medio'])" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-kpi compacto rotulo="Taxas de cartão retidas" :valor="moeda($indicadores['taxas'])" icone="bi-credit-card" cor="warning"
                   :rodape="percentual($percentualTaxas).' do faturamento bruto'"
                   ajuda="Informativo: já está descontado da receita. Não lance de novo como despesa." />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-kpi compacto rotulo="A receber no período" :valor="moeda($indicadores['a_receber'])" icone="bi-hourglass-split" cor="success"
                   rodape="entradas pendentes" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-kpi compacto rotulo="A pagar no período" :valor="moeda($indicadores['a_pagar'])" icone="bi-calendar-x" cor="danger"
                   rodape="saídas pendentes" />
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Fluxo de caixa · {{ $ano }}</span>
                    <a href="{{ route('resumo', ['ano' => $ano]) }}" class="small fw-normal">Ver resumo mensal <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    <div class="grafico">
                        <canvas data-grafico="fluxo" data-fonte="dados-fluxo" role="img"
                                aria-label="Gráfico de entradas, saídas e lucro por mês em {{ $ano }}"></canvas>
                    </div>
                    <script type="application/json" id="dados-fluxo">@json($dadosFluxo)</script>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Pendências</span>
                    <a href="{{ route('lancamentos.index', ['status' => 'pendente']) }}" class="small fw-normal">Ver todas</a>
                </div>
                <div class="card-body pb-2">
                    <div class="d-flex gap-3 mb-3 small">
                        <div class="flex-fill rounded-3 p-2 suave-success">
                            <div class="text-muted">A receber</div>
                            <div class="fw-bold tabular">{{ moeda($pendencias['total_receber']) }}</div>
                        </div>
                        <div class="flex-fill rounded-3 p-2 suave-danger">
                            <div class="text-muted">A pagar</div>
                            <div class="fw-bold tabular">{{ moeda($pendencias['total_pagar']) }}</div>
                        </div>
                    </div>

                    @if ($pendencias['vencidos'] > 0)
                        <a href="{{ route('lancamentos.index', ['status' => 'vencido']) }}" class="alert alert-danger suave-danger d-flex align-items-center gap-2 py-2 small">
                            <i class="bi bi-exclamation-circle-fill"></i> {{ $pendencias['vencidos'] }} lançamento(s) vencido(s)
                        </a>
                    @endif

                    <ul class="list-unstyled mb-0">
                        @forelse ($pendencias['itens'] as $item)
                            <li class="d-flex align-items-center gap-2 py-2 border-bottom">
                                <div class="flex-grow-1 min-w-0">
                                    <a href="{{ route('lancamentos.show', $item) }}" class="d-block text-body fw-semibold text-truncate">
                                        {{ $item->descricao ?: $item->categoria->nome }}
                                    </a>
                                    <div class="small text-muted">
                                        <span @class(['text-danger fw-semibold' => $item->estaVencido()])>{{ $item->data->format('d/m/Y') }}</span>
                                        · {{ $item->contato?->nome ?? $item->categoria->nome }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div @class(['tabular fw-semibold small', 'valor-entrada' => $item->ehEntrada(), 'valor-saida' => ! $item->ehEntrada()])>
                                        {{ moeda($item->valor_caixa) }}
                                    </div>
                                    @can('editar')
                                        <button type="button" class="btn btn-link btn-sm p-0 small"
                                                data-baixar="{{ route('lancamentos.baixar', $item) }}"
                                                data-titulo="{{ $item->ehEntrada() ? 'Confirmar recebimento' : 'Confirmar pagamento' }}"
                                                data-descricao="{{ ($item->descricao ?: $item->categoria->nome).' — '.moeda($item->valor_caixa) }}">
                                            {{ $item->ehEntrada() ? 'Receber' : 'Pagar' }}
                                        </button>
                                    @endcan
                                </div>
                            </li>
                        @empty
                            <li><x-vazio icone="bi-check2-all" mensagem="Nenhuma pendência. Tudo em dia!" /></li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header">Para onde vai o dinheiro · despesas por categoria</div>
                <div class="card-body">
                    @if ($despesasPorCategoria->isEmpty())
                        <x-vazio icone="bi-pie-chart" mensagem="Nenhuma despesa paga no período." />
                    @else
                        <div class="row g-4 align-items-center">
                            <div class="col-md-5">
                                <div class="grafico-rosca">
                                    <canvas data-grafico="categorias" data-fonte="dados-despesas" role="img" aria-label="Distribuição das despesas por categoria"></canvas>
                                </div>
                                <script type="application/json" id="dados-despesas">@json($dadosDespesas)</script>
                            </div>
                            <div class="col-md-7">
                                @foreach ($despesasPorCategoria as $linha)
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center small mb-1">
                                            <span class="d-flex align-items-center gap-2">
                                                <span class="ponto-cor" data-cor="{{ $linha->cor }}"></span>
                                                <a href="{{ route('lancamentos.index', ['categoria_id' => $linha->id, 'status' => 'pago']) }}" class="text-body">{{ $linha->nome }}</a>
                                            </span>
                                            <span class="tabular"><strong>{{ moeda($linha->total) }}</strong> <span class="text-muted">· {{ percentual($linha->percentual) }}</span></span>
                                        </div>
                                        <div class="progress progress-fina">
                                            <div class="progress-bar" data-cor="{{ $linha->cor }}" data-largura="{{ $linha->percentual }}"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-5 d-flex flex-column gap-3">
            <div class="card">
                <div class="card-header">Receitas por categoria</div>
                <div class="card-body">
                    @forelse ($receitasPorCategoria as $linha)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="d-flex align-items-center gap-2"><span class="ponto-cor" data-cor="{{ $linha->cor }}"></span>{{ $linha->nome }}</span>
                                <span class="tabular"><strong>{{ moeda($linha->total) }}</strong> <span class="text-muted">· {{ $linha->quantidade }}x</span></span>
                            </div>
                            <div class="progress progress-fina">
                                <div class="progress-bar" data-cor="{{ $linha->cor }}" data-largura="{{ $linha->percentual }}"></div>
                            </div>
                        </div>
                    @empty
                        <x-vazio icone="bi-bar-chart" mensagem="Nenhuma receita recebida no período." />
                    @endforelse
                </div>
            </div>

            <div class="card flex-fill">
                <div class="card-header">Principais pacientes do período</div>
                <ul class="list-group list-group-flush">
                    @forelse ($principaisPacientes as $indice => $paciente)
                        <li class="list-group-item d-flex align-items-center gap-3 px-4">
                            <span class="avatar">{{ $indice + 1 }}</span>
                            <a href="{{ route('contatos.show', $paciente->id) }}" class="flex-grow-1 text-body fw-semibold">{{ $paciente->nome }}</a>
                            <span class="text-end small">
                                <span class="d-block fw-semibold tabular">{{ moeda($paciente->total) }}</span>
                                <span class="text-muted">{{ $paciente->quantidade }} atendimento(s)</span>
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item"><x-vazio icone="bi-people" mensagem="Sem atendimentos com paciente identificado." /></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
