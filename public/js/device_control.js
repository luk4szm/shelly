$(document).ready(function () {
    let holdTimer;
    const holdDuration = 1000;
    const feedbackDisplayDuration = 2500;

    /**
     * Funkcja pomocnicza do resetowania przycisku do jego pierwotnego stanu.
     * @param {jQuery} button - Obiekt jQuery reprezentujący przycisk.
     */
    function resetButtonState(button) {
        const textSpan = button.find('span');
        const originalText = button.data('original-text');

        // Przywróć oryginalny tekst
        if (originalText) {
            textSpan.text(originalText);
        }

        // Usuń klasy stanu i przywróć klasę 'btn-info'
        button.removeClass('is-holding btn-success btn-danger').addClass('btn-info');

        // Zresetuj flagę i włącz przycisk
        button.data('action-triggered', false);
        button.prop('disabled', false); // Włącz przycisk
    }

    $(document)
        .on('mousedown touchstart', '.long-press-btn', function (e) {
            // Ignoruj kliknięcie, jeśli przycisk jest już wyłączony
            if ($(this).is(':disabled')) {
                return;
            }
            e.preventDefault();

            const button = $(this);
            button.data('action-triggered', false);

            const textSpan = button.find('span');

            // Zapisz stan początkowy (tylko tekst), jeśli jeszcze nie zapisano
            if (!button.data('original-text')) {
                button.data('original-text', textSpan.text());
            }

            textSpan.text('Odliczam...');
            button.addClass('is-holding');

            holdTimer = setTimeout(function () {
                button.data('action-triggered', true);
                textSpan.text('Działam!');

                // Wyłącz przycisk, aby zapobiec dalszym interakcjom
                button.prop('disabled', true);

                // Bezpośrednio zamień klasę 'btn-info' na 'btn-success'
                button.removeClass('btn-info').addClass('btn-success');

                const controller = button.data('controller');
                const action = button.data('action');
                let apiUrl;

                if (controller === 'gate') {
                    apiUrl = '/supla/gate/open-close';
                } else if (controller === 'covers') {
                    apiUrl = '/cover/open-close';
                } else if (controller === 'garage') {
                    apiUrl = '/garage/move';
                }

                if (apiUrl) {
                    $.ajax({
                        type: "PATCH",
                        url: apiUrl,
                        data: { "direction": action },
                        success: function () {
                            console.log(`Akcja '${action}' dla '${controller}' wykonana pomyślnie.`);
                            // Po 5 sekundach zresetuj przycisk
                            setTimeout(() => resetButtonState(button), feedbackDisplayDuration);
                        },
                        error: function (response) {
                            console.error("Błąd podczas wykonywania akcji AJAX:", response);

                            // Logika obsługi błędu
                            textSpan.text('Wystąpił błąd');
                            // Zamień klasę 'btn-success' na 'btn-danger'
                            button.removeClass('btn-success').addClass('btn-danger');

                            // Po 5 sekundach zresetuj przycisk
                            setTimeout(() => resetButtonState(button), feedbackDisplayDuration);
                        }
                    });
                } else {
                    console.error('Nie można było ustalić adresu API dla urządzenia o nazwie: ', controller);
                    // Jeśli nie ma API, natychmiast zresetuj
                    resetButtonState(button);
                }

            }, holdDuration);
        })
        .on('mouseup mouseleave touchend touchmove', '.long-press-btn', function () {
            const button = $(this);
            clearTimeout(holdTimer);

            // Resetuj przycisk tylko wtedy, gdy akcja NIE została uruchomiona
            if (button.hasClass('is-holding') && !button.data('action-triggered')) {
                resetButtonState(button);
            }
        });
});
