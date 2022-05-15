<?php
/**
 * Intercessor Object Items Functions
 *
 * @package     Intercessor
 * @subpackage  Functions/Items
 * @copyright   Copyright (c) 2020, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-2.0.php GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\Database\Queries\Prayer;
use Intercessor\Database\Queries\Note;
use Intercessor\Database\Queries\Requester;
use Intercessor\Database\Queries\Prayed_Counts;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'intercessor_object_types' ) ) {
    /**
     * Get the different object types.
     *
     * @param string $object Object type to retrieve.
     *
     * @since 1.0.0
     *
     * @return object
     */
    function intercessor_object_types( string $object = '' ) {
        $query = '';
        // Query for object.
        switch ( $object ) {
            // Prayer.
            case 'prayer':
                $query = new Prayer();
                break;

            // Requesters.
            case 'requester':
                $query = new Requester();
                break;

            // Notes.
            case 'note':
                $query = new Note();
                break;

            // Prayed counts.
            case 'prayed':
                $query = new Prayed_Counts();
                break;
        }

        // Return the queried object.
        return $query;
    }
}

if ( ! function_exists( 'intercessor_get_items' ) ) {
	/**
	 * Get all items.
	 *
	 * Retrieves an array of all available object items.
	 *
     * @param string $object Object to query items for.
	 * @param array  $args   Query arguments.
	 *
	 * @since 1.0.0
	 * @return mixed array if items exist, false otherwise.
	 */
	function intercessor_get_items( string $object = '', array $args = [] ) {
        $items  = intercessor_object_types( $object );
        $number = 20;
        if ( 'prayer' === $object ) {
            $number = intercessor_get_option( 'prayers_number', 20 );
        }

		// Parse arguments.
		$query = wp_parse_args(
			$args,
			[
                'number' => $number,
            ]
		);

        // Return items.
        return $items->query( $query );
	}
}

if ( ! function_exists( 'intercessor_get_item_by' ) ) {
    /**
     * Retrieve an item by a specific field
     *
     * @param string $object The object item to retrieve.
     * @param string $field  The field to retrieve the item with.
     * @param mixed  $value  The value for $field.
     *
     * @since 1.0.0
     *
     * @return mixed object|bool object or false if not found.
     */
    function intercessor_get_item_by( string $object = '', string $field = '', $value = '' ) {

        // Bail if values not specified.
        if ( empty( $field ) || empty( $value ) ) {
            return false;
        }

        // Get object type.
        $query = intercessor_object_types( $object );

        return $query->get_item_by( $field, $value );
    }
}

if ( ! function_exists( 'intercessor_count_items' ) ) {
    /**
     * Get total number of Prayers count.
     *
     * @param string $object The object to retrieve.
     * @param array  $args   Arguments array.
     *
     * @since 1.0.0
     *
     * @return int Items count.
     */
    function intercessor_count_items( string $object = '', array $args = [] ): int {

        // Parse args.
        $items = wp_parse_args(
            $args,
            [
                'count' => true,
            ]
        );
        $query = '';

        switch ( $object ) {
            // Prayer.
            case 'prayer':
                $query = new Prayer( $items );
                break;

            // Requester.
            case 'requester':
                $query = new Requester( $items );
                break;

            // Prayed Counts.
            case 'prayed':
                $query = new Prayed_Counts( $items );
                break;

            // Notes.
            case 'note':
                $query = new Note( $items );
                break;
        }

        // Return count(s).
        return absint( $query->found_items );
    }
}

if ( ! function_exists( 'intercessor_get_item_counts' ) ) {
    /**
     * Query for and return array of item counts, keyed by status.
     *
     * @param string $object Object type to retrieve counts for.
     * @param array  $args   Arguments array.
     *
     * @since 1.0.0
     *
     * @return array Object item counts keyed by status.
     */
    function intercessor_get_item_counts( string $object = '', array $args = [] ) {

        // Parse arguments.
        $items = wp_parse_args(
            $args,
            [
                'count'   => true,
                'groupby' => 'status',
            ]
        );

        $counts = '';

        // Query for count.
        switch ( $object ) {
            // Prayer.
            case 'prayer':
                $counts = new Prayer( $items );
                break;

            // Requester.
            case 'requester':
                $counts = new Requester( $items );
                break;

            // Notes.
            case 'note':
                $counts = new Note( $items );
                break;

            // Prayed.
            case 'prayed':
                $counts = new Prayed_Counts( $items );
                break;
        }

        // Format & return.
        return intercessor_get_counts_format( $counts, $items['groupby'] );
    }
}

if ( ! function_exists( 'intercessor_process_item') ) {
    /**
     * Process adding, updating or get item.
	 *
     * @param string $object  Object to query items for.
     * @param string $action  Action to process: 'get', 'update' or 'delete'.
     * @param int    $item_id Item ID.
	 * @param mixed  $data    Query arguments.
	 *
	 * @since 1.0.0
     */
    function intercessor_process_item( string $object, string $action, int $item_id, $data ) {
        // Bail if values are not specified.
        if ( empty( $object ) || empty( $action ) ) {
            return false;
        }

        // Query for object.
        $query = intercessor_object_types( $object );

        // Process the action specified.
        if ( 'update' === $action ) {
            return $query->update_item( $item_id, $data );
        } elseif ( 'get' === $action ) {
            return intercessor_get_item_by( $object, 'id', $item_id );
        } elseif ( 'delete' === $action ) {
            return $query->delete_item( $item_id );
        }
    }
}

