<?php
/**
 * Class Base Settings
 */

class WC_AF_Settings_Base {

	public function __construct() {

	}

	public static function get_setting_fields( $settings = array() ) {
		$setting_fields = array();

		foreach ( $settings as $value ) {
			if ( 'sectionend' != $value['type'] ) {
				$setting_fields[ $value['id'] ] = $value;
			}
		}

		return $setting_fields;
	}

	public static function render_field( $field = [] ) {

		$field_type    = isset( $field['type'] ) ? $field['type'] : 'text';
		$field_class   = isset( $field['class'] ) ? $field['class'] : '';
		$field_classes = explode( ' ', $field_class );

		// Start Antifraud-36
		if ( 'textarea' == $field_type  && in_array( 'wc_af_tags_input', $field_classes ) ) {
			// FIXED: Use proper static callable format
			call_user_func( [ __CLASS__, 'field_tags_input' ], $field );
			return;
		}

		// FIXED: Use proper static callable format
		$method_name = 'field_' . $field_type;
		if (is_callable([__CLASS__, $method_name])) {
			call_user_func( [ __CLASS__, $method_name ], $field );
		}
		// END Antifraud-36
	}

	protected static function field_tags_input( $field = [] ) {

		$field_value = str_replace( "\n", ',', get_option( $field['id'] ) );

		?>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field['id'] ); ?>">
					<?php echo wp_kses_post( $field['name'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $field['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-textarea" colspan="3">
				<textarea name="<?php echo esc_attr( $field['id'] ); ?>" id="<?php echo esc_attr( $field['id'] ); ?>" style="<?php echo esc_attr( $field['css'] ); ?>" class="<?php echo esc_attr( $field['class'] ); ?>"><?php echo esc_html( $field_value ); ?></textarea>
			</td>
		</tr>
		<?php
	}

	protected static function field_checkbox( $field = [] ) {

		?>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="wc_af_ip_blacklist">
					<?php echo wp_kses_post( $field['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $field['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo esc_attr( $field['title'] ); ?></span></legend>
					<label for="<?php echo esc_attr( $field['id'] ); ?>" class="opmc-toggle-control">
						<input name="<?php echo esc_attr( $field['id'] ); ?>" id="<?php echo esc_attr( $field['id'] ); ?>" type="checkbox" value="1" <?php checked( get_option( $field['id'] ), 'yes' ); ?> >
						<span class="opmc-control"></span>
					</label>
				</fieldset>
			</td>
		</tr>
		<?php
	}

	public static function render_field_section( $value ) {

		if ( ! isset( $value['desc'] ) ) {
			$value['desc'] = '';
		}

		if ( ! isset( $value['desc_tip'] ) ) {
			$value['desc_tip'] = '';
		}

		$description = WC_Admin_Settings::get_field_description( $value );

		?>
		</table>
		<div class="wc_af_sub-section-title">
			<h3 class="<?php echo esc_attr( $value['class'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>"><?php echo wp_kses_post( $value['name'] ); ?><?php echo wp_kses_post( $description['tooltip_html'] ); ?></h3>
			<?php if ( ! empty( $value['description'] ) ) : ?>
				<p><?php echo wp_kses_post( $value['description'] ); ?></p>
			<?php endif; ?>
		</div>
		<table class="form-table opmc_wc_af_table">
		<?php
	}
}
