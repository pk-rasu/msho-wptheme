<?php
/**
 * WhiteKurti — Cart Enhancements
 * 1. Free Shipping Progress Bar
 * 2. Cart Upsells / Recommendations
 * 3. Premium Coupon Code UI
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ═══════════════════════════════════════════════════════════════
// 1. FREE SHIPPING PROGRESS BAR
// ═══════════════════════════════════════════════════════════════

// Customizer settings
add_action( 'customize_register', function( $wp_customize ) {
	$wp_customize->add_section( 'wk_free_ship_bar', [
		'title'    => __( '🚚 Free Shipping Bar', 'whitekurti' ),
		'panel'    => 'wk_panel',
		'priority' => 42,
	] );
	$fields = [
		[ 'wk_fsb_enabled',        'Enable Free Shipping Progress Bar',   'checkbox', true,   '' ],
		[ 'wk_fsb_threshold',      'Free Shipping Threshold (₹)',         'number',   0,      '0 = always free. Set to match your WooCommerce free shipping rule.' ],
		[ 'wk_fsb_msg_progress',   'Progress Message',                    'text',     'Add {amount} more for FREE shipping!', 'Use {amount} for remaining amount.' ],
		[ 'wk_fsb_msg_achieved',   'Achieved Message',                    'text',     '🎉 You\'ve unlocked FREE shipping!', '' ],
		[ 'wk_fsb_bar_color',      'Progress Bar Color',                  'text',     '#166534', '' ],
		[ 'wk_fsb_bg_color',       'Bar Background Color',                'text',     '#f0fdf4', '' ],
		[ 'wk_fsb_show_cart',      'Show on Cart page',                   'checkbox', true,  '' ],
		[ 'wk_fsb_show_minicart',  'Show in Mini Cart sidebar',           'checkbox', true,  '' ],
		[ 'wk_fsb_show_checkout',  'Show on Checkout page',               'checkbox', false, '' ],
	];
	foreach ( $fields as [$id,$label,$type,$default,$desc] ) {
		$san = $type==='checkbox' ? 'rest_sanitize_boolean' : 'sanitize_text_field';
		$wp_customize->add_setting($id, ['default'=>$default,'sanitize_callback'=>$san,'transport'=>'refresh']);
		$ctrl = ['label'=>$label,'description'=>$desc,'section'=>'wk_free_ship_bar','type'=>$type];
		if ($type==='text' && (substr($id, -6) === '_color')) $ctrl['type'] = 'text'; // Could use color control
		$wp_customize->add_control($id, $ctrl);
	}
} );

// Get free shipping data
function wk_get_free_ship_data() {
	$threshold   = (float) get_theme_mod( 'wk_fsb_threshold', 0 );
	$msg_prog    = get_theme_mod( 'wk_fsb_msg_progress', 'Add {amount} more for FREE shipping!' );
	$msg_done    = get_theme_mod( 'wk_fsb_msg_achieved', '🎉 You\'ve unlocked FREE shipping!' );
	$bar_color   = get_theme_mod( 'wk_fsb_bar_color', '#166534' );
	$bg_color    = get_theme_mod( 'wk_fsb_bg_color', '#f0fdf4' );

	$cart_total  = WC()->cart ? (float) WC()->cart->get_displayed_subtotal() : 0;

	if ( $threshold <= 0 ) {
		return [
			'enabled'    => true,
			'achieved'   => true,
			'percent'    => 100,
			'message'    => $msg_done,
			'bar_color'  => $bar_color,
			'bg_color'   => $bg_color,
			'threshold'  => 0,
			'remaining'  => 0,
		];
	}

	$remaining = max( 0, $threshold - $cart_total );
	$percent   = min( 100, round( ($cart_total / $threshold) * 100 ) );
	$achieved  = $remaining <= 0;
	$message   = $achieved
		? $msg_done
		: str_replace( '{amount}', wc_price($remaining), $msg_prog );

	return [
		'enabled'   => true,
		'achieved'  => $achieved,
		'percent'   => $percent,
		'message'   => $message,
		'bar_color' => $bar_color,
		'bg_color'  => $bg_color,
		'threshold' => $threshold,
		'remaining' => $remaining,
	];
}

// Render the bar HTML
function wk_free_shipping_bar_html( $context = 'cart' ) {
	if ( ! get_theme_mod('wk_fsb_enabled', true) ) return;
	$show_key = 'wk_fsb_show_' . $context;
	if ( ! get_theme_mod($show_key, $context !== 'checkout') ) return;
	if ( ! class_exists('WooCommerce') || ! WC()->cart ) return;

	$d = wk_get_free_ship_data();
	if ( ! $d['enabled'] ) return;

	$achieved_class = $d['achieved'] ? ' wk-fsb--achieved' : '';
	?>
	<div class="wk-free-shipping-bar<?php echo $achieved_class; ?>" id="wk-fsb-<?php echo esc_attr($context); ?>"
	     style="background:<?php echo esc_attr($d['bg_color']); ?>;" role="status" aria-live="polite">
		<div class="wk-fsb__message">
			<?php if ( ! $d['achieved'] ) : ?>
			<svg class="wk-fsb__truck" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
			<?php endif; ?>
			<span class="wk-fsb__text"><?php echo wp_kses_post($d['message']); ?></span>
		</div>
		<div class="wk-fsb__track">
			<div class="wk-fsb__fill" style="width:<?php echo $d['percent']; ?>%;background:<?php echo esc_attr($d['bar_color']); ?>;"></div>
		</div>
	</div>
	<?php
}

// Hook into cart, mini-cart, checkout
add_action( 'woocommerce_before_cart',         function() { wk_free_shipping_bar_html('cart'); }, 5 );
add_action( 'woocommerce_before_checkout_form',function() { wk_free_shipping_bar_html('checkout'); }, 5 );

// Mini cart hook (outputs into the drawer)
add_action( 'woocommerce_before_mini_cart',    function() { wk_free_shipping_bar_html('minicart'); }, 5 );

// AJAX refresh for free shipping bar on cart update
add_filter( 'woocommerce_update_order_review_fragments', function( $fragments ) {
	if ( ! get_theme_mod('wk_fsb_enabled', true) ) return $fragments;

	ob_start();
	wk_free_shipping_bar_html('cart');
	$fragments['.wk-free-shipping-bar#wk-fsb-cart'] = ob_get_clean();

	ob_start();
	wk_free_shipping_bar_html('minicart');
	$fragments['.wk-free-shipping-bar#wk-fsb-minicart'] = ob_get_clean();

	return $fragments;
} );

// Also pass threshold to JS for dynamic updates
add_action( 'wp_enqueue_scripts', function() {
	if ( is_cart() || is_checkout() ) {
		$threshold = (float) get_theme_mod('wk_fsb_threshold', 0);
		wp_localize_script( 'wk-main', 'wk_fsb', [
			'threshold'   => $threshold,
			'bar_color'   => get_theme_mod('wk_fsb_bar_color','#166534'),
			'msg_prog'    => get_theme_mod('wk_fsb_msg_progress','Add {amount} more for FREE shipping!'),
			'msg_done'    => get_theme_mod('wk_fsb_msg_achieved','🎉 You\'ve unlocked FREE shipping!'),
			'currency'    => get_woocommerce_currency_symbol(),
		] );
	}
}, 20 );

// ═══════════════════════════════════════════════════════════════
// 2. CART UPSELLS / RECOMMENDATIONS
// ═══════════════════════════════════════════════════════════════

add_action( 'customize_register', function( $wp_customize ) {
	$wp_customize->add_section( 'wk_cart_upsells', [
		'title' => __('🛒 Cart Recommendations','whitekurti'), 'panel'=>'wk_panel','priority'=>43,
	]);
	$fields = [
		['wk_cu_enabled',  'Show Recommendations in Cart',  'checkbox', true,   ''],
		['wk_cu_title',    'Section Title',                 'text',     'You might also like', ''],
		['wk_cu_count',    'Number of Products',            'number',   3,      '2–6 products'],
		['wk_cu_source',   'Recommend Based On',            'select',   'cart', ''],
	];
	foreach ($fields as [$id,$label,$type,$default,$desc]) {
		$san = $type==='checkbox'?'rest_sanitize_boolean':'sanitize_text_field';
		$wp_customize->add_setting($id,['default'=>$default,'sanitize_callback'=>$san,'transport'=>'refresh']);
		$ctrl = ['label'=>$label,'description'=>$desc,'section'=>'wk_cart_upsells','type'=>$type];
		if ($type==='select') $ctrl['choices']=['cart'=>'Products in cart (their upsells/cross-sells)','random'=>'Bestsellers / Random','manual'=>'Set manually (enter product IDs below)'];
		$wp_customize->add_control($id,$ctrl);
	}
	$wp_customize->add_setting('wk_cu_manual_ids',['default'=>'','sanitize_callback'=>'sanitize_text_field','transport'=>'refresh']);
	$wp_customize->add_control('wk_cu_manual_ids',['label'=>'Manual Product IDs (comma-separated)','section'=>'wk_cart_upsells','type'=>'text']);
} );

function wk_cart_upsells_render() {
	if (!get_theme_mod('wk_cu_enabled', true)) return;
	if (!class_exists('WooCommerce') || !WC()->cart || WC()->cart->is_empty()) return;

	$count   = max(2, min(6, absint(get_theme_mod('wk_cu_count', 3))));
	$source  = get_theme_mod('wk_cu_source', 'cart');
	$title   = get_theme_mod('wk_cu_title', 'You might also like');
	$products = [];

	if ( $source === 'cart' ) {
		$in_cart = array_keys(WC()->cart->get_cart());
		$seen    = [];
		foreach (WC()->cart->get_cart() as $item) {
			$pid = $item['product_id'];
			$p   = wc_get_product($pid);
			if (!$p) continue;
			// Get upsells then cross-sells
			$ids = array_merge($p->get_upsell_ids(), $p->get_cross_sell_ids());
			foreach ($ids as $id) {
				if (isset($seen[$id])) continue;
				$seen[$id] = true;
				if (WC()->cart->find_product_in_cart(WC()->cart->generate_cart_id($id))) continue;
				$up = wc_get_product($id);
				if ($up && $up->is_visible() && $up->is_in_stock()) $products[] = $up;
				if (count($products) >= $count) break 2;
			}
		}
		if (empty($products)) $source = 'random';
	}

	if ($source === 'random' || ($source === 'cart' && empty($products))) {
		$cart_ids = array_column(array_values(WC()->cart->get_cart()), 'product_id');
		$args = [
			'post_type'=>'product','post_status'=>'publish','posts_per_page'=>$count*2,
			'orderby'=>'rand','post__not_in'=>$cart_ids,
			'meta_query'=>[['key'=>'_stock_status','value'=>'instock']],
		];
		foreach (get_posts($args) as $pp) {
			$up = wc_get_product($pp->ID);
			if ($up && $up->is_visible()) { $products[] = $up; if(count($products)>=$count) break; }
		}
	}

	if ($source === 'manual') {
		$manual_ids = array_filter(array_map('absint', explode(',', get_theme_mod('wk_cu_manual_ids',''))));
		foreach ($manual_ids as $id) {
			$up = wc_get_product($id);
			if ($up && $up->is_visible()) $products[] = $up;
		}
	}

	$products = array_slice($products, 0, $count);
	if (empty($products)) return;
	?>
	<div class="wk-cart-upsells">
		<h3 class="wk-cart-upsells__title"><?php echo esc_html($title); ?></h3>
		<div class="wk-cart-upsells__grid">
			<?php foreach ($products as $p) :
				$img   = wp_get_attachment_image_url($p->get_image_id(), 'woocommerce_thumbnail');
				$img   = $img ?: wc_placeholder_img_src('woocommerce_thumbnail');
				$price = $p->get_price_html();
				$url   = get_permalink($p->get_id());
				$name  = $p->get_name();
				$type  = $p->get_type();
			?>
			<article class="wk-cu-item" data-product-id="<?php echo $p->get_id(); ?>">
				<a href="<?php echo esc_url($url); ?>" class="wk-cu-item__img-link">
					<img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($name); ?>" class="wk-cu-item__img" loading="lazy">
				</a>
				<div class="wk-cu-item__body">
					<a href="<?php echo esc_url($url); ?>" class="wk-cu-item__name"><?php echo esc_html($name); ?></a>
					<div class="wk-cu-item__price"><?php echo $price; ?></div>
				</div>
				<?php if ($type !== 'variable') : ?>
				<button class="wk-cu-add-btn" type="button"
					data-product-id="<?php echo $p->get_id(); ?>" aria-label="Add <?php echo esc_attr($name); ?> to cart">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
					Add
				</button>
				<?php else : ?>
				<a href="<?php echo esc_url($url); ?>" class="wk-cu-add-btn wk-cu-add-btn--link">Options</a>
				<?php endif; ?>
			</article>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_cart_collaterals', 'wk_cart_upsells_render', 5 );

// ═══════════════════════════════════════════════════════════════
// 3. PREMIUM COUPON FIELD UI
// ═══════════════════════════════════════════════════════════════
// Inject a "Have a coupon?" toggle above the cart table
add_action( 'woocommerce_before_cart_table', function() {
	if ( ! wc_coupons_enabled() ) return;
	?>
	<div class="wk-coupon-toggle-wrap">
		<button type="button" class="wk-coupon-toggle" id="wk-coupon-toggle" aria-expanded="false" aria-controls="wk-coupon-drawer">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
			Have a coupon code?
			<svg class="wk-coupon-toggle__chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
		</button>
		<div class="wk-coupon-drawer" id="wk-coupon-drawer" hidden>
			<div class="wk-coupon-drawer__inner">
				<p class="wk-coupon-drawer__hint">Enter your discount code below. Only one coupon can be applied per order.</p>
				<form class="wk-coupon-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
					<div class="wk-coupon-form__row">
						<input type="text" name="coupon_code" class="wk-coupon-input" id="wk-coupon-input-top"
							placeholder="e.g. WELCOME10"
							autocomplete="off" autocapitalize="characters" spellcheck="false" />
						<button type="submit" name="apply_coupon" class="wk-btn" value="Apply coupon">Apply</button>
					</div>
				</form>
				<?php
				// Show applied coupons
				if ( WC()->cart && WC()->cart->get_applied_coupons() ) :
					echo '<div class="wk-applied-coupons">';
					foreach ( WC()->cart->get_applied_coupons() as $coupon_code ) :
						$coupon    = new WC_Coupon( $coupon_code );
						$discount  = WC()->cart->get_coupon_discount_amount( $coupon_code );
						echo '<div class="wk-applied-coupon">';
						echo '<span class="wk-applied-coupon__code"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> '.strtoupper(esc_html($coupon_code)).'</span>';
						echo '<span class="wk-applied-coupon__savings">-'.wc_price($discount).'</span>';
						echo '<a href="'.esc_url(add_query_arg(['remove_coupon'=>rawurlencode($coupon_code)],wc_get_cart_url())).'" class="wk-applied-coupon__remove" aria-label="Remove coupon">&times;</a>';
						echo '</div>';
					endforeach;
					echo '</div>';
				endif;
				?>
			</div>
		</div>
	</div>
	<?php
}, 5 );

// Remove the default inline coupon from the cart table (we have our own above)
remove_action( 'woocommerce_cart_actions', 'woocommerce_print_coupon_form' );
