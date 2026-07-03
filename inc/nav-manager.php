<?php
/**
 * WhiteKurti — Header Navigation Manager
 * ─────────────────────────────────────────────────────
 * • Lets admin pick exactly which categories appear in
 *   the header nav, in what order, with custom labels
 * • Generates correct /product-category/slug/ URLs
 * • Falls back to WordPress Appearance > Menus if a
 *   menu is assigned there
 * • Syncs the WordPress "Main Menu" nav with real URLs
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WK_NAV_OPTION', 'wk_header_nav_items' );

// ═══════════════════════════════════════════════════════
// 1. BUILD THE NAV FROM SAVED SETTINGS (front-end output)
// ═══════════════════════════════════════════════════════

/**
 * Returns the nav items from saved settings.
 * Falls back to all published product categories if nothing saved.
 */
function wk_nav_get_items() {
	$saved = get_option( WK_NAV_OPTION, null );

	if ( is_array( $saved ) && ! empty( $saved ) ) {
		// Filter: only show enabled items that have a valid slug
		return array_filter( $saved, function( $item ) {
			return ! empty( $item['slug'] ) && ! empty( $item['enabled'] );
		} );
	}

	// Auto-generate from WooCommerce product categories
	return wk_nav_generate_defaults();
}

/**
 * Build default nav items from WooCommerce product categories.
 */
function wk_nav_generate_defaults() {
	if ( ! class_exists( 'WooCommerce' ) ) return [];

	$terms = get_terms( [
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'parent'     => 0,
		'exclude'    => [ get_option( 'default_product_cat' ) ],
		'orderby'    => 'name',
		'order'      => 'ASC',
		'number'     => 20,
	] );

	if ( is_wp_error( $terms ) || empty( $terms ) ) return [];

	$items = [];
	foreach ( $terms as $term ) {
		$items[] = [
			'slug'    => $term->slug,
			'label'   => $term->name,
			'url'     => get_term_link( $term ),
			'enabled' => 1,
			'term_id' => $term->term_id,
		];
	}
	return $items;
}

/**
 * Override wk_desktop_fallback_menu to use saved nav settings.
 * This replaces the hardcoded shop-URL version in template-functions.php.
 */
