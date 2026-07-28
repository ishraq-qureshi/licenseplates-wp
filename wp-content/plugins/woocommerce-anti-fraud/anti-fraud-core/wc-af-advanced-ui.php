<?php
/**
 * Reusable “advanced settings” disclosure for Anti-Fraud admin screens.
 *
 * @package WooCommerce_Anti_Fraud
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Open an advanced settings region (sibling content should be form fields/tables).
 *
 * @param string      $id_suffix   Unique suffix for button/panel IDs (letters, numbers, hyphen).
 * @param string|null $button_label Optional button label.
 */
function wc_af_advanced_panel_start( $id_suffix, $button_label = null ) {
	$uid = 'wc-af-adv-' . preg_replace( '/[^a-z0-9_-]/i', '', $id_suffix );

	if ( null === $button_label ) {
		$button_label = __( 'Advanced options', 'woocommerce-anti-fraud' );
	}

	$hide_label = __( 'Hide advanced options', 'woocommerce-anti-fraud' );
	?>
	<div class="wc-af-advanced-wrap wc-af-advanced-wrap--panel">
		<div class="wc-af-advanced-wrap__head">
			<div class="wc-af-advanced-wrap__head-main">
				<span class="wc-af-badge wc-af-badge--advanced"><?php esc_html_e( 'Advanced', 'woocommerce-anti-fraud' ); ?></span>
				<p class="wc-af-advanced-wrap__hint wc-af-advanced-wrap__lead"><?php esc_html_e( 'Optional—open when you need finer tuning, edge cases, or less common integrations.', 'woocommerce-anti-fraud' ); ?></p>
			</div>
		</div>
		<button type="button" class="button button-secondary wc-af-btn wc-af-btn--secondary wc-af-advanced-toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $uid ); ?>" data-wc-af-label-show="<?php echo esc_attr( $button_label ); ?>" data-wc-af-label-hide="<?php echo esc_attr( $hide_label ); ?>">
			<?php echo esc_html( $button_label ); ?>
		</button>
		<div class="wc-af-advanced-panel" id="<?php echo esc_attr( $uid ); ?>" hidden>
	<?php
}

/**
 * Close the region opened by {@see wc_af_advanced_panel_start()}.
 */
function wc_af_advanced_panel_end() {
	?>
		</div>
	</div>
	<?php
}
