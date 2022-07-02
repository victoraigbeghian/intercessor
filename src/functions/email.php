<?php
/**
 * Intercessor mails Functions
 *
 * @package     Intercessor
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       0.9.5
 */

use Intercessor\Emails;
use Intercessor\Requester;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Add an email tag
 *
 * @param string $tag Email tag to be replaced in email.
 * @param string $description Tag description.
 * @param string $func Callback function to run when email tag is found.
 * @param string $label Tag label.
 *
 * @return void
 * @since 1.0.0
 */
function intercessor_add_email_tag( string $tag = '', string $description = '', string $func = '', string $label = '' ) {
	return intercessor()->emails->add( $tag, $description, $func, $label );
}

/**
 * Remove an email tag
 *
 * @param string $tag Email tag to remove hook from.
 *
 * @return void
 * @since 0.9.5
 */
function intercessor_remove_email_tag( string $tag ) {
	return intercessor()->emails->remove( $tag );
}

/**
 * Check if $tag is a registered email tag
 *
 * @param string $tag Email tag that will be searched
 *
 * @return bool
 *@since 0.9.5
 *
 */
function intercessor_email_tag_exists( string $tag ) : bool {
	return intercessor()->emails->email_tag_exists( $tag );
}

/**
 * Get all email tags
 *
 * @return array
 * @since 0.9.5
 */
function intercessor_get_email_tags() : array {
	return intercessor()->emails->get_tags();
}

/**
 * Get a formatted HTML list of all available email tags
 *
 * @since 0.9.5
 *
 * @return string
 */
function intercessor_get_emails_tags_list() : string {
	// The list.
	$list = '';

	// Get all tags.
	$email_tags = (array) intercessor_get_email_tags();

	// Check if there are tags.
	if ( count( $email_tags ) > 0 ) {

		// Loop.
		foreach ( $email_tags as $email_tag ) {

			// Add email tag to list.
			$list .= '{' . $email_tag['tag'] . '} - ' . $email_tag['description'] . '<br/>';
		}
	}

	// Return the list.
	return $list;
}

/**
 * Search content for email tags and filter email tags through their hooks
 *
 * @param string $content   Content to search for email tags.
 * @param int    $prayer_id The prayer id
 *
 * @since 0.9.5
 *
 * @return string Content with email tags filtered out.
 */
function intercessor_do_email_tags( string $content, int $prayer_id ) : string {
	// Return content.
	return intercessor()->emails->do_tags( $content, $prayer_id );
}

/**
 * Load email tags
 *
 * @since 0.9.5
 */
function intercessor_load_email_tags() {
	do_action( 'intercessor_add_email_tags' );
}

/**
 * Add default IPR email template tags
 *
 * @since 0.9.5
 */
function intercessor_setup_email_tags() {

	// Setup default tags array.
	$email_tags = [

		[
			'tag'         => 'name',
			'label'       => esc_html__( 'First Name', 'intercessor' ),
			'description' => esc_html__( "The requester's first name", 'intercessor' ),
			'function'    => 'intercessor_email_tag_first_name',
		],
		[
			'tag'         => 'fullname',
			'label'       => esc_html__( 'Full Name', 'intercessor' ),
			'description' => esc_html__( "The requester's full name, first and last", 'intercessor' ),
			'function'    => 'intercessor_email_tag_fullname',
		],
		[
			'tag'         => 'username',
			'label'       => esc_html__( 'Username', 'intercessor' ),
			'description' => esc_html__( "The requester's user name on the site, if they registered an account", 'intercessor' ),
			'function'    => 'intercessor_email_tag_username',
		],
		[
			'tag'         => 'user_email',
			'label'       => esc_html__( 'User Email', 'intercessor' ),
			'description' => esc_html__( "The requester's email address", 'intercessor' ),
			'function'    => 'intercessor_email_tag_user_email',
		],
		[
			'tag'         => 'date',
			'label'       => esc_html__( 'Date', 'intercessor' ),
			'description' => esc_html__( 'The date the prayer request was activated', 'intercessor' ),
			'function'    => 'intercessor_email_tag_date',
		],
		[
			'tag'         => 'prayer_id',
			'label'       => esc_html__( 'Prayer ID', 'intercessor' ),
			'description' => esc_html__( 'The unique ID number for this prayer', 'intercessor' ),
			'function'    => 'intercessor_email_tag_prayer_id',
		],
		[
			'tag'         => 'notification_id',
			'label'       => esc_html__( 'Notification ID', 'intercessor' ),
			'description' => esc_html__( 'The unique ID number for this prayer notification', 'intercessor' ),
			'function'    => 'intercessor_email_tag_notification_id',
		],
		[
			'tag'         => 'sitename',
			'label'       => esc_html__( 'Site Name', 'intercessor' ),
			'description' => esc_html__( 'Your site name', 'intercessor' ),
			'function'    => 'intercessor_email_tag_sitename',
		],
		[
			'tag'         => 'notification_link',
			'label'       => esc_html__( 'Notification Link', 'intercessor' ),
			'description' => esc_html__( 'Adds a link so users can view their notification directly on your website.', 'intercessor' ),
			'function'    => 'intercessor_email_tag_notification_link',
		],
		[
			'tag'         => 'prayer_title',
			'label'       => esc_html__( 'Prayer Title', 'intercessor' ),
			'description' => esc_html__( 'The title of the submitted prayer request', 'intercessor' ),
			'function'    => 'intercessor_email_tag_prayer_title',
		],
		[
			'tag'         => 'prayer_message',
			'label'       => esc_html__( 'Prayer Message', 'intercessor' ),
			'description' => esc_html__( 'The submitted prayer request message', 'intercessor' ),
			'function'    => 'intercessor_email_tag_prayer_message',
		],
		[
			'tag'         => 'prayer_link',
			'label'       => esc_html__( 'Prayer Link', 'intercessor' ),
			'description' => esc_html__( 'The link to the submitted prayer request', 'intercessor' ),
			'function'    => 'intercessor_email_tag_prayer_link',
		],
		[
				'tag'         => 'prayed_counts',
				'label'       => esc_html__( 'Prayed Counts', 'intercessor' ),
				'description' => esc_html__( 'The prayed counts for specified period in settings', 'intercessor' ),
				'function'    => 'intercessor_email_tag_prayed_counts',
		],
			[
					'tag'         => 'total_counts',
					'label'       => esc_html__( 'Total Prayed Counts', 'intercessor' ),
					'description' => esc_html__( 'The total lifetime prayed counts for a prayer request', 'intercessor' ),
					'function'    => 'intercessor_email_tag_total_counts',
			],
	];

	// Apply intercessor_email_tags filter.
	$email_tags = apply_filters( 'intercessor_email_tags', $email_tags );

	// Add email tags.
	foreach ( $email_tags as $email_tag ) {
		$label = $email_tag['label'] ?? '';
		intercessor_add_email_tag( $email_tag['tag'], $email_tag['description'], $email_tag['function'], $label );
	}
}

