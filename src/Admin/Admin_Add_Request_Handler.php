<?php
/**
 * Handler for admin-initiated prayer request creation.
 *
 * Hooked to admin_post_intercessor_admin_add_request. Bypasses the public
 * Submission_Pipeline (no rate-limit, profanity filter, or duplicate check)
 * because administrators are trusted actors who may add requests on behalf
 * of others and need to set the initial status directly.
 *
 * @package Intercessor
 * @since   1.0.2
 */

declare(strict_types=1);

namespace Intercessor\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\Database\Query\Prayer_Request_Query;
use Intercessor\Database\Query\Requester_Query;

/**
 * Processes the Add Prayer Request modal form submitted from the admin list page.
 *
 * @since 1.0.2
 */
final class Admin_Add_Request_Handler {

	/** @var string Nonce action for this form. */
	public const NONCE_ACTION = 'intercessor_admin_add_request';

	/** @var string admin_post_{action} hook suffix. */
	public const ACTION = 'intercessor_admin_add_request';

	/**
	 * Valid status values an admin may assign directly.
	 *
	 * @since 1.0.2
	 * @var string[]
	 */
	private const ALLOWED_STATUSES = array( 'pending', 'approved', 'private' );

	/**
	 * Handle the admin_post hook.
	 *
	 * Verifies nonce and capability, sanitizes input, finds or creates a
	 * requester record, inserts the prayer request, then redirects back to
	 * the requests list with a success or error query arg.
	 *
	 * @since  1.0.2
	 * @return void
	 */
	public static function handle(): void {
		$list_url = admin_url( 'admin.php?page=intercessor-requests' );

		// ── 1. Nonce ───────────────────────────────────────────────────────
		if ( ! isset( $_POST['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), self::NONCE_ACTION ) ) {
			wp_safe_redirect( add_query_arg( 'error', '1', $list_url ) );
			exit;
		}

		// ── 2. Capability ──────────────────────────────────────────────────
		if ( ! current_user_can( 'edit_prayers' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to add prayer requests.', 'intercessor' ),
				403
			);
		}

		// ── 3. Sanitize inputs ─────────────────────────────────────────────
		$for_type    = isset( $_POST['for_type'] ) && 'other' === $_POST['for_type'] ? 'other' : 'self';
		$subject     = sanitize_text_field( wp_unslash( $_POST['subject']  ?? '' ) );
		$content     = sanitize_textarea_field( wp_unslash( $_POST['content'] ?? '' ) );
		$status      = sanitize_key( $_POST['status'] ?? 'pending' );
		$is_private  = ( 'private' === $status );
		$is_anonymous = ! empty( $_POST['is_anonymous'] );

		if ( 'self' === $for_type ) {
			$current   = wp_get_current_user();
			$email     = $current->user_email;
			$first_name = $current->first_name ?: $current->display_name;
			$last_name  = $current->last_name;
		} else {
			$email      = sanitize_email( wp_unslash( $_POST['email']      ?? '' ) );
			$first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
			$last_name  = sanitize_text_field( wp_unslash( $_POST['last_name']  ?? '' ) );
		}

		// ── 4. Validate ────────────────────────────────────────────────────
		if ( '' === $subject || '' === $content ) {
			wp_safe_redirect( add_query_arg( 'error', '1', $list_url ) );
			exit;
		}

		if ( ! is_email( $email ) ) {
			wp_safe_redirect( add_query_arg( 'error', '1', $list_url ) );
			exit;
		}

		if ( ! in_array( $status, self::ALLOWED_STATUSES, true ) ) {
			$status = 'pending';
		}

		// ── 5. Find or create requester ────────────────────────────────────
		$requester_query = new Requester_Query();

		if ( 'self' === $for_type ) {
			// Adding for themselves — use the standard pipeline method which
			// correctly links the current WP user to the requester record.
			$requester_id = $requester_query->find_or_create( $email, $first_name, $last_name );
		} else {
			// Adding for someone else — find existing requester by email or
			// create a new one. Never link the current admin's WP user ID;
			// instead check whether the email belongs to a registered WP user
			// and use their ID, falling back to 0 for non-users.
			$existing = $requester_query->get_items(
				array(
					'email'  => $email,
					'number' => 1,
				)
			);

			if ( ! empty( $existing ) ) {
				$requester_id = $existing[0]->id;
			} else {
				$wp_user      = get_user_by( 'email', $email );
				$full_name    = trim( $first_name . ' ' . $last_name );
				$requester_id = $requester_query->add_item(
					array(
						'email'      => $email,
						'first_name' => $first_name,
						'last_name'  => $last_name,
						'name'       => $full_name ?: $email,
						'wp_user_id' => $wp_user ? $wp_user->ID : 0,
					)
				);
			}
		}

		if ( ! $requester_id ) {
			wp_safe_redirect( add_query_arg( 'error', '1', $list_url ) );
			exit;
		}

		// ── 6. Insert prayer request ───────────────────────────────────────
		$prayer_query = new Prayer_Request_Query();
		$new_id       = $prayer_query->add_item(
			array(
				'requester_id' => $requester_id,
				'subject'      => $subject,
				'content'      => $content,
				'status'       => $status,
				'is_anonymous' => $is_anonymous ? 1 : 0,
				'is_public'    => $is_private ? 0 : 1,
			)
		);

		if ( ! $new_id ) {
			wp_safe_redirect( add_query_arg( 'error', '1', $list_url ) );
			exit;
		}

		// ── 7. Redirect with success notice ───────────────────────────────
		wp_safe_redirect( add_query_arg( 'added', '1', $list_url ) );
		exit;
	}
}
