// language: javascript
// PM2.5/PM10: widok roczny, średnia krocząca; bez ?date= ładuje bieżący rok.
document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) return;

    const dateInput = document.getElementById('wheater_date'); // oczekuje roku YYYY
    const prevBtn = document.getElementById('prev-day-btn');
    const nextBtn = document.getElementById('next-day-btn');
    const elAir = document.getElementById('chart-air-quality');

    if (!dateInput || !prevBtn || !nextBtn || !elAir) return;

    let airChart = null;

    const isValidYearStr = (s) => /^\d{4}$/.test(s);

    const updateNextButtonState = () => {
        const currentYear = parseInt(dateInput.value, 10);
        const maxYear = new Date().getFullYear();
        nextBtn.disabled = currentYear >= maxYear;
    };

    const setUrlDateParam = (yearStr, replace = false) => {
        const url = new URL(window.location.href);
        if (yearStr) url.searchParams.set('date', yearStr);
        else url.searchParams.delete('date');
        if (replace) window.history.replaceState({}, '', url.toString());
        else window.history.pushState({}, '', url.toString());
    };

    const dispatchYearChanged = (yearStr) => {
        window.dispatchEvent(new CustomEvent('weather:yearChanged', { detail: yearStr }));
    };

    const changeYear = (delta) => {
        let y = parseInt(dateInput.value, 10);
        if (isNaN(y)) y = new Date().getFullYear();
        const newVal = (y + delta).toString();

        dateInput.value = newVal;
        setUrlDateParam(newVal);
        updateNextButtonState();
        loadAir(newVal);
        reloadCards();
        dispatchYearChanged(newVal);
    };

    const fetchAirQualityYearlyAvg = async (dateParam) => {
        const url = new URL('/weather/get-air-quality-yearly-avg', window.location.origin);
        if (dateParam) url.searchParams.set('date', dateParam);
        const res = await fetch(url.toString(), { cache: 'no-store' });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return await res.json();
    };

    const movingAverage = (arr, key, windowSize = 3) => {
        const out = [];
        for (let i = 0; i < arr.length; i++) {
            const from = Math.max(0, i - windowSize + 1);
            const slice = arr.slice(from, i + 1).map(p => p[key]).filter(v => typeof v === 'number');
            const avg = slice.length ? slice.reduce((a, b) => a + b, 0) / slice.length : null;
            out.push(avg);
        }
        return out;
    };

    const transformAir = (rows) => {
        if (!Array.isArray(rows)) return { series: [] };
        // Sortujemy po dacie, bo x to teraz string "Y-m-d" z PHP
        const points = rows.map(r => ({
            x: new Date(r.x).getTime(),
            pm25: r.pm25,
            pm10: r.pm10
        })).sort((a, b) => a.x - b.x);

        const pm25ma = movingAverage(points, 'pm25', 7); // Większe okno dla widoku rocznego
        const pm10ma = movingAverage(points, 'pm10', 7);

        return {
            series: [
                { name: 'PM2.5 (Śr. krocząca)', data: points.map((p, i) => ({ x: p.x, y: pm25ma[i] })), type: 'line' },
                { name: 'PM10 (Śr. krocząca)', data: points.map((p, i) => ({ x: p.x, y: pm10ma[i] })), type: 'line' },
            ]
        };
    };

    const renderAir = ({series}, dateParam) => {
        const hasData = series.some(s => s.data.some(p => p.y != null));
        if (!hasData) {
            if (airChart) { airChart.destroy(); airChart = null; }
            elAir.innerHTML = '<div class="text-center p-4">Brak danych dla wybranego roku.</div>';
            return;
        }

        let minX, maxX;
        if (isValidYearStr(dateParam)) {
            const year = parseInt(dateParam, 10);
            minX = new Date(year, 0, 1).getTime();
            maxX = new Date(year, 11, 31, 23, 59, 59).getTime();
        } else {
            // Jeśli brak parametru, pozwalamy ApexCharts automatycznie wyliczyć zakres na podstawie danych (ostatnie 12 m-cy)
            minX = undefined;
            maxX = undefined;
        }

        const options = {
            chart: {type: 'line', height: 355, toolbar: {show: false}},
            series,
            stroke: {curve: 'smooth', width: 2},
            xaxis: {
                type: 'datetime',
                labels: {format: 'MMM', datetimeUTC: false},
                min: minX,
                max: maxX,
                tickAmount: 12
            },
            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: {formatter: (v) => (v == null ? '' : Math.round(v).toString())},
                title: {text: 'µg/m³'}
            },
            tooltip: {
                shared: true, theme: 'dark',
                x: { format: 'dd MMM yyyy' },
                y: { formatter: v => (v == null ? '' : `${v.toFixed(1)} µg/m³`) }
            },
            legend: { show: true, position: 'bottom' },
            colors: ['#ff6b6b', '#4dabf7'],
            grid: { strokeDashArray: 4 }
        };

        elAir.innerHTML = '';
        if (!airChart) { airChart = new ApexCharts(elAir, options); airChart.render(); }
        else { airChart.updateOptions(options, true, true); }
    };

    const reloadCards = async () => {
        const container = document.getElementById('air_quality_data_cards');
        if (!container) return;
        try {
            const url = new URL(window.location.href);
            const res = await fetch(url.toString(), { cache: 'no-store' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const html = await res.text();
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            const fresh = tmp.querySelector('#air_quality_data_cards');
            if (fresh) container.innerHTML = fresh.innerHTML;
        } catch (err) { console.error(err); }
    };

    const loadAir = async (dateParam) => {
        try {
            elAir.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
            const raw = await fetchAirQualityYearlyAvg(dateParam || '');
            renderAir(transformAir(raw || []), dateParam);
        } catch {
            elAir.innerHTML = '<div class="text-center p-4">Błąd ładowania.</div>';
        }
    };

    // Init
    const urlDate = new URL(window.location.href).searchParams.get('date');
    const initialYear = isValidYearStr(urlDate) ? urlDate : '';

    if (initialYear) {
        dateInput.value = initialYear;
        updateNextButtonState();
    } else {
        dateInput.value = ''; // lub zostaw puste, jeśli placeholder to sugeruje
    }

    loadAir(initialYear);

    // Events
    prevBtn.addEventListener('click', () => changeYear(-1));
    nextBtn.addEventListener('click', () => changeYear(1));
    dateInput.addEventListener('change', (e) => {
        const val = e.target.value;
        if (isValidYearStr(val)) {
            setUrlDateParam(val);
            updateNextButtonState();
            loadAir(val);
            reloadCards();
            dispatchYearChanged(val);
        }
    });
});
