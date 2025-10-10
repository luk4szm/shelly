/**
 * @file Skrypt do obsługi dynamicznego wykresu temperatury dla lokalizacji
 * @author Gemini Refactor
 * @version 2.1.0
 */

// === MODUŁY POMOCNICZE ===

/**
 * @namespace apiService
 * @description Odpowiada za komunikację z API w celu pobrania danych.
 */
const apiService = {
    async fetchData(slug, date, type) {
        const url = `/location/${slug}/get-data?type=${type}&date=${date}`;
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
 * @description Przetwarza surowe dane z API, filtrując tylko dane o temperaturze.
 */
const dataProcessor = {
    SERIES_TYPES: {
        TEMPERATURE: 'temperature',
        HUMIDITY: 'humidity', // Pozostawione do identyfikacji i odfiltrowania
        PRESSURE: 'pressure',
        GENERIC: 'generic',
    },

    SERIES_NAMES: {
        temperature: 'Temperatura',
        pressure: 'Ciśnienie',
    },

    _detectType(key) {
        const k = key.toLowerCase();
        if (k.includes('temp')) return this.SERIES_TYPES.TEMPERATURE;
        if (k.includes('humid')) return this.SERIES_TYPES.HUMIDITY;
        if (k.includes('press')) return this.SERIES_TYPES.PRESSURE;
        return this.SERIES_TYPES.GENERIC;
    },

    _getPrettyName(key) {
        return this.SERIES_NAMES[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    },

    process(rawData) {
        const chartSeries = [];
        const seriesTypes = [];

        for (const key of Object.keys(rawData)) {
            const type = this._detectType(key);

            // IGNOROWANIE SERII INNYCH NIŻ TEMPERATURA
            if (type !== this.SERIES_TYPES.TEMPERATURE) {
                continue; // Pomiń tę serię danych
            }

            const dataPoints = rawData[key] || [];
            if (dataPoints.length === 0) continue;

            // Dane tylko dla zakresów min/max
            const seriesData = dataPoints.map(p => ({
                x: p.date,
                y: [parseFloat(p.min), parseFloat(p.max)],
            }));

            chartSeries.push({
                name: this._getPrettyName(key),
                data: seriesData,
                type: 'rangeBar'
            });
            seriesTypes.push(type);
        }

        return { series: chartSeries, types: seriesTypes };
    }
};

/**
 * @namespace chartConfigBuilder
 * @description Tworzy kompletną konfigurację dla instancji ApexCharts.
 */
const chartConfigBuilder = {
    PALETTE: [
        "var(--tblr-red)", "var(--tblr-blue)", "var(--tblr-green)",
        "var(--tblr-orange)", "var(--tblr-cyan)", "var(--tblr-indigo)"
    ],

    _unitFormatter(val, type) {
        if (val === null || typeof val === 'undefined') return '';
        const numVal = Number(val);
        if (isNaN(numVal)) return String(val);

        switch (type) {
            case dataProcessor.SERIES_TYPES.TEMPERATURE: return `${numVal.toFixed(1)}°C`;
            case dataProcessor.SERIES_TYPES.PRESSURE:    return `${numVal.toFixed(2)} bar`;
            default:                                     return numVal.toString();
        }
    },

    /**
     * @private
     * @description Znajduje minimalną i maksymalną wartość dla danego typu serii.
     */
    _findMinMax(series, types, targetType) {
        let globalMin = Infinity;
        let globalMax = -Infinity;

        series.forEach((s, index) => {
            if (types[index] === targetType) {
                s.data.forEach(p => {
                    if (Array.isArray(p.y) && p.y.length === 2) {
                        const min = p.y[0];
                        const max = p.y[1];
                        if (min < globalMin) globalMin = min;
                        if (max > globalMax) globalMax = max;
                    }
                });
            }
        });

        // Jeśli nie znaleziono danych, zwracamy null
        if (globalMin === Infinity || globalMax === -Infinity) {
            return { min: null, max: null };
        }

        // Dodanie bufora 1 stopnia
        return {
            min: Math.floor(globalMin - 1), // Zaokrąglenie w dół dla bezpiecznego marginesu
            max: Math.ceil(globalMax + 1)  // Zaokrąglenie w górę dla bezpiecznego marginesu
        };
    },

    _buildYAxes(processedData) {
        const { series, types } = processedData;
        const yAxesConfig = [];
        const assignedTypes = new Set();

        // Obliczanie zakresu dla temperatury
        const tempRange = this._findMinMax(series, types, dataProcessor.SERIES_TYPES.TEMPERATURE);

        const typeToAxisMap = {
            // Dodano min/max do konfiguracji osi Y dla temperatury
            [dataProcessor.SERIES_TYPES.TEMPERATURE]: {
                title: { text: 'Temperatura' },
                min: tempRange.min,
                max: tempRange.max,
            },
            [dataProcessor.SERIES_TYPES.PRESSURE]:    { title: { text: 'Ciśnienie' }, opposite: true },
        };

        types.forEach((type, index) => {
            if (assignedTypes.has(type) || !typeToAxisMap[type]) return;

            const seriesNamesForType = series
                .filter((_, i) => types[i] === type)
                .map(s => s.name);

            yAxesConfig.push({
                ...typeToAxisMap[type],
                seriesName: seriesNamesForType,
                labels: { formatter: (val) => this._unitFormatter(val, type) },
            });
            assignedTypes.add(type);
        });

        return yAxesConfig.length > 0 ? yAxesConfig : [{}];
    },

    _buildTooltip(processedData) {
        return {
            theme: "dark",
            x: { format: 'dd MMM yyyy' },
            y: {
                formatter: (val, opts) => {
                    if (!opts || typeof opts.seriesIndex === 'undefined') {
                        return this._unitFormatter(val, 'generic');
                    }

                    const { seriesIndex, dataPointIndex, w } = opts;
                    const type = processedData.types[seriesIndex];
                    const dataPoint = w.config.series[seriesIndex].data[dataPointIndex];

                    if (dataPoint && Array.isArray(dataPoint.y)) {
                        const min = this._unitFormatter(dataPoint.y[0], type);
                        const max = this._unitFormatter(dataPoint.y[1], type);
                        return `${min} &mdash; ${max}`;
                    }
                    return this._unitFormatter(val, type);
                }
            }
        };
    },

    build(processedData) {
        const seriesCount = processedData.series.length;

        return {
            series: processedData.series,
            chart: {
                type: "rangeBar",
                fontFamily: "inherit",
                height: 400,
                toolbar: { show: false },
                animations: { enabled: true },
            },
            plotOptions: { bar: { horizontal: false, columnWidth: '80%' } },
            dataLabels: { enabled: false },
            colors: processedData.series.map((_, i) => this.PALETTE[i % this.PALETTE.length]),
            stroke: {
                // Uproszczono: tylko 0 dla rangeBar, brak logiki dla linii
                width: 0,
                dashArray: 0,
                curve: 'straight',
            },
            xaxis: {
                type: 'datetime',
                labels: { format: 'dd', datetimeUTC: false },
                tooltip: { enabled: false }
            },
            yaxis: this._buildYAxes(processedData), // Tutaj przekazywana jest nowa konfiguracja
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
            this.elements.prevBtn.addEventListener('click', () => this.changeMonth(-1));
        }
        if (this.elements.nextBtn) {
            this.elements.nextBtn.addEventListener('click', () => this.changeMonth(1));
        }

        this.updateNextButtonState();
        this.loadChart();
    }

    handleDateChange() {
        this.updateUrl();
        this.updateNextButtonState();
        this.loadChart();
    }

    changeMonth(monthOffset) {
        const currentDate = new Date(this.elements.datePicker.value + '-01T12:00:00Z');
        currentDate.setUTCMonth(currentDate.getUTCMonth() + monthOffset);
        this.elements.datePicker.value = currentDate.toISOString().slice(0, 7);
        this.elements.datePicker.dispatchEvent(new Event('change'));
    }

    updateNextButtonState() {
        if (!this.elements.nextBtn) return;
        const today = new Date();
        const pickerDate = new Date(this.elements.datePicker.value + '-01');
        const isFuture = pickerDate.getFullYear() > today.getFullYear() ||
            (pickerDate.getFullYear() === today.getFullYear() && pickerDate.getMonth() >= today.getMonth());
        this.elements.nextBtn.disabled = isFuture;
    }

    updateUrl() {
        const url = new URL(window.location.href);
        url.searchParams.set('date', this.elements.datePicker.value);
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

        // Dodatkowy warunek sprawdzający, czy po odfiltrowaniu zostały jakieś dane
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
                prevBtnId: 'prev-month-btn',
                nextBtnId: 'next-month-btn'
            });
            chartManager.init();
        } catch (error) {
            console.error("Inicjalizacja menedżera wykresu nie powiodła się:", error);
        }
    }
});
