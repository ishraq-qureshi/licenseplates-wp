<?php
/**
 * Email Verification for WooCommerce - Pro Class.
 *
 * @version 3.0.4
 * @since   1.1.0
 *
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Email_Verification_Pro' ) ) :

class Alg_WC_Email_Verification_Pro {

	/**
	 * Core.
	 *
	 * @since   2.7.0
	 *
	 * @var Alg_WC_Email_Verification_Core
	 */
	public $core;

	/**
	 * $compatibility.
	 *
	 * @since   2.7.0
	 *
	 * @var Alg_WC_Email_Verification_Compatibility_Pro
	 */
	public $compatibility;

	/**
	 * Rest.
	 *
	 * @since   2.7.0
	 *
	 * @var Alg_WC_Email_Verification_REST_API_Pro
	 */
	public $rest;

	/**
	 * Constructor.
	 *
	 * @version 3.0.4
	 * @since   1.1.0
	 * @todo    [next] Block thank you (`maybe_redirect_to_myaccount`?): Cancelled order (also block login)
	 * @todo    (maybe) email verification + order statuses?
	 * @todo    (maybe) emails whitelist
	 */
	function __construct() {
		// Composer.
		require_once alg_wc_ev()->plugin_path() . '/includes/pro/vendor/autoload.php';

		// Initializes WPFactory Key Manager library.
		if ( is_admin() ) {
			function_exists( 'wpfactory_key_manager' ) ? wpfactory_key_manager() : wpf_key_manager();
		}

		add_filter( 'alg_wc_ev_settings', array( $this, 'settings' ), 10, 3 );
		add_filter( 'alg_wc_ev_core_loaded', array( $this, 'core_loaded' ) );
		add_filter( 'alg_wc_ev_email_content', array( $this, 'set_activation_email_content' ), 10, 2 );
		add_filter( 'alg_wc_ev_email_content_heading', array( $this, 'set_activation_email_content_heading' ), 10, 2 );
		add_filter( 'alg_wc_ev_email_subject', array( $this, 'set_activation_email_subject' ), 10, 2 );
		add_filter( 'alg_wc_ev_email_content_final', array( $this, 'maybe_wrap_in_wc_email_template' ), 10, 2 );
		add_action( 'alg_wc_ev_user_account_activated', array( $this, 'maybe_send_admin_email' ), 10, 2 );
		add_action( 'alg_wc_ev_after_thankyou_logout', array( $this, 'maybe_redirect_to_myaccount_action' ) );
		add_filter( 'alg_wc_ev_redirect_after_checkout', array( $this, 'maybe_redirect_to_myaccount_filter' ), 10, 2 );
		add_filter( 'alg_wc_ev_verify_email', array( $this, 'validate_blacklisted_emails' ), 10, 2 );
		add_action( 'alg_wc_ev_verify_email_error', array( $this, 'output_blacklisted_email_notice' ), 10, 2 );
		add_filter( 'alg_wc_ev_verify_email', array( $this, 'validate_activation_code_time' ), 10, 2 );
		add_action( 'alg_wc_ev_verify_email_error', array( $this, 'output_activation_code_expired_notice' ), 10, 3 );
		add_filter( 'alg_wc_ev_delete_unverified_users_loop_args', array( $this, 'add_activation_code_time_meta_query' ), 10, 3 );
		add_filter( 'alg_wc_ev_send_mail_message', array( $this, 'maybe_add_wc_email_style' ), 10, 2 );
		add_action( 'woocommerce_checkout_process', array( $this, 'maybe_block_unverified_user_on_checkout_process' ), PHP_INT_MAX );
		add_filter( 'woocommerce_checkout_create_order', array( $this, 'maybe_block_unverified_user_on_checkout_create_order' ) );

		// Unverify email changing.
		add_action( 'profile_update', array( $this, 'unverify_email_changing' ), 10, 2 );

		// Blocks content for unverified users.
		add_action( 'template_redirect', array( $this, 'block_content_for_unverified_users_by_conditionals' ) );
		add_action( 'template_redirect', array( $this, 'block_products_for_unverified_users' ) );
		add_action( 'template_redirect', array( $this, 'block_url_for_unverified_users' ) );

		// Verify the user on password reset.
		add_action( 'woocommerce_customer_reset_password', array( $this, 'verify_account_on_password_reset' ), 100, 1 );

		// Paying customers.
		add_action( 'woocommerce_order_status_changed', array( $this, 'verify_account_on_order_paid' ), 10, 3 );

		// Confirmation email.
		add_filter( 'alg_wc_ev_email_subject', array( $this, 'set_confirmation_email_subject' ), 11, 2 );
		add_filter( 'alg_wc_ev_email_content', array( $this, 'set_confirmation_email_content' ), 11, 2 );
		add_filter( 'alg_wc_ev_email_content_heading', array( $this, 'set_confirmation_email_heading' ), 11, 2 );

		// Confirmation and Activation email PHP classes.
		add_filter( 'woocommerce_email_classes', array( $this, 'add_woocommerce_email_classes' ), 10, 1 );

		// REST API.
		$this->rest = require_once( 'class-alg-wc-ev-rest-api-pro.php' );

		// Compatibility.
		$this->compatibility = require_once( 'class-alg-wc-ev-compatibility-pro.php' );
		add_action( 'init', array( $this, 'display_email_changing_message_from_cookie' ) );

		// Automatic activation email sending.
		$this->compatibility = require_once( 'class-alg-wc-ev-auto-activation-email-pro.php' );
		$auto_activation_email_sending = new Alg_WC_Email_Verification_Auto_Activation_Email_Pro();
		$auto_activation_email_sending->init();

		// Get verification param from admin settings.
		add_filter( 'alg_wc_ev_verification_param', array( $this, 'get_verification_param_from_settings' ) );
	}

	/**
	 * get_verification_param_from_settings.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 *
	 * @return void
	 */
	function get_verification_param_from_settings( $param ) {
		$param = get_option( 'alg_wc_ev_verification_parameter', 'alg_wc_ev_verify_email' );

		return $param;
	}

	/**
	 * Add activation and confirmation emails to WooCommerce emails.
	 *
	 * @version 2.3.6
	 * @since   2.3.6
	 *
	 * @param $emails
	 *
	 * @return array
	 */
	public function add_woocommerce_email_classes( $emails ) {
		if ( 'real_wc_email' === get_option( 'alg_wc_ev_wc_email_template', 'simulation' ) ) {
			$emails['ALG_WC_EV_Activation_WC_Email_Pro']   = include 'class-alg-wc-ev-activation-wc-email-pro.php';
			$emails['ALG_WC_EV_Confirmation_WC_Email_Pro'] = include 'class-alg-wc-ev-confirmation-wc-email-pro.php';
		}
		return $emails;
	}

	/**
	 * set_confirmation_email_heading.
	 *
	 * @version 2.3.1
	 * @since   2.3.1
	 */
	function set_confirmation_email_heading( $heading, $args ) {
		if ( 'confirmation_email' === $args['context'] ) {
			$heading = do_shortcode( get_option( 'alg_wc_ev_confirmation_email_heading', __( 'Your account has been activated', 'emails-verification-for-woocommerce' ) ) );
		}
		return $heading;
	}

	/**
	 * set_confirmation_email_subject.
	 *
	 * @version 2.6.6
	 * @since   2.3.1
	 */
	function set_confirmation_email_subject( $subject, $args ) {
		if ( 'confirmation_email' === $args['context'] ) {
			$subject = do_shortcode( get_option( 'alg_wc_ev_confirmation_email_subject', '[%site_title%]: ' . __( 'Your account has been activated successfully', 'emails-verification-for-woocommerce' ) ) );
		}
		return $subject;
	}

	/**
	 * filter_confirmation_email_content.
	 *
	 * @version 2.6.7
	 * @since   2.3.0
	 *
	 * @param $content
	 *
	 * @return string
	 */
	function set_confirmation_email_content( $content, $args ) {
		if ( 'confirmation_email' === $args['context'] ) {
			$content = do_shortcode( get_option( 'alg_wc_ev_confirmation_email_content', alg_wc_ev()->core->emails->get_default_email_content('confirmation') ) );
		}
		return $content;
	}

	/**
	 * verify_account_on_order_paid.
	 *
	 * @version 2.2.6
	 * @since   2.2.4
	 *
	 * @param $order_id
	 * @param $from
	 * @param $to
	 */
	function verify_account_on_order_paid( $order_id, $from, $to ) {
		if (
			'yes' === get_option( 'alg_wc_ev_auto_verify_paying_user', 'no' ) &&
			! empty( $statuses = wc_get_is_paid_statuses() ) &&
			in_array( $to, $statuses ) &&
			! empty( $order = wc_get_order( $order_id ) ) &&
			! empty( $order->get_subtotal() ) &&
			! empty( $customer_id = $order->get_customer_id() ) &&
			! alg_wc_ev_is_user_verified_by_user_id( $customer_id )
		) {
			$this->core->activate_user( array(
				'user_id'  => $customer_id,
				'directly' => false
			) );
		}
	}

	/**
	 * verify_account_on_password_reset.
	 *
	 * @version 2.1.4
	 * @since   2.1.4
	 */
	function verify_account_on_password_reset( $user ) {
		if ( 'yes' === get_option( 'alg_wc_ev_verify_account_on_password_reset', 'no' ) ) {
			if ( $user && ! is_wp_error( $user ) ) {
				$user_id = $user->ID;
				if ( ! $this->core->is_user_verified( $user ) ) {
					update_user_meta( $user_id, 'alg_wc_ev_is_activated', '1' );
					wp_safe_redirect(
						add_query_arg(
							array(
								'alg_wc_ev_success_activation_message' => 1,
								'password-reset'                       => 'true',
							),
							wc_get_page_permalink( 'myaccount' )
						)
					);
					exit;
				}
			}
		}
	}

	/**
	 * block_products_for_unverified_users.
	 *
	 * @version 2.1.1
	 * @since   2.1.1
	 */
	function block_products_for_unverified_users() {
		global $post;
		if (
			! is_admin()
			&& ! empty( $blocked_products = get_option( 'alg_wc_ev_blocked_products', array() ) )
			&& is_product()
			&& $post
			&& ( $product = wc_get_product( $post->ID ) )
			&& in_array( $product->get_id(), $blocked_products )
			&&
			(
				! is_user_logged_in()
				|| ! alg_wc_ev_is_user_verified_by_user_id( get_current_user_id() )
			)
		) {
			$redirect_url = add_query_arg( array(
				'alg_wc_ev_blocked_content' => true
			), get_option( 'alg_wc_ev_block_content_redirect', home_url() ) );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * block_url_for_unverified_users.
	 *
	 * @version 2.4.7
	 * @since   2.4.7
	 */
	function block_url_for_unverified_users() {
		if (
			! is_admin() &&
			! empty( $blocked_urls_str = get_option( 'alg_wc_ev_blocked_urls', '' ) ) &&
			(
				! is_user_logged_in()
				|| ! alg_wc_ev_is_user_verified_by_user_id( get_current_user_id() )
			) &&
			! empty( array_filter( explode( PHP_EOL, $blocked_urls_str ), function ( $url ) {
				return false !== strpos( alg_wc_ev_get_current_url(), $url );
			} ) )
		) {
			$redirect_url = add_query_arg( array(
				'alg_wc_ev_blocked_content' => true
			), get_option( 'alg_wc_ev_block_content_redirect', home_url() ) );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * block_content_for_unverified_users_by_conditionals.
	 *
	 * @version 2.1.1
	 * @since   2.1.1
	 */
	function block_content_for_unverified_users_by_conditionals() {
		if (
			! is_admin()
			&& ! empty( $conditionals = get_option( 'alg_wc_ev_blocked_conditionals', array() ) )
			&& ( $result = array_map( function ( $v ) {
				return function_exists( $v ) && call_user_func( $v );
			}, $conditionals ) )
			&& in_array( true, $result )
			&&
			(
				! is_user_logged_in()
				|| ! alg_wc_ev_is_user_verified_by_user_id( get_current_user_id() )
			)
		) {
			$redirect_url = add_query_arg( array(
				'alg_wc_ev_blocked_content' => true
			), get_option( 'alg_wc_ev_block_content_redirect', home_url() ) );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Unverify email changing.
	 *
	 * @version 2.8.6
	 * @since   2.0.3
	 *
	 * @param $user_id
	 * @param $old_user_data
	 */
	function unverify_email_changing( $user_id, $old_user_data ) {
		if (
			'no' === get_option( 'alg_wc_ev_unverify_email_changing', 'no' )
			|| empty( $old_user_email = $old_user_data->user_email )
			|| ! ( $user = get_user_by( 'ID', $user_id ) )
			|| $user->user_email == $old_user_email
		) {
			return;
		}
		// Logout.
		wp_destroy_current_session();
		wp_clear_auth_cookie();
		wp_set_current_user( 0 );
		do_action( 'wp_logout', $user_id );
		// Unverify.
		update_user_meta( $user_id, 'alg_wc_ev_is_activated', '0' );
		delete_user_meta( $user_id, 'alg_wc_ev_customer_new_account_email_sent' );
		delete_user_meta( $user_id, 'alg_wc_ev_admin_email_sent' );
		// Resend activation email.
		alg_wc_ev()->core->emails->reset_and_mail_activation_link( array( 'user_id' => $user_id ) );
		// Store coookie to display email changing message.
		wc_setcookie( 'alg_wc_ev_display_email_changing_msg', 'display' );
	}

	/**
	 * display_email_changing_message_from_cookie.
	 *
	 * @version 2.3.5
	 * @since   2.3.5
	 */
	function display_email_changing_message_from_cookie() {
		if ( isset( $_COOKIE['alg_wc_ev_display_email_changing_msg'] ) && 'display' === $_COOKIE['alg_wc_ev_display_email_changing_msg'] ) {
			alg_wc_ev_add_notice( get_option( 'alg_wc_ev_unverify_email_changing_msg', __( 'Your email has been changed. In order to verify your account please check the activation email that was sent to your new email.', 'emails-verification-for-woocommerce' ) ) );
			wc_setcookie( 'alg_wc_ev_display_email_changing_msg', 'do-not-display', 1 );
		}
	}

	/**
	 * core_loaded.
	 *
	 * @version 2.0.8
	 * @since   1.5.0
	 */
	function core_loaded( $core ) {
		$this->core = $core;
		// Block order emails
		if ( 'yes' === get_option( 'alg_wc_ev_block_customer_order_emails', 'no' ) ) {
			foreach ( get_option( 'alg_wc_ev_block_customer_order_emails_email_ids', array( 'customer_on_hold_order', 'customer_processing_order', 'customer_completed_order' ) ) as $email_id ) {
				add_filter( 'woocommerce_email_enabled_' . $email_id, array( $this, 'block_customer_order_emails' ), PHP_INT_MAX, 3 );
			}
			add_action( 'alg_wc_ev_user_account_activated', array( $this, 'send_blocked_emails' ) );
		}
		// Block guests from adding products to the cart
		if ( 'yes' === get_option( 'alg_wc_ev_block_guest_add_to_cart', 'no' ) ) {
			add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'block_guest_add_to_cart_validation' ), PHP_INT_MAX, 3 );
			add_action( 'woocommerce_init',                   array( $this, 'block_guest_add_to_cart_ajax_error' ), PHP_INT_MAX );
		}
	}

	/**
	 * Unblocks emails by sending the emails that have been blocked to the users who have just verified accounts.
	 *
	 * Sends only the email related to the current order status or/and the 'new_order' email.
	 *
	 * send_blocked_emails.
	 *
	 * @version 2.0.8
	 * @since   2.0.8
	 *
	 * @param $user_id
	 */
	function send_blocked_emails( $user_id ) {
		if ( 'yes' !== get_option( 'alg_wc_ev_block_customer_order_emails_unblock', 'yes' ) ) {
			return;
		}
		$order_ids = wc_get_orders( array(
			'customer_id' => $user_id,
			'return' => 'ids',
		) );
		$email_ids = get_option( 'alg_wc_ev_block_customer_order_emails_email_ids', array( 'customer_on_hold_order', 'customer_processing_order', 'customer_completed_order' ) );
		foreach ( WC()->mailer()->get_emails() as $wc_mail ) {
			if ( in_array( $wc_mail->id, $email_ids ) ) {
				foreach ( $order_ids as $order_id ) {
					$order = wc_get_order( $order_id );
					if (
						'new_order' === $wc_mail->id
						|| false !== strpos( str_replace( '-', '_', $wc_mail->id ), str_replace( '-', '_', $order->get_status() ) )
					) {
						$wc_mail->trigger( $order_id );
					}
				}
			}
		}
	}

	/**
	 * maybe_block_unverified_checkout_process.
	 *
	 * @version 3.0.4
	 * @since   1.8.0
	 * @todo    [next] (maybe) `alg_wc_ev_block_checkout_process_notice`: better default value
	 * @todo    (maybe) `alg_wc_ev_block_checkout_process_notice`: add placeholders (e.g. `%login_url%`)
	 */
	function maybe_block_unverified_user_on_checkout_process() {
		if ( 'yes' === get_option( 'alg_wc_ev_block_checkout_process', 'no' ) ) {

			if ( 0 === get_current_user_id() ) {
				return;
			}

			if ( ! $this->core->is_user_verified( wp_get_current_user() ) ) {
				$message = $this->get_block_checkout_message();
				alg_wc_ev_add_notice( $message, 'error' );
			}
		}
	}

	/**
	 * get_block_checkout_message.
	 *
	 * @version 3.0.4
	 * @since   3.0.4
	 *
	 * @return string
	 */
	function get_block_checkout_message() {
		return do_shortcode(
			get_option(
				'alg_wc_ev_block_checkout_process_notice',
				__( 'You need to log in and verify your email to place an order.', 'emails-verification-for-woocommerce' )
			)
		);
	}

	/**
	 * maybe_block_unverified_user_on_checkout_create_order.
	 *
	 * @version 3.0.4
	 * @since   3.0.4
	 *
	 * @param $order
	 *
	 * @throws Exception
	 * @return mixed
	 */
	function maybe_block_unverified_user_on_checkout_create_order( $order ) {
		if ( 'yes' === get_option( 'alg_wc_ev_block_checkout_process', 'no' ) ) {

			if ( 0 === get_current_user_id() ) {
				return $order;
			}

			if ( ! $this->core->is_user_verified( wp_get_current_user() ) ) {
				$message = $this->get_block_checkout_message();
				throw new Exception( $message );
			}
		}

		return $order;
	}

	/**
	 * add_activation_code_time_meta_query.
	 *
	 * @version 1.9.8
	 * @since   1.7.0
	 */
	function add_activation_code_time_meta_query( $args, $current_user_id, $is_cron ) {
		if ( 0 != ( $expiration_time = alg_wc_ev_get_expiration_time() ) ) {
			$meta_query = array(
				'key'     => 'alg_wc_ev_activation_code_time',
				'value'   => ( time() - $expiration_time ),
				'compare' => '<',
			);
			if ( isset( $args['meta_query']['relation'] ) ) {
				$_meta_query = $args['meta_query'];
				$args['meta_query'] = array( $_meta_query );
			}
			$args['meta_query']['relation'] = 'AND';
			$args['meta_query'][] = $meta_query;
		}
		return $args;
	}

	/**
	 * output_activation_code_expired_notice.
	 *
	 * @version 2.2.6
	 * @since   1.7.0
	 */
	function output_activation_code_expired_notice( $user_id, $args ) {
		if ( ! $this->validate_activation_code_time( true, $user_id ) && $args['directly'] ) {
			alg_wc_ev_add_notice( $this->get_activation_code_expired_message( $user_id ), 'error' );
		}
	}

	/**
	 * get_activation_code_expired_message.
	 *
	 * @version 1.8.0
	 * @since   1.7.0
	 */
	function get_activation_code_expired_message( $user_id ) {
		$notice = do_shortcode( get_option( 'alg_wc_ev_activation_code_expired_message',
			__( 'Link has expired. You can resend the email with verification link by clicking <a href="%resend_verification_url%">here</a>.', 'emails-verification-for-woocommerce' ) ) );
		return str_replace( '%resend_verification_url%', $this->core->messages->get_resend_verification_url( $user_id ), $notice );
	}

	/**
	 * validate_activation_code_time.
	 *
	 * @version 1.9.8
	 * @since   1.7.0
	 */
	function validate_activation_code_time( $is_valid, $user_id ) {
		if ( $is_valid && $user_id && 0 != ( $expiration_time = alg_wc_ev_get_expiration_time() ) ) {
			$activation_code_time = get_user_meta( $user_id, 'alg_wc_ev_activation_code_time', true );
			if ( ! $activation_code_time ) {
				$activation_code_time = 0;
			}
			if ( ( time() - $activation_code_time ) > $expiration_time ) {
				return false;
			}
		}
		return $is_valid;
	}

	/**
	 * output_blacklisted_email_notice.
	 *
	 * @version 2.2.6
	 * @since   1.6.0
	 */
	function output_blacklisted_email_notice( $user_id, $args ) {
		if ( ! $this->validate_blacklisted_emails( true, $user_id ) && $args['directly'] ) {
			alg_wc_ev_add_notice( $this->get_blacklisted_message(), 'error' );
		}
	}

	/**
	 * get_blacklisted_message.
	 *
	 * @version 1.6.0
	 * @since   1.6.0
	 */
	function get_blacklisted_message() {
		return do_shortcode( get_option( 'alg_wc_ev_blacklisted_message', __( 'Your email is denied.', 'emails-verification-for-woocommerce' ) ) );
	}

	/**
	 * wildcard_match.
	 *
	 * @version 1.6.0
	 * @since   1.6.0
	 */
	function wildcard_match( $pattern, $subject ) {
		$pattern = strtr( $pattern, array(
			'*' => '.*?', // 0 or more (lazy) - asterisk (*)
			'?' => '.',   // 1 character - question mark (?)
		) );
		return preg_match( "/$pattern/", $subject );
	}

	/**
	 * validate_blacklisted_emails.
	 *
	 * @version 2.3.5
	 * @since   1.6.0
	 * @todo    (maybe) check for this earlier, i.e. not on verification link click
	 */
	function validate_blacklisted_emails( $is_valid, $user_id ) {
		if ( $is_valid && $user_id && '' != ( $blacklist = get_option( 'alg_wc_ev_email_blacklist', '' ) ) ) {
			$user      = new WP_User( $user_id );
			$blacklist = str_replace( PHP_EOL, ',', $blacklist );
			$blacklist = array_map( 'trim', explode( ',', $blacklist ) );
			$blacklist = array_filter( $blacklist );
			foreach ( $blacklist as $email ) {
				if ( $email === $user->user_email || $this->wildcard_match( $email, $user->user_email ) ) {
					return false;
				}
			}
		}
		return $is_valid;
	}

	/**
	 * maybe_redirect_to_myaccount_filter.
	 *
	 * @version 1.5.0
	 * @since   1.5.0
	 */
	function maybe_redirect_to_myaccount_filter( $redirect_to, $user_id ) {
		return ( 'yes' === get_option( 'alg_wc_ev_prevent_login_after_checkout_block_thankyou', 'no' ) ? wc_get_page_permalink( 'myaccount' ) : $redirect_to );
	}

	/**
	 * maybe_redirect_to_myaccount_action.
	 *
	 * @version 1.5.0
	 * @since   1.5.0
	 */
	function maybe_redirect_to_myaccount_action( $user_id ) {
		if ( 'yes' === get_option( 'alg_wc_ev_prevent_login_after_checkout_block_thankyou', 'no' ) ) {
			wp_safe_redirect( add_query_arg( 'alg_wc_ev_activate_account_message', $user_id, wc_get_page_permalink( 'myaccount' ) ) );
			exit;
		}
	}

	/**
	 * block_customer_order_emails.
	 *
	 * @version 1.5.0
	 * @since   1.5.0
	 * @todo    (maybe) optional `guest`
	 * @todo    (maybe) delay (i.e. not block)
	 */
	function block_customer_order_emails( $is_enabled, $order, $email ) {
		return ( is_a( $order, 'WC_Order' ) && ( $user_id = $order->get_customer_id() ) && $this->core->is_user_verified_by_user_id( $user_id ) ? $is_enabled : false );
	}

	/**
	 * get_block_guest_add_to_cart_notice.
	 *
	 * @version 1.7.0
	 * @since   1.5.0
	 */
	function get_block_guest_add_to_cart_notice() {
		$notice = do_shortcode( get_option( 'alg_wc_ev_block_guest_add_to_cart_notice',
			__( 'You need to <a href="%myaccount_url%" target="_blank">register</a> and verify your email before adding products to the cart.', 'emails-verification-for-woocommerce' ) ) );
		$placeholders = array(
			'%myaccount_url%' => wc_get_page_permalink( 'myaccount' ),
		);
		return str_replace( array_keys( $placeholders ), $placeholders, $notice );
	}

	/**
	 * block_guest_add_to_cart_validation.
	 *
	 * @version 2.0.3
	 * @since   1.5.0
	 */
	function block_guest_add_to_cart_validation( $passed, $product_id, $quantity ) {
		if ( ! is_user_logged_in() ) {
			if ( ! wp_doing_ajax() ) {
				$custom_url   = get_option( 'alg_wc_ev_block_guest_add_to_cart_custom_redirect_url', '' );
				$redirect_url = add_query_arg( array( 'alg_wc_ev_guest' => true ), $custom_url );
				wp_redirect( $redirect_url );
				exit;
			} else {
				add_filter( 'woocommerce_cart_redirect_after_error', array( $this, 'block_guest_add_to_cart_ajax_redirect' ), PHP_INT_MAX, 2 );
			}
			return false;
		}
		return $passed;
	}

	/**
	 * block_guest_add_to_cart_ajax_redirect.
	 *
	 * @version 2.0.3
	 * @since   1.5.0
	 *
	 * @param $url
	 * @param $product_id
	 *
	 * @return string
	 */
	function block_guest_add_to_cart_ajax_redirect( $url, $product_id ) {
		$url = empty( $custom_url = get_option( 'alg_wc_ev_block_guest_add_to_cart_custom_redirect_url', '' ) ) ? $url : $custom_url;
		return add_query_arg( 'alg_wc_ev_guest', true, $url );
	}

	/**
	 * block_guest_add_to_cart_ajax_error.
	 *
	 * @version 1.5.0
	 * @since   1.5.0
	 */
	function block_guest_add_to_cart_ajax_error() {
		if ( isset( $_GET['alg_wc_ev_guest'] ) ) {
			alg_wc_ev_add_notice( $this->get_block_guest_add_to_cart_notice(), 'error' );
		}
	}

	/**
	 * maybe_wrap_in_wrap_in_wc_email_template.
	 *
	 * @version 2.8.4
	 * @since   1.5.0
	 */
	function maybe_wrap_in_wc_email_template( $email_content, $args ) {
		$args = wp_parse_args( $args, array(
			'user_id' => '',
			'heading' => __( 'Activate your account', 'emails-verification-for-woocommerce' ),
			'context' => 'activation_email_separate',
			'placeholders' => alg_wc_ev_get_common_placeholders()
		) );
		$user_id = $args['user_id'];
		$heading = $args['heading'];
		$context = $args['context'];
		if (
			in_array( $email_template = get_option( 'alg_wc_ev_email_template', 'plain' ), array( 'wc', 'smart' ) ) &&
			'activation_email_content_placeholder' !== $context
		) {
			$placeholders = array_merge( $args['placeholders'], alg_wc_ev_get_user_placeholders( array( 'user_id' => $user_id ) ) );
			$heading      = apply_filters( 'alg_wc_ev_email_content_heading', str_replace( array_keys( $placeholders ), $placeholders, $heading ), $args );
			if ( 'manual' === ( $wrap_method = get_option( 'alg_wc_ev_email_template_wc_wrap_method', 'manual' ) ) ) {
				$email_content = $this->wrap_in_wc_email_template( $email_content, $heading );
			} elseif ( 'native' === $wrap_method ) {
				$mailer        = WC()->mailer();
				$email_content = $mailer->wrap_message( $heading, $email_content );
			}
		}
		return $email_content;
	}

	/**
	 * wrap_in_wc_email_template.
	 *
	 * @version 1.9.0
	 * @since   1.0.0
	 */
	function wrap_in_wc_email_template( $content, $email_heading = '' ) {
		$header = $this->get_wc_email_part( 'header', $email_heading );
		$footer = $this->get_wc_email_part( 'footer' );
		if ( class_exists( 'WC_Emails' ) && method_exists( 'WC_Emails', 'instance' ) && method_exists( 'WC_Emails', 'replace_placeholders' ) ) {
			$emails = WC_Emails::instance();
			$footer = $emails->replace_placeholders( $footer );
		} else {
			$footer = $this->replace_placeholders( $footer );
		}
		return $header . $content . $footer;
	}

	/**
	 * Replace placeholder text in strings.
	 *
	 * @version 1.9.0
	 * @since   1.9.0
	 * @param   string $string Email footer text.
	 * @return  string         Email footer text with any replacements done.
	 * @see     /woocommerce/includes/class-wc-emails.php
	 */
	function replace_placeholders( $string ) {
		$domain = wp_parse_url( home_url(), PHP_URL_HOST );

		return str_replace(
			array(
				'{site_title}',
				'{site_address}',
				'{site_url}',
				'{woocommerce}',
				'{WooCommerce}',
			),
			array(
				wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
				$domain,
				$domain,
				'<a href="https://woocommerce.com">WooCommerce</a>',
				'<a href="https://woocommerce.com">WooCommerce</a>',
			),
			$string
		);
	}

	/**
	 * get_wc_email_part.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function get_wc_email_part( $part, $email_heading = '' ) {
		ob_start();
		switch ( $part ) {
			case 'header':
				wc_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
				break;
			case 'footer':
				wc_get_template( 'emails/email-footer.php' );
				break;
		}
		return ob_get_clean();
	}

	/**
	 * maybe_add_wc_email_style.
	 *
	 * @version 2.6.0
	 * @since   1.9.2
	 * @todo    [test] if it's required in case of `mail` function
	 * @todo    (maybe) add style only if are sure that it's not plain text message
	 */
	function maybe_add_wc_email_style( $message, $func ) {
		if ( in_array( $func, array( 'mail', 'wp_mail' ) ) ) {
			if ( ! class_exists( '\WC_Email' ) ) {
				WC()->mailer();
			}
			$email   = new \WC_Email();
			$message = $email->style_inline( $message );
		}
		return $message;
	}

	/**
	 * maybe_send_admin_email.
	 *
	 * @version 2.6.7
	 * @since   1.5.0
	 * @todo    [next] *optional* WC template
	 *
	 * @param $user_id
	 * @param $args
	 */
	function maybe_send_admin_email( $user_id, $args ) {
		if (
			isset( $args['context'] ) &&
			! empty( $args['context'] ) &&
			'admin_verification' === $args['context'] &&
			'no' === get_option( 'alg_wc_ev_send_admin_email_to_manually_verified_users', 'no' )
		) {
			return;
		}
		if (
			'yes' === get_option( 'alg_wc_ev_admin_email', 'no' ) &&
			'' == get_user_meta( $user_id, 'alg_wc_ev_admin_email_sent', true )
		) {
			$recipient = get_option( 'alg_wc_ev_admin_email_recipient', '' );
			if ( '' === $recipient ) {
				$recipient = get_bloginfo( 'admin_email' );
			}
			$content = $this->core->emails->get_email_content( array(
				'user_id' => $user_id,
				'context' => 'admin_email',
				'heading' => do_shortcode( get_option( 'alg_wc_ev_admin_email_heading', __( 'User account has been activated', 'emails-verification-for-woocommerce' ) ) ),
				'content' => do_shortcode( get_option( 'alg_wc_ev_admin_email_content', alg_wc_ev()->core->emails->get_default_email_content('admin') ) )
			) );
			$subject = $this->core->emails->get_email_subject( array(
				'user_id' => $user_id,
				'context' => 'admin_email',
				'subject' => do_shortcode( get_option( 'alg_wc_ev_admin_email_subject', '[%site_title%]: ' . __( 'User email has been verified', 'emails-verification-for-woocommerce' ) ) )
			) );
			$this->core->emails->send_mail( $recipient, $subject, $content );
			update_user_meta( $user_id, 'alg_wc_ev_admin_email_sent', time() );
		}
	}

	/**
	 * email_subject.
	 *
	 * @version 2.6.6
	 * @since   1.1.0
	 */
	function set_activation_email_subject( $subject, $args ) {
		if ( false !== strpos( $args['context'], 'activation_email' ) ) {
			$subject = do_shortcode( get_option( 'alg_wc_ev_email_subject', '[%site_title%]: ' . __( 'Please activate your account', 'emails-verification-for-woocommerce' ) ) );
		}
		return $subject;
	}

	/**
	 * email_content.
	 *
	 * @version 2.6.7
	 * @since   1.1.0
	 */
	function set_activation_email_content( $content, $args ) {
		if ( false !== strpos( $args['context'], 'activation_email' ) ) {
			$content = do_shortcode( get_option( 'alg_wc_ev_email_content', alg_wc_ev()->core->emails->get_default_email_content('activation') ) );
		}
		return $content;
	}

	/**
	 * set_activation_email_content_heading.
	 *
	 * @version 2.3.1
	 * @since   2.3.1
	 *
	 * @param $heading
	 * @param $args
	 *
	 * @return string
	 */
	function set_activation_email_content_heading( $heading, $args ) {
		if ( false !== strpos( $args['context'], 'activation_email' ) ) {
			$heading = do_shortcode( get_option( 'alg_wc_ev_email_template_wc_heading', __( 'Activate your account', 'emails-verification-for-woocommerce' ) ) );
		}
		return $heading;
	}

	/**
	 * settings.
	 *
	 * @version 1.7.0
	 * @since   1.1.0
	 */
	function settings( $value, $type = '', $args = array() ) {
		if ( 'min' === $type ) {
			return array( 'min' => $args[0] );
		}
		return '';
	}
}

endif;

return new Alg_WC_Email_Verification_Pro();
