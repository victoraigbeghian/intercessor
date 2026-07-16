<?php
/**
 * Prayed count row value object.
 *
 * @package Intercessor
 * @since   1.1.0
 */
declare(strict_types=1);

namespace Intercessor\Database\Row;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\BerlinDB\Row;

/**
 * Represents a single row from the intercessor_prayed_counts table.
 *
 * Each row records that a specific actor — a logged-in WordPress user or an
 * anonymous visitor — has indicated they are praying for a particular request.
 * Instead of inserting a new row on each interaction, Prayed_Count_Query::record_prayer()
 * increments the count column on the existing row for the same actor+request pair.
 *
 * @since   1.1.0
 * @package Intercessor
 *
 * @property int    $id                Primary key.
 * @property int    $prayer_request_id Foreign key to the prayer_requests table.
 * @property int    $user_id           WordPress user ID; 0 when the actor is anonymous.
 * @property string $anonymous_key     SHA fingerprint identifying a guest actor.
 * @property int    $count             Running count of prayer interactions by this actor.
 * @property string $date_created      UTC datetime string of the first recorded prayer.
 * @property string $date_modified     UTC datetime string of the most recent prayer interaction.
 */
final class Prayed_Count extends Row {

	/**
	 * Primary key.
	 *
	 * @since 1.1.0
	 * @var   int
	 */
	public int $id = 0;

	/**
	 * Foreign key referencing the prayer request being prayed for.
	 *
	 * @since 1.1.0
	 * @var   int
	 */
	public int $prayer_request_id = 0;

	/**
	 * WordPress user ID of the actor; 0 for anonymous visitors.
	 *
	 * @since 1.1.0
	 * @var   int
	 */
	public int $user_id = 0;

	/**
	 * Hashed session or cookie fingerprint used to identify anonymous actors.
	 *
	 * Empty string when the actor is a logged-in WordPress user.
	 *
	 * @since 1.1.0
	 * @var   string
	 */
	public string $anonymous_key = '';

	/**
	 * Running count of prayer interactions by this actor for this request.
	 *
	 * Incremented by Prayed_Count_Query::record_prayer() on repeat interactions.
	 *
	 * @since 1.1.0
	 * @var   int
	 */
	public int $count = 1;

	/**
	 * UTC datetime string of the first recorded prayer (format: Y-m-d H:i:s).
	 *
	 * @since 1.1.0
	 * @var   string
	 */
	public string $date_created = '';

	/**
	 * UTC datetime string of the most recent prayer interaction (format: Y-m-d H:i:s).
	 *
	 * @since 1.1.0
	 * @var   string
	 */
	public string $date_modified = '';

	/**
	 * Hydrate from the raw database object and cast integer columns.
	 *
	 * @since 1.1.0
	 * @param object $item Raw stdClass returned by $wpdb.
	 */
	public function __construct( object $item ) {
		parent::__construct( $item );

		$this->id                = (int) $this->id;
		$this->prayer_request_id = (int) $this->prayer_request_id;
		$this->user_id           = (int) $this->user_id;
		$this->count             = (int) $this->count;
	}

	/**
	 * Return true when this record belongs to a logged-in WordPress user.
	 *
	 * @since  1.1.0
	 * @return bool True when user_id is greater than zero.
	 */
	public function is_from_user(): bool {
		return $this->user_id > 0;
	}

	/**
	 * Return true when this record belongs to an anonymous (guest) visitor.
	 *
	 * A record is considered anonymous when it has no linked WordPress user
	 * but does carry a non-empty fingerprint key.
	 *
	 * @since  1.1.0
	 * @return bool True when user_id is 0 and anonymous_key is non-empty.
	 */
	public function is_anonymous(): bool {
		return $this->user_id === 0 && $this->anonymous_key !== '';
	}
}
