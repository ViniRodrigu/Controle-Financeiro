@use('App\Enums\TipoContato')

<form method="POST" action="{{ $contato->exists ? route('contatos.update', $contato) : route('contatos.store') }}" novalidate>
    @csrf
    @if ($contato->exists)
        @method('PUT')
    @endif

    <div class="card">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="nome" class="form-label">Nome</label>
                    <input type="text" id="nome" name="nome" maxlength="120" required value="{{ old('nome', $contato->nome) }}"
                           @class(['form-control', 'is-invalid' => $errors->has('nome')])>
                    @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select id="tipo" name="tipo" @class(['form-select', 'is-invalid' => $errors->has('tipo')])>
                        @foreach (TipoContato::opcoes() as $valor => $rotulo)
                            <option value="{{ $valor }}" @selected(old('tipo', $contato->tipo?->value) === $valor)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                    @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="telefone" class="form-label">Telefone <span class="text-muted fw-normal">(opcional)</span></label>
                    <input type="tel" id="telefone" name="telefone" maxlength="30" value="{{ old('telefone', $contato->telefone) }}" placeholder="(11) 90000-0000"
                           @class(['form-control', 'is-invalid' => $errors->has('telefone')])>
                    @error('telefone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">E-mail <span class="text-muted fw-normal">(opcional)</span></label>
                    <input type="email" id="email" name="email" maxlength="150" value="{{ old('email', $contato->email) }}"
                           @class(['form-control', 'is-invalid' => $errors->has('email')])>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="observacoes" class="form-label">Observações <span class="text-muted fw-normal">(opcional)</span></label>
                    <textarea id="observacoes" name="observacoes" rows="3" maxlength="2000"
                              @class(['form-control', 'is-invalid' => $errors->has('observacoes')])>{{ old('observacoes', $contato->observacoes) }}</textarea>
                    <div class="form-text"><i class="bi bi-shield-lock me-1"></i>LGPD: registre apenas o necessário para o controle financeiro. Não inclua dados de saúde aqui.</div>
                    @error('observacoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="ativo" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" @checked(old('ativo', $contato->ativo))>
                        <label class="form-check-label" for="ativo">Ativo (aparece nas sugestões ao lançar)</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2 py-3">
            <a href="{{ $contato->exists ? route('contatos.show', $contato) : route('contatos.index') }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Salvar</button>
        </div>
    </div>
</form>
