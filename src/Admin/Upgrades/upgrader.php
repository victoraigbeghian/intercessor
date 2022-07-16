<?php
/**
 * Intercessor Upgrades Object.
 *
 * @package     Intercessor
 * @subpackage  CLI
 * @copyright   Copyright (c) 2022, Victor Aigbeghian
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.1.0
 */

namespace Intercessor\Admin\Upgrades;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Output the admin upgrades page
 *
 * @since 1.1.0
*/
function intercesssor_upgrades_page() {
	global $wpdb;

	// Upgrade action & steps.
	$action = isset( $_GET['intercessor_upgrade'] ) ? sanitize_key( $_GET['intercessor_upgrade'] ) : '';
	$step   = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
	$number = isset( $_GET['number'] ) ? absint( $_GET['number'] ) : 20;

	// Count posts for 2.x migration
	if ( 'v_110' === $action ) {
        $requesters = new \Intercessor\Admin\Requesters\Emails();
		$total      = $requesters->total;
		$steps      = $requesters->steps;

	// All others are a single step.
	} else {
		$steps = 1;
	} ?>

	<div class="wrap">
		<h1><?php esc_html_e( 'Intercessor Upgrades', 'intercessor' ); ?></h1>
		<hr class="wp-header-end">

		<div id="intercessor-upgrade-status">
			<p><?php esc_html_e( 'The upgrade process has begun. Please be patient. You will be redirected when it is finished.', 'intercessor' ); ?></p>

			<?php if ( ! empty( $total ) ) : ?>
				<p><strong><?php printf( __( 'Step %d of %d...', 'intercessor' ), $step, $steps ); ?></strong></p>
			<?php endif; ?>
		</div>

		<script type="text/javascript"><?php
			$step++;

			// Redirect to the main Intercessor page.
			if ( $step > $steps ) {
				$url = intercessor_get_admin_base_url();

				// Mark as complete before redirecting.
				intercessor_upgrade_done( $action );

			// Redirect to the next upgrade step.
			} else {
				$url = intercessor_upgrades_page_url( array(
					'upgrade' => $action,
					'step'    => $step,
					'total'   => $total,
					'steps'   => $steps
				) );
			} ?>

			setTimeout(function() {
				document.location.href = '<?php echo $url; ?>';
			}, 250);
		</script>
	</div>

	<?php

	// Process the step
	process();
}

/**
 * Get the upgrade page URL, complete with arguments & non
 *
 * @since 1.1.0
 *
 * @param array $args
 *
 * @return string
 */
function intercessor_upgrades_page_url( $args = [] ) {

	// Parse arguments.
    $pages = [
        'page'    => 'intercessor-upgrades',
		'upgrade' => '',
		'step'    => null,
		'total'   => null,
		'steps'   => null
    ];
	$r    = \wp_parse_args( $args, $pages );

	// Nonce url.
	$url = \wp_nonce_url(
        add_query_arg(
            $r,
            admin_url( 'index.php' )
        ),
        'intercessor-upgrade-nonce'
    );

	// Unescape
	return str_replace( '&amp;', '&', $url );
}

/**
 * Display Upgrade Notices
 *
 * @since 1.1.0
 */
function notices() {

	// Avoid showing notices on the upgrades page
	if ( is_upgrades_page() || doing_upgrade() ) {
		return;
	}

	// 2.x - Fresh install, not from 1.x
	if ( ! get_option( 'sc_version' ) && ! did_upgrade( '20_fresh' ) ) {
		do_fresh_install();

	// 2.x - Database migration
	} elseif ( ! did_upgrade( '20_migration' ) ) {
		printf(
			'<div class="updated"><p>' . __( 'Sugar Calendar needs to upgrade the events database. Click <a href="%s">here</a> to start.', 'intercessor' ) . '</p></div>',
			intercessor_upgrades_page_url( array( 'upgrade' => '20_migration' ) )
		);

	// 2.0.6 - Flush rewrite rules
	} else if ( ! did_upgrade( '206_migration' ) ) {
		printf(
			'<div class="updated"><p>' . __( 'Sugar Calendar needs to perform an upgrade. Click <a href="%s">here</a> to start.', 'intercessor' ) . '</p></div>',
			intercessor_upgrades_page_url( array( 'upgrade' => '206_migration' ) )
		);
	}
}

