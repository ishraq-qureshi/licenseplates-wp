<?php
/**
 * Email Order Items (Plain)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-order-items.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 4.7.0
 */

defined('ABSPATH') || exit;

$image = get_term_meta($category->term_id, 'categories_image', true);
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
    $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
    $image = wp_get_attachment_url($thumbnail_id);
} ?>

<li class="product-category 1">
    <a href="<?php echo esc_url(get_term_link($category)); ?>">
        <?php if ($image) : ?>
            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($category->name); ?>" />
        <?php else : ?>
            <img src="<?php echo wc_placeholder_img_src(); ?>" alt="<?php echo esc_attr($category->name); ?>" />
        <?php endif; ?>

        <h2><?php echo esc_html($category->name); ?></h2>
    </a>
</li>