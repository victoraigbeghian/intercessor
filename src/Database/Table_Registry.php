<?php
/**
 * Database table registry.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Database;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\Database\Table\Prayer_Requests_Table;
use Intercessor\Database\Table\Requesters_Table;
use Intercessor\Database\Table\Prayer_History_Table;
use Intercessor\Database\Table\Prayer_Notes_Table;
use Intercessor\Database\Table\Prayed_Counts_Table;
use Intercessor\Database\Table\Requester_Notes_Table;

/**
 * Registers and installs all BerlinDB-managed database tables.
 *
 * Every Table subclass must be listed in $tables. register() is called on
 * 'init' priority 5 so tables are available before any query class runs.
 * install() is called explicitly during activation to guarantee all tables
 * exist before any other activation code (options, capabilities, etc.) runs.
 *
 * Table installation order matters when rows in one table reference another
 * (logical FK relationships). Parent tables are listed before child tables.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Table_Registry {

	/**
	 * Ordered list of all BerlinDB Table subclass FQCNs.
	 *
	 * Order: requesters and prayer_requests first (parent tables),
	 * then child tables (prayer_history, prayer_notes, prayed_counts).
	 *
	 * @since 1.0.0
	 * @var   list<class-string<\Intercessor\BerlinDB\Table>>
	 */
	private static array $tables = array(
		Requesters_Table::class,
		Prayer_Requests_Table::class,
		Prayer_History_Table::class,
		Prayer_Notes_Table::class,
		Prayed_Counts_Table::class,
		Requester_Notes_Table::class,
	);

	/**
	 * Instantiate each table class to register it with $wpdb.
	 *
	 * Constructing a Table sets $wpdb->{name} and appends to $wpdb->tables.
	 * The constructor also calls maybeUpgrade() which runs dbDelta automatically
	 * when the stored schema version is behind the declared $version.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function register(): void {
		foreach ( self::$tables as $tableClass ) {
			new $tableClass();
		}
	}

	/**
	 * Force a synchronous upgrade pass on all registered tables.
	 *
	 * Called during activation to guarantee tables exist before any other
	 * activation code runs. Instantiates each Table and explicitly calls
	 * upgrade() to run dbDelta regardless of the stored version, ensuring
	 * a clean install on a fresh WordPress site.
	 *
	 * This is the definitive table-creation path on first activation.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function install(): void {
		foreach ( self::$tables as $tableClass ) {
			$table = new $tableClass();
			$table->upgrade();
		}
	}
}
