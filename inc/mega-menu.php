<?php
/**
 * WhiteKurti — Mega Menu + Mobile Menu Improvements
 * Desktop: hover mega panel with category images
 * Mobile: accordion sub-menus + category images
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ═══════════════════════════════════════════════════════════════
// 1. MEGA MENU
// ═══════════════════════════════════════════════════════════════

// ── Customizer settings ───────────────────────────────────────
add_action( 'customize_register', function( $wp_customize ) {
	$wp_customize->add_section( 'wk_mega_menu', [
		'title'    => __( '🗂️ Mega Menu', 'whitekurti' ),
		'panel'    => 'wk_panel',
		'priority' => 37,
	] );
	$fields = [
		[ 'wk_mm_enabled',      'Enable mega menu on desktop',        'checkbox', true,  '' ],
		[ 'wk_mm_hover_delay',  'Open delay (ms)',                    'number',   120,   'Delay before mega panel opens on hover (0–500ms)' ],
		[ 'wk_mm_show_images',  'Show category images in mega menu',  'checkbox', true,  '' ],
		[ 'wk_mm_show_counts',  'Show product count per category',    'checkbox', true,  '' ],
		[ 'wk_mm_show_promo',   'Show promo column (rightmost)',      'checkbox', false, 'Shows a featured product or custom promo image' ],
		[ 'wk_mm_promo_img',    'Promo column image URL',             'url',      '',    '' ],
		[ 'wk_mm_promo_text',   'Promo column text',                  'text',     'New Arrivals', '' ],
		[ 'wk_mm_promo_url',    'Promo column link',                  'url',      '',    '' ],
		[ 'wk_mm_bg',           'Mega panel background color',        'text',     '#FFFFFF', '' ],
		[ 'wk_mm_border_color', 'Mega panel border/accent color',     'text',     '#6B1E3E', '' ],
	];
	foreach ($fields as [$id,$label,$type,$default,$desc]) {
		$san = $type==='checkbox' ? 'rest_sanitize_boolean' : 'sanitize_text_field';
		$wp_customize->add_setting($id, ['default'=>$default,'sanitize_callback'=>$san,'transport'=>'refresh']);
		$ctrl = ['label'=>$label,'description'=>$desc,'section'=>'wk_mega_menu','type'=>$type];
		$wp_customize->add_control($id, $ctrl);
	}
} );

// ── Custom Walker for mega menu support ───────────────────────
class WK_Mega_Menu_Walker extends Walker_Nav_Menu {

	public function start_lvl( &$output, $depth = 0, $args = null ) {
		// Suppress default sub-menu output — we build our own mega panel
		if ( $depth === 0 ) return;
		parent::start_lvl( $output, $depth, $args );
	}

	public function end_lvl( &$output, $depth = 0, $args = null ) {
		if ( $depth === 0 ) return;
		parent::end_lvl( $output, $depth, $args );
	}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		if ( $depth === 0 ) {
			$has_children = in_array( 'menu-item-has-children', $item->classes );
			$classes      = implode( ' ', array_filter([
				'menu-item',
				'menu-item-' . $item->ID,
				$has_children ? 'wk-mm-trigger' : '',
				in_array('current-menu-item', $item->classes) ? 'is-active' : '',
			]));

			$mm_panel = '';
			if ( $has_children && get_theme_mod('wk_mm_enabled', true) ) {
				$mm_panel = $this->build_mega_panel( $item, $args );
			}

			$output .= '<li class="' . esc_attr($classes) . '">';
			$output .= '<a href="' . esc_url($item->url) . '" class="wk-desktop-nav__link' . ($has_children ? ' wk-mm-parent-link' : '') . '"';
			if ( $has_children ) $output .= ' aria-haspopup="true" aria-expanded="false"';
			$output .= '>' . esc_html($item->title) . '</a>';
			$output .= $mm_panel;
		} else {
			// Sub-items not shown separately — handled in mega panel
		}
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		if ( $depth === 0 ) $output .= '</li>';
	}

	private function build_mega_panel( $item, $args ) {
		global $wpdb;

		$show_images = get_theme_mod( 'wk_mm_show_images', true );
		$show_counts = get_theme_mod( 'wk_mm_show_counts', true );
		$show_promo  = get_theme_mod( 'wk_mm_show_promo', false );
		$promo_img   = get_theme_mod( 'wk_mm_promo_img', '' );
		$promo_text  = get_theme_mod( 'wk_mm_promo_text', 'New Arrivals' );
		$promo_url   = get_theme_mod( 'wk_mm_promo_url', '' );
		$bg          = get_theme_mod( 'wk_mm_bg', '#FFFFFF' );
		$accent      = get_theme_mod( 'wk_mm_border_color', '#6B1E3E' );

		// Get children of this nav item
		$children = [];
		if ( $args && isset($args->menu) ) {
			$all_items = wp_get_nav_menu_items( $args->menu );
			if ( $all_items ) {
				foreach ( $all_items as $child ) {
					if ( (int)$child->menu_item_parent === (int)$item->ID ) {
						$children[] = $child;
					}
				}
			}
		}

		// If no nav children, auto-populate from product categories
		if ( empty($children) && class_exists('WooCommerce') ) {
			// Try to find a product category that matches this menu item
			$term = null;
			$menu_url = $item->url;
			$cats = get_terms(['taxonomy'=>'product_cat','hide_empty'=>true,'number'=>12,'parent'=>0]);
			if (!is_wp_error($cats) && $cats) {
				foreach ($cats as $cat) {
					if (trailingslashit(get_term_link($cat)) === trailingslashit($menu_url)) {
						$term = $cat;
						break;
					}
				}
				if (!$term) {
					// Use all top-level cats as fallback
					foreach ($cats as $cat) {
						$children[] = (object)[
							'ID'    => $cat->term_id,
							'title' => $cat->name,
							'url'   => get_term_link($cat),
							'is_term'=> true,
							'term'  => $cat,
							'count' => $cat->count,
						];
					}
				} else {
					// Get children of this term
					$sub_cats = get_terms(['taxonomy'=>'product_cat','parent'=>$term->term_id,'hide_empty'=>false]);
					if (!is_wp_error($sub_cats) && $sub_cats) {
						foreach ($sub_cats as $sc) {
							$children[] = (object)[
								'ID'     => $sc->term_id,
								'title'  => $sc->name,
								'url'    => get_term_link($sc),
								'is_term'=> true,
								'term'   => $sc,
								'count'  => $sc->count,
							];
						}
					}
				}
			}
		}

		if ( empty($children) ) return '';

		// Split children into columns (max 5 per col, 3 cols)
		$col_size = max( 4, (int)ceil(count($children) / 3) );
		$cols     = array_chunk($children, $col_size);

		ob_start();
		?>
		<div class="wk-mega-panel" role="region"
		     style="background:<?php echo esc_attr($bg); ?>;border-top:2px solid <?php echo esc_attr($accent); ?>">
			<div class="wk-mega-panel__inner">
				<?php foreach ($cols as $col) : ?>
				<div class="wk-mega-panel__col">
					<?php foreach ($col as $child) :
						$is_term  = isset($child->is_term) && $child->is_term;
						$term     = $is_term ? $child->term : null;
						$term_id  = $is_term ? $child->ID : 0;
						$img_url  = ($show_images && $term_id) ? wk_get_category_image($term_id,'wk-category-card') : '';
						$count    = $show_counts ? ($is_term ? $child->count : 0) : 0;
					?>
					<a href="<?php echo esc_url($child->url); ?>" class="wk-mega-panel__item<?php echo $img_url ? ' has-img' : ''; ?>">
						<?php if ($img_url) : ?>
						<div class="wk-mega-panel__item-img">
							<img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($child->title); ?>" loading="lazy" />
						</div>
						<?php endif; ?>
						<span class="wk-mega-panel__item-name"><?php echo esc_html($child->title); ?></span>
						<?php if ($count) : ?>
						<span class="wk-mega-panel__item-count"><?php echo absint($count); ?></span>
						<?php endif; ?>
					</a>
					<?php endforeach; ?>
				</div>
				<?php endforeach; ?>

				<?php if ($show_promo && $promo_img) : ?>
				<div class="wk-mega-panel__promo">
					<a href="<?php echo esc_url($promo_url ?: home_url('/shop')); ?>" class="wk-mega-panel__promo-link">
						<img src="<?php echo esc_url($promo_img); ?>" alt="<?php echo esc_attr($promo_text); ?>" class="wk-mega-panel__promo-img" loading="lazy" />
						<?php if ($promo_text) : ?>
						<span class="wk-mega-panel__promo-text" style="color:<?php echo esc_attr($accent); ?>"><?php echo esc_html($promo_text); ?></span>
						<?php endif; ?>
					</a>
				</div>
				<?php endif; ?>
			</div><!-- /.wk-mega-panel__inner -->
		</div><!-- /.wk-mega-panel -->
		<?php
		return ob_get_clean();
	}
}

// ── Apply mega menu walker to desktop nav ─────────────────────
add_filter( 'wp_nav_menu_args', function( $args ) {
	if ( $args['theme_location'] !== 'primary' ) return $args;
	if ( ! get_theme_mod('wk_mm_enabled', true) ) return $args;
	if ( wp_is_mobile() ) return $args;
	$args['walker'] = new WK_Mega_Menu_Walker();
	$args['depth']  = 2;
	return $args;
} );

// ── Pass mega menu config to JS ───────────────────────────────
add_action( 'wp_enqueue_scripts', function() {
	wp_localize_script( 'wk-main', 'wk_mm_cfg', [
		'enabled'    => get_theme_mod('wk_mm_enabled', true) ? '1' : '0',
		'hover_delay'=> absint( get_theme_mod('wk_mm_hover_delay', 120) ),
	]);
}, 20 );

// ═══════════════════════════════════════════════════════════════
// 2. IMPROVED MOBILE MENU
// ═══════════════════════════════════════════════════════════════

// Replace the basic mobile menu with enhanced version
function wk_mobile_menu_v2() {
	$menu_items = [];
	$cats       = [];

	if ( class_exists('WooCommerce') ) {
		$cats = get_terms([
			'taxonomy'  => 'product_cat',
			'hide_empty'=> true,
			'number'    => 20,
			'parent'    => 0,
			'orderby'   => 'menu_order',
			'order'     => 'ASC',
		]);
		if ( is_wp_error($cats) ) $cats = [];
	}
	?>
	<div id="wk-mobile-menu" class="wk-mobile-menu" aria-hidden="true" role="dialog" aria-label="<?php esc_attr_e('Navigation menu','whitekurti'); ?>">
		<div class="wk-mobile-menu__overlay" id="wk-mm-overlay"></div>
		<div class="wk-mobile-menu__panel" role="navigation">

			<!-- Header -->
			<div class="wk-mobile-menu__header">
				<span class="wk-mobile-menu__brand"><?php echo esc_html(get_theme_mod('wk_brand_mode','white')==='black'?'BlackKurti':'WhiteKurti'); ?></span>
				<button class="wk-mobile-menu__close" id="wk-mm-close" aria-label="Close menu">&times;</button>
			</div>

			<!-- Search bar in menu -->
			<div class="wk-mobile-menu__search">
				<form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
					<div class="wk-mm-search-row">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
						<input type="search" name="s" placeholder="Search products..." class="wk-mm-search-input" autocomplete="off" />
						<?php if (class_exists('WooCommerce')): ?><input type="hidden" name="post_type" value="product"><?php endif; ?>
					</div>
				</form>
			</div>

			<!-- Nav items via wp_nav_menu -->
			<div class="wk-mobile-menu__nav">
				<?php
				wp_nav_menu([
					'theme_location' => 'primary',
					'menu_class'     => 'wk-mm-list',
					'container'      => false,
					'fallback_cb'    => 'wk_mobile_fallback_menu_items',
					'depth'          => 2,
					'walker'         => new WK_Mobile_Menu_Walker(),
				]);
				?>
			</div>

			<!-- Category quick-access with images -->
			<?php if (!empty($cats)) : ?>
			<div class="wk-mobile-menu__cats">
				<p class="wk-mm-cats__label">Browse Categories</p>
				<div class="wk-mm-cats-grid">
					<?php foreach (array_slice($cats, 0, 8) as $cat) :
						$img = wk_get_category_image($cat->term_id, 'thumbnail');
					?>
					<a href="<?php echo esc_url(get_term_link($cat)); ?>" class="wk-mm-cat-tile">
						<div class="wk-mm-cat-tile__img">
							<img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($cat->name); ?>" loading="lazy" />
						</div>
						<span class="wk-mm-cat-tile__name"><?php echo esc_html($cat->name); ?></span>
					</a>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<!-- Footer links -->
			<div class="wk-mobile-menu__footer">
				<?php if (class_exists('WooCommerce') && is_user_logged_in()) : ?>
				<a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="wk-mm-footer-link">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
					My Orders
				</a>
				<a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>" class="wk-mm-footer-link">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					My Account
				</a>
				<?php else : ?>
				<a href="<?php echo esc_url(class_exists('WooCommerce') ? wc_get_page_permalink('myaccount') : wp_login_url()); ?>" class="wk-mm-footer-link">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					Sign In / Register
				</a>
				<?php endif; ?>
				<?php
				$help_links = ['contact'=>'Contact Us','shipping'=>'Shipping','size-guide'=>'Size Guide'];
				foreach ($help_links as $slug => $label) :
					$p   = get_page_by_path($slug);
					$url = $p ? get_permalink($p->ID) : home_url('/'.$slug);
				?>
				<a href="<?php echo esc_url($url); ?>" class="wk-mm-footer-link"><?php echo esc_html($label); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php
}

// Custom Walker for mobile menu (adds accordion toggle buttons)
class WK_Mobile_Menu_Walker extends Walker_Nav_Menu {
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$has_children = in_array('menu-item-has-children', $item->classes);
		$is_active    = in_array('current-menu-item', $item->classes) || in_array('current-menu-ancestor', $item->classes);
		$indent       = str_repeat("\t", $depth);

		$classes = 'wk-mm-item' . ($depth ? ' wk-mm-item--sub' : '') . ($has_children ? ' wk-mm-item--parent' : '') . ($is_active ? ' is-active' : '');
		$output  .= $indent . '<li class="' . esc_attr($classes) . '">';

		if ($depth === 0 && $has_children) {
			$output .= '<div class="wk-mm-item__row">';
			$output .= '<a href="' . esc_url($item->url) . '" class="wk-mm-item__link">' . esc_html($item->title) . '</a>';
			$output .= '<button class="wk-mm-accordion-btn" aria-expanded="false" aria-label="Expand ' . esc_attr($item->title) . '">';
			$output .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>';
			$output .= '</button>';
			$output .= '</div>';
		} else {
			$output .= '<a href="' . esc_url($item->url) . '" class="wk-mm-item__link">' . esc_html($item->title) . '</a>';
		}
	}

	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '<ul class="wk-mm-sub-list" hidden>';
	}
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul>';
	}
	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= '</li>';
	}
}

function wk_mobile_fallback_menu_items() {
	if (!class_exists('WooCommerce')) return;
	$cats = get_terms(['taxonomy'=>'product_cat','hide_empty'=>true,'number'=>8,'parent'=>0]);
	if (is_wp_error($cats)) return;
	echo '<ul class="wk-mm-list">';
	echo '<li class="wk-mm-item"><a href="'.esc_url(home_url('/')).'">Home</a></li>';
	echo '<li class="wk-mm-item"><a href="'.esc_url(wc_get_page_permalink('shop')).'">All Products</a></li>';
	foreach ($cats as $cat) {
		echo '<li class="wk-mm-item"><a href="'.esc_url(get_term_link($cat)).'">'.esc_html($cat->name).'</a></li>';
	}
	echo '</ul>';
}

// ── Replace old mobile menu hook ────────────────────────────────
remove_action( 'wp_footer', 'wk_mobile_menu' );
add_action( 'wp_footer', 'wk_mobile_menu_v2', 89 );

// ── Pass mobile menu config to JS ───────────────────────────────
add_action( 'wp_enqueue_scripts', function() {
	wp_localize_script( 'wk-main', 'wk_mobile_menu_cfg', [
		'menu_id'     => 'wk-mobile-menu',
		'toggle_id'   => 'wk-mobile-menu-toggle',
		'overlay_id'  => 'wk-mm-overlay',
		'close_id'    => 'wk-mm-close',
	]);
}, 20 );
