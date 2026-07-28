<?php
/**
 * Searchanise initialization
 *
 * @package Searchanise/Init
 */

namespace Searchanise\SmartWoocommerceSearch;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Initialized Searchanise variables
 */
function fn_se_define_constants() {
	$upload_dir = wp_upload_dir( null, false );

	fn_se_define( 'SE_DEBUG_LOG', false );     // Log debug messages.
	fn_se_define( 'SE_ERROR_LOG', false );     // Log error messages.
	fn_se_define( 'SE_DEBUG', false );         // Print debug & error messages.

	fn_se_define( 'SE_REQUEST_TIMEOUT', 30 );       // API request timeout.
	fn_se_define( 'SE_REGISTER_TIMEOUT', 60 );      // Signup request timeout.
	fn_se_define( 'SE_SHORT_REQUEST_TIMEOUT', 5 );  // API short request timeout.

	fn_se_define( 'SE_PRODUCTS_PER_PASS', 100 );
	fn_se_define( 'SE_CATEGORIES_PER_PASS', 500 );
	fn_se_define( 'SE_PAGES_PER_PASS', 100 );

	fn_se_define( 'SE_USE_GDDPR_REGISTRATION', true );
	fn_se_define( 'SE_CACHE_SETTINGS', true );

	fn_se_define( 'SE_VERSION', '1.3' );
	fn_se_define( 'SE_PLUGIN_VERSION', '1.0.20' );
	fn_se_define( 'SE_MEMORY_LIMIT', '512M' );
	fn_se_define( 'SE_MAX_ERROR_COUNT', 3 );
	fn_se_define( 'SE_MAX_PROCESSING_TIME', 720 );
	fn_se_define( 'SE_MAX_SEARCH_REQUEST_LENGTH', 8000 );
	fn_se_define( 'SE_SERVICE_URL', 'http://searchserverapi1.com' );
	fn_se_define( 'SE_PLATFORM', 'woocommerce' );
	fn_se_define( 'SE_SUPPORT_EMAIL', 'feedback@searchanise.com' );

	fn_se_define( 'SE_ABSPATH', __DIR__ );
	fn_se_define( 'SE_PLUGIN_BASENAME', plugin_basename( __DIR__ . DIRECTORY_SEPARATOR . 'woocommerce-searchanise.php' ) );
	fn_se_define( 'SE_BASE_DIR', basename( __DIR__ ) );
	fn_se_define( 'SE_LOG_DIR', $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'se_logs' );
	fn_se_define( 'SE_TEMPLATES_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR );
	fn_se_define( 'SE_VENDOR_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR );
}

/**
 * Define variable if not defined
 *
 * @param string $name Var name.
 * @param mixed  $val  Value.
 */
function fn_se_define( $name, $val ) {
	if ( ! defined( $name ) ) {
		define( $name, $val ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.VariableConstantNameFound
	}
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) && is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';

	if ( file_exists( __DIR__ . '/local_conf.php' ) ) {
		include __DIR__ . '/local_conf.php';
	}

	fn_se_define_constants();
	Bootstrap::init();

} elseif ( is_admin() ) {
	add_action(
		'admin_notices',
		function () { ?>
		<div class="notice notice-error is-dismissible">
			<p>
				<strong><?php esc_attr_e( 'Searchanise:', 'smart-search-for-woocommerce' ); ?></strong>&nbsp;
				<?php
				printf(
					/* translators: %s: file name with path */
					esc_html__( 'File "%s"  was not loaded. Please check file and it\'s permissions.', 'smart-search-for-woocommerce' ),
					esc_attr( __DIR__ . '/vendor/autoload.php' )
				)
				?>
			</p>
		</div>
			<?php
		}
	);
}
