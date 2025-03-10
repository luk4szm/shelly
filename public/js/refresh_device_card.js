$(document).ready(function () {
    setInterval(refreshDeviceCards, 60000);

    function refreshDeviceCard(selector) {
        $(selector).load(location.href + " " + selector, function () {
            // Po załadowaniu elementu, ponownie uruchamiamy updateElapsedTime
            $(selector).find('.current_status_duration').each(function () {
                updateElapsedTime(this);
            });
        });
    }

    function refreshDeviceCards() {
        const deviceNamesString = $('[data-device-names]').data('device-names');
        const deviceNames = deviceNamesString.split('|');

        deviceNames.forEach(deviceName => {
            const selector = `#${deviceName}_device_card`;
            refreshDeviceCard(selector);
        });
    }

    $('.current_status_duration').each(function () {
        updateElapsedTime(this);
    });

    function updateElapsedTime(element) {
        let timeString = $(element).text();
        let parts = timeString.split(' '); // Rozdzielamy na dni i resztę
        let days = 0;
        let timePart = parts[0]; // Domyślnie bierzemy pierwszą część

        if (parts.length > 1) { // Jeśli są dni
            days = parseInt(parts[0].replace('d', ''), 10); // Wyciągamy liczbę dni
            timePart = parts[1]; // Bierzemy część godzinową
        }

        let timeParts = timePart.split(':');
        let hours = parseInt(timeParts[0], 10);
        let minutes = parseInt(timeParts[1], 10);
        let seconds = parseInt(timeParts[2], 10);

        function incrementTime() {
            seconds++;
            if (seconds >= 60) {
                seconds = 0;
                minutes++;
                if (minutes >= 60) {
                    minutes = 0;
                    hours++;
                }
            }

            let formattedHours = hours.toString();
            let formattedMinutes = minutes < 10 ? '0' + minutes : minutes.toString();
            let formattedSeconds = seconds < 10 ? '0' + seconds : seconds.toString();

            let formattedTime = formattedHours + ':' + formattedMinutes + ':' + formattedSeconds;
            if (days > 0) {
                $(element).text(days + 'd ' + formattedTime);
            } else {
                $(element).text(formattedTime);
            }
        }

        // Usuwamy istniejący interwał przed ustawieniem nowego
        clearInterval($(element).data('intervalId'));

        const intervalId = setInterval(incrementTime, 1000);
        $(element).data('intervalId', intervalId);
    }
});
