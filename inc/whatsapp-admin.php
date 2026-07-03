<?php
/**
 * WhiteKurti — WhatsApp Button Admin Settings Page
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Menu registration moved to inc/admin-hub.php

function wk_wa_get_settings() {
	$defaults = [
		'enabled'          => 0,
		'number'           => '',
		'message'          => 'Hi! I am interested in your products.',
		'position'         => 'bottom-right',
		'custom_image_url' => '',
		'size'             => 58,
		'bg_blur'          => 0,
		'tooltip'          => 'Chat with us',
		'show_tooltip'     => 1,
		'pulse_animation'  => 1,
		'bottom_offset'    => 24,
		'side_offset'      => 22,
		'hide_on_mobile'   => 0,
	];
	return array_merge( $defaults, get_option( 'wk_whatsapp_settings', [] ) );
}

add_action( 'admin_init', function() {
	if ( ! isset( $_POST['wk_wa_nonce'] ) ) return;
	if ( ! wp_verify_nonce( $_POST['wk_wa_nonce'], 'wk_wa_save' ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;

	$settings = [
		'enabled'          => ! empty( $_POST['wa_enabled'] ) ? 1 : 0,
		'number'           => preg_replace( '/[^0-9]/', '', $_POST['wa_number'] ?? '' ),
		'message'          => sanitize_textarea_field( $_POST['wa_message'] ?? '' ),
		'position'         => sanitize_text_field( $_POST['wa_position'] ?? 'bottom-right' ),
		'custom_image_url' => esc_url_raw( $_POST['wa_custom_image_url'] ?? '' ),
		'size'             => max( 40, min( 80, absint( $_POST['wa_size'] ?? 58 ) ) ),
		'bg_blur'          => max( 0, min( 20, absint( $_POST['wa_bg_blur'] ?? 0 ) ) ),
		'tooltip'          => sanitize_text_field( $_POST['wa_tooltip'] ?? 'Chat with us' ),
		'show_tooltip'     => ! empty( $_POST['wa_show_tooltip'] ) ? 1 : 0,
		'pulse_animation'  => ! empty( $_POST['wa_pulse_animation'] ) ? 1 : 0,
		'bottom_offset'    => absint( $_POST['wa_bottom_offset'] ?? 24 ),
		'side_offset'      => absint( $_POST['wa_side_offset'] ?? 22 ),
		'hide_on_mobile'   => ! empty( $_POST['wa_hide_on_mobile'] ) ? 1 : 0,
	];
	update_option( 'wk_whatsapp_settings', $settings );
	wp_redirect( admin_url('admin.php?page=wk-whatsapp&saved=1') );
	exit;

} );

function wk_wa_admin_page() {
	$s = wk_wa_get_settings();
	?>
	<div class="wrap" style="max-width:800px;">
	<h1>💬 WhatsApp Floating Button Settings</h1>

	<form method="post">
	<?php wp_nonce_field( 'wk_wa_save', 'wk_wa_nonce' ); ?>

	<style>
	.wk-wa-box { background:#fff; border:1px solid #ddd; border-radius:8px; padding:22px; margin-bottom:20px; }
	.wk-wa-box h2 { margin:0 0 16px; font-size:14px; font-weight:700; border-bottom:1px solid #eee; padding-bottom:10px; }
	.wk-wa-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
	.wk-wa-field { margin-bottom:14px; }
	.wk-wa-field label { display:block; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:#555; margin-bottom:6px; }
	.wk-wa-field input[type=text], .wk-wa-field input[type=number], .wk-wa-field input[type=url], .wk-wa-field select, .wk-wa-field textarea { width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:4px; font-size:13px; }
	.wk-wa-field input[type=color] { width:50px; height:34px; padding:2px; border:1px solid #ddd; border-radius:4px; }
	.wk-wa-toggle { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
	.wk-wa-toggle input { width:18px; height:18px; accent-color:#25D366; cursor:pointer; }
	.wk-wa-toggle label { font-size:13px; font-weight:500; cursor:pointer; }
	</style>

	<!-- Enable -->
	<div class="wk-wa-box" style="display:flex;align-items:center;gap:16px;background:<?php echo $s['enabled'] ? '#f0fdf4' : '#fff'; ?>">
		<label style="display:flex;align-items:center;gap:12px;font-size:15px;font-weight:700;cursor:pointer;">
			<input type="checkbox" name="wa_enabled" value="1" <?php checked($s['enabled'],1); ?> style="width:22px;height:22px;accent-color:#25D366;" />
			Enable WhatsApp Button
		</label>
		<?php if ( $s['enabled'] && $s['number'] ) : ?>
		<a href="https://wa.me/<?php echo $s['number']; ?>" target="_blank" class="button" style="background:#25D366;color:#fff;border-color:#1da756;">Test Button ↗</a>
		<?php endif; ?>
	</div>

	<div class="wk-wa-row">
		<!-- Left column -->
		<div>
			<div class="wk-wa-box">
				<h2>📞 Connection</h2>
				<div class="wk-wa-field">
					<label>WhatsApp Number <span style="color:red">*</span></label>
					<input type="text" name="wa_number" value="<?php echo esc_attr($s['number']); ?>" placeholder="919876543210 (country code + number, no + or spaces)" />
					<p style="font-size:11px;color:#888;margin:4px 0 0;">India: 91 + your number. e.g. 919876543210</p>
				</div>
				<div class="wk-wa-field">
					<label>Pre-filled Message</label>
					<textarea name="wa_message" rows="3"><?php echo esc_textarea($s['message']); ?></textarea>
					<p style="font-size:11px;color:#888;margin:4px 0 0;">This message is pre-filled when visitors open WhatsApp.</p>
				</div>
				<div class="wk-wa-field">
					<label>Tooltip Text</label>
					<input type="text" name="wa_tooltip" value="<?php echo esc_attr($s['tooltip']); ?>" placeholder="Chat with us" />
				</div>
				<div class="wk-wa-toggle">
					<input type="checkbox" id="wa_show_tooltip" name="wa_show_tooltip" value="1" <?php checked($s['show_tooltip'],1); ?> />
					<label for="wa_show_tooltip">Show tooltip on hover</label>
				</div>
			</div>

			<div class="wk-wa-box">
				<h2>📍 Position & Sizing</h2>
				<div class="wk-wa-field">
					<label>Position on Screen</label>
					<select name="wa_position">
						<option value="bottom-right"  <?php selected($s['position'],'bottom-right'); ?>>Bottom Right (Recommended)</option>
						<option value="bottom-left"   <?php selected($s['position'],'bottom-left'); ?>>Bottom Left</option>
						<option value="bottom-center" <?php selected($s['position'],'bottom-center'); ?>>Bottom Center</option>
					</select>
				</div>
				<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
					<div class="wk-wa-field">
						<label>Bottom Offset (px)</label>
						<input type="number" name="wa_bottom_offset" value="<?php echo esc_attr( $s['bottom_offset'] ); ?>" min="10" max="120" />
					</div>
					<div class="wk-wa-field">
						<label>Side Offset (px)</label>
						<input type="number" name="wa_side_offset" value="<?php echo esc_attr( $s['side_offset'] ); ?>" min="10" max="120" />
					</div>
				</div>
				<div class="wk-wa-field">
					<label>Button Size (px) — 40 to 80</label>
					<input type="number" name="wa_size" value="<?php echo esc_attr( $s['size'] ); ?>" min="40" max="80" />
				</div>
				<div class="wk-wa-toggle">
					<input type="checkbox" id="wa_hide_on_mobile" name="wa_hide_on_mobile" value="1" <?php checked($s['hide_on_mobile'],1); ?> />
					<label for="wa_hide_on_mobile">Hide on mobile devices</label>
				</div>
			</div>
		</div>

		<!-- Right column -->
		<div>
			<div class="wk-wa-box">
				<h2>🖼️ Button Image & Style</h2>
				<div class="wk-wa-field">
					<label>Custom Button Image URL</label>
					<input type="url" name="wa_custom_image_url" value="<?php echo esc_attr($s['custom_image_url']); ?>" placeholder="https://... (leave blank for default WhatsApp icon)" />
					<p style="font-size:11px;color:#888;margin:4px 0 0;">Upload your image to Media Library, copy URL, paste here. Leave blank for default.</p>
					<?php if ( $s['custom_image_url'] ) : ?>
					<img src="<?php echo esc_url($s['custom_image_url']); ?>" style="width:60px;height:60px;object-fit:contain;margin-top:8px;border:1px solid #ddd;border-radius:8px;" />
					<?php endif; ?>
				</div>
				<div class="wk-wa-field">
					<label>Background Blur (0 = no blur, 10 = heavy blur)</label>
					<input type="number" name="wa_bg_blur" value="<?php echo esc_attr( $s['bg_blur'] ); ?>" min="0" max="20" />
					<p style="font-size:11px;color:#888;margin:4px 0 0;">Blurs the background behind the button (frosted glass effect). Only visible if custom image has transparency.</p>
				</div>
				<div class="wk-wa-toggle">
					<input type="checkbox" id="wa_pulse_animation" name="wa_pulse_animation" value="1" <?php checked($s['pulse_animation'],1); ?> />
					<label for="wa_pulse_animation">Enable pulse/bounce animation</label>
				</div>
			</div>

			<div class="wk-wa-box">
				<h2>📋 Quick Setup Guide</h2>
				<ol style="font-size:13px;color:#555;line-height:1.8;margin:0;padding-left:20px;">
					<li>Enter your WhatsApp number with country code</li>
					<li>Write your pre-filled message</li>
					<li>Choose your preferred position</li>
					<li>Optionally upload a custom button image</li>
					<li>Check "Enable WhatsApp Button"</li>
					<li>Save Settings</li>
					<li>Visit your site and test the button!</li>
				</ol>
			</div>
		</div>
	</div>

	<input type="submit" class="button button-primary" value="Save WhatsApp Settings" style="background:#25D366;border-color:#1da756;padding:10px 24px;font-size:14px;" />
	</form>
	</div>
	<?php
}

// ── Render WhatsApp button on frontend ────────────────────────────────────────
function wk_wa_render_button() {
	$s = wk_wa_get_settings();
	if ( ! $s['enabled'] || ! $s['number'] ) return;

	$message   = rawurlencode( $s['message'] );
	$wa_url    = 'https://wa.me/' . $s['number'] . '?text=' . $message;
	$size      = $s['size'];
	$offset_b  = $s['bottom_offset'];
	$offset_s  = $s['side_offset'];

	// Position
	$pos_css = 'bottom:' . $offset_b . 'px;';
	if ( $s['position'] === 'bottom-right' )  $pos_css .= 'right:' . $offset_s . 'px;';
	if ( $s['position'] === 'bottom-left' )   $pos_css .= 'left:'  . $offset_s . 'px;';
	if ( $s['position'] === 'bottom-center' ) $pos_css .= 'left:50%;transform:translateX(-50%);';

	// Image source
	$img_src = $s['custom_image_url'] ?: WK_URI . '/assets/images/whatsapp-btn.webp';

	// Blur
	$blur_css = $s['bg_blur'] ? 'backdrop-filter:blur(' . $s['bg_blur'] . 'px);-webkit-backdrop-filter:blur(' . $s['bg_blur'] . 'px);' : '';

	// Animation
	$anim_class = $s['pulse_animation'] ? 'wk-wa-pulse' : '';

	// Mobile hide
	$mobile_class = $s['hide_on_mobile'] ? 'wk-wa-desktop-only' : '';

	// Tooltip
	$tooltip_html = '';
	if ( $s['show_tooltip'] && $s['tooltip'] ) {
		$tooltip_html = '<span class="wk-wa-tooltip">' . esc_html($s['tooltip']) . '</span>';
	}

	// Add extra bottom offset on mobile to clear bottom nav bar
	$mobile_bottom_css = '';
	if ( ! $s['hide_on_mobile'] ) {
		// JS will override this for exact bottom nav height, but this CSS provides a safe fallback
		$mobile_bottom_css = 'style="--wa-bottom:' . $offset_b . 'px;"';
	}

	echo '<a href="' . esc_url($wa_url) . '" class="wk-wa-btn ' . esc_attr($anim_class) . ' ' . esc_attr($mobile_class) . '"
	       style="position:fixed;z-index:9999;' . $pos_css . 'display:flex;width:' . $size . 'px;height:' . $size . 'px;border-radius:50%;align-items:center;justify-content:center;' . $blur_css . '"
	       target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr($s['tooltip'] ?: 'Chat on WhatsApp') . '">';
	echo '<img src="' . esc_url($img_src) . '" alt="WhatsApp" style="width:' . $size . 'px;height:' . $size . 'px;border-radius:50%;display:block;object-fit:contain;" onerror="this.style.display=\'none\'" />';
	echo $tooltip_html;
	echo '</a>';
}
add_action( 'wp_footer', 'wk_wa_render_button', 99 );
