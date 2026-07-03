<?php
/**
 * WhiteKurti — Back-in-Stock Alert System
 * Email signup on OOS products, admin panel, auto-notification
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Admin page ────────────────────────────────────────────────────────────
// Menu registration moved to inc/admin-hub.php

function wk_bis_get_alerts() {
	return get_option('wk_bis_alerts', []);
}

add_action('admin_init', function() {
	// Handle send notifications manually
	if (isset($_POST['wk_bis_send_nonce']) && wp_verify_nonce($_POST['wk_bis_send_nonce'],'wk_bis_send') && current_user_can('manage_options')) {
		$pid = absint($_POST['bis_product_id']??0);
		if ($pid) {
			$count = wk_bis_send_notifications($pid);
			add_action('admin_notices', function() use ($count) {
				echo '<div class="notice notice-success"><p>✅ Sent '.absint($count).' back-in-stock notifications.</p></div>';
			});
		}
	}
	// Handle delete alert
	if (isset($_GET['bis_delete']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'],'bis_delete')) {
		$id = sanitize_text_field($_GET['bis_delete']);
		$alerts = wk_bis_get_alerts();
		unset($alerts[$id]);
		update_option('wk_bis_alerts', $alerts);
	}
});

function wk_bis_admin_page() {
	$alerts  = wk_bis_get_alerts();
	// Group by product
	$by_product = [];
	foreach ($alerts as $key => $alert) {
		$pid = $alert['product_id'] ?? 0;
		$by_product[$pid][] = array_merge($alert, ['key'=>$key]);
	}
	arsort($by_product);
	?>
	<div class="wrap" style="max-width:1000px;">
	<h1>🔔 Back-in-Stock Alerts</h1>
	<p style="color:#666;">Customers who signed up to be notified when a product is back in stock. Use "Send Notifications" to email them when you restock.</p>

	<?php if (empty($by_product)) : ?>
	<div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:40px;text-align:center;color:#888;margin-top:20px;">
		<p style="font-size:16px;">No back-in-stock sign-ups yet.</p>
		<p>When an out-of-stock product shows the "Notify Me" button and a customer signs up, it'll appear here.</p>
	</div>
	<?php else : ?>
	<?php foreach ($by_product as $pid => $palerts) :
		$product = $pid ? wc_get_product($pid) : null;
		$pname   = $product ? $product->get_name() : 'Unknown Product (ID: '.$pid.')';
		$pstatus = $product ? ($product->is_in_stock() ? 'in_stock' : 'oos') : 'unknown';
		$purl    = $product ? get_edit_post_link($pid) : '';
	?>
	<div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;margin-bottom:20px;">
		<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #eee;flex-wrap:wrap;gap:10px;">
			<div>
				<h2 style="margin:0;font-size:16px;">
					<?php if ($purl) : ?><a href="<?php echo esc_url($purl); ?>"><?php endif; ?>
					<?php echo esc_html($pname); ?>
					<?php if ($purl) : ?></a><?php endif; ?>
				</h2>
				<span style="font-size:12px;padding:2px 8px;border-radius:3px;font-weight:600;<?php echo $pstatus==='in_stock'?'background:#f0fdf4;color:#166534;':'background:#fef2f2;color:#B91C1C;'; ?>">
					<?php echo $pstatus==='in_stock'?'✅ In Stock':'❌ Out of Stock'; ?>
				</span>
				<span style="font-size:12px;color:#888;margin-left:8px;"><?php echo count($palerts); ?> subscriber<?php echo count($palerts)!==1?'s':''; ?></span>
			</div>
			<?php if ($pstatus === 'in_stock') : ?>
			<form method="post" style="display:inline;">
				<?php wp_nonce_field('wk_bis_send','wk_bis_send_nonce'); ?>
				<input type="hidden" name="bis_product_id" value="<?php echo esc_attr( $pid ); ?>" />
				<button type="submit" class="button button-primary" style="background:#6B1E3E;border-color:#4a1228;"
					onclick="return confirm('Send notifications to all <?php echo count($palerts); ?> subscriber(s) for this product?')">
					📧 Send Notifications (<?php echo count($palerts); ?>)
				</button>
			</form>
			<?php endif; ?>
		</div>
		<table style="width:100%;border-collapse:collapse;font-size:13px;">
			<thead><tr style="background:#f9f9f9;">
				<th style="padding:8px;text-align:left;border-bottom:1px solid #eee;">Email</th>
				<th style="padding:8px;text-align:left;border-bottom:1px solid #eee;">Date Signed Up</th>
				<th style="padding:8px;text-align:left;border-bottom:1px solid #eee;">Status</th>
				<th style="padding:8px;text-align:center;border-bottom:1px solid #eee;">Actions</th>
			</tr></thead>
			<tbody>
			<?php foreach ($palerts as $alert) : ?>
			<tr>
				<td style="padding:8px;border-bottom:1px solid #f0f0f0;"><strong><?php echo esc_html($alert['email']); ?></strong></td>
				<td style="padding:8px;border-bottom:1px solid #f0f0f0;color:#666;"><?php echo esc_html($alert['date']??'—'); ?></td>
				<td style="padding:8px;border-bottom:1px solid #f0f0f0;">
					<?php if (!empty($alert['notified'])) : ?>
					<span style="color:#166534;font-weight:600;font-size:11px;">✓ Notified <?php echo esc_html($alert['notified_date']??''); ?></span>
					<?php else : ?>
					<span style="color:#888;font-size:11px;">Waiting</span>
					<?php endif; ?>
				</td>
				<td style="padding:8px;border-bottom:1px solid #f0f0f0;text-align:center;">
					<a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wk-back-in-stock&bis_delete='.urlencode($alert['key'])),'bis_delete')); ?>"
					   onclick="return confirm('Remove this subscriber?')" style="color:#d00;font-size:12px;">Remove</a>
				</td>
			</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endforeach; ?>
	<?php endif; ?>
	</div>
	<?php
}

// ── AJAX: sign up for back-in-stock alert ─────────────────────────────────
add_action('wp_ajax_wk_bis_signup',        'wk_ajax_bis_signup');
add_action('wp_ajax_nopriv_wk_bis_signup', 'wk_ajax_bis_signup');
function wk_ajax_bis_signup() {
	check_ajax_referer('wk_bis_nonce','nonce');
	$email = sanitize_email($_POST['email'] ?? '');
	$pid   = absint($_POST['product_id'] ?? 0);
	if (!is_email($email) || !$pid) { wp_send_json_error(['message'=>'Invalid data.']); return; }

	$alerts = wk_bis_get_alerts();
	// Check if already signed up
	foreach ($alerts as $alert) {
		if ($alert['email'] === $email && $alert['product_id'] === $pid) {
			wp_send_json_success(['message'=>"You're already on the list! We'll email you when it's back."]);
			return;
		}
	}
	$key = uniqid('bis_');
	$alerts[$key] = [
		'email'      => $email,
		'product_id' => $pid,
		'date'       => current_time('Y-m-d H:i:s'),
		'notified'   => false,
	];
	update_option('wk_bis_alerts', $alerts);

	// Confirm email to user
	$product = wc_get_product($pid);
	$pname   = $product ? $product->get_name() : 'this product';
	$brand   = get_bloginfo('name');
	wp_mail($email,
		"You're on the waiting list! — {$brand}",
		"Hi!\n\nYou've been added to the waiting list for: {$pname}\n\nWe'll email you as soon as it's back in stock.\n\n— The {$brand} Team"
	);
	wp_send_json_success(['message'=>"✅ We'll email you at {$email} when it's back in stock!"]);
}

// ── Send notifications when product comes back in stock ───────────────────
function wk_bis_send_notifications($pid) {
	$alerts  = wk_bis_get_alerts();
	$product = wc_get_product($pid);
	if (!$product) return 0;
	$pname   = $product->get_name();
	$purl    = get_permalink($pid);
	$pimg_id = $product->get_image_id();
	$brand   = get_bloginfo('name');
	$sent    = 0;
	foreach ($alerts as $key => &$alert) {
		if ($alert['product_id'] !== $pid) continue;
		if (!empty($alert['notified'])) continue;
		$email = $alert['email'];
		$subject = "🎉 It's back! {$pname} — {$brand}";
		$message = "Hi!\n\nGreat news — {$pname} is back in stock!\n\nShop now before it sells out again: {$purl}\n\n— The {$brand} Team";
		$headers = ['Content-Type: text/plain; charset=UTF-8'];
		if (wp_mail($email, $subject, $message, $headers)) {
			$alert['notified']      = true;
			$alert['notified_date'] = current_time('Y-m-d');
			$sent++;
		}
	}
	update_option('wk_bis_alerts', $alerts);
	return $sent;
}

// ── Auto-notify when WooCommerce stock changes from OOS to in-stock ────────
add_action('woocommerce_product_set_stock_status', function($product_id, $stock_status) {
	if ($stock_status !== 'instock') return;
	$alerts = wk_bis_get_alerts();
	$has_waitlist = false;
	foreach ($alerts as $a) {
		if ($a['product_id'] == $product_id && empty($a['notified'])) { $has_waitlist = true; break; }
	}
	if ($has_waitlist) {
		// Schedule a 5-minute delay (avoid spam if multiple saves)
		wp_schedule_single_event(time() + 300, 'wk_bis_auto_notify', [$product_id]);
	}
}, 10, 2);
add_action('wk_bis_auto_notify', 'wk_bis_send_notifications');

// ── Render "Notify Me" form on OOS products ────────────────────────────────
function wk_bis_form() {
	global $product;
	if (!$product || $product->is_in_stock()) return;
	wp_localize_script('wk-main','wk_bis_cfg',[
		'nonce'      => wp_create_nonce('wk_bis_nonce'),
		'ajax'       => admin_url('admin-ajax.php'),
		'product_id' => $product->get_id(),
	]);
	$logged_email = is_user_logged_in() ? wp_get_current_user()->user_email : '';
	?>
	<div class="wk-bis-form" id="wk-bis-form">
		<div class="wk-bis-form__header">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
			<span>Get notified when it's back</span>
		</div>
		<form class="wk-bis-form__row" id="wk-bis-submit-form">
			<input type="email" id="wk-bis-email" class="wk-bis-form__input" placeholder="Your email address" required
				value="<?php echo esc_attr($logged_email); ?>" />
			<button type="submit" class="wk-btn wk-btn--sm" id="wk-bis-btn">Notify Me</button>
		</form>
		<div class="wk-bis-form__result" id="wk-bis-result"></div>
	</div>
	<?php
}
add_action('woocommerce_single_product_summary', 'wk_bis_form', 35);
add_action('woocommerce_single_product_summary', function() {
	global $product;
	if ($product && !$product->is_in_stock()) {
		// Remove default OOS button text
		remove_action('woocommerce_single_product_summary','woocommerce_template_single_add_to_cart',30);
	}
}, 29);
