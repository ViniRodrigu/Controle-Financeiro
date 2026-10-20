import '@fontsource-variable/inter';
import 'bootstrap-icons/font/bootstrap-icons.css';
import { Modal, Tooltip } from 'bootstrap';
import { formatarDecimal, paraNumero } from './numeros';
import { iniciarFormularioLancamento } from './lancamento-form';

// Cores e larguras vêm de atributos data-* (a CSP bloqueia style="" inline).
function aplicarEstilosDinamicos(raiz = document) {
    raiz.querySelectorAll('[data-cor]').forEach((el) => {
        if (/^#[0-9a-f]{6}$/i.test(el.dataset.cor)) {
            el.style.backgroundColor = el.dataset.cor;
        }
    });

    raiz.querySelectorAll('[data-largura]').forEach((el) => {
        const largura = Math.max(0, Math.min(100, Number(el.dataset.largura) || 0));
        el.style.width = `${largura}%`;
    });
}

// Formulários com data-confirmar="mensagem" pedem confirmação num modal antes de enviar.
function iniciarConfirmacoes() {
    const elemento = document.getElementById('modalConfirmacao');

    if (!elemento) {
        return;
    }

    const modal = new Modal(elemento);
    const texto = elemento.querySelector('[data-mensagem]');
    const botao = elemento.querySelector('[data-confirmar-acao]');
    let formularioPendente = null;

    document.addEventListener('submit', (evento) => {
        const form = evento.target;

        if (!(form instanceof HTMLFormElement) || !form.dataset.confirmar || form.dataset.confirmado === '1') {
            return;
        }

        evento.preventDefault();
        formularioPendente = form;
        texto.textContent = form.dataset.confirmar;
        botao.textContent = form.dataset.confirmarBotao || 'Confirmar';
        modal.show();
    });

    botao.addEventListener('click', () => {
        if (formularioPendente) {
            formularioPendente.dataset.confirmado = '1';
            formularioPendente.submit();
        }
    });
}

// Botões data-baixar abrem o modal para confirmar pagamento/recebimento.
function iniciarBaixa() {
    const elemento = document.getElementById('modalBaixa');

    if (!elemento) {
        return;
    }

    const modal = new Modal(elemento);
    const form = elemento.querySelector('form');

    document.addEventListener('click', (evento) => {
        const botao = evento.target.closest('[data-baixar]');

        if (!botao) {
            return;
        }

        evento.preventDefault();
        form.action = botao.dataset.baixar;
        elemento.querySelector('[data-baixa-descricao]').textContent = botao.dataset.descricao || '';
        elemento.querySelector('[data-baixa-titulo]').textContent = botao.dataset.titulo || 'Confirmar pagamento';
        form.querySelector('[name="data_pagamento"]').value = new Date().toLocaleDateString('sv-SE');
        modal.show();
    });
}

function iniciarAutoEnvio() {
    document.querySelectorAll('[data-auto-enviar]').forEach((campo) => {
        campo.addEventListener('change', () => campo.form?.requestSubmit());
    });
}

// Campos monetários: aceitam "1234,5" ou "1.234,50" e formatam ao sair do campo.
function iniciarCamposMoeda() {
    document.querySelectorAll('[data-moeda]').forEach((campo) => {
        campo.addEventListener('blur', () => {
            const numero = paraNumero(campo.value);

            if (numero !== null && numero >= 0) {
                campo.value = formatarDecimal(numero, Number(campo.dataset.casas || 2));
                campo.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    });
}

function iniciarImpressao() {
    document.querySelectorAll('[data-imprimir]').forEach((botao) => {
        botao.addEventListener('click', () => window.print());
    });
}

document.addEventListener('DOMContentLoaded', () => {
    aplicarEstilosDinamicos();
    iniciarConfirmacoes();
    iniciarBaixa();
    iniciarAutoEnvio();
    iniciarCamposMoeda();
    iniciarImpressao();
    iniciarFormularioLancamento();

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new Tooltip(el));

    if (document.querySelector('[data-grafico]')) {
        import('./graficos').then(({ iniciarGraficos }) => iniciarGraficos());
    }
});
