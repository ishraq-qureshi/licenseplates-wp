<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function pmxi_wpallimport_info_panel($html){
	$html = "\n"
		. '<div id="wpallimport-cta-div"><h5 id="wpallimport-cta-headline">The best group of WordPress folks on the Internet.</h5><div id="wpallimport-cta-text">WP All Import users are some of the most advanced in the industry, working on some of the most interesting projects.<br><br>Discuss, share your work, and learn from the best.<br></div><a id="wpallimport-cta-link" href="https://www.facebook.com/groups/wpallimport" target="_blank">Join the Facebook Group</a></div>' . "\n"
		. "\n";

	return $html;
}