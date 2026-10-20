@extends('layouts.app')

@section('titulo', 'Editar categoria')

@section('conteudo')
    <x-cabecalho titulo="Editar categoria" :subtitulo="$categoria->nome">
        <a href="{{ route('categorias.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </x-cabecalho>

    <div class="row"><div class="col-xl-8">@include('categorias._form')</div></div>
@endsection
