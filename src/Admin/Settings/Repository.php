<?php
/**
 * Settings persistence layer.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Admin\Settings;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes plugin settings via the WordPress Options API.
 *
 * Pure storage class: no domain logic, no WordPress hook registration.
 * All methods operate on a single serialised array stored under $optionKey.
 *
 * @since   1.0.0
 * @package Intercessor
 */
class Repository {

	/**
	 * WordPress option name used to store all plugin settings.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	private string $optionKey;

	/**
	 * Construct the repository with the WordPress option key to use.
	 *
	 * @since 1.0.0
	 * @param string $optionKey Option name in wp_options. Default 'intercessor_settings'.
	 */
	public function __construct( string $optionKey = 'intercessor_settings' ) {
		$this->optionKey = $optionKey;
	}

	/**
	 * Retrieve all stored settings as an associative array.
	 *
	 * @since  1.0.0
	 * @return array<string, mixed>
	 */
	public function all(): array {
		return (array) get_option( $this->optionKey, array() );
	}

	/**
	 * Retrieve a single setting value by key.
	 *
	 * @since  1.0.0
	 * @param  string $key     Setting field ID.
	 * @param  mixed  $default Fallback value when the key is absent. Default null.
	 * @return mixed           Stored value, or $default when not found.
	 */
	public function get( string $key, mixed $default = null ): mixed {
		return $this->all()[ $key ] ?? $default;
	}

	/**
	 * Update a single setting value.
	 *
	 * Passing null or empty string removes the key from storage. All other
	 * values are written and the updated array is persisted via update_option().
	 *
	 * @since  1.0.0
	 * @param  string $key   Setting field ID.
	 * @param  mixed  $value New value. Pass null or '' to remove the key.
	 * @return bool          True on success, false on failure.
	 */
	public function update( string $key, mixed $value ): bool {
		$options = $this->all();

		if ( null === $value || '' === $value ) {
			unset( $options[ $key ] );
		} else {
			$options[ $key ] = $value;
		}

		return update_option( $this->optionKey, $options );
	}

	/**
	 * Replace the entire settings array in one operation.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed> $data Complete replacement data.
	 * @return bool                       True on success, false on failure.
	 */
	public function replace( array $data ): bool {
		return update_option( $this->optionKey, $data );
	}

	/**
	 * Delete a single setting key from storage.
	 *
	 * @since  1.0.0
	 * @param  string $key Setting field ID to remove.
	 * @return bool        True on success, false on failure.
	 */
	public function delete( string $key ): bool {
		$options = $this->all();
		unset( $options[ $key ] );

		return update_option( $this->optionKey, $options );
	}
}
