<?php
/**
 * Base Core Object.
 *
 * @package     Intercessor
 * @subpackage  Core
 * @copyright   Copyright (c) 2020, Victor Aigbeghian
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

namespace Intercessor;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup a base object to be extended by core objects.
 *
 * @since 1.0.0
 * @abstract
 */
abstract class Base {

	/**
	 * Object constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $args Object to populate members for.
	 */
	public function __construct( $args = null ) {
		$this->set_vars( $args );
	}

	/**
	 * Magic isset'ter for immutability.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key to use.
	 *
	 * @return mixed
	 */
	public function __isset( $key = '' ) {

		// No more uppercase ID properties ever.
		if ( 'ID' === $key ) {
			$key = 'id';
		}

		// Class method to try and call.
		$method = "get_{$key}";

		// Return property if exists.
		if ( method_exists( $this, $method ) ) {
			return true;

			// Return get method results if exists.
		} elseif ( property_exists( $this, $key ) ) {
			return true;
		}

		// Return false if not exists.
		return false;
	}

	/**
	 * Magic getter for immutability.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key to use.
	 * @return mixed
	 */
	public function __get( $key = '' ) {

		// No more uppercase ID properties ever.
		if ( 'ID' === $key ) {
			$key = 'id';
		}

		// Class method to try and call.
		$method = "get_{$key}";

		// Return property if exists.
		if ( method_exists( $this, $method ) ) {
			return call_user_func( [ $this, $method ] );

			// Return get method results if exists.
		} elseif ( property_exists( $this, $key ) ) {
			return $this->{$key};
		}

		// Return null if not exists.
		return null;
	}

	/**
	 * Converts the given object to an array.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array version of the given object.
	 */
	public function to_array() {
		return get_object_vars( $this );
	}

	/**
	 * Set class variables from arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Array of arguments.
	 */
	protected function set_vars( $args = [] ) {

		// Bail if empty or not an array.
		if ( empty( $args ) ) {
			return;
		}

		// Cast to an array.
		if ( ! is_array( $args ) ) {
			$args = (array) $args;
		}

		// Set all properties.
		foreach ( $args as $key => $value ) {
			$this->{$key} = $value;
		}
	}
}
