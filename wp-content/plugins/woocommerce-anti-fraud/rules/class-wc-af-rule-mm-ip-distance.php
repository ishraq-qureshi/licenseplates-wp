<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MaxMind Advanced Signal Rule: IP Distance from Billing Address.
 *
 * Reads the `_af_mm_ip_distance` meta key (kilometres) stored by the MinFraud
 * rule during the same scoring pass. Requires MinFraud Insights or Factors
 * tier (the Score endpoint does not return location distance).
 *
 * Risk tiers (configurable):
 *   < medium_km   → safe   (no risk)
 *   medium_km–high_km → medium (rule triggers only when high threshold chosen)
 *   ≥ high_km     → high risk (rule triggers)
 *
 * The rule fires when the stored distance is ≥ the configured high threshold.
 * Admins can tune both thresholds and the rule weight in the settings.
 */
class WC_AF_Rule_MM_IP_Distance extends WC_AF_Rule {

	private $is_enabled    = false;
	private $rule_weight   = 0;
	private $high_km       = 500;

	public function __construct() {
		$this->is_enabled  = get_option( 'wc_af_mm_distance_enabled', 'no' );
		$this->rule_weight = get_option( 'wc_settings_anti_fraud_mm_distance_weight', 30 );
		$this->high_km     = intval( get_option( 'wc_settings_anti_fraud_mm_distance_high_km', 500 ) );

		parent::__construct(
			'mm_ip_distance',
			__( 'MaxMind: IP location is far from the billing address.', 'woocommerce-anti-fraud' ),
			$this->rule_weight
		);
	}

	/**
	 * Returns true when the distance between customer IP and billing address
	 * exceeds the configured high-risk threshold (kilometres).
	 *
	 * @param WC_Order $order
	 * @return bool
	 */
	public function is_risk( WC_Order $order ) {
		Af_Logger::debug( 'Checking mm_ip_distance rule' );
		$order_id = $order->get_id();
		$distance = opmc_hpos_get_post_meta( $order_id, '_af_mm_ip_distance', true );

		if ( '' === $distance || false === $distance || null === $distance ) {
			// Distance data not available (Score endpoint or not returned by API)
			return false;
		}

		$distance = intval( $distance );
		$risk     = ( $distance >= $this->high_km );
		Af_Logger::debug( 'mm_ip_distance rule: distance=' . $distance . 'km threshold=' . $this->high_km . 'km risk=' . ( $risk ? 'true' : 'false' ) );
		return $risk;
	}

	public function is_enabled() {
		return 'yes' === $this->is_enabled;
	}
}
