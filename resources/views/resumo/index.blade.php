@extends('layouts.app')

@section('titulo', 'Resumo mensal')

@php
    $abreviados = array_map(fn ($nome) => mb_substr($nome, 0, 3), $meses);
    $celula = fn (float $valor) => $valor == 0 ? '—' : moeda($valor, false);
@endphp

@section('conteudo')
    <x-cabecalho titulo="Resumo mensal" :subtitulo="'Fluxo financeiro de '.$ano.' · regime de caixa'">
        <form method="GET" action="{{ route('resumo') }}">
            <select name="ano" class="form-select w-auto" data-auto-enviar aria-label="Ano de análise">
                @foreach ($anos as $opcao)
                    <option value="{{ $opcao }}" @selected($ano === $opcao)>{{ $opcao }}</option>
                @endforeach
            </select>
        </form>
        <button type="button" class="btn btn-light" data-imprimir><i class="bi bi-printer me-1"></i>Imprimir</button>
    </x-cabecalho>

    <div class="card table-card mb-4">
        <div class="card-header">Mês a mês</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Mês</th>
                    <th class="text-end">Entradas recebidas</th>
                    <th class="text-end">Saídas pagas</th>
                    <th class="text-end">Lucro / saldo</th>
                    <th class="text-end">A receber</th>
                    <th class="text-end">A pagar</th>
                    <th class="text-end">Margem</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($serie['meses'] as $linha)
                    @php $vazio = $linha['entradas'] == 0 && $linha['saidas'] == 0 && $linha['a_receber'] == 0 && $linha['a_pagar'] == 0; @endphp
                    <tr @class(['text-muted' => $vazio])>
                        <td>
                            @if ($vazio)
                                {{ $linha['nome'] }}
                            @else
                                <a href="{{ route('painel', ['ano' => $ano, 'mes' => $linha['mes']]) }}" class="fw-semibold">{{ $linha['nome'] }}</a>
                            @endif
                        </td>
                        <td class="text-end tabular valor-entrada">{{ moeda($linha['entradas']) }}</td>
                        <td class="text-end tabular valor-saida">{{ moeda($linha['saidas']) }}</td>
                        <td @class(['text-end tabular fw-semibold', 'text-danger' => $linha['lucro'] < 0])>{{ moeda($linha['lucro']) }}</td>
                        <td class="text-end tabular">{{ moeda($linha['a_receber']) }}</td>
                        <td class="text-end tabular">{{ moeda($linha['a_pagar']) }}</td>
                        <td class="text-end tabular">{{ percentual($linha['margem']) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="fw-bold">
                <tr class="table-light">
                    <td>Total</td>
                    <td class="text-end tabular valor-entrada">{{ moeda($serie['total']['entradas']) }}</td>
                    <td class="text-end tabular valor-saida">{{ moeda($serie['total']['saidas']) }}</td>
                    <td @class(['text-end tabular', 'text-danger' => $serie['total']['lucro'] < 0])>{{ moeda($serie['total']['lucro']) }}</td>
                    <td class="text-end tabular">{{ moeda($serie['total']['a_receber']) }}</td>
                    <td class="text-end tabular">{{ moeda($serie['total']['a_pagar']) }}</td>
                    <td class="text-end tabular">{{ percentual($serie['total']['margem']) }}</td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Resultado por categoria (R$)</span>
            <span class="small text-muted fw-normal">Somente valores pagos / recebidos</span>
        </div>
        @if (empty($matriz['receitas']) && empty($matriz['despesas']))
            <x-vazio icone="bi-table" :mensagem="'Nenhum valor pago ou recebido em '.$ano.'.'" />
        @else
            <div class="table-responsive">
                <table class="table tabela-matriz align-middle">
                    <thead>
                    <tr>
                        <th>Categoria</th>
                        @foreach ($abreviados as $abreviado)
                            <th>{{ $abreviado }}</th>
                        @endforeach
                        <th>Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="linha-grupo">
                        <td class="valor-entrada"><i class="bi bi-arrow-down-left me-1"></i>Receitas (no caixa)</td>
                        @foreach ($meses as $numero => $_)
                            <td class="valor-entrada">{{ $celula($matriz['totais']['receitas'][$numero]) }}</td>
                        @endforeach
                        <td class="valor-entrada">{{ $celula($matriz['totais']['receitas']['total']) }}</td>
                    </tr>
                    @foreach ($matriz['receitas'] as $linha)
                        <tr>
                            <td class="ps-4"><span class="ponto-cor me-2" data-cor="{{ $linha['cor'] }}"></span>{{ $linha['nome'] }}</td>
                            @foreach ($meses as $numero => $_)
                                <td>{{ $celula($linha['valores'][$numero]) }}</td>
                            @endforeach
                            <td class="fw-semibold">{{ $celula($linha['valores']['total']) }}</td>
                        </tr>
                    @endforeach

                    <tr class="linha-grupo">
                        <td class="valor-saida"><i class="bi bi-arrow-up-right me-1"></i>Despesas</td>
                        @foreach ($meses as $numero => $_)
                            <td class="valor-saida">{{ $celula($matriz['totais']['despesas'][$numero]) }}</td>
                        @endforeach
                        <td class="valor-saida">{{ $celula($matriz['totais']['despesas']['total']) }}</td>
                    </tr>
                    @foreach ($matriz['despesas'] as $grupo => $dadosGrupo)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $grupo }}</td>
                            @foreach ($meses as $numero => $_)
                                <td class="fw-semibold">{{ $celula($dadosGrupo['valores'][$numero]) }}</td>
                            @endforeach
                            <td class="fw-semibold">{{ $celula($dadosGrupo['valores']['total']) }}</td>
                        </tr>
                        @foreach ($dadosGrupo['categorias'] as $linha)
                            <tr class="text-muted">
                                <td class="ps-5"><span class="ponto-cor me-2" data-cor="{{ $linha['cor'] }}"></span>{{ $linha['nome'] }}</td>
                                @foreach ($meses as $numero => $_)
                                    <td>{{ $celula($linha['valores'][$numero]) }}</td>
                                @endforeach
                                <td>{{ $celula($linha['valores']['total']) }}</td>
                            </tr>
                        @endforeach
                    @endforeach

                    <tr class="linha-total">
                        <td>Resultado (lucro / saldo)</td>
                        @foreach ($meses as $numero => $_)
                            <td @class(['text-danger' => $matriz['totais']['resultado'][$numero] < 0])>{{ $celula($matriz['totais']['resultado'][$numero]) }}</td>
                        @endforeach
                        <td @class(['text-danger' => $matriz['totais']['resultado']['total'] < 0])>{{ $celula($matriz['totais']['resultado']['total']) }}</td>
                    </tr>
                    <tr class="text-muted small">
                        <td><i class="bi bi-info-circle me-1"></i>Taxas de cartão retidas (informativo)</td>
                        @foreach ($meses as $numero => $_)
                            <td>{{ $celula($matriz['totais']['taxas'][$numero]) }}</td>
                        @endforeach
                        <td>{{ $celula($matriz['totais']['taxas']['total']) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
