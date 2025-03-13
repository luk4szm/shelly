$(document).ready(function () {
    setInterval(refreshTemperatureCard, 20000);

    function refreshTemperatureCard() {
        $('#temperatures_card').load(location.href + " " + '#temperatures_card');
    }
});
