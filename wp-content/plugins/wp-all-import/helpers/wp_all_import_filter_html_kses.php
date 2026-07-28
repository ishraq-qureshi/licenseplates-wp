<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
if ( ! defined( 'ABSPATH' ) ) exit;

if( !function_exists('wp_all_import_filter_html_kses')){
	function wp_all_import_filter_html_kses($html, $context = 'post'){
		return wp_kses($html, $context);
	}
}