<?php
/**
 * Admin area bootstrapper.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\Admin\Prayer_Request_List_Table;
use Intercessor\Admin\Requester_List_Table;
use Intercessor\Admin\Requester_View;
use Intercessor\Admin\Display_Page;
use Intercessor\Admin\Dashboard_Widget;
use Intercessor\Admin\Requester_Note_Handler;
use Intercessor\Admin\Admin_Add_Request_Handler;
use Intercessor\Reports\Reports_Page;
use Intercessor\Http\Request;
use Intercessor\Tools\Tools_Admin_Page;
use Intercessor\Util\Registration_Handler;

/**
 * Bootstraps all wp-admin functionality for the Intercessor plugin.
 *
 * Registers the top-level Intercessor menu and all submenus, enqueues
 * admin-only assets, wires all admin_post handlers, and delegates settings
 * registration to DisplayPage.
 *
 * Settings page ownership:
 *   Display_Page::register() calls Renderer::init() (hooks admin_init for the
 *   Settings API) and adds the page under the Intercessor top-level menu via
 *   addMenuPages(). DisplayPage does NOT register its own admin_menu hook —
 *   AdminLoader is the sole owner of menu registration.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Admin_Loader {

	/**
	 * Shared DisplayPage instance initialised once in register() and reused
	 * by renderSettingsPage() so the Renderer is always available when the
	 * page callback fires.
	 *
	 * @since 1.0.0
	 * @var   Display_Page
	 */
	private Display_Page $displayPage;

	/**
	 * Register all admin-side WordPress hooks.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		// Initialise DisplayPage once. register() attaches Renderer::init()
		// (admin_init) but no longer adds its own admin_menu hook — menu
		// registration is handled exclusively by addMenuPages() below.
		$this->displayPage = new Display_Page();
		$this->displayPage->register_settings();

		// Reports page.
		( new Reports_Page() )->register();

		// Dashboard widget.
		( new Dashboard_Widget() )->register();

		add_action( 'admin_menu',            array( $this, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'print_menu_icon_style' ) );

		// Plugins list page: action links and row meta.
		add_filter( 'plugin_action_links_' . INTERCESSOR_BASENAME, array( $this, 'add_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'add_row_meta' ), 10, 4 );

		// Moderation (status changes).
		add_action( 'admin_post_intercessor_moderate',    array( Moderation_Handler::class,  'handle' ) );

		// Bulk actions on the requests list table.
		add_action( 'admin_post_intercessor_bulk_action', array( Bulk_Action_Handler::class,  'handle' ) );

		// Prayer notes.
		add_action( 'admin_post_intercessor_add_note',    array( Note_Handler::class, 'handle_add' ) );
		add_action( 'admin_post_intercessor_delete_note', array( Note_Handler::class, 'handle_delete' ) );

		// Requester management.
		add_action( 'admin_post_intercessor_delete_requester',         array( $this, 'handle_delete_requester' ) );
		add_action( 'admin_post_intercessor_resend_confirmation',      array( $this, 'handle_resend_confirmation' ) );
		add_action( 'admin_post_intercessor_manual_confirm_account',   array( $this, 'handle_manual_confirm_account' ) );

		// Requester notes.
		add_action( 'admin_post_intercessor_add_requester_note',    array( Requester_Note_Handler::class, 'handle_add' ) );
		add_action( 'admin_post_intercessor_delete_requester_note', array( Requester_Note_Handler::class, 'handle_delete' ) );

		// Admin add request form.
		add_action( 'admin_post_intercessor_admin_add_request',     array( Admin_Add_Request_Handler::class, 'handle' ) );

		// Export handlers.
		( new Tools_Admin_Page() )->register();
	}

	// -------------------------------------------------------------------------
	// Plugins list page
	// -------------------------------------------------------------------------

	/**
	 * Add a Settings action link on the Plugins list page.
	 *
	 * Prepends the link so it appears before the default plugin links
	 * (Deactivate, etc.).
	 *
	 * @since  1.0.0
	 * @param  array $links Existing action links for this plugin.
	 * @return array        Modified links array with Settings prepended.
	 */
	public function add_action_links( array $links ): array {
		return array_merge(
			array(
				'settings' => sprintf(
					/* translators: %s: URL to the plugin's settings page --- IGNORE --- */
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'admin.php?page=intercessor-settings' ) ),
					esc_html__( 'Settings', 'intercessor' )
				),
			),
			$links
		);
	}

	/**
	 * Add Documentation and Support links to the plugin row meta on the
	 * Plugins list page.
	 *
	 * Only appends links for the Intercessor plugin row; other plugins are
	 * returned unchanged.
	 *
	 * @since  1.0.0
	 * @param  string[] $plugin_meta Plugin meta links already generated by WordPress.
	 * @param  string   $plugin_file Relative path to the plugin's main file.
	 * @param  array    $plugin_data Array of plugin header data.
	 * @param  string   $status      Plugin status ('all', 'active', 'inactive', etc.).
	 * @return string[]              Modified plugin meta array.
	 */
	public function add_row_meta( array $plugin_meta, string $plugin_file, array $plugin_data, string $status ): array {
		if ( INTERCESSOR_BASENAME !== $plugin_file ) {
			return $plugin_meta;
		}

		return array_merge(
			$plugin_meta,
			array(
				 sprintf(
					/* translators: %s: URL to the plugin's documentation --- IGNORE --- */
					'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
					esc_url( 'https://github.com/victoraigbeghian/intercessor/wiki' ),
					esc_html__( 'Documentation', 'intercessor' )
				),
				sprintf(
					/* translators: %s: URL to the plugin's support page --- IGNORE --- */
					'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
					esc_url( 'https://github.com/victoraigbeghian/intercessor/issues' ),
					esc_html__( 'Support', 'intercessor' )
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Menu registration
	// -------------------------------------------------------------------------

	/**
	 * Register the Intercessor top-level menu page and all submenus.
	 *
	 * The Prayer Requests submenu label includes a pending-count badge
	 * (styled identically to the WordPress Comments bubble) so moderators
	 * can see at a glance whether action is required. The badge is only
	 * rendered when the current user holds the `edit_prayers` capability
	 * and the pending count is greater than zero.
	 *
	 * Settings is registered here — not in DisplayPage — so there is exactly
	 * one owner of the 'intercessor-settings' slug.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function add_menu_pages(): void {
		add_menu_page(
			__( 'Intercessor', 'intercessor' ),
			__( 'Intercessor', 'intercessor' ),
			'manage_options',
			'intercessor',
			array( $this, 'render_dashboard' ),
			'none',
			30
		);

		// The first add_submenu_page() call with the parent's own slug renames
		// the auto-generated duplicate entry WordPress always creates.
		add_submenu_page(
			'intercessor',
			__( 'Dashboard', 'intercessor' ),
			__( 'Dashboard', 'intercessor' ),
			'manage_options',
			'intercessor',
			array( $this, 'render_dashboard' )
		);

		// Build the Prayer Requests submenu label — append a pending badge
		// when there are prayers awaiting moderation.
		$requests_label = __( 'Prayer Requests', 'intercessor' );

		if ( current_user_can( 'edit_prayers' ) ) {
			$pending = (int) ( new \Intercessor\Database\Query\Prayer_Request_Query() )->count_pending();

			if ( $pending > 0 ) {
				$requests_label .= sprintf(
					' <span class="awaiting-mod count-%1$d"><span class="pending-count">%1$s</span></span>',
					absint( $pending ),
					number_format_i18n( $pending )
				);
			}
		}

		add_submenu_page(
			'intercessor',
			__( 'Prayer Requests', 'intercessor' ),
			$requests_label,
			'edit_prayers',
			'intercessor-requests',
			array( $this, 'render_requests_page' )
		);

		add_submenu_page(
			'intercessor',
			__( 'Requesters', 'intercessor' ),
			__( 'Requesters', 'intercessor' ),
			'edit_prayers',
			'intercessor-requesters',
			array( $this, 'render_requesters_page' )
		);

		add_submenu_page(
			'intercessor',
			__( 'Tools', 'intercessor' ),
			__( 'Tools', 'intercessor' ),
			'view_prayer_reports',
			'intercessor-tools',
			array( $this, 'render_tools_page' )
		);

		add_submenu_page(
			'intercessor',
			__( 'Settings', 'intercessor' ),
			__( 'Settings', 'intercessor' ),
			'manage_prayer_settings',
			'intercessor-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Output a scoped <style> block that sets the praying hands glyph as the
	 * Intercessor admin menu icon.
	 *
	 * WordPress's add_menu_page() icon parameter accepts dashicons, image URLs,
	 * or 'none'. Our custom praying-hands glyph lives in the bundled icon font
	 * (iconfont.css), but that stylesheet is only enqueued on Intercessor pages
	 * and the dashboard. To make the icon appear correctly on every admin page
	 * we inline a self-contained @font-face declaration with absolute URLs (so
	 * the relative ../fonts/ path in iconfont.css is not an issue) plus a
	 * single targeted rule for the menu item's ::before pseudo-element.
	 *
	 * The menu is registered with icon 'none' so WordPress leaves the
	 * wp-menu-image::before content empty — this rule takes over from there.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	/**
	 * Output the praying-hands menu icon style via wp_add_inline_style().
	 *
	 * Attaches a @font-face declaration and the single admin-menu rule to
	 * the 'wp-admin' stylesheet handle (always enqueued in wp-admin) using
	 * the proper wp_add_inline_style() API instead of a raw <style> tag.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function print_menu_icon_style(): void {
		$font_url = esc_url( INTERCESSOR_URL . 'assets/fonts/intercessor' );

		$css = "
			@font-face {
				font-family: 'intercessor';
				src:  url('{$font_url}.eot?mnue68');
				src:  url('{$font_url}.eot?mnue68#iefix') format('embedded-opentype'),
				      url('{$font_url}.ttf?mnue68')       format('truetype'),
				      url('{$font_url}.woff?mnue68')      format('woff'),
				      url('{$font_url}.svg?mnue68#intercessor') format('svg');
				font-weight: normal;
				font-style:  normal;
				font-display: block;
			}
			#adminmenu .toplevel_page_intercessor .wp-menu-image::before {
				font-family: 'intercessor' !important;
				content: '\\e901';
				font-size: 16px;
			}
		";

		wp_add_inline_style( 'wp-admin', $css );
	}

	/**
	 * Enqueue admin stylesheet and script on all Intercessor admin pages
	 * and on the WordPress dashboard (index.php) for the widget.
	 *
	 * @since  1.0.0
	 * @param  string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		$is_intercessor_page = strpos( $hook, 'intercessor' ) !== false;
		$is_dashboard        = 'index.php' === $hook;

		if ( ! $is_intercessor_page && ! $is_dashboard ) {
			return;
		}

		wp_enqueue_style(
			'intercessor-iconfont',
			INTERCESSOR_URL . 'assets/css/iconfont.css',
			array(),
			INTERCESSOR_VERSION
		);

		wp_enqueue_style(
			'intercessor-admin',
			INTERCESSOR_URL . 'assets/css/admin.css',
			array( 'intercessor-iconfont' ),
			INTERCESSOR_VERSION
		);

		wp_enqueue_script(
			'intercessor-admin',
			INTERCESSOR_URL . 'assets/js/admin/admin.js',
			array( 'jquery' ),
			INTERCESSOR_VERSION,
			true
		);

		wp_localize_script(
			'intercessor-admin',
			'intercessorAdmin',
			array(
				'i18n' => array(
					'confirmDelete' => __( 'Permanently delete the selected prayer requests? This cannot be undone.', 'intercessor' ),
				),
			)
		);
	}

	// ── Action handlers ───────────────────────────────────────────────────────

	/**
	 * Resend the email confirmation to a requester's linked WordPress account.
	 *
	 * Generates a fresh token (invalidating the old one) and re-sends the
	 * confirmation email. Redirects back to the requester overview tab.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_resend_confirmation(): void {
		if ( ! current_user_can( 'edit_prayers' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'intercessor' ) );
		}

		check_admin_referer( 'intercessor_resend_confirmation' );

		$requester_id = isset( $_POST['requester_id'] ) ? absint( $_POST['requester_id'] ) : 0;
		$rq_query     = new \Intercessor\Database\Query\Requester_Query();
		$requester    = $rq_query->get_item( $requester_id );
		$redirect     = add_query_arg(
			array( 'page' => 'intercessor-requesters', 'requester_id' => $requester_id, 'tab' => 'overview' ),
			admin_url( 'admin.php' )
		);

		if ( ! $requester || ! $requester->is_linked_to_user() ) {
			wp_safe_redirect( add_query_arg( 'reg_error', '1', $redirect ) );
			exit;
		}

		$user_id = $requester->wp_user_id;

		// Only resend when the account is still pending.
		if ( ! Registration_Handler::is_pending( $user_id ) ) {
			wp_safe_redirect( add_query_arg( 'reg_error', 'already_confirmed', $redirect ) );
			exit;
		}

		$wp_user = get_user_by( 'id', $user_id );
		if ( ! $wp_user ) {
			wp_safe_redirect( add_query_arg( 'reg_error', '1', $redirect ) );
			exit;
		}

		// Generate a fresh token and resend.
		Registration_Handler::resend_confirmation( $user_id, $wp_user->user_email, $requester->get_first_name() );

		wp_safe_redirect( add_query_arg( 'reg_resent', '1', $redirect ) );
		exit;
	}

	/**
	 * Manually confirm a requester's WordPress account without email verification.
	 *
	 * Useful when a confirmation email was not received or the link expired.
	 * Removes the pending flag from user meta directly.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_manual_confirm_account(): void {
		if ( ! current_user_can( 'edit_prayers' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'intercessor' ) );
		}

		check_admin_referer( 'intercessor_manual_confirm_account' );

		$requester_id = isset( $_POST['requester_id'] ) ? absint( $_POST['requester_id'] ) : 0;
		$rq_query     = new \Intercessor\Database\Query\Requester_Query();
		$requester    = $rq_query->get_item( $requester_id );
		$redirect     = add_query_arg(
			array( 'page' => 'intercessor-requesters', 'requester_id' => $requester_id, 'tab' => 'overview' ),
			admin_url( 'admin.php' )
		);

		if ( ! $requester || ! $requester->is_linked_to_user() ) {
			wp_safe_redirect( add_query_arg( 'reg_error', '1', $redirect ) );
			exit;
		}

		$user_id = $requester->wp_user_id;
		delete_user_meta( $user_id, Registration_Handler::META_PENDING );
		delete_user_meta( $user_id, Registration_Handler::META_TOKEN );
		delete_user_meta( $user_id, Registration_Handler::META_EXPIRY );
		delete_user_meta( $user_id, Registration_Handler::META_SOURCE_URL );

		do_action( 'intercessor_email_confirmed', $user_id );

		wp_safe_redirect( add_query_arg( 'reg_confirmed', '1', $redirect ) );
		exit;
	}

	/**
	 * Handle the admin_post_intercessor_delete_requester action.
	 *
	 * Validates capability, nonce, and confirmation checkbox before permanently
	 * deleting a requester and — optionally — all their prayer requests, notes,
	 * history rows, and prayed counts.
	 *
	 * @since  1.0.1
	 * @return void
	 */
	public function handle_delete_requester(): void {
		if ( ! current_user_can( 'manage_prayer_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'intercessor' ) );
		}

		check_admin_referer( 'intercessor_delete_requester' );

		$requester_id    = isset( $_POST['requester_id'] ) ? absint( $_POST['requester_id'] ) : 0;
		$confirm         = ! empty( $_POST['confirm_delete'] );
		$delete_prayers  = ! empty( $_POST['delete_prayers'] );

		if ( $requester_id <= 0 || ! $confirm ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'         => 'intercessor-requesters',
						'requester_id' => $requester_id,
						'tab'          => 'delete',
						'requester_error' => '1',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		$rq_query = new \Intercessor\Database\Query\Requester_Query();
		$requester = $rq_query->get_item( $requester_id );

		if ( ! $requester ) {
			wp_safe_redirect(
				add_query_arg(
					array( 'page' => 'intercessor-requesters', 'requester_error' => '1' ),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		// Optionally delete all associated data first.
		if ( $delete_prayers ) {
			$pr_query   = new \Intercessor\Database\Query\Prayer_Request_Query();
			$note_query = new \Intercessor\Database\Query\Prayer_Note_Query();
			$hist_query = new \Intercessor\Database\Query\Prayer_History_Query();
			$cnt_query  = new \Intercessor\Database\Query\Prayed_Count_Query();

			$prayers = $pr_query->get_items( array( 'requester_id' => $requester_id, 'number' => 0 ) );
			foreach ( $prayers as $prayer ) {
				$note_query->delete_all_for_request( $prayer->id );
				$hist_query->delete_all_for_request( $prayer->id );
				$cnt_query->delete_all_for_request( $prayer->id );
				$pr_query->delete_item( $prayer->id );
			}
		}

		// Always delete requester notes regardless of the delete_prayers flag.
		( new \Intercessor\Database\Query\Requester_Note_Query() )->delete_all_for_requester( $requester_id );

		// Clean up any pending registration meta on the linked WP user.
		if ( $requester->wp_user_id > 0 ) {
			delete_user_meta( $requester->wp_user_id, Registration_Handler::META_PENDING );
			delete_user_meta( $requester->wp_user_id, Registration_Handler::META_TOKEN );
			delete_user_meta( $requester->wp_user_id, Registration_Handler::META_EXPIRY );
			delete_user_meta( $requester->wp_user_id, Registration_Handler::META_SOURCE_URL );
		}

		// Delete the requester row itself.
		$rq_query->delete_item( $requester_id );

		wp_safe_redirect(
			add_query_arg(
				array( 'page' => 'intercessor-requesters', 'requester_deleted' => '1' ),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// ── Page renderers ────────────────────────────────────────────────────────

	/**
	 * Render the Intercessor dashboard overview page.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_dashboard(): void {
		require INTERCESSOR_DIR . 'templates/admin/dashboard.php';
	}

	/**
	 * Render the prayer requests page.
	 *
	 * Branches to the single-request detail view when ?view={id} is present.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_requests_page(): void {
		$viewId = Request::capture()->get_int( 'view' );

		if ( $viewId > 0 ) {
			require INTERCESSOR_DIR . 'templates/admin/request-detail.php';
			return;
		}

		$table = new Prayer_Request_List_Table();
		$table->prepare_items();
		require INTERCESSOR_DIR . 'templates/admin/requests.php';
	}

	/**
	 * Render the requesters page.
	 *
	 * When ?requester_id={id} is present, the template branches to the
	 * single-requester tabbed detail view and resolves its own Requester_View
	 * controller. Without an ID the list table is shown.
	 *
	 * @since  1.0.1
	 * @return void
	 */
	public function render_requesters_page(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requester_id = isset( $_GET['requester_id'] ) ? absint( $_GET['requester_id'] ) : 0;

		if ( $requester_id === 0 ) {
			$table = new Requester_List_Table();
			$table->prepare_items();
		}

		require INTERCESSOR_DIR . 'templates/admin/requesters.php';
	}

	/**
	 * Render the Tools / Exports admin page.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_tools_page(): void {
		( new Tools_Admin_Page() )->render();
	}

	/**
	 * Render the settings page using the shared DisplayPage instance.
	 *
	 * Uses the same $displayPage instance created in register() so that the
	 * Renderer — which is populated during registerSettings() — is always
	 * available when this callback fires. Instantiating a fresh DisplayPage
	 * here would leave $renderer uninitialised and cause a fatal error.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_settings_page(): void {
		$this->displayPage->render();
	}
}
