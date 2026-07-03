<?php
/**
 * WhiteKurti — Wishlist System (no plugin required)
 * localStorage for guests, usermeta for logged-in users
 * Features: Add/remove, wishlist page, share link, move to cart
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Customizer settings ────────────────────────────────────────────────────
add_action('customize_register', function($wp_customize) {
	$wp_customize->add_section('wk_wishlist', [
		'title' => __('❤️ Wishlist', 'whitekurti'), 'panel'=>'wk_panel','priority'=>39,
	]);
	$fields = [
		['wk_wl_enabled',    'Enable wishlist',                    'checkbox', true,  ''],
		['wk_wl_page_id',    'Wishlist Page ID (auto-created if 0)','number',   0,     ''],
		['wk_wl_btn_text',   'Add to Wishlist button text',        'text',     'Add to Wishlist',''],
		['wk_wl_added_text', 'Added to Wishlist text',             'text',     'Saved to Wishlist',''],
		['wk_wl_show_count', 'Show count on header heart icon',    'checkbox', true,  ''],
		['wk_wl_pos',        'Button position on product cards',   'select',   'overlay',''],
	];
	foreach ($fields as [$id,$label,$type,$default,$desc]) {
		$san = $type==='checkbox'?'rest_sanitize_boolean':'sanitize_text_field';
		$wp_customize->add_setting($id,['default'=>$default,'sanitize_callback'=>$san,'transport'=>'refresh']);
		$ctrl=['label'=>$label,'description'=>$desc,'section'=>'wk_wishlist','type'=>$type];
		if ($type==='select') $ctrl['choices']=['overlay'=>'Overlay on image (hover)','below'=>'Below image'];
		$wp_customize->add_control($id,$ctrl);
	}
});

// ── Auto-create wishlist page on theme activation ─────────────────────────
function wk_maybe_create_wishlist_page() {
	if ( get_theme_mod('wk_wl_page_id', 0) ) return;
	// Transient cache: only check once per hour
	if ( get_transient('wk_wl_page_checked') ) return;
	set_transient('wk_wl_page_checked', 1, HOUR_IN_SECONDS);
	$page = get_page_by_path('wishlist');
	if ($page) {
		set_theme_mod('wk_wl_page_id', $page->ID);
		return;
	}
	$page_id = wp_insert_post([
		'post_title'   => 'My Wishlist',
		'post_name'    => 'wishlist',
		'post_content' => '[wk_wishlist]',
		'post_status'  => 'publish',
		'post_type'    => 'page',
	]);
	if ($page_id && !is_wp_error($page_id)) {
		set_theme_mod('wk_wl_page_id', $page_id);
	}
}
add_action('init', function() {
	if (get_theme_mod('wk_wl_enabled',true)) wk_maybe_create_wishlist_page();
}, 25);

// ── Helper: get/set wishlist for current user ──────────────────────────────
function wk_get_wishlist($user_id = null) {
	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return []; // Guests handled by localStorage
	$wl = get_user_meta($user_id, '_wk_wishlist', true);
	return is_array($wl) ? $wl : [];
}
function wk_save_wishlist($ids, $user_id = null) {
	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return false;
	update_user_meta($user_id, '_wk_wishlist', array_unique(array_map('absint', $ids)));
	return true;
}

// ── AJAX: add/remove/get wishlist ──────────────────────────────────────────
add_action('wp_ajax_wk_wishlist_toggle',        'wk_ajax_wishlist_toggle');
add_action('wp_ajax_nopriv_wk_wishlist_toggle', 'wk_ajax_wishlist_toggle');
function wk_ajax_wishlist_toggle() {
	check_ajax_referer('wk_wishlist_nonce','nonce');
	$pid    = absint($_POST['product_id'] ?? 0);
	if (!$pid) wp_send_json_error();
	$uid    = get_current_user_id();
	if (!$uid) {
		// For guests, just return success — localStorage handles state
		wp_send_json_success(['action'=>'toggle','logged_in'=>false]);
		return;
	}
	$wl     = wk_get_wishlist($uid);
	$in_wl  = in_array($pid, $wl);
	if ($in_wl) {
		$wl = array_diff($wl, [$pid]);
		$action = 'removed';
	} else {
		$wl[] = $pid;
		$action = 'added';
	}
	wk_save_wishlist(array_values($wl), $uid);
	wp_send_json_success([
		'action'     => $action,
		'count'      => count($wl),
		'product_id' => $pid,
		'logged_in'  => true,
	]);
}

add_action('wp_ajax_wk_wishlist_get',        'wk_ajax_wishlist_get');
add_action('wp_ajax_nopriv_wk_wishlist_get', 'wk_ajax_wishlist_get');
function wk_ajax_wishlist_get() {
	check_ajax_referer('wk_wishlist_nonce','nonce');
	$uid = get_current_user_id();
	if (!$uid) { wp_send_json_success(['ids'=>[],'products'=>[]]); return; }
	$wl  = wk_get_wishlist($uid);
	$products = [];
	foreach ($wl as $pid) {
		$p = wc_get_product($pid);
		if (!$p || !$p->is_visible()) continue;
		$img_id = $p->get_image_id();
		$products[] = [
			'id'       => $pid,
			'name'     => $p->get_name(),
			'url'      => get_permalink($pid),
			'price'    => $p->get_price_html(),
			'img'      => $img_id ? wp_get_attachment_image_url($img_id,'woocommerce_single') : wc_placeholder_img_src(),
			'in_stock' => $p->is_in_stock(),
			'type'     => $p->get_type(),
		];
	}
	wp_send_json_success(['ids'=>$wl,'products'=>$products]);
}

// ── AJAX: sync guest wishlist to logged-in user ────────────────────────────
add_action('wp_ajax_wk_wishlist_sync', 'wk_ajax_wishlist_sync');
add_action( 'wp_ajax_nopriv_wk_wishlist_sync', 'wk_ajax_wishlist_sync' );
function wk_ajax_wishlist_sync() {
	check_ajax_referer('wk_wishlist_nonce','nonce');
	$uid = get_current_user_id();
	if (!$uid) wp_send_json_error();
	$ids_raw = $_POST['ids'] ?? '';
	$new_ids = array_filter(array_map('absint', explode(',', $ids_raw)));
	if (!empty($new_ids)) {
		$existing = wk_get_wishlist($uid);
		$merged   = array_unique(array_merge($existing, $new_ids));
		wk_save_wishlist($merged, $uid);
	}
	wp_send_json_success(['count' => count(wk_get_wishlist($uid))]);
}

// ── Pass wishlist config to JS ─────────────────────────────────────────────
add_action('wp_enqueue_scripts', function() {
	if (!get_theme_mod('wk_wl_enabled',true)) return;
	$uid     = get_current_user_id();
	$wl_ids  = $uid ? wk_get_wishlist($uid) : [];
	$page_id = get_theme_mod('wk_wl_page_id',0);
	$wl_url  = $page_id ? get_permalink($page_id) : home_url('/wishlist');
	wp_localize_script('wk-main','wk_wishlist_cfg',[
		'nonce'       => wp_create_nonce('wk_wishlist_nonce'),
		'ajax'        => admin_url('admin-ajax.php'),
		'enabled'     => '1',
		'logged_in'   => $uid ? '1' : '0',
		'server_ids'  => $wl_ids,
		'wishlist_url'=> $wl_url,
		'btn_text'    => get_theme_mod('wk_wl_btn_text','Add to Wishlist'),
		'added_text'  => get_theme_mod('wk_wl_added_text','Saved to Wishlist'),
		'shop_url'    => class_exists('WooCommerce') ? wc_get_page_permalink('shop') : home_url('/shop'),
	]);
}, 20);

// ── Render wishlist button ─────────────────────────────────────────────────
function wk_wishlist_button($product_id = null) {
	if (!get_theme_mod('wk_wl_enabled',true)) return '';
	if (!$product_id) {
		global $product;
		$product_id = $product ? $product->get_id() : get_the_ID();
	}
	if (!$product_id) return '';
	$btn_text  = get_theme_mod('wk_wl_btn_text','Add to Wishlist');
	$added_text= get_theme_mod('wk_wl_added_text','Saved to Wishlist');
	ob_start();
	?>
	<button type="button"
		class="wk-wl-btn"
		data-product-id="<?php echo absint($product_id); ?>"
		aria-label="Add to wishlist"
		aria-pressed="false">
		<svg class="wk-wl-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
		<span class="wk-wl-label"><?php echo esc_html($btn_text); ?></span>
	</button>
	<?php
	return ob_get_clean();
}

// Hook onto product cards
add_action('woocommerce_before_shop_loop_item_title', function() {
	global $product;
	$pos = get_theme_mod('wk_wl_pos','overlay');
	if ($pos === 'overlay') {
		echo '<div class="wk-wl-overlay-wrap">';
		echo wk_wishlist_button($product->get_id());
		echo '</div>';
	}
}, 9);

// Hook onto single product page (near Add to Cart)
add_action('woocommerce_single_product_summary', function() {
	global $product;
	if (!get_theme_mod('wk_wl_enabled',true)) return;
	echo '<div class="wk-pdp__wishlist">';
	echo wk_wishlist_button($product->get_id());
	echo '</div>';
}, 31);

// ── Wishlist page [wk_wishlist] shortcode ──────────────────────────────────
add_shortcode('wk_wishlist', function() {
	ob_start();
	?>
	<div class="wk-wishlist-page" id="wk-wishlist-page">
		<div class="wk-wishlist-header">
			<h1 class="wk-page-title">My Wishlist</h1>
			<div class="wk-wishlist-header__actions">
				<button class="wk-btn wk-btn--sm wk-btn--outline" id="wk-wl-share">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
					Share Wishlist
				</button>
				<button class="wk-btn wk-btn--sm" id="wk-wl-add-all">Add All to Cart</button>
			</div>
		</div>

		<div class="wk-wishlist-loading" id="wk-wl-loading">
			<div class="wk-spinner" style="margin:0 auto;"></div>
		</div>

		<div class="wk-wishlist-grid" id="wk-wishlist-grid" style="display:none;"></div>

		<div class="wk-wishlist-empty" id="wk-wl-empty" style="display:none;">
			<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" opacity=".3"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
			<h2>Your wishlist is empty</h2>
			<p>Save items you love and they'll appear here.</p>
			<a href="<?php echo esc_url(class_exists('WooCommerce') ? wc_get_page_permalink('shop') : home_url('/shop')); ?>" class="wk-btn">Start Shopping</a>
		</div>

		<!-- Share modal -->
		<div class="wk-wl-share-modal" id="wk-wl-share-modal" hidden>
			<div class="wk-modal__overlay" id="wk-wl-share-overlay"></div>
			<div class="wk-modal__panel" style="max-width:400px;padding:28px;">
				<h3 style="margin:0 0 16px;font-family:var(--font-display);font-size:20px;font-weight:400;">Share Your Wishlist</h3>
				<p style="font-size:13px;color:var(--ink-mute);margin:0 0 14px;">Anyone with this link can view your wishlist.</p>
				<div style="display:flex;gap:8px;">
					<input type="text" id="wk-wl-share-url" readonly style="flex:1;padding:9px 12px;border:.5px solid var(--line);background:var(--surface-2);font-size:13px;font-family:monospace;outline:none;" />
					<button class="wk-btn wk-btn--sm" id="wk-wl-copy-url">Copy</button>
				</div>
				<button class="wk-modal__close" id="wk-wl-share-close" style="position:absolute;top:12px;right:14px;">&times;</button>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
});

// ── Shared wishlist view via URL param ─────────────────────────────────────
// Hook to wp_enqueue_scripts (runs before scripts are output, unlike template_redirect)
add_action('wp_enqueue_scripts', function() {
	if (!isset($_GET['wl'])) return;
	if (!is_page('wishlist') && get_query_var('pagename') !== 'wishlist') return;
	$ids_raw = sanitize_text_field($_GET['wl']);
	$ids     = array_filter(array_map('absint', explode(',', $ids_raw)));
	if (!empty($ids)) {
		wp_localize_script('wk-main','wk_shared_wl',['ids'=>$ids,'shared'=>true]);
	}
}, 25);

// ── AJAX: get products by IDs (for guest/shared wishlists) ────────────────
add_action('wp_ajax_wk_wishlist_get_by_ids',        'wk_ajax_wishlist_get_by_ids');
add_action('wp_ajax_nopriv_wk_wishlist_get_by_ids', 'wk_ajax_wishlist_get_by_ids');
function wk_ajax_wishlist_get_by_ids() {
	check_ajax_referer('wk_wishlist_nonce','nonce');
	$raw = sanitize_text_field($_POST['ids'] ?? '');
	$ids = array_filter(array_map('absint', explode(',', $raw)));
	if (empty($ids)) { wp_send_json_success(['ids'=>[],'products'  =>[]]); return; }

	$products = [];
	foreach ($ids as $pid) {
		$p = wc_get_product($pid);
		if (!$p || !$p->is_visible()) continue;
		$img_id     = $p->get_image_id();
		$products[] = [
			'id'       => $pid,
			'name'     => $p->get_name(),
			'url'      => get_permalink($pid),
			'price'    => $p->get_price_html(),
			'img'      => $img_id ? wp_get_attachment_image_url($img_id,'woocommerce_single') : wc_placeholder_img_src(),
			'in_stock' => $p->is_in_stock(),
			'type'     => $p->get_type(),
		];
	}
	wp_send_json_success(['ids'=>$ids,'products'=>$products]);
}
