// Funkcja do generowania losowych danych dla wykresu
function generateRandomChartData(length, min, max) {
    const data = [];
    for (let i = 0; i < length; i++) {
        data.push(Math.floor(Math.random() * (max - min + 1)) + min);
    }
    return data;
}

// Główna funkcja inicjalizująca wszystkie wykresy temperatury
function initializeTemperatureCharts() {
    // Znajdź wszystkie elementy z klasą .chart-temperature
    document.querySelectorAll('.chart-temperature').forEach(chartElement => {
        // Sprawdzamy, czy w kontenerze istnieje już element <svg>.
        // Jeśli tak, oznacza to, że wykres został już zainicjowany.
        if (chartElement.querySelector('svg')) {
            return;
        }

        const chartId = chartElement.id;
        if (!chartId) return;

        // Upewniamy się, że biblioteki są dostępne
        if (window.ApexCharts && window.tabler && window.tabler.tabler) {
            new ApexCharts(document.getElementById(chartId), {
                chart: {
                    type: "area",
                    fontFamily: "inherit",
                    height: 40,
                    sparkline: {
                        enabled: true,
                    },
                    animations: {
                        enabled: false,
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
                    data: generateRandomChartData(30, 15, 30)
                }],
                tooltip: {
                    theme: "dark"
                },
                grid: {
                    strokeDashArray: 4,
                },
                xaxis: {
                    labels: {
                        padding: 0,
                    },
                    tooltip: {
                        enabled: false
                    },
                    axisBorder: {
                        show: false,
                    },
                    type: 'datetime',
                },
                yaxis: {
                    labels: {
                        padding: 4
                    }
                },
                labels: (function () {
                    const dates = [];
                    for (let i = 29; i >= 0; i--) {
                        const d = new Date();
                        d.setDate(d.getDate() - i);
                        dates.push(d.toISOString().split('T')[0]);
                    }
                    return dates;
                })(),
                // ########################
                // ### KLUCZOWA POPRAWKA ###
                // ########################
                colors: [window.tabler.tabler.getColor("primary")],
                legend: {
                    show: false,
                },
            }).render();
        } else {
            console.error("Biblioteka ApexCharts lub Tabler nie jest dostępna.");
        }
    });
}

// Wywołaj funkcję po pierwszym załadowaniu strony
document.addEventListener("DOMContentLoaded", function () {
    initializeTemperatureCharts();
});
