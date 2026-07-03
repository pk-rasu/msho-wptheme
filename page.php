<?php
if ( ! defined( 'ABSPATH' ) ) exit; get_header(); ?>
<main id="wk-main" class="wk-main wk-container">
<?php while (have_posts()) : the_post(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class('wk-page-content'); ?>>
	<h1 class="wk-page-title"><?php the_title(); ?></h1>
	<div class="wk-page-body"><?php the_content(); ?></div>
</article>
<?php endwhile; ?>
</main>
<?php get_footer();
