@extends('layouts.app')

@section('titulo', 'Novo cadastro')

@section('conteudo')
    <x-cabecalho titulo="Novo paciente ou fornecedor">
        <a href="{{ route('contatos.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </x-cabecalho>

    <div class="row"><div class="col-xl-8">@include('contatos._form')</div></div>
@endsection
