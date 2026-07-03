<?php
/**
 * WhiteKurti — inc/woocommerce.php
 * Only loaded when WooCommerce is active (see functions.php).
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'WooCommerce' ) ) return;

// ─── Wrappers (replace default WC main content wrappers) ──────────────────
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content',  'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar',             'woocommerce_get_sidebar', 10 );

add_action( 'woocommerce_before_main_content', function() { echo '<main id="wk-main" class="wk-woo-main">'; }, 10 );
add_action( 'woocommerce_after_main_content',  function() { echo '</main>'; }, 10 );

// ─── Products per page & columns ──────────────────────────────────────────
add_filter( 'loop_shop_per_page', function() { return (int) get_theme_mod( 'wk_products_per_page', 12 ); }, 20 );
add_filter( 'loop_shop_columns',  function() { return max( 1, (int) get_theme_mod( 'wk_card_columns', 2 ) ); }, 20 );

// ─── Remove default loop hooks we replace in content-product.php ──────────
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
remove_action( 'woocommerce_shop_loop_item_title',        'woocommerce_template_loop_product_title', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title',  'woocommerce_template_loop_rating', 5 );
remove_action( 'woocommerce_after_shop_loop_item_title',  'woocommerce_template_loop_price', 10 );
remove_action( 'woocommerce_after_shop_loop_item',        'woocommerce_template_loop_add_to_cart', 10 );

// ─── Star rating output ────────────────────────────────────────────────────
add_filter( 'woocommerce_product_get_rating_html', function( $html, $rating, $count ) {
	if ( ! $rating ) return '';
	$full  = (int) floor( $rating );
	$half  = ( $rating - $full ) >= 0.5;
	$stars = '';
	for ( $i = 1; $i <= 5; $i++ ) {
		$f = ( $i <= $full ) ? 'var(--accent)' : ( ( $half && $i === $full + 1 ) ? 'var(--accent)' : 'none' );
		$o = ( $half && $i === $full + 1 ) ? ' opacity=".5"' : '';
		$stars .= '<svg width="11" height="11" viewBox="0 0 24 24"'.$o.' aria-hidden="true"><polygon points="12 2 15 8.6 22.3 9.4 16.9 14.4 18.4 21.6 12 18 5.6 21.6 7.1 14.4 1.7 9.4 9 8.6" fill="'.$f.'" stroke="var(--accent)" stroke-width="1.5"/></svg>';
	}
	return '<div class="wk-stars" title="' . esc_attr( sprintf( __( 'Rated %s out of 5', 'whitekurti' ), $rating ) ) . '">' . $stars . '<span class="wk-rating-count">(' . absint( $count ) . ')</span></div>';
}, 10, 3 );

// ─── Sale badge ─────────────────────────────────────────────────────────────
add_filter( 'woocommerce_sale_flash', function( $html, $post, $product ) {
	if ( $product->is_on_sale() && $product->get_regular_price() ) {
		$pct = round( ( 1 - (float)$product->get_price() / (float)$product->get_regular_price() ) * 100 );
		return '<span class="wk-badge wk-badge--sale">&#x2212;' . $pct . '%</span>';
	}
	return $html;
}, 10, 3 );

// ─── Breadcrumb styling ────────────────────────────────────────────────────
add_filter( 'woocommerce_breadcrumb_defaults', function( $defaults ) {
	$defaults['delimiter']   = ' <span class="wk-bc-sep">&middot;</span> ';
	$defaults['wrap_before'] = '<nav class="wk-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'whitekurti' ) . '"><ol>';
	$defaults['wrap_after']  = '</ol></nav>';
	$defaults['before']      = '<li>';
	$defaults['after']       = '</li>';
	return $defaults;
} );

// ─── Variable Product Swatches ──────────────────────────────────────────────
add_filter('woocommerce_dropdown_variation_attribute_options_html', 'wk_custom_swatches', 10, 2);
function wk_custom_swatches($html, $args) {
    $options   = $args['options'];
    $product   = $args['product'];
    $attribute = $args['attribute'];
    $name      = ! empty( $args['name'] ) ? $args['name'] : 'attribute_' . sanitize_title($attribute);
    $id        = ! empty( $args['id'] ) ? $args['id'] : sanitize_title($attribute);
    $class     = $args['class'] ?? '';
    $show_option_none = ! empty( $args['show_option_none'] ) ? true : false;
    
    if (empty($options) || !empty($args['name']) && $args['name'] === 'qty') {
        return $html;
    }
    
    // Original select hidden for WooCommerce JS compatibility
    $html = '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . ' wk-hidden-select" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '" style="display:none;">';
    $html .= '<option value="">' . esc_html__( 'Choose an option', 'woocommerce' ) . '</option>';
    if ( ! empty( $options ) ) {
        if ( $product && taxonomy_exists( $attribute ) ) {
            $terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );
            foreach ( $terms as $term ) {
                if ( in_array( $term->slug, $options, true ) ) {
                    $html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
                }
            }
        } else {
            foreach ( $options as $option ) {
                $selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
                $html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
            }
        }
    }
    $html .= '</select>';
    
    // Custom swatches UI
    $html .= '<div class="wk-swatches" data-select-id="' . esc_attr($id) . '">';
    if ( ! empty( $options ) ) {
        if ( $product && taxonomy_exists( $attribute ) ) {
            $terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );
            foreach ( $terms as $term ) {
                if ( in_array( $term->slug, $options, true ) ) {
                    $is_selected = (sanitize_title($args['selected'] ?? '') === $term->slug);
                    $html .= '<button type="button" class="wk-swatch ' . ($is_selected ? 'is-selected' : '') . '" data-value="' . esc_attr($term->slug) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</button>';
                }
            }
        } else {
            foreach ( $options as $option ) {
                $is_selected = (sanitize_title($args['selected'] ?? '') === sanitize_title($option));
                $html .= '<button type="button" class="wk-swatch ' . ($is_selected ? 'is-selected' : '') . '" data-value="' . esc_attr($option) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</button>';
            }
        }
    }
    $html .= '</div>';
    
    return $html;
}

// ─── Add proper WC loop hooks for plugin compatibility ─────────────────────
// These are called from archive-product.php explicitly, but also ensure
// they do nothing if called from default WC loop (we removed those)

// ─── WC Swatches sync: trigger native select change when custom swatch clicked ──
// This is handled in main.js — the swatch button sets the hidden select value
// and dispatches a 'change' event for WC variation JS compatibility.

// ─── Ensure WC template functions are available ─────────────────────────────
add_filter( 'woocommerce_breadcrumb_home_url', function() {
    return home_url('/');
} );

// ─── Ensure WC AJAX nonce fragment is always fresh ─────────────────────────
add_filter( 'woocommerce_cart_hash_salt', function() {
    return wp_create_nonce('wk-nonce');
} );

// ─── Proper product visibility in searches ──────────────────────────────────
add_filter( 'woocommerce_product_query_meta_query', function( $meta_query ) {
    return $meta_query;
} );

// ─── Cart item thumbnail fix ────────────────────────────────────────────────
add_filter( 'woocommerce_cart_item_thumbnail', function( $thumbnail, $cart_item, $cart_item_key ) {
    // Ensure thumbnail has our lazy loading
    return str_replace( '<img ', '<img loading="lazy" ', $thumbnail );
}, 10, 3 );

// ─── Fix: WC variation form in hidden div needs proper data ─────────────────
// The hidden #wk-wc-form variation form needs WC variation JS to find it
// Add data attributes WC variation JS needs
add_action( 'woocommerce_before_variations_form', function() {
    // WC variation JS needs the form to have wc-variation-form class
    // It already does from woocommerce_template_single_add_to_cart()
} );

// ─── Remove native result count & ordering from loop hooks ────────────────
// Our custom archive-product.php shop head already renders these.
// Without this, they appear TWICE (once in our head, once from the hook).
add_action( 'wp', function() {
    remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count',    20 );
    remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
} );
