/**
 * @file Skrypt do obsługi dynamicznego wykresu danych meteorologicznych (linia).
 * @author Gemini Refactor
 * @version 2.2.0
 */

// === MODUŁY POMOCNICZE ===

/**
 * @namespace apiService
 * @description Odpowiada za komunikację z API w celu pobrania danych.
 */
const apiService = {
    async fetchData(slug, date, type) {
        const url = `/location/${slug}/get-data?date=${date}&type=${type}`;
        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`Błąd HTTP! Status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error(`Nie udało się pobrać danych dla lokalizacji "${slug}" i daty ${date}:`, error);
            return null;
        }
    }
};

/**
 * @namespace dataProcessor
 * @description Przetwarza surowe dane z API, ujednolicając format dla wykresu.
 */
const dataProcessor = {
    SERIES_TYPES: {
        TEMPERATURE: 'temperature',
        HUMIDITY: 'humidity',
        PRESSURE: 'pressure',
        GENERIC: 'generic',
    },

    _detectType(key) {
        const k = key.toLowerCase();
        if (k.includes('temp')) return this.SERIES_TYPES.TEMPERATURE;
        if (k.includes('humid')) return this.SERIES_TYPES.HUMIDITY;
        if (k.includes('press')) return this.SERIES_TYPES.PRESSURE;
        return this.SERIES_TYPES.GENERIC;
    },

    _getPrettyName(key) {
        const map = {
            temperature: 'Temperatura',
            humidity: 'Wilgotność',
            pressure: 'Ciśnienie',
            temperature_05m: 'Temperatura 0,5 m',
            temperature_15m: 'Temperatura 1,5 m',
        };
        if (map[key]) return map[key];
        return key
            .replace(/_/g, ' ')
            .replace(/\btemp(erature)?\b/i, 'Temperatura')
            .replace(/\bhumidity\b/i, 'Wilgotność')
            .replace(/\bpressure\b/i, 'Ciśnienie')
            .replace(/\b05m\b/i, '0,5 m')
            .replace(/\b15m\b/i, '1,5 m');
    },

    process(rawData) {
        const chartSeries = [];
        const seriesTypes = [];

        for (const key of Object.keys(rawData)) {
            const dataPoints = rawData[key] || [];
            if (dataPoints.length === 0) continue;

            const type = this._detectType(key);

            const seriesData = dataPoints.map(point => ({
                x: new Date(point.datetime).getTime(),
                y: parseFloat(point.value) // Upewnienie się, że wartość jest liczbą
            }));

            chartSeries.push({
                name: this._getPrettyName(key),
                data: seriesData,
                type: 'line' // Wymuszenie typu 'line' dla ujednolicenia (choć ApexCharts używa go domyślnie)
            });
            seriesTypes.push(type);
        }

        return { series: chartSeries, types: seriesTypes };
    }
};

/**
 * @namespace chartConfigBuilder
 * @description Tworzy kompletną konfigurację dla instancji ApexCharts, w tym dynamiczne osie.
 */
const chartConfigBuilder = {
    PALETTE: [
        "var(--tblr-red)", "var(--tblr-blue)", "var(--tblr-green)",
        "var(--tblr-orange)", "var(--tblr-cyan)", "var(--tblr-indigo)",
        "var(--tblr-teal)", "var(--tblr-yellow)", "var(--tblr-pink)", "var(--tblr-purple)",
    ],
    
    UNIT_BUFFERS: {
        temperature: 1, // +/- 1 stopień
        humidity: 5,    // +/- 5%
    },

    _unitFormatter(val, type) {
        if (val === null || typeof val === 'undefined' || isNaN(val)) return '';
        const numVal = Number(val);

        switch (type) {
            case dataProcessor.SERIES_TYPES.TEMPERATURE: return `${numVal.toFixed(1)}°C`;
            case dataProcessor.SERIES_TYPES.HUMIDITY:    return `${numVal.toFixed(0)}%`;
            case dataProcessor.SERIES_TYPES.PRESSURE:    return `${numVal.toFixed(2)} bar`;
            default:                                     return numVal.toString();
        }
    },

    /**
     * @private
     * @description Znajduje minimalną i maksymalną wartość dla wszystkich serii danego typu.
     */
    _findMinMax(series, types, targetType) {
        let globalMin = Infinity;
        let globalMax = -Infinity;

        series.forEach((s, index) => {
            if (types[index] === targetType) {
                (s.data || []).forEach(p => {
                    const value = p.y;
                    if (typeof value === 'number' && !isNaN(value)) {
                        if (value < globalMin) globalMin = value;
                        if (value > globalMax) globalMax = value;
                    }
                });
            }
        });
        return (globalMin === Infinity || globalMax === -Infinity) ? { min: null, max: null } : { min: globalMin, max: globalMax };
    },

    _buildYAxes(processedData) {
        const { series, types } = processedData;
        const yAxesConfig = [];
        const assignedTypes = new Set();
        
        // Obliczenia zakresów i buforów
        const tempRange = this._findMinMax(series, types, dataProcessor.SERIES_TYPES.TEMPERATURE);
        const humidityRange = this._findMinMax(series, types, dataProcessor.SERIES_TYPES.HUMIDITY);
        const tempBuffer = this.UNIT_BUFFERS.temperature;
        const humBuffer = this.UNIT_BUFFERS.humidity;

        // Dynamiczne wyznaczanie min/max z buforem
        const getTempMin = tempRange.min !== null ? Math.floor(tempRange.min - tempBuffer) : 0;
        const getTempMax = tempRange.max !== null ? Math.ceil(tempRange.max + tempBuffer) : 40;
        
        // Logika buforu dla wilgotności: upewnij się, że min/max nie wychodzi poza 0-100%
        const getHumMin = humidityRange.min !== null ? Math.max(0, humidityRange.min - humBuffer) : 0;
        const getHumMax = humidityRange.max !== null ? Math.min(100, humidityRange.max + humBuffer) : 100;

        const baseAxisConfig = {
            [dataProcessor.SERIES_TYPES.TEMPERATURE]: { 
                title: { text: 'Temperatura' }, 
                min: getTempMin,
                max: getTempMax,
            },
            [dataProcessor.SERIES_TYPES.HUMIDITY]: { 
                title: { text: 'Wilgotność' }, 
                opposite: true,
                min: getHumMin,
                max: getHumMax,
            },
            [dataProcessor.SERIES_TYPES.PRESSURE]: { 
                title: { text: 'Ciśnienie [bar]' }, 
                opposite: true,
                // Stały zakres ciśnienia
                min: 0,
                max: 3,
            },
        };

        // Zbieranie serii dla każdej osi
        const tempSeriesNames = series.filter((s, i) => types[i] === dataProcessor.SERIES_TYPES.TEMPERATURE).map(s => s.name);
        const humiditySeriesNames = series.filter((s, i) => types[i] === dataProcessor.SERIES_TYPES.HUMIDITY).map(s => s.name);
        const pressureSeriesNames = series.filter((s, i) => types[i] === dataProcessor.SERIES_TYPES.PRESSURE).map(s => s.name);

        if (tempSeriesNames.length > 0) {
            yAxesConfig.push({
                ...baseAxisConfig[dataProcessor.SERIES_TYPES.TEMPERATURE],
                seriesName: tempSeriesNames,
                labels: { formatter: (val) => this._unitFormatter(val, dataProcessor.SERIES_TYPES.TEMPERATURE) },
            });
            assignedTypes.add(dataProcessor.SERIES_TYPES.TEMPERATURE);
        }

        if (humiditySeriesNames.length > 0) {
            yAxesConfig.push({
                ...baseAxisConfig[dataProcessor.SERIES_TYPES.HUMIDITY],
                seriesName: humiditySeriesNames,
                labels: { formatter: (val) => this._unitFormatter(val, dataProcessor.SERIES_TYPES.HUMIDITY) },
            });
            assignedTypes.add(dataProcessor.SERIES_TYPES.HUMIDITY);
        }
        
        if (pressureSeriesNames.length > 0) {
             yAxesConfig.push({
                ...baseAxisConfig[dataProcessor.SERIES_TYPES.PRESSURE],
                seriesName: pressureSeriesNames,
                labels: { formatter: (val) => this._unitFormatter(val, dataProcessor.SERIES_TYPES.PRESSURE) },
            });
            assignedTypes.add(dataProcessor.SERIES_TYPES.PRESSURE);
        }


        // Fallback dla osi, jeśli nie ma żadnych zdefiniowanych
        if (yAxesConfig.length === 0) {
            yAxesConfig.push({ labels: { formatter: (v) => this._unitFormatter(v, 'generic') } });
        }

        return yAxesConfig;
    },

    _buildTooltip(processedData) {
        return {
            theme: "dark",
            x: { format: 'dd MMM, HH:mm' },
            y: {
                formatter: (val, opts) => {
                    const idx = opts?.seriesIndex ?? 0;
                    const type = processedData.types[idx] || 'generic';
                    return this._unitFormatter(val, type);
                }
            }
        };
    },

    build(processedData) {
        const seriesCount = processedData.series.length;

        // Standardowa szerokość linii dla wszystkich serii
        const strokes = Array.from({ length: seriesCount }, () => 2);
        const colors = processedData.series.map((_, i) => this.PALETTE[i % this.PALETTE.length]);

        return {
            series: processedData.series,
            chart: {
                type: "line",
                fontFamily: "inherit",
                height: 400,
                parentHeightOffset: 0,
                toolbar: { show: false },
                animations: { enabled: true },
            },
            stroke: {
                width: strokes, 
                curve: "smooth",
            },
            dataLabels: { enabled: false },
            colors: colors,
            xaxis: {
                type: 'datetime',
                labels: {
                    padding: 0,
                    format: 'HH:mm',
                    datetimeUTC: false,
                },
                tooltip: { enabled: false },
            },
            yaxis: this._buildYAxes(processedData),
            tooltip: this._buildTooltip(processedData),
            grid: {
                padding: { top: -20, right: 0, left: -4, bottom: -4 },
                strokeDashArray: 4
            },
            legend: {
                show: seriesCount > 1,
                position: 'bottom',
                horizontalAlign: 'center'
            },
            markers: {
                size: 0,
                hover: { sizeOffset: 1 }
            },
        };
    }
};


/**
 * @class LocationChartManager
 * @description Główna klasa zarządzająca logiką wykresu na stronie.
 */
class LocationChartManager {
    constructor(options) {
        this.elements = {
            chart: document.getElementById(options.chartId),
            datePicker: document.getElementById(options.datePickerId),
            prevBtn: document.getElementById(options.prevBtnId),
            nextBtn: document.getElementById(options.nextBtnId),
        };

        if (!this.elements.chart) {
            console.error(`Nie znaleziono kontenera wykresu: #${options.chartId}`);
            return;
        }

        this.locationSlug = this.elements.chart.dataset.locationSlug;
        this.chartType = this.elements.chart.dataset.chartType || 'monthly';
        this.chart = null;

        this.validateElements();
    }

    validateElements() {
        if (!this.locationSlug) {
            this.showError("Brak `data-location-slug` na kontenerze wykresu.");
            throw new Error("Błąd konfiguracji: Brak `data-location-slug`.");
        }
        if (!this.elements.datePicker) {
            this.showError("Brak elementu do wyboru daty.");
            throw new Error("Błąd konfiguracji: Brak `datePicker`.");
        }
    }

    init() {
        this.elements.datePicker.addEventListener('change', this.handleDateChange.bind(this));
        if (this.elements.prevBtn) {
            this.elements.prevBtn.addEventListener('click', () => this.changeDate(-1));
        }
        if (this.elements.nextBtn) {
            this.elements.nextBtn.addEventListener('click', () => this.changeDate(1));
        }

        this.updateNextButtonState();
        this.loadChart();
    }

    handleDateChange(event) {
        const newDate = event.target.value;
        this.updateUrl(newDate);
        this.updateNextButtonState();
        this.loadChart();
    }

    changeDate(days) {
        const currentDate = new Date(this.elements.datePicker.value);
        currentDate.setDate(currentDate.getDate() + days);
        this.elements.datePicker.value = currentDate.toISOString().split('T')[0];
        
        // Ręczne wywołanie zdarzenia 'change'
        this.elements.datePicker.dispatchEvent(new Event('change', { 'bubbles': true }));
    }

    updateNextButtonState() {
        if (!this.elements.nextBtn) return;
        const today = new Date();
        const currentDate = new Date(this.elements.datePicker.value);
        today.setHours(0, 0, 0, 0);
        currentDate.setHours(0, 0, 0, 0);
        this.elements.nextBtn.disabled = currentDate >= today;
    }

    updateUrl(newDate) {
        const url = new URL(window.location.href);
        url.searchParams.set('date', newDate);
        window.history.pushState({ path: url.href }, '', url.href);
    }

    showLoading() {
        this.elements.chart.innerHTML = '<div class="text-center p-5">Ładowanie danych...</div>';
    }

    showNoData() {
        this.elements.chart.innerHTML = '<div class="text-center p-5">Brak danych do wyświetlenia.</div>';
    }

    showError(message = "Wystąpił błąd podczas ładowania danych.") {
        this.elements.chart.innerHTML = `<div class="text-center p-5 text-danger">${message}</div>`;
    }

    async loadChart() {
        this.showLoading();

        const rawData = await apiService.fetchData(
            this.locationSlug,
            this.elements.datePicker.value,
            this.chartType
        );

        if (!rawData || Object.keys(rawData).length === 0) {
            if (this.chart) { this.chart.destroy(); this.chart = null; }
            this.showNoData();
            return;
        }

        const processedData = dataProcessor.process(rawData);

        if (processedData.series.length === 0) {
            if (this.chart) { this.chart.destroy(); this.chart = null; }
            this.showNoData();
            return;
        }

        const chartOptions = chartConfigBuilder.build(processedData);

        this.elements.chart.innerHTML = '';

        if (!this.chart) {
            this.chart = new ApexCharts(this.elements.chart, chartOptions);
            this.chart.render();
        } else {
            this.chart.updateOptions(chartOptions);
        }
    }
}


// --- INICJALIZACJA ---
document.addEventListener("DOMContentLoaded", () => {
    if (window.ApexCharts && document.getElementById('location-chart')) {
        try {
            const chartManager = new LocationChartManager({
                chartId: 'location-chart',
                datePickerId: 'location_date',
                prevBtnId: 'prev-day-btn',
                nextBtnId: 'next-day-btn' // Zmieniono na 'day' zamiast 'month'
            });
            chartManager.init();
        } catch (error) {
            console.error("Inicjalizacja menedżera wykresu nie powiodła się:", error);
        }
    }
});
