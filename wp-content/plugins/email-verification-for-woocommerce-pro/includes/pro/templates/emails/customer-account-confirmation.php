<?php
/**
 * Customer email when customer confirmed their account.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-account-confirmation.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 *
 * @version 2.6.3
 * @since   2.3.6
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
// Main content from "Email > Confirmation Email > Email content".
echo wp_kses_post( wpautop( wptexturize( $main_content ) ) );
?>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