/**
 * Email template tag: name
 *
 * The requester first name.
 *
 * @param int $prayer_id Prayer ID, default is null.
 *
 * @return string name
 */
function intercessor_email_tag_first_name( int $prayer_id ) : string {
	$prayer    = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
	$requester = new Requester( $prayer->email );
	return $requester->get_first_name();
}

/**
 * Email template tag: fullname
 * The requester full name, first and last.
 *
 * @param int $prayer_id Prayer ID.
 *
 * @return string fullname
 */
function intercessor_email_tag_fullname( int $prayer_id ) : string {
	$prayer    = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
	$requester = new Requester( $prayer->email );

	// Return full name composed of first and last name.
	return $requester->get_first_name() . ' ' . $requester->get_last_name();
}

/**
 * Email template tag: username
 * The requester's username on the site, if they registered an account.
 *
 * @param int $prayer_id Prayer ID.
 *
 * @return string username
 */
function intercessor_email_tag_username( int $prayer_id ) : string {
	$prayer    = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
	$requester = new Requester( $prayer->email );
	$user_id   = esc_attr( $requester->user_id );

	if ( $user_id > 0 ) {
		$user_data             = get_userdata( $user_id );
		$user_info['username'] = $user_data->user_login;
	} else {
		$user_info['username'] = $requester->get_first_name();
	}

	return $user_info['username'];
}

/**
 * Email template tag: user_email
 * The requester's email address
 *
 * @param int $prayer_id Prayer ID.
 *
 * @return string user_email
 */
function intercessor_email_tag_user_email( int $prayer_id ) : string {
	$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );

	return $prayer->email;
}

/**
 * Email template tag: date
 * Date of prayer
 *
 * @param int $prayer_id Prayer ID.
 *
 * @return string date
 */
function intercessor_email_tag_date( int $prayer_id ) : string {
	$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
	return date_i18n( get_option( 'date_format' ), strtotime( $prayer->date_created ) );
}

/**
 * Email template tag: prayer_id
 * The unique ID number for this prayer
 *
 * @param int $prayer_id Prayer ID.
 *
 * @return int prayer_id
 */
function intercessor_email_tag_prayer_id( int $prayer_id ) : int {
	$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
	return (int) esc_attr( $prayer->id );
}

/**
 * Email template tag: notification_id
 * The unique ID number for this prayer notification.
 *
 * @param int $prayer_id Prayer ID.
 *
 * @return string notification_id
 */
function intercessor_email_tag_notification_id( int $prayer_id ) : string {
	$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
	return $prayer->prayer_key;
}

/**
 * Email template tag: sitename
 * Your site name.
 *
 * @return string sitename
 */
function intercessor_email_tag_sitename() : string {
	return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
}

/**
 * Email template tag: notification_link
 * Adds a link so users can view their notification directly on your website if they are unable to view it in the browser correctly
 *
 * @param int $prayer_id Prayer ID.
 *
 * @return string notification_link
 */
function intercessor_email_tag_notification_link( int $prayer_id ): string {
	$notification_url = esc_url(
		add_query_arg(
			[
				'prayer_key'         => intercessor_get_prayer_key( $prayer_id ),
				'intercessor_action' => 'view_notification',
			],
			home_url()
		)
	);
	$formatted        = sprintf(
		// Translators: View it in your browser.
		__( '%1$sView it in your browser %2$s', 'intercessor' ),
		'<a href="' . $notification_url . '">',
		'&raquo;</a>'
	);

	if ( 'none' !== intercessor_get_option( 'email_template' ) ) {
		return $formatted;
	} else {
		return $notification_url;
	}
}

/**
 * Email template tag: Title
 *
 * @param int $prayer_id Prayer ID.
 *
 * @since  0.9.5
 * @return string $title The title of the prayer request
 */
function intercessor_email_tag_prayer_title( int $prayer_id ) : string {
	$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
	return stripslashes( $prayer->title );
}

/**
 * Email template tag: message
 *
 * @since  0.9.5
 * @param int $prayer_id Prayer ID.
 * @return string $message The message content of the prayer request.
 */
function intercessor_email_tag_prayer_message( int $prayer_id ) : string {
	$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
	return stripslashes( $prayer->message );
}

/**
 * Email template tag: prayer link
 *
 * @since  0.9.5
 * @return string $page_url The link to the prayer request.
 */
function intercessor_email_tag_prayer_link() : string {
	$page_id  = intercessor_get_option( 'history_page' );
	$page_url = esc_url( get_permalink( $page_id ) );

	return $page_url;
}

if ( ! function_exists( 'intercessor_email_tag_prayed_counts' ) ) {

	/**
	 * Email template tag: prayed_counts
	 *
	 * @since  1.1.0
	 * @param int $prayer_id The Prayer ID.
	 *
	 * @return int The prayed counts for a specied period
	 */
	function intercessor_email_tag_prayed_counts( int $prayer_id ) : int {
		return intercessor_get_prayed_for_counts_range( $prayer_id );
	}
}


if ( ! function_exists( 'intercessor_email_tag_total_counts' ) ) {

	/**
	 * Email template tag: total_counts
	 *
	 * @since  1.1.0
	 * @param int $prayer_id The Prayer ID.
	 *
	 * @return int The lifetime prayed counts for a prayer request.
	 */
	function intercessor_email_tag_total_counts( int $prayer_id ) : int {
		return intercessor_get_prayed_for_counts( $prayer_id );
	}
}

if ( ! function_exists( 'intercessor_email_tag_requester_prayer_reports' ) ) {
	/**
	 * @param array $prayer_ids Array of prayer IDs.
	 *
	 * @return false|string
	 */
	function intercessor_email_tag_requester_prayer_reports( array $prayer_ids ) {

		// Bail if no prayer ID supplied.
		if ( empty( $prayer_ids ) ) {
			return;
		}

		// Set up variables.
		$color  = 111111;
		$args   = [
			'id__in' => $prayer_ids,
		];
		$prayers = intercessor_get_items( 'prayer', $args );

		ob_start();
		echo '<ul>';
		foreach ( $prayers as $prayer ) :
			$prayer_id    = absint( $prayer->id );
			$title        = intercessor_get_prayer_title( $prayer_id );
			$date         = intercessor_get_prayer_attribute( $prayer_id, 'date' );
			$prayed_for   = esc_html__( 'Prayed for:', 'intercessor' );
			$times_lifted = esc_html__( 'times.', 'intercessor' );

			if ( intercessor_is_answered_prayer( $prayer_id ) ) {
				$answered = esc_html__( 'Answered?: yes.', 'intercessor' );
			} else {
				$answered = '';
			}

			// Output message.
			printf( '<li style="color: #%1$s; padding: 5px 0;"><span style="font-weight: bold;">%2$s</span> – %3$s (%4$s %5$s)</li>',
					$color,
					$title,
					$prayed_for . ' ' . intercessor_get_prayed_for_counts( $prayer_id ) . ' ' . $times_lifted,
					$answered,
					$date
			);

			// Checks that color is properly set.
			if ( $color < 999999 ) {
				$color += 111111;
			}
		endforeach;
		echo '</ul>';

		return ob_get_clean();

	}
}

