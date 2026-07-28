<?php

/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

defined('ABSPATH') || exit;

get_header('shop'); ?>

<?php
// Get the WooCommerce Shop Background from the Customizer
$woocommerce_shop_background = get_theme_mod('lptv_woocommerce_shop_background');

// Check if we're on a product category or tag page
if (is_product_category() || is_product_tag()) {
    $title = single_cat_title("", false);

    // if (strpos($title, "License Plates") === false) {
    //     $title .= " License Plates";
    // }

    // @aleksey Jason said category page should have just a title, no need to add anything 17 june 2025
} else {
    $title = 'Shop'; // Default title for the shop page
}

// Start the banner div and apply the background if set
if ($woocommerce_shop_background) {
    echo '<div class="innerBnner shop-page-banner" style="background-image: url(' . esc_url($woocommerce_shop_background) . ');">';
} else {
    echo '<div class="innerBnner shop-page-banner-default">';
}


// if this is gcc category, show this categories as child categories
$gcc_categories = [];
if (is_product_category() && get_queried_object()->slug === 'gcc-plates') {


    $links[] = 'abu-dhabi';
    $links[] = 'sharjah';
    $links[] = 'saudi-arabia-license-plates';
    $links[] = 'qatar-license-plates';
    $links[] = 'oman';
    $links[] = 'kuwait-license-plates';
    $links[] = 'dubai';
    $links[] = 'bahrain-license-plates';

    // sort links by name
    sort($links);

    $childrens = [];
    foreach ($links as $link) {
        $gcc_categories[] = get_term_by('slug', $link, 'product_cat');
    }
}

$category_slugs = array(
    'european-plates' => 0,
    'canadian-license-plates' => 7,
    'clearance' => 15,
    'custom-fun-plates' => 4,
    'flag-plates-oval-id' => 9,
    'gcc-plates' => 3,
    'international-plates' => 2,
    'military-plates' => 6,
    'motorcycle-plates' => 5,
    'auto-brand-plates' => 12,
    'nautical-plates' => 8,
    'promotional-plates' => 13,
    'religious-plates' => 11,
    'sport-hobby-plates' => 10,
    'usa-state-plates' => 1,
);

?>

<div class="container">
    <div class="positionBox">
        <div class="row">
            <div class="col-md-6 col-12 textBox">
                <h1 class="title"><?php echo esc_html($title); ?></h1> <!-- Display the dynamic title -->
            </div>
            <div class="col-md-6 col-12 blankBox"></div>
        </div>
        <?php echo do_shortcode('[post type="content" id="1599"]'); ?>
    </div>
</div>

