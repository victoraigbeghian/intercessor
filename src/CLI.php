<?php
/**
 * Intercessor CLI Object.
 *
 * @package     Intercessor
 * @subpackage  CLI
 * @copyright   Copyright (c) 2022, Victor Aigbeghian
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.1.0
 */

namespace Intercessor;

use WP_CLI;
use Intercessor\Admin\Upgrades\Migrator;
use function intercessor_get_option;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

\WP_CLI::add_command( 'intercessor', '\Intercessor\CLI' );

/**
 * Intercessor CLI class.
 *
 * @since 1.1.0
 * @extends \WP_CLI_Command
 */
class CLI extends \WP_CLI_Command {
	/**
	 * Get INTERCESSOR details
	 *
	 * ## OPTIONS
	 *
	 * None. Returns basic info regarding your INTERCESSOR instance.
	 *
	 * ## EXAMPLES
	 *
	 * wp intercessor details
	 *
	 * @param array $args       Array of arguments.
	 * @param array $assoc_args Array of associated arguments.
	 */
	public function details( $args, $assoc_args ) {
		$prayer_page  = intercessor_get_option( 'form_page', '' );
		$history_page = intercessor_get_option( 'history_page', '' );
		$listing_page = intercessor_get_option( 'prayers_page', '' );

		WP_CLI::line( sprintf( __( 'You are running Intercessor version: %s', 'intercessor' ), INTERCESSOR_VERSION ) );
		WP_CLI::line( sprintf( __( 'AJAX is: %s', 'intercessor' ), ( intercessor_is_ajax_disabled() ? __( 'Disabled', 'intercessor' ) : __( 'Enabled', 'intercessor' ) ) ) );
		WP_CLI::line( "\n" . sprintf( __( 'Prayer request listing page is: %s', 'intercessor' ), ( ! intercessor_get_option( 'prayers_page', false ) ) ? __( 'Valid', 'intercessor' ) : __( 'Invalid', 'intercessor' ) ) );
		WP_CLI::line( sprintf( __( 'Prayer form URL is: %s', 'intercessor' ), ( ! empty( $prayer_page ) ? get_permalink( $prayer_page ) : __( 'Undefined', 'intercessor' ) ) ) );
		WP_CLI::line( sprintf( __( 'Prayer history page URL is: %s', 'intercessor' ), ( ! empty( $history_page ) ? get_permalink( $history_page ) : __( 'Undefined', 'intercessor' ) ) ) );
		WP_CLI::line( sprintf( __( 'Prayer listing URL is: %s', 'intercessor' ), ( ! empty( $listing_page ) ? get_permalink( $listing_page ) : __( 'Undefined', 'intercessor' ) ) ) );
		WP_CLI::line( sprintf( __( 'Intercessor slug is: %s', 'intercessor' ), ( defined( 'INTERCESSOR_SLUG' ) ? '/' . INTERCESSOR_SLUG : '/intercessor' ) ) );
	}


