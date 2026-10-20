@use('App\Enums\TipoLancamento')

<form method="POST" action="{{ $categoria->exists ? route('categorias.update', $categoria) : route('categorias.store') }}" novalidate>
    @csrf
    @if ($categoria->exists)
        @method('PUT')
    @endif

    <div class="card">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="nome" class="form-label">Nome</label>
                    <input type="text" id="nome" name="nome" maxlength="80" required value="{{ old('nome', $categoria->nome) }}"
                           @class(['form-control', 'is-invalid' => $errors->has('nome')])>
                    @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select id="tipo" name="tipo" @class(['form-select', 'is-invalid' => $errors->has('tipo')])>
                        @foreach (TipoLancamento::cases() as $tipo)
                            <option value="{{ $tipo->value }}" @selected(old('tipo', $categoria->tipo?->value) === $tipo->value)>{{ $tipo === TipoLancamento::Entrada ? 'Receita (entrada)' : 'Despesa (saída)' }}</option>
                        @endforeach
                    </select>
                    @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label for="grupo" class="form-label">Grupo</label>
                    <input type="text" id="grupo" name="grupo" list="lista-grupos" maxlength="60" required value="{{ old('grupo', $categoria->grupo) }}"
                           @class(['form-control', 'is-invalid' => $errors->has('grupo')]) placeholder="Ex.: Fixos, Variáveis…">
                    <datalist id="lista-grupos">
                        @foreach ($grupos as $grupo)
                            <option value="{{ $grupo }}"></option>
                        @endforeach
                    </datalist>
                    <div class="form-text">Agrupa as categorias no resumo anual.</div>
                    @error('grupo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="cor" class="form-label">Cor nos gráficos</label>
                    <input type="color" id="cor" name="cor" value="{{ old('cor', $categoria->cor) }}"
                           @class(['form-control form-control-color w-100', 'is-invalid' => $errors->has('cor')])>
                    @error('cor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="ativo" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" @checked(old('ativo', $categoria->ativo))>
                        <label class="form-check-label" for="ativo">Ativa (aparece ao registrar lançamentos)</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2 py-3">
            <a href="{{ route('categorias.index') }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Salvar</button>
        </div>
    </div>
</form>
