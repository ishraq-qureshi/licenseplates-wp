<?php

namespace Searchanise\SmartWoocommerceSearch;

defined( 'ABSPATH' ) || exit;

echo '<div class="wrap">'
		. '<div class="snize-optin" id="snize_container">'
		. '<div class="title-header"><span class="se-logo"><h2>' . esc_html( Api::get_instance()->get_product_name() ) . '</h2></span></div>'
		. '<img width="100%" src = "'.esc_url( plugins_url(SE_BASE_DIR . '/assets/images/woocommerce_last_step.svg') ).'" />'
		. '<div class="inside">';
echo '<p class="slider-text">'
		. '<h2>' . esc_attr__( 'Connect Searchanise to your store', 'smart-search-for-woocommerce' ) . '</h2>'
		. esc_attr__( 'Searchanise needs your permission to connect this WooCommerce store to our service. By clicking “Connect”, you allow Searchanise to receive and process your store data, including admin email, store URL, and plugin information, in order to activate the service.', 'smart-search-for-woocommerce' )
		. '</p>';
echo '<p class="snize-optin-submit">'
		. '<a href="'.esc_url( get_dashboard_url() ).'" class="button button-secondary visibility-hidden">' . esc_attr__( 'Skip', 'smart-search-for-woocommerce' ) . '</a>'
		. '<a href="'.esc_url( Api::get_instance()->get_admin_url( 'accept_gddpr', true ).'&return_url='.urlencode(wp_get_referer()) ).'" class="button button-primary button-right">'. esc_attr__( 'Connect', 'smart-search-for-woocommerce' ) . '</a>'
		. '</p>';
echo '</div>'; // inside
echo '<div class="snize-optin-footer">'
	. '<a href="https://searchanise.io/privacy-policy/" target="_blank">' . esc_attr__( 'Privacy Policy', 'smart-search-for-woocommerce' ) . '</a>';
echo '</div></div></div>';
