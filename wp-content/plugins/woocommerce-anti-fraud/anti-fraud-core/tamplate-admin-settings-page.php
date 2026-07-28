<?php
require_once dirname( __FILE__ ) . '/wc-af-advanced-ui.php';

$settings_fileds = array();
foreach ( $settings as $value ) {
	// pr($value);
	if ( 'sectionend' != $value['type'] ) {
		$settings_fileds[ $value['id'] ] = $value;
	}
}
// pr($settings_fileds['wc_settings_anti_fraud_cancel_score']);

?>
<div class="wc-af-settings-main wc-af-ui wc-af-app-shell <?php echo '' === $current_section ? 'wc-af-settings-main--dashboard' : ''; ?>">
<?php
if ( '' === $current_section ) : 
	?>
	<div id="<?php echo esc_attr( $this->id . '_home_settings-description' ); ?>" class="wc-af-home-settings-anchor"></div>
	<?php $this->render_home_control_center( $settings_fileds ); ?>

	<?php elseif ( 'general' === $current_section ) : ?>

	<section class="wc-af-settings-panel wc-af-settings-panel--general">

		<h2><?php echo esc_html__( 'Core protection', 'woocommerce-anti-fraud' ); ?></h2>
		<div id="<?php echo esc_attr( $this->id . '_general_settings-description' ); ?>" class="wc-af-panel-intro">
			<?php echo wp_kses_post( $settings_fileds[ $this->id . '_general_settings' ]['desc'] ); ?>
		</div>

		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_thresholds_settings' ] ); ?>

		<table class="form-table opmc_wc_af_table">
			<tbody>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="wc_settings_anti_fraud_low_risk_threshold">
						<?php
						echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_low_risk_threshold']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_low_risk_threshold'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-number">
					<input name="wc_settings_anti_fraud_low_risk_threshold" id="wc_settings_anti_fraud_low_risk_threshold" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_low_risk_threshold']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_low_risk_threshold' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_low_risk_threshold']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_low_risk_threshold']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_low_risk_threshold']['custom_attributes']['max'] ); ?>">
				</td>
				<td class="forminp forminp-slider" rowspan="2">
					<?php $this->opmc_score_slider( 0, false, true ); ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="wc_settings_anti_fraud_higher_risk_threshold">
						<?php
						echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_higher_risk_threshold']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_higher_risk_threshold'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-number">
					<input name="wc_settings_anti_fraud_higher_risk_threshold" id="wc_settings_anti_fraud_higher_risk_threshold" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_higher_risk_threshold']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_higher_risk_threshold' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_higher_risk_threshold']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_higher_risk_threshold']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_higher_risk_threshold']['custom_attributes']['max'] ); ?>">
				</td>
			</tr>
			</tbody>
		</table>

		<p class="wc-af-core-hint"><?php esc_html_e( 'Set thresholds and pre-payment checks first. Expand Advanced options only when you need finer control.', 'woocommerce-anti-fraud' ); ?></p>

		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_pre_purchase_settings' ] ); ?>

		<table class="form-table opmc_wc_af_table">
			<tbody>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_fraud_check_before_payment">
						<?php echo wp_kses_post( $settings_fileds['wc_af_fraud_check_before_payment']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_fraud_check_before_payment']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo esc_attr( $settings_fileds['wc_af_fraud_check_before_payment']['title'] ); ?></span></legend>
						<label for="wc_af_fraud_check_before_payment" class="opmc-toggle-control">
							<input name="wc_af_fraud_check_before_payment" id="wc_af_fraud_check_before_payment" type="checkbox" value="1" <?php checked( get_option( 'wc_af_fraud_check_before_payment' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="wc_af_pre_payment_message">
						<?php
						echo wp_kses_post( $settings_fileds['wc_af_pre_payment_message']['title'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_af_pre_payment_message'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-textarea">
					<textarea name="wc_af_pre_payment_message" id="wc_af_pre_payment_message" style="width:100%; height: 100px;"><?php echo esc_html( get_option( 'wc_af_pre_payment_message' ) ); ?></textarea>
				</td>
			</tr>
			</tbody>
		</table>

		<?php wc_af_advanced_panel_start( 'core-general' ); ?>

		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_order_origin_settings' ] ); ?>

		<table class="form-table opmc_wc_af_table">
			<tbody>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_block_unknown_origin">
						<?php echo wp_kses_post( $settings_fileds['wc_af_block_unknown_origin']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_block_unknown_origin']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo esc_attr( $settings_fileds['wc_af_block_unknown_origin']['title'] ); ?></span></legend>
						<label for="wc_af_block_unknown_origin" class="opmc-toggle-control">
							<!-- ✅ FIXED: Changed default from 'yes' to 'no' to match settings definition and prevent false positives on new installs -->
							<input name="wc_af_block_unknown_origin" id="wc_af_block_unknown_origin" type="checkbox" value="1" <?php checked( get_option( 'wc_af_block_unknown_origin', 'no' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
				</td>
			</tr>
			</tbody>
		</table>

		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_order_status_settings' ] ); ?>

		<table class="form-table opmc_wc_af_table">
			<tbody>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_fraud_update_state">
						<?php echo wp_kses_post( $settings_fileds['wc_af_fraud_update_state']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_html( $settings_fileds['wc_af_fraud_update_state']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_fraud_update_state']['title'] ); ?></span></legend>
						<label for="wc_af_fraud_update_state" class="opmc-toggle-control">
							<input name="wc_af_fraud_update_state" id="wc_af_fraud_update_state" type="checkbox" value="1" <?php checked( get_option( 'wc_af_fraud_update_state' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
				</td>
			</tr>

			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_fraud_custom_order_status">
						<?php echo wp_kses_post( $settings_fileds['wc_af_fraud_custom_order_status']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_html( $settings_fileds['wc_af_fraud_custom_order_status']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_fraud_custom_order_status']['title'] ); ?></span></legend>
						<label for="wc_af_fraud_custom_order_status" class="opmc-toggle-control">
							<input name="wc_af_fraud_custom_order_status" id="wc_af_fraud_custom_order_status" type="checkbox" value="1" <?php checked( get_option( 'wc_af_fraud_custom_order_status' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
				</td>
			</tr>
			
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="wc_settings_anti_fraud_cancel_score">
						<?php
						echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_cancel_score']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_cancel_score'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-select">
					<select name="wc_settings_anti_fraud_cancel_score" id="wc_settings_anti_fraud_cancel_score" style="display: block; width: 5em;" class="">
						<?php foreach ( $settings_fileds['wc_settings_anti_fraud_cancel_score']['options'] as $option_key_inner => $option_value_inner ) : ?>
							<option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( (string) $option_key_inner, esc_attr( get_option( 'wc_settings_anti_fraud_cancel_score' ) ) ); ?>><?php echo esc_html( $option_value_inner ); ?></option>
						<?php endforeach ?>
					</select>
				</td>
				<td class="forminp forminp-slider">
					<?php $this->opmc_score_slider( get_option( 'wc_settings_anti_fraud_cancel_score' ), true ); ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="wc_settings_anti_fraud_hold_score">
						<?php
						echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_hold_score']['title'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_hold_score'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-select">
					<select name="wc_settings_anti_fraud_hold_score" id="wc_settings_anti_fraud_hold_score" style="display: block; width: 5em;" class="">
						<?php foreach ( $settings_fileds['wc_settings_anti_fraud_hold_score']['options'] as $option_key_inner => $option_value_inner ) : ?>
							<option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( (string) $option_key_inner, esc_attr( get_option( 'wc_settings_anti_fraud_hold_score' ) ) ); ?>><?php echo esc_html( $option_value_inner ); ?></option>
						<?php endforeach ?>
					</select>
				</td>
				<td class="forminp forminp-slider">
					<?php $this->opmc_score_slider( get_option( 'wc_settings_anti_fraud_hold_score' ), true ); ?>
				</td>
			</tr>
			</tbody>
		</table>



		<!-- Auto order fraud check settings -->
		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_enable_start_auto_fraud_check' ] ); ?>

		<table class="form-table opmc_wc_af_table">
			<tbody>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_enable_start_auto_fraud_check">
						<?php echo wp_kses_post( $settings_fileds['wc_af_start_auto_fraud_check']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_start_auto_fraud_check']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_start_auto_fraud_check']['title'] ); ?></span></legend>
						<label for="wc_af_start_auto_fraud_check" class="opmc-toggle-control">
							<input name="wc_af_start_auto_fraud_check" id="wc_af_start_auto_fraud_check" type="checkbox" value="1" <?php checked( get_option( 'wc_af_start_auto_fraud_check' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="wc_settings_anti_fraud_auto_check_days">
						<?php
						echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_auto_check_days']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_auto_check_days'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-number">
					<input name="wc_settings_anti_fraud_auto_check_days" id="wc_settings_anti_fraud_auto_check_days" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_auto_check_days']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_auto_check_days' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_auto_check_days']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_auto_check_days']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_auto_check_days']['custom_attributes']['max'] ); ?>">
				</td>
			</tr>
			</tbody>
		</table>


		<!-- SMS Verification -->

		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_sms_verification_section' ] ); ?>
		<?php $enable_sms_verification = get_option( 'wc_af_enable_sms_verification', 'no' ); ?>

		<table class="form-table opmc_wc_af_table">
			<tbody>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_sms_verification_section">
						<?php echo wp_kses_post( $settings_fileds['wc_af_enable_sms_verification']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_enable_sms_verification']['desc_tip'] ); ?>"></span>
					</label>

				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_enable_sms_verification']['title'] ); ?></span></legend>
						<label for="wc_af_enable_sms_verification" class="opmc-toggle-control">
							<input name="wc_af_enable_sms_verification" id="wc_af_enable_sms_verification" type="checkbox" value="1" <?php checked( get_option( 'wc_af_enable_sms_verification' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
				</td>
			</tr>

			<tr valign="top" class="<?php // echo $enable_sms_verification !== 'yes' ? 'no-display' : ''; ?>">
				<th scope="row" class="titledesc">
					<label for="wc_af_sms_fraudlabspro_api_key">
						<?php
						echo wp_kses_post( $settings_fileds['wc_af_sms_fraudlabspro_api_key']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_af_sms_fraudlabspro_api_key'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-number">
					<input name="wc_af_sms_fraudlabspro_api_key" id="wc_af_sms_fraudlabspro_api_key" type="<?php echo esc_attr( $settings_fileds['wc_af_sms_fraudlabspro_api_key']['type'] ); ?>" placeholder="<?php echo esc_attr( $settings_fileds['wc_af_sms_fraudlabspro_api_key']['placeholder'] ); ?>" value="<?php echo esc_attr( get_option( 'wc_af_sms_fraudlabspro_api_key' ) ); ?>">
					<p class="description"><?php echo wp_kses_post( $settings_fileds['wc_af_sms_fraudlabspro_api_key']['desc'] ); ?></p>
				</td>
			</tr>
			</tbody>
		</table>


		<!-- Fraud check for API orders  -->
		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_enable_api_fraud_check' ] ); ?>

		<table class="form-table opmc_wc_af_table">
			<tbody>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_enable_start_auto_fraud_check">
						<?php echo wp_kses_post( $settings_fileds['wc_af_api_fraud_check']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_api_fraud_check']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_api_fraud_check']['title'] ); ?></span></legend>
						<label for="wc_af_api_fraud_check" class="opmc-toggle-control">
							<input name="wc_af_api_fraud_check" id="wc_af_api_fraud_check" type="checkbox" value="1" <?php checked( get_option( 'wc_af_api_fraud_check', $settings_fileds['wc_af_api_fraud_check']['default'] ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
					<p class="description"><?php echo wp_kses_post($settings_fileds['wc_af_api_fraud_check']['desc']); ?></p>
				</td>
			</tr>

			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_enable_start_auto_fraud_check">
						<?php echo wp_kses_post( $settings_fileds['wc_af_throttle_api_based_orders_check']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_throttle_api_based_orders_check']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_throttle_api_based_orders_check']['title'] ); ?></span></legend>
						<label for="wc_af_throttle_api_based_orders_check" class="opmc-toggle-control">
							<input name="wc_af_throttle_api_based_orders_check" id="wc_af_throttle_api_based_orders_check" type="checkbox" value="1" <?php checked( get_option( 'wc_af_throttle_api_based_orders_check' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
				</td>
			</tr>

			<tr valign="top" style="display: <?php echo esc_attr( get_option( 'wc_af_throttle_api_based_orders_check' ) === 'yes' ? 'revert' : 'none' ); ?>">
				<th scope="row" class="titledesc">
					<label for="wc_af_max_orders_through_api_per_hour">
						<?php
						echo wp_kses_post( $settings_fileds['wc_af_max_orders_through_api_per_hour']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_af_max_orders_through_api_per_hour'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-number">
					<input name="wc_af_max_orders_through_api_per_hour" id="wc_af_max_orders_through_api_per_hour" type="<?php echo esc_attr( $settings_fileds['wc_af_max_orders_through_api_per_hour']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_af_max_orders_through_api_per_hour' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_af_max_orders_through_api_per_hour']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_af_max_orders_through_api_per_hour']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_af_max_orders_through_api_per_hour']['custom_attributes']['max'] ); ?>">
					<p class="description"><?php echo esc_html( $settings_fileds['wc_af_max_orders_through_api_per_hour']['desc'] ); ?></p>
				</td>
			</tr>

			<!-- ANTIFRAUD-126 Start New field for API Keys Whitelist -->
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_enable_start_auto_fraud_check">
						<?php echo wp_kses_post( $settings_fileds['wc_af_enable_api_keys_whitelist']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_enable_api_keys_whitelist']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_enable_api_keys_whitelist']['title'] ); ?></span></legend>
						<label for="wc_af_enable_api_keys_whitelist" class="opmc-toggle-control">
							<input name="wc_af_enable_api_keys_whitelist" id="wc_af_enable_api_keys_whitelist" type="checkbox" value="1" <?php checked( get_option( 'wc_af_enable_api_keys_whitelist' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
				</td>
			</tr>

			<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="wc_settings_anti_fraud_whitelist_restapi">
							<?php
							echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_whitelist_restapi']['title'] );
							$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_whitelist_restapi'] );
							echo wp_kses_post( $description['tooltip_html'] );
							?>
						</label>
					</th>
					<td class="forminp forminp-multiselect">
						<select name="wc_settings_anti_fraud_whitelist_restapi[]" id="wc_settings_anti_fraud_whitelist_restapi" style="width: 18em;" class="" multiple="multiple">
							<?php foreach ( $settings_fileds['wc_settings_anti_fraud_whitelist_restapi']['options'] as $option_key_inner => $option_value_inner ) : ?>
								<option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( in_array( (string) $option_key_inner, $settings_fileds['wc_settings_anti_fraud_whitelist_restapi']['default'] ) ); ?>><?php echo esc_html( $option_value_inner ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			<!-- ANTIFRAUD-126 END -->
			</tbody>
		</table>


		<!-- Debug log check settings -->
		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_enable_debug_log_check' ] ); ?>

		<table class="form-table opmc_wc_af_table">
			<tbody>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_enable_debug_log_check">
						<?php echo wp_kses_post( $settings_fileds['wc_af_enable_log_check']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_enable_log_check']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_enable_log_check']['title'] ); ?></span></legend>
						<label for="wc_af_enable_log_check" class="opmc-toggle-control">
							<input name="wc_af_enable_log_check" id="wc_af_enable_log_check" type="checkbox" value="1" <?php checked( get_option( 'wc_af_enable_log_check' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
				</td>
			</tr>
			</tbody>
		</table>
		<div class="main-debug-log-tbl" id="debug_log_tbl" style="width:90%;overflow:auto; max-height:100px;    margin-left: 5%; position: inherit;">
			<table id="debug_t" style="width:100%;">
				<thead style="font-size: 14px;">
				<tr id="debug_tr">
					<th id="debug_th" width="10%"><?php echo esc_html__( 'Sr No', 'woocommerce-anti-fraud' ); ?></th>
					<th id="debug_th" width="30%"><?php echo esc_html__( 'Name', 'woocommerce-anti-fraud' ); ?></th>
					<th id="debug_th" width="30%"><?php echo esc_html__( 'Date', 'woocommerce-anti-fraud' ); ?></th>
					<th id="debug_th" width="30%"><?php echo esc_html__( 'Action', 'woocommerce-anti-fraud' ); ?></th>
				</tr>
				</thead>
				<tbody style="line-height: 2.4em; font-size: 14px;">
				<?php
				global $wpdb;
				$dailyCapLimit = 500;
				$table_name    = $wpdb->prefix . 'af_download_url';
				$api_limits    = 0;
				$created_at    = '';

				// Check if API limits exceeded
				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) == $table_name ) {
					$results = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'af_download_url' );
					// $results = $wpdb->get_results($wpdb->prepare('SELECT download_url, created_at FROM ' . $wpdb->prefix . 'af_download_url WHERE created_at = %s', gmdate('Y-m-d')));
				}
				$resultss = (array) $results;
				if ( is_array( $resultss ) ) {
					foreach ( $resultss as $result ) {

						?>
						<tr id="debug_tr">
							<td id="debug_td" width="10%"><?php echo esc_html( $result->id ); ?></td>
							<td id="debug_td" width="30%">antifraud-log-<?php echo esc_html( $result->created_at ); ?>.csv</td>
							<td id="debug_td" width="30%"><?php echo esc_html( $result->created_at ); ?></td>
							<td id="debug_td" width="30%">
								<a href="<?php echo esc_url( $result->download_url ); ?>" type="button" name="Download" class="download-button"><?php echo esc_html__( 'Download', 'woocommerce-anti-fraud' ); ?> </a>
							</td>
						</tr>
						<?php
					}
				}
				?>
				</tbody>
			</table>
		</div>
		<!-- Debug log End -->

		<!-- Dashboard Settings -->
		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_dashboard_settings' ] ); ?>

		<table class="form-table opmc_wc_af_table">
			<tbody>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_dashboard_settings">
						<?php echo wp_kses_post( $settings_fileds['wc_af_enable_dashboard']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_enable_dashboard']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_enable_dashboard']['title'] ); ?></span></legend>
						<label for="wc_af_enable_dashboard" class="opmc-toggle-control">
							<input name="wc_af_enable_dashboard" id="wc_af_enable_dashboard" type="checkbox" value="1" <?php checked( get_option( 'wc_af_enable_dashboard', 'yes' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_dashboard_date_range">
						<?php echo wp_kses_post( $settings_fileds['wc_af_dashboard_date_range']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_dashboard_date_range']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-select">
					<?php
					// Get current date range from user-specific option (same as dashboard uses)
					$option_key = 'wc_af_dashboard_date_range_' . get_current_user_id();
					$current_date_range = get_option( $option_key, 'last_30_days' );
					?>
					<select name="wc_af_dashboard_date_range" id="wc_af_dashboard_date_range" style="<?php echo esc_attr( $settings_fileds['wc_af_dashboard_date_range']['css'] ); ?>">
						<?php
						foreach ( $settings_fileds['wc_af_dashboard_date_range']['options'] as $key => $value ) {
							?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_date_range, $key ); ?>>
								<?php echo esc_html( $value ); ?>
							</option>
							<?php
						}
						?>
					</select>
				</td>
			</tr>
			</tbody>
		</table>
		<!-- Dashboard Settings End -->
		<?php wc_af_advanced_panel_end(); ?>
	</section>
<?php 
elseif ( 'card_attacks' === $current_section ) : 
	$enable_paypal_acp = get_option( 'wc_af_paypal_acp_enabled', 'no' );
	?>
	<section class="wc-af-settings-panel wc-af-settings-panel--card-attacks">
		<h2><?php echo esc_html__( 'Card testing protection', 'woocommerce-anti-fraud' ); ?></h2>

		<p class="wc-af-ca-page-intro">
			<?php esc_html_e( 'Card attack protection combines Checkout CAPTCHA (bots and card testing at checkout), order and payment attempt limits, failed-payment email controls, and related tuning. Use this page as the hub for all of it.', 'woocommerce-anti-fraud' ); ?>
		</p>

		<?php $this->render_card_attacks_incident_panel(); ?>

		<details class="wc-af-card-attacks__learn-more">
			<summary><?php esc_html_e( 'Learn more about card attacks', 'woocommerce-anti-fraud' ); ?></summary>
			<div id="<?php echo esc_attr( $this->id . '_card_attacks_settings-description' ); ?>" class="wc-af-card-attacks__intro-desc">
				<?php echo wp_kses_post( $settings_fileds[ $this->id . '_card_attacks_settings' ]['desc'] ); ?>
			</div>
		</details>

		<div id="wc-af-card-attacks-protect" class="wc-af-card-attacks__zone wc-af-card-attacks__zone--protect">
			<h3 class="wc-af-card-attacks__zone-title"><?php esc_html_e( 'Checkout CAPTCHA (card attack protection)', 'woocommerce-anti-fraud' ); ?></h3>
			<p class="wc-af-card-attacks__zone-lead">
				<?php esc_html_e( 'Checkout CAPTCHA helps block bots and card testing at checkout. It works together with order and payment limits below—configure keys here, then review limits in Advanced on this same page.', 'woocommerce-anti-fraud' ); ?>
			</p>

		<?php $this->opmc_add_admin_field_section_for_cardAttack( $settings_fileds[ $this->id . '_thresholds_settings' ] ); ?>
		<table class="form-table opmc_wc_af_table wc-af-card-attacks__table">
			<tbody>
			<tr>
				<td colspan="2" class="wc-af-card-attacks__checkout-steps">
					<h4 class="wc-af-card-attacks__steps-title"><?php esc_html_e( 'Quick setup', 'woocommerce-anti-fraud' ); ?></h4>
					<ol class="wc-af-card-attacks__steps-list">
						<li><?php echo wp_kses( __( 'Create your <a href="https://www.google.com/recaptcha/">reCAPTCHA site and secret keys</a> (or configure Cloudflare Turnstile on the Checkout CAPTCHA screen).', 'woocommerce-anti-fraud' ), array( 'a' => array( 'href' => array() ) ) ); ?></li>
						<li>
						<?php
							echo wp_kses(
								sprintf(
									/* translators: %s: admin link to Checkout CAPTCHA settings */
									__( 'Enter keys under %s.', 'woocommerce-anti-fraud' ),
									'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=wc_af&section=recaptcha_settings' ) ) . '">' . esc_html__( 'Checkout CAPTCHA', 'woocommerce-anti-fraud' ) . '</a>'
								),
								array( 'a' => array( 'href' => array() ) )
							);
						?>
							</li>
						<li><?php esc_html_e( 'You are protected when Checkout CAPTCHA shows Active (or Turnstile keys are saved).', 'woocommerce-anti-fraud' ); ?></li>
					</ol>
				</td>
			</tr>
			<?php 
			$plugindetected = get_option( 'paypal_acp_plugindetected', 'no' );
			if ('yes' === $plugindetected) { 
				?>
			<tr>
				<td class="forminp forminp-checkbox wc-af-ca-paypal-acp-row">
					<fieldset>
						<label>
							<input name="wc_af_paypal_acp_enabled" id="wc_af_paypal_acp_enabled_card_attack" type="checkbox" value="1" <?php checked( $enable_paypal_acp, 'yes' ); ?> >
						</label>
					</fieldset>
				</td>

			</tr>
		<?php } ?>

			</tbody>
		</table>
		</div>

			<?php wc_af_advanced_panel_start( 'card-attacks' ); ?>

			<p id="wc-af-card-attacks-tuning" class="wc-af-card-attacks__advanced-lead">
				<?php esc_html_e( 'Advanced: tune order limits, payment limits, and cooldowns only if your default setup needs tightening.', 'woocommerce-anti-fraud' ); ?>
			</p>

			<?php $this->opmc_add_admin_field_section_2_cardAttack( $settings_fileds[ $this->id . '_order_attempts_rules_settings' ], true ); ?>
			<tbody>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_attempt_count_check">
						<?php echo wp_kses_post( $settings_fileds['wc_af_attempt_count_check']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( $settings_fileds['wc_af_attempt_count_check']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_attempt_count_check']['title'] ); ?></span></legend>
						<label for="wc_af_attempt_count_check" class="opmc-toggle-control">
							<input name="wc_af_attempt_count_check" id="wc_af_attempt_count_check" type="checkbox" value="1" <?php checked( get_option( 'wc_af_attempt_count_check' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_settings_anti_fraud_attempt_time_span">
						<?php
						echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_attempt_time_span']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_attempt_time_span'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-number">
					<input name="wc_settings_anti_fraud_attempt_time_span" id="wc_settings_anti_fraud_attempt_time_span" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_attempt_time_span']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_attempt_time_span' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_attempt_time_span']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_attempt_time_span']['custom_attributes']['step'] ); ?>">
				</td>
			</tr>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_settings_anti_fraud_max_order_attempt_time_span">
						<?php
						echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_max_order_attempt_time_span']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_max_order_attempt_time_span'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-number">
					<input name="wc_settings_anti_fraud_max_order_attempt_time_span" id="wc_settings_anti_fraud_max_order_attempt_time_span" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_max_order_attempt_time_span']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_max_order_attempt_time_span' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_max_order_attempt_time_span']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_max_order_attempt_time_span']['custom_attributes']['step'] ); ?>">
				</td>
			</tr>
			<?php if ( isset( $settings_fileds['wc_af_attempt_count_mode'] ) ) : ?>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_attempt_count_mode">
						<?php echo wp_kses_post( $settings_fileds['wc_af_attempt_count_mode']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_attempt_count_mode']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-select">
					<select name="wc_af_attempt_count_mode" id="wc_af_attempt_count_mode" class="wc-af-field-select-wide">
						<?php
						$current_mode = get_option( 'wc_af_attempt_count_mode', 'orders_only' );
						foreach ( $settings_fileds['wc_af_attempt_count_mode']['options'] as $value => $label ) :
							?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_mode, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php echo wp_kses_post( $settings_fileds['wc_af_attempt_count_mode']['desc'] ); ?></p>
				</td>
			</tr>
			<?php endif; ?>
			<tr valign="top">
				<td colspan="2">
					<hr/>
				</td>
			</tr>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_enable_checkout_waiting_time">
						<?php echo wp_kses_post( $settings_fileds['wc_af_enable_checkout_waiting_time']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( $settings_fileds['wc_af_enable_checkout_waiting_time']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminps forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_enable_checkout_waiting_time']['title'] ); ?></span></legend>
						<label for="wc_af_enable_checkout_waiting_time" class="opmc-toggle-control">
							<input name="wc_af_enable_checkout_waiting_time" id="wc_af_enable_checkout_waiting_time" type="checkbox" value="1" <?php checked( get_option( 'wc_af_enable_checkout_waiting_time' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
					<p class="description"><?php echo wp_kses_post( $settings_fileds['wc_af_enable_checkout_waiting_time']['desc'] ); ?></p>
				</td>
			</tr>
			</tbody>

			<?php $this->opmc_add_admin_field_section_3_cardAttack( $settings_fileds[ $this->id . '_order_payment_attempts_settings' ] ); ?>
			<tbody>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_order_payment_attempt_check">
						<?php echo wp_kses_post( $settings_fileds['wc_af_order_payment_attempt_check']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( $settings_fileds['wc_af_order_payment_attempt_check']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_order_payment_attempt_check']['title'] ); ?></span></legend>
						<label for="wc_af_order_payment_attempt_check" class="opmc-toggle-control">
							<input name="wc_af_order_payment_attempt_check" id="wc_af_order_payment_attempt_check" type="checkbox" value="1" <?php checked( get_option( 'wc_af_order_payment_attempt_check' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_settings_anti_fraud_max_order_payment_attempt">
						<?php
						echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_max_order_payment_attempt']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_max_order_payment_attempt'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-number">
					<input name="wc_settings_anti_fraud_max_order_payment_attempt" id="wc_settings_anti_fraud_max_order_payment_attempt" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_max_order_payment_attempt']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_max_order_payment_attempt' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_max_order_payment_attempt']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_max_order_payment_attempt']['custom_attributes']['step'] ); ?>">
				</td>
			</tr>
			</tbody>

			<?php $this->opmc_add_admin_field_section_4_cardAttack( $settings_fileds[ $this->id . '_order_between_times_settings' ] ); ?>
			<tbody>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_limit_order_count">
						<?php echo wp_kses_post( $settings_fileds['wc_af_limit_order_count']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_limit_order_count']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_limit_order_count']['title'] ); ?></span></legend>
						<label for="wc_af_limit_order_count" class="opmc-toggle-control">
							<input name="wc_af_limit_order_count" id="wc_af_limit_order_count" type="checkbox" value="1" <?php checked( get_option( 'wc_af_limit_order_count' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_limit_time_start">
						<?php
						echo wp_kses_post( $settings_fileds['wc_af_limit_time_start']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_af_limit_time_start'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-time">
					<input name="wc_af_limit_time_start" id="wc_af_limit_time_start" type="<?php echo esc_attr( $settings_fileds['wc_af_limit_time_start']['type'] ); ?>" style="<?php echo esc_attr( $settings_fileds['wc_af_limit_time_start']['css'] ); ?>" value="<?php echo esc_attr( get_option( 'wc_af_limit_time_start' ) ); ?>">
				</td>
			</tr>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_limit_time_end">
						<?php
						echo wp_kses_post( $settings_fileds['wc_af_limit_time_end']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_af_limit_time_end'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-number">
					<input name="wc_af_limit_time_end" id="wc_af_limit_time_end" type="<?php echo esc_attr( $settings_fileds['wc_af_limit_time_end']['type'] ); ?>" style="<?php echo esc_attr( $settings_fileds['wc_af_limit_time_start']['css'] ); ?>" value="<?php echo esc_attr( get_option( 'wc_af_limit_time_end' ) ); ?>">
				</td>
			</tr>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_allowed_order_limit">
						<?php
						echo wp_kses_post( $settings_fileds['wc_af_allowed_order_limit']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_af_allowed_order_limit'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-number">
					<input name="wc_af_allowed_order_limit" id="wc_af_allowed_order_limit" type="<?php echo esc_attr( $settings_fileds['wc_af_allowed_order_limit']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_af_allowed_order_limit' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_af_allowed_order_limit']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_af_allowed_order_limit']['custom_attributes']['step'] ); ?>">
				</td>
			</tr>
			</tbody>

		</table>
		<?php wc_af_advanced_panel_end(); ?>

	</section>
<?php 
elseif ( 'cleanup' === $current_section ) : 
	$stop_send_mail_failed = get_option( 'wc_af_stop_send_mail_failed_status', 'no' );
	
	?>
	<section class="wc-af-settings-panel wc-af-settings-panel--cleanup">
		<h2><?php echo esc_html__( 'Failed orders & cleanup', 'woocommerce-anti-fraud' ); ?></h2>
		<div id="<?php echo esc_attr( $this->id . '_order_cleanup_settings-description' ); ?>" class="wc-af-panel-intro">
			<?php echo wp_kses_post( $settings_fileds[ $this->id . '_order_cleanup_settings' ]['desc'] ); ?>
		</div>

		<?php 
		$this->opmc_add_admin_field_section_for_cleanup( $settings_fileds[ $this->id . '_order_cleanup_settings' ] ); 
		$orders_count = get_transient( 'wc_af_preload_failed_counts' );
		$selecttimeframe = get_transient( 'wc_af_failed_orders_to_cleanup' );
		$selectedtime = get_transient( 'wc_af_cleanup_selected_timeframe' );
		$orderidcount = get_transient( 'wc_af_cleanup_orderid_count' );

			$time_labels = array(
				'3_hour'  => '3 hours',
				'6_hour'  => '6 hours',
				'12_hour' => '12 hours',
				'24_hour' => '1 day',
				'2_days'  => '2 days',
				'3_days'  => '3 days',
				'4_days'  => '4 days',
				'5_days'  => '5 days',
			);

			$timeframes = [
				'selecttime' => 'Select timeframe',
				'3_hour'     => 'Last 3 Hours',
				'6_hour'     => 'Last 6 Hours',
				'12_hour'    => 'Last 12 Hours',
				'24_hour'    => 'Last 1 Day',
				'2_days'     => 'Last 2 Days',
				'3_days'     => 'Last 3 Days',
				'4_days'     => 'Last 4 Days',
				'5_days'     => 'Last 5 Days',
			];

			// default message if key not found
			$message = isset( $time_labels[$selectedtime] ) ? $time_labels[$selectedtime] : '';
			$current_value = get_option('wc_af_cleanup_timeframe', 'selecttime');

			?>
		<table class="form-table opmc_wc_af_table">
			<tbody>
				<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="wc_af_cleanup_timeframe">
						<?php
						echo wp_kses_post( $settings_fileds['wc_af_cleanup_timeframe']['title'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_af_cleanup_timeframe'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-select">
					<select name="wc_af_cleanup_timeframe" id="wc_af_cleanup_timeframe" style="width: 12em;">
						<?php 
						foreach ( $timeframes as $key => $label ) :

							$count = isset($orders_count[$key]) ? $orders_count[$key] : 0;

							$label_with_count = $label;
							if ( 'selecttime' !== $key ) {
								$label_with_count .= " – {$count} failed orders";
							}
							?>
							<option value="<?php echo esc_attr($key); ?>"
								<?php selected( $key, $current_value ); ?>>
								<?php echo esc_html( $label_with_count ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span>Choose how far back to check for failed orders to move them to Trash.</span>
				</td>
			</tr>
			
			</tbody>
		</table>
		<?php 
		
		if ( ! empty( $orderidcount ) && intval( $orderidcount ) !== 0 ) { 
			?>
			<div class="cleanup-section">
				<span id="description-cleanup"><?php echo esc_attr($selecttimeframe); ?> Failed orders in the past <?php echo esc_attr($message); ?> <a href="#" class="button-secondary wc-af-btn wc-af-btn--secondary wc-af-inline-action" id="cleanup_order_btn" style="vertical-align: middle; font-size: 14px;">Move to trash</a></span>
				<p class="description" style="font-size: 14px";>Deleting these failed orders may not make changes to other integrated systems where the failed order data may have been sent.</p>
			</div>
			
		<?php } ?>
		<table class="form-table opmc_wc_af_table">
			<tbody>
				<tr>
					<th scope="row" class="titledesc">
					<label for="wc_af_cleanup_timeframe">
						<?php
						echo wp_kses_post( $settings_fileds['wc_af_stop_send_mail_failed_status']['title'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_af_stop_send_mail_failed_status'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
					<td class="forminp forminp-checkbox" style="margin: 0;">
						<fieldset>
							<label>
								<input name="wc_af_stop_send_mail_failed_status" id="wc_af_stop_send_mail_failed_status" type="checkbox" value="1" <?php checked( get_option( 'wc_af_stop_send_mail_failed_status' ), 'yes' ); ?> >
							</label>
							<span><?php esc_html_e( 'Stops customer and admin emails on failed payment—useful when card testing floods your inbox.', 'woocommerce-anti-fraud' ); ?></span>
						</fieldset>
					</td>

				</tr>
			</tbody>
		</table>
	</section>

<?php elseif ( 'rules' == $current_section ) : ?>

	<section class="wc-af-settings-panel wc-af-settings-panel--rules">
		<h2><?php echo esc_html__( 'Fraud rules', 'woocommerce-anti-fraud' ); ?></h2>
		<div id="<?php echo esc_attr( $this->id . '_rule_settings-description' ); ?>" class="wc-af-panel-intro">
			<?php echo wp_kses_post( $settings_fileds[ $this->id . '_rule_settings' ]['desc'] ); ?>
		</div>

		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_first_time_purchase_settings' ] ); ?>
		<tbody>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_first_order_custom">
					<?php echo wp_kses_post( $settings_fileds['wc_af_first_order']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_first_order']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_first_order']['title'] ); ?></span></legend>
					<label for="wc_af_first_order" class="opmc-toggle-control">
						<input name="wc_af_first_order" id="wc_af_first_order" type="checkbox" value="1" <?php checked( get_option( 'wc_af_first_order' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
			</td>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_first_order_weight" id="wc_settings_anti_fraud_first_order_weight" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_first_order_weight']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_first_order_weight' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_first_order_weight']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_first_order_weight']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_first_order_weight']['custom_attributes']['max'] ); ?>">
			</td>
			<td class="forminp forminp-slider">
				<?php $this->opmc_score_slider( get_option( 'wc_settings_anti_fraud_first_order_weight' ) ); ?>
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_first_order_custom">
					<?php echo wp_kses_post( $settings_fileds['wc_af_first_order_custom']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_first_order_custom']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_first_order_custom']['title'] ); ?></span></legend>
					<label for="wc_af_first_order_custom" class="opmc-toggle-control">
						<input name="wc_af_first_order_custom" id="wc_af_first_order_custom" type="checkbox" value="1" <?php checked( get_option( 'wc_af_first_order_custom' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
			</td>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_first_order_custom_weight" id="wc_settings_anti_fraud_first_order_custom_weight" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_first_order_custom_weight']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_first_order_custom_weight' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_first_order_custom_weight']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_first_order_custom_weight']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_first_order_custom_weight']['custom_attributes']['max'] ); ?>">
			</td>
			<td class="forminp forminp-slider">
				<?php $this->opmc_score_slider( get_option( 'wc_settings_anti_fraud_first_order_custom_weight' ) ); ?>
			</td>
		</tr>
		<tr valign="top">
			<td colspan="2">
				<hr/>
			</td>
		</tr>
		</tbody>
		</table>

		<?php wc_af_advanced_panel_start( 'fraud-rules' ); ?>

		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_address_based_rules_settings' ] ); ?>

		<tbody>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_bca_order">
					<?php echo wp_kses_post( $settings_fileds['wc_af_bca_order']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( $settings_fileds['wc_af_bca_order']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_bca_order']['title'] ); ?></span></legend>
					<label for="wc_af_bca_order" class="opmc-toggle-control">
						<input name="wc_af_bca_order" id="wc_af_bca_order" type="checkbox" value="1" <?php checked( get_option( 'wc_af_bca_order' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
			</td>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_bca_order_weight" id="wc_settings_anti_fraud_bca_order_weight" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_bca_order_weight']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_bca_order_weight' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_bca_order_weight']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_bca_order_weight']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_bca_order_weight']['custom_attributes']['max'] ); ?>">
			</td>
			<td class="forminp forminp-slider">
				<?php $this->opmc_score_slider( get_option( 'wc_settings_anti_fraud_bca_order_weight' ) ); ?>
			</td>
		</tr>
		<!-- /* Geo Localion */ -->
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_geolocation_order">
					<?php echo wp_kses_post( $settings_fileds['wc_af_geolocation_order']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( $settings_fileds['wc_af_geolocation_order']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_geolocation_order']['title'] ); ?></span></legend>
					<label for="wc_af_geolocation_order" class="opmc-toggle-control">
						<input name="wc_af_geolocation_order" id="wc_af_geolocation_order" type="checkbox" value="1" <?php checked( get_option( 'wc_af_geolocation_order' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
			</td>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_geolocation_order_weight" id="wc_settings_anti_fraud_geolocation_order_weight" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_geolocation_order_weight']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_geolocation_order_weight' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_geolocation_order_weight']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_geolocation_order_weight']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_geolocation_order_weight']['custom_attributes']['max'] ); ?>">
			</td>
			<td class="forminp forminp-slider">
				<?php $this->opmc_score_slider( get_option( 'wc_settings_anti_fraud_geolocation_order_weight' ) ); ?>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="bigdatacloud_api_key">
					<?php
					echo wp_kses_post( $settings_fileds['bigdatacloud_api_key']['title'] );
					$description = WC_Admin_Settings::get_field_description( $settings_fileds['bigdatacloud_api_key'] );
					echo wp_kses_post( $description['tooltip_html'] );
					?>
				</label>
			</th>
			<td class="forminp forminp-text" colspan="3">
				<input name="bigdatacloud_api_key" id="bigdatacloud_api_key" type="<?php echo esc_attr( $settings_fileds['bigdatacloud_api_key']['type'] ); ?>" value="<?php echo esc_attr( get_option( 'bigdatacloud_api_key' ) ); ?>">
				<?php echo wp_kses_post( $description['description'] ); ?>
			</td>
		</tr>
		<!-- /* Geo Localion End */ -->

		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_billing_phone_number_order">
					<?php echo wp_kses_post( $settings_fileds['wc_af_billing_phone_number_order']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( $settings_fileds['wc_af_billing_phone_number_order']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_billing_phone_number_order']['title'] ); ?></span></legend>
					<label for="wc_af_billing_phone_number_order" class="opmc-toggle-control">
						<input name="wc_af_billing_phone_number_order" id="wc_af_billing_phone_number_order" type="checkbox" value="1" <?php checked( get_option( 'wc_af_billing_phone_number_order' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
			</td>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_billing_phone_number_order_weight" id="wc_settings_anti_fraud_billing_phone_number_order_weight" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_billing_phone_number_order_weight']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_billing_phone_number_order_weight' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_billing_phone_number_order_weight']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_billing_phone_number_order_weight']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_billing_phone_number_order_weight']['custom_attributes']['max'] ); ?>">
			</td>
			<td class="forminp forminp-slider">
				<?php $this->opmc_score_slider( get_option( 'wc_settings_anti_fraud_billing_phone_number_order_weight' ) ); ?>
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_proxy_order">
					<?php echo wp_kses_post( $settings_fileds['wc_af_proxy_order']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( $settings_fileds['wc_af_proxy_order']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_proxy_order']['title'] ); ?></span></legend>
					<label for="wc_af_proxy_order" class="opmc-toggle-control">
						<input name="wc_af_proxy_order" id="wc_af_proxy_order" type="checkbox" value="1" <?php checked( get_option( 'wc_af_proxy_order' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
			</td>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_proxy_order_weight" id="wc_settings_anti_fraud_proxy_order_weight" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_proxy_order_weight']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_proxy_order_weight' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_proxy_order_weight']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_proxy_order_weight']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_proxy_order_weight']['custom_attributes']['max'] ); ?>">
			</td>
			<td class="forminp forminp-slider">
				<?php $this->opmc_score_slider( get_option( 'wc_settings_anti_fraud_proxy_order_weight' ) ); ?>
			</td>
		</tr>
		<tr valign="top">
			<td colspan="2">
				<hr/>
			</td>
		</tr>
		</tbody>

		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_multi_order_attempts_rules_settings' ] ); ?>

		<tbody>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_ip_multiple_check">
					<?php echo wp_kses_post( $settings_fileds['wc_af_ip_multiple_check']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( $settings_fileds['wc_af_ip_multiple_check']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_ip_multiple_check']['title'] ); ?></span></legend>
					<label for="wc_af_ip_multiple_check" class="opmc-toggle-control">
						<input name="wc_af_ip_multiple_check" id="wc_af_ip_multiple_check" type="checkbox" value="1" <?php checked( get_option( 'wc_af_ip_multiple_check' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
			</td>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_ip_multiple_weight" id="wc_settings_anti_fraud_ip_multiple_weight" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_ip_multiple_weight']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_ip_multiple_weight' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_ip_multiple_weight']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_ip_multiple_weight']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_ip_multiple_weight']['custom_attributes']['max'] ); ?>">
			</td>
			<td class="forminp forminp-slider">
				<?php $this->opmc_score_slider( get_option( 'wc_settings_anti_fraud_ip_multiple_weight' ) ); ?>
			</td>
		</tr>

		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_settings_anti_fraud_ip_multiple_time_span">
					<?php
					echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_ip_multiple_time_span']['name'] );
					$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_ip_multiple_time_span'] );
					echo wp_kses_post( $description['tooltip_html'] );
					?>
				</label>
			</th>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_ip_multiple_time_span" id="wc_settings_anti_fraud_ip_multiple_time_span" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_ip_multiple_time_span']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_ip_multiple_time_span' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_ip_multiple_time_span']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_ip_multiple_time_span']['custom_attributes']['step'] ); ?>">
			</td>
		</tr>
		<tr valign="top">
			<td colspan="2">
				<hr/>
			</td>
		</tr>

		</tbody>

		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_origin_countries_rules_settings' ] ); ?>

		<tbody>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_international_order">
					<?php echo wp_kses_post( $settings_fileds['wc_af_international_order']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( $settings_fileds['wc_af_international_order']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_international_order']['title'] ); ?></span></legend>
					<label for="wc_af_international_order" class="opmc-toggle-control">
						<input name="wc_af_international_order" id="wc_af_international_order" type="checkbox" value="1" <?php checked( get_option( 'wc_af_international_order' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
			</td>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_international_order_weight" id="wc_settings_anti_fraud_international_order_weight" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_international_order_weight']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_international_order_weight' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_international_order_weight']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_international_order_weight']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_international_order_weight']['custom_attributes']['max'] ); ?>">
			</td>
			<td class="forminp forminp-slider">
				<?php $this->opmc_score_slider( get_option( 'wc_settings_anti_fraud_international_order_weight' ) ); ?>
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_unsafe_countries">
					<?php echo wp_kses_post( $settings_fileds['wc_af_unsafe_countries']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( $settings_fileds['wc_af_unsafe_countries']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_unsafe_countries']['title'] ); ?></span></legend>
					<label for="wc_af_unsafe_countries" class="opmc-toggle-control">
						<input name="wc_af_unsafe_countries" id="wc_af_unsafe_countries" type="checkbox" value="1" <?php checked( get_option( 'wc_af_unsafe_countries' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
			</td>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_unsafe_countries_weight" id="wc_settings_anti_fraud_unsafe_countries_weight" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_unsafe_countries_weight']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_unsafe_countries_weight' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_unsafe_countries_weight']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_unsafe_countries_weight']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_unsafe_countries_weight']['custom_attributes']['max'] ); ?>">
			</td>
			<td class="forminp forminp-slider">
				<?php $this->opmc_score_slider( get_option( 'wc_settings_anti_fraud_unsafe_countries_weight' ) ); ?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="wc_settings_anti_fraud_define_unsafe_countries_list">
					<?php
					echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_define_unsafe_countries_list']['name'] );
					$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_define_unsafe_countries_list'] );
					echo wp_kses_post( $description['tooltip_html'] );
					?>
				</label>
			</th>
			<td class="forminp forminp-multiselect" colspan="3">
				<select name="wc_settings_anti_fraud_define_unsafe_countries_list[]" id="wc_settings_anti_fraud_define_unsafe_countries_list" class="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_define_unsafe_countries_list']['class'] ); ?>" multiple>
					<?php foreach ( $settings_fileds['wc_settings_anti_fraud_define_unsafe_countries_list']['options'] as $option_key_inner => $option_value_inner ) : ?>
						<option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( in_array( (string) $option_key_inner, $settings_fileds['wc_settings_anti_fraud_define_unsafe_countries_list']['default'] ) ); ?>><?php echo esc_html( $option_value_inner ); ?></option>
					<?php endforeach ?>
				</select>
			</td>
		</tr>
		<tr valign="top">
			<td colspan="2">
				<hr/>
			</td>
		</tr>
		</tbody>

		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_high_risk_domain_rules_settings' ] ); ?>

		<tbody>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_suspecius_email">
					<?php echo wp_kses_post( $settings_fileds['wc_af_suspecius_email']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( $settings_fileds['wc_af_suspecius_email']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_suspecius_email']['title'] ); ?></span></legend>
					<label for="wc_af_suspecius_email" class="opmc-toggle-control">
						<input name="wc_af_suspecius_email" id="wc_af_suspecius_email" type="checkbox" value="1" <?php checked( get_option( 'wc_af_suspecius_email' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
			</td>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_suspecious_email_weight" id="wc_settings_anti_fraud_suspecious_email_weight" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_suspecious_email_weight']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_suspecious_email_weight' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_suspecious_email_weight']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_suspecious_email_weight']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_suspecious_email_weight']['custom_attributes']['max'] ); ?>">
			</td>
			<td class="forminp forminp-slider">
				<?php $this->opmc_score_slider( get_option( 'wc_settings_anti_fraud_suspecious_email_weight' ) ); ?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="wc_settings_anti_fraud_suspecious_email_domains">
					<?php
					echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_suspecious_email_domains']['name'] );
					$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_suspecious_email_domains'] );
					echo wp_kses_post( $description['tooltip_html'] );
					?>
				</label>
			</th>
			<td class="forminp forminp-textarea" colspan="3">
				<textarea name="wc_settings_anti_fraud_suspecious_email_domains" id="wc_settings_anti_fraud_suspecious_email_domains" style="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_suspecious_email_domains']['css'] ); ?>" class="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_suspecious_email_domains']['class'] ); ?>"><?php echo esc_html( get_option( 'wc_settings_anti_fraud_suspecious_email_domains' ) ); ?></textarea>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="check_email_domain_api_key">
					<?php
					echo wp_kses_post( $settings_fileds['check_email_domain_api_key']['title'] );
					$description = WC_Admin_Settings::get_field_description( $settings_fileds['check_email_domain_api_key'] );
					echo wp_kses_post( $description['tooltip_html'] );
					?>
				</label>
			</th>
			<td class="forminp forminp-text" colspan="3">
				<input name="check_email_domain_api_key" id="check_email_domain_api_key" type="<?php echo esc_attr( $settings_fileds['check_email_domain_api_key']['type'] ); ?>" value="<?php echo esc_attr( get_option( 'check_email_domain_api_key' ) ); ?>">
				<?php echo wp_kses_post( $description['description'] ); ?>
			</td>
		</tr>
		<tr valign="top">
			<td colspan="2">
				<hr/>
			</td>
		</tr>

		</tbody>

		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_order_amount_attempts_rules_settings' ] ); ?>

		<tbody>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_order_avg_amount_check">
					<?php echo wp_kses_post( $settings_fileds['wc_af_order_avg_amount_check']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( $settings_fileds['wc_af_order_avg_amount_check']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_order_avg_amount_check']['title'] ); ?></span></legend>
					<label for="wc_af_order_avg_amount_check" class="opmc-toggle-control">
						<input name="wc_af_order_avg_amount_check" id="wc_af_order_avg_amount_check" type="checkbox" value="1" <?php checked( get_option( 'wc_af_order_avg_amount_check' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
			</td>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_order_avg_amount_weight" id="wc_settings_anti_fraud_order_avg_amount_weight" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_order_avg_amount_weight']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_order_avg_amount_weight' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_order_avg_amount_weight']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_order_avg_amount_weight']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_order_avg_amount_weight']['custom_attributes']['max'] ); ?>">
			</td>
			<td class="forminp forminp-slider">
				<?php $this->opmc_score_slider( get_option( 'wc_settings_anti_fraud_order_avg_amount_weight' ) ); ?>
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_settings_anti_fraud_avg_amount_multiplier">
					<?php
					echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_avg_amount_multiplier']['name'] );
					$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_avg_amount_multiplier'] );
					echo wp_kses_post( $description['tooltip_html'] );
					?>
				</label>
			</th>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_avg_amount_multiplier" id="wc_settings_anti_fraud_avg_amount_multiplier" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_avg_amount_multiplier']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_avg_amount_multiplier' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_avg_amount_multiplier']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_avg_amount_multiplier']['custom_attributes']['step'] ); ?>">
			</td>
		</tr>
		<tr valign="top">
			<td colspan="2">
				<hr/>
			</td>
		</tr>

		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_order_amount_check">
					<?php echo wp_kses_post( $settings_fileds['wc_af_order_amount_check']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_order_amount_check']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_order_amount_check']['title'] ); ?></span></legend>
					<label for="wc_af_order_amount_check" class="opmc-toggle-control">
						<input name="wc_af_order_amount_check" id="wc_af_order_amount_check" type="checkbox" value="1" <?php checked( get_option( 'wc_af_order_amount_check' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
			</td>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_order_amount_weight" id="wc_settings_anti_fraud_order_amount_weight" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_order_amount_weight']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_order_amount_weight' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_order_amount_weight']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_order_amount_weight']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_order_amount_weight']['custom_attributes']['max'] ); ?>">
			</td>
			<td class="forminp forminp-slider">
				<?php $this->opmc_score_slider( get_option( 'wc_settings_anti_fraud_order_amount_weight' ) ); ?>
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_settings_anti_fraud_amount_limit">
					<?php
					echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_amount_limit']['name'] );
					$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_amount_limit'] );
					echo wp_kses_post( $description['tooltip_html'] );
					?>
				</label>
			</th>
			<td class="forminp forminp-text">
				<input name="wc_settings_anti_fraud_amount_limit" id="wc_settings_anti_fraud_amount_limit" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_amount_limit']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_amount_limit' ) ); ?>">
			</td>
		</tr>
		<tr valign="top">
			<td colspan="2">
				<hr/>
			</td>
		</tr>

		</tbody>
		<?php $this->opmc_add_admin_field_section( $settings_fileds[ $this->id . '_order_attempts_rules_settings' ] ); ?>

		<tbody>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_attempt_count_check">
					<?php echo wp_kses_post( $settings_fileds['wc_af_attempt_count_check']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( $settings_fileds['wc_af_attempt_count_check']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_attempt_count_check']['title'] ); ?></span></legend>
					<label for="wc_af_attempt_count_check" class="opmc-toggle-control">
						<input name="wc_af_attempt_count_check" id="wc_af_attempt_count_check" type="checkbox" value="1" <?php checked( get_option( 'wc_af_attempt_count_check' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_settings_anti_fraud_attempt_time_span">
					<?php
					echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_attempt_time_span']['name'] );
					$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_attempt_time_span'] );
					echo wp_kses_post( $description['tooltip_html'] );
					?>
				</label>
			</th>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_attempt_time_span" id="wc_settings_anti_fraud_attempt_time_span" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_attempt_time_span']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_attempt_time_span' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_attempt_time_span']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_attempt_time_span']['custom_attributes']['step'] ); ?>">
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_settings_anti_fraud_max_order_attempt_time_span">
					<?php
					echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_max_order_attempt_time_span']['name'] );
					$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_max_order_attempt_time_span'] );
					echo wp_kses_post( $description['tooltip_html'] );
					?>
				</label>
			</th>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_max_order_attempt_time_span" id="wc_settings_anti_fraud_max_order_attempt_time_span" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_max_order_attempt_time_span']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_max_order_attempt_time_span' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_max_order_attempt_time_span']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_max_order_attempt_time_span']['custom_attributes']['step'] ); ?>">
			</td>
		</tr>
		<?php if ( isset( $settings_fileds['wc_af_attempt_count_mode'] ) ) : ?>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_attempt_count_mode_alt">
					<?php echo wp_kses_post( $settings_fileds['wc_af_attempt_count_mode']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_attempt_count_mode']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-select" colspan="3">
				<select name="wc_af_attempt_count_mode" id="wc_af_attempt_count_mode_alt" style="min-width: 320px;">
					<?php
					$current_mode = get_option( 'wc_af_attempt_count_mode', 'orders_only' );
					foreach ( $settings_fileds['wc_af_attempt_count_mode']['options'] as $value => $label ) :
						?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_mode, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php echo wp_kses_post( $settings_fileds['wc_af_attempt_count_mode']['desc'] ); ?></p>
			</td>
		</tr>
		<?php endif; ?>

		<tr valign="top">
			<td colspan="2">
				<hr/>
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_order_payment_attempt_check">
					<?php echo wp_kses_post( $settings_fileds['wc_af_order_payment_attempt_check']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( $settings_fileds['wc_af_order_payment_attempt_check']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_order_payment_attempt_check']['title'] ); ?></span></legend>
					<label for="wc_af_order_payment_attempt_check" class="opmc-toggle-control">
						<input name="wc_af_order_payment_attempt_check" id="wc_af_order_payment_attempt_check" type="checkbox" value="1" <?php checked( get_option( 'wc_af_order_payment_attempt_check' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
			</td>
		</tr>

		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_settings_anti_fraud_max_order_payment_attempt">
					<?php
					echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_max_order_payment_attempt']['name'] );
					$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_max_order_payment_attempt'] );
					echo wp_kses_post( $description['tooltip_html'] );
					?>
				</label>
			</th>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_max_order_payment_attempt" id="wc_settings_anti_fraud_max_order_payment_attempt" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_max_order_payment_attempt']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_max_order_payment_attempt' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_max_order_payment_attempt']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_max_order_payment_attempt']['custom_attributes']['step'] ); ?>">
			</td>
		</tr>

		</tbody>

		</table>
		<?php wc_af_advanced_panel_end(); ?>
	</section>
<?php elseif ( 'minfraud_settings' == $current_section ) : ?>

<section class="wc-af-settings-panel wc-af-settings-panel--minfraud-score">
	<h2><?php echo esc_html__( 'MaxMind minFraud · Score', 'woocommerce-anti-fraud' ); ?></h2>
	<div id="<?php echo esc_attr( $this->id . '_minfraud_settings-description' ); ?>" class="wc-af-panel-intro">
		<?php echo wp_kses_post( $settings_fileds['wc_af_minfraud_settings']['desc'] ); ?>
	</div>

	<table class="form-table opmc_wc_af_table">
		<tbody>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_maxmind_type">
					<?php echo wp_kses_post( $settings_fileds['wc_af_maxmind_type']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_maxmind_type']['desc'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_maxmind_type']['title'] ); ?></span></legend>
					<label for="wc_af_maxmind_type" class="opmc-toggle-control">
						<input name="wc_af_maxmind_type" id="wc_af_maxmind_type" type="checkbox" value="1" <?php checked( get_option( 'wc_af_maxmind_type' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
				<!-- Add description below the toggle -->
				<p class="description"><?php echo wp_kses_post( $settings_fileds['wc_af_maxmind_type']['desc'] ); ?></p>
			</td>
		</tr>

		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_maxmind_device_tracking">
					<?php echo wp_kses_post( $settings_fileds['wc_af_maxmind_device_tracking']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_maxmind_device_tracking']['desc'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_maxmind_device_tracking']['title'] ); ?></span></legend>
					<label for="wc_af_maxmind_device_tracking" class="opmc-toggle-control">
						<input name="wc_af_maxmind_device_tracking" id="wc_af_maxmind_device_tracking" type="checkbox" value="1" <?php checked( get_option( 'wc_af_maxmind_device_tracking' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
				<!-- Add description below the toggle -->
				<p class="description"><?php echo wp_kses_post( $settings_fileds['wc_af_maxmind_device_tracking']['desc'] ); ?></p>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="wc_af_maxmind_user">
					<?php
					echo wp_kses_post( $settings_fileds['wc_af_maxmind_user']['name'] );
					$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_af_maxmind_user'] );
					echo wp_kses_post( $description['tooltip_html'] );
					?>
				</label>
			</th>
			<td class="forminp forminp-text">
				<input name="wc_af_maxmind_user" id="wc_af_maxmind_user" type="<?php echo esc_attr( $settings_fileds['wc_af_maxmind_user']['type'] ); ?>" style="<?php echo esc_attr( $settings_fileds['wc_af_maxmind_user']['css'] ); ?>" value="<?php echo esc_attr( get_option( 'wc_af_maxmind_user' ) ); ?>">
				<?php echo wp_kses_post( $description['description'] ); ?>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="wc_af_maxmind_license_key">
					<?php
					echo wp_kses_post( $settings_fileds['wc_af_maxmind_license_key']['name'] );
					$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_af_maxmind_license_key'] );
					echo wp_kses_post( $description['tooltip_html'] );
					?>
				</label>
			</th>
			<td class="forminp forminp-password">
				<input name="wc_af_maxmind_license_key" id="wc_af_maxmind_license_key" type="<?php echo esc_attr( $settings_fileds['wc_af_maxmind_license_key']['type'] ); ?>" style="<?php echo esc_attr( $settings_fileds['wc_af_maxmind_license_key']['css'] ); ?>" value="<?php echo esc_attr( get_option( 'wc_af_maxmind_license_key' ) ); ?>">
				<?php echo wp_kses_post( $description['description'] ); ?>
			</td>
		</tr>

		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_ip_geolocation_order">
					<?php echo wp_kses_post( $settings_fileds['wc_af_ip_geolocation_order']['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_ip_geolocation_order']['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $settings_fileds['wc_af_ip_geolocation_order']['title'] ); ?></span></legend>
					<label for="wc_af_ip_geolocation_order" class="opmc-toggle-control">
						<input name="wc_af_ip_geolocation_order" id="wc_af_ip_geolocation_order" type="checkbox" value="1" <?php checked( get_option( 'wc_af_ip_geolocation_order' ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
				<!-- Add description below the toggle -->
				<p class="description"><?php echo wp_kses_post( $settings_fileds['wc_af_ip_geolocation_order']['desc_tip'] ); ?></p>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="wc_settings_anti_fraud_ip_geolocation_order_weight">
					<?php
					echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_ip_geolocation_order_weight']['name'] );
					$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_ip_geolocation_order_weight'] );
					echo wp_kses_post( $description['tooltip_html'] );
					?>
				</label>
			</th>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_ip_geolocation_order_weight" id="wc_settings_anti_fraud_ip_geolocation_order_weight" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_ip_geolocation_order_weight']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_ip_geolocation_order_weight' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_ip_geolocation_order_weight']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_ip_geolocation_order_weight']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_ip_geolocation_order_weight']['custom_attributes']['max'] ); ?>">
				<?php echo wp_kses_post( $description['description'] ); ?>
			</td>
		</tr>
		</tbody>
	</table>

	<?php wc_af_advanced_panel_start( 'minfraud-score-tuning' ); ?>

	<?php $this->opmc_add_admin_field_section( $settings_fileds[ 'wc_af_minfraud_rule_settings' ] ); ?>

	<table class="form-table opmc_wc_af_table">
		<tbody>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="wc_settings_anti_fraud_minfraud_risk_score">
					<?php
					echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_minfraud_risk_score']['name'] );
					$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_minfraud_risk_score'] );
					echo wp_kses_post( $description['tooltip_html'] );
					?>
				</label>
			</th>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_minfraud_risk_score" id="wc_settings_anti_fraud_minfraud_risk_score" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_minfraud_risk_score']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_minfraud_risk_score' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_minfraud_risk_score']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_minfraud_risk_score']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_minfraud_risk_score']['custom_attributes']['max'] ); ?>">
				<?php echo wp_kses_post( $description['description'] ); ?>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="wc_settings_anti_fraud_minfraud_order_weight">
					<?php
					echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_minfraud_order_weight']['name'] );
					$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_minfraud_order_weight'] );
					echo wp_kses_post( $description['tooltip_html'] );
					?>
				</label>
			</th>
			<td class="forminp forminp-number">
				<input name="wc_settings_anti_fraud_minfraud_order_weight" id="wc_settings_anti_fraud_minfraud_order_weight" type="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_minfraud_order_weight']['type'] ); ?>" style="display: block; width: 5em;" value="<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_minfraud_order_weight' ) ); ?>" min="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_minfraud_order_weight']['custom_attributes']['min'] ); ?>" step="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_minfraud_order_weight']['custom_attributes']['step'] ); ?>" max="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_minfraud_order_weight']['custom_attributes']['max'] ); ?>">
				<?php echo wp_kses_post( $description['description'] ); ?>
			</td>
		</tr>
		</tbody>
	</table>
	<?php wc_af_advanced_panel_end(); ?>
</section>
<?php elseif ( 'chargeback_settings' === $current_section ) : ?>
	<section class="wc-af-settings-panel wc-af-settings-panel--chargeback">

		<h2><?php echo esc_html__( 'Chargeback programs', 'woocommerce-anti-fraud' ); ?></h2>
	   
			<div id="<?php echo esc_attr( $this->id . '_chargeback_settings-description' ); ?>" class="wc-af-panel-intro">

				<?php echo wp_kses_post( $settings_fileds[ $this->id . '_chargebacks' ]['desc'] ); ?>

			</div>
			 <table class="form-table opmc_wc_af_table">
			<tbody>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="wc_af_chargebacks_support">
						<?php
						echo wp_kses_post( $settings_fileds['wc_af_chargebacks_support']['title'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_af_chargebacks_support'] );
						//echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-number">
					<a href="https://opmc.com.au/chargeback-management-kount/" id="wc_af_chargebacks_support" class="button-secondary" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Chargeback management (Kount)', 'woocommerce-anti-fraud' ); ?>
					</a>
					<p class="description"><?php esc_html_e( 'Opens a secure form to start onboarding with Kount.', 'woocommerce-anti-fraud' ); ?></p>
	  
				</td>
			</tr>
			</tbody>
		</table>
		<div class="chargeback_text wc-af-panel-intro" style="margin: 1% 1% 0% 4%;">
			<p><strong><?php esc_html_e( 'Submit your details', 'woocommerce-anti-fraud' ); ?></strong><br>
				<?php esc_html_e( 'The form sends your business information securely to Kount.', 'woocommerce-anti-fraud' ); ?></p>
			<p><strong><?php esc_html_e( 'Kount will contact you', 'woocommerce-anti-fraud' ); ?></strong><br>
				<?php esc_html_e( 'Their team follows up to guide you through onboarding.', 'woocommerce-anti-fraud' ); ?></p>
			<p><strong><?php esc_html_e( 'Note', 'woocommerce-anti-fraud' ); ?></strong>
				<?php esc_html_e( 'Follow-up comes from Kount, not from this plugin’s authors.', 'woocommerce-anti-fraud' ); ?></p>
		</div>
	</section>

<?php elseif ( 'email_alert' == $current_section ) : ?>
	<section class="wc-af-settings-panel wc-af-settings-panel--email-alert">

		<h2><?php echo esc_html__( 'Email alerts', 'woocommerce-anti-fraud' ); ?></h2>
		<div id="<?php echo esc_attr( $this->id . '_email_alert_settings-description' ); ?>" class="wc-af-panel-intro">
			<?php echo wp_kses_post( $settings_fileds[ $this->id . '_email_alert_settings' ]['desc'] ); ?>
		</div>


		<table class="form-table opmc_wc_af_table">
			<tbody>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_email_notification">
						<?php echo wp_kses_post( $settings_fileds['wc_af_email_notification']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_email_notification']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo esc_attr( $settings_fileds['wc_af_email_notification']['title'] ); ?></span></legend>
						<label for="wc_af_email_notification" class="opmc-toggle-control">
							<input name="wc_af_email_notification" id="wc_af_email_notification" type="checkbox" value="1" <?php checked( get_option( 'wc_af_email_notification' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="wc_settings_anti_fraud_custom_email">
						<?php
						echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_custom_email']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_custom_email'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-textarea" colspan="3">
					<textarea name="wc_settings_anti_fraud_custom_email" id="wc_settings_anti_fraud_custom_email" style="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_custom_email']['css'] ); ?>" class="<?php echo esc_attr( $settings_fileds['wc_settings_anti_fraud_custom_email']['class'] ); ?>"><?php echo esc_html( get_option( 'wc_settings_anti_fraud_custom_email' ) ); ?></textarea>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="wc_settings_anti_fraud_email_score">
						<?php
						echo wp_kses_post( $settings_fileds['wc_settings_anti_fraud_email_score']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_settings_anti_fraud_email_score'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-select">
					<select name="wc_settings_anti_fraud_email_score" id="wc_settings_anti_fraud_email_score" style="display: block; width: 5em;" class="">
						<?php foreach ( $settings_fileds['wc_settings_anti_fraud_email_score']['options'] as $option_key_inner => $option_value_inner ) : ?>
							<option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( (string) $option_key_inner, esc_attr( get_option( 'wc_settings_anti_fraud_email_score' ) ) ); ?>><?php echo esc_html( $option_value_inner ); ?></option>
						<?php endforeach ?>
					</select>
				</td>
				<td class="forminp forminp-slider" colspan="2">
					<?php $this->opmc_score_slider( get_option( 'wc_settings_anti_fraud_email_score' ) ); ?>
				</td>
				</tr>
			</tbody>
		</table>

		<?php wc_af_advanced_panel_start( 'email-alerts' ); ?>

		<table class="form-table opmc_wc_af_table">
			<tbody>
				<!-- PLUGINS-195 Start Rate Limiting Enable -->
				<tr valign="top" class="">
					<th scope="row" class="titledesc">
						<label for="wc_af_email_rate_limit_enable">
							<?php echo wp_kses_post( $settings_fileds['wc_af_email_rate_limit_enable']['title'] ); ?>
							<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_email_rate_limit_enable']['desc_tip'] ); ?>"></span>
						</label>
					</th>
					<td class="forminp forminp-checkbox">
						<fieldset>
							<legend class="screen-reader-text"><span><?php echo esc_attr( $settings_fileds['wc_af_email_rate_limit_enable']['title'] ); ?></span></legend>
							<label for="wc_af_email_rate_limit_enable" class="opmc-toggle-control">
								<input name="wc_af_email_rate_limit_enable" id="wc_af_email_rate_limit_enable" type="checkbox" value="1" <?php checked( get_option( 'wc_af_email_rate_limit_enable' ), 'yes' ); ?> >
								<span class="opmc-control"></span>
							</label>
						</fieldset>
					</td>
				</tr>

				<!-- Max Emails -->
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="wc_af_email_rate_limit_max">
							<?php
							echo wp_kses_post( $settings_fileds['wc_af_email_rate_limit_max']['name'] );
							$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_af_email_rate_limit_max'] );
							echo wp_kses_post( $description['tooltip_html'] );
							?>
						</label>
					</th>
					<td class="forminp forminp-number">
						<input type="number" name="wc_af_email_rate_limit_max" id="wc_af_email_rate_limit_max" value="<?php echo esc_attr( get_option( 'wc_af_email_rate_limit_max', 5 ) ); ?>" min="1" step="1" style="width: 5em;" />
					</td>
				</tr>

				<!-- Time Window (minutes) -->
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="wc_af_email_rate_limit_window">
							<?php
							echo wp_kses_post( $settings_fileds['wc_af_email_rate_limit_window']['name'] );
							$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_af_email_rate_limit_window'] );
							echo wp_kses_post( $description['tooltip_html'] );
							?>
						</label>
					</th>
					<td class="forminp forminp-number">
						<input type="number" name="wc_af_email_rate_limit_window" id="wc_af_email_rate_limit_window" value="<?php echo esc_attr( get_option( 'wc_af_email_rate_limit_window', 30 ) ); ?>" min="1" step="1" style="width: 5em;" />
						<span> <?php esc_html_e( 'minutes', 'woocommerce-anti-fraud' ); ?></span>
					</td>
				</tr>
				<!-- PLUGINS-195 END -->

			</tbody>
		</table>

		<?php wc_af_advanced_panel_end(); ?>
	</section>
<?php
elseif ( 'recaptcha_settings' == $current_section ) :

	WC_AF_Settings_Recaptcha::render_fields();

elseif ( 'white_list' == $current_section ) :

	WC_AF_Settings_Whitelist::render_fields();

elseif ( 'black_list' == $current_section ) :

	WC_AF_Settings_Blacklist::render_fields();

// AI Fraud Prevention start

 elseif ( 'ai_fraud_prevention' === $current_section ) : 
		?>
	<section class="wc-af-settings-panel wc-af-settings-panel--ai">

		<h2><?php echo esc_html__( 'AI fraud signals', 'woocommerce-anti-fraud' ); ?></h2>
		<div id="<?php echo esc_attr( $this->id . '_ai_fraud_prevention_settings-description' ); ?>" class="wc-af-panel-intro">
			<?php echo wp_kses_post( $settings_fileds[ $this->id . '_ai_fraud_prevention_settings' ]['desc'] ); ?>
		</div>


		<table class="form-table opmc_wc_af_table">
			<tbody>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="wc_af_ai_fraud_prevention_check">
						<?php echo wp_kses_post( $settings_fileds['wc_af_ai_fraud_prevention_check']['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $settings_fileds['wc_af_ai_fraud_prevention_check']['desc_tip'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo esc_attr( $settings_fileds['wc_af_ai_fraud_prevention_check']['title'] ); ?></span></legend>
						<label for="wc_af_ai_fraud_prevention_check" class="opmc-toggle-control">
							<input name="wc_af_ai_fraud_prevention_check" id="wc_af_ai_fraud_prevention_check" type="checkbox" value="1" <?php checked( get_option( 'wc_af_ai_fraud_prevention_check' ), 'yes' ); ?> >
							<span class="opmc-control"></span>
						</label>
					</fieldset>
				</td>
			</tr>

			</tbody>
		</table>

		<?php wc_af_advanced_panel_start( 'ai-fraud-credentials' ); ?>

		<table class="form-table opmc_wc_af_table">
			<tbody>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="wc_af_ai_model">
						<?php
						echo wp_kses_post( $settings_fileds['wc_af_ai_model']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_af_ai_model'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-select">
					<select name="wc_af_ai_model" id="wc_af_ai_model" style="display: block; width: 15em;" class="">
						<?php foreach ( $settings_fileds['wc_af_ai_model']['options'] as $option_key_inner => $option_value_inner ) : ?>
							<option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( (string) $option_key_inner, esc_attr( get_option( 'wc_af_ai_model' ) ) ); ?>><?php echo esc_html( $option_value_inner ); ?></option>
						<?php endforeach ?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="wc_af_chatgpt_api_key">
						<?php
						echo wp_kses_post( $settings_fileds['wc_af_chatgpt_api_key']['name'] );
						$description = WC_Admin_Settings::get_field_description( $settings_fileds['wc_af_chatgpt_api_key'] );
						echo wp_kses_post( $description['tooltip_html'] );
						?>
					</label>
				</th>
				<td class="forminp forminp-number">
					<input name="wc_af_chatgpt_api_key" id="wc_af_chatgpt_api_key" type="<?php echo esc_attr( $settings_fileds['wc_af_chatgpt_api_key']['type'] ); ?>" placeholder="<?php echo esc_attr( $settings_fileds['wc_af_chatgpt_api_key']['placeholder'] ); ?>" value="<?php echo esc_attr( get_option( 'wc_af_chatgpt_api_key' ) ); ?>">
					<p class="description"><?php echo wp_kses_post( $settings_fileds['wc_af_chatgpt_api_key']['desc'] ); ?></p>
				</td>
			</tr>

			</tbody>
		</table>

		<?php wc_af_advanced_panel_end(); ?>
	</section>
	<!-- AI Fraud Prevention End -->
<?php 
elseif ( 'recaptcha_settings' == $current_section ) :

	WC_AF_Settings_Recaptcha::render_fields();

else : 
	?>

	<?php WC_Admin_Settings::output_fields( $settings ); ?>

<?php endif; ?>
</div>
