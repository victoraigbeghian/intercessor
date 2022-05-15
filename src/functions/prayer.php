<?php
/**
 * Prayer Functions
 *
 * @package     Intercessor
 * @subpackage  Functions/Prayer
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       0.9.5
 */

use Intercessor\Database\Queries\Prayer;
use Intercessor\Requester;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'intercessor_get_prayers' ) ) {
	/**
	 * Get Prayers
	 *
	 * Retrieves an array of all available prayer requests.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return mixed array if prayers exist, false otherwise.
	 * @since 0.9.5
	 */
	function intercessor_get_prayers( $args = [] ) {
		return intercessor_get_items( 'prayer', $args );
	}
}
/**
 * Get Prayer.
 *
 * @since 0.9.5
 *
 * @param int $prayer_id Prayer ID.
 * @uses  intercessor_get_item_by()
 * @return mixed object|bool \Intercessor\Prayer object or false if not found.
 */
function intercessor_get_prayer( $prayer_id = 0 ) {
	if ( empty( $prayer_id ) ) {
		return false;
	}

    return intercessor_get_item_by( 'prayer', 'id', $prayer_id );
}

if ( ! function_exists( 'intercessor_get_prayer_status_label' ) ) {
	/**
	 * Gets the prayer status label.
	 *
	 * @param int $prayer_id Prayer ID.
	 *
	 * @since 0.9.5
	 * @return $label Formatted prayer status label.
	 */
	function intercessor_get_prayer_status_label( $prayer_id = 0 ) {
		// Bail if no prayer ID supplied.
		if ( empty( $prayer_id ) ) {
			return false;
		}

		$status = intercessor_get_prayer_status( $prayer_id );
		$label  = '';

		switch ( $status ) {
			case 'active':
				$label = esc_html__( 'Active', 'intercessor' );
				break;

			case 'pending':
				$label = esc_html__( 'Pending', 'intercessor' );
				break;

			case 'personal':
				$label = esc_html__( 'Private', 'intercessor' );
				break;

			case 'archived':
				$label = esc_html__( 'Archived', 'intercessor' );
				break;
		}

		return apply_filters( 'intercessor_prayer_status_label', $label );
	}
}

/**
 * Get a prayer's status.
 *
 * @since 0.9.5
 *
 * @param int $prayer_id Prayer ID (default: 0).
 *
 * @return string The prayer status.
 */
function intercessor_get_prayer_status( $prayer_id = 0 ) {
	$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );

	return $prayer->status;
}

if ( ! function_exists( 'intercessor_prayer_statuses' ) ) {
	/**
	 * Retrieves all available statuses for prayers.
	 *
	 * @return array $prayer_status All the available prayer statuses
	 * @since 0.9.5
	 */
	function intercessor_prayer_statuses() {
		$prayer_statuses = [
			'active' => esc_html__( 'Active', 'intercessor' ),
			'archived' => esc_html__( 'Archived', 'intercessor' ),
			'pending' => esc_html__( 'Pending', 'intercessor' ),
			'personal' => esc_html__( 'Private', 'intercessor' ),
		];
		return apply_filters( 'intercessor_prayer_statuses', $prayer_statuses );
	}
}

/**
 * Get the prayer statuses keys
 *
 * @since  0.9.5
 *
 * @return array $prayer_status All available prayer statuses
 */
function intercessor_get_prayer_status_keys() {
	$statuses = array_keys( intercessor_prayer_statuses() );
	asort( $statuses );

	return array_values( $statuses );
}

if ( ! function_exists( 'intercessor_do_prayer_activation' ) ) {
	/**
	 * Activate a prayer request, set the status and visibility.
	 *
	 * @param int $prayer_id Prayer ID.
	 *
	 * @return mixed|void
	 * @since 0.9.5
	 */
	function intercessor_do_prayer_activation( $prayer_id = 0 ) {
		$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
		$share  = $prayer->share;
		$status = 'active';

		if ( 'personal' === $share ) {
			$status = 'personal';
		}

		$args = array(
			'status'      => $status,
			'date_active' => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
			'end_date'    => intercessor_get_end_date(),
		);

		$activated = intercessor_process_item( 'prayer', 'update', $prayer_id, $args );

		return apply_filters( 'intercessor_do_prayer_activation', $activated );
	}
}

