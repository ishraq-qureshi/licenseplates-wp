<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if (!function_exists('chld_thm_cfg_locale_css')):
    function chld_thm_cfg_locale_css($uri)
    {
        if (empty($uri) && is_rtl() && file_exists(get_template_directory() . '/rtl.css'))
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter('locale_stylesheet_uri', 'chld_thm_cfg_locale_css');

if (!function_exists('chld_thm_cfg_parent_css')):
    function chld_thm_cfg_parent_css()
    {
        wp_enqueue_style('chld_thm_cfg_parent', trailingslashit(get_template_directory_uri()) . 'style.css', array());
    }
endif;
add_action('wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10);

if (!function_exists('child_theme_configurator_css')):
    function child_theme_configurator_css()
    {
        wp_enqueue_style('chld_thm_cfg_separate', trailingslashit(get_stylesheet_directory_uri()) . 'ctc-style.css', array('chld_thm_cfg_parent', 'styles', 'baguettebox-css'));
    }
endif;
add_action('wp_enqueue_scripts', 'child_theme_configurator_css', 10);


function defer_non_critical_js($tag, $handle)
{
    $exclude = array('jquery', 'jquery-core', 'jquery-migrate');
    if (!is_admin() && !in_array($handle, $exclude)) {
        return str_replace(' src', ' defer src', $tag);
    }
    return $tag;
}

function my_custom_scripts()
{
    wp_enqueue_script(
        'custom-js',
        get_stylesheet_directory_uri() . '/custom.js',
        array(), // no dependency
        null,
        true // load in footer 
    );
}
add_action('wp_enqueue_scripts', 'my_custom_scripts');

add_filter('script_loader_tag', 'defer_non_critical_js', 10, 2);



add_action( 'wp_footer', function() {
    if ( ! function_exists('is_checkout') || ! is_checkout() ) {
        return;
    }
    ?>
    <script>
    (function($){
        function uncheckPaymentMethods() {
            $('input[name="payment_method"]').prop('checked', false);
            $('.payment_box').hide();
            $('ul.payment_methods li').removeClass('woocommerce-PaymentMethod--checked');
        }
        $(function(){
            uncheckPaymentMethods();
            // Re-apply after AJAX updates (address change, shipping change, etc.)
            $(document.body).on('updated_checkout init_checkout', uncheckPaymentMethods);
        });
    })(jQuery);
    </script>
    <?php
});



// disabling google captcha on checkout page beacuse we already have turnstile captcha there
// add_action('wp_enqueue_scripts', function () {
//     if (is_checkout()) {
//         wp_dequeue_script('google-recaptcha');
//         wp_deregister_script('google-recaptcha');

//         wp_dequeue_script('wpcf7-recaptcha');
//         wp_deregister_script('wpcf7-recaptcha');
//     }
// }, 100);

// region code
add_filter( 'woocommerce_get_country_locale', 'require_state_sitewide' );
function require_state_sitewide( $locale ) {
    foreach ( $locale as $country_code => $fields ) {
        if ( isset( $fields['state'] ) ) {
            $locale[ $country_code ]['state']['required'] = true;
            $locale[ $country_code ]['state']['hidden']   = false;
        }
    }
    return $locale;
}




/**
 * WooCommerce - Saudi Arabia Short Address Custom fields
 *
 * Adds a "Short Address" Custom field to both billing and shipping,
 * shown only when the relevant country is Saudi Arabia (SA).
 * Handles the "Ship to a different address" checkbox correctly.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1. Register the two fields (billing + shipping)
 */
add_filter( 'woocommerce_billing_fields', 'ptech_add_sa_short_address_billing' );
function ptech_add_sa_short_address_billing( $fields ) {
    $fields['billing_short_address'] = array(
        'type'        => 'text',
        'label'       => __( 'Short Address', 'woocommerce' ),
        'placeholder' => __( 'e.g. RRRD2929', 'woocommerce' ),
        'required'    => false, // enforced conditionally, see validation below
        'class'       => array( 'form-row-wide', 'sa-short-address-field' ),
        'priority'    => 65,
    );
    return $fields;
}

add_filter( 'woocommerce_shipping_fields', 'ptech_add_sa_short_address_shipping' );
function ptech_add_sa_short_address_shipping( $fields ) {
    $fields['shipping_short_address'] = array(
        'type'        => 'text',
        'label'       => __( 'Short Address', 'woocommerce' ),
        'placeholder' => __( 'e.g. RRRD2929', 'woocommerce' ),
        'required'    => false,
        'class'       => array( 'form-row-wide', 'sa-short-address-field' ),
        'priority'    => 65,
    );
    return $fields;
}

/**
 * 2. Show/hide the fields based on country + "ship to different address" checkbox
 */
add_action( 'wp_enqueue_scripts', 'ptech_sa_checkout_js' );
function ptech_sa_checkout_js() {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) return;

    $js = "
    jQuery(function($){

        function toggleField(prefix){
            var country = $('#' + prefix + '_country').val();
            var field = $('#' + prefix + '_short_address_field');
            if (country === 'SA') {
                field.show();
            } else {
                field.hide();
                $('#' + prefix + '_short_address').val('');
            }
        }

        function maybeToggleShipping(){
            if ( $('#ship-to-different-address-checkbox').is(':checked') ) {
                toggleField('shipping');
            } else {
                $('#shipping_short_address_field').hide();
            }
        }

        // initial state
        toggleField('billing');
        maybeToggleShipping();

        // live updates
        $(document.body).on('change', '#billing_country', function(){ toggleField('billing'); });
        $(document.body).on('change', '#shipping_country', maybeToggleShipping);
        $(document.body).on('click', '#ship-to-different-address-checkbox', function(){
            setTimeout(maybeToggleShipping, 150);
        });
        $(document.body).on('updated_checkout', maybeToggleShipping);
    });
    ";
    wp_add_inline_script( 'wc-checkout', $js );
}

/**
 * 3. Conditional validation - required only when the relevant country is SA
 */
add_action( 'woocommerce_after_checkout_validation', 'ptech_validate_sa_short_address', 10, 2 );
function ptech_validate_sa_short_address( $data, $errors ) {

    if ( isset( $data['billing_country'] ) && $data['billing_country'] === 'SA'
         && empty( $_POST['billing_short_address'] ) ) {
        $errors->add( 'billing_short_address', __( 'Please enter your Short Address.', 'woocommerce' ) );
    }

    $ship_different = ! empty( $data['ship_to_different_address'] );
    if ( $ship_different && isset( $data['shipping_country'] ) && $data['shipping_country'] === 'SA'
         && empty( $_POST['shipping_short_address'] ) ) {
        $errors->add( 'shipping_short_address', __( 'Please enter the delivery Short Address.', 'woocommerce' ) );
    }
}

/**
 * 4. Save the values to order meta
 */
add_action( 'woocommerce_checkout_create_order', 'ptech_save_sa_short_address', 10, 2 );
function ptech_save_sa_short_address( $order, $data ) {
    if ( ! empty( $_POST['billing_short_address'] ) ) {
        $order->update_meta_data( '_billing_short_address', sanitize_text_field( wp_unslash( $_POST['billing_short_address'] ) ) );
    }
    if ( ! empty( $_POST['shipping_short_address'] ) ) {
        $order->update_meta_data( '_shipping_short_address', sanitize_text_field( wp_unslash( $_POST['shipping_short_address'] ) ) );
    }
}

/**
 * 5. Make it appear everywhere WooCommerce prints a formatted address:
 *    thank-you page, My Account order view, admin order edit screen,
 *    order emails, and most PDF invoice plugins.
 */
add_filter( 'woocommerce_order_formatted_billing_address', 'ptech_append_billing_short_address', 10, 2 );
function ptech_append_billing_short_address( $address, $order ) {
    $short = $order->get_meta( '_billing_short_address' );
    if ( $short && isset( $address['country'] ) && $address['country'] === 'SA' ) {
        $address['address_2'] = trim( ( $address['address_2'] ?? '' ) . "\nShort Address: " . $short );
    }
    return $address;
}

add_filter( 'woocommerce_order_formatted_shipping_address', 'ptech_append_shipping_short_address', 10, 2 );
function ptech_append_shipping_short_address( $address, $order ) {
    $short = $order->get_meta( '_shipping_short_address' );
    if ( $short && isset( $address['country'] ) && $address['country'] === 'SA' ) {
        $address['address_2'] = trim( ( $address['address_2'] ?? '' ) . "\nShort Address: " . $short );
    }
    return $address;
}

/**
 * 6. (Optional) Let admins view/edit the short address directly in the
 *    order edit screen's Billing/Shipping meta boxes, instead of just
 *    seeing it appended to the address block.
 */
add_filter( 'woocommerce_admin_billing_fields', 'ptech_admin_sa_short_address_billing' );
function ptech_admin_sa_short_address_billing( $fields ) {
    $fields['short_address'] = array(
        'label' => __( 'Short Address', 'woocommerce' ),
        'show'  => false, // already shown via the formatted address filter above
    );
    return $fields;
}

add_filter( 'woocommerce_admin_shipping_fields', 'ptech_admin_sa_short_address_shipping' );
function ptech_admin_sa_short_address_shipping( $fields ) {
    $fields['short_address'] = array(
        'label' => __( 'Short Address', 'woocommerce' ),
        'show'  => false,
    );
    return $fields;
}


