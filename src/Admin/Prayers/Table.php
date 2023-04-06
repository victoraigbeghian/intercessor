<?php
/**
 * Prayer Requests Table Class
 *
 * @package     Intercessor
 * @subpackage  Admin/Prayers
 * @copyright   Copyright (c) 2018, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       0.9.5
 */

namespace Intercessor\Admin\Prayers;

use Intercessor\Admin\List_Table;
use Intercessor\Html;
use function intercessor_clean;
use function intercessor_process_item;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Prayer Requests Table Class
 *
 * Renders the Prayer Requests table on the Prayer Requests page.
 *
 * @since 0.9.5
 */
class Table extends List_Table {

	/**
	 * URL of this page.
	 *
	 * @var string
	 * @since 0.9.5
	 */
	public $base_url;

	/**
	 * Get things started
	 *
	 * @since 0.9.5
	 * @see   WP_List_Table::__construct()
	 */
	public function __construct() {
		$args = [
            'singular' => esc_html__( 'Prayer Request', 'intercessor' ),
            'plural'   => esc_html__( 'Prayer Requests', 'intercessor' ),
            'ajax'     => false,
        ];

		parent::__construct( $args );

		$this->base_url = $this->get_base_url();
		$this->filter_bar_hooks();
		$this->get_prayers_counts();
		$this->process_bulk_action();
	}

	/**
	 * Get the base URL for the prayer list table
	 *
	 * @since 0.9.5
	 *
	 * @return string
	 */
	public function get_base_url() {

		// Remove some query arguments.
		return remove_query_arg(
			\intercessor_admin_removable_query_args(),
			\intercessor_get_base_admin_url()
		);
	}

	/**
	 * Hook in filter bar actions
	 *
	 * @since 1.0.0
	 */
	private function filter_bar_hooks() {
		add_action( 'intercessor_admin_filter_bar_prayers', array( $this, 'filter_bar_items' ) );
		add_action( 'intercessor_after_admin_filter_bar_prayers', array( $this, 'filter_bar_searchbox' ) );
	}

	/**
	 * Add prayer search filter.
	 *
	 * @return void
	 */
	public function advanced_filters() {
		$start_date = isset( $_GET['start-date'] ) ? intercessor_clean( $_GET['start-date'] ) : null;
		$end_date   = isset( $_GET['end-date'] ) ? intercessor_clean( $_GET['end-date'] ) : null;
		$status     = isset( $_GET['status'] ) ? intercessor_clean( $_GET['status'] ) : '';
		$requester  = isset( $_GET['requester'] ) ? absint( $_GET['requester'] ) : '';
		$search     = isset( $_GET['s'] ) ? intercessor_clean( $_GET['s'] ) : '';
		?>
		<div id="intercessor_prayer_filters">
			<span id="intercessor_date_filters">
				<?php

                $html = new Html();
				echo $html->date_field(
					[
						'id'          => 'start-date',
						'name'        => 'start-date',
						'placeholder' => esc_html_x( 'From', 'date filter', 'intercessor' ),
						'value'       => $start_date,
						'label'       => esc_html__( 'Start Date', 'intercessor' )
					]
				);

				echo $html->date_field( [
					'id'          => 'end-date',
					'name'        => 'end-date',
					'placeholder' => _x( 'To', 'date filter', 'intercessor' ),
					'value'       => $end_date,
					'label'       => esc_html__( ' End Date', 'intercessor' )
				] );

			?>
			</span>
			<span id="intercessor_prayer_filters_submit">
			<?php do_action( 'intercessor_after_advanced_filters_fields' ); ?>
				<input type="submit" class="button-secondary" value="<?php esc_html_e( 'Filter', 'intercessor' ); ?>"/>
				<?php
				// Clear active filters button.
				if ( ! empty( $start_date ) || ! empty( $end_date ) || ! empty( $requester ) || ! empty( $search ) || ! empty( $status ) || ! empty( $prayer_id ) ) :
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=intercessor-prayers' ) ); ?>"
					class="button-secondary"><?php esc_html_e( 'Clear', 'intercessor' ); ?></a>
				<?php endif; ?>
			</span>

			<?php
			/**
			 * Action to add hidden fields and HTML in Prayer search.
			 *
			 * @since 0.9.5
			 */
			do_action( 'intercessor_prayer_table_advanced_filters' );

			if ( ! empty( $status ) ) {
				echo sprintf( '<input type="hidden" name="status" value="%s"/>', esc_attr( $status ) );
			}

			if ( ! empty( $requester ) ) {
				echo sprintf( '<input type="hidden" name="requester" value="%s"/>', absint( $requester ) );
			}
			?>
			<input type="hidden" name="intercessor-prayers-list-nonce" value="<?php echo esc_attr( wp_create_nonce( 'intercessor_prayers_list_nonce' ) ); ?>"/>

			<?php $this->search_box( esc_html__( 'Search', 'intercessor' ), 'intercessor-prayers' ); ?>

		</div>
		<?php
	}

