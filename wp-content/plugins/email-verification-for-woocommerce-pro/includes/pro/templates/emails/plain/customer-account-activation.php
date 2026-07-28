<?php
/**
 * Customer email sent to the user with an activation link
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-account-activation.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 *
 * @version 2.6.7
 * @since   2.3.6
 */


defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer billing full name */
echo sprintf( esc_html__( 'Please click on the following link to verify your email on %s:', 'emails-verification-for-woocommerce' ), $site_title );

echo "\n\n";

//echo sprintf( esc_html__( 'Please %s to verify your email.', 'emails-verification-for-woocommerce' ), '<a href="' . $verification_url . '" target="_blank">click here</a>' ) . "\n\n";

echo esc_html( wp_strip_all_tags( wptexturize( $verification_url ) ) );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
