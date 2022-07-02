<?php
/**
 * Prayer Request Functions
 *
 * @package     Intercessor
 * @subpackage  Functions/Form
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     http://opensource.org/licenses/gpl-3.0php GNU Public License
 * @since       0.9.5
 */

use Intercessor\Session;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'intercessor_prayer_request_check_existing_email' ) ) {
	/**
	 * Verify that when a logged in user makes a prayer that the email address used doesn't belong to a different requester
	 *
	 * @param array $valid_data Validated data submitted for the prayer.
	 *
	 * @return void
	 * @since  0.9.5
	 */
	function intercessor_prayer_request_check_existing_email( array $valid_data ) {

		// Verify that the email address belongs to this requester.
		if ( is_user_logged_in() ) {

			$email     = strtolower( $valid_data['logged_in_user']['user_email'] );
			$requester = new \Intercessor\Requester( get_current_user_id(), true );

			// If this email address is not registered with this requester, see if it belongs to any other requester.
			if ( strtolower( $requester->email ) !== $email && ( is_array( $requester->emails ) && ! in_array( $email, array_map( 'strtolower', $requester->emails ) ) ) ) {
				$found_requester = new \Intercessor\Requester( $email );
				if ( $found_requester->id > 0 ) {
					intercessor_set_error( 'intercessor-requester-email-exists', sprintf( __( 'The email address %s is already in use.', 'intercessor' ), $email ) );
				}
			}


		}

	}
}

/**
 * Process the prayer request login form
 *
 * @access      private
 * @since       0.9.5
 * @return      void
 */
function intercessor_process_prayer_login() {

	$is_ajax = isset( $_POST['intercessor_ajax'] );

	$user_data = intercessor_request_form_validate_user_login();

	if ( intercessor_get_errors() || $user_data['user_id'] < 1 ) {
		if ( $is_ajax ) {
			do_action( 'intercessor_ajax_prayer_request_errors' );
			intercessor_die();
		} else {
			wp_redirect( $_SERVER['HTTP_REFERER'] );
			exit;
		}
	}

	intercessor_log_user_in( $user_data['user_id'], $user_data['user_login'], $user_data['user_pass'] );

	if ( $is_ajax ) {
		echo 'success';
		intercessor_die();
	} else {
		wp_redirect( intercessor_get_prayer_request_uri( $_SERVER['QUERY_STRING'] ) );
	}
}

/**
 * Prayer Request Form Validate Fields
 *
 * @access      private
 * @since       0.9.5
 * @return      bool|array
 */
function intercessor_request_form_validate_fields() {
	// Bail if there is no $_POST.
	if ( empty( $_POST ) ) {
		return false;
	}

	// Start an array to collect valid data.
	$valid_data = [
		'prayer_title'     => intercessor_validate_form_title(),
		'prayer_message'   => intercessor_validate_form_message(),
		'need_new_user'    => false,     // New user flag.
		'need_user_login'  => false,     // Login user flag.
		'logged_user_data' => [],   // Logged user collected data.
		'new_user_data'    => [],   // New user collected data.
		'login_user_data'  => [],   // Login user collected data.
		'guest_user_data'  => [],   // Guest user collected data.
		'share'            => ! empty( $_POST['intercessor_share'] ) ? intercessor_clean( $_POST['intercessor_share'] ) : '',
		'notify'           => (int) ! empty( $_POST['intercessor_notify'] ) ? intercessor_clean( $_POST['intercessor_notify'] ) : '',
		'terms'            => intercessor_request_form_validate_agree_to_terms(),
	];

	// Terms of service.
	if ( ! intercessor_get_option( 'show_agree_to_terms' ) ) {
		unset( $valid_data['terms'] );
	}

	// Validate privacy policy if specified.
	$privacy    = intercessor_get_option( 'show_privacy_policy' );
	$submission = intercessor_get_option( 'show_on_submission' );
	if ( $privacy && $submission ) {
		$valid_data['privacy'] = intercessor_request_form_validate_agree_to_privacy_policy();
	}

	if ( is_user_logged_in() ) {
		// Collect logged in user data.
		$valid_data['logged_in_user'] = intercessor_request_form_validate_logged_in_user();
	} elseif ( isset( $_POST['intercessor-request-var'] ) && 'needs-to-register' === $_POST['intercessor-request-var'] ) {
		// Set new user registration as required.
		$valid_data['need_new_user'] = true;

		// Validate new user data.
		$valid_data['new_user_data'] = intercessor_request_form_validate_new_user();
		// Check if login validation is needed.
	} elseif ( isset( $_POST['intercessor-request-var'] ) && 'needs-to-login' === $_POST['intercessor-request-var'] ) {
		// Set user login as required.
		$valid_data['need_user_login'] = true;

		// Validate users login info.
		$valid_data['login_user_data'] = intercessor_request_form_validate_user_login();
	} else {
		// Not registering or logging in, so setup guest user data.
		$valid_data['guest_user_data'] = intercessor_request_form_validate_guest_user();
	}

	// Return collected data.
	return $valid_data;
}

