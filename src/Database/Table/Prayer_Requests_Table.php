<?php
/**
 * BerlinDB table definition for prayer_requests.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Database\Table;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\BerlinDB\Table;
use Intercessor\Database\Schema\Prayer_Requests_Schema;

/**
 * BerlinDB Table definition for `{prefix}intercessor_prayer_requests`.
 *
 * BerlinDB calls upgrade() when the stored schema version is behind $version.
 * To add a migration: bump $version, add ALTER TABLE logic before parent::upgrade().
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Prayer_Requests_Table extends Table {

	/**
	 * Table name without the global $wpdb->prefix.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $name = 'intercessor_prayer_requests';

	/**
	 * Semver string; bump to trigger upgrade().
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $version = '1.0.1';

	/**
	 * Fully-qualified Schema subclass that defines the column set.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $schema = Prayer_Requests_Schema::class;

	/**
	 * Run schema migrations and call dbDelta via parent.
	 *
	 * IMPORTANT: parent::upgrade() MUST be called so dbDelta runs and the
	 * schema version is persisted in the options table.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function upgrade(): void {
		// Add future ALTER TABLE migrations here before the parent call.
		// Example:
		// if ( version_compare( $this->get_version(), '1.1.0', '<' ) ) {
		//     global $wpdb;
		//     $wpdb->query( "ALTER TABLE {$this->table_name} ADD COLUMN ..." );
		// }
		parent::upgrade();
	}
}
