<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Template Name: About Us
 * 
 * Custom About page template matching the Libas vibe.
 */

get_header();
$shop_url = class_exists('WooCommerce') ? get_permalink(wc_get_page_id('shop')) : home_url('/shop');
$img_base = WK_URI . '/assets/images/';
?>

<main id="wk-main" class="wk-about-main">
	<section class="wk-hero wk-hero--still wk-about-hero">
		<?php $hero_img = get_theme_mod('wk_about_hero_image') ?: $img_base . 'hero-banner.png'; ?>
		<img src="<?php echo esc_url($hero_img); ?>" alt="<?php the_title(); ?>" class="wk-hero__bg-img">
		<div class="wk-hero__overlay"></div>
		<div class="wk-hero__content">
			<h1 class="wk-hero__title"><?php the_title(); ?></h1>
			<p class="wk-hero__sub"><?php echo esc_html(get_theme_mod('wk_about_hero_subtitle', 'A legacy of elegance')); ?></p>
		</div>
	</section>

	<section class="wk-section wk-container wk-about-content-section">
		<?php 
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				the_content();
			}
		}
		?>
	</section>

	<section class="wk-hero wk-hero--split wk-about-split">
		<div class="wk-hero-split__img">
			<?php $split_img = get_theme_mod('wk_about_split_image') ?: $img_base . 'collection-promo.png'; ?>
			<img src="<?php echo esc_url($split_img); ?>" alt="<?php echo esc_attr(get_theme_mod('wk_about_split_title', 'Our Philosophy')); ?>" class="wk-hero-split__img-tag">
		</div>
		<div class="wk-hero-split__text">
			<h2 class="wk-hero-split__title"><?php echo esc_html(get_theme_mod('wk_about_split_title', 'Our Philosophy')); ?></h2>
			<p class="wk-hero-split__desc"><?php echo esc_html(get_theme_mod('wk_about_split_text', 'To redefine ethnic wear by offering luxurious, accessible, and sustainably crafted pieces that empower women to embrace their cultural roots while looking forward.')); ?></p>
			<a href="<?php echo esc_url( $shop_url ); ?>" class="wk-btn"><?php echo esc_html( get_theme_mod('wk_about_split_btn', 'Explore Collections') ); ?></a>
		</div>
	</section>
</main>

<?php get_footer();
