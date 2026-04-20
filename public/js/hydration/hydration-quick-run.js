document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('valve_quick_run');
    const sortableEl = document.getElementById('valves-sortable');

    if (sortableEl) {
        new Sortable(sortableEl, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            handle: '.drag-handle', // Zmienione z .valve-row na .drag-handle
            filter: '.btn, .btn-action, .form-select', // Ignoruj te elementy przy próbie przeciągania
            preventOnFilter: false // Pozwól na normalne zdarzenia (kliknięcia) na przefiltrowanych elementach
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
                }
                return;
            }

            // Check if clicked element is a "less" or "more" button
            const btn = e.target.closest('button');
            if (!btn || !btn.id) return;

            if (btn.id.endsWith('_less') || btn.id.endsWith('_more')) {
                const isLess = btn.id.endsWith('_less');
                const baseName = isLess ? btn.id.replace('_less', '') : btn.id.replace('_more', '');
                const input = document.getElementById(baseName + '_duration');

                if (input) {
                    let val = parseInt(input.value) || 0;
                    const min = 0;
                    const max = 60;

                    // Increment or decrement
                    val = isLess ? val - 1 : val + 1;

                    // Clamp value between min and max
                    if (val < min) val = min;
                    if (val > max) val = max;

                    input.value = val;
                }
            }
        });

        // Handle manual input validation for all duration fields
        form.addEventListener('input', function (e) {
            if (e.target.id && e.target.id.endsWith('_duration')) {
                // Remove any non-digit characters
                e.target.value = e.target.value.replace(/\D/g, '');
            }
        });

        form.addEventListener('blur', function (e) {
            if (e.target.id && e.target.id.endsWith('_duration')) {
                let val = parseInt(e.target.value);
                const min = parseInt(e.target.min) || 0;
                const max = parseInt(e.target.max) || 100;

                // If empty or NaN, set to min
                if (isNaN(val)) val = min;

                // Correct if out of bounds
                if (val < min) val = min;
                if (val > max) val = max;

                e.target.value = val;
            }
        }, true); // Use capture phase for 'blur' event delegation
    }
});
