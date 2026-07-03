<?php
/**
 * WhiteKurti — Product Swatches
 * Shows color + size swatches on archive/category product cards.
 * Admin panel to configure appearance.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Show swatches below product card image on archive pages ──────────────────
add_action( 'woocommerce_after_shop_loop_item_title', 'wk_swatches_on_card', 8 );
function wk_swatches_on_card() {
	if ( ! class_exists('WooCommerce') ) return;
	global $product;
	if ( ! $product || ! ( $product instanceof WC_Product ) || ! $product->is_type( 'variable' ) ) return;

	$show_color = get_theme_mod( 'wk_swatches_show_color', true );
	$show_size  = get_theme_mod( 'wk_swatches_show_size', true );
	$max        = (int) get_theme_mod( 'wk_swatches_max', 6 );

	$color_attrs = [ 'color', 'colour', 'rang', 'pa_color', 'pa_colour' ];
	$size_attrs  = [ 'size', 'sizes', 'pa_size' ];

	$color_html = '';
	$size_html  = '';

	if ( $show_color ) {
		foreach ( $color_attrs as $attr ) {
			$terms = wk_swatches_get_terms( $product, $attr );
			if ( ! empty( $terms ) ) {
				$color_html = wk_swatches_render_colors( $terms, $max );
				break;
			}
		}
	}

	if ( $show_size ) {
		foreach ( $size_attrs as $attr ) {
			$terms = wk_swatches_get_terms( $product, $attr );
			if ( ! empty( $terms ) ) {
				$size_html = wk_swatches_render_sizes( $terms, $max );
				break;
			}
		}
	}

	if ( ! $color_html && ! $size_html ) return;

	echo '<div class="wk-swatches">';
	echo $color_html;
	echo $size_html;
	echo '</div>';
}

function wk_swatches_get_terms( $product, $attr_key ) {
	if ( ! class_exists('WooCommerce') || ! ( $product instanceof WC_Product ) ) return [];
	// Try taxonomy attribute first
	$tax = strpos( $attr_key, 'pa_' ) === 0 ? $attr_key : 'pa_' . $attr_key;
	$terms = wc_get_product_terms( $product->get_id(), $tax, [ 'fields' => 'all' ] );
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) return $terms;

	// Try custom attribute
	$attrs = $product->get_variation_attributes();
	foreach ( $attrs as $key => $values ) {
		if ( strtolower( wc_attribute_label( $key ) ) === strtolower( $attr_key )
			|| strtolower( $key ) === strtolower( $attr_key )
			|| strtolower( $key ) === strtolower( 'pa_' . $attr_key ) ) {
			return is_array( $values ) ? $values : [];
		}
	}
	return [];
}

function wk_swatches_render_colors( $terms, $max ) {
	$html  = '<div class="wk-swatches__colors">';
	$count = 0;
	$extra = 0;

	foreach ( $terms as $term ) {
		if ( $count >= $max ) { $extra++; continue; }
		$name  = is_object( $term ) ? $term->name : $term;
		$slug  = is_object( $term ) ? $term->slug : sanitize_title( $term );
		$color = wk_swatches_name_to_hex( $name );
		$style = $color ? "background:{$color};" : '';
		$html .= '<span class="wk-swatch wk-swatch--color" style="' . esc_attr($style) . '" title="' . esc_attr($name) . '" data-slug="' . esc_attr($slug) . '"></span>';
		$count++;
	}

	if ( $extra > 0 ) {
		$html .= '<span class="wk-swatch wk-swatch--more">+' . $extra . '</span>';
	}
	$html .= '</div>';
	return $html;
}

function wk_swatches_render_sizes( $terms, $max ) {
	$html  = '<div class="wk-swatches__sizes">';
	$count = 0;
	$extra = 0;

	foreach ( $terms as $term ) {
		if ( $count >= $max ) { $extra++; continue; }
		$name = is_object( $term ) ? $term->name : $term;
		$slug = is_object( $term ) ? $term->slug : sanitize_title( $term );
		$html .= '<span class="wk-swatch wk-swatch--size" data-slug="' . esc_attr($slug) . '">' . esc_html( strtoupper($name) ) . '</span>';
		$count++;
	}

	if ( $extra > 0 ) {
		$html .= '<span class="wk-swatch wk-swatch--more">+' . $extra . '</span>';
	}
	$html .= '</div>';
	return $html;
}

// Map common color names to hex
function wk_swatches_name_to_hex( $name ) {
	$map = [
		'white'      => '#ffffff',
		'black'      => '#111111',
		'red'        => '#dc2626',
		'blue'       => '#2563eb',
		'navy'       => '#1e3a5f',
		'green'      => '#16a34a',
		'yellow'     => '#fbbf24',
		'orange'     => '#f97316',
		'pink'       => '#ec4899',
		'purple'     => '#9333ea',
		'violet'     => '#7c3aed',
		'maroon'     => '#7f1d1d',
		'brown'      => '#78350f',
		'grey'       => '#6b7280',
		'gray'       => '#6b7280',
		'beige'      => '#d4b896',
		'cream'      => '#fef9c3',
		'ivory'      => '#fffff0',
		'off white'  => '#f5f5f0',
		'off-white'  => '#f5f5f0',
		'mustard'    => '#d97706',
		'rust'       => '#b45309',
		'teal'       => '#0d9488',
		'turquoise'  => '#14b8a6',
		'gold'       => '#d4af37',
		'silver'     => '#c0c0c0',
		'peach'      => '#ffb347',
		'lavender'   => '#b57bee',
		'mint'       => '#3eb489',
		'coral'      => '#ff6b6b',
		'magenta'    => '#e040fb',
		'lemon'      => '#fff44f',
		'indigo'     => '#4f46e5',
		'charcoal'   => '#374151',
		'chocolate'  => '#5d4037',
		'khaki'      => '#c5b358',
		'olive'      => '#6b7c2d',
		'rose'       => '#f43f5e',
		'wine'       => '#722f37',
	];
	$key = strtolower( trim( $name ) );
	return $map[$key] ?? '';
}

// ── Enqueue swatches CSS ─────────────────────────────────────────────────────
add_action( 'wp_head', 'wk_swatches_css' );
function wk_swatches_css() {
	$size       = (int) get_theme_mod( 'wk_swatches_color_size', 18 );
	$size_w     = (int) get_theme_mod( 'wk_swatches_size_width', 28 );
	$border_r   = get_theme_mod( 'wk_swatches_shape', 'circle' ) === 'circle' ? '50%' : '3px';
	$gap        = 4;
	?>
	<style id="wk-swatches-css">
	.wk-swatches { display:flex; flex-direction:column; gap:5px; padding:6px 0 2px; }
	.wk-swatches__colors,
	.wk-swatches__sizes { display:flex; align-items:center; gap:<?php echo $gap; ?>px; flex-wrap:wrap; }
	.wk-swatch { display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:transform .15s,box-shadow .15s; }
	.wk-swatch:hover { transform:scale(1.15); }
	.wk-swatch--color {
		width:<?php echo $size; ?>px; height:<?php echo $size; ?>px;
		border-radius:<?php echo $border_r; ?>;
		border:1.5px solid rgba(0,0,0,.12);
		box-shadow:inset 0 0 0 1px rgba(255,255,255,.4);
		background:#ddd;
	}
	.wk-swatch--size {
		min-width:<?php echo $size_w; ?>px; height:22px; padding:0 5px;
		border-radius:3px; border:1px solid #d1d5db;
		font-size:10px; font-weight:600; color:#374151; background:#f9fafb;
		white-space:nowrap;
	}
	.wk-swatch--more {
		font-size:10px; color:#9ca3af; font-weight:500; padding:0 2px;
	}
	.wk-swatch--color[style*="background:#ffffff"],
	.wk-swatch--color[style*="background:#fffff0"],
	.wk-swatch--color[style*="background:#fef9c3"] {
		border-color:#d1d5db;
	}
	@media (max-width:767px) {
		.wk-swatch--color { width:16px; height:16px; }
		.wk-swatch--size  { min-width:24px; height:20px; font-size:9px; }
	}
	</style>
	<?php
}

// ── Customizer settings ──────────────────────────────────────────────────────
add_action( 'customize_register', 'wk_swatches_customizer' );
function wk_swatches_customizer( $wp_customize ) {
	$wp_customize->add_section( 'wk_swatches', [
		'title'    => '🎨 Product Swatches',
		'panel'    => 'wk_panel',
		'priority' => 45,
	] );
	$settings = [
		[ 'wk_swatches_show_color', 'Show Color Swatches on Cards', 'checkbox', true ],
		[ 'wk_swatches_show_size',  'Show Size Swatches on Cards',  'checkbox', true ],
		[ 'wk_swatches_max',        'Max Swatches per Type',        'number',   6    ],
		[ 'wk_swatches_color_size', 'Color Swatch Size (px)',       'number',   18   ],
		[ 'wk_swatches_size_width', 'Size Swatch Min Width (px)',   'number',   28   ],
		[ 'wk_swatches_shape',      'Color Swatch Shape',           'select',   'circle' ],
	];
	foreach ( $settings as $s ) {
		$wp_customize->add_setting( $s[0], [ 'default' => $s[3], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
		$args = [ 'label' => $s[1], 'section' => 'wk_swatches', 'type' => $s[2] ];
		if ( $s[2] === 'select' ) $args['choices'] = [ 'circle' => 'Circle', 'rounded' => 'Rounded Square' ];
		$wp_customize->add_control( $s[0], $args );
	}
}
