(function ($) {
    'use strict';
    function my_ajax(action, values = '', html = false) {
        $.post(ajaxurl, {
            security: settings.nonce,
            action: action,
            data: values
        })
            .done(function (res) {
                if (html) {
                    $('#admin_message').append(res).fadeIn(1000);
                } else {
                    $('#admin_message').text(res).fadeIn(1000).delay(3000).fadeOut(1000);
                }

            })
            .fail(function () {
                console.log('AJAX failed!');
            })
            .always(function () {
                console.log('AJAX called.');
            });
    }

    $('div#wpbody-content').on('click', 'button', function () {
        var row = $(this).closest('tr');
        var columns = row.find('td');
        var product_values = build_product_array(columns);
        my_ajax('add_product', product_values);
    });

    function build_product_array(columns){
        var product_values = {};
        $.each(columns, function (i, item) {
            if (typeof item.dataset.column !== 'undefined') {
                product_values[item.dataset.column] = item.innerHTML;
            }
        });
        return product_values;
    }

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
        var products = [];
        $('#search_table > tbody  > tr').each(function () {
            var columns = $(this).find('td');
            var product_values = build_product_array(columns);
            products.push(product_values);
        });
        my_ajax('add_all_products', products);
    });
})(jQuery);