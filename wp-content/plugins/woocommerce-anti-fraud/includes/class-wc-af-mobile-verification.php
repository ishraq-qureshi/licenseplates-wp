<?php
/**
 * Class for All Hooks
 */

defined( 'ABSPATH' ) || exit;

class WC_AF_Mobile_Verification {

	protected static $_instance = null;

	public function __construct() {

		// if mobile verification is enabled
		if ( ( get_option( 'wc_af_enable_sms_verification', 'no' ) === 'yes' ) ) {
			add_action( 'woocommerce_review_order_before_submit', array( $this, 'add_mobile_verification_form' ) );
			add_action( 'wp_ajax_wc_af_send_otp_code', array( $this, 'send_otp_code' ) );
			add_action( 'wp_ajax_nopriv_wc_af_send_otp_code', array( $this, 'send_otp_code' ) );
			add_action( 'wp_ajax_wc_af_verify_otp_code', array( $this, 'verify_otp_code' ) );
			add_action( 'wp_ajax_nopriv_wc_af_verify_otp_code', array( $this, 'verify_otp_code' ) );
			add_action( 'woocommerce_checkout_process', array( $this, 'check_mobile_verification' ) );
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'reset_sms_verification' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'myplugin_enqueue_intl_tel_input_assets') );
		}
	}


	public function myplugin_enqueue_intl_tel_input_assets() {
		// Register and enqueue JS
		wp_enqueue_script(
			'intl-tel-input', // Handle
			'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.5/js/intlTelInput.min.js',
			array(), // Dependencies
			'17.0.5',
			true // Load in footer
		);

		// Register and enqueue CSS
		wp_enqueue_style(
			'intl-tel-input',
			'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.5/css/intlTelInput.min.css',
			array(),
			'17.0.5'
		);
	}


	/**
	 * Add mobile verification form before Place Order button
	 */
	public function add_mobile_verification_form() {

		if ( WC()->session->get( 'wc_af_sms_verified' ) ) {
			return;
		}

		?>
		<div class="wc-af-mobile-verification">
			<div class="verification-notice-box">
				<h3><?php esc_html_e( 'Mobile Verification', 'woocommerce-anti-fraud' ); ?></h3>
				<p><?php esc_html_e( 'You are required to verify your phone number via SMS verification.', 'woocommerce-anti-fraud' ); ?></p>
				<div class="verify-now-button">
					<span><?php esc_html_e( 'Verify Now', 'woocommerce-anti-fraud' ); ?></span>
				</div>
			</div>

			<div id="wc-af-mobile-verification-modal">
				<div class="modal-content">
					<span class="close">&times;</span>

					<div class="verification-image">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-mobile size-6">
							<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
						</svg>
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-verify size-6">
							<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/>
						</svg>
					</div>

					<h2><?php esc_html_e( 'Mobile Number Verification', 'woocommerce-anti-fraud' ); ?></h2>
					<p><?php esc_html_e( 'You are required to verify your phone number via SMS verification.', 'woocommerce-anti-fraud' ); ?></p>
					<div class="input-fields">
						<label class="mobile-number-field" for="">
							<input autocomplete="no" type="tel" id="wc-af-mobile-number" placeholder="<?php esc_attr_e( 'Mobile number', 'woocommerce-anti-fraud' ); ?>">
						</label>
						<label class="otp-code-field" for="wc-af-otp-code">
							<input type="text" disabled class="wc-af-otp-code" id="wc-af-otp-code" placeholder="<?php esc_attr_e( 'OTP Code', 'woocommerce-anti-fraud' ); ?>" maxlength="6">
						</label>
					</div>
					<p class="modal-message"><?php esc_html_e( 'Something went wrong!', 'woocommerce-anti-fraud' ); ?></p>
					<div class="mobile-number-verify-btn" data-action="send_otp"><?php esc_html_e( 'Verify', 'woocommerce-anti-fraud' ); ?></div>
				</div>
			</div>
		</div>
		<style>
			.verification-notice-box {
				background: #ffffff;
				padding: 20px;
				box-shadow: 0 0 20px 0 #00000014;
				margin-bottom: 30px;
			}

			.verification-notice-box .verify-now-button {
				background: #333333;
				display: inline-block;
				padding: 8px 16px;
				border-radius: 3px;
				color: #f1f1f1;
				cursor: pointer;
			}

			#wc-af-mobile-verification-modal {
				position: fixed;
				z-index: 1000;
				left: 0;
				top: 0;
				width: 100%;
				height: 100%;
				overflow: hidden;
				background-color: rgba(0, 0, 0, 0.4);
				display: none;
			}

			#wc-af-mobile-verification-modal .modal-content {
				background-color: #fefefe;
				margin: 8% auto;
				padding: 50px 20px;
				border: 1px solid #888;
				width: 560px;
				position: relative;
				text-align: center;
			}

			#wc-af-mobile-verification-modal .modal-content .verification-image {
				width: 180px;
				position: relative;
				margin: 0 auto 20px auto;
			}

			#wc-af-mobile-verification-modal .modal-content .verification-image .icon-mobile {
				stroke: #3b3b3b;
			}

			#wc-af-mobile-verification-modal .modal-content .verification-image .icon-verify {
				width: 50px;
				position: absolute;
				left: 50%;
				top: 50%;
				transform: translate(-50%, -50%);
				stroke: #10a372;
				stroke-width: 2;
			}

			#wc-af-mobile-verification-modal .modal-content .input-fields {
				display: flex;
				justify-content: space-between;
				width: 70%;
				margin: 0 auto;
			}

			#wc-af-mobile-verification-modal .modal-content .input-fields input,
			#wc-af-mobile-verification-modal .modal-content .input-fields input:focus,
			#wc-af-mobile-verification-modal .modal-content .input-fields input:active {
				outline: none;
				border: none;
			}

			#wc-af-mobile-verification-modal .modal-content .mobile-number-verify-btn {
				width: 280px;
				margin: 15px auto;
				border: none;
				cursor: pointer;
				background-color: #10a372;
				color: white;
				padding: 10px 20px;
			}

			#wc-af-mobile-verification-modal .modal-content .modal-message {
				background: #f1f1f1;
				color: #393939;
				padding: 5px 12px;
				border-radius: 3px;
				margin-top: 20px;
				margin-bottom: 0;
				display: none;
			}

			#wc-af-mobile-verification-modal .modal-content .modal-message.error {
				background: #ffe7e7;
				color: #f71f10;
			}

			#wc-af-mobile-verification-modal .modal-content .modal-message.success {
				background: #d5f4ea;
				color: #00675d;
			}

			#wc-af-mobile-verification-modal .modal-content .close {
				color: #3b3b3b;
				font-size: 28px;
				line-height: 1;
				font-weight: bold;
				cursor: pointer;
				position: absolute;
				top: 12px;
				right: 15px;
			}
		</style>
		<script>
			jQuery(document).ready(function ($) {
				let wooCommerceBillingPhone = $('input#billing_phone'),
					verificationWrapper = $('.wc-af-mobile-verification'),
					mobileNumberModal = verificationWrapper.find('#wc-af-mobile-verification-modal'),
					modalVerifyButton = mobileNumberModal.find('.mobile-number-verify-btn'),
					modalMessage = mobileNumberModal.find('.modal-message'),
					mobileNumberModalPhoneField = mobileNumberModal.find('#wc-af-mobile-number'),
					otpCodeInputField = mobileNumberModal.find('.wc-af-otp-code'),
					phoneNum = window.intlTelInput(document.querySelector("#wc-af-mobile-number"), {
						utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.5/js/utils.min.js",
						separateDialCode: true,
						initialCountry: jQuery("#sms_phone_cc").val()
					});

				verificationWrapper.find('.verification-notice-box .verify-now-button').on('click', function () {
					mobileNumberModalPhoneField.val(wooCommerceBillingPhone.val());
					mobileNumberModal.show();
				});

				mobileNumberModal.find('.close').on('click', function () {
					mobileNumberModal.hide();
				});

				modalVerifyButton.on('click', function () {

					let dataAction = $(this).data('action'),
						ajaxURL = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';

					modalMessage.removeClass('error success').css('display', 'none');

					if (dataAction === 'send_otp') {
						$.ajax({
							url: ajaxURL,
							type: 'POST',
							data: {
								action: 'wc_af_send_otp_code',
								nonce: '<?php echo esc_html( wp_create_nonce( 'wc_af_send_otp_code' ) ); ?>',,
								phone_number: phoneNum.getNumber(),
								country_code: phoneNum.getSelectedCountryData().iso2.toUpperCase()
							},
							success: function (response) {
								if (response.success) {
									otpCodeInputField.prop('disabled', false);
									otpCodeInputField.focus();
									modalVerifyButton.text('<?php esc_html_e( 'Verify OTP', 'woocommerce-anti-fraud' ); ?>');
									modalVerifyButton.data('action', 'verify_otp');

									modalMessage.html(response.data.message).addClass('success').css('display', 'inline-block');
								} else {
									modalMessage.html(response.data.message).addClass('error').css('display', 'inline-block');
								}
							}
						});
					}

					if (dataAction === 'verify_otp') {
						$.ajax({
							url: ajaxURL,
							type: 'POST',
							data: {
								action: 'wc_af_verify_otp_code',
								nonce: '<?php echo esc_html( wp_create_nonce( 'wc_af_verify_otp_code' ) ); ?>',
								otp_code: otpCodeInputField.val(),
							},
							success: function (response) {
								if (response.success) {
									modalMessage.text(response.data.message).addClass('success').css('display', 'inline-block');
									verificationWrapper.fadeOut();
									setTimeout(function () {
										mobileNumberModal.hide();
									}, 2000);
								} else {
									modalMessage.html(response.data.message).addClass('error').css('display', 'inline-block');
								}
							}
						});
					}

				});
			});
		</script>
		<?php
	}


	/**
	 * Verify OTP code through ajax
	 */
	public function verify_otp_code() {

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wc_af_verify_otp_code' ) ) { 
			wp_send_json_error( array( 'message' => __( 'Invalid nonce', 'woocommerce-anti-fraud' ) ) ); 
		}

		$otp_code = isset( $_POST['otp_code'] ) ? sanitize_text_field( $_POST['otp_code'] ) : '';

		if ( empty( $otp_code ) ) {
			wp_send_json_error( array( 'message' => __( 'OTP code is missing', 'woocommerce-anti-fraud' ) ) );
		}

		$tran_id = WC()->session->get( 'wc_af_sms_tran_id' );
		if ( empty( $tran_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Transaction ID not found', 'woocommerce-anti-fraud' ) ) );
		}

		$api_key = get_option( 'wc_af_sms_fraudlabspro_api_key', '' );
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'API key is not configured', 'woocommerce-anti-fraud' ) ) );
		}

		$api_url      = 'https://api.fraudlabspro.com/v2/verification/result';
		$query_params = http_build_query( [
			'key'     => $api_key,
			'format'  => 'json',
			'tran_id' => $tran_id,
			'otp'     => $otp_code
		] );
		$response     = wp_remote_get( $api_url . '?' . $query_params );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => 'API request failed: ' . $response->get_error_message() ) );
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		if ( isset( $result['result'] ) && 'Y' === $result['result'] ) {
			WC()->session->set( 'wc_af_sms_verified', true );
			wp_send_json_success( array( 'message' => __( 'OTP verified successfully. Closing the modal in 2 seconds..', 'woocommerce-anti-fraud' ) ) );
		} elseif ( isset( $result['error'] ) ) {
			/* translators: %s: Error message returned from the API */
			wp_send_json_error( array( 'message' => sprintf( __( 'OTP verification failed: %s', 'woocommerce-anti-fraud' ), $result['error']['error_message'] ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Unexpected response from API', 'woocommerce-anti-fraud' ) ) );
		}
	}


	/**
	 * Send OTP code through ajax
	 */
	public function send_otp_code() {

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wc_af_send_otp_code' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce', 'woocommerce-anti-fraud' ) ) );
		}

		$phone_number = isset( $_POST['phone_number'] ) ? sanitize_text_field( $_POST['phone_number'] ) : '';
		$country_code = isset( $_POST['country_code'] ) ? sanitize_text_field( $_POST['country_code'] ) : '';

		if ( empty( $phone_number ) || empty( $country_code ) ) {
			wp_send_json_error( array( 'message' => __( 'Phone number or country code is missing', 'woocommerce-anti-fraud' ) ) );
		}

		$api_key = get_option( 'wc_af_sms_fraudlabspro_api_key', '' );
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'API key is not configured', 'woocommerce-anti-fraud' ) ) );
		}

		$api_data = array(
			'key'          => $api_key,
			'format'       => 'json',
			'country_code' => $country_code,
			'tel'          => $phone_number, // '+11', // $phone_number,
			'mesg'         => __( 'Hi, Your OTP code for the WooCommerce order is: <otp>', 'woocommerce-anti-fraud' ),
		);
		$ch       = curl_init( 'https://api.fraudlabspro.com/v2/verification/send' );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $api_data ) );

		// Send the request
		$response = curl_exec( $ch );

		if ( curl_errno( $ch ) ) {
			$curl_error = curl_error( $ch );
			curl_close( $ch );

			wp_send_json_error( array( 'message' => 'cURL Error: ' . $curl_error ) );
		}

		curl_close( $ch );

		$result = json_decode( $response, true );

		if ( isset( $result['error']['error_code'] ) ) {
			wp_send_json_error( array( 'message' => 'API Error: ' . $result['error']['error_message'] ) );
		}

		if ( isset( $result['tran_id'] ) ) {
			WC()->session->set( 'wc_af_sms_tran_id', $result['tran_id'] );
			wp_send_json_success( array( 'message' => __( 'OTP sent successfully', 'woocommerce-anti-fraud' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to send OTP', 'woocommerce-anti-fraud' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'OTP verified successfully', 'woocommerce-anti-fraud' ) ) );
	}

	/**
	 * Check if mobile is verified before order placement
	 */
	public function check_mobile_verification() {
		if ( ! WC()->session->get( 'wc_af_sms_verified' ) ) {
			wc_add_notice( __( 'Please verify your mobile number before placing the order.', 'woocommerce-anti-fraud' ), 'error' );
		}
	}

	/**
	 * Reset SMS verification after order is processed
	 *
	 * @param int $order_id The ID of the order that was just created
	 */
	public function reset_sms_verification( $order_id ) {
		if ( WC()->session ) {
			WC()->session->set( 'wc_af_sms_verified', false );
			WC()->session->set( 'wc_af_sms_tran_id', null );
		}
	}

	/**
	 * Get the mobile verification handler instance.
	 *
	 * @return WC_AF_Mobile_Verification The mobile verification instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

WC_AF_Mobile_Verification::instance();
