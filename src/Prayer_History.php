<?php
/**
 * Intercessor Prayer History
 *
 * @since       0.9.5
 * @subpackage  Classes/Intercessor\Prayer_History
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0php GNU Public License
 * @package     Intercessor
 */

namespace Intercessor;

use function intercessor_add_item_meta;
use function intercessor_clean;
use function intercessor_process_item;
use function intercessor_set_error;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Prayer_History
 *
 * @package IPR
 * @since   0.9.5
 */
class Prayer_History {

	/**
	 * Form action.
	 *
	 * @access protected
	 * @var string
	 */
	protected string $action = '';

	/**
	 * Form errors.
	 *
	 * @access private
	 * @var array
	 */
	private array $errors;

	/**
	 * Prayer statuses
	 *
	 * @access public
	 * @var array
	 */
	public array $status = [];
	/**
	 * Prayer_History constructor.
	 *
	 * @since 0.9.5
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'process' ] );
		add_action( 'init', [ $this, 'register' ] );
		add_action( 'intercessor_history_user_register', [ $this, 'register' ] );
	}

	/**
	 * Adds an error.
	 *
	 * @param int    $error_id Error ID.
	 * @param string $message  Error message.
	 *
	 * @since 0.9.5
	 */
	public function add_error(int $error_id, string $message = '' ): void
    {
		$this->errors[ $error_id ] = $message;
	}

	/**
	 * Displays errors.
	 *
	 * @since 0.9.5
	 * @return void
	 */
	public function print_errors(): void
    {
		// Bailout if no error.
		if ( empty( $this->errors ) ) {
			return;
		}

		echo '<div class="intercessor-errors">';

		foreach ( $this->errors as $error_id => $error ) {

			echo '<p class="intercessor-error">' . esc_html( $error ) . '</p>';

		}

		echo '</div>';

	}

	/**
	 * Get errors
	 *
	 * @since 0.9.5
	 * @return array
	 */
	public function get_errors(): array
    {
		// Bailout if no error.
		if ( empty( $this->errors ) ) {
			return [];
		}

		return $this->errors;

	}

	/**
	 * Gets the action (URL for forms to post to).
	 *
	 * @return string
	 */
	public function get_action(): string
    {
		return esc_url_raw( $this->action ? $this->action : wp_unslash( $_SERVER['REQUEST_URI'] ) );
	}

	/**
	 * Retrieve the prayer history.
	 *
	 * @param string $redirect Page to redirect to.
	 *
	 * @return mixed|void
	 *@since  0.9.5
	 */
	public function get_history( string $redirect = '' ) {
		global $ipr_prayer_history_redirect;

		if ( empty( $redirect ) ) {
			$redirect = $this->get_action();
		}

		$ipr_prayer_history_redirect = $redirect;

		ob_start();

		\intercessor_get_template_part( 'history', 'prayers' );

		return apply_filters( 'intercessor_prayer_history', ob_get_clean() );
	}

