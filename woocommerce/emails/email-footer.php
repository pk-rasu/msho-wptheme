<?php
/**
 * WhiteKurti — Branded WooCommerce Email Footer
 * @version 9.3.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$brand_nm  = get_theme_mod('wk_brand_mode','white') === 'black' ? 'BlackKurti' : 'WhiteKurti';
$accent    = get_theme_mod('wk_email_accent', '#6B1E3E');
$footer_bg = get_theme_mod('wk_email_footer_bg', '#120F0C');
$ig_url    = get_theme_mod('wk_social_instagram_url','');
$fb_url    = get_theme_mod('wk_social_facebook_url','');
$wa_url    = get_theme_mod('wk_social_whatsapp_url','');
$year      = gmdate('Y');
$shop_url  = class_exists('WooCommerce') ? wc_get_page_permalink('shop') : home_url('/shop');
$policy_url= home_url('/privacy-policy');
$returns_url = home_url('/returns');
?>
  </div><!-- /.wk-email-body -->

  <!-- Footer divider -->
  <div style="height:1px;background:#e2dbd2;margin:0 40px;"></div>

  <!-- Quick links -->
  <div style="background:<?php echo esc_attr($footer_bg); ?>;padding:24px 40px;text-align:center;">
    <p style="color:rgba(237,229,218,.5);font-size:11px;letter-spacing:0.15em;text-transform:uppercase;margin:0 0 12px;">Quick Links</p>
    <p style="margin:0 0 16px;">
      <a href="<?php echo esc_url($shop_url); ?>" style="color:#EDE5DA;font-size:12px;text-decoration:none;margin:0 10px;">Shop</a>
      <a href="<?php echo esc_url(home_url('/track-order')); ?>" style="color:#EDE5DA;font-size:12px;text-decoration:none;margin:0 10px;">Track Order</a>
      <a href="<?php echo esc_url($returns_url); ?>" style="color:#EDE5DA;font-size:12px;text-decoration:none;margin:0 10px;">Returns</a>
      <a href="<?php echo esc_url(home_url('/contact')); ?>" style="color:#EDE5DA;font-size:12px;text-decoration:none;margin:0 10px;">Contact Us</a>
    </p>
    <?php if ( $ig_url || $fb_url || $wa_url ) : ?>
    <p style="margin:0 0 14px;">
      <?php if ($ig_url) : ?><a href="<?php echo esc_url($ig_url); ?>" style="display:inline-block;width:32px;height:32px;background:rgba(255,255,255,.1);border-radius:50%;line-height:32px;text-align:center;text-decoration:none;margin:0 4px;" title="Instagram">📸</a><?php endif; ?>
      <?php if ($fb_url) : ?><a href="<?php echo esc_url($fb_url); ?>" style="display:inline-block;width:32px;height:32px;background:rgba(255,255,255,.1);border-radius:50%;line-height:32px;text-align:center;text-decoration:none;margin:0 4px;" title="Facebook">👍</a><?php endif; ?>
      <?php if ($wa_url) : ?><a href="<?php echo esc_url($wa_url); ?>" style="display:inline-block;width:32px;height:32px;background:rgba(255,255,255,.1);border-radius:50%;line-height:32px;text-align:center;text-decoration:none;margin:0 4px;" title="WhatsApp">💬</a><?php endif; ?>
    </p>
    <?php endif; ?>
    <p style="color:rgba(237,229,218,.4);font-size:11px;margin:0 0 6px;">
      © <?php echo $year; ?> <?php echo esc_html($brand_nm); ?> · All rights reserved
    </p>
    <p style="margin:0;">
      <a href="<?php echo esc_url($policy_url); ?>" style="color:rgba(237,229,218,.35);font-size:10.5px;text-decoration:none;margin:0 6px;">Privacy Policy</a>
      <a href="<?php echo esc_url(home_url('/terms')); ?>" style="color:rgba(237,229,218,.35);font-size:10.5px;text-decoration:none;margin:0 6px;">Terms</a>
      <a href="<?php echo esc_url(home_url('/unsubscribe')); ?>" style="color:rgba(237,229,218,.35);font-size:10.5px;text-decoration:none;margin:0 6px;">Unsubscribe</a>
    </p>
  </div>

</div><!-- /.wk-email-wrap -->
</td></tr>
</table>
</body>
</html>
