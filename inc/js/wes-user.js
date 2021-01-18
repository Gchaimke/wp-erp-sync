(function ($) {

    if ($('ul').hasClass('woocommerce-error')) {
        $('.wc-proceed-to-checkout').hide();
        $('.shop_table_responsive').append('<h6 class="woocommerce-error">מינימום הזמנה 100ש"ח</h6>')
    }

})(jQuery);
