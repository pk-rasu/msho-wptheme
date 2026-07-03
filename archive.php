<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * WhiteKurti — Blog Archive Template
 */
get_header(); ?>
<main id="wk-main" class="wk-main">
<div class="wk-container">

	<!-- Archive header -->
	<div class="wk-archive-header">
		<?php if (is_category()) : ?>
		<div class="wk-archive-header__eyebrow">Category</div>
		<?php elseif (is_tag()) : ?>
		<div class="wk-archive-header__eyebrow">Tag</div>
		<?php elseif (is_author()) : ?>
		<div class="wk-archive-header__eyebrow">Author</div>
		<?php elseif (is_search()) : ?>
		<div class="wk-archive-header__eyebrow">Search Results for</div>
		<?php endif; ?>
		<h1 class="wk-archive-title">
			<?php
			if (is_search()) echo '"' . esc_html(get_search_query()) . '"';
			else the_archive_title();
			?>
		</h1>
		<?php
		$desc = get_the_archive_description();
		if ($desc) echo '<p class="wk-archive-desc">' . wp_kses_post($desc) . '</p>';
		?>
	</div>

	<!-- Blog grid -->
	<?php if ( have_posts() ) : ?>
	<div class="wk-post-grid">
		<?php while ( have_posts() ) : the_post();
			$thumb = get_the_post_thumbnail_url(null,'large');
			$cats  = get_the_category();
			$excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words(get_the_content(), 22, '...');
		?>
		<article <?php post_class('wk-post-card'); ?>>
			<?php if ($thumb) : ?>
			<a href="<?php the_permalink(); ?>" class="wk-post-card__img-link" tabindex="-1" aria-hidden="true">
				<img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" class="wk-post-card__img" loading="lazy" />
			</a>
			<?php else : ?>
			<div class="wk-post-card__img-placeholder"></div>
			<?php endif; ?>
			<div class="wk-post-card__body">
				<?php if ($cats) : ?>
				<div class="wk-post-card__cats">
					<a href="<?php echo esc_url(get_category_link($cats[0]->term_id)); ?>" class="wk-post-card__cat"><?php echo esc_html($cats[0]->name); ?></a>
				</div>
				<?php endif; ?>
				<h2 class="wk-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<p class="wk-post-card__excerpt"><?php echo esc_html($excerpt); ?></p>
				<div class="wk-post-card__footer">
					<span class="wk-post-card__date"><?php echo get_the_date('d M Y'); ?></span>
					<span class="wk-post-card__read"><?php echo ceil(str_word_count(strip_tags(get_the_content())) / 200); ?> min read</span>
					<a href="<?php the_permalink(); ?>" class="wk-post-card__read-more" aria-label="Read <?php echo esc_attr(get_the_title()); ?>">Read →</a>
				</div>
			</div>
		</article>
		<?php endwhile; ?>
	</div>

	<!-- Pagination -->
	<div class="wk-blog-pagination">
		<?php the_posts_pagination(['prev_text'=>'← Newer','next_text'=>'Older →','mid_size'=>2]); ?>
	</div>

	<?php else : ?>
	<div class="wk-blog-empty">
		<p><?php is_search() ? _e('No results found. Try different search terms.','whitekurti') : _e('No posts published yet.','whitekurti'); ?></p>
		<a href="<?php echo esc_url(home_url('/')); ?>" class="wk-btn" style="margin-top:16px;">Back to Home</a>
	</div>
	<?php endif; ?>

</div><!-- /.wk-container -->
</main>
<?php get_footer(); ?>
