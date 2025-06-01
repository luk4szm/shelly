$(document).ready(function () {
    $('#devicesControl button[data-role]').on('click', function () {
        let direction = $(this).data('role');

        $.ajax({
            type: "PATCH",
            url: "/cover/open-close",
            data: {"direction": direction},
            success: function (result) {
                console.log(result);

                let collapseElement = $(this).closest('.accordion-collapse')[0];
                let bsCollapse = bootstrap.Collapse.getInstance(collapseElement);

                bsCollapse.hide();
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
