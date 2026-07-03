<?php
/**
 * WhiteKurti — My Account Dashboard v2 (stub — content rendered by hooks)
 * @version 9.3.0
 */
defined('ABSPATH') || exit;
$user = wp_get_current_user();
// The main content is injected by wk_account_dashboard_content() via the hook below
do_action('woocommerce_account_dashboard');
