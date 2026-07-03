<?php
/**
 * WhiteKurti — Homepage Sections v2
 * 1. Testimonials Carousel (with admin management)
 * 2. Lookbook Editorial Section
 * 3. Instagram-style Manual Photo Grid
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ═══════════════════════════════════════════════════════════════
// 1. TESTIMONIALS CAROUSEL ADMIN
// ═══════════════════════════════════════════════════════════════

// Menu registration moved to inc/admin-hub.php

function wk_testimonials_get() {
	$v = get_option( 'wk_testimonials_list', null );
	if ( is_array( $v ) ) return $v;
	return [
		[ 'text'=>'The fabric quality is exceptional — so soft and breathable. Perfect for both everyday wear and special occasions. Packaging was beautiful too!', 'name'=>'Priya Sharma', 'city'=>'Mumbai', 'rating'=>5, 'photo'=>'' ],
		[ 'text'=>'I ordered the Chikankari kurta and I am absolutely obsessed. The detailing is so intricate and the fit is exactly as described. Will definitely order more!', 'name'=>'Ananya Gupta', 'city'=>'Delhi', 'rating'=>5, 'photo'=>'' ],
		[ 'text'=>'Amazing quality for the price. The white kurta set looks exactly like the photos — crisp, clean and perfectly stitched. Delivery was super fast too.', 'name'=>'Sneha Patel', 'city'=>'Ahmedabad', 'rating'=>5, 'photo'=>'' ],
		[ 'text'=>'I have been searching for the perfect white kurta for my sister\'s wedding. Found it here! The fabric drapes beautifully and the embroidery work is stunning.', 'name'=>'Kavya Reddy', 'city'=>'Hyderabad', 'rating'=>5, 'photo'=>'' ],
		[ 'text'=>'My third order from this brand and still impressed every time. The linen co-ord set is everything — comfortable, elegant and effortlessly stylish.', 'name'=>'Meera Nair', 'city'=>'Kochi', 'rating'=>5, 'photo'=>'' ],
		[ 'text'=>'Best investment! The kurta fabric has been washed 10 times and still looks brand new. No pilling, no colour fade. Genuine quality at a fair price.', 'name'=>'Riya Joshi', 'city'=>'Pune', 'rating'=>5, 'photo'=>'' ],
	];
}

add_action( 'admin_init', function() {
	if ( ! isset($_POST['wk_test_nonce']) || ! wp_verify_nonce($_POST['wk_test_nonce'],'wk_test_save') ) return;
	if ( ! current_user_can('manage_options') ) return;
	$testimonials = [];
	$texts   = $_POST['test_text']   ?? [];
	$names   = $_POST['test_name']   ?? [];
	$cities  = $_POST['test_city']   ?? [];
	$ratings = $_POST['test_rating'] ?? [];
	$photos  = $_POST['test_photo']  ?? [];
	foreach ( $texts as $i => $text ) {
		$text = sanitize_textarea_field($text);
		$name = sanitize_text_field($names[$i] ?? '');
		if ( !$text || !$name ) continue;
		$testimonials[] = [
			'text'   => $text,
			'name'   => $name,
			'city'   => sanitize_text_field($cities[$i] ?? ''),
			'rating' => max(1, min(5, absint($ratings[$i] ?? 5))),
			'photo'  => esc_url_raw($photos[$i] ?? ''),
		];
	}
	update_option('wk_testimonials_list', $testimonials);
});


// ── Admin action handlers ────────────────────────────────────────────────────
add_action('admin_init', function() {
	if (get_current_screen() && strpos(get_current_screen()->id ?? '', 'wk-testimonials') === false) return;

	$tests = wk_testimonials_get();

	// DELETE
	if (isset($_GET['delete'], $_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'wk_test_delete')) {
		$idx = absint($_GET['delete']);
		unset($tests[$idx]);
		update_option('wk_testimonials_list', array_values($tests));
		wp_safe_redirect(admin_url('admin.php?page=wk-testimonials&tab=manage&deleted=1'));
		exit;
	}

	// ADD
	if (isset($_POST['wk_test_action']) && $_POST['wk_test_action'] === 'add'
		&& isset($_POST['wk_test_add_nonce']) && wp_verify_nonce($_POST['wk_test_add_nonce'], 'wk_test_add')
		&& current_user_can('manage_options')) {
		$text = sanitize_textarea_field($_POST['test_new_text'] ?? '');
		$name = sanitize_text_field($_POST['test_new_name'] ?? '');
		if ($text && $name) {
			$tests[] = [
				'text'   => $text,
				'name'   => $name,
				'city'   => sanitize_text_field($_POST['test_new_city'] ?? ''),
				'rating' => max(1, min(5, absint($_POST['test_new_rating'] ?? 5))),
				'photo'  => esc_url_raw($_POST['test_new_photo'] ?? ''),
			];
			update_option('wk_testimonials_list', $tests);
			wp_safe_redirect(admin_url('admin.php?page=wk-testimonials&tab=manage&added=1'));
			exit;
		}
	}

	// EDIT
	if (isset($_POST['wk_test_action']) && $_POST['wk_test_action'] === 'edit'
		&& isset($_POST['wk_test_edit_nonce']) && current_user_can('manage_options')) {
		$edit_idx = absint($_POST['wk_test_edit_idx'] ?? -1);
		if ($edit_idx >= 0 && isset($tests[$edit_idx])
			&& wp_verify_nonce($_POST['wk_test_edit_nonce'], 'wk_test_edit_' . $edit_idx)) {
			$text = sanitize_textarea_field($_POST['test_new_text'] ?? '');
			$name = sanitize_text_field($_POST['test_new_name'] ?? '');
			if ($text && $name) {
				$tests[$edit_idx] = [
					'text'   => $text,
					'name'   => $name,
					'city'   => sanitize_text_field($_POST['test_new_city'] ?? ''),
					'rating' => max(1, min(5, absint($_POST['test_new_rating'] ?? 5))),
					'photo'  => esc_url_raw($_POST['test_new_photo'] ?? ''),
				];
				update_option('wk_testimonials_list', $tests);
				wp_safe_redirect(admin_url('admin.php?page=wk-testimonials&tab=manage&saved=1'));
				exit;
			}
		}
	}

	// SETTINGS
	if (isset($_POST['wk_test_action']) && $_POST['wk_test_action'] === 'settings'
		&& isset($_POST['wk_test_settings_nonce']) && wp_verify_nonce($_POST['wk_test_settings_nonce'], 'wk_test_settings')
		&& current_user_can('manage_options')) {
		set_theme_mod('wk_show_testimonials',    !empty($_POST['test_show']));
		set_theme_mod('wk_testimonials_title',   sanitize_text_field($_POST['test_title'] ?? ''));
		set_theme_mod('wk_testimonials_subtitle',sanitize_text_field($_POST['test_sub'] ?? ''));
		wp_safe_redirect(admin_url('admin.php?page=wk-testimonials&tab=settings&saved=1'));
		exit;
	}

	// SUCCESS NOTICES
	if (isset($_GET['added'])) {
		add_action('admin_notices', function(){ echo '<div class="notice notice-success is-dismissible"><p>✅ Review added!</p></div>'; });
	}
	if (isset($_GET['saved'])) {
		add_action('admin_notices', function(){ echo '<div class="notice notice-success is-dismissible"><p>✅ Review updated!</p></div>'; });
	}
	if (isset($_GET['deleted'])) {
		add_action('admin_notices', function(){ echo '<div class="notice notice-success is-dismissible"><p>🗑 Review deleted.</p></div>'; });
	}
});

function wk_testimonials_admin_page() {
	$tests = wk_testimonials_get();
	$active_tab = $_GET['tab'] ?? 'manage';
	wp_enqueue_media();
	wp_enqueue_style('wp-color-picker');
	wp_enqueue_script('wp-color-picker');
	?>
	<div class="wrap wk-reviews-wrap" style="max-width:1100px;">
	<h1 style="display:flex;align-items:center;gap:10px;">⭐ Reviews & Testimonials Manager</h1>

	<!-- Tabs -->
	<nav class="nav-tab-wrapper" style="margin-bottom:0;">
		<a href="?page=wk-testimonials&tab=manage" class="nav-tab <?php echo $active_tab==='manage'?'nav-tab-active':''; ?>">📋 Manage Reviews</a>
		<a href="?page=wk-testimonials&tab=add" class="nav-tab <?php echo $active_tab==='add'?'nav-tab-active':''; ?>">➕ Add New Review</a>
		<a href="?page=wk-testimonials&tab=settings" class="nav-tab <?php echo $active_tab==='settings'?'nav-tab-active':''; ?>">⚙️ Carousel Settings</a>
	</nav>

	<style>
	.wk-rv-wrap{background:#fff;border:1px solid #ddd;border-top:none;padding:24px;margin-bottom:20px;}
	.wk-rv-card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px;margin-bottom:12px;display:grid;grid-template-columns:64px 1fr auto;gap:16px;align-items:start;}
	.wk-rv-card__photo{width:60px;height:60px;border-radius:50%;object-fit:cover;background:#f0f0f0;border:2px solid #eee;}
	.wk-rv-card__avatar{width:60px;height:60px;border-radius:50%;background:#6B1E3E;color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;}
	.wk-rv-card__stars{color:#f5a623;font-size:14px;margin-bottom:4px;}
	.wk-rv-card__text{font-size:13px;color:#555;font-style:italic;margin:4px 0;}
	.wk-rv-card__meta{font-size:11px;color:#888;}
	.wk-rv-card__actions{display:flex;flex-direction:column;gap:6px;}
	.wk-rv-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
	.wk-rv-field label{display:block;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#555;margin-bottom:5px;}
	.wk-rv-field input,.wk-rv-field select,.wk-rv-field textarea{width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;box-sizing:border-box;}
	.wk-rv-field textarea{min-height:90px;resize:vertical;}
	.wk-rv-field input[type=color]{width:44px;height:34px;padding:2px;cursor:pointer;}
	.wk-rv-stars-pick{display:flex;gap:6px;margin-top:4px;}
	.wk-rv-star-btn{width:32px;height:32px;font-size:20px;background:none;border:1px solid #ddd;border-radius:4px;cursor:pointer;transition:.15s;line-height:1;}
	.wk-rv-star-btn.on{background:#fff3cd;border-color:#f5a623;}
	.wk-rv-photo-preview{width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid #ddd;display:block;margin-bottom:6px;}
	</style>

	<?php if ($active_tab === 'manage') : ?>
	<!-- ─── MANAGE TAB ─── -->
	<div class="wk-rv-wrap">
		<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
			<h2 style="margin:0;">All Reviews (<?php echo count($tests); ?>)</h2>
			<a href="?page=wk-testimonials&tab=add" class="button button-primary" style="background:#6B1E3E;border-color:#4a1228;">+ Add New Review</a>
		</div>
		<?php if (empty($tests)) : ?>
		<div style="text-align:center;padding:40px;color:#888;">
			<p style="font-size:16px;">No reviews yet.</p>
			<a href="?page=wk-testimonials&tab=add" class="button button-primary" style="background:#6B1E3E;border-color:#4a1228;">Add Your First Review</a>
		</div>
		<?php else : ?>
		<form method="post" id="wk-test-reorder-form">
		<?php wp_nonce_field('wk_test_save','wk_test_nonce'); ?>
		<div id="wk-test-list" style="margin-bottom:16px;">
		<?php foreach ($tests as $i => $t) :
			$stars = max(1,min(5,(int)($t['rating']??5)));
		?>
		<div class="wk-rv-card" data-idx="<?php echo $i; ?>">
			<!-- Photo -->
			<div>
				<?php if (!empty($t['photo'])) : ?>
				<img src="<?php echo esc_url($t['photo']); ?>" class="wk-rv-card__photo" alt="" />
				<?php else : ?>
				<div class="wk-rv-card__avatar"><?php echo esc_html(strtoupper(mb_substr($t['name']??'?',0,1))); ?></div>
				<?php endif; ?>
				<input type="hidden" name="test_photo[<?php echo $i; ?>]" value="<?php echo esc_attr($t['photo']??''); ?>" />
			</div>
			<!-- Info -->
			<div>
				<div class="wk-rv-card__stars"><?php echo str_repeat('★',$stars).str_repeat('☆',5-$stars); ?></div>
				<div class="wk-rv-card__text">"<?php echo esc_html($t['text']); ?>"</div>
				<div class="wk-rv-card__meta">
					<strong><?php echo esc_html($t['name']); ?></strong>
					<?php if (!empty($t['city'])) echo ' · ' . esc_html($t['city']); ?>
				</div>
				<!-- Hidden fields for re-save on reorder -->
				<input type="hidden" name="test_name[<?php echo $i; ?>]" value="<?php echo esc_attr($t['name']); ?>" />
				<input type="hidden" name="test_city[<?php echo $i; ?>]" value="<?php echo esc_attr($t['city']??''); ?>" />
				<input type="hidden" name="test_rating[<?php echo $i; ?>]" value="<?php echo esc_attr($stars); ?>" />
				<input type="hidden" name="test_text[<?php echo $i; ?>]" value="<?php echo esc_attr($t['text']); ?>" />
			</div>
			<!-- Actions -->
			<div class="wk-rv-card__actions">
				<a href="?page=wk-testimonials&tab=edit&idx=<?php echo $i; ?>" class="button button-small">✏️ Edit</a>
				<a href="<?php echo esc_url(wp_nonce_url('?page=wk-testimonials&tab=manage&delete='.$i,'wk_test_delete')); ?>"
				   class="button button-small" style="color:#d00;" onclick="return confirm('Delete this review?')">🗑 Delete</a>
				<span class="button button-small wk-drag-handle" style="cursor:grab;color:#999;" title="Drag to reorder">⠿</span>
			</div>
		</div>
		<?php endforeach; ?>
		</div>
		<input type="submit" class="button" value="Save Order" style="margin-top:8px;" />
		</form>
		<?php endif; ?>
	</div>

	<?php elseif ($active_tab === 'add') : ?>
	<!-- ─── ADD NEW TAB ─── -->
	<div class="wk-rv-wrap">
	<h2 style="margin:0 0 20px;">Add New Review</h2>
	<form method="post">
	<?php wp_nonce_field('wk_test_add','wk_test_add_nonce'); ?>
	<input type="hidden" name="wk_test_action" value="add" />
	<?php echo wk_testimonials_form_fields(); ?>
	<input type="submit" class="button button-primary" value="Add Review" style="background:#6B1E3E;border-color:#4a1228;padding:10px 24px;font-size:14px;" />
	</form>
	</div>

	<?php elseif ($active_tab === 'edit' && isset($_GET['idx'])) :
		$edit_idx = absint($_GET['idx']);
		$edit_item = $tests[$edit_idx] ?? null;
		if ($edit_item) :
	?>
	<!-- ─── EDIT TAB ─── -->
	<div class="wk-rv-wrap">
	<h2 style="margin:0 0 20px;">Edit Review</h2>
	<form method="post">
	<?php wp_nonce_field('wk_test_edit_'.$edit_idx,'wk_test_edit_nonce'); ?>
	<input type="hidden" name="wk_test_action" value="edit" />
	<input type="hidden" name="wk_test_edit_idx" value="<?php echo $edit_idx; ?>" />
	<?php echo wk_testimonials_form_fields($edit_item); ?>
	<div style="display:flex;gap:12px;align-items:center;">
		<input type="submit" class="button button-primary" value="Save Changes" style="background:#6B1E3E;border-color:#4a1228;padding:10px 24px;font-size:14px;" />
		<a href="?page=wk-testimonials&tab=manage" class="button">← Back to Reviews</a>
	</div>
	</form>
	</div>
	<?php endif; ?>

	<?php elseif ($active_tab === 'settings') : ?>
	<!-- ─── SETTINGS TAB ─── -->
	<div class="wk-rv-wrap">
	<h2 style="margin:0 0 20px;">Carousel Settings</h2>
	<form method="post">
	<?php wp_nonce_field('wk_test_settings','wk_test_settings_nonce'); ?>
	<input type="hidden" name="wk_test_action" value="settings" />
	<?php
	$show  = get_theme_mod('wk_show_testimonials', true);
	$title = get_theme_mod('wk_testimonials_title', 'What Our Customers Say');
	$sub   = get_theme_mod('wk_testimonials_subtitle','Loved by thousands of women across India');
	$bg    = get_theme_mod('wk_testimonials_bg','');
	?>
	<table class="form-table">
		<tr>
			<th>Show on Homepage</th>
			<td><label><input type="checkbox" name="test_show" value="1" <?php checked($show,true); ?> /> Show the testimonials section</label></td>
		</tr>
		<tr>
			<th>Section Title</th>
			<td><input type="text" name="test_title" value="<?php echo esc_attr($title); ?>" style="width:400px;" /></td>
		</tr>
		<tr>
			<th>Subtitle</th>
			<td><input type="text" name="test_sub" value="<?php echo esc_attr($sub); ?>" style="width:400px;" /></td>
		</tr>
	</table>
	<input type="submit" class="button button-primary" value="Save Settings" style="background:#6B1E3E;border-color:#4a1228;padding:10px 24px;font-size:14px;" />
	</form>
	</div>
	<?php endif; ?>
	</div>

	<script>
	jQuery(function($){
		// Photo upload button
		$(document).on('click','.wk-test-photo-btn', function(){
			var $btn=$(this);
			var f=wp.media({title:'Customer Photo',multiple:false});
			f.on('select',function(){
				var a=f.state().get('selection').first().toJSON();
				$btn.siblings('.test-photo-url').val(a.url);
				$btn.siblings('.wk-rv-photo-preview').attr('src',a.url).show();
				$btn.siblings('.wk-test-no-photo').hide();
			});
			f.open();
		});
		// Star rating picker
		$(document).on('click','.wk-rv-star-btn', function(){
			var val = parseInt($(this).data('val'));
			$(this).closest('.wk-rv-stars-pick').find('.wk-rv-star-btn').each(function(){
				$(this).toggleClass('on', parseInt($(this).data('val')) <= val);
			});
			$(this).closest('.wk-rv-stars-pick').siblings('.test-rating-val').val(val);
		});
		// Drag to reorder
		if ($.fn.sortable) {
			$('#wk-test-list').sortable({
				handle: '.wk-drag-handle',
				update: function() {
					$('#wk-test-list .wk-rv-card').each(function(i){
						$(this).data('idx', i);
					});
				}
			});
		}
	});
	</script>
	<?php
}

// Helper: render review form fields
function wk_testimonials_form_fields($item = []) {
	$name   = esc_attr($item['name']   ?? '');
	$city   = esc_attr($item['city']   ?? '');
	$text   = esc_textarea($item['text']  ?? '');
	$rating = (int)($item['rating'] ?? 5);
	$photo  = esc_attr($item['photo']  ?? '');
	ob_start();
	?>
	<div class="wk-rv-form-grid">
		<div class="wk-rv-field">
			<label>Customer Name *</label>
			<input type="text" name="test_new_name" value="<?php echo $name; ?>" placeholder="e.g. Priya Sharma" required />
		</div>
		<div class="wk-rv-field">
			<label>City</label>
			<input type="text" name="test_new_city" value="<?php echo $city; ?>" placeholder="e.g. Mumbai" />
		</div>
	</div>
	<div class="wk-rv-field" style="margin-bottom:16px;">
		<label>Star Rating</label>
		<div class="wk-rv-stars-pick">
			<?php for ($r=1;$r<=5;$r++) : ?>
			<button type="button" class="wk-rv-star-btn <?php echo $r<=$rating?'on':''; ?>" data-val="<?php echo $r; ?>">★</button>
			<?php endfor; ?>
		</div>
		<input type="hidden" name="test_new_rating" class="test-rating-val" value="<?php echo $rating; ?>" />
	</div>
	<div class="wk-rv-field" style="margin-bottom:16px;">
		<label>Review Text *</label>
		<textarea name="test_new_text" required><?php echo $text; ?></textarea>
	</div>
	<div class="wk-rv-field" style="margin-bottom:20px;">
		<label>Customer Photo (optional)</label>
		<?php if ($photo) : ?>
		<img src="<?php echo esc_url($photo); ?>" class="wk-rv-photo-preview" style="display:block;margin-bottom:8px;" />
		<?php else : ?>
		<div class="wk-test-no-photo" style="display:none;"></div>
		<img src="" class="wk-rv-photo-preview" style="display:none;width:64px;height:64px;border-radius:50%;margin-bottom:8px;" />
		<?php endif; ?>
		<input type="hidden" name="test_new_photo" class="test-photo-url" value="<?php echo $photo; ?>" />
		<button type="button" class="button wk-test-photo-btn">Upload Photo</button>
		<p style="font-size:11px;color:#888;margin:4px 0 0;">Recommended: square photo, 200×200px minimum.</p>
	</div>
	<?php
	return ob_get_clean();
}
function wk_render_testimonials_carousel() {
	if (!get_theme_mod('wk_show_testimonials', true)) return;
	$tests = wk_testimonials_get();
	if (empty($tests)) return;
	$title    = get_theme_mod('wk_testimonials_title', 'What Our Customers Say');
	$subtitle = get_theme_mod('wk_testimonials_subtitle', 'Loved by thousands of women across India');
	?>
	<section class="wk-testimonials-section" aria-label="Customer Reviews">
		<div class="wk-container">
			<div class="wk-section-header">
				<h2 class="wk-section-title"><?php echo esc_html($title); ?></h2>
				<?php if ($subtitle) : ?><p class="wk-section-sub"><?php echo esc_html($subtitle); ?></p><?php endif; ?>
			</div>
		</div>
		<div class="wk-testimonials-carousel" id="wk-testimonials-carousel">
			<div class="wk-testimonials-track" id="wk-testimonials-track">
				<?php foreach ($tests as $i => $t) :
					$stars = max(1,min(5,(int)($t['rating']??5)));
				?>
				<div class="wk-testimonial-card" role="article">
					<div class="wk-testimonial-card__stars">
						<?php for ($s=1;$s<=5;$s++) : ?>
						<svg width="14" height="14" viewBox="0 0 24 24" fill="<?php echo $s<=$stars?'var(--accent)':'none'; ?>" stroke="var(--accent)" stroke-width="1.5"><polygon points="12 2 15 8.6 22.3 9.4 16.9 14.4 18.4 21.6 12 18 5.6 21.6 7.1 14.4 1.7 9.4 9 8.6"/></svg>
						<?php endfor; ?>
					</div>
					<blockquote class="wk-testimonial-card__text">"<?php echo esc_html($t['text']); ?>"</blockquote>
					<div class="wk-testimonial-card__author">
						<?php if (!empty($t['photo'])) : ?>
						<img src="<?php echo esc_url($t['photo']); ?>" alt="<?php echo esc_attr($t['name']); ?>" class="wk-testimonial-card__photo" loading="lazy" />
						<?php else : ?>
						<div class="wk-testimonial-card__avatar"><?php echo strtoupper(mb_substr($t['name'],0,1)); ?></div>
						<?php endif; ?>
						<div>
							<span class="wk-testimonial-card__name"><?php echo esc_html($t['name']); ?></span>
							<?php if (!empty($t['city'])) : ?>
							<span class="wk-testimonial-card__city"><?php echo esc_html($t['city']); ?></span>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<!-- Navigation -->
			<div class="wk-testimonials-nav">
				<button class="wk-testimonials-prev" aria-label="Previous testimonials" id="wk-test-prev">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
				</button>
				<div class="wk-testimonials-dots" id="wk-test-dots">
					<?php foreach ($tests as $i => $t) : ?>
					<button class="wk-testimonials-dot <?php echo $i===0?'is-active':''; ?>" data-idx="<?php echo $i; ?>" aria-label="Go to testimonial <?php echo $i+1; ?>"></button>
					<?php endforeach; ?>
				</div>
				<button class="wk-testimonials-next" aria-label="Next testimonials" id="wk-test-next">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
				</button>
			</div>
		</div>
	</section>
	<?php
}

// ═══════════════════════════════════════════════════════════════
// 2. LOOKBOOK / EDITORIAL SECTION
// ═══════════════════════════════════════════════════════════════

function wk_lookbook_get() {
	return get_option('wk_lookbook_panels', [
		['img'=>'https://images.pexels.com/photos/13178920/pexels-photo-13178920.jpeg?w=1200&h=800&fit=crop','eyebrow'=>'New Arrivals','title'=>'The White Linen Edit','body'=>'Effortless everyday luxury. Crafted in breathable handloom linen for the modern Indian woman.','cta_text'=>'Shop the Edit','cta_url'=>'','layout'=>'left'],
		['img'=>'https://images.pexels.com/photos/13998716/pexels-photo-13998716.jpeg?w=1200&h=800&fit=crop','eyebrow'=>'Festival Collection','title'=>'Celebration in White','body'=>'From intimate pooja mornings to festive family gatherings — dressed in pure white elegance.','cta_text'=>'Explore Collection','cta_url'=>'','layout'=>'right'],
	]);
}

// Menu registration moved to inc/admin-hub.php

add_action('admin_init', function() {
	if (!isset($_POST['wk_lb_nonce'])||!wp_verify_nonce($_POST['wk_lb_nonce'],'wk_lb_save')) return;
	if (!current_user_can('manage_options')) return;
	$panels = [];
	$imgs    = $_POST['lb_img']    ?? [];
	$eyebrows= $_POST['lb_eyebrow']?? [];
	$titles  = $_POST['lb_title']  ?? [];
	$bodies  = $_POST['lb_body']   ?? [];
	$cta_txts= $_POST['lb_cta_text']??[];
	$cta_urls= $_POST['lb_cta_url']??[];
	$layouts = $_POST['lb_layout'] ??[];
	foreach ($imgs as $i => $img) {
		if (!$img && !($titles[$i]??'')) continue;
		$panels[] = [
			'img'      => esc_url_raw($img),
			'eyebrow'  => sanitize_text_field($eyebrows[$i]??''),
			'title'    => sanitize_text_field($titles[$i]??''),
			'body'     => sanitize_textarea_field($bodies[$i]??''),
			'cta_text' => sanitize_text_field($cta_txts[$i]??''),
			'cta_url'  => esc_url_raw($cta_urls[$i]??''),
			'layout'   => sanitize_text_field($layouts[$i]??'left'),
		];
	}
	update_option('wk_lookbook_panels', $panels);
});

function wk_lookbook_admin_page() {
	$panels = wk_lookbook_get();
	wp_enqueue_media();
	?>
	<div class="wrap" style="max-width:900px;">
	<h1>🖼️ Lookbook / Editorial Section</h1>
	<p style="color:#666;">Each panel appears as a full-bleed editorial block on the homepage.</p>
	<form method="post"><?php wp_nonce_field('wk_lb_save','wk_lb_nonce'); ?>
	<style>.wk-lb-card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;margin-bottom:16px;}.wk-lb-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:12px;}.wk-lb-field label{display:block;font-weight:700;font-size:11px;text-transform:uppercase;color:#555;margin-bottom:4px;}.wk-lb-field input,.wk-lb-field select,.wk-lb-field textarea{width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:4px;font-size:13px;box-sizing:border-box;}.wk-lb-field textarea{min-height:60px;}.wk-lb-thumb{max-width:100%;max-height:120px;border-radius:4px;display:block;margin-top:6px;}</style>
	<div id="wk-lb-list">
	<?php foreach ($panels as $i => $p) : ?>
	<div class="wk-lb-card">
		<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;"><h3 style="margin:0">Panel <?php echo $i+1; ?></h3><button type="button" class="button button-small wk-lb-remove" style="color:#d00;">Remove</button></div>
		<div class="wk-lb-row">
			<div class="wk-lb-field" style="grid-column:1/-1">
				<label>Image URL</label>
				<div style="display:flex;gap:8px;align-items:center;">
					<input type="url" name="lb_img[<?php echo $i; ?>]" class="lb-img-url" value="<?php echo esc_attr($p['img']); ?>" placeholder="https://..." />
					<button type="button" class="button wk-lb-upload" data-idx="<?php echo $i; ?>">Upload</button>
				</div>
				<?php if ($p['img']) : ?><img src="<?php echo esc_url($p['img']); ?>" class="wk-lb-thumb" /><?php endif; ?>
			</div>
			<div class="wk-lb-field"><label>Eyebrow (small text above title)</label><input type="text" name="lb_eyebrow[<?php echo $i; ?>]" value="<?php echo esc_attr($p['eyebrow']); ?>" placeholder="e.g. New Collection" /></div>
			<div class="wk-lb-field"><label>Headline</label><input type="text" name="lb_title[<?php echo $i; ?>]" value="<?php echo esc_attr($p['title']); ?>" placeholder="Main title" /></div>
			<div class="wk-lb-field" style="grid-column:1/-1"><label>Body Text</label><textarea name="lb_body[<?php echo $i; ?>]"><?php echo esc_textarea($p['body']); ?></textarea></div>
			<div class="wk-lb-field"><label>CTA Button Text</label><input type="text" name="lb_cta_text[<?php echo $i; ?>]" value="<?php echo esc_attr($p['cta_text']); ?>" placeholder="Shop Now" /></div>
			<div class="wk-lb-field"><label>CTA URL</label><input type="url" name="lb_cta_url[<?php echo $i; ?>]" value="<?php echo esc_attr($p['cta_url']); ?>" placeholder="https://..." /></div>
			<div class="wk-lb-field"><label>Text Position</label><select name="lb_layout[<?php echo $i; ?>]"><option value="left" <?php selected($p['layout'],'left'); ?>>Text on Left</option><option value="right" <?php selected($p['layout'],'right'); ?>>Text on Right</option><option value="center" <?php selected($p['layout'],'center'); ?>>Text Centered</option></select></div>
		</div>
	</div>
	<?php endforeach; ?>
	</div>
	<button type="button" class="button" id="wk-add-lb-panel" style="margin-bottom:20px;">+ Add Panel</button><br>
	<input type="submit" class="button button-primary" value="Save Lookbook" style="background:#6B1E3E;border-color:#4a1228;padding:10px 24px;font-size:14px;" />
	</form>
	</div>
	<script>
	jQuery(function($){
		var idx=<?php echo count($panels); ?>;
		$('#wk-add-lb-panel').on('click',function(){
			$('#wk-lb-list').append('<div class="wk-lb-card"><div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;"><h3 style="margin:0">Panel '+(idx+1)+'</h3><button type="button" class="button button-small wk-lb-remove" style="color:#d00;">Remove</button></div><div class="wk-lb-row"><div class="wk-lb-field" style="grid-column:1/-1"><label>Image URL</label><div style="display:flex;gap:8px;align-items:center;"><input type="url" name="lb_img['+idx+']" class="lb-img-url" placeholder="https://..." /><button type="button" class="button wk-lb-upload" data-idx="'+idx+'">Upload</button></div></div><div class="wk-lb-field"><label>Eyebrow</label><input type="text" name="lb_eyebrow['+idx+']" /></div><div class="wk-lb-field"><label>Headline</label><input type="text" name="lb_title['+idx+']" /></div><div class="wk-lb-field" style="grid-column:1/-1"><label>Body Text</label><textarea name="lb_body['+idx+']"></textarea></div><div class="wk-lb-field"><label>CTA Text</label><input type="text" name="lb_cta_text['+idx+']" /></div><div class="wk-lb-field"><label>CTA URL</label><input type="url" name="lb_cta_url['+idx+']" /></div><div class="wk-lb-field"><label>Layout</label><select name="lb_layout['+idx+']"><option value="left">Left</option><option value="right">Right</option><option value="center">Center</option></select></div></div></div>');
			idx++;
		});
		$(document).on('click','.wk-lb-remove',function(){$(this).closest('.wk-lb-card').remove();});
		$(document).on('click','.wk-lb-upload',function(){
			var $btn=$(this);var i=$btn.data('idx');
			var f=wp.media({title:'Select Image',multiple:false});
			f.on('select',function(){var a=f.state().get('selection').first().toJSON();$btn.siblings('.lb-img-url').val(a.url);$btn.closest('.wk-lb-field').find('.wk-lb-thumb').remove();$btn.closest('.wk-lb-field').append('<img class="wk-lb-thumb" src="'+a.url+'" />');});
			f.open();
		});
	});
	</script>
	<?php
}

function wk_render_lookbook() {
	$panels = wk_lookbook_get();
	if (empty($panels) || !get_theme_mod('wk_show_lookbook', true)) return;
	foreach ($panels as $p) :
		if (!$p['img'] && !$p['title']) continue;
		$layout = $p['layout'] ?? 'left';
	?>
	<section class="wk-lookbook-panel wk-lookbook--<?php echo esc_attr($layout); ?>"
	         style="<?php echo $p['img'] ? 'background-image:url('.esc_url($p['img']).')' : ''; ?>">
		<div class="wk-lookbook-panel__overlay"></div>
		<div class="wk-lookbook-panel__content">
			<?php if ($p['eyebrow']) : ?>
			<span class="wk-lookbook-panel__eyebrow"><?php echo esc_html($p['eyebrow']); ?></span>
			<?php endif; ?>
			<?php if ($p['title']) : ?>
			<h2 class="wk-lookbook-panel__title"><?php echo esc_html($p['title']); ?></h2>
			<?php endif; ?>
			<?php if ($p['body']) : ?>
			<p class="wk-lookbook-panel__body"><?php echo esc_html($p['body']); ?></p>
			<?php endif; ?>
			<?php if ($p['cta_text'] && $p['cta_url']) : ?>
			<a href="<?php echo esc_url($p['cta_url']); ?>" class="wk-lookbook-panel__cta">
				<?php echo esc_html($p['cta_text']); ?>
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
			</a>
			<?php endif; ?>
		</div>
	</section>
	<?php endforeach;
}

// ═══════════════════════════════════════════════════════════════
// 3. INSTAGRAM-STYLE MANUAL PHOTO GRID
// ═══════════════════════════════════════════════════════════════

function wk_instagram_grid_get() {
	$defaults = [
		['img'=>'https://images.pexels.com/photos/13178920/pexels-photo-13178920.jpeg?w=600&h=600&fit=crop','url'=>''],
		['img'=>'https://images.pexels.com/photos/13998716/pexels-photo-13998716.jpeg?w=600&h=600&fit=crop','url'=>''],
		['img'=>'https://images.pexels.com/photos/26984710/pexels-photo-26984710.jpeg?w=600&h=600&fit=crop','url'=>''],
		['img'=>'https://images.pexels.com/photos/13178920/pexels-photo-13178920.jpeg?w=600&h=600&fit=crop','url'=>''],
		['img'=>'https://images.pexels.com/photos/13998716/pexels-photo-13998716.jpeg?w=600&h=600&fit=crop','url'=>''],
		['img'=>'https://images.pexels.com/photos/26984710/pexels-photo-26984710.jpeg?w=600&h=600&fit=crop','url'=>''],
	];
	$stored = get_option( 'wk_instagram_grid', null );
	return is_array( $stored ) ? $stored : $defaults;
}

// Menu registration moved to inc/admin-hub.php

add_action('admin_init', function() {
	if (!isset($_POST['wk_ig_nonce'])||!wp_verify_nonce($_POST['wk_ig_nonce'],'wk_ig_save')) return;
	if (!current_user_can('manage_options')) return;
	$photos = [];
	$imgs   = $_POST['ig_img'] ?? [];
	$urls   = $_POST['ig_url'] ?? [];
	foreach ($imgs as $i => $img) {
		if (!$img) continue;
		$photos[] = ['img'=>esc_url_raw($img),'url'=>esc_url_raw($urls[$i]??'')];
	}
	update_option('wk_instagram_grid', $photos);
	$title   = sanitize_text_field($_POST['ig_section_title']??'');
	$handle  = sanitize_text_field($_POST['ig_handle']??'');
	$btn_url = esc_url_raw($_POST['ig_btn_url']??'');
	set_theme_mod('wk_ig_title', $title);
	set_theme_mod('wk_ig_handle', $handle);
	set_theme_mod('wk_ig_btn_url', $btn_url);
});

function wk_ig_admin_page() {
	$photos  = wk_instagram_grid_get();
	$active  = $_GET['tab'] ?? 'photos';
	$title   = get_theme_mod('wk_ig_title', '📸 Follow Our World');
	$handle  = get_theme_mod('wk_ig_handle', '@whitekurti');
	$btn_url = get_theme_mod('wk_ig_btn_url','');
	$show    = get_theme_mod('wk_show_instagram_grid', true);

	// Handle save
	if (isset($_POST['wk_ig_nonce']) && wp_verify_nonce($_POST['wk_ig_nonce'],'wk_ig_save') && current_user_can('manage_options')) {
		// Settings save
		set_theme_mod('wk_ig_title',  sanitize_text_field($_POST['ig_section_title']??''));
		set_theme_mod('wk_ig_handle', sanitize_text_field($_POST['ig_handle']??''));
		set_theme_mod('wk_ig_btn_url',esc_url_raw($_POST['ig_btn_url']??''));
		set_theme_mod('wk_show_instagram_grid', !empty($_POST['ig_show']));
		// Photos save
		$new_photos = [];
		$imgs  = $_POST['ig_img']  ?? [];
		$urls  = $_POST['ig_url']  ?? [];
		$caps  = $_POST['ig_cap']  ?? [];
		foreach ($imgs as $i => $img) {
			if (!trim($img)) continue;
			$new_photos[] = [
				'img' => esc_url_raw($img),
				'url' => esc_url_raw($urls[$i]??''),
				'cap' => sanitize_text_field($caps[$i]??''),
			];
		}
		update_option('wk_instagram_grid', $new_photos);
		$photos = $new_photos;
		echo '<div class="notice notice-success is-dismissible"><p>✅ Instagram grid saved!</p></div>';
	}

	wp_enqueue_media();
	?>
	<div class="wrap" style="max-width:1100px;">
	<h1 style="display:flex;align-items:center;gap:10px;">📸 Instagram Grid Manager</h1>

	<!-- Quick Toggle -->
	<div style="background:<?php echo $show ? '#f0fdf4' : '#fef2f2'; ?>;border:1px solid <?php echo $show ? '#86efac' : '#fca5a5'; ?>;border-radius:8px;padding:14px 20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;">
		<div>
			<strong style="font-size:14px;">Instagram Grid on Homepage</strong>
			<p style="margin:2px 0 0;font-size:12px;color:#666;"><?php echo $show ? '✅ Currently visible on homepage' : '🚫 Currently hidden from homepage'; ?></p>
		</div>
		<form method="post" style="margin:0;">
			<?php wp_nonce_field('wk_ig_save','wk_ig_nonce'); ?>
			<input type="hidden" name="ig_section_title" value="<?php echo esc_attr($title); ?>" />
			<input type="hidden" name="ig_handle" value="<?php echo esc_attr($handle); ?>" />
			<input type="hidden" name="ig_btn_url" value="<?php echo esc_attr($btn_url); ?>" />
			<?php if (!$show) : ?><input type="hidden" name="ig_show" value="1" /><?php endif; ?>
			<?php foreach ($photos as $i => $p) : ?>
			<input type="hidden" name="ig_img[<?php echo $i; ?>]" value="<?php echo esc_attr($p['img']); ?>" />
			<input type="hidden" name="ig_url[<?php echo $i; ?>]" value="<?php echo esc_attr($p['url']??''); ?>" />
			<input type="hidden" name="ig_cap[<?php echo $i; ?>]" value="<?php echo esc_attr($p['cap']??''); ?>" />
			<?php endforeach; ?>
			<button type="submit" class="button <?php echo $show ? 'button-secondary' : 'button-primary'; ?>" style="<?php echo $show ? '' : 'background:#166534;border-color:#14532d;color:#fff;'; ?>">
				<?php echo $show ? '🚫 Hide from Homepage' : '✅ Show on Homepage'; ?>
			</button>
		</form>
	</div>
	<nav class="nav-tab-wrapper">
		<a href="?page=wk-instagram-grid&tab=photos" class="nav-tab <?php echo $active==='photos'?'nav-tab-active':''; ?>">🖼️ Photos</a>
		<a href="?page=wk-instagram-grid&tab=settings" class="nav-tab <?php echo $active==='settings'?'nav-tab-active':''; ?>">⚙️ Settings</a>
		<a href="?page=wk-instagram-grid&tab=preview" class="nav-tab <?php echo $active==='preview'?'nav-tab-active':''; ?>">👁️ Preview</a>
	</nav>

	<style>
	.wk-ig-admin{background:#fff;border:1px solid #ddd;border-top:none;padding:24px;}
	.wk-ig-photo-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;}
	@media(min-width:900px){.wk-ig-photo-grid{grid-template-columns:repeat(4,1fr);}}
	.wk-ig-photo-item{border:2px dashed #ddd;border-radius:8px;overflow:hidden;position:relative;background:#f9f9f9;transition:.2s;}
	.wk-ig-photo-item:hover{border-color:#6B1E3E;}
	.wk-ig-photo-item__img{width:100%;aspect-ratio:1;object-fit:cover;display:block;cursor:pointer;}
	.wk-ig-photo-item__empty{width:100%;aspect-ratio:1;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;gap:8px;color:#bbb;font-size:12px;}
	.wk-ig-photo-item__empty svg{opacity:.4;}
	.wk-ig-photo-item__meta{padding:10px;background:#fff;border-top:1px solid #eee;}
	.wk-ig-photo-item__meta input{width:100%;padding:6px 8px;border:1px solid #ddd;border-radius:3px;font-size:12px;box-sizing:border-box;margin-bottom:6px;}
	.wk-ig-photo-item__meta input:last-child{margin-bottom:0;}
	.wk-ig-photo-item__remove{position:absolute;top:6px;right:6px;background:rgba(255,255,255,.9);border:none;border-radius:50%;width:24px;height:24px;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;line-height:1;transition:.15s;}
	.wk-ig-photo-item__remove:hover{background:#d00;color:#fff;}
	.wk-ig-num{position:absolute;top:6px;left:6px;background:rgba(0,0,0,.5);color:#fff;width:22px;height:22px;border-radius:50%;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;}
	.wk-ig-form-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;}
	.wk-ig-field label{display:block;font-weight:700;font-size:11px;text-transform:uppercase;color:#555;margin-bottom:5px;}
	.wk-ig-field input,.wk-ig-field select{width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;box-sizing:border-box;}
	.wk-ig-add-btn{border:2px dashed #6B1E3E;border-radius:8px;display:flex;align-items:center;justify-content:center;aspect-ratio:1;cursor:pointer;background:none;width:100%;color:#6B1E3E;font-size:13px;font-weight:600;flex-direction:column;gap:8px;transition:.15s;}
	.wk-ig-add-btn:hover{background:#f5eaf0;}
	.wk-ig-preview-grid{display:grid;gap:3px;}
	.wk-ig-preview-tile{aspect-ratio:1;overflow:hidden;background:#f0f0f0;}
	.wk-ig-preview-tile img{width:100%;height:100%;object-fit:cover;display:block;}
	</style>

	<form method="post">
	<?php wp_nonce_field('wk_ig_save','wk_ig_nonce'); ?>

	<?php if ($active === 'photos') : ?>
	<div class="wk-ig-admin">
		<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
			<div>
				<h2 style="margin:0 0 4px;">Photo Grid <span style="font-size:13px;font-weight:400;color:#888;"><?php echo count($photos); ?>/9 photos</span></h2>
				<p style="margin:0;font-size:12px;color:#888;">Click any cell to upload or change. Drag ≡ to reorder. Add up to 9 photos.</p>
			</div>
		</div>
		<div class="wk-ig-photo-grid" id="wk-ig-photo-grid">
		<?php
		// Pad to at least 6 slots or current count + 1
		$slot_count = max(6, count($photos) + 1, 9);
		$slot_count = min($slot_count, 9);
		for ($i = 0; $i < $slot_count; $i++) :
			$p = $photos[$i] ?? null;
		?>
		<div class="wk-ig-photo-item" data-idx="<?php echo $i; ?>">
			<span class="wk-ig-num"><?php echo $i + 1; ?></span>
			<?php if ($p && $p['img']) : ?>
				<img src="<?php echo esc_url($p['img']); ?>" class="wk-ig-photo-item__img wk-ig-click-upload" title="Click to change image" />
				<button type="button" class="wk-ig-photo-item__remove wk-ig-remove" title="Remove">×</button>
			<?php else : ?>
				<div class="wk-ig-photo-item__empty wk-ig-click-upload">
					<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
					<span>Click to upload</span>
				</div>
			<?php endif; ?>
			<input type="hidden" name="ig_img[<?php echo $i; ?>]" class="ig-img-url" value="<?php echo esc_attr($p['img'] ?? ''); ?>" />
			<div class="wk-ig-photo-item__meta">
				<input type="url" name="ig_url[<?php echo $i; ?>]" value="<?php echo esc_attr($p['url'] ?? ''); ?>" placeholder="Link URL (optional)" />
				<input type="text" name="ig_cap[<?php echo $i; ?>]" value="<?php echo esc_attr($p['cap'] ?? ''); ?>" placeholder="Caption (optional)" />
			</div>
		</div>
		<?php endfor; ?>
		</div>
		<input type="submit" class="button button-primary" value="Save Photos" style="background:#6B1E3E;border-color:#4a1228;padding:10px 24px;font-size:14px;" />
	</div>

	<?php elseif ($active === 'settings') : ?>
	<div class="wk-ig-admin">
		<h2 style="margin:0 0 20px;">Section Settings</h2>
		<table class="form-table">
			<tr>
				<th>Show on Homepage</th>
				<td><label><input type="checkbox" name="ig_show" value="1" <?php checked($show,true); ?> /> Show Instagram grid on homepage</label></td>
			</tr>
			<tr>
				<th>Section Title</th>
				<td><input type="text" name="ig_section_title" value="<?php echo esc_attr($title); ?>" style="width:400px;" placeholder="📸 Follow Our World" /></td>
			</tr>
			<tr>
				<th>Instagram Handle</th>
				<td><input type="text" name="ig_handle" value="<?php echo esc_attr($handle); ?>" style="width:200px;" placeholder="@yourhandle" /></td>
			</tr>
			<tr>
				<th>Follow Button URL</th>
				<td><input type="url" name="ig_btn_url" value="<?php echo esc_attr($btn_url); ?>" style="width:400px;" placeholder="https://instagram.com/..." /></td>
			</tr>
		</table>
		<!-- Save hidden fields for photos (preserve them) -->
		<?php foreach ($photos as $i => $p) : ?>
		<input type="hidden" name="ig_img[<?php echo $i; ?>]" value="<?php echo esc_attr($p['img']); ?>" />
		<input type="hidden" name="ig_url[<?php echo $i; ?>]" value="<?php echo esc_attr($p['url']??''); ?>" />
		<input type="hidden" name="ig_cap[<?php echo $i; ?>]" value="<?php echo esc_attr($p['cap']??''); ?>" />
		<?php endforeach; ?>
		<input type="submit" class="button button-primary" value="Save Settings" style="background:#6B1E3E;border-color:#4a1228;padding:10px 24px;font-size:14px;" />
	</div>

	<?php elseif ($active === 'preview') : ?>
	<div class="wk-ig-admin">
		<h2 style="margin:0 0 16px;">Grid Preview</h2>
		<p style="color:#888;font-size:12px;margin:0 0 16px;">Live preview of how your grid will appear. Uses current saved photos.</p>
		<?php if (!is_array($photos)) $photos = []; $preview_photos = array_filter($photos, function($p) { return !empty($p['img']); }); ?>
		<?php if (empty($preview_photos)) : ?>
		<div style="text-align:center;padding:40px;color:#888;border:1px dashed #ddd;border-radius:8px;">
			<p>No photos added yet. <a href="?page=wk-instagram-grid&tab=photos">Add photos →</a></p>
		</div>
		<?php else : ?>
		<div style="background:#f4f0ea;padding:20px;border-radius:8px;">
			<!-- Instagram section header preview -->
			<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
				<div style="display:flex;align-items:center;gap:10px;">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="2" width="20" height="20" rx="5.5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none"/></svg>
					<div>
						<strong><?php echo esc_html($title); ?></strong><br>
						<span style="font-size:11px;color:#888;"><?php echo esc_html($handle); ?></span>
					</div>
				</div>
				<?php if ($btn_url) : ?><a href="<?php echo esc_url($btn_url); ?>" class="button button-small">Follow Us</a><?php endif; ?>
			</div>
			<!-- Grid -->
			<div class="wk-ig-preview-grid" style="grid-template-columns:repeat(<?php echo min(count($preview_photos),3); ?>,1fr);">
				<?php foreach (array_slice($preview_photos,0,9) as $p) : ?>
				<div class="wk-ig-preview-tile">
					<img src="<?php echo esc_url($p['img']); ?>" alt="<?php echo esc_attr($p['cap']??''); ?>" />
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
		<!-- No form fields needed on preview tab -->
	</div>
	<?php endif; ?>
	</form>
	</div>

	<script>
	jQuery(function($){
		// Upload on click
		$(document).on('click', '.wk-ig-click-upload', function(){
			var $item = $(this).closest('.wk-ig-photo-item');
			var f = wp.media({title:'Select Photo', multiple:false});
			f.on('select', function(){
				var a = f.state().get('selection').first().toJSON();
				$item.find('.ig-img-url').val(a.url);
				// Show image
				var $img = $item.find('.wk-ig-photo-item__img');
				if ($img.length) {
					$img.attr('src', a.url);
				} else {
					$item.find('.wk-ig-photo-item__empty').replaceWith('<img src="'+a.url+'" class="wk-ig-photo-item__img wk-ig-click-upload" />');
				}
				$item.find('.wk-ig-photo-item__remove').show();
			});
			f.open();
		});
		// Remove photo
		$(document).on('click', '.wk-ig-remove', function(e){
			e.stopPropagation();
			var $item = $(this).closest('.wk-ig-photo-item');
			$item.find('.ig-img-url').val('');
			$item.find('.wk-ig-photo-item__img').replaceWith('<div class="wk-ig-photo-item__empty wk-ig-click-upload"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg><span>Click to upload</span></div>');
			$(this).hide();
		});
		// Sortable reorder
		if ($.fn.sortable) {
			$('#wk-ig-photo-grid').sortable({
				handle: '.wk-ig-num',
				update: function() {
					$('#wk-ig-photo-grid .wk-ig-photo-item').each(function(i){
						$(this).find('.wk-ig-num').text(i+1);
					});
				}
			});
		}
	});
	</script>
<?php
} // end wk_ig_admin_page()

// ═══════════════════════════════════════════════════════════════
// FRONT-END: Instagram grid renderer (top-level function)
// ═══════════════════════════════════════════════════════════════
function wk_render_instagram_grid() {
	if (!get_theme_mod('wk_show_instagram_grid', true)) return;
	$photos  = wk_instagram_grid_get();
	if ( ! is_array( $photos ) ) $photos = [];
	$photos  = array_filter($photos, function($p) { return !empty($p['img']); });
	if (empty($photos)) return;
	$title   = get_theme_mod('wk_ig_title', '📸 Follow Our World');
	$handle  = get_theme_mod('wk_ig_handle', '@whitekurti');
	$btn_url = get_theme_mod('wk_ig_btn_url','');
	$ig_url  = get_theme_mod('wk_social_instagram_url','');
	$follow_url = $btn_url ?: $ig_url ?: '#';
	?>
	<section class="wk-instagram-section" aria-label="Instagram Feed">
		<div class="wk-instagram-header">
			<div class="wk-instagram-header__left">
				<svg class="wk-instagram-logo" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5.5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none"/></svg>
				<div>
					<h2 class="wk-instagram-title"><?php echo esc_html($title); ?></h2>
					<?php if ($handle) : ?><span class="wk-instagram-handle"><?php echo esc_html($handle); ?></span><?php endif; ?>
				</div>
			</div>
			<?php if ($follow_url && $follow_url !== '#') : ?>
			<a href="<?php echo esc_url($follow_url); ?>" class="wk-btn wk-btn--sm wk-btn--outline" target="_blank" rel="noopener noreferrer">
				Follow Us
			</a>
			<?php endif; ?>
		</div>
		<div class="wk-instagram-grid wk-ig-layout--uniform">
			<?php foreach (array_slice($photos,0,9) as $i => $photo) :
				$tag = !empty($photo['url']) ? 'a' : 'div';
				$attrs = !empty($photo['url']) ? 'href="'.esc_url($photo['url']).'" target="_blank" rel="noopener noreferrer"' : '';
			?>
			<<?php echo $tag; ?> class="wk-ig-tile wk-ig-tile--<?php echo $i; ?>" <?php echo $attrs; ?>>
				<img src="<?php echo esc_url($photo['img']); ?>"
				     alt="Instagram photo <?php echo $i+1; ?>"
				     loading="lazy"
				     class="wk-ig-tile__img" />
				<div class="wk-ig-tile__overlay">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5.5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none"/></svg>
				</div>
			</<?php echo $tag; ?>>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

// ── Customizer toggles for new sections ────────────────────────────────────
add_action('customize_register', function($wp_customize) {
	foreach ([
		['wk_show_lookbook',       '🖼️ Show Lookbook / Editorial Section',    'wk_homepage'],
		['wk_show_instagram_grid', '📸 Show Instagram Photo Grid',            'wk_homepage'],
		['wk_testimonials_subtitle','Testimonials Subtitle',                  'wk_homepage'],
	] as [$id,$label,$section]) {
		$type = strpos($id,'show_')!==false ? 'checkbox' : 'text';
		$default = $type==='checkbox' ? true : 'Loved by thousands of women across India';
		$wp_customize->add_setting($id,['default'=>$default,'sanitize_callback'=>$type==='checkbox'?'rest_sanitize_boolean':'sanitize_text_field','transport'=>'refresh']);
		$wp_customize->add_control($id,['label'=>$label,'section'=>$section,'type'=>$type]);
	}
});
