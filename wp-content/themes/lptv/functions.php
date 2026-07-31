<?php
/**
* Begin Include Files 
*/
require_once(dirname(__FILE__).'/includes/theme-cpts.php');
require_once(dirname(__FILE__).'/includes/theme-functions.php');
require_once(dirname(__FILE__).'/includes/theme-shortcodes.php');

/** 
* Begin Wordpress Menu 
*/
add_theme_support('nav-menus');

function lptv_menus_init() {
    register_nav_menus( array(
        'menu-1' => __( 'Main Menu', 'lptv' ),
        'menu-2' => __( 'Footer Menu', 'lptv' ),
        'menu-3' => __( 'Menu', 'lptv' ),
    ) );            
}
add_action('init', 'lptv_menus_init');

/**
 * Registers Widget Area
 */
function lptv_widgets_init() {
    // Header
    register_sidebar( array(
        'name'          => __( 'Phone Number', 'lptv' ),
        'id'            => 'phone',
        'before_widget' => '',
        'after_widget'  => ''
    ) );
    // Footer
    register_sidebar( array(
        'name'          => __( 'Footer Address', 'lptv' ), 
        'id'            => 'address',
        'before_widget' => '<ul>',
        'after_widget'  => '</ul>'
    ) );
}
add_action( 'widgets_init', 'lptv_widgets_init' );

// function for disable page delete option
function restrict_page_deletion($post_ID){
    $user = get_current_user_id();
    $restricted_pageId = 398;

    if($post_ID == $restricted_pageId) {       
        echo "You are not authorized to delete this page.";
        exit;
    }
}
add_action('wp_trash_post', 'restrict_page_deletion', 10, 1);


// getting  shortcode for any page or post
function post__shortcode( $atts ) {
    $a = shortcode_atts(
        array (
            'id'   => false,
            'type' => "content",
        ), $atts );
    $id   = $a [ 'id' ];
    $type = $a [ 'type' ];
    if ( ! is_numeric( $id ) ) {
        return '';
    }
    $post = get_post( $id );
    if ( ! $post ) {
        return '';
    }
    switch ( $type ) {
        case "content":
            return ($id === get_the_ID() || $id === get_queried_object_id()) ? '' : apply_filters('get_the_content', $post->post_content);
            break;
        case "title":
            return $post->post_title;
            break;
        case "excerpt":
            return $post->post_excerpt;
            break;
        case "featured":
            $output = '';
            if (has_post_thumbnail($post->ID)) {
                $image  = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );
                //$output = '<div id="custom-bg" style="background-image: url('.$image[0].')"></div>';
                $output = '<img src="'.$image[0].'" alt="" />';
            }
            return $output;
            break;
    }
    return '';
}
add_shortcode( 'post', 'post__shortcode' ); 

// CATEGORY TEXT EDITOR --- START
if( is_admin() ) {
// LETS REMOVE THE HTML FILTERING
remove_filter( 'pre_term_description', 'wp_filter_kses' );
remove_filter( 'term_description', 'wp_kses_data' );

// LETS ADD OUR NEW CAT DESCRIPTION BOX
add_filter('edit_category_form_fields', 'filter_wordpress_category_editor');
function filter_wordpress_category_editor($tag) {
    ?>
    <table class="form-table">
        <tr class="form-field">
            <th scope="row" valign="top"><label for="description"><?php _ex('Description', 'Taxonomy Description'); ?></label></th>
            <td>
            <?php
                $settings = array('wpautop' => true, 'media_buttons' => true, 'quicktags' => true, 'textarea_rows' => '15', 'textarea_name' => 'description' );  
          wp_editor(html_entity_decode($tag->description , ENT_QUOTES, 'UTF-8'), 'description1', $settings);
            ?>
            <br />
            <span class="description"><?php _e('The description is not prominent by default; however, some themes may show it.'); ?></span>
            </td>
        </tr>
    </table>
    <?php
}

// HIDE THE DEFAULT CAT DESCRIPTION BOX USING JQUERY
add_action('admin_head', 'remove_default_category_description');
function remove_default_category_description()
{
    global $current_screen;
    if ( $current_screen->id == 'edit-category' )
    {
    ?>
        <script type="text/javascript">
        jQuery(function($) {
            $('textarea#description').closest('tr.form-field').remove();
        });
        </script>
    <?php
    }
}
}
// CATEGORY TEXT EDITOR --- END

