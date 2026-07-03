<?php
if ( ! defined( 'ABSPATH' ) ) exit; get_header(); ?>
<main id="wk-main" class="wk-main wk-404">
<div class="wk-container" style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding:80px 20px;text-align:center;">
  <div style="max-width:560px;">
    <div style="font-size:96px;line-height:1;margin-bottom:24px;filter:grayscale(.3);">🕵️‍♀️</div>
    <h1 style="font-size:clamp(36px,6vw,64px);font-weight:800;letter-spacing:-.02em;margin:0 0 12px;">404</h1>
    <p style="font-size:20px;font-weight:600;margin:0 0 12px;">Page Not Found</p>
    <p style="color:var(--ink-soft,#888);font-size:15px;margin:0 0 36px;line-height:1.7;">The page you're looking for seems to have moved, been renamed, or never existed. Let's get you back on track.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="wk-btn wk-btn--primary">🏠 Back to Home</a>
      <?php if (class_exists('WooCommerce')): ?>
      <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" class="wk-btn wk-btn--secondary">🛍️ Browse Shop</a>
      <?php endif; ?>
    </div>
    <?php if (function_exists('get_search_form')): ?>
    <div style="margin-top:40px;max-width:400px;margin-left:auto;margin-right:auto;">
      <p style="font-size:13px;color:#888;margin-bottom:10px;">Or search for what you need:</p>
      <?php get_search_form(); ?>
    </div>
    <?php endif; ?>
    <?php
    // Show popular categories
    if (class_exists('WooCommerce')):
      $cats = get_terms(['taxonomy'=>'product_cat','hide_empty'=>true,'parent'=>0,'number'=>6,'exclude'=>[get_option('default_product_cat')]]);
      if (!is_wp_error($cats) && !empty($cats)):
    ?>
    <div style="margin-top:48px;">
      <p style="font-size:13px;color:#888;margin-bottom:14px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;">Popular Categories</p>
      <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
        <?php foreach($cats as $cat): ?>
        <a href="<?php echo esc_url( is_wp_error(get_term_link($cat)) ? "#" : get_term_link($cat) ); ?>"
           style="background:#f5f5f0;border:1px solid var(--line,#e0dbd3);border-radius:20px;padding:8px 16px;font-size:13px;text-decoration:none;color:var(--ink,#111);transition:.2s;"
           onmouseover="this.style.background='var(--accent,#6B1E3E)';this.style.color='#fff';this.style.borderColor='var(--accent,#6B1E3E)'"
           onmouseout="this.style.background='#f5f5f0';this.style.color='var(--ink,#111)';this.style.borderColor='var(--line,#e0dbd3)'">
          <?php echo esc_html($cat->name); ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; endif; ?>
  </div>
</div>
</main>
<?php get_footer(); ?>
