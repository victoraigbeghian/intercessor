<?php
/**
 * Google reCAPTCHA integration.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Util;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\Admin\Settings;

/**
 * Handles all Google reCAPTCHA concerns for the Intercessor plugin.
 *
 * Supports both reCAPTCHA v2 ("I'm not a robot" checkbox) and reCAPTCHA v3
 * (invisible score-based). The active version, site key, secret key, score
 * threshold, and per-form enable toggles are all read from plugin settings.
 *
 * Usage flow:
 *   1. Call Recaptcha::enqueue() from a block render callback to load the
 *      Google script and pass configuration to the front-end JS.
 *   2. In v2 mode, call Recaptcha::widget_html() to render the checkbox div
 *      inside the form. In v3 mode no widget is needed; the JS obtains a
 *      token automatically and appends it to the FormData before submission.
 *   3. Call Recaptcha::verify( $token ) in the AJAX/REST handler to validate
 *      the token against the Google siteverify API.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Recaptcha {

	/**
	 * Google reCAPTCHA v2 script URL.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	private const SCRIPT_V2 = 'https://www.google.com/recaptcha/api.js';

	/**
	 * Google reCAPTCHA v3 script URL (render=explicit suppressed; use explicit).
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	private const SCRIPT_V3 = 'https://www.google.com/recaptcha/api.js?render=%s';

	/**
	 * Google siteverify API endpoint for server-side token validation.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

	/**
	 * WordPress script handle used when enqueuing the Google reCAPTCHA script.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	private const SCRIPT_HANDLE = 'google-recaptcha';

	// -------------------------------------------------------------------------
	// Configuration helpers
	// -------------------------------------------------------------------------

	/**
	 * Return true when reCAPTCHA is globally configured (keys present).
	 *
	 * Does NOT check per-form toggles — use isEnabledForForm() for that.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public static function is_configured(): bool {
		return self::get_site_key() !== '' && self::get_secret_key() !== '';
	}

	/**
	 * Return true when reCAPTCHA is enabled for the prayer request form.
	 *
	 * Requires both global configuration and the per-form toggle.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public static function is_enabled_for_form(): bool {
		return self::is_configured() && (bool) Settings::get( 'recaptcha_enable_form', false );
	}

	/**
	 * Return true when reCAPTCHA is enabled for the prayer history block.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public static function is_enabled_for_history(): bool {
		return self::is_configured() && (bool) Settings::get( 'recaptcha_enable_history', false );
	}

	/**
	 * Return the configured reCAPTCHA version string: 'v2' or 'v3'.
	 *
	 * Defaults to 'v2' when not set.
	 *
	 * @since  1.0.0
	 * @return string 'v2' or 'v3'.
	 */
	public static function get_version(): string {
		$version = Settings::get( 'recaptcha_version', 'v2' );
		return in_array( $version, array( 'v2', 'v3' ), true ) ? $version : 'v2';
	}

	/**
	 * Return the configured reCAPTCHA site key (public key shown in HTML).
	 *
	 * @since  1.0.0
	 * @return string Site key, or empty string if not configured.
	 */
	public static function get_site_key(): string {
		return (string) Settings::get( 'recaptcha_site_key', '' );
	}

	/**
	 * Return the configured reCAPTCHA secret key (used for server-side verify).
	 *
	 * @since  1.0.0
	 * @return string Secret key, or empty string if not configured.
	 */
	public static function get_secret_key(): string {
		return (string) Settings::get( 'recaptcha_secret_key', '' );
	}

	/**
	 * Return the minimum score threshold for reCAPTCHA v3 (0.0 – 1.0).
	 *
	 * Scores at or above this value are considered human. Defaults to 0.5.
	 *
	 * @since  1.0.0
	 * @return float
	 */
	public static function get_score_threshold(): float {
		$raw = Settings::get( 'recaptcha_v3_threshold', '0.5' );
		$val = (float) $raw;
		return max( 0.0, min( 1.0, $val ) );
	}

	// -------------------------------------------------------------------------
	// Front-end enqueue
	// -------------------------------------------------------------------------

	/**
	 * Enqueue the Google reCAPTCHA script and pass configuration to JS.
	 *
	 * For v2 the standard api.js is enqueued. For v3 the site key is baked
	 * into the script URL (required by Google's v3 API). In both cases a
	 * `intercessorRecaptcha` JS object is localised with config the front-end
	 * form script uses.
	 *
	 * Safe to call multiple times on the same page — WordPress deduplicates
	 * enqueues by handle.
	 *
	 * @since  1.0.0
	 * @param  string $action  reCAPTCHA v3 action name (used for analytics). Ignored for v2.
	 * @return void
	 */
	public static function enqueue( string $action = 'intercessor_submit' ): void {
		if ( ! self::is_configured() ) {
			return;
		}

		$siteKey = self::get_site_key();
		$version = self::get_version();

		$scriptUrl = $version === 'v3'
			? sprintf( self::SCRIPT_V3, rawurlencode( $siteKey ) )
			: self::SCRIPT_V2;

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			$scriptUrl,
			array(),
			INTERCESSOR_VERSION,
			true
		);

		// Pass configuration to the front-end form JS.
		wp_localize_script(
			self::SCRIPT_HANDLE,
			'intercessorRecaptcha',
			array(
				'version'   => $version,
				'siteKey'   => $siteKey,
				'action'    => sanitize_key( $action ),
				'v2WidgetId' => 'intercessor-recaptcha-v2',
			)
		);
	}

	// -------------------------------------------------------------------------
	// Front-end output helpers
	// -------------------------------------------------------------------------

	/**
	 * Return the reCAPTCHA v2 checkbox widget HTML for embedding in a form.
	 *
	 * For v3, returns an empty string — the token is obtained programmatically
	 * and does not require any visible widget.
	 *
	 * @since  1.0.0
	 * @return string HTML div for v2, or empty string for v3 / not configured.
	 */
	public static function widget_html(): string {
		if ( ! self::is_configured() || self::get_version() !== 'v2' ) {
			return '';
		}

		return sprintf(
			'<div id="intercessor-recaptcha-v2" class="intercessor-recaptcha-widget g-recaptcha" data-sitekey="%s"></div>',
			esc_attr( self::get_site_key() )
		);
	}

	/**
	 * Return the hidden input that holds the v3 token submitted with the form.
	 *
	 * The JS populates this field before form submission. For v2, returns an
	 * empty string (v2 uses the g-recaptcha-response field automatically).
	 *
	 * @since  1.0.0
	 * @return string HTML hidden input or empty string.
	 */
	public static function token_input_html(): string {
		if ( ! self::is_configured() || self::get_version() !== 'v3' ) {
			return '';
		}

		return '<input type="hidden" name="g-recaptcha-response" id="intercessor-recaptcha-v3-token" value="">';
	}

	// -------------------------------------------------------------------------
	// Server-side verification
	// -------------------------------------------------------------------------

	/**
	 * Verify a reCAPTCHA token against the Google siteverify API.
	 *
	 * Sends a POST request to Google's API using wp_remote_post(). On failure
	 * (WP_Error, HTTP error, or invalid JSON), returns false to fail safe.
	 * For v3 also checks that the response score meets the configured threshold.
	 *
	 * @since  1.0.0
	 * @param  string $token   The g-recaptcha-response token from the form POST.
	 * @param  string $remoteIp Optional. Visitor IP address for Google's fraud detection.
	 * @return bool             True when the token is valid (and score is sufficient for v3).
	 */
	public static function verify( string $token, string $remoteIp = '' ): bool {
		if ( ! self::is_configured() ) {
			// If reCAPTCHA is not configured, skip verification (fail open).
			return true;
		}

		if ( $token === '' ) {
			return false;
		}

		$body = array(
			'secret'   => self::get_secret_key(),
			'response' => $token,
		);

		if ( $remoteIp !== '' ) {
			$body['remoteip'] = $remoteIp;
		}

		$response = wp_remote_post(
			self::VERIFY_URL,
			array(
				'body'    => $body,
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			// Network failure — fail safe (block submission).
			return false;
		}

		$httpCode = wp_remote_retrieve_response_code( $response );
		if ( $httpCode !== 200 ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || empty( $data['success'] ) ) {
			return false;
		}

		// For v3, additionally validate the score.
		if ( self::get_version() === 'v3' ) {
			$score = (float) ( $data['score'] ?? 0.0 );
			return $score >= self::get_score_threshold();
		}

		return true;
	}

	/**
	 * Extract and sanitize the reCAPTCHA token from a POST submission via Request.
	 *
	 * Accepts an optional Request instance so callers that have already
	 * captured the request can pass it in; falls back to Request::capture()
	 * when none is provided.
	 *
	 * @since  1.0.0
	 * @param  \Intercessor\Http\Request|null $request Optional request instance.
	 * @return string Sanitized token string, or empty string if not present.
	 */
	public static function get_token_from_post( ?\Intercessor\Http\Request $request = null ): string {
		$req = $request ?? \Intercessor\Http\Request::capture();
		return $req->get_string( 'g-recaptcha-response' );
	}

	/**
	 * Return the visitor's remote IP address via Request.
	 *
	 * Reads REMOTE_ADDR only — does not trust X-Forwarded-For headers to
	 * avoid IP spoofing by malicious clients.
	 *
	 * @since  1.0.0
	 * @param  \Intercessor\Http\Request|null $request Optional request instance.
	 * @return string IP address string, or empty string if unavailable.
	 */
	public static function get_remote_ip( ?\Intercessor\Http\Request $request = null ): string {
		$req = $request ?? \Intercessor\Http\Request::capture();
		return $req->get_remote_addr();
	}
}
