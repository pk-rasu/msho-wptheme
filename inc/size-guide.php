<?php
/**
 * WhiteKurti — Size Guide Modal + Admin Manager
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Admin menu ───────────────────────────────────────────────────────────────
// Menu registration moved to inc/admin-hub.php

function wk_sg_get_charts() {
	return get_option('wk_size_charts', [
		[
			'id'      => 'tops',
			'name'    => 'Kurtas & Tops',
			'note'    => 'All measurements in inches. Model wears Size S.',
			'columns' => ['Size','Chest','Waist','Hip','Length','Sleeve'],
			'rows'    => [
				['XS','32–33','26–27','34–35','38','22'],
				['S', '34–35','28–29','36–37','40','23'],
				['M', '36–37','30–31','38–39','41','23.5'],
				['L', '38–40','32–34','40–42','42','24'],
				['XL','41–43','35–37','43–45','43','24.5'],
				['2XL','44–46','38–40','46–48','44','25'],
				['3XL','47–50','41–44','49–52','45','25.5'],
			],
		],
		[
			'id'      => 'bottoms',
			'name'    => 'Palazzo & Pants',
			'note'    => 'All measurements in inches. Elastic waistband adjusts ±2".',
			'columns' => ['Size','Waist','Hip','Inseam','Outseam'],
			'rows'    => [
				['XS','24–26','34–35','26','38'],
				['S', '26–28','36–37','26','39'],
				['M', '28–30','38–39','27','40'],
				['L', '30–32','40–42','27','41'],
				['XL','32–35','43–46','28','42'],
				['2XL','35–38','47–50','28','43'],
			],
		],
		[
			'id'      => 'dupatta',
			'name'    => 'Dupattas & Stoles',
			'note'    => 'Measurements in inches.',
			'columns' => ['Size','Length','Width'],
			'rows'    => [
				['Regular','96','42'],
				['Long','108','44'],
				['XL Stole','120','46'],
			],
		],
	]);
}

function wk_sg_admin_page() {
	$charts = wk_sg_get_charts();

	// Save handler
	if (isset($_POST['wk_sg_nonce']) && wp_verify_nonce($_POST['wk_sg_nonce'],'wk_sg_save') && current_user_can('manage_options')) {
		$new_charts = [];
		$ids = $_POST['sg_id'] ?? [];
		foreach ($ids as $i => $cid) {
			if (!$cid) continue;
			$cols_raw  = sanitize_text_field($_POST['sg_columns'][$i] ?? '');
			$cols      = array_filter(array_map('trim', explode(',', $cols_raw)));
			$rows_raw  = $_POST['sg_rows'][$i] ?? '';
			$rows      = [];
			foreach (explode("\n", $rows_raw) as $line) {
				$cells = array_map('sanitize_text_field', explode(',', trim($line)));
				if (count(array_filter($cells))) $rows[] = $cells;
			}
			$new_charts[] = [
				'id'      => sanitize_key($cid),
				'name'    => sanitize_text_field($_POST['sg_name'][$i] ?? ''),
				'note'    => sanitize_textarea_field($_POST['sg_note'][$i] ?? ''),
				'columns' => $cols,
				'rows'    => $rows,
			];
		}
		update_option('wk_size_charts', $new_charts);
		$charts = $new_charts;
		echo '<div class="notice notice-success is-dismissible"><p>✅ Size charts saved!</p></div>';
	}
	?>
	<div class="wrap" style="max-width:1000px;">
	<h1>📏 Size Guide Manager</h1>
	<p style="color:#666;">Manage size charts displayed in the product page size guide modal. Each chart appears as a tab.</p>

	<form method="post" id="wk-sg-form">
	<?php wp_nonce_field('wk_sg_save','wk_sg_nonce'); ?>
	<style>
	.wk-sg-chart { background:#fff; border:1px solid #ddd; border-radius:8px; padding:20px; margin-bottom:20px; }
	.wk-sg-chart h2 { margin:0 0 14px; display:flex; align-items:center; justify-content:space-between; }
	.wk-sg-row { display:grid; grid-template-columns:1fr 2fr; gap:16px; margin-bottom:14px; }
	.wk-sg-field label { display:block; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:#555; margin-bottom:4px; }
	.wk-sg-field input, .wk-sg-field textarea { width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:4px; font-size:13px; }
	.wk-sg-field textarea { min-height:120px; font-family:monospace; resize:vertical; }
	.wk-sg-preview-table { width:100%; border-collapse:collapse; font-size:12px; margin-top:8px; }
	.wk-sg-preview-table th { background:#f4f4f4; padding:6px 10px; text-align:left; border:1px solid #ddd; }
	.wk-sg-preview-table td { padding:5px 10px; border:1px solid #ddd; }
	</style>

	<div id="wk-sg-charts">
	<?php foreach ($charts as $idx => $chart) : ?>
	<div class="wk-sg-chart" id="wk-chart-<?php echo $idx; ?>">
		<h2>
			<span><?php echo esc_html($chart['name']); ?></span>
			<button type="button" class="button button-small wk-sg-remove-chart" data-idx="<?php echo $idx; ?>">Remove</button>
		</h2>
		<input type="hidden" name="sg_id[<?php echo $idx; ?>]" value="<?php echo esc_attr($chart['id']); ?>" />
		<div class="wk-sg-row">
			<div class="wk-sg-field">
				<label>Chart Name (tab label)</label>
				<input type="text" name="sg_name[<?php echo $idx; ?>]" value="<?php echo esc_attr($chart['name']); ?>" />
			</div>
			<div class="wk-sg-field">
				<label>Column Headers (comma-separated)</label>
				<input type="text" name="sg_columns[<?php echo $idx; ?>]" value="<?php echo esc_attr(implode(',',$chart['columns'])); ?>" placeholder="Size,Chest,Waist,Hip,Length" />
			</div>
		</div>
		<div class="wk-sg-field">
			<label>Note / Fitting Advice</label>
			<input type="text" name="sg_note[<?php echo $idx; ?>]" value="<?php echo esc_attr($chart['note']); ?>" />
		</div>
		<div class="wk-sg-field">
			<label>Rows (one row per line, values comma-separated)</label>
			<textarea name="sg_rows[<?php echo $idx; ?>]"><?php
				echo esc_textarea(implode("\n", array_map(function($r) { return implode(',', $r); }, $chart['rows'])));
			?></textarea>
			<p style="font-size:11px;color:#888;margin:4px 0 0;">e.g.: <code>S,34–35,28–29,36–37,40</code> — one row per line</p>
		</div>
		<details>
			<summary style="cursor:pointer;font-size:12px;color:#6B1E3E;font-weight:600;margin-top:8px;">Preview Table</summary>
			<table class="wk-sg-preview-table" style="margin-top:8px;">
				<thead><tr><?php foreach ($chart['columns'] as $col) echo '<th>'.esc_html($col).'</th>'; ?></tr></thead>
				<tbody><?php foreach ($chart['rows'] as $row) { echo '<tr>'; foreach ($row as $cell) echo '<td>'.esc_html($cell).'</td>'; echo '</tr>'; } ?></tbody>
			</table>
		</details>
	</div>
	<?php endforeach; ?>
	</div>

	<button type="button" class="button" id="wk-add-chart-btn" style="margin-bottom:20px;">+ Add Size Chart</button>

	<div style="display:flex;gap:12px;align-items:center;">
		<input type="submit" class="button button-primary" value="Save All Charts" style="background:#6B1E3E;border-color:#4a1228;padding:10px 24px;font-size:14px;" />
		<p style="margin:0;font-size:12px;color:#888;">Changes appear instantly on the website after saving.</p>
	</div>
	</form>
	</div>
	<script>
	jQuery(function($){
		var idx = <?php echo count($charts); ?>;
		$('#wk-add-chart-btn').on('click', function(){
			var html = '<div class="wk-sg-chart" id="wk-chart-'+idx+'">'
				+'<h2><span>New Chart</span><button type="button" class="button button-small wk-sg-remove-chart">Remove</button></h2>'
				+'<input type="hidden" name="sg_id['+idx+']" value="chart_'+idx+'" />'
				+'<div class="wk-sg-row">'
				+'<div class="wk-sg-field"><label>Chart Name</label><input type="text" name="sg_name['+idx+']" value="New Chart" /></div>'
				+'<div class="wk-sg-field"><label>Columns (comma-sep)</label><input type="text" name="sg_columns['+idx+']" value="Size,Chest,Waist,Hip,Length" /></div>'
				+'</div>'
				+'<div class="wk-sg-field"><label>Note</label><input type="text" name="sg_note['+idx+']" value="" /></div>'
				+'<div class="wk-sg-field"><label>Rows (one per line)</label><textarea name="sg_rows['+idx+']" style="min-height:80px;font-family:monospace;"></textarea></div>'
				+'</div>';
			$('#wk-sg-charts').append(html); idx++;
		});
		$(document).on('click', '.wk-sg-remove-chart', function(){
			if(confirm('Remove this chart?')) $(this).closest('.wk-sg-chart').remove();
		});
	});
	</script>
	<?php
}

// ── AJAX handler: get chart data ─────────────────────────────────────────────
add_action('wp_ajax_wk_get_size_charts',        'wk_ajax_get_size_charts');
add_action('wp_ajax_nopriv_wk_get_size_charts', 'wk_ajax_get_size_charts');
function wk_ajax_get_size_charts() {
	// Size chart data is public (non-sensitive) — no nonce required for read
	$charts = wk_sg_get_charts();
	wp_send_json_success($charts);
}

// ── Render size guide button + modal on product pages ────────────────────────
function wk_size_guide_modal_html() {
	if (!is_product()) return;
	$charts = wk_sg_get_charts();
	if (empty($charts)) return;
	?>
	<!-- Size Guide Modal -->
	<div id="wk-size-guide-modal" class="wk-modal" role="dialog" aria-modal="true" aria-labelledby="wk-sg-modal-title" hidden>
		<div class="wk-modal__overlay" id="wk-sg-overlay"></div>
		<div class="wk-modal__panel wk-sg-panel">
			<div class="wk-modal__header">
				<h2 class="wk-modal__title" id="wk-sg-modal-title">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 7l-9-4-9 4v14l9 4 9-4V7z"/><path d="M3 7l9 4 9-4"/><path d="M12 11v10"/></svg>
					Size Guide
				</h2>
				<button class="wk-modal__close" id="wk-sg-close" aria-label="Close size guide">&times;</button>
			</div>
			<div class="wk-sg-tabs">
				<?php foreach ($charts as $i => $chart) : ?>
				<button class="wk-sg-tab <?php echo $i===0?'is-active':''; ?>" data-chart="<?php echo esc_attr($chart['id']); ?>">
					<?php echo esc_html($chart['name']); ?>
				</button>
				<?php endforeach; ?>
			</div>
			<div class="wk-sg-content">
				<?php foreach ($charts as $i => $chart) : ?>
				<div class="wk-sg-chart-panel <?php echo $i===0?'is-active':''; ?>" data-chart-id="<?php echo esc_attr($chart['id']); ?>">
					<?php if ($chart['note']) : ?>
					<p class="wk-sg-note">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
						<?php echo esc_html($chart['note']); ?>
					</p>
					<?php endif; ?>
					<div class="wk-sg-table-wrap">
						<table class="wk-sg-table">
							<thead>
								<tr><?php foreach ($chart['columns'] as $col) : ?><th><?php echo esc_html($col); ?></th><?php endforeach; ?></tr>
							</thead>
							<tbody>
								<?php foreach ($chart['rows'] as $row) : ?>
								<tr><?php foreach ($row as $cell) : ?><td><?php echo esc_html($cell); ?></td><?php endforeach; ?></tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<div class="wk-sg-howto">
						<h4>How to Measure</h4>
						<div class="wk-sg-howto-grid">
							<div><strong>Chest/Bust</strong><br>Measure around the fullest part of your chest, keeping the tape parallel to the floor.</div>
							<div><strong>Waist</strong><br>Measure around your natural waistline, the narrowest part of your torso.</div>
							<div><strong>Hip</strong><br>Measure around the fullest part of your hips, about 8" below your waistline.</div>
							<div><strong>Length</strong><br>Measure from the highest point of the shoulder to the hem.</div>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php
}
add_action('wp_footer', 'wk_size_guide_modal_html', 90);

// ── Inject "Size Guide" button before add to cart ────────────────────────────
add_action('woocommerce_before_add_to_cart_button', function() {
	$charts = wk_sg_get_charts();
	if (empty($charts)) return;
	echo '<button type="button" class="wk-size-guide-btn" id="wk-open-size-guide" aria-haspopup="dialog" aria-controls="wk-size-guide-modal">';
	echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 7l-9-4-9 4v14l9 4 9-4V7z"/><path d="M3 7l9 4 9-4"/><path d="M12 11v10"/></svg>';
	echo ' Size Guide</button>';
});
