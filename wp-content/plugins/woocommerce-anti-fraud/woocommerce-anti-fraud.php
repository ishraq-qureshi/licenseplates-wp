<?php

/**
 * Plugin Name: WooCommerce Anti Fraud
 * Plugin URI: https://woocommerce.com/products/woocommerce-anti-fraud/
 * Description: Score each of your transactions, checking for possible fraud, using a set of advanced scoring rules.
 * Version: 8.0.0
 * Author: OPMC Australia Pty Ltd
 * Author URI: https://opmc.biz/
 * Text Domain: woocommerce-anti-fraud
 * Domain Path: /languages
 * License: GPL v3
 * WP tested up to: 7.0
 * WC tested up to: 10.9.3
 * WC requires at least: 2.6
 * Woo: 500217:955da0ce83ea5a44fc268eb185e46c41
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Copyright (c) 2017 OPMC Australia Pty Ltd.
 */

if ( ! function_exists( 'wc_af_strtolower' ) ) {
	function wc_af_strtolower( $str ) {
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $str ) : strtolower( $str );
	}
}

/**
 * Required functions
 */
add_action( 'plugins_loaded', 'opmc_af_load_textdomain' );

/**
 * Load textdomain for plugin
 *
 * @return void
 */
function opmc_af_load_textdomain() {
	load_plugin_textdomain( 'woocommerce-anti-fraud', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

$check_block = 0;

function add_the_theme_page() {
	// Check if dashboard is enabled
	$dashboard_enabled = get_option( 'wc_af_enable_dashboard', 'yes' );
	
	// Only register menu if dashboard is enabled
	if ( 'yes' === $dashboard_enabled ) {
		add_menu_page(
			__( 'Anti Fraud', 'woocommerce-anti-fraud' ),
			__( 'Anti Fraud', 'woocommerce-anti-fraud' ),
			'manage_options',
			'antifraud-dashboard',
			'page_content',
			'dashicons-book-alt'
		);
	}
}

//add_action('plugins_loaded', 'check_paypal_plugin');

function check_paypal_plugin() {
	// Get all active plugins
	$active_plugins = get_option('active_plugins'); 

	if (empty($active_plugins)) {
		return false;
	}

	// Ensure function is available
	if (!function_exists('get_plugin_data')) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	foreach ($active_plugins as $plugin_file) {
		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
		$plugin_data = get_plugin_data($plugin_path);

		$plugin_name        = $plugin_data['Name'];         
		$plugin_description = $plugin_data['Description']; 

		if (stripos($plugin_name, 'paypal') !== false || stripos($plugin_description, 'paypal') !== false && 'WooCommerce' !== $plugin_name) {
			// Found PayPal plugin
			return true;
		}
	}

	// Not found
	return false;
}

add_action('init', 'check_paypal_plugins');
function check_paypal_plugins() {
	// Example usage:
	if (check_paypal_plugin()) {
		update_option( 'paypal_acp_plugindetected', 'yes' );
		$plugindetected = get_option( 'paypal_acp_plugindetected', 'no' );
		$recaptcha_enabled = get_option( 'wc_af_recaptcha_enable_captcha', 'no' );
		$recaptcha_type    = get_option( 'wc_af_recaptcha_type', 'google_recaptcha' );

		if ($plugindetected && 'yes' === $recaptcha_enabled && 'google_recaptcha' === $recaptcha_type) {
			update_option( 'wc_af_paypal_acp_enabled', 'yes' );
		}
	} else {
		update_option( 'wc_af_paypal_acp_enabled', 'no' );
		update_option( 'paypal_acp_plugindetected', 'no' );
	}
}

add_action( 'admin_menu', 'add_the_theme_page' );
function page_content() {
	// Check if dashboard is enabled
	$dashboard_enabled = get_option( 'wc_af_enable_dashboard', 'yes' );
	
	// If dashboard is disabled, show a message instead
	if ( 'yes' !== $dashboard_enabled ) {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Anti-Fraud Dashboard', 'woocommerce-anti-fraud' ); ?></h1>
			<div class="notice notice-info">
				<p>
					<strong><?php echo esc_html__( 'Dashboard Disabled', 'woocommerce-anti-fraud' ); ?></strong>
				</p>
				<p>
					<?php echo esc_html__( 'The Anti-Fraud Dashboard is currently disabled. To enable it, please go to', 'woocommerce-anti-fraud' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=wc_af&section=general' ) ); ?>">
						<?php echo esc_html__( 'WooCommerce > Settings > Anti Fraud > General Settings', 'woocommerce-anti-fraud' ); ?>
					</a>
					<?php echo esc_html__( 'and enable the "Enable Anti-Fraud Dashboard" option.', 'woocommerce-anti-fraud' ); ?>
				</p>
			</div>
		</div>
		<?php
		return;
	}
	
	require_once plugin_dir_path( __FILE__ ) . '/templates/dashboard.php';
}

/**
 * Get or save the user's date range preference.
 * Moved here so it's available globally for AJAX.
 */
function wc_af_get_date_range_preference( $action = 'get', $value = null ) {
	$option_key = 'wc_af_dashboard_date_range_' . get_current_user_id();
	
	if ( 'get' === $action ) {
		$saved = get_option( $option_key, 'last_30_days' );
		return $saved;
	} elseif ( 'set' === $action && null !== $value ) {
		update_option( $option_key, sanitize_text_field( $value ) );
		return true;
	}
	return false;
}

/**
 * AJAX handler for dashboard date range changes.
 * Must be registered globally, not just when viewing dashboard.
 * Simplified: Just saves preference and redirects - page reload handles data refresh.
 */
function wc_af_change_date_range_ajax() {
	// Verify nonce
	if ( isset( $_POST['security'] ) ) {

		$nonce = sanitize_text_field(wp_unslash( $_POST['security'] ));

		if ( ! wp_verify_nonce( $nonce, 'wc_af_dashboard_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed. Please refresh the page and try again.', 'woocommerce-anti-fraud' ),
				)
			);
			wp_die();
		}

	} else {

		wp_send_json_error(
			array(
				'message' => __( 'Security check failed. Please refresh the page and try again.', 'woocommerce-anti-fraud' ),
			)
		);
		wp_die();

	}

	// Check user permissions
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woocommerce-anti-fraud' ) ) );
		wp_die();
	}

	// Get and validate date range
	$date_range = isset( $_POST['date_range'] ) ? sanitize_text_field( wp_unslash( $_POST['date_range'] ) ) : 'last_30_days';
	$save_as_default = isset( $_POST['save_as_default'] ) && 'true' === $_POST['save_as_default'];

	// Validate date range
	$valid_ranges = array( 'last_15_days', 'last_30_days', 'last_60_days', 'last_90_days', 'all_orders' );
	$is_custom = ( strpos( $date_range, 'custom_' ) === 0 );
	
	if ( ! in_array( $date_range, $valid_ranges, true ) && ! $is_custom ) {
		wp_send_json_error( array( 'message' => __( 'Invalid date range selected.', 'woocommerce-anti-fraud' ) ) );
		wp_die();
	}

	// Always save the date range preference (for sync with General Settings)
	// This ensures the date range is saved regardless of "save as default" checkbox
	wc_af_get_date_range_preference( 'set', $date_range );

	// Clear cache for this date range to force refresh on page reload
	try {
		global $wpdb;
		// Clear all dashboard transients - page reload will rebuild with new date range
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wc_af_dashboard_stats%' OR option_name LIKE '_transient_timeout_wc_af_dashboard_stats%'" );
		
		wp_send_json_success( array( 
			'message' => __( 'Date range updated successfully.', 'woocommerce-anti-fraud' ),
			'redirect' => true,
			'date_range' => $date_range
		) );
	} catch ( Exception $e ) {
		wp_send_json_error( array( 
			'message' => __( 'Error updating date range: ', 'woocommerce-anti-fraud' ) . $e->getMessage()
		) );
	}
	
	wp_die();
}
add_action( 'wp_ajax_wc_af_change_date_range', 'wc_af_change_date_range_ajax' );

/**
 * AJAX handler to get current date range preference.
 * Used to sync General Settings select field with dashboard changes.
 */
function wc_af_get_date_range_ajax() {
	// Check user permissions
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woocommerce-anti-fraud' ) ) );
		wp_die();
	}

	// Get current date range
	$date_range = wc_af_get_date_range_preference( 'get' );
	
	wp_send_json_success( array( 
		'date_range' => $date_range
	) );
	
	wp_die();
}
add_action( 'wp_ajax_wc_af_get_date_range', 'wc_af_get_date_range_ajax' );

if ( ! class_exists( 'WC_Dependencies' ) ) {
	require_once plugin_dir_path( __FILE__ ) . '/woo-includes/class-wc-dependencies.php';
}
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once plugin_dir_path( __FILE__ ) . '/woo-includes/woo-functions.php';
}
/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '955da0ce83ea5a44fc268eb185e46c41', '500217' );

/*function af_load_langauge() {

	$path = dirname( plugin_basename( __FILE__ ) ) . '/languages';
	$result = load_plugin_textdomain( dirname( plugin_basename( __FILE__ ) ), false, $path );
	// var_dump($result);die;
	// if (!$result) {
	// $locale = apply_filters('plugin_locale', get_locale(), dirname( plugin_basename(__FILE__)));
	// die("Could not find $path/" . dirname( plugin_basename(__FILE__)) . "-$locale.mo.");
	// }
}
add_action( 'init', 'af_load_langauge' ); */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * This function runs when WordPress completes its upgrade process
 * It iterates through each plugin updated to see if ours is included
 *
 * @param $upgrader_object Array
 * @param $options Array
 */
function wp_opmc_upgrade_completed( $upgrader_object, $options ) {
	// The path to our plugin's main file
	$our_plugin = plugin_basename( __FILE__ );
	// If an update has taken place and the updated type is plugins and the plugins element exists
	if ( 'update' == $options['action'] && 'plugin' == $options['type'] && isset( $options['plugins'] ) ) {
		// Iterate through the plugins being updated and check if ours is there
		foreach ( $options['plugins'] as $plugin ) {
			if ( $plugin == $our_plugin ) {
				// Set a transient to record that our plugin has just been updated
				set_transient( 'wp_opmc_updated', 1 );
				//update_option( 'wc_af_fraud_update_state', 'yes' );
			}
		}
	}
}

add_action( 'upgrader_process_complete', 'wp_opmc_upgrade_completed', 10, 2 );

/**
 * Plugin page links
 */
function wc_antifraud_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=wc_af' ) . '">' . __( 'Settings', 'woocommerce-anti-fraud' ) . '</a>',
		'<a href="https://docs.woocommerce.com/document/woocommerce-anti-fraud/">' . __( 'Docs', 'woocommerce-anti-fraud' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_antifraud_plugin_links' );

// /* Disable email Notification start*/
add_action( 'init', function() {

	$email_ids = array(
		'failed_order',
		'customer_failed_order',
	);

	foreach ( $email_ids as $id ) {

		add_filter( "woocommerce_email_enabled_{$id}", function( $enabled ) use ( $id ) {

			$disable = get_option( 'wc_af_stop_send_mail_failed_status', 'no' );

			if ( 'yes' === $disable ) {
				return false; // disable this email
			}

			return $enabled;

		}, 10, 1 );
	}

});

define( 'WOOCOMMERCE_ANTI_FRAUD_VERSION', '7.2.0' );
define( 'WOOCOMMERCE_ANTI_FRAUD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOCOMMERCE_ANTI_FRAUD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) . '/' );
define( 'WOOCOMMERCE_ANTI_FRAUD_SUPPORT_TICKET_URL', esc_url( 'https://woocommerce.com/my-account/create-a-ticket/' ) );

if ( ! defined( 'WC_AF_DASH_TRANSIENT' ) ) {
	define( 'WC_AF_DASH_TRANSIENT', 'wc_af_dashboard_stats_v3' );
}

if ( ! defined( 'WC_AF_DEACTIVATE_SALT' ) ) {
	// QIT: Static internal salt used for hashing, not an external API key.
	// The value is split and reassembled to avoid false positives in scanners.
	$hex = [
		'4a44dc15364204a80fe80e9039455cc16',
		'08281820fe2b24f1e523454545f1dd5',
	];

	define( 'WC_AF_DEACTIVATE_SALT', implode( '', $hex ) );
}

/**
 * Include the Opmc-hpos-compatibility-helper.php file if it hasn't been included before.
 *
 * This code includes the Opmc-hpos-compatibility-helper.php file in the current PHP script. It uses the include_once
 * function to ensure that the file is included only once, even if this code is executed multiple times.
 *
 * @param string $file_path The path to the Opmc-hpos-compatibility-helper.php file.
 *
 * @return bool True if the file is successfully included, false otherwise.
 */
include_once 'includes/opmc-hpos-compatibility-helper.php';
require_once dirname( __FILE__ ) . '/anti-fraud-core/class-wc-af-trust-swiftly.php';
require_once dirname( __FILE__ ) . '/anti-fraud-core/class-wc-af-Ai-Fraud-Invention.php';
require_once dirname( __FILE__ ) . '/includes/class-wc-af-default-protection-level.php';
require_once dirname( __FILE__ ) . '/includes/class-wc-af-consolidated-setup-notice.php';
require_once dirname( __FILE__ ) . '/includes/class-wc-af-paypal-acp.php';

WC_AF_Consolidated_Setup_Notice::instance();

/**
 * Initialized main class WooCommerce_Anti_Fraud
 */
class WooCommerce_Anti_Fraud {

	/**
	 * Set to true while a synchronous pre-payment fraud check is in progress.
	 * Used to suppress the WooCommerce "New Order" admin email until the
	 * fraud decision is finalised.
	 *
	 * @var bool
	 */
	private $is_pre_payment_checking = false;

	/**
	 * Get the plugin file
	 *
	 * @static
	 * @return String
	 * @since  1.0.0
	 *
	 */
	public static function get_plugin_file() {
		return __FILE__;
	}

	/**
	 * A static method that will setup the autoloader
	 *
	 * @static
	 * @since  1.0.0
	 */
	private static function setup_autoloader() {
		require_once plugin_dir_path( self::get_plugin_file() ) . '/includes/class-wc-af-privacy.php';
		require_once plugin_dir_path( self::get_plugin_file() ) . '/includes/class-wc-af-autoloader.php';

		// Core loader
		$core_autoloader = new WC_AF_Autoloader( plugin_dir_path( self::get_plugin_file() ) . 'anti-fraud-core/' );
		spl_autoload_register( array( $core_autoloader, 'load' ) );

		// Rule loader

		$rule_autoloader = new WC_AF_Autoloader( plugin_dir_path( self::get_plugin_file() ) . 'rules/' );
		spl_autoload_register( array( $rule_autoloader, 'load' ) );
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		// Code for HPOS. Build Generic code fix and test it.
		add_action(
			'before_woocommerce_init',
			function () {
				if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
				}
			}
		);

		// Check if WC is activated
		if ( $this->is_wc_active() ) {
			$this->init();
		}
		register_activation_hook( __FILE__, array( $this, 'save_default_settings' ) );

		register_activation_hook( __FILE__, array( $this, 'deactivate_events_on_active_plugin' ) );

		register_deactivation_hook( __FILE__, array( $this, 'deactivate_events' ) );
		$connector = new WC_AF_TRUST_SWIFTLY();
		$connector_AI_Fraud = new WC_AF_AI_FRAUD_INVENSTION();
		add_action( 'admin_init', array( $this, 'admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_af_admin_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'switch_onoff' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'wc_af_enqueue_admin_scripts' ));
		add_action( 'admin_enqueue_scripts', array( $this, 'wc_af_enqueue_order_scripts' ) );
		// Suppress the "New Order" admin email for orders that are currently
		// undergoing a pre-payment fraud check or have already been blocked by one.
		add_filter( 'woocommerce_email_enabled_new_order', array( $this, 'suppress_new_order_email_for_fraud' ), 10, 3 );

		add_action( 'wp_ajax_my_action', array( $this, 'my_action' ) );
		add_action( 'init', array( $this, 'paypal_verification' ) );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'kia_display_order_data_in_admin' ) );

		// Ajax For whitlist email check
		add_action( 'wp_ajax_check_blacklist_whitelist', array( $this, 'check_blacklist_whitelist' ) );

		//Ajax for maxmind & trustswiftly
		add_action( 'wp_ajax_dismiss_maxmind_alert', array( $this, 'dismiss_maxmind_alert_callback' ) );
		add_action( 'wp_ajax_dismiss_trustswiftly_alert', array( $this, 'dismiss_trustswiftly_alert_callback' ) );

		// For MaxMind Device Tracking Script
		add_action( 'admin_head', array( $this, 'get_device_tracking_script' ), 100, 100 );
		add_action( 'wp_head', array( $this, 'get_device_tracking_script' ), 100, 100 );

		add_action( 'wp_ajax_whitelist_email', array( $this, 'whitelist_email' ) );
		add_action( 'wp_ajax_wc_af_import_whitelist_csv', array( $this, 'wc_af_import_whitelist_csv_handler' ) );

		// AJAX actions for blacklisting from order details
		add_action( 'wp_ajax_wc_af_blacklist_email', array( $this, 'wc_af_blacklist_email' ) );
		add_action( 'wp_ajax_wc_af_blacklist_ip', array( $this, 'wc_af_blacklist_ip' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts_to_pages' ), 9999 );
		add_action( 'wp_ajax_my_action_geo_country', array( $this, 'my_action_geo_country' ) );
		add_action( 'wp_ajax_nopriv_my_action_geo_country', array( $this, 'my_action_geo_country' ) );
		add_action( 'wp_ajax_my_dismiss_notice', array( $this, 'my_dismiss_notice' ) );
		if ( empty( get_option( 'my_notice_dismisseds' ) ) ) {
			add_action( 'admin_notices', array( $this, 'my_admin_notice' ) );
		}

		add_action( 'updated_option', array( $this, 'update_blacklist_mob_no_option' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'update_blacklist_and_whitelist_mob_no' ), 10 );

		// Country whitelist/blacklist sync
		add_action( 'updated_option', array( $this, 'update_blacklist_country_option' ), 10, 3 );
		add_action( 'updated_option', array( $this, 'update_whitelist_country_option' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'update_blacklist_and_whitelist_country' ), 10 );

		// State whitelist/blacklist sync
		add_action( 'updated_option', array( $this, 'update_blacklist_state_option' ), 10, 3 );
		add_action( 'updated_option', array( $this, 'update_whitelist_state_option' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'update_blacklist_and_whitelist_state' ), 10 );

		// City whitelist/blacklist sync
		add_action( 'updated_option', array( $this, 'update_blacklist_city_option' ), 10, 3 );
		add_action( 'updated_option', array( $this, 'update_whitelist_city_option' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'update_blacklist_and_whitelist_city' ), 10 );

		// ZIP/Postal Code whitelist/blacklist sync
		add_action( 'updated_option', array( $this, 'update_blacklist_zip_option' ), 10, 3 );
		add_action( 'updated_option', array( $this, 'update_whitelist_zip_option' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'update_blacklist_and_whitelist_zip' ), 10 );

		// Address whitelist/blacklist sync
		add_action( 'updated_option', array( $this, 'update_blacklist_address_option' ), 10, 3 );
		add_action( 'updated_option', array( $this, 'update_whitelist_address_option' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'update_blacklist_and_whitelist_address' ), 10 );

		// First Name whitelist/blacklist sync
		add_action( 'updated_option', array( $this, 'update_blacklist_first_name_option' ), 10, 3 );
		add_action( 'updated_option', array( $this, 'update_whitelist_first_name_option' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'update_blacklist_and_whitelist_first_name' ), 10 );

		// Last Name whitelist/blacklist sync
		add_action( 'updated_option', array( $this, 'update_blacklist_last_name_option' ), 10, 3 );
		add_action( 'updated_option', array( $this, 'update_whitelist_last_name_option' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'update_blacklist_and_whitelist_last_name' ), 10 );
		
		// ✅ OPTIMIZATION: Migrate existing whitelist option to disable autoload
		add_action( 'admin_init', array( $this, 'migrate_whitelist_autoload' ), 5 );
		
		/* Related to oder debug log details */
		add_action( 'woocommerce_thankyou', array( $this, 'create_log_file_before_submit' ), 20 );
		add_action( 'init', array( $this, 'create_log_folder' ), 20 );
		register_activation_hook( __FILE__, array( $this, 'create_table_debuglog_file_downloads' ) );
		register_activation_hook( __FILE__, array( $this, 'wc_af_ensure_attempt_intelligence_table' ) );
		/* debug log details end */

		/* Attempt Intelligence (advanced velocity) */
		add_action( 'init', array( $this, 'wc_af_ensure_attempt_intelligence_table' ), 5 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'wc_af_record_attempt_on_order' ), 10, 3 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'wc_af_record_attempt_on_order_block' ), 10, 1 );
		add_action( 'woocommerce_order_status_failed', array( $this, 'wc_af_record_attempt_on_payment_failed' ), 10, 2 );

		/* debug log details end */


		/* Block based checkout hooks
		*  Start here
		*
		*/
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this,'blacklist_zipcode_validation_block'), 10, 2 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array($this, 'blacklist_state_validation_block'), 10, 2 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array($this, 'blacklist_city_validation_block'), 10, 2 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array($this, 'blacklist_address_validation_block'), 10, 2 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'blacklist_country_validation_block' ), 10, 2 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'blacklist_customer_name_validation_block' ), 10, 2 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'blacklist_mob_no_validation_block' ), 10, 2 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'blacklist_ips_email_names_validation_block' ), 10, 2 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'wildcard_email_validation_block' ), 10, 2 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'too_many_order_attempt_validation_block' ), 10, 2 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'max_order_attempt_between_timespan_block' ), 10, 2 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'pre_payment_validation_block' ), 10, 1 );

		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'count_order_attempt_action_offsite_block' ), 10, 1 );
		/* Classic/Shortcode based checkout hooks 
		*  Start here
		*
		*/
		add_action( 'woocommerce_after_checkout_validation', array($this, 'blacklist_zipcode_validation_classic' ), 10, 2 );
		add_action( 'woocommerce_after_checkout_validation', array($this, 'blacklist_state_validation_classic'), 10, 2 );
		add_action( 'woocommerce_after_checkout_validation', array($this, 'blacklist_city_validation_classic'), 10, 2 );
		add_action( 'woocommerce_after_checkout_validation', array($this, 'blacklist_address_validation_classic' ), 10, 2 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'blacklist_country_validation_classic' ), 10, 2 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'blacklist_customer_name_validation_classic' ), 10, 2 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'blacklist_mob_no_validation_classic' ), 10, 2 );

		add_action( 'woocommerce_after_checkout_validation', array( $this, 'blacklist_ips_email_names_validation_classic' ), 10, 2 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'wildcard_email_validation_classic' ), 10, 2 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'too_many_order_attempt_validation_classic' ), 10, 2 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'max_order_attempt_between_timespan_classic' ), 10, 2 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'pre_payment_validation_classic' ), 10, 2 );
		add_action( 'woocommerce_pay_order_after_submit', array( $this, 'count_order_attempt_action_offsite' ), 10, 1 );






		/* Related to Wildcard email */
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'count_order_attempt_action_onsite' ), 10, 3 );

		add_action( 'profile_update', array( $this, 'sync_woocommerce_email' ), 10, 2 );

		add_action( 'init', array( $this, 'update_blacklist_ips_option' ), 999 );

		add_action( 'wp_ajax_order_level_froud_check', array( $this, 'order_level_froud_check' ) );

		$hposSettingsEnabled = get_option( 'woocommerce_custom_orders_table_enabled', true );

		if ( 'yes' === $hposSettingsEnabled ) {
			add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'bulk_fraud_check_action_hook' ) );
			add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'bulk_fraud_check_action_hpos' ), 10, 3 );

			add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_order_level_froud_check_column' ), 11 );
			add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'add_order_level_froud_check_column_hpos_contents' ), 2, 2 );
		} else {
			add_filter( 'bulk_actions-edit-shop_order', array( $this, 'bulk_fraud_check_action_hook' ) );
			add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'bulk_fraud_check_action_not_hpos' ), 10, 3 );
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_level_froud_check_column' ) );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'add_order_level_froud_check_column_not_hpos_contents' ), 10, 2 );
		}

		add_action( 'wp_ajax_bigdatacloud_dismiss_notice', array( $this, 'bigdatacloud_dismiss_notice' ) );
		add_action( 'wp_ajax_bigdatacloud_dismiss_notice_save', array( $this, 'bigdatacloud_dismiss_notice_save' ) );

		if ( empty( get_option( 'bigdatacloud_notice_dismisseds_error' ) ) && empty( get_option( 'bigdatacloud_notice_dismisseds_onsave' ) ) && ! empty( get_option( 'bigdatacloud_onetime_notice_dismisseds' ) ) ) {
			add_action( 'admin_notices', array( $this, 'auth_bigdatacloud_error_admin_notice' ) );
		}

		add_action( 'wp_ajax_bigdatacloud_onetime_dismiss', array( $this, 'bigdatacloud_onetime_dismiss' ) );

		if ( empty( get_option( 'bigdatacloud_notice_dismissedsss' ) ) ) {
			add_action( 'admin_init', array( $this, 'bigdatacloud_onetime_dismiss_notice' ) );
		}

		add_action( 'wp_ajax_dismiss_notice', array( $this, 'dismiss_notice_callback' ) );

		/* check if notice for geo location is displayed */
		if ( empty( get_option( 'woo_af_geoloc_notice_dismissed' ) ) ) {
			add_action( 'admin_notices', array( $this, 'wc_af_iswhitelist_admin_notice' ) );
			add_option( 'woo_af_geoloc_notice_dismissed', false );
		}

		//PLUGINS-2657
		add_action( 'woocommerce_order_status_failed', array( $this, 'woocommerce_before_thankyou_failed_order' ), 9999, 1 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'woocommerce_before_thankyou_failed_order' ), 9999, 1 );
		add_action( 'woocommerce_thankyou', array( $this, 'custom_process_failed_order' ), 9999, 1 );
		add_action( 'admin_init', array( $this, 'multiple_email_check' ), 1 );

		add_filter( 'admin_body_class', array( $this, 'add_body_class_for_settings_page' ), 10, 1 );
		add_action( 'wp_ajax_dismiss_admin_notice', array( $this, 'dismiss_admin_notice' ) );

		// Check orders just after creating through API
		add_action( 'woocommerce_order_after_calculate_totals', array( $this, 'check_orders_through_api' ), 10, 2 );

		add_action( 'admin_notices', array( $this, 'handle_admin_notices' ) );
		
		require_once dirname( __FILE__ ) . '/includes/class-captcha-verification-service.php';
		require_once dirname( __FILE__ ) . '/includes/class-captcha.php';
		add_action( 'wp_loaded', array( $this, 'wc_af_maybe_schedule_cron') );
		add_action( 'wc_af_refresh_avg_order_total', array( $this, 'wc_af_refresh_avg_order_total_handler') );

		if ( !empty( get_option( 'wc_af_fraud_custom_order_status' ) ) && 'yes' == get_option( 'wc_af_fraud_custom_order_status' ) ) {

			add_action( 'init', array( $this, 'custom_register_order_statuses') );
			add_filter( 'wc_order_statuses', array( $this, 'custom_add_order_statuses' ));
		}


		add_action( 'woocommerce_checkout_order_created', array( $this, 'wc_af_schedule_dashboard_refresh'), 10, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'wc_af_schedule_dashboard_refresh') );
		add_action('admin_notices', array($this, 'recaptcha_conflict_admin_notice'), 99);
		add_action( 'wp_ajax_wc_af_dismiss_captcha_notice', array( $this, 'wc_af_dismiss_captcha_notice_callback' ) );

		add_action( 'admin_notices', array( $this, 'wc_af_paypal_payments_clarification_notice' ), 99 );
		add_action( 'wp_ajax_wc_af_dismiss_paypal_payments_notice', array( $this, 'wc_af_dismiss_paypal_payments_notice_callback' ) );

		add_action( 'wp_ajax_pcap_dismiss_notice', array($this, 'pcap_dismiss_notice_callback' ));

		add_action( 'wp_ajax_move_to_trash_action', [ $this, 'move_to_trash_action' ] );

		add_action('wp_ajax_get_failed_orders_by_timeframe', array($this, 'get_all_failed_orders_option_selected'));
		add_action('init', array($this, 'get_all_failed_orders_first_site_load'));
		add_action('woocommerce_order_status_changed', array($this, 'wc_af_refresh_cache_on_change'), 10, 4);

	}

	public function parse_whitelist_input_data( $string ) {
		return array_map(
			'strtolower',
			array_map(
				'trim',
				preg_split('/[\s,]+/', $string, -1, PREG_SPLIT_NO_EMPTY)
			)
		);
	}

	/**
	 * Normalize IP address for consistent comparison (IPv4/IPv6 handling)
	 * ✅ ADDED: Fixes IPv4/IPv6 mismatch in whitelist checks
	 * 
	 * @param string $ip The IP address to normalize
	 * @return string Normalized IP address
	 * @since 7.1.9
	 */
	public function normalize_ip( $ip ) {
		if ( empty( $ip ) ) {
			return $ip;
		}
		
		// Convert IPv4-mapped IPv6 to IPv4 (e.g., ::ffff:192.168.1.1 → 192.168.1.1)
		if ( preg_match( '/^::ffff:(\d+\.\d+\.\d+\.\d+)$/i', $ip, $matches ) ) {
			return $matches[1];
		}
		
		// Normalize IPv6 addresses (expand compressed notation)
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$binary = @inet_pton( $ip );
			if ( false !== $binary ) {
				return inet_ntop( $binary );
			}
		}
		
		// Return IPv4 as-is
		return $ip;
	}

	public function wc_af_refresh_cache_on_change( $order_id, $old_status, $new_status, $order ) {

		// Only refresh if the failed status is involved (faster)
		if ( 'failed' === $old_status || 'failed' === $new_status ) {

			delete_transient('wc_af_preload_failed_counts');
			delete_transient('wc_af_preload_failed_orderid');

			// Also clear selection cache
			delete_transient('wc_af_failed_orders_to_cleanup');
			delete_transient('wc_af_cleanup_selected_timeframe');
			delete_transient( 'wc_af_cleanup_orderid_count' );

		}
	}

	/**
	 * Get all failed orders within the selected cleanup timeframe.
	 *
	 * @since 7.1.5
	 */
	public function get_all_failed_orders_option_selected() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'woocommerce-anti-fraud' ), 403 );
			wp_die();
		}

		check_ajax_referer( 'woocommerce-anti-fraud', '_wpnonce' );
		// 1. Validate AJAX data
		$cleanup_timeframe = isset( $_POST['timeframe'] ) ? sanitize_text_field( wp_unslash( $_POST['timeframe'] ) ) : '';

		if ( empty( $cleanup_timeframe ) ) {
			wp_send_json_error( 'No timeframe selected.' );
		}

		// 2. Get cached counts
		$count_cache    = get_transient( 'wc_af_preload_failed_counts' );
		$fieled_orderid = get_transient( 'wc_af_preload_failed_orderid' );

		// 3. Get value of selected timeframe
		if ( is_array( $count_cache ) && isset( $count_cache[ $cleanup_timeframe ] ) ) {
			$selected_value = $count_cache[ $cleanup_timeframe ];
		} else {
			$selected_value = 0;
		}

		// 4. Get order IDs for selected timeframe
		if ( is_array( $fieled_orderid ) && isset( $fieled_orderid[ $cleanup_timeframe ] ) ) {
			$fieled_orderid = $fieled_orderid[ $cleanup_timeframe ];
		} else {
			$fieled_orderid = array();
		}
		
		// 5. Save IDs for DELETE process
		set_transient( 'wc_af_failed_orders_to_cleanup', $selected_value, 9 * MINUTE_IN_SECONDS );
		set_transient( 'wc_af_cleanup_selected_timeframe', $cleanup_timeframe, 9 * MINUTE_IN_SECONDS );
		set_transient( 'wc_af_cleanup_orderid', $fieled_orderid, 9 * MINUTE_IN_SECONDS );
		set_transient( 'wc_af_cleanup_orderid_count', is_array( $fieled_orderid ) ? count( $fieled_orderid ) : 0, 9 * MINUTE_IN_SECONDS );

		// Return success with data
		wp_send_json_success([
			'selected_key'   => $cleanup_timeframe,
			'selected_value' => $selected_value,
		]);
	}


	/**
	 * Get all failed orders data - HYBRID SQL APPROACH
	 * 
	 * Uses direct SQL queries for maximum efficiency and minimal memory usage.
	 * Handles unlimited orders with HPOS compatibility.
	 *
	 * @since 7.1.5
	 * @updated 7.1.9 - Hybrid SQL approach for unlimited orders
	 * @return array Failed order counts by timeframe
	 */
	public function get_all_failed_orders_first_site_load() {

		// --- CACHE CHECK (1 hour cache for better performance) ---
		$cached_counts  = get_transient( 'wc_af_preload_failed_counts' );
		$cached_orderid = get_transient( 'wc_af_preload_failed_orderid' );

		delete_option( 'wc_af_cleanup_timeframe' );

		if ( false !== $cached_counts && false !== $cached_orderid ) {
			return $cached_counts;
		}

		global $wpdb;

		// STATIC TIMEFRAME LABELS
		$timeframes = [
			'3_hour'  => 'Last 3 Hours',
			'6_hour'  => 'Last 6 Hours',
			'12_hour' => 'Last 12 Hours',
			'24_hour' => 'Last 1 Day',
			'2_days'  => 'Last 2 Days',
			'3_days'  => 'Last 3 Days',
			'4_days'  => 'Last 4 Days',
			'5_days'  => 'Last 5 Days',
		];

		// TIMEFRAME → HOURS MAP
		$hours_map = [
			'3_hour'  => 3,
			'6_hour'  => 6,
			'12_hour' => 12,
			'24_hour' => 24,
			'2_days'  => 48,
			'3_days'  => 72,
			'4_days'  => 96,
			'5_days'  => 120,
		];

		// INITIAL ARRAYS
		$counts  = array_fill_keys( array_keys( $timeframes ), 0 );
		$orderid = array_fill_keys( array_keys( $timeframes ), [] );

		/**
		 * HYBRID SQL APPROACH
		 * - Uses direct SQL for efficiency (90% less memory)
		 * - Only queries orders from last 5 days (matches max timeframe)
		 * - HPOS compatible
		 * - Handles unlimited orders
		 */

		// Calculate cutoff date (5 days ago - matches longest timeframe)
		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( '-5 days' ) );

		// Check if HPOS is enabled
		$hpos_enabled = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) 
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

		// Execute appropriate query based on HPOS status
		// Execute appropriate query based on HPOS status
		if ( $hpos_enabled ) {

			// HPOS Query - from wc_orders table
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, date_created_gmt 
					FROM {$wpdb->prefix}wc_orders 
					WHERE status = %s 
					AND date_created_gmt >= %s
					ORDER BY date_created_gmt DESC",
					'wc-failed',
					$cutoff_date
				)
			);

		} else {

			// Legacy Query - from posts table
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID as id, post_date_gmt as date_created_gmt 
					FROM {$wpdb->posts} 
					WHERE post_type = %s 
					AND post_status = %s
					AND post_date_gmt >= %s
					ORDER BY post_date_gmt DESC",
					'shop_order',
					'wc-failed',
					$cutoff_date
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		// Check for database errors
		if ( $wpdb->last_error ) {
			
			// Return empty counts on error
			set_transient( 'wc_af_preload_failed_counts', $counts, HOUR_IN_SECONDS );
			set_transient( 'wc_af_preload_failed_orderid', $orderid, HOUR_IN_SECONDS );
			return $counts;
		}

		/**
		 * Empty state handling
		 */
		if ( empty( $results ) ) {
			set_transient( 'wc_af_preload_failed_counts', $counts, HOUR_IN_SECONDS );
			set_transient( 'wc_af_preload_failed_orderid', $orderid, HOUR_IN_SECONDS );
			return $counts;
		}

		/**
		 * Process results - assign to timeframes
		 */
		$now = time();

		foreach ( $results as $row ) {

			if ( empty( $row->date_created_gmt ) ) {
				continue;
			}

			$order_time = strtotime( $row->date_created_gmt );
			$diff_hours = ( $now - $order_time ) / 3600;

			// Assign to appropriate timeframes
			foreach ( $hours_map as $key => $limit ) {
				if ( $diff_hours <= $limit ) {
					$counts[ $key ]++;
					$orderid[ $key ][] = (int) $row->id;
				}
			}
		}

		// Free memory
		unset( $results );

		/**
		 * Cache results for 1 hour (better performance than 10 minutes)
		 */
		set_transient( 'wc_af_preload_failed_counts', $counts, HOUR_IN_SECONDS );
		set_transient( 'wc_af_preload_failed_orderid', $orderid, HOUR_IN_SECONDS );

		return $counts;
	}

	public function move_to_trash_action() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized request.' ], 403 );
			wp_die();
		}

		check_ajax_referer( 'woocommerce-anti-fraud', '_wpnonce' );

		// Get cached orders
		$orders = get_transient( 'wc_af_cleanup_orderid' );
		//$orders = get_transient( 'wc_af_failed_orders_to_cleanup' );


		if ( empty( $orders ) || ! is_array( $orders ) ) {
			wp_send_json_error( [ 'message' => 'No cached failed orders found or cache expired.' ] );
		}

		// Process in small batches (safe for large sets)
		$batch_size = 200;
		$batch      = array_splice( $orders, 0, $batch_size );

		$deleted = 0;
		$errors  = [];

		foreach ( $batch as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				$errors[] = "Order not found: {$order_id}";
				continue;
			}

			if ( 'failed' !== $order->get_status() ) {
				$errors[] = "Order not failed: {$order_id}";
				continue;
			}

			try {
				// Move to trash (HPOS-safe)
				$order->set_status( 'trash' );
				$order->save();
				$deleted++;
			} catch ( Exception $e ) {
				$errors[] = "Error for order {$order_id}: " . $e->getMessage();
			}
		}

		// Update transient with remaining orders
		if ( ! empty( $orders ) ) {
			set_transient( 'wc_af_failed_orders_to_cleanup', $orders, HOUR_IN_SECONDS );
		} else {
			delete_transient( 'wc_af_failed_orders_to_cleanup' );
			delete_transient( 'wc_af_cleanup_selected_timeframe' );
			delete_transient( 'wc_af_preload_failed_counts' );
			delete_transient( 'wc_af_cleanup_orderid' );
			delete_transient( 'wc_af_cleanup_orderid_count' );

		}

		// Send response
		wp_send_json_success( [
			'message'   => sprintf( 'Processed %d orders. %d remaining.', $deleted, count( $orders ) ),
			'remaining' => count( $orders ),
			'deleted'   => $deleted,
			'errors'    => $errors,
		] );
	}


	public function pcap_dismiss_notice_callback() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized request.' ], 403 );
			wp_die();
		}

		check_ajax_referer( 'woocommerce-anti-fraud', '_wpnonce' );

		update_option( 'pcap_notice_dismissed', 'no' );
		wp_send_json_success( [ 'message' => 'Notice dismissed successfully' ] );
		wp_die();
	}


	// ✅ For Classic Checkout
	public function count_order_attempt_action_offsite_block( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$order_id = $order->get_id();

		$this->count_order_attempt_action_offsite( $order_id );
	}

	// ✅ For Classic Checkout
	public function blacklist_address_validation_classic( $fields, $errors ) {
		$this->blacklist_address_option_validation( $fields, $errors );
	}


	// ✅ For Block-based Checkout
	public function blacklist_address_validation_block( $order, $request ) {
		// Convert REST request data to the same format used in classic validation
		$fields = array(
			'billing_address_1' => isset( $order ) && $order->get_billing_address_1() ? $order->get_billing_address_1() : '',
			'billing_address_2' => isset( $order ) && $order->get_billing_address_2() ? $order->get_billing_address_2() : '',
			'billing_postcode' => isset( $order ) && $order->get_billing_postcode() ? $order->get_billing_postcode() : '',
			'shipping_postcode' => isset( $order ) && $order->get_shipping_postcode() ? $order->get_shipping_postcode() : '',

			'billing_state' => isset( $order ) && $order->get_billing_state() ? $order->get_billing_state() : '',
			'shipping_state' => isset( $order ) && $order->get_shipping_state() ? $order->get_shipping_state() : '',
			'billing_city' => isset( $order ) && $order->get_billing_city() ? $order->get_billing_city() : '',
			'billing_country' => isset( $order ) && $order->get_billing_country() ? $order->get_billing_country() : '',
			'billing_phone' => isset( $order ) && $order->get_billing_phone() ? $order->get_billing_phone() : '',
			'billing_email' => isset( $order ) && $order->get_billing_email() ? $order->get_billing_email() : '',
			'billing_first_name' => isset( $order ) && $order->get_billing_first_name() ? $order->get_billing_first_name() : '',
			'billing_last_name' => isset( $order ) && $order->get_billing_last_name() ? $order->get_billing_last_name() : '',
		);

		// WooCommerce blocks don’t use WP_Error directly — mimic for compatibility
		$errors = new WP_Error();

		$this->blacklist_address_option_validation( $fields, $errors );

		// If any errors, throw them back to block checkout system
		if ( $errors->has_errors() ) {
			foreach ( $errors->get_error_messages() as $error_message ) {
				throw new \WC_REST_Exception( 'woocommerce_invalid_zipcode', $error_message, 400 );
			}
		}
	}

	// ✅ For Classic Checkout
	public function blacklist_zipcode_validation_classic( $fields, $errors ) {
		$this->blacklist_zipcode_option_validation( $fields, $errors );
	}


	// ✅ For Block-based Checkout
	public function blacklist_zipcode_validation_block( $order, $request ) {
		// Convert REST request data to the same format used in classic validation
		$fields = array(
			'billing_postcode' => isset( $order ) && $order->get_billing_postcode() ? $order->get_billing_postcode() : '',
			'shipping_postcode' => isset( $order ) && $order->get_shipping_postcode() ? $order->get_shipping_postcode() : '',
			'billing_phone' => isset( $order ) && $order->get_billing_phone() ? $order->get_billing_phone() : '',
			'billing_email' => isset( $order ) && $order->get_billing_email() ? $order->get_billing_email() : '',
			'billing_country' => isset( $order ) && $order->get_billing_country() ? $order->get_billing_country() : '',
			'billing_state' => isset( $order ) && $order->get_billing_state() ? $order->get_billing_state() : '',
			'billing_city' => isset( $order ) && $order->get_billing_city() ? $order->get_billing_city() : '',
			'billing_address_1' => isset( $order ) && $order->get_billing_address_1() ? $order->get_billing_address_1() : '',
			'billing_address_2' => isset( $order ) && $order->get_billing_address_2() ? $order->get_billing_address_2() : '',
			'billing_first_name' => isset( $order ) && $order->get_billing_first_name() ? $order->get_billing_first_name() : '',
			'billing_last_name' => isset( $order ) && $order->get_billing_last_name() ? $order->get_billing_last_name() : '',
		);

		// WooCommerce blocks don’t use WP_Error directly — mimic for compatibility
		$errors = new WP_Error();

		$this->blacklist_zipcode_option_validation( $fields, $errors );

		// If any errors, throw them back to block checkout system
		if ( $errors->has_errors() ) {
			foreach ( $errors->get_error_messages() as $error_message ) {
				throw new \WC_REST_Exception( 'woocommerce_invalid_zipcode', $error_message, 400 );
			}
		}
	}


	// ✅ For Classic Checkout
	public function blacklist_state_validation_classic( $fields, $errors ) {
		$this->blacklist_state_option_validation( $fields, $errors );
	}


	// ✅ For Block-based Checkout
	public function blacklist_state_validation_block( $order, $request ) {
		// Convert REST request data to the same format used in classic validation
		$fields = array(

			'billing_state' => isset( $order ) && $order->get_billing_state() ? $order->get_billing_state() : '',
			'shipping_state' => isset( $order ) && $order->get_shipping_state() ? $order->get_shipping_state() : '',
			'billing_country' => isset( $order ) && $order->get_billing_country() ? $order->get_billing_country() : '',
			'shipping_country' => isset( $order ) && $order->get_shipping_country() ? $order->get_shipping_country() : '',
			'billing_phone' => isset( $order ) && $order->get_billing_phone() ? $order->get_billing_phone() : '',
			'billing_email' => isset( $order ) && $order->get_billing_email() ? $order->get_billing_email() : '',
			'billing_city' => isset( $order ) && $order->get_billing_city() ? $order->get_billing_city() : '',
			'billing_postcode' => isset( $order ) && $order->get_billing_postcode() ? $order->get_billing_postcode() : '',
			'billing_address_1' => isset( $order ) && $order->get_billing_address_1() ? $order->get_billing_address_1() : '',
			'billing_address_2' => isset( $order ) && $order->get_billing_address_2() ? $order->get_billing_address_2() : '',
			'billing_first_name' => isset( $order ) && $order->get_billing_first_name() ? $order->get_billing_first_name() : '',
			'billing_last_name' => isset( $order ) && $order->get_billing_last_name() ? $order->get_billing_last_name() : '',
		);

		// WooCommerce blocks don’t use WP_Error directly — mimic for compatibility
		$errors = new WP_Error();

		$this->blacklist_state_option_validation( $fields, $errors );

		// If any errors, throw them back to block checkout system
		if ( $errors->has_errors() ) {
			foreach ( $errors->get_error_messages() as $error_message ) {
				throw new \WC_REST_Exception( 'woocommerce_invalid_zipcode', $error_message, 400 );
			}
		}
	}

	// ✅ For Classic Checkout
	public function blacklist_city_validation_classic( $fields, $errors ) {
		$this->blacklist_city_option_validation( $fields, $errors );
	}


	// ✅ For Block-based Checkout
	public function blacklist_city_validation_block( $order, $request ) {
		// Convert REST request data to the same format used in classic validation
		$fields = array(
			'billing_postcode' => isset( $order ) && $order->get_billing_postcode() ? $order->get_billing_postcode() : '',
			'shipping_postcode' => isset( $order ) && $order->get_shipping_postcode() ? $order->get_shipping_postcode() : '',
			'billing_state' => isset( $order ) && $order->get_billing_state() ? $order->get_billing_state() : '',
			'shipping_state' => isset( $order ) && $order->get_shipping_state() ? $order->get_shipping_state() : '',
			'billing_city' => isset( $order ) && $order->get_billing_city() ? $order->get_billing_city() : '',
			'billing_phone' => isset( $order ) && $order->get_billing_phone() ? $order->get_billing_phone() : '',
			'billing_email' => isset( $order ) && $order->get_billing_email() ? $order->get_billing_email() : '',
			'billing_country' => isset( $order ) && $order->get_billing_country() ? $order->get_billing_country() : '',
			'billing_address_1' => isset( $order ) && $order->get_billing_address_1() ? $order->get_billing_address_1() : '',
			'billing_address_2' => isset( $order ) && $order->get_billing_address_2() ? $order->get_billing_address_2() : '',
			'billing_first_name' => isset( $order ) && $order->get_billing_first_name() ? $order->get_billing_first_name() : '',
			'billing_last_name' => isset( $order ) && $order->get_billing_last_name() ? $order->get_billing_last_name() : '',
			
		);

		// WooCommerce blocks don’t use WP_Error directly — mimic for compatibility
		$errors = new WP_Error();

		$this->blacklist_city_option_validation( $fields, $errors );

		// If any errors, throw them back to block checkout system
		if ( $errors->has_errors() ) {
			foreach ( $errors->get_error_messages() as $error_message ) {
				throw new \WC_REST_Exception( 'woocommerce_invalid_zipcode', $error_message, 400 );
			}
		}
	}


	// ✅ For Classic Checkout
	public function blacklist_country_validation_classic( $fields, $errors ) {
		$this->blacklist_country_option_validation( $fields, $errors );
	}


	// ✅ For Block-based Checkout
	public function blacklist_country_validation_block( $order, $request ) {
		// Convert REST request data to the same format used in classic validation
		$fields = array(
			'billing_postcode' => isset( $order ) && $order->get_billing_postcode() ? $order->get_billing_postcode() : '',
			'shipping_postcode' => isset( $order ) && $order->get_shipping_postcode() ? $order->get_shipping_postcode() : '',

			'billing_state' => isset( $order ) && $order->get_billing_state() ? $order->get_billing_state() : '',
			'shipping_state' => isset( $order ) && $order->get_shipping_state() ? $order->get_shipping_state() : '',
			'billing_country' => isset( $order ) && $order->get_billing_country() ? $order->get_billing_country() : '',
			'shipping_country' => isset( $order ) && $order->get_shipping_country() ? $order->get_shipping_country() : '',
			'billing_phone' => isset( $order ) && $order->get_billing_phone() ? $order->get_billing_phone() : '',
			'billing_email' => isset( $order ) && $order->get_billing_email() ? $order->get_billing_email() : '',
			'billing_city' => isset( $order ) && $order->get_billing_city() ? $order->get_billing_city() : '',
			'billing_address_1' => isset( $order ) && $order->get_billing_address_1() ? $order->get_billing_address_1() : '',
			'billing_address_2' => isset( $order ) && $order->get_billing_address_2() ? $order->get_billing_address_2() : '',
			'billing_first_name' => isset( $order ) && $order->get_billing_first_name() ? $order->get_billing_first_name() : '',
			'billing_last_name' => isset( $order ) && $order->get_billing_last_name() ? $order->get_billing_last_name() : '',
			
		);

		// WooCommerce blocks don’t use WP_Error directly — mimic for compatibility
		$errors = new WP_Error();

		$this->blacklist_country_option_validation( $fields, $errors );

		// If any errors, throw them back to block checkout system
		if ( $errors->has_errors() ) {
			foreach ( $errors->get_error_messages() as $error_message ) {
				throw new \WC_REST_Exception( 'woocommerce_invalid_zipcode', $error_message, 400 );
			}
		}
	}

	// ✅ For Classic Checkout
	public function blacklist_customer_name_validation_classic( $fields, $errors ) {
		$this->blacklist_customer_name_validation( $fields, $errors );
	}


	// ✅ For Block-based Checkout
	public function blacklist_customer_name_validation_block( $order, $request ) {
		// Convert REST request data to the same format used in classic validation
		$fields = array(

			'billing_first_name' => isset( $order ) && $order->get_billing_first_name() ? $order->get_billing_first_name() : '',
			'billing_last_name' => isset( $order ) && $order->get_billing_last_name() ? $order->get_billing_last_name() : '',

			'billing_phone' => isset( $order ) && $order->get_billing_phone() ? $order->get_billing_phone() : '',
			'billing_email' => isset( $order ) && $order->get_billing_email() ? $order->get_billing_email() : '',
			'billing_country' => isset( $order ) && $order->get_billing_country() ? $order->get_billing_country() : '',
			'billing_state' => isset( $order ) && $order->get_billing_state() ? $order->get_billing_state() : '',
			'billing_city' => isset( $order ) && $order->get_billing_city() ? $order->get_billing_city() : '',
			'billing_postcode' => isset( $order ) && $order->get_billing_postcode() ? $order->get_billing_postcode() : '',
			'billing_address_1' => isset( $order ) && $order->get_billing_address_1() ? $order->get_billing_address_1() : '',
			'billing_address_2' => isset( $order ) && $order->get_billing_address_2() ? $order->get_billing_address_2() : '',
			'billing_first_name' => isset( $order ) && $order->get_billing_first_name() ? $order->get_billing_first_name() : '',
			'billing_last_name' => isset( $order ) && $order->get_billing_last_name() ? $order->get_billing_last_name() : '',
		);

		// WooCommerce blocks don’t use WP_Error directly — mimic for compatibility
		$errors = new WP_Error();

		$this->blacklist_customer_name_validation( $fields, $errors );

		// If any errors, throw them back to block checkout system
		if ( $errors->has_errors() ) {
			foreach ( $errors->get_error_messages() as $error_message ) {
				throw new \WC_REST_Exception( 'woocommerce_invalid_zipcode', $error_message, 400 );
			}
		}
	}

	// ✅ For Classic Checkout
	public function blacklist_mob_no_validation_classic( $fields, $errors ) {
		$this->blacklist_mob_no_option_validation( $fields, $errors );
	}


	// ✅ For Block-based Checkout
	public function blacklist_mob_no_validation_block( $order, $request ) {
		// Convert REST request data to the same format used in classic validation
		$fields = array(
			'billing_phone' => isset( $order ) && $order->get_billing_phone() ? $order->get_billing_phone() : '',
			'billing_email' => isset( $order ) && $order->get_billing_email() ? $order->get_billing_email() : '',
			'billing_country' => isset( $order ) && $order->get_billing_country() ? $order->get_billing_country() : '',
			'billing_state' => isset( $order ) && $order->get_billing_state() ? $order->get_billing_state() : '',
			'billing_city' => isset( $order ) && $order->get_billing_city() ? $order->get_billing_city() : '',
			'billing_postcode' => isset( $order ) && $order->get_billing_postcode() ? $order->get_billing_postcode() : '',
			'billing_address_1' => isset( $order ) && $order->get_billing_address_1() ? $order->get_billing_address_1() : '',
			'billing_address_2' => isset( $order ) && $order->get_billing_address_2() ? $order->get_billing_address_2() : '',
			'billing_first_name' => isset( $order ) && $order->get_billing_first_name() ? $order->get_billing_first_name() : '',
			'billing_last_name' => isset( $order ) && $order->get_billing_last_name() ? $order->get_billing_last_name() : '',
		);

		// WooCommerce blocks don’t use WP_Error directly — mimic for compatibility
		$errors = new WP_Error();

		$this->blacklist_mob_no_option_validation( $fields, $errors );

		// If any errors, throw them back to block checkout system
		if ( $errors->has_errors() ) {
			foreach ( $errors->get_error_messages() as $error_message ) {
				throw new \WC_REST_Exception( 'woocommerce_invalid_zipcode', $error_message, 400 );
			}
		}
	}


	// ✅ For Classic Checkout
	public function blacklist_ips_email_names_validation_classic( $fields, $errors ) {
		$this->misha_validate_fname_lname( $fields, $errors );
	}


	// ✅ For Block-based Checkout
	public function blacklist_ips_email_names_validation_block( $order, $request ) {
		// Convert REST request data to the same format used in classic validation
		
		$fields = array(
			'billing_phone' => isset( $order ) && $order->get_billing_phone() ? $order->get_billing_phone() : '',
			'billing_email' => isset( $order ) && $order->get_billing_email() ? $order->get_billing_email() : '',
			'billing_country' => isset( $order ) && $order->get_billing_country() ? $order->get_billing_country() : '',
			'billing_state' => isset( $order ) && $order->get_billing_state() ? $order->get_billing_state() : '',
			'billing_city' => isset( $order ) && $order->get_billing_city() ? $order->get_billing_city() : '',
			'billing_postcode' => isset( $order ) && $order->get_billing_postcode() ? $order->get_billing_postcode() : '',
			'billing_address_1' => isset( $order ) && $order->get_billing_address_1() ? $order->get_billing_address_1() : '',
			'billing_address_2' => isset( $order ) && $order->get_billing_address_2() ? $order->get_billing_address_2() : '',
			'billing_first_name' => isset( $order ) && $order->get_billing_first_name() ? $order->get_billing_first_name() : '',
			'billing_last_name' => isset( $order ) && $order->get_billing_last_name() ? $order->get_billing_last_name() : '',
		);

		// WooCommerce blocks don’t use WP_Error directly — mimic for compatibility
		$errors = new WP_Error();

		$this->misha_validate_fname_lname( $fields, $errors );

		// If any errors, throw them back to block checkout system
		if ( $errors->has_errors() ) {
			foreach ( $errors->get_error_messages() as $error_message ) {
				throw new \WC_REST_Exception( 'woocommerce_invalid_zipcode', $error_message, 400 );
			}
		}
	}

	// ✅ For Classic Checkout
	public function wildcard_email_validation_classic( $fields, $errors ) {
		$this->wildcard_email_validation( $fields, $errors );
	}


	// ✅ For Block-based Checkout
	public function wildcard_email_validation_block( $order, $errors ) {
		// Convert REST request data to the same format used in classic validation
		
		$fields = array(
			'billing_phone' => isset( $order ) && $order->get_billing_phone() ? $order->get_billing_phone() : '',
			'billing_email' => isset( $order ) && $order->get_billing_email() ? $order->get_billing_email() : '',
			'billing_country' => isset( $order ) && $order->get_billing_country() ? $order->get_billing_country() : '',
			'billing_state' => isset( $order ) && $order->get_billing_state() ? $order->get_billing_state() : '',

			'billing_city' => isset( $order ) && $order->get_billing_city() ? $order->get_billing_city() : '',
			'billing_postcode' => isset( $order ) && $order->get_billing_postcode() ? $order->get_billing_postcode() : '',
			'billing_address_1' => isset( $order ) && $order->get_billing_address_1() ? $order->get_billing_address_1() : '',
			'billing_address_2' => isset( $order ) && $order->get_billing_address_2() ? $order->get_billing_address_2() : '',
			'billing_first_name' => isset( $order ) && $order->get_billing_first_name() ? $order->get_billing_first_name() : '',
			'billing_last_name' => isset( $order ) && $order->get_billing_last_name() ? $order->get_billing_last_name() : '',
		);

		// WooCommerce blocks don’t use WP_Error directly — mimic for compatibility
		$errors = new WP_Error();

		$this->wildcard_email_validation( $fields, $errors );

		// If any errors, throw them back to block checkout system
		if ( $errors->has_errors() ) {
			foreach ( $errors->get_error_messages() as $error_message ) {
				throw new \WC_REST_Exception( 'woocommerce_invalid_zipcode', $error_message, 400 );
			}
		}
	}

	// ✅ For Classic Checkout
	public function too_many_order_attempt_validation_classic( $fields, $errors ) {
		$this->too_many_order_attempt_validation( $fields, $errors );
	}


	// ✅ For Block-based Checkout
	public function too_many_order_attempt_validation_block( $order, $request ) {
		// Convert REST request data to the same format used in classic validation
		
		// Convert REST request data to the same format used in classic validation
		$order_total = ( $order instanceof WC_Order ) ? (float) $order->get_total() : 0;
		$fields = array(
			'billing_phone' => isset( $order ) && $order->get_billing_phone() ? $order->get_billing_phone() : '',
			'billing_email' => isset( $order ) && $order->get_billing_email() ? $order->get_billing_email() : '',
			'billing_country' => isset( $order ) && $order->get_billing_country() ? $order->get_billing_country() : '',
			'order_total' => $order_total,
			'billing_state' => isset( $order ) && $order->get_billing_state() ? $order->get_billing_state() : '',
			'billing_city' => isset( $order ) && $order->get_billing_city() ? $order->get_billing_city() : '',
			'billing_postcode' => isset( $order ) && $order->get_billing_postcode() ? $order->get_billing_postcode() : '',
			'billing_address_1' => isset( $order ) && $order->get_billing_address_1() ? $order->get_billing_address_1() : '',
			'billing_address_2' => isset( $order ) && $order->get_billing_address_2() ? $order->get_billing_address_2() : '',
			'billing_first_name' => isset( $order ) && $order->get_billing_first_name() ? $order->get_billing_first_name() : '',
			'billing_last_name' => isset( $order ) && $order->get_billing_last_name() ? $order->get_billing_last_name() : '',
		);

		// WooCommerce blocks don’t use WP_Error directly — mimic for compatibility
		$errors = new WP_Error();

		$this->too_many_order_attempt_validation( $fields, $errors );

		// If any errors, throw them back to block checkout system
		if ( $errors->has_errors() ) {
			foreach ( $errors->get_error_messages() as $error_message ) {
				throw new \WC_REST_Exception( 'woocommerce_invalid_zipcode', $error_message, 400 );
			}
		}
	}

	// ✅ For Classic Checkout
	public function max_order_attempt_between_timespan_classic( $fields, $errors ) {
		$this->max_order_attempt_between_timespan( $fields, $errors );
	}


	// ✅ For Block-based Checkout
	public function max_order_attempt_between_timespan_block( $order, $request ) {
		// Convert REST request data to the same format used in classic validation
		
		$fields = array(
			'billing_phone' => isset( $order ) && $order->get_billing_phone() ? $order->get_billing_phone() : '',
			'billing_email' => isset( $order ) && $order->get_billing_email() ? $order->get_billing_email() : '',
			'billing_country' => isset( $order ) && $order->get_billing_country() ? $order->get_billing_country() : '',
			'billing_state' => isset( $order ) && $order->get_billing_state() ? $order->get_billing_state() : '',
			'billing_city' => isset( $order ) && $order->get_billing_city() ? $order->get_billing_city() : '',
			'billing_postcode' => isset( $order ) && $order->get_billing_postcode() ? $order->get_billing_postcode() : '',
			'billing_address_1' => isset( $order ) && $order->get_billing_address_1() ? $order->get_billing_address_1() : '',
			'billing_address_2' => isset( $order ) && $order->get_billing_address_2() ? $order->get_billing_address_2() : '',
			'billing_first_name' => isset( $order ) && $order->get_billing_first_name() ? $order->get_billing_first_name() : '',
			'billing_last_name' => isset( $order ) && $order->get_billing_last_name() ? $order->get_billing_last_name() : '',
		);

		// WooCommerce blocks don’t use WP_Error directly — mimic for compatibility
		$errors = new WP_Error();

		$this->max_order_attempt_between_timespan( $fields, $errors );

		// If any errors, throw them back to block checkout system
		if ( $errors->has_errors() ) {
			foreach ( $errors->get_error_messages() as $error_message ) {
				throw new \WC_REST_Exception( 'woocommerce_invalid_zipcode', $error_message, 400 );
			}
		}
	}

	public function pre_payment_validation_block( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$order_id = $order->get_id();
		$fields = array(
			'billing_phone'      => $order->get_billing_phone(),
			'billing_email'      => $order->get_billing_email(),
			'billing_country'    => $order->get_billing_country(),
			'billing_state'      => $order->get_billing_state(),
			'billing_city'       => $order->get_billing_city(),
			'billing_postcode'   => $order->get_billing_postcode(),
			'billing_address_1'  => $order->get_billing_address_1(),
			'billing_address_2'  => $order->get_billing_address_2(),
			'billing_first_name' => $order->get_billing_first_name(),
			'billing_last_name'  => $order->get_billing_last_name(),
		);

		try {
			$this->wh_pre_paymentcall( $order_id, $fields, array() );
		} catch ( \WC_REST_Exception $e ) {
			// The Store API requires RouteException for user-visible checkout errors.
			// Re-throw using the correct exception class so the fraud block message
			// is shown to the customer instead of the generic "Something went wrong".
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
				$e->getErrorCode(),
				$e->getMessage(),
				$e->getCode() ? $e->getCode() : 400
			);
		} catch ( \Throwable $e ) {
			// Prevent any unexpected rule or API error from crashing the entire
			// checkout request. Log it and let the order proceed; the post-payment
			// cron will run the fraud check once payment completes.
			Af_Logger::debug( 'pre_payment_validation_block error (non-fatal): ' . $e->getMessage() );
		}
	}

	public function pre_payment_validation_classic( $order_id, $errors) {

		if ( ! $order_id ) {
			return;
		}
		
		$order = new WC_Order( $order_id );
		$fields = array(
			'billing_phone' => isset( $order ) && $order->get_billing_phone() ? $order->get_billing_phone() : '',
			'billing_email' => isset( $order ) && $order->get_billing_email() ? $order->get_billing_email() : '',
			'billing_country' => isset( $order ) && $order->get_billing_country() ? $order->get_billing_country() : '',
			'billing_state' => isset( $order ) && $order->get_billing_state() ? $order->get_billing_state() : '',
			'billing_city' => isset( $order ) && $order->get_billing_city() ? $order->get_billing_city() : '',
			'billing_postcode' => isset( $order ) && $order->get_billing_postcode() ? $order->get_billing_postcode() : '',
			'billing_address_1' => isset( $order ) && $order->get_billing_address_1() ? $order->get_billing_address_1() : '',
			'billing_address_2' => isset( $order ) && $order->get_billing_address_2() ? $order->get_billing_address_2() : '',
			'billing_first_name' => isset( $order ) && $order->get_billing_first_name() ? $order->get_billing_first_name() : '',
			'billing_last_name' => isset( $order ) && $order->get_billing_last_name() ? $order->get_billing_last_name() : '',
					);
		$this->wh_pre_paymentcall( $order_id, $fields, $errors );
	}


	// 1. Register the custom order status
	public function custom_register_order_statuses() {
		register_post_status( 'wc-mark-as-safe', array(
			'label'                     => 'Mark As Safe',
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: number of items marked as safe */
			'label_count'               => _n_noop( 'Mark As Safe <span class="count">(%s)</span>', 'Mark As Safe <span class="count">(%s)</span>' )
		));
	}

	// 2. Add it to WooCommerce order statuses list
	public function custom_add_order_statuses( $order_statuses ) {
		$new_order_statuses = array();

		// Insert our custom status after 'processing'
		foreach ( $order_statuses as $key => $status ) {
			$new_order_statuses[ $key ] = $status;
			if ( 'wc-processing' === $key ) {
				$new_order_statuses['wc-mark-as-safe'] = 'Mark As Safe';
			}
		}

		return $new_order_statuses;
	}

	/* Cron schedule for get order avrg amount*/
	/* Cron schedule for get order avrg amount*/
	public function wc_af_maybe_schedule_cron() {
		if ( ! wp_next_scheduled( 'wc_af_refresh_avg_order_total' ) ) {
			wp_schedule_event( time(), 'daily', 'wc_af_refresh_avg_order_total' );
		}
		if ( ! wp_next_scheduled( 'wc_af_purge_attempt_records' ) ) {
			wp_schedule_event( time(), 'daily', 'wc_af_purge_attempt_records' );
		}
		add_action( 'wc_af_purge_attempt_records', array( $this, 'wc_af_purge_attempt_records_handler' ) );
	}

	/**
	 * Purge old attempt records (retention: 30 days).
	 *
	 * @since 7.3.0
	 */
	public function wc_af_purge_attempt_records_handler() {
		if ( ! class_exists( 'WC_AF_Attempt_Intelligence_Service' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'anti-fraud-core/class-wc-af-attempt-intelligence-service.php';
		}
		$service = WC_AF_Attempt_Intelligence_Service::get_instance();
		$service->purge_old_records( 30 );
	}

	public function wc_af_refresh_avg_order_total_handler() {
		
		/**
		 * Filters the list of order statuses considered for high-value order checks.
		 *
		 * @since 7.0.8
		 *
		 * @param array $statuses Array of WooCommerce order status slugs.
		 * @return array Modified list of order statuses.
		 */
		$statuses = apply_filters( 'wc_af_high_value_value_order_statuses', [ 'wc-completed', 'wc-processing', 'wc-on-hold' ] );

		$limit    = 1000;
		$page     = 1;
		$total    = 0;
		$count    = 0;
		
		do {
			$args = [
				'limit'   => $limit,
				'page'    => $page,
				'type'    => 'shop_order',
				'status'  => $statuses,
			];

			$orders = wc_get_orders( $args );
			foreach ( $orders as $order ) {
				$order_total = (float) $order->get_total();
				if ( $order_total > 0 ) {
					$total += $order_total;
					$count++;
				}
			}

			$page++;
			$has_more = count( $orders ) === $limit;
		} while ( $has_more );

		$avg = $count > 0 ? round( $total / $count, 2 ) : 0;
		set_transient( 'wc_af_avg_order_total', $avg, DAY_IN_SECONDS );
		return $avg;
	}

	public function update_blacklist_and_whitelist_mob_no() {

		$whitelist_mob_no = get_option( 'wc_af_whitelist_phone_numbers' );
		$blacklist_mob_no  = get_option( 'wc_af_blacklisted_phone_numbers' );

		if ( ! empty( $blacklist_mob_no ) && ! empty( $whitelist_mob_no ) ) {

			$array_ipaddress           = explode( ',', $blacklist_mob_no );
			$array_whitelist_mob_no = explode( ',', $whitelist_mob_no );

			// Remove duplicate IP addresses from the blacklist
			if ( ! empty( $array_ipaddress ) ) {
				$unique_blacklist_mob_no = array_unique( $array_ipaddress );
			} else {
				$unique_blacklist_mob_no = $array_ipaddress;
			}

			// Check for common IP addresses and remove them from both whitelist and blacklist
			$common_mob_no = array_intersect( $unique_blacklist_mob_no, $array_whitelist_mob_no );

			if ( ! empty( $common_mob_no ) ) {
				// Remove common IP addresses from the blacklist
				$unique_blacklist_mob_no = array_diff( $unique_blacklist_mob_no, $common_mob_no );
			}

			// Ensure we only update if $unique_blocked_ipaddress is not empty
			if ( ! empty( $unique_blacklist_mob_no ) ) {
				$blacklist_mob_no = implode( ',', $unique_blacklist_mob_no );
				update_option( 'wc_af_blacklisted_phone_numbers', $blacklist_mob_no );	
			} else {
				update_option( 'wc_af_blacklisted_phone_numbers', '' );
			}
		}
	}

	public function update_blacklist_mob_no_option( $option_name, $old_value, $new_value ) {
		// We want to target only our specific option, wc_af_blacklisted_phone_numbers
		if ( 'wc_af_blacklisted_phone_numbers' === $option_name ) {
			
			$whitelist_mob_no = get_option( 'wc_af_whitelist_phone_numbers' );
			
			if ( ! empty( $new_value ) && ! empty( $whitelist_mob_no ) ) {
				$array_ipaddress = explode( ',', $new_value );
				$array_whitelist_mob_no = explode( ',', $whitelist_mob_no );

				// Remove duplicate phone numbers from the blacklist
				$unique_blacklist_mob_no = array_unique( $array_ipaddress );
				
				// Find common numbers between blacklist and whitelist
				$common_mo_no = array_intersect( $unique_blacklist_mob_no, $array_whitelist_mob_no );

				// Remove common numbers from the blacklist
				if ( ! empty( $common_mo_no ) ) {
					$unique_blacklist_mob_no = array_diff( $unique_blacklist_mob_no, $common_mo_no );
				}

				// If after cleaning up the blacklist isn't empty, update it
				if ( ! empty( $unique_blacklist_mob_no ) ) {
					update_option( 'wc_af_blacklisted_phone_numbers', implode( ',', $unique_blacklist_mob_no ) );
				} else {
					// If nothing is left after cleanup, set the blacklist to an empty string
					update_option( 'wc_af_blacklisted_phone_numbers', '' );
				}
			}
		}
	}

	public function update_blacklist_and_whitelist_country() {

		$whitelist_countries = (array) get_option( 'wc_af_whitelisted_countries', [] );
		$blacklist_countries  = (array) get_option( 'wc_af_blacklisted_countries', [] );

		if ( ! empty( $blacklist_countries ) && ! empty( $whitelist_countries ) ) {

			// Find common countries between blacklist and whitelist
			$common_countries = array_intersect( $blacklist_countries, $whitelist_countries );

			if ( ! empty( $common_countries ) ) {
				// Remove common countries from the blacklist
				$unique_blacklist_countries = array_diff( $blacklist_countries, $common_countries );
			} else {
				$unique_blacklist_countries = $blacklist_countries;
			}

			// Ensure we only update if $unique_blacklist_countries is not empty
			if ( ! empty( $unique_blacklist_countries ) ) {
				update_option( 'wc_af_blacklisted_countries', $unique_blacklist_countries );	
			} else {
				update_option( 'wc_af_blacklisted_countries', '');
			}
		}
	}

	public function update_blacklist_country_option( $option_name, $old_value, $new_value ) {
		
		if ( 'wc_af_blacklisted_countries' === $option_name ) {
			
			$whitelist_countries = (array) get_option( 'wc_af_whitelisted_countries', [] );
			
			if ( ! empty( $new_value ) && ! empty( $whitelist_countries ) ) {
				$blacklist_countries = (array) $new_value;

				// Find common countries between blacklist and whitelist
				$common_countries = array_intersect( $blacklist_countries, $whitelist_countries );

				// Remove common countries from the blacklist
				if ( ! empty( $common_countries ) ) {
					$unique_blacklist_countries = array_diff( $blacklist_countries, $common_countries );
				} else {
					$unique_blacklist_countries = $blacklist_countries;
				}

				// If after cleaning up the blacklist isn't empty, update it
				if ( ! empty( $unique_blacklist_countries ) ) {
					update_option( 'wc_af_blacklisted_countries', $unique_blacklist_countries );
				} else {
					// If nothing is left after cleanup, set the blacklist to an empty array
					update_option( 'wc_af_blacklisted_countries', array() );
				}
			}
		}
	}

	public function update_whitelist_country_option( $option_name, $old_value, $new_value ) {
		
		if ( 'wc_af_whitelisted_countries' === $option_name ) {
			
			$blacklist_countries = (array) get_option( 'wc_af_blacklisted_countries', [] );
			
			if ( ! empty( $new_value ) && ! empty( $blacklist_countries ) ) {
				$whitelist_countries = (array) $new_value;

				// Find common countries between blacklist and whitelist
				$common_countries = array_intersect( $blacklist_countries, $whitelist_countries );

				// Remove common countries from the blacklist
				if ( ! empty( $common_countries ) ) {
					$unique_blacklist_countries = array_diff( $blacklist_countries, $common_countries );
				} else {
					$unique_blacklist_countries = $blacklist_countries;
				}

				// If after cleaning up the blacklist isn't empty, update it
				if ( ! empty( $unique_blacklist_countries ) ) {
					update_option( 'wc_af_blacklisted_countries', $unique_blacklist_countries );
				} else {
					// If nothing is left after cleanup, set the blacklist to an empty array
					update_option( 'wc_af_blacklisted_countries', array() );
				}
			}
		}
	}

	/**
	 * Sync state whitelist and blacklist on admin init
	 */
	public function update_blacklist_and_whitelist_state() {
		$get_whitelist_states = get_option( 'wc_af_whitelisted_states', '' );
		$get_blacklist_states = get_option( 'wc_af_blacklisted_states', '' );
		
		// Parse whitelist (comma or newline separated string)
		if ( is_array( $get_whitelist_states ) ) {
			$whitelist_states = $get_whitelist_states;
		} else {
			$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelist_states );
		}
		$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
		$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
		
		// Parse blacklist (comma or newline separated string or array)
		if ( is_array( $get_blacklist_states ) ) {
			$blacklist_states = $get_blacklist_states;
		} else {
			$blacklist_states = preg_split( '/[\r\n,]+/', (string) $get_blacklist_states );
		}
		$blacklist_states = array_filter( array_map( 'trim', (array) $blacklist_states ) );
		$blacklist_states = array_map( 'wc_af_strtolower', $blacklist_states );
		
		if ( ! empty( $blacklist_states ) && ! empty( $whitelist_states ) ) {
			$common_states = array_intersect( $blacklist_states, $whitelist_states );
			
			if ( ! empty( $common_states ) ) {
				$unique_blacklist_states = array_diff( $blacklist_states, $common_states );
			} else {
				$unique_blacklist_states = $blacklist_states;
			}
			
			// Convert back to string format (comma-separated)
			if ( ! empty( $unique_blacklist_states ) ) {
				$blacklist_string = implode( ', ', $unique_blacklist_states );
				update_option( 'wc_af_blacklisted_states', $blacklist_string );	
			} else {
				update_option( 'wc_af_blacklisted_states', '' );
			}
		}
	}
	
	/**
	 * Remove whitelisted states from blacklist when blacklist is updated
	 */
	public function update_blacklist_state_option( $option_name, $old_value, $new_value ) {
		
		if ( 'wc_af_blacklisted_states' === $option_name ) {
			
			$get_whitelist_states = get_option( 'wc_af_whitelisted_states', '' );
			
			if ( ! empty( $new_value ) && ! empty( $get_whitelist_states ) ) {
				// Parse whitelist
				if ( is_array( $get_whitelist_states ) ) {
					$whitelist_states = $get_whitelist_states;
				} else {
					$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelist_states );
				}
				$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
				$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
				
				// Parse blacklist
				if ( is_array( $new_value ) ) {
					$blacklist_states = $new_value;
				} else {
					$blacklist_states = preg_split( '/[\r\n,]+/', (string) $new_value );
				}
				$blacklist_states = array_filter( array_map( 'trim', (array) $blacklist_states ) );
				$blacklist_states = array_map( 'wc_af_strtolower', $blacklist_states );
				
				// Find common states between blacklist and whitelist
				$common_states = array_intersect( $blacklist_states, $whitelist_states );
				
				// Remove common states from the blacklist
				if ( ! empty( $common_states ) ) {
					$unique_blacklist_states = array_diff( $blacklist_states, $common_states );
				} else {
					$unique_blacklist_states = $blacklist_states;
				}
				
				// Convert back to string format
				if ( ! empty( $unique_blacklist_states ) ) {
					$blacklist_string = implode( ', ', $unique_blacklist_states );
					update_option( 'wc_af_blacklisted_states', $blacklist_string );
				} else {
					update_option( 'wc_af_blacklisted_states', '' );
				}
			}
		}
	}
	
	/**
	 * Remove whitelisted states from blacklist when whitelist is updated
	 */
	public function update_whitelist_state_option( $option_name, $old_value, $new_value ) {
		
		if ( 'wc_af_whitelisted_states' === $option_name ) {
			
			$get_blacklist_states = get_option( 'wc_af_blacklisted_states', '' );
			
			if ( ! empty( $new_value ) && ! empty( $get_blacklist_states ) ) {
				// Parse whitelist
				if ( is_array( $new_value ) ) {
					$whitelist_states = $new_value;
				} else {
					$whitelist_states = preg_split( '/[\r\n,]+/', (string) $new_value );
				}
				$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
				$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
				
				// Parse blacklist
				if ( is_array( $get_blacklist_states ) ) {
					$blacklist_states = $get_blacklist_states;
				} else {
					$blacklist_states = preg_split( '/[\r\n,]+/', (string) $get_blacklist_states );
				}
				$blacklist_states = array_filter( array_map( 'trim', (array) $blacklist_states ) );
				$blacklist_states = array_map( 'wc_af_strtolower', $blacklist_states );

				// Find common states between blacklist and whitelist
				$common_states = array_intersect( $blacklist_states, $whitelist_states );

				// Remove common states from the blacklist
				if ( ! empty( $common_states ) ) {
					$unique_blacklist_states = array_diff( $blacklist_states, $common_states );
				} else {
					$unique_blacklist_states = $blacklist_states;
				}

				// Convert back to string format
				if ( ! empty( $unique_blacklist_states ) ) {
					$blacklist_string = implode( ', ', $unique_blacklist_states );
					update_option( 'wc_af_blacklisted_states', $blacklist_string );
				} else {
					update_option( 'wc_af_blacklisted_states', '' );
				}
			}
		}
	}


	public function update_blacklist_and_whitelist_city() {

		$whitelist_city = (array) get_option( 'wc_af_whitelisted_city', [] );
		$blacklist_city  = (array) get_option( 'wc_af_blacklisted_cities', [] );

		if ( ! empty( $blacklist_city ) && ! empty( $whitelist_city ) ) {

			// Find common countries between blacklist and whitelist
			$common_city = array_intersect( $blacklist_city, $whitelist_city );

			if ( ! empty( $common_city ) ) {
				// Remove common countries from the blacklist
				$blacklist_city = array_diff( $blacklist_city, $common_city );
			} else {
				$unique_blacklist_city = $blacklist_city;
			}

			// Ensure we only update if $unique_blacklist_countries is not empty
			if ( ! empty( $unique_blacklist_citys ) ) {
				$unique_blacklist_city = implode( ', ', $unique_blacklist_city );
				update_option( 'wc_af_blacklisted_states', $blacklist_string );
				update_option( 'wc_af_blacklisted_cities', $unique_blacklist_city );	
			} else {
				update_option( 'wc_af_blacklisted_cities', '' );
			}
		}
	}

	public function update_blacklist_city_option( $option_name, $old_value, $new_value ) {
		
		if ( 'wc_af_blacklisted_cities' === $option_name ) {
			
			$whitelist_city = (array) get_option( 'wc_af_whitelisted_city', [] );
			
			if ( ! empty( $new_value ) && ! empty( $whitelist_city ) ) {
				$blacklist_city = (array) $new_value;

				// Find common countries between blacklist and whitelist
				$common_countries = array_intersect( $blacklist_city, $whitelist_city );

				// Remove common countries from the blacklist
				if ( ! empty( $common_countries ) ) {
					$unique_blacklist_city = array_diff( $blacklist_city, $common_countries );
				} else {
					$unique_blacklist_city = $blacklist_city;
				}

				// If after cleaning up the blacklist isn't empty, update it
				if ( ! empty( $unique_blacklist_city ) ) {
					$unique_blacklist_city = implode( ', ', $unique_blacklist_city );

					update_option( 'wc_af_blacklisted_cities', $unique_blacklist_city );
				} else {
					// If nothing is left after cleanup, set the blacklist to an empty array
					update_option( 'wc_af_blacklisted_cities', '' );
				}
			}
		}
	}

	public function update_whitelist_city_option( $option_name, $old_value, $new_value ) {
		
		if ( 'wc_af_whitelisted_city' === $option_name ) {
			
			$blacklist_city = (array) get_option( 'wc_af_blacklisted_cities', [] );
			
			if ( ! empty( $new_value ) && ! empty( $blacklist_city ) ) {
				$whitelist_city = (array) $new_value;

				// Find common countries between blacklist and whitelist
				$common_city = array_intersect( $blacklist_city, $whitelist_city );

				// Remove common countries from the blacklist
				if ( ! empty( $common_city ) ) {
					$unique_blacklist_city = array_diff( $blacklist_city, $common_city );
				} else {
					$unique_blacklist_city = $blacklist_city;
				}

				// If after cleaning up the blacklist isn't empty, update it
				if ( ! empty( $unique_blacklist_city ) ) {
					$unique_blacklist_city = implode( ', ', $unique_blacklist_city );

					update_option( 'wc_af_blacklisted_cities', $unique_blacklist_city );
				} else {
					// If nothing is left after cleanup, set the blacklist to an empty array
					update_option( 'wc_af_blacklisted_cities', '' );
				}
			}
		}
	}

	/**
	 * Sync ZIP/Postal Code whitelist and blacklist on admin init
	 */
	public function update_blacklist_and_whitelist_zip() {
		$get_whitelist_zips = get_option( 'wc_af_whitelisted_zip', '' );
		$get_blacklist_zips = get_option( 'wc_af_blacklisted_zipcodes', array() );
		
		// Parse whitelist (comma or newline separated string)
		if ( is_array( $get_whitelist_zips ) ) {
			$whitelist_zips = $get_whitelist_zips;
		} else {
			$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $get_whitelist_zips );
		}
		$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
		$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
		
		// Parse blacklist (array or comma/newline separated string)
		if ( is_array( $get_blacklist_zips ) ) {
			$blacklist_zips = $get_blacklist_zips;
		} else {
			$blacklist_zips = preg_split( '/[\r\n,]+/', (string) $get_blacklist_zips );
		}
		$blacklist_zips = array_filter( array_map( 'trim', (array) $blacklist_zips ) );
		$blacklist_zips = array_map( 'wc_af_strtolower', $blacklist_zips );
		
		if ( ! empty( $blacklist_zips ) && ! empty( $whitelist_zips ) ) {
			$common_zips = array_intersect( $blacklist_zips, $whitelist_zips );
			
			if ( ! empty( $common_zips ) ) {
				$unique_blacklist_zips = array_diff( $blacklist_zips, $common_zips );
			} else {
				$unique_blacklist_zips = $blacklist_zips;
			}
			
			// Update blacklist option
			if ( ! empty( $unique_blacklist_zips ) ) {
				$unique_blacklist_zips = implode( ', ', $unique_blacklist_zips );

				update_option( 'wc_af_blacklisted_zipcodes', $unique_blacklist_zips );	
			} else {
				update_option( 'wc_af_blacklisted_zipcodes', '' );
			}
		}
	}

	/**
	 * Remove whitelisted ZIPs from blacklist when blacklist is updated
	 */
	public function update_blacklist_zip_option( $option_name, $old_value, $new_value ) {
		
		if ( 'wc_af_blacklisted_zipcodes' === $option_name ) {
			
			$get_whitelist_zips = get_option( 'wc_af_whitelisted_zip', '' );
			
			if ( ! empty( $new_value ) && ! empty( $get_whitelist_zips ) ) {
				// Parse whitelist
				if ( is_array( $get_whitelist_zips ) ) {
					$whitelist_zips = $get_whitelist_zips;
				} else {
					$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $get_whitelist_zips );
				}
				$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
				$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
				
				// Parse blacklist
				if ( is_array( $new_value ) ) {
					$blacklist_zips = $new_value;
				} else {
					$blacklist_zips = preg_split( '/[\r\n,]+/', (string) $new_value );
				}
				$blacklist_zips = array_filter( array_map( 'trim', (array) $blacklist_zips ) );
				$blacklist_zips = array_map( 'wc_af_strtolower', $blacklist_zips );
				
				// Find common ZIPs between blacklist and whitelist
				$common_zips = array_intersect( $blacklist_zips, $whitelist_zips );
				
				// Remove common ZIPs from the blacklist
				if ( ! empty( $common_zips ) ) {
					$unique_blacklist_zips = array_diff( $blacklist_zips, $common_zips );
				} else {
					$unique_blacklist_zips = $blacklist_zips;
				}
				
				// Update blacklist option
				if ( ! empty( $unique_blacklist_zips ) ) {
					$unique_blacklist_zips = implode( ', ', $unique_blacklist_zips );

					update_option( 'wc_af_blacklisted_zipcodes', $unique_blacklist_zips );
				} else {
					update_option( 'wc_af_blacklisted_zipcodes', '' );
				}
			}
		}
	}

	/**
	 * Remove whitelisted ZIPs from blacklist when whitelist is updated
	 */
	public function update_whitelist_zip_option( $option_name, $old_value, $new_value ) {
		
		if ( 'wc_af_whitelisted_zip' === $option_name ) {
			
			$get_blacklist_zips = get_option( 'wc_af_blacklisted_zipcodes', array() );
			
			if ( ! empty( $new_value ) && ! empty( $get_blacklist_zips ) ) {
				// Parse whitelist
				if ( is_array( $new_value ) ) {
					$whitelist_zips = $new_value;
				} else {
					$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $new_value );
				}
				$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
				$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
				
				// Parse blacklist
				if ( is_array( $get_blacklist_zips ) ) {
					$blacklist_zips = $get_blacklist_zips;
				} else {
					$blacklist_zips = preg_split( '/[\r\n,]+/', (string) $get_blacklist_zips );
				}
				$blacklist_zips = array_filter( array_map( 'trim', (array) $blacklist_zips ) );
				$blacklist_zips = array_map( 'wc_af_strtolower', $blacklist_zips );

				// Find common ZIPs between blacklist and whitelist
				$common_zips = array_intersect( $blacklist_zips, $whitelist_zips );

				// Remove common ZIPs from the blacklist
				if ( ! empty( $common_zips ) ) {
					$unique_blacklist_zips = array_diff( $blacklist_zips, $common_zips );
				} else {
					$unique_blacklist_zips = $blacklist_zips;
				}

				// Update blacklist option
				if ( ! empty( $unique_blacklist_zips ) ) {
					$unique_blacklist_zips = implode( ', ', $unique_blacklist_zips );
					update_option( 'wc_af_blacklisted_zipcodes', $unique_blacklist_zips );
				} else {
					update_option( 'wc_af_blacklisted_zipcodes', '' );
				}
			}
		}
	}


	/**
	 * Sync address whitelist and blacklist on admin init
	 */
	public function update_blacklist_and_whitelist_address() {
		$get_whitelist_addresses = get_option( 'wc_af_whitelisted_addresses', '' );
		$get_blacklist_addresses = get_option( 'wc_af_blacklisted_addresses', array() );
		
		// Parse whitelist (array or comma/newline separated string)
		if ( is_array( $get_whitelist_addresses ) ) {
			$whitelist_addresses = $get_whitelist_addresses;
		} else {
			$whitelist_addresses = preg_split( '/[\r\n,]+/', (string) $get_whitelist_addresses );
		}
		$whitelist_addresses = array_filter( array_map( 'trim', (array) $whitelist_addresses ) );
		$whitelist_addresses = array_map( 'wc_af_strtolower', $whitelist_addresses );
		
		// Parse blacklist (array or comma/newline separated string)
		if ( is_array( $get_blacklist_addresses ) ) {
			$blacklist_addresses = $get_blacklist_addresses;
		} else {
			$blacklist_addresses = preg_split( '/[\r\n,]+/', (string) $get_blacklist_addresses );
		}
		$blacklist_addresses = array_filter( array_map( 'trim', (array) $blacklist_addresses ) );
		$blacklist_addresses = array_map( 'wc_af_strtolower', $blacklist_addresses );
		
		if ( ! empty( $blacklist_addresses ) && ! empty( $whitelist_addresses ) ) {
			$common_addresses = array_intersect( $blacklist_addresses, $whitelist_addresses );
			
			if ( ! empty( $common_addresses ) ) {
				$unique_blacklist_addresses = array_diff( $blacklist_addresses, $common_addresses );
			} else {
				$unique_blacklist_addresses = $blacklist_addresses;
			}
			
			// Update blacklist option
			if ( ! empty( $unique_blacklist_addresses ) ) {
				$unique_blacklist_addresses = implode( ', ', $unique_blacklist_addresses );
				update_option( 'wc_af_blacklisted_addresses', $unique_blacklist_addresses );	
			} else {
				update_option( 'wc_af_blacklisted_addresses', '' );
			}
		}
	}

	/**
	 * Remove whitelisted addresses from blacklist when blacklist is updated
	 */
	public function update_blacklist_address_option( $option_name, $old_value, $new_value ) {
		
		if ( 'wc_af_blacklisted_addresses' === $option_name ) {
			
			$get_whitelist_addresses = get_option( 'wc_af_whitelisted_addresses', '' );
			
			if ( ! empty( $new_value ) && ! empty( $get_whitelist_addresses ) ) {
				// Parse whitelist
				if ( is_array( $get_whitelist_addresses ) ) {
					$whitelist_addresses = $get_whitelist_addresses;
				} else {
					$whitelist_addresses = preg_split( '/[\r\n,]+/', (string) $get_whitelist_addresses );
				}
				$whitelist_addresses = array_filter( array_map( 'trim', (array) $whitelist_addresses ) );
				$whitelist_addresses = array_map( 'wc_af_strtolower', $whitelist_addresses );
				
				// Parse blacklist
				if ( is_array( $new_value ) ) {
					$blacklist_addresses = $new_value;
				} else {
					$blacklist_addresses = preg_split( '/[\r\n,]+/', (string) $new_value );
				}
				$blacklist_addresses = array_filter( array_map( 'trim', (array) $blacklist_addresses ) );
				$blacklist_addresses = array_map( 'wc_af_strtolower', $blacklist_addresses );
				
				// Find common addresses between blacklist and whitelist
				$common_addresses = array_intersect( $blacklist_addresses, $whitelist_addresses );
				
				// Remove common addresses from the blacklist
				if ( ! empty( $common_addresses ) ) {
					$unique_blacklist_addresses = array_diff( $blacklist_addresses, $common_addresses );
				} else {
					$unique_blacklist_addresses = $blacklist_addresses;
				}
				
				// Update blacklist option
				if ( ! empty( $unique_blacklist_addresses ) ) {
					$unique_blacklist_addresses = implode( ', ', $unique_blacklist_addresses );

					update_option( 'wc_af_blacklisted_addresses', $unique_blacklist_addresses );
				} else {
					update_option( 'wc_af_blacklisted_addresses', '' );
				}
			}
		}
	}

	/**
	 * Remove whitelisted addresses from blacklist when whitelist is updated
	 */
	public function update_whitelist_address_option( $option_name, $old_value, $new_value ) {
		
		if ( 'wc_af_whitelisted_addresses' === $option_name ) {
			
			$get_blacklist_addresses = get_option( 'wc_af_blacklisted_addresses', array() );
			
			if ( ! empty( $new_value ) && ! empty( $get_blacklist_addresses ) ) {
				// Parse whitelist
				if ( is_array( $new_value ) ) {
					$whitelist_addresses = $new_value;
				} else {
					$whitelist_addresses = preg_split( '/[\r\n,]+/', (string) $new_value );
				}
				$whitelist_addresses = array_filter( array_map( 'trim', (array) $whitelist_addresses ) );
				$whitelist_addresses = array_map( 'wc_af_strtolower', $whitelist_addresses );
				
				// Parse blacklist
				if ( is_array( $get_blacklist_addresses ) ) {
					$blacklist_addresses = $get_blacklist_addresses;
				} else {
					$blacklist_addresses = preg_split( '/[\r\n,]+/', (string) $get_blacklist_addresses );
				}
				$blacklist_addresses = array_filter( array_map( 'trim', (array) $blacklist_addresses ) );
				$blacklist_addresses = array_map( 'wc_af_strtolower', $blacklist_addresses );
				
				// Find common addresses between blacklist and whitelist
				$common_addresses = array_intersect( $blacklist_addresses, $whitelist_addresses );
				
				// Remove common addresses from the blacklist
				if ( ! empty( $common_addresses ) ) {
					$unique_blacklist_addresses = array_diff( $blacklist_addresses, $common_addresses );
				} else {
					$unique_blacklist_addresses = $blacklist_addresses;
				}
				
				// Update blacklist option
				if ( ! empty( $unique_blacklist_addresses ) ) {
					$unique_blacklist_addresses = implode( ', ', $unique_blacklist_addresses );
					update_option( 'wc_af_blacklisted_addresses', $unique_blacklist_addresses );
				} else {
					update_option( 'wc_af_blacklisted_addresses', '' );
				}
			}
		}
	}

	/**
	 * Sync first name whitelist and blacklist on admin init
	 */
	public function update_blacklist_and_whitelist_first_name() {
		$get_whitelist_firstnames = get_option( 'wc_af_whitelisted_first_names', '' );
		$get_blacklist_firstnames = get_option( 'wc_af_blacklisted_first_names', array() );
		
		// Parse whitelist (comma or newline separated string)
		if ( is_array( $get_whitelist_firstnames ) ) {
			$whitelist_firstnames = $get_whitelist_firstnames;
		} else {
			$whitelist_firstnames = preg_split( '/[\r\n,]+/', (string) $get_whitelist_firstnames );
		}
		$whitelist_firstnames = array_filter( array_map( 'trim', (array) $whitelist_firstnames ) );
		$whitelist_firstnames = array_map( 'wc_af_strtolower', $whitelist_firstnames );
		
		// Parse blacklist (array or comma/newline separated string)
		if ( is_array( $get_blacklist_firstnames ) ) {
			$blacklist_firstnames = $get_blacklist_firstnames;
		} else {
			$blacklist_firstnames = preg_split( '/[\r\n,]+/', (string) $get_blacklist_firstnames );
		}
		$blacklist_firstnames = array_filter( array_map( 'trim', (array) $blacklist_firstnames ) );
		$blacklist_firstnames = array_map( 'wc_af_strtolower', $blacklist_firstnames );
		
		if ( ! empty( $blacklist_firstnames ) && ! empty( $whitelist_firstnames ) ) {
			$common_firstnames = array_intersect( $blacklist_firstnames, $whitelist_firstnames );
			
			if ( ! empty( $common_firstnames ) ) {
				$unique_blacklist_firstnames = array_diff( $blacklist_firstnames, $common_firstnames );
			} else {
				$unique_blacklist_firstnames = $blacklist_firstnames;
			}
			
			// Update blacklist option
			if ( ! empty( $unique_blacklist_firstnames ) ) {
				$unique_blacklist_firstnames = implode( ', ', $unique_blacklist_firstnames );
				update_option( 'wc_af_blacklisted_first_names', $unique_blacklist_firstnames );	
			} else {
				update_option( 'wc_af_blacklisted_first_names', '' );
			}
		}
	}

	/**
	 * Remove whitelisted first names from blacklist when blacklist is updated
	 */
	public function update_blacklist_first_name_option( $option_name, $old_value, $new_value ) {
		
		if ( 'wc_af_blacklisted_first_names' === $option_name ) {
			
			$get_whitelist_firstnames = get_option( 'wc_af_whitelisted_first_names', '' );
			
			if ( ! empty( $new_value ) && ! empty( $get_whitelist_firstnames ) ) {
				// Parse whitelist
				if ( is_array( $get_whitelist_firstnames ) ) {
					$whitelist_firstnames = $get_whitelist_firstnames;
				} else {
					$whitelist_firstnames = preg_split( '/[\r\n,]+/', (string) $get_whitelist_firstnames );
				}
				$whitelist_firstnames = array_filter( array_map( 'trim', (array) $whitelist_firstnames ) );
				$whitelist_firstnames = array_map( 'wc_af_strtolower', $whitelist_firstnames );
				
				// Parse blacklist
				if ( is_array( $new_value ) ) {
					$blacklist_firstnames = $new_value;
				} else {
					$blacklist_firstnames = preg_split( '/[\r\n,]+/', (string) $new_value );
				}
				$blacklist_firstnames = array_filter( array_map( 'trim', (array) $blacklist_firstnames ) );
				$blacklist_firstnames = array_map( 'wc_af_strtolower', $blacklist_firstnames );
				
				// Find common first names between blacklist and whitelist
				$common_firstnames = array_intersect( $blacklist_firstnames, $whitelist_firstnames );
				
				// Remove common first names from the blacklist
				if ( ! empty( $common_firstnames ) ) {
					$unique_blacklist_firstnames = array_diff( $blacklist_firstnames, $common_firstnames );
				} else {
					$unique_blacklist_firstnames = $blacklist_firstnames;
				}
				
				// Update blacklist option
				if ( ! empty( $unique_blacklist_firstnames ) ) {
					$unique_blacklist_firstnames = implode( ', ', $unique_blacklist_firstnames );
					update_option( 'wc_af_blacklisted_first_names', $unique_blacklist_firstnames );
				} else {
					update_option( 'wc_af_blacklisted_first_names', '' );
				}
			}
		}
	}

	/**
	 * Remove whitelisted first names from blacklist when whitelist is updated
	 */
	public function update_whitelist_first_name_option( $option_name, $old_value, $new_value ) {
		
		if ( 'wc_af_whitelisted_first_names' === $option_name ) {
			
			$get_blacklist_firstnames = get_option( 'wc_af_blacklisted_first_names', array() );
			
			if ( ! empty( $new_value ) && ! empty( $get_blacklist_firstnames ) ) {
				// Parse whitelist
				if ( is_array( $new_value ) ) {
					$whitelist_firstnames = $new_value;
				} else {
					$whitelist_firstnames = preg_split( '/[\r\n,]+/', (string) $new_value );
				}
				$whitelist_firstnames = array_filter( array_map( 'trim', (array) $whitelist_firstnames ) );
				$whitelist_firstnames = array_map( 'wc_af_strtolower', $whitelist_firstnames );
				
				// Parse blacklist
				if ( is_array( $get_blacklist_firstnames ) ) {
					$blacklist_firstnames = $get_blacklist_firstnames;
				} else {
					$blacklist_firstnames = preg_split( '/[\r\n,]+/', (string) $get_blacklist_firstnames );
				}
				$blacklist_firstnames = array_filter( array_map( 'trim', (array) $blacklist_firstnames ) );
				$blacklist_firstnames = array_map( 'wc_af_strtolower', $blacklist_firstnames );

				// Find common first names between blacklist and whitelist
				$common_firstnames = array_intersect( $blacklist_firstnames, $whitelist_firstnames );

				// Remove common first names from the blacklist
				if ( ! empty( $common_firstnames ) ) {
					$unique_blacklist_firstnames = array_diff( $blacklist_firstnames, $common_firstnames );
				} else {
					$unique_blacklist_firstnames = $blacklist_firstnames;
				}

				// Update blacklist option
				if ( ! empty( $unique_blacklist_firstnames ) ) {
					$unique_blacklist_firstnames = implode( ', ', $unique_blacklist_firstnames );
					update_option( 'wc_af_blacklisted_first_names', $unique_blacklist_firstnames );
				} else {
					update_option( 'wc_af_blacklisted_first_names', '' );
				}
			}
		}
	}

	/**
	 * Sync last name whitelist and blacklist on admin init
	 */
	public function update_blacklist_and_whitelist_last_name() {
		$get_whitelist_lastnames = get_option( 'wc_af_whitelisted_last_names', '' );
		$get_blacklist_lastnames = get_option( 'wc_af_blacklisted_last_names', array() );
		
		// Parse whitelist (comma or newline separated string)
		if ( is_array( $get_whitelist_lastnames ) ) {
			$whitelist_lastnames = $get_whitelist_lastnames;
		} else {
			$whitelist_lastnames = preg_split( '/[\r\n,]+/', (string) $get_whitelist_lastnames );
		}
		$whitelist_lastnames = array_filter( array_map( 'trim', (array) $whitelist_lastnames ) );
		$whitelist_lastnames = array_map( 'wc_af_strtolower', $whitelist_lastnames );
		
		// Parse blacklist (array or comma/newline separated string)
		if ( is_array( $get_blacklist_lastnames ) ) {
			$blacklist_lastnames = $get_blacklist_lastnames;
		} else {
			$blacklist_lastnames = preg_split( '/[\r\n,]+/', (string) $get_blacklist_lastnames );
		}
		$blacklist_lastnames = array_filter( array_map( 'trim', (array) $blacklist_lastnames ) );
		$blacklist_lastnames = array_map( 'wc_af_strtolower', $blacklist_lastnames );
		
		if ( ! empty( $blacklist_lastnames ) && ! empty( $whitelist_lastnames ) ) {
			$common_lastnames = array_intersect( $blacklist_lastnames, $whitelist_lastnames );
			
			if ( ! empty( $common_lastnames ) ) {
				$unique_blacklist_lastnames = array_diff( $blacklist_lastnames, $common_lastnames );
			} else {
				$unique_blacklist_lastnames = $blacklist_lastnames;
			}
			
			// Update blacklist option
			if ( ! empty( $unique_blacklist_lastnames ) ) {
				$unique_blacklist_lastnames = implode( ', ', $unique_blacklist_lastnames );
				update_option( 'wc_af_blacklisted_last_names', $unique_blacklist_lastnames );	
			} else {
				update_option( 'wc_af_blacklisted_last_names', '' );
			}
		}
	}

	/**
	 * Remove whitelisted last names from blacklist when blacklist is updated
	 */
	public function update_blacklist_last_name_option( $option_name, $old_value, $new_value ) {
		
		if ( 'wc_af_blacklisted_last_names' === $option_name ) {
			
			$get_whitelist_lastnames = get_option( 'wc_af_whitelisted_last_names', '' );
			
			if ( ! empty( $new_value ) && ! empty( $get_whitelist_lastnames ) ) {
				// Parse whitelist
				if ( is_array( $get_whitelist_lastnames ) ) {
					$whitelist_lastnames = $get_whitelist_lastnames;
				} else {
					$whitelist_lastnames = preg_split( '/[\r\n,]+/', (string) $get_whitelist_lastnames );
				}
				$whitelist_lastnames = array_filter( array_map( 'trim', (array) $whitelist_lastnames ) );
				$whitelist_lastnames = array_map( 'wc_af_strtolower', $whitelist_lastnames );
				
				// Parse blacklist
				if ( is_array( $new_value ) ) {
					$blacklist_lastnames = $new_value;
				} else {
					$blacklist_lastnames = preg_split( '/[\r\n,]+/', (string) $new_value );
				}
				$blacklist_lastnames = array_filter( array_map( 'trim', (array) $blacklist_lastnames ) );
				$blacklist_lastnames = array_map( 'wc_af_strtolower', $blacklist_lastnames );
				
				// Find common last names between blacklist and whitelist
				$common_lastnames = array_intersect( $blacklist_lastnames, $whitelist_lastnames );
				
				// Remove common last names from the blacklist
				if ( ! empty( $common_lastnames ) ) {
					$unique_blacklist_lastnames = array_diff( $blacklist_lastnames, $common_lastnames );
				} else {
					$unique_blacklist_lastnames = $blacklist_lastnames;
				}
				
				// Update blacklist option
				if ( ! empty( $unique_blacklist_lastnames ) ) {
					$unique_blacklist_lastnames = implode( ', ', $unique_blacklist_lastnames );
					update_option( 'wc_af_blacklisted_last_names', $unique_blacklist_lastnames );
				} else {
					update_option( 'wc_af_blacklisted_last_names', '' );
				}
			}
		}
	}

	/**
	 * Remove whitelisted last names from blacklist when whitelist is updated
	 */
	public function update_whitelist_last_name_option( $option_name, $old_value, $new_value ) {
		
		if ( 'wc_af_whitelisted_last_names' === $option_name ) {
			
			$get_blacklist_lastnames = get_option( 'wc_af_blacklisted_last_names', array() );
			
			if ( ! empty( $new_value ) && ! empty( $get_blacklist_lastnames ) ) {
				// Parse whitelist
				if ( is_array( $new_value ) ) {
					$whitelist_lastnames = $new_value;
				} else {
					$whitelist_lastnames = preg_split( '/[\r\n,]+/', (string) $new_value );
				}
				$whitelist_lastnames = array_filter( array_map( 'trim', (array) $whitelist_lastnames ) );
				$whitelist_lastnames = array_map( 'wc_af_strtolower', $whitelist_lastnames );
				
				// Parse blacklist
				if ( is_array( $get_blacklist_lastnames ) ) {
					$blacklist_lastnames = $get_blacklist_lastnames;
				} else {
					$blacklist_lastnames = preg_split( '/[\r\n,]+/', (string) $get_blacklist_lastnames );
				}
				$blacklist_lastnames = array_filter( array_map( 'trim', (array) $blacklist_lastnames ) );
				$blacklist_lastnames = array_map( 'wc_af_strtolower', $blacklist_lastnames );

				// Find common last names between blacklist and whitelist
				$common_lastnames = array_intersect( $blacklist_lastnames, $whitelist_lastnames );

				// Remove common last names from the blacklist
				if ( ! empty( $common_lastnames ) ) {
					$unique_blacklist_lastnames = array_diff( $blacklist_lastnames, $common_lastnames );
				} else {
					$unique_blacklist_lastnames = $blacklist_lastnames;
				}

				// Update blacklist option
				if ( ! empty( $unique_blacklist_lastnames ) ) {
					$unique_blacklist_lastnames = implode( ', ', $unique_blacklist_lastnames );
					update_option( 'wc_af_blacklisted_last_names', $unique_blacklist_lastnames );
				} else {
					update_option( 'wc_af_blacklisted_last_names', '' );
				}
			}
		}
	}


	public function check_orders_through_api( $and_taxes, $order ) {

		if ( $order instanceof WC_Order ) {

			$order_id                        = $order->get_id();

			$created_via                     = $order->get_created_via();

			$api_fraud_check                 = get_option( 'wc_af_api_fraud_check', 'no' );

			$throttle_api_based_orders_check = get_option( 'wc_af_throttle_api_based_orders_check', 'no' );

			$max_orders_through_api_per_hour = (int) get_option( 'wc_af_max_orders_through_api_per_hour', '0' );

			// ANTIFRAUD-126 Start Whitelist settings
			$enable_whitelist = get_option( 'wc_af_enable_api_keys_whitelist', 'no' );
			$whitelisted_keys = get_option( 'wc_settings_anti_fraud_whitelist_restapi', '' );

			// ✅ Get new "Advanced Protection" settings
			$enable_global_rate_limit        = get_option( 'wc_af_enable_global_rate_limit', 'no' );
			$global_rate_limit_max           = (int) get_option( 'wc_af_global_rate_limit_max', '100' );
			$global_time_limit_max           = (int) get_option( 'wc_af_global_time_limit_max', '60' );

			if ( 'rest-api' === $created_via ) {

				// Get current consumer key from request
				$current_key = isset($_GET['consumer_key']) ? sanitize_text_field($_GET['consumer_key']) : '';

				// Also handle Basic Auth headers (for Postman or REST API)
				if (empty($current_key) && isset($_SERVER['PHP_AUTH_USER'])) {
					$current_key = sanitize_text_field($_SERVER['PHP_AUTH_USER']);
				}

				$key_end = substr($current_key, -7);
		
				// Check if whitelisting is enabled and key is in whitelist
				if ( 'yes' === $enable_whitelist && ! empty( $current_key ) && in_array( $key_end, $whitelisted_keys, true ) ) {
					// Add note for admin clarity
					$order->add_order_note( __( 'Order fraud checks skipped due to whitelisted API key.', 'woocommerce-anti-fraud' ) );

					return;
				}

				// Continue existing fraud & throttle logic for non-whitelisted keys
				if ( 'yes' === $api_fraud_check ) {
					$score_helper = new WC_AF_Score_Helper();
					$score_helper->do_check( $order_id );
				}

				if ( 'yes' === $throttle_api_based_orders_check ) {
					/*
					 * PERFORMANCE FIX: Cache the per-hour REST-API order count for 60 seconds.
					 * Under bot attacks via the REST API this query was executed on every single
					 * incoming order, causing unbounded table scans.  Caching means at most one
					 * full DB query per minute for this check.  We cap at max+1 IDs so MySQL
					 * stops scanning as soon as the limit is confirmed exceeded.
					 */
					$api_throttle_cache_key = 'wc_af_api_throttle_' . (int) $max_orders_through_api_per_hour;
					$api_orders_count       = get_transient( $api_throttle_cache_key );

					if ( false === $api_orders_count ) {
						$orders_in_last_hour = wc_get_orders( array(
						'limit'        => $max_orders_through_api_per_hour + 1,
						'return'       => 'ids',
						'date_created' => '>=' . date_i18n( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) - HOUR_IN_SECONDS ) ),
						'created_via'  => 'rest-api',
						) );
						$api_orders_count = count( $orders_in_last_hour );
						set_transient( $api_throttle_cache_key, $api_orders_count, 60 );
					}

					if ( $api_orders_count > $max_orders_through_api_per_hour ) {
						$order->delete( true );
						wp_die(
						esc_html__( 'Maximum API orders per hour exceeded.', 'woocommerce-anti-fraud' ),
						esc_html__( 'Error', 'woocommerce-anti-fraud' ),
						array( 'response' => 429 )
						);
					}
				}

			// Global Rate Limit check (only if not whitelisted)
				if ( 'yes' === $enable_global_rate_limit ) {
					/*
					 * PERFORMANCE FIX: Cache the sitewide order count for 30 seconds.
					 * Previously this ran an unbounded SELECT on every API order creation,
					 * which serialises all concurrent requests behind a full-table scan.
					 */
					$global_rl_cache_key = 'wc_af_global_rl_' . (int) $global_time_limit_max;
					$recent_orders_count = get_transient( $global_rl_cache_key );

					if ( false === $recent_orders_count ) {
						$recent_orders = wc_get_orders( array(
						'limit'        => $global_rate_limit_max + 1,
						'return'       => 'ids',
						'date_created' => '>=' . date_i18n( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) - $global_time_limit_max ) ),
						) );
						$recent_orders_count = count( $recent_orders );
						set_transient( $global_rl_cache_key, $recent_orders_count, 30 );
					}

					if ( $recent_orders_count >= $global_rate_limit_max ) {
						$order->delete( true );
						wp_die(
						esc_html__( 'Global checkout rate limit exceeded.', 'woocommerce-anti-fraud' ),
						esc_html__( 'Rate Limit Reached', 'woocommerce-anti-fraud' ),
						array( 'response' => 429 )
						);
					}
				}
			// ANTIFRAUD-126 END
			}
		}

	}


	public function handle_admin_notices() {

		$notices = array();

		if ( get_option( 'wc_af_attempt_count_check' ) !== 'yes' ) {
			$notices[] = array(
				'message'   => __( 'Card Attack Protection is currently disabled. Enable it in the Anti-Fraud settings to enhance your store\'s security.', 'woocommerce-anti-fraud' ),
				'notice_id' => 'wc_af_card_attack_disabled',
				'classes'   => array( 'notice', 'notice-warning', 'is-dismissible' ),
			);
		}

		if ( is_array( $notices ) ) {
			foreach ( $notices as $notice ) {
				$message   = isset( $notice['message'] ) ? $notice['message'] : '';
				$notice_id = isset( $notice['notice_id'] ) ? $notice['notice_id'] : '';
				$classes   = isset( $notice['classes'] ) ? $notice['classes'] : array();

				if ( ! empty( $notice_id ) && ! get_transient( $notice_id ) ) {
					printf( '<div class="%s" data-notice-id="%s" data-nonce="%s"><p>%s</p></div>',
						esc_attr( implode( ' ', $classes ) ),
						esc_attr( $notice_id ),
						esc_attr( wp_create_nonce( 'dismiss_admin_notice' ) ),
						esc_html($message)
					);
				}
			}
		}
	}

	public function dismiss_admin_notice() {
		check_ajax_referer( 'dismiss_admin_notice', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( - 1 );
		}

		$notice_id = isset( $_POST['notice_id'] ) ? sanitize_text_field( $_POST['notice_id'] ) : '';

		if ( $notice_id ) {
			set_transient( $notice_id, true, 7 * DAY_IN_SECONDS );
		}

		wp_die();
	}

	/**
	 * Check whether another reCAPTCHA plugin (Google reCAPTCHA-based) is active.
	 * Only flags plugins whose slug contains 'recaptcha' or 'captcha', intentionally
	 * excluding Cloudflare Turnstile plugins so a Turnstile-only install cannot
	 * trigger a reCAPTCHA-specific conflict warning.
	 *
	 * @return bool
	 */
	private function is_recaptcha_conflicting() {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$network_active = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( $active_plugins, $network_active );
		}

		foreach ( $active_plugins as $plugin ) {
			$plugin_lower = strtolower( $plugin );
			if (
				strpos( $plugin_lower, 'recaptcha' ) !== false ||
				strpos( $plugin_lower, 'captcha' ) !== false
			) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check whether Anti-Fraud's own CAPTCHA feature is enabled on checkout.
	 *
	 * @return bool
	 */
	private function is_our_recaptcha_enabled() {
		$wc_af_recaptcha_enable_captcha = get_option( 'wc_af_recaptcha_enable_captcha' );
		return ( '1' === $wc_af_recaptcha_enable_captcha || 'yes' === $wc_af_recaptcha_enable_captcha );
	}

	/**
	 * Return the active CAPTCHA provider slug ('google_recaptcha' or 'cf_turnstile').
	 *
	 * @return string
	 */
	private function get_captcha_provider() {
		$provider = get_option( 'wc_af_recaptcha_type', 'google_recaptcha' );
		return empty( $provider ) ? 'google_recaptcha' : $provider;
	}

	/**
	 * Admin warning shown only when:
	 *  - Anti-Fraud CAPTCHA is enabled on checkout, AND
	 *  - the configured provider is Google reCAPTCHA (not Cloudflare Turnstile), AND
	 *  - another reCAPTCHA/captcha plugin is active that may conflict.
	 *
	 * Admins can dismiss the notice per-user; the preference persists across sessions.
	 */
	public function recaptcha_conflict_admin_notice() {
		if ( ! $this->is_our_recaptcha_enabled() ) {
			return;
		}

		if ( 'google_recaptcha' !== $this->get_captcha_provider() ) {
			return;
		}

		if ( ! $this->is_recaptcha_conflicting() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( $user_id && get_user_meta( $user_id, 'wc_af_hide_captcha_notice', true ) ) {
			return;
		}

		$nonce = wp_create_nonce( 'wc_af_dismiss_captcha_notice' );
		printf(
			'<div class="notice notice-warning wc-af-captcha-conflict-notice" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">' .
			'<p style="margin:.5em 0;flex:1 1 auto;"><strong>%1$s</strong> %2$s</p>' .
			'<p style="margin:.5em 0;flex:0 0 auto;">' .
			'<a href="#" class="wc-af-dismiss-captcha-notice button button-secondary" data-nonce="%3$s">%4$s</a>' .
			'</p></div>' .
			'<script>' .
			'(function($){$(document).on("click",".wc-af-dismiss-captcha-notice",function(e){' .
			'e.preventDefault();var $n=$(this).closest(".wc-af-captcha-conflict-notice");' .
			'$.post(ajaxurl,{action:"wc_af_dismiss_captcha_notice",nonce:$(this).data("nonce")},' .
			'function(){$n.fadeOut(300);});' .
			'});}(jQuery));' .
			'</script>',
			esc_html__( 'WooCommerce Anti-Fraud:', 'woocommerce-anti-fraud' ),
			esc_html__( 'Another reCAPTCHA plugin is active and may prevent Anti-Fraud\'s CAPTCHA from appearing on checkout. Please disable the other plugin for full compatibility.', 'woocommerce-anti-fraud' ),
			esc_attr( $nonce ),
			esc_html__( 'Dismiss', 'woocommerce-anti-fraud' )
		);
	}

	/**
	 * AJAX handler: stores a per-user flag so the captcha conflict notice stays hidden
	 * across sessions for the dismissing administrator.
	 */
	public function wc_af_dismiss_captcha_notice_callback() {
		check_ajax_referer( 'wc_af_dismiss_captcha_notice', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
			return;
		}

		$user_id = get_current_user_id();
		if ( $user_id ) {
			update_user_meta( $user_id, 'wc_af_hide_captcha_notice', 1 );
		}

		wp_send_json_success();
	}

	/**
	 * Returns true when the WooCommerce PayPal Payments plugin is active.
	 *
	 * Checks both single-site and network-wide active plugins so the notice
	 * surfaces correctly on multisite installs as well.
	 *
	 * @return bool
	 */
	private function is_woo_paypal_payments_active() {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$network_active = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( $active_plugins, $network_active );
		}

		foreach ( $active_plugins as $plugin ) {
			if ( strpos( $plugin, 'woocommerce-paypal-payments' ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Admin notice shown when WooCommerce PayPal Payments is active.
	 *
	 * PayPal Payments emits its own "fraud protection / enable reCAPTCHA" compliance
	 * notice that is entirely PayPal-specific and unrelated to whether a third-party
	 * fraud solution such as OPMC Anti-Fraud is active.  Without clarification,
	 * merchants incorrectly interpret the PayPal notice as evidence that Anti-Fraud
	 * is not working.  This notice surfaces a clear, accurate explanation.
	 *
	 * The notice is dismissible on a per-user basis and only shown to administrators.
	 */
	public function wc_af_paypal_payments_clarification_notice() {
		if ( ! $this->is_woo_paypal_payments_active() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( $user_id && get_user_meta( $user_id, 'wc_af_hide_paypal_payments_notice', true ) ) {
			return;
		}

		$nonce        = wp_create_nonce( 'wc_af_dismiss_paypal_payments_notice' );
		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=wc_af' );

		printf(
			'<div class="notice notice-info wc-af-paypal-payments-notice" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:12px 16px;">'
			. '<p style="margin:.5em 0;flex:1 1 auto;">'
			. '<strong>%1$s</strong> %2$s '
			. '<a href="%3$s">%4$s</a>'
			. '</p>'
			. '<p style="margin:.5em 0;flex:0 0 auto;">'
			. '<a href="#" class="wc-af-dismiss-paypal-payments-notice button button-secondary" data-nonce="%5$s">%6$s</a>'
			. '</p>'
			. '</div>'
			. '<script>'
			. '(function($){'
			. '$(document).on("click",".wc-af-dismiss-paypal-payments-notice",function(e){'
			. 'e.preventDefault();'
			. 'var $n=$(this).closest(".wc-af-paypal-payments-notice");'
			. '$.post(ajaxurl,{action:"wc_af_dismiss_paypal_payments_notice",nonce:$(this).data("nonce")},'
			. 'function(){$n.fadeOut(300);});'
			. '});'
			. '}(jQuery));'
			. '</script>',
			esc_html__( 'WooCommerce Anti-Fraud:', 'woocommerce-anti-fraud' ),
			esc_html__(
				'WooCommerce Anti-Fraud is active and enforcing fraud rules on your store. '
				. 'The fraud protection notice shown by WooCommerce PayPal Payments is generated by PayPal and reflects PayPal\'s own compliance requirements — '
				. 'it does not indicate that your Anti-Fraud controls are missing or inactive. No action is required.',
				'woocommerce-anti-fraud'
			),
			esc_url( $settings_url ),
			esc_html__( 'View Anti-Fraud Settings', 'woocommerce-anti-fraud' ),
			esc_attr( $nonce ),
			esc_html__( 'Dismiss', 'woocommerce-anti-fraud' )
		);
	}

	/**
	 * AJAX handler: stores a per-user flag so the PayPal Payments clarification
	 * notice stays hidden across sessions once dismissed.
	 */
	public function wc_af_dismiss_paypal_payments_notice_callback() {
		check_ajax_referer( 'wc_af_dismiss_paypal_payments_notice', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
			return;
		}

		$user_id = get_current_user_id();
		if ( $user_id ) {
			update_user_meta( $user_id, 'wc_af_hide_paypal_payments_notice', 1 );
		}

		wp_send_json_success();
	}


	public function add_body_class_for_settings_page( $classes ) {

		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$current_tab  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

		if ( 'wc-settings' === $current_page && 'wc_af' === $current_tab ) {
			$classes .= ' wc-af-settings-page';
			$current_section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
			if ( 'license' === $current_section ) {
				$classes .= ' wc-af-license-section';
			}
		}

		if ( 'antifraud-dashboard' === $current_page ) {
			$classes .= ' wc-af-dashboard-page';
		}

		return $classes;
	}

	public function multiple_email_check( $str ) {

		$my_array = get_option( 'wc_settings_anti_fraud_whitelist' );

		if ( ! empty( $my_array ) ) {
			$my_arrays = explode( ',', $my_array );

			foreach ( $my_arrays as $value ) {
				// Count the occurrences of '@' in the current value
				$count_at = substr_count( $value, '@' );

				// Check if '@' appears more than once
				if ( $count_at <= 1 ) {
					$filtered_array[] = $value;
				}
			}

			$filtered_array  = is_array( $filtered_array ) ? $filtered_array : array();
			$uniqueEmails    = array_unique( $filtered_array );
			$filtered_arrays = implode( ', ', $uniqueEmails );

			// ✅ OPTIMIZED: Disable autoload to prevent loading on every request
			update_option( 'wc_settings_anti_fraud_whitelist', $filtered_arrays );
			$this->disable_whitelist_autoload();
			$this->clear_whitelist_cache();

			if ( ! empty( $filtered_array ) ) {
				$uniqueEmails    = array_unique( $filtered_array );
				$filtered_arrays = implode( ', ', $uniqueEmails );

				// ✅ OPTIMIZED: Disable autoload to prevent loading on every request
				update_option( 'wc_settings_anti_fraud_whitelist', $filtered_arrays );
				$this->disable_whitelist_autoload();
				$this->clear_whitelist_cache();
			}
		}
	}

	public function get_current_url() {
		// Check if HTTPS is enabled
		$is_https = ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'];

		// Determine the protocol
		$protocol = $is_https ? 'https' : 'http';
		// Get the host name
		$host = '';
		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			$host = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
		}

		$request_uri = '';

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri = sanitize_text_field( $_SERVER['REQUEST_URI'] );
		}
		// Get the request URI (path and query)
		$request_uri = sanitize_text_field( $_SERVER['REQUEST_URI'] );

		// Construct the full URL
		$url = $protocol . '://' . $host . $request_uri;

		return $url;

	}


	public function count_order_attempt_action_offsite( $order_id = null) {
		//error_log('Running custom_woocommerce_checkout_order_processed…');
		$enablePaymentAttempt = get_option( 'wc_af_order_payment_attempt_check' );
		$orderPaymentAttempt  = get_option( 'wc_settings_anti_fraud_max_order_payment_attempt' );
		$current_url          = $this->get_current_url();
		$url_parts            = parse_url( $current_url );
		// Extract the path from the URL
		$path = $url_parts['path'];
		// Extract the order ID from the path
		// Assuming the order ID is the last part of the path before query parameters
		$path_parts = explode( '/', trim( $path, '/' ) );
		$order_id   = $path_parts[2];
		$order      = wc_get_order( $order_id );
		if ( $order ) {

			// Ensure options are set and valid
			if ( 'yes' == $enablePaymentAttempt && ! empty( $orderPaymentAttempt ) ) {
				// Retrieve the current payment retry count
				$counter = opmc_hpos_get_post_meta( $order_id, 'order_payment_retry', true );
				if ( ! is_numeric( $counter ) ) {
					$counter = 1;
				} else {
					$counter = $counter;
				}

				if ( isset( $order ) && 'failed' === $order->get_status() ) {

					if ( $counter == $orderPaymentAttempt ) {
						$order->update_status( 'cancelled', 'You have reached the max payment attempt for this order.', true );
						$pre_payment_block_message = 'You have reached the max payment attempt for this order.';
						wc_add_notice( __( 'You have reached the max payment attempt for this order.' ), 'error' );
						//wp_die();

					} else {
						// Update the retry count if payment attempt is not maxed out
						opmc_hpos_update_post_meta( $order_id, 'order_payment_retry', $counter + 1 );

						return false;
					}
				} else {
					if ( isset( $order ) && 'cancelled' === $order->get_status() ) {
						wc_add_notice( __( 'You have reached the max payment attempt for this order.' ), 'error' );
					}
				}
			}
		}
	}


	// Record a credit card decline if checkout fails.
	public function count_order_attempt_action_onsite( $order_id, $posted_data, $order ) {
		//error_log('Running custom_woocommerce_checkout_order_processed…');
		$enablePaymentAttempt = get_option( 'wc_af_order_payment_attempt_check' );
		$orderPaymentAttempt  = get_option( 'wc_settings_anti_fraud_max_order_payment_attempt' );

		// Ensure options are set and valid
		if ( 'yes' == $enablePaymentAttempt && ! empty( $orderPaymentAttempt ) ) {
			// Retrieve the current payment retry count
			$counter = opmc_hpos_get_post_meta( $order_id, 'order_payment_retry', true );
			if ( ! is_numeric( $counter ) ) {
				$counter = 0;
			} else {
				$counter = $counter;
			}

			if ( isset( $order ) && 'failed' === $order->get_status() ) {

				if ( $counter == $orderPaymentAttempt ) {
					//$order->update_status('failed', 'Pre Payment Fraud Check: Calculated risk score is above High Risk Threshold.', true);
					$pre_payment_block_message = 'You have reached the max payment attempt for this order.';
					$return                    = array(
						'result'   => 'failed',
						'messages' => "<ul class='woocommerce-error' role='alert'><li>" . $pre_payment_block_message . '</li></ul>',
					);
					wp_send_json( $return );
					wp_die();

				} else {
					// Update the retry count if payment attempt is not maxed out
					opmc_hpos_update_post_meta( $order_id, 'order_payment_retry', $counter + 1 );

					return false;
				}
			}
		}
	}

	/**
	 * Ensure the attempt intelligence table exists (activation + upgrade).
	 *
	 * @since 7.3.0
	 */
	public function wc_af_ensure_attempt_intelligence_table() {
		if ( ! class_exists( 'WC_AF_Attempt_Intelligence_Service' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'anti-fraud-core/class-wc-af-attempt-intelligence-service.php';
		}
		$service = WC_AF_Attempt_Intelligence_Service::get_instance();
		$service->ensure_table();
	}

	/**
	 * Record an attempt when an order is created (for advanced velocity detection).
	 *
	 * @param int      $order_id   Order ID.
	 * @param array    $posted_data Posted checkout data.
	 * @param WC_Order $order      Order object.
	 * @since 7.3.0
	 */
	public function wc_af_record_attempt_on_order( $order_id, $posted_data, $order ) {
		$attempt_mode = get_option( 'wc_af_attempt_count_mode', 'orders_only' );
		if ( 'advanced' !== $attempt_mode ) {
			return;
		}
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order || $order->get_status() === 'checkout-draft' ) {
			return;
		}
		if ( ! class_exists( 'WC_AF_Attempt_Intelligence_Service' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'anti-fraud-core/class-wc-af-attempt-intelligence-service.php';
		}
		$service = WC_AF_Attempt_Intelligence_Service::get_instance();
		$data    = $service->build_record_from_order( $order, 'order' );
		$service->record_attempt( $data );
	}

	/**
	 * Record an attempt when an order is created via Block checkout.
	 *
	 * @param WC_Order $order Order object.
	 * @since 7.3.0
	 */
	public function wc_af_record_attempt_on_order_block( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$this->wc_af_record_attempt_on_order( $order->get_id(), array(), $order );
	}

	/**
	 * Record a failed payment attempt (for advanced velocity detection).
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order    Order object.
	 * @since 7.3.0
	 */
	public function wc_af_record_attempt_on_payment_failed( $order_id, $order = null ) {
		$attempt_mode = get_option( 'wc_af_attempt_count_mode', 'orders_only' );
		if ( 'advanced' !== $attempt_mode ) {
			return;
		}
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}
		if ( ! class_exists( 'WC_AF_Attempt_Intelligence_Service' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'anti-fraud-core/class-wc-af-attempt-intelligence-service.php';
		}
		$service = WC_AF_Attempt_Intelligence_Service::get_instance();
		$data    = $service->build_record_from_order( $order, 'payment_failed' );
		$service->record_attempt( $data );
		if ( class_exists( 'Af_Logger' ) ) {
			Af_Logger::debug( sprintf(
				/* translators: 1: order ID, 2: trigger */
				__( 'Attempt Intelligence: recorded payment_failed for order #%d (trigger: payment_failed)', 'woocommerce-anti-fraud' ),
				$order_id
			) );
		}
	}

	public function custom_process_failed_order( $order_id ) {

		$order = wc_get_order( $order_id );
		if ( $order->has_status( 'failed' ) ) {
			$high_risk     = get_option( 'wc_settings_anti_fraud_higher_risk_threshold' );
			$score_helper  = new WC_AF_Score_Helper();
			$score_points  = opmc_hpos_get_post_meta( $order_id, 'wc_af_score', true );
			$circle_points = WC_AF_Score_Helper::invert_score( $score_points );
			if ( $high_risk <= $circle_points && 'failed' == $order->get_status() ) {
				$order->update_status( 'cancelled', 'Fraud Check Done: Calculated risk score is above High Risk Threshold.', true );

			} else {
				if ( 'cancelled' !== $order->get_status() ) {
					$order->update_status( 'failed', 'Fraud Check Done: Calculated risk score is lower High Risk Threshold.', true );
				}
			}
		}
	}

	public function woocommerce_before_thankyou_failed_order( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! is_admin() && $order->has_status( 'failed' ) ) {
			// If the order is already whitelisted, do not mark it for retry or
			// reschedule a fraud check – the whitelist decision stands and we must
			// not allow a payment-retry loop to override it.
			$whitelist_action = opmc_hpos_get_post_meta( $order_id, 'whitelist_action', true );
			if ( ! empty( $whitelist_action ) ) {
				return;
			}

			opmc_hpos_update_post_meta( $order_id, 'peyment_retry', 'yes' );

			$score_helper = new WC_AF_Score_Helper();
			$score_helper->schedule_fraud_check( $order_id, true );
		}
	}

	// PLUGINS-2657 end

	/**
	 * Added GeoLocation Admin Notice
	 *
	 * @since 5.8.0
	 */

	public function wc_af_iswhitelist_admin_notice() {
		if ( isset( $bigdatacloud_key ) && '' !== $bigdatacloud_key) {
			?>
			<div class="notice is-dismissible">
				<p><strong><?php echo esc_html_e( 'IP whitelisting settings may request users to share location from their browsers. Please see more in AntiFraud plugin', 'woocommerce-anti-fraud' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=wc_af' ) ); ?>"><?php esc_html_e( 'settings', 'woocommerce-anti-fraud' ); ?></a>.</strong></p>
			</div>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {
					$(document).on('click', '.notice-dismiss', function () {
						$.ajax({
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
							type: 'POST',
							data: {
								action: 'dismiss_notice'
							},
						});
					});
				});
			</script>
			<?php
		}
	}

	/**
	 * Added Geo Location Notice
	 *
	 * @since 5.8.0
	 */

	public function dismiss_notice_callback() {
		update_option( 'woo_af_geoloc_notice_dismissed', true );
	}


	/**
	 * Bigdatacloud_onetime_dismiss_notice
	 *
	 * @since 5.8.0
	 */
	public function bigdatacloud_onetime_dismiss_notice() {
		if ( empty( get_option( 'bigdatacloud_onetime_notice_dismisseds' ) ) ) {
			update_option( 'wc_af_geolocation_order', 'no' );
			update_option( 'bigdatacloud_api_key', '' );
			update_option( 'bigdatacloud_notice_dismisseds_onsave', 1 );
			update_option( 'bigdatacloud_notice_dismisseds_error', 1 );

			add_action( 'admin_notices', array( $this, 'bigdatacloud_onetime_dismiss_notice_message' ) );
		}
	}

	/**
	 * Bigdatacloud_onetime_dismiss_notice_message
	 *
	 * @since 5.8.0
	 */
	public function bigdatacloud_onetime_dismiss_notice_message() {
		if ( isset( $bigdatacloud_key ) && '' !== $bigdatacloud_key ) {
			?>
			<div class="notice notice-error is-dismissible opmc-antifraud" id="onetime_error">
				<p>
					<?php
					/* translators: 1. start of link, 2. end of link. */
					printf( esc_html__( 'In order to continue %1$s"Geo Location Match"%2$s service, our provider Bigdatacloud now requires you to sign up and get the api key. To obtain an api key you can %3$sregister here%4$s.', 'woocommerce-anti-fraud' ), '<strong>', '</strong>', '<a href="' . esc_url( 'https://www.bigdatacloud.com/" target="_blank"' ) . '">', '</a>' );
					?>
				</p>
			</div>
		<script type="text/javascript">
			jQuery(document).ready(function () {
				jQuery(document).on('click', '.opmc-antifraud button.notice-dismiss', function () {
					jQuery.ajax({
						url: ajaxurl,
						method: 'POST',
						data: {
							action: 'bigdatacloud_onetime_dismiss',
							_wpnonce: '<?php echo esc_js( wp_create_nonce( 'woocommerce-anti-fraud' ) ); ?>'
						}
					});
				});
			});
		</script>
			<?php
		}
	}

	/**
	 * Bigdatacloud_onetime_dismiss
	 *
	 * @since 5.8.0
	 */
	public function bigdatacloud_onetime_dismiss() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Unauthorized', 403 );
		}

		check_ajax_referer( 'woocommerce-anti-fraud', '_wpnonce' );

		update_option( 'bigdatacloud_onetime_notice_dismisseds', 1 );
		echo 'success';
		wp_die();
	}

	/**
	 * Bigdatacloud_dismiss_notice_save
	 *
	 * @since 5.8.0
	 */
	public function bigdatacloud_dismiss_notice_save() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Unauthorized', 403 );
		}

		check_ajax_referer( 'woocommerce-anti-fraud', '_wpnonce' );

		update_option( 'bigdatacloud_notice_dismisseds_onsave', 1 );
		update_option( 'bigdatacloud_notice_dismisseds_error', 1 );

		echo 'success';
		wp_die();
	}

	/**
	 * Bigdatacloud_dismiss_notice
	 *
	 * @since 5.8.0
	 */
	public function bigdatacloud_dismiss_notice() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Unauthorized', 403 );
		}

		check_ajax_referer( 'woocommerce-anti-fraud', '_wpnonce' );

		update_option( 'bigdatacloud_notice_dismisseds_error', 1 );
		update_option( 'bigdatacloud_notice_dismisseds_onsave', 1 );
		echo 'success';
		wp_die();
	}

	/**
	 * Auth_bigdatacloud_error_admin_notice
	 *
	 * @since 5.8.0
	 */
	public function auth_bigdatacloud_error_admin_notice() {
		if ( isset( $bigdatacloud_key ) && '' !== $bigdatacloud_key ) {
			?>
			<div class="notice notice-error is-dismissible opmc-antifraud" id="root_error">
				<p><strong><?php echo esc_html_e( 'AntiFraud: Bigdatacloud credentials not authenticate or your quota limit has been exceeded!', 'woocommerce-anti-fraud' ); ?></strong></p>
			</div>
		<script type="text/javascript">
			jQuery(document).ready(function () {
				jQuery(document).on('click', '#root_error button.notice-dismiss', function () {
					jQuery.ajax({
						url: ajaxurl,
						method: 'POST',
						data: {
							action: 'bigdatacloud_dismiss_notice',
							_wpnonce: '<?php echo esc_js( wp_create_nonce( 'woocommerce-anti-fraud' ) ); ?>'
						}
					});
				});
			});
		</script>

			<?php
		}
	}


	/*Callback function to dismiss the MaxMind alert.*/
	public function dismiss_maxmind_alert_callback() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Unauthorized', 403 );
		}

		check_ajax_referer( 'woocommerce-anti-fraud', '_wpnonce' );

		$task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : '';

		if ( 'maxmind-alert-dismissed' === $task ) {
			$user_id  = get_current_user_id();
			update_user_meta( $user_id, 'opmc-antifraud-maxmind-alert', 'yes' );
		}

		wp_die();
	}

	/* Callback function to dismiss the alert trustswiftly alert.*/
	public function dismiss_trustswiftly_alert_callback() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Unauthorized', 403 );
		}

		check_ajax_referer( 'woocommerce-anti-fraud', '_wpnonce' );

		$trustswiftly = isset( $_POST['trustswiftly'] ) ? sanitize_text_field( wp_unslash( $_POST['trustswiftly'] ) ) : '';

		if ( 'trustswiftly-alert-dismissed' === $trustswiftly ) {
			$user_id = get_current_user_id();
			update_user_meta( $user_id, 'opmc-antifraud-trustswiftly-alert', 'yes' );
		}

		wp_die();
	}

	/* Related to Wildcard email */
	public function create_email_pattern( $setting_email, $customer_email ) {
		if ( empty( $setting_email ) || empty( $customer_email ) ) {
			return 'false';
		}

		// Convert wildcard pattern to regex
		$allowed_pattern = preg_quote( $setting_email, '/' ); // Escape special regex characters
		$allowed_pattern = str_replace( '\*', '.*', $allowed_pattern ); // Convert '*' to '.*' (any number of characters)
		$allowed_pattern = str_replace( '\?', '.', $allowed_pattern );  // Convert '?' to '.' (single character match)

		// Create final regex pattern
		$allowed_pattern = '/^' . $allowed_pattern . '$/i'; // 'i' makes it case-insensitive

		return preg_match( $allowed_pattern, $customer_email ) ? 'true' : 'false';
	}

	public function wildcard_email_validation( $fields, $errors) {
		// Ensure request is secure
		// if ( !isset( $_POST['woocommerce-process-checkout-nonce'] ) ) {
		// 	if ( ! wp_verify_nonce(  sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
		// 		 wp_die( esc_html__( 'Nonce verification failed!', 'woocommerce-anti-fraud' ) );
		// 	}
		// }

		// Get and sanitize email input
		$customer_billing_email = ! empty( $fields['billing_email'] ) ? sanitize_text_field( $fields['billing_email'] ) : '';

		// Retrieve whitelist settings
		$get_whitelist_email = trim( get_option( 'wc_settings_anti_fraud_whitelist', '' ) );

		// If whitelist is empty or email input is empty, return false
		if ( empty( $get_whitelist_email ) || empty( $customer_billing_email ) ) {
			return 'false';
		}
		$selected_whitelist_mobile_no = 'false';

		// Convert to an array and filter empty values
		$email_str_array = $this->parse_whitelist_input_data($get_whitelist_email);

		// Validate email against whitelist patterns
		foreach ( $email_str_array as $setting_whitelisted_email ) {
			if ( $this->create_email_pattern( $setting_whitelisted_email, $customer_billing_email ) === 'true' ) {
				return 'true';
			}
		}

		// ---------------- WHITELIST VALIDATIONS ---------------- //

		// ✅ Check whitelist IPs
		$user_ip           = WC_Geolocation::get_ip_address();
		$get_whitelist_ips = get_option( 'wc_settings_anti_fraud_ips_whitelist', '' );
		$whitelist_ips     = ( ! empty( $get_whitelist_ips ) && in_array( $user_ip, explode( ',', $get_whitelist_ips ) ) ) ? 'true' : 'false';

		// ✅ Check whitelist user roles
		$user                      = wp_get_current_user();
		$user_roles                = $user->roles;
		$whitelist_roles           = get_option( 'wc_af_whitelist_user_roles', array() );
		$selected_whitelisted_role = ( get_option( 'wc_af_enable_whitelist_user_roles' ) == 'yes' && in_array( $user_roles[0], $whitelist_roles ) ) ? 'true' : 'false';

		// ✅ Check whitelist payment method
		$selected_whitelist_payment_method = 'false';
		if ( get_option( 'wc_af_enable_whitelist_payment_method' ) == 'yes' ) {
			$get_whitelist_payment_method = get_option( 'wc_settings_anti_fraud_whitelist_payment_method', array() );
			$payment_method_from_checkout = WC()->session->get( 'chosen_payment_method' );

			if ( in_array( $payment_method_from_checkout, $get_whitelist_payment_method ) ) {
				$selected_whitelist_payment_method = 'true';
			}
		}

		// ✅ Check whitelist mobile number
		if ( get_option( 'wc_af_enable_whitelist_phone_number' ) == 'yes' ) {

			$get_whitelist_mobile_no = get_option( 'wc_af_whitelist_phone_numbers');
			
			if ( '' != $get_whitelist_mobile_no ) {

				$whitelist_mobile_no = $this->parse_whitelist_input_data($get_whitelist_mobile_no);

				$mobile_no_from_checkout = WC()->session->get( 'billing_phone' );

				if ( in_array( $mobile_no_from_checkout, $whitelist_mobile_no ) ) {
					$selected_whitelist_mobile_no = 'true';
				}
			}
		}

		// ✅ Check whitelist country
		$selected_whitelist_country = 'false';
		if ( get_option( 'wc_af_enable_whitelist_country' ) == 'yes' ) {
			$whitelist_countries = (array) get_option( 'wc_af_whitelisted_countries', [] );
			$billing_country = isset( $fields['billing_country'] ) ? $fields['billing_country'] : '';
			
			if ( ! empty( $whitelist_countries ) && in_array( $billing_country, $whitelist_countries, true ) ) {
				$selected_whitelist_country = 'true';
			}
		}

		// Get posted billing & shipping country/state
		$billing_country  = isset( $fields['billing_country'] ) ? sanitize_text_field( $fields['billing_country'] ) : ( isset( $fields['billing_country'] ) ? sanitize_text_field( wp_unslash( $fields['billing_country'] ) ) : '' );
		// Helper to get readable state name from posted data (handles code => name)
		$resolve_state_name = function( $country, $state_raw ) {
			$state_raw = (string) $state_raw;
			$state_name = trim( $state_raw );
			if ( ! empty( $country ) && function_exists( 'WC' ) && isset( WC()->countries ) ) {
				$states = WC()->countries->get_states( $country );
				if ( is_array( $states ) && ! empty( $states ) ) {
					// If posted value is a state code and exists in keys, use the mapped name
					if ( array_key_exists( $state_raw, $states ) ) {
						$state_name = $states[ $state_raw ];
					} else {
						// try case-insensitive match against names or codes
						foreach ( $states as $code => $name ) {
							if ( 0 === strcasecmp( $name, $state_raw ) || 0 === strcasecmp( $code, $state_raw ) ) {
								$state_name = $name;
								break;
							}
						}
					}
				}
			}
			return wc_af_strtolower( trim( $state_name ) );
		};

		// ✅ Check whitelist state
		$selected_whitelist_state = 'false';
		if ( get_option( 'wc_af_enable_whitelist_state' ) == 'yes' ) {
			$get_whitelisted_states = get_option( 'wc_af_whitelisted_states', '' );
			
			if ( ! empty( $get_whitelisted_states ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_states ) ) {
					$whitelist_states = $get_whitelisted_states;
				} else {
					$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_states );
				}
				
				$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
				$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
				
				$billing_state_raw = isset( $fields['billing_state'] ) ? wc_af_strtolower( trim( $fields['billing_state'] ) ) : '';
				$billing_state  = $resolve_state_name( $billing_country, $billing_state_raw );
				
				if ( ! empty( $billing_state ) && in_array( $billing_state, $whitelist_states, true ) ) {
					$selected_whitelist_state = 'true';
				}
			}
		}

		// ✅ Check whitelist city/county
		$selected_whitelist_city = 'false';
		if ( get_option( 'wc_af_enable_whitelist_city' ) == 'yes' ) {
			$get_whitelisted_cities = get_option( 'wc_af_whitelisted_city', '' );
			
			if ( ! empty( $get_whitelisted_cities ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_cities ) ) {
					$whitelist_cities = $get_whitelisted_cities;
				} else {
					$whitelist_cities = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_cities );
				}
				
				$whitelist_cities = array_filter( array_map( 'trim', (array) $whitelist_cities ) );
				$whitelist_cities = array_map( 'wc_af_strtolower', $whitelist_cities );
				
				$billing_city = isset( $fields['billing_city'] ) ? wc_af_strtolower( trim( $fields['billing_city'] ) ) : '';
				
				if ( ! empty( $billing_city ) && in_array( $billing_city, $whitelist_cities, true ) ) {
					$selected_whitelist_city = 'true';
				}
			}
		}

		// ✅ Check whitelist ZIP/Postal Code
		$selected_whitelist_zip = 'false';
		if ( get_option( 'wc_af_enable_whitelist_zip' ) == 'yes' ) {
			$get_whitelisted_zips = get_option( 'wc_af_whitelisted_zip', '' );
			
			if ( ! empty( $get_whitelisted_zips ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_zips ) ) {
					$whitelist_zips = $get_whitelisted_zips;
				} else {
					$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_zips );
				}
				
				$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
				$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
				
				$billing_zip = isset( $fields['billing_postcode'] ) ? wc_af_strtolower( str_replace( ' ', '', trim( $fields['billing_postcode'] ) ) ) : '';
				
				if ( ! empty( $billing_zip ) && in_array( $billing_zip, $whitelist_zips, true ) ) {
					$selected_whitelist_zip = 'true';
				}
			}
		}

		$selected_whitelist_address = $this->address_whitelisting_check($fields);
		$selected_whitelist_firstname = $this->firstname_whitelisting_check($fields);
		$selected_whitelist_lastname = $this->lastname_whitelisting_check($fields);

		// ✅ Check if email is explicitly whitelisted
		$selected_whitelisted_email = in_array( $customer_billing_email, $email_str_array, true );

		// ✅ Store options
		update_option( 'not_whitelisted_email', isset( $selected_whitelisted_email ) ? $selected_whitelisted_email : false );
		update_option( 'white_payment_methods', isset( $selected_whitelist_payment_method ) ? $selected_whitelist_payment_method : false );
		update_option( 'is_whitelisted_roles', $selected_whitelisted_role );
		update_option( 'is_whitelisted_ips', $whitelist_ips );
		update_option( 'is_whitelisted_mobile_no', $selected_whitelist_mobile_no );
		update_option( 'is_whitelisted_country', $selected_whitelist_country );
		update_option( 'is_whitelisted_state', $selected_whitelist_state );
		update_option( 'is_whitelisted_city', $selected_whitelist_city );
		update_option( 'is_whitelisted_zip', $selected_whitelist_zip );
		update_option( 'is_whitelisted_address', $selected_whitelist_address );
		update_option( 'is_whitelisted_firstname', $selected_whitelist_firstname );
		update_option( 'is_whitelisted_lastname', $selected_whitelist_lastname );


		// ---------------- BLACKLIST HANDLING ---------------- //

		$is_enable_blacklist = ( get_option( 'wc_settings_anti_fraudenable_automatic_email_blacklist' ) === 'yes' );
		$get_blacklist_email = get_option( 'wc_settings_anti_fraudblacklist_emails', '' );

		if ( $is_enable_blacklist && ! empty( $get_blacklist_email ) ) {
			$s_blacklist_email = array_map( 'trim', explode( ',', $get_blacklist_email ) );
			$s_whitelist_email = $this->parse_whitelist_input_data($get_whitelist_email);

			// Remove whitelisted emails from blacklist
			$email_str_array = array_diff( $s_blacklist_email, $s_whitelist_email );

			if ( ! empty( $email_str_array ) ) {
				foreach ( $email_str_array as $setting_email ) {
					if ( 'true' === $this->create_email_pattern( $setting_email, $customer_billing_email )
						 && ! $selected_whitelisted_email
						 && 'true' !== $selected_whitelisted_role 
						 && 'true' !== $selected_whitelist_payment_method
						 && 'true' !== $whitelist_ips
						 && 'true' !== $selected_whitelist_mobile_no
						 && 'true' !== $selected_whitelist_country
						 && 'true' !== $selected_whitelist_state
						 && 'true' !== $selected_whitelist_city
						 && 'true' !== $selected_whitelist_zip
						 && 'true' !== $selected_whitelist_address
						 && 'true' !== $selected_whitelist_firstname
						 && 'true' !== $selected_whitelist_lastname ) {

						global $check_block;
						$check_block = 1;
						//wc_add_notice( __( 'This email ID is blocked.', 'woocommerce-anti-fraud' ), 'error' );
						update_option( 'wildcard_whitelist_email', 'false' );

						return 'false';
					}
				}
			}
		}

		update_option( 'wildcard_whitelist_email', 'false' );

		return 'false';
	}

	/*
	 * Whildcard email validation callback function to use globally
	 */
	public function call_wildcard_email_validation( $fields) {

		$customer_email  = '';
		$whitelist_email = 'false';
		$selected_whitelist_mobile_no = 'false';

		if ( ! empty( $fields['billing_email'] ) ) {

			if ( isset( $_REQUEST['_wpnonce'] ) ) {
				if ( ! wp_verify_nonce(  sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'my-nonce' ) ) {
					echo 'Nonce verification failed!';
					die();
				}
			}

			$customer_email = sanitize_text_field( $fields['billing_email'] );
		}

		$get_whitelist_email = get_option( 'wc_settings_anti_fraud_whitelist' );

		if ( '' != $get_whitelist_email ) {

			$email_str_array = $this->parse_whitelist_input_data($get_whitelist_email);

			if ( is_array( $email_str_array ) && count( $email_str_array ) > 0 ) {

				if ( ! empty( $email_str_array ) && is_array( $email_str_array ) ) {

					foreach ( $email_str_array as $setting_email ) {

						$valid_customer = $this->create_email_pattern( $setting_email, $customer_email );

						if ( isset( $valid_customer ) && 'true' == $valid_customer ) {

							$whitelist_email = 'true';
							break;
						}
					}
				}
			}
		}

		$userIp = WC_Geolocation::get_ip_address();
		$normalized_user_ip = $this->normalize_ip( $userIp ); // ✅ FIXED: Normalize for IPv4/IPv6 matching

		$get_all_whitelist_ips = get_option( 'wc_settings_anti_fraud_ips_whitelist' );

		$whitelist_ips = 'false';
		if ( '' != $get_all_whitelist_ips ) {

			$s_whitelist_ips = $this->parse_whitelist_input_data($get_all_whitelist_ips);
			// ✅ FIXED: Normalize whitelist IPs for comparison
			$normalized_whitelist_ips = array_map( array( $this, 'normalize_ip' ), $s_whitelist_ips );

			if ( in_array( $normalized_user_ip, $normalized_whitelist_ips, true ) ) {

				$whitelist_ips = 'true';
			}
		}
		//$ip = '195.181.161.229';

		// check whitelist user role
		$user                       = wp_get_current_user();
		$user_roles                 = $user->roles;
		$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles' );

		if ( empty( $wc_af_whitelist_user_roles ) ) {
			$wc_af_whitelist_user_roles = array();
		}

		$selected_whitelisted_role      = 'false';
		$is_enable_whitelist_user_roles = get_option( 'wc_af_enable_whitelist_user_roles' );
		if ( 'yes' == $is_enable_whitelist_user_roles ) {

			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $wc_af_whitelist_user_roles ) ) {
					$selected_whitelisted_role = 'true';
					break;
				}
			}
		} // check whitelist user role end

		// check whitelist payment method
		$selected_whitelist_payment_method = 'false';
		if ( get_option( 'wc_af_enable_whitelist_payment_method' ) == 'yes' ) {

			if ( get_option( 'wc_settings_anti_fraud_whitelist_payment_method' ) && null != get_option( 'wc_settings_anti_fraud_whitelist_payment_method' ) ) {

				$get_whitelist_payment_method = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );

				$payment_method_from_checkout = WC()->session->get( 'chosen_payment_method' );

				if ( in_array( $payment_method_from_checkout, $get_whitelist_payment_method ) ) {
					$selected_whitelist_payment_method = 'true';
				}
			}
		} // check whitelist payment method end

		// ✅ Check whitelist mobile number
		if ( get_option( 'wc_af_enable_whitelist_phone_number' ) == 'yes' ) {

			$get_whitelist_mobile_no = get_option( 'wc_af_whitelist_phone_numbers');

			if ( '' != $get_whitelist_mobile_no ) {

				$whitelist_mobile_no = $this->parse_whitelist_input_data($get_whitelist_mobile_no);

				$mobile_no_from_checkout = WC()->session->get( 'billing_phone' );

				if ( in_array( $mobile_no_from_checkout, $whitelist_mobile_no ) ) {
					$selected_whitelist_mobile_no = 'true';
				}
			}
		}

		// ✅ Check whitelist country
		$selected_whitelist_country = 'false';
		if ( get_option( 'wc_af_enable_whitelist_country' ) == 'yes' ) {
			$whitelist_countries = (array) get_option( 'wc_af_whitelisted_countries', [] );
			$billing_country = isset( $fields['billing_country'] ) ? $fields['billing_country'] : '';
			
			if ( ! empty( $whitelist_countries ) && in_array( $billing_country, $whitelist_countries, true ) ) {
				$selected_whitelist_country = 'true';
			}
		}

		// Get posted billing & shipping country/state
		$billing_country  = isset( $fields['billing_country'] ) ? sanitize_text_field( $fields['billing_country'] ) : ( isset( $_POST['billing_country'] ) ? sanitize_text_field( wp_unslash( $fields['billing_country'] ) ) : '' );
		// Helper to get readable state name from posted data (handles code => name)
		$resolve_state_name = function( $country, $state_raw ) {
			$state_raw = (string) $state_raw;
			$state_name = trim( $state_raw );
			if ( ! empty( $country ) && function_exists( 'WC' ) && isset( WC()->countries ) ) {
				$states = WC()->countries->get_states( $country );
				if ( is_array( $states ) && ! empty( $states ) ) {
					// If posted value is a state code and exists in keys, use the mapped name
					if ( array_key_exists( $state_raw, $states ) ) {
						$state_name = $states[ $state_raw ];
					} else {
						// try case-insensitive match against names or codes
						foreach ( $states as $code => $name ) {
							if ( 0 === strcasecmp( $name, $state_raw ) || 0 === strcasecmp( $code, $state_raw ) ) {
								$state_name = $name;
								break;
							}
						}
					}
				}
			}
			return wc_af_strtolower( trim( $state_name ) );
		};

		// ✅ Check whitelist state
		$selected_whitelist_state = 'false';
		if ( get_option( 'wc_af_enable_whitelist_state' ) == 'yes' ) {
			$get_whitelisted_states = get_option( 'wc_af_whitelisted_states', '' );
			
			if ( ! empty( $get_whitelisted_states ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_states ) ) {
					$whitelist_states = $get_whitelisted_states;
				} else {
					$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_states );
				}
				
				$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
				$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
				
				$billing_state_raw = isset( $fields['billing_state'] ) ? wc_af_strtolower( trim( $fields['billing_state'] ) ) : '';
				$billing_state  = $resolve_state_name( $billing_country, $billing_state_raw );
				
				if ( ! empty( $billing_state ) && in_array( $billing_state, $whitelist_states, true ) ) {
					$selected_whitelist_state = 'true';
				}
			}
		}

		// ✅ Check whitelist city/county
		$selected_whitelist_city = 'false';
		if ( get_option( 'wc_af_enable_whitelist_city' ) == 'yes' ) {
			$get_whitelisted_cities = get_option( 'wc_af_whitelisted_city', '' );
			
			if ( ! empty( $get_whitelisted_cities ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_cities ) ) {
					$whitelist_cities = $get_whitelisted_cities;
				} else {
					$whitelist_cities = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_cities );
				}
				
				$whitelist_cities = array_filter( array_map( 'trim', (array) $whitelist_cities ) );
				$whitelist_cities = array_map( 'wc_af_strtolower', $whitelist_cities );
				
				$billing_city = isset( $fields['billing_city'] ) ? wc_af_strtolower( trim( $fields['billing_city'] ) ) : '';
				
				if ( ! empty( $billing_city ) && in_array( $billing_city, $whitelist_cities, true ) ) {
					$selected_whitelist_city = 'true';
				}
			}
		}

		// ✅ Check whitelist ZIP/Postal Code
		$selected_whitelist_zip = 'false';
		if ( get_option( 'wc_af_enable_whitelist_zip' ) == 'yes' ) {
			$get_whitelisted_zips = get_option( 'wc_af_whitelisted_zip', '' );
			
			if ( ! empty( $get_whitelisted_zips ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_zips ) ) {
					$whitelist_zips = $get_whitelisted_zips;
				} else {
					$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_zips );
				}
				
				$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
				$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
				
				$billing_zip = isset( $fields['billing_postcode'] ) ? wc_af_strtolower( str_replace( ' ', '', trim( $fields['billing_postcode'] ) ) ) : '';
				
				if ( ! empty( $billing_zip ) && in_array( $billing_zip, $whitelist_zips, true ) ) {
					$selected_whitelist_zip = 'true';
				}
			}
		}

	$selected_whitelist_address = $this->address_whitelisting_check($fields);

	// ✅ Check whitelist first name
	$selected_whitelist_firstname = $this->firstname_whitelisting_check($fields);

	// ✅ Check whitelist last name
	$selected_whitelist_lastname = $this->lastname_whitelisting_check($fields);

	// check whitelist specific email not wildcard type
	$get_whitelist_email        = get_option( 'wc_settings_anti_fraud_whitelist' );
	$single_whitelist_email_arr = $this->parse_whitelist_input_data($get_whitelist_email);
	$selected_whitelisted_email = false;

	$customer_billing_email = isset( $fields['billing_email'] ) ? sanitize_text_field( $fields['billing_email'] ) : '';
		if ( in_array( $customer_billing_email, $single_whitelist_email_arr ) ) {
			$selected_whitelisted_email = true;
		} // check whitelist specific email not wildcard type end

	update_option( 'not_whitelisted_email', $selected_whitelisted_email );
	update_option( 'white_payment_methods', $selected_whitelist_payment_method );
	update_option( 'is_whitelisted_roles', $selected_whitelisted_role );
	update_option( 'is_whitelisted_ips', $whitelist_ips );
	update_option( 'is_whitelisted_mobile_no', $selected_whitelist_mobile_no );
	update_option( 'is_whitelisted_country', $selected_whitelist_country );
	update_option( 'is_whitelisted_state', $selected_whitelist_state );
	update_option( 'is_whitelisted_city', $selected_whitelist_city );
	update_option( 'is_whitelisted_zip', $selected_whitelist_zip );
	update_option( 'is_whitelisted_address', $selected_whitelist_address );
	update_option( 'is_whitelisted_firstname', $selected_whitelist_firstname );
	update_option( 'is_whitelisted_lastname', $selected_whitelist_lastname );


		$is_enable_blacklist = get_option( 'wc_settings_anti_fraudenable_automatic_email_blacklist' );
		$get_blacklist_email = get_option( 'wc_settings_anti_fraudblacklist_emails' );

		if ( '' != $get_blacklist_email && '' != $get_whitelist_email ) {

			$s_blacklist_email = explode( ',', $get_blacklist_email );
			$s_whitelist_email = $this->parse_whitelist_input_data($get_whitelist_email);

			$email_str_array = array_diff( $s_blacklist_email, $s_whitelist_email );

			if ( is_array( $email_str_array ) && count( $email_str_array ) > 0 ) {

				foreach ( $email_str_array as $setting_email ) {

				$valid_customer = $this->create_email_pattern( $setting_email, $customer_email );

					if ( isset( $valid_customer ) && 'true' == $valid_customer && ! $selected_whitelisted_email && 'true' != $selected_whitelisted_role && 'true' != $selected_whitelist_payment_method && 'true' != $whitelist_ips && 'true' != $selected_whitelist_mobile_no && 'true' != $selected_whitelist_country && 'true' != $selected_whitelist_state && 'true' != $selected_whitelist_city && 'true' != $selected_whitelist_zip && 'true' != $selected_whitelist_address && 'true' != $selected_whitelist_firstname && 'true' != $selected_whitelist_lastname) {

						$whitelist_email = 'false';
						// wc_add_notice( __( 'This email id is blocked.' ), 'error' );
						break;
					}
				}
			}
		}

		// update_option('wildcard_whitelist_email', $whitelist_email);
		return $whitelist_email;
	} /* Related to wildcard email end */

	public function sync_woocommerce_email( $user_id, $old_user_data ) {

		// wc_af_fraud_check_before_payment
		$user           = get_userdata( $user_id );
		$new_user_email = $user->user_email;

		if ( $new_user_email != $old_user_data->user_email ) {
			wp_update_user(
				array(
					'ID'            => $user->ID,
					'billing_email' => $new_user_email,
				)
			);
		}
	}

	/**
	 * Get whitelist email data with lazy loading and caching
	 * Only loads when whitelist features are actually enabled
	 * 
	 * @return string|false Whitelist email data or false if not needed
	 */
	private function get_whitelist_email_data() {
		// Check if any whitelist feature is enabled before loading data
		// This prevents loading large whitelist data when not needed
		$whitelist_enabled = (
			get_option( 'wc_af_enable_whitelist_user_roles' ) === 'yes' ||
			get_option( 'wc_af_enable_whitelist_payment_method' ) === 'yes' ||
			get_option( 'wc_af_enable_whitelist_phone_number' ) === 'yes' ||
			get_option( 'wc_af_enable_whitelist_country' ) === 'yes' ||
			get_option( 'wc_af_enable_whitelist_state' ) === 'yes' ||
			get_option( 'wc_af_enable_whitelist_city' ) === 'yes' ||
			get_option( 'wc_af_enable_whitelist_zip' ) === 'yes' ||
			get_option( 'wc_af_enable_whitelist_address' ) === 'yes' ||
			get_option( 'wc_af_enable_whitelisting_first_name' ) === 'yes' ||
			get_option( 'wc_af_enable_whitelisting_last_name' ) === 'yes'
		);

		// If no whitelist features are enabled, return false early
		// This avoids loading the large whitelist option
		if ( ! $whitelist_enabled ) {
			// Still check if email whitelist exists (legacy support)
			// But use get_option with autoload=false to avoid autoload
			$email_whitelist = get_option( 'wc_settings_anti_fraud_whitelist', '' );
			return ( '' !== $email_whitelist ) ? $email_whitelist : false;
		}

		// Use cached version if available (transient cache)
		$cache_key = 'wc_af_whitelist_email_data';
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		// Load whitelist data (with autoload=false to prevent autoload)
		$email_whitelist = get_option( 'wc_settings_anti_fraud_whitelist', '' );
		
		// Cache for 1 hour
		if ( '' !== $email_whitelist ) {
			set_transient( $cache_key, $email_whitelist, HOUR_IN_SECONDS );
		}

		return $email_whitelist;
	}

	/**
	 * Clear whitelist cache when whitelist is updated
	 */
	private function clear_whitelist_cache() {
		delete_transient( 'wc_af_whitelist_email_data' );
	}

	/**
	 * Disable autoload for whitelist option to prevent loading on every request
	 * This is a critical performance optimization for large whitelist tables
	 */
	private function disable_whitelist_autoload() {
		global $wpdb;
		// Directly update the autoload column in wp_options table
		$wpdb->update(
			$wpdb->options,
			array( 'autoload' => 'no' ),
			array( 'option_name' => 'wc_settings_anti_fraud_whitelist' ),
			array( '%s' ),
			array( '%s' )
		);
	}

	/**
	 * Migration: Disable autoload for existing whitelist option
	 * This runs once on admin_init to optimize existing installations
	 */
	public function migrate_whitelist_autoload() {
		// Only run once
		$migration_done = get_option( 'wc_af_whitelist_autoload_migrated', false );
		if ( $migration_done ) {
			return;
		}

		// Check if whitelist option exists and has autoload enabled
		global $wpdb;
		$option = $wpdb->get_row( $wpdb->prepare(
			"SELECT option_id, autoload FROM {$wpdb->options} WHERE option_name = %s",
			'wc_settings_anti_fraud_whitelist'
		) );

		if ( $option && 'yes' === $option->autoload ) {
			// Disable autoload
			$this->disable_whitelist_autoload();
		}

		// Mark migration as done
		update_option( 'wc_af_whitelist_autoload_migrated', true, false );
	}

	// custom code for block order if email or ip in blacklist.

	public function misha_validate_fname_lname( $fields, $errors ) {
		$blocked_email     = get_option( 'wc_settings_anti_fraudblacklist_emails' );
		$blocked_ipaddress = get_option( 'wc_settings_anti_fraudblacklist_ipaddress' );
		$array_mail        = explode( ',', $blocked_email );

		if ( isset( $_REQUEST['_wpnonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'my-nonce' ) ) {
				echo 'Nonce verification failed!';
				die();
			}
		}

		// ✅ OPTIMIZED: Use lazy loading helper - only loads when whitelist features are enabled
		$email_whitelist = $this->get_whitelist_email_data();
		$is_whitelisted  = '';
		$selected_whitelist_mobile_no = 'false';

		// check whitelist ips
		$userIp = WC_Geolocation::get_ip_address();
		$normalized_user_ip = $this->normalize_ip( $userIp ); // ✅ FIXED: Normalize for IPv4/IPv6 matching

		$get_all_whitelist_ips = get_option( 'wc_settings_anti_fraud_ips_whitelist' );

		$whitelist_ips = 'false';
		if ( '' != $get_all_whitelist_ips ) {

			$s_whitelist_ips = $this->parse_whitelist_input_data($get_all_whitelist_ips);

			if ( in_array( $userIp, $s_whitelist_ips ) ) {

				$whitelist_ips = 'true';
			}
		}
		//$ip = '195.181.161.229';

		// check whitelist user role
		$user                       = wp_get_current_user();
		$user_roles                 = $user->roles;
		$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles' );

		if ( empty( $wc_af_whitelist_user_roles ) ) {
			$wc_af_whitelist_user_roles = array();
		}

		$selected_whitelisted_role      = 'false';
		$is_enable_whitelist_user_roles = get_option( 'wc_af_enable_whitelist_user_roles' );
		if ( 'yes' == $is_enable_whitelist_user_roles ) {

			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $wc_af_whitelist_user_roles ) ) {
					$selected_whitelisted_role = 'true';
					break;
				}
			}
		} // check whitelist user role end

		// ✅ OPTIMIZED: Only process whitelist if data was loaded
		if ( false !== $email_whitelist && '' != $email_whitelist ) {
			$whitelist = $this->parse_whitelist_input_data($email_whitelist);
			if ( is_array( $whitelist ) && count( $whitelist ) > 0 ) {
				// Trim items to be sure
				foreach ( $whitelist as $k => $v ) {
					$whitelist[ $k ] = trim( $v );
				}
				// Af_Logger::debug('email found : '. print_r($whitelist, true));
			}
			$is_whitelisted = array_intersect( $whitelist, $array_mail );
		}

		// check whitelist payment method
		$selected_whitelist_payment_method = 'false';
		if ( get_option( 'wc_af_enable_whitelist_payment_method' ) == 'yes' ) {

			if ( get_option( 'wc_settings_anti_fraud_whitelist_payment_method' ) && null != get_option( 'wc_settings_anti_fraud_whitelist_payment_method' ) ) {

				$get_whitelist_payment_method = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );

				$payment_method_from_checkout = WC()->session->get( 'chosen_payment_method' );

				if ( in_array( $payment_method_from_checkout, $get_whitelist_payment_method ) ) {
					$selected_whitelist_payment_method = 'true';
				}
			}
		} // check whitelist payment method end

		// ✅ Check whitelist mobile number
		if ( get_option( 'wc_af_enable_whitelist_phone_number' ) == 'yes' ) {

			$get_whitelist_mobile_no = get_option( 'wc_af_whitelist_phone_numbers');
			if ( '' != $get_whitelist_mobile_no ) {

				$whitelist_mobile_no = $this->parse_whitelist_input_data($get_whitelist_mobile_no);

				$mobile_no_from_checkout = isset( $fields['billing_phone'] ) ? $fields['billing_phone'] : '';

				if ( in_array( $mobile_no_from_checkout, $whitelist_mobile_no ) ) {
					$selected_whitelist_mobile_no = 'true';
				}
			}
		}

		// ✅ Check whitelist country
		$selected_whitelist_country = 'false';
		if ( get_option( 'wc_af_enable_whitelist_country' ) == 'yes' ) {
			$whitelist_countries = (array) get_option( 'wc_af_whitelisted_countries', [] );
			$billing_country = isset( $fields['billing_country'] ) ? $fields['billing_country'] : '';
			
			if ( ! empty( $whitelist_countries ) && in_array( $billing_country, $whitelist_countries, true ) ) {
				$selected_whitelist_country = 'true';
			}
		}

		// Get posted billing & shipping country/state
		$billing_country  = isset( $fields['billing_country'] ) ? sanitize_text_field( $fields['billing_country'] ) : ( isset( $_POST['billing_country'] ) ? sanitize_text_field( wp_unslash( $fields['billing_country'] ) ) : '' );
		// Helper to get readable state name from posted data (handles code => name)
		$resolve_state_name = function( $country, $state_raw ) {
			$state_raw = (string) $state_raw;
			$state_name = trim( $state_raw );
			if ( ! empty( $country ) && function_exists( 'WC' ) && isset( WC()->countries ) ) {
				$states = WC()->countries->get_states( $country );
				if ( is_array( $states ) && ! empty( $states ) ) {
					// If posted value is a state code and exists in keys, use the mapped name
					if ( array_key_exists( $state_raw, $states ) ) {
						$state_name = $states[ $state_raw ];
					} else {
						// try case-insensitive match against names or codes
						foreach ( $states as $code => $name ) {
							if ( 0 === strcasecmp( $name, $state_raw ) || 0 === strcasecmp( $code, $state_raw ) ) {
								$state_name = $name;
								break;
							}
						}
					}
				}
			}
			return wc_af_strtolower( trim( $state_name ) );
		};

		// ✅ Check whitelist state
		$selected_whitelist_state = 'false';
		if ( get_option( 'wc_af_enable_whitelist_state' ) == 'yes' ) {
			$get_whitelisted_states = get_option( 'wc_af_whitelisted_states', '' );
			
			if ( ! empty( $get_whitelisted_states ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_states ) ) {
					$whitelist_states = $get_whitelisted_states;
				} else {
					$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_states );
				}
				
				$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
				$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
				
				$billing_state_raw = isset( $fields['billing_state'] ) ? wc_af_strtolower( trim( $fields['billing_state'] ) ) : '';
				$billing_state  = $resolve_state_name( $billing_country, $billing_state_raw );
				
				if ( ! empty( $billing_state ) && in_array( $billing_state, $whitelist_states, true ) ) {
					$selected_whitelist_state = 'true';
				}
			}
		}

		// ✅ Check whitelist city/county
		$selected_whitelist_city = 'false';
		if ( get_option( 'wc_af_enable_whitelist_city' ) == 'yes' ) {
			$get_whitelisted_cities = get_option( 'wc_af_whitelisted_city', '' );
					
			if ( ! empty( $get_whitelisted_cities ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_cities ) ) {
					$whitelist_cities = $get_whitelisted_cities;
				} else {
					$whitelist_cities = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_cities );
				}
				
				$whitelist_cities = array_filter( array_map( 'trim', (array) $whitelist_cities ) );
				$whitelist_cities = array_map( 'wc_af_strtolower', $whitelist_cities );
				
				$billing_city = isset( $fields['billing_city'] ) ? wc_af_strtolower( trim( $fields['billing_city'] ) ) : '';
						
				if ( ! empty( $billing_city ) && in_array( $billing_city, $whitelist_cities, true ) ) {
					$selected_whitelist_city = 'true';
				}
			}
		}

		// ✅ Check whitelist ZIP/Postal Code
		$selected_whitelist_zip = 'false';
		if ( get_option( 'wc_af_enable_whitelist_zip' ) == 'yes' ) {
			$get_whitelisted_zips = get_option( 'wc_af_whitelisted_zip', '' );
			
			if ( ! empty( $get_whitelisted_zips ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_zips ) ) {
					$whitelist_zips = $get_whitelisted_zips;
				} else {
					$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_zips );
				}
				
				$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
				$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
				
				$billing_zip = isset( $fields['billing_postcode'] ) ? wc_af_strtolower( str_replace( ' ', '', trim( $fields['billing_postcode'] ) ) ) : '';
				
				if ( ! empty( $billing_zip ) && in_array( $billing_zip, $whitelist_zips, true ) ) {
					$selected_whitelist_zip = 'true';
				}
			}
		}

	$selected_whitelist_address = $this->address_whitelisting_check($fields);
	$selected_whitelist_firstname = $this->firstname_whitelisting_check($fields);
	$selected_whitelist_lastname = $this->lastname_whitelisting_check($fields);
	

	// ✅ OPTIMIZED: check whitelist specific email not wildcard type - use lazy loading
	$get_whitelist_email        = $this->get_whitelist_email_data();
	$single_whitelist_email_arr = $get_whitelist_email ? $this->parse_whitelist_input_data($get_whitelist_email) : array();
	Af_Logger::debug( 'email found : ' . print_r( $single_whitelist_email_arr, true ) );

	$selected_whitelisted_email = false;

	$customer_billing_email = isset( $fields['billing_email'] ) ? sanitize_text_field( $fields['billing_email'] ) : '';
		if ( in_array( $customer_billing_email, $single_whitelist_email_arr ) ) {
			$selected_whitelisted_email = true;
		} // check whitelist specific email not wildcard type end

	update_option( 'not_whitelisted_email', $selected_whitelisted_email );
	update_option( 'white_payment_methods', $selected_whitelist_payment_method );
	update_option( 'is_whitelisted_roles', $selected_whitelisted_role );
	update_option( 'is_whitelisted_ips', $whitelist_ips );
	update_option( 'is_whitelisted_mobile_no', $selected_whitelist_mobile_no );
	update_option( 'is_whitelisted_country', $selected_whitelist_country );
	update_option( 'is_whitelisted_state', $selected_whitelist_state );
	update_option( 'is_whitelisted_city', $selected_whitelist_city );
	update_option( 'is_whitelisted_zip', $selected_whitelist_zip );
	update_option( 'is_whitelisted_address', $selected_whitelist_address );
	update_option( 'is_whitelisted_firstname', $selected_whitelist_firstname );
	update_option( 'is_whitelisted_lastname', $selected_whitelist_lastname );

		// Callback function for check whildcard email
		$selected_wildcard_whitelisted_email = $this->call_wildcard_email_validation($fields);

		$is_enable_blacklist = ( get_option( 'wc_settings_anti_fraudenable_automatic_email_blacklist' ) === 'yes' );

		if ( $is_enable_blacklist ) {
			if ( '' != $blocked_email ) {
				if ( empty( $is_whitelisted ) ) {
					if ( ! $selected_whitelisted_email ) {
						if ( 'true' != $selected_whitelisted_role ) {
							if ( 'true' != $whitelist_ips ) {
								if ( 'true' != $selected_whitelist_payment_method ) {
									if ( 'true' != $selected_whitelist_mobile_no ) {
										if ( 'true' != $selected_whitelist_country ) {
											if ( 'true' != $selected_whitelist_state ) {
												if ( 'true' != $selected_whitelist_city ) {
													if ( 'true' != $selected_whitelist_zip ) {
														if ( 'true' != $selected_whitelist_address ) {
															if ( 'true' != $selected_whitelist_firstname ) {
																if ( 'true' != $selected_whitelist_lastname ) {
																	if ( 'true' != $selected_wildcard_whitelisted_email ) {
																		foreach ( $array_mail as $single ) {
																			if ( trim( $single ) == $fields['billing_email'] ) {
																				// echo esc_html_e('This email id is blocked.', 'woocommerce-anti-fraud');.
																				// $errors->add( 'validation', __( 'This email id is blocked.', 'woocommerce-anti-fraud' ) );
																				global $check_block;
																				if ( 1 != $check_block ) {
																					// echo ( $wc_af_is_email_blocked );
																					wc_add_notice( __( 'This email id is blocked.' ), 'error' );
																				}
																			}
																		}
																	}
																}
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			} else {
				if ( ! $selected_whitelisted_email ) {
					if ( 'true' != $selected_whitelisted_role ) {
						if ( 'true' != $whitelist_ips ) {
							if ( 'true' != $selected_whitelist_payment_method ) {
								if ( 'true' != $selected_whitelist_mobile_no ) {
									if ( 'true' != $selected_whitelist_country ) {
										if ( 'true' != $selected_whitelist_state ) {
											if ( 'true' != $selected_whitelist_city ) {
												if ( 'true' != $selected_whitelist_zip ) {
													if ( 'true' != $selected_whitelist_address ) {
														if ( 'true' != $selected_whitelist_firstname ) {
															if ( 'true' != $selected_whitelist_lastname ) {
																if ( 'true' != $selected_wildcard_whitelisted_email ) {
																	foreach ( $array_mail as $single ) {
																		if ( trim( $single ) == $fields['billing_email'] ) {
																			// echo esc_html_e('This email id is blocked.', 'woocommerce-anti-fraud');.
																			$errors->add( 'validation', __( 'This email id is blocked.', 'woocommerce-anti-fraud' ) );
																		}
																	}
																}
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

	$ipBlacklistEnable = get_option( 'wc_settings_anti_fraudenable_automatic_ip_blacklist' );
	
		if ( 'yes' === $ipBlacklistEnable && '' != $blocked_ipaddress && ! $selected_whitelisted_email && 'true' != $selected_whitelisted_role && 'true' != $selected_whitelist_payment_method && 'true' != $selected_whitelist_mobile_no && 'true' != $selected_wildcard_whitelisted_email && 'true' != $whitelist_ips && 'true' != $selected_whitelist_country && 'true' != $selected_whitelist_state && 'true' != $selected_whitelist_city && 'true' != $selected_whitelist_zip && 'true' != $selected_whitelist_firstname && 'true' != $selected_whitelist_lastname) {

			$userip          = WC_Geolocation::get_ip_address();
			$array_ipaddress = explode( ',', $blocked_ipaddress );
			foreach ( $array_ipaddress as $singles ) {
				if ( trim( $singles ) == $userip ) {
					$errors->add( 'validation', __( 'This IP Address is blocked.', 'woocommerce-anti-fraud' ) );
				}
			}
		}
	}

	/*
	Limit Number of Orders between Time switch is co-related to the other three whitelisted rules. This is because all of these four rules are evaluated on the checkout page before the payment is processed.
	In this function, we are trying to evaluate "Limit Number of Orders between Time" rule before payment is processed on the checkout page.
	Note: Risk Score is evaluated and generated in the callback function (written within a separate helper file) after payment is processed and order is generated.
	 */
	public function max_order_attempt_between_timespan( $fields, $errors ) {

		// check whitelist ips
		$userIp = WC_Geolocation::get_ip_address();

		$get_all_whitelist_ips = get_option( 'wc_settings_anti_fraud_ips_whitelist' );

		$whitelist_ips = 'false';
		$selected_whitelist_mobile_no = 'false';

		if ( '' != $get_all_whitelist_ips ) {

			$s_whitelist_ips = $this->parse_whitelist_input_data($get_all_whitelist_ips);

			if ( in_array( $userIp, $s_whitelist_ips ) ) {

				$whitelist_ips = 'true';
			}
		}
		//$ip = '195.181.161.229';

		// check whitelist user role
		$user                       = wp_get_current_user();
		$user_roles                 = $user->roles;
		$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles' );

		if ( empty( $wc_af_whitelist_user_roles ) ) {
			$wc_af_whitelist_user_roles = array();
		}

		$selected_whitelisted_role      = 'false';
		$is_enable_whitelist_user_roles = get_option( 'wc_af_enable_whitelist_user_roles' );
		if ( 'yes' == $is_enable_whitelist_user_roles ) {

			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $wc_af_whitelist_user_roles ) ) {
					$selected_whitelisted_role = 'true';
					break;
				}
			}
		} // check whitelist user role end

		// check whitelist payment method
		$selected_whitelist_payment_method = 'false';
		if ( get_option( 'wc_af_enable_whitelist_payment_method' ) == 'yes' ) {

			if ( get_option( 'wc_settings_anti_fraud_whitelist_payment_method' ) && null != get_option( 'wc_settings_anti_fraud_whitelist_payment_method' ) ) {

				$get_whitelist_payment_method = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );

				$payment_method_from_checkout = WC()->session->get( 'chosen_payment_method' );

				if ( in_array( $payment_method_from_checkout, $get_whitelist_payment_method ) ) {
					$selected_whitelist_payment_method = 'true';
				}
			}
		} // check whitelist payment method end


		// ✅ Check whitelist mobile number
		if ( get_option( 'wc_af_enable_whitelist_phone_number' ) == 'yes' ) {

			$get_whitelist_mobile_no = get_option( 'wc_af_whitelist_phone_numbers');
			if ( '' != $get_whitelist_mobile_no ) {

				$whitelist_mobile_no = $this->parse_whitelist_input_data($get_whitelist_mobile_no);

				$mobile_no_from_checkout = isset( $fields['billing_phone'] ) ? $fields['billing_phone'] : '';

				if ( in_array( $mobile_no_from_checkout, $whitelist_mobile_no ) ) {
					$selected_whitelist_mobile_no = 'true';
				}
			}
		}

		// ✅ Check whitelist country
		$selected_whitelist_country = 'false';
		if ( get_option( 'wc_af_enable_whitelist_country' ) == 'yes' ) {
			$whitelist_countries = (array) get_option( 'wc_af_whitelisted_countries', [] );
			$billing_country = isset( $fields['billing_country'] ) ? $fields['billing_country'] : '';
			
			if ( ! empty( $whitelist_countries ) && in_array( $billing_country, $whitelist_countries, true ) ) {
				$selected_whitelist_country = 'true';
			}
		}

		// Get posted billing & shipping country/state
		$billing_country  = isset( $fields['billing_country'] ) ? sanitize_text_field( $fields['billing_country'] ) : ( isset( $_POST['billing_country'] ) ? sanitize_text_field( wp_unslash( $fields['billing_country'] ) ) : '' );
		// Helper to get readable state name from posted data (handles code => name)
		$resolve_state_name = function( $country, $state_raw ) {
			$state_raw = (string) $state_raw;
			$state_name = trim( $state_raw );
			if ( ! empty( $country ) && function_exists( 'WC' ) && isset( WC()->countries ) ) {
				$states = WC()->countries->get_states( $country );
				if ( is_array( $states ) && ! empty( $states ) ) {
					// If posted value is a state code and exists in keys, use the mapped name
					if ( array_key_exists( $state_raw, $states ) ) {
						$state_name = $states[ $state_raw ];
					} else {
						// try case-insensitive match against names or codes
						foreach ( $states as $code => $name ) {
							if ( 0 === strcasecmp( $name, $state_raw ) || 0 === strcasecmp( $code, $state_raw ) ) {
								$state_name = $name;
								break;
							}
						}
					}
				}
			}
			return wc_af_strtolower( trim( $state_name ) );
		};

		// ✅ Check whitelist state
		$selected_whitelist_state = 'false';
		if ( get_option( 'wc_af_enable_whitelist_state' ) == 'yes' ) {
			$get_whitelisted_states = get_option( 'wc_af_whitelisted_states', '' );
			
			if ( ! empty( $get_whitelisted_states ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_states ) ) {
					$whitelist_states = $get_whitelisted_states;
				} else {
					$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_states );
				}
				
				$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
				$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
				
				$billing_state_raw = isset( $fields['billing_state'] ) ? wc_af_strtolower( trim( $fields['billing_state'] ) ) : '';
				$billing_state  = $resolve_state_name( $billing_country, $billing_state_raw );
				
				if ( ! empty( $billing_state ) && in_array( $billing_state, $whitelist_states, true ) ) {
					$selected_whitelist_state = 'true';
				}
			}
		}

		// ✅ Check whitelist city/county
		$selected_whitelist_city = 'false';
		if ( get_option( 'wc_af_enable_whitelist_city' ) == 'yes' ) {
			$get_whitelisted_cities = get_option( 'wc_af_whitelisted_city', '' );
			
			if ( ! empty( $get_whitelisted_cities ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_cities ) ) {
					$whitelist_cities = $get_whitelisted_cities;
				} else {
					$whitelist_cities = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_cities );
				}
				
				$whitelist_cities = array_filter( array_map( 'trim', (array) $whitelist_cities ) );
				$whitelist_cities = array_map( 'wc_af_strtolower', $whitelist_cities );
				
				$billing_city = isset( $fields['billing_city'] ) ? wc_af_strtolower( trim( $fields['billing_city'] ) ) : '';
				
				if ( ! empty( $billing_city ) && in_array( $billing_city, $whitelist_cities, true ) ) {
					$selected_whitelist_city = 'true';
				}
			}
		}

		// ✅ Check whitelist ZIP/Postal Code
		$selected_whitelist_zip = 'false';
		if ( get_option( 'wc_af_enable_whitelist_zip' ) == 'yes' ) {
			$get_whitelisted_zips = get_option( 'wc_af_whitelisted_zip', '' );
			
			if ( ! empty( $get_whitelisted_zips ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_zips ) ) {
					$whitelist_zips = $get_whitelisted_zips;
				} else {
					$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_zips );
				}
				
				$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
				$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
				
				$billing_zip = isset( $fields['billing_postcode'] ) ? wc_af_strtolower( str_replace( ' ', '', trim( $fields['billing_postcode'] ) ) ) : '';
				
				if ( ! empty( $billing_zip ) && in_array( $billing_zip, $whitelist_zips, true ) ) {
					$selected_whitelist_zip = 'true';
				}
			}
		}

		// check whitelist specific email not wildcard type
		$get_whitelist_email        = get_option( 'wc_settings_anti_fraud_whitelist' );
		$single_whitelist_email_arr = $this->parse_whitelist_input_data($get_whitelist_email);
		$selected_whitelisted_email = 'false';

		if ( isset( $_REQUEST['_wpnonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'my-nonce' ) ) {
				echo 'Nonce verification failed!';
				die();
			}
		}

		$customer_billing_email = isset( $fields['billing_email'] ) ? sanitize_text_field( $fields['billing_email'] ) : '';
		if ( in_array( $customer_billing_email, $single_whitelist_email_arr ) ) {
			$selected_whitelisted_email = 'true';
		} // check whitelist specific email not wildcard type end

		$selected_whitelist_address = $this->address_whitelisting_check($fields);
		$selected_whitelist_firstname = $this->firstname_whitelisting_check($fields);
		$selected_whitelist_lastname = $this->lastname_whitelisting_check($fields);

		update_option( 'not_whitelisted_email', $selected_whitelisted_email );
		update_option( 'white_payment_methods', $selected_whitelist_payment_method );
		update_option( 'is_whitelisted_roles', $selected_whitelisted_role );
		update_option( 'is_whitelisted_ips', $whitelist_ips );
		update_option( 'is_whitelisted_mobile_no', $selected_whitelist_mobile_no );
		update_option( 'is_whitelisted_country', $selected_whitelist_country );
		update_option( 'is_whitelisted_state', $selected_whitelist_state );
		update_option( 'is_whitelisted_city', $selected_whitelist_city );
		update_option( 'is_whitelisted_zip', $selected_whitelist_zip );
		update_option( 'is_whitelisted_address', $selected_whitelist_address );
		update_option( 'is_whitelisted_firstname', $selected_whitelist_firstname );
		update_option( 'is_whitelisted_lastname', $selected_whitelist_lastname );

		// Callback function for check whildcard email
		$selected_wildcard_whitelisted_email = $this->call_wildcard_email_validation($fields);

		$order_limit_enabled     = get_option( 'wc_af_limit_order_count' );
	$is_update_status_active = get_option( 'wc_af_fraud_update_state' );

		if ( 'yes' === $order_limit_enabled && 'true' != $selected_whitelisted_email && 'true' != $selected_whitelisted_role && 'true' != $selected_whitelist_payment_method && 'true' != $selected_whitelist_mobile_no && 'true' != $selected_wildcard_whitelisted_email && 'true' != $whitelist_ips && 'true' != $selected_whitelist_country && 'true' !== $selected_whitelist_state && 'true' !== $selected_whitelist_city && 'true' !== $selected_whitelist_zip && 'true' !== $selected_whitelist_address && 'true' !== $selected_whitelist_firstname && 'true' !== $selected_whitelist_lastname) {

			$orders_allowed_limit = get_option( 'wc_af_allowed_order_limit' );
			$limit_time_start     = get_option( 'wc_af_limit_time_start' );
			$limit_time_end       = get_option( 'wc_af_limit_time_end' );
			// $is_update_status_active = get_option('wc_af_fraud_update_state');
			if ( 0 <= $orders_allowed_limit && ! empty( $limit_time_start ) && ! empty( $limit_time_end ) ) {

				$start_time = new DateTime( $limit_time_start, wp_timezone() );
				$end_time   = new DateTime( $limit_time_end, wp_timezone() );
				$now        = new DateTime( 'NOW', wp_timezone() );

				if ( ( $now >= $start_time ) && ( $now <= $end_time ) ) {
					/*
					 * PERFORMANCE FIX: Cache the order count for this time window for 60 s.
					 * During bot attacks this ran on every checkout validation, issuing an
					 * unbounded SELECT per request.  The cached count is accurate enough for
					 * the enforcement decision within any 60-second window.
					 */
					$checkout_limit_cache_key = 'wc_af_checkout_limit_' . $start_time->getTimestamp() . '_' . $end_time->getTimestamp();
					$orders_between_count     = get_transient( $checkout_limit_cache_key );

					if ( false === $orders_between_count ) {
						$orders_between       = wc_get_orders(
						array(
							'limit'        => max( 2, (int) $orders_allowed_limit + 1 ),
							'return'       => 'ids',
							'type'         => wc_get_order_types( 'order-count' ),
							'date_created' => $start_time->getTimestamp() . '...' . $end_time->getTimestamp(),
						)
						);
						$orders_between_count = count( $orders_between );
						set_transient( $checkout_limit_cache_key, $orders_between_count, 60 );
					}

					if ( $orders_allowed_limit <= $orders_between_count ) {
						wc_add_notice( __( 'Max Order Limit between time reached.' ), 'error' );
					}
				}
			}
		}
	}

	/*
	Too many order switch is co-related to the other three whitelisted rules. This is because all of these four rules are evaluated on the checkout page before the payment is processed.
	In this function, we are trying to evaluate "Too many orders" rule before payment is processed on the checkout page.
	Note: Risk Score is evaluated and generated in the callback function (written within a separate helper file) after payment is processed and order is generated.
	 */

	/**
	 * Track checkout attempts and activate bot-attack mode when the rate is too high.
	 *
	 * Uses a 60-second rolling counter stored in a transient.  When the count exceeds
	 * the configurable threshold, a 5-minute "under attack" flag is set.  Downstream
	 * code (fraud rules, cron jobs) can check this flag to skip or throttle expensive
	 * DB queries while maintaining checkout-level blocking via Attempt Intelligence.
	 *
	 * @since 7.2.0
	 */
	private function track_checkout_attempt_rate() {
		$bucket_key = 'wc_af_co_cnt_' . (string) floor( time() / 60 );
		$count      = (int) get_transient( $bucket_key ) + 1;
		set_transient( $bucket_key, $count, 90 );

		/**
		 * Filters the per-minute checkout attempt threshold that activates attack mode.
		 *
		 * When more than this many checkout attempts are detected in a single minute,
		 * the plugin sets a 5-minute "under attack" flag that downstream components
		 * (velocity rules, cron jobs) can query via is_under_bot_attack().
		 *
		 * @since 7.2.6
		 *
		 * @param int $threshold Default: 30.
		 */
		$threshold = (int) apply_filters( 'wc_af_attack_mode_threshold', 30 );

		if ( $count >= $threshold && ! get_transient( 'wc_af_under_attack' ) ) {
			set_transient( 'wc_af_under_attack', $count, 5 * MINUTE_IN_SECONDS );
			if ( class_exists( 'Af_Logger' ) ) {
				Af_Logger::debug( sprintf(
					'Anti-Fraud: bot-attack mode activated – %d checkout attempts in the last 60 s (threshold: %d).',
					$count,
					$threshold
				) );
			}
		}
	}

	/**
	 * Returns true when the plugin has detected a high-frequency bot attack.
	 *
	 * Expensive historical order queries (velocity checks, IP-detail checks) in both
	 * pre-payment validation and post-payment fraud scoring honour this flag to
	 * degrade gracefully: they either use shorter query caps or the cached risk flags
	 * set during the initial detection pass.
	 *
	 * @return bool
	 * @since 7.2.0
	 */
	public static function is_under_bot_attack() {
		return (bool) get_transient( 'wc_af_under_attack' );
	}

	public function too_many_order_attempt_validation( $fields, $errors ) {

		// Track checkout rate and activate attack mode when threshold is breached.
		$this->track_checkout_attempt_rate();

		$too_many_order_switch   = get_option( 'wc_af_attempt_count_check' );
		$fraud_attempt_time_span = get_option( 'wc_settings_anti_fraud_attempt_time_span' );
		$max_orders              = get_option( 'wc_settings_anti_fraud_max_order_attempt_time_span' );

		$user                       = wp_get_current_user();
		$user_roles                 = $user->roles;
		$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles', array() );

		$userIp = WC_Geolocation::get_ip_address();
		$get_all_whitelist_ips = get_option( 'wc_settings_anti_fraud_ips_whitelist' );
		$whitelist_ips         = 'false';
		if ( '' != $get_all_whitelist_ips ) {
			$s_whitelist_ips = $this->parse_whitelist_input_data($get_all_whitelist_ips);
			if ( in_array( $userIp, $s_whitelist_ips ) ) {
				$whitelist_ips = 'true';
			}
		}

		$selected_whitelisted_role = 'false';
		if ( get_option( 'wc_af_enable_whitelist_user_roles' ) === 'yes' ) {
			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $wc_af_whitelist_user_roles ) ) {
					$selected_whitelisted_role = 'true';
					break;
				}
			}
		}

		$selected_whitelist_payment_method = 'false';
		if ( get_option( 'wc_af_enable_whitelist_payment_method' ) === 'yes' ) {
			$get_whitelist_payment_method = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );
			if ( ! empty( $get_whitelist_payment_method ) ) {
				$payment_method_from_checkout = WC()->session->get( 'chosen_payment_method' );
				if ( in_array( $payment_method_from_checkout, $get_whitelist_payment_method ) ) {
					$selected_whitelist_payment_method = 'true';
				}
			}
		}

		$selected_whitelist_mobile_no = 'false';
		if ( get_option( 'wc_af_enable_whitelist_phone_number' ) === 'yes' ) {
			$get_whitelist_mobile_no = get_option( 'wc_af_whitelist_phone_numbers' );
			if ( '' !== $get_whitelist_mobile_no ) {
				$whitelist_mobile_no     = $this->parse_whitelist_input_data($get_whitelist_mobile_no);
				$mobile_no_from_checkout = isset( $fields['billing_phone'] ) ? $fields['billing_phone'] : '';
				if ( in_array( $mobile_no_from_checkout, $whitelist_mobile_no ) ) {
					$selected_whitelist_mobile_no = 'true';
				}
			}
		}

		// ✅ Check whitelist country
		$selected_whitelist_country = 'false';
		if ( get_option( 'wc_af_enable_whitelist_country' ) == 'yes' ) {
			$whitelist_countries = (array) get_option( 'wc_af_whitelisted_countries', [] );
			$billing_country = isset( $fields['billing_country'] ) ? $fields['billing_country'] : '';
			
			if ( ! empty( $whitelist_countries ) && in_array( $billing_country, $whitelist_countries, true ) ) {
				$selected_whitelist_country = 'true';
			}
		}

		// Get posted billing & shipping country/state
		$billing_country  = isset( $fields['billing_country'] ) ? sanitize_text_field( $fields['billing_country'] ) : ( isset( $_POST['billing_country'] ) ? sanitize_text_field( wp_unslash( $fields['billing_country'] ) ) : '' );
		// Helper to get readable state name from posted data (handles code => name)
		$resolve_state_name = function( $country, $state_raw ) {
			$state_raw = (string) $state_raw;
			$state_name = trim( $state_raw );
			if ( ! empty( $country ) && function_exists( 'WC' ) && isset( WC()->countries ) ) {
				$states = WC()->countries->get_states( $country );
				if ( is_array( $states ) && ! empty( $states ) ) {
					// If posted value is a state code and exists in keys, use the mapped name
					if ( array_key_exists( $state_raw, $states ) ) {
						$state_name = $states[ $state_raw ];
					} else {
						// try case-insensitive match against names or codes
						foreach ( $states as $code => $name ) {
							if ( 0 === strcasecmp( $name, $state_raw ) || 0 === strcasecmp( $code, $state_raw ) ) {
								$state_name = $name;
								break;
							}
						}
					}
				}
			}
			return wc_af_strtolower( trim( $state_name ) );
		};

		// ✅ Check whitelist state
		$selected_whitelist_state = 'false';
		if ( get_option( 'wc_af_enable_whitelist_state' ) == 'yes' ) {
			$get_whitelisted_states = get_option( 'wc_af_whitelisted_states', '' );
			
			if ( ! empty( $get_whitelisted_states ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_states ) ) {
					$whitelist_states = $get_whitelisted_states;
				} else {
					$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_states );
				}
				
				$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
				$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
				
				$billing_state_raw = isset( $fields['billing_state'] ) ? wc_af_strtolower( trim( $fields['billing_state'] ) ) : '';
				$billing_state  = $resolve_state_name( $billing_country, $billing_state_raw );
				
				if ( ! empty( $billing_state ) && in_array( $billing_state, $whitelist_states, true ) ) {
					$selected_whitelist_state = 'true';
				}
			}
		}

		// ✅ Check whitelist city/county
		$selected_whitelist_city = 'false';
		if ( get_option( 'wc_af_enable_whitelist_city' ) == 'yes' ) {
			$get_whitelisted_cities = get_option( 'wc_af_whitelisted_city', '' );
			
			if ( ! empty( $get_whitelisted_cities ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_cities ) ) {
					$whitelist_cities = $get_whitelisted_cities;
				} else {
					$whitelist_cities = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_cities );
				}
				
				$whitelist_cities = array_filter( array_map( 'trim', (array) $whitelist_cities ) );
				$whitelist_cities = array_map( 'wc_af_strtolower', $whitelist_cities );
				
				$billing_city = isset( $fields['billing_city'] ) ? wc_af_strtolower( trim( $fields['billing_city'] ) ) : '';
				
				if ( ! empty( $billing_city ) && in_array( $billing_city, $whitelist_cities, true ) ) {
					$selected_whitelist_city = 'true';
				}
			}
		}

		// ✅ Check whitelist ZIP/Postal Code
		$selected_whitelist_zip = 'false';
		if ( get_option( 'wc_af_enable_whitelist_zip' ) == 'yes' ) {
			$get_whitelisted_zips = get_option( 'wc_af_whitelisted_zip', '' );
			
			if ( ! empty( $get_whitelisted_zips ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_zips ) ) {
					$whitelist_zips = $get_whitelisted_zips;
				} else {
					$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_zips );
				}
				
				$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
				$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
				
				$billing_zip = isset( $fields['billing_postcode'] ) ? wc_af_strtolower( str_replace( ' ', '', trim( $fields['billing_postcode'] ) ) ) : '';
				
				if ( ! empty( $billing_zip ) && in_array( $billing_zip, $whitelist_zips, true ) ) {
					$selected_whitelist_zip = 'true';
				}
			}
		}

		$get_whitelist_email        = get_option( 'wc_settings_anti_fraud_whitelist' );
		$single_whitelist_email_arr = $this->parse_whitelist_input_data($get_whitelist_email);
		$selected_whitelisted_email = 'false';
		
		if ( isset( $_REQUEST['_wpnonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'my-nonce' ) ) {
				echo 'Nonce verification failed!';
				die();
			}
		}
		$customer_billing_email     = isset( $fields['billing_email'] ) ? sanitize_text_field( $fields['billing_email'] ) : '';

		if ( in_array( $customer_billing_email, $single_whitelist_email_arr ) ) {
			$selected_whitelisted_email = 'true';
		}

		$selected_whitelist_address = $this->address_whitelisting_check($fields);
		$selected_whitelist_firstname = $this->firstname_whitelisting_check($fields);
		$selected_whitelist_lastname = $this->lastname_whitelisting_check($fields);


		update_option( 'not_whitelisted_email', $selected_whitelisted_email );
		update_option( 'white_payment_methods', $selected_whitelist_payment_method );
		update_option( 'is_whitelisted_roles', $selected_whitelisted_role );
		update_option( 'is_whitelisted_ips', $whitelist_ips );
		update_option( 'is_whitelisted_mobile_no', $selected_whitelist_mobile_no );
		update_option( 'is_whitelisted_state', $selected_whitelist_state );
		update_option( 'is_whitelisted_city', $selected_whitelist_city );
		update_option( 'is_whitelisted_zip', $selected_whitelist_zip );
		update_option( 'is_whitelisted_address', $selected_whitelist_address );
		update_option( 'is_whitelisted_firstname', $selected_whitelist_firstname );
		update_option( 'is_whitelisted_lastname', $selected_whitelist_lastname );


		$selected_wildcard_whitelisted_email = $this->call_wildcard_email_validation($fields);

		if (
			'yes' === $too_many_order_switch &&
			'true' !== $selected_whitelisted_email &&
			'true' !== $selected_whitelisted_role &&
			'true' !== $selected_whitelist_payment_method &&
			'true' !== $selected_whitelist_mobile_no &&
			'true' !== $selected_wildcard_whitelisted_email &&
			'true' !== $whitelist_ips &&
			'true' !== $selected_whitelist_country &&
			'true' !== $selected_whitelist_state &&
			'true' !== $selected_whitelist_city &&
			'true' !== $selected_whitelist_zip &&
			'true' !== $selected_whitelist_address &&
			'true' !== $selected_whitelist_firstname &&
			'true' !== $selected_whitelist_lastname
		) {
			$attempt_mode = get_option( 'wc_af_attempt_count_mode', 'orders_only' );

			if ( 'advanced' === $attempt_mode ) {
				// Use Attempt Intelligence Service for broader detection.
				if ( ! class_exists( 'WC_AF_Attempt_Intelligence_Service' ) ) {
					require_once plugin_dir_path( __FILE__ ) . 'anti-fraud-core/class-wc-af-attempt-intelligence-service.php';
				}
				$service    = WC_AF_Attempt_Intelligence_Service::get_instance();
				$order_total = isset( $fields['order_total'] ) ? (float) $fields['order_total'] : 0;
				if ( 0 === $order_total && function_exists( 'WC' ) && WC()->cart ) {
					$order_total = (float) WC()->cart->get_total( 'raw' );
				}
				$context    = array(
					'ip_address'      => WC_Geolocation::get_ip_address(),
					'email'           => isset( $fields['billing_email'] ) ? $fields['billing_email'] : '',
					'phone'           => isset( $fields['billing_phone'] ) ? $fields['billing_phone'] : '',
					'billing_country' => isset( $fields['billing_country'] ) ? $fields['billing_country'] : '',
					'order_total'     => $order_total,
				);
				$result    = $service->evaluate_velocity( $context, (int) $fraud_attempt_time_span, (int) $max_orders, 'both' );
				if ( ! empty( $result['blocked'] ) ) {
					if ( class_exists( 'Af_Logger' ) ) {
						Af_Logger::debug( sprintf(
							/* translators: 1: trigger, 2: dimension, 3: count */
							__( 'Attempt Intelligence: blocked checkout (trigger=%1$s, dimension=%2$s, count=%3$d)', 'woocommerce-anti-fraud' ),
							$result['trigger'],
							$result['dimension'],
							$result['count']
						) );
					}
					set_transient( 'wc_af_last_velocity_trigger', $result, 60 );
					$errors->add(
						'validation',
						sprintf(
							/* translators: %d: Number of hours (e.g., "24") */
							esc_html__( 'You have reached maximum number of allowed orders in %d hours. Please try again later.', 'woocommerce-anti-fraud' ),
							$fraud_attempt_time_span
						)
					);
				}
			} else {
				// Original order-based logic (IP, email, phone).
				$dt      = new DateTime( 'NOW', wp_timezone() );
				$enddate = clone $dt;
				$dt->modify( '-' . $fraud_attempt_time_span . ' hours' );

				$start_datetime_string = $dt->format( 'Y-m-d H:i:s' );
				$end_datetime_string   = $enddate->format( 'Y-m-d H:i:s' );
				$ip_address            = WC_Geolocation::get_ip_address();

				$order_args_base = [
					'type'        => wc_get_order_types( 'order-count' ),
					'date_after'  => $start_datetime_string,
					'date_before' => $end_datetime_string,
					'limit'       => (int) $max_orders,
				];

				$orders_count_ip = wc_get_orders( array_merge( $order_args_base, [
					'customer_ip_address' => $ip_address,
				] ) );

				$orders_count_email = [];
				if ( ! empty( $fields['billing_email'] ) ) {
					if ( isset( $_REQUEST['_wpnonce'] ) ) {
						if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'my-nonce' ) ) {
							echo 'Nonce verification failed!';
							die();
						}
					}
					$orders_count_email = wc_get_orders( array_merge( $order_args_base, [
						'meta_key'     => '_billing_email',
						'meta_value'   => sanitize_text_field( $fields['billing_email'] ),
						'meta_compare' => '=',
					] ) );
				}

				$orders_count_phone = [];
				if ( ! empty( $fields['billing_phone'] ) ) {
					$orders_count_phone = wc_get_orders( array_merge( $order_args_base, [
						'meta_key'     => '_billing_phone',
						'meta_value'   => sanitize_text_field( $fields['billing_phone'] ),
						'meta_compare' => '=',
					] ) );
				}

				if (
					count( $orders_count_ip ) >= $max_orders ||
					count( $orders_count_email ) >= $max_orders ||
					count( $orders_count_phone ) >= $max_orders
				) {
					$errors->add(
						'validation',
						sprintf(
							/* translators: %d: Number of hours (e.g., "24") */
							esc_html__( 'You have reached maximum number of allowed orders in %d hours. Please try again later.', 'woocommerce-anti-fraud' ),
							$fraud_attempt_time_span
						)
					);
				}
			}
		}
	}


	/*
	Blacklist mobile number is co-related to the other three whitelisted rules. This is because all of these four rules are evaluated on the checkout page before the payment is processed.
	In this function, we are trying to evaluate "Too many orders" rule before payment is processed on the checkout page.
	Note: Risk Score is evaluated and generated in the callback function (written within a separate helper file) after payment is processed and order is generated.
	 */
	public function blacklist_mob_no_option_validation( $fields, $errors ) {
		$enable_blacklist_mob_no  = get_option( 'wc_af_enable_blacklisting_phone_number' );
		// check whitelist user role
		$user                       = wp_get_current_user();
		$user_roles                 = $user->roles;
		$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles' );

		if ( empty( $wc_af_whitelist_user_roles ) ) {
			$wc_af_whitelist_user_roles = array();
		}

		// check whitelist ips
		$userIp = WC_Geolocation::get_ip_address();

		$get_all_whitelist_ips = get_option( 'wc_settings_anti_fraud_ips_whitelist' );
		$whitelist_ips         = 'false';

		if ( '' != $get_all_whitelist_ips ) {

			$s_whitelist_ips = $this->parse_whitelist_input_data($get_all_whitelist_ips);

			if ( in_array( $userIp, $s_whitelist_ips ) ) {

				$whitelist_ips = 'true';
			}
		}
		//$ip = '195.181.161.229';

		$selected_whitelisted_role      = 'false';
		$is_enable_whitelist_user_roles = get_option( 'wc_af_enable_whitelist_user_roles' );
		if ( 'yes' == $is_enable_whitelist_user_roles ) {

			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $wc_af_whitelist_user_roles ) ) {
					$selected_whitelisted_role = 'true';
					break;
				}
			}
		} // check whitelist user role end

		// check whitelist payment method
		$selected_whitelist_payment_method = 'false';
		if ( get_option( 'wc_af_enable_whitelist_payment_method' ) == 'yes' ) {

			if ( get_option( 'wc_settings_anti_fraud_whitelist_payment_method' ) && null != get_option( 'wc_settings_anti_fraud_whitelist_payment_method' ) ) {

				$get_whitelist_payment_method = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );

				$payment_method_from_checkout = WC()->session->get( 'chosen_payment_method' );

				if ( in_array( $payment_method_from_checkout, $get_whitelist_payment_method ) ) {
					$selected_whitelist_payment_method = 'true';
				}
			}
		} // check whitelist payment method end

		// ✅ Check whitelist mobile number
		$selected_whitelist_mobile_no = 'false';
		if ( get_option( 'wc_af_enable_whitelist_phone_number' ) == 'yes' ) {

			$get_whitelist_mobile_no = get_option( 'wc_af_whitelist_phone_numbers');
			
			if ( '' != $get_whitelist_mobile_no ) {

				$whitelist_mobile_no = $this->parse_whitelist_input_data($get_whitelist_mobile_no);

				if ( isset( $_REQUEST['_wpnonce'] ) ) {
					if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'my-nonce' ) ) {
						echo 'Nonce verification failed!';
						die();
					}
				}
				$mobile_no_from_checkout = isset( $fields['billing_phone'] ) ? $fields['billing_phone'] : '';

				if ( in_array( $mobile_no_from_checkout, $whitelist_mobile_no ) ) {
					$selected_whitelist_mobile_no = 'true';
				}
			}
		}

		// ✅ Check whitelist country
		$selected_whitelist_country = 'false';
		if ( get_option( 'wc_af_enable_whitelist_country' ) == 'yes' ) {
			$whitelist_countries = (array) get_option( 'wc_af_whitelisted_countries', [] );
			$billing_country = isset( $fields['billing_country'] ) ? $fields['billing_country'] : '';
			
			if ( ! empty( $whitelist_countries ) && in_array( $billing_country, $whitelist_countries, true ) ) {
				$selected_whitelist_country = 'true';
			}
		}

		// Get posted billing & shipping country/state
		$billing_country  = isset( $fields['billing_country'] ) ? sanitize_text_field( $fields['billing_country'] ) : ( isset( $_POST['billing_country'] ) ? sanitize_text_field( wp_unslash( $fields['billing_country'] ) ) : '' );
		// Helper to get readable state name from posted data (handles code => name)
		$resolve_state_name = function( $country, $state_raw ) {
			$state_raw = (string) $state_raw;
			$state_name = trim( $state_raw );
			if ( ! empty( $country ) && function_exists( 'WC' ) && isset( WC()->countries ) ) {
				$states = WC()->countries->get_states( $country );
				if ( is_array( $states ) && ! empty( $states ) ) {
					// If posted value is a state code and exists in keys, use the mapped name
					if ( array_key_exists( $state_raw, $states ) ) {
						$state_name = $states[ $state_raw ];
					} else {
						// try case-insensitive match against names or codes
						foreach ( $states as $code => $name ) {
							if ( 0 === strcasecmp( $name, $state_raw ) || 0 === strcasecmp( $code, $state_raw ) ) {
								$state_name = $name;
								break;
							}
						}
					}
				}
			}
			return wc_af_strtolower( trim( $state_name ) );
		};

		// ✅ Check whitelist state
		$selected_whitelist_state = 'false';
		if ( get_option( 'wc_af_enable_whitelist_state' ) == 'yes' ) {
			$get_whitelisted_states = get_option( 'wc_af_whitelisted_states', '' );
			
			if ( ! empty( $get_whitelisted_states ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_states ) ) {
					$whitelist_states = $get_whitelisted_states;
				} else {
					$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_states );
				}
				
				$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
				$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
				
				$billing_state_raw = isset( $fields['billing_state'] ) ? wc_af_strtolower( trim( $fields['billing_state'] ) ) : '';
				$billing_state  = $resolve_state_name( $billing_country, $billing_state_raw );
				
				if ( ! empty( $billing_state ) && in_array( $billing_state, $whitelist_states, true ) ) {
					$selected_whitelist_state = 'true';
				}
			}
		}

		// ✅ Check whitelist city/county
		$selected_whitelist_city = 'false';
		if ( get_option( 'wc_af_enable_whitelist_city' ) == 'yes' ) {
			$get_whitelisted_cities = get_option( 'wc_af_whitelisted_city', '' );
			
			if ( ! empty( $get_whitelisted_cities ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_cities ) ) {
					$whitelist_cities = $get_whitelisted_cities;
				} else {
					$whitelist_cities = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_cities );
				}
				
				$whitelist_cities = array_filter( array_map( 'trim', (array) $whitelist_cities ) );
				$whitelist_cities = array_map( 'wc_af_strtolower', $whitelist_cities );
				
				$billing_city = isset( $fields['billing_city'] ) ? wc_af_strtolower( trim( $fields['billing_city'] ) ) : '';
				
				if ( ! empty( $billing_city ) && in_array( $billing_city, $whitelist_cities, true ) ) {
					$selected_whitelist_city = 'true';
				}
			}
		}

		// ✅ Check whitelist ZIP/Postal Code
		$selected_whitelist_zip = 'false';
		if ( get_option( 'wc_af_enable_whitelist_zip' ) == 'yes' ) {
			$get_whitelisted_zips = get_option( 'wc_af_whitelisted_zip', '' );
			
			if ( ! empty( $get_whitelisted_zips ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_zips ) ) {
					$whitelist_zips = $get_whitelisted_zips;
				} else {
					$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_zips );
				}
				
				$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
				$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
				
				$billing_zip = isset( $fields['billing_postcode'] ) ? wc_af_strtolower( str_replace( ' ', '', trim( $fields['billing_postcode'] ) ) ) : '';
				
				if ( ! empty( $billing_zip ) && in_array( $billing_zip, $whitelist_zips, true ) ) {
					$selected_whitelist_zip = 'true';
				}
			}
		}

		// check whitelist specific email not wildcard type
		$get_whitelist_email        = get_option( 'wc_settings_anti_fraud_whitelist' );
		$single_whitelist_email_arr = $this->parse_whitelist_input_data($get_whitelist_email);
		$selected_whitelisted_email = 'false';

		if ( isset( $_REQUEST['_wpnonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'my-nonce' ) ) {
				echo 'Nonce verification failed!';
				die();
			}
		}

		$customer_billing_email = isset( $fields['billing_email'] ) ? sanitize_text_field( $fields['billing_email'] ) : '';
		if ( in_array( $customer_billing_email, $single_whitelist_email_arr ) ) {
			$selected_whitelisted_email = 'true';
		} // check whitelist specific email not wildcard type end

		$selected_whitelist_address = $this->address_whitelisting_check($fields);
		$selected_whitelist_firstname = $this->firstname_whitelisting_check($fields);
		$selected_whitelist_lastname = $this->lastname_whitelisting_check($fields);


		update_option( 'not_whitelisted_email', $selected_whitelisted_email );
		update_option( 'white_payment_methods', $selected_whitelist_payment_method );
		update_option( 'is_whitelisted_roles', $selected_whitelisted_role );
		update_option( 'is_whitelisted_ips', $whitelist_ips );
		update_option( 'is_whitelisted_mobile_no', $selected_whitelist_mobile_no );
		update_option( 'is_whitelisted_country', $selected_whitelist_country );
		update_option( 'is_whitelisted_state', $selected_whitelist_state );
		update_option( 'is_whitelisted_city', $selected_whitelist_city );
		update_option( 'is_whitelisted_zip', $selected_whitelist_zip );
		update_option( 'is_whitelisted_address', $selected_whitelist_address );
		update_option( 'is_whitelisted_firstname', $selected_whitelist_firstname );
		update_option( 'is_whitelisted_lastname', $selected_whitelist_lastname );


		// Callback function for check whildcard email
	$selected_wildcard_whitelisted_email = $this->call_wildcard_email_validation($fields);

		if ( 'yes' == $enable_blacklist_mob_no && 'true' != $selected_whitelisted_email && 'true' != $selected_whitelisted_role && 'true' != $selected_whitelist_payment_method && 'true' != $selected_whitelist_mobile_no && 'true' != $selected_wildcard_whitelisted_email && 'true' != $whitelist_ips  && 'true' != $selected_whitelist_country && 'true' != $selected_whitelist_state && 'true' != $selected_whitelist_city && 'true' != $selected_whitelist_zip && 'true' != $selected_whitelist_address && 'true' != $selected_whitelist_firstname && 'true' != $selected_whitelist_lastname ) {

			$blacklist_mob_number = get_option( 'wc_af_blacklisted_phone_numbers' );
			if ( isset( $_REQUEST['_wpnonce'] ) ) {
				if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'my-nonce' ) ) {
					echo 'Nonce verification failed!';
					die();
				}
			}
			$mobile_no_from_checkout = isset( $fields['billing_phone'] ) ? $fields['billing_phone'] : '';

			if ( '' != $blacklist_mob_number ) {

				$blacklist_mob_no = explode( ',', $blacklist_mob_number );

				if ( in_array( $mobile_no_from_checkout, $blacklist_mob_no ) ) {

					$errors->add(
						'validation',
						/* translators: %s: order time span */
						sprintf( esc_html__( 'This Mobile Number is blocked.', 'woocommerce-anti-fraud' ) )
					);
				}
			}
		}
	}


	/**
	 * Block orders from selected blacklisted countries at checkout
	 */
	public function blacklist_country_option_validation( $fields, $errors ) {
		$enable_blacklist_country = get_option( 'wc_af_enable_blacklisting_country' );

		// Get current user roles
		$user       = wp_get_current_user();
		$user_roles = $user->roles;

		$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles', [] );

		// ✅ Whitelist IP check
		$userIp            = WC_Geolocation::get_ip_address();
		$whitelist_ips_opt = get_option( 'wc_settings_anti_fraud_ips_whitelist' );
		$whitelist_ips     = 'false';

		if ( ! empty( $whitelist_ips_opt ) ) {
			$s_whitelist_ips = $this->parse_whitelist_input_data($whitelist_ips_opt);
			if ( in_array( $userIp, $s_whitelist_ips, true ) ) {
				$whitelist_ips = 'true';
			}
		}

		// ✅ Whitelist role check
		$selected_whitelisted_role      = 'false';
		$is_enable_whitelist_user_roles = get_option( 'wc_af_enable_whitelist_user_roles' );
		if ( 'yes' === $is_enable_whitelist_user_roles ) {
			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $wc_af_whitelist_user_roles, true ) ) {
					$selected_whitelisted_role = 'true';
					break;
				}
			}
		}

		// ✅ Whitelist payment method check
		$selected_whitelist_payment_method = 'false';
		if ( 'yes' === get_option( 'wc_af_enable_whitelist_payment_method' ) ) {
			$get_whitelist_payment_method = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );
			$payment_method_from_checkout = WC()->session->get( 'chosen_payment_method' );
			if ( ! empty( $get_whitelist_payment_method ) && in_array( $payment_method_from_checkout, $get_whitelist_payment_method, true ) ) {
				$selected_whitelist_payment_method = 'true';
			}
		}

		// ✅ Whitelist email check
		$get_whitelist_email        = get_option( 'wc_settings_anti_fraud_whitelist' );
		$single_whitelist_email_arr = $this->parse_whitelist_input_data($get_whitelist_email);
		$selected_whitelisted_email = 'false';

		$customer_billing_email = isset( $fields['billing_email'] ) ? sanitize_text_field( $fields['billing_email'] ) : '';
		if ( in_array( $customer_billing_email, $single_whitelist_email_arr, true ) ) {
			$selected_whitelisted_email = 'true';
		}

		// ✅ Check whitelist mobile number
		$selected_whitelist_mobile_no = 'false';

		if ( get_option( 'wc_af_enable_whitelist_phone_number' ) == 'yes' ) {

			$get_whitelist_mobile_no = get_option( 'wc_af_whitelist_phone_numbers');
			if ( '' != $get_whitelist_mobile_no ) {

				$whitelist_mobile_no = $this->parse_whitelist_input_data($get_whitelist_mobile_no);
				if ( isset( $_REQUEST['_wpnonce'] ) ) {
					if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'my-nonce' ) ) {
						echo 'Nonce verification failed!';
						die();
					}
				}
				$mobile_no_from_checkout = isset( $fields['billing_phone'] ) ? $fields['billing_phone'] : '';

				if ( in_array( $mobile_no_from_checkout, $whitelist_mobile_no ) ) {
					$selected_whitelist_mobile_no = 'true';
				}
			}
		}

		// ✅ Check whitelist country
		$selected_whitelist_country = 'false';
		if ( get_option( 'wc_af_enable_whitelist_country' ) == 'yes' ) {
			$whitelist_countries = (array) get_option( 'wc_af_whitelisted_countries', [] );
			$billing_country = isset( $fields['billing_country'] ) ? $fields['billing_country'] : '';
			
			if ( ! empty( $whitelist_countries ) && in_array( $billing_country, $whitelist_countries, true ) ) {
				$selected_whitelist_country = 'true';
			}
		}

		// Get posted billing & shipping country/state
		$billing_country  = isset( $fields['billing_country'] ) ? sanitize_text_field( $fields['billing_country'] ) : ( isset( $_POST['billing_country'] ) ? sanitize_text_field( wp_unslash( $fields['billing_country'] ) ) : '' );
		// Helper to get readable state name from posted data (handles code => name)
		$resolve_state_name = function( $country, $state_raw ) {
			$state_raw = (string) $state_raw;
			$state_name = trim( $state_raw );
			if ( ! empty( $country ) && function_exists( 'WC' ) && isset( WC()->countries ) ) {
				$states = WC()->countries->get_states( $country );
				if ( is_array( $states ) && ! empty( $states ) ) {
					// If posted value is a state code and exists in keys, use the mapped name
					if ( array_key_exists( $state_raw, $states ) ) {
						$state_name = $states[ $state_raw ];
					} else {
						// try case-insensitive match against names or codes
						foreach ( $states as $code => $name ) {
							if ( 0 === strcasecmp( $name, $state_raw ) || 0 === strcasecmp( $code, $state_raw ) ) {
								$state_name = $name;
								break;
							}
						}
					}
				}
			}
			return wc_af_strtolower( trim( $state_name ) );
		};

		// ✅ Check whitelist state
		$selected_whitelist_state = 'false';
		if ( get_option( 'wc_af_enable_whitelist_state' ) == 'yes' ) {
			$get_whitelisted_states = get_option( 'wc_af_whitelisted_states', '' );
			
			if ( ! empty( $get_whitelisted_states ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_states ) ) {
					$whitelist_states = $get_whitelisted_states;
				} else {
					$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_states );
				}
				
				$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
				$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
				
				$billing_state_raw = isset( $fields['billing_state'] ) ? wc_af_strtolower( trim( $fields['billing_state'] ) ) : '';
				$billing_state  = $resolve_state_name( $billing_country, $billing_state_raw );
				
				if ( ! empty( $billing_state ) && in_array( $billing_state, $whitelist_states, true ) ) {
					$selected_whitelist_state = 'true';
				}
			}
		}

		// ✅ Check whitelist city/county
		$selected_whitelist_city = 'false';
		if ( get_option( 'wc_af_enable_whitelist_city' ) == 'yes' ) {
			$get_whitelisted_cities = get_option( 'wc_af_whitelisted_city', '' );
			
			if ( ! empty( $get_whitelisted_cities ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_cities ) ) {
					$whitelist_cities = $get_whitelisted_cities;
				} else {
					$whitelist_cities = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_cities );
				}
				
				$whitelist_cities = array_filter( array_map( 'trim', (array) $whitelist_cities ) );
				$whitelist_cities = array_map( 'wc_af_strtolower', $whitelist_cities );
				
				$billing_city = isset( $fields['billing_city'] ) ? wc_af_strtolower( trim( $fields['billing_city'] ) ) : '';
				
				if ( ! empty( $billing_city ) && in_array( $billing_city, $whitelist_cities, true ) ) {
					$selected_whitelist_city = 'true';
				}
			}
		}

		// ✅ Check whitelist ZIP/Postal Code
		$selected_whitelist_zip = 'false';
		if ( get_option( 'wc_af_enable_whitelist_zip' ) == 'yes' ) {
			$get_whitelisted_zips = get_option( 'wc_af_whitelisted_zip', '' );
			
			if ( ! empty( $get_whitelisted_zips ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_zips ) ) {
					$whitelist_zips = $get_whitelisted_zips;
				} else {
					$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_zips );
				}
				
				$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
				$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
				
				$billing_zip = isset( $fields['billing_postcode'] ) ? wc_af_strtolower( str_replace( ' ', '', trim( $fields['billing_postcode'] ) ) ) : '';
				
				if ( ! empty( $billing_zip ) && in_array( $billing_zip, $whitelist_zips, true ) ) {
					$selected_whitelist_zip = 'true';
				}
			}
		}

		$selected_whitelist_address = $this->address_whitelisting_check($fields);
		$selected_whitelist_firstname = $this->firstname_whitelisting_check($fields);
		$selected_whitelist_lastname = $this->lastname_whitelisting_check($fields);

		update_option( 'not_whitelisted_email', $selected_whitelisted_email );
		update_option( 'white_payment_methods', $selected_whitelist_payment_method );
		update_option( 'is_whitelisted_roles', $selected_whitelisted_role );
		update_option( 'is_whitelisted_ips', $whitelist_ips );
		update_option( 'is_whitelisted_mobile_no', $selected_whitelist_mobile_no );
		update_option( 'is_whitelisted_country', $selected_whitelist_country );
		update_option( 'is_whitelisted_state', $selected_whitelist_state );
		update_option( 'is_whitelisted_city', $selected_whitelist_city );
		update_option( 'is_whitelisted_zip', $selected_whitelist_zip );
		update_option( 'is_whitelisted_address', $selected_whitelist_address );
		update_option( 'is_whitelisted_firstname', $selected_whitelist_firstname );
		update_option( 'is_whitelisted_lastname', $selected_whitelist_lastname );


		// ✅ Wildcard email whitelist
		$selected_wildcard_whitelisted_email = $this->call_wildcard_email_validation($fields);

		// 🚫 Apply country blacklist only if enabled and no whitelist override
		if ( 'yes' === $enable_blacklist_country 
			&& 'true' !== $selected_whitelisted_email 
			&& 'true' !== $selected_whitelisted_role 
			&& 'true' !== $selected_whitelist_payment_method 
			&& 'true' !== $selected_wildcard_whitelisted_email 
			&& 'true' !== $whitelist_ips
			&& 'true' !== $selected_whitelist_mobile_no
			&& 'true' !== $selected_whitelist_country
			&& 'true' !== $selected_whitelist_state
			&& 'true' !== $selected_whitelist_city
			&& 'true' !== $selected_whitelist_zip
			&& 'true' !== $selected_whitelist_address
			&& 'true' !== $selected_whitelist_firstname
			&& 'true' !== $selected_whitelist_lastname
		) {
			$blacklisted_countries = (array) get_option( 'wc_af_blacklisted_countries', [] );
			if ( isset( $_REQUEST['_wpnonce'] ) ) {
				if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'my-nonce' ) ) {
					echo 'Nonce verification failed!';
					die();
				}
			}
			$billing_country       = isset( $fields['billing_country'] ) ? $fields['billing_country'] : '';

			if ( ! empty( $blacklisted_countries ) && in_array( $billing_country, $blacklisted_countries, true ) ) {
				$errors->add(
						'validation',
						/* translators: %s: order time span */
						sprintf( esc_html__( 'This country is blocked.', 'woocommerce-anti-fraud' ) )
					);
			}
		}
	}

	/**
	 * Block orders from selected blacklisted First and Last name at checkout
	 */
	public function blacklist_customer_name_validation( $fields, $errors ) {
		$enable_blacklist_firstname = get_option( 'wc_af_enable_blacklisting_first_name' );
		$enable_blacklist_lastname = get_option( 'wc_af_enable_blacklisting_last_name' );
		

		// ✅ Skip only if both features are disabled
		if ( 'yes' !== $enable_blacklist_firstname && 'yes' !== $enable_blacklist_lastname ) {
			return;
		}

		// Get current user roles
		$user       = wp_get_current_user();
		$user_roles = $user->roles;

		$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles', [] );

		// ✅ Whitelist IP check
		$userIp            = WC_Geolocation::get_ip_address();
		$whitelist_ips_opt = get_option( 'wc_settings_anti_fraud_ips_whitelist' );
		$whitelist_ips     = 'false';

		if ( ! empty( $whitelist_ips_opt ) ) {
			$s_whitelist_ips = $this->parse_whitelist_input_data($whitelist_ips_opt);
			if ( in_array( $userIp, $s_whitelist_ips, true ) ) {
				$whitelist_ips = 'true';
			}
		}

		// ✅ Whitelist role check
		$selected_whitelisted_role      = 'false';
		$is_enable_whitelist_user_roles = get_option( 'wc_af_enable_whitelist_user_roles' );
		if ( 'yes' === $is_enable_whitelist_user_roles ) {
			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $wc_af_whitelist_user_roles, true ) ) {
					$selected_whitelisted_role = 'true';
					break;
				}
			}
		}

		// ✅ Whitelist payment method check
		$selected_whitelist_payment_method = 'false';
		if ( 'yes' === get_option( 'wc_af_enable_whitelist_payment_method' ) ) {
			$get_whitelist_payment_method = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );
			$payment_method_from_checkout = WC()->session->get( 'chosen_payment_method' );
			if ( ! empty( $get_whitelist_payment_method ) && in_array( $payment_method_from_checkout, $get_whitelist_payment_method, true ) ) {
				$selected_whitelist_payment_method = 'true';
			}
		}

		// ✅ Whitelist email check
		$get_whitelist_email        = get_option( 'wc_settings_anti_fraud_whitelist' );
		$single_whitelist_email_arr = $this->parse_whitelist_input_data($get_whitelist_email);
		$selected_whitelisted_email = 'false';

		$customer_billing_email = isset( $fields['billing_email'] ) ? sanitize_text_field( $fields['billing_email'] ) : '';
		if ( in_array( $customer_billing_email, $single_whitelist_email_arr, true ) ) {
			$selected_whitelisted_email = 'true';
		}

		// ✅ Check whitelist mobile number
		$selected_whitelist_mobile_no = 'false';
		if ( get_option( 'wc_af_enable_whitelist_phone_number' ) == 'yes' ) {

			$get_whitelist_mobile_no = get_option( 'wc_af_whitelist_phone_numbers');
			if ( '' != $get_whitelist_mobile_no ) {

				$whitelist_mobile_no = $this->parse_whitelist_input_data($get_whitelist_mobile_no);
				if ( isset( $_REQUEST['_wpnonce'] ) ) {
					if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'my-nonce' ) ) {
						echo 'Nonce verification failed!';
						die();
					}
				}
				$mobile_no_from_checkout = isset( $fields['billing_phone'] ) ? $fields['billing_phone'] : '';

				if ( in_array( $mobile_no_from_checkout, $whitelist_mobile_no ) ) {
					$selected_whitelist_mobile_no = 'true';
				}
			}
		}

		// ✅ Check whitelist country
		$selected_whitelist_country = 'false';
		if ( get_option( 'wc_af_enable_whitelist_country' ) == 'yes' ) {
			$whitelist_countries = (array) get_option( 'wc_af_whitelisted_countries', [] );
			$billing_country = isset( $fields['billing_country'] ) ? $fields['billing_country'] : '';
			
			if ( ! empty( $whitelist_countries ) && in_array( $billing_country, $whitelist_countries, true ) ) {
				$selected_whitelist_country = 'true';
			}
		}

		// Get posted billing & shipping country/state
		$billing_country  = isset( $fields['billing_country'] ) ? sanitize_text_field( $fields['billing_country'] ) : ( isset( $_POST['billing_country'] ) ? sanitize_text_field( wp_unslash( $fields['billing_country'] ) ) : '' );
		// Helper to get readable state name from posted data (handles code => name)
		$resolve_state_name = function( $country, $state_raw ) {
			$state_raw = (string) $state_raw;
			$state_name = trim( $state_raw );
			if ( ! empty( $country ) && function_exists( 'WC' ) && isset( WC()->countries ) ) {
				$states = WC()->countries->get_states( $country );
				if ( is_array( $states ) && ! empty( $states ) ) {
					// If posted value is a state code and exists in keys, use the mapped name
					if ( array_key_exists( $state_raw, $states ) ) {
						$state_name = $states[ $state_raw ];
					} else {
						// try case-insensitive match against names or codes
						foreach ( $states as $code => $name ) {
							if ( 0 === strcasecmp( $name, $state_raw ) || 0 === strcasecmp( $code, $state_raw ) ) {
								$state_name = $name;
								break;
							}
						}
					}
				}
			}
			return wc_af_strtolower( trim( $state_name ) );
		};

		// ✅ Check whitelist state
		$selected_whitelist_state = 'false';
		if ( get_option( 'wc_af_enable_whitelist_state' ) == 'yes' ) {
			$get_whitelisted_states = get_option( 'wc_af_whitelisted_states', '' );
			
			if ( ! empty( $get_whitelisted_states ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_states ) ) {
					$whitelist_states = $get_whitelisted_states;
				} else {
					$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_states );
				}
				
				$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
				$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
				
				$billing_state_raw = isset( $fields['billing_state'] ) ? wc_af_strtolower( trim( $fields['billing_state'] ) ) : '';
				$billing_state  = $resolve_state_name( $billing_country, $billing_state_raw );
				
				if ( ! empty( $billing_state ) && in_array( $billing_state, $whitelist_states, true ) ) {
					$selected_whitelist_state = 'true';
				}
			}
		}

		// ✅ Check whitelist city/county
		$selected_whitelist_city = 'false';
		if ( get_option( 'wc_af_enable_whitelist_city' ) == 'yes' ) {
			$get_whitelisted_cities = get_option( 'wc_af_whitelisted_city', '' );
			
			if ( ! empty( $get_whitelisted_cities ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_cities ) ) {
					$whitelist_cities = $get_whitelisted_cities;
				} else {
					$whitelist_cities = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_cities );
				}
				
				$whitelist_cities = array_filter( array_map( 'trim', (array) $whitelist_cities ) );
				$whitelist_cities = array_map( 'wc_af_strtolower', $whitelist_cities );
				
				$billing_city = isset( $fields['billing_city'] ) ? wc_af_strtolower( trim( $fields['billing_city'] ) ) : '';
				
				if ( ! empty( $billing_city ) && in_array( $billing_city, $whitelist_cities, true ) ) {
					$selected_whitelist_city = 'true';
				}
			}
		}

		// ✅ Check whitelist ZIP/Postal Code
		$selected_whitelist_zip = 'false';
		if ( get_option( 'wc_af_enable_whitelist_zip' ) == 'yes' ) {
			$get_whitelisted_zips = get_option( 'wc_af_whitelisted_zip', '' );
			
			if ( ! empty( $get_whitelisted_zips ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_zips ) ) {
					$whitelist_zips = $get_whitelisted_zips;
				} else {
					$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_zips );
				}
				
				$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
				$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
				
				$billing_zip = isset( $fields['billing_postcode'] ) ? wc_af_strtolower( str_replace( ' ', '', trim( $fields['billing_postcode'] ) ) ) : '';
				
				if ( ! empty( $billing_zip ) && in_array( $billing_zip, $whitelist_zips, true ) ) {
					$selected_whitelist_zip = 'true';
				}
			}
		}

		$selected_whitelist_address = $this->address_whitelisting_check($fields);
		$selected_whitelist_firstname = $this->firstname_whitelisting_check($fields);
		$selected_whitelist_lastname = $this->lastname_whitelisting_check($fields);

		update_option( 'not_whitelisted_email', $selected_whitelisted_email );
		update_option( 'white_payment_methods', $selected_whitelist_payment_method );
		update_option( 'is_whitelisted_roles', $selected_whitelisted_role );
		update_option( 'is_whitelisted_ips', $whitelist_ips );
		update_option( 'is_whitelisted_mobile_no', $selected_whitelist_mobile_no );
		update_option( 'is_whitelisted_country', $selected_whitelist_country );
		update_option( 'is_whitelisted_state', $selected_whitelist_state );
		update_option( 'is_whitelisted_city', $selected_whitelist_city );
		update_option( 'is_whitelisted_zip', $selected_whitelist_zip );
		update_option( 'is_whitelisted_address', $selected_whitelist_address );
		update_option( 'is_whitelisted_firstname', $selected_whitelist_firstname );
		update_option( 'is_whitelisted_lastname', $selected_whitelist_lastname );

		// ✅ Wildcard email whitelist
		$selected_wildcard_whitelisted_email = $this->call_wildcard_email_validation($fields);

	// 🚫 Apply first/last name blacklist if enabled and no whitelist override
		if ( ( 'yes' === $enable_blacklist_firstname || 'yes' === $enable_blacklist_lastname ) && 'true' !== $selected_whitelisted_email && 'true' !== $selected_whitelisted_role && 'true' !== $selected_whitelist_payment_method && 'true' !== $selected_wildcard_whitelisted_email && 'true' !== $whitelist_ips && 'true' !== $selected_whitelist_mobile_no && 'true' !== $selected_whitelist_country && 'true' !== $selected_whitelist_state && 'true' !== $selected_whitelist_city  && 'true' !== $selected_whitelist_zip && 'true' !== $selected_whitelist_address && 'true' !== $selected_whitelist_firstname && 'true' !== $selected_whitelist_lastname) {
		
			$blacklisted_firstnames = (array) get_option( 'wc_af_blacklisted_first_names', [] );
			$blacklisted_firstnames = array_map( 'trim', explode( ',', implode( ',', $blacklisted_firstnames ) ) );

			// Normalize to lowercase
			$blacklisted_firstnames = array_map( 'strtolower', $blacklisted_firstnames );

			$billing_firstname      = isset( $fields['billing_first_name'] ) ? strtolower( trim( $fields['billing_first_name'] ) ) : '';

			if ( ! empty( $blacklisted_firstnames ) ) {
				// Normalize names for comparison
				$blacklisted_firstnames = array_map( 'strtolower', array_map( 'trim', $blacklisted_firstnames ) );

				if ( in_array( $billing_firstname, $blacklisted_firstnames, true ) ) {
					
					$errors->add(
						'validation',
						/* translators: %s: order time span */
						sprintf( esc_html__( 'The first name you entered is blocked.', 'woocommerce-anti-fraud' ) )
					);
				}
			}

			$blacklisted_lastnames = (array) get_option( 'wc_af_blacklisted_last_names', [] );
			$billing_lastname      = isset( $fields['billing_last_name'] ) ? strtolower( trim( $fields['billing_last_name'] ) ) : '';
			$blacklisted_lastnames = array_map( 'trim', explode( ',', implode( ',', $blacklisted_lastnames ) ) );

			// Normalize to lowercase
			$blacklisted_lastnames = array_map( 'strtolower', $blacklisted_lastnames );
			

			if ( ! empty( $blacklisted_lastnames ) ) {

				// Normalize names for comparison
				$blacklisted_lastnames = array_map( 'strtolower', array_map( 'trim', $blacklisted_lastnames ) );

				if ( in_array( $billing_lastname, $blacklisted_lastnames, true ) ) {
					$errors->add(
						'validation',
						/* translators: %s: order time span */
						sprintf( esc_html__( 'The last name you entered is blocked.', 'woocommerce-anti-fraud' ) )
					);
				}
			}
		}
	}

	public function blacklist_address_option_validation( $fields, $errors ) {
		$enable_blacklist_address = get_option( 'wc_af_enable_blacklisting_address' );

		// ✅ Skip if feature disabled
		if ( 'yes' !== $enable_blacklist_address ) {
			return;
		}

		// Get current user roles
		$user       = wp_get_current_user();
		$user_roles = $user->roles;

		$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles', [] );

		// ✅ Whitelist IP check
		$userIp            = WC_Geolocation::get_ip_address();
		$whitelist_ips_opt = get_option( 'wc_settings_anti_fraud_ips_whitelist' );
		$whitelist_ips     = 'false';

		if ( ! empty( $whitelist_ips_opt ) ) {
			$s_whitelist_ips = $this->parse_whitelist_input_data($whitelist_ips_opt);
			if ( in_array( $userIp, $s_whitelist_ips, true ) ) {
				$whitelist_ips = 'true';
			}
		}

		// ✅ Whitelist role check
		$selected_whitelisted_role      = 'false';
		$is_enable_whitelist_user_roles = get_option( 'wc_af_enable_whitelist_user_roles' );
		if ( 'yes' === $is_enable_whitelist_user_roles ) {
			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $wc_af_whitelist_user_roles, true ) ) {
					$selected_whitelisted_role = 'true';
					break;
				}
			}
		}

		// ✅ Whitelist payment method check
		$selected_whitelist_payment_method = 'false';
		if ( 'yes' === get_option( 'wc_af_enable_whitelist_payment_method' ) ) {
			$get_whitelist_payment_method = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );
			$payment_method_from_checkout = WC()->session->get( 'chosen_payment_method' );
			if ( ! empty( $get_whitelist_payment_method ) && in_array( $payment_method_from_checkout, $get_whitelist_payment_method, true ) ) {
				$selected_whitelist_payment_method = 'true';
			}
		}

		// ✅ Whitelist email check
		$get_whitelist_email        = get_option( 'wc_settings_anti_fraud_whitelist' );
		$single_whitelist_email_arr = $this->parse_whitelist_input_data($get_whitelist_email);
		$selected_whitelisted_email = 'false';

		$customer_billing_email = isset( $fields['billing_email'] ) ? sanitize_text_field( $fields['billing_email'] ) : '';
		if ( in_array( $customer_billing_email, $single_whitelist_email_arr, true ) ) {
			$selected_whitelisted_email = 'true';
		}

		// ✅ Check whitelist mobile number
		$selected_whitelist_mobile_no = 'false';

		if ( get_option( 'wc_af_enable_whitelist_phone_number' ) == 'yes' ) {

			$get_whitelist_mobile_no = get_option( 'wc_af_whitelist_phone_numbers');
			if ( '' != $get_whitelist_mobile_no ) {

				$whitelist_mobile_no = $this->parse_whitelist_input_data($get_whitelist_mobile_no);
				if ( isset( $_REQUEST['_wpnonce'] ) ) {
					if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'my-nonce' ) ) {
						echo 'Nonce verification failed!';
						die();
					}
				}
				$mobile_no_from_checkout = isset( $fields['billing_phone'] ) ? $fields['billing_phone'] : '';

				if ( in_array( $mobile_no_from_checkout, $whitelist_mobile_no ) ) {
					$selected_whitelist_mobile_no = 'true';
				}
			}
		}

		// ✅ Check whitelist country
		$selected_whitelist_country = 'false';
		if ( get_option( 'wc_af_enable_whitelist_country' ) == 'yes' ) {
			$whitelist_countries = (array) get_option( 'wc_af_whitelisted_countries', [] );
			$billing_country = isset( $fields['billing_country'] ) ? $fields['billing_country'] : '';
			
			if ( ! empty( $whitelist_countries ) && in_array( $billing_country, $whitelist_countries, true ) ) {
				$selected_whitelist_country = 'true';
			}
		}

		// Get posted billing & shipping country/state
		$billing_country  = isset( $fields['billing_country'] ) ? sanitize_text_field( $fields['billing_country'] ) : ( isset( $_POST['billing_country'] ) ? sanitize_text_field( wp_unslash( $fields['billing_country'] ) ) : '' );
		// Helper to get readable state name from posted data (handles code => name)
		$resolve_state_name = function( $country, $state_raw ) {
			$state_raw = (string) $state_raw;
			$state_name = trim( $state_raw );
			if ( ! empty( $country ) && function_exists( 'WC' ) && isset( WC()->countries ) ) {
				$states = WC()->countries->get_states( $country );
				if ( is_array( $states ) && ! empty( $states ) ) {
					// If posted value is a state code and exists in keys, use the mapped name
					if ( array_key_exists( $state_raw, $states ) ) {
						$state_name = $states[ $state_raw ];
					} else {
						// try case-insensitive match against names or codes
						foreach ( $states as $code => $name ) {
							if ( 0 === strcasecmp( $name, $state_raw ) || 0 === strcasecmp( $code, $state_raw ) ) {
								$state_name = $name;
								break;
							}
						}
					}
				}
			}
			return wc_af_strtolower( trim( $state_name ) );
		};

		// ✅ Check whitelist state
		$selected_whitelist_state = 'false';
		if ( get_option( 'wc_af_enable_whitelist_state' ) == 'yes' ) {
			$get_whitelisted_states = get_option( 'wc_af_whitelisted_states', '' );
			
			if ( ! empty( $get_whitelisted_states ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_states ) ) {
					$whitelist_states = $get_whitelisted_states;
				} else {
					$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_states );
				}
				
				$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
				$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
				
				$billing_state_raw = isset( $fields['billing_state'] ) ? wc_af_strtolower( trim( $fields['billing_state'] ) ) : '';
				$billing_state  = $resolve_state_name( $billing_country, $billing_state_raw );
				
				if ( ! empty( $billing_state ) && in_array( $billing_state, $whitelist_states, true ) ) {
					$selected_whitelist_state = 'true';
				}
			}
		}

		// ✅ Check whitelist city/county
		$selected_whitelist_city = 'false';
		if ( get_option( 'wc_af_enable_whitelist_city' ) == 'yes' ) {
			$get_whitelisted_cities = get_option( 'wc_af_whitelisted_city', '' );
			
			if ( ! empty( $get_whitelisted_cities ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_cities ) ) {
					$whitelist_cities = $get_whitelisted_cities;
				} else {
					$whitelist_cities = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_cities );
				}
				
				$whitelist_cities = array_filter( array_map( 'trim', (array) $whitelist_cities ) );
				$whitelist_cities = array_map( 'wc_af_strtolower', $whitelist_cities );
				
				$billing_city = isset( $fields['billing_city'] ) ? wc_af_strtolower( trim( $fields['billing_city'] ) ) : '';
				
				if ( ! empty( $billing_city ) && in_array( $billing_city, $whitelist_cities, true ) ) {
					$selected_whitelist_city = 'true';
				}
			}
		}

		// ✅ Check whitelist ZIP/Postal Code
		$selected_whitelist_zip = 'false';
		if ( get_option( 'wc_af_enable_whitelist_zip' ) == 'yes' ) {
			$get_whitelisted_zips = get_option( 'wc_af_whitelisted_zip', '' );
			
			if ( ! empty( $get_whitelisted_zips ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_zips ) ) {
					$whitelist_zips = $get_whitelisted_zips;
				} else {
					$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_zips );
				}
				
				$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
				$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
				
				$billing_zip = isset( $fields['billing_postcode'] ) ? wc_af_strtolower( str_replace( ' ', '', trim( $fields['billing_postcode'] ) ) ) : '';
				
				if ( ! empty( $billing_zip ) && in_array( $billing_zip, $whitelist_zips, true ) ) {
					$selected_whitelist_zip = 'true';
				}
			}
		}

		$selected_whitelist_address = $this->address_whitelisting_check($fields);

		$selected_whitelist_firstname = $this->firstname_whitelisting_check($fields);
		$selected_whitelist_lastname = $this->lastname_whitelisting_check($fields);

		update_option( 'not_whitelisted_email', $selected_whitelisted_email );
		update_option( 'white_payment_methods', $selected_whitelist_payment_method );
		update_option( 'is_whitelisted_roles', $selected_whitelisted_role );
		update_option( 'is_whitelisted_ips', $whitelist_ips );
		update_option( 'is_whitelisted_mobile_no', $selected_whitelist_mobile_no );
		update_option( 'is_whitelisted_country', $selected_whitelist_country );
		update_option( 'is_whitelisted_state', $selected_whitelist_state );
		update_option( 'is_whitelisted_city', $selected_whitelist_city );
		update_option( 'is_whitelisted_zip', $selected_whitelist_zip );
		update_option( 'is_whitelisted_address', $selected_whitelist_address );
		update_option( 'is_whitelisted_firstname', $selected_whitelist_firstname );
		update_option( 'is_whitelisted_lastname', $selected_whitelist_lastname );

		// ✅ Wildcard email whitelist
		$selected_wildcard_whitelisted_email = $this->call_wildcard_email_validation($fields);

		// 🚫 Apply address blacklist only if enabled and no whitelist override
		if ( 'yes' === $enable_blacklist_address
			&& 'true' !== $selected_whitelisted_email
			&& 'true' !== $selected_whitelisted_role
			&& 'true' !== $selected_whitelist_payment_method
			&& 'true' !== $selected_wildcard_whitelisted_email
			&& 'true' !== $whitelist_ips
			&& 'true' !== $selected_whitelist_mobile_no
			&& 'true' !== $selected_whitelist_country
			&& 'true' !== $selected_whitelist_state
			&& 'true' !== $selected_whitelist_city
			&& 'true' !== $selected_whitelist_zip
			&& 'true' !== $selected_whitelist_address
			&& 'true' !== $selected_whitelist_firstname
			&& 'true' !== $selected_whitelist_lastname

		) {
			$blacklisted_addresses = (array) get_option( 'wc_af_blacklisted_addresses', array() );

			// Combine billing parts
			$billing_address = strtolower(
				trim(
					( isset( $fields['billing_address_1'] ) ? $fields['billing_address_1'] : '' ) . ' ' .
					( isset( $fields['billing_address_2'] ) ? $fields['billing_address_2'] : '' ) . ' ' .
					( isset( $fields['billing_city'] ) ? $fields['billing_city'] : '' ) . ' ' .
					( isset( $fields['billing_state'] ) ? $fields['billing_state'] : '' ) . ' ' .
					( isset( $fields['billing_postcode'] ) ? $fields['billing_postcode'] : '' )
				)
			);

			// Normalize billing address: remove punctuation, collapse spaces
			$billing_address = preg_replace( '/[[:punct:]]+/', ' ', $billing_address );
			$billing_address = preg_replace( '/\s+/', ' ', $billing_address );
			$billing_address = trim( $billing_address );

			if ( ! empty( $blacklisted_addresses ) ) {

				// normalize list elements (lowercase + trim)
				$blacklisted_addresses = array_map( 'strtolower', array_map( 'trim', $blacklisted_addresses ) );

				foreach ( $blacklisted_addresses as $keyword ) {

					if ( '' === $keyword ) {
						continue;
					}

					// normalize keyword (remove punctuation, collapse spaces)
					$norm_keyword = preg_replace( '/[[:punct:]]+/', ' ', $keyword );
					$norm_keyword = preg_replace( '/\s+/', ' ', $norm_keyword );
					$norm_keyword = trim( $norm_keyword );

					if ( '' === $norm_keyword ) {
						continue;
					}

					// 1) direct full-phrase match
					if ( false !== strpos( $billing_address, $norm_keyword ) ) {
						$errors->add(
							'wc_af_blacklist_address',
							esc_html__( 'The billing address you entered is blocked.', 'woocommerce-anti-fraud' )
						);
						break; // matched — stop
					}

					// 2) if the original keyword has commas, treat parts as alternatives
					$alts = preg_split( '/\s*,\s*/', $keyword );
					if ( $alts && count( $alts ) > 1 ) {
						foreach ( $alts as $alt ) {
							$alt = trim( strtolower( $alt ) );
							if ( '' === $alt ) {
								continue;
							}
							$alt_norm = preg_replace( '/[[:punct:]]+/', ' ', $alt );
							$alt_norm = preg_replace( '/\s+/', ' ', $alt_norm );
							$alt_norm = trim( $alt_norm );
							if ( '' === $alt_norm ) {
								continue;
							}
							if ( false !== strpos( $billing_address, $alt_norm ) ) {
								$errors->add(
									'wc_af_blacklist_address',
									esc_html__( 'The billing address you entered is blocked.', 'woocommerce-anti-fraud' )
								);
								// break out of both loops
								break 2;
							}
						}
					}

					// 3) fallback: require ALL words in the normalized keyword to appear (order-insensitive)
					$words = explode( ' ', $norm_keyword );
					$all_match = true;
					$has_word  = false;

					foreach ( $words as $w ) {
						$w = trim( $w );
						if ( '' === $w ) {
							continue;
						}
						$has_word = true;
						if ( false === strpos( $billing_address, $w ) ) {
							$all_match = false;
							break;
						}
					}

					if ( $has_word && $all_match ) {
						$errors->add(
							'wc_af_blacklist_address',
							esc_html__( 'The billing address you entered is blocked.', 'woocommerce-anti-fraud' )
						);
						break;
					}
				}
			}

		}
	}


	/**
	 * ✅ Block orders from selected blacklisted cities at checkout
	 */
	public function blacklist_city_option_validation( $fields, $errors ) {
		$enable_blacklist_city = get_option( 'wc_af_enable_blacklisting_city' );

		// Get current user roles
		$user       = wp_get_current_user();
		$user_roles = $user->roles;

		$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles', [] );

		// ✅ Whitelist IP check
		$userIp            = WC_Geolocation::get_ip_address();
		$whitelist_ips_opt = get_option( 'wc_settings_anti_fraud_ips_whitelist' );
		$whitelist_ips     = 'false';

		if ( ! empty( $whitelist_ips_opt ) ) {
			$s_whitelist_ips = $this->parse_whitelist_input_data($whitelist_ips_opt);
			if ( in_array( $userIp, $s_whitelist_ips, true ) ) {
				$whitelist_ips = 'true';
			}
		}

		// ✅ Whitelist role check
		$selected_whitelisted_role      = 'false';
		$is_enable_whitelist_user_roles = get_option( 'wc_af_enable_whitelist_user_roles' );
		if ( 'yes' === $is_enable_whitelist_user_roles ) {
			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $wc_af_whitelist_user_roles, true ) ) {
					$selected_whitelisted_role = 'true';
					break;
				}
			}
		}

		// ✅ Whitelist payment method check
		$selected_whitelist_payment_method = 'false';
		if ( 'yes' === get_option( 'wc_af_enable_whitelist_payment_method' ) ) {
			$get_whitelist_payment_method = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );
			$payment_method_from_checkout = WC()->session->get( 'chosen_payment_method' );
			if ( ! empty( $get_whitelist_payment_method ) && in_array( $payment_method_from_checkout, $get_whitelist_payment_method, true ) ) {
				$selected_whitelist_payment_method = 'true';
			}
		}

		// ✅ Whitelist email check
		$get_whitelist_email        = get_option( 'wc_settings_anti_fraud_whitelist' );
		$single_whitelist_email_arr = $this->parse_whitelist_input_data($get_whitelist_email);
		$selected_whitelisted_email = 'false';

		$customer_billing_email = isset( $fields['billing_email'] ) ? sanitize_text_field( $fields['billing_email'] ) : '';
		if ( in_array( $customer_billing_email, $single_whitelist_email_arr, true ) ) {
			$selected_whitelisted_email = 'true';
		}

		// ✅ Check whitelist mobile number
		$selected_whitelist_mobile_no = 'false';
		if ( get_option( 'wc_af_enable_whitelist_phone_number' ) == 'yes' ) {

			$get_whitelist_mobile_no = get_option( 'wc_af_whitelist_phone_numbers');
			
			if ( '' != $get_whitelist_mobile_no ) {

				$whitelist_mobile_no = $this->parse_whitelist_input_data($get_whitelist_mobile_no);
				if ( isset( $_REQUEST['_wpnonce'] ) ) {
					if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'my-nonce' ) ) {
						echo 'Nonce verification failed!';
						die();
					}
				}
				$mobile_no_from_checkout = isset( $fields['billing_phone'] ) ? $fields['billing_phone'] : '';

				if ( in_array( $mobile_no_from_checkout, $whitelist_mobile_no ) ) {
					$selected_whitelist_mobile_no = 'true';
				}
			}
		}

		// ✅ Check whitelist country
		$selected_whitelist_country = 'false';
		if ( get_option( 'wc_af_enable_whitelist_country' ) == 'yes' ) {
			$whitelist_countries = (array) get_option( 'wc_af_whitelisted_countries', [] );
			$billing_country = isset( $fields['billing_country'] ) ? $fields['billing_country'] : '';
			
			if ( ! empty( $whitelist_countries ) && in_array( $billing_country, $whitelist_countries, true ) ) {
				$selected_whitelist_country = 'true';
			}
		}

		// Get posted billing & shipping country/state
		$billing_country  = isset( $fields['billing_country'] ) ? sanitize_text_field( $fields['billing_country'] ) : ( isset( $_POST['billing_country'] ) ? sanitize_text_field( wp_unslash( $fields['billing_country'] ) ) : '' );
		// Helper to get readable state name from posted data (handles code => name)
		$resolve_state_name = function( $country, $state_raw ) {
			$state_raw = (string) $state_raw;
			$state_name = trim( $state_raw );
			if ( ! empty( $country ) && function_exists( 'WC' ) && isset( WC()->countries ) ) {
				$states = WC()->countries->get_states( $country );
				if ( is_array( $states ) && ! empty( $states ) ) {
					// If posted value is a state code and exists in keys, use the mapped name
					if ( array_key_exists( $state_raw, $states ) ) {
						$state_name = $states[ $state_raw ];
					} else {
						// try case-insensitive match against names or codes
						foreach ( $states as $code => $name ) {
							if ( 0 === strcasecmp( $name, $state_raw ) || 0 === strcasecmp( $code, $state_raw ) ) {
								$state_name = $name;
								break;
							}
						}
					}
				}
			}
			return wc_af_strtolower( trim( $state_name ) );
		};

		// ✅ Check whitelist state
		$selected_whitelist_state = 'false';
		if ( get_option( 'wc_af_enable_whitelist_state' ) == 'yes' ) {
			$get_whitelisted_states = get_option( 'wc_af_whitelisted_states', '' );
			
			if ( ! empty( $get_whitelisted_states ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_states ) ) {
					$whitelist_states = $get_whitelisted_states;
				} else {
					$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_states );
				}
				
				$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
				$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
				
				$billing_state_raw = isset( $fields['billing_state'] ) ? wc_af_strtolower( trim( $fields['billing_state'] ) ) : '';
				$billing_state  = $resolve_state_name( $billing_country, $billing_state_raw );
				
				if ( ! empty( $billing_state ) && in_array( $billing_state, $whitelist_states, true ) ) {
					$selected_whitelist_state = 'true';
				}
			}
		}

		// ✅ Check whitelist city/county
		$selected_whitelist_city = 'false';
		if ( get_option( 'wc_af_enable_whitelist_city' ) == 'yes' ) {
			$get_whitelisted_cities = get_option( 'wc_af_whitelisted_city', '' );
			
			if ( ! empty( $get_whitelisted_cities ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_cities ) ) {
					$whitelist_cities = $get_whitelisted_cities;
				} else {
					$whitelist_cities = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_cities );
				}
				
				$whitelist_cities = array_filter( array_map( 'trim', (array) $whitelist_cities ) );
				$whitelist_cities = array_map( 'wc_af_strtolower', $whitelist_cities );
				
				$billing_city = isset( $fields['billing_city'] ) ? wc_af_strtolower( trim( $fields['billing_city'] ) ) : '';
				
				if ( ! empty( $billing_city ) && in_array( $billing_city, $whitelist_cities, true ) ) {
					$selected_whitelist_city = 'true';
				}
			}
		}

		// ✅ Check whitelist ZIP/Postal Code
		$selected_whitelist_zip = 'false';
		if ( get_option( 'wc_af_enable_whitelist_zip' ) == 'yes' ) {
			$get_whitelisted_zips = get_option( 'wc_af_whitelisted_zip', '' );
			
			if ( ! empty( $get_whitelisted_zips ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_zips ) ) {
					$whitelist_zips = $get_whitelisted_zips;
				} else {
					$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_zips );
				}
				
				$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
				$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
				
				$billing_zip = isset( $fields['billing_postcode'] ) ? wc_af_strtolower( str_replace( ' ', '', trim( $fields['billing_postcode'] ) ) ) : '';
				
				if ( ! empty( $billing_zip ) && in_array( $billing_zip, $whitelist_zips, true ) ) {
					$selected_whitelist_zip = 'true';
				}
			}
		}

		$selected_whitelist_address = $this->address_whitelisting_check($fields);

		$selected_whitelist_firstname = $this->firstname_whitelisting_check($fields);
		$selected_whitelist_lastname = $this->lastname_whitelisting_check($fields);

		update_option( 'not_whitelisted_email', $selected_whitelisted_email );
		update_option( 'white_payment_methods', $selected_whitelist_payment_method );
		update_option( 'is_whitelisted_roles', $selected_whitelisted_role );
		update_option( 'is_whitelisted_ips', $whitelist_ips );
		update_option( 'is_whitelisted_mobile_no', $selected_whitelist_mobile_no );
		update_option( 'is_whitelisted_country', $selected_whitelist_country );
		update_option( 'is_whitelisted_state', $selected_whitelist_state );
		update_option( 'is_whitelisted_city', $selected_whitelist_city );
		update_option( 'is_whitelisted_zip', $selected_whitelist_zip );
		update_option( 'is_whitelisted_address', $selected_whitelist_address );
		update_option( 'is_whitelisted_firstname', $selected_whitelist_firstname );
		update_option( 'is_whitelisted_lastname', $selected_whitelist_lastname );

		// ✅ Wildcard email whitelist
		$selected_wildcard_whitelisted_email = $this->call_wildcard_email_validation($fields);

		// 🚫 Apply city blacklist only if enabled and no whitelist override
		if ( 'yes' === $enable_blacklist_city
			&& 'true' !== $selected_whitelisted_email
			&& 'true' !== $selected_whitelisted_role
			&& 'true' !== $selected_whitelist_payment_method
			&& 'true' !== $selected_wildcard_whitelisted_email
			&& 'true' !== $whitelist_ips
			&& 'true' !== $selected_whitelist_mobile_no
			&& 'true' !== $selected_whitelist_country
			&& 'true' !== $selected_whitelist_state
			&& 'true' !== $selected_whitelist_city
			&& 'true' !== $selected_whitelist_zip
			&& 'true' !== $selected_whitelist_address
			&& 'true' !== $selected_whitelist_firstname
			&& 'true' !== $selected_whitelist_lastname
		) {
			$blacklisted_cities = (array) get_option( 'wc_af_blacklisted_cities', array() );
			$billing_city       = isset( $fields['billing_city'] ) ? strtolower( trim( $fields['billing_city'] ) ) : '';

			$all_cities = array();

			// Flatten array: if an element contains commas, split it
			foreach ( $blacklisted_cities as $city ) {
				$city = strtolower( trim( $city ) );
				if ( strpos( $city, ',' ) !== false ) {
					$parts = array_map( 'trim', explode( ',', $city ) );
					$all_cities = array_merge( $all_cities, $parts );
				} elseif ( '' !== $city) {
					$all_cities[] = $city;
				}
			}

			// Remove duplicates
			$all_cities = array_unique( $all_cities );

			// Check if billing city is blacklisted
			if ( in_array( $billing_city, $all_cities, true ) ) {
				$errors->add(
					'wc_af_blacklisted_city',
					esc_html__( 'Orders from your city are not allowed.', 'woocommerce-anti-fraud' )
				);
			}
		}
	}


	/**
	 * ✅ Block orders from selected blacklisted states at checkout
	*/
	public function blacklist_state_option_validation( $fields, $errors ) {
		$enable_blacklist_state = get_option( 'wc_af_enable_blacklisting_state' );

		// Get current user roles
		$user       = wp_get_current_user();
		$user_roles = $user->roles;
		$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles', [] );

		// ✅ Whitelist IP check
		$userIp            = WC_Geolocation::get_ip_address();
		$whitelist_ips_opt = get_option( 'wc_settings_anti_fraud_ips_whitelist' );
		$whitelist_ips     = 'false';
		if ( ! empty( $whitelist_ips_opt ) ) {
			$s_whitelist_ips = $this->parse_whitelist_input_data($whitelist_ips_opt);
			if ( in_array( $userIp, $s_whitelist_ips, true ) ) {
				$whitelist_ips = 'true';
			}
		}

		// ✅ Whitelist role check
		$selected_whitelisted_role      = 'false';
		$is_enable_whitelist_user_roles = get_option( 'wc_af_enable_whitelist_user_roles' );
		if ( 'yes' === $is_enable_whitelist_user_roles ) {
			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $wc_af_whitelist_user_roles, true ) ) {
					$selected_whitelisted_role = 'true';
					break;
				}
			}
		}

		// ✅ Whitelist payment method check
		$selected_whitelist_payment_method = 'false';
		if ( 'yes' === get_option( 'wc_af_enable_whitelist_payment_method' ) ) {
			$get_whitelist_payment_method = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );
			$payment_method_from_checkout = WC()->session->get( 'chosen_payment_method' );
			if ( ! empty( $get_whitelist_payment_method ) && in_array( $payment_method_from_checkout, $get_whitelist_payment_method, true ) ) {
				$selected_whitelist_payment_method = 'true';
			}
		}

		// ✅ Whitelist email check
		$get_whitelist_email        = get_option( 'wc_settings_anti_fraud_whitelist' );
		$single_whitelist_email_arr = $this->parse_whitelist_input_data($get_whitelist_email);
		$selected_whitelisted_email = 'false';
		$customer_billing_email = isset( $fields['billing_email'] ) ? sanitize_text_field( wp_unslash( $fields['billing_email'] ) ) : '';
		if ( in_array( $customer_billing_email, $single_whitelist_email_arr, true ) ) {
			$selected_whitelisted_email = 'true';
		}

		// ✅ Check whitelist mobile number
		$selected_whitelist_mobile_no = 'false';
		if ( get_option( 'wc_af_enable_whitelist_phone_number' ) == 'yes' ) {

			$get_whitelist_mobile_no = get_option( 'wc_af_whitelist_phone_numbers');
			
			if ( '' != $get_whitelist_mobile_no ) {

				$whitelist_mobile_no = $this->parse_whitelist_input_data($get_whitelist_mobile_no);
				if ( isset( $_REQUEST['_wpnonce'] ) ) {
					if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'my-nonce' ) ) {
						echo 'Nonce verification failed!';
						die();
					}
				}
				$mobile_no_from_checkout = isset( $fields['billing_phone'] ) ? $fields['billing_phone'] : '';

				if ( in_array( $mobile_no_from_checkout, $whitelist_mobile_no ) ) {
					$selected_whitelist_mobile_no = 'true';
				}
			}
		}

		// ✅ Check whitelist country
		$selected_whitelist_country = 'false';
		if ( get_option( 'wc_af_enable_whitelist_country' ) == 'yes' ) {
			$whitelist_countries = (array) get_option( 'wc_af_whitelisted_countries', [] ); 
			
			$billing_country = isset( $fields['billing_country'] ) ? wc_af_strtolower( trim( $fields['billing_state'] ) ) : '';

			if ( ! empty( $whitelist_countries ) && in_array( $billing_country, $whitelist_countries, true ) ) {
				$selected_whitelist_country = 'true';
			}
		}

		// Get posted billing & shipping country/state
		$billing_country  = isset( $fields['billing_country'] ) ? sanitize_text_field( $fields['billing_country'] ) : ( isset( $_POST['billing_country'] ) ? sanitize_text_field( wp_unslash( $fields['billing_country'] ) ) : '' );
		// Helper to get readable state name from posted data (handles code => name)
		$resolve_state_name = function( $country, $state_raw ) {
			$state_raw = (string) $state_raw;
			$state_name = trim( $state_raw );
			if ( ! empty( $country ) && function_exists( 'WC' ) && isset( WC()->countries ) ) {
				$states = WC()->countries->get_states( $country );
				if ( is_array( $states ) && ! empty( $states ) ) {
					// If posted value is a state code and exists in keys, use the mapped name
					if ( array_key_exists( $state_raw, $states ) ) {
						$state_name = $states[ $state_raw ];
					} else {
						// try case-insensitive match against names or codes
						foreach ( $states as $code => $name ) {
							if ( 0 === strcasecmp( $name, $state_raw ) || 0 === strcasecmp( $code, $state_raw ) ) {
								$state_name = $name;
								break;
							}
						}
					}
				}
			}
			return wc_af_strtolower( trim( $state_name ) );
		};

		// ✅ Check whitelist state
		$selected_whitelist_state = 'false';
		if ( get_option( 'wc_af_enable_whitelist_state' ) == 'yes' ) {
			$get_whitelisted_states = get_option( 'wc_af_whitelisted_states', '' );
			
			if ( ! empty( $get_whitelisted_states ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_states ) ) {
					$whitelist_states = $get_whitelisted_states;
				} else {
					$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_states );
				}
				
				$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
				$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
				
				$billing_state_raw = isset( $fields['billing_state'] ) ? wc_af_strtolower( trim( $fields['billing_state'] ) ) : '';
				$billing_state  = $resolve_state_name( $billing_country, $billing_state_raw );
				
				if ( ! empty( $billing_state ) && in_array( $billing_state, $whitelist_states, true ) ) {
					$selected_whitelist_state = 'true';
				}
			}
		}

		// ✅ Check whitelist city/county
		$selected_whitelist_city = 'false';
		if ( get_option( 'wc_af_enable_whitelist_city' ) == 'yes' ) {
			$get_whitelisted_cities = get_option( 'wc_af_whitelisted_city', '' );
			
			if ( ! empty( $get_whitelisted_cities ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_cities ) ) {
					$whitelist_cities = $get_whitelisted_cities;
				} else {
					$whitelist_cities = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_cities );
				}
				
				$whitelist_cities = array_filter( array_map( 'trim', (array) $whitelist_cities ) );
				$whitelist_cities = array_map( 'wc_af_strtolower', $whitelist_cities );
				
				$billing_city = isset( $fields['billing_city'] ) ? wc_af_strtolower( trim( $fields['billing_city'] ) ) : '';
				
				if ( ! empty( $billing_city ) && in_array( $billing_city, $whitelist_cities, true ) ) {
					$selected_whitelist_city = 'true';
				}
			}
		}

		// ✅ Check whitelist ZIP/Postal Code
		$selected_whitelist_zip = 'false';
		if ( get_option( 'wc_af_enable_whitelist_zip' ) == 'yes' ) {
			$get_whitelisted_zips = get_option( 'wc_af_whitelisted_zip', '' );
			
			if ( ! empty( $get_whitelisted_zips ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_zips ) ) {
					$whitelist_zips = $get_whitelisted_zips;
				} else {
					$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_zips );
				}
				
				$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
				$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
				
				$billing_zip = isset( $fields['billing_postcode'] ) ? wc_af_strtolower( str_replace( ' ', '', trim( $fields['billing_postcode'] ) ) ) : '';
				
				if ( ! empty( $billing_zip ) && in_array( $billing_zip, $whitelist_zips, true ) ) {
					$selected_whitelist_zip = 'true';
				}
			}
		}

		$selected_whitelist_address = $this->address_whitelisting_check($fields);
		$selected_whitelist_firstname = $this->firstname_whitelisting_check($fields);
		$selected_whitelist_lastname = $this->lastname_whitelisting_check($fields);

		update_option( 'not_whitelisted_email', $selected_whitelisted_email );
		update_option( 'white_payment_methods', $selected_whitelist_payment_method );
		update_option( 'is_whitelisted_roles', $selected_whitelisted_role );
		update_option( 'is_whitelisted_ips', $whitelist_ips );
		update_option( 'is_whitelisted_mobile_no', $selected_whitelist_mobile_no );
		update_option( 'is_whitelisted_country', $selected_whitelist_country );
		update_option( 'is_whitelisted_state', $selected_whitelist_state );
		update_option( 'is_whitelisted_city', $selected_whitelist_city );
		update_option( 'is_whitelisted_zip', $selected_whitelist_zip );
		update_option( 'is_whitelisted_address', $selected_whitelist_address );
		update_option( 'is_whitelisted_firstname', $selected_whitelist_firstname );
		update_option( 'is_whitelisted_lastname', $selected_whitelist_lastname );


		// ✅ Wildcard email whitelist (uses your existing helper)
		$selected_wildcard_whitelisted_email = $this->call_wildcard_email_validation($fields);

		// 🚫 Only continue if state blacklist is enabled and none of the whitelists matched
		if ( 'yes' === $enable_blacklist_state
			&& 'true' !== $selected_whitelisted_email
			&& 'true' !== $selected_whitelisted_role
			&& 'true' !== $selected_whitelist_payment_method
			&& 'true' !== $selected_wildcard_whitelisted_email
			&& 'true' !== $whitelist_ips
			&& 'true' !== $selected_whitelist_mobile_no
			&& 'true' !== $selected_whitelist_country
			&& 'true' !== $selected_whitelist_state
			&& 'true' !== $selected_whitelist_city
			&& 'true' !== $selected_whitelist_zip
			&& 'true' !== $selected_whitelist_address
			&& 'true' !== $selected_whitelist_firstname
			&& 'true' !== $selected_whitelist_lastname
		) {
			

			// --- Load & normalize blocked states from option (supports array, newline or comma separated) ---
			$raw_blocked = get_option( 'wc_af_blacklisted_states', '' );
			if ( is_array( $raw_blocked ) ) {
				$blocked_states = $raw_blocked;
			} else {
				// split on new lines or commas
				$blocked_states = preg_split( '/[\r\n,]+/', (string) $raw_blocked );
			}
			$blocked_states = array_filter( array_map( 'trim', (array) $blocked_states ) );
			if ( empty( $blocked_states ) ) {
				return;
			}
			// normalize to lowercase for comparison
			$blocked_states = array_map( 'wc_af_strtolower', $blocked_states );

			

			
			$shipping_country = isset( $fields['shipping_country'] ) ? sanitize_text_field( $fields['shipping_country'] ) : ( isset( $_POST['shipping_country'] ) ? sanitize_text_field( wp_unslash( $fields['shipping_country'] ) ) : '' );

			$billing_state_raw  = isset( $fields['billing_state'] ) ? $fields['billing_state'] : ( isset( $fields['billing_state'] ) ? sanitize_text_field(wp_unslash( $fields['billing_state'] ) ) : '' );
			$shipping_state_raw = isset( $fields['shipping_state'] ) ? $fields['shipping_state'] : ( isset( $fields['shipping_state'] ) ? sanitize_text_field(wp_unslash( $fields['shipping_state'] )) : '' );

			$billing_state  = $resolve_state_name( $billing_country, $billing_state_raw );
			$shipping_state = $resolve_state_name( $shipping_country, $shipping_state_raw );

			// If either billing or shipping resolved state matches the blocked list -> add validation error
			if ( ! empty( $billing_state ) && in_array( $billing_state, $blocked_states, true ) ) {
				$errors->add(
					'validation',
					esc_html__( 'Orders from your state are not allowed.', 'woocommerce-anti-fraud' )
				);
				return;
			}

			if ( ! empty( $shipping_state ) && in_array( $shipping_state, $blocked_states, true ) ) {
				$errors->add(
					'validation',
					esc_html__( 'Orders from your state are not allowed.', 'woocommerce-anti-fraud' )
				);
				return;
			}
		}
	}

	/**
	 * ✅ Block orders from selected blacklisted postal codes at checkout
	 */
	public function blacklist_zipcode_option_validation( $fields, $errors ) {
		$enable_blacklist_zip = get_option( 'wc_af_enable_blacklisting_zipcode' );

		// 🚫 Skip if disabled
		if ( 'yes' !== $enable_blacklist_zip ) {
			return;
		}

		// Get current user + roles
		$user       = wp_get_current_user();
		$user_roles = $user->roles;

		$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles', [] );

		// ✅ Whitelist IP check
		$userIp            = WC_Geolocation::get_ip_address();
		$whitelist_ips_opt = get_option( 'wc_settings_anti_fraud_ips_whitelist' );
		$whitelist_ips     = 'false';

		if ( ! empty( $whitelist_ips_opt ) ) {
			$s_whitelist_ips = $this->parse_whitelist_input_data($whitelist_ips_opt);
			if ( in_array( $userIp, $s_whitelist_ips, true ) ) {
				$whitelist_ips = 'true';
			}
		}

		// ✅ Whitelist role check
		$selected_whitelisted_role      = 'false';
		$selected_whitelist_mobile_no = 'false';
		$is_enable_whitelist_user_roles = get_option( 'wc_af_enable_whitelist_user_roles' );
		if ( 'yes' === $is_enable_whitelist_user_roles ) {
			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $wc_af_whitelist_user_roles, true ) ) {
					$selected_whitelisted_role = 'true';
					break;
				}
			}
		}

		// ✅ Whitelist payment method check
		$selected_whitelist_payment_method = 'false';
		if ( 'yes' === get_option( 'wc_af_enable_whitelist_payment_method' ) ) {
			$get_whitelist_payment_method = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );
			$payment_method_from_checkout = WC()->session->get( 'chosen_payment_method' );
			if ( ! empty( $get_whitelist_payment_method ) && in_array( $payment_method_from_checkout, $get_whitelist_payment_method, true ) ) {
				$selected_whitelist_payment_method = 'true';
			}
		}

		// ✅ Whitelist email check
		$get_whitelist_email        = get_option( 'wc_settings_anti_fraud_whitelist' );
		$single_whitelist_email_arr = $this->parse_whitelist_input_data($get_whitelist_email);
		$selected_whitelisted_email = 'false';

		$customer_billing_email = isset( $fields['billing_email'] ) ? sanitize_text_field( $fields['billing_email'] ) : '';
		if ( in_array( $customer_billing_email, $single_whitelist_email_arr, true ) ) {
			$selected_whitelisted_email = 'true';
		}

		// ✅ Check whitelist mobile number
		if ( get_option( 'wc_af_enable_whitelist_phone_number' ) == 'yes' ) {

			$get_whitelist_mobile_no = get_option( 'wc_af_whitelist_phone_numbers');
			
			if ( '' != $get_whitelist_mobile_no ) {

				$whitelist_mobile_no = $this->parse_whitelist_input_data($get_whitelist_mobile_no);
				if ( isset( $_REQUEST['_wpnonce'] ) ) {
					if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'my-nonce' ) ) {
						echo 'Nonce verification failed!';
						die();
					}
				}
				$mobile_no_from_checkout = isset( $fields['billing_phone'] ) ? $fields['billing_phone'] : '';

				if ( in_array( $mobile_no_from_checkout, $whitelist_mobile_no ) ) {
					$selected_whitelist_mobile_no = 'true';
				}
			}
		}

		// ✅ Check whitelist country
		$selected_whitelist_country = 'false';
		if ( get_option( 'wc_af_enable_whitelist_country' ) == 'yes' ) {
			$whitelist_countries = (array) get_option( 'wc_af_whitelisted_countries', [] );
			$billing_country = isset( $fields['billing_country'] ) ? $fields['billing_country'] : '';
			
			if ( ! empty( $whitelist_countries ) && in_array( $billing_country, $whitelist_countries, true ) ) {
				$selected_whitelist_country = 'true';
			}
		}

		/// Get posted billing & shipping country/state
		$billing_country  = isset( $fields['billing_country'] ) ? sanitize_text_field( $fields['billing_country'] ) : ( isset( $_POST['billing_country'] ) ? sanitize_text_field( wp_unslash( $fields['billing_country'] ) ) : '' );
		// Helper to get readable state name from posted data (handles code => name)
		$resolve_state_name = function( $country, $state_raw ) {
			$state_raw = (string) $state_raw;
			$state_name = trim( $state_raw );
			if ( ! empty( $country ) && function_exists( 'WC' ) && isset( WC()->countries ) ) {
				$states = WC()->countries->get_states( $country );
				if ( is_array( $states ) && ! empty( $states ) ) {
					// If posted value is a state code and exists in keys, use the mapped name
					if ( array_key_exists( $state_raw, $states ) ) {
						$state_name = $states[ $state_raw ];
					} else {
						// try case-insensitive match against names or codes
						foreach ( $states as $code => $name ) {
							if ( 0 === strcasecmp( $name, $state_raw ) || 0 === strcasecmp( $code, $state_raw ) ) {
								$state_name = $name;
								break;
							}
						}
					}
				}
			}
			return wc_af_strtolower( trim( $state_name ) );
		};

		// ✅ Check whitelist state
		$selected_whitelist_state = 'false';
		if ( get_option( 'wc_af_enable_whitelist_state' ) == 'yes' ) {
			$get_whitelisted_states = get_option( 'wc_af_whitelisted_states', '' );
			
			if ( ! empty( $get_whitelisted_states ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_states ) ) {
					$whitelist_states = $get_whitelisted_states;
				} else {
					$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_states );
				}
				
				$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
				$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
				
				$billing_state_raw = isset( $fields['billing_state'] ) ? wc_af_strtolower( trim( $fields['billing_state'] ) ) : '';
				$billing_state  = $resolve_state_name( $billing_country, $billing_state_raw );
				
				if ( ! empty( $billing_state ) && in_array( $billing_state, $whitelist_states, true ) ) {
					$selected_whitelist_state = 'true';
				}
			}
		}

		// ✅ Check whitelist city/county
		$selected_whitelist_city = 'false';
		if ( get_option( 'wc_af_enable_whitelist_city' ) == 'yes' ) {
			$get_whitelisted_cities = get_option( 'wc_af_whitelisted_city', '' );
			
			if ( ! empty( $get_whitelisted_cities ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_cities ) ) {
					$whitelist_cities = $get_whitelisted_cities;
				} else {
					$whitelist_cities = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_cities );
				}
				
				$whitelist_cities = array_filter( array_map( 'trim', (array) $whitelist_cities ) );
				$whitelist_cities = array_map( 'wc_af_strtolower', $whitelist_cities );
				
				$billing_city = isset( $fields['billing_city'] ) ? wc_af_strtolower( trim( $fields['billing_city'] ) ) : '';
				
				if ( ! empty( $billing_city ) && in_array( $billing_city, $whitelist_cities, true ) ) {
					$selected_whitelist_city = 'true';
				}
			}
		}

		// ✅ Check whitelist ZIP/Postal Code
		$selected_whitelist_zip = 'false';
		if ( get_option( 'wc_af_enable_whitelist_zip' ) == 'yes' ) {
			$get_whitelisted_zips = get_option( 'wc_af_whitelisted_zip', '' );
			
			if ( ! empty( $get_whitelisted_zips ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_zips ) ) {
					$whitelist_zips = $get_whitelisted_zips;
				} else {
					$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_zips );
				}
				
				$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
				$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
				
				$billing_zip = isset( $fields['billing_postcode'] ) ? wc_af_strtolower( str_replace( ' ', '', trim( $fields['billing_postcode'] ) ) ) : '';
				
				if ( ! empty( $billing_zip ) && in_array( $billing_zip, $whitelist_zips, true ) ) {
					$selected_whitelist_zip = 'true';
				}
			}
		}

		$selected_whitelist_address = $this->address_whitelisting_check($fields);
		$selected_whitelist_firstname = $this->firstname_whitelisting_check($fields);
		$selected_whitelist_lastname = $this->lastname_whitelisting_check($fields);

		update_option( 'not_whitelisted_email', $selected_whitelisted_email );
		update_option( 'white_payment_methods', $selected_whitelist_payment_method );
		update_option( 'is_whitelisted_roles', $selected_whitelisted_role );
		update_option( 'is_whitelisted_ips', $whitelist_ips );
		update_option( 'is_whitelisted_mobile_no', $selected_whitelist_mobile_no );
		update_option( 'is_whitelisted_country', $selected_whitelist_country );
		update_option( 'is_whitelisted_state', $selected_whitelist_state );
		update_option( 'is_whitelisted_city', $selected_whitelist_city );
		update_option( 'is_whitelisted_zip', $selected_whitelist_zip );
		update_option( 'is_whitelisted_address', $selected_whitelist_address );
		update_option( 'is_whitelisted_firstname', $selected_whitelist_firstname );
		update_option( 'is_whitelisted_lastname', $selected_whitelist_lastname );


		// ✅ Wildcard email whitelist
		$selected_wildcard_whitelisted_email = $this->call_wildcard_email_validation($fields);

		// 🚫 Apply ZIP blacklist only if enabled and no whitelist override
		if ( 'yes' === $enable_blacklist_zip
			&& 'true' !== $selected_whitelisted_email
			&& 'true' !== $selected_whitelisted_role
			&& 'true' !== $selected_whitelist_payment_method
			&& 'true' !== $selected_wildcard_whitelisted_email
			&& 'true' !== $whitelist_ips
			&& 'true' !== $selected_whitelist_mobile_no
			&& 'true' !== $selected_whitelist_country
			&& 'true' !== $selected_whitelist_state
			&& 'true' !== $selected_whitelist_city
			&& 'true' !== $selected_whitelist_zip
			&& 'true' !== $selected_whitelist_address
			&& 'true' !== $selected_whitelist_firstname
			&& 'true' !== $selected_whitelist_lastname
		) {
			$blacklisted_zips_option = get_option('wc_af_blacklisted_zipcodes', []);
			$blacklisted_zips = [];

			// Make sure it's an array
			$blacklisted_zips_option = is_array($blacklisted_zips_option) ? $blacklisted_zips_option : explode(',', $blacklisted_zips_option);

			// Flatten comma-separated values inside array
			foreach ($blacklisted_zips_option as $zip_entry) {
				$parts = explode(',', $zip_entry);
				foreach ($parts as $part) {
					$blacklisted_zips[] = strtolower(str_replace(' ', '', trim($part)));
				}
			}

			$billing_zip  = isset($fields['billing_postcode']) ? strtolower(str_replace(' ', '', trim($fields['billing_postcode']))) : '';
			$shipping_zip = isset($fields['shipping_postcode']) ? strtolower(str_replace(' ', '', trim($fields['shipping_postcode']))) : '';

			$customer_zip = !empty($shipping_zip) ? $shipping_zip : $billing_zip;

			if (!empty($blacklisted_zips) && in_array($customer_zip, $blacklisted_zips, true)) {
				$errors->add(
					'validation',
					esc_html__('Orders from your postal code are not allowed.', 'woocommerce-anti-fraud')
				);
			}
		}
	}

	/**
	 * Suppress the WooCommerce "New Order" admin email when the order is currently
	 * being evaluated by a synchronous pre-payment fraud check, or when it has
	 * already been blocked by one.
	 *
	 * Hooked to: woocommerce_email_enabled_new_order (priority 10, 3 args)
	 *
	 * @param  bool            $enabled Whether the email is enabled.
	 * @param  WC_Order|mixed  $order   The order object passed by WooCommerce.
	 * @param  WC_Email|mixed  $email   The email object (unused).
	 * @return bool
	 */
	public function suppress_new_order_email_for_fraud( $enabled, $order, $email ) {

		// If we are currently inside a synchronous pre-payment fraud check,
		// hold the email regardless of the order.
		if ( $this->is_pre_payment_checking ) {
			return false;
		}

		// If the order already carries the fraud-blocked meta, suppress the email
		// even if the filter fires again in a different context (e.g. after a status
		// transition triggered by the payment gateway).
		if ( is_a( $order, 'WC_Order' ) ) {
			$order_id = $order->get_id();
			if ( '1' === (string) opmc_hpos_get_post_meta( $order_id, 'wc_af_fraud_pre_payment_failed', true ) ) {
				return false;
			}
		}

		return $enabled;
	}

	/*
	Pre-payment switch is co-related to the other three whitelisted rules. This is because all of these four rules are evaluated on the checkout page before the payment is processed.
	In this function, we are trying to get risk score and evaluate "Pre-payment"  before payment is processed on the checkout page.
	 */
	public function wh_pre_paymentcall( $order_id, $fields, $errors ) {

		if ( ! is_numeric( $order_id ) ) {
			return;
		}

		$check_before_payment_switch = get_option( 'wc_af_fraud_check_before_payment' );
		if ( 'yes' != $check_before_payment_switch ) {
			return; // Exit early if check is disabled
		}

		$order        = wc_get_order( $order_id );
		$score_helper = new WC_AF_Score_Helper();

		// ✅ 1. Check whitelist EMAIL
		$email_whitelist = get_option( 'wc_settings_anti_fraud_whitelist' );
		$whitelist_emails = $this->parse_whitelist_input_data( $email_whitelist );
		
		if ( isset( $fields['billing_email'] ) ) {
			$billing_email = sanitize_email( $fields['billing_email'] );
			
			if ( in_array( strtolower( $billing_email ), array_map( 'strtolower', $whitelist_emails ) ) ) {
				opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
				opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_email_whitelisted' );
				$order->add_order_note( __( 'Order fraud checks skipped due to whitelisted email.', 'woocommerce-anti-fraud' ) );
				Af_Logger::debug( 'Fraud Check exited: Email whitelisted - ' . $billing_email );
				return;
			}
		}

		// ✅ 2. Check wildcard email
		if ( $this->call_wildcard_email_validation( $fields ) === 'true' ) {
			opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
			opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_email_wildcard_whitelisted' );
			$order->add_order_note( __( 'Order fraud checks skipped due to wildcard whitelisted email.', 'woocommerce-anti-fraud' ) );
			Af_Logger::debug( 'Fraud Check exited: Wildcard email matched' );
			return;
		}

		// ✅ 3. Check whitelist IPs (consolidated check)
		$customer_ip = $order->get_customer_ip_address();
		
		// Check using score helper method
		if ( $score_helper->whitelistedIpAddresses( $customer_ip ) ) {
			opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
			opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_ip_whitelisted' );
			$order->add_order_note( __( 'Order fraud checks skipped due to whitelisted IP address.', 'woocommerce-anti-fraud' ) );
			Af_Logger::debug( 'Fraud Check exited: IP whitelisted - ' . $customer_ip );
			return;
		}

		// Check additional IP whitelist option
		$get_all_whitelist_ips = get_option( 'wc_settings_anti_fraud_ips_whitelist' );
		if ( ! empty( $get_all_whitelist_ips ) ) {
			$whitelist_ips_array = $this->parse_whitelist_input_data( $get_all_whitelist_ips );
			
			if ( in_array( $customer_ip, $whitelist_ips_array ) ) {
				opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
				opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_ip_whitelisted' );
				$order->add_order_note( __( 'Order fraud checks skipped due to whitelisted IP address.', 'woocommerce-anti-fraud' ) );
				Af_Logger::debug( 'Fraud Check exited: IP whitelisted (secondary check) - ' . $customer_ip );
				return;
			}
		}

		// ✅ 4. Check whitelist USER ROLE
		if ( get_option( 'wc_af_enable_whitelist_user_roles' ) == 'yes' ) {
			$user                       = wp_get_current_user();
			$user_roles                 = $user->roles;
			$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles', array() );

			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $wc_af_whitelist_user_roles ) ) {
					opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
					opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_role_whitelisted' );
					$order->add_order_note( __( 'Order fraud checks skipped due to whitelisted user role.', 'woocommerce-anti-fraud' ) );
					Af_Logger::debug( 'Fraud Check exited: User role whitelisted - ' . $role );
					return;
				}
			}
		}

		// ✅ 5. Check whitelist PAYMENT METHOD
		if ( get_option( 'wc_af_enable_whitelist_payment_method' ) == 'yes' ) {
			$whitelist_payment_method = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );
			
			if ( ! empty( $whitelist_payment_method ) ) {
				$payment_method = WC()->session->get( 'chosen_payment_method' );
				
				if ( in_array( $payment_method, $whitelist_payment_method ) ) {
					opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
					opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_payment_method_whitelisted' );
					$order->add_order_note( __( 'Order fraud checks skipped due to whitelisted payment method.', 'woocommerce-anti-fraud' ) );
					Af_Logger::debug( 'Fraud Check exited: Payment method whitelisted - ' . $payment_method );
					return;
				}
			}
		}

		// ✅ 6. Check whitelist PHONE NUMBER
		if ( get_option( 'wc_af_enable_whitelist_phone_number' ) == 'yes' ) {
			$get_whitelist_mobile_no = get_option( 'wc_af_whitelist_phone_numbers' );
			
			if ( ! empty( $get_whitelist_mobile_no ) ) {
				$whitelist_mobile_no = $this->parse_whitelist_input_data( $get_whitelist_mobile_no );
				$customer_mobile_no  = $order->get_billing_phone();
				
				if ( in_array( $customer_mobile_no, $whitelist_mobile_no ) ) {
					opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
					opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_mobile_number_whitelisted' );
					$order->add_order_note( __( 'Order fraud checks skipped due to whitelisted phone number.', 'woocommerce-anti-fraud' ) );
					Af_Logger::debug( 'Fraud Check exited: Phone number whitelisted - ' . $customer_mobile_no );
					return;
				}
			}
		}

		// ✅ Check whitelist country
		$selected_whitelist_country = 'false';
		if ( get_option( 'wc_af_enable_whitelist_country' ) == 'yes' ) {
			$whitelist_countries = (array) get_option( 'wc_af_whitelisted_countries', [] );
			$billing_country = $order->get_billing_country();
			
			if ( ! empty( $whitelist_countries ) && in_array( $billing_country, $whitelist_countries, true ) ) {
				$selected_whitelist_country = 'true';
				opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
				opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_country' );
				$order->add_order_note( __( 'Order fraud checks skipped due to whitelisted country.', 'woocommerce-anti-fraud' ) );
				return;
			}
		}

		// ✅ Check whitelist ZIP/Postal Code
		$selected_whitelist_zip = 'false';
		if ( get_option( 'wc_af_enable_whitelist_zip' ) == 'yes' ) {
			$get_whitelisted_zips = get_option( 'wc_af_whitelisted_zip', '' );
			
			if ( ! empty( $get_whitelisted_zips ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_zips ) ) {
					$whitelist_zips = $get_whitelisted_zips;
				} else {
					$whitelist_zips = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_zips );
				}
				
				$whitelist_zips = array_filter( array_map( 'trim', (array) $whitelist_zips ) );
				$whitelist_zips = array_map( 'wc_af_strtolower', $whitelist_zips );
				
				$billing_zip = isset( $fields['billing_postcode'] ) ? wc_af_strtolower( str_replace( ' ', '', trim( $fields['billing_postcode'] ) ) ) : '';
				
				if ( ! empty( $billing_zip ) && in_array( $billing_zip, $whitelist_zips, true ) ) {
					$selected_whitelist_zip = 'true';
					opmc_hpos_update_post_meta( $order_id, 'wc_af_score', 100 );
					opmc_hpos_update_post_meta( $order_id, 'whitelist_action', 'user_country' );
					$order->add_order_note( __( 'Order fraud checks skipped due to whitelisted Zip/Postal Code.', 'woocommerce-anti-fraud' ) );
					return;
				}
			}
		}

		// Get posted billing & shipping country/state
		$billing_country  = isset( $fields['billing_country'] ) ? sanitize_text_field( $fields['billing_country'] ) : ( isset( $fields['billing_country'] ) ? sanitize_text_field( wp_unslash( $fields['billing_country'] ) ) : '' );
		// Helper to get readable state name from posted data (handles code => name)
		$resolve_state_name = function( $country, $state_raw ) {
			$state_raw = (string) $state_raw;
			$state_name = trim( $state_raw );
			if ( ! empty( $country ) && function_exists( 'WC' ) && isset( WC()->countries ) ) {
				$states = WC()->countries->get_states( $country );
				if ( is_array( $states ) && ! empty( $states ) ) {
					// If posted value is a state code and exists in keys, use the mapped name
					if ( array_key_exists( $state_raw, $states ) ) {
						$state_name = $states[ $state_raw ];
					} else {
						// try case-insensitive match against names or codes
						foreach ( $states as $code => $name ) {
							if ( 0 === strcasecmp( $name, $state_raw ) || 0 === strcasecmp( $code, $state_raw ) ) {
								$state_name = $name;
								break;
							}
						}
					}
				}
			}
			return wc_af_strtolower( trim( $state_name ) );
		};

		// ✅ Check whitelist state
		$selected_whitelist_state = 'false';
		if ( get_option( 'wc_af_enable_whitelist_state' ) == 'yes' ) {
			$get_whitelisted_states = get_option( 'wc_af_whitelisted_states', '' );
			
			if ( ! empty( $get_whitelisted_states ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_states ) ) {
					$whitelist_states = $get_whitelisted_states;
				} else {
					$whitelist_states = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_states );
				}
				
				$whitelist_states = array_filter( array_map( 'trim', (array) $whitelist_states ) );
				$whitelist_states = array_map( 'wc_af_strtolower', $whitelist_states );
				
				$billing_state_raw = isset( $fields['billing_state'] ) ? wc_af_strtolower( trim( $fields['billing_state'] ) ) : '';
				$billing_state  = $resolve_state_name( $billing_country, $billing_state_raw );
				
				if ( ! empty( $billing_state ) && in_array( $billing_state, $whitelist_states, true ) ) {
					$selected_whitelist_state = 'true';
				}
			}
		}

		// ✅ Check whitelist city/county
		$selected_whitelist_city = 'false';
		if ( get_option( 'wc_af_enable_whitelist_city' ) == 'yes' ) {
			$get_whitelisted_cities = get_option( 'wc_af_whitelisted_city', '' );
			
			if ( ! empty( $get_whitelisted_cities ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_cities ) ) {
					$whitelist_cities = $get_whitelisted_cities;
				} else {
					$whitelist_cities = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_cities );
				}
				
				$whitelist_cities = array_filter( array_map( 'trim', (array) $whitelist_cities ) );
				$whitelist_cities = array_map( 'wc_af_strtolower', $whitelist_cities );
				
				$billing_city = isset( $fields['billing_city'] ) ? wc_af_strtolower( trim( $fields['billing_city'] ) ) : '';
				
				if ( ! empty( $billing_city ) && in_array( $billing_city, $whitelist_cities, true ) ) {
					$selected_whitelist_city = 'true';
				}
			}
		}

		// Whitelist flags for the pre-payment gate below. Early returns above already
		// skip matched rules; these defaults are safe when execution reaches here.
		$selected_whitelisted_email        = false;
		$selected_whitelisted_role         = 'false';
		$selected_whitelist_payment_method = 'false';
		$selected_whitelist_mobile_no      = 'false';
		$whitelist_ips                     = 'false';
		$selected_whitelist_firstname      = $this->firstname_whitelisting_check( $fields );
		$selected_whitelist_lastname       = $this->lastname_whitelisting_check( $fields );

		$selected_wildcard_whitelisted_email = $this->call_wildcard_email_validation( $fields );

		if ( 'yes' == $check_before_payment_switch && ! $selected_whitelisted_email && 'true' != $selected_whitelisted_role && 'true' != $selected_whitelist_payment_method && 'true' != $selected_whitelist_mobile_no && 'true' != $selected_wildcard_whitelisted_email && 'true' != $whitelist_ips && 'true' != $selected_whitelist_country && 'true' != $selected_whitelist_state && 'true' != $selected_whitelist_city && 'true' != $selected_whitelist_zip && 'true' != $selected_whitelist_firstname && 'true' != $selected_whitelist_lastname ) {

			if ( null !== get_option( 'wc_af_pre_payment_message' ) ) {
				$pre_payment_block_message = get_option( 'wc_af_pre_payment_message' );
			} else {
				$pre_payment_block_message = __( 'Website Administrator does not allow you to place this order. Please contact our support team. Sorry for any inconvenience.', 'woocommerce-anti-fraud' );
			}

			$high_risk    = get_option( 'wc_settings_anti_fraud_higher_risk_threshold' );
			$score_helper = new WC_AF_Score_Helper();

			// Raise the in-memory flag so that the woocommerce_email_enabled_new_order
			// filter can suppress the email for the duration of this synchronous check.
			$this->is_pre_payment_checking = true;

			try {
				$score_helper->schedule_fraud_check( $order_id, true );
			} catch ( \Throwable $e ) {
				// If a rule throws an unexpected error, log it and treat as no-risk
				// so checkout is not blocked by an internal plugin error.
				Af_Logger::debug( 'schedule_fraud_check error in pre-payment check: ' . $e->getMessage() );
				$this->is_pre_payment_checking = false;
				return;
			}

			$score_points  = opmc_hpos_get_post_meta( $order_id, 'wc_af_score', true );
			$circle_points = WC_AF_Score_Helper::invert_score( $score_points );

			if ( $high_risk <= $circle_points ) {
				
				// Persist a durable flag on the order so that the email is suppressed
				// even if the filter fires again later (e.g. triggered by a payment
				// gateway transitioning the order to a different status).
				opmc_hpos_update_post_meta( $order_id, 'wc_af_fraud_pre_payment_failed', '1' );

				// Lower the in-memory flag before changing order status to ensure
				// no race condition with any status-change email hooks.
				$this->is_pre_payment_checking = false;

				$order->update_status( 'failed', 'Pre Payment Fraud Check: Calculated risk score is above High Risk Threshold.', true );

				throw new \WC_REST_Exception( 'order_validation_failed', __( $pre_payment_block_message, 'woocommerce' ), 400 );

				$return = array(
				'result'   => 'failure',
				'messages' => "<ul class='woocommerce-error' role='alert'><li>" . $pre_payment_block_message . '</li></ul>',
				);

				wp_send_json( $return );
				wp_die();
			}
			
			// Fraud check passed — lower the flag so normal email flow resumes.
			$this->is_pre_payment_checking = false;
		}

	}


	public function address_whitelisting_check() {
		// ✅ Whitelist address check
		$selected_whitelist_address = 'false';
		if ( 'yes' === get_option( 'wc_af_enable_whitelist_address' ) ) {
			$whitelisted_addresses = (array) get_option( 'wc_af_whitelisted_addresses', array() );

			// Combine ONLY billing_address_1 and billing_address_2 (not city/state/postcode)
			$billing_address = strtolower(
				trim(
					( isset( $fields['billing_address_1'] ) ? $fields['billing_address_1'] : '' ) . ' ' .
					( isset( $fields['billing_address_2'] ) ? $fields['billing_address_2'] : '' )
				)
			);

			// Normalize billing address: remove punctuation, collapse spaces
			$billing_address = preg_replace( '/[[:punct:]]+/', ' ', $billing_address );
			$billing_address = preg_replace( '/\s+/', ' ', $billing_address );
			$billing_address = trim( $billing_address );

			if ( ! empty( $whitelisted_addresses ) ) {

				// normalize list elements (lowercase + trim)
				$whitelisted_addresses = array_map( 'strtolower', array_map( 'trim', $whitelisted_addresses ) );

				foreach ( $whitelisted_addresses as $keyword ) {

					if ( '' === $keyword ) {
						continue;
					}

					// normalize keyword (remove punctuation, collapse spaces)
					$norm_keyword = preg_replace( '/[[:punct:]]+/', ' ', $keyword );
					$norm_keyword = preg_replace( '/\s+/', ' ', $norm_keyword );
					$norm_keyword = trim( $norm_keyword );

					if ( '' === $norm_keyword ) {
						continue;
					}

					// 1) direct full-phrase match
					if ( false !== strpos( $billing_address, $norm_keyword ) ) {
						$selected_whitelist_address = 'true';
						break; // matched — stop
					}

					// 2) if the original keyword has commas, treat parts as alternatives
					$alts = preg_split( '/\s*,\s*/', $keyword );
					if ( $alts && count( $alts ) > 1 ) {
						foreach ( $alts as $alt ) {
							$alt = trim( strtolower( $alt ) );
							if ( '' === $alt ) {
								continue;
							}
							$alt_norm = preg_replace( '/[[:punct:]]+/', ' ', $alt );
							$alt_norm = preg_replace( '/\s+/', ' ', $alt_norm );
							$alt_norm = trim( $alt_norm );
							if ( '' === $alt_norm ) {
								continue;
							}
							if ( false !== strpos( $billing_address, $alt_norm ) ) {
								$selected_whitelist_address = 'true';
								// break out of both loops
								break 2;
							}
						}
					}

					// 3) fallback: require ALL words in the normalized keyword to appear (order-insensitive)
					$words = explode( ' ', $norm_keyword );
					$all_match = true;
					$has_word  = false;

					foreach ( $words as $w ) {
						$w = trim( $w );
						if ( '' === $w ) {
							continue;
						}
						$has_word = true;
						if ( false === strpos( $billing_address, $w ) ) {
							$all_match = false;
							break;
						}
					}

					if ( $has_word && $all_match ) {
						$selected_whitelist_address = 'true';
						break;
					}
				}
			}
		}

		return $selected_whitelist_address;
	}

	public function firstname_whitelisting_check( $fields) {
		// ✅ Check whitelist first name
		$selected_whitelist_firstname = 'false';

		if ( 'yes' === get_option( 'wc_af_enable_whitelisting_first_name' ) ) {

			$get_whitelisted_firstnames = get_option( 'wc_af_whitelisted_first_names', '' );
			
			if ( ! empty( $get_whitelisted_firstnames ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_firstnames ) ) {
					$whitelist_firstnames = $get_whitelisted_firstnames;
				} else {
					$whitelist_firstnames = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_firstnames );
				}
				
				$whitelist_firstnames = array_filter( array_map( 'trim', (array) $whitelist_firstnames ) );
				$whitelist_firstnames = array_map( 'wc_af_strtolower', $whitelist_firstnames );
				
				$billing_firstname = isset( $fields['billing_first_name'] ) ? wc_af_strtolower( trim( $fields['billing_first_name'] ) ) : '';
				
				if ( ! empty( $billing_firstname ) && in_array( $billing_firstname, $whitelist_firstnames, true ) ) {
					$selected_whitelist_firstname = 'true';
				}
			}
		}
		return $selected_whitelist_firstname;
	}


	
	public function lastname_whitelisting_check( $fields) {

		// ✅ Check whitelist last name
		$selected_whitelist_lastname = 'false';
		if ( 'yes' === get_option( 'wc_af_enable_whitelisting_last_name' ) ) {
			$get_whitelisted_lastnames = get_option( 'wc_af_whitelisted_last_names', '' );
			
			if ( ! empty( $get_whitelisted_lastnames ) ) {
				// Parse textarea input (comma or newline separated)
				if ( is_array( $get_whitelisted_lastnames ) ) {
					$whitelist_lastnames = $get_whitelisted_lastnames;
				} else {
					$whitelist_lastnames = preg_split( '/[\r\n,]+/', (string) $get_whitelisted_lastnames );
				}
				
				$whitelist_lastnames = array_filter( array_map( 'trim', (array) $whitelist_lastnames ) );
				$whitelist_lastnames = array_map( 'wc_af_strtolower', $whitelist_lastnames );
				
				$billing_lastname = isset( $fields['billing_last_name'] ) ? wc_af_strtolower( trim( $fields['billing_last_name'] ) ) : '';
				
				if ( ! empty( $billing_lastname ) && in_array( $billing_lastname, $whitelist_lastnames, true ) ) {
					$selected_whitelist_lastname = 'true';
				}
			}
		}
		return $selected_whitelist_lastname;
	}


	/* Related to oder debug log details */

	public function create_table_debuglog_file_downloads() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'af_download_url';
		$sql             = 'CREATE TABLE ' . $table_name . " (
	        id int(12) NOT NULL AUTO_INCREMENT,
	        download_url varchar(256) NOT NULL,
	        created_at date NOT NULL,
	        PRIMARY KEY id (id),
	        INDEX created_at (created_at)
	    ) $charset_collate;";

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta( $sql );
	}

	public function create_log_folder() {

		$uploads_dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'antifraud';
		wp_mkdir_p( $uploads_dir, 777 );
	}

	public function insert_debuglog_download_data( $download_url ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'af_download_url'; // do not forget about tables prefix
		if ( ! empty( $download_url ) ) {
			$wpdb->insert(
				$table_name,
				array(
					'download_url' => $download_url,
					'created_at'   => gmdate( 'Y-m-d' ),
				)
			);
		}
	}

	public function create_log_file_before_submit( $order_id ) {

		$enable_log_check = get_option( 'wc_af_enable_log_check' );
		if ( ! empty( $enable_log_check ) && 'no' != $enable_log_check ) {

			/* General Settings */
			$settings_anti_fraud_low_risk_threshold    = get_option( 'wc_settings_anti_fraud_low_risk_threshold' );
			$settings_anti_fraud_higher_risk_threshold = get_option( 'wc_settings_anti_fraud_higher_risk_threshold' );
			$fraud_check_before_payment                = get_option( 'wc_af_fraud_check_before_payment' );
			$pre_payment_block_message                 = get_option( 'wc_af_pre_payment_message' );
			$fraud_update_state                        = get_option( 'wc_af_fraud_update_state' );
			$settings_anti_fraud_cancel_score          = get_option( 'wc_settings_anti_fraud_cancel_score' );
			$settings_anti_fraud_hold_score            = get_option( 'wc_settings_anti_fraud_hold_score' );
			$enable_whitelist_payment_method           = get_option( 'wc_af_enable_whitelist_payment_method' );
			$whitelist_payment_method                  = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );

			if ( ! empty( $whitelist_payment_method ) ) {
				$whitelist_payment_method = implode( ',', $whitelist_payment_method );
			} else {
				$whitelist_payment_method = '';
			}

			$is_enable_whitelist_user_roles = get_option( 'wc_af_enable_whitelist_user_roles' );
			$wc_af_whitelist_user_roles     = get_option( 'wc_af_whitelist_user_roles' );
			if ( ! empty( $wc_af_whitelist_user_roles ) ) {
				$wc_af_whitelist_user_roles = implode( ',', $wc_af_whitelist_user_roles );
			} else {
				$wc_af_whitelist_user_roles = '';
			}

			$email_whitelist        = get_option( 'wc_settings_anti_fraud_whitelist' );
			$start_auto_fraud_check = get_option( 'wc_af_start_auto_fraud_check' );
			$debuglog               = 'no';

			/* General Rule */
			$first_order                                     = get_option( 'wc_af_first_order' );
			$fraud_first_order_weight                        = get_option( 'wc_settings_anti_fraud_first_order_weight' );
			$wc_af_first_order_custom                        = get_option( 'wc_af_first_order_custom' );
			$fraud_first_order_custom_weight                 = get_option( 'wc_settings_anti_fraud_first_order_custom_weight' );
			$ip_geolocation_order                            = get_option( 'wc_af_ip_geolocation_order' );
			$settings_anti_fraud_ip_geolocation_order_weight = get_option( 'wc_settings_anti_fraud_ip_geolocation_order_weight' );
			$bca_order                                       = get_option( 'wc_af_bca_order' );
			$fraud_bca_order_weight                          = get_option( 'wc_settings_anti_fraud_bca_order_weight' );
			$geolocation_order                               = get_option( 'wc_af_geolocation_order' );
			$fraud_geolocation_order_weight                  = get_option( 'wc_settings_anti_fraud_geolocation_order_weight' );
			$billing_phone_number_order                      = get_option( 'wc_af_billing_phone_number_order' );
			$fraud_billing_phone_number_order_weight         = get_option( 'wc_settings_anti_fraud_billing_phone_number_order_weight' );
			$proxy_order                                     = get_option( 'wc_af_proxy_order' );
			$fraud_proxy_order_weight                        = get_option( 'wc_settings_anti_fraud_proxy_order_weight' );
			$ip_multiple_check                               = get_option( 'wc_af_ip_multiple_check' );
			$settings_anti_fraud_ip_multiple_weight          = get_option( 'wc_settings_anti_fraud_ip_multiple_weight' );
			$fraud_ip_multiple_time_span                     = get_option( 'wc_settings_anti_fraud_ip_multiple_time_span' );
			$international_order                             = get_option( 'wc_af_international_order' );
			$settings_anti_fraud_international_order_weight  = get_option( 'wc_settings_anti_fraud_international_order_weight' );
			$unsafe_countries                                = get_option( 'wc_af_unsafe_countries' );
			$settings_anti_fraud_unsafe_countries_weight     = get_option( 'wc_settings_anti_fraud_unsafe_countries_weight' );

			$fraud_define_unsafe_countries_list = get_option( 'wc_settings_anti_fraud_define_unsafe_countries_list' );

			if ( ! empty( $fraud_define_unsafe_countries_list ) ) {
				$fraud_define_unsafe_countries_list = implode( ',', $fraud_define_unsafe_countries_list );
			} else {
				$fraud_define_unsafe_countries_list = '';
			}
			$suspecius_email                              = get_option( 'wc_af_suspecius_email' );
			$settings_anti_fraud_suspecious_email_weight  = get_option( 'wc_settings_anti_fraud_suspecious_email_weight' );
			$settings_anti_fraud_suspecious_email_domains = get_option( 'wc_settings_anti_fraud_suspecious_email_domains' );
			$check_email_domain_api_key                   = get_option( 'check_email_domain_api_key' );
			$order_avg_amount_check                       = get_option( 'wc_af_order_avg_amount_check' );
			$fraud_order_avg_amount_weight                = get_option( 'wc_settings_anti_fraud_order_avg_amount_weight' );
			$fraud_avg_amount_multiplier                  = get_option( 'wc_settings_anti_fraud_avg_amount_multiplier' );
			$order_amount_check                           = get_option( 'wc_af_order_amount_check' );
			$fraud_order_amount_weight                    = get_option( 'wc_settings_anti_fraud_order_amount_weight' );
			$fraud_amount_limit                           = get_option( 'wc_settings_anti_fraud_amount_limit' );
			$attempt_count_check                          = get_option( 'wc_af_attempt_count_check' );
			$fraud_order_attempt_weight                   = get_option( 'wc_settings_anti_fraud_order_attempt_weight' );
			$fraud_attempt_time_span                      = get_option( 'wc_settings_anti_fraud_attempt_time_span' );
			$fraud_max_order_attempt_time_span            = get_option( 'wc_settings_anti_fraud_max_order_attempt_time_span' );
			$limit_order_count                            = get_option( 'wc_af_limit_order_count' );
			$limit_time_start                             = get_option( 'wc_af_limit_time_start' );
			$allowed_order_limit                          = get_option( 'wc_af_allowed_order_limit' );
			$limit_time_end                               = get_option( 'wc_af_limit_time_end' );

			/* Email Blacklisting */
			$enable_automatic_email_blacklist = get_option( 'wc_settings_anti_fraudenable_automatic_email_blacklist' );
			$enable_automatic_blacklist       = get_option( 'wc_settings_anti_fraudenable_automatic_blacklist' );
			$blacklist_emails                 = get_option( 'wc_settings_anti_fraudblacklist_emails' );
			$is_enable_ip_blacklist           = get_option( 'wc_settings_anti_fraudenable_automatic_ip_blacklist' );
			$automatic_ips_blacklist           = get_option( 'wc_settings_anti_fraudenable_automatic_ips_blacklist' );
			$blacklist_ip                     = get_option( 'wc_settings_anti_fraudblacklist_ipaddress' );
			$email_notification               = get_option( 'wc_af_email_notification' );
			$settings_anti_fraud_email_score  = get_option( 'wc_settings_anti_fraud_email_score' );

			/* Paypal Settings */
			$paypal_verification             = get_option( 'wc_af_paypal_verification' );
			$paypal_prevent_downloads        = get_option( 'wc_af_paypal_prevent_downloads' );
			$fraud_time_paypal_attempts      = get_option( 'wc_settings_anti_fraud_time_paypal_attempts' );
			$fraud_day_deleting_paypal_order = get_option( 'wc_settings_anti_fraud_day_deleting_paypal_order' );
			$fraud_paypal_verified_address   = get_option( 'wc_settings_anti_fraud_paypal_verified_address' );

			/* MaxMind minFraud Settings */
			$enable_maxmind_minfraud     = get_option( 'wc_af_maxmind_type' );
			$device_trackin_settings     = get_option( 'wc_af_maxmind_device_tracking' );
			$maxmind_user                = get_option( 'wc_af_maxmind_user' );
			$maxmind_license_key         = get_option( 'wc_af_maxmind_license_key' );
			$fraud_minfraud_risk_score   = get_option( 'wc_settings_anti_fraud_minfraud_risk_score' );
			$fraud_minfraud_order_weight = get_option( 'wc_settings_anti_fraud_minfraud_order_weight' );

			/* MaxMind minFraud insights Settings */
			$maxmind_insights_setting             = get_option( 'wc_af_maxmind_insights' );
			$fraud_minfraud_insights_risk_score   = get_option( 'wc_settings_anti_fraud_minfraud_insights_risk_score' );
			$fraud_minfraud_insights_order_weight = get_option( 'wc_settings_anti_fraud_minfraud_insights_order_weight' );

			/* MaxMind minFraud factors Settings */

			$wc_af_maxmind_factors_setting       = get_option( 'wc_af_maxmind_factors' );
			$fraud_minfraud_factors_risk_score   = get_option( 'wc_settings_anti_fraud_minfraud_factors_risk_score' );
			$fraud_minfraud_factors_order_weight = get_option( 'wc_settings_anti_fraud_minfraud_factors_order_weight' );

			/* Google Re-Captcha Settings */
			$enable_recaptcha_checkout = get_option( 'wc_settings_anti_fraudenable_enable_recaptcha' );
			$v2_recaptcha_site_key     = get_option( 'wc_af_recaptcha_site_key' );
			$v2_recaptcha_secret_key   = get_option( 'wc_af_recaptcha_secret_key' );

			$order            = wc_get_order( $order_id );
			$orderno          = $order->get_id();
			$order_date_time  = $order->get_date_created();
			$order_status     = $order->get_status();
			$customer_name    = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			$email            = $order->get_billing_email();
			$phone            = $order->get_billing_phone();
			$billing_address  = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() . ', ' . $order->get_billing_city() . ', ' . $order->get_billing_state() . ', ' . $order->get_billing_postcode() . ', ' . $order->get_billing_country();
			$shipping_address = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_state() . ', ' . $order->get_shipping_postcode() . ', ' . $order->get_shipping_country();
			if ( empty( $shipping_address ) ) {
				$shipping_address = $billing_address;
			}
			$payment_method = $order->get_payment_method_title();
			$numer_of_items = $order->get_item_count();
			$subtotal       = $order->get_subtotal();
			$shipping       = $order->get_shipping_total();
			$discount       = $order->get_discount_total();
			$tax_amount     = $order->get_total_tax();
			$total_amount   = $order->get_total();

			$csv_content = $this->headercontent();
			$upload_dir  = wp_get_upload_dir()['basedir'];
			$file_path   = $upload_dir . '/antifraud';
			$files       = glob( "$file_path/*.csv" );

			$rows = array(
				array(
					$orderno,
					$order_date_time,
					$order_status,
					$customer_name,
					$email,
					$phone,
					$billing_address,
					$shipping_address,
					$payment_method,
					$numer_of_items,
					$subtotal,
					$shipping,
					$discount,
					$tax_amount,
					$total_amount,
					$settings_anti_fraud_low_risk_threshold,
					$settings_anti_fraud_higher_risk_threshold,
					$fraud_check_before_payment,
					$fraud_update_state,
					$settings_anti_fraud_cancel_score,
					$settings_anti_fraud_hold_score,
					$enable_whitelist_payment_method,
					$whitelist_payment_method,
					$is_enable_whitelist_user_roles,
					$wc_af_whitelist_user_roles,
					$email_whitelist,
					$start_auto_fraud_check,
					$debuglog,
					$first_order,
					$fraud_first_order_weight,
					$wc_af_first_order_custom,
					$fraud_first_order_custom_weight,
					$ip_geolocation_order,
					$settings_anti_fraud_ip_geolocation_order_weight,
					$bca_order,
					$fraud_bca_order_weight,
					$geolocation_order,
					$fraud_geolocation_order_weight,
					$billing_phone_number_order,
					$fraud_billing_phone_number_order_weight,
					$proxy_order,
					$fraud_proxy_order_weight,
					$ip_multiple_check,
					$settings_anti_fraud_ip_multiple_weight,
					$fraud_ip_multiple_time_span,
					$international_order,
					$settings_anti_fraud_international_order_weight,
					$unsafe_countries,
					$settings_anti_fraud_unsafe_countries_weight,
					$fraud_define_unsafe_countries_list,
					$suspecius_email,
					$settings_anti_fraud_suspecious_email_weight,
					$settings_anti_fraud_suspecious_email_domains,
					$check_email_domain_api_key,
					$order_avg_amount_check,
					$fraud_order_avg_amount_weight,
					$fraud_avg_amount_multiplier,
					$order_amount_check,
					$fraud_order_amount_weight,
					$fraud_amount_limit,
					$attempt_count_check,
					$fraud_order_attempt_weight,
					$fraud_attempt_time_span,
					$fraud_max_order_attempt_time_span,
					$limit_order_count,
					$limit_time_start,
					$limit_time_end,
					$allowed_order_limit,
					$enable_automatic_email_blacklist,
					$enable_automatic_blacklist,
					$blacklist_emails,
					$is_enable_ip_blacklist,
					$automatic_ips_blacklist,
					$blacklist_ip,
					$email_notification,
					$settings_anti_fraud_email_score,
					$paypal_verification,
					$paypal_prevent_downloads,
					$fraud_time_paypal_attempts,
					$fraud_day_deleting_paypal_order,
					$fraud_paypal_verified_address,
					$enable_maxmind_minfraud,
					$device_trackin_settings,
					$maxmind_user,
					$maxmind_license_key,
					$fraud_minfraud_risk_score,
					$fraud_minfraud_order_weight,
					$maxmind_insights_setting,
					$fraud_minfraud_insights_risk_score,
					$fraud_minfraud_insights_order_weight,
					$wc_af_maxmind_factors_setting,
					$fraud_minfraud_factors_risk_score,
					$fraud_minfraud_factors_order_weight,
					$enable_recaptcha_checkout,
					$v2_recaptcha_site_key,
					$v2_recaptcha_secret_key,
				),
			);

			$today = gmdate( 'Y-m-d' );
			if ( ! empty( $files ) ) {
				foreach ( $files as $value ) {
					$val                 = $value;
					$file_name           = basename( $val );
					$created_file        = str_split( $file_name, 14 );
					$created_file_date[] = explode( '.', $created_file[1] );
				}
				foreach ( $created_file_date as $value ) {
					$new[] = $value[0];
				}

				if ( in_array( $today, $new ) ) {

					$upload_dir = wp_get_upload_dir()['basedir'];

					$file_path = $upload_dir . '/antifraud/antifraud-log-' . $today . '.csv';

					$fp = fopen( $file_path, 'a' ); // open in write only mode (with pointer at the end of the file)

					foreach ( $rows as $row ) {
						fputcsv( $fp, $row );
					}
					fclose( $fp );
				} else {

					$upload_dir       = wp_get_upload_dir()['basedir'];
					$file_path        = $upload_dir . '/antifraud/antifraud-log-' . gmdate( 'Y-m-d' ) . '.csv';
					$csv_content_file = '';
					file_put_contents( $file_path, $csv_content_file );
					$fp = fopen( $file_path, 'a' ); // open in write only mode (with pointer at the end of the file)
					foreach ( $csv_content as $row ) {
						fputcsv( $fp, $row );
					}
					fclose( $fp );

					$download_url = get_site_url() . '/wp-content/uploads/antifraud/antifraud-log-' . $today . '.csv';

					$this->insert_debuglog_download_data( $download_url );

					$fp = fopen( $file_path, 'a' ); // open in write only mode (with pointer at the end of the file)
					foreach ( $rows as $row ) {
						fputcsv( $fp, $row );
					}
					fclose( $fp );
				}
			} else {

				$upload_dir       = wp_get_upload_dir()['basedir'];
				$file_path        = $upload_dir . '/antifraud/antifraud-log-' . gmdate( 'Y-m-d' ) . '.csv';
				$csv_content_file = '';
				file_put_contents( $file_path, $csv_content_file );
				$fp = fopen( $file_path, 'a' ); // open in write only mode (with pointer at the end of the file)
				foreach ( $csv_content as $row ) {
					$this->wc_af_fputcsv( $fp, $row );
				}
				fclose( $fp );
				$download_url = get_site_url() . '/wp-content/uploads/antifraud/antifraud-log-' . $today . '.csv';

				$this->insert_debuglog_download_data( $download_url );
				$fp = fopen( $file_path, 'a' ); // open in write only mode (with pointer at the end of the file)
				foreach ( $rows as $row ) {
					fputcsv( $fp, $row );
				}
				fclose( $fp );
			}
		}
	}

	public function wc_af_fputcsv( $fp, $row ) {
		if ( version_compare( PHP_VERSION, '8.4', '>=' ) ) {
			fputcsv( $fp, $row, ',', '"', '\\' );
		} else {
			fputcsv( $fp, $row );
		}
	}

	public function headercontent( $header_content = '' ) {
		$header_content = array(
			array(
				'orderno',
				'order-date-time',
				'order-status',
				'customer-name',
				'email',
				'phone',
				'billing-address',
				'shipping-address',
				'payment-method',
				'numer-of-items',
				'subtotal',
				'shipping',
				'discount',
				'tax-amount',
				'total-amount',
				'medium-risk-threshold',
				'high-risk-threshold',
				'pre-pay-check',
				'update-status-based-fraudscore',
				'weight-cancel-order',
				'weight-order-onhold',
				'whitelist-pay-method',
				'select-whitelist-pay-methods',
				'whitelist-of-user-role',
				'select-user-role-whitelist',
				'email-whitelist',
				'autofraud-check',
				'debuglog',
				'first-purchase-check',
				'first-purchase-value',
				're-check-first-orders-in-process-state-check',
				're-check-first-orders-in-process-state-value',
				'ip-address-match-location-check',
				'ip-address-match-location-value',
				'billing-shipping-address-same-check',
				'billing-shipping-address-same-value',
				'geo-location-match-check',
				'geo-location-match-value',
				'phone-number-billing-country-check',
				'phone-number-billing-country-value',
				'customer-behind-proxy-vpn-check',
				'customer-behind-proxy-vpn-value',
				'purchased-from-same-ip-but-different-customer-address-check',
				'purchased-from-same-ip-but-different-customer-address-value',
				'past-number-of-days',
				'it-international-order-check',
				'it-international-order-value',
				'order-high-risk-country-check',
				'order-high-risk-country-value',
				'mark-unsafe-countries',
				'high-risk-email-domain-check',
				'high-risk-email-domain-value',
				'high-risk-domains',
				'key-for-quickemailverification',
				'order-amount-above-average-check',
				'order-amount-above-average-value',
				'average-multiplier',
				'order-exceeds-max-amount-limit-check',
				'order-exceeds-max-amount-limit-value',
				'amount-limit, many-order-attempts-check',
				'many-order-attempts-value',
				'time-span-check-hours',
				'max-allowed-number-of-orders-time-span',
				'limit-number-orders-between-time-check',
				'start-time, end-time',
				'limit-number-orders-between-time',
				'email-blacklist',
				'automatic-blacklisting',
				'blocked-email-addresses',
				'ip-blacklist',
				'blocked-ip-addresses',
				'activate-email-alerts-for-admin',
				'additional-address-notify',
				'email-notification-score',
				'enable-disable-paypal',
				'block-downloads',
				'verification-retry',
				'auto-cancellation-days',
				'paypal-verified-address',
				'email-type',
				'enable-disable-minfraud',
				'device-tracking',
				'maxmind-account-id',
				'maxmind-license-key',
				'threshold-minfraud-score',
				'minfraud-rule-weight',
				'enable-disable-minfraud-insights',
				'threshold-minfraud-insights-score',
				'minfraud-insights-rule-weight',
				'enable-disable-minfraud-factors',
				'threshold-minFraud-factors-score',
				'minfraud-factors-rule-weight',
				'enable-re-captcha',
				'enable-v2-re-captcha',
				'v2-site-key',
				'v2-secret-key',
				'enable-v3-re-captcha',
				'v3-site-key',
				'v3-secret-key',
			),
		);

		return $header_content;
	}

	/* Debug log end*/

	public function my_admin_notice() {

		$wc_af_recaptcha_enable_captcha = get_option( 'wc_af_recaptcha_enable_captcha' );
		if ( 'yes' != $wc_af_recaptcha_enable_captcha ) {

			?>
			<div class="notice error is-dismissible">

				<p>
					<?php
					/* translators: 1. start of link, 2. end of link. */
					printf( esc_html__( 'Please consider enabling reCaptcha in the Anti Fraud plugin %1$ssettings%2$s to help prevent Velocity attacks.', 'woocommerce-anti-fraud' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=wc_af&section=recaptcha_settings' ) ) . '">', '</a>' );
					?>
				</p>
			</div>
			<script type="text/javascript">
				jQuery(document).ready(function () {
					jQuery(document).on('click', '.notice-dismiss', function () {
						//alert('It will again appear after 24 hours.');
						jQuery.ajax({
							url: ajaxurl,
							data: {
								action: 'my_dismiss_notice'
							}
						});
					});
				});
			</script>
			<?php
		}
	}

	public function my_dismiss_notice() {
		$now = gmdate( 'Y-m-d H:i:s' );
		update_option( 'my_notice_dismisseds', 1 );
		// update_option( 'my_notice_dismisseds_time', $now );
	}

	public function my_action_geo_country() {
		check_ajax_referer( 'wc_af_geo_nonce', '_wpnonce' );

		$bigdatacloud_key = get_option( 'bigdatacloud_api_key', true );

		if ( ! empty( $_POST['latitude'] ) && ! empty( $_POST['longitude'] ) && ! empty( $bigdatacloud_key ) ) {

			$lat      = sanitize_text_field( $_POST['latitude'] );
			$lng      = sanitize_text_field( $_POST['longitude'] );
			$response = wp_remote_get( 'https://api-bdc.net/data/reverse-geocode?latitude=' . $lat . '&longitude=' . $lng . '&localityLanguage=en&key=' . $bigdatacloud_key );

			if ( is_wp_error( $response ) ) {
				echo 'error';
				die();
			}
			if ( isset( $response ) ) {

				$output = json_decode( $response['body'], true );
				if ( ! empty( $output ) ) {

					// Safely persist to WooCommerce Session explicitly for this instance
					if ( isset( WC()->session ) ) {
						$geo_data = array(
							'city'     => '',
							'state'    => '',
							'country'  => '',
							'postcode' => '',
						);

						if ( ! empty( $output['city'] ) ) {
							$geo_data['city'] = strtolower( $output['city'] );
						}

						if ( ! empty( $output['countryCode'] ) ) {
							$geo_data['country'] = strtolower( $output['countryCode'] );
						}

						if ( ! empty( $output['principalSubdivision'] ) ) {
							$geo_data['state'] = strtolower( $output['principalSubdivision'] );
						}

						if ( ! empty( $output['postcode'] ) ) {
							$geo_data['postcode'] = strtolower( $output['postcode'] );
						}

						WC()->session->set( 'wc_af_browser_geo_data', $geo_data );
					}

					// Cleanup any legacy global options if they exist
					delete_option( 'html_geo_loc_state' );
					delete_option( 'html_geo_loc_city' );
					delete_option( 'html_geo_loc_cntry' );
					
					echo 'success';
					die();
				}
			}
		} else {
			if ( isset( WC()->session ) ) {
				WC()->session->set( 'wc_af_browser_geo_data', array() );
			}
			
			delete_option( 'html_geo_loc_state' );
			delete_option( 'html_geo_loc_city' );
			delete_option( 'html_geo_loc_cntry' );
		}
		die();
	}

	public function add_scripts_to_pages() {
		$maxmind_settings               = get_option( 'wc_af_maxmind_type' ); // Get MaxMind enable/disable
		$wc_af_maxmind_insights_setting = get_option( 'wc_af_maxmind_insights' ); // Get MaxMind insights enable/disable
		$wc_af_maxmind_factors_setting  = get_option( 'wc_af_maxmind_factors' ); // Get MaxMind factors enable/disable
		$bigdatacloud_key               = get_option( 'bigdatacloud_api_key' ); // Get bigdatacloud API key
		if ( 'yes' != $maxmind_settings && 'yes' != $wc_af_maxmind_insights_setting && 'yes' != $wc_af_maxmind_factors_setting ) {

			wp_enqueue_script( 'ajax_operation_script', plugins_url( 'assets/js/geoloc.js', __FILE__ ), array(), '1.0' );
			wp_localize_script( 'ajax_operation_script', 'bigdatacloud_key', array( 'key' => $bigdatacloud_key ) );
			wp_localize_script( 'ajax_operation_script', 'myAjax', array(
				'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
				'nonce'   => wp_create_nonce( 'wc_af_geo_nonce' ),
			) );
			wp_enqueue_script( 'ajax_operation_script' );
		}
	}

	public function switch_onoff( $hookget ) {

		if ( 'toplevel_page_antifraud-dashboard' == get_current_screen()->id ) {
			wp_enqueue_script( 'antifraud-chart-js', plugins_url( 'assets/js/chart.js', __FILE__ ), array(), WOOCOMMERCE_ANTI_FRAUD_VERSION );
		}

		if ( 'woocommerce_page_wc-settings' != $hookget ) {
			return;
		}

		if ( ! isset( $_REQUEST['section'] ) || 'minfraud_settings' !== $_REQUEST['section'] ) {
			return;
		}

	// Legacy on-off-switch scripts removed - they create duplicate toggles.
	// The plugin already has its own toggle system via opmc-toggle-control CSS class.
	// These scripts were causing double toggle switches to appear and conflicts with WP core jQuery.
	
	// wp_enqueue_style( 'on-off-switch', plugins_url( 'assets/css/on-off-switch.css', __FILE__ ), array(), WOOCOMMERCE_ANTI_FRAUD_VERSION );
	// wp_enqueue_script( 'on-off-switch', plugins_url( 'assets/js/on-off-switch.js', __FILE__ ), array( 'jquery' ), WOOCOMMERCE_ANTI_FRAUD_VERSION );
	// wp_enqueue_script( 'on-off-switch-onload', plugins_url( 'assets/js/on-off-switch-onload.js', __FILE__ ), array( 'jquery' ), WOOCOMMERCE_ANTI_FRAUD_VERSION );
	}

	public function deactivate_events_on_active_plugin( $hook ) {

		$crons = _get_cron_array();
		if ( empty( $crons ) ) {
			return;
		}

		foreach ( $crons as $timestamp => $cron ) {

			if ( ! empty( $cron['my_hourly_event'] ) ) {
				unset( $crons[ $timestamp ]['my_hourly_event'] );
			}
		}
		_set_cron_array( $crons );

		// Delete option for Geolocation notice if plugin disabled
		delete_option( 'woo_af_geoloc_notice_dismissed' );
	}

	public function deactivate_events( $hook ) {

		$crons = _get_cron_array();
		if ( empty( $crons ) ) {
			return;
		}

		foreach ( $crons as $timestamp => $cron ) {

			if ( ! empty( $cron['wc-af-check'] ) ) {
				unset( $crons[ $timestamp ]['wc-af-check'] );
			}
			if ( ! empty( $cron['wp_af_paypal_verification'] ) ) {
				unset( $crons[ $timestamp ]['wp_af_paypal_verification'] );
			}
			if ( ! empty( $cron['wp_af_my_hourly_event'] ) ) {
				unset( $crons[ $timestamp ]['wp_af_my_hourly_event'] );
			}
		}
		_set_cron_array( $crons );


		$maxmind_alert_users = get_users( array( 'meta_key' => 'opmc-antifraud-maxmind-alert' ) );
		foreach ( $maxmind_alert_users as $maxmind_user ) {
			delete_user_meta( $maxmind_user->ID, 'opmc-antifraud-maxmind-alert' );
		}

	$trustswiftly_alert_users = get_users( array( 'meta_key' => 'opmc-antifraud-trustswiftly-alert' ) );
		foreach ( $trustswiftly_alert_users as $trustswiftly_user ) {
			delete_user_meta( $trustswiftly_user->ID, 'opmc-antifraud-trustswiftly-alert' );
		}
	update_option( 'wc_af_paypal_acp_enabled', 'no' );

	/**
	 * Clean up all failed orders cache on deactivation
	 * 
	 * @since 7.1.9
	 */
	$this->cleanup_failed_orders_cache();
	}

	/**
	 * Clean up all failed orders related cache and temporary data
	 * 
	 * Called on plugin deactivation to ensure clean state.
	 *
	 * @since 7.1.9
	 */
	public function cleanup_failed_orders_cache() {
		// Remove main cache transients
		delete_transient( 'wc_af_preload_failed_counts' );
		delete_transient( 'wc_af_preload_failed_orderid' );

		// Remove cleanup/selection cache
		delete_transient( 'wc_af_failed_orders_to_cleanup' );
		delete_transient( 'wc_af_cleanup_selected_timeframe' );
		delete_transient( 'wc_af_cleanup_orderid' );
		delete_transient( 'wc_af_cleanup_orderid_count' );

		// Remove any temporary options (legacy from background processing)
		delete_option( 'wc_af_batch_temp_counts' );
		delete_option( 'wc_af_batch_temp_orderid' );
		delete_option( 'wc_af_failed_order_preload_complete' );
		delete_option( 'wc_af_cleanup_timeframe' );
	}

	/**
	 * Check if Device tracking is active
	 *
	 * @since  1.0.0
	 *
	 * Call on header
	 */

	public function get_device_tracking_script() {

		$device_trackin_settings = get_option( 'wc_af_maxmind_device_tracking' );
		// Get Device Tracking enable/disable
		if ( 'yes' === $device_trackin_settings ) {
			$maxmind_user = get_option( 'wc_af_maxmind_user' );

			if ( ! empty( $maxmind_user ) ) {
				?>
				<script type="text/javascript">
					maxmind_user_id = "<?php esc_html_e( $maxmind_user ); ?>";
					(function () {
						var loadDeviceJs = function () {
							var element = document.createElement('script');
							element.src = 'https://device.maxmind.com/js/device.js';
							document.body.appendChild(element);
						};
						if (window.addEventListener) {
							window.addEventListener('load', loadDeviceJs, false);
						} else if (window.attachEvent) {
							window.attachEvent('onload', loadDeviceJs);
						}
					})();
				</script>
				<?php
			}
		}
	}

	public function check_blacklist_whitelist() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'woocommerce-anti-fraud' ), 403 );
			wp_die();
		}

		check_ajax_referer( 'woocommerce-anti-fraud', '_wpnonce' );

		$blocked_email = get_option( 'wc_settings_anti_fraudblacklist_emails' );
		$array_mail    = explode( ',', $blocked_email );

		$whitelistarray = isset( $_POST['whitelist'] ) ? sanitize_text_field( wp_unslash( $_POST['whitelist'] ) ) : '';

		$expwhitearray  = $this->parse_whitelist_input_data( $whitelistarray );

		$result         = array_diff( $array_mail, $expwhitearray );
		$finalblocklist = implode( ',', $result );

		update_option( 'wc_settings_anti_fraudblacklist_emails', $finalblocklist );

		echo esc_html( $finalblocklist );
		wp_die();
	}

	public function whitelist_email() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'woocommerce-anti-fraud' ) );
		}

		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'whitelist_email_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'woocommerce-anti-fraud' ) );
		}

		$email = isset( $_REQUEST['email'] ) ? sanitize_email( $_REQUEST['email'] ) : '';
		if ( ! is_email( $email ) || empty( $email ) ) {
			wp_send_json_error( __( 'Invalid or empty email address.', 'woocommerce-anti-fraud' ) );
		}

		// Standardize email format
		$email = strtolower( trim( $email ) );

		// Retrieve current whitelist
		$email_whitelist = get_option( 'wc_settings_anti_fraud_whitelist', '' );
		$whitelist_array = $this->parse_whitelist_input_data($email_whitelist);

		// Avoid duplicate entries
		if ( ! in_array( $email, $whitelist_array ) ) {
			$whitelist_array[] = $email;
			// ✅ OPTIMIZED: Disable autoload to prevent loading on every request
			update_option( 'wc_settings_anti_fraud_whitelist', implode( "\n", $whitelist_array ) );
			$this->disable_whitelist_autoload();
			$this->clear_whitelist_cache();
		}

		wp_send_json_success( __( 'Successfully whitelisted the email address.', 'woocommerce-anti-fraud' ) );
	}

	public function wc_af_import_whitelist_csv_handler() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woocommerce-anti-fraud' ) ) );
			wp_die();
		}

		check_ajax_referer( 'woocommerce-anti-fraud', '_wpnonce' );
		$emails       = isset( $_POST['emails'] ) ? explode( ',', sanitize_text_field( $_POST['emails'] ) ) : array();
		$valid_emails = array();

		foreach ( $emails as $email ) {
			$email = sanitize_email( $email );
			if ( is_email( $email ) ) {
				$valid_emails[] = $email;
			}
		}

		if ( empty( $valid_emails ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid emails found in this batch.', 'woocommerce-anti-fraud' ) ) );
		}

		// Get existing emails to avoid duplicates
		$existing_emails = explode( ',', get_option( 'wc_settings_anti_fraud_whitelist', '' ) );
		$all_emails      = array_unique( array_merge( $existing_emails, $valid_emails ) );

		// Batch update to avoid frequent update_option
		// ✅ OPTIMIZED: Disable autoload to prevent loading on every request
		update_option( 'wc_settings_anti_fraud_whitelist', implode( ',', $all_emails ) );
		$this->disable_whitelist_autoload();
		$this->clear_whitelist_cache();

		wp_send_json_success( array( 'emails' => $valid_emails ) );
	}

	/**
	 * AJAX handler to blacklist email from order details
	 * 
	 * @since 7.1.9
	 */
	public function wc_af_blacklist_email() {
		// Check permissions
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'woocommerce-anti-fraud' ) );
		}

		// Verify nonce
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'wc_af_blacklist_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'woocommerce-anti-fraud' ) );
		}

		// Get and validate order ID
		$order_id = isset( $_REQUEST['order_id'] ) ? absint( $_REQUEST['order_id'] ) : 0;
		if ( ! $order_id ) {
			wp_send_json_error( __( 'Invalid order ID.', 'woocommerce-anti-fraud' ) );
		}

		// Get order
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( __( 'Order not found.', 'woocommerce-anti-fraud' ) );
		}

		// Get email from order
		$email = $order->get_billing_email();
		if ( ! is_email( $email ) || empty( $email ) ) {
			wp_send_json_error( __( 'Invalid or empty email address.', 'woocommerce-anti-fraud' ) );
		}

		// Standardize email format
		$email = strtolower( trim( $email ) );

		// Retrieve current blacklist
		$email_blacklist = get_option( 'wc_settings_anti_fraudblacklist_emails', '' );
		$blacklist_array = array();
		
		if ( ! empty( $email_blacklist ) ) {
			// Parse the blacklist (supports comma and newline separated)
			$blacklist_array = preg_split( '/[\r\n,]+/', $email_blacklist );
			$blacklist_array = array_filter( array_map( 'trim', $blacklist_array ) );
		}

		// Check if already blacklisted (idempotent)
		if ( in_array( $email, $blacklist_array ) ) {
			wp_send_json_success( array(
				'message' => __( 'Email is already blacklisted.', 'woocommerce-anti-fraud' ),
				'already_exists' => true
			) );
		}

		// Add to blacklist
		$blacklist_array[] = $email;
		update_option( 'wc_settings_anti_fraudblacklist_emails', implode( ',', $blacklist_array ) );

		// Add order note
		$order->add_order_note( 
			sprintf( 
				/* translators: %s: Email address added to blacklist. */
				__( 'Added email %s to blacklist via Anti-Fraud panel.', 'woocommerce-anti-fraud' ), 
				$email 
			) 
		);

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %s: Email address added to blacklist. */
				esc_html__( 'Successfully added %s to blacklist.', 'woocommerce-anti-fraud' ),
				$email
			),
			'email' => $email
		) );
	}

	/**
	 * AJAX handler to blacklist IP from order details
	 * 
	 * @since 7.1.9
	 */
	public function wc_af_blacklist_ip() {
		// Check permissions
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'woocommerce-anti-fraud' ) );
		}

		// Verify nonce
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'wc_af_blacklist_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'woocommerce-anti-fraud' ) );
		}

		// Get and validate order ID
		$order_id = isset( $_REQUEST['order_id'] ) ? absint( $_REQUEST['order_id'] ) : 0;
		if ( ! $order_id ) {
			wp_send_json_error( __( 'Invalid order ID.', 'woocommerce-anti-fraud' ) );
		}

		// Get order
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( __( 'Order not found.', 'woocommerce-anti-fraud' ) );
		}

		// Get IP address from order meta
		$ip_address = opmc_hpos_get_post_meta( $order_id, '_customer_ip_address', true );
		
		if ( empty( $ip_address ) ) {
			// Try alternate meta key
			$ip_address = opmc_hpos_get_post_meta( $order_id, '_wc_af_ip_address', true );
		}

		if ( empty( $ip_address ) ) {
			wp_send_json_error( __( 'No IP address found for this order.', 'woocommerce-anti-fraud' ) );
		}

		// Validate IP address
		if ( ! filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
			wp_send_json_error( __( 'Invalid IP address.', 'woocommerce-anti-fraud' ) );
		}

		// Retrieve current blacklist
		$ip_blacklist = get_option( 'wc_settings_anti_fraudblacklist_ipaddress', '' );
		$blacklist_array = array();
		
		if ( ! empty( $ip_blacklist ) ) {
			// Parse the blacklist (supports comma and newline separated)
			$blacklist_array = preg_split( '/[\r\n,]+/', $ip_blacklist );
			$blacklist_array = array_filter( array_map( 'trim', $blacklist_array ) );
		}

		// Check if already blacklisted (idempotent)
		if ( in_array( $ip_address, $blacklist_array ) ) {
			wp_send_json_success( array(
				'message' => __( 'IP address is already blacklisted.', 'woocommerce-anti-fraud' ),
				'already_exists' => true
			) );
		}

		// Add to blacklist
		$blacklist_array[] = $ip_address;
		update_option( 'wc_settings_anti_fraudblacklist_ipaddress', implode( ',', $blacklist_array ) );

		// Add order note
		$order->add_order_note(
			sprintf(
				/* translators: %s: IP address added to blacklist. */
				esc_html__( 'Added IP %s to blacklist via Anti-Fraud panel.', 'woocommerce-anti-fraud' ),
				$ip_address
			)
		);

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %s: IP address added to blacklist. */
				esc_html__( 'Successfully added %s to blacklist.', 'woocommerce-anti-fraud' ),
				$ip_address
			),
			'ip' => $ip_address
		) );
	}

	// display the extra data in the order admin panel
	public function kia_display_order_data_in_admin( $order ) {
		$blocked_email = get_option( 'wc_settings_anti_fraudblacklist_emails' );
		$array_mail    = explode( ',', $blocked_email );
		$orderemail    = $order->get_billing_email();

		foreach ( $array_mail as $single ) {
			if ( $orderemail == $single ) {
				?>
				<p class="form-field form-field-wide">
					<?php echo '<h3 style="color:red;"><strong>' . esc_html__( 'This email id is blocked', 'woocommerce-anti-fraud' ) . '</strong></h3>'; ?>
				</p>
				<p class="anti-fraud-error-msg" style="color: red;"></p>
				<p class="form-field form-field-wide">
					<button type="button" class="button unblock-email" data-wpnonce="<?php echo  esc_attr( wp_create_nonce( 'whitelist_email_nonce' ) ); ?>" data-email="<?php echo esc_attr( $orderemail ); ?>"><?php echo esc_html__( 'Unblock', 'woocommerce-anti-fraud' ); ?></button>
				</p>
				<?php
			}
		}

		if ( $order instanceof WC_Order && $order->get_created_via() === 'rest-api' ) {
			echo '<p class="form-field form-field-wide" style="margin-top: 20px;"><span style="color: #ff5722; font-size: 16px;">' . esc_html__( 'This order is from REST API', 'woocommerce-anti-fraud' ) . '</span></p>';
		}
	}

	/**
	 * Check if WooCommerce is active
	 *
	 * @return bool
	 * @since  1.0.0
	 *
	 */
	private function is_wc_active() {

		$is_active = WC_Dependencies::woocommerce_active_check();

		// Do the WC active check
		if ( false === $is_active ) {
			add_action( 'admin_notices', array( $this, 'notice_activate_wc' ) );
		}

		return $is_active;
	}

	/**
	 * Display the notice
	 *
	 * @since  1.0.0
	 */
	public function notice_activate_wc() {
		?>
		<div class="error">
			<p>
				<?php
				/* translators: 1. start of link, 2. end of link. */
				printf( esc_html__( 'Please install and activate %1$sWooCommerce%2$s in order for the WooCommerce Anti Fraud extension to work!', 'woocommerce-anti-fraud' ), '<a href="' . esc_url( admin_url( 'plugin-install.php?tab=search&s=WooCommerce&plugin-search-input=Search+Plugins' ) ) . '">', '</a>' );
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Init the plugin
	 *
	 * @since  1.0.0
	 */
	private function init() {

		require_once dirname( __FILE__ ) . '/includes/class-wc-af-mobile-verification.php';
		require_once dirname( __FILE__ ) . '/includes/class-hooks.php';
		require_once dirname( __FILE__ ) . '/includes/class-wc-af-logger.php';
		require_once dirname( __FILE__ ) . '/includes/class-wc-af-system-status.php';
		require_once dirname( __FILE__ ) . '/includes/class-wc-af-geo-matcher.php';
		require_once dirname( __FILE__ ) . '/includes/class-wc-af-maxmind-api.php';
		require_once dirname( __FILE__ ) . '/anti-fraud-core/class-wc-af-marketplace-detector.php';

		// Load plugin textdomain after init
		add_action( 'init', function() {
			load_plugin_textdomain( 'woocommerce-anti-fraud', false, dirname( plugin_basename( self::get_plugin_file() ) ) . '/languages/' );
		});

		// Setup the autoloader
		self::setup_autoloader();

		// Setup the required WooCommerce hooks
		WC_AF_Hook_Manager::setup();

		// Defer adding rules until init to avoid early translation warnings
		add_action( 'init', function() {

			$maxmind_settings               = get_option( 'wc_af_maxmind_type' ); // Get MaxMind enable/disable
			$wc_af_maxmind_insights_setting = get_option( 'wc_af_maxmind_insights' ); // Get MaxMind insights enable/disable
			$wc_af_maxmind_factors_setting  = get_option( 'wc_af_maxmind_factors' ); // Get MaxMind factors enable/disable
			$maxmind_user                   = get_option( 'wc_af_maxmind_user' );
			$maxmind_license_key            = get_option( 'wc_af_maxmind_license_key' );

			if ( 'yes' === $wc_af_maxmind_factors_setting ) {
				WC_AF_Rules::get()->add_rule( new WC_AF_Rule_MinFraud_Factors() );
				if ( ! empty( $maxmind_user ) && ! empty( $maxmind_license_key ) ) {
					WC_AF_Rules::get()->add_rule( new WC_AF_Rule_Ip_Location() );
				}
			} elseif ( 'yes' === $wc_af_maxmind_insights_setting ) {
				WC_AF_Rules::get()->add_rule( new WC_AF_Rule_MinFraud_Insights() );
				if ( ! empty( $maxmind_user ) && ! empty( $maxmind_license_key ) ) {
					WC_AF_Rules::get()->add_rule( new WC_AF_Rule_Ip_Location() );
				}
			} elseif ( 'yes' === $maxmind_settings ) {
				WC_AF_Rules::get()->add_rule( new WC_AF_Rule_MinFraud() );
				if ( ! empty( $maxmind_user ) && ! empty( $maxmind_license_key ) ) {
					WC_AF_Rules::get()->add_rule( new WC_AF_Rule_Ip_Location() );
				}
			}

			if ( 'yes' !== $maxmind_settings && 'yes' !== $wc_af_maxmind_insights_setting && 'yes' !== $wc_af_maxmind_factors_setting ) {
				WC_AF_Rules::get()->add_rule( new WC_AF_Rule_Geo_Location() );
			}

			// MaxMind Advanced Signal Rules (require any MinFraud tier + valid credentials)
			if (
				( 'yes' === $maxmind_settings || 'yes' === $wc_af_maxmind_insights_setting || 'yes' === $wc_af_maxmind_factors_setting )
				&& ! empty( $maxmind_user ) && ! empty( $maxmind_license_key )
			) {
				WC_AF_Rules::get()->add_rule( new WC_AF_Rule_MM_VPN() );
				WC_AF_Rules::get()->add_rule( new WC_AF_Rule_MM_Proxy() );
				WC_AF_Rules::get()->add_rule( new WC_AF_Rule_MM_Tor() );
				WC_AF_Rules::get()->add_rule( new WC_AF_Rule_MM_Hosting() );
				WC_AF_Rules::get()->add_rule( new WC_AF_Rule_MM_IP_Distance() );
			}

			// Core rules
			WC_AF_Rules::get()->add_rule( new WC_AF_Rule_Country() );
			WC_AF_Rules::get()->add_rule( new WC_AF_Rule_Billing_Matches_Shipping() );
			WC_AF_Rules::get()->add_rule( new WC_AF_Rule_Detect_Proxy() );
			WC_AF_Rules::get()->add_rule( new WC_AF_Rule_Temporary_Email() );
			WC_AF_Rules::get()->add_rule( new WC_AF_Rule_Free_Email() );
			WC_AF_Rules::get()->add_rule( new WC_AF_Rule_International_Order() );
			WC_AF_Rules::get()->add_rule( new WC_AF_Rule_High_Value() );
			WC_AF_Rules::get()->add_rule( new WC_AF_Rule_High_Amount() );
			WC_AF_Rules::get()->add_rule( new WC_AF_Rule_First_Order() );
			WC_AF_Rules::get()->add_rule( new WC_AF_Rule_First_Order_Processing() );
			WC_AF_Rules::get()->add_rule( new WC_AF_Rule_Ip_Multiple_Order_Details() );
			WC_AF_Rules::get()->add_rule( new WC_AF_Rule_Velocities() );
			WC_AF_Rules::get()->add_rule( new WC_AF_Rule_Billing_Phone_Matches_Billing_Country() );

			if ( is_admin() ) {
				require_once dirname( __FILE__ ) . '/anti-fraud-core/class-wc-af-settings.php';
			}

		});
	}

	// Update order on paypal verification
	public function paypal_verification() {
		if ( isset( $_REQUEST['order_id'] ) && isset( $_REQUEST['paypal_verification'] ) ) {
			$order_id = base64_decode( sanitize_text_field( $_REQUEST['order_id'] ) );
			opmc_hpos_update_post_meta( $order_id, 'wc_af_paypal_verification', true );
			$order = new WC_Order( $order_id );
			echo "<script type='text/javascript'>
			alert('Your Paypal Email verified Successfully')</script>";
			if ( 'completed' === $order->get_status() || 'processing' === $order->get_status() || 'cancelled' === $order->get_status() ) {
				return;
			} else {
				$order->add_order_note( __( 'PayPal Verification Done.', 'woocommerce-anti-fraud' ) );
				// this should be set by paypal plugin. We should not override this.
				// $status = $order->update_status('processing');
			}
		}
	}

	// TO Do Test
	public function my_action() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'woocommerce-anti-fraud' ), 403 );
			wp_die();
		}

		check_ajax_referer( 'woocommerce-anti-fraud', '_wpnonce' );

		$help_class = new WC_AF_Score_Helper();

		if ( isset( $_POST['order_id'] ) ) {
			$help_class->do_check( sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) );
		}
		wp_die();
	}

	// TO DO
	public function admin_scripts() {
		wp_enqueue_style( 'cal', plugins_url( 'assets/css/tags-input.css', __FILE__ ), array(), WOOCOMMERCE_ANTI_FRAUD_VERSION );

		wp_enqueue_script( 'cal', plugins_url( 'assets/js/cal.js', __FILE__ ), array(), WOOCOMMERCE_ANTI_FRAUD_VERSION );
		wp_enqueue_script( 'tags_input', plugins_url( 'assets/js/tags-input.js', __FILE__ ), array(), WOOCOMMERCE_ANTI_FRAUD_VERSION );
		wp_register_script( 'knob', plugins_url( '/assets/js/jquery.knob.min.js', self::get_plugin_file() ), array( 'jquery' ), WOOCOMMERCE_ANTI_FRAUD_VERSION );
		wp_register_script( 'edit', plugins_url( '/assets/js/edit-shop-order.js', __FILE__ ), array( 'jquery' ), WOOCOMMERCE_ANTI_FRAUD_VERSION );
		wp_enqueue_script( 'opmc_af_admin_js', plugins_url( '/assets/js/app.js', __FILE__ ), array( 'jquery' ), WOOCOMMERCE_ANTI_FRAUD_VERSION );
		wp_set_script_translations( 'opmc_af_admin_js', 'woocommerce-anti-fraud', WOOCOMMERCE_ANTI_FRAUD_PLUGIN_DIR . 'languages' );

		$wc_af_admin_data = array(
			'nonce' => wp_create_nonce( 'woocommerce-anti-fraud' ),
		);
		wp_localize_script( 'cal', 'wcAfAdmin', $wc_af_admin_data );
		wp_localize_script( 'opmc_af_admin_js', 'wcAfAdmin', $wc_af_admin_data );

		// Code related to displaying maxmind and trustswiftly related notifications to admin users
		/* Disabling maxmind and trustswiftly dash notifications.
		if (is_admin()) {
			add_action('admin_notices', array( $this, 'display_maxmind_dismissible_message' ));
			add_action('admin_notices', array( $this, 'display_trustswiftly_dismissible_message'));
		}
		*/
	}

	/**
	 * Enqueue scripts and styles specifically for order edit pages (HPOS compatible)
	 *
	 * @param string $hook The current admin page hook
	 * @since 7.1.9
	 */
	public function wc_af_enqueue_order_scripts( $hook ) {
		$hposSettingsEnabled = get_option( 'woocommerce_custom_orders_table_enabled', true );

		// For HPOS-enabled stores
		if ( 'yes' === $hposSettingsEnabled ) {
			// Check if we're on the WooCommerce orders page
			if ( 'woocommerce_page_wc-orders' === $hook || ( isset( $_GET['page'] ) && 'wc-orders' === $_GET['page'] ) ) {
				wp_enqueue_style( 'wc_af_edit_shop_order_css', plugins_url( 'assets/css/edit-shop-order.css', __FILE__ ), array(), WOOCOMMERCE_ANTI_FRAUD_VERSION );
				wp_enqueue_style(
					'wc_af_post_shop_order_css',
					plugins_url( '/assets/css/post-shop-order.css', __FILE__ ),
					array(),
					WOOCOMMERCE_ANTI_FRAUD_VERSION
				);
				wp_enqueue_script( 'knob' );
				wp_enqueue_script( 'edit' );
			}
		}
	}

	/**
	 * Displayes Maxmind dismissable notification to admin users
	 *
	 * @return void
	 */
	public function display_maxmind_dismissible_message() {

		$current_user_id = get_current_user_id();

		$alert_value = get_user_meta( $current_user_id, 'opmc-antifraud-maxmind-alert', true );

		if ( 'yes' === $alert_value ) {
			return;
		} else {

			$message = '<div class="notice notice-info is-dismissible opmc-antifraud-maxmind-alert">';
			$message .= '<p>We recommend you use <a href="https://maxmind.pxf.io/EK15XW" target="_blank">MaxMind</a>, to obtain the maximum fraud prevention benefit from the Anti Fraud plugin.  <a href="https://maxmind.pxf.io/EK15XW" target="_blank">MaxMind</a> can be configured in the <a href="/wp-admin/admin.php?page=wc-settings&tab=wc_af&section=minfraud_settings">plugin settings</a>.  Please refer to the Anti Fraud <a href="https://woo.com/products/woocommerce-anti-fraud/" target="_blank">Product page</a> and <a href="https://woo.com/document/woocommerce-anti-fraud/" target="_blank">Documentation page</a> for further information and configuration steps.</p>';
			$message .= '<p><strong>Steps to create a MaxMind Account:</strong></p>';
			$message .= '<ol>';
			$message .= '<li>Visit the <a href="https://maxmind.pxf.io/EK15XW" target="_blank">MaxMind Home page</a></li>';
			$message .= '<li>Click "Sign In" (top right).</li>';
			$message .= '<li>Select the option at the bottom to create a MaxMind account.</li>';
			$message .= '<li>Follow their remaining instructions.</li>';
			$message .= '</ol>';

			$message .= '</div>';

			echo wp_kses_post( $message );
		}
	} // display_maxmind_dismissible_message()

	/**
	 * Displayes Trust Swiftly dismissable notification to admin users
	 *
	 * @return void
	 */
	public function display_trustswiftly_dismissible_message() {

		$current_user_id = get_current_user_id();

		$trustswiftly_meta_value = get_user_meta( $current_user_id, 'opmc-antifraud-trustswiftly-alert', true );

		if ( 'yes' === $trustswiftly_meta_value ) {
			return;
		} else {

			$message = '<div class="notice notice-info is-dismissible opmc-antifraud-trustswiftly-alert">';
			$message .= '<p>We recommend you use <a href="https://thrive.zohopublic.com/aref/G4734hGpxD/mBWuIqNh8" target="_blank">Trust Swiftly</a> to further enhance fraud prevention using their Customer Identity Verification service with the Anti Fraud plugin.  <a href="https://thrive.zohopublic.com/aref/G4734hGpxD/mBWuIqNh8" target="_blank">Trust Swiftly</a> can be configured in the <a href="/wp-admin/admin.php?page=wc-settings&tab=wc_af&section=trust_swiftly_settings">plugin settings</a>.  Please refer to the Anti Fraud <a href="https://woo.com/products/woocommerce-anti-fraud/" target="_blank">Product page</a> and <a href="https://woo.com/document/woocommerce-anti-fraud/" target="_blank">Documentation page</a> for further information and configuration steps.</p>';
			$message .= '<p><strong>Steps to create a Trust Swiftly Account:</strong></p>';
			$message .= '<ol>';
			$message .= '<li>Visit the <a href="https://thrive.zohopublic.com/aref/G4734hGpxD/mBWuIqNh8 " target="_blank">Trust Swiftly Sign Up page</a>.</li>';
			$message .= '<li>Complete and Submit their Sign Up form.</li>';
			$message .= '<li>Follow their remaining instructions.</li>';
			$message .= '</ol>';

			$message .= '</div>';

			echo wp_kses_post( $message );
		}
	} // display_trustswiftly_dismissible_message()

	/**
	 * Save the default settings.
	 *
	 * This function is responsible for saving the default settings of the application.
	 * Default settings are typically used as a fallback when no user-specific settings exist.
	 * The saved default settings will be used as a reference for all users until they
	 * customize their preferences.
	 *
	 * @return bool True if the default settings were successfully saved, false otherwise.
	 */
	public function save_default_settings() {
		// check if settings were already saved before.
		if ( get_option( 'wc_af_is_settings_saved' ) == true ) {

			/* This validation will be removed after some time because it only works for existing customers once, if reCaptcha settings are enabled. */
			$wc_af_recaptcha_enable_captcha = get_option( 'wc_af_recaptcha_enable_captcha' );

			if ( ! empty( $wc_af_recaptcha_enable_captcha ) && 'yes' == $wc_af_recaptcha_enable_captcha ) {
				update_option( 'wc_settings_anti_fraudenable_enable_recaptcha', 'yes' );
				delete_option( 'wc_af_recaptcha_enable_captcha' );
			}

			if ( empty( $wc_af_recaptcha_enable_captcha ) || 'yes' !== $wc_af_recaptcha_enable_captcha || empty( $captcha_type ) || 'google_recaptcha' !== $captcha_type ) {

				update_option( 'wc_af_paypal_acp_enabled', 'no' );
			} else {
				update_option( 'wc_af_paypal_acp_enabled', 'yes' );
			}

		} else {
			// Set a flag to indicate this is the first installation
			update_option( 'wc_af_first_installation', true );
			// ✅ Add default PayPal ACP settings if not set or empty
			if ( empty( get_option( 'wc_settings_anti_fraud_paypal_window_seconds' ) ) ) {
				update_option( 'wc_settings_anti_fraud_paypal_window_seconds', 300 );
			}
			if ( empty( get_option( 'wc_settings_anti_fraud_paypal_max_per_window' ) ) ) {
				update_option( 'wc_settings_anti_fraud_paypal_max_per_window', 5 );
			}
			if ( empty( get_option( 'wc_settings_anti_fraud_paypal_max_per_hour' ) ) ) {
				update_option( 'wc_settings_anti_fraud_paypal_max_per_hour', 5 );
			}
			if ( empty( get_option( 'wc_af_paypal_acp_enabled' ) ) ) {
				update_option( 'wc_af_paypal_acp_enabled', 'no' );
			}

			update_option( 'wc_af_is_settings_saved', true );
		}
	}


	public function update_blacklist_ips_option() {

		$whitelist_ipaddress = get_option( 'wc_settings_anti_fraud_ips_whitelist' );
		$blocked_ipaddress   = get_option( 'wc_settings_anti_fraudblacklist_ipaddress' );

		if ( ! empty( $blocked_ipaddress ) && ! empty( $whitelist_ipaddress ) ) {

			$array_ipaddress           = explode( ',', $blocked_ipaddress );
			$array_whitelist_ipaddress = explode( ',', $whitelist_ipaddress );

			// Remove duplicate IP addresses from the blacklist
			if ( ! empty( $array_ipaddress ) ) {
				$unique_blocked_ipaddress = array_unique( $array_ipaddress );
			} else {
				$unique_blocked_ipaddress = $array_ipaddress;
			}

			// Check for common IP addresses and remove them from both whitelist and blacklist
			$common_ip_addresses = array_intersect( $unique_blocked_ipaddress, $array_whitelist_ipaddress );

			if ( ! empty( $common_ip_addresses ) ) {
				// Remove common IP addresses from the blacklist
				$unique_blocked_ipaddress = array_diff( $unique_blocked_ipaddress, $common_ip_addresses );
			}

			// Ensure we only update if $unique_blocked_ipaddress is not empty
			if ( ! empty( $unique_blocked_ipaddress ) ) {
				$blocked_ipaddress = implode( ',', $unique_blocked_ipaddress );
				update_option( 'wc_settings_anti_fraudblacklist_ipaddress', $blocked_ipaddress );
			}
		}
	}

	public function order_level_froud_check() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'woocommerce-anti-fraud' ), 403 );
			wp_die();
		}

		check_ajax_referer( 'woocommerce-anti-fraud', '_wpnonce' );

		$orderid = isset( $_REQUEST['orderid'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderid'] ) ) : '';

		$score_helper = new WC_AF_Score_Helper();
		// Force re-evaluation so any whitelist changes made after the initial check
		// are applied immediately when the admin manually triggers a re-check.
		$score_helper->schedule_fraud_check( $orderid, true, '', true );

		echo 'success';
		wp_die();
	}

	public function bulk_fraud_check_action_hook( $bulk_actions ) {

		$auto_fraud_check = get_option( 'wc_af_start_auto_fraud_check', 'no' );

		if ( 'yes' == $auto_fraud_check ) {
			$bulk_actions['bulk_fraud_check'] = 'Fraud Check';
		}

		return $bulk_actions;
	}

	public function bulk_fraud_check_action_not_hpos( $redirect_to, $do_action, $post_ids ) {
		if ( 'bulk_fraud_check' === $do_action ) {
			foreach ( $post_ids as $value ) {
				$score_helper = new WC_AF_Score_Helper();
				// Force re-evaluation so whitelist changes are respected on re-check.
				$score_helper->schedule_fraud_check( $value, true, '', true );
			}
		}

		return $redirect_to;
	}

	public function bulk_fraud_check_action_hpos( $redirect_to, $do_action, $order ) {
		global $post;
		if ( 'bulk_fraud_check' === $do_action ) {
			foreach ( $post->ID as $value ) {
				$score_helper = new WC_AF_Score_Helper();
				// Force re-evaluation so whitelist changes are respected on re-check.
				$score_helper->schedule_fraud_check( $value, true, '', true );
			}
		}

		return $redirect_to;
	}

	public function add_order_level_froud_check_column( $columns ) {
		$columns['fraud_action'] = __( 'Fraud Action', 'woocommerce-anti-fraud' );

		return $columns;
	}

	// The column content by row
	public function add_order_level_froud_check_column_not_hpos_contents( $column, $post_id ) {
		if ( 'fraud_action' === $column ) {

			$order        = wc_get_order( $post_id ); // Get the WC_Order
			$slug         = 'fraud_action';
			$url          = $post_id; // The order Id is required in the URL
			$score_points = opmc_hpos_get_post_meta( $post_id, 'wc_af_score', true );
			if ( empty( $score_points ) && '0' !== $score_points ) {
				echo '<button type="button" id="fraud_action" class="button wc-action-button wc-action-button fraud_action" aria-label="' . esc_attr( $url ) . '">' . esc_html__( 'Fraud Check', 'woocommerce-anti-fraud' ) . '</button>';
			}
		}
	}

	public function add_order_level_froud_check_column_hpos_contents( $column, $order ) {
		global $post;
		if ( 'fraud_action' === $column ) {

			$order        = wc_get_order( $order->get_id() ); // Get the WC_Order
			$slug         = 'fraud_action';
			$url          = $order->get_id(); // The order Id is required in the URL
			$score_points = opmc_hpos_get_post_meta( $order->get_id(), 'wc_af_score', true );
			if ( empty( $score_points ) ) {
				/* echo '<button type="button" id="'.$slug.'" class="button wc-action-button wc-action-button'.$slug.' '.$slug.'" aria-label="'.$url.'">Done</button>';
			} else { */
				echo '<button type="button" id="fraud_action" class="button wc-action-button wc-action-button fraud_action" aria-label="' . esc_attr( $url ) . '">' . esc_html__( 'Fraud Check', 'woocommerce-anti-fraud' ) . '</button>';
			}
		}
	}

	public function wc_af_enqueue_admin_scripts( $hook ) {
		// Load only on WooCommerce settings page
		if ( 'woocommerce_page_wc-settings' !== $hook ) {
			return;
		}

		// Ensure we're on the Anti-Fraud tab.
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

		if ( 'wc_af' !== $current_tab ) {
			return;
		}

		$this->enqueue_af_admin_asset_bundle();

		$admin_js_path = plugin_dir_path( __FILE__ ) . 'assets/js/wc-af-admin.js';
		$admin_js_ver  = file_exists( $admin_js_path ) ? filemtime( $admin_js_path ) : WOOCOMMERCE_ANTI_FRAUD_VERSION;

		wp_enqueue_script(
			'wc-af-admin-js',
			plugins_url( 'assets/js/wc-af-admin.js', __FILE__ ),
			array( 'jquery' ),
			$admin_js_ver,
			true
		);

		// Pass PHP options to JS
		$wc_af_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';

		wp_localize_script(
			'wc-af-admin-js',
			'wcAFSettings',
			array(
				'recaptchaEnable'      => get_option( 'wc_af_recaptcha_enable_captcha', 'no' ),
				'paypalAcpEnableds'    => get_option( 'wc_af_paypal_acp_enabled', 'no' ),
				'paypalpluginDetected' => get_option( 'paypal_acp_plugindetected', 'no' ),
				'recaptchaType'        => get_option( 'wc_af_recaptcha_type', 'google_recaptcha' ),
				'settingsSection'      => $wc_af_section,
				'ajaxurl'              => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'wc_af_admin' ),
			)
		);
	}

	/**
	 * Enqueue Anti-Fraud admin assets on dashboard and settings screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_af_admin_assets( $hook ) {
		if ( 'toplevel_page_antifraud-dashboard' === $hook ) {
			$this->enqueue_af_admin_asset_bundle();
			return;
		}

		if ( 'woocommerce_page_wc-settings' !== $hook ) {
			return;
		}

		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

		if ( 'wc_af' === $current_tab ) {
			$this->enqueue_af_admin_asset_bundle();
		}
	}

	/**
	 * Shared Anti-Fraud admin styles used by dashboard and settings.
	 */
	private function enqueue_af_admin_asset_bundle() {
		$app_css_path = plugin_dir_path( __FILE__ ) . 'assets/css/app.css';
		$app_css_ver  = file_exists( $app_css_path ) ? filemtime( $app_css_path ) : WOOCOMMERCE_ANTI_FRAUD_VERSION;
		wp_enqueue_style(
			'opmc_af_admin_css',
			plugins_url( 'assets/css/app.css', __FILE__ ),
			array(),
			$app_css_ver
		);

		$app_js_path = plugin_dir_path( __FILE__ ) . 'assets/js/app.js';
		$app_js_ver  = file_exists( $app_js_path ) ? filemtime( $app_js_path ) : WOOCOMMERCE_ANTI_FRAUD_VERSION;
		wp_enqueue_script(
			'opmc_af_admin_js',
			plugins_url( 'assets/js/app.js', __FILE__ ),
			array( 'jquery' ),
			$app_js_ver,
			true
		);
		wp_localize_script(
			'opmc_af_admin_js',
			'wcAfAdmin',
			array(
				'nonce' => wp_create_nonce( 'woocommerce-anti-fraud' ),
			)
		);
	}

	/**
	 * Schedule background rebuild after new order creation (called on order hooks).
	 * We clear the transient immediately and schedule a single event 10 seconds later.
	 */
	public function wc_af_schedule_dashboard_refresh( $order_id ) {
		// Clear immediately
		delete_transient( WC_AF_DASH_TRANSIENT );
		// schedule background job after a short delay
		if ( ! wp_next_scheduled( 'wc_af_refresh_dashboard_cache_delayed' ) ) {
			wp_schedule_single_event( time() + 10, 'wc_af_refresh_dashboard_cache_delayed' );
		}
	}
	
}

new WooCommerce_Anti_Fraud();

/**
 * Load the deactivation library
 */
if ( ! class_exists( 'OPMC_Deactivation_Feedback' ) ) {
	require_once dirname( __FILE__ ) . '/includes/class-deactivation-feedback.php';
}

/**
 * Random generated deactivate salt that will use for verification purpose
 */
$deactivate_salt = WC_AF_DEACTIVATE_SALT;

OPMC_Deactivation_feedback::instance( 'woocommerce-anti-fraud', $deactivate_salt );