/** PRAYER NOTIFICATIONS */

/**
 * Email the prayer confirmation to the requester in a customizable Prayer Notification
 *
 * @param int    $prayer_id    Prayer ID.
 * @param bool   $admin_notice Whether to send the admin email notification or not (default: true).
 * @param string $to_email     Email address to send to.
 *
 * @return void
 * @since 0.9.5
 *
 */
function intercessor_email_prayer_notification( int $prayer_id, bool $admin_notice = true, string $to_email = '' ) {
	// Bail if no prayer ID supplied.
	if ( empty( $prayer_id ) ) {
		return;
	}

	// Retrieve prayer request.
	$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );

	$requester_email = intercessor_get_prayer_email( $prayer_id );
	$requester       = new Intercessor\Requester( $requester_email );

	$prayer_data = [
		'title'   => stripslashes( $prayer->title ),
		'name'    => $requester->get_first_name() . ' ' . $requester->get_last_name(),
		'email'   => $requester_email,
		'message' => stripslashes( $prayer->message ),
	];

	$from_name  = intercessor_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name  = apply_filters( 'intercessor_prayer_from_name', $from_name, $prayer_id, $prayer_data );
	$from_email = intercessor_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email = apply_filters( 'intercessor_prayer_from_address', $from_email, $prayer_id, $prayer_data );

	if ( empty( $to_email ) ) {
		$to_email = $prayer->email;
	}

	$subject = intercessor_get_option( 'prayer_subject', __( 'Prayer Request', 'intercessor' ) );
	$subject = apply_filters( 'intercessor_prayer_subject', wp_strip_all_tags( $subject ), $prayer_id );
	$subject = wp_specialchars_decode( intercessor_do_email_tags( $subject, $prayer_id ) );
	$heading = intercessor_get_option( 'prayer_heading', __( 'Prayer Notification', 'intercessor' ) );
	$heading = apply_filters( 'intercessor_prayer_heading', $heading, $prayer_id, $prayer_data );
	$heading = intercessor_do_email_tags( $heading, $prayer_id );

	$attachments = apply_filters( 'intercessor_notification_attachments', [], $prayer_id, $prayer_data );
	$message     = intercessor_do_email_tags( intercessor_get_email_body_content( $prayer_id, $prayer_data ), $prayer_id );
	$emails      = new Emails();

	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading', $heading );

	$headers = apply_filters( 'intercessor_notification_headers', $emails->get_headers(), $prayer_id, $prayer_data );
	$emails->__set( 'headers', $headers );

	$emails->send( $to_email, $subject, $message, $attachments );

	if ( $admin_notice && ! intercessor_admin_notices_disabled( $prayer_id ) ) {
		do_action( 'intercessor_admin_prayer_notification', $prayer_id, $prayer_data );
	}
}

/**
 * Email the prayer confirmation to the admin account(s) for testing.
 *
 * @since 0.9.5
 * @return void
 */
function intercessor_email_test_prayer_notification() {

	$from_name  = intercessor_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name  = apply_filters( 'intercessor_prayer_from_name', $from_name, 0, [] );
	$from_email = intercessor_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email = apply_filters( 'intercessor_test_prayer_from_address', $from_email, 0, [] );
	$subject    = intercessor_get_option( 'prayer_subject', esc_html__( 'Prayer Request Notification', 'intercessor' ) );
	$subject    = apply_filters( 'intercessor_prayer_subject', wp_strip_all_tags( $subject ), 0 );
	$subject    = intercessor_do_email_tags( $subject, 0 );
	$heading    = intercessor_get_option( 'prayer_heading', esc_html__( 'Prayer Notification', 'intercessor' ) );
	$heading    = apply_filters( 'intercessor_prayer_heading', $heading, 0, [] );

	$attachments = apply_filters( 'intercessor_notification_attachments', [], 0, [] );
	$message     = intercessor_do_email_tags( intercessor_get_email_body_content( 0, [] ), 0 );

	//$emails = new Emails();
	$emails = intercessor()->emails;
	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading', $heading );

	$headers = apply_filters( 'intercessor_notification_headers', $emails->get_headers(), 0, [] );
	$emails->__set( 'headers', $headers );

	$emails->send( intercessor_get_admin_notice_emails(), $subject, $message, $attachments );

}

/**
 * Sends the Admin Prayer Notification Email
 *
 * @param int $prayer_id   Prayer ID (default: 0).
 * @param array $prayer_data Prayer Meta and Data.
 *
 * @return void
 * @since 0.9.5
 */
function intercessor_admin_email_notice( int $prayer_id = 0, array $prayer_data = [] ) {

	$prayer_id = absint( $prayer_id );

	// Bail if no prayer id.
	if ( empty( $prayer_id ) ) {
		return;
	}

	if ( ! intercessor_get_item_by( 'prayer', 'id', $prayer_id ) ) {
		return;
	}

	$from_name   = intercessor_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name   = apply_filters( 'intercessor_prayer_from_name', $from_name, $prayer_id, $prayer_data );
	$from_email  = intercessor_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email  = apply_filters( 'intercessor_admin_sale_from_address', $from_email, $prayer_id, $prayer_data );
	$subject     = intercessor_get_option( 'prayer_notification_subject', sprintf( __( 'New prayer request - Prayer #%1$s', 'intercessor' ), $prayer_id ) );
	$subject     = apply_filters( 'intercessor_admin_prayer_notification_subject', wp_strip_all_tags( $subject ), $prayer_id );
	$subject     = wp_specialchars_decode( intercessor_do_email_tags( $subject, $prayer_id ) );
	$heading     = intercessor_get_option( 'prayer_notification_heading', esc_html__( 'New Request submitted!', 'intercessor' ) );
	$heading     = apply_filters( 'intercessor_admin_prayer_notification_heading', $heading, $prayer_id, $prayer_data );
	$heading     = intercessor_do_email_tags( $heading, $prayer_id );
	$attachments = apply_filters( 'intercessor_admin_prayer_notification_attachments', [], $prayer_id, $prayer_data );
	$message     = intercessor_get_prayer_notification_body_content( $prayer_id, $prayer_data );
	$emails      = new Emails();

	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading', $heading );

	$headers = apply_filters( 'intercessor_admin_prayer_notification_headers', $emails->get_headers(), $prayer_id, $prayer_data );
	$emails->__set( 'headers', $headers );

	$emails->send( intercessor_get_admin_notice_emails(), $subject, $message, $attachments );

}

