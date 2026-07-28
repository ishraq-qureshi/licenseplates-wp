<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined('ABSPATH') || exit;

global $product;
$font1 = $product->get_meta('_plate_font1', true);
$legendURL = '/wp-content/resources/images/legends/legend' . $font1 . '.png';
$filePath = WP_CONTENT_DIR . '/resources/images/legends/legend' . $font1 . '.png';

if (!file_exists($filePath)) {
    $legendURL = false;
}

// chekc if we need to show plate holders
// suppose we show it for custom plates, if not - need to tweak it
$plate_template_id = $product->get_meta('_plate_template_id', true);
$option2 = $product->get_meta('_plate_options_2', true);
$show_plate_holders = false;
$swiss_plate = false;
$plateHolders = [];

// option2 2 could be > 1 it means we need to show plate holders, if not set - hide it
if ($plate_template_id && ($option2 > 1)) {

    // pull products from category plate holders
    $plate_holders = get_posts(array(
        'post_type' => 'product',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => 'plate-holders',
            ),
        ),
    ));

    if (count($plate_holders) > 0) {
        $show_plate_holders = true;
    }

    // compile array of wooommerce products with category plate holders
    $plateHolders = array();
    foreach ($plate_holders as $plate_holder) {
        $p = wc_get_product($plate_holder->ID);
        $plateHolders[] = array(
            'id' => $plate_holder->ID,
            'title' => $plate_holder->post_title,
            'price' => $p->get_price(),
            'image' => get_the_post_thumbnail_url($plate_holder->ID, 'full'),
        );
    }

    // define Swiss-related terms to check in product name
    $swiss_terms = array('swiss', 'switz');

    $is_swiss_plate = function ($product_name) use ($swiss_terms) {
        $product_name = strtolower($product_name);
        foreach ($swiss_terms as $term) {
            if (strpos($product_name, $term) !== false) {
                return true;
            }
        }
        return false;
    };

    if ($is_swiss_plate($product->name)) {
        $swiss_plate = true;
        $plateHolders = array_filter($plateHolders, function ($plateHolder) {
            return strpos(strtolower($plateHolder['title']), 'swiss') !== false;
        });
    } else {
        $plateHolders = array_filter($plateHolders, function ($plateHolder) {
            return strpos(strtolower($plateHolder['title']), 'swiss') === false;
        });
    }
}


/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked woocommerce_output_all_notices - 10
 */
do_action('woocommerce_before_single_product');

