<?php
/**
 * WhiteKurti — Mobile Filter Panel
 * Full-screen off-canvas filter drawer for shop/category pages on mobile.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Inject filter drawer HTML before footer ──────────────────────────────────
add_action( 'wp_footer', 'wk_mobile_filter_panel', 80 );
function wk_mobile_filter_panel() {
	if ( ! class_exists( 'WooCommerce' ) ) return;
	if ( ! function_exists('is_shop') ) return;
	if ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) return;
	?>
	<!-- Advanced filter content, injected into existing drawer body via JS -->
	<div id="wk-advanced-filter-content" style="display:none;">
		<?php wk_render_filter_groups(); ?>
	</div>
	<div id="wk-advanced-filter-foot" style="display:none;">
		<button class="wk-filter-drawer__reset" id="wk-filter-reset">Clear All</button>
		<button class="wk-filter-drawer__apply" id="wk-filter-apply">Apply Filters</button>
	</div>
	<?php
}

function wk_render_filter_groups() {
	global $wp_query;
	if ( ! $wp_query ) return;

	$current_min = isset( $_GET['min_price'] ) ? (float) $_GET['min_price'] : 0;
	$current_max = isset( $_GET['max_price'] ) ? (float) $_GET['max_price'] : 0;

	echo '<form id="wk-filter-form" method="GET" action="' . esc_url( get_pagenum_link(1) ) . '">';

	// Preserve current page context
	if ( is_product_category() ) {
		$term = get_queried_object();
		// category is in the URL, no hidden field needed
	}

	// ── Price Range ──
	$prices = wk_filter_get_price_range();
	if ( $prices ) {
		$min_p = $prices['min']; $max_p = $prices['max'];
		echo '<div class="wk-filter-group">';
		echo '<button type="button" class="wk-filter-group__toggle">Price Range <span>▾</span></button>';
		echo '<div class="wk-filter-group__body">';
		echo '<div class="wk-price-range">';
		echo '<div class="wk-price-range__inputs">';
		echo '<label>Min <input type="number" name="min_price" value="' . esc_attr( $current_min ?: $min_p ) . '" min="' . $min_p . '" max="' . $max_p . '" class="wk-price-input" /></label>';
		echo '<span>–</span>';
		echo '<label>Max <input type="number" name="max_price" value="' . esc_attr( $current_max ?: $max_p ) . '" min="' . $min_p . '" max="' . $max_p . '" class="wk-price-input" /></label>';
		echo '</div>';
		echo '<div class="wk-price-presets">';
		$presets = [ [0,500,'Under ₹500'], [500,1000,'₹500–₹1000'], [1000,2000,'₹1000–₹2000'], [2000,99999,'Above ₹2000'] ];
		foreach ( $presets as $p ) {
			$active = ( $current_min == $p[0] && ( $current_max == $p[1] || ( $p[1] == 99999 && $current_max == 0 ) ) ) ? 'active' : '';
			echo '<button type="button" class="wk-price-preset ' . $active . '" data-min="' . $p[0] . '" data-max="' . ($p[1]==99999?'':$p[1]) . '">' . $p[2] . '</button>';
		}
		echo '</div></div></div></div>';
	}

	// ── WooCommerce Attributes ──
	if ( class_exists( 'WooCommerce' ) ) {
		$taxonomies = wc_get_attribute_taxonomies();
		foreach ( $taxonomies as $tax ) {
			$tax_name = 'pa_' . $tax->attribute_name;
			$terms    = get_terms( [ 'taxonomy' => $tax_name, 'hide_empty' => true ] );
			if ( is_wp_error( $terms ) || empty( $terms ) ) continue;

			$active_terms = isset( $_GET[ 'filter_' . $tax->attribute_name ] )
				? explode( ',', sanitize_text_field( $_GET[ 'filter_' . $tax->attribute_name ] ) )
				: [];

			echo '<div class="wk-filter-group">';
			echo '<button type="button" class="wk-filter-group__toggle">' . esc_html( $tax->attribute_label ) . ' <span>▾</span></button>';
			echo '<div class="wk-filter-group__body">';

			// Color attributes get swatch treatment
			$is_color = in_array( strtolower($tax->attribute_name), [ 'color', 'colour', 'rang' ] );

			if ( $is_color ) {
				echo '<div class="wk-filter-colors">';
				foreach ( $terms as $term ) {
					$checked = in_array( $term->slug, $active_terms ) ? 'checked' : '';
					$hex     = wk_swatches_name_to_hex( $term->name );
					$style   = $hex ? "background:{$hex}" : 'background:#ddd';
					echo '<label class="wk-filter-color-opt" title="' . esc_attr($term->name) . '">';
					echo '<input type="checkbox" name="filter_' . esc_attr($tax->attribute_name) . '[]" value="' . esc_attr($term->slug) . '" ' . $checked . ' class="wk-filter-cb" data-filter="' . esc_attr($tax->attribute_name) . '" />';
					echo '<span class="wk-filter-swatch" style="' . esc_attr($style) . '"></span>';
					echo '</label>';
				}
				echo '</div>';
			} else {
				echo '<div class="wk-filter-checkboxes">';
				foreach ( $terms as $term ) {
					$checked = in_array( $term->slug, $active_terms ) ? 'checked' : '';
					echo '<label class="wk-filter-check-opt">';
					echo '<input type="checkbox" name="filter_' . esc_attr($tax->attribute_name) . '[]" value="' . esc_attr($term->slug) . '" ' . $checked . ' class="wk-filter-cb" />';
					echo '<span>' . esc_html($term->name) . '</span>';
					echo '</label>';
				}
				echo '</div>';
			}
			echo '</div></div>';
		}
	}

	// ── Sort Order ──
	$orderby = sanitize_text_field( $_GET['orderby'] ?? 'menu_order' );
	echo '<div class="wk-filter-group">';
	echo '<button type="button" class="wk-filter-group__toggle">Sort By <span>▾</span></button>';
	echo '<div class="wk-filter-group__body">';
	$sorts = [ 'menu_order' => 'Default', 'popularity' => 'Most Popular', 'rating' => 'Highest Rated', 'date' => 'Newest First', 'price' => 'Price: Low to High', 'price-desc' => 'Price: High to Low' ];
	foreach ( $sorts as $val => $label ) {
		$checked = $orderby === $val ? 'checked' : '';
		echo '<label class="wk-filter-radio-opt"><input type="radio" name="orderby" value="' . esc_attr($val) . '" ' . $checked . '> <span>' . esc_html($label) . '</span></label>';
	}
	echo '</div></div>';

	echo '</form>';
}

function wk_filter_get_price_range() {
	global $wpdb;
	$row = $wpdb->get_row( "
		SELECT MIN(meta_value+0) as min_price, MAX(meta_value+0) as max_price
		FROM {$wpdb->postmeta}
		WHERE meta_key = '_price' AND meta_value != '' AND meta_value > 0
	" );
	if ( ! $row || ! $row->max_price ) return null;
	return [ 'min' => (int)$row->min_price, 'max' => (int)$row->max_price ];
}

// ── Activate filter button on archive pages ──────────────────────────────────
add_action( 'wp_footer', 'wk_filter_trigger_js', 82 );
function wk_filter_trigger_js() {
	if ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) return;
	?>
	<script>
	(function(){
		// Inject advanced content into the existing theme filter drawer
		document.addEventListener('DOMContentLoaded', function() {
			var advContent = document.getElementById('wk-advanced-filter-content');
			var advFoot    = document.getElementById('wk-advanced-filter-foot');
			var drawerBody = document.querySelector('#wk-filter-drawer .wk-filter-drawer__body');
			var drawerFoot = document.querySelector('#wk-filter-drawer .wk-filter-drawer__foot');

			// Inject our enhanced content into the existing drawer
			if (drawerBody && advContent) {
				drawerBody.innerHTML = advContent.innerHTML;
				advContent.remove();
			}
			if (drawerFoot && advFoot) {
				drawerFoot.innerHTML = advFoot.innerHTML;
				advFoot.remove();
			} else if (advFoot && drawerBody) {
				// Create footer if missing
				var foot = document.createElement('div');
				foot.className = 'wk-filter-drawer__foot';
				foot.innerHTML = advFoot.innerHTML;
				drawerBody.parentNode.appendChild(foot);
				advFoot.remove();
			}

			var drawer  = document.getElementById('wk-filter-drawer');
			var overlay = document.getElementById('wk-filter-overlay');
			var closeBtn= document.getElementById('wk-filter-close');
			var applyBtn= document.getElementById('wk-filter-apply');
			var resetBtn= document.getElementById('wk-filter-reset');

			if (!drawer) return;

			function openDrawer() {
				drawer.hidden = false;
				document.body.style.overflow = 'hidden';
				setTimeout(function(){ drawer.classList.add('is-open'); }, 10);
			}
			function closeDrawer() {
				drawer.classList.remove('is-open');
				document.body.style.overflow = '';
				setTimeout(function(){ drawer.hidden = true; }, 300);
			}

			closeBtn && closeBtn.addEventListener('click', closeDrawer);
			overlay  && overlay.addEventListener('click', closeDrawer);

			applyBtn && applyBtn.addEventListener('click', function() {
				var form = document.getElementById('wk-filter-form');
				if (!form) return;
				// Consolidate checkboxes into comma-separated values
				var cbGroups = {};
				form.querySelectorAll('input[type="checkbox"].wk-filter-cb:checked').forEach(function(cb) {
					var name = cb.name.replace('[]','');
					if (!cbGroups[name]) cbGroups[name] = [];
					cbGroups[name].push(cb.value);
				});
				var url = new URL(form.action);
				// Clear existing filter params
				['min_price','max_price','orderby'].forEach(function(p){ url.searchParams.delete(p); });
				// Re-add from form
				var minP = form.querySelector('[name="min_price"]');
				var maxP = form.querySelector('[name="max_price"]');
				if (minP && minP.value) url.searchParams.set('min_price', minP.value);
				if (maxP && maxP.value) url.searchParams.set('max_price', maxP.value);
				var orderby = form.querySelector('[name="orderby"]:checked');
				if (orderby && orderby.value !== 'menu_order') url.searchParams.set('orderby', orderby.value);
				Object.keys(cbGroups).forEach(function(key) {
					url.searchParams.set('filter_'+key.replace('filter_',''), cbGroups[key].join(','));
				});
				window.location.href = url.toString();
			});

			resetBtn && resetBtn.addEventListener('click', function() {
				var url = new URL(window.location.href);
				['min_price','max_price','orderby'].forEach(function(p){ url.searchParams.delete(p); });
				url.searchParams.forEach(function(v, k) {
					if (k.startsWith('filter_')) url.searchParams.delete(k);
				});
				window.location.href = url.toString();
			});

			// Price presets
			document.querySelectorAll('.wk-price-preset').forEach(function(btn) {
				btn.addEventListener('click', function() {
					var min = this.dataset.min, max = this.dataset.max;
					var minI = document.querySelector('[name="min_price"]');
					var maxI = document.querySelector('[name="max_price"]');
					if (minI) minI.value = min;
					if (maxI) maxI.value = max;
					document.querySelectorAll('.wk-price-preset').forEach(function(b){ b.classList.remove('active'); });
					this.classList.add('active');
				});
			});

			// Accordion
			document.querySelectorAll('.wk-filter-group__toggle').forEach(function(btn) {
				btn.addEventListener('click', function() {
					var body = this.nextElementSibling;
					var open = body.style.display !== 'none';
					body.style.display = open ? 'none' : '';
					this.querySelector('span').textContent = open ? '▾' : '▴';
				});
			});
		});
	})();
	</script>
	<?php
}

// ── CSS ──────────────────────────────────────────────────────────────────────
// filter CSS in main.css
function wk_filter_css() {
	if ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) return;
	?>
	<style id="wk-filter-css">
	.wk-filter-drawer { position:fixed; inset:0; z-index:10000; pointer-events:none; }
	.wk-filter-drawer.is-open { pointer-events:all; }
	.wk-filter-drawer__overlay { position:absolute; inset:0; background:rgba(0,0,0,.5); opacity:0; transition:opacity .3s; }
	.wk-filter-drawer.is-open .wk-filter-drawer__overlay { opacity:1; }
	.wk-filter-drawer__panel {
		position:absolute; right:0; top:0; bottom:0; width:min(380px, 95vw);
		background:#fff; display:flex; flex-direction:column;
		transform:translateX(100%); transition:transform .3s cubic-bezier(.4,0,.2,1);
		box-shadow:-4px 0 24px rgba(0,0,0,.12);
	}
	.wk-filter-drawer.is-open .wk-filter-drawer__panel { transform:translateX(0); }
	.wk-filter-drawer__head { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #e5e7eb; font-size:15px; font-weight:600; flex-shrink:0; }
	.wk-filter-drawer__close { background:none; border:none; cursor:pointer; padding:4px; color:#555; border-radius:4px; }
	.wk-filter-drawer__body { flex:1; overflow-y:auto; padding:8px 0; }
	.wk-filter-drawer__foot { display:flex; gap:10px; padding:14px 16px; border-top:1px solid #e5e7eb; flex-shrink:0; }
	.wk-filter-drawer__reset { flex:1; background:#f3f4f6; border:1px solid #ddd; border-radius:6px; padding:10px; cursor:pointer; font-size:13px; color:#555; }
	.wk-filter-drawer__apply { flex:2; background:var(--accent,#6B1E3E); color:#fff; border:none; border-radius:6px; padding:10px; cursor:pointer; font-size:13px; font-weight:600; }
	.wk-filter-group { border-bottom:1px solid #f0f0f0; }
	.wk-filter-group__toggle { width:100%; background:none; border:none; padding:14px 20px; cursor:pointer; display:flex; justify-content:space-between; align-items:center; font-size:13px; font-weight:600; text-align:left; color:#111; }
	.wk-filter-group__body { padding:0 20px 14px; }
	.wk-filter-checkboxes, .wk-filter-colors { display:flex; flex-wrap:wrap; gap:8px; }
	.wk-filter-check-opt { display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer; min-width:120px; }
	.wk-filter-check-opt input { accent-color:var(--accent,#6B1E3E); width:15px; height:15px; }
	.wk-filter-color-opt { cursor:pointer; position:relative; }
	.wk-filter-color-opt input { position:absolute; opacity:0; width:0; height:0; }
	.wk-filter-swatch { display:block; width:28px; height:28px; border-radius:50%; border:2px solid transparent; box-shadow:0 0 0 1px rgba(0,0,0,.1); transition:.15s; }
	.wk-filter-color-opt input:checked + .wk-filter-swatch { border-color:var(--accent,#6B1E3E); box-shadow:0 0 0 2px var(--accent,#6B1E3E); }
	.wk-filter-radio-opt { display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer; margin-bottom:8px; }
	.wk-filter-radio-opt input { accent-color:var(--accent,#6B1E3E); }
	.wk-price-range__inputs { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
	.wk-price-input { width:90px; padding:8px; border:1px solid #ddd; border-radius:4px; font-size:13px; }
	.wk-price-presets { display:flex; flex-wrap:wrap; gap:6px; }
	.wk-price-preset { background:#f3f4f6; border:1px solid #ddd; border-radius:4px; padding:5px 10px; font-size:12px; cursor:pointer; }
	.wk-price-preset.active { background:var(--accent,#6B1E3E); color:#fff; border-color:var(--accent,#6B1E3E); }
	/* Floating FAB button on mobile */
	.wk-fab-filter { display:none; }
	@media (max-width:767px) {
		.wk-fab-filter { display:flex; align-items:center; gap:6px; position:fixed; bottom:76px; right:16px; z-index:999; background:var(--accent,#6B1E3E); color:#fff; border:none; border-radius:24px; padding:10px 18px; font-size:13px; font-weight:600; box-shadow:0 4px 16px rgba(0,0,0,.25); cursor:pointer; }
	}
	</style>
	<?php
}