/**
 * Prayer Request Form Validate Title
 *
 * @access      private
 * @since       0.9.5
 * @return      string
 */
function intercessor_validate_form_title() {

	$form_title = isset( $_POST['intercessor_title'] ) ? intercessor_clean( $_POST['intercessor_title'] ) : '';

	return stripslashes( $form_title );
}

/**
 * Prayer Request Form Validate Message
 *
 * @access      private
 * @since       0.9.5
 * @return      string
 */
function intercessor_validate_form_message() {
	$form_message = isset( $_POST['intercessor_message'] ) ? intercessor_sanitize_textarea( $_POST['intercessor_message'] ) : '';

	return stripslashes( $form_message );
}

/**
 * Prayer Request Form Validate Agree To Terms
 *
 * @access      private
 * @since       0.9.5
 * @return      int
 */
function intercessor_request_form_validate_agree_to_terms() {
	$terms = ! empty( $_POST['intercessor_agree_to_terms'] )
		? intercessor_clean( $_POST['intercessor_agree_to_terms'] )
		: 0;

	// User did not agree.
	if ( ! $terms ) {
		intercessor_set_error(
			'agree_to_terms',
			apply_filters(
				'intercessor_agree_to_terms_text',
				esc_html__( 'You must agree to the terms of use', 'intercessor' )
			)
		);
	}

	return $terms;
}

/**
 * Prayer Form Validate Agree To Privacy Policy
 *
 * @since  0.9.5
 * @return int
 */
function intercessor_request_form_validate_agree_to_privacy_policy() {
	$privacy = ! empty( $_POST['intercessor_agree_to_privacy_policy'] )
		? intercessor_clean( $_POST['intercessor_agree_to_privacy_policy'] )
		: 0;
	// Validate agree to privacy policy.
	if ( ! $privacy ) {
		// User did not agree.
		intercessor_set_error(
			'agree_to_privacy_policy',
			apply_filters(
				'intercessor_agree_to_privacy_policy_text',
				esc_html__( 'You must agree to the privacy policy', 'intercessor' )
			)
		);
	}

	return $privacy;
}

/**
 * Prayer Request Form Required Fields
 *
 * @access      private
 * @since       0.9.5
 * @return      array
 */
function intercessor_request_form_required_fields() {

	$settings = new \Intercessor\Admin\Settings();

	// Required fields.
	$required_fields = [
		'intercessor_email'            => [
			'error_id'      => 'invalid_email',
			'error_message' => esc_html__( 'Please enter a valid email address', 'intercessor' ),
		],
		'intercessor_first'            => [
			'error_id'      => 'invalid_first_name',
			'error_message' => esc_html__( 'Please enter your first name', 'intercessor' ),
		],
		'intercessor_last'             => [
			'error_id'      => 'invalid_last_name',
			'error_message' => esc_html__( 'Please enter your last name', 'intercessor' ),
		],
		'intercessor_title'            => [
			'error_id'      => 'invalid_prayer_title',
			'error_message' => esc_html__( 'Please enter the title of your prayer request.', 'intercessor' )
		],
		'intercessor_message'          => [
			'error_id'      => 'invalid_prayer_message',
			'error_message' => esc_html__( 'Please enter your prayer request', 'intercessor' )
		],
		'intercessor_share'            => [
			'error_id'      => 'invalid_prayer_share',
			'error_message' => esc_html__( 'Please choose how we share your prayer request', 'intercessor' )
		],
		'intercessor_agree_to_privacy' => [
			'error_id'      => 'invalid_privacy_policy',
			'error_message' => esc_html__( 'Please agree to our privacy policy before submitting your prayer request', 'intercessor' )
		],
		'intercessor_agree_to_terms'   => [
			'error_id'      => 'invalid_prayer_terms',
			'error_message' => esc_html__( 'Please agree to our terms of service before submitting your prayer request', 'intercessor' )
		],
	];

	// Only require terms of service or privacy policy if specified in settings.
	$terms   = intercessor_get_option( 'show_agree_to_terms' );
	$privacy = intercessor_get_option( 'show_privacy_policy' );
	if ( ! $terms ) {
		unset( $required_fields['intercessor_agree_to_terms'] );
	}

	if ( ! $privacy ) {
		unset( $required_fields['intercessor_agree_to_privacy'] );
	}

	return apply_filters( 'intercessor_request_form_required_fields', $required_fields );
}

/**
 * Prayer Request Form Validate Logged In User
 *
 * @access      private
 * @since       0.9.5
 * @return      array
 */
