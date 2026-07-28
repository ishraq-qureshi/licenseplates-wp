<?php
/**
 * All hooks here
 */


use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

if ( ! class_exists( 'WC_AF_Hooks' ) ) {
	class WC_AF_Hooks {

		protected static $_instance = null;

		public function __construct() {

			add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'verify_captcha_on_checkout' ), 5 );
			add_action('woocommerce_store_api_before_order_created', array( $this, 'google_captcha_verify_on_checkout' ), 10, 2 ); 

			add_filter( 'woocommerce_rest_pre_insert_shop_order_object', array( $this, 'block_unknown_origin_orders_rest_api' ), 10, 3 );

			add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'block_unknown_origin_orders' ), 10, 2);

			add_action( 'woocommerce_checkout_process', [ $this, 'block_unknown_origin_classic_checkout' ] );
		
		// ✅ SECURITY FIX: Block Store API when "Block Unknown Origin" is enabled
		// This prevents bot attacks via /wp-json/wc/store/v1/checkout endpoint
		// ✅ FIXED: Changed default from 'no' to match settings default (should be off by default)
			if ( get_option( 'wc_af_block_unknown_origin', 'no' ) === 'yes' ) {
				add_filter( 'rest_pre_dispatch', array( $this, 'block_store_api_checkout' ), 10, 3 );
			}
		}


	/**
	 * Block WooCommerce Store API v2 checkout endpoint when "Block Unknown Origin" is enabled
	 * ✅ SECURITY FIX: Prevents bot attacks via /wp-json/wc/store/v1/checkout
	 * ✅ IMPROVED: Now allows legitimate Store API requests from site's own checkout
	 * 
	 * @param mixed $result Response to replace the requested version with
	 * @param WP_REST_Server $server Server instance
	 * @param WP_REST_Request $request Request used to generate the response
	 * @return mixed
	 * @since 7.1.9
	 */
		public function block_store_api_checkout( $result, $server, $request ) {
			$route = $request->get_route();

			// Only process Store API checkout endpoints (route match or WC helper).
			$is_store_checkout_route = (
				strpos( $route, '/wc/store/v1/checkout' ) !== false
				|| strpos( $route, '/wc/store/checkout' ) !== false
			);
			if ( ! $is_store_checkout_route && ! ( function_exists( 'wc_is_store_api_request' ) && wc_is_store_api_request() ) ) {
				return $result;
			}
	
	// Standard security pattern: verify each allowlist condition for FAILURE,
	// then block only when every check fails.  Falling through to `return $result`
	// means the request is legitimate.

	// 1. WooCommerce Store API nonce — injected into every Blocks checkout page
	//    and sent as the "Nonce" request header.  A valid nonce proves the request
	//    came from a real browser session on this site.
	//    HTTP_REFERER is deliberately NOT checked — it is a client-controlled
	//    header that is trivially spoofed.
	$nonce     = $request->get_header( 'Nonce' );
	$nonce_bad = empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wc_store_api' );

	// 2. IP whitelist — site-operator-configured trusted IPs.
	$user_ip           = WC_Geolocation::get_ip_address();
	$ip_allowed        = false;
	$whitelist_ips_opt = get_option( 'wc_settings_anti_fraud_ips_whitelist', '' );
			if ( ! empty( $whitelist_ips_opt ) ) {
				$whitelist_ips        = array_map( 'trim', explode( ',', $whitelist_ips_opt ) );
				$normalized_user_ip   = $this->normalize_ip( $user_ip );
				$normalized_whitelist = array_map( array( $this, 'normalize_ip' ), $whitelist_ips );
				$ip_allowed           = in_array( $normalized_user_ip, $normalized_whitelist, true );
			}

	// Block only when the nonce is bad AND neither bypass condition is met.
			if ( $nonce_bad && ! $ip_allowed && ! is_user_logged_in() ) {
				// phpcs:ignore QITStandard.PHP.DebugCode.DebugFunctionFound -- Legitimate security logging.
				error_log( sprintf(
					'WC Anti-Fraud: Blocked Store API checkout - no valid nonce from IP %s (Route: %s)',
					$user_ip,
					$route
				) );

				return new WP_Error(
					'woocommerce_store_api_checkout_blocked',
					__( 'For security reasons, orders must be placed through the standard checkout page. The API checkout endpoint is currently disabled.', 'woocommerce-anti-fraud' ),
					array( 'status' => 403 )
				);
			}

	return $result;
		}

		public function block_unknown_origin_orders_rest_api( WC_Order $order, $request, $creating ) {

			// ✅ FIXED: Changed default from 'yes' to 'no' to match settings definition and prevent false positives
			if ( $creating && get_option( 'wc_af_block_unknown_origin', 'no' ) == 'yes' ) {
		
				// ✅ SECURITY FIX: Check IP whitelist FIRST to allow legitimate API orders
				$user_ip = WC_Geolocation::get_ip_address();
				$whitelist_ips_opt = get_option( 'wc_settings_anti_fraud_ips_whitelist', '' );
		
				if ( ! empty( $whitelist_ips_opt ) ) {
					$whitelist_ips = array_map( 'trim', explode( ',', $whitelist_ips_opt ) );
					$normalized_user_ip = $this->normalize_ip( $user_ip );
					$normalized_whitelist = array_map( array( $this, 'normalize_ip' ), $whitelist_ips );
			
					if ( in_array( $normalized_user_ip, $normalized_whitelist, true ) ) {
						$order->add_order_note( __( 'Order fraud checks skipped due to whitelisted IP address.', 'woocommerce-anti-fraud' ) );
						return $order;
					}
				}

				// ✅ MARKETPLACE FIX: Skip unknown origin blocking for recognised marketplace imports
				if ( WC_AF_Marketplace_Detector::is_enabled() ) {
					$marketplace_source = WC_AF_Marketplace_Detector::detect_source( $order );
					if ( WC_AF_Marketplace_Detector::should_apply_marketplace_trust( $order, $marketplace_source ) ) {
						$profile = WC_AF_Marketplace_Detector::get_effective_profile( $marketplace_source );
						if ( $profile && ! empty( $profile['ignore_unknown_origin'] ) ) {
							$order->add_order_note(
							sprintf(
								/* translators: %s: marketplace name (e.g. eBay) */
								__( 'Unknown origin check skipped — order imported from %s.', 'woocommerce-anti-fraud' ),
								WC_AF_Marketplace_Detector::get_source_label( $marketplace_source )
							)
							);
							return $order;
						}
					}
				}

				$created_via = $request->get_param( 'created_via' );

				$auth_header = $request->get_header( 'Authorization' );

				$current_key = '';

				if ( $auth_header && strpos( $auth_header, 'Basic ' ) === 0 ) {
					$decoded = base64_decode( str_replace( 'Basic ', '', $auth_header ) );
					list( $key, $secret ) = explode( ':', $decoded );
					$current_key = $key;
				}

				$enable_whitelist = get_option( 'wc_af_enable_api_keys_whitelist', 'no' );
				$whitelisted_keys = get_option( 'wc_settings_anti_fraud_whitelist_restapi', '' );
				// Also handle Basic Auth headers (for Postman or REST API)
				if (empty($current_key) && isset($_SERVER['PHP_AUTH_USER'])) {
					$current_key = sanitize_text_field($_SERVER['PHP_AUTH_USER']);
				}

				$key_end = substr($current_key, -7);
				// Check if whitelisting is enabled and key is in whitelist
				if ( 'yes' === $enable_whitelist && ! empty( $current_key ) && in_array( $key_end, $whitelisted_keys, true ) ) {
					// Add note for admin clarity
					$order->add_order_note( __( 'Order fraud checks skipped due to whitelisted API key.', 'woocommerce-anti-fraud' ) );

					return $order;
				}

				if ( empty( $created_via ) || 'unknown' === $created_via || empty( $order->get_meta( '_wc_order_attribution_utm_source' ) ) ) {
					return new WP_Error(
					'woocommerce_api_unknown_origin_blocked',
					__( 'Orders with unknown origin cannot be created via the REST API.', 'woocommerce' ),
					array( 'status' => 403 )
					);
				}
			}

			return $order;
		}


		public function block_unknown_origin_orders( $order, $request ) {

			// ✅ FIXED: Changed default from 'yes' to 'no' to match settings definition
			if ( is_admin() || get_option( 'wc_af_block_unknown_origin', 'no' ) !== 'yes' ) {
				return true;
			}

			// ✅ FIXED: Check whitelist BEFORE blocking - Critical fix for false positives
			if ( $this->is_customer_whitelisted( $order ) ) {
				return true; // Allow whitelisted customers immediately
			}

			// ✅ MARKETPLACE FIX: Skip blocking for marketplace-imported orders
			if ( $order instanceof \WC_Order && WC_AF_Marketplace_Detector::is_enabled() ) {
				$marketplace_source = WC_AF_Marketplace_Detector::detect_source( $order );
				if ( WC_AF_Marketplace_Detector::should_apply_marketplace_trust( $order, $marketplace_source ) ) {
					$profile = WC_AF_Marketplace_Detector::get_effective_profile( $marketplace_source );
					if ( $profile && ! empty( $profile['ignore_unknown_origin'] ) ) {
						return true;
					}
				}
			}

		// Security: HTTP_REFERER is client-controlled and must not be used for
		// security-sensitive decisions.  Use server-set created_via and/or a valid
		// WooCommerce Store API nonce (via get_trusted_request_source) instead.
		$created_via     = $order instanceof \WC_Order ? $order->get_created_via() : 'unknown';
		$trusted_source  = self::get_trusted_request_source( $request );
		$allowed_origins = array(
			'checkout',          // Classic shortcode checkout (legacy)
			'checkout-block',    // WooCommerce Blocks checkout
			'store-api',         // Store API (used by checkout block + headless frontends)
			'rest-api',          // REST API requests
			'admin',             // Orders created manually in wp-admin
			'pos',               // Point of Sale integrations
			'woocommerce-pos',   // WooCommerce POS plugin
			'checkout-draft',    // Draft orders (some extensions use this)
			'subscription',      // Woo Subscriptions renewal orders
		);

		// Block only when neither created_via nor the request context is trusted.
			if ( ! in_array( $created_via, $allowed_origins, true ) && 'store_api' !== $trusted_source ) {
				$error_message = 'Blocked suspicious checkout request with unknown origin.';
				throw new \WC_REST_Exception( 'woocommercse_origin_unknown', $error_message, 400 );
			}
		}

	/**
	 * Determine the request source using only server-side, non-spoofable signals.
	 *
	 * This is the single canonical place in the plugin where request origin is
	 * evaluated.  HTTP_REFERER is deliberately excluded because it is a
	 * client-controlled header that can be trivially forged.
	 *
	 * Priority order:
	 *  1. WooCommerce REST API constant  → 'rest_api'
	 *  2. Valid WooCommerce Store API nonce (Nonce header) → 'store_api'
	 *  3. WOOCOMMERCE_CHECKOUT constant (classic checkout) → 'checkout'
	 *  4. WP Admin context → 'admin'
	 *  5. WP-Cron context → 'cron'
	 *  6. WP-CLI context → 'cli'
	 *  7. Fallback → 'unknown'  (must never be treated as trusted)
	 *
	 * @param  WP_REST_Request|null $request  Optional REST request object.
	 * @return string  One of: 'rest_api', 'store_api', 'checkout', 'admin', 'cron', 'cli', 'unknown'
	 * @since  7.2.0
	 */
		public static function get_trusted_request_source( $request = null ) {
			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				// Distinguish a WooCommerce Store API request (carries a valid nonce) from
				// a generic REST API request.  Standard pattern: check for the invalid/missing
				// nonce case first and return early; fall through to 'store_api' on success.
				if ( $request instanceof WP_REST_Request ) {
					$nonce     = $request->get_header( 'Nonce' );
					$nonce_bad = empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wc_store_api' );
					if ( $nonce_bad ) {
						return 'rest_api';
					}
					return 'store_api';
				}
				return 'rest_api';
			}

			if ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) {
				return 'checkout';
			}

			if ( is_admin() ) {
				return 'admin';
			}

			if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
				return 'cron';
			}

			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				return 'cli';
			}

			return 'unknown';
		}

	/**
	 * Check if customer is whitelisted (email, role, IP, payment method, mobile, country, state)
	 * ✅ ADDED: Early whitelist check to prevent false positives
	 * 
	 * @param WC_Order $order The order object
	 * @return bool True if customer is whitelisted
	 * @since 7.1.9
	 */
		private function is_customer_whitelisted( $order ) {
			if ( ! $order instanceof \WC_Order ) {
				return false;
			}

			// Check whitelist IP
			$user_ip = WC_Geolocation::get_ip_address();
			$whitelist_ips_opt = get_option( 'wc_settings_anti_fraud_ips_whitelist', '' );
		
			if ( ! empty( $whitelist_ips_opt ) ) {
				$whitelist_ips = array_map( 'trim', explode( ',', $whitelist_ips_opt ) );
				$normalized_user_ip = $this->normalize_ip( $user_ip );
				$normalized_whitelist = array_map( array( $this, 'normalize_ip' ), $whitelist_ips );
			
				if ( in_array( $normalized_user_ip, $normalized_whitelist, true ) ) {
					return true;
				}
			}

			// Check whitelist email
			$billing_email = $order->get_billing_email();
			// ✅ OPTIMIZED: Use cached version
			$cache_key = 'wc_af_whitelist_email_data';
			$get_whitelist_email = get_transient( $cache_key );
			if ( false === $get_whitelist_email ) {
				$get_whitelist_email = get_option( 'wc_settings_anti_fraud_whitelist', '' );
				if ( '' !== $get_whitelist_email ) {
					set_transient( $cache_key, $get_whitelist_email, HOUR_IN_SECONDS );
				}
			}
		
			if ( ! empty( $get_whitelist_email ) && ! empty( $billing_email ) ) {
				$whitelist_emails = array_map( 'trim', array_map( 'strtolower', explode( ',', $get_whitelist_email ) ) );
				if ( in_array( strtolower( trim( $billing_email ) ), $whitelist_emails, true ) ) {
					return true;
				}
			}

			// Check whitelist user role
			$user = wp_get_current_user();
			if ( $user && ! empty( $user->roles ) ) {
				$whitelisted_roles = get_option( 'wc_af_whitelist_user_roles', array() );
				$is_whitelist_enabled = get_option( 'wc_af_enable_whitelist_user_roles', 'no' );
			
				if ( 'yes' === $is_whitelist_enabled && ! empty( $whitelisted_roles ) ) {
					foreach ( $user->roles as $role ) {
						if ( in_array( $role, (array) $whitelisted_roles, true ) ) {
							return true;
						}
					}
				}
			}

			return false;
		}

	/**
	 * Normalize IP address for consistent comparison
	 * 
	 * @param string $ip The IP address to normalize
	 * @return string Normalized IP address
	 */
		private function normalize_ip( $ip ) {
			if ( empty( $ip ) ) {
				return $ip;
			}
		
			// Convert IPv4-mapped IPv6 to IPv4
			if ( preg_match( '/^::ffff:(\d+\.\d+\.\d+\.\d+)$/i', $ip, $matches ) ) {
				return $matches[1];
			}
		
			// Normalize IPv6 addresses
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				$binary = @inet_pton( $ip );
				if ( false !==  $binary ) {
					return inet_ntop( $binary );
				}
			}
		
			return $ip;
		}

		public function block_unknown_origin_classic_checkout() {

			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				return;
			}
			// Skip in admin or if feature disabled
			// ✅ FIXED: Changed default from 'yes' to 'no' to match settings definition
			if ( is_admin() || get_option( 'wc_af_block_unknown_origin', 'no' ) !== 'yes' ) {
				return;
			}

			// ✅ FIXED: Check IP/Email whitelist BEFORE blocking for classic checkout
			$user_ip = WC_Geolocation::get_ip_address();
			$whitelist_ips_opt = get_option( 'wc_settings_anti_fraud_ips_whitelist', '' );
		
			if ( ! empty( $whitelist_ips_opt ) ) {
				$whitelist_ips = array_map( 'trim', explode( ',', $whitelist_ips_opt ) );
				$normalized_user_ip = $this->normalize_ip( $user_ip );
				$normalized_whitelist = array_map( array( $this, 'normalize_ip' ), $whitelist_ips );
			
				if ( in_array( $normalized_user_ip, $normalized_whitelist, true ) ) {
					return; // Allow whitelisted IP
				}
			}

		// Security: HTTP_REFERER is client-controlled and must not be used for any
		// security decision.  Classic WooCommerce checkout (woocommerce_checkout_process)
		// is only reachable after WooCommerce verifies its own checkout nonce, which is
		// a far stronger proof of origin than the spoofable Referer header.
		//
		// As an additional server-side guard we verify the WooCommerce checkout nonce
		// ourselves and confirm the WOOCOMMERCE_CHECKOUT constant is set (defined by
		// WooCommerce's own process_checkout() before this hook fires).  Neither of
		// these signals is client-controllable.
		$wc_nonce = isset( $_POST['woocommerce-process-checkout-nonce'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			: '';

		$is_valid_wc_checkout = ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT )
			|| wp_verify_nonce( $wc_nonce, 'woocommerce-process_checkout' );

			if ( ! $is_valid_wc_checkout ) {
				// Show error to customer on checkout page.
				wc_add_notice( __( 'Blocked suspicious checkout request with unknown origin.', 'woocommerce-anti-fraud' ), 'error' );

				// Prevent order creation by redirecting back to checkout.
				wp_safe_redirect( wc_get_checkout_url() );
				exit;
			}
		}

		
		
	/**
	 * Verify Cloudflare Turnstile on Woo Blocks (Store API) checkout.
	 * Only runs when CAPTCHA is enabled and type is cf_turnstile.
	 *
	 * @throws RouteException
	 */
		public function verify_captcha_on_checkout( WC_Order $order ) {

			if ( 'yes' !== get_option( 'wc_af_recaptcha_enable_captcha', 'no' ) ) {
				return;
			}

			if ( 'cf_turnstile' !== get_option( 'wc_af_recaptcha_type', '' ) ) {
				return;
			}

			$turnstile_secret_key = get_option( 'wc_af_turnstile_secret_key', '' );

			if ( empty( $turnstile_secret_key ) ) {
				return;
			}

			$turnstile_response = isset( $_COOKIE['turnstile_response'] )
			? sanitize_text_field( $_COOKIE['turnstile_response'] )
			: '';

			if ( empty( $turnstile_response ) ) {
				throw new RouteException(
				'recaptcha_verification_incomplete',
				esc_html( __( 'Please complete the Turnstile verification.', 'woocommerce-anti-fraud' ) ),
				400
				);
			}

			$result = WC_AF_Captcha_Verification_Service::instance()->verify_turnstile(
			$turnstile_response,
			$turnstile_secret_key,
			WC_Geolocation::get_ip_address(),
			WC_AF_Captcha_Verification_Service::CTX_BLOCK_CHECKOUT
			);

			if ( is_wp_error( $result ) ) {
				throw new RouteException(
				'recaptcha_verification_failed',
				esc_html( $result->get_error_message() ),
				400
				);
			}
		}

	/**
	 * Verify Google reCAPTCHA token for Woo Blocks (Store API) checkout.
	 * Only runs when CAPTCHA is enabled and type is google_recaptcha.
	 *
	 * Token resolution order (first non-empty value wins):
	 *  1. Woo Blocks extension_data  — requires woocommerce_store_api_register_endpoint_data
	 *     so the Store API does not strip it from the request.
	 *  2. Cookie 'recaptcha_response' — belt-and-suspenders fallback.
	 *  3. Header 'x-recaptcha-response' — headless / custom integrations.
	 *
	 * @param  mixed            $order
	 * @param  WP_REST_Request  $request
	 * @throws RouteException
	 */
		public function google_captcha_verify_on_checkout( $order, $request ) {

			if ( 'yes' !== get_option( 'wc_af_recaptcha_enable_captcha', 'no' ) ) {
				return;
			}

			if ( 'google_recaptcha' !== get_option( 'wc_af_recaptcha_type' ) ) {
				return;
			}

			// 1. Woo Blocks extension_data (primary channel for Block Checkout).
			$extensions           = $request->get_param( 'extensions' );
			$token_from_extension = '';
			if ( is_array( $extensions ) && isset( $extensions['checkout-captcha-block']['checkout_captcha'] ) ) {
				$token_from_extension = sanitize_text_field( $extensions['checkout-captcha-block']['checkout_captcha'] );
			}

			// 2. Cookie fallback (set by frontend success callback; covers Classic Checkout too).
			$token_from_cookie = isset( $_COOKIE['recaptcha_response'] )
			? sanitize_text_field( wp_unslash( $_COOKIE['recaptcha_response'] ) )
			: '';

			// 3. Header fallback (headless / custom integrations).
			$token_from_header = sanitize_text_field( (string) $request->get_header( 'x-recaptcha-response' ) );

			$token = ! empty( $token_from_extension )
			? $token_from_extension
			: ( ! empty( $token_from_cookie ) ? $token_from_cookie : $token_from_header );

			$secret = get_option( 'wc_af_recaptcha_secret_key' );

			if ( empty( $token ) || empty( $secret ) ) {
				throw new RouteException(
				'recaptcha_missing',
				esc_html( __( 'Please complete the reCAPTCHA verification.', 'woocommerce-anti-fraud' ) ),
				400
				);
			}

			$result = WC_AF_Captcha_Verification_Service::instance()->verify_google( $token, $secret, WC_AF_Captcha_Verification_Service::CTX_BLOCK_CHECKOUT );

			if ( is_wp_error( $result ) ) {
				throw new RouteException(
				$result->get_error_code(),
				esc_html( $result->get_error_message() ),
				400
				);
			}
		}

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}
	}
}

WC_AF_Hooks::instance();
