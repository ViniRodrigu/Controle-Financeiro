@props([
    'rotulo',
    'valor',
    'icone' => 'bi-graph-up',
    'cor' => 'primary',
    'variacao' => null,
    'inverter' => false,
    'rodape' => null,
    'compacto' => false,
    'ajuda' => null,
])

@php
    // Para despesas, subir é ruim: "inverter" troca as cores da variação.
    $positivo = $variacao !== null && ($inverter ? $variacao < 0 : $variacao > 0);
@endphp

<div @class(['card kpi', 'kpi-compacto' => $compacto])>
    <div class="card-body">
        <div class="kpi-topo">
            <span class="kpi-rotulo">
                {{ $rotulo }}
                @if ($ajuda)
                    <i class="bi bi-info-circle ms-1" data-bs-toggle="tooltip" data-bs-title="{{ $ajuda }}"></i>
                @endif
            </span>
            <span class="kpi-icone suave-{{ $cor }}"><i class="bi {{ $icone }}"></i></span>
        </div>
        <div class="kpi-valor">{{ $valor }}</div>
        @if ($variacao !== null || $rodape)
            <div class="kpi-rodape">
                @if ($variacao !== null)
                    <span @class(['fw-semibold', 'text-success' => $positivo, 'text-danger' => ! $positivo && $variacao != 0])>
                        <i class="bi {{ $variacao >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short' }}"></i>{{ percentual(abs($variacao)) }}
                    </span>
                @endif
                {{ $rodape }}
            </div>
        @endif
    </div>
</div>
