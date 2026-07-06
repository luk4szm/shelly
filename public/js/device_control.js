$(document).ready(function () {
    // time must be the same as in css for .long-press-btn
    const holdDuration = 350;
    const feedbackDisplayDuration = 2000;
    const sceneStepDelay = 2000; // 2 seconds delay between scene steps
    const apiCallDelay = 1000; // 1 second delay between API calls to respect Shelly Cloud limits
    const statusClearDelay = 3000; // 3 seconds to clear the status display
    const getStatusDisplay = () => $('.scene-controller-status');

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

    const apiUrls = {
        'gate': '/supla/gate/open-close',
        'covers': '/cover/open-close',
        'garage': '/garage/move',
        'config_set': '/config/set',
        'scene': '/scene/run',
        'switch': '/device/switch/turn',
    };

    // Przeniesiono readApiUrls do globalnego zakresu dla dostępu w executeScene
    const readApiUrls = {
        'gate': '/supla/gate/read',
        'covers': '/cover/read',
        'garage': '/garage/read'
    };

    const scenes = {
        'coming': [ // Wracam do domu
            { controller: 'gate', action: 'open', text: 'Otwieranie bramy...' },
            { controller: 'garage', action: 'open', text: 'Otwieranie garażu...' },
            { controller: 'covers', action: 'open', text: 'Otwieranie rolet...' },
            { controller: 'switch', action: 'on', device_id: '64b708097270', channel: 0, text: 'Włączanie pompy CWU...' },
            { controller: 'config', action: 'set_occupancy_mode_home', text: 'Zmieniam tryb domu na: <b>w domu</b>...' }
        ],
        'leaving': [ // Wychodzę z domu
            { controller: 'covers', action: 'close', text: 'Zamykanie rolet...' },
            { controller: 'garage', action: 'close', text: 'Zamykanie garażu...' },
            { controller: 'gate', action: 'open', text: 'Otwieranie bramy...' },
            { controller: 'switch', action: 'off', device_id: '64b708097270', channel: 0, text: 'Wyłączanie pompy CWU...' },
            { controller: 'config', action: 'set_occupancy_mode_away', text: 'Zmieniam tryb domu na: <b>nieobecność</b>...' }
        ],
        'kindergarten-work': [ // Przedszkole -> Forum
            { controller: 'gate', action: 'open', text: 'Otwieranie bramy...' },
            { controller: 'navigation', action: 'start', text: 'Uruchamianie nawigacji...' }
        ],
        'sleeping': [ // Idziemy spać
            { controller: 'garage', action: 'close', text: 'Zamykanie garażu...' },
            { controller: 'covers', action: 'close', text: 'Zamykanie rolet...' },
            { controller: 'scene', action: '1776464366415', text: 'Wyłączam światła...' },
            { controller: 'switch', action: 'off', device_id: '64b708097270', channel: 0, text: 'Wyłączanie pompy CWU...' },
            { controller: 'config', action: 'set_occupancy_mode_sleep', text: 'Zmieniam tryb domu na: <b>spanie</b>...' }
        ],
        'waking': [ // Pobudka
            { controller: 'covers', action: 'open', text: 'Otwieranie rolet...' },
            { controller: 'switch', action: 'on', device_id: '64b708097270', channel: 0, text: 'Włączanie pompy CWU...' },
            { controller: 'config', action: 'set_occupancy_mode_home', text: 'Zmieniam tryb domu na: <b>w domu</b>...' }
        ]
    };

    // Mapowanie kluczy scen na ich nazwy wyświetlane
    const sceneDisplayNames = {
        'leaving': 'Wychodzę z domu',
        'coming': 'Wracam do domu',
        'kindergarten-work': 'Przedszkole -> Forum',
        'sleeping': 'Idziemy spać',
        'waking': 'Pobudka'
    };

    function executeScene(button, sceneActions) {
        let currentActionIndex = 0;

        function finalizeScene(message, isSuccess) {
            getStatusDisplay().append(`<div>${message}</div>`);
            button.removeClass('btn-azure').addClass(isSuccess ? 'btn-success' : 'btn-danger');

            setTimeout(() => resetButtonState(button), feedbackDisplayDuration);

            setTimeout(() => {
                getStatusDisplay().empty();
                if (isSuccess) {
                    button.closest('.scene-hideable-div').hide();
                }
            }, statusClearDelay);
        }

        function performActionAjax(step, apiUrl, dataToSend) {
            $.ajax({
                type: "PATCH",
                url: apiUrl,
                data: dataToSend,
                success: function () {
                    console.log(`Akcja '${step.action}' dla '${step.controller}' wykonana pomyślnie.`);
                    currentActionIndex++;
                    setTimeout(executeNextAction, sceneStepDelay);
                },
                error: function (response) {
                    const errorMsg = `Błąd przy: ${step.text}`;
                    console.error("Błąd podczas wykonywania akcji AJAX:", response);
                    finalizeScene(errorMsg, false);
                }
            });
        }

        function executeNextAction() {
            if (currentActionIndex >= sceneActions.length) {
                finalizeScene('Zakończono!', true);
                return;
            }

            const step = sceneActions[currentActionIndex];

            // Obsługa specjalna: krok "navigation" – prosty link do Google Maps z hasha przycisku
            if (step.controller === 'navigation') {
                const mapHash = (button && typeof button.data === 'function') ? button.data('map-hash') : null;

                if (!mapHash) {
                    const errorMsg = 'Brak "data-map-hash" na przycisku sceny – nie można uruchomić nawigacji.';
                    console.error(errorMsg);
                    finalizeScene(errorMsg, false);
                    return;
                }

                const mapsWebUrl = `https://maps.app.goo.gl/${mapHash}?g_st=ac`;

                try {
                    // Proste przejście — najlepsza kompatybilność na mobile
                    window.location.href = mapsWebUrl;

                    // Dla spójności (jeśli kiedyś otworzysz w nowej karcie), zachowujemy te linie:
                    currentActionIndex++;
                    setTimeout(executeNextAction, sceneStepDelay);
                } catch (e) {
                    console.error('Błąd podczas otwierania nawigacji:', e);
                    finalizeScene('Nie udało się uruchomić nawigacji.', false);
                }
                return;
            }

            if (step.controller === 'config' && step.action === 'set_occupancy_mode_sleep') {
                const configApiUrl = apiUrls['config_set'];
                if (!configApiUrl) {
                    const errorMsg = `Błąd konfiguracji dla ${step.controller}`;
                    console.error(errorMsg);
                    finalizeScene(errorMsg, false);
                    return;
                }
                getStatusDisplay().append(`<div>${step.text}</div>`);

                performActionAjax(step, configApiUrl, { "name": "occupancy_mode", "value": "sleeping" });
                return;
            }

            if (step.controller === 'config' && step.action === 'set_occupancy_mode_home') {
                const configApiUrl = apiUrls['config_set'];
                if (!configApiUrl) {
                    const errorMsg = `Błąd konfiguracji dla ${step.controller}`;
                    console.error(errorMsg);
                    finalizeScene(errorMsg, false);
                    return;
                }
                getStatusDisplay().append(`<div>${step.text}</div>`);

                performActionAjax(step, configApiUrl, { "name": "occupancy_mode", "value": "home" });
                return;
            }

            if (step.controller === 'config' && step.action === 'set_occupancy_mode_away') {
                const configApiUrl = apiUrls['config_set'];
                if (!configApiUrl) {
                    const errorMsg = `Błąd konfiguracji dla ${step.controller}`;
                    console.error(errorMsg);
                    finalizeScene(errorMsg, false);
                    return;
                }
                getStatusDisplay().append(`<div>${step.text}</div>`);

                performActionAjax(step, configApiUrl, { "name": "occupancy_mode", "value": "away" });
                return;
            }

            if (step.controller === 'scene') {
                const sceneRunUrl = `${apiUrls['scene']}/${step.action}`;
                getStatusDisplay().append(`<div>${step.text}</div>`);

                performActionAjax(step, sceneRunUrl, {});
                return;
            }

            if (step.controller === 'switch') {
                const switchApiUrl = apiUrls['switch'];
                getStatusDisplay().append(`<div>${step.text}</div>`);

                performActionAjax(step, switchApiUrl, {
                    "deviceId": step.device_id,
                    "channel": step.channel,
                    "action": step.action
                });
                return;
            }

            const apiUrl = apiUrls[step.controller];

            if (!apiUrl) {
                const errorMsg = `Błąd konfiguracji dla ${step.controller}`;
                console.error(errorMsg);
                finalizeScene(errorMsg, false);
                return;
            }

            // Sprawdź status urządzenia przed wysłaniem żądania, aby uniknąć zbędnych akcji
            if (['gate', 'garage', 'covers'].includes(step.controller) && (step.action === 'open' || step.action === 'close')) {
                const readApiUrl = readApiUrls[step.controller];
                if (!readApiUrl) {
                    const errorMsg = `Błąd konfiguracji dla odczytu statusu ${step.controller}`;
                    console.error(errorMsg);
                    finalizeScene(errorMsg, false);
                    return;
                }

                const deviceNamesGenitive = { 'gate': 'bramy', 'garage': 'garażu', 'covers': 'rolet' }; // Genitive for "status bramy/garażu/rolet"
                const deviceNameGenitive = deviceNamesGenitive[step.controller];

                const statusSpanId = `status-check-result-${step.controller}-${currentActionIndex}`;
                getStatusDisplay().append(`<div>Sprawdzam status ${deviceNameGenitive}: <span id="${statusSpanId}"></span></div>`);

                $.ajax({
                    type: "GET",
                    url: readApiUrl,
                    success: function (response) {
                        let isCurrentlyOpen = false;
                        if (step.controller === 'gate' || step.controller === 'garage') {
                            isCurrentlyOpen = response.is_open === true;
                        } else if (step.controller === 'covers') {
                            isCurrentlyOpen = response.last_direction === 'open';
                        }

                        let alreadyInDesiredPosition = false;
                        let actionMessage = '';
                        let skipMessage = '';

                        if (step.action === 'open') {
                            alreadyInDesiredPosition = isCurrentlyOpen;
                            actionMessage = 'otwieram!';
                            skipMessage = 'otwarte, pomijam';
                        } else if (step.action === 'close') {
                            alreadyInDesiredPosition = !isCurrentlyOpen; // If action is 'close', and it's not open (i.e., closed)
                            actionMessage = 'zamykam!';
                            skipMessage = 'zamknięte, pomijam';
                        }

                        const resultSpan = $(`#${statusSpanId}`);

                        if (alreadyInDesiredPosition) {
                            resultSpan.html(`<span style="color: grey;">${skipMessage}</span>`);
                            currentActionIndex++;
                            setTimeout(executeNextAction, sceneStepDelay);
                        } else {
                            resultSpan.html(`<span style="color: green; font-weight: bold;">${actionMessage}</span>`);
                            // Add delay before performing the action (avoid shelly cloud 429 response)
                            setTimeout(() => performActionAjax(step, apiUrl, { "direction": step.action }), apiCallDelay);
                        }
                    },
                    error: function (xhr, status, error) {
                        const errorMsg = `Błąd podczas sprawdzania statusu ${deviceNameGenitive}: ${error}`;
                        console.error("Błąd podczas sprawdzania statusu AJAX:", error);
                        finalizeScene(errorMsg, false);
                    }
                });
            } else {
                // Dla innych kontrolerów lub akcji, wyświetl oryginalny tekst i przejdź bezpośrednio
                getStatusDisplay().append(`<div>${step.text}</div>`);
                performActionAjax(step, apiUrl, { "direction": step.action });
            }
        }

        executeNextAction();
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

                // Wyłącz przycisk, aby zapobiec dalszym interakcjom
                button.prop('disabled', true);

                const controller = button.data('controller');
                const action = button.data('action');

                if (controller === 'scene') {
                    const sceneActions = scenes[action];
                    if (sceneActions) {
                        const sceneName = sceneDisplayNames[action] || action; // Pobierz nazwę sceny
                        getStatusDisplay().html(`<div>Rozpoczynanie sceny <strong>${sceneName}</strong>...</div>`); // Zaktualizowany komunikat
                        button.removeClass('btn-azure').addClass('btn-success');
                        executeScene(button, sceneActions);
                    } else {
                        const errorMsg = `Błąd: Nie zdefiniowano sceny dla akcji: ${action}`;
                        console.error(errorMsg);
                        getStatusDisplay().html(`<div>${errorMsg}</div>`);
                        button.removeClass('btn-azure').addClass('btn-danger');
                        setTimeout(() => {
                            resetButtonState(button);
                        }, feedbackDisplayDuration);
                        setTimeout(() => {
                            getStatusDisplay().html('');
                        }, statusClearDelay);
                    }
                } else {
                    // Original non-scene logic
                    textSpan.text('Gotowe!');
                    button.removeClass('btn-azure').addClass('btn-success');
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
                console.error(`Błąd podczas sprawdzania statusu dla \"${controller}\":`, error);

                clickedSpan.addClass('bg-warning');
            },
            complete: function () {
                clickedSpan.removeClass('is-loading');
            }
        });
    });
});
