document.addEventListener('DOMContentLoaded', function () {
    const switchButtons = document.querySelectorAll('[data-bs-toggle="switch-icon"][data-shelly-id]');

    switchButtons.forEach(button => {
        button.addEventListener('click', function () {
            const shellyId = this.getAttribute('data-shelly-id');
            const url = `/scene/run/${shellyId}`;
            const sceneRow = this.closest('.row');
            const messageContainer = sceneRow.querySelector('.scene-name').parentElement;
            let errorElement = messageContainer.querySelector('.scene-run-error');
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.className = 'scene-run-error text-danger small mt-1';
                errorElement.setAttribute('role', 'alert');
                messageContainer.appendChild(errorElement);
            }
            errorElement.textContent = '';

            // Wysłanie zapytania AJAX (PATCH)
            fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            })
            .then(async response => {
                if (!response.ok) {
                    const data = await response.json().catch(() => ({}));
                    errorElement.textContent = data.error || 'Nie udało się uruchomić sceny.';
                    this.classList.remove('active');
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                errorElement.textContent = 'Błąd połączenia podczas uruchamiania sceny.';
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
