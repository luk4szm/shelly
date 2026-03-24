document.addEventListener('DOMContentLoaded', function() {
    const showChannelsBtn = document.querySelector('[data-role="show-channels"]');

    if (showChannelsBtn) {
        showChannelsBtn.addEventListener('click', function(e) {
            e.preventDefault();

            const parentCol = this.closest('.col-12');
            const row = this.closest('.row');
            const hiddenCols = row.querySelectorAll('.col-6.d-none');

            // 1. Start animacji znikania przycisku głównego
            parentCol.classList.add('fade-out');

            // Czekamy na koniec animacji znikania (300ms)
            setTimeout(() => {
                parentCol.style.display = 'none'; // Całkowicie usuwamy z układu

                hiddenCols.forEach(col => {
                    // 2. Najpierw przygotowujemy element (usuwamy d-none i dajemy stan init)
                    col.classList.remove('d-none');
                    col.classList.add('fade-in-init');

                    // 3. Używamy podwójnego requestAnimationFrame, aby wymusić start animacji w kolejnej klatce
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            col.classList.add('fade-in-show');
                        });
                    });
                });
            }, 300);
        });
    }
});
