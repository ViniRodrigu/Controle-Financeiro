// Comportamento do formulário de lançamento: categorias por tipo, sugestão de taxa
// da maquininha e prévia do cálculo. O cálculo definitivo é sempre refeito no servidor.
import { arredondar, formatarDecimal, formatarMoeda, paraNumero } from './numeros';

export function iniciarFormularioLancamento() {
    const form = document.querySelector('[data-form-lancamento]');

    if (!form) {
        return;
    }

    const taxas = JSON.parse(document.getElementById('taxas-cartao')?.textContent || '{}');
    const campo = (nome) => form.querySelector(`[name="${nome}"]`);
    const categoria = campo('categoria_id');
    const forma = campo('forma_pagamento');
    const parcelas = campo('parcelas_cartao');
    const taxa = campo('taxa_percentual');
    const valor = campo('valor');
    const liquido = campo('liquido_informado');
    const status = campo('status');
    const repetir = campo('repetir');
    const blocoTaxas = form.querySelector('[data-bloco-taxas]');
    const blocoParcelas = form.querySelector('[data-bloco-parcelas]');
    const blocoRepetir = form.querySelector('[data-bloco-repetir]');
    const rotuloContato = form.querySelector('[data-rotulo-contato]');
    const resumo = (chave) => form.querySelector(`[data-resumo="${chave}"]`);

    // Na edição, a taxa gravada é respeitada; só é sugerida de novo se o usuário mudar forma/parcelas.
    let taxaManual = form.dataset.modo === 'editar' && taxa.value !== '';

    const tipoAtual = () => form.querySelector('[name="tipo"]:checked')?.value;

    function filtrarCategorias() {
        const tipo = tipoAtual();

        categoria.querySelectorAll('option[data-tipo]').forEach((opcao) => {
            opcao.hidden = opcao.dataset.tipo !== tipo;
            opcao.disabled = opcao.hidden;
        });

        categoria.querySelectorAll('optgroup').forEach((grupo) => {
            grupo.hidden = !grupo.querySelector('option:not([hidden])');
        });

        if (categoria.selectedOptions[0]?.hidden) {
            categoria.value = '';
        }

        status.querySelectorAll('option').forEach((opcao) => {
            if (opcao.value === 'pago') {
                opcao.textContent = tipo === 'entrada' ? 'Recebido' : 'Pago';
            }
        });

        if (rotuloContato) {
            rotuloContato.textContent = tipo === 'entrada' ? 'Paciente' : 'Fornecedor';
        }
    }

    function atualizarVisibilidade() {
        const entrada = tipoAtual() === 'entrada';
        blocoTaxas.hidden = !entrada;
        blocoParcelas.hidden = !(entrada && forma.value === 'credito');
    }

    function sugerirTaxa() {
        if (taxaManual || tipoAtual() !== 'entrada') {
            return;
        }

        const chave = `${forma.value}:${forma.value === 'credito' ? (parcelas.value || 1) : 1}`;
        taxa.value = chave in taxas ? formatarDecimal(taxas[chave], 2) : '';
    }

    function atualizarResumo() {
        const bruto = paraNumero(valor.value) ?? 0;
        const percentual = paraNumero(taxa.value);
        const informado = paraNumero(liquido.value);
        let taxaValor = null;
        let liquidoValor = null;

        if (tipoAtual() === 'entrada') {
            if (informado !== null) {
                liquidoValor = informado;
                taxaValor = arredondar(bruto - informado);
            } else if (percentual !== null) {
                taxaValor = arredondar((bruto * percentual) / 100);
                liquidoValor = arredondar(bruto - taxaValor);
            }
        }

        const previsto = percentual !== null ? arredondar(bruto - arredondar((bruto * percentual) / 100)) : null;
        const diferenca = informado !== null && previsto !== null ? arredondar(informado - previsto) : null;

        resumo('bruto').textContent = formatarMoeda(bruto);
        resumo('taxa').textContent = taxaValor === null ? '—' : `− ${formatarMoeda(taxaValor)}`;
        resumo('caixa').textContent = formatarMoeda(liquidoValor ?? bruto);
        resumo('efetiva').textContent = taxaValor !== null && bruto > 0 ? `${formatarDecimal((taxaValor / bruto) * 100, 2)}%` : '—';

        const linhaDiferenca = resumo('diferenca');
        linhaDiferenca.textContent = diferenca === null ? '—' : formatarMoeda(diferenca);
        linhaDiferenca.classList.toggle('text-danger', diferenca !== null && diferenca < 0);
        linhaDiferenca.classList.toggle('text-success', diferenca !== null && diferenca > 0);
    }

    function atualizarRepeticao() {
        if (!repetir || !blocoRepetir) {
            return;
        }

        blocoRepetir.hidden = !repetir.checked;
        blocoRepetir.querySelectorAll('input, select').forEach((el) => {
            el.disabled = !repetir.checked;
        });
    }

    function atualizarTudo() {
        filtrarCategorias();
        atualizarVisibilidade();
        sugerirTaxa();
        atualizarResumo();
    }

    form.querySelectorAll('[name="tipo"]').forEach((radio) => radio.addEventListener('change', atualizarTudo));

    [forma, parcelas].forEach((el) => el.addEventListener('change', () => {
        taxaManual = false;
        atualizarVisibilidade();
        sugerirTaxa();
        atualizarResumo();
    }));

    taxa.addEventListener('input', () => {
        taxaManual = true;
        atualizarResumo();
    });

    [valor, liquido].forEach((el) => el.addEventListener('input', atualizarResumo));
    repetir?.addEventListener('change', atualizarRepeticao);

    filtrarCategorias();
    atualizarVisibilidade();
    if (!taxaManual && taxa.value === '') {
        sugerirTaxa();
    }
    atualizarResumo();
    atualizarRepeticao();
}
