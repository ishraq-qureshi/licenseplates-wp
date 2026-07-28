<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MaxMind Advanced Signal Rule: TOR Exit Node Detection.
 *
 * Reads the `_af_mm_is_tor` meta key stored by the MinFraud rule during the
 * same scoring pass. Requires MinFraud Insights or Factors tier.
 */
class WC_AF_Rule_MM_Tor extends WC_AF_Rule {

	private $is_enabled  = false;
	private $rule_weight = 0;

	public function __construct() {
		$this->is_enabled  = get_option( 'wc_af_mm_tor_enabled', 'no' );
		$this->rule_weight = get_option( 'wc_settings_anti_fraud_mm_tor_weight', 80 );

		parent::__construct(
			'mm_tor',
			__( 'MaxMind: customer IP is a TOR exit node.', 'woocommerce-anti-fraud' ),
			$this->rule_weight
		);
	}

	/**
	 * Returns true when the customer IP is a TOR exit node.
	 *
	 * @param WC_Order $order
	 * @return bool
	 */
	public function is_risk( WC_Order $order ) {
		Af_Logger::debug( 'Checking mm_tor rule' );
		$order_id = $order->get_id();
		$value    = opmc_hpos_get_post_meta( $order_id, '_af_mm_is_tor', true );
		$risk     = ( 'yes' === $value );
		Af_Logger::debug( 'mm_tor rule risk: ' . ( $risk ? 'true' : 'false' ) );
		return $risk;
	}

	public function is_enabled() {
		return 'yes' === $this->is_enabled;
	}
}
