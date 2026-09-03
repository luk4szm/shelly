$(function () {
    const reloadDelay = 5000;
    let reloadTimer = null;

    $(document).on('device:switch-success', function () {
        clearTimeout(reloadTimer);

        reloadTimer = setTimeout(function () {
            const statusElement = document.getElementById('device_status');

            if (!statusElement) {
                return;
            }

            const url = new URL(window.location.href);
            url.searchParams.set('_device_status_reload', Date.now().toString());

            $.get(url.toString(), function (html) {
                const documentParser = new DOMParser();
                const responseDocument = documentParser.parseFromString(html, 'text/html');
                const refreshedStatus = responseDocument.getElementById('device_status');

                if (!refreshedStatus) {
                    return;
                }

                $(statusElement).find('.current-status-duration').each(function () {
                    clearInterval($(this).data('intervalId'));
                });

                statusElement.innerHTML = refreshedStatus.innerHTML;

                $(statusElement).find('.current-status-duration').each(function () {
                    if (typeof updateElapsedTime === 'function') {
                        updateElapsedTime(this);
                    }
                });
            }).fail(function (xhr) {
                console.error('Nie udało się odświeżyć statusu urządzenia:', xhr.status, xhr.statusText);
            });
        }, reloadDelay);
    });
});
