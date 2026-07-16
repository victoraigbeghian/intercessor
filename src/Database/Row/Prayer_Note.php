<?php
/**
 * Prayer note row value object.
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
 * Represents a single row from the intercessor_prayer_notes table.
 *
 * Prayer notes are private internal annotations attached to a prayer request
 * by a moderator or administrator. They are never exposed to requesters or
 * displayed in front-end block templates.
 *
 * @since   1.0.0
 * @package Intercessor
 *
 * @property int    $id                Primary key.
 * @property int    $prayer_request_id Foreign key to the prayer_requests table.
 * @property int    $author_user_id    WordPress user ID of the note author.
 * @property string $content           Note body text.
 * @property int    $is_private        1 = private (admin only), 0 = shared with requester.
 * @property string $date_created      UTC datetime of note creation.
 * @property string $date_modified     UTC datetime of most recent edit.
 */
final class Prayer_Note extends Row {

	/**
	 * Primary key.
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	public int $id = 0;

	/**
	 * Foreign key referencing the parent prayer request.
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	public int $prayer_request_id = 0;

	/**
	 * WordPress user ID of the moderator who authored the note.
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	public int $author_user_id = 0;

	/**
	 * Body text of the internal note.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $content = '';

	/**
	 * Whether this note is private (admin-only).
	 *
	 * 1 = visible only to administrators and moderators.
	 * 0 = may be shared with the requester.
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	public int $is_private = 1;

	/**
	 * UTC datetime string of note creation (format: Y-m-d H:i:s).
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $date_created = '';

	/**
	 * UTC datetime string of the most recent edit (format: Y-m-d H:i:s).
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $date_modified = '';

	/**
	 * Hydrate from the raw database object and cast integer columns.
	 *
	 * @since 1.0.0
	 * @param object $item Raw stdClass returned by $wpdb.
	 */
	public function __construct( object $item ) {
		parent::__construct( $item );

		$this->id                = (int) $this->id;
		$this->prayer_request_id = (int) $this->prayer_request_id;
		$this->author_user_id    = (int) $this->author_user_id;
		$this->is_private        = (int) $this->is_private;
	}

	/**
	 * Return true when this note is restricted to administrators and moderators.
	 *
	 * @since  1.0.0
	 * @return bool True when is_private equals 1.
	 */
	public function is_private(): bool {
		return $this->is_private === 1;
	}
}
