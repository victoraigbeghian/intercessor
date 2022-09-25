<?php
/**
 * Intercessor reports.
 *
 * @package     Intercessor
 * @subpackage  Functions/Reports
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     http://opensource.org/licenses/gpl-0.9.5.php GNU Public License
 * @since       0.9.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sets up Report page.
 *
 * @since 1.1.1
 * @return void
 */
function intercessor_reports_page() {

	$active_tab = isset( $_GET[ 'tab' ] ) && array_key_exists( $_GET['tab'], intercessor_get_reports_tabs() ) ? $_GET[ 'tab' ] : 'prayers';

	// Enqueue necessary styles and scripts.
	wp_enqueue_style( 'intercessor-reports' );
	wp_enqueue_script( 'intercessor-reports' );

	?>
	<div class="wrap">
        <h1 class="screen-reader-text"><?php esc_html_e( 'Intercessor Reports', 'intercessor' ); ?></h1>

		<?php do_action( 'intercessor_reports_page_top' ); ?>

		<h2 class="nav-tab-wrapper">
			<?php
			foreach ( intercessor_get_reports_tabs() as $tab_id => $tab_name ) {

				$tab_url = add_query_arg(
					[
						'settings-updated'   => false,
						'tab'                => $tab_id,
						'intercessor_notice' => false,
					]
				);

				$active = $active_tab === $tab_id ? ' nav-tab-active' : '';

				echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">';
				echo esc_html( $tab_name );
				echo '</a>';
			}
			?>
		</h2>

		<?php do_action( 'intercessor_reports_page_middle' ); ?>

		<div id="tab_container">
			<?php do_action( 'intercessor_reports_tab_' . $active_tab ); ?>
		</div><!-- #tab_container-->

		<?php do_action( 'intercessor_reports_page_bottom' ); ?>

	</div>
	<?php
}

/**
 * Retrieve reports tabs
 *
 * @since 0.9.5
 * @return array $tabs
 */
function intercessor_get_reports_tabs(): array {

	$tabs                = [];
	$tabs['prayers']     = esc_html__( 'Prayers', 'intercessor' );
	$tabs['prayed']      = esc_html__( 'Prayed Counts', 'intercessor' );
	$tabs['requesters']  = esc_html__( 'Requesters', 'intercessor' );

	return apply_filters( 'intercessor_reports_tabs', $tabs );
}

/**
 * Display the prayers reports tab
 *
 * @since 0.9.5
 * @return void
 */
