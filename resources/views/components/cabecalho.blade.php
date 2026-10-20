@props(['titulo', 'subtitulo' => null])

<div class="cabecalho-pagina">
    <div>
        <h1>{{ $titulo }}</h1>
        @if ($subtitulo)
            <div class="subtitulo">{{ $subtitulo }}</div>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="d-flex flex-wrap align-items-center gap-2 nao-imprimir">
            {{ $slot }}
        </div>
    @endif
</div>