if (post_password_required()) {
    echo get_the_password_form(); // WPCS: XSS ok.
    return;
} ?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class('', $product); ?>>

    <!--releaseWrap-->
    <div class="productWrap">
        <div class="container">
            <div class="bgimageBox">
                <div class="bg_image_overlay"></div>
                <!-- <h2 class="secTitle line">
                    <//?php 
                    $terms = get_the_terms( get_the_ID(), 'product_cat' );
                    if ( $terms && ! is_wp_error( $terms ) ) {
                        if ( ! empty( $terms ) ) {
                            echo $terms[0]->name;
                        } 
                    } ?>  
                </h2> -->
                <h2 class="topTitle">German License Plate Munich (Home Of Bmw)</h2>
                <h4>Issued from january 1994 with free state and date decals</h4>
                <p>Embossed with your custom number</p>
            </div>
            <div class="colorBox">
                <div class="row">
                    <div class="col-lg-4 col-sm-12 imgSlide">
						
                        <div class="gallaryBox">
                            <?php
                            /**
                             * Hook: woocommerce_before_single_product_summary.
                             *
                             * @hooked woocommerce_show_product_sale_flash - 10
                             * @hooked woocommerce_show_product_images - 20
                             */
                            do_action('woocommerce_before_single_product_summary');
                            ?>
                        </div>

                        <!-- add frame product here -->
                        <?php if ($show_plate_holders): ?>
                            <div class="plateHolderBox">
                                <div class="plateHolderInner">
                                    <div class="" style="border-bottom: 1px solid #ccc; padding-bottom: 5px;margin-bottom: 10px;">
                                        <span class=""><?php echo $swiss_plate ? 'Swiss Plate Holder' : 'Universal Plate Holder'; ?></span>
                                    </div>
                                    <div class="plateHolder" style="margin-bottom: 20px;">
                                        <?php if ($swiss_plate): ?>
                                            <img src="<?php echo $plateHolders[0]['image']; ?>" style="" alt="Swiss Plate Holder" />
                                        <?php else: ?>
                                            <img src="/wp-content/uploads/unversal.webp" style="" alt="Universal Plate Holder" />
                                        <?php endif; ?>

                                        <div class="" style="margin:10px 0 5px 0">ADD UNIVERSAL PLATEHOLDER</div>
                                        <div class="selectted">
                                            <select id="plateHolderSelect" name="plateHolderSelect">
                                                <option value="">None</option>
                                                <?php foreach ($plateHolders as $plateHolder) { ?>
                                                    <option value="<?php echo $plateHolder['id']; ?>"><?php echo $plateHolder['title']; ?> ( +<?php echo wc_price($plateHolder['price']); ?> )</option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($legendURL): ?>
                            <div class="fontBox">
                                <div class="authenticBpx">
                                    <div class="bgimageBox">
                                        <div class="bg_image_overlay"></div>
                                        <h3 class="fontTitle">Authentic Font Legend</h3>
                                    </div>

                                    <div class="imageBox">
                                        <!-- <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/authentic-font-legend.svg"> -->
                                        <img src="<?php echo $legendURL; ?>" style="width:100%;" alt="Legend" />
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div> <!-- gallaryBox -->

                    <div class="col-lg-8 col-sm-12 contentBox">
                        <div class="title-innr">
                            <div class="freeBox">
                                <?php echo do_shortcode('[post type="content" id="1912"]'); ?>
                            </div>
                        </div>

                        <div class="priceBox">
                            <?php if($product->get_meta('_plate_template_id', true) !== ""): ?><h2 class="productTitle">Customize your <span>plate</span></h2><?php endif; ?>
                            <?php
                            if ($product) {
                                if ($product->get_type() == "simple" && $product->is_purchasable() && $product->is_in_stock() && ! $product->is_sold_individually()) {
                                    $html = '<form action="' . esc_url($product->add_to_cart_url()) . '" class="simple_cart_form cart" method="post" enctype="multipart/form-data">';
                                    $html .= woocommerce_quantity_input(array(), $product, false);

                                    $html .= '<button type="submit" data-quantity="1" data-product_id="' . $product->get_id() . '" class="button product_type_simple add_to_cart_button ajax_add_to_cart buyBtn elementor-button">Add to Cart</button>';
                                    $html .= '</form>';

                                    $html .= '<script type="text/javascript">
                                        jQuery(function($) { 
                                            // handle quantity change
                                            $("form.simple_cart_form").on("change", "input.qty", function() { 
                                                $(this.form).find("[data-quantity]").attr("data-quantity", this.value); 
                                            });
                                            // remove added to cart message
                                            jQuery(document.body).on("adding_to_cart", function() { 
                                                jQuery("a.added_to_cart").remove(); 
                                            }); 
                                        });
                                    </script>';

                                    echo $html;
                                } else {
                                    woocommerce_template_single_add_to_cart();
                                }
                            } ?>

                            <h3><?php woocommerce_template_single_price(); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tabWrap descriptionWrap">
        <div class="container">
			
			<div class="woocommerce-tabs wc-tabs-wrapper">
				
				<ul class="tabs wc-tabs" role="tablist">
					<li role="presentation" class="description_tab active" id="tab-title-description">
						<a href="#tab-description" role="tab" aria-controls="tab-description" aria-selected="true" tabindex="0">Description</a>
					</li>
					<li role="presentation" class="additional_information_tab" id="tab-title-additional_information">
						<a href="#tab-additional_information" role="tab" aria-controls="tab-additional_information" aria-selected="false" tabindex="-1">Additional information</a>
					</li>
				</ul>
				
				<div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--description panel entry-content wc-tab" id="tab-description" role="tabpanel" aria-labelledby="tab-title-description" style="">
					<h2>Description</h2>
					<?php
					$product_id = $product->get_id();
					$full  = get_post_field( 'post_content', $product_id );
					if ( $full )  echo apply_filters( 'the_content', $full );
					
					/*
					$top_parent_ids  = wc_get_parent_cat_ids_at_level( $product->get_id(), 1 );
					$field_name      = 'select_cat_pro_desc';
					$want_value      = 'category_product_description_2'; // the stored radio value
					$expecteds       = (array) $want_value;              // normalize to array
					$match_top_id    = 0;
					$term_custom_desc = '';

					// 1) find a top parent whose radio equals the wanted value
					foreach ( $top_parent_ids as $top_id ) {
						$fo = get_field_object( $field_name, 'product_cat_' . $top_id );					
						if ( ! $fo ) { continue; }

						// read stored value robustly
						$stored = isset( $fo['value'] ) ? $fo['value'] : get_field( $field_name, 'product_cat_' . $top_id );
						if ( is_array( $stored ) && isset( $stored['value'] ) ) {
							$stored = $stored['value']; // Radio set to return Array
						}

						if ( $stored !== null && $stored !== '' && in_array( (string) $stored, array_map( 'strval', $expecteds ), true ) ) {
							$match_top_id = $top_id; // remember which term matched
							break;
						}
					}

					// 2) pick the description from the matched term, else fallback
					if ( $match_top_id ) {
						// radio matched your 'category_product_description_2' value
						$term_custom_desc = get_field( 'category_product_description_2', 'product_cat_' . $match_top_id );
					} else {
						// fallback: take the first available description_1 from the top parents
						foreach ( $top_parent_ids as $top_id ) {
							$term_custom_desc = get_field( 'category_product_description_1', 'product_cat_' . $top_id );
							if ( $term_custom_desc ) { break; }
						}
					}
												
					if ( $term_custom_desc ) {
						echo $term_custom_desc;
					} else {
						echo '<ul>
									<li>Exquisitely created from UV protected highly durable aluminum with an embossed border.</li>
									<li>Colors are a brilliant black background accented with your custom NAME in red to distinguish your car or truck from the rest!</li>
									<li>Supplied with mounting screws and matching screw hider cap covers for an attractive uniform finish.</li>
									<li>Weather resistant.</li>
									<li>Dimensions are 6 inches (height) x 12 inches (width) with four standard screw holes.</li>
									<li>Metric Dimensions are 152mm x 305mm.</li>
									<li>Free Shipping &amp; Handling to all U.S.A. addresses.</li>
								</ul>';
					} */ ?>
				</div>
				<div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--additional_information panel entry-content wc-tab" id="tab-additional_information" role="tabpanel" aria-labelledby="tab-title-additional_information" style="display: none;">
					<h2>Additional information</h2>
					<p>PRODUCT USE: All license plates marketed by Autogeardepot.com, Inc. and LICENSEPLATES.TV, are sold as novelty and not for official use items. Henceforth, none of our replica license plates may be used in lieu of county issued, state issued, country issued or officially (government) issued license plates. The license plate replicas manufactured and marketed by Autogeardepot.com, Inc and LICENSEPLATES.TV are similar but may not be identical to originals (officially issued) due to different materials, colors/hues and character type styles used. You are wholly responsible to ensure that the license plates purchased from this site will not be used in a way so as to violate county, state or country statutes. Autogeardepot.com, Inc, LICENSEPLATES.TV, our suppliers and licensors are not responsible for legal violations that may arise from the use of products marketed on this site.</p>
				</div>
			</div>
			
            <div class="row bottomBox">
                <div class="col-md-6 col-sm-6 col-12 leftBox">
                    <?php if ( $product && $product->get_sku() ) { ?>
						<p class="modelText">
							<span>Model:</span> <?php echo $product->get_sku(); ?>
						</p>
                    <?php } ?>
                </div>
                <div class="col-md-6 col-sm-6 col-12 rightBox">
                    <div class="btnBox">
                        <a class="dbtn" href="<?php echo get_permalink(wc_get_page_id('shop')); ?>">Return to Product List</a>
                    </div>
                </div>
            </div>
			
        </div>
    </div>

    <?php echo do_shortcode('[post type="content" id="696"]'); ?>

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
            <?php echo do_shortcode('[contact-form-7 id="1c1d715" title="Have Questions? We\'re Here To Help Form"]'); ?>
        </div>
    </div>

</div>