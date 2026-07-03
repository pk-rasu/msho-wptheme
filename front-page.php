<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Front Page — Libas-style Homepage
 * Full visual design: hero banner, category circles, product grids, 
 * collection promos, testimonials, trust strip, blog rail.
 */
get_header(); 
$shop_url = function_exists('wc_get_page_id') ? get_permalink(wc_get_page_id('shop')) : home_url('/shop');
$img_base = WK_URI . '/assets/images/';
?>
<main id="wk-main" class="wk-main wk-home">

  <!-- ═══════════ 1. HERO BANNER (Interactive Slider) ═══════════ -->
  <?php wk_render_interactive_hero(); ?>

  <!-- ═══════════ 2. SHOP BY CATEGORY (Circular) ═══════════ -->
  <?php if ( get_theme_mod('wk_show_categories', true) ) : ?>
  <section class="wk-home-section wk-container">
    <div class="wk-home-section__head">
      <h2 class="wk-home-section__title"><?php echo esc_html(get_theme_mod('wk_cats_title', 'Shop By Category')); ?></h2>
    </div>
    <div class="wk-home-cats">
      <?php
      $categories = [];
      if (class_exists('WooCommerce')) {
        $wc_cats = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'number' => 8, 'parent' => 0, 'exclude' => [get_option('default_product_cat')]]);
        if (!is_wp_error($wc_cats) && !empty($wc_cats)) {
          foreach ($wc_cats as $cat) {
            $categories[] = [
              'title'   => $cat->name,
              'url'     => get_term_link($cat),
              'img'     => wk_get_category_image($cat->term_id, 'medium'),
              'term_id' => $cat->term_id,
            ];
          }
        }
      }
      // Fallback demo categories if no WooCommerce categories exist
      if (empty($categories)) {
        // No external image URLs in fallback — use styled initials instead
        $fallback_names = ['Kurta Sets', 'Suits', 'Sarees', 'Dresses', 'Lehengas', 'Co-ords', 'Loungewear', 'Plus Size'];
        foreach ($fallback_names as $i => $name) {
          $categories[] = [
            'title' => $name,
            'url'   => $shop_url,
            'img'   => '', // Empty = styled initial fallback rendered in template
          ];
        }
      }
      foreach ($categories as $cat) :
      ?>
      <a href="<?php echo esc_url($cat['url']); ?>" class="wk-home-cat">
        <div class="wk-home-cat__circle">
          <?php if (!empty($cat['img'])) : ?>
            <img src="<?php echo esc_url($cat['img']); ?>" alt="<?php echo esc_attr($cat['title']); ?>" loading="lazy">
          <?php else :
              // Styled initial fallback — no external URLs
              $bg_colors = ['#6B1E3E','#0a5a68','#1a1050','#4a1a00','#1a4a00','#3a2000'];
              $bg_idx    = abs(crc32($cat['title'])) % count($bg_colors);
              $bg_color  = $bg_colors[$bg_idx];
              $initial   = strtoupper(mb_substr($cat['title'], 0, 1));
          ?>
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:<?php echo esc_attr($bg_color); ?>;color:#fff;font-size:28px;font-weight:800;font-family:serif;"><?php echo esc_html($initial); ?></div>
          <?php endif; ?>
        </div>
        <span class="wk-home-cat__name"><?php echo esc_html($cat['title']); ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════════ 3. BESTSELLERS GRID ═══════════ -->
  <?php if ( get_theme_mod('wk_show_bestsellers', true) ) : ?>
  <section class="wk-home-section wk-container">
    <div class="wk-home-section__head">
      <h2 class="wk-home-section__title"><?php echo esc_html(get_theme_mod('wk_bestsellers_title', 'Bestsellers')); ?></h2>
      <a href="<?php echo esc_url($shop_url); ?>" class="wk-home-section__more"><?php echo esc_html(get_theme_mod('wk_bestsellers_link_text', 'View All')); ?></a>
    </div>
    <?php if (class_exists('WooCommerce')) : ?>
      <div class="wk-home-products-grid">
        <?php
        $bestsellers = new WP_Query([
          'post_type'      => 'product',
          'posts_per_page' => 8,
          'meta_key'       => 'total_sales',
          'orderby'        => 'meta_value_num',
          'order'          => 'DESC',
        ]);
        if ($bestsellers->have_posts()) :
          while ($bestsellers->have_posts()) : $bestsellers->the_post();
            $product = wc_get_product( get_the_ID() );
            if ($product && $product->is_visible()) wk_product_card($product, ['layout' => 'editorial']);
          endwhile;
          wp_reset_postdata();
        else :
          // Fallback dummy product cards when no products exist
          for ($i = 1; $i <= 4; $i++) :
        ?>
          <article class="wk-pcard wk-pcard--editorial">
            <a href="<?php echo esc_url($shop_url); ?>" class="wk-pcard-link">
              <div class="wk-pcard__media">
                <img src="<?php echo esc_url($img_base . 'product-' . $i . '.png'); ?>" alt="<?php printf(esc_attr__('Product %d', 'whitekurti'), $i); ?>" class="wk-pcard__img" loading="lazy">
              </div>
              <div class="wk-pcard-info">
                <span class="wk-pcard-cat"><?php esc_html_e('Kurta Sets', 'whitekurti'); ?></span>
                <h3 class="wk-pcard-title"><?php printf(esc_html__('Sample Kurta Set %d', 'whitekurti'), $i); ?></h3>
                <div class="wk-pcard-price">
                  <span class="wk-price">&#8377;1,<?php echo ($i * 299); ?></span>
                  <span class="wk-price-was">&#8377;2,<?php echo ($i * 499); ?></span>
                  <span class="wk-price-save">&#x2212;40%</span>
                </div>
              </div>
            </a>
          </article>
        <?php
          endfor;
        endif;
        ?>
      </div>
    <?php else : ?>
      <div class="wk-home-products-grid">
        <?php for ($i = 1; $i <= 4; $i++) : ?>
          <article class="wk-pcard wk-pcard--editorial">
            <a href="#" class="wk-pcard-link">
              <div class="wk-pcard__media">
                <img src="<?php echo esc_url($img_base . 'product-' . $i . '.png'); ?>" alt="<?php printf(esc_attr__('Product %d', 'whitekurti'), $i); ?>" class="wk-pcard__img" loading="lazy">
              </div>
              <div class="wk-pcard-info">
                <span class="wk-pcard-cat"><?php esc_html_e('Kurta Sets', 'whitekurti'); ?></span>
                <h3 class="wk-pcard-title"><?php printf(esc_html__('Sample Kurta Set %d', 'whitekurti'), $i); ?></h3>
                <div class="wk-pcard-price">
                  <span class="wk-price">&#8377;1,<?php echo ($i * 299); ?></span>
                  <span class="wk-price-was">&#8377;2,<?php echo ($i * 499); ?></span>
                  <span class="wk-price-save">&#x2212;40%</span>
                </div>
              </div>
            </a>
          </article>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <!-- ═══════════ 4. COLLECTION PROMO (Split Image) ═══════════ -->
  <?php if ( get_theme_mod('wk_show_promos', true) ) : ?>
  <section class="wk-home-section wk-container">
    <div class="wk-home-section__head">
      <h2 class="wk-home-section__title"><?php esc_html_e('Libas Collections', 'whitekurti'); ?></h2>
    </div>
    <div class="wk-home-promos">
      <?php 
      $p1_img = get_theme_mod('wk_promo1_image') ?: WK_URI . '/assets/images/product-2.png';
      $p1_title = get_theme_mod('wk_promo1_title', 'Gul');
      $p1_link = get_theme_mod('wk_promo1_link', $shop_url);
      ?>
      <a href="<?php echo esc_url($p1_link); ?>" class="wk-home-promo">
        <img src="<?php echo esc_url($p1_img); ?>" alt="<?php echo esc_attr($p1_title); ?>" class="wk-home-promo__img" loading="lazy">
        <div class="wk-home-promo__overlay">
          <h3 class="wk-home-promo__title"><?php echo esc_html($p1_title); ?></h3>
          <span class="wk-home-promo__cta"><?php esc_html_e('Explore →', 'whitekurti'); ?></span>
        </div>
      </a>

      <?php 
      $p2_img = get_theme_mod('wk_promo2_image') ?: WK_URI . '/assets/images/product-3.png';
      $p2_title = get_theme_mod('wk_promo2_title', 'Bahaar');
      $p2_link = get_theme_mod('wk_promo2_link', $shop_url);
      ?>
      <a href="<?php echo esc_url($p2_link); ?>" class="wk-home-promo">
        <img src="<?php echo esc_url($p2_img); ?>" alt="<?php echo esc_attr($p2_title); ?>" class="wk-home-promo__img" loading="lazy">
        <div class="wk-home-promo__overlay">
          <h3 class="wk-home-promo__title"><?php echo esc_html($p2_title); ?></h3>
          <span class="wk-home-promo__cta"><?php esc_html_e('Explore →', 'whitekurti'); ?></span>
        </div>
      </a>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════════ 5. NEW ARRIVALS RAIL ═══════════ -->
  <?php if ( get_theme_mod('wk_show_new_arrivals', true) ) : ?>
  <section class="wk-home-section wk-container">
    <div class="wk-home-section__head">
      <h2 class="wk-home-section__title"><?php echo esc_html(get_theme_mod('wk_new_arrivals_title', 'New Arrivals')); ?></h2>
      <a href="<?php echo esc_url($shop_url); ?>" class="wk-home-section__more"><?php echo esc_html(get_theme_mod('wk_new_arrivals_link_text', 'View All')); ?></a>
    </div>
    <?php if (class_exists('WooCommerce')) : ?>
      <div class="wk-home-products-rail">
        <?php
        $new_arrivals = new WP_Query([
          'post_type'      => 'product',
          'posts_per_page' => 8,
          'orderby'        => 'date',
          'order'          => 'DESC',
        ]);
        if ($new_arrivals->have_posts()) :
          while ($new_arrivals->have_posts()) : $new_arrivals->the_post();
            $product = wc_get_product( get_the_ID() );
            if ($product && $product->is_visible()) wk_product_card($product, ['layout' => 'editorial']);
          endwhile;
          wp_reset_postdata();
        else :
          for ($i = 1; $i <= 4; $i++) :
        ?>
          <article class="wk-pcard wk-pcard--editorial">
            <a href="<?php echo esc_url($shop_url); ?>" class="wk-pcard-link">
              <div class="wk-pcard__media">
                <img src="<?php echo esc_url($img_base . 'product-' . (5 - $i) . '.png'); ?>" alt="<?php printf(esc_attr__('New Arrival %d', 'whitekurti'), $i); ?>" class="wk-pcard__img" loading="lazy">
              </div>
              <div class="wk-pcard-info">
                <span class="wk-pcard-cat"><?php esc_html_e('Fresh Arrivals', 'whitekurti'); ?></span>
                <h3 class="wk-pcard-title"><?php printf(esc_html__('New Arrival Suit %d', 'whitekurti'), $i); ?></h3>
                <div class="wk-pcard-price"><span class="wk-price">&#8377;1,<?php echo ($i * 499); ?></span></div>
              </div>
            </a>
          </article>
        <?php
          endfor;
        endif;
        ?>
      </div>
    <?php else : ?>
      <div class="wk-home-products-rail">
        <?php for ($i = 1; $i <= 4; $i++) : ?>
          <article class="wk-pcard wk-pcard--editorial">
            <a href="#" class="wk-pcard-link">
              <div class="wk-pcard__media">
                <img src="<?php echo esc_url($img_base . 'product-' . (5 - $i) . '.png'); ?>" alt="<?php printf(esc_attr__('New Arrival %d', 'whitekurti'), $i); ?>" class="wk-pcard__img" loading="lazy">
              </div>
              <div class="wk-pcard-info">
                <span class="wk-pcard-cat"><?php esc_html_e('Fresh Arrivals', 'whitekurti'); ?></span>
                <h3 class="wk-pcard-title"><?php printf(esc_html__('New Arrival Suit %d', 'whitekurti'), $i); ?></h3>
                <div class="wk-pcard-price"><span class="wk-price">&#8377;1,<?php echo ($i * 499); ?></span></div>
              </div>
            </a>
          </article>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <!-- ═══════════ 6. EDITORIAL STRIP ═══════════ -->
  <?php if ( get_theme_mod('wk_show_editorial', true) ) : ?>
  <section class="wk-editorial-strip">
    <div class="wk-editorial-strip__inner">
      <h2 class="wk-editorial-strip__title"><?php echo esc_html(get_theme_mod('wk_editorial_title', '"Where tradition meets the modern woman."')); ?></h2>
      <p class="wk-editorial-strip__body"><?php echo esc_html(get_theme_mod('wk_editorial_body', 'We believe that true luxury lies in simplicity. Our journey began with a singular vision: to create ethnic wear that transcends seasons and trends — blending time-honored Indian craftsmanship with contemporary silhouettes.')); ?></p>
      <a href="<?php echo esc_url(get_theme_mod('wk_editorial_link_url', $shop_url)); ?>" class="wk-link-underline"><?php echo esc_html(get_theme_mod('wk_editorial_link_text', 'Our Story')); ?></a>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════════ 7. TESTIMONIALS CAROUSEL ═══════════ -->
  <?php try { wk_render_testimonials_carousel(); } catch(\Throwable $e) { if(defined('WP_DEBUG')&&WP_DEBUG) echo '<!-- testimonials error: '.esc_html($e->getMessage()).' -->'; } ?>

  <!-- ═══════════ 7b. LOOKBOOK / EDITORIAL ═══════════ -->
  <?php try { wk_render_lookbook(); } catch(\Throwable $e) { if(defined('WP_DEBUG')&&WP_DEBUG) echo '<!-- lookbook error: '.esc_html($e->getMessage()).' -->'; } ?>

  <!-- ═══════════ 8. TRUST STRIP ═══════════ -->
  <?php if ( get_theme_mod('wk_show_trust_strip', true) ) { try { wk_trust_strip(); } catch(\Throwable $e) {} } ?>

  <!-- ═══════════ 9. INSTAGRAM GRID ═══════════ -->
  <?php try { wk_render_instagram_grid(); } catch(\Throwable $e) { if(defined('WP_DEBUG')&&WP_DEBUG) echo '<!-- instagram error: '.esc_html($e->getMessage()).' -->'; } ?>

</main>
<?php get_footer();
