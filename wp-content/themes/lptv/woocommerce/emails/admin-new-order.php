<?php
/**
 * Admin new order email - manufacturing work order layout.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/admin-new-order.php.
 *
 * @package WooCommerce\Templates\Emails\HTML
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', '', $email );

$brand_red    = '#ee2227';
$logo_url     = esc_url( home_url( '/wp-content/uploads/email-logo.png' ) );
$order_number = $order->get_order_number();
$order_date   = wc_string_to_datetime( $order->get_date_created() )->date_i18n( 'm/d/Y' );
?>

<table cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 0px; padding-bottom:5px; border: 0px;">
	<tr style="border: 0px;">
		<td align="left" style="padding-bottom: 0px; padding-left: 0px; border: 0px;">
			<img style="display:block; width:150px; height:auto;" src="<?php echo $logo_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="150px" style="display: block;" />
		</td>
	</tr>
	<tr>
		<td align="center" style="padding-bottom: 6px; padding-top: 10px; padding-left: 0px;" valign="bottom">
			<h1 style="margin: 0; font-size: 38px; font-weight: 800; color: #111111; text-align: center; line-height:100%; text-align: left;">
				<?php echo esc_html( sprintf( 'ORDER #%s', $order_number ) ); ?>
			</h1>
		</td>
		<td align="center" style="padding: 12px; padding-bottom: 6px; padding-top: 10px; padding-right:20px; text-align: right;" valign="bottom">
			<strong style="font-size: 29px; color: #111111;"><?php echo esc_html( $order_date ); ?></strong>
		</td>
	</tr>

</table>
<table cellpadding="0" cellspacing="0" width="100%" style=" border: 0px; margin-bottom: 5px;" style="margin-bottom:5px; border:0 !important; border-collapse:collapse; border-spacing:0;">
		<tbody style="border:0;">
			<tr style="border:0;">
		<td style="border-top: 2px solid #111111; padding: 0px;  mso-line-height-rule:exactly;"></td>
		<td style="border-top: 2px solid #111111; padding: 0px; mso-line-height-rule:exactly;"></td>
	</tr>
	</table>
<table cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 0px; border: 0px;">
	<tr>
		<td width="50%" valign="top" style="text-align: center; padding-bottom: 6px; padding-right: 10px; padding-left: 0px;">
			<strong style="display: block; padding-bottom: 10px; font-size:16px; font-weight:600;"><?php esc_html_e( 'BILLING ADDRESS', 'woocommerce' ); ?></strong>
			<div style="color: #111111; line-height:115%; font-size: 16px; font-weight:600; text-transform: uppercase;" align="left">
				<?php echo wp_kses_post( $order->get_formatted_billing_address( __( 'N/A', 'woocommerce' ) ) ); ?><br />
				<?php echo esc_html( $order->get_billing_email() ); ?>
			</div>
		</td>
	   <td style="border-left: 2px solid #111111; padding: 0px;" cellpadding=""></td>
		<td width="50%" valign="top" style="text-align: center; padding-left: 10px; padding-right: 0px;">
			<strong style="display: block; padding-bottom: 10px; font-size:16px; font-weight:600;"><?php esc_html_e( 'SHIPPING ADDRESS', 'woocommerce' ); ?></strong>
			<div style="color: #111111; line-height:115%; font-size: 16px; font-weight:600; text-transform: uppercase;" align="left">
				<?php echo wp_kses_post( $order->get_formatted_shipping_address( $order->get_formatted_billing_address( __( 'N/A', 'woocommerce' ) ) ) ); ?><br />
				<?php echo esc_html( $order->get_billing_email() ); ?>
			</div>
		</td>
	</tr>
</table>
<table cellpadding="0" cellspacing="0" width="100%" style=" border: 0px; margin-top: 5px;" style="margin-bottom:5px; border:0 !important; border-collapse:collapse; border-spacing:0;">
		<tbody style="border:0;">
			<tr style="border:0;">
		<td style="border-top: 2px solid #111111; padding: 0px; padding-bottom: 5px; mso-line-height-rule:exactly;"></td>
		<td style="border-top: 2px solid #111111; padding: 0px; padding-bottom: 5px; mso-line-height-rule:exactly;"></td>
	</tr>
		</tbody>
	</table>
<?php
foreach ( $order->get_items() as $item_id => $item ) :
	if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
		continue;
	}

	$product = $item->get_product();
	$sku     = $product ? $product->get_sku() : '';
	
	$image   = $product ? apply_filters( 'woocommerce_order_item_thumbnail', $product->get_image( array( 350, 350 ) ), $item ) : '';
	// force the plate image to fill its column width instead of showing at its natural pixel size
	$image   = str_replace( '<img ', '<img style="width:100%;height:auto;display:block;" ', $image );

	$text1 = trim( (string) $item->get_meta( '_plate_text1' ) );
	$text2 = trim( (string) $item->get_meta( '_plate_text2' ) );

	$instructions_raw  = $product ? (string) $product->get_meta( '_plate_products_instructions', true ) : '';
	$instructions_text = function_exists( 'lptvplate_instructions_to_plain_text' )
		? lptvplate_instructions_to_plain_text( $instructions_raw )
		: trim( wp_strip_all_tags( $instructions_raw ) );

	$font = '';
	if ( $product ) {
		$font_choose = $product->get_meta( '_plate_font_choose', true );
		$font        = ( '1' === (string) $font_choose )
			? $item->get_meta( '_plate_font' )
			: $product->get_meta( '_plate_font1', true );
	}
	?>
	<table cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 5px; border: none; padding-bottom: 10px; padding-top: 5px;  border-collapse:collapse; border-spacing:0;">
		<tr style="border:0;">
			<td width="55%" valign="top" style="padding:0px; padding-right: 16px; font-weight:600; ">
				<?php echo wp_kses_post( $image ); ?>
				<p style="margin: 10px 0 0; font-size:15px; line-height:120%; text-transform: uppercase; border:0;">
					<?php echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) ); ?>
					<?php if ( $sku ) : ?>
						<span style="color: <?php echo esc_attr( $brand_red ); ?>;">(#<?php echo esc_html( $sku ); ?>)</span>
					<?php endif; ?>
					<br />
					<?php if ( '' !== $text1 ) : ?>
						<?php esc_html_e( 'TEXT1:', 'woocommerce' ); ?> <span style="text-transform: none;"> <?php echo esc_html( $text1 ); ?> </span><br />
					<?php endif; ?>
					<?php if ( '' !== $text2 ) : ?>
						<?php esc_html_e( 'TEXT2:', 'woocommerce' ); ?> <span style="text-transform: none;"><?php echo esc_html( $text2 ); ?></span><br />
					<?php endif; ?>
									<?php if ( '' !== $instructions_text ) : ?>
					<strong style="display: block; "><span style="display:block;"><?php esc_html_e( 'MANUFACTURING INSTRUCTIONS:', 'woocommerce' ); ?></span></strong>
					<span style="font-weight: 400; text-transform: uppercase; color: <?php echo esc_attr( $brand_red ); ?>;"><?php echo esc_html( $instructions_text ); ?></span>
				<?php endif; ?>

				</p>
			</td>
			<td width="45%" valign="top" style="padding:0px; text-align: right; padding-left: 15px;">
				<div style="margin-bottom: 0px; text-align: center">
					<span style="font-size: 40px; font-weight: bold; color: #111111; line-height: 1.1"><?php esc_html_e( 'QTY', 'woocommerce' ); ?></span><br />
					<span style="font-size: 115px; line-height: 0.7; font-weight: 800; color: <?php echo esc_attr( $brand_red ); ?>;"><?php echo esc_html( $item->get_quantity() ); ?></span>

				</div>
				<!-- <?php if ( $font ) : ?>
					<p><strong style="display: block; margin-top: 8px; margin-bottom: 4px; font-size:12px; line-height:120%;"><?php esc_html_e( 'FONT TYPE:', 'woocommerce' ); ?></strong>
					<div style="font-size:12px; line-height:120%;"><?php echo esc_html( strtoupper( $font ) ); ?></div></p>
				<?php endif; ?> -->
			</td>
		</tr>
	</table>

<?php endforeach; ?>

<?php if ( $order->get_customer_note() ) : ?>
	<div style="margin: 0 0 16px;font-size: 16px;margin-bottom: 5px;line-height: 1.1em; margin-top: 10px;">
		<p style="margin-bottom: 5px;"><strong style="display: block; margin-bottom: 4px;"><?php esc_html_e( 'SPECIAL INSTRUCTIONS:', 'woocommerce' ); ?></strong></p>
		<p><span style="font-size: 16px;font-weight: 600; color: <?php echo esc_attr( $brand_red ); ?>;"><?php echo wp_kses_post( nl2br( esc_html( $order->get_customer_note() ) ) ); ?></span></p>
</div>
<?php endif; ?>


<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