if ( ! function_exists( 'intercessor_get_end_date' ) ) {
	/**
	 * Get the last date the prayer request should be displayed on listing page.
	 *
	 * @since 0.9.5
	 * @return string Last display date.
	 */
	function intercessor_get_end_date() {
		$final_date = intercessor_get_option( 'prayer_display_period', '90' );
		$display    = '';

		// Get current time once to avoid inconsistencies.
		$current_time = current_time( 'timestamp' );

		switch ( $final_date ) {
			// One month time.
			case 'one':
				$display = date( 'Y-m-d H:i:s', strtotime( '+30 days', $current_time ) );
				break;

			// Two months time.
			case 'two':
				$display = date( 'Y-m-d H:i:s', strtotime( '+60 days', $current_time ) );
				break;

			// Three months time.
			case 'three':
				$display = date( 'Y-m-d H:i:s', strtotime( '+90 days', $current_time ) );
				break;

			// Six months time.
			case 'six':
				$display = date( 'Y-m-d H:i:s', strtotime( '+180 day', $current_time ) );
				break;

			// A year from now.
			case 'year':
				$display = date( 'Y-m-d H:i:s', strtotime( '+1 year', $current_time ) );
				break;

			// No end date.
			case 'forever':
			case 'default':
				$display = date( 'Y-m-d H:i:s', $current_time );
				break;
		}

		return apply_filters( 'intercessor_prayer_end_date', $display );
	}
}

if ( ! function_exists( 'intercessor_is_active_prayer' ) ) {
	/**
	 * Checks whether a prayer request is active.
	 *
	 * @param int $prayer_id Prayer ID.
	 *
	 * @return bool Whether or not the prayer is active.
	 * @since 0.9.5
	 *
	 */
	function intercessor_is_active_prayer( int $prayer_id ) {
		$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
		if ( 'active' === $prayer->status || 'personal' === $prayer->status ) {
			return true;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'intercessor_get_prayer_attribute' ) ) {
	/**
	 * Retrieve prayer attributes for a prayer ID.
	 *
	 * @param int $prayer_id The prayer ID to receive attributes for.
	 *
	 * @return mixed
	 * @since 1.1.0
	 */
	function intercessor_get_prayer_attribute( int $prayer_id ) {
		// Bail if no valid prayer ID supplied.
		if ( empty( $prayer_id ) ) {
			return;
		}

		// Get the prayer request.
		$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
		$value  = '';

		// Try to retrieve the different attributes of the prayer request.
		switch ( $prayer ) {
			// Prayer number.
			case 'number':
				$format   = intercessor_get_option( 'prayer_id_format' );
				$position = intercessor_get_option( 'number_position', 'left' );

				if ( ! empty( $format ) ) {
					if ( 'right' === $position ) {
						$value = $prayer_id . $format;
					} else {
						$value = $format . ' ' . $prayer_id . ':';
					}
				}
				break;

			// Prayer title.
			case 'title':
				$value = $prayer->title;
				break;

			// Prayer Message.
			case 'message':
				$value = $prayer->message;
				break;

			// Prayer name.
			case 'name':
				if ( 'anon' === $prayer->share ) {
					$value = esc_html__( 'Anonymous', 'intercessor' );
				} else {
					$requester = new Requester( $prayer->email );
					$value     = esc_attr( $requester->name );
				}
				break;

			// Prayer share;
			case 'share':
				$value = $prayer->share;
				break;

			// Prayer email.
			case 'email':
				$email        = $prayer->email;
				$requester_id = $prayer->requester_id;

				if ( empty( $email ) && ! empty( $requester_id ) ) {
					$requester = new Requester( $requester_id, false );
					$value     = $requester->email;
				} else {
					$value     = $email;
				}
				break;

			// Prayer date.
			case 'date':
				$value = intercessor_date_i18n( $prayer->date_created, 'mysql' );
				break;
		}

		// Return the value.
		return $value;
	}
}

if ( ! function_exists( 'intercessor_get_prayer_number' ) ) {

	/**
	 * Retrieve the prayer request number.
	 *
	 * @since 0.9.5
	 *
	 * @param int $prayer_id Prayer ID.
	 * @return string $number Prayer formatted number.
	 */
	function intercessor_get_prayer_number( int $prayer_id ) {
		$format   = intercessor_get_option( 'prayer_id_format' );
		$position = intercessor_get_option( 'number_position', 'left' );
		$number   = '';

		if ( ! empty( $format ) ) {
			if ( 'right' === $position ) {
				$number = $prayer_id . $format;
			} else {
				$number = $format . ' ' . $prayer_id . ':';
			}
		}

		return esc_attr( $number );
	}
}

if ( ! function_exists( 'intercessor_get_prayer_name' ) ) {
	/**
	 * Retrieve the name of the prayer requester
	 *
	 * @param int $prayer_id Prayer ID.
	 *
	 * @return string $name The requester name who submitted the prayer request.
	 * @since  0.9.5
	 */
	function intercessor_get_prayer_name( int $prayer_id ) {
		$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
		if ( 'anon' === $prayer->share ) {
			$name = esc_html__( 'Anonymous', 'intercessor' );
		} else {
			$requester = new Requester( $prayer->email );
			$name      = esc_attr( $requester->name );
		}
		return apply_filters( 'intercessor_prayer_display_name', $name );
	}
}

if ( ! function_exists( 'intercessor_get_prayer_email' ) ) {
	/**
	 * Retrieve the requester email.
	 *
	 * @param int $prayer_id Prayer ID.
	 *
	 * @return string $email The requester email address.
	 * @since  0.9.5
	 */
	function intercessor_get_prayer_email( int $prayer_id ) {
		$prayer       = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
		$email        = $prayer->email;
		$requester_id = $prayer->requester_id;
		$returned     = '';

		if ( empty( $email ) && ! empty( $requester_id ) ) {
			$requester = new Requester( $requester_id, false );
			$returned  = $requester->email;
		} else {
			$returned  = $email;
		}

		return $returned;
	}
}

if ( ! function_exists( 'intercessor_get_prayer_key' ) ) {
	/**
	 * Retrieve the requester email.
	 *
	 * @param int $prayer_id Prayer ID.
	 *
	 * @return string $email The requester email address.
	 * @since  0.9.5
	 */
	function intercessor_get_prayer_key( int $prayer_id ) {
		// Bail if nothing was passed.
		if ( empty( $prayer_id ) ) {
			return '';
		}

		$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );

		return $prayer
			? $prayer->prayer_key
			: '';
	}
}