	/**
	 * Get the requesters currently on your Intercessor site. Can also be used to create requesters records
	 *
	 * ## OPTIONS
	 *
	 * --id=<requester_id>: A specific requester ID to retrieve.
	 * --email=<requester_email>: The email address of the requester to retrieve.
	 * --create=<number>: The number of arbitrary requesters to create. Leave as 1 or blank to create a requester with a
	 * speciific email.
	 *
	 * ## EXAMPLES
	 *
	 * wp intercessor requesters --id=103
	 * wp intercessor requesters --email=john@test.com
	 * wp intercessor requesters --create=1 --email=john@test.com
	 * wp intercessor requesters --create=1 --email=john@test.com --name="John Doe"
	 * wp intercessor requesters --create=1 --email=john@test.com --name="John Doe" user_id=1
	 * wp intercessor requesters --create=1000
	 */
	public function requesters( $args, $assoc_args ) {
		$requester_id = isset( $assoc_args ) && array_key_exists( 'id', $assoc_args ) ? absint( $assoc_args['id'] ) : false;
		$email        = isset( $assoc_args ) && array_key_exists( 'email', $assoc_args ) ? $assoc_args['email'] : false;
		$name         = isset( $assoc_args ) && array_key_exists( 'name', $assoc_args ) ? $assoc_args['name'] : null;
		$user_id      = isset( $assoc_args ) && array_key_exists( 'user_id', $assoc_args ) ? $assoc_args['user_id'] : null;
		$create       = isset( $assoc_args ) && array_key_exists( 'create', $assoc_args ) ? $assoc_args['create'] : false;
		$start        = time();

		if ( $create ) {
			$number = 1;

			// Create one or more requesters.
			if ( ! $email ) {

				// If no email is specified, look to see if we are generating arbitrary requester accounts.
				$number = is_numeric( $create ) ? absint( $create ) : 1;
			}

			for ( $i = 0; $i < $number; $i ++ ) {
				if ( ! $email ) {

					// Generate fake email.
					$email = 'requester-' . uniqid() . '@test.com';
				}

				$args = [
					'email'   => $email,
					'name'    => $name,
					'user_id' => $user_id,
				];

				$requester_id = \intercessor_add_item( 'requester', $args );

				if ( $requester_id ) {
					WP_CLI::line( sprintf( __( 'Requester %d created successfully', 'intercessor' ), $requester_id ) );
				} else {
					WP_CLI::error( __( 'Failed to create requester', 'intercessor' ) );
				}

				// Reset email to false, so it is generated on the next loop (if creating requesters).
				$email = false;

			}

			WP_CLI::line(
				WP_CLI::colorize(
					'%G' . sprintf(
						__( '%d requesters created in %d seconds', 'intercessor' ),
						$create,
						time() - $start
					) . '%N'
				)
			);
		} else {
			// Search for requesters.
			$search = false;

			// Checking if search is being done by id, email or user_id fields.
			if ( $requester_id || $email || ( 'null' !== $user_id ) ) {
				$search            = array();
				$requester_details = array();

				if ( $requester_id ) {
					$requester_details['id'] = $requester_id;
				} elseif ( $email ) {
					$requester_details['email'] = $email;
				} elseif ( null !== $user_id ) {
					$requester_details['user_id'] = $user_id;
				}

				$search['requester'] = $requester_details;
			}

			$requesters = \intercessor_get_items( 'requester', $search );

			if ( isset( $requesters['error'] ) ) {
				WP_CLI::error( $requesters['error'] );
			}

			// Bail if no requesters found.
			if ( empty( $requesters ) ) {
				WP_CLI::error( __( 'No requesters found', 'intercessor' ) );

				return;
			}

			foreach ( $requesters['requesters'] as $requester ) {
				WP_CLI::line( WP_CLI::colorize( '%G' . $requester['info']['email'] . '%N' ) );
				WP_CLI::line( sprintf( __( 'Requester User ID: %s', 'intercessor' ), $requester['info']['id'] ) );
				WP_CLI::line( sprintf( __( 'Username: %s', 'intercessor' ), $requester['info']['username'] ) );
				WP_CLI::line( sprintf( __( 'Display Name: %s', 'intercessor' ), $requester['info']['display_name'] ) );

				if ( array_key_exists( 'first_name', $requester ) ) {
					WP_CLI::line( sprintf( __( 'First Name: %s', 'intercessor' ), $requester['info']['first_name'] ) );
				}

				if ( array_key_exists( 'last_name', $requester ) ) {
					WP_CLI::line( sprintf( __( 'Last Name: %s', 'intercessor' ), $requester['info']['last_name'] ) );
				}

				WP_CLI::line( sprintf( __( 'Email: %s', 'intercessor' ), $requester['info']['email'] ) );

				WP_CLI::line( '' );
				WP_CLI::line( sprintf( __( 'Prayers: %s', 'intercessor' ), $requester['stats']['prayer_count'] ) );

				WP_CLI::line( '' );
			}

		}

	}


	/**
	 * Run the Intercessor v110 Migration via WP-CLI
	 *
	 * ## OPTIONS
	 *
	 * --force=<boolean>: If the routine should be run even if the upgrade routine has been run already
	 *
	 * @param mixed $args       Arguments.
	 * @param mixed $assoc_args Associated arguments.
	 */
	public function v110_upgrade( $args, $assoc_args ) {

		// Suspend the cache addition while we're migrating.
		wp_suspend_cache_addition( true );

		$this->migrate_prayed( $args, $assoc_args );
		$this->migrate_requesters( $args, $assoc_args );
		//$this->remove_legacy_data( $args, $assoc_args );

	}

