<?php
/**
 * Edit account form — WhiteKurti
 * @package WooCommerce\Templates
 * @version 9.3.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;
do_action( 'woocommerce_before_edit_account_form' );

$eye_open  = '<svg class="wk-eye wk-eye--open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
$eye_closed= '<svg class="wk-eye wk-eye--closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
?>

<form class="woocommerce-EditAccountForm edit-account wk-account-form" action="" method="post" <?php do_action( 'woocommerce_edit_account_form_tag' ); ?>>
	<?php do_action( 'woocommerce_edit_account_form_start' ); ?>

	<div class="wk-account-section">
		<h3 class="wk-account-section__title">Personal Information</h3>

		<div class="wk-form-row-2col">
			<div class="wk-field">
				<label class="wk-field__label" for="account_first_name">
					<?php esc_html_e( 'First Name', 'woocommerce' ); ?> <span class="required">*</span>
				</label>
				<input type="text" class="wk-field__input woocommerce-Input input-text" name="account_first_name" id="account_first_name"
					autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>" />
			</div>
			<div class="wk-field">
				<label class="wk-field__label" for="account_last_name">
					<?php esc_html_e( 'Last Name', 'woocommerce' ); ?> <span class="required">*</span>
				</label>
				<input type="text" class="wk-field__input woocommerce-Input input-text" name="account_last_name" id="account_last_name"
					autocomplete="family-name" value="<?php echo esc_attr( $user->last_name ); ?>" />
			</div>
		</div>

		<div class="wk-field">
			<label class="wk-field__label" for="account_display_name">
				<?php esc_html_e( 'Display Name', 'woocommerce' ); ?> <span class="required">*</span>
			</label>
			<input type="text" class="wk-field__input woocommerce-Input input-text" name="account_display_name" id="account_display_name"
				value="<?php echo esc_attr( $user->display_name ); ?>" />
			<p class="wk-field-hint"><?php esc_html_e( 'This will be how your name will be displayed in the account section and in reviews', 'woocommerce' ); ?></p>
		</div>

		<div class="wk-field">
			<label class="wk-field__label" for="account_email">
				<?php esc_html_e( 'Email Address', 'woocommerce' ); ?> <span class="required">*</span>
			</label>
			<input type="email" class="wk-field__input woocommerce-Input input-text" name="account_email" id="account_email"
				autocomplete="email" value="<?php echo esc_attr( $user->user_email ); ?>" />
		</div>
	</div>

	<?php do_action( 'woocommerce_edit_account_form' ); ?>

	<div class="wk-account-section">
		<h3 class="wk-account-section__title">Change Password</h3>
		<p style="font-size:13px;color:var(--ink-mute);margin:0 0 20px;">Leave blank to keep your current password.</p>

		<div class="wk-field">
			<label class="wk-field__label" for="password_current">
				<?php esc_html_e( 'Current Password', 'woocommerce' ); ?>
			</label>
			<div class="wk-field__password-wrap">
				<input type="password" class="wk-field__input woocommerce-Input input-text" name="password_current"
					id="password_current" autocomplete="off" />
				<button type="button" class="wk-pw-toggle" data-target="password_current" aria-pressed="false" aria-label="Show or hide password">
					<?php echo $eye_open . $eye_closed; ?>
				</button>
			</div>
		</div>

		<div class="wk-field">
			<label class="wk-field__label" for="password_1">
				<?php esc_html_e( 'New Password', 'woocommerce' ); ?>
			</label>
			<div class="wk-field__password-wrap">
				<input type="password" class="wk-field__input woocommerce-Input input-text" name="password_1"
					id="password_1" autocomplete="new-password" />
				<button type="button" class="wk-pw-toggle" data-target="password_1" aria-pressed="false" aria-label="Show or hide password">
					<?php echo $eye_open . $eye_closed; ?>
				</button>
			</div>
		</div>

		<div class="wk-field">
			<label class="wk-field__label" for="password_2">
				<?php esc_html_e( 'Confirm New Password', 'woocommerce' ); ?>
			</label>
			<div class="wk-field__password-wrap">
				<input type="password" class="wk-field__input woocommerce-Input input-text" name="password_2"
					id="password_2" autocomplete="new-password" />
				<button type="button" class="wk-pw-toggle" data-target="password_2" aria-pressed="false" aria-label="Show or hide password">
					<?php echo $eye_open . $eye_closed; ?>
				</button>
			</div>
		</div>
	</div>

	<?php do_action( 'woocommerce_edit_account_form_end' ); ?>

	<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
	<input type="hidden" name="action" value="save_account_details" />
	<button type="submit" class="wk-btn" name="save_account_details" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>">
		<?php esc_html_e( 'Save Changes', 'woocommerce' ); ?>
	</button>
</form>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
