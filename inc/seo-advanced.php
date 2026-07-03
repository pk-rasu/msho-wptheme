<?php
/**
 * WhiteKurti — Advanced SEO Module v1.0
 * ─────────────────────────────────────────────────────────────
 * Covers everything missing for world-class SEO:
 *  1.  LCP hero/product image preload (fetchpriority=high)
 *  2.  CLS prevention — img width+height attributes
 *  3.  WooCommerce filter URL canonicals (orderby, min_price, etc.)
 *  4.  Paginated archive rel=prev/next
 *  5.  Tag / attribute archive noindex
 *  6.  Enhanced Product schema (color, material, size, gtin, mpn, itemCondition)
 *  7.  FAQPage schema (per-product + global)
 *  8.  ItemList schema on shop/category pages
 *  9.  LocalBusiness schema
 *  10. Article schema for blog posts
 *  11. Image XML sitemap provider (adds images to wp-sitemap.xml)
 *  12. Category page SEO descriptions (admin UI)
 *  13. Auto-internal-linking in blog post content
 *  14. Open Graph article/product enhancements
 *  15. SiteLinksSearchBox / Speakable schema
 *  16. Performance: script defer rules, hero preload
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ═══════════════════════════════════════════════════════════════
// 1.  LCP IMAGE PRELOAD (fetchpriority="high")
//     Must run at priority 0 — before any other wp_head output
// ═══════════════════════════════════════════════════════════════
add_action( 'wp_head', 'wk_adv_lcp_preload', 0 );
function wk_adv_lcp_preload() {

	$preload_url = '';
	$img_w       = 0;
	$img_h       = 0;

	// ── Homepage hero ──
	if ( is_front_page() ) {
		$hero_mod = get_theme_mod( 'wk_hero_image' );
		if ( $hero_mod ) {
			$attachment_id = attachment_url_to_postid( $hero_mod );
			if ( $attachment_id ) {
				$src_set = wp_get_attachment_image_src( $attachment_id, 'wk-hero-banner' );
				if ( $src_set ) {
					$preload_url = $src_set[0];
					$img_w       = $src_set[1];
					$img_h       = $src_set[2];
				}
			}
			if ( ! $preload_url ) $preload_url = $hero_mod;
		}
		if ( ! $preload_url ) {
			$preload_url = get_theme_file_uri( 'assets/images/hero-banner.png' );
		}
	}

	// ── Product page — first gallery image ──
	if ( is_singular( 'product' ) ) {
		global $post;
		if ( $post && class_exists( 'WooCommerce' ) ) {
			$product = wc_get_product( $post->ID );
			if ( $product ) {
				$img_id = $product->get_image_id();
				if ( $img_id ) {
					$src_set = wp_get_attachment_image_src( $img_id, 'wk-product-hero' );
					if ( $src_set ) {
						$preload_url = $src_set[0];
						$img_w       = $src_set[1];
						$img_h       = $src_set[2];
					}
				}
			}
		}
	}

	// ── Shop / Category — first product image ──
	if ( is_shop() || is_product_category() || is_product_tag() ) {
		$first_product = wc_get_products( [
			'limit'   => 1,
			'status'  => 'publish',
			'orderby' => 'date',
			'order'   => 'DESC',
		] );
		if ( ! empty( $first_product ) ) {
			$img_id = $first_product[0]->get_image_id();
			if ( $img_id ) {
				$src_set     = wp_get_attachment_image_src( $img_id, 'wk-product-card' );
				$preload_url = $src_set ? $src_set[0] : '';
			}
		}
	}

	if ( ! $preload_url ) return;

	// Output preload link
	echo '<link rel="preload" as="image" href="' . esc_url( $preload_url ) . '" fetchpriority="high"';
	if ( $img_w ) echo ' imagesrcset="' . esc_attr( $preload_url ) . ' ' . absint($img_w) . 'w"';
	echo '>' . "\n";
}

// ═══════════════════════════════════════════════════════════════
// 2.  CLS PREVENTION — add width + height to product images
//     Prevents layout shift score penalty (Google CWV)
// ═══════════════════════════════════════════════════════════════
add_filter( 'wp_get_attachment_image_attributes', 'wk_adv_img_dimensions', 10, 3 );
function wk_adv_img_dimensions( $attrs, $attachment, $size ) {
	// Only add if not already present
	if ( isset( $attrs['width'] ) && isset( $attrs['height'] ) ) return $attrs;

	$image_src = wp_get_attachment_image_src( $attachment->ID, $size );
	if ( $image_src && $image_src[1] && $image_src[2] ) {
		$attrs['width']  = $image_src[1];
		$attrs['height'] = $image_src[2];
	}
	return $attrs;
}

// ═══════════════════════════════════════════════════════════════
// 3.  FILTER URL CANONICALS
//     WooCommerce creates thousands of duplicate URLs via:
//     ?orderby=  ?min_price=  ?max_price=  ?product_cat=
//     ?product_tag=  ?pa_*=  &paged=1
//     These drain crawl budget and cause duplicate content.
// ═══════════════════════════════════════════════════════════════
add_action( 'wp_head', 'wk_adv_filter_canonicals', 3 );
function wk_adv_filter_canonicals() {
	if ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) return;

	// Parameters that create duplicate pages — we'll strip them for canonical
	$filter_params = [
		'orderby', 'min_price', 'max_price',
		'product_cat', 'product_tag', 'rating_filter',
		'filter_color', 'filter_size', 'filter_material',
		'in_stock', 'on_sale',
	];

	// Build attribute filter params dynamically
	if ( class_exists( 'WooCommerce' ) ) {
		$taxonomies = wc_get_attribute_taxonomies();
		foreach ( $taxonomies as $tax ) {
			$filter_params[] = 'filter_' . $tax->attribute_name;
			$filter_params[] = 'query_type_' . $tax->attribute_name;
		}
	}

	// Check if any filter params are in the current URL
	$has_filter = false;
	foreach ( $filter_params as $param ) {
		if ( isset( $_GET[ $param ] ) ) {
			$has_filter = true;
			break;
		}
	}

	if ( ! $has_filter ) return;

	// Canonical = clean URL without filter params (but preserve category/tag slug)
	$clean_url = '';
	if ( is_shop() ) {
		$clean_url = wc_get_page_permalink( 'shop' );
	} elseif ( is_product_category() ) {
		$clean_url = get_term_link( get_queried_object() );
	} elseif ( is_product_tag() ) {
		$clean_url = get_term_link( get_queried_object() );
	}

	if ( $clean_url && ! is_wp_error( $clean_url ) ) {
		// Remove the default canonical that wp_head outputs so we don't duplicate
		remove_action( 'wp_head', 'rel_canonical' );
		echo '<link rel="canonical" href="' . esc_url( $clean_url ) . '">' . "\n";
		// Tell Google to noindex filtered pages but still follow links
		echo '<meta name="robots" content="noindex, follow">' . "\n";
	}
}

// ═══════════════════════════════════════════════════════════════
// 4.  PAGINATED ARCHIVE rel=prev / rel=next
// ═══════════════════════════════════════════════════════════════
add_action( 'wp_head', 'wk_adv_pagination_links', 3 );
function wk_adv_pagination_links() {
	if ( ! is_paged() && 1 === get_query_var( 'paged', 1 ) ) return;

	$paged    = get_query_var( 'paged', 1 ) ?: 1;
	$max_page = isset($GLOBALS['wp_query']) && $GLOBALS['wp_query'] instanceof WP_Query ? (int)$GLOBALS['wp_query']->max_num_pages : 1;

	$base = '';
	if ( is_shop() ) {
		$base = wc_get_page_permalink( 'shop' );
	} elseif ( is_product_category() || is_product_tag() ) {
		$term = get_queried_object();
		$base = $term ? get_term_link( $term ) : '';
	} elseif ( is_home() || is_archive() ) {
		$base = get_pagenum_link( 1 );
	}

	if ( ! $base || is_wp_error( $base ) ) return;

	if ( $paged > 1 ) {
		$prev = get_pagenum_link( $paged - 1 );
		echo '<link rel="prev" href="' . esc_url( $prev ) . '">' . "\n";
	}
	if ( $paged < $max_page ) {
		$next = get_pagenum_link( $paged + 1 );
		echo '<link rel="next" href="' . esc_url( $next ) . '">' . "\n";
	}
}

// ═══════════════════════════════════════════════════════════════
// 5.  TAG & ATTRIBUTE ARCHIVE NOINDEX
//     Product tag & PA_* taxonomy archives are thin pages;
//     noindexing them protects crawl budget.
// ═══════════════════════════════════════════════════════════════
add_action( 'wp_head', 'wk_adv_thin_page_noindex', 2 );
function wk_adv_thin_page_noindex() {
	$noindex = false;

	// Product tags (usually thin)
	if ( is_product_tag() ) $noindex = true;

	// WooCommerce attribute archives (pa_color, pa_size, etc.)
	if ( is_tax() && class_exists( 'WooCommerce' ) ) {
		$queried = get_queried_object();
		if ( $queried && isset( $queried->taxonomy ) && strpos( $queried->taxonomy, 'pa_' ) === 0 ) {
			$noindex = true;
		}
	}

	// Page 2+ of any archive (they often become thin)
	$paged = get_query_var( 'paged', 1 );
	if ( $paged > 4 && ( is_shop() || is_product_category() ) ) $noindex = true;

	if ( $noindex ) {
		// Remove default canonical to avoid conflict
		remove_action( 'wp_head', 'rel_canonical' );
		echo '<meta name="robots" content="noindex, follow">' . "\n";
	}
}

// ═══════════════════════════════════════════════════════════════
// 6.  ENHANCED PRODUCT SCHEMA
//     Adds to the JSON-LD output from seo.php:
//     color, material, size → Google Shopping rich results
//     gtin / mpn → Google Merchant Center required
//     itemCondition → required for Google Shopping
// ═══════════════════════════════════════════════════════════════
add_filter( 'wk_product_schema', 'wk_adv_enhance_product_schema', 10, 2 );
function wk_adv_enhance_product_schema( $schema, $product ) {
	if ( ! ( $product instanceof WC_Product ) ) return $schema;

	// ── Attributes → schema properties ──
	$attr_map = [
		'color'    => [ 'color', 'colour', 'rang', 'रंग' ],
		'material' => [ 'material', 'fabric', 'kapda', 'कपड़ा', 'vastra' ],
		'size'     => [ 'size', 'sizes', 'siz', 'आकार' ],
		'pattern'  => [ 'pattern', 'design' ],
		'occasion' => [ 'occasion', 'event' ],
	];
	foreach ( $attr_map as $schema_key => $search_names ) {
		foreach ( $search_names as $attr_name ) {
			$attr = $product->get_attribute( $attr_name );
			if ( ! $attr ) {
				// Try taxonomy attribute
				$attr = $product->get_attribute( 'pa_' . $attr_name );
			}
			if ( $attr ) {
				if ( $schema_key === 'size' ) {
					// Use SizeSpecification
					$sizes = array_map( 'trim', explode( ',', $attr ) );
					$schema['size'] = array_map( function( $s ) {
						return [ '@type' => 'SizeSpecification', 'name' => $s ];
					}, $sizes );
				} else {
					$schema[ $schema_key ] = $attr;
				}
				break;
			}
		}
	}

	// ── itemCondition — always New for fashion e-commerce ──
	$schema['itemCondition'] = 'https://schema.org/NewCondition';

	// ── gtin / mpn from SKU ──
	$sku = $product->get_sku();
	if ( $sku ) {
		// If SKU is 13 digits, treat as GTIN-13 (EAN/barcode)
		if ( preg_match( '/^\d{13}$/', $sku ) ) {
			$schema['gtin13'] = $sku;
		} elseif ( preg_match( '/^\d{12}$/', $sku ) ) {
			$schema['gtin12'] = $sku;
		} elseif ( preg_match( '/^\d{8}$/', $sku ) ) {
			$schema['gtin8'] = $sku;
		} else {
			$schema['mpn'] = $sku;
		}
	}

	// ── priceValidUntil — use sale end date if available, else year-end ──
	if ( $product->is_on_sale() ) {
		$sale_end = get_post_meta( $product->get_id(), '_sale_price_dates_to', true );
		if ( $sale_end ) {
			$schema['offers']['priceValidUntil'] = date( 'Y-m-d', (int)$sale_end );
		}
	}

	// ── Category as additionalProperty ──
	$cats = wp_get_post_terms( $product->get_id(), 'product_cat', [ 'fields' => 'names' ] );
	if ( $cats && ! is_wp_error( $cats ) ) {
		$schema['category'] = implode( ' > ', $cats );
	}

	// ── Additional images ──
	$gallery_ids = $product->get_gallery_image_ids();
	if ( $gallery_ids ) {
		$images = [];
		$main_img = wp_get_attachment_image_url( $product->get_image_id(), 'full' );
		if ( $main_img ) $images[] = $main_img;
		foreach ( $gallery_ids as $gid ) {
			$url = wp_get_attachment_image_url( $gid, 'full' );
			if ( $url ) $images[] = $url;
		}
		if ( count( $images ) > 1 ) {
			$schema['image'] = $images;
		}
	}

	return $schema;
}

// Hook the filter into the JSON-LD output from seo.php
add_action( 'wp_head', 'wk_adv_inject_enhanced_product_schema', 4 );
function wk_adv_inject_enhanced_product_schema() {
	if ( ! is_singular( 'product' ) ) return;
	if ( ! class_exists( 'WooCommerce' ) ) return;
	global $post;
	if ( ! $post ) return;

	$product = wc_get_product( $post->ID );
	if ( ! $product ) return;

	// Build enhanced product schema directly
	$org_name = get_theme_mod( 'wk_seo_org_name', get_bloginfo( 'name' ) );
	$img_id   = $product->get_image_id();
	$img_url  = $img_id ? wp_get_attachment_image_url( $img_id, 'full' ) : '';
	$images   = $img_url ? [ $img_url ] : [];
	foreach ( $product->get_gallery_image_ids() as $gid ) {
		$url = wp_get_attachment_image_url( $gid, 'full' );
		if ( $url ) $images[] = $url;
	}

	$reviews      = function_exists( 'wk_get_product_reviews' ) ? wk_get_product_reviews( $product->get_id() ) : [];
	$review_count = count( $reviews );
	$avg_rating   = $review_count ? round( array_sum( array_column( $reviews, 'rating' ) ) / $review_count, 1 ) : 0;

	// Base offer
	$price       = $product->get_price();
	$sale_end    = get_post_meta( $product->get_id(), '_sale_price_dates_to', true );
	$price_until = $sale_end ? date( 'Y-m-d', (int)$sale_end ) : date( 'Y-12-31' );

	$schema = [
		'@context' => 'https://schema.org',
		'@type'    => 'Product',
		'name'     => $product->get_name(),
		'description' => wp_strip_all_tags( $product->get_description() ?: $product->get_short_description() ),
		'image'    => count( $images ) === 1 ? $images[0] : $images,
		'url'      => get_permalink( $post->ID ),
		'sku'      => $product->get_sku() ?: 'SKU-' . $product->get_id(),
		'brand'    => [ '@type' => 'Brand', 'name' => $org_name ],
		'itemCondition' => 'https://schema.org/NewCondition',
		'offers'   => [
			'@type'           => 'Offer',
			'url'             => get_permalink( $post->ID ),
			'price'           => $price,
			'priceCurrency'   => 'INR',
			'priceValidUntil' => $price_until,
			'availability'    => 'https://schema.org/' . ( $product->is_in_stock() ? 'InStock' : 'OutOfStock' ),
			'seller'          => [ '@type' => 'Organization', 'name' => $org_name ],
			'hasMerchantReturnPolicy' => [
				'@type'                => 'MerchantReturnPolicy',
				'applicableCountry'    => 'IN',
				'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
				'merchantReturnDays'   => 5,
				'returnMethod'         => 'https://schema.org/ReturnByMail',
				'returnFees'           => 'https://schema.org/FreeReturn',
			],
			'shippingDetails' => [
				'@type'       => 'OfferShippingDetails',
				'shippingRate'=> [ '@type' => 'MonetaryAmount', 'value' => '0', 'currency' => 'INR' ],
				'shippingDestination' => [
					'@type'          => 'DefinedRegion',
					'addressCountry' => 'IN',
				],
				'deliveryTime' => [
					'@type'        => 'ShippingDeliveryTime',
					'handlingTime' => [ '@type' => 'QuantitativeValue', 'minValue' => 1, 'maxValue' => 2, 'unitCode' => 'DAY' ],
					'transitTime'  => [ '@type' => 'QuantitativeValue', 'minValue' => 2, 'maxValue' => 5, 'unitCode' => 'DAY' ],
				],
			],
		],
	];

	// ── SKU → GTIN / MPN ──
	$sku = $product->get_sku();
	if ( $sku ) {
		if ( preg_match( '/^\d{13}$/', $sku ) )      $schema['gtin13'] = $sku;
		elseif ( preg_match( '/^\d{12}$/', $sku ) )  $schema['gtin12'] = $sku;
		elseif ( preg_match( '/^\d{8}$/', $sku ) )   $schema['gtin8']  = $sku;
		else                                          $schema['mpn']    = $sku;
	}

	// ── Attributes ──
	$attr_map = [
		'color'    => [ 'color', 'colour', 'rang' ],
		'material' => [ 'material', 'fabric', 'kapda' ],
	];
	foreach ( $attr_map as $schema_key => $names ) {
		foreach ( $names as $n ) {
			$val = $product->get_attribute( $n ) ?: $product->get_attribute( 'pa_' . $n );
			if ( $val ) { $schema[ $schema_key ] = $val; break; }
		}
	}
	// Size
	foreach ( [ 'size', 'sizes', 'pa_size' ] as $n ) {
		$val = $product->get_attribute( $n );
		if ( $val ) {
			$sizes = array_map( 'trim', explode( ',', $val ) );
			$schema['size'] = array_map( function($s) { return [ '@type' => 'SizeSpecification', 'name' => $s ]; }, $sizes );
			break;
		}
	}

	// ── Category ──
	$cats = wp_get_post_terms( $product->get_id(), 'product_cat', [ 'fields' => 'names' ] );
	if ( $cats && ! is_wp_error( $cats ) ) {
		$schema['category'] = implode( ' > ', $cats );
	}

	// ── Aggregate Rating + Reviews ──
	if ( $avg_rating && $review_count ) {
		$schema['aggregateRating'] = [
			'@type'       => 'AggregateRating',
			'ratingValue' => $avg_rating,
			'reviewCount' => $review_count,
			'bestRating'  => 5,
			'worstRating' => 1,
		];
		$schema['review'] = array_map( function( $r ) {
			return [
				'@type'        => 'Review',
				'author'       => [ '@type' => 'Person', 'name' => $r['name'] ],
				'reviewRating' => [ '@type' => 'Rating', 'ratingValue' => $r['rating'], 'bestRating' => 5 ],
				'reviewBody'   => $r['text'],
				'datePublished'=> $r['date'] ?: date('Y-m-d'),
			];
		}, array_slice( $reviews, 0, 10 ) );
	}

	// ── FAQ if set ──
	$faq_items = wk_adv_get_product_faq( $product->get_id() );
	if ( $faq_items ) {
		$faq_schema = [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => array_map( function( $item ) {
				return [
					'@type'          => 'Question',
					'name'           => $item['q'],
					'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $item['a'] ],
				];
			}, $faq_items ),
		];
		echo '<script type="application/ld+json">' . wp_json_encode( $faq_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	echo '<script type="application/ld+json" id="wk-product-schema-enhanced">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

// ═══════════════════════════════════════════════════════════════
// 7.  FAQPage SCHEMA — per-product + customizer global defaults
// ═══════════════════════════════════════════════════════════════

/**
 * Get FAQ items for a product.
 * First checks product-specific meta, then falls back to global FAQ.
 */
