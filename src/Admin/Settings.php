<?php
/**
 * Settings read helper.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Thin read-only facade over the Intercessor settings option.
 *
 * All writing, rendering, and schema definition is now handled by the
 * Intercessor\Admin\Settings\ subsystem (Registry, Repository, Sanitizer,
 * Renderer, DisplayPage). This class exists solely to provide the static
 * Settings::get() helper that the rest of the plugin calls to read values
 * without needing to instantiate a Repository directly.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Settings {

	/**
	 * WordPress option key shared with Repository.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	private const OPTION_KEY = 'intercessor_settings';

	/**
	 * Retrieve a single setting value by key.
	 *
	 * Falls back to $default when the key is not present in the stored option.
	 * Reads directly from get_option() so no object instantiation is required
	 * at call sites throughout the plugin.
	 *
	 * @since  1.0.0
	 * @param  string $key     The setting field ID to look up.
	 * @param  mixed  $default Value to return when the key is absent. Default null.
	 * @return mixed           Stored value, or $default when not found.
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$settings = (array) get_option( self::OPTION_KEY, [] );
		return $settings[ $key ] ?? $default;
	}
}
