<?php
/**
 * BerlinDB table definition for prayer_notes.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Database\Table;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\BerlinDB\Table;
use Intercessor\Database\Schema\Prayer_Notes_Schema;

/**
 * BerlinDB Table definition for `{prefix}intercessor_prayer_notes`.
 *
 * Stores private moderator annotations attached to prayer requests.
 * Notes are never shown on the front end or to requesters unless
 * is_private is explicitly set to 0 by an administrator.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Prayer_Notes_Table extends Table {

	/**
	 * Table name without the global $wpdb->prefix.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $name = 'intercessor_prayer_notes';

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
	protected string $schema = Prayer_Notes_Schema::class;

	/**
	 * Run schema migrations and call dbDelta via parent.
	 *
	 * parent::upgrade() MUST be called to run dbDelta and persist the version.
	 * Add ALTER TABLE statements before the parent call for future migrations.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function upgrade(): void {
		parent::upgrade();
	}
}
