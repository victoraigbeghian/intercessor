<?php
/**
 * Intercessor Requester display page functions and actions.
 *
 * @package     Intercessor
 * @subpackage  Admin/Requesters
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       0.9.5
 */

use Intercessor\Admin\Requesters\Table;
use Intercessor\Requester;
use Intercessor\Html;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Requesters Page
 *
 * Renders the Requesters page contents.
 *
 * @since  0.9.5
 * @return void
 */
function intercessor_requesters_page() {
	// Enqueue scripts.
	wp_enqueue_script( 'intercessor-requesters' );
	wp_enqueue_script( 'intercessor-admin-prayers' );

	// Views.
	$default_views  = intercessor_requester_views();
	$requested_view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'requesters';

	if ( array_key_exists( $requested_view, $default_views ) && is_callable( $default_views[ $requested_view ] ) ) {
		intercessor_render_requester_view( $requested_view, $default_views );
	} else {
		intercessor_requesters_list();
	}
}

/**
 * Register the views for requester management
 *
 * @since  0.9.5
 * @return array Array of views and their callbacks
 */
function intercessor_requester_views() {
	return apply_filters( 'intercessor_requester_views', [] );
}

/**
 * Register the tabs for requester management
 *
 * @since  0.9.5
 * @return array Array of tabs for the requester
 */
function intercessor_requester_tabs() {
	return apply_filters( 'intercessor_requester_tabs', [] );
}

/**
 * List table of Requesters
 *
 * @since  0.9.5
 * @return void
 */
function intercessor_requesters_list() {

	// Enqueue scripts.
	wp_enqueue_script( 'intercessor-requesters' );

	$requesters_table = new Table();
	$requesters_table->prepare_items();
	?>

    <div class="wrap">
        <h1 class="wp-heading-inline">
			<?php esc_html_e( 'Intercessor Requesters', 'intercessor' ); ?>
		</h1>

		<hr class="wp-header-end">

		<?php do_action( 'intercessor_requesters_table_top' ); ?>

		<form id="intercessor-requesters-filter" method="get" action="<?php echo admin_url( 'admin.php?page=intercessor-requesters' ); ?>">
			<?php
			$requesters_table->search_box( esc_html__( 'Search Requesters', 'intercessor' ), 'intercessor-requesters' );
			$requesters_table->display();
			?>
			<input type="hidden" name="page" value="intercessor-requesters" />
			<input type="hidden" name="view" value="requesters" />
		</form>

		<?php do_action( 'intercessor_requesters_table_bottom' ); ?>

    </div>

	<?php
}

/**
 * Renders the requester view wrapper
 *
 * @since  0.9.5
 * @param string $view      The View being requested.
 * @param array  $callbacks The Registered views and their callback functions.
 * @return void
 */
