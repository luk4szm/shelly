document.addEventListener("DOMContentLoaded", function () {
    // --- ELEMENTY DOM ---
    const datePicker = document.getElementById('heating_date');
    const chartElement = document.getElementById('chart-temperature');

    // --- STAN ---
    // Zmienna do przechowywania instancji wykresu, aby można było ją aktualizować
    let chart = null;

    /**
     * Asynchroniczna funkcja do pobierania danych z backendu dla podanej daty.
     * @param {string} dateString - Data w formacie YYYY-MM-DD.
     * @returns {Promise<Object|null>} - Obiekt z danymi lub null w przypadku błędu.
     */
    const fetchChartData = async (dateString) => {
        const url = `/data/temp?group=heating&date=${dateString}`;

        // Wyświetl komunikat o ładowaniu
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
            console.error(`Nie udało się pobrać danych dla daty ${dateString}:`, error);
            if (chartElement) {
                chartElement.innerHTML = "Wystąpił błąd podczas ładowania danych.";
            }
            return null;
        }
    };

    /**
     * Przekształca dane z formatu backendu na format wymagany przez ApexCharts.
     * @param {Object} rawData - Surowe dane z backendu.
     * @returns {{series: Array<Object>}} - Obiekt z seriami danych.
     */
    const transformDataForChart = (rawData) => {
        const chartSeries = [];
        for (const seriesName in rawData) {
            if (Object.hasOwnProperty.call(rawData, seriesName)) {
                const dataPoints = rawData[seriesName];
                const seriesData = dataPoints.map(point => ({
                    x: new Date(point.datetime).getTime(),
                    y: point.value
                }));
                chartSeries.push({
                    name: seriesName,
                    data: seriesData
                });
            }
        }
        return { series: chartSeries };
    };

    /**
     * Główna funkcja, która pobiera dane, a następnie renderuje lub aktualizuje wykres.
     * @param {string} dateString - Data w formacie YYYY-MM-DD.
     */
    const loadAndRenderChart = async (dateString) => {
        if (!dateString) {
            console.error("Nie podano daty do załadowania wykresu.");
            return;
        }

        const rawData = await fetchChartData(dateString);

        // Jeśli pobieranie danych nie powiodło się lub zwrócono pusty obiekt
        if (!rawData || Object.keys(rawData).length === 0) {
            // Jeśli wykres istnieje, zniszcz go, aby wyczyścić obszar
            if (chart) {
                chart.destroy();
                chart = null;
            }
            chartElement.innerHTML = "Brak danych do wyświetlenia dla wybranego dnia.";
            return;
        }

        const chartData = transformDataForChart(rawData);

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
                width: 2,
                lineCap: "round",
                curve: "smooth",
            },
            series: chartData.series,
            tooltip: {
                theme: "dark",
                x: { format: 'dd MMM, HH:mm' }
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
            yaxis: {
                labels: {
                    padding: 4,
                    formatter: (value) => {
                        if (value === null) return '';
                        return value.toFixed(1) + "°C";
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
                size: 0,
                hover: { sizeOffset: 1 }
            },
        };

        chartElement.innerHTML = "";

        // Jeśli wykres nie został jeszcze utworzony, stwórz go
        if (chart === null) {
            chart = new ApexCharts(chartElement, options);
            chart.render();
        }
        // Jeśli wykres już istnieje, zaktualizuj go nowymi danymi i opcjami
        else {
            chart.updateOptions(options);
        }
    };

    // --- INICJALIZACJA ---
    if (window.ApexCharts && datePicker && chartElement) {
        // Nasłuchuj na zmiany w polu daty
        datePicker.addEventListener('change', (event) => {
            loadAndRenderChart(event.target.value);
        });

        // Załaduj wykres dla domyślnie ustawionej daty przy pierwszym otwarciu strony
        loadAndRenderChart(datePicker.value);
    } else {
        console.error("Nie można zainicjować wykresu. Brakuje biblioteki ApexCharts lub kluczowych elementów HTML.");
    }
});
