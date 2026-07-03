<?php
/**
 * Gutenberg block registration loader.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Block;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers all Intercessor Gutenberg blocks with WordPress.
 *
 * Each block has a block.json manifest in assets/js/blocks/{name}/ and a
 * PHP render callback class. The webpack build produces:
 *   - assets/js/blocks/{name}/index.js       — editor script
 *   - assets/js/blocks/{name}/index.asset.php — auto-generated dependency list
 *
 * When block.json is present and the compiled index.js exists, the block is
 * registered from its directory (WordPress reads block.json and enqueues the
 * editorScript automatically). When either file is absent, registerFallback()
 * registers the block programmatically with no editor JS.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Block_Loader {

	/**
	 * Register the 'init' hook that performs block registration.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register all three Intercessor blocks with the WordPress block registry.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register_blocks(): void {
		$blocks = array(
			'prayer-form'    => Prayer_Form_Block::class,
			'prayer-wall'    => Prayer_Wall_Block::class,
			'prayer-history' => Prayer_History_Block::class,
		);

		foreach ( $blocks as $name => $class ) {
			$blockDir  = INTERCESSOR_DIR . 'assets/js/blocks/' . $name;
			$blockJson = $blockDir . '/block.json';
			$indexJs   = $blockDir . '/index.js';

			if ( ! file_exists( $blockJson ) || ! file_exists( $indexJs ) ) {
				// Compiled assets absent — register without editor JS.
				$this->register_fallback( $name, $class );
				continue;
			}

			$result = register_block_type(
				$blockDir,
				array( 'render_callback' => array( new $class(), 'render' ) )
			);

			if ( ! $result || is_wp_error( $result ) ) {
				$this->register_fallback( $name, $class );
			}
		}
	}

	/**
	 * Register a block programmatically when compiled assets are absent.
	 *
	 * @since  1.0.0
	 * @param  string $name  Block slug without the 'intercessor/' prefix.
	 * @param  string $class Fully-qualified block render callback class name.
	 * @return void
	 */
	private function register_fallback( string $name, string $class ): void {
		register_block_type(
			'intercessor/' . $name,
			array(
				'render_callback' => array( new $class(), 'render' ),
				'attributes'      => $class::default_attributes(),
			)
		);
	}
}
