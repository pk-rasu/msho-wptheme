<?php
/**
 * WhiteKurti — PIN Code Delivery Estimator
 * Enhanced with admin zone control and delivery time rules
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Admin settings ────────────────────────────────────────────────────────────
// Menu registration moved to inc/admin-hub.php

function wk_dz_get_settings() {
	return wp_parse_args(get_option('wk_delivery_zones', []), [
		'enabled'          => 1,
		'free_delivery'    => 1,
		'delivery_days'    => '4–6',
		'express_days'     => '1–2',
		'express_pincodes' => "110001–110099\n400001–400099\n500001–500099\n600001–600099\n700001–700099\n560001–560099",
		'excluded_pincodes'=> '',
		'cod_available'    => 1,
		'cod_excluded'     => '',
		'delivery_msg'     => 'Estimated delivery by {date}',
		'express_msg'      => '⚡ Express delivery by {date}',
		'unavailable_msg'  => 'Delivery not available to this PIN code yet.',
		'free_threshold'   => 0,
	]);
}

add_action('admin_init', function(){
	if (!isset($_POST['wk_dz_nonce'])) return;
	if (!wp_verify_nonce($_POST['wk_dz_nonce'],'wk_dz_save')) return;
	if (!current_user_can('manage_options')) return;
	$s = [];
	$text_fields = ['delivery_days','express_days','express_pincodes','excluded_pincodes','cod_excluded','delivery_msg','express_msg','unavailable_msg'];
	foreach ($text_fields as $f) $s[$f] = sanitize_textarea_field($_POST['dz_'.$f]??'');
	$s['enabled']       = !empty($_POST['dz_enabled'])       ? 1 : 0;
	$s['free_delivery'] = !empty($_POST['dz_free_delivery']) ? 1 : 0;
	$s['cod_available'] = !empty($_POST['dz_cod_available']) ? 1 : 0;
	$s['free_threshold']= absint($_POST['dz_free_threshold']??0);
	update_option('wk_delivery_zones', $s);
});

function wk_dz_admin_page() {
	$s = wk_dz_get_settings();
	?>
	<div class="wrap" style="max-width:800px;">
	<h1>📍 Delivery Zones & PIN Code Settings</h1>
	<form method="post">
	<?php wp_nonce_field('wk_dz_save','wk_dz_nonce'); ?>
	<style>
	.wk-dz-card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;margin-bottom:18px;}
	.wk-dz-card h2{margin:0 0 14px;font-size:14px;border-bottom:1px solid #eee;padding-bottom:10px;}
	.wk-dz-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;}
	.wk-dz-field label{display:block;font-weight:600;font-size:11px;text-transform:uppercase;color:#555;margin-bottom:4px;}
	.wk-dz-field input[type=text],.wk-dz-field input[type=number],.wk-dz-field textarea{width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:4px;font-size:13px;}
	.wk-dz-field textarea{min-height:100px;resize:vertical;font-family:monospace;}
	.wk-dz-toggle{display:flex;align-items:center;gap:8px;margin-bottom:10px;}
	.wk-dz-toggle input{width:18px;height:18px;accent-color:#6B1E3E;}
	</style>

	<div class="wk-dz-card">
		<h2>⚙️ General Settings</h2>
		<div class="wk-dz-toggle">
			<input type="checkbox" id="dz_enabled" name="dz_enabled" value="1" <?php checked($s['enabled'],1); ?> />
			<label for="dz_enabled" style="font-weight:600;cursor:pointer;">Enable PIN code delivery checker on product pages</label>
		</div>
		<div class="wk-dz-toggle">
			<input type="checkbox" id="dz_free_delivery" name="dz_free_delivery" value="1" <?php checked($s['free_delivery'],1); ?> />
			<label for="dz_free_delivery" style="font-weight:600;cursor:pointer;">Show FREE delivery for all orders</label>
		</div>
		<div class="wk-dz-toggle">
			<input type="checkbox" id="dz_cod_available" name="dz_cod_available" value="1" <?php checked($s['cod_available'],1); ?> />
			<label for="dz_cod_available" style="font-weight:600;cursor:pointer;">COD (Cash on Delivery) available by default</label>
		</div>
		<div class="wk-dz-row">
			<div class="wk-dz-field">
				<label>Standard Delivery Days</label>
				<input type="text" name="dz_delivery_days" value="<?php echo esc_attr($s['delivery_days']); ?>" placeholder="4–6" />
			</div>
			<div class="wk-dz-field">
				<label>Express Delivery Days</label>
				<input type="text" name="dz_express_days" value="<?php echo esc_attr($s['express_days']); ?>" placeholder="1–2" />
			</div>
		</div>
	</div>

	<div class="wk-dz-card">
		<h2>⚡ Express Delivery PIN Codes</h2>
		<p style="font-size:12px;color:#888;margin:0 0 10px;">Enter PIN code prefixes or ranges (one per line) that qualify for express delivery. e.g. <code>110001–110099</code> or just <code>1100</code> to match all PINs starting with 1100.</p>
		<div class="wk-dz-field">
			<textarea name="dz_express_pincodes"><?php echo esc_textarea($s['express_pincodes']); ?></textarea>
		</div>
		<div class="wk-dz-field" style="margin-top:12px;">
			<label>Excluded PIN Codes (no delivery)</label>
			<textarea name="dz_excluded_pincodes" style="min-height:70px;"><?php echo esc_textarea($s['excluded_pincodes']); ?></textarea>
			<p style="font-size:11px;color:#888;margin:3px 0 0;">One per line. These PINs will show "delivery unavailable".</p>
		</div>
		<div class="wk-dz-field" style="margin-top:12px;">
			<label>COD Excluded PIN Codes</label>
			<textarea name="dz_cod_excluded" style="min-height:70px;"><?php echo esc_textarea($s['cod_excluded']); ?></textarea>
		</div>
	</div>

	<div class="wk-dz-card">
		<h2>💬 Message Templates</h2>
		<p style="font-size:12px;color:#888;margin:0 0 10px;">Use <code>{date}</code> for expected delivery date, <code>{days}</code> for days range.</p>
		<?php foreach ([
			['dz_delivery_msg', 'Standard Delivery Message', $s['delivery_msg']],
			['dz_express_msg',  'Express Delivery Message',  $s['express_msg']],
			['dz_unavailable_msg','Unavailable Message',     $s['unavailable_msg']],
		] as [$fname, $flabel, $fval]) : ?>
		<div class="wk-dz-field" style="margin-bottom:12px;">
			<label><?php echo $flabel; ?></label>
			<input type="text" name="<?php echo $fname; ?>" value="<?php echo esc_attr($fval); ?>" />
		</div>
		<?php endforeach; ?>
	</div>

	<input type="submit" class="button button-primary" value="Save Delivery Settings" style="background:#6B1E3E;border-color:#4a1228;padding:10px 24px;font-size:14px;" />
	</form>
	</div>
	<?php
}

// ── AJAX PIN code check handler ───────────────────────────────────────────────
add_action('wp_ajax_wk_check_pincode',        'wk_ajax_check_pincode');
add_action('wp_ajax_nopriv_wk_check_pincode', 'wk_ajax_check_pincode');

function wk_ajax_check_pincode() {
	check_ajax_referer('wk_pincode_check', 'nonce');
	$pin = preg_replace('/[^0-9]/', '', sanitize_text_field($_POST['pincode'] ?? ''));

	if (strlen($pin) !== 6) {
		wp_send_json_error(['message' => 'Please enter a valid 6-digit PIN code.']);
		return;
	}

	$s    = wk_dz_get_settings();
	$type = 'standard'; // standard | express | unavailable

	// Check excluded
	$excluded = array_filter(array_map('trim', explode("\n", $s['excluded_pincodes'])));
	foreach ($excluded as $exc) {
		if (strpos($pin, trim($exc)) === 0) {
			wp_send_json_error(['message' => $s['unavailable_msg']]);
			return;
		}
	}

	// Check express
	$express_list = array_filter(array_map('trim', explode("\n", $s['express_pincodes'])));
	foreach ($express_list as $expr) {
		$expr = trim($expr);
		if (!$expr) continue;
		if (strpos($expr, '–') !== false || strpos($expr, '-') !== false) {
			$sep   = strpos($expr,'–') !== false ? '–' : '-';
			[$from, $to] = explode($sep, $expr, 2);
			if ($pin >= trim($from) && $pin <= trim($to)) { $type = 'express'; break; }
		} elseif (strpos($pin, $expr) === 0) {
			$type = 'express'; break;
		}
	}

	// Calculate delivery date
	$days_range = $type === 'express' ? $s['express_days'] : $s['delivery_days'];
	$days_parts = preg_split('/[–\-–]/', $days_range);
	$min_days   = max(1, (int)trim($days_parts[0]));
	$max_days   = isset($days_parts[1]) ? (int)trim($days_parts[1]) : $min_days + 1;

	// Skip weekends for business days
	$date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
	$added = 0;
	while ($added < $max_days) {
		$date->modify('+1 day');
		if ($date->format('N') < 7) $added++;
	}
	$delivery_date  = $date->format('D, d M');

	// Check COD
	$cod = $s['cod_available'];
	$cod_exc = array_filter(array_map('trim', explode("\n", $s['cod_excluded'])));
	foreach ($cod_exc as $exc) {
		if (strpos($pin, $exc) === 0) { $cod = false; break; }
	}

	$template = $type === 'express' ? $s['express_msg'] : $s['delivery_msg'];
	$message  = str_replace(['{date}', '{days}'], [$delivery_date, $days_range], $template);

	wp_send_json_success([
		'type'     => $type,
		'message'  => $message,
		'free'     => (bool)$s['free_delivery'],
		'cod'      => $cod,
		'days'     => $days_range,
		'date'     => $delivery_date,
	]);
}

// ── Enqueue nonce for pincode check ──────────────────────────────────────────
add_action('wp_enqueue_scripts', function() {
	if (is_product()) {
		$s = wk_dz_get_settings();
		wp_localize_script('wk-main', 'wk_pincode_cfg', [
			'nonce'   => wp_create_nonce('wk_pincode_check'),
			'enabled' => $s['enabled'] ? '1' : '0',
			'ajax'    => admin_url('admin-ajax.php'),
		]);
	}
}, 20);
