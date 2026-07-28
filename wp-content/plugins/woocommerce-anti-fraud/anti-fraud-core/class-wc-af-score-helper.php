<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


if ( ! class_exists( 'WC_AF_Score_Helper' ) ) {
	class WC_AF_Score_Helper {

		/**
		 * Get score meta
		 *
		 * @param $score_points
		 *
		 * @static
		 *
		 * @return array
		 * @since  1.0.0
		 *
		 */
		public static function get_score_meta( $score_points, $order_id = '' ) {
			// Af_Logger::debug('Score Points '.$score_points);
			// No score? return defaults
			if ( '' == $score_points ) {
				return array(
					'color' => '#adadad',
					'label' => __( 'No fraud checking has been done on this order yet.', 'woocommerce-anti-fraud' ),
				);
			}

			$meta           = array(
				'label' => __( 'Low Risk', 'woocommerce-anti-fraud' ),
				'color' => '#7AD03A',
			);
			$high_threshold = get_option( 'wc_settings_anti_fraud_higher_risk_threshold' );

			$low_threshold    = get_option( 'wc_settings_anti_fraud_low_risk_threshold' );
			$score_points     = self::invert_score( $score_points );
			$whitelist_action = opmc_hpos_get_post_meta( $order_id, 'whitelist_action', true );
			if ( $score_points <= $high_threshold && $score_points >= $low_threshold ) {

				$meta['label'] = __( 'Medium Risk', 'woocommerce-anti-fraud' );
				$meta['color'] = ( 'user_email_whitelisted' == $whitelist_action ) ? 'grey' : '#FFAE00';
			} elseif ( $score_points > $high_threshold ) {

				$meta['label'] = __( 'High Risk', 'woocommerce-anti-fraud' );
				$meta['color'] = ( 'user_email_whitelisted' == $whitelist_action ) ? 'grey' : '#D54E21';
			}

			return $meta;
		}

		/**
		 * Invert a score
		 *
		 * @param        $score
		 *
		 * @return int
		 * @since  1.0.0
		 *
		 */
		public static function invert_score( $score ) {

			if ( $score < 0 ) {

				$score = 0;

			} else {

				$score = $score;
			}

			return 100 - $score;
		}

	/**
	 * Schedule a fraud check
	 *
	 * @param $order_id
	 * @param bool   $checknow  Run immediately instead of scheduling.
	 * @param string $timestamp Optional timestamp for scheduled check.
	 * @param bool   $force     When true, clears any existing score so the check always re-runs.
	 *                          Use this when the whitelist has been updated after the initial check.
	 *
	 * @since  1.0.0
	 */
		public function schedule_fraud_check( $order_id, $checknow = false, $timestamp = '', $force = false ) {

			// When force re-check is requested, wipe the cached score so the full
			// whitelist + rule evaluation runs again unconditionally.
			if ( $force ) {
				opmc_hpos_delete_post_meta( $order_id, 'wc_af_score', '' );
				opmc_hpos_delete_post_meta( $order_id, 'whitelist_action', '' );
				opmc_hpos_delete_post_meta( $order_id, 'wc_af_failed_rules', '' );
				opmc_hpos_delete_post_meta( $order_id, '_wc_af_recommended_status', '' );
				delete_transient( 'wc_af_whitelist_email_data' );
			}

			if ( ! empty( opmc_hpos_get_post_meta( $order_id, 'wc_af_score' ) ) ) {
				return;
			}

			// Set order to wait queue
			opmc_hpos_update_post_meta( $order_id, '_wc_af_waiting', true );

			if ( $checknow ) {
				$this->do_check( $order_id );
			} else {
				$timestamp = empty( $timestamp ) ? time() : $timestamp;

				wp_schedule_single_event( time(), 'wc-af-check', array( 'order_id' => $order_id ) );
			}
		}

	/**
	 * Check whether an order's customer is currently covered by any whitelist entry.
	 *
	 * Unlike the transient-cached whitelistedEmail(), this method reads fresh data
	 * from the database so it picks up whitelist changes made moments before.
	 *
	 * Checks are performed in the same order as do_check(): wildcard → email → IP → role.
	 *
	 * @param WC_Order $order
	 * @return bool
	 * @since 7.2.0
	 */
		public function is_order_currently_whitelisted( $order ) {
			// Wildcard – all emails pass.
			$wildcard = get_option( 'wildcard_whitelist_email' );
			if ( isset( $wildcard ) && 'true' === $wildcard ) {
				return true;
			}

			// Email whitelist (read directly from option – no transient).
			$email           = strtolower( trim( $order->get_billing_email() ) );
			$email_whitelist = get_option( 'wc_settings_anti_fraud_whitelist', '' );
			if ( ! empty( $email_whitelist ) ) {
				$whitelist = $this->parse_input_data( $email_whitelist );
				if ( in_array( $email, $whitelist, true ) ) {
					return true;
				}
			}

			// Also check the registered user e-mail if different from billing e-mail.
			$user_id = $order->get_user_id();
			if ( $user_id ) {
				$user = get_userdata( $user_id );
				if ( $user && ! empty( $user->user_email ) ) {
					$user_email = strtolower( trim( $user->user_email ) );
					if ( $user_email !== $email && ! empty( $email_whitelist ) ) {
						$whitelist = $this->parse_input_data( $email_whitelist );
						if ( in_array( $user_email, $whitelist, true ) ) {
							return true;
						}
					}
				}
			}

			// IP whitelist.
			$ip = $order->get_customer_ip_address();
			if ( ! empty( $ip ) && $this->whitelistedIpAddresses( $ip ) ) {
				return true;
			}

			// User role whitelist.
			$is_enable_whitelist_user_roles = get_option( 'wc_af_enable_whitelist_user_roles' );
			if ( 'yes' === $is_enable_whitelist_user_roles && $user_id ) {
				$user = get_userdata( $user_id );
				if ( $user && ! empty( $user->roles ) ) {
					$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles', array() );
					if ( ! empty( $wc_af_whitelist_user_roles ) ) {
						foreach ( $user->roles as $role ) {
							if ( in_array( $role, (array) $wc_af_whitelist_user_roles, true ) ) {
								return true;
							}
						}
					}
				}
			}

			return false;
		}

		/**
		 * Cancel scheduled fruad check
		 *
		 * @param $order_id
		 */
		public function cancel_schedule_fraud_check( $order_id ) {
			// Try to get the Anti Fraud score
			$score = opmc_hpos_get_post_meta( $order_id, 'wc_af_score', true );

			if ( $this->is_fraud_check_queued( $order_id ) ) {
				wp_clear_scheduled_hook( 'wc-af-check', array( 'order_id' => $order_id ) );

				// Get the order
				$order = wc_get_order( $order_id );
				opmc_hpos_update_post_meta( $order_id, '_wc_af_waiting', false );
			}

		}

		/**
		 * Returns a flag indicating if a fraud check has been queued but not yet completed
		 *
		 * @param $order_id
		 *
		 * @since  1.0.2
		 */
		public static function is_fraud_check_queued( $order_id ) {

			$waiting = opmc_hpos_get_post_meta( $order_id, '_wc_af_waiting', true );

			return ( ! empty( $waiting ) );

		}

		/**
		 * Returns a flag indicating if a fraud check has been completed
		 *
		 * @param $order_id
		 *
		 * @since  1.0.2
		 */
		public static function is_fraud_check_complete( $order_id ) {

			$score = opmc_hpos_get_post_meta( $order_id, 'wc_af_score', true );

			return ( ! empty( $score ) );

		}
		public function parse_input_data( $string ) {
			return array_map(
				'strtolower',
				array_map(
					'trim',
					preg_split('/[\s,]+/', $string, -1, PREG_SPLIT_NO_EMPTY)
				)
			);
		}

		public function whitelistedEmail( $email ) {
			// ✅ OPTIMIZED: Use cached version if available (transient cache)
			$cache_key = 'wc_af_whitelist_email_data';
			$email_whitelist = get_transient( $cache_key );
			
			if ( false === $email_whitelist ) {
				// Load whitelist data (with autoload=false, so it won't autoload)
				$email_whitelist = get_option( 'wc_settings_anti_fraud_whitelist', '' );
				// Cache for 1 hour
				if ( '' !== $email_whitelist ) {
					set_transient( $cache_key, $email_whitelist, HOUR_IN_SECONDS );
				}
			}
		
			// Split by new lines, commas, and trim each entry
			$whitelist = $this->parse_input_data($email_whitelist);
		
		
			$whitlisted_email = false;
		
			// Check if the email exists in the whitelist
			if (in_array($email, $whitelist)) {
				Af_Logger::debug('whitelist email found: ' . $email);
				// $this->log->info('Email found: ' . $email, array('source' => 'wc_af_log'));
				$whitlisted_email = true;
			} 
			// else {
			// 	Af_Logger::debug('whitelist email not found: ' . $email);
			// 	// $this->log->info('whitelist email not found.', array('source' => 'wc_af_log'));
			// }
		
			// Log the result properly
			Af_Logger::debug('whitlisted_email: ' . ( $whitlisted_email ? 'true' : 'false' ));
			// $this->log->info('whitlisted_email: ' . ($whitlisted_email ? 'true' : 'false'), array('source' => 'wc_af_log'));
		
			return $whitlisted_email;
		}

		public function whitelistedIpAddresses( $ip_address = '' ) {

			$ip_address     = empty( $ip_address ) ? WC_Geolocation::get_ip_address() : $ip_address;
			$ips_whitelist  = get_option( 'wc_settings_anti_fraud_ips_whitelist' );
			$ips_whitelist  = $this->parse_input_data($ips_whitelist);
			
			$is_whitelisted = false;

			// ✅ FIXED: Normalize IPs for IPv4/IPv6 matching
			$normalized_user_ip = $this->normalize_ip_address( $ip_address );
			$normalized_whitelist = array_map( array( $this, 'normalize_ip_address' ), $ips_whitelist );

			if ( in_array( $normalized_user_ip, $normalized_whitelist, true ) ) {
				Af_Logger::debug( 'whitelist ip address found: ' . $ip_address . ' (normalized: ' . $normalized_user_ip . ')' );
				$is_whitelisted = true;
			}
			Af_Logger::debug( 'whitelisted_ip ' . $is_whitelisted );

			return $is_whitelisted;
		}

		/**
		 * Normalize IP address for consistent comparison (IPv4/IPv6 handling)
		 * ✅ ADDED: Fixes IPv4/IPv6 mismatch in whitelist checks
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
	 * Do the actual fraud check
	 *
	 * @param $order_id
	 *
	 * @since 1.0.0
	 * @since 1.0.13 Revert points score to make clear comparison with email score.
	 */
		public function do_check( $order_id ) {

			// if (class_exists('WC_Logger')) {
			// 	$this->log = wc_get_logger();
			// }
	
			// Check if logger is initialized properly
			// if (!isset($this->log)) {
			// 	error_log('Logger not initialized!'); // Fallback if something goes wrong
			// 	return;
			// }
		$order = wc_get_order( $order_id );
		$order_email = $order->get_billing_email();

		// ✅ CRITICAL FIX: Get order details and check whitelists FIRST before any cancellation logic
		// Get order IP address early for whitelist checks
		$order_ip = $order->get_customer_ip_address();
		
			// Get user details for role checks
			$user_id = $order->get_user_id();
			$user = $user_id ? get_userdata( $user_id ) : null;
			$user_roles = $user && ! empty( $user->roles ) ? $user->roles : array();
		
			// Check 1: Email Whitelist (both billing email and user email)
			$wildcard = get_option( 'wildcard_whitelist_email' );
			$is_email_whitelisted = $this->whitelistedEmail( $order_email );
		
			// Also check user email if different from billing email
			if ( ! $is_email_whitelisted && $user ) {
				$user_email = $user->user_email;
				if ( ! empty( $user_email ) && strtolower( $user_email ) !== strtolower( $order_email ) ) {
					$is_email_whitelisted = $this->whitelistedEmail( $user_email );
				}
			}
		
			if ( $is_email_whitelisted || ( isset( $wildcard ) && 'true' == $wildcard ) ) {
				Af_Logger::debug( 'Fraud Check exited because this order email is whitelisted.' );
				opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
				opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_email_whitelisted' );
				$order->add_order_note( __( 'Order fraud checks skipped due to whitelisted email.', 'woocommerce-anti-fraud' ) );

				return;
			}

			// Check 2: IP Whitelist
			if ( ! empty( $order_ip ) && $this->whitelistedIpAddresses( $order_ip ) ) {
				Af_Logger::debug( 'Fraud Check exited because this order ip is whitelisted: ' . $order_ip );
				opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
				opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_ip_whitelisted' );
				$order->add_order_note( __( 'Order fraud checks skipped due to whitelisted IP address.', 'woocommerce-anti-fraud' ) );

				return;
			}

			// Check 3: User Role Whitelist (improved to check ALL roles, not just first)
			$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles' );
			if ( empty( $wc_af_whitelist_user_roles ) ) {
				$wc_af_whitelist_user_roles = array();
			}
			$is_enable_whitelist_user_roles = get_option( 'wc_af_enable_whitelist_user_roles' );
		
			if ( 'yes' === $is_enable_whitelist_user_roles && ! empty( $wc_af_whitelist_user_roles ) && ! empty( $user_roles ) ) {
				// Check if ANY of the user's roles is whitelisted (improved B2BKing compatibility)
				foreach ( $user_roles as $role ) {
					if ( in_array( $role, (array) $wc_af_whitelist_user_roles ) ) {
						Af_Logger::debug( 'Fraud Check exited because user role is whitelisted: ' . $role );
						opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
						opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_role_whitelisted' );
						/* translators: %s: User role that caused fraud checks to be skipped */
						$order->add_order_note( sprintf( __( 'Order fraud checks skipped due to whitelisted user role: %s', 'woocommerce-anti-fraud' ), $role ) );

						return;
					}
				}
			}

			// Check 4: Payment Method Whitelist
			$is_enable_whitelist_payment_method = get_option( 'wc_af_enable_whitelist_payment_method' );
			if ( 'yes' === $is_enable_whitelist_payment_method ) {
				$whitelist_payment_methods = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );
				$payment_method = $order->get_payment_method();
			
				if ( ! empty( $whitelist_payment_methods ) && in_array( $payment_method, (array) $whitelist_payment_methods ) ) {
					Af_Logger::debug( 'Fraud Check exited because payment method is whitelisted: ' . $payment_method );
					opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
					opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_payment_method_whitelisted' );
					/* translators: %s: Payment Method that caused fraud checks to be skipped */
					$order->add_order_note( sprintf( __( 'Order fraud checks skipped due to whitelisted payment method: %s', 'woocommerce-anti-fraud' ), $payment_method ) );

					return;
				}
			}

			// Check 5: Phone Number Whitelist
			$is_enable_whitelist_phone_number = get_option( 'wc_af_enable_whitelist_phone_number' );
			if ( 'yes' === $is_enable_whitelist_phone_number ) {
				$whitelist_phone_numbers = get_option( 'wc_af_whitelist_phone_numbers' );
				$billing_phone = $order->get_billing_phone();
			
				if ( ! empty( $whitelist_phone_numbers ) && ! empty( $billing_phone ) ) {
					$phone_list = $this->parse_input_data( $whitelist_phone_numbers );
					$normalized_phone = preg_replace( '/[^0-9+]/', '', $billing_phone );
				
					foreach ( $phone_list as $whitelisted_phone ) {
						$normalized_whitelist_phone = preg_replace( '/[^0-9+]/', '', $whitelisted_phone );
						if ( $normalized_phone === $normalized_whitelist_phone ) {
							Af_Logger::debug( 'Fraud Check exited because phone number is whitelisted.' );
							opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
							opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_mobile_number' );
							$order->add_order_note( __( 'Order fraud checks skipped due to whitelisted mobile number.', 'woocommerce-anti-fraud' ) );

							return;
						}
					}
				}
			}

			// Check 6: Country Whitelist
			$is_enable_whitelist_country = get_option( 'wc_af_enable_whitelist_country' );
			if ( 'yes' === $is_enable_whitelist_country ) {
				$whitelisted_countries = get_option( 'wc_af_whitelisted_countries' );
				$billing_country = $order->get_billing_country();
			
				if ( ! empty( $whitelisted_countries ) && in_array( $billing_country, (array) $whitelisted_countries ) ) {
					Af_Logger::debug( 'Fraud Check exited because country is whitelisted: ' . $billing_country );
					opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
					opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_country' );
					/* translators: %s: Countrythat caused fraud checks to be skipped */
					$order->add_order_note( sprintf( __( 'Order fraud checks skipped due to whitelisted country: %s', 'woocommerce-anti-fraud' ), $billing_country ) );

					return;
				}
			}

			// Check 7: Zip/Postal Code Whitelist
			$is_enable_whitelist_zip = get_option( 'wc_af_enable_whitelist_zip' );
			if ( 'yes' === $is_enable_whitelist_zip ) {
				$whitelisted_zips = get_option( 'wc_af_whitelisted_zip' );
				$billing_zip = $order->get_billing_postcode();
			
				if ( ! empty( $whitelisted_zips ) && in_array( $billing_zip, (array) $whitelisted_zips ) ) {
					Af_Logger::debug( 'Fraud Check exited because zip is whitelisted: ' . $billing_zip );
					opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
					opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_zip' );
					/* translators: %s: Zip that caused fraud checks to be skipped */
					$order->add_order_note( sprintf( __( 'Order fraud checks skipped due to whitelisted zip/postal code: %s', 'woocommerce-anti-fraud' ), $billing_zip ) );

					return;
				}
			}

			// Check 8: City Whitelist
			$is_enable_whitelist_city = get_option( 'wc_af_enable_whitelist_city' );
			if ( 'yes' === $is_enable_whitelist_zip ) {
				$whitelisted_cities = get_option( 'wc_af_whitelisted_city' );
				$billing_city = $order->get_billing_city();
			
				if ( ! empty( $whitelisted_cities ) && in_array( $billing_city, (array) $whitelisted_cities ) ) {
					Af_Logger::debug( 'Fraud Check exited because city is whitelisted: ' . $billing_city );
					opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
					opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_city' );
					/* translators: %s: City that caused fraud checks to be skipped */
					$order->add_order_note( sprintf( __( 'Order fraud checks skipped due to whitelisted city: %s', 'woocommerce-anti-fraud' ), $billing_city ) );

					return;
				}
			}

			// Check 9: Address Whitelist
			$is_enable_whitelist_address = get_option( 'wc_af_enable_whitelist_address' );
			if ( 'yes' === $is_enable_whitelist_address ) {
				$whitelisted_addresses = (array) get_option( 'wc_af_whitelisted_addresses', array() );
			
				// Combine ONLY billing_address_1 and billing_address_2
				$billing_address = strtolower(
				trim(
					$order->get_billing_address_1() . ' ' .
					$order->get_billing_address_2()
				)
				);
			
				// Normalize billing address: remove punctuation, collapse spaces
				$billing_address = preg_replace( '/[[:punct:]]+/', ' ', $billing_address );
				$billing_address = preg_replace( '/\s+/', ' ', $billing_address );
				$billing_address = trim( $billing_address );
			
				if ( ! empty( $whitelisted_addresses ) && ! empty( $billing_address ) ) {
				
					// Normalize list elements (lowercase + trim)
					$whitelisted_addresses = array_map( 'strtolower', array_map( 'trim', $whitelisted_addresses ) );
				
					$is_whitelisted = false;
				
					foreach ( $whitelisted_addresses as $keyword ) {
					
						if ( '' === $keyword ) {
							continue;
						}
					
						// Normalize keyword (remove punctuation, collapse spaces)
						$norm_keyword = preg_replace( '/[[:punct:]]+/', ' ', $keyword );
						$norm_keyword = preg_replace( '/\s+/', ' ', $norm_keyword );
						$norm_keyword = trim( $norm_keyword );
					
						if ( '' === $norm_keyword ) {
							continue;
						}
					
						// 1) Direct full-phrase match
						if ( false !== strpos( $billing_address, $norm_keyword ) ) {
							$is_whitelisted = true;
							break;
						}
					
						// 2) If the original keyword has commas, treat parts as alternatives
						$alts = preg_split( '/\s*,\s*/', $keyword );
						if ( $alts && count( $alts ) > 1 ) {
							foreach ( $alts as $alt ) {
								$alt = trim( strtolower( $alt ) );
								if ( '' === $alt ) {
									continue;
								}
								$alt_norm = preg_replace( '/[[:punct:]]+/', ' ', $alt );
								$alt_norm = preg_replace( '/\s+/', ' ', $alt_norm );
								$alt_norm = trim( $alt_norm );
								if ( '' === $alt_norm ) {
									continue;
								}
								if ( false !== strpos( $billing_address, $alt_norm ) ) {
									$is_whitelisted = true;
									break 2;
								}
							}
						}
					
						// 3) Fallback: require ALL words in the normalized keyword to appear (order-insensitive)
						$words = explode( ' ', $norm_keyword );
						$all_match = true;
						$has_word  = false;
					
						foreach ( $words as $w ) {
							$w = trim( $w );
							if ( '' === $w ) {
								continue;
							}
							$has_word = true;
							if ( false === strpos( $billing_address, $w ) ) {
								$all_match = false;
								break;
							}
						}
					
						if ( $has_word && $all_match ) {
							$is_whitelisted = true;
							break;
						}
					}
				
					// If address is whitelisted, skip fraud checks
					if ( $is_whitelisted ) {
						Af_Logger::debug( 'Fraud Check exited because address is whitelisted: ' . $billing_address );
						opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
						opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_address' );
						/* translators: %s: address that caused fraud checks to be skipped */
						$order->add_order_note( sprintf( __( 'Order fraud checks skipped due to whitelisted address: %s', 'woocommerce-anti-fraud' ), $billing_address ) );
					
						return;
					}
				}
			}

			// Check 10: State Whitelist
			$is_enable_whitelist_state = get_option( 'wc_af_enable_whitelist_state' );
			if ( 'yes' === $is_enable_whitelist_state ) {
				//$whitelisted_states = get_option( 'wc_af_whitelisted_states' );
			
				$billing_country = $order->get_billing_country(); // e.g., "AU"
			
				// Get posted billing & shipping country/state
	
				// Helper to get readable state name from posted data (handles code => name)
				$resolve_state_name = function( $country, $state_raw ) {
					$state_raw = (string) $state_raw;
					$state_name = trim( $state_raw );
					if ( ! empty( $country ) && function_exists( 'WC' ) && isset( WC()->countries ) ) {
						$states = WC()->countries->get_states( $country );
						if ( is_array( $states ) && ! empty( $states ) ) {
							// If posted value is a state code and exists in keys, use the mapped name
							if ( array_key_exists( $state_raw, $states ) ) {
								$state_name = $states[ $state_raw ];
							} else {
								// try case-insensitive match against names or codes
								foreach ( $states as $code => $name ) {
									if ( 0 === strcasecmp( $name, $state_raw ) || 0 === strcasecmp( $code, $state_raw ) ) {
										$state_name = $name;
										break;
									}
								}
							}
						}
					}
					return mb_strtolower( trim( $state_name ) );
				};

				// ✅ Check whitelist state
			
			
				$get_whitelisted_states = get_option( 'wc_af_whitelisted_states', '' );
			
				if ( ! empty( $get_whitelisted_states ) ) {
					// Parse textarea input (comma or newline separated)
					if ( is_array( $get_whitelisted_states ) ) {
						$whitelist_states = $get_whitelisted_states;
					} else {
						$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_states );
					}
				
					$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
					$whitelist_states = array_map( 'mb_strtolower', $whitelist_states );
				
					$billing_state_raw = $order->get_billing_state(); // e.g., "NSW"
					$billing_state  = $resolve_state_name( $billing_country, $billing_state_raw );
				
					if ( ! empty( $billing_state ) && in_array( $billing_state, $whitelist_states, true ) ) {
					
						Af_Logger::debug( 'Fraud Check exited because state is whitelisted: ' . $billing_country );
						opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
						opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_country' );
						/* translators: %s: state that caused fraud checks to be skipped */
						$order->add_order_note( sprintf( __( 'Order fraud checks skipped due to whitelisted state: %s', 'woocommerce-anti-fraud' ), ucwords($billing_state) ) );

						return;
					}
				}
			}


			// ✅ Check whitelist first name
			$enable_whitelisting_first_name = get_option( 'wc_af_enable_whitelisting_first_name', '' );

			if ( 'yes' === $enable_whitelisting_first_name ) {

				$get_whitelisted_firstnames = get_option( 'wc_af_whitelisted_first_names', '' );
				
				if ( ! empty( $get_whitelisted_firstnames ) ) {
					// Parse textarea input (comma or newline separated)
					if ( is_array( $get_whitelisted_firstnames ) ) {
						$whitelist_firstnames = $get_whitelisted_firstnames;
					} else {
						$whitelist_firstnames = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_firstnames );
					}
					
					$whitelist_firstnames = array_filter( array_map( 'trim', (array) $whitelist_firstnames ) );
					$whitelist_firstnames = array_map( 'mb_strtolower', $whitelist_firstnames );
					
					$billing_firstname = isset( $order ) && $order->get_billing_first_name() ? $order->get_billing_first_name() : '';

					$billing_firstname = isset( $billing_firstname ) ? mb_strtolower( trim( $billing_firstname ) ) : '';
	
					if ( ! empty( $billing_firstname ) && in_array( $billing_firstname, $whitelist_firstnames, true ) ) {

						Af_Logger::debug( 'Fraud Check exited because first name is whitelisted: ' . $billing_firstname );
						opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
						opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_firstname' );
						/* translators: %s: customer first name that caused fraud checks to be skipped */
						$order->add_order_note( sprintf( __( 'Order fraud checks skipped due to whitelisted customer first name: %s', 'woocommerce-anti-fraud' ), ucwords($billing_firstname) ) );

						return;
					}
				}
			}

			// ✅ Check whitelist last name
			$enable_whitelisting_last_name = get_option( 'wc_af_enable_whitelisting_last_name', '' );

			if ( 'yes' === $enable_whitelisting_last_name ) {

				$get_whitelisted_lastnames = get_option( 'wc_af_whitelisted_last_names', '' );
			
				if ( ! empty( $get_whitelisted_lastnames ) ) {
					// Parse textarea input (comma or newline separated)
					if ( is_array( $get_whitelisted_lastnames ) ) {
						$whitelist_lastnames = $get_whitelisted_lastnames;
					} else {
						$whitelist_lastnames = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_lastnames );
					}
					
					$whitelist_lastnames = array_filter( array_map( 'trim', (array) $whitelist_lastnames ) );
					$whitelist_lastnames = array_map( 'mb_strtolower', $whitelist_lastnames );
					

					$billing_lastname = isset( $order ) && $order->get_billing_last_name() ? $order->get_billing_last_name() : '';

					$billing_lastname = isset( $billing_lastname ) ? mb_strtolower( trim( $billing_lastname ) ) : '';
					
					if ( ! empty( $billing_lastname ) && in_array( $billing_lastname, $whitelist_lastnames, true ) ) {

							Af_Logger::debug( 'Fraud Check exited because last name is whitelisted: ' . $billing_lastname );
							opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
							opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_lastname' );
							/* translators: %s: customer last name that caused fraud checks to be skipped */
							$order->add_order_note( sprintf( __( 'Order fraud checks skipped due to whitelisted customer last name: %s', 'woocommerce-anti-fraud' ), ucwords($billing_lastname) ) );

							return;
					}
				}
			}

		// Check 11: Trust Swiftly Verification
		$usr_verify_frm_ts = get_option( 'user_verified_from_ts' );
			if ( isset( $usr_verify_frm_ts ) && 'yes' == $usr_verify_frm_ts ) {
				Af_Logger::debug( 'Fraud Check exited because user is verified from Trust Swiftly.' );
				opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
				opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_ts_whitelisted' );
				$order->add_order_note( __( 'Order fraud checks skipped due to user verified from Trust Swiftly.', 'woocommerce-anti-fraud' ) );

				return;
			}

	// ✅ END OF WHITELIST CHECKS - If we reach here, order is NOT whitelisted
	// Now proceed with normal fraud rule evaluation

	// ─── Marketplace detection ────────────────────────────────────────────────
	// Detect whether this order was imported from a marketplace (eBay, Amazon,
	// Etsy, or an unknown external source) so we can apply the correct profile.
	$wc_af_marketplace_source  = null;
	$wc_af_marketplace_profile = null;

			if ( WC_AF_Marketplace_Detector::is_enabled() ) {
				$wc_af_marketplace_source = WC_AF_Marketplace_Detector::detect( $order );

				if ( WC_AF_Marketplace_Detector::should_apply_marketplace_trust( $order, $wc_af_marketplace_source ) ) {
					$wc_af_marketplace_profile = WC_AF_Marketplace_Detector::get_effective_profile( $wc_af_marketplace_source );

					if ( $wc_af_marketplace_profile ) {
						Af_Logger::debug( sprintf(
							'Marketplace order detected for order #%d: source=%s, profile=%s',
							$order_id,
							$wc_af_marketplace_source,
							$wc_af_marketplace_profile['label']
						) );

						$order->add_order_note( sprintf(
							/* translators: %s: marketplace name (e.g. eBay) */
							__( 'Marketplace order detected: %s. Marketplace Anti-Fraud profile applied.', 'woocommerce-anti-fraud' ),
							WC_AF_Marketplace_Detector::get_source_label( $wc_af_marketplace_source )
						) );
					}
				}
			} else {
				// Even when detection is disabled, save the source for display purposes (meta box badge).
				$wc_af_marketplace_source = WC_AF_Marketplace_Detector::detect( $order );
			}
	// ─────────────────────────────────────────────────────────────────────────

	// ✅ CRITICAL FIX: Check Unknown Origin AFTER whitelists and ONLY if feature is enabled
	// This fixes the bug where valid PayPal orders were being cancelled incorrectly
	$block_unknown_origin_enabled = get_option( 'wc_af_block_unknown_origin', 'no' );
		
			if ( 'yes' === $block_unknown_origin_enabled ) {

				// ✅ MARKETPLACE FIX: If marketplace detection is active and this order's profile
				// instructs us to ignore unknown origin checks, skip the cancellation logic entirely.
				$marketplace_ignore_origin = $wc_af_marketplace_profile
				&& ! empty( $wc_af_marketplace_profile['ignore_unknown_origin'] );

				if ( $marketplace_ignore_origin ) {
					Af_Logger::debug( sprintf(
					'Unknown origin check skipped for order #%d — marketplace profile: %s',
					$order_id,
					$wc_af_marketplace_profile['label']
					) );
				} else {

				$created_via = $order->get_created_via();
				$utm_source = $order->get_meta( '_wc_order_attribution_utm_source', true );
				$payment_method = $order->get_payment_method();
				$order_status = $order->get_status();
			
					// Define allowed origins
					$allowed_origins = array(
					'checkout',          // Classic shortcode checkout
					'checkout-block',    // WooCommerce Blocks checkout
					'admin',             // Orders created manually
					'rest-api',          // REST API requests
					'store-api',         // Store API
					'pos',               // Point of Sale
					'woocommerce-pos',   // WooCommerce POS plugin
					'subscription',      // Woo Subscriptions renewal
					'checkout-draft',    // Draft orders
					);
			
					// Define payment gateways that should be exempt from origin checks
					// These gateways have their own fraud protection and payment verification
					$exempt_payment_gateways = array(
					'paypal',            // PayPal Standard
					'ppec_paypal',       // PayPal Checkout
					'paypal_express',    // PayPal Express
					'stripe',            // Stripe
					'stripe_cc',         // Stripe Credit Card
					'bacs',              // Bank Transfer (pre-authorized)
					'cheque',            // Check Payment
					'cod',               // Cash on Delivery (store managed)
					);
			
					// ✅ KEY FIX: Do NOT cancel orders that have already been paid/processing
					// PayPal and other gateways verify payment before setting these statuses
					$paid_statuses = array( 'processing', 'completed', 'on-hold' );
					$is_paid = in_array( $order_status, $paid_statuses, true );
			
					// ✅ KEY FIX: Exempt legitimate payment gateways from origin checks
					$is_exempt_gateway = in_array( $payment_method, $exempt_payment_gateways, true );
			
					// Only block if ALL of these conditions are met:
					// 1. Order is NOT already paid
					// 2. Payment gateway is NOT exempt
					// 3. Origin is suspicious (empty or not in allowed list)
					$suspicious_origin = empty( $created_via ) || ! in_array( $created_via, $allowed_origins, true );
			
					// ✅ IMPROVED: Don't cancel on missing/unknown UTM alone - many legitimate orders lack UTM params
					// Only flag it as suspicious, don't auto-cancel for this reason
			
					if ( ! $is_paid && ! $is_exempt_gateway && $suspicious_origin ) {
						// Log detailed information for debugging
						Af_Logger::debug( sprintf(
						'Order #%d flagged for suspicious origin: created_via=%s, payment_method=%s, status=%s, utm_source=%s',
						$order_id,
						$created_via,
						$payment_method,
						$order_status,
						$utm_source
						) );
				
						$order->update_status( 'cancelled', 'Order cancelled - Suspicious origin detected' );
				
						$origin_display = empty( $created_via ) ? 'empty' : $created_via;
						$utm_display = empty( $utm_source ) ? 'not set' : $utm_source;
				
						$order->add_order_note(
							sprintf(
							'Order automatically cancelled due to suspicious origin (%s) or UTM source (%s). Note: Block Unknown Origin feature is enabled. Paid orders and legitimate payment gateways (PayPal, Stripe, etc.) are exempt from this check.',
							$origin_display,
							$utm_display
						)
						);
				
					// Set fraud score and reason
					opmc_hpos_update_post_meta( $order_id, 'wc_af_score', '10' );
					opmc_hpos_update_post_meta( $order_id, 'fraud_flag_reason', 'unknown_origin_blocked' );
			
					return; // Stop fraud check processing
					}

				} // end else (non-marketplace case)
			}

	// Create a Score object
	$score = new WC_AF_Score( $order_id );

		// Calculate score
		$score->calculate();

		// The score points
		$score_points  = $score->get_score();
		$failed_rules  = $score->get_failed_rules();

		// ─── Apply marketplace profile adjustments ────────────────────────────
		$marketplace_skipped_rule_labels = array();

			if ( $wc_af_marketplace_profile ) {
				$rules_to_skip = isset( $wc_af_marketplace_profile['skip_rules'] ) ? $wc_af_marketplace_profile['skip_rules'] : array();
				$score_bonus   = isset( $wc_af_marketplace_profile['base_score_bonus'] ) ? intval( $wc_af_marketplace_profile['base_score_bonus'] ) : 0;

				// Remove risk points contributed by rules that should be skipped for this marketplace.
				if ( ! empty( $rules_to_skip ) ) {
					$filtered_rules = array();
					foreach ( $failed_rules as $rule ) {
						if ( in_array( $rule->get_id(), $rules_to_skip, true ) ) {
							$score_points = min( 100, $score_points + (int) $rule->get_risk_points() );
							$marketplace_skipped_rule_labels[] = $rule->get_label();
						} else {
							$filtered_rules[] = $rule;
						}
					}
					$failed_rules = $filtered_rules;
				}

				// Apply the base score bonus (lower perceived risk for verified marketplace channels).
				if ( $score_bonus > 0 ) {
					$score_points = min( 100, $score_points + $score_bonus );
				}

				if ( ! empty( $marketplace_skipped_rule_labels ) ) {
					$order->add_order_note( sprintf(
					/* translators: 1: marketplace name, 2: comma-separated rule names */
					__( 'Marketplace profile (%1$s): rules skipped — %2$s', 'woocommerce-anti-fraud' ),
					WC_AF_Marketplace_Detector::get_source_label( $wc_af_marketplace_source ),
					implode( ', ', $marketplace_skipped_rule_labels )
					) );
				}

				// Persist the list of skipped rule labels for the meta box.
				opmc_hpos_update_post_meta( $order_id, '_wc_af_marketplace_skipped_rules', $marketplace_skipped_rule_labels );
			} else {
				opmc_hpos_delete_post_meta( $order_id, '_wc_af_marketplace_skipped_rules', '' );
			}
		// ─────────────────────────────────────────────────────────────────────

		// Save score to order
		opmc_hpos_update_post_meta( $order_id, 'wc_af_score', $score_points );

		// Rules in JSON
		$json_rules = array();
			if ( count( $failed_rules ) > 0 ) {
				foreach ( $failed_rules as $failed_rule ) {
					$json_rules[] = $failed_rule->to_json();
				}
			}

		// Save the failed rules as JSON
		opmc_hpos_update_post_meta( $order_id, 'wc_af_failed_rules', $json_rules );

			// Clear the pending flag from meta
			opmc_hpos_delete_post_meta( $order_id, '_wc_af_waiting', '' );

			// Get the order
			$order               = wc_get_order( $order_id );
			$current_user_id     = $order->get_user_id();
			$order_date          = $order->get_date_created();
			$order_date          = gmdate( 'Y-m-d', strtotime( $order_date ) );
			$currentDate         = gmdate( 'Y-m-d' );
			$userDetails         = $order->get_user();
			$userRole            = ( '' != $userDetails ) ? $userDetails->roles[0] : '';
			$blacklist_available = false;

		/*
		 * PERFORMANCE FIX: The historical WP_Query / card-decline lookup that previously ran
		 * here loaded customer order history on every single fraud check but its results were
		 * only consumed by a fully commented-out code block.  Executing a full meta_query
		 * against the orders table on every check — especially during bot attacks — created
		 * unnecessary DB load.  The dead query has been removed entirely.
		 */
			$is_enable_email_blacklist    = get_option( 'wc_settings_anti_fraudenable_automatic_email_blacklist' );
			$is_enable_ip_blacklist = get_option( 'wc_settings_anti_fraudenable_automatic_ip_blacklist' );

			if ( 'yes' == $is_enable_email_blacklist ) {

				$email_blacklist = get_option( 'wc_settings_anti_fraudblacklist_emails' );
				if ( '' != $email_blacklist ) {
					// String to array
					$blacklist = $this->parse_input_data($email_blacklist);
					// Check if is valid array
					if ( is_array( $blacklist ) && count( $blacklist ) > 0 ) {

						// Trim items to be sure
						foreach ( $blacklist as $k => $v ) {
							$blacklist[ $k ] = trim( $v );
						}

						// Set $blacklist_available true
						$blacklist_available = true;
					}
				}
			}

			$whitelist_available = false;

			// ✅ OPTIMIZED: Use cached version if available (transient cache)
			$cache_key = 'wc_af_whitelist_email_data';
			$email_whitelist = get_transient( $cache_key );
			
			if ( false === $email_whitelist ) {
				// Load whitelist data (with autoload=false, so it won't autoload)
				$email_whitelist = get_option( 'wc_settings_anti_fraud_whitelist', '' );
				// Cache for 1 hour
				if ( '' !== $email_whitelist ) {
					set_transient( $cache_key, $email_whitelist, HOUR_IN_SECONDS );
				}
			}

			if ( '' != $email_whitelist ) {
				// String to array

				$whitelist = $this->parse_input_data($email_whitelist);

				// Check if is valid array
				if ( is_array( $whitelist ) && count( $whitelist ) > 0 ) {

					// Trim items to be sure
					foreach ( $whitelist as $k => $v ) {
						$whitelist[ $k ] = trim( $v );
					}

					// Set $blacklist_available true
					$whitelist_available = true;
				}
			}

			// Default new status
			$new_status = null;

			// check payment method
			/*
			$payment_method = opmc_hpos_get_post_meta( $order_id, '_payment_method', true );

			if('paypal' == $payment_method || 'ppec_paypal' == $payment_method && null == opmc_hpos_get_post_meta($order_id,'wc_af_paypal_email_status')) {

				$paypal_verification = $this->paypal_email_verification($order,10);
			}*/

			// If a payment for this order has already completed, use the payment requested
			// status as the default
			$payment_requested_status = opmc_hpos_get_post_meta( $order_id, '_wc_af_post_payment_status', true );
			if ( ! empty( $payment_requested_status ) ) {
				$new_status = $payment_requested_status;
			}
			$ip_address     = $order->get_customer_ip_address();
			$is_whitelisted = true;

			$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles' );
			if ( empty( $wc_af_whitelist_user_roles ) ) {
				$wc_af_whitelist_user_roles = array();
			}
			$is_enable_whitelist_user_roles = get_option( 'wc_af_enable_whitelist_user_roles' );

			// ✅ REMOVED: Duplicate whitelist checks (now handled at the beginning of do_check())
			// If we reach here, the order is NOT whitelisted, so proceed with normal fraud processing
		
			$orderemail = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_email : $order->get_billing_email();
		
			Af_Logger::debug( 'Order not whitelisted - proceeding with fraud score evaluation.' );

			$cancel_score = get_option( 'wc_settings_anti_fraud_cancel_score' );
			$hold_score   = get_option( 'wc_settings_anti_fraud_hold_score' );

			// 0 in settings means to disable
			$cancel_score = 0 >= intval( $cancel_score ) ? 0 : self::invert_score( $cancel_score );
			$hold_score   = 0 >= intval( $hold_score ) ? 0 : self::invert_score( $hold_score );

			// Note: Payment method whitelist logic was already handled at the beginning
			// This section now only handles score-based status changes
		
		// Check for automated action rules based on score
			if ( $score_points <= $cancel_score && 0 !== $cancel_score ) {
				$new_status     = 'cancelled';
				$is_whitelisted = false;
				Af_Logger::debug( 'Status changed to ' . $new_status );
			} elseif ( $score_points <= $hold_score && 0 !== $hold_score ) {
				$new_status = 'on-hold';
				Af_Logger::debug( 'Status changed to ' . $new_status );
			}

		// ─── Marketplace action override ─────────────────────────────────────
		// If a marketplace profile is active and the score would trigger a cancel,
		// downgrade the action to "hold" (or "allow") to prevent false positives.
			if ( $wc_af_marketplace_profile && 'cancelled' === $new_status ) {
				$mp_action = isset( $wc_af_marketplace_profile['action_on_suspicion'] ) ? $wc_af_marketplace_profile['action_on_suspicion'] : 'hold';

				if ( 'hold' === $mp_action ) {
					$new_status = 'on-hold';
					$order->add_order_note( sprintf(
					/* translators: %s: marketplace name (e.g. eBay) */
					__( 'Order moved to On Hold (instead of Cancelled) — Marketplace profile active: %s.', 'woocommerce-anti-fraud' ),
					WC_AF_Marketplace_Detector::get_source_label( $wc_af_marketplace_source )
					) );
					Af_Logger::debug( 'Marketplace profile overrode cancel → on-hold for order #' . $order_id );
				} elseif ( 'allow' === $mp_action ) {
					$new_status = null;
					$order->add_order_note( sprintf(
					/* translators: %s: marketplace name (e.g. Amazon) */
					__( 'Cancellation suppressed — Marketplace profile active: %s. Order allowed to proceed.', 'woocommerce-anti-fraud' ),
					WC_AF_Marketplace_Detector::get_source_label( $wc_af_marketplace_source )
					) );
					Af_Logger::debug( 'Marketplace profile overrode cancel → allow for order #' . $order_id );
				}
				// 'notify' leaves $new_status as null (no status change) but the admin email is still sent below.
			}
		// ─────────────────────────────────────────────────────────────────────

		// Auto blacklist email with high risk
			$enable_auto_blacklist = get_option( 'wc_settings_anti_fraudenable_automatic_blacklist' );
			$is_enable_ip_blacklist = get_option( 'wc_settings_anti_fraudenable_automatic_ip_blacklist' );
			$automatic_ips_blacklist = get_option( 'wc_settings_anti_fraudenable_automatic_ips_blacklist' );

			$new_score_points = self::invert_score( $score_points );

			if ( 'yes' == $enable_auto_blacklist && $new_score_points > get_option( 'wc_settings_anti_fraud_higher_risk_threshold' ) ) {
				$existing_blacklist_emails = get_option( 'wc_settings_anti_fraudblacklist_emails', false );
				$auto_blacklist_emails = $this->parse_input_data($existing_blacklist_emails);

				if ( ! in_array( $orderemail, $auto_blacklist_emails ) ) {
					$existing_blacklist_emails .= ',' . $orderemail;

					$wildcard = get_option( 'wildcard_whitelist_email' );

					if ( isset( $wildcard ) && 'true' != $wildcard ) {
						update_option( 'wc_settings_anti_fraudblacklist_emails', $existing_blacklist_emails );
					}
				}
			}
			// Blacklist ip if enabled
			if ( 'yes' == $automatic_ips_blacklist && $new_score_points > get_option( 'wc_settings_anti_fraud_higher_risk_threshold' ) ) {
			
				Af_Logger::debug( 'IP Blacklist ' . $ip_address );
				$existing_blacklist_ip = get_option( 'wc_settings_anti_fraudblacklist_ipaddress', false );
				if ( '' != $existing_blacklist_ip ) {
					$auto_blacklist_ip = $this->parse_input_data($existing_blacklist_ip);

					if ( ! in_array( $ip_address, $auto_blacklist_ip ) ) {
						$existing_blacklist_ip .= ',' . $ip_address;
						update_option( 'wc_settings_anti_fraudblacklist_ipaddress', $existing_blacklist_ip );
					}
				} else {
					update_option( 'wc_settings_anti_fraudblacklist_ipaddress', $ip_address );
				}
			}

		$under_attack = class_exists( 'WooCommerce_Anti_Fraud' ) && WooCommerce_Anti_Fraud::is_under_bot_attack();
			if ( $under_attack ) {
				Af_Logger::debug( sprintf(
				'do_check #%d: bot-attack mode is active – velocity rule running with reduced query caps; risk cache will short-circuit repeat attackers.',
				$order_id
				) );
			}
		$max_order_per_ip = new WC_AF_Rule_Velocities();
		$is_max           = $max_order_per_ip->is_risk( $order );
			if ( 'yes' === get_option( 'wc_af_attempt_count_check' ) && true === $is_max && false === $is_whitelisted ) {
				$new_status = 'cancelled';
				$order->add_order_note( __( 'Max order limit reched from same IP.', 'woocommerce-anti-fraud' ) );
				Af_Logger::debug( 'Max Order Reched  and Order: ' . $new_status );
			}

			$order_limit_enabled     = get_option( 'wc_af_limit_order_count', 'no' );
			$is_update_status_active = get_option( 'wc_af_fraud_update_state' );

			Af_Logger::debug( 'Order Limit enabled :' . print_r( $order_limit_enabled, true ) );

			if ( 'yes' === $order_limit_enabled ) {

				$orders_allowed_limit = get_option( 'wc_af_allowed_order_limit' );
				$limit_time_start     = get_option( 'wc_af_limit_time_start' );
				$limit_time_end       = get_option( 'wc_af_limit_time_end' );
				$time_zone            = self::get_user_timezone();
				$start_time           = new \DateTime( $limit_time_start, $time_zone );
				$end_time             = new \DateTime( $limit_time_end, $time_zone );
				$now                  = new \DateTime( 'NOW', $time_zone );

				if ( ( $now >= $start_time ) && ( $now <= $end_time ) ) {
					/*
					 * PERFORMANCE FIX: Under bot attacks this block ran on every single order,
					 * triggering an unbounded SELECT across all orders in the time window.
					 * Cache the order count for 60 seconds per time-window bucket so that
					 * hundreds of concurrent requests reuse one result instead of each firing
					 * an independent full-table scan.
					 */
					$order_limit_cache_key = 'wc_af_order_limit_cnt_' . $start_time->getTimestamp() . '_' . $end_time->getTimestamp();
					$orders_between_count  = get_transient( $order_limit_cache_key );

					if ( false === $orders_between_count ) {
						$orders_between       = wc_get_orders(
						array(
							'limit'        => max( 2, (int) $orders_allowed_limit + 1 ),
							'return'       => 'ids',
							'type'         => wc_get_order_types( 'order-count' ),
							'date_created' => $start_time->getTimestamp() . '...' . $end_time->getTimestamp(),
						)
						);
						$orders_between_count = count( $orders_between );
						set_transient( $order_limit_cache_key, $orders_between_count, 60 );
					}

					if ( $orders_allowed_limit <= $orders_between_count ) {
						$new_status = 'cancelled';
						$order->add_order_note( __( 'Max Order Limit between time reached.', 'woocommerce-anti-fraud' ) );
						Af_Logger::debug( 'Max Order Limit between time reached: ' . $new_status );
					}
				}
			}

			/**
			 * Filter: 'wc_anti_fraud_new_order_status' - Allow altering new order status
			 *
			 * @api array $new_status The new status
			 * @api int      $order_id Order ID
			 * @api WC_Order $order    the Order
			 *
			 * @since 1.0.0
			 */
			$new_status = apply_filters( 'wc_anti_fraud_new_order_status', $new_status, $order_id, $order );
			Af_Logger::debug( 'wc_anti_fraud_new_order_status  ' . $new_status );

			// Possibly update the order status
			if ( $new_status ) {
				opmc_hpos_update_post_meta( $order_id, '_wc_af_recommended_status', $new_status );

				/**
				 * Filter: 'wc_anti_fraud_skip_order_statuses' - Skip status change for these statuses
				 *
				 * @api array $statuses List of statuses to skip changes for
				 *
				 * @since 1.0.0
				 */
				$skip_status_change = apply_filters( 'wc_anti_fraud_skip_order_statuses', array( 'cancelled' ) );

				Af_Logger::debug( 'is_update_status_active : ' . $is_update_status_active );
				Af_Logger::debug( 'payment_requested_status : ' . print_r( $payment_requested_status, true ) );

				if ( ! in_array( $order->get_status(), $skip_status_change ) && 'yes' == $is_update_status_active ) {
					if ( ! empty( $payment_requested_status ) ) {
						$order->update_status( $payment_requested_status, __( 'Fraud check done.', 'woocommerce-anti-fraud' ) );
					} else {
						$order->update_status( $new_status, __( 'Fraud check done.', 'woocommerce-anti-fraud' ) );
					}
				} else {
					$order->add_order_note( __( 'Fraud check done.', 'woocommerce-anti-fraud' ) );
				}
			} else {
				opmc_hpos_delete_post_meta( $order_id, '_wc_af_recommended_status', '' );
			}

		$email_score             = get_option( 'wc_settings_anti_fraud_email_score' );
		$score_points            = self::invert_score( $score_points );
		$email_nofication_status = get_option( 'wc_af_email_notification' );

		$sent_email = get_option( 'email_sent_' . $order_id );

		// ✅ FIX: Do NOT send fraud emails for whitelisted orders or 0% risk orders
		$whitelist_action = opmc_hpos_get_post_meta( $order_id, 'whitelist_action', true );
		$is_order_whitelisted = ! empty( $whitelist_action );

			if ( (string) $order_id != $sent_email ) {

				// Only send email if:
				// 1. Email notifications are enabled
				// 2. Score exceeds threshold
				// 3. Order is NOT whitelisted
				// 4. Score is NOT 0 or 100 (0% risk)
				if ( ( 'yes' == $email_nofication_status ) 
				&& ( $score_points > $email_score ) 
				&& ! $is_order_whitelisted 
				&& $score_points > 0 
				&& $score_points < 100 ) {

					// PLUGINS-195 Start
					if ( ! $this->check_email_rate_limit() ) {
						Af_Logger::debug( 'Fraud email not sent due to rate limiting for order #' . $order_id );
						// Optional: add an order note
						$order->add_order_note( __( 'Fraud alert email skipped because the rate limit was exceeded.', 'woocommerce-anti-fraud' ) );
					} else {
						// This is unfortunately needed in the current WooCommerce email setup
						if ( version_compare( WC_VERSION, '2.2.11', '<=' ) ) {
							include_once( WC()->plugin_path() . '/includes/abstracts/abstract-wc-email.php' );
						} else {
							include_once( WC()->plugin_path() . '/includes/emails/class-wc-email.php' );
						}

						$email = new WC_AF_Admin_Email( $order, $score_points );
						update_option( 'email_sent_' . $order_id, $order_id );
						// Send admin email
						$data = $email->send_notification();
					
						Af_Logger::debug( 'Fraud notification email sent for order #' . $order_id . ' with score: ' . $score_points );
					}
					// PLUGINS-195 END

				} else {
					if ( $is_order_whitelisted ) {
						Af_Logger::debug( 'Fraud email NOT sent - order is whitelisted.' );
					} elseif ( $score_points <= 0 || $score_points >= 100 ) {
						Af_Logger::debug( 'Fraud email NOT sent - order has 0% risk score.' );
					}
				}

			} else {

			Af_Logger::debug( 'Email already sent.' );
			delete_option( 'email_sent_' . $order_id );
			}


			// Check if we need to send an admin email notification
			if ( false == $is_whitelisted ) {

				$is_update_status_active = get_option( 'wc_af_fraud_update_state' );
				if ( 'yes' == $is_update_status_active ) {
					if ( ! empty( $payment_requested_status ) ) {
						$order->update_status( $payment_requested_status, __( 'Fraud check done.', 'woocommerce-anti-fraud' ) );
					} else {
						$order->update_status( $new_status, __( 'Fraud check done.', 'woocommerce-anti-fraud' ) );
					}
				} else {
					$order->add_order_note( __( 'Fraud check done.', 'woocommerce-anti-fraud' ) );
					// $order->update_status( 'cancelled', __( 'Fraud check done.', 'woocommerce-anti-fraud' ) );
				}
			}

		$circle_points    = self::invert_score( $score_points );
		$high_risk        = get_option( 'wc_settings_anti_fraud_higher_risk_threshold' );
		$peyment_retry    = opmc_hpos_get_post_meta( $order_id, 'peyment_retry', true );
		$whitelist_active = opmc_hpos_get_post_meta( $order_id, 'whitelist_action', true );
		$new_status       = 'failed';

			if ( $circle_points <= $high_risk ) {

				if ( 'yes' == $peyment_retry ) {
					// Never revert a whitelisted order back to failed – the whitelist must win.
					if ( empty( $whitelist_active ) ) {
						$order->update_status( $new_status, __( 'Fraud check done.', 'woocommerce-anti-fraud' ) );
					}
				}
			}

		}

		/**
		 * Function to get the ip address utilizing WC core
		 * geolocation when available.
		 *
		 * @since 1.0.7
		 * @version 1.0.7
		 */
		public static function get_ip_address() {
			if ( class_exists( 'WC_Geolocation' ) ) {
				return WC_Geolocation::get_ip_address();
			}

			return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '';
		}

		/**
		 * Return DateTimeZone for the visitor based on his ip address
		 *
		 * @param $ip_address
		 *
		 * @return DateTimeZone|null
		 */
		public static function get_user_timezone() {

			if ( ! class_exists( 'WC_Geolocation' ) ) {
				return new DateTimeZone( wp_timezone_string() );
			}

			$ip_address = self::get_ip_address();
			$geo_data   = WC_Geolocation::geolocate_ip( $ip_address );
			$timezone   = null;

			if ( isset( $geo_data['country'] ) && preg_match( '/^[A-Z]{2}$/', $geo_data['country'] ) ) {
				try {
					$timezone_identifier = DateTimeZone::listIdentifiers( DateTimeZone::PER_COUNTRY, $geo_data['country'] );
					if ( ! empty( $timezone_identifier ) ) {
						$timezone = new DateTimeZone( $timezone_identifier[0] );
					}
				} catch ( \Throwable $e ) {
					$timezone = new DateTimeZone( wp_timezone_string() );
				}
			}

			if ( ! $timezone ) {
				$timezone = new DateTimeZone( wp_timezone_string() );
			}

			return $timezone;
		}

		/**
		 * Function to send email if payment is done via papal
		 */
		public function paypal_email_verification( $order, $score_points ) {
			Af_Logger::debug( 'paypal_email_verification' );

			if ( is_numeric( $order ) ) {
				$order = wc_get_order( absint( $order ) );
			} elseif ( $order instanceof WC_Order ) {
				$order = wc_get_order( $order->get_id() );
			}

			if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
				Af_Logger::debug( 'paypal_email_verification: invalid or missing order, skipping.' );
				return;
			}

			// This is unfortunately needed in the current WooCommerce email setup
			if ( version_compare( WC_VERSION, '2.2.11', '<=' ) ) {
				include_once( WC()->plugin_path() . '/includes/abstracts/abstract-wc-email.php' );
			} else {
				include_once( WC()->plugin_path() . '/includes/emails/class-wc-email.php' );
			}

			$order_id = $order->get_id();

			$payment_method = opmc_hpos_get_post_meta( $order_id, '_payment_method', true );

			if ( 'ppec_paypal' == $payment_method ) {

				$orderemail = opmc_hpos_get_post_meta( $order_id, '_paypal_express_payer_email', true );

			} else {

				$orderemail = opmc_hpos_get_post_meta( $order_id, '_paypal_payer_email', true );

			}

			if ( get_option( 'wc_settings_anti_fraud_paypal_verified_address' ) && null != get_option( 'wc_settings_anti_fraud_paypal_verified_address' ) ) {

				$paypal_verified_emails = get_option( 'wc_settings_anti_fraud_paypal_verified_address' );

				$verified_paypal = $this->parse_input_data($paypal_verified_emails);

				if ( ! in_array( $orderemail, $verified_paypal ) ) {
					// Setup admin email
					$email = new WC_AF_Paypal_Email( $order, $score_points );

					// Send admin email
					$email_status = $email->send_notification();

					if ( ! opmc_hpos_get_post_meta( $order_id, 'wc_af_paypal_email_status', true ) ) {
						add_post_meta( $order_id, 'wc_af_paypal_email_status', false );
						$email_status = $email->send_notification();
					}

					/*if ( $email_status ) {
						opmc_hpos_update_post_meta( $order, 'wc_af_paypal_email_status', true );
						Af_Logger::debug( 'paypal_email_verification notification send to  ' . $orderemail );
					}*/
				}
			} else {
				// Setup admin email
				$email = new WC_AF_Paypal_Email( $order, $score_points );

				if ( ! opmc_hpos_get_post_meta( $order_id, 'wc_af_paypal_email_status', true ) ) {
					add_post_meta( $order_id, 'wc_af_paypal_email_status', false );
					$email_status = $email->send_notification();
				}


				/*if ( $email_status ) {
					update_post_meta( $order_id, 'wc_af_paypal_email_status', 1 );
					Af_Logger::debug( 'paypal_email_verification notification send to  ' . $orderemail );
				}*/


			}
		}

		/** PLUGINS-195 Start
		 * Check if sending an email is allowed based on rate limit settings.
		 *
		 * @return bool True if allowed, false if rate limited.
		 */
		private function check_email_rate_limit() {
			$enabled = get_option( 'wc_af_email_rate_limit_enable', 'no' );
			if ( 'yes' !== $enabled ) {
				return true; // Rate limiting disabled
			}

			$max    = (int) get_option( 'wc_af_email_rate_limit_max', 5 );
			$window = (int) get_option( 'wc_af_email_rate_limit_window', 30 ) * 60; // convert to seconds

			$log = get_option( 'wc_af_email_rate_limit_log', array() );
			if ( ! is_array( $log ) ) {
				$log = array();
			}

			$now = time();

			// Remove timestamps older than the window
			$log = array_filter( $log, function( $timestamp ) use ( $now, $window ) {
				return ( $now - $timestamp ) <= $window;
			} );

			// Count recent emails
			$count = count( $log );

			if ( $count < $max ) {
				// Allowed – add current timestamp and save
				$log[] = $now;
				update_option( 'wc_af_email_rate_limit_log', $log, false );
				return true;
			}

			// Rate limit exceeded
			return false;
		}
		// PLUGINS-195 END
	}
}
