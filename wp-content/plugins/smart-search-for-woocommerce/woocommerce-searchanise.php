<?php
/**
 * Plugin Name: Smart Search & Product Filter for WooCommerce - Searchanise
 * Plugin URI: https://searchanise.io/
 * Description: Searchanise shows product previews, relevant categories, pages, and search suggestions as you type.
 * Version: 1.0.20
 * Author: Searchanise
 * Author URI: https://searchanise.io/
 * License: GPLv3
 * Tested up to: 7.0
 * Requires at least: 4.7
 * Requires PHP: 5.6
 * WC requires at least: 3.0.0
 * WC tested up to: 10.8
 * Requires Plugin: Woocommerce
 *
 * @package Searchanise
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( file_exists( __DIR__ . 'c3.php' ) ) {
	require_once __DIR__ . '/c3.php';
}

// Makes sure the plugin is defined before trying to use it.
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
}

// Init.
require_once __DIR__ . '/init.php';

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
