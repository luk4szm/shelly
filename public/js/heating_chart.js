document.addEventListener("DOMContentLoaded", function () {
    // --- ELEMENTY DOM ---
    const datePicker = document.getElementById('heating_date');
    // ZMIANA: Usuwamy sztywne przypisanie chartElement, będziemy pobierać elementy dynamicznie
    const prevDayBtn = document.getElementById('prev-day-btn');
    const nextDayBtn = document.getElementById('next-day-btn');
    const notesContainer = document.getElementById('heating_notes_card');

    // --- KONFIGURACJA WYKRESÓW ---
    const chartConfigs = [
        {
            elementId: 'chart-temperature',
            locations: 'heating-full', // Domyślny zestaw danych
            showActivities: true,      // Pokaż tła (piec, kominek, solary)
            showNotesOnAxis: true,     // Pokaż markery notatek na osi X
            updateNotesList: true      // Czy ten wykres ma aktualizować listę notatek w panelu bocznym
        },
        {
            elementId: 'chart-underfloor-heating',
            locations: 'underfloor-heating',   // Dane dla podłogówki
            showActivities: false,     // Bez teł urządzeń
            showNotesOnAxis: true,
            updateNotesList: false
        }
    ];

    // --- STAN ---
    const chartInstances = {}; // Przechowuje instancje ApexCharts: { 'elementId': chartInstance }

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
     * Pobiera dane z backendu.
     * @param {string} dateString - Data w formacie YYYY-MM-DD.
     * @param {string} locations - Parametr locations dla backendu.
     * @returns {Promise<Object|null>}
     */
    const fetchChartData = async (dateString, locations) => {
        const url = `/heating/get-data/${dateString}?locations=${locations}`;
        // Nie czyścimy tutaj HTML, bo robimy to per-chart w loadChart
        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`Błąd HTTP! Status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error(`Nie udało się pobrać danych (${locations}) dla daty ${dateString}:`, error);
            return null;
        }
    };

    /**
     * Przekształca dane z formatu backendu na format wymagany przez ApexCharts,
     * tworząc serie dla dnia bieżącego i poprzedniego z odpowiednimi stylami.
     * ZAMIANA: kolory linii pochodzą z rawData.locationColors.
     * @param {Object} rawData - Surowe dane z backendu.
     * @returns {{series: Array<Object>, colors: Array<string>, dashArrays: Array<number>, strokeWidths: Array<number>}}
     */
    const transformDataForChart = (rawData) => {
        const finalSeries = [];
        const finalColors = [];
        const finalDashArrays = [];
        const finalStrokeWidths = [];

        const toPaleColor = (colorStr, alpha = 0.65) => {
            // Konwertuj HEX na RGBA
            if (typeof colorStr === 'string' && colorStr.startsWith('#')) {
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
            return colorStr;
        };

        const currentDayData = rawData.currentDay || {};
        const previousDayData = rawData.previousDay || {};
        const locationColors = rawData.locationColors || {};

        for (const seriesName in currentDayData) {
            if (!Object.hasOwnProperty.call(currentDayData, seriesName)) continue;

            const baseColor = locationColors[seriesName] || undefined;

            // 1) Dzień bieżący
            finalSeries.push({
                name: seriesName,
                data: currentDayData[seriesName].map(p => ({ x: new Date(p.datetime).getTime(), y: p.value }))
            });
            finalColors.push(baseColor);
            finalDashArrays.push(0);
            finalStrokeWidths.push(2.5);

            // 2) Dzień poprzedni
            if (previousDayData[seriesName]) {
                finalSeries.push({
                    name: `${seriesName} (pop.)`,
                    data: previousDayData[seriesName].map(p => {
                        const prevDate = new Date(p.datetime);
                        prevDate.setHours(prevDate.getHours() + 24);
                        return { x: prevDate.getTime(), y: p.value };
                    })
                });
                finalColors.push(baseColor ? toPaleColor(baseColor) : undefined);
                finalDashArrays.push(5);
                finalStrokeWidths.push(1.5);
            }
        }

        // Jeżeli wszystkie kolory są undefined, zwróć tablicę bez colors (Apex nada domyślne)
        const hasAnyColor = finalColors.some(c => !!c);
        return {
            series: finalSeries,
            colors: hasAnyColor ? finalColors : undefined,
            dashArrays: finalDashArrays,
            strokeWidths: finalStrokeWidths
        };
    };

    /**
     * Ładuje i renderuje pojedynczy wykres na podstawie konfiguracji.
     * @param {Object} config - Obiekt konfiguracji wykresu.
     * @param {string} dateString - Data.
     */
    const loadSingleChart = async (config, dateString) => {
        const element = document.getElementById(config.elementId);
        if (!element) return;

        // Ustaw loading state tylko jeśli nie ma jeszcze wykresu lub chcemy go przesłonić
        // Jeśli wykres istnieje, ApexCharts obsłuży update, ale przy braku danych musimy wyczyścić
        // Dla UX: przy zmianie daty można pokazać loader
        // element.innerHTML = '<div class="text-center p-5">Ładowanie...</div>'; // To zniszczy instancję ApexCharts jeśli jest w środku

        const rawData = await fetchChartData(dateString, config.locations);

        if (config.updateNotesList && notesContainer && rawData) {
            try {
                notesContainer.innerHTML = rawData.notes.rendered;
            } catch (e) {
                console.error('Błąd podczas aktualizacji notatek:', e);
            }
        }

        if (!rawData || !rawData.currentDay || Object.keys(rawData.currentDay).length === 0) {
            if (chartInstances[config.elementId]) {
                chartInstances[config.elementId].destroy();
                delete chartInstances[config.elementId];
            }
            element.innerHTML = '<div class="text-center p-5">Brak danych do wyświetlenia dla wybranego dnia.</div>';
            return;
        }

        // Wyczyszczenie ewentualnego komunikatu o błędzie/ładowaniu jeśli tworzymy nowy wykres
        if (!chartInstances[config.elementId]) {
            element.innerHTML = "";
        }

        const { series, colors, dashArrays, strokeWidths } = transformDataForChart(rawData);

        // Budowanie adnotacji (tła aktywności)
        const buildAnnotations = (activities) => {
            if (!activities || !config.showActivities) return { xaxis: [] };
            const deviceColors = {
                'piec': 'rgba(220, 53, 69, 0.45)',
                'kominek': 'rgba(253, 126, 20, 0.1)',
                'solary': 'rgba(25, 135, 84, 0.4)',
            };
            const xaxis = [];
            Object.entries(activities).forEach(([device, intervals]) => {
                const fillColor = deviceColors[device] || 'rgba(0,0,0,0.08)';
                intervals.forEach(({ from, to }) => {
                    const x = new Date(from).getTime();
                    const x2 = new Date(to).getTime();
                    if (!isNaN(x) && !isNaN(x2) && x < x2) {
                        xaxis.push({ x, x2, fillColor, opacity: 1, borderColor: 'transparent' });
                    }
                });
            });
            return { xaxis };
        };

        const annotations = buildAnnotations(rawData.activities);

        // Budowanie adnotacji notatek (markery na osi)
        const buildNoteAnnotations = (notes) => {
            if (!config.showNotesOnAxis || !notes || !Array.isArray(notes.data)) return { xaxis: [] };

            const sorted = notes.data
                .map(n => ({ x: new Date(n.time).getTime(), note: n.note }))
                .filter(n => !isNaN(n.x))
                .sort((a, b) => a.x - b.x);

            const GROUP_WINDOW_MS = 5 * 60 * 1000; // 5 minut
            const groups = [];
            let current = [];

            for (const n of sorted) {
                if (current.length === 0) {
                    current.push(n);
                } else {
                    const last = current[current.length - 1];
                    if (n.x - last.x <= GROUP_WINDOW_MS) {
                        current.push(n);
                    } else {
                        groups.push(current);
                        current = [n];
                    }
                }
            }
            if (current.length) groups.push(current);

            const xaxis = groups.map(group => {
                const x = group[Math.floor(group.length / 2)].x;
                const textShort = group.length === 1 ? 'i' : `+${group.length}`;
                return {
                    x,
                    strokeDashArray: 0,
                    borderColor: 'rgba(0,0,0,0.35)',
                    label: {
                        text: textShort,
                        borderColor: 'rgba(0,0,0,0.0)',
                        style: {
                            background: 'var(--tblr-yellow, #f6c343)',
                            color: '#1f1f1f',
                            fontSize: '11px',
                            padding: { left: 4, right: 4, top: 1, bottom: 1 }
                        }
                    }
                };
            });

            return { xaxis };
        };

        const noteAnnotations = buildNoteAnnotations(rawData?.notes);
        const mergedAnnotations = {
            xaxis: [
                ...(annotations?.xaxis || []),
                ...(noteAnnotations?.xaxis || [])
            ]
        };

        const options = {
            chart: {
                type: "line",
                fontFamily: "inherit",
                height: 400,
                parentHeightOffset: 0,
                toolbar: { show: true, tools: { download: true, selection: true, zoom: true, zoomin: true, zoomout: true, pan: true, reset: true } },
                animations: { enabled: true },
            },
            annotations: mergedAnnotations,
            stroke: {
                width: strokeWidths,
                lineCap: "round",
                curve: "smooth",
                dashArray: dashArrays,
            },
            series: series,
            ...(colors ? { colors } : {}),
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

        if (!chartInstances[config.elementId]) {
            chartInstances[config.elementId] = new ApexCharts(element, options);
            chartInstances[config.elementId].render();
        } else {
            chartInstances[config.elementId].updateOptions(options);
        }
    };

    /**
     * Główna funkcja iterująca po wszystkich wykresach
     */
    const loadAllCharts = (dateString) => {
        if (!dateString) {
            console.error("Nie podano daty do załadowania wykresów.");
            return;
        }
        chartConfigs.forEach(config => loadSingleChart(config, dateString));
    };

    // --- INICJALIZACJA ---
    if (window.ApexCharts && datePicker && prevDayBtn && nextDayBtn) {
        prevDayBtn.addEventListener('click', () => changeDate(-1));
        nextDayBtn.addEventListener('click', () => changeDate(1));
        datePicker.addEventListener('change', (event) => {
            updateNextButtonState();
            loadAllCharts(event.target.value);
        });

        updateNextButtonState();
        loadAllCharts(datePicker.value);
    } else {
        console.error("Nie można zainicjować skryptu. Brakuje biblioteki ApexCharts lub kluczowych elementów HTML.");
    }
});
