<?php
/**
 * WhiteKurti — Cookie Consent Banner
 * GDPR / India PDPB compliant, customizable
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Customizer settings ──────────────────────────────────────────────────────
add_action( 'customize_register', function( $wp_customize ) {
	$wp_customize->add_section( 'wk_cookie', [
		'title'    => __( '🍪 Cookie Consent', 'whitekurti' ),
		'panel'    => 'wk_panel',
		'priority' => 82,
	] );
	$fields = [
		[ 'wk_cookie_enabled',    'Enable Cookie Banner',              'checkbox', true,   '' ],
		[ 'wk_cookie_heading',    'Banner Heading',                    'text',    'We use cookies 🍪', '' ],
		[ 'wk_cookie_text',       'Banner Text',                       'textarea','We use cookies to improve your experience, personalize content, and analyze traffic. By clicking "Accept All", you agree to our cookie policy.', '' ],
		[ 'wk_cookie_accept_btn', 'Accept Button Text',                'text',    'Accept All',   '' ],
		[ 'wk_cookie_decline_btn','Decline Button Text',               'text',    'Decline',      '' ],
		[ 'wk_cookie_manage_btn', 'Manage Preferences Button Text',    'text',    'Manage',       '' ],
		[ 'wk_cookie_position',   'Banner Position',                   'select',  'bottom', '' ],
		[ 'wk_cookie_policy_url', 'Privacy Policy URL',                'url',     '', '' ],
		[ 'wk_cookie_bg',         'Banner Background Color',           'text',    '#1a1410', '' ],
		[ 'wk_cookie_text_color', 'Banner Text Color',                 'text',    '#ede5da', '' ],
		[ 'wk_cookie_btn_bg',     'Accept Button Color',               'text',    '#6B1E3E', '' ],
	];
	$priority = 10;
	foreach ( $fields as [ $id, $label, $type, $default, $desc ] ) {
		$san = $type==='checkbox' ? 'rest_sanitize_boolean' : ($type==='url'?'esc_url_raw':'sanitize_text_field');
		$wp_customize->add_setting($id, ['default'=>$default,'sanitize_callback'=>$san,'transport'=>'refresh']);
		$ctrl = ['label'=>$label,'description'=>$desc,'section'=>'wk_cookie','type'=>$type==='select'?'select':$type,'priority'=>$priority++];
		if ($type==='select') $ctrl['choices'] = ['bottom'=>'Bottom Bar','bottom-right'=>'Bottom Right Popup','bottom-left'=>'Bottom Left Popup'];
		$wp_customize->add_control($id, $ctrl);
	}
} );

// ── Output banner HTML ───────────────────────────────────────────────────────
function wk_cookie_banner() {
	if ( ! get_theme_mod('wk_cookie_enabled', true) ) return;
	$heading    = get_theme_mod('wk_cookie_heading',    'We use cookies 🍪');
	$text       = get_theme_mod('wk_cookie_text',       'We use cookies to improve your experience, personalize content, and analyze traffic. By clicking "Accept All", you agree to our cookie policy.');
	$accept     = get_theme_mod('wk_cookie_accept_btn', 'Accept All');
	$decline    = get_theme_mod('wk_cookie_decline_btn','Decline');
	$manage     = get_theme_mod('wk_cookie_manage_btn', 'Manage');
	$position   = get_theme_mod('wk_cookie_position',   'bottom');
	$policy_url = get_theme_mod('wk_cookie_policy_url', '');
	$bg         = get_theme_mod('wk_cookie_bg',         '#1a1410');
	$tc         = get_theme_mod('wk_cookie_text_color', '#ede5da');
	$btn_bg     = get_theme_mod('wk_cookie_btn_bg',     '#6B1E3E');

	$pos_class = 'wk-cookie--' . esc_attr($position);
	?>
	<div class="wk-cookie-banner <?php echo $pos_class; ?>" id="wk-cookie-banner" role="dialog" aria-modal="false"
	     aria-label="Cookie consent" aria-live="polite"
	     style="background:<?php echo esc_attr($bg); ?>;color:<?php echo esc_attr($tc); ?>;display:none;">
		<div class="wk-cookie-banner__inner">
			<div class="wk-cookie-banner__content">
				<?php if ($heading) : ?>
				<p class="wk-cookie-banner__heading" style="color:<?php echo esc_attr($tc); ?>"><?php echo esc_html($heading); ?></p>
				<?php endif; ?>
				<p class="wk-cookie-banner__text" style="color:<?php echo esc_attr($tc); ?>;opacity:.8">
					<?php echo esc_html($text); ?>
					<?php if ($policy_url) : ?>
					<a href="<?php echo esc_url($policy_url); ?>" style="color:<?php echo esc_attr($tc); ?>" target="_blank" rel="noopener">Learn more</a>
					<?php endif; ?>
				</p>
			</div>
			<div class="wk-cookie-banner__actions">
				<button class="wk-cookie-btn wk-cookie-btn--decline" id="wk-cookie-decline"
				        style="color:<?php echo esc_attr($tc); ?>;border-color:<?php echo esc_attr($tc); ?>;opacity:.6">
					<?php echo esc_html($decline); ?>
				</button>
				<button class="wk-cookie-btn wk-cookie-btn--manage" id="wk-cookie-manage"
				        style="color:<?php echo esc_attr($tc); ?>;border-color:<?php echo esc_attr($tc); ?>">
					<?php echo esc_html($manage); ?>
				</button>
				<button class="wk-cookie-btn wk-cookie-btn--accept" id="wk-cookie-accept"
				        style="background:<?php echo esc_attr($btn_bg); ?>;color:#fff;border-color:<?php echo esc_attr($btn_bg); ?>">
					<?php echo esc_html($accept); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Cookie Preferences Modal -->
	<div class="wk-cookie-prefs" id="wk-cookie-prefs" role="dialog" aria-modal="true" aria-labelledby="wk-prefs-title" style="display:none;">
		<div class="wk-cookie-prefs__overlay" id="wk-prefs-overlay"></div>
		<div class="wk-cookie-prefs__modal" style="--cookie-btn-bg:<?php echo esc_attr($btn_bg); ?>">
			<div class="wk-cookie-prefs__header">
				<h2 id="wk-prefs-title">Cookie Preferences</h2>
				<button class="wk-cookie-prefs__close" id="wk-prefs-close" aria-label="Close">&times;</button>
			</div>
			<div class="wk-cookie-prefs__body">
				<div class="wk-cookie-cat">
					<div class="wk-cookie-cat__info">
						<strong>Necessary Cookies</strong>
						<p>Required for the website to function. Cannot be disabled.</p>
					</div>
					<span class="wk-cookie-toggle wk-cookie-toggle--locked">Always On</span>
				</div>
				<div class="wk-cookie-cat">
					<div class="wk-cookie-cat__info">
						<strong>Analytics Cookies</strong>
						<p>Help us understand how visitors use our site. Used by Google Analytics.</p>
					</div>
					<label class="wk-cookie-toggle__label">
						<input type="checkbox" id="wk-pref-analytics" checked />
						<span class="wk-cookie-toggle__switch"></span>
					</label>
				</div>
				<div class="wk-cookie-cat">
					<div class="wk-cookie-cat__info">
						<strong>Marketing Cookies</strong>
						<p>Used for Facebook Pixel and remarketing ads.</p>
					</div>
					<label class="wk-cookie-toggle__label">
						<input type="checkbox" id="wk-pref-marketing" checked />
						<span class="wk-cookie-toggle__switch"></span>
					</label>
				</div>
				<div class="wk-cookie-cat">
					<div class="wk-cookie-cat__info">
						<strong>Personalization Cookies</strong>
						<p>Remember your preferences like wishlist and recently viewed products.</p>
					</div>
					<label class="wk-cookie-toggle__label">
						<input type="checkbox" id="wk-pref-personalization" checked />
						<span class="wk-cookie-toggle__switch"></span>
					</label>
				</div>
			</div>
			<div class="wk-cookie-prefs__footer">
				<button class="wk-cookie-btn wk-cookie-btn--accept" id="wk-prefs-save" style="background:<?php echo esc_attr($btn_bg); ?>;color:#fff;border-color:<?php echo esc_attr($btn_bg); ?>">Save Preferences</button>
			</div>
		</div>
	</div>

	<script id="wk-cookie-js">
	(function() {
		var COOKIE_KEY = 'wk_cookie_consent';
		var banner = document.getElementById('wk-cookie-banner');
		var prefs  = document.getElementById('wk-cookie-prefs');
		if (!banner) return;

		function getConsent() {
			try { return JSON.parse(localStorage.getItem(COOKIE_KEY)); } catch(e) { return null; }
		}
		function setConsent(val) {
			localStorage.setItem(COOKIE_KEY, JSON.stringify(Object.assign({timestamp: Date.now()}, val)));
			banner.style.display = 'none';
			if (prefs) prefs.style.display = 'none';
		}

		// Show if no consent stored or expired (6 months)
		var stored = getConsent();
		var sixMonths = 180 * 24 * 60 * 60 * 1000;
		if (!stored || (Date.now() - (stored.timestamp||0)) > sixMonths) {
			setTimeout(function() {
				banner.style.display = '';
				banner.style.animation = 'wk-cookie-in .4s ease forwards';
			}, 1200);
		}

		// Accept all
		var btnAccept = document.getElementById('wk-cookie-accept');
		if (btnAccept) btnAccept.addEventListener('click', function() {
			setConsent({necessary:true, analytics:true, marketing:true, personalization:true, all:true});
		});
		// Decline
		var btnDecline = document.getElementById('wk-cookie-decline');
		if (btnDecline) btnDecline.addEventListener('click', function() {
			setConsent({necessary:true, analytics:false, marketing:false, personalization:false, all:false});
		});
		// Manage
		var btnManage = document.getElementById('wk-cookie-manage');
		if (btnManage && prefs) {
			btnManage.addEventListener('click', function() {
				prefs.style.display = '';
				prefs.querySelector('#wk-cookie-prefs')
			});
		}
		// Close prefs
		var btnClose = document.getElementById('wk-prefs-close');
		var overlay  = document.getElementById('wk-prefs-overlay');
		function closePrefs() { if(prefs) prefs.style.display = 'none'; }
		if (btnClose) btnClose.addEventListener('click', closePrefs);
		if (overlay)  overlay.addEventListener('click', closePrefs);
		// Save prefs
		var btnSave = document.getElementById('wk-prefs-save');
		if (btnSave) btnSave.addEventListener('click', function() {
			var a = document.getElementById('wk-pref-analytics');
			var m = document.getElementById('wk-pref-marketing');
			var p = document.getElementById('wk-pref-personalization');
			setConsent({necessary:true, analytics:a&&a.checked, marketing:m&&m.checked, personalization:p&&p.checked});
		});
		// Expose consent checker globally
		window.wkCookieConsent = function(category) {
			var c = getConsent();
			if (!c) return false;
			return c.all === true || c[category] === true;
		};
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'wk_cookie_banner', 98 );

// ── CSS (inline critical) ───────────────────────────────────────────────────
add_action( 'wp_head', function() {
	if ( ! get_theme_mod('wk_cookie_enabled', true) ) return;
	?>
	<style id="wk-cookie-css">
	.wk-cookie-banner{position:fixed;z-index:99997;left:0;right:0;padding:0;box-shadow:0 -4px 30px rgba(0,0,0,.25);transition:transform .4s ease,opacity .4s ease;}
	.wk-cookie-banner--bottom{bottom:0;}
	.wk-cookie-banner--bottom-right{bottom:20px;left:auto;right:20px;max-width:380px;border-radius:12px;box-shadow:0 4px 30px rgba(0,0,0,.25);}
	.wk-cookie-banner--bottom-left{bottom:20px;right:auto;left:20px;max-width:380px;border-radius:12px;box-shadow:0 4px 30px rgba(0,0,0,.25);}
	@keyframes wk-cookie-in{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
	.wk-cookie-banner__inner{display:flex;align-items:center;gap:20px;padding:16px 20px;flex-wrap:wrap;}
	.wk-cookie-banner--bottom-right .wk-cookie-banner__inner,
	.wk-cookie-banner--bottom-left .wk-cookie-banner__inner{flex-direction:column;align-items:flex-start;}
	.wk-cookie-banner__content{flex:1;min-width:200px;}
	.wk-cookie-banner__heading{font-weight:700;font-size:14px;margin:0 0 4px;}
	.wk-cookie-banner__text{font-size:12.5px;line-height:1.55;margin:0;}
	.wk-cookie-banner__text a{text-decoration:underline;}
	.wk-cookie-banner__actions{display:flex;gap:8px;flex-shrink:0;flex-wrap:wrap;align-items:center;}
	.wk-cookie-btn{padding:8px 16px;border-radius:4px;border:1px solid;cursor:pointer;font-family:inherit;font-size:12.5px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;transition:.15s;white-space:nowrap;background:none;}
	.wk-cookie-btn:hover{opacity:.85;}
	/* Prefs modal */
	.wk-cookie-prefs{position:fixed;inset:0;z-index:99999;}
	.wk-cookie-prefs__overlay{position:absolute;inset:0;background:rgba(0,0,0,.6);}
	.wk-cookie-prefs__modal{position:relative;z-index:1;background:#fff;border-radius:12px;max-width:480px;width:90%;max-height:80vh;overflow-y:auto;margin:10vh auto 0;display:flex;flex-direction:column;}
	.wk-cookie-prefs__header{display:flex;justify-content:space-between;align-items:center;padding:20px 24px;border-bottom:1px solid #eee;}
	.wk-cookie-prefs__header h2{margin:0;font-size:17px;font-weight:700;}
	.wk-cookie-prefs__close{background:none;border:none;cursor:pointer;font-size:24px;color:#666;padding:0;line-height:1;}
	.wk-cookie-prefs__body{padding:20px 24px;display:flex;flex-direction:column;gap:16px;}
	.wk-cookie-cat{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 0;border-bottom:1px solid #f0f0f0;}
	.wk-cookie-cat__info strong{display:block;font-size:13.5px;margin-bottom:2px;}
	.wk-cookie-cat__info p{margin:0;font-size:12px;color:#666;line-height:1.4;}
	.wk-cookie-toggle--locked{font-size:11px;font-weight:600;color:#27ae60;white-space:nowrap;}
	.wk-cookie-toggle__label{position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0;}
	.wk-cookie-toggle__label input{opacity:0;width:0;height:0;}
	.wk-cookie-toggle__switch{position:absolute;inset:0;background:#ccc;border-radius:24px;cursor:pointer;transition:.3s;}
	.wk-cookie-toggle__switch::before{content:'';position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;}
	.wk-cookie-toggle__label input:checked + .wk-cookie-toggle__switch{background:var(--cookie-btn-bg,#6B1E3E);}
	.wk-cookie-toggle__label input:checked + .wk-cookie-toggle__switch::before{transform:translateX(20px);}
	.wk-cookie-prefs__footer{padding:16px 24px;border-top:1px solid #eee;}
	</style>
	<?php
}, 3 );
