<?php
/**
 * Intercessor Admin Functions and actions.
 *
 * @package   Intercessor
 * @copyright Copyright (c) 2019, Victor Aigbeghian
 * @license   http://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since     0.9.5
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Processes all IPR actions sent via POST and GET by looking for the
 * 'intercessor-action' request and running do_action() to call the function
 *
 * @since 0.9.5
 * @return void
 */
function intercessor_process_admin_actions() {
	if ( isset( $_POST['intercessor-action'] ) ) {
		do_action( 'intercessor_' . $_POST['intercessor-action'], $_POST );
	}

	if ( isset( $_GET['intercessor-action'] ) ) {
		do_action( 'intercessor_' . $_GET['intercessor-action'], $_GET );
	}
}
add_action( 'admin_init', 'intercessor_process_admin_actions' );

/**
 * Return array of query arguments that should be removed from URLs.
 *
 * @since 0.9.5
 *
 * @return array
 */
function intercessor_admin_removable_query_args() : array {
	$args = array(
		'intercessor-action',
		'intercessor-notice',
		'intercessor-message',
		'intercessor-redirect'
	);

	return apply_filters( 'intercessor_admin_removable_query_args', $args );
}

/**
 * Checks if page is an Intercessor admin page
 *
 * @param	string $page Optional. Specified admin page to check
 *
 * @return	bool True if it is an Intercessor admin page, otherwise False.
 *@since 	0.9.5
 *
 */
function intercessor_is_admin_page() : bool {

	// Bail if the wp_loaded hook has not been called or it is not an admin.
	if ( ! is_admin() || ! did_action( 'wp_loaded' ) ) {
		$ret = false;
	}

	// Get available pages.
	$page        = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
	$admin_pages = [
		'intercessor-prayers',
		'intercessor-requesters',
		'intercessor-tools',
		'intercessor-settings',
        'intercessor-upgrades',
		'intercessor-reports',
	];

	if ( ! empty( $page ) && in_array( $page, $admin_pages, true ) ) {
		$ret = true;
	} else {
		$ret = in_array( $page, $admin_pages, true );
	}

	// Return filtered admin page.
	return apply_filters( 'intercessor_is_admin_page', $ret );

}

/**
 * Get the current Intercessor admin screen
 *
 * @since 0.9.5
 *
 * @return bool|string Returns the admin page
 */
function intercessor_get_admin_current_screen() {
	// Bail early if not on an Intercessor admin page.
	if ( ! intercessor_is_admin_page() ) {
		return false;
	}

	return ( isset( $_GET['page'] ) )
		? sanitize_text_field( $_GET['page'] )
		: false;
}

/**
 * Retrieve the admin page url.
 *
 * @param string $type The type of admin page ( 'prayer-requests', 'Requesters' )
 * @param array  $query Array of arguments to query with.
 *
 * @return mixed|void $url, $type, $query sting
 * @since 0.9.5
 */
function intercessor_get_admin_url( string $type = '', array $query = [] ) {
	// Set up pages slugs.
	$page  = 'intercessor';
	$known = [
		'prayers',
        'requesters',
        'tools',
        'settings',
        'reports',
        'upgrades',
	];

	// Check if the page is in defined array.
	if ( in_array( $type, $known, true ) ) {
		$page = "intercessor-{$type}";
	}

	// Merge page arguments.
	$admin_args = array_merge( array( 'page' => $page ), $query );

	// Set up pages url.
	$url = add_query_arg(
		$admin_args,
		admin_url( 'admin.php' )
	);

	// Return filtered url of admin pages.
	return apply_filters( 'intercessor_get_admin_url', $url, $type, $query );
}

if ( ! function_exists( 'intercessor_admin_get_notes_html' ) ) {
	/**
	 * Get the HTML used to output all of the notes for a single object
	 *
	 * @param array $notes
	 *
	 * @return string
	 * @since 0.9.5
	 *
	 */
	function intercessor_admin_get_notes_html( array $notes = [] ) : string {

		// Whether to show or hide the "No notes" default text.
		$no_notes_display = ! empty( $notes )
			? ' style="display:none;"'
			: '';

		// Start a buffer.
		ob_start(); ?>

		<div class="intercessor-notes" id="intercessor-notes">
			<?php

			// Output notes.
			foreach ( $notes as $note ) {
				echo intercessor_admin_get_note_html($note);
			}

			?>

			<p class="intercessor-no-notes"<?php echo $no_notes_display; ?>>
				<?php esc_html_e('No notes.', 'intercessor'); ?>
			</p>
		</div>

		<?php

		// Return the current buffer.
		return ob_get_clean();
	}
}

