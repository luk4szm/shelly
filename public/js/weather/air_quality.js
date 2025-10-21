document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) {
        console.error('Brak ApexCharts. Upewnij się, że biblioteka jest załadowana.');
        return;
    }

    const el = document.getElementById('chart-air-quality');
    if (!el) {
        console.error('Nie znaleziono elementu #chart-air-quality');
        return;
    }

    let chart = null;

    const todayDateStr = () => {
        const d = new Date();
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    };

    const fetchData = async () => {
        const dateStr = todayDateStr();
        try {
            const res = await fetch(`/weather/get-air-quality?date=${encodeURIComponent(dateStr)}`, {cache: 'no-store'});
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return await res.json();
        } catch (e) {
            console.error('Błąd pobierania danych jakości powietrza:', e);
            return null;
        }
    };

    const lastHours = (data, hours = 8) => {
        const now = Date.now();
        const start = now - hours * 60 * 60 * 1000;
        return data.filter(p => p.x >= start && p.x <= now);
    };

    const transform = (raw) => {
        // Oczekiwany format elementu: { measuredAt: string|Date, pm25: number, pm10: number }
        if (!Array.isArray(raw)) return {series: []};

        const points = raw
            .map(r => {
                const t = new Date(r.measuredAt).getTime();
                if (Number.isNaN(t)) return null;
                const pm25 = typeof r.pm25 === 'number' ? r.pm25 : null;
                const pm10 = typeof r.pm10 === 'number' ? r.pm10 : null;
                return {x: t, pm25, pm10};
            })
            .filter(Boolean)
            .sort((a, b) => a.x - b.x);

        const pm25Series = points.map(p => ({x: p.x, y: p.pm25})).filter(p => p.y != null);
        const pm10Series = points.map(p => ({x: p.x, y: p.pm10})).filter(p => p.y != null);

        return {
            series: [
                {name: 'PM2.5', data: pm25Series},
                {name: 'PM10', data: pm10Series}
            ]
        };
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
            chart: {
                type: 'area',
                height: 340,
                toolbar: {show: false},
                animations: {enabled: true}
            },
            series,
            stroke: {
                curve: 'smooth',
                width: 2
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 0.3,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 50, 100]
                }
            },
            dataLabels: {enabled: false},
            markers: {size: 0, hover: {sizeOffset: 2}},
            tooltip: {
                shared: true,
                intersect: false,
                theme: 'dark',
                x: {format: 'dd MMM, HH:mm'},
                y: {
                    formatter: (v) => (v == null ? '' : `${v.toFixed(1)} µg/m³`)
                }
            },
            xaxis: {
                type: 'datetime',
                labels: {format: 'HH:mm', datetimeUTC: false}
            },
            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: {
                    formatter: (v) => (v == null ? '' : `${v.toFixed(0)}`)
                },
                title: {text: 'µg/m³'}
            },
            grid: {strokeDashArray: 4},
            legend: {show: true, position: 'bottom'},
            colors: ['#ff6b6b', '#4dabf7'] // PM2.5, PM10
        };

        el.innerHTML = '';
        if (!chart) {
            chart = new ApexCharts(el, options);
            chart.render();
        } else {
            chart.updateOptions(options, true, true);
        }
    };

    const load = async () => {
        el.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
        const raw = await fetchData();
        const t = transform(raw || []);
        render(t);
    };

    load();
    // Opcjonalne odświeżanie:
    // setInterval(load, 60000);
});
