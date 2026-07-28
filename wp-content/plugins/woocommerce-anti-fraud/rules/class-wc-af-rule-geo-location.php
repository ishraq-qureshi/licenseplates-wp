<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_AF_Rule_Geo_Location extends WC_AF_Rule {
	// exit();
	private $is_enabled  = false;
	private $rule_weight = 0;

	/**
	 * The constructor
	 */
	public function __construct() {
		$this->is_enabled  = get_option( 'wc_af_geolocation_order' );
		$this->rule_weight = get_option( 'wc_settings_anti_fraud_geolocation_order_weight' );

		parent::__construct( 'geo_location', __('Customer order geo location mismatches billing/shipping address.', 'woocommerce-anti-fraud'), $this->rule_weight );
	}

	/**
	 * Do the required check in this method. The method must return a boolean.
	 *
	 * @param WC_Order $order
	 *
	 * @since  1.0.0
	 *
	 * @return bool
	 */
	public function is_risk( WC_Order $order ) {

		Af_Logger::debug( 'Checking Geo address rule' );

		// Default risk is false
		$risk = false;

		if ( $this->is_enabled ) {
			
			$order_id = $order->get_id();

			// JIT Persistence: Check if order meta exists. If not, pull from session and save.
			$geo_country = opmc_hpos_get_post_meta( $order_id, '_wc_af_browser_geo_country', true );
			$geo_state   = opmc_hpos_get_post_meta( $order_id, '_wc_af_browser_geo_state', true );
			$geo_city    = opmc_hpos_get_post_meta( $order_id, '_wc_af_browser_geo_city', true );
			$geo_postcode= opmc_hpos_get_post_meta( $order_id, '_wc_af_browser_geo_postcode', true );

			// If meta does not exist, try to populate from session JIT
			if ( empty( $geo_country ) && empty( $geo_state ) && empty( $geo_city ) && empty( $geo_postcode ) ) {
				if ( isset( WC()->session ) ) {
					$session_data = WC()->session->get( 'wc_af_browser_geo_data' );
					if ( ! empty( $session_data ) && is_array( $session_data ) ) {
						$geo_country  = isset( $session_data['country'] ) ? $session_data['country'] : '';
						$geo_state    = isset( $session_data['state'] ) ? $session_data['state'] : '';
						$geo_city     = isset( $session_data['city'] ) ? $session_data['city'] : '';
						$geo_postcode = isset( $session_data['postcode'] ) ? $session_data['postcode'] : '';

						// Persist to Order Meta for history
						opmc_hpos_update_post_meta( $order_id, '_wc_af_browser_geo_country', $geo_country );
						opmc_hpos_update_post_meta( $order_id, '_wc_af_browser_geo_state', $geo_state );
						opmc_hpos_update_post_meta( $order_id, '_wc_af_browser_geo_city', $geo_city );
						opmc_hpos_update_post_meta( $order_id, '_wc_af_browser_geo_postcode', $geo_postcode );
					}
				}
			}

			// If no geolocation data is available at all, avoid false positive
			$has_geo_data = ! empty( $geo_city ) || ! empty( $geo_state ) || ! empty( $geo_country ) || ! empty( $geo_postcode );

			if ( ! $has_geo_data ) {
				Af_Logger::debug( 'Geo Location rule: no geolocation data available (browser access denied, API unavailable, or session missing) – skipping rule to avoid false positive' );
				return false;
			}

			// Determine which address to use
			if ( $order->get_billing_address_1() != $order->get_shipping_address_1() ) {
				$customer_city = $order->get_billing_city();
				$customer_state = $order->get_billing_state();
				$customer_country = $order->get_billing_country();
				$customer_postcode = $order->get_billing_postcode();
			} else {
				$customer_city = $order->get_shipping_city();
				$customer_state = $order->get_shipping_state();
				$customer_country = $order->get_shipping_country();
				$customer_postcode = $order->get_shipping_postcode();
			}

			$order_data = array(
				'country'  => $customer_country,
				'state'    => $customer_state,
				'city'     => $customer_city,
				'postcode' => $customer_postcode,
			);

			$geo_data = array(
				'country'  => $geo_country,
				'state'    => $geo_state,
				'city'     => $geo_city,
				'postcode' => $geo_postcode,
			);

			// Use the WC_AF_Geo_Matcher utility to evaluate strict pass/fail with normalization
			$evaluation = WC_AF_Geo_Matcher::is_match( $order_data, $geo_data, false );
			
			$risk = $evaluation['is_risk'];

			if ( $risk ) {
				Af_Logger::debug( 'Geo Location does not match billing/shipping address explicitly.' );
				
				if ( isset( $evaluation['data_evaluated'] ) ) {
					$this->add_explanation_item( 'data_evaluated', $evaluation['data_evaluated'] );
				}

				if ( ! empty( $evaluation['reasons'] ) ) {
					$this->add_explanation_item( 'reason', implode( '. ', $evaluation['reasons'] ) );
				} else {
					$this->add_explanation_item( 'reason', 'Geographic location does not match billing/shipping address' );
				}
				
				$this->add_explanation_item( 'alert_type', 'warning' );

			} else {
				Af_Logger::debug( 'Geo Location matches implicitly or strictly.' );
			}
			
		} else {
			$risk = false;
			Af_Logger::debug( 'Geo Location rule is disabled.' );
		}
		Af_Logger::debug( 'Geo Location rule risk : ' . ( true === $risk ? 'true' : 'false' ) );
		return $risk;

	}

	// Enable rule check
	public function is_enabled() {
		if ( 'yes' == $this->is_enabled ) {
			return true;
		}
		return false;
	}
}
