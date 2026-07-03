<?php
/**
 * WhiteKurti — Google Shopping Feed
 * ─────────────────────────────────────────────────────────────────
 * • Generates a live GMC-spec XML feed at /feed/google-shopping.xml
 * • Full WordPress admin panel with step-by-step setup guide
 * • Auto-maps WooCommerce categories to Google Product Categories
 * • Handles variants, sale prices, gallery images, attributes
 * ─────────────────────────────────────────────────────────────────
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ═══════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════
define( 'WK_GSF_VERSION', '1.0.0' );
define( 'WK_GSF_SLUG',    'wk-google-shopping' );
define( 'WK_GSF_OPTION',  'wk_google_shopping_settings' );

// ═══════════════════════════════════════════════════════════════
// 1. REGISTER FEED URL REWRITE
//    Feed lives at: yoursite.com/feed/google-shopping.xml
// ═══════════════════════════════════════════════════════════════
add_action( 'init', 'wk_gsf_register_rewrite' );
function wk_gsf_register_rewrite() {
	add_rewrite_rule( '^feed/google-shopping\.xml$', 'index.php?wk_gsf=1', 'top' );
	add_rewrite_tag( '%wk_gsf%', '([0-9]+)' );
}

add_action( 'after_switch_theme', function() {
	wk_gsf_register_rewrite();
	flush_rewrite_rules();
} );

// Also support ?wk_gsf=1 query param as fallback
add_action( 'parse_request', function( $wp ) {
	if ( isset( $_GET['wk_gsf'] ) && $_GET['wk_gsf'] === '1' ) {
		wk_gsf_serve_feed();
	}
} );

// ═══════════════════════════════════════════════════════════════
// 2. SERVE THE FEED
// ═══════════════════════════════════════════════════════════════
add_action( 'template_redirect', 'wk_gsf_maybe_serve' );
function wk_gsf_maybe_serve() {
	if ( ! get_query_var( 'wk_gsf' ) ) return;
	wk_gsf_serve_feed();
}

function wk_gsf_serve_feed() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		http_response_code( 503 );
		die( '<!-- WooCommerce not active -->' );
	}

	$settings = wk_gsf_get_settings();

	// Optional password protection
	if ( ! empty( $settings['feed_password'] ) ) {
		$provided = $_GET['key'] ?? '';
		if ( $provided !== $settings['feed_password'] ) {
			http_response_code( 403 );
			header( 'Content-Type: text/plain' );
			die( 'Access denied. Add ?key=YOUR_PASSWORD to the URL.' );
		}
	}

	// Cache: serve cached feed if fresh enough
	$cache_key = 'wk_gsf_feed_cache';
	$ttl       = max( 900, (int)( $settings['cache_ttl'] ?? 3600 ) ); // min 15 min
	$cached    = get_transient( $cache_key );
	if ( $cached ) {
		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-WK-Feed: cached' );
		header( 'Cache-Control: public, max-age=' . $ttl );
		echo $cached;
		exit;
	}

	// Generate fresh feed
	$xml = wk_gsf_generate_xml( $settings );

	// Save to cache
	set_transient( $cache_key, $xml, $ttl );

	header( 'Content-Type: application/xml; charset=UTF-8' );
	header( 'X-WK-Feed: fresh' );
	header( 'Cache-Control: public, max-age=' . $ttl );
	echo $xml;
	exit;
}

// ═══════════════════════════════════════════════════════════════
// 3. GENERATE THE XML
// ═══════════════════════════════════════════════════════════════
function wk_gsf_generate_xml( $settings ) {
	$store_name = get_bloginfo( 'name' );
	$store_url  = home_url( '/' );
	$store_desc = get_bloginfo( 'description' ) ?: $store_name . ' — Indian Ethnic Wear';
	$brand      = $settings['brand'] ?: $store_name;
	$currency   = 'INR';
	$condition  = 'new';
	$gender     = $settings['gender'] ?: 'female';
	$age_group  = $settings['age_group'] ?: 'adult';
	$shipping_country = 'IN';
	$shipping_price   = $settings['shipping_price'] ?: '0 INR';
	$shipping_service = $settings['shipping_service'] ?: 'Standard';
	$ship_min_days    = $settings['ship_min_days'] ?: 3;
	$ship_max_days    = $settings['ship_max_days'] ?: 7;

	// Default category mapping from WooCommerce category → Google Product Category
	$cat_map = wk_gsf_get_category_map( $settings );

	ob_start();
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
	echo '<channel>' . "\n";
	echo '  <title>' . esc_html( $store_name ) . '</title>' . "\n";
	echo '  <link>' . esc_url( $store_url ) . '</link>' . "\n";
	echo '  <description>' . esc_html( $store_desc ) . '</description>' . "\n";

	// ── Fetch products ──
	$query_args = [
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => [],
	];

	// Respect excluded categories
	if ( ! empty( $settings['excluded_cats'] ) ) {
		$query_args['tax_query'] = [ [
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => array_map( 'absint', $settings['excluded_cats'] ),
			'operator' => 'NOT IN',
		] ];
	}

	$product_ids = get_posts( $query_args );

	foreach ( $product_ids as $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_purchasable() ) continue;

		// Skip out-of-stock if setting enabled
		if ( ! empty( $settings['hide_out_of_stock'] ) && ! $product->is_in_stock() ) continue;

		if ( $product->is_type( 'variable' ) ) {
			// Output each variation as a separate item
			$variations = $product->get_available_variations();
			foreach ( $variations as $variation_data ) {
				$variation = wc_get_product( $variation_data['variation_id'] );
				if ( ! $variation ) continue;
				if ( ! empty( $settings['hide_out_of_stock'] ) && ! $variation->is_in_stock() ) continue;
				echo wk_gsf_product_item( $variation, $product, $settings, $cat_map, $brand, $currency, $condition, $gender, $age_group, $shipping_country, $shipping_price, $shipping_service, $ship_min_days, $ship_max_days );
			}
		} else {
			echo wk_gsf_product_item( $product, null, $settings, $cat_map, $brand, $currency, $condition, $gender, $age_group, $shipping_country, $shipping_price, $shipping_service, $ship_min_days, $ship_max_days );
		}
	}

	echo '</channel>' . "\n";
	echo '</rss>';

	return ob_get_clean();
}

// ═══════════════════════════════════════════════════════════════
// 4. INDIVIDUAL PRODUCT ITEM
// ═══════════════════════════════════════════════════════════════
function wk_gsf_product_item( $product, $parent_product, $settings, $cat_map, $brand, $currency, $condition, $gender, $age_group, $shipping_country, $shipping_price, $shipping_service, $ship_min_days, $ship_max_days ) {
	$is_variation = $product->is_type( 'variation' );
	$parent       = $parent_product ?: ( $is_variation ? wc_get_product( $product->get_parent_id() ) : null );
	$base         = $parent ?: $product;

	// ── Core fields ──
	$id          = 'WK-' . $product->get_id();
	$item_group  = $is_variation ? ( 'WK-' . $base->get_id() ) : '';
	$title       = wk_gsf_format_title( $product, $base, $settings );
	$description = wk_gsf_format_description( $product, $base );
	$link        = $is_variation
		? add_query_arg( [ 'variation_id' => $product->get_id() ], get_permalink( $base->get_id() ) )
		: get_permalink( $product->get_id() );

	// ── Images ──
	$main_img_id  = $product->get_image_id() ?: ( $base ? $base->get_image_id() : 0 );
	$main_img_url = $main_img_id ? wp_get_attachment_image_url( $main_img_id, 'wk-product-hero' ) : '';
	if ( ! $main_img_url ) return ''; // Image is required by Google

	$gallery_ids  = $base ? $base->get_gallery_image_ids() : [];
	$extra_images = [];
	foreach ( array_slice( $gallery_ids, 0, 9 ) as $gid ) {
		$url = wp_get_attachment_image_url( $gid, 'wk-product-hero' );
		if ( $url && $url !== $main_img_url ) $extra_images[] = $url;
	}

	// ── Pricing ──
	$price       = (float) $product->get_regular_price();
	$sale_price  = $product->is_on_sale() ? (float) $product->get_sale_price() : null;
	$final_price = $sale_price ?? $price;
	if ( $final_price <= 0 ) return ''; // Price required

	$sale_start  = get_post_meta( $product->get_id(), '_sale_price_dates_from', true );
	$sale_end    = get_post_meta( $product->get_id(), '_sale_price_dates_to',   true );

	// ── Availability ──
	$availability = $product->is_in_stock()
		? ( $product->get_stock_quantity() > 0 && $product->get_stock_quantity() <= 5 ? 'limited availability' : 'in_stock' )
		: 'out_of_stock';
	if ( $product->is_on_backorder() ) $availability = 'preorder';

	// ── SKU / GTIN / MPN ──
	$sku = $product->get_sku() ?: ( $parent ? $parent->get_sku() : '' );
	$gtin_field = '';
	$mpn_field  = '';
	if ( $sku ) {
		if ( preg_match( '/^\d{13}$/', $sku ) )     $gtin_field = 'g:gtin>' . esc_html($sku) . '</g:gtin';
		elseif ( preg_match( '/^\d{12}$/', $sku ) ) $gtin_field = 'g:gtin>' . esc_html($sku) . '</g:gtin';
		elseif ( preg_match( '/^\d{8}$/', $sku ) )  $gtin_field = 'g:gtin>' . esc_html($sku) . '</g:gtin';
		else                                         $mpn_field  = $sku;
	}

	// ── Attributes (color, size, material) ──
	$color    = wk_gsf_get_attr( $product, $base, [ 'color','colour','rang','pa_color','pa_colour' ] );
	$size     = wk_gsf_get_attr( $product, $base, [ 'size','sizes','pa_size' ] );
	$material = wk_gsf_get_attr( $product, $base, [ 'material','fabric','pa_material','pa_fabric' ] );
	$pattern  = wk_gsf_get_attr( $product, $base, [ 'pattern','design','pa_pattern' ] );

	// ── Google Product Category ──
	$gpc       = '';
	$wc_cats   = wp_get_post_terms( $base->get_id(), 'product_cat', [ 'fields' => 'ids' ] );
	if ( ! is_wp_error( $wc_cats ) ) {
		foreach ( $wc_cats as $cat_id ) {
			if ( isset( $cat_map[ $cat_id ] ) ) {
				$gpc = $cat_map[ $cat_id ];
				break;
			}
		}
	}
	if ( ! $gpc ) $gpc = $settings['default_gpc'] ?: '2271'; // Apparel & Accessories > Clothing

	// ── Custom labels for campaign bidding ──
	$custom_label_0 = $product->is_on_sale() ? 'on-sale' : 'regular';
	$custom_label_1 = $product->is_featured() ? 'featured' : '';
	$custom_label_2 = $availability === 'in_stock' ? 'in-stock' : 'low-stock';

	// ── Build XML item ──
	$item  = "  <item>\n";
	$item .= "    <g:id>" . esc_html( $id ) . "</g:id>\n";
	$item .= "    <g:title>" . esc_html( $title ) . "</g:title>\n";
	$item .= "    <g:description>" . esc_html( $description ) . "</g:description>\n";
	$item .= "    <g:link>" . esc_url( $link ) . "</g:link>\n";
	$item .= "    <g:image_link>" . esc_url( $main_img_url ) . "</g:image_link>\n";

	foreach ( $extra_images as $extra_url ) {
		$item .= "    <g:additional_image_link>" . esc_url( $extra_url ) . "</g:additional_image_link>\n";
	}

	$item .= "    <g:availability>" . esc_html( $availability ) . "</g:availability>\n";
	$item .= "    <g:price>" . number_format( $price, 2, '.', '' ) . " " . $currency . "</g:price>\n";

	if ( $sale_price !== null ) {
		$item .= "    <g:sale_price>" . number_format( $sale_price, 2, '.', '' ) . " " . $currency . "</g:sale_price>\n";
		if ( $sale_start && $sale_end ) {
			$item .= "    <g:sale_price_effective_date>"
				. date( 'Y-m-d', (int)$sale_start ) . 'T00:00+05:30/'
				. date( 'Y-m-d', (int)$sale_end )   . 'T23:59+05:30'
				. "</g:sale_price_effective_date>\n";
		}
	}

	$item .= "    <g:brand>" . esc_html( $brand ) . "</g:brand>\n";
	$item .= "    <g:condition>" . esc_html( $condition ) . "</g:condition>\n";
	$item .= "    <g:google_product_category>" . esc_html( $gpc ) . "</g:google_product_category>\n";
	$item .= "    <g:gender>" . esc_html( $gender ) . "</g:gender>\n";
	$item .= "    <g:age_group>" . esc_html( $age_group ) . "</g:age_group>\n";

	if ( $sku )      $item .= "    <g:mpn>" . esc_html( $sku ) . "</g:mpn>\n";
	if ( $gtin_field ) $item .= "    <" . $gtin_field . ">\n";
	if ( $item_group ) $item .= "    <g:item_group_id>" . esc_html( $item_group ) . "</g:item_group_id>\n";
	if ( $color )    $item .= "    <g:color>" . esc_html( $color ) . "</g:color>\n";
	if ( $size )     $item .= "    <g:size>" . esc_html( $size ) . "</g:size>\n";
	if ( $material ) $item .= "    <g:material>" . esc_html( $material ) . "</g:material>\n";
	if ( $pattern )  $item .= "    <g:pattern>" . esc_html( $pattern ) . "</g:pattern>\n";

	// Shipping
	$item .= "    <g:shipping>\n";
	$item .= "      <g:country>" . esc_html( $shipping_country ) . "</g:country>\n";
	$item .= "      <g:service>" . esc_html( $shipping_service ) . "</g:service>\n";
	$item .= "      <g:price>" . esc_html( $shipping_price ) . "</g:price>\n";
	$item .= "      <g:min_handling_time>0</g:min_handling_time>\n";
	$item .= "      <g:max_handling_time>1</g:max_handling_time>\n";
	$item .= "      <g:min_transit_time>" . absint( $ship_min_days ) . "</g:min_transit_time>\n";
	$item .= "      <g:max_transit_time>" . absint( $ship_max_days ) . "</g:max_transit_time>\n";
	$item .= "    </g:shipping>\n";

	// Return policy
	$item .= "    <g:return_policy_label>free-returns</g:return_policy_label>\n";

	// Custom labels
	if ( $custom_label_0 ) $item .= "    <g:custom_label_0>" . esc_html( $custom_label_0 ) . "</g:custom_label_0>\n";
	if ( $custom_label_1 ) $item .= "    <g:custom_label_1>" . esc_html( $custom_label_1 ) . "</g:custom_label_1>\n";
	if ( $custom_label_2 ) $item .= "    <g:custom_label_2>" . esc_html( $custom_label_2 ) . "</g:custom_label_2>\n";

	$item .= "  </item>\n";
	return $item;
}

// ═══════════════════════════════════════════════════════════════
// 5. HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function wk_gsf_format_title( $product, $base, $settings ) {
	$name    = $base ? $base->get_name() : $product->get_name();
	$brand   = $settings['brand'] ?: get_bloginfo('name');

	// Good title format: "Brand Name Product Type Color Size"
	// E.g. "WhiteKurti Embroidered Cotton Kurta Set White Free Size"
	$color   = wk_gsf_get_attr( $product, $base, ['color','colour','pa_color'] );
	$size    = wk_gsf_get_attr( $product, $base, ['size','pa_size'] );

	$title = $brand . ' ' . $name;
	if ( $color ) $title .= ' ' . $color;
	if ( $size  ) $title .= ' ' . $size;

	// Strip HTML, trim to 150 chars (Google max is 150)
	$title = wp_strip_all_tags( $title );
	return mb_substr( $title, 0, 150 );
}

function wk_gsf_format_description( $product, $base ) {
	$desc = $base
		? ( $base->get_description() ?: $base->get_short_description() )
		: ( $product->get_description() ?: $product->get_short_description() );

	// Add variation-specific info
	if ( $product->is_type('variation') ) {
		$attrs = $product->get_variation_attributes();
		$attr_text = '';
		foreach ( $attrs as $key => $val ) {
			if ( $val ) $attr_text .= ' ' . ucfirst(str_replace(['attribute_pa_','attribute_'],'',$key)) . ': ' . $val . '.';
		}
		$desc = rtrim($desc,'.') . '.' . $attr_text;
	}

	$desc = wp_strip_all_tags( $desc );
	$desc = preg_replace('/\s+/', ' ', trim($desc) );
	// Google max 5000 chars, min 30 chars
	if ( mb_strlen($desc) < 30 ) $desc = wk_gsf_format_title($product, $base, ['brand'=>get_bloginfo('name')]) . '. Premium quality ethnic wear.';
	return mb_substr( $desc, 0, 5000 );
}

function wk_gsf_get_attr( $product, $base, $names ) {
	foreach ( $names as $n ) {
		$val = $product->get_attribute( $n );
		if ( ! $val && $base ) $val = $base->get_attribute( $n );
		if ( $val ) return trim( explode(',', $val)[0] ); // First value only
	}
	return '';
}

function wk_gsf_get_category_map( $settings ) {
	// Stored per-category mappings + defaults
	$stored = $settings['cat_map'] ?? [];
	$map    = is_array($stored) ? $stored : [];
	return $map;
}

// ═══════════════════════════════════════════════════════════════
// 6. SETTINGS HELPERS
// ═══════════════════════════════════════════════════════════════
function wk_gsf_get_settings() {
	$defaults = [
		'brand'              => get_bloginfo('name'),
		'gender'             => 'female',
		'age_group'          => 'adult',
		'default_gpc'        => '2271',
		'shipping_price'     => '0 INR',
		'shipping_service'   => 'Standard Shipping',
		'ship_min_days'      => 3,
		'ship_max_days'      => 7,
		'hide_out_of_stock'  => 0,
		'feed_password'      => '',
		'cache_ttl'          => 3600,
		'cat_map'            => [],
		'excluded_cats'      => [],
	];
	$saved = get_option( WK_GSF_OPTION, [] );
	return array_merge( $defaults, is_array($saved) ? $saved : [] );
}

function wk_gsf_save_settings( $post_data ) {
	$s = [
		'brand'             => sanitize_text_field( $post_data['gsf_brand']            ?? '' ),
		'gender'            => sanitize_text_field( $post_data['gsf_gender']           ?? 'female' ),
		'age_group'         => sanitize_text_field( $post_data['gsf_age_group']        ?? 'adult' ),
		'default_gpc'       => sanitize_text_field( $post_data['gsf_default_gpc']      ?? '2271' ),
		'shipping_price'    => sanitize_text_field( $post_data['gsf_shipping_price']   ?? '0 INR' ),
		'shipping_service'  => sanitize_text_field( $post_data['gsf_shipping_service'] ?? 'Standard Shipping' ),
		'ship_min_days'     => absint( $post_data['gsf_ship_min_days']                 ?? 3 ),
		'ship_max_days'     => absint( $post_data['gsf_ship_max_days']                 ?? 7 ),
		'hide_out_of_stock' => ! empty( $post_data['gsf_hide_oos'] ) ? 1 : 0,
		'feed_password'     => sanitize_text_field( $post_data['gsf_password']         ?? '' ),
		'cache_ttl'         => max( 900, absint( $post_data['gsf_cache_ttl']           ?? 3600 ) ),
		'cat_map'           => [],
		'excluded_cats'     => array_map( 'absint', (array)( $post_data['gsf_excluded_cats'] ?? [] ) ),
	];
	// Category map
	$cat_ids  = (array)( $post_data['gsf_cat_id']  ?? [] );
	$cat_gpcs = (array)( $post_data['gsf_cat_gpc'] ?? [] );
	foreach ( $cat_ids as $i => $cat_id ) {
		$cat_id  = absint( $cat_id );
		$cat_gpc = sanitize_text_field( $cat_gpcs[$i] ?? '' );
		if ( $cat_id && $cat_gpc ) $s['cat_map'][ $cat_id ] = $cat_gpc;
	}
	update_option( WK_GSF_OPTION, $s );
	// Clear feed cache
	delete_transient( 'wk_gsf_feed_cache' );
}

// Clear feed cache when product is updated
add_action( 'save_post_product',        function() { delete_transient('wk_gsf_feed_cache'); } );
add_action( 'woocommerce_update_product', function() { delete_transient('wk_gsf_feed_cache'); } );

// ═══════════════════════════════════════════════════════════════
// 7. ADMIN MENU + PAGE
// ═══════════════════════════════════════════════════════════════
// Menu registration moved to inc/admin-hub.php
// wk_gsf_admin_page() callback function is defined below.

// Save handler
add_action( 'admin_init', function() {
	if ( ! isset( $_POST['wk_gsf_nonce'] ) ) return;
	if ( ! wp_verify_nonce( $_POST['wk_gsf_nonce'], 'wk_gsf_save' ) ) return;
	if ( ! current_user_can('manage_options') ) return;
	wk_gsf_save_settings( $_POST );
	wp_redirect( admin_url( 'admin.php?page=' . WK_GSF_SLUG . '&saved=1' ) );
	exit;
} );

// ═══════════════════════════════════════════════════════════════
// 8. ADMIN PAGE HTML
// ═══════════════════════════════════════════════════════════════
function wk_gsf_admin_page() {
	if ( ! class_exists('WooCommerce') ) {
		echo '<div class="wrap"><div class="notice notice-error"><p>⚠️ WooCommerce must be active to use Google Shopping Feed.</p></div></div>';
		return;
	}

	$s        = wk_gsf_get_settings();
	$feed_url = home_url('/feed/google-shopping.xml');
	if ( $s['feed_password'] ) $feed_url .= '?key=' . $s['feed_password'];
	$saved    = isset($_GET['saved']);

	// Count products
	$product_count = wp_count_posts('product')->publish ?? 0;
	$all_cats      = get_terms(['taxonomy'=>'product_cat','hide_empty'=>false,'parent'=>0]);
	$all_cats      = is_wp_error($all_cats) ? [] : $all_cats;

	// Active tab
	$tab = sanitize_text_field( $_GET['tab'] ?? 'guide' );
	?>
	<div class="wrap" style="max-width:1100px;">

	<!-- Header -->
	<div style="display:flex;align-items:center;gap:14px;margin-bottom:6px;">
		<span style="font-size:32px;">🛒</span>
		<div>
			<h1 style="margin:0;font-size:22px;">Google Shopping Feed</h1>
			<p style="margin:4px 0 0;color:#666;font-size:13px;">Auto-generate your Google Merchant Center product feed — get your products on Google Shopping.</p>
		</div>
		<?php if ($saved): ?>
		<div class="notice notice-success" style="margin:0 0 0 auto;padding:8px 16px;"><p style="margin:0;">✅ Settings saved! Feed cache cleared.</p></div>
		<?php endif; ?>
	</div>

	<!-- Status Bar -->
	<div style="display:flex;gap:12px;margin:16px 0;flex-wrap:wrap;">
		<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px 18px;display:flex;align-items:center;gap:10px;">
			<span style="font-size:20px;">📦</span>
			<div><div style="font-size:20px;font-weight:700;color:#166534;"><?php echo number_format($product_count); ?></div><div style="font-size:11px;color:#15803d;">Products in Feed</div></div>
		</div>
		<div style="background:#eff6ff;border:1px solid #93c5fd;border-radius:8px;padding:12px 18px;display:flex;align-items:center;gap:10px;">
			<span style="font-size:20px;">🔗</span>
			<div>
				<div style="font-size:12px;font-weight:600;color:#1d4ed8;word-break:break-all;"><?php echo esc_url($feed_url); ?></div>
				<div style="font-size:11px;color:#3b82f6;">Your Feed URL — copy this into Google Merchant Center</div>
			</div>
		</div>
		<a href="<?php echo esc_url($feed_url); ?>" target="_blank" style="background:#1a73e8;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px;align-self:center;">
			👁️ Preview Feed
		</a>
		<a href="<?php echo esc_url(admin_url('admin.php?page='.WK_GSF_SLUG.'&flush=1&_wpnonce='.wp_create_nonce('gsf_flush'))); ?>" style="background:#f59e0b;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px;align-self:center;">
			🔄 Refresh Feed Cache
		</a>
	</div>

	<?php
	// Flush cache action
	if ( isset($_GET['flush']) && wp_verify_nonce($_GET['_wpnonce']??'','gsf_flush') ) {
		delete_transient('wk_gsf_feed_cache');
		echo '<div class="notice notice-success is-dismissible"><p>✅ Feed cache cleared! Next visit to the feed URL will regenerate fresh data.</p></div>';
	}
	?>

	<!-- Tabs -->
	<nav style="display:flex;gap:0;border-bottom:2px solid #ddd;margin-bottom:24px;">
		<?php
		$tabs = [
			'guide'    => '📋 Setup Guide',
			'settings' => '⚙️ Feed Settings',
			'category' => '🗂️ Category Mapping',
		];
		foreach ( $tabs as $t_key => $t_label ) {
			$active = $tab === $t_key;
			echo '<a href="' . esc_url(admin_url('admin.php?page='.WK_GSF_SLUG.'&tab='.$t_key)) . '" style="padding:10px 20px;font-size:13px;font-weight:600;text-decoration:none;border-bottom:' . ($active?'3px solid #1a73e8;color:#1a73e8;margin-bottom:-2px':'2px solid transparent;color:#555') . ';">' . $t_label . '</a>';
		}
		?>
	</nav>

	<?php if ( $tab === 'guide' ) : ?>
	<!-- ═══════════ TAB 1: SETUP GUIDE ═══════════ -->
	<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

		<!-- Left: Step by step -->
		<div>
		<h2 style="margin:0 0 16px;font-size:16px;">🚀 5-Step Setup (takes ~15 minutes)</h2>

		<?php
		$steps = [
			[
				'num'   => '1',
				'color' => '#1a73e8',
				'title' => 'Create Google Merchant Center Account',
				'time'  => '5 min',
				'body'  => 'Go to <a href="https://merchants.google.com" target="_blank" style="color:#1a73e8;">merchants.google.com</a> → Click "Get started" → Enter your business name, country (India), and website URL → Complete business verification.',
				'tip'   => '💡 Use the same Google account you use for Google Ads or Search Console.',
			],
			[
				'num'   => '2',
				'color' => '#0f9d58',
				'title' => 'Verify & Claim Your Website',
				'time'  => '3 min',
				'body'  => 'In GMC → <b>Business Info → Website</b> → Click "Verify & Claim" → Choose "HTML tag" method → Copy the meta tag content value → Paste it in <b>Appearance → Customizer → SEO → Google Search Console Verify Code</b> → Save → Click Verify in GMC.',
				'tip'   => '💡 The same GSC verify code works for both Search Console AND Merchant Center.',
			],
			[
				'num'   => '3',
				'color' => '#f57c00',
				'title' => 'Set Up Shipping & Return Policy',
				'time'  => '4 min',
				'body'  => 'In GMC → <b>Shipping & Returns → Shipping services → Add service</b> → Set: Country = India, Currency = INR, free shipping. Then go to <b>Returns → Add return policy</b> → Set 5-day return window. This must match what is on your website.',
				'tip'   => '💡 Google will reject products if your GMC shipping doesn\'t match your website\'s policy.',
			],
			[
				'num'   => '4',
				'color' => '#7b1fa2',
				'title' => 'Add Your Product Feed URL',
				'time'  => '2 min',
				'body'  => 'In GMC → <b>Products → Feeds → Add Feed (+)</b> → Select: Country = India, Language = English, Destination = Shopping ads + Free listings → Choose "<b>Scheduled fetch</b>" → Enter your feed URL:<br><br><code style="background:#f1f3f4;padding:4px 8px;border-radius:4px;font-size:12px;word-break:break-all;">' . esc_html($feed_url) . '</code><br><br>Set fetch frequency to <b>Daily</b>.',
				'tip'   => '💡 "Scheduled fetch" means Google automatically checks your feed URL every day for updates.',
			],
			[
				'num'   => '5',
				'color' => '#c62828',
				'title' => 'Fix Disapprovals & Go Live',
				'time'  => '1-3 days',
				'body'  => 'After adding the feed, Google reviews each product within 3 business days. Go to <b>Products → All Products</b> to see status. Fix any "Disapproved" items by clicking them to see the reason. Common fixes: add better product titles, ensure images are 500×500px+, add missing GTINs.',
				'tip'   => '💡 Free listings go live automatically. Shopping Ads need a linked Google Ads account + daily budget.',
			],
		];
		foreach ( $steps as $step ) :
		?>
		<div style="display:flex;gap:14px;margin-bottom:20px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;">
			<div style="flex-shrink:0;width:36px;height:36px;background:<?php echo $step['color']; ?>;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;">
				<?php echo $step['num']; ?>
			</div>
			<div style="flex:1;min-width:0;">
				<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
					<strong style="font-size:13px;"><?php echo $step['title']; ?></strong>
					<span style="font-size:10px;background:#f3f4f6;padding:2px 7px;border-radius:10px;color:#6b7280;white-space:nowrap;margin-left:8px;">⏱ <?php echo $step['time']; ?></span>
				</div>
				<p style="font-size:12.5px;color:#374151;line-height:1.6;margin:0 0 8px;"><?php echo $step['body']; ?></p>
				<div style="background:#fffbeb;border-left:3px solid #f59e0b;padding:6px 10px;font-size:11.5px;color:#92400e;border-radius:0 4px 4px 0;">
					<?php echo $step['tip']; ?>
				</div>
			</div>
		</div>
		<?php endforeach; ?>
		</div>

		<!-- Right: What you need + FAQ -->
		<div>
		<div style="background:#1a73e8;color:#fff;border-radius:10px;padding:20px;margin-bottom:20px;">
			<h3 style="margin:0 0 12px;font-size:15px;">✅ What Your Theme Already Provides</h3>
			<?php
			$provided = [
				'Product schema (price, availability, brand)',
				'Shipping details schema',
				'Return policy schema',
				'GTIN / MPN from SKU',
				'Product attributes (color, size, material)',
				'Multiple product images',
				'Sale price + effective dates',
				'Per-variant feed entries',
				'Google Product Category mapping',
				'Auto-refreshing XML feed',
				'Image sitemap for Google Images',
			];
			foreach ($provided as $item):
			echo '<div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:12.5px;"><span>✓</span><span>' . $item . '</span></div>';
			endforeach;
			?>
		</div>

		<div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:20px;margin-bottom:20px;">
			<h3 style="margin:0 0 14px;font-size:15px;">📋 Required Pages on Your Site</h3>
			<p style="font-size:12.5px;color:#555;line-height:1.6;margin:0 0 10px;">Google checks these pages exist before approving your account:</p>
			<?php
			$pages = [
				['Shipping Policy', 'yoursite.com/shipping-policy', 'State: "Free delivery in 3–7 days across India"'],
				['Return Policy',   'yoursite.com/return-policy',   'State: "5-day returns, free return shipping"'],
				['Privacy Policy',  'yoursite.com/privacy-policy',  'Required by law + Google policy'],
				['Contact Us',      'yoursite.com/contact',         'Must have a way for customers to contact you'],
				['Terms of Service','yoursite.com/terms',            'Business terms and conditions'],
			];
			foreach ($pages as $p):
			?>
			<div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:10px;padding:8px 10px;background:#f9fafb;border-radius:6px;">
				<span style="color:#d97706;font-size:14px;margin-top:1px;">⚠</span>
				<div>
					<div style="font-size:12.5px;font-weight:600;"><?php echo $p[0]; ?></div>
					<div style="font-size:11px;color:#6b7280;">URL: /<?php echo explode('/', $p[1])[1] ?? $p[1]; ?> — <?php echo $p[2]; ?></div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<div style="background:#fef9c3;border:1px solid #fde047;border-radius:10px;padding:18px;">
			<h3 style="margin:0 0 10px;font-size:14px;">❓ Free Listings vs Shopping Ads</h3>
			<div style="font-size:12.5px;color:#713f12;line-height:1.7;">
				<strong>Free Product Listings</strong> — 100% free. Products appear in Google Search "Popular Products" carousel and the Shopping tab. Goes live automatically after GMC account is approved.<br><br>
				<strong>Shopping Ads</strong> — Paid. Products appear at the very top of Google with your image + price. Requires a Google Ads account linked to GMC and a daily budget (can start with ₹200/day).
			</div>
		</div>
		</div>

	</div>
	<?php endif; ?>

	<?php if ( $tab === 'settings' ) : ?>
	<!-- ═══════════ TAB 2: FEED SETTINGS ═══════════ -->
	<form method="post">
		<?php wp_nonce_field('wk_gsf_save','wk_gsf_nonce'); ?>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

		<!-- Column 1 -->
		<div>
		<div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:22px;margin-bottom:20px;">
			<h2 style="margin:0 0 16px;font-size:14px;font-weight:700;border-bottom:1px solid #f0f0f0;padding-bottom:10px;">🏷️ Product Defaults</h2>
			<?php
			wk_gsf_field('gsf_brand', 'Brand Name', $s['brand'], 'text', 'Your store/brand name. Appears in every product listing on Google.');
			wk_gsf_select('gsf_gender', 'Default Gender', $s['gender'], [
				'female' => 'Female (Women\'s)',
				'male'   => 'Male (Men\'s)',
				'unisex' => 'Unisex',
			], 'For Indian fashion, usually "Female". Google uses this to target ads correctly.');
			wk_gsf_select('gsf_age_group', 'Default Age Group', $s['age_group'], [
				'adult'    => 'Adult (18+)',
				'kids'     => 'Kids',
				'toddler'  => 'Toddler',
				'infant'   => 'Infant',
				'newborn'  => 'Newborn',
			], 'Google requires this for apparel. Use "Adult" for most fashion products.');
			wk_gsf_field('gsf_default_gpc', 'Default Google Product Category ID', $s['default_gpc'], 'text', 'Numeric ID from <a href="https://www.google.com/basepages/producttype/taxonomy-with-ids.en-IN.txt" target="_blank" style="color:#1a73e8;">Google\'s taxonomy list</a>. Default 2271 = Apparel > Clothing. Use Category Mapping tab for per-category control.');
			?>
		</div>

		<div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:22px;">
			<h2 style="margin:0 0 16px;font-size:14px;font-weight:700;border-bottom:1px solid #f0f0f0;padding-bottom:10px;">⚙️ Feed Options</h2>
			<?php
			wk_gsf_checkbox('gsf_hide_oos', 'Exclude out-of-stock products from feed', $s['hide_out_of_stock'], 'Recommended — prevents disapprovals for unavailable items.');
			wk_gsf_field('gsf_password', 'Feed Password (optional)', $s['feed_password'], 'text', 'If set, feed URL needs ?key=YOUR_PASSWORD. Leave blank to keep feed public (recommended for GMC).');
			wk_gsf_field('gsf_cache_ttl', 'Feed Cache Duration (seconds)', $s['cache_ttl'], 'number', 'How long the feed is cached. 3600 = 1 hour. Min 900 (15 min). Google fetches max once per day.');
			?>
		</div>
		</div>

		<!-- Column 2 -->
		<div>
		<div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:22px;margin-bottom:20px;">
			<h2 style="margin:0 0 16px;font-size:14px;font-weight:700;border-bottom:1px solid #f0f0f0;padding-bottom:10px;">🚚 Shipping Settings</h2>
			<?php
			wk_gsf_field('gsf_shipping_service', 'Shipping Service Name', $s['shipping_service'], 'text', 'E.g. "Free Shipping", "Standard Delivery". Must match your GMC shipping settings.');
			wk_gsf_field('gsf_shipping_price', 'Shipping Price', $s['shipping_price'], 'text', 'Format: "0 INR" for free, "49 INR" for paid. Must match your actual checkout shipping cost.');
			?>
			<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
				<?php
				wk_gsf_field('gsf_ship_min_days', 'Min Delivery Days', $s['ship_min_days'], 'number', '');
				wk_gsf_field('gsf_ship_max_days', 'Max Delivery Days', $s['ship_max_days'], 'number', '');
				?>
			</div>
			<div style="background:#eff6ff;border-radius:6px;padding:10px 12px;font-size:11.5px;color:#1d4ed8;margin-top:8px;">
				⚠️ Shipping details here must <strong>exactly match</strong> what you set in Google Merchant Center → Shipping & Returns. Mismatches cause product disapprovals.
			</div>
		</div>

		<div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:22px;">
			<h2 style="margin:0 0 16px;font-size:14px;font-weight:700;border-bottom:1px solid #f0f0f0;padding-bottom:10px;">🚫 Excluded Categories</h2>
			<p style="font-size:12px;color:#666;margin:0 0 10px;">Select categories whose products should NOT appear in the feed (e.g. gift cards, bundles).</p>
			<div style="max-height:200px;overflow-y:auto;border:1px solid #e5e7eb;padding:10px;border-radius:6px;">
			<?php foreach ($all_cats as $cat): ?>
				<label style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:12.5px;cursor:pointer;">
					<input type="checkbox" name="gsf_excluded_cats[]" value="<?php echo $cat->term_id; ?>"
						<?php checked( in_array($cat->term_id, $s['excluded_cats']), true ); ?>
						style="accent-color:#1a73e8;" />
					<?php echo esc_html($cat->name); ?> <span style="color:#9ca3af;">(<?php echo $cat->count; ?> products)</span>
				</label>
			<?php endforeach; ?>
			</div>
		</div>
		</div>

		</div><!-- /grid -->

		<div style="padding:16px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;display:flex;align-items:center;justify-content:space-between;margin-top:4px;">
			<span style="font-size:13px;color:#555;">Changes clear the feed cache and take effect on the next feed request.</span>
			<input type="submit" class="button button-primary" value="💾 Save Feed Settings" style="background:#1a73e8;border-color:#1557b0;padding:10px 28px;font-size:14px;" />
		</div>
	</form>
	<?php endif; ?>

	<?php if ( $tab === 'category' ) : ?>
	<!-- ═══════════ TAB 3: CATEGORY MAPPING ═══════════ -->
	<form method="post">
		<?php wp_nonce_field('wk_gsf_save','wk_gsf_nonce'); ?>

		<div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:22px;">
		<h2 style="margin:0 0 6px;font-size:15px;">🗂️ Map Your Categories → Google Product Categories</h2>
		<p style="font-size:13px;color:#666;margin:0 0 16px;line-height:1.6;">
			This tells Google exactly what type of product each category contains. Better category mapping = better ad targeting and more impressions.<br>
			Find Google Category IDs at: <a href="https://www.google.com/basepages/producttype/taxonomy-with-ids.en-IN.txt" target="_blank" style="color:#1a73e8;">Google Taxonomy List (India)</a>
		</p>

		<!-- Common Indian Fashion Category IDs Reference -->
		<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-bottom:20px;">
			<strong style="font-size:12px;display:block;margin-bottom:8px;color:#374151;">📌 Common Google Category IDs for Indian Fashion:</strong>
			<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;font-size:11.5px;color:#4b5563;">
				<?php
				$refs = [
					'2271'   => 'Apparel > Clothing (generic)',
					'1604'   => 'Tops & Blouses',
					'5322'   => 'Suits & Co-ords',
					'3915'   => 'Dresses',
					'3913'   => 'Skirts',
					'212'    => 'Sarees',
					'5697'   => 'Lehengas',
					'1594'   => 'Pants & Trousers',
					'1581'   => 'Shorts',
					'5697'   => 'Ethnic & Cultural',
					'178'    => 'Accessories',
					'3032'   => 'Jewelry',
				];
				foreach ($refs as $id => $label):
				echo '<div style="background:#fff;border:1px solid #e5e7eb;padding:5px 8px;border-radius:4px;cursor:pointer;transition:.1s;" onclick="navigator.clipboard&&navigator.clipboard.writeText(\'' . $id . '\').then(()=>{this.style.background=\'#e0f2fe\';setTimeout(()=>this.style.background=\'#fff\',1000)});" title="Click to copy ID">';
				echo '<code style="color:#1a73e8;">' . $id . '</code> — ' . $label;
				echo '</div>';
				endforeach;
				?>
			</div>
			<p style="font-size:11px;color:#9ca3af;margin:8px 0 0;">💡 Click any ID to copy it.</p>
		</div>

		<table style="width:100%;border-collapse:collapse;">
			<thead>
				<tr style="background:#f9fafb;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#555;">
					<th style="padding:10px 12px;text-align:left;border:1px solid #e5e7eb;">Your WooCommerce Category</th>
					<th style="padding:10px 12px;text-align:left;border:1px solid #e5e7eb;">Google Product Category ID</th>
					<th style="padding:10px 12px;text-align:left;border:1px solid #e5e7eb;">Products</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($all_cats as $cat):
				$mapped = $s['cat_map'][$cat->term_id] ?? '';
			?>
			<tr style="border:1px solid #f0f0f0;">
				<td style="padding:10px 12px;border:1px solid #f0f0f0;font-size:13px;font-weight:500;">
					<?php echo esc_html($cat->name); ?>
					<input type="hidden" name="gsf_cat_id[]" value="<?php echo $cat->term_id; ?>" />
				</td>
				<td style="padding:10px 12px;border:1px solid #f0f0f0;">
					<input type="text" name="gsf_cat_gpc[]" value="<?php echo esc_attr($mapped); ?>"
						placeholder="e.g. 2271"
						style="width:140px;padding:6px 10px;border:1px solid #ddd;border-radius:4px;font-size:13px;font-family:monospace;" />
					<?php if ($mapped): ?>
					<span style="font-size:11px;color:#059669;margin-left:6px;">✓ mapped</span>
					<?php else: ?>
					<span style="font-size:11px;color:#d97706;margin-left:6px;">⚠ using default</span>
					<?php endif; ?>
				</td>
				<td style="padding:10px 12px;border:1px solid #f0f0f0;font-size:12px;color:#6b7280;"><?php echo $cat->count; ?> products</td>
			</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		</div>

		<div style="padding:16px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;display:flex;align-items:center;justify-content:space-between;margin-top:16px;">
			<span style="font-size:13px;color:#555;">Unmapped categories will use the Default Google Product Category ID from Settings tab.</span>
			<input type="submit" class="button button-primary" value="💾 Save Category Mapping" style="background:#1a73e8;border-color:#1557b0;padding:10px 28px;font-size:14px;" />
		</div>
	</form>
	<?php endif; ?>

	</div><!-- /wrap -->
	<?php
}

// ═══════════════════════════════════════════════════════════════
// 9. ADMIN FORM FIELD HELPERS
// ═══════════════════════════════════════════════════════════════
function wk_gsf_field( $name, $label, $value, $type = 'text', $desc = '' ) {
	echo '<div style="margin-bottom:14px;">';
	echo '<label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#555;margin-bottom:5px;">' . $label . '</label>';
	echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:4px;font-size:13px;" />';
	if ( $desc ) echo '<p style="margin:4px 0 0;font-size:11.5px;color:#888;line-height:1.5;">' . $desc . '</p>';
	echo '</div>';
}
function wk_gsf_select( $name, $label, $value, $options, $desc = '' ) {
	echo '<div style="margin-bottom:14px;">';
	echo '<label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#555;margin-bottom:5px;">' . $label . '</label>';
	echo '<select name="' . esc_attr($name) . '" style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:4px;font-size:13px;">';
	foreach ($options as $k => $v) echo '<option value="' . esc_attr($k) . '"' . selected($value,$k,false) . '>' . esc_html($v) . '</option>';
	echo '</select>';
	if ( $desc ) echo '<p style="margin:4px 0 0;font-size:11.5px;color:#888;">' . $desc . '</p>';
	echo '</div>';
}
function wk_gsf_checkbox( $name, $label, $value, $desc = '' ) {
	echo '<div style="margin-bottom:14px;">';
	echo '<label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">';
	echo '<input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked($value,1,false) . ' style="margin-top:2px;accent-color:#1a73e8;width:16px;height:16px;" />';
	echo '<div><div style="font-size:13px;font-weight:500;">' . $label . '</div>';
	if ( $desc ) echo '<div style="font-size:11.5px;color:#888;margin-top:2px;">' . $desc . '</div>';
	echo '</div></label></div>';
}

// ═══════════════════════════════════════════════════════════════
// 10. ADD FEED URL TO ROBOTS.TXT + HEAD DISCOVERY
// ═══════════════════════════════════════════════════════════════
add_filter( 'robots_txt', function($out) {
	return $out . 'Sitemap: ' . home_url('/feed/google-shopping.xml') . "\n";
} );
