@props(['icone' => 'bi-inbox', 'mensagem' => 'Nada por aqui ainda.'])

<div class="vazio">
    <i class="bi {{ $icone }}"></i>
    <div>{{ $mensagem }}</div>
    @if ($slot->isNotEmpty())
        <div class="mt-3">{{ $slot }}</div>
    @endif
</div>
