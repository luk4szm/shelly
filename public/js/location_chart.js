document.addEventListener("DOMContentLoaded", function () {
    const chartElement = document.getElementById('location-chart');
    const datePicker = document.getElementById('location_date');
    const locationSlug = chartElement ? chartElement.dataset.locationSlug : null;

    // Przyciski nawigacji daty
    const prevDayBtn = document.getElementById('prev-day-btn');
    const nextDayBtn = document.getElementById('next-day-btn');

    let chart = null;

    /**
     * Asynchroniczna funkcja do pobierania danych (temperatury i wilgotności) z backendu.
     * @param {string} dateString - Data w formacie YYYY-MM-DD.
     * @param {string} slug - Identyfikator lokalizacji.
     * @returns {Promise<Object|null>} - Obiekt z danymi lub null w przypadku błędu.
     */
    const fetchChartData = async (dateString, slug) => {
        // Endpoint zwraca dane dla temperatury i wilgotności dla danej lokalizacji i daty
        const url = `/location/${slug}/get-data?date=${dateString}`;

        if (chartElement) {
            chartElement.innerHTML = '<div class="text-center p-5">Ładowanie danych...</div>';
        }

        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`Błąd HTTP! Status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error(`Nie udało się pobrać danych dla lokalizacji "${slug}" i daty ${dateString}:`, error);
            if (chartElement) {
                chartElement.innerHTML = `<div class="text-center p-5 text-danger">Wystąpił błąd podczas ładowania danych.</div>`;
            }
            return null;
        }
    };

    /**
     * Przekształca surowe dane na format wymagany przez ApexCharts.
     * @param {Object} rawData - Surowe dane z backendu (np. { "temperature": [...], "humidity": [...] }).
     * @returns {{series: Array<Object>}} - Obiekt z seriami danych.
     */
    const transformDataForChart = (rawData) => {
        // Uniwersalne przetwarzanie serii – akceptuje dowolne klucze z backendu
        // Oczekiwany format wartości: [{ datetime: 'Y-m-d H:i:s', value: number }]

        const prettyName = (key) => {
            const map = {
                temperature: 'Temperatura',
                humidity: 'Wilgotność',
                pressure: 'Ciśnienie',
                temperature_05m: 'Temperatura 0,5 m',
                temperature_15m: 'Temperatura 1,5 m',
            };
            if (map[key]) return map[key];
            // Fallback: humanizuj klucz
            return key
                .replace(/_/g, ' ')
                .replace(/\btemp(erature)?\b/i, 'Temperatura')
                .replace(/\bhumidity\b/i, 'Wilgotność')
                .replace(/\bpressure\b/i, 'Ciśnienie')
                .replace(/\b05m\b/i, '0,5 m')
                .replace(/\b15m\b/i, '1,5 m');
        };

        const detectType = (key) => {
            const k = key.toLowerCase();
            if (k.includes('temp')) return 'temperature';
            if (k.includes('humid')) return 'humidity';
            if (k.includes('press')) return 'pressure';
            return 'generic';
        };

        const chartSeries = [];
        const seriesTypes = [];

        // Zachowujemy kolejność kluczy tak, jak przyjdzie z backendu
        for (const key of Object.keys(rawData)) {
            const dataPoints = rawData[key] || [];
            const t = detectType(key);
            const seriesData = dataPoints.map(point => ({
                x: new Date(point.datetime).getTime(),
                y: point.value
            }));

            chartSeries.push({
                name: prettyName(key),
                data: seriesData
            });
            seriesTypes.push(t);
        }

        return { series: chartSeries, types: seriesTypes };
    };

    /**
     * Główna funkcja, która pobiera dane i renderuje lub aktualizuje wykres.
     * @param {string} dateString - Data w formacie YYYY-MM-DD.
     * @param {string} slug - Identyfikator lokalizacji.
     */
    const loadAndRenderChart = async (dateString, slug) => {
        if (!dateString || !slug) {
            console.error("Nie podano daty lub lokalizacji do załadowania wykresu.");
            return;
        }

        const rawData = await fetchChartData(dateString, slug);

        if (!rawData || Object.keys(rawData).length === 0) {
            if (chart) {
                chart.destroy();
                chart = null;
            }
            chartElement.innerHTML = '<div class="text-center p-5">Brak danych do wyświetlenia dla wybranego dnia.</div>';
            return;
        }

        const chartData = transformDataForChart(rawData);

        // Przygotuj dynamiczne ustawienia dla serii
        const seriesCount = chartData.series.length;
        const types = chartData.types || [];

        // Wyznacz globalny zakres dla wszystkich serii temperatury (ujednolicona skala Y)
        let tempAllValues = [];
        chartData.series.forEach((s, idx) => {
            if (types[idx] === 'temperature') {
                (s.data || []).forEach(p => {
                    if (p && typeof p.y === 'number' && !isNaN(p.y)) tempAllValues.push(p.y);
                });
            }
        });
        const hasTemps = tempAllValues.length > 0;
        const tempMinRaw = hasTemps ? Math.min(...tempAllValues) : null;
        const tempMaxRaw = hasTemps ? Math.max(...tempAllValues) : null;
        // Dodaj niewielki bufor do zakresu
        const tempMinUnified = hasTemps ? Math.floor(tempMinRaw - 2) : 0;
        const tempMaxUnified = hasTemps ? Math.ceil(tempMaxRaw + 2) : 40;

        const palette = [
            "var(--tblr-red)",
            "var(--tblr-blue)",
            "var(--tblr-green)",
            "var(--tblr-orange)",
            "var(--tblr-cyan)",
            "var(--tblr-indigo)",
            "var(--tblr-teal)",
            "var(--tblr-yellow)",
            "var(--tblr-pink)",
            "var(--tblr-purple)",
        ];

        const colors = Array.from({ length: seriesCount }, (_, i) => palette[i % palette.length]);
        const strokes = Array.from({ length: seriesCount }, () => 2);

        const unitFormat = (val, type) => {
            if (val === null || typeof val === 'undefined' || isNaN(val)) return '';
            switch (type) {
                case 'temperature':
                    return `${val.toFixed(1)}°C`;
                case 'humidity':
                    return `${val.toFixed(0)}%`;
                case 'pressure':
                    return `${val.toFixed(2)} bar`;
                default:
                    return String(val);
            }
        };

        const axisForType = (type) => {
            if (type === 'temperature') {
                return {
                    title: { text: 'Temperatura' },
                    labels: { formatter: (value) => unitFormat(value, 'temperature') },
                    max: tempMaxUnified,
                    min: tempMinUnified,
                };
            }
            if (type === 'humidity') {
                return {
                    title: { text: 'Wilgotność' },
                    opposite: true,
                    max: function (maxDataValue) {
                        if (typeof maxDataValue === 'undefined' || maxDataValue === null) return 100;
                        return Math.min(maxDataValue + 5, 100);
                    },
                    min: function (minDataValue) {
                        if (typeof minDataValue === 'undefined' || minDataValue === null) return 0;
                        return Math.max(minDataValue - 5, 0);
                    },
                    labels: { formatter: (value) => unitFormat(value, 'humidity') },
                };
            }
            if (type === 'pressure') {
                return {
                    title: { text: 'Ciśnienie [bar]' },
                    opposite: true,
                    max: 3,
                    min: 0,
                    labels: { formatter: (value) => unitFormat(value, 'pressure') },
                };
            }
            return {
                labels: { formatter: (value) => unitFormat(value, 'generic') },
            };
        };

        // Deduplicate temperature axis: one shared temp axis mapped to all temperature series
        // Build seriesName arrays to explicitly map each axis to its series
        let yaxes = [];
        const hasType = (t) => types.includes(t);

        const tempSeriesNames = chartData.series
            .map((s, i) => (types[i] === 'temperature' ? s.name : null))
            .filter(Boolean);
        const humiditySeriesNames = chartData.series
            .map((s, i) => (types[i] === 'humidity' ? s.name : null))
            .filter(Boolean);
        const pressureSeriesNames = chartData.series
            .map((s, i) => (types[i] === 'pressure' ? s.name : null))
            .filter(Boolean);
        const genericSeriesNames = chartData.series
            .map((s, i) => (types[i] === 'generic' ? s.name : null))
            .filter(Boolean);

        if (tempSeriesNames.length > 0) {
            yaxes.push({ ...axisForType('temperature'), seriesName: tempSeriesNames });
        }
        if (humiditySeriesNames.length > 0) {
            yaxes.push({ ...axisForType('humidity'), seriesName: humiditySeriesNames });
        }
        if (pressureSeriesNames.length > 0) {
            yaxes.push({ ...axisForType('pressure'), seriesName: pressureSeriesNames });
        }
        // Any generic series: give each its own axis
        genericSeriesNames.forEach((name) => {
            yaxes.push({ ...axisForType('generic'), seriesName: [name] });
        });

        // Fallback: if for some reason no axes were added, keep a default generic axis
        if (yaxes.length === 0) {
            yaxes = [{ labels: { formatter: (v) => unitFormat(v, 'generic') } }];
        }

        const options = {
            chart: {
                type: "line",
                fontFamily: "inherit",
                height: 400,
                parentHeightOffset: 0,
                toolbar: { show: false },
                animations: { enabled: true },
            },
            stroke: {
                width: strokes, // Szerokość linii dla każdej serii
                curve: "smooth",
            },
            series: chartData.series,
            tooltip: {
                theme: "dark",
                x: { format: 'dd MMM, HH:mm' },
                y: {
                    formatter: function(val, opts) {
                        const idx = opts?.seriesIndex ?? 0;
                        const type = types[idx] || 'generic';
                        return unitFormat(val, type);
                    }
                }
            },
            grid: {
                padding: { top: -20, right: 0, left: -4, bottom: -4 },
                strokeDashArray: 4,
            },
            dataLabels: { enabled: false },
            xaxis: {
                type: 'datetime',
                labels: {
                    padding: 0,
                    format: 'HH:mm',
                    datetimeUTC: false,
                },
                tooltip: { enabled: false },
            },
            yaxis: yaxes,
            colors: colors,
            legend: {
                show: true,
                position: 'bottom',
                horizontalAlign: 'center'
            },
            markers: {
                size: 0,
                hover: { sizeOffset: 1 }
            },
        };

        chartElement.innerHTML = "";

        if (chart === null) {
            chart = new ApexCharts(chartElement, options);
            chart.render();
        } else {
            chart.updateOptions(options);
        }
    };

    /**
     * Aktualizuje stan przycisku "następny dzień".
     */
    const updateNextButtonState = () => {
        const today = new Date();
        const currentDate = new Date(datePicker.value);
        today.setHours(0, 0, 0, 0);
        currentDate.setHours(0, 0, 0, 0);
        nextDayBtn.disabled = currentDate >= today;
    };

    /**
     * Zmienia datę w polu datePicker i odświeża wykres.
     * @param {number} days - Liczba dni do dodania/odjęcia.
     */
    const changeDate = (days) => {
        const currentDate = new Date(datePicker.value);
        currentDate.setDate(currentDate.getDate() + days);
        datePicker.value = currentDate.toISOString().split('T')[0];

        // Ręczne wywołanie zdarzenia 'change', aby zaktualizować wykres
        datePicker.dispatchEvent(new Event('change', { 'bubbles': true }));
    };

    // --- INICJALIZACJA ---
    if (window.ApexCharts && datePicker && chartElement && locationSlug) {
        // Nasłuchuj na zmiany w polu daty
        datePicker.addEventListener('change', (event) => {
            const newDate = event.target.value;

            // Aktualizuj URL bez przeładowywania strony
            const url = new URL(window.location.href);
            url.searchParams.set('date', newDate);
            window.history.pushState({path: url.href}, '', url.href);

            updateNextButtonState();
            loadAndRenderChart(newDate, locationSlug);
        });

        // Nasłuchiwanie na przyciski nawigacji
        if (prevDayBtn && nextDayBtn) {
            prevDayBtn.addEventListener('click', () => changeDate(-1));
            nextDayBtn.addEventListener('click', () => changeDate(1));
        }

        // Inicjalizacja stanu przycisku i załadowanie wykresu
        updateNextButtonState();
        loadAndRenderChart(datePicker.value, locationSlug);

    } else {
        let errorMsg = "Nie można zainicjować wykresu. ";
        if (!window.ApexCharts) errorMsg += "Brakuje biblioteki ApexCharts. ";
        if (!chartElement) errorMsg += "Brakuje elementu #location-chart. ";
        if (!locationSlug) errorMsg += "Brakuje atrybutu data-location-slug. ";
        if (!datePicker) errorMsg += "Brakuje elementu #location_date. ";
        console.error(errorMsg);
        if(chartElement) {
            chartElement.innerHTML = `<div class="text-center p-5 text-danger">Błąd konfiguracji strony. Skontaktuj się z administratorem.</div>`;
        }
    }
});
