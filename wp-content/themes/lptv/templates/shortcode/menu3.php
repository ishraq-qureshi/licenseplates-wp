<?php
/**
 * The template used for shortcode layout *
 */
?> 

<?php 
if (!function_exists('render_category_tree')) {
    function render_category_tree($parent_id) {

        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => $parent_id,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);


        if (!empty($terms) && !is_wp_error($terms)) {
            echo '<ul class="sub-menu">';
            foreach ($terms as $term) {
                // Get product count in this term (including children)
                $product_query = new WP_Query([
                    'post_type' => 'product',
                    'posts_per_page' => 1,
                    'tax_query' => [[
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => [$term->term_id],
                        'include_children' => true,
                        'operator' => 'IN'
                    ]]
                ]);

                if ($product_query->have_posts()) {
                    $class_name = 'cat-' . sanitize_title($term->name);
                    echo '<li class="' . esc_attr($class_name) . '">';
                    echo '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';

                    // Recursive call
                    render_category_tree($term->term_id);

                    echo '</li>';
                }

                wp_reset_postdata();
            }
            echo '</ul>';
        }

    }
}

//Call the function
//render_category_tree(1990);


echo render_category_tree_v2(1990);



?>
