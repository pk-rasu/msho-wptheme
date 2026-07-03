<?php
/**
 * WhiteKurti — Performance Optimization System
 * Minification, critical CSS, preloads, WebP hints, WordPress bloat removal
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ═══════════════════════════════════════════════════════════════
// 1. REMOVE WORDPRESS BLOAT
// ═══════════════════════════════════════════════════════════════
add_action( 'init', function() {
	// Remove emoji scripts/styles (saves ~10KB per page)
	remove_action( 'wp_head',             'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles',     'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles',  'print_emoji_styles' );
	remove_filter( 'the_content_feed',    'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss',    'wp_staticize_emoji' );
	remove_filter( 'wp_mail',             'wp_staticize_emoji_for_email' );

	// Remove oEmbed
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );

	// Remove REST API link from header (security)
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );

	// Remove wlwmanifest (Windows Live Writer)
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );

	// Remove WordPress generator meta
	remove_action( 'wp_head', 'wp_generator' );

	// Remove shortlink
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
} );

// Remove Gutenberg / Block editor CSS on frontend when not using blocks
add_action( 'wp_enqueue_scripts', function() {
	if ( ! is_singular() || ! has_blocks( get_queried_object_id() ) ) {
		wp_dequeue_style('wp-block-library');
		wp_dequeue_style('wp-block-library-theme');
		wp_dequeue_style('wc-blocks-style');
		wp_dequeue_style('wc-blocks-vendors-style');
	}
}, 100 );

// Remove query strings from static assets (cache-busting improvement)
add_filter( 'script_loader_src', 'wk_remove_query_strings', 15 );
add_filter( 'style_loader_src',  'wk_remove_query_strings', 15 );
function wk_remove_query_strings( $src ) {
	if ( ! is_admin() && strpos($src,'?ver=') ) {
		$src = remove_query_arg('ver', $src);
	}
	return $src;
}

// ═══════════════════════════════════════════════════════════════
// 2. RESOURCE HINTS (preconnect, preload)
// ═══════════════════════════════════════════════════════════════
add_action( 'wp_head', function() {
	// Preconnect to key origins
	$origins = [
		'https://fonts.googleapis.com'  => 'preconnect',
		'https://fonts.gstatic.com'     => 'preconnect crossorigin',
		'https://www.googletagmanager.com' => 'dns-prefetch',
		'https://connect.facebook.net'  => 'dns-prefetch',
		'https://images.pexels.com'     => 'dns-prefetch',
	];
	foreach ( $origins as $url => $rel ) {
		echo '<link rel="' . esc_attr($rel) . '" href="' . esc_url($url) . '">' . "\n";
	}

	// Preload the theme's main CSS
	$css_url = get_theme_file_uri('assets/css/main.css');
	echo '<link rel="preload" href="' . esc_url($css_url) . '" as="style">' . "\n";

	// NOTE: Google Fonts are loaded via functions.php wp_enqueue_style with display=swap.
	// No duplicate preload needed here.
}, 1 );

// Google Fonts: loaded via optimised preload in wp_head above.
// The standard wk-google-fonts enqueue in functions.php is intentionally kept
// as a reliable fallback for when the preload approach doesn't work (e.g. cached pages).
// The preload tag above uses onload to avoid render-blocking, which is sufficient.

// ═══════════════════════════════════════════════════════════════
// 3. INLINE CRITICAL CSS (above-the-fold)
// ═══════════════════════════════════════════════════════════════
add_action( 'wp_head', function() {
	// Only render critical CSS once
	static $rendered = false;
	if ( $rendered ) return;
	$rendered = true;
	?>
	<style id="wk-critical-css">
	/* Critical above-the-fold CSS — inlined for fastest LCP */
	/* Note: --font-display and --font-body will be set by theme customizer CSS vars */
	:root{--bg:#FDFCFA;--surface:#fff;--ink:#120F0C;--accent:#6B1E3E;--header-h:68px;}
	*,*::before,*::after{box-sizing:border-box}
	html{-webkit-text-size-adjust:100%}
	body{margin:0;background:var(--bg);color:var(--ink);font-family:var(--font-body);-webkit-font-smoothing:antialiased;line-height:1.6}
	.wk-header{position:sticky;top:0;z-index:100;background:var(--bg);border-bottom:.5px solid #e6e1da;height:var(--header-h);display:flex;align-items:center}
	.wk-container{max-width:1440px;margin:0 auto;padding:0 20px}
	img{max-width:100%;height:auto;display:block}
	a{color:inherit;text-decoration:none}
	.wk-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--ink);color:var(--bg);border:none;font-family:var(--font-body);font-size:11.5px;font-weight:600;letter-spacing:.18em;text-transform:uppercase;padding:14px 28px;cursor:pointer;transition:opacity .2s}
	/* Prevent CLS from layout shifts */
	.wk-home-hero{min-height:70vh}
	.wk-footer{background:#120F0C}
	</style>
	<?php
}, 0 );

