<?php

if (!defined('ABSPATH')) exit;

get_header();
?>

<div class="lptv-manual-order-entry-page" style="max-width:700px;margin:40px auto;padding:0 20px;">
    <h1>Manual Order Entry</h1>

    <div id="lptv-moe-message" style="display:none;margin-bottom:20px;padding:12px 15px;border-radius:4px;"></div>

    <div class="lptv-moe-field">
        <label for="lptv_moe_description"><strong>Description:</strong></label><br>
        <textarea id="lptv_moe_description" rows="5" style="width:100%;margin-top:8px;"></textarea>
    </div>

    <div class="lptv-moe-field" style="margin-top:20px;display:flex;align-items:flex-end;gap:30px;flex-wrap:wrap;">
        <div>
            <label for="lptv_moe_price"><strong>Enter Cost/Unit:</strong></label><br>
            <div style="display:flex;align-items:center;margin-top:8px;">
                <span style="margin-right:5px;">$</span>
                <input type="number" id="lptv_moe_price" min="0" step="0.01" value="0.00" style="width:120px;">
            </div>
        </div>

        <div>
            <label><strong>Quantity:</strong></label><br>
            <div style="display:flex;align-items:center;margin-top:8px;">
                <button type="button" id="lptv_moe_qty_minus" class="button">&minus;</button>
                <input type="number" id="lptv_moe_qty" min="1" step="1" value="1" style="width:60px;text-align:center;margin:0 5px;">
                <button type="button" id="lptv_moe_qty_plus" class="button">+</button>
            </div>
        </div>

        <div style="margin-left:auto;">
            <button type="button" id="lptv_moe_add_to_cart" class="button alt">Add to Cart</button>
        </div>
    </div>
</div>

<script>
jQuery(function ($) {
    $('#lptv_moe_qty_minus').on('click', function () {
        var input = $('#lptv_moe_qty');
        input.val(Math.max(1, parseInt(input.val(), 10) - 1));
    });
    $('#lptv_moe_qty_plus').on('click', function () {
        var input = $('#lptv_moe_qty');
        input.val(parseInt(input.val(), 10) + 1);
    });

    $('#lptv_moe_add_to_cart').on('click', function () {
        var $button = $(this);
        var $message = $('#lptv-moe-message');

        $message.hide();
        $button.prop('disabled', true).addClass('loading');

        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
            action: 'lptv_add_custom_plate',
            security: '<?php echo esc_js(wp_create_nonce(LPTV_MANUAL_ORDER_ENTRY_NONCE)); ?>',
            description: $('#lptv_moe_description').val(),
            price: $('#lptv_moe_price').val(),
            quantity: $('#lptv_moe_qty').val()
        }, function (response) {
            $button.prop('disabled', false).removeClass('loading');

            if (response && response.success === false) {
                var msg = (response.data && response.data.message) ? response.data.message : 'Could not add item to cart.';
                $message
                    .css({ background: '#f8d7da', color: '#721c24', border: '1px solid #f5c6cb' })
                    .text(msg)
                    .show();
                return;
            }

            if (!response || !response.fragments) {
                $message
                    .css({ background: '#f8d7da', color: '#721c24', border: '1px solid #f5c6cb' })
                    .text('Could not add item to cart.')
                    .show();
                return;
            }

            $message
                .css({ background: '#d4edda', color: '#155724', border: '1px solid #c3e6cb' })
                .text('Item added to cart.')
                .show();

            $('#lptv_moe_description').val('');
            $('#lptv_moe_price').val('0.00');
            $('#lptv_moe_qty').val('1');

            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

            // Open the mini-cart flyout, same class the header's cart icon click handler toggles
            $('.mini-cart').closest('.header').addClass('mini-cart-active');
        });
    });
});
</script>

<?php
get_footer();
