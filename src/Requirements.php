<?php
/**
 * Plugin requirements checker.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Checks server and WordPress requirements before the plugin boots.
 *
 * BerlinDB is bundled in src/includes/berlindb/ and loaded at startup. The
 * Intercessor Settings subsystem (Registry, Repository, Sanitizer, Renderer,
 * DisplayPage) is part of the plugin source and loaded via the PSR-4
 * autoloader, so no separate check is required for settings.
 *
 * Instantiated in the bootstrap file before any other class is loaded.
 * If met() returns false the plugin halts and displays an admin notice
 * listing all failures.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Requirements {

	/**
	 * Collected list of human-readable failure messages, keyed by requirement.
	 *
	 * @since 1.0.0
	 * @var   array<string, string>
	 */
	private array $failures = array();

	/**
	 * Run all checks on instantiation.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->check();
	}

	/**
	 * Execute every individual requirement check in dependency order.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function check(): void {
		$this->check_php();
		$this->check_word_press();
		$this->check_berlin_db();
	}

	/**
	 * Verify the server's PHP version meets the minimum defined by INTERCESSOR_MIN_PHP.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function check_php(): void {
		if ( version_compare( PHP_VERSION, INTERCESSOR_MIN_PHP, '<' ) ) {
			// translators: %s: minimum required PHP version, %s: current PHP version
			$this->failures['php'] = sprintf(
				/* translators: 1: required PHP version 2: current PHP version */
				__( 'PHP %1$s or higher is required. You are running PHP %2$s.', 'intercessor' ),
				INTERCESSOR_MIN_PHP,
				PHP_VERSION
			);
		}
	}

	/**
	 * Verify the WordPress installation meets the minimum version defined by INTERCESSOR_MIN_WP.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function check_word_press(): void {
		global $wp_version;

		if ( version_compare( $wp_version, INTERCESSOR_MIN_WP, '<' ) ) {
			// translators: %s: minimum required WordPress version, %s: current WordPress version
			$this->failures['wordpress'] = sprintf(
				/* translators: 1: required WP version 2: current WP version */
				__( 'WordPress %1$s or higher is required. You are running WordPress %2$s.', 'intercessor' ),
				INTERCESSOR_MIN_WP,
				$wp_version
			);
		}
	}

	/**
	 * Verify the bundled BerlinDB library classes are available.
	 *
	 * src/includes/berlindb/load.php is included before this check runs, so a
	 * missing class indicates the bundled files are corrupt or absent.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function check_berlin_db(): void {
		if ( ! class_exists( 'Intercessor\\BerlinDB\\Table', false ) ) {
			$this->failures['berlindb'] = __(
				'Intercessor: Bundled BerlinDB library could not be loaded. Please re-install the plugin.',
				'intercessor'
			);
		}
	}

	/**
	 * Return true when every requirement check passed with no failures.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function met(): bool {
		return empty( $this->failures );
	}

	/**
	 * Render a wp-admin error notice listing all unmet requirements.
	 *
	 * Hooked to 'admin_notices' when met() returns false.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function notice(): void {
		$message  = '<strong>' . esc_html__( 'Intercessor cannot be activated:', 'intercessor' ) . '</strong>';
		$message .= '<ul>';

		foreach ( $this->failures as $failure ) {
			$message .= '<li>' . esc_html( $failure ) . '</li>';
		}

		$message .= '</ul>';

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			wp_kses( $message, array( 'strong' => array(), 'ul' => array(), 'li' => array() ) )
		);
	}
}
