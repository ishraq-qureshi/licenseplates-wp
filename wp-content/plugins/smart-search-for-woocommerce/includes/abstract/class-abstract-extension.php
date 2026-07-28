<?php
/**
 * Searchanise abstract extension
 *
 * @package Searchanise/AbstractExtension
 */

namespace Searchanise\SmartWoocommerceSearch;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class for Searchanise extensions
 */
abstract class Abstract_Extension {

	/**
	 * Extension constructor
	 *
	 * @param bool $deferred If true, hooks install later.
	 */
	public function __construct( $deferred = false ) {
		if ( ! $deferred ) {
			$this->install_hooks();
		} else {
			add_action( 'plugins_loaded', array( $this, 'install_hooks' ) );
		}
	}

	/**
	 * Install hooks
	 *
	 * @return void
	 */
	public function install_hooks() {
		if ( ! $this->is_active() ) {
			return;
		}

		$priority = $this->get_priority();

		foreach ( $this->get_hooks() as $hook ) {
			$fn = str_replace( 'woocommerce_', '', $hook );
			$fn = str_replace( 'wp_', '', $fn );
			$fn = lcfirst( implode( '', array_map( 'ucfirst', explode( '_', $fn ) ) ) );

			if ( method_exists( $this, $fn ) ) {
				$reflect_method = new \ReflectionMethod( $this, $fn );
				add_action( $hook, array( $this, $fn ), $priority, $reflect_method->getNumberOfParameters() );
			}
		}

		foreach ( $this->get_filters() as $filter ) {
			$fn = str_replace( 'woocommerce_', '', $filter );
			$fn = str_replace( 'wp_', '', $fn );
			$fn = lcfirst( implode( '', array_map( 'ucfirst', explode( '_', $fn ) ) ) );

			if ( method_exists( $this, $fn ) ) {
				$reflect_method = new \ReflectionMethod( $this, $fn );
				if ( ! has_filter( $filter ) ) {
					add_filter( $filter, array( $this, $fn ), $priority, $reflect_method->getNumberOfParameters() );
				}
			}
		}
	}

	/**
	 * Returns hooks & filters priority
	 *
	 * @return int
	 */
	public function get_priority() {
		return 10;
	}

	/**
	 * Check if an extension is active
	 *
	 * @return boolean
	 */
	abstract public function is_active();

	/**
	 * Returns actions list
	 *
	 * @return array
	 */
	protected function get_hooks() {
		return array();
	}

	/**
	 * Returns filters list
	 *
	 * @return array
	 */
	protected function get_filters() {
		return array();
	}
}
