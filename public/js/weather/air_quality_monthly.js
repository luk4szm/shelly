// language: javascript
// PM2.5/PM10: wykres liniowy z dobowymi średnimi i średnią kroczącą (front).
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
        const d = parseMonth(dateInput.value);
        if (!d) return;
        d.setMonth(d.getMonth() + delta);
        const v = toMonthStr(d);
        dateInput.value = v;
        setUrlDateParam(v);
        updateNextButtonState();
        loadAir(v);
        reloadCards();
        // poinformuj drugi skrypt (pogoda), aby też się przeładował
        dispatchMonthChanged(v);
    };

    const fetchAirQualityMonthlyAvg = async (monthStr) => {
        const res = await fetch(`/weather/get-air-quality-monthly-avg?date=${encodeURIComponent(monthStr)}`, { cache: 'no-store' });
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
            elAir.innerHTML = '<div class="text-center p-4">Brak danych miesięcznych.</div>';
            return;
        }
        const options = {
            chart: { type: 'line', height: 355, toolbar: { show: false } },
            series,
            stroke: { curve: 'smooth', width: 2 },
            dataLabels: { enabled: false },
            markers: { size: 0 }, // brak punktów
            xaxis: { type: 'datetime', labels: { format: 'dd MMM', datetimeUTC: false } },
            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: { formatter: (v) => (v == null ? '' : Math.round(v).toString()) }, // pełne liczby
                title: { text: 'µg/m³' }
            },
            tooltip: {
                shared: true, intersect: false, theme: 'dark',
                x: { format: 'dd MMM' },
                y: { formatter: v => (v == null ? '' : `${v.toFixed(0)} µg/m³`) } // pełne liczby
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
            const m = dateInput.value || '';
            if (m) url.searchParams.set('date', m);
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

    const loadAir = async (monthStr) => {
        try {
            elAir.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
            const raw = await fetchAirQualityMonthlyAvg(monthStr);
            renderAir(transformAir(raw || []));
        } catch {
            elAir.innerHTML = '<div class="text-center p-4">Błąd ładowania.</div>';
        }
    };

    prevBtn.addEventListener('click', () => changeMonth(-1));
    nextBtn.addEventListener('click', () => changeMonth(1));
    dateInput.addEventListener('change', (e) => {
        const val = e.target.value;
        if (!isValidMonthStr(val)) return;
        setUrlDateParam(val);
        updateNextButtonState();
        loadAir(val);
        reloadCards();
        dispatchMonthChanged(val); // wywołaj także przeładowanie pogodowych wykresów
    });
    window.addEventListener('popstate', () => {
        const d = new URL(window.location.href).searchParams.get('date');
        const m = isValidMonthStr(d) ? d : dateInput.value;
        if (isValidMonthStr(m)) {
            dateInput.value = m;
            updateNextButtonState();
            loadAir(m);
            reloadCards();
            dispatchMonthChanged(m);
        }
    });

    const urlDate = new URL(window.location.href).searchParams.get('date');
    let initial = '';
    if (/^\d{4}-\d{2}$/.test(urlDate)) initial = urlDate;
    else {
        const holder = document.querySelector('[data-role-date]');
        const raw = holder ? (holder.getAttribute('data-role-date') || '').trim() : '';
        const m = raw.match(/^(\d{4}-\d{2})/);
        initial = m ? m[1] : toMonthStr(new Date());
    }
    dateInput.value = initial;
    setUrlDateParam(initial, true);
    updateNextButtonState();
    loadAir(initial);
    // poinformuj drugi skrypt o starcie (aby zaczytał ten sam miesiąc)
    dispatchMonthChanged(initial);
});