if ( ! function_exists( 'intercessor_get_prayer_notify' ) ) {
	/**
	 * Get prayer notification option.
	 *
	 * @param int $prayer_id Prayer ID.
	 *
	 * @since 1.0.0
	 * @return bool $notify True if selected otherwise false.
	 */
	function intercessor_get_prayer_notify( int $prayer_id ): bool {
		$notify = false;
		$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
		if ( '1' === $prayer->notify ) {
			$notify = true;
		}

		return $notify;
	}
}

if ( ! function_exists( 'intercessor_get_prayer_share' ) ) {
	/**
	 * Retrieve the key associated with the prayer
	 *
	 * @param int|null $prayer_id Prayer ID.
	 *
	 * @return string $share Prayer share option.
	 * @since 0.9.5
	 */
	function intercessor_get_prayer_share( int $prayer_id ): string {
		$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
		return $prayer->share;
	}
}

if ( ! function_exists( 'intercessor_get_prayer_title' ) ) {

	/**
	 * Get prayer request title.
	 *
	 * @param int $prayer_id Prayer ID.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	function intercessor_get_prayer_title( int $prayer_id ) {
		$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
		return $prayer->title;
	}
}

if ( ! function_exists( 'intercessor_hold_prayers' ) ) {
	/**
	 * Check if prayer status is set to pending in Intercessor Settings.
	 *
	 * @return bool
	 */
	function intercessor_hold_prayers() {
		$hold = intercessor_get_option( 'hold_prayers' );
		return (bool) apply_filters( 'hold_prayers', $hold );
	}
}

/**
 * Insert a prayer request
 *
 * @param array $prayer_data Array of data to create a prayer request.
 *
 * @since  0.9.5
 * @return int $prayer_id The ID of the created prayer request.
 */
