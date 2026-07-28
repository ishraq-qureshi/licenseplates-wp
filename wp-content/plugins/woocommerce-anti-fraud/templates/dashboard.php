<?php
/**
 * Antifraud Dashboard (HPOS-optimized, cached, batched)
 *
 * Dashboard page for order details.
 *
 * @package WordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Config
 */
if ( ! defined( 'WC_AF_DASH_TRANSIENT' ) ) {
	define( 'WC_AF_DASH_TRANSIENT', 'wc_af_dashboard_stats_v3' );
}
if ( ! defined( 'WC_AF_DASH_TRANSIENT_TTL' ) ) {
	define( 'WC_AF_DASH_TRANSIENT_TTL', 60 * MINUTE_IN_SECONDS ); // 5 minutes
}
if ( ! defined( 'WC_AF_BATCH_SIZE' ) ) {
	define( 'WC_AF_BATCH_SIZE', 500 ); // batch size for wc_get_orders()
}


/**
 * Increase memory limit temporarily for this page.
 * Kept as requested but wrapped with function_exists guard.
 */
if ( function_exists( 'ini_set' ) ) {
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_set
	@ini_set( 'memory_limit', '1024M' );
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_set
	@ini_set( 'max_execution_time', 300 );
}


/**
 * Always return a valid WC_Order object (never a refund).
 */
function af_safe_order( $order ) {

	// Convert ID → order object
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	}

	if ( ! $order ) {
		return false;
	}

	// If it's a refund object, use parent order
	if ( $order instanceof WC_Order_Refund ) {
		$parent_id = $order->get_parent_id();
		if ( $parent_id ) {
			$order = wc_get_order( $parent_id );
		}
	}

	return $order instanceof WC_Order ? $order : false;
}

/**
 * Get or save the user's date range preference.
 * Note: This function may also be defined in woocommerce-anti-fraud.php for AJAX access.
 */
if ( ! function_exists( 'wc_af_get_date_range_preference' ) ) {
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
} // End if ! function_exists

/**
 * Parse date range preset into actual dates.
 * Returns array with 'start' and 'end' datetime strings, or false for 'all'.
 */
function wc_af_parse_date_range( $preset ) {
	$now = current_time( 'timestamp' );
	
	switch ( $preset ) {
		case 'last_15_days':
			return array(
				'start' => gmdate( 'Y-m-d H:i:s', strtotime( '-15 days', $now ) ),
				'end'   => gmdate( 'Y-m-d H:i:s', $now ),
			);
		case 'last_30_days':
			return array(
				'start' => gmdate( 'Y-m-d H:i:s', strtotime( '-30 days', $now ) ),
				'end'   => gmdate( 'Y-m-d H:i:s', $now ),
			);
		case 'last_60_days':
			return array(
				'start' => gmdate( 'Y-m-d H:i:s', strtotime( '-60 days', $now ) ),
				'end'   => gmdate( 'Y-m-d H:i:s', $now ),
			);
		case 'last_90_days':
			return array(
				'start' => gmdate( 'Y-m-d H:i:s', strtotime( '-90 days', $now ) ),
				'end'   => gmdate( 'Y-m-d H:i:s', $now ),
			);
		case 'all_orders':
			return false; // No date filtering
		default:
			// Handle custom range: format "custom_YYYY-MM-DD_YYYY-MM-DD"
			if ( strpos( $preset, 'custom_' ) === 0 ) {
				$dates = str_replace( 'custom_', '', $preset );
				$parts = explode( '_', $dates );
				if ( count( $parts ) === 2 ) {
					return array(
						'start' => gmdate( 'Y-m-d 00:00:00', strtotime( $parts[0] ) ),
						'end'   => gmdate( 'Y-m-d 23:59:59', strtotime( $parts[1] ) ),
					);
				}
			}
			// Default to last 30 days
			return array(
				'start' => gmdate( 'Y-m-d H:i:s', strtotime( '-30 days', $now ) ),
				'end'   => gmdate( 'Y-m-d H:i:s', $now ),
			);
	}
}

/**
 * Detect if store is large (>25k orders).
 */
function wc_af_is_large_store() {

	$count = wp_cache_get( 'wc_af_total_order_count' );

	if ( false === $count ) {

		global $wpdb;

		if ( function_exists( 'wc_get_container' ) 
			&& class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore' ) ) {

			// HPOS enabled
			$orders_table = $wpdb->prefix . 'wc_orders';

			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $orders_table ) ) === $orders_table ) {

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$count = $wpdb->get_var(
					'SELECT COUNT(*) FROM `' . esc_sql( $orders_table ) . '`'
				);

			} else {
				$count = wp_count_posts( 'shop_order' )->publish;
			}

		} else {

			// Legacy CPT
			$count = wp_count_posts( 'shop_order' );
			$count = isset( $count->publish ) ? $count->publish : 0;
		}

		wp_cache_set( 'wc_af_total_order_count', $count, '', 3600 );
	}

	return intval( $count ) > 25000;
}


/**
 * Generate dashboard stats (batched) and return array of values.
 * This function does not read from transient; it computes fresh values.
 *
 * @param string $date_range_preset The date range preset (e.g., 'last_30_days', 'all_orders').
 */
