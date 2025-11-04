// language: javascript
// Dane atmosferyczne: 3 wykresy świeczkowe (temperatura, ciśnienie SLP, wilgotność)
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

    const pad = (n) => String(n).padStart(2, '0');
    const toMonthStr = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;
    const isValidMonthStr = (s) => /^\d{4}-\d{2}$/.test(s);

    const fetchAtmosphereCandles = async (monthStr) => {
        const res = await fetch(`/weather/get-atmosphere-monthly-candles?date=${encodeURIComponent(monthStr)}`, { cache: 'no-store' });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return await res.json();
    };

    const renderSingleCandle = (el, chartRef, name, data, yTitle, height = 300) => {
        const hasData = Array.isArray(data) && data.length > 0;
        if (!hasData) {
            if (chartRef.current) { chartRef.current.destroy(); chartRef.current = null; }
            el.innerHTML = '<div class="text-center p-4">Brak danych miesięcznych.</div>';
            return;
        }
        const options = {
            chart: { type: 'candlestick', height, toolbar: { show: false } },
            series: [{ name, type: 'candlestick', data }],
            xaxis: { type: 'datetime', labels: { format: 'dd MMM', datetimeUTC: false } },
            yaxis: { tooltip: { enabled: true }, title: { text: yTitle } },
            plotOptions: { candlestick: { wick: { useFillColor: true } } },
            legend: { show: false },
            grid: { strokeDashArray: 4 }
        };
        el.innerHTML = '';
        if (!chartRef.current) { chartRef.current = new ApexCharts(el, options); chartRef.current.render(); }
        else { chartRef.current.updateOptions(options, true, true); }
    };

    const loadWeather = async (monthStr) => {
        try {
            elTemp.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
            elPress.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
            elHum.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';

            const raw = await fetchAtmosphereCandles(monthStr);

            renderSingleCandle(elTemp, { get current() { return tempChart; }, set current(v) { tempChart = v; } }, 'Temperatura', raw?.temperature || [], '°C');
            renderSingleCandle(elPress, { get current() { return pressChart; }, set current(v) { pressChart = v; } }, 'Ciśnienie (SLP)', raw?.seaLevelPressure || [], 'hPa');
            renderSingleCandle(elHum, { get current() { return humChart; }, set current(v) { humChart = v; } }, 'Wilgotność', raw?.humidity || [], '%');
        } catch {
            elTemp.innerHTML = '<div class="text-center p-4">Błąd ładowania.</div>';
            elPress.innerHTML = '<div class="text-center p-4">Błąd ładowania.</div>';
            elHum.innerHTML = '<div class="text-center p-4">Błąd ładowania.</div>';
        }
    };

    // Start – z URL lub data-role-date
    const urlDate = new URL(window.location.href).searchParams.get('date');
    let initial = '';
    if (isValidMonthStr(urlDate)) initial = urlDate;
    else {
        const holder = document.querySelector('[data-role-date]');
        const raw = holder ? (holder.getAttribute('data-role-date') || '').trim() : '';
        const m = raw.match(/^(\d{4}-\d{2})/);
        initial = m ? m[1] : toMonthStr(new Date());
    }
    loadWeather(initial);

    // Reakcja na zmianę miesiąca z drugiego skryptu (strzałki/zmiana inputu)
    window.addEventListener('weather:monthChanged', (e) => {
        if (isValidMonthStr(e.detail)) {
            loadWeather(e.detail);
        }
    });

    // Gdy użytkownik ręcznie wybierze miesiąc – odpal również lokalnie (na wypadek braku eventu)
    dateInput.addEventListener('change', (e) => {
        const val = e.target.value;
        if (isValidMonthStr(val)) loadWeather(val);
    });
});
