<?php
/**
 * Intercessor Requester functions and actions.
 *
 * @package     Intercessor
 * @subpackage  Admin/Requesters
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       0.9.5
 */

// Exit if accessed directly.
use Intercessor\Requester;

defined( 'ABSPATH' ) || exit;

/**
 * Register a view for the single requester view
 *
 * @since  0.9.5
 * @param  array $views An array of existing views.
 * @return array        The altered list of views
 */
function intercessor_register_default_requester_views( $views ) {

	$default_views = [
		'overview' => 'intercessor_requesters_view',
		'delete'   => 'intercessor_requesters_delete_view',
		'notes'    => 'intercessor_requester_notes_view',
		'tools'    => 'intercessor_requester_tools_view',
	];

	return array_merge( $views, $default_views );

}
add_filter( 'intercessor_requester_views', 'intercessor_register_default_requester_views', 1, 1 );

/**
 * Register a tab for the single requester view
 *
 * @since  0.9.5
 * @param  array $tabs An array of existing tabs.
 * @return array       The altered list of tabs
 */
function intercessor_register_default_requester_tabs( $tabs ) {

	$default_tabs = [
		'overview' => [
			'dashicon' => 'dashicons-admin-users',
			'title'    => esc_html_x( 'Requester Profile', 'intercessor' )
		],
		'notes'   => [
			'dashicon' => 'dashicons-admin-comments',
			'title'    => esc_html_x( 'Requester Notes', 'intercessor' )
		],
		'tools'   => [
			'dashicon' => 'dashicons-admin-tools',
			'title'    => esc_html_x( 'Requester Tools', 'intercessor' )
		],
	];

	return array_merge( $tabs, $default_tabs );
}
add_filter( 'intercessor_requester_tabs', 'intercessor_register_default_requester_tabs', 1, 1 );

/**
 * Register the Delete icon as late as possible so it's at the bottom
 *
 * @since  0.9.5
 * @param  array $tabs An array of existing tabs.
 * @return array       The altered list of tabs, with 'delete' at the bottom
 */
function intercessor_register_delete_requester_tab( $tabs ) {

	$tabs['delete'] = [
        'dashicon' => 'dashicons-trash',
        'title'    => esc_html_x( 'Delete', 'Delete Requester tab title', 'intercessor' ),
    ];

	return $tabs;
}
add_filter( 'intercessor_requester_tabs', 'intercessor_register_delete_requester_tab', PHP_INT_MAX, 1 );

/**
 * Add Requester column to Users list table.
 *
 * @since 0.9.5
 *
 * @param array $columns Existing columns.
 *
 * @return array $columns Columns with `Requester` added.
 */
function intercessor_add_requester_column_to_users_table( $columns ) {
	$columns['intercessor_requester'] = esc_html__( 'Requester', 'intercessor' );
	return $columns;
}
add_filter( 'manage_users_columns', 'intercessor_add_requester_column_to_users_table' );

/**
 * Display requester details on Users list table.
 *
 * @since 0.9.5
 *
 * @param string $value       Existing value of the custom column.
 * @param string $column_name Column name.
 * @param int    $user_id     User ID.
 *
 * @return string URL to Requester page, existing value otherwise.
 */
function intercessor_render_requester_column( $value, $column_name, $user_id ) {
	if ( 'intercessor_requester' === $column_name ) {
		$requester = new Requester( $user_id, true );

		if ( $requester->id > 0 ) {
			$name     = '#' . $requester->id . ' ';
			$name    .= ! empty( $requester->name ) ? $requester->name : '<em>' . esc_html__( 'Unnamed Requester', 'intercessor' ) . '</em>';
			$view_url = admin_url( 'admin.php?page=intercessor-requesters&view=overview&id=' . $requester->id );

			return '<a href="' . esc_url( $view_url ) . '">' . $name . '</a>';
		}
	}

	return $value;
}
add_action( 'manage_users_custom_column',  'intercessor_render_requester_column', 10, 3 );


