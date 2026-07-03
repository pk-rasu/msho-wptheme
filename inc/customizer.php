<?php
/**
 * WhiteKurti — WordPress Customizer settings
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function wk_customizer_register( $wp_customize ) {

	$wp_customize->add_panel( 'wk_panel', [
		'title'    => __( 'WhiteKurti Theme', 'whitekurti' ),
		'priority' => 30,
	] );

	// ── Brand & Palette ────────────────────────────────────────────────────
	$wp_customize->add_section( 'wk_brand', [
		'title' => __( 'Brand & Palette', 'whitekurti' ),
		'panel' => 'wk_panel',
	] );

	$wp_customize->add_setting( 'wk_brand_mode', [
		'default'           => 'white',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'wk_brand_mode', [
		'label'   => __( 'Brand Mode', 'whitekurti' ),
		'section' => 'wk_brand',
		'type'    => 'radio',
		'choices' => [
			'white' => 'WhiteKurti (light luxury)',
			'black' => 'BlackKurti (dark luxury)',
		],
	] );

	$wp_customize->add_setting( 'wk_palette', [
		'default'           => 'ivory',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'wk_palette', [
		'label'   => __( 'Colour Palette', 'whitekurti' ),
		'section' => 'wk_brand',
		'type'    => 'select',
		'choices' => [
			'ivory'    => 'Ivory (WhiteKurti default)',
			'sand'     => 'Sand',
			'linen'    => 'Linen',
			'onyx'     => 'Onyx (BlackKurti default)',
			'graphite' => 'Graphite',
			'midnight' => 'Midnight',
		],
	] );

	$wp_customize->add_setting( 'wk_hero_style', [
		'default'           => 'still',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'wk_hero_style', [
		'label'   => __( 'Hero Style', 'whitekurti' ),
		'section' => 'wk_brand',
		'type'    => 'radio',
		'choices' => [ 'still' => 'Full-bleed', 'split' => 'Split (text + image)' ],
	] );

	$wp_customize->add_setting( 'wk_announcement', [
		'default'           => 'Free Delivery on All Orders · ₹2,000+  ·  5-day easy returns  ·  New arrivals every Thursday',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'postMessage',
	] );
	$wp_customize->add_control( 'wk_announcement', [
		'label'   => __( 'Announcement Bar', 'whitekurti' ),
		'section' => 'wk_brand',
		'type'    => 'text',
	] );

	// ── Typography ─────────────────────────────────────────────────────────
	$wp_customize->add_section( 'wk_typography', [
		'title'    => __( 'Typography', 'whitekurti' ),
		'panel'    => 'wk_panel',
		'priority' => 20,
	] );

	$wp_customize->add_setting( 'wk_type_pairing', [
		'default'           => 'editorial',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'wk_type_pairing', [
		'label'   => __( 'Font Preset', 'whitekurti' ),
		'section' => 'wk_typography',
		'type'    => 'radio',
		'choices' => [
			'editorial' => 'Editorial — Cormorant Garamond + Manrope',
			'modern'    => 'Modern — Bodoni Moda + Work Sans',
			'soft'      => 'Soft — Cardo + Nunito Sans',
		],
	] );

	$wp_customize->add_setting( 'wk_font_display', [ 'default' => '', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
	$wp_customize->add_control( 'wk_font_display', [
		'label'       => __( 'Custom Heading Font (Google Fonts name)', 'whitekurti' ),
		'description' => 'e.g. Playfair Display — leave blank to use preset',
		'section'     => 'wk_typography',
		'type'        => 'text',
	] );

	$wp_customize->add_setting( 'wk_font_body', [ 'default' => '', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
	$wp_customize->add_control( 'wk_font_body', [
		'label'       => __( 'Custom Body Font (Google Fonts name)', 'whitekurti' ),
		'description' => 'e.g. DM Sans — leave blank to use preset',
		'section'     => 'wk_typography',
		'type'        => 'text',
	] );

	// ── Colour Tokens ──────────────────────────────────────────────────────
	$wp_customize->add_section( 'wk_colors', [
		'title'       => __( 'Colour Tokens', 'whitekurti' ),
		'description' => 'Override individual design tokens. Leave blank for palette defaults.',
		'panel'       => 'wk_panel',
		'priority'    => 30,
	] );

	$tokens = [
		'wk_color_bg'       => 'Background',
		'wk_color_surface'  => 'Surface',
		'wk_color_surface2' => 'Surface 2',
		'wk_color_ink'      => 'Text Primary',
		'wk_color_inksoft'  => 'Text Soft',
		'wk_color_inkmute'  => 'Text Muted',
		'wk_color_line'     => 'Dividers',
		'wk_color_accent'   => 'Accent',
		'wk_color_sale'     => 'Sale / Alert',
	];

	foreach ( $tokens as $id => $label ) {
		$wp_customize->add_setting( $id, [ 'default' => '', 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'postMessage' ] );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, [
			'label' => $label, 'section' => 'wk_colors',
		] ) );
	}

	// ── Shop ───────────────────────────────────────────────────────────────
	$wp_customize->add_section( 'wk_shop', [
		'title' => __( 'Shop & Products', 'whitekurti' ),
		'panel' => 'wk_panel', 'priority' => 40,
	] );

	$wp_customize->add_setting( 'wk_products_per_page', [ 'default' => 12, 'sanitize_callback' => 'absint' ] );
	$wp_customize->add_control( 'wk_products_per_page', [
		'label' => __( 'Products Per Page', 'whitekurti' ),
		'section' => 'wk_shop', 'type' => 'number',
	] );

	$wp_customize->add_setting( 'wk_filter_style', [ 'default' => 'drawer', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_filter_style', [
		'label'   => __( 'Filter Style', 'whitekurti' ),
		'section' => 'wk_shop', 'type' => 'radio',
		'choices' => [ 'sidebar' => 'Sidebar', 'drawer' => 'Drawer (mobile)', 'topbar' => 'Top bar chips' ],
	] );

	// ── Homepage ───────────────────────────────────────────────────────────
	$wp_customize->add_section( 'wk_homepage', [
		'title' => __( 'Homepage Content', 'whitekurti' ),
		'panel' => 'wk_panel', 'priority' => 45,
	] );

	// ── Homepage Toggles ───────────────────────────────────────────────────
	$wp_customize->add_setting( 'wk_show_hero', [ 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ] );
	$wp_customize->add_control( 'wk_show_hero', [ 'label' => 'Show Hero Banner', 'section' => 'wk_homepage', 'type' => 'checkbox' ] );
	$wp_customize->add_setting( 'wk_show_categories', [ 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ] );
	$wp_customize->add_control( 'wk_show_categories', [ 'label' => 'Show Categories', 'section' => 'wk_homepage', 'type' => 'checkbox' ] );
	$wp_customize->add_setting( 'wk_show_bestsellers', [ 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ] );
	$wp_customize->add_control( 'wk_show_bestsellers', [ 'label' => 'Show Bestsellers', 'section' => 'wk_homepage', 'type' => 'checkbox' ] );
	$wp_customize->add_setting( 'wk_show_promos', [ 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ] );
	$wp_customize->add_control( 'wk_show_promos', [ 'label' => 'Show Promos', 'section' => 'wk_homepage', 'type' => 'checkbox' ] );
	$wp_customize->add_setting( 'wk_show_new_arrivals', [ 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ] );
	$wp_customize->add_control( 'wk_show_new_arrivals', [ 'label' => 'Show New Arrivals', 'section' => 'wk_homepage', 'type' => 'checkbox' ] );
	$wp_customize->add_setting( 'wk_show_editorial', [ 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ] );
	$wp_customize->add_control( 'wk_show_editorial', [ 'label' => 'Show Editorial Strip', 'section' => 'wk_homepage', 'type' => 'checkbox' ] );
	$wp_customize->add_setting( 'wk_show_testimonials', [ 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ] );
	$wp_customize->add_control( 'wk_show_testimonials', [ 'label' => 'Show Testimonials', 'section' => 'wk_homepage', 'type' => 'checkbox' ] );
	$wp_customize->add_setting( 'wk_show_trust_strip', [ 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ] );
	$wp_customize->add_control( 'wk_show_trust_strip', [ 'label' => 'Show Trust Strip', 'section' => 'wk_homepage', 'type' => 'checkbox' ] );

	// Hero
	$wp_customize->add_setting( 'wk_hero_image', [ 'default' => '', 'sanitize_callback' => 'esc_url_raw' ] );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'wk_hero_image', [
		'label' => __( 'Hero Banner Image', 'whitekurti' ), 'section' => 'wk_homepage',
	] ) );
	$wp_customize->add_setting( 'wk_hero_eyebrow', [ 'default' => 'New Collection', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_hero_eyebrow', [ 'label' => 'Hero Eyebrow', 'section' => 'wk_homepage', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_hero_title', [ 'default' => 'Spring Summer \'26', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_hero_title', [ 'label' => 'Hero Title', 'section' => 'wk_homepage', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_hero_subtitle', [ 'default' => 'Celebrate in Style — Fresh Arrivals Every Week', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_hero_subtitle', [ 'label' => 'Hero Subtitle', 'section' => 'wk_homepage', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_hero_btn_text', [ 'default' => 'Shop Now', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_hero_btn_text', [ 'label' => 'Hero Button Text', 'section' => 'wk_homepage', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_hero_link', [ 'default' => '', 'sanitize_callback' => 'esc_url_raw' ] );
	$wp_customize->add_control( 'wk_hero_link', [ 'label' => 'Hero Button Link', 'section' => 'wk_homepage', 'type' => 'url' ] );

	// Categories Section
	$wp_customize->add_setting( 'wk_cats_title', [ 'default' => 'Shop By Category', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_cats_title', [ 'label' => 'Categories Section Title', 'section' => 'wk_homepage', 'type' => 'text' ] );

	// Bestsellers Section
	$wp_customize->add_setting( 'wk_bestsellers_title', [ 'default' => 'Bestsellers', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_bestsellers_title', [ 'label' => 'Bestsellers Title', 'section' => 'wk_homepage', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_bestsellers_link_text', [ 'default' => 'View All', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_bestsellers_link_text', [ 'label' => 'Bestsellers Link Text', 'section' => 'wk_homepage', 'type' => 'text' ] );

	// Promo 1
	$wp_customize->add_setting( 'wk_promo1_image', [ 'default' => '', 'sanitize_callback' => 'esc_url_raw' ] );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'wk_promo1_image', [
		'label' => __( 'Promo 1 Image', 'whitekurti' ), 'section' => 'wk_homepage',
	] ) );
	$wp_customize->add_setting( 'wk_promo1_title', [ 'default' => 'Gul', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_promo1_title', [ 'label' => 'Promo 1 Title', 'section' => 'wk_homepage', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_promo1_link', [ 'default' => '', 'sanitize_callback' => 'esc_url_raw' ] );
	$wp_customize->add_control( 'wk_promo1_link', [ 'label' => 'Promo 1 Link', 'section' => 'wk_homepage', 'type' => 'url' ] );

	// Promo 2
	$wp_customize->add_setting( 'wk_promo2_image', [ 'default' => '', 'sanitize_callback' => 'esc_url_raw' ] );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'wk_promo2_image', [
		'label' => __( 'Promo 2 Image', 'whitekurti' ), 'section' => 'wk_homepage',
	] ) );
	$wp_customize->add_setting( 'wk_promo2_title', [ 'default' => 'Bahaar', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_promo2_title', [ 'label' => 'Promo 2 Title', 'section' => 'wk_homepage', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_promo2_link', [ 'default' => '', 'sanitize_callback' => 'esc_url_raw' ] );
	$wp_customize->add_control( 'wk_promo2_link', [ 'label' => 'Promo 2 Link', 'section' => 'wk_homepage', 'type' => 'url' ] );

	// New Arrivals Section
	$wp_customize->add_setting( 'wk_new_arrivals_title', [ 'default' => 'New Arrivals', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_new_arrivals_title', [ 'label' => 'New Arrivals Title', 'section' => 'wk_homepage', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_new_arrivals_link_text', [ 'default' => 'View All', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_new_arrivals_link_text', [ 'label' => 'New Arrivals Link Text', 'section' => 'wk_homepage', 'type' => 'text' ] );

	// Editorial Strip
	$wp_customize->add_setting( 'wk_editorial_title', [ 'default' => '"Where tradition meets the modern woman."', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_editorial_title', [ 'label' => 'Editorial Title', 'section' => 'wk_homepage', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_editorial_body', [ 'default' => 'We believe that true luxury lies in simplicity. Our journey began with a singular vision: to create ethnic wear that transcends seasons and trends — blending time-honored Indian craftsmanship with contemporary silhouettes.', 'sanitize_callback' => 'sanitize_textarea_field' ] );
	$wp_customize->add_control( 'wk_editorial_body', [ 'label' => 'Editorial Body', 'section' => 'wk_homepage', 'type' => 'textarea' ] );
	$wp_customize->add_setting( 'wk_editorial_link_text', [ 'default' => 'Our Story', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_editorial_link_text', [ 'label' => 'Editorial Link Text', 'section' => 'wk_homepage', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_editorial_link_url', [ 'default' => '', 'sanitize_callback' => 'esc_url_raw' ] );
	$wp_customize->add_control( 'wk_editorial_link_url', [ 'label' => 'Editorial Link URL', 'section' => 'wk_homepage', 'type' => 'url' ] );

	// Testimonials
	$wp_customize->add_setting( 'wk_testimonials_title', [ 'default' => 'What Our Customers Say', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_testimonials_title', [ 'label' => 'Testimonials Title', 'section' => 'wk_homepage', 'type' => 'text' ] );

	for ( $i = 1; $i <= 3; $i++ ) {
		$wp_customize->add_setting( "wk_test{$i}_text", [ 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ] );
		$wp_customize->add_control( "wk_test{$i}_text", [ 'label' => "Testimonial {$i} Text", 'section' => 'wk_homepage', 'type' => 'textarea' ] );
		$wp_customize->add_setting( "wk_test{$i}_name", [ 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ] );
		$wp_customize->add_control( "wk_test{$i}_name", [ 'label' => "Testimonial {$i} Name", 'section' => 'wk_homepage', 'type' => 'text' ] );
		$wp_customize->add_setting( "wk_test{$i}_rating", [ 'default' => 5, 'sanitize_callback' => 'absint' ] );
		$wp_customize->add_control( "wk_test{$i}_rating", [ 'label' => "Testimonial {$i} Rating (1-5)", 'section' => 'wk_homepage', 'type' => 'number', 'input_attrs' => [ 'min' => 1, 'max' => 5 ] ] );
	}

	// ── About Page ─────────────────────────────────────────────────────────
	$wp_customize->add_section( 'wk_aboutpage', [
		'title' => __( 'About Page', 'whitekurti' ),
		'panel' => 'wk_panel', 'priority' => 46,
	] );
	$wp_customize->add_setting( 'wk_about_hero_image', [ 'default' => '', 'sanitize_callback' => 'esc_url_raw' ] );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'wk_about_hero_image', [
		'label' => __( 'Hero Banner Image', 'whitekurti' ), 'section' => 'wk_aboutpage',
	] ) );
	$wp_customize->add_setting( 'wk_about_hero_subtitle', [ 'default' => 'A legacy of elegance', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_about_hero_subtitle', [ 'label' => 'Hero Subtitle', 'section' => 'wk_aboutpage', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_about_split_image', [ 'default' => '', 'sanitize_callback' => 'esc_url_raw' ] );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'wk_about_split_image', [
		'label' => __( 'Philosophy Split Image', 'whitekurti' ), 'section' => 'wk_aboutpage',
	] ) );
	$wp_customize->add_setting( 'wk_about_split_title', [ 'default' => 'Our Philosophy', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_about_split_title', [ 'label' => 'Philosophy Title', 'section' => 'wk_aboutpage', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_about_split_text', [ 'default' => 'To redefine ethnic wear by offering luxurious, accessible, and sustainably crafted pieces that empower women to embrace their cultural roots while looking forward.', 'sanitize_callback' => 'sanitize_textarea_field' ] );
	$wp_customize->add_control( 'wk_about_split_text', [ 'label' => 'Philosophy Text', 'section' => 'wk_aboutpage', 'type' => 'textarea' ] );
	$wp_customize->add_setting( 'wk_about_split_btn', [ 'default' => 'Explore Collections', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_about_split_btn', [ 'label' => 'Philosophy Button Text', 'section' => 'wk_aboutpage', 'type' => 'text' ] );

	// ── Footer ─────────────────────────────────────────────────────────────
	$wp_customize->add_section( 'wk_footer', [
		'title' => __( 'Footer', 'whitekurti' ), 'panel' => 'wk_panel', 'priority' => 50,
	] );

	$wp_customize->add_setting( 'wk_footer_text', [
		'default'           => '© ' . date('Y') . ' WhiteKurti. Designed in India.',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'postMessage',
	] );
	$wp_customize->add_control( 'wk_footer_text', [
		'label' => __( 'Copyright Text', 'whitekurti' ), 'section' => 'wk_footer', 'type' => 'text',
	] );

	$wp_customize->add_setting( 'wk_footer_tagline', [ 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ] );
	$wp_customize->add_control( 'wk_footer_tagline', [ 'label' => 'Brand Tagline (Overrides default)', 'section' => 'wk_footer', 'type' => 'textarea' ] );

	$wp_customize->add_setting( 'wk_footer_shop_title', [ 'default' => 'Shop', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_footer_shop_title', [ 'label' => 'Shop Column Title', 'section' => 'wk_footer', 'type' => 'text' ] );

	$wp_customize->add_setting( 'wk_footer_help_title', [ 'default' => 'Help', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_footer_help_title', [ 'label' => 'Help Column Title', 'section' => 'wk_footer', 'type' => 'text' ] );

	$wp_customize->add_setting( 'wk_footer_company_title', [ 'default' => 'Company', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_footer_company_title', [ 'label' => 'Company Column Title', 'section' => 'wk_footer', 'type' => 'text' ] );

	$wp_customize->add_setting( 'wk_newsletter_title', [ 'default' => 'Join the Atelier', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_newsletter_title', [ 'label' => 'Newsletter Title', 'section' => 'wk_footer', 'type' => 'text' ] );

	$wp_customize->add_setting( 'wk_newsletter_desc', [ 'default' => 'New arrivals, exclusive edits, and slow-fashion stories — every Thursday.', 'sanitize_callback' => 'sanitize_textarea_field' ] );
	$wp_customize->add_control( 'wk_newsletter_desc', [ 'label' => 'Newsletter Description', 'section' => 'wk_footer', 'type' => 'textarea' ] );

	$wp_customize->add_setting( 'wk_newsletter_btn', [ 'default' => 'Subscribe', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_newsletter_btn', [ 'label' => 'Newsletter Button', 'section' => 'wk_footer', 'type' => 'text' ] );

	// ── Global Texts & System Pages ────────────────────────────────────────
	$wp_customize->add_section( 'wk_global_texts', [
		'title' => __( 'Global Texts & Contact', 'whitekurti' ), 'panel' => 'wk_panel', 'priority' => 55,
	] );

	// Contact Page
	$wp_customize->add_setting( 'wk_contact_heading', [ 'default' => 'Get in Touch', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_contact_heading', [ 'label' => 'Contact Page Heading', 'section' => 'wk_global_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_contact_email', [ 'default' => 'care@whitekurti.com', 'sanitize_callback' => 'sanitize_email' ] );
	$wp_customize->add_control( 'wk_contact_email', [ 'label' => 'Contact Email', 'section' => 'wk_global_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_contact_phone', [ 'default' => '+91 123 456 7890', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_contact_phone', [ 'label' => 'Contact Phone', 'section' => 'wk_global_texts', 'type' => 'text' ] );
	
	// 404 Page
	$wp_customize->add_setting( 'wk_404_title', [ 'default' => 'Page not found', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_404_title', [ 'label' => '404 Title', 'section' => 'wk_global_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_404_text', [ 'default' => 'The page you are looking for does not exist.', 'sanitize_callback' => 'sanitize_textarea_field' ] );
	$wp_customize->add_control( 'wk_404_text', [ 'label' => '404 Text', 'section' => 'wk_global_texts', 'type' => 'textarea' ] );
	$wp_customize->add_setting( 'wk_404_btn', [ 'default' => 'Back to Home', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_404_btn', [ 'label' => '404 Button', 'section' => 'wk_global_texts', 'type' => 'text' ] );

	// Archive Page
	$wp_customize->add_setting( 'wk_archive_empty', [ 'default' => 'No posts found.', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_archive_empty', [ 'label' => 'Archive Empty Text', 'section' => 'wk_global_texts', 'type' => 'text' ] );

	// ── UI & Shop Texts ────────────────────────────────────────────────────
	$wp_customize->add_section( 'wk_ui_texts', [
		'title' => __( 'UI & Shop Texts', 'whitekurti' ), 'panel' => 'wk_panel', 'priority' => 57,
	] );
	
	$wp_customize->add_setting( 'wk_text_cart_title', [ 'default' => 'Cart', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_text_cart_title', [ 'label' => 'Cart Title', 'section' => 'wk_ui_texts', 'type' => 'text' ] );
	
	$wp_customize->add_setting( 'wk_text_subtotal', [ 'default' => 'Subtotal', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_text_subtotal', [ 'label' => 'Subtotal Label', 'section' => 'wk_ui_texts', 'type' => 'text' ] );
	
	$wp_customize->add_setting( 'wk_text_checkout', [ 'default' => 'Checkout', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_text_checkout', [ 'label' => 'Checkout Button', 'section' => 'wk_ui_texts', 'type' => 'text' ] );
	
	$wp_customize->add_setting( 'wk_text_view_cart', [ 'default' => 'View Cart', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_text_view_cart', [ 'label' => 'View Cart Button', 'section' => 'wk_ui_texts', 'type' => 'text' ] );
	
	$wp_customize->add_setting( 'wk_text_add_to_cart', [ 'default' => 'Add to Cart', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_text_add_to_cart', [ 'label' => 'Add to Cart Button', 'section' => 'wk_ui_texts', 'type' => 'text' ] );
	
	$wp_customize->add_setting( 'wk_text_search_placeholder', [ 'default' => 'Search products...', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_text_search_placeholder', [ 'label' => 'Search Placeholder', 'section' => 'wk_ui_texts', 'type' => 'text' ] );

	$wp_customize->add_setting( 'wk_text_cart_page_title', [ 'default' => 'Shopping Bag', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_text_cart_page_title', [ 'label' => 'Cart Page Title', 'section' => 'wk_ui_texts', 'type' => 'text' ] );

	$wp_customize->add_setting( 'wk_text_empty_cart_title', [ 'default' => 'Your bag is empty', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_text_empty_cart_title', [ 'label' => 'Empty Cart Title', 'section' => 'wk_ui_texts', 'type' => 'text' ] );
	
	$wp_customize->add_setting( 'wk_text_empty_cart_desc', [ 'default' => 'Explore our latest collections and find your new favorites.', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_text_empty_cart_desc', [ 'label' => 'Empty Cart Description', 'section' => 'wk_ui_texts', 'type' => 'text' ] );

	$wp_customize->add_setting( 'wk_text_empty_cart_btn', [ 'default' => 'Start Shopping', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_text_empty_cart_btn', [ 'label' => 'Empty Cart Button', 'section' => 'wk_ui_texts', 'type' => 'text' ] );

	$wp_customize->add_setting( 'wk_text_signin', [ 'default' => 'Sign In', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_text_signin', [ 'label' => 'Sign In Text', 'section' => 'wk_ui_texts', 'type' => 'text' ] );

	$wp_customize->add_setting( 'wk_text_register', [ 'default' => 'Create Account', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_text_register', [ 'label' => 'Register Text', 'section' => 'wk_ui_texts', 'type' => 'text' ] );

	// ── PDP Texts ─────────────────────────────────────────────────────────
	$wp_customize->add_section( 'wk_pdp_texts', [
		'title' => __( 'PDP Texts (Single Product)', 'whitekurti' ), 'panel' => 'wk_panel', 'priority' => 58,
	] );

	// Trust Badges
	$wp_customize->add_setting( 'wk_pdp_trust1', [ 'default' => 'Free delivery above ₹2,000', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_pdp_trust1', [ 'label' => 'Trust Item 1', 'section' => 'wk_pdp_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_pdp_trust2', [ 'default' => '5-day easy returns', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_pdp_trust2', [ 'label' => 'Trust Item 2', 'section' => 'wk_pdp_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_pdp_trust3', [ 'default' => 'Secure checkout', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_pdp_trust3', [ 'label' => 'Trust Item 3', 'section' => 'wk_pdp_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_pdp_trust4', [ 'default' => 'COD available', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_pdp_trust4', [ 'label' => 'Trust Item 4', 'section' => 'wk_pdp_texts', 'type' => 'text' ] );

	// Accordions
	$wp_customize->add_setting( 'wk_pdp_acc_details', [ 'default' => 'Product Details', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_pdp_acc_details', [ 'label' => 'Details Accordion Title', 'section' => 'wk_pdp_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_pdp_acc_size', [ 'default' => 'Size & Fit', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_pdp_acc_size', [ 'label' => 'Size Accordion Title', 'section' => 'wk_pdp_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_pdp_acc_size_body', [ 'default' => 'Model is 5\'6" and wears size S. Refer to our size guide for exact measurements.', 'sanitize_callback' => 'sanitize_textarea_field' ] );
	$wp_customize->add_control( 'wk_pdp_acc_size_body', [ 'label' => 'Size Accordion Body', 'section' => 'wk_pdp_texts', 'type' => 'textarea' ] );
	$wp_customize->add_setting( 'wk_pdp_acc_shipping', [ 'default' => 'Shipping & Returns', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_pdp_acc_shipping', [ 'label' => 'Shipping Accordion Title', 'section' => 'wk_pdp_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_pdp_acc_shipping_body', [ 'default' => 'Free Delivery on all orders. Delivered in 4–7 business days. Free 5-day returns with free pickup.', 'sanitize_callback' => 'sanitize_textarea_field' ] );
	$wp_customize->add_control( 'wk_pdp_acc_shipping_body', [ 'label' => 'Shipping Accordion Body', 'section' => 'wk_pdp_texts', 'type' => 'textarea' ] );

	// Pincode
	$wp_customize->add_setting( 'wk_pdp_pincode_placeholder', [ 'default' => 'Enter pincode', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_pdp_pincode_placeholder', [ 'label' => 'Pincode Placeholder', 'section' => 'wk_pdp_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_pdp_pincode_btn', [ 'default' => 'Check', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_pdp_pincode_btn', [ 'label' => 'Pincode Button', 'section' => 'wk_pdp_texts', 'type' => 'text' ] );

	// Related
	$wp_customize->add_setting( 'wk_pdp_related_title', [ 'default' => 'Complete the Look', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_pdp_related_title', [ 'label' => 'Related Products Title', 'section' => 'wk_pdp_texts', 'type' => 'text' ] );

	// ── Shop / PLP Texts ───────────────────────────────────────────────────
	$wp_customize->add_section( 'wk_shop_texts', [
		'title' => __( 'Shop Texts (PLP)', 'whitekurti' ), 'panel' => 'wk_panel', 'priority' => 59,
	] );

	// Buttons & Filters
	$wp_customize->add_setting( 'wk_shop_filter_btn', [ 'default' => 'Filter', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_shop_filter_btn', [ 'label' => 'Filter Button Text', 'section' => 'wk_shop_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_shop_filter_drawer_title', [ 'default' => 'Filter & Sort', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_shop_filter_drawer_title', [ 'label' => 'Filter Drawer Title', 'section' => 'wk_shop_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_shop_cat_title', [ 'default' => 'Category', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_shop_cat_title', [ 'label' => 'Category Filter Title', 'section' => 'wk_shop_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_shop_clear_btn', [ 'default' => 'Clear All', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_shop_clear_btn', [ 'label' => 'Clear All Button', 'section' => 'wk_shop_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_shop_apply_btn', [ 'default' => 'Apply', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_shop_apply_btn', [ 'label' => 'Apply Button', 'section' => 'wk_shop_texts', 'type' => 'text' ] );

	// Empty State
	$wp_customize->add_setting( 'wk_shop_empty_eyebrow', [ 'default' => 'Nothing here yet', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_shop_empty_eyebrow', [ 'label' => 'Empty State Eyebrow', 'section' => 'wk_shop_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_shop_empty_title', [ 'default' => 'No products found', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_shop_empty_title', [ 'label' => 'Empty State Title', 'section' => 'wk_shop_texts', 'type' => 'text' ] );
	$wp_customize->add_setting( 'wk_shop_empty_btn', [ 'default' => 'Browse all', 'sanitize_callback' => 'sanitize_text_field' ] );
	$wp_customize->add_control( 'wk_shop_empty_btn', [ 'label' => 'Empty State Button', 'section' => 'wk_shop_texts', 'type' => 'text' ] );

	// ── Trust Strip ─────────────────────────────────────────────────────────
	$wp_customize->add_section( 'wk_trust_strip', [
		'title' => __( 'Trust Strip', 'whitekurti' ), 'panel' => 'wk_panel', 'priority' => 56,
	] );

	for ( $i = 1; $i <= 4; $i++ ) {
		$default_titles = [ 1 => 'Free Shipping', 2 => '7-Day Returns', 3 => 'Secure Pay', 4 => 'Made in India' ];
		$default_subs   = [ 1 => 'On orders ₹2,000+', 2 => 'Free pickup', 3 => 'UPI · Card · COD', 4 => 'Small-batch craft' ];
		
		$wp_customize->add_setting( "wk_trust{$i}_title", [ 'default' => $default_titles[$i], 'sanitize_callback' => 'sanitize_text_field' ] );
		$wp_customize->add_control( "wk_trust{$i}_title", [ 'label' => "Item {$i} Title", 'section' => 'wk_trust_strip', 'type' => 'text' ] );
		
		$wp_customize->add_setting( "wk_trust{$i}_sub", [ 'default' => $default_subs[$i], 'sanitize_callback' => 'sanitize_text_field' ] );
		$wp_customize->add_control( "wk_trust{$i}_sub", [ 'label' => "Item {$i} Subtitle", 'section' => 'wk_trust_strip', 'type' => 'text' ] );
	}
}
add_action( 'customize_register', 'wk_customizer_register' );

function wk_customizer_preview_js() {
	wp_enqueue_script( 'wk-customizer', WK_URI . '/assets/js/customizer.js', [ 'customize-preview' ], WK_VERSION, true );
}
add_action( 'customize_preview_init', 'wk_customizer_preview_js' );

// ═══════════════════════════════════════════════════════════════════════════
// PRO FEATURES — added by WhiteKurti Pro update
// ═══════════════════════════════════════════════════════════════════════════

// ── Extended Footer & Section Colors ───────────────────────────────────────
function wk_pro_customizer_register( $wp_customize ) {

	// ── Section Colors (Extended) ─────────────────────────────────────────
	$wp_customize->add_section( 'wk_section_colors', [
		'title'       => __( 'Section Colors (Footer, Trust, Newsletter)', 'whitekurti' ),
		'description' => 'Override colors for specific sections.',
		'panel'       => 'wk_panel',
		'priority'    => 31,
	] );

	$section_tokens = [
		'wk_color_footer_bg'          => 'Footer Background',
		'wk_color_footer_text'        => 'Footer: Main Text',
		'wk_color_footer_link'        => 'Footer: Link Text',
		'wk_color_footer_heading'     => 'Footer: Column Headings',
		'wk_color_footer_tagline'     => 'Footer: Brand Tagline',
		'wk_color_newsletter_bg'      => 'Newsletter Section BG',
		'wk_color_newsletter_text'    => 'Newsletter: Text & Input Color',
		'wk_color_trust_bg'           => 'Trust Strip: Background',
		'wk_color_trust_text'         => 'Trust Strip: Title Text',
		'wk_color_trust_desc'         => 'Trust Strip: Description Text',
		'wk_color_trust_icon'         => 'Trust Strip: Icon Color',
		'wk_color_bottom_bar_bg'      => 'Footer Bottom Bar: Background',
		'wk_color_bottom_bar_text'    => 'Footer Bottom Bar: Text',
		'wk_color_pay_icon_bg'        => 'Payment Icons: Background',
		'wk_color_pay_icon_text'      => 'Payment Icons: Text',
	];
	foreach ( $section_tokens as $id => $label ) {
		$wp_customize->add_setting( $id, [ 'default' => '', 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'postMessage' ] );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, [
			'label' => $label, 'section' => 'wk_section_colors',
		] ) );
	}

	// ── Timer Bar ─────────────────────────────────────────────────────────
	$wp_customize->add_section( 'wk_timer_bar', [
		'title' => __( 'Countdown Timer Bar', 'whitekurti' ),
		'panel' => 'wk_panel', 'priority' => 15,
	] );

	$wp_customize->add_setting( 'wk_timer_enabled', [ 'default' => false, 'sanitize_callback' => 'rest_sanitize_boolean', 'transport' => 'refresh' ] );
	$wp_customize->add_control( 'wk_timer_enabled', [ 'label' => '✅ Enable Timer Bar', 'section' => 'wk_timer_bar', 'type' => 'checkbox' ] );

	$wp_customize->add_setting( 'wk_timer_mode', [ 'default' => 'fixed', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
	$wp_customize->add_control( 'wk_timer_mode', [
		'label' => 'Timer Mode', 'section' => 'wk_timer_bar', 'type' => 'radio',
		'choices' => [ 'fixed' => 'Fixed End Date/Time (same for all visitors)', 'session' => 'Per-Visitor Session Timer (unique countdown per visitor)' ],
	] );

	$wp_customize->add_setting( 'wk_timer_end_datetime', [ 'default' => '', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
	$wp_customize->add_control( 'wk_timer_end_datetime', [
		'label'       => 'End Date & Time (for Fixed mode)',
		'description' => 'Format: YYYY-MM-DD HH:MM:SS — e.g. 2025-12-31 23:59:59',
		'section'     => 'wk_timer_bar', 'type' => 'text',
	] );

	$wp_customize->add_setting( 'wk_timer_session_mins', [ 'default' => 30, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ] );
	$wp_customize->add_control( 'wk_timer_session_mins', [
		'label'       => 'Session Duration (minutes, for Per-Visitor mode)',
		'description' => 'Each visitor gets this many minutes before the timer expires.',
		'section'     => 'wk_timer_bar', 'type' => 'number',
		'input_attrs' => [ 'min' => 5, 'max' => 1440 ],
	] );

	$wp_customize->add_setting( 'wk_timer_text', [ 'default' => '🔥 Limited Time Offer — Ends In:', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
	$wp_customize->add_control( 'wk_timer_text', [ 'label' => 'Timer Bar Message', 'section' => 'wk_timer_bar', 'type' => 'text' ] );

	$wp_customize->add_setting( 'wk_timer_bg', [ 'default' => '#7D2D6B', 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'refresh' ] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'wk_timer_bg', [
		'label' => 'Background Color', 'section' => 'wk_timer_bar',
	] ) );

	$wp_customize->add_setting( 'wk_timer_text_color', [ 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'refresh' ] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'wk_timer_text_color', [
		'label' => 'Text & Number Color', 'section' => 'wk_timer_bar',
	] ) );

	$wp_customize->add_setting( 'wk_timer_font_size', [ 'default' => 13, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ] );
	$wp_customize->add_control( 'wk_timer_font_size', [
		'label' => 'Font Size (px)', 'section' => 'wk_timer_bar', 'type' => 'number',
		'input_attrs' => [ 'min' => 10, 'max' => 20 ],
	] );

	$wp_customize->add_setting( 'wk_timer_font_style', [ 'default' => 'normal', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
	$wp_customize->add_control( 'wk_timer_font_style', [
		'label' => 'Font Style', 'section' => 'wk_timer_bar', 'type' => 'radio',
		'choices' => [ 'normal' => 'Normal', 'italic' => 'Italic', 'uppercase' => 'Uppercase' ],
	] );

	// ── WhatsApp Button ───────────────────────────────────────────────────
	$wp_customize->add_section( 'wk_whatsapp', [
		'title' => __( 'WhatsApp Floating Button', 'whitekurti' ),
		'panel' => 'wk_panel', 'priority' => 16,
	] );

	$wp_customize->add_setting( 'wk_wa_enabled', [ 'default' => false, 'sanitize_callback' => 'rest_sanitize_boolean', 'transport' => 'refresh' ] );
	$wp_customize->add_control( 'wk_wa_enabled', [ 'label' => '✅ Enable WhatsApp Button', 'section' => 'wk_whatsapp', 'type' => 'checkbox' ] );

	$wp_customize->add_setting( 'wk_wa_number', [ 'default' => '', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
	$wp_customize->add_control( 'wk_wa_number', [
		'label'       => 'WhatsApp Number',
		'description' => 'Include country code. e.g. 919876543210 (no + or spaces)',
		'section'     => 'wk_whatsapp', 'type' => 'text',
	] );

	$wp_customize->add_setting( 'wk_wa_message', [ 'default' => 'Hi! I have a question about your products.', 'sanitize_callback' => 'sanitize_textarea_field', 'transport' => 'refresh' ] );
	$wp_customize->add_control( 'wk_wa_message', [
		'label'       => 'Pre-filled Message',
		'description' => 'This message will be pre-filled when a visitor opens WhatsApp.',
		'section'     => 'wk_whatsapp', 'type' => 'textarea',
	] );

	// ── Social Proof Notifications ────────────────────────────────────────
	$wp_customize->add_section( 'wk_social_proof', [
		'title' => __( 'Social Proof Notifications', 'whitekurti' ),
		'panel' => 'wk_panel', 'priority' => 17,
	] );

	$wp_customize->add_setting( 'wk_sp_enabled', [ 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean', 'transport' => 'refresh' ] );
	$wp_customize->add_control( 'wk_sp_enabled', [ 'label' => '✅ Enable Social Proof Popups', 'section' => 'wk_social_proof', 'type' => 'checkbox' ] );

	$wp_customize->add_setting( 'wk_sp_interval', [ 'default' => 28, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ] );
	$wp_customize->add_control( 'wk_sp_interval', [
		'label'       => 'Show Every X Seconds',
		'description' => 'How often to show a new notification (10–120 seconds).',
		'section'     => 'wk_social_proof', 'type' => 'number',
		'input_attrs' => [ 'min' => 10, 'max' => 120 ],
	] );
}
add_action( 'customize_register', 'wk_pro_customizer_register' );

// ── Output extended CSS variables ─────────────────────────────────────────────
function wk_pro_css_vars() {
	$token_map = [
		'wk_color_footer_bg'        => '--footer-bg-custom',
		'wk_color_footer_text'      => '--footer-text-custom',
		'wk_color_footer_link'      => '--footer-link-custom',
		'wk_color_footer_heading'   => '--footer-heading-custom',
		'wk_color_newsletter_bg'    => '--newsletter-bg-custom',
		'wk_color_newsletter_text'  => '--newsletter-text-custom',
		'wk_color_trust_bg'         => '--trust-bg-custom',
		'wk_color_trust_text'       => '--trust-text-custom',
		'wk_color_trust_icon'       => '--trust-icon-custom',
		'wk_color_bottom_bar_bg'    => '--footer-bottom-bg-custom',
		'wk_color_bottom_bar_text'  => '--footer-bottom-text-custom',
		'wk_color_pay_icon_bg'      => '--pay-icon-bg-custom',
		'wk_color_pay_icon_text'    => '--pay-icon-text-custom',
	];
	$vars = [];
	foreach ( $token_map as $mod => $cssvar ) {
		$v = get_theme_mod( $mod, '' );
		if ( $v ) $vars[] = $cssvar . ':' . sanitize_hex_color($v) . ';';
	}
	if ( $vars ) {
		echo '<style id="wk-pro-css-vars">:root{' . implode('', $vars) . '}</style>' . "\n";
	}
}
add_action( 'wp_head', 'wk_pro_css_vars', 6 );

// ══════════════════════════════════════════════════════
// V3 ADDITIONS — Logo, Timer Padding, Section Colors Fix
// ══════════════════════════════════════════════════════
function wk_v3_customizer( $wp_customize ) {

	// ── Logo Size Fix ────────────────────────────────────
	// Override custom-logo dimensions to prevent breakage
	$wp_customize->remove_setting( 'custom_logo' );
	$wp_customize->add_setting( 'custom_logo', [
		'sanitize_callback' => 'absint',
		'transport'         => 'postMessage',
	]);
	$wp_customize->add_control( new WP_Customize_Cropped_Image_Control( $wp_customize, 'custom_logo', [
		'label'         => __( 'Logo', 'whitekurti' ),
		'description'   => __( 'Recommended size: 200 × 56 px (PNG with transparent background). Max height will always be 56px.', 'whitekurti' ),
		'section'       => 'title_tagline',
		'width'         => 200,
		'height'        => 56,
		'flex_width'    => true,
		'flex_height'   => false,
		'button_labels' => [
			'select'       => __( 'Select Logo', 'whitekurti' ),
			'change'       => __( 'Change Logo', 'whitekurti' ),
			'remove'       => __( 'Remove', 'whitekurti' ),
			'default'      => __( 'Default', 'whitekurti' ),
			'placeholder'  => __( 'No logo selected', 'whitekurti' ),
			'frame_title'  => __( 'Select Logo', 'whitekurti' ),
			'frame_button' => __( 'Choose Logo', 'whitekurti' ),
		],
	]));

	// ── Timer Bar Padding ─────────────────────────────────
	$wp_customize->add_setting( 'wk_timer_pad_top', [ 'default' => 10, 'sanitize_callback' => 'absint', 'transport' => 'postMessage' ] );
	$wp_customize->add_control( 'wk_timer_pad_top', [
		'label'       => __( 'Timer Bar: Top Padding (px)', 'whitekurti' ),
		'section'     => 'wk_timer_bar',
		'type'        => 'number',
		'input_attrs' => [ 'min' => 4, 'max' => 40 ],
		'priority'    => 60,
	]);
	$wp_customize->add_setting( 'wk_timer_pad_bottom', [ 'default' => 10, 'sanitize_callback' => 'absint', 'transport' => 'postMessage' ] );
	$wp_customize->add_control( 'wk_timer_pad_bottom', [
		'label'       => __( 'Timer Bar: Bottom Padding (px)', 'whitekurti' ),
		'section'     => 'wk_timer_bar',
		'type'        => 'number',
		'input_attrs' => [ 'min' => 4, 'max' => 40 ],
		'priority'    => 61,
	]);

	// Section colors now fully managed in wk_pro_customizer_register above.

	// ── Trust Strip Text Updates ──────────────────────────
	// Update defaults
	$wp_customize->get_setting('wk_trust1_title')->default = 'FREE Delivery';
	$wp_customize->get_setting('wk_trust1_sub')->default   = 'On all orders';
	$wp_customize->get_setting('wk_trust2_title')->default = '5-Day Returns';
	$wp_customize->get_setting('wk_trust2_sub')->default   = 'Easy free pickup';
	$wp_customize->get_setting('wk_trust4_title')->default = 'New Every Sunday';
	$wp_customize->get_setting('wk_trust4_sub')->default   = 'Fresh drops weekly';
}
add_action( 'customize_register', 'wk_v3_customizer' );

// ── Output v3 CSS variables ────────────────────────────────
function wk_v3_css_vars() {
	$map = [
		'wk_color_footer_bg'        => '--footer-bg-custom',
		'wk_color_footer_text'      => '--footer-text-custom',
		'wk_color_footer_tagline'   => '--footer-tagline-custom',
		'wk_color_footer_heading'   => '--footer-heading-custom',
		'wk_color_footer_link'      => '--footer-link-custom',
		'wk_color_newsletter_bg'    => '--newsletter-bg-custom',
		'wk_color_newsletter_text'  => '--newsletter-text-custom',
		'wk_color_trust_bg'         => '--trust-bg-custom',
		'wk_color_trust_text'       => '--trust-text-custom',
		'wk_color_trust_desc'       => '--trust-desc-custom',
		'wk_color_trust_icon'       => '--trust-icon-custom',
		'wk_color_bottom_bar_bg'    => '--footer-bottom-bg-custom',
		'wk_color_bottom_bar_text'  => '--footer-bottom-text-custom',
		'wk_color_pay_icon_bg'      => '--pay-icon-bg-custom',
		'wk_color_pay_icon_text'    => '--pay-icon-text-custom',
	];
	$vars  = [];
	foreach ( $map as $mod => $var ) {
		$v = get_theme_mod( $mod, '' );
		if ( $v ) $vars[] = $var . ':' . sanitize_hex_color($v) . ';';
	}
	// Timer padding
	$pad_top    = absint( get_theme_mod( 'wk_timer_pad_top', 10 ) );
	$pad_bottom = absint( get_theme_mod( 'wk_timer_pad_bottom', 10 ) );
	$vars[] = '--wk-timer-pad-top:' . $pad_top . 'px;';
	$vars[] = '--wk-timer-pad-bottom:' . $pad_bottom . 'px;';

	if ( $vars ) {
		echo '<style id="wk-v3-css-vars">:root{' . implode('', $vars) . '}</style>' . "\n";
	}
}
add_action( 'wp_head', 'wk_v3_css_vars', 7 );

// ── V4: Social Links + Newsletter Toggle in Footer customizer ──────────────
function wk_v4_customizer( $wp_customize ) {

	// Ensure footer section exists
	$footer_section = $wp_customize->get_section('wk_footer');

	// ── Social Media Links ────────────────────────────────────────────────
	$socials = [
		'wk_instagram_url'       => ['Instagram Profile URL',       'https://instagram.com/yourpage'],
		'wk_facebook_url'        => ['Facebook Page URL',            'https://facebook.com/yourpage'],
		'wk_whatsapp_footer_url' => ['WhatsApp Link (wa.me URL)',     'https://wa.me/919876543210'],
		'wk_youtube_url'         => ['YouTube Channel URL',          ''],
		'wk_pinterest_url'       => ['Pinterest Profile URL',        ''],
	];
	foreach ($socials as $id => [$label, $default]) {
		$wp_customize->add_setting($id, ['default'=>$default,'sanitize_callback'=>'esc_url_raw','transport'=>'refresh']);
		$wp_customize->add_control($id, ['label'=>$label,'section'=>'wk_footer','type'=>'url']);
	}

	// ── Newsletter Toggle ──────────────────────────────────────────────────
	$wp_customize->add_setting('wk_show_newsletter', ['default'=>true,'sanitize_callback'=>'rest_sanitize_boolean','transport'=>'refresh']);
	$wp_customize->add_control('wk_show_newsletter', [
		'label'   => __('✅ Show Newsletter Section in Footer','whitekurti'),
		'section' => 'wk_footer',
		'type'    => 'checkbox',
		'priority'=> 5,
	]);

	// Newsletter benefit text
	$wp_customize->get_setting('wk_newsletter_title') && $wp_customize->get_setting('wk_newsletter_title')->default = '🎁 Get 10% Off Your First Order';
}
add_action('customize_register','wk_v4_customizer');
