(function ($) {
    'use strict';
    function my_ajax(action, values = '', html = false) {
        $.post(ajaxurl, {
            security: settings.nonce,
            action: action,
            data: values
        })
            .done(function (res) {
                console.log(res);
                if (html) {
                    $('#admin_message').append(res).fadeIn(1000);
                } else {
                    $('#admin_message').text(res + " Errors.").fadeIn(1000).delay(3000).fadeOut(1000);
                }

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

    $('.search_for_product').on('click', function () {
        $('#admin_message').empty();
        $('.add_products_from_search').fadeIn(1000);
        my_ajax('search_for_product', $('#search_product').val(), true);
    });

    $('.add_products_from_search').on('click', function () {
        var values = '';
        $('.search_row').each(function () {
            $(this).find('td').each(function (i, item) {
                if (i < 4 && item.innerHTML!='') {
                    values = values + ',' + item.innerHTML;
                }
            });
            values = values + ';';
        });
        my_ajax('add_all_products', values);
    });


})(jQuery);