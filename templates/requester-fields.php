<?php
/**
 * Intercessor Prayer Submit Form Requester Fields.
 *
 * This template is used to display the requester fields.
 *
 * @package   Intercessor
 * @copyright Copyright (c) 2019, Victor Aigbeghian
 * @license   http://opensource.org/licenses/gpl-3.0 GNU Public License
 * @since     0.9.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div id="intercessor_requester_fields" class="intercessor-row">
	<div class="col form-col4">
		<label class="intercessor-label" for="intercessor-first">
			<?php esc_html_e( 'First Name', 'intercessor' ); ?>
			<?php if ( intercessor_required_fields( 'intercessor_first' ) ) { ?>
				<span class="intercessor-required-indicator">*</span>
			<?php } ?>
		</label>
		<input class="intercessor-input required" type="text" name="intercessor_first" placeholder="<?php esc_html_e( 'First Name', 'intercessor' ); ?>" id="intercessor-first" value="<?php echo esc_attr( $requester['first_name'] ); ?>"<?php if ( intercessor_required_fields( 'intercessor_first' ) ) {  echo ' required '; } ?> aria-describedby="intercessor-first-description" />
	</div>
	<div class="col form-col4">
		<label class="intercessor-label" for="intercessor-last">
			<?php esc_html_e( 'Last Name', 'intercessor' ); ?>
			<?php if ( intercessor_required_fields( 'intercessor_last' ) ) { ?>
				<span class="intercessor-required-indicator">*</span>
			<?php } ?>
		</label>
		<input class="intercessor-input<?php if ( intercessor_required_fields( 'intercessor_last' ) ) { echo ' required'; } ?>" type="text" name="intercessor_last" id="intercessor-last" placeholder="<?php esc_html_e( 'Last Name', 'intercessor' ); ?>" value="<?php echo esc_attr( $requester['last_name'] ); ?>"<?php if ( intercessor_required_fields( 'intercessor_last' ) ) {  echo ' required '; } ?> aria-describedby="intercessor-last-description"/>
	</div>
	
	<?php do_action( 'intercessor_request_form_before_email' ); ?>
	<div class="col form-col4">
		<label class="intercessor-label" for="intercessor-email">
			<?php esc_html_e( 'Email Address', 'intercessor' ); ?>
			<?php if ( intercessor_required_fields( 'intercessor_email' ) ) { ?>
				<span class="intercessor-required-indicator">*</span>
			<?php } ?>
		</label>
		<input class="intercessor-input required" type="email" name="intercessor_email" placeholder="<?php esc_html_e( 'Email address', 'intercessor' ); ?>" id="intercessor-email" value="<?php echo esc_attr( $requester['email'] ); ?>" aria-describedby="intercessor-email-description"<?php if ( intercessor_required_fields( 'intercessor_email' ) ) {  echo ' required '; } ?>/>
	</div>
	
	<?php do_action( 'intercessor_request_form_after_email' ); ?>	
	
	<?php if ( is_user_logged_in() ) : ?>
	<div class="col form-column">
		<?php
			$user = wp_get_current_user();
			printf( __( 'You are currently signed in as <strong>%s</strong>.', 'intercessor' ), $user->user_login );
		?>
		<a class="intercessor-submit" href="<?php echo apply_filters( 'intercessor_submit_form_logout_url', wp_logout_url( get_permalink() ) ); ?>"><?php _e( 'Sign out', 'intercessor' ); ?></a>
	</div>
	<?php endif; ?>
	
	<?php do_action( 'intercessor_request_form_user_info' ); ?>
	<?php do_action( 'intercessor_request_form_user_info_fields' ); ?>
</div>
