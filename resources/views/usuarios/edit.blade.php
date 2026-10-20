@extends('layouts.app')

@section('titulo', 'Editar usuário')

@section('conteudo')
    <x-cabecalho titulo="Editar usuário" :subtitulo="$usuario->email">
        <a href="{{ route('usuarios.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </x-cabecalho>

    <div class="row"><div class="col-xl-9">@include('usuarios._form')</div></div>
@endsection
