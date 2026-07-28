<?php
/**
 * Attempt Intelligence Service
 *
 * Advanced velocity engine for card-testing attack detection. Records lightweight
 * attempt records and evaluates broader signals (email domain, store-wide,
 * billing country) to detect attacks that rotate IP, email, phone, and address.
 *
 * @package WooCommerce_Anti_Fraud
 * @since 7.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_AF_Attempt_Intelligence_Service' ) ) {

	/**
	 * WC_AF_Attempt_Intelligence_Service
	 */
	class WC_AF_Attempt_Intelligence_Service {

		/**
		 * Table name (without prefix).
		 *
		 * @var string
		 */
		const TABLE_NAME = 'wc_af_attempt_records';

		/**
		 * DB version for migrations.
		 *
		 * @var string
		 */
		const DB_VERSION = '1.0';

		/**
		 * Option key for DB version.
		 *
		 * @var string
		 */
		const DB_VERSION_OPTION = 'wc_af_attempt_intelligence_db_version';

		/**
		 * Singleton instance.
		 *
		 * @var WC_AF_Attempt_Intelligence_Service|null
		 */
		private static $instance = null;

		/**
		 * Get singleton instance.
		 *
		 * @return WC_AF_Attempt_Intelligence_Service
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Get full table name.
		 *
		 * @return string
		 */
		public function get_table_name() {
			global $wpdb;
			return $wpdb->prefix . self::TABLE_NAME;
		}

		/**
		 * Create or update the attempt records table.
		 *
		 * @return void
		 */
		public function ensure_table() {
			$installed_version = get_option( self::DB_VERSION_OPTION, '0' );
			if ( version_compare( $installed_version, self::DB_VERSION, '>=' ) ) {
				return;
			}

			global $wpdb;
			$table = $this->get_table_name();
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				source varchar(32) NOT NULL DEFAULT 'order',
				ip_address varchar(45) NOT NULL DEFAULT '',
				email varchar(255) NOT NULL DEFAULT '',
				email_domain varchar(191) NOT NULL DEFAULT '',
				phone varchar(64) NOT NULL DEFAULT '',
				billing_country char(2) NOT NULL DEFAULT '',
				billing_region varchar(64) NOT NULL DEFAULT '',
				order_total_band decimal(12,2) NOT NULL DEFAULT 0.00,
				order_id bigint(20) unsigned DEFAULT NULL,
				payment_method varchar(64) NOT NULL DEFAULT '',
				session_id varchar(64) NOT NULL DEFAULT '',
				user_agent_hash varchar(64) NOT NULL DEFAULT '',
				PRIMARY KEY (id),
				KEY created_at (created_at),
				KEY source (source),
				KEY ip_address (ip_address),
				KEY email_domain (email_domain),
				KEY billing_country (billing_country),
				KEY order_total_band (order_total_band)
			) {$charset_collate};";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
			update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
		}

		/**
		 * Normalize email to domain for correlation.
		 *
		 * @param string $email Email address.
		 * @return string Domain part, or empty if invalid.
		 */
		public function get_email_domain( $email ) {
			if ( empty( $email ) || ! is_email( $email ) ) {
				return '';
			}
			$parts = explode( '@', strtolower( trim( $email ) ), 2 );
			return isset( $parts[1] ) ? $parts[1] : '';
		}

		/**
		 * Round order total to a band for clustering (e.g. $0-10, $10-25).
		 *
		 * @param float $total Order total.
		 * @return float Band value (lower bound of band).
		 */
		public function get_total_band( $total ) {
			$total = (float) $total;
			if ( $total <= 0 ) {
				return 0;
			}
			if ( $total <= 10 ) {
				return 0;
			}
			if ( $total <= 25 ) {
				return 10;
			}
			if ( $total <= 50 ) {
				return 25;
			}
			if ( $total <= 100 ) {
				return 50;
			}
			if ( $total <= 250 ) {
				return 100;
			}
			if ( $total <= 500 ) {
				return 250;
			}
			return 500;
		}

		/**
		 * Hash user agent for privacy-safe grouping.
		 *
		 * @param string $ua User agent string.
		 * @return string 32-char hash or empty.
		 */
		public function hash_user_agent( $ua ) {
			if ( empty( $ua ) || ! is_string( $ua ) ) {
				return '';
			}
			return substr( md5( $ua ), 0, 32 );
		}

		/**
		 * Record an attempt.
		 *
		 * @param array $data Must include: source ('order'|'payment_failed'), ip_address.
		 *                    Optional: email, phone, billing_country, billing_region,
		 *                    order_total, order_id, payment_method, session_id, user_agent.
		 * @return int|false Insert ID or false on failure.
		 */
		public function record_attempt( $data ) {
			global $wpdb;

			$table = $this->get_table_name();

			$source = isset( $data['source'] ) ? sanitize_text_field( $data['source'] ) : 'order';
			if ( ! in_array( $source, array( 'order', 'payment_failed' ), true ) ) {
				$source = 'order';
			}

			$email = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
			$email_domain = $this->get_email_domain( $email );
			$phone = isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '';
			$country = isset( $data['billing_country'] ) ? sanitize_text_field( substr( $data['billing_country'], 0, 2 ) ) : '';
			$region = isset( $data['billing_region'] ) ? sanitize_text_field( substr( $data['billing_region'], 0, 64 ) ) : '';
			$total = isset( $data['order_total'] ) ? (float) $data['order_total'] : 0;
			$total_band = $this->get_total_band( $total );
			$order_id = isset( $data['order_id'] ) ? absint( $data['order_id'] ) : null;
			$payment_method = isset( $data['payment_method'] ) ? sanitize_text_field( substr( $data['payment_method'], 0, 64 ) ) : '';
			$session_id = isset( $data['session_id'] ) ? sanitize_text_field( substr( $data['session_id'], 0, 64 ) ) : '';
			if ( isset( $data['user_agent'] ) ) {
				$user_agent = sanitize_text_field( wp_unslash( $data['user_agent'] ) );
			} elseif ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
				$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
			} else {
				$user_agent = '';
			}
			$user_agent_hash = $this->hash_user_agent( $user_agent );

			$ip_address = isset( $data['ip_address'] ) ? sanitize_text_field( $data['ip_address'] ) : '';
			if ( empty( $ip_address ) && function_exists( 'WC_Geolocation' ) && class_exists( 'WC_Geolocation' ) ) {
				$ip_address = WC_Geolocation::get_ip_address();
			}

			$now = current_time( 'mysql' );

			$result = $wpdb->insert(
				$table,
				array(
					'created_at'       => $now,
					'source'           => $source,
					'ip_address'       => $ip_address,
					'email'            => $email,
					'email_domain'     => $email_domain,
					'phone'            => $phone,
					'billing_country'  => $country,
					'billing_region'   => $region,
					'order_total_band' => $total_band,
					'order_id'         => $order_id,
					'payment_method'   => $payment_method,
					'session_id'       => $session_id,
					'user_agent_hash'  => $user_agent_hash,
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%d', '%s', '%s', '%s' )
			);

			if ( false === $result ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && class_exists( 'Af_Logger' ) ) {
					Af_Logger::debug( 'WC_AF_Attempt_Intelligence_Service: insert failed: ' . $wpdb->last_error );
				}
				return false;
			}

			return (int) $wpdb->insert_id;
		}

		/**
		 * Count attempts in time window by dimension.
		 *
		 * @param array $args {
		 *     @type string $start_date Start datetime (Y-m-d H:i:s).
		 *     @type string $end_date   End datetime (Y-m-d H:i:s).
		 *     @type string $dimension  'store'|'ip'|'email_domain'|'country'|'total_band'.
		 *     @type string $value      Value for dimension (required when dimension is not 'store').
		 *     @type string $source     Filter by source: 'order','payment_failed', or '' for all.
		 * }
		 * @return int
		 */
		public function count_attempts( $args ) {
			global $wpdb;
			$table = $this->get_table_name();

			$start_date = isset( $args['start_date'] ) ? sanitize_text_field( $args['start_date'] ) : '';
			$end_date   = isset( $args['end_date'] ) ? sanitize_text_field( $args['end_date'] ) : '';
			$dimension  = isset( $args['dimension'] ) ? sanitize_text_field( $args['dimension'] ) : 'store';
			$value      = isset( $args['value'] ) ? $args['value'] : '';
			$source     = isset( $args['source'] ) ? sanitize_text_field( $args['source'] ) : '';

			if ( empty( $start_date ) || empty( $end_date ) ) {
				return 0;
			}

			$has_source = ! empty( $source ) && in_array( $source, array( 'order', 'payment_failed' ), true );
			$count      = 0;

			if ( 'ip' === $dimension && ! empty( $value ) ) {
				$dim_value = sanitize_text_field( $value );
				if ( $has_source ) {
					$count = (int) $wpdb->get_var(
						$wpdb->prepare(
							'SELECT COUNT(*) FROM ' . esc_sql( $table ) . ' WHERE created_at >= %s AND created_at <= %s AND source = %s AND ip_address = %s',
							$start_date, $end_date, $source, $dim_value
						)
					);
				} else {
					$count = (int) $wpdb->get_var(
						$wpdb->prepare(
							'SELECT COUNT(*) FROM ' . esc_sql( $table ) . ' WHERE created_at >= %s AND created_at <= %s AND ip_address = %s',
							$start_date, $end_date, $dim_value
						)
					);
				}
			} elseif ( 'email_domain' === $dimension && ! empty( $value ) ) {
				$dim_value = sanitize_text_field( $value );
				if ( $has_source ) {
					$count = (int) $wpdb->get_var(
						$wpdb->prepare(
							'SELECT COUNT(*) FROM ' . esc_sql( $table ) . ' WHERE created_at >= %s AND created_at <= %s AND source = %s AND email_domain = %s',
							$start_date, $end_date, $source, $dim_value
						)
					);
				} else {
					$count = (int) $wpdb->get_var(
						$wpdb->prepare(
							'SELECT COUNT(*) FROM ' . esc_sql( $table ) . ' WHERE created_at >= %s AND created_at <= %s AND email_domain = %s',
							$start_date, $end_date, $dim_value
						)
					);
				}
			} elseif ( 'country' === $dimension && ! empty( $value ) ) {
				$dim_value = sanitize_text_field( substr( $value, 0, 2 ) );
				if ( $has_source ) {
					$count = (int) $wpdb->get_var(
						$wpdb->prepare(
							'SELECT COUNT(*) FROM ' . esc_sql( $table ) . ' WHERE created_at >= %s AND created_at <= %s AND source = %s AND billing_country = %s',
							$start_date, $end_date, $source, $dim_value
						)
					);
				} else {
					$count = (int) $wpdb->get_var(
						$wpdb->prepare(
							'SELECT COUNT(*) FROM ' . esc_sql( $table ) . ' WHERE created_at >= %s AND created_at <= %s AND billing_country = %s',
							$start_date, $end_date, $dim_value
						)
					);
				}
			} elseif ( 'total_band' === $dimension && '' !== $value ) {
				$dim_value = (float) $value;
				if ( $has_source ) {
					$count = (int) $wpdb->get_var(
						$wpdb->prepare(
							'SELECT COUNT(*) FROM ' . esc_sql( $table ) . ' WHERE created_at >= %s AND created_at <= %s AND source = %s AND order_total_band = %f',
							$start_date, $end_date, $source, $dim_value
						)
					);
				} else {
					$count = (int) $wpdb->get_var(
						$wpdb->prepare(
							'SELECT COUNT(*) FROM ' . esc_sql( $table ) . ' WHERE created_at >= %s AND created_at <= %s AND order_total_band = %f',
							$start_date, $end_date, $dim_value
						)
					);
				}
			} else {
				// store dimension or dimension without a value — date range (+ optional source) only
				if ( $has_source ) {
					$count = (int) $wpdb->get_var(
						$wpdb->prepare(
							'SELECT COUNT(*) FROM ' . esc_sql( $table ) . ' WHERE created_at >= %s AND created_at <= %s AND source = %s',
							$start_date, $end_date, $source
						)
					);
				} else {
					$count = (int) $wpdb->get_var(
						$wpdb->prepare(
							'SELECT COUNT(*) FROM ' . esc_sql( $table ) . ' WHERE created_at >= %s AND created_at <= %s',
							$start_date, $end_date
						)
					);
				}
			}

			return max( 0, $count );
		}

		/**
		 * Check if any dimension exceeds threshold in the time window.
		 *
		 * @param array $context {
		 *     @type string $ip_address
		 *     @type string $email
		 *     @type string $phone (optional)
		 *     @type string $billing_country
		 *     @type float  $order_total
		 * }
		 * @param int   $time_span_hours Time window in hours.
		 * @param int   $max_attempts   Threshold.
		 * @param string $attempt_source 'orders_only'|'payment_attempts'|'both'.
		 * @return array{ 'blocked' => bool, 'trigger' => string, 'count' => int, 'dimension' => string }
		 */
		public function evaluate_velocity( $context, $time_span_hours, $max_attempts, $attempt_source = 'orders_only' ) {
			$dt_end = new DateTime( 'now', wp_timezone() );
			$dt_start = clone $dt_end;
			$dt_start->modify( '-' . (int) $time_span_hours . ' hours' );
			$start_date = $dt_start->format( 'Y-m-d H:i:s' );
			$end_date   = $dt_end->format( 'Y-m-d H:i:s' );

			$source_filter = '';
			if ( 'orders_only' === $attempt_source ) {
				$source_filter = 'order';
			} elseif ( 'payment_attempts' === $attempt_source ) {
				$source_filter = 'payment_failed';
			}

			$base = array(
				'start_date' => $start_date,
				'end_date'   => $end_date,
				'source'     => $source_filter,
			);

			// Store-wide
			$store_count = $this->count_attempts( array_merge( $base, array( 'dimension' => 'store' ) ) );
			if ( $store_count >= $max_attempts ) {
				return array(
					'blocked'   => true,
					'trigger'   => 'attempt_intelligence_store',
					'count'     => $store_count,
					'dimension' => 'store',
				);
			}

			// IP
			$ip = isset( $context['ip_address'] ) ? $context['ip_address'] : '';
			if ( ! empty( $ip ) ) {
				$ip_count = $this->count_attempts( array_merge( $base, array( 'dimension' => 'ip', 'value' => $ip ) ) );
				if ( $ip_count >= $max_attempts ) {
					return array(
						'blocked'   => true,
						'trigger'   => 'attempt_intelligence_ip',
						'count'     => $ip_count,
						'dimension' => 'ip',
					);
				}
			}

			// Email domain
			$email = isset( $context['email'] ) ? $context['email'] : '';
			$domain = $this->get_email_domain( $email );
			if ( ! empty( $domain ) ) {
				$domain_count = $this->count_attempts( array_merge( $base, array( 'dimension' => 'email_domain', 'value' => $domain ) ) );
				if ( $domain_count >= $max_attempts ) {
					return array(
						'blocked'   => true,
						'trigger'   => 'attempt_intelligence_email_domain',
						'count'     => $domain_count,
						'dimension' => 'email_domain',
					);
				}
			}

			// Billing country
			$country = isset( $context['billing_country'] ) ? $context['billing_country'] : '';
			if ( ! empty( $country ) ) {
				$country_count = $this->count_attempts( array_merge( $base, array( 'dimension' => 'country', 'value' => $country ) ) );
				if ( $country_count >= $max_attempts ) {
					return array(
						'blocked'   => true,
						'trigger'   => 'attempt_intelligence_country',
						'count'     => $country_count,
						'dimension' => 'country',
					);
				}
			}

			// Total band
			$total = isset( $context['order_total'] ) ? (float) $context['order_total'] : 0;
			if ( $total > 0 ) {
				$band = $this->get_total_band( $total );
				$band_count = $this->count_attempts( array_merge( $base, array( 'dimension' => 'total_band', 'value' => $band ) ) );
				if ( $band_count >= $max_attempts ) {
					return array(
						'blocked'   => true,
						'trigger'   => 'attempt_intelligence_total_band',
						'count'     => $band_count,
						'dimension' => 'total_band',
					);
				}
			}

			return array(
				'blocked'   => false,
				'trigger'   => '',
				'count'     => 0,
				'dimension' => '',
			);
		}

		/**
		 * Build context from order or posted checkout data.
		 *
		 * @param WC_Order|array $order_or_data Order object or array of checkout fields.
		 * @return array
		 */
		public function build_context_from_order_or_data( $order_or_data ) {
			$context = array(
				'ip_address'      => '',
				'email'           => '',
				'phone'           => '',
				'billing_country' => '',
				'order_total'     => 0,
			);

			if ( $order_or_data instanceof WC_Order ) {
				$order = $order_or_data;
				$context['ip_address']      = $order->get_customer_ip_address();
				$context['email']           = $order->get_billing_email();
				$context['phone']           = $order->get_billing_phone();
				$context['billing_country'] = $order->get_billing_country();
				$context['order_total']      = (float) $order->get_total();
			} elseif ( is_array( $order_or_data ) ) {
				$context['ip_address']      = isset( $order_or_data['ip_address'] ) ? $order_or_data['ip_address'] : ( function_exists( 'WC_Geolocation' ) ? WC_Geolocation::get_ip_address() : '' );
				$context['email']           = isset( $order_or_data['billing_email'] ) ? $order_or_data['billing_email'] : '';
				$context['phone']           = isset( $order_or_data['billing_phone'] ) ? $order_or_data['billing_phone'] : '';
				$context['billing_country'] = isset( $order_or_data['billing_country'] ) ? $order_or_data['billing_country'] : '';
				$context['order_total']     = isset( $order_or_data['order_total'] ) ? (float) $order_or_data['order_total'] : 0;
			}

			return $context;
		}

		/**
		 * Build record data from order.
		 *
		 * @param WC_Order $order Order object.
		 * @param string   $source 'order' or 'payment_failed'.
		 * @return array
		 */
		public function build_record_from_order( $order, $source = 'order' ) {
			if ( ! $order instanceof WC_Order ) {
				return array();
			}

			$data = array(
				'source'          => $source,
				'ip_address'      => $order->get_customer_ip_address(),
				'email'           => $order->get_billing_email(),
				'phone'           => $order->get_billing_phone(),
				'billing_country' => $order->get_billing_country(),
				'billing_region'  => $order->get_billing_state(),
				'order_total'     => (float) $order->get_total(),
				'order_id'        => $order->get_id(),
				'payment_method'  => $order->get_payment_method(),
			);

			if ( function_exists( 'WC' ) && WC()->session ) {
				$data['session_id'] = WC()->session->get_customer_id();
			}
			return $data;
		}

		/**
		 * Purge old records to limit table size (e.g. older than 30 days).
		 *
		 * @param int $days Retention days. Default 30.
		 * @return int Rows deleted.
		 */
		public function purge_old_records( $days = 30 ) {
			global $wpdb;
			$table = $this->get_table_name();
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . esc_sql( $table ) . ' WHERE created_at < %s', $cutoff ) );
		}
	}
}