<div class="shopWrap wooWrap">
    <div class="container">

        <?php
		$term = get_queried_object();
		$custom_title = get_field('custom_title', $term);
		
		if ($custom_title ) { echo '<div class="cat-custom-title"><h2 class="scnTitle i-v">'.$custom_title.'</h2></div>'; }
		
        do_action('woocommerce_before_main_content');

        if (is_product_category()) {

            $term = get_queried_object();

            if (is_product_category() && get_queried_object()->slug === 'gcc-plates') {
                $children = $gcc_categories;
            } elseif (is_product_category() && get_queried_object()->slug === 'license-plates') {
				
                // get children of license-plates category
                $children = get_terms([
                    'taxonomy'   => 'product_cat',
                    'parent'     => $term->term_id,
                    'hide_empty' => true,
                ]);
                
                // sort children according to $category_slugs array
                if (!empty($children)) {
                    usort($children, function($a, $b) use ($category_slugs) {
                        $a_order = isset($category_slugs[$a->slug]) ? $category_slugs[$a->slug] : 999;
                        $b_order = isset($category_slugs[$b->slug]) ? $category_slugs[$b->slug] : 999;
                        return $a_order - $b_order;
                    });
                }
            } else {
                $children = get_terms([
                    'taxonomy'   => 'product_cat',
                    'parent'     => $term->term_id,
                    'hide_empty' => true,
                ]);
            }

            if (!empty($children)) {

                echo '<ul class="products columns-3">';

                foreach ($children as $child) {

                    $image = get_term_meta($child->term_id, 'categories_image', true);
                    $website_url = get_bloginfo('url');
                    $image_url = $website_url . '/wp-content/resources/images/' . $image;

                    $imagepath = __DIR__ . '/../../../resources/images/resized_' . $image;

                    // check if file exists
                    if (file_exists($imagepath)) {
                        $image_url = $website_url . '/wp-content/resources/images/resized_' . $image;
                    } else if ($image) {
                        $image_url = $website_url . '/wp-content/resources/images/' . $image;
                    } else {
                        $image_url = $website_url . '/wp-content/resources/images/default.jpg';
                    }

                    if ($image) {
                    } else {
                        $thumbnail_id = get_term_meta($child->term_id, 'thumbnail_id', true);
                        $image = wp_get_attachment_url($thumbnail_id);
                    } ?>

                    <li class="product-category">
                        <a href="<?php echo esc_url(get_term_link($child)); ?>">
                            <?php if ($image) : ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($child->name); ?>" />
                            <?php else : ?>
                                <img src="<?php echo wc_placeholder_img_src(); ?>" alt="<?php echo esc_attr($child->name); ?>" />
                            <?php endif; ?>
                            <h2><?php echo esc_html($child->name); ?></h2>
                        </a>
                    </li>
		
				<?php 
				}
                echo '</ul>';

                // also show products that belong exactly to the current category (not subcategories)
                $current_term = get_queried_object();
                $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

                $products_query = new WP_Query(array(
                    'post_type' => 'product',
                    'posts_per_page' => get_option('posts_per_page'),
                    'paged' => $paged,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field'    => 'term_id',
                            'terms'    => $current_term->term_id,
                            'include_children' => false, // only exact category match
                        ),
                    ),
                    'meta_query' => array(
                        array(
                            'key' => '_stock_status',
                            'value' => 'instock',
                            'compare' => '='
                        )
                    ),
                    'orderby' => 'menu_order title',
                    'order' => 'ASC'
                ));

                if ($products_query->have_posts()) {
                    echo '<ul class="products columns-3 force-3-columns" style="padding-top: 10px">';

                    while ($products_query->have_posts()) {
                        $products_query->the_post();
                        wc_get_template_part('content', 'product');
                    }

                    echo '</ul>';
                    wp_reset_postdata();
                }
            } else {

                if (woocommerce_product_loop()) {

                    woocommerce_output_all_notices();

                    do_action('woocommerce_before_shop_loop');
                    woocommerce_product_loop_start();

                    if (wc_get_loop_prop('total')) {
                        while (have_posts()) {
                            the_post();
                            do_action('woocommerce_shop_loop');
                            wc_get_template_part('content', 'product');
                        }
                    }

                    woocommerce_product_loop_end();
                    do_action('woocommerce_after_shop_loop');
                } else {
                    do_action('woocommerce_no_products_found');
                }
            }
        } else {

            // Default shop page (not category)
            if (woocommerce_product_loop()) {

                woocommerce_output_all_notices();

                do_action('woocommerce_before_shop_loop');

                woocommerce_product_loop_start();

                if (wc_get_loop_prop('total')) {
                    while (have_posts()) {
                        the_post();
                        do_action('woocommerce_shop_loop');
                        wc_get_template_part('content', 'product');
                    }
                }

                woocommerce_product_loop_end();
                do_action('woocommerce_after_shop_loop');
            } else {
                do_action('woocommerce_no_products_found');
            }
        }

        do_action('woocommerce_after_main_content'); ?>

    </div>
</div>

<?php
if (is_product_category()) {
    $category = get_queried_object();
    if (isset($category->description) && ! empty($category->description)) {
        echo '<div class="hmaboutWrap catdescriptionWrap"><div class="container"> <div class="scrollBox">';
        echo wp_kses_post(wpautop($category->description)); // Outputs the description with safe HTML.
        echo '</div> </div></div>';
    }
} ?>

<div class="common-sec">
    <?php echo do_shortcode('[post type="content" id="696"]'); ?>
</div>

<div class="formWrap receiveWrap">
    <div class="container">
        <div class="bgBox">
            <div class="wp-block-group__inner-container">
                <?php echo do_shortcode('[post type="content" id="1209"]'); ?>
                <div class="row">
                    <div class="col-xl-7 col-lg-12 textBox">
                        <?php echo do_shortcode('[post type="content" id="813"]'); ?>
                    </div>
                    <div class="col-xl-5 col-lg-12 buttonBox">
                        <?php echo do_shortcode('[contact-form-7 id="9c3fa00" title="Sign-UP to Receive Form"]'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="formWrap questionWrap">
    <div class="container">
        <?php echo do_shortcode('[post type="content" id="825"]'); ?>
        <?php echo do_shortcode('[contact-form-7 id="1c1d715" title="Have Questions? We’re Here To Help Form"]'); ?>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        $(".woocommerce ul.products li .add_to_cart_button").addClass('btnbx');
    });
</script>

<?php get_footer('shop');
