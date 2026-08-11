document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) {
        console.error('Brak biblioteki ApexCharts.');
        return;
    }

    const dateInput = document.getElementById('location_date');
    const prevBtn = document.getElementById('prev-year-btn');
    const nextBtn = document.getElementById('next-year-btn');
    const chartToggle = document.getElementById('location-chart-type-toggle');
    const elTemp = document.getElementById('location-chart-temperature');
    const elHum = document.getElementById('location-chart-humidity');

    if (!dateInput || !prevBtn || !nextBtn || (!elTemp && !elHum)) {
        return;
    }

    const locationSlug = elTemp ? elTemp.dataset.locationSlug : elHum.dataset.locationSlug;

    let tempChart = null;
    let humChart = null;
    let currentGroup = 'weeks';

    const isValidYear = (value) => /^\d{4}$/.test(value);

    const updateUrlParam = (year) => {
        const url = new URL(window.location.href);
        if (isValidYear(year)) {
            url.searchParams.set('date', year);
        } else {
            url.searchParams.delete('date');
        }
        window.history.pushState({}, '', url.toString());
    };

    const checkNextButtonState = () => {
        if (!dateInput.value) {
            nextBtn.disabled = true;
            return;
        }

        const maxYear = parseInt(dateInput.getAttribute('max'), 10);
        const currentYear = parseInt(dateInput.value, 10);
        nextBtn.disabled = Number.isFinite(maxYear) && Number.isFinite(currentYear) && currentYear >= maxYear;
    };

    const changeYear = (delta) => {
        const baseYear = isValidYear(dateInput.value)
            ? parseInt(dateInput.value, 10)
            : new Date().getFullYear();
        const newYear = String(baseYear + delta);

        dateInput.value = newYear;
        updateUrlParam(newYear);
        checkNextButtonState();
        loadAll(newYear);
    };

    const fetchData = async (type, year, group) => {
        const url = new URL(`/location/${locationSlug}/get-yearly-data`, window.location.origin);
        url.searchParams.set('type', type);
        url.searchParams.set('group', group);
        if (isValidYear(year)) {
            url.searchParams.set('date', year);
        }

        try {
            const res = await fetch(url.toString(), {cache: 'no-store'});
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return await res.json();
        } catch (e) {
            console.error(`Błąd pobierania danych (${type}):`, e);
            return [];
        }
    };

    const transformToCandle = (rawData) => {
        if (!Array.isArray(rawData)) return [];

        return rawData.map(item => {
            const timestamp = new Date(item.period_start).getTime();
            const open = parseFloat(item.start_value);
            const high = parseFloat(item.max_value);
            const low = parseFloat(item.min_value);
            const close = parseFloat(item.end_value);

            if (Number.isNaN(timestamp) || Number.isNaN(open)) return null;

            return {
                x: timestamp,
                y: [open, high, low, close]
            };
        }).filter(Boolean).sort((a, b) => a.x - b.x);
    };

    const getRange = (year) => {
        if (isValidYear(year)) {
            const parsedYear = parseInt(year, 10);
            return {
                min: new Date(parsedYear, 0, 1).getTime(),
                max: new Date(parsedYear, 11, 31, 23, 59, 59).getTime()
            };
        }

        const now = new Date();
        return {
            min: new Date(now.getFullYear(), now.getMonth() - 11, 1, 0, 0, 0).getTime(),
            max: new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59).getTime()
        };
    };

    const renderChart = (element, instance, dataSeries, title, unit, year) => {
        if (!element) return null;

        if (dataSeries.length === 0) {
            if (instance) {
                instance.destroy();
            }
            element.innerHTML = '<div class="text-center p-4 text-muted">Brak danych dla wybranego roku.</div>';
            return null;
        }

        const range = getRange(year);
        const options = {
            chart: {
                type: 'candlestick',
                height: 350,
                toolbar: {show: false},
                animations: {enabled: true}
            },
            series: [{
                name: title,
                data: dataSeries
            }],
            stroke: {
                width: 1
            },
            xaxis: {
                type: 'datetime',
                min: range.min,
                max: range.max,
                tickAmount: currentGroup === 'weeks' ? 26 : 12,
                tickPlacement: 'on',
                labels: {
                    datetimeUTC: false,
                    rotate: currentGroup === 'weeks' ? -45 : 0,
                    datetimeFormatter: {
                        year: 'yyyy',
                        month: 'MMM yyyy',
                        day: 'dd MMM',
                        hour: 'HH:mm'
                    },
                    style: {
                        fontSize: '10px'
                    }
                },
                tooltip: {
                    enabled: false
                }
            },
            yaxis: {
                tooltip: {
                    enabled: true
                },
                labels: {
                    formatter: (val) => Number.isFinite(val) ? val.toFixed(unit === '%' ? 0 : 1) + ' ' + unit : ''
                }
            },
            plotOptions: {
                candlestick: {
                    wick: {
                        useFillColor: true
                    }
                }
            },
            tooltip: {
                theme: 'dark',
                x: {format: currentGroup === 'weeks' ? 'dd MMM yyyy' : 'MMM yyyy'},
                y: {
                    formatter: function (val) {
                        return val + ' ' + unit;
                    }
                },
                custom: function ({seriesIndex, dataPointIndex, w}) {
                    const open = w.globals.seriesCandleO[seriesIndex][dataPointIndex];
                    const high = w.globals.seriesCandleH[seriesIndex][dataPointIndex];
                    const low = w.globals.seriesCandleL[seriesIndex][dataPointIndex];
                    const close = w.globals.seriesCandleC[seriesIndex][dataPointIndex];

                    return `
                        <div class="apexcharts-tooltip-title" style="background: #202b33; color: #fff; font-family: Helvetica, Arial, sans-serif;">
                            ${new Date(w.globals.seriesX[seriesIndex][dataPointIndex]).toLocaleDateString()}
                        </div>
                        <div class="apexcharts-tooltip-text" style="padding: 8px;">
                            <div><strong>Początek:</strong> <span class="value">${open} ${unit}</span></div>
                            <div><strong>Max:</strong> <span class="value">${high} ${unit}</span></div>
                            <div><strong>Min:</strong> <span class="value">${low} ${unit}</span></div>
                            <div><strong>Koniec:</strong> <span class="value">${close} ${unit}</span></div>
                        </div>
                    `;
                }
            },
            grid: {
                strokeDashArray: 4
            }
        };

        element.innerHTML = '';
        if (instance) {
            instance.updateOptions(options, true, true);
            instance.updateSeries([{data: dataSeries}]);
            return instance;
        }

        const newChart = new ApexCharts(element, options);
        newChart.render();
        return newChart;
    };

    const loadAll = async (year) => {
        if (elTemp && !tempChart) elTemp.innerHTML = '<div class="text-center p-4">Ładowanie danych...</div>';
        if (elHum && !humChart) elHum.innerHTML = '<div class="text-center p-4">Ładowanie danych...</div>';

        const [tempData, humData] = await Promise.all([
            fetchData('temp', year, currentGroup),
            fetchData('humidity', year, currentGroup)
        ]);

        tempChart = renderChart(elTemp, tempChart, transformToCandle(tempData), 'Temperatura', '°C', year);
        humChart = renderChart(elHum, humChart, transformToCandle(humData), 'Wilgotność', '%', year);
    };

    prevBtn.addEventListener('click', () => changeYear(-1));
    nextBtn.addEventListener('click', () => changeYear(1));

    dateInput.addEventListener('change', (e) => {
        const year = e.target.value;
        if (isValidYear(year) || year === '') {
            updateUrlParam(year);
            checkNextButtonState();
            loadAll(year);
        }
    });

    chartToggle?.addEventListener('change', (e) => {
        currentGroup = e.target.value;
        loadAll(dateInput.value);
    });

    window.addEventListener('popstate', () => {
        const year = new URL(window.location.href).searchParams.get('date') || '';
        dateInput.value = isValidYear(year) ? year : '';
        checkNextButtonState();
        loadAll(dateInput.value);
    });

    checkNextButtonState();
    loadAll(dateInput.value);
});
