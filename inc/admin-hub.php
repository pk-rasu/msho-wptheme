<?php
/**
 * WhiteKurti — Unified Admin Hub
 * Single top-level "WhiteKurti" menu. Every theme feature is a submenu.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Register all menus at priority 50 (after callbacks defined) ───────────
add_action( 'admin_menu', 'wk_hub_register_menus', 50 );
function wk_hub_register_menus() {
	add_menu_page( 'WhiteKurti', '🎨 WhiteKurti', 'manage_options', 'wk-hub', 'wk_hub_dashboard_page', 'dashicons-store', 3 );
	add_submenu_page( 'wk-hub', 'Dashboard',         '🏠 Dashboard',        'manage_options', 'wk-hub',                  'wk_hub_dashboard_page' );
	wk_hub_sep( 'Reviews & Content' );
	add_submenu_page( 'wk-hub', 'All Reviews',        '⭐ All Reviews',      'manage_options', 'edit.php?post_type=wk_review' );
	add_submenu_page( 'wk-hub', 'Add Review',         '➕ Add Review',       'manage_options', 'post-new.php?post_type=wk_review' );
	add_submenu_page( 'wk-hub', 'Import / Export',    '📥 Import / Export',  'manage_options', 'wk-reviews-io',          'wk_reviews_io_page' );
	add_submenu_page( 'wk-hub', 'By Product',         '📦 By Product',       'manage_options', 'wk-reviews-by-product',  'wk_reviews_by_product_page' );
	add_submenu_page( 'wk-hub', 'Testimonials',       '💬 Testimonials',     'manage_options', 'wk-testimonials',        'wk_testimonials_admin_page' );
	add_submenu_page( 'wk-hub', 'Instagram Grid',     '📸 Instagram Grid',   'manage_options', 'wk-instagram-grid',      'wk_ig_admin_page' );
	add_submenu_page( 'wk-hub', 'Lookbook',           '🖼️ Lookbook',         'manage_options', 'wk-lookbook',            'wk_lookbook_admin_page' );
	wk_hub_sep( 'Marketing' );
	add_submenu_page( 'wk-hub', 'Notifications',      '🔔 Notifications',    'manage_options', 'wk-fake-notifications',  'wk_fn_admin_page' );
	add_submenu_page( 'wk-hub', 'Exit Popup',         '🚪 Exit Popup',       'manage_options', 'wk-exit-popup',          'wk_ep_admin_page' );
	add_submenu_page( 'wk-hub', 'WhatsApp Button',    '💬 WhatsApp',         'manage_options', 'wk-whatsapp',            'wk_wa_admin_page' );
	wk_hub_sep( 'Store & Display' );
	add_submenu_page( 'wk-hub', 'Header Navigation', '🧭 Header Nav',       'manage_options', 'wk-header-nav',          'wk_nav_admin_page' );
	add_submenu_page( 'wk-hub', 'Bottom Nav Bar',     '📱 Bottom Nav',       'manage_options', 'wk-bottom-nav',          'wk_bn_admin_page' );
	add_submenu_page( 'wk-hub', 'Product Badges',     '🏷️ Product Badges',   'manage_options', 'wk-badges',              'wk_badges_admin_page' );
	add_submenu_page( 'wk-hub', 'Size Guide',         '📏 Size Guide',       'manage_options', 'wk-size-guide',          'wk_sg_admin_page' );
	add_submenu_page( 'wk-hub', 'Delivery Zones',     '📍 Delivery Zones',   'manage_options', 'wk-delivery-zones',      'wk_dz_admin_page' );
	add_submenu_page( 'wk-hub', 'Back-in-Stock',      '🔔 Back-in-Stock',    'manage_options', 'wk-back-in-stock',       'wk_bis_admin_page' );
	wk_hub_sep( 'SEO & Growth' );
	add_submenu_page( 'wk-hub', 'Google Shopping',    '🛒 Google Shopping',  'manage_options', 'wk-google-shopping',     'wk_gsf_admin_page' );
	add_submenu_page( 'wk-hub', 'Search Analytics',   '🔍 Search Analytics', 'manage_options', 'wk-search-log',          'wk_hub_search_page' );
}

// Hide CPT auto-menu — hub manages it
add_action( 'admin_menu', function() {
	remove_menu_page( 'edit.php?post_type=wk_review' );
}, 999 );

// ── Section divider helper ─────────────────────────────────────────────────
function wk_hub_sep( $label ) {
	static $n = 0;
	$n++;
	add_submenu_page( 'wk-hub', $label,
		'<span style="font-size:9px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#aaa;pointer-events:none;cursor:default;display:block;padding:8px 0 2px;">' . esc_html($label) . '</span>',
		'manage_options', 'wk-hub-sep-' . $n, 'wk_hub_sep_cb' );
}
function wk_hub_sep_cb() {
	wp_redirect( admin_url('admin.php?page=wk-hub') ); exit;
}

// ── Search analytics page ──────────────────────────────────────────────────
function wk_hub_search_page() {
	if ( isset($_GET['clear']) && wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'wk_clear_search' ) ) {
		delete_option('wk_search_log');
	}
	$log = get_option('wk_search_log', []);
	arsort($log);
	echo '<div class="wrap"><h1>🔍 Search Analytics</h1>';
	if ( empty($log) ) {
		echo '<p style="color:#888;margin-top:16px;">No searches logged yet. Visitor search terms will appear here once people start searching.</p>';
	} else {
		$total = array_sum($log); $max = max($log);
		echo '<p style="color:#666;font-size:13px;">Top '.min(50,count($log)).' terms &nbsp;·&nbsp; Total: <strong>'.number_format($total).'</strong></p>';
		echo '<table class="wp-list-table widefat striped" style="max-width:580px;margin-top:12px;">';
		echo '<thead><tr><th>Term</th><th style="text-align:center;">Count</th><th>Bar</th></tr></thead><tbody>';
		foreach ( array_slice($log,0,50,true) as $term => $count ) {
			$pct = $max>0 ? round(($count/$max)*100) : 0;
			echo '<tr><td><strong>'.esc_html($term).'</strong></td>';
			echo '<td style="text-align:center;">'.absint($count).'</td>';
			echo '<td><div style="background:#e5e7eb;border-radius:3px;height:7px;width:160px;"><div style="background:#6B1E3E;border-radius:3px;height:7px;width:'.$pct.'%;"></div></div></td></tr>';
		}
		echo '</tbody></table>';
		$url = wp_nonce_url(admin_url('admin.php?page=wk-search-log&clear=1'),'wk_clear_search');
		echo '<p style="margin-top:12px;"><a href="'.esc_url($url).'" class="button" onclick="return confirm(\'Clear all?\')">🗑 Clear Log</a></p>';
	}
	echo '</div>';
}

// ══════════════════════════════════════════════════════════════════════════════
// FEATURE CARD RENDERER — top-level function (NOT nested, avoids fatal error)
// ══════════════════════════════════════════════════════════════════════════════
function wk_hub_card( $title, $items ) {
	echo '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">';
	echo '<div style="background:#f9fafb;border-bottom:1px solid #e5e7eb;padding:11px 16px;">';
	echo '<strong style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#6b7280;">' . esc_html($title) . '</strong>';
	echo '</div><div style="padding:4px 0;">';
	foreach ( $items as $item ) {
		if ( isset($item['status']) ) {
			$on    = (bool)$item['status'];
			$badge = '<span style="font-size:10px;font-weight:700;padding:2px 6px;border-radius:10px;background:'.($on?'#dcfce7':'#f1f5f9').';color:'.($on?'#166534':'#94a3b8').';">'.($on?'● ON':'○ OFF').'</span>';
		} elseif ( isset($item['count']) ) {
			$badge = '<span style="font-size:10px;font-weight:600;padding:2px 6px;border-radius:10px;background:#f1f5f9;color:#475569;">'.esc_html($item['count']).'</span>';
		} else {
			$badge = '<span style="color:#d1d5db;">›</span>';
		}
		echo '<a href="'.esc_url($item['url']).'" style="display:flex;align-items:center;justify-content:space-between;padding:9px 16px;text-decoration:none;color:#111;border-bottom:1px solid #f5f5f5;">';
		echo '<span style="display:flex;align-items:center;gap:8px;font-size:13px;"><span>'.esc_html($item['icon']).'</span><span>'.esc_html($item['label']).'</span></span>';
		echo $badge;
		echo '</a>';
	}
	echo '</div></div>';
}

// ── Dashboard page ─────────────────────────────────────────────────────────
function wk_hub_dashboard_page() {
	$review_count  = (int)( wp_count_posts('wk_review')->publish ?? 0 );
	$product_count = class_exists('WooCommerce') ? (int)wp_count_posts('product')->publish : 0;
	$order_count   = 0;
	if ( class_exists('WooCommerce') ) {
		$cnt = wp_count_posts('shop_order');
		$order_count = (int)($cnt->processing??0) + (int)($cnt->on_hold??0);
	}
	$search_count = array_sum( get_option('wk_search_log',[]) );
	$testimonials = get_option('wk_testimonials_list',[]);
	$_notif       = get_option('wk_fake_notifications',[]);
	$notif_on     = is_array($_notif) && !empty($_notif['enabled']);
	$_popup       = get_option('wk_exit_popup',[]);
	$popup_on     = is_array($_popup) && !empty($_popup['enabled']);
	$_wa          = get_option('wk_whatsapp_settings',[]);
	$wa_on        = is_array($_wa) && !empty($_wa['enabled']);
	$bn_settings  = get_option('wk_bottom_nav',[]);
	$bn_on        = isset($bn_settings['enabled']) ? (bool)$bn_settings['enabled'] : true;
	$ver          = defined('WK_VERSION') ? WK_VERSION : '1.0.0';
	?>
	<div class="wrap" style="max-width:1100px;">
	<div style="background:linear-gradient(135deg,#6B1E3E,#4a1228);border-radius:10px;padding:24px 28px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
		<div>
			<h1 style="color:#fff;margin:0;font-size:20px;font-weight:700;">🎨 WhiteKurti Store Manager</h1>
			<p style="color:rgba(255,255,255,.65);margin:4px 0 0;font-size:12px;">Theme v<?php echo esc_html($ver); ?> &nbsp;·&nbsp; <?php echo esc_html(get_bloginfo('name')); ?></p>
		</div>
		<div style="display:flex;gap:8px;">
			<a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" style="background:rgba(255,255,255,.15);color:#fff;padding:7px 14px;border-radius:6px;text-decoration:none;font-size:12px;font-weight:600;">👁️ View Site</a>
			<a href="<?php echo esc_url(admin_url('customize.php')); ?>" style="background:#fff;color:#6B1E3E;padding:7px 14px;border-radius:6px;text-decoration:none;font-size:12px;font-weight:700;">🎨 Customizer</a>
		</div>
	</div>

	<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;">
	<?php
	foreach ( [
		['📦', number_format($product_count), 'Products',      admin_url('edit.php?post_type=product'),   '#f0fdf4','#166534'],
		['🛒', number_format($order_count),   'Pending Orders',admin_url('edit.php?post_type=shop_order'),'#eff6ff','#1d4ed8'],
		['⭐', number_format($review_count),  'Reviews',       admin_url('admin.php?page=wk-hub'),        '#fefce8','#92400e'],
		['🔍', number_format($search_count),  'Searches',      admin_url('admin.php?page=wk-search-log'), '#fdf4ff','#7e22ce'],
	] as $s ) {
		list($ico,$val,$lbl,$lnk,$bg,$col) = $s;
		echo '<a href="'.esc_url($lnk).'" style="background:'.$bg.';border:1px solid '.$col.'22;border-radius:10px;padding:14px;text-decoration:none;display:flex;align-items:center;gap:10px;">';
		echo '<span style="font-size:22px;">'.$ico.'</span>';
		echo '<div><div style="font-size:20px;font-weight:800;color:'.$col.';">'.$val.'</div><div style="font-size:11px;color:'.$col.';opacity:.8;">'.$lbl.'</div></div>';
		echo '</a>';
	}
	?>
	</div>

	<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
	<?php
	wk_hub_card('⭐ Reviews & Content', [
		['icon'=>'⭐','label'=>'All Reviews',    'url'=>admin_url('edit.php?post_type=wk_review'),       'count'=>$review_count.' reviews'],
		['icon'=>'➕','label'=>'Add New Review', 'url'=>admin_url('post-new.php?post_type=wk_review')],
		['icon'=>'📥','label'=>'Import/Export', 'url'=>admin_url('admin.php?page=wk-reviews-io')],
		['icon'=>'📦','label'=>'By Product',    'url'=>admin_url('admin.php?page=wk-reviews-by-product')],
		['icon'=>'💬','label'=>'Testimonials',  'url'=>admin_url('admin.php?page=wk-testimonials'),     'count'=>count($testimonials).' added'],
		['icon'=>'📸','label'=>'Instagram',     'url'=>admin_url('admin.php?page=wk-instagram-grid')],
		['icon'=>'🖼️','label'=>'Lookbook',      'url'=>admin_url('admin.php?page=wk-lookbook')],
	]);
	wk_hub_card('📣 Marketing', [
		['icon'=>'🔔','label'=>'Notifications', 'url'=>admin_url('admin.php?page=wk-fake-notifications'),'status'=>$notif_on],
		['icon'=>'🚪','label'=>'Exit Popup',    'url'=>admin_url('admin.php?page=wk-exit-popup'),        'status'=>$popup_on],
		['icon'=>'💬','label'=>'WhatsApp',      'url'=>admin_url('admin.php?page=wk-whatsapp'),          'status'=>$wa_on],
	]);
	wk_hub_card('🏪 Store & Display', [
		['icon'=>'🧭','label'=>'Header Nav',    'url'=>admin_url('admin.php?page=wk-header-nav')],
		['icon'=>'📱','label'=>'Bottom Nav',    'url'=>admin_url('admin.php?page=wk-bottom-nav'),        'status'=>$bn_on],
		['icon'=>'🏷️','label'=>'Prod. Badges', 'url'=>admin_url('admin.php?page=wk-badges')],
		['icon'=>'📏','label'=>'Size Guide',    'url'=>admin_url('admin.php?page=wk-size-guide')],
		['icon'=>'📍','label'=>'Delivery Zones','url'=>admin_url('admin.php?page=wk-delivery-zones')],
		['icon'=>'🔔','label'=>'Back-in-Stock', 'url'=>admin_url('admin.php?page=wk-back-in-stock')],
	]);
	wk_hub_card('📈 SEO & Growth', [
		['icon'=>'🛒','label'=>'Google Shopping','url'=>admin_url('admin.php?page=wk-google-shopping')],
		['icon'=>'🔍','label'=>'Search Analytics','url'=>admin_url('admin.php?page=wk-search-log'),'count'=>number_format($search_count).' searches'],
	]);
	wk_hub_card('⚙️ Settings', [
		['icon'=>'🎨','label'=>'Customizer',   'url'=>admin_url('customize.php')],
		['icon'=>'🧩','label'=>'Plugins',      'url'=>admin_url('plugins.php')],
		['icon'=>'📄','label'=>'Pages',        'url'=>admin_url('edit.php?post_type=page')],
		['icon'=>'🖼️','label'=>'Media',        'url'=>admin_url('upload.php')],
		['icon'=>'🔗','label'=>'Permalinks',   'url'=>admin_url('options-permalink.php')],
	]);
	?>
	</div>
	<p style="margin-top:16px;color:#aaa;font-size:11px;text-align:center;">WhiteKurti Theme v<?php echo esc_html($ver); ?></p>
	</div>
	<?php
}

// ─── Category Images Quick-Link in Admin ──────────────────────────────────────
add_action( 'admin_notices', function() {
    if ( ! current_user_can('manage_options') ) return;
    $screen = get_current_screen();
    if ( ! $screen || $screen->id !== 'toplevel_page_wk-hub' ) return;
    ?>
    <div class="notice notice-info is-dismissible" style="margin-top:0;">
        <p>
            <strong>📸 Category Images:</strong>
            To add or change category images, go to
            <a href="<?php echo admin_url('edit-tags.php?taxonomy=product_cat&post_type=product'); ?>">
                Products → Categories
            </a>
            and edit each category to upload a thumbnail image.
            These images appear in the homepage category circles and mobile menu.
        </p>
    </div>
    <?php
} );
