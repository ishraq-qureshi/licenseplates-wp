<?php
/*
 * 
 * Bulk remove posts from category
 * 
 * Plugin Name: Bulk remove posts from category
 * Plugin URI:   https://masterns-studio.com/code-factory/wordpress-plugin/bulk-remove-from-category/
 * Description: Bulk remove posts from category
 * Version: 3.4
 * Author: MasterNs
 * Author URI: https://masterns-studio.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Tested up to: 6.7.1
 * Text Domain: bulk-remove-posts-from-category
 * Domain Path: languages/
 * 
 * WC tested up to: 9.4.2
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/*
*
* add admin files
*
*/

add_action( 'admin_enqueue_scripts', 'wpbulkremove_enqueue' );
function wpbulkremove_enqueue($hook) {
	
		$my_js_ver  = date("ymd.Gis", filemtime( plugin_dir_path( __FILE__ ) . '/js/wpbulkremove.js' ));
		$my_css_ver = date("ymd.Gis", filemtime( plugin_dir_path( __FILE__ ) . '/css/wpbulkremove_style.css' ));
		
		wp_enqueue_style('wpbulkremove_style', plugins_url('/css/wpbulkremove_style.css', __FILE__), false,   $my_css_ver);	     
		wp_enqueue_script( 'wpbulkremove-js', plugins_url('/js/wpbulkremove.js', __FILE__), array(), $my_js_ver);
		$wpbulkremove_array = array(
			'wpbulkremove_string' => __( 'Remove from category', 'bulk-remove-posts-from-category' ),
			'ajax_url'  => admin_url( 'admin-ajax.php' ),	    
			'security'  => wp_create_nonce( 'brfc_security_nonce' )
		);
		wp_localize_script( 'wpbulkremove-js', 'wpbulkremove', $wpbulkremove_array );

}

/*
*
* ajax hook
*
*/

add_filter('allowed_http_origins', function($origins) {
    $origins[] = home_url('');
    return $origins;
});

add_action( 'wp_ajax_masterns_bulk_remove_cat', 'masterns_bulk_remove_cat_edit_hook' ); 
function masterns_bulk_remove_cat_edit_hook() {

	if ( ! wp_verify_nonce( $_POST['security'], 'brfc_security_nonce' ) ) {
		wp_send_json_error( 'Invalid security token sent.' );	    
		wp_die();	 
	}
	
	if( empty( $_POST[ 'post_ids' ] ) ) {
		die();
	}	
	if( empty( $_POST[ 'catout' ] ) ) {
		die();
	}
	

	foreach( $_POST[ 'post_ids' ] as $id ) {
		$post_id = (int)$id;
		foreach( $_POST[ 'catout' ] as $cat ) {
			$cat_tax = str_replace("[]", "", $cat['taxonomy']);
				if (strpos($cat_tax, 'tax_input[') !== false) {
					$cat_tax = str_replace("tax_input[", "", $cat_tax);
					$cat_tax = str_replace("]", "", $cat_tax);
				}
				if($cat_tax == 'post_category'){
					$cat_tax = 'category';
				}
			$cat_id = (int)$cat['taxonomy_id'];	
			$rem = wp_remove_object_terms( $post_id, $cat_id, $cat_tax );
		}
	} 
	die();
}

// add compatibility with WooCommerce HPOS
add_action('before_woocommerce_init', function(){
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );

    }
});


