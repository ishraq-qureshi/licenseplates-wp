<?php
/**
 * Missing functions if WooCommerce is disabled.

 * @package Freesoul Deactivate Plugins
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

// Define woocommerce functions if other plugins don't check properly the existence of WooCommerce and use one of those functions.

if ( ! function_exists( 'get_woocommerce_price_format' ) ) {

	/**
	 * Get_woocommerce_price_format - Returns price format.
	 *
	 * @return string
	 */
	function get_woocommerce_price_format() {
		$currency_pos = get_option( 'woocommerce_currency_pos' );
		$format       = '%1$s%2$s';
		switch ( $currency_pos ) {
			case 'left':
				$format = '%1$s%2$s';
				break;
			case 'right':
				$format = '%2$s%1$s';
				break;
			case 'left_space':
				$format = '%1$s&nbsp;%2$s';
				break;
			case 'right_space':
				$format = '%2$s&nbsp;%1$s';
				break;
		}
		return apply_filters( 'woocommerce_price_format', $format, $currency_pos );
	}
}

if ( ! function_exists( 'is_shop' ) ) {
	/**
	 * Is_shop - Returns true when viewing the product type archive (shop).
	 *
	 * @return bool
	 */
	function is_shop() {
		return ( is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) );
	}
}

if ( ! function_exists( 'is_product_category' ) ) {

	/**
	 * Is_product_category - Returns true when viewing a product category.
	 *
	 * @param  string $term (default: '') The term slug your checking for. Leave blank to return true on any.
	 * @return bool
	 */
	function is_product_category( $term = '' ) {
		return is_tax( 'product_cat', $term );
	}
}

if ( ! function_exists( 'is_product' ) ) {

	/**
	 * Is_product - Returns true when viewing a single product.
	 *
	 * @return bool
	 */
	function is_product() {
		return is_singular( array( 'product' ) );
	}
}

if ( ! function_exists( 'is_checkout' ) ) {

	/**
	 * Is_checkout - Returns false. If WooCommerce isn't active, we return false.
	 *
	 * @return bool
	 */
	function is_checkout() {
		return false;
	}
}

if ( ! function_exists( 'is_cart' ) ) {

	/**
	 * Is_cart - Returns false. If WooCommerce isn't active, we return false.
	 *
	 * @return bool
	 */
	function is_cart() {
		return false;
	}
}

if ( ! function_exists( 'is_account_page' ) ) {

	/**
	 * Is_account_page - Returns false. If WooCommerce isn't active, we return false.
	 *
	 * @return bool
	 */
	function is_account_page() {
		return false;
	}
}

if ( ! function_exists( 'is_woocommerce' ) && apply_filters( 'fdp_is_woocommerce_function_exists', true ) ) {

	/**
	 * Is_woocommerce - Returns false. If WooCommerce isn't active, we return false.
	 *
	 * @return bool
	 */
	function is_woocommerce() {
		return false;
	}
}


