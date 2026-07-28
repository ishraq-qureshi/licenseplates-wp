<?php
/**
 * Email Verification for WooCommerce - REST API Pro Class.
 *
 * @version 2.2.6
 * @since   2.1.4
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Email_Verification_REST_API_Pro' ) ) :

	class Alg_WC_Email_Verification_REST_API_Pro {
		public function __construct() {
			// Adds main verify function to the REST API
			add_action( 'rest_api_init', array( $this, 'register_rest_api_verify_function' ) );
		}

		/**
		 * register_rest_api_verify_function.
		 *
		 * @version 2.1.4
		 * @since   2.1.4
		 */
		function register_rest_api_verify_function() {
			if ( 'yes' === get_option( 'alg_wc_ev_rest_api_verify_endpoint', 'no' ) ) {
				register_rest_route( 'alg_wc_ev/v1', '/verify/', array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'verify' ),
					'permission_callback' => '__return_true',
				) );
			}
		}

		/**
		 * verify.
		 *
		 * @version 2.2.6
		 * @since   2.1.4
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		function verify( \WP_REST_Request $request ) {
			if ( ! isset( $request['verify_code'] ) || empty( $request['verify_code'] ) ) {
				return new WP_Error( 'no_verify_code', sprintf( __( 'You must provide the verification code %s parameter.', 'emails-verification-for-woocommerce' ), '(verify_code)' ), array( 'status' => 400 ) );
			}
			$result = alg_wc_ev()->core->verify( array(
				'verify_code' => $request['verify_code'],
				'directly'    => false,
			) );
			if ( ! $result ) {
				return new WP_Error( 'error', __( 'Verification failed.', 'emails-verification-for-woocommerce' ), array( 'status' => 500 ) );
			} else {
				$rest_response = new WP_REST_Response( array(
					'message' => __( 'Account verified successfully.', 'emails-verification-for-woocommerce' )
				) );
				$rest_response->set_status( 200 );
				return $rest_response;
			}
		}
	}
endif;

return new Alg_WC_Email_Verification_REST_API_Pro();