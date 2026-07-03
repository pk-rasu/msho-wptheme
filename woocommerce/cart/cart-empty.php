<?php
/**
 * Empty cart page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-empty.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked wc_empty_cart_message - 10
 */
do_action( 'woocommerce_cart_is_empty' );

if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
	<div class="wk-empty-cart-page">
		<div class="wk-empty-cart-page__icon">
			<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
				<line x1="3" y1="6" x2="21" y2="6"></line>
				<path d="M16 10a4 4 0 0 1-8 0"></path>
			</svg>
		</div>
		<h1 class="wk-empty-cart-page__title"><?php echo esc_html( get_theme_mod( 'wk_text_empty_cart_title', 'Your bag is empty' ) ); ?></h1>
		<p class="wk-empty-cart-page__desc"><?php echo esc_html( get_theme_mod( 'wk_text_empty_cart_desc', 'Explore our latest collections and find your new favorites.' ) ); ?></p>
		<p class="return-to-shop">
			<a class="button wc-backward wk-btn" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
				<?php
					/**
					 * Filter "Return To Shop" text.
					 *
					 * @since 4.6.0
					 * @param string $default_text Default text.
					 */
					echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', get_theme_mod( 'wk_text_empty_cart_btn', 'Start Shopping' ) ) );
				?>
			</a>
		</p>
	</div>
<?php endif; ?>
