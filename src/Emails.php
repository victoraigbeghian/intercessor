<?php
/**
 * Intercessor Emails
 *
 * @package     Intercessor
 * @subpackage  Classes/Emails
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       0.9.5
 */

namespace Intercessor;

use function intercessor_get_option;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Emails Class
 *
 * @since 0.9.5
 */
class Emails {

	/**
	 * Holds the from address
	 *
	 * @since 0.9.5
	 * @access private
	 * @var string
	 */
	private $from_address;

	/**
	 * Holds the from name
	 *
	 * @since 0.9.5
	 * @access private
	 * @var string
	 */
	private $from_name;

	/**
	 * Holds the email content type
	 *
	 * @since 0.9.5
	 * @access private
	 * @var string
	 */
	private $content_type;

	/**
	 * Holds the email headers
	 *
	 * @since 0.9.5
	 * @access private
	 * @var string
	 */
	private $headers;

	/**
	 * Whether to send email in HTML
	 *
	 * @since 0.9.5
	 * @access private
	 * @var bool
	 */
	private $html = true;

	/**
	 * The email template to use
	 *
	 * @since 0.9.5
	 * @access private
	 * @var string
	 */
	private $template;

	/**
	 * The header text for the email
	 *
	 * @since  0.9.5
	 * @access public
	 * @var string
	 */
	public $heading = '';

	/**
	 * Container for storing all tags
	 *
	 * @since 0.9.5
	 * @access private
	 * @var array
	 */
	private $tags = [];

	/**
	 * Prayer ID
	 *
	 * @since 0.9.5
	 * @access private
	 * @var int
	 */
	private $prayer_id;

	/**
	 * Get things going
	 *
	 * @access public
	 * @since 0.9.5
	 */
	public function __construct() {

		if ( 'none' === $this->get_template() ) {
			$this->html = false;
		}

		add_action( 'intercessor_email_send_before', [ $this, 'send_before' ] );
		add_action( 'intercessor_email_send_after', [ $this, 'send_after' ] );

	}

	/**
	 * Set a property
	 *
	 * @param string $key   Specified key.
	 * @param string $value The value.
	 *
	 * @access public
	 * @since 0.9.5
	 */
	public function __set( string $key, string $value ) {
		$this->$key = $value;
	}

	/**
	 * Get a property
	 *
	 * @param string $key The key.
	 *
	 * @access public
	 * @return string $key The specified key.
	 * @since 0.9.5
	 *
	 */
	public function __get( string $key ) {
		return $this->$key;
	}

	/**
	 * Get the email from address
	 *
	 * @access public
	 * @return mixed|void
	 * @since 0.9.5
	 */
	public function get_from_address() {
		if ( ! $this->from_address ) {
			$this->from_address = intercessor_get_option( 'from_email' );
		}

		if ( empty( $this->from_address ) || ! is_email( $this->from_address ) ) {
			$this->from_address = get_option( 'admin_email' );
		}

		return apply_filters( 'intercessor_email_from_address', $this->from_address, $this );
	}

	/**
	 * Get the email content type
	 *
	 * @access public
	 * @return mixed|void
	 * @since 0.9.5
	 */
	public function get_content_type() {
		if ( ! $this->content_type && $this->html ) {
			$this->content_type = apply_filters( 'intercessor_email_default_content_type', 'text/html', $this );
		} elseif ( ! $this->html ) {
			$this->content_type = 'text/plain';
		}

		return apply_filters( 'intercessor_email_content_type', $this->content_type, $this );
	}

	/**
	 * Get the email headers
	 *
	 * @access public
	 * @return mixed|void
	 * @since 0.9.5
	 */
	public function get_headers() {
		if ( ! $this->headers ) {
			$this->headers  = "From: {$this->get_from_name()} <{$this->get_from_address()}>\r\n";
			$this->headers .= "Reply-To: {$this->get_from_address()}\r\n";
			$this->headers .= "Content-Type: {$this->get_content_type()}; charset=utf-8\r\n";
		}

		return apply_filters( 'intercessor_email_headers', $this->headers, $this );
	}

	/**
	 * Retrieve email templates
	 *
	 * @access public
	 * @return mixed|void
	 * @since 0.9.5
	 */
	public function get_templates() {
		$templates = [
			'default' => esc_html__( 'Default Template', 'intercessor' ),
			'none'    => esc_html__( 'Plain text only, no template', 'intercessor' ),
		];

		return apply_filters( 'intercessor_email_templates', $templates );
	}

