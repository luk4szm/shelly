// language: javascript
// PM2.5/PM10: linia, brak markerów, pełne liczby; bez ?date= ładuje ostatnie 30 dni.
document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) return;

    const dateInput = document.getElementById('wheater_date'); // type="month"
    const prevBtn = document.getElementById('prev-day-btn');
    const nextBtn = document.getElementById('next-day-btn');
    const elAir = document.getElementById('chart-air-quality');

    if (!dateInput || !prevBtn || !nextBtn || !elAir) return;

    let airChart = null;

    const pad = (n) => String(n).padStart(2, '0');
    const toMonthStr = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;
    const isValidMonthStr = (s) => /^\d{4}-\d{2}$/.test(s);
    const parseMonth = (s) => {
        if (!isValidMonthStr(s)) return null;
        const [y, m] = s.split('-').map(Number);
        return new Date(y, m - 1, 1);
    };

    const updateNextButtonState = () => {
        const lock = new Date();
        const urlDate = new URL(window.location.href).searchParams.get('date');
        if (!isValidMonthStr(urlDate)) { nextBtn.disabled = true; return; } // w trybie 30 dni przyciski nie mają sensu
        const current = parseMonth(dateInput.value);
        const lockYm = new Date(lock.getFullYear(), lock.getMonth(), 1);
        const currYm = current ? new Date(current.getFullYear(), current.getMonth(), 1) : null;
        nextBtn.disabled = !!currYm && currYm >= lockYm;
    };

    const setUrlDateParam = (monthStr, replace = false) => {
        const url = new URL(window.location.href);
        if (monthStr) url.searchParams.set('date', monthStr);
        else url.searchParams.delete('date');
        if (replace) window.history.replaceState({}, '', url.toString());
        else window.history.pushState({}, '', url.toString());
    };

    const dispatchMonthChanged = (monthStr) => {
        window.dispatchEvent(new CustomEvent('weather:monthChanged', { detail: monthStr }));
    };

    const changeMonth = (delta) => {
        const urlDate = new URL(window.location.href).searchParams.get('date');
        if (!isValidMonthStr(urlDate)) return; // w trybie 30 dni nic nie zmieniamy
        const d = parseMonth(dateInput.value);
        if (!d) return;
        d.setMonth(d.getMonth() + delta);
        const v = toMonthStr(d);
        dateInput.value = v;
        setUrlDateParam(v);
        updateNextButtonState();
        loadAir(v);
        reloadCards();
        dispatchMonthChanged(v);
    };

    const fetchAirQualityMonthlyAvg = async (dateParam) => {
        const url = new URL('/weather/get-air-quality-monthly-avg', window.location.origin);
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
        const points = rows.slice().sort((a, b) => a.x - b.x);
        const pm25ma = movingAverage(points, 'pm25', 3);
        const pm10ma = movingAverage(points, 'pm10', 3);

        const sPm25 = points.map((p, i) => ({ x: p.x, y: typeof pm25ma[i] === 'number' ? pm25ma[i] : null }));
        const sPm10 = points.map((p, i) => ({ x: p.x, y: typeof pm10ma[i] === 'number' ? pm10ma[i] : null }));

        return {
            series: [
                { name: 'PM2.5 (Śr. krocząca)', data: sPm25, type: 'line' },
                { name: 'PM10 (Śr. krocząca)', data: sPm10, type: 'line' },
            ]
        };
    };

    const renderAir = ({ series }) => {
        const hasData = series.some(s => s.data.some(p => p.y != null));
        if (!hasData) {
            if (airChart) { airChart.destroy(); airChart = null; }
            elAir.innerHTML = '<div class="text-center p-4">Brak danych.</div>';
            return;
        }
        const options = {
            chart: { type: 'line', height: 355, toolbar: { show: false } },
            series,
            stroke: { curve: 'smooth', width: 2 },
            dataLabels: { enabled: false },
            markers: { size: 0 },
            xaxis: { type: 'datetime', labels: { format: 'dd MMM', datetimeUTC: false } },
            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: { formatter: (v) => (v == null ? '' : Math.round(v).toString()) },
                title: { text: 'µg/m³' }
            },
            tooltip: {
                shared: true, intersect: false, theme: 'dark',
                x: { format: 'dd MMM' },
                y: { formatter: v => (v == null ? '' : `${v.toFixed(0)} µg/m³`) }
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
        container.innerHTML = '<div class="text-center p-3">Ładowanie…</div>';
        try {
            const url = new URL(window.location.href);
            const d = url.searchParams.get('date');
            if (d) url.searchParams.set('date', d);
            const res = await fetch(url.toString(), { cache: 'no-store' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const html = await res.text();
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            const fresh = tmp.querySelector('#air_quality_data_cards');
            container.innerHTML = fresh ? fresh.innerHTML : '<div class="text-center text-muted p-3">Brak danych.</div>';
        } catch {
            container.innerHTML = '<div class="text-center text-muted p-3">Nie udało się załadować.</div>';
        }
    };

    const loadAir = async (dateParam) => {
        try {
            elAir.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
            const raw = await fetchAirQualityMonthlyAvg(dateParam || '');
            renderAir(transformAir(raw || []));
        } catch {
            elAir.innerHTML = '<div class="text-center p-4">Błąd ładowania.</div>';
        }
    };

    // Inicjalizacja: jeśli brak ?date= -> tryb „ostatnie 30 dni”
    const urlDate = new URL(window.location.href).searchParams.get('date');
    let initialMonth = '';
    if (isValidMonthStr(urlDate)) {
        initialMonth = urlDate;
        dateInput.value = initialMonth;
        setUrlDateParam(initialMonth, true);
    } else {
        // tryb 30 dni – blokuj nextBtn i nie ustawiaj parametru date
        const now = new Date();
        dateInput.value = toMonthStr(now);
        const url = new URL(window.location.href);
        url.searchParams.delete('date');
        window.history.replaceState({}, '', url.toString());
    }

    updateNextButtonState();
    loadAir(initialMonth || '');

    // Zdarzenia
    prevBtn.addEventListener('click', () => changeMonth(-1));
    nextBtn.addEventListener('click', () => changeMonth(1));
    dateInput.addEventListener('change', (e) => {
        const val = e.target.value;
        // w trybie 30 dni przejście na konkretny miesiąc po wyborze z inputa
        if (isValidMonthStr(val)) {
            setUrlDateParam(val);
            updateNextButtonState();
            loadAir(val);
            reloadCards();
            dispatchMonthChanged(val);
        }
    });
    window.addEventListener('popstate', () => {
        const d = new URL(window.location.href).searchParams.get('date');
        if (isValidMonthStr(d)) {
            dateInput.value = d;
            updateNextButtonState();
            loadAir(d);
            reloadCards();
            dispatchMonthChanged(d);
        } else {
            updateNextButtonState();
            loadAir('');
            reloadCards();
            dispatchMonthChanged('');
        }
    });
});
