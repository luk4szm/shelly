/**
 * @file Skrypt do obsługi dynamicznych wykresów miesięcznych (rangeBar) – temperatura i wilgotność.
 * @version 2.2.0
 */

// --- API ---
const apiService = {
    async fetchData(slug, date, type) {
        const url = `/location/${slug}/get-data?type=${type}&date=${date}`;
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return await response.json();
        } catch (e) {
            console.error('fetchData error:', e);
            return null;
        }
    }
};

// --- Procesor danych (monthly, min/max) ---
const dataProcessor = {
    processMonthlyForKey(rawData, key) {
        const arr = rawData?.[key] || [];
        if (!Array.isArray(arr) || arr.length === 0) return {series: [], types: []};

        const seriesData = arr.map(p => ({
            x: p.date,
            y: [parseFloat(p.min), parseFloat(p.max)]
        }));

        const pretty = key === 'temperature' ? 'Temperatura' : 'Wilgotność';
        return {series: [{name: pretty, data: seriesData, type: 'rangeBar'}], types: [key]};
    }
};

// --- Budowa konfiguracji (monthly) ---
const chartConfigBuilder = {
    _unit(val, type) {
        if (val === null || typeof val === 'undefined' || isNaN(val)) return '';
        const n = Number(val);
        return type === 'temperature' ? `${n.toFixed(1)}°C`
            : type === 'humidity' ? `${n.toFixed(0)}%`
                : String(n);
    },
    _minMaxFromRange(series) {
        let min = Infinity, max = -Infinity;
        (series[0]?.data || []).forEach(p => {
            if (Array.isArray(p.y)) {
                const a = Number(p.y[0]);
                const b = Number(p.y[1]);
                if (!isNaN(a) && a < min) min = a;
                if (!isNaN(b) && b > max) max = b;
            }
        });
        if (min === Infinity) {
            min = 0;
            max = 1;
        }
        return {min, max};
    },
    _yaxis(type, series) {
        const {min, max} = this._minMaxFromRange(series);
        let ymin = min, ymax = max;
        if (type === 'temperature') {
            ymin = Math.floor(min - 1);
            ymax = Math.ceil(max + 1);
        } else if (type === 'humidity') {
            ymin = Math.max(0, Math.floor(min - 5));
            ymax = Math.min(100, Math.ceil(max + 5));
        }
        return {
            title: {text: type === 'temperature' ? 'Temperatura' : 'Wilgotność'},
            min: ymin,
            max: ymax,
            labels: {formatter: (v) => this._unit(v, type)}
        };
    },
    buildMonthly(processed, type) {
        return {
            series: processed.series,
            chart: {type: 'rangeBar', height: 400, toolbar: {show: false}, fontFamily: 'inherit'},
            plotOptions: {bar: {horizontal: false, columnWidth: '80%'}},
            dataLabels: {enabled: false},
            xaxis: {
                type: 'datetime',
                labels: {format: 'dd', datetimeUTC: false},
                tooltip: {enabled: false}
            },
            yaxis: this._yaxis(type, processed.series),
            tooltip: {
                theme: 'dark',
                x: {format: 'dd MMM yyyy'},
                y: {
                    formatter: (val, opts) => {
                        const dp = opts?.w?.config?.series?.[opts.seriesIndex]?.data?.[opts.dataPointIndex];
                        if (dp && Array.isArray(dp.y)) {
                            const [a, b] = dp.y;
                            return `${this._unit(a, type)} — ${this._unit(b, type)}`;
                        }
                        return this._unit(val, type);
                    }
                }
            },
            grid: {padding: {top: -20, right: 0, left: -4, bottom: -4}, strokeDashArray: 4},
            legend: {show: false},
            // Dodano: kolor słupków temperatury na czerwono
            colors: type === 'temperature' ? ['var(--tblr-red)'] : undefined,
        };
    }
};

// --- Manager jednego wykresu ---
class SingleMonthlyChart {
    constructor({chartId, key /* 'temperature' | 'humidity' */}) {
        this.el = document.getElementById(chartId);
        if (!this.el) return;
        this.key = key;
        this.locationSlug = this.el.dataset.locationSlug;
        this.chartType = this.el.dataset.chartType || 'monthly';
        this.chart = null;
    }

    show(msg) {
        this.el.innerHTML = `<div class="text-center p-5">${msg}</div>`;
    }

    clear() {
        this.el.innerHTML = '';
    }

    async load(month) {
        if (!this.el || !this.locationSlug) return;
        this.show('Ładowanie danych...');
        const raw = await apiService.fetchData(this.locationSlug, month, this.chartType);
        if (!raw) {
            this.show('Brak danych do wyświetlenia.');
            return;
        }

        const processed = dataProcessor.processMonthlyForKey(raw, this.key);
        if (processed.series.length === 0) {
            this.show('Brak danych do wyświetlenia.');
            return;
        }

        const options = chartConfigBuilder.buildMonthly(processed, this.key);
        this.clear();
        if (!this.chart) {
            this.chart = new ApexCharts(this.el, options);
            await this.chart.render();
        } else {
            await this.chart.updateOptions(options, true, true);
        }
    }
}

// --- Koordynator dwóch wykresów (monthly) ---
class DualMonthlyController {
    constructor() {
        this.datePicker = document.getElementById('location_date');
        this.prev = document.getElementById('prev-month-btn');
        this.next = document.getElementById('next-month-btn');

        this.temp = new SingleMonthlyChart({chartId: 'location-chart-temperature', key: 'temperature'});
        this.hum = new SingleMonthlyChart({chartId: 'location-chart-humidity', key: 'humidity'});
    }

    init() {
        if (!this.datePicker) return;
        this.datePicker.addEventListener('change', () => this.reload());
        if (this.prev) this.prev.addEventListener('click', () => this.shiftMonth(-1));
        if (this.next) this.next.addEventListener('click', () => this.shiftMonth(1));
        this.updateNextDisabled();
        this.reload();
    }

    updateNextDisabled() {
        if (!this.next) return;
        const today = new Date();
        const pick = new Date(this.datePicker.value + '-01');
        const isFuture = pick.getFullYear() > today.getFullYear()
            || (pick.getFullYear() === today.getFullYear() && pick.getMonth() >= today.getMonth());
        this.next.disabled = isFuture;
    }

    shiftMonth(offset) {
        const d = new Date(this.datePicker.value + '-01T12:00:00Z');
        d.setUTCMonth(d.getUTCMonth() + offset);
        this.datePicker.value = d.toISOString().slice(0, 7);
        this.datePicker.dispatchEvent(new Event('change', {bubbles: true}));
    }

    reload() {
        const month = this.datePicker.value;
        const url = new URL(window.location.href);
        url.searchParams.set('date', month);
        window.history.replaceState({}, '', url.toString());

        this.updateNextDisabled();
        this.temp.load(month);
        this.hum.load(month);
    }
}

// --- Inicjalizacja (monthly) ---
document.addEventListener('DOMContentLoaded', () => {
    if (!window.ApexCharts) return;
    const hasTemp = document.getElementById('location-chart-temperature');
    const hasHum = document.getElementById('location-chart-humidity');
    const chartType = hasTemp?.dataset.chartType || hasHum?.dataset.chartType || 'monthly';
    if (chartType !== 'monthly') return;
    new DualMonthlyController().init();
});
