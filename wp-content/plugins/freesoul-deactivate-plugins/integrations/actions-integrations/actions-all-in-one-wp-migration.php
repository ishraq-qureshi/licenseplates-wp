<?php
/**
 * All In One WP Migration actions to be added to the Actions settings page.

 * @package Freesoul Deactivate Plugins
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

$actions = array(
    'main_file'    => 'all-in-one-wp-migration/all-in-one-wp-migration.php',
    'is_active'    => defined( 'AI1WM_PATH' ),
    'ajax_actions' => array(
        'ai1wm_import' => array(
            'description' => esc_html__( 'Import installation', 'freesoul-deactivate-plugins' ),
            'notes'       => esc_html__( 'Note: You should disable unwanted plugins on the source site before exporting and on the destination site.', 'freesoul-deactivate-plugins' ),
        ),
        'ai1wm_export' => array( 'description' => esc_html__( 'Export installation', 'freesoul-deactivate-plugins' ) ),
        'ai1wm_status' => array( 'description' => esc_html__( 'Check status', 'freesoul-deactivate-plugins' ) ),
    ),
);