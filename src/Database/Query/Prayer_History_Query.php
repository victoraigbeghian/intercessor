<?php
/**
 * Query class for the prayer_history table.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Database\Query;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\BerlinDB\Query;
use Intercessor\Database\Row\Prayer_History;
use Intercessor\Database\Schema\Prayer_History_Schema;

/**
 * Provides CRUD and domain-specific query methods for the prayer_history table.
 *
 * History rows are append-only audit records created automatically by
 * Prayer_Request_Query::update_status(). Direct calls to update_item() or
 * delete_item() are intentionally discouraged to preserve the audit trail.
 *
 * @since   1.0.0
 * @package Intercessor
 *
 * @method Prayer_History|false get_item( int $id )
 * @method PrayerHistory[]     get_items( array $args = [] )
 * @method int|false           add_item( array $data )
 * @method bool                update_item( int $id, array $data )
 * @method bool                delete_item( int $id )
 * @method int                 count_items( array $args = [] )
 */
final class Prayer_History_Query extends Query {

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
	protected string $table_name = 'prayer_history';

	/**
	 * Short SQL alias used in query fragments.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $table_alias = 'ph';

	/**
	 * Fully-qualified Schema subclass name.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $table_schema = Prayer_History_Schema::class;

	/**
	 * Singular item label used for cache keys and hook names.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $item_name = 'prayer_history';

	/**
	 * Plural item label used for cache keys and hook names.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $item_name_plural = 'prayer_histories';

	/**
	 * Fully-qualified Row subclass instantiated for each result.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $item_shape = Prayer_History::class;

	/**
	 * Retrieve the complete status-change timeline for a single prayer request.
	 *
	 * Results are ordered chronologically (oldest first) to match the natural
	 * reading direction of a timeline UI component.
	 *
	 * @since  1.0.0
	 * @param  int              $prayerRequestId Primary key of the parent prayer request.
	 * @return PrayerHistory[]                   Chronologically ordered history rows.
	 */
	public function get_for_request( int $prayerRequestId ): array {
		return $this->get_items(
			array(
				'prayer_request_id' => $prayerRequestId,
				'orderby'           => 'date_created',
				'order'             => 'ASC',
			)
		);
	}

	/**
	 * Delete all history rows associated with a given prayer request.
	 *
	 * Called during a hard delete of a prayer request to keep the database
	 * consistent. History rows are otherwise append-only.
	 *
	 * @since  1.0.0
	 * @param  int  $prayerRequestId Primary key of the prayer request being deleted.
	 * @return bool                  True on success; false on DB error.
	 */
	public function delete_all_for_request( int $prayerRequestId ): bool {
		global $wpdb;

		$result = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->fq_table_name,
			array( 'prayer_request_id' => $prayerRequestId ),
			array( '%d' )
		);

		return $result !== false;
	}
}
