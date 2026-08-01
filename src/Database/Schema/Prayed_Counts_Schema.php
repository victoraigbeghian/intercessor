<?php
/**
 * Schema definition for the prayed_counts table.
 *
 * @package Intercessor
 * @since   1.1.0
 */
declare(strict_types=1);

namespace Intercessor\Database\Schema;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\BerlinDB\Schema;

/**
 * Defines the column set for the `{prefix}intercessor_prayed_counts` table.
 *
 * Each row records that a specific actor — either a logged-in WordPress user
 * (identified by user_id) or an anonymous visitor (identified by a hashed
 * anonymous_key fingerprint) — has indicated they are praying for a request.
 * The count column is incremented on repeat interactions rather than inserting
 * a new row, keeping the table compact.
 *
 * @since   1.1.0
 * @package Intercessor
 */
final class Prayed_Counts_Schema extends Schema {

	/**
	 * Column definitions for the prayed_counts table.
	 *
	 * @since 1.1.0
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
			'name'       => 'prayer_request_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'default'    => '0',
			'sortable'   => true,
			'searchable' => false,
		),
		array(
			'name'     => 'user_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'default'  => '0',
			'sortable' => true,
		),
		array(
			'name'       => 'anonymous_key',
			'type'       => 'varchar',
			'length'     => '64',
			'default'    => '',
			'searchable' => false,
		),
		array(
			'name'     => 'count',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'default'  => '1',
			'sortable' => true,
		),
		array(
			'name'       => 'date_created',
			'type'       => 'datetime',
			'default'    => '0000-00-00 00:00:00',
			'created'    => true,
			'date_query' => true,
			'sortable'   => true,
		),
		array(
			'name'       => 'date_modified',
			'type'       => 'datetime',
			'default'    => '0000-00-00 00:00:00',
			'modified'   => true,
			'date_query' => true,
			'sortable'   => true,
		),
	);
}