if ( ! function_exists( 'intercessor_email_prayed_notification' ) ) {
	/**
	 * Email the requester when prayed for.
	 *
	 * Prayed notifications are sent out only once per day.
	 *
	 * @param int    $prayer_id  Prayer ID.
	 * @param int    $prayed_num Number of times request was prayed for.
	 * @param string $to_email   Email to send prayed counts to.
	 *
	 * @return void
	 * @since 0.9.5
	 */
	function intercessor_email_prayed_notification( int $prayer_id, int $prayed_num, string $to_email = '' ) {
		// Setup requester data.
	    $requester_email = intercessor_get_prayer_email( $prayer_id );
		$requester       = new Requester( $requester_email );

		// Setup prayed for data.
		$prayed_data = [
			'title'  => intercessor_get_prayer_title( $prayer_id ),
			'name'   => $requester->get_first_name() . ' ' . $requester->get_last_name(),
			'email'  => $requester_email,
			'number' => $prayed_num,
		];

		// Setup email data.
		$from_name  = intercessor_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
		$from_name  = apply_filters( 'intercessor_prayer_from_name', $from_name, $prayer_id, $prayed_data );
		$from_email = intercessor_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
		$from_email = apply_filters( 'intercessor_prayer_from_address', $from_email, $prayer_id, $prayed_data );

		if ( empty( $to_email ) ) {
			$to_email = $prayed_data['email'];
		}

		$subject = intercessor_get_option( 'prayed_notice_subject', __( 'Your Prayer Request', 'intercessor' ) );
		$subject = apply_filters( 'intercessor_prayer_subject', wp_strip_all_tags( $subject ), $prayer_id );
		$subject = wp_specialchars_decode( intercessor_do_email_tags( $subject, $prayer_id ) );

		$heading = intercessor_get_option( 'prayed_heading', __( 'You Have Been Prayed For!', 'intercessor' ) );
		$heading = apply_filters( 'intercessor_prayer_heading', $heading, $prayer_id, $prayed_data );
		$heading = intercessor_do_email_tags( $heading, $prayer_id );

		$message = intercessor_do_email_tags( intercessor_get_prayed_email_body_content( $prayer_id, $prayed_data ), $prayer_id );
		$emails  = intercessor()->emails;

		$emails->__set( 'from_name', $from_name );
		$emails->__set( 'from_email', $from_email );
		$emails->__set( 'heading', $heading );

		$headers = apply_filters( 'intercessor_prayed_notification_headers', $emails->get_headers(), $prayer_id, $prayed_data );
		$emails->__set( 'headers', $headers );

		$emails->send( $to_email, $subject, $message );
	}
}

if ( ! function_exists( 'intercessor_get_prayed_email_body_content' ) ) {
	/**
	 * Prayed Notification Template Body
	 *
	 * @param  int   $prayer_id Prayer ID.
	 * @param array $prayed_data Prayer Data.
	 *
	 * @return string $email_body  Body of the email
	 *@since  0.9.5
	 */
	function intercessor_get_prayed_email_body_content( int $prayer_id = 0, array $prayed_data = [] ) : string {
		$date = intercessor_get_option( 'notify_period', 'weekly' );
	    if ( 'monthly' === $date ) {
	        $date_label = esc_html__( 'this month', 'intercessor' );
        } elseif ( 'daily' === $date ) {
		    $date_label = esc_html__( 'today', 'intercessor' );
	    } else {
			$date_label = esc_html__( 'this week', 'intercessor' );
		}

		$default_email_body  = esc_html__( 'Dearly beloved', 'intercessor' ) . " {name},\n\n";
		$default_email_body .= sprintf(
			// Translators: Your prayer requester titled: $prayer_title has been prayed for x times today.
			__( 'Your prayer request titled: %1$s has been prayed for %2$1s times ', 'intercessor' ) . $date_label,
			'<strong>' . $prayed_data['title'] . '</strong>',
			$prayed_data['number']
		) . "\n\n";
		$default_email_body .= esc_html__( 'We will continue praying for you concerning this request.', 'intercessor' ) . "\n\n";
		$default_email_body .= esc_html__( 'Remain blessed in Jesus Christ name.', 'intercessor' );

		$email = intercessor_get_option( 'prayed_notice_text', false );
		$email = $email ? stripslashes( $email ) : $default_email_body;

		$email_body = apply_filters( 'intercessor_email_template_wpautop', true ) ? wpautop( $email ) : $email;
		$email_body = apply_filters( 'intercessor_prayed_notification_' . intercessor()->emails->get_template(), $email_body, $prayer_id, $prayed_data );

		return apply_filters( 'intercessor_prayed_for_notification', $email_body, $prayer_id, $prayed_data );
	}
}

/**
 * Retrieves the emails for which admin notifications are sent to
 *
 * @since 0.9.5
 * @return mixed
 */
function intercessor_get_admin_notice_emails() {
	$emails = intercessor_get_option( 'admin_notice_emails', false );
	$emails = strlen( trim( $emails ) ) > 0 ? $emails : get_bloginfo( 'admin_email' );
	$emails = array_map( 'trim', explode( "\n", $emails ) );

	return apply_filters( 'intercessor_admin_notice_emails', $emails );
}

/**
 * Checks whether admin notices are disabled
 *
 * @param int $prayer_id Prayer ID.
 *
 * @return bool
 * @since 0.9.5
 *
 */
function intercessor_admin_notices_disabled( int $prayer_id = 0 ) : bool {
	$ret = intercessor_get_option( 'disable_admin_notices', false );
	return (bool) apply_filters( 'intercessor_admin_notices_disabled', $ret, $prayer_id );
}

/**
 * Get prayer notification email text
 *
 * Returns the stored email text if available, the standard email text if not
 *
 * @since 0.9.5
 * @return string $message
 */
