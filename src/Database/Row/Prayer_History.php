<?php
/**
 * Prayer history row value object.
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
 * Represents a single row from the intercessor_prayer_history table.
 *
 * Each instance is an immutable audit record of one status transition on a
 * prayer request. History rows are created by Prayer_Request_Query::update_status()
 * and are never modified after insertion.
 *
 * @since   1.0.0
 * @package Intercessor
 *
 * @property int    $id                Primary key.
 * @property int    $prayer_request_id Foreign key to the prayer_requests table.
 * @property string $old_status        Status value before the transition.
 * @property string $new_status        Status value after the transition.
 * @property int    $actor_user_id     WordPress user ID of the moderator who acted.
 * @property string $note              Optional moderator note recorded at transition time.
 * @property string $date_created      UTC datetime string of the status change.
 */
final class Prayer_History extends Row {

	/**
	 * Primary key.
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	public int $id = 0;

	/**
	 * Foreign key referencing the prayer request that was updated.
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	public int $prayer_request_id = 0;

	/**
	 * Status value that the request held before this transition.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $old_status = '';

	/**
	 * Status value applied to the request by this transition.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $new_status = '';

	/**
	 * WordPress user ID of the moderator who triggered the status change.
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	public int $actor_user_id = 0;

	/**
	 * Optional moderator note recorded at the time of the status change.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $note = '';

	/**
	 * UTC datetime string of when this history entry was created (format: Y-m-d H:i:s).
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $date_created = '';

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
		$this->actor_user_id     = (int) $this->actor_user_id;
	}
}
