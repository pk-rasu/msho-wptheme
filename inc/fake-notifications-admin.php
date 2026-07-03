<?php
/**
 * WhiteKurti — Fake Notifications Admin Panel
 * Complete management system for social proof notifications
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Register admin menu page ─────────────────────────────────────────────────
// Menu registration moved to inc/admin-hub.php

// ── Save settings ────────────────────────────────────────────────────────────
function wk_fn_save_settings() {
	if ( ! isset( $_POST['wk_fn_nonce'] ) ) return;
	if ( ! wp_verify_nonce( $_POST['wk_fn_nonce'], 'wk_fn_save' ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;

	$settings = [
		'enabled'          => ! empty( $_POST['fn_enabled'] ) ? 1 : 0,
		'position'         => sanitize_text_field( $_POST['fn_position'] ?? 'bottom-left' ),
		'per_minute'       => max( 1, min( 10, absint( $_POST['fn_per_minute'] ?? 2 ) ) ),
		'display_duration' => max( 2, min( 30, absint( $_POST['fn_display_duration'] ?? 5 ) ) ),
		'first_delay'      => max( 3, absint( $_POST['fn_first_delay'] ?? 8 ) ),
		'product_specific' => ! empty( $_POST['fn_product_specific'] ) ? 1 : 0,
		'show_image'       => ! empty( $_POST['fn_show_image'] ) ? 1 : 0,
		'bg_color'         => sanitize_hex_color( $_POST['fn_bg_color'] ?? '#ffffff' ) ?: '#ffffff',
		'text_color'       => sanitize_hex_color( $_POST['fn_text_color'] ?? '#111111' ) ?: '#111111',
		'accent_color'     => sanitize_hex_color( $_POST['fn_accent_color'] ?? '#25D366' ) ?: '#25D366',
		'border_radius'    => absint( $_POST['fn_border_radius'] ?? 10 ),
	];

	// Products list
	$products_raw = sanitize_textarea_field( $_POST['fn_products'] ?? '' );
	$products     = array_filter( array_map( 'trim', explode( "\n", $products_raw ) ) );
	$settings['products'] = $products;

	// Active cities
	$all_cities   = wk_fn_get_all_cities();
	$active_cities = [];
	foreach ( $all_cities as $city ) {
		if ( ! empty( $_POST['fn_city_' . sanitize_title($city)] ) ) {
			$active_cities[] = $city;
		}
	}
	if ( empty( $active_cities ) ) $active_cities = array_slice( $all_cities, 0, 20 );
	$settings['cities'] = $active_cities;

	// Custom cities
	$custom_cities_raw = sanitize_textarea_field( $_POST['fn_custom_cities'] ?? '' );
	$custom_cities     = array_filter( array_map( 'trim', explode( "\n", $custom_cities_raw ) ) );
	$settings['custom_cities'] = $custom_cities;

	update_option( 'wk_fake_notifications', $settings );
}
add_action( 'admin_init', function() {
	if ( isset( $_POST['wk_fn_nonce'] ) ) wk_fn_save_settings();
} );

// ── Get all cities ───────────────────────────────────────────────────────────
function wk_fn_get_all_cities() {
	return [
		'Mumbai','Delhi','Bengaluru','Hyderabad','Ahmedabad','Chennai','Kolkata','Surat','Pune','Jaipur',
		'Lucknow','Kanpur','Nagpur','Indore','Thane','Bhopal','Visakhapatnam','Pimpri-Chinchwad','Patna','Vadodara',
		'Ghaziabad','Ludhiana','Agra','Nashik','Faridabad','Meerut','Rajkot','Kalyan-Dombivali','Vasai-Virar','Varanasi',
		'Srinagar','Aurangabad','Dhanbad','Amritsar','Navi Mumbai','Allahabad','Ranchi','Howrah','Coimbatore','Jabalpur',
		'Gwalior','Vijayawada','Jodhpur','Madurai','Raipur','Kota','Chandigarh','Guwahati','Solapur','Hubli-Dharwad',
		'Mysuru','Tiruchirappalli','Bareilly','Aligarh','Tiruppur','Moradabad','Jalandhar','Bhubaneswar','Salem','Warangal',
	];
}

// ── Get default settings ─────────────────────────────────────────────────────
function wk_fn_get_settings() {
	$defaults = [
		'enabled'          => 1,
		'position'         => 'bottom-left',
		'per_minute'       => 2,
		'display_duration' => 5,
		'first_delay'      => 8,
		'product_specific' => 1,
		'show_image'       => 1,
		'bg_color'         => '#ffffff',
		'text_color'       => '#111111',
		'accent_color'     => '#8B1A4A',
		'border_radius'    => 10,
		'products'         => [
			'White Embroidered Kurta Set',
			'Floral Coord Set',
			'Cotton Kaftan',
			'Silk Saree',
			'Palazzo Set',
			'Anarkali Kurti',
			'Chikankari Kurti',
			'Short Kurti',
			'A-Line Kurti',
			'Straight Fit Kurti',
			'Trail Cut Kurti',
			'Angrakha Kurti',
			'Kurti with Dupatta',
			'Cotton Top',
			'Wide-Leg Shorts',
			'Printed Co-ord Set',
		],
		'cities'       => array_slice( wk_fn_get_all_cities(), 0, 30 ),
		'custom_cities'=> [],
	];
	$saved = get_option( 'wk_fake_notifications', [] );
	return array_merge( $defaults, $saved );
}

// ── Admin page HTML ──────────────────────────────────────────────────────────
function wk_fn_admin_page() {
	$s    = wk_fn_get_settings();
	$cities = wk_fn_get_all_cities();
	$saved_msg = isset( $_GET['settings-updated'] ) ? '<div class="notice notice-success is-dismissible"><p>✅ Notification settings saved!</p></div>' : '';
	?>
	<div class="wrap" style="max-width:1100px;">
	<h1 style="display:flex;align-items:center;gap:10px;">🔔 Fake Notifications Manager</h1>
	<?php echo $saved_msg; ?>

	<form method="post" action="">
	<?php wp_nonce_field( 'wk_fn_save', 'wk_fn_nonce' ); ?>

	<style>
	.wk-fn-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:20px; }
	.wk-fn-box { background:#fff; border:1px solid #ddd; border-radius:8px; padding:20px; }
	.wk-fn-box h2 { margin:0 0 16px; font-size:15px; border-bottom:1px solid #eee; padding-bottom:10px; }
	.wk-fn-field { margin-bottom:16px; }
	.wk-fn-field label { display:block; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:#555; margin-bottom:6px; }
	.wk-fn-field input[type=text], .wk-fn-field input[type=number], .wk-fn-field select, .wk-fn-field textarea { width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:4px; font-size:13px; }
	.wk-fn-field textarea { min-height:120px; resize:vertical; font-family:monospace; }
	.wk-fn-field input[type=color] { width:50px; height:34px; padding:2px; border:1px solid #ddd; border-radius:4px; cursor:pointer; }
	.wk-fn-inline { display:flex; align-items:center; gap:8px; }
	.wk-fn-preview { background:var(--fn-bg,#fff); color:var(--fn-text,#111); border-radius:var(--fn-radius,10px); box-shadow:0 4px 20px rgba(0,0,0,.15); padding:14px 40px 14px 14px; display:inline-flex; align-items:center; gap:12px; max-width:280px; position:relative; border:1px solid #eee; margin-top:12px; }
	.wk-fn-preview__img { width:48px; height:48px; background:#f0f0f0; border-radius:6px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:20px; }
	.wk-fn-preview__name { font-weight:700; font-size:13px; margin-bottom:2px; }
	.wk-fn-preview__action { font-size:12px; color:#555; }
	.wk-fn-preview__time { font-size:10.5px; color:#999; }
	.wk-fn-city-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:6px; max-height:240px; overflow-y:auto; border:1px solid #eee; padding:10px; border-radius:4px; }
	.wk-fn-city-grid label { display:flex; align-items:center; gap:5px; font-size:12px; cursor:pointer; }
	.wk-fn-toggle { display:flex; align-items:center; gap:10px; }
	.wk-fn-toggle input[type=checkbox] { width:18px; height:18px; accent-color:#8B1A4A; cursor:pointer; }
	.wk-fn-section-full { grid-column:1/-1; }
	.wk-fn-pos-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
	.wk-fn-pos-btn { border:1.5px solid #ddd; border-radius:6px; padding:10px; text-align:center; cursor:pointer; font-size:12px; transition:.15s; }
	.wk-fn-pos-btn:has(input:checked) { border-color:#8B1A4A; background:#f9f0f4; }
	.wk-fn-pos-btn input { display:none; }
	.wk-select-all { font-size:11px; color:#8B1A4A; cursor:pointer; text-decoration:underline; margin-left:8px; }
	</style>

	<!-- Enable toggle + preview row -->
	<div style="display:flex;align-items:center;gap:20px;background:#fff;padding:16px 20px;border:1px solid #ddd;border-radius:8px;margin-top:16px;">
		<label style="display:flex;align-items:center;gap:10px;font-size:15px;font-weight:600;cursor:pointer;">
			<input type="checkbox" name="fn_enabled" value="1" <?php checked($s['enabled'],1); ?> style="width:20px;height:20px;accent-color:#8B1A4A;" />
			Enable Social Proof Notifications
		</label>
		<span style="font-size:12px;color:#888;">When enabled, visitors will see popup notifications of recent purchases.</span>
	</div>

	<div class="wk-fn-grid">

		<!-- POSITION & TIMING -->
		<div class="wk-fn-box">
			<h2>📍 Position & Timing</h2>
			<div class="wk-fn-field">
				<label>Notification Position</label>
				<div class="wk-fn-pos-grid">
					<?php
					$positions = [
						'top-left'     => '↖ Top Left',
						'top-right'    => '↗ Top Right',
						'top-center'   => '↑ Top Center',
						'bottom-left'  => '↙ Bottom Left',
						'bottom-right' => '↘ Bottom Right',
						'bottom-center'=> '↓ Bottom Center',
					];
					foreach ($positions as $val => $label) :
					?>
					<label class="wk-fn-pos-btn">
						<input type="radio" name="fn_position" value="<?php echo esc_attr( $val ); ?>" <?php checked($s['position'],$val); ?> />
						<?php echo $label; ?>
					</label>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="wk-fn-field">
				<label>Notifications Per Minute (1–10)</label>
				<input type="number" name="fn_per_minute" value="<?php echo esc_attr( $s['per_minute'] ); ?>" min="1" max="10" />
				<p style="font-size:11px;color:#888;margin:4px 0 0;">Recommended: 2–3. Too many feels spammy.</p>
			</div>
			<div class="wk-fn-field">
				<label>Display Duration (seconds) — how long notification stays visible</label>
				<input type="number" name="fn_display_duration" value="<?php echo esc_attr( $s['display_duration'] ); ?>" min="2" max="30" />
			</div>
			<div class="wk-fn-field">
				<label>First Notification Delay (seconds after page load)</label>
				<input type="number" name="fn_first_delay" value="<?php echo esc_attr( $s['first_delay'] ); ?>" min="3" max="60" />
			</div>
			<div class="wk-fn-field">
				<div class="wk-fn-toggle">
					<input type="checkbox" id="fn_product_specific" name="fn_product_specific" value="1" <?php checked($s['product_specific'],1); ?> />
					<label for="fn_product_specific" style="font-weight:600;font-size:13px;cursor:pointer;">Show product-specific notifications on product pages</label>
				</div>
				<p style="font-size:11px;color:#888;margin:4px 0 0;">When ON, product pages will show "<em>Priya from Delhi just bought THIS product</em>" instead of random products.</p>
			</div>
			<div class="wk-fn-field">
				<div class="wk-fn-toggle">
					<input type="checkbox" id="fn_show_image" name="fn_show_image" value="1" <?php checked($s['show_image'],1); ?> />
					<label for="fn_show_image" style="font-weight:600;font-size:13px;cursor:pointer;">Show product thumbnail in notification</label>
				</div>
			</div>
		</div>

		<!-- APPEARANCE -->
		<div class="wk-fn-box">
			<h2>🎨 Appearance</h2>
			<div class="wk-fn-field">
				<label>Background Color</label>
				<div class="wk-fn-inline">
					<input type="color" id="fn_bg_color" name="fn_bg_color" value="<?php echo esc_attr($s['bg_color']); ?>" />
					<input type="text" id="fn_bg_hex" value="<?php echo esc_attr($s['bg_color']); ?>" style="width:90px;" />
				</div>
			</div>
			<div class="wk-fn-field">
				<label>Text Color</label>
				<div class="wk-fn-inline">
					<input type="color" id="fn_text_color" name="fn_text_color" value="<?php echo esc_attr($s['text_color']); ?>" />
					<input type="text" id="fn_text_hex" value="<?php echo esc_attr($s['text_color']); ?>" style="width:90px;" />
				</div>
			</div>
			<div class="wk-fn-field">
				<label>Accent / Time Color</label>
				<div class="wk-fn-inline">
					<input type="color" id="fn_accent_color" name="fn_accent_color" value="<?php echo esc_attr($s['accent_color']); ?>" />
					<input type="text" id="fn_accent_hex" value="<?php echo esc_attr($s['accent_color']); ?>" style="width:90px;" />
				</div>
			</div>
			<div class="wk-fn-field">
				<label>Border Radius (px) — 0 for sharp, 10 for rounded, 24 for pill</label>
				<input type="number" name="fn_border_radius" value="<?php echo esc_attr( $s['border_radius'] ); ?>" min="0" max="24" />
			</div>
			<div class="wk-fn-field">
				<label>Preview</label>
				<div class="wk-fn-preview" id="fn-preview" style="--fn-bg:<?php echo esc_attr($s['bg_color']); ?>;--fn-text:<?php echo esc_attr($s['text_color']); ?>;--fn-radius:<?php echo $s['border_radius']; ?>px">
					<div class="wk-fn-preview__img">👕</div>
					<div>
						<div class="wk-fn-preview__name" style="color:<?php echo esc_attr($s['text_color']); ?>">Priya from Mumbai</div>
						<div class="wk-fn-preview__action">just bought White Embroidered Kurta</div>
						<div class="wk-fn-preview__time" style="color:<?php echo esc_attr($s['accent_color']); ?>">● 4 minutes ago</div>
					</div>
				</div>
			</div>
		</div>

		<!-- PRODUCTS LIST -->
		<div class="wk-fn-box">
			<h2>👗 Product Names List
				<span style="font-size:11px;font-weight:400;color:#888;margin-left:8px;">(one per line — these are displayed in notifications)</span>
			</h2>
			<div class="wk-fn-field">
				<textarea name="fn_products" placeholder="White Embroidered Kurta Set&#10;Floral Coord Set&#10;Cotton Kaftan&#10;..."><?php echo esc_textarea( implode("\n", $s['products']) ); ?></textarea>
				<p style="font-size:11px;color:#888;margin:4px 0 0;">Add/remove/edit product names. These are randomly picked for notifications. One per line.</p>
			</div>
		</div>

		<!-- CITIES -->
		<div class="wk-fn-box">
			<h2>🗺️ Active Cities
				<span class="wk-select-all" id="fn-select-all">Select All</span>
				<span class="wk-select-all" id="fn-deselect-all" style="margin-left:8px;">Deselect All</span>
			</h2>
			<p style="font-size:12px;color:#888;margin:0 0 10px;">Check cities to include in notifications. Unchecked cities won't appear.</p>
			<div class="wk-fn-city-grid" id="fn-city-grid">
				<?php foreach ($cities as $city) :
					$slug = sanitize_title($city);
					$checked = in_array($city, $s['cities']) ? 'checked' : '';
				?>
				<label>
					<input type="checkbox" name="fn_city_<?php echo $slug; ?>" value="1" <?php echo $checked; ?> class="fn-city-cb" />
					<?php echo esc_html($city); ?>
				</label>
				<?php endforeach; ?>
			</div>
			<div class="wk-fn-field" style="margin-top:16px;">
				<label>Add Custom Cities (one per line)</label>
				<textarea name="fn_custom_cities" style="min-height:80px;" placeholder="Your City&#10;Another City"><?php echo esc_textarea( implode("\n", $s['custom_cities'] ?? []) ); ?></textarea>
			</div>
		</div>

	</div>

	<!-- Save Button -->
	<div style="margin-top:20px;padding:16px;background:#fff;border:1px solid #ddd;border-radius:8px;display:flex;align-items:center;justify-content:space-between;">
		<div style="font-size:13px;color:#555;">
			Changes take effect immediately for all visitors.
		</div>
		<input type="submit" class="button button-primary" value="Save Notification Settings" style="background:#8B1A4A;border-color:#6d1339;padding:10px 24px;font-size:14px;" />
	</div>

	</form>
	</div>

	<script>
	jQuery(function($){
		// Color picker sync
		function syncColor(colorId, hexId) {
			$('#'+colorId).on('input change', function(){ $('#'+hexId).val(this.value); updatePreview(); });
			$('#'+hexId).on('input', function(){ if(/^#[0-9A-Fa-f]{6}$/.test(this.value)){ $('#'+colorId).val(this.value); updatePreview(); } });
		}
		syncColor('fn_bg_color','fn_bg_hex');
		syncColor('fn_text_color','fn_text_hex');
		syncColor('fn_accent_color','fn_accent_hex');

		function updatePreview(){
			var $p = $('#fn-preview');
			$p.css('background', $('#fn_bg_hex').val());
			$p.css('color', $('#fn_text_hex').val());
			$p.css('border-radius', $('input[name=fn_border_radius]').val() + 'px');
			$p.find('.wk-fn-preview__name').css('color', $('#fn_text_hex').val());
			$p.find('.wk-fn-preview__time').css('color', $('#fn_accent_hex').val());
		}
		$('input[name=fn_border_radius]').on('input', updatePreview);

		// Select/Deselect all cities
		$('#fn-select-all').on('click', function(){ $('.fn-city-cb').prop('checked', true); });
		$('#fn-deselect-all').on('click', function(){ $('.fn-city-cb').prop('checked', false); });

		// Radio button position preview update
		$('input[name=fn_position]').on('change', function(){
			$('.wk-fn-pos-btn').css({borderColor:'#ddd',background:''});
			$(this).closest('.wk-fn-pos-btn').css({borderColor:'#8B1A4A',background:'#f9f0f4'});
		});
	});
	</script>
	<?php
}

// ── Output notification settings to frontend ─────────────────────────────────
function wk_fn_output_settings() {
	$s = wk_fn_get_settings();
	if ( ! $s['enabled'] ) return;

	// Get all cities
	$cities = array_merge( $s['cities'], $s['custom_cities'] ?? [] );
	$cities = array_filter( array_unique( $cities ) );
	if ( empty( $cities ) ) $cities = wk_fn_get_all_cities();

	// Interval in ms
	$interval_ms = $s['per_minute'] > 0 ? round( 60000 / $s['per_minute'] ) : 30000;

	// Product-specific: get current product name
	$current_product_name = '';
	if ( $s['product_specific'] && is_product() ) {
		global $product;
		if ( $product instanceof WC_Product ) {
			$current_product_name = $product->get_name();
		} elseif ( is_singular('product') ) {
			$current_product_name = get_the_title();
		}
	}

	// Products from WooCommerce if empty
	$products = $s['products'];
	if ( empty( $products ) && class_exists('WooCommerce') ) {
		$posts = get_posts(['post_type'=>'product','posts_per_page'=>20,'post_status'=>'publish']);
		foreach ( $posts as $p ) $products[] = $p->post_title;
	}

	wp_localize_script( 'wk-main', 'wk_fake_notifications', [
		'enabled'           => '1',
		'position'          => $s['position'],
		'interval'          => $interval_ms,
		'display_duration'  => (int)$s['display_duration'] * 1000,
		'first_delay'       => (int)$s['first_delay'] * 1000,
		'bg_color'          => $s['bg_color'],
		'text_color'        => $s['text_color'],
		'accent_color'      => $s['accent_color'],
		'border_radius'     => (int)$s['border_radius'],
		'show_image'        => $s['show_image'] ? '1' : '0',
		'product_specific'  => $s['product_specific'] ? '1' : '0',
		'current_product'   => $current_product_name,
		'products'          => array_values( $products ),
		'cities'            => array_values( $cities ),
	] );
}
add_action( 'wp_enqueue_scripts', 'wk_fn_output_settings', 30 );

// ── Render notification popup HTML ────────────────────────────────────────────
function wk_fn_popup_html() {
	$s = wk_fn_get_settings();
	if ( ! $s['enabled'] ) return;

	$bg     = esc_attr( $s['bg_color'] );
	$text   = esc_attr( $s['text_color'] );
	$accent = esc_attr( $s['accent_color'] );
	$radius = absint( $s['border_radius'] );
	?>
	<div id="wk-fn-popup"
	     role="status"
	     aria-live="polite"
	     aria-atomic="true"
	     style="
	       position:fixed;
	       z-index:99998;
	       display:none;
	       width:300px;
	       max-width:calc(100vw - 28px);
	       background:<?php echo $bg; ?>;
	       border-radius:<?php echo $radius; ?>px;
	       box-shadow:0 8px 32px rgba(0,0,0,.18), 0 2px 8px rgba(0,0,0,.08);
	       overflow:hidden;
	       font-family:inherit;
	       border:1px solid rgba(0,0,0,.06);
	     ">

		<!-- Inner row: image + text + close -->
		<div style="
		  display:flex;
		  align-items:center;
		  gap:12px;
		  padding:13px 38px 13px 13px;
		  position:relative;
		">

			<!-- Product image / emoji placeholder -->
			<div id="wk-fn-img-wrap" style="
			  flex-shrink:0;
			  width:52px;
			  height:52px;
			  border-radius:<?php echo min($radius, 8); ?>px;
			  overflow:hidden;
			  background:#f5f0f3;
			  display:flex;
			  align-items:center;
			  justify-content:center;
			">
				<img id="wk-fn-img" src="" alt="" style="width:100%;height:100%;object-fit:cover;display:none;" />
				<span id="wk-fn-emoji" style="font-size:24px;line-height:1;">👗</span>
			</div>

			<!-- Text content -->
			<div style="flex:1;min-width:0;">
				<div id="wk-fn-name"
				     style="font-weight:700;font-size:13px;line-height:1.3;margin-bottom:2px;color:<?php echo $text; ?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
				</div>
				<div id="wk-fn-action"
				     style="font-size:12px;color:<?php echo $text; ?>;opacity:.72;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
				</div>
				<div id="wk-fn-time"
				     style="font-size:10.5px;margin-top:4px;color:<?php echo $accent; ?>;font-weight:500;display:flex;align-items:center;gap:4px;">
				</div>
			</div>

			<!-- Close × -->
			<button id="wk-fn-close"
			        type="button"
			        aria-label="Dismiss notification"
			        style="
			          position:absolute;
			          top:8px;
			          right:10px;
			          background:none;
			          border:none;
			          cursor:pointer;
			          font-size:17px;
			          color:<?php echo $text; ?>;
			          opacity:.4;
			          padding:2px 4px;
			          line-height:1;
			          border-radius:3px;
			          transition:opacity .15s;
			        "
			        onmouseover="this.style.opacity='.8'"
			        onmouseout="this.style.opacity='.4'">
				&times;
			</button>

		</div><!-- /inner row -->
	</div><!-- /#wk-fn-popup -->
	<?php
}
add_action( 'wp_footer', 'wk_fn_popup_html', 97 );
