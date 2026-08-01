<?php
/**
 * Prayer History block render callback.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Block;

defined( 'ABSPATH' ) || exit;

use Intercessor\Database\Query\Prayer_Request_Query;
use Intercessor\Database\Query\Prayed_Count_Query;
use Intercessor\Database\Query\Requester_Query;

/**
 * Server-side render callback for the intercessor/prayer-history Gutenberg block.
 *
 * Renders a user-facing dashboard of the logged-in user's own prayer requests.
 * Guests see a combined login / register prompt and are redirected back after
 * authentication. Authenticated users can view, edit, and delete their requests.
 * Edits reset status to 'pending' for admin re-moderation.
 *
 * @since   1.1.0 Redesigned from a single-request timeline to the user history dashboard.
 * @package Intercessor
 */
final class Prayer_History_Block {

	public static function default_attributes(): array {
		return array();
	}

	public function render( array $attributes, string $content ): string {
		// ── Assets ────────────────────────────────────────────────────────
		if ( ! wp_script_is( 'intercessor-public', 'registered' ) ) {
			wp_register_script( 'intercessor-public', '', array(), INTERCESSOR_VERSION, true );
		}
		if ( ! wp_script_is( 'intercessor-prayer-history', 'registered' ) ) {
			wp_register_script(
				'intercessor-prayer-history',
				INTERCESSOR_URL . 'assets/js/public/prayer-history.js',
				array( 'intercessor-public' ),
				INTERCESSOR_VERSION,
				true
			);
		}
		wp_enqueue_style( 'intercessor-public' );
		wp_enqueue_script( 'intercessor-public' );

		// ── Guest: login + register prompt ────────────────────────────────
		if ( ! is_user_logged_in() ) {
			return $this->render_guest_prompt();
		}

		// ── JS config (once per page) ──────────────────────────────────────
		if ( ! defined( 'INTERCESSOR_HISTORY_CONFIG_PRINTED' ) ) {
			define( 'INTERCESSOR_HISTORY_CONFIG_PRINTED', true );

			$cfg = wp_json_encode( array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'intercessor_history' ),
				'updateAction' => 'intercessor_update_own_request',
				'deleteAction' => 'intercessor_delete_own_request',
				'i18n'         => array(
					'edit'          => esc_html__( 'Edit',     'intercessor' ),
					'cancelEdit'    => esc_html__( 'Cancel',   'intercessor' ),
					'pendingLabel'  => esc_html__( 'Pending review', 'intercessor' ),
					'updateSuccess' => esc_html__( 'Saved — your request will be reviewed shortly.', 'intercessor' ),
					'error'         => esc_html__( 'An error occurred. Please try again.', 'intercessor' ),
					'networkError'  => esc_html__( 'Network error. Please try again.', 'intercessor' ),
					'confirmDelete' => esc_html__( 'Are you sure you want to delete this prayer request? This cannot be undone.', 'intercessor' ),
					'deleteError'   => esc_html__( 'Could not delete the prayer request.', 'intercessor' ),
				),
			) );

			wp_add_inline_script( 'intercessor-public', 'window.intercessorHistory = ' . $cfg . ';', 'before' );
		}

		wp_enqueue_script( 'intercessor-prayer-history' );

		// ── Requester lookup ───────────────────────────────────────────────
		$requester_query = new Requester_Query();
		$requester       = $requester_query->find_by_wp_user( get_current_user_id() );

		if ( ! $requester ) {
			return '<div class="intercessor-user-history wp-block-intercessor-prayer-history">'
				. '<p class="intercessor-empty">'
				. esc_html__( "You haven't submitted any prayer requests yet.", 'intercessor' )
				. '</p></div>';
		}

		// ── Fetch all requests (all statuses, newest first) ────────────────
		$prayer_query = new Prayer_Request_Query();
		$items        = $prayer_query->get_items( array(
			'requester_id' => $requester->id,
			'orderby'      => 'date_created',
			'order'        => 'DESC',
			'number'       => 100,
		) );

		$countQuery = new Prayed_Count_Query();

		ob_start();
		require INTERCESSOR_DIR . 'templates/blocks/user-prayer-history.php';
		return ob_get_clean() ?: '';
	}

	/**
	 * Render the login + register prompt for guests.
	 *
	 * @since  1.1.0
	 */
	private function render_guest_prompt(): string {
		$current_url  = $this->current_url();
		$login_form   = wp_login_form( array(
			'echo'           => false,
			'redirect'       => $current_url,
			'form_id'        => 'intercessor-history-loginform',
			'label_username' => esc_html__( 'Username or Email Address', 'intercessor' ),
			'label_password' => esc_html__( 'Password', 'intercessor' ),
			'label_remember' => esc_html__( 'Remember Me', 'intercessor' ),
			'label_log_in'   => esc_html__( 'Log In', 'intercessor' ),
			'remember'       => true,
		) );

		$register_url = add_query_arg(
			'redirect_to',
			rawurlencode( $current_url ),
			wp_registration_url()
		);

		ob_start();
		?>
		<div class="intercessor-guest-prompt wp-block-intercessor-prayer-history">

			<div class="intercessor-guest-prompt__intro">
				<span class="intercessor-guest-prompt__icon" aria-hidden="true"></span>
				<h2 class="intercessor-guest-prompt__title">
					<?php esc_html_e( 'Your Prayer History', 'intercessor' ); ?>
				</h2>
				<p class="intercessor-guest-prompt__message">
					<?php esc_html_e( 'Log in to view, edit, or manage your prayer requests.', 'intercessor' ); ?>
				</p>
			</div>

			<div class="intercessor-guest-prompt__login">
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_login_form returns escaped WP core markup.
				echo $login_form; ?>
			</div>

			<?php if ( get_option( 'users_can_register' ) ) : ?>
				<div class="intercessor-guest-prompt__register">
					<p>
						<?php
						printf(
							/* translators: %s: link to registration page */
							wp_kses( esc_html__( "Don't have an account? <a href=\"%s\">Register here</a>.", 'intercessor' ), array( 'a' => array( 'href' => array() ) ) ),
							esc_url( $register_url )
						);
						?>
					</p>
				</div>
			<?php endif; ?>

		</div>
		<?php
		return ob_get_clean() ?: '';
	}

	/**
	 * Return the current frontend URL for login redirect.
	 */
	private function current_url(): string {
		$request_uri = isset( $_SERVER['REQUEST_URI'] )
			? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';
		return home_url( $request_uri );
	}
}
