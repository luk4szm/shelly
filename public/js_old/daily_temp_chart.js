$(document).ready(function () {
    let ctx = document.getElementById('temperatureChart').getContext('2d');
    let date = $("#date").data('date');

    $.ajax({
        url: '/data/temp',
        method: 'GET',
        dataType: 'json',
        data: {'date': date, 'location': 'all'},
        success: function (data) {
            if (data.length === 0) {
                $('#no_data_modal').show();
                $('#temperature_chart_modal_content').hide();

                return;
            } else {
                $('#no_data_modal').hide();
                $('#temperature_chart_modal_content').show();
            }

            const datasets = [];
            // const colors = ['rgb(75, 192, 192)', 'rgb(255, 99, 132)', 'rgb(54, 162, 235)', 'rgb(59,172,51)'];

            Object.keys(data).forEach((deviceName, index) => {
                const chartData = data[deviceName].map(item => ({
                    x: new Date(item.datetime),
                    y: item.value
                }));

                datasets.push({
                    label: `${deviceName} (°C)`,
                    data: chartData,
                    // borderColor: colors[index % colors.length],
                    tension: 0.5,
                    fill: false,
                });
            });

            temperatureChart = new Chart(ctx, {
                type: 'line',
                data: {
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    pointStyle: false,
                    scales: {
                        x: {
                            type: 'time',
                            adapters: {
                                date: {
                                    adapter: 'luxon'
                                }
                            },
                            time: {
                                unit: 'hour',
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
});
