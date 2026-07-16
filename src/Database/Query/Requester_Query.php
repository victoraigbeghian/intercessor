<?php
/**
 * Query class for the requesters table.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Database\Query;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\BerlinDB\Query;
use Intercessor\Database\Row\Requester;
use Intercessor\Database\Schema\Requesters_Schema;
use Intercessor\Database\Query\Prayer_Request_Query;

/**
 * Provides CRUD and domain-specific query methods for the requesters table.
 *
 * @since   1.0.0
 * @package Intercessor
 *
 * @method Requester|false get_item( int $id )
 * @method Requester[]     get_items( array $args = [] )
 * @method int|false       add_item( array $data )
 * @method bool            update_item( int $id, array $data )
 * @method bool            delete_item( int $id )
 * @method int             count_items( array $args = [] )
 */
final class Requester_Query extends Query {

	/**
	 * Shared prefix for all Intercessor table names.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $prefix = 'intercessor';

	/**
	 * Table name segment (appended to prefix + $wpdb->prefix).
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $table_name = 'requesters';

	/**
	 * Short SQL alias used in query fragments.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $table_alias = 'rq';

	/**
	 * Fully-qualified Schema subclass name.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $table_schema = Requesters_Schema::class;

	/**
	 * Singular item label used for cache keys and hook names.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $item_name = 'requester';

	/**
	 * Plural item label used for cache keys and hook names.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $item_name_plural = 'requesters';

	/**
	 * Fully-qualified Row subclass instantiated for each result.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $item_shape = Requester::class;

	/**
	 * Find an existing requester by email, or create one if none exists.
	 *
	 * Used by Public_Loader and Rest_Api to ensure each form submission is
	 * associated with exactly one requester record per email address. When a
	 * match is found, returns its ID without modifying the existing row.
	 *
	 * The combined name is written to the legacy name column for backward
	 * compatibility with any code that reads it directly.
	 *
	 * @since  1.0.0
	 * @param  string $email      Sanitized email address of the submitter.
	 * @param  string $first_name First name provided at submission time.
	 * @param  string $last_name  Last name provided at submission time.
	 * @return int|false          Requester primary key on success; false on DB error.
	 */
	public function find_or_create( string $email, string $first_name, string $last_name = '' ): int|false {
		$existing = $this->get_items(
			array(
				'email'  => $email,
				'number' => 1,
			)
		);

		if ( ! empty( $existing ) ) {
			return $existing[0]->id;
		}

		$full_name = trim( $first_name . ' ' . $last_name );

		return $this->add_item(
			array(
				'email'      => $email,
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'name'       => $full_name ?: $email,
				'wp_user_id' => get_current_user_id(),
			)
		);
	}

	/**
	 * Find a requester row linked to a specific WordPress user account.
	 *
	 * Returns the first match ordered by the default sort (date_created ASC).
	 * Returns null when no row has the given wp_user_id.
	 *
	 * @since  1.0.0
	 * @param  int           $userId WordPress user ID to search for.
	 * @return Requester|null        Matching requester row, or null if not found.
	 */
	public function find_by_wp_user( int $userId ): ?Requester {
		$results = $this->get_items(
			array(
				'wp_user_id' => $userId,
				'number'     => 1,
			)
		);

		return $results[0] ?? null;
	}

	/**
	 * Check whether a requester (identified by email) has already submitted
	 * a prayer request with the same subject line.
	 *
	 * Used by Submission_Pipeline to block duplicate submissions when the
	 * "Prevent Duplicate Requests" setting is enabled.
	 *
	 * Returns false immediately when no requester record exists for the given
	 * email — a first-time submitter can never have a duplicate.
	 *
	 * @since  1.0.1
	 * @param  string $email   Sanitized email address of the submitter.
	 * @param  string $subject Sanitized subject line of the new request.
	 * @return bool            True when a duplicate exists; false otherwise.
	 */
	public function has_duplicate_subject( string $email, string $subject ): bool {
		if ( '' === $email || '' === $subject ) {
			return false;
		}

		$existing = $this->get_items(
			array(
				'email'  => $email,
				'number' => 1,
				'fields' => 'id',
			)
		);

		if ( empty( $existing ) ) {
			return false;
		}

		$requester_id = (int) $existing[0];

		$prayer_query = new Prayer_Request_Query();
		$matches      = $prayer_query->get_items(
			array(
				'requester_id' => $requester_id,
				'subject'      => $subject,
				'number'       => 1,
				'fields'       => 'id',
			)
		);

		return ! empty( $matches );
	}
}
