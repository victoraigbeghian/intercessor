<?php
/**
 * Prayed Counts Functions
 *
 * @package     Intercessor
 * @subpackage  Functions/Prayed
 * @copyright   Copyright (c) 2021, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       1.0.0
 */

use Intercessor\Database\Queries\Prayed_Counts;
use Intercessor\Requester;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'intercessor_prayed_date_query' ) ) {
	/**
	 * Query prayed requests with date
	 *
	 * @return int|void
	 * @since 1.0.0
	 */
    function intercessor_prayed_date_query() {
        $query = [
            'date_created_query' => [
                'after' => 'last month', // week, day, month.
            ],
        ];

        $prayed_query = [
            'fields' => 'prayer_id',
            'count'  => true,
            'date_created_query' => [
                'after' => 'last month', // week, day, month.
            ],
        ];

    //    $prayed = new Prayed_Counts( $prayed_query );
        $prayed = new Prayed_Counts();
        if ( $prayed ) {
            foreach ( $prayed as $prayed_for ) {
                $prayer_id = $prayed_for->prayer_id;
                $total     = $prayer_id;

                return intval( $total );
            }
        }
    }
}

if ( ! function_exists( 'intercessor_count_prayed' ) ) {
    /**
     * Count prayed requests
     *
     * @param array $args Arguments to parse.
     *
     * @return int Number of found prayed counts.
     */
    function intercessor_count_prayed( array $args = [] ) : int {
        // Setup defaults.
	    $set_date = intercessor_get_notify_period();
        $defaults = wp_parse_args(
            $args,
            [
                'count'              => true,
                'date_created_query' => [
                    'after'          => $set_date, // week, day, month.
                ],
            ]
        );

        $prayed = new Prayed_Counts( $defaults );

        //return the counts.
        return absint( $prayed->found_items );
	}
}

if ( ! function_exists( 'intercessor_get_prayed_for_counts' ) ) {
    /**
     * Get prayed for counts for a prayer ID.
     *
     * @param int $prayer_id Prayer ID.
     *
     * @since 1.0.0
     */
    function intercessor_get_prayed_for_counts( int $prayer_id ) {
        // Bail if no prayer ID supplied.
        if ( empty( $prayer_id ) ) {
            return false;
        }

        // Setup prayed for args.
        $args = [
            'prayer_id' => $prayer_id,
        ];

        // Get prayed for counts.
        $prayed     = intercessor_get_items( 'prayed', $args );
        $key        = esc_attr( 'prayed_for' );
        $prayed_for = array_sum( array_column( $prayed, $key ) );

        // Return values of prayed counts.
        return apply_filters( 'intercessor_get_prayed_for_counts', $prayed_for );
    }
}

if ( ! function_exists( 'intercessor_get_prayed_for_counts_range' ) ) {
	/**
	 * Get prayed for counts for a prayer ID with date range.
	 *
	 * @param int $prayer_id Prayer ID.
	 *
	 * @since 1.0.0
	 */
	function intercessor_get_prayed_for_counts_range( int $prayer_id ) {
		// Bail if no prayer ID supplied.
		if ( empty( $prayer_id ) ) {
			return false;
		}

		// Setup prayed for args.
		$set_date = intercessor_get_notify_period();
		$args     = [
			'prayer_id' => $prayer_id,
			'date_created_query' => [
				'after'          => $set_date, // week, day, month.
			],
		];

		// Get prayed for counts.
		$prayed     = intercessor_get_items( 'prayed', $args );
		$key        = esc_attr( 'prayed_for' );
		$prayed_for = array_sum( array_column( $prayed, $key ) );

		// Return values of prayed counts.
		return apply_filters( 'intercessor_get_prayed_for_counts_range', $prayed_for );
	}
}

if ( ! function_exists( 'intercessor_get_notify_period' ) ) {
	/**
     * Get notification period.
     *
     * @since 1.1.0
     *
     * @return string
     */
    function intercessor_get_notify_period() : string {
		// Configure start date.
		$default_date = intercessor_get_option( 'notify_period', 'weekly' );
		if ( 'daily' === $default_date ) {
			$date_value = 'yesterday';
		} elseif ( 'monthly' === $default_date ) {
			$date_value = 'last month';
		} else {
			$date_value = 'last week';
		}

		// Return the filtered value.
		return apply_filters( 'intercessor_notify_requester_period', $date_value );
	}
}

if ( ! function_exists( 'intercessor_remove_prayed_counts_meta' ) ) {
    /**
     * Remove prayed counts meta
     *
     * Deletes prayed counts meta from the prayer meta database if not already removed.
     *
     * @since 1.1.1
     * @return 
     */
    function intercessor_remove_prayed_counts_meta() {
        $found = intercessor_get_item_meta( 'prayer', false, 'prayed_counts', false );

        if ( ! empty( $found ) ) {
           return  intercessor_delete_item_meta_by_key( 'prayer', 'prayed_counts' );
        }
    }
}
