document.addEventListener('DOMContentLoaded', function () {
    const switchButtons = document.querySelectorAll('[data-bs-toggle="switch-icon"][data-shelly-id]');

    switchButtons.forEach(button => {
        button.addEventListener('click', function () {
            const shellyId = this.getAttribute('data-shelly-id');
            const url = `/scene/run/${shellyId}`;

            // Wysłanie zapytania AJAX (PATCH)
            fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    console.error('Błąd podczas uruchamiania sceny');
                    // Opcjonalnie: jeśli wystąpi błąd, możemy od razu przywrócić ikonę
                    this.classList.remove('active');
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                this.classList.remove('active');
            });

            // Przywrócenie stanu ikonki po około 2 sekundach
            setTimeout(() => {
                if (this.classList.contains('active')) {
                    this.classList.remove('active');
                }
            }, 2000);
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const sceneSpans = document.querySelectorAll('.scene-name');

    sceneSpans.forEach(span => {
        let text = span.textContent;

        // Zamiana "on" (niezależnie od wielkości liter)
        // \b zapewnia, że zmieniamy całe słowo, a nie fragment dłuższego wyrazu
        text = text.replace(/\bon\b/gi, '<span class="text-green fw-bold text-uppercase">ON</span>');

        // Zamiana "off" (niezależnie od wielkości liter)
        text = text.replace(/\boff\b/gi, '<span class="text-red fw-bold text-uppercase">OFF</span>');

        // Podmiana HTML wewnątrz spana
        span.innerHTML = text;
    });
});
