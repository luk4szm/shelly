$(document).ready(function () {
    // scroll under gate opener range
    document.querySelector('#temperatures_row').scrollIntoView({
        behavior: 'smooth'
    });

    $('#gate_opener_range_input').on('mouseup touchend', function () {
        var currentValue = parseInt($(this).val());

        if (currentValue === 100) {
            $.ajax({
                url: '/supla/gate/open',
                method: 'PATCH',
                success: function (response) {
                    console.log(response);

                    $(this).val(0);
                },
            });
        } else {
            $(this).val(0);
        }
    });
});
