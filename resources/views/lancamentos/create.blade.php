@extends('layouts.app')

@section('titulo', 'Novo lançamento')

@section('conteudo')
    <x-cabecalho titulo="Novo lançamento" subtitulo="Registre uma entrada (recebimento) ou saída (despesa)">
        <a href="{{ route('lancamentos.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </x-cabecalho>

    @include('lancamentos._form')
@endsection
