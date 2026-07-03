<?php
/**
 * WhiteKurti — checkout/form-checkout.php
 * Customised checkout layout keeping all WooCommerce functionality.
 */
defined('ABSPATH') || exit;
if ( ! is_checkout() ) return;
?>
<?php get_header('shop'); ?>

<main id="wk-main" class="wk-woo-main">
<div class="wk-container">

  <?php woocommerce_breadcrumb(); ?>

  <h1 class="wk-page-title wk-checkout-title"><?php esc_html_e('Checkout','whitekurti'); ?></h1>

  <?php wc_print_notices(); ?>

  <?php do_action('woocommerce_before_checkout_form', WC()->checkout()); ?>

  <form name="checkout" method="post" class="checkout woocommerce-checkout"
        action="<?php echo esc_url( wc_get_checkout_url() ); ?>"
        enctype="multipart/form-data">

    <div class="wk-checkout-grid">

      <!-- ── Customer fields ── -->
      <div class="wk-checkout-fields">

        <?php if ( WC()->cart->needs_shipping_address() ) : ?>
        <h2 class="wk-checkout-heading"><?php esc_html_e('Delivery Details','whitekurti'); ?></h2>
        <?php endif; ?>

        <?php do_action('woocommerce_checkout_billing'); ?>
        <?php do_action('woocommerce_checkout_shipping'); ?>

        <!-- Order notes -->
        <div class="woocommerce-additional-fields">
          <?php do_action('woocommerce_before_order_notes', WC()->checkout()); ?>
          <?php if ( apply_filters('woocommerce_enable_order_notes_field', 'yes' === get_option('woocommerce_enable_order_comments')) ) : ?>
          <div class="woocommerce-additional-fields__field-wrapper">
            <?php foreach ( WC()->checkout()->get_checkout_fields('order') as $key => $field ) :
              woocommerce_form_field( $key, $field, WC()->checkout()->get_value($key) );
            endforeach; ?>
          </div>
          <?php endif; ?>
          <?php do_action('woocommerce_after_order_notes', WC()->checkout()); ?>
        </div>

      </div><!-- /.wk-checkout-fields -->

      <!-- ── Order summary + payment ── -->
      <div class="wk-checkout-summary">

        <div class="wk-checkout-summary__head">
          <h2 class="wk-checkout-heading"><?php esc_html_e('Your Order','whitekurti'); ?></h2>
        </div>

        <?php do_action('woocommerce_checkout_before_order_review_heading'); ?>
        <?php do_action('woocommerce_checkout_order_review'); ?>

      </div>

    </div><!-- /.wk-checkout-grid -->

    <?php do_action('woocommerce_after_checkout_form', WC()->checkout()); ?>

  </form>

</div><!-- /.wk-container -->
</main>

<?php get_footer('shop');
