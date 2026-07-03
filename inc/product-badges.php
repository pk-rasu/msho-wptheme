<?php
/**
 * WhiteKurti — Product Badges & Stock Counter System
 * NEW, SALE, HOT, TRENDING, LOW STOCK, OUT OF STOCK
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Admin settings page ───────────────────────────────────────────────────────
// Menu registration moved to inc/admin-hub.php

function wk_badges_get_settings() {
	return wp_parse_args( get_option('wk_product_badges', []), [
		'new_enabled'         => 1,
		'new_label'           => 'NEW',
		'new_days'            => 21,
		'new_bg'              => '#166534',
		'new_color'           => '#ffffff',
		'sale_enabled'        => 1,
		'sale_label'          => 'SALE',
		'sale_show_percent'   => 1,
		'sale_bg'             => '#B91C1C',
		'sale_color'          => '#ffffff',
		'hot_enabled'         => 1,
		'hot_label'           => 'HOT 🔥',
		'hot_bg'              => '#C2410C',
		'hot_color'           => '#ffffff',
		'trending_enabled'    => 1,
		'trending_label'      => 'TRENDING',
		'trending_bg'         => '#6B1E3E',
		'trending_color'      => '#ffffff',
		'stock_enabled'       => 1,
		'stock_threshold'     => 5,
		'stock_bg'            => '#92400E',
		'stock_color'         => '#ffffff',
		'oos_enabled'         => 1,
		'oos_label'           => 'SOLD OUT',
		'oos_bg'              => '#374151',
		'oos_color'           => '#ffffff',
		'position'            => 'top-left',
		'shape'               => 'pill',
	]);
}

add_action('admin_init', function(){
	if (!isset($_POST['wk_badges_nonce'])) return;
	if (!wp_verify_nonce($_POST['wk_badges_nonce'],'wk_badges_save')) return;
	if (!current_user_can('manage_options')) return;
	$s = [];
	$keys = ['new_enabled','new_label','new_days','new_bg','new_color','sale_enabled','sale_label','sale_show_percent','sale_bg','sale_color','hot_enabled','hot_label','hot_bg','hot_color','trending_enabled','trending_label','trending_bg','trending_color','stock_enabled','stock_threshold','stock_bg','stock_color','oos_enabled','oos_label','oos_bg','oos_color','position','shape'];
	foreach($keys as $k){
		$val = $_POST['badge_'.$k] ?? '';
		$s[$k] = in_array($k,['new_enabled','sale_enabled','hot_enabled','trending_enabled','stock_enabled','oos_enabled','sale_show_percent']) ? (!empty($val)?1:0) : sanitize_text_field($val);
	}
	update_option('wk_product_badges', $s);
});

function wk_badges_admin_page(){
	$s = wk_badges_get_settings();
	$f = function($key) use ($s) { return esc_attr($s[$key]??''); };
	$c = function($key) use ($s) { return !empty($s[$key]) ? 'checked' : ''; };
	?>
	<div class="wrap" style="max-width:900px;">
	<h1>🏷️ Product Badges Settings</h1>
	<form method="post">
	<?php wp_nonce_field('wk_badges_save','wk_badges_nonce'); ?>
	<style>
	.wk-b-card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;margin-bottom:18px;}
	.wk-b-card h2{margin:0 0 14px;font-size:14px;border-bottom:1px solid #eee;padding-bottom:10px;display:flex;align-items:center;gap:10px;}
	.wk-b-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;align-items:end;}
	.wk-b-field label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#555;margin-bottom:4px;}
	.wk-b-field input[type=text],.wk-b-field input[type=number],.wk-b-field select{width:100%;padding:7px 10px;border:1px solid #ddd;border-radius:4px;font-size:13px;}
	.wk-b-field input[type=color]{width:40px;height:32px;padding:1px;border:1px solid #ddd;border-radius:4px;cursor:pointer;}
	.wk-b-preview{display:inline-block;padding:4px 10px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-top:6px;}
	.wk-b-toggle{display:flex;align-items:center;gap:8px;margin-bottom:12px;}
	.wk-b-toggle input{width:18px;height:18px;accent-color:#6B1E3E;}
	.wk-b-toggle label{font-size:13px;font-weight:600;cursor:pointer;}
	.wk-b-color-inline{display:flex;align-items:center;gap:6px;}
	</style>

	<!-- Position & Shape -->
	<div class="wk-b-card">
		<h2>⚙️ Global Settings</h2>
		<div class="wk-b-row">
			<div class="wk-b-field">
				<label>Badge Position</label>
				<select name="badge_position">
					<option value="top-left"   <?php selected($s['position'],'top-left'); ?>>Top Left</option>
					<option value="top-right"  <?php selected($s['position'],'top-right'); ?>>Top Right</option>
					<option value="bottom-left" <?php selected($s['position'],'bottom-left'); ?>>Bottom Left</option>
				</select>
			</div>
			<div class="wk-b-field">
				<label>Badge Shape</label>
				<select name="badge_shape">
					<option value="pill"  <?php selected($s['shape'],'pill'); ?>>Pill (Rounded)</option>
					<option value="rect"  <?php selected($s['shape'],'rect'); ?>>Rectangle (Sharp)</option>
					<option value="tag"   <?php selected($s['shape'],'tag'); ?>>Tag (Angled)</option>
				</select>
			</div>
		</div>
	</div>

	<?php
	$badge_defs = [
		['new',      'NEW Badge',             "Shows for products added within X days", [
			['new_label','Label','text'],['new_days','Show for (days after launch)','number'],
			['new_bg','BG Color','color'],['new_color','Text Color','color'],
		]],
		['sale',     'SALE Badge',            "Shows on products with a sale price set in WooCommerce", [
			['sale_label','Label','text'],['sale_show_percent','Show % discount (e.g. -30%)','checkbox'],
			['sale_bg','BG Color','color'],['sale_color','Text Color','color'],
		]],
		['hot',      'HOT Badge',             "Manually added per-product (meta field wk_badge_hot=1)", [
			['hot_label','Label','text'],['hot_bg','BG Color','color'],['hot_color','Text Color','color'],
		]],
		['trending', 'TRENDING Badge',        "Manually added (meta field wk_badge_trending=1)", [
			['trending_label','Label','text'],['trending_bg','BG Color','color'],['trending_color','Text Color','color'],
		]],
		['stock',    'LOW STOCK Badge',       "Shows when stock is at or below threshold", [
			['stock_threshold','Show when stock ≤','number'],
			['stock_bg','BG Color','color'],['stock_color','Text Color','color'],
		]],
		['oos',      'SOLD OUT Badge',        "Shows on out-of-stock products", [
			['oos_label','Label','text'],['oos_bg','BG Color','color'],['oos_color','Text Color','color'],
		]],
	];
	foreach ($badge_defs as [$key, $title, $desc, $fields]) : ?>
	<div class="wk-b-card">
		<h2>
			<span style="background:<?php echo esc_attr($s[$key.'_bg']); ?>;color:<?php echo esc_attr($s[$key.'_color']); ?>;padding:3px 10px;font-size:11px;border-radius:3px;"><?php echo esc_html($s[$key.'_label']??strtoupper($key)); ?></span>
			<?php echo esc_html($title); ?>
		</h2>
		<p style="font-size:12px;color:#888;margin:0 0 12px;"><?php echo esc_html($desc); ?></p>
		<div class="wk-b-toggle">
			<input type="checkbox" id="badge_<?php echo $key; ?>_en" name="badge_<?php echo $key; ?>_enabled" value="1" <?php echo $c($key.'_enabled'); ?> />
			<label for="badge_<?php echo $key; ?>_en">Enable this badge</label>
		</div>
		<div class="wk-b-row">
		<?php foreach ($fields as [$fkey, $flabel, $ftype]) :
			if ($ftype==='checkbox') : ?>
			<div class="wk-b-field">
				<label><?php echo esc_html($flabel); ?></label>
				<label style="display:flex;align-items:center;gap:6px;margin-top:6px;cursor:pointer;">
					<input type="checkbox" name="badge_<?php echo $fkey; ?>" value="1" <?php echo $c($fkey); ?> style="accent-color:#6B1E3E;width:16px;height:16px;" />
					<span style="font-size:12px;">Yes</span>
				</label>
			</div>
			<?php elseif ($ftype==='color') : ?>
			<div class="wk-b-field">
				<label><?php echo esc_html($flabel); ?></label>
				<div class="wk-b-color-inline">
					<input type="color" value="<?php echo $f($fkey); ?>" oninput="document.querySelector('[name=badge_<?php echo $fkey; ?>]').value=this.value" />
					<input type="text" name="badge_<?php echo $fkey; ?>" value="<?php echo $f($fkey); ?>" style="width:90px;" />
				</div>
			</div>
			<?php else : ?>
			<div class="wk-b-field">
				<label><?php echo esc_html($flabel); ?></label>
				<input type="<?php echo $ftype; ?>" name="badge_<?php echo $fkey; ?>" value="<?php echo $f($fkey); ?>" />
			</div>
			<?php endif;
		endforeach; ?>
		</div>
	</div>
	<?php endforeach; ?>

	<input type="submit" class="button button-primary" value="Save Badge Settings" style="background:#6B1E3E;border-color:#4a1228;padding:10px 24px;font-size:14px;" />
	</form>
	</div>
	<?php
}

// ── Frontend badge render ─────────────────────────────────────────────────────
function wk_get_product_badges( $product ) {
	if ( ! $product ) return [];
	$s      = wk_badges_get_settings();
	$badges = [];
	$id     = $product->get_id();

	// SALE badge
	if ( $s['sale_enabled'] && $product->is_on_sale() ) {
		$label = $s['sale_label'];
		if ( $s['sale_show_percent'] ) {
			$reg  = (float) $product->get_regular_price();
			$sale = (float) $product->get_sale_price();
			if ( $reg > 0 && $sale > 0 ) {
				$pct   = round( ( $reg - $sale ) / $reg * 100 );
				$label = '-' . $pct . '%';
			}
		}
		$badges[] = [ 'label' => $label, 'bg' => $s['sale_bg'], 'color' => $s['sale_color'], 'priority' => 1, 'class' => 'wk-badge--sale' ];
	}

	// NEW badge
	if ( $s['new_enabled'] ) {
		$days_ago = (time() - strtotime($product->get_date_created()?:'')) / 86400;
		if ( $days_ago <= (int)$s['new_days'] ) {
			$badges[] = [ 'label' => $s['new_label'], 'bg' => $s['new_bg'], 'color' => $s['new_color'], 'priority' => 2, 'class' => 'wk-badge--new' ];
		}
	}

	// HOT badge (manual meta)
	if ( $s['hot_enabled'] && get_post_meta($id,'wk_badge_hot',true) ) {
		$badges[] = [ 'label' => $s['hot_label'], 'bg' => $s['hot_bg'], 'color' => $s['hot_color'], 'priority' => 3, 'class' => 'wk-badge--hot' ];
	}

	// TRENDING badge (manual meta)
	if ( $s['trending_enabled'] && get_post_meta($id,'wk_badge_trending',true) ) {
		$badges[] = [ 'label' => $s['trending_label'], 'bg' => $s['trending_bg'], 'color' => $s['trending_color'], 'priority' => 4, 'class' => 'wk-badge--trending' ];
	}

	// LOW STOCK badge
	if ( $s['stock_enabled'] && $product->managing_stock() ) {
		$stock = $product->get_stock_quantity();
		if ( $stock !== null && $stock <= (int)$s['stock_threshold'] && $stock > 0 ) {
			$badges[] = [ 'label' => 'Only ' . $stock . ' left!', 'bg' => $s['stock_bg'], 'color' => $s['stock_color'], 'priority' => 5, 'class' => 'wk-badge--lowstock' ];
		}
	}

	// OUT OF STOCK badge
	if ( $s['oos_enabled'] && ! $product->is_in_stock() ) {
		$badges[] = [ 'label' => $s['oos_label'], 'bg' => $s['oos_bg'], 'color' => $s['oos_color'], 'priority' => 0, 'class' => 'wk-badge--oos' ];
	}

	// Sort by priority (lower = shown first / on top)
	usort( $badges, function($a, $b) { return $a['priority'] - $b['priority']; } );
	return $badges;
}

function wk_render_product_badges( $product = null ) {
	if ( ! $product ) {
		global $product;
	}
	if ( ! $product ) return;
	$badges = wk_get_product_badges($product);
	if ( empty($badges) ) return;
	$s       = wk_badges_get_settings();
	$pos_cls = 'wk-badges--' . esc_attr($s['position']);
	$shp_cls = 'wk-badges--' . esc_attr($s['shape']);
	echo '<div class="wk-badges ' . $pos_cls . ' ' . $shp_cls . '">';
	foreach ( $badges as $b ) {
		echo '<span class="wk-badge ' . esc_attr($b['class']) . '" style="background:' . esc_attr($b['bg']) . ';color:' . esc_attr($b['color']) . ';">' . esc_html($b['label']) . '</span>';
	}
	echo '</div>';
}

// Hook badges onto product loops and single product
add_action( 'woocommerce_before_shop_loop_item_title', function() {
	global $product;
	wk_render_product_badges( $product );
}, 8 );

add_action( 'woocommerce_before_single_product_summary', function() {
	global $product;
	wk_render_product_badges( $product );
}, 4 );

// ── Stock Counter on product pages ───────────────────────────────────────────
function wk_stock_counter() {
	global $product;
	if ( ! $product ) return;
	$s = wk_badges_get_settings();
	if ( ! $s['stock_enabled'] ) return;
	if ( ! $product->managing_stock() ) return;
	$stock = $product->get_stock_quantity();
	if ( $stock === null ) return;

	if ( $stock <= 0 ) {
		echo '<div class="wk-stock-counter wk-stock-counter--oos"><span class="wk-stock-dot wk-stock-dot--oos"></span> Out of Stock</div>';
	} elseif ( $stock <= (int)$s['stock_threshold'] ) {
		echo '<div class="wk-stock-counter wk-stock-counter--low">';
		echo '<span class="wk-stock-dot wk-stock-dot--low"></span>';
		echo '<span>Only <strong>' . absint($stock) . '</strong> left — order soon!</span>';
		// Progress bar (visual urgency)
		$pct = min( 100, max(5, ($stock / max(1, $s['stock_threshold']*2)) * 100) );
		echo '<div class="wk-stock-bar"><div class="wk-stock-bar__fill" style="width:' . $pct . '%"></div></div>';
		echo '</div>';
	} else {
		echo '<div class="wk-stock-counter wk-stock-counter--ok"><span class="wk-stock-dot wk-stock-dot--ok"></span> In Stock</div>';
	}
}
add_action( 'woocommerce_single_product_summary', 'wk_stock_counter', 25 );

// ── Manual badge meta boxes ───────────────────────────────────────────────────
add_action( 'add_meta_boxes', function() {
	add_meta_box( 'wk_product_badges_meta', '🏷️ Product Badges', 'wk_product_badges_meta_cb', 'product', 'side', 'default' );
} );

function wk_product_badges_meta_cb( $post ) {
	wp_nonce_field('wk_prod_badge_save','wk_prod_badge_nonce');
	$hot      = get_post_meta($post->ID,'wk_badge_hot',true);
	$trending = get_post_meta($post->ID,'wk_badge_trending',true);
	echo '<p style="font-size:12px;color:#666;margin:0 0 10px;">Manually assign special badges to this product.</p>';
	echo '<label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;cursor:pointer;">';
	echo '<input type="checkbox" name="wk_badge_hot" value="1" '.checked($hot,'1',false).' style="accent-color:#C2410C;" />';
	echo '<strong>HOT 🔥</strong></label>';
	echo '<label style="display:flex;align-items:center;gap:8px;cursor:pointer;">';
	echo '<input type="checkbox" name="wk_badge_trending" value="1" '.checked($trending,'1',false).' style="accent-color:#6B1E3E;" />';
	echo '<strong>TRENDING</strong></label>';
}

add_action( 'save_post_product', function($pid) {
	if (!isset($_POST['wk_prod_badge_nonce'])||!wp_verify_nonce($_POST['wk_prod_badge_nonce'],'wk_prod_badge_save')) return;
	update_post_meta($pid,'wk_badge_hot',  !empty($_POST['wk_badge_hot'])  ? '1' : '0');
	update_post_meta($pid,'wk_badge_trending',!empty($_POST['wk_badge_trending'])?'1':'0');
});
