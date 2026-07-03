<?php
/**
 * WhiteKurti — Block Patterns
 * Native WordPress Block Editor (Gutenberg) patterns.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function wk_register_block_patterns() {
	if ( ! function_exists( 'register_block_pattern_category' ) ) return;

	register_block_pattern_category(
		'whitekurti',
		[ 'label' => __( 'WhiteKurti / Libas', 'whitekurti' ) ]
	);

	// 1. Hero Banner Pattern
	register_block_pattern(
		'whitekurti/hero-banner',
		[
			'title'       => __( 'Hero Banner', 'whitekurti' ),
			'categories'  => [ 'whitekurti', 'header' ],
			'content'     => '<!-- wp:cover {"url":"' . WK_URI . '/assets/images/hero-banner.png","dimRatio":30,"overlayColor":"black","align":"full","className":"wk-hero-block"} -->
<div class="wp-block-cover alignfull wk-hero-block"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-30 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="' . WK_URI . '/assets/images/hero-banner.png" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}},"textColor":"white"} -->
<p class="has-text-align-center has-white-color has-text-color" style="font-size:12px;letter-spacing:2px;text-transform:uppercase">New Collection</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"64px","lineHeight":"1.1"}},"textColor":"white"} -->
<h1 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="font-size:64px;line-height:1.1">Spring Summer \'26</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","textColor":"white"} -->
<p class="has-text-align-center has-white-color has-text-color">Celebrate in Style — Fresh Arrivals Every Week</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"white","textColor":"black","style":{"border":{"radius":"0px"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-black-color has-white-background-color has-text-color has-background wp-element-button" style="border-radius:0px">Shop Now</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover -->',
		]
	);

	// 2. Editorial Strip
	register_block_pattern(
		'whitekurti/editorial-strip',
		[
			'title'       => __( 'Editorial Strip', 'whitekurti' ),
			'categories'  => [ 'whitekurti', 'text' ],
			'content'     => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px"}}},"backgroundColor":"background","layout":{"type":"constrained","contentSize":"800px"}} -->
<div class="wp-block-group alignfull has-background-background-color has-background" style="padding-top:80px;padding-bottom:80px"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"italic","fontWeight":"400","fontSize":"36px"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:36px;font-style:italic;font-weight:400">"Where tradition meets the modern woman."</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">We believe that true luxury lies in simplicity. Our journey began with a singular vision: to create ethnic wear that transcends seasons and trends — blending time-honored Indian craftsmanship with contemporary silhouettes.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">Our Story</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->',
		]
	);

	// 3. Collection Promos
	register_block_pattern(
		'whitekurti/collection-promos',
		[
			'title'       => __( 'Collection Promos', 'whitekurti' ),
			'categories'  => [ 'whitekurti', 'gallery' ],
			'content'     => '<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading -->
<h2 class="wp-block-heading">Libas Collections</h2>
<!-- /wp:heading -->
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:cover {"url":"' . WK_URI . '/assets/images/collection-promo.png","dimRatio":20,"overlayColor":"black","minHeight":600} -->
<div class="wp-block-cover has-black-background-color has-background-dim-20 has-background-dim" style="min-height:600px"><img class="wp-block-cover__image-background" alt="" src="' . WK_URI . '/assets/images/collection-promo.png" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:heading {"textAlign":"center","level":3,"textColor":"white"} -->
<h3 class="wp-block-heading has-text-align-center has-white-color has-text-color">Gul</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","textColor":"white"} -->
<p class="has-text-align-center has-white-color has-text-color">Explore →</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:cover {"url":"' . WK_URI . '/assets/images/hero-banner.png","dimRatio":20,"overlayColor":"black","minHeight":600} -->
<div class="wp-block-cover has-black-background-color has-background-dim-20 has-background-dim" style="min-height:600px"><img class="wp-block-cover__image-background" alt="" src="' . WK_URI . '/assets/images/hero-banner.png" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:heading {"textAlign":"center","level":3,"textColor":"white"} -->
<h3 class="wp-block-heading has-text-align-center has-white-color has-text-color">Bahaar</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","textColor":"white"} -->
<p class="has-text-align-center has-white-color has-text-color">Explore →</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
		]
	);

	// 4. Testimonials
	register_block_pattern(
		'whitekurti/testimonials',
		[
			'title'       => __( 'Testimonials', 'whitekurti' ),
			'categories'  => [ 'whitekurti', 'text' ],
			'content'     => '<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:60px;padding-bottom:60px"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">What Our Customers Say</h2>
<!-- /wp:heading -->
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">★★★★★</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">"The fabric quality is awesome. It feels so comfortable and soft. Both casual and ethnic wear are brilliant."</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"12px","textTransform":"uppercase"}}} -->
<p class="has-text-align-center" style="font-size:12px;text-transform:uppercase">— Madhu S.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">★★★★★</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">"I\'m in love with this brand now, the quality of the clothes is top notch. The website is very user-friendly."</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"12px","textTransform":"uppercase"}}} -->
<p class="has-text-align-center" style="font-size:12px;text-transform:uppercase">— Tejasvi C.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">★★★★★</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">"The kurta set I bought had the perfect and breathable fabric. Its fit is comfortable. 100% reasonable!"</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"12px","textTransform":"uppercase"}}} -->
<p class="has-text-align-center" style="font-size:12px;text-transform:uppercase">— Trisha J.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
		]
	);
}
add_action( 'init', 'wk_register_block_patterns' );