add_filter('redirect_canonical', function($redirect_url, $requested_url) {
    if (strpos($_SERVER['REQUEST_URI'], '.') !== false) {
        return false;
    }
    return $redirect_url;
}, 10, 2);

add_action('parse_request', 'handle_dotted_product_urls', 5);
function handle_dotted_product_urls($wp) {
    if (strpos($_SERVER['REQUEST_URI'], '.-') !== false) {
        global $wpdb;
        
        // Extract the slug from URL
        $request_slug = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        
        // Find product by original post_name (with dot)
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_name = %s 
             AND post_type = 'product' 
             AND post_status = 'publish'", 
            $request_slug
        ));
        
        if ($product_id) {
            $wp->query_vars = [
                'post_type' => 'product',
                'p' => $product_id
            ];
            $wp->request = $request_slug;
            $wp->matched_rule = null; // Bypass normal rewrite matching
            unset($wp->query_vars['error']); // Remove 404 flag
        }
    }
}



// Enqueue custom JS for checkout
add_action( 'wp_footer', function() {
    if ( is_checkout() ) {
        ?>
        <script>
        jQuery(function($) {
            function reorderCountrySelect($select) {
                var usOption = $select.find('option[value="US"]');
                if (usOption.length) {
                    // Remove and reinsert at top
                    usOption.remove();
                    $select.prepend(usOption);

                    // Refresh Select2
                    $select.trigger('change.select2');
                }
            }

            // Apply on billing and shipping country selects
            reorderCountrySelect($('#billing_country'));
            reorderCountrySelect($('#shipping_country'));
        });
        </script>
        <?php
    }
});


// Preselect United States by default in checkout
add_filter( 'default_checkout_billing_country', function( $country ) {
    return empty( $country ) ? 'US' : $country;
} );

add_filter( 'default_checkout_shipping_country', function( $country ) {
    return empty( $country ) ? 'US' : $country;
} );

// dequeue individual block inline styles (wp 6.x+)
function remove_block_inline_styles() {
    //get all registered blocks and remove their styles
    $block_registry = WP_Block_Type_Registry::get_instance();
    foreach ($block_registry->get_all_registered() as $block_name => $block_type) {
        if (!empty($block_type->style_handles)) {
            foreach ($block_type->style_handles as $style_handle) {
                wp_dequeue_style($style_handle);
                wp_deregister_style($style_handle);
            }
        }
        // also handle the older style property
        if (!empty($block_type->style)) {
            $style_handle = is_array($block_type->style) ? $block_type->style[0] : $block_type->style;
            wp_dequeue_style($style_handle);
            wp_deregister_style($style_handle);
        }
    }
    
    // remove global styles inline css
    wp_dequeue_style('global-styles');
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('classic-theme-styles');
}
add_action('wp_enqueue_scripts', 'remove_block_inline_styles', 200);

add_action( 'wp_enqueue_scripts', 'force_load_cart_fragments', 99 );
function force_load_cart_fragments() {
    if ( function_exists( 'is_woocommerce' ) ) {
        wp_enqueue_script( 'wc-cart-fragments' );
    }
}

add_filter( 'woocommerce_email_order_items_args', function ( $args ) {
    $args['image_size'] = array( 300, 300 );
    return $args;
} );

// Strip WooCommerce's default colored header bar/heading from the admin "New Order"
// email only - it's redesigned as a plain manufacturing work order sheet.
add_filter( 'woocommerce_email_styles', function ( $css, $email ) {
    if ( $email && 'new_order' === $email->id ) {
        $css .= "\n#template_header { background-color: transparent !important; padding: 0 !important; border: 0 !important; }\n";
        $css .= "#template_header h1, #template_header h1 a { display: none !important; }\n";
        $css .= "#template_header_image { display: none !important; }\n";
    }
    return $css;
}, 10, 2 );

// Remove WooCommerce's default "mobile app" upsell ("Congratulations on the sale...
// Collect payments easily...") from the footer of the admin "New Order" email only -
// it's a manufacturing work order sheet, not a stock WC email.
add_action( 'init', function () {
    if ( ! function_exists( 'WC' ) ) {
        return;
    }
    $mailer = WC()->mailer();
    if ( isset( $mailer->emails['WC_Email_New_Order'] ) ) {
        remove_action( 'woocommerce_email_footer', array( $mailer->emails['WC_Email_New_Order'], 'mobile_messaging' ), 9 );
    }
}, 20 );