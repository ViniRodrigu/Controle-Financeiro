@extends('layouts.app')

@section('titulo', 'Taxas da maquininha')

@use('App\Enums\FormaPagamento')

@section('conteudo')
    <x-cabecalho titulo="Taxas da maquininha" subtitulo="Usadas para sugerir a taxa e calcular o líquido das entradas">
        <a href="{{ route('taxas.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nova taxa</a>
    </x-cabecalho>

    <div class="alert alert-info suave-info d-flex gap-2 small">
        <i class="bi bi-info-circle-fill"></i>
        <div>
            Taxas cadastradas a partir da planilha (Mastercard). Outras bandeiras podem ter tarifas diferentes.
            Alterar uma taxa não muda lançamentos já registrados — eles guardam a taxa da época.
            Taxa em branco significa “não calcular até preencher”.
        </div>
    </div>

    <div class="row g-4">
        @forelse ($porForma as $forma => $taxas)
            <div class="col-xl-6">
                <div class="card table-card">
                    <div class="card-header"><i class="bi {{ FormaPagamento::from($forma)->icone() }} me-1"></i>{{ FormaPagamento::from($forma)->rotulo() }}</div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Parcelas</th><th class="text-end">Taxa</th><th>Observação</th><th></th></tr></thead>
                            <tbody>
                            @foreach ($taxas as $taxa)
                                <tr>
                                    <td class="tabular">{{ $taxa->parcelas }}x</td>
                                    <td class="text-end tabular fw-semibold">
                                        @if ($taxa->taxa_percentual === null)
                                            <span class="badge suave-warning">não informada</span>
                                        @else
                                            {{ percentual($taxa->taxa_percentual, 2) }}
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $taxa->observacao }}</td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('taxas.edit', $taxa) }}" class="btn btn-sm btn-light" title="Editar"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="{{ route('taxas.destroy', $taxa) }}" class="d-inline"
                                              data-confirmar="Excluir a taxa de {{ $taxa->parcelas }}x?" data-confirmar-botao="Excluir">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light" title="Excluir"><i class="bi bi-trash3 text-danger"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card"><x-vazio icone="bi-percent" mensagem="Nenhuma taxa cadastrada." /></div></div>
        @endforelse
    </div>
@endsection
