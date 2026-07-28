<?php
/**
 * Single calm admin notice nudging merchants to open Anti-Fraud settings when setup is incomplete.
 *
 * @package WooCommerce_Anti_Fraud
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Consolidated setup notice.
 */
class WC_AF_Consolidated_Setup_Notice {

	const SNOOZE_OPTION = 'wc_af_setup_notice_snooze_until';

	/**
	 * Singleton.
	 *
	 * @return self
	 */
	public static function instance() {
		static $inst = null;
		if ( null === $inst ) {
			$inst = new self();
		}
		return $inst;
	}

	/**
	 * WC_AF_Consolidated_Setup_Notice constructor.
	 */
	private function __construct() {
		add_action( 'admin_notices', array( $this, 'render' ), 5 );
		add_action( 'wp_ajax_wc_af_snooze_setup_notice', array( $this, 'ajax_snooze' ) );
	}

	/**
	 * Default Anti-Fraud settings section (overview): control center already shows status + recommended steps.
	 *
	 * @return bool
	 */
	protected function is_antifraud_overview_screen() {
		if ( ! is_admin() ) {
			return false;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen detection.
		$page    = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$tab     = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
		return 'wc-settings' === $page && 'wc_af' === $tab && '' === $section;
	}

	/**
	 * Whether the protection-level step still needs a choice (aligned with legacy onboarding rules).
	 *
	 * @return bool
	 */
	protected function needs_protection_level_choice() {
		if ( get_option( 'wc_af_default_protection_level' ) ) {
			return false;
		}

		$first_installation = get_option( 'wc_af_first_installation' );
		$settings_manual    = get_option( 'wc_af_is_settings_saved_manually' );
		$protection_closed  = get_option( 'wc_af_default_protection_closed' );
		$tab                = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

		if ( $first_installation && ! $settings_manual ) {
			return true;
		}

		if ( 'wc_af' === $tab && $protection_closed ) {
			return true;
		}

		return false;
	}

	/**
	 * Checkout CAPTCHA / Turnstile not enabled.
	 *
	 * @return bool
	 */
	protected function needs_checkout_protection() {
		return get_option( 'wc_af_recaptcha_enable_captcha', 'no' ) !== 'yes';
	}

	/**
	 * Order-attempt (card attack) limits off.
	 *
	 * @return bool
	 */
	protected function needs_card_attack_limits() {
		return get_option( 'wc_af_attempt_count_check', 'yes' ) !== 'yes';
	}

	/**
	 * Snooze active (dismissed for a period).
	 *
	 * @return bool
	 */
	protected function is_snoozed() {
		$until = (int) get_option( self::SNOOZE_OPTION, 0 );
		return $until > time();
	}

	/**
	 * Setup gaps used only to decide if the notice should show (details belong in settings UI).
	 *
	 * @return string[]
	 */
	public function get_setup_gaps() {
		$gaps = array();

		if ( $this->needs_protection_level_choice() ) {
			$gaps[] = __( 'Choose a protection level', 'woocommerce-anti-fraud' );
		}

		if ( $this->needs_card_attack_limits() ) {
			$gaps[] = __( 'Enable order attempt limits', 'woocommerce-anti-fraud' );
		}

		if ( $this->needs_checkout_protection() ) {
			$gaps[] = __( 'Enable Checkout CAPTCHA', 'woocommerce-anti-fraud' );
		}

		return $gaps;
	}

	/**
	 * Render a single onboarding notice when setup is incomplete and not snoozed.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( $this->is_snoozed() ) {
			return;
		}

		if ( $this->is_antifraud_overview_screen() ) {
			return;
		}

		$gaps = $this->get_setup_gaps();
		if ( empty( $gaps ) ) {
			return;
		}

		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=wc_af' );
		$nonce        = wp_create_nonce( 'wc_af_snooze_setup_notice' );

		?>
		<div class="notice notice-info wc-af-setup-notice is-dismissible" data-wc-af-setup-notice="1">
			<p>
				<strong><?php esc_html_e( 'Complete your Anti-Fraud setup', 'woocommerce-anti-fraud' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'Anti-Fraud includes default rules to help you get started. Review your settings to make sure protection is configured appropriately for your store.', 'woocommerce-anti-fraud' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Open Anti-Fraud settings', 'woocommerce-anti-fraud' ); ?></a>
			</p>
		</div>
		<script type="text/javascript">
		(function() {
			document.addEventListener('DOMContentLoaded', function() {
				var n = document.querySelector('[data-wc-af-setup-notice]');
				if (!n || typeof jQuery === 'undefined') return;
				jQuery(n).on('click', '.notice-dismiss', function() {
					jQuery.post(ajaxurl, {
						action: 'wc_af_snooze_setup_notice',
						nonce: '<?php echo esc_js( $nonce ); ?>'
					});
				});
			});
		})();
		</script>
		<?php
	}

	/**
	 * Snooze notice for 7 days after dismiss.
	 */
	public function ajax_snooze() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}
		check_ajax_referer( 'wc_af_snooze_setup_notice', 'nonce' );
		update_option( self::SNOOZE_OPTION, time() + 7 * DAY_IN_SECONDS, false );
		wp_send_json_success();
	}
}
