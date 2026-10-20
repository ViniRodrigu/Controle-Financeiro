@extends('layouts.app')

@section('titulo', 'Nova taxa')

@section('conteudo')
    <x-cabecalho titulo="Nova taxa da maquininha">
        <a href="{{ route('taxas.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </x-cabecalho>

    <div class="row"><div class="col-xl-8">@include('taxas._form')</div></div>
@endsection
