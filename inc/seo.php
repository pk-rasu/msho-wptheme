<?php
/**
 * WhiteKurti — Complete SEO System
 * Meta tags, Open Graph, Twitter Cards, JSON-LD Structured Data, Breadcrumbs
 */
if ( ! defined( 'ABSPATH' ) ) exit;
// ── SEO Plugin detection ─────────────────────────────────────────────────────
// Returns true if a major SEO plugin is active and handling meta tags
function wk_seo_plugin_active() {
    // Yoast SEO
    if ( defined( 'WPSEO_FILE' ) || function_exists( 'wpseo_init' ) ) return true;
    // Rank Math
    if ( defined( 'RANK_MATH_FILE' ) || class_exists( 'RankMath' ) ) return true;
    // The SEO Framework
    if ( function_exists( 'the_seo_framework' ) || class_exists( 'The_SEO_Framework\\Load' ) ) return true;
    // All in One SEO
    if ( defined( 'AIOSEO_FILE' ) || class_exists( 'AIOSEO\\Plugin\\AIOSEO' ) ) return true;
    // SEOPress
    if ( defined( 'SEOPRESS_VERSION' ) ) return true;
    return false;
}



// ═══════════════════════════════════════════════════════════════
// 1. CUSTOMIZER SETTINGS
// ═══════════════════════════════════════════════════════════════
add_action( 'customize_register', function( $wp_customize ) {
	$wp_customize->add_section( 'wk_seo', [
		'title'    => __( '🔍 SEO & Meta Tags', 'whitekurti' ),
		'panel'    => 'wk_panel',
		'priority' => 80,
	] );

	$fields = [
		[ 'wk_seo_default_desc',     'Default Meta Description', 'textarea', 'Elegant white Indian kurtas and ethnic wear — free delivery, 5-day returns.', 'Used on homepage and pages with no custom excerpt.' ],
		[ 'wk_seo_og_image',         'Default Social Share Image (URL)', 'url', '', 'Used when sharing your homepage on Facebook/WhatsApp.' ],
		[ 'wk_seo_twitter_handle',   'Twitter / X Handle', 'text', '', 'e.g. @whitekurti (used in Twitter card meta)' ],
		[ 'wk_seo_org_name',         'Business Name for Schema', 'text', '', 'Your official business name for Google structured data.' ],
		[ 'wk_seo_org_phone',        'Business Phone (Schema)', 'text', '', 'e.g. +91 98765 43210' ],
		[ 'wk_seo_org_email',        'Business Email (Schema)', 'text', '', 'e.g. hello@whitekurti.com' ],
		[ 'wk_seo_org_address',      'Business Address (Schema)', 'textarea', '', 'Street, City, State, PIN' ],
		[ 'wk_seo_separator',        'Title Separator', 'text', '|', 'Character between page title and site name. e.g. | or — or ·' ],
		[ 'wk_seo_noindex_search',   'Noindex search results pages', 'checkbox', true, 'Recommended: prevents duplicate content.' ],
		[ 'wk_seo_noindex_account',  'Noindex account/cart pages', 'checkbox', true, 'Recommended: keeps private pages out of Google.' ],
		[ 'wk_seo_ga4_id',           'Google Analytics 4 ID', 'text', '', 'e.g. G-XXXXXXXXXX' ],
		[ 'wk_seo_gtm_id',           'Google Tag Manager ID', 'text', '', 'e.g. GTM-XXXXXXX' ],
		[ 'wk_seo_fb_pixel_id',      'Facebook / Meta Pixel ID', 'text', '', 'Your 15-16 digit Pixel ID' ],
		[ 'wk_seo_gsc_code',         'Google Search Console Verify Code', 'text', '', 'Content value from the meta tag (not the full tag)' ],
	];

	foreach ( $fields as [ $id, $label, $type, $default, $desc ] ) {
		$san = $type === 'checkbox' ? 'rest_sanitize_boolean' : ( $type === 'url' ? 'esc_url_raw' : 'sanitize_text_field' );
		$wp_customize->add_setting( $id, [ 'default' => $default, 'sanitize_callback' => $san, 'transport' => 'refresh' ] );
		$wp_customize->add_control( $id, [ 'label' => $label, 'description' => $desc, 'section' => 'wk_seo', 'type' => $type ] );
	}
} );

