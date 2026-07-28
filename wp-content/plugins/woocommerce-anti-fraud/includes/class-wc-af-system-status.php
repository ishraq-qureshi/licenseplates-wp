<?php
/**
 * Anti Fraud System Status Integration
 *
 * Adds Anti Fraud plugin settings to WooCommerce System Status report
 *
 * @package WooCommerce Anti Fraud
 * @since 7.1.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class WC_AF_System_Status
 */
class WC_AF_System_Status {

	/**
	 * The singleton instance of the class.
	 *
	 * @var null|WC_AF_System_Status
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return WC_AF_System_Status
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'woocommerce_system_status_report', array( $this, 'render_system_status_section' ) );
	}

	/**
	 * Render the Anti Fraud section in WooCommerce System Status
	 */
	public function render_system_status_section() {
		$settings = $this->get_all_settings();
		$first_section = true;
		
		?>
		<table class="wc_status_table widefat" cellspacing="0" id="status">
			<thead>
				<tr>
					<th colspan="3" data-export-label="Anti-Fraud settings">
						<h2><?php esc_html_e( 'Anti-Fraud settings', 'woocommerce-anti-fraud' ); ?></h2>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $settings as $section_title => $section_settings ) : ?>
					<?php if ( ! $first_section ) : ?>
						<tr>
							<td colspan="3" style="padding: 0; height: 10px;">&nbsp;</td>
						</tr>
					<?php endif; ?>
					<?php $first_section = false; ?>
					<tr>
						<td colspan="3">
							<strong><?php echo esc_html( $section_title ); ?></strong>
						</td>
					</tr>
					<?php foreach ( $section_settings as $label => $value ) : ?>
						<tr>
							<td data-export-label="<?php echo esc_attr( $label ); ?>"><?php echo esc_html( $label ); ?>:</td>
							<td class="help">&nbsp;</td>
							<td><?php echo wp_kses_post( $this->format_value( $value ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Format setting value for display
	 *
	 * @param mixed $value The setting value
	 * @return string Formatted value
	 */
	private function format_value( $value ) {
		if ( is_array( $value ) ) {
			if ( empty( $value ) ) {
				return '<span class="dashicons dashicons-no-alt"></span> ' . esc_html__( 'None', 'woocommerce-anti-fraud' );
			}
			return implode( ', ', array_map( 'esc_html', $value ) );
		}

		if ( 'yes' === $value ) {
			return '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
		}

		if ( 'no' === $value || empty( $value ) ) {
			return '<span class="dashicons dashicons-no-alt"></span>';
		}

		// Mask sensitive data (API keys, passwords)
		if ( is_string( $value ) && strlen( $value ) > 10 && ! ctype_digit( $value ) ) {
			// Check if it looks like an API key or license key
			if ( strpos( strtolower( $value ), 'sk_' ) === 0 || 
				 strpos( strtolower( $value ), 'pk_' ) === 0 ||
				 preg_match( '/^[a-zA-Z0-9]{20,}$/', $value ) ) {
				return str_repeat( '*', strlen( $value ) - 4 ) . substr( $value, -4 );
			}
		}

		return esc_html( $value );
	}

	/**
	 * Get all Anti Fraud settings organized by section
	 * Organized by actual plugin tabs for easier reference
	 *
	 * @return array
	 */
	private function get_all_settings() {
		$settings = array();

		// Section: General Settings
		$settings['Section: General Settings'] = array(
			'Protection Level' => get_option( 'wc_af_default_protection_level', 'Not Set' ),
			'Low Risk Threshold' => get_option( 'wc_settings_anti_fraud_low_risk_threshold', '25' ),
			'Medium Risk Threshold' => get_option( 'wc_settings_anti_fraud_medium_risk_threshold', '50' ),
			'Higher Risk Threshold' => get_option( 'wc_settings_anti_fraud_higher_risk_threshold', '75' ),
			'Cancel Score' => get_option( 'wc_settings_anti_fraud_cancel_score', '100' ),
			'Enable Custom Order Status' => get_option( 'wc_af_fraud_custom_order_status', 'no' ),
			'Enable Email Notifications' => get_option( 'wc_settings_anti_fraud_email_notifications', 'no' ),
			'Notification Email' => get_option( 'wc_settings_anti_fraud_notify_email', get_option( 'admin_email' ) ),
		);

		// Section: Card Attacks
		$settings['Section: Card Attacks'] = array(
			'Enable order attempt limits (card attacks)' => get_option( 'wc_af_card_attack_protection', 'no' ),
			'Max Failed Attempts' => get_option( 'wc_af_card_attack_max_attempts', '5' ),
			'Time Window (minutes)' => get_option( 'wc_af_card_attack_time_window', '60' ),
		);

		// Section: Cleanup
		$settings['Section: Cleanup'] = array(
			'Auto Cleanup Failed Orders' => get_option( 'wc_af_auto_cleanup_failed_orders', 'no' ),
			'Cleanup Timeframe' => get_option( 'wc_af_cleanup_timeframe', 'Not Set' ),
		);

		// Section: AI Fraud Prevention
		$ai_api_key = get_option( 'wc_af_ai_fraud_prevention_api_key', '' );
		$settings['Section: AI Fraud Prevention'] = array(
			'Enable AI Fraud Prevention' => get_option( 'wc_af_ai_fraud_prevention_type', 'no' ),
			'API Key Status' => ! empty( $ai_api_key ) ? 'Set' : 'Not Set',
			'AI Model' => get_option( 'wc_af_ai_fraud_prevention_model', 'Not Set' ),
			'AI Score Threshold' => get_option( 'wc_settings_anti_fraud_ai_fraud_prevention_score', '0' ),
		);

		// Section: Rules (Fraud Detection Rules)
		$settings['Section: Rules'] = array(
			'First Order Check' => $this->format_rule_status( 'wc_af_first_order_check', 'wc_settings_anti_fraud_first_order_weight' ),
			'International Order Check' => $this->format_rule_status( 'wc_settings_anti_fraud_international_order_check', 'wc_settings_anti_fraud_international_order_weight' ),
			'High Order Amount Check' => $this->format_rule_status( 'wc_settings_anti_fraud_amount_check', 'wc_settings_anti_fraud_order_amount_weight' ),
			'High Average Order Check' => $this->format_rule_status( 'wc_settings_anti_fraud_avg_amount_check', 'wc_settings_anti_fraud_order_avg_amount_weight' ),
			'Proxy Detection Check' => $this->format_rule_status( 'wc_settings_anti_fraud_proxy_order_check', 'wc_settings_anti_fraud_proxy_order_weight' ),
			'Suspicious Email Check' => $this->format_rule_status( 'wc_settings_anti_fraud_suspecious_email_check', 'wc_settings_anti_fraud_suspecious_email_weight' ),
			'Unsafe Countries Check' => $this->format_rule_status( 'wc_settings_anti_fraud_unsafe_countries_check', 'wc_settings_anti_fraud_unsafe_countries_weight' ),
			'Billing/Shipping Match Check' => $this->format_rule_status( 'wc_settings_anti_fraud_bca_check', 'wc_settings_anti_fraud_bca_order_weight' ),
			'Phone Number Validation' => $this->format_rule_status( 'wc_settings_anti_fraud_billing_phone_number_check', 'wc_settings_anti_fraud_billing_phone_number_order_weight' ),
			'Attempt Count Check' => $this->format_rule_status( 'wc_af_attempt_count_check', 'wc_settings_anti_fraud_attempt_count_weight' ),
			'IP Multiple Orders Check' => $this->format_rule_status( 'wc_af_ip_multiple_check', 'wc_settings_anti_fraud_ip_multiple_weight' ),
			'Velocity Checks' => get_option( 'wc_af_enable_velocity_checks', 'no' ),
			'Velocity Time Span (minutes)' => get_option( 'wc_af_velocity_time_span', '60' ),
			'Max Orders in Time Span' => get_option( 'wc_af_max_orders_in_time_span', '5' ),
		);

		// Section: Whitelist Settings
		$whitelist_emails = get_option( 'wc_settings_anti_fraud_whitelist', '' );
		$whitelist_ips = get_option( 'wc_settings_anti_fraud_ips_whitelist', '' );
		$whitelist_roles = get_option( 'wc_af_whitelist_user_roles', array() );
		$whitelist_phones = get_option( 'wc_af_whitelist_phone_numbers', '' );
		$whitelist_payment_methods = get_option( 'wc_settings_anti_fraud_whitelist_payment_method', array() );
		
		$settings['Section: Whitelist Settings'] = array(
			'Whitelisted Emails' => ! empty( $whitelist_emails ) ? count( explode( "\n", trim( $whitelist_emails ) ) ) . ' email(s)' : 'None',
			'Whitelisted IP Addresses' => ! empty( $whitelist_ips ) ? count( explode( "\n", trim( $whitelist_ips ) ) ) . ' IP(s)' : 'None',
			'Enable User Role Whitelist' => get_option( 'wc_af_enable_whitelist_user_roles', 'no' ),
			'Whitelisted User Roles' => ! empty( $whitelist_roles ) ? implode( ', ', $whitelist_roles ) : 'None',
			'Enable Payment Method Whitelist' => get_option( 'wc_af_enable_whitelist_payment_method', 'no' ),
			'Whitelisted Payment Methods' => ! empty( $whitelist_payment_methods ) ? count( $whitelist_payment_methods ) . ' method(s)' : 'None',
			'Enable Phone Number Whitelist' => get_option( 'wc_af_enable_whitelist_phone_number', 'no' ),
			'Whitelisted Phone Numbers' => ! empty( $whitelist_phones ) ? count( explode( "\n", trim( $whitelist_phones ) ) ) . ' number(s)' : 'None',
		);

		// Section: Blacklist Settings
		$blacklist_emails = get_option( 'wc_settings_anti_fraudblacklist_emails', '' );
		$blacklist_ips = get_option( 'wc_settings_anti_fraudblacklist_ipaddress', '' );
		$blacklist_phones = get_option( 'wc_af_blacklisted_phone_numbers', '' );
		
		$settings['Section: Blacklist Settings'] = array(
			'Enable Automatic Email Blacklist' => get_option( 'wc_settings_anti_fraudenable_automatic_email_blacklist', 'no' ),
			'Blacklisted Emails' => ! empty( $blacklist_emails ) ? count( explode( "\n", trim( $blacklist_emails ) ) ) . ' email(s)' : 'None',
			'Blacklisted IP Addresses' => ! empty( $blacklist_ips ) ? count( explode( "\n", trim( $blacklist_ips ) ) ) . ' IP(s)' : 'None',
			'Blacklisted Phone Numbers' => ! empty( $blacklist_phones ) ? count( explode( "\n", trim( $blacklist_phones ) ) ) . ' number(s)' : 'None',
			'Block Temporary Emails' => get_option( 'wc_settings_anti_fraud_temporary_email_check', 'no' ),
		);

		// Section: Email Alerts
		$settings['Section: Email Alerts'] = array(
			'Email Notifications Enabled' => get_option( 'wc_settings_anti_fraud_email_notifications', 'no' ),
			'Notification Email Address' => get_option( 'wc_settings_anti_fraud_notify_email', get_option( 'admin_email' ) ),
			'Stop Emails on Failed Status' => get_option( 'wc_af_stop_send_mail_failed_status', 'no' ),
		);

		// Section: PayPal Settings
		$settings['Section: PayPal Settings'] = array(
			'Enable PayPal Email Verification' => get_option( 'wc_af_paypal_email_check', 'no' ),
			'PayPal Risk Threshold' => get_option( 'wc_settings_anti_fraud_paypal_prevent_downloads', '0' ),
			'API Fraud Check' => get_option( 'wc_af_api_fraud_check', 'no' ),
			'Throttle API Orders' => get_option( 'wc_af_throttle_api_based_orders_check', 'no' ),
			'Max API Orders Per Hour' => get_option( 'wc_af_max_orders_through_api_per_hour', '0' ),
			'Order Payment Attempt Check' => get_option( 'wc_af_order_payment_attempt_check', 'no' ),
			'Max Payment Attempts' => get_option( 'wc_settings_anti_fraud_max_order_payment_attempt', '0' ),
		);

		// Section: MinFraud Settings
		$maxmind_user = get_option( 'wc_af_maxmind_user', '' );
		$maxmind_key = get_option( 'wc_af_maxmind_license_key', '' );
		
		$settings['Section: MinFraud Settings'] = array(
			'Enable MaxMind minFraud' => get_option( 'wc_af_maxmind_type', 'no' ),
			'Enable Device Tracking' => get_option( 'wc_af_maxmind_device_tracking', 'no' ),
			'MaxMind Account ID' => ! empty( $maxmind_user ) ? $maxmind_user : 'Not Set',
			'MaxMind License Key Status' => ! empty( $maxmind_key ) ? 'Set' : 'Not Set',
			'IP Geolocation Check' => get_option( 'wc_af_ip_geolocation_order', 'no' ),
			'minFraud Risk Score Threshold' => get_option( 'wc_settings_anti_fraud_minfraud_score', '0' ),
		);

		// Section: MinFraud Insights Settings
		$settings['Section: MinFraud Insights Settings'] = array(
			'Enable minFraud Insights' => get_option( 'wc_af_minfraud_insights_type', 'no' ),
			'Insights Risk Score Threshold' => get_option( 'wc_settings_anti_fraud_minfraud_insights_score', '0' ),
		);

		// Section: MinFraud Factors Settings
		$settings['Section: MinFraud Factors Settings'] = array(
			'Enable minFraud Factors' => get_option( 'wc_af_minfraud_factors_type', 'no' ),
			'Factors Risk Score Threshold' => get_option( 'wc_settings_anti_fraud_minfraud_factors_score', '0' ),
		);

		// Section: Trust Swiftly Settings
		$ts_api_key = get_option( 'wc_af_trust_swiftly_api_key', '' );
		$ts_base_url = get_option( 'wc_af_trust_swiftly_base_url', '' );
		
		$settings['Section: Trust Swiftly Settings'] = array(
			'Enable Trust Swiftly' => get_option( 'wc_af_trust_swiftly_type', 'no' ),
			'API Key Status' => ! empty( $ts_api_key ) ? 'Set' : 'Not Set',
			'Base URL' => ! empty( $ts_base_url ) ? $ts_base_url : 'Not Set',
			'Verification Method' => get_option( 'wc_af_trust_swiftly_veri_method', 'Not Set' ),
			'Verification Template' => get_option( 'wc_af_trust_swiftly_veri_template', 'Not Set' ),
			'When To Verify' => get_option( 'wc_af_trust_when_to_verify', 'Not Set' ),
			'Risk Score Threshold' => get_option( 'wc_settings_anti_fraud_strust_swiftly_score', '75' ),
		);

		// Section: reCAPTCHA
		$recaptcha_site_key = get_option( 'wc_af_recaptcha_site_key', '' );
		$recaptcha_secret_key = get_option( 'wc_af_recaptcha_secret_key', '' );
		
		$settings['Section: reCAPTCHA'] = array(
			'Enable reCAPTCHA' => get_option( 'wc_af_recaptcha_enable_captcha', 'no' ),
			'reCAPTCHA Type' => get_option( 'wc_af_recaptcha_type', 'Not Set' ),
			'reCAPTCHA Version' => get_option( 'wc_af_recaptcha_version', 'v2' ),
			'Site Key Status' => ! empty( $recaptcha_site_key ) ? 'Set' : 'Not Set',
			'Secret Key Status' => ! empty( $recaptcha_secret_key ) ? 'Set' : 'Not Set',
			'Show on Login Page' => get_option( 'wc_af_recaptcha_show_on_login', 'no' ),
			'Show on Registration Page' => get_option( 'wc_af_recaptcha_show_on_registration', 'no' ),
			'Show on Checkout Page' => get_option( 'wc_af_recaptcha_show_on_checkout', 'no' ),
		);

		// Section: Chargebacks (if settings exist)
		$settings['Section: Chargebacks'] = array(
			'Chargeback Tracking' => get_option( 'wc_af_chargeback_tracking', 'Not Set' ),
		);
		/**
		 * Fires after Anti-Fraud validation is completed.
		 *
		 * @param int $wc_af_system_status_settings settings being validated.
		 *
		 * @since v7.2.1
		 */
		return apply_filters( 'wc_af_system_status_settings', $settings );
	}

	/**
	 * Format rule status with weight
	 *
	 * @param string $check_option   The option name for the check enable/disable
	 * @param string $weight_option  The option name for the weight
	 * @return string Formatted rule status
	 */
	private function format_rule_status( $check_option, $weight_option ) {
		$enabled = get_option( $check_option, 'no' );
		$weight = get_option( $weight_option, '0' );
		
		if ( 'yes' === $enabled ) {
			return 'yes (Weight: ' . $weight . ')';
		}
		
		return 'no';
	}
}

// Initialize the class
function wc_af_init_system_status() {
	WC_AF_System_Status::get_instance();
}
add_action( 'init', 'wc_af_init_system_status' );
