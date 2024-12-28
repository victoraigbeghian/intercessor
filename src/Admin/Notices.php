<?php
/**
 * Intercessor Admin Notices
 *
 * @package   	Intercessor
 * @subpackage  Admin/Notices
 * @author    	Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0.php GNU Public License
 * @copyright 	Copyright (c) 2019 Victor Aigbeghian
 * @version		0.9.5
 */

namespace Intercessor\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Notices Class
 *
 * @since 0.9.5
 */
class Notices {

	/**
	 * @var array Array of notices to output to the current user
	 */
	private $notices = [];

	/**
	 * Get things started
	 *
	 * @since 0.9.5
	 */
	public function __construct() {
		add_action( 'intercessor_dismiss_notices', [ $this, 'dismiss_notices' ] );
		add_action( 'admin_init', [ $this, 'add_notices' ], 20 );
		add_action( 'admin_notices', [ $this, 'output_notices' ], 30 );
	}

	/**
	 * Add a notice to the notices array
	 *
	 * @param array $args Array of arguments.
	 *
	 * @since 0.9.5
	 */
	public function add_notice( array $args = [] ) {

		// Parse args
		$r = wp_parse_args(
			$args,
			[
				'id'             => '',
				'message'        => '',
				'class'          => false,
				'is_dismissible' => true,
			]
		);

		$default_class = 'updated';

		// One message as string.
		if ( is_string( $r['message'] ) ) {
			$message       = '<p>' . $this->esc_notice( $r['message'] ) . '</p>';

		} elseif ( is_array( $r['message'] ) ) {
			$message       = '<p>' . implode( '</p><p>', array_map( [ $this, 'esc_notice' ], $r['message'] ) ) . '</p>';

		// Messages as objects.
		} elseif ( \is_wp_error( $r['message'] ) ) {
			$default_class = 'is-error';
			$errors        = $r['message']->get_error_messages();

			switch ( count( $errors ) ) {
				case 0:
					return false;

				case 1:
					$message = '<p>' . $this->esc_notice( $errors[0] ) . '</p>';
					break;

				default:
					$escaped = array_map( [ $this, 'esc_notice' ], $errors );
					$message = '<ul>' . "\n\t" . '<li>' . implode( '</li>' . "\n\t" . '<li>', $escaped ) . '</li>' . "\n" . '</ul>';
					break;
			}

			// Message is an unknown format, so bail.
		} else {
			return false;
		}

		// CSS Classes.
		$classes = ! empty( $r['class'] )
			? [ $r['class'] ]
			: [ $default_class ];

		// Add dismissible class.
		if ( ! empty( $r['is_dismissible'] ) ) {
			array_push( $classes, 'is-dismissible' );
		}

		// Assemble the message.
		$message = '<div class="notice ' . implode( ' ', array_map( 'sanitize_html_class', $classes ) ) . '">' . $message . '</div>';
		$message = str_replace( "'", "\'", $message );

		// Avoid malformed notices variable.
		if ( ! is_array( $this->notices ) ) {
			$this->notices = [];
		}

		// Add notice to $notices array.
		$this->notices[] = $message;
	}

	/**
	 * Add all admin area notices
	 *
	 * @since 0.9.5
	 */
	public function add_notices() {

		// User can edit pages
		if ( current_user_can( 'edit_pages' ) ) {
			$this->add_page_notices();
		}

		// User can manage Intercessor settings.
		if ( current_user_can( 'manage_prayer_settings' ) ) {
			$this->add_settings_notices();
		}

		// Generic notices.
		if ( ! empty( $_REQUEST['intercessor-message'] ) ) {
			$this->add_user_action_notices( $_REQUEST['intercessor-message'] );
		}
	}

