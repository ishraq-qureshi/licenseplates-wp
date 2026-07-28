<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_AF_Settings' ) ) :

	function wc_af_add_settings( $settings ) {
		/**
		 * Settings class
		 *
		 * @since 1.0.0
		 */
		class WC_AF_Settings extends WC_Settings_Page {

			/**
			 * The request response
			 *
			 * @var array
			 */
			private $response = null;

			/**
			 * The request log
			 *
			 * @var array
			 */
			private $log = null;

			/**
			 * The error message
			 *
			 * @var string
			 */
			private $error_message = '';

			/**
			 * Ensure custom admin field render hooks are registered once.
			 *
			 * @var bool
			 */
			private static $custom_admin_field_hooks_registered = false;

			/**
			 * Setup settings class
			 *
			 * @since  1.0
			 */
						const SETTINGS_NAMESPACE = 'anti_fraud';

			public function __construct() {
				$this->id    = 'wc_af';
				$this->label = __( 'Anti-Fraud', 'woocommerce-anti-fraud' );

				add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
				add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
				add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
				add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
				add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'Authorized_Minfraud' ) );
				add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'Authorized_Quickemailverification' ) );
				//ChatGPT settings validation
				add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'validate_ai_api_key_on_save' ) );

				add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'updateBulkTextariaTagData' ) );
				add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'Authorized_bigdatacloud' ) );
				// PLUGINS-2675	
