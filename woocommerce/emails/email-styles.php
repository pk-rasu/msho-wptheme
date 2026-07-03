<?php
/**
 * WhiteKurti — Branded WooCommerce Email Styles
 * @version 9.3.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$accent = get_theme_mod( 'wk_email_accent', '#6B1E3E' );
$bg     = get_theme_mod( 'wk_email_bg', '#fdfcfa' );
?>
body { background-color: #f4f0ea; margin: 0; padding: 0; }
#wrapper { background-color: #f4f0ea; }
#template_container { box-shadow: none; border: none; border-radius: 0; }
#template_header { background-color: #120F0C; border-radius: 0; color: #EDE5DA; border-bottom: 3px solid <?php echo esc_attr($accent); ?>; padding: 36px 48px; }
#template_header h1 { font-weight: 300; font-size: 26px; letter-spacing: 0.15em; text-transform: uppercase; color: #EDE5DA; font-family: Georgia, serif; }
#template_body { background-color: <?php echo esc_attr($bg); ?>; }
#body_content { background-color: <?php echo esc_attr($bg); ?>; padding: 40px 48px; }
#body_content table td { padding: 0; }
#body_content p, #body_content ul, #body_content ol { color: #3E3028; font-size: 14px; line-height: 1.7; margin: 0 0 16px; }
#body_content a { color: <?php echo esc_attr($accent); ?>; }
h2 { color: #120F0C; font-weight: 400; font-size: 20px; letter-spacing: 0.04em; }
.woocommerce-order-details th { background-color: <?php echo esc_attr($accent); ?>; color: #fff; }
.button.pay { background-color: <?php echo esc_attr($accent); ?> !important; }
#template_footer { background-color: #120F0C; color: rgba(237,229,218,.5); padding: 24px 48px; }
#template_footer p { color: rgba(237,229,218,.5); font-size: 12px; }
#template_footer a { color: rgba(237,229,218,.6); }
