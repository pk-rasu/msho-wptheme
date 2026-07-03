<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * WhiteKurti — Single Blog Post Template
 */
get_header(); ?>
<main id="wk-main" class="wk-main">
<?php while ( have_posts() ) : the_post(); ?>

<article <?php post_class('wk-blog-single'); ?>>

	<!-- Hero image -->
	<?php if ( has_post_thumbnail() ) : ?>
	<div class="wk-blog-hero">
		<?php the_post_thumbnail( 'full', ['class'=>'wk-blog-hero__img','loading'=>'eager'] ); ?>
	</div>
	<?php endif; ?>

	<div class="wk-container">
		<div class="wk-blog-single__layout">

			<!-- Article body -->
			<div class="wk-blog-single__body">

				<!-- Meta -->
				<div class="wk-blog-meta">
					<?php
					$cats = get_the_category();
					if ( $cats ) :
					foreach ( $cats as $cat ) :
					?>
					<a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>" class="wk-blog-meta__cat"><?php echo esc_html($cat->name); ?></a>
					<?php endforeach; endif; ?>
					<span class="wk-blog-meta__date"><?php echo get_the_date('d M Y'); ?></span>
					<span class="wk-blog-meta__sep">·</span>
					<span class="wk-blog-meta__read"><?php echo ceil(str_word_count(strip_tags(get_the_content())) / 200); ?> min read</span>
				</div>

				<h1 class="wk-blog-single__title"><?php the_title(); ?></h1>

				<!-- Author row -->
				<div class="wk-blog-author">
					<div class="wk-blog-author__avatar"><?php echo strtoupper(get_the_author()[0]); ?></div>
					<div>
						<span class="wk-blog-author__name"><?php the_author(); ?></span>
						<span class="wk-blog-author__date"><?php echo get_the_date('d M Y'); ?></span>
					</div>
					<!-- Share buttons -->
					<div class="wk-blog-share" aria-label="Share this article">
						<span class="wk-blog-share__label">Share:</span>
						<?php
						$url   = urlencode(get_permalink());
						$title = urlencode(get_the_title());
						$shares = [
							['https://wa.me/?text='.$title.'%20'.$url,    'WhatsApp',  '#25D366'],
							['https://www.facebook.com/sharer/sharer.php?u='.$url, 'Facebook', '#1877F2'],
							['https://twitter.com/intent/tweet?text='.$title.'&url='.$url, 'X', '#000'],
						];
						foreach ($shares as [$share_url, $share_label, $color]) :
						?>
						<a href="<?php echo esc_url($share_url); ?>" class="wk-blog-share__btn" target="_blank" rel="noopener noreferrer"
						   style="background:<?php echo esc_attr($color); ?>" title="Share on <?php echo esc_attr($share_label); ?>">
							<?php echo esc_html(strtoupper($share_label[0])); ?>
						</a>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Content -->
				<div class="wk-blog-content entry-content">
					<?php the_content( sprintf( esc_html__('Continue reading %s'), '<span class="screen-reader-text">'.get_the_title().'</span>' ) ); ?>
					<?php wp_link_pages(['before'=>'<nav class="wk-page-links">','after'=>'</nav>']); ?>
				</div>

				<!-- Tags -->
				<?php $tags = get_the_tags(); if ($tags) : ?>
				<div class="wk-blog-tags">
					<?php foreach ($tags as $tag) : ?>
					<a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>" class="wk-blog-tag">#<?php echo esc_html($tag->name); ?></a>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

				<!-- Post navigation -->
				<nav class="wk-blog-post-nav" aria-label="Post navigation">
					<?php
					$prev = get_previous_post();
					$next = get_next_post();
					if ($prev) :
					?>
					<a href="<?php echo esc_url(get_permalink($prev->ID)); ?>" class="wk-blog-nav-link wk-blog-nav-link--prev">
						<span class="wk-blog-nav-link__dir">← Previous</span>
						<span class="wk-blog-nav-link__title"><?php echo esc_html(get_the_title($prev->ID)); ?></span>
					</a>
					<?php endif; if ($next) : ?>
					<a href="<?php echo esc_url(get_permalink($next->ID)); ?>" class="wk-blog-nav-link wk-blog-nav-link--next">
						<span class="wk-blog-nav-link__dir">Next →</span>
						<span class="wk-blog-nav-link__title"><?php echo esc_html(get_the_title($next->ID)); ?></span>
					</a>
					<?php endif; ?>
				</nav>
			</div>

			<!-- Sidebar -->
			<aside class="wk-blog-single__sidebar" aria-label="Blog sidebar">
				<!-- Recent posts -->
				<div class="wk-blog-widget">
					<h3 class="wk-blog-widget__title">More Articles</h3>
					<?php
					$recent = get_posts(['posts_per_page'=>4,'post__not_in'=>[get_the_ID()],'orderby'=>'date','order'=>'DESC']);
					foreach ($recent as $rp) :
					$thumb = get_the_post_thumbnail_url($rp->ID,'thumbnail');
					?>
					<a href="<?php echo esc_url(get_permalink($rp->ID)); ?>" class="wk-blog-widget__post">
						<?php if ($thumb) : ?>
						<img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr(get_the_title($rp->ID)); ?>" class="wk-blog-widget__post-img" loading="lazy" />
						<?php endif; ?>
						<div>
							<span class="wk-blog-widget__post-title"><?php echo esc_html(get_the_title($rp->ID)); ?></span>
							<span class="wk-blog-widget__post-date"><?php echo get_the_date('d M Y', $rp->ID); ?></span>
						</div>
					</a>
					<?php endforeach; wp_reset_postdata(); ?>
				</div>
				<!-- Categories -->
				<div class="wk-blog-widget" style="margin-top:24px;">
					<h3 class="wk-blog-widget__title">Categories</h3>
					<ul class="wk-blog-cat-list">
						<?php wp_list_categories(['title_li'=>'','show_count'=>1,'hierarchical'=>false]); ?>
					</ul>
				</div>
			</aside>

		</div><!-- /.wk-blog-single__layout -->
	</div><!-- /.wk-container -->
