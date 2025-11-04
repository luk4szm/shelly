// language: javascript
document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) {
        console.error('Brak ApexCharts.');
        return;
    }

    // Elementy strony (tylko miesięczne)
    const dateInput = document.getElementById('wheater_date'); // type="month", wartość: YYYY-MM
    const prevBtn = document.getElementById('prev-day-btn');
    const nextBtn = document.getElementById('next-day-btn');

    const elAir = document.getElementById('chart-air-quality'); // PM2.5 + PM10
    const elTemp = document.getElementById('chart-weather-temperature');
    const elPress = document.getElementById('chart-weather-pressure');
    const elHum = document.getElementById('chart-weather-humidity');

    if (!dateInput || !prevBtn || !nextBtn || !elAir || !elTemp || !elPress || !elHum) {
        return;
    }

    let airChart = null;
    let tempChart = null;
    let pressChart = null;
    let humChart = null;

    // Helpers dla typu month
    const pad = (n) => String(n).padStart(2, '0');
    const toMonthStr = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;
    const isValidMonthStr = (s) => /^\d{4}-\d{2}$/.test(s);

    const updateNextButtonState = () => {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const current = parseMonth(dateInput.value);
        if (!current) { nextBtn.disabled = false; return; }
        // porównujemy rok-miesiąc
        const lock = new Date(today.getFullYear(), today.getMonth(), 1);
        const curr = new Date(current.getFullYear(), current.getMonth(), 1);
        nextBtn.disabled = curr >= lock;
    };

    const setUrlDateParam = (monthStr, replace = false) => {
        const url = new URL(window.location.href);
        if (monthStr) url.searchParams.set('date', monthStr);
        else url.searchParams.delete('date');
        if (replace) window.history.replaceState({}, '', url.toString());
        else window.history.pushState({}, '', url.toString());
    };

    // Parsowanie YYYY-MM -> Date (1. dzień miesiąca lokalnie)
    function parseMonth(monthStr) {
        if (!isValidMonthStr(monthStr)) return null;
        const [y, m] = monthStr.split('-').map(Number);
        return new Date(y, m - 1, 1);
    }

    // Zmiana o +/- 1 miesiąc
    const changeMonth = (delta) => {
        if (!isValidMonthStr(dateInput.value)) return;
        const d = parseMonth(dateInput.value);
        d.setMonth(d.getMonth() + delta);
        const newVal = toMonthStr(d);
        dateInput.value = newVal;
        setUrlDateParam(newVal);
        updateNextButtonState();
        loadMonthly(newVal);
        reloadAirQualityCards();
    };

    const dateHolder = document.querySelector('[data-role-date]');
    const dateFromDataAttr = dateHolder ? (dateHolder.getAttribute('data-role-date') || '').trim() : '';

    // API monthly
    const fetchMonthly = async (monthStr) => {
        // backend przyjmuje ?date=YYYY-MM (parsuje jako pierwszy dzień miesiąca)
        const res = await fetch(`/weather/get-air-quality-monthly?date=${encodeURIComponent(monthStr)}`, { cache: 'no-store' });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return await res.json();
    };

    // Transformacje monthly (ApexCharts candlestick: [x, [o,h,l,c]])
    const transformMonthly = (raw) => {
        if (!raw || typeof raw !== 'object') {
            return { pm25: [], pm10: [], temperature: [], seaLevelPressure: [], humidity: [] };
        }
        return {
            pm25: Array.isArray(raw.pm25) ? raw.pm25 : [],
            pm10: Array.isArray(raw.pm10) ? raw.pm10 : [],
            temperature: Array.isArray(raw.temperature) ? raw.temperature : [],
            seaLevelPressure: Array.isArray(raw.seaLevelPressure) ? raw.seaLevelPressure : [],
            humidity: Array.isArray(raw.humidity) ? raw.humidity : [],
        };
    };

    // Renderery miesięczne (candlestick)
    const renderAirMonthly = (pm25, pm10) => {
        const series = [
            { name: 'PM2.5', type: 'candlestick', data: pm25 },
            { name: 'PM10',  type: 'candlestick', data: pm10 },
        ];
        const hasData = series.some(s => Array.isArray(s.data) && s.data.length > 0);
        if (!hasData) {
            if (airChart) { airChart.destroy(); airChart = null; }
            elAir.innerHTML = '<div class="text-center p-4">Brak danych miesięcznych.</div>';
            return;
        }
        const options = {
            chart: { type: 'candlestick', height: 355, toolbar: { show: false } },
            series,
            xaxis: { type: 'datetime', labels: { format: 'dd MMM', datetimeUTC: false } },
            yaxis: { tooltip: { enabled: true }, title: { text: 'µg/m³' } },
            plotOptions: { candlestick: { wick: { useFillColor: true } } },
            legend: { show: true, position: 'bottom' },
            colors: ['#ff6b6b', '#4dabf7'],
            grid: { strokeDashArray: 4 }
        };
        elAir.innerHTML = '';
        if (!airChart) { airChart = new ApexCharts(elAir, options); airChart.render(); }
        else { airChart.updateOptions(options, true, true); }
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

    // Odświeżanie kart (sekcja z boxami u góry)
    const reloadAirQualityCards = async () => {
        const container = document.getElementById('air_quality_data_cards');
        if (!container) return;
        container.innerHTML = '<div class="text-center p-3">Ładowanie…</div>';
        try {
            const url = new URL(window.location.href);
            const currentMonth = dateInput.value || '';
            if (currentMonth) url.searchParams.set('date', currentMonth);
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

    const loadMonthly = async (monthStr) => {
        try {
            elAir.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
            elTemp.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
            elPress.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
            elHum.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';

            const raw = await fetchMonthly(monthStr);
            const data = transformMonthly(raw);

            renderAirMonthly(data.pm25, data.pm10);
            renderSingleCandle(elTemp, { get current() { return tempChart; }, set current(v) { tempChart = v; } }, 'Temperatura', data.temperature, '°C');
            renderSingleCandle(elPress, { get current() { return pressChart; }, set current(v) { pressChart = v; } }, 'Ciśnienie (SLP)', data.seaLevelPressure, 'hPa');
            renderSingleCandle(elHum, { get current() { return humChart; }, set current(v) { humChart = v; } }, 'Wilgotność', data.humidity, '%');
        } catch (e) {
            console.error(e);
            elAir.innerHTML = '<div class="text-center p-4">Błąd ładowania.</div>';
            elTemp.innerHTML = '<div class="text-center p-4">Błąd ładowania.</div>';
            elPress.innerHTML = '<div class="text-center p-4">Błąd ładowania.</div>';
            elHum.innerHTML = '<div class="text-center p-4">Błąd ładowania.</div>';
        }
    };

    // Handlery (zmiana miesiąca)
    prevBtn.addEventListener('click', () => changeMonth(-1));
    nextBtn.addEventListener('click', () => changeMonth(1));
    dateInput.addEventListener('change', (e) => {
        const val = e.target.value; // YYYY-MM
        if (!isValidMonthStr(val)) return;
        setUrlDateParam(val);
        updateNextButtonState();
        loadMonthly(val);
        reloadAirQualityCards();
    });

    window.addEventListener('popstate', () => {
        const url = new URL(window.location.href);
        const d = url.searchParams.get('date');
        const monthStr = isValidMonthStr(d) ? d : dateInput.value;
        if (isValidMonthStr(monthStr)) {
            dateInput.value = monthStr;
            updateNextButtonState();
            loadMonthly(monthStr);
            reloadAirQualityCards();
        }
    });

    // Init: preferuj ?date=YYYY-MM, potem data-role-date (YYYY-MM lub YYYY-MM-DD -> obetnij), potem input
    const urlDate = new URL(window.location.href).searchParams.get('date');
    let initial = '';
    if (isValidMonthStr(urlDate)) {
        initial = urlDate;
    } else if (dateFromDataAttr) {
        // jeśli przyszło YYYY-MM-DD, ucinamy do YYYY-MM
        const m = dateFromDataAttr.match(/^(\d{4}-\d{2})/);
        if (m) initial = m[1];
    }
    if (!initial) {
        const now = new Date();
        initial = toMonthStr(now);
    }

    dateInput.value = initial;
    setUrlDateParam(initial, true);
    updateNextButtonState();
    loadMonthly(initial);
});
