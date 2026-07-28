<?php
/**
 * Integrate the blocks modifications
 */

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

define( 'EXPRESS_BLOCK_VERSION', '1.0.0' );

class Blocks_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'captcha';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->register_block_frontend_scripts();
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'captcha-block-frontend' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		return array();
	}


	/**
	 * Register scripts for frontend block.
	 *
	 * @return void
	 */
	public function register_block_frontend_scripts() {

		$wc_af_recaptcha_type = get_option( 'wc_af_recaptcha_type', 'google_recaptcha' );
		$wc_af_recaptcha_type = empty( $wc_af_recaptcha_type ) ? 'google_recaptcha' : $wc_af_recaptcha_type;
		$script_path          = 'assets/js/block/build/captcha-block-frontend.js';
		$script_url           = plugins_url( $script_path, __DIR__ );
		// Use the filesystem path (not a URL) so file_exists() and require work correctly.
		// The webpack-generated asset manifest file is captcha-block-frontend.asset.php.
		$plugin_dir           = dirname( __DIR__ );
		$script_asset_path    = $plugin_dir . '/assets/js/block/build/captcha-block-frontend.asset.php';
		$script_asset         = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_path ),
			);

		wp_register_script(
			'captcha-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_localize_script(
			'captcha-block-frontend',
			'captcha_ajax',
			array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'turnstile_ajax_nonce' ),
				'captcha_type'   => $wc_af_recaptcha_type,
				'captcha_key'    => get_option( 'wc_af_recaptcha_site_key' ),
				'turnstile_key'  => get_option( 'wc_af_turnstile_site_key' ),
				'captcha_enable' => get_option( 'wc_af_recaptcha_enable_captcha' )
			)
		);
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 *
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}

		return EXPRESS_BLOCK_VERSION;
	}

}
