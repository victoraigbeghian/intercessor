<?php
/**
 * BerlinDB table definition for requesters.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Database\Table;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\BerlinDB\Table;
use Intercessor\Database\Schema\Requesters_Schema;

/**
 * BerlinDB Table definition for `{prefix}intercessor_requesters`.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Requesters_Table extends Table {

	/**
	 * Table name without the global $wpdb->prefix.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $name = 'intercessor_requesters';

	/**
	 * Semver string; bump to trigger upgrade().
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $version = '1.0.2';

	/**
	 * Fully-qualified Schema subclass that defines the column set.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $schema = Requesters_Schema::class;

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
