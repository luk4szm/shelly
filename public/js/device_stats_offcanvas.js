$(document).on('click', 'a[data-bs-toggle="offcanvas"]', function () {
    let deviceName = $(this).data('device-name');

    $('#offcanvasDeviceStatsLabel').html(deviceName);

    $.ajax({
        type: "GET",
        url: "/device/daily-stats/",
        data: {"device": deviceName},
        success: function (result) {
            $('#offcanvasDeviceStatsBody').html(result.content);
        }
    });
});
