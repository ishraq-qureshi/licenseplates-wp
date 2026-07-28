<?php
/**
 * Marketplace-Aware Order Source Detection
 *
 * Detects orders imported from eBay, Amazon, Etsy and other marketplaces
 * so Anti-Fraud rules can be applied appropriately without false positives.
 *
 * @since 7.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_AF_Marketplace_Detector' ) ) {

	class WC_AF_Marketplace_Detector {

		/**
		 * Order source constants
		 */
		const SOURCE_NATIVE         = 'woocommerce_native';
		const SOURCE_EBAY           = 'ebay';
		const SOURCE_AMAZON         = 'amazon';
		const SOURCE_ETSY           = 'etsy';
		const SOURCE_UNKNOWN_IMPORT = 'unknown_import';

		/**
		 * Meta key used to persist detected order source
		 */
		const ORDER_SOURCE_META_KEY = '_wc_af_order_source';

		/**
		 * Default marketplace profiles.
		 * Each profile controls how Anti-Fraud behaves for that source.
		 *
		 * - skip_rules:           Rule IDs whose risk points are removed from the score
		 * - base_score_bonus:     Extra points added to the final score (lower perceived risk)
		 * - ignore_unknown_origin: Skip "Unknown Origin" cancel logic
		 * - trust_payment:        Note that payment is already verified by the marketplace
		 * - action_on_suspicion:  What to do when score crosses cancel threshold (hold/allow/notify)
		 */
		protected static $profiles = array(
			'ebay' => array(
				'label'                 => 'eBay',
				'trust_payment'         => true,
				'ignore_unknown_origin' => true,
				'skip_rules'            => array(
					'detect_proxy',
					'ip_multiple_order_Details',
					'velocities_order',
					'first_order',
					'free_email',
					'temporary_email',
					'geo_location',
				),
				'base_score_bonus'      => 0,
				'action_on_suspicion'   => 'hold',
				'description'           => 'eBay Managed Payments — buyer identity and payment verified by eBay.',
			),
			'amazon' => array(
				'label'                 => 'Amazon',
				'trust_payment'         => true,
				'ignore_unknown_origin' => true,
				'skip_rules'            => array(
					'detect_proxy',
					'ip_multiple_order_Details',
					'velocities_order',
					'first_order',
					'free_email',
					'temporary_email',
					'geo_location',
				),
				'base_score_bonus'      => 10,
				'action_on_suspicion'   => 'hold',
				'description'           => 'Amazon SP-API — buyer verified through Amazon Pay, no on-site checkout.',
			),
			'etsy' => array(
				'label'                 => 'Etsy',
				'trust_payment'         => true,
				'ignore_unknown_origin' => true,
				'skip_rules'            => array(
					'detect_proxy',
					'ip_multiple_order_Details',
					'free_email',
					'temporary_email',
				),
				'base_score_bonus'      => 5,
				'action_on_suspicion'   => 'hold',
				'description'           => 'Etsy Payments — masked buyer email, payment processed by Etsy.',
			),
			'unknown_import' => array(
				'label'                 => 'Unknown Import',
				'trust_payment'         => false,
				'ignore_unknown_origin' => true,
				'skip_rules'            => array(),
				'base_score_bonus'      => 0,
				'action_on_suspicion'   => 'hold',
				'description'           => 'API-imported order from an unrecognised external source.',
			),
		);

		// -------------------------------------------------------------------------
		// Global toggle
		// -------------------------------------------------------------------------

		/**
		 * Check whether marketplace detection is globally enabled by the admin.
		 *
		 * @return bool
		 */
		public static function is_enabled() {
			return get_option( 'wc_af_marketplace_detection_enabled', 'no' ) === 'yes';
		}

		// -------------------------------------------------------------------------
		// Detection
		// -------------------------------------------------------------------------

		/**
		 * Detect the order source, persist it as order meta, and return the source key.
		 *
		 * @param  WC_Order $order
		 * @return string   One of the SOURCE_* constants
		 */
		public static function detect( WC_Order $order ) {
			// Return previously-detected value to avoid redundant processing.
			$cached = $order->get_meta( self::ORDER_SOURCE_META_KEY, true );
			if ( ! empty( $cached ) ) {
				return $cached;
			}

			$source = self::detect_source( $order );

			// Persist so the meta box and other components can read it without re-running detection.
			$order->update_meta_data( self::ORDER_SOURCE_META_KEY, $source );
			$order->save_meta_data();

			return $source;
		}

	/**
	 * Detect the order source without reading or writing cached meta.
	 * Safe to call on unsaved order objects (e.g. inside REST API pre-insert hooks).
	 *
	 * Security note: detection relies exclusively on server-set order meta, channel
	 * meta, created_via, and UTM attribution signals.  Billing email domain is
	 * intentionally excluded — it is a customer-supplied field and must not be
	 * used to grant marketplace trust or reduce fraud risk scores.
	 *
	 * @param  WC_Order $order
	 * @return string
	 */
		public static function detect_source( WC_Order $order ) {
			if ( self::is_ebay_order( $order ) ) {
				return self::SOURCE_EBAY;
			}
			if ( self::is_amazon_order( $order ) ) {
				return self::SOURCE_AMAZON;
			}
			if ( self::is_etsy_order( $order ) ) {
				return self::SOURCE_ETSY;
			}
			if ( self::is_unknown_import( $order ) ) {
				return self::SOURCE_UNKNOWN_IMPORT;
			}
			return self::SOURCE_NATIVE;
		}

		/**
		 * Return the order source stored in meta, or run detection if not yet saved.
		 *
		 * @param  int $order_id
		 * @return string
		 */
		public static function get_saved_source( $order_id ) {
			$saved = opmc_hpos_get_post_meta( $order_id, self::ORDER_SOURCE_META_KEY, true );
			if ( ! empty( $saved ) ) {
				return $saved;
			}
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return self::SOURCE_NATIVE;
			}
			return self::detect( $order );
		}

		// -------------------------------------------------------------------------
		// Per-marketplace signal detection
		// -------------------------------------------------------------------------

		/**
		 * Check whether the order is considered risky based on geolocation mismatch.
		 *
		 * @param WC_Order $order Order object.
		 * @return bool True if risk detected, false otherwise.
		 */
		protected static function is_ebay_order( WC_Order $order ) {
			// WP-Lister for eBay and similar plugins.
			if ( $order->get_meta( '_wplister_ebay_order_id', true ) ) {
return true;
			}
			if ( $order->get_meta( '_wplister_listing_id', true ) ) {
return true;
			}
			if ( $order->get_meta( '_wplister_ebay_site_id', true ) ) {
return true;
			}
			if ( $order->get_meta( '_ebay_order_id', true ) ) {
return true;
			}
			if ( $order->get_meta( 'ebay_order_id', true ) ) {
return true;
			}
			if ( $order->get_meta( '_wc_ebay_order_id', true ) ) {
return true;
			}

			// Generic channel / source meta used by several multi-channel plugins.
			$channel      = strtolower( (string) $order->get_meta( '_wc_channel', true ) );
			$order_source = strtolower( (string) $order->get_meta( '_order_source', true ) );
			if ( 'ebay' === $channel || 'ebay' === $order_source ) {
return true;
			}

			// created_via value set by the importer.
			$created_via = strtolower( (string) $order->get_created_via() );
			if ( false !== strpos( $created_via, 'ebay' ) ) {
return true;
			}
			if ( false !== strpos( $created_via, 'wplister' ) ) {
return true;
			}
			if ( false !== strpos( $created_via, 'wp-lister' ) ) {
return true;
			}

		// WooCommerce order attribution UTM source.
		$utm = strtolower( (string) $order->get_meta( '_wc_order_attribution_utm_source', true ) );
			if ( false !== strpos( $utm, 'ebay' ) ) {
	return true;
			}

		// Security: billing email domain (@ebay.com, @members.ebay.com) is NOT used as
		// a trust signal.  The billing_email field is set by the submitter and is
		// trivially spoofed — anyone can place an order with attacker@members.ebay.com.
		// Legitimate eBay orders are identified by the order-meta checks above which
		// are set server-side by the importer plugin, not by the customer.

		return false;
		}

		/**
		 * Check whether the order is considered risky based on geolocation mismatch.
		 *
		 * @param WC_Order $order Order object.
		 * @return bool True if risk detected, false otherwise.
		 */
		protected static function is_amazon_order( WC_Order $order ) {
			// WP-Lister for Amazon and SP-API connectors.
			if ( $order->get_meta( '_wplamazon_amazon_order_id', true ) ) {
return true;
			}
			if ( $order->get_meta( '_amazon_order_id', true ) ) {
return true;
			}
			if ( $order->get_meta( 'amazon_order_id', true ) ) {
return true;
			}
			if ( $order->get_meta( '_wc_amazon_order_id', true ) ) {
return true;
			}
			if ( $order->get_meta( '_amazon_reference_id', true ) ) {
return true;
			}
			// Amazon Pay WooCommerce plugin.
			if ( $order->get_meta( 'amazon_charge_permission_id', true ) ) {
return true;
			}
			if ( $order->get_meta( '_amazon_pay_charge_permission_id', true ) ) {
return true;
			}

			$channel      = strtolower( (string) $order->get_meta( '_wc_channel', true ) );
			$order_source = strtolower( (string) $order->get_meta( '_order_source', true ) );
			if ( 'amazon' === $channel || 'amazon' === $order_source ) {
return true;
			}

			$created_via = strtolower( (string) $order->get_created_via() );
			if ( false !== strpos( $created_via, 'amazon' ) ) {
return true;
			}
			if ( false !== strpos( $created_via, 'wplamazon' ) ) {
return true;
			}

		$utm = strtolower( (string) $order->get_meta( '_wc_order_attribution_utm_source', true ) );
			if ( false !== strpos( $utm, 'amazon' ) ) {
	return true;
			}

		// Security: billing email domain (@marketplace.amazon.com) is NOT used as a
		// trust signal.  The billing_email field is set by the submitter and is trivially
		// spoofed — anyone can place an order with attacker@marketplace.amazon.com.
		// Legitimate Amazon orders are identified by the order-meta checks above which
		// are written server-side by the importer plugin, not by the customer.

		return false;
		}

	/**
	 * Check whether the order is considered risky based on geolocation mismatch.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool True if risk detected, false otherwise.
	 */
		protected static function is_etsy_order( WC_Order $order ) {
			if ( $order->get_meta( '_etsy_order_id', true ) ) {
return true;
			}
			if ( $order->get_meta( 'etsy_order_id', true ) ) {
return true;
			}
			if ( $order->get_meta( '_etsy_receipt_id', true ) ) {
return true;
			}
			if ( $order->get_meta( 'etsy_receipt_id', true ) ) {
return true;
			}
			if ( $order->get_meta( '_etsy_transaction_id', true ) ) {
return true;
			}

			$channel      = strtolower( (string) $order->get_meta( '_wc_channel', true ) );
			$order_source = strtolower( (string) $order->get_meta( '_order_source', true ) );
			if ( 'etsy' === $channel || 'etsy' === $order_source ) {
return true;
			}

			$created_via = strtolower( (string) $order->get_created_via() );
			if ( false !== strpos( $created_via, 'etsy' ) ) {
return true;
			}

			$utm = strtolower( (string) $order->get_meta( '_wc_order_attribution_utm_source', true ) );
			if ( false !== strpos( $utm, 'etsy' ) ) {
	return true;
			}

			// Security: billing email domain (@buyer.etsy.com, @etsy.com) is NOT used as a
			// trust signal.  The billing_email field is set by the submitter and is trivially
			// spoofed — anyone can place an order with attacker@buyer.etsy.com.
			// Legitimate Etsy orders are identified by the order-meta checks above which
			// are written server-side by the importer plugin, not by the customer.

			return false;
		}

	/**
	 * Identifies API-imported orders that don't match any known marketplace.
		 * Criteria: not a native checkout origin, no customer IP address.
		 *
		 * @param WC_Order $order
		 * @return bool
		 */
		protected static function is_unknown_import( WC_Order $order ) {
			$native_origins = array(
				'checkout',
				'checkout-block',
				'store-api',
				'admin',
				'pos',
				'woocommerce-pos',
				'checkout-draft',
				'subscription',
			);

			$created_via = $order->get_created_via();
			if ( in_array( $created_via, $native_origins, true ) ) {
				return false;
			}

			// Consider "empty" created_via or "rest-api" without an IP as an unknown import.
			$ip = $order->get_customer_ip_address();
			if ( empty( $ip ) && ( empty( $created_via ) || 'rest-api' === $created_via ) ) {
				return true;
			}

			return false;
		}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Whether the order came from any known marketplace (not unknown import, not native).
	 *
	 * Detection is intentionally conservative: only server-set order meta, channel
	 * meta, created_via, or UTM signals count.  Billing email domain alone is NOT
	 * sufficient to classify an order as a marketplace order because billing_email
	 * is a customer-supplied field that can be trivially spoofed.
	 *
	 * @param WC_Order $order
	 * @return bool
	 */
		public static function is_marketplace_order( WC_Order $order ) {
			$source = self::detect_source( $order );
			return in_array( $source, array( self::SOURCE_EBAY, self::SOURCE_AMAZON, self::SOURCE_ETSY ), true );
		}

	/**
	 * Return true only when at least one strong (server-set) signal identifies the
	 * order as originating from the given marketplace.  Strong signals are order-meta
	 * keys written by the importer/integration plugin — they cannot be forged by the
	 * end user.  Email domain is explicitly excluded as a strong signal.
	 *
	 * This method is intended for callers that need higher confidence before granting
	 * marketplace-level trust (e.g. skipping fraud rules).
	 *
	 * @param  WC_Order $order
	 * @param  string   $source  One of the SOURCE_* constants.
	 * @return bool
	 * @since  7.2.0
	 */
		public static function has_strong_marketplace_signal( WC_Order $order, $source ) {
			switch ( $source ) {
				case self::SOURCE_EBAY:
					$created_via = strtolower( (string) $order->get_created_via() );
					return (bool) (
					$order->get_meta( '_wplister_ebay_order_id', true ) ||
					$order->get_meta( '_wplister_listing_id', true )    ||
					$order->get_meta( '_wplister_ebay_site_id', true )  ||
					$order->get_meta( '_ebay_order_id', true )          ||
					$order->get_meta( 'ebay_order_id', true )           ||
					$order->get_meta( '_wc_ebay_order_id', true )       ||
					'ebay' === strtolower( (string) $order->get_meta( '_wc_channel', true ) )   ||
					'ebay' === strtolower( (string) $order->get_meta( '_order_source', true ) ) ||
					false !== strpos( $created_via, 'ebay' )          ||
					false !== strpos( $created_via, 'wplister' )      ||
					false !== strpos( $created_via, 'wp-lister' )     ||
					false !== strpos( strtolower( (string) $order->get_meta( '_wc_order_attribution_utm_source', true ) ), 'ebay' )
					);

				case self::SOURCE_AMAZON:
					return (bool) (
					$order->get_meta( '_wplamazon_amazon_order_id', true )        ||
					$order->get_meta( '_amazon_order_id', true )                  ||
					$order->get_meta( 'amazon_order_id', true )                   ||
					$order->get_meta( '_wc_amazon_order_id', true )               ||
					$order->get_meta( '_amazon_reference_id', true )              ||
					$order->get_meta( 'amazon_charge_permission_id', true )       ||
					$order->get_meta( '_amazon_pay_charge_permission_id', true )  ||
					'amazon' === strtolower( (string) $order->get_meta( '_wc_channel', true ) )   ||
					'amazon' === strtolower( (string) $order->get_meta( '_order_source', true ) ) ||
					false !== strpos( strtolower( (string) $order->get_created_via() ), 'amazon' )   ||
					false !== strpos( strtolower( (string) $order->get_created_via() ), 'wplamazon' )||
					false !== strpos( strtolower( (string) $order->get_meta( '_wc_order_attribution_utm_source', true ) ), 'amazon' )
					);

				case self::SOURCE_ETSY:
					return (bool) (
					$order->get_meta( '_etsy_order_id', true )      ||
					$order->get_meta( 'etsy_order_id', true )       ||
					$order->get_meta( '_etsy_receipt_id', true )    ||
					$order->get_meta( 'etsy_receipt_id', true )     ||
					$order->get_meta( '_etsy_transaction_id', true )||
					'etsy' === strtolower( (string) $order->get_meta( '_wc_channel', true ) )   ||
					'etsy' === strtolower( (string) $order->get_meta( '_order_source', true ) ) ||
					false !== strpos( strtolower( (string) $order->get_created_via() ), 'etsy' ) ||
					false !== strpos( strtolower( (string) $order->get_meta( '_wc_order_attribution_utm_source', true ) ), 'etsy' )
					);

				default:
					return false;
			}
		}

	/**
	 * Whether marketplace-level trust (profile adjustments, origin bypass) may be
	 * applied for the given detected source.  Known marketplaces require at least
	 * one strong server-set signal; unknown imports use their own criteria.
	 *
	 * @param  WC_Order $order
	 * @param  string   $source  One of the SOURCE_* constants.
	 * @return bool
	 * @since  7.2.8
	 */
		public static function should_apply_marketplace_trust( WC_Order $order, $source ) {
			if ( self::SOURCE_NATIVE === $source ) {
				return false;
			}
			if ( self::SOURCE_UNKNOWN_IMPORT === $source ) {
				return true;
			}
			return self::has_strong_marketplace_signal( $order, $source );
		}

		/**
		 * Whether the order was imported (marketplace or unknown external source).
		 *
		 * @param WC_Order $order
		 * @return bool
		 */
		public static function is_imported_order( WC_Order $order ) {
			$source = self::detect_source( $order );
			return in_array(
				$source,
				array( self::SOURCE_EBAY, self::SOURCE_AMAZON, self::SOURCE_ETSY, self::SOURCE_UNKNOWN_IMPORT ),
				true
			);
		}

		/**
		 * Return the default profile for a source key.
		 *
		 * @param  string $source
		 * @return array|null
		 */
		public static function get_profile( $source ) {
			return isset( self::$profiles[ $source ] ) ? self::$profiles[ $source ] : null;
		}

		/**
		 * Return the profile with admin setting overrides applied.
		 *
		 * For eBay, Amazon and Etsy the built-in defaults cover everything — no
		 * per-marketplace settings are exposed in the UI, so this method simply
		 * returns the hardcoded profile for those sources.
		 *
		 * For SOURCE_UNKNOWN_IMPORT the single admin option
		 * `wc_af_marketplace_unknown_import_handling` controls the behaviour:
		 *  - treat_as_marketplace (default) → return the hold profile as-is
		 *  - treat_as_native               → return null so normal rules run fully
		 *  - always_hold                   → force action_on_suspicion to 'hold'
		 *
		 * @param  string $source
		 * @return array|null  null means "apply no marketplace profile"
		 */
		public static function get_effective_profile( $source ) {
			$profile = self::get_profile( $source );
			if ( null === $profile ) {
				return null;
			}

			// Handle the unknown-import edge case via the one admin-exposed option.
			if ( self::SOURCE_UNKNOWN_IMPORT === $source ) {
				$handling = get_option( 'wc_af_marketplace_unknown_import_handling', 'treat_as_marketplace' );

				if ( 'treat_as_native' === $handling ) {
					// Returning null tells do_check() to apply zero marketplace adjustments
					// and let the full rule pipeline (including unknown origin block) run.
					return null;
				}

				if ( 'always_hold' === $handling ) {
					$profile['action_on_suspicion']   = 'hold';
					$profile['ignore_unknown_origin']  = true;
				}
				// 'treat_as_marketplace' falls through with the default profile unchanged.
			}

			return $profile;
		}

		/**
		 * Human-readable display label for a source key.
		 *
		 * @param  string $source
		 * @return string
		 */
		public static function get_source_label( $source ) {
			$labels = array(
				self::SOURCE_NATIVE         => __( 'WooCommerce Checkout', 'woocommerce-anti-fraud' ),
				self::SOURCE_EBAY           => __( 'eBay', 'woocommerce-anti-fraud' ),
				self::SOURCE_AMAZON         => __( 'Amazon', 'woocommerce-anti-fraud' ),
				self::SOURCE_ETSY           => __( 'Etsy', 'woocommerce-anti-fraud' ),
				self::SOURCE_UNKNOWN_IMPORT => __( 'Unknown Import', 'woocommerce-anti-fraud' ),
			);
			return isset( $labels[ $source ] ) ? $labels[ $source ] : __( 'Unknown', 'woocommerce-anti-fraud' );
		}

		/**
		 * Display icon for a source key.
		 *
		 * @param  string $source
		 * @return string
		 */
		public static function get_source_icon( $source ) {
			$icons = array(
				self::SOURCE_NATIVE         => '🏪',
				self::SOURCE_EBAY           => '🛒',
				self::SOURCE_AMAZON         => '📦',
				self::SOURCE_ETSY           => '🎨',
				self::SOURCE_UNKNOWN_IMPORT => '📥',
			);
			return isset( $icons[ $source ] ) ? $icons[ $source ] : '❓';
		}

		/**
		 * Return a badge-style HTML string for use in admin UIs.
		 *
		 * @param  string $source
		 * @return string HTML
		 */
		public static function get_source_badge_html( $source ) {
			$colors = array(
				self::SOURCE_NATIVE         => array( 'bg' => '#e8f5e9', 'border' => '#4caf50', 'text' => '#2e7d32' ),
				self::SOURCE_EBAY           => array( 'bg' => '#e3f2fd', 'border' => '#2196f3', 'text' => '#1565c0' ),
				self::SOURCE_AMAZON         => array( 'bg' => '#fff8e1', 'border' => '#ff9800', 'text' => '#e65100' ),
				self::SOURCE_ETSY           => array( 'bg' => '#fce4ec', 'border' => '#e91e63', 'text' => '#880e4f' ),
				self::SOURCE_UNKNOWN_IMPORT => array( 'bg' => '#f3e5f5', 'border' => '#9c27b0', 'text' => '#4a148c' ),
			);

			$color = isset( $colors[ $source ] ) ? $colors[ $source ] : array( 'bg' => '#f5f5f5', 'border' => '#9e9e9e', 'text' => '#424242' );
			$icon  = self::get_source_icon( $source );
			$label = self::get_source_label( $source );

			return sprintf(
				'<span style="display:inline-block; background:%s; border:1px solid %s; color:%s; border-radius:3px; padding:2px 8px; font-size:12px; font-weight:600; margin-bottom:8px;">%s %s</span>',
				esc_attr( $color['bg'] ),
				esc_attr( $color['border'] ),
				esc_attr( $color['text'] ),
				esc_html( $icon ),
				esc_html( $label )
			);
		}

	}
}