function wc_af_generate_dashboard_stats( $date_range_preset = 'last_30_days' ) {
	$stats = array(
		'number_of_orders' => 0,
		'total_transaction_amt' => 0.0,
		'number_of_low_risk_orders' => 0,
		'number_of_medium_risk_orders' => 0,
		'number_of_high_risk_orders' => 0,

		'total_orders_24h' => 0,
		'total_transaction_amt24' => 0.0,
		'high_risk_transaction_amt24' => 0.0,
		'low_risk_24' => 0,
		'medium_risk_24' => 0,
		'high_risk_24' => 0,
		'high_risk_hold_24' => 0,
		'high_risk_cancelled_24' => 0,
		'paypal_verification_24' => 0,
		'blocked_emails' => array(),

		'last7_days' => array(),
		'low_week_arr' => array(),
		'medium_week_arr' => array(),
		'high_week_arr' => array(),

		'recent_order_ids' => array(),
		'date_range_preset' => $date_range_preset,
	);

	$low_risk_label = __( 'Low Risk', 'woocommerce-anti-fraud' );
	$medium_risk_label = __( 'Medium Risk', 'woocommerce-anti-fraud' );
	$high_risk_label = __( 'High Risk', 'woocommerce-anti-fraud' );

	// blacklisted emails from settings
	$wc_settings_anti_fraudblacklist_emails = get_option( 'wc_settings_anti_fraudblacklist_emails' );
	$blacklisted_emails = $wc_settings_anti_fraudblacklist_emails ? array_map( 'trim', explode( ',', $wc_settings_anti_fraudblacklist_emails ) ) : array();

	// Parse date range
	$date_filter = wc_af_parse_date_range( $date_range_preset );

	// date ranges for 24h and 7 days (always based on current time)
	$one_day_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) );
	$seven_days_after = gmdate( 'Y-m-d H:i:s', strtotime( '-6 days' ) ); // include 7 days

	// prepare last7 days map
	$last7_days = array();
	for ( $i = 6; $i >= 0; $i-- ) {
		$day = gmdate( 'Y-m-d', strtotime( "-$i days" ) );
		$last7_days[] = $day;
	}
	$stats['last7_days'] = $last7_days;
	$stats['last7_days_map'] = array();
	foreach ( $last7_days as $d ) {
		$stats['last7_days_map'][ $d ] = array( 'low' => 0, 'medium' => 0, 'high' => 0 );
	}

	// Helper to iterate orders in batches
	$batch_size = WC_AF_BATCH_SIZE;
	$page = 1;

	// Suspends cache addition during heavy work if available
	if ( function_exists( 'wp_suspend_cache_addition' ) ) {
		wp_suspend_cache_addition( true );
	}
	// Increase the execution time limit if possible
	if ( function_exists( 'set_time_limit' ) ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_set
		@set_time_limit( 300 );
	}

	do {
		$query_args = array(
			'limit'  => $batch_size,
			'page'   => $page,
			'status' => array( 'wc-pending', 'wc-on-hold', 'wc-processing', 'wc-completed', 'wc-refunded', 'wc-cancelled', 'wc-failed' ),
			'return' => 'ids',
		);

		// Add date filtering if not "all_orders"
		if ( false !== $date_filter ) {
			$query_args['date_created'] = $date_filter['start'] . '...' . $date_filter['end'];
		}

		$ids = wc_get_orders( $query_args );
		if ( empty( $ids ) ) {
			break;
		}

		foreach ( $ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}

			// global cumulative counters
			$stats['number_of_orders']++;

			$order = af_safe_order( $order );
			$order_total = $order ? floatval( $order->get_total() ) : 0;

			$stats['total_transaction_amt'] += $order_total;

			$wc_af_score = intval( opmc_hpos_get_post_meta( $order_id, 'wc_af_score', true ) );
			$meta = WC_AF_Score_Helper::get_score_meta( $wc_af_score, $order_id );

			if ( $low_risk_label === $meta['label'] ) {
				$stats['number_of_low_risk_orders']++;
			} elseif ( $medium_risk_label === $meta['label'] ) {
				$stats['number_of_medium_risk_orders']++;
			} elseif ( $high_risk_label === $meta['label'] ) {
				$stats['number_of_high_risk_orders']++;
			} elseif ( 0 === $wc_af_score ) {
				$stats['number_of_high_risk_orders']++;
			}

			// 24 hours
			$created = $order->get_date_created();
			if ( $created && $created->date( 'Y-m-d H:i:s' ) >= $one_day_ago ) {
				$stats['total_orders_24h']++;
				$stats['total_transaction_amt24'] += $order_total;

				$email = $order ? $order->get_billing_email() : '';
				$wc_af_score_24 = $wc_af_score;
				$meta24 = WC_AF_Score_Helper::get_score_meta( $wc_af_score_24, $order_id );
				$status = $order ? 'wc-' . $order->get_status() : '';

				if ( $low_risk_label === $meta24['label'] ) {
					$stats['low_risk_24']++;
				} elseif ( $medium_risk_label === $meta24['label'] ) {
					$stats['medium_risk_24']++;
				} elseif ( $high_risk_label === $meta24['label'] ) {
					$stats['high_risk_24']++;
					$stats['high_risk_transaction_amt24'] += $order_total;
					if ( 'wc-on-hold' === $status ) {
						$stats['high_risk_hold_24']++;
					}
					if ( 'wc-cancelled' === $status ) {
						$stats['high_risk_cancelled_24']++;
					}
				} elseif ( 0 === $wc_af_score_24 && 'wc-cancelled' === $status ) {
					$stats['high_risk_transaction_amt24'] += $order_total;
					$stats['high_risk_cancelled_24']++;
				}

				if ( in_array( $email, $blacklisted_emails, true ) ) {
					$stats['blocked_emails'][ $email ] = isset( $stats['blocked_emails'][ $email ] ) ? $stats['blocked_emails'][ $email ] + 1 : 1;
				}

				$paypal_status = opmc_hpos_get_post_meta( $order_id, '_paypal_status', true );
				if ( 'pending' === $paypal_status ) {
					$stats['paypal_verification_24']++;
				}
			}

			// last 7 days mapping
			if ( $created ) {
				$order_day = $created->date( 'Y-m-d' );
				if ( isset( $stats['last7_days_map'][ $order_day ] ) ) {
					$wc_af_score_7d = $wc_af_score;
					$meta7 = WC_AF_Score_Helper::get_score_meta( $wc_af_score_7d, $order_id );
					if ( $low_risk_label === $meta7['label'] ) {
						$stats['last7_days_map'][ $order_day ]['low']++;
					} elseif ( $medium_risk_label === $meta7['label'] ) {
						$stats['last7_days_map'][ $order_day ]['medium']++;
					} elseif ( $high_risk_label === $meta7['label'] || 0 === $wc_af_score_7d ) {
						$stats['last7_days_map'][ $order_day ]['high']++;
					}
				}
			}
		}

		$page++;
	} while ( count( $ids ) === $batch_size );

	// restore cache addition
	if ( function_exists( 'wp_suspend_cache_addition' ) ) {
		wp_suspend_cache_addition( false );
	}

	// flatten chart arrays
	foreach ( $stats['last7_days'] as $d ) {
		$stats['low_week_arr'][] = $stats['last7_days_map'][ $d ]['low'];
		$stats['medium_week_arr'][] = $stats['last7_days_map'][ $d ]['medium'];
		$stats['high_week_arr'][] = $stats['last7_days_map'][ $d ]['high'];
	}
	unset( $stats['last7_days_map'] );

	// recent orders (only IDs)
	$recent_ids = wc_get_orders( array(
		'limit'   => 10,
		'orderby' => 'date',
		'order'   => 'DESC',
		'status'  => array( 'wc-pending', 'wc-on-hold', 'wc-processing', 'wc-completed', 'wc-refunded', 'wc-cancelled', 'wc-failed' ),
		'return'  => 'ids',
	) );
	$stats['recent_order_ids'] = $recent_ids ? $recent_ids : array();

	// finalize numeric types
	$stats['number_of_orders'] = intval( $stats['number_of_orders'] );
	$stats['total_transaction_amt'] = floatval( $stats['total_transaction_amt'] );
	$stats['number_of_low_risk_orders'] = intval( $stats['number_of_low_risk_orders'] );
	$stats['number_of_medium_risk_orders'] = intval( $stats['number_of_medium_risk_orders'] );
	$stats['number_of_high_risk_orders'] = intval( $stats['number_of_high_risk_orders'] );

	return $stats;
}

/**
 * Get dashboard stats, using transient when available.
 *
 * @param string $date_range_preset The date range preset.
 */
