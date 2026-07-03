<?php
/**
 * WhiteKurti — Product Image Zoom System
 * Desktop: hover lens zoom panel | Mobile: native pinch-to-zoom + swipe gallery
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Customizer settings ───────────────────────────────────────────────────────
add_action( 'customize_register', function( $wp_customize ) {
	$wp_customize->add_section( 'wk_product_zoom', [
		'title'    => __( '🔍 Product Image Zoom', 'whitekurti' ),
		'panel'    => 'wk_panel',
		'priority' => 46,
	] );
	$fields = [
		[ 'wk_zoom_enabled',         'Enable Image Zoom',          'checkbox', true,    '' ],
		[ 'wk_zoom_desktop_type',    'Desktop Zoom Type',          'select',   'lens',  '' ],
		[ 'wk_zoom_lens_size',       'Zoom Lens Size (px)',        'number',   120,     'Size of the hover lens circle' ],
		[ 'wk_zoom_magnify',         'Magnification Level',       'number',   2,       '2 = 2x zoom, 3 = 3x zoom (1.5–4)' ],
		[ 'wk_zoom_panel_position',  'Zoom Panel Position',       'select',   'right', '' ],
	];
	foreach ( $fields as [$id, $label, $type, $default, $desc] ) {
		$san = $type==='checkbox' ? 'rest_sanitize_boolean' : 'sanitize_text_field';
		$wp_customize->add_setting($id, ['default'=>$default,'sanitize_callback'=>$san,'transport'=>'refresh']);
		$ctrl = ['label'=>$label,'description'=>$desc,'section'=>'wk_product_zoom','type'=>$type,'priority'=>10];
		if ($type==='select' && $id==='wk_zoom_desktop_type') $ctrl['choices'] = ['lens'=>'Lens (hover magnifier)','expand'=>'Click to expand fullscreen'];
		if ($type==='select' && $id==='wk_zoom_panel_position') $ctrl['choices'] = ['right'=>'Beside image (right)','inner'=>'Inside image (overlay)'];
		$wp_customize->add_control($id, $ctrl);
	}
} );

// ── Inject zoom data attributes into gallery images ───────────────────────────
function wk_add_zoom_data_to_gallery() {
	if ( ! get_theme_mod('wk_zoom_enabled', true) ) return;
	if ( ! is_product() ) return;

	$zoom_type = get_theme_mod('wk_zoom_desktop_type', 'lens');
	$lens_size = absint(get_theme_mod('wk_zoom_lens_size', 120));
	$magnify   = max(1.5, min(4, (float)get_theme_mod('wk_zoom_magnify', 2)));
	$panel_pos = get_theme_mod('wk_zoom_panel_position', 'right');

	wp_localize_script('wk-main', 'wk_zoom_cfg', [
		'enabled'   => '1',
		'type'      => $zoom_type,
		'lens_size' => $lens_size,
		'magnify'   => $magnify,
		'panel_pos' => $panel_pos,
	]);
}
add_action('wp_enqueue_scripts', 'wk_add_zoom_data_to_gallery', 20);

// ── Add full-size image URLs to gallery wrappers (needed for zoom) ─────────────
add_filter('wp_get_attachment_image_attributes', function($attr, $attachment, $size) {
	if (!is_product()) return $attr;
	if (strpos($attr['class']??'', 'wk-gallery__img') === false) return $attr;
	$full = wp_get_attachment_image_src($attachment->ID, 'full');
	if ($full) $attr['data-zoom-src'] = $full[0];
	return $attr;
}, 10, 3);