function intercessor_request_form_validate_logged_in_user() {
	global $user_ID;

	// Start empty array to collect valid user data
	$valid_user_data = array(
		// Assume there will be errors
		'user_id' => -1
	);

	// Verify there is a user_ID
	if ( $user_ID > 0 ) {
		// Get the logged in user data
		$user_data = get_userdata( $user_ID );

		// Get required fields to loop through
		$fields = intercessor_request_form_required_fields();

		// Loop through required fields and show error messages
		foreach ( $fields as $field_name => $value ) {
			if ( in_array( $value, $fields ) && empty( $_POST[ $field_name ] ) ) {
				intercessor_set_error( $value['error_id'], $value['error_message'] );
			}
		}

		// Verify data
		if ( $user_data ) {
			// Collected logged in user data
			$valid_user_data = array(
				'user_id'    => $user_ID,
				'user_email' => isset( $_POST['intercessor_email'] ) ? sanitize_email( $_POST['intercessor_email'] ) : $user_data->user_email,
				'user_first' => isset( $_POST['intercessor_first'] ) && ! empty( $_POST['intercessor_first'] ) ? sanitize_text_field( $_POST['intercessor_first'] ) : $user_data->first_name,
				'user_last'  => isset( $_POST['intercessor_last'] ) && ! empty( $_POST['intercessor_last']  ) ? sanitize_text_field( $_POST['intercessor_last']  ) : $user_data->last_name,
			);

			if ( ! is_email( $valid_user_data['user_email'] ) ) {
				intercessor_set_error( 'email_invalid', esc_html__( 'Invalid email', 'intercessor' ) );
			}

		} else {
			// Set invalid user error
			intercessor_set_error( 'invalid_user', esc_html__( 'The user information is invalid', 'intercessor' ) );
		}
	}

	// Return user data
	return $valid_user_data;
}

/**
 * Prayer Request Form Validate New User
 *
 * @access      private
 * @since       0.9.5
 * @return      array
 */
function intercessor_request_form_validate_new_user() {
	$registering_new_user = false;

	/** Sanitize **************************************************************/

	// Sanitize first name
	$user_first = isset( $_POST['intercessor_first'] )
		? sanitize_text_field( $_POST['intercessor_first'] )
		: '';

	// Sanitize last name
	$user_last = isset( $_POST['intercessor_last'] )
		? sanitize_text_field( $_POST['intercessor_last'] )
		: '';

	// Sanitize user login (not strict-mode for back-compat)
	$user_login   = isset( $_POST['intercessor_user_login'] )
		? preg_replace( '/\s+/', '', sanitize_user( $_POST['intercessor_user_login'], false ) )
		: false;

	// Sanitize email address (allowed formatting only)
	$user_email   = isset( $_POST['intercessor_email'] )
		? sanitize_email( $_POST['intercessor_email'] )
		: false;

	// Trim front/back whitespace from password (don't alter characters)
	$user_pass    = isset( $_POST['intercessor_user_pass'] )
		? trim( $_POST['intercessor_user_pass'] )
		: false;

	// Trim front/back whitespace from password (don't alter characters)
	$pass_confirm = isset( $_POST['intercessor_user_pass_confirm'] )
		? trim( $_POST['intercessor_user_pass_confirm'] )
		: false;

	/** Required Fields *******************************************************/

	// Get required fields to loop through
	$fields = intercessor_request_form_required_fields();

	// Loop through required fields and provide error messages if missing
	foreach ( $fields as $field_name => $value ) {
		if ( empty( $_POST[ $field_name ] ) && ! empty( $value['error_id'] ) && ! empty( $value['error_message'] ) ) {
			intercessor_set_error( $value['error_id'], $value['error_message'] );
		}
	}

	/** Setup Userdata ********************************************************/

	// Start an empty array to collect valid user data
	$valid_user_data = array(
		'user_id'    => 0,
		'user_first' => $user_first,
		'user_last'  => $user_last
	);

	/** Check Login ***********************************************************/

	// Check if we have a username to register
	if ( ! empty( $user_login ) && strlen( $user_login ) > 0 ) {
		$registering_new_user = true;

		// Error if username already exists
		if ( username_exists( $user_login ) ) {
			intercessor_set_error( 'username_unavailable', esc_html__( 'Username already exists', 'intercessor' ) );

		// Error if username is not valid
		} elseif ( ! intercessor_validate_username( $user_login ) ) {
			is_multisite()
				? intercessor_set_error( 'username_invalid', esc_html__( 'Invalid username. Only lowercase letters (a-z) and numbers are allowed', 'intercessor' ) )
				: intercessor_set_error( 'username_invalid', esc_html__( 'Invalid username',                                                       'intercessor' ) );

		// Add login to valid user data
		} else {
			$valid_user_data['user_login'] = $user_login;
		}

	// Error if users are required to register and no login was provided
	} elseif ( intercessor_account_required() ) {
		intercessor_set_error( 'registration_required', esc_html__( 'You must register or login to complete your purchase', 'intercessor' ) );
	}

	/** Check Email ***********************************************************/

	// Check if we have an email to verify
	if ( ! empty( $user_email ) && strlen( $user_email ) > 0 ) {

		// Error if invalid email address
		if ( ! is_email( $user_email ) ) {
			intercessor_set_error( 'email_invalid', esc_html__( 'Invalid email', 'intercessor' ) );

		// Email address is unsafe (multisite only)
		} elseif ( is_multisite() && is_email_address_unsafe( $user_email ) ) {
			intercessor_set_error( 'email_unsafe', esc_html__( 'You cannot use that email address to signup at this time.', 'intercessor' ) );

		// Check if email exists
		} elseif ( ( true === $registering_new_user ) && email_exists( $user_email ) ) {
			intercessor_set_error( 'email_used', esc_html__( 'Email already used. Login or use a different email to complete your purchase.', 'intercessor' ) );

		// Add email to valid user data
		} else {
			$valid_user_data['user_email'] = $user_email;
		}

	// Error if no email address was provided
	} else {
		intercessor_set_error( 'email_empty', esc_html__( 'Enter an email', 'intercessor' ) );
	}

	/** Check Password ********************************************************/

	// Check password
	if ( ! empty( $user_pass ) && ! empty( $pass_confirm ) ) {

		// Error if passwords do not match
		if ( 0 !== strcmp( $user_pass, $pass_confirm ) ) {
			intercessor_set_error( 'password_mismatch', esc_html__( 'Passwords do not match', 'intercessor' ) );

		// Add password to valid user data
		} else {
			$valid_user_data['user_pass'] = $user_pass;
		}

	// Error if no password when signing up
	} elseif ( true === $registering_new_user ) {
		if ( empty( $user_pass ) ) {
			intercessor_set_error( 'password_empty',     esc_html__( 'Enter a password', 'intercessor' ) );
		} elseif ( empty( $pass_confirm ) ) {
			intercessor_set_error( 'confirmation_empty', esc_html__( 'Confirm your password', 'intercessor' ) );
		}
	}

	// Cast as array and return.
	return (array) $valid_user_data;
}

