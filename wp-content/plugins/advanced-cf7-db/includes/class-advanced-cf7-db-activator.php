<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.vsourz.com
 * @since      1.0.0
 *
 * @package    Advanced_Cf7_Db
 * @subpackage Advanced_Cf7_Db/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Advanced_Cf7_Db
 * @subpackage Advanced_Cf7_Db/includes
 * @author     vsourz Digital <mehul@vsourz.com>
 */
class Advanced_Cf7_Db_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		global $wpdb;
		if (function_exists('is_multisite') && is_multisite()) {
        // check if it is a network activation - if so, run the activation function for each blog id
             $old_blog = $wpdb->blogid;
            // Get all blog ids
			
            $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            foreach ($blogids as $blog_id) {
                switch_to_blog($blog_id);
                create_table_cf7_vdata();
				create_table_cf7_vdata_entry();
				create_f7_capability();
            }
            switch_to_blog($old_blog);
		}else{
				create_table_cf7_vdata();
				create_table_cf7_vdata_entry();
				create_f7_capability();
		}

		//call function for php version check
		vsz_check_php_version();

		// Remove legacy CSV folder from old versions (admin/csv)
		vsz_cf7_remove_legacy_csv_folder();

	}

}


//checking php version not valid then it triggers fatal error and add notice for update php version.
function vsz_check_php_version(){

	if (version_compare(PHP_VERSION, '7.4', '<'))
	{
		?><div class="notice notice-error">
	        <p><?php echo 'You need to update the php version to activate the plugin, your currrent php version is '.PHP_VERSION; exit; ?></p>
	    </div><?php
    }
}

/**
 * Remove legacy CSV folder that was previously used to store import/export files.
 *
 * The directory used to live under the plugin path at admin/csv. Since files are now
 * stored under the uploads directory (advanced-cf7-db-import/csv/), this legacy
 * folder can be safely removed on activation/update.
 */
function vsz_cf7_remove_legacy_csv_folder() {

	$plugin_dir = plugin_dir_path( dirname( __FILE__ ) );
	$legacy_dir = trailingslashit( $plugin_dir ) . 'admin/csv';

	if ( ! is_dir( $legacy_dir ) ) {
		return;
	}

	$plugin_dir_real = realpath( $plugin_dir );
	$legacy_dir_real = realpath( $legacy_dir );

	// Extra safety: ensure the directory we are about to delete is inside this plugin.
	if ( ! $plugin_dir_real || ! $legacy_dir_real || strpos( $legacy_dir_real, $plugin_dir_real ) !== 0 ) {
		return;
	}

	// Prefer WordPress core helper when available.
	if ( function_exists( 'wp_delete_directory' ) ) {
		wp_delete_directory( $legacy_dir_real );
		return;
	}

	// Fallback recursive delete for older WordPress versions.
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $legacy_dir_real, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $files as $file ) {
		if ( $file->isDir() ) {
			@rmdir( $file->getRealPath() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		} else {
			@unlink( $file->getRealPath() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}

	@rmdir( $legacy_dir_real ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
}


/**
 * Contact Form submitted table created from here
 */

function create_table_cf7_vdata(){

	global $wpdb;
	$table_name = $wpdb->prefix .'cf7_vdata';

	$charset_collate = $wpdb->get_charset_collate();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
	if( $wpdb->get_var( $wpdb->prepare( "show tables like %s" , $wpdb->esc_like(  $table_name ) ) ) != $table_name ) {
        $sql = "CREATE TABLE " . $table_name . " (
             `id` int(11) NOT NULL AUTO_INCREMENT,
			 `created` timestamp NOT NULL,
			  UNIQUE KEY id (id)
		)$charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}
/**
 * Contact Form entry table created from here
 */

function create_table_cf7_vdata_entry(){
	global $wpdb;
	$table_name = $wpdb->prefix .'cf7_vdata_entry';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
 
	$charset_collate = $wpdb->get_charset_collate();
	if( $wpdb->get_var( $wpdb->prepare("show tables like %s", $wpdb->esc_like(  $table_name ) ) ) != $table_name ) {
        $sql = "CREATE TABLE " . $table_name . " (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`cf7_id` int(11) NOT NULL,
				`data_id` int(11) NOT NULL,
				`name` varchar(250),
				`value` text,
				UNIQUE KEY id (id)
		)$charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}

function create_f7_capability(){
// Add Capability when update plugin
	$role = get_role( 'administrator');
	$args = array('post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1);
	$cf7Forms = get_posts( $args );
	foreach($cf7Forms as $data){
		$role->add_cap('cf7_db_form_view'.$data->ID);
		$role->add_cap('cf7_db_form_edit_'.$data->ID);
	}
}