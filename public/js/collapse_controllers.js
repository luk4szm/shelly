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

    $('#devicesControl button[data-role]').on('click', function () {
        let clickedButton = $(this);
        let direction = clickedButton.data('role');

        $.ajax({
            type: "PATCH",
            url: "/cover/open-close",
            data: {"direction": direction},
            success: function () {
                setTimeout(function () {
                    let collapseElement = clickedButton.closest('.accordion-collapse')[0];

                    if (collapseElement) {
                        let bsCollapse = bootstrap.Collapse.getInstance(collapseElement);

                        if (bsCollapse) {
                            bsCollapse.hide();
                        } else {
                            new bootstrap.Collapse(collapseElement).hide();
                        }
                    }
                }, 1000);
            },
            error: function (response) {
                const toastEl = document.getElementById('liveToast');
                const liveToast = new bootstrap.Toast(toastEl, {});

                $(".toast-body").html(response.responseJSON);

                liveToast.show();
            }
        });
    });
});

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
                gateStatusImg.attr("src",gateOpenImgSrc);
            } else {
                gateStatusImg.attr("src", gateClosedImgSrc);
            }
        },
    });
}

function onShowCoverControllers()
{

}
