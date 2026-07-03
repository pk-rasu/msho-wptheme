<?php
/**
 * WhiteKurti — Conversion & Trust Features
 * 7.  Recently Viewed Products section on PDP
 * 8.  Wishlist Share via URL
 * 9.  Customer Photo Upload in Reviews
 * 10. Flash Sale Timer on Product Cards
 * 11. In-session Cart Abandonment Reminder
 * 12. Prepaid Discount Badge at Checkout
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ═══════════════════════════════════════════════════════
// 7. RECENTLY VIEWED PRODUCTS
// ═══════════════════════════════════════════════════════

// Track viewed products via JS (stored in localStorage)
add_action( 'wp_footer', 'wk_recently_viewed_tracker' );
function wk_recently_viewed_tracker() {
	if ( ! class_exists('WooCommerce') ) return;
	if ( ! is_singular('product') ) return;
	global $post;
	$product = wc_get_product( $post->ID );
	if ( ! $product ) return;
	$img_id  = $product->get_image_id();
	$img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'wk-product-card' ) : wc_placeholder_img_src();
	?>
	<script>
	(function(){
		var KEY  = 'wk_recently_viewed';
		var item = {
			id:    <?php echo absint($post->ID); ?>,
			title: <?php echo wp_json_encode( $product->get_name() ); ?>,
			url:   <?php echo wp_json_encode( get_permalink($post->ID) ); ?>,
			price: <?php echo wp_json_encode( '₹' . number_format((float)$product->get_price(), 0, '.', ',') ); ?>,
			img:   <?php echo wp_json_encode( $img_url ); ?>
		};
		try {
			var list = JSON.parse(localStorage.getItem(KEY) || '[]');
			list = list.filter(function(i){ return i.id !== item.id; });
			list.unshift(item);
			list = list.slice(0, 12);
			localStorage.setItem(KEY, JSON.stringify(list));
		} catch(e){}
	})();
	</script>
	<?php
}

// Render recently viewed section on PDP
add_action( 'woocommerce_after_single_product', 'wk_recently_viewed_section', 15 );
function wk_recently_viewed_section() {
	if ( ! get_theme_mod('wk_show_recently_viewed', true) ) return;
	?>
	<div id="wk-recently-viewed" class="wk-recently-viewed wk-container" style="display:none;">
		<h3 class="wk-section-title" style="font-size:18px;margin-bottom:20px;">Recently Viewed</h3>
		<div class="wk-recently-viewed__grid" id="wk-rv-grid"></div>
	</div>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		try {
			var list = JSON.parse(localStorage.getItem('wk_recently_viewed') || '[]');
			var currentId = <?php global $post; echo absint($post->ID); ?>;
			list = list.filter(function(i){ return i.id !== currentId; }).slice(0, 6);
			if (list.length < 2) return;
			var grid = document.getElementById('wk-rv-grid');
			var wrap = document.getElementById('wk-recently-viewed');
			if (!grid || !wrap) return;
			list.forEach(function(item) {
				var card = document.createElement('a');
				card.href = item.url;
				card.className = 'wk-rv-card';
				card.innerHTML = '<div class="wk-rv-card__img"><img src="'+item.img+'" alt="'+item.title+'" loading="lazy" /></div>'
					+ '<div class="wk-rv-card__info"><p class="wk-rv-card__title">'+item.title+'</p>'
					+ '<p class="wk-rv-card__price">'+item.price+'</p></div>';
				grid.appendChild(card);
			});
			wrap.style.display = 'block';
		} catch(e) {}
	});
	</script>
	<style>
	.wk-recently-viewed { padding:40px 0; border-top:1px solid var(--line,#e0dbd3); margin-top:40px; }
	.wk-recently-viewed__grid { display:grid; grid-template-columns:repeat(6,1fr); gap:14px; }
	.wk-rv-card { text-decoration:none; color:inherit; }
	.wk-rv-card__img { aspect-ratio:3/4; overflow:hidden; border-radius:6px; background:#f9f9f9; margin-bottom:8px; }
	.wk-rv-card__img img { width:100%; height:100%; object-fit:cover; transition:transform .3s; }
	.wk-rv-card:hover .wk-rv-card__img img { transform:scale(1.04); }
	.wk-rv-card__title { font-size:12px; margin:0 0 4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
	.wk-rv-card__price { font-size:13px; font-weight:600; color:var(--accent,#6B1E3E); margin:0; }
	@media (max-width:767px) { .wk-recently-viewed__grid { grid-template-columns:repeat(3,1fr); } }
	@media (max-width:480px) { .wk-recently-viewed__grid { grid-template-columns:repeat(2,1fr); } }
	</style>
	<?php
}

// ═══════════════════════════════════════════════════════
// 8. WISHLIST SHARE VIA URL
// ═══════════════════════════════════════════════════════

// Add share button to wishlist page
add_action( 'wp_footer', 'wk_wishlist_share_btn_inject' );
function wk_wishlist_share_btn_inject() {
	if ( ! is_page('wishlist') && strpos( $_SERVER['REQUEST_URI'] ?? '', 'wishlist' ) === false ) return;
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		var wishlistSection = document.querySelector('.wk-wishlist, .woocommerce-wishlist, #wk-wishlist-table');
		if (!wishlistSection) return;
		var btn = document.createElement('button');
		btn.className = 'wk-wishlist-share-btn';
		btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Share Wishlist';
		btn.style.cssText = 'background:var(--accent,#6B1E3E);color:#fff;border:none;padding:10px 18px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;margin-bottom:16px;';
		wishlistSection.insertBefore(btn, wishlistSection.firstChild);
		btn.addEventListener('click', function() {
			var ids = [];
			document.querySelectorAll('[data-product-id],[data-product_id]').forEach(function(el) {
				var id = el.dataset.productId || el.dataset.product_id;
				if (id) ids.push(id);
			});
			if (ids.length === 0) { alert('Add some items to your wishlist first!'); return; }
			var shareUrl = window.location.origin + '/wishlist/?shared=' + ids.join(',');
			navigator.clipboard && navigator.clipboard.writeText(shareUrl).then(function() {
				btn.textContent = '✅ Link Copied!';
				setTimeout(function(){ btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Share Wishlist'; }, 2500);
			});
		});
	});
	</script>
	<?php
}

// Handle shared wishlist URL — show products from shared list
add_action( 'wp', 'wk_wishlist_show_shared' );
function wk_wishlist_show_shared() {
	if ( ! isset($_GET['shared']) ) return;
	if ( ! class_exists('WooCommerce') ) return;
	$ids = array_filter( array_map('absint', explode(',', sanitize_text_field($_GET['shared']))) );
	if ( empty($ids) ) return;

	add_action( 'wp_footer', function() use ($ids) {
		$products_html = '';
		foreach ( $ids as $pid ) {
			$product = wc_get_product( $pid );
			if ( ! $product ) continue;
			$img_id  = $product->get_image_id();
			$img_url = $img_id ? wp_get_attachment_image_url($img_id, 'wk-product-card') : wc_placeholder_img_src();
			$price   = '&#8377;' . number_format( (float)$product->get_price(), 0, '.', ',' );
			$products_html .= '<a href="' . esc_url(get_permalink($pid)) . '" class="wk-shared-wl-card">';
			$products_html .= '<img src="' . esc_url($img_url) . '" loading="lazy" alt="' . esc_attr($product->get_name()) . '" />';
			$products_html .= '<p class="wk-shared-wl-title">' . esc_html($product->get_name()) . '</p>';
			$products_html .= '<p class="wk-shared-wl-price">' . $price . '</p></a>';
		}
		if ( ! $products_html ) return;
		echo '<div id="wk-shared-wishlist-banner" class="wk-shared-wishlist-banner">';
		echo '<button onclick="document.getElementById(\'wk-shared-wishlist-banner\').remove();" style="position:absolute;top:10px;right:14px;background:none;border:none;font-size:20px;cursor:pointer;color:#888;">×</button>';
		echo '<p>💝 Someone shared their wishlist with you!</p>';
		echo '<div class="wk-shared-wishlist-grid">' . $products_html . '</div>';
		echo '</div>';
		echo '<style>
		.wk-shared-wishlist-banner{position:relative;background:#fff8f8;border-top:3px solid var(--accent,#6B1E3E);padding:24px 20px;text-align:center;}
		.wk-shared-wishlist-banner>p{font-size:16px;font-weight:600;color:var(--accent,#6B1E3E);margin:0 0 16px;}
		.wk-shared-wishlist-grid{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;}
		.wk-shared-wl-card{text-decoration:none;color:inherit;text-align:center;width:140px;}
		.wk-shared-wl-card img{width:140px;height:185px;object-fit:cover;border-radius:6px;margin-bottom:6px;display:block;}
		.wk-shared-wl-title{font-size:12px;margin:0 0 4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
		.wk-shared-wl-price{font-size:13px;font-weight:600;color:var(--accent,#6B1E3E);margin:0;}
		</style>';
	} );
}

// ═══════════════════════════════════════════════════════
// 9. CUSTOMER PHOTO UPLOAD IN REVIEWS
// ═══════════════════════════════════════════════════════

// Add photo upload field to review form
add_filter( 'comment_form_fields', 'wk_review_photo_field' );
function wk_review_photo_field( $fields ) {
	if ( ! is_singular('product') ) return $fields;
	if ( ! class_exists('WooCommerce') ) return $fields;
	if ( ! get_theme_mod('wk_reviews_photo_upload', true) ) return $fields;

	$photo_field = '<div class="wk-review-photo-field">
		<label for="wk_review_photos" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">
			📷 Add Photos <span style="font-weight:400;color:#888;">(optional, max 3 photos, 2MB each)</span>
		</label>
		<input type="file" name="wk_review_photos[]" id="wk_review_photos" accept="image/*" multiple
		       style="font-size:13px;" onchange="wkPreviewPhotos(this)" />
		<div id="wk-photo-preview" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;"></div>
	</div>
	<script>
	function wkPreviewPhotos(input) {
		var prev = document.getElementById("wk-photo-preview");
		prev.innerHTML = "";
		var files = Array.prototype.slice.call(input.files, 0, 3);
		files.forEach(function(file) {
			var reader = new FileReader();
			reader.onload = function(e) {
				var img = document.createElement("img");
				img.src = e.target.result;
				img.style.cssText = "width:64px;height:64px;object-fit:cover;border-radius:4px;border:1px solid #ddd;";
				prev.appendChild(img);
			};
			reader.readAsDataURL(file);
		});
	}
	</script>';

	$fields['wk_photos'] = $photo_field;
	return $fields;
}

// Save uploaded photos when review is submitted
add_action( 'comment_post', 'wk_review_save_photos', 10, 2 );
function wk_review_save_photos( $comment_id, $approved ) {
	if ( ! isset($_FILES['wk_review_photos']) ) return;
	if ( ! function_exists('wp_handle_upload') ) require_once ABSPATH . 'wp-admin/includes/file.php';

	$files    = $_FILES['wk_review_photos'];
	$uploaded = [];
	$max      = 3;

	for ( $i = 0; $i < min( count($files['name']), $max ); $i++ ) {
		if ( ! $files['name'][$i] ) continue;
		$file = [
			'name'     => $files['name'][$i],
			'type'     => $files['type'][$i],
			'tmp_name' => $files['tmp_name'][$i],
			'error'    => $files['error'][$i],
			'size'     => $files['size'][$i],
		];
		if ( $file['error'] || $file['size'] > 2 * 1024 * 1024 ) continue;

		$result = wp_handle_upload( $file, [ 'test_form' => false ] );
		if ( isset($result['url']) ) $uploaded[] = $result['url'];
	}

	if ( ! empty($uploaded) ) {
		update_comment_meta( $comment_id, '_wk_review_photos', $uploaded );
	}
}

// Display photos in review output
add_filter( 'comment_text', 'wk_review_display_photos', 10, 2 );
function wk_review_display_photos( $text, $comment ) {
	if ( ! $comment ) return $text;
	$photos = get_comment_meta( $comment->comment_ID, '_wk_review_photos', true );
	if ( empty($photos) || ! is_array($photos) ) return $text;

	$html = '<div class="wk-review-photos" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">';
	foreach ( $photos as $url ) {
		$html .= '<a href="' . esc_url($url) . '" target="_blank">';
		$html .= '<img src="' . esc_url($url) . '" loading="lazy" style="width:72px;height:72px;object-fit:cover;border-radius:6px;border:1px solid #ddd;" />';
		$html .= '</a>';
	}
	$html .= '</div>';
	return $text . $html;
}

// Make review form support file uploads
add_filter( 'comment_form_defaults', function( $defaults ) {
	if ( is_singular('product') ) {
		$defaults['class_form'] = ( $defaults['class_form'] ?? '' ) . ' wk-review-form-with-photos';
	}
	return $defaults;
} );
add_action( 'wp_head', function() {
	if ( is_singular('product') ) {
		echo '<script>document.addEventListener("DOMContentLoaded",function(){ var f = document.querySelector("form#commentform"); if(f) f.setAttribute("enctype","multipart/form-data"); });</script>' . "\n";
	}
} );

// ═══════════════════════════════════════════════════════
// 10. FLASH SALE TIMER ON PRODUCT CARDS
// ═══════════════════════════════════════════════════════

add_action( 'woocommerce_after_shop_loop_item', 'wk_flash_sale_card_timer', 12 );
function wk_flash_sale_card_timer() {
	global $product;
	if ( ! $product || ! ( $product instanceof WC_Product ) || ! $product->is_on_sale() ) return;

	$sale_end = get_post_meta( $product->get_id(), '_sale_price_dates_to', true );
	if ( ! $sale_end ) return;

	$end_ts     = (int) $sale_end;
	$now_ts     = time();
	$diff       = $end_ts - $now_ts;
	if ( $diff <= 0 ) return;

	$days  = floor( $diff / 86400 );
	$hours = floor( ($diff % 86400) / 3600 );
	$mins  = floor( ($diff % 3600) / 60 );
	$secs  = $diff % 60;

	echo '<div class="wk-sale-timer" data-end="' . esc_attr($end_ts) . '">';
	echo '<span class="wk-sale-timer__label">Offer ends in</span>';
	echo '<div class="wk-sale-timer__countdown">';
	if ( $days > 0 ) echo '<span>' . $days . '<small>d</small></span>';
	echo '<span class="wk-slt-h">' . sprintf('%02d', $hours) . '<small>h</small></span>';
	echo '<span class="wk-slt-m">' . sprintf('%02d', $mins) . '<small>m</small></span>';
	echo '<span class="wk-slt-s">' . sprintf('%02d', $secs) . '<small>s</small></span>';
	echo '</div></div>';
}

// JS to tick down the timers
add_action( 'wp_footer', 'wk_flash_sale_timer_js' );
function wk_flash_sale_timer_js() {
	if ( ! is_shop() && ! is_product_category() && ! is_product_tag() && ! is_singular('product') ) return;
	?>
	<style>
	.wk-sale-timer { background:#fff0f0; border-radius:4px; padding:5px 8px; margin-top:5px; display:inline-flex; align-items:center; gap:6px; }
	.wk-sale-timer__label { font-size:10px; color:#dc2626; font-weight:600; white-space:nowrap; }
	.wk-sale-timer__countdown { display:flex; gap:3px; }
	.wk-sale-timer__countdown span { background:#dc2626; color:#fff; border-radius:3px; padding:2px 4px; font-size:11px; font-weight:700; min-width:22px; text-align:center; }
	.wk-sale-timer__countdown small { font-size:8px; margin-left:1px; font-weight:400; }
	</style>
	<script>
	(function(){
		function pad(n){ return n<10?'0'+n:''+n; }
		function tick() {
			var now = Math.floor(Date.now()/1000);
			document.querySelectorAll('.wk-sale-timer[data-end]').forEach(function(el) {
				var end  = parseInt(el.dataset.end, 10);
				var diff = end - now;
				if (diff <= 0) { el.remove(); return; }
				var d = Math.floor(diff/86400);
				var h = Math.floor((diff%86400)/3600);
				var m = Math.floor((diff%3600)/60);
				var s = diff % 60;
				var hEl = el.querySelector('.wk-slt-h');
				var mEl = el.querySelector('.wk-slt-m');
				var sEl = el.querySelector('.wk-slt-s');
				if (hEl) hEl.innerHTML = pad(h) + '<small>h</small>';
				if (mEl) mEl.innerHTML = pad(m) + '<small>m</small>';
				if (sEl) sEl.innerHTML = pad(s) + '<small>s</small>';
			});
		}
		setInterval(tick, 1000);
	})();
	</script>
	<?php
}

// ═══════════════════════════════════════════════════════
// 11. IN-SESSION CART ABANDONMENT REMINDER
// ═══════════════════════════════════════════════════════

add_action( 'wp_footer', 'wk_cart_reminder_html' );
function wk_cart_reminder_html() {
	if ( ! class_exists('WooCommerce') ) return;
	if ( function_exists('is_cart') && ( is_cart() || is_checkout() ) ) return;
	if ( ! get_theme_mod('wk_cart_reminder_enabled', true) ) return;
	?>
	<div id="wk-cart-reminder" class="wk-cart-reminder" style="display:none;" role="alert">
		<div class="wk-cart-reminder__inner">
			<span class="wk-cart-reminder__icon">🛒</span>
			<div class="wk-cart-reminder__text">
				<strong id="wk-cr-heading">You left something behind!</strong>
				<p id="wk-cr-body">You have items in your cart.</p>
			</div>
			<a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="wk-cart-reminder__btn">View Cart</a>
			<button class="wk-cart-reminder__close" onclick="document.getElementById('wk-cart-reminder').style.display='none';" aria-label="Close">×</button>
		</div>
	</div>
	<style>
	.wk-cart-reminder { position:fixed; bottom:20px; left:50%; transform:translateX(-50%); z-index:9999; max-width:480px; width:calc(100% - 32px); background:#fff; border-radius:10px; box-shadow:0 8px 32px rgba(0,0,0,.18); border-left:4px solid var(--accent,#6B1E3E); animation:wk-cr-in .4s ease; }
	@keyframes wk-cr-in { from { transform:translateX(-50%) translateY(20px); opacity:0; } to { transform:translateX(-50%) translateY(0); opacity:1; } }
	.wk-cart-reminder__inner { display:flex; align-items:center; gap:12px; padding:14px 16px; }
	.wk-cart-reminder__icon { font-size:24px; flex-shrink:0; }
	.wk-cart-reminder__text { flex:1; min-width:0; }
	.wk-cart-reminder__text strong { font-size:13px; display:block; }
	.wk-cart-reminder__text p { font-size:12px; color:#666; margin:2px 0 0; }
	.wk-cart-reminder__btn { background:var(--accent,#6B1E3E); color:#fff; border-radius:6px; padding:8px 14px; font-size:12px; font-weight:600; text-decoration:none; white-space:nowrap; flex-shrink:0; }
	.wk-cart-reminder__close { background:none; border:none; font-size:20px; color:#aaa; cursor:pointer; padding:0 4px; flex-shrink:0; }
	</style>
	<script>
	(function(){
		var CR_HIDE = 'wk_cr_hidden';
		if (sessionStorage.getItem(CR_HIDE)) return;
		var cartCount = <?php echo (class_exists('WooCommerce') && WC()->cart !== null) ? (int)WC()->cart->get_cart_contents_count() : 0; ?>;
		if (cartCount === 0) return;
		var banner = document.getElementById('wk-cart-reminder');
		if (!banner) return;
		var heading = document.getElementById('wk-cr-heading');
		var body    = document.getElementById('wk-cr-body');
		if (heading) heading.textContent = 'Your cart is waiting!';
		if (body) body.textContent = cartCount + ' item' + (cartCount>1?'s':'') + ' ready to check out.';
		// Show after user has been browsing for 30 seconds
		setTimeout(function(){
			if (sessionStorage.getItem(CR_HIDE)) return;
			banner.style.display = 'block';
		}, 30000);
		// Auto-hide after 8 seconds
		setTimeout(function(){
			banner.style.display = 'none';
			sessionStorage.setItem(CR_HIDE, '1');
		}, 38000);
		banner.querySelector('.wk-cart-reminder__close').addEventListener('click', function(){
			sessionStorage.setItem(CR_HIDE, '1');
		});
	})();
	</script>
	<?php
}

// ═══════════════════════════════════════════════════════
// 12. PREPAID DISCOUNT BADGE AT CHECKOUT
// ═══════════════════════════════════════════════════════

// Show prepaid discount notice on checkout page
add_action( 'woocommerce_before_checkout_form', 'wk_prepaid_notice', 5 );
function wk_prepaid_notice() {
	if ( ! class_exists('WooCommerce') ) return;
	if ( ! get_theme_mod('wk_prepaid_enabled', true) ) return;
	$discount = get_theme_mod( 'wk_prepaid_discount', '5' );
	$coupon   = get_theme_mod( 'wk_prepaid_coupon', '' );
	?>
	<div class="wk-prepaid-notice" id="wk-prepaid-notice">
		<div class="wk-prepaid-notice__icon">💳</div>
		<div class="wk-prepaid-notice__text">
			<strong>Get <?php echo esc_html($discount); ?>% off on Prepaid Orders</strong>
			<span>Pay via UPI / Card / Net Banking and save instantly<?php echo $coupon ? ' — use code <b>' . esc_html($coupon) . '</b>' : ''; ?></span>
		</div>
	</div>
	<style>
	.wk-prepaid-notice { display:flex; align-items:center; gap:12px; background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:12px 16px; margin-bottom:20px; }
	.wk-prepaid-notice__icon { font-size:22px; flex-shrink:0; }
	.wk-prepaid-notice__text { font-size:13px; color:#166534; }
	.wk-prepaid-notice__text strong { display:block; font-weight:700; margin-bottom:2px; }
	#wk-prepaid-notice.cod-selected { background:#fef9c3; border-color:#fbbf24; }
	#wk-prepaid-notice.cod-selected .wk-prepaid-notice__text { color:#92400e; }
	#wk-prepaid-notice.cod-selected .wk-prepaid-notice__text strong::before { content:'⚠️ '; }
	</style>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		function updateNotice() {
			var codRadio = document.querySelector('#payment_method_cod');
			var notice   = document.getElementById('wk-prepaid-notice');
			if (!notice) return;
			if (codRadio && codRadio.checked) {
				notice.classList.add('cod-selected');
				notice.querySelector('strong').textContent = 'Switch to Prepaid for <?php echo esc_js($discount); ?>% extra off!';
			} else {
				notice.classList.remove('cod-selected');
				notice.querySelector('strong').textContent = 'Get <?php echo esc_js($discount); ?>% off on Prepaid Orders';
			}
		}
		document.body.addEventListener('change', function(e) {
			if (e.target && e.target.name === 'payment_method') updateNotice();
		});
		document.body.addEventListener('updated_checkout', updateNotice);
	});
	</script>
	<?php
}

// Customizer for prepaid discount
add_action( 'customize_register', 'wk_prepaid_customizer' );
function wk_prepaid_customizer( $wp_customize ) {
	$wp_customize->add_section( 'wk_prepaid', [
		'title'    => '💳 Prepaid Discount',
		'panel'    => 'wk_panel',
		'priority' => 75,
	] );
	$s = [
		[ 'wk_prepaid_enabled',  'Enable Prepaid Discount Notice', 'checkbox', true  ],
		[ 'wk_prepaid_discount', 'Discount % (display only)',      'text',     '5'   ],
		[ 'wk_prepaid_coupon',   'Auto-apply Coupon Code',         'text',     ''    ],
	];
	foreach ( $s as $item ) {
		$wp_customize->add_setting( $item[0], [ 'default' => $item[3], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
		$wp_customize->add_control( $item[0], [ 'label' => $item[1], 'section' => 'wk_prepaid', 'type' => $item[2] ] );
	}
}
