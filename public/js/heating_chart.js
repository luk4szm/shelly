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
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('heating_date');
    const prevDayBtn = document.getElementById('prev-day-btn');
    const nextDayBtn = document.getElementById('next-day-btn');

    // Funkcja do formatowania daty do stringa YYYY-MM-DD
    const formatDate = (date) => {
        return date.toISOString().split('T')[0];
    };

    // Funkcja do aktualizacji stanu przycisku "następny dzień"
    const updateNextButtonState = () => {
        const today = new Date();
        const currentDate = new Date(dateInput.value);

        // Ustawiamy godziny na 0, aby porównywać tylko daty
        today.setHours(0, 0, 0, 0);
        currentDate.setHours(0, 0, 0, 0);

        // Wyłącz przycisk "następny", jeśli wybrana data to dzisiaj lub data z przyszłości
        nextDayBtn.disabled = currentDate >= today;
    };

    // Funkcja do zmiany daty o podaną liczbę dni
    const changeDate = (days) => {
        const currentDate = new Date(dateInput.value);
        currentDate.setDate(currentDate.getDate() + days);
        dateInput.value = formatDate(currentDate);

        // Zaktualizuj stan przycisku po zmianie daty
        updateNextButtonState();

        // Wywołaj zdarzenie 'change', aby inne skrypty (np. odświeżanie wykresu) mogły zareagować
        dateInput.dispatchEvent(new Event('change', {'bubbles': true}));
    };

    // Dodanie nasłuchiwania na zdarzenia
    prevDayBtn.addEventListener('click', () => changeDate(-1));
    nextDayBtn.addEventListener('click', () => changeDate(1));
    dateInput.addEventListener('change', updateNextButtonState);

    // Sprawdzenie stanu przycisku przy pierwszym załadowaniu strony
    updateNextButtonState();
});
