$(document).on('click', 'button[data-bs-toggle="offcanvas"][data-action="history"]', function () {
    let deviceName = $(this).data('device-name');

    $('#offcanvasDeviceHistoryLabel').html(deviceName);
    $('#offcanvasDeviceHistoryBody').html();

    $.ajax({
        type: "GET",
        url: deviceName === 'gasMeter' ? '/gas/meter/history' : '/device/history',
        data: {"device": deviceName},
        success: function (result) {
            $('#offcanvasDeviceHistoryBody').html(result.content);
        }
    });
});