function intercessor_reports_tab_prayers() {

	$stats = new Intercessor\Reports();

	?>
	<table id="intercessor_active_prayers" class="intercessor-table">

		<thead>

		<tr>

			<th><?php esc_html_e( 'Total Active Prayers', 'intercessor' ); ?></th>
			<th><?php esc_html_e( 'Active Prayers This Month', 'intercessor' ); ?></th>
			<th><?php esc_html_e( 'Active Prayers Today', 'intercessor' ); ?></th>

		</tr>

		</thead>

		<tbody>

		<tr>
			<td><?php echo $stats->get_prayer_requests( 0, 'last year', false,'active' ); ?></td>
			<td><?php echo $stats->get_prayer_requests( 0, 'this month', false,'active' ); ?></td>
			<td><?php echo $stats->get_prayer_requests( 0, 'today', false,'active' ); ?></td>
		</tr>

		</tbody>

	</table>

	<table id="intercessor_pending_prayers" class="intercessor-table">

		<thead>

		<tr>

			<th><?php esc_html_e( 'Pending Prayers', 'intercessor' ); ?></th>
			<th><?php esc_html_e( 'Pending Prayers This Month', 'intercessor' ); ?></th>
			<th><?php esc_html_e( 'Pending Prayers Today', 'intercessor' ); ?></th>

		</tr>

		</thead>

		<tbody>

		<tr>
			<td><?php echo $stats->get_prayer_requests( 0, 'this year', false,'pending' ); ?></td>
			<td><?php echo $stats->get_prayer_requests( 0, 'this month', false,'pending' ); ?></td>
			<td><?php echo $stats->get_prayer_requests( 0, 'today', false,'pending' ); ?></td>
		</tr>

		</tbody>

	</table>

	<table id="intercessor_personal_prayers" class="intercessor-table">

		<thead>

		<tr>

			<th><?php esc_html_e( 'Private Prayers', 'intercessor' ); ?></th>
			<th><?php esc_html_e( 'Private Prayers This Month', 'intercessor' ); ?></th>
			<th><?php esc_html_e( 'Private Prayers Today', 'intercessor' ); ?></th>

		</tr>

		</thead>

		<tbody>

		<tr>
			<td><?php echo $stats->get_prayer_requests( 0, 'this_year', false,'personal' ); ?></td>
			<td><?php echo $stats->get_prayer_requests( 0, 'this_month', false,'personal' ); ?></td>
			<td><?php echo $stats->get_prayer_requests( 0, 'today', false,'personal' ); ?></td>
		</tr>

		</tbody>

	</table>

	<table id="intercessor_archived_counts" class="intercessor-table">

		<thead>

		<tr>

			<th><?php esc_html_e( 'Archived Prayer Requests', 'intercessor' ); ?></th>
			<th><?php esc_html_e( 'Archived Prayer Requests This Month', 'intercessor' ); ?></th>
			<th><?php esc_html_e( 'Archived Prayer Requests Today', 'intercessor' ); ?></th>

		</tr>

		</thead>

		<tbody>

		<tr>
			<td><?php echo $stats->get_prayer_requests( 0, 'this_year', false,'archived' ); ?></td>
			<td><?php echo $stats->get_prayer_requests( 0, 'this_month', false,'archived' ); ?></td>
			<td><?php echo $stats->get_prayer_requests( 0, 'today', false,'archived' ); ?></td>
		</tr>

		</tbody>

	</table>

	<?php
	$graph = new \Intercessor\Admin\Reports\Prayers();
	$graph->set( 'x_mode', 'time' );
	$graph->display();

}
add_action( 'intercessor_reports_tab_prayers', 'intercessor_reports_tab_prayers' );


/**
 * Display the prayers reports tab
 *
 * @since 0.9.5
 * @return void
 */
function intercessor_reports_tab_prayed() {

	$stats = new Intercessor\Reports();

	?>
    <table id="intercessor_active_prayers" class="intercessor-table">

        <thead>

        <tr>

            <th><?php esc_html_e( 'Total Prayed For', 'intercessor' ); ?></th>
            <th><?php esc_html_e( 'Prayed Today', 'intercessor' ); ?></th>
            <th><?php esc_html_e( 'Prayed This Week', 'intercessor' ); ?></th>

        </tr>

        </thead>

        <tbody>

        <tr>
            <td><?php echo $stats->get_prayed_for( 0, false, false ); ?></td>
            <td><?php echo $stats->get_prayed_for( 0, 'today', false ); ?></td>
            <td><?php echo $stats->get_prayed_for( 0, 'this week', false ); ?></td>
        </tr>

        </tbody>

    </table>

    <table id="intercessor_pending_prayers" class="intercessor-table">
        <thead>

        <tr>
            <th><?php esc_html_e( 'Prayed For This Month', 'intercessor' ); ?></th>
            <th><?php esc_html_e( 'Prayed For Last Month', 'intercessor' ); ?></th>
            <th><?php esc_html_e( 'Prayed For This Year', 'intercessor' ); ?></th>
        </tr>

        </thead>

        <tbody>
        <tr>
            <td><?php echo $stats->get_prayed_for( 0, 'this month',  false ); ?></td>
            <td><?php echo $stats->get_prayed_for( 0, 'last month', false ); ?></td>
            <td><?php echo $stats->get_prayed_for( 0, 'this year', false ); ?></td>
        </tr>
        </tbody>

    </table>

	<?php
	$graph = new \Intercessor\Admin\Reports\Prayers();
	$graph->set( 'x_mode', 'time' );
	$graph->display();

}
add_action( 'intercessor_reports_tab_prayed', 'intercessor_reports_tab_prayed' );

/**
 * Display the intercessor requesters reports tab
 *
 * @since 0.9.5
 * @return void
 */
