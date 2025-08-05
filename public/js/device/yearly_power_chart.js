document.addEventListener('DOMContentLoaded', function () {
    const chartElement = document.getElementById('chart-power');
    if (!chartElement) {
        return;
    }
    const deviceName = chartElement.dataset.deviceName;

    // --- Navigation Logic ---
    const dateInput = document.getElementById('heating_date');
    const prevYearBtn = document.getElementById('prev-year-btn');
    const nextYearBtn = document.getElementById('next-year-btn');

    /**
     * Reloads the page with a new year in the URL parameter.
     * @param {string} yearString - The year in YYYY format
     */
    function navigateToYear(yearString) {
        const url = new URL(window.location);
        url.searchParams.set('date', yearString);
        window.location.href = url.toString();
    }

    if (dateInput && prevYearBtn && nextYearBtn) {
        prevYearBtn.addEventListener('click', () => {
            const currentYear = parseInt(dateInput.value, 10);
            if (!isNaN(currentYear)) {
                navigateToYear(currentYear - 1);
            }
        });

        nextYearBtn.addEventListener('click', () => {
            const currentYear = parseInt(dateInput.value, 10);
            const maxYear = new Date().getFullYear(); // Do not navigate to the future
            if (!isNaN(currentYear) && currentYear < maxYear) {
                navigateToYear(currentYear + 1);
            }
        });
    }

    // --- Chart Logic ---
    const yearlyDataRaw = chartElement.dataset.yearlyData;
    if (!yearlyDataRaw) {
        chartElement.innerHTML = '<div class="text-muted text-center pt-5">Brak danych do wyświetlenia dla wybranego roku.</div>';
        return;
    }

    const yearlyData = JSON.parse(yearlyDataRaw);

    if (Object.keys(yearlyData).length === 0) {
        chartElement.innerHTML = '<div class="text-muted text-center pt-5">Brak danych do wyświetlenia dla wybranego roku.</div>';
        return;
    }

    // Create a complete list of months for the year
    const year = parseInt(dateInput.value, 10);
    const monthNames = Array.from({length: 12}, (_, i) => {
        const date = new Date(year, i, 15);
        return date.toLocaleString('pl-PL', { month: 'long' });
    });


    // Map data to the full list of months
    const energyData = Array.from({length: 12}, (_, i) => {
        return yearlyData[i] ? parseFloat((yearlyData[i].energy / 1000).toFixed(2)) : 0;
    });
    const timeData = Array.from({length: 12}, (_, i) => {
        return yearlyData[i] ? parseFloat((yearlyData[i].time / 3600).toFixed(2)) : 0;
    });
    const inclusionsData = Array.from({length: 12}, (_, i) => {
        return yearlyData[i] ? yearlyData[i].inclusions : 0;
    });
    const gasData = Array.from({length: 12}, (_, i) => {
        return yearlyData[i] && yearlyData[i].gas ? parseFloat(yearlyData[i].gas.toFixed(2)) : 0;
    });

    const hasGasData = gasData.some(d => d > 0);

    const chartOptions = {
        series: [
            { name: 'Zużyta energia', type: 'column', data: energyData },
            { name: 'Czas pracy', type: 'column', data: timeData },
            { name: 'Liczba włączeń', type: 'column', data: inclusionsData }
        ],
        chart: {
            height: 400,
            type: 'line',
            stacked: false,
            zoom: { enabled: false },
            toolbar: { show: false },
        },
        stroke: {
            width: [0, 0, 3],
            curve: 'smooth'
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            categories: monthNames,
            title: {
                text: 'Miesiąc'
            }
        },
        yaxis: [
            {
                seriesName: 'Zużyta energia',
                axisTicks: { show: true },
                axisBorder: { show: true, color: '#008FFB' },
                labels: {
                    formatter: (val) => val.toFixed(1) + ' kWh',
                    style: { colors: '#008FFB' }
                },
                title: {
                    text: "Zużyta energia (kWh)",
                    style: { color: '#008FFB' }
                },
            },
            {
                seriesName: 'Czas pracy',
                opposite: true,
                axisTicks: { show: true },
                axisBorder: { show: true, color: '#00E396' },
                labels: {
                    formatter: (val) => val.toFixed(1) + ' h',
                    style: { colors: '#00E396' }
                },
                title: {
                    text: "Czas pracy (h)",
                    style: { color: '#00E396' }
                },
            },
            {
                seriesName: 'Liczba włączeń',
                opposite: true,
                axisTicks: { show: true },
                axisBorder: { show: true, color: '#FEB019' },
                labels: {
                    formatter: (val) => val.toFixed(0),
                    style: { colors: '#FEB019' }
                },
                title: {
                    text: "Liczba włączeń",
                    style: { color: '#FEB019' }
                },
            }
        ],
        tooltip: {
            x: {
                formatter: function(val, { series, seriesIndex, dataPointIndex, w }) {
                    return w.globals.labels[dataPointIndex] + " " + year;
                }
            },
            shared: true,
            intersect: false,
        },
        legend: {
            position: 'top',
            horizontalAlign: 'center'
        },
        noData: {
            text: 'Brak danych do wyświetlenia...',
        }
    };

    if (hasGasData) {
        chartOptions.series.push({
            name: 'Zużycie gazu',
            type: 'column',
            data: gasData
        });

        chartOptions.yaxis.push({
            seriesName: 'Zużycie gazu',
            axisTicks: { show: true },
            axisBorder: { show: true, color: '#FF4560' },
            labels: {
                formatter: (val) => val.toFixed(2) + ' m³',
                style: { colors: '#FF4560' }
            },
            title: {
                text: "Zużycie gazu (m³)",
                style: { color: '#FF4560' }
            },
        });

        chartOptions.stroke.width.push(3);
    }

    const chart = new ApexCharts(chartElement, chartOptions);
    chart.render();
    chart.hideSeries('Liczba włączeń');
    if (deviceName === 'piec') {
        chart.hideSeries('Zużyta energia');
    }
});
