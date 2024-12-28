<?php
/**
 * Requester Object
 *
 * @package     Intercessor
 * @subpackage  Classes/Requester
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       0.9.5
 */

namespace Intercessor;

use function intercessor_process_item;
use function intercessor_get_item_meta;
use function intercessor_delete_item_meta_by_key;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Requester Class
 *
 * @since 0.9.5
 *
 * @property int $id
 * @property int $prayer_count
 * @property string $email
 * @property string $emails
 * @property string $name
 * @property string $status
 * @property string $date_created
 * @property int $user_id
 * @property string $notes
 */
class Requester extends \Intercessor\Database\Rows\Requester {

	/**
	 * The requester ID
	 *
	 * @since 0.9.5
	 * @var int
	 */
	public $id = 0;

	/**
	 * The requester's prayer count
	 *
	 * @since 0.9.5
	 * @var int
	 */
	public $prayer_count = 0;

	/**
	 * The requester's primary email
	 *
	 * @since 0.9.5
	 * @var string
	 */
	public $email;

	/**
	 * The requester's emails
	 *
	 * @since 0.9.5
	 * @var string
	 */
	public $emails;

	/**
	 * The requester's name
	 *
	 * @since 0.9.5
	 * @var string
	 */
	public $name;

    /**
     * The requester's status
     *
     * @since 1.0.0
     * @var string
     */
    public $status;

	/**
	 * The requester's creation date
	 *
	 * @since 0.9.5
	 * @var string
	 */
	public $date_created;

	/**
	 * The requester's modification's date
	 *
	 * @since 0.9.5
	 * @var string
	 */
	public $date_modified;
	/**
	 * The prayer IDs associated with the requester
	 *
	 * @since  0.9.5
	 * @var string
	 */
	public $prayer_ids;

	/**
	 * The user ID associated with the requester
	 *
	 * @since  0.9.5
	 * @var int
	 */
	public $user_id;

	/**
	 * Requester Notes
	 *
	 * @since  0.9.5
	 * @var string
	 */
	protected $notes;

    /**
     * Get things going
     *
     * @param mixed $_id_or_email Get by requester ID or email.
     * @param bool  $by_user_id   Get requester by user ID.
     *
     * @since 0.9.5
     * @return mixed|void
     */
	public function __construct( $_id_or_email = false, $by_user_id = false ) {
		// Try to get requester by fields or values supplied.
		if ( false === $_id_or_email || ( is_numeric( $_id_or_email ) && absint( $_id_or_email ) !== (int) $_id_or_email ) ) {
			return false;
		}

		$by_user_id = is_bool( $by_user_id ) ? $by_user_id : false;

		if ( is_object( $_id_or_email ) ) {
			$requester = $_id_or_email;
		} else {
			if ( is_numeric( $_id_or_email ) ) {
				$field = $by_user_id ? 'user_id' : 'id';
			} else {
				$field = 'email';
			}

			$requester = \intercessor_get_item_by( 'requester', $field, $_id_or_email );
		}

		// Bail if requester not found.
		if ( empty( $requester ) || ! is_object( $requester ) ) {
			return false;
		}

		// Setup requester.
		$this->setup_requester( $requester );
	}

