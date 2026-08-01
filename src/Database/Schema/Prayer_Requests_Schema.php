<?php
/**
 * Schema definition for the prayer_requests table.
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
 * Defines the column set for the `{prefix}intercessor_prayer_requests` table.
 *
 * Each entry in $columns maps to a BerlinDB Column object. The 'created' and
 * 'modified' flags instruct BerlinDB's Query layer to auto-populate those
 * datetime columns on INSERT and UPDATE respectively. 'sortable' columns are
 * whitelisted for ORDER BY; 'searchable' columns receive a KEY index and are
 * included in LIKE searches.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Prayer_Requests_Schema extends Schema {

	/**
	 * Column definitions for the prayer_requests table.
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
			'name'       => 'requester_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'default'    => '0',
			'sortable'   => true,
			'searchable' => false,
		),
		array(
			'name'       => 'subject',
			'type'       => 'varchar',
			'length'     => '255',
			'default'    => '',
			'searchable' => true,
			'sortable'   => true,
		),
		array(
			'name'    => 'content',
			'type'    => 'longtext',
			'default' => '',
			// longtext cannot carry a plain KEY index in MySQL/MariaDB without
			// a prefix length. Omitting 'searchable' prevents buildCreateSql()
			// from emitting "KEY content (content)", which would cause dbDelta
			// to fail silently and leave the table uncreated. LIKE searches
			// against this column still work via Query::buildWhere().
		),
		array(
			'name'     => 'status',
			'type'     => 'varchar',
			'length'   => '50',
			'default'  => 'pending',
			'sortable' => true,
		),
		array(
			'name'    => 'is_anonymous',
			'type'    => 'tinyint',
			'length'  => '1',
			'default' => '0',
		),
		array(
			'name'    => 'is_public',
			'type'    => 'tinyint',
			'length'  => '1',
			'default' => '1',
		),
		array(
			'name'    => 'moderator_note',
			'type'    => 'text',
			'default' => '',
			// text cannot carry a plain KEY index. Omitting searchable here
			// prevents a KEY being generated for this column.
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
