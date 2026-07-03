<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * WhiteKurti footer.php v4
 * Fixed: no duplicate trust strip, social icons (IG+FB+WA), newsletter toggle, clean menu
 */
$brand      = get_theme_mod( 'wk_brand_mode', 'white' );
$brand_name = $brand === 'black' ? 'BlackKurti' : 'WhiteKurti';
$wc_active  = class_exists( 'WooCommerce' );
// Social icons rendered via wk_social_icons_html() — see inc/social-media.php
$show_newsletter = (bool) get_theme_mod( 'wk_show_newsletter', true );
?>
<footer class="wk-footer" role="contentinfo">

	<div class="wk-footer__main">
		<div class="wk-container">
			<div class="wk-footer__grid">

				<div class="wk-footer__brand-col">
					<div class="wk-footer__brand-name"><?php echo esc_html($brand_name); ?></div>
					<p class="wk-footer__tagline"><?php
						$default = $brand === 'black'
							? 'Modern black ethnicwear blending understated luxury and contemporary Indian fashion.'
							: 'Elegant white Indian wear — timeless luxury for the modern woman.';
						echo esc_html(get_theme_mod('wk_footer_tagline', $default));
					?></p>
					<?php try { echo wk_social_icons_html(); } catch(\Throwable $e) {} ?>
				</div>

				<div class="wk-footer__col">
					<h4 class="wk-footer__col-title">Shop</h4>
					<ul class="wk-footer__nav-list">
						<?php
						$shop_url = $wc_active ? wc_get_page_permalink('shop') : home_url('/shop');
						$cats = [];
						if ($wc_active) {
							$terms = get_terms(['taxonomy'=>'product_cat','hide_empty'=>false,'number'=>8,'parent'=>0,'exclude'=>[get_option('default_product_cat')],'orderby'=>'name','order'=>'ASC']);
							if (!is_wp_error($terms)) $cats = $terms;
						}
						if ($cats) :
							foreach ($cats as $cat) : ?>
							<li><a href="<?php echo esc_url( is_wp_error(get_term_link($cat)) ? "#" : get_term_link($cat) ); ?>"><?php echo esc_html($cat->name); ?></a></li>
							<?php endforeach;
						else :
							$items = ['New Arrivals','Kurta Sets','Suits','Dresses','Sarees','Co-ords'];
							foreach ($items as $item) : ?>
							<li><a href="<?php echo esc_url($shop_url); ?>"><?php echo esc_html($item); ?></a></li>
							<?php endforeach;
						endif; ?>
						<li><a href="<?php echo esc_url($shop_url); ?>">All Products</a></li>
					</ul>
				</div>

				<div class="wk-footer__col">
					<h4 class="wk-footer__col-title">Help</h4>
					<ul class="wk-footer__nav-list">
						<?php
						$help = ['contact'=>'Contact Us','shipping'=>'Shipping Policy','returns'=>'Returns & Exchange','size-guide'=>'Size Guide'];
						foreach ($help as $slug => $label) :
							$p = get_page_by_path($slug);
							$u = $p ? get_permalink($p->ID) : home_url('/'.$slug);
						?>
						<li><a href="<?php echo esc_url($u); ?>"><?php echo esc_html($label); ?></a></li>
						<?php endforeach; ?>
						<?php if ($wc_active) : ?>
						<li><a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">Track Order</a></li>
						<?php endif; ?>
					</ul>
				</div>

				<div class="wk-footer__col">
					<h4 class="wk-footer__col-title">Company</h4>
					<ul class="wk-footer__nav-list">
						<?php
						$co = ['about'=>'About Us','privacy-policy'=>'Privacy Policy','terms'=>'Terms & Conditions'];
						foreach ($co as $slug => $label) :
							$p = get_page_by_path($slug);
							$u = $p ? get_permalink($p->ID) : home_url('/'.$slug);
						?>
						<li><a href="<?php echo esc_url($u); ?>"><?php echo esc_html($label); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>

			</div>
		</div>
	</div>

	<?php if ($show_newsletter) : ?>
	<div class="wk-footer__newsletter">
		<div class="wk-container">
			<div class="wk-newsletter">
				<div class="wk-newsletter__text">
					<h3><?php echo esc_html(get_theme_mod('wk_newsletter_title', '🎁 Get 10% Off Your First Order')); ?></h3>
					<p><?php echo esc_html(get_theme_mod('wk_newsletter_desc', 'Subscribe for exclusive deals, new arrivals every Sunday & style tips — straight to your inbox.')); ?></p>
				</div>
				<form class="wk-newsletter__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<input type="hidden" name="action" value="wk_newsletter_signup">
					<?php wp_nonce_field('wk_newsletter','wk_nl_nonce'); ?>
					<input type="email" name="email" class="wk-newsletter__input" placeholder="Your email address" required>
					<button type="submit" class="wk-btn"><?php echo esc_html(get_theme_mod('wk_newsletter_btn','Get 10% Off')); ?></button>
				</form>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<div class="wk-footer__bottom">
		<div class="wk-container">
			<div class="wk-footer__bottom-inner">
				<p class="wk-footer__copy"><?php
					$y = gmdate('Y');
					echo esc_html(get_theme_mod('wk_footer_text', '© '.$y.' '.$brand_name.'. Designed in India ♥'));
				?></p>
				<div class="wk-footer__payment-icons">
					<span class="wk-pay-icon">Visa</span>
					<span class="wk-pay-icon">Mastercard</span>
					<span class="wk-pay-icon">UPI</span>
					<span class="wk-pay-icon">Razorpay</span>
					<span class="wk-pay-icon">COD</span>
				</div>
			</div>
		</div>
	</div>
</footer>
<?php
wk_cart_drawer();
wk_mobile_menu();
wk_search_overlay();
wk_toast_container();
wp_footer();
?>
</body>
</html>
