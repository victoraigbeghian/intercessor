<?php
/**
 * Requester Reports Table Class
 *
 * @package     Intercessor
 * @subpackage  Admin/Reports
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-1.0.0.php GNU Public License
 * @since       0.9.5
 */

namespace Intercessor\Admin\Requesters;

use Intercessor\Admin\List_Table;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Requester Table Class
 *
 * Renders the Requester Reports table
 *
 * @since 0.9.5
 */
class Table extends List_Table {

	/**
	 * Get things started
	 *
	 * @since 1.0.0
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => esc_html__( 'Requester', 'intercessor' ),
				'plural'   => esc_html__( 'Requesters', 'intercessor' ),
				'ajax'     => false,
			]
		);

		$this->process_bulk_action();
		$this->get_counts();
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
		return 'name';
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $item        Contains all the data of the requesters.
	 * @param string $column_name The name of the column.
	 *
	 * @return string Column Name
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'id':
				$value = $item['id'];
				break;

			case 'email':
				$value = '<a href="mailto:' . esc_attr( $item['email'] ) . '">' . esc_html( $item['email'] ) . '</a>';
				break;

			case 'prayer_count':
				$url   = intercessor_get_admin_url(
					'prayers',
					[
						'requester' => $item['id'],
					]
				);
				$value = '<a href="' . esc_url( $url ) . '">' . esc_html( $item['prayer_count'] ) . '</a>';
				break;

			case 'date_created':
				$start_date = $item['date_created'];
				if ( $start_date ) {
					$value = date_i18n( get_option( 'date_format' ), strtotime( $start_date ) );
				} else {
					$value = '&mdash;';
				}
				break;

			default:
				$value = isset( $item[ $column_name ] )
					? $item[ $column_name ]
					: null;
				break;
		}

		// Filter & return.
		return apply_filters( 'intercessor_requesters_column_' . $column_name, $value, $item['id'] );
	}

	/**
	 * Get the contents of the "Name" column
	 *
	 * @since 1.0.0
	 *
	 * @param array $item The requester item.
	 *
	 * @return string
	 */
	public function column_name( $item ) {
		$state    = '';
		$status   = $this->get_status();
		$name     = ! empty( $item['name'] ) ? $item['name'] : '&mdash;';
		$view_url = admin_url( 'admin.php?page=intercessor-requesters&view=overview&id=' . $item['id'] );
		$actions  = array(
			'view'   => '<a href="' . $view_url . '">' . esc_html__( 'Edit', 'intercessor' ) . '</a>',
			'logs'   => '<a href="' . admin_url( 'admin.php?page=intercessor-tools&tab=logs&requester=' . $item['id'] ) . '">' . esc_html__( 'Logs', 'intercessor' ) . '</a>',
			'delete' => '<a href="' . admin_url( 'admin.php?page=intercessor-requesters&view=delete&id=' . $item['id'] ) . '">' . esc_html__( 'Delete', 'intercessor' ) . '</a>',
		);

		$item_status = ! empty( $item['status'] )
			? $item['status']
			: 'active';

		// Status.
		if ( ( ! empty( $status ) && ( $status !== $item_status ) ) || ( $item_status !== 'active' ) ) {
			switch ( $status ) {
				case 'pending':
					$value = esc_html__( 'Pending', 'intercessor' );
					break;
				case 'active':
				case '':
				default:
					$value = esc_html__( 'Active', 'intercessor' );
					break;
			}

			$state = ' &mdash; ' . $value;
		}

		// Get the requester's avatar.
		$avatar = \get_avatar( $item['email'], 32 );

		// Concatenate and return.
		return $avatar . '<strong><a class="row-title" href="' . esc_url( $view_url ) . '">' . esc_html( $name ) . '</a>' . esc_html( $state ) . '</strong>' . $this->row_actions( $actions );
	}

