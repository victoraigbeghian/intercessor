<?php
/**
 * Email notification utility.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Util;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\Admin\Settings;
use Intercessor\Database\Query\Prayer_Request_Query;
use Intercessor\Database\Query\Requester_Query;
use Intercessor\Database\Row\Prayer_Request;
use Intercessor\Database\Row\Requester;

/**
 * Sends email notifications for prayer request lifecycle events.
 *
 * All methods are static and check the relevant notification toggle in
 * Settings before dispatching any email. Uses WordPress wp_mail() so
 * the site's configured mailer (SMTP, SES, etc.) is automatically used.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Notifier {

	/**
	 * Notify the site administrator that a new prayer request was submitted.
	 *
	 * Respects the 'notify_admin_new_request' setting. The recipient address
	 * falls back to the WordPress admin email when 'admin_email' is empty.
	 *
	 * @since  1.0.0
	 * @param  int $prayerRequestId  Primary key of the newly created prayer request.
	 * @return void
	 */
	public static function notify_admin_new_request( int $prayerRequestId ): void {
		if ( ! Settings::get( 'notify_admin_new_request', true ) ) {
			return;
		}

		$query = new Prayer_Request_Query();
		$item  = $query->get_item( $prayerRequestId );

		if ( ! $item ) {
			return;
		}

		$to      = Settings::get( 'admin_email' ) ?: get_option( 'admin_email' );
		// translators: %s: prayer request subject line
		$subject = sprintf(
			/* translators: %s: prayer request subject line */
			__( 'New Prayer Request: %s', 'intercessor' ),
			$item->subject
		);

		// translators: %s: prayer request subject line
		$message = sprintf(
			/* translators: %s: prayer request subject line */
			__( "A new prayer request has been submitted.\n\nSubject: %s\n\nPlease log in to review it.", 'intercessor' ),
			$item->subject
		);

		wp_mail( sanitize_email( $to ), $subject, $message );
	}

	/**
	 * Send a confirmation email to the requester after their request is received.
	 *
	 * Respects the 'notify_requester_received' setting. Silently returns when
	 * the requester record is missing or has no email address.
	 *
	 * @since  1.0.0
	 * @param  int $prayerRequestId  Primary key of the submitted prayer request.
	 * @return void
	 */
	public static function notify_requester_received( int $prayerRequestId ): void {
		if ( ! Settings::get( 'notify_requester_received', true ) ) {
			return;
		}

		list( $item, $requester ) = self::fetch_pair( $prayerRequestId );

		if ( ! $item || ! $requester || ! $requester->email ) {
			return;
		}

		$subject = __( 'We received your prayer request', 'intercessor' );
		$message = sprintf(
			/* translators: 1: requester display name 2: prayer request subject line */
			__( "Dear %1\$s,\n\nThank you for submitting your prayer request: \"%2\$s\".\n\nWe will review it shortly.", 'intercessor' ),
			$requester->get_display_name(),
			$item->subject
		);

		wp_mail( sanitize_email( $requester->email ), $subject, $message );
	}

	/**
	 * Notify the requester that the status of their request has changed.
	 *
	 * Respects the 'notify_requester_status_change' setting. Called by
	 * ModerationHandler and Prayer_Request_Query::update_status() after a
	 * successful status update.
	 *
	 * @since  1.0.0
	 * @param  int    $prayerRequestId  Primary key of the affected prayer request.
	 * @param  string $newStatus        The status string that was just applied (e.g. 'approved').
	 * @return void
	 */
	public static function notify_status_change( int $prayerRequestId, string $newStatus ): void {
		if ( ! Settings::get( 'notify_requester_status_change', true ) ) {
			return;
		}

		list( $item, $requester ) = self::fetch_pair( $prayerRequestId );

		if ( ! $item || ! $requester || ! $requester->email ) {
			return;
		}

		// translators: %s: new status label
		$subject = sprintf(
			/* translators: %s: prayer request subject line */
			__( 'Update on your prayer request: %s', 'intercessor' ),
			$item->subject
		);

		$message = sprintf(
			/* translators: 1: requester display name 2: new status label */
			__( "Dear %1\$s,\n\nYour prayer request has been updated.\n\nNew status: %2\$s\n\nThank you.", 'intercessor' ),
			$requester->get_display_name(),
			$newStatus
		);

		wp_mail( sanitize_email( $requester->email ), $subject, $message );
	}

	/**
	 * Fetch a prayer request and its associated requester row in a single method call.
	 *
	 * Used internally to avoid duplicating the two-query lookup across
	 * notification methods. Returns false for the request when the ID does
	 * not exist; returns null for the requester when the request has no
	 * requester_id or the requester row is missing.
	 *
	 * @since  1.0.0
	 * @param  int $prayerRequestId  Primary key of the prayer request.
	 * @return array{0: Prayer_Request|false, 1: Requester|false|null}
	 *     Index 0: prayer request row or false.
	 *     Index 1: requester row, false on DB failure, or null when not linked.
	 */
	private static function fetch_pair( int $prayerRequestId ): array {
		$pqQuery   = new Prayer_Request_Query();
		$item      = $pqQuery->get_item( $prayerRequestId );
		$requester = null;

		if ( $item && $item->requester_id > 0 ) {
			$rQuery    = new Requester_Query();
			$requester = $rQuery->get_item( $item->requester_id );
		}

		return array( $item, $requester );
	}
}