	/**
	 * Migrate prayed counts to the custom tables
	 *
	 * ## OPTIONS
	 *
	 * --force=<boolean>: If the routine should be run even if the upgrade routine has been run already
	 *
	 * ## EXAMPLES
	 *
	 * wp intercessor migrate_prayed
	 * wp intercessor migrate_prayed --force
	 */
	public function migrate_prayed( $args, $assoc_args ) {
		global $wpdb;

		$force = isset( $assoc_args['force'] );

		$upgrade_completed = \intercessor_has_upgrade_completed( 'prayed_counts' );

		if ( ! $force && $upgrade_completed ) {
			WP_CLI::error( __( 'The prayed counts custom database migration has already been run. To do this anyway, use the --force argument.', 'intercessor' ) );
		}

		$sql     = "SELECT * FROM {$wpdb->ipr_prayermeta} WHERE meta_key = 'prayed_counts'";
		$results = $wpdb->get_results( $sql );
		$total   = count( $results );

		if ( ! empty( $total ) ) {

			$progress = new \cli\progress\Bar( 'Migrating Prayed counts', $total );

			foreach ( $results as $result ) {
				Migrator::prayed_counts( $result );

				$progress->tick();
			}

			$progress->finish();

			$args = [
				'count'   => true,
				'groupby' => 'prayer_id',
			];

			WP_CLI::line( __( 'Migration complete: Prayed Counts', 'intercessor' ) );
			$new_count = \intercessor_get_item_counts( 'prayed', $args );
			$old_count = $wpdb->get_col( "SELECT count(ipr_prayer_id) FROM $wpdb->ipr_prayermeta WHERE meta_key ='prayed_counts'", 0 );
			WP_CLI::line( __( 'Old Records: ', 'intercessor' ) . $old_count[0] );
			WP_CLI::line( __( 'New Records: ', 'intercessor' ) . $new_count );

			intercessor_update_db_version();
			intercessor_set_upgrade_complete( 'prayed_counts' );

		} else {

			WP_CLI::line( __( 'No prayed count records found.', 'intercessor' ) );
			intercessor_set_upgrade_complete( 'prayed_counts' );

		}
	}

	/**
	 * Migrate prayed counts to the custom tables
	 *
	 * ## OPTIONS
	 *
	 * --force=<boolean>: If the routine should be run even if the upgrade routine has been run already
	 *
	 * ## EXAMPLES
	 *
	 * wp intercessor migrate_requesters
	 * wp intercessor migrate_requesters --force
	 */
	public function migrate_requesters( $args, $assoc_args ) {
		global $wpdb;

		$force = isset( $assoc_args['force'] );

		$upgrade_completed = \intercessor_has_upgrade_completed( 'requesters' );

		if ( ! $force && $upgrade_completed ) {
			WP_CLI::error( __( 'The requester prayed counts database upgrade has already been run. To do this anyway, use the --force argument.', 'intercessor' ) );
		}

		$sql     = "SELECT * FROM {$wpdb->ipr_requesters} WHERE status = 'active'";
		$results = $wpdb->get_results( $sql );
		$total   = count( $results );

		if ( ! empty( $total ) ) {

			$progress = new \cli\progress\Bar( 'Upgrading requesters Prayed counts', $total );

			foreach ( $results as $result ) {
				Migrator::requesters( $result );

				$progress->tick();
			}

			$progress->finish();

			WP_CLI::line( __( 'Migration complete: Requesters Prayed Counts', 'intercessor' ) );
			intercessor_update_db_version();
			intercessor_set_upgrade_complete( 'requesters' );

		} else {

			WP_CLI::line( __( 'No requester prayed count records found.', 'intercessor' ) );
			intercessor_set_upgrade_complete( 'requesters' );

		}
	}


	/**
	 * Migrate prayed counts to the custom tables
	 *
	 * ## OPTIONS
	 *
	 * --force=<boolean>: If the routine should be run even if the upgrade routine has been run already
	 *
	 * ## EXAMPLES
	 *
	 * wp intercessor migrate_requesters
	 * wp intercessor migrate_requesters --force
	 */
	public function update_db_version( $args, $assoc_args ) {
		global $wpdb;

		$force = isset( $assoc_args['force'] );

		$upgrade_completed = \intercessor_has_upgrade_completed( 'requesters' );

		if ( ! $force && $upgrade_completed ) {
			WP_CLI::error( __( 'The requester prayed counts database upgrade has already been run. To do this anyway, use the --force argument.', 'intercessor' ) );
		}

		$sql     = "SELECT * FROM {$wpdb->ipr_requesters} WHERE status = 'active'";
		$results = $wpdb->get_results( $sql );
		$total   = count( $results );

		if ( ! empty( $total ) ) {

			$progress = new \cli\progress\Bar( 'Upgrading requesters Prayed counts', $total );

			foreach ( $results as $result ) {
				Migrator::requesters( $result );

				$progress->tick();
			}

			$progress->finish();

			WP_CLI::line( __( 'Migration complete: Requesters Prayed Counts', 'intercessor' ) );
			intercessor_update_db_version();
			intercessor_set_upgrade_complete( 'requesters' );

		} else {

			WP_CLI::line( __( 'No requester prayed count records found.', 'intercessor' ) );
			intercessor_set_upgrade_complete( 'requesters' );

		}
	}
}
