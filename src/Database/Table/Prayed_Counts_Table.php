<?php
/**
 * BerlinDB table definition for prayed_counts.
 *
 * @package Intercessor
 * @since   1.1.0
 */
declare(strict_types=1);

namespace Intercessor\Database\Table;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\BerlinDB\Table;
use Intercessor\Database\Schema\Prayed_Counts_Schema;

/**
 * BerlinDB Table definition for `{prefix}intercessor_prayed_counts`.
 *
 * Registers the prayed_counts interaction table with $wpdb and manages its
 * schema via dbDelta. To add or modify columns in a future release, bump
 * $version and add ALTER TABLE statements in upgrade() before the parent call.
 *
 * @since   1.1.0
 * @package Intercessor
 */
final class Prayed_Counts_Table extends Table {

	/**
	 * Table name without the global $wpdb->prefix.
	 *
	 * @since 1.1.0
	 * @var   string
	 */
	protected string $name = 'intercessor_prayed_counts';

	/**
	 * Semver string; bump to trigger a schema upgrade via upgrade().
	 *
	 * @since 1.1.0
	 * @var   string
	 */
	protected string $version = '1.0.0';

	/**
	 * Fully-qualified Schema subclass that defines the column set.
	 *
	 * @since 1.1.0
	 * @var   string
	 */
	protected string $schema = Prayed_Counts_Schema::class;

	/**
	 * Run schema migrations when the installed version is behind $version.
	 *
	 * Add ALTER TABLE statements here for future changes before calling
	 * parent::upgrade() which re-runs dbDelta and persists the new version.
	 *
	 * Example:
	 *     if ( version_compare( $this->get_version(), '1.1.0', '<' ) ) {
	 *         global $wpdb;
	 *         $wpdb->query( "ALTER TABLE {$this->table_name} ADD COLUMN `source` varchar(50) NOT NULL DEFAULT 'web'" );
	 *     }
	 *
	 * @since  1.1.0
	 * @return void
	 */
	public function upgrade(): void {
		parent::upgrade();
	}
}
