<?php
/**
 * Searchanise Admin settings
 *
 * @package Searchanise/Admin
 */

namespace Searchanise\SmartWoocommerceSearch;

defined( 'ABSPATH' ) || exit;

/**
 * Administrator class
 */
class Admin {

	/**
	 * Lang code
	 *
	 * @var string
	 */
	private $lang_code = '';

	/**
	 * Admin constructor
	 *
	 * @param string $lang_code Lang code.
	 */
	public function __construct( $lang_code = null ) {
		$this->lang_code = $lang_code ? $lang_code : Api::get_instance()->get_locale();

		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'check_gddpr_redirect' ), 9999 );
		add_action( 'wp_loaded', array( $this, 'register' ) );
	}

	/**
	 * Admin init. Performs basic check
	 */
	public function init() {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice-error notice"><p>'
						. '<b>' . esc_html( Api::get_instance()->get_product_name() ) . '</b></p><p>'
						. wp_kses_data( __( '<a href="https://wordpress.org/plugins/woocommerce">WooCommerce</a> plugin should be enabled to work correctly.', 'smart-search-for-woocommerce' ) ) . '</p></div>';
				}
			);

			if ( current_user_can( 'activate_plugins' ) ) {
				deactivate_plugins( SE_ABSPATH . DIRECTORY_SEPARATOR . 'woocommerce-searchanise.php' );

				add_action(
					'admin_notices',
					function () {
						echo '<div class="notice-error notice"><p>'
							. '<b>' . esc_html( Api::get_instance()->get_product_name() ) . '</b></p><p>'
							. wp_kses_data( __( 'Plugin was deactivated.', 'smart-search-for-woocommerce' ) ) . '</p></div>';
					}
				);

				if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					unset( $_GET['activate'] );
				}
			}
		}

		Installer::create_caps();

		if ( $this->count_bubble_notices() ) {
			global $submenu;
			$plugin_titles = array( 'Searchanise', 'Smart Search and Product Filter' );
			$count         = $this->count_bubble_notices();

			if ( isset( $submenu['woocommerce'] ) ) {
				foreach ( $submenu['woocommerce'] as &$menu_item ) {
					if ( is_array( $menu_item ) && in_array( $menu_item[0], $plugin_titles, true ) ) {
						$menu_item[0] .= " <span class='awaiting-mod count-" . esc_attr( $count ) . "'><span class='pending-count'>" . absint( $count ) . '</span></span>';
						break;
					}
				}
			}
		}
	}

	/**
	 * Print reigstration error notice
	 *
	 * @return void
	 */
	public function error_register_plugin_notice() {
		/* translators: %s: support email */
		echo '<div class="notice-warning notice"><p>'
			. '<b>' . esc_html( Api::get_instance()->get_product_name() ) . '</b></p><p>' . wp_kses(
				sprintf(
					'Unable to register plugin. Please, contact Searchanise <a href="mailto:%s">%s</a> technical support',
					SE_SUPPORT_EMAIL,
					SE_SUPPORT_EMAIL
				),
				array( 'a' => array( 'href' => array() ) )
			) . '</p></div>';
	}

	/**
	 * Register backend scripts
	 */
	public function register() {
		if ( ! is_admin() ) {
			return;
		}

		// Network activation, try to install pluging.
		if ( is_multisite() && Api::get_instance()->get_module_status() != 'Y' ) {
			// Network activation, try to install pluging.
			Cron::unregister();

			if ( Installer::install() ) {
				// Register searchanise info page.
				add_rewrite_rule( '^searchanise/info', 'index.php?is_searchanise_page=1&post_type=page', 'top' );
				flush_rewrite_rules();
			} else {
				add_action( 'admin_notices', array( $this, 'error_register_plugin_notice' ) );
			}
		}

		if ( Api::get_instance()->get_module_status() != 'Y' ) {
			return;
		}

		if ( ! Upgrade::is_updated() ) {
			if ( Upgrade::process_upgrade() ) {
				$text_notification = sprintf(
					/* translators: %s: admin panel */
					__( 'Plugin was successfully updated. Catalog indexation in process. <a href="%s">Admin Panel</a>.', 'smart-search-for-woocommerce' ),
					Api::get_instance()->get_admin_url()
				);

				if ( SE_PLUGIN_VERSION == '1.0.12' ) {
					Api::get_instance()->add_admin_notitice(
						sprintf(
							/* translators: %s: admin panel */
							__( 'In the new version 1.0.12 of the plugin, the settings moved from <b>Settings → Searchanise</b> to the <b><a href="%1$s">WooCommerce → Settings → Searchanise</a></b> and <br />admin panel moved from <b>Products → Searchanise</b> to <b><a href="%2$s"> Woocommerce → Searchanise</a></b>.', 'smart-search-for-woocommerce' ),
							$this->get_admin_settings_link(),
							admin_url( 'admin.php?page=searchanise' )
						),
						'info'
					);
				}
			}
		} elseif ( Api::get_instance()->check_auto_install() ) {
			if ( ! Api::get_instance()->use_gddpr_registration() ) {
				$text_notification = sprintf(
					/* translators: %s: admin panel */
					__( 'Plugin was successfully installed. Catalog indexation in process. <a href="%s">Admin Panel</a>.', 'smart-search-for-woocommerce' ),
					Api::get_instance()->get_admin_url()
				);
			}
		} elseif ( Api::get_instance()->get_is_need_reindexation() ) {
			// Full reindexation, usually used after addon activating.
			$text_notification = sprintf(
				/* translators: %s: admin panel */
				__( 'Plugin was successfully activated. Catalog indexation in process. <a href="%s">Admin Panel</a>.', 'smart-search-for-woocommerce' ),
				Api::get_instance()->get_admin_url()
			);
			Api::get_instance()->set_is_need_reindexation( false );
		}

		if ( ! empty( $text_notification ) ) {
			$this->signup( $text_notification );
		} else {
			Api::get_instance()->show_notification_async_completed();
		}

		$this->searchanise_settings();

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 999999 );
		add_filter( 'plugin_action_links_' . SE_PLUGIN_BASENAME, array( $this, 'admin_settings_link' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
		add_action( 'admin_notices', array( $this, 'display_wp_dashboard_notices' ) );
	}

	/**
	 * Run signup
	 *
	 * @param string $text_notification Success notification.
	 * @return bool
	 */
	public function signup( $text_notification = '' ) {
		if ( Api::get_instance()->signup( null, false ) ) {
			Api::get_instance()->queue_import( null, false );

			if ( '' != $text_notification ) {
				Api::get_instance()->add_admin_notitice( $text_notification, 'success' );
			}

			return true;
		} else {
			Api::get_instance()->add_admin_notitice(
				sprintf(
				/* translators: %s: support email */
					__( 'Something is wrong in plugin registration. Please contact Searchanise <a href="mailto:%1$s">%2$s</a> technical support', 'smart-search-for-woocommerce' ),
					SE_SUPPORT_EMAIL,
					SE_SUPPORT_EMAIL
				),
				'error'
			);

			return false;
		}
	}

	/**
	 * Returns admin searchanise settings page link
	 *
	 * @return string
	 */
	public function get_admin_settings_link() {
		return get_admin_url( null, 'admin.php?page=wc-settings&tab=searchanise_settings' );
	}

	/**
	 * Checks gddpr redirect
	 *
	 * @return void
	 */
	public function check_gddpr_redirect() {
		if ( Api::get_instance()->get_module_status() == 'Y' && Api::get_instance()->check_auto_install() && Api::get_instance()->check_gddpr_redirect() ) {
			Api::get_instance()->set_gddpr_redirect( false );
			$this->redirect_to_gddpr_page();
		}
	}

	/**
	 * Returns admin gddpr page link
	 *
	 * @return string
	 */
	public function get_admin_gddpr_link() {
		return menu_page_url( 'searchanise-gddpr', false );
	}

	/**
	 * Redirects to gddpr page
	 *
	 * @return void
	 */
	public function redirect_to_gddpr_page() {
		wp_safe_redirect( $this->get_admin_gddpr_link(), 301 );
		exit;
	}

	/**
	 * Adds plugin links.
	 *
	 * @param array $links Links.
	 *
	 * @return array $links with additional links
	 */
	public function admin_settings_link( $links ) {
		$links[] = '<a href="' . menu_page_url( 'searchanise', false ) . '">' . __( 'Admin Panel', 'smart-search-for-woocommerce' ) . '</a>';
		$links[] = '<a href="' . $this->get_admin_settings_link() . '">' . __( 'Settings', 'smart-search-for-woocommerce' ) . '</a>';

		return $links;
	}

	/**
	 * Add the Searchanise Admin Panel menu items.
	 */
	public function admin_menu() {
		$admin_page = add_submenu_page(
			'woocommerce',
			Api::get_instance()->get_woocommerce_plugin_version() ? Api::get_instance()->get_product_name() : __( 'Searchanise', 'smart-search-for-woocommerce' ),
			Api::get_instance()->get_woocommerce_plugin_version() ? Api::get_instance()->get_product_name() : __( 'Searchanise', 'smart-search-for-woocommerce' ),
			'manage_product_terms',
			'searchanise',
			array( $this, 'searchanise_manage' )
		);

		$admin_optin_page = add_submenu_page(
			null,
			__( 'Searchanise opt-in', 'smart-search-for-woocommerce' ),
			__( 'Searchanise opt-in', 'smart-search-for-woocommerce' ),
			'manage_product_terms',
			'searchanise-gddpr',
			array( $this, 'show_gddpr_page' )
		);

		add_action( 'load-' . $admin_page, array( $this, 'load_dashboard' ) );
	}

	/**
	 * Display stored admin notice
	 */
	public function display_admin_notices() {
		$admin_notices = Api::get_instance()->get_admin_notices();
		$allowed_tags  = array(
			'div'    => array(
				'class' => array(),
			),
			'strong' => array(),
			'em'     => array(),
			'p'      => array(),
			'b'      => array(),
			'i'      => array(),
			'a'      => array(
				'href' => array(),
			),
		);

		if ( ! empty( $admin_notices ) ) {
			foreach ( $admin_notices as $notice ) {
				$class   = ! empty( $notice['type'] ) ? 'notice-' . $notice['type'] : '';
				$message = $notice['message'];
				echo wp_kses( "<div class=\"notice {$class} is-dismissible\"><p><b>" . Api::get_instance()->get_product_name() . "</b></p><p>{$message}</p></div>", $allowed_tags );
			}
		}

		return $this;
	}

	/**
	 * Adds rating request to footer
	 *
	 * @param string $footer_text Original footer text.
	 *
	 * @return string modified footer text
	 */
	public function admin_footer_text( $footer_text ) {
		$current_screen = get_current_screen();

		if ( isset( $current_screen->id ) && ! Api::get_instance()->get_woocommerce_plugin_version() && in_array( $current_screen->id, array( 'woocommerce_page_searchanise', 'product_page_searchanise' ) ) ) {
			if ( ! Api::get_instance()->get_is_rated() ) {
				$footer_text = sprintf(
					/* translators: %s: review link */
					esc_html__( 'If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'smart-search-for-woocommerce' ),
					sprintf( '<strong>%s</strong>', Api::get_instance()->get_product_name() ),
					'<a href="https://wordpress.org/support/plugin/smart-search-for-woocommerce/reviews?rate=5#new-post" target="_blank" class="se-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'smart-search-for-woocommerce' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
				);

				$script = "jQuery('a.se-rating-link').click( function() {
					jQuery.get('" . esc_js( admin_url( 'admin-ajax.php' ) ) . "', {action: 'searchanise_rated'});
					jQuery(this).parent().text(jQuery(this).data('rated'));
				});";

				if ( function_exists( 'wp_add_inline_script' ) ) {
					$searchanise_custom_handle = 'searchanise-custom-script';
					wp_register_script( $searchanise_custom_handle, false, array( 'jquery' ), SE_PLUGIN_VERSION, true );
					wp_enqueue_script( $searchanise_custom_handle );
					wp_add_inline_script( $searchanise_custom_handle, $script );
				} else {
					wc_enqueue_js( $script );
				}
			} else {
				$footer_text = esc_html__( 'Thank you for using', 'smart-search-for-woocommerce' ) .
					' <strong>' . esc_attr( Api::get_instance()->get_product_name() ) . '</strong>.';
			}
		}

		return $footer_text;
	}

	/**
	 * Load assets
	 */
	public function load_settings() {
		// Adds page to allow loads woocommerce scripts / css on them.
		add_filter(
			'woocommerce_screen_ids',
			function ( $screen_ids ) {
				return array_merge(
					$screen_ids,
					array(
						'settings_page_searchanise_settings',
					)
				);
			}
		);
		add_filter(
			'woocommerce_display_admin_footer_text',
			function ( $result ) {
				$current_screen = get_current_screen();

				if ( 'settings_page_searchanise_settings' == $current_screen->id ) {
					return false;
				}

				return $result;
			}
		);

		return $this;
	}

	/**
	 * Load Searchanise Admin Widget
	 */
	public function load_dashboard() {
		global $wp_version;

		Api::get_instance()->check_enviroments();

		$addon_options = Api::get_instance()->get_addon_options();
		$last_request  = Api::get_instance()->get_last_request( $this->lang_code );
		$last_resync   = Api::get_instance()->get_last_resync( $this->lang_code );
		$service_url   = is_ssl() ? str_replace( 'http://', 'https://', SE_SERVICE_URL ) : SE_SERVICE_URL;

		$searchanise_admin_widgets_file_path = SE_BASE_DIR . '/assets/js/se-admin-widgets.js';
		$searchanise_options                 = array(
			'version'             => SE_PLUGIN_VERSION,
			'status'              => 'enabled',
			'platform'            => SE_PLATFORM,
			'platform_edition'    => ! empty( $addon_options['woocommerce'] ) ? $addon_options['woocommerce']['Version'] : '',
			'platform_version'    => $wp_version,
			'host'                => $service_url,
			'private_key'         => Api::get_instance()->get_private_key( $this->lang_code ),
			'parent_private_key'  => Api::get_instance()->get_parent_private_key(),
			'connect_link'        => Api::get_instance()->get_admin_url( 'signup', true ),
			're_sync_link'        => Api::get_instance()->get_admin_url( 'reindex', true ),
			'last_request'        => Api::get_instance()->format_date( $last_request ),
			'last_resync'         => Api::get_instance()->format_date( $last_resync ),
			'lang_code'           => $this->lang_code,
			'name'                => Api::get_instance()->get_store_name( $this->lang_code ),
			'symbol'              => get_woocommerce_currency_symbol(),
			'decimals'            => wc_get_price_decimals(),
			'decimals_separator'  => wc_get_price_decimal_separator(),
			'thousands_separator' => wc_get_price_thousand_separator(),
			'api_key'             => Api::get_instance()->get_api_key( $this->lang_code ),
			'export_status'       => Api::get_instance()->get_export_status( $this->lang_code ),
			's_engines'           => array_values( Api::get_instance()->get_engines() ),
		);

		/**
		 * Gets admin widgets file path
		 *
		 * @since 1.0.0
		 *
		 * @param string $searchanise_admin_widgets_file_path
		 */
		$searchanise_admin_widgets_file_path = apply_filters( 'searchanise_admin_widgets_file_path', $searchanise_admin_widgets_file_path );

		/**
		 * Gets admin widgets options
		 *
		 * @since 1.0.0
		 *
		 * @param array $searchanise_options
		 */
		$searchanise_options = apply_filters( 'searchanise_admin_load_admin_widgets', $searchanise_options );

		wp_register_script( 'searchanise-admin-widgets', plugins_url( $searchanise_admin_widgets_file_path ), array(), SE_PLUGIN_VERSION, true );
		wp_localize_script( 'searchanise-admin-widgets', 'searchanise_options', $searchanise_options );
		wp_register_script( 'searchanise-link', $service_url . '/js/init.js', array( 'searchanise-admin-widgets' ), SE_PLUGIN_VERSION, true );
		wp_enqueue_style( 'searchanise-admin-css', plugins_url( SE_BASE_DIR . '/assets/css/se-admin.css' ), array(), SE_PLUGIN_VERSION, false );

		return $this;
	}

	/**
	 * Show gddpr searchanise page
	 *
	 * @return void
	 */
	public function show_gddpr_page() {
		wp_enqueue_style( 'searchanise_admin_css', plugins_url( SE_BASE_DIR . '/assets/css/se-admin.css' ), array(), SE_PLUGIN_VERSION, false );
		require_once SE_TEMPLATES_PATH . 'searchanise_optin.php';
	}

	/**
	 * Searchanise manage controller
	 */
	public function searchanise_manage() {
		if ( ! current_user_can( 'manage_searchanise' ) ) {
			wp_die( esc_html__( 'Access denied.', 'smart-search-for-woocommerce' ) );
		}

		if ( isset( $_GET['searchanise_mode'] ) ) {
			// Validate the nonce.
			if ( ! check_admin_referer( 'searchanise_action', 'searchanise_nonce' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'smart-search-for-woocommerce' ) );
			}

			$mode   = sanitize_text_field( wp_unslash( $_GET['searchanise_mode'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action = 'action_' . mb_strtolower( $mode );

			if ( ! empty( $mode ) && method_exists( $this, $action ) ) {
				call_user_func_array( array( $this, $action ), array() );
				wp_safe_redirect( Api::get_instance()->get_admin_url() );
			} else {
				wp_die( esc_html__( 'Invalid mode.', 'smart-search-for-woocommerce' ) );
			}
		}

		if ( Api::get_instance()->use_gddpr_registration() && ! Api::get_instance()->check_gddpr_accepted() ) {
			if ( ! Api::get_instance()->check_auto_install() ) {
				// User already accept gddpr.
				Api::get_instance()->set_gddpr_accepted();
			} else {
				$this->show_gddpr_page();
				return $this;
			}
		}

		wp_enqueue_script( 'searchanise-admin-widgets' );
		wp_enqueue_script( 'searchanise-link' );

		echo '<div class="wrap"><h1>'
			. esc_html( Api::get_instance()->get_product_name() )
			. '</h1><div class="snize" id="snize_container"></div></div>';

		return $this;
	}

	/**
	 * Accept gddpr action
	 *
	 * @return void
	 */
	private function action_accept_gddpr() {
		if ( ! current_user_can( 'manage_searchanise' ) ) {
			wp_die( esc_html__( 'Access denied.', 'smart-search-for-woocommerce' ) );
		}

		Api::get_instance()->set_gddpr_accepted();

		$return_url = isset( $_GET['return_url'] ) ? sanitize_text_field( wp_unslash( $_GET['return_url'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( Api::get_instance()->check_auto_install() ) {
			$text_notification = sprintf(
			/* translators: %s: admin panel */
				__( 'Plugin was successfully installed. Catalog indexation in process. <a href="%s">Admin Panel</a>.', 'smart-search-for-woocommerce' ),
				Api::get_instance()->get_admin_url()
			);

			$this->signup( $text_notification );
		}

		if ( $return_url ) {
			wp_safe_redirect( $return_url );
		}
	}

	/**
	 * Signup controller action
	 */
	private function action_signup() {
		if ( Api::get_instance()->get_module_status() == 'Y' && Api::get_instance()->signup() ) {
			Api::get_instance()->queue_import();
		}

		return $this;
	}

	/**
	 * Reindex controller action
	 */
	private function action_reindex() {
		if ( Api::get_instance()->get_module_status() == 'Y' && Api::get_instance()->signup() ) {
			Api::get_instance()->queue_import();
		}

		return $this;
	}

	/**
	 * Returns settings list for reindex
	 *
	 * @param string $name Setting name.
	 *
	 * @return boolean
	 */
	public function need_setting_reindexation( $name ) {
		return in_array(
			$name,
			array(
				'se_use_direct_image_links',
				'se_import_block_posts',
				'se_excluded_tags',
				'se_excluded_pages',
				'se_excluded_categories',
				'se_custom_attribute',
				'se_custom_product_fields',
				'se_custom_taxonomies',
			)
		);
	}

	/**
	 * Settings controller
	 */
	public function searchanise_settings() {
		global $searchanise_need_reindexation;

		$admin_setting = new Admin_Setting();
		$admin_setting->init();

		if (
			isset( $_SERVER['REQUEST_METHOD'] ) &&
			'POST' == $_SERVER['REQUEST_METHOD'] &&
			isset( $_REQUEST['searchanise_mode'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'update' == $_REQUEST['searchanise_mode'] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		) {
			if ( ! current_user_can( 'manage_searchanise_settings' ) ) {
				wp_die( esc_html__( 'Access denied.', 'smart-search-for-woocommerce' ) );
			}

			if ( ! isset( $_POST['searchanise_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['searchanise_settings_nonce'] ) ), 'searchanise_settings_nonce' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				wp_die( esc_html__( 'Security check failed.', 'smart-search-for-woocommerce' ) );
			}

			$post        = filter_input_array( INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS );
			$se_settings = isset( $post['se_search_input_selector'] ) ? $post : array();

			if ( ! empty( $se_settings ) ) {
				$need_reindexation = false;

				foreach ( $post as $name => $val ) {
					if ( $this->need_setting_reindexation( $name ) ) {
						$old_value          = Api::get_instance()->get_system_setting( $name );
						$need_reindexation |= $old_value != $val;
					}

					if ( in_array( $name, array( 'color_attribute', 'size_attribute' ) ) ) {
						$old_value = Api::get_instance()->get_system_setting( $name );

						if ( $old_value != $val ) {
							// Need attribute reindexation.
							Queue::get_instance()->add_action_update_attributes();
						}
					}

					if ( 'search_result_page' == $name ) {
						Installer::create_search_results_page( array( 'post_name' => $val ), true );
					}

					Api::get_instance()->set_system_setting( $name, $val );
				}

				$searchanise_need_reindexation = $need_reindexation;
			}

			flush_rewrite_rules();

			$referrer_url = wp_get_referer();
			wp_safe_redirect( false === $referrer_url ? $this->get_admin_settings_link() : $referrer_url );
		}

		return $this;
	}

	/**
	 * Returns all pages for system settings
	 *
	 * @param string $lang_code Lanuage code.
	 *
	 * @return array
	 */
	public function get_all_pages( $lang_code ) {
		$pages = array();

		$posts = get_posts(
			array(
				'post_type'   => Async::get_post_types(),
				'numberposts' => -1,
			)
		);

		foreach ( $posts as $post ) {
			$pages[ $post->post_name ] = $post->post_title;
		}

		/**
		 * Returns all pages for system settings
		 *
		 * @since 1.0.0
		 *
		 * @param array $pages
		 * @param string $lang_code
		 */
		return (array) apply_filters( 'searchanise_admin_get_all_pages', $pages, $lang_code );
	}

	/**
	 * Returns all categories for system settings
	 *
	 * @param string $lang_code Lanuage code.
	 *
	 * @return array
	 */
	public function get_all_categories( $lang_code ) {
		$categories = array();

		$terms = get_terms( 'product_cat' );

		foreach ( $terms as $term ) {
			$categories[ $term->slug ] = $term->name;
		}

		/**
		 * Returns all categories for system settings
		 *
		 * @since 1.0.0
		 *
		 * @param array $categories
		 * @param string $lang_code
		 */
		return (array) apply_filters( 'searchanise_admin_get_all_categories', $categories, $lang_code ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	}

	/**
	 * Displays notice on dashboard
	 *
	 * @return void
	 */
	public function display_wp_dashboard_notices() {

		$store_data     = Api::get_instance()->get_woocommerce_state_data();
		$se_admin_panel = get_admin_url( null, '/admin.php?page=searchanise' );

		if ( false === $store_data ) {
			return;
		}

		if ( is_admin() ) {
			$screen = get_current_screen();

			$allowed_tags = array(
				'div' => array(
					'class' => array(),
				),
				'p'   => array(),
				'a'   => array(
					'href' => array(),
				),
			);

			if ( 'dashboard' === $screen->id ) {
				if ( isset( $store_data['subscription_expired'] ) && 'Y' === $store_data['subscription_expired'] ) {
					echo wp_kses(
						'<div class=\"notice notice-warning is-dismissible\">
						<p>Your payment for Searchanise failed for some reason. <a href="' . esc_url( $se_admin_panel ) . '">Please reactivate your Searchanise</a> subscription.</p></div>',
						$allowed_tags
					);
				}
				if ( isset( $store_data['indexing_error'] ) && 'Y' === $store_data['indexing_error'] ) {
					echo wp_kses(
						'<div class=\"notice notice-warning is-dismissible\">
						<p>It looks like the sync with the search engine failed. Please address Searchanise support <a href=\'mailto:feedback@searchanise.io\'>feedback@searchanise.io</a> to solve the issue.</p></div>',
						$allowed_tags
					);
				}
				if ( isset( $store_data['trail_over'] ) && 'Y' === $store_data['trail_over'] ) {
					echo wp_kses(
						'<div class=\"notice notice-warning is-dismissible\">
						<p>Your Searchanise trial has ended. <a href="' . esc_url( $se_admin_panel ) . '"> Please select a plan</a> to continue using the app.</p></div>',
						$allowed_tags
					);
				}
				if ( isset( $store_data['products_over_limit'] ) && 'Y' === $store_data['products_over_limit'] ) {
					echo wp_kses(
						'<div class=\"notice notice-warning is-dismissible\">
						<p>The product limit of your store has been exceeded. <a href="' . esc_url( $se_admin_panel ) . '"> Please upgrade your Searchanise plan</a>.</p></div>',
						$allowed_tags
					);
				}
			}
		}
	}

	/**
	 * Count the number of bubble notices
	 *
	 * @return int|mixed
	 */
	public function count_bubble_notices() {
		$store_data = Api::get_instance()->get_woocommerce_state_data();

		if ( is_array( $store_data ) ) {
			$value_counts = array_count_values( $store_data );

			return isset( $value_counts['Y'] ) ? $value_counts['Y'] : 0;
		}

		return 0;
	}
}
