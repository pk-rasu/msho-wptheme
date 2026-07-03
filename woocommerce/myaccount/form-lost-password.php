<?php
/**
 * Lost password form — Custom WhiteKurti template
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_lost_password_form' );
?>

<div class="wk-auth-wrapper">
	<h2 class="wk-auth-title"><?php esc_html_e( 'Lost password', 'woocommerce' ); ?></h2>
	
	<p class="wk-auth-subtitle">
		<?php echo apply_filters( 'woocommerce_lost_password_message', esc_html__( 'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'woocommerce' ) ); ?>
	</p>

	<form method="post" class="woocommerce-ResetPassword lost_reset_password wk-auth-form-inner">

		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wk-auth-field">
			<label for="user_login"><?php esc_html_e( 'Username or email', 'woocommerce' ); ?></label>
			<input class="woocommerce-Input woocommerce-Input--text input-text wk-input" type="text" name="user_login" id="user_login" autocomplete="username" />
		</p>

		<div class="clear"></div>

		<?php do_action( 'woocommerce_lostpassword_form' ); ?>

		<p class="woocommerce-form-row form-row wk-auth-submit">
			<input type="hidden" name="wc_reset_password" value="true" />
			<button type="submit" class="woocommerce-Button button wk-btn wk-btn--full" value="<?php esc_attr_e( 'Reset password', 'woocommerce' ); ?>"><?php esc_html_e( 'Reset password', 'woocommerce' ); ?></button>
		</p>

		<?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>

	</form>
</div>

<?php
do_action( 'woocommerce_after_lost_password_form' );