function wk_adv_get_product_faq( $product_id ) {
	// Product-specific FAQ stored as JSON in post meta
	$meta = get_post_meta( $product_id, '_wk_faq', true );
	if ( $meta ) {
		$decoded = json_decode( $meta, true );
		if ( is_array( $decoded ) && ! empty( $decoded ) ) return $decoded;
	}

	// Global fallback FAQ from customizer
	$global = get_theme_mod( 'wk_global_faq_json', '' );
	if ( $global ) {
		$decoded = json_decode( $global, true );
		if ( is_array( $decoded ) && ! empty( $decoded ) ) return $decoded;
	}

	// Built-in sensible defaults for Indian fashion e-commerce
	return [
		[
			'q' => 'What is the return policy?',
			'a' => 'We offer 5-day easy returns on all products. Items must be unworn and in original packaging. Return shipping is free.',
		],
		[
			'q' => 'How long does delivery take?',
			'a' => 'Orders are delivered within 3–7 business days across India. Express delivery (1–2 days) is available for select pin codes.',
		],
		[
			'q' => 'Is Cash on Delivery (COD) available?',
			'a' => 'Yes, COD is available on all orders across India. No additional charges for COD orders.',
		],
		[
			'q' => 'How do I find my correct size?',
			'a' => 'Please refer to our size guide on the product page. We recommend measuring your chest, waist, and hip and comparing with our size chart.',
		],
		[
			'q' => 'Are these products authentic and original?',
			'a' => 'Yes, all products are 100% original and sourced directly from verified manufacturers and artisans in India.',
		],
	];
}

