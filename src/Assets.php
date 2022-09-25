<?php
/**
 * Intercessor Assets Object.
 *
 * @package     Intercessor
 * @subpackage  Assets
 * @copyright   Copyright (c) 2022, Victor Aigbeghian
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.1.0
 */

namespace Intercessor;

use function wp_register_script;
use function wp_register_style;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_localize_script;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sets up a class for handling registering and enqueuing scripts and stylesheets.
 *
 * @since 1.1.0
 */
class Assets {
    /**
     * Intercessor version
     *
     * @access public
     * @var int
     */
    public $version;

    /**
     * Footer scripts
     *
     * @access public
     * @var bool
     */
    public $in_footer;

    /**
     * Intercessor css directory url
     *
     * @access public
     * @var string
     */
    public $css_dir;

    /**
     * Intercessor javascript (js) directory url
     *
     * @access public
     * @var string
     */
    public $js_dir;

    /**
     * Use minified libraries if SCRIPT_DEBUG is turned off.
     *
     * @access public
     * @var string
     */
    public $suffix;

    /**
     * Checks if it is RTL or not
     *
     * @access private
     * @var string
     */
    private $is_rtl;

    /**
     * Instance of this class.
     *
     * @since 1.1.0
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Return an instance of this class.
     *
     * @since 1.1.0
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {
        // If the single instance hasn't been set, set it now.
        if ( null === self::$instance ) {
            self::$instance = new self();

            self::$instance->init();
        }

        return self::$instance;
    }

    /**
     * Get things going.
     *
     * @access public
     * @since 1.1.0
     */
    public static function init() {
        // Set up variables.
        self::$version   = INTERCESSOR_VERSION;
        self::$is_rtl    = ( \is_rtl() || isset( $_GET['d'] ) && 'rtl' === $_GET['d'] ) ? '.rtl' : '';
        $scripts_footer  = \intercessor_get_option( 'footer_scripts' );
        self::$in_footer = $scripts_footer ? true : false;
        self::$css_dir   = INTERCESSOR_URL . 'assets/css/';
        self::$js_dir    = INTERCESSOR_URL . 'assets/js/';
        self::$suffix    = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

        // Register scripts and styles.
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_styles'] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_scripts'] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'register_styles'] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'register_scripts'] );

        // Load scripts and styles.
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'frontend_assets' ] );
        
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_styles' ] );
            add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_scripts' ] );
            add_action( 'admin_head', [ __CLASS__, 'admin_head'] );
        }
    }

    /**
     * Register all styles.
     *
     * @since 1.1.1
     */
    public static function register_styles() {
        // Frontend styles.
        wp_register_style( 'intercessor-notices', self::$css_dir . 'intercessor-notices' . self::$suffix . '.css', [], self::$version, 'all' );
        wp_register_style( 'intercessor-form', self::$css_dir . 'intercessor' . self::$suffix . '.css', [], self::$version, 'all' );
        wp_register_style( 'intercessor-prayers', self::$css_dir . 'intercessor-listing' . self::$suffix . '.css', [], self::$version, 'all' );
        wp_register_style( 'intercessor-history', self::$css_dir . 'intercessor-history' . self::$suffix . '.css', [], self::$version, 'all' );

        // Admin styles.
        wp_register_style( 'intercessor-admin', self::$css_dir . 'intercessor-admin' . self::$suffix . '.css', [], self::$version, 'all' );
        wp_register_style( 'intercessor-reports', self::$css_dir . 'intercessor-admin-reports' . self::$suffix . '.css', [], self::$version, 'all' );
    }

    /**
     * Register all scripts.
     *
     * @since 1.1.1
     */
    public static function register_scripts() {
        // Frontend scripts.
        wp_register_script( 'intercessor-js', self::$js_dir . 'intercessor' . self::$suffix . '.js', [ 'jquery' ], self::$version, false );
        wp_register_script( 'intercessor-ajax', self::$js_dir . 'intercessor-ajax' . self::$suffix . '.js', [ 'jquery' ], self::$version, false );
        wp_register_script( 'intercessor-history', self::$js_dir . 'intercessor-history' . self::$suffix . '.js', [ 'jquery' ], self::$version, false );
        wp_register_script( 'intercessor-recaptcha', 'https://www.google.com/recaptcha/api.js', [], self::$version, false );
        
        // Admin scripts.
        wp_register_script( 'intercessor-admin-prayers', self::$js_dir . 'admin/prayers/index' . self::$suffix . '.js', [ 'jquery' ], self::$version, self::$in_footer );
        wp_register_script( 'intercessor-chosen', self::$js_dir . 'vendor/chosen.jquery' . self::$suffix . '.js', [ 'jquery' ], self::$version, self::$in_footer );
        wp_register_script( 'intercessor-requesters', self::$js_dir . 'admin/requesters/requester' . self::$suffix . '.js', [ 'jquery' ], self::$version, self::$in_footer );
        wp_register_script( 'intercessor-export', self::$js_dir . 'admin/export/export' . self::$suffix . '.js', [ 'jquery' ], self::$version, self::$in_footer );
        wp_register_script( 'intercessor-import', self::$js_dir . 'admin/import/import' . self::$suffix . '.js', [ 'jquery' ], self::$version, self::$in_footer );
        wp_register_script( 'intercessor-settings', self::$js_dir . 'admin/settings/index' . self::$suffix . '.js', [ 'jquery' ], self::$version, self::$in_footer );
        wp_register_script( 'intercessor-reports', self::$js_dir . 'admin/reports/index' . self::$suffix . '.js', [ 'jquery' ], self::$version, self::$in_footer ); 
    }

    /**
     * Enqueue frontend styles and scripts.
     *
     * @since 1.1.1
     */
    public static function frontend_assets() {

        // Bail if not on the frontend pages.
        if ( ! self::frontend_pages() ) {
            return;
        }

        // All frontend pages
        wp_enqueue_style( 'intercessor-notices' );
            
        // Prayer request form page.
        if ( self::frontend_pages( 'form' ) ) {
            wp_enqueue_style( 'intercessor-form' );
            wp_enqueue_script( 'intercessor-js' );
        
            // Prayers listing page.
        } elseif ( self::frontend_pages( 'prayers' ) ) {
            self::localize_prayers_script();
            wp_enqueue_style( 'intercessor-prayers' );
            wp_enqueue_script( 'intercessor-ajax' );
            wp_enqueue_script( 'intercessor-ajax' );

            // Prayers history page.
        } elseif ( self::frontend_pages( 'history' ) ) {
            self::localize_history_script();
            wp_enqueue_style( 'intercessor-history' );
            wp_enqueue_script( 'intercessor-history' );
        }

        // Enqueue Google recaptcha if the user is not logged in.
		if ( ! is_user_logged_in() ) {
            wp_enqueue_script( 'intercessor-recaptcha' );
        }
    }

    /**
     * Enqueues admin styles
     *
     * @since 1.1.1
     * @return void
     */
    public static function admin_styles() {
        // Enqueue Intercessor style globally.
        wp_enqueue_style( 'intercessor-admin' );

        // Bail if not on intercessor admin pages.
        if ( ! intercessor_is_admin_page() ) {
            return;
        }

        // Enqueue necessary styles.
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style( 'thickbox' );
        wp_enqueue_style( 'jquery-chosen' );
        wp_enqueue_media();

        // jQuery UI styles are loaded on our admin pages only.
        $ui_style = ( 'classic' === get_user_option( 'admin_color' ) ) ? 'classic' : 'fresh';
        wp_enqueue_style( 'jquery-ui-css', $css_dir . 'jquery-ui-' . $ui_style . '.min.css' );
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook Page hook.
     *
     * @since 1.1.1
     */
    public static function admin_scripts( string $hook ) {
        // Bail if not on admin pages.
        if ( ! apply_filters( 'intercessor_load_admin_scripts', intercessor_is_admin_page(), $hook ) ) {
            return;
        }

        // Enqueue the scripts.
        wp_enqueue_script( 'jquery-chosen' );
        wp_enqueue_script( 'jquery-form' );
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'jquery-ui-dialog' );
        wp_enqueue_script( 'jquery-ui-tooltip' );
        wp_enqueue_script( 'media-upload' );
        wp_enqueue_script( 'thickbox' );
        wp_enqueue_script( 'wp-color-picker' );

        // Localize admin script.
        self::localize_admin_script();

        wp_enqueue_script( 'intercessor-requestrs' );
    }

    /**
     * Localize frontend script
     *
     * @since 1.1.1
     * return array
     */
    public static function localize_history_script() {
        return wp_localize_script(
			'intercessor-history',
			'intercessor_history_vars',
			apply_filters(
				'intercessor_history_params',
				[
					'delete_prayer' => esc_html__( 'Are you sure you want to delete this prayer request? The process is irreversible.', 'intercessor' ),
				]
			)
		);
    }

    /**
     * Localize frontend script
     *
     * @since 1.1.1
     * return array
     */
    public static function localize_prayers_script() {
        return wp_localize_script(
            'intercessor-ajax',
            'intercessor_params',
            apply_filters(
                'intercessor_ajax_params',
                [
                    'ajaxurl'   => intercessor_get_ajax_url(),
                    'praying'   => esc_html__( 'You are praying.', 'intercessor' ),
                    'prayed'    => esc_html__( 'Thanks for praying.', 'intercessor' ),
                    'nopraying' => esc_html__( 'There was an error processing your praying for that request. please refresh your browser and try again.', 'intercessor' ),
                ]
            )
        );
    }

    /**
     * Localize admin script
     *
     * @since 1.1.1
     * return array
     */
    public static function localize_admin_script() {
        $script_vars = [
			'intercessor_version'     => $version,
			'ajaxurl'                 => intercessor_get_ajax_url(),
			'add_new_prayer'          => esc_html__( 'Add New Prayer', 'intercessor' ),
			'delete_prayer'           => esc_html__( 'Are you sure you wish to delete this prayer?', 'intercessor' ),
			'delete_prayer_note'      => esc_html__( 'Are you sure you wish to delete this note?', 'intercessor' ),
			'wpajax'                  => new WP_Ajax_Response(),
			'resend_notification'     => esc_html__( 'Are you sure you wish to resend the prayer notification?', 'intercessor' ),
			'delete_prayer_request'   => sprintf(
				/* translators: %s: prayer request */
				esc_html__( 'Are you sure you wish to delete this %s?', 'intercessor' ),
				'Prayer Request'
			),
			'one_field_min'           => esc_html__( 'You must have at least one field', 'intercessor' ),
			'one_option'              => sprintf(
				/* translators: %s: prayer request */
				esc_html__( 'Choose a %s', 'intercessor' ),
				'Prayer Request'
			),
			'one_or_more_option'      => sprintf(
				/* translators: %s: prayer request */
				esc_html__( 'Choose one or more %s', 'intercessor' ),
				'Prayer Requests'
			),
			'new_media_ui'            => apply_filters( 'intercessor_use_35_media_ui', 1 ),
			'remove_text'             => esc_html__( 'Remove', 'intercessor' ),
			'type_to_search'          => esc_html__( 'Type to search prayer requests', 'intercessor' ),
			'show_advanced_settings'  => esc_html__( 'Show advanced settings', 'intercessor' ),
			'hide_advanced_settings'  => esc_html__( 'Hide advanced settings', 'intercessor' ),
			'chosen'                  => [
				'no_results_msg'  => esc_html__( 'No results match {search_term}', 'intercessor' ),
				'ajax_search_msg' => esc_html__( 'Searching results for match {search_term}', 'intercessor' ),
			],
			'unlock_requester_fields' => esc_html__( 'To edit first name and last name, please go to user profile of the requester.', 'intercessor' ),
			'remove_from_bulk_delete' => esc_html__( 'Remove from Bulk Delete', 'intercessor' ),
			'requesters_bulk_action'  => [
				'no_requester_selected' => esc_html__( 'You must choose at least one or more Requesters to delete.', 'intercessor' ),
				'no_action_selected'    => esc_html__( 'You must select a bulk action to proceed.', 'intercessor' ),
			],
			'prayers_bulk_action'     => [
				'delete'              => [
					'zero'     => esc_html__( 'You must choose at least one or more prayers to delete.', 'intercessor' ),
					'single'   => esc_html__( 'Are you sure you want to permanently delete this prayer?', 'intercessor' ),
					'multiple' => esc_html__( 'Are you sure you want to permanently delete the selected {prayer_count} prayers?', 'intercessor' ),
				],
				'resend_notification' => [
					'zero'     => esc_html__( 'You must choose at least one or more recipients to resend the email notification.', 'intercessor' ),
					'single'   => esc_html__( 'Are you sure you want to resend the email notification to this recipient?', 'intercessor' ),
					'multiple' => esc_html__( 'Are you sure you want to resend the emails notification to {prayer_count} recipients?', 'intercessor' ),
				],
			],
		];

        // Return localized scripts.
        return wp_localize_script( 'intercessor-admin-prayers', 'intercessor_vars', $script_vars );
    }

    /**
     * Get frontend pages.
     *
     * @param string $page Page name.
     *
     * @since 1.1.1
     * @return mixed
     */
    public static function frontend_pages( $page = '' ) {
        if ( 'prayers' === $page ) {
            $value = intercessor_is_listing_page();
        } elseif ( 'history' === $page ) {
            $value = intercessor_is_prayer_history_page();
        } elseif ( 'form' === $page ) {
            $value = intercessor_is_prayer_request_form_page();
        }

        // Return the page.
        return apply_filters( 'intercessor_frontend_page', $value );
    }

    /**
     * Admin head styles.
     *
     * @since 1.1.1
     */
    public static function admin_head() {
        ?>
		<style type="text/css" media="screen">
			@font-face {
				font-family: 'ipr-icomoon';
				src: url('<?php echo INTERCESSOR_URL . 'assets/fonts/ipr-icomoon.ttf?hrm5xq'; ?>');
				src: url('<?php echo INTERCESSOR_URL . 'assets/fonts/ipr-icomoon.ttf?hrm5xq'; ?>') format('truetype'),
				url('<?php echo INTERCESSOR_URL . 'assets/fonts/ipr-icomoon.woff?hrm5xq'; ?>') format('woff'),
				url('<?php echo INTERCESSOR_URL . 'assets/fonts/ipr-icomoon.svg?hrm5xq#ipr-icon'; ?>') format('svg');
				font-weight: normal;
				font-style: normal;
			}

			.ipr-icon-praying:before, #adminmenu div.wp-menu-image.ipr-icon-praying:before {
				font-family: 'ipr-icomoon';
				font-size: 18px;
				width: 18px;
				height: 18px;
				content: "\e901";
				padding-top: 8px;
			}
		</style>
		<?php
    }
}