/**
 * Detects and processes upgrades
 *
 * @since  1.1.0
 *
 * @return bool
 */
function process() {

	// Bail if not in admin
	if ( ! is_admin() ) {
		return;
	}

	// Bail if no upgrade action
	if ( ! doing_upgrade() ) {
		return;
	}

	// Bail if nonce check fails
	if ( ! verify_nonce() ) {
		return;
	}

	// Bail if doing ajax
	if ( wp_doing_ajax() ) {
		return;
	}

	// Bail if current user cannot manage options
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Sanitize the step
	$step = current_upgrade();
	$func = "Sugar_Calendar\\Admin\\Upgrades\\do_{$step}";

	// Process the step
	if ( function_exists( $func ) ) {
		call_user_func( $func );
	}
}

/**
 * Adds an upgrade action to the completed upgrades array
 *
 * @since  1.1.0
 *
 * @param string $action New upgrade action.
 *
 * @return bool True if upgrades added, otherwise false.
 */
function intercessor_upgrade_done( $action = '' ) {

	// Bail if no new upgrade action.
	if ( empty( $action ) ) {
		return false;
	}

	// Get completed upgrades.
	$existing = intercessor_completed_upgrades();

	// Cast upgrade to array.
	$upgrades = (array) $action;

	// Merge new upgrades with existing ones.
	$completed = array_merge( $existing, $upgrades );

	// Remove any duplicates and blanks.
	$upgrades_done = array_unique( array_values( $completed ) );

	// Return the results of upgrading the option.
	return update_option( 'intercessor_completed_upgrades', $upgrades_done );
}

/**
 * Check if the upgrade routine has been run for a specific action
 *
 * @since  1.1.0
 * @param  string $upgrade_action The upgrade action to check completion for
 *
 * @return bool                   If the action has been added to the completed actions array
 */
function did_upgrade( $upgrade_action = '' ) {

	// Bail if no new upgrade action to set
	if ( empty( $upgrade_action ) ) {
		return false;
	}

	$upgrades_done = intercessor_completed_upgrades();

	return in_array( $upgrade_action, $upgrades_done, true );
}

/**
 * Gets the array of completed upgrade actions
 *
 * @since  1.1.0
 *
 * @return array The array of completed upgrades
 */
function intercessor_completed_upgrades() {

	// Get option
	$upgrades_done = get_option( 'sc_completed_upgrades', [] );

	// Make sure empty value is an array
	if ( empty( $upgrades_done ) ) {
		$upgrades_done = [];
	}

	// Cast and return
	return (array) $upgrades_done;
}

/**
 * Is the current admin area page our "upgrades" page?
 *
 * @since 2.0.8
 *
 * @return hoolean
 */
function is_upgrades_page() {
	return isset( $_GET['page'] ) && ( 'intercessor-upgrades' === $_GET['page'] );
}

/**
 * Get the current upgrade
 *
 * @since 2.0.8
 *
 * @return string
 */
function current_upgrade() {
	return is_upgrades_page() && doing_upgrade()
		? sanitize_key( $_GET['upgrade'] )
		: false;
}

/**
 * Is an upgrade being performed?
 *
 * @since 2.0.8
 *
 * @return mixed
 */
function doing_upgrade() {
	return ! empty( $_GET['upgrade'] );
}

/**
 * Verify the upgrade nonce.
 *
 * @since 2.0.8
 *
 * @return bool
 */
function verify_nonce() {
	return ! empty( $_REQUEST['_wpnonce'] )
		? wp_verify_nonce( $_REQUEST['_wpnonce'], 'intercessor-upgrade-nonce' )
		: false;
}

