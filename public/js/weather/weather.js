document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) return;

    const el = document.getElementById('chart-weather'); // ustaw swój kontener
    if (!el) return;

    let chart = null;

    const todayDateStr = () => {
        const d = new Date();
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    };

    const fetchData = async () => {
        const dateStr = todayDateStr();
        try {
            const res = await fetch(`/weather/get-weather-data?date=${encodeURIComponent(dateStr)}`, {cache: 'no-store'});
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return await res.json();
        } catch (e) {
            console.error('Błąd pobierania danych atmosferycznych:', e);
            return null;
        }
    };

    const transform = (raw) => {
        if (!Array.isArray(raw)) return {series: []};

        const pts = raw
            .map(r => {
                const t = new Date(r.measuredAt).getTime();
                if (Number.isNaN(t)) return null;
                return {
                    x: t,
                    pressure: typeof r.pressure === 'number' ? r.pressure : null,
                    temperature: typeof r.temperature === 'number' ? r.temperature : null,
                    humidity: typeof r.humidity === 'number' ? r.humidity : null
                };
            })
            .filter(Boolean)
            .sort((a, b) => a.x - b.x);

        const pressure = pts.filter(p => p.pressure != null).map(p => ({x: p.x, y: p.pressure}));
        const temperature = pts.filter(p => p.temperature != null).map(p => ({x: p.x, y: p.temperature}));
        const humidity = pts.filter(p => p.humidity != null).map(p => ({x: p.x, y: p.humidity}));

        return {
            series: [
                {name: 'Ciśnienie', data: pressure, type: 'line'},
                {name: 'Temperatura', data: temperature, type: 'area'},
                {name: 'Wilgotność', data: humidity, type: 'area'}
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
                height: 300,
                type: 'line',
                stacked: false,
                toolbar: {show: false},
                animations: {enabled: true}
            },
            series,
            colors: ['#7463f0', '#d90f0f', '#4bc0c0'], // pressure, temperature, humidity
            stroke: {curve: 'smooth', width: [2, 2, 2]},
            fill: {
                type: ['solid', 'gradient', 'gradient'],
                gradient: {
                    shadeIntensity: 0.3,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 50, 100]
                }
            },
            dataLabels: {enabled: false},
            markers: {size: 0, hover: {sizeOffset: 2}},
            xaxis: {
                type: 'datetime',
                labels: {format: 'HH:mm', datetimeUTC: false}
            },
            yaxis: [
                {
                    seriesName: 'Ciśnienie',
                    opposite: true,
                    title: {text: 'hPa'},
                    labels: {formatter: v => (v == null ? '' : `${v.toFixed(0)}`)},
                    tooltip: {enabled: true}
                },
                {
                    seriesName: 'Temperatura',
                    opposite: false,
                    title: {text: '°C'},
                    labels: {formatter: v => (v == null ? '' : `${v.toFixed(1)}°C`)}
                },
                {
                    seriesName: 'Wilgotność',
                    opposite: true,
                    title: {text: '%'},
                    labels: {formatter: v => (v == null ? '' : `${v.toFixed(0)}%`)},
                    min: 0,
                    max: 100
                }
            ],
            grid: {strokeDashArray: 4},
            legend: {show: true, position: 'bottom'},
            tooltip: {
                shared: true,
                intersect: false,
                theme: 'dark',
                x: {format: 'dd MMM, HH:mm'},
                y: [
                    {formatter: v => (v == null ? '' : `${v.toFixed(1)} hPa`)},
                    {formatter: v => (v == null ? '' : `${v.toFixed(1)} °C`)},
                    {formatter: v => (v == null ? '' : `${v.toFixed(0)} %`)}
                ]
            }
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
