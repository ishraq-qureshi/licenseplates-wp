<?php
/**
 * The template used for shortcode layout *
 */
$primary_logo = get_theme_mod( 'lptv_primary_logo' );
$site_title   = get_bloginfo( 'name' );

if ( $primary_logo ) {
    echo '<a href="' . esc_url( home_url( '/' ) ) . '">
            <img src="' . esc_url( $primary_logo ) . '" alt="Custom license plates from Licenseplates.tv">  
          </a>';
} else {
    echo '<a href="' . esc_url( home_url( '/' ) ) . '">
            <div>' . esc_html( $site_title ) . '</div>
          </a>';
}
?>
