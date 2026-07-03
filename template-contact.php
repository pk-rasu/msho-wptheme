<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Template Name: Contact Libas Clone
 * 
 * Custom Contact page template matching the Libas vibe.
 */

get_header(); ?>

<main id="wk-main" class="wk-container wk-contact-main">
	<h1 class="wk-page-title wk-contact-title"><?php the_title(); ?></h1>

	<div class="wk-contact-grid">
		<div class="wk-contact-info-card">
			<h2 class="wk-contact-heading"><?php echo esc_html( get_theme_mod( 'wk_contact_heading', 'Get in Touch' ) ); ?></h2>
			
			<div class="wk-contact-content">
				<?php 
				if ( have_posts() ) {
					while ( have_posts() ) {
						the_post();
						the_content();
					}
				}
				?>
			</div>

			<div class="wk-contact-method">
				<strong class="wk-contact-method-label"><?php esc_html_e( 'Email Support', 'whitekurti' ); ?></strong>
				<a href="mailto:<?php echo esc_attr( get_theme_mod('wk_contact_email', 'care@libasclone.com') ); ?>" class="wk-contact-method-value">
					<?php echo esc_html( get_theme_mod('wk_contact_email', 'care@libasclone.com') ); ?>
				</a>
			</div>
			
			<div class="wk-contact-method">
				<strong class="wk-contact-method-label"><?php esc_html_e( 'Phone Support', 'whitekurti' ); ?></strong>
				<a href="tel:<?php echo esc_attr( get_theme_mod('wk_contact_phone', '+911234567890') ); ?>" class="wk-contact-method-value">
					<?php echo esc_html( get_theme_mod('wk_contact_phone', '+91 123 456 7890') ); ?>
				</a>
			</div>
		</div>

		<div class="wk-contact-form-card">
			<form action="#" method="post" class="wk-contact-form">
				<div class="wk-form-row">
					<label for="name"><?php esc_html_e( 'Full Name *', 'whitekurti' ); ?></label>
					<input type="text" id="name" name="name" required class="wk-input">
				</div>
				<div class="wk-form-row">
					<label for="email"><?php esc_html_e( 'Email Address *', 'whitekurti' ); ?></label>
					<input type="email" id="email" name="email" required class="wk-input">
				</div>
				<div class="wk-form-row">
					<label for="order_number"><?php esc_html_e( 'Order Number (Optional)', 'whitekurti' ); ?></label>
					<input type="text" id="order_number" name="order_number" class="wk-input">
				</div>
				<div class="wk-form-row">
					<label for="message"><?php esc_html_e( 'Message *', 'whitekurti' ); ?></label>
					<textarea id="message" name="message" rows="5" required class="wk-input" style="resize: vertical;"></textarea>
				</div>
				<button type="submit" class="wk-btn"><?php esc_html_e( 'Send Message', 'whitekurti' ); ?></button>
			</form>
		</div>
	</div>
</main>

<?php get_footer();
