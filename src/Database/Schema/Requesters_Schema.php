<?php
/**
 * Schema definition for the requesters table.
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
 * Defines the column set for the `{prefix}intercessor_requesters` table.
 *
 * A requester represents a person who has submitted at least one prayer
 * request. The wp_user_id column links to the WordPress user table when
 * the requester is a registered site member; it is 0 for guests.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Requesters_Schema extends Schema {

	/**
	 * Column definitions for the requesters table.
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
			'name'       => 'wp_user_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'default'    => '0',
			'sortable'   => true,
		),
		array(
			'name'       => 'first_name',
			'type'       => 'varchar',
			'length'     => '100',
			'default'    => '',
			'searchable' => true,
			'sortable'   => true,
		),
		array(
			'name'       => 'last_name',
			'type'       => 'varchar',
			'length'     => '100',
			'default'    => '',
			'searchable' => true,
			'sortable'   => true,
		),
		array(
			'name'       => 'name',
			'type'       => 'varchar',
			'length'     => '255',
			'default'    => '',
			'searchable' => true,
			'sortable'   => true,
		),
		array(
			'name'       => 'email',
			'type'       => 'varchar',
			'length'     => '255',
			'default'    => '',
			'searchable' => true,
			'sortable'   => true,
		),
		array(
			'name'     => 'status',
			'type'     => 'varchar',
			'length'   => '50',
			'default'  => 'active',
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
