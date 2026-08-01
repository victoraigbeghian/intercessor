<?php
/**
 * Tools admin page controller.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Tools;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers the Tools admin submenu page and dispatches CSV export and import requests.
 *
 * Wired into AdminLoader so the page appears as Intercessor → Tools. Each
 * registered exporter gets its own admin_post_intercessor_export_{slug} handler
 * and each importer gets admin_post_intercessor_import_{slug}, all registered
 * during Admin_Loader::register() so POST requests fire before any output.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Tools_Admin_Page {

	/**
	 * Map of export slug to concrete Abstract_Exporter subclass name.
	 *
	 * Add new exporters here to make them available on the Tools page without
	 * touching any other class.
	 *
	 * @since 1.0.0
	 * @var   array<string, class-string<Abstract_Exporter>>
	 */
	private const EXPORTERS = array(
		'settings'        => Settings_Exporter::class,
		'prayer_requests' => Prayer_Requests_Exporter::class,
		'requesters'      => Requesters_Exporter::class,
		'prayed_counts'   => Prayed_Counts_Exporter::class,
	);

	/**
	 * Map of import slug to concrete Abstract_Importer subclass name.
	 *
	 * Add new importers here to make them available on the Tools page without
	 * touching any other class.
	 *
	 * @since 1.0.2
	 * @var   array<string, class-string<Abstract_Importer>>
	 */
	private const IMPORTERS = array(
		'prayer_requests' => Prayer_Requests_Importer::class,
		'settings'        => Settings_Importer::class,
	);

	/**
	 * Register admin_post handlers for every registered exporter and importer.
	 *
	 * Must be called early — from Admin_Loader::register() — so the handlers
	 * exist before WordPress processes the current request. Export actions follow
	 * the pattern intercessor_export_{slug}; import actions follow
	 * intercessor_import_{slug}.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		foreach ( self::EXPORTERS as $slug => $class ) {
			add_action(
				"admin_post_intercessor_export_{$slug}",
				static function () use ( $class ): void {
					( new $class() )->dispatch();
				}
			);
		}

		foreach ( self::IMPORTERS as $slug => $class ) {
			add_action(
				"admin_post_intercessor_import_{$slug}",
				static function () use ( $class ): void {
					( new $class() )->dispatch();
				}
			);
		}
	}

	/**
	 * Render the Tools admin page — Export or Import tab.
	 *
	 * The active tab is driven by the 'tab' GET parameter. Defaults to 'export'.
	 * Both tabs share the same page header and nav-tab-wrapper.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_prayer_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'intercessor' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'export';
		$active_tab = in_array( $active_tab, array( 'export', 'import' ), true ) ? $active_tab : 'export';

		require INTERCESSOR_DIR . 'templates/admin/tools/exports.php';
	}

	/**
	 * Return import descriptor arrays for the import tab template.
	 *
	 * @since  1.0.2
	 * @return array<int, array{slug:string, label:string, description:string, columns:string[], importer:Abstract_Importer}>
	 */
	public static function get_import_descriptors(): array {
		return array(
			array(
				'slug'        => 'prayer_requests',
				'label'       => esc_html__( 'Prayer Requests', 'intercessor' ),
				'description' => esc_html__( 'Import prayer requests from a CSV file. Each row creates one request and finds or creates the requester by email.', 'intercessor' ),
				'columns'     => array( 'Subject', 'Status', 'Requester Email', 'Prayer Content (opt)', 'Requester Name (opt)', 'Anonymous (opt)', 'Public (opt)', 'Moderator Note (opt)' ),
				'importer'    => new Prayer_Requests_Importer(),
			),
			array(
				'slug'        => 'settings',
				'label'       => esc_html__( 'Plugin Settings', 'intercessor' ),
				'description' => esc_html__( 'Restore Intercessor settings from a previously exported CSV. Only known setting keys are applied; unknown keys are skipped.', 'intercessor' ),
				'columns'     => array( 'Setting Key', 'Value', 'Section (ignored)' ),
				'importer'    => new Settings_Importer(),
			),
		);
	}

	/**
	 * Return a list of export descriptor arrays consumed by the exports template.
	 *
	 * Each descriptor provides the slug, human-readable label, description,
	 * and an instantiated exporter object whose nonceField() method is called
	 * in the template to embed the form security token.
	 *
	 * @since  1.0.0
	 * @return array<int, array{slug: string, label: string, description: string, exporter: AbstractExporter}>
	 *     Indexed list of export card data. Each element contains:
	 *     - slug        (string)           Export action slug.
	 *     - label       (string)           Localised card heading.
	 *     - description (string)           Localised card body text.
	 *     - exporter    (AbstractExporter) Instantiated exporter for nonce generation.
	 */
	public static function get_export_descriptors(): array {
		return array(
			array(
				'slug'        => 'settings',
				'label'       => esc_html__( 'Plugin Settings', 'intercessor' ),
				'description' => esc_html__( 'Export all Intercessor settings as a CSV audit snapshot.', 'intercessor' ),
				'exporter'    => new Settings_Exporter(),
			),
			array(
				'slug'        => 'prayer_requests',
				'label'       => esc_html__( 'Prayer Requests', 'intercessor' ),
				'description' => esc_html__( 'Export all prayer requests, including status, requester info, and moderator notes.', 'intercessor' ),
				'exporter'    => new Prayer_Requests_Exporter(),
			),
			array(
				'slug'        => 'requesters',
				'label'       => esc_html__( 'Requesters', 'intercessor' ),
				'description' => esc_html__( 'Export the full requester database with linked WordPress account details.', 'intercessor' ),
				'exporter'    => new Requesters_Exporter(),
			),
			array(
				'slug'        => 'prayed_counts',
				'label'       => esc_html__( 'Prayed Counts', 'intercessor' ),
				'description' => esc_html__( 'Export aggregated or detailed "prayed for" interaction data.', 'intercessor' ),
				'exporter'    => new Prayed_Counts_Exporter(),
			),
		);
	}
}
