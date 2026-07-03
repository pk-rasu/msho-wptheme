<?php
/**
 * WhiteKurti — Mini cart contents (inside cart drawer)
 */
defined('ABSPATH') || exit;
do_action('woocommerce_before_mini_cart');

if ( ! WC()->cart->is_empty() ) : ?>

<ul class="wk-mini-cart-list">
	<?php
	do_action('woocommerce_before_mini_cart_contents');
	foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
		$_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
		$product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
		if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) :
			$product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
			$img_id = $_product->get_image_id();
	?>
	<li class="wk-mini-cart-item <?php echo esc_attr(apply_filters('woocommerce_mini_cart_item_class','mini_cart_item',$cart_item,$cart_item_key)); ?>">
		<div class="wk-mini-cart-item__img">
			<?php if ($img_id) echo wp_get_attachment_image($img_id, [88,117], false, ['loading'=>'lazy']);
			else echo '<div class="wk-mini-cart-item__placeholder"></div>'; ?>
		</div>
		<div class="wk-mini-cart-item__body">
			<div class="wk-mini-cart-item__cat">
				<?php echo strip_tags(wc_get_product_category_list($product_id)); ?>
			</div>
			<a href="<?php echo $product_permalink ? esc_url($product_permalink) : '#'; ?>" class="wk-mini-cart-item__name">
				<?php echo wp_kses_post(apply_filters('woocommerce_cart_item_name',$_product->get_name(),$cart_item,$cart_item_key)); ?>
			</a>
			<?php echo wc_get_formatted_cart_item_data($cart_item); ?>
			<div class="wk-mini-cart-item__meta">
				<span class="wk-mini-cart-item__qty">Qty: <?php echo $cart_item['quantity']; ?></span>
				<span class="wk-mini-cart-item__price"><?php echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); ?></span>
			</div>
		</div>
		<a href="<?php echo esc_url(wc_get_cart_remove_url($cart_item_key)); ?>"
			class="wk-icon-btn wk-mini-cart-item__remove"
			aria-label="<?php esc_attr_e('Remove item','whitekurti'); ?>"
			data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
			<?php wk_icon('close',14); ?>
		</a>
	</li>
	<?php endif; endforeach;
	do_action('woocommerce_after_mini_cart_contents');
	?>
</ul>

<?php else : ?>

<div class="wk-mini-cart-empty">
	<?php wk_icon('bag', 40); ?>
	<p><?php echo esc_html( get_theme_mod( 'wk_text_empty_cart_title', 'Your cart is empty.' ) ); ?></p>
	<a href="<?php echo esc_url( get_permalink( wc_get_page_id('shop') ) ); ?>" class="wk-btn">
		<?php echo esc_html( get_theme_mod( 'wk_text_empty_cart_btn', 'Start Shopping' ) ); ?>
	</a>
</div>

<?php endif;
do_action('woocommerce_after_mini_cart');
