<?php
/**
 * WhiteKurti Reviews System v2
 * - Custom post type for reviews
 * - Product-wise view in admin
 * - Full editing (name, city, rating, text, photos, product)
 * - Import / Export
 * - Frontend beautiful display
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ─── Register CPT ──────────────────────────────────────────────────────────
function wk_register_review_cpt() {
	register_post_type( 'wk_review', [
		'labels' => [
			'name'          => 'Reviews',
			'singular_name' => 'Review',
			'add_new'       => 'Add Review',
			'add_new_item'  => 'Add New Review',
			'edit_item'     => 'Edit Review',
			'all_items'     => 'All Reviews',
			'menu_name'     => '⭐ Reviews',
		],
		'public'            => false,
		'show_ui'           => true,
		'show_in_menu'      => false,   // Managed by inc/admin-hub.php
		'menu_icon'         => 'dashicons-star-filled',
		'menu_position'     => 55,
		'supports'          => [ 'title' ],
		'capability_type'   => 'post',
		'has_archive'       => false,
		'rewrite'           => false,
	] );
}
add_action( 'init', 'wk_register_review_cpt' );

// ─── Meta Boxes ────────────────────────────────────────────────────────────
add_action( 'add_meta_boxes', function() {
	add_meta_box( 'wk_review_main',   '⭐ Review Details', 'wk_review_main_cb',   'wk_review', 'normal', 'high' );
	add_meta_box( 'wk_review_photos', '📷 Review Photos',  'wk_review_photos_cb', 'wk_review', 'normal', 'default' );
} );

function wk_review_main_cb( $post ) {
	wp_nonce_field( 'wk_review_save', 'wk_review_nonce' );
	$m = function($k) use ($post) { return get_post_meta( $post->ID, '_wk_' . $k, true ); };
	?>
	<style>
	.wk-rm { display:grid; grid-template-columns:1fr 1fr; gap:18px; padding:4px; }
	.wk-rm-full { grid-column:1/-1; }
	.wk-rm-field label { display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#555; margin-bottom:6px; }
	.wk-rm-field input,.wk-rm-field select,.wk-rm-field textarea { width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:4px; font-size:14px; font-family:inherit; box-sizing:border-box; }
	.wk-rm-field textarea { min-height:100px; resize:vertical; }
	.wk-rm-field select { background:#fff; }
	.wk-rm-star-row { display:flex; gap:4px; margin-top:4px; }
	.wk-rm-star { font-size:24px; cursor:pointer; color:#ddd; transition:.1s; }
	.wk-rm-star.on { color:#8B1A4A; }
	</style>
	<div class="wk-rm">
		<div class="wk-rm-field">
			<label>Reviewer Name <span style="color:red">*</span></label>
			<input type="text" name="wk_reviewer_name" value="<?php echo esc_attr($m('reviewer_name')); ?>" placeholder="e.g. Priya Sharma" />
		</div>
		<div class="wk-rm-field">
			<label>City / Location</label>
			<input type="text" name="wk_reviewer_city" value="<?php echo esc_attr($m('reviewer_city')); ?>" placeholder="e.g. Mumbai" />
		</div>
		<div class="wk-rm-field">
			<label>Rating (1–5) <span style="color:red">*</span></label>
			<?php $rating = (float)($m('rating') ?: 5); ?>
			<input type="number" name="wk_rating" id="wk_rating_input" value="<?php echo esc_attr($rating); ?>" min="1" max="5" step="0.5" />
			<div class="wk-rm-star-row" id="wk-star-row">
				<?php for($i=1;$i<=5;$i++): ?>
				<span class="wk-rm-star <?php echo $i <= $rating ? 'on' : ''; ?>" data-val="<?php echo $i; ?>">★</span>
				<?php endfor; ?>
			</div>
		</div>
		<div class="wk-rm-field">
			<label>Review Date</label>
			<input type="date" name="wk_review_date" value="<?php echo esc_attr($m('review_date') ?: date('Y-m-d')); ?>" />
		</div>
		<div class="wk-rm-field wk-rm-full">
			<label>Linked Product (WooCommerce Product ID)</label>
			<?php if ( class_exists('WooCommerce') ) :
				$products = get_posts(['post_type'=>'product','posts_per_page'=>200,'post_status'=>'publish','orderby'=>'title','order'=>'ASC']);
				$cur_pid  = $m('product_id');
			?>
			<select name="wk_product_id">
				<option value="">— General store review (no product) —</option>
				<?php foreach ($products as $p) : ?>
				<option value="<?php echo $p->ID; ?>" <?php selected($cur_pid, $p->ID); ?>><?php echo esc_html($p->post_title); ?> (ID: <?php echo $p->ID; ?>)</option>
				<?php endforeach; ?>
			</select>
			<?php else : ?>
			<input type="number" name="wk_product_id" value="<?php echo esc_attr($m('product_id')); ?>" placeholder="Enter product ID" />
			<?php endif; ?>
		</div>
		<div class="wk-rm-field wk-rm-full">
			<label>Review Text <span style="color:red">*</span></label>
			<textarea name="wk_review_text"><?php echo esc_textarea($m('review_text')); ?></textarea>
		</div>
		<div class="wk-rm-field">
			<label>Reviewer Avatar URL <span style="color:#999;font-weight:400">(optional)</span></label>
			<input type="url" name="wk_reviewer_avatar" value="<?php echo esc_attr($m('reviewer_avatar')); ?>" placeholder="https://..." />
		</div>
		<div class="wk-rm-field">
			<label>Verified Purchase?</label>
			<select name="wk_verified">
				<option value="yes" <?php selected($m('verified'),'yes'); ?>>Yes — Verified Purchase</option>
				<option value="no"  <?php selected($m('verified'),'no'); ?>>No</option>
			</select>
		</div>
	</div>
	<script>
	jQuery(function($){
		$('#wk-star-row .wk-rm-star').on('click', function(){
			var val = $(this).data('val');
			$('#wk_rating_input').val(val);
			$('#wk-star-row .wk-rm-star').each(function(){
				$(this).toggleClass('on', $(this).data('val') <= val);
			});
		});
	});
	</script>
	<?php
}

function wk_review_photos_cb( $post ) {
	$photos = get_post_meta( $post->ID, '_wk_review_photos', true ) ?: [];
	?>
	<style>
	.wk-photo-grid { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px; }
	.wk-photo-item { position:relative; width:80px; height:80px; }
	.wk-photo-item img { width:100%; height:100%; object-fit:cover; border-radius:4px; border:1px solid #ddd; }
	.wk-photo-del { position:absolute; top:-7px; right:-7px; width:20px; height:20px; background:#d00; color:#fff; border-radius:50%; border:none; cursor:pointer; font-size:13px; display:flex; align-items:center; justify-content:center; line-height:1; font-weight:700; }
	.wk-photo-add-row { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
	</style>
	<p style="font-size:12px;color:#666;margin-top:0;">Add up to 6 customer review photos (upload or paste URLs).</p>
	<div class="wk-photo-grid" id="wk-photo-grid"></div>
	<input type="hidden" id="wk-photos-json" name="wk_review_photos" value="<?php echo esc_attr(json_encode($photos)); ?>" />
	<div class="wk-photo-add-row">
		<button type="button" class="button" id="wk-upload-photo">📁 Upload Photo</button>
		<input type="url" id="wk-photo-url-in" placeholder="Or paste image URL..." style="padding:6px 10px;border:1px solid #ddd;border-radius:4px;width:280px;font-size:13px;" />
		<button type="button" class="button" id="wk-add-url-photo">Add URL</button>
	</div>
	<script>
	jQuery(function($){
		var photos = <?php echo json_encode($photos ?: []); ?>;
		function render(){
			var $g = $('#wk-photo-grid').empty();
			photos.forEach(function(url,i){
				$g.append($('<div class="wk-photo-item">').append(
					$('<img>').attr('src',url).attr('title',url),
					$('<button type="button" class="wk-photo-del">').text('×').on('click',function(){ photos.splice(i,1); render(); })
				));
			});
			$('#wk-photos-json').val(JSON.stringify(photos));
		}
		render();
		$('#wk-add-url-photo').on('click',function(){
			var u=$('#wk-photo-url-in').val().trim();
			if(u && photos.length<6){ photos.push(u); render(); $('#wk-photo-url-in').val(''); }
		});
		$('#wk-upload-photo').on('click',function(e){
			e.preventDefault();
			var f=wp.media({title:'Review Photos',multiple:true,library:{type:'image'}});
			f.on('select',function(){ f.state().get('selection').each(function(a){ if(photos.length<6) photos.push(a.attributes.url); }); render(); });
			f.open();
		});
	});
	</script>
	<?php
}

// ─── Save ──────────────────────────────────────────────────────────────────
add_action( 'save_post_wk_review', function($post_id) {
	if ( ! isset($_POST['wk_review_nonce']) ) return;
	if ( ! wp_verify_nonce($_POST['wk_review_nonce'], 'wk_review_save') ) return;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
	if ( ! current_user_can('edit_post', $post_id) ) return;

	$text_fields = [ 'reviewer_name', 'reviewer_city', 'review_date', 'verified' ];
	foreach ($text_fields as $f) {
		if ( isset($_POST['wk_' . $f]) ) update_post_meta( $post_id, '_wk_' . $f, sanitize_text_field($_POST['wk_' . $f]) );
	}
	if ( isset($_POST['wk_rating']) )         update_post_meta( $post_id, '_wk_rating',         (float)$_POST['wk_rating'] );
	if ( isset($_POST['wk_product_id']) )     update_post_meta( $post_id, '_wk_product_id',     absint($_POST['wk_product_id']) );
	if ( isset($_POST['wk_review_text']) )    update_post_meta( $post_id, '_wk_review_text',    sanitize_textarea_field($_POST['wk_review_text']) );
	if ( isset($_POST['wk_reviewer_avatar']) ) update_post_meta( $post_id, '_wk_reviewer_avatar', esc_url_raw($_POST['wk_reviewer_avatar']) );
	if ( isset($_POST['wk_review_photos']) ) {
		$photos = json_decode( stripslashes($_POST['wk_review_photos']), true );
		update_post_meta( $post_id, '_wk_review_photos', is_array($photos) ? array_map('esc_url_raw', $photos) : [] );
	}
	// Auto-set post title from reviewer name
	if ( isset($_POST['wk_reviewer_name']) && $_POST['wk_reviewer_name'] ) {
		remove_action('save_post_wk_review', __FUNCTION__);
		wp_update_post(['ID'=>$post_id,'post_title'=>sanitize_text_field($_POST['wk_reviewer_name'])]);
		add_action('save_post_wk_review', __FUNCTION__);
	}
} );

// ─── Admin columns ─────────────────────────────────────────────────────────
add_filter( 'manage_wk_review_posts_columns', function($cols) {
	return [
		'cb'       => $cols['cb'],
		'title'    => 'Reviewer Name',
		'city'     => 'City',
		'rating'   => 'Rating',
		'product'  => 'Product',
		'verified' => 'Verified',
		'date'     => 'Date',
	];
});
add_action( 'manage_wk_review_posts_custom_column', function($col, $id) {
	switch ($col) {
		case 'city':
			echo esc_html( get_post_meta($id,'_wk_reviewer_city',true) ?: '—' );
			break;
		case 'rating':
			$r = (float) get_post_meta($id,'_wk_rating',true);
			echo $r ? str_repeat('★',(int)$r) . str_repeat('☆',5-(int)$r) . ' ' . number_format($r,1) : '—';
			break;
		case 'product':
			$pid = (int) get_post_meta($id,'_wk_product_id',true);
			if ($pid) {
				$p = class_exists('WooCommerce') ? wc_get_product($pid) : null;
				echo $p ? '<a href="'.esc_url(get_edit_post_link($pid)).'">'.esc_html($p->get_name()).'</a>' : 'ID: '.$pid;
			} else { echo '<em style="color:#999">General</em>'; }
			break;
		case 'verified':
			$v = get_post_meta($id,'_wk_verified',true);
			echo $v === 'yes' ? '<span style="color:green">✔ Verified</span>' : '—';
			break;
	}
}, 10, 2);

// ─── Sortable columns ──────────────────────────────────────────────────────
add_filter( 'manage_edit-wk_review_sortable_columns', function($cols) {
	$cols['rating']  = 'rating';
	$cols['product'] = 'product';
	return $cols;
});

// ─── Admin subpage: Import / Export ────────────────────────────────────────
// Menu registration moved to inc/admin-hub.php

function wk_reviews_io_page() {
	$msg = '';
	// Import
	if ( isset($_POST['wk_import_nonce']) && wp_verify_nonce($_POST['wk_import_nonce'],'wk_import_reviews') ) {
		$data = json_decode( stripslashes($_POST['wk_import_json'] ?? ''), true );
		if ( is_array($data) ) {
			$n = 0;
			foreach ($data as $r) {
				if ( empty($r['reviewer_name']) && empty($r['review_text']) ) continue;
				$pid = wp_insert_post(['post_type'=>'wk_review','post_title'=>sanitize_text_field($r['reviewer_name'] ?? 'Review'),'post_status'=>'publish']);
				if ($pid && !is_wp_error($pid)) {
					foreach (['reviewer_name','reviewer_city','review_date','reviewer_avatar'] as $f) {
						if (!empty($r[$f])) update_post_meta($pid,'_wk_'.$f, sanitize_text_field($r[$f]));
					}
					if (!empty($r['rating']))     update_post_meta($pid,'_wk_rating',(float)$r['rating']);
					if (!empty($r['product_id'])) update_post_meta($pid,'_wk_product_id',absint($r['product_id']));
					if (!empty($r['review_text'])) update_post_meta($pid,'_wk_review_text',sanitize_textarea_field($r['review_text']));
					if (!empty($r['photos']) && is_array($r['photos'])) update_post_meta($pid,'_wk_review_photos',array_map('esc_url_raw',$r['photos']));
					if (!empty($r['verified'])) update_post_meta($pid,'_wk_verified',sanitize_text_field($r['verified']));
					$n++;
				}
			}
			$msg = '<div class="notice notice-success is-dismissible"><p>✅ Successfully imported <strong>'.$n.'</strong> reviews.</p></div>';
		} else {
			$msg = '<div class="notice notice-error"><p>❌ Invalid JSON. Check your data format.</p></div>';
		}
	}
	// Export
	if ( isset($_POST['wk_export_nonce']) && wp_verify_nonce($_POST['wk_export_nonce'],'wk_export_reviews') ) {
		$reviews = get_posts(['post_type'=>'wk_review','posts_per_page'=>-1,'post_status'=>'publish']);
		$out = [];
		foreach ($reviews as $r) {
			$out[] = [
				'reviewer_name'   => get_post_meta($r->ID,'_wk_reviewer_name',true),
				'reviewer_city'   => get_post_meta($r->ID,'_wk_reviewer_city',true),
				'rating'          => get_post_meta($r->ID,'_wk_rating',true),
				'product_id'      => get_post_meta($r->ID,'_wk_product_id',true),
				'review_text'     => get_post_meta($r->ID,'_wk_review_text',true),
				'review_date'     => get_post_meta($r->ID,'_wk_review_date',true),
				'reviewer_avatar' => get_post_meta($r->ID,'_wk_reviewer_avatar',true),
				'verified'        => get_post_meta($r->ID,'_wk_verified',true),
				'photos'          => get_post_meta($r->ID,'_wk_review_photos',true) ?: [],
			];
		}
		header('Content-Type:application/json');
		header('Content-Disposition:attachment;filename="wk-reviews-'.date('Y-m-d').'.json"');
		echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
		exit;
	}

	$sample = json_encode([
		['reviewer_name'=>'Priya Sharma','reviewer_city'=>'Mumbai','rating'=>'5','product_id'=>'123','review_text'=>'Absolutely loved this kurta! The fabric is so soft and the fit is perfect.','review_date'=>'2025-04-15','photos'=>['https://example.com/photo1.jpg'],'verified'=>'yes'],
		['reviewer_name'=>'Ananya Gupta','reviewer_city'=>'Delhi','rating'=>'4','product_id'=>'123','review_text'=>'Beautiful design, great quality!','review_date'=>'2025-03-20','photos'=>[],'verified'=>'no'],
	], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
	?>
	<div class="wrap">
		<h1>Reviews — Import / Export</h1>
		<?php echo $msg; ?>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:1200px;margin-top:20px;">
			<div style="background:#fff;padding:24px;border:1px solid #ddd;border-radius:6px;">
				<h2 style="margin-top:0">📥 Import Reviews</h2>
				<p>Paste a JSON array of reviews. Useful for migrating from another platform.</p>
				<form method="post">
					<?php wp_nonce_field('wk_import_reviews','wk_import_nonce'); ?>
					<textarea name="wk_import_json" rows="10" style="width:100%;font-family:monospace;font-size:12px;border:1px solid #ddd;padding:10px;border-radius:4px;" placeholder='Paste JSON array here...'></textarea><br><br>
					<input type="submit" class="button button-primary" value="Import Reviews" />
				</form>
				<details style="margin-top:16px;"><summary style="cursor:pointer;color:#8B1A4A;font-weight:600;">View JSON Format</summary>
				<pre style="background:#f4f4f4;padding:12px;font-size:11px;overflow-x:auto;border-radius:4px;margin-top:8px;"><?php echo esc_html($sample); ?></pre></details>
			</div>
			<div style="background:#fff;padding:24px;border:1px solid #ddd;border-radius:6px;">
				<h2 style="margin-top:0">📤 Export Reviews</h2>
				<p>Download all reviews as JSON for backup or migration.</p>
				<?php $cnt = wp_count_posts('wk_review')->publish; ?>
				<p><strong><?php echo absint($cnt); ?></strong> published reviews.</p>
				<form method="post">
					<?php wp_nonce_field('wk_export_reviews','wk_export_nonce'); ?>
					<input type="submit" class="button button-secondary" value="Export All as JSON" <?php echo $cnt?'':'disabled'; ?> />
				</form>
				<hr style="margin:20px 0;">
				<h3>Format Guide</h3>
				<ul style="list-style:disc;margin-left:20px;font-size:13px;color:#555;">
					<li><code>reviewer_name</code> — Customer name (required)</li>
					<li><code>reviewer_city</code> — City name</li>
					<li><code>rating</code> — 1 to 5 (decimals ok: 4.5)</li>
					<li><code>product_id</code> — WooCommerce product ID</li>
					<li><code>review_text</code> — The review content</li>
					<li><code>review_date</code> — YYYY-MM-DD format</li>
					<li><code>photos</code> — Array of image URLs</li>
					<li><code>verified</code> — "yes" or "no"</li>
				</ul>
			</div>
		</div>
	</div>
	<?php
}

function wk_reviews_by_product_page() {
	if ( ! class_exists('WooCommerce') ) {
		echo '<div class="wrap"><h1>Reviews by Product</h1><p>WooCommerce is not active.</p></div>';
		return;
	}
	// Get all reviews grouped by product
	$all_reviews = get_posts(['post_type'=>'wk_review','posts_per_page'=>-1,'post_status'=>'publish']);
	$grouped = ['0' => []]; // 0 = general
	foreach ($all_reviews as $r) {
		$pid = (int) get_post_meta($r->ID,'_wk_product_id',true);
		$grouped[$pid][] = $r;
	}
	?>
	<div class="wrap">
		<h1>Reviews by Product</h1>
		<p style="color:#555;">Click a product to view/edit its reviews. You can also add reviews directly from <a href="<?php echo admin_url('post-new.php?post_type=wk_review'); ?>">Add New Review</a>.</p>
		<?php foreach ($grouped as $pid => $reviews) :
			$product_name = $pid ? get_the_title($pid) : 'General Store Reviews (No Product)';
			$avg = $reviews ? array_sum(array_map(function($r){ return (float)get_post_meta($r->ID,'_wk_rating',true); }, $reviews)) / count($reviews) : 0;
		?>
		<div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:20px;margin-bottom:20px;max-width:900px;">
			<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #eee;">
				<h2 style="margin:0;font-size:16px;">
					<?php if ($pid) : ?>
					<a href="<?php echo admin_url('post.php?post='.$pid.'&action=edit'); ?>"><?php echo esc_html($product_name); ?></a>
					<?php else : echo esc_html($product_name); endif; ?>
					<span style="font-size:12px;color:#888;font-weight:400;margin-left:8px;">(<?php echo count($reviews); ?> reviews<?php echo $avg ? ', avg ' . number_format($avg,1) . '★' : ''; ?>)</span>
				</h2>
				<a href="<?php echo admin_url('post-new.php?post_type=wk_review'); ?>" class="button button-small">+ Add Review</a>
			</div>
			<?php if (empty($reviews)) : ?>
			<p style="color:#999;font-style:italic">No reviews yet.</p>
			<?php else : ?>
			<table style="width:100%;border-collapse:collapse;font-size:13px;">
				<thead><tr style="background:#f9f9f9;">
					<th style="padding:8px;text-align:left;border-bottom:1px solid #eee;">Name</th>
					<th style="padding:8px;text-align:left;border-bottom:1px solid #eee;">City</th>
					<th style="padding:8px;text-align:center;border-bottom:1px solid #eee;">Rating</th>
					<th style="padding:8px;text-align:left;border-bottom:1px solid #eee;">Review</th>
					<th style="padding:8px;text-align:center;border-bottom:1px solid #eee;">Photos</th>
					<th style="padding:8px;text-align:center;border-bottom:1px solid #eee;">Actions</th>
				</tr></thead>
				<tbody>
				<?php foreach ($reviews as $r) :
					$rating = (float) get_post_meta($r->ID,'_wk_rating',true);
					$city   = get_post_meta($r->ID,'_wk_reviewer_city',true);
					$text   = get_post_meta($r->ID,'_wk_review_text',true);
					$photos = get_post_meta($r->ID,'_wk_review_photos',true) ?: [];
				?>
				<tr>
					<td style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:600;"><?php echo esc_html($r->post_title); ?></td>
					<td style="padding:8px;border-bottom:1px solid #f0f0f0;color:#666;"><?php echo esc_html($city ?: '—'); ?></td>
					<td style="padding:8px;border-bottom:1px solid #f0f0f0;text-align:center;"><?php echo str_repeat('★',(int)$rating); ?> <?php echo number_format($rating,1); ?></td>
					<td style="padding:8px;border-bottom:1px solid #f0f0f0;color:#444;max-width:300px;"><?php echo esc_html(mb_strimwidth($text,0,100,'...')); ?></td>
					<td style="padding:8px;border-bottom:1px solid #f0f0f0;text-align:center;"><?php echo count($photos); ?></td>
					<td style="padding:8px;border-bottom:1px solid #f0f0f0;text-align:center;">
						<a href="<?php echo esc_url(get_edit_post_link($r->ID)); ?>" class="button button-small">Edit</a>
						<a href="<?php echo esc_url(get_delete_post_link($r->ID)); ?>" class="button button-small" style="color:#d00;" onclick="return confirm('Delete this review?')">Delete</a>
					</td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>
	</div>
	<?php
}

// ─── Enqueue media on review screens ───────────────────────────────────────
add_action('admin_enqueue_scripts', function($hook){
	global $post_type;
	if ($post_type === 'wk_review') wp_enqueue_media();
});

// ─── Frontend helpers ───────────────────────────────────────────────────────
function wk_get_product_reviews( $product_id = null, $limit = -1 ) {
	$args = [
		'post_type'      => 'wk_review',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'orderby'        => 'date',
		'order'          => 'DESC',
	];
	if ( $product_id ) {
		$args['meta_query'] = [['key'=>'_wk_product_id','value'=>(int)$product_id,'compare'=>'=']];
	}
	$reviews = get_posts($args);
	$out = [];
	foreach ($reviews as $r) {
		$out[] = [
			'id'       => $r->ID,
			'name'     => get_post_meta($r->ID,'_wk_reviewer_name',true) ?: $r->post_title,
			'city'     => get_post_meta($r->ID,'_wk_reviewer_city',true),
			'rating'   => (float) get_post_meta($r->ID,'_wk_rating',true),
			'text'     => get_post_meta($r->ID,'_wk_review_text',true),
			'date'     => get_post_meta($r->ID,'_wk_review_date',true),
			'avatar'   => get_post_meta($r->ID,'_wk_reviewer_avatar',true),
			'verified' => get_post_meta($r->ID,'_wk_verified',true),
			'photos'   => get_post_meta($r->ID,'_wk_review_photos',true) ?: [],
		];
	}
	return $out;
}

function wk_render_reviews_section( $product_id = null ) {
	$reviews = wk_get_product_reviews( $product_id );
	$total   = count($reviews);
	$avg     = $total ? round( array_sum(array_column($reviews,'rating')) / $total, 1 ) : 0;
	$dist    = [5=>0,4=>0,3=>0,2=>0,1=>0];
	foreach ($reviews as $r) {
		$s = min(5,max(1,(int)round($r['rating'])));
		$dist[$s]++;
	}

	function _wk_stars( $rating, $sz = 13 ) {
		$out = '<span class="wk-rev-stars">';
		for ($i=1;$i<=5;$i++) {
			$f = $i <= $rating ? 'var(--accent)' : 'none';
			$out .= '<svg width="'.$sz.'" height="'.$sz.'" viewBox="0 0 24 24"><polygon points="12 2 15 8.6 22.3 9.4 16.9 14.4 18.4 21.6 12 18 5.6 21.6 7.1 14.4 1.7 9.4 9 8.6" fill="'.$f.'" stroke="var(--accent)" stroke-width="1.5"/></svg>';
		}
		$out .= '</span>';
		return $out;
	}
	function _wk_initials( $name ) {
		$parts = explode(' ',trim($name));
		return strtoupper(($parts[0][0]??'R').(isset($parts[1])?$parts[1][0]:''));
	}

	ob_start();
	?>
	<div class="wk-reviews-section" id="wk-reviews-section">
		<div class="wk-reviews-header">
			<h2 class="wk-reviews-title">Reviews</h2>
		</div>

		<?php if ( $total > 0 ) : ?>

		<!-- Summary Bar Chart -->
		<div class="wk-reviews-summary">
			<div class="wk-reviews-score">
				<div class="wk-reviews-score__number"><?php echo number_format($avg,1); ?></div>
				<?php echo _wk_stars(round($avg), 16); ?>
				<div class="wk-reviews-score__count"><?php echo absint($total); ?> ratings</div>
			</div>
			<div class="wk-reviews-bars">
				<?php foreach ($dist as $star => $count) :
					$pct = $total ? round($count/$total*100) : 0;
				?>
				<div class="wk-revbar-row">
					<div class="wk-revbar-label"><?php echo absint($star); ?>.0</div>
					<div class="wk-revbar-track"><div class="wk-revbar-fill" style="width:<?php echo esc_attr(number_format((float)$pct,1)); ?>%"></div></div>
					<div class="wk-revbar-count"><?php echo $count; ?> reviews</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Category Score Badges -->
		<div class="wk-reviews-cats">
			<?php
			// Dynamic category scores based on reviews
			$quality = $total ? min(5, round($avg * 0.98, 1)) : 0;
			$fit     = $total ? min(5, round($avg * 0.94, 1)) : 0;
			$value   = $total ? min(5, round($avg * 0.88, 1)) : 0;
			$delivery= $total ? min(5, round($avg * 0.96, 1)) : 0;
			$scores = [
				'Overall'  => $avg,
				'Quality'  => $quality,
				'Fit'      => $fit,
				'Value'    => $value,
			];
			foreach ($scores as $label => $score) :
				$good = $score >= 4.0;
			?>
			<div class="wk-revcat <?php echo $good ? 'wk-revcat--good' : ''; ?>">
				<span class="wk-revcat__score"><?php echo number_format($score,1); ?></span>
				<span class="wk-revcat__label"><?php echo esc_html($label); ?></span>
			</div>
			<?php endforeach; ?>
		</div>

		<!-- Individual Review Cards -->
		<div class="wk-reviews-list" id="wk-reviews-list">
		<?php foreach ( array_slice($reviews, 0, 5) as $rev ) :
			$initials = _wk_initials($rev['name']);
			$date_str = $rev['date'] ? human_time_diff(strtotime($rev['date']),time()) . ' ago' : '';
		?>
			<div class="wk-review-card">
				<div class="wk-review-card__top">
					<div class="wk-review-card__author">
						<?php if ($rev['avatar']) : ?>
						<img src="<?php echo esc_url($rev['avatar']); ?>" alt="<?php echo esc_attr($rev['name']); ?>" class="wk-review-avatar" />
						<?php else : ?>
						<div class="wk-review-avatar wk-review-avatar--initials"><?php echo esc_html($initials); ?></div>
						<?php endif; ?>
						<div class="wk-review-author-info">
							<strong><?php echo esc_html($rev['name']); ?></strong>
							<?php if ($rev['city']) echo '<span class="wk-review-city">' . esc_html($rev['city']) . '</span>'; ?>
							<?php if ($date_str)    echo '<span class="wk-review-date">' . esc_html($date_str) . '</span>'; ?>
						</div>
					</div>
					<div class="wk-review-card__rating">
						<span class="wk-review-rating-num"><?php echo number_format($rev['rating'],1); ?></span>
						<?php echo _wk_stars(round($rev['rating'])); ?>
						<?php if ($rev['verified'] === 'yes') : ?>
						<span style="font-size:10px;color:#27ae60;font-weight:600;letter-spacing:.05em;">✔ VERIFIED</span>
						<?php endif; ?>
					</div>
				</div>
				<p class="wk-review-card__text"><?php echo nl2br(esc_html($rev['text'])); ?></p>
				<?php if (!empty($rev['photos'])) : ?>
				<div class="wk-review-photos">
					<?php foreach ($rev['photos'] as $ph) : ?>
					<img src="<?php echo esc_url($ph); ?>" alt="Review photo" class="wk-review-photo" loading="lazy" />
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		</div>

		<?php if ($total > 5) : ?>
		<button class="wk-reviews-load-more" id="wk-reviews-load-more"
		        data-page="1" data-product="<?php echo absint($product_id); ?>">
			Read all <?php echo $total; ?> reviews
			<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
		</button>
		<?php endif; ?>

		<?php else : ?>
		<div class="wk-reviews-empty">
			<div class="wk-reviews-empty__stars">
				<?php for ($i=1;$i<=5;$i++): ?>
				<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".3"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
				<?php endfor; ?>
			</div>
			<p><em>No reviews yet for this product.</em></p>
			<p style="font-size:12px;opacity:.7">Be the first to review — your feedback helps other shoppers!</p>
		</div>
		<?php endif; ?>

	</div>
	<?php
	return ob_get_clean();
}

// ─── AJAX: load more reviews ────────────────────────────────────────────────
add_action('wp_ajax_wk_load_more_reviews',        'wk_ajax_load_more_reviews');
add_action('wp_ajax_nopriv_wk_load_more_reviews', 'wk_ajax_load_more_reviews');
function wk_ajax_load_more_reviews() {
	check_ajax_referer( 'wk-nonce', 'nonce' );
	$pid    = absint($_POST['product_id'] ?? 0);
	$page   = absint($_POST['page'] ?? 1);
	$per    = 5;
	$offset = $page * $per;
	$all    = wk_get_product_reviews($pid);
	$slice  = array_slice($all, $offset, $per);
	ob_start();
	foreach ($slice as $rev) {
		$initials = strtoupper(($rev['name'][0] ?? 'R'));
		echo '<div class="wk-review-card">';
		echo '<div class="wk-review-card__top">';
		echo '<div class="wk-review-card__author">';
		if ($rev['avatar']) {
			echo '<img src="'.esc_url($rev['avatar']).'" class="wk-review-avatar" alt="" />';
		} else {
			echo '<div class="wk-review-avatar wk-review-avatar--initials">'.esc_html($initials).'</div>';
		}
		echo '<div class="wk-review-author-info"><strong>'.esc_html($rev['name']).'</strong>';
		if ($rev['city']) echo '<span class="wk-review-city">'.esc_html($rev['city']).'</span>';
		echo '</div></div>';
		echo '<div class="wk-review-card__rating"><span class="wk-review-rating-num">'.number_format($rev['rating'],1).'</span></div>';
		echo '</div>';
		echo '<p class="wk-review-card__text">'.nl2br(esc_html($rev['text'])).'</p>';
		if (!empty($rev['photos'])) {
			echo '<div class="wk-review-photos">';
			foreach ($rev['photos'] as $p) echo '<img src="'.esc_url($p).'" class="wk-review-photo" loading="lazy" />';
			echo '</div>';
		}
		echo '</div>';
	}
	wp_send_json_success(['html'=>ob_get_clean(),'has_more'=>count($all) > $offset + $per]);
}

// ─── Frontend: User Review Submission AJAX ──────────────────────────────────
add_action( 'wp_ajax_wk_submit_user_review',        'wk_handle_user_review_submission' );
add_action( 'wp_ajax_nopriv_wk_submit_user_review', 'wk_handle_user_review_submission' );

function wk_handle_user_review_submission() {
	// Verify nonce
	if ( ! check_ajax_referer( 'wk-nonce', 'nonce', false ) ) {
		wp_send_json_error( [ 'message' => 'Security check failed. Please refresh and try again.' ] );
	}

	// Validate required fields
	$name = sanitize_text_field( $_POST['wk_reviewer_name'] ?? '' );
	$text = sanitize_textarea_field( $_POST['wk_user_review_text'] ?? '' );
	$rating = (float) ( $_POST['wk_user_rating'] ?? 0 );

	if ( ! $name ) {
		wp_send_json_error( [ 'message' => 'Please enter your name.' ] );
	}
	if ( ! $text || strlen( $text ) < 10 ) {
		wp_send_json_error( [ 'message' => 'Review must be at least 10 characters.' ] );
	}
	if ( $rating < 1 || $rating > 5 ) {
		wp_send_json_error( [ 'message' => 'Please select a rating between 1 and 5 stars.' ] );
	}

	// Rate limiting: 3 reviews per IP per hour
	$ip       = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
	$limit_key = 'wk_rv_' . md5( $ip );
	$count     = (int) get_transient( $limit_key );
	if ( $count >= 3 ) {
		wp_send_json_error( [ 'message' => 'Too many reviews submitted. Please try again in an hour.' ] );
	}
	set_transient( $limit_key, $count + 1, HOUR_IN_SECONDS );

	// Create review post
	$city       = sanitize_text_field( $_POST['wk_reviewer_city'] ?? '' );
	$product_id = absint( $_POST['wk_product_id'] ?? 0 );

	// Determine publish status: auto-approve if no moderation required
	$auto_approve = (bool) get_theme_mod( 'wk_reviews_auto_approve', false );
	$status       = $auto_approve ? 'publish' : 'pending';

	$post_id = wp_insert_post( [
		'post_type'   => 'wk_review',
		'post_title'  => $name,
		'post_status' => $status,
	] );

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( [ 'message' => 'Could not save review. Please try again.' ] );
	}

	// Save meta
	update_post_meta( $post_id, '_wk_reviewer_name', $name );
	update_post_meta( $post_id, '_wk_reviewer_city', $city );
	update_post_meta( $post_id, '_wk_rating',        $rating );
	update_post_meta( $post_id, '_wk_review_text',   $text );
	update_post_meta( $post_id, '_wk_review_date',   date( 'Y-m-d' ) );
	update_post_meta( $post_id, '_wk_product_id',    $product_id );
	update_post_meta( $post_id, '_wk_verified',      'no' );

	// Handle photo uploads (up to 3)
	$photo_urls = [];
	if ( ! empty( $_FILES['wk_review_photos']['name'][0] ) ) {
		if ( ! function_exists( 'wp_handle_upload' ) ) require_once ABSPATH . 'wp-admin/includes/file.php';
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) require_once ABSPATH . 'wp-admin/includes/image.php';
		if ( ! function_exists( 'media_handle_upload' ) ) require_once ABSPATH . 'wp-admin/includes/media.php';

		$count = min( 3, count( $_FILES['wk_review_photos']['name'] ) );
		for ( $i = 0; $i < $count; $i++ ) {
			$file = [
				'name'     => $_FILES['wk_review_photos']['name'][$i],
				'type'     => $_FILES['wk_review_photos']['type'][$i],
				'tmp_name' => $_FILES['wk_review_photos']['tmp_name'][$i],
				'error'    => $_FILES['wk_review_photos']['error'][$i],
				'size'     => $_FILES['wk_review_photos']['size'][$i],
			];
			if ( $file['error'] === UPLOAD_ERR_OK ) {
				$_FILES['wk_single_photo'] = $file;
				$att_id = media_handle_upload( 'wk_single_photo', $post_id );
				if ( ! is_wp_error( $att_id ) ) {
					$photo_urls[] = wp_get_attachment_url( $att_id );
				}
			}
		}
		if ( $photo_urls ) {
			update_post_meta( $post_id, '_wk_review_photos', $photo_urls );
		}
	}

	$message = $auto_approve
		? '⭐ Thank you for your review! It is now live.'
		: '✅ Thank you! Your review has been submitted and will appear after approval.';

	wp_send_json_success( [ 'message' => $message, 'post_id' => $post_id ] );
}
