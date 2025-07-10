document.addEventListener("DOMContentLoaded", function () {
    // Adres URL, pod którym backend udostępnia dane do wykresu
    const dataUrl = "/data/temp?group=heating";

    /**
     * Asynchroniczna funkcja do pobierania surowych danych z backendu.
     * @param {string} url - Adres URL endpointu API.
     * @returns {Promise<Object|null>} - Obiekt z danymi z backendu lub null w przypadku błędu.
     */
    const fetchChartData = async (url) => {
        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`Błąd HTTP! Status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error("Nie udało się pobrać danych do wykresu:", error);
            const chartElement = document.getElementById("chart-temperature");
            if (chartElement) {
                chartElement.innerHTML = "Wystąpił błąd podczas ładowania danych.";
            }
            return null;
        }
    };

    /**
     * Przekształca dane z formatu backendu na format wymagany przez ApexCharts dla osi datetime.
     * @param {Object} rawData - Surowe dane z backendu.
     * @returns {{series: Array<Object>}} - Obiekt z seriami danych gotowymi dla wykresu.
     */
    const transformDataForChart = (rawData) => {
        const chartSeries = [];

        // Iterujemy po kluczach obiektu (np. "bufor-solary", "bufor")
        for (const seriesName in rawData) {
            if (Object.hasOwnProperty.call(rawData, seriesName)) {
                const dataPoints = rawData[seriesName];

                // Mapujemy dane do formatu {x: timestamp, y: value}
                // ApexCharts użyje timestampu do umiejscowienia punktu na osi czasu.
                const seriesData = dataPoints.map(point => {
                    return {
                        x: new Date(point.datetime).getTime(), // Konwertujemy datę na timestamp (milisekundy)
                        y: point.value
                    };
                });

                chartSeries.push({
                    name: seriesName,
                    data: seriesData
                });
            }
        }
        return { series: chartSeries };
    };


    /**
     * Inicjalizuje i renderuje wykres ApexCharts z podanymi danymi.
     * @param {Object} chartData - Obiekt zawierający dane `series`.
     */
    const initChart = (chartData) => {
        if (!chartData || !chartData.series || chartData.series.length === 0) {
            console.warn("Brak danych do wyrenderowania wykresu.");
            const chartElement = document.getElementById("chart-temperature");
            if (chartElement) {
                chartElement.innerHTML = "Brak danych do wyświetlenia.";
            }
            return;
        }

        const options = {
            chart: {
                type: "line",
                fontFamily: "inherit",
                height: 400,
                parentHeightOffset: 0,
                toolbar: {
                    show: false,
                },
                animations: {
                    enabled: true,
                },
            },
            stroke: {
                width: 2,
                lineCap: "round",
                curve: "smooth",
            },
            series: chartData.series,
            tooltip: {
                theme: "dark",
                x: {
                    // Formatowanie daty w podpowiedzi (tooltip)
                    format: 'dd MMM, HH:mm'
                }
            },
            grid: {
                padding: {
                    top: -20,
                    right: 0,
                    left: -4,
                    bottom: -4,
                },
                strokeDashArray: 4,
            },
            dataLabels: {
                enabled: false,
            },
            xaxis: {
                type: 'datetime',
                labels: {
                    padding: 0,
                    format: 'HH:mm', // Format wyświetlania etykiet (Godzina:Minuta)
                    datetimeUTC: false, // Ważne, aby wyświetlać czas w lokalnej strefie czasowej przeglądarki
                },
                tooltip: {
                    enabled: false,
                },
            },
            yaxis: {
                labels: {
                    padding: 4,
                    formatter: (value) => {
                        if (value === null) return '';
                        return value.toFixed(1) + "°C"
                    }
                },
            },
            colors: ["var(--tblr-primary)", "var(--tblr-orange)", "var(--tblr-green)"],
            legend: {
                show: true,
                position: 'bottom',
                horizontalAlign: 'center'
            },
            markers: {
                size: 1, // ZMIANA: Zmniejszenie rozmiaru punktów pomiarowych
                hover: {
                    sizeOffset: 3 // Opcjonalnie: Powiększ punkt przy najechaniu myszką
                }
            },
        };

        new ApexCharts(document.getElementById("chart-temperature"), options).render();
    };

    // Główna funkcja uruchamiająca proces
    const main = async () => {
        if (!window.ApexCharts) {
            console.error("Biblioteka ApexCharts nie została załadowana.");
            return;
        }

        const rawData = await fetchChartData(dataUrl);
        if (rawData) {
            const chartData = transformDataForChart(rawData);
            initChart(chartData);
        }
    };

    // Uruchomienie
    main();
});