if ( ! function_exists( 'intercessor_add_item' ) ) {
    /**
     * Add object item.
     *
     * @param string $object Type of object to create.
     * @param array  $data   Array of data to add or create item.
     *
     * @since 1.0.0
     *
     * @return int ID of newly created item.
     */
    function intercessor_add_item( string $object = '', array $data = [] ) {
        // Bail if no object specified.
        if ( empty( $object ) ) {
            return false;
        }

        // Query for object.
        $query = intercessor_object_types( $object );

        // Adds the item and return the item ID.
        return $query->add_item( $data );
    }
}

/** Meta **********************************************************************/
if ( ! function_exists( 'intercessor_add_item_meta' ) ) {
    /**
     * Add meta data field to a item.
     *
     * @param string $item       Item Object.
     * @param int    $item_id    Item ID.
     * @param string $meta_key   Meta data name.
     * @param mixed  $meta_value Meta data value. Must be serializable if non-scalar.
     * @param bool   $unique     Optional. Whether the same key should not be added.
     *                           Default false.
     *
     * @since 1.0.0
     *@return int|false Meta ID on success, false on failure.
     */
	function intercessor_add_item_meta( string $item, int $item_id, string $meta_key, $meta_value, bool $unique ) {
        // Query for object item.
        $query = intercessor_object_types( $item );

        // Try to add item meta.
		return $query->add_item_meta( $item_id, $meta_key, $meta_value, $unique );
	}
}

if ( ! function_exists( 'intercessor_delete_item_meta' ) ) {
	/**
	 * Remove meta data matching criteria from a item.
	 *
	 * You can match based on the key, or key and value. Removing based on key and
	 * value, will keep from removing duplicate meta data with the same key. It also
	 * allows removing all meta data matching key, if needed.
	 *
     * @param string $item       The item to delete meta from.
	 * @param int    $item_id    Prayer ID.
	 * @param string $meta_key   Meta data name.
	 * @param mixed  $meta_value Optional. Meta data value. Must be serializable if
	 *                           non-scalar. Default empty.
	 *
	 * @since 1.0.0
	 * @return bool True on success, false on failure.
	 */
	function intercessor_delete_item_meta( string $item, int $item_id, string $meta_key, $meta_value = '', $delete_all = false ): bool {
        // Query for object item.
        $query = intercessor_object_types( $item );

        // Delete the meta.
		return $query->delete_item_meta( $item_id, $meta_key, $meta_value, $delete_all );
	}
}

if ( ! function_exists( 'intercessor_get_item_meta' ) ) {
	/**
	 * Retrieve item meta field for a item.
	 *
     * @param string $item     The item to query for (rquired).
	 * @param int    $item_id  Item ID.
	 * @param string $meta_key Optional. The meta key to retrieve. By default, returns
	 *                           data for all keys. Default empty.
	 * @param bool   $single  Optional, default is false.
	 *                        If true, return only the first value of the specified meta_key.
	 *                        This parameter has no effect if meta_key is not specified.
	 *
	 * @since 1.0.0
	 * @return mixed Will be an array if $single is false. Will be value of meta data
	 *               field if $single is true.
	 */
	function intercessor_get_item_meta( string $item, int $item_id, string $meta_key, bool $single ) {

		// Query for object item.
        $query = intercessor_object_types( $item );

		return $query->get_item_meta( $item_id, $meta_key, $single );
	}
}

if ( ! function_exists( 'intercessor_update_item_meta' ) ) {
	/**
	 * Update item meta field based on item ID.
	 *
	 * Use the $prev_value parameter to differentiate between meta fields with the
	 * same key and item ID.
	 *
	 * If the meta field for the item does not exist, it will be added.
	 *
     * @param string $item       Type of object meta item to update.
	 * @param int    $item_id    Item ID.
	 * @param string $meta_key   Meta data key.
	 * @param mixed  $meta_value Meta data value. Must be serializable if non-scalar.
	 * @param mixed  $prev_value Optional. Previous value to check before removing.
	 *                           Default empty.
	 *
	 * @since 1.0.0
	 * @return int|bool Meta ID if the key didn't exist, true on successful update,
	 *                  false on failure.
	 */
	function intercessor_update_item_meta( string $item, int $item_id, string $meta_key, $meta_value, $prev_value = '' ) {
        // Query for object item.
        $query = intercessor_object_types( $item );

		return $query->update_item_meta( $item_id, $meta_key, $meta_value, $prev_value );
	}
}

if ( ! function_exists( 'intercessor_delete_item_meta_by_key' ) ) {
	/**
	 * Delete everything from item meta matching meta key.
	 *
	 * @param string $item     Item to query.
	 * @param string $meta_key Key to search for when deleting.
	 *
	 * @since 1.0.0
	 *
     * @return bool Whether the item meta key was deleted from the database.
	 */
	function intercessor_delete_item_meta_by_key( string $item, string $meta_key ): bool {
        // Setup database object.
		$query = '';

        switch ( $item ) {
            case 'prayer':
                $query = 'ipr_prayer';
                break;

            case 'requester':
                $query = 'ipr_requester';
                break;
        }

		return delete_metadata( $query, null, $meta_key, '' );
	}
}