/**
 * Connect and Reconnect Requester with User profile.
 *
 * @param object $requester      Requester Object.
 * @param array  $requester_data Requester Post Variables.
 *
 * @return array
 * @since 0.9.5
 */
function intercessor_connect_user_requester_profile( $requester, $requester_data ) {

	$requester_id     = $requester->id;
	$previous_user_id = $requester->user_id;

	/**
	 * Fires before editing a requester.
	 *
	 * @param int   $requester_id   The ID of the requester.
	 * @param array $requester_data The requester data.
	 *
	 * @since 0.9.5
	 */
	do_action( 'intercessor_pre_edit_requester', $requester_id, $requester_data );

	$output = [];

	if ( $requester->update( $requester_data ) ) {

		// Create and Update Requester First Name and Last Name in Meta Fields.
		$requester->update_meta( '_intercessor_requester_first_name', $requester_data['first_name'] );
		$requester->update_meta( '_intercessor_requester_last_name', $requester_data['last_name'] );

		// Fetch disconnected user id, if exists.
		$disconnected_user_id = $requester->get_meta( '_intercessor_disconnected_user_id', true );

		// Flag User and Requester Disconnection.
		delete_user_meta( $disconnected_user_id, '_intercessor_is_requester_disconnected' );

		// Check whether the disconnected user id and the reconnected user id are same or not.
		// If both are same then delete user id store in requester meta.
		if( $requester_data['user_id'] === $disconnected_user_id ) {
			delete_user_meta( $disconnected_user_id, '_intercessor_disconnected_requester_id' );
			$requester->delete_meta( '_intercessor_disconnected_user_id' );
		}

		$output['success']        = true;
		$requester_data           = array_merge( $requester_data );
		$output['requester_info'] = $requester_data;

	} else {

		$output['success'] = false;

	}

	/**
	 * Fires after editing a requester.
	 *
	 * @param int   $requester_id   The ID of the requester.
	 * @param array $requester_data The requester data.
	 *
	 * @since 0.9.5
	 */
	do_action( 'intercessor_post_edit_requester', $requester_id, $requester_data );


	return $output;
}

/**
 * Processes a custom edit
 *
 * @since  0.9.5
 *
 * @param  array $args The $_POST array being passed
 *
 * @return array|bool|void
 */
