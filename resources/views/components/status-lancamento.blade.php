@props(['lancamento'])

@if ($lancamento->estaVencido())
    <span class="badge suave-danger"><i class="bi bi-exclamation-circle me-1"></i>Vencido</span>
@elseif ($lancamento->estaPendente())
    <span class="badge suave-warning"><i class="bi bi-clock me-1"></i>Pendente</span>
@else
    <span class="badge suave-success"><i class="bi bi-check2 me-1"></i>{{ $lancamento->status->rotuloPara($lancamento->tipo) }}</span>
@endif
