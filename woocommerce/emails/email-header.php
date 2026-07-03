<?php
/**
 * WhiteKurti — Branded WooCommerce Email Header
 * @version 9.3.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$brand     = get_theme_mod( 'wk_brand_mode', 'white' );
$brand_nm  = $brand === 'black' ? 'BlackKurti' : 'WhiteKurti';
$accent    = get_theme_mod( 'wk_email_accent', '#6B1E3E' );
$bg        = get_theme_mod( 'wk_email_bg', '#fdfcfa' );
$header_bg = get_theme_mod( 'wk_email_header_bg', '#120F0C' );
$logo_url  = '';
if ( has_custom_logo() ) {
	$logo_id  = get_theme_mod('custom_logo');
	$logo_img = wp_get_attachment_image_src( $logo_id, 'full' );
	if ( $logo_img ) $logo_url = $logo_img[0];
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo esc_html( $email_heading ?? get_bloginfo('name') ); ?></title>
<style>
  body { background-color: #f4f0ea; margin: 0; padding: 0; font-family: 'Helvetica Neue', Arial, sans-serif; -webkit-font-smoothing: antialiased; }
  .wk-email-wrap { max-width: 600px; margin: 0 auto; background: <?php echo esc_attr($bg); ?>; }
  .wk-email-header { background: <?php echo esc_attr($header_bg); ?>; text-align: center; padding: 28px 40px; }
  .wk-email-logo { max-height: 48px; width: auto; display: inline-block; }
  .wk-email-logo-text { color: #EDE5DA; font-size: 22px; font-weight: 500; letter-spacing: 0.25em; text-transform: uppercase; text-decoration: none; display: inline-block; }
  .wk-email-body { padding: 0 40px 32px; }
  .wk-email-accent-bar { height: 3px; background: <?php echo esc_attr($accent); ?>; }
  h2.wk-email-title { font-size: 22px; font-weight: 400; color: #120F0C; letter-spacing: 0.04em; margin: 28px 0 16px; }
  p { color: #3E3028; font-size: 14px; line-height: 1.7; margin: 0 0 16px; }
  a { color: <?php echo esc_attr($accent); ?>; }
  @media screen and (max-width: 600px) {
    .wk-email-body { padding: 0 20px 24px; }
    .wk-email-header { padding: 20px; }
  }
</style>
</head>
<body>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f0ea;padding:20px 0;">
<tr><td align="center">
<div class="wk-email-wrap">
  <!-- Header -->
  <div class="wk-email-header">
    <?php if ($logo_url) : ?>
    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($brand_nm); ?>" class="wk-email-logo" />
    <?php else : ?>
    <a href="<?php echo esc_url(home_url('/')); ?>" class="wk-email-logo-text"><?php echo esc_html($brand_nm); ?></a>
    <?php endif; ?>
  </div>
  <!-- Accent bar -->
  <div class="wk-email-accent-bar"></div>
  <!-- Body start -->
  <div class="wk-email-body">
  <?php if ( isset($email_heading) ) : ?>
  <h2 class="wk-email-title"><?php echo esc_html($email_heading); ?></h2>
  <?php endif; ?>
