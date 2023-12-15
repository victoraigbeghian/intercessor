<?php
/**
 * Intercessor Scripts and Styles
 *
 * @link       https://github.com/victoraigbeghian
 * @since      0.9.5
 *
 * @package    Intercessor
 * @subpackage Intercessor/includes
 */

// If this file is called directly, abort.
defined( 'WPINC' ) || exit;

/**
 * Load Scripts
 *
 * Enqueues the required scripts.
 *
 * @since 0.9.5
 * @global $post
 * @return void
 */
function intercessor_load_scripts() {

	$js_dir = INTERCESSOR_URL . 'assets/js/frontend/';

	// Use minified libraries if SCRIPT_DEBUG is turned off.
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	if ( intercessor_is_prayer_request_form_page() ) {
		wp_register_script( 'intercessor-js', $js_dir . 'intercessor' . $suffix . '.js', array( 'jquery' ), INTERCESSOR_VERSION, true );
		wp_enqueue_script( 'intercessor-js' );
	}

	wp_register_script( 'intercessor-ajax', $js_dir . 'intercessor-ajax' . $suffix . '.js', array( 'jquery' ), INTERCESSOR_VERSION, false );

	wp_localize_script(
		'intercessor-ajax',
		'intercessor_params',
		apply_filters(
			'intercessor_ajax_params',
			array(
				'ajaxurl'   => intercessor_get_ajax_url(),
				'praying'   => esc_html__( 'You are praying.', 'intercessor' ),
				'prayed'    => esc_html__( 'Thanks for praying.', 'intercessor' ),
				'nopraying' => esc_html__( 'There was an error processing your praying for that request. please refresh your browser and try again.', 'intercessor' ),
			) )
	);

	if ( intercessor_is_listing_page() ) {

		wp_enqueue_script( 'intercessor-ajax' );

	}

	if ( intercessor_is_prayer_history_page() ) {
		wp_register_script( 'intercessor-history', $js_dir . 'intercessor-history' . $suffix . '.js', array( 'jquery' ), INTERCESSOR_VERSION, false );

		wp_localize_script(
			'intercessor-history',
			'intercessor_vars',
			apply_filters(
				'intercessor_history_params',
				[
					'delete_prayer' => esc_html__( 'Are you sure you want to delete this prayer request? The process is irreversible.', 'intercessor' ),
				]
			)
		);

		wp_enqueue_script( 'intercessor-history' );

		// Enqueue Google recaptcha if the user is not logged in.
		if ( ! is_user_logged_in() ) {
		//	if ( intercessor_recaptcha_is_enabled() ) {
				wp_register_script( 'intercessor-recaptcha', 'https://www.google.com/recaptcha/api.js', [], INTERCESSOR_VERSION, true );
				wp_enqueue_script( 'intercessor-recaptcha' );
		//	}
		}
	}


}
add_action( 'wp_enqueue_scripts', 'intercessor_load_scripts' );

/**
 * Load Styles
 *
 * Enqueues the required styles.
 *
 * @since 0.9.5
 * @return void
 */
function intercessor_load_styles() {
	$css_dir = INTERCESSOR_URL . 'assets/css/';

	// Use minified libraries if SCRIPT_DEBUG is turned off.
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	// Register frontend notices style.
	wp_register_style( 'intercessor-notices', $css_dir . 'intercessor-notices' . $suffix . '.css', [], INTERCESSOR_VERSION, 'all' );

	// Register and conditionally enqueue necessary styles.
	if ( intercessor_is_prayer_request_form_page() ) {
		wp_register_style( 'intercessor-form', $css_dir . 'intercessor' . $suffix . '.css', [], INTERCESSOR_VERSION, 'all' );
		wp_enqueue_style( 'intercessor-form' );
		wp_enqueue_style( 'intercessor-notices' );
	}

	if ( intercessor_is_listing_page() ) {
		wp_register_style( 'intercessor-prayers', $css_dir . 'intercessor-listing' . $suffix . '.css', [], INTERCESSOR_VERSION, 'all' );
		wp_enqueue_style( 'intercessor-prayers' );
		wp_enqueue_style( 'intercessor-notices' );
	}

	if ( intercessor_is_prayer_history_page() ) {
		wp_register_style( 'intercessor-history', $css_dir . 'intercessor-history' . $suffix . '.css', [], INTERCESSOR_VERSION, 'all' );
		wp_enqueue_style( 'intercessor-history' );
		wp_enqueue_style( 'intercessor-notices' );
	}
}
add_action( 'wp_enqueue_scripts', 'intercessor_load_styles' );

