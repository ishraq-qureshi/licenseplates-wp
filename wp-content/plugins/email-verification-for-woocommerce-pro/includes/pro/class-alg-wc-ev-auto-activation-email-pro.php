<?php
/**
 * Email Verification for WooCommerce - Auto activation email sending class.
 *
 * @version 2.8.6
 * @since   2.8.6
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Email_Verification_Auto_Activation_Email_Pro' ) ) {

	class Alg_WC_Email_Verification_Auto_Activation_Email_Pro {

		/**
		 * Initializer.
		 *
		 * @version 2.8.6
		 * @since   2.8.6
		 *
		 * @return void
		 */
		function init() {
			add_action( 'alg_wc_ev_reset_and_mail_activation_link', array( $this, 'schedule_new_activation_email_sending' ) );
			add_action( 'alg_wc_ev_reset_and_mail_activation_link_event', array( $this, 'alg_wc_ev_reset_and_mail_activation_on_schedule' ) );
			add_action( 'alg_wc_ev_user_account_activated', array( $this, 'reset_auto_activation_email_count' ) );
		}

		/**
		 * reset_auto_activation_email_count.
		 *
		 * @version 2.8.6
		 * @since   2.8.6
		 *
		 * @param $user_id
		 *
		 * @return void
		 */
		function reset_auto_activation_email_count( $user_id ) {
			delete_user_meta( $user_id, 'alg_wc_ev_activation_email_automatic_sending_count' );
		}

		/**
		 * alg_wc_ev_reset_and_mail_activation_on_schedule.
		 *
		 * @version 2.8.6
		 * @since   2.8.6
		 *
		 * @param $user_id
		 *
		 * @return void
		 */
		function alg_wc_ev_reset_and_mail_activation_on_schedule( $user_id ) {
			if ( ! alg_wc_ev_is_user_verified_by_user_id( $user_id ) ) {
				$auto_sending_count = get_user_meta( $user_id, 'alg_wc_ev_activation_email_automatic_sending_count', true );
				$auto_sending_count = empty( $auto_sending_count ) ? 0 : (int) $auto_sending_count;
				$auto_sending_count ++;
				update_user_meta( $user_id, 'alg_wc_ev_activation_email_automatic_sending_count', $auto_sending_count );
				alg_wc_ev()->core->emails->reset_and_mail_activation_link( array(
					'user_id' => $user_id,
					'context' => 'auto_resend'
				) );
			}
		}

		/**
		 * need_to_schedule_activation_email_sending.
		 *
		 * @version 2.8.6
		 * @since   2.8.6
		 *
		 * @param $user_id
		 *
		 * @return bool
		 */
		function need_to_schedule_activation_email_sending( $user_id ) {
			if (
				'yes' === get_option( 'alg_wc_ev_activation_email_automatic_sending', 'no' ) &&
				! alg_wc_ev_is_user_verified_by_user_id( $user_id ) &&
				(int) get_user_meta( $user_id, 'alg_wc_ev_activation_email_automatic_sending_count', true ) < (int) get_option( 'alg_wc_ev_activation_email_automatic_sending_count_max', '3' )
			) {
				return true;
			}

			return false;
		}

		/**
		 * schedule_new_activation_email_sending.
		 *
		 * @version 2.8.6
		 * @since   2.8.6
		 *
		 * @param $args
		 *
		 * @return void
		 */
		function schedule_new_activation_email_sending( $args ) {
			$user_id = intval( $args['user_id'] );
			$context = $args['context'];
			if (
				in_array( $context, array(
					'admin_resend',
					'own_user_resend'
				) )
			) {
				$this->reset_auto_activation_email_count( $user_id );
			}
			if ( $this->need_to_schedule_activation_email_sending( $user_id ) ) {
				$unit                    = get_option( 'alg_wc_ev_activation_email_automatic_sending_frequency_unit', 'day' );
				$frequency               = (int) get_option( 'alg_wc_ev_activation_email_automatic_sending_frequency', '1' );
				$human_readable_interval = 'day' === $unit ? DAY_IN_SECONDS : HOUR_IN_SECONDS;
				wp_schedule_single_event( time() + ( $human_readable_interval * $frequency ), 'alg_wc_ev_reset_and_mail_activation_link_event', array( $user_id ) );
			}
		}
	}
}