function wc_af_get_dashboard_stats( $date_range_preset = '' ) {
	// If no preset provided, get from user preference
	if ( empty( $date_range_preset ) ) {
		$date_range_preset = wc_af_get_date_range_preference( 'get' );
	}

	// Build transient key with date range
	$transient_key = WC_AF_DASH_TRANSIENT . '_' . md5( $date_range_preset );
	
	$cached = get_transient( $transient_key );
	if ( false !== $cached && isset( $cached['date_range_preset'] ) && $cached['date_range_preset'] === $date_range_preset ) {
		return $cached;
	}

	$stats = wc_af_generate_dashboard_stats( $date_range_preset );
	set_transient( $transient_key, $stats, WC_AF_DASH_TRANSIENT_TTL );
	return $stats;
}

/**
 * Rebuild dashboard cache immediately (used by cron or AJAX).
 * Increase limits locally for rebuild.
 *
 * @param string $date_range_preset The date range preset.
 */
function wc_af_rebuild_dashboard_cache( $date_range_preset = '' ) {
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_set
	if ( function_exists( 'ini_set' ) ) {
		@ini_set( 'memory_limit', '1024M' );
		@ini_set( 'max_execution_time', 300 );
	}

	// If no preset provided, get from user preference
	if ( empty( $date_range_preset ) ) {
		$date_range_preset = wc_af_get_date_range_preference( 'get' );
	}

	$new_stats = wc_af_generate_dashboard_stats( $date_range_preset );
	$transient_key = WC_AF_DASH_TRANSIENT . '_' . md5( $date_range_preset );
	set_transient( $transient_key, $new_stats, WC_AF_DASH_TRANSIENT_TTL );
}

/**
 * Cron callback to rebuild cache
 */
function wc_af_rebuild_dashboard_cache_delayed() {
	wc_af_rebuild_dashboard_cache();
}
add_action( 'wc_af_refresh_dashboard_cache_delayed', 'wc_af_rebuild_dashboard_cache_delayed' );

/**
 * AJAX endpoint to rebuild cache (nopriv so thank-you page guests can trigger it).
 * This is the instant path triggered via JS on the order received page.
 */
function wc_af_rebuild_dashboard_cache_ajax() {
	check_ajax_referer( 'wc_af_dashboard_nonce', 'security' );

	// Rebuild in request — increase limits locally
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_set
	if ( function_exists( 'ini_set' ) ) {
		@ini_set( 'memory_limit', '1024M' );
		@ini_set( 'max_execution_time', 300 );
	}

	wc_af_rebuild_dashboard_cache();

	wp_send_json_success( array( 'message' => 'Dashboard cache rebuilt' ) );
}
add_action( 'wp_ajax_wc_af_rebuild_dashboard_cache', 'wc_af_rebuild_dashboard_cache_ajax' );
add_action( 'wp_ajax_nopriv_wc_af_rebuild_dashboard_cache', 'wc_af_rebuild_dashboard_cache_ajax' );

/**
 * AJAX handler for changing date range.
 * NOTE: The actual AJAX handler is registered in woocommerce-anti-fraud.php
 * so it's available globally, not just when viewing the dashboard.
 * The handler function wc_af_change_date_range_ajax() is defined there.
 */

/**
 * Print inline JS on the order received (thank-you) page to trigger AJAX rebuild.
 * This runs in the customer's browser after checkout completes (non-blocking).
 */
function wc_af_maybe_print_thankyou_trigger() {
	if ( ! function_exists( 'is_order_received_page' ) ) {
		return;
	}

	if ( ! is_order_received_page() ) {
		return;
	}

	$ajax_url = admin_url( 'admin-ajax.php' );
	$nonce = wp_create_nonce( 'wc_af_dashboard_nonce' );
	?>
	<script type="text/javascript">
	(function($){
		// fire AJAX non-blocking; we don't care about response here
		try {
			$.post("<?php echo esc_js( $ajax_url ); ?>", {
				action: 'wc_af_rebuild_dashboard_cache',
				security: '<?php echo esc_js( $nonce ); ?>'
			}).always(function(){ /* no-op */ });
		} catch (e) {
			// gracefully ignore
			console && console.log && console.log('wc_af: ajax failed', e);
		}
	})(jQuery);
	</script>
	<?php
}
add_action( 'wp_footer', 'wc_af_maybe_print_thankyou_trigger' );

/*** Build data for template ***/
// Get current date range preference
$current_date_range = isset( $_GET['date_range'] ) ? sanitize_text_field( $_GET['date_range'] ) : wc_af_get_date_range_preference( 'get' );
$is_large_store = wc_af_is_large_store();

// Get stats with date range
$stats = wc_af_get_dashboard_stats( $current_date_range );

// assign to template variables for compatibility
$number_of_orders = $stats['number_of_orders'];
$total_transaction_amt = $stats['total_transaction_amt'];
$number_of_low_risk_orders = $stats['number_of_low_risk_orders'];
$number_of_medium_risk_orders = $stats['number_of_medium_risk_orders'];
$number_of_high_risk_orders = $stats['number_of_high_risk_orders'];

$currency = get_woocommerce_currency_symbol();

$total_orders = $stats['total_orders_24h'];
$total_transaction_amt24 = $stats['total_transaction_amt24'];
$high_risk_transaction_amt24 = $stats['high_risk_transaction_amt24'];
$number_of_low_risk_orders24 = $stats['low_risk_24'];
$number_of_medium_risk_orders24 = $stats['medium_risk_24'];
$number_of_high_risk_orders24 = $stats['high_risk_24'];
$number_of_high_risk_orders_hold24 = $stats['high_risk_hold_24'];
$number_of_high_risk_orders_cancelled24 = $stats['high_risk_cancelled_24'];
$number_of_paypal_verification_orders = $stats['paypal_verification_24'];
$block_emails = $stats['blocked_emails'];

$last7_days = $stats['last7_days'];
$low_week_arr = $stats['low_week_arr'];
$medium_week_arr = $stats['medium_week_arr'];
$high_week_arr = $stats['high_week_arr'];

$recent_orders = $stats['recent_order_ids'];

// Get date range label for display
$date_range_labels = array(
	'last_15_days' => __( 'Last 15 days', 'woocommerce-anti-fraud' ),
	'last_30_days' => __( 'Last 30 days', 'woocommerce-anti-fraud' ) . ' ' . __( '(recommended)', 'woocommerce-anti-fraud' ),
	'last_60_days' => __( 'Last 60 days', 'woocommerce-anti-fraud' ),
	'last_90_days' => __( 'Last 90 days', 'woocommerce-anti-fraud' ),
	'all_orders'   => __( 'All orders', 'woocommerce-anti-fraud' ) . ' ' . __( '(not recommended)', 'woocommerce-anti-fraud' ),
);

$current_label = isset( $date_range_labels[ $current_date_range ] ) ? $date_range_labels[ $current_date_range ] : __( 'Custom range', 'woocommerce-anti-fraud' );

