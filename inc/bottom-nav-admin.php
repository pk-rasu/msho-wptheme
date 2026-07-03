<?php
/**
 * WhiteKurti — Sticky Bottom Navigation Bar
 * Full admin management + frontend render
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Menu registration moved to inc/admin-hub.php

// ── Default settings ──────────────────────────────────────────────────────────
function wk_bn_get_defaults() {
	return [
		'enabled'            => 1,
		'mobile_only'        => 0,           // legacy — replaced by per-device below
		'hide_on_mobile'     => 0,           // hide on phones <768px
		'hide_on_tablet'     => 0,           // hide on tablets 768–1023px
		'hide_on_desktop'    => 0,           // hide on desktop 1024px+
		'mobile_breakpoint'  => 768,         // px — mobile/tablet boundary
		'desktop_breakpoint' => 1024,        // px — tablet/desktop boundary
		'bg_color'           => '#ffffff',
		'text_color'         => '#666666',
		'active_color'       => '#6B1E3E',
		'border_color'       => '#e8e8e8',
		'font_size'          => 10,
		'icon_size'          => 22,
		'hide_on_scroll_down' => 0,
		'items' => [
			[ 'icon' => 'home',     'label' => 'Home',     'url' => '/',         'badge' => '',       'enabled' => 1 ],
			[ 'icon' => 'sparkle',  'label' => 'New',      'url' => '/new-arrivals', 'badge' => '',   'enabled' => 1 ],
			[ 'icon' => 'tag',      'label' => 'Sale',     'url' => '/sale',      'badge' => 'sale',   'enabled' => 1 ],
			[ 'icon' => 'heart',    'label' => 'Wishlist', 'url' => '/wishlist',  'badge' => '',       'enabled' => 1 ],
			[ 'icon' => 'user',     'label' => 'Account',  'url' => '/my-account','badge' => '',       'enabled' => 1 ],
		],
	];
}

function wk_bn_get_settings() {
	$saved    = get_option( 'wk_bottom_nav', [] );
	$defaults = wk_bn_get_defaults();
	$settings = array_merge( $defaults, is_array($saved) ? $saved : [] );
	// Ensure items is always a valid array
	if ( ! is_array( $settings['items'] ?? null ) ) {
		$settings['items'] = $defaults['items'];
	}
	return $settings;
}

// ── Save handler ──────────────────────────────────────────────────────────────
add_action( 'admin_init', function() {
	if ( ! isset($_POST['wk_bn_nonce']) ) return;
	if ( ! wp_verify_nonce($_POST['wk_bn_nonce'], 'wk_bn_save') ) return;
	if ( ! current_user_can('manage_options') ) return;

	$settings = [
		'enabled'             => ! empty($_POST['bn_enabled'])  ? 1 : 0,
		'mobile_only'         => 0,  // deprecated — use per-device below
		'hide_on_mobile'      => empty($_POST['bn_hide_mobile'])   ? 1 : 0,
		'hide_on_tablet'      => empty($_POST['bn_hide_tablet'])   ? 1 : 0,
		'hide_on_desktop'     => empty($_POST['bn_hide_desktop'])  ? 1 : 0,
		'mobile_breakpoint'   => max(480, min(960, absint($_POST['bn_mobile_bp']  ?? 768))),
		'desktop_breakpoint'  => max(768, min(1440, absint($_POST['bn_desktop_bp'] ?? 1024))),
		'hide_on_scroll_down' => ! empty($_POST['bn_hide_scroll']) ? 1 : 0,
		'bg_color'            => sanitize_hex_color($_POST['bn_bg_color']    ?? '#ffffff') ?: '#ffffff',
		'text_color'          => sanitize_hex_color($_POST['bn_text_color']  ?? '#666666') ?: '#666666',
		'active_color'        => sanitize_hex_color($_POST['bn_active_color']?? '#6B1E3E') ?: '#6B1E3E',
		'border_color'        => sanitize_hex_color($_POST['bn_border_color']?? '#e8e8e8') ?: '#e8e8e8',
		'font_size'           => max(8, min(14, absint($_POST['bn_font_size']  ?? 10))),
		'icon_size'           => max(16, min(32, absint($_POST['bn_icon_size'] ?? 22))),
		'items'               => [],
	];
	for ($i = 0; $i < 5; $i++) {
		$settings['items'][] = [
			'icon'    => sanitize_text_field($_POST["bn_icon_{$i}"]    ?? 'home'),
			'label'   => sanitize_text_field($_POST["bn_label_{$i}"]   ?? ''),
			'url'     => esc_url_raw($_POST["bn_url_{$i}"]             ?? '/'),
			'badge'   => sanitize_text_field($_POST["bn_badge_{$i}"]   ?? ''),
			'enabled' => ! empty($_POST["bn_item_enabled_{$i}"]) ? 1 : 0,
		];
	}
	update_option('wk_bottom_nav', $settings);
	wp_redirect( admin_url('admin.php?page=wk-bottom-nav&saved=1') );
	exit;
} );

// ── Available icons ───────────────────────────────────────────────────────────
function wk_bn_get_icons() {
	return [
		'home'     => ['label'=>'Home',     'path'=>'<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
		'sparkle'  => ['label'=>'New/Spark','path'=>'<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 18 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'],
		'tag'      => ['label'=>'Sale/Tag', 'path'=>'<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>'],
		'heart'    => ['label'=>'Wishlist', 'path'=>'<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>'],
		'user'     => ['label'=>'Account',  'path'=>'<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
		'bag'      => ['label'=>'Cart/Bag', 'path'=>'<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>'],
		'grid'     => ['label'=>'Cats/Grid','path'=>'<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
		'search'   => ['label'=>'Search',   'path'=>'<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>'],
		'gift'     => ['label'=>'Offers',   'path'=>'<polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>'],
		'truck'    => ['label'=>'Orders',   'path'=>'<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'],
		'percent'  => ['label'=>'Discount', 'path'=>'<line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>'],
		'star'     => ['label'=>'Featured', 'path'=>'<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 18 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'],
	];
}

// ── Admin page HTML ────────────────────────────────────────────────────────────
function wk_bn_admin_page() {
	$saved = isset($_GET['saved']) && $_GET['saved'] === '1';
	$s     = wk_bn_get_settings();
	$icons = wk_bn_get_icons();
	?>
	<div class="wrap" style="max-width:980px;">
	<h1>📱 Sticky Bottom Navigation Bar</h1>
	<?php if ( $saved ) : ?>
	<div class="notice notice-success is-dismissible"><p>✅ Bottom Navigation settings saved successfully.</p></div>
	<?php endif; ?>
	<p style="color:#666;">This bar appears at the bottom of the screen on mobile (and optionally desktop), giving users quick access to key pages.</p>

	<form method="post" id="wk-bn-form">
	<?php wp_nonce_field('wk_bn_save','wk_bn_nonce'); ?>

	<style>
	.wk-bn-box { background:#fff; border:1px solid #ddd; border-radius:8px; padding:22px; margin-bottom:20px; }
	.wk-bn-box h2 { margin:0 0 16px; font-size:14px; font-weight:700; border-bottom:1px solid #eee; padding-bottom:10px; }
	.wk-bn-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
	.wk-bn-field { margin-bottom:14px; }
	.wk-bn-field label { display:block; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:#555; margin-bottom:5px; }
	.wk-bn-field input[type=text],.wk-bn-field input[type=url],.wk-bn-field input[type=number],.wk-bn-field select { width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:4px; font-size:13px; }
	.wk-bn-field input[type=color] { width:44px; height:32px; padding:2px; border:1px solid #ddd; border-radius:4px; cursor:pointer; }
	.wk-bn-color-row { display:flex; align-items:center; gap:8px; }
	.wk-bn-color-row input[type=text] { width:100px; }
	.wk-bn-item { background:#f9f9f9; border:1px solid #ddd; border-radius:6px; padding:14px 16px; margin-bottom:12px; }
	.wk-bn-item-header { display:flex; align-items:center; gap:10px; margin-bottom:12px; font-weight:700; font-size:13px; }
	.wk-bn-item-fields { display:grid; grid-template-columns:auto 1fr 1fr 1fr; gap:12px; align-items:end; }
	.wk-bn-icon-select { display:grid; grid-template-columns:repeat(4,1fr); gap:6px; margin-top:6px; }
	.wk-bn-icon-opt { border:1.5px solid #ddd; border-radius:4px; padding:6px; text-align:center; cursor:pointer; transition:.15s; font-size:10px; }
	.wk-bn-icon-opt:hover { border-color:#8B1A4A; background:#f9f0f4; }
	.wk-bn-icon-opt.selected { border-color:#6B1E3E; background:#F5EAF0; }
	.wk-bn-icon-opt svg { display:block; margin:0 auto 4px; }
	.wk-bn-preview { background:var(--bn-bg,#fff); border-top:1px solid var(--bn-border,#e8e8e8); display:flex; justify-content:space-around; align-items:stretch; padding:8px 0 4px; border-radius:0 0 12px 12px; max-width:360px; box-shadow:0 -2px 12px rgba(0,0,0,.1); margin-top:12px; }
	.wk-bn-preview-item { display:flex; flex-direction:column; align-items:center; gap:4px; padding:4px 8px; cursor:pointer; min-width:60px; }
	.wk-bn-preview-item svg { color:var(--bn-text,#666); }
	.wk-bn-preview-item span { font-size:10px; color:var(--bn-text,#666); }
	.wk-bn-preview-item.active svg, .wk-bn-preview-item.active span { color:var(--bn-active,#6B1E3E); }
	.wk-toggle { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
	.wk-toggle input { width:18px; height:18px; accent-color:#6B1E3E; cursor:pointer; }
	.wk-toggle label { font-size:13px; font-weight:500; cursor:pointer; }
	</style>

	<!-- Global Enable -->
	<div class="wk-bn-box" style="background:<?php echo $s['enabled'] ? '#f0fdf4' : '#fff'; ?>;border-color:<?php echo $s['enabled']?'#86efac':'#ddd'; ?>;">
		<label style="display:flex;align-items:center;gap:12px;font-size:15px;font-weight:700;cursor:pointer;">
			<input type="checkbox" name="bn_enabled" value="1" <?php checked($s['enabled'],1); ?> style="width:22px;height:22px;accent-color:#6B1E3E;" />
			Enable Sticky Bottom Navigation Bar
		</label>
	</div>

	<div class="wk-bn-grid" style="margin-bottom:20px;">
		<!-- Behavior -->
		<div class="wk-bn-box">
			<h2>⚙️ Visibility & Behavior</h2>

			<p style="font-size:12px;color:#666;margin:0 0 14px;line-height:1.6;">
				Choose which devices show the bottom nav bar. Use breakpoints to define your exact cut-offs.
			</p>

			<table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
				<thead>
					<tr style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#555;border-bottom:1px solid #eee;">
						<th style="padding:8px 6px;text-align:left;">Device</th>
						<th style="padding:8px 6px;text-align:left;">Show Nav</th>
						<th style="padding:8px 6px;text-align:left;">Breakpoint</th>
					</tr>
				</thead>
				<tbody>
					<tr style="border-bottom:1px solid #f5f5f5;">
						<td style="padding:10px 6px;font-size:13px;">📱 Mobile</td>
						<td style="padding:10px 6px;">
							<label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
								<input type="checkbox" name="bn_hide_mobile" value="1"
									<?php checked( ! ($s['hide_on_mobile']??0), 1); ?>
									id="bn_show_mobile" style="accent-color:#6B1E3E;width:17px;height:17px;" />
								<span id="bn_show_mobile_lbl" style="font-size:12px;font-weight:600;color:<?php echo empty($s['hide_on_mobile']) ? '#166534' : '#B91C1C'; ?>">
									<?php echo empty($s['hide_on_mobile']) ? '✅ Visible' : '🚫 Hidden'; ?>
								</span>
							</label>
						</td>
						<td style="padding:10px 6px;">
							<label style="font-size:11px;color:#888;">Below
								<input type="number" name="bn_mobile_bp" value="<?php echo esc_attr($s['mobile_breakpoint']??768); ?>"
									min="320" max="960" style="width:70px;padding:4px 6px;margin:0 4px;border:1px solid #ddd;border-radius:4px;font-size:12px;" />px
							</label>
						</td>
					</tr>
					<tr style="border-bottom:1px solid #f5f5f5;">
						<td style="padding:10px 6px;font-size:13px;">💻 Tablet</td>
						<td style="padding:10px 6px;">
							<label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
								<input type="checkbox" name="bn_hide_tablet" value="1"
									<?php checked( ! ($s['hide_on_tablet']??0), 1); ?>
									id="bn_show_tablet" style="accent-color:#6B1E3E;width:17px;height:17px;" />
								<span id="bn_show_tablet_lbl" style="font-size:12px;font-weight:600;color:<?php echo empty($s['hide_on_tablet']) ? '#166534' : '#B91C1C'; ?>">
									<?php echo empty($s['hide_on_tablet']) ? '✅ Visible' : '🚫 Hidden'; ?>
								</span>
							</label>
						</td>
						<td style="padding:10px 6px;">
							<label style="font-size:11px;color:#888;">
								<?php echo esc_html($s['mobile_breakpoint']??768); ?>px –
								<input type="number" name="bn_desktop_bp" value="<?php echo esc_attr($s['desktop_breakpoint']??1024); ?>"
									min="768" max="1440" style="width:70px;padding:4px 6px;margin:0 4px;border:1px solid #ddd;border-radius:4px;font-size:12px;" />px
							</label>
						</td>
					</tr>
					<tr>
						<td style="padding:10px 6px;font-size:13px;">🖥️ Desktop</td>
						<td style="padding:10px 6px;">
							<label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
								<input type="checkbox" name="bn_hide_desktop" value="1"
									<?php checked( ! ($s['hide_on_desktop']??1), 1); ?>
									id="bn_show_desktop" style="accent-color:#6B1E3E;width:17px;height:17px;" />
								<span id="bn_show_desktop_lbl" style="font-size:12px;font-weight:600;color:<?php echo empty($s['hide_on_desktop']) ? '#166534' : '#B91C1C'; ?>">
									<?php echo empty($s['hide_on_desktop']) ? '✅ Visible' : '🚫 Hidden'; ?>
								</span>
							</label>
						</td>
						<td style="padding:10px 6px;font-size:11px;color:#888;">
							Above <?php echo esc_html($s['desktop_breakpoint']??1024); ?>px
						</td>
					</tr>
				</tbody>
			</table>

			<div class="wk-toggle" style="margin-top:12px;padding-top:12px;border-top:1px solid #f0f0f0;">
				<input type="checkbox" id="bn_hide_scroll" name="bn_hide_scroll" value="1" <?php checked($s['hide_on_scroll_down']??0,1); ?> />
				<label for="bn_hide_scroll">🔄 Hide when scrolling down, show on scroll up</label>
			</div>

			<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:6px;padding:10px 12px;margin-top:14px;font-size:11.5px;color:#0369a1;line-height:1.6;">
				<strong>ℹ️ How it works:</strong> Unchecked = hidden on that device. The breakpoints define boundaries.<br>
				<em>Default: visible on mobile &amp; tablet, hidden on desktop.</em>
			</div>
		</div>

		<!-- Style -->
		<div class="wk-bn-box">
			<h2>🎨 Style</h2>
			<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
				<?php
				$style_fields = [
					'bn_bg_color'     => ['Background',   $s['bg_color']],
					'bn_text_color'   => ['Inactive Text', $s['text_color']],
					'bn_active_color' => ['Active Text',   $s['active_color']],
					'bn_border_color' => ['Top Border',    $s['border_color']],
				];
				foreach ($style_fields as $name => [$label, $val]) : ?>
				<div class="wk-bn-field">
					<label><?php echo $label; ?></label>
					<div class="wk-bn-color-row">
						<input type="color" id="<?php echo $name; ?>_picker" value="<?php echo esc_attr($val); ?>" />
						<input type="text"  name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo esc_attr($val); ?>" style="width:90px;" />
					</div>
				</div>
				<?php endforeach; ?>
				<div class="wk-bn-field">
					<label>Font Size (px)</label>
					<input type="number" name="bn_font_size" value="<?php echo esc_attr( $s['font_size'] ); ?>" min="8" max="14" />
				</div>
				<div class="wk-bn-field">
					<label>Icon Size (px)</label>
					<input type="number" name="bn_icon_size" value="<?php echo esc_attr( $s['icon_size'] ); ?>" min="16" max="32" />
				</div>
			</div>
		</div>
	</div>

	<!-- Nav Items -->
	<div class="wk-bn-box">
		<h2>🗂️ Navigation Items (5 slots)</h2>
		<p style="font-size:12px;color:#888;margin:0 0 16px;">Configure each tab. Click an icon to select it.</p>
		<?php for ($i = 0; $i < 5; $i++) :
			$item = $s['items'][$i] ?? wk_bn_get_defaults()['items'][$i];
			$active_icon = $item['icon'] ?? 'home';
		?>
		<div class="wk-bn-item" id="bn-item-<?php echo $i; ?>">
			<div class="wk-bn-item-header">
				<span style="background:#6B1E3E;color:#fff;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;"><?php echo $i+1; ?></span>
				Tab <?php echo $i+1; ?>
				<label style="margin-left:auto;display:flex;align-items:center;gap:6px;font-weight:400;font-size:12px;cursor:pointer;">
					<input type="checkbox" name="bn_item_enabled_<?php echo $i; ?>" value="1" <?php checked($item['enabled']??1,1); ?> style="accent-color:#6B1E3E;" />
					Enabled
				</label>
			</div>
			<div class="wk-bn-item-fields">
				<div class="wk-bn-field" style="min-width:120px;">
					<label>Icon</label>
					<input type="hidden" name="bn_icon_<?php echo $i; ?>" id="bn-icon-val-<?php echo $i; ?>" value="<?php echo esc_attr($active_icon); ?>" />
					<div class="wk-bn-icon-select" id="bn-icon-grid-<?php echo $i; ?>">
						<?php foreach ($icons as $icon_key => $icon_data) :
							$sz = 18;
						?>
						<div class="wk-bn-icon-opt <?php echo $active_icon === $icon_key ? 'selected' : ''; ?>"
						     data-icon="<?php echo esc_attr($icon_key); ?>"
						     data-item="<?php echo $i; ?>"
						     title="<?php echo esc_attr($icon_data['label']); ?>">
							<svg width="<?php echo $sz; ?>" height="<?php echo $sz; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
								<?php echo $icon_data['path']; ?>
							</svg>
							<span><?php echo esc_html($icon_data['label']); ?></span>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="wk-bn-field">
					<label>Label Text</label>
					<input type="text" name="bn_label_<?php echo $i; ?>" value="<?php echo esc_attr($item['label']); ?>" placeholder="e.g. Home" />
				</div>
				<div class="wk-bn-field">
					<label>URL / Link</label>
					<input type="url" name="bn_url_<?php echo $i; ?>" value="<?php echo esc_attr($item['url']); ?>" placeholder="https://..." />
				</div>
				<div class="wk-bn-field">
					<label>Badge Type</label>
					<select name="bn_badge_<?php echo $i; ?>">
						<option value="" <?php selected($item['badge'],''); ?>>None</option>
						<option value="cart" <?php selected($item['badge'],'cart'); ?>>Cart Count</option>
						<option value="sale" <?php selected($item['badge'],'sale'); ?>>SALE</option>
						<option value="new" <?php selected($item['badge'],'new'); ?>>NEW</option>
						<option value="hot" <?php selected($item['badge'],'hot'); ?>>HOT</option>
					</select>
				</div>
			</div>
		</div>
		<?php endfor; ?>
	</div>

	<!-- Live Preview -->
	<div class="wk-bn-box">
		<h2>👁️ Live Preview</h2>
		<p style="font-size:12px;color:#888;margin:0 0 8px;">Approximate preview — updates when you save.</p>
		<div class="wk-bn-preview" id="bn-preview"
		     style="--bn-bg:<?php echo esc_attr($s['bg_color']); ?>;--bn-border:<?php echo esc_attr($s['border_color']); ?>;--bn-text:<?php echo esc_attr($s['text_color']); ?>;--bn-active:<?php echo esc_attr($s['active_color']); ?>">
			<?php foreach ($s['items'] as $j => $item) :
				if ( ! ($item['enabled']??1) ) continue;
				$icon_key = $item['icon'] ?? 'home';
				$icon_path = $icons[$icon_key]['path'] ?? $icons['home']['path'];
			?>
			<div class="wk-bn-preview-item <?php echo $j===0?'active':''; ?>">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
					<?php echo $icon_path; ?>
				</svg>
				<span><?php echo esc_html($item['label']); ?></span>
			</div>
			<?php endforeach; ?>
		</div>
	</div>

	<input type="submit" class="button button-primary" value="Save Bottom Nav Settings" style="background:#6B1E3E;border-color:#4a1228;padding:10px 28px;font-size:14px;" />
	</form>
	</div>

	<script>
	jQuery(function($){
		// Color picker sync
		$('input[type=color]').on('input change', function(){
			var id = this.id.replace('_picker','');
			$('#'+id).val(this.value);
		});
		$('input[type=text][id^=bn_]').on('input', function(){
			var pid = this.id + '_picker';
			if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) $('#'+pid).val(this.value);
		});

		// Icon selection
		$(document).on('click', '.wk-bn-icon-opt', function(){
			var item = $(this).data('item');
			$('#bn-icon-grid-' + item + ' .wk-bn-icon-opt').removeClass('selected');
			$(this).addClass('selected');
			$('#bn-icon-val-' + item).val($(this).data('icon'));
		});

		// Device toggle visual labels + instant preview feedback
		function updateDeviceLbl(cbId, lblId) {
			var $cb  = $('#' + cbId);
			var $lbl = $('#' + lblId);
			if ($cb.is(':checked')) {
				$lbl.html('<span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">✅ Visible</span>');
			} else {
				$lbl.html('<span style="background:#fee2e2;color:#B91C1C;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">🚫 Hidden</span>');
			}
			// Update preview visibility hint
			var hints = {mobile:'📱 Bottom nav will be hidden on mobile phones',tablet:'💻 Bottom nav will be hidden on tablets',desktop:'🖥️ Bottom nav will be hidden on desktop'};
			var key = cbId.replace('bn_show_','');
			var $info = $('#bn-device-info');
			if ($info.length === 0) { $('#bn-preview').after('<p id="bn-device-info" style="font-size:12px;color:#6B1E3E;margin-top:6px;"></p>'); $info = $('#bn-device-info'); }
			if (!$cb.is(':checked') && hints[key]) { $info.text(hints[key]).show(); } else { $info.text('').hide(); }
			$('#wk-bn-save-notice').show();
		}
		// Init labels on page load
		['mobile','tablet','desktop'].forEach(function(d) { updateDeviceLbl('bn_show_'+d,'bn_show_'+d+'_lbl'); });
		$('#bn_show_mobile').on('change', function(){ updateDeviceLbl('bn_show_mobile','bn_show_mobile_lbl'); });
		$('#bn_show_tablet').on('change', function(){ updateDeviceLbl('bn_show_tablet','bn_show_tablet_lbl'); });
		$('#bn_show_desktop').on('change', function(){ updateDeviceLbl('bn_show_desktop','bn_show_desktop_lbl'); });
		// Show save reminder
		$('<p id="wk-bn-save-notice" style="display:none;color:#92400e;background:#fef3c7;padding:8px 14px;border-radius:6px;font-size:12px;margin-top:8px;">💾 Changes not saved yet — click Save below</p>').insertAfter('#bn-device-info, .wk-bn-box:nth-child(3) table');

	});
	</script>
	<?php
}

// ── Output settings to JS ──────────────────────────────────────────────────────
function wk_bn_output_to_js() {
	$s = wk_bn_get_settings();
	if ( ! $s['enabled'] ) return;
	wp_localize_script( 'wk-main', 'wk_bottom_nav', [
		'enabled'             => '1',
		'mobile_only'         => '0',  // deprecated
		'hide_on_mobile'      => ($s['hide_on_mobile']  ?? 0) ? '1' : '0',
		'hide_on_tablet'      => ($s['hide_on_tablet']  ?? 0) ? '1' : '0',
		'hide_on_desktop'     => ($s['hide_on_desktop'] ?? 0) ? '1' : '0',
		'mobile_breakpoint'   => (int)($s['mobile_breakpoint']  ?? 768),
		'desktop_breakpoint'  => (int)($s['desktop_breakpoint'] ?? 1024),
		'hide_on_scroll_down' => ($s['hide_on_scroll_down']??0) ? '1' : '0',
		'bg_color'            => $s['bg_color'],
		'text_color'          => $s['text_color'],
		'active_color'        => $s['active_color'],
		'border_color'        => $s['border_color'],
		'font_size'           => $s['font_size'],
		'icon_size'           => $s['icon_size'],
		'items'               => $s['items'],
		'current_url'         => esc_url( home_url( add_query_arg( null, null ) ) ),
	] );
}
add_action( 'wp_enqueue_scripts', 'wk_bn_output_to_js', 30 );

// ── Render bottom nav HTML ─────────────────────────────────────────────────────
function wk_bn_render() {
	$s     = wk_bn_get_settings();
	$icons = wk_bn_get_icons();
	if ( ! $s['enabled'] ) return;

	$items = is_array( $s['items'] ) ? $s['items'] : [];
	$items = array_filter( $items, function($i) { return isset($i['enabled']) ? $i['enabled'] : 1; } );
	if ( empty($items) ) return;

	$current_url = home_url( add_query_arg( null, null ) );
	$wc_active   = class_exists('WooCommerce');
	$cart_count  = ($wc_active && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;

	$sz = $s['icon_size'];
	$fs = $s['font_size'];

	// Build device visibility classes
	$vis_classes = '';
	if ( $s['hide_on_mobile']  ?? 0 ) $vis_classes .= ' wk-bn-hide-mobile';
	if ( $s['hide_on_tablet']  ?? 0 ) $vis_classes .= ' wk-bn-hide-tablet';
	if ( $s['hide_on_desktop'] ?? 0 ) $vis_classes .= ' wk-bn-hide-desktop';

	// Mobile-only class added server-side to prevent FOUC on desktop
	$mobile_only_class = $s['mobile_only'] ? ' wk-bn-mobile-only' : '';
	echo '<nav class="wk-bottom-nav' . $mobile_only_class . $vis_classes . '" id="wk-bottom-nav" role="navigation" aria-label="Mobile navigation"';
	echo ' style="--bn-bg:' . esc_attr($s['bg_color']) . ';--bn-text:' . esc_attr($s['text_color']) . ';--bn-active:' . esc_attr($s['active_color']) . ';--bn-border:' . esc_attr($s['border_color']) . ';--bn-icon-size:' . $sz . 'px;--bn-font-size:' . $fs . 'px;">';

	foreach ($items as $item) {
		$icon_key  = $item['icon'] ?? 'home';
		$icon_path = $icons[$icon_key]['path'] ?? $icons['home']['path'];
		$label     = $item['label'];
		$url       = $item['url'];
		$badge     = $item['badge'];

		// Active state check
		$item_url = esc_url($url);
		$is_home  = ($icon_key === 'home' && (is_front_page() || $item_url === home_url('/')));
		$is_shop  = $wc_active && is_shop() && (strpos($url,'shop') !== false);
		$is_account = (function_exists('is_account_page') && is_account_page()) && (strpos($url,'account') !== false);

		// Badge HTML
		$badge_html = '';
		if ($badge === 'cart' && $cart_count > 0) {
			$badge_html = '<span class="wk-bn-badge wk-bn-badge--count">' . absint($cart_count) . '</span>';
		} elseif ($badge === 'sale') {
			$badge_html = '<span class="wk-bn-badge wk-bn-badge--sale">SALE</span>';
		} elseif ($badge === 'new') {
			$badge_html = '<span class="wk-bn-badge wk-bn-badge--new">NEW</span>';
		} elseif ($badge === 'hot') {
			$badge_html = '<span class="wk-bn-badge wk-bn-badge--hot">HOT</span>';
		}

		$active_class = ($is_home || $is_shop || $is_account) ? ' is-active' : '';

		echo '<a href="' . $item_url . '" class="wk-bn-item' . $active_class . '" aria-label="' . esc_attr($label) . '">';
		echo '<span class="wk-bn-item__icon-wrap">';
		echo '<svg width="' . $sz . '" height="' . $sz . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icon_path . '</svg>';
		if ($badge_html) echo $badge_html;
		echo '</span>';
		echo '<span class="wk-bn-item__label">' . esc_html($label) . '</span>';
		echo '</a>';
	}

	echo '</nav>';
}
add_action( 'wp_footer', 'wk_bn_render', 95 );
