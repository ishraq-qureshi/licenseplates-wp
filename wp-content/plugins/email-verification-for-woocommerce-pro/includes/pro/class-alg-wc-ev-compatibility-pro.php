<?php
/**
 * Email Verification for WooCommerce - Compatibility Class.
 *
 * @version 2.7.6
 * @since   2.3.3
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Email_Verification_Compatibility_Pro' ) ) :

	class Alg_WC_Email_Verification_Compatibility_Pro {

		/**
		 * $auto_verify_from_3rd_party.
		 *
		 * @version 2.0.7
		 * @since   2.0.7
		 *
		 * @var bool
		 */
		protected $auto_verify_from_3rd_party = false;

		/**
		 * Is user verified by Elementor - Essentials addon - Login Register element?
		 *
		 * @since 2.2.8
		 *
		 * @var bool
		 */
		protected $eael_user_verified = null;

		/**
		 * $pmpro_skip_user_verification.
		 *
		 * @since 2.3.2
		 *
		 * @var bool
		 */
		protected $pmpro_skip_user_verification = null;

		/**
		 * Constructor.
		 *
		 * @version 2.8.1
		 * @since   2.3.3
		 */
		function __construct() {
			add_filter( 'alg_wc_ev_is_user_verified', array( $this, 'accept_verification_by_3rd_party' ), 10, 2 );

			// Nextend Social Login.
			add_action( 'nsl_login', array( $this, 'verify_nsl_user' ), 10 );
			add_action( 'nsl_register_new_user', array( $this, 'verify_nsl_user' ), 10 );

			// Social Login (Skyverge).
			add_action( 'wc_social_login_user_authenticated', array( $this, 'verify_social_login_skyverge_user' ) );
			add_action( 'wc_social_login_before_user_login', array( $this, 'verify_social_login_skyverge_user' ) );
			add_action( 'wc_social_login_before_create_user', array( $this, 'wc_social_login_before_create_user' ) );

			// WooMail - https://codecanyon.net/item/email-customizer-for-woocommerce-with-drag-drop-builder-woo-email-editor/22400984
			add_filter( 'do_shortcode_tag', array( $this, 'create_woomail_shortcode_parameter' ), 10, 3 );

			// Compatibility with Email Customizer.
			add_action( 'alg_wc_ev_ec_email_content', array( $this, 'get_email_from_email_customizer_plugin_and_display_activation_email_content' ), 10, 2 );

			// Elementor - Essentials addon - Login Register element.
			add_action( 'set_auth_cookie', array( $this, 'elementor_ea_login_register_set_auth_cookie' ), 10, 6 );
			add_filter( 'send_auth_cookies', array( $this, 'elementor_ea_login_register_send_auth_cookies' ) );
			add_action( 'wp_login', array( $this, 'elementor_ea_login_register_wp_login' ), 10, 2 );

			// Paid Memberships Pro.
			add_filter( 'alg_wc_ev_is_user_verified', array( $this, 'auto_verify_pmpro_user' ), 10, 2 );
			add_filter( 'alg_wc_ev_is_user_verified', array( $this, 'auto_verify_pmpro_user_registration' ), 10, 2 );
			add_action( 'pmpro_checkout_before_user_auth', array( $this, 'skip_pmpro_verification_on_user_registration' ) );

			// Template Customizer for WooCommerce by VillaTheme.
			add_filter( 'viwec_register_replace_shortcode', array( $this, 'add_villatheme_email_customizer_special_text' ), 10, 3 );
			add_filter( 'viwec_register_replace_shortcode', array( $this, 'add_villatheme_email_customizer_placeholders' ), 10, 3 );
			add_filter( 'viwec_register_preview_shortcode', array( $this, 'register_villatheme_email_customizer_render_preview_shortcode' ), 20 );
			add_filter( 'viwec_live_edit_shortcodes', array( $this, 'register_villatheme_email_customizer_render_preview_shortcode' ), 20 );
			add_filter( 'viwec_register_email_type', array( $this, 'register_villatheme_email_customizer_email_type' ), 10 );

			// Polylang.
			add_filter( 'init', array( $this, 'add_compatibility_with_polylang' ) );

			// Yaymail.
			add_filter('yaymail_customs_shortcode',array($this, 'add_activation_email_msg_shortcode_to_yaymail' ),10,3);

			// Woodmart.
			add_filter( 'alg_wc_ev_is_user_verified', array( $this, 'auto_verify_woodmart_auth' ), 10, 2 );

			// Login/Signup Popup.
			add_filter( 'xoo_el_registration_success_notice', array( $this, 'replace_xoo_registration_message' ), 10, 2 );
			add_filter( 'xoo_el_process_registration_errors', array( $this, 'disable_xoo_registration_auto_login' ) );

			// Wp Social Login and Register Social Counter.
			add_action( 'wslu_social/before_create_user', array( $this, 'wslu_social_activate_on_before_create_user' ) );
		}

		/**
		 * wslu_social_activate_on_before_create_user.
		 *
		 * @version 2.8.1
		 * @since   2.8.1
		 *
		 * @return void
		 */
		function wslu_social_activate_on_before_create_user() {
			if ( 'yes' === get_option( 'alg_wc_ev_wslu_verify_user_on_sign_up', 'no' ) ) {
				$new_user_action = apply_filters( 'alg_wc_ev_new_user_action', ( get_option( 'alg_wc_ev_new_user_action', 'user_register' ) ) );
				add_action( $new_user_action, function ( $user_id ) {
					alg_wc_ev()->core->activate_user( array(
						'user_id'  => $user_id,
						'directly' => false
					) );
					add_filter( 'alg_wc_ev_reset_and_mail_activation_link_validation', function ( $validation, $user_id_param ) use ( $user_id ) {
						if ( $user_id == $user_id_param ) {
							$validation = false;
						}

						return $validation;
					}, 10, 2 );
				}, 10 );
			}
		}

		/**
		 * disable_xoo_registration_auto_login.
		 *
		 * @version 2.7.6
		 * @since   2.7.6
		 *
		 * @param $error
		 *
		 * @return mixed
		 */
		function disable_xoo_registration_auto_login( $error ) {
			if ( 'yes' === get_option( 'alg_wc_ev_compatibility_xoo_lsp_prevent_auto_login', 'no' ) && class_exists( '\Xoo_El_Form_Handler' ) ) {
				\Xoo_El_Form_Handler::$glSettings['m-auto-login'] = 'no';
			}

			return $error;
		}

		/**
		 * replace_xoo_registration_message.
		 *
		 * @version 2.7.6
		 * @since   2.7.6
		 *
		 * @param $notice
		 * @param $customer_id
		 *
		 * @return array|mixed|string|string[]
		 */
		function replace_xoo_registration_message( $notice, $customer_id ) {
			if ( 'yes' === get_option( 'alg_wc_ev_compatibility_xoo_lsp_replace_registration_msg', 'no' ) ) {
				$notice = alg_wc_ev()->core->messages->get_activation_message( intval( $customer_id ) );
			}

			return $notice;
		}

		/**
		 * woodmart_auto_verify.
		 *
		 * @version 2.5.3
		 * @since   2.5.3
		 *
		 * @see WOODMART_Auth
		 *
		 * @param $is_user_verified
		 * @param $user_id
		 *
		 * @return mixed|true
		 */
		function auto_verify_woodmart_auth( $is_user_verified, $user_id ) {
			if (
				'yes' === get_option( 'alg_wc_ev_woodmart_auth_auto_verify', 'no' ) &&
				isset( $_GET['opauth'] ) &&
				! empty( $_GET['opauth'] ) &&
				! empty( $opauth = unserialize( base64_decode( $_GET['opauth'] ) ) ) &&
				isset( $opauth['auth']['info']['email'] ) &&
				! empty( $email = $opauth['auth']['info']['email'] ) &&
				false !== ( $user = get_user_by( 'email', $email ) )
			) {
				alg_wc_ev()->core->activate_user( array(
					'user_id'  => $user->ID,
					'directly' => false
				) );
				$is_user_verified = true;
			}

			return $is_user_verified;
		}

		/**
		 * yaymail_custom_shortcode_activation_email_msg.
		 *
		 * @version 2.5.1
		 * @since   2.5.1
		 *
		 * @see https://docs.yaycommerce.com/yaymail/the-elements-of-design/shortcodes
		 *
		 * @param $shortcode_list
		 * @param $yaymail_informations
		 * @param $args
		 *
		 * @return string
		 */
		function yaymail_custom_shortcode_activation_email_msg( $shortcode_list, $yaymail_informations, $args ) {
			if (
				isset( $args['email'] ) &&
				property_exists( $args['email'], 'object' ) &&
				is_a( $user = $args['email']->object, 'WP_User' )
			) {
				ob_start();
				do_action( "alg_wc_ev_activation_email_content_placeholder", $user );
				$content = ob_get_contents();
				ob_end_clean();
				return $content;
			}
			return '[yaymail_custom_shortcode_alg_wc_ev_aem]';
		}

		/**
		 * add_activation_email_msg_shortcode_to_yaymail.
		 *
		 * @version 2.5.1
		 * @since   2.5.1
		 *
		 * @see https://docs.yaycommerce.com/yaymail/the-elements-of-design/shortcodes
		 *
		 * @param $shortcode_list
		 * @param $yaymail_informations
		 * @param $args
		 *
		 * @return mixed
		 */
		function add_activation_email_msg_shortcode_to_yaymail( $shortcode_list, $yaymail_informations, $args ) {
			if ( 'yes' === get_option( 'alg_wc_ev_yaymail_activation_email_msg_sc', 'no' ) ) {
				$shortcode_list['[yaymail_custom_shortcode_alg_wc_ev_aem]'] = $this->yaymail_custom_shortcode_activation_email_msg( $shortcode_list, $yaymail_informations, $args );
			}
			return $shortcode_list;
		}

		/**
		 * add_compatibility_with_polylang.
		 *
		 * @version 2.4.3
		 * @since   2.4.3
		 */
		function add_compatibility_with_polylang() {
			if ( 'yes' === get_option( 'alg_wc_ev_polylang_translate_activation_link', 'no' ) ) {
				$wc_pages_to_translate = array(
					'myaccount',
				);
				foreach ( $wc_pages_to_translate as $page_name ) {
					add_filter( 'woocommerce_get_' . $page_name . '_page_id', array( $this, 'translate_post_id_to_polylang' ) );
				}
			}
		}

		/**
		 * translate_post_id_to_polylang.
		 *
		 * @version 2.4.3
		 * @since   2.4.3
		 *
		 * @param $page_id
		 *
		 * @return mixed
		 */
		function translate_post_id_to_polylang( $page_id ) {
			if ( function_exists( 'pll_get_post' ) ) {
				$page_id = pll_get_post( $page_id );
			}
			return $page_id;
		}

		/**
		 * add_special_text_for_villatheme_email_customizer.
		 *
		 * @version 2.3.5
		 * @since   2.3.5
		 *
		 * @param $shortcodes
		 * @param $object
		 * @param $args
		 *
		 * @return mixed
		 */
		function add_villatheme_email_customizer_special_text( $shortcodes, $object, $args ) {
			if (
				'yes' === get_option( 'alg_wc_ev_comp_email_customizer_vt_special_text_enabled', 'no' ) &&
				is_email( $user_email = is_a( $object, 'WP_User' ) ? $object->user_email : ( is_a( $object, 'WC_Email' ) ? $object->recipient : null ) ) &&
				(
					( isset( $args['email'] ) && is_a( $args['email'], 'WC_Email_Customer_New_Account' ) ) ||
					( is_a( $object, 'WC_Email_Customer_New_Account' ) )
				)
			) {
				$user = get_user_by( 'email', $user_email );
				ob_start();
				do_action( "alg_wc_ev_activation_email_content_placeholder", $user );
				$content = ob_get_contents();
				ob_end_clean();
				$shortcodes['alg_wc_ev_viwec'] = array( '{alg_wc_ev_viwec}' => $content );
			}
			return $shortcodes;
		}

		/**
		 * add_placeholders_for_villatheme_email_customizer.
		 *
		 * @version 2.3.9
		 * @since   2.3.9
		 *
		 * @param $shortcodes
		 * @param $object
		 * @param $args
		 *
		 * @return mixed
		 */
		function add_villatheme_email_customizer_placeholders( $shortcodes, $object, $args ) {
			if ( 'yes' !== get_option( 'alg_wc_ev_comp_email_customizer_vt_placeholders_enabled', 'no' ) ) {
				return $shortcodes;
			}
			$placeholders = null;
			// Adds generic user placeholders.
			if ( $user = is_a( $object, 'WP_User' ) ? $object : ( isset( $args['user'] ) && is_a( $args['user'], 'WP_User' ) ? $args['user'] : null ) ) {
				$placeholders = alg_wc_ev_generate_placeholders_for_villatheme_email_customizer( $user );
			}
			// Adds verification url placeholder {alg_wc_ev_verification_url}.
			if ( isset( $args['email'] ) && is_a( $args['email'], 'ALG_WC_EV_Activation_WC_Email_Pro' ) ) {
				$placeholders['{alg_wc_ev_verification_url}'] = $args['email']->format_string( '{verification_url}' );
			}
			// Adds the placeholders.
			if ( $placeholders ) {
				$shortcodes['alg_wc_ev_placeholders'] = $placeholders;
			}
			return $shortcodes;
		}

		/**
		 * register_villatheme_email_customizer_render_preview_shortcode.
		 *
		 * @version 2.5.2
		 * @since   2.3.9
		 *
		 * @param $shortcodes
		 *
		 * @return mixed
		 */
		function register_villatheme_email_customizer_render_preview_shortcode( $shortcodes ) {
			if ( 'yes' === get_option( 'alg_wc_ev_comp_email_customizer_vt_placeholders_enabled', 'no' ) ) {
				$user                                         = wp_get_current_user();
				$placeholders                                 = alg_wc_ev_generate_placeholders_for_villatheme_email_customizer( $user );
				$placeholders['{alg_wc_ev_verification_url}'] = get_home_url() . '?alg_wc_ev_verify_email=999999';
				$shortcodes['alg_wc_ev_activation_wc_email']  = $placeholders;
				$shortcodes['alg_wc_ev_email_confirmation']   = $placeholders;
			}
			return $shortcodes;
		}

		/**
		 * register_villatheme_email_customizer_email_type.
		 *
		 * @version 2.5.2
		 * @since   2.3.9
		 *
		 * @param $emails
		 *
		 * @return mixed
		 */
		function register_villatheme_email_customizer_email_type( $emails ) {
			if ( 'yes' === get_option( 'alg_wc_ev_comp_email_customizer_vt_placeholders_enabled', 'no' ) ) {
				$emails['alg_wc_ev_activation_wc_email'] = array( 'name' => __( 'Activation email', 'emails-verification-for-woocommerce' ) );
				$emails['alg_wc_ev_email_confirmation']  = array( 'name' => __( 'Confirmation email', 'emails-verification-for-woocommerce' ) );
			}
			return $emails;
		}

		/**
		 * auto_verify_pmpro_user_registration.
		 *
		 * @version 2.3.2
		 * @since   2.3.2
		 *
		 * @param $is_user_verified
		 * @param $user_id
		 *
		 * @return bool
		 */
		function auto_verify_pmpro_user_registration( $is_user_verified, $user_id ) {
			if ( true === $this->pmpro_skip_user_verification ) {
				$is_user_verified = true;
			}
			return $is_user_verified;
		}

		/**
		 * skip_pmpro_verification_on_user_creation.
		 *
		 * @version 2.3.2
		 * @since   2.3.2
		 *
		 * @param $user_id
		 */
		function skip_pmpro_verification_on_user_registration( $user_id ) {
			if ( 'yes' === get_option( 'alg_wc_ev_compatibility_pmpro_auto_verify_registration', 'no' ) ) {
				$this->pmpro_skip_user_verification = true;
			}
		}

		/**
		 * auto_verify_pmpro_user.
		 *
		 * @version 2.3.2
		 * @since   2.3.2
		 *
		 * @param $is_user_verified
		 * @param $user_id
		 *
		 * @return bool
		 */
		function auto_verify_pmpro_user( $is_user_verified, $user_id ) {
			if (
				'yes' === get_option( 'alg_wc_ev_compatibility_pmpro_auto_verify_valid_membership', 'no' ) &&
				function_exists( 'pmpro_getMembershipLevelForUser' ) &&
				! empty( pmpro_getMembershipLevelForUser( $user_id ) )
			) {
				$is_user_verified = true;
			}
			return $is_user_verified;
		}

		/**
		 * accept_verification_by_3rd_party.
		 *
		 * @version 2.1.1
		 * @since   1.6.0
		 * @see     https://codecanyon.net/item/woocommerce-social-login-wordpress-plugin/8495883 (WooCommerce Social Login - WordPress Plugin)
		 * @todo    [next] https://wordpress.org/plugins/yith-woocommerce-social-login/ (YITH WooCommerce Social Login)
		 */
		function accept_verification_by_3rd_party( $is_user_verified, $user_id ) {

			if ( 'yes' === get_option( 'alg_wc_ev_accept_social_login', 'no' ) && ! empty( $user_id ) ) {
				// WooCommerce Social Login (SkyVerge)
				if ( defined( 'WOO_SLG_USER_META_PREFIX' ) ) {
					$wooslg_by_social_login = get_user_meta( $user_id, WOO_SLG_USER_META_PREFIX . 'by_social_login', true );
					if ( 'true' === $wooslg_by_social_login ) {
						return true;
					}
				}
			} elseif (
				// Super Socializer
				'yes' === get_option( 'alg_wc_ev_super_socializer_login', 'no' )
				&& defined( 'THE_CHAMP_SS_VERSION' )
				&& ! empty( $user_id )
				&& ! empty( get_user_meta( $user_id, 'thechamp_current_id', true ) )
			) {
				return true;
			} elseif (
				// Social Login from My Listing theme
				'yes' === get_option( 'alg_wc_ev_my_listing_social_login', 'no' )
				&& ! empty( $user_id )
				&&
				(
					! empty( get_user_meta( $user_id, 'mylisting_google_account_id', true ) )
					|| ! empty( get_user_meta( $user_id, 'mylisting_facebook_account_id', true ) )
				)
			) {
				return true;
			} elseif ( $this->auto_verify_from_3rd_party ) {
				return true;
			}
			return $is_user_verified;
		}

		/**
		 * verify_nsl_user.
		 *
		 * @see https://nextendweb.com/nextend-social-login-docs/backend-developer/
		 *
		 * @version 1.9.7
		 * @since   1.9.7
		 *
		 * @param $user_id
		 */
		function verify_nsl_user( $user_id ) {
			if (
				! empty( $nextend_verify = get_option( 'alg_wc_ev_nextend_verify', array() ) )
				&& in_array( current_action(), $nextend_verify )
			) {
				update_user_meta( $user_id, 'alg_wc_ev_is_activated', '1' );
			}
		}

		/**
		 * Activates user from Social Login (Skyverge)
		 *
		 * @version 2.0.7
		 * @since   1.9.7
		 *
		 * @see https://docs.woocommerce.com/document/woocommerce-social-login-developer-docs/
		 *
		 * @param $user_id
		 */
		function verify_social_login_skyverge_user( $user_id ) {
			if ( 'yes' === get_option( 'alg_wc_ev_accept_social_login_skyverge', 'no' ) ) {
				update_user_meta( $user_id, 'alg_wc_ev_is_activated', '1' );
			}
		}

		/**
		 * wc_social_login_before_create_user.
		 *
		 * @version 2.0.7
		 * @since   2.0.7
		 *
		 * @param $profile
		 */
		function wc_social_login_before_create_user( $profile ) {
			if ( 'yes' === get_option( 'alg_wc_ev_accept_social_login_skyverge', 'no' ) ) {
				$this->auto_verify_from_3rd_party = true;
			}
		}

		/**
		 * create_woomail_shortcode_parameter.
		 *
		 * @see https://codecanyon.net/item/email-customizer-for-woocommerce-with-drag-drop-builder-woo-email-editor/22400984
		 * @see https://emailcustomizer.com/2019/10/22/how-to-create-custom-shortcode-in-email-customizer-for-woocommerce/
		 *
		 * @version 2.0.8
		 * @since   2.0.8
		 *
		 * @param $output
		 * @param $tag
		 * @param $attr
		 *
		 * @return string
		 */
		function create_woomail_shortcode_parameter( $output, $tag, $attr ) {
			if (
				'ec_woo_custom_code' == $tag
				&& 'yes' === get_option( 'alg_wc_ev_woomail', 'no' )
				&& $attr['type'] == 'alg_wc_ev_activation_email'
				&& ! empty( $user_email = do_shortcode( '[ec_woo_user_email]' ) )
			) {
				$user = get_user_by( 'email', $user_email );
				ob_start();
				do_action( "alg_wc_ev_activation_email_content_placeholder", $user );
				$content = ob_get_contents();
				ob_end_clean();
				return $content;
			}
			return $output;
		}

		/**
		 * get_email_from_email_customizer_plugin_and_display_activation_email_content.
		 *
		 * @see https://help.themehigh.com/hc/en-us/articles/4405390768025-Add-New-Email-Template#h_01FDS804XTGFN0S9F2W736GKD6.
		 *
		 * @version 2.2.7
		 * @since   2.2.7
		 *
		 * @param $param1
		 * @param $param2
		 */
		function get_email_from_email_customizer_plugin_and_display_activation_email_content( $param1, $param2 ) {
			if (
				'yes' === get_option( 'alg_wc_ev_email_customizer_hook_enabled', 'no' ) &&
				! empty( $user_email = $param2->user_email )
			) {
				$activation_email_content = alg_wc_ev()->core->emails->alg_wc_ev_email_content_placeholder( array(
					'user_email' => $user_email
				) );
				echo $activation_email_content;
			}
		}

		/**
		 * elementor_ea_login_register_send_auth_cookies.
		 *
		 * @version 2.2.8
		 * @since   2.2.8
		 *
		 * @param $auth_cookie
		 * @param $expire
		 * @param $expiration
		 * @param $user_id
		 * @param $scheme
		 * @param $token
		 */
		function elementor_ea_login_register_set_auth_cookie( $auth_cookie, $expire, $expiration, $user_id, $scheme, $token ) {
			if (
				empty( $_POST['action'] ) ||
				'eael-login-register-form' !== $_POST['action'] ||
				'yes' !== get_option( 'alg_wc_ev_compatibility_elementor_ea_login_register_form', 'no' )
			) {
				return;
			}
			if (
				! empty( $check_user = get_user_by( 'ID', $user_id ) ) &&
				is_a( $check_user, 'WP_User' )
			) {
				$this->eael_user_verified = alg_wc_ev()->core->is_user_verified( $check_user );
			}
		}

		/**
		 * elementor_ea_login_register_send_auth_cookies.
		 *
		 * @version 2.2.8
		 * @since   2.2.8
		 *
		 * @param $send
		 *
		 * @return bool
		 */
		function elementor_ea_login_register_send_auth_cookies( $send ) {
			if ( false === $this->eael_user_verified ) {
				$send = false;
			}
			return $send;
		}

		/**
		 * elementor_ea_login_register_wp_login.
		 *
		 * @version 2.2.8
		 * @since   2.2.8
		 *
		 * @see Essential_Addons_Elementor\Pro\Traits\Extender;
		 *
		 * @param $login
		 * @param $check_user
		 */
		function elementor_ea_login_register_wp_login( $login, $check_user ) {
			if (
				false === $this->eael_user_verified &&
				is_a( $check_user, 'WP_User' ) &&
				! alg_wc_ev()->core->is_user_verified( $check_user )
			) {
				$error_msg = apply_filters( 'alg_wc_ev_block_unverified_user_login_error_message', alg_wc_ev()->core->messages->get_error_message( $check_user->ID ), $check_user );
				wp_send_json_error( $error_msg );
			} elseif (
				true === $this->eael_user_verified &&
				is_a( $check_user, 'WP_User' ) &&
				alg_wc_ev()->core->is_user_verified( $check_user )
			) {
				if ( false !== ( $redirect_url = alg_wc_ev()->core->get_redirect_url_on_success_activation() ) ) {
					wp_send_json_success( array(
						'message'     => __( 'You are logged in successfully', 'essential-addons-elementor' ),
						'redirect_to' => $redirect_url,
					) );
				}
			}
		}

	}

endif;

return new Alg_WC_Email_Verification_Compatibility_Pro();
