document.addEventListener("DOMContentLoaded", function () {
    // --- ELEMENTY DOM ---
    const datePicker = document.getElementById('heating_date');
    const chartElement = document.getElementById('chart-temperature');
    const prevDayBtn = document.getElementById('prev-day-btn');
    const nextDayBtn = document.getElementById('next-day-btn');

    // --- STAN ---
    let chart = null; // Instancja wykresu ApexCharts

    // --- LOGIKA PRZYCISKÓW DATY ---

    const formatDate = (date) => date.toISOString().split('T')[0];

    const updateNextButtonState = () => {
        if (!datePicker || !nextDayBtn) return;
        const today = new Date();
        const currentDate = new Date(datePicker.value);
        today.setHours(0, 0, 0, 0);
        currentDate.setHours(0, 0, 0, 0);
        nextDayBtn.disabled = currentDate >= today;
    };

    const changeDate = (days) => {
        const currentDate = new Date(datePicker.value);
        currentDate.setDate(currentDate.getDate() + days);
        datePicker.value = formatDate(currentDate);
        updateNextButtonState();
        datePicker.dispatchEvent(new Event('change', { 'bubbles': true }));
    };

    // --- LOGIKA WYKRESU ---

    /**
     * Pobiera dane z backendu. Oczekuje odpowiedzi w formacie:
     * { currentDay: {...}, previousDay: {...} }
     * @param {string} dateString - Data w formacie YYYY-MM-DD.
     * @returns {Promise<Object|null>}
     */
    const fetchChartData = async (dateString) => {
        const url = `/heating/get-data/${dateString}`;
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
                chartElement.innerHTML = '<div class="text-center p-5 text-danger">Wystąpił błąd podczas ładowania danych.</div>';
            }
            return null;
        }
    };

    /**
     * Przekształca dane z formatu backendu na format wymagany przez ApexCharts,
     * tworząc serie dla dnia bieżącego i poprzedniego z odpowiednimi stylami.
     * @param {Object} rawData - Surowe dane z backendu.
     * @param {Array<string>} baseColors - Podstawowa paleta kolorów.
     * @returns {{series: Array<Object>, colors: Array<string>, dashArrays: Array<number>, strokeWidths: Array<number>}}
     */
    const transformDataForChart = (rawData, baseColors) => {
        const finalSeries = [];
        const finalColors = [];
        const finalDashArrays = [];
        const finalStrokeWidths = []; // NOWOŚĆ: Tablica na grubości linii

        /**
         * Konwertuje kolor (np. zmienną CSS lub hex) na format RGBA z zadaną przezroczystością.
         * @param {string} colorStr - Kolor wejściowy.
         * @param {number} alpha - Poziom przezroczystości (0-1).
         * @returns {string} Kolor w formacie RGBA.
         */
        const toPaleColor = (colorStr, alpha = 0.65) => {
            // Jeśli kolor to zmienna CSS, pobierz jej faktyczną wartość
            if (colorStr.startsWith('var(')) {
                const varName = colorStr.match(/--[\w-]+/)[0];
                colorStr = getComputedStyle(document.documentElement).getPropertyValue(varName).trim();
            }

            // Konwertuj HEX na RGBA
            if (colorStr.startsWith('#')) {
                const shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
                const hex = colorStr.replace(shorthandRegex, (m, r, g, b) => r + r + g + g + b + b);
                const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
                if (result) {
                    const r = parseInt(result[1], 16);
                    const g = parseInt(result[2], 16);
                    const b = parseInt(result[3], 16);
                    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
                }
            }
            return colorStr; // Zwróć oryginał, jeśli konwersja się nie powiedzie
        };

        const currentDayData = rawData.currentDay || {};
        const previousDayData = rawData.previousDay || {};

        let colorIndex = 0;
        for (const seriesName in currentDayData) {
            if (Object.hasOwnProperty.call(currentDayData, seriesName)) {
                const color = baseColors[colorIndex % baseColors.length];

                // 1. Seria dla dnia bieżącego (gruba, ciągła, pełny kolor)
                finalSeries.push({
                    name: seriesName,
                    data: currentDayData[seriesName].map(p => ({ x: new Date(p.datetime).getTime(), y: p.value }))
                });
                finalColors.push(color);
                finalDashArrays.push(0); // 0 = linia ciągła
                finalStrokeWidths.push(2.5); // Standardowa grubość

                // 2. Seria dla dnia poprzedniego (cienka, przerywana, blady kolor)
                if (previousDayData[seriesName]) {
                    finalSeries.push({
                        name: `${seriesName} (pop.)`,
                        data: previousDayData[seriesName].map(p => {
                            const prevDate = new Date(p.datetime);
                            prevDate.setHours(prevDate.getHours() + 24);
                            return { x: prevDate.getTime(), y: p.value };
                        })
                    });
                    finalColors.push(toPaleColor(color)); // Ten sam kolor, ale bledszy
                    finalDashArrays.push(5); // > 0 = linia przerywana
                    finalStrokeWidths.push(1.5); // Cieńsza linia
                }
                colorIndex++;
            }
        }
        return { series: finalSeries, colors: finalColors, dashArrays: finalDashArrays, strokeWidths: finalStrokeWidths };
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

        if (!rawData || !rawData.currentDay || Object.keys(rawData.currentDay).length === 0) {
            if (chart) {
                chart.destroy();
                chart = null;
            }
            chartElement.innerHTML = '<div class="text-center p-5">Brak danych do wyświetlenia dla wybranego dnia.</div>';
            return;
        }

        const baseColors = ["var(--tblr-primary)", "var(--tblr-orange)", "var(--tblr-green)", "var(--tblr-red)"];
        // ZMIANA: Pobieramy teraz również grubości linii
        const { series, colors, dashArrays, strokeWidths } = transformDataForChart(rawData, baseColors);

        const options = {
            chart: {
                type: "line",
                fontFamily: "inherit",
                height: 400,
                parentHeightOffset: 0,
                toolbar: { show: true, tools: { download: true, selection: true, zoom: true, zoomin: true, zoomout: true, pan: true, reset: true } },
                animations: { enabled: true },
            },
            stroke: {
                width: strokeWidths, // ZMIANA: Użycie tablicy grubości linii
                lineCap: "round",
                curve: "smooth",
                dashArray: dashArrays,
            },
            series: series,
            colors: colors,
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
                labels: { padding: 0, format: 'HH:mm', datetimeUTC: false },
                tooltip: { enabled: false },
            },
            yaxis: {
                labels: {
                    padding: 4,
                    formatter: (value) => (value === null ? '' : value.toFixed(1) + "°C")
                },
            },
            legend: {
                show: true,
                position: 'bottom',
                horizontalAlign: 'center'
            },
            markers: {
                size: 0,
                hover: { sizeOffset: 2 }
            },
        };

        if (chartElement) {
            chartElement.innerHTML = "";
        }

        if (chart === null) {
            chart = new ApexCharts(chartElement, options);
            chart.render();
        } else {
            chart.updateOptions(options);
        }
    };

    // --- INICJALIZACJA ---
    if (window.ApexCharts && datePicker && chartElement && prevDayBtn && nextDayBtn) {
        prevDayBtn.addEventListener('click', () => changeDate(-1));
        nextDayBtn.addEventListener('click', () => changeDate(1));
        datePicker.addEventListener('change', (event) => {
            updateNextButtonState();
            loadAndRenderChart(event.target.value);
        });

        updateNextButtonState();
        loadAndRenderChart(datePicker.value);
    } else {
        console.error("Nie można zainicjować skryptu. Brakuje biblioteki ApexCharts lub kluczowych elementów HTML.");
    }
});