function intercessor_get_default_prayer_notification_email() : string {
	$default_email_body  = esc_html__( 'Praise the LORD!', 'intercessor' ) . "\n\n" . sprintf( __( 'A %s has been submitted', 'intercessor' ), 'Prayer Requests' ) . ".\n\n";
	$default_email_body .= esc_html__( 'Titled: ', 'intercessor' ) . "{prayer_title}\n\n";
	$default_email_body .= esc_html__( 'Prayer Message:', 'intercessor' ) . "\n\n";
	$default_email_body .= '{prayer_message}' . "\n\n";
	$default_email_body .= esc_html__( 'Submitted by: ', 'intercessor' ) . ' {fullname}' . "\n";
	$default_email_body .= esc_html__( 'Requester Email: ', 'intercessor' ) . ' {user_email}' . "\n";
	$default_email_body .= esc_html__( 'Date: ', 'intercessor' ) . "{date}\n\n";
	$default_email_body .= esc_html__( 'Remain blessed in Jesus name.', 'intercessor' );

	$message = intercessor_get_option( 'prayer_notification', false );
	$message = ! empty( $message ) ? $message : $default_email_body;

	return $message;
}

if ( ! function_exists( 'intercessor_get_default_prayed_notice_email' ) ) {
	/**
	 * Get prayed notification email text
	 *
	 * Returns the stored email text if available, the standard email text if not
	 *
	 * @since 0.9.5
	 * @return string $message
	 */
	function intercessor_get_default_prayed_notice_email() : string {
		$default_email_body  = esc_html__( 'Praise the LORD!', 'intercessor' ) . "\n\n" . sprintf( __( 'Your %s has been prayed for. today', 'intercessor' ), 'Prayer Requests' ) . ".\n\n";
		$default_email_body .= '{prayer_title}' . "\n\n";
		$default_email_body .= esc_html__( 'Submitted on: ', 'intercessor' ) . ' {date}' . "\n";
		$default_email_body .= esc_html__( 'We will continue praying for you ', 'intercessor' ) . "\n\n";
		$default_email_body .= esc_html__( 'Remain blessed in Jesus name,', 'intercessor' );
		$default_email_body .= esc_html__( 'The Prayer Team.', 'intercessor' ) . "\n\n";

		$message = intercessor_get_option( 'prayer_notification', false );
		$message = ! empty( $message ) ? $message : $default_email_body;

		return $message;
	}
}

/**
 * Gets all the email templates that have been registered. One can add more
 * templates to extend the list.
 *
 * @since 0.9.5
 * @return array $templates All the registered email templates
 */
function intercessor_get_email_templates() : array {
	return intercessor()->emails->get_templates();
}

/**
 * Email Preview Template Tags
 *
 * @since 0.9.5
 * @param string $message Email message with template tags.
 * @return string $message Fully formatted message
 */
function intercessor_email_preview_template_tags( string $message ) : string {
	$prayer_list = '<ul>';
	$prayer_list .= '<li>' . esc_html__( 'Intercessor', 'intercessor' ) . '<br />';
	$prayer_list .= '<div>';
	$prayer_list .= '<a href="#">' . esc_html__( 'Sample Prayer Title', 'intercessor' ) . '</a> - <small>' . esc_html__( 'Include the title of the prayer request', 'intercessor' ) . '</small>';
	$prayer_list .= '</div>';
	$prayer_list .= '</li>';
	$prayer_list .= '</ul>';

	$notes           = esc_html__( 'These are some sample notes added to a prayer request.', 'intercessor' );
	$notification_id = strtolower( md5( uniqid() ) );
	$prayer_id       = wp_rand( 1, 100 );
	$prayer_message  = esc_html__( 'Please, pray for me to be successful in Jesus name.', 'intercessor' );
	$prayer_title    = '<strong>' . esc_html__( 'Prayer for success and promotion', 'intercessor' ) . '</strong>';
	$user            = wp_get_current_user();

	$message = str_replace( '{prayer_list}', $prayer_list, $message );
	$message = str_replace( '{name}', $user->display_name, $message );
	$message = str_replace( '{fullname}', $user->display_name, $message );
	$message = str_replace( '{username}', $user->user_login, $message );
	$message = str_replace( '{date}', gmdate( get_option( 'date_format' ), current_time( 'timestamp' ) ), $message );
	$message = str_replace( '{prayer_title}', $prayer_title, $message );
	$message = str_replace( '{prayer_message}', $prayer_message, $message );
	$message = str_replace( '{notification_id}', $notification_id, $message );
	$message = str_replace( '{sitename}', get_bloginfo( 'name' ), $message );
	$message = str_replace( '{prayer_notes}', $notes, $message );
	$message = str_replace( '{prayer_id}', $prayer_id, $message );
	$message = str_replace( '{notification_link}', intercessor_email_tag_notification_link( $prayer_id ), $message );

	$message = apply_filters( 'intercessor_email_preview_template_tags', $message );

	return apply_filters( 'intercessor_email_template_wpautop', true ) ? wpautop( $message ) : $message;
}

/**
 * Email Template Preview
 *
 * @access private
 * @since 0.9.5
 */
function intercessor_email_template_preview() {
	// Bail if user is unauthorized.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Start the buffer.
	ob_start();
	?>
	<a href="<?php
	echo esc_url(
		add_query_arg(
			[
				'intercessor_action' => 'preview_email',
			],
			home_url()
		)
	);
	?>" class="button-secondary" target="_blank"><?php esc_html_e( 'Preview Prayer Notification', 'intercessor' ); ?></a>
	<a href="<?php
	echo esc_url(
		wp_nonce_url(
			add_query_arg(
				[
					'intercessor_action' => 'send_test_email',
				]
			),
			'intercessor-test-email'
		)
	); ?>" class="button-secondary"><?php esc_html_e( 'Send Test Email', 'intercessor' ); ?>
	</a>
	<?php
	echo ob_get_clean();
}

/**
 * Displays the email preview
 *
 * @since 0.9.5
 * @return void
 */
function intercessor_display_email_template_preview() {

	// Bail if no action specified.
	if ( empty( $_GET['intercessor_action'] ) ) {
		return;
	}

	// Bail if preview email action is not set.
	if ( 'preview_email' !== $_GET['intercessor_action'] ) {
		return;
	}

	// Bail if user is unauthorized.
	if ( ! current_user_can( 'manage_prayer_settings' ) ) {
		return;
	}

	intercessor()->emails->heading = intercessor_email_preview_template_tags( intercessor_get_option( 'prayer_heading', __( 'Prayer Notification', 'intercessor' ) ) );

	echo intercessor()->emails->setup_email( intercessor_email_preview_template_tags( intercessor_get_email_body_content( 0, [] ) ) );

	exit;
}

/**
 * Email Template Body
 *
 * @param int   $prayer_id   Prayer ID.
 * @param array $prayer_data Prayer Data.
 *
 * @return string $email_body Body of the email
 *@since 0.9.5
 */
