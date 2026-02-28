// language: javascript
// Atmosfera: 3 świeczki roczne; bez ?date= ładuje bieżący rok; reaguje na weather:yearChanged.
document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) return;

    const dateInput = document.getElementById('wheater_date'); // type="number" lub "text" dla roku
    const elTemp = document.getElementById('chart-weather-temperature');
    const elPress = document.getElementById('chart-weather-pressure');
    const elHum = document.getElementById('chart-weather-humidity');

    if (!dateInput || !elTemp || !elPress || !elHum) return;

    let tempChart = null;
    let pressChart = null;
    let humChart = null;

    const isValidYearStr = (s) => /^\d{4}$/.test(s);

    const fetchAtmosphereCandles = async (dateParam) => {
        const url = new URL('/weather/get-atmosphere-yearly-candles', window.location.origin);
        if (dateParam) url.searchParams.set('date', dateParam);
        const res = await fetch(url.toString(), { cache: 'no-store' });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return await res.json();
    };

    const renderSingleCandle = (el, chartRef, name, data, yTitle, dateParam, height = 300) => {
        const hasData = Array.isArray(data) && data.length > 0;

        if (!hasData) {
            if (chartRef.current) { chartRef.current.destroy(); chartRef.current = null; }
            el.innerHTML = '<div class="text-center p-4">Brak danych dla wybranego roku.</div>';
            return;
        }

        let minX, maxX;
        if (dateParam && /^\d{4}$/.test(dateParam)) {
            const year = parseInt(dateParam, 10);
            minX = new Date(year, 0, 1).getTime();
            maxX = new Date(year, 11, 31, 23, 59, 59).getTime();
        } else {
            // Precyzyjny zakres ostatnich 12 pełnych miesięcy (zgodnie z logiką PHP)
            const now = new Date();
            const to = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59); // Ostatni dzień bieżącego miesiąca
            const from = new Date(now.getFullYear(), now.getMonth() - 11, 1, 0, 0, 0); // Pierwszy dzień sprzed 11 miesięcy

            minX = from.getTime();
            maxX = to.getTime();
        }

        const options = {
            chart: { type: 'candlestick', height, toolbar: { show: false } },
            series: [{ name, type: 'candlestick', data }],
            xaxis: {
                type: 'datetime',
                labels: {
                    format: 'MMM yyyy', // Dodanie roku do etykiety pomoże odróżnić miesiące z różnych lat
                    datetimeUTC: false
                },
                min: minX,
                max: maxX,
                range: maxX - minX, // WYMUSZA, by widoczny zakres zawsze wynosił dokładnie 12 miesięcy
                tickAmount: 12,
                tickPlacement: 'between'
            },
            yaxis: {
                tooltip: { enabled: true },
                title: { text: yTitle },
                labels: {
                    formatter: (val) => Number.isFinite(val) ? Math.round(val).toString() : ''
                }
            },
            plotOptions: { candlestick: { wick: { useFillColor: true } } },
            legend: { show: false },
            grid: { strokeDashArray: 4 }
        };
        el.innerHTML = '';
        if (!chartRef.current) { chartRef.current = new ApexCharts(el, options); chartRef.current.render(); }
        else { chartRef.current.updateOptions(options, true, true); }
    };

    const loadWeather = async (dateParam) => {
        try {
            elTemp.innerHTML = '<div class="text-center p-4">Ładowanie danych rocznych…</div>';
            elPress.innerHTML = '<div class="text-center p-4">Ładowanie danych rocznych…</div>';
            elHum.innerHTML = '<div class="text-center p-4">Ładowanie danych rocznych…</div>';

            const raw = await fetchAtmosphereCandles(dateParam || '');

            renderSingleCandle(elTemp, { get current() { return tempChart; }, set current(v) { tempChart = v; } }, 'Temperatura', raw?.temperature || [], '°C', dateParam);
            renderSingleCandle(elPress, { get current() { return pressChart; }, set current(v) { pressChart = v; } }, 'Ciśnienie (SLP)', raw?.seaLevelPressure || [], 'hPa', dateParam);
            renderSingleCandle(elHum, { get current() { return humChart; }, set current(v) { humChart = v; } }, 'Wilgotność', raw?.humidity || [], '%', dateParam);
        } catch (err) {
            console.error(err);
            elTemp.innerHTML = '<div class="text-center p-4">Błąd ładowania danych.</div>';
            elPress.innerHTML = '<div class="text-center p-4">Błąd ładowania danych.</div>';
            elHum.innerHTML = '<div class="text-center p-4">Błąd ładowania danych.</div>';
        }
    };

    // Start – pobierz rok z URL lub bieżący
    const urlDate = new URL(window.location.href).searchParams.get('date');
    loadWeather(isValidYearStr(urlDate) ? urlDate : '');

    // Reakcja na zmianę roku (np. z nawigacji)
    window.addEventListener('weather:yearChanged', (e) => {
        const val = e.detail;
        loadWeather(isValidYearStr(val) ? val : '');
    });

    dateInput.addEventListener('change', (e) => {
        const val = e.target.value;
        loadWeather(isValidYearStr(val) ? val : '');
    });
});
