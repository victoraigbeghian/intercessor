<?php
/**
 * Requester row value object.
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
 * Represents a single row from the intercessor_requesters table.
 *
 * A requester is a person who has submitted one or more prayer requests.
 * They may be a registered WordPress user (wp_user_id > 0) or a guest.
 * Instantiated automatically by RequesterQuery when rows are fetched.
 *
 * Name storage:
 *   first_name + last_name hold the structured name captured since v1.0.2.
 *   name is the legacy combined field retained for backward compatibility;
 *   pre-existing rows that only have name set still display correctly.
 *
 * @since   1.0.0
 * @package Intercessor
 *
 * @property int    $id            Primary key.
 * @property int    $wp_user_id    Linked WordPress user ID; 0 for guests.
 * @property string $first_name    First name (since 1.0.2).
 * @property string $last_name     Last name (since 1.0.2).
 * @property string $name          Legacy combined display name (pre-1.0.2 rows).
 * @property string $email         Email address.
 * @property string $status        Account status: active or blocked.
 * @property string $date_created  UTC datetime of first registration.
 * @property string $date_modified UTC datetime of most recent update.
 */
final class Requester extends Row {

	/**
	 * Primary key.
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	public int $id = 0;

	/**
	 * Linked WordPress user ID; 0 when the requester is a guest.
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	public int $wp_user_id = 0;

	/**
	 * First name of the requester (since v1.0.2).
	 *
	 * @since 1.0.2
	 * @var   string
	 */
	public string $first_name = '';

	/**
	 * Last name of the requester (since v1.0.2).
	 *
	 * @since 1.0.2
	 * @var   string
	 */
	public string $last_name = '';

	/**
	 * Legacy combined display name (retained for backward compatibility).
	 *
	 * Pre-v1.0.2 rows have only this field. New rows populate first_name and
	 * last_name and also write the combined string here. Call get_display_name()
	 * rather than reading this property directly.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $name = '';

	/**
	 * Email address used for notifications and deduplication.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $email = '';

	/**
	 * Account status: 'active' for normal access, 'blocked' to prevent submissions.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $status = 'active';

	/**
	 * UTC datetime string of the first prayer request submission (format: Y-m-d H:i:s).
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $date_created = '';

	/**
	 * UTC datetime string of the most recent record update (format: Y-m-d H:i:s).
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

		$this->id         = (int) $this->id;
		$this->wp_user_id = (int) $this->wp_user_id;
	}

	/**
	 * Return true when this requester is linked to a WordPress user account.
	 *
	 * @since  1.0.0
	 * @return bool True when wp_user_id is greater than zero.
	 */
	public function is_linked_to_user(): bool {
		return $this->wp_user_id > 0;
	}

	/**
	 * Return the best available display name for this requester.
	 *
	 * Resolution order:
	 *   1. first_name + last_name (v1.0.2+ rows).
	 *   2. first_name or last_name alone when only one is set.
	 *   3. Legacy name field (pre-v1.0.2 rows that have no first/last name).
	 *   4. Translated 'Anonymous' string as final fallback.
	 *
	 * @since  1.0.0
	 * @return string Non-empty display name, safe for output.
	 */
	public function get_display_name(): string {
		$full = trim( $this->first_name . ' ' . $this->last_name );

		if ( $full !== '' ) {
			return $full;
		}

		if ( $this->name !== '' ) {
			return $this->name;
		}

		return __( 'Anonymous', 'intercessor' );
	}

	/**
	 * Return the first name, falling back to the start of the legacy name field.
	 *
	 * @since  1.0.2
	 * @return string First name or empty string.
	 */
	public function get_first_name(): string {
		if ( $this->first_name !== '' ) {
			return $this->first_name;
		}

		// Attempt to infer from legacy name: return the first word.
		if ( $this->name !== '' ) {
			$parts = explode( ' ', $this->name, 2 );
			return $parts[0];
		}

		return '';
	}

	/**
	 * Return the last name, falling back to the remainder of the legacy name field.
	 *
	 * @since  1.0.2
	 * @return string Last name or empty string.
	 */
	public function get_last_name(): string {
		if ( $this->last_name !== '' ) {
			return $this->last_name;
		}

		// Attempt to infer from legacy name: return everything after the first word.
		if ( $this->name !== '' ) {
			$parts = explode( ' ', $this->name, 2 );
			return $parts[1] ?? '';
		}

		return '';
	}
}
