$(document).ready(function () {
    const myCollapsible = document.getElementById('devicesControl');

    myCollapsible.addEventListener('show.bs.collapse', event => {
        switch (event.target.id) {
            case 'gate-control':
                onShowGateControllers();
                break;
            case 'cover-control':
                onShowCoverControllers();
                break;
        }
    });

    setupDeviceControls();
});

function setupDeviceControls() {
    const controlButtons = $('#cover-control button[data-role], #garage-control button[data-role]');

    controlButtons.on('click', function () {
        const clickedButton = $(this);
        const collapseContainer = clickedButton.closest('.accordion-collapse');
        const containerId = collapseContainer.attr('id');
        const direction = clickedButton.data('role');

        let apiUrl;

        if (containerId === 'cover-control') {
            apiUrl = '/cover/open-close';
        } else if (containerId === 'garage-control') {
            apiUrl = '/garage/move';
        }

        if (apiUrl) {
            $.ajax({
                type: "PATCH",
                url: apiUrl,
                data: {"direction": direction},
                success: function () {
                    setTimeout(function () {
                        const collapseElement = collapseContainer[0];
                        if (collapseElement) {
                            let bsCollapse = bootstrap.Collapse.getInstance(collapseElement);

                            if (bsCollapse) {
                                bsCollapse.hide();
                            } else {
                                // Awaryjne ukrycie, jeśli instancja nie została znaleziona
                                new bootstrap.Collapse(collapseElement).hide();
                            }
                        }
                    }, 1000);
                },
                error: function (response) {
                    const toastEl = document.getElementById('liveToast');
                    if (toastEl && response.responseJSON) {
                        const liveToast = new bootstrap.Toast(toastEl, {});
                        $(".toast-body").html(response.responseJSON);
                        liveToast.show();
                    }
                }
            });
        } else {
            console.error('Nie można było ustalić adresu API dla kontenera o ID:', containerId);
        }
    });
}

function onShowGateControllers()
{
    let gateStatusImg = $("img#gate-status");
    let gateOpenImgSrc = gateStatusImg.data("open-svg");
    let gateClosedImgSrc = gateStatusImg.data("closed-svg");

    $.ajax({
        url: '/supla/gate/read',
        method: 'GET',
        success: function (response) {
            if (response.isOpen === true) {
                gateStatusImg.attr("src", gateOpenImgSrc);
            } else {
                gateStatusImg.attr("src", gateClosedImgSrc);
            }

            gateStatusImg.css({
                "width": "80px",
                "height": "80px",
                "margin": "0"
            });
        },
    });
}

function onShowCoverControllers()
{
    let coverStatusImg = $("img#cover-status");
    let coverOpenImgSrc = coverStatusImg.data("open-svg");
    let coverClosedImgSrc = coverStatusImg.data("closed-svg");

    $.ajax({
        url: '/cover/read',
        method: 'GET',
        success: function (response) {
            if (response.last_direction === 'open') {
                coverStatusImg.attr("src", coverOpenImgSrc);
            } else {
                coverStatusImg.attr("src", coverClosedImgSrc);
            }
        },
    });
}
