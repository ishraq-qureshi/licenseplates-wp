<?php
/**
 * Searchanise bootsrap
 *
 * @package Searchanise/Bootstrap
 */

namespace Searchanise\SmartWoocommerceSearch;

defined( 'ABSPATH' ) || exit;

use Searchanise\Extensions\WcWeglot;
use Searchanise\Extensions\WcSeJetpack;

/**
 * Bootstrap class
 */
class Bootstrap {
	/**
	 * Initialization
	 */
	public static function init() {
		// Init translations.
		add_action( 'init', 'fn_se_load_plugin_textdomain' );

		// Init logger.
		add_action(
			'init',
			function () {
				$GLOBALS['SearchaniseLogger'] = Logger::get_instance(
					array(
						'log_dir'      => SE_LOG_DIR,
						'log_debug'    => SE_DEBUG_LOG,
						'log_errors'   => SE_ERROR_LOG,
						'output_debug' => SE_DEBUG,
					)
				);
			}
		);

		if ( defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			return;
		}

		if ( SE_DEBUG ) {
			fn_se_define( 'WP_DEBUG', true );
			fn_se_define( 'WP_DEBUG_DISPLAY', true );
		}

		if ( isset( $_REQUEST[ Async::FL_DISPLAY_ERRORS ] ) && Async::FL_DISPLAY_ERRORS_KEY == $_REQUEST[ Async::FL_DISPLAY_ERRORS ] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			fn_se_define( 'WP_DEBUG', true );
			fn_se_define( 'WP_DEBUG_DISPLAY', true );
		}

		add_action( 'plugins_loaded', array( __CLASS__, 'plugin_loaded' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'load_extensions' ), 15 );
		add_action( 'wp_dashboard_setup', array( Admin_Dashboard::class, 'init' ) );

		// Init Searchanise Async.
		add_action( 'plugins_loaded', array( Async::class, 'init' ), Api::LOAD_PRIORITY );

		// Add to cart action.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			add_action( 'wp_ajax_se_ajax_add_to_cart', array( Search_Results::class, 'ajax_add_to_cart' ) );
			add_action( 'wp_ajax_nopriv_se_ajax_add_to_cart', array( Search_Results::class, 'ajax_add_to_cart' ) );
		}

		// Init Se info.
		add_action( 'wp_ajax_nopriv_se_info', array( Info::class, 'display' ) );
		add_action( 'wp_ajax_se_info', array( Info::class, 'display' ) );
		add_action( 'init', array( Info::class, 'init' ) );

		// Init Se cron.
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			add_action(
				'init',
				function () {
					/**
					 * Initialize Woocommerce session
					 *
					 * @since 3.6.4
					 */
					$session_class    = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
					$default_location = wc_get_customer_default_location();

					WC()->session = new $session_class();
					WC()->session->set(
						'customer',
						array(
							'postcode'         => WC()->countries->get_base_postcode(),
							'city'             => WC()->countries->get_base_city(),
							'address_1'        => WC()->countries->get_base_address(),
							'address_2'        => WC()->countries->get_base_address_2(),
							'country'          => isset( $default_location['country'] ) ? $default_location['country'] : WC()->countries->get_base_country(),
							'billing_country'  => isset( $default_location['country'] ) ? $default_location['country'] : WC()->countries->get_base_country(),
							'shipping_country' => isset( $default_location['country'] ) ? $default_location['country'] : WC()->countries->get_base_country(),
							'state'            => isset( $default_location['state'] ) ? $default_location['state'] : WC()->countries->get_base_state(),
							'billing_state'    => isset( $default_location['state'] ) ? $default_location['state'] : WC()->countries->get_base_state(),
							'shipping_state'   => isset( $default_location['state'] ) ? $default_location['state'] : WC()->countries->get_base_state(),
						)
					);
					WC()->customer = new \WC_Customer();
					include_once WC()->plugin_path() . '/includes/wc-cart-functions.php';
				}
			);
		}

		add_filter( 'cron_schedules', array( Cron::class, 'add_intervals' ) );
		add_action( 'wp', array( Cron::class, 'activate' ) );
		add_action( Cron::CRON_INDEX_EVENT, array( Cron::class, 'indexer' ) );
		add_action( Cron::CRON_RESYNC_EVENT, array( Cron::class, 'reimporter' ) );

		// Init hooks.
		$GLOBALS['Searchanise_Hooks'] = new Hooks();

		self::load_extensions();
	}

	/**
	 * Load plugins
	 */
	public static function plugin_loaded() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$GLOBALS['Searchanise_Cli'] = new Cli_Commands();

		} elseif ( ! is_admin() && ! defined( 'DOING_AJAX' ) && ! defined( 'DOING_CRON' ) ) {
			// Init Searchanise Recommendations.
			$GLOBALS['Searchanise_Recommendations'] = new Recommendations( Api::get_instance()->get_locale() );
			// Init widgets.
			add_action(
				'plugins_loaded',
				function () {
					$currently_language = Api::get_instance()->get_currently_language();
					// Init Searchresults widget.
					$GLOBALS['searchanise_Widget'] = new Search_Results( $currently_language );
					// Init fulltext search.
					$GLOBALS['Searchanise_Search'] = new Fulltext_Search( $currently_language );
					// Init Searchanise SmartNavigaion.
					$GLOBALS['Searchanise_Navigation'] = new Navigation( $currently_language );
				},
				Api::POSTPONED_LOAD_PRIORITY
			);

			Async::init();

		} elseif ( is_admin() && ! defined( 'DOING_AJAX' ) && ! defined( 'DOING_CRON' ) ) {
			$GLOBALS['Searchanise_Admin'] = new Admin();
		}
	}

	/**
	 * Load Extensions
	 *
	 * @return void
	 */
	public static function load_extensions() {
		$GLOBALS['Searchanise_Woocommerce_Weglot']  = new WcWeglot();
		$GLOBALS['Searchanise_Woocommerce_Jetpack'] = new WcSeJetpack();

		register_uninstall_hook( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'weglot/weglot.php', array( WcWeglot::class, 'uninstallAddon' ) );
	}
}
