<?php
/**
 * WhiteKurti — Email Customization Settings
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Customizer: Email branding settings ──────────────────────────────────────
add_action( 'customize_register', function( $wp_customize ) {
	$wp_customize->add_section( 'wk_emails', [
		'title'    => __( '📧 Email Branding', 'whitekurti' ),
		'panel'    => 'wk_panel',
		'priority' => 83,
	] );
	$fields = [
		[ 'wk_email_accent',    'Accent Color (buttons, borders)', 'text', '#6B1E3E' ],
		[ 'wk_email_bg',        'Email Body Background',           'text', '#fdfcfa' ],
		[ 'wk_email_header_bg', 'Header Background',               'text', '#120F0C' ],
		[ 'wk_email_footer_bg', 'Footer Background',               'text', '#120F0C' ],
	];
	foreach ( $fields as [$id, $label, $type, $default] ) {
		$wp_customize->add_setting($id, ['default'=>$default,'sanitize_callback'=>'sanitize_text_field','transport'=>'refresh']);
		$wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, $id, ['label'=>$label,'section'=>'wk_emails']));
	}
} );

// ── Override WC email colors via filters ─────────────────────────────────────
add_filter( 'woocommerce_email_styles', function( $css ) {
	$accent    = get_theme_mod( 'wk_email_accent', '#6B1E3E' );
	$header_bg = get_theme_mod( 'wk_email_header_bg', '#120F0C' );
	$body_bg   = get_theme_mod( 'wk_email_bg', '#fdfcfa' );
	$brand_nm  = get_theme_mod('wk_brand_mode','white') === 'black' ? 'BlackKurti' : 'WhiteKurti';

	$custom = "
	body { background-color: #f4f0ea !important; }
	#wrapper { background-color: #f4f0ea !important; margin: 0; padding: 30px 0; }
	#template_container { border: none !important; box-shadow: 0 2px 20px rgba(0,0,0,.1) !important; border-radius: 0 !important; max-width: 600px; }
	#template_header { background-color: {$header_bg} !important; color: #EDE5DA !important; border-radius: 0 !important; border-bottom: 3px solid {$accent} !important; padding: 30px 48px !important; }
	#template_header h1, #template_header h1 a { color: #EDE5DA !important; font-family: Georgia, 'Times New Roman', serif !important; font-weight: 300 !important; letter-spacing: 0.2em !important; text-transform: uppercase !important; text-decoration: none !important; }
	#template_body { background-color: {$body_bg} !important; }
	#body_content { padding: 40px 48px !important; }
	#body_content p { color: #3E3028 !important; font-size: 14px !important; line-height: 1.7 !important; }
	#body_content a { color: {$accent} !important; }
	h2 { color: #120F0C !important; font-weight: 400 !important; letter-spacing: 0.05em !important; }
	.td { background-color: {$body_bg} !important; }
	.woocommerce-order-details th { background-color: {$accent} !important; color: #fff !important; }
	.woocommerce-order-details td { border-color: #e2dbd2 !important; }
	.button.pay, .button { background-color: {$accent} !important; border-color: {$accent} !important; color: #fff !important; font-family: inherit !important; text-transform: uppercase !important; letter-spacing: 0.1em !important; font-size: 13px !important; padding: 14px 28px !important; border-radius: 0 !important; }
	#template_footer { background-color: #120F0C !important; color: rgba(237,229,218,.5) !important; }
	#template_footer td { border-top: none !important; }
	#template_footer p { color: rgba(237,229,218,.5) !important; font-size: 12px !important; }
	#template_footer a { color: rgba(237,229,218,.6) !important; }
	";
	return $css . $custom;
} );

// ── Set WooCommerce email from name ───────────────────────────────────────────
add_filter( 'woocommerce_email_from_name', function() {
	return get_bloginfo('name');
} );

// ── Add order tracking link to confirmation emails ────────────────────────────
add_action( 'woocommerce_email_after_order_table', function( $order, $sent_to_admin ) {
	if ( $sent_to_admin ) return;
	$track_url = wc_get_account_endpoint_url('orders');
	echo '<p style="text-align:center;margin:24px 0 0;">
		<a href="' . esc_url($track_url) . '" style="background:#6B1E3E;color:#fff;padding:12px 28px;text-decoration:none;font-size:12px;letter-spacing:.1em;text-transform:uppercase;display:inline-block;">Track Your Order</a>
	</p>';
}, 10, 2 );

// ── Subject line improvements ─────────────────────────────────────────────────
add_filter( 'woocommerce_email_subject_new_order', function( $subject, $order ) {
	$brand = get_bloginfo('name');
	return "🛍️ New Order #{$order->get_order_number()} — {$brand}";
}, 10, 2 );

add_filter( 'woocommerce_email_subject_customer_processing_order', function( $subject, $order ) {
	$brand = get_bloginfo('name');
	return "✅ Your order is confirmed — #{$order->get_order_number()} | {$brand}";
}, 10, 2 );

add_filter( 'woocommerce_email_subject_customer_completed_order', function( $subject, $order ) {
	$brand = get_bloginfo('name');
	return "📦 Your order has been shipped — #{$order->get_order_number()} | {$brand}";
}, 10, 2 );

add_filter( 'woocommerce_email_subject_customer_on_hold_order', function( $subject, $order ) {
	$brand = get_bloginfo('name');
	return "⏳ Order on hold — #{$order->get_order_number()} | {$brand}";
}, 10, 2 );