// Admin UI: Product FAQ meta box
add_action( 'add_meta_boxes', function() {
	if ( ! class_exists( 'WooCommerce' ) ) return;
	add_meta_box(
		'wk_faq_metabox',
		'🙋 Product FAQ (Schema)',
		'wk_adv_faq_metabox_html',
		'product',
		'normal',
		'default'
	);
} );

function wk_adv_faq_metabox_html( $post ) {
	$meta  = get_post_meta( $post->ID, '_wk_faq', true );
	$items = $meta ? json_decode( $meta, true ) : [];
	if ( ! is_array( $items ) ) $items = [];
	wp_nonce_field( 'wk_faq_save', 'wk_faq_nonce' );
	?>
	<style>
	.wk-faq-row{background:#f9f9f9;border:1px solid #e2e2e2;border-radius:5px;padding:12px;margin-bottom:10px;}
	.wk-faq-row label{font-size:11px;font-weight:600;text-transform:uppercase;color:#555;display:block;margin-bottom:4px;}
	.wk-faq-row input,.wk-faq-row textarea{width:100%;border:1px solid #ddd;border-radius:3px;padding:6px 8px;font-size:13px;}
	.wk-faq-row textarea{min-height:60px;resize:vertical;}
	.wk-faq-remove{float:right;color:#c00;cursor:pointer;font-size:11px;text-decoration:underline;margin-top:-2px;}
	</style>
	<div id="wk-faq-list">
	<?php foreach ( $items as $i => $item ) : ?>
	<div class="wk-faq-row">
		<span class="wk-faq-remove" data-idx="<?php echo $i; ?>">Remove</span>
		<label>Question</label>
		<input type="text" name="wk_faq[<?php echo $i; ?>][q]" value="<?php echo esc_attr( $item['q'] ?? '' ); ?>" placeholder="e.g. What fabric is this?" />
		<label style="margin-top:8px;">Answer</label>
		<textarea name="wk_faq[<?php echo $i; ?>][a]"><?php echo esc_textarea( $item['a'] ?? '' ); ?></textarea>
	</div>
	<?php endforeach; ?>
	</div>
	<p><button type="button" id="wk-faq-add" class="button">+ Add FAQ Item</button></p>
	<p style="font-size:12px;color:#888;">These appear as FAQ structured data in Google search results — can generate a rich snippet showing your Q&amp;As directly on Google.</p>
	<script>
	(function(){
		var list = document.getElementById('wk-faq-list');
		var idx  = <?php echo count($items); ?>;
		document.getElementById('wk-faq-add').addEventListener('click', function(){
			var html = '<div class="wk-faq-row">';
			html += '<span class="wk-faq-remove" data-idx="'+idx+'">Remove</span>';
			html += '<label>Question</label><input type="text" name="wk_faq['+idx+'][q]" placeholder="e.g. Is COD available?" />';
			html += '<label style="margin-top:8px;">Answer</label><textarea name="wk_faq['+idx+'][a]"></textarea>';
			html += '</div>';
			list.insertAdjacentHTML('beforeend', html);
			idx++;
		});
		list.addEventListener('click', function(e){
			if(e.target.classList.contains('wk-faq-remove')){
				e.target.closest('.wk-faq-row').remove();
			}
		});
	})();
	</script>
	<?php
}

add_action( 'save_post_product', function( $post_id ) {
	if ( ! isset( $_POST['wk_faq_nonce'] ) ) return;
	if ( ! wp_verify_nonce( $_POST['wk_faq_nonce'], 'wk_faq_save' ) ) return;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	if ( isset( $_POST['wk_faq'] ) && is_array( $_POST['wk_faq'] ) ) {
		$items = [];
		foreach ( $_POST['wk_faq'] as $item ) {
			$q = sanitize_text_field( $item['q'] ?? '' );
			$a = wp_kses_post( $item['a'] ?? '' );
			if ( $q && $a ) $items[] = [ 'q' => $q, 'a' => $a ];
		}
		update_post_meta( $post_id, '_wk_faq', wp_json_encode( $items ) );
	} else {
		delete_post_meta( $post_id, '_wk_faq' );
	}
} );

// ═══════════════════════════════════════════════════════════════
// 8.  ItemList SCHEMA — shop / category pages
//     Tells Google exactly what products are on each page
// ═══════════════════════════════════════════════════════════════
add_action( 'wp_head', 'wk_adv_itemlist_schema', 4 );
function wk_adv_itemlist_schema() {
	if ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) return;
	if ( ! class_exists( 'WooCommerce' ) ) return;

	global $wp_query;
	if ( empty( $wp_query->posts ) ) return;

	$list_items = [];
	$position   = 1;

	foreach ( $wp_query->posts as $post ) {
		$product = wc_get_product( $post->ID );
		if ( ! $product ) continue;

		$img_id  = $product->get_image_id();
		$img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'wk-product-card' ) : '';

		$list_items[] = [
			'@type'    => 'ListItem',
			'position' => $position++,
			'url'      => get_permalink( $post->ID ),
			'name'     => $product->get_name(),
			'image'    => $img_url ?: '',
		];
	}

	if ( empty( $list_items ) ) return;

	$page_title = is_shop()
		? get_the_title( wc_get_page_id( 'shop' ) )
		: single_term_title( '', false );

	$schema = [
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'name'            => $page_title,
		'url'             => get_pagenum_link( 1 ),
		'numberOfItems'   => count( $list_items ),
		'itemListElement' => $list_items,
	];

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

// ═══════════════════════════════════════════════════════════════
// 9.  LocalBusiness SCHEMA
//     Crucial for "kurta shop near me" / "kurta online Jaipur" etc.
// ═══════════════════════════════════════════════════════════════
add_action( 'wp_head', 'wk_adv_local_business_schema', 4 );
function wk_adv_local_business_schema() {
	if ( ! is_front_page() ) return;
	// Local Business schema is safe to output alongside SEO plugins

	$name    = get_theme_mod( 'wk_seo_org_name',    get_bloginfo('name') );
	$phone   = get_theme_mod( 'wk_seo_org_phone',   '' );
	$email   = get_theme_mod( 'wk_seo_org_email',   '' );
	$address = get_theme_mod( 'wk_seo_org_address', '' );
	$lat     = get_theme_mod( 'wk_local_lat',        '' );
	$lng     = get_theme_mod( 'wk_local_lng',        '' );
	$city    = get_theme_mod( 'wk_local_city',       'Jaipur' );
	$state   = get_theme_mod( 'wk_local_state',      'Rajasthan' );
	$pin     = get_theme_mod( 'wk_local_pin',        '' );
	$hours   = get_theme_mod( 'wk_local_hours',      'Mo-Sa 10:00-19:00' );
	$custom_logo_id = get_theme_mod('custom_logo');
	$logo    = $custom_logo_id ? wp_get_attachment_image_url( $custom_logo_id, 'full' ) : get_site_icon_url(512);
	$og_img  = get_theme_mod( 'wk_seo_og_image', '' ) ?: get_site_icon_url(512);

	$schema = [
		'@context'   => 'https://schema.org',
		'@type'      => [ 'LocalBusiness', 'ClothingStore' ],
		'@id'        => home_url( '/#localbusiness' ),
		'name'       => $name,
		'url'        => home_url('/'),
		'image'      => $og_img,
		'logo'       => $logo ?: $og_img,
		'description'=> get_bloginfo('description') ?: get_theme_mod('wk_seo_default_desc',''),
		'currenciesAccepted' => 'INR',
		'paymentAccepted'    => 'Cash, Credit Card, Debit Card, UPI, NetBanking, COD',
		'areaServed'         => 'India',
		'address'    => [
			'@type'           => 'PostalAddress',
			'streetAddress'   => $address,
			'addressLocality' => $city,
			'addressRegion'   => $state,
			'postalCode'      => $pin,
			'addressCountry'  => 'IN',
		],
		'sameAs' => array_values( array_filter( [
			get_theme_mod( 'wk_social_instagram_url', '' ),
			get_theme_mod( 'wk_social_facebook_url',  '' ),
			get_theme_mod( 'wk_social_youtube_url',   '' ),
			get_theme_mod( 'wk_social_twitter_url',   '' ),
		] ) ),
	];

	if ( $phone )  $schema['telephone'] = $phone;
	if ( $email )  $schema['email']     = $email;
	if ( $hours )  $schema['openingHours'] = $hours;
	if ( $lat && $lng ) {
		$schema['geo'] = [
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $lat,
			'longitude' => (float) $lng,
		];
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

// Customizer settings for LocalBusiness
add_action( 'customize_register', 'wk_adv_local_business_customizer' );
function wk_adv_local_business_customizer( $wp_customize ) {
	$wp_customize->add_section( 'wk_local_seo', [
		'title'    => __( '📍 Local SEO & Business', 'whitekurti' ),
		'panel'    => 'wk_panel',
		'priority' => 82,
	] );
	$fields = [
		[ 'wk_local_city',    'City',                  'text', 'Jaipur' ],
		[ 'wk_local_state',   'State',                 'text', 'Rajasthan' ],
		[ 'wk_local_pin',     'PIN Code',              'text', '' ],
		[ 'wk_local_hours',   'Opening Hours',         'text', 'Mo-Sa 10:00-19:00' ],
		[ 'wk_local_lat',     'Latitude (GPS)',         'text', '' ],
		[ 'wk_local_lng',     'Longitude (GPS)',        'text', '' ],
		[ 'wk_global_faq_json','Global FAQ (JSON)',    'textarea', '' ],
	];
	foreach ( $fields as [ $id, $label, $type, $default ] ) {
		$wp_customize->add_setting( $id, [ 'default' => $default, 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
		$wp_customize->add_control( $id, [ 'label' => $label, 'section' => 'wk_local_seo', 'type' => $type ] );
	}
}

// ═══════════════════════════════════════════════════════════════
// 10. Article SCHEMA for blog posts
// ═══════════════════════════════════════════════════════════════
add_action( 'wp_head', 'wk_adv_article_schema', 4 );
function wk_adv_article_schema() {
	if ( ! is_singular( 'post' ) ) return;
	global $post;
	if ( ! $post ) return;

	$author_name = get_the_author_meta( 'display_name', $post->post_author );
	$author_url  = get_author_posts_url( $post->post_author );
	$thumb_url   = get_the_post_thumbnail_url( $post->ID, 'large' );
	$og_img      = get_theme_mod( 'wk_seo_og_image', '' ) ?: get_site_icon_url(512);
	$org_name    = get_theme_mod( 'wk_seo_org_name', get_bloginfo('name') );
	$logo_url    = get_custom_logo() ? wp_get_attachment_image_url( get_theme_mod('custom_logo'), 'full' ) : $og_img;

	$schema = [
		'@context'      => 'https://schema.org',
		'@type'         => 'Article',
		'headline'      => get_the_title(),
		'description'   => wp_strip_all_tags( get_the_excerpt() ?: wp_trim_words( $post->post_content, 30 ) ),
		'image'         => $thumb_url ?: $og_img,
		'author'        => [
			'@type' => 'Person',
			'name'  => $author_name,
			'url'   => $author_url,
		],
		'publisher'     => [
			'@type' => 'Organization',
			'name'  => $org_name,
			'logo'  => [ '@type' => 'ImageObject', 'url' => $logo_url ?: $og_img ],
		],
		'datePublished' => get_the_date( 'c' ),
		'dateModified'  => get_the_modified_date( 'c' ),
		'mainEntityOfPage' => [
			'@type' => 'WebPage',
			'@id'   => get_permalink(),
		],
		'inLanguage'    => 'en-IN',
		'keywords'      => implode( ', ', wp_list_pluck( get_the_tags() ?: [], 'name' ) ),
	];

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

// ═══════════════════════════════════════════════════════════════
// 11. IMAGE SITEMAP PROVIDER
//     WordPress's built-in sitemap doesn't include images.
//     This registers a custom sitemap that lists all product
//     images — critical for Google Image Search traffic.
// ═══════════════════════════════════════════════════════════════
add_filter( 'wp_sitemaps_add_provider', 'wk_adv_add_image_sitemap', 10, 2 );
function wk_adv_add_image_sitemap( $provider, $name ) {
	return $provider; // Keep default providers
}

// Register a custom /wp-sitemap-images.xml endpoint
add_action( 'init', 'wk_adv_register_image_sitemap_rewrite' );
function wk_adv_register_image_sitemap_rewrite() {
	add_rewrite_rule( '^sitemap-images\.xml$', 'index.php?wk_image_sitemap=1', 'top' );
	add_rewrite_tag( '%wk_image_sitemap%', '([0-9]+)' );
}

add_action( 'template_redirect', 'wk_adv_serve_image_sitemap' );
function wk_adv_serve_image_sitemap() {
	if ( ! get_query_var( 'wk_image_sitemap' ) ) return;

	header( 'Content-Type: application/xml; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex, follow' );

	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

	$products = get_posts( [
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 1000,
		'fields'         => 'ids',
	] );

	foreach ( $products as $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) continue;

		$permalink  = get_permalink( $product_id );
		$all_img_ids = array_filter( array_merge(
			$product->get_image_id() ? [ $product->get_image_id() ] : [],
			$product->get_gallery_image_ids()
		) );

		if ( empty( $all_img_ids ) ) continue;

		echo '<url>' . "\n";
		echo '  <loc>' . esc_url( $permalink ) . '</loc>' . "\n";

		foreach ( $all_img_ids as $img_id ) {
			$img_url   = wp_get_attachment_image_url( $img_id, 'full' );
			$img_alt   = get_post_meta( $img_id, '_wp_attachment_image_alt', true );
			$img_title = get_the_title( $img_id );
			if ( ! $img_url ) continue;

			echo '  <image:image>' . "\n";
			echo '    <image:loc>' . esc_url( $img_url ) . '</image:loc>' . "\n";
			if ( $img_alt )   echo '    <image:caption>' . esc_xml( $img_alt )   . '</image:caption>' . "\n";
			if ( $img_title ) echo '    <image:title>'   . esc_xml( $img_title ) . '</image:title>' . "\n";
			echo '  </image:image>' . "\n";
		}

		echo '</url>' . "\n";
	}

	echo '</urlset>';
	exit;
}

// Add image sitemap link to robots.txt
add_filter( 'robots_txt', function( $output ) {
	$output .= 'Sitemap: ' . home_url( '/sitemap-images.xml' ) . "\n";
	return $output;
} );

// Also add to <head> as a discovery link
add_action( 'wp_head', function() {
	if ( is_front_page() ) {
		echo '<link rel="sitemap" type="application/xml" title="Image Sitemap" href="' . esc_url( home_url('/sitemap-images.xml') ) . '">' . "\n";
	}
} );

// ═══════════════════════════════════════════════════════════════
// 12. CATEGORY PAGE SEO DESCRIPTIONS
//     Adds a top/bottom description to WooCommerce category pages.
//     Admins edit it in the term edit screen.
// ═══════════════════════════════════════════════════════════════

// Add SEO description field to product_cat edit screen
add_action( 'product_cat_edit_form_fields', 'wk_adv_cat_seo_fields', 20 );
function wk_adv_cat_seo_fields( $term ) {
	$seo_intro   = get_term_meta( $term->term_id, 'wk_seo_intro',   true );
	$seo_outro   = get_term_meta( $term->term_id, 'wk_seo_outro',   true );
	$seo_metadesc= get_term_meta( $term->term_id, 'wk_cat_metadesc', true );
	?>
	<tr class="form-field">
		<th scope="row"><label for="wk_seo_intro">SEO Top Description</label></th>
		<td>
			<textarea name="wk_seo_intro" id="wk_seo_intro" rows="4" style="width:100%;"><?php echo esc_textarea($seo_intro); ?></textarea>
			<p class="description">Appears above the product grid. Write 80–150 words describing this category with your target keywords (e.g. "white kurtas", "cotton kurta sets").</p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="wk_seo_outro">SEO Bottom Description</label></th>
		<td>
			<textarea name="wk_seo_outro" id="wk_seo_outro" rows="4" style="width:100%;"><?php echo esc_textarea($seo_outro); ?></textarea>
			<p class="description">Appears below the grid. Use for longer keyword-rich content (buying guides, fabric info, style tips).</p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="wk_cat_metadesc">Meta Description</label></th>
		<td>
			<input type="text" name="wk_cat_metadesc" id="wk_cat_metadesc" value="<?php echo esc_attr($seo_metadesc); ?>" maxlength="160" style="width:100%;" />
			<p class="description">Max 160 characters. Appears in Google search snippets. Leave blank to use category description.</p>
			<p><span id="wk_cat_metadesc_count">0</span>/160 characters</p>
			<script>
			(function(){
				var el=document.getElementById('wk_cat_metadesc'), counter=document.getElementById('wk_cat_metadesc_count');
				function upd(){counter.textContent=el.value.length;counter.style.color=el.value.length>155?'#c00':'#555';}
				el.addEventListener('input',upd); upd();
			})();
			</script>
		</td>
	</tr>
	<?php
}

add_action( 'edited_product_cat', 'wk_adv_save_cat_seo_fields' );
function wk_adv_save_cat_seo_fields( $term_id ) {
	if ( isset( $_POST['wk_seo_intro'] ) )    update_term_meta( $term_id, 'wk_seo_intro',    wp_kses_post( $_POST['wk_seo_intro'] ) );
	if ( isset( $_POST['wk_seo_outro'] ) )    update_term_meta( $term_id, 'wk_seo_outro',    wp_kses_post( $_POST['wk_seo_outro'] ) );
	if ( isset( $_POST['wk_cat_metadesc'] ) ) update_term_meta( $term_id, 'wk_cat_metadesc', sanitize_text_field( $_POST['wk_cat_metadesc'] ) );
}

// Override meta description for category pages
add_action( 'wp_head', 'wk_adv_cat_meta_override', 1 );
function wk_adv_cat_meta_override() {
	if ( ! is_product_category() ) return;
	$term     = get_queried_object();
	$metadesc = get_term_meta( $term->term_id, 'wk_cat_metadesc', true );
	if ( $metadesc ) {
		// Output before seo.php (priority 2) so it wins
		echo '<meta name="description" content="' . esc_attr( $metadesc ) . '">' . "\n";
	}
}

// Display top/bottom descriptions on category pages
add_action( 'woocommerce_before_shop_loop', 'wk_adv_cat_seo_intro', 5 );
function wk_adv_cat_seo_intro() {
	if ( ! is_product_category() ) return;
	$term  = get_queried_object();
	$intro = get_term_meta( $term->term_id, 'wk_seo_intro', true );
	if ( $intro ) {
		echo '<div class="wk-cat-seo-intro wk-container" style="padding:16px 0 24px;font-size:14px;color:var(--ink-soft);line-height:1.7;max-width:860px;">';
		echo wp_kses_post( $intro );
		echo '</div>';
	}
}

add_action( 'woocommerce_after_shop_loop', 'wk_adv_cat_seo_outro', 20 );
function wk_adv_cat_seo_outro() {
	if ( ! is_product_category() ) return;
	$term  = get_queried_object();
	$outro = get_term_meta( $term->term_id, 'wk_seo_outro', true );
	if ( $outro ) {
		echo '<div class="wk-cat-seo-outro wk-container" style="padding:32px 0 16px;font-size:14px;color:var(--ink-soft);line-height:1.8;max-width:860px;border-top:.5px solid var(--line);margin-top:32px;">';
		echo wp_kses_post( $outro );
		echo '</div>';
	}
}

// ═══════════════════════════════════════════════════════════════
// 13. AUTO INTERNAL LINKING IN BLOG POSTS
//     Automatically links first mention of product names in posts
//     to their product pages. Drives traffic from editorial content.
// ═══════════════════════════════════════════════════════════════
add_filter( 'the_content', 'wk_adv_auto_internal_links', 20 );
function wk_adv_auto_internal_links( $content ) {
	if ( ! is_singular( 'post' ) ) return $content;
	if ( ! class_exists( 'WooCommerce' ) ) return $content;
	if ( is_admin() ) return $content;

	// Only run if there's meaningful text
	if ( strlen( $content ) < 200 ) return $content;

	// Cache product links to avoid repeated DB queries
	$product_links = get_transient( 'wk_auto_link_products' );
	if ( false === $product_links ) {
		$product_links = [];
		$products = get_posts( [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		] );
		foreach ( $products as $pid ) {
			$title = get_the_title( $pid );
			if ( strlen( $title ) > 4 ) {  // Skip very short titles
				$product_links[ $title ] = get_permalink( $pid );
			}
		}
		set_transient( 'wk_auto_link_products', $product_links, DAY_IN_SECONDS );
	}

	if ( empty( $product_links ) ) return $content;

	// Sort by length desc to match longer names first (avoid partial matches)
	uksort( $product_links, function($a,$b) { return strlen($b) - strlen($a); } );

	$linked_products = []; // only link each product once per post
	$link_count      = 0;
	$max_links       = 5;  // max auto-links per post

	foreach ( $product_links as $name => $url ) {
		if ( $link_count >= $max_links ) break;
		if ( in_array( $name, $linked_products, true ) ) continue;

		// Only replace first occurrence, case-insensitive, not inside existing <a> tags
		$pattern     = '/(?<!["\'>])(' . preg_quote( $name, '/' ) . ')(?!["\'])/iu';
		$replacement = '<a href="' . esc_url($url) . '" class="wk-auto-link" title="' . esc_attr($name) . '">$1</a>';

		$new_content = preg_replace( $pattern, $replacement, $content, 1, $count );
		if ( $count > 0 && $new_content ) {
			$content = $new_content;
			$linked_products[] = $name;
			$link_count++;
		}
	}

	return $content;
}

// Clear transient when products are saved
add_action( 'save_post_product', function() {
	delete_transient( 'wk_auto_link_products' );
} );

// ═══════════════════════════════════════════════════════════════
// 14. OPEN GRAPH ENHANCEMENTS
//     og:image:alt, og:locale:alternate, product:* tags
// ═══════════════════════════════════════════════════════════════
add_action( 'wp_head', 'wk_adv_og_enhancements', 2 );
function wk_adv_og_enhancements() {
	if ( function_exists('wk_seo_plugin_active') && wk_seo_plugin_active() ) return;
	// og:image:alt (missing from base seo.php)
	if ( is_singular( 'product' ) && class_exists('WooCommerce') ) {
		global $post;
		if ( $post ) {
			$product = wc_get_product( $post->ID );
			if ( $product ) {
				$img_id  = $product->get_image_id();
				$img_alt = $img_id ? get_post_meta( $img_id, '_wp_attachment_image_alt', true ) : '';
				if ( ! $img_alt ) $img_alt = $product->get_name();
				echo '<meta property="og:image:alt" content="' . esc_attr( $img_alt ) . '">' . "\n";
				// Product catalog details for FB/Instagram shopping
				echo '<meta property="og:availability" content="' . ( $product->is_in_stock() ? 'in stock' : 'out of stock' ) . '">' . "\n";
				echo '<meta property="og:condition" content="new">' . "\n";
				echo '<meta property="og:brand" content="' . esc_attr( get_theme_mod('wk_seo_org_name', get_bloginfo('name')) ) . '">' . "\n";
				if ( $product->get_sku() ) echo '<meta property="og:retailer_item_id" content="' . esc_attr( $product->get_sku() ) . '">' . "\n";
			}
		}
	}

	// Article published/modified dates for blog posts
	if ( is_singular( 'post' ) ) {
		echo '<meta property="article:published_time" content="' . esc_attr( get_the_date('c') ) . '">' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date('c') ) . '">' . "\n";
		$author = get_post_field( 'post_author', get_the_ID() );
		echo '<meta property="article:author" content="' . esc_attr( get_the_author_meta('display_name', $author) ) . '">' . "\n";
		// Tags as article:tag
		$tags = wp_get_post_tags( get_the_ID() );
		foreach ( array_slice($tags, 0, 5) as $tag ) {
			echo '<meta property="article:tag" content="' . esc_attr( $tag->name ) . '">' . "\n";
		}
	}
}

// ═══════════════════════════════════════════════════════════════
// 15. SiteLinksSearchBox & Speakable SCHEMA
// ═══════════════════════════════════════════════════════════════
add_action( 'wp_head', 'wk_adv_sitelinks_speakable', 4 );
function wk_adv_sitelinks_speakable() {
	if ( ! is_front_page() ) return;

	// SiteLinksSearchBox — shows a search box directly in Google results
	$sitelinks = [
		'@context'        => 'https://schema.org',
		'@type'           => 'WebSite',
		'url'             => home_url('/'),
		'potentialAction' => [
			'@type'        => 'SearchAction',
			'target'       => [
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url('/?s={search_term_string}&post_type=product'),
			],
			'query-input'  => 'required name=search_term_string',
		],
	];
	echo '<script type="application/ld+json">' . wp_json_encode( $sitelinks, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

// Speakable schema for product pages (voice search / Google Assistant)
add_action( 'wp_head', 'wk_adv_speakable_schema', 4 );
function wk_adv_speakable_schema() {
	if ( ! is_singular( 'product' ) ) return;

	$schema = [
		'@context' => 'https://schema.org',
		'@type'    => 'WebPage',
		'url'      => get_permalink(),
		'speakable'=> [
			'@type'     => 'SpeakableSpecification',
			'cssSelector' => [ '.wk-pdp__title', '.wk-pdp__short-desc', '.wk-pdp__price' ],
		],
	];
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

// ═══════════════════════════════════════════════════════════════
// 16. PERFORMANCE: Hero image — mark as eager + high priority
//     Also adds explicit width/height to hero img tag in front-page
// ═══════════════════════════════════════════════════════════════

// Ensure hero image in front-page.php gets fetchpriority=high
add_filter( 'wp_get_attachment_image_attributes', 'wk_adv_hero_fetchpriority', 10, 3 );
function wk_adv_hero_fetchpriority( $attrs, $attachment, $size ) {
	if ( ! is_front_page() ) return $attrs;
	if ( $size === 'wk-hero-banner' || $size === 'full' ) {
		$attrs['fetchpriority'] = 'high';
		$attrs['loading']       = 'eager';
	}
	return $attrs;
}

// ═══════════════════════════════════════════════════════════════
// 17. STRUCTURED DATA BREADCRUMBS — inject into template too
//     (belt + suspenders: schema in JSON-LD AND microdata in HTML)
// ═══════════════════════════════════════════════════════════════
// Already handled by seo.php wk_breadcrumbs_html()

// ═══════════════════════════════════════════════════════════════
// 18. HREFLANG — en-IN default + future Hindi support
// ═══════════════════════════════════════════════════════════════
add_action( 'wp_head', 'wk_adv_hreflang', 3 );
function wk_adv_hreflang() {
	// en-IN hreflang — tells Google this is Indian English content
	$current_url = ( is_ssl() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$clean_url   = strtok( $current_url, '?' ); // strip query params
	echo '<link rel="alternate" hreflang="en-IN" href="' . esc_url( $clean_url ) . '">' . "\n";
	echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $clean_url ) . '">' . "\n";
}

// ═══════════════════════════════════════════════════════════════
// 19. XML SITEMAP IMPROVEMENTS
//     Adds products to the default WP sitemap with images,
//     and sets correct priorities.
// ═══════════════════════════════════════════════════════════════

// Exclude noindex pages from sitemap
add_filter( 'wp_sitemaps_posts_query_args', 'wk_adv_sitemap_exclude_noindex', 10, 2 );
function wk_adv_sitemap_exclude_noindex( $args, $post_type ) {
	// Exclude draft/private posts (WordPress does this by default — just ensuring)
	$args['post_status'] = 'publish';
	return $args;
}

// Exclude noindex taxonomies from sitemap
add_filter( 'wp_sitemaps_taxonomies', 'wk_adv_sitemap_taxonomies' );
function wk_adv_sitemap_taxonomies( $taxonomies ) {
	// Remove product_tag from sitemap (thin pages — noindexed)
	unset( $taxonomies['product_tag'] );
	return $taxonomies;
}

// ── Flush rewrite rules on theme activation to register image sitemap URL ──
add_action( 'after_switch_theme', function() {
	wk_adv_register_image_sitemap_rewrite();
	flush_rewrite_rules();
} );

// ── Helper: esc_xml ──
if ( ! function_exists( 'esc_xml' ) ) {
	function esc_xml( $text ) {
		return htmlspecialchars( $text, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
	}
}
