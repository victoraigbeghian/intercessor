<?php
/**
 * Requester Functions
 *
 * @package     IPR
 * @subpackage  Functions/Requester
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       0.9.5
*/

// Exit if accessed directly.

use Intercessor\Database\Queries\Requester as QueriesRequester;
use Intercessor\Requester;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'intercessor_get_requester' ) ) {
    /**
     * Get a requester by ID
     *
     * @param int $requester_id Requester ID.
     *
     * @return \Intercessor\Requester
     * @since 0.9.5
     */
    function intercessor_get_requester( int $requester_id = 0 ) {
        return intercessor_process_item('requester', 'get', $requester_id, false );
    }
}

if ( ! function_exists( 'intercessor_get_requester_by' ) ) {
	/**
	 * Get a requester by a specific field value
	 *
	 * @param string $field Field.
	 * @param string $value Value.
	 *
	 * @since 0.9.5
	 *
	 * @uses  \intercessor_get_item_by()
	 */
	function intercessor_get_requester_by( string $field = '', string $value = '' ) {
		// Return requester.
		return intercessor_get_item_by( 'requester', $field, $value );
	}
}

/**
 * Count number of prayers of a requester
 *
 * Returns total number of prayers a requester has made
 *
 * @access      public
 * @since       0.9.5
 * @param       mixed $user - ID or email
 * @return      int - the total number of prayers
 */
function intercessor_count_prayers_of_requester( $user = null ) : int {
	if ( empty( $user ) ) {
		$user = get_current_user_id();
	}

	$stats = ! empty( $user ) ? intercessor_get_prayer_stats_by_user( $user ) : false;

	return $stats['prayers'] ?? 0;
}

if ( ! function_exists( 'intercessor_get_prayer_requester_id' ) ) {
	/**
	 * Get the requester ID associated with a prayer
	 *
	 * @param int $prayer_id Prayer ID
	 *
	 * @return int $requester_id Requester ID
	 * @since 0.9.5
	 */
	function intercessor_get_prayer_requester_id( int $prayer_id = null ): int
    {
		$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
		return $prayer->requester_id;
	}
}

/**
 * Updates the email address of a requester record when the email on a user is updated
 *
 * @access  public
 *
 * @param int     $user_id       User ID.
 * @param WP_User $old_user_data Object containing user's data prior to update.
 *
 * @return void
 * @since   0.9.5
 */
function intercessor_update_requester_email_on_user_update( int $user_id, WP_User $old_user_data ) {
	$user = get_userdata( $user_id );

	// Bail if the email address didn't actually change just now.
	if ( empty( $user ) || $user->user_email === $old_user_data->user_email ) {
		return;
	}

	$requester = intercessor_get_requester_by( 'user_id', $user_id );

	if ( empty( $requester ) || $user->user_email === $requester->email ) {
		return;
	}

	// Bail if we have another requester with this email address already.
	if ( intercessor_get_requester_by( 'email', $user->user_email ) ) {
		return;
	}

	$success = intercessor_process_item( 'requester', 'update', $requester->id, [ 'email' => $user->user_email ] );

	if ( ! $success ) {
		return;
	}

	/**
	 * Fires after the requester has been successfully updated.
	 *
	 * @param WP_User               $user
	 * @param Intercessor/Requester $requester
	 */
	do_action( 'intercessor_update_requester_email_on_user_update', $user, $requester );
}

if ( ! function_exists( 'intercessor_get_requester_counts' ) ) {

	/**
	 * Query for and return array of requester counts, keyed by status.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments. See `Intercessor\Database\Queries\Requester` for
	 *                    accepted arguments.
	 * @return array Requester counts keyed by status.
	 */
	function intercessor_get_requester_counts( $args = [] ) {

		// Parse arguments.
		$r = wp_parse_args(
			$args,
			[
				'count'   => true,
				'groupby' => 'status',
			]
		);

		// Query for count.
		$counts = new Intercessor\Database\Queries\Requester( $r );

		// Format & return.
		return intercessor_get_counts_format( $counts, $r['groupby'] );
	}
}

if ( ! function_exists( 'intercessor_get_requester_prayers' ) ) {
	/**
	 * Get prayer or prayer counts of requester.
	 *
	 * @param int  $requester_id Requester ID.
	 * @param bool $counts       Whether to retrieve prayer counts.
	 *
	 * @since 1.1.0
	 * 
	 * @return array|int Array or number of prayers of requester.
	 */
	function intercessor_get_requester_prayers( int $requester_id, bool $counts ) {
		// Bail if no requester ID supplied.
		if ( empty( $requester_id ) ) {
			return false;
		}

		// Setup arguments to get prayers.
		$prayer_args = [
			'requester_id' => $requester_id,
		];

		// Get prayer(s) for requester.
		$prayers = intercessor_get_items( 'prayer', $prayer_args );

		// Get counts of prayer.
		if ( true === $counts ) {
			$query = new \Intercessor\Database\Queries\Prayer( $prayer_args );
			$found = absint( $query->found_items );
		} else {
			$found = $prayers;
		}
		
		// Return array or number of prayers.
		return apply_filters( 'intercessor_get_requester_prayers', $found );
	}
}

if ( ! function_exists( 'intercessor_get_requester_from_prayer' ) ) {
	/**
	 * Get a requester attached to a prayer ID.
	 *
	 * @param int $prayer_id The prayer ID.
	 *
	 * @since 1.1.0
	 * @return object /Intercessor/Requester 
	 */
	function intercessor_get_requester_from_prayer( int $prayer_id ) {
		// Retrieve prayer.
		$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );

		// Get requester ID and requester.
		$requester_id = $prayer->requester_id;
		$requester    = new Intercessor\Requester( $requester_id, false );

		// Return requester object.
		return apply_filters( 'intercessor_get_requester_from_prayer', $requester );
	}
}