// ═══════════════════════════════════════════════════════════════
// 2. META TAGS OUTPUT
// ═══════════════════════════════════════════════════════════════
function wk_seo_head() {
	// If Yoast SEO, RankMath, AIOSEO, SEOPress or The SEO Framework is active,
	// skip ALL meta/OG/Twitter output — those plugins handle it fully.
	// JSON-LD schema is output separately via wk_json_ld() and is always safe.
	if ( wk_seo_plugin_active() ) return;

	global $post;

	$site_name = get_bloginfo( 'name' );
	$sep       = get_theme_mod( 'wk_seo_separator', '|' );

	// ── Title ──
	// (handled by wp_title / title-tag support — but we set a filter)

	// ── Description ──
	$description = '';
	if ( is_singular() && $post ) {
		$description = $post->post_excerpt ?: wp_trim_words( strip_shortcodes( $post->post_content ), 25, '' );
	} elseif ( class_exists('WooCommerce') && is_shop() ) {
		$description = get_bloginfo('description') ?: get_theme_mod('wk_seo_default_desc');
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();
		$description = $term ? term_description( $term->term_id, $term->taxonomy ) : '';
		$description = strip_tags( $description );
	}
	$description = $description ?: get_theme_mod( 'wk_seo_default_desc', '' );
	$description = wp_strip_all_tags( $description );

	// ── OG Image ──
	$og_image = '';
	if ( is_singular() && has_post_thumbnail() ) {
		$og_image = get_the_post_thumbnail_url( $post, 'large' );
	} elseif ( is_singular( 'product' ) && $post ) {
		$product = wc_get_product( $post->ID );
		if ( $product ) {
			$img_id   = $product->get_image_id();
			$og_image = $img_id ? wp_get_attachment_image_url( $img_id, 'large' ) : '';
		}
	}
	if ( ! $og_image ) {
		$og_image = get_theme_mod( 'wk_seo_og_image', '' );
	}
	if ( ! $og_image ) {
		$og_image = get_site_icon_url( 512 );
	}

	// ── Canonical ──
	$canonical = get_permalink();
	if ( is_front_page() ) $canonical = home_url('/');
	if ( is_paged() ) $canonical = '';

	// ── Noindex ──
	$noindex = false;
	if ( get_theme_mod('wk_seo_noindex_search', true) && is_search() ) $noindex = true;
	if ( get_theme_mod('wk_seo_noindex_account', true) && ( is_account_page() || is_cart() || is_checkout() ) ) $noindex = true;

	// ── Title string ──
	$title = wp_get_document_title();

	// ── OG type ──
	$og_type = is_singular('product') ? 'product' : ( is_singular() ? 'article' : 'website' );

	// ── Twitter handle ──
	$twitter = get_theme_mod( 'wk_seo_twitter_handle', '' );
	$twitter = $twitter ? '@' . ltrim($twitter, '@') : '';

	// ── GSC verification ──
	$gsc = get_theme_mod( 'wk_seo_gsc_code', '' );
	?>

	<!-- WhiteKurti SEO Meta Tags -->
	<?php if ( $description ) : ?>
	<meta name="description" content="<?php echo esc_attr( $description ); ?>" />
	<?php endif; ?>
	<?php if ( $noindex ) : ?>
	<meta name="robots" content="noindex, nofollow" />
	<?php else : ?>
	<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
	<?php endif; ?>
	<?php if ( $canonical ) : ?>
	<link rel="canonical" href="<?php echo esc_url( $canonical ); ?>" />
	<?php endif; ?>
	<?php if ( $gsc ) : ?>
	<meta name="google-site-verification" content="<?php echo esc_attr($gsc); ?>" />
	<?php endif; ?>

	<!-- Open Graph -->
	<meta property="og:title" content="<?php echo esc_attr( $title ); ?>" />
	<meta property="og:type" content="<?php echo esc_attr( $og_type ); ?>" />
	<meta property="og:url" content="<?php echo esc_url( get_permalink() ?: home_url('/') ); ?>" />
	<meta property="og:site_name" content="<?php echo esc_attr( $site_name ); ?>" />
	<?php if ( $description ) : ?>
	<meta property="og:description" content="<?php echo esc_attr( $description ); ?>" />
	<?php endif; ?>
	<?php if ( $og_image ) : ?>
	<meta property="og:image" content="<?php echo esc_url( $og_image ); ?>" />
	<meta property="og:image:width" content="1200" />
	<meta property="og:image:height" content="630" />
	<?php endif; ?>
	<?php if ( is_singular('product') && isset($product) && $product ) : ?>
	<meta property="product:price:amount" content="<?php echo esc_attr( $product->get_price() ); ?>" />
	<meta property="product:price:currency" content="INR" />
	<meta property="product:availability" content="<?php echo $product->is_in_stock() ? 'in stock' : 'out of stock'; ?>" />
	<?php endif; ?>

	<!-- Twitter / X Card -->
	<meta name="twitter:card" content="summary_large_image" />
	<?php if ( $twitter ) : ?><meta name="twitter:site" content="<?php echo esc_attr($twitter); ?>" /><?php endif; ?>
	<meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>" />
	<?php if ( $description ) : ?><meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>" /><?php endif; ?>
	<?php if ( $og_image ) : ?><meta name="twitter:image" content="<?php echo esc_url( $og_image ); ?>" /><?php endif; ?>

	<!-- WhatsApp / Telegram sharing -->
	<meta property="og:locale" content="en_IN" />
	<?php
}
add_action( 'wp_head', 'wk_seo_head', 2 );

