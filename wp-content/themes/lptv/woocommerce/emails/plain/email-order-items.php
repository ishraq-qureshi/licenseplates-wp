<?php
/**
 * Email Order Items (Plain)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-order-items.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

foreach ( $items as $item_id => $item ) :
	$product = $item->get_product();

	if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
		continue;
	}

	// SKU.
	$sku = '';
	if ( is_object( $product ) ) {
		$sku = $product->get_sku();
	}

	?>
<?php echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) ); ?>

	<?php if ( $show_sku && $sku ) : ?>
		(<?php echo esc_html( $sku ); ?>)
	<?php endif; ?>

	<?php
	// allow other plugins to add additional product information here.
	do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, $plain_text );

	wc_display_item_meta(
		$item,
		array(
			'label_before' => '<strong>',
			'label_after'  => ':</strong> ',
		)
	);

	// display custom product instructions if available (admin only)
	if ( $sent_to_admin && $product && $product->get_meta( '_plate_products_instructions' ) ) {
		$instructions = $product->get_meta( '_plate_products_instructions' );
		if ( ! empty( $instructions ) ) {
			echo '<br/>Manufacturing instructions: <br/>' . wp_kses_post( $instructions ) . "\n";
		}
	}

	// allow other plugins to add additional product information here.
	do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, $plain_text );
	?>

<?php
$qty          = $item->get_quantity();
$refunded_qty = $order->get_qty_refunded_for_item( $item_id );

if ( $refunded_qty ) {
	$qty_display = '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * -1 ) ) . '</ins>';
} else {
	$qty_display = esc_html( $qty );
}
?>

	<?php echo wp_kses_post( apply_filters( 'woocommerce_email_order_item_quantity', $qty_display, $item ) ); ?>	<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>

<?php endforeach; ?>
