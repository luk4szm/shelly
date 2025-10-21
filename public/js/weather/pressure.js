// pressure_chart.js
document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) return;

    const el = document.getElementById('chart-pressure');
    if (!el) return;

    let chart = null;

    const todayDateStr = () => {
        const d = new Date();
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    };

    const fetchData = async () => {
        const dateStr = todayDateStr();
        try {
            const res = await fetch(`/weather/get-pressure?date=${encodeURIComponent(dateStr)}`, {cache: 'no-store'});
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return await res.json();
        } catch (e) {
            console.error('Błąd pobierania ciśnienia:', e);
            return null;
        }
    };

    const transform = (raw) => {
        if (!Array.isArray(raw)) return {series: []};
        const data = raw
            .map(r => {
                const t = new Date(r.measuredAt).getTime();
                const v = typeof r.value === 'number' ? r.value : null;
                return Number.isNaN(t) || v == null ? null : {x: t, y: v};
            })
            .filter(Boolean)
            .sort((a, b) => a.x - b.x);
        return {series: [{name: 'Ciśnienie', data}]};
    };

    const render = ({series}) => {
        const hasData = series.some(s => s.data.length > 0);
        if (!hasData) {
            if (chart) {
                chart.destroy();
                chart = null;
            }
            el.innerHTML = '<div class="text-center p-4">Brak danych.</div>';
            return;
        }

        const options = {
            chart: {type: 'area', height: 300, toolbar: {show: false}, animations: {enabled: true}},
            series,
            stroke: {curve: 'smooth', width: 2},
            fill: {
                type: 'gradient',
                gradient: {shadeIntensity: 0.3, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 50, 100]}
            },
            colors: ['#7463f0'],
            dataLabels: {enabled: false},
            markers: {size: 0, hover: {sizeOffset: 2}},
            tooltip: {
                theme: 'dark',
                x: {format: 'dd MMM, HH:mm'},
                y: {formatter: v => (v == null ? '' : `${v.toFixed(1)} hPa`)}
            },
            xaxis: {type: 'datetime', labels: {format: 'HH:mm', datetimeUTC: false}},
            yaxis: {
                forceNiceScale: true,
                labels: {formatter: v => (v == null ? '' : `${v.toFixed(0)}`)},
                title: {text: 'hPa'}
            },
            grid: {strokeDashArray: 4},
            legend: {show: false}
        };

        el.innerHTML = '';
        if (!chart) {
            chart = new ApexCharts(el, options);
            chart.render();
        } else {
            chart.updateOptions(options, true, true);
        }
    };

    (async () => {
        el.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
        const raw = await fetchData();
        render(transform(raw || []));
    })();
});
