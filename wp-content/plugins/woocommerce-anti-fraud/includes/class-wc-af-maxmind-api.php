<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Utility class to interact with MaxMind MinFraud API safely and consistently.
 */
class WC_AF_MaxMind_API_Client {

	private static $api_url_base = 'https://minfraud.maxmind.com/minfraud/v2.0/';

	/**
	 * Perform the MinFraud API check.
	 *
	 * @param WC_Order $order
	 * @param string   $endpoint (score, insights, factors)
	 *
	 * @return array {
	 *     @type float $risk_score
	 *     @type string $content (legacy string output for meta tracking)
	 *     @type array $advanced_signals (array of VPN/proxy/etc traits)
	 *     @type string $error Message if failed
	 * }
	 */
	public static function check_order( $order, $endpoint = 'score' ) {
		$maxmind_user = get_option( 'wc_af_maxmind_user' );
		$maxmind_key  = get_option( 'wc_af_maxmind_license_key' );

		if ( empty( $maxmind_user ) || empty( $maxmind_key ) ) {
			return array( 'error' => 'MaxMind credentials not configured.' );
		}

		$authkey = 'Basic ' . base64_encode( $maxmind_user . ':' . $maxmind_key );
		
		$ip_address = $order->get_customer_ip_address();
		$user_agent = $order->get_customer_user_agent();
		
		if ( empty( $ip_address ) ) {
			return array( 'error' => 'No customer IP address available for MaxMind check.' );
		}

		$payload = self::build_payload( $order, $ip_address, $user_agent );

		$url = self::$api_url_base . $endpoint;

		$response = wp_remote_post(
			$url,
			array(
				'method'      => 'POST',
				'timeout'     => 30,
				'redirection' => 5,
				'httpversion' => '1.1',
				'blocking'    => true,
				'headers'     => array(
					'authorization' => $authkey,
					'content-type'  => 'application/json',
					'user-agent'    => 'AnTiFrAuDOPMC',
				),
				'body'        => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( 200 !== $status_code ) {
			$error_code = isset( $data['code'] ) ? $data['code'] : 'API_ERROR';
			$error_msg  = isset( $data['error'] ) ? $data['error'] : 'Unknown error occurred from MaxMind';
			return array( 'error' => $error_code . ': ' . $error_msg );
		}

		if ( empty( $data ) ) {
			return array( 'error' => 'Empty response from MaxMind' );
		}

		// Extract risk score
		$risk_score = isset( $data['risk_score'] ) ? floatval( $data['risk_score'] ) : 0.00;

		// Extract content / warnings (legacy format for meta box)
		$content = array();
		if ( isset( $data['warnings'] ) && is_array( $data['warnings'] ) ) {
			foreach ( $data['warnings'] as $warning ) {
				if ( isset( $warning['warning'] ) ) {
					$content[][] = $warning['warning'];
				}
			}
		}

		// Extract Advanced Signals from ip_address object (Insights/Factors tier)
		$ip = isset( $data['ip_address'] ) ? $data['ip_address'] : array();
		$advanced_signals = array(
			'is_anonymous'            => isset( $ip['traits']['is_anonymous'] ) ? (bool) $ip['traits']['is_anonymous'] : false,
			'is_anonymous_vpn'        => isset( $ip['traits']['is_anonymous_vpn'] ) ? (bool) $ip['traits']['is_anonymous_vpn'] : false,
			'is_public_proxy'         => isset( $ip['traits']['is_public_proxy'] ) ? (bool) $ip['traits']['is_public_proxy'] : false,
			'is_tor_exit_node'        => isset( $ip['traits']['is_tor_exit_node'] ) ? (bool) $ip['traits']['is_tor_exit_node'] : false,
			'is_hosting_provider'     => isset( $ip['traits']['is_hosting_provider'] ) ? (bool) $ip['traits']['is_hosting_provider'] : false,
			'network'                 => isset( $ip['traits']['network'] ) ? $ip['traits']['network'] : '',
			'distance_to_ip_location' => isset( $data['billing_address']['distance_to_ip_location'] ) ? intval( $data['billing_address']['distance_to_ip_location'] ) : null,
		);

		return array(
			'risk_score'       => $risk_score,
			'content'          => $content,
			'advanced_signals' => $advanced_signals,
		);
	}

	private static function build_payload( $order, $ip_address, $user_agent ) {
		$billing_address  = $order->get_billing_address_1();
		$billing_city     = $order->get_billing_city();
		$billing_state    = $order->get_billing_state();
		$billing_country  = $order->get_billing_country();
		$billing_postcode = $order->get_billing_postcode();
		$billing_phone    = $order->get_billing_phone();

		$shipping_address  = $order->get_shipping_address_1();
		$shipping_city     = $order->get_shipping_city();
		$shipping_state    = $order->get_shipping_state();
		$shipping_country  = $order->get_shipping_country();
		$shipping_postcode = $order->get_shipping_postcode();
		$shipping_phone    = $order->get_shipping_phone();

		$payload = array();
		
		$payload['device'] = array(
			'ip_address'      => $ip_address,
			'user_agent'      => $user_agent,
			'accept_language' => 'en-US,en;q=0.8',
		);

		$payload['billing'] = array(
			'first_name' => strval( $order->get_billing_first_name() ),
			'last_name'  => strval( $order->get_billing_last_name() ),
			'company'    => strval( $order->get_billing_company() ),
			'address'    => strval( $billing_address ),
			'address_2'  => strval( $order->get_billing_address_2() ),
			'city'       => strval( $billing_city ),
			'region'     => strval( $billing_state ),
			'country'    => strval( $billing_country ),
			'postal'     => strval( $billing_postcode ),
			'phone_number' => strval( $billing_phone ),
		);

		if ( ! empty( $shipping_address ) ) {
			$payload['shipping'] = array(
				'first_name' => strval( $order->get_shipping_first_name() ),
				'last_name'  => strval( $order->get_shipping_last_name() ),
				'company'    => strval( $order->get_shipping_company() ),
				'address'    => strval( $shipping_address ),
				'address_2'  => strval( $order->get_shipping_address_2() ),
				'city'       => strval( $shipping_city ),
				'region'     => strval( $shipping_state ),
				'country'    => strval( $shipping_country ),
				'postal'     => strval( $shipping_postcode ),
				'phone_number' => strval( $shipping_phone ),
			);
		}

		$payload['account'] = array(
			'user_id' => strval( $order->get_user_id() ),
		);

		$payload['email'] = array(
			'address' => strval( $order->get_billing_email() ),
			'domain'  => strval( substr( strrchr( $order->get_billing_email(), '@' ), 1 ) ),
		);

		$payload['order'] = array(
			'amount'        => floatval( $order->get_total() ),
			'currency'      => strval( $order->get_currency() ),
			'discount_code' => '', // Could be multiple, skipping for basics like legacy
		);

		return $payload;
	}
}