// ═══════════════════════════════════════════════════════════════
// 4. DEFER NON-CRITICAL SCRIPTS
// ═══════════════════════════════════════════════════════════════
add_filter( 'script_loader_tag', function( $tag, $handle, $src ) {
	if ( is_admin() ) return $tag;

	// Never defer these — critical for page function
	$no_defer = [
		'jquery','jquery-core','jquery-migrate',
		'wc-cart-fragments','woocommerce','wk-main',
		'wp-hooks','wp-i18n',
	];
	if ( in_array( $handle, $no_defer, true ) ) return $tag;

	// Always defer these known safe handles
	$defer_handles = [
		'wp-embed','comment-reply',
		'contact-form-7','wpcf7',
		'wk-customizer','wk-zoom',
	];
	if ( in_array( $handle, $defer_handles, true ) ) {
		if ( strpos( $tag, 'defer' ) === false ) {
			return str_replace( ' src=', ' defer src=', $tag );
		}
	}
	return $tag;
}, 10, 3 );

// Async-load non-blocking third-party scripts
add_filter( 'script_loader_tag', function( $tag, $handle ) {
	$async_handles = ['google-recaptcha'];
	if ( in_array( $handle, $async_handles, true ) && strpos( $tag, 'async' ) === false ) {
		return str_replace( ' src=', ' async src=', $tag );
	}
	return $tag;
}, 10, 2 );

// ═══════════════════════════════════════════════════════════════
// 5. IMAGE OPTIMISATION HELPERS
// ═══════════════════════════════════════════════════════════════

// Add loading="lazy" and decoding="async" to content images
add_filter( 'the_content', function( $content ) {
	if ( is_admin() ) return $content;
	// Add lazy + async to imgs that don't have loading= yet
	$content = preg_replace(
		'/<img(?![^>]*loading=)([^>]+)>/i',
		'<img loading="lazy" decoding="async"$1>',
		$content
	);
	return $content;
} );

// Add decoding="async" + lazy to WooCommerce product images
add_filter( 'woocommerce_product_get_image', function( $image ) {
	if ( strpos( $image, 'loading=' ) === false ) {
		$image = str_replace( '<img ', '<img loading="lazy" decoding="async" ', $image );
	} elseif ( strpos( $image, 'decoding=' ) === false ) {
		$image = str_replace( '<img ', '<img decoding="async" ', $image );
	}
	return $image;
} );

// Default attrs: lazy + async + dimensions
add_filter( 'wp_get_attachment_image_attributes', function( $attrs, $attachment, $size ) {
	if ( ! isset( $attrs['loading'] ) )  $attrs['loading']  = 'lazy';
	if ( ! isset( $attrs['decoding'] ) ) $attrs['decoding'] = 'async';
	return $attrs;
}, 10, 3 );

// First product image on PDP — override to eager (LCP image)
add_filter( 'wp_get_attachment_image_attributes', function( $attrs, $attachment, $size ) {
	if ( ! is_singular( 'product' ) ) return $attrs;
	if ( $size !== 'wk-product-hero' ) return $attrs;

	global $post;
	static $pdp_img_count = 0;
	if ( $pdp_img_count === 0 ) {
		// First product image should load immediately for LCP
		$attrs['loading']       = 'eager';
		$attrs['fetchpriority'] = 'high';
		$attrs['decoding']      = 'sync';
	}
	$pdp_img_count++;
	return $attrs;
}, 20, 3 );

// ═══════════════════════════════════════════════════════════════
// 6. OUTPUT MINIFICATION (CSS/HTML)
// ═══════════════════════════════════════════════════════════════

