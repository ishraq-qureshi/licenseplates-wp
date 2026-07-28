<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_AF_Rule_MinFraud extends WC_AF_Rule {
	private $is_enabled  = false;
	private $rule_weight = 0;
	private $minimum_minfraud_score;

	
	/**
	 * The constructor
	 */
	public function __construct() {
		$this->minimum_minfraud_score = get_option( 'wc_settings_anti_fraud_minfraud_risk_score' );
		$this->is_enabled             = get_option( 'wc_af_maxmind_type' );
		$this->rule_weight            = get_option( 'wc_settings_anti_fraud_minfraud_order_weight' );

		// Safe: translation loads after init
		$message = __( 'Score returned by Minfraud exceeds the allowed value.', 'woocommerce-anti-fraud' );

		parent::__construct( 'minfraud', $message, $this->rule_weight );
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
		Af_Logger::debug( 'Checking minfraud rule' );
		global $wpdb;
		$order_id = $order->get_id();
		$minfraud_score = $this->call_maxmind_api_on_order_place( $order );
		opmc_hpos_update_post_meta( $order_id, '_wc_af_maxmind_score', $minfraud_score );
		// Default risk is false
		$risk = false;

		if ( $this->minimum_minfraud_score < $minfraud_score ) {

			$risk = true;
		}
		Af_Logger::debug( 'minfraud rule risk : ' . ( true === $risk ? 'true' : 'false' ) );
		return $risk;
	}

	public function call_maxmind_api_on_order_place( $order ) {
		$order = wc_get_order( $order ); // getting order Object
		if ( false === $order ) {
			return false;
		}

		$order_id = $order->get_id();
		
		$result = WC_AF_MaxMind_API_Client::check_order( $order, 'score' );
		
		if ( isset( $result['error'] ) ) {
			Af_Logger::debug( 'minfraud error: ' . $result['error'] );
			return 0; // Return safe default score if API fails
		}

		// Legacy storage for the warnings format
		if ( ! empty( $result['content'] ) ) {
			opmc_hpos_update_post_meta( $order_id, '_wc_af_maxmind_content', $result['content'] );
		}

		// Save advanced signals as a JSON blob for the meta box
		if ( ! empty( $result['advanced_signals'] ) ) {
			$signals = $result['advanced_signals'];
			opmc_hpos_update_post_meta( $order_id, '_wc_af_maxmind_advanced_signals', wp_json_encode( $signals ) );

			// Store individual signal meta keys for rule access and order debugging
			opmc_hpos_update_post_meta( $order_id, '_af_mm_risk_score', $result['risk_score'] );
			opmc_hpos_update_post_meta( $order_id, '_af_mm_is_vpn', $signals['is_anonymous_vpn'] ? 'yes' : 'no' );
			opmc_hpos_update_post_meta( $order_id, '_af_mm_is_proxy', $signals['is_public_proxy'] ? 'yes' : 'no' );
			opmc_hpos_update_post_meta( $order_id, '_af_mm_is_tor', $signals['is_tor_exit_node'] ? 'yes' : 'no' );
			opmc_hpos_update_post_meta( $order_id, '_af_mm_is_hosting', $signals['is_hosting_provider'] ? 'yes' : 'no' );
			opmc_hpos_update_post_meta( $order_id, '_af_mm_ip_distance', null !== $signals['distance_to_ip_location'] ? intval( $signals['distance_to_ip_location'] ) : '' );
		}

		Af_Logger::debug( 'minfraud score ' . $result['risk_score'] );
		return $result['risk_score'];
	}


	// Enable rule check
	public function is_enabled() {
		if ( 'yes' == $this->is_enabled ) {
			return true;
		}
		return false;
	}
}