// Check if custom range
$is_custom_range = strpos( $current_date_range, 'custom_' ) === 0;
if ( $is_custom_range ) {
	$dates = str_replace( 'custom_', '', $current_date_range );
	$parts = explode( '_', $dates );
	if ( count( $parts ) === 2 ) {
		/* translators: 1: Start date, 2: End date */
		$current_label = sprintf( __( 'Custom: %1$s to %2$s', 'woocommerce-anti-fraud' ), $parts[0], $parts[1] );
	}
}

?>
<div class="wc-af-dashboard-shell">
<div class="wc-af-dashboard-header-card">
	<div class="wc-af-dashboard-header-main">
		<p class="wc-af-dashboard-eyebrow"><?php echo esc_html__( 'WooCommerce Anti-Fraud', 'woocommerce-anti-fraud' ); ?></p>
		<h1 class="wc-af-dashboard-hero"><?php echo esc_html__( 'Dashboard', 'woocommerce-anti-fraud' ); ?></h1>
		<p class="wc-af-dashboard-subtitle"><?php echo esc_html__( 'Your anti-fraud control center for protection status, risk pressure, and next actions.', 'woocommerce-anti-fraud' ); ?></p>
	</div>
	<div class="wc-af-dashboard-header-actions">
		<a class="button button-primary wc-af-btn wc-af-btn--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=wc_af' ) ); ?>"><?php echo esc_html__( 'Anti-Fraud setup', 'woocommerce-anti-fraud' ); ?></a>
		<a class="button wc-af-btn wc-af-btn--secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=wc_af&section=card_attacks' ) ); ?>"><?php echo esc_html__( 'Card attacks', 'woocommerce-anti-fraud' ); ?></a>
		<a class="button wc-af-btn wc-af-btn--secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=wc_af&section=white_list' ) ); ?>"><?php echo esc_html__( 'Allow list', 'woocommerce-anti-fraud' ); ?></a>
		<a class="button wc-af-btn wc-af-btn--secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=wc_af&section=black_list' ) ); ?>"><?php echo esc_html__( 'Block list', 'woocommerce-anti-fraud' ); ?></a>
	</div>
</div>

<details class="wc-af-dashboard-details wc-af-dashboard-start-here">
	<summary class="wc-af-dashboard-details__summary"><?php echo esc_html__( 'Getting started & resources', 'woocommerce-anti-fraud' ); ?></summary>
	<div class="wc-af-dashboard-start-here__actions wc-af-dashboard-start-here__actions--wrap">
		<a class="button wc-af-btn wc-af-btn--secondary" href="https://youtu.be/moc-P4kAA4I" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Watch a walkthrough', 'woocommerce-anti-fraud' ); ?></a>
		<a class="button wc-af-btn wc-af-btn--secondary" href="https://docs.woocommerce.com/document/woocommerce-anti-fraud/" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Read the setup guide', 'woocommerce-anti-fraud' ); ?></a>
		<a class="button wc-af-btn wc-af-btn--secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=wc_af&section=general' ) ); ?>"><?php echo esc_html__( 'Core protection', 'woocommerce-anti-fraud' ); ?></a>
		<a class="button wc-af-btn wc-af-btn--secondary" href="<?php echo esc_url( defined( 'WC_AF_SUPPORT_TICKET_URL' ) ? WC_AF_SUPPORT_TICKET_URL : 'https://woocommerce.com/my-account/create-a-ticket/' ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Get support', 'woocommerce-anti-fraud' ); ?></a>
	</div>
</details>

<!-- Date Range Filter Controls -->
<div class="dash-row wc-af-date-filter-container wc-af-dashboard-card">
	<div class="wc-af-date-filter-wrapper">
		
		<?php if ( $is_large_store ) : ?>
		<div class="wc-af-notice wc-af-notice-info">
			<strong><?php echo esc_html__( 'Large catalog', 'woocommerce-anti-fraud' ); ?></strong>
			<?php echo esc_html__( 'Use a shorter date range first for faster results. Widen it below if needed.', 'woocommerce-anti-fraud' ); ?>
		</div>
		<?php endif; ?>

		<div class="wc-af-date-controls">
			<label for="wc-af-date-range-select" class="wc-af-date-controls-label">
				<?php echo esc_html__( 'Date range', 'woocommerce-anti-fraud' ); ?>
			</label>
			
			<select id="wc-af-date-range-select" class="wc-af-date-select">
				<option value="last_15_days" <?php selected( $current_date_range, 'last_15_days' ); ?>>
					<?php echo esc_html__( 'Last 15 days', 'woocommerce-anti-fraud' ); ?>
				</option>
				<option value="last_30_days" <?php selected( $current_date_range, 'last_30_days' ); ?>>
					<?php echo esc_html__( 'Last 30 days (recommended)', 'woocommerce-anti-fraud' ); ?>
				</option>
				<option value="last_60_days" <?php selected( $current_date_range, 'last_60_days' ); ?>>
					<?php echo esc_html__( 'Last 60 days', 'woocommerce-anti-fraud' ); ?>
				</option>
				<option value="last_90_days" <?php selected( $current_date_range, 'last_90_days' ); ?>>
					<?php echo esc_html__( 'Last 90 days', 'woocommerce-anti-fraud' ); ?>
				</option>
				<option value="custom_range" <?php selected( $is_custom_range, true ); ?>>
					<?php echo esc_html__( 'Custom range…', 'woocommerce-anti-fraud' ); ?>
				</option>
				<option value="all_orders" <?php selected( $current_date_range, 'all_orders' ); ?>>
					<?php echo esc_html__( 'All orders (not recommended)', 'woocommerce-anti-fraud' ); ?>
				</option>
			</select>

			<button type="button" id="wc-af-apply-range" class="button button-primary wc-af-btn wc-af-btn--primary">
				<?php echo esc_html__( 'Apply', 'woocommerce-anti-fraud' ); ?>
			</button>

			<button type="button" id="wc-af-set-default" class="button wc-af-btn wc-af-btn--secondary" title="<?php echo esc_attr__( 'Save current selection as default', 'woocommerce-anti-fraud' ); ?>">
				<?php echo esc_html__( 'Set as Default', 'woocommerce-anti-fraud' ); ?>
			</button>

			<button type="button" id="wc-af-reset-default" class="button wc-af-btn wc-af-btn--secondary" title="<?php echo esc_attr__( 'Reset to Last 30 days', 'woocommerce-anti-fraud' ); ?>">
				<?php echo esc_html__( 'Reset to Default', 'woocommerce-anti-fraud' ); ?>
			</button>
		</div>

		<!-- Custom Date Range Picker (hidden by default) -->
		<div id="wc-af-custom-range-picker" class="wc-af-custom-picker" style="display: none;">
			<label class="wc-af-custom-picker-label">
				<?php echo esc_html__( 'From:', 'woocommerce-anti-fraud' ); ?>
			</label>
			<input type="date" id="wc-af-date-from" class="wc-af-date-input" />
			
			<label class="wc-af-custom-picker-label">
				<?php echo esc_html__( 'To:', 'woocommerce-anti-fraud' ); ?>
			</label>
			<input type="date" id="wc-af-date-to" class="wc-af-date-input" />
			
			<button type="button" id="wc-af-apply-custom" class="button button-primary wc-af-btn wc-af-btn--primary">
				<?php echo esc_html__( 'Apply Custom Range', 'woocommerce-anti-fraud' ); ?>
			</button>
		</div>

		<div class="wc-af-current-range">
			<?php echo esc_html__( 'Showing:', 'woocommerce-anti-fraud' ); ?> <strong><?php echo esc_html( $current_label ); ?></strong>
		</div>
	</div>