function intercessor_edit_requester( $args = [] ) {
	$requester_edit_role = apply_filters( 'intercessor_edit_requesters_role', 'edit_prayers' );

	// Bail if there is nothing to edit
	if ( empty( $args ) || empty( $args['requesterinfo'] ) || empty( $args['_wpnonce'] ) ) {
		return;
	}

	if ( ! is_admin() || ! current_user_can( $requester_edit_role ) ) {
		wp_die(
			esc_html__( 'You do not have permission to edit this requester.', 'intercessor' )
		);
	}

	$requester_info = $args['requesterinfo'];
	$requester_id   = (int)$args['requesterinfo']['id'];
	$nonce         = $args['_wpnonce'];

	// Bail if nonce check fails.
	if ( ! wp_verify_nonce( $nonce, 'edit-requester' ) ) {
		wp_die(
			esc_html__( 'Cheatin\' eh?!', 'intercessor' )
		);
	}

	// Try to get requester.
	$requester = interccessor_process_item( 'requester', 'get', $requester_id, false);
	
	// Bail if requester does not exist.
	if ( empty( $requester->id ) ) {
		return false;
	}

	// Parse requester details with defaults.
	$defaults = [
		'name'    	   => '',
		'email'   	   => '',
		'user_id' 	   => 0,
		'date_created' => '',
	];

	$requester_info = wp_parse_args( $requester_info, $defaults );

	if ( ! is_email( $requester_info['email'] ) ) {
		intercessor_set_error(
			'intercessor-invalid-email',
			esc_html__( 'Please enter a valid email address.', 'intercessor' )
		);
	}

	if ( (int) $requester_info['user_id'] !== (int) $requester->user_id ) {

		// Make sure we don't already have this user attached to a requester.
		if ( ! empty( $requester_info['user_id'] )
			&& false !== intercessor_get_item_by( 'requester', 'user_id', $requester_info['user_id'] ) ) {
			intercessor_set_error(
				'intercessor-invalid-requester-user_id',
				sprintf(
					esc_html__( 'The User ID %d is already associated with a different requester.', 'intercessor' ),
					$requester_info['user_id']
				)
			);
		}

		// Make sure it's actually a user
		$user = get_user_by( 'id', $requester_info['user_id'] );
		if ( ! empty( $requester_info['user_id'] ) && false === $user ) {
			intercessor_set_error( 'intercessor-invalid-user_id', sprintf(
				esc_html__( 'The User ID %d does not exist. Please assign an existing user.', 'intercessor' ),
				$requester_info['user_id'] )
			);
		}

	}

	// Record this for later.
	$previous_user_id = $requester->user_id;

	// Bail, if there is any error.
	if ( intercessor_get_errors() ) {
		return;
	}

	// Retrieve user data.
	$user_id = intval( $requester_info['user_id'] );

	if ( empty( $user_id ) && ! empty( $requester_info['user_login'] ) ) {
		// See if email exists, otherwise use login.
		$user_by_field = is_email( $requester_info['user_login'] )
			? 'email'
			: 'login';

		$user = get_user_by( $user_by_field, $requester_info['user_login'] );
		if ( $user ) {
			$user_id = $user->ID;
		} else {
			intercessor_set_error(
				'intercessor-invalid-user-string',
				sprintf(
					esc_html__( 'Failed to attach user. The login or email address %s was not found.', 'intercessor' ),
					$requester_info['user_login']
				)
			);
		}
	}

	// Sanitize the inputs
	$requester_data            	    = [];
	$requester_data['name']    	    = strip_tags( stripslashes( $requester_info['name'] ) );
	$requester_data['email']   	    = $requester_info['email'];
	$requester_data['user_id'] 	    = $user_id;
	$requester_data['date_created'] = gmdate( 'Y-m-d H:i:s', strtotime( $requester_info['date_created'] ) );

	$requester_data = apply_filters( 'intercessor_edit_requester_info', $requester_data, $requester_id );
	$requester_data = array_map( 'sanitize_text_field', $requester_data );

	/**
	 * Runs before a requester is edited.
	 *
	 * @since 0.9.5
	 */
	do_action( 'intercessor_pre_edit_requester', $requester_id, $requester_data );

	$output         = [];
	$previous_email = $requester->email;

	if ( $requester->update( $requester_data ) ) {

		// Update some prayer meta if necessary.
		$prayers_array = explode( ',', $requester->prayer_ids );

		if ( $requester->email !== $previous_email ) {
			foreach ( $prayers_array as $prayer_id ) {
				intercessor_update_item_meta( 'prayer', $prayer_id, 'email', $requester->email );
			}
		}

		if ( $requester->user_id !== $previous_user_id ) {
			foreach ( $prayers_array as $prayer_id ) {
				intercessor_update_item_meta( 'prayer', $prayer_id, '_intercessor_prayer_user_id', $requester->user_id );
			}
		}

		$output['success']        = true;
		$output['requester_info'] = $requester_data;

	} else {

		$output['success'] = false;

	}

	/**
	 * Runs after a requester is edited
	 *
	 * @since 0.9.5
	 */
	do_action( 'intercessor_post_edit_requester', $requester_id, $requester_data );

	if ( intercessor_doing_ajax() ) {
		wp_send_json( $output );
	}

	return $output;

}
add_action( 'intercessor_edit-requester', 'intercessor_edit_requester', 10, 1 );


