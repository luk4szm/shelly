function initializeTemperatureCharts() {
    document.querySelectorAll('.chart-temperature').forEach(chartElement => {
        if (chartElement.querySelector('svg')) {
            return;
        }

        const chartId = chartElement.id;
        if (!chartId) {
            console.error("Chart element is missing an ID.");
            return;
        }

        const locationName = chartId.replace('chart-temp-', '');

        $.ajax({
            url: '/data/temp',
            type: 'GET',
            dataType: 'json',
            data: {'location': locationName},
            success: function(response) {
                if (!Array.isArray(response) || response.length === 0) {
                    console.warn(`Brak danych dla wykresu "${locationName}".`);
                    chartElement.innerHTML = `<div class="text-center text-muted small p-3">Brak danych do wyświetlenia.</div>`;
                    return;
                }

                const chartData = response.map(item => {
                    return [new Date(item.datetime).getTime(), item.value];
                });

                renderChart(chartElement, chartData);
            },
            error: function(xhr, status, error) {
                console.error(`Nie udało się pobrać danych dla wykresu "${locationName}":`, error);
                chartElement.innerHTML = `<div class="text-center text-muted small p-3">Błąd ładowania danych wykresu.</div>`;
            }
        });
    });
}

/**
 * Funkcja pomocnicza do renderowania pojedynczego wykresu ApexCharts.
 * @param {HTMLElement} chartElement - Element DOM, w którym ma być renderowany wykres.
 * @param {Array} chartData - Przetworzone dane w formacie [[timestamp, value], ...].
 */
function renderChart(chartElement, chartData) {
    const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--tblr-primary').trim();

    const options = {
        chart: {
            type: "area",
            fontFamily: "inherit",
            height: 40,
            sparkline: {
                enabled: true,
            },
            animations: {
                enabled: false,
                // easing: 'easeinout',
                // speed: 800,
            },
        },
        dataLabels: {
            enabled: false,
        },
        fill: {
            opacity: .16,
            type: 'solid'
        },
        stroke: {
            width: 2,
            lineCap: "round",
            curve: "smooth",
        },
        series: [{
            name: "Temperatura",
            data: chartData,
        }],
        tooltip: {
            theme: "dark",
            x: {
                format: 'dd MMM yyyy, HH:mm'
            }
        },
        grid: {
            strokeDashArray: 4,
        },
        xaxis: {
            type: 'datetime',
            labels: { show: false },
            axisTicks: { show: false },
            tooltip: { enabled: false },
            axisBorder: { show: false },
        },
        yaxis: {
            labels: { show: false }
        },
        colors: [primaryColor],
        legend: {
            show: false,
        },
    };

    new ApexCharts(chartElement, options).render();
}

document.addEventListener("DOMContentLoaded", function () {
    initializeTemperatureCharts();
});
