<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'WC_AF_AI_FRAUD_INVENSTION' ) ) {

	class WC_AF_AI_FRAUD_INVENSTION {

		public function __construct() {
			add_action( 'woocommerce_thankyou', array( $this, 'process_ai_fraud_invenston' ), 10, 1 );
		}

		public function process_ai_fraud_invenston( $order_id) {

			$apiEndPoint = 'https://api.openai.com/v1/chat/completions';
			$apiKey = get_option('wc_af_chatgpt_api_key');
			$chatgptVersion = get_option('wc_af_ai_model');
			$enableSetting = get_option('wc_af_ai_fraud_prevention_check');

			if (isset($enableSetting) && 'yes' === $enableSetting) {

				if (empty($apiKey) || empty($chatgptVersion)) {
					return;
				}

				if (opmc_hpos_get_post_meta($order_id, '_ai_risk_score') !== null && !empty(opmc_hpos_get_post_meta($order_id, '_ai_risk_score'))) {
					return false;
				}
				$order = wc_get_order($order_id);
				$post_data = $this->create_body($order, $chatgptVersion);

				$curl = curl_init();

				curl_setopt_array($curl, array(
					CURLOPT_URL => $apiEndPoint,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => 'POST',
					CURLOPT_POSTFIELDS => $post_data,
					CURLOPT_HTTPHEADER => array(
						'Authorization: Bearer ' . $apiKey,
						'Content-Type: application/json',
					),
				));


				$response = curl_exec($curl);
				$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				$curl_error = curl_error($curl);
				curl_close($curl);

				if ($curl_error) {
					
					Af_Logger::debug( 'OpenAI Fraud Check: cURL error: ' . $curl_error );
					return false;
				}

				if ( 200 !== $http_code) {

					Af_Logger::debug( 'OpenAI Fraud Check: API HTTP status: ' . $response );
					return false;
				}

				$responsess = json_decode($response, true);

				if (
					!isset($responsess['choices'][0]['message']['content']) ||
					!is_string($responsess['choices'][0]['message']['content'])
				) {
					Af_Logger::debug( 'OpenAI Fraud Check: API response: ' . $responsess );
					return false;
				}

				$data = $responsess['choices'][0]['message']['content'];
				$data = ltrim($data, '[');
				$data = rtrim($data, ']');

				$result = explode(',', $data, 2);
				$risk_score = trim($result[0]);
				$explanation = isset($result[1]) ? $result[1] : '';

				$cleaned = preg_replace('/["\'\\\\]/', '', trim($explanation));
				$explanations = preg_split('/(?<=[a-z0-9])\. (?=[A-Z])/', $cleaned);
				$filtered = array_filter($explanations);
				$filtereds = array_values($filtered);

				opmc_hpos_update_post_meta($order_id, '_ai_explanations', $filtereds);
				opmc_hpos_update_post_meta($order_id, '_ai_risk_score', $risk_score);
			}
		}

		private function get_user_ip_address() {
			if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				$ip = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
				return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : 'UNKNOWN';
			}

			if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$ip_list = explode( ',', sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
				$ip      = trim( $ip_list[0] );
				return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : 'UNKNOWN';
			}

			if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
				return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : 'UNKNOWN';
			}

			return 'UNKNOWN';
		}

		private function create_body( $order, $chatgptVersion) {

			$userIp = $this->get_user_ip_address();
			$post_data = array(
				'shipping_address' => array(
					'first_name' => $order->get_shipping_first_name(),
					'last_name' => $order->get_shipping_last_name(),
					'company' => $order->get_shipping_company(),
					'address' => $order->get_shipping_address_1(),
					'address_2' => $order->get_shipping_address_2(),
					'city' => $order->get_shipping_city(),
					'region' => $order->get_shipping_state(),
					'country' => $order->get_shipping_country(),
					'postal' => $order->get_shipping_postcode(),
					'phone_number' => $order->get_billing_phone(),
				),
				'billing_address' => array(
					'first_name' => $order->get_billing_first_name(),
					'last_name' => $order->get_billing_last_name(),
					'company' => $order->get_billing_company(),
					'address' => $order->get_billing_address_1(),
					'address_2' => $order->get_billing_address_2(),
					'city' => $order->get_billing_city(),
					'region' => $order->get_billing_state(),
					'country' => $order->get_billing_country(),
					'postal' => $order->get_billing_postcode(),
					'phone_number' => $order->get_billing_phone(),
				),
				'customer_ip_address' => $userIp,
				'payment' => array(
					'processor' => $order->get_payment_method_title(),
				),
				'order' => array(
					'amount' => $order->get_total(),
					'currency' => get_woocommerce_currency(),
				),
			);

			$data = [
				'model' => $chatgptVersion,
				'messages' => [
					[
						'role' => 'system',
						'content' => 'Assess the likelihood of fraud for the given WooCommerce order based on the provided details.'
					],
					[
						'role' => 'user',
						'content' => 'These are the details of an order from my WooCommerce store. Can you rate the likelihood this order is fraudulent on a scale from 0 to 100? Provide a risk score and brief explanation in array format.'
					],
					[
						'role' => 'user',
						'content' => json_encode($post_data)
					]
				]
			];

			return json_encode($data);
		}
	}
}