/**
 * Prayer Request Form Validate User Login
 *
 * @access      private
 * @since       0.9.5
 * @return      array
 */
function intercessor_request_form_validate_user_login() {

	// Start an array to collect valid user data
	$valid_user_data = array(
		// Assume there will be errors
		'user_id' => -1
	);

	// Username
	if ( empty( $_POST['intercessor_user_login'] ) && intercessor_account_required() ) {
		intercessor_set_error( 'must_log_in', esc_html__( 'You must login or register to complete your prayer', 'intercessor' ) );
		return $valid_user_data;
	}

	$login_or_email = strip_tags( $_POST['intercessor_user_login'] );

	if ( is_email( $login_or_email ) ) {
		// Get the user by email
		$user_data = get_user_by( 'email', $login_or_email );
	} else {
		// Get the user by login
		$user_data = get_user_by( 'login', $login_or_email );
	}

	// Check if user exists
	if ( $user_data ) {
		// Get password
		$user_pass = isset( $_POST["intercessor_user_pass"] ) ? $_POST["intercessor_user_pass"] : false;

		// Check user_pass
		if ( $user_pass ) {
			// Check if password is valid
			if ( ! wp_check_password( $user_pass, $user_data->user_pass, $user_data->ID ) ) {
				// Incorrect password
				intercessor_set_error(
					'password_incorrect',
					sprintf(
						__( 'The password you entered is incorrect. %sReset Password%s', 'intercessor' ),
						'<a href="' . wp_lostpassword_url( intercessor_get_prayer_request_uri() ) . '">',
						'</a>'
					)
				);
				// All is correct
			} else {
				// Repopulate the valid user data array
				$valid_user_data = array(
					'user_id' => $user_data->ID,
					'user_login' => $user_data->user_login,
					'user_email' => $user_data->user_email,
					'user_first' => $user_data->first_name,
					'user_last' => $user_data->last_name,
					'user_pass' => $user_pass,
				);
			}
		} else {
			// Empty password
			intercessor_set_error( 'password_empty', esc_html__( 'Enter a password', 'intercessor' ) );
		}
	} else {
		// no username
		intercessor_set_error( 'username_incorrect', esc_html__( 'The username you entered does not exist', 'intercessor' ) );
	}

	return $valid_user_data;
}

/**
 * Prayer Request Form Validate Guest User
 *
 * @access  private
 * @since  0.9.5
 * @return  array
 */