	/**
	 * Get the enabled email template
	 *
	 * @return mixed|void
	 * @since 0.9.5
	 *
	 * @access public
	 */
	public function get_template() {
		if ( ! $this->template ) {
			$this->template = intercessor_get_option( 'email_template', 'default' );
		}

		return apply_filters( 'intercessor_email_template', $this->template );
	}

	/**
	 * Get the header text for the email
	 *
	 * @access public
	 * @return mixed|void
	 * @since 0.9.5
	 */
	public function get_heading() {
		return apply_filters( 'intercessor_email_heading', $this->heading );
	}

	/**
	 * Parse email template tags
	 *
	 * @param string $content Tag content.
	 *
	 * @access public
	 * @return string
	 * @since 0.9.5
	 *
	 */
	public function parse_tags( string $content ) : string {

		// The email tags are parsed during setup for email notifications.
		return $content;
	}

	/**
	 * Setup the final email
	 *
	 * @param string $message The email message.
	 *
	 * @return string
	 * @since 0.9.5
	 * @access public
	 */
	public function setup_email( string $message ) : string {

		if ( false === $this->html ) {
			return apply_filters( 'intercessor_email_message', wp_strip_all_tags( $message ), $this );
		}

		$message = $this->text_to_html( $message );

		ob_start();

		intercessor_get_template_part( 'emails/header', $this->get_template(), true );

		/**
		 * Hooks into the email header
		 *
		 * @since 0.9.5
		 */
		do_action( 'intercessor_email_header', $this );

		if ( has_action( 'intercessor_email_template_' . $this->get_template() ) ) {
			/**
			 * Hooks into the template of the email
			 *
			 * @param string $this->template Gets the enabled email template
			 * @since 0.9.5
			 */
			do_action( 'intercessor_email_template_' . $this->get_template() );
		} else {
			intercessor_get_template_part( 'emails/body', $this->get_template(), true );
		}

		/**
		 * Hooks into the body of the email
		 *
		 * @since 0.9.5
		 */
		do_action( 'intercessor_email_body', $this );

		intercessor_get_template_part( 'emails/footer', $this->get_template(), true );

		/**
		 * Hooks into the footer of the email
		 *
		 * @since 0.9.5
		 */
		do_action( 'intercessor_email_footer', $this );

		$body    = ob_get_clean();
		$message = str_replace( '{email}', $message, $body );

		return apply_filters( 'intercessor_email_message', $message, $this );
	}

