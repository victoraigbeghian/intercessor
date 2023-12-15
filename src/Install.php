<?php
/**
 * Intercessor Install class.
 *
 * The file that defines the core plugin installation functions and actions.
 *
 * @package    Intercessor
 * @subpackage Classes/Loader
 * @author     Victor Aigbeghian <info@intercessorwp.com>
 * @copyright  Copyright (c) 2020, Victor Aigbeghian
 * @license    http://opensource.org/licenses/GPL-2.0.php GNU Public License
 * @link       https://github.com/victoraigbeghian/intercessor
 * @since      1.0.0
 */

namespace Intercessor;

use function add_action;
use function restore_current_blog;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Installation class
 *
 * @since 1.0.0
 */
class Install {
 	
	/**
     * Plugin version
     *
	 * @access public
     * @since  1.0.0
     * @var    string
     */
    public $db_version = '1.1.0'; // TODO

	/**
	 * Constructor
	 *
	 * @param mixed $db_version The database version.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return void
	 */
    public function __construct( $db_version ) {
        $this->db_version = $db_version;

		// Initialization functions and actions.
		if ( version_compare( \get_bloginfo( 'version' ), '5.1', '>=' ) ) {
			add_action( 'wp_initialize_site', [ $this, 'new_blog' ] );
		} else {
			add_action( 'wpmu_new_blog', [ $this, 'new_blog' ] );
		}

		add_action( 'admin_init', [ $this, 'after_install' ] );
		add_action( 'admin_init', [ $this, 'network_roles' ] );
    }

	/**
	 * Activate the plugin to setup custom post types, etc.
	 *
	 * @param bool $network_wide Whether to activate network wide on multisite.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function activate( bool $network_wide ) {

		// On multi-site(s).
		if ( \is_multisite() && ! empty( $network_wide ) ) {
			$this->multisite_activation();

			// On single site.
		} else {
			$this->single_activation();
		}
	}

	/**
	 * Run installation on multi-site.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function multisite_activation() {
		global $wpdb;

		// Get count of available sites.
		$network_id = \get_current_network_id();
		$query      = $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->blogs} WHERE site_id = %d", $network_id );
		$count      = $wpdb->get_var( $query );

		// Bail, if no sites found.
		if ( empty( $count ) || is_wp_error( $count ) ) {
			return;
		}

		// Build the steps.
		$per_step    = 100;
		$total_steps = ceil( $count / $per_step );
		$step        = 1;
		$offset      = 0;

		// Go through all sites in this network in groups of 100.
		do {

			// Get the next batch of site IDs.
			$query    = $wpdb->prepare( "SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = %d LIMIT %d, %d", $network_id, $offset, $per_step );
			$site_ids = $wpdb->get_col( $query );

			// Proceed if site IDs exist.
			if ( ! empty( $site_ids ) ) {
				foreach ( $site_ids as $site_id ) {
					$this->single_activation( $site_id );
				}
			}

			// Bump the limit for the next iteration.
			$offset = ( $step * $per_step ) - 1;

			// Bump the step.
			++$step;

			// Bail when steps are greater than or equal to total steps.
		} while ( $total_steps > $step );
	}

	/**
	 * Setup single site activation.
	 *
	 * @param bool $site_id The ID of the site.
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function single_activation( $site_id = false ) {
		// Not changed.
		$changed = false;

		// Maybe switch to a site.
		if ( ! empty( $site_id ) ) {
			\switch_to_blog( $site_id );
			$changed = true;
		}

		// Retrieve current database version.
		$current_version = \intercessor_get_db_version();

		// Install default pages.
		$this->setup_pages();

		// Install default options.
		$this->setup_default_options();

		// Maybe save the previous version, only if different than current.
		if ( ! empty( $current_version ) && ( \intercessor_format_db_version( INTERCESSOR_VERSION ) !== $current_version ) ) {
			\update_option( 'intercessor_version_upgraded_from', $current_version );
		}

		// Enable PHP session support if available.
		$session = intercessor()->session;
		$session->use_php_sessions();

		// Update database version.
		\intercessor_update_db_version();

		// Install Intercessor roles.
		$roles = new Roles();
		$roles->add_roles();
		$roles->add_caps();

		// Bail if activating from network, or bulk.
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Clear the permalinks.
		\flush_rewrite_rules( false );

		// Maybe switch back.
		if ( true === $changed ) {
			restore_current_blog();
		}
	}

	/**
	 * Install on new blog created in a multi-site.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WP_Site $blog_id WP_Site object.
	 *
	 * @return void
	 */
	public function new_blog( $blog_id ) {
		// Bail if plugin is not network activated.
		if ( ! \is_plugin_active_for_network( INTERCESSOR_BASENAME ) ) {
			return;
		}

		// Return the blog id.
		if ( ! is_int( $blog_id ) ) {
			$blog_id = $blog_id->id;
		}

		// Activate plugin on new blog.
		\switch_to_blog( $blog_id );
		$this->activate( $blog_id );
		restore_current_blog();
	}

