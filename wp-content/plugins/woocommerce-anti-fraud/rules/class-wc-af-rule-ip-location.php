<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_AF_Rule_Ip_Location extends WC_AF_Rule {
	private $is_enabled  = false;
	private $rule_weight = 0;
	private $is_maxmind_auth = false;
	/**
	 * The constructor
	 */
	public function __construct() {
		$this->is_enabled  = get_option( 'wc_af_ip_geolocation_order' );
		$this->rule_weight = get_option( 'wc_settings_anti_fraud_ip_geolocation_order_weight' );
		$this->is_maxmind_auth = get_option( 'wc_af_maxmind_authentication' );

		parent::__construct( 'ip_location', __('Customer IP address did not match given billing country.', 'woocommerce-anti-fraud'), $this->rule_weight );
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
		Af_Logger::debug( 'Checking ip address rule' );
		global $wpdb;
		// Default risk is false
		$risk = false;

		if ( $this->is_maxmind_auth ) {

			// Set IP address in var
			$ip_address = $order->get_customer_ip_address();
			$billing_country = $order->get_billing_country();
			$billing_city = $order->get_billing_city();
			$billing_state = $order->get_billing_state();
			$billing_postcode = $order->get_billing_postcode();
			$shipping_country = $order->get_shipping_country();
			$shipping_city = $order->get_shipping_city();
			$shipping_state = $order->get_shipping_state();
			$shipping_postcode = $order->get_shipping_postcode();
			$maxmind_user = get_option( 'wc_af_maxmind_user' );
			$maxmind_license_key = get_option( 'wc_af_maxmind_license_key' );
			$authkey = 'Basic ' . base64_encode( $maxmind_user . ':' . $maxmind_license_key );

			$curl = curl_init();
			curl_setopt_array(
				$curl,
				array(
					CURLOPT_URL => 'https://geoip.maxmind.com/geoip/v2.1/insights/' . $ip_address,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_USERAGENT => 'AnTiFrAuDOPMC',
					CURLOPT_ENCODING => '',
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_HTTPHEADER => array(
						'authorization:' . $authkey,
						'cache-control: no-cache',
						'content-type: application/json',
					),
				)
			);

		$response = curl_exec( $curl );
		curl_close( $curl );
		$response = json_decode( $response, true );
		// Af_Logger::debug(print_r($response,true));
		
		$ip_city         = isset( $response['city']['names']['en'] ) ? $response['city']['names']['en'] : '';
		$ip_country_code = isset( $response['country']['iso_code'] ) ? $response['country']['iso_code'] : '';

		// Prefer iso_code for state subdivision (e.g. "CA") over the English full name
		// ("California") so that WC_AF_Geo_Matcher::normalize_state() always receives a
		// value it can directly key-match without a full-name lookup.
		$ip_state_iso  = isset( $response['subdivisions'][0]['iso_code'] )    ? $response['subdivisions'][0]['iso_code']    : '';
		$ip_state_name = isset( $response['subdivisions'][0]['names']['en'] )  ? $response['subdivisions'][0]['names']['en'] : '';
		$ip_state      = ! empty( $ip_state_iso ) ? $ip_state_iso : $ip_state_name;

		$ip_postal       = isset( $response['postal']['code'] ) ? $response['postal']['code'] : '';
		$accuracy_radius = isset( $response['location']['accuracy_radius'] ) ? $response['location']['accuracy_radius'] : 'Unknown';

		Af_Logger::debug( 'GeoIP raw response: ' . print_r( $response, true ) );
		Af_Logger::debug( 'ip city : ' . $ip_city );
		Af_Logger::debug( 'ip country : ' . $ip_country_code );
		Af_Logger::debug( 'ip state (iso=' . $ip_state_iso . ' name=' . $ip_state_name . ' resolved=' . $ip_state . ')' );
		
			if ( ! empty( $response ) && ! isset( $response['error'] ) ) {
				if ( ! empty( $ip_country_code ) ) {
					
					$geo_data = array(
						'country' => $ip_country_code,
						'state'   => $ip_state,
						'city'    => $ip_city,
						'postcode'=> $ip_postal,
					);
					
					$billing_data = array(
						'country' => $billing_country,
						'state'   => $billing_state,
						'city'    => $billing_city,
						'postcode'=> $billing_postcode,
					);
					
					$billing_eval = WC_AF_Geo_Matcher::is_match( $billing_data, $geo_data, true );
					
					$risk = $billing_eval['is_risk'];
					$evaluation = $billing_eval;
					$addr_type = 'billing';
					
					// If billing fails, give shipping a chance
					if ( $risk && ! empty( $shipping_country ) ) {
						$shipping_data = array(
							'country' => $shipping_country,
							'state'   => $shipping_state,
							'city'    => $shipping_city,
							'postcode'=> $shipping_postcode,
						);
						$shipping_eval = WC_AF_Geo_Matcher::is_match( $shipping_data, $geo_data, true );
						if ( ! $shipping_eval['is_risk'] ) {
							$risk = false;
							$evaluation = $shipping_eval;
							$addr_type = 'shipping';
						}
					}
					
					if ( ! $risk ) {
						Af_Logger::debug( 'Customer IP address matched given billing/shipping country' );
					} else {
						Af_Logger::debug( 'Customer IP address not matched given billing/shipping country' );
						
						if ( isset( $evaluation['data_evaluated'] ) ) {
							// Merge accuracy radius into geolocation data explanation
							$explanation_geo = array(
								'detected_city' => $ip_city,
								'detected_state' => $ip_state,
								'detected_country' => $ip_country_code,
								'detected_postcode' => $ip_postal,
								'accuracy_radius_km' => $accuracy_radius,
							);
							$this->add_explanation_item( 'data_evaluated', $evaluation['data_evaluated'] );
							$this->add_explanation_item( 'ip_geolocation', $explanation_geo );
						}
						
						if ( ! empty( $evaluation['reasons'] ) ) {
							$this->add_explanation_item( 'reason', implode( '. ', $evaluation['reasons'] ) );
						} else {
							$this->add_explanation_item( 'reason', 'IP address location does not match billing/shipping country' );
						}
						$this->add_explanation_item( 'alert_type', 'critical' );
					}
					
				} else {
					$risk = true;
					Af_Logger::debug( 'Customer IP address not matched given billing country ' . $billing_country . ' and city ' . $billing_city );
				
					$this->add_explanation_item( 'data_evaluated', array(
					'customer_ip' => $ip_address,
					'billing_country' => $billing_country,
					) );
				
					$this->add_explanation_item( 'reason', 'Could not determine country from IP address' );
					$this->add_explanation_item( 'alert_type', 'warning' );
				}
			} else {
				$risk = true;
				Af_Logger::debug( 'Customer IP address not matched given billing country ' . $billing_country . ' and city ' . $billing_city );
			
				$error_msg = isset( $response['error'] ) ? $response['error'] : 'API request failed';
			
				$this->add_explanation_item( 'data_evaluated', array(
				'customer_ip' => $ip_address,
				'billing_country' => $billing_country,
				) );
			
				$this->add_explanation_item( 'reason', 'IP geolocation lookup failed: ' . $error_msg );
				$this->add_explanation_item( 'alert_type', 'warning' );
			}
		} else {
			$risk = false;
			Af_Logger::debug( 'Maxmind creds not authenticated. IP Address rule is disabled.' );
		}
		Af_Logger::debug( 'ip address rule risk : ' . ( true === $risk ? 'true' : 'false' ) );
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
