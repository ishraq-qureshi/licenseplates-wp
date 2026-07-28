<?php
/**
 * The template used for slider layout
 *
 */
?>

<?php 
if (is_singular('post')) {
    echo "Blog";
} elseif ( !is_front_page() && is_home() ) {
    echo "<span>Everything You Wanted To Know About</span>
            LICENSE <strong>PLATES</strong>";
} elseif (is_category()) {
    single_cat_title();
} elseif (is_tax( 'team_cat')) {
    $term = get_queried_object();
    echo $term->name;
} elseif (is_tag()) {
    single_tag_title(); 
} elseif (is_home()) {
    echo '';
} elseif (is_search()) {
    printf(esc_html__('Search Results for %s'), '"'. get_search_query().'"');
} elseif (is_404()) {
    esc_html_e('404 Error!');
} elseif (class_exists('WooCommerce') && ((is_product_category() || is_product_tag()))) {
    single_cat_title();
} elseif (class_exists('WooCommerce') && is_shop()) {
    $shop_page_id = wc_get_page_id( 'shop');
    echo get_the_title($shop_page_id);  
} elseif (is_singular('product')) {
    global $post;
    $terms = get_the_terms( $post->ID, 'product_cat' ); // Get categories associated with the product

    if ( $terms && ! is_wp_error( $terms ) ) {
        $categories = array();

        // Loop through each category
        foreach ( $terms as $term ) {
            $categories[] = '<span>' . esc_html( $term->name ) . '</span>'; // Wrap each category name in a span
        }

        // Output the categories, separated by commas
        echo implode( ', ', $categories );
    }
} elseif (is_singular('cases')) {
    echo 'Work';
} else { 
    the_title();
} ?>