function intercessor_insert_prayer( $prayer_data = [] ) {

	// Bail if no prayer data passed.
	if ( empty( $prayer_data ) ) {
		return false;
	}

	$time = time();

	// Array of prayer arguments to create prayer request.
	$prayer_args = [
		'user_id'      => $prayer_data['user_id'],
		'email'        => $prayer_data['user_email'],
		'title'        => $prayer_data['title'],
		'message'      => $prayer_data['message'],
		'status'       => $prayer_data['status'],
		'prayer_key'   => $prayer_data['prayer_key'],
		'share'        => $prayer_data['share'],
		'notify'       => $prayer_data['notify'],
		'date_created' => $prayer_data['created'],
	/*	'date_active'  => $time,
		'end_date'     => intercessor_get_end_date(), */
	];


	// Process the requester.
	$requester = new stdClass();

	// Requester is logged in.
	if ( did_action( 'intercessor_pre_process_prayer' ) && is_user_logged_in() ) {
		$requester = new Requester( get_current_user_id(), true );

		// Requester is logged in but used a different email to submit prayer,
		// so we need to assign that email address to their requester record.
		if ( ! empty( $requester->id ) && ( $prayer_args['email'] !== $requester->email ) ) {
			$requester->add_email( $prayer_args['email'] );
		}
	}

	// New requester.
	if ( empty( $requester->id ) ) {
		$requester = new Requester( $prayer_args['email'], false );

		if ( empty( $prayer_data['first_name'] ) && empty( $prayer_data['last_name'] ) ) {
			$name = $prayer_args['email'];
		} else {
			$name = $prayer_data['first_name'] . ' ' . $prayer_data['last_name'];
		}

		$create_args = array(
			'name'    => $name,
			'email'   => $prayer_args['email'],
			'user_id' => $prayer_args['user_id'],
		);

		$requester->create( $create_args );
	}

	// If the requester name was initially empty, update the record.
	if ( empty( $requester->name ) ) {
		$requester->update(
			[
				'name' => $prayer_data['first_name'] . ' ' . $prayer_data['last_name'],
			]
		);
	}

	$prayer_args['requester_id'] = $requester->id;

	// Parse the array of arguments.
	$args = wp_parse_args( $prayer_args );

	// Create the prayer request and add it to the database.
	$prayer_id = intercessor_add_item( 'prayer', $args );

	// Attach the prayer to the requester and update prayer counts.
	$requester->attach_prayer( $prayer_id, true );
	$requester->recalculate_stats();

	// Set up terms agreement date if agreed to during prayer submission.
	if ( ! empty( $prayer_data['terms'] ) ) {
		intercessor_add_item_meta( 'requester', $requester->id, 'agreed_to_terms', $time, false );
	}

	// Set up privacy policy agreement date if agreed to by user.
	if ( ! empty( $prayer_data['privacy'] ) ) {
		intercessor_add_item_meta( 'requester', $requester->id, 'agreed_to_privacy', $time, false );
	}

	// Setup prayer tweet if selected by user.
	if ( ! empty( $prayer_data['tweet'] ) ) {
		intercessor_tweet_prayer( $args );
	}

	// Return ID of newly created prayer, otherwise false.
	if ( ! empty( $prayer_id ) ) {

		/**
		 * Fires after the prayer has been inserted into the database.
		 *
		 * @since 0.9.5
		 *
		 * @param int    $id          Prayer ID.
		 * @param array  $prayer_data Array of prayer data.
		 */
		do_action( 'intercessor_inserted_prayer', $prayer_id, $prayer_data );

		// Trigger prayer notification.
		intercessor_trigger_prayer_notification( $prayer_args['email'], $prayer_id );

		// Setup pending or active prayer notices.
		if ( 'pending' === $args['status'] ) {
			$success_message = esc_html__( 'Prayer request submitted successfully but is awaiting approval.', 'intercessor' );
			intercessor_display_frontend_notice( $success_message, true, 'success' );
		} else {
			$success_message = esc_html__( 'Prayer request submitted successfully.', 'intercessor' );
			intercessor_display_frontend_notice( $success_message, true, 'success' );
		}

        return $prayer_id;
	} else {
		$failed_message = esc_html__( 'Error: Prayer request submission failed. Try submitting again or contact us if the prayer submission problem persists.', 'intercessor' );
		intercessor_display_frontend_notice( $failed_message, true, 'error' );

		return false;
	}
}

