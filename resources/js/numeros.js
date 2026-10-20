// Conversão e formatação de números no padrão brasileiro (espelha App\Support\Dinheiro).

const moedaBR = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

export function paraNumero(texto) {
    if (texto === null || texto === undefined) {
        return null;
    }

    let valor = String(texto).replace(/R\$|\s| |%/g, '').trim();

    if (valor === '') {
        return null;
    }

    if (valor.includes(',')) {
        valor = valor.replace(/\./g, '').replace(',', '.');
    } else if (/^\d{1,3}(\.\d{3})+$/.test(valor)) {
        valor = valor.replace(/\./g, '');
    }

    const numero = Number(valor);

    return Number.isFinite(numero) ? numero : null;
}

export function formatarMoeda(numero) {
    return numero === null || Number.isNaN(numero) ? '—' : moedaBR.format(numero);
}

export function formatarDecimal(numero, casas = 2) {
    return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas }).format(numero);
}

export function arredondar(numero) {
    return Math.round((numero + Number.EPSILON) * 100) / 100;
}

export function moedaCompacta(numero) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', notation: 'compact', maximumFractionDigits: 1 }).format(numero);
}