function intercessor_render_requester_view( $view, $callbacks ) {
	
	$render = true;

	$requester_view_role = apply_filters( 'intercessor_view_requesters_role', 'view_prayer_reports' );

	if ( ! current_user_can( $requester_view_role ) ) {
		intercessor_set_error( 'intercessor_no_access', esc_html__( 'You are not permitted to view this data.', 'intercessor' ) );
		$render = false;
	}

	if ( ! isset( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
		intercessor_set_error( 'intercessor_invalid_requester', esc_html__( 'Invalid Requester ID Provided.', 'intercessor' ) );
		$render = false;
	}

	$requester_id = (int)$_GET['id'];
	$requester    = intercessor_get_requester( $requester_id );

	if ( empty( $requester->id ) ) {
		intercessor_set_error(
			'intercessor_invalid_requester',
			esc_html__( 'Invalid Requester ID Provided.', 'intercessor' )
		);
		$render = false;
	}

	$requester_tabs = intercessor_requester_tabs(); ?>

    <div class='wrap'>
        <h2>
			<?php esc_html_e( 'Requester Details', 'intercessor' ); ?>
			<?php do_action( 'intercessor_after_requester_details_header', $requester ); ?>
        </h2>

		<?php
		if ( intercessor_get_errors() ) :
		?>
            <div class="error settings-error">
				<?php intercessor_print_errors(); ?>
            </div>
		<?php endif; ?>

		<?php if ( $requester && $render ) : ?>

            <div id="intercessor-item-wrapper" class="intercessor-item-has-tabs intercessor-clearfix">
                <div id="intercessor-item-tab-wrapper" class="requester-tab-wrapper">
                    <ul id="intercessor-item-tab-wrapper-list" class="requester-tab-wrapper-list">
						<?php foreach ( $requester_tabs as $key => $tab ) : ?>
							<?php $active = $key === $view ? true : false; ?>
							<?php $class  = $active ? 'active' : 'inactive'; ?>

                            <li class="<?php echo sanitize_html_class( $class ); ?>">

								<?php

								// prevent double "Requester" output from extensions.
								$tab['title'] = preg_replace( "(^Requester )", "", $tab['title'] );

								// ipr item tab full title.
								$tab_title = sprintf( _x( 'Requester %s', 'Requester Details page tab title', 'intercessor' ), esc_attr( $tab[ 'title' ] ) );

								// aria-label output
								$aria_label = ' aria-label="' . $tab_title . '"';
								?>

								<?php if ( ! $active ) : ?>

                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=intercessor-requesters&view=' . $key . '&id=' . $requester->id ) ); ?>"<?php echo $aria_label; ?>>

									<?php endif; ?>

                                    <span class="intercessor-item-tab-label-wrap"<?php echo $active ? $aria_label : ''; ?>>
										<span class="dashicons <?php echo sanitize_html_class( $tab['dashicon'] ); ?>" aria-hidden="true"></span>
										<span class="intercessor-item-tab-label"><?php echo esc_attr( $tab['title'] ); ?></span>
									</span>

									<?php if ( ! $active ) : ?>
                                </a>
							<?php endif; ?>

                            </li>

						<?php endforeach; ?>
                    </ul>
                </div>

                <div id="intercessor-item-card-wrapper" class="intercessor-requester-card-wrapper">
					<?php call_user_func( $callbacks[ $view ], $requester ); ?>
                </div>
            </div>

		<?php endif; ?>

    </div>
	<?php

}

/**
 * View a requester profile
 *
 * @since  0.9.5
 * @param object \Intercessor\Requester $requester The Requester object being displayed.
 */
function intercessor_requesters_view( $requester ) {
    // Check if user can view requester details.
	$requester_edit_role = apply_filters( 'intercessor_view_requesters_role', 'view_prayer_reports' );

	do_action( 'intercessor_requester_card_top', $requester ); ?>

    <div class="info-wrapper requester-section">
        <form id="edit-requester-info" method="post" action="<?php echo admin_url( 'admin.php?page=intercessor-requesters&view=overview&id=' . $requester->id ); ?>">
            <div class="intercessor-item-info requester-info">
                <div class="avatar-wrap left" id="requester-avatar">
					<?php echo get_avatar( $requester->email ); ?><br />
					<?php if ( current_user_can( $requester_edit_role ) ): ?>
                        <span class="info-item editable requester-edit-link">
							<a href="#" id="edit-requester"><?php esc_html_e( 'Edit Requester', 'intercessor' ); ?></a>
						</span>
						<?php do_action( 'intercessor_after_requester_edit_link', $requester ); ?>
					<?php endif; ?>
                </div>

                <div class="requester-id right">
                    #<?php echo esc_html( $requester->id ); ?>
                </div>

                <div class="requester-main-wrapper left">
					<span class="requester-name info-item edit-item">
						<input size="15" data-key="name" name="requesterinfo[name]" type="text" value="<?php echo esc_attr( $requester->name ); ?>" placeholder="<?php esc_html_e( 'Requester Name', 'intercessor' ); ?>" />
					</span>
                    <span class="requester-name info-item editable" data-key="name">
						<?php echo esc_html( $requester->name ); ?>
					</span>

                    <span class="requester-email info-item edit-item">
						<input size="20" data-key="email" name="requesterinfo[email]" type="text" value="<?php echo esc_attr( $requester->email ); ?>" placeholder="<?php esc_html_e( 'Requester Email', 'intercessor' ); ?>" />
					</span>
                    <span class="requester-email info-item editable" data-key="email">
						<?php echo esc_html( $requester->email ); ?>
					</span>
                    <span class="requester-date-created info-item edit-item">
						<input size="" data-key="date_created" name="requesterinfo[date_created]" type="text" value="<?php echo esc_attr( $requester->date_created ); ?>" placeholder="<?php esc_html_e( 'Requester Since', 'intercessor' ); ?>" class="intercessor_datepicker" />
					</span>
                    <span class="requester-since info-item editable">
						<?php
	                    printf(
		                    /* translators: The date. */
		                    esc_html__( 'Requester since %s', 'intercessor' ),
                            esc_html( intercessor_date_i18n(
								strtotime( $requester->date_created ), 'date'
							) )
                        );
	                    ?>
					</span>
                    <span class="requester-user-id info-item edit-item">
						<?php

						$user_id    = $requester->user_id > 0 ? $requester->user_id : '';
						$data_atts  = [
                            'key' 	  => 'user_login',
                            'exclude' => $user_id,
                        ];
						$user_args  = [
                            'name'    => 'requesterinfo[user_login]',
                            'class'   => 'intercessor-user-dropdown',
                            'data'    => $data_atts,
                        ];

						$userdata = false;
						if ( ! empty( $user_id ) ) {
							$userdata = get_userdata( $user_id );
							$user_args['value'] = $userdata->user_login;
						}

						$html = new Html();
						echo $html->ajax_user_search( $user_args );
						?>
                        <input type="hidden" name="requesterinfo[user_id]" data-key="user_id" value="<?php echo esc_attr( $requester->user_id ); ?>" />
					</span>
                    <span class="requester-user-id info-item editable">
						<?php if ( intval( $requester->user_id ) > 0 ) : ?>
                            <span data-key="user_id">
								<a href="<?php echo admin_url( 'user-edit.php?user_id=' . $requester->user_id ); ?>"><?php echo esc_html( $userdata->user_login ); ?></a>
							</span>
						<?php else : ?>
                            <span data-key="user_id">
								<?php esc_html_e( 'Not a registered user', 'intercessor' ); ?>
							</span>
						<?php endif; ?>

						<?php if ( current_user_can( $requester_edit_role ) && intval( $requester->user_id ) > 0 ) : ?>
                            <span class="disconnect-user">
								<a id="disconnect-requester" href="#disconnect" class="dashicons dashicons-editor-unlink"></a>
							</span>
						<?php endif; ?>
					</span>
                </div>
            </div>

            <span id="requester-edit-actions" class="edit-item">
				<input type="hidden" data-key="id" name="requesterinfo[id]" value="<?php echo esc_html( $requester->id ); ?>" />
				<?php wp_nonce_field( 'edit-requester', '_wpnonce', false, true ); ?>
                <input type="hidden" name="intercessor_action" value="edit-requester" />
				<button id="intercessor-edit-requester-save" class="button button-secondary"><?php esc_html_e( 'Update Requester', 'intercessor' ); ?></button>
				<a id="intercessor-edit-requester-cancel" href="" class="cancel"><?php esc_html_e( 'Cancel', 'intercessor' ); ?></a>
			</span>
        </form>
    </div>

	<?php do_action( 'intercessor_requester_before_stats', $requester ); ?>

    <div id="intercessor-item-stats-wrapper" class="requester-stats-wrapper requester-section">
        <ul>
            <li>
                <a href="<?php echo admin_url( 'admin.php?page=intercessor-prayers&requester=' . $requester->id ); ?>">
                    <span class="intercessor-icon-fire"></span>
					<?php printf( _n( '%d Prayer Request', '%d Prayer Requests', $requester->prayer_count, 'intercessor' ), $requester->prayer_count ); ?>
                </a>
            </li>
			<?php do_action( 'intercessor_requester_stats_list', $requester ); ?>
        </ul>
    </div>


	<?php do_action( 'intercessor_requester_before_agreements', $requester );

		$terms_dates   = intercessor_get_item_meta( 'requester', $requester->id, 'agreed_to_terms', false );
		$privacy_dates = intercessor_get_item_meta( 'requester', $requester->id, 'agreed_to_privacy', false );

		$prayers = intercessor_get_prayers(
			array(
				'output'    => 'prayers',
				'prayer_in' => explode( ',', $requester->prayer_ids ),
				'orderby'   => 'date',
				'number'    => 1,
			)
		);

	if ( is_array( $terms_dates ) ) {
		$terms_agreed = array_pop( $terms_dates );
	}

	if ( is_array( $privacy_dates ) ) {
		$privacy_agreed = array_pop( $privacy_dates );
	}
	?>

	<div id="intercessor-item-agreements-wrapper" class="requester-agreements-wrapper requester-section">
		<h3><?php esc_html_e( 'Agreements', 'intercessor' ); ?></h3>
		<p class="requester-terms-agreement-date info-item">
			<?php if ( ! empty( $terms_agreed ) ) {
				echo date_i18n( get_option( 'date_format' ) . ' H:i:s', $terms_agreed );
				esc_html_e( ' &mdash; Agreed to Terms', 'intercessor' );

				if ( ! empty( $terms_dates ) ) : ?>

					<span alt="f223" class="intercessor-help-tip dashicons dashicons-editor-help" title="<strong><?php esc_html_e( 'Previous Agreement Dates', 'intercessor' ); ?></strong><br /><?php foreach ( $terms_dates as $timestamp ) { echo date_i18n( get_option( 'date_format' ) . ' H:i:s', $timestamp ); } ?>"></span>

				<?php endif;

			} else {
				esc_html_e( 'No terms agreement found.', 'intercessor' );
			}
			?>
		</p>

		<p class="requester-privacy-policy-date info-item">
			<?php if ( ! empty( $privacy_agreed ) ) {
				echo date_i18n( get_option( 'date_format' ) . ' H:i:s', $privacy_agreed );
				esc_html_e( ' &mdash; Agreed to Privacy Policy', 'intercessor' );

				if ( ! empty( $privacy_dates ) ) : ?>

					<span alt="f223" class="intercessor-help-tip dashicons dashicons-editor-help" title="<strong><?php esc_html_e( 'Previous Agreement Dates', 'intercessor' ); ?></strong><br /><?php foreach ( $privacy_dates as $timestamp ) { echo date_i18n( get_option( 'date_format' ) . ' H:i:s', $timestamp ); } ?>"></span>

				<?php endif;

			} else {
				esc_html_e( 'No privacy policy agreement found.', 'intercessor' );
			}
			?>
		</p>
	</div>

	<?php do_action( 'intercessor_requester_before_tables_wrapper', $requester ); ?>

    <div id="intercessor-item-tables-wrapper" class="requester-tables-wrapper requester-section">

		<?php do_action( 'intercessor_requester_before_tables', $requester ); ?>

        <h3>
			<?php esc_html_e( 'Requester Emails', 'intercessor' ); ?>
            <span alt="f223" class="intercessor-help-tip dashicons dashicons-editor-help" title="<?php esc_html_e( 'This requester can use any of the emails listed here when making new prayers.', 'intercessor' ); ?>"></span>
        </h3>
		<?php

		// Setup requester emails view
		$all_emails = array( 'primary' => $requester->email );
		foreach ( $requester->emails as $key => $email ) {
			if ( $requester->email === $email ) {
				continue;
			}

			$all_emails[ $key ] = $email;
		}
		?>
        <table class="wp-list-table widefat striped emails">
            <thead>
            <tr>
                <th><?php esc_html_e( 'Email',   'intercessor' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'intercessor' ); ?></th>
            </tr>
            </thead>
            <tbody>
			<?php if ( ! empty( $all_emails ) ) : ?>

				<?php foreach ( $all_emails as $key => $email ) : ?>

                    <tr data-key="<?php echo esc_attr( $key ); ?>">
                        <td>
							<?php echo esc_html( $email ); ?>
							<?php if ( 'primary' === $key ) : ?>
                                <span class="dashicons dashicons-star-filled primary-email-icon"></span>
							<?php endif; ?>
                        </td>
                        <td>
							<?php if ( 'primary' !== $key ) : ?>
								<?php
								$base_url    = admin_url( 'admin.php?page=intercessor-requesters&view=overview&id=' . $requester->id );
								$promote_url = wp_nonce_url( add_query_arg( array( 'email' => esc_html( $email ), 'intercessor_action' => 'requester-primary-email' ), $base_url ), 'intercessor-set-requester-primary-email' );
								$remove_url  = wp_nonce_url( add_query_arg( array( 'email' => esc_html( $email ), 'intercessor_action' => 'requester-remove-email' ), $base_url ), 'intercessor-remove-requester-email' );
								?>
                                <a href="<?php echo esc_url( $promote_url ); ?>"><?php esc_html_e( 'Make Primary', 'intercessor' ); ?></a>
                                &nbsp;|&nbsp;
                                <a href="<?php echo esc_url( $remove_url ); ?>" class="delete"><?php esc_html_e( 'Remove', 'intercessor' ); ?></a>
							<?php endif; ?>
                        </td>
                    </tr>

				<?php endforeach; ?>

                <tr class="add-requester-email-row">
                    <td colspan="2" class="add-requester-email-td">
                        <div class="add-requester-email-wrapper">
                            <input type="hidden" name="requester-id" value="<?php echo esc_attr( $requester->id ); ?>" />
							<?php wp_nonce_field( 'intercessor-add-requester-email', 'add_email_nonce', false, true ); ?>
                            <input type="email" name="additional-email" value="" placeholder="<?php esc_html_e( 'Email Address', 'intercessor' ); ?>" />&nbsp;
                            <input type="checkbox" name="make-additional-primary" value="1" id="make-additional-primary" />&nbsp;<label for="make-additional-primary"><?php esc_html_e( 'Make Primary', 'intercessor' ); ?></label>
                            <button class="button-secondary intercessor-add-requester-email" id="add-requester-email" style="margin: 6px 0;"><?php esc_html_e( 'Add Email', 'intercessor' ); ?></button>
                            <span class="spinner"></span>
                        </div>
                        <div class="notice-wrap"></div>
                    </td>
                </tr>

			<?php else: ?>

                <tr><td colspan="2"><?php esc_html_e( 'No Emails Found', 'intercessor' ); ?></td></tr>

			<?php endif; ?>
            </tbody>
        </table>

        <h3><?php esc_html_e( 'Recent Prayer Requests', 'intercessor' ); ?></h3>
		<?php
		$prayer_ids = $requester->get_prayer_ids();
		$prayers    = intercessor_get_items(
			'prayer',
			array( 'id__in' => $prayer_ids )
		);
		$prayers    = array_slice( $prayers, 0, 10 );
		?>
        <table class="wp-list-table widefat striped prayers">
            <thead>
            <tr>
                <th><?php esc_html_e( 'ID', 'intercessor' ); ?></th>
				<th><?php esc_html_e( 'Title', 'intercessor' ); ?></th>
				<th><?php esc_html_e( 'Counts', 'intercessor' ); ?></th>
				<th><?php esc_html_e( 'Date', 'intercessor' ); ?></th>
				<th><?php esc_html_e( 'Status', 'intercessor' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'intercessor' ); ?></th>
            </tr>
            </thead>
            <tbody>
			<?php if ( ! empty( $prayers ) ) : ?>
				<?php
				foreach ( $prayers as $prayer ) :
					$prayed_args = [
						'date_created_query' => false,
						'prayer_id'          => $prayer->id,
					];
					$prayed_text = intercessor_count_prayed( $prayed_args );
					?>
                    <tr>
                        <td><?php echo $prayer->id; ?></td>
                        <td><?php echo esc_html( stripslashes( $prayer->title ) ); ?></td>
                        <td><?php echo esc_attr( $prayed_text ); ?></td>
						<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $prayer->date_created ) ); ?></td>
                        <td><?php echo intercessor_get_prayer_status( $prayer->id ); ?></td>
                        <td>
                            <a href="<?php echo admin_url( 'admin.php?page=intercessor-prayers&intercessor-action=view_request_details&prayer=' . $prayer->id ); ?>">

								<?php esc_html_e( 'View Details', 'intercessor' ); ?>
                            </a>
							<?php
								/**
								 * Fires in the screen by the requester recent prayers.
								 *
								 * @since  0.9.5
								 */
								do_action( 'intercessor_requester_recent_prayers_actions', $requester, $prayer ); ?>
                        </td>
                    </tr>
				<?php endforeach; ?>
			<?php else: ?>
                <tr><td colspan="5"><?php esc_html_e( 'No Prayer Request Found', 'intercessor' ); ?></td></tr>
			<?php endif; ?>
            </tbody>
        </table>

    </div>

	<?php do_action( 'intercessor_requester_card_bottom', $requester );
}