function intercessor_reports_tab_requesters() {
	$stats = new Intercessor\Reports();

	?>
    <table id="intercessor_active_prayers" class="intercessor-table">

        <thead>

        <tr>

            <th><?php esc_html_e( 'Total Requesters', 'intercessor' ); ?></th>
            <th><?php esc_html_e( 'Requesters Today', 'intercessor' ); ?></th>
            <th><?php esc_html_e( 'Requesters This Week', 'intercessor' ); ?></th>

        </tr>

        </thead>

        <tbody>

        <tr>
            <td><?php echo $stats->get_requesters( 0,false, false ); ?></td>
            <td><?php echo $stats->get_requesters( 0, 'today', false ); ?></td>
            <td><?php echo $stats->get_requesters( 0, 'this week', false ); ?></td>
        </tr>

        </tbody>

    </table>

    <table id="intercessor_pending_prayers" class="intercessor-table">

        <thead>

        <tr>

            <th><?php esc_html_e( 'Requesters This Month', 'intercessor' ); ?></th>
            <th><?php esc_html_e( 'Requesters This Year', 'intercessor' ); ?></th>
            <th><?php esc_html_e( 'Requesters Last Year', 'intercessor' ); ?></th>

        </tr>

        </thead>

        <tbody>

        <tr>
            <td><?php echo $stats->get_requesters( 0, 'this month', false ); ?></td>
            <td><?php echo $stats->get_prayer_requests( 0, 'this year', false,'pending' ); ?></td>
            <td><?php echo $stats->get_prayer_requests( 0, 'today', false,'pending' ); ?></td>
        </tr>

        </tbody>

    </table>

    <table id="intercessor_personal_prayers" class="intercessor-table">

        <thead>

        <tr>

            <th><?php esc_html_e( 'Private Prayers', 'intercessor' ); ?></th>
            <th><?php esc_html_e( 'Private Prayers This Month', 'intercessor' ); ?></th>
            <th><?php esc_html_e( 'Private Prayers Today', 'intercessor' ); ?></th>

        </tr>

        </thead>

        <tbody>

        <tr>
            <td><?php echo $stats->get_prayer_requests( 0, 'this_year', false,'personal' ); ?></td>
            <td><?php echo $stats->get_prayer_requests( 0, 'this_month', false,'personal' ); ?></td>
            <td><?php echo $stats->get_prayer_requests( 0, 'today', false,'personal' ); ?></td>
        </tr>

        </tbody>

    </table>

    <table id="intercessor_archived_counts" class="intercessor-table">

        <thead>

        <tr>

            <th><?php esc_html_e( 'Archived Prayer Requests', 'intercessor' ); ?></th>
            <th><?php esc_html_e( 'Archived Prayer Requests This Month', 'intercessor' ); ?></th>
            <th><?php esc_html_e( 'Archived Prayer Requests Today', 'intercessor' ); ?></th>

        </tr>

        </thead>

        <tbody>

        <tr>
            <td><?php echo $stats->get_prayer_requests( 0, 'this_year', false,'archived' ); ?></td>
            <td><?php echo $stats->get_prayer_requests( 0, 'this_month', false,'archived' ); ?></td>
            <td><?php echo $stats->get_prayer_requests( 0, 'today', false,'archived' ); ?></td>
        </tr>

        </tbody>

    </table>

	<?php
	$graph = new \Intercessor\Admin\Reports\Requesters();
	$graph->set( 'x_mode', 'time' );
	$graph->display();

}
add_action( 'intercessor_reports_tab_requesters', 'intercessor_reports_tab_requesters' );

/**
 * Sets up the dates used to filter graph data
 *
 * Date sent via $_GET is read first and then modified (if needed) to match the
 * selected date-range (if any)
 *
 * @since 0.9.5
 * @return array
 */