function intercessor_get_email_body_content( int $prayer_id = 0, array $prayer_data = [] ): string {
	$default_email_body  = esc_html__( 'Dearly beloved', 'intercessor' ) . " {name},\n\n";
	$default_email_body .= esc_html__( "Thank you for your submitting your prayer request titled: ", "intercessor" ) . "{prayer_title}\n\n";
	$default_email_body .= esc_html__( "Please click on the link below to view or edit your prayer request.", "intercessor" );
	$default_email_body .= "{prayer_link}\n\n";
	$default_email_body .=  esc_html__( "We are praying for you and believe that God Almighty will meet you at the point of your need, in Jesus name.", "intercessor" ). "\n\n";
	$default_email_body .= esc_html__( "Remain blessed,", "intercessor" ). "\n\n";
	$default_email_body .= "{sitename}";

	$email  = intercessor_get_option( 'prayer_notification', false );
	$email  = $email ? stripslashes( $email ) : $default_email_body;

	$email_body = apply_filters( 'intercessor_email_template_wpautop', true ) ? wpautop( $email ) : $email;
	$email_body = apply_filters( 'intercessor_prayer_notification_' . intercessor()->emails->get_template(), $email_body, $prayer_id, $prayer_data );

	return apply_filters( 'intercessor_prayer_notification', $email_body, $prayer_id, $prayer_data );
}

/**
 * Prayer Notification Template Body
 *
 * @param int   $prayer_id   Prayer ID.
 * @param array $prayer_data Prayer Data.
 *
 * @since  0.9.5
 * @return string $email_body  Body of the email
 */
function intercessor_get_prayer_notification_body_content( int $prayer_id = 0, array $prayer_data = [] ) : string {
	$prayer     = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
	$email      = intercessor_get_prayer_email( $prayer_id );
	$requester  = new Requester( $email );
	$first_name = $requester->get_first_name();
	$last_name  = $requester->get_last_name();
	$title      = stripslashes( $prayer->title );
	$message    = stripslashes( $prayer->message );

	if ( $prayer->user_id > 0 ) {
		$user_data = get_userdata( $prayer->user_id );
		$name      = $user_data->display_name;
	} elseif ( ! empty( $first_name ) && ! empty( $last_name ) ) {
		$name = $first_name . ' ' . $last_name;
	} else {
		$name = $email;
	}

	$prayer_list = '';

	$default_email_body = __( 'Hello', 'intercessor' ) . "\n\n" . sprintf( __( 'A %s has been submitted', 'intercessor' ), 'Prayer Requests' ) . ".\n\n";
	$default_email_body .= sprintf( __( '%s submitted:', 'intercessor' ), 'Prayer Requests' ) . "\n\n";
	$default_email_body .= $prayer_list . "\n\n";
	$default_email_body .= __( 'Submitted by: ', 'intercessor' ) . " " . html_entity_decode( $name, ENT_COMPAT, 'UTF-8' ) . "\n";
	$default_email_body .= __( 'Title: ', 'intercessor' ) . " " . html_entity_decode( $title, ENT_COMPAT, 'UTF-8' ) . "\n";
	$default_email_body .= __( 'Prayer Message: ', 'intercessor' ) . " " . html_entity_decode( $message, ENT_COMPAT, 'UTF-8' ) . "\n";
	$default_email_body .= __( 'Stay blessed', 'intercessor' );

	$message    = intercessor_get_option( 'prayer_notification', false );
	$message    = $message ? stripslashes( $message ) : $default_email_body;
	$email_body = intercessor_do_email_tags( $message, $prayer_id );
	$email_body = apply_filters( 'intercessor_email_templates_wpautop', true ) ? wpautop( $email_body ) : $email_body;

	return apply_filters( 'intercessor_prayer_notification', $email_body, $prayer_id, $prayer_data );
}

/**
 * Render Notification in the Browser
 *
 * A link is added to the Prayer Notification to view the email in the browser and
 * this function renders the Prayer Notification in the browser. It overrides the
 * Prayer Notification template and provides its only styling.
 *
 * @since 0.9.5
 */
