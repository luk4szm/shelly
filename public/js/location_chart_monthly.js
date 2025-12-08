/**
 * Kontroler miesięcznych wykresów świeczkowych (temperatura i wilgotność).
 * Obsługuje input type="month", pobieranie danych i renderowanie ApexCharts.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) {
        console.error('Brak biblioteki ApexCharts.');
        return;
    }

    // 1. Elementy DOM
    const dateInput = document.getElementById('location_date');
    const prevBtn = document.getElementById('prev-month-btn');
    const nextBtn = document.getElementById('next-month-btn');
    const elTemp = document.getElementById('location-chart-temperature');
    const elHum = document.getElementById('location-chart-humidity');

    if (!dateInput || !prevBtn || !nextBtn || (!elTemp && !elHum)) {
        return;
    }

    // Pobranie sluga lokalizacji z atrybutu data (zakładamy, że oba kontenery mają ten sam slug)
    const locationSlug = elTemp ? elTemp.getAttribute('data-location-slug') : elHum.getAttribute('data-location-slug');

    // Instancje wykresów
    let tempChart = null;
    let humChart = null;

    const getMonthDate = () => {
        if (!dateInput.value) return new Date();
        // input type="month" zwraca "YYYY-MM", dodajemy "-01" by utworzyć pełną datę
        return new Date(dateInput.value + '-01');
    };

    const formatMonth = (dateObj) => {
        const y = dateObj.getFullYear();
        const m = (dateObj.getMonth() + 1).toString().padStart(2, '0');
        return `${y}-${m}`;
    };

    const updateUrlParam = (dateStr) => {
        const url = new URL(window.location.href);
        if (dateStr && dateStr !== 'last30days') {
            url.searchParams.set('date', dateStr);
        } else {
            url.searchParams.delete('date');
        }
        window.history.pushState({}, '', url.toString());
    };

    const changeMonth = (delta) => {
        let currentDate;

        // Jeśli pole jest puste (tryb "ostatnie 30 dni"), startujemy od obecnego miesiąca
        if (!dateInput.value) {
             currentDate = new Date();
             // Ustawiamy na 1 dzień miesiąca, żeby uniknąć problemów przy przesuwaniu (np. 31 marca -> luty)
             currentDate.setDate(1);
        } else {
             currentDate = getMonthDate();
        }

        currentDate.setMonth(currentDate.getMonth() + delta);

        const newVal = formatMonth(currentDate);
        dateInput.value = newVal;

        updateUrlParam(newVal);
        checkNextButtonState();
        loadAll(newVal);
    };

    const checkNextButtonState = () => {
        const current = getMonthDate();
        const maxStr = dateInput.getAttribute('max');

        // Jeśli input jest pusty, to jesteśmy w "ostatnich 30 dniach", więc "dalej" jest nieaktywne (chyba że chcemy pozwolić przejść do widoku bieżącego miesiąca kalendarzowego, ale zazwyczaj last30days zawiera "dzisiaj")
        if (!dateInput.value) {
            nextBtn.disabled = true;
            return;
        }

        if (maxStr) {
            const maxDate = new Date(maxStr + '-01');
            // Blokujemy, jeśli obecny miesiąc >= max miesiąc
            nextBtn.disabled = current >= maxDate;
        }
    };

    // 3. Pobieranie danych (API)
    // Zakładamy endpointy zgodne z konwencją. Należy dostosować URL, jeśli routing w Symfony jest inny.
    const fetchData = async (type, dateStr) => {
        // type: 'temperature' lub 'humidity'
        // Jeśli dateStr jest puste lub null, wysyłamy flagę oznaczającą "ostatnie 30 dni"
        const queryDate = dateStr || 'last30days';

        // endpoint np.: /location/{slug}/get-monthly-data?date=...&type=...
        const url = `/location/${locationSlug}/get-monthly-data?date=${queryDate}&type=${type}`;

        try {
            const res = await fetch(url, { cache: 'no-store' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return await res.json();
        } catch (e) {
            console.error(`Błąd pobierania danych (${type}):`, e);
            return [];
        }
    };

    // 4. Transformacja danych do formatu Candlestick
    // Input: { day: "2025-10-01", start_value, max_value, min_value, end_value }
    // Output ApexCharts: { x: timestamp, y: [Open, High, Low, Close] }
    const transformToCandle = (rawData) => {
        if (!Array.isArray(rawData)) return [];

        return rawData.map(item => {
            // Parsowanie daty dnia
            const timestamp = new Date(item.day).getTime();

            // Parsowanie wartości (start, max, min, end)
            const open = parseFloat(item.start_value);
            const high = parseFloat(item.max_value);
            const low = parseFloat(item.min_value);
            const close = parseFloat(item.end_value);

            if (Number.isNaN(timestamp) || Number.isNaN(open)) return null;

            return {
                x: timestamp,
                y: [open, high, low, close]
            };
        }).filter(Boolean).sort((a, b) => a.x - b.x);
    };

    // 5. Renderowanie wykresów
    const renderChart = (element, instance, dataSeries, title, unit, dateStr) => {
        if (!element) return null;

        const hasData = dataSeries.length > 0;

        if (!hasData) {
            if (instance) {
                instance.destroy();
            }
            element.innerHTML = '<div class="text-center p-4 text-muted">Brak danych dla wybranego miesiąca.</div>';
            return null;
        }

        let minX, maxX;
        if (dateStr && /^\d{4}-\d{2}$/.test(dateStr)) {
            const [year, month] = dateStr.split('-').map(Number);
            minX = new Date(year, month - 1, 1).getTime();
            maxX = new Date(year, month, 0, 23, 59, 59).getTime();
        }

        const options = {
            chart: {
                type: 'candlestick',
                height: 350,
                toolbar: { show: false },
                animations: { enabled: true }
            },
            series: [{
                name: title,
                data: dataSeries
            }],
            title: {
                text: '',
                align: 'left'
            },
            stroke: {
                width: 1
            },
            xaxis: {
                type: 'datetime',
                min: minX,
                max: maxX,
                labels: {
                    format: 'dd MMM', // np. 01 Oct
                    datetimeUTC: false
                },
                tooltip: {
                    enabled: true
                }
            },
            yaxis: {
                tooltip: {
                    enabled: true
                },
                labels: {
                    formatter: (val) => val.toFixed(1) + ' ' + unit
                }
            },
            plotOptions: {
                candlestick: {
                    // colors: {
                    //     upward: colors.up,   // Kolor gdy end > start
                    //     downward: colors.down // Kolor gdy end < start
                    // },
                    wick: {
                        useFillColor: true
                    }
                }
            },
            tooltip: {
                theme: 'dark',
                x: { format: 'dd MMM yyyy' },
                y: {
                    formatter: function (val) {
                        return val + ' ' + unit;
                    }
                },
                // Customizacja tooltipa, aby wyświetlić O/H/L/C czytelniej
                custom: function({seriesIndex, dataPointIndex, w}) {
                    const o = w.globals.seriesCandleO[seriesIndex][dataPointIndex];
                    const h = w.globals.seriesCandleH[seriesIndex][dataPointIndex];
                    const l = w.globals.seriesCandleL[seriesIndex][dataPointIndex];
                    const c = w.globals.seriesCandleC[seriesIndex][dataPointIndex];

                    return `
                        <div class="apexcharts-tooltip-title" style="background: #202b33; color: #fff; font-family: Helvetica, Arial, sans-serif;">
                            ${new Date(w.globals.seriesX[seriesIndex][dataPointIndex]).toLocaleDateString()}
                        </div>
                        <div class="apexcharts-tooltip-text" style="padding: 8px;">
                            <div><strong>Początek:</strong> <span class="value">${o} ${unit}</span></div>
                            <div><strong>Max:</strong> <span class="value">${h} ${unit}</span></div>
                            <div><strong>Min:</strong> <span class="value">${l} ${unit}</span></div>
                            <div><strong>Koniec:</strong> <span class="value">${c} ${unit}</span></div>
                        </div>
                    `;
                }
            },
            grid: {
                strokeDashArray: 4
            }
        };

        // Czyścimy ewentualny loader/komunikat
        if (element.innerHTML.includes('Ładowanie') || element.innerHTML.includes('Brak danych')) {
            element.innerHTML = '';
        }

        if (instance) {
            instance.updateOptions(options);
            instance.updateSeries([{ data: dataSeries }]);
            return instance;
        } else {
            const newChart = new ApexCharts(element, options);
            newChart.render();
            return newChart;
        }
    };

    // 6. Główna funkcja ładująca
    const loadAll = async (dateStr) => {
        // Pokaż loading
        if (elTemp && !tempChart) elTemp.innerHTML = '<div class="text-center p-4">Ładowanie...</div>';
        if (elHum && !humChart) elHum.innerHTML = '<div class="text-center p-4">Ładowanie...</div>';

        // Równoległe pobieranie
        const [tempData, humData] = await Promise.all([
            fetchData('temp', dateStr),
            fetchData('humidity', dateStr)
        ]);

        // Renderowanie Temperatury
        // Kolory: niebieski dla spadków, czerwony dla wzrostów (lub odwrotnie, tu: up=czerwony/ciepły, down=niebieski/zimny)
        // W wykresach giełdowych up=zielony, down=czerwony.
        // Przy temperaturze: wzrost temperatury w ciągu dnia -> czerwony (#ff6b6b), spadek -> niebieski (#4dabf7).
        tempChart = renderChart(
            elTemp,
            tempChart,
            transformToCandle(tempData),
            'Temperatura',
            '°C',
            dateStr
        );

        // Renderowanie Wilgotności
        // Tutaj kolory mogą być inne, np. turkusowy dla obu, lub rozróżnienie zmian.
        humChart = renderChart(
            elHum,
            humChart,
            transformToCandle(humData),
            'Wilgotność',
            '%',
            dateStr
        );
    };

    // 7. Event Listeners
    prevBtn.addEventListener('click', () => changeMonth(-1));
    nextBtn.addEventListener('click', () => changeMonth(1));

    dateInput.addEventListener('change', (e) => {
        const val = e.target.value;
        // Jeśli użytkownik wyczyścił input (choć w type=month to trudne), traktujemy to jak last30days
        updateUrlParam(val);
        checkNextButtonState();
        loadAll(val || 'last30days');
    });

    // Obsługa przycisków Wstecz/Dalej w przeglądarce
    window.addEventListener('popstate', () => {
        const url = new URL(window.location.href);
        const date = url.searchParams.get('date');

        if (date) {
            dateInput.value = date;
            loadAll(date);
        } else {
            // Powrót do braku parametru -> ostatnie 30 dni
            dateInput.value = '';
            loadAll('last30days');
        }
        checkNextButtonState();
    });

    // 8. Inicjalizacja
    // Pobierz wartość początkową z inputa (ustawionego przez Twig)
    // Jeśli input pusty, ładujemy 'last30days'
    const initialDate = dateInput.value || 'last30days';

    // Upewniamy się, że stan przycisku Next jest poprawny na starcie
    checkNextButtonState();

    loadAll(initialDate);
});
