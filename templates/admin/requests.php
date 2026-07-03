<?php
/**
 * Admin template: prayer requests list table.
 *
 * Rendered by Admin_Loader::render_requests_page() when no 'view' param is set.
 * $table is prepared before this template is included.
 *
 * The form POSTs to admin-post.php. The 'action' field value must match the
 * registered admin_post_{action} hook (intercessor_bulk_action). Individual
 * approve/reject buttons use their own inline forms pointing to the same URL
 * with action=intercessor_moderate and bypass the bulk nonce entirely.
 *
 * @var \Intercessor\Admin\Prayer_Request_List_Table $table
 *
 * @package Intercessor
 * @since   1.0.0
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-scoped variables included via require, not true globals

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">

	<h1 class="wp-heading-inline"><?php esc_html_e( 'Prayer Requests', 'intercessor' ); ?></h1>
	<hr class="wp-header-end">

	<?php
	// ── Flash notices ────────────────────────────────────────────────────────

	// Single-row moderation (approve / reject from inline buttons or detail view).
	if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Request updated.', 'intercessor' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'An error occurred. Please try again.', 'intercessor' ); ?></p>
		</div>
	<?php endif; ?>

	<?php
	// Bulk status update notice.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['bulk_updated'] ) ) :
		$bulkCount  = absint( $_GET['bulk_updated'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$bulkStatus = isset( $_GET['bulk_status'] ) ? sanitize_key( $_GET['bulk_status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$label      = $bulkStatus === 'approved' ? __( 'approved', 'intercessor' ) : __( 'rejected', 'intercessor' );
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: count of requests, 2: status label (approved/rejected) */
						_n(
							'%1$d request %2$s.',
							'%1$d requests %2$s.',
							$bulkCount,
							'intercessor'
						),
						$bulkCount,
						$label
					)
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php
	// Bulk delete notice.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['bulk_deleted'] ) ) :
		$deleted = absint( $_GET['bulk_deleted'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of deleted requests */
						_n(
							'%d request permanently deleted.',
							'%d requests permanently deleted.',
							$deleted,
							'intercessor'
						),
						$deleted
					)
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php
	// Bulk error notices.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['bulk_error'] ) ) :
		$bulkError = sanitize_key( $_GET['bulk_error'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$errorMsg  = $bulkError === 'no_selection'
			? esc_html__( 'Please select at least one request.', 'intercessor' )
			: esc_html__( 'An error occurred with the bulk action.', 'intercessor' );
		?>
		<div class="notice notice-warning is-dismissible">
			<p><?php echo esc_html( $errorMsg ); ?></p>
		</div>
	<?php endif; ?>

	<?php
	/*
	 * The list table form must POST to admin-post.php so the
	 * admin_post_intercessor_bulk_action hook fires before any output.
	 * We embed a hidden 'action' field and a nonce for the bulk handler,
	 * plus a hidden 'page' field so the redirect goes back here.
	 *
	 * WP_List_Table::display() renders the top and bottom bulk-action
	 * dropdowns, checkboxes, and the table rows. The search box is rendered
	 * separately before display() so it sits above the view links.
	 */
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

		<input type="hidden" name="action"      value="intercessor_bulk_action">
		<input type="hidden" name="page"        value="intercessor-requests">
		<?php wp_nonce_field( 'intercessor_bulk_action' ); ?>

		<?php $table->search_box( esc_html__( 'Search', 'intercessor' ), 'intercessor-search' ); ?>

		<?php $table->views(); ?>
		
		<?php $table->display(); ?>

	</form>

</div>
