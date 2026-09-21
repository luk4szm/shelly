document.addEventListener('DOMContentLoaded', function () {
    const themeToggle = document.getElementById('mobile-theme-toggle');

    if (!themeToggle) {
        return;
    }

    const isDarkTheme = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';

    themeToggle.checked = isDarkTheme();

    themeToggle.addEventListener('change', function () {
        const theme = themeToggle.checked ? 'dark' : 'light';

        localStorage.setItem('tabler-theme', theme);

        if (theme === 'dark') {
            document.documentElement.setAttribute('data-bs-theme', theme);
        } else {
            document.documentElement.removeAttribute('data-bs-theme');
        }
    });
});
