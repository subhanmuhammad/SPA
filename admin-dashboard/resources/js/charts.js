import { BarController, BarElement, CategoryScale, Chart, LinearScale, Tooltip } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);

const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const compact = new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 });

document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
    const { labels, values, unit } = JSON.parse(document.getElementById(canvas.dataset.chart).textContent);
    const format = unit === 'rupiah' ? (v) => rupiah.format(v) : (v) => `${v} booking`;
    const styles = getComputedStyle(document.documentElement);

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: '#2a78d6',
                hoverBackgroundColor: '#1f5fae',
                borderRadius: { topLeft: 4, topRight: 4 },
                borderSkipped: 'start',
                maxBarThickness: 24,
            }],
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    displayColors: false,
                    callbacks: { label: (ctx) => format(ctx.parsed.y) },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: styles.getPropertyValue('--muted-foreground'), maxRotation: 0, autoSkipPadding: 12 } },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: 'rgba(0, 0, 0, 0.06)' },
                    ticks: { color: styles.getPropertyValue('--muted-foreground'), precision: 0, maxTicksLimit: 5, callback: (v) => (unit === 'rupiah' ? compact.format(v) : v) },
                },
            },
        },
    });
});