/**
 * View the notes section of a requester
 *
 * @since  0.9.5
 * @param object $requester The Requester being displayed
 * @return void
 */
function intercessor_requester_notes_view( $requester ) {

	$paged      = ! empty( $_GET['paged'] ) && is_numeric( $_GET['paged'] )
		? absint( $_GET['paged'] )
		: 1;

	$per_page   = apply_filters( 'intercessor_requester_notes_per_page', 20 );
	$notes      = $requester->get_notes( $per_page, $paged );
	$note_count = $requester->get_notes_count();
	$args       = array(
		'total'        => $note_count,
		'add_fragment' => '#intercessor_requester_notes'
	);
	?>

    <div id="intercessor-item-notes-wrapper">
        <div class="intercessor-item-header-small">
			<?php echo get_avatar( $requester->email, 30 ); ?> <span><?php echo esc_html( $requester->name ); ?></span>
        </div>
        <h3><?php esc_html_e( 'Notes', 'intercessor' ); ?></h3>

		<?php echo intercessor_admin_get_notes_pagination( $args ); ?>

        <div id="intercessor-requester-notes">
			<?php echo intercessor_admin_get_notes_html( $notes ); ?>
			<?php echo intercessor_admin_get_new_note_form( $requester->id, 'requester' ); ?>
        </div>

		<?php echo intercessor_admin_get_notes_pagination( $args ); ?>
    </div>

	<?php
}

