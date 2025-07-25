$(document).ready(function () {
    let ctx = document.getElementById('temperatureChart').getContext('2d');
    let temperatureChart;
    let intervalId;
    let prevTimeRange;
    let location = null;
    let group = null;

    let urlSegments = window.location.pathname.split('/');
    if (urlSegments[urlSegments.length - 1] === 'heating') {
        group = 'heating';
    }

    $('#input_date').change(function () {
        var date = $(this).val();

        updateChart(date, location, group);
    });

    $('#prev_day').click(function () {
        changeDate(-1);
    });

    $('#next_day').click(function () {
        changeDate(1);
    });

    function changeDate(days) {
        var inputDate = $('#input_date').val();

        console.log(inputDate);

        if (inputDate) {
            var date = new Date(inputDate);
            date.setDate(date.getDate() + days);

            // Formatowanie daty do YYYY-MM-DD
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            var formattedDate = year + '-' + month + '-' + day;

            $('#input_date').val(formattedDate);

            updateChart(formattedDate, location, group);
        }
    }

    function updateChart(timeRange, location = null, group = null) {
        prevTimeRange = timeRange;

        $.ajax({
            url: '/data/temp',
            method: 'GET',
            dataType: 'json',
            data: {'timeRange': timeRange, 'location': location, 'group': group},
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
                const colors = ['rgb(75, 192, 192)', 'rgb(255, 99, 132)', 'rgb(54, 162, 235)', 'rgb(59,172,51)'];

                Object.keys(data).forEach((deviceName, index) => {
                    const chartData = data[deviceName].map(item => ({
                        x: new Date(item.datetime),
                        y: item.value
                    }));

                    datasets.push({
                        label: `${deviceName} (°C)`,
                        data: chartData,
                        borderColor: colors[index % colors.length],
                        tension: 0.1,
                        fill: false,
                    });
                });

                if (temperatureChart) {
                    temperatureChart.data.datasets = datasets;
                    temperatureChart.update('none');
                } else {
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
                }
            },
            error: function (xhr, status, error) {
                console.error('Błąd ładowania danych:', error);
            }
        });
    }

    // Pierwsze załadowanie danych
    updateChart(prevTimeRange, location, group);

    // Ustawienie interwału aktualizacji co 30 sekund
    intervalId = setInterval(() => updateChart(prevTimeRange, location, group), 30000);

    $('.btn[data-time-range]').click(function() {
        let timeRange = $(this).data('time-range');

        clearInterval(intervalId);
        updateChart(timeRange, location, group);

        intervalId = setInterval(() => updateChart(prevTimeRange, location, group), 30000);
    });
});
