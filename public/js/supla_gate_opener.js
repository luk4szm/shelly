$(document).ready(function () {
    // scroll under gate opener range
    document.querySelector('#temperatures_row').scrollIntoView({
        behavior: 'smooth'
    });

    let infoButton = $("#gate_opener_info_button_div")
    let progressBar = $("#gate_opener_progress_bar");
    let rangeInput = $('#gate_opener_range_input');

    rangeInput.on('mouseup touchend', function () {
        let currentValue = parseInt($(this).val());

        if (currentValue === 100) {
            hideDiv(rangeInput);
            showDiv(infoButton);

            $.ajax({
                url: '/supla/gate/open',
                method: 'PATCH',
                success: function () {
                    $("#gate_opener_info_button").html('Gate is opening...');
                    showDiv(progressBar);
                    showGateOpeningProgress(progressBar);
                },
            });
        } else {
            $(this).val(0);
        }
    });
});

function showDiv(selector)
{
    selector
        .removeClass('d-none')
        .addClass('d-grid')
        .css({opacity: 0}) // Ustaw początkowy stan dla animacji
        .animate({opacity: 1}, 1000, function () {
            // Opcjonalnie: kod do wykonania po zakończeniu animacji
        });
}

function hideDiv(selector)
{
    selector
        .removeClass('d-grid')
        .addClass('d-none');
}

function showGateOpeningProgress() {
    let duration = 18000; // 18 sekund w milisekundach
    let progressBar = $(".progress-bar");

    progressBar.animate({
        width: '100%'
    }, duration, function () {
        $("#gate_opener_info_button")
            .removeClass('btn-primary')
            .addClass('btn-success')
            .html('Gate open!');

        $("#gate_opener_info_button_div").removeClass('mb-2');

        hideDiv($("#gate_opener_progress_bar"));
    });
}
