<?php
/**
 * Login Form — WhiteKurti
 * @package WooCommerce\Templates
 * @version 9.3.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;
do_action( 'woocommerce_before_customer_login_form' );
?>
<div class="wk-auth-page" id="customer_login">
<div class="wk-auth-card">

<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
<div class="wk-auth-tabs">
	<button type="button" class="wk-auth-tab is-active" data-target="wk-login-form"><?php esc_html_e( 'Sign In', 'woocommerce' ); ?></button>
	<button type="button" class="wk-auth-tab" data-target="wk-register-form"><?php esc_html_e( 'Create Account', 'woocommerce' ); ?></button>
</div>

<!-- LOGIN -->
<div class="wk-auth-pane is-active" id="wk-login-form">
	<form class="woocommerce-form login wk-form" method="post">
		<?php do_action( 'woocommerce_login_form_start' ); ?>
		<div class="wk-field">
			<label class="wk-field__label" for="username"><?php esc_html_e( 'Username or Email Address', 'woocommerce' ); ?> <span class="required">*</span></label>
			<input type="text" class="wk-field__input woocommerce-Input input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
		</div>
		<div class="wk-field">
			<label class="wk-field__label" for="password"><?php esc_html_e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
			<div class="wk-field__password-wrap">
				<input class="wk-field__input woocommerce-Input input-text" type="password" name="password" id="password" autocomplete="current-password" />
				<button type="button" class="wk-pw-toggle" data-target="password" aria-pressed="false" aria-label="Show or hide password">
					<svg class="wk-eye wk-eye--open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
					<svg class="wk-eye wk-eye--closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
				</button>
			</div>
		</div>
		<?php do_action( 'woocommerce_login_form' ); ?>
		<div class="wk-auth-meta">
			<label class="wk-auth-remember"><input type="checkbox" name="rememberme" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span></label>
			<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="wk-auth-forgot"><?php esc_html_e( 'Forgot password?', 'woocommerce' ); ?></a>
		</div>
		<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
		<button type="submit" class="wk-btn wk-btn--full" name="login" value="Log in"><?php esc_html_e( 'Log In', 'woocommerce' ); ?></button>
		<?php do_action( 'woocommerce_login_form_end' ); ?>
	</form>
</div>

<!-- REGISTER -->
<div class="wk-auth-pane" id="wk-register-form">
	<form method="post" class="woocommerce-form register wk-form" <?php do_action( 'woocommerce_register_form_tag' ); ?>>
		<?php do_action( 'woocommerce_register_form_start' ); ?>
		<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
		<div class="wk-field">
			<label class="wk-field__label" for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?> <span class="required">*</span></label>
			<input type="text" class="wk-field__input woocommerce-Input input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
		</div>
		<?php endif; ?>
		<div class="wk-field">
			<label class="wk-field__label" for="reg_email"><?php esc_html_e( 'Email Address', 'woocommerce' ); ?> <span class="required">*</span></label>
			<input type="email" class="wk-field__input woocommerce-Input input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" />
		</div>
		<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
		<div class="wk-field">
			<label class="wk-field__label" for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
			<div class="wk-field__password-wrap">
				<input type="password" class="wk-field__input woocommerce-Input input-text" name="password" id="reg_password" autocomplete="new-password" />
				<button type="button" class="wk-pw-toggle" data-target="reg_password" aria-pressed="false" aria-label="Show or hide password">
					<svg class="wk-eye wk-eye--open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
					<svg class="wk-eye wk-eye--closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
				</button>
			</div>
		</div>
		<?php else : ?>
		<p class="wk-auth-note"><?php esc_html_e( 'A password will be sent to your email address.', 'woocommerce' ); ?></p>
		<?php endif; ?>
		<?php do_action( 'woocommerce_register_form' ); ?>
		<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
		<button type="submit" class="wk-btn wk-btn--full" name="register" value="Register"><?php esc_html_e( 'Create My Account', 'woocommerce' ); ?></button>
		<?php do_action( 'woocommerce_register_form_end' ); ?>
	</form>
</div>

<?php else : ?>
<!-- Login only -->
<h1 class="wk-auth-title"><?php esc_html_e( 'Sign In', 'woocommerce' ); ?></h1>
<form class="woocommerce-form login wk-form" method="post">
	<?php do_action( 'woocommerce_login_form_start' ); ?>
	<div class="wk-field">
		<label class="wk-field__label" for="username"><?php esc_html_e( 'Username or Email', 'woocommerce' ); ?> <span class="required">*</span></label>
		<input type="text" class="wk-field__input woocommerce-Input input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
	</div>
	<div class="wk-field">
		<label class="wk-field__label" for="password"><?php esc_html_e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
		<div class="wk-field__password-wrap">
			<input class="wk-field__input woocommerce-Input input-text" type="password" name="password" id="password" autocomplete="current-password" />
			<button type="button" class="wk-pw-toggle" data-target="password" aria-pressed="false" aria-label="Show or hide password">
				<svg class="wk-eye wk-eye--open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
				<svg class="wk-eye wk-eye--closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
			</button>
		</div>
	</div>
	<?php do_action( 'woocommerce_login_form' ); ?>
	<div class="wk-auth-meta">
		<label class="wk-auth-remember"><input type="checkbox" name="rememberme" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span></label>
		<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="wk-auth-forgot"><?php esc_html_e( 'Forgot password?', 'woocommerce' ); ?></a>
	</div>
	<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
	<button type="submit" class="wk-btn wk-btn--full" name="login" value="Log in"><?php esc_html_e( 'Log In', 'woocommerce' ); ?></button>
	<?php do_action( 'woocommerce_login_form_end' ); ?>
</form>
<?php endif; ?>

</div><!-- /.wk-auth-card -->
</div><!-- /#customer_login -->
<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
