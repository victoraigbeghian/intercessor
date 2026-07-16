<?php
/**
 * Shared prayer-request submission pipeline.
 *
 * @package Intercessor
 * @since   1.0.1
 */
declare(strict_types=1);

namespace Intercessor\Http;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\Admin\Settings;
use Intercessor\Database\Query\Prayer_Request_Query;
use Intercessor\Database\Query\Requester_Query;
use Intercessor\Util\Notifier;
use Intercessor\Util\Profanity_Filter;
use Intercessor\Util\Rate_Limiter;
use WP_Error;

/**
 * Encapsulates the shared prayer-request submission pipeline.
 *
 * Both the AJAX form handler (Public_Loader) and the REST endpoint
 * (Rest_Api) go through this single method so that every submission —
 * regardless of entry point — is subject to the same guards and
 * side-effects:
 *
 *   1. Rate limit check (per email, per day).
 *   2. Profanity filter — forces pending status and writes a moderator
 *      note when triggered; does NOT block the submission.
 *   3. Requester find-or-create + prayer_request INSERT.
 *   4. Admin and requester email notifications.
 *
 * Caller-specific concerns (nonce, reCAPTCHA, WP account registration)
 * are deliberately excluded from this class; each caller handles them
 * before invoking submit().
 *
 * @since   1.0.1
 * @package Intercessor
 */
final class Submission_Service {

	/**
	 * Run the full submission pipeline and persist the prayer request.
	 *
	 * @since  1.0.1
	 * @param  string $email       Sanitized submitter email address.
	 * @param  string $first_name  Sanitized first name.
	 * @param  string $last_name   Sanitized last name (may be empty).
	 * @param  string $subject     Sanitized subject line.
	 * @param  string $content     Sanitized prayer request body.
	 * @param  bool   $is_anonymous Whether to hide the requester's identity publicly.
	 * @param  bool   $is_private   Whether the request should be kept private (not shown on the Prayer Wall).
	 * @return int|WP_Error        New prayer request ID on success, or a WP_Error
	 *                             whose 'status' data key carries the HTTP status code.
	 */
	public static function submit(
		string $email,
		string $first_name,
		string $last_name,
		string $subject,
		string $content,
		bool $is_anonymous,
		bool $is_private = false
	): int|WP_Error {

		// ── 1. Rate limit ─────────────────────────────────────────────────────
		if ( ! Rate_Limiter::is_allowed( $email ) ) {
			$limit = Rate_Limiter::get_limit();
			return new WP_Error(
				'rate_limit_exceeded',
				sprintf(
					/* translators: %d: daily submission limit number */
					_n(
						'You may only submit %d prayer request per day. Please try again tomorrow.',
						'You may only submit %d prayer requests per day. Please try again tomorrow.',
						$limit,
						'intercessor'
					),
					$limit
				),
				array( 'status' => 429 )
			);
		}

		// ── 2. Profanity filter ───────────────────────────────────────────────
		$auto_approve   = (bool) Settings::get( 'auto_approve', false );
		$initial_status = $auto_approve ? 'approved' : 'pending';
		$moderator_note = '';

		if ( Profanity_Filter::is_enabled() ) {
			$matched = array_unique( array_merge(
				Profanity_Filter::get_matched_words( $subject ),
				Profanity_Filter::get_matched_words( $content )
			) );

			if ( ! empty( $matched ) ) {
				$initial_status = 'pending';
				$moderator_note = Profanity_Filter::build_moderator_note( $matched );
			}
		}

		// ── 3. Persist ────────────────────────────────────────────────────────
		$requester_query = new Requester_Query();
		$requester_id    = $requester_query->find_or_create( $email, $first_name, $last_name );

		if ( ! $requester_id ) {
			return new WP_Error(
				'requester_create_failed',
				__( 'Could not save requester information.', 'intercessor' ),
				array( 'status' => 500 )
			);
		}

		$prayer_query = new Prayer_Request_Query();
		$new_id       = $prayer_query->add_item( array(
			'requester_id'   => $requester_id,
			'subject'        => $subject,
			'content'        => $content,
			'status'         => $is_private ? 'private' : $initial_status,
			'is_anonymous'   => $is_anonymous ? 1 : 0,
			'is_public'      => $is_private ? 0 : 1,
			'moderator_note' => $moderator_note,
		) );

		if ( ! $new_id ) {
			return new WP_Error(
				'prayer_insert_failed',
				__( 'Could not save your prayer request.', 'intercessor' ),
				array( 'status' => 500 )
			);
		}

		// ── 4. Notifications ──────────────────────────────────────────────────
		Notifier::notify_admin_new_request( $new_id );
		Notifier::notify_requester_received( $new_id );

		return $new_id;
	}
}