if ( ! function_exists( 'wc_get_page_id' ) ) {
	/**
	 * Wc_get_page_id - Returns page ID.
	 *
	 * @param string $page Page.
	 * @return int
	 */
	function wc_get_page_id( $page ) {
		if ( 'pay' === $page || 'thanks' === $page ) {
			$page = 'checkout';
		}
		if ( 'change_password' === $page || 'edit_address' === $page || 'lost_password' === $page ) {
			$page = 'myaccount';
		}

		$page = apply_filters( 'woocommerce_get_' . $page . '_page_id', get_option( 'woocommerce_' . $page . '_page_id' ) );

		return $page ? absint( $page ) : -1;
	}
}
if ( ! function_exists( 'wc_get_page_permalink' ) ) {
	/**
	 * Wc_get_page_permalink - Returns page permalink.
	 *
	 * @param string $page Page.
	 * @param string $fallback Page fallback.
	 * @return string
	 */
	function wc_get_page_permalink( $page, $fallback = null ) {
		$page_id   = wc_get_page_id( $page );
		$permalink = 0 < $page_id ? get_permalink( $page_id ) : '';
		if ( ! $permalink ) {
			$permalink = is_null( $fallback ) ? get_home_url() : $fallback;
		}
		return apply_filters( 'woocommerce_get_' . $page . '_page_permalink', $permalink );
	}
}
if ( ! function_exists( 'wc_get_cart_url' ) ) {
	/**
	 * Wc_get_cart_url - Returns cart url.
	 *
	 * @return string
	 */
	function wc_get_cart_url() {
		return apply_filters( 'woocommerce_get_cart_url', wc_get_page_permalink( 'cart' ) );
	}
}
if ( ! function_exists( 'wc_get_checkout_url' ) ) {
	/**
	 * Wc_get_checkout_url - Returns checkout url.
	 *
	 * @return string
	 */
	function wc_get_checkout_url() {
		$checkout_url = wc_get_page_permalink( 'checkout' );
		if ( $checkout_url ) {
			// Force SSL if needed.
			if ( is_ssl() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
				$checkout_url = str_replace( 'http:', 'https:', $checkout_url );
			}
		}

		return apply_filters( 'woocommerce_get_checkout_url', $checkout_url );
	}
}

if ( ! function_exists( 'get_woocommerce_currency' ) ) {
	/**
	 * Get_woocommerce_currency - Returns currency.
	 *
	 * @return string
	 */
	function get_woocommerce_currency() {
		return apply_filters( 'woocommerce_currency', get_option( 'woocommerce_currency' ) );
	}
}

if ( ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
	/**
	 * Get_woocommerce_currency_symbol - Returns currency symbol.
	 *
	 * @param string $currency Currency.
	 * @return string
	 */
	function get_woocommerce_currency_symbol( $currency = '' ) {
		if ( ! $currency ) {
			$currency = get_woocommerce_currency();
		}
		$symbols         = get_woocommerce_currency_symbols();
		$currency_symbol = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '';
		return apply_filters( 'woocommerce_currency_symbol', $currency_symbol, $currency );
	}
}

