<?php
/**
 * WhiteKurti — index.php (fallback template)
 */
get_header(); ?>

<main id="wk-main" class="wk-main wk-container">
	<?php if ( have_posts() ) : ?>
		<div class="wk-post-grid">
		<?php while ( have_posts() ) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class('wk-post-card'); ?>>
				<?php if ( has_post_thumbnail() ) : ?>
					<a href="<?php the_permalink(); ?>" class="wk-post-card__thumb">
						<?php the_post_thumbnail('medium'); ?>
					</a>
				<?php endif; ?>
				<div class="wk-post-card__body">
					<h2 class="wk-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<div class="wk-post-card__excerpt"><?php the_excerpt(); ?></div>
					<a href="<?php the_permalink(); ?>" class="wk-btn wk-btn--outline wk-btn--sm"><?php esc_html_e('Read More','whitekurti'); ?></a>
				</div>
			</article>
		<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(['mid_size' => 2]); ?>
	<?php else : ?>
		<div class="wk-empty-state">
			<p><?php esc_html_e('No content found.','whitekurti'); ?></p>
		</div>
	<?php endif; ?>
</main>

<?php get_footer();
