<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! function_exists('wp_all_import_url_title')){

	function wp_all_import_url_title($str, $separator = 'dash', $lowercase = FALSE) {
		if ($separator == 'dash') {
			$search		= '_';
			$replace	= '-';
		} else {
			$search		= '-';
			$replace	= '_';
		}

		$trans = array(
			'&\#\d+?;'				=> '',
			'&\S+?;'				=> '',
			'\s+'					=> $replace,
			'[^a-z0-9\-\._]'		=> '',
			$replace.'+'			=> $replace,
			$replace.'$'			=> $replace,
			'^'.$replace			=> $replace,
			'\.+$'					=> ''
		);

		$str = wp_strip_all_tags($str);

		foreach ($trans as $key => $val) {
			$str = preg_replace("#".$key."#i", $val, $str);
		}

		if ($lowercase === TRUE) {
			$str = strtolower($str);
		}

		return trim(stripslashes($str));
	}
}