<?php
/**
 * Admin template: single-requester tabbed detail view.
 *
 * Included by templates/admin/requesters.php when ?requester_id={id} is
 * present and a valid Requester_View instance has been resolved.
 *
 * Variables provided by requesters.php:
 *
 * @var \Intercessor\Admin\Requester_View $view  Resolved view controller.
 *
 * @package Intercessor
 * @since   1.0.0
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-scoped variable passed from parent template.

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$requester  = $view->get_requester();
$active_tab = $view->get_active_tab();
$tabs       = $view->get_tabs();
$back_url   = admin_url( 'admin.php?page=intercessor-requesters' );
?>
<div class="wrap intercessor-requester-detail">

	<h1 class="wp-heading-inline">
		<?php
		echo esc_html(
			sprintf(
				/* translators: %s: Requester display name */
				__( 'Requester: %s', 'intercessor' ),
				$requester->name ?: __( '(No name)', 'intercessor' )
			)
		);
		?>
	</h1>

	<a href="<?php echo esc_url( $back_url ); ?>" class="page-title-action">
		&larr; <?php esc_html_e( 'Back to Requesters', 'intercessor' ); ?>
	</a>

	<hr class="wp-header-end">

	<?php // ── Tab navigation ─────────────────────────────────────────────── ?>
	<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Requester detail tabs', 'intercessor' ); ?>">
		<?php foreach ( $tabs as $slug => $meta ) : ?>
			<a
				href="<?php echo esc_url( $view->tab_url( $slug ) ); ?>"
				class="nav-tab <?php echo $slug === $active_tab ? 'nav-tab-active' : ''; ?>"
				<?php echo $slug === $active_tab ? 'aria-current="page"' : ''; ?>
			>
				<?php if ( ! empty( $meta['dashicon'] ) ) : ?>
					<span class="dashicons <?php echo esc_attr( $meta['dashicon'] ); ?>" aria-hidden="true"></span>
				<?php endif; ?>
				<?php echo esc_html( $meta['label'] ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php // ── Tab content ────────────────────────────────────────────────── ?>
	<div class="intercessor-tab-content">
		<?php $view->render_tab_content(); ?>
	</div>

</div>
