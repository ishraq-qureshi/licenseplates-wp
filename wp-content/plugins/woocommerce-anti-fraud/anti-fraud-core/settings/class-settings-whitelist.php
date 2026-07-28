<?php
/**
 * Class Settings - Recaptcha
 */

class WC_AF_Settings_Whitelist extends WC_AF_Settings_Base {

	public static function get_settings() {

		/** Country Whitelisting */
		$country_options = array();

		if ( function_exists( 'wc' ) && wc() && isset( wc()->countries ) && is_object( wc()->countries ) ) {
			// Preferred: use the running WC instance
			$country_options = wc()->countries->get_countries();
		} else {
			// Fallback: include the class file and instantiate directly
			if ( ! class_exists( 'WC_Countries' ) && function_exists( 'WC' ) ) {
				$path = WC()->plugin_path() . '/includes/class-wc-countries.php';
				if ( file_exists( $path ) ) {
					include_once $path;
				}
			}
			if ( class_exists( 'WC_Countries' ) ) {
				$country_options = ( new WC_Countries() )->get_countries();
			}
		}
		/** END Country Whitelisting */

		global $wp_roles;
		$user_roles = array();
		$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles' );
		if ( empty( $wc_af_whitelist_user_roles ) ) {
			$wc_af_whitelist_user_roles = array();
		}
		// print_r($wc_af_whitelist_user_roles);
		$wc_af_whitelist_payment_methods = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );
		if ( empty( $wc_af_whitelist_payment_methods ) ) {
			$wc_af_whitelist_payment_methods = array();
		}
		$all_roles = $wp_roles->roles;

		/**
		 * Editable roles
		 *
		 * @since 1.0.0
		 */
		$editable_roles = apply_filters( 'editable_roles', $all_roles );
		foreach ( $editable_roles as $role => $details ) {
			$role = esc_attr( $role );
			$name = translate_user_role( $details['name'] );
			$user_roles[ $role ] = $name;
		}

		$installed_payment_methods = WC()->payment_gateways->payment_gateways();
		$availableMethods = array();

		foreach ( $installed_payment_methods as $methods => $arr ) {
			// Check if payment method is enabled AND has a method title
			if ( isset( $arr->enabled ) && 'yes' === $arr->enabled && 
				 isset( $arr->method_title ) && ! empty( $arr->method_title ) ) {
				$availableMethods[ $methods ] = $arr->method_title;
			}
		}

