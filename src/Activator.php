<?php
/**
 * Plugin activation handler.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\Database\Table_Registry;
use Intercessor\Roles;
use Intercessor\Util\Cron_Handler;

/**
 * Handles all tasks that must run on plugin activation.
 *
 * Registered with register_activation_hook() in intercessor.php. Runs
 * synchronously during the activation request before WordPress redirects
 * back to the plugins list page.
 *
 * Activation sequence (order matters):
 * 1. Load dbDelta via require_once so it is available before any Table calls.
 * 2. Register all BerlinDB tables with $wpdb (sets named properties + tables[]).
 * 3. Install all tables by calling upgrade() on each, running dbDelta.
 * 4. Persist the plugin version to the options table for future upgrade checks.
 * 5. Flush rewrite rules so REST API endpoints resolve immediately.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Activator {

	/**
	 * Execute all activation tasks.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function activate(): void {
		// Step 1: Ensure dbDelta() is available. During activation WordPress
		// has not yet loaded wp-admin/includes/upgrade.php, so we load it now
		// before any Table::upgrade() call tries to use it.
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		// Step 2: Register all tables with $wpdb so column definitions are
		// resolved correctly when buildCreateSql() runs.
		Table_Registry::register();

		// Step 3: Install (or upgrade) every table by running dbDelta.
		// This creates missing tables and adds missing columns on existing ones.
		Table_Registry::install();

		// Step 4: Create the three custom roles (prayer_manager, prayer_warrior,
		// requester) and grant the six custom capabilities to the appropriate
		// roles. Both calls are idempotent — safe on re-activation.
		Roles::add_roles();
		Roles::add_caps();

		// Step 5: Store the plugin version for future upgrade comparisons.
		update_option( 'intercessor_version', INTERCESSOR_VERSION );

		// Step 6: Schedule the prayer-count notification cron event.
		Cron_Handler::schedule();

		// Step 7: Flush rewrite rules so REST API routes resolve on first load.
		flush_rewrite_rules();
	}
}