/**
 * View the delete section of a requester
 *
 * @since  0.9.5
 * @param object $requester The Requester being displayed
 * @return void
 */
function intercessor_requesters_delete_view( $requester ) {
    $html = new Html();
    /**
     * Fires before a requester is deleted.
     *
     * @param object $requester Requester object.
     * @since 1.0.0
     */
	do_action( 'intercessor_requester_delete_top', $requester );
	?>

    <div class="info-wrapper requester-section">

        <form id="delete-requester" method="post" action="<?php echo admin_url( 'admin.php?page=intercessor-requesters&view=delete&id=' . $requester->id ); ?>">

            <div class="intercessor-item-header-small">
				<?php echo get_avatar( $requester->email, 30 ); ?> <span><?php echo $requester->name; ?></span>
            </div>

            <h3><?php esc_html_e( 'Delete', 'intercessor' ); ?></h3>

            <div class="requester-info delete-requester">
				<span class="delete-requester-options">
					<p>
						<?php echo $html->checkbox(
							[
								'name' => 'intercessor-requester-delete-confirm',
								'id'   => 'intercessor-requester-delete-confirm',
							]
							);
							?>
                        <label for="intercessor-requester-delete-confirm"><?php esc_html_e( 'Are you sure you want to delete this requester?', 'intercessor' ); ?></label>
					</p>

					<p>
						<?php echo $html->checkbox(
							[
								'name'    => 'intercessor-requester-delete-records',
								'id'      => 'intercessor-requester-delete-records',
								'options' => [ 'disabled' => true ]
							]
						); ?>
                        <label for="intercessor-requester-delete-records"><?php esc_html_e( 'Delete all associated prayers and records?', 'intercessor' ); ?></label>
					</p>

					<?php do_action( 'intercessor_requester_delete_inputs', $requester ); ?>
				</span>

                <span id="requester-edit-actions">
					<input type="hidden" name="requester_id" value="<?php echo $requester->id; ?>" />
					<?php wp_nonce_field( 'delete-requester', '_wpnonce', false, true ); ?>
                    <input type="hidden" name="intercessor_action" value="delete_requester" />
					<input type="submit" disabled="disabled" id="intercessor-delete-requester" class="button-primary" value="<?php esc_html_e( 'Delete Requester', 'intercessor' ); ?>" />
					<a id="intercessor-delete-requester-cancel" href="<?php echo admin_url( 'admin.php?page=intercessor-requesters&view=overview&id=' . $requester->id ); ?>" class="delete"><?php esc_html_e( 'Cancel', 'intercessor' ); ?></a>
				</span>
            </div>
        </form>
    </div>

	<?php

	do_action( 'intercessor_requester_delete_bottom', $requester );
}

