const statusModalElement = document.getElementById('deviceStatusModal')

statusModalElement.addEventListener('hidden.bs.modal', event => {
    $('#device_status_modal_content').html('<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>');
});

$(document).on('click', 'button[data-bs-toggle="modal"][data-action="status"]', function () {
    loadDeviceStatus($(this).data('device-name'));
});

function loadDeviceStatus(device) {
    $.ajax({
        url: '/data/status',
        method: 'GET',
        dataType: 'json',
        data: {'device': device},
        success: function (response) {
            $('#device_status_modal_content').html('<pre>' + JSON.stringify(response, undefined, 2) + '</pre>');
        },
        error: function (xhr, status, error) {
            console.error('Błąd ładowania danych:', error);
        }
    });
}
