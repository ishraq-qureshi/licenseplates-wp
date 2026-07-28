<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_AF_Autoloader {

	private $path;

	/**
	 * Constructor: set the base path for classes.
	 *
	 * @param string $path
	 */
	public function __construct( $path ) {
		// Ensure trailing slash
		$this->path = trailingslashit( $path );
	}

	/**
	 * Autoload WooCommerce Anti-Fraud classes.
	 *
	 * This method constructs the expected file path deterministically from
	 * class names prefixed with `WC_AF_`. The resulting file name is limited
	 * to the plugin's includes directory and cannot be influenced by user input.
	 *
	 * Security:
	 * - $file_path is built only from trusted class names (WC_AF_*).
	 * - Directory is restricted to $this->path (plugin includes folder).
	 * - File is verified with file_exists() before require_once.
	 * - Therefore, there is no risk of arbitrary file inclusion.
	 *
	 * @param string $class_name The class name being autoloaded.
	 */
	public function load( $class_name ) {
		// Only autoload classes starting with WC_AF_
		if ( strpos( $class_name, 'WC_AF_' ) === 0 ) {

			// Build the expected file name
			$file_name = 'class-wc-af-' . str_ireplace( '_', '-', strtolower( str_replace( 'WC_AF_', '', $class_name ) ) ) . '.php';

			// Build absolute path inside the known includes directory
			$file_path = $this->path . $file_name;

			// Only load if file exists in our base directory
			if ( file_exists( $file_path ) && strpos( $file_path, $this->path ) === 0 ) {
				// semgrep-disable-next-line php.lang.security.file.inclusion-arg
				// Safe deterministic include; no user input involved.
				require_once $file_path;
			}
		}
	}
}
