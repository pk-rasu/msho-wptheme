<?php
/**
 * WhiteKurti — Social Media Links System
 * Dedicated customizer section + admin page for all socials
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── All social platforms definition ──────────────────────────────────────────
function wk_social_get_platforms() {
	return [
		'instagram' => [
			'label'        => 'Instagram',
			'placeholder'  => 'https://instagram.com/yourpage',
			'hover_color'  => '#E1306C',
			'show_default' => true,
			'icon'         => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5.5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="1.3" fill="currentColor" stroke="none"/></svg>',
		],
		'facebook' => [
			'label'        => 'Facebook',
			'placeholder'  => 'https://facebook.com/yourpage',
			'hover_color'  => '#1877F2',
			'show_default' => true,
			'icon'         => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
		],
		'whatsapp' => [
			'label'        => 'WhatsApp',
			'placeholder'  => 'https://wa.me/919876543210',
			'hover_color'  => '#25D366',
			'show_default' => true,
			'icon'         => '<svg width="20" height="20" viewBox="0 0 32 32" fill="currentColor"><path d="M16 2.667C8.636 2.667 2.667 8.636 2.667 16c0 2.369.638 4.588 1.75 6.5L2.667 29.333l6.979-1.73A13.28 13.28 0 0016 29.333C23.364 29.333 29.333 23.364 29.333 16S23.364 2.667 16 2.667zm7.17 18.548c-.3.842-1.76 1.617-2.413 1.72-.613.096-1.395.136-2.252-.14-.519-.168-1.185-.391-2.037-.765-3.585-1.543-5.928-5.14-6.109-5.379-.18-.24-1.468-1.948-1.468-3.718s.929-2.642 1.258-3c.33-.36.72-.45.96-.45h.689c.221 0 .522-.082.817.623.3.72 1.02 2.49 1.11 2.67.09.18.15.39.03.63-.12.24-.18.39-.36.6-.18.21-.378.469-.54.63-.18.18-.369.375-.159.735.21.36.933 1.54 2.003 2.494 1.375 1.224 2.533 1.602 2.893 1.782.36.18.57.15.78-.09.21-.24.9-1.05 1.14-1.41s.48-.3.81-.18c.33.12 2.1 1.05 2.46 1.24.36.18.6.27.69.42.09.15.09.87-.21 1.71z"/></svg>',
		],
		'youtube' => [
			'label'        => 'YouTube',
			'placeholder'  => 'https://youtube.com/@yourchannel',
			'hover_color'  => '#FF0000',
			'show_default' => false,
			'icon'         => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.54C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="white"/></svg>',
		],
		'twitter' => [
			'label'        => 'X / Twitter',
			'placeholder'  => 'https://x.com/yourhandle',
			'hover_color'  => '#000000',
			'show_default' => false,
			'icon'         => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
		],
		'tiktok' => [
			'label'        => 'TikTok',
			'placeholder'  => 'https://tiktok.com/@yourhandle',
			'hover_color'  => '#010101',
			'show_default' => false,
			'icon'         => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.27 6.27 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.18 8.18 0 0 0 4.78 1.52V6.79a4.85 4.85 0 0 1-1.01-.1z"/></svg>',
		],
		'pinterest' => [
			'label'        => 'Pinterest',
			'placeholder'  => 'https://pinterest.com/yourprofile',
			'hover_color'  => '#E60023',
			'show_default' => false,
			'icon'         => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.236 2.636 7.855 6.356 9.312-.088-.791-.167-2.005.035-2.868.181-.772 1.24-5.26 1.24-5.26s-.316-.633-.316-1.57c0-1.47.853-2.57 1.912-2.57.902 0 1.34.677 1.34 1.489 0 .908-.578 2.267-.878 3.528-.25 1.056.527 1.914 1.564 1.914 1.878 0 3.14-2.399 3.14-5.24 0-2.162-1.46-3.675-3.546-3.675-2.415 0-3.833 1.813-3.833 3.685 0 .73.281 1.51.63 1.937.069.084.079.158.059.243-.064.268-.207.853-.235.972-.038.16-.126.193-.29.116-1.083-.504-1.76-2.086-1.76-3.358 0-2.734 1.986-5.246 5.728-5.246 3.008 0 5.344 2.143 5.344 5.006 0 2.988-1.883 5.39-4.495 5.39-.879 0-1.706-.457-1.99-.996l-.54 2.023c-.196.754-.724 1.698-1.08 2.272.814.252 1.676.388 2.57.388 5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>',
		],
		'telegram' => [
			'label'        => 'Telegram',
			'placeholder'  => 'https://t.me/yourchannel',
			'hover_color'  => '#229ED9',
			'show_default' => false,
			'icon'         => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8l-1.7 8.02c-.12.58-.46.72-.94.45l-2.6-1.92-1.25 1.21c-.14.14-.26.26-.52.26l.18-2.67 4.74-4.28c.21-.18-.04-.28-.31-.1L7.39 14.6l-2.57-.8c-.56-.17-.57-.56.12-.83l10.03-3.87c.47-.17.88.11.67.7z"/></svg>',
		],
		'snapchat' => [
			'label'        => 'Snapchat',
			'placeholder'  => 'https://snapchat.com/add/yourname',
			'hover_color'  => '#FFFC00',
			'show_default' => false,
			'icon'         => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.206.793c.99 0 4.347.276 5.93 3.821.529 1.193.403 3.219.299 4.847l-.003.06c-.012.18-.022.35-.03.517.05.02.12.045.217.07.31.077.774.033 1.25-.24.138-.08.302-.127.477-.127.166 0 .316.047.444.118.232.143.38.39.38.67 0 .41-.213.812-.793 1.07a5.72 5.72 0 0 1-.79.248c-.046.012-.094.023-.138.033l.022.07c.218.683.668 2.078 3.07 2.428.148.02.26.144.26.295 0 .137-.1.26-.234.29-.424.106-1.4.318-1.59.594-.154.225-.12.536-.067.84l.003.02c.04.23.077.461.051.667-.074.575-.424.916-.827 1.098C18.47 18.28 17.19 18 16.8 18.077l-.012.004c-.24.064-.506.135-.806.135-.257 0-.514-.044-.76-.14-.48-.19-.848-.435-1.108-.576a5.82 5.82 0 0 0-.516-.247c-.346-.13-.677-.193-1.006-.193-.316 0-.627.06-.96.184a5.92 5.92 0 0 0-.51.244c-.26.142-.628.386-1.107.576-.246.096-.504.14-.762.14-.3 0-.566-.07-.807-.135l-.011-.004c-.39-.077-1.669.203-2.62-.241-.403-.182-.753-.523-.827-1.098-.026-.206.012-.437.052-.667l.002-.02c.054-.304.087-.615-.066-.84-.19-.276-1.167-.488-1.591-.594C2.1 15.073 2 14.95 2 14.812c0-.15.11-.274.259-.294 2.4-.35 2.851-1.745 3.07-2.428l.02-.07c-.04-.01-.086-.02-.131-.032-.275-.07-.548-.15-.796-.25C3.714 11.483 3.5 11.08 3.5 10.67c0-.282.148-.527.38-.67a.86.86 0 0 1 .445-.118c.175 0 .339.046.477.127.476.273.94.317 1.25.24.097-.024.167-.049.218-.07l-.032-.578c-.104-1.627-.23-3.653.3-4.845C7.857 1.069 11.215.793 12.206.793z"/></svg>',
		],
		'linkedin' => [
			'label'        => 'LinkedIn',
			'placeholder'  => 'https://linkedin.com/company/yourcompany',
			'hover_color'  => '#0A66C2',
			'show_default' => false,
			'icon'         => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>',
		],
		'threads' => [
			'label'        => 'Threads',
			'placeholder'  => 'https://threads.net/@yourhandle',
			'hover_color'  => '#000000',
			'show_default' => false,
			'icon'         => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.5 12.01v-.017c.004-6.919 5.602-12.478 12.5-12.478 3.362 0 6.496 1.308 8.846 3.683a12.43 12.43 0 0 1 3.654 8.812c0 3.424-.888 6.249-2.567 8.169-1.778 2.039-4.334 3.091-7.397 3.091-.447 0-.897-.023-1.35-.07zm3.217-7.225c-.877.344-1.869.518-2.951.518-2.842 0-4.946-1.578-4.946-3.711 0-2.082 1.948-3.621 4.608-3.621.607 0 1.189.068 1.737.204-.138-.524-.576-.89-1.184-.89-.788 0-1.471.424-2.073 1.281l-1.69-1.267c.873-1.315 2.202-2.028 3.863-2.028 1.981 0 3.397.857 3.981 2.41.14.375.228.777.265 1.195.05.571.025 1.147-.077 1.697-.363 1.948-1.592 3.302-3.533 4.212zm.36-4.17c-.437-.111-.904-.167-1.393-.167-1.279 0-2.108.482-2.108 1.236 0 .736.807 1.213 2.036 1.213 1.508 0 2.567-.68 2.764-1.776.047-.266.063-.542.048-.817l-1.347.311z"/></svg>',
		],
		'sharechat' => [
			'label'        => 'ShareChat',
			'placeholder'  => 'https://sharechat.com/profile/yourname',
			'hover_color'  => '#FF4D4D',
			'show_default' => false,
			'icon'         => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm5.5 6.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-9 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm1.5 9c-2.21 0-4-1.79-4-4h8c0 2.21-1.79 4-4 4z"/></svg>',
		],
	];
}

// ── Helper: get setting for a platform ───────────────────────────────────────
function wk_social_get( $platform, $key, $default = '' ) {
	$id = 'wk_social_' . $platform . '_' . $key;
	return get_theme_mod( $id, $default );
}

// ── Register customizer section ───────────────────────────────────────────────
function wk_social_customizer_register( $wp_customize ) {

	// Dedicated Social Media panel section
	$wp_customize->add_section( 'wk_social_links', [
		'title'       => __( '🌐 Social Media Links', 'whitekurti' ),
		'description' => 'Add your social media profile links. Enable/disable each platform individually. Enabled platforms with URLs will show in the footer.',
		'panel'       => 'wk_panel',
		'priority'    => 48,
	] );

	$platforms = wk_social_get_platforms();
	$priority  = 10;

	foreach ( $platforms as $key => $platform ) {
		$id_show = 'wk_social_' . $key . '_show';
		$id_url  = 'wk_social_' . $key . '_url';

		// Show toggle
		$wp_customize->add_setting( $id_show, [
			'default'           => $platform['show_default'] ? true : false,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( $id_show, [
			'label'    => '✅ Show ' . $platform['label'],
			'section'  => 'wk_social_links',
			'type'     => 'checkbox',
			'priority' => $priority,
		] );

		// URL field
		$wp_customize->add_setting( $id_url, [
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( $id_url, [
			'label'       => $platform['label'] . ' URL',
			'description' => 'e.g. ' . $platform['placeholder'],
			'section'     => 'wk_social_links',
			'type'        => 'url',
			'priority'    => $priority + 1,
		] );

		$priority += 10;
	}

	// Icon size
	$wp_customize->add_setting( 'wk_social_icon_size', [
		'default'           => 38,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'wk_social_icon_size', [
		'label'       => 'Icon Button Size (px)',
		'description' => 'The size of each social icon circle (default 38px)',
		'section'     => 'wk_social_links',
		'type'        => 'number',
		'input_attrs' => [ 'min' => 28, 'max' => 60 ],
		'priority'    => $priority + 10,
	] );

	// Brand hover colors toggle
	$wp_customize->add_setting( 'wk_social_brand_hover', [
		'default'           => true,
		'sanitize_callback' => 'rest_sanitize_boolean',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'wk_social_brand_hover', [
		'label'       => '🎨 Use brand colors on hover (Instagram pink, Facebook blue, etc.)',
		'section'     => 'wk_social_links',
		'type'        => 'checkbox',
		'priority'    => $priority + 11,
	] );
}
add_action( 'customize_register', 'wk_social_customizer_register' );

// ── Render social icons HTML ───────────────────────────────────────────────────
function wk_social_icons_html() {
	$platforms   = wk_social_get_platforms();
	$icon_size   = absint( get_theme_mod( 'wk_social_icon_size', 38 ) );
	$brand_hover = get_theme_mod( 'wk_social_brand_hover', true );
	$out         = '';
	$any         = false;

	foreach ( $platforms as $key => $platform ) {
		$show = get_theme_mod( 'wk_social_' . $key . '_show', $platform['show_default'] );
		if ( ! $show ) continue;

		$url = get_theme_mod( 'wk_social_' . $key . '_url', '' );
		// If no URL set, skip this platform
		if ( ! $url ) continue;

		$any          = true;
		$hover_color  = $brand_hover ? $platform['hover_color'] : '';
		$data_color   = $hover_color ? ' data-hover="' . esc_attr($hover_color) . '"' : '';
		$wa_class     = $key === 'whatsapp' ? ' wk-social-wa' : '';

		$out .= '<a href="' . esc_url($url) . '" class="wk-social-icon' . $wa_class . '"'
			. $data_color
			. ' target="_blank" rel="noopener noreferrer"'
			. ' aria-label="' . esc_attr($platform['label']) . '"'
			. ' style="width:' . $icon_size . 'px;height:' . $icon_size . 'px;"'
			. ' title="' . esc_attr('Follow us on ' . $platform['label']) . '">'
			. $platform['icon']
			. '</a>';
	}

	if ( ! $any ) return '';
	return '<div class="wk-social-icons" aria-label="Follow us on social media">' . $out . '</div>';
}

// ── Output hover color JS ──────────────────────────────────────────────────────
function wk_social_hover_js() {
	if ( ! get_theme_mod( 'wk_social_brand_hover', true ) ) return;
	?>
	<script id="wk-social-hover">
	(function(){
		document.addEventListener('DOMContentLoaded', function() {
			document.querySelectorAll('.wk-social-icon[data-hover]').forEach(function(el) {
				var original = '';
				el.addEventListener('mouseenter', function() {
					original = this.style.background;
					this.style.background = this.getAttribute('data-hover');
					this.style.transform  = 'translateY(-3px) scale(1.08)';
				});
				el.addEventListener('mouseleave', function() {
					this.style.background = original;
					this.style.transform  = '';
				});
			});
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'wk_social_hover_js', 100 );
