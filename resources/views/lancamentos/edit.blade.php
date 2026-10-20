@extends('layouts.app')

@section('titulo', 'Editar lançamento')

@section('conteudo')
    <x-cabecalho titulo="Editar lançamento" :subtitulo="'Registrado em '.$lancamento->created_at?->format('d/m/Y H:i')">
        <a href="{{ route('lancamentos.show', $lancamento) }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </x-cabecalho>

    @if ($lancamento->parcela_total)
        <div class="alert alert-info suave-info small">
            <i class="bi bi-info-circle me-1"></i>
            Esta é a parcela {{ $lancamento->parcela_numero }} de {{ $lancamento->parcela_total }}. A alteração vale só para ela.
        </div>
    @endif

    @include('lancamentos._form')
@endsection