function intercessor_request_form_validate_guest_user() {
	// Start an array to collect valid user data
	$valid_user_data = array(
		// Set a default id for guests
		'user_id' => 0,
	);

	// Show error message if user must be logged in
	if ( intercessor_account_required() ) {
		intercessor_set_error( 'logged_in_only', esc_html__( 'You must be logged into an account to prayer', 'intercessor' ) );
	}

	// Get the guest email
	$guest_email = isset( $_POST['intercessor_email'] ) ? $_POST['intercessor_email'] : false;

	// Check email
	if ( $guest_email && strlen( $guest_email ) > 0 ) {
		// Validate email
		if ( ! is_email( $guest_email ) ) {
			// Invalid email
			intercessor_set_error( 'email_invalid', esc_html__( 'Invalid email', 'intercessor' ) );
		} else {
			// All is good to go
			$valid_user_data['user_email'] = $guest_email;
		}
	} else {
		// No email
		intercessor_set_error( 'email_empty', esc_html__( 'Enter an email', 'intercessor' ) );
	}

	// Get required fields to loop through
	$fields = intercessor_request_form_required_fields();

	// Loop through required fields and show error messages
	foreach ( $fields as $field_name => $value ) {
		if ( in_array( $value, $fields ) && empty( $_POST[ $field_name ] ) ) {
			intercessor_set_error( $value['error_id'], $value['error_message'] );
		}
	}

	return $valid_user_data;
}

/**
 * Register And Login New User
 *
 * @param array   $user_data
 *
 * @access  private
 * @since  0.9.5
 * @return  integer
 */
function intercessor_register_and_login_new_user( $user_data = [] ) {
	// Verify the array
	if ( empty( $user_data ) )
		return -1;

	if ( intercessor_get_errors() )
		return -1;

	$user_args = apply_filters( 'intercessor_insert_user_args', array(
		'user_login'      => isset( $user_data['user_login'] ) ? $user_data['user_login'] : '',
		'user_pass'       => isset( $user_data['user_pass'] )  ? $user_data['user_pass']  : '',
		'user_email'      => isset( $user_data['user_email'] ) ? $user_data['user_email'] : '',
		'first_name'      => isset( $user_data['user_first'] ) ? $user_data['user_first'] : '',
		'last_name'       => isset( $user_data['user_last'] )  ? $user_data['user_last']  : '',
		'user_registered' => date( 'Y-m-d H:i:s' ),
		'role'            => 'requester'
	), $user_data );

	// Insert new user
	$user_id = wp_insert_user( $user_args );

	// Validate inserted user
	if ( is_wp_error( $user_id ) )
		return -1;

	// Allow themes and plugins to filter the user data
	$user_data = apply_filters( 'intercessor_insert_user_data', $user_data, $user_args );

	// Allow themes and plugins to hook
	do_action( 'intercessor_insert_user', $user_id, $user_data );

	// Login new user
	intercessor_log_user_in( $user_id, $user_data['user_login'], $user_data['user_pass'] );

	// Return user id
	return $user_id;
}

/**
 * Get Prayer Request Form User
 *
 * @param array   $valid_data
 *
 * @access  private
 * @since  0.9.5
 * @return bool
 */
function intercessor_get_prayer_form_user( $valid_data = [] ) {
	// Initialize user
	$user    = false;
	$is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

	if ( $is_ajax ) {
		// Do not create or login the user during the ajax submission (check for errors only)
		return true;
	} else if ( is_user_logged_in() ) {
		// Set the valid user as the logged in collected data
		$user = $valid_data['logged_in_user'];
	} else if ( $valid_data['need_new_user'] === true || $valid_data['need_user_login'] === true  ) {
		// New user registration
		if ( $valid_data['need_new_user'] === true ) {
			// Set user
			$user = $valid_data['new_user_data'];
			// Register and login new user
			$user['user_id'] = intercessor_register_and_login_new_user( $user );
			// User login
		} else if ( $valid_data['need_user_login'] === true  && ! $is_ajax ) {
			/*
			 * The login form is now processed in the intercessor_process_prayer_login() function.
			 * This is still here for backwards compatibility.
			 * This also allows the old login process to still work if a user removes the
			 * prayer request login submit button.
			 *
			 * This also ensures that the requester is logged in correctly if they click "Submit Prayer"
			 * instead of submitting the login form, meaning the requester is logged in during the prayer process.
			 */

			// Set user
			$user = $valid_data['login_user_data'];

			// Login user
			if ( empty( $user ) || $user['user_id'] === -1 ) {
				intercessor_set_error( 'invalid_user', esc_html__( 'The user information is invalid', 'intercessor' ) );
				return false;
			} else {
				intercessor_log_user_in( $user['user_id'], $user['user_login'], $user['user_pass'] );
			}
		}
	}

	// Check guest prayer request
	if ( false === $user && false === intercessor_account_required() ) {
		// Set user
		$user = $valid_data['guest_user_data'];
	}

	// Verify we have an user
	if ( false === $user || empty( $user ) ) {
		// Return false
		return false;
	}

	// Get user first name
	if ( ! isset( $user['user_first'] ) || strlen( trim( $user['user_first'] ) ) < 1 ) {
		$user['user_first'] = isset( $_POST["intercessor_first"] ) ? strip_tags( trim( $_POST["intercessor_first"] ) ) : '';
	}

	// Get user last name
	if ( ! isset( $user['user_last'] ) || strlen( trim( $user['user_last'] ) ) < 1 ) {
		$user['user_last'] = isset( $_POST["intercessor_last"] ) ? strip_tags( trim( $_POST["intercessor_last"] ) ) : '';
	}

	// Return valid user
	return $user;
}

