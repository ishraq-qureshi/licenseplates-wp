<?php
if ( ! defined( 'ABSPATH' ) ) exit;
function pmxi_wp_ajax_dismiss_notifications(){

	if ( ! check_ajax_referer( 'wp_all_import_secure', 'security', false )){
		exit( json_encode(array('result' => false, 'msg' => __('Security check', 'wp-all-import'))) );
	}

	if ( ! current_user_can( PMXI_Plugin::$capabilities ) ){
		exit( json_encode(array('result' => false, 'msg' => __('Security check', 'wp-all-import'))) );
	}
	
	if (isset($_POST['addon']) ) {
		update_option(sanitize_key($_POST['addon']) . '_notice_ignore', 'true', false);
	}
	exit( json_encode( array('result' => true)));
}