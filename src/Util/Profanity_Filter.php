<?php
/**
 * Profanity filter utility.
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
 * Checks prayer request text against the administrator-configured word list.
 *
 * The filter uses whole-word, case-insensitive matching via a PCRE word
 * boundary assertion (\b) so that "class" does not trigger on "classified".
 * The word list is read from the 'profanity_words' setting, stored as a
 * comma-separated string, and cached for the lifetime of a single request.
 *
 * The filter intentionally does NOT block submissions. Instead, it forces
 * the request to 'pending' status and populates the moderator_note field
 * with the matched terms, giving administrators full context to decide
 * whether the content should be approved or rejected.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Profanity_Filter {

	/**
	 * Runtime cache of the parsed word list for the current request.
	 *
	 * Populated once by getWords() and reused by subsequent calls.
	 *
	 * @since 1.0.0
	 * @var   string[]|null
	 */
	private static ?array $wordCache = null;

	/**
	 * Return true when the profanity filter is enabled in settings.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return (bool) Settings::get( 'profanity_filter', true );
	}

	/**
	 * Return true when $text passes the filter (contains no prohibited words).
	 *
	 * An empty word list always passes. Returns true when the filter is
	 * disabled — callers should check isEnabled() first or use this directly.
	 *
	 * @since  1.0.0
	 * @param  string $text The text to scan (unsanitized subject or content).
	 * @return bool         True when clean; false when a prohibited word is found.
	 */
	public static function passes( string $text ): bool {
		return empty( self::get_matched_words( $text ) );
	}

	/**
	 * Return the list of prohibited words found in $text.
	 *
	 * Uses whole-word, case-insensitive PCRE matching. Returns an empty array
	 * when the word list is empty or no matches are found.
	 *
	 * @since  1.0.0
	 * @param  string   $text The text to scan.
	 * @return string[]       Unique matched words from the prohibited list, lowercase.
	 */
	public static function get_matched_words( string $text ): array {
		$words = self::get_words();

		if ( empty( $words ) ) {
			return array();
		}

		$matched = array();

		foreach ( $words as $word ) {
			if ( $word === '' ) {
				continue;
			}

			// \b asserts a word boundary so partial matches are not triggered.
			$pattern = '/\b' . preg_quote( $word, '/' ) . '\b/iu';

			if ( preg_match( $pattern, $text ) === 1 ) {
				$matched[] = mb_strtolower( $word );
			}
		}

		return array_unique( $matched );
	}

	/**
	 * Build a human-readable moderator note describing the flagged terms.
	 *
	 * Used to populate the moderator_note field on the prayer request row when
	 * the filter is triggered, so moderators immediately know why the request
	 * was held for review.
	 *
	 * @since  1.0.0
	 * @param  string[] $matchedWords Words returned by getMatchedWords().
	 * @return string                 Formatted note string, or empty string if no matches.
	 */
	public static function build_moderator_note( array $matchedWords ): string {
		if ( empty( $matchedWords ) ) {
			return '';
		}

		// translators: %s: comma-separated list of matched profanity words
		return sprintf(
			/* translators: %s: comma-separated list of matched prohibited words */
			__( '[Profanity filter] Flagged for review. Matched terms: %s', 'intercessor' ),
			implode( ', ', array_map( 'esc_html', $matchedWords ) )
		);
	}

	/**
	 * Return the parsed, trimmed, non-empty word list from settings.
	 *
	 * Parses the 'profanity_words' setting on first call and caches the result
	 * for the lifetime of the request. The setting stores a comma-separated
	 * string; each token is trimmed and lowercased before caching.
	 *
	 * @since  1.0.0
	 * @return string[] Cleaned list of prohibited words.
	 */
	private static function get_words(): array {
		if ( self::$wordCache !== null ) {
			return self::$wordCache;
		}

		$raw   = (string) Settings::get( 'profanity_words', '' );
		$words = array_filter(
			array_map(
				static fn( string $w ) => mb_strtolower( trim( $w ) ),
				explode( ',', $raw )
			)
		);

		self::$wordCache = array_values( $words );

		return self::$wordCache;
	}

	/**
	 * Clear the word list cache.
	 *
	 * Useful in unit tests or when settings have been updated mid-request.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function clear_cache(): void {
		self::$wordCache = null;
	}
}
