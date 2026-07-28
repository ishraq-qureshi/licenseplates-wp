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
                $class_name = 'cat-' . sanitize_title($term->name); // e.g., 'cat-widgets'
                echo '<li class="' . esc_attr($class_name) . '">';
                echo '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';

                // Recursive call to render subcategories
                render_category_tree($term->term_id);

                echo '</li>';
            }
            echo '</ul>';
        }
    }
}

// Call the function to render starting from category ID 1440
//render_category_tree(1440);
echo render_category_tree_v2(1440);
?> 