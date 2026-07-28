<?php
/*
Plugin Name: PYI Review Aggregator
Plugin URI: 
Description: This plugin lets you aggregate, cache and display reviews
Version: 1.21
Author:
Author URI:

This plugin lets you aggregate, cache and display reviews
*/

require_once(dirname(__FILE__).'/controls.php');
require_once(dirname(__FILE__).'/reviews-cpt.php');
require_once(dirname(__FILE__).'/settings.php');
require_once(dirname(__FILE__).'/cron.php');
require_once(dirname(__FILE__).'/api.php');
require_once(dirname(__FILE__).'/template.php');

add_action('admin_menu', 'pyi_review_aggregator_admin_menu');
function pyi_review_aggregator_admin_menu() {
	$hook = add_menu_page('PYI Reviews', 'PYI Reviews', 'manage_options', 'pyi-review-aggregator', '', 'dashicons-star-half', 50);
}