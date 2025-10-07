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
        // value w formacie "YYYY-MM-DDTHH:MM" (datetime-local)
        if (!value) return null;
        var parts = value.split('T');
        if (parts.length !== 2) return null;
        var date = parts[0].split('-');
        var time = parts[1].split(':');
        if (date.length !== 3 || time.length < 2) return null;
        var year = parseInt(date[0], 10);
        var month = parseInt(date[1], 10) - 1; // 0-based
        var day = parseInt(date[2], 10);
        var hour = parseInt(time[0], 10);
        var minute = parseInt(time[1], 10);
        var d = new Date(year, month, day, hour, minute, 0, 0);
        return isNaN(d.getTime()) ? null : d;
    }

    function toLocalDateTimeValue(d) {
        // Zwraca "YYYY-MM-DDTHH:MM" w lokalnej strefie
        var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
        var y = d.getFullYear();
        var m = pad(d.getMonth() + 1);
        var day = pad(d.getDate());
        var h = pad(d.getHours());
        var min = pad(d.getMinutes());
        return y + '-' + m + '-' + day + 'T' + h + ':' + min;
    }

    function clampSecondToMin(secondEl, minStr) {
        if (!secondEl) return;
        secondEl.min = minStr || '';
        if (!secondEl.value) return;
        if (minStr && secondEl.value < minStr) {
            secondEl.value = minStr;
        }
    }

    function inputsOrder(modalRoot) {
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

        function applyDisablingAndMinRule() {
            var order = inputsOrder(modalRoot);
            var first = order.first;
            var second = order.second;
            if (!first || !second) return;

            var firstHasValue = !!(first.value && first.value.trim().length > 0);
            second.disabled = !firstHasValue;

            if (firstHasValue) {
                var firstDate = parseLocalDateTime(first.value);
                if (firstDate) {
                    // +1 minuta
                    firstDate.setMinutes(firstDate.getMinutes() + 1);
                    var minForSecond = toLocalDateTimeValue(firstDate);
                    clampSecondToMin(second, minForSecond);
                }
            } else {
                // Gdy pierwszy jest pusty, czyścimy ograniczenie min i blokujemy drugi
                clampSecondToMin(second, '');
            }
        }

        function applyAll() {
            applyButtonState();
            applyDisablingAndMinRule();
        }

        inputs.forEach(function (el) {
            if (!el) return;
            ['input', 'change', 'keyup', 'blur'].forEach(function (evt) {
                el.addEventListener(evt, applyAll, { passive: true });
            });
            el.addEventListener('search', applyAll, { passive: true });
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