function intercessor_render_notification_in_browser() {
	if ( ! isset( $_GET['prayer_key'] ) ) {
		wp_die(
			esc_html__( 'Missing prayer key.', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' )
		);
	}

	$key = urlencode( $_GET['prayer_key'] );

	ob_start();
	// Disallows caching of the page.
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache');
	header('Expires: Sat, 23 Oct 1977 05:00:00 PST');
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php esc_html_e( 'Prayer Notification', 'intercessor' ); ?></title>
		<meta charset="utf-8" />
		<meta name="robots" content="noindex, nofollow" />
		<?php wp_head(); ?>
	</head>
	<body class="<?php echo apply_filters( 'intercessor_notification_page_body_class', 'intercessor_notification_page' ); ?>">
		<div id="intercessor_notification_wrapper">
			<?php do_action( 'intercessor_render_notification_in_browser_before' ); ?>
			<?php echo do_shortcode( '[intercessor_notification prayer_key='. $key .']' ); ?>
			<?php do_action( 'intercessor_render_notification_in_browser_after' ); ?>
		</div>
	<?php wp_footer(); ?>
	</body>
</html>
<?php
	echo ob_get_clean();
	die();
}

/**
 * Triggers Prayer Notification to be sent after the prayer status is updated
 *
 * @param string $to_email  Email to send notification to.
 * @param int    $prayer_id Prayer ID.
 *
 * @return void
 * @since 0.9.5
 */
function intercessor_trigger_prayer_notification(  string $to_email, int $prayer_id = 0 ) {
	// Make sure we don't send a prayer notification while editing a prayer request.
	if ( isset( $_POST['intercessor-action'] ) && 'edit_prayer' === $_POST['intercessor-action'] ) {
		return;
	}

	// Send email with secure prayer link.
	intercessor_email_prayer_notification( $prayer_id, true, $to_email );
}

/**
 * Resend the Email Prayer Notification. (This can be done from the Prayer History page)
 *
 * @param array $data Prayer Data.
 *
 * @since 0.9.5
 * @return void
 */
function intercessor_resend_prayer_notification( array $data = [] ) {

	$prayer_id = absint( $data['prayer_id'] );
	$prayer    = intercessor_process_item( 'prayer', 'get', $prayer_id, false );

	// Bailout if no prayer ID supplied.
	if ( empty( $prayer_id ) ) {
		return;
	}

	// Bail out if user has no privilege to resend notification.
	if ( ! current_user_can( 'edit_prayers' ) ) {
		wp_die(
			esc_html__( 'You do not have permission to resend the prayer notification.', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			array( 'response' => 403 )
		);
	}

	$email = ! empty( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';

	if ( empty( $email ) ) {
		$requester = new Requester( intercessor_get_prayer_requester_id( $prayer_id ) );
		$email     = $requester->email;
	}

	// Send the prayer notification.
	intercessor_email_prayer_notification( $prayer_id, false, $email );

	wp_safe_redirect(
		add_query_arg(
			array(
				'intercessor-message' => 'email_sent',
				'intercessor-action'  => false,
				'prayer_id'           => false,
			)
		)
	);

	exit;
}

if ( ! function_exists( 'intercessor_send_prayed_email' ) ) {
	/**
	 * Get the number of requests prayed for.
	 *
	 * @return false|void
	 * @since 1.0.0
	 */
    function intercessor_send_prayed_email() {
	    // Get notification date value.
		$date_value = intercessor_get_notify_period();

	    // Setup array of prayed for arguments.
	    $args = [
		    'date_created_query' => [
			    'after'  => $date_value,
		    ],
	    ];

	    // Retrieve prayer requests already prayed for.
	    $prayed_for = intercessor_get_items( 'prayed', $args );

	    // Send email to requester if prayed for.
	    if ( $prayed_for ) {
			// Set up default variable values.
			$ids_args = [
				'id__in' => $prayed_for->prayer_id,
			];

			// Get array of prayers lifted within specified period.
			$prayers  = intercessor_get_items( 'prayer', $ids_args );

			// Get each prayer.
			foreach ( $prayers as $prayer ) {
				$prayer_id = $prayer->id;
				$email     = intercessor_get_prayer_email( $prayer_id );
				$notify    = intercessor_get_prayer_notify( $prayer_id );
				$answered  = intercessor_is_answered_prayer( $prayer_id );
				$counts    = intercessor_get_prayed_for_counts_range( $prayer_id );

				// Send email to requesters who wish to be notified.
				if ( $notify && $counts && ! $answered ) {
					intercessor_email_prayed_notification( $prayer_id, $counts, $email );
				}
			}
	    } else {
			esc_html_e( 'Error processing prayed for email', 'intercessor' );
            return false;
        }
    }
}

/**
 * Sends the new user notification email when a user registers during prayer submission
 *
 * @access public
 * @since  0.9.5
 *
 * @param int   $user_id   User ID.
 * @param array $user_data User data.
 */
function intercessor_new_user_notification( int $user_id = 0, array $user_data = [] ) {
    // Bail if user ID or data is not specified.
	if ( empty( $user_id ) || empty( $user_data ) ) {
		return;
	}

	$emails     = new Emails();
	$from_name  = intercessor_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_email = intercessor_get_option( 'from_email', get_bloginfo( 'admin_email' ) );

	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );

	$admin_subject  = sprintf( __( '[%s] New User Registration', 'intercessor' ), $from_name );
	$admin_heading  = esc_html__( 'New user registration', 'intercessor' );
	$admin_message  = sprintf( __( 'Username: %s', 'intercessor' ), $user_data['user_login'] ) . "\r\n\r\n";
	$admin_message .= sprintf( __( 'E-mail: %s', 'intercessor' ), $user_data['user_email'] ) . "\r\n";

	$emails->__set( 'heading', $admin_heading );

	$emails->send( get_option( 'admin_email' ), $admin_subject, $admin_message );

	$user_subject  = sprintf( __( '[%s] Your username and password', 'intercessor' ), $from_name );
	$user_heading  = __( 'Your account info', 'intercessor' );
	$user_message  = sprintf( __( 'Username: %s', 'intercessor' ), $user_data['user_login'] ) . "\r\n";

	if ( did_action( 'intercessor_pre_process_prayer' ) ) {
		$password_message = __( 'Password entered during prayer submission', 'intercessor' );
	} else {
		$password_message = __( 'Password entered at registration', 'intercessor' );
	}

	$user_message .= sprintf( __( 'Password: %s', 'intercessor' ), '[' . $password_message . ']' ) . "\r\n";

	if ( $emails->html ) {

		$user_message .= '<a href="' . wp_login_url() . '"> ' . esc_attr__( 'Click here to log in', 'intercessor' ) . ' &raquo;</a>' . "\r\n";

	} else {

		$user_message .= sprintf( __( 'To log in, visit: %s', 'intercessor' ), wp_login_url() ) . "\r\n";

	}

	$emails->__set( 'heading', $user_heading );

	$emails->send( $user_data['user_email'], $user_subject, $user_message );
}

/**
 * Sends the new user notification email when a user registers during prayer submission
 *
 * @access public
 * @since  0.9.5
 *
 * @param int   $new_user_id        ID of newly created user.
 * @param array $new_requester_data Array of data used in creating user.
 * @param bool  $password_generated True if password was generated, otherwise false.
 *
 * @return void
 */
function intercessor_new_created_user_notification( int $new_user_id, array $new_requester_data = [], bool $password_generated = false ) {
	// Bail if user ID or data is not specified.
	if ( empty( $new_requester_data ) || empty( $new_user_id ) ) {
		return;
	}

	// Admin email notification.
	$emails     = new Emails();
	$from_name  = intercessor_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_email = intercessor_get_option( 'from_email', get_bloginfo( 'admin_email' ) );

	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );

	$admin_subject  = sprintf( __( '[%s] New User Registration', 'intercessor' ), $from_name );
	$admin_heading  = __( 'New user registration', 'intercessor' );
	$admin_message  = sprintf( __( 'Username: %s', 'intercessor' ), $new_requester_data['user_login'] ) . "\r\n\r\n";
	$admin_message .= sprintf( __( 'E-mail: %s', 'intercessor' ), $new_requester_data['user_email'] ) . "\r\n";

	$emails->__set( 'heading', $admin_heading );

	$emails->send( get_option( 'admin_email' ), $admin_subject, $admin_message );

	// New user email notification.
	$blogname      = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	$user_subject  = sprintf( __( '[%s] Your username and password', 'intercessor' ), $from_name );
	$user_heading  = __( 'Your account info', 'intercessor' );
	$user_message  = sprintf( __( 'Thanks for creating an account on %s', 'intercessor' ), $blogname ) . "\r\n";
	$user_message .= sprintf( __( 'Username: %s', 'intercessor' ), $new_requester_data['user_login'] ) . "\r\n";

	if ( intercessor_generate_password() && $password_generated ) {
		$password_message = sprintf( __( 'Your password has been automatically generated: %s', 'intercessor' ), '<strong>' . esc_html( $new_requester_data['user_pass'] ) . '</strong>' );
	} else {
		$password_message = __( 'Password entered during prayer submission', 'intercessor' );
	}

	$user_message .= sprintf( __( 'Password: %s', 'intercessor' ), '[' . $password_message . ']' ) . "\r\n";

	if ( $emails->html ) {

		$user_message .= '<a href="' . wp_login_url() . '"> ' . esc_attr__( 'Click here to log in', 'intercessor' ) . ' &raquo;</a>' . "\r\n";

	} else {

		$user_message .= sprintf( __( 'To log in, visit: %s', 'intercessor' ), wp_login_url() ) . "\r\n";

	}

	$emails->__set( 'heading', $user_heading );

	$emails->send( $new_requester_data['user_email'], $user_subject, $user_message );
}

if ( ! function_exists( 'intercessor_send_requester_reports' ) ) {
	/**
	 * send prayer records to requester
	 *
	 * @return void
	 * @since 1.1.1
	 */
    function intercessor_send_requester_reports() {
		// Bail if this action is not from the admin end.
	/*	if ( ! intercessor_did_v111_upgrade() ) {
			return;
		}
		*/
		// Get email option from database.
		$notified = get_option( 'requesters_reports' );

		// Bail if already notified requesters.
		if ( $notified ) {
			return;
		}

		// Set up variables.
		$args    = [
		    'number' => 100000000,
	    ];

		$prayers = intercessor_get_prayers( $args );

		// Process prayers and requesters, if available.
		if ( $prayers ) {
			$requesters = intercessor_get_items( 'requester', $args );

			if ( $requesters) {
				foreach ( $requesters as $requester ) {
					// Set up variables.
					$requester_id = absint( $requester->id );

					// Send prayer reports email.
					intercessor_email_reports_notification( $requester_id );
				}
			}
		}

		return add_option( 'requesters_reports', 1 );
    }
}

if ( ! function_exists( 'intercessor_email_reports_notification' ) ) {
	/**
	 * Email prayer reports to requester.
	 *
	 * @param int   $requester_id Requester ID.
	 *
	 * @return void
	 */
	function intercessor_email_reports_notification( int $requester_id ) {
		// Bail, if no requester ID supplied.
		if ( 0 === $requester_id ) {
			return;
		}

		// Set up variables.
		$requester      = new Requester( $requester_id, false );
		$requester_data = [
			'first_name' => $requester->get_first_name(),
			'email'      => $requester->email,
			'prayer_ids' => $requester->get_prayer_ids(),
		];
		$prayer_reports = intercessor_email_tag_requester_prayer_reports( $requester_data['prayer_ids'] );

		$prayers_link   = intercessor_get_history_page_uri();
		$dashboard      = '<a href="' . $prayers_link . '"> ' . esc_attr__( 'Prayers History Page', 'intercessor' ) . ' &raquo;</a>' . "\r\n";

		// Email address values.
		$from_name  = intercessor_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
		$from_name  = apply_filters( 'intercessor_prayer_from_name', $from_name, $requester_id );
		$from_email = intercessor_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
		$from_email = apply_filters( 'intercessor_admin_prayer_from_address', $from_email );

		// Email subject.
		$subject = esc_html__( 'Prayer request reports', 'intercessor' );
		$subject = apply_filters( 'intercessor_admin_prayer_notification_subject', wp_strip_all_tags( $subject ), $requester_id );

		// Email header.
		$heading = esc_html__( 'Your Prayer Records', 'intercessor' );
		$heading = apply_filters( 'intercessor_admin_requester_notification_heading', $heading, $requester_id );

		// Message content.
		$message  = sprintf( __( 'Dear %1$s ', 'intercessor' ), $requester_data['first_name'] ) . "\n\n";
		$message .= esc_html__( 'we have been praying for your request(s). This report shows you the number of times your prayer request(s) has been prayed for.', 'intercessor' ) . "\n\n";
		$message .= esc_html__( 'We will continue praying for your request, if it is not already answered. This is a one-time notification for all requesters on our website. But, it will be sent periodically to those who asked to be notified when they submitted their prayer request.', 'intercessor' ) . "\n\n" ;
		$message .= $prayer_reports . "\n\n";
		$message .= esc_html__( 'Moreover, you could submit a praise report if the prayer request has been answered by visiting your ', 'intercessor' ) . $dashboard . "\n\n";
		$message .= esc_html__( 'Remain blessed in the name of Jesus,', 'intercessor' ) . "\n\n";
		$message .= esc_html__( 'The Prayer Team.', 'intercessor' );

		$emails = new Emails();

		// Add action to change email template.
		add_action( 'intercessor_email_send_before', 'intercessor_email_report_template_change' );

		$emails->__set( 'from_name', $from_name );
		$emails->__set( 'from_email', $from_email );
		$emails->__set( 'heading', $heading );

		$headers = apply_filters( 'intercessor_admin_prayer_notification_headers', $emails->get_headers(), $requester_id );
		$emails->__set( 'headers', $headers );

		$emails->send( $requester_data['email'], $subject, $message, '' );

		// Remove the action and filter to change email template.
		remove_action( 'intercessor_email_send_before', 'intercessor_email_report_template_change' );
		remove_filter( 'intercessor_email_template', 'intercessor_email_change_reports_email' );
	}
}

if ( ! function_exists( 'intercessor_email_bottom_text' ) ) {
	/**
	 * Sets up the footer of the emails sent by Intercessor.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	function intercessor_email_bottom_text() {
		// Set up values.
		$site_name    = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$prayers_link = intercessor_get_history_page_uri();
		$user_message = '<a href="' . $prayers_link . '"> ' . esc_attr__( 'Click here ', 'intercessor' ) . ' &raquo;</a>';
		$main_text    = esc_html__( 'You are receiving this email because you submitted a prayer request on our website.', 'intercessor' );
		$main_text   .= esc_html__( 'This email is sent by ', 'intercessor' ) . $site_name . '. ';
		$main_text   .= esc_html__( 'If you would like to view, edit, or delete your prayer request ', 'intercessor' ) . $user_message;
		$main_text   .= esc_html__( 'You can unsubscribe from this email by sending us an email with your request to unsubscribe.', 'intercessor' );
		
		// Return filtered values.
		return apply_filters( 'intercessor_email_bottom_text', $main_text );
	}
}
add_filter( 'intercessor_email_reports_footer_text', 'intercessor_email_bottom_text' );

if ( ! function_exists( 'intercessor_email_report_template_change' ) ) {
	/**
	 * Adds filter to change email template.
	 *
	 * @return void
	 * @since 1.1.0
	 */
	function intercessor_email_report_template_change() {
		add_filter( 'intercessor_email_template', 'intercessor_email_change_reports_email' );
	}
}

if ( ! function_exists( 'intercessor_email_change_reports_email' ) ) {
	/**
	 * Change reports email template.
	 *
	 * @param string $template_name Template name.
	 *
	 * @since 1.1.0
	 * @return string|void
	 */
	function intercessor_email_change_reports_email( string $template_name ) {
		return esc_attr( 'prayed' );
	}
}
