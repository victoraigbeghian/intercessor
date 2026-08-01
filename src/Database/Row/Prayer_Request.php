<?php
/**
 * Prayer request row value object.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Database\Row;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\BerlinDB\Row;

/**
 * Represents a single row from the intercessor_prayer_requests table.
 *
 * Instantiated automatically by PrayerRequestQuery when rows are fetched
 * from the database. All properties are hydrated from the raw stdClass
 * returned by $wpdb and cast to their correct PHP types in __construct().
 *
 * @since   1.0.0
 * @package Intercessor
 *
 * @property int    $id             Primary key.
 * @property int    $requester_id   Foreign key to the requesters table.
 * @property string $subject        Short subject line for the prayer request.
 * @property string $content        Full body text of the prayer request.
 * @property string $status         Lifecycle status: pending|approved|rejected|archived|private.
 * @property int    $is_anonymous   1 when the requester chose to hide their identity publicly.
 * @property int    $is_public      1 when the request may be displayed on the front end.
 * @property string $moderator_note Internal note added by a moderator.
 * @property string $date_created   UTC datetime string of initial submission.
 * @property string $date_modified  UTC datetime string of the most recent update.
 */
final class Prayer_Request extends Row {

	/**
	 * Primary key.
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	public int $id = 0;

	/**
	 * Foreign key referencing the requesters table.
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	public int $requester_id = 0;

	/**
	 * Short subject line for the prayer request.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $subject = '';

	/**
	 * Full body text of the prayer request.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $content = '';

	/**
	 * Lifecycle status: pending, approved, rejected, archived, or private.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $status = 'pending';

	/**
	 * Whether the requester elected to remain anonymous on public displays.
	 *
	 * Stored as a tinyint; cast to int on hydration. Use isAnonymous() for
	 * boolean checks.
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	public int $is_anonymous = 0;

	/**
	 * Whether the request may appear on the front end.
	 *
	 * Stored as a tinyint; cast to int on hydration. Use isPublic() for
	 * boolean checks.
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	public int $is_public = 1;

	/**
	 * Internal moderator note attached to the request.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $moderator_note = '';

	/**
	 * UTC datetime string of the initial submission (format: Y-m-d H:i:s).
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $date_created = '';

	/**
	 * UTC datetime string of the most recent update (format: Y-m-d H:i:s).
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $date_modified = '';

	/**
	 * Hydrate from the raw database object and cast integer columns.
	 *
	 * Calls parent::__construct() to populate all matching public properties
	 * from the stdClass, then explicitly casts tinyint/bigint columns to int
	 * because $wpdb returns all column values as strings.
	 *
	 * @since 1.0.0
	 * @param object $item Raw stdClass returned by $wpdb.
	 */
	public function __construct( object $item ) {
		parent::__construct( $item );

		$this->id           = (int) $this->id;
		$this->requester_id = (int) $this->requester_id;
		$this->is_anonymous = (int) $this->is_anonymous;
		$this->is_public    = (int) $this->is_public;
	}

	/**
	 * Return true when the requester elected to remain anonymous.
	 *
	 * @since  1.0.0
	 * @return bool True when is_anonymous equals 1.
	 */
	public function is_anonymous(): bool {
		return $this->is_anonymous === 1;
	}

	/**
	 * Return true when the request is visible on the front end.
	 *
	 * @since  1.0.0
	 * @return bool True when is_public equals 1.
	 */
	public function is_public(): bool {
		return $this->is_public === 1;
	}

	/**
	 * Return true when the request is awaiting moderation.
	 *
	 * @since  1.0.0
	 * @return bool True when status equals 'pending'.
	 */
	public function is_pending(): bool {
		return $this->status === 'pending';
	}

	/**
	 * Return true when the request has been approved by a moderator.
	 *
	 * @since  1.0.0
	 * @return bool True when status equals 'approved'.
	 */
	public function is_approved(): bool {
		return $this->status === 'approved';
	}

	/**
	 * Return true when the request has been marked private by a moderator.
	 *
	 * Private requests are hidden from all public-facing displays and REST
	 * API responses for non-admin callers, but remain visible in the admin.
	 *
	 * @since  1.0.0
	 * @return bool True when status equals 'private'.
	 */
	public function is_private_status(): bool {
		return $this->status === 'private';
	}
}