if ( ! function_exists( 'get_woocommerce_currency_symbols' ) ) {
	/**
	 * Get_woocommerce_currency_symbols - Returns currency symbols.
	 *
	 * @return array
	 */
	function get_woocommerce_currency_symbols() {
		$symbols = apply_filters(
			'woocommerce_currency_symbols',
			array(
				'AED' => '&#x62f;.&#x625;',
				'AFN' => '&#x60b;',
				'ALL' => 'L',
				'AMD' => 'AMD',
				'ANG' => '&fnof;',
				'AOA' => 'Kz',
				'ARS' => '&#36;',
				'AUD' => '&#36;',
				'AWG' => 'Afl.',
				'AZN' => 'AZN',
				'BAM' => 'KM',
				'BBD' => '&#36;',
				'BDT' => '&#2547;&nbsp;',
				'BGN' => '&#1083;&#1074;.',
				'BHD' => '.&#x62f;.&#x628;',
				'BIF' => 'Fr',
				'BMD' => '&#36;',
				'BND' => '&#36;',
				'BOB' => 'Bs.',
				'BRL' => '&#82;&#36;',
				'BSD' => '&#36;',
				'BTC' => '&#3647;',
				'BTN' => 'Nu.',
				'BWP' => 'P',
				'BYR' => 'Br',
				'BYN' => 'Br',
				'BZD' => '&#36;',
				'CAD' => '&#36;',
				'CDF' => 'Fr',
				'CHF' => '&#67;&#72;&#70;',
				'CLP' => '&#36;',
				'CNY' => '&yen;',
				'COP' => '&#36;',
				'CRC' => '&#x20a1;',
				'CUC' => '&#36;',
				'CUP' => '&#36;',
				'CVE' => '&#36;',
				'CZK' => '&#75;&#269;',
				'DJF' => 'Fr',
				'DKK' => 'DKK',
				'DOP' => 'RD&#36;',
				'DZD' => '&#x62f;.&#x62c;',
				'EGP' => 'EGP',
				'ERN' => 'Nfk',
				'ETB' => 'Br',
				'EUR' => '&euro;',
				'FJD' => '&#36;',
				'FKP' => '&pound;',
				'GBP' => '&pound;',
				'GEL' => '&#x20be;',
				'GGP' => '&pound;',
				'GHS' => '&#x20b5;',
				'GIP' => '&pound;',
				'GMD' => 'D',
				'GNF' => 'Fr',
				'GTQ' => 'Q',
				'GYD' => '&#36;',
				'HKD' => '&#36;',
				'HNL' => 'L',
				'HRK' => 'kn',
				'HTG' => 'G',
				'HUF' => '&#70;&#116;',
				'IDR' => 'Rp',
				'ILS' => '&#8362;',
				'IMP' => '&pound;',
				'INR' => '&#8377;',
				'IQD' => '&#x639;.&#x62f;',
				'IRR' => '&#xfdfc;',
				'IRT' => '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;',
				'ISK' => 'kr.',
				'JEP' => '&pound;',
				'JMD' => '&#36;',
				'JOD' => '&#x62f;.&#x627;',
				'JPY' => '&yen;',
				'KES' => 'KSh',
				'KGS' => '&#x441;&#x43e;&#x43c;',
				'KHR' => '&#x17db;',
				'KMF' => 'Fr',
				'KPW' => '&#x20a9;',
				'KRW' => '&#8361;',
				'KWD' => '&#x62f;.&#x643;',
				'KYD' => '&#36;',
				'KZT' => '&#8376;',
				'LAK' => '&#8365;',
				'LBP' => '&#x644;.&#x644;',
				'LKR' => '&#xdbb;&#xdd4;',
				'LRD' => '&#36;',
				'LSL' => 'L',
				'LYD' => '&#x644;.&#x62f;',
				'MAD' => '&#x62f;.&#x645;.',
				'MDL' => 'MDL',
				'MGA' => 'Ar',
				'MKD' => '&#x434;&#x435;&#x43d;',
				'MMK' => 'Ks',
				'MNT' => '&#x20ae;',
				'MOP' => 'P',
				'MRU' => 'UM',
				'MUR' => '&#x20a8;',
				'MVR' => '.&#x783;',
				'MWK' => 'MK',
				'MXN' => '&#36;',
				'MYR' => '&#82;&#77;',
				'MZN' => 'MT',
				'NAD' => 'N&#36;',
				'NGN' => '&#8358;',
				'NIO' => 'C&#36;',
				'NOK' => '&#107;&#114;',
				'NPR' => '&#8360;',
				'NZD' => '&#36;',
				'OMR' => '&#x631;.&#x639;.',
				'PAB' => 'B/.',
				'PEN' => 'S/',
				'PGK' => 'K',
				'PHP' => '&#8369;',
				'PKR' => '&#8360;',
				'PLN' => '&#122;&#322;',
				'PRB' => '&#x440;.',
				'PYG' => '&#8370;',
				'QAR' => '&#x631;.&#x642;',
				'RMB' => '&yen;',
				'RON' => 'lei',
				'RSD' => '&#1088;&#1089;&#1076;',
				'RUB' => '&#8381;',
				'RWF' => 'Fr',
				'SAR' => '&#x631;.&#x633;',
				'SBD' => '&#36;',
				'SCR' => '&#x20a8;',
				'SDG' => '&#x62c;.&#x633;.',
				'SEK' => '&#107;&#114;',
				'SGD' => '&#36;',
				'SHP' => '&pound;',
				'SLL' => 'Le',
				'SOS' => 'Sh',
				'SRD' => '&#36;',
				'SSP' => '&pound;',
				'STN' => 'Db',
				'SYP' => '&#x644;.&#x633;',
				'SZL' => 'L',
				'THB' => '&#3647;',
				'TJS' => '&#x405;&#x41c;',
				'TMT' => 'm',
				'TND' => '&#x62f;.&#x62a;',
				'TOP' => 'T&#36;',
				'TRY' => '&#8378;',
				'TTD' => '&#36;',
				'TWD' => '&#78;&#84;&#36;',
				'TZS' => 'Sh',
				'UAH' => '&#8372;',
				'UGX' => 'UGX',
				'USD' => '&#36;',
				'UYU' => '&#36;',
				'UZS' => 'UZS',
				'VEF' => 'Bs F',
				'VES' => 'Bs.S',
				'VND' => '&#8363;',
				'VUV' => 'Vt',
				'WST' => 'T',
				'XAF' => 'CFA',
				'XCD' => '&#36;',
				'XOF' => 'CFA',
				'XPF' => 'Fr',
				'YER' => '&#xfdfc;',
				'ZAR' => '&#82;',
				'ZMW' => 'ZK',
			)
		);
		return $symbols;
	}
}

