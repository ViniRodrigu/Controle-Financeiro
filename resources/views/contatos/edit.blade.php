@extends('layouts.app')

@section('titulo', 'Editar cadastro')

@section('conteudo')
    <x-cabecalho titulo="Editar cadastro" :subtitulo="$contato->nome">
        <a href="{{ route('contatos.show', $contato) }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </x-cabecalho>

    <div class="row"><div class="col-xl-8">@include('contatos._form')</div></div>
@endsection
