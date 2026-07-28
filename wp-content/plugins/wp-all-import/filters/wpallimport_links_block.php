<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function pmxi_wpallimport_links_block($html){
	$src =  WP_ALL_IMPORT_ROOT_URL . '/static/img/f_logo_RGB-Blue_250.png';
	$text = __( 'Discuss, share your work, and learn from the best.', 'wp-all-import' );
	$doc_text = __( 'Documentation', 'wp-all-import' );
	$sup_text = __( 'Support', 'wp-all-import' );

	$html = "\n"
		. "\t" . '<div class="wpallimport-links">' . "\n"
		. "\t\t\t\t" . '<a href="http://www.wpallimport.com/support/?utm_source=import-plugin-free&utm_medium=help&utm_campaign=premium-support" target="_blank">' . $sup_text . '</a> | <a href="http://www.wpallimport.com/documentation/?utm_source=import-plugin-free&utm_medium=help&utm_campaign=docs" target="_blank">' . $doc_text . '</a> ' . "\n"
		. "\t\t\t" . '</div>' . "\n";

	return $html;

}