<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MaxMind Advanced Signal Rule: Hosting / Datacenter IP Detection.
 *
 * Reads the `_af_mm_is_hosting` meta key stored by the MinFraud rule during
 * the same scoring pass. Requires MinFraud Insights or Factors tier.
 * Fraudsters frequently use cloud / datacenter IPs to mask their location.
 */
class WC_AF_Rule_MM_Hosting extends WC_AF_Rule {

	private $is_enabled  = false;
	private $rule_weight = 0;

	public function __construct() {
		$this->is_enabled  = get_option( 'wc_af_mm_hosting_enabled', 'no' );
		$this->rule_weight = get_option( 'wc_settings_anti_fraud_mm_hosting_weight', 40 );

		parent::__construct(
			'mm_hosting',
			__( 'MaxMind: customer IP belongs to a hosting / datacenter provider.', 'woocommerce-anti-fraud' ),
			$this->rule_weight
		);
	}

	/**
	 * Returns true when the customer IP is associated with a hosting provider.
	 *
	 * @param WC_Order $order
	 * @return bool
	 */
	public function is_risk( WC_Order $order ) {
		Af_Logger::debug( 'Checking mm_hosting rule' );
		$order_id = $order->get_id();
		$value    = opmc_hpos_get_post_meta( $order_id, '_af_mm_is_hosting', true );
		$risk     = ( 'yes' === $value );
		Af_Logger::debug( 'mm_hosting rule risk: ' . ( $risk ? 'true' : 'false' ) );
		return $risk;
	}

	public function is_enabled() {
		return 'yes' === $this->is_enabled;
	}
}