		$settings = array(
			array(
				'name' => __( 'Allow list', 'woocommerce-anti-fraud' ),
				'type' => 'title',
				'desc' => __( 'Trusted emails, IPs, roles, or payment methods can receive softer scoring or skip certain automated actions, depending on your rules.', 'woocommerce-anti-fraud' ),
				'id'   => 'wc_af_whitelist_settings',
			),
			array(
				'name'     => __( 'Trusted payment methods', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Gateways you mark here can be excluded from automatic holds or cancellations tied to risk score.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_whitelist_payment_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),
			array(
				'title'    => __( 'Skip holds and cancellations for trusted gateways', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'When on, the gateways you select below are not subject to those automatic order status changes.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_whitelist_payment_method',
			),
			array(
				'title'    => __( 'Trusted gateways', 'woocommerce-anti-fraud' ),
				'type'     => 'multiselect',
				'options'  => $availableMethods,
				'desc'     => '',
				'desc_tip' => __( 'Use Ctrl/Cmd+click to select multiple payment methods.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_settings_anti_fraud_whitelist_payment_method',
				'default'  => $wc_af_whitelist_payment_methods,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_whitelist_payment_settings',
			),
			array(
				'name'     => __( 'Trusted emails', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Matching customers can get softer scoring or bypass certain checks, depending on your other settings.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_email_whitelist_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),

			// Email list table section
			array(
				'name'     => __( 'Email addresses (allow list)', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'Comma, space, or line separated.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_settings_anti_fraud_whitelist',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),

			// CSV Import Section
			array(
				'name'              => __( 'Import emails (CSV)', 'woocommerce-anti-fraud' ),
				'type'              => 'file',
				'desc'              => __( 'One email per row or column.', 'woocommerce-anti-fraud' ),
				'id'                => 'wc_settings_wc_af_email_whitelist_csv',
				'css'               => 'width:100%;',
				'class'             => 'wc_af_csv_upload',
				'default'           => '',
				'custom_attributes' => array(
					'accept' => '.csv',
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_email_whitelist_settings',
			),


			/* Ip Whitelist  */
			array(
				'name'     => __( 'IP allow list', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'IPs you add here are treated as trusted. Some flows may also add IPs when you unblock them. Location-related browser prompts can apply depending on your setup.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_ips_whitelist_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;'
			),
			array(
				'name'     => __( 'IP addresses', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'IPv4 or IPv6. Separate with commas, spaces, or new lines.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_settings_anti_fraud_ips_whitelist',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_ips_whitelist_settings'
			),
			/* Ip Whitelist  End */

			array(
				'name'     => __( 'User roles', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Let selected WordPress roles bypass or soften fraud scoring.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_user_role_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),
			array(
				'title'    => __( 'Enable role-based allow list', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to apply the roles you select below.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_whitelist_user_roles',
			),
			array(
				'title'       => __( 'Allowed roles', 'woocommerce-anti-fraud' ),
				'type'        => 'multiselect',
				'options'     => $user_roles,
				'desc'        => '',
				'desc_tip'    => __( 'Ctrl/Cmd+click to select multiple roles.', 'woocommerce-anti-fraud' ),
				'id'          => 'wc_af_whitelist_user_roles',
				'placeholder' => __( 'Select roles', 'woocommerce-anti-fraud' ),
				'default'     => $wc_af_whitelist_user_roles,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_user_role_settings',
			),

			array(
				'name'     => __( 'Phone numbers', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Numbers on this list can bypass order blocking from fraud rules.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_phone_number_wl_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),
			array(
				'title'    => __( 'Enable phone allow list', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the list below.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_whitelist_phone_number',
			),
			array(
				'name'     => __( 'Phone Numbers', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'Separate numbers with commas or spaces.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_whitelist_phone_numbers',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_phone_number_wl_settings',
			),


			/** First/Last Name Blacklisting */
			array(
				'name'     => __( 'Names', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Optional: allow checkout when the billing first or last name matches an entry below.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_name_wl_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),

			array(
				'title'    => __( 'Match first names', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the first-name list.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_whitelisting_first_name',
			),

			array(
				'name'     => __( 'First names', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'One per line or comma-separated. Matching customers can proceed per your rules.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_whitelisted_first_names',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),

			array(
				'title'    => __( 'Match last names', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the last-name list.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_whitelisting_last_name',
			),

			array(
				'name'     => __( 'Whitelist Last Names', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'Enter whitelisting last names, separated by commas or new lines. Customers with these last names will be allowed to place orders.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_whitelisted_last_names',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_name_wl_settings',
			),
			/** First/Last Name Blacklisting End */


			/** Address Blacklisting */
			array(
				'name'     => __( 'Address keywords', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'If billing or shipping address contains any phrase below, the order can be treated as allowed (e.g. internal test addresses).', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_address_wl_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),
			array(
				'title'    => __( 'Enable address keyword allow list', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the list below.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_whitelist_address',
			),
			array(
				'name'     => __( 'Address phrases', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'Comma or new-line separated. Partial matches count.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_whitelisted_address',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_address_wl_settings',
			),
			/** Address Blacklisting End */

			array(
				'name'     => __( 'Countries', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Customers in selected countries can bypass order blocking from fraud rules.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_country_wl_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),
			array(
				'title'    => __( 'Enable country allow list', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the country list below.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_whitelist_country',
			),
			array(
				'name'     => __( 'Allowed countries', 'woocommerce-anti-fraud' ),
				'type'     => 'multiselect',
				'desc'     => __( 'Choose countries to allow.', 'woocommerce-anti-fraud' ),
				'desc_tip' => __( 'These countries bypass blocking when this rule is active.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_whitelisted_countries',
				'class'    => 'wc-enhanced-select',
				'css'      => 'width: 50%;',
				'default'  => array(),
				'options'  => $country_options,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_country_wl_settings',
			),


			array(
				'name'     => __( 'Cities', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Customers in selected cities can bypass order blocking.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_city_wl_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),
			array(
				'title'    => __( 'Enable city allow list', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the city list below.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_whitelist_city',
			),
			array(
				'name'     => __( 'Allowed cities', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'One per line or comma-separated.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_whitelisted_city',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_city_wl_settings',
			),


			array(
				'name'     => __( 'States / regions', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Customers in selected states or regions can bypass order blocking.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_state_wl_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),
			array(
				'title'    => __( 'Enable state allow list', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the state list below.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_whitelist_state',
			),
			array(
				'name'     => __( 'Allowed states', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'One per line or comma-separated. Use the same spelling customers enter at checkout.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_whitelisted_states',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_state_wl_settings',
			),

			array(
				'name'     => __( 'Postal codes', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Customers with matching ZIP or postal codes can bypass order blocking.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_zip_wl_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),
			array(
				'title'    => __( 'Enable postal code allow list', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the list below.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_whitelist_zip',
			),
			array(
				'name'     => __( 'Allowed postal codes', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'One per line or comma-separated.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_whitelisted_zip',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_zip_wl_settings',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_whitelist_settings',
			),
		);
		/**
		 * Filters the whitelist settings before they are used by WooCommerce Anti-Fraud.
		 *
		 * This filter allows developers to modify the whitelist settings array before it's processed.
		 * Useful for adding, removing, or modifying whitelist rules programmatically.
		 *
		 * @since 7.0.8
		 * @param array $settings The current whitelist settings array.
		 * @return array Modified whitelist settings array.
		 */
		return apply_filters( 'wc_af_whitelist_settings', $settings );
	}

	public static function render_fields() {

		$setting_fields = self::get_setting_fields( self::get_settings() );

		?>
		<section>
			<h2><?php esc_html_e( 'Allow list', 'woocommerce-anti-fraud' ); ?></h2>
			<div id="<?php echo esc_attr( 'wc_af_whitelist_settings-description' ); ?>">
				<?php echo wp_kses_post( $setting_fields['wc_af_whitelist_settings']['desc'] ); ?>
			</div>
			<hr/>

			<div class="wc-af-settings-list wc-af-settings-list--basic">
			<p class="wc-af-settings-list__intro"><?php esc_html_e( 'Most stores only need payment methods, emails, and names here. Expand Advanced when you need IPs, roles, or location lists.', 'woocommerce-anti-fraud' ); ?></p>

			<?php self::render_field_section( $setting_fields['wc_af_whitelist_payment_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
				<tr valign="top" class="">
					<th scope="row" class="titledesc">
						<label for="wc_af_enable_whitelist_payment_method">
							<?php echo wp_kses_post( $setting_fields['wc_af_enable_whitelist_payment_method']['title'] ); ?>
							<span class="woocommerce-help-tip" data-tip="<?php echo wp_kses_post( $setting_fields['wc_af_enable_whitelist_payment_method']['desc_tip'] ); ?>"></span>
						</label>
					</th>
					<td class="forminp forminp-checkbox">
						<fieldset>
							<legend class="screen-reader-text"><span><?php echo wp_kses_post( $setting_fields['wc_af_enable_whitelist_payment_method']['title'] ); ?></span></legend>
							<label for="wc_af_enable_whitelist_payment_method" class="opmc-toggle-control">
								<input name="wc_af_enable_whitelist_payment_method" id="wc_af_enable_whitelist_payment_method" type="checkbox" value="1" <?php checked( get_option( 'wc_af_enable_whitelist_payment_method' ), 'yes' ); ?> >
								<span class="opmc-control"></span>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="wc_settings_anti_fraud_whitelist_payment_method">
							<?php
							echo wp_kses_post( $setting_fields['wc_settings_anti_fraud_whitelist_payment_method']['title'] );
							$description = WC_Admin_Settings::get_field_description( $setting_fields['wc_settings_anti_fraud_whitelist_payment_method'] );
							echo wp_kses_post( $description['tooltip_html'] );
							?>
						</label>
					</th>
					<td class="forminp forminp-multiselect">
						<select name="wc_settings_anti_fraud_whitelist_payment_method[]" id="wc_settings_anti_fraud_whitelist_payment_method" style="" class="" multiple="multiple">
							<?php foreach ( $setting_fields['wc_settings_anti_fraud_whitelist_payment_method']['options'] as $option_key_inner => $option_value_inner ) : ?>
								<option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( in_array( (string) $option_key_inner, $setting_fields['wc_settings_anti_fraud_whitelist_payment_method']['default'] ) ); ?>><?php echo esc_html( $option_value_inner ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				</tbody>
			</table>

			<!-- Email Whitelist -->
			<?php self::render_field_section( $setting_fields['wc_af_email_whitelist_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
				<tr valign="top" class="">
					<th scope="row" class="titledesc">
						<label for="wc_settings_anti_fraud_whitelist">
							<?php echo wp_kses_post( $setting_fields['wc_settings_anti_fraud_whitelist']['name'] ); ?>
							<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_settings_anti_fraud_whitelist']['desc_tip'] ); ?>"></span>
						</label>
					</th>
					<td class="forminp forminp-textarea" colspan="3">
						<?php
						$email_whitelist = get_option( 'wc_settings_anti_fraud_whitelist' );
						$emails_array    = array_filter( array_map('trim', preg_split('/[\s,]+/', $email_whitelist, -1, PREG_SPLIT_NO_EMPTY)));
						?>

						<div style="margin-bottom: 10px;">
							<textarea id="wc_af_bulk_email_paste" placeholder="<?php echo esc_attr__( 'Emails, separated by commas or spaces', 'woocommerce-anti-fraud' ); ?>" rows="5" style="width: 100%; max-width: 500px;"></textarea>
							<button type="button" id="wc_af_add_bulk_email_button" class="button button-secondary" style="margin-top: 5px;">
								<?php esc_html_e( 'Add Emails', 'woocommerce-anti-fraud' ); ?>
							</button>
						</div>

						<div style="margin-bottom: 10px;">
							<input type="file" name="wc_settings_anti_fraud_whitelist_csv" id="wc_settings_anti_fraud_whitelist_csv" accept=".csv"/>
							<button type="button" id="wc_af_import_csv_button" class="button button-primary">
								<?php esc_html_e( 'Import Emails via CSV', 'woocommerce-anti-fraud' ); ?>
							</button>
							<button type="button" id="show_csv_format_button" class="button" style="margin-left: 10px; background-color: #f3f3f3; border: 1px solid #ccc; color: #333;">
								<?php esc_html_e( 'View CSV Format', 'woocommerce-anti-fraud' ); ?>
							</button>
						</div>

						<!-- CSV Format Modal/Popup -->
						<div id="csv_format_modal" style="display: none; position: fixed; z-index: 9999; left: 50%; top: 50%; width: 420px; background-color: #fff; border: 1px solid #ccc; padding: 15px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); transform: translate(-50%, -50%); border-radius: 8px;">
							<h3 style="margin-top: 0; font-size: 18px;">📄 CSV Format Instructions</h3>
							<p style="font-size: 14px; color: #555;">Each email should be in a new row.</p>

							<table id="csv_format_table" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
								<thead>
								<tr style="background-color: #f3f3f3;">
									<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Email Address</th>
								</tr>
								</thead>
								<tbody>
								<tr>
									<td style="padding: 8px; border: 1px solid #ddd;">example1@example.com</td>
								</tr>
								<tr>
									<td style="padding: 8px; border: 1px solid #ddd;">example2@example.com</td>
								</tr>
								<tr>
									<td style="padding: 8px; border: 1px solid #ddd;">example3@example.com</td>
								</tr>
								</tbody>
							</table>

							<button type="button" id="close_csv_format_button" class="button" style="margin-top: 10px; background-color: #d9534f; color: #fff; border: none; padding: 8px 12px; border-radius: 5px;">Close</button>
						</div>

						<!-- Modal Background/Overlay -->
						<div id="csv_modal_overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.4); z-index: 9998;"></div>

						<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; border-radius: 5px; margin-top: 10px;">
							<table id="wc_af_email_list" class="widefat fixed striped" style="width: 100%; border-collapse: collapse;">
								<thead style="position: sticky; top: 0; background: #dbdbdb; z-index: 1;">
								<tr>
									<th style="width: 5%; text-align: center; padding: 10px;">
										<input type="checkbox" id="wc_af_select_all"/>
									</th>
									<th style="width: 75%; padding: 10px; text-align: left;"><?php esc_html_e( 'Email Address', 'woocommerce-anti-fraud' ); ?></th>
									<th style="width: 20%; padding: 10px; text-align: center;"><?php esc_html_e( 'Actions', 'woocommerce-anti-fraud' ); ?></th>
								</tr>
								</thead>
								<tbody>
								<?php if ( empty( $emails_array ) ) : ?>
									<tr id="no-emails">
										<td colspan="3" style="padding: 10px; text-align: center;">
											<?php esc_html_e( 'No emails added yet.', 'woocommerce-anti-fraud' ); ?>
										</td>
									</tr>
								<?php else : ?>
									<?php foreach ( $emails_array as $email ) : ?>
										<tr data-email="<?php echo esc_attr( $email ); ?>" style="border-bottom: 1px solid #e1e1e1;">
											<td style="text-align: center; padding: 8px;">
												<input type="checkbox" class="wc_af_email_checkbox" data-email="<?php echo esc_attr( $email ); ?>"/>
											</td>
											<td style="padding: 8px;"><?php echo esc_html( $email ); ?></td>
											<td style="text-align: center; padding: 8px;">
												<a href="#" class="remove-email" data-email="<?php echo esc_attr( $email ); ?>" style="color: #d9534f;">
													<?php esc_html_e( 'Remove', 'woocommerce-anti-fraud' ); ?>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
								</tbody>
							</table>
						</div>

						<button type="button" id="wc_af_bulk_delete_button" class="button button-secondary" style="margin-top: 10px;">
							<?php esc_html_e( 'Delete Selected', 'woocommerce-anti-fraud' ); ?>
						</button>

						<input type="hidden" name="wc_settings_anti_fraud_whitelist" id="wc_settings_anti_fraud_imported_emails" value="<?php echo esc_attr( $email_whitelist ); ?>"/>
					</td>
				</tr>
				</tbody>
			</table>

			<?php self::render_field_section( $setting_fields['wc_af_name_wl_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
					<?php self::render_field( $setting_fields['wc_af_enable_whitelisting_first_name'] ); ?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="wc_af_whitelisted_first_names">
								<?php echo wp_kses_post( $setting_fields['wc_af_whitelisted_first_names']['name'] ); ?>
								<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_first_names']['desc_tip'] ); ?>"></span>
							</label>
						</th>
						<td class="forminp forminp-textarea" colspan="3">
							<textarea
								name="wc_af_whitelisted_first_names"
								id="wc_af_whitelisted_first_names"
								style="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_first_names']['css'] ); ?>"
								class="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_first_names']['class'] ); ?>"
							><?php echo esc_html( get_option( 'wc_af_whitelisted_first_names' ) ); ?></textarea>
						</td>
					</tr>
					<?php self::render_field( $setting_fields['wc_af_enable_whitelisting_last_name'] ); ?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="wc_af_whitelisted_last_names">
								<?php echo wp_kses_post( $setting_fields['wc_af_whitelisted_last_names']['name'] ); ?>
								<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_last_names']['desc_tip'] ); ?>"></span>
							</label>
						</th>
						<td class="forminp forminp-textarea" colspan="3">
							<textarea
								name="wc_af_whitelisted_last_names"
								id="wc_af_whitelisted_last_names"
								style="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_last_names']['css'] ); ?>"
								class="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_last_names']['class'] ); ?>"
							><?php echo esc_html( get_option( 'wc_af_whitelisted_last_names' ) ); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>

			</div>

			<details class="wc-af-settings-advanced">
			<summary class="wc-af-settings-advanced__summary"><?php esc_html_e( 'Advanced allow list', 'woocommerce-anti-fraud' ); ?></summary>
			<div class="wc-af-settings-advanced__inner">

			<!-- Ip Whitelist  -->
			<?php self::render_field_section( $setting_fields['wc_af_ips_whitelist_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
				<tr valign="top" class="">
					<th scope="row" class="titledesc">
						<label for="wc_settings_anti_fraud_ips_whitelist">
							<?php echo wp_kses_post( $setting_fields['wc_settings_anti_fraud_ips_whitelist']['name'] ); ?>
							<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_settings_anti_fraud_ips_whitelist']['desc_tip'] ); ?>"></span>
						</label>
					</th>
					<td class="forminp forminp-textarea" colspan="3">
						<?php
						$ip_whitelist = str_replace( "\n", ',', get_option( 'wc_settings_anti_fraud_ips_whitelist' ) );
						$ip_blacklist = '';

						if ( get_option( 'wc_settings_anti_fraudenable_automatic_ip_blacklist' ) === 'yes' ) {
							$ip_blacklist = str_replace( "\n", ',', get_option( 'wc_settings_anti_fraudblacklist_ipaddress' ) );
						}
						?>
						<textarea data-blacklist-ips="<?php echo esc_attr( $ip_blacklist ); ?>" name="wc_settings_anti_fraud_ips_whitelist" id="wc_settings_anti_fraud_ips_whitelist" style="<?php echo esc_attr( $setting_fields['wc_settings_anti_fraud_ips_whitelist']['css'] ); ?>" class="<?php echo esc_attr( $setting_fields['wc_settings_anti_fraud_ips_whitelist']['class'] ); ?>"><?php echo esc_html( $ip_whitelist ); ?></textarea>
					</td>
				</tr>
				</tbody>
			</table>
			<!-- /* End */ -->

			<?php self::render_field_section( $setting_fields['wc_af_user_role_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
				<tr valign="top" class="">
					<th scope="row" class="titledesc">
						<label for="wc_af_enable_whitelist_user_roles">
							<?php echo wp_kses_post( $setting_fields['wc_af_enable_whitelist_user_roles']['title'] ); ?>
							<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_enable_whitelist_user_roles']['desc_tip'] ); ?>"></span>
						</label>
					</th>
					<td class="forminp forminp-checkbox">
						<fieldset>
							<legend class="screen-reader-text"><span><?php echo wp_kses_post( $setting_fields['wc_af_enable_whitelist_user_roles']['title'] ); ?></span></legend>
							<label for="wc_af_enable_whitelist_user_roles" class="opmc-toggle-control">
								<input name="wc_af_enable_whitelist_user_roles" id="wc_af_enable_whitelist_user_roles" type="checkbox" value="1" <?php checked( get_option( 'wc_af_enable_whitelist_user_roles' ), 'yes' ); ?> >
								<span class="opmc-control"></span>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="wc_af_whitelist_user_roles">
							<?php
							echo wp_kses_post( $setting_fields['wc_af_whitelist_user_roles']['title'] );
							$description = WC_Admin_Settings::get_field_description( $setting_fields['wc_af_whitelist_user_roles'] );
							echo wp_kses_post( $description['tooltip_html'] );
							?>
						</label>
					</th>
					<td class="forminp forminp-multiselect">
						<select name="wc_af_whitelist_user_roles[]" id="wc_af_whitelist_user_roles" style="" class="" multiple="multiple">
							<?php foreach ( $setting_fields['wc_af_whitelist_user_roles']['options'] as $option_key_inner => $option_value_inner ) : ?>
								<option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( in_array( (string) $option_key_inner, $setting_fields['wc_af_whitelist_user_roles']['default'] ) ); ?>><?php echo esc_html( $option_value_inner ); ?></option>
							<?php endforeach ?>
						</select>
					</td>
				</tr>
				</tbody>
			</table>


			<!-- Mobile numbers Whitelist  -->
			<?php self::render_field_section( $setting_fields['wc_af_phone_number_wl_settings'] ); ?>

			<table class="form-table opmc_wc_af_table">
				<tbody>
				<?php self::render_field( $setting_fields['wc_af_enable_whitelist_phone_number'] ); ?>

				<?php self::render_field( $setting_fields['wc_af_whitelist_phone_numbers'] ); ?>
				</tbody>
			</table>
			<!-- Mobile numbers Whitelist End -->

			<!-- Address Whitelist -->
			<?php self::render_field_section( $setting_fields['wc_af_address_wl_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
					<?php self::render_field( $setting_fields['wc_af_enable_whitelist_address'] ); ?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="wc_af_whitelisted_address">
								<?php echo wp_kses_post( $setting_fields['wc_af_whitelisted_address']['name'] ); ?>
								<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_address']['desc_tip'] ); ?>"></span>
							</label>
						</th>
						<td class="forminp forminp-textarea" colspan="3">
							<textarea 
								name="wc_af_whitelisted_address" 
								id="wc_af_whitelisted_address" 
								style="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_address']['css'] ); ?>" 
								class="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_address']['class'] ); ?>"
							><?php echo esc_html( get_option( 'wc_af_whitelisted_address' ) ); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>
		<!-- City Whitelist End -->

			<!-- Country Whitelist -->
			<?php self::render_field_section( $setting_fields['wc_af_country_wl_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
					<?php self::render_field( $setting_fields['wc_af_enable_whitelist_country'] ); ?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="wc_af_whitelisted_countries">
								<?php echo wp_kses_post( $setting_fields['wc_af_whitelisted_countries']['name'] ); ?>
								<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_countries']['desc_tip'] ); ?>"></span>
							</label>
						</th>
						<td class="forminp forminp-select">
							<select 
								multiple="multiple" 
								name="wc_af_whitelisted_countries[]" 
								id="wc_af_whitelisted_countries" 
								class="wc-enhanced-select" 
								style="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_countries']['css'] ); ?>"
							>
								<?php
								$saved_countries = (array) get_option( 'wc_af_whitelisted_countries', [] );
								foreach ( $setting_fields['wc_af_whitelisted_countries']['options'] as $key => $label ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $saved_countries ), true, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			<!-- Country Whitelist End -->


			<!-- City Whitelist -->
			<?php self::render_field_section( $setting_fields['wc_af_city_wl_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
					<?php self::render_field( $setting_fields['wc_af_enable_whitelist_city'] ); ?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="wc_af_whitelisted_city">
								<?php echo wp_kses_post( $setting_fields['wc_af_whitelisted_city']['name'] ); ?>
								<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_city']['desc_tip'] ); ?>"></span>
							</label>
						</th>
						<td class="forminp forminp-textarea" colspan="3">
							<textarea 
								name="wc_af_whitelisted_city" 
								id="wc_af_whitelisted_city" 
								style="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_city']['css'] ); ?>" 
								class="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_city']['class'] ); ?>"
							><?php echo esc_html( get_option( 'wc_af_whitelisted_city' ) ); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>
		<!-- City Whitelist End -->

			<!-- State Whitelist -->
			<?php self::render_field_section( $setting_fields['wc_af_state_wl_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
					<?php self::render_field( $setting_fields['wc_af_enable_whitelist_state'] ); ?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="wc_af_whitelisted_states">
								<?php echo wp_kses_post( $setting_fields['wc_af_whitelisted_states']['name'] ); ?>
								<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_states']['desc_tip'] ); ?>"></span>
							</label>
						</th>
						<td class="forminp forminp-textarea" colspan="3">
							<textarea 
								name="wc_af_whitelisted_states" 
								id="wc_af_whitelisted_states" 
								style="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_states']['css'] ); ?>" 
								class="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_states']['class'] ); ?>"
							><?php echo esc_html( get_option( 'wc_af_whitelisted_states' ) ); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>
		<!-- State Whitelist End -->

		<!-- Zip Whitelist -->
			<?php self::render_field_section( $setting_fields['wc_af_zip_wl_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
					<?php self::render_field( $setting_fields['wc_af_enable_whitelist_zip'] ); ?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="wc_af_whitelisted_zip">
								<?php echo wp_kses_post( $setting_fields['wc_af_whitelisted_zip']['name'] ); ?>
								<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_zip']['desc_tip'] ); ?>"></span>
							</label>
						</th>
						<td class="forminp forminp-textarea" colspan="3">
							<textarea 
								name="wc_af_whitelisted_zip" 
								id="wc_af_whitelisted_zip" 
								style="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_zip']['css'] ); ?>" 
								class="<?php echo esc_attr( $setting_fields['wc_af_whitelisted_zip']['class'] ); ?>"
							><?php echo esc_html( get_option( 'wc_af_whitelisted_zip' ) ); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>
		<!-- Zip Whitelist End -->

			</div>
			</details>

		</section>
		<?php
	}
}
