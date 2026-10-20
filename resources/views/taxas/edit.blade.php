@extends('layouts.app')

@section('titulo', 'Editar taxa')

@section('conteudo')
    <x-cabecalho titulo="Editar taxa" :subtitulo="$taxa->forma_pagamento->rotulo().' · '.$taxa->parcelas.'x'">
        <a href="{{ route('taxas.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </x-cabecalho>

    <div class="row"><div class="col-xl-8">@include('taxas._form')</div></div>
@endsection
