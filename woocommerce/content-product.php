<?php
/**
 * WhiteKurti — content-product.php (product loop template)
 */
defined('ABSPATH') || exit;
global $product;
if ( ! $product->is_visible() ) return;
wk_product_card($product, ['layout' => 'editorial']);
