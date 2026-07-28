<?php
/**
 * Centralised CAPTCHA verification service.
 *
 * Handles all remote API calls for both Google reCAPTCHA v2 and
 * Cloudflare Turnstile so that callers (classic checkout, block
 * checkout, admin verification) share a single, tested code path.
 *
 * Return contract for both public methods:
 *   - Returns (bool) true  on successful verification.
 *   - Returns WP_Error     on any failure (network, config, or API rejection).
 *
 * The $context parameter controls which user-facing message is returned:
 *   'checkout'       — WooCommerce classic checkout page
 *   'block_checkout' — WooCommerce Blocks checkout
 *   'admin'          — wp-admin verification widget
 *
 * When "Enable Debug Log" is on (wc_af_enable_log_check = yes) every
 * call is written to uploads/wc-logs/antifraud-recaptcha-{date}-{hash}.log.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_AF_Captcha_Verification_Service' ) ) {
	class WC_AF_Captcha_Verification_Service {

		protected static $_instance = null;

		// Context constants — passed by callers so error messages are tailored.
		const CTX_CHECKOUT       = 'checkout';
		const CTX_BLOCK_CHECKOUT = 'block_checkout';
		const CTX_ADMIN          = 'admin';

		// -------------------------------------------------------------------------
		// Public verification methods
		// -------------------------------------------------------------------------

		/**
		 * Verify a Google reCAPTCHA v2 token against the siteverify API.
		 *
		 * Retries once on a network-level failure before giving up.
		 *
		 * @param string $token      Token submitted by the browser widget.
		 * @param string $secret_key Google reCAPTCHA secret key.
		 * @param string $context    One of the CTX_* constants (default: checkout).
		 * @return true|WP_Error
		 */
		public function verify_google( $token, $secret_key, $context = self::CTX_CHECKOUT ) {
			$verify_url = 'https://www.google.com/recaptcha/api/siteverify';

			$this->log(
				'Google reCAPTCHA verification started',
				array(
					'context_caller' => $context,
					'settings'       => array(
						'type'            => 'google_recaptcha',
						'site_key'        => $this->mask_key( get_option( 'wc_af_recaptcha_site_key', '' ) ),
						'secret_key'      => $this->mask_key( $secret_key ),
						'captcha_enabled' => get_option( 'wc_af_recaptcha_enable_captcha', 'no' ),
						'admin_verified'  => get_option( 'wc_af_admin_recaptcha_verified', 'no' ),
					),
					'request'        => array(
						'endpoint'      => $verify_url,
						'token_preview' => $this->preview_token( $token ),
					),
				)
			);

			$args = array(
				'body'        => array(
					'secret'   => $secret_key,
					'response' => $token,
				),
				'timeout'     => 15,
				'httpversion' => '1.1',
				'sslverify'   => true,
			);

			$response = wp_remote_post( $verify_url, $args );

			if ( is_wp_error( $response ) ) {
				$this->log(
					'Google reCAPTCHA network error — retrying once',
					array( 'error' => $response->get_error_message(), 'result' => 'RETRY' )
				);
				Af_Logger::error( 'reCAPTCHA API error (will retry): ' . $response->get_error_message() );

				sleep( 1 );
				$response = wp_remote_post( $verify_url, $args );

				if ( is_wp_error( $response ) ) {
					$this->log(
						'Google reCAPTCHA network error after retry — giving up',
						array( 'error' => $response->get_error_message(), 'result' => 'FAIL' )
					);
					Af_Logger::error( 'reCAPTCHA API error after retry: ' . $response->get_error_message() );

					return new WP_Error(
						'captcha_network_error',
						$this->msg( 'service_unavailable', $context )
					);
				}
			}

			$data          = json_decode( wp_remote_retrieve_body( $response ) );
			$error_codes   = isset( $data->{'error-codes'} ) && is_array( $data->{'error-codes'} )
				? $data->{'error-codes'}
				: array();
			$response_data = array(
				'success'      => isset( $data->success ) ? (bool) $data->success : false,
				'error_codes'  => $error_codes,
				'hostname'     => isset( $data->hostname ) ? $data->hostname : '',
				'challenge_ts' => isset( $data->challenge_ts ) ? $data->challenge_ts : '',
			);

			if ( ! isset( $data->success ) || ! (bool) $data->success ) {
				$this->log(
					'Google reCAPTCHA verification response',
					array( 'response' => $response_data, 'result' => 'FAIL' )
				);

				return $this->google_error_from_codes( $error_codes, $context );
			}

			$this->log(
				'Google reCAPTCHA verification response',
				array( 'response' => $response_data, 'result' => 'PASS' )
			);

			return true;
		}

		/**
		 * Verify a Cloudflare Turnstile token against the siteverify API.
		 *
		 * @param string $token      The cf-turnstile-response value.
		 * @param string $secret_key Turnstile secret key.
		 * @param string $remote_ip  Optional client IP for Cloudflare analytics.
		 * @param string $context    One of the CTX_* constants (default: checkout).
		 * @return true|WP_Error
		 */
		public function verify_turnstile( $token, $secret_key, $remote_ip = '', $context = self::CTX_CHECKOUT ) {
			$endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

			$this->log(
				'Cloudflare Turnstile verification started',
				array(
					'context_caller' => $context,
					'settings'       => array(
						'type'            => 'cf_turnstile',
						'site_key'        => $this->mask_key( get_option( 'wc_af_turnstile_site_key', '' ) ),
						'secret_key'      => $this->mask_key( $secret_key ),
						'captcha_enabled' => get_option( 'wc_af_recaptcha_enable_captcha', 'no' ),
					),
					'request'        => array(
						'endpoint'      => $endpoint,
						'token_preview' => $this->preview_token( $token ),
						'remote_ip'     => $remote_ip ? $remote_ip : '(not provided)',
					),
				)
			);

			$body = array(
				'secret'   => $secret_key,
				'response' => $token,
			);
			if ( ! empty( $remote_ip ) ) {
				$body['remoteip'] = $remote_ip;
			}

			$response = wp_remote_post( $endpoint, array( 'body' => $body, 'timeout' => 15 ) );

			if ( is_wp_error( $response ) ) {
				$this->log(
					'Cloudflare Turnstile network error',
					array( 'error' => $response->get_error_message(), 'result' => 'FAIL' )
				);

				return new WP_Error(
					'cf_captcha_network_error',
					$this->msg( 'service_unavailable', $context )
				);
			}

			$result        = json_decode( wp_remote_retrieve_body( $response ) );
			$error_codes   = isset( $result->{'error-codes'} ) && is_array( $result->{'error-codes'} )
				? $result->{'error-codes'}
				: array();
			$response_data = array(
				'success'     => ! empty( $result->success ),
				'error_codes' => $error_codes,
				'hostname'    => isset( $result->hostname ) ? $result->hostname : '',
				'action'      => isset( $result->action ) ? $result->action : '',
			);

			if ( empty( $result->success ) ) {
				$this->log(
					'Cloudflare Turnstile verification response',
					array( 'response' => $response_data, 'result' => 'FAIL' )
				);

				return $this->turnstile_error_from_codes( $error_codes, $context );
			}

			$this->log(
				'Cloudflare Turnstile verification response',
				array( 'response' => $response_data, 'result' => 'PASS' )
			);

			return true;
		}

		// -------------------------------------------------------------------------
		// Private helpers
		// -------------------------------------------------------------------------

		/**
		 * Return a context-aware user-facing message for a given error type.
		 *
		 * Error types:
		 *   'expired'           — token has timed out or was already used
		 *   'invalid'           — token is malformed or verification rejected
		 *   'service_unavailable' — cannot reach the CAPTCHA provider
		 *   'config_error'      — server-side key misconfiguration
		 *
		 * @param  string $type    One of the error types above.
		 * @param  string $context One of the CTX_* constants.
		 * @return string Translated, user-facing message.
		 */
		private function msg( $type, $context ) {
			$is_admin = ( self::CTX_ADMIN === $context );

			switch ( $type ) {

				case 'expired':
					return $is_admin
						? __( 'Expired CAPTCHA — the verification has timed out. Please solve the widget again.', 'woocommerce-anti-fraud' )
						: __( 'Expired CAPTCHA — please solve the verification again to complete your order.', 'woocommerce-anti-fraud' );

				case 'invalid':
					return $is_admin
						? __( 'Invalid CAPTCHA — verification was rejected. Please try again.', 'woocommerce-anti-fraud' )
						: __( 'Invalid CAPTCHA — verification failed. Please complete the CAPTCHA and try again.', 'woocommerce-anti-fraud' );

				case 'service_unavailable':
					return $is_admin
						? __( 'Service unavailable — cannot reach the CAPTCHA provider. Please check the server connection and try again.', 'woocommerce-anti-fraud' )
						: __( 'Service unavailable — the CAPTCHA service is temporarily unreachable. Please try again in a moment.', 'woocommerce-anti-fraud' );

				case 'config_error':
					return $is_admin
						? __( 'CAPTCHA configuration error — please verify your Site Key and Secret Key in the Anti-Fraud plugin settings.', 'woocommerce-anti-fraud' )
						: __( 'CAPTCHA is not configured correctly. Please contact the store administrator.', 'woocommerce-anti-fraud' );

				default:
					return $is_admin
						? __( 'CAPTCHA verification failed. Please try again.', 'woocommerce-anti-fraud' )
						: __( 'CAPTCHA verification failed. Please try again.', 'woocommerce-anti-fraud' );
			}
		}

		/**
		 * Translate Google error-codes into a context-aware WP_Error.
		 *
		 * Error-code → user-visible category mapping:
		 *   timeout-or-duplicate               → expired
		 *   invalid-input-response             → expired  (token rejected as invalid/stale)
		 *   missing-input-response / bad-request → invalid
		 *   missing-input-secret / invalid-input-secret → config_error
		 *
		 * @param  string[] $codes   Google error-code strings.
		 * @param  string   $context One of the CTX_* constants.
		 * @return WP_Error
		 */
		private function google_error_from_codes( array $codes, $context = self::CTX_CHECKOUT ) {
			if ( ! empty( $codes ) ) {
				Af_Logger::error( 'reCAPTCHA validation failed. Error codes: ' . implode( ', ', $codes ) );
			}

			// Config errors — secret key is wrong or missing.
			if ( in_array( 'missing-input-secret', $codes, true ) || in_array( 'invalid-input-secret', $codes, true ) ) {
				Af_Logger::error( 'reCAPTCHA: Invalid or missing secret key. Please check plugin settings.' );

				return new WP_Error( 'captcha_config_error', $this->msg( 'config_error', $context ) );
			}

			// Expired — token timed out or was already consumed.
			if ( in_array( 'timeout-or-duplicate', $codes, true ) || in_array( 'invalid-input-response', $codes, true ) ) {
				return new WP_Error( 'captcha_expired', $this->msg( 'expired', $context ) );
			}

			// Invalid — response was missing or request was malformed.
			if ( in_array( 'missing-input-response', $codes, true ) || in_array( 'bad-request', $codes, true ) ) {
				if ( in_array( 'bad-request', $codes, true ) ) {
					Af_Logger::error( 'reCAPTCHA: Bad request. Check API configuration.' );
				}

				return new WP_Error( 'captcha_invalid', $this->msg( 'invalid', $context ) );
			}

			// Generic fallback.
			return new WP_Error( 'captcha_failed', $this->msg( 'invalid', $context ) );
		}

		/**
		 * Translate Cloudflare Turnstile error-codes into a context-aware WP_Error.
		 * Turnstile uses the same error-code vocabulary as Google reCAPTCHA.
		 *
		 * @param  string[] $codes   Turnstile error-code strings.
		 * @param  string   $context One of the CTX_* constants.
		 * @return WP_Error
		 */
		private function turnstile_error_from_codes( array $codes, $context = self::CTX_CHECKOUT ) {
			if ( ! empty( $codes ) ) {
				Af_Logger::error( 'Turnstile validation failed. Error codes: ' . implode( ', ', $codes ) );
			}

			if ( in_array( 'missing-input-secret', $codes, true ) || in_array( 'invalid-input-secret', $codes, true ) ) {
				return new WP_Error( 'cf_captcha_config_error', $this->msg( 'config_error', $context ) );
			}

			if ( in_array( 'timeout-or-duplicate', $codes, true ) || in_array( 'invalid-input-response', $codes, true ) ) {
				return new WP_Error( 'cf_captcha_expired', $this->msg( 'expired', $context ) );
			}

			return new WP_Error( 'cf_captcha_invalid', $this->msg( 'invalid', $context ) );
		}

		/**
		 * Write a structured debug entry to the antifraud-recaptcha log file.
		 *
		 * Silently no-ops when debug logging is disabled (wc_af_enable_log_check ≠ yes).
		 * Log appears in WooCommerce → Status → Logs (source: antifraud-recaptcha).
		 *
		 * @param string $message Short human-readable summary.
		 * @param array  $data    Structured context (settings, request, response, etc.).
		 */
		private function log( $message, array $data = array() ) {
			if ( 'yes' !== get_option( 'wc_af_enable_log_check' ) ) {
				return;
			}

			if ( ! function_exists( 'wc_get_logger' ) ) {
				return;
			}

			$entry = array_merge(
				array(
					'message'   => $message,
					'timestamp' => current_time( 'Y-m-d H:i:s' ),
					'page'      => is_admin() ? 'admin' : 'frontend',
				),
				$data
			);

			wc_get_logger()->debug(
				print_r( $entry, true ),
				array( 'source' => 'antifraud-recaptcha' )
			);
		}

		/**
		 * Mask a sensitive API key for safe logging.
		 *
		 * @param  string $key Raw key value.
		 * @return string
		 */
		private function mask_key( $key ) {
			if ( empty( $key ) ) {
				return '(empty)';
			}

			return substr( $key, 0, 6 ) . '******';
		}

		/**
		 * Return a safe token preview for logging (first 24 chars only).
		 *
		 * @param  string $token Raw token value.
		 * @return string
		 */
		private function preview_token( $token ) {
			if ( empty( $token ) ) {
				return '(empty)';
			}

			return substr( $token, 0, 24 ) . '... [length:' . strlen( $token ) . ']';
		}

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}
	}
}
