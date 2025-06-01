$(document).ready(function () {
    $('#cover-buttons button').on('click', function () {
        let direction = $(this).data('role');

        $.ajax({
            type: "PATCH",
            url: "/cover/open-close",
            data: {"direction": direction},
            success: function (result) {
                console.log(result)
            }
        });

        let accordionCollapseElement = $('#cover-buttons');
        let bsCollapse = bootstrap.Collapse.getInstance(accordionCollapseElement[0]);

        bsCollapse.hide(); // close accordion
    });
});
