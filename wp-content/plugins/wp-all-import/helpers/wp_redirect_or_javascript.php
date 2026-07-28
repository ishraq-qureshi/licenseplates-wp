<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! function_exists('wp_redirect_or_javascript')):
/**
 * For AJAX request outputs javascript specified, otherwise acts like wp_redirect 
 * @param string $location
 * @param string[optional] $javascript
 * @param int[optional] $status
 */
function wp_redirect_or_javascript($location, $javascript = NULL, $status = 302) {
	if (strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''))) === 'xmlhttprequest') {
		is_null($javascript) and $javascript = 'location.href="' . addslashes($location) . '";';
		echo '<script type="text/javascript">' . esc_js($javascript) . '</script>';
	} else {
		return wp_redirect($location, $status); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
	}
}
endif;