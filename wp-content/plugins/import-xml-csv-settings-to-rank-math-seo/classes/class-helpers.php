<?php


if( !class_exists('WPAI_RankMath_SEO_Helpers')) {
    class WPAI_RankMath_SEO_Helpers
    {
        public function __construct()
        {

        }

        public function get_post_type() {
            global $argv;
            $import_id = false;
            /**
             * Show fields based on post type
             **/

            $custom_type = false;

            if ( ! empty( $argv ) ) {
                if ( isset( $argv[3] ) ) {
                    $import_id = $argv[3];
                }
            }

            if ( ! $import_id ) {
                // Get import ID from URL or set to 'new'
                if ( isset( $_GET['import_id'] ) ) {
                    $import_id = $_GET['import_id'];
                } elseif ( isset( $_GET['id'] ) ) {
                    $import_id = $_GET['id'];
                }

                if ( empty( $import_id ) ) {
                    $import_id = 'new';
                }
            }

            // Declaring $wpdb as global to access database
            global $wpdb;

            // Get values from import data table
            $imports_table = $wpdb->prefix . 'pmxi_imports';

            // Get import session from database based on import ID or 'new'
            $import_options = $wpdb->get_row( $wpdb->prepare("SELECT options FROM $imports_table WHERE id = %d", $import_id), ARRAY_A );

            // If this is an existing import load the custom post type from the array
            if ( ! empty($import_options) )	{
                $import_options_arr = unserialize($import_options['options']);
                $custom_type = $import_options_arr['custom_type'];
            } else {
                // If this is a new import get the custom post type data from the current session
                $handler            = $this->fetch_handler();
                if ( $handler === false ) {
                    return $custom_type;
                }
                
                $import_options_arr = $handler->get_session_data();
                
                
                $custom_type = empty($import_options_arr['custom_type']) ? '' : $import_options_arr['custom_type'];
            }

            return $custom_type;
        }

        public function get_taxonomy_type() {
            global $argv;
            $import_id = false;
            /**
             * Show fields based on post type
             **/

            $taxonomy_type = false;

            if ( ! empty( $argv ) ) {
                if ( isset( $argv[3] ) ) {
                    $import_id = $argv[3];
                }
            }

            if ( ! $import_id ) {
                // Get import ID from URL or set to 'new'
                if ( isset( $_GET['import_id'] ) ) {
                    $import_id = $_GET['import_id'];
                } elseif ( isset( $_GET['id'] ) ) {
                    $import_id = $_GET['id'];
                }

                if ( empty( $import_id ) ) {
                    $import_id = 'new';
                }
            }

            // Declaring $wpdb as global to access database
            global $wpdb;

            // Get values from import data table
            $imports_table = $wpdb->prefix . 'pmxi_imports';

            // Get import session from database based on import ID or 'new'
            $import_options = $wpdb->get_row( $wpdb->prepare("SELECT options FROM $imports_table WHERE id = %d", $import_id), ARRAY_A );

            // If this is an existing import load the custom post type from the array
            if ( ! empty($import_options) )	{
                $import_options_arr = unserialize($import_options['options']);
                $taxonomy_type = $import_options_arr['taxonomy_type'];
            } else {
                // If this is a new import get the custom post type data from the current session
                $handler            = $this->fetch_handler();
                if ( $handler === false ) {
                    return $taxonomy_type;
                }
                
                $import_options_arr = $handler->get_session_data();
                
                
                $taxonomy_type = empty($import_options_arr['taxonomy_type']) ? '' : $import_options_arr['taxonomy_type'];
            }

            return $taxonomy_type;
        }

        public function fetch_handler() {
            // is_plugin_active() lives in wp-admin/includes/plugin.php, which is not
            // loaded on the front end (e.g. cron or import_key triggered imports).
            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            // Check if Pro version is active
            if ( is_plugin_active( 'wp-all-import-pro/wp-all-import-pro.php' ) ) {
                $folder = 'wp-all-import-pro';
            } elseif ( is_plugin_active('wp-all-import/plugin.php' ) ) {
                $folder = 'wp-all-import';
            } else {
                return false;
            }

            $class_paths = [
                WP_PLUGIN_DIR . '/' . $folder . '/classes/session.php',
                WP_PLUGIN_DIR . '/' . $folder . '/classes/input.php',
                WP_PLUGIN_DIR . '/' . $folder . '/classes/handler.php'
            ];

            foreach ( $class_paths as $path ) {
                require_once( $path );
            }

            $handler = new PMXI_Handler();
            return $handler;
            
        }


    }
}