/**
 * Load Admin Styles and Scripts
 *
 * Enqueues the required styles.
 *
 * @since 0.9.5
 * @global $post
 * @return void
 */
function intercessor_load_admin_styles() {
	// Set up variables.
	$css_dir = INTERCESSOR_URL . 'assets/css/';
	$version = INTERCESSOR_VERSION;

	// Use minified libraries if SCRIPT_DEBUG is turned off.
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	// Register Intercessor styles globally..
	wp_register_style( 'intercessor-admin', $css_dir . 'intercessor-admin' . $suffix . '.css', [], $version );
	wp_enqueue_style( 'intercessor-admin' );

	// Bail if not on intercessor admin pages.
	if ( ! intercessor_is_admin_page() ) {
		return;
	}

	// Register styles.
	wp_register_style( 'jquery-chosen', $css_dir . 'chosen' . $suffix . '.css', [], $version );
	wp_register_style( 'intercessor-reports', $css_dir . 'intercessor-admin-reports' . $suffix . '.css', [], $version );

	// Enqueue necessary styles.
	wp_enqueue_style( 'thickbox' );
	wp_enqueue_style( 'jquery-chosen' );
	wp_enqueue_media();

	// jQuery UI styles are loaded on our admin pages only.
	$ui_style = ( 'classic' === get_user_option( 'admin_color' ) ) ? 'classic' : 'fresh';
	wp_enqueue_style( 'jquery-ui-css', $css_dir . 'jquery-ui-' . $ui_style . '.min.css' );
}
add_action( 'admin_enqueue_scripts', 'intercessor_load_admin_styles' );


/**
 * Register admin scripts
 *
 * @since 1.0.0
 */
function intercessor_load_admin_scripts() {

	$js_dir     = INTERCESSOR_URL . 'assets/js/';
	$admin_deps = [ 'jquery' ];
	$version    = INTERCESSOR_VERSION;

	// Use minified libraries if SCRIPT_DEBUG is turned off.
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	// Bail if not on intercessor admin pages.
	if ( ! intercessor_is_admin_page() ) {
		return;
	}
	// Register required scripts globally.
	wp_register_script( 'jquery-chosen', $js_dir . 'vendor/chosen.jquery' . $suffix . '.js', $admin_deps, $version, true );
	wp_register_script( 'intercessor-admin-prayers', $js_dir . 'admin/prayers/index' . $suffix . '.js', $admin_deps, $version, false );

	// Enqueue the scripts.
	wp_enqueue_script( 'jquery-chosen' );
	wp_enqueue_script( 'jquery-form' );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_script( 'jquery-ui-tooltip' );

	// Localize admin scripts.
	wp_localize_script(
		'intercessor-admin-prayers',
		'intercessor_vars',
		[
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
		]
	);
}
add_action( 'admin_enqueue_scripts', 'intercessor_load_admin_scripts' );

/**
 * Admin head styles.
 *
 * @since 1.1.1
 */
function intercessor_admin_head() {
	?>
	<style type="text/css" media="screen">
		@font-face {
			font-family: 'ipr-icomoon';
			src: url('<?php echo INTERCESSOR_URL . 'assets/fonts/icomoon.eot?ngjl88'; ?>');
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
add_action( 'admin_head', 'intercessor_admin_head' );