/**
 * Add an email address to the requester from within the admin and log a requester note
 *
 * @since  0.9.5
 * @param  array $args  Array of arguments: nonce, requester id, and email address
 * @return mixed        If DOING_AJAX echos out JSON, otherwise returns array of success (bool) and message (string)
 */
function intercessor_add_requester_email( $args = [] ) {

	$requester_edit_role = apply_filters( 'intercessor_edit_requesters_role', 'edit_prayers' );

	if ( ! is_admin() || ! current_user_can( $requester_edit_role ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this requester.', 'intercessor' ) );
	}

	$output       = [];
	$requester_id = 0;

	if ( empty( $args ) || empty( $args['email'] ) || empty( $args['requester_id'] ) ) {

		$output['success'] = false;

		if ( empty( $args['email'] ) ) {
			$output['message'] = esc_html__( 'Email address is required.', 'intercessor' );
		} else if ( empty( $args['requester_id'] ) ) {
			$output['message'] = esc_html__( 'Requester ID is required.', 'intercessor' );
		} else {
			$output['message'] = esc_html__( 'An error has occured. Please try again.', 'intercessor' );
		}

	} else if ( ! wp_verify_nonce( $args['_wpnonce'], 'intercessor-add-requester-email' ) ) {

		$output = [
			'success' => false,
			'message' => esc_html__( 'Nonce verification failed.', 'intercessor' ),
		];

	} else if ( ! is_email( $args['email'] ) ) {

		$output = [
			'success' => false,
			'message' => esc_html__( 'Invalid email address.', 'intercessor' ),
		];

	} else {
		// Get requester data.
		$email        = sanitize_email( $args['email'] );
		$requester_id = (int) $args['requester_id'];
		$primary      = 'true' === $args['primary'] ? true : false;
		$requester    = intercessor_process_item( 'requester', 'get', $requester_id, false );
		

		if ( false === $requester->add_email( $email, $primary ) ) {

			if ( in_array( $email, $requester->emails ) ) {

				$output = [
					'success'  => false,
					'message'  => esc_html__( 'Email already associated with this requester.', 'intercessor' ),
				];

			} else {

				$output = [
					'success' => false,
					'message' => esc_html__( 'Email address is already associated with another requester.', 'intercessor' ),
				];

			}

		} else {

			$redirect = admin_url( 'admin.php?page=intercessor-requesters&view=overview&id=' . $requester_id . '&intercessor-message=email-added' );
			$output   = [
				'success'  => true,
				'message'  => esc_html__( 'Email successfully added to requester.', 'intercessor' ),
				'redirect' => $redirect,
			];

			$user           = wp_get_current_user();
			$user_login     = ! empty( $user->user_login ) ? $user->user_login : 'IPRBot';
			$requester_note = sprintf( esc_html__( 'Email address %s added by %s', 'intercessor' ), $email, $user_login );
			$requester->add_note( $requester_note );

			// Add note about the email change.
			if ( $primary ) {
				$requester_note =  sprintf( esc_html__( 'Email address %s set as primary by %s', 'intercessor' ), $email, $user_login );
				$requester->add_note( $requester_note );
			}


		}

	}

	/**
	 * Runs after a requester email is added
	 *
	 * @since 0.9.5
	 */
	do_action( 'intercessor_post_add_requester_email', $requester_id, $args );

	if ( intercessor_doing_ajax() ) {
		wp_send_json( $output );
	}

	return $output;

}
add_action( 'intercessor_requester-add-email', 'intercessor_add_requester_email', 10, 1 );

/**
 * Remove an email address to the requester from within the admin and log a requester note
 * and redirect back to the requester interface for feedback
 *
 * @since  0.9.5
 *
 * @return bool
 */
function intercessor_remove_requester_email() {
	// Bail if no ID supplied.
	if ( empty( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
		return false;
	}

	// Bail, if no email supplied.
	if ( empty( $_GET['email'] ) || ! is_email( $_GET['email'] ) ) {
		return false;
	}

	// Bail, if nonce fails.
	if ( empty( $_GET['_wpnonce'] ) ) {
		return false;
	}

	$nonce = $_GET['_wpnonce'];
	if ( ! wp_verify_nonce( $nonce, 'intercessor-remove-requester-email' ) ) {
		wp_die(
			esc_html__( 'Nonce verification failed', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			[ 'response' => 403 ]
		);
	}

	// Process email removal from requester.
	$requester = new Requester( $_GET['id'] );
	if ( $requester->remove_email( $_GET['email'] ) ) {

		$url = add_query_arg(
			'intercessor-message',
			'email-removed',
			admin_url( 'admin.php?page=intercessor-requesters&view=overview&id=' . $requester->id )
		);

		$user           = wp_get_current_user();
		$user_login     = ! empty( $user->user_login ) ? $user->user_login : 'Intercessor Bot';
		$requester_note = sprintf( esc_html__( 'Email address %s removed by %s', 'intercessor' ), sanitize_email( $_GET['email'] ), $user_login );
		$requester->add_note( $requester_note );

	} else {
		$url = add_query_arg( 'intercessor-message', 'email-remove-failed', admin_url( 'admin.php?page=intercessor-requesters&view=overview&id=' . $requester->id ) );
	}

	// Redirect.
	wp_safe_redirect( $url );
	exit;
}
add_action( 'intercessor_requester-remove-email', 'intercessor_remove_requester_email', 10 );

/**
 * Set an email address as the primary for a requester from within the admin and log a requester note
 * and redirect back to the requester interface for feedback
 *
 * @since  0.9.5
 * @return bool|void
 */
function intercessor_set_requester_primary_email() {
	if ( empty( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
		return false;
	}

	if ( empty( $_GET['email'] ) || ! is_email( $_GET['email'] ) ) {
		return false;
	}

	if ( empty( $_GET['_wpnonce'] ) ) {
		return false;
	}

	$nonce = $_GET['_wpnonce'];
	if ( ! wp_verify_nonce( $nonce, 'intercessor-set-requester-primary-email' ) ) {
		wp_die(
			esc_html__( 'Nonce verification failed', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			[ 'response' => 403 ]
		);
	}

	// Set requester primary email.
	$requester = new Requester( $_GET['id'] );
	if ( $requester->set_primary_email( $_GET['email'] ) ) {

		$url = add_query_arg(
			'intercessor-message',
			'primary-email-updated',
			admin_url( 'admin.php?page=intercessor-requesters&view=overview&id=' . $requester->id )
		);

		$user           = wp_get_current_user();
		$user_login     = ! empty( $user->user_login ) ? $user->user_login : 'Intercessor Bot';
		$requester_note = sprintf( esc_html__( 'Email address %s set as primary by %s', 'intercessor' ), sanitize_email( $_GET['email'] ), $user_login );
		$requester->add_note( $requester_note );

	} else {
		$url = add_query_arg(
			'intercessor-message',
			'primary-email-failed',
			admin_url( 'admin.php?page=intercessor-requesters&view=overview&id=' . $requester->id )
		);
	}

	// Redirect and exit.
	wp_safe_redirect( $url );
	exit;
}
add_action( 'intercessor_requester-primary-email', 'intercessor_set_requester_primary_email', 10 );

/**
 * Save a requester note being added
 *
 * @since  0.9.5
 *
 * @param  array $args The $_POST array being passed
 *
 * @return int|void
 */
function intercessor_requester_save_note( $args ) {

	$requester_view_role = apply_filters( 'intercessor_view_requesters_role', 'view_prayer_reports' );

	// Bail, if current user has no rights to be here.
	if ( ! is_admin() || ! current_user_can( $requester_view_role ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this requester.', 'intercessor' ) );
	}

	// Bail if no arguments passed to the function.
	if ( empty( $args ) ) {
		return;
	}

	// Sanitize inputs.
	$requester_note = trim( sanitize_text_field( $args['requester_note'] ) );
	$requester_id   = (int)$args['requester_id'];
	$nonce          = $args['add_requester_note_nonce'];

	// Bail if nonce did not verify.
	if ( ! wp_verify_nonce( $nonce, 'add-requester-note' ) ) {
		wp_die( esc_html__( 'Cheatin\' eh?!', 'intercessor' ) );
	}

	// Display error if the note is empty.
	if ( empty( $requester_note ) ) {
		intercessor_set_error( 'empty-requester-note', esc_html__( 'A note is required', 'intercessor' ) );
	}

	// Bail if error found.
	if ( intercessor_get_errors() ) {
		return;
	}

	$requester = intercessor_process_item( 'requester', 'get', $requester_id, false );
	$new_note  = $requester->add_note( $requester_note );

	/**
	 * Fires before a new note is added to the requester.
	 *
	 * @param int    $requester_id Requester ID.
	 * @param string $new_note Add new note to the requester.
	 *
	 * @since 0.9.5
	 */
	do_action( 'intercessor_pre_insert_requester_note', $requester_id, $new_note );

	// Process addition of note to the requester.
	if ( ! empty( $new_note ) && ! empty( $requester->id ) ) {

		ob_start();
		?>
		<div class="requester-note-wrapper dashboard-comment-wrap comment-item">
			<span class="note-content-wrap">
				<?php echo stripslashes( $new_note ); ?>
			</span>
		</div>
		<?php
		$output = ob_get_contents();
		ob_end_clean();

		// Process output.
		if ( intercessor_doing_ajax() ) {
			wp_send_json( $output );
		}

		// Return the new note.
		return $new_note;

	}

	return false;

}
add_action( 'intercessor_add-requester-note', 'intercessor_requester_save_note', 10, 1 );

/**
 * Delete a requester
 *
 * @since  0.9.5
 *
 * @param  array $args The $_POST array being passed
 *
 * @return void Whether it was a successful deletion
 */
function intercessor_requester_delete( $args = [] ) {

	$requester_edit_role = apply_filters( 'intercessor_edit_requesters_role', 'edit_prayers' );

	if ( ! is_admin() || ! current_user_can( $requester_edit_role ) ) {
		wp_die( esc_html__( 'You do not have permission to delete this requester.', 'intercessor' ) );
	}

	if ( empty( $args ) ) {
		return;
	}

	$requester_id = (int)$args['requester_id'];
	$confirm      = ! empty( $args['intercessor-requester-delete-confirm'] ) ? true : false;
	$remove_data  = ! empty( $args['intercessor-requester-delete-records'] ) ? true : false;
	$nonce        = $args['_wpnonce'];

	if ( ! wp_verify_nonce( $nonce, 'delete-requester' ) ) {
		wp_die( esc_html__( 'Cheatin\' eh?!', 'intercessor' ) );
	}

	if ( ! $confirm ) {
		intercessor_set_error( 'requester-delete-no-confirm', esc_html__( 'Please confirm you want to delete this requester', 'intercessor' ) );
	}

	if ( intercessor_get_errors() ) {
		wp_redirect( admin_url( 'admin.php?page=intercessor-requesters&view=overview&id=' . $requester_id ) );
		exit;
	}

	$requester = intercessor_process_item( 'requester', 'get', $requester_id, false );

	/**
	 * Runs before a requester is deleted
	 *
	 * @since 0.9.5
	 */
	do_action( 'intercessor_pre_delete_requester', $requester_id, $confirm, $remove_data );

	$success = false;

	if ( $requester->id > 0 ) {

		$prayer_ids = explode( ',', $requester->prayer_ids );
		$success    = intercessor_process_item( 'requester', 'delete', $requester->id, false );

		if ( $success ) {
			if ( $remove_data ) {
				// Destroy the requesters' prayer(s) and associated meta(s).
				foreach ( $prayer_ids as $prayer_id ) {
					intercessor_process_item( 'prayer', 'delete', $prayer_id, false );
				}
			}

			$redirect = admin_url( 'admin.php?page=intercessor-requesters&intercessor-message=requester-deleted' );
		} else {
			intercessor_set_error( 'intercessor-requester-delete-failed', esc_html__( 'Error deleting requester', 'intercessor' ) );
			$redirect = admin_url( 'admin.php?page=intercessor-requesters&view=delete&id=' . $requester_id );
		}

	} else {

		intercessor_set_error( 'intercessor-requester-delete-invalid-id', esc_html__( 'Invalid Requester ID', 'intercessor' ) );
		$redirect = admin_url( 'admin.php?page=intercessor-requesters' );

	}

	wp_redirect( $redirect );
	exit;

}
add_action( 'intercessor_delete_requester', 'intercessor_requester_delete', 10, 1 );

/**
 * Disconnect a user ID from a requester
 *
 * @since  0.9.5
 *
 * @param  array $args Array of arguments
 *
 * @return void|bool        If the disconnect was sucessful
 */
function intercessor_disconnect_requester_user_id( $args = [] ) {

	$requester_edit_role = apply_filters( 'intercessor_edit_requesters_role', 'edit_prayers' );

	if ( ! is_admin() || ! current_user_can( $requester_edit_role ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this requester.', 'intercessor' ) );
	}

	if ( empty( $args ) ) {
		return;
	}

	$requester_id   = (int)$args['requester_id'];
	$nonce         = $args['_wpnonce'];

	if ( ! wp_verify_nonce( $nonce, 'edit-requester' ) ) {
		wp_die( esc_html__( 'Cheatin\' eh?!', 'intercessor' ) );
	}

	$requester = intercessor_process_item( 'requester', 'get', $requester_id, false );
	if ( empty( $requester->id ) ) {
		return false;
	}

	/**
	 * Runs before a User ID is disconnected from a requester
	 *
	 * @since 0.9.5
	 */
	do_action( 'intercessor_pre_requester_disconnect_user_id', $requester_id, $requester->user_id );

	$requester_args = array( 'user_id' => 0 );

	if ( $requester->update( $requester_args ) ) {
		global $wpdb;

		if ( ! empty( $requester->prayer_ids ) ) {
			$wpdb->query( "UPDATE $wpdb->postmeta SET meta_value = 0 WHERE meta_key = '_intercessor_prayer_user_id' AND post_id IN ( $requester->prayer_ids )" );
		}

		$output['success'] = true;

	} else {

		$output['success'] = false;
		intercessor_set_error( 'intercessor-disconnect-user-fail', esc_html__( 'Failed to disconnect user from requester', 'intercessor' ) );
	}

	/**
	 * Runs after a User ID is disconnected from a requester
	 *
	 * @since 0.9.5
	 */
	do_action( 'intercessor_post_requester_disconnect_user_id', $requester_id );

	if ( intercessor_doing_ajax() ) {
		wp_send_json( $output );
	}

	return $output;

}
add_action( 'intercessor_disconnect-userid', 'intercessor_disconnect_requester_user_id', 10, 1 );

/**
 * Register the reset single requester stats batch processor
 * @since  0.9.5
 */
function intercessor_register_batch_single_requester_recount_tool() {
	add_action( 'intercessor_batch_export_class_include', 'intercessor_include_single_requester_recount_tool_batch_processer', 10, 1 );
}
add_action( 'intercessor_register_batch_exporter', 'intercessor_register_batch_single_requester_recount_tool', 10 );


/**
 * Loads the tools batch processing class for recounting stats for a single requester
 *
 * @since  0.9.5
 * @param string $class The class being requested to run for the batch export.
 * @return void
 */
function intercessor_include_single_requester_recount_tool_batch_processer( $class ) {
	if ( '\Intercessor\Recount_Requester_Stats' === $class ) {
		require_once INTERCESSOR_DIR . 'src/Admin/Tools/Recount_Requester_Stats.php';
	}
}
