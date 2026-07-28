<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'WC_AF_Ai_Fraud_Prevention_Meta_Box' ) ) {

	/**
	 * Class for WC_AF_Meta_Box
	 */
	class WC_AF_Ai_Fraud_Prevention_Meta_Box {
		/**
		 * Class for construct
		 */
		public function __construct() {

			foreach ( wc_get_order_types( 'custom-order-meta-box' ) as $type ) {
				opmc_hpos_add_meta_box( 'woocommerce-af-ai-fraud-prevention', __( 'AI fraud risk', 'woocommerce-anti-fraud' ), array( $this, 'aIFraudPreventionScore' ), $type, 'side', 'high' );
			}
		}

		/**
		 * Output the metabox output
		 *
		 * @since  1.0.0
		 */
		public function aIFraudPreventionScore() {

			$hposSettingsEnabled = get_option('woocommerce_custom_orders_table_enabled', true);
			
			if ('yes' != $hposSettingsEnabled ) {
				// Post get must be set.
				if ( ! isset( $_GET['post'] ) ) {
					return;
				}
			} else {
				// Post get must be set.
				if ( ! isset( $_GET['id'] ) ) {
					return;
				}
			}

			if ('yes' != $hposSettingsEnabled ) {

				$order_id = sanitize_text_field( $_GET['post'] );

			} else {

				$order_id = sanitize_text_field( $_GET['id'] );

			}
			// Create Score object and calculate score.
			$score_points = opmc_hpos_get_post_meta( $order_id, '_ai_risk_score', true );

			$low_risk = get_option('wc_settings_anti_fraud_low_risk_threshold', true);
			$high_risk = get_option('wc_settings_anti_fraud_higher_risk_threshold', true);
			if ($score_points > $high_risk) {
				$color = '#D54E21';
				//$label = "High";
			} elseif ($score_points > $low_risk && $score_points < $high_risk) {
				$color = '#FFAE00';
				//$label = "Medium";

			} else {
				$color = 'green';
				//$label = "Low";

			}

			// Check if there is an score order.
			if ( '' != $score_points ) {

				// The rules
				
				
				// The label.
				echo '<span class="mb-score-label" style="color:' . esc_attr( $color ) . '; font-size:20px">' . esc_attr( $score_points ) . ' % </span>' . PHP_EOL;

				// Circle points.
				$circle_points = $score_points;
			

				// The rules
				$json_rule = opmc_hpos_get_post_meta( $order_id, '_ai_explanations', true );
				//$json_rules = json_decode( $json_rule, true );
				//$json_rules = explode(',', $json_rules);

				 //echo '<pre>'; print_r($json_rule); echo '</pre>';

				
				if ( is_array( $json_rule ) && ! empty( $json_rule ) ) {

					echo '<div class="woocommerce-af-risk-ai-fraud-list">' . PHP_EOL;

					echo '<ul>' . PHP_EOL;

					
					$whitelist_action_style = 'red';
					
					foreach ( $json_rule as $json_rules ) {

						//print_r($json_rule);
						echo '<li class="failed" ' . esc_attr( $whitelist_action_style ) . '>' . esc_attr( $json_rules ) . '</li>' . PHP_EOL;
					}

					echo '</ul>' . PHP_EOL;
					
					echo '<a class="woocommerce-af-risk-ai-fraud-list-toggle" href="#" data-toggle="' . esc_html__( 'Hide details', 'woocommerce-anti-fraud' ) . '">' . esc_html__( 'Show fraud risk details', 'woocommerce-anti-fraud' ) . '</a>' . PHP_EOL;

					echo '</div>' . PHP_EOL;

					?>

				<style type="text/css">
					.woocommerce-af-risk-ai-fraud-list{
					margin: 20px 0 0;
					background: #f8f8f8;
					border-top: 1px solid #dfdfdf;
				}
				div#woocommerce-af-ai-fraud-risk .inside {
					background: #f8f8f8;
				}

				.woocommerce-af-risk-ai-fraud-list ul li.failed:before {
					font-family: WooCommerce;
					speak: none;
					font-weight: 400;
					font-variant: normal;
					text-transform: none;
					line-height: 1;
					-webkit-font-smoothing: antialiased;
					margin-right: 7px;
					content: "\e016";
				}

				.woocommerce-af-risk-ai-fraud-list ul li.failed {
					color: #e33800;
				}
				</style>

					<?php
				} 
			} 

		}

	}

}
