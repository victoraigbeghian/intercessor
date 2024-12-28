<?php
/**
 * Prayer Actions
 *
 * @package     Intercessor
 * @subpackage  Admin/Prayers
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly.
use Intercessor\Prayer;
use Intercessor\Requester;

defined( 'ABSPATH' ) || exit;

/**
 * Add new prayer request.
 *
 * @param array $data Array of data to create prayer.
 *
 * @since 0.9.5
 */
function intercessor_process_add_prayer( array $data = [] )
{
	// Bail if it is not admin.
	if ( ! is_admin() ) {
		return false;
	}

	// Bail if current user cannot manage prayer settings.
	if ( ! current_user_can( 'manage_prayer_settings' ) ) {
		wp_die(
			esc_html__( 'You do not have permission to create prayer requests', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			array( 'response' => 403 )
		);
	}

	// Prayer args.
	$args = [
		'first_name'   => ! empty( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : false,
		'last_name'    => ! empty( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : false,
		'email'        => ! empty( $data['email'] ) ? is_email( $data['email'] ) : false,
		'title'        => ! empty( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
		'message'      => ! empty( $data['message'] ) ? intercessor_sanitize_textarea( $data['message'] ) : '',
		'status'       => ! empty( $data['status'] ) ? sanitize_text_field( $data['status'] ) : '',
		'share'        => ! empty( $data['share'] ) ? sanitize_text_field( $data['share'] ) : 'freely',
		'notify'       => $data['notify'] ?? '0',
		'date_created' => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
		'date_active'  => ! empty( $data['date_active'] ) ? sanitize_text_field( $data['date_active'] ) : '',
		'end_date'     => ! empty( $data['end_date'] ) ? sanitize_text_field( $data['end_date'] ) : '',
	];

	// Bail if prayer request already exits.
	if ( intercessor_is_multiple_request( $args['email'], $args['title'] ) ) {
		wp_safe_redirect(
			add_query_arg(
				'intercessor-message',
				'prayer_exists',
				$data['intercessor_redirect']
			)
		);

		$message = esc_html__( 'It seems like you have a prayer request with the same title. Consider changing the title and try again.', 'intercessor' );
		intercessor_display_frontend_notice( $message, true, 'error', false );

	// 	return false;
		//	intercessor_die();
	}

	// Prayer key.
	$auth_key           = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
	$args['prayer_key'] = strtolower( md5( $args['email'] . date( 'Y-m-d H:i:s' ) . $auth_key . uniqid( 'ipr', true ) ) );

	// Requester data.
	$requester = new Requester( $args['email'] );
	$user_id   = 0;
	$exists    = email_exists( $args['email'] );

	if ( $requester->user_id > 0 ) {
		$args['user_id'] = $requester->user_id;
	} elseif ( $exists ) {
		$args['user_id'] = $exists;
	} else {
		$args['user_id'] = $user_id;
	}

	// New requester.
	if ( empty( $requester->id ) ) {
		$requester = new Requester( $args['email'] );

		if ( empty( $args['first_name'] ) && empty( $args['last_name'] ) ) {
			$name = $args['email'];
		} else {
			$name = $args['first_name'] . ' ' . $args['last_name'];
		}

		$create_args = array(
			'name'    => $name,
			'email'   => $args['email'],
			'user_id' => $args['user_id'],
		);

		$requester->create( $create_args );
	}

	// If the requester name was initially empty, update the record.
	if ( empty( $requester->name ) ) {
		$requester->update(
			array(
				'name' => $args['first_name'] . ' ' . $args['last_name'],
			)
		);
	}

	$args['requester_id'] = $requester->id;

	// Create the prayer request and add it to the database.
	$prayer_id = intercessor_add_item( 'prayer', $args );

	// Attach the prayer to the requester and update prayer counts.
	$requester->attach_prayer( $prayer_id, true );

    // Redirect arguments.
    $added = ! empty( $prayer_id )
		? 'prayer_added'
		: 'prayer_add_failed';

    // Redirect to prayer list page.
    intercessor_redirect( add_query_arg( 'intercessor-message', $added, $data['intercessor-redirect'] ) );
}
add_action( 'intercessor_admin_add_prayer', 'intercessor_process_add_prayer' );

/**
 * Saves an edited prayer
 *
 * @param array $data Prayer request data.
 *
 * @since 0.9.5
 * @return void
 */
function intercessor_admin_edit_prayer( $data = [] ) {

	if ( ! isset( $data['intercessor-request-nonce'] )
		|| ! wp_verify_nonce( $data['intercessor-request-nonce'], 'intercessor_edit_prayer_nonce' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_prayers' ) ) {
		wp_die(
			esc_html__( 'You do not have permission to edit prayer requests', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			array( 'response' => 403 )
		);
	}

	if ( empty( $data['prayer-id'] ) ) {
		wp_die(
			esc_html__( 'No prayer request ID supplied', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			array( 'response' => 403 )
	);
	}

	$prayer_id = absint( $data['prayer-id'] );
	$prayer    = intercessor_process_item( 'prayer', 'update', $prayer_id, false );

	if ( ! $prayer ) {
		wp_die(
			esc_html__( 'Invalid prayer', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			array(
				'response' => 403,
			)
		);
	}

	$form_name = array(
		'first_name' => ! empty( $data['first_name'] ) ? intercessor_clean( $data['first_name'] ) : '',
		'last_name'  => ! empty( $data['last_name'] ) ? intercessor_clean( $data['last_name'] ) : '',
	);

	$update_data = array(
		'email'   => ! empty( $data['email'] ) ? is_email( $data['email'] ) : '',
		'title'   => ! empty( $data['title'] ) ? intercessor_clean( $data['title'] ) : '',
		'message' => ! empty( $data['message'] ) ? intercessor_sanitize_textarea( $data['message'] ) : '',
		'status'  => ! empty( $data['status'] ) ? intercessor_clean( $data['status'] ) : '',
		'share'   => ! empty( $data['share'] ) ? intercessor_clean( $data['share'] ) : 'freely',
		'notify'  => isset( $data['notify'] ) ? intercessor_clean( $data['notify'] ) : 0,
	);

	// Requester Details.
	$requester = new Requester( $update_data['email'] );
	$name      = $form_name['first_name'] . ' ' . $form_name['last_name'];

	// Update requester name, if changed.
	if ( $name !== $requester->name ) {
		$new_name = array(
			'name' => $name,
		);

		$requester->update( $new_name );
	}

	// Update requester email if changed. TODO disable the email input field.
	if ( $requester->email !== $update_data['email'] ) {
		intercessor_add_item_meta( 'requester', $requester->id, 'additional_email', $update_data['email'], false );
	}

	/**
	 * Fires before a prayer request is updated.
	 *
	 * @param int $prayer_id Prayer ID.
	 *
	 * @since 0.9.5
	 */
	do_action( 'intercessor_admin_update_prayer_request', $prayer_id );

	// Attempt to update.
	$updated = intercessor_process_item( 'prayer', 'update', $prayer_id, $update_data );
	$arg     = ! empty( $updated )
		? 'prayer_updated'
		: 'prayer_not_changed';

	// Redirect and display message for success or failure.
	wp_safe_redirect( add_query_arg( 'intercessor-message', $arg, $data['intercessor-redirect'] ) );
	intercessor_die();
}
add_action( 'intercessor_admin_edit_prayer', 'intercessor_admin_edit_prayer' );

/**
 * Listens for when a prayer delete button is clicked and deletes the
 * prayer request
 *
 * @param array $data Prayer request data.
 *
 * @return void
 *@uses \intercessor_process_item()
 * @since 0.9.5
 */
function intercessor_delete_prayer( array $data = [] ): void
{

	if ( ! isset( $data['_wpnonce'] )
		|| ! wp_verify_nonce( $data['_wpnonce'], 'intercessor_prayer_nonce' ) ) {
		wp_die(
			esc_html__( 'Trying to cheat or something?', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			array( 'response' => 403 )
		);
	}

	if ( ! current_user_can( 'edit_prayers' ) ) {
		wp_die(
			esc_html__( 'You do not have permission to delete prayer requests', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			array( 'response' => 403 )
		);
	}

	// Retrieve prayer and requester.
	$prayer_id = absint( $data['prayer'] );
	$requester = intercessor_get_requester_from_prayer( $prayer_id );
	$prayer    = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
	
	// Try to delete prayer.
	$deleted   = intercessor_process_item( 'prayer', 'delete', $prayer->id, false );
	
	// Recalculate stats for the requester and delete prayed counts.
	if ( $deleted ) {
		$requester->recalculate_stats();
        intercessor_process_item( 'prayed', 'delete', $prayer_id, false );
	}

	// Setup array of arguments to display delete message.
	$arg       = ! empty( $deleted )
		? 'prayer_deleted'
		: 'prayer_delete_failed';

	// Redirect.
	wp_safe_redirect(
		remove_query_arg(
			'intercessor-action',
			add_query_arg(
				'intercessor-message',
				$arg,
				wp_unslash( esc_url( $_SERVER['REQUEST_URI'] ) )
			)
		)
	);

	intercessor_die();
}
add_action( 'intercessor_delete_prayer', 'intercessor_delete_prayer' );

/**
 * Activates Prayer Request
 *
 * Sets a prayer request status to active
 *
 * @param array $data Prayer request data.
 *
 * @return void
 *@uses intercessor_update_prayer_status()
 * @since 0.9.5
 */
function intercessor_activate_prayer( array $data = [] ): void
{
    // Bail if nonce did not verify.
	if ( ! isset( $data['_wpnonce'] )
		|| ! wp_verify_nonce( $data['_wpnonce'], 'intercessor_prayer_nonce' ) ) {
		wp_die(
			esc_html__( 'Trying to cheat or something?', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			array( 'response' => 403 )
		);
	}

	// Bail if user cannot edit prayer.
	if ( ! current_user_can( 'edit_prayers' ) ) {
		wp_die(
			esc_html__( 'You do not have permission to edit prayer requests', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			array( 'response' => 403 )
		);
	}

	$prayer_id = absint( $data['prayer'] );


	$activated = intercessor_do_prayer_activation( $prayer_id );
	$arg       = ! empty( $activated )
		? 'prayer_activated'
		: 'prayer_activation_failed';

	// Redirect.
	wp_redirect( remove_query_arg( 'intercessor-action',
		add_query_arg(
			'intercessor-message',
			$arg,
			$_SERVER['REQUEST_URI']
		)
	) );

	intercessor_die();
}
add_action( 'intercessor_activate_prayer', 'intercessor_activate_prayer' );

/**
 * Deactivate Prayer
 *
 * Sets a prayer request's status to deactivate
 *
 * @param array $data Prayer request data.
 *
 * @return void
*@uses intercessor_update_prayer_status()
 * @since 0.9.5
 */
function intercessor_deactivate_prayer( array $data = [] ): void
{

    // Check if nonce data verified
	if ( ! isset( $data['_wpnonce'] ) || ! wp_verify_nonce( $data['_wpnonce'], 'intercessor_prayer_nonce' ) ) {
		wp_die(
            esc_html__( 'Trying to cheat or something?', 'intercessor' ),
            esc_html__( 'Error', 'intercessor' ),
            [ 'response' => 403 ]
        );
	}

	if ( ! current_user_can( 'edit_prayers' ) ) {
		wp_die(
            esc_html__( 'You do not have permission to create prayer requests', 'intercessor' ),
            [ 'response' => 403 ]
        );
	}

    // Set up prayer query arguments.
	$prayer_id   = absint( $data['prayer'] );
	$new_status  = [
		'status' => 'pending',
	];
	$deactivated = intercessor_process_item( 'prayer', 'update', $prayer_id, $new_status );
	$arg         = ! empty( $deactivated )
			? 'prayer_deactivated'
			: 'prayer_deactivation_failed';

	// Redirect.
	wp_redirect(
        remove_query_arg(
            'intercessor-action',
            add_query_arg( 'intercessor-message', $arg, $_SERVER['REQUEST_URI'] )
        )
    );
	intercessor_die();
}
add_action( 'intercessor_deactivate_prayer', 'intercessor_deactivate_prayer' );

/**
 * Uplift Prayer Request
 *
 * Pray for a prayer request and increase the counts
 *
 * @since 0.9.5
 * @param array $data Prayer request data.
 *
 * @uses intercessor_add_prayer_meta()
 * @return void
 */
function intercessor_uplift_prayer( $data = [] ) {
    // Verify nonce.
    intercessor_verify_nonce( $data );
    /*
	if ( ! isset( $data['_wpnonce'] ) || ! wp_verify_nonce( $data['_wpnonce'], 'intercessor_prayer_nonce' ) ) {
		wp_die(
			esc_html__( 'Trying to cheat or something?', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			array( 'response' => 403 )
		);
	}
*/
	if ( ! current_user_can( 'edit_prayers' ) ) {
		wp_die( esc_html__( 'You do not have permission to edit prayer requests', 'intercessor' ), esc_html__( 'Error', 'intercessor' ), array( 'response' => 403 ) );
	}

	$prayer_id = absint( $data['prayer'] );
	$prayer    = intercessor_process_item('prayer', 'get', $prayer_id, false );
	$counts    = intercessor_date_i18n( time(), 'mysql' );
/*	
	$former     = intercessor_process_item( 'prayed', 'get', $prayer_id, false );
	$old_counts = '';

	if ( ! empty( $former ) ) {
		$old_counts = absint( $former->prayed_for );
	}
	
	$new_counts = ceil( (int)$old_counts + 1  );
	$prayed_for_args = [
		'prayer_id'    => $prayer_id,
		'prayed_for'   => $new_counts,
		'date_created' => $counts,
	];

	$prayed_update = [
		'prayed_for' => $new_counts,
	];

	// Update prayed counts if not empty or add new one.
	if ( ! empty( $former ) && 0 < $former->prayed_for ) {
		intercessor_process_item( 'prayed', 'update', $former->prayer_id, $prayed_update );
	} else {
		intercessor_add_item( 'prayed', $prayed_for_args );
	}
*/
	/**
	 * Fires before the prayer request is prayed for.
	 */
	do_action( 'intercessor_admin_pre_uplift_prayer', $counts, $prayer_id );
	$args = [
		'prayer_id'    => $prayer_id,
		'prayed_for'   => 1,
		'date_created' => $counts,
	];

	$uplifted = intercessor_add_item( 'prayed', $args );
//	$uplifted = intercessor_add_item_meta( 'prayer', $prayer->id, 'prayed_counts', $counts, false );
	$arg      = $uplifted
		? 'prayer_uplifted'
		: 'prayer_uplift_failed';

	/**
	 * Fires after the prayer request is prayed for.
	 */
	do_action( 'intercessor_admin_post_uplift_prayer', $uplifted );

	// Redirect.
	wp_redirect(
		remove_query_arg(
			'intercessor-action',
			add_query_arg(
				'intercessor-message',
				$arg, $_SERVER['REQUEST_URI']
			)
		)
	);
	intercessor_die();
}
add_action( 'intercessor_uplift_prayer', 'intercessor_uplift_prayer' );

/**
 * Answered Prayer Request
 *
 * Mark a prayer request as answered.
 *
 * @since  0.9.5
 *
 * @param  array $data Prayer request data
 * @uses   intercessor_add_prayer_meta()
 *
 * @return void
 */
function intercessor_answered_prayer( $data = [] ) {

	if ( ! isset( $data['_wpnonce'] ) || ! wp_verify_nonce( $data['_wpnonce'], 'intercessor_prayer_nonce' ) ) {
		wp_die( esc_html__( 'Trying to cheat or something?', 'intercessor' ), esc_html__( 'Error', 'intercessor' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'edit_prayers' ) ) {
		wp_die( esc_html__( 'You do not have permission to edit prayer requests', 'intercessor' ), esc_html__( 'Error', 'intercessor' ), array( 'response' => 403 ) );
	}

	$prayer_id = absint( $data['prayer'] );
    $prayer    = intercessor_process_item( 'prayer', 'get', $prayer_id, false );

	/**
	 * Fires before the prayer request is marked answered.
	 *
	 * @param int $prayer_id The prayer request ID.
	 *
	 * @since 0.9.5
	 */
	do_action( 'intercessor_admin_pre_answered_prayer', $prayer_id );

	$answered = intercessor_add_item_meta( 'prayer', $prayer->id, 'answered_prayer', '1', true );

	$arg = $answered
		? 'prayer_answered'
		: 'prayer_answered_failed';

	/**
	 * Fires after the prayer request is marked answered.
	 *
	 * @param string $answered Answered prayer.
	 *
	 * @since 0.9.5
	 */
	do_action( 'intercessor_admin_post_answered_prayer', $answered );

	// Redirect
	wp_redirect( remove_query_arg( 'intercessor-action', add_query_arg( 'intercessor-message', $arg, $_SERVER['REQUEST_URI'] ) ) );

	intercessor_die();
}
add_action( 'intercessor_answered_prayer', 'intercessor_answered_prayer' );

/**
 * Archive a Prayer Request
 *
 * Sets a prayer request status to archive
 *
 * @since 0.9.5
 * @param array $data Prayer request data
 * @uses intercessor_update_prayer_status()
 * @return void
 */
function intercessor_admin_archive_prayer( $data = [] ) {

	if ( ! isset( $data['_wpnonce'] ) || ! wp_verify_nonce( $data['_wpnonce'], 'intercessor_prayer_nonce' ) ) {
		wp_die( esc_html__( 'Trying to cheat or something?', 'intercessor' ), esc_html__( 'Error', 'intercessor' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'edit_prayers' ) ) {
		wp_die(
			esc_html__( 'You do not have permission to edit prayer requests', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			array( 'response' => 403 )
		);
	}

	$prayer_id  = absint( $data['prayer'] );
	$new_status = esc_attr( 'archived' );
	$archived   = intercessor_process_item( 'prayer', 'update', $prayer_id, $new_status );
//	$archived    = intercessor_update_prayer_status( $prayer_id, 'archived' );;
	$arg        = ! empty( $archived )
		? 'prayer_archived'
		: 'prayer_archive_failed';

	// Redirect.
	wp_redirect( remove_query_arg( 'intercessor-action', add_query_arg( 'intercessor-message', $arg, $_SERVER['REQUEST_URI'] ) ) );
	intercessor_die();
}
add_action( 'intercessor_archive_prayer', 'intercessor_admin_archive_prayer' );

/**
 * Process the changes from the `View Prayer Details` page.
 *
 * @since 0.9.5
 *
 * @param array $data Prayer data.
*/
function intercessor_update_prayer_details( $data = [] ) {

	// Bail if an empty array is passed.
	if ( empty( $data ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this prayer request', 'intercessor' ), esc_html__( 'Error', 'intercessor' ), array( 'response' => 403 ) );
	}

	// Bail if the user does not have the correct permissions.
	if ( ! current_user_can( 'edit_prayers', $data['prayer_id'] ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this prayer request', 'intercessor' ), esc_html__( 'Error', 'intercessor' ), array( 'response' => 403 ) );
	}

//	check_admin_referer( 'intercessor_update_prayer_details_nonce' );

	// Retrieve the prayer ID and set up the prayer.
	$prayer_id = absint( $data['prayer_id'] );
	$prayer    = intercessor_process_item( 'prayer', 'update', $prayer_id, false );

	$prayer_update_args = [];

	$status = $data['intercessor-request-status'];
	$date   = sanitize_text_field( $data['intercessor-request-date'] );
	$minute = sanitize_text_field( $data['intercessor-request-time-min'] );
	$hour   = sanitize_text_field( $data['intercessor-request-time-hour'] );

	// Restrict hour change to our high and low.
	if ( $hour > 23 ) {
		$hour = 23;
	} elseif ( $hour < 0 ) {
		$hour = 00;
	}

	// Restrict minute change to our high and low.
	if ( $minute > 59 ) {
		$minute = 59;
	} elseif ( $minute < 0 ) {
		$minute = 00;
	}

	$date        		= date( 'Y-m-d', strtotime( $date ) ) . ' ' . $hour . ':' . $minute . ':00';
	$curr_requester_id  = sanitize_text_field( $data['intercessor-current-requester'] );
	$new_requester_id   = sanitize_text_field( $data['requester-id'] );

	do_action( 'intercessor_update_edited_prayer', $prayer_id );

	$prayer_update_args['date_created'] = $date;

	$requester_changed = false;

	// Create a new requester.
	if ( isset( $data['intercessor-new-requester'] ) && $data['intercessor-new-requester'] == '1' ) {
		$email = isset( $data['intercessor-new-requester-email'] ) ? sanitize_text_field( $data['intercessor-new-requester-email'] ) : '';
		$names = isset( $data['intercessor-new-requester-name'] ) ? sanitize_text_field( $data['intercessor-new-requester-name'] ) : '';

		if ( empty( $email ) || empty( $names ) ) {
			wp_die( esc_html__( 'New Requesters require a name and email address', 'intercessor' ) );
		}

		$requester = new Requester( $email );

		if ( empty( $requester->id ) ) {

			$requester_data = array(
				'name'  => $names,
				'email' => $email
			);

			$user_id = email_exists( $email );

			if ( false !== $user_id ) {
				$requester_data['user_id'] = $user_id;
			}

			if ( ! $requester->create( $requester_data ) ) {
				// Failed to crete the new requester, assume the previous requester
				$requester_changed = false;

				$requester = new Requester( $curr_requester_id );
				intercessor_set_error( 'intercessor-request-new-requester-fail', esc_html__( 'Error creating new requester', 'intercessor' ) );
			}
		}

		$new_requester_id  = $requester->id;
		$old_requester 	   = new Requester( $curr_requester_id );
		$requester_changed = true;

	} elseif ( $curr_requester_id !== $new_requester_id ) {

		$requester		   = new Requester( $new_requester_id );
		$email     		   = $requester->email;
		$names     		   = $requester->name;
		$old_requester 	   = new Requester( $curr_requester_id );
		$requester_changed = true;

	} else {
		$requester = new Requester( $curr_requester_id );
		$email     = $requester->email;
		$names     = $requester->name;
	}

	// Setup first and last name from input values.
	$names      = explode( ' ', $names );
	$first_name = ! empty( $names[0] ) ? $names[0] : '';
	$last_name  = '';

	if ( ! empty( $names[1] ) ) {
		unset( $names[0] );
		$last_name = implode( ' ', $names );
	}

	if ( $requester_changed ) {

		// Remove the stats and prayer from the previous requester and attach it to the new requester.
		$old_requester->remove_prayer( $prayer_id, false );
		$requester->attach_prayer( $prayer_id, false );

		// Adjust the stats of Requesters
		if ( 'archived' === $status || 'active' === $status || 'personal' === $status ) {
			$old_requester->recalculate_stats();
			$requester->recalculate_stats();
		}

		$prayer_update_args['requester_id'] = $requester->id;
	}

	// Set new prayer values.
	$prayer_update_args['user_id'] = $requester->user_id;
	$prayer_update_args['email']   = $requester->email;

	$updated = intercessor_process_item( 'prayer', 'update', $prayer_id, $prayer_update_args );

	// Update prayer request status if changed.
	if ( $prayer_update_args['status'] !== $prayer->status ) {
		intercessor_process_item( 'prayer', 'update', $prayer_id, $status );
	}

	if ( false === $updated ) {
		wp_die( esc_html__( 'Error Updating Prayer', 'intercessor' ), esc_html__( 'Error', 'intercessor' ), array( 'response' => 400 ) );
	}

	do_action( 'intercessor_updated_prayer', $prayer_id );

	intercessor_redirect(
		add_query_arg(
			array(
				'page'                => 'intercessor-prayers',
				'view'                => 'view-prayer-details',
				'intercessor-message' => 'prayer-updated',
				'id'                  => $prayer_id,
			),
			admin_url( 'admin.php' )
		)
	);
}
add_action( 'intercessor_update_prayer_details', 'intercessor_update_prayer_details' );
/**
 * Add praise report to an answered prayer request.
 *
 * @param array $data Array of argumenst to create praise report.
 *
 * @since 0.9.5
 * @return bool New Praise ID on success, error on failure.
 */
function intercessor_admin_add_praise( $data = [] ) {
	// Check nonce.
	if ( ! check_admin_referer( 'intercessor_praise_nonce', 'intercessor_praise_nonce' ) ) {
		wp_die(
			esc_html__( 'Trying to cheat or something?', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			array( 'response' => 403 )
		);
	}

	// Bail if user cannot add praise report.
	if ( ! current_user_can( 'manage_prayer_settings' ) ) {
		wp_die(
			esc_html__( 'You do not have permission to add praise report', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			array( 'response' => 403 )
		);
	}

	// Get praise report contents.
	$praise = ! empty( $data['intercessor-praise'] )
		? intercessor_sanitize_textarea( stripslashes( $data['intercessor-praise'] ) )
		: false;

	// Bail if no praise.
	if ( empty( $praise ) ) {
		wp_die(
			esc_html__( 'Praise report textarea cannot be empty', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			array( 'response' => 403 )
		);
	}

	/**
	 * Fires before adding a praise report
	 *
	 * @param string $praise Praise report.
	 *
	 * @since 0.9.5
	 */
	do_action( 'intercessor_admin_post_add_praise', $praise );

	$prayer_id = absint( $data['prayer'] );
	$prayer    = intercessor_process_item( 'prayer', 'update', $prayer_id, false );

	// Try adding praise report meta.
	$praises = intercessor_add_item_meta( 'prayer', $prayer->id, 'praise_report', $praise, false );
	$arg     = $praises
		? 'praise_added'
		: 'praise_addition_failed';

	/**
	 * Fires after adding a praise report
	 *
	 * @param string $praises Praise report.
	 *
	 * @since 0.9.5
	 */
	do_action( 'intercessor_admin_post_add_praise', $praises );

	// Redirect
	wp_redirect( remove_query_arg( 'intercessor-action', add_query_arg( 'intercessor-message', $arg, $_SERVER['REQUEST_URI'] ) ) );

	intercessor_die();
}
add_action( 'intercessor_add_praise', 'intercessor_admin_add_praise' );
