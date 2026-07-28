<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'WC_AF_Score' ) ) {

	class WC_AF_Score {

		private $order_id;

		/**
		 * Current WooCommerce order object or null if not available.
		 * 
		 * @var WC_Order|null
		 */
		private $order = null;

		private $score = 0;

		private $passed_rules = array();
		private $failed_rules = array();
		private $max_weight = 20;

		/**
		 * Constructor
		 *
		 * @param $order_id
		 */
		public function __construct( $order_id ) {
			$this->order_id = $order_id;
		}

		/**
		 * Load the order into class property.
		 * We're not doing this on construct because of performance reasons.
		 */
		private function load_order() {
			if ( null == $this->order ) {
				$this->order = wc_get_order( $this->order_id );
			}
		}

	/**
	 * Calculates the risk score the order has.
	 * ✅ FIXED: Now checks ALL whitelist conditions before running rules
	 */
		public function calculate() {
			// Load the order
			$this->load_order();

			// ✅ CRITICAL FIX: Check if order is whitelisted BEFORE running any rules
			// Check if whitelist_action meta is already set (means order was whitelisted in do_check)
			$whitelist_action = $this->order->get_meta( 'whitelist_action', true );
			if ( ! empty( $whitelist_action ) ) {
				Af_Logger::debug( 'Score calculation skipped - order is whitelisted: ' . $whitelist_action );
				$this->score = 100;
				return;
			}

			// Additional IP check for redundancy
			$customer_ip       = $this->order->get_customer_ip_address();
			$all_whitelist_ips = get_option( 'wc_settings_anti_fraud_ips_whitelist' );
		
			if ( ! empty( $all_whitelist_ips ) && ! empty( $customer_ip ) ) {
				// Use same parsing logic as whitelistedIpAddresses method
				$whitelist_ips = array_map( 'trim', preg_split('/[\s,]+/', $all_whitelist_ips, -1, PREG_SPLIT_NO_EMPTY ) );
			
				// Normalize IPs for comparison
				$normalized_user_ip = $this->normalize_ip_address( $customer_ip );
				$normalized_whitelist = array_map( array( $this, 'normalize_ip_address' ), $whitelist_ips );
			
				if ( in_array( $normalized_user_ip, $normalized_whitelist, true ) ) {
					Af_Logger::debug( 'Score calculation skipped - customer IP is whitelisted: ' . $customer_ip );
					$this->score = 100;
					return;
				}
			}

			// ✅ If we reach here, order is NOT whitelisted - proceed with rule evaluation
			Af_Logger::debug( 'Order not whitelisted - running fraud rules...' );

			// Get the rules
			$rules = WC_AF_Rules::get()->get_rules();

			// Set points
			$score       = 100;
			$risk_points = 0;
			// Count the rules
			if ( count( $rules ) > 0 ) {

				// Loop through the rules
				foreach ( $rules as $rule ) {

					// Check if the rule reported a risk
					if ( true === $rule->is_enabled() && true === $rule->is_risk( $this->order ) ) {
						$risk_points          += (int) $rule->get_risk_points();
						$score                -= (int) $rule->get_risk_points();
						$this->failed_rules[] = $rule;
					} else {
						$this->passed_rules[] = $rule;
					}
				}
			}
			// echo '<pre>'; print_r($score); echo '</pre>'; exit;
			// echo '<pre>'; print_r($risk_points); echo '</pre>';
			// echo '<pre>'; print_r($rules); echo '</pre>'; exit;
			// $score = ($this->max_weight*100)/$risk_points;
			// Calculate score
			// $this->score = absint( $score );
			Af_Logger::debug( 'Anti fraud final score ' . $score );
			$this->score = $score;
		}

	/**
	 * Normalize IP address for consistent comparison (IPv4/IPv6 handling)
	 * 
	 * @param string $ip The IP address to normalize
	 * @return string Normalized IP address
	 * @since 7.1.9
	 */
		private function normalize_ip_address( $ip ) {
			if ( empty( $ip ) ) {
				return $ip;
			}
		
			// Convert IPv4-mapped IPv6 to IPv4 (e.g., ::ffff:192.168.1.1 → 192.168.1.1)
			if ( preg_match( '/^::ffff:(\d+\.\d+\.\d+\.\d+)$/i', $ip, $matches ) ) {
				return $matches[1];
			}
		
			// Normalize IPv6 addresses (expand compressed notation)
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				$binary = @inet_pton( $ip );
				if ( false !== $binary ) {
					return inet_ntop( $binary );
				}
			}
		
			// Return IPv4 as-is
			return $ip;
		}

		/**
		 * Get the score
		 *
		 * @return int
		 * @since  1.0.0
		 *
		 */
		public function get_score() {
			return $this->score;
		}


		/**
		 * Get passed rules
		 *
		 * @return array
		 * @since  1.0.0
		 *
		 */
		public function get_passed_rules() {
			return $this->passed_rules;
		}

		/**
		 * Get failed rules
		 *
		 * @return array
		 * @since  1.0.0
		 *
		 */
		public function get_failed_rules() {
			return $this->failed_rules;
		}

	}

}
