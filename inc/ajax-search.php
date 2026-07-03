<?php
/**
 * WhiteKurti — AJAX Live Search System
 * Rich product image results, categories, keyboard navigation, recent searches
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Customizer settings ───────────────────────────────────────────────────────
add_action( 'customize_register', function( $wp_customize ) {
	$wp_customize->add_section( 'wk_live_search', [
		'title'    => __( '🔍 Live Search Settings', 'whitekurti' ),
		'panel'    => 'wk_panel',
		'priority' => 38,
	] );
	$fields = [
		[ 'wk_ls_enabled',         'Enable rich live search',                'checkbox', true,  '' ],
		[ 'wk_ls_products',        'Number of product results',              'number',   6,     '3–12' ],
		[ 'wk_ls_show_cats',       'Show matching categories',               'checkbox', true,  '' ],
		[ 'wk_ls_show_pages',      'Show matching pages',                    'checkbox', true,  '' ],
		[ 'wk_ls_show_price',      'Show product price in results',          'checkbox', true,  '' ],
		[ 'wk_ls_show_category',   'Show product category label',            'checkbox', true,  '' ],
		[ 'wk_ls_show_recent',     'Show recent searches (localStorage)',    'checkbox', true,  '' ],
		[ 'wk_ls_min_chars',       'Minimum characters to trigger search',  'number',   2,     '1–4' ],
		[ 'wk_ls_debounce',        'Debounce delay (ms)',                   'number',   280,   'Lower = faster, higher = fewer requests' ],
		[ 'wk_ls_trending',        'Show trending/popular searches',         'checkbox', true,  '' ],
		[ 'wk_ls_trending_terms',  'Trending search terms (comma-sep)',     'text',     'White Kurta,Anarkali,Chikankari,Co-ord Set,Saree', '' ],
	];
	foreach ( $fields as [$id,$label,$type,$default,$desc] ) {
		$san = $type==='checkbox' ? 'rest_sanitize_boolean' : 'sanitize_text_field';
		$wp_customize->add_setting($id, ['default'=>$default,'sanitize_callback'=>$san,'transport'=>'refresh']);
		$wp_customize->add_control($id, ['label'=>$label,'description'=>$desc,'section'=>'wk_live_search','type'=>$type]);
	}
} );

// ── AJAX search handler ────────────────────────────────────────────────────────
add_action( 'wp_ajax_wk_live_search',        'wk_ajax_live_search' );
add_action( 'wp_ajax_nopriv_wk_live_search', 'wk_ajax_live_search' );

function wk_ajax_live_search() {
	check_ajax_referer( 'wk_live_search', 'nonce' );

	$query   = sanitize_text_field( $_POST['q'] ?? '' );
	$limit   = max( 3, min( 12, absint( get_theme_mod('wk_ls_products', 6) ) ) );

	if ( strlen( $query ) < 1 ) {
		wp_send_json_success( [] );
		return;
	}

	$results = [
		'products'   => [],
		'categories' => [],
		'pages'      => [],
	];

	// ── Products ──
	$product_args = [
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		's'              => $query,
		'orderby'        => 'relevance',
	];
	$products = get_posts( $product_args );

	// Also search by SKU
	if ( count($products) < $limit ) {
		$sku_query = new WP_Query([
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit - count($products),
			'meta_query'     => [['key'=>'_sku','value'=>$query,'compare'=>'LIKE']],
			'post__not_in'   => array_column($products,'ID'),
		]);
		$products = array_merge($products, $sku_query->posts);
	}

	foreach ( $products as $p ) {
		$product = wc_get_product( $p->ID );
		if ( !$product || !$product->is_visible() ) continue;

		$img_id  = $product->get_image_id();
		$img_url = $img_id ? wp_get_attachment_image_url($img_id,'woocommerce_thumbnail') : wc_placeholder_img_src('woocommerce_thumbnail');

		$cats    = get_the_terms( $p->ID, 'product_cat' );
		$cat_name= !empty($cats) && !is_wp_error($cats) ? $cats[0]->name : '';

		$results['products'][] = [
			'id'       => $p->ID,
			'name'     => $p->post_title,
			'url'      => get_permalink( $p->ID ),
			'price'    => $product->get_price_html(),
			'img'      => $img_url,
			'cat'      => $cat_name,
			'on_sale'  => $product->is_on_sale(),
			'in_stock' => $product->is_in_stock(),
		];
	}

	// ── Categories ──
	if ( get_theme_mod('wk_ls_show_cats', true) ) {
		$cats = get_terms([
			'taxonomy'   => 'product_cat',
			'name__like' => $query,
			'hide_empty' => true,
			'number'     => 4,
		]);
		if ( !is_wp_error($cats) ) {
			foreach ( $cats as $cat ) {
				$img_id  = get_term_meta( $cat->term_id, 'thumbnail_id', true );
				$img_url = $img_id ? wp_get_attachment_image_url($img_id,'thumbnail') : '';
				$results['categories'][] = [
					'name'  => $cat->name,
					'url'   => get_term_link($cat),
					'count' => $cat->count,
					'img'   => $img_url,
				];
			}
		}
	}

	// ── Pages ──
	if ( get_theme_mod('wk_ls_show_pages', true) ) {
		$pages = get_posts([
			'post_type'      => 'page',
			'post_status'    => 'publish',
			's'              => $query,
			'posts_per_page' => 2,
			'post__not_in'   => class_exists('WooCommerce') ? [ wc_get_page_id('cart'), wc_get_page_id('checkout'), wc_get_page_id('myaccount') ] : [],
		]);
		foreach ($pages as $page) {
			$results['pages'][] = [
				'name' => $page->post_title,
				'url'  => get_permalink($page->ID),
			];
		}
	}

	// ── Log search (for popularity tracking) ──
	if ( ! empty($results['products']) ) {
		$searches = get_option('wk_search_log', []);
		$query_lc = strtolower($query);
		$searches[$query_lc] = ($searches[$query_lc] ?? 0) + 1;
		arsort($searches);
		update_option('wk_search_log', array_slice($searches, 0, 200, true));
	}

	wp_send_json_success( $results );
}

// ── Pass config to JS ──────────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', function() {
	wp_localize_script( 'wk-main', 'wk_search_cfg', [
		'nonce'        => wp_create_nonce('wk_live_search'),
		'ajax'         => admin_url('admin-ajax.php'),
		'min_chars'    => absint(get_theme_mod('wk_ls_min_chars', 2)),
		'debounce'     => absint(get_theme_mod('wk_ls_debounce', 280)),
		'show_price'   => get_theme_mod('wk_ls_show_price', true) ? '1' : '0',
		'show_cat'     => get_theme_mod('wk_ls_show_category', true) ? '1' : '0',
		'show_recent'  => get_theme_mod('wk_ls_show_recent', true) ? '1' : '0',
		'show_trending'=> get_theme_mod('wk_ls_trending', true) ? '1' : '0',
		'trending'     => array_map('trim', explode(',', get_theme_mod('wk_ls_trending_terms','White Kurta,Anarkali,Chikankari,Co-ord Set,Saree'))),
		'enabled'      => get_theme_mod('wk_ls_enabled', true) ? '1' : '0',
		'shop_url'     => class_exists('WooCommerce') ? wc_get_page_permalink('shop') : home_url('/shop'),
		'currency'     => get_woocommerce_currency_symbol(),
	]);
}, 20 );

// Search analytics page moved to inc/admin-hub.php → wk_hub_search_analytics_page()
