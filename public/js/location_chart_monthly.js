document.addEventListener("DOMContentLoaded", function () {
    const chartElement = document.getElementById('location-chart');
    const datePicker = document.getElementById('location_date');
    const locationSlug = chartElement ? chartElement.dataset.locationSlug : null;
    const chartType = chartElement ? chartElement.dataset.chartType : 'monthly';

    // Przyciski nawigacji daty
    const prevMonthBtn = document.getElementById('prev-month-btn');
    const nextMonthBtn = document.getElementById('next-month-btn');

    let chart = null;

    /**
     * Asynchroniczna funkcja do pobierania danych (temperatury i wilgotności) z backendu.
     * @param {string} dateString - Data w formacie YYYY-MM.
     * @param {string} slug - Identyfikator lokalizacji.
     * @returns {Promise<Object|null>} - Obiekt z danymi lub null w przypadku błędu.
     */
    const fetchChartData = async (dateString, slug) => {
        // Endpoint zwraca dane dla temperatury i wilgotności dla danej lokalizacji i daty
        const url = `/location/${slug}/get-data?type=${chartType}&date=${dateString}`;

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
     * Przekształca surowe dane na format wymagany przez ApexCharts dla wykresu rangeBar.
     * @param {Object} rawData - Surowe dane z backendu (np. { "temperature": [...], "humidity": [...] }).
     * @returns {{series: Array<Object>}} - Obiekt z seriami danych.
     */
    const transformDataForChart = (rawData) => {
        const prettyName = (key) => {
            const map = {
                temperature: 'Temperatura',
                humidity: 'Wilgotność',
                pressure: 'Ciśnienie',
            };
            if (map[key]) return map[key];
            // Fallback: humanizuj klucz
            return key
                .replace(/_/g, ' ')
                .replace(/\btemp(erature)?\b/i, 'Temperatura')
                .replace(/\bhumidity\b/i, 'Wilgotność')
                .replace(/\bpressure\b/i, 'Ciśnienie');
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
        let tempAvgPoints = [];

        for (const key of Object.keys(rawData)) {
            const dataPoints = rawData[key] || [];
            const t = detectType(key);
            const seriesData = dataPoints.map(point => ({
                x: point.date, // 'YYYY-MM-DD'
                y: [point.min, point.max]
            }));

            // Jeśli to seria temperatury i punkty mają 'avg', zbuduj serię liniową dla średniej
            if (t === 'temperature') {
                const avgCandidates = dataPoints
                    .filter(p => typeof p.avg === 'number' && !isNaN(p.avg))
                    .map(p => ({ x: p.date, y: p.avg }));
                if (avgCandidates.length > 0) {
                    tempAvgPoints = avgCandidates;
                }
            }

            chartSeries.push({
                name: prettyName(key),
                data: seriesData
            });
            seriesTypes.push(t);
        }

        // Dodaj linię średniej temperatury jako osobną serię (przerywana)
        if (tempAvgPoints.length > 0) {
            chartSeries.push({
                name: 'Temperatura (średnia)',
                data: tempAvgPoints,
                type: 'line',
                _isAvg: true, // niestandardowa flaga do stylowania (zignorowana przez ApexCharts)
            });
            seriesTypes.push('temperature'); // mapuj na oś temperatury
        }

        return { series: chartSeries, types: seriesTypes };
    };

    /**
     * Główna funkcja, która pobiera dane i renderuje lub aktualizuje wykres.
     * @param {string} dateString - Data w formacie YYYY-MM.
     * @param {string} slug - Identyfikator lokalizacji.
     */
    const loadAndRenderChart = async (dateString, slug) => {
        if (!dateString || !slug) {
            console.error("Nie podano daty lub lokalizacji do załadowania wykresu.");
            return;
        }

        const rawData = await fetchChartData(dateString, slug);

        if (!rawData || Object.keys(rawData).length === 0 || Object.values(rawData).every(arr => arr.length === 0)) {
            if (chart) {
                chart.destroy();
                chart = null;
            }
            chartElement.innerHTML = '<div class="text-center p-5">Brak danych do wyświetlenia dla wybranego miesiąca.</div>';
            return;
        }

        const chartData = transformDataForChart(rawData);

        // Przygotuj dynamiczne ustawienia dla serii
        const seriesCount = chartData.series.length;
        const types = chartData.types || [];

        // Wyznacz globalny zakres dla wszystkich serii temperatury (ujednolicona skala Y)
        let tempAllValues = [];
        let humidityAllValues = [];
        chartData.series.forEach((s, idx) => {
            const t = types[idx];
            (s.data || []).forEach(point => {
                if (point && Array.isArray(point.y)) {
                    const vmin = point.y[0];
                    const vmax = point.y[1];
                    if (typeof vmin === 'number' && !isNaN(vmin)) {
                        if (t === 'temperature') tempAllValues.push(vmin);
                        if (t === 'humidity') humidityAllValues.push(vmin);
                    }
                    if (typeof vmax === 'number' && !isNaN(vmax)) {
                        if (t === 'temperature') tempAllValues.push(vmax);
                        if (t === 'humidity') humidityAllValues.push(vmax);
                    }
                }
            });
        });
        const hasTemps = tempAllValues.length > 0;
        const tempMinRaw = hasTemps ? Math.min(...tempAllValues) : null;
        const tempMaxRaw = hasTemps ? Math.max(...tempAllValues) : null;
        // Dodaj niewielki bufor do zakresu
        const tempMinUnified = hasTemps ? Math.floor(tempMinRaw - 2) : 0;
        const tempMaxUnified = hasTemps ? Math.ceil(tempMaxRaw + 2) : 40;

        // Wyznacz globalny zakres dla wilgotności (stabilna skala dla wąskich zakresów)
        const hasHum = humidityAllValues.length > 0;
        let humMin = hasHum ? Math.min(...humidityAllValues) : 0;
        let humMax = hasHum ? Math.max(...humidityAllValues) : 100;
        // Zabezpieczenie przed zbyt małą rozpiętością: min. 2 pp
        if (hasHum && humMax - humMin < 2) {
            const mid = (humMax + humMin) / 2;
            humMin = mid - 1;
            humMax = mid + 1;
        }
        // Dodaj niewielki bufor i ogranicz do [0, 100]
        if (hasHum) {
            humMin = Math.max(0, Math.floor(humMin - 2));
            humMax = Math.min(100, Math.ceil(humMax + 2));
        }

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
                    max: hasHum ? humMax : 100,
                    min: hasHum ? humMin : 0,
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
                type: "rangeBar",
                fontFamily: "inherit",
                height: 400,
                parentHeightOffset: 0,
                toolbar: { show: false },
                animations: { enabled: true },
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '80%',
                }
            },
            series: chartData.series,
            stroke: {
                // 0 dla słupków (rangeBar), 2 dla linii
                width: chartData.series.map(s => (s.type === 'line' ? 2 : 0)),
                // przerywana linia tylko dla serii średniej
                dashArray: chartData.series.map(s => (s._isAvg ? 6 : 0)),
                curve: 'straight',
            },
            markers: {
                size: 0,
                hover: { size: 0 },
                strokeWidth: 0
            },
            tooltip: {
                theme: "dark",
                x: { format: 'dd MMM yyyy' },
                y: {
                    formatter: function(val, opts) {
                        const idx = opts?.seriesIndex ?? 0;
                        const type = types[idx] || 'generic';
                        const series = opts.w.config.series[idx];
                        const dataPoint = series.data[opts.dataPointIndex];
                        if (dataPoint && Array.isArray(dataPoint.y)) {
                            const min = unitFormat(dataPoint.y[0], type);
                            const max = unitFormat(dataPoint.y[1], type);
                            return `${min} &mdash; ${max}`;
                        }
                        return unitFormat(val, type); // Fallback (np. dla linii średniej)
                    }
                }
            },
            grid: {
                padding: { top: -20, right: 0, left: -4, bottom: -4 },
                strokeDashArray: 4,
                xaxis: { lines: { show: true } }
            },
            dataLabels: { enabled: false },
            xaxis: {
                type: 'datetime',
                labels: {
                    padding: 0,
                    format: 'dd',
                    datetimeUTC: false,
                },
                tooltip: { enabled: false },
            },
            yaxis: yaxes,
            colors: colors,
            legend: {
                show: seriesCount > 1,
                position: 'bottom',
                horizontalAlign: 'center'
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
     * Aktualizuje stan przycisku "następny miesiąc".
     */
    const updateNextButtonState = () => {
        const today = new Date();
        const currentDate = new Date(datePicker.value + '-01'); // Użyj pierwszego dnia miesiąca do porównania

        const isSameOrFutureMonth = currentDate.getFullYear() > today.getFullYear() ||
            (currentDate.getFullYear() === today.getFullYear() && currentDate.getMonth() >= today.getMonth());

        if (nextMonthBtn) {
            nextMonthBtn.disabled = isSameOrFutureMonth;
        }
    };

    /**
     * Zmienia datę w polu datePicker i odświeża wykres.
     * @param {number} months - Liczba miesięcy do dodania/odjęcia.
     */
    const changeMonth = (months) => {
        const currentDate = new Date(datePicker.value + '-01');
        currentDate.setMonth(currentDate.getMonth() + months);

        const year = currentDate.getFullYear();
        const month = (currentDate.getMonth() + 1).toString().padStart(2, '0');
        datePicker.value = `${year}-${month}`;

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
        if (prevMonthBtn && nextMonthBtn) {
            prevMonthBtn.addEventListener('click', () => changeMonth(-1));
            nextMonthBtn.addEventListener('click', () => changeMonth(1));
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
