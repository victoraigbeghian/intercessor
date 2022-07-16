<?php
/**
 * Intercessor Data Migrator.
 *
 * @package     Intercessor
 * @subpackage  Admin/Upgrades
 * @copyright   Copyright (c) 2021, Victor Aigbeghian
 * @license     http://opensource.org/licenses/gpl-1.1.0.php GNU Public License
 * @since       1.1.0
 */
namespace Intercessor\Admin\Upgrades;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Data_Migrator Class.
 *
 * This class holds all the logic for migrating data to custom tables as part
 * of Intercessor 1.1.0.
 *
 * @since 1.1.0
 */
class Migrator {

    /**
     * Prayed counts.
     *
     * @since 1.1.0
     *
     * @param object $data Data to migrate.
     */
    public static function prayed_counts( $data = null ) {

        // Bail if no data passed.
        if ( ! $data ) {
            return;
        }

        // Set up variables.
        $prayer_id   = absint( $data->ipr_prayer_id );
		$meta_key    = esc_attr( $data->meta_key );
		$meta_value  = esc_attr( $data->meta_value );
        $prayed_data = [
            'prayer_id'    => $prayer_id,
            'prayed_for'   => 1,
            'date_created' => $meta_value,
        ];

		// Add prayed for count to the database.
        $prayed_for = intercessor_add_item( 'prayed',  $prayed_data );

		// Delete old meta table values.
	    if ( $prayed_for ) {
			\intercessor_delete_item_meta( 'prayer', $prayer_id, $meta_key, $meta_value, false );
	    }
    }

    /**
     * Requesters.
     *
     * @since 1.1.0
     *
     * @param object $data Data to migrate.
     */
    public static function requesters( $data = null ) {

        // Bail if no data passed.
        if ( ! $data ) {
            return;
        }

        // Set up variables.
        $requester_id = absint( $data->requester_id );
        $old_counts   = absint( $data->prayer_count );
        $prayer_count = \intercessor_get_requester_prayers( $requester_id, true );

        // Update prayer count if database varies from actual count.
        if ( $old_counts !== $prayer_count ) {
            $update_data  = [
                'prayer_counts' => $prayer_count,
            ];

            intercessor_process_item( 'requester', 'update', $requester_id, $update_data );
        }
    }

    /**
     * Prayer meta.
     *
     * @param object $data Data to migrate.
     *
     * @since 1.1.0
     * @access public
     * @return void
     */
    public static function prayer_meta( $data = null )
    {
        // Bail if no data passed.
        if ( ! $data ) {
            return;
        }
	/*
        // Set up variables.
        $prayer_id  = absint( $data->ipr_prayer_id );
        $counts     = \intercessor_get_prayed_requests( $prayer_id );
        $new_counts = \intercessor_get_prayed_for_counts( $prayer_id );

        // Delete prayed counts from prayer meta database, if already migrated.
        if ( $counts === $new_counts ) {
            \intercessor_delete_item_meta_by_key( 'prayer', 'prayed_counts' );
        }
	*/
    }
}