if ( ! function_exists( 'intercessor_admin_get_note_html' ) ) {
	/**
	 * Get the HTML used to output a single note, from an array of notes
	 *
	 * @param int $note_id
	 *
	 * @return string
	 * @return void|mixed
	 * @since 0.9.5
	 */
	function intercessor_admin_get_note_html( int $note_id = 0) : string {

		// Get the note
		$note = is_numeric($note_id)
			? intercessor_get_note($note_id)
			: $note_id;

		// Bail if no note.
		if ( empty( $note ) ) {
			return false;
		}

		// User who created the note.
		$user_id = $note->user_id;
		$author  = ! empty( $user_id )
			? get_userdata( $user_id )->display_name
			: esc_html__( 'Intercessor Bot', 'intercessor');

		// URL to delete note
		$delete_note_url = wp_nonce_url(
			add_query_arg(
				[
					'intercessor-action' => 'delete_note',
					'note_id'            => $note->id,
				]
			), 'intercessor_delete_note_' . $note->id
		);

		// Start a buffer.
		ob_start();
		?>

		<div class="intercessor-note" id="intercessor-note-<?php echo esc_attr( $note->id ); ?>">
			<div>
				<strong class="intercessor-note-author"><?php echo esc_html( $author ); ?></strong>
				<time
					datetime="<?php echo esc_attr( $note->date_created ); ?>"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $note->date_created ) ); ?></time>

				<p><?php echo make_clickable( $note->content ); ?></p>
				<a href="<?php echo esc_url( $delete_note_url ); ?>#intercessor-notes" class="intercessor-delete-note"
				   data-note-id="<?php echo esc_attr( $note->id ); ?>"
				   data-object-id="<?php echo esc_attr( $note->object_id ); ?>"
				   data-object-type="<?php echo esc_attr( $note->object_type ); ?>">
					<?php esc_html_e( 'Delete', 'intercessor' ); ?>
				</a>
			</div>
		</div>

		<?php

		// Return the current buffer.
		return ob_get_clean();
	}
}

if ( ! function_exists( 'intercessor_admin_get_new_note_form' ) ) {
	/**
	 * Get the HTML used to add a note to an object ID and type
	 *
	 * @param int $object_id
	 * @param string $object_type
	 *
	 * @return string
	 * @since 0.9.5
	 *
	 */
	function intercessor_admin_get_new_note_form( $object_id = 0, $object_type = '' ) {

		// Start a buffer
		ob_start(); ?>

		<div class="intercessor-add-note">
			<label for="intercessor-new-note"><?php esc_html__( 'Add New Note', 'intercessor' ); ?></label>
			<textarea name="intercessor-note" id="intercessor-note"></textarea>

			<p>
				<button type="button" id="intercessor-add-note"
						class="intercessor-note-submit button button-secondary left"
						data-object-id="<?php echo esc_attr( $object_id ); ?>"
						data-object-type="<?php echo esc_attr( $object_type ); ?>">
					<?php esc_html_e( 'Add Note', 'intercessor' ); ?>
				</button>
			</p>
			<?php wp_nonce_field( 'intercessor_note', 'intercessor_note_nonce' ); ?>
		</div>

		<?php

		// Return the current buffer.
		return ob_get_clean();
	}
}

if ( ! function_exists( 'intercessor_get_note_delete_redirect_url' ) ) {
	/**
	 * Return the URL to redirect to after deleting a note
	 *
	 * For now, this is always the current URL, because we aren't ever sure where
	 * notes are being used. Maybe this will need a filter or something, someday.
	 *
	 * @return string
	 * @since 0.9.5
	 *
	 */
	function intercessor_get_note_delete_redirect_url() {

		// HTTP or HTTPS.
		$scheme = is_ssl()
			? 'https'
			: 'http';

		$host        = esc_url( $_SERVER[HTTP_HOST] );
		$request_url = esc_url( $_SERVER[REQUEST_URI] );

		// Return the concatenated URL.
		return "{$scheme}://{$host}{$request_url}";
	}
}

if ( ! function_exists( 'intercessor_admin_get_notes_pagination' ) ) {
	/**
	 * Return the HTML used to paginate through notes.
	 *
	 * @param array $args
	 *
	 * @return false|string
	 * @since 0.9.5
	 *
	 */
	function intercessor_admin_get_notes_pagination( $args = [] ) {

		// Parse args.
		$query = wp_parse_args(
			$args,
			[
				'total'        => 0,
				'pag_arg'      => 'paged',
				'base'         => '%_%',
				'show_all'     => true,
				'prev_text'    => is_rtl() ? '&rarr;' : '&larr;',
				'next_text'    => is_rtl() ? '&larr;' : '&rarr;',
				'add_fragment' => '',
			]
		);

		// Maximum notes per page.
		$per_page        = apply_filters( 'intercessor_notes_per_page', 20 );
		$query['total']  = ceil( $query['total'] / $per_page );
		$query['format'] = "?{$query['pag_arg']}=%#%";

		// Don't allow pagination beyond the boundaries.
		$query['current'] = ! empty( $_GET[ $query['pag_arg'] ] ) && is_numeric( $_GET[ $query['pag_arg'] ] )
			? absint( $_GET[ $query['pag_arg'] ] )
			: 1;

		// Start a buffer.
		ob_start();
		?>

		<div class="intercessor-note-pagination">
			<?php echo paginate_links( $query ); ?>
		</div>

		<?php

		// Return the current buffer.
		return ob_get_clean();
	}
}

/**
 * Add a note via AJAX.
 *
 * @since 0.9.5
 */
