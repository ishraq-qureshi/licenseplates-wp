<?php

if ( ! function_exists( 'wpf_key_manager' ) ) {

	/**
	 * Returns the main instance of WPFKM to prevent the need to use globals.
	 *
	 * @version 1.0.4
	 * @since   1.0.0
	 *
	 * @return void|WPFKM|null
	 */
	function wpf_key_manager() {
		_deprecated_function( __FUNCTION__, '1.0.4', 'wpfactory_key_manager' );
		return wpfactory_key_manager();
	}
}

if ( ! function_exists( 'wpfactory_key_manager' ) ) {

	/**
	 * Returns the main instance of WPFKM to prevent the need to use globals.
	 *
	 * @version 1.0.4
	 * @since   1.0.4
	 *
	 * @return void|WPFKM|null
	 */
	function wpfactory_key_manager() {
		if ( function_exists( 'alg_wpcodefactory_helper' ) ) {
			return;
		}

		defined( 'ABSPATH' ) || exit;

		defined( 'WPFKM_UPDATE_SERVER' ) || define( 'WPFKM_UPDATE_SERVER', 'https://wpfactory.com' );

		defined( 'WPFKM_VERSION' ) || define( 'WPFKM_VERSION', '1.0.6' );

		defined( 'WPFKM_FILE' ) || define( 'WPFKM_FILE', __FILE__ );

		return WPFKM::instance();
	}
}