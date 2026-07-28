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

$brand_red    = '#D9232E';
$logo_url     = esc_url( home_url( '/wp-content/uploads/email-logo.png' ) );
$order_number = $order->get_order_number();
$order_date   = wc_string_to_datetime( $order->get_date_created() )->date_i18n( 'm/d/Y' );
?>

<table cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px;">
	<tr>
		<td align="left" style="padding-bottom: 20px;">
			<img src="<?php echo $logo_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="220" style="display: block;" />
		</td>
	</tr>
	<tr>
		<td align="center" style="padding-bottom: 6px;">
			<h1 style="margin: 0; font-size: 32px; font-weight: 800; color: #111111; text-align: center;">
				<?php echo esc_html( sprintf( 'ORDER #%s', $order_number ) ); ?>
			</h1>
		</td>
	</tr>
	<tr>
		<td align="center" style="padding-bottom: 20px;">
			<strong style="font-size: 15px; color: #111111;"><?php echo esc_html( $order_date ); ?></strong>
		</td>
	</tr>
	<tr>
		<td style="border-top: 2px solid #111111;"></td>
	</tr>
</table>

<table cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 30px;">
	<tr>
		<td width="50%" valign="top" style="text-align: center; padding-right: 10px;">
			<strong style="display: block; margin-bottom: 8px;"><?php esc_html_e( 'BILLING ADDRESS', 'woocommerce' ); ?></strong>
			<div style="color: #111111;">
				<?php echo wp_kses_post( $order->get_formatted_billing_address( __( 'N/A', 'woocommerce' ) ) ); ?><br />
				<?php echo esc_html( $order->get_billing_email() ); ?>
			</div>
		</td>
		<td width="50%" valign="top" style="text-align: center; padding-left: 10px;">
			<strong style="display: block; margin-bottom: 8px;"><?php esc_html_e( 'SHIPPING ADDRESS', 'woocommerce' ); ?></strong>
			<div style="color: #111111;">
				<?php echo wp_kses_post( $order->get_formatted_shipping_address( $order->get_formatted_billing_address( __( 'N/A', 'woocommerce' ) ) ) ); ?><br />
				<?php echo esc_html( $order->get_billing_email() ); ?>
			</div>
		</td>
	</tr>
</table>

<?php
foreach ( $order->get_items() as $item_id => $item ) :
	if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
		continue;
	}

	$product = $item->get_product();
	$sku     = $product ? $product->get_sku() : '';
	$image   = $product ? apply_filters( 'woocommerce_order_item_thumbnail', $product->get_image( array( 350, 350 ) ), $item ) : '';

	$text1 = trim( (string) $item->get_meta( '_plate_text1' ) );
	$text2 = trim( (string) $item->get_meta( '_plate_text2' ) );

	$instructions      = $product ? (string) $product->get_meta( '_plate_products_instructions', true ) : '';
	$instructions_text = trim( wp_strip_all_tags( html_entity_decode( $instructions, ENT_QUOTES, 'UTF-8' ) ) );
	?>

	<table cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 25px; border-bottom: 1px solid #dddddd; padding-bottom: 20px;">
		<tr>
			<td width="55%" valign="top" style="padding-right: 15px;">
				<?php echo wp_kses_post( $image ); ?>
				<p style="margin: 10px 0 0;">
					<?php echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) ); ?>
					<?php if ( $sku ) : ?>
						<span style="color: <?php echo esc_attr( $brand_red ); ?>;">(#<?php echo esc_html( $sku ); ?>)</span>
					<?php endif; ?>
					<br />
					<?php if ( '' !== $text1 ) : ?>
						<?php esc_html_e( 'TEXT1:', 'woocommerce' ); ?> <?php echo esc_html( $text1 ); ?><br />
					<?php endif; ?>
					<?php if ( '' !== $text2 ) : ?>
						<?php esc_html_e( 'TEXT2:', 'woocommerce' ); ?> <?php echo esc_html( $text2 ); ?><br />
					<?php endif; ?>
				</p>
			</td>
			<td width="45%" valign="top" style="text-align: right; padding-left: 15px;">
				<div style="margin-bottom: 10px;">
					<span style="font-size: 20px; font-weight: bold; color: #111111;"><?php esc_html_e( 'QTY:', 'woocommerce' ); ?></span><br />
					<span style="font-size: 48px; font-weight: 800; color: <?php echo esc_attr( $brand_red ); ?>; line-height: 1.1;"><?php echo esc_html( $item->get_quantity() ); ?></span>
				</div>
				<?php if ( '' !== $instructions_text ) : ?>
					<strong style="display: block; margin-bottom: 4px;"><?php esc_html_e( 'MANUFACTURING INSTRUCTIONS:', 'woocommerce' ); ?></strong>
					<div><?php echo wp_kses_post( $instructions ); ?></div>
				<?php endif; ?>
			</td>
		</tr>
	</table>

<?php endforeach; ?>

<?php if ( $order->get_customer_note() ) : ?>
	<p style="margin-bottom: 25px;">
		<strong style="display: block; margin-bottom: 4px;"><?php esc_html_e( 'CUSTOMER NOTES:', 'woocommerce' ); ?></strong>
		<span style="color: <?php echo esc_attr( $brand_red ); ?>;"><?php echo wp_kses_post( nl2br( esc_html( $order->get_customer_note() ) ) ); ?></span>
	</p>
<?php endif; ?>

<table class="td" cellspacing="0" cellpadding="8" width="100%" border="1" style="border-collapse: collapse; text-align: center; margin-bottom: 20px;">
	<thead>
		<tr>
			<th class="td"><?php esc_html_e( 'SUBTOTAL', 'woocommerce' ); ?></th>
			<th class="td"><?php esc_html_e( 'SALES TAX', 'woocommerce' ); ?></th>
			<th class="td"><?php esc_html_e( 'SHIPPING', 'woocommerce' ); ?></th>
			<th class="td"><?php esc_html_e( 'DISCOUNT', 'woocommerce' ); ?></th>
			<th class="td"><?php esc_html_e( 'PAYMENT METHOD', 'woocommerce' ); ?></th>
			<th class="td"><?php esc_html_e( 'TOTAL', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="td"><?php echo wp_kses_post( wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ) ); ?></td>
			<td class="td">
				<?php
				if ( $order->get_total_tax() > 0 ) {
					echo wp_kses_post( wc_price( $order->get_total_tax(), array( 'currency' => $order->get_currency() ) ) );
				} else {
					esc_html_e( 'N/A', 'woocommerce' );
				}
				?>
			</td>
			<td class="td">
				<?php
				if ( (float) $order->get_shipping_total() <= 0 && $order->get_shipping_method() ) {
					esc_html_e( 'FREE SHIPPING', 'woocommerce' );
				} elseif ( $order->get_shipping_method() ) {
					echo wp_kses_post( wc_price( $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ) );
				} else {
					esc_html_e( 'N/A', 'woocommerce' );
				}
				?>
			</td>
			<td class="td">
				<?php
				if ( $order->get_discount_total() > 0 ) {
					echo wp_kses_post( wc_price( $order->get_discount_total(), array( 'currency' => $order->get_currency() ) ) );
				} else {
					esc_html_e( 'N/A', 'woocommerce' );
				}
				?>
			</td>
			<td class="td"><?php echo esc_html( $order->get_payment_method_title() ?: __( 'N/A', 'woocommerce' ) ); ?></td>
			<td class="td"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
		</tr>
	</tbody>
</table>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