/**
 * View the tools section of a requester
 *
 * @since  0.9.5
 * @param object $requester The Requester being displayed
 * @return void
 */
function intercessor_requester_tools_view( $requester ) {

	do_action( 'intercessor_requester_tools_top', $requester ); ?>

    <div id="intercessor-item-tools-wrapper">
        <div class="intercessor-item-header-small">
			<?php echo get_avatar( $requester->email, 30 ); ?> <span><?php echo $requester->name; ?></span>
        </div>

        <h3><?php esc_html_e( 'Tools', 'intercessor' ); ?></h3>

        <div class="intercessor-item-info requester-info">
            <h4><?php esc_html_e( 'Recount Requester Stats', 'intercessor' ); ?></h4>
            <p class="intercessor-item-description"><?php esc_html_e( 'Use this tool to recalculate the prayer counts of the requester.', 'intercessor' ); ?></p>
            <form method="post" id="intercessor-tools-recount-form" class="intercessor-export-form intercessor-import-export-form">
				<span>
					<?php wp_nonce_field( 'intercessor_ajax_export', 'intercessor_ajax_export' ); ?>

                    <input type="hidden" name="intercessor-export-class" data-type="recount-single-requester-stats" value="\Intercessor\Admin\Tools\Recount_Requester_Stats" />
					<input type="hidden" name="requester_id" value="<?php echo $requester->id; ?>" />
					<input type="submit" id="recount-stats-submit" value="<?php esc_html_e( 'Recount Stats', 'intercessor' ); ?>" class="button-secondary"/>
					<span class="spinner"></span>
				</span>
            </form>
        </div>
    </div>

	<?php

	do_action( 'intercessor_requester_tools_bottom', $requester );
}
