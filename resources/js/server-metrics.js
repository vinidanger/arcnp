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
                    tension: 0.2,
                },
                {
                    label: 'RAM (%)',
                    data: snapshots.map((s) => s.mem_percent),
                    borderColor: '#dc3545',
                    tension: 0.2,
                },
            ],
        },
        options: {
            scales: { y: { min: 0, max: 100 } },
            responsive: true,
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
                    tension: 0.2,
                },
            ],
        },
        options: {
            scales: { y: { min: 0 } },
            responsive: true,
        },
    });
}
