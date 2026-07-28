<?php
/**
 * Class Settings - Recaptcha
 */

require_once dirname( __DIR__ ) . '/wc-af-advanced-ui.php';

class WC_AF_Settings_Recaptcha extends WC_AF_Settings_Base {

	protected static $_settings_prefix = 'wc_af_recaptcha_';

	public static function get_settings() {

		$settings = array(
			array(
				'name' => __( 'Checkout CAPTCHA', 'woocommerce-anti-fraud' ),
				'type' => 'title',
				'desc' => __( 'Checkout CAPTCHA is part of card attack protection: it adds a human verification step at checkout to slow bots and card testing. Use Google reCAPTCHA v2 or Cloudflare Turnstile. Order and payment limits live under WooCommerce → Settings → Anti-Fraud → Card attacks. Questions? <a href="mailto:support@opmc.biz" target="_top">support@opmc.biz</a>.<hr/>', 'woocommerce-anti-fraud' ),
				'id'   => 'wc_af_recaptch_settings',
			),
			array(
				'id'       => 'wc_af_recaptcha_enable_captcha',
				'title'    => __( 'Require Checkout CAPTCHA at checkout', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => 'wc_af_recaptcha_enable_captcha',
				'default'  => 'no',
				'desc'     => __( 'Shows a challenge before an order can be placed.', 'woocommerce-anti-fraud' ),
				'desc_tip' => __( 'Use during bots or a card attack (card testing); leave on for ongoing protection if it fits your checkout.', 'woocommerce-anti-fraud' ),
			),

			array(
				'title'    => __( 'Checkout CAPTCHA provider', 'woocommerce-anti-fraud' ),
				'desc'     => __( 'Choose Google reCAPTCHA or Cloudflare Turnstile for Checkout CAPTCHA.', 'woocommerce-anti-fraud' ),
				'type'     => 'select',
				'options'  => array(
					'google_recaptcha' => __( 'Google reCAPTCHA', 'woocommerce-anti-fraud' ),
					'cf_turnstile'     => __( 'Cloudflare Turnstile', 'woocommerce-anti-fraud' ),
				),
				'desc_tip' => __( 'Use the matching key fields below for the provider you select.', 'woocommerce-anti-fraud' ),
				'class'    => '',
				'default'  => 'google_recaptcha',
				'id'       => 'wc_af_recaptcha_type',
			),
			array(
				'title'    => __( 'Checkout CAPTCHA position (classic checkout)', 'woocommerce-anti-fraud' ),
				'desc'     => __( 'Where the widget appears on shortcode checkout. If it overlaps a gateway, move it nearer to “Place order”.', 'woocommerce-anti-fraud' ),
				'type'     => 'select',
				'options'  => class_exists( 'WC_AF_Captcha' ) ? WC_AF_Captcha::get_checkout_position_options() : array(),
				'desc_tip' => __( 'Block Checkout uses the WooCommerce Blocks integration—this setting is for classic checkout only.', 'woocommerce-anti-fraud' ),
				'class'    => '',
				'default'  => 'woocommerce_review_order_before_payment',
				'id'       => 'wc_af_captcha_checkout_position',
			),
			array(
				'title'    => __( 'reCAPTCHA v2 site key', 'woocommerce-anti-fraud' ),
				'type'     => 'text',
				'desc_tip' => __( 'Public key—safe to expose in the browser.', 'woocommerce-anti-fraud' ),
				'desc'     => __( 'From <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA admin</a> (v2 checkbox or invisible).', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_recaptcha_site_key',
			),
			array(
				'title'    => __( 'reCAPTCHA v2 secret key', 'woocommerce-anti-fraud' ),
				'type'     => 'text',
				'label'    => '',
				'desc_tip' => __( 'Server-side only—treat like a password.', 'woocommerce-anti-fraud' ),
				'desc'     => __( 'Pair with the site key above from the same reCAPTCHA site.', 'woocommerce-anti-fraud' ),
				'default'  => '',
				'id'       => 'wc_af_recaptcha_secret_key',
			),
			array(
				'title'    => __( 'Turnstile site key', 'woocommerce-anti-fraud' ),
				'type'     => 'text',
				'desc_tip' => __( 'Public key from Cloudflare Turnstile.', 'woocommerce-anti-fraud' ),
				'desc'     => __( 'Create a Turnstile widget in the Cloudflare dashboard and paste the site key here.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_turnstile_site_key',
			),
			array(
				'title'    => __( 'Turnstile secret key', 'woocommerce-anti-fraud' ),
				'type'     => 'text',
				'label'    => '',
				'desc_tip' => __( 'Server-side secret—do not expose publicly.', 'woocommerce-anti-fraud' ),
				'desc'     => __( 'Pair with the Turnstile site key from the same widget.', 'woocommerce-anti-fraud' ),
				'default'  => '',
				'id'       => 'wc_af_turnstile_secret_key',
			),

			array(
				'id'       => 'wc_af_paypal_acp_enabled',
				'title'    => __( 'PayPal checkout hardening', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => 'wc_af_paypal_acp_enabled',
				'default'  => 'no',
				'desc'     => __( 'PayPal is active on your store. Enable this so Checkout CAPTCHA also covers PayPal card flows that can bypass standard checkout.<br/>', 'woocommerce-anti-fraud' ),
				'desc_tip' => __( 'Recommended when PayPal Standard or similar is enabled; reduces card testing via PayPal.', 'woocommerce-anti-fraud' ),
			),

			// ✅ New Advanced Protection Settings
			array(
				'name' => __( 'Advanced: site-wide checkout limits', 'woocommerce-anti-fraud' ),
				'type' => 'title',
				'desc' => __( 'Optional. Caps how many checkout attempts the whole store can receive in a short window—useful during burst attacks or unusual traffic.', 'woocommerce-anti-fraud' ),
				'id'   => 'wc_af_advanced_protection_settings',
			),
			
			array(
				'id'       => 'wc_af_enable_global_rate_limit',
				'title'    => __( 'Enable site-wide checkout rate limit', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'default'  => 'no',
				'desc'     => __( 'Limit total checkout attempts across all visitors in each time window.', 'woocommerce-anti-fraud' ),
				'desc_tip' => __( 'Helps when attackers rotate IPs but still hit checkout from many places at once.', 'woocommerce-anti-fraud' ),
			),
			array(
				'title'    => __( 'Time window (seconds)', 'woocommerce-anti-fraud' ),
				'type'     => 'number',
				'desc_tip' => '',
				'desc'     => __( 'Length of each rolling window (minimum 60 seconds). Default: 60.', 'woocommerce-anti-fraud' ),
				'default'  => '60',
				'id'       => 'wc_af_global_time_limit_max',
				'custom_attributes' => array(
					'min'  => '60',
					'step' => '10',
				),
			),
			array(
				'title'    => __( 'Max checkouts per window', 'woocommerce-anti-fraud' ),
				'type'     => 'number',
				'desc_tip' => '',
				'desc'     => __( 'When checkout attempts in the window exceed this number, further attempts in that window are blocked. Default: 100.', 'woocommerce-anti-fraud' ),
				'default'  => '100',
				'id'       => 'wc_af_global_rate_limit_max',
				'custom_attributes' => array(
					'min'  => '10',
					'step' => '1',
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_advanced_protection_settings',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_recaptch_settings',
			),
		);

		/**
		 * Filters the reCAPTCHA settings for WooCommerce Anti-Fraud.
		 *
		 * @since 1.0.0
		 * @param array $settings The reCAPTCHA settings array.
		 * @return array Filtered reCAPTCHA settings.
		 */
		return apply_filters( 'wc_af_settings_recaptcha', $settings );
	}

	public static function render_fields() {

		$setting_fields         = self::get_setting_fields( self::get_settings() );
		$enable_recaptcha       = get_option( 'wc_af_recaptcha_enable_captcha', 'no' );
		$enable_paypal_acp      = get_option( 'wc_af_paypal_acp_enabled', 'no' );
		$recaptcha_type         = get_option( 'wc_af_recaptcha_type', 'google_recaptcha' );
		$captcha_position       = get_option( 'wc_af_captcha_checkout_position', 'woocommerce_review_order_before_payment' );
		$position_options       = class_exists( 'WC_AF_Captcha' ) ? WC_AF_Captcha::get_checkout_position_options() : array();

		?>
		<section>
			<h2><?php esc_html_e( 'Checkout CAPTCHA', 'woocommerce-anti-fraud' ); ?></h2>

			<div id="wc_af_recaptch_settings-description">
				<?php echo wp_kses_post( $setting_fields['wc_af_recaptch_settings']['desc'] ); ?>
			</div>

			<table class="form-table opmc_wc_af_table">
				<tbody>

				<tr valign="top" class="">
					<th scope="row" class="titledesc">
						<label for="wc_af_recaptcha_enable_captcha">
							<?php echo wp_kses_post( $setting_fields['wc_af_recaptcha_enable_captcha']['title'] ); ?>
							<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_recaptcha_enable_captcha']['desc_tip'] ); ?>"></span>
						</label>
					</th>
					<td class="forminp forminp-checkbox">
						<fieldset>
							<legend class="screen-reader-text"><span><?php echo esc_attr( $setting_fields['wc_af_recaptcha_enable_captcha']['title'] ); ?></span></legend>
							<label for="wc_af_recaptcha_enable_captcha" class="opmc-toggle-control">
								<input name="wc_af_recaptcha_enable_captcha" id="wc_af_recaptcha_enable_captcha" type="checkbox" value="1" <?php checked( $enable_recaptcha, 'yes' ); ?> >
								<span class="opmc-control"></span>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr valign="top" class="<?php echo 'yes' !== $enable_recaptcha ? 'no-display' : ''; ?>">
					<th scope="row" class="titledesc">
						<label for="wc_af_recaptcha_type">
							<?php
							echo wp_kses_post( $setting_fields['wc_af_recaptcha_type']['title'] );
							$description = WC_Admin_Settings::get_field_description( $setting_fields['wc_af_recaptcha_type'] );
							echo wp_kses_post( $description['tooltip_html'] );
							?>
						</label>
					</th>
					<td class="forminp forminp-multiselect" colspan="2">
						<select name="wc_af_recaptcha_type" id="wc_af_recaptcha_type" class="<?php echo esc_attr( $setting_fields['wc_af_recaptcha_type']['class'] ); ?>">
							<?php foreach ( $setting_fields['wc_af_recaptcha_type']['options'] as $key => $value ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( get_option( 'wc_af_recaptcha_type', $setting_fields['wc_af_recaptcha_type']['default'] ) === $key ); ?>><?php echo esc_html( $value ); ?></option>
							<?php endforeach ?>
						</select>

						<?php if ( ! empty( $setting_fields['wc_af_recaptcha_type']['desc'] ) ) : ?>
							<p class="description"><?php echo esc_html( $setting_fields['wc_af_recaptcha_type']['desc'] ); ?></p>
						<?php endif; ?>
					</td>
				</tr>

			<!-- Checkout CAPTCHA Position -->
			<tr valign="top" class="<?php echo 'yes' !== $enable_recaptcha ? 'no-display' : ''; ?>" id="wc_af_captcha_position_row">
				<th scope="row" class="titledesc">
					<label for="wc_af_captcha_checkout_position">
						<?php echo wp_kses_post( $setting_fields['wc_af_captcha_checkout_position']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_captcha_checkout_position']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-multiselect" colspan="2">
					<select name="wc_af_captcha_checkout_position" id="wc_af_captcha_checkout_position">
						<?php foreach ( $position_options as $hook_name => $label ) : ?>
							<option value="<?php echo esc_attr( $hook_name ); ?>" <?php selected( $captcha_position, $hook_name ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( ! empty( $setting_fields['wc_af_captcha_checkout_position']['desc'] ) ) : ?>
						<p class="description"><?php echo wp_kses_post( $setting_fields['wc_af_captcha_checkout_position']['desc'] ); ?></p>
					<?php endif; ?>
				</td>
			</tr>

			<!-- For Google Recaptcha -->
			<tr valign="top" class="<?php echo ( 'yes' === $enable_recaptcha && 'google_recaptcha' === $recaptcha_type ) ? '' : 'no-display'; ?>">
				<th scope="row" class="titledesc">
					<label for="wc_af_recaptcha_site_key">
							<?php
							echo wp_kses_post( $setting_fields['wc_af_recaptcha_site_key']['title'] );
							$description = WC_Admin_Settings::get_field_description( $setting_fields['wc_af_recaptcha_site_key'] );
							echo wp_kses_post( $description['tooltip_html'] );
							?>
						</label>
					</th>
					<td class="forminp forminp-text" colspan="3">
						<input name="wc_af_recaptcha_site_key" id="wc_af_recaptcha_site_key" type="<?php echo esc_attr( $setting_fields['wc_af_recaptcha_site_key']['type'] ); ?>" value="<?php echo esc_attr( get_option( 'wc_af_recaptcha_site_key' ) ); ?>">
						<?php echo wp_kses_post( $description['description'] ); ?>
					</td>
				</tr>
				<tr valign="top" class="<?php echo ( 'yes' === $enable_recaptcha && 'google_recaptcha' === $recaptcha_type ) ? '' : 'no-display'; ?>">
					<th scope="row" class="titledesc">
						<label for="wc_af_recaptcha_secret_key">
							<?php
							echo wp_kses_post( $setting_fields['wc_af_recaptcha_secret_key']['title'] );
							$description = WC_Admin_Settings::get_field_description( $setting_fields['wc_af_recaptcha_secret_key'] );
							echo wp_kses_post( $description['tooltip_html'] );
							?>
						</label>
					</th>
					<td class="forminp forminp-text" colspan="3">
						<input name="wc_af_recaptcha_secret_key" id="wc_af_recaptcha_secret_key" type="<?php echo esc_attr( $setting_fields['wc_af_recaptcha_secret_key']['type'] ); ?>" value="<?php echo esc_attr( get_option( 'wc_af_recaptcha_secret_key' ) ); ?>">
						<?php echo wp_kses_post( $description['description'] ); ?>
					</td>
				</tr>

				<!-- For Cloudflare Turnstile -->
				<tr valign="top" class="<?php echo ( 'yes' === $enable_recaptcha && 'cf_turnstile' === $recaptcha_type ) ? '' : 'no-display'; ?>">
					<th scope="row" class="titledesc">
						<label for="wc_af_turnstile_site_key">
							<?php
							echo wp_kses_post( $setting_fields['wc_af_turnstile_site_key']['title'] );
							$description = WC_Admin_Settings::get_field_description( $setting_fields['wc_af_turnstile_site_key'] );
							echo wp_kses_post( $description['tooltip_html'] );
							?>
						</label>
					</th>
					<td class="forminp forminp-text" colspan="3">
						<input name="wc_af_turnstile_site_key" id="wc_af_turnstile_site_key" type="<?php echo esc_attr( $setting_fields['wc_af_turnstile_site_key']['type'] ); ?>" value="<?php echo esc_attr( get_option( 'wc_af_turnstile_site_key' ) ); ?>">
						<?php echo wp_kses_post( $description['description'] ); ?>
					</td>
				</tr>
				<tr valign="top" class="<?php echo ( 'yes' === $enable_recaptcha && 'cf_turnstile' === $recaptcha_type ) ? '' : 'no-display'; ?>">
					<th scope="row" class="titledesc">
						<label for="wc_af_turnstile_secret_key">
							<?php
							echo wp_kses_post( $setting_fields['wc_af_turnstile_secret_key']['title'] );
							$description = WC_Admin_Settings::get_field_description( $setting_fields['wc_af_turnstile_secret_key'] );
							echo wp_kses_post( $description['tooltip_html'] );
							?>
						</label>
					</th>
					<td class="forminp forminp-text" colspan="3">
						<input name="wc_af_turnstile_secret_key" id="wc_af_turnstile_secret_key" type="<?php echo esc_attr( $setting_fields['wc_af_turnstile_secret_key']['type'] ); ?>" value="<?php echo esc_attr( get_option( 'wc_af_turnstile_secret_key' ) ); ?>">
						<?php echo wp_kses_post( $description['description'] ); ?>
					</td>
				</tr>
				</tbody>
			</table>
			<?php 
			$plugindetected = get_option( 'paypal_acp_plugindetected', 'no' );
			if ('yes' === $plugindetected) {
		
				?>

			<table class="form-table opmc_wc_af_table">
				<tbody>
					<tr valign="top" class="">
						<th scope="row" class="titledesc">
							<label for="wc_af_paypal_acp_enabled">
								<?php echo wp_kses_post( $setting_fields['wc_af_paypal_acp_enabled']['title'] ); ?>
								<!-- <span class="woocommerce-help-tip" data-tip="<?php //echo esc_attr( $setting_fields['wc_af_paypal_acp_enabled']['desc_tip'] ); ?>"></span> -->
							</label>
						</th>
						<td class="forminp forminp-checkbox">
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php echo esc_attr( $setting_fields['wc_af_paypal_acp_enabled']['title'] ); ?></span>
								</legend>
								<label>
									<input name="wc_af_paypal_acp_enabled" id="wc_af_paypal_acp_enabled_recaptcha" type="checkbox" value="1" <?php checked( $enable_paypal_acp, 'yes' ); ?> >
								</label>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
		</section>
	<?php } ?>
		<?php wc_af_advanced_panel_start( 'recaptcha-advanced' ); ?>
			<section>
					<h2><?php echo esc_html( $setting_fields['wc_af_advanced_protection_settings']['name'] ); ?></h2>
					<div id="wc_af_advanced_protection_settings-description">
						<?php echo wp_kses_post( $setting_fields['wc_af_advanced_protection_settings']['desc'] ); ?>
					</div>

					<table class="form-table opmc_wc_af_table">
						<tbody>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="wc_af_enable_global_rate_limit">
									<?php echo wp_kses_post( $setting_fields['wc_af_enable_global_rate_limit']['title'] ); ?>
									<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_enable_global_rate_limit']['desc_tip'] ); ?>"></span>
								</label>
							</th>
							<td class="forminp forminp-checkbox">
								<fieldset>
									<legend class="screen-reader-text"><span><?php echo esc_attr( $setting_fields['wc_af_enable_global_rate_limit']['title'] ); ?></span></legend>
									<label for="wc_af_enable_global_rate_limit" class="opmc-toggle-control">
										<input name="wc_af_enable_global_rate_limit" id="wc_af_enable_global_rate_limit" type="checkbox" value="1" <?php checked( get_option( 'wc_af_enable_global_rate_limit', 'yes' ), 'yes' ); ?> >
										<span class="opmc-control"></span>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="wc_af_global_time_limit_max">
									<?php
									echo wp_kses_post( $setting_fields['wc_af_global_time_limit_max']['title'] );
									$description = WC_Admin_Settings::get_field_description( $setting_fields['wc_af_global_time_limit_max'] );
									echo wp_kses_post( $description['tooltip_html'] );
									?>
								</label>
							</th>
							<td class="forminp forminp-number" colspan="3">
								<input name="wc_af_global_time_limit_max" id="wc_af_global_time_limit_max" type="number" value="<?php echo esc_attr( get_option( 'wc_af_global_time_limit_max', '60' ) ); ?>" min="60" step="10">
								<?php echo wp_kses_post( $description['description'] ); ?>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="wc_af_global_rate_limit_max">
									<?php
									echo wp_kses_post( $setting_fields['wc_af_global_rate_limit_max']['title'] );
									$description = WC_Admin_Settings::get_field_description( $setting_fields['wc_af_global_rate_limit_max'] );
									echo wp_kses_post( $description['tooltip_html'] );
									?>
								</label>
							</th>
							<td class="forminp forminp-number" colspan="3">
								<input name="wc_af_global_rate_limit_max" id="wc_af_global_rate_limit_max" type="number" value="<?php echo esc_attr( get_option( 'wc_af_global_rate_limit_max', '100' ) ); ?>" min="10" step="1">
								<?php echo wp_kses_post( $description['description'] ); ?>
							</td>
						</tr>
						</tbody>
					</table>
				</section>
		<?php wc_af_advanced_panel_end(); ?>
			<?php
		
	}
}


