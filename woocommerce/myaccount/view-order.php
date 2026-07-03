<?php
/**
 * WhiteKurti — View Order (My Account)
 * Injects visual order status tracker at top
 * @version 9.3.0
 */
defined( 'ABSPATH' ) || exit;

$notes        = $order->get_customer_order_notes();
$show_tracker = true;
?>
<div class="wk-woo-view-order">

	<?php do_action( 'woocommerce_before_view_order_page' ); ?>

	<?php if ( $show_tracker ) :
		do_action( 'woocommerce_view_order', $order->get_id() );
	endif; ?>

	<?php if ( $order->has_status( 'failed' ) ) : ?>
	<p class="wk-order-note wk-order-note--failed">
		<?php echo wp_kses_post( apply_filters( 'woocommerce_my_account_my_orders_failed_message', sprintf( esc_html__( 'Your order cannot be paid. Please contact us if you need assistance. %1$sRetry payment%2$s', 'woocommerce' ), '<a href="' . esc_url( $order->get_checkout_payment_url() ) . '" class="wk-btn wk-btn--sm">', '</a>' ) ) ); ?>
	</p>
	<?php endif; ?>

	<?php do_action( 'woocommerce_view_order_before_order_table', $order ); ?>

	<section class="wk-order-details">
		<h2 class="wk-order-details__title">
			<?php printf( esc_html__( 'Order #%s', 'woocommerce' ), $order->get_order_number() ); ?>
			<span class="wk-order-status wk-order-status--<?php echo esc_attr( $order->get_status() ); ?>">
				<?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
			</span>
		</h2>
		<p class="wk-order-details__meta">
			<?php
			printf( esc_html__( 'Placed on %s', 'woocommerce' ),
				'<time datetime="' . esc_attr( $order->get_date_created()->date( 'c' ) ) . '">' .
				esc_html( wc_format_datetime( $order->get_date_created() ) ) . '</time>'
			);
			?>
		</p>
	</section>

	<?php
	do_action( 'woocommerce_view_order_content', $order );
	woocommerce_order_details_table( $order->get_id() );
	?>

	<?php if ( $notes ) : ?>
	<section class="wk-order-notes">
		<h3 class="wk-order-notes__title"><?php esc_html_e( 'Order Updates', 'woocommerce' ); ?></h3>
		<ol class="wk-order-notes__list">
			<?php foreach ( $notes as $note ) : ?>
			<li class="wk-order-note">
				<div class="wk-order-note__content"><?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?></div>
				<p class="wk-order-note__date"><?php echo esc_html( date_i18n( wc_date_format(), strtotime( $note->comment_date ) ) ); ?></p>
			</li>
			<?php endforeach; ?>
		</ol>
	</section>
	<?php endif; ?>

</div>
