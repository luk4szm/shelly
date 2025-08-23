(function ($) {
    let inactivityTimer = null;
    const defaultInactivityTime = 30000;
    const divToReload = '#reloadable-box';

    function reloadDiv() {
        const noCacheUrl = window.location.href.split('#')[0] + ' ' + divToReload;

        console.log('Brak aktywności. Przeładowuję ' + divToReload);

        $(divToReload).load(noCacheUrl, function (response, status, xhr) {
            if (status == "error") {
                console.error("Wystąpił błąd podczas przeładowywania diva: " + xhr.status + " " + xhr.statusText);
                $(divToReload).html("<div class='text-center'>Wystąpił błąd podczas odświeżania treści. Spróbuj odświeżyć stronę ręcznie.</div>");
            } else {
                // Ponownie inicjujemy skrypty dla nowej zawartości
                $('body').find('.current-status-duration').each(function () {
                    updateElapsedTime(this);
                });

                if (typeof initializeTemperatureCharts === 'function') {
                    initializeTemperatureCharts();
                }

                // KLUCZOWA ZMIANA: Ustawiamy timer na nowo, aby stworzyć pętlę
                resetTimer();
            }
        });
    }

    /**
     * Główna funkcja resetująca timer nieaktywności.
     * @param {number} [newTime] - Opcjonalny nowy czas w milisekundach.
     * Jeśli nie zostanie podany, użyty będzie domyślny czas.
     */
    function resetTimer(newTime) {
        const timeout = (typeof newTime === 'number' && newTime > 0) ? newTime : defaultInactivityTime;

        clearTimeout(inactivityTimer);

        inactivityTimer = setTimeout(reloadDiv, timeout);
    }

    window.resetInactivityTimer = resetTimer;

    $(document).on('mousemove keydown click touchstart', function () {
        resetTimer();
    });

    $(document).ready(function () {
        resetTimer();
        console.log('Timer nieaktywności został uruchomiony.');

        $('.current-status-duration').each(function () {
            updateElapsedTime(this);
        });
    });
})(jQuery);


function updateElapsedTime(element) {
    let timeString = $(element).text().trim();
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
