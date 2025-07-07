$(document).ready(function () {
    const yrIframe = document.querySelector('#temperatures_row .col-lg-4 iframe');

    if (yrIframe) {
        function reloadIframe() {
            yrIframe.src = yrIframe.src; // Wymusza ponowne załadowanie iframe
            console.log('Odświeżono iframe o ' + new Date().toLocaleTimeString());
        }

        function scheduleReload() {
            const now = new Date();
            const nextHour = new Date(now);
            nextHour.setMinutes(0);
            nextHour.setSeconds(0);
            nextHour.setMilliseconds(0);
            nextHour.setTime(nextHour.getTime() + 60 * 60 * 1000); // Następna pełna godzina

            // Ustaw czas pierwszego odświeżenia na 2 minuty po pełnej godzinie
            const firstReloadTime = nextHour.getTime() + 2 * 60 * 1000;
            const timeUntilFirstReload = firstReloadTime - now.getTime();

            setTimeout(function () {
                reloadIframe();
                // Ustaw interwał do odświeżania co godzinę (3 600 000 milisekund)
                setInterval(reloadIframe, 3600000);
            }, timeUntilFirstReload);

            const firstReloadDate = new Date(firstReloadTime);
            console.log('Pierwsze odświeżenie iframe o ' + firstReloadDate.toLocaleTimeString());
        }

        // Uruchom planowanie odświeżenia po załadowaniu strony
        scheduleReload();
    }
});
