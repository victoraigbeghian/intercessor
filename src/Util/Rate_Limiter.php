<?php
/**
 * Submission rate limiter.
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

/**
 * Enforces the per-email daily prayer request submission limit.
 *
 * The limit is configured via the 'max_requests_per_day' plugin setting.
 * A value of 0 disables rate limiting entirely.
 *
 * The check works by:
 *   1. Looking up the requester record for the given email address.
 *   2. Counting prayer requests created by that requester in the last 24 hours
 *      using a direct $wpdb query (BerlinDB's date_query support is limited,
 *      so we use a raw WHERE date_created >= NOW() - INTERVAL 1 DAY).
 *   3. Returning false (blocked) when the count meets or exceeds the limit.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Rate_Limiter {

	/**
	 * Check whether the given email address has exceeded the daily submission limit.
	 *
	 * Returns true (allowed) when:
	 *   - The limit setting is 0 (disabled).
	 *   - The email has no existing requester record (first submission).
	 *   - The requester's submission count in the last 24 h is below the limit.
	 *
	 * Returns false (blocked) when the count meets or exceeds the limit.
	 *
	 * @since  1.0.0
	 * @param  string $email Sanitized email address of the submitter.
	 * @return bool          True when the submission is allowed; false when blocked.
	 */
	public static function is_allowed( string $email ): bool {
		$limit = (int) Settings::get( 'max_requests_per_day', 3 );

		// 0 = rate limiting disabled.
		if ( $limit === 0 ) {
			return true;
		}

		// Look up the requester record for this email.
		$requester_query = new Requester_Query();
		$existing        = $requester_query->get_items(
			array(
				'email'  => $email,
				'number' => 1,
			)
		);

		// No existing requester → this is their first submission, always allow.
		if ( empty( $existing ) ) {
			return true;
		}

		$requesterId = (int) $existing[0]->id;
		$count       = self::count_recent_submissions( $requesterId );

		return $count < $limit;
	}

	/**
	 * Return the number of prayer requests submitted by a requester in the last 24 hours.
	 *
	 * Uses a direct $wpdb query with a date comparison rather than BerlinDB's
	 * get_items() because the base Query class does not yet expose date_query
	 * args. The table name is retrieved via the global $wpdb object.
	 *
	 * @since  1.0.0
	 * @param  int $requesterId Requester primary key.
	 * @return int              Count of prayer requests in the last 24 hours.
	 */
	private static function count_recent_submissions( int $requesterId ): int {
		global $wpdb;

		// WordPress-safe table identifier (WP 6.3+ supports %i)
		$table = $wpdb->prefix . 'intercessor_prayer_requests';

		// Use WP time (respects timezone settings)
		$after = gmdate(
			'Y-m-d H:i:s',
			current_time( 'timestamp', true ) - DAY_IN_SECONDS
		);

		$sql = $wpdb->prepare(
			'SELECT COUNT(*)
			FROM %i
			WHERE requester_id = %d
			AND date_created >= %s',
			$table,
			$requesterId,
			$after
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Return the configured daily limit for display in error messages.
	 *
	 * @since  1.0.0
	 * @return int Configured limit; 0 means disabled.
	 */
	public static function get_limit(): int {
		return (int) Settings::get( 'max_requests_per_day', 3 );
	}
}
