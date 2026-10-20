import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import { formatarMoeda, moedaCompacta } from './numeros';

Chart.register(
    ArcElement, BarController, BarElement, CategoryScale, DoughnutController, Legend,
    LinearScale, LineController, LineElement, PointElement, Tooltip,
);

const estilo = getComputedStyle(document.documentElement);
const cor = (variavel, padrao) => estilo.getPropertyValue(variavel).trim() || padrao;

Chart.defaults.font.family = '"Inter Variable", system-ui, sans-serif';
Chart.defaults.font.size = 12;
Chart.defaults.color = '#6b6878';
Chart.defaults.plugins.tooltip.padding = 10;
Chart.defaults.plugins.tooltip.cornerRadius = 8;
Chart.defaults.plugins.tooltip.backgroundColor = '#1f1b24';

function lerDados(id) {
    const elemento = document.getElementById(id);

    return elemento ? JSON.parse(elemento.textContent) : null;
}

function graficoFluxo(canvas) {
    const dados = lerDados(canvas.dataset.fonte);

    if (!dados) {
        return;
    }

    new Chart(canvas, {
        data: {
            labels: dados.rotulos,
            datasets: [
                {
                    type: 'bar',
                    label: 'Entradas',
                    data: dados.entradas,
                    backgroundColor: cor('--bs-success', '#1e9e6a'),
                    borderRadius: 6,
                    maxBarThickness: 26,
                    order: 2,
                },
                {
                    type: 'bar',
                    label: 'Saídas',
                    data: dados.saidas,
                    backgroundColor: cor('--bs-danger', '#d6455d'),
                    borderRadius: 6,
                    maxBarThickness: 26,
                    order: 3,
                },
                {
                    type: 'line',
                    label: 'Lucro / saldo',
                    data: dados.lucro,
                    borderColor: cor('--bs-primary', '#7b3f6e'),
                    backgroundColor: cor('--bs-primary', '#7b3f6e'),
                    borderWidth: 2.5,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    order: 1,
                },
            ],
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 18 } },
                tooltip: { callbacks: { label: (c) => ` ${c.dataset.label}: ${formatarMoeda(c.parsed.y)}` } },
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    grid: { color: '#efedf2' },
                    border: { display: false },
                    ticks: { callback: (v) => moedaCompacta(v) },
                },
            },
        },
    });
}

function graficoCategorias(canvas) {
    const dados = lerDados(canvas.dataset.fonte);

    if (!dados || !dados.valores.length) {
        return;
    }

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: dados.rotulos,
            datasets: [{ data: dados.valores, backgroundColor: dados.cores, borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }],
        },
        options: {
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (c) => {
                            const total = c.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? ((c.parsed / total) * 100).toFixed(1).replace('.', ',') : '0';

                            return ` ${formatarMoeda(c.parsed)} (${pct}%)`;
                        },
                    },
                },
            },
        },
    });
}

export function iniciarGraficos() {
    document.querySelectorAll('[data-grafico="fluxo"]').forEach(graficoFluxo);
    document.querySelectorAll('[data-grafico="categorias"]').forEach(graficoCategorias);
}
