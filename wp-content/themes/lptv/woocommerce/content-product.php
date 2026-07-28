<?php
/**
 * The template for displaying product content within loops.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Get the product gallery images
$attachment_ids = $product->get_gallery_image_ids();
// Check if there are gallery images and get the first one
$gallery_image = '';
if ( $attachment_ids && isset( $attachment_ids[0] ) ) {
    $gallery_image_id = $attachment_ids[0];
    $gallery_image = wp_get_attachment_image( $gallery_image_id, 'woocommerce_thumbnail' );
}

// Ensure visibility.
if ( empty( $product ) || ! $product->is_visible() ) {
    return;
}
?>

<li <?php wc_product_class( '', $product ); ?> >
    <div class="inner">

    <?php
    /**
     * Hook: woocommerce_before_shop_loop_item.
     *
     * @hooked woocommerce_template_loop_product_link_open - 10
     */
    //do_action( 'woocommerce_before_shop_loop_item' );
  

    /**
     * Hook: woocommerce_before_shop_loop_item_title.
     *
     * @hooked woocommerce_show_product_loop_sale_flash - 10
     * @hooked woocommerce_template_loop_product_thumbnail - 10
     */
    //do_action( 'woocommerce_before_shop_loop_item_title' );

    // Display the product title as a link
    echo '<h2 class="product-title"><a href="' . get_permalink() . '">';
    the_title();
    echo '</a></h2>';


    global $product;

    if ( ! function_exists( 'custom_trim_words' ) ) {
        function custom_trim_words( $text, $word_limit = 20 ) {
            if ( ! is_string( $text ) ) {
                return ''; // Ensure the input is a string
            }

            $words = explode( ' ', $text ); // Split text into an array of words
            if ( count( $words ) > $word_limit ) {
                $text = implode( ' ', array_slice( $words, 0, $word_limit ) ) . '...'; // Trim to word limit
            }

            return $text;
        }
    }

    // Check if the product has a description
    if ( ! empty( $product->get_description() ) ) {
        $description = $product->get_description();
        $excerpt = custom_trim_words( wp_strip_all_tags( $description ), 20 ); // Limit to 20 words

        echo '<div class="woocommerce-product-excerpt">';
        echo wp_kses_post( wpautop( $excerpt ) ); // Display the trimmed description
        echo '</div>';
    }

    // Display the product featured image as a link
    echo '<div class="featured"><a href="' . get_permalink() . '"><div class="img-box">';
        echo '<div class="primary-image'; if ($gallery_image) {    echo ' has-secondary';} echo '">';
            echo woocommerce_get_product_thumbnail();
        echo '</div>';   
        if ( $gallery_image ) :
            echo '<div class="secondary-image">';
                echo $gallery_image; 
            echo '</div>';  
        endif;   
    echo '</div></a></div>'; 

    /**
     * Hook: woocommerce_after_shop_loop_item_title.
     *
     * @hooked woocommerce_template_loop_rating - 5
     * @hooked woocommerce_template_loop_price - 10
     */
    
   

    /**
     * Hook: woocommerce_after_shop_loop_item.
     *
     * @hooked woocommerce_template_loop_product_link_close - 5
     */
    //do_action( 'woocommerce_after_shop_loop_item' );
    ?>
    </div>
    <?php
    echo '<div class="processBox">';
        do_action( 'woocommerce_after_shop_loop_item_title' );    
        // Add view button linked to product page
        echo '<a href="' . get_permalink() . '" class="button view_button">Buy Now</a>';
    echo '</div>';
    ?>

    <script type="application/ld+json">
            {
                "@context": "http://schema.org/",
                "@type": "Product",
                "name": "<?php echo get_the_title(); ?>",
                "url": "<?php echo get_permalink(); ?>","image": [
                        "<?php $modelId = $product->get_meta( '_plate_template_id', true ); echo $productImage = get_site_url().'/wp-content/plugins/lptv-plates/images/' . $modelId . '.gif';?>"
                    ],"description": "<?php  echo plates_custom_excerpts(90000); ?>","sku": "<?php echo $product->get_sku(); ?>","brand": {
                    "@type": "Thing",
                    "name": "LICENSEPLATES.TV"             
                },
                "offers": [
                    {
                        "@type" : "Offer","sku": "<?php echo $product->get_sku(); ?>","availability" : "http://schema.org/InStock",
                        "price" : "<?php echo $product->get_price(); ?>",
                        "priceCurrency" : "<?php echo get_woocommerce_currency();?>",
                        "url" : "<?php echo get_permalink(); ?>"
                    }
                ]
            }
        </script>
</li>
