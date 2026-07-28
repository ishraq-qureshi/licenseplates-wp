<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'WC_AF_Maxmind_Meta_Box' ) ) {

	/**
	 * Class for WC_AF_Meta_Box
	 */
	class WC_AF_Maxmind_Meta_Box {
		/**
		 * Class for construct
		 */
		public function __construct() {

			foreach ( wc_get_order_types( 'custom-order-meta-box' ) as $type ) {
				opmc_hpos_add_meta_box( 'woocommerce-af-maxmind-risk', __( 'MaxMind Risk Score', 'woocommerce-anti-fraud' ), array( $this, 'MaxMindRiskScoreOutput' ), $type, 'side', 'high' );
			}
		}

		/**
		 * Output the metabox.
		 *
		 * @since  1.0.0
		 */
		public function MaxMindRiskScoreOutput() {

			$hpos_enabled = get_option( 'woocommerce_custom_orders_table_enabled', true );

			if ( 'yes' != $hpos_enabled ) {
				if ( ! isset( $_GET['post'] ) ) {
					return;
				}
				$order_id = sanitize_text_field( $_GET['post'] );
			} else {
				if ( ! isset( $_GET['id'] ) ) {
					return;
				}
				$order_id = sanitize_text_field( $_GET['id'] );
			}

			$score_points = opmc_hpos_get_post_meta( $order_id, '_wc_af_maxmind_score', true );

			// Nothing to show yet (MinFraud not run or not enabled)
			if ( '' === $score_points && false === $score_points ) {
				echo '<p style="color:#999;font-size:12px;">' . esc_html__( 'No MinFraud data available for this order.', 'woocommerce-anti-fraud' ) . '</p>';
				return;
			}

			// Score colour band
			if ( $score_points > 70 ) {
				$score_color = '#D54E21';
				$score_label = __( 'High Risk', 'woocommerce-anti-fraud' );
			} elseif ( $score_points > 40 ) {
				$score_color = '#e6a817';
				$score_label = __( 'Medium Risk', 'woocommerce-anti-fraud' );
			} else {
				$score_color = '#2e7d32';
				$score_label = __( 'Low Risk', 'woocommerce-anti-fraud' );
			}

			// Risk score header
			echo '<div style="margin-bottom:12px;">';
			echo '<span style="font-size:28px;font-weight:700;color:' . esc_attr( $score_color ) . ';">' . esc_html( $score_points ) . '</span>';
			echo '<span style="font-size:13px;color:' . esc_attr( $score_color ) . ';margin-left:4px;">/ 100 &mdash; ' . esc_html( $score_label ) . '</span>';
			echo '</div>';

			// MinFraud warnings (legacy content)
			$json_rule = opmc_hpos_get_post_meta( $order_id, '_wc_af_maxmind_content', true );
			if ( is_array( $json_rule ) && ! empty( $json_rule ) ) {
				echo '<div class="woocommerce-af-risk-maxmind-list">';
				echo '<ul>';
				foreach ( $json_rule as $json_rules ) {
					echo '<li class="failed">' . esc_html( $json_rules[0] ) . '</li>';
				}
				echo '</ul>';
				echo '<a class="woocommerce-af-risk-maxmind-list-toggle" href="#" data-toggle="' . esc_attr__( 'Hide details', 'woocommerce-anti-fraud' ) . '">'
					. esc_html__( 'Show fraud risk details', 'woocommerce-anti-fraud' ) . '</a>';
				echo '</div>';
			}

			// ---------------------------------------------------------------
			// Fraud Signals Panel
			// ---------------------------------------------------------------
			$is_vpn     = opmc_hpos_get_post_meta( $order_id, '_af_mm_is_vpn', true );
			$is_proxy   = opmc_hpos_get_post_meta( $order_id, '_af_mm_is_proxy', true );
			$is_tor     = opmc_hpos_get_post_meta( $order_id, '_af_mm_is_tor', true );
			$is_hosting = opmc_hpos_get_post_meta( $order_id, '_af_mm_is_hosting', true );
			$distance   = opmc_hpos_get_post_meta( $order_id, '_af_mm_ip_distance', true );

			$has_signals = ( '' !== $is_vpn || '' !== $is_proxy || '' !== $is_tor || '' !== $is_hosting );

			if ( $has_signals ) {
				echo '<div class="wc-af-mm-signals-panel">';
				echo '<strong style="display:block;margin-bottom:6px;">' . esc_html__( 'Fraud Signals', 'woocommerce-anti-fraud' ) . '</strong>';
				echo '<table class="wc-af-signals-table">';

				$rows = array(
					__( 'Risk Score', 'woocommerce-anti-fraud' ) => array( $score_points, $score_points > 70 ? 'high' : ( $score_points > 40 ? 'medium' : 'low' ) ),
					__( 'VPN', 'woocommerce-anti-fraud' ) => array( 'yes' === $is_vpn ? __( 'Yes', 'woocommerce-anti-fraud' ) : __( 'No', 'woocommerce-anti-fraud' ), 'yes' === $is_vpn ? 'high' : 'ok' ),
					__( 'Proxy', 'woocommerce-anti-fraud' ) => array( 'yes' === $is_proxy ? __( 'Yes', 'woocommerce-anti-fraud' ) : __( 'No', 'woocommerce-anti-fraud' ), 'yes' === $is_proxy ? 'high' : 'ok' ),
					__( 'TOR', 'woocommerce-anti-fraud' ) => array( 'yes' === $is_tor ? __( 'Yes', 'woocommerce-anti-fraud' ) : __( 'No', 'woocommerce-anti-fraud' ), 'yes' === $is_tor ? 'high' : 'ok' ),
					__( 'Hosting Provider', 'woocommerce-anti-fraud' ) => array( 'yes' === $is_hosting ? __( 'Yes', 'woocommerce-anti-fraud' ) : __( 'No', 'woocommerce-anti-fraud' ), 'yes' === $is_hosting ? 'medium' : 'ok' ),
				);

				if ( '' !== $distance && false !== $distance ) {
					$dist_int  = intval( $distance );
					$dist_tier = $dist_int >= 500 ? 'high' : ( $dist_int >= 50 ? 'medium' : 'ok' );
					$rows[ __( 'Distance', 'woocommerce-anti-fraud' ) ] = array( $dist_int . ' km', $dist_tier );
				}

				$tier_colors = array(
					'high'   => '#D54E21',
					'medium' => '#e6a817',
					'low'    => '#2e7d32',
					'ok'     => '#2e7d32',
				);

				foreach ( $rows as $label => $data ) {
					list( $value, $tier ) = $data;
					$val_color = isset( $tier_colors[ $tier ] ) ? $tier_colors[ $tier ] : '#333';
					$bold      = in_array( $tier, array( 'high', 'medium' ), true ) ? 'font-weight:bold;' : '';
					echo '<tr>';
					echo '<td class="wc-af-signal-label">' . esc_html( $label ) . '</td>';
					echo '<td style="color:' . esc_attr( $val_color ) . ';' . esc_attr( $bold ) . '">' . esc_html( $value ) . '</td>';
					echo '</tr>';
				}

				echo '</table>';
				echo '</div>';
			}

			// Network info from the full signals JSON (network CIDR, etc.)
			$advanced_signals_json = opmc_hpos_get_post_meta( $order_id, '_wc_af_maxmind_advanced_signals', true );
			if ( ! empty( $advanced_signals_json ) ) {
				$signals = json_decode( $advanced_signals_json, true );
				if ( is_array( $signals ) && ! empty( $signals['network'] ) ) {
					echo '<p style="font-size:11px;color:#777;margin-top:8px;">'
						. esc_html__( 'Network:', 'woocommerce-anti-fraud' ) . ' <code>' . esc_html( $signals['network'] ) . '</code></p>';
				}
			}

			?>
			<style type="text/css">
			.woocommerce-af-risk-maxmind-list { margin: 12px 0 0; background: #f8f8f8; border-top: 1px solid #dfdfdf; }
			div#woocommerce-af-maxmind-risk .inside { background: #f8f8f8; }
			.woocommerce-af-risk-maxmind-list ul li.failed:before {
				font-family: WooCommerce; speak: none; font-weight: 400; font-variant: normal;
				text-transform: none; line-height: 1; -webkit-font-smoothing: antialiased;
				margin-right: 7px; content: "\e016";
			}
			.woocommerce-af-risk-maxmind-list ul li.failed { color: #e33800; }
			.wc-af-mm-signals-panel { margin-top: 14px; padding-top: 10px; border-top: 1px solid #dfdfdf; }
			.wc-af-signals-table { width: 100%; border-collapse: collapse; font-size: 12px; }
			.wc-af-signals-table tr td { padding: 3px 0; vertical-align: middle; }
			.wc-af-signal-label { color: #555; width: 110px; }
			</style>
			<?php
		}

	}

}