	/**
	 * Output filter bar items
	 *
	 * @since 1.0.0
	 */
	public function filter_bar_items() {

	}

	/**
	 * Output the filter bar searchbox
	 *
	 * @since 1.0.0
	 */
	public function filter_bar_searchbox() {
		do_action( 'intercessor_prayer_advanced_filters_row' );

		$this->search_box( esc_html__( 'Search', 'intercessor' ), 'intercessor-prayers' );
	}

	/**
	 * Show the search field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text     Label for the search box.
	 * @param string $input_id ID of the search box.
	 */
	public function search_box( $text, $input_id ) {

		// Bail if no requesters and no search.
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		}

		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		}

		?>

		<p class="search-form">
			<?php do_action( 'intercessor_prayer_history_search' ); ?>
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" placeholder="<?php esc_html_e( 'Search prayers...', 'intercessor' ); ?>" value="<?php _admin_search_query(); ?>" />
		</p>

		<?php
	}

	/**
	 * Retrieve the table columns
	 *
	 * @access public
	 * @since 0.9.5
	 *
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {
		$columns = [
			'cb'           => '<input type="checkbox" />',
			'id'           => esc_html__( 'ID', 'intercessor' ),
			'details'      => esc_html__( 'Prayer Details', 'intercessor' ),
			'requester'    => esc_html__( 'Requester', 'intercessor' ),
			'start_date'   => esc_html__( 'Date', 'intercessor' ),
			'status'       => esc_html__( 'Status', 'intercessor' ),
			'prayed_count' => esc_html__( 'Counts', 'intercessor' ),
		];

		return apply_filters( 'intercessor_prayers_table_columns', $columns );
	}

	/**
	 * Retrieve the table's sortable columns
	 *
	 * @access public
	 * @since 0.9.5
	 *
	 * @return array Array of all the sortable columns
	 */
	public function get_sortable_columns() {
		$sortable = [
			'id'           => [ 'id', false ],
			'requester'    => [ 'requester', false ],
			'prayed_count' => [ 'prayed_count', false ],
			'start_date'   => [ 'start_date', false ],
		];

		return apply_filters( 'intercessor_prayers_table_sortable_columns', $sortable );
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return string Name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'id';
	}
	
	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @param object $prayer Prayer object.
	 * @param string $column_name The name of the column.
	 *
	 * @access public
	 * @since 0.9.5
	 *
	 * @return string Column Name
	 */
	public function column_default( $prayer, $column_name ) {
		switch ( $column_name ) {
			case 'prayed_count':
				$value = intercessor_get_prayed_for_counts( $prayer->id );
				break;

			case 'start_date':
				$start_date = $prayer->date_created;

				if ( $start_date ) {
					$value = date_i18n( get_option( 'date_format' ), strtotime( $start_date ) );
				} else {
					$value = '&mdash;';
				}
				break;

			default:
				$value = isset( $prayer->$column_name ) ? $prayer->$column_name : '';
				break;
		}

		/**
		 * Filters the default value for each prayers list table column.
		 *
		 * This dynamic filter is appended with a suffix of the column name.
		 *
		 * @param string           $value     The column data.
		 * @param object $prayer The current prayer object
		 */
		return apply_filters( 'intercessor_prayers_table_' . $column_name, $value, $prayer );
	}

	/**
	 * Render the Details Column
	 *
	 * @access public
	 * @since 0.9.5
	 *
	 * @param object Intercessor\Prayer $prayer Prayer object.
	 * @return string Data shown in the details column
	 */
	public function column_details( $prayer ) {
		$row_actions    = [];
		$prayer_content = '<strong>' . $prayer->title . '</strong></br>' . $prayer->message;

		// Active, so add "deactivate" action or pray for request.
		if ( 'active' === strtolower( $prayer->status ) || 'personal' === strtolower( $prayer->status ) ) {

			// Pray for request.
			$row_actions['uplift'] = '<a class="button-primary ipr-icon-praying" href="' . esc_url(
				wp_nonce_url(
					add_query_arg(
						array(
							'intercessor-action' => 'uplift_prayer',
							'prayer'             => $prayer->id,
						),
						$this->base_url
					),
					'intercessor_prayer_nonce'
					)
				) . '">' . esc_html__( ' Pray', 'intercessor' ) . '</a>';

			// Deactivate prayer.
			$row_actions['deactivate'] = '<a href="' . esc_url(
				wp_nonce_url(
					add_query_arg(
						array(
							'intercessor-action' => 'deactivate_prayer',
							'prayer'             => $prayer->id,
						),
						$this->base_url
					),
					'intercessor_prayer_nonce'
				)
			) . '">' . esc_html__( 'Deactivate', 'intercessor' ) . '</a>';

			// Pending, so add "activate" action.
		} elseif ( 'pending' === strtolower( $prayer->status ) ) {
			$row_actions['activate'] = '<a href="' . esc_url(
				wp_nonce_url(
					add_query_arg(
						array(
							'intercessor-action' => 'activate_prayer',
							'prayer'             => $prayer->id,
						),
						$this->base_url
					),
					'intercessor_prayer_nonce'
				)
			) . '">' . esc_html__( 'Activate', 'intercessor' ) . '</a>';

			// Archived, so add "activate" action.
		} elseif ( 'archived' === strtolower( $prayer->status ) ) {
			$row_actions['activate'] = '<a href="' . esc_url(
				wp_nonce_url(
					add_query_arg(
						array(
							'intercessor-action' => 'activate_prayer',
							'prayer'             => $prayer->id,
						),
						$this->base_url
					),
					'intercessor_prayer_nonce'
				)
			) . '">' . esc_html__( 'Restore', 'intercessor' ) . '</a>';

		}

		// View prayer request details.
		$row_actions['view_details'] = '<a class="button-secondary" href="' . esc_url(
			wp_nonce_url(
				add_query_arg(
					array(
						'intercessor-action' => 'view_request_details',
						'prayer'             => $prayer->id,
					)
				),
				'intercessor_prayer_nonce'
			)
		) . '">' . esc_html__( 'View Prayer', 'intercessor' ) . '</a>';

		// Delete prayer request.
		$row_actions['delete'] = '<a href="' . esc_url(
			wp_nonce_url(
				add_query_arg(
					array(
						'intercessor-action' => 'delete_prayer',
						'prayer'             => $prayer->id,
					),
					$this->base_url
				),
				'intercessor_prayer_nonce'
			)
		) . '" onclick="return confirm(\'Are you sure you want to delete this prayer request?\')">' . esc_html__( 'Delete', 'intercessor' ) . '</a>';

		/**
		 * Filters the details table row actions.
		 *
		 * @param string $row_actions The row actions.
		 * @param object $prayer      The prayer object.
		 *
		 * @since 0.9.5
		 */
		$row_actions = apply_filters( 'intercessor_prayers_table_row_actions', $row_actions, $prayer );

		return stripslashes( $prayer_content ) . $this->row_actions( $row_actions );
	}

	/**
	 * This function renders the requester column.
	 *
	 * @access public
	 * @since 0.9.5
	 *
	 * @param object Intercessor\Prayer $prayer Data for the prayer request.
	 * @return string Requester.
	 */
	public function column_requester( $prayer ): string {

		$email        = \intercessor_get_prayer_email( $prayer->id );
		$requester_id = $prayer->requester_id;
		$requester    = \intercessor_process_item( 'requester', 'get', $requester_id, false );
		$base         = $this->get_base_url();
		$row_actions  = [];

		// Check if requester exists.
		if ( ! empty( $requester ) ) {

			// Use requester name if available.
			$name_value = ! empty( $requester->name )
				? $requester->name
				: esc_html__( 'No Name', 'intercessor' );

			// Requester link.
			$link = intercessor_get_admin_url(
				'requesters',
				array(
					'view' => 'overview',
					'id'   => $requester_id,
				)
			);

			$name_value = '<a href="' . esc_url( $link ) . '">' . $name_value . '</a>';
		} else {
			$name_value = esc_html__( 'Requester missing', 'intercessor' );
		}

		// Process requester email.
		if ( 'active' === $prayer->status && ! empty( $email ) ) {
			$row_actions['email_links'] = '<a href="' . add_query_arg(
				array(
					'intercessor-action' => 'email_links',
					'prayer_id'          => $prayer->id ),
					$this->base_url
				) . '">' . esc_html__( 'Resend Notification', 'intercessor' ) . '</a>';
		}

		$row_actions = apply_filters( 'intercessor_prayers_table_row_actions', $row_actions, $prayer );

		if ( empty( $email ) ) {
			$email = esc_html__( '(unknown)', 'intercessor' );
		}

		$email_value = $email . $this->row_actions( $row_actions );

		$email_values = apply_filters( 'intercessor_prayers_table_column', $email_value, $prayer->id, 'email' );

		$name_values = apply_filters( 'intercessor_prayers_table_column_requester', $name_value, $prayer->id, 'requester' );

		return $name_values . '<br>' . $email_values;

	}

	/**
	 * Render the prayer request status.
	 *
	 * @since 0.9.5
	 *
	 * @param object $prayer Prayer request object.
	 *
	 * @return string Prayer status
	 */
	public function column_status( $prayer ) {
		switch ( $prayer->status ) {
			case 'archived':
				$status = esc_html__( 'Archived', 'intercessor' );
				break;
			case 'pending':
				$status = esc_html__( 'Pending', 'intercessor' );
				break;
			case 'personal':
				$status = esc_html__( 'Private', 'intercessor' );
				break;
			case 'active':
			default:
				$status = esc_html__( 'Active', 'intercessor' );
				break;
		}

		return $status;
	}

	/**
	 * Render the checkbox column
	 *
	 * @access public
	 * @since 0.9.5
	 *
	 * @param object Intercessor\Prayer $prayer Prayer object.
	 *
	 * @return string Displays a checkbox
	 */
	public function column_cb( $prayer ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ 'prayer',
			/*$2%s*/ $prayer->id
		);
	}

	/**
	 * Message to be displayed when there are no prayer requests
	 *
	 * @since 0.9.5
	 * @access public
	 */
	public function no_items() {
		esc_html_e( 'No prayer requests found.', 'intercessor' );
	}

	/**
	 * Retrieve the bulk actions
	 *
	 * @access public
	 * @since 0.9.5
	 * @return array $actions Array of the bulk actions
	 */
	public function get_bulk_actions() {
		$actions = [
			'activate'        => esc_html__( 'Activate', 'intercessor' ),
			'deactivate'      => esc_html__( 'Set to Pending', 'intercessor' ),
			'delete'          => esc_html__( 'Delete', 'intercessor' ),
			'set-to-archived' => esc_html__( 'Set to Archived', 'intercessor' ),
			'set-to-personal' => esc_html__( 'Set to Private', 'intercessor' ),
		];

		return apply_filters( 'intercessor_prayers_table_bulk_actions', $actions );
	}

	/**
	 * Process the bulk actions
	 *
	 * @access public
	 * @since 0.9.5
	 * @return void
	 */
	public function process_bulk_action() {
		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		$ids = isset( $_GET['prayer'] ) ? intercessor_clean( $_GET['prayer'] ) : false;

		$action = $this->current_action();

		if ( ! is_array( $ids ) ) {
			$ids = [ $ids ];
		}

		if ( empty( $action ) ) {
			return;
		}

		$ids = wp_parse_id_list( $ids );

		foreach ( $ids as $id ) {

			// Process a bulk action.
			switch ( $action ) {
				case 'activate':
					\intercessor_do_prayer_activation( $id );
					break;

				case 'deactivate':
				    $data = [
				        'status' => 'pending',
                    ];
				//	\intercessor_update_prayer_status( $id, 'pending' );
                    intercessor_process_item( 'prayer', 'update', $id, $data );
					break;

				case 'set-to-personal':
				//	\intercessor_update_prayer_status( $id, 'personal' );
                    $data = [
                        'status' => 'personal',
                    ];
                    intercessor_process_item( 'prayer', 'update', $id, $data );
					break;

				case 'set-to-archived':
				//	\intercessor_update_prayer_status( $id, 'archived' );
                    $data = [
                        'status' => 'archived',
                    ];
                    intercessor_process_item( 'prayer', 'update', $id, $data );
					break;

				case 'delete':
					intercessor_process_item( 'prayer', 'delete', $id, false );
					break;

				case 'email_links':
					\intercessor_email_prayer_notification( $id, false );
					break;

			}

			/**
			 * Fires when the prayer request table bulk action is done.
			 *
			 * @param int    $id             The prayer ID
			 * @param string $current_action The current action.
			 *
			 * @since 0.9.5
			 */
			do_action( 'intercessor_prayers_table_do_bulk_action', $id, $this->current_action() );
		}
	}

	/**
	 * Retrieve the prayer request counts
	 *
	 * @access public
	 * @since 0.9.5
	 * @return void
	 */
	public function get_prayers_counts() {
		// Get the args (without pagination).
		$args = $this->parse_args( false );

		unset( $args['status'], $args['status__not_in'], $args['status__in'] );

		// Get prayer counts by type.
		$this->counts = intercessor_get_item_counts( 'prayer', $args );
	}

	/**
	 * Retrieve all the data for all the prayer requests
	 *
	 * @access public
	 * @since 0.9.5
	 * @return array $prayer_requests_data Array of all the data for the prayer requests
	 */
	public function get_data() {
		// Parse args (with pagination).
		$this->args = $this->parse_args( true );

		// Force Intercessor\Prayer objects to be returned.
		$this->args['output'] = 'prayers';

		if ( empty( $this->args['status'] ) ) {
			$this->args['status__not_in'] = array( 'trash' );
		}

		// Get data.
		$items = \intercessor_get_items( 'prayer', $this->args );

		// Get requester IDs and count from prayers.
		$requester_ids   = array_unique( wp_list_pluck( $items, 'requester_id' ) );
		$requester_count = count( $requester_ids );

		// Maybe prime requester objects (if more than number of queries).
		if ( $requester_count > 1 ) {
			intercessor_get_items(
				'requester',
				array(
					'id__in'        => $requester_ids,
					'no_found_rows' => true,
					'number'        => $requester_count,
				)
			);
		}

		// Return items.
		return $items;
	}

	/**
	 * Builds an array of arguments for getting orders for the list table, counts, and pagination.
	 *
	 * @since 3.0
	 *
	 * @param bool $paginate Whether to add pagination arguments
	 *
	 * @return array Array of arguments to use for querying orders.
	 */
	private function parse_args( $paginate = true ) {
		$status     = $this->get_status();
		$user       = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : null;
		$requester  = isset( $_GET['requester'] ) ? absint( $_GET['requester'] ) : null;
		$search     = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : null;
		$start_date = isset( $_GET['start-date'] ) ? sanitize_text_field( $_GET['start-date'] ) : null;
		$end_date   = isset( $_GET['end-date'] ) ? sanitize_text_field( $_GET['end-date'] ) : $start_date;

		$args = array(
			'user'      => $user,
			'requester' => $requester,
			'status'    => $status,
			'search'    => $search,
		);

		// Search.
		if ( is_string( $search ) && ( false !== strpos( $search, 'txn:' ) ) ) {
			$args['search_in_notes'] = true;
			$args['search']          = trim( str_replace( 'txn:', '', $args['s'] ) );
		}

		// Date query.
		if ( ! empty( $start_date ) || ! empty( $end_date ) ) {

			// start AND end.
			$args['date_query'] = array(
				'relation'  => 'AND'
			);

			// Start (of day).
			if ( ! empty( $start_date ) ) {
				$args['date_query'][] = array(
					'column' => 'date_created',
					'after'  => date( 'Y-m-d 00:00:00', strtotime( $start_date ) )
				);
			}

			// End (of day).
			if ( ! empty( $end_date ) ) {
				$args['date_query'][] = array(
					'column' => 'date_created',
					'before'  => date( 'Y-m-d 23:59:59', strtotime( $end_date ) )
				);
			}
		}

		// Return args, possibly with pagination.
		return ( true === $paginate )
			? $this->parse_pagination_args( $args )
			: $args;
	}

	/**
	 * Setup the final data for the table
	 *
	 * @access public
	 * @since 0.9.5
	 * @uses Prayer_Requests_Table::get_columns()
	 * @uses Prayer_Requests_Table::get_sortable_columns()
	 * @uses Prayer_Requests_Table::process_bulk_action()
	 * @uses Prayer_Requests_Table::prayer_requests_data()
	 * @uses WP_List_Table::get_pagenum()
	 * @uses WP_List_Table::set_pagination_args()
	 *
	 * @return void
	 */
	public function prepare_items() {
		wp_reset_vars( [ 'action', 'order', 'orderby', 'order', 's' ] );

		$hidden      = []; // No hidden columns.
		$columns     = $this->get_columns();
		$sortable    = $this->get_sortable_columns();
		$status      = $this->get_status( 'total' );
		$this->items = $this->get_data();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->set_pagination_args(
			array(
				'total_pages' => ceil( $this->counts[ $status ] / $this->per_page ),
				'total_items' => $this->counts[ $status ],
				'per_page'    => $this->per_page,
			)
		);
	}
}
