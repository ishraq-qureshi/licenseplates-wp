<?php
/**
 * All captcha codebase here
 */

if ( ! class_exists( 'WC_AF_Captcha' ) ) {
	class WC_AF_Captcha {

		protected static $_instance = null;

		public function __construct() {

			$wc_af_recaptcha_enable_captcha = get_option( 'wc_af_recaptcha_enable_captcha' );
			$wc_af_admin_recaptcha_verified = get_option( 'wc_af_admin_recaptcha_verified' );

		add_action( 'admin_notices', array( $this, 'check_admin_recaptcha_verification' ) );
		add_action( 'admin_notices', array( $this, 'check_turnstile_configuration_notice' ) );
		add_action( 'wp_ajax_verify_admin_recaptcha', array( $this, 'verify_admin_recaptcha_callback' ) );

		// Widget-solve logging — fires from the browser the moment the user
		// completes the challenge, before form submission. Available to both
		// guests (nopriv) and logged-in users so checkout works for everyone.
		add_action( 'wp_ajax_wc_af_log_captcha_solved', array( $this, 'log_captcha_solved_callback' ) );
		add_action( 'wp_ajax_nopriv_wc_af_log_captcha_solved', array( $this, 'log_captcha_solved_callback' ) );
		// Priority 20 so we run after other plugins (e.g. recaptcha-woo at
		// default priority 10) have had a chance to register their handles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			if ( self::is_checkout_protection_active() ) {

				// Hook into the WooCommerce order processing
//				add_action( 'woocommerce_before_pay_action', array( $this, 'verify_recaptcha_on_order_retry_v2' ), 1 );
//				add_action( 'woocommerce_pay_order_before_submit', array( $this, 'add_recaptcha_to_retry_page_v2' ) );

				// For express checkout
				include_once WOOCOMMERCE_ANTI_FRAUD_PLUGIN_DIR . 'woo-includes/checkout-blocks-initialize.php';

			// For classic checkout — attach to the admin-selected hook/position.
			// get_captcha_checkout_hook() already validates against the allowed list,
			// so no extra has_action() guard is needed here.
			$checkout_hook     = $this->get_captcha_checkout_hook();
			$checkout_priority = $this->get_captcha_checkout_priority();

			add_action( $checkout_hook, array( $this, 'wc_af_captcha_checkout_field' ), $checkout_priority );
				add_action( 'woocommerce_after_checkout_validation', array( $this, 'wc_af_validate_captcha' ), 10, 2 );
			}
		}

		/**
		 * Returns the ordered list of allowed checkout hook positions.
		 * Keys are WooCommerce action hook names; values are human-readable labels.
		 *
		 * @return array<string, string>
		 */
		public static function get_checkout_position_options() {
			// Visual order on the classic checkout page:
			//
			//  [Billing form]      [Shipping form]
			//                      [Order notes textarea]
			//                      ← woocommerce_after_order_notes (inside shipping/notes column)
			//  ← woocommerce_checkout_after_customer_details (full-width, after billing+shipping block)
			//  <Your order heading>
			//  [Order summary table]
			//  ← woocommerce_review_order_before_payment (before payment methods)
			//  [Payment method list]
			//  ← woocommerce_review_order_before_submit (after payment methods, before button)
			//  [Place Order button]
			//  ← woocommerce_review_order_after_payment (below Place Order button)
			return array(
				'woocommerce_after_order_notes'           => __( 'After order notes', 'woocommerce-anti-fraud' ),
				'woocommerce_checkout_after_customer_details' => __( 'After billing / shipping form', 'woocommerce-anti-fraud' ),
				'woocommerce_review_order_before_payment' => __( 'Before payment methods (default)', 'woocommerce-anti-fraud' ),
				'woocommerce_review_order_before_submit'  => __( 'After payment methods (above Place Order button)', 'woocommerce-anti-fraud' ),
				'woocommerce_review_order_after_payment'  => __( 'Below Place Order button', 'woocommerce-anti-fraud' ),
			);
		}

		/**
		 * Resolves the WooCommerce hook that will output the CAPTCHA on classic checkout.
		 * Reads the saved setting, validates it against the allowed list, then passes it
		 * through the `wc_af_captcha_checkout_hook` filter so developers can override it.
		 *
		 * @return string
		 */
		public function get_captcha_checkout_hook() {
			$allowed = array_keys( self::get_checkout_position_options() );
			$saved   = get_option( 'wc_af_captcha_checkout_position', 'woocommerce_review_order_before_payment' );
			$hook    = in_array( $saved, $allowed, true ) ? $saved : 'woocommerce_review_order_before_payment';

			/**
			 * Filters the WooCommerce hook used to render the CAPTCHA on classic checkout.
			 *
			 * Useful when none of the preset positions matches your theme/gateway layout.
			 *
			 * @since 7.2.0
			 * @param string $hook The resolved hook name (e.g. 'woocommerce_review_order_before_submit').
			 */
			return (string) apply_filters( 'wc_af_captcha_checkout_hook', $hook );
		}

		/**
		 * Resolves the priority used when attaching the CAPTCHA output to the checkout hook.
		 * Passes through the `wc_af_captcha_checkout_priority` filter so developers can adjust it.
		 *
		 * @return int
		 */
		public function get_captcha_checkout_priority() {
			/**
			 * Filters the action priority used to render the CAPTCHA on classic checkout.
			 *
			 * Lower values run earlier; higher values run later within the same hook.
			 *
			 * @since 7.2.0
			 * @param int $priority Default 10.
			 */
			return (int) apply_filters( 'wc_af_captcha_checkout_priority', 10 );
		}

	/**
	 * Whether Cloudflare Turnstile site and secret keys are both configured.
	 *
	 * @return bool
	 * @since 7.2.8
	 */
		public static function is_turnstile_configured() {
			$site_key   = get_option( 'wc_af_turnstile_site_key', '' );
			$secret_key = get_option( 'wc_af_turnstile_secret_key', '' );

			return ! empty( $site_key ) && ! empty( $secret_key );
		}

	/**
	 * Whether checkout CAPTCHA protection is active for the configured provider.
	 *
	 * Google reCAPTCHA requires the existing admin-verification flag.
	 * Cloudflare Turnstile is considered active once its keys are saved.
	 *
	 * @return bool
	 * @since 7.2.8
	 */
		public static function is_checkout_protection_active() {
			$enabled = get_option( 'wc_af_recaptcha_enable_captcha', 'no' );
			if ( 'yes' !== $enabled && '1' !== $enabled ) {
				return false;
			}

			$provider = get_option( 'wc_af_recaptcha_type', 'google_recaptcha' );
			$provider = empty( $provider ) ? 'google_recaptcha' : $provider;

			if ( 'cf_turnstile' === $provider ) {
				return self::is_turnstile_configured();
			}

			// Google reCAPTCHA — preserve existing activation requirements unchanged.
			return 'yes' === get_option( 'wc_af_admin_recaptcha_verified', 'no' );
		}

		public function wc_af_validate_captcha( $fields, $validation_errors ) {

			$recaptcha_type        = get_option( 'wc_af_recaptcha_type', 'google_recaptcha' );
			$recaptcha_type        = empty( $recaptcha_type ) ? 'google_recaptcha' : $recaptcha_type;
			$cart_hash             = WC()->cart->get_cart_hash();
			$is_verified_transient = 'wc_af_is_verified_' . $cart_hash;
			$verified_at_transient = 'wc_af_verified_at_' . $cart_hash;
			$is_verified           = get_transient( $is_verified_transient );
			$verified_at           = get_transient( $verified_at_transient );

			if ( 'yes' === $is_verified && ( current_time( 'U' ) - $verified_at ) < 120 ) {
				return $validation_errors;
			}

			$service = WC_AF_Captcha_Verification_Service::instance();

			if ( 'cf_turnstile' === $recaptcha_type ) {
				$turnstile_secret_key = get_option( 'wc_af_turnstile_secret_key', '' );

				if ( empty( $turnstile_secret_key ) ) {
					$validation_errors->add( 'captcha_error', __( 'Your reCAPTCHA is not configured properly.', 'woocommerce-anti-fraud' ) );

					return $validation_errors;
				}

				if ( isset( $_POST['woocommerce-process-checkout-nonce'] ) ) {
					if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
						wp_die( esc_html__( 'Security check failed. Please try again.', 'woocommerce-anti-fraud' ) );
					}
				}

				$turnstile_response = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( $_POST['cf-turnstile-response'] ) : '';

				if ( empty( $turnstile_response ) ) {
					$validation_errors->add( 'cf_captcha_error', __( 'Please verify the recaptcha properly.', 'woocommerce-anti-fraud' ) );

					return $validation_errors;
				}

				$result = $service->verify_turnstile( $turnstile_response, $turnstile_secret_key, '', WC_AF_Captcha_Verification_Service::CTX_CHECKOUT );

				if ( is_wp_error( $result ) ) {
					$validation_errors->add( $result->get_error_code(), $result->get_error_message() );

					return $validation_errors;
				}
			}

			if ( 'google_recaptcha' === $recaptcha_type ) {
				$secret_key = get_option( 'wc_af_recaptcha_secret_key', '' );

				if ( empty( $secret_key ) ) {
					$validation_errors->add( 'captcha_error', __( 'Your reCAPTCHA is not configured properly.', 'woocommerce-anti-fraud' ) );

					return $validation_errors;
				}

				if ( isset( $_POST['woocommerce-process-checkout-nonce'] ) ) {
					if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
						wp_die( esc_html__( 'Security check failed. Please try again.', 'woocommerce-anti-fraud' ) );
					}
				}

				$captcha_response = isset( $_POST['af_checkout_captcha_response'] ) ? sanitize_text_field( $_POST['af_checkout_captcha_response'] ) : '';

				if ( empty( $captcha_response ) ) {
					$validation_errors->add( 'captcha_error', __( 'Please complete the captcha verification properly.', 'woocommerce-anti-fraud' ) );

					return $validation_errors;
				}

				$result = $service->verify_google( $captcha_response, $secret_key, WC_AF_Captcha_Verification_Service::CTX_CHECKOUT );

				if ( is_wp_error( $result ) ) {
					$validation_errors->add( $result->get_error_code(), $result->get_error_message() );

					return $validation_errors;
				}
			}

			set_transient( $is_verified_transient, 'yes', 120 );
			set_transient( $verified_at_transient, current_time( 'U' ), 120 );

			return $validation_errors;
		}

		public function wc_af_captcha_checkout_field() {

			wp_enqueue_script( 'jquery' );

			$wc_af_recaptcha_site_key           = get_option( 'wc_af_recaptcha_site_key' );
			$wc_af_turnstile_site_key           = get_option( 'wc_af_turnstile_site_key' );
			$wc_af_enable_checkout_waiting_time = get_option( 'wc_af_enable_checkout_waiting_time', 'no' );
			$wc_af_recaptcha_wait_time          = get_option( 'wc_af_recaptcha_wait_time', 10 );
			$wc_af_recaptcha_type               = get_option( 'wc_af_recaptcha_type', 'google_recaptcha' );
			$wc_af_recaptcha_type               = empty( $wc_af_recaptcha_type ) ? 'google_recaptcha' : $wc_af_recaptcha_type;

			// Detect hooks that fire between floated checkout columns.
			// Storefront (and most WC themes) float .col2-set left and #order_review right.
			// Injecting a block element between them via PHP collapses the form and shows the
			// theme sidebar. For those hooks we let PHP output the wrapper at the end of the
			// document flow, then immediately relocate it with JS into the correct DOM parent.
			$current_hook     = (string) current_filter();
			$needs_relocation = ( 'woocommerce_checkout_after_customer_details' === $current_hook );

			// Hooks whose output lives inside #order_review (refreshed on every
			// updated_checkout AJAX call). For these we need to re-initialise the
			// captcha widget after each refresh. Hooks outside #order_review must NOT
			// reset on updated_checkout — doing so clears a valid solved token.
			$inside_order_review = in_array( $current_hook, array(
				'woocommerce_review_order_before_payment',
				'woocommerce_review_order_before_submit',
				'woocommerce_review_order_after_payment',
			), true );

			?>

			<?php if ( 'google_recaptcha' === $wc_af_recaptcha_type) : ?>
			<div class="wc-af-captcha-checkout-wrapper"<?php echo $needs_relocation ? ' style="display:none"' : ''; ?>>
				<div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_captcha"><?php echo esc_html__( 'Captcha', 'woocommerce-anti-fraud' ); ?>&nbsp;<span class="required">*</span></label>
					<div id="wc-af-recaptcha-checkout" class="g-recaptcha" data-sitekey="<?php echo esc_attr( $wc_af_recaptcha_site_key ); ?>" data-callback="onCheckoutCaptchaSuccess"></div>
					<input type="hidden" name="af_checkout_captcha_response" id="af_checkout_captcha_response" value="">
				</div>

				<div id="wc-af-timer" style="display: none; text-align: left; margin-bottom: 10px;"></div>
				
				<script>
					let wcAfCaptcha = null;

					(function ($) {
					window.onCheckoutCaptchaSuccess = function (token) {

						if (!token) {
							return;
						}

						$('#af_checkout_captcha_response').val(token);

						if (window.wcAfClassicExpressGuard) {
							window.wcAfClassicExpressGuard.setSolved(true);
						}

						// Log the widget-solve event immediately so the debug log
						// shows a timestamped entry before the form is submitted.
						$.post('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', {
							action:        'wc_af_log_captcha_solved',
							nonce:         '<?php echo esc_js( wp_create_nonce( 'wc_af_log_captcha_solved' ) ); ?>',
							type:          'google_recaptcha',
							token_preview: token.substring(0, 24) + '... [length:' + token.length + ']'
						});

							let enable_checkout_waiting_time = '<?php echo esc_js( $wc_af_enable_checkout_waiting_time ); ?>';
							if (enable_checkout_waiting_time !== 'yes') {
								return;
							}

							let $timer = $("#wc-af-timer"),
								$placeOrder = $("#place_order"),
								baseWaitTime = <?php echo esc_js( $wc_af_recaptcha_wait_time ); ?>,
								attemptCount = parseInt($timer.data('attempts') || 0) + 1;

							$timer.data('attempts', attemptCount);

							let remainingTime = Math.min(baseWaitTime * attemptCount, 1200);

							$timer.show();
							$placeOrder.prop("disabled", true);

							wcafUpdateTimer(remainingTime);

							let captchaTimer = setInterval(function () {
								remainingTime--;
								if (remainingTime <= 0) {
									clearInterval(captchaTimer);
									$timer.hide();
									$placeOrder.prop("disabled", false);
								} else {
									wcafUpdateTimer(remainingTime);
								}
							}, 1000);

							$timer.data('timer', captchaTimer);
						};

						function wcafUpdateTimer(remainingTime) {
							$("#wc-af-timer").html("<b>Anti-fraud prevention measures will delay the transaction time for the next <span style='color: red;'>" + remainingTime + "</span> seconds.</b>");
						}

					function initializeAFCaptcha() {
						if (typeof grecaptcha !== 'undefined') {
							grecaptcha.ready(function () {
								if (wcAfCaptcha === null && document.getElementById('wc-af-recaptcha-checkout')) {
									try {
										wcAfCaptcha = grecaptcha.render('wc-af-recaptcha-checkout', {
											'sitekey': '<?php echo esc_attr( $wc_af_recaptcha_site_key ); ?>',
											'callback': onCheckoutCaptchaSuccess
										});
									} catch (e) {
										console.log('reCAPTCHA has already been rendered in this element');
									}
								}
							});
						} else {
							setTimeout(initializeAFCaptcha, 300);
						}
					}

				$(document.body).on('checkout_error', function () {
					let $timer = $("#wc-af-timer"),
						captchaTimer = $timer.data('timer');

					if (captchaTimer) {
						clearInterval(captchaTimer);
					}

					if (typeof grecaptcha !== 'undefined' && wcAfCaptcha !== null) {
						grecaptcha.reset(wcAfCaptcha);
					}

					$('#af_checkout_captcha_response').val('');

					if (window.wcAfClassicExpressGuard) {
						window.wcAfClassicExpressGuard.setSolved(false);
					}

					$timer.hide();
					$("#place_order").prop("disabled", false);
				});

				$(function () {
					initializeAFCaptcha();
					if (window.wcAfClassicExpressGuard) {
						window.wcAfClassicExpressGuard.refresh();
					}
				});

				<?php if ( $inside_order_review ) : ?>
				$(document).off('updated_checkout.wc_af').on('updated_checkout.wc_af', function () {
					wcAfCaptcha = null;
					initializeAFCaptcha();
					if (window.wcAfClassicExpressGuard) {
						window.wcAfClassicExpressGuard.refresh();
					}
				});
				<?php endif; ?>
					})(jQuery);
				</script>

				<?php if ( $needs_relocation ) : ?>
				<script>
					(function ($) {
						// Move the wrapper inside #customer_details so it sits within the
						// floated left column instead of between the two checkout columns.
						function wcAfRelocateCaptcha() {
							var $wrapper = $('.wc-af-captcha-checkout-wrapper');
							var $target  = $('#customer_details');
							if ( $wrapper.length && $target.length && ! $.contains( $target[0], $wrapper[0] ) ) {
								$target.append( $wrapper.css( 'display', '' ) );
							}
						}
						$(function () { wcAfRelocateCaptcha(); });
					})(jQuery);
				</script>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php if ( 'cf_turnstile' === $wc_af_recaptcha_type && ! empty( $wc_af_turnstile_site_key ) ) : ?>
			<div class="wc-af-captcha-checkout-wrapper"<?php echo $needs_relocation ? ' style="display:none"' : ''; ?>>
				<div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<div class="cf-turnstile"
						data-sitekey="<?php echo esc_attr( $wc_af_turnstile_site_key ); ?>"
						data-callback="wcAfOnTurnstileSolved">
					</div>
				</div>
				<script>
				window.wcAfOnTurnstileSolved = function (token) {
					if (!token) { return; }
					jQuery.post('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', {
						action:        'wc_af_log_captcha_solved',
						nonce:         '<?php echo esc_js( wp_create_nonce( 'wc_af_log_captcha_solved' ) ); ?>',
						type:          'cf_turnstile',
						token_preview: token.substring(0, 24) + '... [length:' + token.length + ']'
					});
				};
				</script>
				<?php if ( $needs_relocation ) : ?>
				<script>
					(function ($) {
						function wcAfRelocateCaptcha() {
							var $wrapper = $('.wc-af-captcha-checkout-wrapper');
							var $target  = $('#customer_details');
							if ( $wrapper.length && $target.length && ! $.contains( $target[0], $wrapper[0] ) ) {
								$target.append( $wrapper.css( 'display', '' ) );
							}
						}
						$(function () { wcAfRelocateCaptcha(); });
					})(jQuery);
				</script>
				<?php endif; ?>
			</div>
			<?php endif; ?>
			<?php
		}

		public function add_recaptcha_to_retry_page_v2() {
			$wc_af_recaptcha_site_key = get_option( 'wc_af_recaptcha_site_key' );
			?>
			<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" style="padding: 0px !important;">
				<label for="reg_captcha"><?php echo esc_html__( 'Captcha', 'woocommerce-anti-fraud' ); ?>&nbsp;<span class="required">*</span></label>
			<div id="wc-af-recaptcha-retry" class="g-recaptcha" data-sitekey="<?php echo esc_attr( $wc_af_recaptcha_site_key ); ?>" data-callback="onRetryPageCaptchaSuccess"></div>
			<input type="hidden" name="retry_captcha_response" id="retry_captcha_response" value="">
			</p>
			<script>
				window.onRetryPageCaptchaSuccess = function (token) {
					document.getElementById('retry_captcha_response').value = token;
				};
			</script>
			<?php
		}

		public function verify_recaptcha_on_order_retry_v2( $order_id ) {

			if ( isset( $_POST['woocommerce-pay-nonce'] ) && ! empty( $_POST['woocommerce-pay-nonce'] ) ) {

				$nonce_value = '';
				if ( isset( $_REQUEST['woocommerce-pay-nonce'] ) || isset( $_REQUEST['_wpnonce'] ) ) {
					if ( isset( $_REQUEST['woocommerce-pay-nonce'] ) && ! empty( $_REQUEST['woocommerce-pay-nonce'] ) ) {
						$nonce_value = sanitize_text_field( $_REQUEST['woocommerce-pay-nonce'] );
					} else if ( isset( $_REQUEST['_wpnonce'] ) && ! empty( $_REQUEST['_wpnonce'] ) ) {
						$nonce_value = sanitize_text_field( $_REQUEST['_wpnonce'] );
					}
				}

				if ( wp_verify_nonce( $nonce_value, 'woocommerce-pay' ) ) {

					if ( 'yes' == get_transient( $nonce_value ) ) {
						return true;
					}

					if ( isset( $_POST['g-recaptcha-response'] ) && ! empty( $_POST['g-recaptcha-response'] ) ) {

						$response   = sanitize_text_field( $_POST['g-recaptcha-response'] );
						$secret_key = get_option( 'wc_af_recaptcha_secret_key' );
						$result     = WC_AF_Captcha_Verification_Service::instance()->verify_google( $response, $secret_key, WC_AF_Captcha_Verification_Service::CTX_CHECKOUT );

						if ( is_wp_error( $result ) ) {
							wc_add_notice( $result->get_error_message(), 'error' );
						} else {
							if ( 0 != 3 ) {
								set_transient( $nonce_value, 'yes', ( 3 * 60 ) );
							}
						}
					} else {
						wc_add_notice( __( 'Recaptcha is a required field.' ), 'error' );
					}
				} else {
					wc_add_notice( __( 'Could not verify request.' ), 'error' );
				}
			}

			return true;

		}

		/**
		 * AJAX handler: log the moment the user solves the CAPTCHA widget.
		 *
		 * Called from the browser immediately on widget solve, before the
		 * checkout form is submitted. Only writes to the log when debug
		 * logging is enabled; always returns a 200 success so the AJAX
		 * ping never blocks the checkout flow.
		 */
		public function log_captcha_solved_callback() {
			// No-op when debug logging is off — always return success so the
			// fire-and-forget $.post() never causes a visible JS error.
			if ( 'yes' !== get_option( 'wc_af_enable_log_check' ) ) {
				wp_send_json_success();
				return;
			}

			// Soft nonce check: reject silently instead of wp_die() so a
			// stale page cache never prevents a log entry from being written.
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'wc_af_log_captcha_solved' ) ) {
				wp_send_json_success(); // still 200 — logging endpoint only
				return;
			}

			if ( ! function_exists( 'wc_get_logger' ) ) {
				wp_send_json_success();
				return;
			}

			$type          = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'unknown';
			$token_preview = isset( $_POST['token_preview'] ) ? sanitize_text_field( wp_unslash( $_POST['token_preview'] ) ) : '(empty)';

			$entry = array(
				'message'       => 'CAPTCHA widget solved by user',
				'timestamp'     => current_time( 'Y-m-d H:i:s' ),
				'context'       => 'frontend',
				'event'         => 'widget_solved',
				'captcha_type'  => $type,
				'token_preview' => $token_preview,
				'note'          => 'Server-side verification will follow on form submit',
			);

			wc_get_logger()->debug(
				print_r( $entry, true ),
				array( 'source' => 'antifraud-recaptcha' )
			);

			wp_send_json_success();
		}

		public function verify_admin_recaptcha_callback() {
			check_ajax_referer( 'wc_af_verify_admin_recaptcha', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				update_option( 'wc_af_admin_recaptcha_verified', 'no' );
				wp_send_json_error( 'Unauthorized access' );

				return;
			}

			$token = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';
			if ( empty( $token ) ) {
				update_option( 'wc_af_admin_recaptcha_verified', 'no' );
				wp_send_json_error( 'Invalid token' );

				return;
			}

			$secret_key = get_option( 'wc_af_recaptcha_secret_key' );
			$result     = WC_AF_Captcha_Verification_Service::instance()->verify_google( $token, $secret_key, WC_AF_Captcha_Verification_Service::CTX_ADMIN );

			if ( is_wp_error( $result ) ) {
				update_option( 'wc_af_admin_recaptcha_verified', 'no' );
				wp_send_json_error( $result->get_error_message() );

				return;
			}

			update_option( 'wc_af_admin_recaptcha_verified', 'yes' );
			wp_send_json_success( 'Verification successful' );
		}

	/**
	 * Admin notice when Turnstile is selected but site/secret keys are incomplete.
	 * Does not affect the Google reCAPTCHA admin verification flow.
	 *
	 * @since 7.2.8
	 */
		public function check_turnstile_configuration_notice() {
			if ( 'cf_turnstile' !== get_option( 'wc_af_recaptcha_type', '' ) ) {
				return;
			}

			if ( 'yes' !== get_option( 'wc_af_recaptcha_enable_captcha', 'no' ) ) {
				return;
			}

			if ( self::is_turnstile_configured() ) {
				return;
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			printf(
				'<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s</p></div>',
				esc_html__( 'WooCommerce Anti-Fraud:', 'woocommerce-anti-fraud' ),
				esc_html__( 'Cloudflare Turnstile is enabled but the site key or secret key is missing. Checkout protection will remain inactive until both keys are saved.', 'woocommerce-anti-fraud' )
			);
		}

		public function check_admin_recaptcha_verification() {
			// Skip if already verified or no site key set
			if (
				get_option( 'wc_af_admin_recaptcha_verified' ) === 'yes' ||
				! get_option( 'wc_af_recaptcha_site_key' ) ||
				get_option( 'wc_af_recaptcha_type' ) !== 'google_recaptcha'
			) {
				return;
			}

			// Only show to administrators
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			?>
			<div class="notice notice-warning" id="wc-af-admin-recaptcha-notice">
				<p><?php esc_html_e( 'Please verify reCAPTCHA to complete Anti-Fraud plugin setup:', 'woocommerce-anti-fraud' ); ?></p>
				<div id="wc-af-admin-recaptcha" class="g-recaptcha" style="margin-bottom: 10px;" data-sitekey="<?php echo esc_attr( get_option( 'wc_af_recaptcha_site_key' ) ); ?>" data-callback="onAdminCaptchaSuccess"></div>
				<div id="wc-af-admin-recaptcha-message"></div>
				<script>
					function onAdminCaptchaSuccess(token) {
						jQuery.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'verify_admin_recaptcha',
								token: token,
								nonce: '<?php echo esc_js( wp_create_nonce( 'wc_af_verify_admin_recaptcha' ) ); ?>'
							},
							success: function (response) {
								if (response.success) {
									jQuery('#wc-af-admin-recaptcha-notice').fadeOut();
								} else {
									jQuery('#wc-af-admin-recaptcha-message').html('<p style="color: red;">' + response.data + '</p>');
								}
							}
						});
					}
				</script>
			</div>
			<?php
		}

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Returns true when the checkout page currently being viewed uses the
		 * WooCommerce Checkout block (not the classic [woocommerce_checkout] shortcode).
		 *
		 * @return bool
		 */
		public function is_block_checkout_page() {
			if ( ! function_exists( 'has_block' ) || ! is_checkout() ) {
				return false;
			}

			global $post;

			if ( ! $post instanceof WP_Post ) {
				return false;
			}

			return has_block( 'woocommerce/checkout', $post );
		}

		/**
		 * Enqueue the Google reCAPTCHA API script exactly once, even when
		 * another plugin (e.g. recaptcha-woo) has already registered it under
		 * a different handle without the render=explicit parameter we require.
		 *
		 * Running at wp_enqueue_scripts priority 20 means all priority-10
		 * registrations are already in the queue when this runs.
		 *
		 * Strategy:
		 *  - If a known third-party handle is already registered, deregister it
		 *    and re-register with the render=explicit URL, then enqueue it.
		 *    This ensures only one <script> tag is emitted.
		 *  - Otherwise register and enqueue our own handle.
		 */
		private function enqueue_google_recaptcha_api() {
			$src = 'https://www.google.com/recaptcha/api.js?render=explicit';
			$versions        = WOOCOMMERCE_ANTI_FRAUD_VERSION;
			// Handles commonly used by other plugins for the reCAPTCHA API script.
			$third_party_handles = array( 'recaptcha', 'google-recaptcha', 'recaptchav2', 'recaptcha-v2' );

			foreach ( $third_party_handles as $handle ) {
				if ( wp_script_is( $handle, 'registered' ) ) {
					wp_deregister_script( $handle );
					wp_register_script( $handle, $src, array(), $versions, false );
					wp_enqueue_script( $handle );
					return;
				}
			}

			wp_enqueue_script( 'wc-af-google-recaptcha-v3', $src, array(), $versions, false );
		}

		public function enqueue_scripts() {
			$version        = WOOCOMMERCE_ANTI_FRAUD_VERSION;
			$recaptcha_type = get_option( 'wc_af_recaptcha_type', 'google_recaptcha' );
			$recaptcha_type = empty( $recaptcha_type ) ? 'google_recaptcha' : $recaptcha_type;

			if ( ! is_admin() && is_checkout() ) {
				if ( 'google_recaptcha' === $recaptcha_type ) {
					$this->enqueue_google_recaptcha_api();

					if (
					'yes' === get_option( 'wc_af_recaptcha_enable_captcha', 'no' )
					&& 'yes' === get_option( 'wc_af_admin_recaptcha_verified', 'no' )
					&& ! $this->is_block_checkout_page()
					) {
						wp_enqueue_script(
						'wc-af-classic-checkout-express-guard',
						WOOCOMMERCE_ANTI_FRAUD_PLUGIN_URL . 'assets/js/wc-af-classic-checkout-express-guard.js',
						array( 'jquery' ),
						$version,
						true
						);
					}
				} elseif ( 'cf_turnstile' === $recaptcha_type && self::is_checkout_protection_active() ) {
					wp_enqueue_script(
					'wc-af-cf-turnstile',
					'https://challenges.cloudflare.com/turnstile/v0/api.js',
					array(),
					$version,
					false
					);
				}
			}

		// Google reCAPTCHA admin verification — only load the script when the
		// admin verification notice will actually be rendered, to avoid
		// unnecessary reCAPTCHA timeouts on every wp-admin page.
			if (
			is_admin() &&
			get_option( 'wc_af_admin_recaptcha_verified' ) !== 'yes' &&
			get_option( 'wc_af_recaptcha_site_key' ) &&
			get_option( 'wc_af_recaptcha_type' ) === 'google_recaptcha' &&
			current_user_can( 'manage_options' )
		) {
				wp_enqueue_script(
				'wc-af-google-recaptcha-admin',
				'https://www.google.com/recaptcha/api.js',
				array(),
				$version,  // no version for external script
				false  // load in <head> so grecaptcha is available when the notice renders
				);
			}
		}

	}
}

WC_AF_Captcha::instance();