</div>

<!-- All Orders Warning Modal -->
<div id="wc-af-all-orders-modal" class="wc-af-modal" style="display: none;">
	<div class="wc-af-modal-content">
		<div class="wc-af-modal-header">
			<h2 class="wc-af-modal-title"><?php echo esc_html__( 'Slow load warning', 'woocommerce-anti-fraud' ); ?></h2>
		</div>
		<div class="wc-af-modal-body">
			<p>
				<?php echo esc_html__( 'Loading all orders can time out on very large stores (often above ~100,000 orders).', 'woocommerce-anti-fraud' ); ?>
			</p>
			<p>
				<strong><?php echo esc_html__( 'Prefer a date range unless you need full history.', 'woocommerce-anti-fraud' ); ?></strong>
			</p>
			<label class="wc-af-modal-checkbox">
				<input type="checkbox" id="wc-af-confirm-all-orders" />
				<?php echo esc_html__( 'I understand — load all orders', 'woocommerce-anti-fraud' ); ?>
			</label>
		</div>
		<div class="wc-af-modal-footer">
			<button type="button" id="wc-af-cancel-all-orders" class="button button-secondary wc-af-btn wc-af-btn--secondary">
				<?php echo esc_html__( 'Cancel', 'woocommerce-anti-fraud' ); ?>
			</button>
			<button type="button" id="wc-af-proceed-all-orders" class="button button-primary wc-af-btn wc-af-btn--primary" disabled>
				<?php echo esc_html__( 'Load all orders', 'woocommerce-anti-fraud' ); ?>
			</button>
		</div>
	</div>
</div>

<!-- Loading Overlay -->
<div id="wc-af-loading-overlay" style="display: none;">
	<div class="wc-af-spinner"></div>
	<p class="wc-af-loading-text"><?php echo esc_html__( 'Loading metrics…', 'woocommerce-anti-fraud' ); ?></p>
</div>

<div class="dash-row wc-af-metric-row">

	<div class="metric-box metric-style1">
		<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'icons/cart.svg'; ?>">
		<p class="wc-af-metric-label"><?php echo esc_html__( 'Orders in range', 'woocommerce-anti-fraud' ); ?></p>
		<h2><?php echo esc_html( $number_of_orders ); ?></h2>
	</div>

	<div class="metric-box metric-style2">
		<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'icons/low-risk.svg'; ?>">
		<p class="wc-af-metric-label"><?php echo esc_html__( 'Low risk', 'woocommerce-anti-fraud' ); ?></p>
		<h2><?php echo esc_html( $number_of_low_risk_orders ); ?></h2>
	</div>

	<div class="metric-box metric-style3">
		<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'icons/med-risk.svg'; ?>">
		<p class="wc-af-metric-label"><?php echo esc_html__( 'Medium risk', 'woocommerce-anti-fraud' ); ?></p>
		<h2><?php echo esc_html( $number_of_medium_risk_orders ); ?></h2>
	</div>

	<div class="metric-box metric-style4">
		<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'icons/high-risk.svg'; ?>">
		<p class="wc-af-metric-label"><?php echo esc_html__( 'High risk', 'woocommerce-anti-fraud' ); ?></p>
		<h2><?php echo esc_html( $number_of_high_risk_orders ); ?></h2>
	</div>

</div>

<div class="dash-row">

	<div class="dash-section-50 bar-chart">
		<h2 class="wc-af-section-title"><?php echo esc_html__( 'Risk by level', 'woocommerce-anti-fraud' ); ?></h2>
		<div class="chart-wrapper">
			<canvas id="bar-chart-grouped"></canvas>
		</div>
	</div>

	<div class="dash-section-50 dash-stats">
		<h2 class="wc-af-section-title"><?php echo esc_html__( 'Last 24 hours', 'woocommerce-anti-fraud' ); ?></h2>

		<div class="blurb">
			<div class="blurb-inner">
				<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'icons/totaol.svg' ); ?>">
				<h3><?php echo esc_html__( 'Revenue (24h)', 'woocommerce-anti-fraud' ); ?></h3>
				<div class="blurb-content"><span><?php echo esc_html( $currency . number_format_i18n( $total_transaction_amt24, 2 ) ); ?></span></div>
			</div>
		</div>

		<div class="blurb">
			<div class="blurb-inner">
				<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'icons/hash.svg' ); ?>">
				<h3><?php echo esc_html__( 'Orders (24h)', 'woocommerce-anti-fraud' ); ?></h3>
				<div class="blurb-content"><?php echo esc_html( $total_orders ); ?></div>
			</div>
		</div>

		<div class="blurb">
			<div class="blurb-inner">
				<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'icons/med-risk.svg' ); ?>">
				<h3><?php echo esc_html__( 'Medium risk (24h)', 'woocommerce-anti-fraud' ); ?></h3>
				<div class="blurb-content"><?php echo esc_html( $number_of_medium_risk_orders24 ); ?></div>
			</div>
		</div>

		<div class="blurb">
			<div class="blurb-inner">
				<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'icons/high-risk.svg' ); ?>">
				<h3><?php echo esc_html__( 'High risk on hold (24h)', 'woocommerce-anti-fraud' ); ?></h3>
				<div class="blurb-content"><?php echo esc_html( $number_of_high_risk_orders_hold24 ); ?></div>
			</div>
		</div>

		<div class="blurb">
			<div class="blurb-inner">
				<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'icons/orders-cancelled.svg' ); ?>">
				<h3><?php echo esc_html__( 'High risk cancelled (24h)', 'woocommerce-anti-fraud' ); ?></h3>
				<div class="blurb-content"><?php echo esc_html( $number_of_high_risk_orders_cancelled24 ); ?></div>
			</div>
		</div>

		<div class="blurb">
			<div class="blurb-inner">
				<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'icons/money-risk.svg' ); ?>">
				<h3><?php echo esc_html__( 'High-risk revenue (24h)', 'woocommerce-anti-fraud' ); ?></h3>
				<div class="blurb-content"><?php echo esc_html( $currency . number_format_i18n( $high_risk_transaction_amt24, 2 ) ); ?></div>
			</div>
		</div>

		<div class="blurb">
			<div class="blurb-inner">
				<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'icons/emails-blocked.svg' ); ?>">
				<h3><?php echo esc_html__( 'Blocked emails', 'woocommerce-anti-fraud' ); ?></h3>
				<div class="blurb-content"><?php echo esc_html( count( $block_emails ) ); ?></div>
			</div>
		</div>

		<div class="blurb">
			<div class="blurb-inner">
				<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'icons/paypal.svg' ); ?>">
				<h3><?php echo esc_html__( 'PayPal verification pending', 'woocommerce-anti-fraud' ); ?></h3>
				<div class="blurb-content"><?php echo esc_html( $number_of_paypal_verification_orders ); ?></div>
			</div>
		</div>

	</div>