	/**
	 * Send the email
	 *
	 * @param  mixed        $to The To address to send to.
	 * @param  string       $subject The subject line of the email to send.
	 * @param  string       $message The body of the email to send.
	 * @param  string|array $attachments Attachments to the email in a format supported by wp_mail().
	 *
	 * @since 0.9.5
	 * @return bool
	 */
	public function send( $to, string $subject, string $message, $attachments = '' ) {

		if ( ! did_action( 'init' ) && ! did_action( 'admin_init' ) ) {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'You cannot send email with Emails until init/admin_init has been reached', 'intercessor' ), null );
			return false;
		}

		/**
		 * Hooks before the email is sent
		 *
		 * @since 0.9.5
		 */
		do_action( 'intercessor_email_send_before', $this );

		$subject = $this->parse_tags( $subject );
		$message = $this->parse_tags( $message );

		$message = $this->setup_email( $message );

		$attachments = apply_filters( 'intercessor_email_attachments', $attachments, $this );

		$sent       = wp_mail( $to, $subject, $message, $this->get_headers(), $attachments );
		$log_errors = apply_filters( 'intercessor_log_email_errors', true, $to, $subject, $message );

		if ( ! $sent && true === $log_errors ) {
			if ( is_array( $to ) ) {
				$to = implode( ',', $to );
			}

			$log_message = sprintf(
				// Translators: Email could not be sent.
				esc_html__( "Email from Intercessor could not be sent.\nSend time: %1\$s\nTo: %2\$s\nSubject: %3\$s\n\n", 'intercessor' ),
				date_i18n( 'F j Y H:i:s', current_time( 'timestamp' ) ),
				$to,
				$subject
			);

			error_log( $log_message );
		}

		/**
		 * Hooks after the email is sent
		 *
		 * @since 0.9.5
		 */
		do_action( 'intercessor_email_send_after', $this );

		return $sent;

	}

	/**
	 * Add filters / actions before the email is sent
	 *
	 * @since 0.9.5
	 */
	public function send_before() {
		add_filter( 'wp_mail_from', [ $this, 'get_from_address' ] );
		add_filter( 'wp_mail_from_name', [ $this, 'get_from_name' ] );
		add_filter( 'wp_mail_content_type', [ $this, 'get_content_type' ] );
	}

	/**
	 * Remove filters / actions after the email is sent
	 *
	 * @since 0.9.5
	 */
	public function send_after() {
		remove_filter( 'wp_mail_from', [ $this, 'get_from_address' ] );
		remove_filter( 'wp_mail_from_name', [ $this, 'get_from_name' ] );
		remove_filter( 'wp_mail_content_type', [ $this, 'get_content_type' ] );

		// Reset heading to an empty string.
		$this->heading = '';
	}

	/**
	 * Converts text to formatted HTML. This is primarily for turning line breaks into <p> and <br/> tags.
	 *
	 * @param string $message The email message.
	 *
	 * @since 0.9.5
	 *
	 * @return mixed|string
	 */
	public function text_to_html( $message ) {

		if ( 'text/html' === $this->content_type || true === $this->html ) {
			$message = apply_filters( 'intercessor_email_template_wpautop', true ) ? wpautop( $message ) : $message;
			$message = apply_filters( 'intercessor_email_template_make_clickable', true ) ? make_clickable( $message ) : $message;
			$message = str_replace( '&#038;', '&amp;', $message );
		}

		return $message;
	}

	/**
	 * Get the email from name
	 *
	 * @since 0.9.5
	 */
	public function get_from_name() {
		if ( ! $this->from_name ) {
			$this->from_name = intercessor_get_option( 'from_name', get_bloginfo( 'name' ) );
		}

		return apply_filters( 'intercessor_email_from_name', wp_specialchars_decode( $this->from_name ), $this );
	}

	/**
	 * Add an email tag
	 *
	 * @param string $tag Email tag to be replaced in email.
	 * @param string $description The email tag description.
	 * @param callable $func Hook to run when email tag is found.
	 * @param string|null $label Human readable tag label.
	 *
	 * @return void
	 * @since 0.9.5
	 */
	public function add( string $tag, string $description, callable $func, string $label = null ) {
		if ( is_callable( $func ) ) {
			$this->tags[ $tag ] = [
				'tag'         => $tag,
				'label'       => $label,
				'description' => $description,
				'func'        => $func,
			];
		}
	}

	/**
	 * Remove an email tag
	 *
	 * @param string $tag Email tag to remove hook from.
	 *
	 * @since 0.9.5
	 */
	public function remove( string $tag ) {
		unset( $this->tags[ $tag ] );
	}

	/**
	 * Check if $tag is a registered email tag.
	 *
	 * @param string $tag Email tag that will be searched.
	 *
	 * @since 0.9.5
	 *
	 * @return bool
	 */
	public function email_tag_exists( string $tag ) : bool {
		return array_key_exists( $tag, $this->tags );
	}

	/**
	 * Returns a list of all email tags
	 *
	 * @since 0.9.5
	 *
	 * @return array
	 */
	public function get_tags() : array {
		return $this->tags;
	}

	/**
	 * Search content for email tags and filter email tags through their hooks
	 *
	 * @param string $content   Content to search for email tags.
	 * @param int    $prayer_id The prayer id.
	 *
	 * @since 0.9.5
	 *
	 * @return string Content with email tags filtered out.
	 */
	public function do_tags( $content, $prayer_id ) {

		// verify that one tag is added at least.
		if ( empty( $this->tags ) || ! is_array( $this->tags ) ) {
			return $content;
		}

		$this->prayer_id = $prayer_id;

		$new_content = preg_replace_callback( '/{([A-z0-9\-\_]+)}/s', [ $this, 'do_tag' ], $content );

		$this->prayer_id = null;

		return $new_content;
	}

	/**
	 * Do a specific tag, this function should not be used. Please use intercessor_do_emails instead.
	 *
	 * @param string $message Tag message.
	 *
	 * @since 0.9.5
	 *
	 * @return mixed
	 */
	public function do_tag( $message ) {

		// Get tag.
		$tag = $message[1];

		// Return tag if tag not set.
		if ( ! $this->email_tag_exists( $tag ) ) {
			return $message[0];
		}

		return call_user_func( $this->tags[ $tag ]['func'], $this->prayer_id, $tag );
	}
}
