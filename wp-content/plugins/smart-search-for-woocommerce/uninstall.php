<?php
/**
 * Searchanise Uninstall
 *
 * Uninstalling Searchanise deletes searchanise engine
 *
 * @package Searchanise\Uninstaller
 */

namespace Searchanise\SmartWoocommerceSearch;

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/init.php';

$searchanise_engines = Api::get_instance()->get_engines( null, false, true );
foreach ( $searchanise_engines as $searchanise_engine ) {
	Api::get_instance()->addon_status_request( Api::ADDON_STATUS_DELETED, $searchanise_engine['lang_code'] );
	Api::get_instance()->set_export_status( Api::EXPORT_STATUS_NONE, $searchanise_engine['lang_code'] );
}

Queue::get_instance()->clear_actions();
Cron::unregister();
Installer::uninstall();
