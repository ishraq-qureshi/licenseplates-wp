<?php
/**
 * WPFactory Key Manager - Admin Site Key Manager
 *
 * @version 1.0.6
 * @since   1.0.0
 *
 * @author  Algoritmika Ltd.
 */

use WPFactory\WPFactory_Admin_Menu\WPFactory_Admin_Menu;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPFKM_Site_Key_Manager' ) ) :

	class WPFKM_Site_Key_Manager {

		/**
		 * Constructor.
		 *
		 * @version 1.0.1
		 * @since   1.0.0
		 */
		function __construct() {

			// WPFactory admin menu.
			WPFactory_Admin_Menu::get_instance();

			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'set_item_site_key' ) );
			add_action( 'admin_init', array( $this, 'update_item_list' ) );
			add_action( 'admin_init', array( $this, 'check_item_site_key' ) );
			add_action( 'admin_notices', array( $this, 'admin_notice_site_key_status' ) );

		}

		/**
		 * check_item_site_key.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		function check_item_site_key() {
			if ( isset( $_GET['alg_check_item_site_key'] ) ) {
				alg_wpcfh_check_site_key( $_GET['alg_check_item_site_key'] );
				wp_safe_redirect( remove_query_arg( 'alg_check_item_site_key' ) );
				exit;
			}
		}

		/**
		 * update_item_list.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @todo    (dev) add "Item list successfully updated" message
		 */
		function update_item_list() {
			if ( isset( $_GET['alg_update_item_list'] ) ) {
				do_action( 'alg_get_plugins_list' );
				do_action( 'alg_get_themes_list' );
				wp_safe_redirect( remove_query_arg( 'alg_update_item_list' ) );
				exit;
			}
		}

		/**
		 * set_item_site_key.
		 *
		 * @version 1.0.6
		 * @since   1.0.0
		 *
		 * @todo    (dev) All items: `wpfactory_key_manager()->plugins_updater->update_checkers[ $item_slug ]->checkForUpdates();`?
		 */
		function set_item_site_key() {

			if ( isset( $_POST['alg_set_site_key'] ) ) {

				// Single item
				$item_slug          = sanitize_text_field( $_POST['alg_item_slug'] );
				$site_key           = sanitize_text_field( $_POST['alg_site_key'] );
				$keys               = get_option( 'alg_site_keys', array() );
				$keys[ $item_slug ] = $site_key;
				update_option( 'alg_site_keys', $keys );
				alg_wpcfh_check_site_key( $item_slug );
				wpfactory_key_manager()->plugins_updater->update_checkers[ $item_slug ]->checkForUpdates();

			} elseif ( isset( $_POST['alg_set_site_key_all'] ) ) {

				// All items
				$item_slugs = array_merge(
					wpfactory_key_manager()->plugins_updater->plugins_to_update,
					wpfactory_key_manager()->plugins_updater->themes_to_update,
				);
				$site_key   = sanitize_text_field( $_POST['alg_site_key'] );
				$keys       = get_option( 'alg_site_keys', array() );
				foreach ( $item_slugs as $item_slug ) {
					$keys[ $item_slug ] = $site_key;
				}
				update_option( 'alg_site_keys', $keys );
				foreach ( $item_slugs as $item_slug ) {
					alg_wpcfh_check_site_key( $item_slug );
				}

			}

		}

		/**
		 * admin_notice_site_key_status.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		function admin_notice_site_key_status() {
			if (
				isset( $_GET['page'] ) &&
				'wpfactory-key-manager' === $_GET['page'] &&
				isset( $_GET['item_slug'] )
			) {
				$item_slug       = sanitize_text_field( $_GET['item_slug'] );
				$site_key_status = alg_wpcfh_get_site_key_status( $item_slug );
				if ( false !== $site_key_status ) {
					$class   = ( alg_wpcfh_is_site_key_valid( $item_slug ) ? 'notice notice-success is-dismissible' : 'notice notice-error' );
					$message = alg_wpcfh_get_site_key_status_message( $item_slug );
					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
				}
			}
		}

		/**
		 * add_admin_menu.
		 *
		 * @version 1.0.5
		 * @since   1.0.0
		 */
		function add_admin_menu() {
			\add_submenu_page(
				WPFactory_Admin_Menu::get_instance()->get_menu_slug(),
				__( 'WPFactory Key Manager', 'wpcodefactory-key-manager' ),
				__( 'Key Manager', 'wpcodefactory-key-manager' ),
				class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options',
				'wpfactory-key-manager',
				array( $this, 'output_admin_menu' ),
				1
			);
		}

		/**
		 * get_table_html.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		function get_table_html( $data, $args = array() ) {
			$args        = array_merge( array(
				'table_class'        => '',
				'table_style'        => '',
				'row_styles'         => '',
				'table_heading_type' => 'horizontal',
				'columns_classes'    => array(),
				'columns_styles'     => array(),
			), $args );
			$table_class = ( '' == $args['table_class'] ) ? '' : ' class="' . $args['table_class'] . '"';
			$table_style = ( '' == $args['table_style'] ) ? '' : ' style="' . $args['table_style'] . '"';
			$row_styles  = ( '' == $args['row_styles'] ) ? '' : ' style="' . $args['row_styles'] . '"';

			$html = '';
			$html .= '<table style="margin-left:-1px"' . $table_class . $table_style . '>';
			end( $data );
			$lasy_key = key( $data );
			foreach ( $data as $row_nr => $row ) {
				$html .= 0 === $row_nr ? '<thead>' : ( 1 === $row_nr ? '<tbody>' : '' );
				$html .= '<tr' . $row_styles . '>';
				foreach ( $row as $column_nr => $value ) {
					$th_or_td     = ( ( 0 === $row_nr && 'horizontal' === $args['table_heading_type'] ) || ( 0 === $column_nr && 'vertical' === $args['table_heading_type'] ) ) ? 'th' : 'td';
					$column_class = ( ! empty( $args['columns_classes'][ $column_nr ] ) ) ? ' class="' . $args['columns_classes'][ $column_nr ] . '"' : '';
					$column_style = ( ! empty( $args['columns_styles'][ $column_nr ] ) ) ? ' style="' . $args['columns_styles'][ $column_nr ] . '"' : '';
					$html         .= '<' . $th_or_td . $column_class . $column_style . '>' . $value . '</' . $th_or_td . '>';
				}
				$html .= '</tr>';
				$html .= 0 === $row_nr ? '</thead>' : ( $lasy_key === $row_nr ? '</tbody>' : '' );
			}
			$html .= '</table>';

			return $html;
		}

		/**
		 * get_site_item_key_column.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		function get_site_item_key_column( $item_site_key, $item_slug ) {
			return ( '' === $item_site_key ? '' :
				'<span title="' . strip_tags( alg_wpcfh_get_site_key_status_message( $item_slug ) ) . '" style="color:' .
				( alg_wpcfh_is_site_key_valid( $item_slug ) ? 'green' : 'red' ) . ';">' . $item_site_key . '</span>'
			);
		}

		/**
		 * get_site_url_html.
		 *
		 * @version 1.0.4
		 * @since   1.0.0
		 *
		 * @return false|string
		 */
		function get_site_url_html() {
			ob_start();
			?>
			<table class="form-table" role="presentation">
				<tbody>
				<tr>
					<th scope="row">
						<label><?php _e( 'Site URL', 'wpfactory-key-manager' ); ?></label>
					</th>
					<td>
						<code> <?php echo esc_url( wpfactory_key_manager()->site_url ) ?> </code>
					</td>
				</tr>
				</tbody>
			</table>
			<?php
			$html = ob_get_contents();
			ob_end_clean();

			return $html;
		}

		/**
		 * get_key_setting_input_html.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $all_plugins
		 * @param $all_themes
		 *
		 * @return false|string
		 */
		function get_key_setting_input_html( $all_plugins, $all_themes ) {

			ob_start();
			if ( isset( $_GET['item_slug'] ) ) {
				$item_slug       = sanitize_text_field( $_GET['item_slug'] );
				$key             = alg_wpcfh_get_site_key( $item_slug );
				$item_type_label = 'plugin' === $_GET['item_type'] ? __( 'Plugin', 'wpfactory-key-manager' ) : __( 'Theme', 'wpfactory-key-manager' );
				if ( isset( $_GET['item_type'] ) && 'theme' == $_GET['item_type'] ) {
					$item_label = ( '' != $all_themes[ $item_slug ]->get( 'Name' ) ? $all_themes[ $item_slug ]->get( 'Name' ) : esc_html( $item_slug ) );
				} else {
					$plugin_file = $item_slug . '/' . $item_slug . '.php';
					$item_label  = ( isset( $all_plugins[ $plugin_file ]['Name'] ) ? $all_plugins[ $plugin_file ]['Name'] : esc_html( $item_slug ) );
				}
				?>
				<table class="form-table" role="presentation">
					<tbody>
					<tr>
						<th scope="row">
							<label for="alg_site_key">
								<?php echo sprintf( esc_html__( '%s key', 'wpfactory-key-manager' ), $item_type_label ); ?>
							</label>
						</th>
						<td>
							<form method="post">
								<input style="min-width:300px;" type="text" name="alg_site_key" id="alg_site_key"
									   value="<?php echo esc_attr( $key ); ?>">
								<input type="hidden" name="alg_item_slug" value="<?php echo esc_attr( $item_slug ); ?>">
								<input class="button-primary" type="submit" name="alg_set_site_key"
									   value="<?php echo esc_attr__( 'Set key', 'wpfactory-key-manager' ); ?>">
								<input class="button-primary" type="submit" name="alg_set_site_key_all"
									   value="<?php echo esc_attr__( 'Set this key for all items', 'wpfactory-key-manager' ); ?>"
									   title="<?php echo esc_attr__( 'Useful for the &quot;All Plugins Access&quot; users.', 'wpfactory-key-manager' ); ?>"
									   onclick="return confirm('<?php echo esc_html__( 'Are you sure?', 'wpfactory-key-manager' ); ?>');">
								<p class="description">
									<?php echo sprintf(
										__( 'Key for %s %s' ),
										'<strong>' . esc_html( $item_label ) . '</strong>',
										esc_html( strtolower( $item_type_label ) )
									); ?>
								</p>
							</form>
						</td>
					</tr>
					</tbody>
				</table>
				<?php
			}
			$html = ob_get_contents();
			ob_end_clean();

			return $html;
		}

		/**
		 * output_admin_menu.
		 *
		 * @version 1.0.6
		 * @since   1.0.0
		 *
		 * @todo    (dev) restyle
		 */
		function output_admin_menu() {

			$all_plugins = get_plugins();
			$all_themes  = wp_get_themes();

			$html = '';
			$html .= '<div class="wrap">';
			$html .= '<h2>' . esc_html__( 'WPFactory Key Manager', 'wpfactory-key-manager' ) . '</h2>';
			$html .= $this->get_site_url_html();
			$html .= apply_filters( 'wpfactory_helper_plugins_table_html_before', '' );
			$html .= $this->get_key_setting_input_html( $all_plugins, $all_themes );
			$html .= '<h2 style="margin-bottom:23px;">' . esc_html__( 'WPFactory items', 'wpfactory-key-manager' ) . '</h2>';

			$table_data = array();

			foreach ( wpfactory_key_manager()->plugins_updater->plugins_to_update as $plugin_slug ) {
				$plugin_file   = $plugin_slug . '/' . $plugin_slug . '.php';
				$item_site_key = alg_wpcfh_get_site_key( $plugin_slug );
				$table_data[]  = array(
					esc_html__( 'Plugin', 'wpfactory-key-manager' ),
					( isset( $all_plugins[ $plugin_file ]['Name'] ) ?
						$all_plugins[ $plugin_file ]['Name'] :
						$plugin_slug
					),
					$this->get_site_item_key_column( $item_site_key, $plugin_slug ),
					sprintf(
						'<a class="button button-primary" href="%s">%s</a>',
						esc_url( add_query_arg( array( 'item_slug' => $plugin_slug, 'item_type' => 'plugin' ) ) ),
						esc_html__( 'Set key', 'wpfactory-key-manager' )
					) .
					( '' != $item_site_key ?
						' ' . sprintf(
							'<a class="button button-secondary" href="%s">%s</a>',
							esc_url( add_query_arg( array( 'alg_check_item_site_key' => $plugin_slug ) ) ),
							esc_html__( 'Check key', 'wpfactory-key-manager' )
						) :
						''
					),
				);
			}

			foreach ( wpfactory_key_manager()->plugins_updater->themes_to_update as $theme_slug ) {
				$item_site_key = alg_wpcfh_get_site_key( $theme_slug );
				$table_data[]  = array(
					esc_html__( 'Theme', 'wpfactory-key-manager' ),
					( '' != $all_themes[ $theme_slug ]->get( 'Name' ) ?
						$all_themes[ $theme_slug ]->get( 'Name' ) :
						$theme_slug
					),
					$this->get_site_item_key_column( $item_site_key, $theme_slug ),
					sprintf(
						'<a class="button button-primary" href="%s">%s</a>',
						esc_url( add_query_arg( array( 'item_slug' => $theme_slug, 'item_type' => 'theme' ) ) ),
						esc_html__( 'Set key', 'wpfactory-key-manager' )
					) .
					( '' != $item_site_key ?
						' ' . sprintf(
							'<a class="button button-secondary" href="%s">%s</a>',
							esc_url( add_query_arg( array( 'alg_check_item_site_key' => $theme_slug ) ) ),
							esc_html__( 'Check key', 'wpfactory-key-manager' )
						) :
						''
					),
				);
			}

			// Sort by item name
			usort( $table_data, function ( $a, $b ) {
				return ( $a[1] == $b[1] ? 0 : ( $a[1] < $b[1] ? -1 : 1 ) );
			} );

			if ( ! empty( $table_data ) ) {
				$table_data = array_merge(
					array(
						array(
							esc_html__( 'Type', 'wpfactory-key-manager' ),
							esc_html__( 'Item', 'wpfactory-key-manager' ),
							esc_html__( 'Key', 'wpfactory-key-manager' ),
							esc_html__( 'Actions', 'wpfactory-key-manager' )
						)
					),
					$table_data
				);
				$html       .= '<div class="wrap">' .
							   $this->get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) ) .
							   '</div>';
			} else {
				$html .= '<p style="font-style:italic;">' .
						 sprintf(
							 __( 'You have no items from %s installed.', 'wpfactory-key-manager' ),
							 '<a target="_blank" href="' . wpfactory_key_manager()->update_server . '">' .
							 wpfactory_key_manager()->update_server_text .
							 '</a>'
						 ) .
						 '</p>';
			}

			$html .= '<p>' .
					 sprintf(
						 '<a class="button button-secondary" style="margin-top:18px;" href="%s">%s</a>',
						 esc_url( add_query_arg( array( 'alg_update_item_list' => '1' ) ) ),
						 esc_html__( 'Update item list manually', 'wpfactory-key-manager' )
					 ) .
					 '</p>';

			$html .= '</div>';

			echo $html;
		}

	}

endif;
