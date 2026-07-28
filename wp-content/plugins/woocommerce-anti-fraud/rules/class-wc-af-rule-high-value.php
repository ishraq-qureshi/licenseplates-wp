<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_AF_Rule_High_Value extends WC_AF_Rule {
	private $is_enabled  = false;
	private $rule_weight = 0;
	private $transient_key = 'wc_af_avg_order_total';

	public function __construct() {
		$this->is_enabled  = get_option( 'wc_af_order_avg_amount_check' );
		$this->rule_weight = (int) get_option( 'wc_settings_anti_fraud_order_avg_amount_weight', 15 ); // ✅ Cast to int with default

		/**
		 * Filters the high value multiplier used to determine if an order is high-value.
		 *
		 * @since 1.0.0
		 *
		 * @param int $multiplier The multiplier value for average order comparison.
		 */
		parent::__construct(
			'high_value',
			sprintf(
				'Order has a total higher than %s times the average order.',
				/**
				 * Filters the high value multiplier used in rule description.
				 *
				 * @since 1.0.0
				 *
				 * @param int $rule_weight The rule weight value from settings.
				 */
				apply_filters( 'wc_af_high_value_multiplier', $this->rule_weight )
			),
			15
		);
	}


	public function is_risk( WC_Order $order ) {
		Af_Logger::debug( 'Checking high value rule' );
		$risk = false;
		$avg_order_total = get_transient( $this->transient_key );

		// fallback if transient is missing
		if ( false === $avg_order_total && function_exists( 'wc_af_refresh_avg_order_total_handler' ) ) {
			$avg_order_total = wc_af_refresh_avg_order_total_handler();
		}

		$order_date = $order->get_date_created();
		if ( $order_date && $order_date->format( 'Y-m-d' ) === current_time( 'Y-m-d' ) ) {
			$order_total = (float) $order->get_total();
			$avg_order_total = $this->include_today_order( $avg_order_total, $order_total );
		}

		Af_Logger::debug( 'Average order total : ' . $avg_order_total );

		/**
		 * Filters the multiplier used to define a high-value order.
		 *
		 * @since 1.0.0
		 *
		 * @param int $multiplier Multiplier value (default: 2).
		 */
		$multiplier = (float) apply_filters( 
			'wc_af_high_value_multiplier', 
			get_option( 'wc_settings_anti_fraud_avg_amount_multiplier', 2 ) 
		);

		if ( floatval($avg_order_total) > 0 && $order->get_total() > ( floatval($avg_order_total) * $multiplier ) ) {
			$risk = true;
		}

		Af_Logger::debug( 'high value rule risk : ' . ( $risk ? 'true' : 'false' ) );

		return $risk;
	}

	public function refresh_avg_order_total() {

		/**
		 * Filters the order statuses used to calculate average order total.
		 *
		 * @since 1.0.0
		 *
		 * @param array $statuses Array of order statuses.
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
		set_transient( $this->transient_key, $avg, DAY_IN_SECONDS );

		return $avg;
	}

	private function include_today_order( $cached_avg, $today_order_total ) {
		// Adjust by simple averaging (can be replaced with weighted logic)
		return round( ( $cached_avg + $today_order_total ) / 2, 2 );
	}

	public function is_enabled() {
		if ( 'yes' === $this->is_enabled ) {
			return true;
		} else {
			return false;
		}
	}
}
