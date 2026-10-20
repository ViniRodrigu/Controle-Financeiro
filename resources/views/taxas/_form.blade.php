@use('App\Enums\FormaPagamento')

<form method="POST" action="{{ $taxa->exists ? route('taxas.update', $taxa) : route('taxas.store') }}" novalidate>
    @csrf
    @if ($taxa->exists)
        @method('PUT')
    @endif

    <div class="card">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="forma_pagamento" class="form-label">Modalidade</label>
                    <select id="forma_pagamento" name="forma_pagamento" @class(['form-select', 'is-invalid' => $errors->has('forma_pagamento')])>
                        @foreach (FormaPagamento::opcoes() as $valor => $rotulo)
                            <option value="{{ $valor }}" @selected(old('forma_pagamento', $taxa->forma_pagamento?->value) === $valor)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                    @error('forma_pagamento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="parcelas" class="form-label">Parcelas</label>
                    <select id="parcelas" name="parcelas" @class(['form-select', 'is-invalid' => $errors->has('parcelas')])>
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" @selected((int) old('parcelas', $taxa->parcelas) === $i)>{{ $i }}x</option>
                        @endfor
                    </select>
                    <div class="form-text">Só o crédito usa parcelas; as demais modalidades são sempre 1x.</div>
                    @error('parcelas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="taxa_percentual" class="form-label">Taxa (%)</label>
                    <div class="input-group has-validation">
                        <input type="text" id="taxa_percentual" name="taxa_percentual" inputmode="decimal" placeholder="Em branco = não informada"
                               value="{{ old('taxa_percentual', $taxa->taxa_percentual !== null ? preg_replace('/0$/', '', number_format((float) $taxa->taxa_percentual, 3, ',', '')) : '') }}"
                               @class(['form-control tabular', 'is-invalid' => $errors->has('taxa_percentual')])>
                        <span class="input-group-text">%</span>
                        @error('taxa_percentual')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="observacao" class="form-label">Observação</label>
                    <input type="text" id="observacao" name="observacao" maxlength="255" value="{{ old('observacao', $taxa->observacao) }}"
                           @class(['form-control', 'is-invalid' => $errors->has('observacao')]) placeholder="Ex.: Mastercard · repasse em 1 dia útil">
                    @error('observacao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2 py-3">
            <a href="{{ route('taxas.index') }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Salvar</button>
        </div>
    </div>
</form>
