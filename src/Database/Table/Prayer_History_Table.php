<?php
/**
 * BerlinDB table definition for prayer_history.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Database\Table;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\BerlinDB\Table;
use Intercessor\Database\Schema\Prayer_History_Schema;

/**
 * BerlinDB Table definition for `{prefix}intercessor_prayer_history`.
 *
 * Audit log table; rows are append-only. No UPDATEs or DELETEs are
 * performed on this table by the plugin's normal operation.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Prayer_History_Table extends Table {

	/**
	 * Table name without the global $wpdb->prefix.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $name = 'intercessor_prayer_history';

	/**
	 * Semver string; bump to trigger upgrade().
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $version = '1.0.0';

	/**
	 * Fully-qualified Schema subclass that defines the column set.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $schema = Prayer_History_Schema::class;

	/**
	 * Run schema migrations and call dbDelta via parent.
	 *
	 * parent::upgrade() MUST be called to run dbDelta and persist the version.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function upgrade(): void {
		parent::upgrade();
	}
}