	/**
	 * Process prayer history.
	 *
	 * @return bool|string|void
	 * @since 0.9.5
	 */
	public function process() {
		// Bailout if nonce did not verify.
		if ( ! isset( $_POST['ipr_prayer_history_nonce'] ) || ! wp_verify_nonce( $_POST['ipr_prayer_history_nonce'], 'ipr-prayer-history-nonce' ) ) {
			return false;
		}

		$history_submit = isset( $_POST['ipr_history_submit'] ) ? intercessor_clean( $_POST['ipr_history_submit'] ) : '';
		$prayer_delete  = isset( $_POST['ipr_history_delete'] ) ? intercessor_clean( $_POST['ipr_history_delete'] ) : '';
		$prayer_id      = (int) ! empty( $_POST['ipr_history_id'] ) ? intercessor_clean( $_POST['ipr_history_id'] ) : 0;

		// Process update prayer request.
		if ( ! empty( $prayer_id ) && 'Update' === $history_submit ) {
			/**
			 * Fires before prayer history actions are processed.
			 *
			 * @since 0.9.5
			 */
			do_action( 'intercessor_pre_process_history_prayer' );

			// Bailout if user is not logged in.
			if ( ! is_user_logged_in() ) {
				return;
			}

			// Process answered prayer.
			$answered = (int) ! empty( $_POST['ipr_history_answered_prayer'] ) ? \wp_unslash( intercessor_clean( $_POST['ipr_history_answered_prayer'] ) ) : 0;
			if ( $answered ) {
				$prayer = intercessor_process_item( 'prayer', 'get', $prayer_id, false );
				intercessor_add_item_meta( 'prayer', $prayer->id, 'answered_prayer', 1, true );
			}

			// Loop through required fields and display error message.
			foreach ( $this->get_required_fields() as $field_name => $value ) {
				$field = \sanitize_text_field( $_POST[ $field_name ] );

				if ( empty( $field ) ) {
					$this->add_error( $value['error_id'], $value['error_message'] );
				}
			}

			// Process actions if there is no error.
			if ( empty( $this->errors ) ) {
				$history_data = [
					'title'   => ! empty( $_POST['ipr_history_title'] ) ? intercessor_clean( $_POST['ipr_history_title'] ) : false,
					'message' => ! empty( $_POST['ipr_history_message'] ) ? \intercessor_sanitize_textarea( $_POST['ipr_history_message'] ) : false,
					'share'   => ! empty( $_POST['ipr_history_share'] ) ? intercessor_clean( $_POST['ipr_history_share'] ) : false,
					'notify'  => (int) ! empty( $_POST['ipr_history_notify'] ) ? intercessor_clean( $_POST['ipr_history_notify'] ) : 0,
				];

				if ( empty( $history_data ['title'] ) ) {
					intercessor_set_error(
						343,
						esc_html__('Please specify the title of your prayer request.', 'intercessor' )
					);
				}

				// Process prayer status. Defaults to 'pending'.
				$history_data['status'] = 'pending';

				if ( 'personal' === $history_data['share'] ) {
					$history_data['status'] = 'personal';
				} elseif ( ! intercessor_hold_prayers() ) {
					$history_data['status'] = 'active';
				}

				// Update prayer request.
				$updated = intercessor_process_item( 'prayer', 'update', $prayer_id, $history_data );

				if ( ! $updated ) {
					$new_prayer = intercessor_process_item( 'prayer', 'get', $updated, false );

					if ( 'personal' === $new_prayer->status ) {
						$message = esc_html__( 'Your prayer request is now private. It will not be displayed on the frontend of our website anymore.', 'intercessor' );

						return intercessor_display_frontend_notice(
							$message,
							true,
							'success'
						);
					} elseif ( 'active' === $new_prayer->status ) {
						$message = esc_html__( 'Your prayer request has been successfully updated.', 'intercessor' );

						return intercessor_display_frontend_notice(
							$message,
							true,
							'success'
						);
					} elseif ( 'pending' === $new_prayer->status ) {
						$message = esc_html__( 'Your prayer request has been successfully updated, but must be verified and approved by an admin staff.', 'intercessor' );

						return intercessor_display_frontend_notice(
							$message,
							true,
							'success'
						);
					}
				} else {
					$message = esc_html__( 'There was an error updating your prayer request. Please contact support or refresh your browser and try again.', 'intercessor' );

					return intercessor_display_frontend_notice(
						$message,
						true,
						'error'
					);
				}
			}
		} elseif ( $prayer_delete ) {
			// Process delete prayer.
			intercessor_process_item( 'prayer', 'delete', $prayer_id, false );
		}
	}

	/**
	 * Get list of required fields.
	 *
	 * @since 0.9.5
	 * @return mixed|void
	 */
	public function get_required_fields() {
		$required_fields = [
			'ipr_history_title' => [
				'error_id'      => 'empty_title',
				'error_message' => esc_html__( 'Please enter the prayer title', 'intercessor' ),
			],
			'ipr_history_message' => [
				'error_id'      => 'empty_body',
				'error_message' => esc_html__( 'Please enter a your prayer request', 'intercessor' ),
			],
			'ipr_history_share' => [
				'error_id'      => 'empty_share',
				'error_message' => esc_html__( 'Please choose how we share your prayer request', 'intercessor' ),
			],
		];

		return apply_filters( 'ipr_prayer_history_required_fields', $required_fields );
	}