</div>

<div class="dash-row second">

	<div class="dash-section-50 recent-orders">
		<h2 class="wc-af-section-title"><?php echo esc_html__( 'Recent orders', 'woocommerce-anti-fraud' ); ?></h2>
		<div class="wc-af-status-key wc-af-status-key--dashboard" aria-labelledby="wc-af-dashboard-status-key-title">
			<h3 id="wc-af-dashboard-status-key-title" class="wc-af-status-key__title"><?php echo esc_html__( 'Status key', 'woocommerce-anti-fraud' ); ?></h3>
			<div class="wc-af-status-key__groups">
				<div class="wc-af-status-key__group">
					<p class="wc-af-status-key__group-title"><?php echo esc_html__( 'Risk dots', 'woocommerce-anti-fraud' ); ?></p>
					<ul class="wc-af-status-key__list">
						<li class="wc-af-status-key__item">
							<span class="wc-af-status-key__visual" aria-hidden="true"><span class="table-icon low-risk-icon"></span></span>
							<span class="wc-af-status-key__copy">
								<span class="wc-af-status-key__term"><?php echo esc_html__( 'Low risk', 'woocommerce-anti-fraud' ); ?></span>
								<span class="wc-af-status-key__desc"><?php echo esc_html__( 'Green dot: lower-risk order based on the current Anti-Fraud score.', 'woocommerce-anti-fraud' ); ?></span>
							</span>
						</li>
						<li class="wc-af-status-key__item">
							<span class="wc-af-status-key__visual" aria-hidden="true"><span class="table-icon med-risk-icon"></span></span>
							<span class="wc-af-status-key__copy">
								<span class="wc-af-status-key__term"><?php echo esc_html__( 'Medium risk', 'woocommerce-anti-fraud' ); ?></span>
								<span class="wc-af-status-key__desc"><?php echo esc_html__( 'Amber dot: medium-risk order that may need a closer look.', 'woocommerce-anti-fraud' ); ?></span>
							</span>
						</li>
						<li class="wc-af-status-key__item">
							<span class="wc-af-status-key__visual" aria-hidden="true"><span class="table-icon high-risk-icon"></span></span>
							<span class="wc-af-status-key__copy">
								<span class="wc-af-status-key__term"><?php echo esc_html__( 'High risk', 'woocommerce-anti-fraud' ); ?></span>
								<span class="wc-af-status-key__desc"><?php echo esc_html__( 'Red dot: higher-risk order that is more likely to need review.', 'woocommerce-anti-fraud' ); ?></span>
							</span>
						</li>
					</ul>
				</div>
				<div class="wc-af-status-key__group">
					<p class="wc-af-status-key__group-title"><?php echo esc_html__( 'Order badges', 'woocommerce-anti-fraud' ); ?></p>
					<ul class="wc-af-status-key__list">
						<li class="wc-af-status-key__item">
							<span class="wc-af-status-key__visual" aria-hidden="true"><span class="wc-af-order-status-badge is-pending"><?php echo esc_html__( 'Pending payment', 'woocommerce-anti-fraud' ); ?></span></span>
							<span class="wc-af-status-key__copy">
								<span class="wc-af-status-key__term"><?php echo esc_html__( 'Pending payment', 'woocommerce-anti-fraud' ); ?></span>
								<span class="wc-af-status-key__desc"><?php echo esc_html__( 'Order created but payment not completed.', 'woocommerce-anti-fraud' ); ?></span>
							</span>
						</li>
						<li class="wc-af-status-key__item">
							<span class="wc-af-status-key__visual" aria-hidden="true"><span class="wc-af-order-status-badge is-processing"><?php echo esc_html__( 'Processing', 'woocommerce-anti-fraud' ); ?></span></span>
							<span class="wc-af-status-key__copy">
								<span class="wc-af-status-key__term"><?php echo esc_html__( 'Processing', 'woocommerce-anti-fraud' ); ?></span>
								<span class="wc-af-status-key__desc"><?php echo esc_html__( 'Paid order awaiting fulfilment.', 'woocommerce-anti-fraud' ); ?></span>
							</span>
						</li>
						<li class="wc-af-status-key__item">
							<span class="wc-af-status-key__visual" aria-hidden="true"><span class="wc-af-order-status-badge is-on-hold"><?php echo esc_html__( 'On hold', 'woocommerce-anti-fraud' ); ?></span></span>
							<span class="wc-af-status-key__copy">
								<span class="wc-af-status-key__term"><?php echo esc_html__( 'On hold', 'woocommerce-anti-fraud' ); ?></span>
								<span class="wc-af-status-key__desc"><?php echo esc_html__( 'Needs review or manual action.', 'woocommerce-anti-fraud' ); ?></span>
							</span>
						</li>
						<li class="wc-af-status-key__item">
							<span class="wc-af-status-key__visual" aria-hidden="true"><span class="wc-af-order-status-badge is-completed"><?php echo esc_html__( 'Completed', 'woocommerce-anti-fraud' ); ?></span></span>
							<span class="wc-af-status-key__copy">
								<span class="wc-af-status-key__term"><?php echo esc_html__( 'Completed', 'woocommerce-anti-fraud' ); ?></span>
								<span class="wc-af-status-key__desc"><?php echo esc_html__( 'Fulfilled or complete order.', 'woocommerce-anti-fraud' ); ?></span>
							</span>
						</li>
						<li class="wc-af-status-key__item">
							<span class="wc-af-status-key__visual" aria-hidden="true">
								<span class="wc-af-status-key__badge-stack">
									<span class="wc-af-order-status-badge is-cancelled"><?php echo esc_html__( 'Cancelled', 'woocommerce-anti-fraud' ); ?></span>
									<span class="wc-af-order-status-badge is-failed"><?php echo esc_html__( 'Failed', 'woocommerce-anti-fraud' ); ?></span>
								</span>
							</span>
							<span class="wc-af-status-key__copy">
								<span class="wc-af-status-key__term"><?php echo esc_html__( 'Cancelled or failed', 'woocommerce-anti-fraud' ); ?></span>
								<span class="wc-af-status-key__desc"><?php echo esc_html__( 'Order did not complete.', 'woocommerce-anti-fraud' ); ?></span>
							</span>
						</li>
						<li class="wc-af-status-key__item">
							<span class="wc-af-status-key__visual" aria-hidden="true"><span class="wc-af-order-status-badge is-refunded"><?php echo esc_html__( 'Refunded', 'woocommerce-anti-fraud' ); ?></span></span>
							<span class="wc-af-status-key__copy">
								<span class="wc-af-status-key__term"><?php echo esc_html__( 'Refunded', 'woocommerce-anti-fraud' ); ?></span>
								<span class="wc-af-status-key__desc"><?php echo esc_html__( 'Payment was returned after the order was created.', 'woocommerce-anti-fraud' ); ?></span>
							</span>
						</li>
					</ul>
				</div>
			</div>
		</div>

		<table>
			<thead>
			<tr>
				<th></th>
				<th><?php echo esc_html__( 'Name', 'woocommerce-anti-fraud' ); ?></th>
				<th><?php echo esc_html__( 'Spent', 'woocommerce-anti-fraud' ); ?></th>
				<th><?php echo esc_html__( 'Status', 'woocommerce-anti-fraud' ); ?></th>
			</tr>
			</thead>

			<tbody>
			<?php
			if ( ! empty( $recent_orders ) ) {
				foreach ( $recent_orders as $recent_order_id ) {
					$recent_order_obj = wc_get_order( $recent_order_id );
					if ( ! $recent_order_obj ) {
						continue;
					}
					$recent_order = af_safe_order( $recent_order_obj );

					$billing_first_name =  $recent_order ? $recent_order->get_billing_first_name() : '';
					$billing_last_name  = $recent_order ? $recent_order->get_billing_last_name() : '';
					$order_total        = $recent_order ? floatval( $recent_order->get_total() ) : 0;
					$order_currency     = $recent_order ? $recent_order->get_currency() : '';
					$order_status       = $recent_order ? 'wc-' . $recent_order->get_status() : '';

					$wc_af_score      = intval( opmc_hpos_get_post_meta( $recent_order_id, 'wc_af_score', true ) );
					$meta             = WC_AF_Score_Helper::get_score_meta( $wc_af_score, $recent_order_id );
					$risk_score_class = '';
					if ( __( 'Low Risk', 'woocommerce-anti-fraud' ) == $meta['label'] ) {
						$risk_score_class = 'low-risk-icon';
					}
					if ( __( 'Medium Risk', 'woocommerce-anti-fraud' ) == $meta['label'] ) {
						$risk_score_class = 'med-risk-icon';
					}
					if ( __( 'High Risk', 'woocommerce-anti-fraud' ) == $meta['label'] ) {
						$risk_score_class = 'high-risk-icon';
					}
					if ( 0 == $wc_af_score ) {
						$risk_score_class = 'high-risk-icon';
					}

					switch ( $order_status ) {
						case 'wc-pending':
							$recent_order_status = 'Pending payment';
							$recent_order_status_class = 'is-pending';
							break;
						case 'wc-processing':
							$recent_order_status = 'Processing';
							$recent_order_status_class = 'is-processing';
							break;
						case 'wc-on-hold':
							$recent_order_status = 'On hold';
							$recent_order_status_class = 'is-on-hold';
							break;
						case 'wc-completed':
							$recent_order_status = 'Completed';
							$recent_order_status_class = 'is-completed';
							break;
						case 'wc-cancelled':
							$recent_order_status = 'Cancelled';
							$recent_order_status_class = 'is-cancelled';
							break;
						case 'wc-refunded':
							$recent_order_status = 'Refunded';
							$recent_order_status_class = 'is-refunded';
							break;
						case 'wc-failed':
							$recent_order_status = 'Failed';
							$recent_order_status_class = 'is-failed';
							break;
						default:
							$recent_order_status = '';
							$recent_order_status_class = 'is-default';
					}
					?>
					<tr>
						<td>
							<div class="table-icon <?php echo esc_attr( $risk_score_class ); ?>"></div>
						</td>

						<td><?php echo esc_html( $billing_first_name . ' ' . $billing_last_name ); ?></td>

						<td><?php echo esc_html( get_woocommerce_currency_symbol( $order_currency ) . number_format_i18n( $order_total, 2 ) ); ?></td>

						<td><span class="wc-af-order-status-badge <?php echo esc_attr( $recent_order_status_class ); ?>"><?php echo esc_html( $recent_order_status ); ?></span></td>

					</tr>
					<?php
				}
			}
			?>
			</tbody>
		</table>

	</div>

	<div class="dash-section-50 pie-chart">
		<h2 class="wc-af-section-title"><?php echo esc_html__( 'At-risk customers', 'woocommerce-anti-fraud' ); ?></h2>
		<div class="chart-wrapper">
			<canvas id="barChart"></canvas>
		</div>
	</div>

