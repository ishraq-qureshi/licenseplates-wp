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

<table cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 0px; padding-bottom:0px;">
	<tr>
		<td align="left" style="padding-bottom: 0px;">
			<img src="<?php echo $logo_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="150px" style="display: block;" />
		</td>
	</tr>
	<tr>
		<td align="center" style="padding-bottom: 6px; padding-top: 10px;">
			<h1 style="margin: 0; font-size: 44px; font-weight: 800; color: #111111; text-align: center; line-height:100%;">
				<?php echo esc_html( sprintf( 'ORDER #%s', $order_number ) ); ?>
			</h1>
		</td>
	</tr>
	<tr>
		<td align="center" style="padding: 0px;  padding-bottom: 0px;">
			<strong style="font-size: 29px; color: #111111;"><?php echo esc_html( $order_date ); ?></strong>
		</td>
	</tr>
	<tr>
		<td style="border-top: 2px solid #111111; padding: 0px;"></td>
	</tr>
</table>

<table cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 30px;">
	<tr>
		<td width="50%" valign="top" style="text-align: center; padding-bottom: 6px; padding-right: 10px;">
			<strong style="display: block; margin-bottom: 0px; font-size:12px; font-weight:600;"><?php esc_html_e( 'BILLING ADDRESS', 'woocommerce' ); ?></strong>
			<div style="color: #111111; line-height:115%; font-size: 13px; font-weight:600;" align="left">
				<?php echo wp_kses_post( $order->get_formatted_billing_address( __( 'N/A', 'woocommerce' ) ) ); ?><br />
				<?php echo esc_html( $order->get_billing_email() ); ?>
			</div>
		</td>
		<td width="50%" valign="top" style="text-align: center; padding-left: 10px;">
			<strong style="display: block; margin-bottom: 0px; font-size:12px; font-weight:600;"><?php esc_html_e( 'SHIPPING ADDRESS', 'woocommerce' ); ?></strong>
			<div style="color: #111111; line-height:115%; font-size: 13px; font-weight:600;" align="left">
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

	<table cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 25px; border-bottom: 1px solid #dddddd; padding-bottom: 20px;">
		<tr>
			<td width="55%" valign="top" style="padding-right: 15px; font-weight:600;">
				<?php echo wp_kses_post( $image ); ?>
				<p style="margin: 10px 0 0; font-size:12px; line-height:120%;">
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
				<div style="margin-bottom: 10px; text-align: center">
					<span style="font-size: 30px; font-weight: bold; color: #111111; line-height: 1.1"><?php esc_html_e( 'QTY x PRICE', 'woocommerce' ); ?></span><br />
					<span style="font-size: 100px; line-height: 1; font-weight: 800; color: <?php echo esc_attr( $brand_red ); ?>;"><?php echo esc_html( $item->get_quantity() ); ?></span>
					<span style="line-height: 1;font-weight: 800;color: #D9232E;font-size: 19px;"> x <?php echo wp_kses_post( wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?>
</span>
				</div>
				<?php if ( '' !== $instructions_text ) : ?>
					<p style="font-size: 11px;margin: 0 0;text-align: left;"><strong style="display: block; font-size:11px; line-height:1;"><?php esc_html_e( 'MANUFACTURING INSTRUCTIONS:', 'woocommerce' ); ?></strong>
					<?php echo esc_html( $instructions_text ); ?></p>
				<?php endif; ?>
				<!-- <?php if ( $font ) : ?>
					<p><strong style="display: block; margin-top: 8px; margin-bottom: 4px; font-size:12px; line-height:120%;"><?php esc_html_e( 'FONT TYPE:', 'woocommerce' ); ?></strong>
					<div style="font-size:12px; line-height:120%;"><?php echo esc_html( strtoupper( $font ) ); ?></div></p>
				<?php endif; ?> -->
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
			<th class="td" style=" padding-left: 2px; padding-right: 2px; font-size:12px; line-height: 110%; font-weight: 600;"><?php esc_html_e( 'SUBTOTAL', 'woocommerce' ); ?></th>
			<th class="td" style=" padding-left: 2px; padding-right: 2px; font-size:12px; line-height: 110%; font-weight: 600;"><?php esc_html_e( 'SALES TAX', 'woocommerce' ); ?></th>
			<th class="td" style=" padding-left: 2px; padding-right: 2px; font-size:12px; line-height: 110%; font-weight: 600;"><?php esc_html_e( 'SHIPPING', 'woocommerce' ); ?></th>
			<th class="td" style=" padding-left: 2px; padding-right: 2px; font-size:12px; line-height: 110%; font-weight: 600;"><?php esc_html_e( 'DISCOUNT', 'woocommerce' ); ?></th>
			<th class="td" style=" padding-left: 2px; padding-right: 2px; font-size:12px; line-height: 110%; font-weight: 600;"><?php esc_html_e( 'PAYMENT METHOD', 'woocommerce' ); ?></th>
			<th class="td" style=" padding-left: 2px; padding-right: 2px; font-size:12px; line-height: 110%; font-weight: 600;"><?php esc_html_e( 'TOTAL', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="td" style=" padding-left: 2px; padding-right: 2px; font-size:12px; line-height: 110%; font-weight: 600;"><?php echo wp_kses_post( wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ) ); ?></td>
			<td class="td" style=" padding-left: 2px; padding-right: 2px; font-size:12px; line-height: 110%; font-weight: 600;">
				<?php
				if ( $order->get_total_tax() > 0 ) {
					echo wp_kses_post( wc_price( $order->get_total_tax(), array( 'currency' => $order->get_currency() ) ) );
				} else {
					esc_html_e( 'N/A', 'woocommerce' );
				}
				?>
			</td>
			<td class="td" style=" padding-left: 2px; padding-right: 2px; font-size:12px; line-height: 110%; font-weight: 600;">
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
			<td class="td" style=" padding-left: 2px; padding-right: 2px; font-size:12px; line-height: 110%; font-weight: 600;">
				<?php
				if ( $order->get_discount_total() > 0 ) {
					echo wp_kses_post( wc_price( $order->get_discount_total(), array( 'currency' => $order->get_currency() ) ) );
				} else {
					esc_html_e( 'N/A', 'woocommerce' );
				}
				?>
			</td>
			<td class="td" style=" padding-left: 2px; padding-right: 2px; font-size:12px; line-height: 110%; font-weight: 600;"><?php echo esc_html( $order->get_payment_method_title() ?: __( 'N/A', 'woocommerce' ) ); ?></td>
			<td class="td" style=" padding-left: 2px; padding-right: 2px; font-size:12px; line-height: 110%; font-weight: 600;"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
		</tr>
	</tbody>
</table>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
