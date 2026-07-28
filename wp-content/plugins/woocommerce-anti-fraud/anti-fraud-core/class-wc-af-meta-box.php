<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'WC_AF_Meta_Box' ) ) {

	/**
	 * Class for WC_AF_Meta_Box
	 */
	class WC_AF_Meta_Box {
		/**
		 * Class for construct
		 */
		public function __construct() {

			foreach ( wc_get_order_types( 'order-meta-boxes' ) as $type ) {
				opmc_hpos_add_meta_box( 'woocommerce-af-risk', __( 'Fraud Risk', 'woocommerce-anti-fraud' ), array( $this, 'output' ), $type, 'side', 'high' );
			}
		}

		/**
		 * Output the metabox output
		 *
		 * @since  1.0.0
		 */
		public function output() {

			$is_hpos = get_option( 'woocommerce_custom_orders_table_enabled', true );
			$param   = 'yes' == $is_hpos ? 'id' : 'post';

			if ( ! isset( $_GET[ $param ] ) ) {
				return;
			}

			if ('yes' == $is_hpos) {
				$order_id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
			} else {
				$order_id = isset($_GET['post']) ? sanitize_text_field($_GET['post']) : '';
			}
			$order    = wc_get_order( $order_id );
			

		$score_points     = opmc_hpos_get_post_meta( $order_id, 'wc_af_score', true );
		$whitelist_action = opmc_hpos_get_post_meta( $order_id, 'whitelist_action', true );
		$meta             = WC_AF_Score_Helper::get_score_meta( $score_points, $order_id );

		// ── Order source badge ────────────────────────────────────────────────
		$order_source = WC_AF_Marketplace_Detector::get_saved_source( $order_id );

			if ( ! empty( $order_source ) ) {

				$badge_html = WC_AF_Marketplace_Detector::get_source_badge_html( $order_source );

				echo '<div style="margin-bottom:8px;">' .
				wp_kses_post( $badge_html ) .
				'</div>' . PHP_EOL;
			}

		// ── Marketplace profile applied notice ───────────────────────────────
			if ( WC_AF_Marketplace_Detector::is_enabled() && ! empty( $order_source ) && WC_AF_Marketplace_Detector::SOURCE_NATIVE !== $order_source ) {
				$mp_profile       = WC_AF_Marketplace_Detector::get_effective_profile( $order_source );
				$skipped_rules    = opmc_hpos_get_post_meta( $order_id, '_wc_af_marketplace_skipped_rules', true );

				echo '<div style="background:#e8f4fd; border-left:3px solid #2196f3; padding:8px 10px; margin-bottom:10px; font-size:12px;">' . PHP_EOL;
				echo '<strong style="color:#1565c0;">&#128230; ' . esc_html__( 'Marketplace profile applied', 'woocommerce-anti-fraud' ) . '</strong><br/>' . PHP_EOL;

				if ( $mp_profile && ! empty( $mp_profile['description'] ) ) {
					echo '<span style="color:#555;">' . esc_html( $mp_profile['description'] ) . '</span><br/>' . PHP_EOL;
				}

				if ( ! empty( $skipped_rules ) && is_array( $skipped_rules ) ) {
					echo '<span style="color:#888; font-size:11px;">' . esc_html__( 'Rules skipped:', 'woocommerce-anti-fraud' ) . ' ' . esc_html( implode( ', ', $skipped_rules ) ) . '</span>' . PHP_EOL;
				}

				echo '</div>' . PHP_EOL;
			}
		// ─────────────────────────────────────────────────────────────────────

		// Check if there is an score order.
			if ( '' != $score_points ) {

				// The label.
				echo '<span class="mb-score-label" style="color:' . esc_attr( $meta['color'] ) . '">' . esc_attr( WC_AF_Score_Helper::invert_score( $score_points ) ) . ' % ' . esc_attr__( $meta['label'], 'woocommerce-anti-fraud' ) . '</span>' . PHP_EOL;

				// Circle points.
				$circle_points = WC_AF_Score_Helper::invert_score( $score_points );

				// The circle.
				echo '<input class="knob" data-fgColor="' . esc_attr( $meta['color'] ) . '" data-thickness=".4" data-readOnly=true value="0" rel="' . esc_attr( $circle_points ) . '">';

				// The rules
				$json_rules             = opmc_hpos_get_post_meta( $order_id, 'wc_af_failed_rules', true );
				$whitelisted_actions = [ 'user_email_whitelisted', 'user_ip_whitelisted', 'user_mobile_number' ];
				$whitelist_action_style = in_array( $whitelist_action, $whitelisted_actions ) ? 'style="color:grey"' : '';
				// Failed Rules.
				if ( is_array( $json_rules ) && ! empty( $json_rules ) ) {

					echo '<div class="woocommerce-af-risk-failure-list">' . PHP_EOL;

					echo '<ul>' . PHP_EOL;

					foreach ( $json_rules as $wc_af_failed_rule ) {
						$wc_af_failed_rule_decode = json_decode( $wc_af_failed_rule, true );
						if ( 'whitelist' == $wc_af_failed_rule_decode['id'] ) {
							echo '<li class="failed" ' . esc_attr( $whitelist_action_style ) . '>' . esc_attr__( $wc_af_failed_rule_decode['label'], 'woocommerce-anti-fraud' ) . '</li>' . PHP_EOL;
						}
					}

					// Calculate total for display
					$total_risk_points = 0;
					foreach ( $json_rules as $json_rule ) {
						$rule_data = json_decode( $json_rule, true );
						if ( isset( $rule_data['risk_points'] ) ) {
							$total_risk_points += intval( $rule_data['risk_points'] );
						}
					}
				
					foreach ( $json_rules as $json_rule ) {

						$rule = WC_AF_Rules::get()->get_rule_from_json( $json_rule );
						if ( ! is_a( $rule, 'WC_AF_Rule' ) ) {
							continue;
						}
					
						$rule_data = json_decode( $json_rule, true );
						$detailed_explanation = isset( $rule_data['detailed_explanation'] ) ? $rule_data['detailed_explanation'] : array();
						$alert_type = isset( $detailed_explanation['alert_type'] ) ? $detailed_explanation['alert_type'] : 'warning';
						$risk_points = isset( $rule_data['risk_points'] ) ? intval( $rule_data['risk_points'] ) : 0;
					
						// Set icon based on alert type
						$type_icons = array(
						'critical' => '🔴',
						'warning' => '⚠️',
						'info' => 'ℹ️',
						);
						$icon = isset( $type_icons[ $alert_type ] ) ? $type_icons[ $alert_type ] : '⚠️';
					
						// Calculate percentage contribution
						$contribution_percentage = 0;
						if ( $total_risk_points > 0 && $risk_points > 0 ) {
							$contribution_percentage = round( ( $risk_points / $total_risk_points ) * 100, 1 );
						}
					
						// Main alert with icon and percentage
						echo '<li class="failed" style="margin-bottom: 10px; padding: 8px; background: #fff; border-left: 3px solid #ddd;" ' . esc_attr( $whitelist_action_style ) . '>';
						echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">';
						echo '<span style="margin-right: 5px;">' . esc_html( $icon ) . '</span>';
						echo esc_html( $rule->get_label() ) . '</span>';
						if ( $contribution_percentage > 0 ) {
							echo ' <span style="color: #dc3545; font-weight: 600; font-size: 12px;">' . esc_html( $contribution_percentage ) . '%</span>';
						}
						echo '</div>';
					
						// Show compact explanation if available
						if ( ! empty( $detailed_explanation ) ) {
						
							// Show reason in simple language
							if ( isset( $detailed_explanation['reason'] ) ) {
								echo '<div style="font-size: 12px; color: #555; margin-bottom: 5px;">' . esc_html( $detailed_explanation['reason'] ) . '</div>';
							}
						
							// Show key data points in a cleaner way
							if ( isset( $detailed_explanation['data_evaluated'] ) && is_array( $detailed_explanation['data_evaluated'] ) ) {
								echo '<div style="font-size: 11px; color: #888; padding: 5px 0; border-top: 1px solid #eee;">';
								foreach ( $detailed_explanation['data_evaluated'] as $key => $value ) {
									// Convert technical terms to user-friendly labels
									$friendly_labels = array(
									'billing_phone' => 'Phone',
									'billing_phone_normalized' => 'Phone Format',
									'billing_country' => 'Country',
									'detected_country_code' => 'Detected Code',
									'detected_country' => 'Detected Country',
									'expected_country_code' => 'Expected Code',
									'expected_country' => 'Expected Country',
									'customer_ip' => 'IP Address',
									'billing_city' => 'City',
									'billing_state' => 'State',
									);
									$label = isset( $friendly_labels[ $key ] ) ? $friendly_labels[ $key ] : ucwords( str_replace( '_', ' ', $key ) );
									echo '<div style="margin: 2px 0;"><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</div>';
								}
								echo '</div>';
							}
						
							// Show phone interpretation if available
							if ( isset( $detailed_explanation['phone_interpretation'] ) && is_array( $detailed_explanation['phone_interpretation'] ) ) {
								echo '<div style="font-size: 11px; color: #888; padding: 5px 0; margin-top: 5px; border-top: 1px solid #eee;">';
								echo '<strong style="color: #666;">Phone Details:</strong><br>';
								foreach ( $detailed_explanation['phone_interpretation'] as $key => $value ) {
									$friendly_labels = array(
									'detected_country_code' => 'Detected Code',
									'detected_country' => 'Detected From',
									'expected_country_code' => 'Expected Code',
									'expected_country' => 'Expected From',
									);
									$label = isset( $friendly_labels[ $key ] ) ? $friendly_labels[ $key ] : ucwords( str_replace( '_', ' ', $key ) );
									echo '<div style="margin: 2px 0;"><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</div>';
								}
								echo '</div>';
							}
						}
					
						echo '</li>' . PHP_EOL;
					}

					echo '</ul>' . PHP_EOL;
					// echo '<p><a href="#" data_id='.$order_id.' class="button button-primary test-fraud">' . __( 'Ajax Fraud Risk', 'woocommerce-anti-fraud' ) . '</a></p>' . PHP_EOL;.
					echo '<a class="woocommerce-af-risk-failure-list-toggle" href="#" data-toggle="' . esc_html__( 'Hide details', 'woocommerce-anti-fraud' ) . '">' . esc_html__( 'Show fraud risk details', 'woocommerce-anti-fraud' ) . '</a>' . PHP_EOL;

					echo '</div>' . PHP_EOL;
				}

				// IP Multiple Order Details Explainability (with toggle)
				$this->display_ip_multiple_explainability( $order_id );

				// Blacklist actions section
				echo '<div class="woocommerce-af-blacklist-actions" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">' . PHP_EOL;
				
				$blacklist_nonce = wp_create_nonce( 'wc_af_blacklist_nonce' );
				$billing_email = $order->get_billing_email();
				$ip_address = opmc_hpos_get_post_meta( $order_id, '_customer_ip_address', true );
				
				// Only show if IP address exists
				if ( empty( $ip_address ) ) {
					$ip_address = opmc_hpos_get_post_meta( $order_id, '_wc_af_ip_address', true );
				}

				echo '<p style="margin-bottom: 8px; font-weight: 600;">' . esc_html__( 'Quick actions', 'woocommerce-anti-fraud' ) . '</p>';
				
				// Email blacklist button
				if ( ! empty( $billing_email ) && is_email( $billing_email ) ) {
					echo '<button type="button" class="button button-secondary wc-af-blacklist-email-btn" data-order-id="' . esc_attr( $order_id ) . '" data-wpnonce="' . esc_attr( $blacklist_nonce ) . '" style="width: 100%; margin-bottom: 5px;">';
					echo '<span class="dashicons dashicons-dismiss" style="vertical-align: middle;"></span> ';
					echo esc_html__( 'Block email', 'woocommerce-anti-fraud' );
					echo '</button>' . PHP_EOL;
				}
				
				// IP blacklist button
				if ( ! empty( $ip_address ) ) {
					echo '<button type="button" class="button button-secondary wc-af-blacklist-ip-btn" data-order-id="' . esc_attr( $order_id ) . '" data-wpnonce="' . esc_attr( $blacklist_nonce ) . '" style="width: 100%;">';
					echo '<span class="dashicons dashicons-dismiss" style="vertical-align: middle;"></span> ';
					echo esc_html__( 'Block IP', 'woocommerce-anti-fraud' );
					echo '</button>' . PHP_EOL;
				}
				
				// Message container
				echo '<div class="wc-af-blacklist-message" style="margin-top: 10px;"></div>' . PHP_EOL;
				
				echo '</div>' . PHP_EOL;

			} else {

				// Get order.
				$order = wc_get_order( $order_id );

				// Check if we need to schedule an order.
				if ( isset( $_GET['schedule_anti_fraud'] ) && ! WC_AF_Score_Helper::is_fraud_check_queued( $order_id ) ) {

					// Schedule fraud check.
					$score_helper = new WC_AF_Score_Helper();
					$score_helper->schedule_fraud_check( $order_id );

					// Refetch order.
					$order = wc_get_order( $order_id );
				}

				if ( isset( $_GET['cancel_schedule_anti_fraud'] ) && WC_AF_Score_Helper::is_fraud_check_queued( $order_id ) ) {

					// Schedule fraud check.
					$score_helper = new WC_AF_Score_Helper();
					$score_helper->cancel_schedule_fraud_check( $order_id );

					// Refetch order.
					$order = wc_get_order( $order_id );
				}

				// Check if we're currently waiting for an audit.
				if ( WC_AF_Score_Helper::is_fraud_check_queued( $order_id ) ) {

					echo '<p>' . esc_html__( 'This order is queued for a fraud check.', 'woocommerce-anti-fraud' ) . '</p>' . PHP_EOL;
					echo '<p><a href="' . esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit&cancel_schedule_anti_fraud=1' ) ) . '" class="button button-danger">' . esc_html__( 'Cancel queued fraud check', 'woocommerce-anti-fraud' ) . '</a></p>' . PHP_EOL;

				} else {
					// No score found and order not scheduled for fraud check.
					echo '<p>' . esc_attr( $meta['label'], 'woocommerce-anti-fraud' ) . '</p>' . PHP_EOL;
					echo '<p><a href="' . esc_url( admin_url( 'post.php?post=' . sanitize_text_field( $order_id ) . '&action=edit&schedule_anti_fraud=1' ) ) . '" class="button button-primary">' . esc_html__( 'Calculate fraud risk', 'woocommerce-anti-fraud' ) . '</a></p>' . PHP_EOL;
				}
			}


			$action_style = 'background: #e2ffe3; margin: 6px !important; text-align: left; padding: 8px; border-radius: 5px; border: 1px solid #a1e1a3;';
			$action_text  = '';

			switch ( $whitelist_action ) {
				case 'user_email_whitelisted':
					$action_text = esc_html__( 'Fraud checks skipped: billing email is on the allow list.', 'woocommerce-anti-fraud' );
					break;

				case 'user_ip_whitelisted':
					$action_text = esc_html__( 'Fraud checks skipped: IP address is on the allow list.', 'woocommerce-anti-fraud' );
					break;
				case 'user_mobile_number':
					$action_text = esc_html__( 'Fraud checks skipped: phone number is on the allow list.', 'woocommerce-anti-fraud' );
					break;
			}
			if ( ! empty( $action_text ) ) {
				printf( '<p style="%s">%s</p>', esc_attr( $action_style ), esc_html( $action_text ) );
			}
		}

		/**
		 * Display IP multiple order details explainability with toggle
		 *
		 * @param int $order_id The order ID
		 * @since 7.1.9
		 */
		private function display_ip_multiple_explainability( $order_id ) {
			$ip_multiple_data = opmc_hpos_get_post_meta( $order_id, '_wc_af_ip_multiple_data', true );
			
			if ( empty( $ip_multiple_data ) || ! is_array( $ip_multiple_data ) ) {
				return;
			}

			echo '<div class="wc-af-ip-multiple-explainability" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">' . PHP_EOL;
			
			echo '<div class="wc-af-ip-multiple-details">' . PHP_EOL;
			echo '<h4 style="margin-top: 0; margin-bottom: 10px; font-size: 13px;">' . esc_html__( 'Multiple orders from this IP', 'woocommerce-anti-fraud' ) . '</h4>' . PHP_EOL;

			echo '<div class="wc-af-ip-multiple-content" style="margin-bottom: 15px; padding: 10px; background: #fff3cd; border-left: 3px solid #ffc107;">' . PHP_EOL;
			
			// Header with IP
			echo '<div style="margin-bottom: 8px;">' . PHP_EOL;
			echo '<strong>' . esc_html__( 'IP Address', 'woocommerce-anti-fraud' ) . ':</strong> ';
			echo '<code style="background: #fff; padding: 2px 6px; font-size: 12px;">' . esc_html( $ip_multiple_data['ip'] ) . '</code>' . PHP_EOL;
			echo '</div>' . PHP_EOL;

			// Reason
			if ( ! empty( $ip_multiple_data['reason'] ) ) {
				echo '<div style="margin-bottom: 8px; font-size: 12px; color: #856404;">' . PHP_EOL;
				echo '<span class="dashicons dashicons-warning" style="font-size: 14px; vertical-align: middle;"></span> ';
				echo '<strong>' . esc_html( $ip_multiple_data['reason'] ) . '</strong>' . PHP_EOL;
				echo '</div>' . PHP_EOL;
			}

			// Time window and count
			echo '<div style="margin-bottom: 8px; font-size: 12px; color: #666;">' . PHP_EOL;
			echo '<span class="dashicons dashicons-clock" style="font-size: 14px; vertical-align: middle;"></span> ';
			echo sprintf( 
				 /* translators: 1: Number of matching orders, 2: Time window (e.g. 24 hours). */
				esc_html__( '%1$s matching orders in the last %2$s', 'woocommerce-anti-fraud' ),
				'<strong>' . esc_html( $ip_multiple_data['count'] ) . '</strong>',
				'<strong>' . esc_html( $ip_multiple_data['time_window'] ) . '</strong>'
			);
			echo '</div>' . PHP_EOL;

			// Evaluation period
			if ( ! empty( $ip_multiple_data['start_date'] ) && ! empty( $ip_multiple_data['end_date'] ) ) {
				echo '<div style="margin-bottom: 8px; font-size: 11px; color: #999;">' . PHP_EOL;
				echo sprintf( 
					 /* translators: 1: start_date, 2: end_date. */
					esc_html__( 'Evaluated from %1$s to %2$s', 'woocommerce-anti-fraud' ),
					'<em>' . esc_html( $ip_multiple_data['start_date'] ) . '</em>',
					'<em>' . esc_html( $ip_multiple_data['end_date'] ) . '</em>'
				);
				echo '</div>' . PHP_EOL;
			}

			// Matching orders list
			if ( ! empty( $ip_multiple_data['matching_orders'] ) && is_array( $ip_multiple_data['matching_orders'] ) ) {
				echo '<div style="margin-top: 10px;">' . PHP_EOL;
				echo '<strong style="font-size: 12px;">' . esc_html__( 'Orders with different details', 'woocommerce-anti-fraud' ) . '</strong>' . PHP_EOL;
				echo '<ul style="margin: 5px 0 0 0; padding-left: 20px; font-size: 12px;">' . PHP_EOL;
				
				foreach ( $ip_multiple_data['matching_orders'] as $matched_order ) {
					$order_id_matched = isset( $matched_order['id'] ) ? $matched_order['id'] : 0;
					$order_date = isset( $matched_order['date'] ) ? $matched_order['date'] : '';

					if ( ! $order_id_matched ) {
						continue;
					}

					// Determine order edit URL based on HPOS
					$is_hpos = get_option( 'woocommerce_custom_orders_table_enabled', true );
					if ( 'yes' == $is_hpos ) {
						$edit_url = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id_matched );
					} else {
						$edit_url = admin_url( 'post.php?post=' . $order_id_matched . '&action=edit' );
					}

					echo '<li style="margin-bottom: 5px;">' . PHP_EOL;
					echo '<a href="' . esc_url( $edit_url ) . '" target="_blank" style="text-decoration: none;">';
					echo '<strong>#' . esc_html( $order_id_matched ) . '</strong>';
					echo '</a>';
					
					if ( $order_date ) {
						echo ' <span style="color: #666;">(' . esc_html( $order_date ) . ')</span>';
					}
					
					echo '</li>' . PHP_EOL;
				}
				
				echo '</ul>' . PHP_EOL;
				echo '</div>' . PHP_EOL;
			}

			echo '</div>' . PHP_EOL;
			echo '</div>' . PHP_EOL;
			
			// Toggle button
			echo '<a class="wc-af-ip-multiple-toggle" href="#" data-toggle="' . esc_html__( 'Hide details', 'woocommerce-anti-fraud' ) . '" style="display: inline-block; margin-top: 5px; font-size: 12px;">' . esc_html__( 'Show details', 'woocommerce-anti-fraud' ) . '</a>' . PHP_EOL;
			
			echo '</div>' . PHP_EOL;
		}

	}

}
