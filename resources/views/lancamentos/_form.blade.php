@use('App\Enums\FormaPagamento')
@use('App\Enums\StatusLancamento')
@use('App\Enums\TipoLancamento')

@php
    $editando = $lancamento->exists;
    $tipo = old('tipo', $lancamento->tipo?->value ?? 'entrada');
    $forma = old('forma_pagamento', $lancamento->forma_pagamento?->value ?? 'pix');
    $paraCampo = fn ($valor, int $casas = 2) => $valor === null || $valor === '' ? '' : number_format((float) $valor, $casas, ',', '.');
    $taxaCampo = $lancamento->taxa_percentual !== null ? preg_replace('/0$/', '', number_format((float) $lancamento->taxa_percentual, 3, ',', '')) : '';
@endphp

<form method="POST" action="{{ $editando ? route('lancamentos.update', $lancamento) : route('lancamentos.store') }}"
      data-form-lancamento data-modo="{{ $editando ? 'editar' : 'criar' }}" novalidate>
    @csrf
    @if ($editando)
        @method('PUT')
    @endif

    <script type="application/json" id="taxas-cartao">@json($taxas)</script>

    <div class="row g-4">
        <div class="col-lg-8 d-flex flex-column gap-4">
            <div class="card">
                <div class="card-body p-4">
                    <div class="row g-2 seletor-tipo mb-4" role="radiogroup" aria-label="Tipo de lançamento">
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="tipo" id="tipo_entrada" value="entrada" @checked($tipo === 'entrada')>
                            <label class="btn btn-outline-success w-100" for="tipo_entrada"><i class="bi bi-arrow-down-left me-1"></i>Entrada</label>
                        </div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="tipo" id="tipo_saida" value="saida" @checked($tipo === 'saida')>
                            <label class="btn btn-outline-danger w-100" for="tipo_saida"><i class="bi bi-arrow-up-right me-1"></i>Saída</label>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="data" class="form-label">Data</label>
                            <input type="date" id="data" name="data" required
                                   value="{{ old('data', $lancamento->data?->format('Y-m-d')) }}"
                                   @class(['form-control', 'is-invalid' => $errors->has('data')])>
                            @error('data')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="valor" class="form-label">Valor bruto</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">R$</span>
                                <input type="text" id="valor" name="valor" inputmode="decimal" autocomplete="off" required data-moeda
                                       value="{{ old('valor', $paraCampo($lancamento->valor)) }}" placeholder="0,00"
                                       @class(['form-control tabular', 'is-invalid' => $errors->has('valor')])>
                                @error('valor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" @class(['form-select', 'is-invalid' => $errors->has('status')])>
                                @foreach (StatusLancamento::cases() as $opcao)
                                    <option value="{{ $opcao->value }}" @selected(old('status', $lancamento->status?->value) === $opcao->value)>
                                        {{ $opcao->rotuloPara(TipoLancamento::tryFrom((string) $tipo) ?? TipoLancamento::Entrada) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="categoria_id" class="form-label">Categoria</label>
                            <select id="categoria_id" name="categoria_id" required @class(['form-select', 'is-invalid' => $errors->has('categoria_id')])>
                                <option value="">Selecione…</option>
                                @foreach ($categorias->groupBy(fn ($c) => $c->tipo->value.'|'.$c->grupo) as $grupo)
                                    <optgroup label="{{ $grupo->first()->grupo }}">
                                        @foreach ($grupo as $categoria)
                                            <option value="{{ $categoria->id }}" data-tipo="{{ $categoria->tipo->value }}"
                                                @selected((int) old('categoria_id', $lancamento->categoria_id) === $categoria->id)>
                                                {{ $categoria->nome }}{{ $categoria->ativo ? '' : ' (inativa)' }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('categoria_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="forma_pagamento" class="form-label">Forma de pagamento</label>
                            <select id="forma_pagamento" name="forma_pagamento" @class(['form-select', 'is-invalid' => $errors->has('forma_pagamento')])>
                                @foreach (FormaPagamento::opcoes() as $valor => $rotulo)
                                    <option value="{{ $valor }}" @selected($forma === $valor)>{{ $rotulo }}</option>
                                @endforeach
                            </select>
                            @error('forma_pagamento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="contato_nome" class="form-label"><span data-rotulo-contato>{{ $tipo === 'entrada' ? 'Paciente' : 'Fornecedor' }}</span> <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="text" id="contato_nome" name="contato_nome" list="lista-contatos" maxlength="120" autocomplete="off"
                                   value="{{ old('contato_nome', $lancamento->contato?->nome) }}" placeholder="Digite para buscar ou cadastrar"
                                   @class(['form-control', 'is-invalid' => $errors->has('contato_nome')])>
                            <datalist id="lista-contatos">
                                @foreach ($contatos as $nome)
                                    <option value="{{ $nome }}"></option>
                                @endforeach
                            </datalist>
                            <div class="form-text">Se o nome ainda não existir, o cadastro é criado automaticamente.</div>
                            @error('contato_nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="descricao" class="form-label">Descrição <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="text" id="descricao" name="descricao" maxlength="255"
                                   value="{{ old('descricao', $lancamento->descricao) }}" placeholder="Ex.: Botox e ácido hialurônico"
                                   @class(['form-control', 'is-invalid' => $errors->has('descricao')])>
                            @error('descricao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="observacoes" class="form-label">Observações <span class="text-muted fw-normal">(opcional)</span></label>
                            <textarea id="observacoes" name="observacoes" rows="2" maxlength="2000"
                                      @class(['form-control', 'is-invalid' => $errors->has('observacoes')])>{{ old('observacoes', $lancamento->observacoes) }}</textarea>
                            @error('observacoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" data-bloco-taxas @if ($tipo !== 'entrada') hidden @endif>
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-credit-card text-primary"></i> Taxa da maquininha
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4" data-bloco-parcelas @if ($forma !== 'credito') hidden @endif>
                            <label for="parcelas_cartao" class="form-label">Parcelas no cartão</label>
                            <select id="parcelas_cartao" name="parcelas_cartao" class="form-select">
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" @selected((int) old('parcelas_cartao', $lancamento->parcelas_cartao ?? 1) === $i)>{{ $i }}x</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="taxa_percentual" class="form-label">Taxa (%)</label>
                            <div class="input-group has-validation">
                                <input type="text" id="taxa_percentual" name="taxa_percentual" inputmode="decimal" autocomplete="off"
                                       value="{{ old('taxa_percentual', $taxaCampo) }}" placeholder="Sem taxa"
                                       @class(['form-control tabular', 'is-invalid' => $errors->has('taxa_percentual')])>
                                <span class="input-group-text">%</span>
                                @error('taxa_percentual')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-text">Sugerida pela tabela de taxas; pode ajustar.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="liquido_informado" class="form-label">Líquido recebido <span class="text-muted fw-normal">(opcional)</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">R$</span>
                                <input type="text" id="liquido_informado" name="liquido_informado" inputmode="decimal" autocomplete="off" data-moeda
                                       value="{{ old('liquido_informado', $paraCampo($lancamento->liquido_informado)) }}" placeholder="0,00"
                                       @class(['form-control tabular', 'is-invalid' => $errors->has('liquido_informado')])>
                                @error('liquido_informado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-text">Valor real que caiu na conta. Quando informado, prevalece sobre a taxa.</div>
                        </div>
                    </div>
                </div>
            </div>

            @unless ($editando)
                <div class="card">
                    <div class="card-body p-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="repetir" name="repetir" value="1" @checked(old('repetir'))>
                            <label class="form-check-label fw-semibold" for="repetir">Parcelar ou repetir este lançamento</label>
                            <div class="form-text mt-0">Ex.: compra de material em 3x no cartão, mensalidade da agência, tráfego semanal.</div>
                        </div>

                        <div class="row g-3 mt-1" data-bloco-repetir @unless (old('repetir')) hidden @endunless>
                            <div class="col-md-3">
                                <label for="repetir_quantidade" class="form-label">Quantidade</label>
                                <input type="number" id="repetir_quantidade" name="repetir_quantidade" min="2" max="60"
                                       value="{{ old('repetir_quantidade', 3) }}"
                                       @class(['form-control', 'is-invalid' => $errors->has('repetir_quantidade')])>
                                @error('repetir_quantidade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-5">
                                <label for="repetir_modo" class="form-label">Valor</label>
                                <select id="repetir_modo" name="repetir_modo" class="form-select">
                                    <option value="dividir" @selected(old('repetir_modo', 'dividir') === 'dividir')>Dividir o valor entre as parcelas</option>
                                    <option value="repetir" @selected(old('repetir_modo') === 'repetir')>Repetir o mesmo valor em cada uma</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="repetir_frequencia" class="form-label">Frequência</label>
                                <select id="repetir_frequencia" name="repetir_frequencia" class="form-select">
                                    <option value="mensal" @selected(old('repetir_frequencia', 'mensal') === 'mensal')>Mensal</option>
                                    <option value="semanal" @selected(old('repetir_frequencia') === 'semanal')>Semanal</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-text"><i class="bi bi-info-circle me-1"></i>O primeiro fica com o status escolhido acima; os seguintes são criados como pendentes.</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endunless
        </div>

        <div class="col-lg-4">
            <div class="card resumo-fixo">
                <div class="card-header">Resumo</div>
                <div class="card-body">
                    <dl class="row resumo-calculo mb-3">
                        <dt class="col-7">Valor bruto</dt>
                        <dd class="col-5" data-resumo="bruto">—</dd>
                        <dt class="col-7">Taxa</dt>
                        <dd class="col-5 text-danger" data-resumo="taxa">—</dd>
                        <dt class="col-7">Taxa efetiva</dt>
                        <dd class="col-5" data-resumo="efetiva">—</dd>
                        <dt class="col-7">Diferença vs. previsto</dt>
                        <dd class="col-5" data-resumo="diferenca">—</dd>
                        <dt class="col-5 total">No caixa</dt>
                        <dd class="col-7 total fs-5" data-resumo="caixa">—</dd>
                    </dl>
                    <p class="small text-muted mb-4"><i class="bi bi-shield-check me-1"></i>Prévia. O cálculo é refeito e conferido pelo servidor ao salvar.</p>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>{{ $editando ? 'Salvar alterações' : 'Salvar lançamento' }}</button>
                        @unless ($editando)
                            <button type="submit" name="continuar" value="1" class="btn btn-outline-primary">Salvar e lançar outro</button>
                        @endunless
                        <a href="{{ $editando ? route('lancamentos.show', $lancamento) : route('lancamentos.index') }}" class="btn btn-light">Cancelar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
