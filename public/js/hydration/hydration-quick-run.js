document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('valve_quick_run');
    const sortableEl = document.getElementById('valves-sortable');
    const durationDisplay = document.getElementById('hydration_duration');

    const calculateTotalDuration = function () {
        if (!form || !durationDisplay) return;

        let totalMinutes = 0;
        const durationInputs = form.querySelectorAll('select[id$="_duration"]');
        const multiplicityInput = document.getElementById('multiplicity');
        const multiplicity = multiplicityInput ? parseInt(multiplicityInput.value) || 0 : 1;

        durationInputs.forEach(input => {
            totalMinutes += parseInt(input.value) || 0;
        });

        const grandTotalMinutes = totalMinutes * multiplicity;
        const hours = Math.floor(grandTotalMinutes / 60);
        const minutes = grandTotalMinutes % 60;

        durationDisplay.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
    };

    if (sortableEl) {
        new Sortable(sortableEl, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            handle: '.drag-handle',
            filter: '.btn, .btn-action, .form-select',
            preventOnFilter: false,
            onEnd: function () {
                calculateTotalDuration();
            }
        });
    }

    if (form) {
        form.addEventListener('click', function (e) {
            // Check if clicked element is (or is inside) the remove button
            const removeBtn = e.target.closest('.remove-valve');

            if (removeBtn) {
                e.preventDefault();
                const row = removeBtn.closest('.valve-row');
                if (row) {
                    row.remove();
                    calculateTotalDuration();
                }
                return;
            }

            // Check if clicked element is a "less" or "more" button
            const btn = e.target.closest('button');
            if (!btn || !btn.id) return;

            if (btn.id.endsWith('_less') || btn.id.endsWith('_more')) {
                const isLess = btn.id.endsWith('_less');
                const baseName = isLess ? btn.id.replace('_less', '') : btn.id.replace('_more', '');

                // Szukamy inputu albo po pełnej nazwie (np. multiplicity), albo z przyrostkiem _duration
                let input = document.getElementById(baseName + '_duration');
                if (!input) {
                    input = document.getElementById(baseName);
                }

                if (input) {
                    let val = parseInt(input.value) || 0;

                    // Definicja limitów w zależności od pola
                    const isMultiplicity = input.id === 'multiplicity';
                    const min = isMultiplicity ? 1 : 0;
                    const max = isMultiplicity ? 10 : 60;

                    // Increment or decrement
                    val = isLess ? val - 1 : val + 1;

                    // Clamp value between min and max
                    if (val < min) val = min;
                    if (val > max) val = max;

                    input.value = val;
                    calculateTotalDuration();
                }
            }
        });

        // Handle manual input validation and update duration
        form.addEventListener('input', function (e) {
            if (e.target.id && (e.target.id.endsWith('_duration') || e.target.id === 'multiplicity')) {
                if (e.target.tagName === 'INPUT') {
                    e.target.value = e.target.value.replace(/\D/g, '');
                }
                calculateTotalDuration();
            }
        });

        form.addEventListener('change', function (e) {
            if (e.target.id && (e.target.id.endsWith('_duration') || e.target.id === 'multiplicity')) {
                calculateTotalDuration();
            }
        });

        form.addEventListener('blur', function (e) {
            if (e.target.id && (e.target.id.endsWith('_duration') || e.target.id === 'multiplicity')) {
                let val = parseInt(e.target.value);

                const isMultiplicity = e.target.id === 'multiplicity';
                const min = isMultiplicity ? 1 : 0;
                const max = isMultiplicity ? 10 : 100;

                // If empty or NaN, set to min
                if (isNaN(val)) val = min;

                // Correct if out of bounds
                if (val < min) val = min;
                if (val > max) val = max;

                e.target.value = val;
                calculateTotalDuration();
            }
        }, true); // Use capture phase for 'blur' event delegation

        // Initial calculation on load
        calculateTotalDuration();
    }
});
