<?php
/**
 * WhiteKurti — Product Quick View Modal
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Customizer ────────────────────────────────────────────────────────────────
add_action('customize_register', function($wp_customize) {
	$wp_customize->add_section('wk_quick_view', [
		'title' => __('👁️ Quick View', 'whitekurti'), 'panel' => 'wk_panel', 'priority' => 48,
	]);
	foreach ([
		['wk_qv_enabled', 'Enable Quick View on Product Cards', 'checkbox', true, ''],
		['wk_qv_trigger',  'Show Quick View Button On',          'select',  'hover', ''],
	] as [$id,$label,$type,$default,$desc]) {
		$san = $type==='checkbox' ? 'rest_sanitize_boolean' : 'sanitize_text_field';
		$wp_customize->add_setting($id, ['default'=>$default,'sanitize_callback'=>$san,'transport'=>'refresh']);
		$ctrl = ['label'=>$label,'section'=>'wk_quick_view','type'=>$type];
		if ($type==='select') $ctrl['choices'] = ['hover'=>'On Hover','always'=>'Always Visible','click'=>'On Click (no hover)'];
		$wp_customize->add_control($id, $ctrl);
	}
});

// ── Render modal placeholder (populated via AJAX) ────────────────────────────
function wk_quick_view_modal_html() {
	if (!get_theme_mod('wk_qv_enabled', true)) return;
	?>
	<div id="wk-quick-view-modal" class="wk-modal wk-qv-modal" role="dialog" aria-modal="true" aria-labelledby="wk-qv-title" hidden>
		<div class="wk-modal__overlay" id="wk-qv-overlay"></div>
		<div class="wk-modal__panel wk-qv-panel">
			<button class="wk-modal__close" id="wk-qv-close" aria-label="Close quick view">&times;</button>
			<div class="wk-qv-loading" id="wk-qv-loading">
				<div class="wk-spinner"></div>
			</div>
			<div class="wk-qv-content" id="wk-qv-content" style="display:none;">
				<div class="wk-qv-layout">
					<div class="wk-qv-gallery" id="wk-qv-gallery"></div>
					<div class="wk-qv-info" id="wk-qv-info"></div>
				</div>
			</div>
		</div>
	</div>
	<?php
}
add_action('wp_footer', 'wk_quick_view_modal_html', 91);

// ── AJAX: Load quick view product data ───────────────────────────────────────
add_action('wp_ajax_wk_quick_view',        'wk_ajax_quick_view');
add_action('wp_ajax_nopriv_wk_quick_view', 'wk_ajax_quick_view');
function wk_ajax_quick_view() {
	check_ajax_referer('wk_qv_nonce', 'nonce');
	$product_id = absint($_POST['product_id'] ?? 0);
	if (!$product_id) wp_send_json_error(['message' => 'Invalid product.']);

	$product = wc_get_product($product_id);
	if (!$product || !$product->is_visible()) wp_send_json_error(['message' => 'Product not found.']);

	// Gallery images
	$img_ids   = array_filter(array_merge([$product->get_image_id()], $product->get_gallery_image_ids()));
	$gallery   = array_map(function($id) {
		return [
			'thumb' => wp_get_attachment_image_url($id, 'woocommerce_single'),
			'full'  => wp_get_attachment_image_url($id, 'full'),
			'alt'   => get_post_meta($id, '_wp_attachment_image_alt', true),
		];
	}, $img_ids);

	// Badges
	ob_start();
	wk_render_product_badges($product);
	$badges_html = ob_get_clean();

	// Price
	$price_html = $product->get_price_html();

	// Short description
	$short_desc = wpautop($product->get_short_description());

	// Categories
	$cats = strip_tags(wc_get_product_category_list($product_id));

	// ATC form
	ob_start();
	$GLOBALS['product'] = $product;
	setup_postdata(get_post($product_id));
	woocommerce_template_single_add_to_cart();
	wp_reset_postdata();
	$atc_html = ob_get_clean();

	// Rating
	$rating_count = $product->get_rating_count();
	$avg_rating   = $product->get_average_rating();

	// Stock
	$in_stock = $product->is_in_stock();
	$stock    = $product->get_stock_quantity();

	wp_send_json_success([
		'id'          => $product_id,
		'name'        => $product->get_name(),
		'price'       => $price_html,
		'short_desc'  => $short_desc,
		'cats'        => $cats,
		'gallery'     => $gallery,
		'badges'      => $badges_html,
		'atc'         => $atc_html,
		'url'         => get_permalink($product_id),
		'rating'      => $avg_rating,
		'rating_count'=> $rating_count,
		'in_stock'    => $in_stock,
		'stock'       => $stock,
	]);
}

// ── Enqueue nonce ─────────────────────────────────────────────────────────────
add_action('wp_enqueue_scripts', function() {
	if (get_theme_mod('wk_qv_enabled', true)) {
		wp_localize_script('wk-main', 'wk_qv_cfg', [
			'nonce'   => wp_create_nonce('wk_qv_nonce'),
			'ajax'    => admin_url('admin-ajax.php'),
			'enabled' => '1',
			'trigger' => get_theme_mod('wk_qv_trigger', 'hover'),
		]);
	}
}, 20);

// ── Add Quick View button to product cards ───────────────────────────────────
add_action('woocommerce_after_shop_loop_item', function() {
	if (!get_theme_mod('wk_qv_enabled', true)) return;
	global $product;
	$trigger = get_theme_mod('wk_qv_trigger', 'hover');
	$cls     = 'wk-qv-btn wk-qv-trigger--' . esc_attr($trigger);
	echo '<button type="button" class="' . $cls . '" data-product-id="' . $product->get_id() . '" aria-label="Quick view ' . esc_attr($product->get_name()) . '">';
	echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
	echo ' Quick View</button>';
}, 12);
