$(document).ready(function () {
    // time must be the same as in css for .long-press-btn
    const holdDuration = 350;
    const feedbackDisplayDuration = 2000;

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

        // Usuń klasy stanu i przywróć klasę 'btn-azure'
        button.removeClass('is-holding btn-success btn-danger').addClass('btn-azure');

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

            textSpan.text('Przytrzymaj...');
            button.addClass('is-holding');

            const holdTimer = setTimeout(function () {
                button.data('action-triggered', true);
                textSpan.text('Gotowe!');

                // Wyłącz przycisk, aby zapobiec dalszym interakcjom
                button.prop('disabled', true);

                // Bezpośrednio zamień klasę 'btn-azure' na 'btn-success'
                button.removeClass('btn-azure').addClass('btn-success');

                const controller = button.data('controller');
                const action = button.data('action');

                const apiUrls = {
                    'gate': '/supla/gate/open-close',
                    'covers': '/cover/open-close',
                    'garage': '/garage/move',
                    'scene': '/scene'
                };
                const apiUrl = apiUrls[controller];

                if (apiUrl) {
                    $.ajax({
                        type: "PATCH",
                        url: apiUrl,
                        data: { "direction": action },
                        success: function () {
                            console.log(`Akcja '${action}' dla '${controller}' wykonana pomyślnie.`);
                        },
                        error: function (response) {
                            console.error("Błąd podczas wykonywania akcji AJAX:", response);
                            textSpan.text('Wystąpił błąd');
                            button.removeClass('btn-success').addClass('btn-danger');
                        },
                        complete: function() {
                            // Po określonym czasie zresetuj przycisk, niezależnie od wyniku
                            setTimeout(() => resetButtonState(button), feedbackDisplayDuration);
                        }
                    });
                } else {
                    console.error('Nie można było ustalić adresu API dla urządzenia o nazwie: ', controller);
                    // Jeśli nie ma API, natychmiast zresetuj
                    resetButtonState(button);
                }

            }, holdDuration);

            // Zapisz ID timera w danych przycisku, aby uniknąć konfliktu
            button.data('holdTimer', holdTimer);
        })
        .on('mouseup mouseleave touchend touchmove', '.long-press-btn', function () {
            const button = $(this);
            clearTimeout(button.data('holdTimer'));

            // Resetuj przycisk tylko wtedy, gdy akcja NIE została uruchomiona
            if (button.hasClass('is-holding') && !button.data('action-triggered')) {
                resetButtonState(button);
            }
        });

    $(document).on('click', 'span[data-role="check_status"]', function () {
        const clickedSpan = $(this);
        const controller = clickedSpan.data('controller');

        if (!controller) {
            console.error('Nie znaleziono atrybutu data-controller na klikniętym elemencie.');
            return;
        }

        const readApiUrls = {
            'gate': '/supla/gate/read',
            'covers': '/cover/read',
            'garage': '/garage/read'
        };
        const apiUrl = readApiUrls[controller];

        clickedSpan.addClass('is-loading').removeClass('bg-light-lt bg-red bg-green');

        $.ajax({
            type: "GET",
            url: apiUrl,
            success: function (response) {
                let status;

                switch (controller) {
                    case 'gate':
                    case 'garage':
                        status = response.is_open === true;
                        break;
                    case 'covers':
                        status = response.last_direction === 'open';
                        break;
                }

                if (status === true) {
                    clickedSpan.addClass('bg-green');
                } else {
                    clickedSpan.addClass('bg-red');
                }
            },
            error: function (xhr, status, error) {
                console.error(`Błąd podczas sprawdzania statusu dla "${controller}":`, error);

                clickedSpan.addClass('bg-warning');
            },
            complete: function () {
                clickedSpan.removeClass('is-loading');
            }
        });
    });
});