if ( ! function_exists( 'intercessor_get_prayer_button' ) ) {
	/**
	 * Get the pray for request button.
	 *
	 * @param int @prayer_id The prayer ID.
	 *
	 * @since 1.1.0
	 * @return string $button Pray for request button.
	 */
	function intercessor_get_prayer_button( int $prayer_id ) {
		// Bail if no prayer ID supplied.
		if ( empty( $prayer_id ) ) {
			return;
		}

		// Setup button label.
		$prayed_label  = intercessor_get_option( 'prayed_for_label' );
		if ( ! empty( $prayed_label ) ) {
			$prayed = $prayed_label;
		} else {
			$prayed = esc_html__( 'I Prayed for this', 'intercessor' );
		}

		// Setup answered prayer values.
		$answered = intercessor_is_answered_prayer( $prayer_id );
		$done     = esc_html__( 'Prayer answered', 'intercessor' );

		// Setup praying button.
		$button  = '<input name="prayer_id" class="prayers-id" value="' . $prayer_id . '" type="hidden"/>';

		// Verify if prayer already answered.
		if ( $answered ) {
			$button .= '<input type="submit" name="intercessor_prayed_updater" disabled="disabled" class="prayed-updater intercessor-submit" value="' . $done . '" />';
		} else {
			$button .= '<input type="submit" name="intercessor_prayed_updater" class="prayed-updater intercessor-submit" value="' . $prayed . '" />';
		}

		// Nonce verification.
		$button .= wp_nonce_field( 'praying_nonce', 'intercessor_update_prayed_nonce' );

		// Return prayer for request button.
		return apply_filters( 'intercessor_get_prayer_button', $button );
	}
}

/**
 * Process praying for request.
 *
 * @since 1.0.0
 * @return bool New prayed count object on success, otherwise false.
 */
function intercessor_process_praying_for_request() {
	// Get submitted prayer ID.
	$prayer_id = isset( $_POST['praying_id'] ) ? absint( $_POST['praying_id'] ) : 0;

	// Process form only if it is submitted.
	if ( ! empty( $prayer_id ) ) {
		// Bail if nonce fails.
		$nonce = isset( $_POST['intercessor_update_prayed_nonce'] )
		? intercessor_clean( wp_unslash( $_POST['intercessor_update_prayed_nonce'] ) )
		: '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'praying_nonce' ) ) {
			esc_html_e( 'Nonce verification failed. Please try again.', 'intercessor' );
		}

		// Process praying for request.
		$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
		$counts = intercessor_date_i18n( time(), 'mysql' );

		/**
		 * Fires before the prayer request is prayed for.
		 *
		 * @since 1.0.0
		 */
		do_action( 'intercessor_pre_uplift_prayer', $counts, $prayer_id );

		// If prayer exists, add prayed counts.
		if ( $prayer ) {
			$args = [
				'prayer_id'    => $prayer_id,
				'prayed_for'   => 1,
				'date_created' => $counts,
			];

			return intercessor_add_item( 'prayed', $args );
		}

		/**
		 * Fires after the prayer request is prayed for.
		 *
		 * @since 1.0.0
		 */
		do_action( 'intercessor_post_uplift_prayer', $counts, $prayer_id, $prayer );
	}
}

if ( ! function_exists( 'intercessor_get_old_prayed_counts' ) ) {
	/**
	 * Retrieve already prayed counts.
	 *
	 * @param int $prayer_id Prayer ID.
	 * @return int $prayed_counts Prayed counts otherwise 0.
	 */
	function intercessor_get_old_prayed_counts( $prayer_id = 0 ) {
		// Bail if nothing is passed.
		if ( empty( $prayer_id ) ) {
			return false;
		}

		$prayed = intercessor_process_item( 'prayed', 'get', $prayer_id, false );
		if ( $prayed ) {
			$prayed_counts = count( $prayed->prayed_for );
		} else {
			$prayed_counts = 0;
		}

		return (int) $prayed_counts;
	}
}

