<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_AF_Rule_Billing_Phone_Matches_Billing_Country' ) ) {

	class WC_AF_Rule_Billing_Phone_Matches_Billing_Country extends WC_AF_Rule {

		private $is_enabled  = false;
		private $rule_weight = 0;

		public function __construct() {
			$this->is_enabled  = get_option( 'wc_af_billing_phone_number_order' );
			$this->rule_weight = get_option( 'wc_settings_anti_fraud_billing_phone_number_order_weight' );

			parent::__construct(
				'Billing_Phone_Matches_Billing_Country',
				__( 'Billing phone number does not match with Billing country', 'woocommerce-anti-fraud' ),
				$this->rule_weight
			);
		}

		/**
		 * Check rule risk.
		 *
		 * @param WC_Order $order Order object.
		 * @return bool
		 */
		public function is_risk( WC_Order $order ) {

			Af_Logger::debug( 'Checking billing phone matches billing country rule' );

			$risk = false;

			$store_country   = WC()->countries->get_base_country();
			$billing_country = $order->get_billing_country();
			$billing_phone   = $order->get_billing_phone();

			if ( empty( $billing_phone ) ) {
				Af_Logger::debug( 'No phone number provided, skipping check' );
				return false;
			}

			$num     = trim( $billing_phone );
			$new_num = str_replace( ' ', '', $num );
			$phone   = preg_replace( '/[^A-Za-z0-9\-]/', '', $new_num );

			$has_country_code = ( strpos( trim( $billing_phone ), '+' ) === 0 );
			$is_us_billing    = ( 'US' === $billing_country );

			if ( ! $has_country_code && $is_us_billing ) {

				Af_Logger::debug( 'US billing detected, assuming +1' );

				$billing_phone_code    = '+1';
				$detected_country_name = 'US';
				$country_detected      = true;

			} else {

				if ( ! $has_country_code && $billing_country === $store_country ) {
					Af_Logger::debug( 'Domestic phone without code - safe' );
					return false;
				}

				$phones   = array( $phone );
				$country  = array();

				require_once plugin_dir_path( __FILE__ ) . 'country-code-list.php';

				$ccodes = countrycodelist( $countryArray );

				krsort( $ccodes );

				$detected_country_name = '';
				$country_detected      = false;

				foreach ( $phones as $pn ) {
					foreach ( $ccodes as $key => $value ) {
						if ( substr( $pn, 0, strlen( $key ) ) === $key ) {
							$country[ $pn ]        = $value;
							$detected_country_name = $value;
							$country_detected      = true;
							break;
						}
					}
				}

				if ( ! $country_detected && strlen( $phone ) < 15 ) {
					Af_Logger::debug( 'Short number, likely domestic' );
					return false;
				}

				if ( $country_detected ) {
					$billing_phone_code = array_search( $detected_country_name, $ccodes, true );
				} else {
					$billing_phone_code = '';
				}

				if ( $billing_phone_code ) {
					$billing_phone_code = '+' . $billing_phone_code;
				} else {
					$billing_phone_code = '';
				}
			}

			$calling_code = '';

			if ( $billing_country ) {
				$calling_code = WC()->countries->get_country_calling_code( $billing_country );
				if ( is_array( $calling_code ) ) {
					$calling_code = $calling_code[0];
				}
			}

			$countries = WC()->countries->countries;

			if ( isset( $countries[ $billing_country ] ) ) {
				$billing_country_name = $countries[ $billing_country ];
			} else {
				$billing_country_name = $billing_country;
			}

			$billing_state    = $order->get_billing_state();
			$billing_city     = $order->get_billing_city();
			$billing_postcode = $order->get_billing_postcode();

			$phone_area_code            = '';
			$area_code_matches_location = false;

			if ( 'US' === $billing_country || 'CA' === $billing_country ) {

				$phone_digits = preg_replace( '/[^0-9]/', '', $phone );

				if ( strlen( $phone_digits ) >= 10 ) {

					if ( strlen( $phone_digits ) > 10 && substr( $phone_digits, 0, 1 ) === '1' ) {
						$phone_area_code = substr( $phone_digits, 1, 3 );
					} else {
						$phone_area_code = substr( $phone_digits, 0, 3 );
					}
				}
			}

			if ( $calling_code === $billing_phone_code || empty( $billing_phone_code ) ) {

				$risk = false;

			} else {

				$risk = true;

				$mismatches = array();

				if ( $calling_code !== $billing_phone_code ) {
					$mismatches[] = sprintf(
						'Phone country code does not match billing country: Phone "%s" vs Billing "%s"',
						$billing_phone_code ? $billing_phone_code : 'No code detected',
						$calling_code ? $calling_code : 'Unknown'
					);
				}

				$reason_parts = array();

				if ( ! empty( $mismatches ) ) {
					$reason_parts[] = implode( '; ', $mismatches );
				}

				if ( ! empty( $phone_area_code ) ) {
					$reason_parts[] = sprintf(
						'Phone area code: %s (verify location: %s, %s)',
						$phone_area_code,
						$billing_city,
						$billing_state
					);
				}

				if ( ! empty( $reason_parts ) ) {
					$reason_text = implode( '. ', $reason_parts );
				} else {
					$reason_text = sprintf(
						'Phone number appears to be from %s but billing country is %s',
						$detected_country_name ? $detected_country_name : 'another country',
						$billing_country_name
					);
				}

				$data_evaluated = array(
					'billing_phone'   => $billing_phone,
					'billing_country' => $billing_country_name,
					'billing_city'    => $billing_city,
					'billing_state'   => $billing_state,
					'billing_postcode'=> $billing_postcode,
				);

				if ( ! empty( $phone_area_code ) ) {
					$data_evaluated['phone_area_code'] = $phone_area_code;
				}

				$this->add_explanation_item( 'data_evaluated', $data_evaluated );

				$this->add_explanation_item( 'reason', $reason_text );
				$this->add_explanation_item( 'alert_type', 'warning' );
			}

			Af_Logger::debug(
				'billing phone matches billing country rule risk : ' .
				( true === $risk ? 'true' : 'false' )
			);

			return $risk;
		}

		public function is_enabled() {
			return ( 'yes' === $this->is_enabled );
		}
	}
}