	/**
	 * Runs after installation
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function after_install() {
		// Bail if it is not an admin.
		if ( ! \is_admin() ) {
			return;
		}

		$intercessor_options = \get_transient( '_intercessor_installed' );

		/**
		 * Fires after installation
		 *
		 * @param array $intercessor_options Array of options.
		 * @since 1.0.0
		 */
		do_action( 'intercessor_after_install', $intercessor_options );

		if ( false !== $intercessor_options ) {
			// Delete the transient.
			\delete_transient( '_intercessor_installed' );
		}
	}

	/**
	 * Install network roles.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function network_roles() {
		global $wp_roles;

		// Bail if no WP roles.
		if ( ! is_object( $wp_roles ) ) {
			return;
		}

		// Check if Intercessor roles not created.
		if ( empty( $wp_roles->roles ) || ! array_key_exists( 'prayer_manager', $wp_roles->roles ) ) {

			// Create roles.
			$roles = intercessor()->roles;
			$roles->add_roles();
			$roles->add_caps();
		}
	}

	/**
	 * Set up required pages.
	 *
	 * @since 1.0.0
	 */
	public function setup_pages() {

        // Get all the Intercessor settings.
        $current_options = \get_option( 'intercessor_settings', [] );

        // Required store pages.
        $pages = array_flip(
            [
                'form_page',
                'prayers_page',
                'prayer_history',
            ]
        );

        // Look for missing pages
        $missing_pages  = array_diff_key( $pages, $current_options );
        $pages_to_check = array_intersect_key( $current_options, $pages );

        // Query for any existing pages.
        $posts = new WP_Query(
            [
                'include'   => array_values( $pages_to_check ),
                'post_type' => 'page',
            ]
        );

        // Default value for prayer page.
        $prayer = 0;

        // We'll only update settings on change.
        $changed  = false;

        // Loop through all pages, fix or create any missing ones.
        foreach ( array_flip( $pages ) as $page ) {

            // Checks if the page option exists.
            $page_object = empty( $missing_pages[ $page ] ) && ! empty( $posts->posts ) && ! empty( $pages_to_check[ $page ] )
                ? wp_filter_object_list( $posts->posts, array( 'ID' => $pages_to_check[ $page ] ) )
                : [];

            // Skip if page exists.
            if ( ! empty( $page_object ) ) {

                // Get the first item in the array.
                $page_object = reset( $page_object );

                // Set the prayer page.
                if ( 'form_page' === $page ) {
                    $prayer = $page_object->ID;
                }

                // Skip if page exists.
                continue;
            }

            // Get page attributes for missing pages.
            switch ( $page ) {

                // Prayer Request.
                case 'form_page':
                    $page_attributes = [
                        'post_title'     => esc_html__( 'Prayer Request', 'intercessor' ),
                        'post_content'   => '[intercessor_form]',
                        'post_status'    => 'publish',
                        'post_author'    => 1,
                        'post_parent'    => 0,
                        'post_type'      => 'page',
                        'comment_status' => 'closed',
                    ];
                    break;

                // Success.
                case 'prayers_page':
                    $page_attributes = [
                        'post_title'     => esc_html__( 'Prayers', 'intercessor' ),
                        'post_content'   => '[intercessor_prayers]',
                        'post_status'    => 'publish',
                        'post_author'    => 1,
                        'post_parent'    => $prayer,
                        'post_type'      => 'page',
                        'comment_status' => 'closed',
                    ];
                    break;

                // Prayer History.
                case 'prayer_history':
                    $page_attributes = [
                        'post_title'     => esc_html__( 'Prayer History', 'intercessor' ),
                        'post_content'   => '[intercessor_history]',
                        'post_status'    => 'publish',
                        'post_author'    => 1,
                        'post_type'      => 'page',
                        'post_parent'    => $prayer,
                        'comment_status' => 'closed',
                    ];
                    break;
            }

            // Create the new page.
            $new_page = wp_insert_post( $page_attributes );

            // Update the prayer page ID.
            if ( 'form_page' === $page ) {
                $prayer = $new_page;
            }

            // Set the page option.
            $current_options[ $page ] = $new_page;

            // Pages changed.
            $changed = true;
        }

        // Update the option
        if ( true === $changed ) {
            update_option( 'intercessor_settings', $current_options );
        }
    }

	/**
	 * Setup default settings options
	 *
	 * @access public
	 * @since  0.9.5
	 *
	 * @return array $options Array of default options
	 */
	public function setup_default_options() {
		$settings      = intercessor()->settings;
		$settings->install();
		$bible_passage = esc_html__( 'Again I say to you, if two of you agree on earth about anything they ask, it will be done for them by my Father in heaven. Matthew 18 verse 19.', 'intercessor' );

		$options = [
			'notify_period'                 => 'daily',
			'enable_registration'           => 1,
			'guest_access'                  => 'enabled',
			'intercessor_generate_username' => 1,
			'hold_prayers'                  => 1,
			'agree_label'                   => esc_html__( 'Agree to Terms', 'intercessor' ),
			'agree_text'                    => intercessor_get_default_terms(),
			'agree_privacy_label'           => esc_html__( 'Agree to Privacy Policy', 'intercessor' ),
			'request_title'                 => esc_html__( 'Prayer Request Submission Form', 'intercessor' ),
			'request_subtitle'              => esc_html__( 'Use the prayer form below to send us your prayer request. Our growing and powerful community of intercessors check our prayer wall daily to specifically pray for your request.', 'intercessor' ),
			'bible_passage'                 => $bible_passage,
			'submit_prayer_label'           => esc_html__( 'Submit Prayer', 'intercessor' ),
			'enable_prayer_count'           => 1,
			'prayer_list_title'             => esc_html__( 'Prayer Requests', 'intercessor' ),
			'prayer_list_message'           => esc_html__( 'Pray for any of these requests and click the "I Prayed" button to inform the user know that somebody prayed.', 'intercessor' ),
			'prayer_number'                 => 20,
			'prayed_for_label'              => esc_html__( 'I Prayed', 'intercessor' ),
			'from_name'                     => get_bloginfo( 'name' ),
			'from_email'                    => get_bloginfo( 'admin_email' ),
			'prayer_subject'                => esc_html__( 'Prayer Request Received', 'intercessor' ),
			'prayer_heading'                => esc_html__( 'We are praying for you', 'intercessor' ),
			'prayer_received_email'         => esc_html__( 'Dear', 'intercessor' ) . ' {name},\n\n' . esc_html__( 'Thank you for your submitting your prayer request on our site. We are currently praying for you. If for any reason you wish to edit your prayer request, click on the link below. Remain blessed in Jesus name.', 'intercessor' ) . '\n\n{intercessor_list}\n\n{sitename}',
			'prayer_notification_subject'   => esc_html__( 'New Prayer - Request #{prayer_id}', 'intercessor' ),
			'prayer_notification'           => intercessor_get_default_prayer_notification_email(),
			'admin_notice_emails'           => get_bloginfo( 'admin_email' ),
			'prayed_notice_subject'         => esc_html__( 'You have been prayed for - Request #{prayer_id}', 'intercessor' ),
			'prayed_notice_text'            => intercessor_get_default_prayed_notice_email(),
			'button_background_color'       => '#00bfef',
			'button_border_color'           => '#0094d3',
			'button_font_color'             => '#ffffff',
		];

		// Return the default options.
		return $options;
	}
}
