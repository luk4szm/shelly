$(document).on('click', 'a[data-bs-toggle="offcanvas"][data-action="history"]', function () {
    let deviceName = $(this).data('device-name');

    $('#offcanvasDeviceHistoryLabel').html(deviceName);
    $('#offcanvasDeviceHistoryBody').html();

    $.ajax({
        type: "GET",
        url: "/device/history/",
        data: {"device": deviceName},
        success: function (result) {
            $('#offcanvasDeviceHistoryBody').html(result.content);
        }
    });
});
