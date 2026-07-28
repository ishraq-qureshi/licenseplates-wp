<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_AF_Rule_Velocities extends WC_AF_Rule {

	private $is_enabled  = false;
	private $rule_weight = 0;
	private $time_stamp  = 0;
	private $max_orders  = 0;
	private $start_datetime_string = '';
	private $end_datetime_string   = '';

	/**
	 * The constructor
	 */
	public function __construct() {
		$this->is_enabled  = get_option( 'wc_af_attempt_count_check' );
		$this->rule_weight = get_option( 'wc_settings_anti_fraud_order_attempt_weight' );
		$this->time_stamp  = get_option( 'wc_settings_anti_fraud_attempt_time_span' );
		$this->max_orders  = get_option( 'wc_settings_anti_fraud_max_order_attempt_time_span' );

		parent::__construct(
			'velocities',
			__( 'IP address ordered multiple orders in the last ' . $this->time_stamp . ' hours.', 'woocommerce-anti-fraud' ),
			$this->rule_weight
		);
	}

	/**
	 * Add the date range condition to the SQL
	 *
	 * @param string $where
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function date_range( $where = '' ) {
		$where .= " AND ( `post_date` >= '" . $this->start_datetime_string . "' AND `post_date` <= '" . $this->end_datetime_string . "' )";

		return $where;
	}

	/**
	 * Do the required check in this method. The method must return a boolean.
	 *
	 * @param WC_Order $order
	 *
	 * @since  1.0.0
	 *
	 * @return bool
	 */
	public function is_risk( WC_Order $order ) {

		Af_Logger::debug( 'Checking velocities rule' );
		global $wpdb;

		// Default risk is false
		$risk = false;

		$pre_wc_30  = version_compare( WC_VERSION, '3.0', '<' );
		$order_id   = $pre_wc_30 ? $order->id : $order->get_id();
		$order_date = $pre_wc_30 ? $order->order_date : ( $order->get_date_created() ? gmdate( 'Y-m-d H:i:s', $order->get_date_created()->getOffsetTimestamp() ) : '' );
		$order_ip   = $pre_wc_30 ? opmc_hpos_get_post_meta( $order_id, '_customer_ip_address', true ) : $order->get_customer_ip_address();
		$email = $pre_wc_30 ? opmc_hpos_get_post_meta( $order_id, '_billing_email', true ) : $order->get_billing_email();
		$phone = $pre_wc_30 ? opmc_hpos_get_post_meta( $order_id, '_billing_phone', true ) : $order->get_billing_phone();

		if ( empty( $order_ip ) ) {
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

		// Calculate the new datetime
		$dt = new DateTime( $order_date );
		$dt->modify( '-' . $this->time_stamp . ' hours' );

		// Set the start and send datetime strings
		$this->start_datetime_string = $dt->format( 'Y-m-d H:i:s' );
		$this->end_datetime_string   = $order_date;

		/*
		 * PERFORMANCE FIX: Under bot attacks, unlimited wc_get_orders calls with limit=-1
		 * can saturate the MySQL query queue. Apply a hard cap so each check fetches at
		 * most N IDs regardless of store size, then cache positive ("risky") detections
		 * for 5 minutes so repeated checks for the same attacker IP/email/phone bypass
		 * the database entirely.
		 *
		 * The cap is intentionally set well above any realistic max_orders threshold
		 * (default 200, filterable) so legitimate risk decisions are never affected.
		 */

		/**
		 * Filters the maximum number of orders fetched per velocity dimension.
		 *
		 * Increase this value if your max_orders threshold is higher than 100.
		 * Lower it to reduce DB load under attack. Default: 200.
		 * During bot-attack mode this is automatically reduced to max_orders+1 so
		 * each query stops at the threshold rather than scanning the full window.
		 *
		 * @param int $limit Maximum order count per velocity query.
		 */
		$under_attack = class_exists( 'WooCommerce_Anti_Fraud' ) && WooCommerce_Anti_Fraud::is_under_bot_attack();
		$default_cap  = $under_attack ? (int) $this->max_orders + 1 : 200;
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
		$query_cap    = (int) apply_filters( 'wc_af_velocity_query_limit', $default_cap );
		$query_cap    = max( (int) $this->max_orders + 1, $query_cap );

		if ( $under_attack ) {
			Af_Logger::debug( 'Velocity rule: running in attack-mode with reduced query cap (' . $query_cap . ').' );
		}

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

		// For WC 3.0, `date_before` and `date_after` will take care of this filter.
		if ( $pre_wc_30 ) {
			// Add date range filter
			add_filter( 'posts_where', array( $this, 'date_range' ) );
		}

		// --- Check IP velocity ---
		// Short-circuit using cached risky flag to avoid repeated DB hits during attacks.
		$ip_cache_key = 'wc_af_risky_ip_' . substr( md5( $order_ip ), 0, 16 );
		$ip_count     = 0;

		if ( get_transient( $ip_cache_key ) ) {
			Af_Logger::debug( 'Velocity IP risk: cached risky flag hit for IP ' . $order_ip );
			$ip_count = (int) $this->max_orders; // treat as threshold met
		} else {
			$velocities_custmr_ip_ids = wc_get_orders(
				array(
					'limit'               => $query_cap,
					'return'              => 'ids',
					'exclude'             => array( $order_id ),
					'customer_ip_address' => $order_ip,
					'type'                => wc_get_order_types( 'order-count' ),
					'status'              => $non_draft_statuses,
					'date_after'          => $this->start_datetime_string,
					'date_before'         => $this->end_datetime_string,
				)
			);
			$ip_count = count( $velocities_custmr_ip_ids );
			if ( $ip_count >= $this->max_orders ) {
				set_transient( $ip_cache_key, true, 5 * MINUTE_IN_SECONDS );
			}
		}

		// --- Check email velocity ---
		$email_cache_key = 'wc_af_risky_email_' . substr( md5( $email ), 0, 16 );
		$email_count     = 0;

		if ( ! empty( $email ) ) {
			if ( get_transient( $email_cache_key ) ) {
				Af_Logger::debug( 'Velocity email risk: cached risky flag hit for email ' . $email );
				$email_count = (int) $this->max_orders;
			} else {
				$velocities_custmr_email_ids = wc_get_orders(
					array(
						'limit'        => $query_cap,
						'return'       => 'ids',
						'exclude'      => array( $order_id ),
						'billing_email' => $email,
						'type'         => wc_get_order_types( 'order-count' ),
						'status'       => $non_draft_statuses,
						'date_after'   => $this->start_datetime_string,
						'date_before'  => $this->end_datetime_string,
					)
				);
				$email_count = count( $velocities_custmr_email_ids );
				if ( $email_count >= $this->max_orders ) {
					set_transient( $email_cache_key, true, 5 * MINUTE_IN_SECONDS );
				}
			}
		}

		// --- Check phone velocity ---
		$phone_cache_key = ! empty( $phone ) ? 'wc_af_risky_phone_' . substr( md5( $phone ), 0, 16 ) : '';
		$phone_count     = 0;

		if ( ! empty( $phone ) ) {
			if ( get_transient( $phone_cache_key ) ) {
				Af_Logger::debug( 'Velocity phone risk: cached risky flag hit for phone ' . $phone );
				$phone_count = (int) $this->max_orders;
			} else {
				$velocities_custmr_mob_ids = wc_get_orders(
					array(
						'limit'         => $query_cap,
						'return'        => 'ids',
						'exclude'       => array( $order_id ),
						'billing_phone' => $phone,
						'type'          => wc_get_order_types( 'order-count' ),
						'status'        => $non_draft_statuses,
						'date_after'    => $this->start_datetime_string,
						'date_before'   => $this->end_datetime_string,
					)
				);
				$phone_count = count( $velocities_custmr_mob_ids );
				if ( $phone_count >= $this->max_orders ) {
					set_transient( $phone_cache_key, true, 5 * MINUTE_IN_SECONDS );
				}
			}
		}

		if ( $pre_wc_30 ) {
			// Remove date range filter
			remove_filter( 'posts_where', array( $this, 'date_range' ) );
		}

		// Check if there are orders with same IP
		if ( $ip_count >= $this->max_orders || $email_count >= $this->max_orders || $phone_count >= $this->max_orders ) {
			$risk = true;

			// Store velocity explainability data for admin display.
			// Load full order objects only when needed for display, capped at 20 to avoid memory bloat.
			$explain_cap     = 20;
			$velocity_data   = array();

			if ( $ip_count >= $this->max_orders ) {
				$matching_orders = array();
				$ip_orders_full  = wc_get_orders(
					array(
						'limit'               => $explain_cap,
						'exclude'             => array( $order_id ),
						'customer_ip_address' => $order_ip,
						'type'                => wc_get_order_types( 'order-count' ),
						'status'              => $non_draft_statuses,
						'date_after'          => $this->start_datetime_string,
						'date_before'         => $this->end_datetime_string,
					)
				);
				foreach ( $ip_orders_full as $matched_order ) {
					$matching_orders[] = array(
						'id'     => $matched_order->get_id(),
						'date'   => $matched_order->get_date_created() ? $matched_order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
						'status' => $matched_order->get_status(),
						'total'  => $matched_order->get_total(),
					);
				}
				$velocity_data['ip'] = array(
					'value'           => $order_ip,
					'time_window'     => $this->time_stamp . ' ' . __( 'hours', 'woocommerce-anti-fraud' ),
					'threshold'       => $this->max_orders,
					'count'           => $ip_count,
					'start_date'      => $this->start_datetime_string,
					'end_date'        => $this->end_datetime_string,
					'matching_orders' => $matching_orders,
				);
			}

			if ( $email_count >= $this->max_orders ) {
				$matching_orders      = array();
				$email_orders_full    = wc_get_orders(
					array(
						'limit'         => $explain_cap,
						'exclude'       => array( $order_id ),
						'billing_email' => $email,
						'type'          => wc_get_order_types( 'order-count' ),
						'status'        => $non_draft_statuses,
						'date_after'    => $this->start_datetime_string,
						'date_before'   => $this->end_datetime_string,
					)
				);
				foreach ( $email_orders_full as $matched_order ) {
					$matching_orders[] = array(
						'id'     => $matched_order->get_id(),
						'date'   => $matched_order->get_date_created() ? $matched_order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
						'status' => $matched_order->get_status(),
						'total'  => $matched_order->get_total(),
					);
				}
				$velocity_data['email'] = array(
					'value'           => $email,
					'time_window'     => $this->time_stamp . ' ' . __( 'hours', 'woocommerce-anti-fraud' ),
					'threshold'       => $this->max_orders,
					'count'           => $email_count,
					'start_date'      => $this->start_datetime_string,
					'end_date'        => $this->end_datetime_string,
					'matching_orders' => $matching_orders,
				);
			}

			if ( $phone_count >= $this->max_orders ) {
				$matching_orders    = array();
				$phone_orders_full  = wc_get_orders(
					array(
						'limit'         => $explain_cap,
						'exclude'       => array( $order_id ),
						'billing_phone' => $phone,
						'type'          => wc_get_order_types( 'order-count' ),
						'status'        => $non_draft_statuses,
						'date_after'    => $this->start_datetime_string,
						'date_before'   => $this->end_datetime_string,
					)
				);
				foreach ( $phone_orders_full as $matched_order ) {
					$matching_orders[] = array(
						'id'     => $matched_order->get_id(),
						'date'   => $matched_order->get_date_created() ? $matched_order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
						'status' => $matched_order->get_status(),
						'total'  => $matched_order->get_total(),
					);
				}
				$velocity_data['phone'] = array(
					'value'           => $phone,
					'time_window'     => $this->time_stamp . ' ' . __( 'hours', 'woocommerce-anti-fraud' ),
					'threshold'       => $this->max_orders,
					'count'           => $phone_count,
					'start_date'      => $this->start_datetime_string,
					'end_date'        => $this->end_datetime_string,
					'matching_orders' => $matching_orders,
				);
			}

			// Store the data as order meta for later retrieval
			opmc_hpos_update_post_meta( $order_id, '_wc_af_velocity_data', $velocity_data );
		}

		Af_Logger::debug( 'velocities rule risk : ' . ( true === $risk ? 'true' : 'false' ) );
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
