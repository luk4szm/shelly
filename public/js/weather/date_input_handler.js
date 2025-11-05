document.addEventListener('DOMContentLoaded', function () {
    const isValidMonthStr = (s) => /^\d{4}-\d{2}$/.test(s);
    const dateInput = document.getElementById('wheater_date');
    const prevBtn = document.getElementById('prev-day-btn');
    const nextBtn = document.getElementById('next-day-btn');

    if (!dateInput || !prevBtn || !nextBtn) return;

    const urlDate = new URL(window.location.href).searchParams.get('date');

    const toggleNavButtons = (show) => {
        prevBtn.classList.toggle('d-none', !show);
        nextBtn.classList.toggle('d-none', !show);
    };

    // Start: bez wartości w input, strzałki widoczne tylko przy poprawnym ?date=
    if (isValidMonthStr(urlDate)) {
        dateInput.value = urlDate;
        toggleNavButtons(true);
    } else {
        dateInput.value = '';
        toggleNavButtons(false);
    }

    dateInput.addEventListener('change', (e) => {
        const v = e.target.value;
        toggleNavButtons(isValidMonthStr(v));
    });

    window.addEventListener('popstate', () => {
        const d = new URL(window.location.href).searchParams.get('date');
        if (isValidMonthStr(d)) {
            dateInput.value = d;
            toggleNavButtons(true);
        } else {
            dateInput.value = '';
            toggleNavButtons(false);
        }
    });
});
