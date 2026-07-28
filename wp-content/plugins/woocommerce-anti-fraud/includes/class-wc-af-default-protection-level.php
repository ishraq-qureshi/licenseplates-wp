<?php
/**
 * All Default prevention level codebase here
 */
if ( ! class_exists( 'WC_AF_Default_Protection_Level' ) ) {
	class WC_AF_Default_Protection_Level {

		protected static $_instance = null;

		public function __construct() {
			add_action('admin_footer', array( $this, 'custom_admin_notice_with_modal' ) );
			add_action('wp_ajax_set_protection_level', array( $this, 'handle_protection_level_ajax' ) );

			add_action('wp_ajax_set_protection_closed', array( $this, 'handle_protection_closed_ajax' ) );
			add_action('admin_init', array( $this, 'default_settings_option_visite_rule_tab' ) );
		}

		public function default_settings_option_visite_rule_tab() {
			$visite_first_time = get_option('visite_rule_tab_default_settings_saved');
			$section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';
			if ('rules' === $section) {

				if ('true' === $visite_first_time) {
					return true;
				}
		
				if ('true' !== $visite_first_time) {
					$this->default_settings_save_visite_rule_tab();
					update_option('visite_rule_tab_default_settings_saved', 'true');
				}
			}
			return;
		}

		public function default_settings_save_visite_rule_tab() {
			//check_ajax_referer('set_protection_nonce', 'nonce');
			update_option( 'wc_af_ai_fraud_prevention_check', 'no' );
			update_option( 'user_verified_from_ts', 'no' );
			// For Minfraud.
			update_option( 'wc_settings_anti_fraud_auto_check_days', 7 );
			update_option( 'wc_af_fraud_check_before_payment', 'no' );
			update_option( 'wc_af_fraud_update_state', 'yes' );

			update_option( 'wc_af_enable_whitelist_payment_method', 'no' );
			update_option( 'wc_settings_anti_fraud_minfraud_order_weight', 30 );
			update_option( 'wc_settings_anti_fraud_minfraud_risk_score', 30 );

			update_option( 'wc_af_email_notification', 'no' );
			update_option( 'wc_settings_anti_fraud_cancel_score', 90 );
			update_option( 'wc_settings_anti_fraud_hold_score', 70 );
			update_option( 'wc_settings_anti_fraud_email_score', 50 );
			update_option( 'wc_settings_anti_fraud_email_score1', 51 );
			update_option( 'wc_settings_anti_fraud_low_risk_threshold', 25 );
			update_option( 'wc_settings_anti_fraud_higher_risk_threshold', 75 );
			update_option( 'wc_af_first_order', 'yes' );
			update_option( 'wc_settings_anti_fraud_first_order_weight', 5 );
			update_option( 'wc_af_first_order_custom', 'no' );
			update_option( 'wc_settings_anti_fraud_first_order_custom_weight', 5 );
			update_option( 'wc_af_geolocation_order', 'no' );
			update_option( 'wc_settings_anti_fraud_geolocation_order_weight', 5 );
			// PLUGINS-195 Start
			update_option( 'wc_af_email_rate_limit_enable', 'no' );
			update_option( 'wc_af_email_rate_limit_max', 5 );
			update_option( 'wc_af_email_rate_limit_window', 30 );
			// PLUGINS-195 END

			update_option( 'wc_af_international_order', 'yes' );
			update_option( 'wc_settings_anti_fraud_international_order_weight', 10 );
			update_option( 'wc_af_ip_geolocation_order', 'yes' );
			update_option( 'wc_af_billing_phone_number_order', 'no' );
			update_option( 'wc_settings_anti_fraud_billing_phone_number_order_weight', 15 );
			update_option( 'wc_settings_anti_fraud_ip_geolocation_order_weight', 50 );
			update_option( 'wc_af_bca_order', 'yes' );
			update_option( 'wc_settings_anti_fraud_bca_order_weight', 20 );
			update_option( 'wc_af_proxy_order', 'yes' );
			update_option( 'wc_settings_anti_fraud_proxy_order_weight', 50 );
			update_option( 'wc_af_suspecius_email', 'yes' );
			update_option( 'wc_settings_anti_fraud_suspecious_email_weight', 5 );
			update_option( 'wc_settings_anti_fraud_suspecious_email_domains', $this->suspicious_domains() );
			update_option( 'wc_af_unsafe_countries', 'yes' );
			update_option( 'wc_settings_anti_fraud_unsafe_countries_weight', 25 );
			update_option( 'wc_af_order_avg_amount_check', 'yes' );
			update_option( 'wc_settings_anti_fraud_order_avg_amount_weight', 15 );
			update_option( 'wc_settings_anti_fraud_avg_amount_multiplier', 2 );
			update_option( 'wc_af_order_amount_check', 'yes' );
			update_option( 'wc_settings_anti_fraud_order_amount_weight', 5 );
			update_option( 'wc_settings_anti_fraud_amount_limit', 10000 );
			update_option( 'wc_af_attempt_count_check', 'yes' );
			update_option( 'wc_settings_anti_fraud_order_attempt_weight', 25 );
			update_option( 'wc_settings_anti_fraud_attempt_time_span', 24 );
			update_option( 'wc_settings_anti_fraud_max_order_attempt_time_span', 5 );

			update_option( 'wc_af_limit_order_count', 'no' );
			delete_option( 'wc_af_limit_time_start', 24 );
			delete_option( 'wc_af_limit_time_end', 24 );
			update_option( 'wc_af_allowed_order_limit', 2 );
			update_option( 'wc_settings_anti_fraud_max_order_payment_attempt', 2 );
			update_option( 'wc_af_ip_multiple_check', 'yes' );

			update_option( 'wc_settings_anti_fraud_ip_multiple_weight', 25 );
			update_option( 'wc_settings_anti_fraud_ip_multiple_time_span', 30 );
			update_option( 'wc_settings_anti_fraudenable_automatic_email_blacklist', 'yes' );
			update_option( 'wc_settings_anti_fraudenable_automatic_blacklist', 'yes' );
			update_option( 'wc_af_paypal_verification', 'yes' );
			update_option( 'wc_af_paypal_prevent_downloads', 'yes' );
			update_option( 'wc_settings_anti_fraud_time_paypal_attempts', 2 );
			update_option( 'wc_settings_anti_fraud_paypal_email_format', 'html' );
			update_option( 'wc_settings_anti_fraud_paypal_email_subject', $this->paypal_email_subject() );
			update_option( 'wc_settings_anti_fraud_email_body', $this->paypal_email_body() );
		}

		// Add modal and logic
		public function custom_admin_notice_with_modal() {
			global $pagenow;

			$is_settings_saved   = get_option( 'wc_af_is_settings_saved' );
			$protection_level    = get_option( 'wc_af_default_protection_level' );
			$protection_closed   = get_option( 'wc_af_default_protection_closed' );
			$settings_saved_manually  = get_option( 'wc_af_is_settings_saved_manually' );
			$first_installation  = get_option( 'wc_af_first_installation' );
			$nonce               = wp_create_nonce( 'set_protection_nonce' );

			$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';

			// CASE 1: Show modal automatically on first visit to antifraud settings
			$show_modal_auto = false;
			if ( $first_installation && 'wc_af' === $tab && !$is_settings_saved) {
				$show_modal_auto = true;
			}

			// CASE 2: Protection-level prompt is shown via WC_AF_Consolidated_Setup_Notice (admin_notices).
			?>

	<!-- Modal HTML -->
	<div id="protection-modal" style="display:none;">
		<div id="protection-modal-content">
			<span id="close-protection-modal">&times;</span>
			<h2 style="text-align: center;"><?php esc_html_e( 'Select the Default Protection Level', 'swift-antifraud' ); ?></h2>

			<div class="protection-buttons" id="protection-buttons">
				<button class="protection-btn" data-level="low"><?php esc_html_e( 'Low', 'swift-antifraud' ); ?></button>
				<button class="protection-btn" data-level="medium"><?php esc_html_e( 'Medium', 'swift-antifraud' ); ?></button>
				<button class="protection-btn" data-level="high"><?php esc_html_e( 'High', 'swift-antifraud' ); ?></button>
				<p style="text-align: center;"><?php esc_html_e( "This will configure the Antifraud plugin's rules based on your selected protection level. You can adjust individual rules later if needed.", 'swift-antifraud' ); ?></p>
			</div>
			
			<div id="thank-you-message" style="display:none; text-align:center; margin-top:20px;">
				<img src="https://cdn-icons-png.flaticon.com/512/845/845646.png" alt="Success" style="width: 60px; height: 60px; margin-bottom: 10px;" />
				<div style="font-weight: bold; color: green;">
				<?php esc_html_e( 'Thank you! Your protection level has been set.', 'swift-antifraud' ); ?>
				</div>
			</div>
		</div>
	</div>

	<script>
		var protectionNonce   = "<?php echo esc_js( $nonce ); ?>";
		var shouldShowModal   = <?php echo $show_modal_auto ? 'true' : 'false'; ?>;
		var settingsUrl       = "<?php echo esc_url_raw( admin_url( 'admin.php?page=wc-settings&tab=wc_af&section=rules' ) ); ?>";

		document.addEventListener("DOMContentLoaded", function() {
			if (shouldShowModal) {
				document.getElementById("protection-modal").style.display = "block";
			}
			var trigger = document.getElementById("open-protection-modal");
			if (trigger) {
				trigger.addEventListener("click", function(e) {
					e.preventDefault();
					document.getElementById("protection-modal").style.display = "block";
				});
			}
		});
	</script>

	<style>
		#protection-modal {
			position: fixed;
			z-index: 9999;
			left: 0; top: 0;
			width: 100%; height: 100%;
			background-color: rgba(0,0,0,0.5);
		}
		#protection-modal-content {
			background: #fff;
			width: 400px;
			margin: 10% auto;
			padding: 30px;
			position: relative;
			border-radius: 10px;
			box-shadow: 0 10px 25px rgba(0,0,0,0.3);
			font-family: sans-serif;
		}
		#close-protection-modal {
			position: absolute;
			top: 12px;
			right: 18px;
			font-size: 22px;
			color: #888;
			cursor: pointer;
		}
		.protection-buttons { text-align: center; margin-top: 20px; }
		.protection-btn {
			padding: 10px 20px;
			margin: 0 10px;
			font-size: 15px;
			border: none;
			border-radius: 6px;
			cursor: pointer;
			color: white;
		}
		.protection-btn[data-level="low"]    { background-color: #4CAF50; }
		.protection-btn[data-level="medium"] { background-color: #ff9800; }
		.protection-btn[data-level="high"]   { background-color: #f44336; }
		.protection-btn:hover { opacity: 0.85; }
	</style>
	<?php
		}



		public function handle_protection_level_ajax() {

			$is_settings_saved   = get_option( 'wc_af_is_settings_saved' );
			$protection_level    = get_option( 'wc_af_default_protection_level' );
			$protection_closed   = get_option( 'wc_af_default_protection_closed' );
			$settings_saved_manually  = get_option( 'wc_af_is_settings_saved_manually' );
			$first_installation  = get_option( 'wc_af_first_installation' );

			if (!$protection_closed) {
				$this->default_settings_option();
			}

			if (!$protection_level && !$settings_saved_manually) {

				check_ajax_referer('set_protection_nonce', 'nonce');
				$level = isset($_POST['level']) ? sanitize_text_field($_POST['level']) : '';
				update_option('wc_af_default_protection_level', $level);
				delete_option('wc_af_default_protection_closed'); // Reset close flag

					// Define weights per level
				$weights = [
					'low' => [
						'first_order' => 5,
						'international_order' => 5,
						'bca_order' => 5,
						'billing_phone_number_order' => 5,
						'proxy_order' => 10,
						'suspecius_email' => 5,
						'unsafe_countries' => 15,
						'order_avg_amount' => 20,
						'order_amount' => 25,
						'attempt_count' => 25,
						'ip_multiple' => 5,
					],
					'medium' => [
						'first_order' => 10,
						'international_order' => 10,
						'bca_order' => 25,
						'billing_phone_number_order' => 15,
						'proxy_order' => 70,
						'suspecius_email' => 10,
						'unsafe_countries' => 65,
						'order_avg_amount' => 70,
						'order_amount' => 75,
						'attempt_count' => 60,
						'ip_multiple' => 15,
					],
					'high' => [
						'first_order' => 90,
						'international_order' => 95,
						'bca_order' => 90,
						'billing_phone_number_order' => 85,
						'proxy_order' => 100,
						'suspecius_email' => 100,
						'unsafe_countries' => 95,
						'order_avg_amount' => 90,
						'order_amount' => 80,
						'attempt_count' => 90,
						'ip_multiple' => 95,
					]
				];

				// Handle only valid levels
				if (!isset($weights[$level])) {
					return;
				}

				$selected = $weights[$level];

				// Apply all rules
				foreach ($selected as $rule => $weight) {
					update_option("wc_af_{$rule}", 'yes');
					update_option("wc_settings_anti_fraud_{$rule}_weight", $weight);
				}
				update_option('wc_settings_anti_fraud_max_order_attempt_time_span', ( 'low' === $level ? 3 : 5 ) );
				$protection_closed   = get_option( 'wc_af_default_protection_closed' );
				
				update_option( 'wc_af_is_settings_saved', true);

				wp_send_json_success(['message' => 'Protection level set to ' . $level]);
			} else {
				wp_send_json_error( [ 'message' => 'Something went wrong' ] );
			}
		}

		public function handle_protection_closed_ajax() {

			$is_settings_saved   = get_option( 'wc_af_is_settings_saved' );

			$settings_saved_manually  = get_option( 'wc_af_is_settings_saved_manually' );
			$first_installation  = get_option( 'wc_af_first_installation' );

			if ( !$is_settings_saved && $first_installation) {
				update_option('wc_af_default_protection_closed', true);
				update_option( 'wc_af_is_settings_saved', true);
				$this->default_settings_option();
				wp_send_json_success(['message' => 'Modal closed without selection']);
			}
		}

		public function default_settings_option() {
			check_ajax_referer('set_protection_nonce', 'nonce');
				update_option( 'wc_af_ai_fraud_prevention_check', 'no' );
				update_option( 'user_verified_from_ts', 'no' );
				// For Minfraud.
				update_option( 'wc_settings_anti_fraud_auto_check_days', 7 );
				update_option( 'wc_af_fraud_check_before_payment', 'no' );
				update_option( 'wc_af_fraud_update_state', 'yes' );

				update_option( 'wc_af_enable_whitelist_payment_method', 'no' );
				update_option( 'wc_settings_anti_fraud_minfraud_order_weight', 30 );
				update_option( 'wc_settings_anti_fraud_minfraud_risk_score', 30 );

				update_option( 'wc_af_email_notification', 'no' );
				update_option( 'wc_settings_anti_fraud_cancel_score', 90 );
				update_option( 'wc_settings_anti_fraud_hold_score', 70 );
				update_option( 'wc_settings_anti_fraud_email_score', 50 );
				update_option( 'wc_settings_anti_fraud_email_score1', 51 );
				update_option( 'wc_settings_anti_fraud_low_risk_threshold', 25 );
				update_option( 'wc_settings_anti_fraud_higher_risk_threshold', 75 );
				update_option( 'wc_af_first_order', 'yes' );
				update_option( 'wc_settings_anti_fraud_first_order_weight', 5 );
				update_option( 'wc_af_international_order', 'yes' );
				update_option( 'wc_settings_anti_fraud_international_order_weight', 10 );
				update_option( 'wc_af_ip_geolocation_order', 'yes' );
				update_option( 'wc_af_billing_phone_number_order', 'no' );
				update_option( 'wc_settings_anti_fraud_billing_phone_number_order_weight', 15 );
				update_option( 'wc_settings_anti_fraud_ip_geolocation_order_weight', 50 );
				update_option( 'wc_af_bca_order', 'yes' );
				update_option( 'wc_settings_anti_fraud_bca_order_weight', 20 );
				update_option( 'wc_af_proxy_order', 'yes' );
				update_option( 'wc_settings_anti_fraud_proxy_order_weight', 50 );
				update_option( 'wc_af_suspecius_email', 'yes' );
				update_option( 'wc_settings_anti_fraud_suspecious_email_weight', 5 );
				update_option( 'wc_settings_anti_fraud_suspecious_email_domains', $this->suspicious_domains() );
				update_option( 'wc_af_unsafe_countries', 'yes' );
				update_option( 'wc_settings_anti_fraud_unsafe_countries_weight', 25 );
				update_option( 'wc_af_order_avg_amount_check', 'yes' );
				update_option( 'wc_settings_anti_fraud_order_avg_amount_weight', 15 );
				update_option( 'wc_settings_anti_fraud_avg_amount_multiplier', 2 );
				update_option( 'wc_af_order_amount_check', 'yes' );
				update_option( 'wc_settings_anti_fraud_order_amount_weight', 5 );
				update_option( 'wc_settings_anti_fraud_amount_limit', 10000 );
				update_option( 'wc_af_attempt_count_check', 'yes' );
				update_option( 'wc_settings_anti_fraud_order_attempt_weight', 25 );
				update_option( 'wc_settings_anti_fraud_attempt_time_span', 24 );
				update_option( 'wc_settings_anti_fraud_max_order_attempt_time_span', 5 );

				update_option( 'wc_af_limit_order_count', 'no' );
				delete_option( 'wc_af_limit_time_start', 24 );
				delete_option( 'wc_af_limit_time_end', 24 );
				update_option( 'wc_af_allowed_order_limit', 2 );
				update_option( 'wc_settings_anti_fraud_max_order_payment_attempt', 2 );
				update_option( 'wc_af_ip_multiple_check', 'yes' );

				update_option( 'wc_settings_anti_fraud_ip_multiple_weight', 25 );
				update_option( 'wc_settings_anti_fraud_ip_multiple_time_span', 30 );
				update_option( 'wc_settings_anti_fraudenable_automatic_email_blacklist', 'yes' );
				update_option( 'wc_settings_anti_fraudenable_automatic_blacklist', 'yes' );
				update_option( 'wc_af_paypal_verification', 'yes' );
				update_option( 'wc_af_paypal_prevent_downloads', 'yes' );
				update_option( 'wc_settings_anti_fraud_time_paypal_attempts', 2 );
				update_option( 'wc_settings_anti_fraud_paypal_email_format', 'html' );
				update_option( 'wc_settings_anti_fraud_paypal_email_subject', $this->paypal_email_subject() );
				update_option( 'wc_settings_anti_fraud_email_body', $this->paypal_email_body() );
		}

		public function suspicious_domains() {
			$email_domains = array(
				'hotmail',
				'live',
				'gmail',
				'yahoo',
				'mail',
				'123vn',
				'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijk',
				'aaemail.com',
				'webmail.aol',
				'postmaster.info.aol',
				'personal',
				'atgratis',
				'aventuremail',
				'byke',
				'lycos',
				'computermail',
				'dodgeit',
				'thedoghousemail',
				'doramail',
				'e-mailanywhere',
				'eo.yifan',
				'earthlink',
				'emailaccount',
				'zzn',
				'everymail',
				'excite',
				'expatmail',
				'fastmail',
				'flashmail',
				'fuzzmail',
				'galacmail',
				'godmail',
				'gurlmail',
				'howlermonkey',
				'hushmail',
				'icqmail',
				'indiatimes',
				'juno',
				'katchup',
				'kukamail',
				'mail',
				'mail2web',
				'mail2world',
				'mailandnews',
				'mailinator',
				'mauimail',
				'meowmail',
				'merawalaemail',
				'muchomail',
				'MyPersonalEmail',
				'myrealbox',
				'nameplanet',
				'netaddress',
				'nz11',
				'orgoo',
				'phat.co',
				'probemail',
				'prontomail',
				'rediff',
				'returnreceipt',
				'synacor',
				'walkerware',
				'walla',
				'wongfaye',
				'xasamail',
				'zapak',
				'zappo',
			);

			return implode( ',', $email_domains );
		}

		public function paypal_email_body() {
			/* translators: 1. start of link, 2. end of link. */
			return sprintf( esc_html__( 'Hi! We have received your order on %1$s, but to complete it, we have to verify your PayPal email address. If you haven`t made or authorized any purchase, please, contact PayPal support immediately, and email us at %2$s .', 'woocommerce-anti-fraud' ), get_site_url(), get_option( 'admin_email' ) );
		}

		public function paypal_email_subject() {
			/* translators: 1. start of link. */
			return sprintf( esc_html__( '%1$s Confirm your PayPal email address.', 'woocommerce-anti-fraud' ), get_bloginfo( 'name' ) );
		}

		/**
		 * Get the singleton instance of WC_AF_Mobile_Verification.
		 *
		 * @return WC_AF_Mobile_Verification
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}
	}
}

WC_AF_Default_Protection_Level::instance();