//				add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'Authorized_reCaptcha' ) );
				if ( ! self::$custom_admin_field_hooks_registered ) {
					// Keep only the generic "section" field renderer on WooCommerce's hook.
					// Specialized card-attack section renderers are called explicitly by template
					// code where needed; hooking them globally causes duplicated/invalid markup.
					add_action( 'woocommerce_admin_field_section', array( $this, 'opmc_add_admin_field_section' ) );
					add_action( 'woocommerce_admin_field_button', array( $this, 'opmc_add_admin_field_button' ) );
					add_action( 'woocommerce_admin_field_help_cards', array( $this, 'render_help_cards_field' ) );
					add_action( 'woocommerce_admin_field_timepicker', array( $this, 'opmc_add_admin_field_timepicker' ) );
					add_action( 'woocommerce_admin_field_marketplace_test_tool', array( $this, 'render_marketplace_test_tool' ) );
					self::$custom_admin_field_hooks_registered = true;
				}
				add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'AuthorizedTrustSwiftly' ) );
				add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'wc_af_sync_recaptcha_to_paypal_acp' ) );

				add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'paypal_option_enable_message' ) );
				add_action( 'admin_notices', array( $this, 'paypal_setting_notification' ) );

				//add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'get_all_failed_orders' ) );

			// Marketplace test tool renderer hook is registered above with
			// other custom admin-field renderers and guarded against duplicates.

				/* initiation of logging instance */
				$this->log = new WC_Logger();
			}



			/**
			 * Render the interactive test-tool panel inside the Marketplace Orders settings page.
			 *
			 * @since 7.3.0
			 */
			public function render_marketplace_test_tool() {

				$config = array(
					'ajax_url'    => admin_url( 'admin-ajax.php' ),
					'nonce'       => wp_create_nonce( 'wc_af_test_marketplace_nonce' ),
					'is_hpos'     => get_option( 'woocommerce_custom_orders_table_enabled', 'no' ),
					'i18n_view'   => __( 'View order & fraud risk', 'woocommerce-anti-fraud' ),
					'i18n_delete' => __( 'Delete test order', 'woocommerce-anti-fraud' ),
					'i18n_fail'   => __( 'Request failed. Please try again.', 'woocommerce-anti-fraud' ),
				);

				$i18n_title = __( 'Test Marketplace Detection', 'woocommerce-anti-fraud' );
				$i18n_desc  = __( 'Creates a test order with marketplace metadata, runs the risk check, and shows what was detected. You get a link to view or delete the test order.', 'woocommerce-anti-fraud' );
				$i18n_from  = __( 'Simulate channel:', 'woocommerce-anti-fraud' );
				$i18n_btn   = __( 'Create test order & run check', 'woocommerce-anti-fraud' );

				echo '<tr valign="top"><td colspan="2" style="padding:0;">';
				echo '<div id="wc-af-marketplace-test-tool" style="background:#f9f9f9;border:1px solid #ddd;border-radius:4px;padding:20px 24px;margin:10px 0;">';

				echo '<h3 style="margin:0 0 6px;font-size:14px;">&#129514; ' . esc_html( $i18n_title ) . '</h3>';
				echo '<p style="color:#666;margin:0 0 16px;font-size:13px;">' . esc_html( $i18n_desc ) . '</p>';

				echo '<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">';

				echo '<label for="wc-af-test-source" style="font-weight:600;font-size:13px;">' . esc_html( $i18n_from ) . '</label>';

				echo '<select id="wc-af-test-source" style="height:34px;font-size:13px;">';
				echo '<option value="ebay">&#128721; eBay</option>';
				echo '<option value="amazon">&#128230; Amazon</option>';
				echo '<option value="etsy">&#127912; Etsy</option>';
				echo '<option value="unknown_import">&#128442; Unknown API Import</option>';
				echo '<option value="native">&#127978; Native WooCommerce Checkout (control)</option>';
				echo '</select>';

				echo '<button type="button" id="wc-af-run-test" class="button button-primary" style="height:34px;">' . esc_html( $i18n_btn ) . '</button>';

				echo '<span id="wc-af-test-spinner" style="display:none;"><span class="spinner is-active" style="float:none;margin:0;"></span></span>';

				echo '</div>';

				echo '<div id="wc-af-test-result" style="display:none;margin-top:18px;padding:14px 16px;border-radius:4px;font-size:13px;line-height:1.6;"></div>';

				echo '</div></td></tr>';

				// Inline script.
				echo '<script type="text/javascript">' . "\n";
				echo '(function($){' . "\n";

				// Safe JSON output.
				echo 'var wc_af_tc = ' . wp_json_encode( $config ) . ';' . "\n";

				echo 'function wc_af_run_test(){' . "\n";
				echo '  var src=$("#wc-af-test-source").val();' . "\n";
				echo '  var $r=$("#wc-af-test-result"),$s=$("#wc-af-test-spinner");' . "\n";
				echo '  $("#wc-af-run-test").prop("disabled",true);$s.show();$r.hide();' . "\n";

				echo '  $.post(wc_af_tc.ajax_url,{action:"wc_af_test_marketplace_detection",source:src,_wpnonce:wc_af_tc.nonce,is_hpos:wc_af_tc.is_hpos},' . "\n";

				echo '    function(resp){' . "\n";
				echo '      $("#wc-af-run-test").prop("disabled",false);$s.hide();' . "\n";

				echo '      if(!resp.success){' . "\n";
				echo '        $r.css({background:"#fde8e8",border:"1px solid #f5c6c6",color:"#c0392b"})' . "\n";
				echo '          .html("&#10060; "+(resp.data?resp.data.message:"Error")).show();' . "\n";
				echo '        return;' . "\n";
				echo '      }' . "\n";

				echo '      var d=resp.data;' . "\n";
				echo '      var h="<strong style=\"font-size:14px;\">"+d.icon+" "+d.title+"</strong><br/><br/>";' . "\n";

				echo '      h+="<table style=\"border-collapse:collapse;width:100%;max-width:580px;font-size:12px;\">";' . "\n";
				echo '      d.rows.forEach(function(r){' . "\n";
				echo '        h+="<tr><td style=\"padding:4px 10px 4px 0;color:#555;white-space:nowrap;width:200px;\">"+r[0]+"</td><td style=\"padding:4px 0;font-weight:600;\">"+r[1]+"</td></tr>";' . "\n";
				echo '      });' . "\n";
				echo '      h+="</table>";' . "\n";

				echo '      if(d.order_url){' . "\n";
				echo '        h+=\'<br/><a href="\'+d.order_url+\'" target="_blank" class="button button-secondary" style="font-size:12px;">&#128269; \'+wc_af_tc.i18n_view+\'</a>\';' . "\n";
				echo '      }' . "\n";

				echo '      if(d.order_id&&d.delete_nonce){' . "\n";
				echo '        h+=\'&nbsp;<button type="button" class="button wc-af-delete-test-order" style="font-size:12px;color:#999;" data-id="\'+d.order_id+\'" data-nonce="\'+d.delete_nonce+\'">&#128465; \'+wc_af_tc.i18n_delete+\'</button>\';' . "\n";
				echo '      }' . "\n";

				echo '      var bc=d.passed?"#eaf7ec":"#fff8e1";' . "\n";
				echo '      var bd=d.passed?"#b2dfdb":"#ffe082";' . "\n";
				echo '      var tc=d.passed?"#1b5e20":"#5d4037";' . "\n";

				echo '      $r.css({background:bc,border:"1px solid "+bd,color:tc}).html(h).show();' . "\n";
				echo '    },"json")' . "\n";

				echo '    .fail(function(){' . "\n";
				echo '      $("#wc-af-run-test").prop("disabled",false);$s.hide();' . "\n";
				echo '      $r.css({background:"#fde8e8",border:"1px solid #f5c6c6",color:"#c0392b"})' . "\n";
				echo '        .html("&#10060; "+wc_af_tc.i18n_fail).show();' . "\n";
				echo '    });' . "\n";

				echo '}' . "\n";

				// Delete handler.
				echo '$(document).on("click",".wc-af-delete-test-order",function(){' . "\n";
				echo '  var $btn=$(this),oid=$btn.data("id"),nnc=$btn.data("nonce");' . "\n";
				echo '  if(!confirm("Delete test order #"+oid+"?")){return;}' . "\n";

				echo '  $btn.prop("disabled",true).text("Deleting...");' . "\n";

				echo '  $.post(wc_af_tc.ajax_url,{action:"wc_af_delete_test_order",order_id:oid,_wpnonce:nnc},function(resp){' . "\n";

				echo '    if(resp.success){' . "\n";
				echo '      $btn.closest("#wc-af-test-result").find("a.button-secondary").remove();' . "\n";
				echo '      $btn.replaceWith(\'<em style="color:#888;font-size:12px;">&#10003; Test order deleted</em>\');' . "\n";
				echo '    }else{' . "\n";
				echo '      alert("Delete failed: "+(resp.data?resp.data.message:"Unknown error"));' . "\n";
				echo '      $btn.prop("disabled",false).text("&#128465; "+wc_af_tc.i18n_delete);' . "\n";
				echo '    }' . "\n";

				echo '  },"json");' . "\n";
				echo '});' . "\n";

				echo '$("#wc-af-run-test").on("click",wc_af_run_test);' . "\n";

				echo '})(jQuery);' . "\n";
				echo '</script>' . "\n";
			}

			public function wc_af_sync_recaptcha_to_paypal_acp() {
				global $current_section;
				$get_settings = $this->get_settings( $current_section );

				if ( isset( $get_settings ) ) {
					$curr_settings = isset( $get_settings[0]['id'] ) ? $get_settings[0]['id'] : '';

					if ( 'wc_af_recaptch_settings' === $curr_settings ) {

						$recaptcha_enabled = get_option( 'wc_af_recaptcha_enable_captcha', 'no' );
						$recaptcha_type    = get_option( 'wc_af_recaptcha_type', 'google_recaptcha' );

						$paypal_used = $this->check_paypal_plugin_save();
						// ✅ 4. Update setting only if PayPal plugin active.
						if ( $paypal_used && 'yes' === $recaptcha_enabled && 'google_recaptcha' === $recaptcha_type ) {
							update_option( 'wc_af_paypal_acp_enabled', 'yes' );
						} else {
							update_option( 'wc_af_paypal_acp_enabled', 'no' );
						}
					}
				}
			}


			public function check_paypal_plugin_save() {
				// Get all active plugins
				$active_plugins = get_option('active_plugins'); 

				if (empty($active_plugins)) {
					return false;
				}

				// Ensure function is available
				if (!function_exists('get_plugin_data')) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				foreach ($active_plugins as $plugin_file) {
					$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
					$plugin_data = get_plugin_data($plugin_path);

					$plugin_name        = $plugin_data['Name'];         
					$plugin_description = $plugin_data['Description']; 

					if (stripos($plugin_name, 'paypal') !== false || stripos($plugin_description, 'paypal') !== false && 'WooCommerce' !== $plugin_name) {
						// Found PayPal plugin
						return true;
					}
				}

				// Not found
				return false;
			}

			
			/**
			 * Get sections
			 *
			 * @return array
			 */
			public function settingsSavedManually() {
				update_option( 'wc_af_is_settings_saved_manually', true );
				delete_option( 'wc_af_first_installation' );
			}

			/**
			 * Get sections
			 *
			 * @return array
			 */
			public function get_sections() {

			// Order matches merchant task flow; keys are stable for URLs and save handlers.
			$sections = array(
				''                           => __( 'Overview', 'woocommerce-anti-fraud' ),
				'general'                    => __( 'Core protection', 'woocommerce-anti-fraud' ),
				'rules'                      => __( 'Fraud rules', 'woocommerce-anti-fraud' ),
				'card_attacks'               => __( 'Card testing', 'woocommerce-anti-fraud' ),
				'cleanup'                    => __( 'Failed orders & cleanup', 'woocommerce-anti-fraud' ),
				'marketplace_orders'         => __( 'Marketplace orders', 'woocommerce-anti-fraud' ),
				'email_alert'                => __( 'Email alerts', 'woocommerce-anti-fraud' ),
				'white_list'                 => __( 'Allow list', 'woocommerce-anti-fraud' ),
				'black_list'                 => __( 'Block list', 'woocommerce-anti-fraud' ),
				'recaptcha_settings'         => __( 'Checkout CAPTCHA', 'woocommerce-anti-fraud' ),
				'paypal_settings'            => __( 'PayPal & Checkout CAPTCHA', 'woocommerce-anti-fraud' ),
				'minfraud_settings'          => __( 'MaxMind · Score', 'woocommerce-anti-fraud' ),
				'minfraud_insights_settings' => __( 'MaxMind · Insights', 'woocommerce-anti-fraud' ),
				'minfraud_factors_settings'  => __( 'MaxMind · Factors', 'woocommerce-anti-fraud' ),
				'minfraud_signals_settings' => __( 'MaxMind · Advanced Signals', 'woocommerce-anti-fraud' ),
				'trust_swiftly_settings'     => __( 'Trust Swiftly', 'woocommerce-anti-fraud' ),
				'ai_fraud_prevention'        => __( 'AI fraud signals', 'woocommerce-anti-fraud' ),
				'chargeback_settings'        => __( 'Chargeback programs', 'woocommerce-anti-fraud' ),
				'need_support'               => __( 'Help & support', 'woocommerce-anti-fraud' ),
			);

				/**
				 * Wc_sections for admin settings
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
			}

			/**
			 * Primary navigation groups (two-tier: primary tabs + contextual secondary links).
			 * Section keys must match {@see get_sections()}; URLs and save handlers rely on stable slugs.
			 *
			 * @since 7.2.6
			 * @since 7.4.0 Restructured for primary/secondary navigation (replaces flat column dump).
			 *
			 * @return array<int, array{ id: string, title: string, sections: string[] }>
			 */
			protected function get_navigation_groups() {
				$groups = array(
					array(
						'id'       => 'overview',
						'title'    => __( 'Your store protection', 'woocommerce-anti-fraud' ),
						'sections' => array( '' ),
					),
					array(
						'id'       => 'protection',
						'title'    => __( 'Essential protection', 'woocommerce-anti-fraud' ),
						'sections' => array( 'general', 'rules' ),
					),
					array(
						'id'       => 'card_attacks',
						'title'    => __( 'Card testing protection', 'woocommerce-anti-fraud' ),
						'sections' => array( 'card_attacks' ),
					),
					array(
						'id'       => 'orders_alerts',
						'title'    => __( 'Order review & alerts', 'woocommerce-anti-fraud' ),
						'sections' => array( 'cleanup', 'marketplace_orders', 'email_alert' ),
					),
					array(
						'id'       => 'lists',
						'title'    => __( 'Trusted & blocked customers', 'woocommerce-anti-fraud' ),
						'sections' => array( 'white_list', 'black_list' ),
					),
					array(
						'id'       => 'integrations',
						'title'    => __( 'Extra protection tools', 'woocommerce-anti-fraud' ),
						'sections' => array(
							'recaptcha_settings',
							'paypal_settings',
							'minfraud_settings',
							'minfraud_insights_settings',
							'minfraud_factors_settings',
							'minfraud_signals_settings',
							'trust_swiftly_settings',
						),
					),
					array(
						'id'       => 'advanced',
						'title'    => __( 'Advanced controls', 'woocommerce-anti-fraud' ),
						'sections' => array( 'ai_fraud_prevention', 'chargeback_settings' ),
					),
					array(
						'id'       => 'support',
						'title'    => __( 'Help', 'woocommerce-anti-fraud' ),
						'sections' => array( 'need_support' ),
					),
				);

				/**
				 * Filter primary navigation groups for Anti-Fraud settings.
				 *
				 * @since 7.4.0
				 *
				 * @param array          $groups Groups with id, title, and ordered section ids.
				 * @param WC_AF_Settings $this   Settings instance.
				 */
				return apply_filters( 'wc_af_settings_navigation_groups', $groups, $this );
			}

			/**
			 * Append ungrouped sections (e.g. from filters) as a final “More” primary tab.
			 *
			 * @param array $groups   Navigation groups.
			 * @param array $sections All section keys from {@see get_sections()}.
			 * @return array
			 */
			protected function append_orphan_sections_to_navigation( $groups, $sections ) {
				$seen = array();
				foreach ( $groups as $group ) {
					if ( empty( $group['sections'] ) ) {
						continue;
					}
					foreach ( $group['sections'] as $sid ) {
						$seen[ (string) $sid ] = true;
					}
				}

				$orphans = array();
				foreach ( array_keys( $sections ) as $sid ) {
					$key = (string) $sid;
					if ( ! isset( $seen[ $key ] ) ) {
						$orphans[] = $sid;
					}
				}

				if ( ! empty( $orphans ) ) {
					$groups[] = array(
						'id'       => 'more',
						'title'    => __( 'More', 'woocommerce-anti-fraud' ),
						'sections' => $orphans,
					);
				}

				return $groups;
			}

			/**
			 * Whether the current settings section belongs to a navigation group.
			 *
			 * @param array  $group            Group definition.
			 * @param string $current_section  Current section slug.
			 * @return bool
			 */
			protected function navigation_group_contains_section( array $group, $current_section ) {
				if ( empty( $group['sections'] ) ) {
					return false;
				}
				foreach ( $group['sections'] as $sid ) {
					if ( (string) $sid === (string) $current_section ) {
						return true;
					}
				}
				return false;
			}

			/**
			 * Settings tab URL for a section slug (empty string = default / overview).
			 *
			 * @param string $section_id Section key.
			 * @return string
			 */
			protected function get_settings_tab_url( $section_id = '' ) {
				$url = admin_url( 'admin.php?page=wc-settings&tab=' . $this->id );
				if ( '' !== (string) $section_id ) {
					$url = add_query_arg( 'section', $section_id, $url );
				}
				return $url;
			}

			/**
			 * Primary + contextual secondary navigation (replaces multi-column subsubsub dump).
			 *
			 * @since 7.2.6
			 * @since 7.4.0 Two-tier pattern: compact primary row; secondary row only when the active group has multiple sections.
			 */
			public function output_sections() {
				global $current_section;

				$sections = $this->get_sections();

				if ( empty( $sections ) || 1 === count( $sections ) ) {
					return;
				}

				$groups = $this->get_navigation_groups();
				$groups = $this->append_orphan_sections_to_navigation( $groups, $sections );

				$active_group = null;
				foreach ( $groups as $group ) {
					if ( $this->navigation_group_contains_section( $group, $current_section ) ) {
						$active_group = $group;
						break;
					}
				}

				$has_secondary_nav = ( $active_group && count( $active_group['sections'] ) > 1 );
				$shell_classes     = array( 'wc-af-settings-nav-shell', 'wc-af-section-nav-shell' );
				if ( ! $has_secondary_nav ) {
					$shell_classes[] = 'wc-af-settings-nav-shell--primary-only';
				}

				echo '<div class="' . esc_attr( implode( ' ', $shell_classes ) ) . '" data-wc-af-nav="1">';

				echo '<nav class="wc-af-primary-nav" aria-label="' . esc_attr__( 'Main areas', 'woocommerce-anti-fraud' ) . '">';
				echo '<ul class="wc-af-primary-nav__list">';

				foreach ( $groups as $group ) {
					if ( empty( $group['sections'] ) || empty( $group['title'] ) ) {
						continue;
					}

					$valid_sections = array();
					foreach ( $group['sections'] as $sid ) {
						if ( array_key_exists( $sid, $sections ) ) {
							$valid_sections[] = $sid;
						}
					}
					if ( empty( $valid_sections ) ) {
						continue;
					}

					$first_sid    = $valid_sections[0];
					$primary_url  = $this->get_settings_tab_url( $first_sid );
					$is_primary_active = $this->navigation_group_contains_section( $group, $current_section );

					$item_classes = array( 'wc-af-primary-nav__item' );
					if ( $is_primary_active ) {
						$item_classes[] = 'is-active';
					}

					echo '<li class="' . esc_attr( implode( ' ', $item_classes ) ) . '">';
					echo '<a href="' . esc_url( $primary_url ) . '" class="wc-af-primary-nav__link">' . esc_html( $group['title'] ) . '</a>';
					echo '</li>';
				}

				echo '</ul>';
				echo '</nav>';

				if ( $has_secondary_nav ) {
					echo '<nav class="wc-af-secondary-nav" aria-label="' . esc_attr__( 'Sub-pages in this area', 'woocommerce-anti-fraud' ) . '">';
					echo '<ul class="wc-af-secondary-nav__list">';

					foreach ( $active_group['sections'] as $section_id ) {
						if ( ! array_key_exists( $section_id, $sections ) ) {
							continue;
						}

						$label = $sections[ $section_id ];
						$url   = $this->get_settings_tab_url( $section_id );
						$is_current = (string) $current_section === (string) $section_id;

						$li_classes = array( 'wc-af-secondary-nav__item' );
						if ( $is_current ) {
							$li_classes[] = 'is-current';
						}

						$current_attr = $is_current ? ' aria-current="page"' : '';

						echo '<li class="' . esc_attr( implode( ' ', $li_classes ) ) . '">';
						echo '<a href="' . esc_url( $url ) . '" class="wc-af-secondary-nav__link" data-wc-af-section="' . esc_attr( $section_id ) . '"' . ( $is_current ? ' aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a>';
						echo '</li>';
					}

					echo '</ul>';
					echo '</nav>';
				}

				echo '</div>';
			}

			/**
			 * Short intro strip for the active section (merchant language).
			 *
			 * @since 7.2.6
			 *
			 * @param string $section Section slug (empty string = overview).
			 */
			public function print_section_intro( $section ) {
				if ( '' === $section ) {
					return;
				}

				$intros = array(
					'general'                    => array(
						'title' => __( 'Core protection', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Risk bands and what happens to orders before payment or shipment—hold, block, or allow.', 'woocommerce-anti-fraud' ),
					),
					'rules'                      => array(
						'title' => __( 'Fraud rules', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Turn rules on and set weights; each match adds to the score. Tune for your store.', 'woocommerce-anti-fraud' ),
					),
					'card_attacks'               => array(
						'title' => __( 'Card testing protection', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Checkout CAPTCHA, order and payment attempt limits, and cooldowns against scripted checkouts.', 'woocommerce-anti-fraud' ),
					),
					'cleanup'                    => array(
						'title' => __( 'Failed orders & cleanup', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Bulk-delete failed orders and optionally quiet failed-payment emails during a card attack or heavy card testing.', 'woocommerce-anti-fraud' ),
					),
					'marketplace_orders'         => array(
						'title' => __( 'Marketplace orders', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Fair scoring for imported eBay, Amazon, and Etsy orders.', 'woocommerce-anti-fraud' ),
					),
					'email_alert'                => array(
						'title' => __( 'Email alerts', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Alerts when risk crosses your threshold; rate limits keep volume manageable.', 'woocommerce-anti-fraud' ),
					),
					'white_list'               => array(
						'title' => __( 'Allow list', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Trusted customers, IPs, roles, or payment methods—fewer false positives.', 'woocommerce-anti-fraud' ),
					),
					'black_list'               => array(
						'title' => __( 'Block list', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Block or penalize emails, domains, or IPs you reject.', 'woocommerce-anti-fraud' ),
					),
					'recaptcha_settings'       => array(
						'title' => __( 'Checkout CAPTCHA', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Google reCAPTCHA or Cloudflare Turnstile so bots cannot finish checkout.', 'woocommerce-anti-fraud' ),
					),
					'paypal_settings'          => array(
						'title' => __( 'PayPal & Checkout CAPTCHA', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Align PayPal checkouts with Checkout CAPTCHA, order attempt limits, and payment attempt limits.', 'woocommerce-anti-fraud' ),
					),
					'minfraud_settings'        => array(
						'title' => __( 'MaxMind minFraud · Score', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Adds minFraud Score to the risk total after you connect credentials.', 'woocommerce-anti-fraud' ),
					),
					'minfraud_insights_settings' => array(
						'title' => __( 'MaxMind minFraud · Insights', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Optional extra signals when your plan includes Insights.', 'woocommerce-anti-fraud' ),
					),
					'minfraud_factors_settings'  => array(
						'title' => __( 'MaxMind minFraud · Factors', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Optional finer inputs when your plan supports Factors.', 'woocommerce-anti-fraud' ),
					),
					'minfraud_signals_settings' => array(
						'title' => __( 'MaxMind Advanced Signal Rules', 'woocommerce-anti-fraud' ),
						'body'  => __( 'IP intelligence signal rules powered by MinFraud Insights or Factors.', 'woocommerce-anti-fraud' ),
					),
					'trust_swiftly_settings'   => array(
						'title' => __( 'Trust Swiftly', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Step-up verification for selected high-risk orders when you use the service.', 'woocommerce-anti-fraud' ),
					),
					'ai_fraud_prevention'      => array(
						'title' => __( 'AI fraud signals', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Optional: add your API key and comply with the provider’s terms.', 'woocommerce-anti-fraud' ),
					),
					'chargeback_settings'      => array(
						'title' => __( 'Chargeback programs', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Partner programs; sign-up happens outside WooCommerce.', 'woocommerce-anti-fraud' ),
					),
					'need_support'             => array(
						'title' => __( 'Help & support', 'woocommerce-anti-fraud' ),
						'body'  => __( 'Docs, WooCommerce.com support, and in-plugin links.', 'woocommerce-anti-fraud' ),
					),
				);

				$intro = isset( $intros[ $section ] ) ? $intros[ $section ] : null;

				if ( empty( $intro ) ) {
					return;
				}

				// One heading per screen: panel <h2> holds the section title; intro is lead copy only.
				echo '<div class="wc-af-section-intro wc-af-section-intro--premium wc-af-section-intro--compact"><div class="wc-af-section-intro__inner">';
				echo '<p class="wc-af-section-intro__body">' . esc_html( $intro['body'] ) . '</p>';
				echo '</div></div>';
			}

			/**
			 * Get settings array
			 *
			 * @param string $current_section Optional. Defaults to empty string.
			 *
			 * @return array Array of settings
			 *@since 1.0.0
			 */
			public function get_settings( $current_section = '' ) {

				$plugindetected = get_option( 'paypal_acp_plugindetected', 'no' );

				$score_options = array();
				for ( $i = 100; $i > - 1; $i -- ) {
					if ( ( $i % 5 ) == 0 ) {
						$score_options[ $i ] = $i;
					}
				}

				$rule_weight = array();
				for ( $i = 20; $i > -1; $i -- ) {
					$rule_weight[ $i ] = $i;
				}

				$user_roles = array();
				$wc_af_whitelist_user_roles = get_option( 'wc_af_whitelist_user_roles' );
				if ( empty( $wc_af_whitelist_user_roles ) ) {
					$wc_af_whitelist_user_roles = array();
				}

				// $wc_af_whitelist_restapi = get_option( 'wc_settings_anti_fraud_whitelist_restapi' );
				// if ( empty( $wc_af_whitelist_restapi ) ) {
				// 	$wc_af_whitelist_restapi = array();
				// }

				// print_r($wc_af_whitelist_user_roles);
				$wc_af_whitelist_payment_methods = get_option( 'wc_settings_anti_fraud_whitelist_payment_method' );
				if ( empty( $wc_af_whitelist_payment_methods ) ) {
					$wc_af_whitelist_payment_methods = array();
				}
				// print_r($wc_af_whitelist_payment_methods);

				$wc_af_unsafe_countries_list = get_option( 'wc_settings_anti_fraud_define_unsafe_countries_list' );
				if ( empty( $wc_af_unsafe_countries_list ) ) {
					$wc_af_unsafe_countries_list = array();
				}

				global $wp_roles;

				$all_roles = $wp_roles->roles;

				/**
				 * Editable roles
				 *
				 * @since 1.0.0
				 */
				$editable_roles = apply_filters( 'editable_roles', $all_roles );
				foreach ( $editable_roles as $role => $details ) {
					$role = esc_attr( $role );
					$name = translate_user_role( $details['name'] );
					$user_roles[ $role ] = $name;
				}

				$installed_payment_methods = WC()->payment_gateways->payment_gateways();
				$availableMethods = array();
				foreach ( $installed_payment_methods as $methods => $arr ) {
					$availableMethods[ $methods ] = $arr->method_title;
				}

				global $wpdb;

				$availablRestAPIKeys = array();

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$results = $wpdb->get_results(
					"SELECT 
						k.key_id,
						k.description,
						k.permissions,
						k.truncated_key,
						k.consumer_secret,
						u.ID as user_id,
						u.user_login,
						u.user_email
					FROM {$wpdb->prefix}woocommerce_api_keys k
					LEFT JOIN {$wpdb->users} u
					ON k.user_id = u.ID"
				);

				if ( ! empty( $results ) ) {
					foreach ( $results as $row ) {

						// Make a readable label for dropdown
						$label  = 'API Key ending with - ' . $row->truncated_key;
						// Use key_id as the option key
						$availablRestAPIKeys[ $row->truncated_key ] = $label;
					}
				}

				/* Trust Swiftly get verification template */
				$apiKey = get_option( 'wc_af_trust_swiftly_api_key' );
				$baseUrl = get_option( 'wc_af_trust_swiftly_base_url' );
				$vTemplate = array();
				if (isset($apiKey) && !empty($apiKey) && isset($baseUrl) && !empty($baseUrl)) {

					$headers = array(
						'Authorization' => 'Bearer ' . $apiKey,
						'Content-Type' => 'application/json',
						'User-Agent' => 'TrustSwiftly/1.0'
					);
					$response = wp_remote_get( $baseUrl . '/api/settings/templates/verifications', array(
						'headers' => $headers,
					));
					$retrieve_body = wp_remote_retrieve_body( $response );

					$body_data = json_decode( $retrieve_body );
					$response_code = wp_remote_retrieve_response_code($response);

					if ( json_last_error() === JSON_ERROR_NONE ) {

						if ( '200' == $response_code ) {

							if ( ! empty( $body_data ) ) {

								foreach ( $body_data as $product ) {

									$vTemplate[$product->name] = $product->template_name;
									
								}
							}
						}
					}
				}

				$whenToVerify = array(
					'before_checkout' => __( 'Before checkout', 'woocommerce-anti-fraud' ),
					'after_checkout'  => __( 'After checkout', 'woocommerce-anti-fraud' ),
				);

				$linkMethod = array(
					'link_method' => __( 'Link', 'woocommerce-anti-fraud' ),
				 );

				$chatGptModels = array(
					'' => 'Please Select',
					'gpt-5.1' => 'Latest version - ChatGPT 5.1',
					'gpt-4.1' => 'ChatGPT 4.1',
					'gpt-4.1-mini' => 'ChatGPT 4.1 Mini',
					'gpt-4.1-nano' => 'ChatGPT 4.1 Nano',
					'gpt-4o' => 'ChatGPT 4o',
					'gpt-4o-mini' => 'ChatGPT 4o Mini',
					'o4-mini' => 'ChatGPT o4 Mini',
					'gpt-3.5-turbo' => 'ChatGPT 3.5 Turbo',
				);


				/**
				 * Ensure whitelist option always saves correctly and supports "empty" unselect
				 */
				add_filter(
					'woocommerce_admin_settings_sanitize_option_wc_settings_anti_fraud_whitelist_restapi',
					function ( $value ) {
						// If array contains empty string (None option), treat as empty selection
						if ( is_array( $value ) && in_array( '', $value, true ) ) {
							return [];
						}
						return is_array( $value ) ? $value : [];
					}
				);

				/**
				 * Read saved whitelist REST API keys (always as array)
				 */
				$wc_af_whitelist_restapi = get_option( 'wc_settings_anti_fraud_whitelist_restapi', [] );
				if ( ! is_array( $wc_af_whitelist_restapi ) ) {
					$wc_af_whitelist_restapi = [];
				}

				/**
				 * Always include "None" option at the top to allow deselection
				 */
				$availablRestAPIKeys = array_merge(
					[ '' => __( '— Clear selection —', 'woocommerce-anti-fraud' ) ],
					$availablRestAPIKeys
				);

				/* End */

				if ( 'marketplace_orders' == $current_section ) {

				/*
				 * Simplified settings — 2 options only.
				 *
				 * All per-marketplace behaviour (ignore unknown origin, score bonus,
				 * hold-instead-of-cancel, etc.) is handled automatically through the
				 * built-in profiles in WC_AF_Marketplace_Detector.  Merchants only
				 * need to flip the global toggle to get the full protection.
				 *
				 * The only extra choice exposed is what to do with "unknown imports"
				 * (API orders that don't match any known marketplace), because this is
				 * the one case where different merchants have legitimately different
				 * needs (ERP systems, custom integrations, etc.).
				 */

				$unknown_import_options = array(
					'treat_as_marketplace' => __( 'Hold for review — safe default (recommended)', 'woocommerce-anti-fraud' ),
					'treat_as_native'      => __( 'Apply standard rules — same as a native checkout order', 'woocommerce-anti-fraud' ),
					'always_hold'          => __( 'Always hold — manual review required every time', 'woocommerce-anti-fraud' ),
				);

				$settings = array(
					array(
						'name' => __( 'Marketplace orders', 'woocommerce-anti-fraud' ),
						'type' => 'title',
						'desc' => wp_kses_post(
							__( 'Orders imported from <strong>eBay, Amazon and Etsy</strong> are created via API and lack standard storefront signals (IP address, checkout session, browser data). Without this feature, they can be incorrectly flagged or auto-cancelled by rules such as <em>Unknown Origin Orders</em>.'
							. '<br/><br/>Turning on <strong>Enable Marketplace Detection</strong> is all you need to do. Each marketplace\'s built-in profile handles the rest automatically:'
							. '<ul style="margin:8px 0 0 20px; list-style:disc;">'
							. '<li><strong>eBay &amp; Amazon &amp; Etsy</strong> — Unknown Origin check skipped, payment trusted, cancel downgraded to On Hold.</li>'
							. '<li><strong>Native WooCommerce checkout</strong> — completely unaffected, all existing rules continue to run normally.</li>'
							. '</ul><hr/>', 'woocommerce-anti-fraud' )
						),
						'id'   => 'wc_af_marketplace_settings_section',
					),

					// ── Setting 1 of 2: global enable toggle ──────────────────────────
					array(
						'title'   => __( 'Detect eBay, Amazon, and Etsy imports', 'woocommerce-anti-fraud' ),
						'type'    => 'checkbox',
						'label'   => __( 'Detect eBay, Amazon, Etsy and other imported orders and apply channel-appropriate Anti-Fraud profiles', 'woocommerce-anti-fraud' ),
						'desc'    => __( 'When on, imported marketplace orders use built-in profiles (rule skips, score tweaks, safer holds). You do not configure eBay, Amazon, or Etsy separately.', 'woocommerce-anti-fraud' ),
						'default' => 'no',
						'id'      => 'wc_af_marketplace_detection_enabled',
					),

					// ── Setting 2 of 2: unknown import edge-case ──────────────────────
					array(
						'title'   => __( 'Other REST API orders', 'woocommerce-anti-fraud' ),
						'type'    => 'select',
						'options' => $unknown_import_options,
						'default' => 'treat_as_marketplace',
						'desc'    => __( 'How to treat REST API orders that are not from eBay, Amazon, or Etsy (for example ERP, connectors, or headless). Only applies when marketplace detection above is enabled.', 'woocommerce-anti-fraud' ),
						'id'      => 'wc_af_marketplace_unknown_import_handling',
						'css'     => 'width: 28em;',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'wc_af_marketplace_settings_section',
					),

					// ── Built-in test tool ────────────────────────────────────────────
					array(
						'type' => 'marketplace_test_tool',
						'id'   => 'wc_af_marketplace_test_tool',
					),
				);

				} else if ( 'trust_swiftly_settings' == $current_section ) {

					/**
					* WCAF Filter Plugin Trust Swiftly Settings
					*
					* @param array $settings Array of the plugin settings
					*
					*@since 1.0.0
					*/
					$settings = apply_filters(
						'myplugin_trust_swiftly_settings',
						array(
							array(
								'name'     => __( 'Trust Swiftly', 'woocommerce-anti-fraud' ),
								'type'     => 'title',
								'desc'     => __( '<a href="https://thrive.zohopublic.com/aref/G4734hGpxD/mBWuIqNh8" target="_blank">Trust Swiftly</a> is a paid identity-verification service. Send selected high-risk orders to step-up checks (for example ID or selfie). <a href="https://thrive.zohopublic.com/aref/G4734hGpxD/mBWuIqNh8" target="_blank">Learn more</a>.<hr/>', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_af_trust_swiftly_settings',
							),

							array(
								'title'    => __( 'Enable Trust Swiftly', 'woocommerce-anti-fraud' ),
								'type'     => 'checkbox',
								'label'    => '',
								'desc'     => __( 'Turn on to send customers to Trust Swiftly when the risk score reaches the threshold below.', 'woocommerce-anti-fraud' ),
								'default'  => 'no',
								'id'       => 'wc_af_trust_swiftly_type',
							),
							array(
								'title'    => __( 'API key', 'woocommerce-anti-fraud' ),
								'type'     => 'password',
								'label'    => '',
								'desc'     => __( 'From your Trust Swiftly dashboard.', 'woocommerce-anti-fraud' ),
								//'css'      => 'width: 10em;',
								'id'       => 'wc_af_trust_swiftly_api_key',
							),
							
							array(
								'title'    => __( 'Base URL', 'woocommerce-anti-fraud' ),
								'type'     => 'text',
								'label'    => '',
								'desc'     => __( 'Your Trust Swiftly site URL (for example https://example.trustswiftly.com).', 'woocommerce-anti-fraud' ),
								//'css'      => 'width: 10em;',
								'id'       => 'wc_af_trust_swiftly_base_url',
							),
							
							array(
								'title'    => __( 'Verification method', 'woocommerce-anti-fraud' ),
								'type'     => 'select',
								'options'  => $linkMethod,
								'option'   => '',
								'label'    => '',
								'desc'     => __( 'How Trust Swiftly delivers the verification step (when supported by your account).', 'woocommerce-anti-fraud' ),
								'css'      => 'width: 10em;',
								'id'       => 'wc_af_trust_swiftly_veri_method',
							),

							array(
								'title'    => __( 'Verification template', 'woocommerce-anti-fraud' ),
								'type'     => 'select',
								'options'  => $vTemplate,
								'option'   => '',
								'label'    => '',
								'desc'     => __( 'Which flow to use (for example ID or selfie). Create templates in Trust Swiftly first.', 'woocommerce-anti-fraud' ),
								'css'      => 'width: 15em;',
								'id'       => 'wc_af_trust_swiftly_veri_template',
							),

							array(
								'title'    => __( 'When to verify', 'woocommerce-anti-fraud' ),
								'type'     => 'select',
								'options'  => $whenToVerify,
								'option'   => '',
								'label'    => '',
								'desc'     => __( 'Run verification before the order completes, or after checkout, depending on your workflow.', 'woocommerce-anti-fraud' ),
								'css'      => 'width: 10em;',
								'id'       => 'wc_af_trust_when_to_verify',
							),

							array(
							'name'     => __( 'Risk score to start verification', 'woocommerce-anti-fraud' ),
							'type'     => 'select',
							'options'  => $score_options,
							'option'   => '',
							'desc'     => '',
							'desc_tip'     => __( 'When the order risk score reaches this value, the customer is prompted to complete Trust Swiftly verification.', 'woocommerce-anti-fraud' ),
							'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_strust_swiftly_score',
							'css'         => 'display: block; width: 5em;',
							'default' => '75',
						),

							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_trust_swiftly_settings',
							),
						)
					);

				} else if ( 'minfraud_settings' == $current_section ) {

					/**
					* WCAF Filter Plugin  MinFraud Settings
					*
					* @param array $settings Array of the plugin settings
					*
					*@since 1.0.0
					*/
					$settings = apply_filters(
						'myplugin_minfraud_settings',
						array(
							array(
								'name'     => __( 'MaxMind minFraud · Score API', 'woocommerce-anti-fraud' ),
								'type'     => 'title',
								'desc_tip' => '',
								'desc'     => __( '<a href="https://maxmind.pxf.io/EK15XW" target="_blank">MaxMind minFraud</a> scores transactions with machine learning (paid). Enter credentials below to blend its score with this plugin’s rules. <a href="https://maxmind.pxf.io/EK15XW" target="_blank">Sign up at maxmind.com</a>.<hr/>', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_af_minfraud_settings',
							),

							array(
								'title'    => __( 'Enable Score API', 'woocommerce-anti-fraud' ),
								'type'     => 'checkbox',
								'label'    => '',
								'desc_tip' => __( 'Requires a MaxMind plan that includes the Score API.', 'woocommerce-anti-fraud' ),
								'desc'    => __( 'Send order data to minFraud Score using the account ID and license key below.', 'woocommerce-anti-fraud' ),
								'default'  => 'no',
								'id'       => 'wc_af_maxmind_type',
							),
							array(
								'title'    => __( 'Device tracking', 'woocommerce-anti-fraud' ),
								'type'     => 'checkbox',
								'label'    => '',
								'desc_tip' => __( 'Uses MaxMind device data when available.', 'woocommerce-anti-fraud' ),
								'desc'    => __( 'Surface risk when the same device is seen with changing proxies across attempts (common in scripted attacks).', 'woocommerce-anti-fraud' ),
								'default'  => 'no',
								'id'       => 'wc_af_maxmind_device_tracking',
							),

							array(
								'name'     => __( 'MaxMind account ID', 'woocommerce-anti-fraud' ),
								'type'     => 'text',
								'desc_tip' => __( 'Shown in your MaxMind account dashboard.', 'woocommerce-anti-fraud' ),
								'desc'     => __( 'Need an account? <a href="https://maxmind.pxf.io/EK15XW" target="_blank">Register at maxmind.com</a>.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_af_maxmind_user',
								'css'      => 'width: 10em;',
							),
							array(
								'name'     => __( 'MaxMind license key', 'woocommerce-anti-fraud' ),
								'type'     => 'password',
								'desc_tip' => __( 'Keep private—same privilege as a password.', 'woocommerce-anti-fraud' ),
								'desc'     => __( 'From your MaxMind account.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_af_maxmind_license_key',
								'css'      => 'width: 15em;',
							),

							array(
								'title'       => __( 'Flag IP vs billing location mismatch', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc' => '',
								'desc_tip' => __( 'Adds risk when the customer’s IP geolocation does not align with the billing address.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_ip_geolocation_order',
							),
							array(
								'name'     => __( 'Points if matched', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc_tip' => __( 'Points added to the risk score when this check triggers.', 'woocommerce-anti-fraud' ),
								'desc'     => '',
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_ip_geolocation_order_weight',
								'css'         => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),

							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_minfraud_settings',
							),

							array(
								'name' => __( 'Blend minFraud into the order score', 'woocommerce-anti-fraud' ),
								'type' => 'title',
								'desc' => '<hr/>',
								'id'   => 'wc_af_minfraud_rule_settings',
							),

							array(
								'name'     => __( 'Minimum Score API value to blend', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc_tip' => __( 'Ignore minFraud when its score is below this—reduces noise from low-confidence signals.', 'woocommerce-anti-fraud' ),
								'desc'     => __( 'Only minFraud results above this value are blended into the order risk score.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_minfraud_risk_score',
								'css'      => 'display: block; width: 5em;',
								'default'  => '5',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),

							),

							array(
								'name'     => __( 'Score API blend strength', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc_tip' => __( 'Higher = minFraud moves the combined score more when the minimum threshold is met.', 'woocommerce-anti-fraud' ),
								'desc'     => __( 'How much minFraud contributes to the order score once blending applies.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_minfraud_order_weight',
								'css'         => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),
							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_minfraud_rule_settings',
							),

						)
					);

				} else if ( 'minfraud_insights_settings' == $current_section ) {

					/**
					* WCAF Filter Plugin  MinFraud Settings
					*
					* @param array $settings Array of the plugin settings
					*
					*@since 1.0.0
					*/
					$settings = apply_filters(
						'myplugin_minfraud_insights_settings',
						array(
							array(
								'name'     => __( 'MaxMind minFraud · Insights', 'woocommerce-anti-fraud' ),
								'type'     => 'title',
								'desc'     => __( '<a href="https://maxmind.pxf.io/EK15XW" target="_blank">MaxMind minFraud</a> scores transactions using machine learning. It is a paid service—add your credentials below to blend its score with this plugin’s rules. <a href="https://maxmind.pxf.io/EK15XW" target="_blank">Sign up at maxmind.com</a>.<hr/>', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_af_minfraud_settings',
							),
							array(
								'title'    => __( 'Enable Insights API', 'woocommerce-anti-fraud' ),
								'type'     => 'checkbox',
								'label'    => '',
								'desc'    => __( 'Requires a MaxMind plan that includes Insights. Uses the same account credentials as the Score API section.', 'woocommerce-anti-fraud' ),
								'default'  => 'no',
								'id'       => 'wc_af_maxmind_insights',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_minfraud_settings',
							),

							array(
								'name' => __( 'Blend minFraud into the order score', 'woocommerce-anti-fraud' ),
								'type' => 'title',
								'desc' => '<hr/>',
								'id'   => 'wc_af_minfraud_rule_settings',
							),

							array(
								'name'     => __( 'Minimum Insights value to blend', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc_tip' => __( 'Ignore Insights when below this—same idea as Score API minimum.', 'woocommerce-anti-fraud' ),
								'desc'     => __( 'Only Insights results above this are blended into the order risk score.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_minfraud_insights_risk_score',
								'css'      => 'display: block; width: 5em;',
								'default'  => '5',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),

							),

							array(
								'name'     => __( 'Insights blend strength', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc_tip' => __( 'How much Insights contributes once the minimum is met.', 'woocommerce-anti-fraud' ),
								'desc'     => __( 'How strongly Insights affects the combined score—the same role as the Score API blend weight.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_minfraud_insights_order_weight',
								'css'         => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),
							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_minfraud_rule_settings',
							),

						)
					);

				} else if ( 'minfraud_factors_settings' == $current_section ) {

					/**
					* WCAF Filter Plugin  MinFraud Settings
					*
					* @param array $settings Array of the plugin settings
					*
					*@since 1.0.0
					*/
					$settings = apply_filters(
						'myplugin_minfraud_factors_settings',
						array(
							array(
								'name'     => __( 'MaxMind minFraud · Factors', 'woocommerce-anti-fraud' ),
								'type'     => 'title',
								'desc'     => __( '<a href="https://maxmind.pxf.io/EK15XW" target="_blank">MaxMind minFraud</a> scores transactions using machine learning. It is a paid service—add your credentials below to blend its score with this plugin’s rules. <a href="https://maxmind.pxf.io/EK15XW" target="_blank">Sign up at maxmind.com</a>.<hr/>', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_af_minfraud_settings',
							),
							array(
								'title'    => __( 'Enable Factors API', 'woocommerce-anti-fraud' ),
								'type'     => 'checkbox',
								'label'    => '',
								'desc'    => __( 'Requires a MaxMind plan that includes Factors. Uses the same account credentials as the Score API section.', 'woocommerce-anti-fraud' ),
								'default'  => 'no',
								'id'       => 'wc_af_maxmind_factors',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_minfraud_settings',
							),

							array(
								'name' => __( 'Blend minFraud into the order score', 'woocommerce-anti-fraud' ),
								'type' => 'title',
								'desc' => '<hr/>',
								'id'   => 'wc_af_minfraud_rule_settings',
							),

							array(
								'name'     => __( 'Minimum Factors value to blend', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc_tip' => __( 'Ignore Factors when below this.', 'woocommerce-anti-fraud' ),
								'desc'     => __( 'Only Factors results above this are blended into the order risk score.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_minfraud_factors_risk_score',
								'css'      => 'display: block; width: 5em;',
								'default'  => '5',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),

							),

							array(
								'name'     => __( 'Factors blend strength', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc_tip' => __( 'How much Factors contributes once the minimum is met.', 'woocommerce-anti-fraud' ),
								'desc'     => __( 'Strength of Factors in the combined score.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_minfraud_factors_order_weight',
								'css'         => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),
							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_minfraud_rule_settings',
							),

						)
					);

				} else if ( 'minfraud_signals_settings' == $current_section ) {

					/**
					 * MaxMind Advanced Signals Settings
					 * Controls the five signal-level fraud rules powered by MinFraud Insights/Factors.
					 *
					 * @since 7.2.6
					 *
					 *@param array $settings.
					 */
					$settings = apply_filters(
					'myplugin_minfraud_signals_settings',
					array(
						array(
							'name' => __( 'MaxMind Advanced Signal Rules', 'woocommerce-anti-fraud' ),
							'type' => 'title',
							'desc' => __( 'These rules use IP intelligence signals returned by the MinFraud <strong>Insights</strong> or <strong>Factors</strong> endpoint. They run after the primary MinFraud rule and read signals already stored against the order — no extra API calls are made.<br/><em>Note: the Score endpoint does not return IP traits; signals will remain "No" when only the Score tier is enabled.</em><hr/>', 'woocommerce-anti-fraud' ),
							'id'   => 'wc_af_mm_signals_section',
						),

						// --- VPN ---
						array(
							'title'   => __( 'Anonymous VPN Detection', 'woocommerce-anti-fraud' ),
							'type'    => 'title',
							'desc'    => '<hr/>',
							'id'      => 'wc_af_mm_vpn_section',
						),
						array(
							'title'   => __( 'Enable VPN Rule', 'woocommerce-anti-fraud' ),
							'type'    => 'checkbox',
							'label'   => '',
							'desc'    => __( 'Flag orders where the customer IP is an anonymous VPN.', 'woocommerce-anti-fraud' ),
							'default' => 'no',
							'id'      => 'wc_af_mm_vpn_enabled',
						),
						array(
							'name'    => __( 'VPN Rule Weight', 'woocommerce-anti-fraud' ),
							'type'    => 'number',
							'desc'    => __( 'Risk points deducted from the overall score when a VPN is detected. Default: 30.', 'woocommerce-anti-fraud' ),
							'id'      => 'wc_settings_anti_fraud_mm_vpn_weight',
							'default' => '30',
							'css'     => 'display: block; width: 5em;',
							'custom_attributes' => array( 'min' => 0, 'step' => 1, 'max' => 100 ),
						),
						array( 'type' => 'sectionend', 'id' => 'wc_af_mm_vpn_section' ),

						// --- Public Proxy ---
						array(
							'title'   => __( 'Public Proxy Detection', 'woocommerce-anti-fraud' ),
							'type'    => 'title',
							'desc'    => '<hr/>',
							'id'      => 'wc_af_mm_proxy_section',
						),
						array(
							'title'   => __( 'Enable Proxy Rule', 'woocommerce-anti-fraud' ),
							'type'    => 'checkbox',
							'label'   => '',
							'desc'    => __( 'Flag orders where the customer IP is a public proxy.', 'woocommerce-anti-fraud' ),
							'default' => 'no',
							'id'      => 'wc_af_mm_proxy_enabled',
						),
						array(
							'name'    => __( 'Proxy Rule Weight', 'woocommerce-anti-fraud' ),
							'type'    => 'number',
							'desc'    => __( 'Risk points deducted from the overall score when a public proxy is detected. Default: 50.', 'woocommerce-anti-fraud' ),
							'id'      => 'wc_settings_anti_fraud_mm_proxy_weight',
							'default' => '50',
							'css'     => 'display: block; width: 5em;',
							'custom_attributes' => array( 'min' => 0, 'step' => 1, 'max' => 100 ),
						),
						array( 'type' => 'sectionend', 'id' => 'wc_af_mm_proxy_section' ),

						// --- TOR ---
						array(
							'title'   => __( 'TOR Exit Node Detection', 'woocommerce-anti-fraud' ),
							'type'    => 'title',
							'desc'    => '<hr/>',
							'id'      => 'wc_af_mm_tor_section',
						),
						array(
							'title'   => __( 'Enable TOR Rule', 'woocommerce-anti-fraud' ),
							'type'    => 'checkbox',
							'label'   => '',
							'desc'    => __( 'Flag orders where the customer IP is a TOR exit node.', 'woocommerce-anti-fraud' ),
							'default' => 'no',
							'id'      => 'wc_af_mm_tor_enabled',
						),
						array(
							'name'    => __( 'TOR Rule Weight', 'woocommerce-anti-fraud' ),
							'type'    => 'number',
							'desc'    => __( 'Risk points deducted from the overall score when a TOR exit node is detected. Default: 80.', 'woocommerce-anti-fraud' ),
							'id'      => 'wc_settings_anti_fraud_mm_tor_weight',
							'default' => '80',
							'css'     => 'display: block; width: 5em;',
							'custom_attributes' => array( 'min' => 0, 'step' => 1, 'max' => 100 ),
						),
						array( 'type' => 'sectionend', 'id' => 'wc_af_mm_tor_section' ),

						// --- Hosting Provider ---
						array(
							'title'   => __( 'Hosting / Datacenter IP Detection', 'woocommerce-anti-fraud' ),
							'type'    => 'title',
							'desc'    => '<hr/>',
							'id'      => 'wc_af_mm_hosting_section',
						),
						array(
							'title'   => __( 'Enable Hosting Provider Rule', 'woocommerce-anti-fraud' ),
							'type'    => 'checkbox',
							'label'   => '',
							'desc'    => __( 'Flag orders where the customer IP belongs to a cloud or datacenter provider.', 'woocommerce-anti-fraud' ),
							'default' => 'no',
							'id'      => 'wc_af_mm_hosting_enabled',
						),
						array(
							'name'    => __( 'Hosting Provider Rule Weight', 'woocommerce-anti-fraud' ),
							'type'    => 'number',
							'desc'    => __( 'Risk points deducted from the overall score when a hosting provider IP is detected. Default: 40.', 'woocommerce-anti-fraud' ),
							'id'      => 'wc_settings_anti_fraud_mm_hosting_weight',
							'default' => '40',
							'css'     => 'display: block; width: 5em;',
							'custom_attributes' => array( 'min' => 0, 'step' => 1, 'max' => 100 ),
						),
						array( 'type' => 'sectionend', 'id' => 'wc_af_mm_hosting_section' ),

						// --- IP Distance ---
						array(
							'title'   => __( 'IP Distance from Billing Address', 'woocommerce-anti-fraud' ),
							'type'    => 'title',
							'desc'    => __( 'Compares the geolocation of the customer\'s IP against the billing address coordinates returned by MinFraud.<br/>Requires <strong>Insights</strong> or <strong>Factors</strong> tier.<hr/>', 'woocommerce-anti-fraud' ),
							'id'      => 'wc_af_mm_distance_section',
						),
						array(
							'title'   => __( 'Enable IP Distance Rule', 'woocommerce-anti-fraud' ),
							'type'    => 'checkbox',
							'label'   => '',
							'desc'    => __( 'Flag orders where the IP location is far from the billing address.', 'woocommerce-anti-fraud' ),
							'default' => 'no',
							'id'      => 'wc_af_mm_distance_enabled',
						),
						array(
							'name'    => __( 'High-Risk Distance Threshold (km)', 'woocommerce-anti-fraud' ),
							'type'    => 'number',
							'desc'    => __( 'Orders where the IP is further than this distance (km) from billing will be flagged as high risk. Default: 500 km.', 'woocommerce-anti-fraud' ),
							'id'      => 'wc_settings_anti_fraud_mm_distance_high_km',
							'default' => '500',
							'css'     => 'display: block; width: 5em;',
							'custom_attributes' => array( 'min' => 0, 'step' => 10, 'max' => 20000 ),
						),
						array(
							'name'    => __( 'IP Distance Rule Weight', 'woocommerce-anti-fraud' ),
							'type'    => 'number',
							'desc'    => __( 'Risk points deducted when distance exceeds the threshold above. Default: 30.', 'woocommerce-anti-fraud' ),
							'id'      => 'wc_settings_anti_fraud_mm_distance_weight',
							'default' => '30',
							'css'     => 'display: block; width: 5em;',
							'custom_attributes' => array( 'min' => 0, 'step' => 1, 'max' => 100 ),
						),
						array( 'type' => 'sectionend', 'id' => 'wc_af_mm_distance_section' ),
					)
					);

				} else if ( 'chargeback_settings' == $current_section ) {

					/**
					* WCAF Filter Plugin  MinFraud Settings
					*
					* @param array $settings Array of the plugin settings
					*
					*@since 1.0.0
					*/
					$settings = apply_filters(
						'chargeback_settings',
						array(
							array(
								'name'     => __( 'Chargeback management', 'woocommerce-anti-fraud' ),
								'type'     => 'title',
								'desc_tip'    => __( 'Separate OPMC/Kount onboarding—not part of core Anti-Fraud scoring.', 'woocommerce-anti-fraud' ),
								'desc'     => __( 'Connect your business to OPMC and Kount through a secure onboarding flow. Use the button below to begin.<hr/>', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_af_chargebacks',
							),
							array(
								'title'    => __( 'Chargeback onboarding', 'woocommerce-anti-fraud' ),
								'name' => __( 'Open OPMC chargeback form', 'woocommerce-anti-fraud' ),
								'type' => 'button',
								'desc_tip'    => __( 'Opens OPMC’s form in a new tab. Follow-up comes from OPMC/Kount, not from this plugin.', 'woocommerce-anti-fraud' ),
								'desc' => __( 'Continue to OPMC’s onboarding.', 'woocommerce-anti-fraud' ),
								'class' => 'button-secondary',
								'href'  => 'https://opmc.com.au/chargeback-management-kount/',
								'target'=>'_blank',
								'rel'=>'noopener noreferrer',
								'id'    => 'wc_af_chargebacks_support',
							),
							
							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_chargebacks',
							),

						)
					);
				} else if ( 'need_support' == $current_section ) {

					/**
					* WCAF Filter Plugin  MinFraud Settings
					*
					* @param array $settings Array of the plugin settings
					*
					*@since 1.0.0
					*/
					$settings = apply_filters(
						'need_support',
						array(
							array(
								'name'     => __( 'Help & support', 'woocommerce-anti-fraud' ),
								'type'     => 'title',
								'desc'     => __( 'Documentation, WooCommerce.com support, and quick ways to share feedback.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_af_need_support',
							),
							array(
								'type'  => 'help_cards',
								'id'    => 'wc_af_need_support_cards',
								'cards' => array(
									array(
										'title'        => __( 'Need technical help?', 'woocommerce-anti-fraud' ),
										'text'         => __( 'Open a support ticket for this extension. We also welcome product feedback.', 'woocommerce-anti-fraud' ),
										'action_label' => __( 'Contact WooCommerce.com support', 'woocommerce-anti-fraud' ),
										'url'          => 'https://woocommerce.com/my-account/create-a-ticket/',
									),
									array(
										'title'        => __( 'Found it useful?', 'woocommerce-anti-fraud' ),
										'text'         => __( 'Your review helps other merchants decide if Anti-Fraud is right for their store.', 'woocommerce-anti-fraud' ),
										'action_label' => __( 'Leave a review on WooCommerce.com', 'woocommerce-anti-fraud' ),
										'url'          => 'https://woocommerce.com/products/woocommerce-anti-fraud/#reviews-start',
									),
								),
							),
							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_need_support',
							),

						)
					);
				} else if ( 'recaptcha_settings' == $current_section ) {
					$settings = WC_AF_Settings_Recaptcha::get_settings();
				} else if ( 'email_alert' == $current_section ) {

					/**
					* WCAF Filter Plugin  MinFraud Settings
					*
					* @param array $settings Array of the plugin settings
					*
					*@since 1.0.0
					*/
					$settings = apply_filters(
						'email_alert',
						array(
							array(
								'name'     => __( 'High-risk order emails', 'woocommerce-anti-fraud' ),
								'type'     => 'title',
								'desc'     => __( 'Email the store when an order reaches your alert threshold. Optional rate limits reduce inbox volume during a card attack or heavy card testing.<hr/>', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_af_email_alert_settings',
							),
							array(
								'title'       => __( 'Email when risk hits threshold', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'no',
								'desc'        => '',
								'desc_tip'    => __( 'Sends to the WooCommerce admin email, plus any extra addresses below.', 'woocommerce-anti-fraud' ),
								'id'          => 'wc_af_email_notification',
							),
							array(
								'name'     => __( 'Additional recipients', 'woocommerce-anti-fraud' ),
								'type'     => 'textarea',
								'desc'     => '',
								'desc_tip'   => __( 'One email per line, or separate with commas.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_custom_email',
								'css'         => 'width:100%; height: 100px;',
								'default'     => '',
								'class'       => 'wc_af_tags_input',
							),
							array(
								'name'     => __( 'Minimum score to send alert', 'woocommerce-anti-fraud' ),
								'type'     => 'select',
								'options'  => $score_options,
								'desc'     => '',
								'desc_tip'     => __( 'An email is sent when the order risk score is at or above this value.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_email_score',
								'css'         => 'display: block; width: 5em;',
								'default' => '50',
							),

														// PLUGINS-195 Start
							array(
								'title'       => __( 'Rate-limit alert emails', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'no',
								'desc'        => '',
								'desc_tip'    => __( 'When on, only sends up to the max number below within each time window.', 'woocommerce-anti-fraud' ),
								'id'          => 'wc_af_email_rate_limit_enable',
							),
							array(
								'name'       => __( 'Max alerts per window', 'woocommerce-anti-fraud' ),
								'type'        => 'number',
								'default'     => 5,
								'desc'        => '',
								'desc_tip'    => __( 'After this many alerts, further high-risk orders are still scored but may not email until the window resets.', 'woocommerce-anti-fraud' ),
								'id'          => 'wc_af_email_rate_limit_max',
								'custom_attributes' => array( 'min' => 1, 'step' => 1 ),
							),
							array(
								'name'       => __( 'Window length (minutes)', 'woocommerce-anti-fraud' ),
								'type'        => 'number',
								'default'     => 30,
								'desc'        => '',
								'desc_tip'    => __( 'Rolling period for the alert cap (e.g. 30 = per half hour).', 'woocommerce-anti-fraud' ),
								'id'          => 'wc_af_email_rate_limit_window',
								'custom_attributes' => array( 'min' => 1, 'step' => 1 ),
							),
							// PLUGINS-195 END
							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_email_alert_settings',
							),
						)
					);

				} else if ( 'white_list' == $current_section ) {

					$settings = WC_AF_Settings_Whitelist::get_settings();
					
				} else if ( 'black_list' == $current_section ) {

					$settings = WC_AF_Settings_Blacklist::get_settings();

				} else if ( 'paypal_settings' == $current_section ) {
					if ('yes' === $plugindetected ) { 
					
					
					/**
					* WCAF Filter Plugin Paypal Settings
					*
					* @param array $settings Array of the plugin settings
					*
					*@since 1.0.0
					*/
					$settings = apply_filters(
						'wc_af_paypal_settings',
						array(

							array(
								'name' => __( 'PayPal account verification', 'woocommerce-anti-fraud' ),
								'type' => 'title',
								'desc' => __( 'Applies when customers pay with their PayPal account (not guest card). Use these options to confirm the PayPal email before you ship or release downloads.<hr/>', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_paypal_settings',
							),
							array(
								'title'       => __( 'Require PayPal email verification', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => 'wc_af_paypal_verification',
								'default'     => 'no',
								'desc' => __( 'Send verification for orders paid with a PayPal account.', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_paypal_verification',
							),
							// Prevent downloads if verification failed or still processing
							array(
								'title'       => __( 'Hold downloads until verified', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => 'wc_af_paypal_verification',
								'default'     => 'no',
								'desc' => __( 'Block downloadable products until PayPal verification succeeds or you clear the order manually.', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_paypal_prevent_downloads',
							),
							// Time span before further attempts
							array(
								'name'     => __( 'Days between reminder emails', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => __( 'Wait this many days before sending another reminder while the order is still awaiting verification.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_time_paypal_attempts',
								'css'         => 'display: block; width: 5em;',
								'default' => '2',
								'custom_attributes' => array(
									'min'  => 1,
									'step' => 1,
								),
							),
							// Time span before the orders are cancelled
							array(
								'name'     => __( 'Cancel unverified orders after (days)', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => __( 'Automatically cancel orders that stay unverified past this many days.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_day_deleting_paypal_order',
								'default' => '2',
								'css'       => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 1,
									'step' => 1,
								),
							),
							// PayPal verified addresses
							array(
								'name'        => __( 'Trusted PayPal emails', 'woocommerce-anti-fraud' ),
								'type'        => 'textarea',
								'desc'        => __( 'One email per line. Matches skip extra verification for those PayPal accounts.', 'woocommerce-anti-fraud' ),
								'id'          => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_paypal_verified_address',
								'class'         => 'wc_af_tags_input',
								'default'     => '',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_paypal_settings',
							),

							array(
								'name' => '',
								'type' => 'title',
								'desc' => '<hr>',
								'id'   => 'wc_af_paypal_hr_section',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_paypal_hr_section',
							),

							/* PayPal ACP start */
							array(
								'name' => __( 'PayPal payment attempt limits', 'woocommerce-anti-fraud' ),
								'type' => 'title',
								'desc' => __( 'Limit rapid PayPal and Braintree attempts to slow card attacks, card testing, and repeated fake checkouts.<hr/>', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_paypal_acp_settings',
							),
							array(
								'title'       => __( 'Enable PayPal payment attempt limits', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => __( 'Limit excessive PayPal attempts', 'woocommerce-anti-fraud' ),
								'desc' => __( '<br>Works with WooCommerce PayPal Payments, Braintree for WooCommerce, PayPal for WooCommerce, and similar gateways.', 'woocommerce-anti-fraud' ),
								'default'     => 'no',
								'id'          => 'wc_af_paypal_acp_enabled',  // <-- changed for clarity
							),

							// Rolling window time in seconds
							array(
								'name'     => __( 'Rolling window (seconds)', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => __( 'How long each sliding window lasts when counting PayPal attempts (default 300 = 5 minutes).', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_paypal_window_seconds',
								'css'      => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 1,
									'step' => 1,
								),
							),
							// Maximum attempts per window
							array(
								'name'     => __( 'Max attempts per window', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => __( 'Block or throttle when PayPal attempts exceed this count inside the window (default 5).', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_paypal_max_per_window',
								'css'      => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 1,
									'step' => 1,
								),
							),
							// Maximum attempts per hour
							array(
								'name'     => __( 'Max attempts per hour', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => __( 'Additional hourly cap for PayPal attempts (default 5).', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_paypal_max_per_hour',
								'css'      => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 1,
									'step' => 1,
								),
							),

							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_paypal_acp_settings',
							),

							/* PayPal ACP start */
							array(
								'name' => '',
								'type' => 'title',
								'desc' => '<hr>',
								'id'   => 'wc_af_paypal_hr_section',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_paypal_hr_section',
							),

							/*END PayPal ACP */
							array(
								'name' => __( 'Email Template', 'woocommerce-anti-fraud' ),
								'type' => 'title',
								'desc' => __( 'Available placeholders:<br/><br/><b>{site_title}</b> — store name<br/><b>{site_email}</b> — admin email', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_paypal_email_settings',
							),

							// Email type
							array(
								'name'     => __( 'Email Type', 'woocommerce-anti-fraud' ),
								'type'     => 'select',
								'options'  => array(
									'html'        => __( 'HTML', 'woocommerce-anti-fraud' ),
									'text'       => __( 'Plain text', 'woocommerce-anti-fraud' ),
								),
								'desc'     => __( 'Send as HTML or plain text.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_paypal_email_format',
								'default' => 'html',
								'css'       => 'display: block; width: 5em;',
							),
							// Email subject
							array(
								'name'     => __( 'Email Subject', 'woocommerce' ),
								'desc'     => __( 'Subject line the customer sees.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_paypal_email_subject',
								'type'     => 'text',
								'placeholder' => __( '[{site_title}] Confirm your PayPal email address', 'woocommerce-anti-fraud' ),
							),
							// Email body
							array(
								'name'        => __( 'Email body', 'woocommerce-anti-fraud' ),
								'type'        => 'textarea',
								'desc'        => __( 'Email body sent to the customer.', 'woocommerce-anti-fraud' ),
								'id'          => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_email_body',
								'css'         => 'width:100%; height: 100px;',
								'default'     => __( 'Hi!We have received your order on {site_title}, but to complete we have to verify your PayPal email address.If you havent made or authorized any purchase, please, contact PayPal support service immediately,and email us to {site_email} for having your money back.', 'woocommerce-anti-fraud' ),
							),

							array(
								'type' => 'sectionend',
								'id' => 'wc_af_paypal_email_settings',
							),

						)
					);
					} else {
						/**
					* WCAF Filter Plugin Paypal Settings
					*
					* @param array $settings Array of the plugin settings
					*
					*@since 1.0.0
					*/
					$settings = apply_filters(
						'wc_af_paypal_settings',
						array(

							array(
								'name' => __( 'PayPal account verification', 'woocommerce-anti-fraud' ),
								'type' => 'title',
								'desc' => __( 'Applies when customers pay with their PayPal account (not guest card). Use these options to confirm the PayPal email before you ship or release downloads.<hr/>', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_paypal_settings',
							),
							array(
								'title'       => __( 'Require PayPal email verification', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => 'wc_af_paypal_verification',
								'default'     => 'no',
								'desc' => __( 'Send verification for orders paid with a PayPal account.', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_paypal_verification',
							),
							// Prevent downloads if verification failed or still processing
							array(
								'title'       => __( 'Hold downloads until verified', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => 'wc_af_paypal_verification',
								'default'     => 'no',
								'desc' => __( 'Block downloadable products until PayPal verification succeeds or you clear the order manually.', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_paypal_prevent_downloads',
							),
							// Time span before further attempts
							array(
								'name'     => __( 'Days between reminder emails', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => __( 'Wait this many days before sending another reminder while the order is still awaiting verification.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_time_paypal_attempts',
								'css'         => 'display: block; width: 5em;',
								'default' => '2',
								'custom_attributes' => array(
									'min'  => 1,
									'step' => 1,
								),
							),
							// Time span before the orders are cancelled
							array(
								'name'     => __( 'Cancel unverified orders after (days)', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => __( 'Automatically cancel orders that stay unverified past this many days.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_day_deleting_paypal_order',
								'default' => '2',
								'css'       => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 1,
									'step' => 1,
								),
							),
							// PayPal verified addresses
							array(
								'name'        => __( 'Trusted PayPal emails', 'woocommerce-anti-fraud' ),
								'type'        => 'textarea',
								'desc'        => __( 'One email per line. Matches skip extra verification for those PayPal accounts.', 'woocommerce-anti-fraud' ),
								'id'          => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_paypal_verified_address',
								'class'         => 'wc_af_tags_input',
								'default'     => '',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_paypal_settings',
							),

							array(
								'name' => '',
								'type' => 'title',
								'desc' => '<hr>',
								'id'   => 'wc_af_paypal_hr_section',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_paypal_hr_section',
							),

							/*END PayPal ACP */
							array(
								'name' => __( 'Email Template', 'woocommerce-anti-fraud' ),
								'type' => 'title',
								'desc' => __( 'Available placeholders:<br/><br/><b>{site_title}</b> — store name<br/><b>{site_email}</b> — admin email', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_paypal_email_settings',
							),

							// Email type
							array(
								'name'     => __( 'Email Type', 'woocommerce-anti-fraud' ),
								'type'     => 'select',
								'options'  => array(
									'html'        => __( 'HTML', 'woocommerce-anti-fraud' ),
									'text'       => __( 'Plain text', 'woocommerce-anti-fraud' ),
								),
								'desc'     => __( 'Send as HTML or plain text.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_paypal_email_format',
								'default' => 'html',
								'css'       => 'display: block; width: 5em;',
							),
							// Email subject
							array(
								'name'     => __( 'Email Subject', 'woocommerce' ),
								'desc'     => __( 'Subject line the customer sees.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_paypal_email_subject',
								'type'     => 'text',
								'placeholder' => __( '[{site_title}] Confirm your PayPal email address', 'woocommerce-anti-fraud' ),
							),
							// Email body
							array(
								'name'        => __( 'Email body', 'woocommerce-anti-fraud' ),
								'type'        => 'textarea',
								'desc'        => __( 'Email body sent to the customer.', 'woocommerce-anti-fraud' ),
								'id'          => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_email_body',
								'css'         => 'width:100%; height: 100px;',
								'default'     => __( 'Hi!We have received your order on {site_title}, but to complete we have to verify your PayPal email address.If you havent made or authorized any purchase, please, contact PayPal support service immediately,and email us to {site_email} for having your money back.', 'woocommerce-anti-fraud' ),
							),

							array(
								'type' => 'sectionend',
								'id' => 'wc_af_paypal_email_settings',
							),

						)
					);
					}
				} else if ( 'general' == $current_section ) {

					$generalSettingsArray = array(

						array(
							'name' => __( 'Core protection', 'woocommerce-anti-fraud' ),
							'type' => 'title',
							'desc' => __( '<p><strong>Seeing a card attack</strong> (automated card testing at checkout)? Start with <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=wc_af&section=card_attacks' ) ) . '">Card attacks</a>: Checkout CAPTCHA, Order attempt limit, Payment attempt limit, and cooldowns slow scripted attempts.</p><p>Then adjust thresholds and rules here. When you are ready, add MaxMind, Trust Swiftly, or other integrations—API keys stay on your site.</p>', 'woocommerce-anti-fraud' ),
							'id'   => 'wc_af_general_settings',
						),

						// thresholds settings
						array(
							'name' => __( 'Risk thresholds', 'woocommerce-anti-fraud' ),
							'type' => 'section',
							'desc' => __( 'Scores run from 0 (safest) to 100 (riskiest). Set where low, medium, and high risk begin; the preview updates as you change values.<br/>', 'woocommerce-anti-fraud' ),
							'desc_tip' => __( 'These boundaries decide how orders are labeled. Lower the medium threshold to flag more orders for review; raise the high threshold if too many orders look “high risk”.', 'woocommerce-anti-fraud' ),
							'id'   => 'wc_af_thresholds_settings',
							'class' => 'wc_af_sub-section',
							'css'   => 'display: block;',
						),
						array(
							'name'     => __( 'Medium risk starts at', 'woocommerce-anti-fraud' ),
							'type'     => 'number',
							'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_low_risk_threshold',
							'css'         => 'display: block; width: 5em;',
							'desc'  => '',
							'desc_tip'  => __( 'Scores at or above this number are labeled medium risk (unless they reach the high-risk threshold). Lower it to flag more orders for review.', 'woocommerce-anti-fraud' ),
							'default' => '25',
							'custom_attributes' => array(
								'min'  => 0,
								'step' => 1,
								'max'  => 100,
							),
						),
						array(
							'name'     => __( 'High risk starts at', 'woocommerce-anti-fraud' ),
							'type'     => 'number',
							'desc'  => '',
							'desc_tip'  => __( 'Scores at or above this number are labeled high risk. Raise it if too many orders are flagged high; lower it to treat more orders as critical.', 'woocommerce-anti-fraud' ),
							'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_higher_risk_threshold',
							'css'         => 'display: block; width: 5em;',
							'default' => '75',
							'custom_attributes' => array(
								'min'  => 0,
								'step' => 1,
								'max'  => 100,
							),
						),
						array(
							'title'       => __( 'Log debug messages', 'woocommerce-anti-fraud' ),
							'type'        => 'checkbox',
							'label'       => '',
							'desc_tip'       => __( 'Write detailed Anti-Fraud logs when troubleshooting. Turn off on production once you finish.', 'woocommerce-anti-fraud' ),
							'default'     => 'no',
							'id'    => 'wc_af_enable_debug_logging',
						),
						array(
							'type' => 'sectionend',
							'id' => 'wc_af_thresholds_settings',
						),


						// Order Origin Settings
						array(
							'name' => __( 'Order origin', 'woocommerce-anti-fraud' ),
							'type' => 'section',
							'desc'  => '',
							'desc_tip' => __( 'Use when you need to reject checkouts that do not declare a valid origin header.', 'woocommerce-anti-fraud' ),
							'id'   => 'wc_af_order_origin_settings',
							'class' => 'wc_af_sub-section',
							'css'   => 'display: block;',
						),
						array(
							'title'       => __( 'Block unknown origin', 'woocommerce-anti-fraud' ),
							'type'        => 'checkbox',
							'label'       => '',
							'default'     => 'no',
							'desc'        => '',
							'desc_tip'    => __( 'Stops checkout when the request origin cannot be validated—can block some API or headless flows if misconfigured.', 'woocommerce-anti-fraud' ),
							'id'    => 'wc_af_block_unknown_origin',
						),
						array(
							'type' => 'sectionend',
							'id' => 'wc_af_order_origin_settings',
						),

						// thresholds settings
						array(
							'name' => __( 'Pre-payment fraud check', 'woocommerce-anti-fraud' ),
							'type' => 'section',
							'desc'  => '',
							'desc_tip' => __( 'Runs scoring before payment so high-risk orders can be blocked before money is captured.', 'woocommerce-anti-fraud' ),
							'id'   => 'wc_af_pre_purchase_settings',
							'class' => 'wc_af_sub-section',
							'css'   => 'display: block;',
						),
						array(
							'title'       => __( 'Run check before payment', 'woocommerce-anti-fraud' ),
							'type'        => 'checkbox',
							'label'       => '',
							'default'     => 'no',
							'desc'        => '',
							'desc_tip'    => __( 'When enabled, high-risk scores can stop checkout using the message below.', 'woocommerce-anti-fraud' ),
							'id'    => 'wc_af_fraud_check_before_payment',
						),
						// Pre payment custom text
						array(
							'title'       => __( 'Message for blocked checkouts', 'woocommerce-anti-fraud' ),
							'type'        => 'textarea',
							'label'       => '',
							'desc'        => '',
							'default'     => __( 'This order cannot be completed for security reasons. Please contact the store if you believe this is a mistake.', 'woocommerce-anti-fraud' ),
							'css'         => 'width:100%; height: 100px;',
							'desc_tip' => __( 'Custom message shown to blocked users at checkout.', 'woocommerce-anti-fraud' ),
							'id'    => 'wc_af_pre_payment_message',
						),

						array(
							'type' => 'sectionend',
							'id' => 'wc_af_pre_purchase_settings',
						),

						array(
							'name' => __( 'Automatic order status', 'woocommerce-anti-fraud' ),
							'type' => 'section',
							'desc'  => '',
							'desc_tip' => __( 'Optionally put high-risk orders on hold or cancel them automatically based on score.', 'woocommerce-anti-fraud' ),
							'id'   => 'wc_af_order_status_settings',
							'class' => 'wc_af_sub-section',
							'css'   => 'display: block;',
						),
						array(
							'title'       => __( 'Adjust status from fraud score', 'woocommerce-anti-fraud' ),
							'type'        => 'checkbox',
							'label'       => '',
							'default'     => 'yes',
							'desc'       => '',
							'desc_tip' => __( 'Turn on to apply on-hold and cancel rules using the thresholds below.', 'woocommerce-anti-fraud' ),
							'id'    => 'wc_af_fraud_update_state',
						),

						array(
							'title'       => __( 'Allow custom status after review', 'woocommerce-anti-fraud' ),
							'type'        => 'checkbox',
							'label'       => '',
							'default'     => 'yes',
							'desc'       => '',
							'desc_tip' => __( 'Lets you map very high scores to a custom order status after manual review.', 'woocommerce-anti-fraud' ),
							'id'    => 'wc_af_fraud_custom_order_status',
						),
						
						array(
							'name'     => __( 'Cancel orders at score', 'woocommerce-anti-fraud' ),
							'type'     => 'select',
							'options'  => $score_options,
							'option'   => '',
							'desc'     => '',
							'desc_tip'     => __( 'Orders at or above this score are cancelled automatically. Set to 0 to disable auto-cancel.', 'woocommerce-anti-fraud' ),
							'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_cancel_score',
							'css'         => 'display: block; width: 5em;',
							'default' => '90',
						),
						array(
							'title'     => __( 'Put orders on hold at score', 'woocommerce-anti-fraud' ),
							'type'     => 'select',
							'options'  => $score_options,
							'desc'     => '',
							'desc_tip'     => __( 'Orders at or above this score go on hold for review. Set to 0 to disable auto hold.', 'woocommerce-anti-fraud' ),
							'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_hold_score',
							'css'         => 'display: block; width: 5em;',
							'default' => '70',
						),
						array(
							'type' => 'sectionend',
							'id' => 'wc_af_order_status_settings',
						),
						array(
							'type' => 'sectionend',
							'id' => 'wc_af_user_role_settings',
						),

						/* Auto order fraud check */
						array(
							'name' => __( 'Backfill scores for past orders', 'woocommerce-anti-fraud' ),
							'type' => 'section',
							'desc'  => '',
							'desc_tip' => __( 'Scores orders created before you installed Anti-Fraud so reporting stays consistent.', 'woocommerce-anti-fraud' ),
							'id'   => 'wc_af_enable_start_auto_fraud_check',
							'class' => 'wc_af_sub-section',
							'css'   => 'display: block;',
						),
						array(
							'title'       => __( 'Enable backfill scoring', 'woocommerce-anti-fraud' ),
							'type'        => 'checkbox',
							'label'       => '',
							'desc'        => '',
							'desc_tip'    => __( 'Runs the fraud engine on older orders so their risk labels update in the admin.', 'woocommerce-anti-fraud' ),
							'default'     => 'no',
							'id'    => 'wc_af_start_auto_fraud_check',
						),

						array(
							'name'     => __( 'Days of history to score', 'woocommerce-anti-fraud' ),
							'type'     => 'number',
							'desc'  => '',
							'desc_tip'  => __( 'How far back to include when backfilling (default 7).', 'woocommerce-anti-fraud' ),
							'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_auto_check_days',
							'css'         => 'display: block; width: 5em;',
							'default' => '7',
							'custom_attributes' => array(
								'min'  => 0,
								'step' => 1,
								'max'  => 365,
							),
						),

						array(
							'type' => 'sectionend',
							'id' => 'wc_af_enable_start_auto_fraud_check',
						),
						/* End */

						array(
							'name' => __( 'SMS verification (FraudLabs Pro)', 'woocommerce-anti-fraud' ),
							'type' => 'section',
							'description'  =>  __( '<span style="color: red;">Classic checkout only—Block Checkout support is planned.</span>', 'woocommerce-anti-fraud' ),
							'desc_tip' => __( 'Send a one-time code to the customer’s phone before the order completes.', 'woocommerce-anti-fraud' ),
							'id'   => 'wc_af_sms_verification_section',
							'class' => 'wc_af_sub-section',
							'css'   => 'display: block;',
						),
						array(
							'title'       => __( 'Require SMS verification', 'woocommerce-anti-fraud' ),
							'type'        => 'checkbox',
							'label'       => '',
							'desc'        => '',
							'desc_tip'    => __( 'Uses FraudLabs Pro—add the API key below.', 'woocommerce-anti-fraud' ),
							'default'     => 'no',
							'id'    => 'wc_af_enable_sms_verification',
						),
						array(
							'name'     => __( 'FraudLabs Pro API key', 'woocommerce-anti-fraud' ),
							'type'     => 'text',
							'desc'  => __( 'Paste the key from your FraudLabs Pro account.', 'woocommerce-anti-fraud' ) . sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( 'https://www.fraudlabspro.com/merchant/sms-verification-api' ), __( 'Get an API key', 'woocommerce-anti-fraud' ) ),
							'desc_tip'  => __( 'Required for SMS OTP delivery.', 'woocommerce-anti-fraud' ),
							'placeholder'  => __( 'Paste API key', 'woocommerce-anti-fraud' ),
							'id'    => 'wc_af_sms_fraudlabspro_api_key',
						),
						array(
							'type' => 'sectionend',
							'id' => 'wc_af_sms_verification_section',
						),
						/* Fraud checking on orders through API */
						array(
							'name' => __( 'REST API orders', 'woocommerce-anti-fraud' ),
							'type' => 'section',
							'desc'  => '',
							'desc_tip' => __( 'Control fraud scoring and throttling for orders created via the WooCommerce REST API.', 'woocommerce-anti-fraud' ),
							'id'   => 'wc_af_enable_api_fraud_check',
							'class' => 'wc_af_sub-section',
							'default'     => 'yes',
							'css'   => 'display: block;',
						),
						array(
							'title'       => __( 'Score REST API orders', 'woocommerce-anti-fraud' ),
							'type'        => 'checkbox',
							'label'       => '',
							'desc'        => __( 'API imports are often used for bulk fraud—keep scoring on unless you fully trust the source.', 'woocommerce-anti-fraud' ),
							'desc_tip'    => __( 'When enabled, REST API orders receive the same fraud checks as web orders.', 'woocommerce-anti-fraud' ),
							'default'     => 'yes',
							'id'    => 'wc_af_api_fraud_check',
						),
						array(
							'title'       => __( 'Throttle API order volume', 'woocommerce-anti-fraud' ),
							'type'        => 'checkbox',
							'label'       => '',
							'desc'        => '',
							'desc_tip'    => __( 'Caps how many API orders can be created per hour—useful if a key is abused.', 'woocommerce-anti-fraud' ),
							'default'     => 'no',
							'id'    => 'wc_af_throttle_api_based_orders_check',
						),
						array(
							'name'      => __( 'Max REST API orders per hour', 'woocommerce-anti-fraud' ),
							'type'      => 'number',
							'desc'      => __( 'Set to 0 to block all API-created orders until you raise the limit—safest if you do not use the API.', 'woocommerce-anti-fraud' ),
							'desc_tip'  => __( 'Maximum REST API orders accepted per hour (0 = none).', 'woocommerce-anti-fraud' ),
							'id'        => 'wc_af_max_orders_through_api_per_hour',
							'css'       => 'display: block; width: 5em;',
							'default'   => '0',
							'custom_attributes' => array(
								'min'  => 0,
								'step' => 1,
								'max'  => 999999999,
							),
						),

						array(
							'title'       => __( 'Trust specific REST API keys', 'woocommerce-anti-fraud' ),
							'type'        => 'checkbox',
							'label'       => '',
							'desc'        => '',
							'desc_tip'    => __( 'When enabled, only the keys you select below can bypass throttling rules.', 'woocommerce-anti-fraud' ),
							'default'     => 'no',
							'id'    => 'wc_af_enable_api_keys_whitelist',
						),

						array(
							'title'    => __( 'Trusted REST API keys', 'woocommerce-anti-fraud' ),
							'type'     => 'multiselect',
							'options'  => $availablRestAPIKeys,
							'desc'     => '',
							'desc_tip' => __( 'Hold Ctrl (Windows) or ⌘ (Mac) to select multiple keys.', 'woocommerce-anti-fraud' ),
							'id'       => 'wc_settings_anti_fraud_whitelist_restapi',
							'default'  => $wc_af_whitelist_restapi,
						),

						array(
							'type' => 'sectionend',
							'id' => 'wc_af_enable_api_fraud_check',
						),
						/* End */
						/* Debug Log check */
						array(
							'name' => __( 'Developer logging', 'woocommerce-anti-fraud' ),
							'type' => 'section',
							'desc'  => '',
							'desc_tip'       => __( 'Verbose logs for support—disable when you are done debugging.', 'woocommerce-anti-fraud' ),
							'id'   => 'wc_af_enable_debug_log_check',
							'class' => 'wc_af_sub-section',
							'css'   => 'display: block;',
						),
						array(
							'title'       => __( 'Write detailed debug log', 'woocommerce-anti-fraud' ),
							'type'        => 'checkbox',
							'label'       => '',
							'desc'        => '',
							'desc_tip'    => __( 'Stores verbose Anti-Fraud events—use only while troubleshooting performance or rule issues.', 'woocommerce-anti-fraud' ),
							'default'     => 'no',
							'id'    => 'wc_af_enable_log_check',
						),

						array(
							'type' => 'sectionend',
							'id' => 'wc_af_enable_debug_log_check',
						),
						/* End */

						/* Home insights settings */
						array(
							'name' => __( 'Home insights', 'woocommerce-anti-fraud' ),
							'type' => 'section',
							'desc'  => '',
							'desc_tip' => __( 'Controls for the integrated Anti-Fraud home (Dashboard tab) and its insights range.', 'woocommerce-anti-fraud' ),
							'id'   => 'wc_af_dashboard_settings',
							'class' => 'wc_af_sub-section',
							'css'   => 'display: block;',
						),
						array(
							'title'       => __( 'Use Dashboard as product home', 'woocommerce-anti-fraud' ),
							'type'        => 'checkbox',
							'label'       => '',
							'desc'        => '',
							'desc_tip'    => __( 'Dashboard is the unified Anti-Fraud home and control center.', 'woocommerce-anti-fraud' ),
							'default'     => 'yes',
							'id'    => 'wc_af_enable_dashboard',
						),
						array(
							'title'       => __( 'Default home insights date range', 'woocommerce-anti-fraud' ),
							'type'        => 'select',
							'options'     => array(
								'last_15_days' => __( 'Last 15 days', 'woocommerce-anti-fraud' ),
								'last_30_days' => __( 'Last 30 days', 'woocommerce-anti-fraud' ) . ' ' . __( '(recommended)', 'woocommerce-anti-fraud' ),
								'last_60_days' => __( 'Last 60 days', 'woocommerce-anti-fraud' ),
								'last_90_days' => __( 'Last 90 days', 'woocommerce-anti-fraud' ),
								'all_orders'   => __( 'All orders', 'woocommerce-anti-fraud' ) . ' ' . __( '(not recommended)', 'woocommerce-anti-fraud' ),
							),
							'desc'        => '',
							'desc_tip'    => __( 'Initial range for Anti-Fraud home insights. Shorter ranges load faster on large catalogs.', 'woocommerce-anti-fraud' ),
							'default'     => 'last_30_days',
							'id'          => 'wc_af_dashboard_date_range',
							'css'         => 'display: block; width: 15em;',
						),
						array(
							'type' => 'sectionend',
							'id' => 'wc_af_dashboard_settings',
						),
						/* End */

						array(
							'type' => 'sectionend',
							'id' => 'wc_af_general_settings',
						),

					);

					/**
					* WCAF Filter Plugin General Settings
					*
					* @param array $settings Array of the plugin settings
					*
					*@since 1.0.0
					*/
					$settings = apply_filters( 'wc_af_general_settings', $generalSettingsArray );

				} else if ( 'card_attacks' == $current_section ) {

					$generalSettingsArray = array(

						array(
							'name' => __( 'Card testing protection', 'woocommerce-anti-fraud' ),
							'type' => 'title',
							'desc' => __( 'Use during a card attack or heavy card testing. Review status below, then adjust Checkout CAPTCHA, Order attempt limit, and Payment attempt limit.', 'woocommerce-anti-fraud' ),
							'id'   => 'wc_af_card_attacks_settings',
						),

							// Section - Checkout CAPTCHA
							array(
								'name' => __( 'Checkout CAPTCHA', 'woocommerce-anti-fraud' ),
								'type' => 'section',
								'desc' => __( 'Turn on Checkout CAPTCHA first when you see a card attack or heavy card testing.', 'woocommerce-anti-fraud' ),
								'desc_tip' => __( 'Combine Checkout CAPTCHA, order attempt limits, payment attempt limits, and delays to slow scripted card testing.', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_thresholds_settings',
								'class' => 'wc_af_sub-section',
								'css'   => 'display: block;',
							),

							// Section - Order Attempts
							array(
								'name' => __( 'Order attempt limits', 'woocommerce-anti-fraud' ),
								'type' => 'section',
								'desc' => '',
								'desc_tip' => __( 'Counts how many orders a buyer places in your window. Helps enforce your order attempt limit during card attacks and card testing.', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_order_attempts_rules_settings',
								'class' => 'wc_af_sub-section',
								'css'   => 'display: block;',
							),
							array(
								'title'       => __( 'Limit orders per buyer', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc' => '',
								'desc_tip' => __( 'Caps how many orders each customer can place inside the window below.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_attempt_count_check',
							),
							array(
								'name'     => __( 'Rolling window (hours) <span id="many_astric_required">*</span>', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => '',
								'desc_tip'     => __( 'How long each counting window lasts.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_attempt_time_span',
								'css'         => 'display: block; width: 5em;',
								'default' => '24',
								'custom_attributes' => array(
									'min'  => 1,
									'step' => 1,
								),
							),
							array(
								'name'     => __( 'Max orders per buyer in window <span id="many_astric_required">*</span>', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => '',
								'desc_tip'     => __( 'Orders above this count inside the window are blocked or throttled.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_max_order_attempt_time_span',
								'css'         => 'display: block; width: 5em;',
								'default' => '5',
								'custom_attributes' => array(
									'min'  => 1,
									'step' => 1,
								),
							),
							array(
								'title'       => __( 'Order attempt counting', 'woocommerce-anti-fraud' ),
								'type'        => 'select',
								'desc'        => __( 'Orders only: counts placed orders by IP, email, and phone. Advanced: also uses failed payments and broader signals to catch rotated card testing.', 'woocommerce-anti-fraud' ),
								'desc_tip'    => __( 'Chooses what counts toward the order attempt limit. Start with “Orders only”. Use Advanced when attackers change IPs, emails, or cards (a common velocity attack pattern).', 'woocommerce-anti-fraud' ),
								'id'          => 'wc_af_attempt_count_mode',
								'default'     => 'orders_only',
								'options'     => array(
									'orders_only' => __( 'WooCommerce orders only (IP, email, phone)', 'woocommerce-anti-fraud' ),
									'advanced'    => __( 'Advanced (orders + payment attempts)', 'woocommerce-anti-fraud' ),
								),
							),
							array(
								'type' => 'sectionend',
								'id' => 'wc_af_order_attempts_rules_settings',
							),

							// Section - Payment Attempts
							array(
								'name' => __( 'Failed payment handling', 'woocommerce-anti-fraud' ),
								'type' => 'section',
								'id'   => 'wc_af_order_payment_attempts_settings',
								'class' => 'wc_af_sub-section',
								'desc_tip' => __( 'Slows repeat declines and caps retries per order.', 'woocommerce-anti-fraud' ),
								'css'   => 'display: block;',
							),
							array(
								'title'       => __( 'Progressive checkout delay', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc_tip' => __( 'Use at least 10 seconds between steps so automated retries cannot hammer checkout.', 'woocommerce-anti-fraud' ),
								'desc' => __( 'Each failed payment adds a longer wait before “Place order” works again (for example 10s, then 20s, then 30s).', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_enable_checkout_waiting_time',
							),
							array(
								'title'       => __( 'Cap failed payments per order', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc' => '',
								'desc_tip' => __( 'When on, checkout blocks after the number of failed payment attempts below.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_order_payment_attempt_check',
							),
							array(
								'name'     => __( 'Max failed payments per order <span id="payment_astric_required">*</span>', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => '',
								'desc_tip'     => __( 'After this many declines on one order, checkout is blocked until the cart resets or you intervene.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_max_order_payment_attempt',
								'css'         => 'display: block; width: 5em;',
								'default' => '3',
								'custom_attributes' => array(
									'min'  => 1,
									'step' => 1,
								),
							),
							array(
								'type' => 'sectionend',
								'id' => 'wc_af_order_payment_attempts_settings',
							),

							// Section - Orders Between Times
							array(
								'name' => __( 'Time-of-day order cap', 'woocommerce-anti-fraud' ),
								'type' => 'section',
								'id'   => 'wc_af_order_between_times_settings',
								'desc_tip' => __( 'Optional: limit total checkout volume during specific hours (uses your WordPress timezone).', 'woocommerce-anti-fraud' ),
								'class' => 'wc_af_sub-section',
								'css'   => 'display: block;',
							),
							array(
								'title'       => __( 'Limit orders during selected hours', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'no',
								'desc' => '',
								'desc_tip' => __( 'When enabled, the store rejects orders after the hourly cap between the start and end times.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_limit_order_count',
							),
							array(
								'name'     => __( 'Start Time <span id="limit_astric_required">*</span>', 'woocommerce-anti-fraud' ),
								'type'     => 'time',
								'desc'     => '',
								'desc_tip'     => __( 'Uses the WordPress timezone (Settings → General).', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_af_limit_time_start',
								'css'         => 'display: block; width: 8.5em;',
								'default' => '',
								'required'  => 'required',
							),
							array(
								'name'     => __( 'End Time <span id="limit_astric_required">*</span>', 'woocommerce-anti-fraud' ),
								'type'     => 'time',
								'desc'     => '',
								'desc_tip' => __( 'Uses the WordPress timezone (Settings → General).', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_af_limit_time_end',
								'css'         => 'display: block; width: 8.5em;',
								'default' => '',
								'required'  => 'required',
								
							),
							array(
								'name'     => __( 'Max orders allowed in that window <span id="limit_astric_required">*</span>', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => '',
								'desc_tip'     => __( 'Total checkout attempts allowed between start and end time.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_af_allowed_order_limit',
								'css'         => 'display: block; width: 5em;',
								'default' => '5',
								'custom_attributes' => array(
									'min'  => 1,
									'step' => 1,
								),
							),
							array(
								'type' => 'sectionend',
								'id' => 'wc_af_order_between_times_settings',
							),

							// array(
							// 	'type' => 'sectionend',
							// 	'id' => 'wc_af_order_attempts_rules_settings',
							// ),

						array(
							'type' => 'sectionend',
							'id' => 'wc_af_card_attacks_settings',
						),

					);

					/**
					* WCAF Filter Plugin General Settings
					*
					* @param array $settings Array of the plugin settings
					*
					*@since 1.0.0
					*/
					$settings = apply_filters( 'wc_af_general_settings', $generalSettingsArray );

				} else if ( 'cleanup' == $current_section ) {
					// Fallback options (used if the settings array isn't present).
					$default_options = array(
						'selecttime'  => __( 'Select timeframe', 'woocommerce-anti-fraud' ),
						'3_hour'  => __( 'Last 3 Hours', 'woocommerce-anti-fraud' ),
						'6_hour'  => __( 'Last 6 Hours', 'woocommerce-anti-fraud' ),
						'12_hour'  => __( 'Last 12 Hours', 'woocommerce-anti-fraud' ),
						'24_hour' => __( 'Last 1 Day', 'woocommerce-anti-fraud' ),
						'2_days'  => __( 'Last 2 Days', 'woocommerce-anti-fraud' ),
						'3_days'  => __( 'Last 3 Days', 'woocommerce-anti-fraud' ),
						'4_days'  => __( 'Last 4 Days', 'woocommerce-anti-fraud' ),
						'5_days'  => __( 'Last 5 Days', 'woocommerce-anti-fraud' ),
					);
					$generalSettingsArray = array(

							array(
								'name' => __( 'Failed orders & cleanup', 'woocommerce-anti-fraud' ),
								'type' => 'title',
								'desc_tip' => __( 'Bulk-delete failed orders after a card attack or heavy card testing; optionally quiet failure emails.', 'woocommerce-anti-fraud' ),
								'desc' => __( 'Delete failed-payment orders in bulk and optionally silence failure emails so inboxes stay usable.<hr/>', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_order_cleanup_settings',
								'class' => 'cleanup-setting'
							),

								// Failed orders cleanup — date range
							array(
								'title'    => __( 'Delete failed orders from', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_af_cleanup_timeframe',
								'type'     => 'select',
								'options'  => $default_options,
								'default'  => 'selecttime',
								'desc_tip' => __( 'Only failed orders created within this window are removed when you run the action.', 'woocommerce-anti-fraud' ),
								'desc'     => __( 'Choose how far back to include when deleting failed orders.', 'woocommerce-anti-fraud' ),
							),

							array(
								'title'    => __( 'OPMC chargeback program', 'woocommerce-anti-fraud' ),
								'name' => __( 'Open OPMC chargeback form', 'woocommerce-anti-fraud' ),
								'type' => 'button',
								'desc_tip'    => __( 'Optional: separate Kount/OPMC onboarding—not required for cleanup.', 'woocommerce-anti-fraud' ),
								'desc' => __( 'Opens the secure onboarding form in a new tab.', 'woocommerce-anti-fraud' ),
								'class' => 'button-secondary',
								'href'  => 'https://opmc.com.au/chargeback-management-kount/',
								'target'=>'_blank',
								'rel'=>'noopener noreferrer',
								'id'    => 'wc_af_order_cleanup_action',
							),

							array(
								'title'       => __( 'Stop failed-payment emails', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'no',
								'desc'        => __( 'Stops customer and store emails when a payment fails.', 'woocommerce-anti-fraud' ),
								'desc_tip' => __( 'Use during card testing or a velocity attack when inboxes are flooded. Turn off again when you want normal failure notifications.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_stop_send_mail_failed_status',
							),

						array(
							'type' => 'sectionend',
							'id' => 'wc_af_order_cleanup_settings',
						),

					);

					/**
					* WCAF Filter Plugin General Settings
					*
					* @param array $settings Array of the plugin settings
					*
					*@since 1.0.0
					*/
					$settings = apply_filters( 'wc_af_general_settings', $generalSettingsArray );

				} else if ( 'rules' == $current_section ) {
					/**
					* WCAF Filter Plugin General Settings
					*
					* @param array $settings Array of the plugin settings
					*
					*@since 1.0.0
					*/
					$settings = apply_filters(
						'wc_af_rule_settings',
						array(

							array(
								'name' => __( 'Rule weights', 'woocommerce-anti-fraud' ),
								'type' => 'title',
								'desc' => __( 'Each active rule adds weight (points) to the order score (0–100). Increase weights for signals you care about; set to 0 or turn the rule off to ignore it.<hr/>', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_rule_settings',
							),

							array(
								'name' => __( 'First-time buyers', 'woocommerce-anti-fraud' ),
								'type' => 'section',
								'desc' => __( 'Signals for customers who have not ordered from you before.', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_first_time_purchase_settings',
								'class' => 'wc_af_sub-section',
								'css'   => 'display: block;',
							),

							array(
								'title'       => __( 'First-time buyer', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc'        => '',
								'desc_tip' => __( 'Adds risk when the email or customer has no prior completed orders.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_first_order',
							),
							array(
								'name'     => __( 'First-time buyer — points', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc'     => '',
								'desc_tip' => __( 'Points added when the first-time buyer rule matches.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_first_order_weight',
								'css'         => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),
							array(
								'title'       => __( 'Re-score first orders in Processing', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'no',
								'desc' => '',
								'desc_tip' => __( 'Runs the first-time buyer rule again while the order is still processing—useful if data arrives late.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_first_order_custom',
							),
							array(
								'name'     => __( 'Processing re-check — points', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc'     => '',
								'desc_tip' => __( 'Points added when the re-score rule matches for orders still in Processing.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_first_order_custom_weight',
								'css'         => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),

							array(
								'type' => 'sectionend',
								'id' => 'wc_af_first_time_purchase_settings',
							),

							array(
								'name' => __( 'IP and address checks', 'woocommerce-anti-fraud' ),
								'type' => 'section',
								'desc' => '',
								'desc_tip' => __( 'Geo, proxy, and mismatch rules that look at IP, billing, and shipping data.', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_address_based_rules_settings',
								'class' => 'wc_af_sub-section',
								'css'   => 'display: block;',
							),

							/* Geo Location */
							array(
								'title'       => __( 'Billing region matches geo location', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc' => '',
								'desc_tip' => __( 'Flags orders where the billing region does not match the location inferred from the customer IP.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_geolocation_order',
							),
							array(
								'name'     => __( 'Points if matched', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc'     => '',
								'desc_tip' => __( 'Points added when billing region and geo IP do not match.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_geolocation_order_weight',
								'css'         => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),

							array(
								'title'       => __( 'BigDataCloud API key', 'woocommerce-anti-fraud' ),
								'type'        => 'password',
								'desc' => __( 'No account yet? <a href="https://www.bigdatacloud.com/" target="_blank">Create one</a> — free tier includes many requests per month.', 'woocommerce-anti-fraud' ),
								'desc_tip' => __( 'Used to resolve IP to region for the billing vs geo rule above.', 'woocommerce-anti-fraud' ),
								'id'    => 'bigdatacloud_api_key',
							),
							/* Geo Location End*/

							array(
								'title'       => __( 'Billing vs shipping address mismatch', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc' => '',
								'desc_tip' => __( 'Adds risk when billing and shipping addresses differ.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_bca_order',
							),
							array(
								'name'     => __( 'Points if matched', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc'     => '',
								'desc_tip' => __( 'Points added when billing and shipping differ.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_bca_order_weight',
								'css'         => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),
							array(
								'title'       => __( 'Phone country vs billing country', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'no',
								'desc'        => '',
								'desc_tip'    => __( 'Flags mismatches between phone country code and billing country. Use consistent international phone formatting at checkout (a phone validation plugin helps).', 'woocommerce-anti-fraud' ),
								'id'          => 'wc_af_billing_phone_number_order',

							),

							array(
								'name'     => __( 'Points if matched', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc'     => __( 'How many points this rule adds when it triggers.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_billing_phone_number_order_weight',
								'css'         => 'display: block;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),
							array(
								'title'       => __( 'Proxy or VPN', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc' => '',
								'desc_tip' => __( 'Adds risk when the visitor appears to use a proxy or VPN.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_proxy_order',
							),
							array(
								'name'     => __( 'Points if matched', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc'     => '',
								'desc_tip' => __( 'Points added when proxy/VPN is detected.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_proxy_order_weight',
								'css'         => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),

							array(
								'type' => 'sectionend',
								'id' => 'wc_af_address_based_rules_settings',
							),

							array(
								'name' => __( 'Same IP, different addresses', 'woocommerce-anti-fraud' ),
								'type' => 'section',
								'desc' => '',
								'desc_tip' => __( 'Flags many orders from one IP shipping to different addresses—common in testing or reshipping fraud.', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_multi_order_attempts_rules_settings',
								'class' => 'wc_af_sub-section',
								'css'   => 'display: block;',
							),
							array(
								'title'       => __( 'Flag same IP, different addresses', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc' => '',
								'desc_tip' => __( 'Uses the lookback window below to compare orders.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_ip_multiple_check',
							),
							array(
								'name'     => __( 'Points if matched', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc'     => '',
								'desc_tip' => __( 'Points added when the same IP uses many different addresses in the lookback window.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_ip_multiple_weight',
								'css'         => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),
							array(
								'name'     => __( 'Lookback (days)', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => '',
								'desc_tip'     => __( 'How far back to compare orders from the same IP.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_ip_multiple_time_span',
								'css'         => 'display: block; width: 5em;',
								'default' => '2',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
								),
							),

							array(
								'type' => 'sectionend',
								'id' => 'wc_af_multi_order_attempts_rules_settings',
							),

							array(
								'name' => __( 'Country of origin', 'woocommerce-anti-fraud' ),
								'type' => 'section',
								'desc' => '',
								'desc_tip' => __( 'Score orders from abroad or from countries you mark as higher risk.', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_origin_countries_rules_settings',
								'class' => 'wc_af_sub-section',
								'css'   => 'display: block;',
							),
							array(
								'title'       => __( 'International order', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc' => '',
								'desc_tip' => __( 'Adds risk when the order is not from your store’s base country.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_international_order',
							),
							array(
								'name'     => __( 'Points if matched', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc'     => '',
								'desc_tip' => __( 'Points added for international orders (vs your store base country).', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_international_order_weight',
								'css'         => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),
							array(
								'title'       => __( 'High-risk country list', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc' => '',
								'desc_tip' => __( 'Adds risk when the order comes from a country you select below.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_unsafe_countries',
							),
							array(
								'name'     => __( 'Points if matched', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc'     => '',
								'desc_tip' => __( 'Points added when the order is from a country you marked as high-risk.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_unsafe_countries_weight',
								'css'         => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),
							array(
								'name'        => __( 'High-risk countries', 'woocommerce-anti-fraud' ),
								'type'        => 'multiselect',
								'desc'        => '',
								'desc_tip'    => __( 'Ctrl/Cmd+click to select multiple countries.', 'woocommerce-anti-fraud' ),
								'id'          => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_define_unsafe_countries_list',
								'class'        => 'chzn-drop',
								'options'      => $this->get_countries(),
								'default'      => $wc_af_unsafe_countries_list,
							),

							array(
								'type' => 'sectionend',
								'id' => 'wc_af_origin_countries_rules_settings',
							),

							array(
								'name' => __( 'Risky email domains', 'woocommerce-anti-fraud' ),
								'type' => 'section',
								'desc' => '',
								'desc_tip' => __( 'Flag disposable or suspicious domains manually, or use QuickEmailVerification for automatic checks.', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_high_risk_domain_rules_settings',
								'class' => 'wc_af_sub-section',
								'css'   => 'display: block;',
							),
							array(
								'title'       => __( 'Suspicious email domain', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc' => '',
								'desc_tip' => __( 'Uses your domain list and/or QuickEmailVerification when an API key is set.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_suspecius_email',
							),
							array(
								'name'     => __( 'Points if matched', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc'     => '',
								'desc_tip' => __( 'Points added when the email domain looks high-risk.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_suspecious_email_weight',
								'css'         => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),
							array(
								'name'        => __( 'Domains to flag', 'woocommerce-anti-fraud' ),
								'type'        => 'textarea',
								'desc'        => '',
								'desc_tip'    => __( 'One domain per line or comma-separated (e.g. tempmail.com).', 'woocommerce-anti-fraud' ),
								'id'          => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_suspecious_email_domains',
								'css'         => 'width:100%; height: 100px;',
								'default'     => $this->suspicious_domains(),
								'class'       => 'wc_af_tags_input',
							),
							array(
								'title'       => __( 'QuickEmailVerification API key', 'woocommerce-anti-fraud' ),
								'type'        => 'password',
								'desc' => __( 'Optional. Create an account at quickemailverification.com for automated domain reputation checks.', 'woocommerce-anti-fraud' ),
								'desc_tip' => __( 'Improves detection of risky or disposable domains beyond the manual list.', 'woocommerce-anti-fraud' ),
								'id'    => 'check_email_domain_api_key',
							),

							array(
								'type' => 'sectionend',
								'id' => 'wc_af_high_risk_domain_rules_settings',
							),

							array(
								'name' => __( 'Order totals', 'woocommerce-anti-fraud' ),
								'type' => 'section',
								'desc' => '',
								'desc_tip' => __( 'Rules based on cart total vs your average or a fixed ceiling.', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_order_amount_attempts_rules_settings',
								'class' => 'wc_af_sub-section',
								'css'   => 'display: block;',
							),
							array(
								'title' => __( 'Order far above store average', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc' => '',
								'desc_tip' => __( 'Triggers when the order total is above your historical average times the multiplier below.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_order_avg_amount_check',
							),
							array(
								'name'     => __( 'Points if matched', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc'     => '',
								'desc_tip' => __( 'Points added when the order total is far above your store average.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_order_avg_amount_weight',
								'css'         => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),
							array(
								'name'     => __( 'Multiplier vs average', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => '',
								'desc_tip'     => __( 'Example: 2 means “more than double your typical order value”.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_avg_amount_multiplier',
								'css'         => 'display: block; width: 5em;',
								'default' => '2',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
								),
							),
							array(
								'title'       => __( 'Order over maximum total', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc' => '',
								'desc_tip' => __( 'Triggers when the order total is above the ceiling you set below.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_order_amount_check',
							),
							array(
								'name'     => __( 'Points if matched', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'options'  => $rule_weight,
								'desc'     => '',
								'desc_tip' => __( 'Points added when the order exceeds your configured store limit.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_order_amount_weight',
								'css'         => 'display: block; width: 5em;',
								'custom_attributes' => array(
									'min'  => 0,
									'step' => 1,
									'max'  => 100,
								),
							),
							array(
								'name'     => __( 'Maximum order total', 'woocommerce-anti-fraud' ),
								'type'     => 'text',
								'desc'     => '',
								'desc_tip' => __( 'In your store currency. Orders above this add risk (0 may disable depending on your setup).', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_amount_limit',
								'css'         => 'display: block; width: 5em;',
								'default' => '0',
							),

							array(
								'type' => 'sectionend',
								'id' => 'wc_af_order_amount_attempts_rules_settings',
							),

							array(
								'name' => __( 'Order attempt limits', 'woocommerce-anti-fraud' ),
								'type' => 'section',
								'desc' => '',
								'desc_tip' => __( 'Counts how many orders a buyer places in your window. Helps enforce your order attempt limit during card attacks and card testing.', 'woocommerce-anti-fraud' ),
								'id'   => 'wc_af_order_attempts_rules_settings',
								'class' => 'wc_af_sub-section',
								'css'   => 'display: block;',
							),
							array(
								'title' => __( 'Limit orders per buyer', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc' => '',
								'desc_tip' => __( 'Caps how many orders each customer can place in the rolling window below.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_attempt_count_check',
							),
							array(
								'name'     => __( 'Rolling window (hours) <span id="many_astric_required">*</span>', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => '',
								'desc_tip'     => __( 'How long each counting window lasts.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_attempt_time_span',
								'css'         => 'display: block; width: 5em;',
								'default' => '24',
								'custom_attributes' => array(
									'min'  => 1,
									'step' => 1,
								),
							),
							array(
								'name'     => __( 'Max orders per buyer in window <span id="many_astric_required">*</span>', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => '',
								'desc_tip'     => __( 'Orders above this count inside the window are blocked or throttled.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_max_order_attempt_time_span',
								'css'         => 'display: block; width: 5em;',
								'default' => '5',
								'custom_attributes' => array(
									'min'  => 1,
									'step' => 1,
								),
							),
							array(
								'title'       => __( 'Order attempt counting', 'woocommerce-anti-fraud' ),
								'type'        => 'select',
								'desc'        => __( 'Orders only: counts placed orders by IP, email, and phone. Advanced: also uses failed payments and broader signals to catch rotated card testing.', 'woocommerce-anti-fraud' ),
								'desc_tip'    => __( 'Chooses what counts toward the order attempt limit. Start with “Orders only”. Use Advanced when attackers change IPs, emails, or cards (a common velocity attack pattern).', 'woocommerce-anti-fraud' ),
								'id'          => 'wc_af_attempt_count_mode',
								'default'     => 'orders_only',
								'options'     => array(
									'orders_only' => __( 'WooCommerce orders only (IP, email, phone)', 'woocommerce-anti-fraud' ),
									'advanced'    => __( 'Advanced (orders + payment attempts)', 'woocommerce-anti-fraud' ),
								),
							),

							array(
								'title'       => __( 'Limit failed payment attempts per order', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'yes',
								'desc' => '',
								'desc_tip' => __( 'When on, checkout stops after too many declined payments on the same order.', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_order_payment_attempt_check',
							),
							array(
								'name'     => __( 'Max failed payments per order <span id="payment_astric_required">*</span>', 'woocommerce-anti-fraud' ),
								'type'     => 'number',
								'desc'     => '',
								'desc_tip'     => __( 'After this many declines, the customer must start over (new cart or your help).', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_max_order_payment_attempt',
								'css'         => 'display: block; width: 5em;',
								'default' => '3',
								'custom_attributes' => array(
									'min'  => 1,
									'step' => 1,
								),
							),

							array(
								'type' => 'sectionend',
								'id' => 'wc_af_order_attempts_rules_settings',
							),

							array(
								'type' => 'sectionend',
								'id' => 'wc_af_rule_settings',
							),

						)
					);

				} else if ( 'ai_fraud_prevention' == $current_section ) {

					/**
					* WCAF Filter Plugin  MinFraud Settings
					*
					* @param array $settings Array of the plugin settings
					*
					*@since 1.0.0
					*/
					$settings = apply_filters(
						'ai_fraud_prevention',
						array(
							array(
								'name'     => __( 'AI-assisted signals (OpenAI)', 'woocommerce-anti-fraud' ),
								'type'     => 'title',
								'desc'     => __( 'Optional: sends limited order context to OpenAI for a short risk note in the admin. Review OpenAI’s data policy before enabling.<hr/>', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_af_ai_fraud_prevention_settings',
							),
							array(
								'title'       => __( 'Enable AI hints on orders', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'no',
								'desc'        => '',
								'desc_tip'    => __( 'Shows an AI-generated risk note on the order screen when your API key is valid.', 'woocommerce-anti-fraud' ),
								'id'          => 'wc_af_ai_fraud_prevention_check',
							),
							array(
								'name'     => __( 'OpenAI API key', 'woocommerce-anti-fraud' ),
								'type'     => 'password',
								'desc'  => __( 'Create a key in your OpenAI account.', 'woocommerce-anti-fraud' ) . sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( 'https://platform.openai.com/account/api-keys' ), __( 'OpenAI API keys', 'woocommerce-anti-fraud' ) ),
								'desc_tip'  => __( 'Stored in your database—restrict who can access these settings.', 'woocommerce-anti-fraud' ),
								'placeholder'  => __( 'sk-…', 'woocommerce-anti-fraud' ),
								'id'    => 'wc_af_chatgpt_api_key',
							),
							
							array(
								'name'     => __( 'OpenAI model', 'woocommerce-anti-fraud' ),
								'type'     => 'select',
								'options'  => $chatGptModels,
								'desc'     => '',
								'desc_tip'     => __( 'Newer models cost more per request; pick one that fits your budget.', 'woocommerce-anti-fraud' ),
								'id'       => 'wc_af_ai_model',
								'css'         => 'display: block; width: 10em;',

							),
							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_ai_fraud_prevention_settings',
							),
						)
					);
				} else if ( '' == $current_section ) {

					/**
					* WCAF Filter Plugin  MinFraud Settings
					*
					* @param array $settings Array of the plugin settings
					*
					*@since 1.0.0
					*/
					$settings = apply_filters(
						'home_settings',
						array(
							array(
								'name'     => __( 'Overview', 'woocommerce-anti-fraud' ),
								'type'     => 'title',
								'desc'     => '',
								'id'       => 'wc_af_home_settings',
							),
							array(
								'title'       => __( 'AI fraud insights (shortcut)', 'woocommerce-anti-fraud' ),
								'type'        => 'checkbox',
								'label'       => '',
								'default'     => 'no',
								'desc'        => '',
								'desc_tip'    => __( 'Matches the AI fraud signals section. Add your API key there to enable this shortcut.', 'woocommerce-anti-fraud' ),
								'id'          => 'wc_af_ai_fraud_prevention_check',
							),
							
							array(
								'type' => 'sectionend',
								'id'   => 'wc_af_home_option_settings',
							),
						)
					);
				}


				//update_option( 'wc_af_is_settings_saved', true);
				/**
				 * Filter WCAF Settings
				 *
				 * @param array $settings Array of the plugin settings
				 *
				 *@since 1.0.0
				 */
				return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );

			}

			public function opmc_add_admin_field_button( $value ) {
				$option_value = (array) WC_Admin_Settings::get_option( $value['id'] );
				$description = WC_Admin_Settings::get_field_description( $value );

				?>
			   
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $value['id'] ); ?>">
							<?php echo esc_html( $value['title'] ); ?>
						</label>
						<?php if ( isset( $value['title_icon'] ) && '' != $value['title_icon'] ) : ?>
							<img src="<?php echo esc_attr( $value['title_icon'] ); ?>" alt="<?php echo esc_html( $value['title'] ); ?>" style="width:100px;">
						<?php endif; ?>
					</th>
					
					<td class="forminp forminp-<?php echo esc_attr( $value['type'] ); ?>">
						<a 
							target="_blank"
							href="<?php echo esc_attr( $value['href'] ); ?>" 
							class="<?php echo esc_attr( $value['class'] ); ?>" 
							id="<?php echo esc_attr( $value['id'] ); ?>"							
							><?php echo esc_attr( $value['name'] ); ?></a> 
						<?php echo wp_kses_post( $description['description'] ); ?>
					   
					</td>
				</tr>

				<?php
			}

			public function render_help_cards_field( $value ) {
				$value = wp_parse_args(
					$value,
					array(
						'id'    => '',
						'cards' => array(),
					)
				);

				$cards = isset( $value['cards'] ) && is_array( $value['cards'] ) ? $value['cards'] : array();
				?>
				<tr valign="top">
					<td colspan="2" class="forminp forminp-help_cards">
						<div<?php echo '' !== $value['id'] ? ' id="' . esc_attr( $value['id'] ) . '"' : ''; ?> class="wc-af-help-grid">
							<?php foreach ( $cards as $card ) : ?>
								<?php
								$card = wp_parse_args(
									$card,
									array(
										'title'        => '',
										'text'         => '',
										'action_label' => '',
										'url'          => '',
										'action_class' => '',
									)
								);

								$action_classes = trim( 'button button-secondary wc-af-btn wc-af-btn--secondary wc-af-help-card__action ' . $card['action_class'] );
								?>
								<section class="wc-af-help-card">
									<?php if ( '' !== $card['title'] ) : ?>
										<h3 class="wc-af-help-card__title"><?php echo esc_html( $card['title'] ); ?></h3>
									<?php endif; ?>

									<?php if ( '' !== $card['text'] ) : ?>
										<p class="wc-af-help-card__text"><?php echo esc_html( $card['text'] ); ?></p>
									<?php endif; ?>

									<?php if ( '' !== $card['action_label'] && '' !== $card['url'] ) : ?>
										<p class="wc-af-help-card__action-wrap">
											<a
												class="<?php echo esc_attr( $action_classes ); ?>"
												href="<?php echo esc_url( $card['url'] ); ?>"
												target="_blank"
												rel="noopener noreferrer"
											><?php echo esc_html( $card['action_label'] ); ?></a>
										</p>
									<?php endif; ?>
								</section>
							<?php endforeach; ?>
						</div>
					</td>
				</tr>
				<?php
			}

			public function opmc_add_admin_field_section( $value ) {

				// Define all expected keys to prevent "Undefined array key" warnings
				$value = wp_parse_args(
					$value,
					array(
						'id'          => '',
						'name'        => '',
						'title'       => '',
						'class'       => '',
						'desc'        => '',
						'desc_tip'    => '',
						'description' => '',
						'type'        => '',
					)
				);

				$option_value = (array) WC_Admin_Settings::get_option( $value['id'] );

				// Safe call after keys exist
				$description = WC_Admin_Settings::get_field_description( $value );
				?>
				</table>
				<div class="wc_af_sub-section-title wc-af-settings-subheading-wrap wc-af-settings-subheading-wrap--section">
					<h3 class="<?php echo esc_attr( $value['class'] ); ?> wc-af-settings-subheading" id="<?php echo esc_attr( $value['id'] ); ?>">
						<?php echo wp_kses_post( $value['name'] ); ?> 
						<?php echo wp_kses_post( $description['tooltip_html'] ); ?>
					</h3>

					<?php if ( ! empty( $value['description'] ) ) : ?>
						<p class="wc-af-subsection-desc"><?php echo wp_kses_post( $value['description'] ); ?></p>
					<?php endif; ?>
				</div>
				<table class="form-table opmc_wc_af_table">
				<?php
			}


			/* CLEANUP */
			public function opmc_add_admin_field_section_for_cleanup( $value ) {
				$option_value = (array) WC_Admin_Settings::get_option( $value['id'] );
				$description = WC_Admin_Settings::get_field_description( $value );
				
				?>
				</table>
				  <div class="wc_af_sub-section-title wc-af-settings-subheading-wrap wc-af-settings-subheading-wrap--cleanup">
					   <h3 class="wc_af_sub-section wc-af-settings-subheading" id="<?php echo esc_attr( $value['id'] ); ?>"><?php echo wp_kses_post( $value['name'] ); ?> <?php echo wp_kses_post( $description['tooltip_html'] ); ?></h3>
					   
					   <?php if ( ! empty( $value['description'] ) ) : ?>
						   <p class="wc-af-subsection-desc"><?php echo wp_kses_post( $value['description'] ); ?></p>
					   <?php endif; ?>
				   </div>
				<table class="form-table opmc_wc_af_table">
				<?php
			}


			//PLUGINS-2675
			public function opmc_add_admin_field_section_for_cardAttack( $value ) {
				$option_value = (array) WC_Admin_Settings::get_option( $value['id'] );
				$description = WC_Admin_Settings::get_field_description( $value );
				
				$wc_af_recaptcha_enable_captcha = get_option( 'wc_af_recaptcha_enable_captcha' );
				$wc_af_admin_recaptcha_verified = get_option( 'wc_af_admin_recaptcha_verified' );
				$recaptcha_type = get_option('wc_af_recaptcha_type');

				if ( 'yes' === $wc_af_recaptcha_enable_captcha && 'yes' === $wc_af_admin_recaptcha_verified && 'google_recaptcha' === $recaptcha_type ) {
					
					$text = 'Active';
					
				} else {
					$text = 'Not Active';

				}
				?>
				</table>
				   <div class="wc_af_sub-section-title wc-af-settings-subheading-wrap wc-af-settings-subheading-wrap--card<?php echo 'Active' === $text ? ' is-active' : ''; ?>">
					   <h3 class="<?php echo esc_attr( $value['class'] ); ?> wc-af-settings-subheading" id="<?php echo esc_attr( $value['id'] ); ?>"><?php echo wp_kses_post( $value['name'] ); ?> <?php echo wp_kses_post( $description['tooltip_html'] ); ?><span class="wc-af-subheading-status" id="active_not"><?php echo wp_kses_post( $text ); ?></span></h3>
					   
					   <?php if ( ! empty( $value['description'] ) ) : ?>
						   <p class="wc-af-subsection-desc"><?php echo wp_kses_post( $value['description'] ); ?></p>
					   <?php endif; ?>
				   </div>
				<table class="form-table opmc_wc_af_table">
				<?php
			}


			//PLUGINS-2675
			/**
			 * Card attacks section 2 heading + table opener.
			 *
			 * @param array $value Field definition.
			 * @param bool  $skip_leading_table_close When true, omit the leading `</table>` so the caller can close the previous table before an advanced-settings wrapper (valid HTML).
			 */
			public function opmc_add_admin_field_section_2_cardAttack( $value, $skip_leading_table_close = false ) {
				$option_value = (array) WC_Admin_Settings::get_option( $value['id'] );
				$description = WC_Admin_Settings::get_field_description( $value );
				
				$validkey = get_option('wc_af_attempt_count_check');

				if ('yes' == $validkey ) {
					$text = 'Active';
					
				} else {
					$text = 'Not Active';

				}
				if ( ! $skip_leading_table_close ) {
					?>
				</table>
					<?php
				}
				?>
				   <div class="wc_af_sub-section-title wc-af-settings-subheading-wrap wc-af-settings-subheading-wrap--card<?php echo 'Active' === $text ? ' is-active' : ''; ?>">
					   <h3 class="<?php echo esc_attr( $value['class'] ); ?> wc-af-settings-subheading" id="<?php echo esc_attr( $value['id'] ); ?>"><?php echo wp_kses_post( $value['name'] ); ?> <?php echo wp_kses_post( $description['tooltip_html'] ); ?><span class="wc-af-subheading-status" id="active_not_sec_2"><?php echo wp_kses_post( $text ); ?></span></h3>
					   
					   <?php if ( ! empty( $value['description'] ) ) : ?>
						   <p class="wc-af-subsection-desc"><?php echo wp_kses_post( $value['description'] ); ?></p>
					   <?php endif; ?>
				   </div>
				<table class="form-table opmc_wc_af_table">
				<?php
			}

			public function opmc_add_admin_field_section_3_cardAttack( $value ) {
				$description = WC_Admin_Settings::get_field_description( $value );
				$validkey = get_option('wc_af_order_payment_attempt_check');

				if ('yes' == $validkey ) {
					$text = 'Active';

				} else {
					$text = 'Not Active';

				}
				?>
				</table>
				   <div class="wc_af_sub-section-title wc-af-settings-subheading-wrap wc-af-settings-subheading-wrap--card<?php echo 'Active' === $text ? ' is-active' : ''; ?>">
					   <h3 class="<?php echo esc_attr( $value['class'] ); ?> wc-af-settings-subheading" id="<?php echo esc_attr( $value['id'] ); ?>"><?php echo wp_kses_post( $value['name'] ); ?> <?php echo wp_kses_post( $description['tooltip_html'] ); ?><span class="wc-af-subheading-status" id="active_not_sec_3"><?php echo wp_kses_post( $text ); ?></span></h3>

					   <?php if ( ! empty( $value['description'] ) ) : ?>
						   <p class="wc-af-subsection-desc"><?php echo wp_kses_post( $value['description'] ); ?></p>
					   <?php endif; ?>
				   </div>
				<table class="form-table opmc_wc_af_table">
				<?php
			}

			public function opmc_add_admin_field_section_4_cardAttack( $value ) {
				$description = WC_Admin_Settings::get_field_description( $value );
				$validkey = get_option('wc_af_limit_order_count');

				if ('yes' == $validkey ) {
					$text = 'Active';
				} else {
					$text = 'Not Active';

				}
				?>
				</table>
				   <div class="wc_af_sub-section-title wc-af-settings-subheading-wrap wc-af-settings-subheading-wrap--card<?php echo 'Active' === $text ? ' is-active' : ''; ?>">
					   <h3 class="<?php echo esc_attr( $value['class'] ); ?> wc-af-settings-subheading" id="<?php echo esc_attr( $value['id'] ); ?>"><?php echo wp_kses_post( $value['name'] ); ?> <?php echo wp_kses_post( $description['tooltip_html'] ); ?><span class="wc-af-subheading-status" id="active_not_sec_4"><?php echo wp_kses_post( $text ); ?></span></h3>

					   <?php if ( ! empty( $value['description'] ) ) : ?>
						   <p class="wc-af-subsection-desc"><?php echo wp_kses_post( $value['description'] ); ?></p>
					   <?php endif; ?>
				   </div>
				<table class="form-table opmc_wc_af_table">
				<?php
			}

			public function opmc_score_slider( $score = 0, $order_risk = false, $thresholds = false ) {

				$medium_risk_score = (int) get_option( 'wc_settings_anti_fraud_low_risk_threshold', 25 );
				$high_risk_score   = (int) get_option( 'wc_settings_anti_fraud_higher_risk_threshold', 75 );


				$gradient = 'linear-gradient(90deg, rgba(90,198,125,1) ' . ( $medium_risk_score - 25 ) . '%, rgba(205,119,57,1) ' . ( $high_risk_score ) . '%, rgba(185,74,72,1) 100%)';
				$score_bar_bg = '#777777';

				if ( '' == $score ) {
					$score = 0;
				}

				$score = (int) $score;

				if ( 0 == $score && ! $thresholds ) {
					$score_bar_bg = '#777777';
				} else {
					$score_bar_bg = $gradient;
				}

				if ( 0 < $score && $medium_risk_score >= $score ) {
					$score_value_border = 'rgba(90,198,125,1)';
				} elseif ( $medium_risk_score <= $score && $high_risk_score >= $score ) {
					$score_value_border = 'rgba(205,119,57,1)';
				} elseif ( $high_risk_score < $score ) {
					$score_value_border = 'rgba(185,74,72,1)';
				} else {
					$score_value_border = '#777777';
				}

				if ( $order_risk ) {
					if ( 0 < $score && $medium_risk_score >= $score ) {
						$order_risk_status = 'Low Risk Order';
					} elseif ( $medium_risk_score <= $score && $high_risk_score >= $score ) {
						$order_risk_status = 'Medium Risk Order';
					} elseif ( $high_risk_score < $score ) {
						$order_risk_status = 'High Risk Order';
					} else {
						$order_risk_status = 'Disabled';
					}
				}

				?>
				<div class="score-slider <?php echo ( $thresholds ) ? 'multi-handle' : ''; ?>">
					<div class="score-bar" style="background:<?php echo esc_attr( $score_bar_bg ); ?>;">

						<?php if ( $thresholds ) : ?>
							<div class="score-value min-score" style="left:<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_low_risk_threshold' ) ); ?>%; border-color:rgba(90,198,125,1);">
								<span class="score-text min-score">
									<?php echo esc_html( get_option( 'wc_settings_anti_fraud_low_risk_threshold' ) ); ?>
								</span>
							</div>
							<div class="score-value max-score" style="left:<?php echo esc_attr( get_option( 'wc_settings_anti_fraud_higher_risk_threshold' ) ); ?>%; border-color:rgba(205,119,57,1)">
								<span class="score-text max-score">
									<?php echo esc_html( get_option( 'wc_settings_anti_fraud_higher_risk_threshold' ) ); ?>
								</span>
							</div>
							<?php else : ?>
								<div class="score-value" style="left:<?php echo esc_attr( $score ); ?>%; border-color:<?php echo esc_attr( $score_value_border ); ?>" data-min-score="<?php echo esc_attr( $medium_risk_score ); ?>" data-max-score="<?php echo esc_attr( $high_risk_score ); ?>">
									<span class="score-text">
										<?php echo wp_kses_post( $score ); ?>
									</span>
								</div>
							<?php endif; ?>

						</div>
						<?php if ( $thresholds ) : ?>
							<div class="score-bar-label">
								<span><?php echo esc_html__( 'Low Risk', 'woocommerce-anti-fraud' ); ?></span>
								<span><?php echo esc_html__( 'Medium Risk', 'woocommerce-anti-fraud' ); ?></span>
								<span><?php echo esc_html__( 'High Risk', 'woocommerce-anti-fraud' ); ?></span>
							</div>
							<?php elseif ( $order_risk ) : ?>
								<div class="score-bar-label">
									<span>
									<?php 
									if ( 0 < $score && $medium_risk_score >= $score ) {
											$order_risk_status = esc_html__( 'Low Risk Order', 'woocommerce-anti-fraud' );
									} elseif ( $medium_risk_score <= $score && $high_risk_score >= $score ) {
										echo esc_html__( 'Medium Risk Order', 'woocommerce-anti-fraud' );
									} elseif ( $high_risk_score < $score ) {
										echo esc_html__( 'High Risk Order', 'woocommerce-anti-fraud' );
									} else {
										echo esc_html__( 'Disabled', 'woocommerce-anti-fraud' );
											
									}
									?>
									</span>
								</div>
						<?php else : ?>
							<div class="score-bar-label">
								<span><?php echo esc_html__( 'No Importance', 'woocommerce-anti-fraud' ); ?></span>
								<span><?php echo esc_html__( 'Moderate', 'woocommerce-anti-fraud' ); ?></span>
								<span><?php echo esc_html__( 'High Importance', 'woocommerce-anti-fraud' ); ?></span>
							</div>
						<?php endif; ?>
				</div>
				<?php
			}

			/**
			 * Output the settings
			 *
			 * @since 1.0
			 */
			public function output() {

				global $current_section;

				$settings = $this->get_settings( $current_section );

				$this->print_section_intro( $current_section );

				if ( 'recaptcha_settings' == $current_section ) {
					echo '<div class="wc-af-settings-main wc-af-ui wc-af-app-shell">';
					WC_AF_Settings_Recaptcha::render_fields();
					echo '</div>';

				} elseif ( 'white_list' == $current_section ) {

					echo '<div class="wc-af-settings-main wc-af-ui wc-af-app-shell">';
					WC_AF_Settings_Whitelist::render_fields();
					echo '</div>';

				} elseif ( 'black_list' == $current_section ) {

					echo '<div class="wc-af-settings-main wc-af-ui wc-af-app-shell">';
					WC_AF_Settings_Blacklist::render_fields();
					echo '</div>';

				} else {
					include_once WOOCOMMERCE_ANTI_FRAUD_PLUGIN_DIR . 'anti-fraud-core/tamplate-admin-settings-page.php';
				}
			}

			/**
			 * Save settings
			 *
			 * @since 1.0
			 */
			public function save() {

				global $current_section;

				$settings = $this->get_settings( $current_section );
				
				$nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';

				if ( empty($nonce) || ! wp_verify_nonce( $nonce, 'woocommerce-settings' ) ) {
					echo 'Nonce verification failed!';
					wp_die();
				}
				
				if ( isset( $_POST['wc_settings_anti_fraud_whitelist'] ) ) {
					$_POST['wc_settings_anti_fraud_whitelist'] = str_replace( ',', "\n", sanitize_text_field( $_POST['wc_settings_anti_fraud_whitelist'] ) );
				}
				WC_Admin_Settings::save_fields( $settings );

				// Save dashboard date range to user-specific option key (synced with dashboard)
				if ( isset( $_POST['wc_af_dashboard_date_range'] ) ) {
					$date_range = sanitize_text_field( wp_unslash( $_POST['wc_af_dashboard_date_range'] ) );
					$valid_ranges = array( 'last_15_days', 'last_30_days', 'last_60_days', 'last_90_days', 'all_orders' );
					if ( in_array( $date_range, $valid_ranges, true ) ) {
						$option_key = 'wc_af_dashboard_date_range_' . get_current_user_id();
						update_option( $option_key, $date_range );
					}
				}

				$get_all_whitelist_ips = get_option('wc_settings_anti_fraud_ips_whitelist');
				// Check if whitelist is enabled and some IP addresses are added in the list under settings

			}

			/**
			 * Authorized_Minfraud
			 *
			 * @since 1.0
			 */
			public function Authorized_Minfraud() {

				global $current_section;
				$get_settings = $this->get_settings( $current_section );

				if ( isset( $get_settings ) ) {

					$this->log->add( 'MinFraud', '====== Authentication function has been accessed' );

					$curr_settings = $get_settings['0']['id'];
					$setting_type = get_option( 'wc_af_maxmind_type' );

					$this->log->add(
						'MinFraud',
						print_r(
							array(
								'current settings tab' => $curr_settings,
								'setting enable' => $setting_type,
							),
							true
						)
					);

					if ( 'yes' == $setting_type && 'wc_af_minfraud_settings' == $curr_settings ) {

						$maxmind_user = get_option( 'wc_af_maxmind_user' );
						$maxmind_license_key = get_option( 'wc_af_maxmind_license_key' );
						$authkey = 'Basic ' . base64_encode( $maxmind_user . ':' . $maxmind_license_key );

						$this->log->add( 'MinFraud', print_r( array( 'Authorization' => $authkey ), true ) );

						$curl = curl_init();

						curl_setopt_array(
							$curl,
							array(
								CURLOPT_URL => 'https://minfraud.maxmind.com/minfraud/v2.0/score',
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_USERAGENT => 'AnTiFrAuDOPMC',
								CURLOPT_ENCODING => '',
								CURLOPT_MAXREDIRS => 10,
								CURLOPT_TIMEOUT => 30,
								CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
								CURLOPT_CUSTOMREQUEST => 'POST',
								CURLOPT_POSTFIELDS => '',
								CURLOPT_HTTPHEADER => array(
									'authorization:' . $authkey,
									'cache-control: no-cache',
									'content-type: application/json',
								),
							)
						);

						$response = curl_exec( $curl );
						curl_close( $curl );
						$result = json_decode( $response, true );
						if ( is_array( $result ) && isset( $result['code'] ) ) {
							$error = $result['code'];
						} else {
							$error = ''; // or assign a default, like 'unknown_error'
						}

						if ( 'AUTHORIZATION_INVALID' === $error ) {

							$this->log->add( 'MinFraud', '====== Authentication failed' );
							$this->log->add(
								'MinFraud',
								print_r(
									array(
										'MaxMind Account Id' => $maxmind_user,
										'MaxMind license key' => $maxmind_license_key,
									),
									true
								)
							);
							update_option( 'wc_af_maxmind_authentication', false );
							add_action( 'admin_notices', array( $this, 'auth_error_admin_notice' ) );

						} else {

							$this->log->add( 'MinFraud', '====== Authentication succeed ' );
							update_option( 'wc_af_maxmind_authentication', true );
							add_action( 'admin_notices', array( $this, 'auth_success_admin_notice' ) );

						}
					}
				}
			}

			/**
			 * Auth_error_admin_notice
			 *
			 * @since 1.0
			 */
			public function auth_error_admin_notice() {

				?>
			<div class="error is-dismissible">
				<p><strong><?php esc_html_e( 'Your MaxMind Account ID and/or Licence Key couldn\'t be authenticated. This likely means the details entered are incorrect. Please, check these and try again.', 'woocommerce-anti-fraud' ); ?></strong></p>
			</div>

				<?php
			}

			/**
			 * Auth_success_admin_notice
			 *
			 * @since 1.0
			 */
			public function auth_success_admin_notice() {

				?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php echo esc_html_e( 'Great, authenticated successfully!!', 'woocommerce-anti-fraud' ); ?></strong></p>
			</div>

				<?php
			}

			public function suspicious_domains() {
				$email_domains = array(
					'hotmail',
					'live',
					'gmail',
					'yahoo',
					'mail',
					'123vn',
					'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijk',
					'aaemail.com',
					'webmail.aol',
					'postmaster.info.aol',
					'personal',
					'atgratis',
					'aventuremail',
					'byke',
					'lycos',
					'computermail',
					'dodgeit',
					'thedoghousemail',
					'doramail',
					'e-mailanywhere',
					'eo.yifan',
					'earthlink',
					'emailaccount',
					'zzn',
					'everymail',
					'excite',
					'expatmail',
					'fastmail',
					'flashmail',
					'fuzzmail',
					'galacmail',
					'godmail',
					'gurlmail',
					'howlermonkey',
					'hushmail',
					'icqmail',
					'indiatimes',
					'juno',
					'katchup',
					'kukamail',
					'mail',
					'mail2web',
					'mail2world',
					'mailandnews',
					'mailinator',
					'mauimail',
					'meowmail',
					'merawalaemail',
					'muchomail',
					'MyPersonalEmail',
					'myrealbox',
					'nameplanet',
					'netaddress',
					'nz11',
					'orgoo',
					'phat.co',
					'probemail',
					'prontomail',
					'rediff',
					'returnreceipt',
					'synacor',
					'walkerware',
					'walla',
					'wongfaye',
					'xasamail',
					'zapak',
					'zappo',
				);
				return implode( ',', $email_domains );
			}

			public function get_countries() {
				$countries_obj   = new WC_Countries();
				$countries       = $countries_obj->__get( 'countries' );
				return $countries;

			}

			/**
			 * Authorized_ChatGPT API
			 *
			 * @since 7.0.3
			 */
			public function validate_ai_api_key_on_save() {

				global $current_section;
				$get_settings = $this->get_settings( $current_section );
				if ( isset( $get_settings ) ) {

					$curr_settings = $get_settings['0']['id'];

					$setting_type = get_option( 'wc_af_ai_fraud_prevention_check' );

					if ( 'yes' == $setting_type && 'wc_af_ai_fraud_prevention_settings' == $curr_settings ) {
						$ai_api_key = get_option( 'wc_af_chatgpt_api_key' );
						$ai_model = get_option( 'wc_af_ai_model' );

						if (empty($ai_api_key)) {
							return;
						}
						$apiEndPoint = 'https://api.openai.com/v1/chat/completions';
						$post_data = $this->create_ai_body($ai_model);
						$curl = curl_init();

							curl_setopt_array($curl, array(
							CURLOPT_URL => $apiEndPoint,
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_MAXREDIRS => 10,
							CURLOPT_TIMEOUT => 30,
							CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
							CURLOPT_CUSTOMREQUEST => 'POST',
							CURLOPT_POSTFIELDS => $post_data,
							CURLOPT_HTTPHEADER => array(
								'Authorization: Bearer ' . $ai_api_key,
								'Content-Type: application/json',
							),
						));

						$response  = curl_exec($curl);
						$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
						$curl_error = curl_error($curl);
						curl_close($curl);

						if ( 429 === $http_code ) {
							add_action( 'admin_notices', array( $this, 'limit_exceeded_api_key' ) );
							return;
						}

						if ($curl_error || 401 === $http_code || empty($ai_model)) {
							//update_option('wc_af_invalid_api_key', true); // Show error notice
							add_action( 'admin_notices', array( $this, 'invalid_api_key' ) );
							return;
						} else {
							if ($curl_error || 200 === $http_code ) {
								//update_option('wc_af_valid_api_key', true); // Show error notice
								add_action( 'admin_notices', array( $this, 'valid_api_key' ) );
								return;
							}
						}
					}
				}
			}

			private function create_ai_body( $ai_model) {

				$data = [
					'model' => $ai_model,
					'messages' => [
						[
							'role' => 'system',
							'content' => 'Ping for validation check.'
						]
					]
				];

				return json_encode($data);
			}

			/**
			 * Auth_error_admin_notice
			 *
			 * @since 7.0.3
			 */
			public function limit_exceeded_api_key() {
				
				?>
				<div class="error is-dismissible">
					<p><strong><?php echo esc_html_e( 'Anti-Fraud: The provided OpenAI API key has exceeded its usage limits. Please verify your key and try again.', 'woocommerce-anti-fraud' ); ?></strong></p>
				</div>
				<?php 
			}

			/**
			 * Auth_error_admin_notice
			 *
			 * @since 7.0.3
			 */
			public function invalid_api_key() {
				
				?>
				<div class="error is-dismissible">
					<p><strong><?php echo esc_html_e( 'Anti-Fraud: The provided OpenAI API key is invalid, expired, or the ChatGPT version is not selected. Please verify these settings and try again.', 'woocommerce-anti-fraud' ); ?></strong></p>
				</div>
				<?php 
			}

			/**
			 * Auth_success_admin_notice
			 *
			 * @since 7.0.3
			 */
			public function valid_api_key() {
				
				?>
				<div class="notice notice-success is-dismissible">
					<p><strong><?php echo esc_html_e( 'Anti-Fraud: The provided OpenAI API key is valided successfully.', 'woocommerce-anti-fraud' ); ?></strong></p>
				</div>
				<?php
			}


			/**
			 * Authorized_Quickemailverification
			 *
			 * @since 1.0
			 */
			public function Authorized_Quickemailverification() {

				global $current_section;
				$get_settings = $this->get_settings( $current_section );

				if ( isset( $get_settings ) ) {

					$curr_settings = $get_settings['0']['id'];

					$setting_type = get_option( 'wc_af_suspecius_email' );

					if ( 'yes' == $setting_type && 'wc_af_general_settings' == $curr_settings ) {

						$email_api_key = get_option( 'check_email_domain_api_key' );
						$admin_email = get_option( 'admin_email' );

						$contents = @file_get_contents( "https://api.quickemailverification.com/v1/verify?email=$admin_email&apikey=$email_api_key" );

						if ( false !== $contents ) {

							$res = @json_decode( $contents );

							if ( json_last_error() === JSON_ERROR_NONE ) {

								$data = @$res->message;

								if ( 'Invalid api key' !== $data ) {

									add_action( 'admin_notices', array( $this, 'auth_quickemailverification_success_admin_notice' ) );
								} else {
									 add_action( 'admin_notices', array( $this, 'auth_quickemailverification_success_low_creadit_admin_notice' ) );
								}
							}
						} else {
							$email_api_key = get_option( 'check_email_domain_api_key' );
							if ( ! empty( $email_api_key ) ) {
															add_action( 'admin_notices', array( $this, 'auth_quickemailverification_error_admin_notice' ) );
							}
						}
					}
				}
			}

			/**
			 * Auth_error_admin_notice
			 *
			 * @since 1.0
			 */
			public function auth_quickemailverification_error_admin_notice() {

				?>
			<div class="error is-dismissible">
				<p><strong><?php echo esc_html_e( 'Your Quickemailverification API Key could not be authenticated!!', 'woocommerce-anti-fraud' ); ?></strong></p>
			</div>

				<?php
			}

			/**
			 * Auth_success_admin_notice
			 *
			 * @since 1.0
			 */
			public function auth_quickemailverification_success_admin_notice() {

				?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php echo esc_html_e( 'Great, Quickemailverification authenticated successfully!!', 'woocommerce-anti-fraud' ); ?></strong></p>
			</div>

				<?php
			}

			/**
			 * Auth_success_admin_notice With low creadit
			 *
			 * @since 1.0
			 */
			public function auth_quickemailverification_success_low_creadit_admin_notice() {

				?>
			<div class="notice notice-info is-dismissible">
				<p><strong><?php echo esc_html_e( 'Great, Quickemailverification authenticated successfully but you don\'t have enough credit to use this service.', 'woocommerce-anti-fraud' ); ?></strong></p>
			</div>

				<?php
			}

			/**
			 * Authorized_bigdatacloud
			 *
			 * @since 5.8.0
			 */
			public function Authorized_bigdatacloud() {

				global $current_section;
				$get_settings = $this->get_settings( $current_section );

				if ( isset( $get_settings ) ) {

					$setting_type = get_option( 'wc_af_geolocation_order' );
					$curr_settings = $get_settings['0']['id'];

					if ( 'yes' == $setting_type && 'wc_af_rule_settings' == $curr_settings ) {

						$bigdatacloud_key = get_option( 'bigdatacloud_api_key', true );
						
						/* if ( empty( get_option( 'bigdatacloud_notice_dismisseds_onsave' ) ) &&  empty( get_option( 'bigdatacloud_notice_dismisseds_error' ) ) ) {
							if (empty($bigdatacloud_key)) {

								update_option('wc_af_geolocation_order', 'no');
								update_option('bigdatacloud_error_empty_key', 'yes');
								add_action( 'admin_notices', array( $this, 'auth_bigdatacloud_empty_admin_notice' ) );						

								return true;
							}
						} */

						$response = wp_remote_get( 'https://api-bdc.net/data/reverse-geocode?latitude=-34.93129&longitude=138.59669&localityLanguage=en&key=' . $bigdatacloud_key );

						if ( is_wp_error( $response ) ) {
							echo 'bigdatacloud ';
							return true;
						}

						if ( isset( $response ) && '200' == $response['response']['code']) {
							//delete_option('bigdatacloud_error_empty_key');
							update_option( 'bigdatacloud_onetime_notice_dismisseds', 1 );
							update_option( 'bigdatacloud_notice_dismisseds_onsave', 1 );
							update_option( 'bigdatacloud_notice_dismisseds_error', 1 );
							add_action( 'admin_notices', array( $this, 'auth_bigdatacloud_success_admin_notice' ) );

						} else {

							if (isset( $response ) && '403' == $response['response']['code']) {
								update_option( 'wc_af_geolocation_order', 'no');
								update_option( 'bigdatacloud_api_key', '');
								update_option( 'bigdatacloud_onetime_notice_dismisseds', 1 );
								//update_option( 'bigdatacloud_error_empty_key', 'yes');
								update_option( 'bigdatacloud_notice_dismisseds_onsave', '' );
								update_option( 'bigdatacloud_notice_dismisseds_error', '' );

								add_action( 'admin_notices', array( $this, 'auth_bigdatacloud_empty_admin_notice' ) );						
							}
						}
					}
				}
			}

			/**
			 * Auth_bigdatacloud_success_admin_notice
			 *
			 * @since 5.8.0
			 */
			public function auth_bigdatacloud_success_admin_notice() {

				?>
				<div class="notice notice-success is-dismissible" id="on_success">
					<p><strong><?php echo esc_html_e( 'Great, bigdatacloud authenticated successfully!', 'woocommerce-anti-fraud' ); ?></strong></p>
				</div>

				<?php
			}


			/**
			 * Auth_bigdatacloud_empty_admin_notice
			 *
			 * @since 5.8.0
			 */
			public function auth_bigdatacloud_empty_admin_notice() {

				?>
				<div class="notice notice-error is-dismissible opmc-antifraud" id="on_save">
					<p><strong><?php echo esc_html_e( 'AntiFraud: Bigdatacloud credentials not authenticate or your quota limit has been exceeded!', 'woocommerce-anti-fraud' ); ?></strong></p>
				</div>
			
				<?php
			}


			// PLUGINS-2675
			/**
			 * Authorized_reCaptcha
			 *
			 * @since 1.0
			 */
			public function Authorized_reCaptcha() {

				global $current_section;
				$get_settings = $this->get_settings( $current_section );

				if ( isset( $get_settings ) ) {

					$curr_settings = $get_settings['0']['id'];
					$setting_enable = get_option( 'wc_settings_anti_fraudenable_enable_recaptcha' );
					$v2secret_key = get_option( 'wc_af_recaptcha_secret_key' );

					if ( 'yes' == $setting_enable && 'wc_af_recaptch_settings' == $curr_settings && !empty($v2secret_key) ) {

						// Send a request to Google's reCAPTCHA API to validate the keys
						$pattern = '#^6[0-9a-zA-Z_-]{39}$#';
						$formate = preg_match($pattern, $v2secret_key);
						
						if ($formate) {
							
							$response = wp_remote_get("https://www.google.com/recaptcha/api/siteverify?secret={$v2secret_key}&site={$v2site_key}&response=test");

							// Check if the request was successful
							if (is_wp_error($response)) {
								   update_option('recaptcha_secret_key_valid', 'no');

							   add_action( 'admin_notices', array( $this, 'auth_recaptcha_error_admin_notice' ) );
							}

							// Retrieve the body of the response
							$body = wp_remote_retrieve_body($response);
							if (isset($body)) {
								// Decode the JSON response
								$data = json_decode($body, true);

								// Check if the response indicates success
								if ($data && 'invalid-input-response' === $data['error-codes'][0]) {
								   update_option('recaptcha_secret_key_valid', 'yes');
								   
								   add_action( 'admin_notices', array( $this, 'auth_recaptcha_success_admin_notice' ) );
								} else {
								   update_option('recaptcha_secret_key_valid', 'no');

									 add_action( 'admin_notices', array( $this, 'auth_recaptcha_error_admin_notice' ) );
								}
							}
						} else {
							 update_option('recaptcha_secret_key_valid', 'no');
							 add_action( 'admin_notices', array( $this, 'auth_recaptcha_error_admin_notice' ) );
						}
					}
				}
			}

			/**
			 * Auth_error_admin_notice
			 *
			 * @since 1.0
			 */
			public function auth_recaptcha_error_admin_notice() {

				?>
			<div class="notice notice-error is-dismissible">
				<p><strong><?php esc_html_e( 'reCAPTCHA keys could not be verified. Check your site and secret keys under Checkout CAPTCHA.', 'woocommerce-anti-fraud' ); ?></strong></p>
			</div>

				<?php
			}

			/**
			 * Auth_success_admin_notice
			 *
			 * @since 1.0
			 */
			public function auth_recaptcha_success_admin_notice() {

				?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php esc_html_e( 'reCAPTCHA keys verified successfully.', 'woocommerce-anti-fraud' ); ?></strong></p>
			</div>

				<?php
			}

			public function whitelist_payment_method() {

				$whitelist_payment_method = array(
					'paysera',
					'skrill_flexible',
					'skrill_wlt',
					'skrill_acc',
					'skrill_vsa',
					'skrill_msc',
					'skrill_ntl',
				);
				return implode( ',', $whitelist_payment_method );
			}

			public function whitelist_user_roles() {

			}

			/**
			 * AuthorizedTrustSwiftly
			 *
			 * @since 5.6.0
			 */
			public function AuthorizedTrustSwiftly() {

				global $current_section;
				$get_settings = $this->get_settings( $current_section );

				if ( isset( $get_settings ) ) {

					$curr_settings = $get_settings['0']['id'];

					$setting_type = get_option( 'wc_af_trust_swiftly_type' );

					if ( 'yes' == $setting_type && 'wc_af_trust_swiftly_settings' == $curr_settings ) {

						$apiKey = get_option( 'wc_af_trust_swiftly_api_key' );
						$baseUrl = get_option( 'wc_af_trust_swiftly_base_url' );
						$vTemplate = array();

						if (isset($apiKey) && !empty($apiKey) && isset($baseUrl) ) {

							$headers = array(
								'Authorization' => 'Bearer ' . $apiKey,
								'Content-Type' => 'application/json',
								'User-Agent' => 'TrustSwiftly/1.0'
							);
							$request = wp_remote_get( $baseUrl . '/api/users', array(
								'headers' => $headers,
							));

							$response_code = wp_remote_retrieve_response_code($request);

							if ( !is_wp_error( $request ) ) {

								if ( '200' == $response_code ) {

									update_option('trust_api_keys_validated', 'true');
									add_action( 'admin_notices', array( $this, 'AuthTrustSwiftlySuccessAdminNotice' ) );
								} else {
									
									delete_option('trust_api_keys_validated');

									add_action( 'admin_notices', array( $this, 'AuthTrustSwiftlyErrorAdminNotice' ) );
								}
							} else {
									
								delete_option('trust_api_keys_validated');

								add_action( 'admin_notices', array( $this, 'AuthTsBaseurlErrAdminNtc' ) );
							}
						}
					}
				}
			}	
			
			/**
			 * Auth_error_admin_notice
			 *
			 * @since 1.0
			 */
			public function AuthTrustSwiftlyErrorAdminNotice() {

				?>
			<div class="error is-dismissible">
				<p><strong><?php echo esc_html_e( 'Your Trust Swiftly API Key could not be authenticated!!', 'woocommerce-anti-fraud' ); ?></strong></p>
			</div>

				<?php
			}

			/**
			 * Auth_error_admin_notice
			 *
			 * @since 1.0
			 */
			public function AuthTsBaseurlErrAdminNtc() {

				?>
			<div class="error is-dismissible">
				<p><strong><?php echo esc_html_e( 'Your Trust Swiftly Base URL could not be validated!!', 'woocommerce-anti-fraud' ); ?></strong></p>
			</div>

				<?php
			}

			/**
			 * Auth_success_admin_notice
			 *
			 * @since 1.0
			 */
			public function AuthTrustSwiftlySuccessAdminNotice() {

				?>
			<div class="notice notice-success">
				<p><strong><?php echo esc_html_e( 'Great, Trust Swiftly authenticated successfully!!', 'woocommerce-anti-fraud' ); ?></strong></p>
			</div>

				<?php
			}

			public function updateBulkTextariaTagData() {	
				
				// Step 1: Process and clean BLACKLIST emails
				$blocked_email = get_option( 'wc_settings_anti_fraudblacklist_emails' );
				$cleanedEmailsBlacklist = [];
				
				if ( '' != $blocked_email) {
					$email_parts1 = preg_split('/[\s,]+/', $blocked_email);
					$cleanedEmailsBlacklist = array_filter($email_parts1, 'strlen');
					// Normalize to lowercase for comparison
					$cleanedEmailsBlacklist = array_map('strtolower', array_map('trim', $cleanedEmailsBlacklist));
					$cleanedEmailsBlacklist = array_values(array_unique($cleanedEmailsBlacklist));
				}

				// Step 2: Process and clean BLACKLIST IPs
				$blocked_ips = get_option( 'wc_settings_anti_fraudblacklist_ipaddress' );
				$cleanedIpsBlacklist = [];
				
				if ( '' != $blocked_ips ) {
					// Split by commas, spaces, or newlines
					$ips_parts = preg_split('/[\s,]+/', $blocked_ips);
					foreach ($ips_parts as $ip) {
						$ip = trim($ip);
						if ('' !== $ip && filter_var($ip, FILTER_VALIDATE_IP)) {
							$cleanedIpsBlacklist[] = $ip; // only valid IP added
						}
					}
					// Remove duplicates
					$cleanedIpsBlacklist = array_values(array_unique($cleanedIpsBlacklist));
				}

				// Step 3: Process and clean WHITELIST IPs (wc_settings_anti_fraudwhitelist_ipaddress)
				$ips_whitelist = get_option( 'wc_settings_anti_fraudwhitelist_ipaddress' );
				$cleanedIpsWhitelist = [];
				
				if ( '' != $ips_whitelist) {
					$ips_parts3 = preg_split('/[\s,]+/', $ips_whitelist);
					foreach ($ips_parts3 as $ip) {
						$ip = trim($ip);
						if ('' !== $ip && filter_var($ip, FILTER_VALIDATE_IP)) {
							$cleanedIpsWhitelist[] = $ip;
						}
					}
					// Remove duplicates
					$cleanedIpsWhitelist = array_values(array_unique($cleanedIpsWhitelist));
				}

				// Step 4: Process and clean WHITELIST emails
				$whitelist_email = get_option( 'wc_settings_anti_fraud_whitelist' );
				$cleanedEmailsWhitelist = [];
				
				if ( '' != $whitelist_email) {
					$email_parts4 = preg_split('/[\s,]+/', $whitelist_email);
					$cleanedEmailsWhitelist = array_filter($email_parts4, 'strlen');
					// Normalize to lowercase for comparison
					$cleanedEmailsWhitelist = array_map('strtolower', array_map('trim', $cleanedEmailsWhitelist));
					$cleanedEmailsWhitelist = array_values(array_unique($cleanedEmailsWhitelist));
				}

				// Step 5: Process WHITELIST IPs (wc_settings_anti_fraud_ips_whitelist) 
				$ips_whitelist_main = get_option( 'wc_settings_anti_fraud_ips_whitelist' );
				$cleanedIpsWhitelistMain = [];
				
				if ( '' != $ips_whitelist_main ) {
					$ips_parts_main = preg_split('/[\s,\r\n]+/', $ips_whitelist_main);
					foreach ($ips_parts_main as $ip) {
						$ip = trim($ip);
						if ('' !== $ip && filter_var($ip, FILTER_VALIDATE_IP)) {
							$cleanedIpsWhitelistMain[] = $ip;
						}
					}
					$cleanedIpsWhitelistMain = array_values(array_unique($cleanedIpsWhitelistMain));
				}

				// Step 6: WHITELIST PRIORITY - Remove whitelisted items from blacklist
				
				// Merge all whitelist IPs from both options
				$allWhitelistIps = array_unique(array_merge($cleanedIpsWhitelist, $cleanedIpsWhitelistMain));
				
				// Remove whitelisted emails from blacklist
				if (!empty($cleanedEmailsWhitelist)) {
					$cleanedEmailsBlacklist = array_diff($cleanedEmailsBlacklist, $cleanedEmailsWhitelist);
					$cleanedEmailsBlacklist = array_values($cleanedEmailsBlacklist);
				}
				
				// Remove whitelisted IPs from blacklist
				if (!empty($allWhitelistIps)) {
					$cleanedIpsBlacklist = array_diff($cleanedIpsBlacklist, $allWhitelistIps);
					$cleanedIpsBlacklist = array_values($cleanedIpsBlacklist);
				}

				// Step 7: Save all cleaned data back to options
				
				// Save blacklist emails
				$cleanedEmailsString1 = implode(', ', $cleanedEmailsBlacklist);
				update_option('wc_settings_anti_fraudblacklist_emails', $cleanedEmailsString1);

				// Save blacklist IPs
				$cleanedIpsString = implode(', ', $cleanedIpsBlacklist);
				update_option('wc_settings_anti_fraudblacklist_ipaddress', $cleanedIpsString);

				// Save whitelist IPs (wc_settings_anti_fraudwhitelist_ipaddress)
				$cleanedIpsString3 = implode(', ', $cleanedIpsWhitelist);
				update_option('wc_settings_anti_fraudwhitelist_ipaddress', $cleanedIpsString3);

				// Save whitelist emails
				// ✅ OPTIMIZED: Disable autoload to prevent loading on every request
				$cleanedEmailsString4 = implode(', ', $cleanedEmailsWhitelist);
				update_option('wc_settings_anti_fraud_whitelist', $cleanedEmailsString4);
				// Disable autoload directly via database
				global $wpdb;
				$wpdb->update(
					$wpdb->options,
					array( 'autoload' => 'no' ),
					array( 'option_name' => 'wc_settings_anti_fraud_whitelist' ),
					array( '%s' ),
					array( '%s' )
				);
				// Clear cache
				delete_transient( 'wc_af_whitelist_email_data' );

				// Save whitelist IPs (wc_settings_anti_fraud_ips_whitelist)
				if ( '' != $ips_whitelist_main ) {
					$cleanedIpsStringMain = implode(', ', $cleanedIpsWhitelistMain);
					update_option('wc_settings_anti_fraud_ips_whitelist', $cleanedIpsStringMain);
				}
			}

			/**
			 * Paypal Notification
			 *
			 * @since 7.0.3
			 */
			public function paypal_option_enable_message() {

				global $current_section;
				$get_settings = $this->get_settings( $current_section );
				if ( isset( $get_settings ) ) {

					$curr_settings = $get_settings['0']['id'];

					if ( 'wc_af_recaptch_settings' === $curr_settings ) {

						$paypal_acp_enabled = get_option( 'wc_af_paypal_acp_enabled' );
						$enable_captcha = get_option( 'wc_af_recaptcha_enable_captcha' );
						$recaptcha_type = get_option( 'wc_af_recaptcha_type' );

						if ( 'yes' === $enable_captcha && 'google_recaptcha' === $recaptcha_type) {

							update_option('pcap_notice_dismissed', 'yes'); // Show error notice
							add_action( 'admin_notices', array( $this, 'paypal_setting_notification' ) );
							return;
						}
					}
					
				}
			}

			public function paypal_setting_notification() {
				if (
					get_option( 'wc_af_paypal_acp_enabled' ) === 'yes' &&
					get_option( 'wc_af_recaptcha_enable_captcha' ) === 'yes' &&
					get_option( 'wc_af_recaptcha_type' ) === 'google_recaptcha' &&
					get_option( 'pcap_notice_dismissed' ) !== 'no'
				) {

					echo '<div class="notice notice-success is-dismissible" id="successpcap">
						<p>' . esc_html__( 'PayPal payment attempt limits were turned on because Checkout CAPTCHA (Google reCAPTCHA) is active.', 'woocommerce-anti-fraud' ) . '</p>
					</div>';
				}
			}

			/**
			 * Incident summary and quick links for the Card attacks settings section (Phase 4 UX).
			 *
			 * @since 7.2.6
			 */
			public function render_card_attacks_incident_panel() {
				$tab   = 'wc_af';
				$base  = admin_url( 'admin.php?page=wc-settings&tab=' . $tab );
				$links = array(
					'cleanup'   => add_query_arg( 'section', 'cleanup', $base ),
					'email'     => add_query_arg( 'section', 'email_alert', $base ),
					'captcha'   => add_query_arg( 'section', 'recaptcha_settings', $base ),
					'general'   => add_query_arg( 'section', 'general', $base ),
				);

				$captcha_on     = get_option( 'wc_af_recaptcha_enable_captcha', 'no' ) === 'yes';
				$captcha_ok     = get_option( 'wc_af_admin_recaptcha_verified', 'no' ) === 'yes';
				$recaptcha_type = get_option( 'wc_af_recaptcha_type', 'google_recaptcha' );
				$captcha_active = $captcha_on && $captcha_ok;

				// Treat Turnstile as configured when keys exist (no separate "verified" flag in all flows).
				if ( $captcha_on && ! $captcha_active && 'cf_turnstile' === $recaptcha_type ) {
					$site = (string) get_option( 'wc_af_turnstile_site_key', '' );
					if ( '' !== $site ) {
						$captcha_active = true;
					}
				}

				$order_limits_on    = get_option( 'wc_af_attempt_count_check', 'yes' ) === 'yes';
				$payment_limits_on  = get_option( 'wc_af_order_payment_attempt_check', 'yes' ) === 'yes';
				$stop_failed_emails = get_option( 'wc_af_stop_send_mail_failed_status', 'no' ) === 'yes';

				$failed_counts = get_transient( 'wc_af_preload_failed_counts' );
				$failed_24h    = null;
				if ( is_array( $failed_counts ) && isset( $failed_counts['24_hour'] ) ) {
					$failed_24h = (int) $failed_counts['24_hour'];
				}

				$pill_class = function ( $ok ) {
					return $ok ? 'wc-af-status-pill wc-af-status-pill--ok' : 'wc-af-status-pill wc-af-status-pill--warn';
				};

				$captcha_label = __( 'Checkout CAPTCHA', 'woocommerce-anti-fraud' );
				if ( ! $captcha_on ) {
					$captcha_state = __( 'Off', 'woocommerce-anti-fraud' );
					$captcha_class = 'wc-af-status-pill wc-af-status-pill--muted';
				} elseif ( $captcha_active ) {
					$captcha_state = __( 'Active', 'woocommerce-anti-fraud' );
					$captcha_class = 'wc-af-status-pill wc-af-status-pill--ok';
				} else {
					$captcha_state = __( 'Needs setup', 'woocommerce-anti-fraud' );
					$captcha_class = 'wc-af-status-pill wc-af-status-pill--warn';
				}
				?>
				<div class="wc-af-incident-panel wc-af-incident-panel--premium" role="region" aria-label="<?php echo esc_attr__( 'Card attack protection summary', 'woocommerce-anti-fraud' ); ?>">
					<div class="wc-af-incident-panel__header wc-af-ca-header">
						<div class="wc-af-incident-panel__title-row">
							<h3 class="wc-af-incident-panel__title"><?php esc_html_e( 'Card attack protection', 'woocommerce-anti-fraud' ); ?></h3>
							<span class="wc-af-badge wc-af-badge--warning"><?php esc_html_e( 'Card attack', 'woocommerce-anti-fraud' ); ?></span>
						</div>
						<p class="wc-af-incident-panel__lead">
							<?php esc_html_e( 'Use this page to manage Checkout CAPTCHA, order attempt limit, payment attempt limit, and failed-payment handling when card testing appears at checkout.', 'woocommerce-anti-fraud' ); ?>
						</p>
						<p class="wc-af-ca-header__action">
							<a class="button button-secondary wc-af-btn wc-af-btn--secondary" href="<?php echo esc_url( $links['cleanup'] ); ?>"><?php esc_html_e( 'Incident tools: Failed orders & cleanup', 'woocommerce-anti-fraud' ); ?></a>
						</p>
					</div>

					<div class="wc-af-incident-panel__subsection wc-af-ca-status">
						<h4 class="wc-af-incident-panel__subsection-title">
							<span class="wc-af-incident-panel__subsection-icon" aria-hidden="true">●</span>
							<?php esc_html_e( 'Status', 'woocommerce-anti-fraud' ); ?>
						</h4>
						<div class="wc-af-incident-panel__status" aria-label="<?php echo esc_attr__( 'Card attack status', 'woocommerce-anti-fraud' ); ?>">
							<span class="<?php echo esc_attr( $captcha_class ); ?>">
								<span class="wc-af-status-pill__label"><?php echo esc_html( $captcha_label ); ?></span>
								<span class="wc-af-status-pill__value"><?php echo esc_html( $captcha_state ); ?></span>
							</span>
							<span class="<?php echo esc_attr( $pill_class( $order_limits_on ) ); ?>">
								<span class="wc-af-status-pill__label"><?php esc_html_e( 'Order attempt limit', 'woocommerce-anti-fraud' ); ?></span>
								<span class="wc-af-status-pill__value"><?php echo $order_limits_on ? esc_html__( 'On', 'woocommerce-anti-fraud' ) : esc_html__( 'Off', 'woocommerce-anti-fraud' ); ?></span>
							</span>
							<span class="<?php echo esc_attr( $pill_class( $payment_limits_on ) ); ?>">
								<span class="wc-af-status-pill__label"><?php esc_html_e( 'Payment attempt limit', 'woocommerce-anti-fraud' ); ?></span>
								<span class="wc-af-status-pill__value"><?php echo $payment_limits_on ? esc_html__( 'On', 'woocommerce-anti-fraud' ) : esc_html__( 'Off', 'woocommerce-anti-fraud' ); ?></span>
							</span>
							<span class="<?php echo esc_attr( $pill_class( $stop_failed_emails ) ); ?>">
								<span class="wc-af-status-pill__label"><?php esc_html_e( 'Failed-payment emails', 'woocommerce-anti-fraud' ); ?></span>
								<span class="wc-af-status-pill__value"><?php echo $stop_failed_emails ? esc_html__( 'Suppressed', 'woocommerce-anti-fraud' ) : esc_html__( 'Sending', 'woocommerce-anti-fraud' ); ?></span>
							</span>
						</div>
						<?php if ( null !== $failed_24h ) : ?>
							<p class="wc-af-incident-panel__metric">
								<strong><?php esc_html_e( 'Failed orders (24h, cached):', 'woocommerce-anti-fraud' ); ?></strong>
								<?php echo esc_html( number_format_i18n( $failed_24h ) ); ?>
							</p>
						<?php else : ?>
							<p class="wc-af-incident-panel__metric wc-af-incident-panel__metric--muted">
								<?php esc_html_e( 'Open Failed orders & cleanup once to load this count.', 'woocommerce-anti-fraud' ); ?>
							</p>
						<?php endif; ?>
					</div>

					<div class="wc-af-incident-panel__subsection wc-af-ca-actions">
						<h4 class="wc-af-incident-panel__subsection-title">
							<span class="wc-af-incident-panel__subsection-icon" aria-hidden="true">↗</span>
							<?php esc_html_e( 'Quick actions', 'woocommerce-anti-fraud' ); ?>
						</h4>
						<div class="wc-af-incident-panel__quick-links" role="navigation" aria-label="<?php echo esc_attr__( 'Card attack quick actions', 'woocommerce-anti-fraud' ); ?>">
							<a class="button button-small wc-af-btn wc-af-btn--secondary" href="<?php echo esc_url( $links['captcha'] ); ?>"><?php esc_html_e( 'Checkout CAPTCHA', 'woocommerce-anti-fraud' ); ?></a>
							<a class="button button-small wc-af-btn wc-af-btn--secondary" href="<?php echo esc_url( $links['cleanup'] ); ?>"><?php esc_html_e( 'Failed orders & cleanup', 'woocommerce-anti-fraud' ); ?></a>
							<a class="button button-small wc-af-btn wc-af-btn--secondary" href="<?php echo esc_url( $links['email'] ); ?>"><?php esc_html_e( 'Email alerts', 'woocommerce-anti-fraud' ); ?></a>
							<a class="button button-small wc-af-btn wc-af-btn--secondary" href="<?php echo esc_url( $links['general'] ); ?>"><?php esc_html_e( 'Core protection', 'woocommerce-anti-fraud' ); ?></a>
						</div>
					</div>

					<div class="wc-af-incident-panel__subsection wc-af-incident-panel__subsection--guidance wc-af-ca-guidance">
						<h4 class="wc-af-incident-panel__subsection-title">
							<span class="wc-af-incident-panel__subsection-icon" aria-hidden="true">i</span>
							<?php esc_html_e( 'During a card attack', 'woocommerce-anti-fraud' ); ?>
						</h4>
						<p class="wc-af-incident-panel__subsection-hint"><?php esc_html_e( 'Use this checklist when card testing traffic increases and checkout declines climb.', 'woocommerce-anti-fraud' ); ?></p>
						<ol class="wc-af-incident-panel__recommended-list">
							<li><?php esc_html_e( 'Confirm Checkout CAPTCHA is active in Status above.', 'woocommerce-anti-fraud' ); ?></li>
							<li><?php esc_html_e( 'Keep Order attempt limit and Payment attempt limit enabled.', 'woocommerce-anti-fraud' ); ?></li>
							<li><?php esc_html_e( 'Move failed orders to trash from Failed orders & cleanup.', 'woocommerce-anti-fraud' ); ?></li>
							<li><?php esc_html_e( 'If inboxes flood, suppress failed-payment emails temporarily and tune Email alerts.', 'woocommerce-anti-fraud' ); ?></li>
						</ol>
					</div>
				</div>
				<?php
			}

			/**
			 * Render the WooCommerce Anti-Fraud settings "Home" control center (default section).
			 *
			 * @since 7.2.5
			 *
			 * @param array $settings_fileds Field definitions keyed by id from {@see get_settings()} (empty section).
			 */
			public function render_home_control_center( $settings_fileds ) {

				if ( ! function_exists( 'wc_af_get_home_control_center_data' ) ) {
					require_once WOOCOMMERCE_ANTI_FRAUD_PLUGIN_DIR . 'anti-fraud-core/wc-af-home-snapshot.php';
				}

				$data       = wc_af_get_home_control_center_data();
				$home_intro = '';

				if ( isset( $settings_fileds['wc_af_home_settings']['desc'] ) ) {
					$home_intro = $settings_fileds['wc_af_home_settings']['desc'];
				}

				include WOOCOMMERCE_ANTI_FRAUD_PLUGIN_DIR . 'anti-fraud-core/partials/admin-settings-home.php';
			}
		}

		// Guard against duplicate registration when WooCommerce filters settings pages
		// multiple times in one request lifecycle.
		foreach ( $settings as $settings_page ) {
			if ( is_object( $settings_page ) && isset( $settings_page->id ) && 'wc_af' === $settings_page->id ) {
				return $settings;
			}
		}

		static $wc_af_settings_page_instance = null;
		if ( null === $wc_af_settings_page_instance ) {
			$wc_af_settings_page_instance = new WC_AF_Settings();
		}
		$settings[] = $wc_af_settings_page_instance;

		return $settings;
	}

	include_once WOOCOMMERCE_ANTI_FRAUD_PLUGIN_DIR . 'anti-fraud-core/settings/class-settings-base.php';
	include_once WOOCOMMERCE_ANTI_FRAUD_PLUGIN_DIR . 'anti-fraud-core/settings/class-settings-recaptcha.php';
	include_once WOOCOMMERCE_ANTI_FRAUD_PLUGIN_DIR . 'anti-fraud-core/settings/class-settings-whitelist.php';
	include_once WOOCOMMERCE_ANTI_FRAUD_PLUGIN_DIR . 'anti-fraud-core/settings/class-settings-blacklist.php';

	add_filter( 'woocommerce_get_settings_pages', 'wc_af_add_settings', 15 );

	/**
	 * AJAX handler for the marketplace detection test tool.
	 *
	 * Defined as a standalone function so it can be called during AJAX requests
	 * without needing WC_AF_Settings (which requires WC_Settings_Page — a class
	 * that is not loaded during AJAX) to be instantiated.
	 *
	 * @since 7.3.0
	 */
	function wc_af_ajax_test_marketplace_detection() {
		check_ajax_referer( 'wc_af_test_marketplace_nonce', '_wpnonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woocommerce-anti-fraud' ) ) );
		}

		$source  = isset( $_POST['source'] ) ? sanitize_key( $_POST['source'] ) : 'ebay';
		$is_hpos = get_option( 'woocommerce_custom_orders_table_enabled', 'no' );

		$order = wc_create_order();
		$order->set_billing_first_name( 'AntifraudTest' );
		$order->set_billing_last_name( 'Buyer' );
		$order->set_billing_address_1( '1 Test Street' );
		$order->set_billing_city( 'Testville' );
		$order->set_billing_postcode( '12345' );
		$order->set_billing_country( 'US' );
		$order->set_billing_phone( '+12025551234' );
		$order->set_total( 49.99 );
		$order->set_payment_method( 'bacs' );
		$order->update_meta_data( '_customer_ip_address', '' );

		switch ( $source ) {
			case 'ebay':
				$order->set_billing_email( 'testbuyer@members.ebay.com' );
				$order->update_meta_data( '_wplister_ebay_order_id', 'EBAY-TEST-' . time() );
				$order->set_created_via( 'rest-api' );
				$desc = '_wplister_ebay_order_id + eBay email + created_via=rest-api';
				break;
			case 'amazon':
				$order->set_billing_email( 'testbuyer@marketplace.amazon.com' );
				$order->update_meta_data( '_amazon_order_id', '123-' . time() . '-9999999' );
				$order->set_created_via( 'rest-api' );
				$desc = '_amazon_order_id + Amazon email + created_via=rest-api';
				break;
			case 'etsy':
				$order->set_billing_email( 'testbuyer@buyer.etsy.com' );
				$order->update_meta_data( '_etsy_receipt_id', 'ETSY-RCP-' . time() );
				$order->set_created_via( 'rest-api' );
				$desc = '_etsy_receipt_id + Etsy email + created_via=rest-api';
				break;
			case 'unknown_import':
				$order->set_billing_email( 'import.test.' . time() . '@example.com' );
				$order->set_created_via( 'rest-api' );
				$desc = 'No marketplace meta, no IP, created_via=rest-api';
				break;
			case 'native':
			default:
				$source = 'native';
				$order->set_billing_email( 'native.test.' . time() . '@example.com' );
				$order->set_created_via( 'checkout' );
				$order->update_meta_data( '_customer_ip_address', '127.0.0.1' );
				$desc = 'created_via=checkout + IP — standard WooCommerce order';
				break;
		}

		$order->save();
		$order_id = $order->get_id();

		if ( ! $order_id ) {
			wp_send_json_error( array( 'message' => __( 'Could not create test order.', 'woocommerce-anti-fraud' ) ) );
		}

		$helper = new WC_AF_Score_Helper();
		$helper->do_check( $order_id );

		$order        = wc_get_order( $order_id );
		$detected     = WC_AF_Marketplace_Detector::get_saved_source( $order_id );
		$score_raw    = opmc_hpos_get_post_meta( $order_id, 'wc_af_score', true );
		$risk_display = '' !== $score_raw
			? WC_AF_Score_Helper::invert_score( $score_raw ) . '% risk'
			: 'Not scored';
		$skipped      = opmc_hpos_get_post_meta( $order_id, '_wc_af_marketplace_skipped_rules', true );
		$order_status = wc_get_order_status_name( $order->get_status() );
		$mp_enabled   = WC_AF_Marketplace_Detector::is_enabled();
		$mp_profile   = $mp_enabled ? WC_AF_Marketplace_Detector::get_effective_profile( $detected ) : null;
		$source_label = WC_AF_Marketplace_Detector::get_source_label( $detected );
		$source_icon  = WC_AF_Marketplace_Detector::get_source_icon( $detected );

		$profile_cell = 'None — detection disabled or native order';
		if ( $mp_profile ) {
			$profile_cell = '&#10003; ' . esc_html( $mp_profile['label'] )
				. ' (action: ' . esc_html( $mp_profile['action_on_suspicion'] ) . ', '
				. ( $mp_profile['ignore_unknown_origin'] ? 'origin check skipped' : 'origin check active' )
				. ', +' . intval( $mp_profile['base_score_bonus'] ) . ' score bonus)';
		} elseif ( $mp_enabled && 'woocommerce_native' !== $detected ) {
			$profile_cell = 'Treat as native — full standard rules applied';
		}

		$skipped_cell = ( ! empty( $skipped ) && is_array( $skipped ) )
			? implode( ', ', $skipped )
			: ( $mp_profile ? 'None triggered' : '—' );

		$status_icon = in_array( $order->get_status(), array( 'cancelled' ), true ) ? '&#10060;' : '&#9989;';

		if ( 'yes' === $is_hpos ) {
			$order_url = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id );
		} else {
			$order_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
		}

	// Delete nonce for the AJAX delete handler (not a WC admin URL nonce).
	$delete_nonce = wp_create_nonce( 'wc_af_delete_test_order_' . $order_id );

	$rows = array(
		array( 'Test order #',                $order_id ),
		array( 'Signals injected',            $desc ),
		array( 'Detected source',             $source_icon . ' ' . $source_label ),
		array( 'Marketplace profile applied', $profile_cell ),
		array( 'Rules skipped by profile',    $skipped_cell ),
		array( 'Fraud risk score',            $risk_display ),
		array( 'Order status after check',    $status_icon . ' ' . $order_status ),
	);

	$passed = ! in_array( $order->get_status(), array( 'cancelled' ), true );

	wp_send_json_success( array(
		'title'        => 'Detection test complete — ' . $source_label,
		'icon'         => $source_icon,
		'order_id'     => $order_id,
		'order_url'    => $order_url,
		'delete_nonce' => $delete_nonce,
		'rows'         => $rows,
		'passed'       => $passed,
	) );
	}

add_action( 'wp_ajax_wc_af_test_marketplace_detection', 'wc_af_ajax_test_marketplace_detection' );

/**
 * AJAX handler: permanently delete a test order created by the test tool.
 *
 * @since 7.3.0
 */
	function wc_af_ajax_delete_test_order() {
		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

		if ( ! $order_id ) {
			wp_send_json_error( array( 'message' => 'Invalid order ID.' ) );
		}

		check_ajax_referer( 'wc_af_delete_test_order_' . $order_id, '_wpnonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( array( 'message' => 'Order not found.' ) );
		}

		$order->delete( true ); // true = force-delete, skip trash
		wp_send_json_success( array( 'message' => 'Test order #' . $order_id . ' deleted.' ) );
	}

add_action( 'wp_ajax_wc_af_delete_test_order', 'wc_af_ajax_delete_test_order' );

endif;
