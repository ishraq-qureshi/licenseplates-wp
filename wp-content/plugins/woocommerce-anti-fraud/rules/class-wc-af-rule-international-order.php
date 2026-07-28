<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_AF_Rule_International_Order extends WC_AF_Rule {
	private $is_enabled  = false;
	private $rule_weight = 0;
	/**
	 * The constructor
	 */
	public function __construct() {
		$this->is_enabled  = get_option( 'wc_af_international_order' );
		$this->rule_weight = get_option( 'wc_settings_anti_fraud_international_order_weight' );

		parent::__construct( 'international_order', __('Order is an international order.', 'woocommerce-anti-fraud'), $this->rule_weight );
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

		Af_Logger::debug( 'Checking international order rule' );
		// Default risk is false
		$risk = false;

		// Get store country
		$store_country = WC()->countries->get_base_country();

		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$billing_country = $order->billing_country;
			$shipping_country = $order->shipping_country;
		} else {
			$billing_country = $order->get_billing_country();
			$shipping_country = $order->get_shipping_country();
		}

	// Get country names
	$countries = WC()->countries->get_countries();
	$store_country_name = isset( $countries[ $store_country ] ) ? $countries[ $store_country ] : $store_country;
	$billing_country_name = isset( $countries[ $billing_country ] ) ? $countries[ $billing_country ] : $billing_country;
	$shipping_country_name = isset( $countries[ $shipping_country ] ) ? $countries[ $shipping_country ] : $shipping_country;

	// Check if store country differs from billing or shipping country
		if ( ( $store_country != $billing_country && ! empty( $billing_country ) ) || ( $store_country != $shipping_country && ! empty( $shipping_country ) ) ) {
			$risk = true;
		
			// ✅ Add detailed explanation
			$different_countries = array();
			if ( $store_country != $billing_country && ! empty( $billing_country ) ) {
				$different_countries[] = 'Billing: ' . $billing_country_name;
			}
			if ( $store_country != $shipping_country && ! empty( $shipping_country ) && $shipping_country != $billing_country ) {
				$different_countries[] = 'Shipping: ' . $shipping_country_name;
			}
		
			$this->add_explanation_item( 'data_evaluated', array(
			'store_country' => $store_country_name,
			'billing_country' => $billing_country_name,
			'shipping_country' => ! empty( $shipping_country ) ? $shipping_country_name : 'Same as billing',
			) );
		
			$this->add_explanation_item( 'reason', sprintf(
				'Order from %s, store is in %s',
				implode( ' and ', $different_countries ),
				$store_country_name
			) );
		
			$this->add_explanation_item( 'alert_type', 'info' );
		}

	$us_territories = array('UM', 'AS', 'GU', 'MP', 'PR', 'VI');
		if ('US' === $store_country && in_array($billing_country, $us_territories) && in_array($shipping_country, $us_territories)) {

			$risk = false;
		}

	Af_Logger::debug( 'international order rule risk : ' . ( true === $risk ? 'true' : 'false' ) );
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
