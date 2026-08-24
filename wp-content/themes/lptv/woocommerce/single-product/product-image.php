<?php

/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.7.0
 */

defined('ABSPATH') || exit;

// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
if (! function_exists('wc_get_gallery_image_html')) {
    return;
}

global $product;

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$post_thumbnail_id = get_post_thumbnail_id($product->get_id());

// print main image
$modelId        = $product->get_meta('_plate_template_id', true);
$plate_template_id = $product->get_meta('_plate_template_id', true);

$productsMetaImage = $product->get_meta('_plate_products_image', true);
$productImage   = '/wp-content/plugins/lptv-plates/images/' . strtolower($modelId) . '.gif';
if ($productsMetaImage) {
    $productImage   = '/wp-content/plugins/lptv-plates/images/' . $productsMetaImage;
}

$parent_post_id = $product->get_id();
$font_choose = $product->get_meta('_plate_font_choose', true);

// uncomment if you want to add attchment with image
// but remember that we hook the rank math opengrpah image in theme functions
// with filter rank_math/opengraph/facebook/image

//if ( $post_thumbnail_id === false || $post_thumbnail_id == 0 ) {
//	$attachment_id = media_sideload_image( get_site_url() . '/' . $productImage, $parent_post_id, $product->get_title(), 'id' );
//	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $product->get_title() );
//	set_post_thumbnail( $parent_post_id, $attachment_id );
//}

// print generated image
$generatedImage = '';

$columns           = apply_filters('woocommerce_product_thumbnails_columns', 4);
$post_thumbnail_id = $product->get_image_id();
$wrapper_classes   = apply_filters(
    'woocommerce_single_product_image_gallery_classes',
    array(
        'woocommerce-product-gallery',
        'woocommerce-product-gallery--' . ($post_thumbnail_id ? 'with-images' : 'without-images'),
        'woocommerce-product-gallery--columns-' . absint($columns),
        'images',
    )
);

$edecal = $product->get_meta('_plate_edecal', true);
$saftydecal = $product->get_meta('_plate_saftydecal', true);
$statedecal = $product->get_meta('_plate_statedecal', true);
$customFonts = $product->get_meta('_plate_font_choose', true) == 1;

$isDecalsExists = $saftydecal == 'Y' || $edecal == 'Y' || !empty($statedecal);

?>

<div class="<?php echo esc_attr(implode(' ', array_map('sanitize_html_class', $wrapper_classes))); ?>"
    data-columns="<?php echo esc_attr($columns); ?>" style="opacity: 0; transition: opacity .25s ease-in-out;">

    <?php if ($product->get_type() == 'lptvplate'): ?>
        <img src="<?= $productImage ?>" alt='<?= $product->get_name() ?>' />

                        <!-- noice about colored decals -->
                        <?php if($isDecalsExists): ?>
                            <div class="" style="color: red;font-size: 12px;margin:12px 0 0px 0;text-align: center;">
                                DECALS AND HEART WILL BE IN COLOR LIKE EXAMPLE IMAGE
                            </div>
                        <?php endif; ?>

        <?php if ($plate_template_id): ?>
            <img src="" id="demoImage" class="hidden"  alt="<?= $product->get_name() ?>"  />
        <?php endif; ?>

    <?php else: ?>

        <figure class="woocommerce-product-gallery__wrapper">
            <?php
            if ($post_thumbnail_id) {
                $html = wc_get_gallery_image_html($post_thumbnail_id, true);
            } else {
                $html = '<div class="woocommerce-product-gallery__image--placeholder">';
                $html .= sprintf('<img src="%s" alt="%s" class="wp-post-image" />', esc_url(wc_placeholder_img_src('woocommerce_single')), esc_html__('Awaiting product image', 'woocommerce'));
                $html .= '</div>';
            }

            echo apply_filters('woocommerce_single_product_image_thumbnail_html', $html, $post_thumbnail_id); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped

            do_action('woocommerce_product_thumbnails');
            ?>
        </figure>

    <?php endif; ?>
</div>

<?php if ($product->get_type() == 'lptvplate'): ?>
    <script>
        jQuery(function($) {

            let $demo = $('#demoImage');
            let $text1Input = $('#plateText1');
            let $text2Input = $('#plateText2');
            let $fontSelect = $('#plateFont');

            let modelId = '<?= $modelId ?>',
                text1 = '',
                text2 = '',
                font_field = '';

            function getUrl(modelId, text1, text2, font = '', edecalYear = '', sdecalYear = '') {
                function sanitizeText(text) {
                    return text
                        .replace(/>/g, 'TVGTSYMBOL')
                        .replace(/</g, 'TVLTSYMBOL')
                        .replace(/\|/g, 'TVPIPESYMBOL');
                }

                text1 = sanitizeText(text1);
                text2 = sanitizeText(text2);
                let query = {
                    'productId': modelId,
                    'text1': text1,
                    'text2': text2,
                    'font': font,
                    'font_choose': '<?= $font_choose ?>',
                    'edecal_year': edecalYear,
                    'sdecal_year': sdecalYear
                }
                let queryString = $.param(query);
                return '/wp-content/plugins/lptv-plates/includes/lpgenI.php?' + queryString;
                //return document.location.origin + `/wp-content/plugins/lptv-plates/includes/lpgenI.php?productId=${modelId}&text1=${encodeURIComponent(text1)}&text2=${encodeURIComponent(text2)}`;
            }

            $($demo).attr('src', getUrl(modelId, text1, text2, font_field));
            $demo.removeClass('hidden')

            // on change, update image
            $('#plateText1, #plateText2').on('keyup', function() {
                updateImageSrc();
            });

            $($fontSelect).change(function() {
                updateImageSrc();
            });

            function updateImageSrc() {
                text1 = $($text1Input).val()
                text2 = $($text2Input).val() || ''
                font_field = $($fontSelect).val() || ''
                let edecalYear = $('#_edecal_year').val() || ''
                let sdecalYear = $('#_sdecal_year').val() || ''
                $($demo).attr('src', getUrl(modelId, text1, text2, font_field, edecalYear, sdecalYear));
            }

            $(document).on('lptv:decalChanged', function() {
                updateImageSrc();
            });

        })
    </script>
<?php endif; ?>