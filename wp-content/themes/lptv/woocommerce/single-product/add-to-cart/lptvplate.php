<?php

/**
 * Simple product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/simple.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined('ABSPATH') || exit;

global $product;

if (! $product->is_purchasable()) {
    return;
}

$font1 = $product->get_meta('_plate_font1', true);
$maxChar1 = $product->get_meta('_plate_maxChar1', true);
$maxChar2 = $product->get_meta('_plate_maxChar2', true);
$symbols = $product->get_meta('_plate_symbols', true);
$template_id = $product->get_meta('_plate_template_id', true);
$edecal = $product->get_meta('_plate_edecal', true);
$saftydecal = $product->get_meta('_plate_saftydecal', true);
$statedecal = $product->get_meta('_plate_statedecal', true);
$customFonts = $product->get_meta('_plate_font_choose', true) == 1;

$isDecalsExists = $saftydecal == 'Y' || $edecal == 'Y' || trim((string) $statedecal) !== '';
// prepare symbols array
if (strlen($symbols) > 0) {
    $symbols = explode(',', $symbols);
}

echo wc_get_stock_html($product); // WPCS: XSS ok.

if ($product->is_in_stock()) : ?>


    <?php do_action('woocommerce_before_add_to_cart_form'); ?>
    <form class="cart"
        action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>"
        method="post" enctype='multipart/form-data'>

        <?php do_action('woocommerce_before_add_to_cart_button'); ?>
        <?php if ($template_id): ?>
            <div class="info1 info-box">
                <h4>Please enter your custom letters/numbers <br> into the box below</h4>
                <input type="text" class="textwriteinput <?php echo 'a' . $template_id; ?>" autocomple="off" id="plateText1" name="_plate_text1"
                    maxlength="<?php echo $maxChar1 ?>" maxlength="8">
                <?php if ($maxChar2 > 0): ?>
                    <input type="text" class="textwriteinput <?php echo 'a' . $template_id; ?>"" autocomple=" off" id="plateText2" name="_plate_text2"
                        maxlength="<?php echo $maxChar2 ?>">
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <!-- custom fonts -->
        <?php if ($customFonts): ?>
            <select id="plateFont" name="_plate_font">
                <option value="0" selected="selected">Choose font...</option>
                <option value="ag_alb">Albertus Medium</option>
                <option value="bnkgothm">Bank Gothic</option>
                <option value="brushscriptstd">Brush Script</option>
                <option value="copgothb">Copperplate Gothic</option>
                <option value="harlowsi">Harlow Solid Italic</option>
                <option value="verdana">Verdana</option>
                <option value="times">Times New Roman</option>
                <option value="lithograph">Lithograph</option>
                <option value="magnetob">Magneto</option>
                <option value="ag_zurchke">Zurich BLKEX BT</option>
            </select>
            <div class="info-box mt-1">
                <img src="/wp-content/plugins/lptv-plates/images/legends/legendag_customplate.png" />
            </div>
        <?php endif; ?>

        <!--  special symbols -->
        <?php if (is_array($symbols) && count($symbols) > 0): ?>
            <div class="info2 info-box">
                <h4>Click to select special character <br> <span>(some characters may show differently in text box)</span></h4>
                <div class="characterBox">
                    <?php foreach ($symbols as $symbol): ?>
                        <button type="button" onclick="appendSymbol(<?php echo esc_attr(json_encode($symbol)); ?>)" class=" <?php echo 'a' . $template_id; ?> <?php echo 'a' . strtoupper($template_id); ?>"><?php echo $symbol; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isDecalsExists): ?>
            <div class="info3 info-box">
                <h4>Select different date decals by clicking on desired<span>Choice below, state decal will be that of the plate that was chosen</span></h4>

                <!--  decals -->
                <?php
                include('edecal.php');
                if (trim((string) $statedecal) !== '') {
                    include('state-decals.php');
                }

                ?>
            </div>
        <?php endif; ?>




        <?php
        do_action('woocommerce_before_add_to_cart_quantity');

        woocommerce_quantity_input(
            array(
                'min_value'   => apply_filters('woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product),
                'max_value'   => apply_filters('woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product),
                'input_value' => isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : $product->get_min_purchase_quantity(),
                // WPCS: CSRF ok, input var ok.
            )
        );

        do_action('woocommerce_after_add_to_cart_quantity');
        ?>

        <input type="hidden" name="_decal_year" id="_decal_year" />
        <input type="hidden" name="_plate_holder_id" value="">

        <button type="submit" name="add-to-cart"
            value="<?php echo esc_attr($product->get_id()); ?>"
            class="single_add_to_cart_button button alt<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"><?php echo esc_html($product->single_add_to_cart_text()); ?></button>

        <?php do_action('woocommerce_after_add_to_cart_button'); ?>
    </form>

    <?php do_action('woocommerce_after_add_to_cart_form'); ?>

<?php endif; ?>

<!--Confirmation and validation-->
<script src="<?php echo get_template_directory_uri() ?>/js/swal/sweetalert2.all.min.js"></script>
<link rel="stylesheet" href="<?php echo get_template_directory_uri() ?>/js/swal/sweetalert2.min.css">

<script>
    let lastFocus = 'plateText1';
    // on each focus for plateText1 or plateText2 save to lastFocus
    jQuery('#plateText1, #plateText2').focus(function() {
        lastFocus = this.id
    })

    // change font
    function changeFont() {

    }

    jQuery(function($) {
        $("#plateHolderSelect").on("change", function() {
            $("input[name=_plate_holder_id]").val($(this).val());
        })
    });

    // append symbols and decals
    function appendSymbol(symbol) {
        console.log(symbol);
        // get element with current focus
        let plateText = jQuery(`#${lastFocus}`);
        let v = `${plateText.val()}${symbol}`;
        plateText.val(v);
        plateText.trigger('keyup')
        return false;
    }

    function setDecalYear(year) {
        jQuery('#_decal_year').val(year);
    }

    // alert
    jQuery(function($) {

        let proceeed = false;
        $('[name="add-to-cart"]').click(async function(e) {

            if (!proceeed) {

                 let plateInput = jQuery('#plateText1');

                if (plateInput.length === 0) {
                    return true;
                }

                e.preventDefault();
                let form = $(this).parents('form');

                let plateText1 = jQuery('#plateText1').val();
                let plateText2 = jQuery('#plateText2').val();

                if (plateText1 !== undefined && plateText1 == '') {
                    alert('You Must Enter A Value');
                    return false;
                }

                if (plateText2 !== undefined && plateText2 == '') {
                    alert('You Must Enter A Value 2');
                    return false;
                }

                let msg = "Please enter your letters, numbers and spaces with care - we manufacture whatever you enter. \n\n" +
                    " Because WWW.LICENSEPLATES.TV customize/personalize each license plate individually for you, we do not accept returns or issue refunds - WE MANUFACTURE WHATEVER YOU CUSTOMIZE. \n\n" +
                    "Our Aluminum Embossed Replica license plates are not marketed for official use or vehicle registration purposes. ";

                let result = await Swal.fire({
                    html: msg,
                    showCancelButton: true,
                    confirmButtonText: 'Confirm',
                    cancelButtonText: `Cancel`,
                });

                if (result.isConfirmed === true) {
                    proceeed = true;
                    $('[name="add-to-cart"]').click();
                }
                return true;
            } else {
                return true;
            }

        });

    });

</script>

<style type="text/css">
    @font-face {
        font-family: 'a<?php echo $template_id; ?>';
        src: url('/wp-content/plugins/lptv-plates/includes/fonts/truetype/<?php echo $font1; ?>.ttf');
    }

    .a<?php echo $template_id; ?> {
        font-family: 'a<?php echo $template_id; ?>' !important;
    }

    .productWrap .info2.info-box button.a<?php echo $template_id; ?> {
        font-family: 'a<?php echo $template_id; ?>' !important;
        font-weight: normal;
    }
</style>