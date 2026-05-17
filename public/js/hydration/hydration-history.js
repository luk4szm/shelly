document.addEventListener('DOMContentLoaded', function () {
    const singleViewRadio = document.getElementById('hydration-history-single');
    const groupedViewRadio = document.getElementById('hydration-history-grouped');
    const singleTable = document.getElementById('hydration-history-single-table');
    const groupedTable = document.getElementById('hydration-history-grouped-table');

    function toggleHistoryView() {
        if (singleViewRadio.checked) {
            singleTable.style.display = 'block';
            groupedTable.style.display = 'none';
        } else {
            singleTable.style.display = 'none';
            groupedTable.style.display = 'block';
        }
    }

    singleViewRadio.addEventListener('change', toggleHistoryView);
    groupedViewRadio.addEventListener('change', toggleHistoryView);

    // Set initial view based on checked radio button
    toggleHistoryView();
});