/**
 * Check the prayer to ensure a banned email is not allowed through
 *
 * @since       0.9.5
 *
 * @param $posted
 *
 * @return      void
 */
function intercessor_check_prayer_email( $posted ) {

	$banned = intercessor_get_banned_emails();

	if ( empty( $banned ) ) {
		return;
	}

	$user_emails = array( $posted['intercessor_email'] );
	if ( is_user_logged_in() ) {

		// The user is logged in, check that their account email is not banned.
		$user_data     = get_userdata( get_current_user_id() );
		$user_emails[] = $user_data->user_email;

	} elseif ( isset( $posted['intercessor-request-var'] ) && $posted['intercessor-request-var'] == 'needs-to-login' ) {

		// The user is logging in, check that their email is not banned.
		if ( $user_data = get_user_by( 'login', $posted['intercessor_user_login'] ) ) {
			$user_emails[] = $user_data->user_email;
		}

	}

	foreach ( $user_emails as $email ) {
		if ( intercessor_is_email_banned( $email ) ) {
			// Set an error and give the requester a general error (don't alert them that they were banned).
			intercessor_set_error( 'email_banned', esc_html__( 'An internal error has occurred, please try again or contact support.', 'intercessor' ) );
			break;
		}
	}

}

if ( ! function_exists( 'intercessor_set_prayer_session' ) ):
	/**
	 * Set prayer request session for non logged-in users.
	 *
	 * @param array $prayer_data
	 *
	 * @since 1.0.0
	 */
	function intercessor_set_prayer_session( $prayer_data = [] ) {
	    $session = new Session();
		$session->set( 'intercessor_request', $prayer_data );
	}

endif;

/**
 * Retrieve the Prayer Request Data from Session
 *
 * @since 0.9.5
 * @return mixed array | false
 */
function intercessor_get_prayer_session() {
    $session = new Session();
    $session->get( 'intercessor_request' );
}

/**
 * Determines if we're currently on the Prayer Request page
 *
 * @since 1.0.9.5
 * @return bool True if on the Prayer Request page, false otherwise
 */
function intercessor_is_prayer_request_form_page() {
	global $wp_query, $is_prayer_request;

	$is_object_set     = isset( $wp_query->queried_object );
	$is_object_id_set  = isset( $wp_query->queried_object_id );
	$is_prayer_request = is_page( intercessor_get_option( 'form_page' ) );

	if ( ! $is_object_set ) {
		unset( $wp_query->queried_object );
	}

	if ( ! $is_object_id_set ) {
		unset( $wp_query->queried_object_id );
	}

	return apply_filters( 'intercessor_is_prayer_request_form_page', $is_prayer_request );
}

/**
 * Get the URL of the Prayer Request page
 *
 * @since 0.9.5
 * @param array $args Extra query args to add to the URI
 * @return mixed Full URL to the prayer request page, if present | null if it doesn't exist
 */
function intercessor_get_prayer_request_uri( $args = [] ) {
	$uri      = intercessor_get_option( 'form_page', false );
	$uri      = isset( $uri ) ? get_permalink( $uri ) : NULL;

	if ( ! empty( $args ) ) {
		// Check for backward compatibility
		if ( is_string( $args ) )
			$args = str_replace( '?', '', $args );

		$args = wp_parse_args( $args );
		$uri  = add_query_arg( $args, $uri );
	}

	$scheme   = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'https' : 'admin';
	$ajax_url = admin_url( 'admin-ajax.php', $scheme );

	if ( ( ! preg_match( '/^https/', $uri )
		&& preg_match( '/^https/', $ajax_url )
		&& ! intercessor_is_ajax_disabled() )
		|| intercessor_is_ssl_enforced() ) {
			$uri = preg_replace( '/^http:/', 'https:', $uri );
	}


	if ( ( ! preg_match( '/^https/', $uri )  || intercessor_is_ssl_enforced()  ) ) {
		$uri = preg_replace( '/^http:/', 'https:', $uri );
	}

	return apply_filters( 'intercessor_get_prayer_request_uri', $uri );
}

/**
 * Retrieve the Listing page URI
 *
 * @since       0.9.5
 * @return      string
*/
function intercessor_get_listing_page_uri( $query_string = null ) {
	$page_id  = intercessor_get_option( 'prayers_page', 0 );
	$page_id  = absint( $page_id );

	$listing_page = get_permalink( $page_id );

	if ( $query_string ) {
		$listing_page .= $query_string;
	}

	return apply_filters( 'intercessor_get_listing_page_uri', $listing_page );
}