// ═══════════════════════════════════════════════════════════════
// 3. ANALYTICS & TRACKING
// ═══════════════════════════════════════════════════════════════
function wk_analytics_head() {
	$ga4  = get_theme_mod( 'wk_seo_ga4_id', '' );
	$gtm  = get_theme_mod( 'wk_seo_gtm_id', '' );

	if ( $gtm ) : ?>
	<!-- Google Tag Manager -->
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo esc_js($gtm); ?>');</script>
	<!-- End GTM -->
	<?php elseif ( $ga4 ) : ?>
	<!-- Google Analytics 4 -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($ga4); ?>"></script>
	<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?php echo esc_js($ga4); ?>');</script>
	<!-- End GA4 -->
	<?php endif;
}
add_action( 'wp_head', 'wk_analytics_head', 5 );

function wk_analytics_body() {
	$gtm = get_theme_mod( 'wk_seo_gtm_id', '' );
	if ( $gtm ) :
	?><noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr($gtm); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript><?php
	endif;
}
add_action( 'wp_body_open', 'wk_analytics_body', 1 );

function wk_fb_pixel_head() {
	$pixel = get_theme_mod( 'wk_seo_fb_pixel_id', '' );
	if ( ! $pixel ) return;
	?>
	<!-- Meta Pixel -->
	<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?php echo esc_js($pixel); ?>');fbq('track','PageView');</script>
	<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo esc_attr($pixel); ?>&ev=PageView&noscript=1"/></noscript>
	<!-- End Meta Pixel -->
	<?php
}
add_action( 'wp_head', 'wk_fb_pixel_head', 6 );

// WooCommerce purchase event for Meta Pixel & GA4
function wk_tracking_purchase_event( $order_id ) {
	if ( ! $order_id ) return;
	$order  = wc_get_order( $order_id );
	if ( ! $order ) return;
	$pixel  = get_theme_mod( 'wk_seo_fb_pixel_id', '' );
	$ga4    = get_theme_mod( 'wk_seo_ga4_id', '' );
	$total  = $order->get_total();
	$items  = [];
	foreach ( $order->get_items() as $item ) {
		$items[] = [ 'id' => $item->get_product_id(), 'name' => $item->get_name(), 'quantity' => $item->get_quantity(), 'price' => $item->get_total() ];
	}
	?>
	<script>
	<?php if ($pixel) : ?>
	if(typeof fbq==='function'){fbq('track','Purchase',{value:<?php echo (float)$total; ?>,currency:'INR',contents:<?php echo json_encode(array_map(function($i){return['id'=>$i['id'],'quantity'=>$i['quantity']];}, $items)); ?>,content_type:'product'});}
	<?php endif; ?>
	<?php if ($ga4) : ?>
	if(typeof gtag==='function'){gtag('event','purchase',{transaction_id:'<?php echo esc_js($order_id); ?>',value:<?php echo (float)$total; ?>,currency:'INR',items:<?php echo json_encode(array_map(function($i){return['item_id'=>$i['id'],'item_name'=>$i['name'],'quantity'=>$i['quantity'],'price'=>(float)$i['price']];}, $items)); ?>});}
	<?php endif; ?>
	</script>
	<?php
}
add_action( 'woocommerce_thankyou', 'wk_tracking_purchase_event', 30 );

