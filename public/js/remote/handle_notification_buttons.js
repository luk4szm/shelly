document.addEventListener('DOMContentLoaded', function () {
    const showChannelsBtn = document.querySelector('[data-role="show-channels"]');

    if (showChannelsBtn) {
        showChannelsBtn.addEventListener('click', function (e) {
            e.preventDefault();

            // Znajdujemy wspólny kontener (row), w którym są nasze elementy
            const row = this.closest('.row');

            if (row) {
                // Ukrywamy kontener przycisku (ten z klasą col-12)
                const parentCol = this.closest('.col-12');
                if (parentCol) {
                    parentCol.classList.add('d-none');
                }

                // Pokazujemy ukryte kolumny col-6
                const hiddenCols = row.querySelectorAll('.col-6.d-none');
                hiddenCols.forEach(col => {
                    col.classList.remove('d-none');
                });
            }
        });
    }
});
