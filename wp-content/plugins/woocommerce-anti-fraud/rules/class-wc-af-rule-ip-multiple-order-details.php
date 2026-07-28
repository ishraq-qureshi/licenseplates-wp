<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_AF_Rule_Ip_Multiple_Order_Details extends WC_AF_Rule {

	private $start_datetime_string = '';
	private $end_datetime_string   = '';
	private $is_enabled            = false;
	private $rule_weight           = 0;
	private $time_span             = 0; // ✅ Added property to fix dynamic property deprecation

	/**
	 * The constructor
	 */
	public function __construct() {

		$this->is_enabled  = get_option( 'wc_af_ip_multiple_check' );
		$this->rule_weight = (int) get_option( 'wc_settings_anti_fraud_ip_multiple_weight', 0 ); // ✅ Cast to int
		$this->time_span   = (int) get_option( 'wc_settings_anti_fraud_ip_multiple_time_span', 0 ); // ✅ Cast to int with default
	
		parent::__construct(
			'ip_multiple_order_Details',
			__( 'IP address ordered with multiple order details in the past ' . $this->time_span . ' days', 'woocommerce-anti-fraud' ),
			$this->rule_weight
		);
	}

	/**
	 * Add the date range condition to the SQL
	 *
	 * @param string $where
	 * @return string
	 */
	public function date_range( $where = '' ) {
		$where .= " AND ( `post_date` >= '" . $this->start_datetime_string . "' AND `post_date` <= '" . $this->end_datetime_string . "' )";
		return $where;
	}

	/**
	 * Perform the IP multiple order details risk check.
	 *
	 * @param WC_Order $order
	 * @return bool
	 */
	public function is_risk( WC_Order $order ) {
		Af_Logger::debug( 'Checking multiple order rule' );
		global $wpdb;

		$risk = false;

		$pre_wc_30  = version_compare( WC_VERSION, '3.0', '<' );
		$order_id   = $pre_wc_30 ? $order->id : $order->get_id();
		$order_date = $pre_wc_30 ? $order->order_date : ( $order->get_date_created() ? gmdate( 'Y-m-d H:i:s', $order->get_date_created()->getOffsetTimestamp() ) : '' );
		$order_ip   = $pre_wc_30 ? opmc_hpos_get_post_meta( $order_id, '_customer_ip_address', true ) : $order->get_customer_ip_address();

		if ( empty( $order_ip ) ) {
			return false;
		}

		// Skip rule for IPs belonging to legitimate shared-IP privacy services
		// (e.g. iCloud Private Relay, Cloudflare CDN).  These services assign
		// the same egress IP to thousands of unrelated users, so "same IP,
		// different address" is expected and carries no fraud signal.
		if ( class_exists( 'WC_AF_Proxy_Helper' ) && WC_AF_Proxy_Helper::is_legitimate_proxy( $order_ip ) ) {
			$summary = WC_AF_Proxy_Helper::get_ip_summary( $order_ip );
			Af_Logger::debug( sprintf(
				'IP Multiple Order Details: skipping rule – IP %s belongs to trusted privacy service (ASN: %s, Provider: %s)',
				$order_ip,
				$summary['asn'],
				$summary['provider']
			) );
			return false;
		}

		$is_subscriptions_order = false;
		if ( class_exists( 'WC_Subscriptions_Plugin' ) ) {
			$is_subscriptions_order = wcs_order_contains_subscription( $order, 'renewal' );
		}

		Af_Logger::debug( 'is_subscriptions_renewal_order : ' . print_r( $is_subscriptions_order, true ) );

		if ( $is_subscriptions_order ) {
			return false;
		}

		// ✅ Validate time_span before using it
		$days = (int) $this->time_span;
		if ( $days <= 0 ) {
			Af_Logger::debug( 'Invalid time_span configuration: ' . $this->time_span . ', skipping rule' );
			return false;
		}

		// ✅ Wrap DateTime operations in try-catch for safety
		try {
			// Calculate the new datetime
			$dt = new DateTime( $order_date );
			// Use proper string interpolation with double quotes
			$dt->modify( "-{$days} days" );

			// Set the start and end datetime strings
			$this->start_datetime_string = $dt->format( 'Y-m-d H:i:s' );
			$this->end_datetime_string   = $order_date;
		} catch ( Exception $e ) {
			// Log the error and return false to prevent blocking checkout
			Af_Logger::debug( 'DateTime error in IP multiple order details check: ' . $e->getMessage() );
			return false;
		}

		// Add date range filter for WC < 3.0
		if ( $pre_wc_30 ) {
			add_filter( 'posts_where', array( $this, 'date_range' ) );
		}

		/*
		 * PERFORMANCE FIX: Replaced unlimited wc_get_orders (limit=-1) with a hard cap
		 * and per-IP transient risk cache to prevent DB saturation during bot attacks.
		 * The cap is well above any practical detection need; the risk cache short-circuits
		 * repeated checks for the same attacking IP within a 5-minute window.
		 */

		// Short-circuit: if this IP was already flagged as risky, skip the DB query.
		$ip_risk_cache_key = 'wc_af_risky_ipmod_' . substr( md5( $order_ip ), 0, 16 );

		if ( get_transient( $ip_risk_cache_key ) ) {
			Af_Logger::debug( 'IP Multiple Order: cached risky flag hit for IP ' . $order_ip );
			if ( $pre_wc_30 ) {
				remove_filter( 'posts_where', array( $this, 'date_range' ) );
			}
			return true;
		}

		/**
		 * Filters the maximum number of orders fetched by the IP multiple-order-details rule.
		 *
		 * During bot-attack mode the cap is automatically halved to limit DB work.
		 *
		 * @since 7.2.6
		 *
		 * @param int $limit Default: 100.
		 */
		$under_attack = class_exists( 'WooCommerce_Anti_Fraud' ) && WooCommerce_Anti_Fraud::is_under_bot_attack();
		$default_cap  = $under_attack ? 20 : 100;
		/**
		 * Filters the maximum number of orders fetched per velocity dimension.
		 *
		 * Increase this value if your max_orders threshold is higher than 100.
		 * Lower it to reduce DB load under attack. Default: 200.
		 * During bot-attack mode this is automatically reduced to max_orders+1 so
		 * each query stops at the threshold rather than scanning the full window.
		 *
		 * @since 7.2.6
		 *
		 * @param int $limit Maximum order count per velocity query.
		 */
		$query_cap    = (int) apply_filters( 'wc_af_ip_multiple_query_limit', $default_cap );
		$query_cap    = max( 10, $query_cap );

		// Build all WC order statuses except checkout-draft for query-level filtering.
		$non_draft_statuses = array_filter(
			array_keys( wc_get_order_statuses() ),
			function( $s ) {
 return 'wc-checkout-draft' !== $s; }
		);
		$non_draft_statuses = array_values( array_map(
			function( $s ) {
 return str_replace( 'wc-', '', $s ); },
			$non_draft_statuses
		) );

		// Get orders with same IP (capped to avoid full-table scan under attack).
		$si_orders = wc_get_orders(
			array(
				'limit'               => $query_cap,
				'exclude'             => array( $order_id ),
				'customer_ip_address' => $order_ip,
				'type'                => wc_get_order_types( 'order-count' ),
				'status'              => $non_draft_statuses,
				'date_after'          => $this->start_datetime_string,
				'date_before'         => $this->end_datetime_string,
			)
		);

		if ( $pre_wc_30 ) {
			remove_filter( 'posts_where', array( $this, 'date_range' ) );
		}

		// Check if there are orders with same IP but different billing/shipping details
		if ( count( $si_orders ) > 0 ) {
			$matching_orders = array();
			foreach ( $si_orders as $si_order_object ) {
				if (
					( $si_order_object->get_formatted_billing_address() != $order->get_formatted_billing_address() ) ||
					( $si_order_object->get_formatted_shipping_address() != $order->get_formatted_shipping_address() )
				) {
					$risk = true;
					// Collect matching order data
					$matching_orders[] = array(
						'id'        => $si_order_object->get_id(),
						'date'      => $si_order_object->get_date_created() ? $si_order_object->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
						'status'    => $si_order_object->get_status(),
						'total'     => $si_order_object->get_total(),
						'billing'   => $si_order_object->get_formatted_billing_address(),
						'shipping'  => $si_order_object->get_formatted_shipping_address(),
					);
				}
			}

			// Store IP multiple order details explainability data
			if ( $risk && ! empty( $matching_orders ) ) {
				// Cache the risky flag so subsequent requests for same IP skip the DB query.
				set_transient( $ip_risk_cache_key, true, 5 * MINUTE_IN_SECONDS );

				$ip_multiple_data = array(
					'ip'              => $order_ip,
					'time_window'     => $this->time_span . ' ' . __( 'days', 'woocommerce-anti-fraud' ),
					'count'           => count( $matching_orders ),
					'start_date'      => $this->start_datetime_string,
					'end_date'        => $this->end_datetime_string,
					'matching_orders' => $matching_orders,
					'reason'          => __( 'Same IP used with different billing/shipping details', 'woocommerce-anti-fraud' ),
				);
				opmc_hpos_update_post_meta( $order_id, '_wc_af_ip_multiple_data', $ip_multiple_data );

				$this->add_explanation_item( 'data_evaluated', array(
					'ip_address'   => $order_ip,
					'time_window'  => $this->time_span . ' days',
					'orders_found' => count( $matching_orders ),
				) );

				$this->add_explanation_item( 'reason', sprintf(
					'IP address %s was used for %d order(s) with different billing/shipping details in the last %d days',
					$order_ip,
					count( $matching_orders ),
					$this->time_span
				) );

				$this->add_explanation_item( 'alert_type', 'critical' );
			}
		}

		Af_Logger::debug( 'multiple order rule risk : ' . ( $risk ? 'true' : 'false' ) );
		return $risk;
	}

	// Enable rule check
	public function is_enabled() {
		if ( 'yes' == $this->is_enabled ) {
			return true;
		}
		return false;
	}
}
