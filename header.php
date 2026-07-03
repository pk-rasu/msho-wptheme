<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#ffffff">
<link rel="profile" href="https://gmpg.org/xfn/11">
<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>
	data-theme="<?php echo esc_attr( get_theme_mod( 'wk_brand_mode', 'white' ) ); ?>"
	data-palette="<?php echo esc_attr( get_theme_mod( 'wk_palette', 'ivory' ) ); ?>"
	data-type="<?php echo esc_attr( get_theme_mod( 'wk_type_pairing', 'editorial' ) ); ?>">
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#wk-main"><?php esc_html_e( 'Skip to content', 'whitekurti' ); ?></a>

<?php wk_announcement_strip(); ?>

<header class="wk-header" id="wk-header" role="banner">
	<div class="wk-header__inner">

		<!-- Left: Mobile menu toggle + Search -->
		<div class="wk-header__left">
			<button class="wk-icon-btn wk-header__menu-toggle" id="wk-menu-toggle" type="button"
				aria-label="<?php esc_attr_e( 'Open menu', 'whitekurti' ); ?>"
				aria-expanded="false" aria-controls="wk-menu-overlay">
				<?php wk_icon( 'menu', 22 ); ?>
			</button>
			<button class="wk-icon-btn wk-header__search-toggle" id="wk-search-toggle" type="button"
				aria-label="<?php esc_attr_e( 'Search', 'whitekurti' ); ?>"
				aria-expanded="false" aria-controls="wk-search-overlay">
				<?php wk_icon( 'search', 20 ); ?>
			</button>
		</div>

		<!-- Center: Logo / Brand -->
		<div class="wk-header__brand">
			<?php if ( has_custom_logo() ) :
				the_custom_logo();
			else :
				$brand_name = get_theme_mod( 'wk_brand_mode', 'white' ) === 'black' ? 'BlackKurti' : 'WhiteKurti';
			?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="wk-brand-name" rel="home" aria-label="<?php echo esc_attr( $brand_name ); ?> &mdash; <?php bloginfo( 'description' ); ?>">
				<?php echo esc_html( $brand_name ); ?>
			</a>
			<?php endif; ?>
		</div>

		<!-- Right actions -->
		<div class="wk-header__actions">
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<a class="wk-icon-btn wk-header__action-link" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ); ?>"
				aria-label="<?php esc_attr_e( 'My account', 'whitekurti' ); ?>">
				<?php wk_icon( 'user', 20 ); ?>
			</a>

			<?php if ( function_exists( 'YITH_WCWL' ) ) : ?>
			<?php
			$wl_page_id = get_theme_mod('wk_wl_page_id',0);
			$wl_url     = $wl_page_id ? get_permalink($wl_page_id) : home_url('/wishlist');
			?>
			<a class="wk-icon-btn wk-header__action-link" href="<?php echo esc_url($wl_url); ?>"
				aria-label="Wishlist" style="position:relative;">
				<?php wk_icon( 'heart', 20 ); ?>
			</a>
			<?php endif; ?>

			<button class="wk-icon-btn wk-header__cart-btn" id="wk-cart-toggle" type="button"
				aria-label="<?php echo esc_attr( sprintf( __( 'Cart (%d items)', 'whitekurti' ), (WC()->cart ? WC()->cart->get_cart_contents_count() : 0) ) ); ?>"
				aria-expanded="false" aria-controls="wk-cart-overlay">
				<?php wk_icon( 'bag', 20 ); ?>
				<span class="wk-cart-count<?php echo (WC()->cart ? WC()->cart->get_cart_contents_count() : 0) ? ' has-items' : ''; ?>">
					<?php echo absint( (WC()->cart ? WC()->cart->get_cart_contents_count() : 0) ); ?>
				</span>
			</button>
			<?php endif; // class_exists WooCommerce ?>
		</div>

	</div><!-- /.wk-header__inner -->

	<!-- Desktop Navigation Bar -->
	<nav class="wk-desktop-nav" aria-label="<?php esc_attr_e( 'Main navigation', 'whitekurti' ); ?>">
		<div class="wk-container">
			<?php wp_nav_menu( [
				'theme_location' => 'primary',
				'menu_class'     => 'wk-desktop-nav__list',
				'container'      => false,
				'fallback_cb'    => 'wk_desktop_fallback_menu',
				'depth'          => 2,
			] ); ?>
		</div>
	</nav>
</header>
