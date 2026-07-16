<?php
/**
 * Query class for the prayer_notes table.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Database\Query;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\BerlinDB\Query;
use Intercessor\Database\Row\Prayer_Note;
use Intercessor\Database\Schema\Prayer_Notes_Schema;

/**
 * Provides CRUD and domain-specific query methods for the prayer_notes table.
 *
 * Prayer notes are internal annotations attached to prayer requests by
 * administrators or moderators. They are never surfaced in front-end templates.
 *
 * @since   1.0.0
 * @package Intercessor
 *
 * @method Prayer_Note|false get_item( int $id )
 * @method PrayerNote[]     get_items( array $args = [] )
 * @method int|false        add_item( array $data )
 * @method bool             update_item( int $id, array $data )
 * @method bool             delete_item( int $id )
 * @method int              count_items( array $args = [] )
 */
final class Prayer_Note_Query extends Query {

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
	protected string $table_name = 'prayer_notes';

	/**
	 * Short SQL alias used in query fragments.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $table_alias = 'pn';

	/**
	 * Fully-qualified Schema subclass name.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $table_schema = Prayer_Notes_Schema::class;

	/**
	 * Singular item label for cache keys and hook names.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $item_name = 'prayer_note';

	/**
	 * Plural item label for cache keys and hook names.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $item_name_plural = 'prayer_notes';

	/**
	 * Fully-qualified Row subclass instantiated for each result.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $item_shape = Prayer_Note::class;

	// -------------------------------------------------------------------------
	// Domain-specific methods
	// -------------------------------------------------------------------------

	/**
	 * Retrieve all notes for a given prayer request, oldest first.
	 *
	 * @since  1.0.0
	 * @param  int          $prayerRequestId Primary key of the parent prayer request.
	 * @return PrayerNote[]                  Chronologically ordered note rows.
	 */
	public function get_for_request( int $prayerRequestId ): array {
		return $this->get_items( array(
			'prayer_request_id' => $prayerRequestId,
			'orderby'           => 'date_created',
			'order'             => 'ASC',
			'number'            => 0,
		) );
	}

	/**
	 * Retrieve only private (admin-only) notes for a given prayer request.
	 *
	 * @since  1.0.0
	 * @param  int          $prayerRequestId Primary key of the parent prayer request.
	 * @return PrayerNote[]                  Private note rows, oldest first.
	 */
	public function get_private_for_request( int $prayerRequestId ): array {
		return $this->get_items( array(
			'prayer_request_id' => $prayerRequestId,
			'is_private'        => 1,
			'orderby'           => 'date_created',
			'order'             => 'ASC',
			'number'            => 0,
		) );
	}

	/**
	 * Add a new note authored by the current WordPress user.
	 *
	 * Automatically sets author_user_id to the currently logged-in user.
	 * Pass is_private = 0 to make the note visible to the requester.
	 *
	 * @since  1.0.0
	 * @param  int    $prayerRequestId Primary key of the parent prayer request.
	 * @param  string $content         Note body text.
	 * @param  bool   $private         Whether the note is admin-only. Default true.
	 * @return int|false               New note row ID on success; false on failure.
	 */
	public function add_note( int $prayerRequestId, string $content, bool $private = true ): int|false {
		return $this->add_item( array(
			'prayer_request_id' => $prayerRequestId,
			'author_user_id'    => get_current_user_id(),
			'content'           => $content,
			'is_private'        => $private ? 1 : 0,
		) );
	}

	/**
	 * Delete all notes associated with a given prayer request.
	 *
	 * Called when a prayer request is hard-deleted to maintain consistency,
	 * since the plugin does not enforce foreign key constraints at the DB level.
	 *
	 * @since  1.0.0
	 * @param  int  $prayerRequestId Primary key of the prayer request being deleted.
	 * @return bool                  True on success; false on DB error.
	 */
	public function delete_all_for_request( int $prayerRequestId ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$this->fq_table_name,
			array( 'prayer_request_id' => $prayerRequestId ),
			array( '%d' )
		);

		return $result !== false;
	}
}
