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
        // Zbierz punkty średniej temperatury (jeśli są w danych)
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
// ... existing code ...
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
            tooltip: {
                theme: "dark",
                x: { format: 'dd MMM yyyy' },
                y: {
                    formatter: function(val, opts) {
                        // Zabezpieczenie przed undefined
                        if (!opts || !opts.w || !opts.w.config) {
                            return unitFormat(val, 'generic');
                        }

                        const idx = opts.seriesIndex ?? 0;
                        const type = types[idx] || 'generic';
                        const series = opts.w.config.series[idx];

                        // Zabezpieczenie przed undefined dataPointIndex
                        if (typeof opts.dataPointIndex === 'undefined' || !series || !series.data) {
                            return unitFormat(val, type);
                        }

                        const dataPoint = series.data[opts.dataPointIndex];

                        // Dla rangeBar (słupków z zakresem) - wyświetl min-max
                        if (dataPoint && Array.isArray(dataPoint.y)) {
                            const min = unitFormat(dataPoint.y[0], type);
                            const max = unitFormat(dataPoint.y[1], type);
                            return `${min} &mdash; ${max}`;
                        }

                        // Fallback (np. dla linii średniej)
                        return unitFormat(val, type);
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
// ... existing code ...
