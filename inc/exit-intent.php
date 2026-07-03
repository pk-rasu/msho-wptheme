<?php
/**
 * WhiteKurti — Exit Intent Popup
 * Desktop: mouse leaves viewport top | Mobile: idle timer
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Admin page ────────────────────────────────────────────────────────────────
// Menu registration moved to inc/admin-hub.php

function wk_ep_get_settings() {
	return wp_parse_args(get_option('wk_exit_popup',[]), [
		'enabled'         => 0,
		'desktop_trigger' => 'mouse_leave',
		'mobile_trigger'  => 'idle',
		'mobile_idle_sec' => 20,
		'delay_sec'       => 3,
		'show_once_hours' => 24,
		'pages'           => 'all',
		'bg_color'        => '#FDFCFA',
		'overlay_opacity' => 70,
		'accent_color'    => '#6B1E3E',
		'image_url'       => '',
		'eyebrow'         => '⏰ Wait! Before you go...',
		'heading'         => 'Get 10% Off Your First Order',
		'body'            => "Don't miss out! Use code below at checkout and save on your first purchase.",
		'coupon_code'     => 'WELCOME10',
		'cta_text'        => 'Shop Now & Save 10%',
		'cta_url'         => '',
		'secondary_text'  => 'No thanks, I\'ll pay full price.',
		'collect_email'   => 0,
		'email_placeholder'=> 'Enter your email',
		'email_btn_text'  => 'Send My Coupon',
	]);
}

add_action('admin_init', function() {
	if (!isset($_POST['wk_ep_nonce'])) return;
	if (!wp_verify_nonce($_POST['wk_ep_nonce'],'wk_ep_save')) return;
	if (!current_user_can('manage_options')) return;
	$s = [];
	$bools = ['enabled','collect_email'];
	$texts = ['desktop_trigger','mobile_trigger','pages','bg_color','accent_color','image_url','eyebrow','heading','body','coupon_code','cta_text','cta_url','secondary_text','email_placeholder','email_btn_text'];
	$nums  = ['mobile_idle_sec','delay_sec','show_once_hours','overlay_opacity'];
	foreach ($bools as $k) $s[$k] = !empty($_POST['ep_'.$k]) ? 1 : 0;
	foreach ($texts as $k) $s[$k] = sanitize_textarea_field($_POST['ep_'.$k]??'');
	foreach ($nums  as $k) $s[$k] = absint($_POST['ep_'.$k]??0);
	update_option('wk_exit_popup', $s);
});

function wk_ep_admin_page() {
	$s = wk_ep_get_settings();
	?>
	<div class="wrap" style="max-width:900px;">
	<h1>🚪 Exit Intent Popup</h1>
	<form method="post">
	<?php wp_nonce_field('wk_ep_save','wk_ep_nonce'); ?>
	<style>
	.wk-ep-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
	.wk-ep-box{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;margin-bottom:18px;}
	.wk-ep-box h2{margin:0 0 14px;font-size:14px;border-bottom:1px solid #eee;padding-bottom:10px;}
	.wk-ep-field{margin-bottom:14px;}
	.wk-ep-field label{display:block;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#555;margin-bottom:4px;}
	.wk-ep-field input[type=text],.wk-ep-field input[type=number],.wk-ep-field input[type=url],.wk-ep-field select,.wk-ep-field textarea{width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:4px;font-size:13px;}
	.wk-ep-field input[type=color]{width:44px;height:32px;padding:2px;border:1px solid #ddd;border-radius:4px;cursor:pointer;}
	.wk-ep-field textarea{min-height:70px;resize:vertical;}
	.wk-ep-field .color-row{display:flex;align-items:center;gap:8px;}
	.wk-ep-toggle{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
	.wk-ep-toggle input{width:18px;height:18px;accent-color:#6B1E3E;}
	.wk-ep-toggle label{font-size:13px;font-weight:600;cursor:pointer;}
	/* Preview */
	.wk-ep-preview{background:#f4f0ea;border:1px solid #ddd;border-radius:8px;padding:20px;display:flex;align-items:center;justify-content:center;min-height:200px;margin-top:12px;}
	.wk-ep-prev-inner{background:#fff;border-radius:8px;padding:24px 28px;max-width:340px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.12);}
	.wk-ep-prev-eyebrow{font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;margin-bottom:8px;}
	.wk-ep-prev-heading{font-size:20px;font-weight:700;margin:0 0 8px;line-height:1.25;}
	.wk-ep-prev-body{font-size:13px;color:#555;margin-bottom:12px;}
	.wk-ep-prev-coupon{border:2px dashed;padding:8px 16px;font-weight:800;letter-spacing:.12em;font-size:16px;margin-bottom:14px;display:inline-block;}
	.wk-ep-prev-btn{padding:12px 24px;font-size:13px;font-weight:700;border:none;cursor:pointer;text-decoration:none;display:block;text-align:center;color:#fff;border-radius:3px;}
	</style>

	<div style="background:<?php echo ($s['enabled'])?'#f0fdf4':'#fff'; ?>;border:1px solid <?php echo ($s['enabled'])?'#86efac':'#ddd'; ?>;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
		<label style="display:flex;align-items:center;gap:12px;font-size:15px;font-weight:700;cursor:pointer;">
			<input type="checkbox" name="ep_enabled" value="1" <?php checked($s['enabled'],1); ?> style="width:22px;height:22px;accent-color:#6B1E3E;" />
			Enable Exit Intent Popup
		</label>
	</div>

	<div class="wk-ep-grid">
		<!-- Left col -->
		<div>
			<div class="wk-ep-box">
				<h2>⚙️ Trigger Settings</h2>
				<div class="wk-ep-field">
					<label>Desktop Trigger</label>
					<select name="ep_desktop_trigger">
						<option value="mouse_leave" <?php selected($s['desktop_trigger'],'mouse_leave'); ?>>Mouse leaves browser (standard)</option>
						<option value="scroll_up"   <?php selected($s['desktop_trigger'],'scroll_up'); ?>>Scrolls up rapidly</option>
						<option value="timer"        <?php selected($s['desktop_trigger'],'timer'); ?>>After X seconds on page</option>
					</select>
				</div>
				<div class="wk-ep-field">
					<label>Mobile Trigger</label>
					<select name="ep_mobile_trigger">
						<option value="idle"   <?php selected($s['mobile_trigger'],'idle'); ?>>Idle for X seconds</option>
						<option value="scroll" <?php selected($s['mobile_trigger'],'scroll'); ?>>After scrolling 60% of page</option>
					</select>
				</div>
				<div class="wk-ep-field">
					<label>Mobile Idle Duration (seconds)</label>
					<input type="number" name="ep_mobile_idle_sec" value="<?php echo esc_attr( $s['mobile_idle_sec'] ); ?>" min="5" max="60" />
				</div>
				<div class="wk-ep-field">
					<label>Minimum Delay Before Showing (seconds)</label>
					<input type="number" name="ep_delay_sec" value="<?php echo esc_attr( $s['delay_sec'] ); ?>" min="0" max="60" />
					<p style="font-size:11px;color:#888;margin:3px 0 0;">Don't show before this many seconds on page.</p>
				</div>
				<div class="wk-ep-field">
					<label>Don't Show Again For (hours)</label>
					<input type="number" name="ep_show_once_hours" value="<?php echo esc_attr( $s['show_once_hours'] ); ?>" min="1" max="720" />
					<p style="font-size:11px;color:#888;margin:3px 0 0;">After dismiss/accept, won't show again for this long.</p>
				</div>
				<div class="wk-ep-field">
					<label>Show On Pages</label>
					<select name="ep_pages">
						<option value="all"        <?php selected($s['pages'],'all'); ?>>All pages</option>
						<option value="shop"       <?php selected($s['pages'],'shop'); ?>>Shop / Category only</option>
						<option value="product"    <?php selected($s['pages'],'product'); ?>>Product pages only</option>
						<option value="cart"       <?php selected($s['pages'],'cart'); ?>>Cart page only</option>
						<option value="not_checkout" <?php selected($s['pages'],'not_checkout'); ?>>Everywhere except checkout</option>
					</select>
				</div>
			</div>

			<div class="wk-ep-box">
				<h2>🎨 Style</h2>
				<div class="wk-ep-field">
					<label>Background Color</label>
					<div class="color-row">
						<input type="color" value="<?php echo esc_attr($s['bg_color']); ?>" oninput="document.querySelector('[name=ep_bg_color]').value=this.value;updatePreview()" />
						<input type="text" name="ep_bg_color" value="<?php echo esc_attr($s['bg_color']); ?>" style="width:90px;" />
					</div>
				</div>
				<div class="wk-ep-field">
					<label>Accent Color (button & highlights)</label>
					<div class="color-row">
						<input type="color" value="<?php echo esc_attr($s['accent_color']); ?>" oninput="document.querySelector('[name=ep_accent_color]').value=this.value;updatePreview()" />
						<input type="text" name="ep_accent_color" value="<?php echo esc_attr($s['accent_color']); ?>" style="width:90px;" />
					</div>
				</div>
				<div class="wk-ep-field">
					<label>Overlay Opacity (% darkness)</label>
					<input type="number" name="ep_overlay_opacity" value="<?php echo esc_attr( $s['overlay_opacity'] ); ?>" min="20" max="90" />
				</div>
				<div class="wk-ep-field">
					<label>Popup Image URL (optional — top banner image)</label>
					<input type="url" name="ep_image_url" value="<?php echo esc_attr($s['image_url']); ?>" placeholder="https://..." />
					<p style="font-size:11px;color:#888;margin:3px 0 0;">Leave blank for no image. Recommended: 560×200px.</p>
				</div>
			</div>
		</div>

		<!-- Right col -->
		<div>
			<div class="wk-ep-box">
				<h2>✍️ Copy & Content</h2>
				<div class="wk-ep-field">
					<label>Eyebrow Text</label>
					<input type="text" name="ep_eyebrow" value="<?php echo esc_attr($s['eyebrow']); ?>" />
				</div>
				<div class="wk-ep-field">
					<label>Main Heading</label>
					<input type="text" name="ep_heading" value="<?php echo esc_attr($s['heading']); ?>" />
				</div>
				<div class="wk-ep-field">
					<label>Body Text</label>
					<textarea name="ep_body"><?php echo esc_textarea($s['body']); ?></textarea>
				</div>
				<div class="wk-ep-field">
					<label>Coupon Code to Highlight (leave blank to hide)</label>
					<input type="text" name="ep_coupon_code" value="<?php echo esc_attr($s['coupon_code']); ?>" style="font-family:monospace;" />
				</div>
				<div class="wk-ep-field">
					<label>CTA Button Text</label>
					<input type="text" name="ep_cta_text" value="<?php echo esc_attr($s['cta_text']); ?>" />
				</div>
				<div class="wk-ep-field">
					<label>CTA Button URL (blank = shop page)</label>
					<input type="url" name="ep_cta_url" value="<?php echo esc_attr($s['cta_url']); ?>" placeholder="https://..." />
				</div>
				<div class="wk-ep-field">
					<label>Dismiss Link Text</label>
					<input type="text" name="ep_secondary_text" value="<?php echo esc_attr($s['secondary_text']); ?>" />
				</div>
			</div>
			<div class="wk-ep-box">
				<h2>📧 Email Capture (optional)</h2>
				<div class="wk-ep-toggle">
					<input type="checkbox" id="ep_collect_email" name="ep_collect_email" value="1" <?php checked($s['collect_email'],1); ?> />
					<label for="ep_collect_email">Show email capture form instead of CTA button</label>
				</div>
				<p style="font-size:12px;color:#666;margin:0 0 10px;">When enabled, shows an email field. Submissions are saved to your Newsletter subscribers list.</p>
				<div class="wk-ep-field">
					<label>Email Placeholder</label>
					<input type="text" name="ep_email_placeholder" value="<?php echo esc_attr($s['email_placeholder']); ?>" />
				</div>
				<div class="wk-ep-field">
					<label>Email Submit Button Text</label>
					<input type="text" name="ep_email_btn_text" value="<?php echo esc_attr($s['email_btn_text']); ?>" />
				</div>
			</div>

			<!-- Preview -->
			<div class="wk-ep-preview">
				<div class="wk-ep-prev-inner" id="wk-ep-prev" style="background:<?php echo esc_attr($s['bg_color']); ?>">
					<?php if ($s['image_url']) : ?><img src="<?php echo esc_url($s['image_url']); ?>" style="width:100%;border-radius:4px;margin-bottom:12px;" /><?php endif; ?>
					<p class="wk-ep-prev-eyebrow" style="color:<?php echo esc_attr($s['accent_color']); ?>"><?php echo esc_html($s['eyebrow']); ?></p>
					<h3 class="wk-ep-prev-heading"><?php echo esc_html($s['heading']); ?></h3>
					<p class="wk-ep-prev-body"><?php echo esc_html($s['body']); ?></p>
					<?php if ($s['coupon_code']) : ?><span class="wk-ep-prev-coupon" style="border-color:<?php echo esc_attr($s['accent_color']); ?>;color:<?php echo esc_attr($s['accent_color']); ?>"><?php echo esc_html($s['coupon_code']); ?></span><?php endif; ?>
					<a href="#" class="wk-ep-prev-btn" style="background:<?php echo esc_attr($s['accent_color']); ?>"><?php echo esc_html($s['cta_text']); ?></a>
				</div>
			</div>
		</div>
	</div>

	<input type="submit" class="button button-primary" value="Save Exit Popup Settings" style="background:#6B1E3E;border-color:#4a1228;padding:10px 24px;font-size:14px;" />
	</form>
	</div>
	<script>
	function updatePreview(){
		var bg = document.querySelector('[name=ep_bg_color]').value;
		var ac = document.querySelector('[name=ep_accent_color]').value;
		if(/^#[0-9a-f]{6}$/i.test(bg)) document.getElementById('wk-ep-prev').style.background = bg;
		document.querySelectorAll('.wk-ep-prev-eyebrow,.wk-ep-prev-coupon').forEach(function(el){ if(/^#[0-9a-f]{6}$/i.test(ac)) el.style.color = ac; });
		var btn = document.querySelector('.wk-ep-prev-btn'); if(btn&&/^#[0-9a-f]{6}$/i.test(ac)) btn.style.background = ac;
	}
	</script>
	<?php
}

// ── Frontend: render popup HTML ───────────────────────────────────────────────
function wk_exit_popup_html() {
	$s = wk_ep_get_settings();
	if (!$s['enabled']) return;

	// Page restriction check
	$pages = $s['pages'];
	$show  = false;
	if ($pages === 'all') $show = true;
	elseif ($pages === 'shop' && (is_shop() || is_product_category())) $show = true;
	elseif ($pages === 'product' && is_product()) $show = true;
	elseif ($pages === 'cart' && is_cart()) $show = true;
	elseif ($pages === 'not_checkout' && !is_checkout()) $show = true;
	if (!$show) return;

	$shop_url = class_exists('WooCommerce') ? wc_get_page_permalink('shop') : home_url('/shop');
	$cta_url  = $s['cta_url'] ?: $shop_url;
	?>
	<div id="wk-exit-popup" class="wk-exit-popup" role="dialog" aria-modal="true" aria-labelledby="wk-ep-title" hidden
	     style="--ep-accent:<?php echo esc_attr($s['accent_color']); ?>;">
		<div class="wk-exit-popup__overlay" id="wk-ep-overlay"></div>
		<div class="wk-exit-popup__modal" style="background:<?php echo esc_attr($s['bg_color']); ?>;border-top:3px solid <?php echo esc_attr($s['accent_color']); ?>">
			<button class="wk-exit-popup__close" id="wk-ep-close" aria-label="Close">&times;</button>
			<?php if ($s['image_url']) : ?>
			<div class="wk-exit-popup__image">
				<img src="<?php echo esc_url($s['image_url']); ?>" alt="" loading="lazy">
			</div>
			<?php endif; ?>
			<div class="wk-exit-popup__body">
				<?php if ($s['eyebrow']) : ?>
				<p class="wk-exit-popup__eyebrow" style="color:<?php echo esc_attr($s['accent_color']); ?>"><?php echo esc_html($s['eyebrow']); ?></p>
				<?php endif; ?>
				<h2 class="wk-exit-popup__heading" id="wk-ep-title"><?php echo esc_html($s['heading']); ?></h2>
				<?php if ($s['body']) : ?>
				<p class="wk-exit-popup__text"><?php echo esc_html($s['body']); ?></p>
				<?php endif; ?>
				<?php if ($s['coupon_code']) : ?>
				<div class="wk-exit-popup__coupon" style="border-color:<?php echo esc_attr($s['accent_color']); ?>">
					<span class="wk-exit-popup__coupon-label">Your Code:</span>
					<span class="wk-exit-popup__coupon-code" style="color:<?php echo esc_attr($s['accent_color']); ?>"><?php echo esc_html($s['coupon_code']); ?></span>
					<button class="wk-exit-popup__copy" data-code="<?php echo esc_attr($s['coupon_code']); ?>" aria-label="Copy coupon code">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
						Copy
					</button>
				</div>
				<?php endif; ?>
				<?php if ($s['collect_email']) : ?>
				<form class="wk-exit-popup__email-form" id="wk-ep-email-form">
					<?php wp_nonce_field('wk_newsletter','wk_ep_nl_nonce'); ?>
					<input type="email" name="ep_email" class="wk-exit-popup__email-input" placeholder="<?php echo esc_attr($s['email_placeholder']); ?>" required />
					<button type="submit" class="wk-exit-popup__btn" style="background:<?php echo esc_attr($s['accent_color']); ?>">
						<?php echo esc_html($s['email_btn_text']); ?>
					</button>
				</form>
				<?php else : ?>
				<a href="<?php echo esc_url($cta_url); ?>" class="wk-exit-popup__btn" style="background:<?php echo esc_attr($s['accent_color']); ?>">
					<?php echo esc_html($s['cta_text']); ?>
				</a>
				<?php endif; ?>
				<?php if ($s['secondary_text']) : ?>
				<button class="wk-exit-popup__dismiss" id="wk-ep-dismiss"><?php echo esc_html($s['secondary_text']); ?></button>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<script id="wk-exit-popup-cfg">
	window.wk_exit_popup_cfg = <?php echo json_encode([
		'enabled'         => '1',
		'desktop_trigger' => $s['desktop_trigger'],
		'mobile_trigger'  => $s['mobile_trigger'],
		'mobile_idle_sec' => (int)$s['mobile_idle_sec'],
		'delay_sec'       => (int)$s['delay_sec'],
		'show_once_hours' => (int)$s['show_once_hours'],
		'ajax'            => admin_url('admin-ajax.php'),
		'nl_nonce'        => wp_create_nonce('wk_newsletter'),
	]); ?>;
	</script>
	<?php
}
add_action('wp_footer', 'wk_exit_popup_html', 96);

// AJAX email capture for exit popup
add_action('wp_ajax_wk_ep_email',        'wk_ep_email_handler');
add_action('wp_ajax_nopriv_wk_ep_email', 'wk_ep_email_handler');
function wk_ep_email_handler() {
	check_ajax_referer('wk_newsletter','nonce');
	$email = sanitize_email($_POST['email']??'');
	if (!is_email($email)) { wp_send_json_error(['message'=>'Invalid email.']); return; }
	$subscribers = get_option('wk_newsletter_subscribers',[]);
	$existing    = array_column($subscribers,'email');
	if (!in_array($email,$existing)) {
		$subscribers[] = ['email'=>$email,'date'=>current_time('Y-m-d H:i:s'),'page'=>esc_url_raw(sanitize_url($_SERVER['HTTP_REFERER']??'')),'status'=>'active','source'=>'exit_popup'];
		update_option('wk_newsletter_subscribers',$subscribers);
	}
	wp_send_json_success(['message'=>'🎉 Your coupon code has been sent!']);
}
