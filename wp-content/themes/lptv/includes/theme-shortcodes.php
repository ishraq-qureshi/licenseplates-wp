<?php
// nav
function nav_function() {
     ob_start();
     get_template_part( 'templates/shortcode/nav');
     return ob_get_clean();
}
add_shortcode('nav', 'nav_function');

// copyright-year
function copyrightyear_function() {
     ob_start();
     get_template_part( 'templates/shortcode/copyright-year');
     return ob_get_clean();
}
add_shortcode('copyrightyear', 'copyrightyear_function');

// logo
function logo_function() {
     ob_start();
     get_template_part( 'templates/shortcode/logo');
     return ob_get_clean();
}
add_shortcode('logo', 'logo_function');

// secondary-logo
function secondary_logo_function() {
     ob_start();
     get_template_part( 'templates/shortcode/secondary-logo');
     return ob_get_clean();  
}
add_shortcode('secondary-logo', 'secondary_logo_function');      
 
// page-title
function pagetitle_function() { 
    ob_start();
    get_template_part('templates/shortcode/page-title');
    return ob_get_clean(); 
}
add_shortcode('pagetitle', 'pagetitle_function');
 
// woocommerce related
// mini cart
function woo_minicart_function() { 
    ob_start();
    get_template_part('templates/shortcode/mini-cart');
    return ob_get_clean(); 
}
add_shortcode('minicart', 'woo_minicart_function');

// category based product ajax search
function woo_product_ajax_search_function() {
    ob_start();
    get_template_part('templates/shortcode/product-ajax-search');
    return ob_get_clean(); 
}
add_shortcode('product_ajax_search', 'woo_product_ajax_search_function');

// dropdown menu 1
function menu1_function() {
    ob_start();
    get_template_part('templates/shortcode/menu1');
    return ob_get_clean(); 
}
add_shortcode('menu1', 'menu1_function');

// dropdown menu 2
function menu2_function() {
    ob_start();
    get_template_part('templates/shortcode/menu2');
    return ob_get_clean(); 
}
add_shortcode('menu2', 'menu2_function');

// dropdown menu 3
function menu3_function() { 
    ob_start();
    get_template_part('templates/shortcode/menu3');
    return ob_get_clean(); 
}
add_shortcode('menu3', 'menu3_function'); 