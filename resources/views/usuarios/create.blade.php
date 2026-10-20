@extends('layouts.app')

@section('titulo', 'Novo usuário')

@section('conteudo')
    <x-cabecalho titulo="Novo usuário">
        <a href="{{ route('usuarios.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </x-cabecalho>

    <div class="row"><div class="col-xl-9">@include('usuarios._form')</div></div>
@endsection
