<?php
/**
 * Intercessor Main class.
 *
 * The file that defines the core plugin class
 *
 * @package    Intercessor
 * @subpackage Classes/Loader
 * @copyright  Copyright (c) 2020, Victor Aigbeghian
 * @license    http://opensource.org/licenses/GPL-2.0.php GNU Public License
 * @since      1.0.0
 */

namespace Intercessor;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Hook the WordPress plugin into the appropriate WordPress actions and filters.
 *
 * @since 1.0.0
 */
class Loader {
    /**
     * Plugin version
     *
	 * @access public
     * @since  1.0.0
     * @var    string
     */
    public $version = '1.1.0';

  /**
     * This plugin object
     *
	 * @access public
     * @since  1.0.0
     * @var    object
     */
    public $loader;

	/**
	 * The settings options of this plugin.
	 *
	 * @access public
	 * @since  1.0.0
	 * @var    object|Admin\Settings
	 */
    public $settings;

    /**
     * Session
     *
	 * @access public
     * @since  1.0.0
     * @var    object|Session
     */
    public $session;

    /**
     * HTML template helper class.
     *
	 * @access public
     * @since  1.0.0
     * @var    object|Html
     */
    public $html;
    
	/**
	 * The Intercessor Roles Object.
	 *
	 * @access public
	 * @since  0.9.5
	 * @var    object|Roles
	 */
	public $roles;

	/**
	 * The Intercessor Database tables.
	 *
	 * @access public
	 * @since  0.9.5
	 * @var    array Array of database tables.
	 */
    public $tables = [];

	/**
	 * The Intercessor Reports.
	 *
	 * @access public
	 * @since  1.0.0
	 * @var    object|Reports
	 */
	public $reports;

	/**
	 * The Intercessor Emails.
	 *
	 * @access public
	 * @since  0.9.5
	 * @var    object|Emails
	 */
	public $emails;

	/**
	 * The Intercessor Cron class.
	 *
	 * @access public
	 * @since  0.9.5
	 * @var    object|Cron
	 */
    public $cron;

	/**
	 * The Intercessor Request Forms.
	 *
	 * @since    0.9.5
	 * @access   public
	 * @var      object|Form
	 */
	public $forms;

	/**
	 * The Intercessor Prayer History class.
	 *
	 * @since  0.9.5
	 * @access public
	 * @var    object|Prayer_History
	 */
	public $history;

    /**
     * The path to this plugin main file
     *
     * @since 1.0.0
     * @var   string
     */
    private $plugin_file = '';

    /**
     * Singleton instance
     *
     * @since 1.0.0
     * @var Loader
     */

    private static $instance;
	
	/**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone() {
        if ( WP_DEBUG ) {
            trigger_error( __( 'Cloning is forbidden.', 'intercessor' ), E_USER_ERROR );
        }
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup(){
        if ( WP_DEBUG ) {
            trigger_error( __( 'Unserializing instances of this class is forbidden.', 'intercessor' ), E_USER_ERROR );
        }
    }

    /**
     * Constructor with dependency injection
     *
     * @param mixed $plugin_file This plugin file.
     *
     * @access public
     * @since  1.1.0
     *
     * @return void
     */
    public function __construct( $plugin_file ) {
        $this->plugin_file = $plugin_file;
    }

    /**
     * The singleton method.
     *
     * @param object $plugin_file This plugin file.
     * 
     * @since 1.0.0
     *
     * @return Loader
     */
    public static function setup_instance( $plugin_file ) {
        if ( self::already_instantiated() ) {
            return self::$instance;
        }

        self::$instance = new Loader( $plugin_file );

        self::$instance->define_constants();
        self::$instance->setup_options();
        self::$instance->setup_tables();
        self::$instance->init();

        // Activate Admin.
        if ( is_admin() ) {
            self::$instance->admin();
        }
        
		// Return the instance.
	    return self::$instance;
    }

    /**
     * Checks if main loader class has been instantiated.
     *
     * @since 1.0.0
     * @return bool
     */
    private static function already_instantiated() : bool {
        // Return true if instance is the correct class.
        if ( ! empty( self::$instance ) && ( self::$instance instanceof Loader ) ) {
            return true;
        }

        // Return false if not correctly instantiated.
        return false;
    }

    /**
     * Setup and instantiate database tables.
     *
     * @since 1.1.0
     *
     * @access public
     */
    public function setup_tables() : array {
        $this->tables = [
            'requesters'     => new Database\Tables\Requesters(),
            'requester_meta' => new Database\Tables\Requester_Meta(),
            'prayers'        => new Database\Tables\Prayers(),
            'prayer_meta'    => new Database\Tables\Prayer_Meta(),
            'notes'          => new Database\Tables\Notes(),
            'prayed_counts'  => new Database\Tables\Prayed_Counts(),
        ];

        return $this->tables;
    }

    /**
     * Hook into actions and filters for administrative interface page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function admin() {
        // Block the admin toolbar if the current user is not allowed.
        if ( ! current_user_can( 'edit_posts' ) ) {
            add_filter( 'show_admin_bar', '__return_false' );
        }

        // Setup administration functionalities.
        if ( is_admin() || ( defined( 'DOING_AJAX' ) && ! DOING_AJAX ) ) {
            Admin\Admin::instance();
            new Admin\Notices();
        }
    }

    /**
     * Register Custom Post type.
     *
     * @since 1.0.0
     */
    public function init() {
        $this->roles   = new Roles();
		$this->session = new Session();
		$this->html    = new Html();
		$this->emails  = new Emails();
        $this->history = new Prayer_History();
        $this->forms   = Form::get_instance();
        $this->cron    = new Cron();
        $this->reports = new Reports();
        new Shortcodes();
       // Assets::get_instance();

	    // CLI.
	    if ( defined( 'WP_CLI' ) && WP_CLI ) {
		    new CLI();
	    }
    }

    /**
     * Fires once activated plugin have loaded.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function setup_options() {
        global $intercessor_options;

		$this->settings      = Admin\Settings::instance();
        $intercessor_options = $this->settings->get_settings();
    }

    /**
     * Define necessary constants.
     *
     * @access public
     * @since  1.0.0
     *
     * @return void
     */
    public function define_constants() {
       // Plugin version.
		if ( ! defined( 'INTERCESSOR_VERSION' ) ) {
			define( 'INTERCESSOR_VERSION', $this->version );
		}

		// Plugin Folder Path.
		if ( ! defined( 'INTERCESSOR_DIR' ) ) {
            define( 'INTERCESSOR_DIR', plugin_dir_path( $this->plugin_file ) );
		}

		// Plugin Folder URL.
		if ( ! defined( 'INTERCESSOR_URL' ) ) {
			define( 'INTERCESSOR_URL', plugin_dir_url( $this->plugin_file ) );
		}

		// Plugin Basename.
		if ( ! defined( 'INTERCESSOR_BASENAME' ) ) {
			define( 'INTERCESSOR_BASENAME', plugin_basename( $this->plugin_file ) );
		}

    }
}
