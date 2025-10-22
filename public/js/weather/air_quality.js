/**
 * Jeden kontroler obsługujący oba wykresy: jakość powietrza i pogodę.
 * Zapobiega podwójnej zmianie daty przez współdzielone przyciski.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) {
        console.error('Brak ApexCharts.');
        return;
    }

    // Wspólne elementy sterujące datą
    const dateInput = document.getElementById('wheater_date');
    const prevBtn = document.getElementById('prev-day-btn');
    const nextBtn = document.getElementById('next-day-btn');

    // Kontenery wykresów (mogą istnieć jeden lub oba)
    const elAir = document.getElementById('chart-air-quality');
    const elWeather = document.getElementById('chart-weather');

    if (!dateInput || !prevBtn || !nextBtn || (!elAir && !elWeather)) {
        // Brak wymaganych elementów na stronie – nic nie robimy
        return;
    }

    // Instancje wykresów
    let airChart = null;
    let weatherChart = null;

    // --- Pomocnicze dla sterowania datą ---
    const formatDate = (date) => date.toISOString().split('T')[0];

    const updateNextButtonState = () => {
        const today = new Date();
        const currentDate = new Date(dateInput.value);
        today.setHours(0, 0, 0, 0);
        currentDate.setHours(0, 0, 0, 0);
        nextBtn.disabled = currentDate >= today;
    };

    const changeDate = (days) => {
        if (!dateInput.value) return;
        const currentDate = new Date(dateInput.value);
        currentDate.setDate(currentDate.getDate() + days);
        dateInput.value = formatDate(currentDate);
        updateNextButtonState();
        // Kluczowe: tylko jeden listener zmienia dane (poniżej), unikamy wielokrotnych handlerów w wielu plikach
        loadAll(dateInput.value);
    };

    // --- API: pobieranie danych ---
    const fetchAirQuality = async (dateStr) => {
        try {
            if (elAir) elAir.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
            const res = await fetch(`/weather/get-air-quality?date=${encodeURIComponent(dateStr)}`, { cache: 'no-store' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return await res.json();
        } catch (e) {
            console.error('Błąd pobierania jakości powietrza:', e);
            return null;
        }
    };

    const fetchWeather = async (dateStr) => {
        try {
            if (elWeather) elWeather.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
            const res = await fetch(`/weather/get-weather-data?date=${encodeURIComponent(dateStr)}`, { cache: 'no-store' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return await res.json();
        } catch (e) {
            console.error('Błąd pobierania danych pogodowych:', e);
            return null;
        }
    };

    // --- Transformacje ---
    const transformAir = (raw) => {
        if (!Array.isArray(raw)) return { series: [] };
        const points = raw
            .map(r => {
                const t = new Date(r.measuredAt).getTime();
                if (Number.isNaN(t)) return null;
                return {
                    x: t,
                    pm25: typeof r.pm25 === 'number' ? r.pm25 : null,
                    pm10: typeof r.pm10 === 'number' ? r.pm10 : null
                };
            })
            .filter(Boolean)
            .sort((a, b) => a.x - b.x);

        const pm25 = points.filter(p => p.pm25 != null).map(p => ({ x: p.x, y: p.pm25 }));
        const pm10 = points.filter(p => p.pm10 != null).map(p => ({ x: p.x, y: p.pm10 }));

        return {
            series: [
                { name: 'PM2.5', data: pm25 },
                { name: 'PM10', data: pm10 }
            ]
        };
    };

    const transformWeather = (raw) => {
        if (!Array.isArray(raw)) return { series: [] };
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

        const pressure = pts.filter(p => p.pressure != null).map(p => ({ x: p.x, y: p.pressure }));
        const temperature = pts.filter(p => p.temperature != null).map(p => ({ x: p.x, y: p.temperature }));
        const humidity = pts.filter(p => p.humidity != null).map(p => ({ x: p.x, y: p.humidity }));

        return {
            series: [
                { name: 'Ciśnienie', data: pressure, type: 'line' },
                { name: 'Temperatura', data: temperature, type: 'area' },
                { name: 'Wilgotność', data: humidity, type: 'area' }
            ]
        };
    };

    // --- Renderery ---
    const renderAir = ({ series }) => {
        if (!elAir) return;
        const hasData = series.some(s => s.data.length > 0);
        if (!hasData) {
            if (airChart) { airChart.destroy(); airChart = null; }
            elAir.innerHTML = '<div class="text-center p-4">Brak danych.</div>';
            return;
        }
        const options = {
            chart: { type: 'area', height: 340, toolbar: { show: false }, animations: { enabled: true } },
            series,
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 0.3, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 50, 100] }
            },
            dataLabels: { enabled: false },
            markers: { size: 0, hover: { sizeOffset: 2 } },
            tooltip: {
                shared: true, intersect: false, theme: 'dark',
                x: { format: 'dd MMM, HH:mm' },
                y: { formatter: v => (v == null ? '' : `${v.toFixed(1)} µg/m³`) }
            },
            xaxis: { type: 'datetime', labels: { format: 'HH:mm', datetimeUTC: false } },
            yaxis: {
                min: 0, forceNiceScale: true,
                labels: { formatter: v => (v == null ? '' : `${v.toFixed(0)}`) },
                title: { text: 'µg/m³' }
            },
            grid: { strokeDashArray: 4 },
            legend: { show: true, position: 'bottom' },
            colors: ['#ff6b6b', '#4dabf7']
        };
        elAir.innerHTML = '';
        if (!airChart) { airChart = new ApexCharts(elAir, options); airChart.render(); }
        else { airChart.updateOptions(options, true, true); }
    };

    const renderWeather = ({ series }) => {
        if (!elWeather) return;
        const hasData = series.some(s => s.data.length > 0);
        if (!hasData) {
            if (weatherChart) { weatherChart.destroy(); weatherChart = null; }
            elWeather.innerHTML = '<div class="text-center p-4">Brak danych.</div>';
            return;
        }
        const options = {
            chart: { height: 300, type: 'line', stacked: false, toolbar: { show: false }, animations: { enabled: true } },
            series,
            colors: ['#7463f0', '#d90f0f', '#4bc0c0'],
            stroke: { curve: 'smooth', width: [2, 2, 2] },
            fill: {
                type: ['solid', 'gradient', 'gradient'],
                gradient: { shadeIntensity: 0.3, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 50, 100] }
            },
            dataLabels: { enabled: false },
            markers: { size: 0, hover: { sizeOffset: 2 } },
            xaxis: { type: 'datetime', labels: { format: 'HH:mm', datetimeUTC: false } },
            yaxis: [
                {
                    seriesName: 'Ciśnienie', opposite: true, title: { text: 'hPa' },
                    labels: { formatter: v => (v == null ? '' : `${v.toFixed(0)}`) }, tooltip: { enabled: true }
                },
                {
                    seriesName: 'Temperatura', opposite: false, title: { text: '°C' },
                    labels: { formatter: v => (v == null ? '' : `${v.toFixed(1)}°C`) }
                },
                {
                    seriesName: 'Wilgotność', opposite: true, title: { text: '%' },
                    labels: { formatter: v => (v == null ? '' : `${v.toFixed(0)}%`) }, min: 0, max: 100
                }
            ],
            grid: { strokeDashArray: 4 },
            legend: { show: true, position: 'bottom' },
            tooltip: {
                shared: true, intersect: false, theme: 'dark',
                x: { format: 'dd MMM, HH:mm' },
                y: [
                    { formatter: v => (v == null ? '' : `${v.toFixed(1)} hPa`) },
                    { formatter: v => (v == null ? '' : `${v.toFixed(1)} °C`) },
                    { formatter: v => (v == null ? '' : `${v.toFixed(0)} %`) }
                ]
            }
        };
        elWeather.innerHTML = '';
        if (!weatherChart) { weatherChart = new ApexCharts(elWeather, options); weatherChart.render(); }
        else { weatherChart.updateOptions(options, true, true); }
    };

    // --- Ładowanie obu wykresów dla danej daty ---
    const loadAll = async (dateStr) => {
        // Równolegle, ale tylko te które istnieją na stronie
        const tasks = [];
        if (elAir) tasks.push(fetchAirQuality(dateStr).then(raw => renderAir(transformAir(raw || []))));
        if (elWeather) tasks.push(fetchWeather(dateStr).then(raw => renderWeather(transformWeather(raw || []))));
        await Promise.allSettled(tasks);
    };

    // --- Podpięcie JEDNEGO zestawu handlerów do wspólnych kontrolek ---
    prevBtn.addEventListener('click', () => changeDate(-1));
    nextBtn.addEventListener('click', () => changeDate(1));
    dateInput.addEventListener('change', (e) => {
        updateNextButtonState();
        loadAll(e.target.value);
    });

    // Inicjalizacja
    updateNextButtonState();
    const startDate = dateInput.value || new Date().toISOString().split('T')[0];
    loadAll(startDate);
});
