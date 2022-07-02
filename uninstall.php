<?php
/**
 * Uninstall Intercessor
 *
 * Deletes all the plugin data i.e.
 * 		3. Plugin pages.
 * 		4. Plugin options.
 * 		5. Capabilities.
 * 		6. Roles.
 * 		7. Database tables.
 * 		8. Cron events.
 *
 * @package     Intercessor
 * @subpackage  Uninstall
 * @copyright   Copyright (c) 2019,Victor Aigbeghian
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Load Intercessor file.
require_once 'intercessor.php';

// Proccess actions, only if specified in plugin settings.
if ( intercessor_get_option( 'uninstall_on_delete' ) ) {

    /** Delete the Plugin Pages */
    $intercessor_created_pages = [
        'intercessor_request_page',
        'intercessor_listing_page',
        'intercessor_history_page'
    ];

    foreach ( $intercessor_created_pages as $pages ) {
        $page = intercessor_get_option( $pages, false );
        if ( $page ) {
            wp_delete_post( $page, true );
        }
    }

    /** Delete all the Plugin Options */
    delete_option( 'intercessor_settings' );
    delete_option( 'intercessor_version' );
    delete_option( 'intercessor_use_php_sessions' );
    delete_option( 'intercessor_completed_upgrades' );
    delete_option( 'widget_intercessor-recent-requests' );
    delete_option( 'widget_intercessor-request-form' );
    delete_option( 'intercessor_version_upgraded_from' );

    /** Delete Capabilities */
    intercessor()->roles->remove_caps();

    /** Delete the Roles */
    $intercessor_roles = [
        'prayer_manager',
        'prayer_warrior',
        'requester'
    ];

    foreach ( $intercessor_roles as $role ) {
        remove_role( $role );
    }

    // Remove all database tables.
    if ( is_multisite() ) {
        $sites = get_sites(
            [
                'fields' => 'ids',
            ]
        );

        // Remove all database tables.
        foreach ( $sites as $site_id ) {
            // Switch to blog.
            switch_to_blog( $site_id );

            // Uninstall all database tables.
            intercessor_uninstall_tables();

            // Restore current blog.
            restore_current_blog();
        }
    } else {
        // Uninstall all database tables.
        intercessor_uninstall_tables();

    }

    // Remove any transients we've left behind.
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_intercessor\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_intercessor\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_intercessor\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_timeout\_intercessor\_%'" );

    // Cleanup Cron Events.
    wp_clear_scheduled_hook( 'intercessor_notify_requester' );
}

/**
 * Drop database tables and delete their options.
 *
 * @since 1.1.0
 */
function intercessor_uninstall_tables() {
    $tables = intercessor()->tables;

    // Drop database tables and delete stored options.
    foreach ( $tables as $table ) {
        return $table->uninstall();
    }
}

// Removes all cache items.
wp_cache_flush();
