// Uproszczony wykres temperatury: okno ostatnich 8 godzin, bez zmiany daty
document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) {
        console.error('Brak ApexCharts. Upewnij się, że biblioteka jest załadowana.');
        return;
    }

    const elChart = document.getElementById('heating_simple_chart');
    if (!elChart) {
        console.error('Nie znaleziono elementu kontenera wykresu #heating_simple_chart.');
        return;
    }

    let chart = null;

    const fetchData = async () => {
        // Pobieramy dane dla dzisiejszej daty (backend zwraca cały dzień)
        const today = new Date();
        const dateStr = today.toISOString().split('T')[0];

        try {
            const res = await fetch(`/heating/get-data/${dateStr}`, { cache: 'no-store' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return await res.json();
        } catch (e) {
            console.error('Błąd pobierania danych:', e);
            return null;
        }
    };

    // Wytnij do ostatnich 8 godzin względem bieżącej chwili
    const last8Hours = (series) => {
        const now = new Date();
        const endTs = now.getTime();
        const startTs = endTs - 8 * 60 * 60 * 1000;

        return series.map(s => ({
            name: s.name,
            data: s.data.filter(p => p.x >= startTs && p.x <= endTs)
        }));
    };

    const transform = (raw) => {
        const current = raw?.currentDay || {};
        const locationColors = raw?.locationColors || {}; // mapa: nazwa lokacji -> kolor z backendu
        const series = [];
        let idx = 0;
        for (const key in current) {
            if (!Object.prototype.hasOwnProperty.call(current, key)) continue;
            const color = locationColors[key] || null;
            series.push({
                name: key,
                data: current[key].map(p => ({ x: new Date(p.datetime).getTime(), y: p.value })),
                _color: color
            });
            idx++;
        }
        // Posortuj punkty i przytnij do 8h
        series.forEach(s => s.data.sort((a, b) => a.x - b.x));
        const sliced = last8Hours(series);
        // użyj kolorów z backendu w kolejności serii; jeśli brak – nie ustawiaj (ApexCharts dobierze domyślne)
        const colors = sliced.map((s, i) => series[i]?._color).filter(c => !!c);
        return { series: sliced, colors: colors.length ? colors : undefined };
    };

    const render = ({ series, colors }) => {
        const options = {
            chart: {
                type: 'line',
                height: 340,
                animations: { enabled: true },
                toolbar: { show: false }
            },
            stroke: {
                width: 2,
                curve: 'smooth',
                lineCap: 'round'
            },
            series,
            ...(colors ? { colors } : {}),
            dataLabels: { enabled: false },
            tooltip: { theme: 'dark', x: { format: 'dd MMM, HH:mm' } },
            xaxis: {
                type: 'datetime',
                labels: { format: 'HH:mm', datetimeUTC: false }
            },
            yaxis: {
                labels: {
                    formatter: (v) => (v == null ? '' : v.toFixed(1) + '°C')
                }
            },
            grid: {
                strokeDashArray: 4
            },
            legend: {
                show: true,
                position: 'bottom'
            },
            markers: { size: 0, hover: { sizeOffset: 2 } }
        };

        elChart.innerHTML = '';

        if (chart === null) {
            chart = new ApexCharts(elChart, options);
            chart.render();
        } else {
            chart.updateOptions(options, true, true);
        }
    };

    const load = async () => {
        elChart.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
        const data = await fetchData();
        if (!data || !data.currentDay || Object.keys(data.currentDay).length === 0) {
            if (chart) {
                chart.destroy();
                chart = null;
            }
            elChart.innerHTML = '<div class="text-center p-4">Brak danych.</div>';
            return;
        }
        const t = transform(data);
        render(t);
    };

    // Pierwsze ładowanie; można dodać cykliczne odświeżanie jeśli potrzebne
    load();
    // setInterval(load, 60000);
});