function intercessor_admin_ajax_add_note() {
	
	// Check AJAX referrer.
	check_ajax_referer( 'intercessor_note', 'nonce' );

	// Bail if user cannot delete notes.
	if ( ! current_user_can( 'manage_prayer_settings' ) ) {
		wp_die( -1 );
	}

	// Get object ID.
	$object_id = ! empty( $_POST['object_id'] )
		? absint( $_POST['object_id'] )
		: 0;

	// Get object type.
	$object_type = ! empty( $_POST['object_type'] )
		? sanitize_key( $_POST['object_type'] )
		: '';

	// Bail if no object.
	if ( empty( $object_id ) || empty( $object_type ) ) {
		wp_die( -1 );
	}

	// Get note contents (maybe sanitize).
	$note = ! empty( $_POST['note'] )
		? wp_kses( stripslashes_deep( $_POST['note'] ), [] )
		: '';

	// Bail if no note.
	if ( empty( $note ) ) {
		wp_die( -1 );
	}

	// Add the note
	$note_args = [
        'object_id'   => $object_id,
        'object_type' => $object_type,
        'content'     => $note,
        'user_id'     => get_current_user_id(),
    ];

	// Add the new note.
	$note_id = intercessor_add_item( 'note', $note_args );

	// Process ajax response on successful note addition.
	if ( $note_id ) {
		$n = new WP_Ajax_Response();
		$n->add(
			[
				'what' => 'intercessor_note_html',
				'data' => intercessor_admin_get_note_html( $note_id ),
			]
		);
		$n->send();
	}
}
add_action( 'wp_ajax_intercessor_add_note', 'intercessor_admin_ajax_add_note' );

/**
 * Delete a note.
 *
 * @since 0.9.5
 *
 * @param array $data Data from $_GET.
 */
function intercessor_admin_delete_note( array $data = [] ) {

	// Bail if missing any data.
	if ( empty( $data['_wpnonce'] ) || empty( $data['note_id'] ) ) {
		return;
	}

	// Bail if nonce fails.
	if ( ! wp_verify_nonce( $data['_wpnonce'], 'intercessor_delete_note_' . $data['note_id'] ) ) {
		return;
	}

	// Try to delete.
	$deleted = intercessor_process_item( 'note', 'delete', $data['note_id'], false );

	// Redirect if successfully deleted.
	if ( $deleted ) {
		intercessor_redirect( intercessor_get_note_delete_redirect_url() );
	}
}
add_action( 'intercessor_delete_note', 'intercessor_admin_delete_note' );

/**
 * Delete a note via AJAX.
 *
 * @since 0.9.5
 */
function intercessor_admin_ajax_delete_note() {

	// Check AJAX referrer.
	check_ajax_referer( 'intercessor_note', 'nonce' );

	// Bail if user cannot delete notes.
	if ( ! current_user_can( 'manage_prayer_settings' ) ) {
		wp_die( -1 );
	}

	// Get note ID.
	$note_id = ! empty( $_POST['note_id'] )
		? absint( $_POST['note_id'] )
		: 0;

	// Bail if no note.
	if ( empty( $note_id ) ) {
		wp_die( -1 );
	}

	// Delete note.
	$deleted = intercessor_process_item( 'note', 'delete', $note_id, false );
	if ( $deleted ) {
		wp_die( 1 );
	}

	wp_die( 0 );
}
add_action( 'wp_ajax_intercessor_delete_note', 'intercessor_admin_ajax_delete_note' );

if ( ! function_exists( 'intercessor_get_admin_base_url' ) ) {
	/**
     * Retrieve admin base url.
     *
     * @since 1.1.0
	 * @return mixed|void
	 */
    function intercessor_get_admin_base_url() {
        // Setup defaults.
        $defaults  = [
            'page' => 'intercessor-prayers',
        ];
        $admin_url = admin_url( 'admin.php' );

        // Base url.
        $base = add_query_arg( $defaults, $admin_url );

        // Filter and return base url.
        return apply_filters( 'intercessor_get_admin_base_url', $base, $defaults, $admin_url );
    }
}

if ( ! function_exists( 'intercessor_has_upgrade' ) ) {
	/**
	 * Checks if plugin has upgrade.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	function intercessor_has_upgrade(): bool {
		$version = intercessor_get_db_version();
		$current = intercessor_format_db_version( INTERCESSOR_VERSION );

		// Bail if no new version.
		if ( version_compare( $current, $version, '>' ) ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'intercessor_get_view' ) ) {
    /**
     * Get admin page views.
     *
     * @param string $path File path.
     * @param array  $args Array of arguments to parse with query.
     *
     * @return void
     * @since 1.1.0
     */
    function intercessor_get_view( string $path = '', array $args = [] ) {
        // Allow file view.
        if ( substr( $path, -4 ) !== '.php' ) {
            $path = intercessor_get_path( "src/Admin/views/{$path}.php" );
        }

        // Include the file
        if ( file_exists( $path ) ) {
            // Protect the path with `EXTR_SKIP`.
            extract( $args, EXTR_SKIP );
            include $path;
        }
    }
}
