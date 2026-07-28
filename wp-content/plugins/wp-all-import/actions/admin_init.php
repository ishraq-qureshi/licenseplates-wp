<?php
	
if ( ! defined( 'ABSPATH' ) ) exit;
function pmxi_admin_init(){
	wp_enqueue_script('wp-all-import-script', WP_ALL_IMPORT_ROOT_URL . '/static/js/wp-all-import.js', array('jquery'), PMXI_VERSION, true);
    // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
    @ini_set('mysql.connect_timeout', 300);
    // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
    @ini_set('default_socket_timeout', 300);
}