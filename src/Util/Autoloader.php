<?php
/**
 * PSR-4 autoloader for the Intercessor plugin.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Util;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Multi-namespace PSR-4 autoloader for the Intercessor plugin.
 *
 * Handles the Intercessor\ namespace mapped to src/ by default. Additional
 * mappings can be registered at runtime via addNamespace(). BerlinDB and
 * BerlinDB is loaded via an explicit require_once in src/includes/berlindb/load.php
 * before this autoloader is registered.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Autoloader {

	/**
	 * Map of namespace prefix to absolute base directory.
	 *
	 * Keys include a trailing backslash; values include a trailing
	 * directory separator.
	 *
	 * @since 1.0.0
	 * @var   array<string, string>
	 */
	private array $namespaces = array();

	/**
	 * Register the Intercessor namespace mapping and attach the autoloader.
	 *
	 * Maps Intercessor\ to INTERCESSOR_DIR . 'src/', then registers the
	 * load() method with spl_autoload_register().
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		// Core plugin namespace.
		$this->add_namespace( 'Intercessor\\', INTERCESSOR_DIR . 'src/' );

		spl_autoload_register( array( $this, 'load' ) );
	}

	/**
	 * Add a PSR-4 namespace-to-directory mapping.
	 *
	 * Both the prefix and baseDir are normalised (trailing backslash and
	 * trailing directory separator respectively) before being stored.
	 *
	 * @since  1.0.0
	 * @param  string $prefix  Namespace prefix, with or without a trailing backslash.
	 * @param  string $baseDir Absolute filesystem path to the namespace root directory.
	 * @return void
	 */
	public function add_namespace( string $prefix, string $baseDir ): void {
		$prefix  = rtrim( $prefix, '\\' ) . '\\';
		$baseDir = rtrim( $baseDir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;

		$this->namespaces[ $prefix ] = $baseDir;
	}

	/**
	 * Attempt to load a class file from the registered namespace map.
	 *
	 * Iterates over all registered mappings and, when a prefix match is
	 * found, constructs the expected file path. Returns true if the file
	 * exists and is loaded; false if no mapping could satisfy the request.
	 *
	 * @since  1.0.0
	 * @param  string $class Fully-qualified class name requested by PHP.
	 * @return bool          True when the class file was found and loaded.
	 */
	public function load( string $class ): bool {
		foreach ( $this->namespaces as $prefix => $baseDir ) {
			$prefixLen = strlen( $prefix );

			if ( strncmp( $prefix, $class, $prefixLen ) !== 0 ) {
				continue;
			}

			$relativeClass = substr( $class, $prefixLen );
			$file          = $baseDir . str_replace( '\\', DIRECTORY_SEPARATOR, $relativeClass ) . '.php';

			if ( file_exists( $file ) ) {
				require $file;
				return true;
			}
		}

		return false;
	}
}
