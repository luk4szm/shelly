const myModalEl = document.getElementById('temperatureChartModal')

myModalEl.addEventListener('hidden.bs.modal', event => {
    $('#temperature_chart_modal_content').html('<canvas id="temperatureChart"></canvas>');
});

$(document).on('click', 'button[data-bs-toggle="modal"][data-action="history"]', function () {
    loadTemperatureData($(this).data('location'));
});

// Funkcja ładująca dane i renderująca wykres
function loadTemperatureData(location) {
    let ctx = document.getElementById('temperatureChart').getContext('2d');
    let temperatureChart;

    $.ajax({
        url: '/data/temp/' + location,
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            const chartData = data.map(item => ({
                x: new Date(item.datetime),
                y: item.value
            }));

            temperatureChart = new Chart(ctx, {
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
