<?php
/**
 * Intercessor Admin Class
 *
 * @package     Intercessor
 * @subpackage  Admin
 * @copyright   Copyright (c) 2020, Victor Aigbeghian
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       1.0.0
 */

namespace Intercessor\Admin;

use Intercessor\Admin\Settings;
use function add_submenu_page;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Intercessor Admin Class
 *
 * @since 1.0.0
 */
class Admin {
    /**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

    /**
     * Constructor
     *
     * @since  1.0.0
     * @access private
     */
    private function __construct() {
        $plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( dirname( __FILE__ ) ) ) ) . 'intercessor.php' );
        add_filter( 'plugin_action_links_' . $plugin_basename, [ $this, 'add_action_links' ] );

		add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 10, 4 );

        add_action( 'admin_menu', [ $this, 'menu' ] );
        add_action( 'admin_menu', [ $this, 'upgrades_menu'] );
		
		$this->maybe_load();
    }

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

    /**
	 * Add settings pages
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function menu() {
		global  $intercessor_prayers_page, $intercessor_settings_page, $intercessor_requesters_page,
			$intercessor_tools_page, $intercessor_reports_page;

        // Bail if user is unauthorized.
        if ( ! \current_user_can( 'edit_posts' ) || \wp_doing_ajax() ) {
            \wp_safe_redirect( esc_url( get_home_url() ) );
            exit;
        }

		// Setup prayers page menu.
        $intercessor_prayers_page = \add_menu_page(
            esc_html__( 'Prayer Requests', 'intercessor' ),
			esc_html__( 'Prayers', 'intercessor' ),
            'edit_prayers',
            'intercessor-prayers',
            'intercessor_prayers_page',
			'',
			25
		);

		// Setup requester page sub menu.
		$intercessor_requesters_page = add_submenu_page(
			'intercessor-prayers',
			esc_html__( 'Intercessor Requesters', 'intercessor' ),
            esc_html__( 'Requesters', 'intercessor' ),
            'view_prayer_reports',
            'intercessor-requesters',
            'intercessor_requesters_page'
		);

		// Setup reports page sub menu.
		$intercessor_reports_page = add_submenu_page(
			'intercessor-prayers',
			esc_html__( 'Intercessor Reports', 'intercessor' ),
			esc_html__( 'Reports', 'intercessor' ),
			'manage_options',
			'intercessor-reports',
			'intercessor_reports_page'
		);

		// Setup tools page sub menu.
		$intercessor_tools_page = add_submenu_page(
			'intercessor-prayers',
			esc_html__( 'Intercessor Tools', 'intercessor' ),
            esc_html__( 'Tools', 'intercessor' ),
            'manage_prayer_settings',
            'intercessor-tools',
            'intercessor_tools_page'
        );

        // Setup settings page sub menu.
        $settings_page = Settings::instance();
		$intercessor_settings_page = add_submenu_page(
			'intercessor-prayers',
			esc_html__( 'Intercessor Settings', 'intercessor' ),
            esc_html__( 'Settings', 'intercessor' ),
            'manage_options',
            'intercessor-settings',
            [ $settings_page, 'render' ]
        );

		add_action( 'load-' . $intercessor_prayers_page, 'intercessor_add_prayers_screen_options' );
		add_action( 'load-' . $intercessor_prayers_page, 'intercessor_prayers_contextual_help' );
		add_action( 'load-' . $intercessor_settings_page, [ $settings_page, 'sidebar' ] );
    }

    /**
	 * Add settings action link to the plugins page.
	 *
	 * @param array $links Links.
	 *
	 * @return array
	 * @since  0.9.5
	 */
    public function add_action_links( array $links ): array {
        return array_merge(
            $links,
            [
                'settings' => '<a href="' . admin_url( 'admin.php?page=intercessor-settings' ) . '">' . esc_html__( 'Settings', 'intercessor' ) . '</a>',
            ]
        );
    }

	/**
	 * Row meta.
	 *
	 * @param array  $plugin_meta Plugin meta values.
	 * @param string $plugin_file Plugin file.
	 * @param array  $plugin_data Array of plugin data.
	 * @param string $status      Status, default 'all'.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function row_meta( array $plugin_meta, string $plugin_file, array $plugin_data, string $status ): array {

		if ( INTERCESSOR_BASENAME !== $plugin_file ) {
			return $plugin_meta;
		}

		$new_meta = [
			sprintf( '<a href="%s">%s</a>', esc_url( 'https://github.com/victoraigbeghian/intercessor/wiki' ), esc_html__( 'Documentation', 'intercessor' ) ),
		];

		return array_merge( $plugin_meta, $new_meta );
    }

	/**
	 * Upgrades menu
	 *
	 * @since 1.0.0
	 * @access public
	 * 
	 * @return mixed
	 */
    public function upgrades_menu() {
        global $intercessor_upgrades_page;

        // Add upgrades sub menu.
        $intercessor_upgrades_page = add_submenu_page(
            'admin.php',
            esc_html__( 'Intercessor Upgrades', 'intercessor' ),
            esc_html__( 'Intercessor Upgrades', 'intercessor' ),
            'manage_prayer_settings',
            'intercessor-upgrades',
            'intercessor_upgrades_screen'
        );
    }

	/**
	 * Maybe load PDF files.
	 *
	 * @access private
	 * @since 1.0.0
	 * @return void
	 */
	private function maybe_load() {
		// Maybe load pdf generation files.
		if ( $this->generate_pdf() ) {
			require_once INTERCESSOR_DIR . '/src/Admin/Tools/Export/pdf-prayers.php';
		}
	}

	/**
	 * Generate PDF
	 *
	 * @access private
	 * @since 1.0.0
	 * @return bool
	 */
	private function generate_pdf() {
		return isset( $_GET['intercessor-action'] ) && 'generate_pdf' === \intercessor_clean( $_GET['intercessor-action'] );
	}
}