</article>

<?php
// Related posts (same category)
$cats_ids = wp_get_post_categories(get_the_ID());
$related  = get_posts(['posts_per_page'=>3,'post__not_in'=>[get_the_ID()],'category__in'=>$cats_ids,'orderby'=>'rand']);
if ($related) :
?>
<section class="wk-container wk-blog-related">
	<h2 class="wk-section-title" style="margin-bottom:24px;">You Might Also Like</h2>
	<div class="wk-post-grid">
		<?php foreach ($related as $rp) : setup_postdata($rp);
			$thumb = get_the_post_thumbnail_url($rp->ID,'large');
		?>
		<article class="wk-post-card">
			<?php if ($thumb) : ?>
			<a href="<?php echo esc_url(get_permalink($rp->ID)); ?>" class="wk-post-card__img-link">
				<img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr(get_the_title($rp->ID)); ?>" class="wk-post-card__img" loading="lazy" />
			</a>
			<?php endif; ?>
			<div class="wk-post-card__body">
				<div class="wk-post-card__meta"><?php echo get_the_date('d M Y',$rp->ID); ?></div>
				<h3 class="wk-post-card__title"><a href="<?php echo esc_url(get_permalink($rp->ID)); ?>"><?php echo esc_html(get_the_title($rp->ID)); ?></a></h3>
				<p class="wk-post-card__excerpt"><?php echo wp_trim_words(get_the_excerpt($rp->ID),18,'...'); ?></p>
				<a href="<?php echo esc_url(get_permalink($rp->ID)); ?>" class="wk-post-card__read-more">Read Article →</a>
			</div>
		</article>
		<?php endforeach; wp_reset_postdata(); ?>
	</div>
</section>
<?php endif; ?>

<?php endwhile; ?>
</main>
<?php get_footer(); ?>
