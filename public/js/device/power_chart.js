document.addEventListener('DOMContentLoaded', async function () {
    const chartElement = document.getElementById('chart-power');
    if (!chartElement) {
        return;
    }

    const deviceName = chartElement.dataset.deviceName;
    const dateInput = document.getElementById('heating_date');
    const prevDayBtn = document.getElementById('prev-day-btn');
    const nextDayBtn = document.getElementById('next-day-btn');
    const loaderElement = chartElement.querySelector('.chart-loader');
    const deviceStatsElement = document.getElementById('device_stats');

    const chartOptions = {
        series: [{
            name: 'Moc',
            data: []
        }],
        chart: {
            type: 'area',
            height: 400,
            zoom: { enabled: true },
            toolbar: { show: false },
            animations: { enabled: true, speed: 400 }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'stepline',
            width: 2
        },
        xaxis: {
            type: 'datetime',
            labels: {
                datetimeUTC: false,
                format: 'HH:mm'
            },
            title: {
                text: 'Godzina'
            }
        },
        yaxis: {
            title: {
                text: 'Moc (W)'
            },
            min: 0,
            labels: {
                formatter: (val) => { return val.toFixed(0) + ' W' }
            }
        },
        tooltip: {
            x: {
                format: 'dd MMM yyyy, HH:mm:ss'
            },
            y: {
                formatter: (val) => { return val.toFixed(2) + " W" }
            }
        },
        noData: {
            text: 'Brak danych dla wybranego dnia...',
            align: 'center',
            verticalAlign: 'middle',
        }
    };

    // Inicjalizacja wykresu
    const chart = new ApexCharts(chartElement, chartOptions);
    await chart.render();

    /**
     * Asynchronicznie pobiera dane i aktualizuje wykres oraz statystyki dla podanej daty.
     * @param {string} date - Data w formacie YYYY-MM-DD
     */
    async function fetchAndRenderData(date) {
        if (loaderElement) loaderElement.style.display = 'block';

        const startOfDay = new Date(`${date}T00:00:00`);
        const endOfDay = new Date(`${date}T23:59:59.999`);

        chart.updateOptions({
            xaxis: {
                min: startOfDay.getTime(),
                max: endOfDay.getTime()
            }
        });

        // Definicja zapytań, które będą wykonane równolegle
        const fetchChartData = fetch(`/device/${deviceName}/power-data?date=${date}`).then(res => {
            if (!res.ok) throw new Error(`Błąd HTTP (wykres): ${res.status}`);
            return res.json();
        });

        const fetchStatsHtml = fetch(`${window.location.pathname}?date=${date}`).then(res => {
            if (!res.ok) throw new Error(`Błąd HTTP (statystyki): ${res.status}`);
            return res.text();
        });

        try {
            // Równoległe wykonanie zapytań
            const [chartData, statsHtml] = await Promise.all([fetchChartData, fetchStatsHtml]);

            // Aktualizacja wykresu
            chart.updateSeries([{ data: chartData }]);

            // Aktualizacja statystyk
            if (deviceStatsElement && statsHtml) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(statsHtml, 'text/html');
                const newStatsElement = doc.getElementById('device_stats');
                if (newStatsElement) {
                    deviceStatsElement.innerHTML = newStatsElement.innerHTML;
                } else {
                    console.error('Nie znaleziono elementu #device_stats w odpowiedzi serwera.');
                }
            }
        } catch (error) {
            console.error('Nie udało się zaktualizować danych:', error);
            chart.updateSeries([{ data: [] }]); // Wyczyść wykres w razie błędu
        } finally {
            if (loaderElement) loaderElement.style.display = 'none';
        }
    }

    /**
     * Aktualizuje wartość w polu daty i wywołuje odświeżenie danych.
     * @param {number} days - Liczba dni do dodania/odjęcia
     */
    function updateDate(days) {
        const currentDate = new Date(dateInput.value);
        currentDate.setDate(currentDate.getDate() + days);
        const newDateString = currentDate.toISOString().split('T')[0];

        const maxDate = dateInput.getAttribute('max');
        if (newDateString > maxDate) {
            return;
        }

        dateInput.value = newDateString;
        dateInput.dispatchEvent(new Event('change'));
    }

    // --- NOWA LOGIKA: Ustalanie daty początkowej ---
    /**
     * Pobiera datę z parametru URL lub zwraca aktualną wartość pola input.
     * @returns {string} Data w formacie YYYY-MM-DD
     */
    function getInitialDate() {
        const urlParams = new URLSearchParams(window.location.search);
        const dateFromUrl = urlParams.get('date');

        // Prosta walidacja formatu YYYY-MM-DD
        if (dateFromUrl && /^\d{4}-\d{2}-\d{2}$/.test(dateFromUrl)) {
            dateInput.value = dateFromUrl; // Zaktualizuj pole input, aby pasowało do URL
            return dateFromUrl;
        }

        return dateInput.value; // Zwróć domyślną wartość, jeśli brak parametru
    }

    const initialDate = getInitialDate();
    // --- KONIEC NOWEJ LOGIKI ---


    // Nasłuchiwanie na zdarzenia
    dateInput.addEventListener('change', () => {
        fetchAndRenderData(dateInput.value);
    });

    prevDayBtn.addEventListener('click', () => updateDate(-1));
    nextDayBtn.addEventListener('click', () => updateDate(1));

    // Pobierz dane dla ustalonej daty (z URL lub domyślnej) przy pierwszym załadowaniu strony
    await fetchAndRenderData(initialDate);
});