// ═══════════════════════════════════════════════════════════════
// 4. JSON-LD STRUCTURED DATA
// ═══════════════════════════════════════════════════════════════
function wk_json_ld() {
	$schemas = [];

	// ── Organization (all pages) ──
	$org_name    = get_theme_mod( 'wk_seo_org_name', get_bloginfo('name') );
	$org_phone   = get_theme_mod( 'wk_seo_org_phone', '' );
	$org_email   = get_theme_mod( 'wk_seo_org_email', '' );
	$org_address = get_theme_mod( 'wk_seo_org_address', '' );
	$org_logo    = get_custom_logo() ? wp_get_attachment_image_src( get_theme_mod('custom_logo'), 'full' ) : null;
	$og_image_url= get_theme_mod('wk_seo_og_image','') ?: get_site_icon_url(512);

	$org = [
		'@type'  => 'Organization',
		'@id'    => home_url('/#organization'),
		'name'   => $org_name,
		'url'    => home_url('/'),
		'logo'   => [
			'@type' => 'ImageObject',
			'url'   => $org_logo ? $org_logo[0] : $og_image_url,
		],
		'sameAs' => array_filter([
			get_theme_mod('wk_social_instagram_url',''),
			get_theme_mod('wk_social_facebook_url',''),
			get_theme_mod('wk_social_youtube_url',''),
			get_theme_mod('wk_social_twitter_url',''),
		]),
	];
	if ( $org_phone ) $org['telephone'] = $org_phone;
	if ( $org_email ) $org['email']     = $org_email;
	if ( $org_address ) $org['address'] = [ '@type' => 'PostalAddress', 'streetAddress' => $org_address, 'addressCountry' => 'IN' ];

	// ── WebSite with SearchAction ──
	$website = [
		'@type'           => 'WebSite',
		'@id'             => home_url('/#website'),
		'url'             => home_url('/'),
		'name'            => get_bloginfo('name'),
		'publisher'       => [ '@id' => home_url('/#organization') ],
		'potentialAction' => [
			'@type'        => 'SearchAction',
			'target'       => [ '@type' => 'EntryPoint', 'urlTemplate' => home_url('/?s={search_term_string}') ],
			'query-input'  => 'required name=search_term_string',
		],
	];
	$schemas[] = $org;
	$schemas[] = $website;

	// ── Product page ──
	// NOTE: Full enhanced product schema (with color, material, size, GTIN, FAQPage etc.)
	// is now output by inc/seo-advanced.php → wk_adv_inject_enhanced_product_schema()
	// We skip it here to avoid duplicate Product schema in the same <head>.
	if ( false && is_singular('product') ) {
		// Intentionally disabled — handled by seo-advanced.php
	}

	// ── Breadcrumb ──
	$breadcrumbs = wk_get_breadcrumbs_data();
	if ( count($breadcrumbs) > 1 ) {
		$crumb_items = [];
		foreach ( $breadcrumbs as $i => $crumb ) {
			$item = [ '@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb['name'] ];
			if ( $crumb['url'] ) $item['item'] = $crumb['url'];
			$crumb_items[] = $item;
		}
		$schemas[] = [
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $crumb_items,
		];
	}

	// ── Output ──
	if ( $schemas ) :
	?>
	<script type="application/ld+json"><?php echo wp_json_encode( [ '@context' => 'https://schema.org', '@graph' => $schemas ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>
	<?php
	endif;
}
add_action( 'wp_head', 'wk_json_ld', 4 );

// ═══════════════════════════════════════════════════════════════
// 5. BREADCRUMBS
// ═══════════════════════════════════════════════════════════════
function wk_get_breadcrumbs_data() {
	$crumbs    = [];
	$crumbs[]  = [ 'name' => 'Home', 'url' => home_url('/') ];

	if ( is_shop() ) {
		$crumbs[] = [ 'name' => get_the_title( wc_get_page_id('shop') ), 'url' => '' ];
	} elseif ( is_singular('product') ) {
		$crumbs[] = [ 'name' => get_the_title( wc_get_page_id('shop') ), 'url' => wc_get_page_permalink('shop') ];
		$terms    = get_the_terms( get_the_ID(), 'product_cat' );
		if ( $terms && ! is_wp_error($terms) ) {
			$term = $terms[0];
			if ( $term->parent ) {
				$parent = get_term( $term->parent, 'product_cat' );
				if ( ! is_wp_error($parent) ) {
					$crumbs[] = [ 'name' => $parent->name, 'url' => get_term_link($parent) ];
				}
			}
			$crumbs[] = [ 'name' => $term->name, 'url' => get_term_link($term) ];
		}
		$crumbs[] = [ 'name' => get_the_title(), 'url' => '' ];
	} elseif ( is_tax('product_cat') || is_tax('product_tag') ) {
		$term     = get_queried_object();
		$crumbs[] = [ 'name' => get_the_title( wc_get_page_id('shop') ), 'url' => wc_get_page_permalink('shop') ];
		if ( $term->parent ) {
			$parent = get_term( $term->parent, $term->taxonomy );
			if ( ! is_wp_error($parent) ) $crumbs[] = [ 'name' => $parent->name, 'url' => get_term_link($parent) ];
		}
		$crumbs[] = [ 'name' => $term->name, 'url' => '' ];
	} elseif ( is_singular() ) {
		$crumbs[] = [ 'name' => get_the_title(), 'url' => '' ];
	} elseif ( is_search() ) {
		$crumbs[] = [ 'name' => 'Search: ' . get_search_query(), 'url' => '' ];
	}

	return $crumbs;
}

function wk_breadcrumbs_html() {
	// Don't show on homepage
	if ( is_front_page() ) return;
	$crumbs = wk_get_breadcrumbs_data();
	if ( count($crumbs) < 2 ) return;

	echo '<nav class="wk-breadcrumbs" aria-label="Breadcrumb">';
	echo '<ol class="wk-breadcrumbs__list" itemscope itemtype="https://schema.org/BreadcrumbList">';
	foreach ( $crumbs as $i => $crumb ) {
		$last = $i === count($crumbs) - 1;
		echo '<li class="wk-breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
		if ( ! $last && $crumb['url'] ) {
			echo '<a href="' . esc_url($crumb['url']) . '" class="wk-breadcrumbs__link" itemprop="item"><span itemprop="name">' . esc_html($crumb['name']) . '</span></a>';
		} else {
			echo '<span class="wk-breadcrumbs__current" itemprop="name" aria-current="page">' . esc_html($crumb['name']) . '</span>';
		}
		echo '<meta itemprop="position" content="' . ($i+1) . '" />';
		if ( ! $last ) echo '<span class="wk-breadcrumbs__sep" aria-hidden="true">›</span>';
		echo '</li>';
	}
	echo '</ol></nav>';
}

// Hook breadcrumbs into shop/product pages
add_action( 'woocommerce_before_main_content', function() {
	wk_breadcrumbs_html();
}, 5 );

// ═══════════════════════════════════════════════════════════════
// 6. TITLE TAG FILTER
// ═══════════════════════════════════════════════════════════════
// wp_title deprecated in WP 4.4 - use document_title_parts instead
add_filter( 'document_title_parts', function( $title_parts ) {
    if ( is_singular( 'product' ) ) {
        global $post;
        $sep = get_theme_mod( 'wk_title_sep', ' | ' );
        $site = get_bloginfo( 'name' );
        if ( isset($title_parts['title']) && $site && strpos($title_parts['title'], $site) === false ) {
            $title_parts['site'] = $site;
        }
    }
    return $title_parts;
} );

// ═══════════════════════════════════════════════════════════════
// 7. SITEMAP HINT IN ROBOTS
// ═══════════════════════════════════════════════════════════════
add_filter( 'robots_txt', function( $output, $public ) {
	$output .= "\nSitemap: " . home_url('/wp-sitemap.xml') . "\n";
	return $output;
}, 10, 2 );
