<?php
namespace Wpai\AddonAPI;

if ( ! defined( 'ABSPATH' ) ) exit;

class PMXI_Addon_Admin {
    use Singleton;

    public string $url = WP_ALL_IMPORT_ROOT_URL . '/addon-api';

    public function __construct() {
        add_action( 'pmxi_extend_options_custom_fields', [ $this, 'render' ], 10, 2 );
        add_action( 'pmxi_reimport', [ $this, 'renderUpdateScreen' ], 10, 2 );
        add_action( 'pmxi_confirm_data_to_import', [ $this, 'renderConfirmDataToImport' ], 10, 2 );

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
        add_filter( 'script_loader_tag', [ $this, 'add_type_attribute' ], 10, 3 );
    }

    public function enqueue() {
        // Loaded as an ES module; a stray module on unrelated admin pages
        // invalidates WordPress core's import map in Firefox (strict ordering),
        // breaking screens such as the WP 7.0 Connectors page. Only load it on
        // WP All Import's own pages, where it is used.
        if ( ! $this->is_wp_all_import_page() ) {
            return;
        }

        wp_enqueue_script( 'pmxi-datepicker', $this->url . '/static/vendor/air-datepicker/air-datepicker.min.js', array(), PMXI_VERSION, true );
        wp_enqueue_style( 'pmxi-datepicker', $this->url . '/static/vendor/air-datepicker/air-datepicker.min.css', array(), PMXI_VERSION );

        wp_enqueue_style( 'pmxi-addon-admin-style', $this->url . '/static/css/admin.css', array(), PMXI_VERSION );
        wp_enqueue_script( 'pmxi-addon-admin-script', $this->url . '/static/js/admin.js', array(), PMXI_VERSION, true );
        wp_localize_script( 'pmxi-addon-admin-script', 'pmxiAddon', [
            'ajaxUrl' => get_rest_url( null, 'wp-all-import/v1/addon/fields' ),
        ] );
    }

    private function is_wp_all_import_page(): bool {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( empty( $_GET['page'] ) || ! is_string( $_GET['page'] ) ) {
            return false;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return strpos( sanitize_key( wp_unslash( $_GET['page'] ) ), 'pmxi-' ) === 0;
    }

    public function add_type_attribute( $tag, $handle, $src ) {
        if ( 'pmxi-addon-admin-script' !== $handle ) {
            return $tag;
        }
        $tag = str_replace( ' src', ' type="module" src', $tag );

        return $tag;
    }

    /**
     * Render something on the import page
     *
     * @param string $type
     * @param array $importOptions
     *
     * @return void
     */
    public function render( string $type, array $importOptions ) {
        $subtype = $importOptions['taxonomy_type'];
        $addons  = PMXI_Addon_Manager::get_addons();

        if ( empty( $addons ) ) {
            return;
        }

        foreach ( $addons as $addon ) {
            $view = PMXI_Addon_View::create( $addon->slug, $type, $subtype );
            $view->renderTabs( $importOptions );
        }
    }

    /**
     * Render the update screen
     *
     * @param string $type
     * @param array $importOptions
     *
     * @return void
     */

    public function renderUpdateScreen( string $type, array $importOptions ) {
        $subtype = $importOptions['taxonomy_type'];
        $addons  = PMXI_Addon_Manager::get_addons();

        if ( empty( $addons ) ) {
            return;
        }

        foreach ( $addons as $addon ) {
            $view = PMXI_Addon_View::create( $addon->slug, $type, $subtype );
            $view->renderUpdate( $importOptions );
        }
    }

    public function renderConfirmDataToImport( bool $isWizard, array $importOptions ) {
        $type    = $importOptions['custom_type'];
        $subtype = $importOptions['taxonomy_type'];
        $addons  = PMXI_Addon_Manager::get_addons();

        if ( empty( $addons ) ) {
            return;
        }

        foreach ( $addons as $addon ) {
            $view = PMXI_Addon_View::create( $addon->slug, $type, $subtype );
            $view->renderConfirmDataToImport( $importOptions );
        }
    }
}
