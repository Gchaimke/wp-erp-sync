(function ($) {
    'use strict';
    function my_ajax(action, values='') {
        $.post(ajaxurl, {
            security: settings.nonce,
            action: action,
            data: values
        })
            .done(function (res) {
                console.log(res);
                $('#admin_message').text(res+" Errors.").fadeIn(1000).delay(3000).fadeOut(1000)
            })
            .fail(function () {
                console.log('AJAX failed!');
            })
            .always(function () {
                console.log('AJAX called.');
            });
    }

    $('.add_product_button').on('click', function () {
        var row = $(this).closest('tr');
        var columns = row.find('td');
        var values = "";
        $.each(columns, function (i, item) {
            if (i > 0 && i < 5) {
                values = values + ',' + item.innerHTML;
            }
        });
        my_ajax('add_product', values);
    });

    $('.add_all_product_button').on('click', function () {
        my_ajax('add_all_products');
    });

    $('.update_all_products_button').on('click', function () {
        my_ajax('update_all_products');
    });


})(jQuery);