if ( ! function_exists( 'intercessor_get_history_page_uri' ) ) {
	/**
	 * Retrieve the History page URI.
	 *
	 * @param null $query_string Query string.
	 *
	 * @return      string
	 * @since       0.9.5
	 */
	function intercessor_get_history_page_uri( $query_string = null ) {
		$page_id  = intercessor_get_option( 'history_page', 0 );
		$page_id  = absint( $page_id );

		$ipr_history_page = esc_url( get_permalink( $page_id ) );

		if ( $query_string ) {
			$ipr_history_page .= $query_string;
		}

		return apply_filters( 'intercessor_get_history_page_uri', $ipr_history_page );
	}
}

/**
 * Determines if we're currently on the Listing page.
 *
 * @since 0.9.5.
 * @return bool True if on the Listing page, false otherwise.
 */
function intercessor_is_listing_page() {
	global $ipr_listing_page;

	$ipr_listing_page = intercessor_get_option( 'prayers_page', false );
	$ipr_listing_page = isset( $ipr_listing_page ) ? is_page( $ipr_listing_page ) : false;

	return apply_filters( 'intercessor_is_listing_page', $ipr_listing_page );
}

/**
 * Determines if we're currently on the history page.
 *
 * @since 0.9.5.
 * @return bool True if on the Listing page, false otherwise.
 */
function intercessor_is_prayer_history_page() {
	global $ipr_history_page;

	$ipr_history_page = intercessor_get_option( 'history_page', false );
	$ipr_history_page = isset( $ipr_history_page ) ? is_page( $ipr_history_page ) : false;

	return apply_filters( 'intercessor_is_prayer_history_page', $ipr_history_page);
}

/**
 * Checks whether AJAX is disabled.
 *
 * @since 0.9.5
 * @return bool True when IPR AJAX is disabled, false otherwise.
 */
function intercessor_is_ajax_disabled(): bool {
	return apply_filters( 'intercessor_is_ajax_disabled', false );
}

/**
 * Get AJAX URL
 *
 * @since 0.9.5
 * @return string URL to the AJAX file to call during AJAX requests.
 */
function intercessor_get_ajax_url(): string {
	$scheme = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'https' : 'admin';

	$current_url = intercessor_get_current_page_url();
	$ajax_url    = admin_url( 'admin-ajax.php', $scheme );

	if ( preg_match( '/^https/', $current_url ) && ! preg_match( '/^https/', $ajax_url ) ) {
		$ajax_url = preg_replace( '/^http/', 'https', $ajax_url );
	}

	return apply_filters( 'intercessor_ajax_url', $ajax_url );
}

/**
 * Get the current page URL
 *
 * @since 0.9.5
 * @return string $page_url Current page URL
 */
function intercessor_get_current_page_url(): string {

	global $wp;

	if ( get_option( 'permalink_structure' ) ) {

		$base = trailingslashit( home_url( $wp->request ) );

	} else {

		$base = add_query_arg( $wp->query_string, '', trailingslashit( home_url( $wp->request ) ) );
		$base = remove_query_arg( array( 'post_type', 'name' ), $base );

	}

	$scheme = is_ssl() ? 'https' : 'http';
	$uri    = set_url_scheme( $base, $scheme );

	if ( is_front_page() ) {
		$uri = home_url( '/' );
	} elseif ( intercessor_is_prayer_request_form_page() ) {
		$uri = intercessor_get_prayer_request_uri();
	}

	$uri = apply_filters( 'intercessor_get_current_page_url', $uri );

	return $uri;
}

/**
 * Check if a field is required
 *
 * @param string $field Field.
 *
 * @access      public
 * @since       0.9.5
 * @return      bool
*/
function intercessor_required_fields( string $field = '' ): bool {
	$required_fields = intercessor_request_form_required_fields();
	return array_key_exists( $field, $required_fields );
}

/**
 * Retrieve an array of banned_emails.
 *
 * @since  0.9.5
 * @return array $emails Array of banned emails.
 */
function intercessor_get_banned_emails(): array {
    $banned = intercessor_get_option( 'banned_emails', [] );
    $emails = ! is_array( $banned )
        ? explode( "\n", $banned )
        : $banned;

    $emails = array_map( 'trim', $emails );

	return apply_filters( 'intercessor_get_banned_emails', $emails );
}

/**
 * Determines if an email is banned
 *
 * @since       0.9.5
 * @param string $email Email to check if is banned.
 * @return bool
 */
