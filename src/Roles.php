<?php
/**
 * Requester Roles Object
 *
 * @package     Intercessor
 * @subpackage  Classes/Roles
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0.php GNU Public License
 * @since       1.0.0
 */

namespace Intercessor;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Intercessor Roles.
 *
 * @since 0.9.5
 */
class Roles {

	/**
	 * Constructor
	 *
	 * @since  0.9.5
	 * @return void
	 */
	public function __construct() {}

	/**
	 * Add new prayer roles with default WP caps
	 *
	 * @access public
	 * @since 0.9.5
	 * @return void
	 */
	public function add_roles() {
		add_role(
			'prayer_manager',
			esc_html__( 'Prayer Manager', 'intercessor' ),
			[
				'level_9'                => true,
				'level_8'                => true,
				'level_7'                => true,
				'level_6'                => true,
				'level_5'                => true,
				'level_4'                => true,
				'level_3'                => true,
				'level_2'                => true,
				'level_1'                => true,
				'level_0'                => true,
				'read'                   => true,
				'read_private_pages'     => true,
				'read_private_posts'     => true,
				'edit_users'             => true,
				'edit_posts'             => true,
				'edit_pages'             => true,
				'edit_published_posts'   => true,
				'edit_published_pages'   => true,
				'edit_private_pages'     => true,
				'edit_private_posts'     => true,
				'edit_others_posts'      => true,
				'edit_others_pages'      => true,
				'publish_posts'          => true,
				'publish_pages'          => true,
				'delete_posts'           => true,
				'delete_pages'           => true,
				'delete_private_pages'   => true,
				'delete_private_posts'   => true,
				'delete_published_pages' => true,
				'delete_published_posts' => true,
				'delete_others_posts'    => true,
				'delete_others_pages'    => true,
				'manage_categories'      => true,
				'manage_links'           => true,
				'moderate_comments'      => true,
				'upload_files'           => true,
				'export'                 => true,
				'import'                 => true,
				'list_users'             => true,
			]
		);

		// Prayer Warrior role.
		add_role( 
			'prayer_warrior',
			esc_html__( 'Prayer Warrior', 'intercessor' ),
			[
				'read'         => true,
				'edit_posts'   => false,
				'delete_posts' => false,
			]
		);

		// Requester role
		add_role( 
			'requester',
			esc_html__( 'Requester', 'intercessor' ),
			[
				'read' => true,
			]
		);

	}

	/**
	 * Add new prayer-specific capabilities
	 *
	 * @access public
	 * @since  0.9.5
	 * @global WP_Roles $wp_roles
	 * @return void
	 */
	public function add_caps() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new \WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {
			$wp_roles->add_cap( 'prayer_manager', 'edit_prayers' );
			$wp_roles->add_cap( 'prayer_manager', 'export_prayer_reports' );
			$wp_roles->add_cap( 'prayer_manager', 'manage_prayer_settings' );
			$wp_roles->add_cap( 'prayer_manager', 'view_prayer_reports' );
			$wp_roles->add_cap( 'prayer_manager', 'view_prayer_sensitive_data' );

			$wp_roles->add_cap( 'administrator', 'edit_prayers' );
			$wp_roles->add_cap( 'administrator', 'export_prayer_reports' );
			$wp_roles->add_cap( 'administrator', 'manage_prayer_settings' );
			$wp_roles->add_cap( 'administrator', 'view_prayer_reports' );
			$wp_roles->add_cap( 'administrator', 'view_prayer_sensitive_data' );

			$wp_roles->add_cap( 'prayer_warrior', 'edit_prayers' );
			$wp_roles->add_cap( 'prayer_warrior', 'read_private_prayers' );
			$wp_roles->add_cap( 'prayer_warrior', 'uplift_prayers' );
		}
	}

	/**
	 * Remove core post type capabilities (called on uninstall)
	 *
	 * @access public
	 * @since 0.9.5
	 * @return void
	 */
	public function remove_caps() {

		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new \WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {
			/** Prayer Manager Capabilities */
			$wp_roles->remove_cap( 'prayer_manager', 'view_prayer_reports' );
			$wp_roles->remove_cap( 'prayer_manager', 'view_prayer_sensitive_data' );
			$wp_roles->remove_cap( 'prayer_manager', 'export_prayer_reports' );
			$wp_roles->remove_cap( 'prayer_manager', 'manage_prayers' );
			$wp_roles->remove_cap( 'prayer_manager', 'manage_prayer_settings' );

			/** Site Administrator Capabilities */
			$wp_roles->remove_cap( 'administrator', 'view_prayer_reports' );
			$wp_roles->remove_cap( 'administrator', 'view_prayer_sensitive_data' );
			$wp_roles->remove_cap( 'administrator', 'export_prayer_reports' );
			$wp_roles->remove_cap( 'administrator', 'manage_prayers' );
			$wp_roles->remove_cap( 'administrator', 'manage_prayer_settings' );

			/** Prayer Warrior Capabilities */
			$wp_roles->remove_cap( 'prayer_warrior', 'edit_prayers' );
			$wp_roles->remove_cap( 'prayer_warrior', 'read_private_prayers' );
			$wp_roles->remove_cap( 'prayer_warrior', 'uplift_prayers' );

			/** Requester Capabilities */
			$wp_roles->remove_cap( 'requester', 'read' );

		}
	}
}
