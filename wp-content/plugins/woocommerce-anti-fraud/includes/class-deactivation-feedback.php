<?php
/**
 * Plugin Deactivation Feedback Class
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'OPMC_Deactivation_Feedback' ) ) {
	class OPMC_Deactivation_Feedback {
		protected static $_instance = null;

		private $text_domain = '';
		private $plugin_file = '';
		private $secret_key = '';
		private $feedback_server = 'https://affeedback.nicer8.com/';

		/**
		 * Constructor
		 */
		public function __construct( $text_domain = 'woocommerce-anti-fraud', $secret_key = '', $plugin_file = '' ) {

			$this->text_domain = $text_domain;
			$this->secret_key  = $secret_key;
			$this->plugin_file = empty( $plugin_file ) ? sprintf( '%1$s/%1$s.php', $this->text_domain ) : $plugin_file;

			add_action( 'admin_footer', array( $this, 'load_deactivation_assets' ) );
			add_action( 'wp_ajax_opmc_submit_deactivation_feedback', array( $this, 'submit_deactivation_feedback' ) );
		}


		/**
		 * Load deactivation resources in required place
		 *
		 * @return void
		 */
		public function load_deactivation_assets() {

			$this->feedback_modal();
			$this->load_required_css();
			$this->load_required_js();
		}


		/**
		 * Load JS for the modal
		 *
		 * @return void
		 */
		public function load_required_js() {
			wp_enqueue_script( 'jquery' );

			?>
			<script>
				jQuery(document).ready(function ($) {
					let deactivateLink = $('tr[data-plugin="<?php echo esc_attr( $this->plugin_file ); ?>"] .deactivate a'),
						modalContainer = $('#opmc-feedback-modal'),
						form = $('#opmc-deactivation-feedback-form');

					deactivateLink.on('click', function (e) {
						e.preventDefault();
						modalContainer.show();
					});

					$('input[name="reason"]').on('change', function () {
						if ($(this).val() === 'other') {
							$('.opmc-feedback-other').show();
						} else {
							$('.opmc-feedback-other').hide();
						}
					});

					form.on('submit', function (e) {
						e.preventDefault();
						let reason = $('input[name="reason"]:checked').val(),
							otherInfo = $('.opmc-feedback-other').val();

						$.ajax({
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' )); ?>',
							type: 'POST',
							data: {
								action: 'opmc_submit_deactivation_feedback',
								nonce: '<?php echo esc_html( wp_create_nonce( 'opmc_feedback_nonce' ) ); ?>',
								reason: reason,
								other_info: otherInfo
							},
							success: function (response) {
								window.location.href = deactivateLink.attr('href');
							},
							error: function (xhr, status, error) {
								window.location.href = deactivateLink.attr('href');
							}
						});
					});

					$('.opmc-feedback-skip').on('click', function () {
						window.location.href = deactivateLink.attr('href');
					});

					$('.opmc-feedback-modal-close').on('click', function () {
						modalContainer.hide();
					});
				});
			</script>
			<?php
		}


		/**
		 * Load CSS for the feedback modal
		 *
		 * @return void
		 */
		public function load_required_css() {
			?>
			<style>
				#opmc-feedback-modal {
					position: fixed;
					z-index: 99999;
					left: 0;
					top: 0;
					width: 100%;
					height: 100%;
					background-color: rgba(0, 0, 0, 0.5);
					display: none;
				}

				#opmc-feedback-modal .opmc-feedback-modal-content {
					background-color: #f1f1f1;
					margin: 8% auto;
					padding: 20px;
					border: 1px solid #888;
					width: 50%;
					max-width: 540px;
					position: relative;
					border-radius: 10px;
				}

				#opmc-feedback-modal #opmc-deactivation-feedback-form p {
					margin: 0 0 10px 0;
				}

				#opmc-feedback-modal #opmc-deactivation-feedback-form label {
					display: inline-block;
					cursor: pointer;
				}

				#opmc-feedback-modal .opmc-feedback-other,
				#opmc-feedback-modal .opmc-feedback-other:active,
				#opmc-feedback-modal .opmc-feedback-other:focus {
					width: 100%;
					height: 100px;
					margin-top: 10px;
					padding: 10px;
					outline: none;
					box-shadow: none;
				}

				#opmc-feedback-modal .opmc-feedback-buttons {
					margin-top: 20px;
					display: flex;
					justify-content: space-between;
					align-items: center;
				}

				#opmc-feedback-modal .opmc-feedback-buttons .opmc-feedback-skip {
					text-decoration: underline;
					cursor: pointer;
				}

				#opmc-feedback-modal .opmc-feedback-modal-close {
					position: absolute;
					top: 10px;
					right: 10px;
					font-size: 28px;
					font-weight: bold;
					cursor: pointer;
				}
			</style>
			<?php
		}


		/**
		 * Displaying feedback modal form
		 */
		public function feedback_modal() {

			echo '<div id="opmc-feedback-modal" class="opmc-feedback-modal">';
			echo '<div class="opmc-feedback-modal-content">';
			echo '<span class="opmc-feedback-modal-close">&times;</span>';

			printf( '<h2>%s</h2>', esc_html__( "We're sorry to see you go", 'woocommerce-anti-fraud' ) );
			printf( '<p>%s</p>', esc_html__( "Please let us know why you're deactivating the plugin:", 'woocommerce-anti-fraud' ) );

			echo '<form id="opmc-deactivation-feedback-form">';

			foreach ( $this->get_feedback_options() as $value => $label ) {
				printf( '<p><label><input type="radio" required name="reason" value="%s"> %s</label></p>', esc_attr( $value ), esc_html( $label ) );
			}

			echo '<textarea class="opmc-feedback-other" rows="3" style="display:none;" placeholder="' . esc_attr__( 'Please provide more information', 'woocommerce-anti-fraud' ) . '"></textarea>';
			echo '<div class="opmc-feedback-buttons">';
			echo '<span class="opmc-feedback-skip">' . esc_html__( 'Skip & Deactivate', 'woocommerce-anti-fraud' ) . '</span>';
			echo '<button type="submit" class="button button-primary">' . esc_html__( 'Submit & Deactivate', 'woocommerce-anti-fraud' ) . '</button>';
			echo '</div>';

			echo '</form>';

			echo '</div>';
			echo '</div>';
		}


		/**
		 * Return feedback options
		 *
		 * @return mixed|null
		 */
		private function get_feedback_options() {
			$feedback_options = array(
				'temporary'          => esc_html__( "I'm only deactivating temporarily", 'woocommerce-anti-fraud' ),
				'not_working'        => esc_html__( 'The plugin is not working', 'woocommerce-anti-fraud' ),
				'found_better'       => esc_html__( 'I found a better plugin', 'woocommerce-anti-fraud' ),
				'no_longer_needed'   => esc_html__( 'I no longer need the plugin', 'woocommerce-anti-fraud' ),
				'lack_of_features'   => esc_html__( 'The plugin lacks features I need', 'woocommerce-anti-fraud' ),
				'confusing'          => esc_html__( 'I found the plugin confusing or difficult to use', 'woocommerce-anti-fraud' ),
				'performance_issues' => esc_html__( 'The plugin caused performance issues on my site', 'woocommerce-anti-fraud' ),
				'conflict'           => esc_html__( 'The plugin conflicts with another plugin or theme', 'woocommerce-anti-fraud' ),
				'other'              => esc_html__( 'Other', 'woocommerce-anti-fraud' )
			);

			/**
			 * Filters the deactivation feedback options before they are returned.
			 *
			 * @since 1.0.0
			 *
			 * @param array $feedback_options Array of feedback options shown in the deactivation modal.
			 * @return array Modified feedback options.
			 */
			return apply_filters( 'opmc_deactivation_feedback_options', $feedback_options );
		}


		/**
		 * Send feedback to server
		 *
		 * @return void
		 */
		public function submit_deactivation_feedback() {

			check_ajax_referer( 'opmc_feedback_nonce', 'nonce' );

			$reason     = isset( $_POST['reason'] ) ? sanitize_text_field( $_POST['reason'] ) : '';
			$other_info = isset( $_POST['other_info'] ) ? sanitize_textarea_field( $_POST['other_info'] ) : '';

			if ( empty( $reason ) ) {
				wp_send_json_error( esc_html__( 'Reason is required', 'woocommerce-anti-fraud' ) );
			}

			$feedback_options = $this->get_feedback_options();
			$reason_label     = isset( $feedback_options[ $reason ] ) ? $feedback_options[ $reason ] : '';

			$api_url  = $this->feedback_server . 'wp-json/opmc/v1/feedback';
			$response = wp_remote_post( $api_url, array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->secret_key,
				),
				'body'    => array(
					'reason'       => $reason,
					'reason_label' => 'other' === $reason ? $other_info : $reason_label,
					'plugin_slug'  => 'woocommerce-anti-fraud',
					'site_url'     => site_url(),
				),
			) );

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( 'Failed to submit feedback' );
			} else {
				wp_send_json_success( 'Feedback submitted successfully' );
			}
		}


		/**
		 * Returns a singleton instance of the OPMC_Deactivation_Feedback class.
		 *
		 * @param string $text_domain The text domain for translations. Default 'woocommerce-anti-fraud'.
		 * @param string $secret_key  Optional secret key.
		 * @return OPMC_Deactivation_Feedback
		 */
		public static function instance( $text_domain = 'woocommerce-anti-fraud', $secret_key = '' ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self( $text_domain, $secret_key );
			}

			return self::$_instance;
		}
	}
}
