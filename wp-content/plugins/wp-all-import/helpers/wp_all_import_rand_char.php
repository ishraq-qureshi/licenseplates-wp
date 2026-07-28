<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! function_exists('wp_all_import_rand_char')){

	function wp_all_import_rand_char($length) {
	  $random = '';
	  for ($i = 0; $i < $length; $i++) {
	    $random .= chr( function_exists( 'wp_rand' ) ? wp_rand( 33, 126 ) : random_int( 33, 126 ) );
	  }
	  return $random;
	}
}