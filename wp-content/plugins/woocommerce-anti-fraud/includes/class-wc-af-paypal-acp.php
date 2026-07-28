<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_AF_PayPal_ACP {

	public function __construct() {
		// Run before WooCommerce PayPal endpoints
		add_action( 'init', [ $this, 'maybe_throttle' ], 0 );
		// Clear transients whenever ACP settings are updated
		add_action( 'update_option_wc_settings_anti_fraud_paypal_window_seconds', [ $this, 'clear_all_counters' ], 10, 2 );
		add_action( 'update_option_wc_settings_anti_fraud_paypal_max_per_window', [ $this, 'clear_all_counters' ], 10, 2 );
		add_action( 'update_option_wc_settings_anti_fraud_paypal_max_per_hour', [ $this, 'clear_all_counters' ], 10, 2 );
		add_action( 'update_option_wc_af_paypal_acp_enabled', [ $this, 'clear_all_counters' ], 10, 2 );

		add_action( 'update_option_wc_af_global_rate_limit_max', [ $this, 'clear_all_counters' ], 10, 2 );
		add_action( 'update_option_wc_af_global_time_limit_max', [ $this, 'clear_all_counters' ], 10, 2 );
		add_action( 'update_option_wc_af_enable_global_rate_limit', [ $this, 'clear_all_counters' ], 10, 2 );
	}



	 /**
	 * Clear all throttling counters (transients).
	 */
	public function clear_all_counters() {
		global $wpdb;

		$prefix1 = $wpdb->esc_like( '_transient_opmc_ppcp_rl_' ) . '%';
		$prefix2 = $wpdb->esc_like( '_transient_timeout_opmc_ppcp_rl_' ) . '%';

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options}
				 WHERE option_name LIKE %s OR option_name LIKE %s",
				$prefix1,
				$prefix2
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options}
				 WHERE option_name = %s",
				'_transient_wc_af_global_limit_counter'
			)
		);
	}

	/**
	 * Check if current request is for a PayPal "hot endpoint".
	 */
	private function is_hot_endpoint() {
		
		$action = isset($_GET['wc-ajax']) ? sanitize_text_field( wp_unslash($_GET['wc-ajax']) ) : '';

		// Existing check for AJAX-based PayPal
		if ( in_array($action, [ 'ppc-create-order', 'ppc-approve-order', 'checkout' ], true) ) {
			return true;
		}
		// ✅ New: Detect Store API-based checkout attempts
		$request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw( wp_unslash($_SERVER['REQUEST_URI']) ) : '';
		if ( strpos($request_uri, '/wp-json/wc/store/v1/checkout') !== false ) {
			// Optionally also check if PayPal or PayPal Card payment is being used
			$raw = file_get_contents('php://input');
			
			if ( strpos($raw, 'ppcp-gateway') !== false ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Generate a unique key per user (IP + User Agent + WC Session).
	 */
	private function get_key() {
		$ip = isset($_SERVER['REMOTE_ADDR']) ? filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) : '0.0.0.0';

		if (false === $ip ) {
			$ip = '0.0.0.0';
		}
		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
		$sid  = WC()->session ? WC()->session->get_customer_id() : '';
		return 'opmc_ppcp_rl_' . md5($ip . '|' . $ua . '|' . $sid);
	}

	/**
	 * Check if user is whitelisted (bypass throttling)
	 * Uses the same conditions as the country block validation
	 */
	private function is_user_whitelisted() {
		// Get current user roles
		$user = wp_get_current_user();
		$user_roles = $user->roles;

		$wc_af_whitelist_user_roles = get_option('wc_af_whitelist_user_roles', []);

		// ✅ Whitelist IP check
		$userIp = WC_Geolocation::get_ip_address();
		$whitelist_ips_opt = get_option('wc_settings_anti_fraud_ips_whitelist');
		$whitelist_ips = 'false';

		if (!empty($whitelist_ips_opt)) {
			$s_whitelist_ips = explode(',', $whitelist_ips_opt);
			if (in_array($userIp, $s_whitelist_ips, true)) {
				$whitelist_ips = 'true';
			}
		}

		// ✅ Whitelist role check
		$selected_whitelisted_role = 'false';
		$is_enable_whitelist_user_roles = get_option('wc_af_enable_whitelist_user_roles');
		if ('yes' === $is_enable_whitelist_user_roles) {
			foreach ($user_roles as $role) {
				if (in_array($role, $wc_af_whitelist_user_roles, true)) {
					$selected_whitelisted_role = 'true';
					break;
				}
			}
		}

		// ✅ Whitelist payment method check
		$selected_whitelist_payment_method = 'false';
		if ('yes' === get_option('wc_af_enable_whitelist_payment_method')) {
			$get_whitelist_payment_method = get_option('wc_settings_anti_fraud_whitelist_payment_method');
			$payment_method_from_checkout = '';

			if ( function_exists('WC') && WC()->session ) {
				// Classic checkout (AJAX)
				$payment_method_from_checkout = WC()->session->get('chosen_payment_method');
			} else {
				// Store API or PayPal ACP – no session exists
				$raw = file_get_contents('php://input');
				if ( ! empty( $raw ) ) {
					$decoded = json_decode( $raw, true );
					if ( isset( $decoded['payment_method'] ) ) {
						$payment_method_from_checkout = sanitize_text_field( $decoded['payment_method'] );
					}
				}
			}
			
			if (!empty($get_whitelist_payment_method) && in_array($payment_method_from_checkout, $get_whitelist_payment_method, true)) {
				$selected_whitelist_payment_method = 'true';
			}
		}

		// ✅ OPTIMIZED: Whitelist email check - use cached version
		$cache_key = 'wc_af_whitelist_email_data';
		$get_whitelist_email = get_transient( $cache_key );
		if ( false === $get_whitelist_email ) {
			$get_whitelist_email = get_option('wc_settings_anti_fraud_whitelist', '');
			if ( '' !== $get_whitelist_email ) {
				set_transient( $cache_key, $get_whitelist_email, HOUR_IN_SECONDS );
			}
		}
		$single_whitelist_email_arr = explode("\n", $get_whitelist_email);
		$selected_whitelisted_email = 'false';

		// Get email from raw POST data for PayPal endpoints
		$customer_billing_email = $this->get_email_from_raw_post();
		if (empty($customer_billing_email)) {
			// Fallback to POST data if not found in raw data
			if ( is_checkout() ) {
				if ( !isset( $_POST['woocommerce-process-checkout-nonce'] ) ) {
					if ( ! wp_verify_nonce(  sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
						 wp_die( esc_html__( 'Nonce verification failed!', 'woocommerce-anti-fraud' ) );
					}
				}
			}
			$customer_billing_email = isset($_POST['billing_email']) ? sanitize_text_field($_POST['billing_email']) : '';
		}

		if (!empty($customer_billing_email) && in_array($customer_billing_email, $single_whitelist_email_arr, true)) {
			$selected_whitelisted_email = 'true';
		}

		// ✅ Wildcard email whitelist check
		$selected_wildcard_whitelisted_email = $this->call_wildcard_email_validation();

		// Check if any whitelist condition is true
		if ('true' === $selected_whitelisted_email ||
			'true' === $selected_whitelisted_role ||
			'true' === $selected_whitelist_payment_method ||
			'true' === $selected_wildcard_whitelisted_email ||
			'true' === $whitelist_ips) {
			return true;
		}

		return false;
	}

	/**
	 * Parse raw POST data to find the email address
	 * This works because PayPal endpoints receive form data as application/x-www-form-urlencoded
	 */
	private function get_email_from_raw_post() {
		$email = '';
		
		// Read the raw POST data
		$raw_post = file_get_contents('php://input');
		
		if (!empty($raw_post)) {
			// Parse the URL-encoded data
			parse_str($raw_post, $post_data);
			
			// Look for billing_email in the parsed data
			if (isset($post_data['billing_email']) && !empty($post_data['billing_email'])) {
				$email = sanitize_email($post_data['billing_email']);
			}
		}
		
		return $email;
	}

	/**
	 * Wildcard email validation (copied from your main class)
	 */
	private function call_wildcard_email_validation() {
		$customer_email = '';
		$whitelist_email = 'false';

		// Get email from raw POST data first
		$customer_email = $this->get_email_from_raw_post();
		if (empty($customer_email)) {
			// Fallback to POST data
			if ( is_checkout() ) {
				if ( !isset( $_POST['woocommerce-process-checkout-nonce'] ) ) {
					if ( ! wp_verify_nonce(  sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
						 wp_die( esc_html__( 'Nonce verification failed!', 'woocommerce-anti-fraud' ) );
					}
				}
			}
			
			$customer_email = isset($_POST['billing_email']) ? sanitize_text_field($_POST['billing_email']) : '';
		}

		// ✅ OPTIMIZED: Use cached version
		$cache_key = 'wc_af_whitelist_email_data';
		$get_whitelist_email = get_transient( $cache_key );
		if ( false === $get_whitelist_email ) {
			$get_whitelist_email = get_option('wc_settings_anti_fraud_whitelist', '');
			if ( '' !== $get_whitelist_email ) {
				set_transient( $cache_key, $get_whitelist_email, HOUR_IN_SECONDS );
			}
		}

		if ('' != $get_whitelist_email) {
			$email_str_array = explode("\n", $get_whitelist_email);

			if (is_array($email_str_array) && count($email_str_array) > 0) {
				if (!empty($email_str_array) && is_array($email_str_array)) {
					foreach ($email_str_array as $setting_email) {
						$valid_customer = $this->create_email_pattern($setting_email, $customer_email);

						if (isset($valid_customer) && 'true' == $valid_customer) {
							$whitelist_email = 'true';
							break;
						}
					}
				}
			}
		}

		return $whitelist_email;
	}

	/**
	 * Create email pattern for wildcard matching (copied from your main class)
	 */
	private function create_email_pattern( $setting_email, $customer_email) {
		if (empty($setting_email) || empty($customer_email)) {
			return 'false';
		}

		// Convert wildcard pattern to regex
		$allowed_pattern = preg_quote($setting_email, '/'); // Escape special regex characters
		$allowed_pattern = str_replace('\*', '.*', $allowed_pattern); // Convert '*' to '.*' (any number of characters)
		$allowed_pattern = str_replace('\?', '.', $allowed_pattern);  // Convert '?' to '.' (single character match)

		// Create final regex pattern
		$allowed_pattern = '/^' . $allowed_pattern . '$/i'; // 'i' makes it case-insensitive

		return preg_match($allowed_pattern, $customer_email) ? 'true' : 'false';
	}

	/**
	 * Throttle PayPal attempts if enabled in settings.
	 */
	public function throttle() {
		// Check if ACP settings are enabled
		$enabled_acp = get_option('wc_af_paypal_acp_enabled', 'no');
		$enable_global_rate = get_option('wc_af_enable_global_rate_limit', 'no');
		if ('yes' !== $enabled_acp && 'yes' !== $enable_global_rate) {
			return;
		}

		// Check if user is whitelisted - bypass throttling if true
		if ($this->is_user_whitelisted()) {
			// Completely bypass throttling for whitelisted users
			// Also clear any existing throttling counters for this user
			$key_base = $this->get_key();
			delete_transient($key_base . '_w');
			delete_transient($key_base . '_h');
			delete_transient('wc_af_global_limit_counter');
			return;
		}

		// Get settings (with fallbacks)
		$window_seconds = (int) get_option('wc_settings_anti_fraud_paypal_window_seconds', 300);
		$max_per_window = (int) get_option('wc_settings_anti_fraud_paypal_max_per_window', 5);
		$max_per_hour = (int) get_option('wc_settings_anti_fraud_paypal_max_per_hour', 5);

		$key_base = $this->get_key();
		$key_win = $key_base . '_w';
		$key_hour = $key_base . '_h';

		$win_count = (int) get_transient($key_win);
		$hour_count = (int) get_transient($key_hour);
		$message = __('Too many PayPal checkout attempts. Please wait a minute and try again.', 'woocommerce-anti-fraud');
		// If user already over limits, return error
		if ('yes' === $enabled_acp) {
			if ($win_count >= $max_per_window || $hour_count >= $max_per_hour) {
				
			
				if ( $this->wc_af_is_block_checkout_request() ) {
						add_action( 'woocommerce_store_api_checkout_update_order_from_request', function( $order, $request ) {
							   
								   throw new \WC_REST_Exception(
									   'rate_limited',
									   __('Too many PayPal checkout attempts. Please wait a minute and try again.', 'woocommerce-anti-fraud'),
									   400
								   );
								
						}, 10, 2 );
				} else {
					wp_send_json_error([
						'name'    => 'rate_limited',
						'message' => __( 'Too many PayPal checkout attempts. Please wait a minute and try again.', 'woocommerce-anti-fraud' ),
					], 429);
					exit;
				}
			}

			// Increment counters
			$win_count++;
			$hour_count++;
			

			// Persist counters
			set_transient($key_win, $win_count, $window_seconds);
			set_transient($key_hour, $hour_count, HOUR_IN_SECONDS);
		}

		$global_time_enable = get_option('wc_af_enable_global_rate_limit', 'no');
		if ('yes' === $global_time_enable ) {

			// Global/site-wide keys (1 minute window)

			$global_key = 'wc_af_global_limit_counter';
			$global_count = (int) get_transient($global_key);
			
			$global_allow_sec = (int) get_option('wc_af_global_time_limit_max', 60);
			$global_allow_order = (int) get_option('wc_af_global_rate_limit_max', 100);

			$global_threshold = $global_allow_order;
			// If user already over limits, return error
			if ($global_count >= $global_threshold) {
				if ( $this->wc_af_is_block_checkout_request() ) {
					 add_action( 'woocommerce_store_api_checkout_update_order_from_request', function( $order, $request ) {
						   
								throw new \WC_REST_Exception(
									'rate_limited',
									__('Too many requests detected — temporarily blocked.', 'woocommerce-anti-fraud'),
									400
								);
							
					 }, 10, 2 );
				} else {
					wp_send_json_error([
						'name'    => 'rate_limited',
						'message' => __( 'Too many requests detected — temporarily blocked.', 'woocommerce-anti-fraud' ),
					], 429);
					exit;
				}
			  
			}

			$global_count++;

			// Global transient: short window to capture bursts across IPs
			// Use 60 seconds so we detect bursts per minute site-wide
			set_transient($global_key, $global_count, $global_allow_sec);

		}	
	}


	public function wc_af_is_block_checkout_request() {
		// WooCommerce 7.1+ has this helper
		if ( function_exists( 'wc_is_store_api_request' ) && wc_is_store_api_request() ) {
			return true;
		}

		// Fallback for older WC versions or non-standard setups
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

			// Matches Store API endpoints
			if ( strpos( $request_uri, '/wp-json/wc/store/' ) !== false || 
				 strpos( $request_uri, '/?rest_route=/wc/store/' ) !== false ) {
				return true;
			}
		}
		$request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw( wp_unslash($_SERVER['REQUEST_URI']) ) : '';
		// Check JSON request content type (used by Store API)
		$content_type = isset( $_SERVER['CONTENT_TYPE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['CONTENT_TYPE'] ) ) : '';
		if ( stripos( $content_type, 'application/json' ) !== false &&
			 strpos( $request_uri, 'checkout' ) !== false ) {
			return true;
		}

		return false;
	}


	/**
	 * Run throttle if on a hot endpoint.
	 */
	public function maybe_throttle() {
		if ($this->is_hot_endpoint()) {
			$this->throttle();
		}
	}
}

// Initialize the class
new WC_AF_PayPal_ACP();