</div>
</div>

<script>
	var canvas = document.getElementById("barChart");
	if (canvas) {
		var ctx = canvas.getContext('2d');
		if (Chart && Chart.defaults) {
			Chart.defaults.global = Chart.defaults.global || {};
			Chart.defaults.global.defaultFontColor = 'white';
			Chart.defaults.global.defaultFontSize = 16;
		}

		var data = {
			labels: ["<?php echo esc_html__( 'Low risk', 'woocommerce-anti-fraud' ); ?>", "<?php echo esc_html__( 'Medium risk', 'woocommerce-anti-fraud' ); ?>", "<?php echo esc_html__( 'High risk', 'woocommerce-anti-fraud' ); ?>"],
			datasets: [
				{
					fill: true,
					fontColor: 'green',
					backgroundColor: [
						'#5CE593',
						'#E0B826',
						'#E25D71'
					],
					data: [<?php echo esc_attr( $number_of_low_risk_orders ); ?>, <?php echo esc_attr( $number_of_medium_risk_orders ); ?>, <?php echo esc_attr( $number_of_high_risk_orders ); ?>]
				}
			]
		};

		var options = {
			responsive: true,
			maintainAspectRatio: false,
			title: {
				display: true,
			},
			rotation: -0.7 * Math.PI
		};

		var myBarChart = new Chart(ctx, {
			type: 'pie',
			data: data,
			options: options,
			responsive: true,
			maintainAspectRatio: false
		});
	}
</script>

<script type="text/javascript">
	if (Chart && Chart.defaults) {
		Chart.defaults.global = Chart.defaults.global || {};
		Chart.defaults.global.defaultFontColor = 'white';
		Chart.defaults.global.defaultFontSize = 16;
	}

	let afChartConfig = {
		type: 'bar',
		data: {
			labels: [
				<?php
				foreach ( $last7_days as $day ) {
					echo "'" . esc_js( $day ) . "',";
				}
				?>
			],
			datasets: [
				{
					label: "<?php echo esc_html__( 'Low risk', 'woocommerce-anti-fraud' ); ?>",
					backgroundColor: "#5CE593",
					data: [
						<?php
						foreach ( $low_week_arr as $score ) {
							echo "'" . esc_js( $score ) . "',";
						}
						?>
					]
				},
				{
					label: "<?php echo esc_html__( 'Medium risk', 'woocommerce-anti-fraud' ); ?>",
					backgroundColor: "#E0B826",
					data: [
						<?php
						foreach ( $medium_week_arr as $score ) {
							echo "'" . esc_js( $score ) . "',";
						}
						?>
					]
				},
				{
					label: "<?php echo esc_html__( 'High risk', 'woocommerce-anti-fraud' ); ?>",
					backgroundColor: "#E25D71",
					data: [
						<?php
						foreach ( $high_week_arr as $score ) {
							echo "'" . esc_js( $score ) . "',";
						}
						?>
					]
				}
			]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false
		}
	},
	afChart = new Chart(document.getElementById("bar-chart-grouped"), afChartConfig);