	/**
	 * Render the checkbox column
	 *
	 * @since 1.0.0
	 *
	 * @param \Intercessor\Requester $item Requester object.
	 *
	 * @return string Displays a checkbox
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ 'requester',
			/*$2%s*/ $item['id']
		);
	}

	/**
	 * Message to be displayed when there are no requesters
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function no_items() {
		esc_html_e( 'No requester found.', 'intercessor' );
	}

	/**
	 * Retrieve the requester counts
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function get_counts() {
		$this->counts = \intercessor_get_requester_counts();
	}

	/**
	 * Retrieve the table columns
	 *
	 * @since 1.0.0
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {
		// Setup column arguments array.
		$column_args = [
			'cb'           => '<input type="checkbox" />',
			'id'           => esc_html__( 'ID','intercessor' ),
			'name'         => esc_html__( 'Name','intercessor' ),
			'email'        => esc_html__( 'Email','intercessor' ),
			'prayer_count' => esc_html__( 'Prayers','intercessor' ),
			'date_created' => esc_html__( 'Date','intercessor' ),
		];

		return apply_filters( 'intercessor_report_requester_columns', $column_args );
	}

	/**
	 * Get the sortable columns
	 *
	 * @since 1.0.0
	 * @return array Array of all the sortable columns
	 */
	public function get_sortable_columns() {
		return [
			'id'            => [ 'id', true ],
			'date_created'  => [ 'date_created', true ],
			'name'          => [ 'name', true ],
			'email'         => [ 'email', true ],
			'prayer_count'  => [ 'prayer_count', false ],
		];
	}

	/**
	 * Retrieve the bulk actions
	 *
	 * @since 1.0.0
	 * @return array Array of the bulk actions
	 */
	public function get_bulk_actions() {
		return [
			'delete' => esc_html__( 'Delete', 'intercessor' )
		];
	}

	/**
	 * Process the bulk actions
	 *
	 * @since 1.0.0
	 */
	public function process_bulk_action() {
		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-requesters' ) ) {
			return;
		}

		$ids = isset( $_GET['requester'] )
			? $_GET['requester']
			: false;

		if ( ! is_array( $ids ) ) {
			$ids = [ $ids ];
		}

		foreach ( $ids as $id ) {
			switch ( $this->current_action() ) {
				case 'delete' :
					\intercessor_process_item( 'requester', 'delete', $id, false );
					break;
			}
		}
	}

	/**
	 * Retrieves all of the items to display, given the current filters.
	 *
	 * @since 1.0.0
	 *
	 * @return array $data All the row data.
	 */
	public function get_data() {
		$data   = [];
		$search = $this->get_search();
		$args   = [ 'status' => $this->get_status() ];

		// Email search.
		if ( is_email( $search ) ) {
			$args['email'] = $search;

			// Requester ID.
		} elseif ( is_numeric( $search ) ) {
			$args['id'] = $search;
		} elseif ( strpos( $search, 'c:' ) !== false ) {
			$args['id'] = trim( str_replace( 'c:', '', $search ) );

			// User ID.
		} elseif ( strpos( $search, 'user:' ) !== false ) {
			$args['user_id'] = trim( str_replace( 'u:', '', $search ) );
		} elseif ( strpos( $search, 'u:' ) !== false ) {
			$args['user_id'] = trim( str_replace( 'u:', '', $search ) );

			// Other.
		} else {
			$args['search']         = $search;
			$args['search_columns'] = [ 'name', 'email' ];
		}

		// Parse pagination.
		$this->args = $this->parse_pagination_args( $args );

		// Get the data.
		$requesters = intercessor_get_items( 'requester', $this->args );

		if ( ! empty( $requesters ) ) {
			foreach ( $requesters as $requester ) {
				$data[] = [
					'id'           => $requester->id,
					'user_id'      => $requester->user_id,
					'name'         => $requester->name,
					'email'        => $requester->email,
					'prayer_count' => $requester->prayer_count,
					'date_created' => $requester->date_created,
				];
			}
		}

		return $data;
	}

	/**
	 * Setup the final data for the table
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function prepare_items() {
		$this->_column_headers = [
			$this->get_columns(),
			[],
			$this->get_sortable_columns()
		];

		$this->items = $this->get_data();

		$status = $this->get_status( 'total' );

		// Add condition to be sure we don't divide by zero.
		// If $this->per_page is 0, then set total pages to 1.
		$total_pages = $this->per_page ? ceil( (int) $this->counts[ $status ] / (int) $this->per_page ) : 1;

		// Setup pagination.
		$this->set_pagination_args(
			[
				'total_pages' => $total_pages,
				'total_items' => $this->counts[ $status ],
				'per_page'    => $this->per_page,
			]
		);
	}
}
