<?php
/**
 * The template used for shortcode layout *
 */
$secondary_logo = get_theme_mod( 'lptv_secondary_logo' );
$site_title   = get_bloginfo( 'name' );

if ( $secondary_logo ) {
    echo '<a href="' . esc_url( home_url( '/' ) ) . '">
            <img src="' . esc_url( $secondary_logo ) . '" alt="Custom license plates from Licenseplates.tv">  
          </a>';
} else { 
    echo '<a href="' . esc_url( home_url( '/' ) ) . '">
            <div>' . esc_html( $site_description ) . '</div>
          </a>';
}
?>
