<?php
/**
 * WhiteKurti — Product Video & WhatsApp Share
 * - Upload/embed product video (YouTube/Vimeo/direct MP4) on PDP
 * - One-tap WhatsApp share button on product pages
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ═══════════════════════════════════════════════════════
// PRODUCT VIDEO
// ═══════════════════════════════════════════════════════

// Add video meta box on product edit screen
add_action( 'add_meta_boxes', 'wk_video_meta_box' );
function wk_video_meta_box() {
	if ( ! class_exists('WooCommerce') ) return; // guard OK
	add_meta_box(
		'wk_product_video',
		'🎬 Product Video',
		'wk_video_meta_box_html',
		'product',
		'side',
		'default'
	);
}

function wk_video_meta_box_html( $post ) {
	$video_url  = get_post_meta( $post->ID, '_wk_video_url',   true );
	$video_pos  = get_post_meta( $post->ID, '_wk_video_pos',   true ) ?: 'gallery';
	wp_nonce_field( 'wk_video_save', 'wk_video_nonce' );
	?>
	<p>
		<label style="display:block;font-size:11px;font-weight:600;margin-bottom:5px;">Video URL</label>
		<input type="url" name="wk_video_url" value="<?php echo esc_attr($video_url); ?>"
		       style="width:100%;padding:6px 8px;border:1px solid #ddd;border-radius:3px;font-size:12px;"
		       placeholder="YouTube, Vimeo, or .mp4 URL" />
	</p>
	<p>
		<label style="display:block;font-size:11px;font-weight:600;margin-bottom:5px;">Position</label>
		<select name="wk_video_pos" style="width:100%;padding:6px;border:1px solid #ddd;border-radius:3px;font-size:12px;">
			<option value="gallery" <?php selected($video_pos,'gallery'); ?>>In Image Gallery (last slide)</option>
			<option value="below"   <?php selected($video_pos,'below'); ?>>Below Product Details</option>
			<option value="tab"     <?php selected($video_pos,'tab'); ?>>In a Video Tab</option>
		</select>
	</p>
	<p style="font-size:11px;color:#888;">Paste a YouTube, Vimeo or direct .mp4 link. The video will auto-embed on the product page.</p>
	<?php
}

add_action( 'save_post_product', 'wk_video_save_meta' );
function wk_video_save_meta( $post_id ) {
	if ( ! isset($_POST['wk_video_nonce']) ) return;
	if ( ! wp_verify_nonce($_POST['wk_video_nonce'], 'wk_video_save') ) return;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
	if ( ! current_user_can('edit_post', $post_id) ) return;

	$url = esc_url_raw( $_POST['wk_video_url'] ?? '' );
	$pos = sanitize_text_field( $_POST['wk_video_pos'] ?? 'gallery' );

	if ( $url ) {
		update_post_meta( $post_id, '_wk_video_url', $url );
		update_post_meta( $post_id, '_wk_video_pos', $pos );
	} else {
		delete_post_meta( $post_id, '_wk_video_url' );
		delete_post_meta( $post_id, '_wk_video_pos' );
	}
}

// Render video on PDP
add_action( 'wp_footer', 'wk_video_render_pdp', 10 );
function wk_video_render_pdp() {
	if ( ! class_exists('WooCommerce') ) return;
	if ( ! is_singular('product') ) return;
	global $post;
	if ( ! $post ) return;

	$video_url = get_post_meta( $post->ID, '_wk_video_url', true );
	if ( ! $video_url ) return;

	$video_pos = get_post_meta( $post->ID, '_wk_video_pos', true ) ?: 'gallery';
	$embed     = wk_video_get_embed( $video_url );
	if ( ! $embed ) return;

	// Output inline via JS based on position setting
	?>
	<div id="wk-product-video" class="wk-product-video" data-pos="<?php echo esc_attr($video_pos); ?>" style="display:none;">
		<div class="wk-product-video__inner"><?php echo $embed; ?></div>
	</div>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		var videoEl = document.getElementById('wk-product-video');
		if (!videoEl) return;
		var pos = videoEl.dataset.pos;

		if (pos === 'below') {
			var infoEl = document.querySelector('.wk-pdp__info, .wk-pdp__layout');
			if (infoEl) {
				videoEl.style.display = 'block';
				videoEl.style.marginTop = '32px';
				infoEl.parentNode.insertBefore(videoEl, infoEl.nextSibling);
			}
		} else if (pos === 'gallery') {
			var gallery = document.querySelector('.wk-pdp__gallery');
			if (gallery) {
				var slide = document.createElement('div');
				slide.className = 'wk-gallery__img-wrapper wk-gallery__img-wrapper--video';
				slide.innerHTML = '<div class="wk-product-video__inner" style="width:100%;aspect-ratio:9/16;background:#000;">' + videoEl.querySelector('.wk-product-video__inner').innerHTML + '</div>';
				gallery.appendChild(slide);
				// Add video indicator dot
				var dots = document.querySelector('.wk-gallery-dots');
				if (dots) {
					var dot = document.createElement('button');
					dot.className = 'wk-gallery-dot';
					dot.setAttribute('aria-label', 'Product Video');
					dot.innerHTML = '▶';
					dot.style.fontSize = '8px';
					dots.appendChild(dot);
				}
			}
		} else if (pos === 'tab') {
			var tabsEl = document.querySelector('.wk-pdp__tabs, .woocommerce-tabs');
			if (tabsEl) {
				videoEl.style.display = 'block';
				videoEl.style.padding = '20px 0';
				var tabBtn = document.createElement('button');
				tabBtn.className = 'wk-tab-btn';
				tabBtn.textContent = '🎬 Video';
				tabBtn.style.cssText = 'background:none;border:none;border-bottom:2px solid var(--accent,#6B1E3E);padding:10px 16px;font-size:13px;font-weight:600;cursor:pointer;color:var(--accent,#6B1E3E);';
				tabsEl.prepend(tabBtn);
				tabsEl.after(videoEl);
			}
		}
	});
	</script>
	<style>
	.wk-product-video { width:100%; }
	.wk-product-video__inner { width:100%; aspect-ratio:16/9; background:#000; border-radius:8px; overflow:hidden; }
	.wk-product-video__inner iframe,
	.wk-product-video__inner video { width:100%; height:100%; border:none; display:block; }
	</style>
	<?php
}

function wk_video_get_embed( $url ) {
	// YouTube
	if ( preg_match( '/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m ) ) {
		return '<iframe src="https://www.youtube.com/embed/' . esc_attr($m[1]) . '?rel=0&playsinline=1" allowfullscreen allow="autoplay"></iframe>';
	}
	// Vimeo
	if ( preg_match( '/vimeo\.com\/(\d+)/', $url, $m ) ) {
		return '<iframe src="https://player.vimeo.com/video/' . esc_attr($m[1]) . '?title=0&byline=0" allowfullscreen></iframe>';
	}
	// Direct video file
	if ( preg_match( '/\.(mp4|webm|ogg)(\?|$)/i', $url ) ) {
		return '<video src="' . esc_url($url) . '" controls playsinline style="width:100%;height:100%;object-fit:cover;"></video>';
	}
	return '';
}

// ═══════════════════════════════════════════════════════
// WHATSAPP SHARE ON PRODUCT PAGE
// ═══════════════════════════════════════════════════════

add_action( 'woocommerce_single_product_summary', 'wk_whatsapp_share_btn', 45 );
function wk_whatsapp_share_btn() {
	if ( ! class_exists('WooCommerce') ) return;
	if ( ! get_theme_mod('wk_wa_share_pdp', true) ) return;

	global $post, $product;
	if ( ! $product ) return;

	$title   = $product->get_name();
	$price   = wc_price( $product->get_price() );
	$url     = get_permalink();
	$msg     = get_theme_mod( 'wk_wa_share_msg', "Check out {title} at {price}! {url}" );
	$msg     = str_replace( [ '{title}', '{price}', '{url}' ], [ $title, strip_tags($price), $url ], $msg );
	$encoded = urlencode( $msg );
	$wa_url  = 'https://wa.me/?text=' . $encoded;
	?>
	<a href="<?php echo esc_url($wa_url); ?>" target="_blank" rel="noopener noreferrer"
	   class="wk-wa-share" aria-label="Share on WhatsApp">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="#25D366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
		<?php echo esc_html( get_theme_mod('wk_wa_share_label', 'Share on WhatsApp') ); ?>
	</a>
	<style>
	.wk-wa-share {
		display:inline-flex; align-items:center; gap:8px;
		background:#25D366; color:#fff !important; text-decoration:none;
		padding:10px 20px; border-radius:6px; font-size:13px; font-weight:600;
		margin-top:8px; transition:background .2s;
	}
	.wk-wa-share:hover { background:#1da851; }
	</style>
	<?php
}

// Customizer settings for WhatsApp share
add_action( 'customize_register', 'wk_wa_share_customizer' );
function wk_wa_share_customizer( $wp_customize ) {
	$wp_customize->add_section( 'wk_wa_share', [
		'title'    => '💬 WhatsApp Share (Product Page)',
		'panel'    => 'wk_panel',
		'priority' => 72,
	] );
	$settings = [
		[ 'wk_wa_share_pdp',   'Show Share Button on Product Page', 'checkbox', true ],
		[ 'wk_wa_share_label', 'Button Label',  'text', 'Share on WhatsApp' ],
		[ 'wk_wa_share_msg',   'Share Message (use {title}, {price}, {url})', 'textarea',
		  'Check out {title} at {price}! Shop here: {url}' ],
	];
	foreach ( $settings as $s ) {
		$wp_customize->add_setting( $s[0], [ 'default' => $s[3], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
		$wp_customize->add_control( $s[0], [ 'label' => $s[1], 'section' => 'wk_wa_share', 'type' => $s[2] ] );
	}
}