function intercessor_is_email_banned( string $email = '' ): bool {

	$email = trim( $email );
	if ( empty( $email ) ) {
		return false;
	}

	$email         = strtolower( $email );
	$banned_emails = intercessor_get_banned_emails();

	if ( ! is_array( $banned_emails ) || empty( $banned_emails ) ) {
		return false;
	}

	$return = false;
	foreach ( $banned_emails as $banned_email ) {

		$banned_email = strtolower( $banned_email );

		if ( is_email( $banned_email ) ) {

			// Complete email address.
			$return = ( $banned_email === $email ? true : false );

		} elseif ( strpos( $banned_email, '.' ) === 0 ) {

			// TLD block.
			$return = substr( $email, ( strlen( $banned_email ) * -1 ) ) === $banned_email;

		} else {

			// Domain block.
			$return = ( stristr( $email, $banned_email ) ? true : false );

		}

		if ( true === $return ) {
			break;
		}
	}

	return apply_filters( 'intercessor_is_email_banned', $return, $email );
}

/**
 * Determines if secure prayer request pages are enforced
 *
 * @since       0.9.5
 * @return      bool True if enforce SSL is enabled, false otherwise
 */
function intercessor_is_ssl_enforced() {
	$ssl_enforced = intercessor_get_option( 'enforce_ssl', false );
	return (bool) apply_filters( 'intercessor_is_ssl_enforced', $ssl_enforced );
}

/**
 * Handle redirections for SSL enforced prayer requests
 *
 * @since 0.9.5
 * @return void
 */
function intercessor_enforced_ssl_redirect_handler() {

	if ( ! intercessor_is_ssl_enforced() || ! intercessor_is_prayer_request_form_page() || is_admin() || is_ssl() ) {
		return;
	}

	if ( intercessor_is_prayer_request_form_page() && false !== strpos( intercessor_get_current_page_url(), 'https://' ) ) {
		return;
	}

	$uri = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

	wp_safe_redirect( $uri );
	exit;
}

/**
 * Handle rewriting asset URLs for SSL enforced prayer requests
 *
 * @since 0.9.5
 * @return void
 */
function intercessor_enforced_ssl_asset_handler() {
	if ( ! intercessor_is_ssl_enforced() || ! intercessor_is_prayer_request_form_page() || is_admin() ) {
		return;
	}

	$filters = array(
		'post_thumbnail_html',
		'wp_get_attachment_url',
		'wp_get_attachment_image_attributes',
		'wp_get_attachment_url',
		'option_stylesheet_url',
		'option_template_url',
		'script_loader_src',
		'style_loader_src',
		'template_directory_uri',
		'stylesheet_directory_uri',
		'site_url'
	);

	$filters = apply_filters( 'intercessor_enforced_ssl_asset_filters', $filters );

	foreach ( $filters as $filter ) {
		add_filter( $filter, 'intercessor_enforced_ssl_asset_filter', 1 );
	}
}

/**
 * Filter filters and convert http to https
 *
 * @since 0.9.5
 * @param mixed $content
 * @return mixed
 */
function intercessor_enforced_ssl_asset_filter( $content ) {

	if ( is_array( $content ) ) {

		$content = array_map( 'intercessor_enforced_ssl_asset_filter', $content );

	} else {

		// Detect if URL ends in a common domain suffix. We want to only affect assets
		$extension = untrailingslashit( intercessor_get_file_extension( $content ) );
		$suffixes  = array(
			'br',
			'ca',
			'cn',
			'com',
			'de',
			'dev',
			'edu',
			'fr',
			'in',
			'info',
			'jp',
			'local',
			'mobi',
			'name',
			'net',
			'nz',
			'org',
			'ru',
		);

		if ( ! in_array( $extension, $suffixes ) ) {

			$content = str_replace( 'http:', 'https:', $content );

		}

	}

	return $content;
}

if ( ! function_exists( 'intercessor_login_form' ) ) {
	/**
	 * Login Form
	 *
	 * @param string $redirect Redirect page URL
	 *
	 * @return string Login form
	*@global $post
	 *
	 * @since 0.9.5
	 */
	function intercessor_login_form( string $redirect = '' ): string {
		global $intercessor_login_redirect;

		if ( empty( $redirect ) ) {
			$redirect = intercessor_get_current_page_url();
		}

		$intercessor_login_redirect = $redirect;

		ob_start();

		intercessor_get_template_part( 'shortcode', 'login' );

		return apply_filters( 'intercessor_login_form', ob_get_clean() );
	}
}

if ( ! function_exists( 'intercessor_register_form' ) ) {
	/**
	 * Registration Form
	 *
	 * @param string $redirect Redirect page URL.
	 *
	 * @return string Register form
	 *@global $post
	 * @since 0.9.5
	 */
	function intercessor_register_form( string $redirect = '' ): string {
		global $intercessor_register_redirect;

		if ( empty( $redirect ) ) {
			$redirect = intercessor_get_current_page_url();
		}

		$intercessor_register_redirect = $redirect;

		ob_start();

		intercessor_get_template_part( 'shortcode', 'register' );

		return apply_filters( 'intercessor_register_form', ob_get_clean() );
	}
}
