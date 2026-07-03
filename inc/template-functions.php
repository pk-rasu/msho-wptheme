<?php
/**
 * WhiteKurti — inc/template-functions.php
 * All template helpers. No WC functions called outside WC guards.
 * FIX BUG 5: wk_footer_fallback_menu moved here from footer.php.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ─── Helpers ─────────────────────────────────────────────────────────────────
function wk_brand() {
	return get_theme_mod( 'wk_brand_mode', 'white' );
}
function wk_format_price( $price ) {
	return '&#8377;' . number_format( (float) $price, 0, '.', ',' );
}

// ─── SVG Icon ─────────────────────────────────────────────────────────────────
function wk_icon( $name, $size = 20, $extra_attrs = '' ) {
	static $paths = null;
	if ( $paths === null ) {
		$paths = [
			'menu'    => '<line x1="3" y1="7" x2="21" y2="7"/><line x1="3" y1="17" x2="21" y2="17"/>',
			'search'  => '<circle cx="11" cy="11" r="7"/><line x1="20" y1="20" x2="16.65" y2="16.65"/>',
			'bag'     => '<path d="M5 8h14l-1.5 12h-11Z"/><path d="M9 8a3 3 0 016 0"/>',
			'heart'   => '<path d="M20.84 4.6a5.5 5.5 0 00-7.78 0L12 5.66l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21l7.78-7.56 1.06-1.06a5.5 5.5 0 000-7.78z"/>',
			'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
			'close'   => '<line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/>',
			'chev-d'  => '<polyline points="6 9 12 15 18 9"/>',
			'chev-r'  => '<polyline points="9 6 15 12 9 18"/>',
			'chev-l'  => '<polyline points="15 6 9 12 15 18"/>',
			'filter'  => '<line x1="4" y1="6" x2="20" y2="6"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="10" y1="18" x2="14" y2="18"/>',
			'sort'    => '<path d="M7 4v16M4 7l3-3 3 3M17 20V4M14 17l3 3 3-3"/>',
			'check'   => '<polyline points="5 12 10 17 19 7"/>',
			'truck'   => '<rect x="2" y="7" width="11" height="10"/><path d="M13 10h5l3 3v4h-8z"/><circle cx="6" cy="19" r="2"/><circle cx="17" cy="19" r="2"/>',
			'shield'  => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
			'return'  => '<path d="M4 7h10a5 5 0 010 10H8"/><polyline points="8 11 4 7 8 3"/>',
			'leaf'    => '<path d="M5 19c0-7 7-14 14-14-2 8-7 14-14 14z"/><path d="M5 19l8-8"/>',
		'kurti'   => '<path d="M8 2l-4 5v15h16V7l-4-5" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 2c0 2.5 2 4 4 4s4-1.5 4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 7l4 2M22 7l-4 2" stroke-linecap="round" stroke-linejoin="round"/>',
			'arrow-r' => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="13 6 19 12 13 18"/>',
			'pin'     => '<path d="M12 21s-7-7-7-12a7 7 0 0114 0c0 5-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
			'star'    => '<polygon points="12 2 15 8.6 22.3 9.4 16.9 14.4 18.4 21.6 12 18 5.6 21.6 7.1 14.4 1.7 9.4 9 8.6"/>',
			'home'    => '<path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z"/><polyline points="9 21 9 12 15 12 15 21"/>',
			'refresh' => '<polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>',
		];
	}
	$p = $paths[ $name ] ?? '';
	if ( ! $p ) return;
	$s = absint( $size );
	echo '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"' . ( $extra_attrs ? ' ' . $extra_attrs : '' ) . '>' . $p . '</svg>';
}

// ─── Announcement strip ───────────────────────────────────────────────────────
function wk_announcement_strip() {
	$text = get_theme_mod( 'wk_announcement', 'Free Delivery on All Orders  &middot;  5-Day Easy Returns  &middot;  New Arrivals Every Thursday' );
	if ( ! $text ) return;
	echo '<div class="wk-promo-strip" role="marquee" aria-label="' . esc_attr__( 'Announcements', 'whitekurti' ) . '">';
	echo '<span>' . wp_kses_post( $text ) . '</span>';
	echo '</div>';
}

// ─── Trust strip ─────────────────────────────────────────────────────────────
function wk_trust_strip() {
	$items = [
		[ 'icon' => 'truck',  'label' => get_theme_mod('wk_trust1_title', 'FREE Delivery'), 'sub' => get_theme_mod('wk_trust1_sub', 'On All Orders') ],
		[ 'icon' => 'return', 'label' => get_theme_mod('wk_trust2_title', '5-Day Returns'), 'sub' => get_theme_mod('wk_trust2_sub', 'Easy free pickup') ],
		[ 'icon' => 'shield', 'label' => get_theme_mod('wk_trust3_title', 'Secure Pay'),    'sub' => get_theme_mod('wk_trust3_sub', 'UPI · Card · COD') ],
		[ 'icon' => 'kurti',  'label' => get_theme_mod('wk_trust4_title', 'New Products Every Sunday'),  'sub' => get_theme_mod('wk_trust4_sub', 'Fresh drops weekly') ],
	];
	echo '<div class="wk-trust-strip">';
	foreach ( $items as $item ) {
		echo '<div class="wk-trust-item">';
		echo '<div class="wk-trust-item__icon">';
		wk_icon( $item['icon'], 28 );
		echo '</div>';
		echo '<div class="wk-trust-item__title">' . $item['label'] . '</div>';
		echo '<div class="wk-trust-item__desc">' . $item['sub'] . '</div>';
		echo '</div>';
	}
	echo '</div>';
}

// ─── Product card ─────────────────────────────────────────────────────────────
function wk_product_card( $product, $args = [] ) {
	if ( ! class_exists( 'WC_Product' ) ) return;
	if ( ! ( $product instanceof WC_Product ) ) {
		$product = wc_get_product( $product );
		if ( ! $product ) return;
	}
	$defaults = [ 'layout' => 'editorial', 'show_badge' => true, 'show_wish' => true ];
	$args     = wp_parse_args( $args, $defaults );

	$id         = $product->get_id();
	$title      = $product->get_name();
	$price      = (float) $product->get_price();
	$reg_price  = (float) $product->get_regular_price();
	$on_sale    = $product->is_on_sale() && $reg_price > 0;
	$pct        = $on_sale ? round( ( 1 - $price / $reg_price ) * 100 ) : 0;
	$permalink  = get_permalink( $id );
	$img_id     = $product->get_image_id();
	$img        = $img_id ? wp_get_attachment_image( $img_id, 'wk-product-card', false, [ 'class' => 'wk-pcard__img', 'loading' => 'lazy' ] ) : '';
	if ( ! $img ) {
		$demo_src = get_post_meta( $id, '_wk_demo_img', true );
		if ( $demo_src ) { $img = '<img src="' . esc_url($demo_src) . '" alt="' . esc_attr($title) . '" class="wk-pcard__img" loading="lazy" />'; }
	}
	$cat_list   = wc_get_product_category_list( $id );
	$cat_text   = $cat_list ? wp_strip_all_tags( $cat_list ) : '';

	$badge_html = '';
	if ( $args['show_badge'] ) {
		if ( $on_sale )                     $badge_html = '<span class="wk-badge wk-badge--sale">&#x2212;' . $pct . '%</span>';
		elseif ( $product->is_featured() )  $badge_html = '<span class="wk-badge wk-badge--new">New</span>';
	}

	$wish_html = '';
	if ( $args['show_wish'] ) {
		$wish_html = '<button class="wk-wish-btn" data-product-id="' . absint( $id ) . '" aria-label="' . esc_attr__( 'Add to wishlist', 'whitekurti' ) . '" type="button">'
			. '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.6a5.5 5.5 0 00-7.78 0L12 5.66l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21l7.78-7.56 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>'
			. '</button>';
	}

	$price_html  = '<span class="wk-price">' . wk_format_price( $price ) . '</span>';
	if ( $on_sale ) {
		$price_html .= ' <span class="wk-price-was">' . wk_format_price( $reg_price ) . '</span>';
		$price_html .= ' <span class="wk-price-save">&#x2212;' . $pct . '%</span>';
	}

	$layout_class = 'wk-pcard--' . sanitize_html_class( $args['layout'] );
	?>
	<article class="wk-pcard <?php echo esc_attr( $layout_class ); ?>">
		<a href="<?php echo esc_url( $permalink ); ?>" class="wk-pcard-link">
			<div class="wk-pcard__media">
				<?php echo $badge_html; echo $wish_html; ?>
				<?php if ( $img ) : echo $img; else : 
					// Fallback to a stunning fashion image to maintain Libas aesthetic on empty installs
					// Use local theme demo images — never external URLs that may load wrong content
					$fallback_imgs = [
						WK_URI . '/assets/images/product-1.png',
						WK_URI . '/assets/images/product-2.png',
						WK_URI . '/assets/images/product-3.png',
						WK_URI . '/assets/images/product-4.png',
					];
					$random_fallback = $fallback_imgs[absint($id) % count($fallback_imgs)];
				?>
				<img src="<?php echo esc_url($random_fallback); ?>" alt="<?php echo esc_attr($title); ?>" class="wk-pcard__img" loading="lazy">
				<?php endif; ?>
			</div>
			<div class="wk-pcard-info">
				<?php if ( $args['layout'] === 'editorial' && $cat_text ) : ?>
				<span class="wk-pcard-cat"><?php echo esc_html( $cat_text ); ?></span>
				<?php endif; ?>
				<h3 class="wk-pcard-title"><?php echo esc_html( $title ); ?></h3>
				<div class="wk-pcard-price"><?php echo $price_html; ?></div>
			</div>
		</a>
		<button class="wk-quick-atc" data-product-id="<?php echo absint( $id ); ?>" type="button">
			<?php echo esc_html( get_theme_mod('wk_text_add_to_cart', 'Add to Cart') ); ?>
		</button>
	</article>
	<?php
}

// ─── Cart Drawer HTML ─────────────────────────────────────────────────────────
function wk_cart_drawer() {
	$wc = class_exists( 'WooCommerce' );
	?>
	<div id="wk-cart-overlay" class="wk-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Shopping cart', 'whitekurti' ); ?>">
		<div class="wk-overlay__backdrop" id="wk-cart-backdrop"></div>
		<div class="wk-cart-drawer">
			<div class="wk-cart-drawer__head">
				<span class="wk-cart-drawer__title">
					<?php echo esc_html( get_theme_mod('wk_text_cart_title', 'Cart') ); ?>
					<span class="wk-cart-count<?php echo ( $wc && WC()->cart->get_cart_contents_count() ) ? ' has-items' : ''; ?>">
						<?php echo $wc ? absint( WC()->cart->get_cart_contents_count() ) : 0; ?>
					</span>
				</span>
				<button class="wk-icon-btn" id="wk-cart-close" aria-label="<?php esc_attr_e( 'Close cart', 'whitekurti' ); ?>" type="button">
					<?php wk_icon( 'close', 20 ); ?>
				</button>
			</div>
			<div class="wk-cart-drawer-items">
				<?php if ( $wc ) woocommerce_mini_cart(); else echo '<p class="wk-cart-drawer__empty-msg">' . esc_html__( 'WooCommerce required.', 'whitekurti' ) . '</p>'; ?>
			</div>
			<div class="wk-cart-drawer__foot">
				<?php if ( $wc ) : ?>
				<div class="wk-cart-drawer__total">
					<span><?php echo esc_html( get_theme_mod('wk_text_subtotal', 'Subtotal') ); ?></span>
					<strong><?php echo WC()->cart->get_cart_subtotal(); ?></strong>
				</div>
				<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="wk-btn wk-btn--full">
					<?php echo esc_html( get_theme_mod('wk_text_checkout', 'Checkout') ); ?>
				</a>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="wk-btn wk-btn--outline wk-btn--full wk-cart-drawer__view-cart">
					<?php echo esc_html( get_theme_mod('wk_text_view_cart', 'View Cart') ); ?>
				</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}

// ─── Mobile Menu HTML ─────────────────────────────────────────────────────────
function wk_mobile_menu() {
	$brand_name = wk_brand() === 'black' ? 'BlackKurti' : 'WhiteKurti';
	$shop_url   = class_exists('WooCommerce') ? get_permalink( wc_get_page_id('shop') ) : home_url('/shop');
	?>
	<div id="wk-menu-overlay" class="wk-overlay" aria-hidden="true" role="dialog" aria-modal="true"
	     aria-label="<?php esc_attr_e( 'Navigation menu', 'whitekurti' ); ?>">
		<div class="wk-overlay__backdrop" id="wk-menu-backdrop"></div>

		<div class="wk-mobile-menu">

			<!-- Header row -->
			<div class="wk-mobile-menu__head">
				<a href="<?php echo esc_url( home_url('/') ); ?>" class="wk-mobile-menu__brand">
					<?php echo esc_html( $brand_name ); ?>
				</a>
				<button class="wk-icon-btn" id="wk-menu-close"
				        aria-label="<?php esc_attr_e( 'Close menu', 'whitekurti' ); ?>"
				        type="button">
					<?php wk_icon( 'close', 22 ); ?>
				</button>
			</div>

			<!-- Inline search bar -->
			<div style="padding:12px 16px;border-bottom:.5px solid var(--line,#e8e4de);">
				<form role="search" method="get" action="<?php echo esc_url( home_url('/') ); ?>"
				      style="display:flex;align-items:center;gap:8px;background:var(--surface-2,#f5f0eb);border-radius:6px;padding:9px 14px;">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;opacity:.45;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
					<input type="search" name="s" placeholder="Search products…"
					       style="background:none;border:none;outline:none;font-size:14px;width:100%;color:var(--ink,#111);"
					       autocomplete="off" />
					<input type="hidden" name="post_type" value="product" />
				</form>
			</div>

			<!-- Navigation links (from WP menu / fallback) -->
			<nav class="wk-mobile-nav" aria-label="<?php esc_attr_e( 'Mobile navigation', 'whitekurti' ); ?>"
			     style="flex:1;overflow-y:auto;overscroll-behavior:contain;">
				<?php
				wp_nav_menu( [
					'theme_location' => 'primary',
					'menu_class'     => 'wk-mobile-nav__list',
					'container'      => false,
					'fallback_cb'    => 'wk_fallback_menu',
					'depth'          => 2,
				] );
				?>
			</nav>

			<!-- Category quick-tiles -->
			<?php
			if ( class_exists('WooCommerce') ) :
				$cats = get_terms( [
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
					'number'     => 8,
					'parent'     => 0,
					'exclude'    => [ get_option('default_product_cat') ],
					'orderby'    => 'count',
					'order'      => 'DESC',
				] );
				if ( ! is_wp_error($cats) && ! empty($cats) ) :
			?>
			<div style="padding:14px 16px 10px;border-top:.5px solid var(--line,#e8e4de);background:var(--surface-2,#f9f7f4);">
				<p style="font-size:9.5px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:#999;margin:0 0 12px;">SHOP BY CATEGORY</p>
				<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
					<?php foreach ( $cats as $cat ) :
						$t_url = get_term_link( $cat );
						if ( is_wp_error($t_url) ) $t_url = $shop_url;
						$thumb_id  = get_term_meta( $cat->term_id, 'thumbnail_id', true );
						$thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '';
					?>
					<a href="<?php echo esc_url($t_url); ?>"
					   style="display:flex;flex-direction:column;align-items:center;gap:5px;text-decoration:none;">
						<div style="width:100%;aspect-ratio:1;border-radius:50%;overflow:hidden;background:var(--surface-3,#f0ede8);border:1.5px solid var(--line,#e8e4de);">
							<?php if ( $thumb_url ) : ?>
							<img src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr($cat->name); ?>"
							     style="width:100%;height:100%;object-fit:cover;display:block;" loading="lazy" />
							<?php else :
								// Generate a gradient color based on category name for unique visual
								$colors = ['#6B1E3E','#0a5a68','#1a1050','#4a1a00','#1a4a00','#00374a','#4a004a','#3a2000'];
								$color_idx = abs(crc32($cat->name)) % count($colors);
								$bg = $colors[$color_idx];
								$initial = strtoupper(mb_substr($cat->name, 0, 1));
							?>
							<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:<?php echo esc_attr($bg); ?>;color:#fff;font-size:18px;font-weight:800;font-family:serif;letter-spacing:-.01em;"><?php echo esc_html($initial); ?></div>
							<?php endif; ?>
						</div>
						<span style="font-size:9.5px;font-weight:600;color:var(--ink-soft,#555);text-align:center;line-height:1.3;letter-spacing:.01em;"><?php echo esc_html($cat->name); ?></span>
					</a>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; endif; ?>

			<!-- Footer links -->
			<div class="wk-mobile-menu__foot">
				<?php if ( class_exists('WooCommerce') ) : ?>
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id('myaccount') ) ); ?>"
				   class="wk-mobile-menu__link">
					<?php wk_icon('user', 16); ?> My Account
				</a>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>"
				   class="wk-mobile-menu__link">
					<?php wk_icon('bag', 16); ?> Cart
					<?php if ( WC()->cart && WC()->cart->get_cart_contents_count() ) :?>
					<span style="background:var(--accent,#6B1E3E);color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;font-weight:700;margin-left:auto;">
						<?php echo WC()->cart->get_cart_contents_count(); ?>
					</span>
					<?php endif; ?>
				</a>
				<?php endif; ?>
				<a href="<?php echo esc_url( home_url('/') ); ?>" class="wk-mobile-menu__link">
					<?php wk_icon('home', 16); ?> Home
				</a>
			</div>

		</div><!-- /.wk-mobile-menu -->
	</div><!-- /#wk-menu-overlay -->
	<?php
}

// ─── Search Overlay HTML ──────────────────────────────────────────────────────
function wk_search_overlay() {
	?>
	<div id="wk-search-overlay" class="wk-overlay" aria-hidden="true" role="search" aria-label="<?php esc_attr_e( 'Search', 'whitekurti' ); ?>">
		<div class="wk-overlay__backdrop" id="wk-search-backdrop"></div>
		<div class="wk-search-panel">
			<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<div class="wk-search-bar">
					<input type="search" name="s" id="wk-search-input"
						placeholder="<?php echo esc_attr( get_theme_mod('wk_text_search_placeholder', 'Search products...') ); ?>"
						autocomplete="off" value="<?php echo esc_attr( get_search_query() ); ?>" />
					<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<input type="hidden" name="post_type" value="product" />
					<?php endif; ?>
					<button class="wk-icon-btn" id="wk-search-close" type="button" aria-label="<?php esc_attr_e( 'Close search', 'whitekurti' ); ?>">
						<?php wk_icon( 'close', 20 ); ?>
					</button>
				</div>
			</form>
			<div id="wk-search-results" class="wk-search-results" aria-live="polite"></div>
		</div>
	</div>
	<?php
}

// ─── Toast container ─────────────────────────────────────────────────────────
function wk_toast_container() {
	echo '<div id="wk-toast" class="wk-toast" aria-live="polite" aria-atomic="true"></div>';
}

// ─── Fallback menu ─────────────────────────────────────────────────────────
// FIX BUG 5: moved here from footer.php
function wk_fallback_menu() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<ul class="wk-mobile-nav__list"><li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'whitekurti' ) . '</a></li></ul>';
		return;
	}
	$cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 10, 'parent' => 0 ] );
	if ( is_wp_error( $cats ) || empty( $cats ) ) {
		echo '<ul class="wk-mobile-nav__list"><li><a href="' . esc_url( home_url( '/shop' ) ) . '">' . esc_html__( 'Shop', 'whitekurti' ) . '</a></li></ul>';
		return;
	}
	echo '<ul class="wk-mobile-nav__list">';
	foreach ( $cats as $cat ) {
		echo '<li class="menu-item"><a href="' . esc_url( is_wp_error(get_term_link($cat)) ? "#" : get_term_link($cat) ) . '">' . esc_html( $cat->name ) . '</a></li>';
	}
	echo '</ul>';
}

function wk_footer_fallback_menu() {
	if ( ! class_exists( 'WooCommerce' ) ) return;
	$cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 6, 'parent' => 0 ] );
	if ( is_wp_error( $cats ) || empty( $cats ) ) return;
	echo '<ul class="wk-footer__nav-list">';
	foreach ( $cats as $cat ) {
		echo '<li><a href="' . esc_url( is_wp_error(get_term_link($cat)) ? "#" : get_term_link($cat) ) . '">' . esc_html( $cat->name ) . '</a></li>';
	}
	echo '</ul>';
}

// wk_desktop_fallback_menu() is defined in inc/nav-manager.php