/**
 * Enhanced Shortcode to display a "Light" Mini-Cart with robust session detection.
 * Includes fallback for session keys and better unserialization handling.
 */
add_shortcode('fdp_cart_emulator', 'fdp_emulate_woocommerce_mini_cart');

function fdp_emulate_woocommerce_mini_cart() {
    global $wpdb;
    // 1. Find the WooCommerce session cookie
    $session_cookie = '';
    foreach ( $_COOKIE as $key => $value ) {
        if ( strpos( $key, 'wp_woocommerce_session_' ) === 0 ) {
            $session_cookie = $value;
            break;
        }
    }
    $cart_items_html = '';
    $total_count = 0;
    $total_price = 0;
    if ( ! empty( $session_cookie ) ) {
        // Break the cookie: [0] is the customer_id/hash, [1] is expiration
        $cookie_parts = explode( '|', $session_cookie );
        $customer_id  = $cookie_parts[0];
        $table_name = $wpdb->prefix . 'woocommerce_sessions';
        // 2. Fetch the session. We try to match the customer_id
        $session_raw = $wpdb->get_var( $wpdb->prepare( 
            "SELECT session_value FROM $table_name WHERE session_key = %s", 
            $customer_id 
        ) );
        if ( $session_raw ) {
            // WooCommerce stores data in a double-serialized or specially encoded format
            $session_data = maybe_unserialize( $session_raw );
            // Critical check: Ensure 'cart' exists and is not empty
            if ( isset( $session_data['cart'] ) && ! empty( $session_data['cart'] ) ) {
                $cart_array = maybe_unserialize( $session_data['cart'] );

                if ( is_array( $cart_array ) ) {
                    foreach ( $cart_array as $item ) {
                        $product_id = isset($item['product_id']) ? $item['product_id'] : 0;
                        $qty        = isset($item['quantity']) ? $item['quantity'] : 0;

                        if ( $product_id > 0 ) {
                            $product_title = get_the_title($product_id);
                            // Get price (handling both simple and variable products)
                            $price = get_post_meta($product_id, '_price', true);
                            
                            $total_count += $qty;
                            $total_price += ($price * $qty);

                            $cart_items_html .= '<li style="display: flex; justify-content: space-between; gap: 20px; padding: 8px 0; border-bottom: 1px solid #eee;">';
                            $cart_items_html .= '<span style="font-size:13px;">' . esc_html($product_title) . ' <strong>x' . $qty . '</strong></span>';
                            $cart_items_html .= '<span style="font-size:13px; font-weight:bold;">' . number_format($price * $qty, 2) . '€</span>';
                            $cart_items_html .= '</li>';
                        }
                    }
                }
            }
        }
    }
	// Fetch URLs for Cart and Checkout pages from wp_options
	$cart_url     = get_permalink(get_option('woocommerce_cart_page_id'));
	$checkout_url = get_permalink(get_option('woocommerce_checkout_page_id'));
    // 3. UI and Logic for the dropdown
    ob_start(); ?>
    <style>
        .fdp-mini-cart { position: relative; display: inline-block; font-family: sans-serif; }
        .fdp-cart-link { text-decoration: none; color: inherit; display: flex; align-items: center; gap: 5px; }
        .fdp-cart-dropdown { 
            display: none; position: absolute; top: 100%; right: 0; background: #fff; 
            min-width: 280px; border: 1px solid #ddd; padding: 15px; z-index: 9999;
            box-shadow: 0 10px 20px rgba(0,0,0,0.15); border-radius: 4px;
        }
        .fdp-mini-cart:hover .fdp-cart-dropdown { display: block; }
        .fdp-cart-dropdown ul { list-style: none; margin: 0; padding: 0; max-height: 300px; overflow-y: auto; }
        .fdp-total { font-weight: bold; margin-top: 15px; padding-top: 10px; border-top: 2px solid #333; text-align: right; }
        .fdp-btn-cart { display: block; text-align: center; margin-top: 15px; background: #222; color: #fff; padding: 10px; text-decoration: none; font-size: 14px; border-radius: 3px; }
        .fdp-btn-cart:hover { background: #000; }
        .badge { background: #e21; color: #fff; border-radius: 50%; padding: 2px 6px; font-size: 11px; }
    </style>
    <div class="fdp-mini-cart">
        <a href="<?php echo get_permalink(get_option('woocommerce_cart_page_id')); ?>" class="fdp-cart-link">
            <span style="font-size: 20px;">🛒</span>
            <?php if ( $total_count > 0 ) : ?>
                <span class="badge"><?php echo $total_count; ?></span>
            <?php endif; ?>
        </a>
        
        <div class="fdp-cart-dropdown">
            <?php if ( $total_count > 0 ) : ?>
                <ul><?php echo $cart_items_html; ?></ul>
                <div class="fdp-total">Total: <?php echo number_format($total_price, 2); ?> €</div>
				<div class="fdp-buttons-container">
                    <a href="<?php echo esc_url($cart_url); ?>" class="fdp-btn button btn fdp-btn-view-cart">View Cart</a>
                    <a href="<?php echo esc_url($checkout_url); ?>" class="fdp-btn button btnfdp-btn-checkout">Checkout</a>
                </div>
            <?php else : ?>
                <p style="text-align:center; color:#888;">Your cart is empty.</p>
            <?php endif; ?>
        </div>
    </div>
	<script type="text/javascript">
	document.addEventListener('DOMContentLoaded', function() {
		const container = document.getElementById('fdp-mini-cart-container');
		if (!container) return;
		const refreshFdpCart = () => {
			const formData = new FormData();
			formData.append('action', 'fdp_woo_ajax_update_mini_cart');

			fetch(window.location.origin + '/wp-admin/admin-ajax.php', {
				method: 'POST',
				body: formData
			})
			.then(response => response.text())
			.then(html => {
				container.innerHTML = html;
				// Dispatch a native vanilla event in case other scripts need it
				document.dispatchEvent(new CustomEvent('fdp_cart_updated'));
			})
			.catch(err => console.warn('FDP: Update failed', err));
		};
		const send = XMLHttpRequest.prototype.send;
		XMLHttpRequest.prototype.send = function() {
			this.addEventListener('load', function() {
				if (this.responseURL && (
					this.responseURL.includes('add_to_cart') || 
					this.responseURL.includes('get_refreshed_fragments')
				)) {
					setTimeout(refreshFdpCart, 100);
				}
			});
			return send.apply(this, arguments);
		};
		document.addEventListener('click', function(e) {
			if (e.target.classList.contains('add_to_cart_button')) {
				setTimeout(refreshFdpCart, 800);
			}
		}, true);
	});
	</script>
    <?php
	$output = '<div id="fdp-mini-cart-container">' . ob_get_clean() . '</div>';
	return $output;
}

// Hook for logged-in and guest users
add_action('wp_ajax_fdp_woo_ajax_update_mini_cart', 'fdp_ajax_update_mini_cart');
add_action('wp_ajax_nopriv_fdp_woo_ajax_update_mini_cart', 'fdp_ajax_update_mini_cart');

/*
* AJAX Update Mini Cart
*/
function fdp_ajax_update_mini_cart() {
    echo fdp_emulate_woocommerce_mini_cart();
    wp_die();
}