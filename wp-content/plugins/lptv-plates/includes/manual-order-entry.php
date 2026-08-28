<?php

if (!defined('ABSPATH')) exit;

define('LPTV_CUSTOM_PLATE_SKU', 'custom-plate-manual-entry');
define('LPTV_MANUAL_ORDER_ENTRY_SLUG', 'manual-order-entry');
define('LPTV_MANUAL_ORDER_ENTRY_NONCE', 'lptv_manual_order_entry');

/**
 * Resolve the hidden "Custom Plate" product used for every non-catalog line item.
 */
function lptv_get_custom_plate_product_id()
{
    static $product_id = null;
    if ($product_id === null) {
        $product_id = (int) wc_get_product_id_by_sku(LPTV_CUSTOM_PLATE_SKU);
    }
    return $product_id;
}

// Serve the Manual Order Entry page template for the manual-order-entry page, admin-only
add_filter('template_include', function ($template) {
    if (!is_page(LPTV_MANUAL_ORDER_ENTRY_SLUG)) {
        return $template;
    }

    if (!current_user_can('manage_woocommerce')) {
        wp_safe_redirect(home_url('/'));
        exit;
    }

    return plugin_dir_path(__FILE__) . '../templates/manual-order-entry-page.php';
});

// Add the admin-only "Manual Order Entry" link to the header nav
add_filter('wp_nav_menu_objects', function ($items, $args) {
    if ($args->theme_location !== 'menu-3' || !current_user_can('manage_woocommerce')) {
        return $items;
    }

    $item = new stdClass();
    $item->ID = 'lptv-manual-order-entry';
    $item->db_id = 0;
    $item->title = 'Manual Order Entry';
    $item->url = home_url('/' . LPTV_MANUAL_ORDER_ENTRY_SLUG . '/');
    $item->menu_item_parent = 0;
    $item->object = 'custom';
    $item->object_id = 0;
    $item->type = 'custom';
    $item->type_label = 'Custom Link';
    $item->target = '';
    $item->attr_title = '';
    $item->description = '';
    $item->xfn = '';
    $item->classes = array('menu-item', 'menu-manual-order-entry');
    $item->current = is_page(LPTV_MANUAL_ORDER_ENTRY_SLUG);

    $items[] = $item;

    return $items;
}, 10, 2);

// AJAX handler: add a non-catalog "Custom Plate" line item to the cart
add_action('wp_ajax_lptv_add_custom_plate', function () {
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(array('message' => 'Not allowed'));
    }

    check_ajax_referer(LPTV_MANUAL_ORDER_ENTRY_NONCE, 'security');

    $description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';
    $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
    $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;

    if ($description === '' || $price <= 0 || $quantity < 1) {
        wp_send_json_error(array('message' => 'Please enter a description, a price greater than 0, and a quantity.'));
    }

    $product_id = lptv_get_custom_plate_product_id();
    if (!$product_id) {
        wp_send_json_error(array('message' => 'Custom Plate product is not configured.'));
    }

    $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, 0, array(), array(
        'custom_plate_description' => $description,
        'custom_plate_price' => $price,
    ));

    if (!$cart_item_key) {
        wp_send_json_error(array('message' => 'Could not add item to cart.'));
    }

    do_action('woocommerce_ajax_added_to_cart', $product_id);

    // is_admin() is true for any admin-ajax.php request, which wrongly triggers the
    // legacy generateLPTVplateThumbnail() admin branch (lptv-plates.php) and fatals
    // on any cart item since it calls a method on the cart item key string. Unhook
    // it only around this fragment render.
    remove_filter('woocommerce_cart_item_thumbnail', 'generateLPTVplateThumbnail', 20);
    WC_AJAX::get_refreshed_fragments();
    add_filter('woocommerce_cart_item_thumbnail', 'generateLPTVplateThumbnail', 20, 3);
});

// Re-apply the admin-entered price whenever the cart is (re)loaded from session.
// WooCommerce only re-runs woocommerce_before_calculate_totals when the cart actually
// changes - on a plain page load it restores cached totals and reloads each product
// fresh from the DB (price $0 for the hidden "Custom Plate" product), which is why the
// per-item price reverted to $0.00 while the cached subtotal stayed correct.
add_filter('woocommerce_get_cart_item_from_session', function ($session_data, $values) {
    if (isset($values['custom_plate_price']) && isset($session_data['data']) && $session_data['data'] instanceof WC_Product) {
        $session_data['data']->set_price($values['custom_plate_price']);
    }
    return $session_data;
}, 10, 2);

// Apply the admin-entered price to custom plate cart items added within the current request
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['custom_plate_price'])) {
            $cart_item['data']->set_price($cart_item['custom_plate_price']);
        }
    }
});

// Show the admin-entered description under the item name in cart/checkout review
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    if (!empty($cart_item['custom_plate_description'])) {
        $item_data[] = array(
            'name' => 'Description',
            'value' => wc_clean($cart_item['custom_plate_description']),
        );
    }
    return $item_data;
}, 10, 2);

// Persist the admin-entered description onto the order line item
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
    if (!empty($values['custom_plate_description'])) {
        $item->add_meta_data('Description', $values['custom_plate_description']);
    }
}, 10, 4);