	/**
	 * Dismiss admin notices when dismiss links are clicked
	 *
	 * @since 0.9.5
	 */
	public function dismiss_notices() {

		// Bail if no notices to dismiss.
		if ( empty( $_GET['intercessor_notice'] ) || empty( $_GET['_wpnonce'] ) ) {
			return;
		}

		// Construct key we are dismissing.
		$key = sanitize_key( $_GET['intercessor_notice'] );

		// Bail if sanitized notice is empty.
		if ( empty( $key ) ) {
			return;
		}

		// Bail if nonce does not verify.
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'intercessor_notice_nonce' ) ) {
			return;
		}

		// Dismiss notice.
		intercessor_update_option( $key, 1 );
		wp_redirect(
			remove_query_arg(
				[
					'intercessor_action',
					'intercessor_notice',
					'_wpnonce',
				]
			)
		);
		exit;
	}

	/**
	 * Output all notices in the admin area
	 *
	 * @since 0.9.5
	 */
	public function output_notices() {

		// Bail if no notices.
		if ( empty( $this->notices ) || ! is_array( $this->notices ) ) {
			return;
		}

		// Start an output buffer.
		ob_start();

		// Loop through notices, and add them to buffer.
		foreach ( $this->notices as $notice ) {
			echo $notice;
		}

		// Output the current buffer.
		$notices = ob_get_clean();

		// Only echo if not empty.
		if ( ! empty( $notices ) ) {
			echo $notices;
		}
	}

	/** Private Methods *******************************************************/

	/**
	 * Notices about missing pages
	 *
	 * @since 0.9.5
	 */
	private function add_page_notices() {

		// Prayer request form page is missing
		$prayer_page  = \intercessor_get_option( 'form_page', '' );
		$listing_page = \intercessor_get_option( 'prayers_page', '' );
		if ( empty( $prayer_page ) || ( 'trash' === \get_post_status( $prayer_page ) ) && ! intercessor_get_option( 'dismissed_notice_prayers' ) ) {
			$this->add_notice(
				[
					'id'             => 'intercessor-no-prayer-page',
					'message'        => sprintf( __( 'No prayer request form page is configured. Set one in <a href="%s">Settings</a>.', 'intercessor' ), \admin_url( 'admin.php?page=intercessor-settings' ) ),
					'class'          => 'error',
					'is_dismissible' => false,
				]
		);
		}

		if ( empty( $listing_page ) || ( 'trash' === get_post_status( $listing_page ) ) && ! intercessor_get_option( 'dismissed_notice_listings' ) ) {
			$this->add_notice(
				[
					'id'             => 'intercessor-no-listing-page',
					'message'        => sprintf( __( 'No prayer listing page is configured. Set one in <a href="%s">Settings</a>.', 'intercessor' ), \admin_url( 'admin.php?page=intercessor-settings' ) ),
					'class'          => 'error',
					'is_dismissible' => false,
			]
		);
		}
	}

	/**
	 * Notices about settings (updating, etc...)
	 *
	 * @since 0.9.5
	 */
	private function add_settings_notices() {

		// Settings area.
		if ( ! empty( $_GET['page'] ) && ( 'intercessor-settings' === $_GET['page'] ) ) {

			// Settings updated.
			if ( ! empty( $_GET['settings-updated'] ) ) {
				$this->add_notice(
					[
						'id'      => 'intercessor-notices',
						'message' => esc_html__( 'Settings successfully updated.', 'intercessor' ),
					]
				);
			}
		}
	}

	/**
	 * Notices about actions that the user has taken
	 *
	 * @param string $notice Notice.
	 *
	 * @since 0.9.5
	 */
	private function add_user_action_notices( string $notice = '' ) {

		// Sanitize notice key.
		$notice = sanitize_key( $notice );

		// Bail if notice is empty.
		if ( empty( $notice ) ) {
			return;
		}

		// Prayer settings, add and edit screen errors.
		if ( current_user_can( 'manage_prayer_settings' ) ) {
			switch ( $notice ) {
				case 'settings-imported' :
					$this->add_notice( array(
						'id'      => 'intercessor-settings-imported',
						'message' => esc_html__( 'The settings have been imported.', 'intercessor' )
					) );
					break;
				case 'prayer_added' :
					$this->add_notice( array(
						'id'      => 'intercessor-prayer-added',
						'message' => esc_html__( 'Prayer request added.', 'intercessor' )
					) );
					break;
				case 'prayer_add_failed' :
					$this->add_notice( array(
						'id'      => 'intercessor-prayer-add-fail',
						'message' => esc_html__( 'There was a problem adding that prayer request, please try again.', 'intercessor' ),
						'class'   => 'error'
					) );
					break;
				case 'prayer_exists' :
					$this->add_notice( array(
						'id'      => 'intercessor-prayer-exists',
						'message' => esc_html__( 'A prayer request with that title already exists, please use a different title.', 'intercessor' ),
						'class'   => 'error'
					) );
					break;
				case 'prayer_updated' :
					$this->add_notice( array(
						'id'      => 'intercessor-prayer-updated',
						'message' => esc_html__( 'Prayer request updated.', 'intercessor' )
					) );
					break;
				case 'prayer_not_changed' :
					$this->add_notice( array(
						'id'      => 'intercessor-prayer-not-changed',
						'message' => esc_html__( 'No changes were made to that prayer request.', 'intercessor' )
					) );
					break;
				case 'prayer_update_failed' :
					$this->add_notice( array(
						'id'      => 'intercessor-prayer-updated-fail',
						'message' => esc_html__( 'There was a problem updating that prayer request, please try again.', 'intercessor' ),
						'class'   => 'error'
					) );
					break;
				case 'prayer_validation_failed' :
					$this->add_notice( array(
						'id'      => 'intercessor-prayer-validation-fail',
						'message' => esc_html__( 'The prayer request could not be added because one or more of the required fields was empty, please try again.', 'intercessor' ),
						'class'   => 'error'
					) );
					break;
                case 'prayer_deleted':
                    $this->add_notice( array(
                        'id'      => 'intercessor-prayer-deleted',
                        'message' => esc_html__( 'Prayer request and prayed for counts deleted.', 'intercessor' )
                    ) );
                    break;
				case 'prayer_delete_failed':
					$this->add_notice( array(
						'id'      => 'intercessor-prayer-delete-fail',
						'message' => esc_html__( 'There was a problem deleting that prayer request, please try again.', 'intercessor' ),
						'class'   => 'error'
					) );
					break;
				case 'prayer_activated':
					$this->add_notice( array(
						'id'      => 'intercessor-prayer-activated',
						'message' => esc_html__( 'Prayer request activated.', 'intercessor' )
					) );
					break;
				case 'prayer_activation_failed':
					$this->add_notice( array(
						'id'      => 'intercessor-prayer-activation-fail',
						'message' => esc_html__( 'There was a problem activating that prayer request, please try again.', 'intercessor' ),
						'class'   => 'error'
					) );
					break;

				case 'prayer_uplifted':
					$this->add_notice( array(
						'id'      => 'intercessor-prayer-uplifted',
						'message' => esc_html__( 'Amen. You have prayed for the Prayer request.', 'intercessor' )
					) );
					break;
				case 'prayer_uplift_failed':
					$this->add_notice( array(
						'id'      => 'intercessor-prayer-uplift-fail',
						'message' => esc_html__( 'There was a problem praying for that prayer request, please try again.', 'intercessor' ),
						'class'   => 'error'
					) );
					break;

				case 'prayer_answered':
					$this->add_notice( array(
						'id'      => 'intercessor-prayer-answered',
						'message' => esc_html__( 'Praise the Lord. You have marked the prayer request as answered.', 'intercessor' )
					) );
					break;
				case 'prayer_answered_failed':
					$this->add_notice( array(
						'id'      => 'intercessor-prayer-answered-fail',
						'message' => esc_html__( 'There was a problem marking that prayer request as answered, please try again.', 'intercessor' ),
						'class'   => 'error'
					) );
					break;
                // Set prayer status to pending.
				case 'prayer_deactivated':
					$this->add_notice(
					    [
                            'id'      => 'intercessor-prayer-deactivated',
                            'message' => esc_html__( 'Prayer request status set to pending.', 'intercessor' ),
                        ]
                    );
					break;
				case 'prayer_deactivation_failed':
					$this->add_notice(
					    [
                            'id'      => 'intercessor-prayer-deactivation-fail',
                            'message' => esc_html__( 'There was a problem deactivating that prayer request, please try again.', 'intercessor' ),
                            'class'   => 'error',
                        ]
                    );
					break;

				// Prayer archive.
                case 'prayer_archive':
                    $this->add_notice(
                        [
                            'id'      => 'intercessor-prayer-archived',
                            'message' => esc_html__( 'Prayer request status set to archived.', 'intercessor' ),
                        ]
                    );
                    break;
                case 'prayer_archive_failed':
                    $this->add_notice(
                        [
                            'id'      => 'intercessor-prayer-archive-fail',
                            'message' => esc_html__( 'There was a problem archiving that prayer request, please try again.', 'intercessor' ),
                            'class'   => 'error',
                        ]
                    );
                    break;
			}
		}

		// Prayer Reports errors.
		if ( current_user_can( 'view_prayer_reports' ) ) {
			switch( $notice ) {
				case 'email_sent' :
					$this->add_notice( array(
						'id'      => 'intercessor-prayer-sent',
						'message' => esc_html__( 'The prayer request notification has been resent.', 'intercessor' )
					) );
					break;
				case 'refreshed-Reports' :
					$this->add_notice( array(
						'id'      => 'intercessor-refreshed-Reports',
						'message' => esc_html__( 'The Reports have been refreshed.', 'intercessor' )
					) );
					break;
				case 'prayer-note-deleted' :
					$this->add_notice( array(
						'id'      => 'intercessor-note-deleted',
						'message' => esc_html__( 'The prayer note has been deleted.', 'intercessor' )
					) );
					break;
				case 'note-added' :
					$this->add_notice( array(
						'id'      => 'intercessor-note-added',
						'message' => esc_html__( 'The note has been added successfully.', 'intercessor' )
					) );
					break;
			}
		}

		// Requester Notices.
		if ( current_user_can( 'edit_prayers' ) ) {
			switch( $notice ) {
				case 'requester-deleted' :
					$this->add_notice( array(
						'id'      => 'intercessor-requester-deleted',
						'message' => esc_html__( 'Requester successfully deleted', 'intercessor' )
					) );
					break;
				case 'user-verified' :
					$this->add_notice( array(
						'id'      => 'intercessor-user-verified',
						'message' => esc_html__( 'User successfully verified', 'intercessor' )
					) );
					break;
				case 'email-added' :
					$this->add_notice( array(
						'id'      => 'intercessor-requester-email-added',
						'message' => esc_html__( 'Requester email added', 'intercessor' )
					) );
					break;
				case 'email-removed' :
					$this->add_notice( array(
						'id'      => 'intercessor-requester-email-removed',
						'message' => esc_html__( 'Requester email removed', 'intercessor')
					) );
					break;
				case 'email-remove-failed' :
					$this->add_notice( array(
						'id'      => 'intercessor-requester-email-remove-failed',
						'message' => esc_html__( 'Failed to remove requester email', 'intercessor'),
						'class'   => 'error'
					) );
					break;
				case 'primary-email-updated' :
					$this->add_notice( array(
						'id'      => 'iprintercessor-requester-primary-email-updated',
						'message' => esc_html__( 'Primary email updated for requester', 'intercessor'),
						'class'   => 'error'
					) );
					break;
				case 'primary-email-failed' :
					$this->add_notice( array(
						'id'      => 'intercessor-requester-primary-email-failed',
						'message' => esc_html__( 'Failed to set primary email', 'intercessor'),
						'class'   => 'error'
					) );
					break;
			}
		}
	}

	/**
	 * Escape message string output
	 *
	 * @param string $message Message to display.
	 *
	 * @return string
	 */
	private function esc_notice( string $message = '' ): string {
		$tags = wp_kses_allowed_html( 'post' );

		return wp_kses( $message, $tags );
	}
}
