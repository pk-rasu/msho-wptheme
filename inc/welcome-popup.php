<?php
/**
 * WhiteKurti — Welcome Popup & COD Confirmation Popup
 * - Welcome popup for new visitors (email capture or coupon)
 * - COD order confirmation reassurance popup
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ═══════════════════════════════════════════════════════
// WELCOME / ENTRY POPUP
// ═══════════════════════════════════════════════════════

add_action( 'wp_footer', 'wk_welcome_popup_html', 95 );
function wk_welcome_popup_html() {
	if ( ! class_exists('WooCommerce') ) return;
	$s = wk_welcome_popup_settings();
	if ( ! $s['enabled'] ) return;
	if ( is_checkout() || is_cart() ) return;
	if ( function_exists('is_account_page') && is_account_page() ) return;

	$pages = $s['pages'];
	if ( $pages === 'home' && ! is_front_page() ) return;
	if ( $pages === 'product' && ! is_singular('product') ) return;
	if ( $pages === 'shop' && function_exists('is_shop') && ! is_shop() && ! is_product_category() ) return;
	?>
	<div id="wk-welcome-popup" class="wk-welcome-popup" hidden role="dialog" aria-modal="true" aria-label="Welcome offer">
		<div class="wk-welcome-popup__overlay" id="wk-wp-overlay"></div>
		<div class="wk-welcome-popup__modal">
			<button class="wk-welcome-popup__close" id="wk-wp-close" aria-label="Close">×</button>

			<?php if ( $s['image'] ) : ?>
			<div class="wk-welcome-popup__img">
				<img src="<?php echo esc_url($s['image']); ?>" alt="" loading="lazy" />
			</div>
			<?php endif; ?>

			<div class="wk-welcome-popup__content">
				<?php if ( $s['eyebrow'] ) : ?>
				<p class="wk-welcome-popup__eyebrow"><?php echo esc_html($s['eyebrow']); ?></p>
				<?php endif; ?>

				<h2 class="wk-welcome-popup__heading"><?php echo esc_html($s['heading']); ?></h2>
				<p class="wk-welcome-popup__body"><?php echo esc_html($s['body']); ?></p>

				<?php if ( $s['coupon'] ) : ?>
				<div class="wk-welcome-popup__coupon" onclick="navigator.clipboard&&navigator.clipboard.writeText('<?php echo esc_js($s['coupon']); ?>');this.querySelector('.wk-wlc-hint').textContent='Copied!';">
					<span class="wk-wlc-code"><?php echo esc_html($s['coupon']); ?></span>
					<span class="wk-wlc-hint">Click to copy</span>
				</div>
				<?php endif; ?>

				<?php if ( $s['show_email'] ) : ?>
				<form class="wk-welcome-popup__form" id="wk-wp-form" novalidate>
					<div class="wk-welcome-popup__field">
						<input type="email" name="wlp_email" placeholder="<?php echo esc_attr($s['email_placeholder']); ?>"
						       required class="wk-welcome-popup__input" />
						<button type="submit" class="wk-welcome-popup__btn"
						        style="background:<?php echo esc_attr($s['btn_color']); ?>;">
							<?php echo esc_html($s['btn_text']); ?>
						</button>
					</div>
					<p class="wk-welcome-popup__privacy">No spam, unsubscribe anytime.</p>
				</form>
				<?php else : ?>
				<a href="<?php echo esc_url($s['cta_url'] ?: home_url('/shop')); ?>"
				   class="wk-welcome-popup__btn"
				   style="background:<?php echo esc_attr($s['btn_color']); ?>; text-decoration:none; display:inline-block;">
					<?php echo esc_html($s['btn_text']); ?>
				</a>
				<?php endif; ?>

				<button type="button" id="wk-wp-dismiss" class="wk-welcome-popup__skip">
					<?php echo esc_html($s['skip_text'] ?: 'No thanks, I\'ll pay full price'); ?>
				</button>
			</div>
		</div>
	</div>

	<style id="wk-welcome-popup-css">
	.wk-welcome-popup { position:fixed; inset:0; z-index:100000; display:flex; align-items:center; justify-content:center; padding:20px; }
	.wk-welcome-popup__overlay { position:absolute; inset:0; background:rgba(0,0,0,.65); cursor:pointer; }
	.wk-welcome-popup__modal {
		position:relative; background:#fff; border-radius:12px; overflow:hidden;
		max-width:680px; width:100%; max-height:90vh; overflow-y:auto;
		display:flex; flex-direction:row; box-shadow:0 20px 60px rgba(0,0,0,.3);
		animation:wk-wlp-in .4s cubic-bezier(.34,1.56,.64,1);
	}
	@keyframes wk-wlp-in { from { transform:scale(.85); opacity:0; } to { transform:scale(1); opacity:1; } }
	.wk-welcome-popup__close { position:absolute; top:12px; right:14px; background:rgba(255,255,255,.9); border:none; font-size:22px; cursor:pointer; z-index:10; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; line-height:1; color:#333; }
	.wk-welcome-popup__img { flex:0 0 45%; overflow:hidden; }
	.wk-welcome-popup__img img { width:100%; height:100%; object-fit:cover; display:block; }
	.wk-welcome-popup__content { flex:1; padding:36px 32px; display:flex; flex-direction:column; justify-content:center; }
	.wk-welcome-popup__eyebrow { font-size:11px; letter-spacing:.15em; text-transform:uppercase; color:var(--accent,#6B1E3E); margin:0 0 10px; font-weight:600; }
	.wk-welcome-popup__heading { font-size:clamp(22px,3vw,30px); font-weight:700; margin:0 0 12px; line-height:1.2; }
	.wk-welcome-popup__body { font-size:14px; color:#555; margin:0 0 20px; line-height:1.6; }
	.wk-welcome-popup__coupon { display:inline-flex; flex-direction:column; align-items:center; border:2px dashed var(--accent,#6B1E3E); padding:10px 20px; border-radius:6px; cursor:pointer; margin-bottom:20px; }
	.wk-wlc-code { font-size:20px; font-weight:800; letter-spacing:.15em; color:var(--accent,#6B1E3E); }
	.wk-wlc-hint { font-size:11px; color:#888; margin-top:2px; }
	.wk-welcome-popup__field { display:flex; gap:8px; margin-bottom:8px; }
	.wk-welcome-popup__input { flex:1; padding:12px 14px; border:1.5px solid #ddd; border-radius:6px; font-size:14px; }
	.wk-welcome-popup__btn { padding:12px 20px; border:none; border-radius:6px; color:#fff; font-size:14px; font-weight:600; cursor:pointer; white-space:nowrap; }
	.wk-welcome-popup__privacy { font-size:11px; color:#9ca3af; margin:0; }
	.wk-welcome-popup__skip { background:none; border:none; color:#9ca3af; font-size:12px; cursor:pointer; margin-top:16px; padding:0; text-decoration:underline; }
	@media (max-width:600px) {
		.wk-welcome-popup__modal { flex-direction:column; max-height:95vh; }
		.wk-welcome-popup__img { height:180px; flex:0 0 180px; }
		.wk-welcome-popup__content { padding:24px 20px; }
		.wk-welcome-popup__field { flex-direction:column; }
	}
	</style>

	<script>
	(function(){
		var COOKIE = 'wk_wlp_shown';
		var delay  = <?php echo absint($s['delay_sec']) * 1000; ?>;
		var popup  = document.getElementById('wk-welcome-popup');
		if (!popup) return;

		function setCookie(days) {
			var d = new Date(); d.setTime(d.getTime() + (days*24*60*60*1000));
			document.cookie = COOKIE + '=1;expires=' + d.toUTCString() + ';path=/';
		}
		function getCookie() { return document.cookie.indexOf(COOKIE + '=') !== -1; }

		if (getCookie()) return;

		function showPopup() {
			popup.hidden = false;
			document.body.style.overflow = 'hidden';
		}
		function closePopup() {
			popup.hidden = true;
			document.body.style.overflow = '';
			setCookie(<?php echo absint($s['repeat_days'] ?: 7); ?>);
		}

		setTimeout(showPopup, delay);

		var overlay = document.getElementById('wk-wp-overlay');
		var closeBtn = document.getElementById('wk-wp-close');
		var dismiss  = document.getElementById('wk-wp-dismiss');

		overlay  && overlay.addEventListener('click', closePopup);
		closeBtn && closeBtn.addEventListener('click', closePopup);
		dismiss  && dismiss.addEventListener('click', closePopup);

		// Email form submit
		var form = document.getElementById('wk-wp-form');
		if (form) {
			form.addEventListener('submit', function(e) {
				e.preventDefault();
				var email = form.querySelector('[name="wlp_email"]').value;
				if (!email) return;
				// Store email (admin can hook into wk_welcome_popup_email_submit action via AJAX)
				fetch('<?php echo esc_url(admin_url("admin-ajax.php")); ?>', {
					method:'POST',
					headers:{'Content-Type':'application/x-www-form-urlencoded'},
					body:'action=wk_welcome_email&email='+encodeURIComponent(email)+'&nonce=<?php echo wp_create_nonce("wk_wp_email"); ?>'
				});
				form.innerHTML = '<p style="color:#166534;font-weight:600;text-align:center;padding:20px 0;">🎉 Thank you! Check your inbox for the code.</p>';
				setTimeout(closePopup, 2000);
			});
		}
	})();
	</script>
	<?php
}

// AJAX: store welcome popup email
add_action( 'wp_ajax_nopriv_wk_welcome_email', 'wk_welcome_popup_save_email' );
add_action( 'wp_ajax_wk_welcome_email',        'wk_welcome_popup_save_email' );
function wk_welcome_popup_save_email() {
	if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'wk_wp_email' ) ) wp_die();
	$email = sanitize_email( $_POST['email'] ?? '' );
	if ( ! is_email($email) ) wp_die();
	// Save to a simple option list
	$list   = get_option( 'wk_popup_emails', [] );
	$list[] = [ 'email' => $email, 'date' => current_time('Y-m-d H:i:s') ];
	update_option( 'wk_popup_emails', $list );
	wp_send_json_success();
}

function wk_welcome_popup_settings() {
	return [
		'enabled'          => get_theme_mod( 'wk_wlp_enabled',       false ),
		'pages'            => get_theme_mod( 'wk_wlp_pages',         'all' ),
		'delay_sec'        => get_theme_mod( 'wk_wlp_delay',         5 ),
		'repeat_days'      => get_theme_mod( 'wk_wlp_repeat_days',   7 ),
		'image'            => get_theme_mod( 'wk_wlp_image',         '' ),
		'eyebrow'          => get_theme_mod( 'wk_wlp_eyebrow',       'Welcome to WhiteKurti' ),
		'heading'          => get_theme_mod( 'wk_wlp_heading',       'Get 10% Off Your First Order' ),
		'body'             => get_theme_mod( 'wk_wlp_body',          'Sign up for exclusive deals, new arrivals every Thursday, and style inspiration.' ),
		'coupon'           => get_theme_mod( 'wk_wlp_coupon',        'WELCOME10' ),
		'show_email'       => get_theme_mod( 'wk_wlp_show_email',    true ),
		'email_placeholder'=> get_theme_mod( 'wk_wlp_email_ph',      'Enter your email' ),
		'btn_text'         => get_theme_mod( 'wk_wlp_btn_text',      'Claim My Discount' ),
		'btn_color'        => get_theme_mod( 'wk_wlp_btn_color',     '#6B1E3E' ),
		'cta_url'          => get_theme_mod( 'wk_wlp_cta_url',       '' ),
		'skip_text'        => get_theme_mod( 'wk_wlp_skip',          'No thanks, I\'ll pay full price' ),
	];
}

// Customizer
add_action( 'customize_register', 'wk_welcome_popup_customizer' );
function wk_welcome_popup_customizer( $wp_customize ) {
	$wp_customize->add_section( 'wk_welcome_popup', [
		'title'    => '👋 Welcome Popup',
		'panel'    => 'wk_panel',
		'priority' => 65,
	] );
	$s = [
		[ 'wk_wlp_enabled',    'Enable Welcome Popup',          'checkbox', false ],
		[ 'wk_wlp_pages',      'Show On',                       'select',   'all' ],
		[ 'wk_wlp_delay',      'Delay (seconds)',               'number',   5 ],
		[ 'wk_wlp_repeat_days','Repeat after (days)',           'number',   7 ],
		[ 'wk_wlp_image',      'Image URL (portrait 400×600)',  'text',     '' ],
		[ 'wk_wlp_eyebrow',    'Eyebrow Text',                  'text',     'Welcome to WhiteKurti' ],
		[ 'wk_wlp_heading',    'Heading',                       'text',     'Get 10% Off Your First Order' ],
		[ 'wk_wlp_body',       'Body Text (popup description)', 'textarea', 'Sign up for exclusive deals, new arrivals every Thursday, and style inspiration.' ],
		[ 'wk_wlp_coupon',     'Coupon Code (leave blank to hide)','text',  'WELCOME10' ],
		[ 'wk_wlp_show_email', 'Show Email Capture Form',       'checkbox', true ],
		[ 'wk_wlp_btn_text',   'Button Text',                   'text',     'Claim My Discount' ],
		[ 'wk_wlp_btn_color',  'Button Color',                  'text',     '#6B1E3E' ],
		[ 'wk_wlp_skip',       'Skip Link Text',                'text',     "No thanks, I'll pay full price" ],
	];
	foreach ( $s as $item ) {
		$sanitize = $item[2] === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field';
		$wp_customize->add_setting( $item[0], [ 'default' => $item[3], 'sanitize_callback' => $sanitize, 'transport' => 'refresh' ] );
		$args = [ 'label' => $item[1], 'section' => 'wk_welcome_popup', 'type' => $item[2] ];
		if ( $item[2] === 'select' ) $args['choices'] = [ 'all' => 'All Pages', 'home' => 'Homepage Only', 'product' => 'Product Pages', 'shop' => 'Shop/Category Pages' ];
		$wp_customize->add_control( $item[0], $args );
	}
}

// ═══════════════════════════════════════════════════════
// COD CONFIRMATION POPUP
// ═══════════════════════════════════════════════════════

// Use woocommerce_thankyou hook - fires directly on order-received page with order ID
add_action( 'woocommerce_thankyou', 'wk_cod_popup_html', 20 );
function wk_cod_popup_html( $order_id ) {
	if ( ! $order_id ) return;
	$order = wc_get_order( $order_id );
	if ( ! $order ) return;
	if ( $order->get_payment_method() !== 'cod' ) return;

	$name  = $order->get_billing_first_name();
	$total = wc_price( $order->get_total() );
	?>
	<div id="wk-cod-popup" class="wk-cod-popup" role="dialog" aria-modal="true">
		<div class="wk-cod-popup__box">
			<div class="wk-cod-popup__icon">📦</div>
			<h2 class="wk-cod-popup__title">Order Confirmed, <?php echo esc_html($name ?: 'Friend'); ?>!</h2>
			<p class="wk-cod-popup__sub">Your Cash on Delivery order has been placed successfully.</p>
			<div class="wk-cod-popup__details">
				<div class="wk-cod-popup__detail-row">
					<span>Order Total</span>
					<strong><?php echo $total; ?></strong>
				</div>
				<div class="wk-cod-popup__detail-row">
					<span>Payment</span>
					<strong>Cash on Delivery</strong>
				</div>
				<div class="wk-cod-popup__detail-row">
					<span>Delivery</span>
					<strong>3–7 Business Days</strong>
				</div>
			</div>
			<div class="wk-cod-popup__tips">
				<p>✅ Keep the exact amount ready at delivery</p>
				<p>✅ You'll receive a tracking SMS shortly</p>
				<p>✅ Call us if the delivery agent asks for extra charges</p>
			</div>
			<button class="wk-cod-popup__btn" onclick="document.getElementById('wk-cod-popup').remove();">
				Got It — View My Order
			</button>
		</div>
	</div>
	<style>
	.wk-cod-popup { position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:100000; display:flex; align-items:center; justify-content:center; padding:20px; animation:wk-cod-in .4s ease; }
	@keyframes wk-cod-in { from { opacity:0; } to { opacity:1; } }
	.wk-cod-popup__box { background:#fff; border-radius:14px; max-width:420px; width:100%; padding:36px 28px; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,.3); animation:wk-cod-slide .4s cubic-bezier(.34,1.56,.64,1); }
	@keyframes wk-cod-slide { from { transform:translateY(30px) scale(.95); } to { transform:translateY(0) scale(1); } }
	.wk-cod-popup__icon { font-size:48px; margin-bottom:12px; display:block; }
	.wk-cod-popup__title { font-size:22px; font-weight:700; margin:0 0 8px; color:#111; }
	.wk-cod-popup__sub { color:#666; font-size:14px; margin:0 0 20px; }
	.wk-cod-popup__details { background:#f9fafb; border-radius:8px; padding:16px; margin-bottom:16px; text-align:left; }
	.wk-cod-popup__detail-row { display:flex; justify-content:space-between; padding:6px 0; font-size:13px; border-bottom:1px solid #f0f0f0; }
	.wk-cod-popup__detail-row:last-child { border-bottom:none; }
	.wk-cod-popup__tips { background:#f0fdf4; border-radius:8px; padding:14px 16px; text-align:left; margin-bottom:20px; }
	.wk-cod-popup__tips p { font-size:12.5px; color:#166534; margin:4px 0; }
	.wk-cod-popup__btn { background:var(--accent,#6B1E3E); color:#fff; border:none; border-radius:8px; padding:14px 32px; font-size:15px; font-weight:600; cursor:pointer; width:100%; }
	</style>
	<?php
}
