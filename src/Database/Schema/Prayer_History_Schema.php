<?php
/**
 * Schema definition for the prayer_history table.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Database\Schema;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\BerlinDB\Schema;

/**
 * Defines the column set for the `{prefix}intercessor_prayer_history` table.
 *
 * Each row is an immutable audit record of a single status transition on a
 * prayer request. The actor_user_id records which WordPress user made the
 * change; the note column captures any accompanying moderator comment.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Prayer_History_Schema extends Schema {

	/**
	 * Column definitions for the prayer_history table.
	 *
	 * @since 1.0.0
	 * @var   array<int, array<string, mixed>>
	 */
	public array $columns = array(
		array(
			'name'     => 'id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'extra'    => 'auto_increment',
			'primary'  => true,
			'sortable' => true,
		),
		array(
			'name'     => 'prayer_request_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'default'  => '0',
			'sortable' => true,
		),
		array(
			'name'    => 'old_status',
			'type'    => 'varchar',
			'length'  => '50',
			'default' => '',
		),
		array(
			'name'    => 'new_status',
			'type'    => 'varchar',
			'length'  => '50',
			'default' => '',
		),
		array(
			'name'     => 'actor_user_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'default'  => '0',
			'sortable' => true,
		),
		array(
			'name'       => 'note',
			'type'       => 'text',
			'default'    => '',
			'searchable' => false,
		),
		array(
			'name'       => 'date_created',
			'type'       => 'datetime',
			'default'    => '0000-00-00 00:00:00',
			'created'    => true,
			'date_query' => true,
			'sortable'   => true,
		),
	);
}
