const myModalEl = document.getElementById('locationTemperatureChartModal')

myModalEl.addEventListener('hidden.bs.modal', event => {
    $('#location_temperature_chart_modal_content').html('<canvas id="locationTemperatureChart"></canvas>');
});

$(document).on('click', '[data-bs-toggle="modal"][data-action="history"]', function () {
    loadTemperatureData($(this).data('location'));
});

// Funkcja ładująca dane i renderująca wykres
function loadTemperatureData(location) {
    let ctx = document.getElementById('locationTemperatureChart').getContext('2d');
    let locationTemperatureChart;

    $.ajax({
        url: '/data/temp',
        method: 'GET',
        dataType: 'json',
        data: {'location': location},
        success: function (data) {
            if (data.length === 0) {
                $('#location_temperature_chart_modal_content').html('<h5 class="text-center my-5">Brak danych dla zadanego okresu</h5>');

                return;
            }

            const chartData = data.map(item => ({
                x: new Date(item.datetime),
                y: item.value
            }));

            locationTemperatureChart = new Chart(ctx, {
                type: 'line',
                data: {
                    datasets: [{
                        label: 'Temperatura (°C)',
                        data: chartData,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1,
                        fill: false,
                    }]
                },
                options: {
                    pointStyle: false,
                    responsive: true,
                    scales: {
                        x: {
                            type: 'time',
                            adapters: {
                                date: {
                                    adapter: 'luxon'
                                }
                            },
                            time: {
                                unit: 'hour', // Możesz zmienić na 'minute', 'day' itp.
                                displayFormats: {
                                    hour: 'HH:mm'
                                },
                                tooltipFormat: 'yyyy-MM-dd HH:mm'
                            },
                            title: {
                                display: true,
                                text: 'Czas'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Temperatura (°C)'
                            },
                            beginAtZero: false
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return `${context.dataset.label}: ${context.parsed.y}°C`;
                                }
                            }
                        }
                    }
                }
            });
        },
        error: function (xhr, status, error) {
            console.error('Błąd ładowania danych:', error);
        }
    });
}
