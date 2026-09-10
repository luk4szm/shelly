// language: javascript
// Atmosfera: 3 świeczki; bez ?date= ładuje ostatnie 30 dni; reaguje na weather:monthChanged.
document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) return;

    const dateInput = document.getElementById('wheater_date'); // type="month"
    const elTemp = document.getElementById('chart-weather-temperature');
    const elPress = document.getElementById('chart-weather-pressure');
    const elHum = document.getElementById('chart-weather-humidity');

    if (!dateInput || !elTemp || !elPress || !elHum) return;

    let tempChart = null;
    let pressChart = null;
    let humChart = null;
    let loadId = 0;

    const isValidMonthStr = (s) => /^\d{4}-\d{2}$/.test(s);

    const fetchAtmosphereCandles = async (dateParam) => {
        const url = new URL('/weather/get-atmosphere-monthly-candles', window.location.origin);
        if (dateParam) url.searchParams.set('date', dateParam);
        const res = await fetch(url.toString(), { cache: 'no-store' });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return await res.json();
    };

    const renderSingleCandle = (el, chartRef, name, data, yTitle, dateParam, forecast = [], height = 300) => {
        const hasData = data.length > 0 || forecast.length > 0;

        if (!hasData) {
            if (chartRef.current) { chartRef.current.destroy(); chartRef.current = null; }
            el.innerHTML = '<div class="text-center p-4">Brak danych.</div>';
            return;
        }

        let minX, maxX;
        if (dateParam && /^\d{4}-\d{2}$/.test(dateParam)) {
            const [year, month] = dateParam.split('-').map(Number);
            minX = new Date(year, month - 1, 1).getTime();
            maxX = new Date(year, month, 0, 23, 59, 59).getTime();
        }

        // ApexCharts selects one data format for all series from the first series.
        const series = [{ name: `${name} — pomiary`, type: 'candlestick', data: data.map(([x, y]) => ({ x, y })) }];
        if (forecast.length > 0) {
            series.push({
                name: `${name} — prognoza`,
                type: 'candlestick',
                data: forecast.map(([x, y]) => {
                    const color = y[3] >= y[0] ? 'rgba(0, 183, 70, 0.3)' : 'rgba(239, 64, 60, 0.3)';
                    return { x, y, fillColor: color, strokeColor: color };
                })
            });
        }

        const options = {
            chart: { type: 'candlestick', height, toolbar: { show: false } },
            series,
            // Candle bodies use generated up/down colors, so set their alpha per series.
            fill: { opacity: [1, 0.3] },
            xaxis: {
                type: 'datetime',
                labels: { format: 'dd MMM', datetimeUTC: false },
                min: minX,
                max: maxX
            },
            yaxis: {
                tooltip: { enabled: true },
                title: { text: yTitle },
                labels: {
                    formatter: (val) => Number.isFinite(val) ? Math.round(val).toString() : ''
                }
            },
            plotOptions: { candlestick: { wick: { useFillColor: true } } },
            legend: { show: forecast.length > 0, markers: { fillColors: ['#00B746', 'rgba(0, 183, 70, 0.3)'] } },
            tooltip: { shared: false, x: { format: 'dd MMM yyyy' } },
            grid: { strokeDashArray: 4 }
        };
        if (!chartRef.current) { el.innerHTML = ''; chartRef.current = new ApexCharts(el, options); chartRef.current.render(); }
        else { chartRef.current.updateOptions(options, true, true); }
    };

    const loadWeather = async (dateParam) => {
        const currentLoad = ++loadId;
        try {
            if (!tempChart) elTemp.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
            if (!pressChart) elPress.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
            if (!humChart) elHum.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';

            const raw = await fetchAtmosphereCandles(dateParam || '');
            if (currentLoad !== loadId) return;

            renderSingleCandle(elTemp, { get current() { return tempChart; }, set current(v) { tempChart = v; } }, 'Temperatura', raw?.temperature || [], '°C', dateParam, raw?.forecast?.temperature || []);
            renderSingleCandle(elPress, { get current() { return pressChart; }, set current(v) { pressChart = v; } }, 'Ciśnienie (SLP)', raw?.seaLevelPressure || [], 'hPa', dateParam, raw?.forecast?.seaLevelPressure || []);
            renderSingleCandle(elHum, { get current() { return humChart; }, set current(v) { humChart = v; } }, 'Wilgotność', raw?.humidity || [], '%', dateParam);
        } catch {
            if (currentLoad !== loadId) return;
            [tempChart, pressChart, humChart].forEach(chart => chart?.destroy());
            tempChart = pressChart = humChart = null;
            elTemp.innerHTML = '<div class="text-center p-4">Błąd ładowania.</div>';
            elPress.innerHTML = '<div class="text-center p-4">Błąd ładowania.</div>';
            elHum.innerHTML = '<div class="text-center p-4">Błąd ładowania.</div>';
        }
    };

    // Start – jeśli brak ?date= to przekaż pusty parametr (ostatnie 30 dni)
    const urlDate = new URL(window.location.href).searchParams.get('date');
    loadWeather(isValidMonthStr(urlDate) ? urlDate : '');

    // Reakcja na zmianę miesiąca z air_quality_monthly.js
    window.addEventListener('weather:monthChanged', (e) => {
        const val = e.detail;
        loadWeather(isValidMonthStr(val) ? val : '');
    });

    // Dodatkowa asekuracja na ręczną zmianę inputu
    dateInput.addEventListener('change', (e) => {
        const val = e.target.value;
        loadWeather(isValidMonthStr(val) ? val : '');
    });
});
