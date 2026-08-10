import { Chart, BarController, BarElement, LinearScale, CategoryScale, Tooltip, Legend } from 'chart.js';

Chart.register(BarController, BarElement, LinearScale, CategoryScale, Tooltip, Legend);

function parseStats(el) {
    try {
        return JSON.parse(el.dataset.stats || '[]');
    } catch (e) {
        return [];
    }
}

function formatLabel(isoDate) {
    const date = new Date(isoDate);

    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
}

const canvas = document.getElementById('traffic-chart');

if (canvas) {
    const stats = parseStats(canvas);

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: stats.map((s) => formatLabel(s.date)),
            datasets: [
                {
                    label: 'Hits',
                    data: stats.map((s) => s.hits),
                    backgroundColor: '#0d6efd',
                },
                {
                    label: 'Visitantes únicos',
                    data: stats.map((s) => s.unique_visitors),
                    backgroundColor: '#198754',
                },
            ],
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { ticks: { autoSkip: true, maxTicksLimit: 10, maxRotation: 0 } },
                y: { min: 0 },
            },
        },
    });
}
