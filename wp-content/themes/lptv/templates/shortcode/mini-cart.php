<?php
/**
 * The template used for slider layout
 *
 */

if ( class_exists( 'WooCommerce' ) ) {
	
$wooItemsCount = is_object( WC()->cart ) ? WC()->cart->get_cart_contents_count() : ''; ?>

<div class="mini-cart">
    <div class="inrBox">
        <img src="<?php echo get_stylesheet_directory_uri();?>/images/header-minicart-icon.svg" alt="LPTV minicart icon">   
    </div>
    <sup class="misha-cart"><?php echo $wooItemsCount; ?></sup>
</div>
<div class="mini-cart-sidebar">
    <div class="close"><i class="far fa-times-circle"></i></div>
    <div class="mini-cart-sidebar-inner">
        <div class="widget_shopping_cart_content">            
            <?php if ( !is_null(WC()->cart) && !WC()->cart->is_empty() ) { woocommerce_mini_cart(); } ?>
            <?php  
                echo '<div class="wc-mini-custom-msg"><p class="custom_empty-message">The cart is empty. <br>Please <a href="' . esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ) . '">add to cart</a>.</p></div>';
            ?>
        </div>
    </div> 
</div>
<div class="mini-cart-overlay"></div>

<?php } ?>