	/**
	 * Given the requester data, let's set the variables
	 *
	 * @since  0.9.5
	 * @param  object $requester The Requester Object.
	 *
	 * @return bool If the setup was successful or not
	 */
	private function setup_requester( $requester ) {
		// Bail if not a requester object.
		if ( ! is_object( $requester ) ) {
			return false;
		}

		foreach ( $requester as $key => $value ) {

			switch ( $key ) {
				case 'prayer_count':
					$this->$key = absint( $value );
					break;
				default:
					$this->$key = $value;
					break;
			}
		}

		$this->emails   = (array) \intercessor_delete_item_meta( 'requester', $this->id, 'additional_email', false );
		$this->emails[] = $this->email;

		// Requester ID and email are the^ only things that are necessary, make sure they exist.
		if ( ! empty( $this->id ) && ! empty( $this->email ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Magic __get function to dispatch a call to retrieve a private property.
	 *
	 * @since 0.9.5
	 * @param string $key Key to use.
	 *
	 * @return mixed|\WP_Error
	 */
	public function __get( $key = '' ) {
		switch ( $key ) {
			case 'prayer_ids':
				$prayer_ids = $this->get_prayer_ids();
				$prayer_ids = implode( ',', $prayer_ids );
				return $prayer_ids;
			default:
				return isset( $this->{$key} )
					? $this->{$key}
					: \intercessor_get_item_meta( 'requester', $this->id, $key );

		}

	}

	/**
	 * Magic __set method to dispatch a call to update a protected property.
	 *
	 * @since 0.9.5
	 *
	 * @param string $key   Property name.
	 * @param mixed  $value Property value.
	 *
	 * @return mixed|void Return value of setter being dispatched to.
	 */
	public function __set( $key, $value ) {
		$key = sanitize_key( $key );

		// Only real properties can be saved.
		$keys = array_keys( get_class_vars( get_called_class() ) );

		if ( ! in_array( $key, $keys, true ) ) {
			return false;
		}

		// Dispatch to setter method if value needs to be sanitized.
		if ( method_exists( $this, 'set_' . $key ) ) {
			return call_user_func( [ $this, 'set_' . $key ], $key, $value );
		} else {
			$this->{$key} = $value;
		}
	}

	/**
	 * Creates a requester
	 *
	 * @since  0.9.5
	 * @param  array $data Array of attributes for a requester.
	 * @return mixed        False if not a valid creation, Requester ID if user is found or valid creation
	 */
	public function create( $data = [] ) {

		if ( 0 !== $this->id || empty( $data ) ) {
			return false;
		}

		$defaults = [];
		$args     = wp_parse_args( $data, $defaults );
		$args     = $this->sanitize_columns( $args );

		// Bail if email is not supplied or valid.
		if ( empty( $args['email'] ) || ! is_email( $args['email'] ) ) {
			return false;
		}

		/**
		 * Fires before a requester is created
		 *
		 * @param array $args Contains requester information such as prayer ID, name, and email.
		 */
		do_action( 'intercessor_requester_pre_create', $args );

		$created      = false;
		$requester_id = \intercessor_add_item( 'requester', $args );

		// Proceed if requester has been created.
		if ( ! empty( $requester_id ) ) {

			// Maybe add prayer requests.
			if ( ! empty( $args['prayer_ids'] ) && is_array( $args['prayer_ids'] ) ) {
				$prayer_ids = array_unique( array_values( $args['prayer_ids'] ) );
				foreach ( $prayer_ids as $prayer_id ) {
					$update_args = [
						'requester_id' => $requester_id,
					];

					// Update prayer requests.
					intercessor_process_item( 'prayer', 'update', $prayer_id, $update_args );
				}
			}

			// We've successfully added/updated the requester, reset the class vars with the new data.
			$requester = intercessor_process_item( 'requester', 'get', $requester_id, false );

			// Setup the requester data with the values from DB.
			$this->setup_requester( $requester );

			$created = $this->id;
		}
		
		/**
		 * Fires after a requester is created
		 *
		 * @param int   $created If created successfully, the requester ID.  Defaults to false.
		 * @param array $args Contains requester information such as prayer ID, name, and email.
		 */
		do_action( 'intercessor_requester_post_create', $created, $args );

		return $created;
	}

	/**
	 * Update a requester record
	 *
	 * @since  0.9.5
	 * @param  array $data Array of data attributes for a requester (checked via whitelist).
	 * @return bool         If the update was successful or not
	 */
	public function update( $data = [] ) {
		// Bail if no data supplied.
		if ( empty( $data ) ) {
			return false;
		}

		$data = $this->sanitize_columns( $data );

		/**
		 * Fires immediately before a requester is updated
		 *
		 * @param int   $id The requester ID.
		 * @param array $data Data array to update requester record with.
		 */
		do_action( 'intercessor_requester_pre_update', $this->id, $data );

        $updated          = false;
        $previous_user_id = $this->user_id;

		if ( intercessor_process_item( 'requester', 'update', $this->id, $data ) ) {

			$requester = intercessor_process_item( 'requester', 'get', $this->id, false );

			$this->setup_requester( $requester );

			// Update prayers associated with this requester if the user ID changed.
            if ( intval( $previous_user_id ) !== intval( $this->user_id ) ) {

                // Update some prayer meta if necessary.
                $prayer_args = [
                    'requester_id' => $this->id,
					'number'       => 9999,
				];

                $prayer_ids = \intercessor_get_items( 'prayer', $prayer_args );
                foreach ( $prayer_ids as $prayer_id ) {
                    intercessor_process_item( 'prayer', 'update', $prayer_id, $prayer_args );
                }
            }

			$updated = true;
		}

		/**
		 * Fires immediately after a requester is updated
		 *
		 * @param string $updated
		 * @param int   $id The requester ID.
		 * @param array $data Data array to update requester record with.
		 */
		do_action( 'intercessor_requester_post_update', $updated, $this->id, $data );

		return $updated;
	}

	/**
	 * Attach an email to the requester
	 *
	 * @since  0.9.5
	 * @param  string $email The email address to remove from the requester.
	 * @param  bool   $primary Allows setting the email added as the primary.
	 * @return bool   If the email was added successfully
	 */
	public function add_email( $email = '', $primary = false ) {
		// Bailout if email address is invalid.
		if ( ! is_email( $email ) ) {
			return false;
		}

		$existing = \intercessor_get_item_by( 'requester', 'email', $email );

        // Email address already belongs to a requester.
		if ( $existing->id > 0 ) {
			return false;
		}

		// Bail if the email belongs to another registered user.
		if ( email_exists( $email ) ) {
			$user = get_user_by( 'email', $email );
			if ( $user->ID !== $this->user_id ) {
				return false;
			}
		}

		/**
		 * Fires immediately before a requester email is added.
		 *
		 * @param string $email The requester email address.
		 * @param int    $id    The requester ID.
		 * @param object $this  This requester object.
		 */
		do_action( 'intercessor_requester_pre_add_email', $email, $this->id, $this );

		// Update is used to ensure duplicate emails are not added.
		$ret = (bool) \intercessor_add_item_meta( 'requester', $this->id, 'additional_email', $email );

		/**
		 * Fires immediately after a requester email is added.
		 *
		 * @param string $email The requester email address.
		 * @param int    $id    The requester ID.
		 * @param object $this  This requester object.
		 */
		do_action( 'intercessor_requester_post_add_email', $email, $this->id, $this );

		// Set this email as primary if specified.
		if ( $ret && true === $primary ) {
			$this->set_primary_email( $email );
		}

		return $ret;

	}

	/**
	 * Removes an email from the requester
	 *
	 * @since  0.9.5
	 * @param  string $email The email address to remove from the requester.
     *
	 * @return bool True if the email was removed successfully, otherwise false.
	 */
	public function remove_email( $email = '' ) {
		// Bailout if email address is invalid.
		if ( ! is_email( $email ) ) {
			return false;
		}

		/**
		 * Fires immediately before a requester email is removed.
		 *
		 * @param string $email The requester email address.
		 * @param int    $id    The requester ID.
		 * @param object $this  This requester object.
		 */
		do_action( 'intercessor_requester_pre_remove_email', $email, $this->id, $this );

		$ret = (bool) \intercessor_delete_item_meta( 'requester', $this->id, 'additional_email', $email );

		/**
		 * Fires immediately after a requester email is removed.
		 *
		 * @param string $email The requester email address.
		 * @param int    $id    The requester ID.
		 * @param object $this  This requester object.
		 */
		do_action( 'intercessor_requester_post_remove_email', $email, $this->id, $this );

		return $ret;
	}

	/**
	 * Set an email address as the requester's primary email
	 *
	 * This will move the requester's previous primary email to an additional email
	 *
	 * @since  0.9.5
	 * @param  string $new_primary_email The email address to remove from the requester
	 * @return bool                      If the email was set as primary successfully
	 */
	public function set_primary_email( $new_primary_email ) {
	    // Bailout if email address is invalid.
		if ( ! is_email( $new_primary_email ) ) {
			return false;
		}

		/**
		 * Fires immediately before a requester email is added as primary.
		 *
		 * @param string $primary_email The requester's primary email address.
		 * @param int    $id            The requester ID.
		 * @param object $this          This requester object.
		 */
		do_action( 'intercessor_requester_pre_set_primary_email', $new_primary_email, $this->id, $this );

		$existing = \intercessor_get_item_by( 'requester', 'email', $new_primary_email );

        // Bail if this email belongs to another requester.
		if ( $existing->id > 0 && (int) $existing->id !== (int) $this->id ) {
			return false;
		}

		$old_email = $this->email;

		// Update requester record with new email.
		$update = $this->update(
			[
				'email' => $new_primary_email,
			]
		);

		// Remove new primary from list of additional emails
		$remove = $this->remove_email( $new_primary_email );

		// Add old email to additional emails list.
		$add = $this->add_email( $old_email );

		$ret = $update && $remove && $add;

		if ( $ret ) {
			$this->email = $new_primary_email;
			$prayer_ids  = $this->get_prayer_ids();

			if ( $prayer_ids ) {
				foreach ( $prayer_ids as $prayer_id ) {
					// Update prayer emails to primary email.
					\intercessor_update_item_meta( 'prayer', $prayer_id, 'email', $new_primary_email );
				}
			}
		}

		/**
		 * Fires immediately after a requester email is set as primary.
		 *
		 * @param string $primary_email The requester's primary email address.
		 * @param int    $id            The requester ID.
		 * @param object $this          This requester object.
		 */
		do_action( 'intercessor_requester_post_set_primary_email', $new_primary_email, $this->id, $this );

		return $ret;

	}

	/**
	 * Get the prayer ids of the requester in an array.
	 *
	 * @since 0.9.5
	 * @return array An array of prayer IDs for the requester, or an empty array if none exist.
	 */
	public function get_prayer_ids() {
		// Bail if no requester.
		if ( empty( $this->id ) ) {
			return [];
		}

        // Get total prayer counts.
        $count_args = [
			'requester_id' => $this->id,
		];

        $counts = \intercessor_count_items( 'prayer', $count_args );

        // Retrieve prayer IDs.
        $args = [
            'requester_id'  => $this->id,
            'number'        => $counts,
            'fields'        => 'ids',
			'no_found_rows' => true,
		];

        $prayer_ids = intercessor_get_items( 'prayer', $args );

        // Cast prayer IDs to integers.
        return array_map( 'absint', $prayer_ids );

	}

	/**
	 * Get an array of Prayer objects from the prayer_ids attached to the requester
	 *
	 * @since  0.9.5
	 * @param array|string  $status A single status as a string or an array of statuses.
	 *
	 * @return array An array of Prayer objects or an empty array
	 */
	public function get_prayers( $status = [] ) {

	    // Get prayers.
		$prayer_ids = $this->get_prayer_ids();
		$prayers = [];

		// Bail if no IDs.
		if ( empty( $prayer_ids ) ) {
			return $prayers;
		}

		// Retrieve prayers one at a time.
		foreach ( $prayer_ids as $prayer_id ) {
			$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
			if ( empty( $status ) || ( is_array( $status ) && in_array( $prayer->status, $status, true ) )
                || $status === $prayer->status ) {
				$prayers[] = $prayer;
			}
		}

		return $prayers;
	}

	/**
	 * Attach prayer to the requester then triggers increasing stats
	 *
	 * @since  0.9.5
	 * @param  int $prayer_id     The prayer ID to attach to the requester
	 * @param  bool $update_stats Whether to increase stats or not.
     *
	 * @return bool If the attachment was successfully
	 */
	public function attach_prayer( $prayer_id = 0, $update_stats = true ) {

		// Bail if no prayer ID.
		if ( empty( $prayer_id ) ) {
			return false;
		}

		// Get prayer request.
		$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );

		// Bail if prayer does not exist.
		if ( empty( $prayer ) ) {
			return false;
		}

		// Bail if prayer request already attached.
        if ( (int) $prayer->requester_id === (int) $this->id ) {
            return true;
        }

		/**
		 * Fires before a prayer is attached to a requester.
		 *
		 * @param int    $prayer_id    The prayer ID
		 * @param int    $id           The requester ID.
		 * @param object $this         This requester object.
		 */
		do_action( 'intercessor_requester_pre_attach_prayer', $prayer->id, $this->id, $this );

        // Update the prayer request.
        $update_args = [
            'requester_id' => $this->id,
			'email'        => $this->email,
		];

        $updated = (bool) intercessor_process_item( 'prayer', 'update', $prayer_id, $update_args );

        // Maybe update stats.
    //    if ( ! empty( $updated ) && ! empty( $update_stats ) ) {
            $this->recalculate_stats();
    //    }

		/**
		 * Fires after a prayer is attached to a requester.
		 *
		 * @param bool   $updated    Whether update took place.
		 * @param int    $prayer_id  The prayer ID.
		 * @param int    $id         The requester ID.
		 * @param object $this       This requester object.
		 */
		do_action( 'intercessor_requester_post_attach_prayer', $updated, $prayer->id, $this->id, $this );

		return $updated;
	}


	/**
	 * Remove a prayer from this requester, then triggers reducing stats
	 *
	 * @since  0.9.5
	 * @param  integer $prayer_id    The Prayer ID to remove.
	 * @param  bool    $update_stats Whether to update stats.
	 * @return boolean             True if the removal was successful
	 */
	public function remove_prayer( $prayer_id = 0, $update_stats = true ) {

		// Bail if no prayer ID supplied.
		if ( empty( $prayer_id ) ) {
			return false;
		}

		// Get prayer.
		$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );

		// Bail if prayer does not exist.
		if ( empty( $prayer ) ) {
			return false;
		}

		// Get all prayer request IDs.
        $prayers = $this->get_prayer_ids();

		// Bail if already removed.
        if ( ! in_array( $prayer_id, $prayers, true ) ) {
            return true;
        }

		
		/**
		 * Fires before a prayer is removed from a requester.
		 *
		 * @param int    $prayer_id       The prayer ID
		 * @param object $this            This requester object.
		 */
		do_action( 'intercessor_requester_pre_remove_prayer', $prayer->id, $this->id );

		// Try to delete prayer.
		$removed = (bool) intercessor_process_item( 'prayer', 'delete', $prayer_id, false );

		// Maybe update the stats.
        if ( ! empty( $removed ) && ! empty( $update_stats ) ) {
            $this->recalculate_stats();
        }

		/**
		 * Fires after a prayer is removed from a requester.
		 *
		 * @param bool   $removed   Whether the prayer request was removed.
		 * @param int    $prayer_id The prayer ID.
		 * @param int    $id        The requester ID.
		 * @param object $this      This requester object.
		 */
		do_action( 'intercessor_requester_post_remove_prayer', $removed, $prayer->id, $this->id, $this );

		return $removed;
	}

    /**
     * Recalculate stats for this requester.
     *
     * @since 1.0.0
     */
    public function recalculate_stats() {

		// Get total prayers.
		$count_args = [
			'requester_id' => $this->id,
			'status'       => [ 'active', 'pending', 'personal', 'archived' ],
		];

        $this->prayer_count = \intercessor_count_items( 'prayer', $count_args );

        // Update the requester prayer count.
        return $this->update(
            [
				'prayer_count' => $this->prayer_count,
			]
        );
    }

	/**
	 * Deletes a requester and associated prayer requests and metas.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return bool True if successful, otherwise false.
	 */
    public function delete() {
    	$status  = [ 'active', 'pending', 'personal', 'archived' ];
    	$prayers = $this->get_prayers( $status );

    	// Retrieve and delete prayer requests and metas if available.
    	if ( ! empty( $prayers ) ) {
    		foreach ( $prayers as $prayer ) {
    			intercessor_process_item( 'prayer', 'delete', $prayer->id, false );

    			$metas = \intercessor_get_item_meta( 'prayer', $prayer->id, 'prayed_counts', false );
    			if ( ! empty( $metas ) ) {
    				foreach ( $metas as $meta ) {
    					intercessor_delete_item_meta_by_key( 'prayer', $meta );
				    }
			    }
		    }
		}

    	// Delete requester privacy meta if available.
	    $privacy_metas = intercessor_get_item_meta( 'requester', $this->id, 'agreed_to_privacy', false );
    	if ( ! empty( $privacy_metas ) ) {
    		foreach ( $privacy_metas as $privacy_meta ) {
    			intercessor_delete_item_meta_by_key( 'requester', $privacy_meta );
		    }
	    }

        // Delete requester agreed to terms meta if available.
	    $terms_metas = intercessor_get_item_meta( 'requester', $this->id, 'agreed_to_terms', false );
	    if ( ! empty( $terms_metas ) ) {
		    foreach ( $terms_metas as $terms_meta ) {
			    intercessor_delete_item_meta_by_key( 'requester', $terms_meta );
		    }
	    }

	    // Delete additional emails if available.
	    $additional = intercessor_get_item_meta( 'requester', $this->id, 'additional_emails', false );
	    if ( ! empty( $additional ) ) {
	    	foreach ( $additional as $item ) {
	    		intercessor_delete_item_meta_by_key( 'requester', $item );
		    }
	    }

	    $deleted = intercessor_process_item( 'requester', 'delete', $this->id, false );

	    // Try to delete requester.
	    if ( $deleted ) {
	    	return true;
	    } else {
	    	return false;
	    }
    }

	/**
	 * Get the parsed notes for a requester as an array
	 *
	 * @since  0.9.5
	 * @param  integer $length The number of notes to get
	 * @param  integer $paged  What note to start at
	 * @return array           The notes requested
	 */
	public function get_notes( $length = 20, $paged = 1 ) {

		// Number.
		$length = is_numeric( $length )
			? absint( $length )
			: 20;

		// Offset.
		$offset = is_numeric( $paged ) && ( 1 !== $paged )
			? ( ( absint( $paged ) - 1 ) * $length )
			: 0;

		// Return the paginated notes.
		$notes =  [
			'object_id'   => $this->id,
			'object_type' => 'requester',
			'number'      => $length,
			'offset'      => $offset,
			'order'       => 'desc',
		];

		return \intercessor_get_items( 'note', $notes );
	}

	/**
	 * Get the total number of notes we have after parsing
	 *
	 * @since  0.9.5
	 * @return int The number of notes for the requester
	 */
	public function get_notes_count() {

		$all_notes = [
			'object_id'   => $this->id,
			'object_type' => 'requester',
		];

		return intercessor_count_items( 'note', $all_notes );
	}

	/**
	 * Add a note for the requester
	 *
	 * @since  0.9.5
	 * @param string $note The note to add.
	 *
	 * @return string|boolean The new note if added successfully, false otherwise
	 */
	public function add_note( $note = '' ) {

		// Bail if note content is empty.
		$note = trim( $note );
		if ( empty( $note ) ) {
			return false;
		}

		/**
		 * Filter the note of a requester before it's added
		 *
		 * @since 0.9.5
		 *
		 * @param string $note The content of the note to add
		 * @return string
		 */
		$note = apply_filters( 'intercessor_requester_add_note_string', $note );

		/**
		 * Allow actions before a note is added
		 *
		 * @since 0.9.5
		 */
		do_action( 'intercessor_requester_pre_add_note', $note, $this->id, $this );

		// Sanitize note content.
        $content = wp_kses( stripslashes( $note ), \intercessor_allowed_tags() );

		// Note args.
		$note_args = [
			'user_id'     => 0, // Authored by System/Bot.
			'object_id'   => $this->id,
			'object_type' => 'requester',
			'content'     => $content,
		];

        // Try to add the note.
		intercessor_add_item( 'note', $note_args );

		/**
		 * Allow actions after a note is added
		 *
		 * @since 0.9.5
		 */
		do_action( 'intercessor_requester_post_add_note', '', $note, $this->id, $this );

		// Return the formatted note.
		return $note;
	}

	/**
	 * Sanitize the data for update/create
	 *
	 * @since  0.9.5
	 * @param  array $data The data to sanitize
	 * @return array       The sanitized data, based off column defaults
	 */
	private function sanitize_columns( $data ) {

	    // Default values.
        $default_values = [];

        foreach ( $data as $key => $type ) {

            // Only sanitize data that we were provided.
            if ( ! array_key_exists( $key, $data ) ) {
                continue;
            }

            switch ( $type ) {
                case '%s':
                    if ( 'email' === $key ) {
                        $data[ $key ] = sanitize_email( $data[ $key ] );
                    } else {
                        $data[ $key ] = sanitize_text_field( $data[ $key ] );
                    }
                    break;

                case '%d':
                    if ( ! is_numeric( $data[ $key ] ) || absint( $data[ $key ] ) !== (int) $data[ $key ] ) {
                        $data[ $key ] = $default_values[ $key ];
                    } else {
                        $data[ $key ] = absint( $data[ $key ] );
                    }
                    break;

                case '%f':
                    // Convert what was given to a float.
                    $value = floatval( $data[ $key ] );

                    if ( ! is_float( $value ) ) {
                        $data[ $key ] = $default_values[ $key ];
                    } else {
                        $data[ $key ] = $value;
                    }
                    break;

                default:
                    $data[ $key ] = sanitize_text_field( $data[ $key ] );
                    break;
            }
        }

		return $data;
	}

	/**
	 * Split requester name into first and last names.
	 *
	 * @param int $id Requester ID.
	 *
	 * @since   0.9.5
	 * @return  object
	 */
	public function get_requester_name( $id ) {
		$first_name = $last_name = '';
		$requester  = new Requester( $id );
		$main_name  = explode( ' ', $requester->name, 2 );

		// Check for existence of first name after splitting requester name.
		if ( is_array( $main_name ) && ! empty( $main_name[0] ) ) {
			$first_name = $main_name[0];
		}

		// Check for existence of last name after splitting requester name.
		if ( is_array( $main_name ) && ! empty( $main_name[1] ) ) {
			$last_name = $main_name[1];
		}

		$splitted = [
			'first_name' => $first_name,
			'last_name'  => $last_name,
		];

		return (object) $splitted;
	}

	/**
	 * Retrieves first name of requester.
	 *
	 * @since   0.9.5
	 * @return  string
	 */
	public function get_first_name() {

		return $this->get_requester_name( $this->id )->first_name;
	}

	/**
	 * Retrieves last name of requester.
	 *
	 * @since   0.9.5
	 * @return  string
	 */
	public function get_last_name() {
		$last_name = $this->get_requester_name( $this->id )->last_name;

		return ( $last_name ) ? $last_name : '';
	}
}