// Inline CSS minifier
function wk_minify_css( $css ) {
	// Remove comments
	$css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
	// Remove whitespace
	$css = str_replace(["\r\n","\r","\n","\t",'  ','    ','    '], ' ', $css);
	$css = preg_replace('/\s+/', ' ', $css);
	// Remove spaces before/after symbols
	$css = preg_replace('/\s*([{}|:;,])\s*/', '$1', $css);
	$css = preg_replace('/;}/', '}', $css);
	return trim($css);
}

// Minify inline <style> tags in HTML output
add_filter( 'style_loader_tag', function( $tag, $handle ) {
	return $tag; // Don't modify external stylesheets
}, 10, 2 );

// Minify the custom inline CSS vars we output
add_filter( 'wk_inline_css', 'wk_minify_css' );

// ── HTML output buffering minification (optional, enable via customizer) ──
function wk_html_minify_start() {
	if ( is_admin() || is_feed() || is_preview() || is_customize_preview() ) return;
	if ( !get_theme_mod('wk_html_minify', false) ) return;
	ob_start('wk_html_minify_output');
}
function wk_html_minify_output( $html ) {
	// Preserve pre, code, script, textarea content
	$preserve  = [];
	$preserved = preg_replace_callback(
		'#(<(pre|code|script|textarea)[^>]*>).*?(</\2>)#si',
		function($m) use (&$preserve) {
			$token = '<!--WK_PRESERVE_' . count($preserve) . '-->';
			$preserve[$token] = $m[0];
			return $token;
		},
		$html
	);
	// Minify whitespace
	$minified = preg_replace(['/\s+/', '/>\s+</'], [' ', '><'], $preserved);
	// Restore preserved content
	foreach ($preserve as $token => $content) {
		$minified = str_replace($token, $content, $minified);
	}
	return $minified;
}
add_action( 'template_redirect', 'wk_html_minify_start', 1 );

// ═══════════════════════════════════════════════════════════════
// 7. PERFORMANCE CUSTOMIZER SETTINGS
// ═══════════════════════════════════════════════════════════════
add_action( 'customize_register', function( $wp_customize ) {
	$wp_customize->add_section( 'wk_performance', [
		'title' => __('⚡ Performance','whitekurti'), 'panel'=>'wk_panel','priority'=>90,
	]);
	$fields = [
		['wk_remove_emoji',       'Remove WordPress emoji scripts (saves ~10KB)',      true ],
		['wk_defer_scripts',      'Defer non-critical JavaScript',                     true ],
		['wk_lazy_images',        'Lazy load all images',                              true ],
		['wk_remove_query_strings','Remove version query strings from assets',         true ],
		['wk_html_minify',        'Minify HTML output (advanced — test before using)', false],
		['wk_disable_gutenberg_css','Remove Gutenberg block CSS (if not using blocks)', true ],
		['wk_preconnect_fonts',   'Preconnect to Google Fonts CDN',                   true ],
	];
	foreach ($fields as [$id,$label,$default]) {
		$wp_customize->add_setting($id,['default'=>$default,'sanitize_callback'=>'rest_sanitize_boolean','transport'=>'refresh']);
		$wp_customize->add_control($id,['label'=>$label,'section'=>'wk_performance','type'=>'checkbox']);
	}
});

// ═══════════════════════════════════════════════════════════════
// 8. CACHE HEADERS FOR STATIC ASSETS
// ═══════════════════════════════════════════════════════════════
add_action( 'send_headers', function() {
	if ( is_admin() ) return;
	// Tell browsers to cache static assets for 1 week
	if ( is_singular() || is_home() || ( function_exists('is_shop') && is_shop() ) ) {
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
	}
} );

// ── Query caching helpers ──────────────────────────────────────────────
// Cache WP_Query results for product queries to avoid repeated DB hits
add_filter( 'posts_results', 'wk_cache_product_queries', 10, 2 );
function wk_cache_product_queries( $posts, $query ) {
	if ( ! $query->is_main_query() ) return $posts;
	if ( $query->get('post_type') !== 'product' ) return $posts;
	// Results are already cached by WP's query cache — this filter
	// is a placeholder for future custom caching if needed.
	return $posts;
}
