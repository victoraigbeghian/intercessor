<?php
/**
 * Schema definition for the prayer_notes table.
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
 * Defines the column set for the `{prefix}intercessor_prayer_notes` table.
 *
 * Prayer notes are private annotations that moderators or administrators
 * attach to a prayer request for internal tracking purposes. They are never
 * shown to the requester or on the public-facing block templates.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Prayer_Notes_Schema extends Schema {

	/**
	 * Column definitions for the prayer_notes table.
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
			'name'     => 'author_user_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'default'  => '0',
			'sortable' => true,
		),
		array(
			'name'    => 'content',
			'type'    => 'text',
			'default' => '',
			// text cannot carry a plain KEY index in MySQL/MariaDB without a
			// prefix length. Omitting 'searchable' prevents buildCreateSql()
			// from emitting "KEY content (content)", which would cause dbDelta
			// to fail and leave the table uncreated.
		),
		array(
			'name'    => 'is_private',
			'type'    => 'tinyint',
			'length'  => '1',
			'default' => '1',
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
