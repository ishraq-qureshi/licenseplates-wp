<?php
/**
 * Class ALG_WC_EV_Confirmation_WC_Email_Pro file.
 *
 * @version 2.6.7
 * @since   2.3.6
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ALG_WC_EV_Confirmation_WC_Email_Pro', false ) ) :
	/**
	 * Customer account confirmation email.
	 *
	 * An email sent to the user when the account is verified.
	 *
	 * @class       ALG_WC_EV_Confirmation_WC_Email_Pro
	 * @extends     WC_Email
	 */
	class ALG_WC_EV_Confirmation_WC_Email_Pro extends WC_Email {
		/**
		 * Constructor of ALG_WC_EV_Confirmation_WC_Email_Pro class.
		 *
		 * @version 2.3.6
		 * @since   2.3.6
		 */
		public function __construct() {
			$this->id             = 'alg_wc_ev_email_confirmation';
			$this->customer_email = true;
			$this->title          = __( 'Confirmation email', 'emails-verification-for-woocommerce' );
			$this->description    = __( 'An email sent to the user when the account is verified.', 'emails-verification-for-woocommerce' );
			$this->template_html  = 'emails/customer-account-confirmation.php';
			$this->template_plain = 'emails/plain/customer-account-confirmation.php';
			$this->placeholders   = array(
				'{user_id}'                => '',
				'{user_login}'             => '',
				'{user_nicename}'          => '',
				'{user_email}'             => '',
				'{user_url}'               => '',
				'{user_registered}'        => '',
				'{user_display_name}'      => '',
				'{user_roles}'             => '',
				'{user_first_name}'        => '',
				'{user_last_name}'         => '',
				'{admin_user_profile_url}' => '',
			);

			// Triggers for this email.
			add_action( 'alg_wc_ev_trigger_confirmation_wc_email', array( $this, 'trigger' ), 10, 1 );
			// Template changing.
			add_filter( 'woocommerce_locate_core_template', array( $this, 'change_email_locate_template' ), 10, 2 );
			add_filter( 'woocommerce_locate_template', array( $this, 'change_email_locate_template' ), 10, 2 );
			add_filter( 'woocommerce_template_directory', array( $this, 'change_woocommerce_template_directory' ), 10, 2 );
			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * change_woocommerce_template_directory.
		 *
		 * @version 2.3.6
		 * @since   2.3.6
		 *
		 * @param $dir
		 * @param $template
		 *
		 * @return string
		 */
		function change_woocommerce_template_directory( $dir, $template ) {
			if ( false !== strpos( $template, 'customer-account-confirmation.php' ) ) {
				$dir = alg_wc_ev()->plugin_dir_name();
			}
			return $dir;
		}

		/**
		 * change_email_locate_template.
		 *
		 * @version 2.3.6
		 * @since   2.3.6
		 *
		 * @param $file
		 * @param $template
		 *
		 * @return string
		 */
		function change_email_locate_template( $file, $template ) {
			if ( false !== strpos( $template, 'customer-account-confirmation.php' ) ) {
				$file = ( file_exists( $local_file = $this->get_theme_template_file( $template ) ) ) ? $local_file : alg_wc_ev()->plugin_path() . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'pro' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template;
			}
			return $file;
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param $user_id
		 *
		 * @version 2.6.3
		 * @since   2.3.6
		 */
		public function trigger( $user_id ) {
			$this->setup_locale();

			$user                                           = get_user_by( 'ID', $user_id );
			$this->object                                   = $user;
			$this->recipient                                = $user->user_email;
			$this->placeholders['{user_id}']                = $user_id;
			$this->placeholders['{user_login}']             = $user->user_login;
			$this->placeholders['{user_nicename}']          = $user->user_nicename;
			$this->placeholders['{user_email}']             = $user->user_email;
			$this->placeholders['{user_url}']               = $user->user_url;
			$this->placeholders['{user_registered}']        = $user->user_registered;
			$this->placeholders['{user_display_name}']      = $user->display_name;
			$this->placeholders['{user_roles}']             = implode( ', ', $user->roles );
			$this->placeholders['{user_first_name}']        = $user->first_name;
			$this->placeholders['{user_last_name}']         = $user->last_name;
			$this->placeholders['{admin_user_profile_url}'] = $user->user_url;
			$this->placeholders['{main_content}']           = $this->get_email_main_content( array(
				'user_id' => $user_id
			) );

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$sent_status = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
				if ( $sent_status ) {
					update_user_meta( $user_id, 'alg_wc_ev_confirmation_wc_email_sent', time() );
				}
			}

			$this->restore_locale();
		}

		/**
		 * get_email_main_content.
		 *
		 * @version 2.6.7
		 * @since   2.6.3
		 *
		 * @param $args
		 *
		 * @return string
		 */
		function get_email_main_content( $args = null ) {
			$args         = wp_parse_args( $args, array(
				'user_id' => ''
			) );
			$user_id      = $args['user_id'];
			$content      = apply_filters( 'alg_wc_ev_email_content', '', array( 'context' => 'confirmation_email' ) );
			$placeholders = array_merge( alg_wc_ev_get_common_placeholders(), alg_wc_ev_get_user_placeholders( array( 'user_id' => $user_id ) ) );

			return str_replace( array_keys( $placeholders ), $placeholders, $content );
		}

		/**
		 * Get email subject.
		 *
		 * @version 2.6.6
		 * @since   2.3.6
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return '[{site_title}]: ' . __( 'Your account has been activated successfully', 'emails-verification-for-woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @version 2.3.6
		 * @since   2.3.6
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Your account has been activated', 'emails-verification-for-woocommerce' );
		}

		/**
		 * Get content html.
		 *
		 * @version 2.6.7
		 * @since   2.3.6
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'user'               => $this->object,
					'user_display_name'  => $this->object->display_name,
					'site_title'         => $this->placeholders['{site_title}'],
					'site_url'           => $this->placeholders['{site_url}'],
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'main_content'       => $this->format_string( $this->placeholders['{main_content}'] ),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
				)
			);
		}

		/**
		 * Get content plain.
		 *
		 * @version 2.6.7
		 * @since   2.3.6
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'user'               => $this->object,
					'user_display_name'  => $this->object->display_name,
					'site_title'         => $this->placeholders['{site_title}'],
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'main_content'       => $this->format_string( $this->placeholders['{main_content}'] ),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @version 2.3.6
		 * @since   2.3.6
		 *
		 * @return string
		 */
		public function get_default_additional_content() {
			return '';
		}
	}

endif;

return new ALG_WC_EV_Confirmation_WC_Email_Pro();
