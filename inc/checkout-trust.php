<?php
/**
 * WhiteKurti — Checkout Enhancements
 * Trust badges, UPI/payment icons, order bump, secure checkout signals
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Customizer ─────────────────────────────────────────────────────────────
add_action( 'customize_register', function( $wp_customize ) {
	$wp_customize->add_section( 'wk_checkout_trust', [
		'title'    => __( '🔒 Checkout Trust & Payment', 'whitekurti' ),
		'panel'    => 'wk_panel',
		'priority' => 44,
	] );
	$fields = [
		[ 'wk_cot_enabled',      'Show Trust Strip at Checkout',       'checkbox', true,  '' ],
		[ 'wk_cot_show_payment', 'Show Payment Method Icons',          'checkbox', true,  '' ],
		[ 'wk_cot_show_ssl',     'Show SSL Secure Badge',              'checkbox', true,  '' ],
		[ 'wk_cot_show_return',  'Show Return Policy Badge',           'checkbox', true,  '' ],
		[ 'wk_cot_show_cod',     'Show COD Badge',                     'checkbox', true,  '' ],
		[ 'wk_cot_custom_text',  'Custom Trust Message (optional)',    'text',     '100% Secure & Encrypted Checkout', '' ],
		[ 'wk_cot_upi_ids',      'Show UPI Payment Icons',             'checkbox', true,  'Shows GPay, PhonePe, Paytm, BHIM icons' ],
		[ 'wk_cot_show_bump',    'Show Order Bump (product upsell) at checkout', 'checkbox', false, 'Adds a product offer just before "Place Order"' ],
		[ 'wk_cot_bump_pid',     'Order Bump Product ID',              'number',   0,     'Enter the WooCommerce Product ID to offer' ],
		[ 'wk_cot_bump_msg',     'Order Bump Message',                 'text',     '⚡ Special offer — add to your order!', '' ],
		[ 'wk_cot_bump_discount','Order Bump Discount (%)',            'number',   10,    'Optional % discount shown in the bump' ],
	];
	$priority = 10;
	foreach ($fields as [$id,$label,$type,$default,$desc]) {
		$san = $type==='checkbox'?'rest_sanitize_boolean':'sanitize_text_field';
		$wp_customize->add_setting($id,['default'=>$default,'sanitize_callback'=>$san,'transport'=>'refresh']);
		$wp_customize->add_control($id,['label'=>$label,'description'=>$desc,'section'=>'wk_checkout_trust','type'=>$type,'priority'=>$priority++]);
	}
} );

// ── Trust strip HTML ───────────────────────────────────────────────────────
function wk_checkout_trust_strip() {
	if ( !get_theme_mod('wk_cot_enabled', true) ) return;
	$custom_text  = get_theme_mod('wk_cot_custom_text', '100% Secure & Encrypted Checkout');
	$show_payment = get_theme_mod('wk_cot_show_payment', true);
	$show_ssl     = get_theme_mod('wk_cot_show_ssl', true);
	$show_return  = get_theme_mod('wk_cot_show_return', true);
	$show_cod     = get_theme_mod('wk_cot_show_cod', true);
	$show_upi     = get_theme_mod('wk_cot_upi_ids', true);
	?>
	<div class="wk-checkout-trust">

		<?php if ($custom_text) : ?>
		<div class="wk-cot-secure">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
			<span><?php echo esc_html($custom_text); ?></span>
		</div>
		<?php endif; ?>

		<div class="wk-cot-badges">
			<?php if ($show_ssl) : ?>
			<div class="wk-cot-badge wk-cot-badge--ssl">
				<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
				<span>SSL Secured</span>
			</div>
			<?php endif; ?>
			<?php if ($show_cod) : ?>
			<div class="wk-cot-badge wk-cot-badge--cod">
				<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
				<span>COD Available</span>
			</div>
			<?php endif; ?>
			<?php if ($show_return) : ?>
			<div class="wk-cot-badge wk-cot-badge--return">
				<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.57"/></svg>
				<span>5-Day Returns</span>
			</div>
			<?php endif; ?>
		</div>

		<?php if ($show_payment) : ?>
		<div class="wk-cot-payments">
			<span class="wk-cot-payments__label">Pay with:</span>
			<div class="wk-cot-payment-icons">
				<!-- Card brands -->
				<span class="wk-cot-payicon wk-cot-payicon--visa">VISA</span>
				<span class="wk-cot-payicon wk-cot-payicon--mc">MC</span>
				<span class="wk-cot-payicon wk-cot-payicon--rupay">RuPay</span>
				<span class="wk-cot-payicon wk-cot-payicon--netbanking">Net Banking</span>
				<span class="wk-cot-payicon wk-cot-payicon--cod">COD</span>
				<?php if ($show_upi) : ?>
				<!-- UPI Apps -->
				<span class="wk-cot-payicon wk-cot-payicon--upi">UPI</span>
				<span class="wk-cot-payicon wk-cot-payicon--gpay">GPay</span>
				<span class="wk-cot-payicon wk-cot-payicon--phonepe">PhonePe</span>
				<span class="wk-cot-payicon wk-cot-payicon--paytm">Paytm</span>
				<span class="wk-cot-payicon wk-cot-payicon--bhim">BHIM</span>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>

	</div>
	<?php
}

// Hook trust strip before the payment button
add_action( 'woocommerce_review_order_before_payment', 'wk_checkout_trust_strip', 5 );
// Also before the order total section
add_action( 'woocommerce_checkout_before_order_review', 'wk_checkout_trust_strip', 99 );

// ── Order Bump ──────────────────────────────────────────────────────────────
function wk_checkout_order_bump() {
	if ( !get_theme_mod('wk_cot_show_bump', false) ) return;
	$bump_pid  = absint(get_theme_mod('wk_cot_bump_pid', 0));
	if (!$bump_pid) return;
	$product   = wc_get_product($bump_pid);
	if (!$product || !$product->is_visible() || !$product->is_in_stock()) return;

	// Don't show if already in cart
	if (WC()->cart && WC()->cart->find_product_in_cart(WC()->cart->generate_cart_id($bump_pid))) return;

	$bump_msg  = get_theme_mod('wk_cot_bump_msg', '⚡ Special offer — add to your order!');
	$discount  = absint(get_theme_mod('wk_cot_bump_discount', 10));
	$img_id    = $product->get_image_id();
	$img       = $img_id ? wp_get_attachment_image_url($img_id,'woocommerce_thumbnail') : wc_placeholder_img_src();
	$orig_price= (float) $product->get_regular_price();
	$bump_price= $discount > 0 ? $orig_price * (1 - $discount/100) : $orig_price;
	?>
	<div class="wk-order-bump" id="wk-order-bump" data-product-id="<?php echo $bump_pid; ?>">
		<div class="wk-order-bump__offer-tag"><?php echo esc_html($bump_msg); ?></div>
		<label class="wk-order-bump__body" for="wk-bump-checkbox">
			<input type="checkbox" id="wk-bump-checkbox" class="wk-bump-check" />
			<span class="wk-bump-custom-check">
				<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
			</span>
			<img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" class="wk-order-bump__img" />
			<div class="wk-order-bump__info">
				<strong class="wk-order-bump__name"><?php echo esc_html($product->get_name()); ?></strong>
				<div class="wk-order-bump__pricing">
					<?php if ($discount > 0) : ?>
					<span class="wk-order-bump__orig"><?php echo wc_price($orig_price); ?></span>
					<span class="wk-order-bump__sale"><?php echo wc_price($bump_price); ?></span>
					<span class="wk-order-bump__off">-<?php echo $discount; ?>%</span>
					<?php else : ?>
					<span class="wk-order-bump__sale"><?php echo wc_price($orig_price); ?></span>
					<?php endif; ?>
				</div>
				<p class="wk-order-bump__desc">
					Yes! Add this to my order
					<?php if ($discount) echo ' at ' . $discount . '% OFF'; ?>
				</p>
			</div>
		</label>
	</div>
	<?php
}
add_action('woocommerce_review_order_before_submit', 'wk_checkout_order_bump', 5);

// AJAX: Add order bump to cart before form submission
add_action('wp_ajax_wk_add_order_bump',        'wk_ajax_add_order_bump');
add_action('wp_ajax_nopriv_wk_add_order_bump', 'wk_ajax_add_order_bump');
function wk_ajax_add_order_bump() {
	check_ajax_referer('wk_bump_nonce','nonce');
	$pid      = absint($_POST['product_id']??0);
	$discount = absint(get_theme_mod('wk_cot_bump_discount', 10));
	if (!$pid) wp_send_json_error();
	$result = WC()->cart->add_to_cart($pid, 1);
	if ($result && $discount > 0) {
		// Apply custom price via session (requires hook below)
		WC()->session->set('wk_bump_product_'.$pid, ['pid'=>$pid,'discount'=>$discount]);
	}
	wp_send_json_success(['message'=>'Added to your order!']);
}

// Apply bump discount to cart item
add_filter('woocommerce_before_calculate_totals', function($cart) {
	if (is_admin() && !defined('DOING_AJAX')) return;
	foreach ($cart->get_cart() as $key => $item) {
		$pid = $item['product_id'];
		$bump_data = WC()->session->get('wk_bump_product_'.$pid);
		if (!$bump_data) continue;
		$discount   = (float)($bump_data['discount']??0)/100;
		$product    = wc_get_product($pid);
		if (!$product) continue;
		$orig_price = (float)$product->get_regular_price();
		$item['data']->set_price($orig_price * (1-$discount));
	}
}, 20);

// Pass nonce to JS
add_action('wp_enqueue_scripts', function() {
	if (is_checkout()) {
		wp_localize_script('wk-main','wk_bump_cfg',[
			'nonce' => wp_create_nonce('wk_bump_nonce'),
			'ajax'  => admin_url('admin-ajax.php'),
		]);
	}
}, 20);
