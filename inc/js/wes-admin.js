(function ($) {
    'use strict';
    function my_ajax(action, values = '', html = false) {
        $(".spinner").addClass("is-active");
        $.post(ajaxurl, {
            security: settings.nonce,
            action: action,
            data: values
        }).done(function (res) {
            if (html) {
                $('#admin_message').append(res).fadeIn(1000);
            } else {
                $('#admin_message').text(res).fadeIn(1000).delay(5000).fadeOut(1000);
            }

        }).fail(function () {
            console.log('AJAX failed!');
        }).always(function () {
            console.log('AJAX called.');
            $(".spinner").removeClass("is-active");
        });
    }

    $('div#wpbody-content').on('click', 'button', function () {
        var row = $(this).closest('tr');
        var columns = row.find('td');
        var product_values = build_product_array(columns);
        my_ajax('add_product', product_values);
    });

    function build_product_array(columns) {
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

    $('#get_date').on('click', function () {
        var date = new Date($('#log_date').val());
        var fdate = format_date(date);
        go_to_log(fdate);
    });

})(jQuery);

function view_selected_log() {
    var d = document.getElementById("select_logs").value;
    go_to_log(d);
}

function go_to_log(date) {
    if (date != '') {
        window.location.href = 'admin.php?page=wesLogs&log=' + date;
    } else {
        alert('please select date');
    }
}

function format_date(date) {
    var fdate = '';
    var curr_date = date.getDate();
    var curr_month = date.getMonth() + 1;
    var curr_year = date.getFullYear();
    fdate = curr_date + "-" + curr_month + "-" + curr_year;
    return fdate;
}