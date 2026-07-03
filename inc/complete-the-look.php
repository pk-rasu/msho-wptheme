<?php
/**
 * WhiteKurti — Complete the Look + Recently Viewed Products
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ═══════════════════════════════════════════════════════════════
// 1. COMPLETE THE LOOK
// ═══════════════════════════════════════════════════════════════

// Customizer settings
add_action('customize_register', function($wp_customize) {
	$wp_customize->add_section('wk_complete_look', [
		'title'    => __('👗 Complete the Look', 'whitekurti'),
		'panel'    => 'wk_panel',
		'priority' => 47,
	]);
	$fields = [
		['wk_ctl_enabled',        'Enable "Complete the Look" section', 'checkbox', true, ''],
		['wk_ctl_title',          'Section Title',                      'text',     'Complete the Look', ''],
		['wk_ctl_subtitle',       'Section Subtitle',                   'text',     'Style it with these picks', ''],
		['wk_ctl_source',         'Product Source',                     'select',   'upsells', ''],
		['wk_ctl_count',          'Number of Products to Show',         'number',   4, '2–8 products'],
		['wk_ctl_show_quickadd',  'Show Quick Add Button',              'checkbox', true, ''],
	];
	foreach ($fields as [$id,$label,$type,$default,$desc]) {
		$san = $type==='checkbox' ? 'rest_sanitize_boolean' : 'sanitize_text_field';
		$wp_customize->add_setting($id, ['default'=>$default,'sanitize_callback'=>$san,'transport'=>'refresh']);
		$ctrl = ['label'=>$label,'description'=>$desc,'section'=>'wk_complete_look','type'=>$type];
		if ($type==='select') $ctrl['choices'] = ['upsells'=>'WooCommerce Upsells','related'=>'Related Products','manual'=>'Manually set (per product)','category'=>'Same Category'];
		$wp_customize->add_control($id, $ctrl);
	}
});

// Meta box for manual CTL products per product
add_action('add_meta_boxes', function() {
	add_meta_box('wk_ctl_products', '👗 Complete the Look — Products', 'wk_ctl_meta_cb', 'product', 'normal', 'default');
});
function wk_ctl_meta_cb($post) {
	wp_nonce_field('wk_ctl_save','wk_ctl_nonce');
	$ids = get_post_meta($post->ID, '_wk_ctl_products', true) ?: '';
	echo '<p style="font-size:12px;color:#666;margin:0 0 8px;">Enter Product IDs separated by commas for the "Complete the Look" section (used when source is set to Manual).</p>';
	echo '<input type="text" name="wk_ctl_products" value="'.esc_attr($ids).'" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px;" placeholder="e.g. 123, 456, 789" />';
	echo '<p style="font-size:11px;color:#888;margin:4px 0 0;">You can find Product IDs in <a href="'.admin_url('edit.php?post_type=product').'" target="_blank">Products list</a> (hover over a product to see its ID).</p>';
}
add_action('save_post_product', function($pid) {
	if (!isset($_POST['wk_ctl_nonce'])||!wp_verify_nonce($_POST['wk_ctl_nonce'],'wk_ctl_save')) return;
	update_post_meta($pid, '_wk_ctl_products', sanitize_text_field($_POST['wk_ctl_products']??''));
});

// Get CTL products
function wk_get_ctl_products($product) {
	$source = get_theme_mod('wk_ctl_source', 'upsells');
	$count  = max(2, min(8, absint(get_theme_mod('wk_ctl_count', 4))));
	$products = [];

	if ($source === 'upsells') {
		$upsell_ids = $product->get_upsell_ids();
		foreach (array_slice($upsell_ids, 0, $count) as $id) {
			$p = wc_get_product($id);
			if ($p && $p->is_visible()) $products[] = $p;
		}
		// Fallback to related if no upsells
		if (empty($products)) $source = 'related';
	}

	if ($source === 'related') {
		$related_ids = wc_get_related_products($product->get_id(), $count);
		foreach ($related_ids as $id) {
			$p = wc_get_product($id);
			if ($p && $p->is_visible()) $products[] = $p;
		}
	}

	if ($source === 'manual') {
		$manual_raw = get_post_meta($product->get_id(), '_wk_ctl_products', true);
		if ($manual_raw) {
			$ids = array_filter(array_map('absint', explode(',', $manual_raw)));
			foreach (array_slice($ids, 0, $count) as $id) {
				$p = wc_get_product($id);
				if ($p && $p->is_visible()) $products[] = $p;
			}
		}
		if (empty($products)) {
			$related_ids = wc_get_related_products($product->get_id(), $count);
			foreach ($related_ids as $id) {
				$p = wc_get_product($id);
				if ($p && $p->is_visible()) $products[] = $p;
			}
		}
	}

	if ($source === 'category') {
		$term_ids = wp_get_post_terms($product->get_id(), 'product_cat', ['fields'=>'ids']);
		if ($term_ids && !is_wp_error($term_ids)) {
			$query = new WP_Query([
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $count + 1,
				'post__not_in'   => [$product->get_id()],
				'tax_query'      => [['taxonomy'=>'product_cat','field'=>'term_id','terms'=>$term_ids]],
				'orderby'        => 'rand',
			]);
			foreach ($query->posts as $p_post) {
				$p = wc_get_product($p_post->ID);
				if ($p && $p->is_visible()) $products[] = $p;
			}
		}
	}

	return array_slice($products, 0, $count);
}

// Render CTL section
// ═══════════════════════════════════════════════════════════════════════
// ROBUST PRODUCT RECOMMENDATIONS ENGINE
// Waterfall: same category → upsells/cross-sells → same tag → newest
// ALWAYS returns products — never shows empty section
// ═══════════════════════════════════════════════════════════════════════

function wk_get_recommended_products($product_id, $limit = 8) {
	if (!$product_id || !class_exists('WooCommerce')) return [];
	$product  = wc_get_product($product_id);
	if (!$product) return [];

	$found    = [];
	$found_ids= [$product_id]; // exclude current product

	// ── Layer 1: Same category products ──────────────────────────────
	$cat_terms = wp_get_post_terms($product_id, 'product_cat', ['fields'=>'ids']);
	if (!is_wp_error($cat_terms) && !empty($cat_terms)) {
		$cat_query = new WP_Query([
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit * 2,
			'post__not_in'   => $found_ids,
			'orderby'        => 'rand',
			'tax_query'      => [[
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $cat_terms,
				'operator' => 'IN',
			]],
			'meta_query'     => [[
				'key'   => '_stock_status',
				'value' => 'instock',
			]],
		]);
		foreach ($cat_query->posts as $post) {
			$p = wc_get_product($post->ID);
			if ($p && $p->is_visible()) {
				$found[] = $p;
				$found_ids[] = $post->ID;
				if (count($found) >= $limit) break;
			}
		}
		wp_reset_postdata();
	}

	// ── Layer 2: WooCommerce upsells ──────────────────────────────────
	if (count($found) < $limit) {
		foreach ($product->get_upsell_ids() as $uid) {
			if (in_array($uid, $found_ids)) continue;
			$p = wc_get_product($uid);
			if ($p && $p->is_visible()) {
				$found[] = $p;
				$found_ids[] = $uid;
				if (count($found) >= $limit) break;
			}
		}
	}

	// ── Layer 3: WooCommerce cross-sells ─────────────────────────────
	if (count($found) < $limit) {
		foreach ($product->get_cross_sell_ids() as $cid) {
			if (in_array($cid, $found_ids)) continue;
			$p = wc_get_product($cid);
			if ($p && $p->is_visible()) {
				$found[] = $p;
				$found_ids[] = $cid;
				if (count($found) >= $limit) break;
			}
		}
	}

	// ── Layer 4: Same tag products ────────────────────────────────────
	if (count($found) < $limit) {
		$tag_terms = wp_get_post_terms($product_id, 'product_tag', ['fields'=>'ids']);
		if (!is_wp_error($tag_terms) && !empty($tag_terms)) {
			$tag_query = new WP_Query([
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'post__not_in'   => $found_ids,
				'orderby'        => 'rand',
				'tax_query'      => [[
					'taxonomy' => 'product_tag',
					'field'    => 'term_id',
					'terms'    => $tag_terms,
				]],
			]);
			foreach ($tag_query->posts as $post) {
				$p = wc_get_product($post->ID);
				if ($p && $p->is_visible()) {
					$found[] = $p;
					$found_ids[] = $post->ID;
					if (count($found) >= $limit) break;
				}
			}
			wp_reset_postdata();
		}
	}

	// ── Layer 5: Newest products (ultimate fallback) ──────────────────
	if (count($found) < $limit) {
		$fill_needed = $limit - count($found);
		$new_query   = new WP_Query([
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $fill_needed * 2,
			'post__not_in'   => $found_ids,
			'orderby'        => 'date',
			'order'          => 'DESC',
		]);
		foreach ($new_query->posts as $post) {
			$p = wc_get_product($post->ID);
			if ($p && $p->is_visible()) {
				$found[] = $p;
				$found_ids[] = $post->ID;
				if (count($found) >= $limit) break;
			}
		}
		wp_reset_postdata();
	}

	return array_slice($found, 0, $limit);
}

// ── Render recommended products on PDP ────────────────────────────────────────
function wk_render_pdp_recommended($product_id, $product_obj = null) {
	if (!class_exists('WooCommerce')) return;
	if (!$product_id) {
		global $product;
		if (!$product) return;
		$product_id  = $product->get_id();
		$product_obj = $product;
	}

	$limit    = absint(get_theme_mod('wk_related_count', 8));
	$title    = get_theme_mod('wk_pdp_related_title', 'You May Also Like');
	$subtitle = get_theme_mod('wk_related_subtitle', 'Handpicked just for you');
	$products = wk_get_recommended_products($product_id, $limit);
	if (empty($products)) return;

	// Split products into two rows for desktop
	$row1 = array_slice($products, 0, 4);
	$row2 = array_slice($products, 4);
	?>
	<section class="wk-pdp-recommended" aria-label="<?php echo esc_attr($title); ?>">
		<div class="wk-container">
			<div class="wk-pdp-recommended__header">
				<div>
					<h2 class="wk-pdp-recommended__title"><?php echo esc_html($title); ?></h2>
					<?php if ($subtitle): ?>
					<p class="wk-pdp-recommended__sub"><?php echo esc_html($subtitle); ?></p>
					<?php endif; ?>
				</div>
				<?php
				$shop_url = wc_get_page_permalink('shop');
				$cat_terms = wp_get_post_terms($product_id,'product_cat',['fields'=>'all']);
				if (!is_wp_error($cat_terms) && !empty($cat_terms)) {
					$shop_url = get_term_link($cat_terms[0]);
				}
				?>
				<a href="<?php echo esc_url($shop_url); ?>" class="wk-pdp-recommended__view-all">
					View All
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
				</a>
			</div>

			<!-- Desktop: 4-col grid | Mobile: horizontal scroll -->
			<div class="wk-pdp-recommended__grid" id="wk-pdp-rec-grid">
				<?php foreach ($products as $rec_product) :
					$rec_id    = $rec_product->get_id();
					$rec_img_id= $rec_product->get_image_id();
					$rec_img   = $rec_img_id ? wp_get_attachment_image_url($rec_img_id,'woocommerce_single') : wc_placeholder_img_src();
					$rec_price = $rec_product->get_price_html();
					$rec_link  = get_permalink($rec_id);
					$rec_name  = $rec_product->get_name();
					$rec_cats  = wp_get_post_terms($rec_id,'product_cat',['fields'=>'names']);
					$rec_cat   = (!is_wp_error($rec_cats) && !empty($rec_cats)) ? $rec_cats[0] : '';
					$rec_sale  = $rec_product->is_on_sale();
					$rec_reg   = (float)$rec_product->get_regular_price();
					$rec_prc   = (float)$rec_product->get_price();
					$rec_pct   = ($rec_sale && $rec_reg > 0) ? round((1 - $rec_prc/$rec_reg)*100) : 0;
					$rec_stock = $rec_product->is_in_stock();
					$rec_type  = $rec_product->get_type();
				?>
				<article class="wk-rec-card" data-product-id="<?php echo absint($rec_id); ?>">
					<a href="<?php echo esc_url($rec_link); ?>" class="wk-rec-card__img-link" tabindex="-1">
						<div class="wk-rec-card__img-wrap">
							<img src="<?php echo esc_url($rec_img); ?>"
							     alt="<?php echo esc_attr($rec_name); ?>"
							     class="wk-rec-card__img"
							     loading="lazy" />
							<!-- Product badges -->
							<div class="wk-rec-card__badges">
								<?php if ($rec_sale && $rec_pct > 0) : ?>
								<span class="wk-rec-badge wk-rec-badge--sale">-<?php echo $rec_pct; ?>%</span>
								<?php endif; ?>
								<?php
								$days_old = (time() - strtotime($rec_product->get_date_created()?:'')) / 86400;
								if ($days_old <= 21 && !$rec_sale) :
								?>
								<span class="wk-rec-badge wk-rec-badge--new">NEW</span>
								<?php endif; ?>
								<?php if (!$rec_stock) : ?>
								<span class="wk-rec-badge wk-rec-badge--oos">Sold Out</span>
								<?php endif; ?>
							</div>
							<!-- Quick view trigger -->
							<?php if (get_theme_mod('wk_qv_enabled', true)) : ?>
							<button class="wk-rec-card__qv wk-qv-btn"
							        data-product-id="<?php echo absint($rec_id); ?>"
							        aria-label="Quick view <?php echo esc_attr($rec_name); ?>">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
								Quick View
							</button>
							<?php endif; ?>
						</div>
					</a>
					<div class="wk-rec-card__body">
						<?php if ($rec_cat) : ?>
						<span class="wk-rec-card__cat"><?php echo esc_html($rec_cat); ?></span>
						<?php endif; ?>
						<a href="<?php echo esc_url($rec_link); ?>" class="wk-rec-card__name"><?php echo esc_html($rec_name); ?></a>
						<div class="wk-rec-card__price-row">
							<span class="wk-rec-card__price"><?php echo $rec_price; ?></span>
						</div>
						<?php if ($rec_stock) : ?>
							<?php if ($rec_type !== 'variable') : ?>
							<button class="wk-rec-card__atc"
							        data-product-id="<?php echo absint($rec_id); ?>"
							        aria-label="Add <?php echo esc_attr($rec_name); ?> to cart">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
								Add to Cart
							</button>
							<?php else : ?>
							<a href="<?php echo esc_url($rec_link); ?>" class="wk-rec-card__atc wk-rec-card__atc--options">
								Select Options
							</a>
							<?php endif; ?>
						<?php else : ?>
						<span class="wk-rec-card__oos">Out of Stock</span>
						<?php endif; ?>
					</div>
					<!-- Wishlist button -->
					<?php if (get_theme_mod('wk_wl_enabled', true)) : ?>
					<button type="button" class="wk-rec-card__wl wk-wl-btn"
					        data-product-id="<?php echo absint($rec_id); ?>"
					        aria-label="Save to wishlist">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
					</button>
					<?php endif; ?>
				</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}


function wk_render_complete_the_look() {
	if (!get_theme_mod('wk_ctl_enabled', true)) return;
	global $product;
	if (!$product) return;
	$items = wk_get_ctl_products($product);
	if (empty($items)) return;

	$title      = get_theme_mod('wk_ctl_title', 'Complete the Look');
	$subtitle   = get_theme_mod('wk_ctl_subtitle', 'Style it with these picks');
	$show_quick = get_theme_mod('wk_ctl_show_quickadd', true);
	?>
	<section class="wk-ctl" aria-label="<?php echo esc_attr($title); ?>">
		<div class="wk-section-header">
			<h2 class="wk-section-title"><?php echo esc_html($title); ?></h2>
			<?php if ($subtitle) : ?><p class="wk-section-sub"><?php echo esc_html($subtitle); ?></p><?php endif; ?>
		</div>
		<div class="wk-ctl-grid">
			<?php foreach ($items as $item) :
				$img_id   = $item->get_image_id();
				$img_url  = $img_id ? wp_get_attachment_image_url($img_id, 'woocommerce_single') : wc_placeholder_img_src();
				$img_full = $img_id ? wp_get_attachment_image_url($img_id, 'full') : $img_url;
				$price    = $item->get_price_html();
				$link     = get_permalink($item->get_id());
				$name     = $item->get_name();
				$in_stock = $item->is_in_stock();
			?>
			<article class="wk-ctl-item" data-product-id="<?php echo $item->get_id(); ?>">
				<a href="<?php echo esc_url($link); ?>" class="wk-ctl-item__img-link">
					<img src="<?php echo esc_url($img_url); ?>"
					     alt="<?php echo esc_attr($name); ?>"
					     class="wk-ctl-item__img"
					     loading="lazy"
					     data-full="<?php echo esc_url($img_full); ?>" />
					<?php
					global $wk_orig_product;
					$wk_orig_product = $GLOBALS['product'];
					$GLOBALS['product'] = $item;
					wk_render_product_badges($item);
					$GLOBALS['product'] = $wk_orig_product;
					?>
				</a>
				<div class="wk-ctl-item__info">
					<a href="<?php echo esc_url($link); ?>" class="wk-ctl-item__name"><?php echo esc_html($name); ?></a>
					<div class="wk-ctl-item__price"><?php echo $price; ?></div>
					<?php if ($show_quick && $in_stock) : ?>
					<button class="wk-ctl-quickadd wk-btn wk-btn--sm"
					        data-product-id="<?php echo $item->get_id(); ?>"
					        data-product-type="<?php echo esc_attr($item->get_type()); ?>">
						<?php echo $item->is_type('variable') ? 'Select Options' : '+ Add to Bag'; ?>
					</button>
					<?php elseif (!$in_stock) : ?>
					<span class="wk-ctl-soldout">Sold Out</span>
					<?php endif; ?>
				</div>
			</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}
add_action('woocommerce_after_single_product_summary', 'wk_render_complete_the_look', 15);

// ═══════════════════════════════════════════════════════════════
// 2. RECENTLY VIEWED PRODUCTS
// ═══════════════════════════════════════════════════════════════

// Track current product view (via JS/localStorage - server just provides the data endpoint)
add_action('wp_footer', function() {
	if (!is_product()) return;
	global $product;
	if (!$product) return;
	// Pass current product ID to JS for localStorage tracking
	$img_id  = $product->get_image_id();
	$img_url = $img_id ? wp_get_attachment_image_url($img_id, 'woocommerce_thumbnail') : wc_placeholder_img_src();
	echo '<script id="wk-rv-track">
	(function(){
		var KEY = "wk_recently_viewed";
		var cur = { id:' . $product->get_id() . ', name:' . json_encode($product->get_name()) . ', url:' . json_encode(get_permalink($product->get_id())) . ', img:' . json_encode($img_url) . ', price:' . json_encode(strip_tags($product->get_price_html())) . ' };
		try {
			var stored = JSON.parse(localStorage.getItem(KEY)||"[]");
			stored = stored.filter(function(p){ return p.id !== cur.id; });
			stored.unshift(cur);
			stored = stored.slice(0, 10);
			localStorage.setItem(KEY, JSON.stringify(stored));
		} catch(e){}
	})();
	</script>';
}, 90);

// Render recently viewed section (populated by JS from localStorage)
add_action('woocommerce_after_single_product_summary', function() {
	if (!get_theme_mod('wk_rv_enabled', true)) return;
	$title = get_theme_mod('wk_rv_title', 'Recently Viewed');
	?>
	<section class="wk-recently-viewed" id="wk-recently-viewed" aria-label="<?php echo esc_attr($title); ?>" style="display:none;">
		<div class="wk-section-header">
			<h2 class="wk-section-title"><?php echo esc_html($title); ?></h2>
		</div>
		<div class="wk-rv-grid" id="wk-rv-grid"></div>
	</section>
	<?php
}, 25);

// Also on shop page
add_action('woocommerce_after_shop_loop', function() {
	if (!get_theme_mod('wk_rv_enabled', true)) return;
	if (!get_theme_mod('wk_rv_show_shop', false)) return;
	$title = get_theme_mod('wk_rv_title', 'Recently Viewed');
	echo '<section class="wk-recently-viewed wk-recently-viewed--shop" id="wk-recently-viewed-shop" style="display:none;">';
	echo '<div class="wk-section-header"><h2 class="wk-section-title">'.esc_html($title).'</h2></div>';
	echo '<div class="wk-rv-grid" id="wk-rv-grid-shop"></div>';
	echo '</section>';
}, 20);

// Customizer settings for recently viewed
add_action('customize_register', function($wp_customize) {
	if (!$wp_customize->get_section('wk_complete_look')) return;
	$fields = [
		['wk_rv_enabled',   'Enable Recently Viewed Products', 'checkbox', true,  ''],
		['wk_rv_title',     'Section Title',                   'text',     'Recently Viewed', ''],
		['wk_rv_show_shop', 'Show on Shop/Category page too',  'checkbox', false, ''],
	];
	foreach ($fields as [$id,$label,$type,$default,$desc]) {
		$san = $type==='checkbox' ? 'rest_sanitize_boolean' : 'sanitize_text_field';
		$wp_customize->add_setting($id, ['default'=>$default,'sanitize_callback'=>$san,'transport'=>'refresh']);
		$wp_customize->add_control($id, ['label'=>$label,'description'=>$desc,'section'=>'wk_complete_look','type'=>$type]);
	}
});

// ── Customizer settings for recommended products ─────────────────────────────
add_action('customize_register', function($wp_customize) {
	if (!$wp_customize->get_section('wk_complete_look')) return;
	$fields = [
		['wk_related_count',   'Recommended Products Count (1–12)', 'number', 8, ''],
		['wk_related_subtitle','Section Subtitle',                   'text',   'Handpicked just for you', ''],
	];
	foreach ($fields as [$id,$label,$type,$default,$desc]) {
		$wp_customize->add_setting($id,['default'=>$default,'sanitize_callback'=>'sanitize_text_field','transport'=>'refresh']);
		$wp_customize->add_control($id,['label'=>$label,'description'=>$desc,'section'=>'wk_complete_look','type'=>$type]);
	}
});

// ── AJAX quick-add for recommendation cards ───────────────────────────────────
// (Reuses wk_add_to_cart from functions.php — no extra handler needed)
