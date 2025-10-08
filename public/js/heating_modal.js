// heating_modal.js
(function () {
    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsa(selector, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(selector));
    }

    function getButtonInitialState(buttonEl) {
        return {
            value: buttonEl.value,
            text: buttonEl.textContent.trim()
        };
    }

    function anyDateFilled(inputs) {
        return inputs.some(function (el) {
            return el && el.value && el.value.trim().length > 0;
        });
    }

    function parseLocalDateTime(value) {
        if (!value) return null;
        var parts = value.split('T');
        if (parts.length !== 2) return null;
        var date = parts[0].split('-');
        var time = parts[1].split(':');
        if (date.length !== 3 || time.length < 2) return null;
        var year = parseInt(date[0], 10);
        var month = parseInt(date[1], 10) - 1;
        var day = parseInt(date[2], 10);
        var hour = parseInt(time[0], 10);
        var minute = parseInt(time[1], 10);
        var d = new Date(year, month, day, hour, minute, 0, 0);
        return isNaN(d.getTime()) ? null : d;
    }

    function toLocalDateTimeValue(d) {
        var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
        var y = d.getFullYear();
        var m = pad(d.getMonth() + 1);
        var day = pad(d.getDate());
        var h = pad(d.getHours());
        var min = pad(d.getMinutes());
        return y + '-' + m + '-' + day + 'T' + h + ':' + min;
    }

    function getInputsOrder(modalRoot) {
        var inputs = qsa('input#heating_start, input#heating_end', modalRoot);
        if (inputs.length < 2) {
            return { first: inputs[0] || null, second: null };
        }
        return { first: inputs[0], second: inputs[1] };
    }

    function setupModalBehaviour(modalRoot) {
        if (!modalRoot) return;

        var btn = qs('button[name="heating_action"]', modalRoot);
        if (!btn) return;

        var initial = getButtonInitialState(btn);

        var startInput = qs('#heating_start', modalRoot);
        var endInput = qs('#heating_end', modalRoot);

        var inputs = [startInput, endInput];

        function applyButtonState() {
            if (anyDateFilled(inputs)) {
                btn.value = 'create-heating-process';
                btn.textContent = 'Zapisz planowany harmonogram';
                btn.classList.add('btn-success');
            } else {
                btn.value = initial.value;
                btn.textContent = initial.text;
            }
        }

        function updateSecondMinOnly(first, second) {
            var firstDate = parseLocalDateTime(first.value);
            if (!firstDate) {
                second.min = '';
                return null;
            }
            firstDate.setMinutes(firstDate.getMinutes() + 1);
            var minStr = toLocalDateTimeValue(firstDate);
            second.min = minStr;
            return { minDate: firstDate, minStr: minStr };
        }

        function maybeClampSecondValue(second, minInfo) {
            if (!minInfo) return;
            if (!second.value) return;
            var secondDate = parseLocalDateTime(second.value);
            if (!secondDate) return;

            if (secondDate.getTime() < minInfo.minDate.getTime()) {
                // korekta TYLKO na change/blur
                second.value = minInfo.minStr;
            }
        }

        function applyDisablingAndMinRule() {
            var order = getInputsOrder(modalRoot);
            var first = order.first;
            var second = order.second;
            if (!first || !second) return;

            var firstHasValue = !!(first.value && first.value.trim().length > 0);
            second.disabled = !firstHasValue;

            if (firstHasValue) {
                updateSecondMinOnly(first, second);
            } else {
                second.min = '';
            }
        }

        function applyAll() {
            applyButtonState();
            applyDisablingAndMinRule();
        }

        // Listeners:
        // 1) Pierwsze pole – pełna reakcja (min + blokada + ewentualna korekta drugiego na change/blur)
        if (startInput) {
            ['input', 'change'].forEach(function (evt) {
                startInput.addEventListener(evt, function () {
                    // aktualizuj min i stan przycisku/disabled
                    applyButtonState();
                    var order = getInputsOrder(modalRoot);
                    if (!order.first || !order.second) return;
                    var minInfo = updateSecondMinOnly(order.first, order.second);

                    // na "input" NIE korygujemy wartości drugiego, aby nie kasować wpisywania
                    if (evt === 'change') {
                        maybeClampSecondValue(order.second, minInfo);
                    }

                    // zaktualizuj disabled po zmianie
                    order.second.disabled = !(order.first.value && order.first.value.trim().length > 0);
                }, { passive: true });
            });
        }

        // 2) Drugie pole – nie wymuszamy wartości podczas pisania
        if (endInput) {
            // Podczas wpisywania – tylko aktualizacja stanu przycisku
            ['input', 'keyup'].forEach(function (evt) {
                endInput.addEventListener(evt, function () {
                    applyButtonState();
                    // brak klampowania tutaj
                }, { passive: true });
            });

            // Na change/blur – jeśli poniżej min, podnieś do min
            ['change', 'blur'].forEach(function (evt) {
                endInput.addEventListener(evt, function () {
                    var order = getInputsOrder(modalRoot);
                    if (!order.first || !order.second) return;
                    var minInfo = updateSecondMinOnly(order.first, order.second);
                    maybeClampSecondValue(order.second, minInfo);
                    applyButtonState();
                }, { passive: true });
            });
        }

        // 3) Ogólne – na wypadek czyszczenia pól, „search” bywa emitowane
        inputs.forEach(function (el) {
            if (!el) return;
            el.addEventListener('search', function () {
                applyAll();
            }, { passive: true });
        });

        // Inicjalna synchronizacja
        applyAll();
    }

    function init() {
        var modal = document.getElementById('heatingControllerModal');
        if (!modal) return;

        setupModalBehaviour(modal);

        modal.addEventListener('shown.bs.modal', function () {
            setupModalBehaviour(modal);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