function wk_desktop_fallback_menu() {
	$items = wk_nav_get_items();

	if ( empty( $items ) ) {
		// Ultimate fallback: shop link only
		$shop = class_exists( 'WooCommerce' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/shop' );
		echo '<ul class="wk-desktop-nav__list"><li class="menu-item"><a href="' . esc_url( $shop ) . '">Shop</a></li></ul>';
		return;
	}

	$current_term = get_queried_object();
	echo '<ul class="wk-desktop-nav__list">';
	foreach ( $items as $item ) {
		$url   = ! empty( $item['url'] ) ? $item['url'] : get_term_link( $item['slug'], 'product_cat' );
		$label = $item['label'];

		// Highlight active category
		$is_active = ( $current_term instanceof WP_Term && $current_term->slug === $item['slug'] );
		$class = 'menu-item' . ( $is_active ? ' current-menu-item' : '' );

		echo '<li class="' . esc_attr( $class ) . '">';
		echo '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		echo '</li>';
	}
	echo '</ul>';
}

// ═══════════════════════════════════════════════════════
// 2. SYNC WORDPRESS "MAIN MENU" WITH REAL CATEGORY URLS
//    Runs once on admin_init to patch any existing menu
//    that has hardcoded shop URLs
// ═══════════════════════════════════════════════════════
add_action( 'admin_init', 'wk_nav_sync_wp_menu', 20 );
function wk_nav_sync_wp_menu() {
	if ( ! class_exists( 'WooCommerce' ) ) return;

	// Only run if flagged to sync (after save or first time)
	if ( ! get_option( 'wk_nav_needs_sync', true ) ) return;

	$menu = wp_get_nav_menu_object( 'Main Menu' );
	if ( ! $menu ) return;

	$menu_items = wp_get_nav_menu_items( $menu->term_id );
	if ( ! $menu_items ) return;

	$shop_url = trailingslashit( get_permalink( wc_get_page_id( 'shop' ) ) );
	$updated  = false;

	foreach ( $menu_items as $item ) {
		$current_url = $item->url;
		$title       = $item->title;

		// If URL is just the shop page (no category) try to match by title
		if ( trailingslashit( $current_url ) === $shop_url || empty( $current_url ) ) {
			// Try to find a matching WooCommerce category by menu item title
			$term = get_term_by( 'name', $title, 'product_cat' );
			if ( ! $term ) {
				// Try by slug (lowercase, hyphenated)
				$slug = sanitize_title( $title );
				$term = get_term_by( 'slug', $slug, 'product_cat' );
			}
			// Special case: "New Arrivals" → shop sorted by date
			if ( ! $term && stripos( $title, 'new arrival' ) !== false ) {
				$new_url = $shop_url . '?orderby=date';
				wp_update_post( [
					'ID'         => $item->ID,
					'post_title' => $item->title,
				] );
				update_post_meta( $item->ID, '_menu_item_url', $new_url );
				$updated = true;
				continue;
			}
			if ( $term && ! is_wp_error( $term ) ) {
				$cat_url = get_term_link( $term );
				if ( ! is_wp_error( $cat_url ) ) {
					update_post_meta( $item->ID, '_menu_item_url', $cat_url );
					$updated = true;
				}
			}
		}
	}

	if ( $updated ) {
		remove_theme_mod( 'nav_menu_locations' ); // force re-read
		wp_cache_flush();
	}

	// Mark as synced so we don't run every page load
	update_option( 'wk_nav_needs_sync', false );
}

// ═══════════════════════════════════════════════════════
// 3. ADMIN PANEL — Header Navigation Manager
// ═══════════════════════════════════════════════════════
// Menu registration handled by admin-hub.php (add_action admin_menu priority 50)
// wk_nav_admin_page() callback defined below

// Save handler
add_action( 'admin_init', 'wk_nav_handle_save' );
function wk_nav_handle_save() {
	if ( ! isset( $_POST['wk_nav_nonce'] ) ) return;
	if ( ! wp_verify_nonce( $_POST['wk_nav_nonce'], 'wk_nav_save' ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;

	$raw_slugs   = $_POST['nav_slug']    ?? [];
	$raw_labels  = $_POST['nav_label']   ?? [];
	$raw_enabled = $_POST['nav_enabled'] ?? [];

	$items = [];
	foreach ( $raw_slugs as $i => $slug ) {
		$slug = sanitize_title( $slug );
		if ( ! $slug ) continue;

		$label   = sanitize_text_field( $raw_labels[$i] ?? '' );
		$enabled = isset( $raw_enabled[$i] ) ? 1 : 0;

		// Build URL
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		$url  = '';
		if ( $slug === '__new_arrivals' ) {
			$url = class_exists('WooCommerce') ? get_permalink( wc_get_page_id('shop') ) . '?orderby=date' : home_url('/shop?orderby=date');
		} elseif ( $slug === '__shop' ) {
			$url = class_exists('WooCommerce') ? get_permalink( wc_get_page_id('shop') ) : home_url('/shop');
		} elseif ( $term && ! is_wp_error( $term ) ) {
			$url = get_term_link( $term );
		} else {
			$url = home_url( '/product-category/' . $slug . '/' );
		}

		if ( ! $label && $term ) $label = $term->name;
		if ( ! $label ) $label = ucwords( str_replace( '-', ' ', $slug ) );

		$items[] = [
			'slug'    => $slug,
			'label'   => $label,
			'url'     => is_wp_error($url) ? '' : $url,
			'enabled' => $enabled,
			'term_id' => $term ? $term->term_id : 0,
		];
	}

	update_option( WK_NAV_OPTION, $items );

	// Sync WP menu too
	update_option( 'wk_nav_needs_sync', true );
	wk_nav_sync_wp_menu_with_saved( $items );

	wp_redirect( admin_url( 'admin.php?page=wk-header-nav&saved=1' ) );
	exit;
}

/**
 * Sync saved nav items to the WordPress "Main Menu".
 */
function wk_nav_sync_wp_menu_with_saved( $items ) {
	if ( ! class_exists( 'WooCommerce' ) ) return;

	$menu = wp_get_nav_menu_object( 'Main Menu' );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( 'Main Menu' );
		$menu    = wp_get_nav_menu_object( 'Main Menu' );
	}

	// Delete all existing items
	$existing = wp_get_nav_menu_items( $menu->term_id );
	if ( $existing ) {
		foreach ( $existing as $ei ) {
			wp_delete_post( $ei->ID, true );
		}
	}

	// Re-add from saved settings
	$pos = 1;
	foreach ( $items as $item ) {
		if ( empty( $item['enabled'] ) ) continue;
		wp_update_nav_menu_item( $menu->term_id, 0, [
			'menu-item-title'    => $item['label'],
			'menu-item-url'      => $item['url'],
			'menu-item-status'   => 'publish',
			'menu-item-position' => $pos++,
			'menu-item-type'     => 'custom',
		] );
	}

	// Ensure assigned to primary location
	$locations = get_theme_mod( 'nav_menu_locations', [] );
	$locations['primary'] = $menu->term_id;
	set_theme_mod( 'nav_menu_locations', $locations );
	update_option( 'wk_nav_needs_sync', false );
}

// ═══════════════════════════════════════════════════════
// 4. ADMIN PAGE HTML
// ═══════════════════════════════════════════════════════
function wk_nav_admin_page() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<div class="wrap"><div class="notice notice-error"><p>WooCommerce must be active.</p></div></div>';
		return;
	}

	$saved  = wk_nav_get_items();
	$saved  = is_array( $saved ) ? array_values( $saved ) : [];
	$saved_result = $_GET['saved'] ?? false;

	// All available product categories (for "Add" dropdown)
	$all_cats = get_terms( [
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'parent'     => 0,
		'exclude'    => [ get_option( 'default_product_cat' ) ],
		'orderby'    => 'name',
		'order'      => 'ASC',
	] );
	$all_cats = is_wp_error( $all_cats ) ? [] : $all_cats;

	// Build lookup of saved slugs
	$saved_slugs = array_column( $saved, 'slug' );

	// If nothing saved yet, generate from categories
	if ( empty( $saved ) ) {
		$saved = array_values( wk_nav_generate_defaults() );
		// Also add "New Arrivals" as first item
		array_unshift( $saved, [
			'slug'    => '__new_arrivals',
			'label'   => 'New Arrivals',
			'url'     => get_permalink( wc_get_page_id('shop') ) . '?orderby=date',
			'enabled' => 1,
			'term_id' => 0,
		] );
	}
	?>
	<div class="wrap" style="max-width:900px;">

	<!-- Header -->
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
		<div>
			<h1 style="margin:0;font-size:21px;display:flex;align-items:center;gap:10px;">🧭 Header Navigation Manager</h1>
			<p style="margin:4px 0 0;color:#666;font-size:13px;">Control exactly which categories appear in the header menu, their order and labels. Changes are saved instantly and update the live site.</p>
		</div>
		<a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>" target="_blank" style="background:#f0f0f1;border:1px solid #ddd;padding:8px 14px;border-radius:4px;font-size:12px;text-decoration:none;color:#444;">
			⚙️ Advanced: WP Menu Editor
		</a>
	</div>

	<?php if ( $saved_result ) : ?>
	<div class="notice notice-success is-dismissible"><p>✅ Navigation saved! Menu is live on your site.</p></div>
	<?php endif; ?>

	<!-- Live Preview -->
	<div style="background:#1a1a1a;border-radius:10px;padding:14px 24px;margin-bottom:20px;">
		<p style="color:#999;font-size:11px;margin:0 0 10px;text-transform:uppercase;letter-spacing:.1em;">Live Preview</p>
		<div style="display:flex;gap:0;flex-wrap:wrap;" id="wk-nav-preview">
			<?php foreach ( $saved as $item ) :
				if ( empty( $item['enabled'] ) ) continue;
			?>
			<a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank"
			   style="color:#fff;text-decoration:none;padding:10px 18px;font-size:11px;letter-spacing:.14em;text-transform:uppercase;font-weight:500;border-bottom:2px solid transparent;transition:.2s;"
			   onmouseover="this.style.borderBottomColor='#8B1A4A'" onmouseout="this.style.borderBottomColor='transparent'">
				<?php echo esc_html( $item['label'] ); ?>
			</a>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Editor -->
	<form method="post" id="wk-nav-form">
		<?php wp_nonce_field( 'wk_nav_save', 'wk_nav_nonce' ); ?>

		<div style="background:#fff;border:1px solid #e0e0e0;border-radius:10px;overflow:hidden;">
			<div style="background:#f9fafb;border-bottom:1px solid #e0e0e0;padding:12px 20px;display:flex;align-items:center;justify-content:space-between;">
				<strong style="font-size:13px;">Navigation Items</strong>
				<span style="font-size:11px;color:#888;">≡ Drag rows to reorder &nbsp;|&nbsp; Toggle ● to show/hide</span>
			</div>

			<div id="wk-nav-rows" style="min-height:60px;">
			<?php foreach ( $saved as $idx => $item ) :
				$enabled  = ! empty( $item['enabled'] );
				$is_cat   = ! empty( $item['term_id'] );
				$slug     = $item['slug'];
				$label    = $item['label'];
				$url      = $item['url'] ?? '';
				$count    = $item['term_id'] ? get_term( $item['term_id'] )->count : '';
			?>
			<div class="wk-nav-row" style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid #f0f0f0;background:<?php echo $enabled ? '#fff' : '#fafafa'; ?>;transition:.15s;" data-idx="<?php echo $idx; ?>">

				<!-- Drag handle -->
				<span class="wk-nav-drag" style="cursor:grab;color:#bbb;font-size:18px;padding:0 4px;user-select:none;" title="Drag to reorder">≡</span>

				<!-- Enable toggle -->
				<label style="cursor:pointer;flex-shrink:0;" title="<?php echo $enabled ? 'Click to hide' : 'Click to show'; ?>">
					<input type="checkbox" name="nav_enabled[<?php echo $idx; ?>]" value="1"
					       <?php checked( $enabled, true ); ?>
					       style="display:none;" class="wk-nav-toggle" />
					<span class="wk-nav-pill" style="display:inline-block;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;background:<?php echo $enabled ? '#dcfce7' : '#f1f5f9'; ?>;color:<?php echo $enabled ? '#166534' : '#94a3b8'; ?>;min-width:56px;text-align:center;">
						<?php echo $enabled ? '● ON' : '○ OFF'; ?>
					</span>
				</label>

				<!-- Label input -->
				<input type="text" name="nav_label[<?php echo $idx; ?>]" value="<?php echo esc_attr( $label ); ?>"
				       style="flex:1;padding:8px 12px;border:1px solid #e5e7eb;border-radius:4px;font-size:13px;min-width:120px;"
				       placeholder="Label text" />

				<!-- Hidden slug -->
				<input type="hidden" name="nav_slug[<?php echo $idx; ?>]" value="<?php echo esc_attr( $slug ); ?>" />

				<!-- URL / target info -->
				<div style="flex:1.5;min-width:0;">
					<?php if ( $slug === '__new_arrivals' ) : ?>
					<span style="font-size:11px;color:#6b7280;background:#f3f4f6;padding:4px 10px;border-radius:4px;white-space:nowrap;">🆕 New Arrivals (sorted by date)</span>
					<?php elseif ( $slug === '__shop' ) : ?>
					<span style="font-size:11px;color:#6b7280;background:#f3f4f6;padding:4px 10px;border-radius:4px;">🛍️ All Products / Shop</span>
					<?php else : ?>
					<a href="<?php echo esc_url( $url ); ?>" target="_blank"
					   style="font-size:11px;color:#1d4ed8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;max-width:280px;"
					   title="<?php echo esc_attr( $url ); ?>">
						🔗 <?php echo esc_html( str_replace( home_url(), '', $url ) ); ?>
					</a>
					<?php if ( $count !== '' ) : ?>
					<span style="font-size:10px;color:#9ca3af;"><?php echo absint($count); ?> products</span>
					<?php endif; ?>
					<?php endif; ?>
				</div>

				<!-- Remove button -->
				<button type="button" class="wk-nav-remove"
				        style="background:none;border:none;cursor:pointer;color:#ef4444;font-size:18px;padding:4px;border-radius:4px;flex-shrink:0;"
				        title="Remove from nav">×</button>
			</div>
			<?php endforeach; ?>
			</div>

			<!-- Add Category -->
			<div style="padding:16px 20px;background:#f9fafb;border-top:1px solid #e0e0e0;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
				<select id="wk-add-cat-select" style="padding:9px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;min-width:200px;">
					<option value="">— Add a category —</option>
					<option value="__new_arrivals">🆕 New Arrivals</option>
					<option value="__shop">🛍️ All Products (Shop)</option>
					<optgroup label="Product Categories">
					<?php foreach ( $all_cats as $cat ) : ?>
					<option value="<?php echo esc_attr( $cat->slug ); ?>"
					        data-url="<?php echo esc_attr( get_term_link($cat) ); ?>"
					        data-label="<?php echo esc_attr( $cat->name ); ?>"
					        data-id="<?php echo $cat->term_id; ?>"
					        <?php disabled( in_array( $cat->slug, $saved_slugs ), true ); ?>>
						<?php echo esc_html( $cat->name ); ?> (<?php echo $cat->count; ?> products)
					</option>
					<?php endforeach; ?>
					</optgroup>
				</select>
				<button type="button" id="wk-add-nav-item"
				        style="background:#6B1E3E;color:#fff;border:none;padding:9px 18px;border-radius:4px;font-size:13px;cursor:pointer;font-weight:600;">
					+ Add to Navigation
				</button>
				<span style="font-size:11px;color:#9ca3af;">Can't find a category? <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=product_cat&post_type=product')); ?>" target="_blank" style="color:#1d4ed8;">Create it here →</a></span>
			</div>
		</div>

		<!-- Save Bar -->
		<div style="display:flex;align-items:center;justify-content:space-between;margin-top:16px;background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:14px 20px;">
			<div style="font-size:12.5px;color:#555;">
				<strong>ℹ️ How it works:</strong> Changes here update <em>both</em> the live site navigation AND WordPress's built-in Appearance › Menus. For mega-menus, use the <a href="<?php echo esc_url(admin_url('nav-menus.php')); ?>" target="_blank" style="color:#1d4ed8;">WP Menu Editor</a>.
			</div>
			<div style="display:flex;gap:10px;align-items:center;">
				<button type="button" id="wk-nav-reset" style="background:#fff;border:1px solid #ddd;padding:9px 16px;border-radius:4px;font-size:13px;cursor:pointer;color:#666;">
					↩ Reset to Categories
				</button>
				<input type="submit" value="💾 Save Navigation" class="button button-primary"
				       style="background:#6B1E3E;border-color:#4a1228;padding:10px 28px;font-size:14px;" />
			</div>
		</div>
	</form>

	<!-- Tips -->
	<div style="margin-top:20px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:16px 20px;font-size:12.5px;color:#1e40af;line-height:1.7;">
		<strong>💡 Tips:</strong><br>
		• Drag the ≡ handle to reorder items — top = leftmost in the header<br>
		• Toggle ●/○ to instantly show or hide a category without deleting it<br>
		• Edit the label to show a custom name (e.g. "Summer Kurtis" instead of the category name)<br>
		• The blue link shows exactly which URL the menu item will go to — click to verify
	</div>

	</div>

	<script>
	jQuery(function($){
		var $rows = $('#wk-nav-rows');
		var rowCount = <?php echo count($saved); ?>;

		// ── Drag-and-drop reorder ──
		if ( typeof $.fn.sortable === 'function' ) {
			$rows.sortable({
				handle: '.wk-nav-drag',
				axis: 'y',
				placeholder: 'wk-nav-placeholder',
				update: function() {
					// Re-index all fields after reorder
					$rows.find('.wk-nav-row').each(function(i) {
						$(this).attr('data-idx', i);
						$(this).find('input[name^="nav_slug"]').attr('name', 'nav_slug['+i+']');
						$(this).find('input[name^="nav_label"]').attr('name', 'nav_label['+i+']');
						$(this).find('input[name^="nav_enabled"]').attr('name', 'nav_enabled['+i+']');
					});
					updatePreview();
				}
			});
		}

		// ── Toggle ON/OFF ──
		$rows.on('change', '.wk-nav-toggle', function() {
			var $row = $(this).closest('.wk-nav-row');
			var on   = $(this).is(':checked');
			$(this).siblings('.wk-nav-pill')
				.text(on ? '● ON' : '○ OFF')
				.css({'background': on ? '#dcfce7' : '#f1f5f9', 'color': on ? '#166534' : '#94a3b8'});
			$row.css('background', on ? '#fff' : '#fafafa');
			updatePreview();
		});

		// Make clicking the pill toggle the checkbox
		$rows.on('click', '.wk-nav-pill', function() {
			var $cb = $(this).siblings('.wk-nav-toggle');
			$cb.prop('checked', !$cb.prop('checked')).trigger('change');
		});

		// ── Remove row ──
		$rows.on('click', '.wk-nav-remove', function() {
			$(this).closest('.wk-nav-row').remove();
			updatePreview();
		});

		// ── Add category ──
		$('#wk-add-nav-item').on('click', function() {
			var $sel   = $('#wk-add-cat-select');
			var slug   = $sel.val();
			if (!slug) { alert('Please select a category first.'); return; }

			var opt    = $sel.find(':selected');
			var label  = opt.data('label') || (slug === '__new_arrivals' ? 'New Arrivals' : slug === '__shop' ? 'All Products' : slug.replace(/-/g,' ').replace(/\b\w/g,c=>c.toUpperCase()));
			var url    = opt.data('url') || '';
			var tid    = opt.data('id') || 0;
			var i      = rowCount++;

			var urlDisplay = slug === '__new_arrivals'
				? '<span style="font-size:11px;color:#6b7280;background:#f3f4f6;padding:4px 10px;border-radius:4px;">🆕 New Arrivals (sorted by date)</span>'
				: slug === '__shop'
				? '<span style="font-size:11px;color:#6b7280;background:#f3f4f6;padding:4px 10px;border-radius:4px;">🛍️ All Products / Shop</span>'
				: '<a href="'+url+'" target="_blank" style="font-size:11px;color:#1d4ed8;">🔗 '+url+'</a>';

			var html = '<div class="wk-nav-row" style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid #f0f0f0;background:#fffbeb;" data-idx="'+i+'">'
				+'<span class="wk-nav-drag" style="cursor:grab;color:#bbb;font-size:18px;padding:0 4px;user-select:none;" title="Drag to reorder">≡</span>'
				+'<label style="cursor:pointer;flex-shrink:0;">'
				+'<input type="checkbox" name="nav_enabled['+i+']" value="1" checked style="display:none;" class="wk-nav-toggle" />'
				+'<span class="wk-nav-pill" style="display:inline-block;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;background:#dcfce7;color:#166534;min-width:56px;text-align:center;">● ON</span>'
				+'</label>'
				+'<input type="text" name="nav_label['+i+']" value="'+label+'" style="flex:1;padding:8px 12px;border:1px solid #e5e7eb;border-radius:4px;font-size:13px;" />'
				+'<input type="hidden" name="nav_slug['+i+']" value="'+slug+'" />'
				+'<div style="flex:1.5;">'+urlDisplay+'</div>'
				+'<button type="button" class="wk-nav-remove" style="background:none;border:none;cursor:pointer;color:#ef4444;font-size:18px;padding:4px;">×</button>'
				+'</div>';

			$rows.append(html);
			$sel.val('');
			updatePreview();

			// Disable the option so it can't be added twice
			opt.prop('disabled', true);
		});

		// ── Update live preview ──
		function updatePreview() {
			var $preview = $('#wk-nav-preview');
			$preview.empty();
			$rows.find('.wk-nav-row').each(function() {
				var enabled = $(this).find('.wk-nav-toggle').is(':checked');
				if (!enabled) return;
				var label = $(this).find('input[name^="nav_label"]').val() || '(unnamed)';
				var slug  = $(this).find('input[name^="nav_slug"]').val();
				// Find URL from the link
				var url = $(this).find('a').attr('href') || '#';
				$preview.append('<a href="'+url+'" target="_blank" style="color:#fff;text-decoration:none;padding:10px 18px;font-size:11px;letter-spacing:.14em;text-transform:uppercase;font-weight:500;border-bottom:2px solid transparent;" onmouseover="this.style.borderBottomColor=\'#8B1A4A\'" onmouseout="this.style.borderBottomColor=\'transparent\'">'+label+'</a>');
			});
		}

		// ── Input change updates preview ──
		$rows.on('input', 'input[name^="nav_label"]', function() { updatePreview(); });

		// ── Reset to defaults ──
		$('#wk-nav-reset').on('click', function() {
			if (confirm('Reset navigation to your product categories? This will overwrite current settings.')) {
				window.location.href = '<?php echo esc_url( admin_url('admin.php?page=wk-header-nav&reset=1&_wpnonce=' . wp_create_nonce('wk_nav_reset')) ); ?>';
			}
		});
	});
	</script>
	<?php
}

// Handle reset action
add_action( 'admin_init', function() {
	if ( ! isset( $_GET['reset'] ) || ! isset( $_GET['_wpnonce'] ) ) return;
	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wk_nav_reset' ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;

	delete_option( WK_NAV_OPTION );
	update_option( 'wk_nav_needs_sync', true );
	wp_redirect( admin_url( 'admin.php?page=wk-header-nav&saved=1' ) );
	exit;
} );

// ═══════════════════════════════════════════════════════
// 5. TRIGGER INITIAL SYNC on theme activation
// ═══════════════════════════════════════════════════════
add_action( 'after_switch_theme', function() {
	update_option( 'wk_nav_needs_sync', true );
} );
