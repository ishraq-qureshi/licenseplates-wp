<?php
/**
 * Searchanise functions
 *
 * @package Searchanise/functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Loads localization files from:
 *    - WP_LANG_DIR/woocommerce-searchanise/woocommerce-searchanise-LOCALE.mo
 *    - WP_LANG_DIR/plugins/woocommerce-searchanise-LOCALE.mo
 */
function fn_se_load_plugin_textdomain() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	/**
	 * Returns locale
	 *
	 * @since 1.0.0
	 */
	$locale = apply_filters( 'se_locale', get_locale(), 'smart-search-for-woocommerce' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	load_textdomain( 'smart-search-for-woocommerce', WP_LANG_DIR . DIRECTORY_SEPARATOR . 'woocommerce-searchanise' . DIRECTORY_SEPARATOR . 'woocommerce-searchanise-' . $locale . '.mo' );
	load_plugin_textdomain( 'smart-search-for-woocommerce', false, plugin_basename( __DIR__ ) . DIRECTORY_SEPARATOR . 'i18n' );
}

/**
 * Output help tip.
 *
 * @param string $tip        Tip content.
 * @param bool   $allow_html Allowed tags.
 * @return void
 */
function fn_se_print_help_tip( $tip, $allow_html = false ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	echo $allow_html ? esc_html( wc_help_tip( $tip, $allow_html ) ) : esc_attr( wc_help_tip( $tip, $allow_html ) );
}

/**
 * Get timezone string wrapper.
 *
 * @return string
 */
function fn_se_get_timezone_string() {
	if ( function_exists( 'wp_timezone_string' ) ) {
		$timezone_string = wp_timezone_string();
	} else {
		$timezone_string = get_option( 'timezone_string' );
		$gmt_offset      = get_option( 'gmt_offset' );

		// If timezone string is empty, fallback to the gmt_offset.
		if ( empty( $timezone_string ) ) {
			$timezone_string = $gmt_offset;
		}
	}

	return $timezone_string;
}
