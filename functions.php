<?php
/**
 * WhiteKurti Theme — functions.php
 * All WC calls guarded. if(!function_exists) throughout. No fatal errors.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Force INR (₹) currency ──────────────────────────────────────────────────
add_filter( 'woocommerce_currency',        function() { return 'INR'; } );
add_filter( 'woocommerce_currency_symbol', function( $symbol, $currency ) { return '₹'; }, 10, 2 );



// ── Force INR price formatting everywhere ─────────────────────────────────────
add_filter( 'woocommerce_price_format', function( $format, $currency_pos ) {
	return '%1$s%2$s'; // ₹ always before price, no space
}, 10, 2 );
// Force thousands separator as comma (Indian style)
add_filter( 'wc_price_args', function( $args ) {
	$args['thousand_separator'] = ',';
	$args['decimal_separator']  = '.';
	$args['decimals']           = 0; // No paise on display
	return $args;
} );
// ── Always load review AJAX handler ──────────────────────────────────────────
define( 'WK_VERSION', '1.9.0' );
define( 'WK_DIR',     get_template_directory() );
define( 'WK_URI',     get_template_directory_uri() );

// ─── THEME SETUP ─────────────────────────────────────────────────────────────
function wk_setup() {
	load_theme_textdomain( 'whitekurti', WK_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', [ 'search-form','comment-form','comment-list','gallery','caption','script','style' ] );
	add_theme_support( 'custom-logo', [ 'height' => 60, 'width' => 200, 'flex-height' => true, 'flex-width' => true ] );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	// Page Builder Support
	add_theme_support( 'elementor' );
	// WooCommerce — declare even if WC not yet loaded
	add_theme_support( 'woocommerce', [
		'thumbnail_image_width' => 600,
		'single_image_width'    => 900,
		'product_grid'          => [ 'default_rows' => 4, 'default_columns' => 2, 'min_columns' => 2, 'max_columns' => 4 ],
	] );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	register_nav_menus( [
		'primary' => __( 'Primary Navigation', 'whitekurti' ),
		'footer'  => __( 'Footer Navigation', 'whitekurti' ),
	] );
	add_image_size( 'wk-product-card',  600,  800, true  );
	add_image_size( 'wk-product-hero',  900, 1200, true  );
	add_image_size( 'wk-category-card', 600,  750, true  );
	add_image_size( 'wk-hero-banner',  1440,  800, false );
}
add_action( 'after_setup_theme', 'wk_setup' );

// ─── ENQUEUE ──────────────────────────────────────────────────────────────────
function wk_enqueue() {
	$font_preset    = get_theme_mod( 'wk_type_pairing', 'editorial' );
	$custom_display = get_theme_mod( 'wk_font_display', '' );
	$custom_body    = get_theme_mod( 'wk_font_body', '' );
	$google_fonts   = [
		'editorial' => 'Playfair+Display:ital,wght@0,400;0,500;0,600;1,400|Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400',
		'modern'    => 'Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400',
		'soft'      => 'Playfair+Display:ital,wght@0,400;0,500;0,600;1,400|Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400',
	];
	if ( $custom_display || $custom_body ) {
		$f = [];
		if ( $custom_display ) $f[] = str_replace( ' ', '+', $custom_display ) . ':ital,wght@0,300;0,400;0,500;1,400';
		if ( $custom_body )    $f[] = str_replace( ' ', '+', $custom_body )    . ':wght@300;400;500;600';
		$gurl = 'https://fonts.googleapis.com/css2?family=' . implode( '&family=', $f ) . '&display=swap';
	} else {
		$gurl = 'https://fonts.googleapis.com/css2?family=' . ( $google_fonts[ $font_preset ] ?? $google_fonts['editorial'] ) . '&display=swap';
	}
	wp_enqueue_style( 'wk-google-fonts', $gurl, [], null );
	wp_enqueue_style( 'wk-main', WK_URI . '/assets/css/main.css', [], WK_VERSION );
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style( 'wk-woocommerce', WK_URI . '/assets/css/woocommerce.css', [ 'wk-main' ], WK_VERSION );
	}
	wp_enqueue_script( 'wk-main', WK_URI . '/assets/js/main.js', [ 'jquery' ], WK_VERSION, true );
	// FIX BUG 1: every WC call wrapped
	$wc = class_exists( 'WooCommerce' );
	wp_localize_script( 'wk-main', 'wk_params', [
		'ajax_url'        => admin_url( 'admin-ajax.php' ),
		'cart_url'        => $wc ? wc_get_cart_url() : home_url('/cart'),
		'checkout_url'    => $wc ? wc_get_checkout_url() : home_url('/checkout'),
		'nonce'           => wp_create_nonce( 'wk-nonce' ),
		'is_woocommerce'  => $wc ? '1' : '0',
		'currency_symbol' => $wc ? get_woocommerce_currency_symbol() : '&#8377;',
		'i18n_added'      => __( 'Added to cart', 'whitekurti' ),
		'i18n_error'      => __( 'Something went wrong.', 'whitekurti' ),
		'shop_url'        => $wc ? wc_get_page_permalink('shop') : home_url('/shop'),
	] );
}
add_action( 'wp_enqueue_scripts', 'wk_enqueue' );

// ─── CSS VARIABLES IN <head> ──────────────────────────────────────────────────
function wk_output_css_vars() {
	$token_map = [
		'wk_color_bg'       => '--bg',
		'wk_color_surface'  => '--surface',
		'wk_color_surface2' => '--surface-2',
		'wk_color_ink'      => '--ink',
		'wk_color_inksoft'  => '--ink-soft',
		'wk_color_inkmute'  => '--ink-mute',
		'wk_color_line'     => '--line',
		'wk_color_accent'   => '--accent',
		'wk_color_sale'     => '--sale',
	];
	$vars = [];
	foreach ( $token_map as $mod => $cssvar ) {
		$v = get_theme_mod( $mod, '' );
		if ( $v ) $vars[] = $cssvar . ':' . sanitize_hex_color( $v ) . ';';
	}
	$fd = get_theme_mod( 'wk_font_display', '' );
	$fb = get_theme_mod( 'wk_font_body', '' );
	if ( $fd ) $vars[] = "--font-display:'" . esc_attr( $fd ) . "',serif;";
	if ( $fb ) $vars[] = "--font-body:'"    . esc_attr( $fb ) . "',sans-serif;";
	if ( $vars ) {
		echo '<style id="wk-css-vars">:root{' . implode( '', $vars ) . '}</style>' . "\n";
	}
}
add_action( 'wp_head', 'wk_output_css_vars', 5 );

// ─── BODY CLASSES ────────────────────────────────────────────────────────────
function wk_body_classes( $classes ) {
	$classes[] = 'wk-brand-'   . sanitize_html_class( get_theme_mod( 'wk_brand_mode', 'white' ) );
	$classes[] = 'wk-palette-' . sanitize_html_class( get_theme_mod( 'wk_palette', 'ivory' ) );
	$classes[] = 'wk-type-'    . sanitize_html_class( get_theme_mod( 'wk_type_pairing', 'editorial' ) );
	return $classes;
}
add_filter( 'body_class', 'wk_body_classes' );

// ─── INCLUDES ─────────────────────────────────────────────────────────────────
require WK_DIR . '/inc/admin-hub.php';
require WK_DIR . '/inc/template-functions.php';
require WK_DIR . '/inc/customizer.php';
require WK_DIR . '/inc/theme-setup.php';
require WK_DIR . '/inc/block-patterns.php';
require WK_DIR . '/inc/reviews-admin.php';
require WK_DIR . '/inc/features-pro.php';
require WK_DIR . '/inc/fake-notifications-admin.php';
require WK_DIR . '/inc/whatsapp-admin.php';
require WK_DIR . '/inc/bottom-nav-admin.php';
require WK_DIR . '/inc/social-media.php';
require WK_DIR . '/inc/seo.php';
require WK_DIR . '/inc/seo-advanced.php';
require WK_DIR . '/inc/google-shopping-feed.php';
require WK_DIR . '/inc/hero-section.php';
require WK_DIR . '/inc/nav-manager.php';
require WK_DIR . '/inc/cookie-consent.php';
require WK_DIR . '/inc/swatches.php';
require WK_DIR . '/inc/mobile-filters.php';
require WK_DIR . '/inc/product-video.php';
require WK_DIR . '/inc/welcome-popup.php';
require WK_DIR . '/inc/conversion-features.php';
require WK_DIR . '/inc/email-settings.php';
require WK_DIR . '/inc/product-badges.php';
require WK_DIR . '/inc/product-zoom.php';
require WK_DIR . '/inc/size-guide.php';
require WK_DIR . '/inc/pincode-delivery.php';
require WK_DIR . '/inc/complete-the-look.php';
require WK_DIR . '/inc/quick-view.php';
require WK_DIR . '/inc/cart-enhancements.php';
require WK_DIR . '/inc/checkout-trust.php';
require WK_DIR . '/inc/exit-intent.php';
require WK_DIR . '/inc/ajax-search.php';
require WK_DIR . '/inc/mega-menu.php';
require WK_DIR . '/inc/my-account.php';
require WK_DIR . '/inc/wishlist.php';
require WK_DIR . '/inc/back-in-stock.php';
require WK_DIR . '/inc/homepage-sections.php';
require WK_DIR . '/inc/performance.php';

// WooCommerce file only after plugins load
add_action( 'after_setup_theme', function() {
	if ( class_exists( 'WooCommerce' ) ) {
		require_once WK_DIR . '/inc/woocommerce.php';
	}
}, 20 );

// ─── WIDGET AREAS ─────────────────────────────────────────────────────────────
function wk_widgets_init() {
	$a = [ 'before_widget' => '<div id="%1$s" class="widget %2$s">', 'after_widget' => '</div>', 'before_title' => '<h3 class="widget-title">', 'after_title' => '</h3>' ];
	register_sidebar( $a + [ 'name' => __( 'Shop Sidebar', 'whitekurti' ), 'id' => 'shop-sidebar' ] );
	register_sidebar( $a + [ 'name' => __( 'Footer Col 1', 'whitekurti' ), 'id' => 'footer-1' ] );
	register_sidebar( $a + [ 'name' => __( 'Footer Col 2', 'whitekurti' ), 'id' => 'footer-2' ] );
}
add_action( 'widgets_init', 'wk_widgets_init' );

// ─── AJAX HANDLERS (only registered when WC is active) FIX BUG 7 ─────────────
add_action( 'wp_loaded', function() {
	if ( ! class_exists( 'WooCommerce' ) ) return;
	add_action( 'wp_ajax_wk_add_to_cart',        'wk_ajax_add_to_cart' );
	add_action( 'wp_ajax_nopriv_wk_add_to_cart', 'wk_ajax_add_to_cart' );
	add_action( 'wp_ajax_wk_get_cart',            'wk_ajax_get_cart' );
	add_action( 'wp_ajax_nopriv_wk_get_cart',     'wk_ajax_get_cart' );
	// Fragment filter
	add_filter( 'woocommerce_add_to_cart_fragments', 'wk_cart_fragments' );
	// Remove default WC styles
	add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
} );

function wk_ajax_add_to_cart() {
	if ( ! check_ajax_referer( 'wk-nonce', 'nonce', false ) ) {
		wp_send_json_error( [ 'message' => 'Invalid nonce' ] ); return;
	}
	$product_id   = absint( $_POST['product_id'] ?? 0 );
	$variation_id = absint( $_POST['variation_id'] ?? 0 );
	$quantity     = max( 1, absint( $_POST['quantity'] ?? 1 ) );
	if ( ! $product_id ) { wp_send_json_error( [ 'message' => 'Invalid product' ] ); return; }
	$added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );
	if ( $added ) {
		WC()->cart->calculate_totals();
		ob_start(); woocommerce_mini_cart(); $mini = ob_get_clean();
		wp_send_json_success( [
			'cart_count'   => WC()->cart->get_cart_contents_count(),
			'cart_total'   => WC()->cart->get_cart_total(),
			'mini_cart'    => $mini,
			'fragments'    => apply_filters( 'woocommerce_add_to_cart_fragments', [] ),
			'cart_hash'    => apply_filters( 'woocommerce_add_to_cart_hash', WC()->cart->get_cart_hash() ?: '' ),
			'play_sound'   => 'add_to_cart',
		] );
	} else {
		$notices = wc_get_notices( 'error' );
		wc_clear_notices();
		wp_send_json_error( [ 'message' => $notices ? $notices[0]['notice'] : 'Could not add to cart' ] );
	}
}

function wk_ajax_get_cart() {
	// FIX BUG 8: this only runs via AJAX when WC is definitely active
	ob_start(); woocommerce_mini_cart(); $mini = ob_get_clean();
	wp_send_json_success( [
		'mini_cart'  => $mini,
		'cart_count' => WC()->cart->get_cart_contents_count(),
		'cart_total' => WC()->cart->get_cart_total(),
	] );
}

function wk_cart_fragments( $fragments ) {
	$count = WC()->cart->get_cart_contents_count();
	$fragments['.wk-cart-count'] = '<span class="wk-cart-count' . ( $count ? ' has-items' : '' ) . '">' . absint( $count ) . '</span>';
	ob_start(); woocommerce_mini_cart();
	$fragments['.wk-cart-drawer-items'] = '<div class="wk-cart-drawer-items">' . ob_get_clean() . '</div>';
	return $fragments;
}

// ─── MISC ─────────────────────────────────────────────────────────────────────
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'excerpt_length', function() { return 24; } );
add_filter( 'excerpt_more',   function() { return '&hellip;'; } );

// ─── AUTO-CREATE ESSENTIAL PAGES ON THEME ACTIVATION ─────────────────────────
function wk_create_essential_pages() {
	if ( get_option( 'wk_pages_created' ) ) return;

	$pages = [
		[
			'title'   => 'Contact Us',
			'slug'    => 'contact',
			'content' => '[contact-form-7 id="contact-form" title="Contact Form"]',
		],
		[
			'title'   => 'Size Guide',
			'slug'    => 'size-guide',
			'content' => '<h2>Size Guide</h2><p>Please refer to our size chart below to find your perfect fit.</p>',
		],
		[
			'title'   => 'Shipping Policy',
			'slug'    => 'shipping',
			'content' => '<h2>Shipping Policy</h2><p>We offer free shipping on orders above ₹2,000. Orders are delivered within 4–7 business days.</p>',
		],
		[
			'title'   => 'Returns & Exchange',
			'slug'    => 'returns',
			'content' => '<h2>Returns & Exchange</h2><p>Easy 7-day returns with free pickup. Items must be unworn and in original packaging.</p>',
		],
		[
			'title'   => 'Privacy Policy',
			'slug'    => 'privacy-policy',
			'content' => '<h2>Privacy Policy</h2><p>Your privacy is important to us. We do not share your personal data with third parties.</p>',
		],
		[
			'title'   => 'Terms & Conditions',
			'slug'    => 'terms',
			'content' => '<h2>Terms & Conditions</h2><p>By using this website, you agree to our terms of service.</p>',
		],
		[
			'title'   => 'About Us',
			'slug'    => 'about',
			'content' => '<h2>About WhiteKurti</h2><p>Elegant monochrome Indian wear designed for comfort, sophistication, and timeless everyday luxury.</p>',
		],
	];

	foreach ( $pages as $p ) {
		if ( ! get_page_by_path( $p['slug'] ) ) {
			wp_insert_post( [
				'post_title'   => $p['title'],
				'post_name'    => $p['slug'],
				'post_content' => $p['content'],
				'post_status'  => 'publish',
				'post_type'    => 'page',
			] );
		}
	}

	update_option( 'wk_pages_created', true );
}
add_action( 'after_switch_theme', 'wk_create_essential_pages' );
add_action( 'init', function() {
	// Also run on init in case after_switch_theme was missed
	if ( ! get_option( 'wk_pages_created' ) ) {
		wk_create_essential_pages();
	}
} );

// ─── REGISTER WC ACCOUNT ENDPOINT MENUS ─────────────────────────────────────
function wk_account_menu_items( $items ) {
	$new_items = [];
	foreach ( $items as $key => $label ) {
		$new_items[ $key ] = $label;
		// Insert Wishlist after dashboard if YITH Wishlist active
		if ( $key === 'dashboard' && function_exists( 'YITH_WCWL' ) ) {
			$new_items['wishlist'] = __( 'Wishlist', 'whitekurti' );
		}
	}
	return $new_items;
}
add_filter( 'woocommerce_account_menu_items', 'wk_account_menu_items' );

// ─── NEWSLETTER SIGNUP HANDLER ───────────────────────────────────────────────
// Cached newsletter subscriber getter (standalone, not nested)
function wk_get_newsletter_subscribers( $default = [] ) {
	static $cache = null;
	if ( $cache === null ) {
		$cache = get_option( 'wk_newsletter_subscribers', $default );
	}
	return $cache;
}

function wk_handle_newsletter_signup() {
	if ( ! isset($_POST['wk_nl_nonce']) || ! wp_verify_nonce($_POST['wk_nl_nonce'], 'wk_newsletter') ) {
		wp_safe_redirect( add_query_arg('nl', 'error', wp_get_referer() ?: home_url()) );
		exit;
	}
	$email = sanitize_email( $_POST['email'] ?? '' );
	if ( ! is_email($email) ) {
		wp_safe_redirect( add_query_arg('nl', 'invalid', wp_get_referer() ?: home_url()) );
		exit;
	}
	$subscribers = wk_get_newsletter_subscribers( [] );
	$existing    = array_column($subscribers, 'email');
	if ( in_array($email, $existing) ) {
		wp_safe_redirect( add_query_arg('nl', 'already', wp_get_referer() ?: home_url()) );
		exit;
	}
	$subscribers[] = [
		'email'    => $email,
		'date'     => current_time('Y-m-d H:i:s'),
		'page'     => wp_get_referer() ?: home_url(),
		'ip'       => $_SERVER['REMOTE_ADDR'] ?? '',
		'status'   => 'active',
	];
	update_option('wk_newsletter_subscribers', $subscribers);
	// Send welcome email
	$site_name = get_bloginfo('name');
	$subject   = 'Welcome! Your 10% discount code is inside 🎁';
	$msg       = "Hi there!\n\nThank you for subscribing to {$site_name}.\n\nHere's your 10% discount code: WELCOME10\n\nUse it on your first order at " . home_url() . "\n\nHappy shopping!\nThe {$site_name} Team";
	wp_mail($email, $subject, $msg);
	wp_safe_redirect( add_query_arg('nl', 'success', wp_get_referer() ?: home_url()) );
	exit;
}
add_action('admin_post_wk_newsletter_signup',        'wk_handle_newsletter_signup');
add_action('admin_post_nopriv_wk_newsletter_signup', 'wk_handle_newsletter_signup');

// ─── NEWSLETTER ADMIN PAGE ───────────────────────────────────────────────────
add_action('admin_menu', function() {
	add_menu_page('Newsletter','📧 Newsletter','manage_options','wk-newsletter','wk_newsletter_admin_page','dashicons-email-alt',58);
});

function wk_newsletter_admin_page() {
	// Handle delete
	if ( isset($_GET['delete_email']) && check_admin_referer('wk_nl_delete') ) {
		$email = sanitize_email($_GET['delete_email']);
		$subs  = get_option('wk_newsletter_subscribers',[]);
		$subs  = array_filter($subs, function($s) use ($email) { return $s['email'] !== $email; });
		update_option('wk_newsletter_subscribers', array_values($subs));
		echo '<div class="notice notice-success"><p>Subscriber removed.</p></div>';
	}
	// Handle export
	if ( isset($_POST['export_csv']) && check_admin_referer('wk_nl_export') ) {
		$subs = get_option('wk_newsletter_subscribers',[]);
		header('Content-Type:text/csv');
		header('Content-Disposition:attachment;filename="newsletter-'.date('Y-m-d').'.csv"');
		echo "Email,Date Subscribed,Source Page,Status\n";
		foreach ($subs as $s) {
			echo '"'.($s['email']??'').'","'.($s['date']??'').'","'.($s['page']??'').'","'.($s['status']??'active')."\"\n";
		}
		exit;
	}

	$subs  = get_option('wk_newsletter_subscribers',[]);
	$total = count($subs);
	$active = count(array_filter($subs, function($s) { return (isset($s['status']) ? $s['status'] : 'active') === 'active'; }));
	?>
	<div class="wrap" style="max-width:960px;">
	<h1>📧 Newsletter Subscribers</h1>
	<div style="display:flex;gap:20px;margin:16px 0 24px;flex-wrap:wrap;">
		<div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px 28px;text-align:center;min-width:120px;">
			<div style="font-size:32px;font-weight:700;color:#6B1E3E;"><?php echo $total; ?></div>
			<div style="font-size:12px;color:#666;text-transform:uppercase;letter-spacing:.05em;">Total</div>
		</div>
		<div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px 28px;text-align:center;min-width:120px;">
			<div style="font-size:32px;font-weight:700;color:#27ae60;"><?php echo $active; ?></div>
			<div style="font-size:12px;color:#666;text-transform:uppercase;letter-spacing:.05em;">Active</div>
		</div>
	</div>

	<div style="display:flex;gap:12px;margin-bottom:20px;align-items:center;flex-wrap:wrap;">
		<form method="post"><?php wp_nonce_field('wk_nl_export'); ?><input type="hidden" name="export_csv" value="1" /><input type="submit" class="button" value="📥 Export CSV" /></form>
	</div>

	<?php if (empty($subs)) : ?>
	<div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:40px;text-align:center;color:#888;">
		<p style="font-size:16px;">No subscribers yet.</p>
		<p>Once people sign up from your website footer, they'll appear here.</p>
	</div>
	<?php else : ?>
	<table class="wp-list-table widefat striped" style="margin-top:0;">
		<thead>
			<tr>
				<th style="width:40px;">#</th>
				<th>Email Address</th>
				<th>Date Subscribed</th>
				<th>Status</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach (array_reverse($subs) as $i => $s) : ?>
		<tr>
			<td><?php echo $total - $i; ?></td>
			<td><strong><?php echo esc_html($s['email']); ?></strong></td>
			<td><?php echo esc_html($s['date']??'—'); ?></td>
			<td><span style="background:<?php echo ($s['status']??'active')==='active'?'#e8f8ee':'#f8e8e8'; ?>;color:<?php echo ($s['status']??'active')==='active'?'#27ae60':'#c00'; ?>;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;text-transform:uppercase;"><?php echo esc_html($s['status']??'active'); ?></span></td>
			<td>
				<a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wk-newsletter&delete_email='.urlencode($s['email'])),'wk_nl_delete')); ?>" onclick="return confirm('Remove this subscriber?')" style="color:#c00;font-size:12px;">Remove</a>
			</td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
	</div>
	<?php
}