if ( ! function_exists( 'intercessor_get_prayed_requests' ) ) {
	/**
	 * Get Prayed counts
	 *
	 * @param int $prayer_id Prayer ID.
	 *
	 * @return int Number of times prayer request has been prayed for.
	 */
	function intercessor_get_prayed_requests(int $prayer_id = 0 ) : int {
		$prayed       = intercessor_get_item_meta( 'prayer', $prayer_id, 'prayed_counts', false );
		$prayed_counts = esc_html__( '0', 'intercessor' );
		if ( $prayed ) {
			$prayed_counts = count( $prayed );
			esc_attr( $prayed_counts );
		}

		return apply_filters( 'intercessor_prayed_requests', $prayed_counts );
	}
}

if ( ! function_exists( 'intercessor_count_prayers' ) ) :

	/**
	 * Query for and return array of prayer counts, keyed by status
	 *
	 * @param array $args Prayer arguments.
	 *
	 * @since 0.9.5
	 * @return int Prayer counts.
	 */
	function intercessor_count_prayers( $args = [] ) : int {
        // Setup defaults.
        $defaults = wp_parse_args(
            $args,
            [
                'count' => true,
            ]
        );

        $prayers = new Prayer( $defaults );

        //return the counts.
        return absint( $prayers->found_items );
	}
endif;

if ( ! function_exists( 'intercessor_get_prayer_counts' ) ) :
	/**
	 * Get prayer counts by status.
	 *
	 * @param array $args Arguments to query with.
	 *
	 * @since 0.9.5
	 * @return array Prayer request keyed by status.
	 */
	function intercessor_get_prayer_counts( array $args = [] ): array {
		// Parse arguments.
		$counts = wp_parse_args(
			$args,
			[
				'count'   => true,
				'groupby' => 'status',
			]
		);

        $prayers = new Intercessor\Database\Queries\Prayer( $counts );
        return intercessor_get_counts_format( $prayers, $counts['groupby'] );

	}
endif;

if ( ! function_exists( 'intercessor_is_answered_prayer' ) ) {
	/**
	 * Checks whether a prayer request is answered
	 *
	 * @param int $prayer_id Prayer ID.
	 *
	 * @since  0.9.5
	 * @return bool Whether or not the prayer is answered.
	 */
	function intercessor_is_answered_prayer( int $prayer_id ) : bool {
		// Retrieve prayer meta.
		$answered = intercessor_get_item_meta( 'prayer', $prayer_id, 'answered_prayer', true );

		if ( '1' === $answered ) {
			return true;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'intercessor_is_multiple_request' ) ) {
	/**
	 * Check for duplicate prayer request
	 *
	 * @param string $email User email during prayer submission.
	 * @param string $title Prayer request title.
	 *
	 * @return void
	 * @since  0.9.5
	 */
	function intercessor_is_multiple_request( string $email = '', string $title = '' ) {
		// Retrieve the requester from the email address.
		$requester = new Requester( $email, false );

		// Query for past prayers.
		$requester_args = [
			'email'        => $email,
			'requester_id' => $requester->id,
		];

		$prayers = intercessor_get_items( 'prayer', $requester_args );

		// Checks if the title has been used before.
		if ( ! empty( $prayers ) ) {

			foreach ( $prayers as $prayer ) {
				if ( $title === $prayer->title ) {
					return true;
				} else {
					return false;
				}
			}

		}
	}
}

if ( ! function_exists( 'intercessor_prayer_display_period' ) ) :
/**
 * The prayer request display period specified in plugin settings.
 *
 * @since  0.9.5
 * @return string $value The time period to display the prayer request.
 */
function intercessor_prayer_display_period() {
	$display = intercessor_get_option( 'prayer_display_period' );

	switch ( $display ) {

		case '30':
			$value = '+1 month';
			break;

		case '60':
			$value = '+2 months';
			break;

		case '180':
			$value = '+6 months';
			break;

		case '365':
			$value = '+1 year';
			break;

		case '730':
			$value = '+2 years';
			break;

		case '90':
		default:
			$value = '+3 months';
			break;
	}

	return apply_filters( 'intercessor_prayer_display_period', $value );
}
endif;

if ( !function_exists( 'intercessor_tweet_prayer' ) ) {
	/**
	 * Tweet prayer request.
	 *
	 * @param array $prayer_data Prayer data array.
	 *
	 * @since 0.9.5
	 */
	function intercessor_tweet_prayer( $prayer_data = [] ) {

	}
}
