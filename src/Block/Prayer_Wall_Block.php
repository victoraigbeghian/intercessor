<?php
/**
 * Prayer Wall block render callback.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Block;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\Admin\Settings;
use Intercessor\Database\Query\Prayer_Request_Query;
use Intercessor\Database\Query\Requester_Query;
use Intercessor\Http\Request;

/**
 * Server-side render callback for the intercessor/prayer-wall Gutenberg block.
 *
 * Renders a paginated, filterable list of prayer requests on the front end.
 * Pagination is driven by the 'ipage' GET parameter to avoid conflicts with
 * WordPress's native paged parameter.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Prayer_Wall_Block {

	/**
	 * Return the default block attribute definitions.
	 *
	 * @since  1.0.0
	 * @return array<string, array<string, mixed>> Map of attribute name to schema definition.
	 */
	public static function default_attributes(): array {
		return array(
			'limit'      => array( 'type' => 'integer', 'default' => 10 ),
			'showDate'   => array( 'type' => 'boolean', 'default' => true ),
			'showAuthor' => array( 'type' => 'boolean', 'default' => true ),
			'status'     => array( 'type' => 'string',  'default' => 'approved' ),
		);
	}

	/**
	 * Render the paginated prayer request list on the front end.
	 *
	 * Block attributes are merged with the corresponding plugin settings so
	 * editor-configured values take precedence over global defaults. The
	 * $paged, $maxPages, $items, $requesterQuery, $showDate, and $showAuthor
	 * variables are extracted into the template scope via the require statement.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed> $attributes Block attributes from the block editor.
	 * @param  string               $content    Inner block content (unused).
	 * @return string                           Rendered HTML string.
	 */
	public function render( array $attributes, string $content ): string {
		$limit      = absint( $attributes['limit']      ?? Settings::get( 'requests_per_page', 10 ) );
		$showDate   = (bool) ( $attributes['showDate']   ?? Settings::get( 'show_date', true ) );
		$showAuthor = (bool) ( $attributes['showAuthor'] ?? Settings::get( 'show_requester_name', true ) );
		$status = sanitize_key( $attributes['status'] ?? 'approved' );

		// Ensure the data-carrier handle exists (may not be registered when the
		// block lives in a reusable block, FSE template, or query loop).
		if ( ! wp_script_is( 'intercessor-public', 'registered' ) ) {
			wp_register_script(
				'intercessor-public',
				'', // no src — data carrier only.
				array(),
				INTERCESSOR_VERSION,
				true
			);
		}

		// Directly enqueue the data-carrier handle so the wp_add_inline_script()
		// call in prayer-wall.php (which injects window.intercessorPray) is
		// guaranteed to be printed. Relying on the dependency chain alone is not
		// sufficient when has_block() returned false during wp_enqueue_scripts.
		wp_enqueue_script( 'intercessor-public' );
		wp_enqueue_script( 'intercessor-prayer-wall' );
		wp_enqueue_style( 'intercessor-public' );

		// 'private' requests are never shown publicly regardless of the
		// block attribute — force back to 'approved' for non-admin callers.
		if ( $status === 'private' && ! current_user_can( 'edit_prayers' ) ) {
			$status = 'approved';
		}

		$paged = max( 1, get_query_var( 'intercessor_page', 1 ) );
		$ipage = Request::capture()->get_int( 'ipage' );
		if ( $ipage > 0 ) {
			$paged = $ipage;
		}

		$query = new Prayer_Request_Query();
		$items = $query->get_items(
			array(
				'status'    => $status,
				'is_public' => 1,
				'number'    => $limit,
				'offset'    => ( $paged - 1 ) * $limit,
				'orderby'   => 'date_created',
				'order'     => 'DESC',
			)
		);

		$total    = $query->count_items( array( 'status' => $status, 'is_public' => 1 ) );
		$maxPages = (int) ceil( $total / $limit );

		$requesterQuery = new Requester_Query();

		ob_start();
		require INTERCESSOR_DIR . 'templates/blocks/prayer-wall.php';
		return ob_get_clean() ?: '';
	}
}
