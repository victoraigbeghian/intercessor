<?php
/**
 * Prayers DB class
 *
 * This class is for interacting with the Prayers' database table.
 *
 * @package     Intercessor
 * @subpackage  Database/Prayers Table
 * @copyright   Copyright (c) 2020, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       1.0.0
 */

namespace Intercessor\Database\Tables;

use Intercessor\Database\Table;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the global "intercessor_prayers" database table.
 *
 * @since 1.0.0
 */
final class Prayers extends Table {

	/**
	 * Table name
	 *
	 * @access protected
	 * @since 1.0.0
	 * @var string
	 */
	public $name = 'prayers';

	/**
	 * Database version
	 *
	 * @access protected
	 * @since 1.0.0
	 * @var int
	 */
	protected $version = 202109011;

	/**
	 * Array of upgrade versions and methods
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $upgrades = [
		'202001122' => 202001122,
		'202109011' => 202109011,
	];

	/**
	 * Setup the database schema
	 *
	 * @access protected
	 * @since 1.0.0
	 * @return void
	 */
	protected function set_schema() {
		$this->schema = "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            requester_id bigint(20) unsigned NOT NULL default '0',
            user_id bigint(20) unsigned NOT NULL default '0',
			email varchar(100) NOT NULL default '',
			title varchar(64) NOT NULL,
            message text NOT NULL,
            status varchar(20) NOT NULL default 'pending',
            prayer_key varchar(64) NOT NULL default '',
            share varchar(64) NOT NULL default 'freely',
            notify tinyint(2) NOT NULL default '1',
			date_created datetime NOT NULL default CURRENT_TIMESTAMP,
			date_active datetime default null,
			end_date datetime default null,			
			uuid varchar(100) NOT NULL default '',
			PRIMARY KEY (id),
			KEY requester_id (requester_id),
            KEY user_id (user_id),
            KEY email (email(100)),
            KEY status (status(20)),
            KEY prayer_key (prayer_key(64)),
            KEY date_created_active (date_created,date_active)";
	}

	/**
	 * Upgrade to version 202001122
	 * - Add the `uuid` varchar column
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	protected function __202001122() {

		// Look for column.
		$result = $this->column_exists( 'uuid' );

		// Maybe add column.
		if ( false === $result ) {
			$result = $this->get_db()->query(
				"ALTER TABLE {$this->table_name} ADD COLUMN `uuid` varchar(100) default '' AFTER `end_date`;"
			);
		}

		// Return success/fail.
		return $this->is_success( $result );
	}

	/**
	 * Upgrade to version 202109011
	 * - Change the dafault value for the column `date_created` to `CURRENT_TIMESTAMP`
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	protected function __202109011() {

		// Update `date_created`.
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} MODIFY COLUMN `date_created` datetime NOT NULL default CURRENT_TIMESTAMP;"
		);

		// Update `date_active`.
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} MODIFY COLUMN `date_active` datetime default null;"
		);

		if ( $this->is_success( $result ) ) {
			$this->get_db()->query( "UPDATE {$this->table_name} SET `date_active` = NULL WHERE `date_active` = '0000-00-00 00:00:00'" );
		}

		// Update `end_date`.
		$result = $this->get_db()->query(
			"ALTER TABLE {$this->table_name} MODIFY COLUMN `end_date` datetime default null;"
		);

		if ( $this->is_success( $result ) ) {
			$this->get_db()->query( "UPDATE {$this->table_name} SET `end_date` = NULL WHERE `end_date` = '0000-00-00 00:00:00'" );
		}

		// Return success/fail.
		return $this->is_success( $result );
	}
}
