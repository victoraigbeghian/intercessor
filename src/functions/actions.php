<?php
/**
 * Intercessor Actions and Filters
 *
 * @package     Intercessor
 * @subpackage  Includes/Actions
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       0.9.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Function documented in src/functions/misc.php.
add_action( 'init', 'intercessor_process_actions' );
add_action( 'wp_ajax_intercessor_user_search', 'intercessor_ajax_user_search' );
add_action( 'wp_ajax_intercessor_requester_search', 'intercessor_ajax_requester_search' );
add_action( 'wp_ajax_intercessor_search_users', 'intercessor_ajax_search_users' );
add_action( 'widgets_init', 'intercessor_register_widgets' );


// Email actions documented in src/functions/email.php.
add_action( 'init', 'intercessor_load_email_tags', -999 );
add_action( 'intercessor_admin_prayer_notification', 'intercessor_admin_email_notice', 10, 2 );
add_action( 'intercessor_prayer_notification_email_settings', 'intercessor_email_template_preview' );
add_action( 'template_redirect', 'intercessor_display_email_template_preview' );
add_action( 'intercessor_post_insert_prayer', 'intercessor_trigger_prayer_notification', 999, 3 );
add_action( 'intercessor_email_links', 'intercessor_resend_prayer_notification' );
add_action( 'intercessor_view_notification', 'intercessor_render_notification_in_browser' );
add_action( 'intercessor_add_email_tags', 'intercessor_setup_email_tags' );
add_action( 'intercessor_notify_requester', 'intercessor_send_prayed_email', 10, 0 );
add_action( 'init', 'intercessor_send_requester_reports' );
//add_action( 'init', 'intercessor_do_all_requesters_reports' );
add_action( 'intercessor_insert_user', 'intercessor_new_user_notification', 10, 2 );
add_action( 'intercessor_created_user', 'intercessor_new_created_user_notification', 10, 2 );


// Prayer actions documented in src/functions/prayer.php.
add_action( 'init', 'intercessor_process_praying_for_request' );


// User related actions documented in src/functions/user.php.
add_action( 'user_register', 'intercessor_add_past_prayers_to_new_user', 10, 1 );
add_action( 'user_register', 'intercessor_connect_existing_requester_to_new_user', 10, 1 );
add_action( 'delete_user', 'intercessor_detach_deleted_user', 10, 1 );
add_action( 'intercessor_requester_post_attach_prayer', 'intercessor_connect_guest_requester_to_existing_user', 10, 4 );

// Request form actions documented in src/functions/form.php.
add_action( 'intercessor_user_register', 'intercessor_process_register_form' );
add_action( 'intercessor_user_login', 'intercessor_process_login_form' );
add_action( 'template_redirect', 'intercessor_enforced_ssl_asset_handler' );
add_action( 'template_redirect', 'intercessor_enforced_ssl_redirect_handler' );

// Function documented in src/functions/requester.php.
add_action( 'profile_update', 'intercessor_update_requester_email_on_user_update', 10, 2 );
