<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;

$estado_pol = $_GET['estado_pol'];
$ref_venta  = $_GET['ref_venta'];
if ( ! $order && $ref_venta ) {
	$order = new WC_Order( $ref_venta );
}

if ( $order ) : ?>

	<?php if ( in_array( $order->status, array( 'failed', 'cancelled' ) ) || $_GET['estado_pol'] == 5 || $_GET['estado_pol'] == 6 ) : ?>

		<?php if ( in_array( $order->status, array('cancelled') ) || $_GET['estado_pol'] == 5 ) : ?>
		<p><?php _e( 'Your order was cancelled.', 'woocommerce' ); ?></p>
		<?php else: ?>
		<p><?php _e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction.', 'woocommerce' ); ?></p>
		<?php endif; ?>

		<p><?php
			if ( is_user_logged_in() )
				_e( 'Please attempt your purchase again or go to your account page.', 'woocommerce' );
			else
				_e( 'Please attempt your purchase again.', 'woocommerce' );
		?></p>

		<p>
			<?php if ( in_array( $order->status, array('failed') ) || $_GET['estado_pol'] == 6 ) : ?>
			<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php _e( 'Pay', 'woocommerce' ) ?></a>
			<?php endif; ?>

			<?php if ( is_user_logged_in() ) : ?>
			<a href="<?php echo esc_url( get_permalink( woocommerce_get_page_id( 'myaccount' ) ) ); ?>" class="button pay"><?php _e( 'My Account', 'woocommerce' ); ?></a>
			<?php endif; ?>
		</p>

	<?php else : ?>

		<?php if ( in_array( $order->status, array('processing', 'completed') ) || $_GET['estado_pol'] == 4 ) : ?>
		<p><?php _e( 'Thank you. Your order has been received.', 'woocommerce' ); ?></p>
		<?php else: ?>
		<p><?php _e( 'Your order has been received and is now being processed. Your order details are shown below for your reference:', 'woocommerce' ); ?></p>
		<?php endif; ?>

		<ul class="order_details">
			<li class="order">
				<?php _e( 'Order:', 'woocommerce' ); ?>
				<strong><?php echo $order->get_order_number(); ?></strong>
			</li>
			<li class="date">
				<?php _e( 'Date:', 'woocommerce' ); ?>
				<strong><?php echo date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ); ?></strong>
			</li>
			<li class="total">
				<?php _e( 'Total:', 'woocommerce' ); ?>
				<strong><?php echo $order->get_formatted_order_total(); ?></strong>
			</li>
			<?php if ( $order->payment_method_title ) : ?>
			<li class="method">
				<?php _e( 'Payment method:', 'woocommerce' ); ?>
				<strong><?php echo $order->payment_method_title; ?></strong>
			</li>
			<?php endif; ?>
		</ul>
		<div class="clear"></div>

	<?php endif; ?>

	<?php do_action( 'woocommerce_thankyou_' . $order->payment_method, $order->id ); ?>
	<?php do_action( 'woocommerce_thankyou', $order->id ); ?>

<?php else : ?>

	<p><?php _e( 'Thank you. Your order has been received.', 'woocommerce' ); ?></p>

<?php endif; ?>
