<?php
/**
 * Front-end template: Prayer Form block.
 *
 * Variables provided by Prayer_Form_Block::render():
 *
 * @var bool   $show_anon           Whether to show the anonymous checkbox.
 * @var string $submitLabel         Localised submit button label.
 * @var bool   $recaptchaEnabled    Whether reCAPTCHA is active for this form.
 * @var string $recaptchaWidgetHtml HTML for the v2 checkbox widget (may be empty).
 * @var string $recaptchaTokenInput HTML hidden input for v3 token (may be empty).
 *
 * @package Intercessor
 * @since   1.0.0
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-scoped variables included via require, not true globals

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\Admin\Settings;

// ── Account confirmation status notices ──────────────────────────────────────
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$account_status = isset( $_GET['intercessor_account'] ) ? sanitize_key( $_GET['intercessor_account'] ) : '';

// Pre-fill name and email for logged-in users.
$current_user  = wp_get_current_user();
$is_logged_in  = is_user_logged_in();
$first_name_value = $is_logged_in ? $current_user->first_name : '';
$last_name_value  = $is_logged_in ? $current_user->last_name  : '';
$email_value      = $is_logged_in ? $current_user->user_email  : '';

// Terms and privacy settings — correct keys matching Display_Page::get_schema().
$show_terms   = (bool) Settings::get( 'show_site_terms', false );
$terms_label  = (string) Settings::get( 'terms_label', '' );
$terms_url    = (string) Settings::get( 'terms_url', '' );
$show_privacy = (bool) Settings::get( 'show_privacy_policy', false );
$privacy_label = (string) Settings::get( 'privacy_label', '' );
$privacy_url  = (string) Settings::get( 'privacy_url', '' );
$show_private_option = (bool) Settings::get( 'allow_private_requests', false );
?>
<div class="intercessor-prayer-form wp-block-intercessor-prayer-form" data-intercessor-form>

	<?php if ( $account_status === 'confirmed' ) : ?>
		<div class="intercessor-alert intercessor-alert--success">
			<?php esc_html_e( 'Your email has been confirmed. Your account is now active.', 'intercessor' ); ?>
		</div>
	<?php elseif ( $account_status === 'expired' ) : ?>
		<div class="intercessor-alert intercessor-alert--error">
			<?php esc_html_e( 'This confirmation link has expired. Please submit a new prayer request and create your account again.', 'intercessor' ); ?>
		</div>
	<?php elseif ( $account_status === 'invalid' ) : ?>
		<div class="intercessor-alert intercessor-alert--error">
			<?php esc_html_e( 'This confirmation link is invalid. Please check your email or try again.', 'intercessor' ); ?>
		</div>
	<?php endif; ?>

	<div class="intercessor-form-messages" aria-live="polite"></div>

	<form class="intercessor-form" novalidate>

		<div class="intercessor-field-row">
			<div class="intercessor-field">
				<label for="intercessor-first-name">
					<?php esc_html_e( 'First Name', 'intercessor' ); ?>
					<span aria-hidden="true">*</span>
				</label>
				<input type="text" id="intercessor-first-name" name="first_name"
					   required autocomplete="given-name" maxlength="100"
					   value="<?php echo esc_attr( $first_name_value ); ?>"
					   <?php echo $is_logged_in ? 'readonly' : ''; ?>>
			</div>

			<div class="intercessor-field">
				<label for="intercessor-last-name">
					<?php esc_html_e( 'Last Name', 'intercessor' ); ?>
				</label>
				<input type="text" id="intercessor-last-name" name="last_name"
					   autocomplete="family-name" maxlength="100"
					   value="<?php echo esc_attr( $last_name_value ); ?>"
					   <?php echo $is_logged_in ? 'readonly' : ''; ?>>
			</div>
		</div>

		<div class="intercessor-field">
			<label for="intercessor-email">
				<?php esc_html_e( 'Email Address', 'intercessor' ); ?>
				<span aria-hidden="true">*</span>
			</label>
			<input type="email" id="intercessor-email" name="requester_email"
				   required autocomplete="email" maxlength="255"
				   value="<?php echo esc_attr( $email_value ); ?>"
				   <?php echo $is_logged_in ? 'readonly' : ''; ?>>
		</div>

		<div class="intercessor-field">
			<label for="intercessor-subject">
				<?php esc_html_e( 'Subject', 'intercessor' ); ?>
				<span aria-hidden="true">*</span>
			</label>
			<input type="text" id="intercessor-subject" name="subject"
				   required maxlength="255">
		</div>

		<div class="intercessor-field">
			<label for="intercessor-content">
				<?php esc_html_e( 'Prayer Request', 'intercessor' ); ?>
				<span aria-hidden="true">*</span>
			</label>
			<textarea id="intercessor-content" name="content" rows="6" required></textarea>
		</div>

		<?php if ( $show_anon ) : ?>
		<div class="intercessor-field intercessor-field--checkbox">
			<label>
				<input type="checkbox" name="is_anonymous" value="1">
				<?php esc_html_e( 'Keep my name anonymous on the public list', 'intercessor' ); ?>
			</label>
		</div>
		<?php endif; ?>

		<?php if ( $show_private_option ) : ?>
		<div class="intercessor-field intercessor-field--checkbox intercessor-field--private">
			<label>
				<input type="checkbox" name="is_private" value="1" id="intercessor-is-private">
				<?php esc_html_e( 'Keep my prayer request private', 'intercessor' ); ?>
			</label>
			<p class="intercessor-field-hint">
				<?php esc_html_e( 'Private requests are seen only by our prayer team and will not appear on the public Prayer Wall.', 'intercessor' ); ?>
			</p>
		</div>
		<?php endif; ?>

		<?php
		/**
		 * "Create an account?" section — only for guests when enable_registration
		 * is on. The visibility of username/password fields is toggled by JS
		 * reading window.intercessorForm.generateUsername / generatePassword.
		 * The fieldset is always rendered when enableRegistration is true so
		 * it works without JS (fields shown by default, hidden progressively).
		 */
		$enable_registration = ! $is_logged_in && (bool) \Intercessor\Admin\Settings::get( 'enable_registration', false );
		$generate_username   = (bool) \Intercessor\Admin\Settings::get( 'generate_username', false );
		$generate_password   = (bool) \Intercessor\Admin\Settings::get( 'generate_password', false );
		$need_username       = ! $generate_username;
		$need_password       = ! $generate_password;
		?>

		<?php if ( $enable_registration ) : ?>
		<div class="intercessor-field intercessor-field--checkbox intercessor-field--register-toggle">
			<label>
				<input type="checkbox" name="create_account" value="1"
				       id="intercessor-create-account"
				       aria-controls="intercessor-register-fields"
				       aria-expanded="false">
				<?php esc_html_e( 'Create an account?', 'intercessor' ); ?>
			</label>
		</div>

		<div id="intercessor-register-fields"
		     class="intercessor-register-fields"
		     aria-hidden="true"
		     hidden>

			<?php if ( $need_username ) : ?>
			<div class="intercessor-field">
				<label for="intercessor-username">
					<?php esc_html_e( 'Username', 'intercessor' ); ?>
					<span aria-hidden="true">*</span>
				</label>
				<input type="text" id="intercessor-username" name="username"
				       autocomplete="username" maxlength="60"
				       placeholder="<?php esc_attr_e( 'Choose a username', 'intercessor' ); ?>">
			</div>
			<?php endif; ?>

			<?php if ( $need_password ) : ?>
			<div class="intercessor-field-row">
				<div class="intercessor-field">
					<label for="intercessor-password">
						<?php esc_html_e( 'Password', 'intercessor' ); ?>
						<span aria-hidden="true">*</span>
					</label>
					<input type="password" id="intercessor-password"
					       name="account_password"
					       autocomplete="new-password"
					       minlength="8"
					       placeholder="<?php esc_attr_e( 'Min. 8 characters', 'intercessor' ); ?>">
				</div>
				<div class="intercessor-field">
					<label for="intercessor-password-confirm">
						<?php esc_html_e( 'Confirm Password', 'intercessor' ); ?>
						<span aria-hidden="true">*</span>
					</label>
					<input type="password" id="intercessor-password-confirm"
					       name="account_password_confirm"
					       autocomplete="new-password"
					       minlength="8"
					       placeholder="<?php esc_attr_e( 'Repeat password', 'intercessor' ); ?>">
				</div>
			</div>
			<?php endif; ?>

			<p class="intercessor-register-hint">
				<span class="dashicons dashicons-info" aria-hidden="true"></span>
				<?php esc_html_e( 'After submitting, you will receive a confirmation email to activate your account.', 'intercessor' ); ?>
			</p>

		</div><!-- #intercessor-register-fields -->
		<?php endif; ?>

		<?php if ( $show_terms && ! empty( $terms_url ) ) : ?>
		<div class="intercessor-field intercessor-field--checkbox">
			<label>
				<input type="checkbox" name="accept_terms" value="1" required>
				<?php echo wp_kses_post( $terms_label ?: __( 'I agree to the Terms of Service.', 'intercessor' ) ); ?>
				<a href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Read Terms', 'intercessor' ); ?>
				</a>
			</label>
		</div>
		<?php elseif ( $show_terms ) : ?>
		<div class="intercessor-field intercessor-field--checkbox">
			<label>
				<input type="checkbox" name="accept_terms" value="1" required>
				<?php echo wp_kses_post( $terms_label ?: __( 'I agree to the Terms of Service.', 'intercessor' ) ); ?>
			</label>
		</div>
		<?php endif; ?>

		<?php if ( $show_privacy && ! empty( $privacy_url ) ) : ?>
		<div class="intercessor-field intercessor-field--checkbox">
			<label>
				<input type="checkbox" name="accept_privacy" value="1" required>
				<?php
				printf(
					/* translators: %1$s: URL to the privacy policy page. */
					wp_kses_post( __( 'I have read and accept the <a href="%1$s" target="_blank" rel="noopener noreferrer">Privacy Policy</a>.', 'intercessor' ) ),
					esc_url( $privacy_url )
				);
				?>
			</label>
		</div>
		<?php elseif ( $show_privacy ) : ?>
		<div class="intercessor-field intercessor-field--checkbox">
			<label>
				<input type="checkbox" name="accept_privacy" value="1" required>
				<?php echo wp_kses_post( $privacy_label ?: __( 'I have read and accept the Privacy Policy.', 'intercessor' ) ); ?>
			</label>
		</div>
		<?php endif; ?>

		<?php
		// reCAPTCHA — only shown to guests; logged-in users are already verified.
		// Recaptcha::widget_html() / token_input_html() produce plugin-controlled
		// HTML with all dynamic values escaped — safe to output directly.
		if ( ! $is_logged_in ) :
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $recaptchaWidgetHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped HTML returned by Recaptcha::widget_html()
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $recaptchaTokenInput; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped HTML returned by Recaptcha::token_input_html()
		endif;
		?>

		<?php wp_nonce_field( 'intercessor_submit_request', 'nonce' ); ?>
		<input type="hidden" name="source_url" value="<?php echo esc_url( home_url( add_query_arg( array() ) ) ); ?>">

		<div class="intercessor-field">
			<button type="submit" class="intercessor-submit wp-element-button">
				<?php echo esc_html( $submitLabel ); ?>
			</button>
		</div>

	</form>

</div>
