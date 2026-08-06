import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend);

// Última janela de snapshots (server_metric_snapshots, populado a cada
// heartbeat de 60s — ver AgentWebhookController::heartbeat()) embutida
// direto na página via data-attribute — sem endpoint AJAX no v1, é só
// o que já veio renderizado, mesmo espírito de simplicidade do resto
// do painel.
function parseSnapshots(el) {
    try {
        return JSON.parse(el.dataset.snapshots || '[]');
    } catch (e) {
        return [];
    }
}

function formatLabel(isoString) {
    const date = new Date(isoString);

    return date.toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
}

// Até ~1440 pontos em 24h (1 a cada 60s) — com o raio de ponto padrão
// do Chart.js, isso enche a tela de bolinha e engole a linha fina por
// baixo (o que o usuário via não era "gráfico feio", era literalmente
// isso: pontos demais, sem linha visível entre eles). Sem ponto nenhum
// visível em repouso (só ao passar o mouse) resolve pra qualquer
// densidade, e o eixo X limita a quantidade de rótulos pra não
// empilhar texto ilegível.
const sharedOptions = {
    responsive: true,
    interaction: { mode: 'index', intersect: false },
    elements: {
        point: { radius: 0, hitRadius: 6, hoverRadius: 4 },
        line: { borderWidth: 2, tension: 0.25 },
    },
    scales: {
        x: { ticks: { autoSkip: true, maxTicksLimit: 8, maxRotation: 0 } },
    },
};

const percentCanvas = document.getElementById('server-metrics-percent-chart');

if (percentCanvas) {
    const snapshots = parseSnapshots(percentCanvas);

    new Chart(percentCanvas, {
        type: 'line',
        data: {
            labels: snapshots.map((s) => formatLabel(s.recorded_at)),
            datasets: [
                {
                    label: 'Disco (%)',
                    data: snapshots.map((s) => s.disk_percent),
                    borderColor: '#0d6efd',
                },
                {
                    label: 'RAM (%)',
                    data: snapshots.map((s) => s.mem_percent),
                    borderColor: '#dc3545',
                },
            ],
        },
        options: {
            ...sharedOptions,
            scales: {
                ...sharedOptions.scales,
                y: { min: 0, max: 100 },
            },
        },
    });
}

const loadCanvas = document.getElementById('server-metrics-load-chart');

if (loadCanvas) {
    const snapshots = parseSnapshots(loadCanvas);

    new Chart(loadCanvas, {
        type: 'line',
        data: {
            labels: snapshots.map((s) => formatLabel(s.recorded_at)),
            datasets: [
                {
                    label: 'Load average',
                    data: snapshots.map((s) => s.load_avg),
                    borderColor: '#198754',
                },
            ],
        },
        options: {
            ...sharedOptions,
            scales: {
                ...sharedOptions.scales,
                y: { min: 0 },
            },
        },
    });
}