	/**
	 * Process Registration Form on Prayer History Page
	 *
	 * @since 0.9.5
	 *
	 * @param array $data Data sent from the register form.
	 *
	 * @return void
	*/
	public function register( $data ) {

		// Bailout if user is already logged in.
		if ( \is_user_logged_in() ) {
			return;
		}

		// Bailout if nonce do not verify.
		if ( ! isset( $_POST['intercessor_history_register_nonce'] ) ||
			! wp_verify_nonce( $_POST['intercessor_history_register_nonce'], 'intercessor-history-register-nonce' )
		) {
		//	intercessor_set_error( 'invalide_nonce', esc_html__( 'Nonce did not verify', 'intercessor' ) );
			return;
		}

		// Bailout if no registration submitted.
		$submitted = isset( $_POST['intercessor_register_submit'] ) ? intercessor_clean( $_POST['intercessor_register_submit'] ) : '';
		if ( empty( $submitted ) ) {
			return;
		}

		// Check if captcha is enabled and validate the values.
		if ( ! is_user_logged_in() && \intercessor_recaptcha_is_enabled() ) {
			$data = [
				'g-recaptcha-response' => isset( $_POST['g-recaptcha-response'] ) ? esc_attr( $_POST['g-recaptcha-response'] ) : '',
				'g-recaptcha-remoteip' => isset( $_POST['g-recaptcha-remoteip'] ) ? esc_attr( $_POST['g-recaptcha-remoteip'] ) : '',
			];

			if ( ! \intercessor_is_valid_recaptcha_response( $data ) ) {
				$message = \intercessor_get_option( 'captcha_message'  );
				intercessor_set_error( 'failed-captcha', $message );
			}
		}

		// Bailout if it is spam registration.
		if ( ! empty( $_POST['intercessor_history_honeypot'] ) ) {
			$spam_msg = esc_html__( 'Nice try honey bear, don\'t touch our honey', 'intercessor' );
			intercessor_set_error( 'spam-prayer', $spam_msg );
		}

		/**
		 * Fires before the history register form is processed.
		 *
		 * @since 0.9.5
		 */
		do_action( 'intercessor_pre_process_register_form' );

		// Setup new user data.
		$user_data = [
			'user_email' => isset( $_POST['intercessor_history_email'] ) ? intercessor_clean( $_POST['intercessor_history_email'] ) : false,
			'user_login' => isset( $_POST['intercessor_history_login'] ) ? intercessor_clean( $_POST['intercessor_history_login'] ) : false,
			'user_pass'  => isset( $_POST['intercessor_history_pass'] ) ? intercessor_clean( $_POST['intercessor_history_pass'] ) : false,
			'user_pass2' => isset( $_POST['intercessor_history_pass2'] ) ? intercessor_clean( $_POST['intercessor_history_pass2'] ) : false,
		];


		if ( empty( $user_data['user_login'] ) ) {
			intercessor_set_error( 'empty_username', esc_html__( 'Invalid username', 'intercessor' ) );
		}

		if ( username_exists( $user_data['user_login'] ) ) {
			intercessor_set_error( 'username_unavailable', esc_html__( 'Username already taken', 'intercessor' ) );
		}

		if ( ! validate_username( $user_data['user_login'] ) ) {
			intercessor_set_error( 'username_invalid', esc_html__( 'Invalid username', 'intercessor' ) );
		}

		if ( email_exists( $user_data['user_email'] ) ) {
			intercessor_set_error( 'email_unavailable', esc_html__( 'Email address already taken', 'intercessor' ) );
		}

		if ( empty( $user_data['user__email'] ) || ! is_email( $user_data['user_email'] ) ) {
			intercessor_set_error( 'email_invalid', esc_html__( 'Invalid email', 'intercessor' ) );
		}

		if ( empty( $user_data['user_pass'] ) ) {
			intercessor_set_error( 'empty_password', esc_html__( 'Please enter a password', 'intercessor' ) );
		}

		if ( ! intercessor_validate_new_password( $user_data['user_pass'] ) ) {
			intercessor_set_error( 'short_password', esc_html__( 'Password should be at least 8 character long', 'intercessor' ) );
		}

		if ( ( ! empty( $user_data['user_pass'] ) && empty( $user_data['user_pass2'] ) ) || ( $user_data['user_pass'] !== $user_data['user_pass2'] ) ) {
			intercessor_set_error( 'password_mismatch', esc_html__( 'Passwords do not match', 'intercessor' ) );
		}

		/**
		 * Fires when the history register form is processed.
		 *
		 * @since 0.9.5
		 */
		do_action( 'intercessor_process_register_form' );

		// Check for errors and redirect if none present.
		$default_page = intercessor_is_prayer_history_page();
		\intercessor_create_new_requester( $user_data );

		\intercessor_redirect( $default_page );
	}

}
