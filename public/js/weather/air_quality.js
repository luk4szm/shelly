// language: javascript
/**
 * Kontroler wykresów jakości powietrza i pogody z obsługą daty:
 * - start z data-role-date (YYYY-MM-DD),
 * - aktualizacja ?date= w URL przy każdej zmianie,
 * - pobieranie danych dla wybranej daty,
 * - odświeżanie kart z tej samej strony pod wybraną datę.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) {
        console.error('Brak ApexCharts.');
        return;
    }

    // Elementy sterujące i kontenery wykresów
    const dateInput = document.getElementById('wheater_date');
    const prevBtn = document.getElementById('prev-day-btn');
    const nextBtn = document.getElementById('next-day-btn');
    const elAir = document.getElementById('chart-air-quality');
    const elWeather = document.getElementById('chart-weather');

    if (!dateInput || !prevBtn || !nextBtn || (!elAir && !elWeather)) {
        return;
    }

    // Instancje wykresów
    let airChart = null;
    let weatherChart = null;

    // Narzędzia
    const formatDate = (date) => date.toISOString().split('T')[0];

    const updateNextButtonState = () => {
        const today = new Date();
        const currentDate = new Date(dateInput.value);
        today.setHours(0, 0, 0, 0);
        currentDate.setHours(0, 0, 0, 0);
        nextBtn.disabled = currentDate >= today;
    };

    const setUrlDateParam = (dateStr, replace = false) => {
        const url = new URL(window.location.href);
        if (dateStr) {
            url.searchParams.set('date', dateStr);
        } else {
            url.searchParams.delete('date');
        }
        if (replace) {
            window.history.replaceState({}, '', url.toString());
        } else {
            window.history.pushState({}, '', url.toString());
        }
    };

    const changeDate = (days) => {
        if (!dateInput.value) return;
        const currentDate = new Date(dateInput.value);
        currentDate.setDate(currentDate.getDate() + days);
        const newVal = formatDate(currentDate);
        dateInput.value = newVal;
        setUrlDateParam(newVal);
        updateNextButtonState();
        loadAll(newVal);
    };

    // Odczyt daty ze znacznika data-role-date
    const dateHolder = document.querySelector('[data-role-date]');
    const dateFromDataAttr = dateHolder ? (dateHolder.getAttribute('data-role-date') || '').trim() : '';
    const isValidDateStr = (s) => /^\d{4}-\d{2}-\d{2}$/.test(s);

    // API: pobieranie danych
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

    // Transformacje danych
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

    // Renderery
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
        if (!weatherChart) { weatherChart = new ApexCharts(elWeather, options); weatherChart.render(); weatherChart.hideSeries('Wilgotność'); }
        else { weatherChart.updateOptions(options, true, true); }
    };

    // Odświeżanie kart (zachowuje wybraną datę)
    const reloadAirQualityCards = async () => {
        const container = document.getElementById('air_quality_data_cards');
        if (!container) return;
        container.innerHTML = '<div class="text-center p-3">Ładowanie…</div>';
        try {
            const url = new URL(window.location.href);
            const currentDate = dateInput.value || '';
            if (currentDate) url.searchParams.set('date', currentDate);
            const res = await fetch(url.toString(), { cache: 'no-store' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const html = await res.text();
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            const fresh = tmp.querySelector('#air_quality_data_cards');
            container.innerHTML = fresh ? fresh.innerHTML : '<div class="text-center text-muted p-3">Brak danych.</div>';
        } catch (e) {
            console.error(e);
            container.innerHTML = '<div class="text-center text-muted p-3">Nie udało się załadować.</div>';
        }
    };

    // Ładowanie danych dla danej daty
    const loadAll = async (dateStr) => {
        const tasks = [];
        if (elAir) tasks.push(fetchAirQuality(dateStr).then(raw => renderAir(transformAir(raw || []))));
        if (elWeather) tasks.push(fetchWeather(dateStr).then(raw => renderWeather(transformWeather(raw || []))));
        tasks.push(reloadAirQualityCards());
        await Promise.allSettled(tasks);
    };

    // Handlery
    prevBtn.addEventListener('click', () => changeDate(-1));
    nextBtn.addEventListener('click', () => changeDate(1));
    dateInput.addEventListener('change', (e) => {
        const val = e.target.value;
        if (!isValidDateStr(val)) return;
        setUrlDateParam(val);
        updateNextButtonState();
        loadAll(val);
    });

    // Reakcja na nawigację wstecz/przód (przywróć stan po zmianie historii)
    window.addEventListener('popstate', () => {
        const url = new URL(window.location.href);
        const d = url.searchParams.get('date');
        const dateStr = isValidDateStr(d) ? d : dateInput.value;
        if (isValidDateStr(dateStr)) {
            dateInput.value = dateStr;
            updateNextButtonState();
            loadAll(dateStr);
        }
    });

    // Inicjalizacja
    const urlDate = new URL(window.location.href).searchParams.get('date');
    const initial = isValidDateStr(urlDate)
        ? urlDate
        : (isValidDateStr(dateFromDataAttr) ? dateFromDataAttr : (dateInput.value || formatDate(new Date())));
    dateInput.value = initial;
    setUrlDateParam(initial, true); // zsynchronizuj URL bez dodawania wpisu w historii
    updateNextButtonState();
    loadAll(initial);
});
