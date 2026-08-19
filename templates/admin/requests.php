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

	<button type="button" class="page-title-action" id="intercessor-add-request-btn">
		<?php esc_html_e( '+ Add Prayer Request', 'intercessor' ); ?>
	</button>

	<hr class="wp-header-end">

	<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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

<?php if ( isset( $_GET['added'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
<script>
document.addEventListener( 'DOMContentLoaded', function () {
	var n = document.createElement( 'div' );
	n.className = 'notice notice-success is-dismissible';
	n.innerHTML = '<p><?php echo esc_js( __( 'Prayer request added successfully.', 'intercessor' ) ); ?></p>';
	var h = document.querySelector( '.wp-header-end' );
	if ( h ) h.after( n );
} );
</script>
<?php endif; ?>

<?php // ── Add Prayer Request modal ──────────────────────────────────────── ?>
<div id="intercessor-add-modal" class="intercessor-modal" role="dialog" aria-modal="true" aria-labelledby="intercessor-modal-title" hidden>
	<div class="intercessor-modal__backdrop" id="intercessor-modal-backdrop"></div>
	<div class="intercessor-modal__box">

		<div class="intercessor-modal__header">
			<h2 class="intercessor-modal__title" id="intercessor-modal-title">
				<?php esc_html_e( 'Add Prayer Request', 'intercessor' ); ?>
			</h2>
			<button type="button" class="intercessor-modal__close" id="intercessor-modal-close" aria-label="<?php esc_attr_e( 'Close', 'intercessor' ); ?>">&#10005;</button>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="intercessor-modal__form">
			<input type="hidden" name="action" value="intercessor_admin_add_request">
			<?php wp_nonce_field( 'intercessor_admin_add_request' ); ?>

			<div class="intercessor-modal__body">

				<?php // ── For ──────────────────────────────────────────── ?>
				<fieldset class="intercessor-modal__fieldset">
					<legend class="intercessor-modal__label"><?php esc_html_e( 'This request is for', 'intercessor' ); ?></legend>
					<div class="intercessor-modal__radio-group">
						<label class="intercessor-modal__radio-label">
							<input type="radio" name="for_type" value="self" checked>
							<?php esc_html_e( 'Myself', 'intercessor' ); ?>
						</label>
						<label class="intercessor-modal__radio-label">
							<input type="radio" name="for_type" value="other">
							<?php esc_html_e( 'Someone else', 'intercessor' ); ?>
						</label>
					</div>
				</fieldset>

				<?php // ── Name + Email (shown for "someone else") ─────── ?>
				<div id="intercessor-modal-other-fields" class="intercessor-modal__other-fields" hidden>
					<div class="intercessor-modal__row intercessor-modal__row--two">
						<div class="intercessor-modal__field">
							<label class="intercessor-modal__label" for="ipr-add-first-name">
								<?php esc_html_e( 'First Name', 'intercessor' ); ?>
							</label>
							<input type="text" id="ipr-add-first-name" name="first_name" class="regular-text">
						</div>
						<div class="intercessor-modal__field">
							<label class="intercessor-modal__label" for="ipr-add-last-name">
								<?php esc_html_e( 'Last Name', 'intercessor' ); ?>
							</label>
							<input type="text" id="ipr-add-last-name" name="last_name" class="regular-text">
						</div>
					</div>
					<div class="intercessor-modal__field">
						<label class="intercessor-modal__label" for="ipr-add-email">
							<?php esc_html_e( 'Email', 'intercessor' ); ?>
							<span class="intercessor-modal__required" aria-hidden="true">*</span>
						</label>
						<input type="email" id="ipr-add-email" name="email" class="regular-text" autocomplete="off">
					</div>
				</div>

				<?php // ── Subject ──────────────────────────────────────── ?>
				<div class="intercessor-modal__field">
					<label class="intercessor-modal__label" for="ipr-add-subject">
						<?php esc_html_e( 'Subject', 'intercessor' ); ?>
						<span class="intercessor-modal__required" aria-hidden="true">*</span>
					</label>
					<input type="text" id="ipr-add-subject" name="subject" class="large-text" required>
				</div>

				<?php // ── Prayer Request ───────────────────────────────── ?>
				<div class="intercessor-modal__field">
					<label class="intercessor-modal__label" for="ipr-add-content">
						<?php esc_html_e( 'Prayer Request', 'intercessor' ); ?>
						<span class="intercessor-modal__required" aria-hidden="true">*</span>
					</label>
					<textarea id="ipr-add-content" name="content" rows="5" class="large-text" required></textarea>
				</div>

				<?php // ── Options row ──────────────────────────────────── ?>
				<div class="intercessor-modal__row intercessor-modal__row--two">
					<div class="intercessor-modal__field">
						<label class="intercessor-modal__label" for="ipr-add-status">
							<?php esc_html_e( 'Status', 'intercessor' ); ?>
						</label>
						<select id="ipr-add-status" name="status" class="regular-text">
							<option value="pending"><?php esc_html_e( 'Pending', 'intercessor' ); ?></option>
							<option value="approved"><?php esc_html_e( 'Approved', 'intercessor' ); ?></option>
							<option value="private"><?php esc_html_e( 'Private', 'intercessor' ); ?></option>
						</select>
					</div>
					<div class="intercessor-modal__field intercessor-modal__field--checkbox">
						<label class="intercessor-modal__checkbox-label">
							<input type="checkbox" name="is_anonymous" value="1">
							<?php esc_html_e( 'Anonymous', 'intercessor' ); ?>
						</label>
						<p class="intercessor-modal__hint">
							<?php esc_html_e( 'Hide requester name on the Prayer Wall.', 'intercessor' ); ?>
						</p>
					</div>
				</div>

			</div>

			<div class="intercessor-modal__footer">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Add Request', 'intercessor' ); ?>
				</button>
				<button type="button" class="button" id="intercessor-modal-cancel">
					<?php esc_html_e( 'Cancel', 'intercessor' ); ?>
				</button>
			</div>

		</form>
	</div>
</div>