</script>

<script type="text/javascript">
// Ensure ajaxurl is defined (WordPress admin global)
if (typeof ajaxurl === 'undefined') {
	var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
}

(function($) {
	'use strict';

	// Check if jQuery is available
	if (typeof $ === 'undefined') {
		console.error('jQuery is not loaded. Date range filter requires jQuery.');
		return;
	}

	// Dashboard date range handler
	var wcAfDashboard = {
		init: function() {
			this.bindEvents();
			this.checkCustomRange();
		},

		bindEvents: function() {
			var self = this;

			// Date range select change
			$('#wc-af-date-range-select').on('change', function() {
				self.handleRangeChange();
			});

			// Apply button
			$('#wc-af-apply-range').on('click', function() {
				self.applyDateRange(false);
			});

			// Set as default button
			$('#wc-af-set-default').on('click', function() {
				self.applyDateRange(true);
			});

			// Reset to default button
			$('#wc-af-reset-default').on('click', function() {
				self.resetToDefault();
			});

			// Apply custom range
			$('#wc-af-apply-custom').on('click', function() {
				self.applyCustomRange(false);
			});

			// All orders modal handlers
			$('#wc-af-confirm-all-orders').on('change', function() {
				$('#wc-af-proceed-all-orders').prop('disabled', !this.checked);
			});

			$('#wc-af-cancel-all-orders').on('click', function() {
				self.closeModal();
				$('#wc-af-date-range-select').val('last_30_days');
			});

			$('#wc-af-proceed-all-orders').on('click', function() {
				self.closeModal();
				self.proceedWithAllOrders();
			});
		},

		handleRangeChange: function() {
			var selected = $('#wc-af-date-range-select').val();
			
			if (selected === 'custom_range') {
				$('#wc-af-custom-range-picker').slideDown(200);
			} else {
				$('#wc-af-custom-range-picker').slideUp(200);
			}
		},

		checkCustomRange: function() {
			var selected = $('#wc-af-date-range-select').val();
			if (selected === 'custom_range') {
				$('#wc-af-custom-range-picker').show();
			}
		},

		applyDateRange: function(saveAsDefault) {
			var selected = $('#wc-af-date-range-select').val();

			// If "all_orders" selected, show warning
			if (selected === 'all_orders') {
				this.showAllOrdersWarning(saveAsDefault);
				return;
			}

			// If custom range, handle separately
			if (selected === 'custom_range') {
				this.applyCustomRange(saveAsDefault);
				return;
			}

			// Apply preset range
			this.submitDateChange(selected, saveAsDefault);
		},

		applyCustomRange: function(saveAsDefault) {
			var dateFrom = $('#wc-af-date-from').val();
			var dateTo = $('#wc-af-date-to').val();

			if (!dateFrom || !dateTo) {
				alert('<?php echo esc_js( __( 'Please select both start and end dates.', 'woocommerce-anti-fraud' ) ); ?>');
				return;
			}

			if (dateFrom > dateTo) {
				alert('<?php echo esc_js( __( 'Start date must be before end date.', 'woocommerce-anti-fraud' ) ); ?>');
				return;
			}

			var customRange = 'custom_' + dateFrom + '_' + dateTo;
			this.submitDateChange(customRange, saveAsDefault);
		},

		showAllOrdersWarning: function(saveAsDefault) {
			var self = this;
			$('#wc-af-all-orders-modal').fadeIn(200);
			$('#wc-af-confirm-all-orders').prop('checked', false);
			$('#wc-af-proceed-all-orders').prop('disabled', true);

			// Store saveAsDefault flag
			$('#wc-af-proceed-all-orders').data('save-as-default', saveAsDefault);
		},

		closeModal: function() {
			$('#wc-af-all-orders-modal').fadeOut(200);
		},

		proceedWithAllOrders: function() {
			var saveAsDefault = $('#wc-af-proceed-all-orders').data('save-as-default');
			this.submitDateChange('all_orders', saveAsDefault);
		},

		resetToDefault: function() {
			this.submitDateChange('last_30_days', true);
		},

		submitDateChange: function(dateRange, saveAsDefault) {
			var self = this;

			// Validate inputs
			if (!dateRange) {
				alert('<?php echo esc_js( __( 'Please select a date range.', 'woocommerce-anti-fraud' ) ); ?>');
				return;
			}

			console.log('Submitting date range change:', {
				dateRange: dateRange,
				saveAsDefault: saveAsDefault,
				ajaxurl: ajaxurl
			});

			// Show loading
			$('#wc-af-loading-overlay').fadeIn(200);

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wc_af_change_date_range',
					security: '<?php echo esc_js( wp_create_nonce( 'wc_af_dashboard_nonce' ) ); ?>',
					date_range: dateRange,
					save_as_default: saveAsDefault ? 'true' : 'false'
				},
				timeout: 30000, // 30 second timeout
				success: function(response) {
					console.log('AJAX Success Response:', response);
					if (response && response.success) {
						// Reload page with new date range
						// Simple approach: just reload with date_range parameter
						var currentUrl = window.location.href;
						var separator = currentUrl.indexOf('?') > -1 ? '&' : '?';
						// Remove existing date_range if present
						currentUrl = currentUrl.replace(/[?&]date_range=[^&]*/, '');
						// Add new date_range
						var newUrl = currentUrl + separator + 'date_range=' + encodeURIComponent(dateRange);
						console.log('Redirecting to:', newUrl);
						window.location.href = newUrl;
					} else {
						var errorMsg = (response && response.data && response.data.message) ? response.data.message : '<?php echo esc_js( __( 'Error updating date range. Please try again.', 'woocommerce-anti-fraud' ) ); ?>';
						alert(errorMsg);
						console.error('Date range update error:', response);
						$('#wc-af-loading-overlay').fadeOut(200);
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX Error Details:', {
						status: status,
						error: error,
						responseText: xhr.responseText,
						statusCode: xhr.status,
						readyState: xhr.readyState
					});
					
					var errorMsg = '<?php echo esc_js( __( 'Error updating date range. Please try again.', 'woocommerce-anti-fraud' ) ); ?>';
					if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						errorMsg = xhr.responseJSON.data.message;
					} else if (xhr.responseText) {
						// Try to parse error from response
						try {
							var errorResponse = JSON.parse(xhr.responseText);
							if (errorResponse.data && errorResponse.data.message) {
								errorMsg = errorResponse.data.message;
							}
						} catch(e) {
							console.error('Could not parse error response:', e);
						}
					}
					
					if (status === 'timeout') {
						errorMsg = '<?php echo esc_js( __( 'Request timed out. The date range may still have been saved. Please refresh the page.', 'woocommerce-anti-fraud' ) ); ?>';
					}
					
					alert(errorMsg);
					$('#wc-af-loading-overlay').fadeOut(200);
				}
			});
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		wcAfDashboard.init();
	});

})(jQuery);
</script>

