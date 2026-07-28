<?php
/**
 * Copyright (C) 2014-2020 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

class Ai1wmle_Main_Controller extends Ai1wmve_Main_Controller {

	/**
	 * Register plugin menus
	 *
	 * @return void
	 */
	public function admin_menu() {
		// Sub-level Settings menu
		add_submenu_page(
			'ai1wm_export',
			__( 'URL Settings', AI1WMLE_PLUGIN_NAME ),
			__( 'URL Settings', AI1WMLE_PLUGIN_NAME ),
			'import',
			'ai1wmle_settings',
			'Ai1wmle_Settings_Controller::index'
		);
	}

	/**
	 * Enqueue scripts and styles for Import Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_import_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wm_import', $hook ) === false ) {
			return;
		}

		if ( is_rtl() ) {
			wp_enqueue_style(
				'ai1wmle_import',
				Ai1wm_Template::asset_link( 'css/import.min.rtl.css', 'AI1WMLE' ),
				array( 'ai1wm_import' )
			);
		} else {
			wp_enqueue_style(
				'ai1wmle_import',
				Ai1wm_Template::asset_link( 'css/import.min.css', 'AI1WMLE' ),
				array( 'ai1wm_import' )
			);
		}

		wp_enqueue_script(
			'ai1wmle_import',
			Ai1wm_Template::asset_link( 'javascript/import.min.js', 'AI1WMLE' ),
			array( 'ai1wm_import' )
		);

		wp_localize_script(
			'ai1wmle_import',
			'ai1wmle_dependencies',
			array( 'messages' => $this->get_missing_dependencies() )
		);
	}

	/**
	 * Enqueue scripts and styles for Settings Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_settings_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wmle_settings', $hook ) === false ) {
			return;
		}

		if ( is_rtl() ) {
			wp_enqueue_style(
				'ai1wmle_settings',
				Ai1wm_Template::asset_link( 'css/settings.min.rtl.css', 'AI1WMLE' ),
				array( 'ai1wm_servmask' )
			);
		} else {
			wp_enqueue_style(
				'ai1wmle_settings',
				Ai1wm_Template::asset_link( 'css/settings.min.css', 'AI1WMLE' ),
				array( 'ai1wm_servmask' )
			);
		}

		wp_enqueue_script(
			'ai1wmle_settings',
			Ai1wm_Template::asset_link( 'javascript/settings.min.js', 'AI1WMLE' ),
			array( 'ai1wm_settings' )
		);

		wp_localize_script(
			'ai1wmle_settings',
			'ai1wm_feedback',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_feedback' ) ),
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);
	}

	/**
	 * Register listeners for actions
	 *
	 * @return void
	 */
	protected function activate_actions() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_export_scripts_and_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_import_scripts_and_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_scripts_and_styles' ), 20 );
	}

	/**
	 * Export and import commands
	 *
	 * @return void
	 */
	public function ai1wm_commands() {
		if ( ai1wmle_is_running() ) {
			add_filter( 'ai1wm_import', 'Ai1wmle_Import_Url::execute', 20 );
			add_filter( 'ai1wm_import', 'Ai1wmle_Import_Download::execute', 30 );
			add_filter( 'ai1wm_import', 'Ai1wmle_Import_Settings::execute', 290 );
			add_filter( 'ai1wm_import', 'Ai1wmle_Import_Database::execute', 310 );

			remove_all_filters( 'ai1wm_import', 5 );
		}
	}

	public function get_missing_dependencies() {
		$messages = array();
		if ( ! extension_loaded( 'curl' ) ) {
			$messages[] = __( 'Your PHP is missing cURL extension. <a href="https://help.servmask.com/knowledgebase/curl-missing-in-php-installation/" target="_blank">Technical details</a>', AI1WMLE_PLUGIN_NAME );
		}

		return $messages;
	}

	/**
	 * Check whether All-in-One WP Migration has been loaded
	 *
	 * @return void
	 */
	public function ai1wm_loaded() {
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'admin_menu' ), 20 );
		} else {
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
		}

		// Settings
		add_action( 'admin_post_ai1wmle_url_settings', 'Ai1wmle_Settings_Controller::settings' );

		// Picker
		add_action( 'ai1wm_import_left_end', 'Ai1wmle_Import_Controller::picker' );

		// Add import button
		add_filter( 'ai1wm_import_url', 'Ai1wmle_Import_Controller::button' );
	}

	/**
	 * WP CLI commands: extension
	 *
	 * @return void
	 */
	public function wp_cli_extension() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command(
				'ai1wm url',
				'Ai1wmle_URL_WP_CLI_Command',
				array(
					'shortdesc'     => __( 'All-in-One WP Migration Command for URL Extension', AI1WMLE_PLUGIN_NAME ),
					'before_invoke' => array( $this, 'activate_extension_commands' ),
				)
			);
		}
	}

	/**
	 * Activates extension specific commands
	 *
	 * @return void
	 */
	public function activate_extension_commands() {
		$_GET['url'] = 1;
		$this->ai1wm_commands();
	}

	/**
	 * Display All-in-One WP Migration notice
	 *
	 * @return void
	 */
	public function ai1wm_notice() {
		?>
		<div class="error">
			<p>
				<?php
				_e(
					'URL Extension requires <a href="https://wordpress.org/plugins/all-in-one-wp-migration/" target="_blank">All-in-One WP Migration plugin</a> to be activated. ' .
					'<a href="https://help.servmask.com/knowledgebase/install-instructions-for-url-extension/" target="_blank">URL Extension install instructions</a>',
					AI1WMLE_PLUGIN_NAME
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Enqueue scripts and styles for Export Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_export_scripts_and_styles( $hook ) {
		if ( stripos( 'toplevel_page_ai1wm_export', $hook ) === false ) {
			return;
		}

		if ( is_rtl() ) {
			wp_enqueue_style(
				'ai1wmle_file_selector',
				Ai1wm_Template::asset_link( 'css/export.min.rtl.css', 'AI1WMLE' )
			);
		} else {
			wp_enqueue_style(
				'ai1wmle_file_selector',
				Ai1wm_Template::asset_link( 'css/export.min.css', 'AI1WMLE' )
			);
		}
	}

	/**
	 * Register initial router
	 *
	 * @return void
	 */
	public function router() {
	}

	/**
	 * Add links to plugin list page
	 *
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file === AI1WMLE_PLUGIN_BASENAME ) {
			$links[] = __( '<a href="https://help.servmask.com/knowledgebase/url-extension-user-guide/" target="_blank">User Guide</a>', AI1WMLE_PLUGIN_NAME );
			$links[] = __( '<a href="https://servmask.com/contact-support" target="_blank">Contact Support</a>', AI1WMLE_PLUGIN_NAME );
		}

		return $links;
	}
}
