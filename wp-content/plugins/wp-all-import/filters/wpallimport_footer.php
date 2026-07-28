<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function pmxi_wpallimport_footer($html){
	$src = WP_ALL_IMPORT_ROOT_URL.'/static/img/f_logo_RGB-Blue_250.png';
	$text = __('Discuss, share your work, and learn from the best.', 'wp-all-import');
	$created =  esc_html__('Created by', 'wp-all-import');
	$upgrade_text = __( 'Find out more about the Pro edition of WP All Import.', 'wp-all-import' );

	$html = "\t" . '<div class="wpallimport-footer">' . "\n"
		. "\t" . '<div class="wpallimport-footer-left-column wpallimport-text-link">' . "\n"
		. "\t" . '<a href="https://www.wpallimport.com/wordpress-xml-csv-import/?utm_source=import-plugin-free&utm_medium=help&utm_campaign=upgrade-to-pro" target="_blank" >' . $upgrade_text . '</a>' . "\n"
		. "\t" . '</div>' . "\n"
		. "\t" . '<div class="wpallimport-soflyy">' . "\n"
		. "\t\t" . '<a href="http://soflyy.com/" target="_blank" class="wpallimport-created-by">' . $created . '<span></span></a>' . "\n"
		. "\t" . '</div>' . "\n"
		. "\t" . '<div class="wpallimport-cta-text-link">' . "\n"
		. "\t    " . '<a href="https://www.facebook.com/groups/wpallimport" target="_blank" ><img src="' . $src . '" alt="Find us on Facebook"/></a>' . "\n"
		. "        " . '<p><a href="https://www.facebook.com/groups/wpallimport" target="_blank" >' . $text . '</a></p>' . "\n"
		. "    " . '</div>' . "\n"
		. "\t" . '</div>';

	return $html;
}