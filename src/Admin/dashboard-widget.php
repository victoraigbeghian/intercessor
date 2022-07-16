<?php
/**
 * Dashboard Widgets
 *
 * @package     Intercessor
 * @subpackage  Admin/Dashboard
 * @copyright   Copyright (c) 2021, Victor Aigbeghian
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers the dashboard widgets
 *
 * @since  1.0.0
 * @return void
 */
function intercessor_register_dashboard_widgets() {
	if ( current_user_can( apply_filters( 'intercessor_dashboard_stats_cap', 'edit_prayers' ) ) ) {
		wp_add_dashboard_widget(
			'intercessor_request_summary',
			esc_html__( 'Intercessor Prayers Summary', 'intercessor' ),
			'intercessor_requests_dashboard_widget'
		);
	}
}
add_action( 'wp_dashboard_setup', 'intercessor_register_dashboard_widgets', 10 );

/**
 * Load the prayer request dashboard widget
 *
 * @since 1.0.0
 * @return void
 */
function intercessor_requests_dashboard_widget() {
	if ( ! current_user_can( apply_filters( 'intercessor_dashboard_stats_cap', 'view_prayer_reports' ) ) ) {
		die();
	}

	$stats            = intercessor()->reports;
	$weekly_count     = $stats->get_prayer_requests( 0, 'last week' );
	$monthly_count    = $stats->get_prayer_requests( 0, 'last month' );
	$last_month_count = $stats->get_prayer_requests( 0, 'last 2 months', 'last month' );
	$yearly_count     = $stats->get_prayer_requests( 0, 'last year' );
	?>

	<ul class="intercessor_dashboard_list">	
		<div class="intercessor-dashboard-daily intercessor-clearfix">
			<h3 class="intercessor-dashboard-date-today">
				<?php
				echo date_i18n( _x( 'F j, Y', 'dashboard widget', 'intercessor' ) ); 
				?>
			</h3>
			
			<p class="intercessor-dashboard-blessed-day">
			<?php
				printf(
				/* translators: %s: day of the week */
					__( 'Happy %s!', 'intercessor' ),
					date_i18n( 'l', time() )
				);
			?>
			</p>
			
			<li class="intercessor-dashboard-today-prayers">
			<?php
				$active_today = $stats->get_prayer_requests( 0, 'yesterday', false, 'active' );
				
				printf(
					/* translators: %s: daily prayer request count */
					_n( '%s active prayer today', '%s active prayers today', $active_today, 'intercessor' ),
					$active_today
				);
			?>
			</li>

			<li class="intercessor-dashboard-today-prayers">
			<?php
				$pending_today = $stats->get_prayer_requests( 0, 'yesterday', false, 'pending' );

				printf(
					/* translators: %s: daily prayer request count */
					_n( '%s pending prayer today', '%s pending prayers today', $pending_today, 'intercessor' ), 
					$pending_today
				);
			?>
			</li>

		</div>

		<li class="weekly-prayers">
			<span>
			<?php
				/* translators: %s: prayer count */
				printf(
					_n( '<strong>%s active prayer</strong> This Week', '<strong>%s active prayers</strong> This Week', $weekly_count, 'intercessor' ),
					$weekly_count
				);
			?>
			</span>
		</li>		

		<li class="monthly-prayers">
			<span>
			<?php
				/* translators: %s: prayer count */
				printf(
					_n( '<strong>%s active prayer</strong> This Month', '<strong>%s active prayers</strong> This Month', $monthly_count, 'intercessor' ),
					$monthly_count
				);
			?>
			</span>
		</li>

		<li class="last-month-prayers">
			<span>
			<?php
				/* translators: %s: prayer count */
				printf(
					_n( '<strong>%s active prayer</strong> Last Month', '<strong>%s active prayers</strong> Last Month', $last_month_count, 'intercessor' ),
					$last_month_count
				);
			?>
			</span>
		</li>		

		<li class="yearly-prayers">
			<span>
			<?php
				/* translators: %s: prayer count */
				printf(
					_n( '<strong>%s active prayer</strong> This Year', '<strong>%s active prayers</strong> This Year', $yearly_count, 'intercessor' ),
					$yearly_count
				);
			?>
			</span>
		</li>			 

	</ul>
	<?php
}
