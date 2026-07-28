<?php
/**
 * Class Settings - Recaptcha
 */

class WC_AF_Settings_Blacklist extends WC_AF_Settings_Base {

	public static function get_settings() {

		/** Country Blacklisting */
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
		/** END Country Blacklisting */

		$settings = array(
			array(
				'name' => __( 'Block list', 'woocommerce-anti-fraud' ),
				'type' => 'title',
				'desc' => __( 'Stop repeat abuse by blocking specific emails, IPs, and other identifiers at checkout.', 'woocommerce-anti-fraud' ),
				'id'   => 'wc_af_blacklist_settings',
			),

			array(
				'name'     => __( 'Blocked emails', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Manual list plus optional auto-block from high-risk orders.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_sub_blacklist_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),
			// Enable email blacklist
			array(
				'title'    => __( 'Block listed emails', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => 'wc_af_email_blacklist',
				'default'  => 'no',
				'desc'     => '',
				'desc_tip' => __( 'When enabled, addresses on this list cannot complete checkout.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_settings_anti_fraudenable_automatic_email_blacklist',
			),
			// Enable automatic blacklisting
			array(
				'title'    => __( 'Auto-block high-risk emails', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => 'wc_af_automatic_blacklist',
				'default'  => 'no',
				'desc'     => '',
				'desc_tip' => __( 'Adds emails from orders that exceed your high-risk threshold so repeat attempts are stopped.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_settings_anti_fraudenable_automatic_blacklist',
			),
			// Block these email addresses
			array(
				'name'     => __( 'Manual email blocklist', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'Comma, space, or line separated. These addresses are always blocked at checkout.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_settings_anti_fraudblacklist_emails',
				'css'      => 'width:100%; height: 100px;',
				'default'  => '',
				'class'    => 'wc_af_tags_input',
			),

			array(
				'name'     => __( 'Blocked IP addresses', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Blocks checkout from listed IPs; optional auto-block from risky orders.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_sub_ip_blacklist_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),
			// Enable IP blacklist
			array(
				'title'    => __( 'Block listed IP addresses', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => 'wc_af_ip_blacklist',
				'default'  => 'no',
				'desc'     => __( 'Main switch for the manual IP list below.', 'woocommerce-anti-fraud' ),
				'desc_tip' => __( 'When on, IPs below cannot place orders.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_settings_anti_fraudenable_automatic_ip_blacklist',
			),

			/* Automatic IP blacklist section start */

			array(
				'title'    => __( 'Auto-block high-risk IPs', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => 'wc_af_automatic_ips_blacklist',
				'default'  => 'no',
				'desc'     => '',
				'desc_tip' => __( 'Adds IPs from high-risk orders so repeat visits are blocked.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_settings_anti_fraudenable_automatic_ips_blacklist',
			),
			
			// Block these email addresses
			array(
				'name'     => __( 'Manual IP blocklist', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'IPv4/IPv6, comma or line separated.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_settings_anti_fraudblacklist_ipaddress',
				'css'      => 'width:100%; height: 100px;',
				'default'  => '',
				'class'    => 'wc_af_tags_input',
			),

			/** Phone Number Blacklisting */
			array(
				'name'     => __( 'Phone numbers', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Block checkout when the phone number matches an entry below.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_phone_number_bl_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),
			array(
				'title'    => __( 'Enable phone block list', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the list below.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_blacklisting_phone_number',
			),
			array(
				'name'     => __( 'Phone Numbers', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'Separate numbers with commas or spaces.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_blacklisted_phone_numbers',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_phone_number_bl_settings',
			),
			/** Phone Number Blacklisting End */

			/** Country Blacklisting */
			array(
				'name'     => __( 'Countries', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Block checkout for customers in selected countries.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_country_bl_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),

			array(
				'title'    => __( 'Enable country block list', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the country list below.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_blacklisting_country',
			),

			array(
				'name'     => __( 'Blocked Countries', 'woocommerce-anti-fraud' ),
				'type'     => 'multiselect',
				'desc'     => __( 'Choose countries to block.', 'woocommerce-anti-fraud' ),
				'desc_tip' => __( 'Checkout is blocked for billing or shipping in these countries when this rule is active.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_blacklisted_countries',
				'class'    => 'wc-enhanced-select',
				'css'      => 'width: 50%;',
				'default'  => array(),
				'options'  => $country_options, // <-- use the safe array
			),

			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_country_bl_settings',
			),
			/* Country Blacklisting END */

			/** First/Last Name Blacklisting */
			array(
				'name'     => __( 'Names', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Block checkout when billing first or last name matches an entry below.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_name_bl_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),

			array(
				'title'    => __( 'Block by first name', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the first-name list.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_blacklisting_first_name',
			),

			array(
				'name'     => __( 'Blocked First Names', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'One per line or comma-separated. Matching checkouts are blocked.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_blacklisted_first_names',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),

			array(
				'title'    => __( 'Block by last name', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the last-name list.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_blacklisting_last_name',
			),

			array(
				'name'     => __( 'Blocked Last Names', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'One per line or comma-separated.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_blacklisted_last_names',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_name_bl_settings',
			),
			/** First/Last Name Blacklisting End */

			/** Address Blacklisting */
			array(
				'name'     => __( 'Address keywords', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Block checkout when billing or shipping address contains any phrase below.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_address_bl_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),

			array(
				'title'    => __( 'Enable address keyword block list', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the list below.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_blacklisting_address',
			),

			array(
				'name'     => __( 'Blocked phrases', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'One per line or comma-separated. Partial matches count (e.g. PO Box, test addresses).', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_blacklisted_addresses',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_address_bl_settings',
			),
			/** Address Blacklisting End */
			

			/** City Blacklisting */
			array(
				'name'     => __( 'Cities', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Block checkout for customers in selected cities.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_city_bl_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),

			array(
				'title'    => __( 'Enable city block list', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the city list below.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_blacklisting_city',
			),

			array(
				'name'     => __( 'Blocked Cities', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'One per line or comma-separated.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_blacklisted_cities',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_city_bl_settings',
			),
			/** City Blacklisting End */

			/** State Blacklisting */
			array(
				'name'     => __( 'States / regions', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Block checkout for customers in selected states or regions.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_state_bl_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),

			array(
				'title'    => __( 'Enable state block list', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the state list below.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_blacklisting_state',
			),

			array(
				'name'     => __( 'Blocked States', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'One per line or comma-separated.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_blacklisted_states',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_state_bl_settings',
			),
			/** State Blacklisting END*/

			/** Zip/Postal Code Blacklisting */
			array(
				'name'     => __( 'Postal codes', 'woocommerce-anti-fraud' ),
				'type'     => 'section',
				'desc'     => '',
				'desc_tip' => __( 'Block checkout when ZIP or postal code matches an entry below.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_zip_bl_settings',
				'class'    => 'wc_af_sub-section',
				'css'      => 'display: block;',
			),

			array(
				'title'    => __( 'Enable postal code block list', 'woocommerce-anti-fraud' ),
				'type'     => 'checkbox',
				'label'    => '',
				'desc'     => '',
				'desc_tip' => __( 'Turn on to use the list below.', 'woocommerce-anti-fraud' ),
				'default'  => 'no',
				'id'       => 'wc_af_enable_blacklisting_zipcode',
			),

			array(
				'name'     => __( 'Blocked Zip/Postal Codes', 'woocommerce-anti-fraud' ),
				'type'     => 'textarea',
				'desc'     => '',
				'desc_tip' => __( 'One per line or comma-separated.', 'woocommerce-anti-fraud' ),
				'id'       => 'wc_af_blacklisted_zipcodes',
				'css'      => 'width:100%; height: 100px;',
				'class'    => 'wc_af_tags_input',
				'default'  => '',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_zip_bl_settings',
			),
			/** Zip/Postal Code Blacklisting End */

			array(
				'type' => 'sectionend',
				'id'   => 'wc_af_blacklist_settings',
			),
		);
		
		/**
		 * Filters the blacklist settings for WooCommerce Anti-Fraud.
		 *
		 * Allows modification of the blacklist settings array before it's used.
		 *
		 * @since 7.0.8
		 * @param array $settings The current blacklist settings array.
		 * @return array The filtered blacklist settings.
		 */
		return apply_filters( 'wc_af_settings_blacklist', $settings );
	}



	public static function render_fields() {

		$setting_fields = self::get_setting_fields( self::get_settings() );

		?>
		<section>
			<h2><?php esc_html_e( 'Block list', 'woocommerce-anti-fraud' ); ?></h2>
			<div id="<?php echo esc_attr( 'wc_af_blacklist_settings-description' ); ?>">
				<?php echo wp_kses_post( $setting_fields['wc_af_blacklist_settings']['desc'] ); ?>
			</div>
			<hr/>
			<div class="wc-af-settings-list wc-af-settings-list--basic">
			<p class="wc-af-settings-list__intro"><?php esc_html_e( 'Start with emails, names, and address phrases to catch dummy or repeat-abuse checkouts. Expand Advanced for IPs, phones, and location lists.', 'woocommerce-anti-fraud' ); ?></p>
			<?php self::render_field_section( $setting_fields['wc_af_sub_blacklist_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
				<tr valign="top" class="">
					<th scope="row" class="titledesc">
						<label for="wc_af_email_blacklist">
							<?php echo wp_kses_post( $setting_fields['wc_settings_anti_fraudenable_automatic_email_blacklist']['title'] ); ?>
							<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_settings_anti_fraudenable_automatic_email_blacklist']['desc_tip'] ); ?>"></span>
						</label>
					</th>
					<td class="forminp forminp-checkbox">
						<fieldset>
							<legend class="screen-reader-text"><span><?php echo esc_attr( $setting_fields['wc_settings_anti_fraudenable_automatic_email_blacklist']['title'] ); ?></span></legend>
							<label for="wc_settings_anti_fraudenable_automatic_email_blacklist" class="opmc-toggle-control">
								<input name="wc_settings_anti_fraudenable_automatic_email_blacklist" id="wc_settings_anti_fraudenable_automatic_email_blacklist" type="checkbox" value="1" <?php checked( get_option( 'wc_settings_anti_fraudenable_automatic_email_blacklist' ), 'yes' ); ?> >
								<span class="opmc-control"></span>
							</label>
						</fieldset>
					</td>
				</tr>

				<tr valign="top" class="">
					<th scope="row" class="titledesc">
						<label for="wc_af_automatic_blacklist">
							<?php echo wp_kses_post( $setting_fields['wc_settings_anti_fraudenable_automatic_blacklist']['title'] ); ?>
							<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_settings_anti_fraudenable_automatic_blacklist']['desc_tip'] ); ?>"></span>
						</label>
					</th>
					<td class="forminp forminp-checkbox">
						<fieldset>
							<legend class="screen-reader-text"><span><?php echo esc_attr( $setting_fields['wc_settings_anti_fraudenable_automatic_blacklist']['title'] ); ?></span></legend>
							<label for="wc_settings_anti_fraudenable_automatic_blacklist" class="opmc-toggle-control">
								<input name="wc_settings_anti_fraudenable_automatic_blacklist" id="wc_settings_anti_fraudenable_automatic_blacklist" type="checkbox" value="1" <?php checked( get_option( 'wc_settings_anti_fraudenable_automatic_blacklist' ), 'yes' ); ?> >
								<span class="opmc-control"></span>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="wc_settings_anti_fraudblacklist_emails">
							<?php
							echo wp_kses_post( $setting_fields['wc_settings_anti_fraudblacklist_emails']['name'] );
							$description = WC_Admin_Settings::get_field_description( $setting_fields['wc_settings_anti_fraudblacklist_emails'] );
							echo wp_kses_post( $description['tooltip_html'] );
							?>
						</label>
					</th>
					<td class="forminp forminp-textarea" colspan="3">
						<textarea name="wc_settings_anti_fraudblacklist_emails" id="wc_settings_anti_fraudblacklist_emails" style="<?php echo esc_attr( $setting_fields['wc_settings_anti_fraudblacklist_emails']['css'] ); ?>" class="<?php echo esc_attr( $setting_fields['wc_settings_anti_fraudblacklist_emails']['class'] ); ?>"><?php echo esc_html( get_option( 'wc_settings_anti_fraudblacklist_emails' ) ); ?></textarea>
					</td>
				</tr>


				</tbody>
			</table>

			<?php self::render_field_section( $setting_fields['wc_af_name_bl_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
					<?php self::render_field( $setting_fields['wc_af_enable_blacklisting_first_name'] ); ?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="wc_af_blacklisted_first_names">
								<?php echo wp_kses_post( $setting_fields['wc_af_blacklisted_first_names']['name'] ); ?>
								<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_first_names']['desc_tip'] ); ?>"></span>
							</label>
						</th>
						<td class="forminp forminp-textarea" colspan="3">
							<textarea
								name="wc_af_blacklisted_first_names"
								id="wc_af_blacklisted_first_names"
								style="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_first_names']['css'] ); ?>"
								class="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_first_names']['class'] ); ?>"
							><?php echo esc_html( get_option( 'wc_af_blacklisted_first_names' ) ); ?></textarea>
						</td>
					</tr>
					<?php self::render_field( $setting_fields['wc_af_enable_blacklisting_last_name'] ); ?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="wc_af_blacklisted_last_names">
								<?php echo wp_kses_post( $setting_fields['wc_af_blacklisted_last_names']['name'] ); ?>
								<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_last_names']['desc_tip'] ); ?>"></span>
							</label>
						</th>
						<td class="forminp forminp-textarea" colspan="3">
							<textarea
								name="wc_af_blacklisted_last_names"
								id="wc_af_blacklisted_last_names"
								style="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_last_names']['css'] ); ?>"
								class="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_last_names']['class'] ); ?>"
							><?php echo esc_html( get_option( 'wc_af_blacklisted_last_names' ) ); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>

			<?php self::render_field_section( $setting_fields['wc_af_address_bl_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
					<?php self::render_field( $setting_fields['wc_af_enable_blacklisting_address'] ); ?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="wc_af_blacklisted_addresses">
								<?php echo wp_kses_post( $setting_fields['wc_af_blacklisted_addresses']['name'] ); ?>
								<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_addresses']['desc_tip'] ); ?>"></span>
							</label>
						</th>
						<td class="forminp forminp-textarea" colspan="3">
							<textarea
								name="wc_af_blacklisted_addresses"
								id="wc_af_blacklisted_addresses"
								style="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_addresses']['css'] ); ?>"
								class="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_addresses']['class'] ); ?>"
							><?php echo esc_html( get_option( 'wc_af_blacklisted_addresses' ) ); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>

			</div>

			<details class="wc-af-settings-advanced">
			<summary class="wc-af-settings-advanced__summary"><?php esc_html_e( 'Advanced block list', 'woocommerce-anti-fraud' ); ?></summary>
			<div class="wc-af-settings-advanced__inner">

			<?php self::render_field_section( $setting_fields['wc_af_sub_ip_blacklist_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
				<tr valign="top" class="">
					<th scope="row" class="titledesc">
						<label for="wc_af_ip_blacklist">
							<?php echo wp_kses_post( $setting_fields['wc_settings_anti_fraudenable_automatic_ip_blacklist']['title'] ); ?>
							<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_settings_anti_fraudenable_automatic_ip_blacklist']['desc_tip'] ); ?>"></span>
						</label>
					</th>
					<td class="forminp forminp-checkbox">
						<fieldset>
							<legend class="screen-reader-text"><span><?php echo esc_attr( $setting_fields['wc_settings_anti_fraudenable_automatic_ip_blacklist']['title'] ); ?></span></legend>
							<label for="wc_settings_anti_fraudenable_automatic_ip_blacklist" class="opmc-toggle-control">
								<input name="wc_settings_anti_fraudenable_automatic_ip_blacklist" id="wc_settings_anti_fraudenable_automatic_ip_blacklist" type="checkbox" value="1" <?php checked( get_option( 'wc_settings_anti_fraudenable_automatic_ip_blacklist' ), 'yes' ); ?> >
								<span class="opmc-control"></span>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr valign="top" class="">
					<th scope="row" class="titledesc">
						<label for="wc_af_automatic_blacklist">
							<?php echo wp_kses_post( $setting_fields['wc_settings_anti_fraudenable_automatic_ips_blacklist']['title'] ); ?>
							<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_settings_anti_fraudenable_automatic_ips_blacklist']['desc_tip'] ); ?>"></span>
						</label>
					</th>
					<td class="forminp forminp-checkbox">
						<fieldset>
							<legend class="screen-reader-text"><span><?php echo esc_attr( $setting_fields['wc_settings_anti_fraudenable_automatic_ips_blacklist']['title'] ); ?></span></legend>
							<label for="wc_settings_anti_fraudenable_automatic_ips_blacklist" class="opmc-toggle-control">
								<input name="wc_settings_anti_fraudenable_automatic_ips_blacklist" id="wc_settings_anti_fraudenable_automatic_ips_blacklist" type="checkbox" value="1" <?php checked( get_option( 'wc_settings_anti_fraudenable_automatic_ips_blacklist' ), 'yes' ); ?> >
								<span class="opmc-control"></span>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="wc_settings_anti_fraudblacklist_ipaddress">
							<?php
							echo wp_kses_post( $setting_fields['wc_settings_anti_fraudblacklist_ipaddress']['name'] );
							$description = WC_Admin_Settings::get_field_description( $setting_fields['wc_settings_anti_fraudblacklist_ipaddress'] );
							echo wp_kses_post( $description['tooltip_html'] );
							?>
						</label>
					</th>
					<td class="forminp forminp-textarea" colspan="3">
						<textarea name="wc_settings_anti_fraudblacklist_ipaddress" id="wc_settings_anti_fraud_blacklist_ipaddress" style="<?php echo esc_attr( $setting_fields['wc_settings_anti_fraudblacklist_ipaddress']['css'] ); ?>" class="<?php echo esc_attr( $setting_fields['wc_settings_anti_fraudblacklist_ipaddress']['class'] ); ?>"><?php echo esc_html( get_option( 'wc_settings_anti_fraudblacklist_ipaddress' ) ); ?></textarea>
					</td>
				</tr>
				</tbody>
			</table>

			<!-- Mobile numbers Blacklist  -->
			<?php self::render_field_section( $setting_fields['wc_af_phone_number_bl_settings'] ); ?>

			<table class="form-table opmc_wc_af_table">
				<tbody>
				<?php self::render_field( $setting_fields['wc_af_enable_blacklisting_phone_number'] ); ?>

				<?php self::render_field( $setting_fields['wc_af_blacklisted_phone_numbers'] ); ?>
				</tbody>
			</table>
			<!-- Mobile numbers Blacklist End -->

			<!--ANTIFRAUD-36 Country Blacklist -->
			<?php self::render_field_section( $setting_fields['wc_af_country_bl_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
					<?php self::render_field( $setting_fields['wc_af_enable_blacklisting_country'] ); ?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="wc_af_blacklisted_countries">
								<?php echo wp_kses_post( $setting_fields['wc_af_blacklisted_countries']['name'] ); ?>
								<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_countries']['desc_tip'] ); ?>"></span>
							</label>
						</th>
						<td class="forminp forminp-select">
							<select 
								multiple="multiple" 
								name="wc_af_blacklisted_countries[]" 
								id="wc_af_blacklisted_countries" 
								class="wc-enhanced-select" 
								style="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_countries']['css'] ); ?>"
							>
								<?php
								$saved_countries = (array) get_option( 'wc_af_blacklisted_countries', [] );
								foreach ( $setting_fields['wc_af_blacklisted_countries']['options'] as $key => $label ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $saved_countries ), true, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			<!--END ANTIFRAUD-36 Country Blacklist End -->

			<!-- City Blacklist -->
			<?php self::render_field_section( $setting_fields['wc_af_city_bl_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
					<?php self::render_field( $setting_fields['wc_af_enable_blacklisting_city'] ); ?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="wc_af_blacklisted_cities">
								<?php echo wp_kses_post( $setting_fields['wc_af_blacklisted_cities']['name'] ); ?>
								<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_cities']['desc_tip'] ); ?>"></span>
							</label>
						</th>
						<td class="forminp forminp-textarea" colspan="3">
							<textarea 
								name="wc_af_blacklisted_cities" 
								id="wc_af_blacklisted_cities" 
								style="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_cities']['css'] ); ?>" 
								class="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_cities']['class'] ); ?>"
							><?php echo esc_html( get_option( 'wc_af_blacklisted_cities' ) ); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>
			<!-- City Blacklist End -->

			<!-- State Blacklist -->
			<?php self::render_field_section( $setting_fields['wc_af_state_bl_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
					<?php self::render_field( $setting_fields['wc_af_enable_blacklisting_state'] ); ?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="wc_af_blacklisted_states">
								<?php echo wp_kses_post( $setting_fields['wc_af_blacklisted_states']['name'] ); ?>
								<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_states']['desc_tip'] ); ?>"></span>
							</label>
						</th>
						<td class="forminp forminp-textarea" colspan="3">
							<textarea 
								name="wc_af_blacklisted_states" 
								id="wc_af_blacklisted_states" 
								style="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_states']['css'] ); ?>" 
								class="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_states']['class'] ); ?>"
							><?php echo esc_html( get_option( 'wc_af_blacklisted_states' ) ); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>
			<!-- State Blacklist End -->

			<!-- Zip/Postal Code Blacklist -->
			<?php self::render_field_section( $setting_fields['wc_af_zip_bl_settings'] ); ?>
			<table class="form-table opmc_wc_af_table">
				<tbody>
					<?php self::render_field( $setting_fields['wc_af_enable_blacklisting_zipcode'] ); ?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="wc_af_blacklisted_zipcodes">
								<?php echo wp_kses_post( $setting_fields['wc_af_blacklisted_zipcodes']['name'] ); ?>
								<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_zipcodes']['desc_tip'] ); ?>"></span>
							</label>
						</th>
						<td class="forminp forminp-textarea" colspan="3">
							<textarea 
								name="wc_af_blacklisted_zipcodes" 
								id="wc_af_blacklisted_zipcodes" 
								style="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_zipcodes']['css'] ); ?>" 
								class="<?php echo esc_attr( $setting_fields['wc_af_blacklisted_zipcodes']['class'] ); ?>"
							><?php echo esc_html( get_option( 'wc_af_blacklisted_zipcodes' ) ); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>
			<!-- Zip/Postal Code Blacklist End -->

			</div>
			</details>

		</section>
		<?php
	}
}
