<?php
/**
 * WhiteKurti Pro Features — Category Images + Timer Bar only
 * WhatsApp: see inc/whatsapp-admin.php
 * Notifications: see inc/fake-notifications-admin.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ═══════════════════════════════════════════════════════════════
// COUNTDOWN TIMER BAR
// ═══════════════════════════════════════════════════════════════
function wk_timer_bar() {
	if ( ! get_theme_mod( 'wk_timer_enabled', false ) ) return;

	$mode       = get_theme_mod( 'wk_timer_mode', 'fixed' );
	$end_date   = get_theme_mod( 'wk_timer_end_datetime', '' );
	$duration   = absint( get_theme_mod( 'wk_timer_session_mins', 30 ) );
	$text       = get_theme_mod( 'wk_timer_text', '🔥 Limited Time Offer — Ends In:' );
	$bg         = get_theme_mod( 'wk_timer_bg', '#8B1A4A' );
	$text_color = get_theme_mod( 'wk_timer_text_color', '#ffffff' );
	$font_size  = absint( get_theme_mod( 'wk_timer_font_size', 13 ) );
	$font_style = get_theme_mod( 'wk_timer_font_style', 'normal' );

	$end_ts    = ( $mode === 'fixed' && $end_date ) ? strtotime( $end_date ) : 0;
	$transform = $font_style === 'uppercase' ? 'uppercase' : 'none';
	$italic    = $font_style === 'italic' ? 'italic' : 'normal';
	?>
	<div class="wk-timer-bar" id="wk-timer-bar" role="timer" aria-live="polite"
	     style="background:<?php echo esc_attr($bg); ?>;color:<?php echo esc_attr($text_color); ?>;font-size:<?php echo $font_size; ?>px;font-style:<?php echo $italic; ?>;text-transform:<?php echo $transform; ?>">
		<span class="wk-timer-bar__text"><?php echo esc_html($text); ?></span>
		<span class="wk-timer-bar__countdown" id="wk-timer-countdown"
		      data-mode="<?php echo esc_attr($mode); ?>"
		      data-end="<?php echo esc_attr($end_ts); ?>"
		      data-session-mins="<?php echo absint($duration); ?>">
			<span class="wk-tc-segment"><span id="wk-tc-h">00</span><span class="wk-tc-label">h</span></span>
			<span class="wk-tc-sep">:</span>
			<span class="wk-tc-segment"><span id="wk-tc-m">00</span><span class="wk-tc-label">m</span></span>
			<span class="wk-tc-sep">:</span>
			<span class="wk-tc-segment"><span id="wk-tc-s">00</span><span class="wk-tc-label">s</span></span>
		</span>
		<button class="wk-timer-bar__close" id="wk-timer-close" aria-label="Close" style="color:<?php echo esc_attr($text_color); ?>">&times;</button>
	</div>
	<?php
}
add_action( 'wp_body_open', 'wk_timer_bar', 1 );

// ═══════════════════════════════════════════════════════════════
// PRODUCT CATEGORY IMAGES
// ═══════════════════════════════════════════════════════════════
add_action( 'init', function() {
	if ( ! class_exists('WooCommerce') ) return;
	add_action( 'product_cat_add_form_fields',  'wk_cat_image_add_field' );
	add_action( 'product_cat_edit_form_fields', 'wk_cat_image_edit_field', 10 );
	add_action( 'created_product_cat', 'wk_cat_image_save' );
	add_action( 'edited_product_cat',  'wk_cat_image_save' );
}, 20 );

function wk_cat_image_js_inline() { ?>
<script>
jQuery(function($){
  var frame;
  $('#wk-cat-upload-btn').on('click', function(e){
    e.preventDefault();
    if (frame) { frame.open(); return; }
    frame = wp.media({ title:'Category Image', button:{text:'Use Image'}, multiple:false });
    frame.on('select', function(){
      var a = frame.state().get('selection').first().toJSON();
      $('#wk_cat_image_id').val(a.id);
      $('#wk_cat_image_url').val(a.url);
      $('#wk-cat-img-preview').html('<img src="'+a.url+'" style="max-width:200px;height:auto;display:block;border-radius:4px;margin-bottom:8px;" />');
      $('#wk-cat-remove-btn').show();
    });
    frame.open();
  });
  $('#wk-cat-remove-btn').on('click', function(){
    $('#wk_cat_image_id').val('');
    $('#wk_cat_image_url').val('');
    $('#wk-cat-img-preview').html('');
    $(this).hide();
  });
});
</script>
<?php }

function wk_cat_image_add_field() {
	wp_enqueue_media();
	?>
	<div class="form-field term-thumbnail-wrap">
		<label><?php _e('Category Image','whitekurti'); ?></label>
		<div id="wk-cat-img-preview"></div>
		<input type="hidden" id="wk_cat_image_id"  name="wk_cat_image_id"  value="" />
		<input type="hidden" id="wk_cat_image_url" name="wk_cat_image_url" value="" />
		<button type="button" class="button" id="wk-cat-upload-btn">Upload / Choose Image</button>
		<button type="button" class="button" id="wk-cat-remove-btn" style="display:none;margin-left:6px;">Remove</button>
		<p class="description">Recommended: 600×750px. Displayed on homepage category circles.</p>
		<?php wk_cat_image_js_inline(); ?>
	</div>
	<?php
}

function wk_cat_image_edit_field( $term ) {
	wp_enqueue_media();
	$img_id  = get_term_meta( $term->term_id, 'wk_cat_image_id',  true );
	$img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'medium' ) : get_term_meta( $term->term_id, 'wk_cat_image_url', true );
	?>
	<tr class="form-field term-thumbnail-wrap">
		<th><label><?php _e('Category Image','whitekurti'); ?></label></th>
		<td>
			<div id="wk-cat-img-preview"><?php if ($img_url) echo '<img src="'.esc_url($img_url).'" style="max-width:200px;height:auto;display:block;border-radius:4px;margin-bottom:8px;" />'; ?></div>
			<input type="hidden" id="wk_cat_image_id"  name="wk_cat_image_id"  value="<?php echo esc_attr($img_id); ?>" />
			<input type="hidden" id="wk_cat_image_url" name="wk_cat_image_url" value="<?php echo esc_attr($img_url); ?>" />
			<button type="button" class="button" id="wk-cat-upload-btn">Upload / Change Image</button>
			<button type="button" class="button" id="wk-cat-remove-btn" <?php echo $img_url ? '' : 'style="display:none;"'; ?> style="margin-left:6px;">Remove</button>
			<p class="description">Recommended: 600×750px. Used on homepage category circles.</p>
			<?php wk_cat_image_js_inline(); ?>
		</td>
	</tr>
	<?php
}

function wk_cat_image_save( $term_id ) {
	if ( isset($_POST['wk_cat_image_id']) )  update_term_meta( $term_id, 'wk_cat_image_id',  absint($_POST['wk_cat_image_id']) );
	if ( isset($_POST['wk_cat_image_url']) ) update_term_meta( $term_id, 'wk_cat_image_url', esc_url_raw($_POST['wk_cat_image_url']) );
}

function wk_get_category_image( $term_id, $size = 'wk-category-card' ) {
	$img_id = get_term_meta( $term_id, 'wk_cat_image_id', true );
	if ( $img_id ) {
		$url = wp_get_attachment_image_url( $img_id, $size );
		if ( $url ) return $url;
	}
	if ( class_exists('WooCommerce') ) {
		$thumb_id = get_term_meta( $term_id, 'thumbnail_id', true );
		if ( $thumb_id ) {
			$url = wp_get_attachment_image_url( $thumb_id, $size );
			if ( $url ) return $url;
		}
	}
	$custom_url = get_term_meta( $term_id, 'wk_cat_image_url', true );
	if ( $custom_url ) return $custom_url;
	$fallbacks = [
		'https://images.pexels.com/photos/13178920/pexels-photo-13178920.jpeg?w=600&h=750&fit=crop',
		'https://images.pexels.com/photos/13998716/pexels-photo-13998716.jpeg?w=600&h=750&fit=crop',
		'https://images.pexels.com/photos/26984710/pexels-photo-26984710.jpeg?w=600&h=750&fit=crop',
	];
	return $fallbacks[ $term_id % 3 ];
}
add_action( 'admin_enqueue_scripts', function($hook){
	global $post_type;
	if ($post_type === 'wk_review') wp_enqueue_media();
});
