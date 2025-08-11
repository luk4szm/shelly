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
        // Mapa kluczy z backendu na nazwy wyświetlane w legendzie wykresu
        const seriesNameMapping = {
            temperature: 'Temperatura',
            humidity: 'Wilgotność'
        };

        const chartSeries = [];

        // Iteruj po zmapowanych kluczach, aby zachować spójną kolejność serii
        for (const key of Object.keys(seriesNameMapping)) {
            if (Object.hasOwnProperty.call(rawData, key)) {
                const dataPoints = rawData[key];
                const seriesData = dataPoints.map(point => ({
                    x: new Date(point.datetime).getTime(),
                    y: point.value
                }));
                chartSeries.push({
                    name: seriesNameMapping[key], // Użyj nazwy z mapy
                    data: seriesData
                });
            }
        }
        return { series: chartSeries };
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
                width: [2, 2], // Szerokość linii dla każdej serii
                curve: "smooth",
            },
            series: chartData.series,
            tooltip: {
                theme: "dark",
                x: { format: 'dd MMM, HH:mm' },
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
            yaxis: [
                {
                    seriesName: 'Temperatura',
                    title: {text: "Temperatura"},
                    labels: {
                        formatter: (value) => value ? value.toFixed(1) + "°C" : '',
                    },
                    max: function (maxDataValue) {
                        if (typeof maxDataValue === 'undefined' || maxDataValue === null) {
                            return 40; // Wartość domyślna
                        }
                        return maxDataValue + 2;
                    },
                    min: function (minDataValue) {
                        if (typeof minDataValue === 'undefined' || minDataValue === null) {
                            return 0; // Wartość domyślna
                        }
                        return minDataValue - 2;
                    },
                },
                {
                    seriesName: 'Wilgotność',
                    opposite: true, // Oś po prawej stronie
                    title: {text: "Wilgotność"},

                    // Dynamiczne maksimum, które nie przekroczy 100%
                    max: function (maxDataValue) {
                        // Jeśli nie ma danych, maxDataValue może być niezdefiniowane
                        if (typeof maxDataValue === 'undefined' || maxDataValue === null) {
                            return 100; // Wartość domyślna
                        }
                        // Dodaj bufor, ale nie przekraczaj 100
                        return Math.min(maxDataValue + 5, 100);
                    },

                    // NOWOŚĆ: Dynamiczne minimum, które nie będzie niższe niż 0%
                    min: function (minDataValue) {
                        if (typeof minDataValue === 'undefined' || minDataValue === null) {
                            return 0; // Wartość domyślna
                        }
                        // Odejmij bufor, ale nie schodź poniżej 0
                        return Math.max(minDataValue - 5, 0);
                    },

                    labels: {
                        formatter: (value) => value ? value.toFixed(0) + "%" : '',
                    },
                }
            ],
            colors: ["var(--tblr-red)", "var(--tblr-blue)"],
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
