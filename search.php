<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * WhiteKurti - Custom Search Results Template
 */
get_header();
$query   = get_search_query();
global $wp_query;
$results = $wp_query;
$count   = $results ? (int)$results->found_posts : 0;
?>
<main id="wk-main" class="wk-main wk-search">
<div class="wk-container" style="padding-top:40px;padding-bottom:60px;">

  <!-- Search Header -->
  <div style="margin-bottom:32px;">
    <p style="font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.1em;margin:0 0 6px;">Search Results</p>
    <h1 style="font-size:clamp(24px,4vw,36px);margin:0 0 8px;">
      <?php if ($query): ?>
        Results for "<span style="color:var(--accent,#6B1E3E);"><?php echo esc_html($query); ?></span>"
      <?php else: echo 'Search'; endif; ?>
    </h1>
    <?php if ($query && $count): ?>
    <p style="color:#666;font-size:14px;margin:0;"><?php echo number_format($count); ?> result<?php echo $count !== 1 ? 's' : ''; ?> found</p>
    <?php endif; ?>
  </div>

  <!-- Search bar -->
  <div style="max-width:540px;margin-bottom:32px;">
    <form role="search" method="GET" action="<?php echo esc_url(home_url('/')); ?>" style="display:flex;gap:0;">
      <input type="search" name="s" value="<?php echo esc_attr($query); ?>"
             placeholder="Search products..."
             style="flex:1;padding:13px 16px;border:2px solid var(--accent,#6B1E3E);border-right:none;border-radius:6px 0 0 6px;font-size:14px;outline:none;" />
      <input type="hidden" name="post_type" value="product" />
      <button type="submit" style="background:var(--accent,#6B1E3E);color:#fff;border:none;padding:13px 20px;border-radius:0 6px 6px 0;font-size:14px;font-weight:600;cursor:pointer;">Search</button>
    </form>
  </div>

  <?php if ( ! have_posts() ): ?>
  <!-- No results -->
  <div style="background:#f9fafb;border-radius:10px;padding:48px;text-align:center;">
    <div style="font-size:48px;margin-bottom:16px;">🔍</div>
    <h2 style="font-size:20px;margin:0 0 10px;">No results found for "<?php echo esc_html($query); ?>"</h2>
    <p style="color:#666;margin:0 0 24px;">Try different keywords, or browse by category:</p>
    <?php if (class_exists('WooCommerce')):
      $cats = get_terms(['taxonomy'=>'product_cat','hide_empty'=>true,'parent'=>0,'number'=>8,'exclude'=>[get_option('default_product_cat')]]);
      if (!is_wp_error($cats)):
    ?>
    <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
      <?php foreach($cats as $cat): ?>
      <a href="<?php echo esc_url( is_wp_error(get_term_link($cat)) ? "#" : get_term_link($cat) ); ?>" style="background:#fff;border:1px solid #ddd;border-radius:20px;padding:7px 14px;font-size:13px;text-decoration:none;color:#333;"><?php echo esc_html($cat->name); ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; endif; ?>
  </div>

  <?php else: ?>
  <!-- Results grid -->
  <div class="wk-search-grid wk-products-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;">
    <?php while (have_posts()): the_post();
      if (class_exists('WooCommerce') && get_post_type() === 'product'):
        $product = wc_get_product(get_the_ID());
        if ($product): ?>
        <div class="wk-pcard">
          <a href="<?php the_permalink(); ?>" class="wk-pcard__img-link">
            <div class="wk-pcard__img" style="aspect-ratio:3/4;overflow:hidden;border-radius:6px;background:#f5f5f0;">
              <?php echo $product->get_image('wk-product-card', ['loading'=>'lazy','style'=>'width:100%;height:100%;object-fit:cover;']); ?>
            </div>
          </a>
          <div class="wk-pcard__info" style="padding:10px 4px 0;">
            <a href="<?php the_permalink(); ?>" style="text-decoration:none;color:inherit;">
              <h3 style="font-size:13px;margin:0 0 6px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php the_title(); ?></h3>
            </a>
            <div style="font-size:14px;font-weight:600;color:var(--accent,#6B1E3E);"><?php echo '₹' . number_format((float)$product->get_price(), 0, '.', ','); ?></div>
          </div>
        </div>
        <?php endif;
      else: ?>
        <div style="background:#fff;border:1px solid #eee;border-radius:8px;padding:20px;">
          <a href="<?php the_permalink(); ?>" style="text-decoration:none;color:inherit;">
            <h3 style="font-size:14px;margin:0 0 8px;"><?php the_title(); ?></h3>
            <p style="font-size:13px;color:#666;margin:0;"><?php echo wp_trim_words(get_the_excerpt(), 15); ?></p>
          </a>
        </div>
      <?php endif; endwhile; ?>
  </div>
  <?php the_posts_pagination(['mid_size'=>2]); ?>
  <?php endif; ?>

</div>
</main>
<style>
@media (max-width:767px) { .wk-search-grid { grid-template-columns:repeat(2,1fr) !important; } }
</style>
<?php get_footer(); ?>