/**
 * Things that happen on a fresh installation.
 *
 * Only fires once (on initial activation) via notices() function.
 *
 * @since 2.0.8
 */
function do_fresh_install() {

	// Remove the 1.x option.
	delete_option( 'sc_version' );

	// Mark all upgrades as complete so they are not asked for again.
	intercessor_upgrade_done( array(
		'20_fresh',
		'20_migration',
		'206_migration'
	) );

	// Make sure post types & taxonomies work
	flush_rewrite_rules( true );
}

/**
 * Upgrades for events data for version 2.0
 *
 * @since 1.1.0
 *
 * @global mixed $sc_upgrade_meta_skip
 */
function do_20_migration() {
	global $wpdb, $sc_upgrade_meta_skip;

	// Set the upgrade global
	$sc_upgrade_meta_skip = true;

	// Get the old abort, and set it
	$old_abort = ignore_user_abort( true );

	$step   = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
	$number = 20;
	$offset = ( $step === 1 )
		? 0
		: ( $step - 1 ) * $number;

	// Get the post type
	$post_type = sugar_calendar_get_event_post_type_id();

	// Events
	$posts_sql = "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status != %s ORDER BY %s ASC LIMIT %d, %d";
	$posts     = $wpdb->get_results( $wpdb->prepare( $posts_sql, $post_type, 'auto-draft', 'ID', $offset, $number ) );

	// Loop through posts to migrate
	if ( ! empty( $posts ) && ! is_wp_error( $posts ) ) {

		// Migrate events
		foreach ( $posts as $post ) {

			// Query for duplicate
			$duplicate = sugar_calendar_get_event_by_object( $post->ID, 'post' );

			// Skip if already exists
			if ( $duplicate->exists() ) {
				continue;
			}

			// Get start & end
			$start   = (int) get_post_meta( $post->ID, 'sc_event_date_time',     true );
			$end     = (int) get_post_meta( $post->ID, 'sc_event_end_date_time', true );
			$all_day = false;

			// Format the start & end
			$start_date_time = gmdate( 'Y-m-d H:i:s', $start );
			$end_date_time   = gmdate( 'Y-m-d H:i:s', $end   );

			// Recurring
			$recur_type = get_post_meta( $post->ID, 'sc_event_recurring', true );

			// Do not save "none" string value
			$recur_type = ! empty( $recur_type ) && ( 'none' !== $recur_type )
				? sanitize_key( $recur_type )
				: '';

			// Format recurrence end
			$recur_end = (int) get_post_meta( $post->ID, 'sc_recur_until', true );
			$recur_end = ! empty( $recur_end )
				? gmdate( 'Y-m-d H:i:s', $recur_end )
				: '';

			// Add the event
			sugar_calendar_add_event( array(
				'object_id'      => $post->ID,
				'object_type'    => 'post',
				'object_subtype' => $post->post_type,
				'title'          => $post->post_title,
				'content'        => $post->post_content,
				'status'         => $post->post_status,
				'start'          => $start_date_time,
				'end'            => $end_date_time,
				'all_day'        => $all_day,
				'recurrence'     => $recur_type,
				'recurrence_end' => $recur_end,
				'date_created'   => $post->post_date_gmt,
				'date_modified'  => $post->post_modified_gmt,

				/**
				 * Add event meta keys and values below.
				 *
				 * - Largely from add-ons
				 * - Empty values are not saved
				 * - Duplicated keys will overwrite
				 * - Repeat get_post_meta() calls are cached
				 */
				// From Google Maps Add-on
				'location'       => get_post_meta( $post->ID, 'sc_map_address', true )
			) );
		}
	}

	// Reset abort
	ignore_user_abort( $old_abort );

	// Unset the meta-skip global
	unset( $sc_upgrade_meta_skip );
}

/**
 * Upgrades rewrite rules for version 2.0.6
 *
 * @since 2.0.6
 */
function do_206_migration() {
	flush_rewrite_rules( true );
}