function intercessor_get_report_dates() {
	$dates = [];

	$current_time      = current_time( 'timestamp' );

	$dates['range']    = isset( $_GET['range'] ) ? esc_attr( $_GET['range'] ) : 'this_month';
	$dates['year']     = isset( $_GET['year_start'] ) ? esc_attr( $_GET['year_start'] ) : date( 'Y', $current_time );
	$dates['year_end'] = isset( $_GET['year_end'] ) ? esc_attr( $_GET['year_end'] ) : date( 'Y', $current_time );
	$dates['m_start']  = isset( $_GET['m_start'] ) ? esc_attr( $_GET['m_start'] ) : 1;
	$dates['m_end']    = isset( $_GET['m_end'] ) ? esc_attr( $_GET['m_end'] ) : 12;
	$dates['day']      = isset( $_GET['day'] ) ? esc_attr( $_GET['day'] ) : 1;
	$dates['day_end']  = isset( $_GET['day_end'] ) ? esc_attr( $_GET['day_end'] ) : cal_days_in_month( CAL_GREGORIAN, $dates['m_start'], $dates['year'] );

	// Modify dates based on predefined ranges.
	switch ( $dates['range'] ) :

		case 'this_month':
			$dates['day']       = 1;
			$dates['day_end']   = cal_days_in_month( CAL_GREGORIAN, $dates['m_start'], date( 'Y', $current_time ) );
			$dates['m_start']   = date( 'n', $current_time );
			$dates['m_end']	    = date( 'n', $current_time );
			$dates['year']      = date( 'Y', $current_time );
			break;

		case 'last_month':
			if ( date( 'n' ) === 1 ) {
				$dates['day']      = 1;
				$dates['day_end']  = cal_days_in_month( CAL_GREGORIAN, 12, date( 'Y', $current_time ) );
				$dates['m_start']  = 12;
				$dates['m_end']	   = 12;
				$dates['year']     = date( 'Y', $current_time ) - 1;
				$dates['year_end'] = date( 'Y', $current_time ) - 1;
			} else {
				$dates['day']      = 1;
				$dates['day_end']  = cal_days_in_month( CAL_GREGORIAN, date( 'n' ) - 1, date( 'Y', $current_time ) );
				$dates['m_start']  = date( 'n' ) - 1;
				$dates['m_end']	   = date( 'n' ) - 1;
				$dates['year_end'] = $dates['year'];
			}
			break;

		case 'today':
			$dates['day']     = date( 'd', $current_time );
			$dates['day_end'] = date( 'd', $current_time );
			$dates['m_start'] = date( 'n', $current_time );
			$dates['m_end']	  = date( 'n', $current_time );
			$dates['year']    = date( 'Y', $current_time );
			break;

		case 'yesterday':
			$month              = date( 'n', $current_time ) == 1 && date( 'd', $current_time ) == 1 ? 12 : date( 'n', $current_time );
			$days_in_month      = cal_days_in_month( CAL_GREGORIAN, $month, date( 'Y', $current_time ) );
			$yesterday          = date( 'd', $current_time ) == 1 ? $days_in_month : date( 'd', $current_time ) - 1;
			$dates['day']		= $yesterday;
			$dates['day_end']   = $yesterday;
			$dates['m_start'] 	= $month;
			$dates['m_end'] 	= $month;
			$dates['year']		= $month === 1 && date( 'd', $current_time ) == 1 ? date( 'Y', $current_time ) - 1 : date( 'Y', $current_time );
			break;

		case 'this_week':
			$dates['day']       = date( 'd', $current_time - ( date( 'w', $current_time ) - 1 ) * 60 * 60 * 24 ) - 1;
			$dates['day']      += get_option( 'start_of_week' );
			$dates['day_end']   = $dates['day'] + 6;
			$dates['m_start'] 	= date( 'n', $current_time );
			$dates['m_end']		= date( 'n', $current_time );
			$dates['year']		= date( 'Y', $current_time );
			break;

		case 'last_week':
			$dates['day']     = date( 'd', $current_time - ( date( 'w' ) - 1 ) * 60 * 60 * 24 ) - 8;
			$dates['day']    += get_option( 'start_of_week' );
			$dates['day_end'] = $dates['day'] + 6;
			$dates['year']    = date( 'Y', $current_time );

			if ( date( 'j', $current_time ) <= 7 ) {
				$dates['m_start'] 	= date( 'n', $current_time ) - 1;
				$dates['m_end']		= date( 'n', $current_time ) - 1;
				if ( $dates['m_start'] <= 1 ) {
					$dates['year'] = date( 'Y', $current_time ) - 1;
					$dates['year_end'] = date( 'Y', $current_time ) - 1;
				}
			} else {
				$dates['m_start'] = date( 'n', $current_time );
				$dates['m_end']   = date( 'n', $current_time );
			}
			break;

		case 'this_quarter':
			$month_now = date( 'n', $current_time );
			$dates['day'] = 1;

			if ( $month_now <= 3 ) {

				$dates['day_end']   = cal_days_in_month( CAL_GREGORIAN, 4, date( 'Y', $current_time ) );
				$dates['m_start'] 	= 1;
				$dates['m_end']		= 4;
				$dates['year']		= date( 'Y', $current_time );

			} else if ( $month_now <= 6 ) {

				$dates['day_end']   = cal_days_in_month( CAL_GREGORIAN, 7, date( 'Y', $current_time ) );
				$dates['m_start'] 	= 4;
				$dates['m_end']		= 7;
				$dates['year']		= date( 'Y', $current_time );

			} else if ( $month_now <= 9 ) {

				$dates['day_end']   = cal_days_in_month( CAL_GREGORIAN, 10, date( 'Y', $current_time ) );
				$dates['m_start'] 	= 7;
				$dates['m_end']		= 10;
				$dates['year']		= date( 'Y', $current_time );

			} else {

				$dates['day_end']   = cal_days_in_month( CAL_GREGORIAN, 1, date( 'Y', $current_time ) + 1 );
				$dates['m_start'] 	= 10;
				$dates['m_end']		= 1;
				$dates['year']		= date( 'Y', $current_time );
				$dates['year_end']  = date( 'Y', $current_time ) + 1;

			}
			break;

		case 'last_quarter' :
			$month_now = date( 'n' );
			$dates['day'] = 1;

			if ( $month_now <= 3 ) {

				$dates['day_end']   = cal_days_in_month( CAL_GREGORIAN, 9, date( 'Y', $current_time ) - 1 );
				$dates['m_start']   = 10;
				$dates['m_end']     = 12;
				$dates['year']      = date( 'Y', $current_time ) - 1; // Previous year

			} else if ( $month_now <= 6 ) {

				$dates['day_end']   = cal_days_in_month( CAL_GREGORIAN, 3, date( 'Y', $current_time ) );
				$dates['m_start'] 	= 1;
				$dates['m_end']		= 3;
				$dates['year']		= date( 'Y', $current_time );

			} else if ( $month_now <= 9 ) {

				$dates['day_end']   = cal_days_in_month( CAL_GREGORIAN, 6, date( 'Y', $current_time ) );
				$dates['m_start'] 	= 4;
				$dates['m_end']		= 6;
				$dates['year']		= date( 'Y', $current_time );

			} else {

				$dates['day_end']   = cal_days_in_month( CAL_GREGORIAN, 9, date( 'Y', $current_time ) );
				$dates['m_start'] 	= 7;
				$dates['m_end']		= 9;
				$dates['year']		= date( 'Y', $current_time );

			}
			break;

		case 'this_year' :
			$dates['day']       = 1;
			$dates['day_end']   = cal_days_in_month( CAL_GREGORIAN, 12, date( 'Y', $current_time ) );
			$dates['m_start'] 	= 1;
			$dates['m_end']		= 12;
			$dates['year']		= date( 'Y', $current_time );
			$dates['year_end']  = date( 'Y', $current_time );
			break;

		case 'last_year' :
			$dates['day']       = 1;
			$dates['day_end']   = cal_days_in_month( CAL_GREGORIAN, 12, date( 'Y', $current_time ) - 1 );
			$dates['m_start'] 	= 1;
			$dates['m_end']		= 12;
			$dates['year']		= date( 'Y', $current_time ) - 1;
			$dates['year_end']  = date( 'Y', $current_time ) - 1;
			break;

	endswitch;

	return apply_filters( 'intercessor_report_dates', $dates );
}

