$(document).ready(function () {
    let ctx = document.getElementById('salonTemperatureChart').getContext('2d');
    let htx = document.getElementById('salonHumidityChart').getContext('2d');

    $.ajax({
        url: '/heating',
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    datasets: [{
                        label: 'Min (°C)',
                        data: data['salonTemp'].map(item => ({
                            x: new Date(item.date + ' 00:00:00'),
                            y: item.min
                        })),
                        borderColor: 'rgb(124,241,241)',
                        tension: 0.5,
                        fill: false,
                    },{
                        label: 'Średnia (°C)',
                        data: data['salonTemp'].map(item => ({
                            x: new Date(item.date + ' 00:00:00'),
                            y: item.avg
                        })),
                        borderColor: 'rgb(75,192,85)',
                        tension: 0.5,
                        fill: false,
                    },{
                        label: 'Max (°C)',
                        data: data['salonTemp'].map(item => ({
                            x: new Date(item.date + ' 00:00:00'),
                            y: item.max
                        })),
                        borderColor: 'rgb(192,75,75)',
                        tension: 0.5,
                        fill: false,
                    }]
                },
                options: getChartOptions('Temperatura (°C)')
            });

            new Chart(htx, {
                type: 'line',
                data: {
                    datasets: [{
                        label: 'Min (%)',
                        data: data['salonHum'].map(item => ({
                            x: new Date(item.date + ' 00:00:00'),
                            y: item.min
                        })),
                        borderColor: 'rgb(124,241,241)',
                        tension: 0.5,
                        fill: false,
                    },{
                        label: 'Średnia (%)',
                        data: data['salonHum'].map(item => ({
                            x: new Date(item.date + ' 00:00:00'),
                            y: item.avg
                        })),
                        borderColor: 'rgb(75,192,85)',
                        tension: 0.5,
                        fill: false,
                    },{
                        label: 'Max (%)',
                        data: data['salonHum'].map(item => ({
                            x: new Date(item.date + ' 00:00:00'),
                            y: item.max
                        })),
                        borderColor: 'rgb(192,75,75)',
                        tension: 0.5,
                        fill: false,
                    }]
                },
                options: getChartOptions('Wilgotność (%)')
            });
        },
        error: function (xhr, status, error) {
            console.error('Błąd ładowania danych:', error);
        }
    });
});

function getChartOptions(title) {
    return {
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
                    unit: 'day', // Możesz zmienić na 'minute', 'day' itp.
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
                    text: title
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
}
