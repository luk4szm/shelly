document.addEventListener('DOMContentLoaded', function () {
    const chartElement = document.getElementById('chart-power');
    if (!chartElement) {
        return;
    }

    // --- Logika nawigacji ---
    const dateInput = document.getElementById('heating_date');
    const prevMonthBtn = document.getElementById('prev-month-btn');
    const nextMonthBtn = document.getElementById('next-month-btn');

    /**
     * Przeładowuje stronę z nowym miesiącem w parametrze URL.
     * @param {string} dateString - Data w formacie YYYY-MM
     */
    function navigateToMonth(dateString) {
        const url = new URL(window.location);
        url.searchParams.set('date', dateString);
        window.location.href = url.toString();
    }

    if (dateInput && prevMonthBtn && nextMonthBtn) {
        dateInput.addEventListener('change', () => {
            if (dateInput.value) {
                navigateToMonth(dateInput.value);
            }
        });

        prevMonthBtn.addEventListener('click', () => {
            const currentDate = new Date(dateInput.value + '-02'); // Używamy 'YYYY-MM-02' aby uniknąć problemów z końcem miesiąca
            currentDate.setMonth(currentDate.getMonth() - 1);
            const newDateString = currentDate.toISOString().slice(0, 7); // Format YYYY-MM
            navigateToMonth(newDateString);
        });

        nextMonthBtn.addEventListener('click', () => {
            const currentDate = new Date(dateInput.value + '-02');
            currentDate.setMonth(currentDate.getMonth() + 1);
            const newDateString = currentDate.toISOString().slice(0, 7);

            // Zapobiegaj nawigacji w przyszłość poza maksymalną dozwoloną datę
            const maxDate = dateInput.getAttribute('max');
            if (newDateString > maxDate) {
                return;
            }
            navigateToMonth(newDateString);
        });
    }

    // --- Logika wykresu ---
    const monthlyDataRaw = chartElement.dataset.monthlyData;
    if (!monthlyDataRaw) {
        chartElement.innerHTML = '<div class="text-muted text-center pt-5">Brak danych do wyświetlenia dla wybranego miesiąca.</div>';
        return;
    }

    const monthlyData = JSON.parse(monthlyDataRaw);

    if (monthlyData.length === 0) {
        chartElement.innerHTML = '<div class="text-muted text-center pt-5">Brak danych do wyświetlenia dla wybranego miesiąca.</div>';
        return;
    }

    monthlyData.sort((a, b) => new Date(a.date) - new Date(b.date));

    // Przetwarzanie danych na format wymagany przez ApexCharts
    const categories = monthlyData.map(d => d.date);
    const energyData = monthlyData.map(d => parseFloat((d.energy / 1000).toFixed(2))); // Konwersja Wh -> kWh
    const timeData = monthlyData.map(d => parseFloat((d.time / 3600).toFixed(2)));   // Konwersja sekundy -> godziny
    const inclusionsData = monthlyData.map(d => d.inclusions);

    const chartOptions = {
        series: [
            { name: 'Zużyta energia', type: 'column', data: energyData },
            { name: 'Czas pracy', type: 'column', data: timeData },
            { name: 'Liczba włączeń', type: 'line', data: inclusionsData }
        ],
        chart: {
            height: 400,
            type: 'line',
            stacked: false,
            zoom: { enabled: false },
            toolbar: { show: true },
        },
        stroke: {
            width: [0, 0, 3], // Szerokość obrysu: 0 dla słupków, 3 dla linii
            curve: 'smooth'
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            type: 'datetime',
            categories: categories,
            labels: {
                datetimeUTC: false, // Ważne dla poprawnego wyświetlania dat
                format: 'dd MMM'
            },
            title: {
                text: 'Dzień miesiąca'
            }
        },
        yaxis: [
            {
                seriesName: 'Zużyta energia',
                axisTicks: { show: true },
                axisBorder: { show: true, color: '#008FFB' },
                labels: {
                    formatter: (val) => val.toFixed(1) + ' kWh',
                    style: { colors: '#008FFB' }
                },
                title: {
                    text: "Zużyta energia (kWh)",
                    style: { color: '#008FFB' }
                },
            },
            {
                seriesName: 'Czas pracy',
                opposite: true, // Oś po prawej stronie
                axisTicks: { show: true },
                axisBorder: { show: true, color: '#00E396' },
                labels: {
                    formatter: (val) => val.toFixed(1) + ' h',
                    style: { colors: '#00E396' }
                },
                title: {
                    text: "Czas pracy (h)",
                    style: { color: '#00E396' }
                },
            },
            {
                seriesName: 'Liczba włączeń',
                opposite: true, // Oś po prawej stronie
                axisTicks: { show: true },
                axisBorder: { show: true, color: '#FEB019' },
                labels: {
                    formatter: (val) => val.toFixed(0),
                    style: { colors: '#FEB019' }
                },
                title: {
                    text: "Liczba włączeń",
                    style: { color: '#FEB019' }
                },
            }
        ],
        tooltip: {
            x: {
                format: 'dd MMMM yyyy'
            },
            shared: true,
            intersect: false,
        },
        legend: {
            position: 'top',
            horizontalAlign: 'center'
        },
        noData: {
            text: 'Brak danych do wyświetlenia...',
        }
    };

    const chart = new ApexCharts(chartElement, chartOptions);
    chart.render();
});
