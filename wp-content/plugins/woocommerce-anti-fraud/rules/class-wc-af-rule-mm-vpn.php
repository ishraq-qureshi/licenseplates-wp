<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MaxMind Advanced Signal Rule: Anonymous VPN Detection.
 *
 * Reads the `_af_mm_is_vpn` meta key stored by the MinFraud rule during the
 * same scoring pass. Requires MinFraud Insights or Factors tier (the Score
 * endpoint does not return IP traits; the value will simply stay "no").
 */
class WC_AF_Rule_MM_VPN extends WC_AF_Rule {

	private $is_enabled  = false;
	private $rule_weight = 0;

	public function __construct() {
		$this->is_enabled  = get_option( 'wc_af_mm_vpn_enabled', 'no' );
		$this->rule_weight = get_option( 'wc_settings_anti_fraud_mm_vpn_weight', 30 );

		parent::__construct(
			'mm_vpn',
			__( 'MaxMind: customer IP is identified as an Anonymous VPN.', 'woocommerce-anti-fraud' ),
			$this->rule_weight
		);
	}

	/**
	 * Returns true when the customer IP is a known anonymous VPN.
	 *
	 * @param WC_Order $order
	 * @return bool
	 */
	public function is_risk( WC_Order $order ) {
		Af_Logger::debug( 'Checking mm_vpn rule' );
		$order_id = $order->get_id();
		$value    = opmc_hpos_get_post_meta( $order_id, '_af_mm_is_vpn', true );
		$risk     = ( 'yes' === $value );
		Af_Logger::debug( 'mm_vpn rule risk: ' . ( $risk ? 'true' : 'false' ) );
		return $risk;
	}

	public function is_enabled() {
		return 'yes' === $this->is_enabled;
	}
}
