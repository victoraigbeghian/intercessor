<?php
/**
 * Intercessor Stats
 *
 * @package     Intercessor
 * @subpackage  Stats
 * @copyright   Copyright (c) 2020, Victor Aigbeghian
 * @license     https://opensource.org/licenses/GPL-1.0.0.0 GNU Public License
 * @since       1.0.0
 */

namespace Intercessor;

// Exit if accessed directly.
use Intercessor\Database\Queries\Date;

defined( 'ABSPATH' ) || exit;

/**
 * Stats Class.
 *
 * @since 1.0.0
 */
class Stats {

	/**
	 * Parsed query vars.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $query_vars = [];

	/**
	 * Query var originals. These hold query vars passed to the constructor.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $query_var_originals = [];

	/**
	 * Date ranges.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $date_ranges = [];

	/**
	 * Date ranges used when calculating percentage difference.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $relative_date_ranges = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class. Some methods will not allow parameters to be overridden as it could lead to inaccurate calculations.
	 *
	 *     @type string $start         Start day and time (based on the beginning of the given day).
	 *     @type string $end           End day and time (based on the end of the given day).
	 *     @type string $range         Date range. If a range is passed, this will override and `start` and `end`
	 *                                 values passed. See \Intercessor\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type bool   $exclude_taxes If taxes should be excluded from calculations. Default `false`.
	 *     @type string $function      SQL function. Certain methods will only accept certain functions. See each method for
	 *                                 a list of accepted SQL functions.
	 *     @type string $where_sql     Reserved for internal use. Allows for additional WHERE clauses to be appended to the
	 *                                 query.
	 *     @type string $output        The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 */
	public function __construct( $query = [] ) {
		// Set date ranges.
		$this->set_date_ranges();

		// Maybe parse query.
		if ( ! empty( $query ) ) {
			$this->parse_query( $query );

			$this->query_var_originals = $this->query_vars;

			// Set defaults.
		} else {
			$this->query_var_originals = $this->query_vars = [
				'start'             => '',
				'end'               => '',
				'range'             => '',
				'status'            => [ 'active', 'pending', 'personal', 'archived' ],
				'status_sql'        => '',
				'type'              => [],
				'type_sql'          => '',
				'where_sql'         => '',
				'date_query_sql'    => '',
				'date_query_column' => '',
				'column'            => '',
				'table'             => '',
				'function'          => 'SUM',
				'output'            => 'raw',
				'relative'          => false,
				'relative_start'    => '',
				'relative_end'      => '',
				'grouped'           => false,
				'prayer_id'         => '',
				'requester_id'      => '',
			];
		}

	}

	/** Prayer Requests *******************************************************/

	/**
	 * Calculate the number of prayers.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \Intercessor\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  SQL function. Accepts `COUNT` and `AVG`. Default `COUNT`.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended to the
	 *                             query.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return int Number of prayers.
	 */
	public function get_prayer_count( $query = [] ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->ipr_prayers;
		$this->query_vars['column']            = 'id';
		$this->query_vars['date_query_column'] = 'date_created';

		/*
		 * By default we're checking prayers only and excluding refunds. This gives us gross prayer counts.
		 * This may be overridden in $query parameters that get passed through.
		 */
		$this->query_vars['type']   = 'prayer';
		$this->query_vars['status'] = [ 'personal', 'active', 'pending', 'archived' ];

		/**
		 * Filters Order statuses that should be included when calculating stats.
		 *
		 * @since 1.0.0
		 *
		 * @param array $statuses Order statuses to include when generating stats.
		 */
		$this->query_vars['status'] = apply_filters( 'intercessor_prayer_stats_post_statuses', $this->query_vars['status'] );

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		// Only `COUNT` and `AVG` are accepted by this method.
		$accepted_functions = [ 'COUNT', 'AVG' ];

		$function = isset( $this->query_vars['function'] ) && in_array( strtoupper( $this->query_vars['function'] ), $accepted_functions, true )
			? $this->query_vars['function'] . "({$this->query_vars['column']})"
			: 'COUNT(id)';

		if ( true === $this->query_vars['relative'] ) {
			$relative_date_query_sql = $this->generate_relative_date_query_sql();

			$sql = "SELECT IFNULL(COUNT(id), 0) AS total, IFNULL(relative, 0) AS relative
					FROM {$this->query_vars['table']}
					CROSS JOIN (
						SELECT IFNULL(COUNT(id), 0) AS relative
						FROM {$this->query_vars['table']}
						WHERE 1=1 {$this->query_vars['type_sql']} {$this->query_vars['status_sql']} {$this->query_vars['where_sql']} {$relative_date_query_sql}
					) o
					WHERE 1=1 {$this->query_vars['type_sql']} {$this->query_vars['status_sql']} {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}";
		} else {
			$sql = "SELECT {$function} AS total
					FROM {$this->query_vars['table']}
					WHERE 1=1 {$this->query_vars['type_sql']} {$this->query_vars['status_sql']} {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}";
		}

		$result = $this->get_db()->get_row( $sql );

		$total = null === $result
			? 0
			: absint( $result->total );

		if ( true === $this->query_vars['relative'] ) {
			$total    = absint( $result->total );
			$relative = absint( $result->relative );

			if ( ( 0 === $total && 0 === $relative ) || ( $total === $relative ) ) {
				$total = esc_html__( 'No Change', 'intercessor' );
			} elseif ( 0 === $relative ) {
				$total = 0 < $total
					? '<span class="dashicons dashicons-arrow-up"></span> ' . absint( $total )
					: '<span class="dashicons dashicons-arrow-down"></span> ' . absint( $total );
			} else {
				$percentage_change = ( $total - $relative ) / $relative * 100;

				$total = 0 < $percentage_change
					? '<span class="dashicons dashicons-arrow-up"></span> ' . absint( $percentage_change ) . '%'
					: '<span class="dashicons dashicons-arrow-down"></span> ' . absint( $percentage_change ) . '%';
			}
		}

		// Reset query vars.
		$this->post_query();

		return $total;
	}

	/**
	 * Calculate the busiest day of the week for stores.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \Intercessor\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  This method does not allow any SQL functions to be passed.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended to the
	 *                             query.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return string Busiest day of the week.
	 */
	public function get_busiest_day( $query = [] ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->Intercessor_prayers;
		$this->query_vars['column']            = 'id';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		$sql = "SELECT DAYOFWEEK(date_created) AS day, COUNT({$this->query_vars['column']}) as total
				FROM {$this->query_vars['table']}
				WHERE 1=1 {$this->query_vars['status_sql']} {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}
				GROUP BY day
				ORDER BY total DESC
				LIMIT 1";

		$result = $this->get_db()->get_row( $sql );

		$days = [
			esc_html__( 'Sunday', 'intercessor' ),
			esc_html__( 'Monday', 'intercessor' ),
			esc_html__( 'Tuesday', 'intercessor' ),
			esc_html__( 'Wednesday', 'intercessor' ),
			esc_html__( 'Thursday', 'intercessor' ),
			esc_html__( 'Friday', 'intercessor' ),
			esc_html__( 'Saturday', 'intercessor' ),
		];

		$day = null === $result
			? ''
			: $days[ $result->day - 1 ];

		// Reset query vars.
		$this->post_query();

		return $day;
	}


	/** Requesters ************************************************************/

	/**
	 * Calculate the number of requesters.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \Intercessor\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  This method does not allow any SQL functions to be passed.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended
	 *                             to the query.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return int Number of requesters.
	 */
	public function get_requester_count( $query = [] ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->ipr_requesters;
		$this->query_vars['column']            = 'id';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		if ( true === $this->query_vars['relative'] ) {
			$relative_date_query_sql = $this->generate_relative_date_query_sql();

			$sql = "SELECT IFNULL(COUNT(id), 0) AS total, IFNULL(relative, 0) AS relative
					FROM {$this->query_vars['table']}
					CROSS JOIN (
						SELECT IFNULL(COUNT(id), 0) AS relative
						FROM {$this->query_vars['table']}
						WHERE 1=1 {$this->query_vars['where_sql']} {$relative_date_query_sql}
					) o
					WHERE 1=1 {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}";
		} else {
			$sql = "SELECT COUNT(id) AS total
					FROM {$this->query_vars['table']}
					WHERE 1=1 {$this->query_vars['date_query_sql']}";
		}

		$result = $this->get_db()->get_row( $sql );

		$total = null === $result->total
			? 0
			: absint( $result->total );

		if ( true === $this->query_vars['relative'] ) {
			$total    = absint( $result->total );
			$relative = absint( $result->relative );

			if ( ( 0 === $total && 0 === $relative ) || ( $total === $relative ) ) {
				$total = esc_html__( 'No Change', 'intercessor' );
			} elseif ( 0 === $relative ) {
				$total = 0 < $total
					? '<span class="dashicons dashicons-arrow-up"></span> ' . $total
					: '<span class="dashicons dashicons-arrow-down"></span> ' . $total;
			} else {
				$percentage_change = ( $total - $relative ) / $relative * 100;

				$total = 0 < $percentage_change
					? '<span class="dashicons dashicons-arrow-up"></span> ' . absint( $percentage_change ) . '%'
					: '<span class="dashicons dashicons-arrow-down"></span> ' . absint( $percentage_change ) . '%';
			}
		} else {
			$total = $this->maybe_format( $total );
		}

		// Reset query vars.
		$this->post_query();

		return $total;
	}

	/**
	 * Calculate the number of prayers made by a requester.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start        Start day and time (based on the beginning of the given day).
	 *     @type string $end          End day and time (based on the end of the given day).
	 *     @type string $range        Date range. If a range is passed, this will override and `start` and `end`
	 *                                values passed. See \Intercessor\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function     SQL function. Accepts `AVG` and `SUM`. Default `SUM`.
	 *     @type string $where_sql    Reserved for internal use. Allows for additional WHERE clauses to be appended
	 *                                to the query.
	 *     @type int    $requester_id Requester ID. Default empty.
	 *     @type int    $user_id      User ID. Default empty.
	 *     @type string $email        Email address.
	 *     @type string $output       The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return int Number of prayers made by a requester.
	 */
	public function get_requester_prayer_count( $query = [] ) {
		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->Intercessor_prayers;
		$this->query_vars['column']            = 'id';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		// Only `COUNT` and `AVG` are accepted by this method.
		$accepted_functions = array( 'COUNT', 'AVG' );

		$function = isset( $this->query_vars['function'] ) && in_array( strtoupper( $this->query_vars['function'] ), $accepted_functions, true )
			? strtoupper( $this->query_vars['function'] )
			: '';

		$user = isset( $this->query_vars['user_id'] )
			? $this->get_db()->prepare( 'AND user_id = %d', absint( $this->query_vars['user_id'] ) )
			: '';

		$requester = isset( $this->query_vars['requester'] )
			? $this->get_db()->prepare( 'AND requester_id = %d', absint( $this->query_vars['requester'] ) )
			: '';

		$email = isset( $this->query_vars['email'] )
			? $this->get_db()->prepare( 'AND email = %s', sanitize_email( $this->query_vars['email'] ) )
			: '';

		if ( 'AVG' === $function ) {
			$sql = "SELECT COUNT(id) / COUNT(DISTINCT requester_id) AS average
					FROM {$this->query_vars['table']}
					WHERE 1=1 {$this->query_vars['status_sql']} {$user} {$requester} {$email} {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}";
		} else {
			$sql = "SELECT COUNT(id)
					FROM {$this->query_vars['table']}
					WHERE 1=1 {$this->query_vars['status_sql']} {$user} {$requester} {$email} {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}";
		}
		$result = $this->get_db()->get_var( $sql );

		$total = null === $result
			? 0
			: absint( $result );

		// Reset query vars.
		$this->post_query();
		return $total;
	}

	/**
	 * Calculate the average age of a requester.
	 *
	 * @since 1.0.0
	 *
	 * @see \Intercessor\Stats::get_prayer_count()
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start       Start day and time (based on the beginning of the given day).
	 *     @type string $end         End day and time (based on the end of the given day).
	 *     @type string $range       Date range. If a range is passed, this will override and `start` and `end`
	 *                               values passed. See \Intercessor\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function    This method does not allow any SQL functions to be passed.
	 *     @type string $where_sql   Reserved for internal use. Allows for additional WHERE clauses to be appended
	 *                               to the query.
	 *     @type string $output      The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return int|float Average age of a requester.
	 */
	public function get_requester_age( $query = [] ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->ipr_requesters;
		$this->query_vars['column']            = 'id';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		$sql = "SELECT AVG(DATEDIFF(NOW(), date_created))
				FROM {$this->query_vars['table']}
				WHERE 1=1 {$this->query_vars['date_query_sql']}";

		$result = $this->get_db()->get_var( $sql );

		// Reset query vars.
		$this->post_query();

		return null === $result
			? 0
			: round( $result, 2 );
	}

	/** Private Methods ******************************************************/

	/**
	 * Parse query vars to be passed to the calculation methods.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @see \Intercessor\Stats::__construct()
	 *
	 * @param array $query Array of arguments. See \Intercessor\Stats::__construct().
	 */
	private function parse_query( $query = [] ) {
		$query_var_defaults = [
			'start'             => '',
			'end'               => '',
			'range'             => '',
			'status'            => [ 'pending', 'personal', 'active', 'archived' ],
			'status_sql'        => '',
			'type'              => [],
			'type_sql'          => '',
			'where_sql'         => '',
			'date_query_sql'    => '',
			'date_query_column' => '',
			'column'            => '',
			'table'             => '',
			'function'          => 'SUM',
			'output'            => 'raw',
			'relative'          => false,
			'relative_start'    => '',
			'relative_end'      => '',
			'grouped'           => false,
			'prayer_id'         => '',
			'requester_id'      => '',
		];

		if ( empty( $this->query_vars ) ) {
			$this->query_vars_defaults = $this->query_vars = wp_parse_args( $query, $query_var_defaults );
		} else {
			$this->query_vars = wp_parse_args( $query, $this->query_vars );
		}

		// Use Carbon to set up start and end date based on range passed.
		if ( ! empty( $this->query_vars['range'] ) && isset( $this->date_ranges[ $this->query_vars['range'] ] ) ) {
			if ( ! empty( $this->date_ranges[ $this->query_vars['range'] ]['start'] ) ) {
				$this->query_vars['start'] = $this->date_ranges[ $this->query_vars['range'] ]['start']->format( 'mysql' );
			}

			if ( ! empty( $this->date_ranges[ $this->query_vars['range'] ]['end'] ) ) {
				$this->query_vars['end'] = $this->date_ranges[ $this->query_vars['range'] ]['end']->format( 'mysql' );
			}
		}

		// Use Carbon to set up start and end date based on range passed.
		if ( true === $this->query_vars['relative'] && ! empty( $this->query_vars['range'] ) && isset( $this->relative_date_ranges[ $this->query_vars['range'] ] ) ) {
			if ( ! empty( $this->relative_date_ranges[ $this->query_vars['range'] ]['start'] ) ) {
				$this->query_vars['relative_start'] = $this->relative_date_ranges[ $this->query_vars['range'] ]['start']->format( 'mysql' );
			}

			if ( ! empty( $this->relative_date_ranges[ $this->query_vars['range'] ]['end'] ) ) {
				$this->query_vars['relative_end'] = $this->relative_date_ranges[ $this->query_vars['range'] ]['end']->format( 'mysql' );
			}
		}

		// Correctly format functions and column names.
		if ( ! empty( $this->query_vars['function'] ) ) {
			$this->query_vars['function'] = strtoupper( $this->query_vars['function'] );
		}

		if ( ! empty( $this->query_vars['column'] ) ) {
			$this->query_vars['column'] = strtolower( $this->query_vars['column'] );
		}

		/**
		 * Fires after the item query vars have been parsed.
		 *
		 * @since 1.0.0
		 *
		 * @param \Intercessor\Stats &$this The \Intercessor\Stats (passed by reference).
		 */
		do_action_ref_array( 'intercessor_prayer_stats_parse_query', array( &$this ) );
	}

	/**
	 * Ensures arguments exist before going ahead and calculating statistics.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param array $query
	 */
	private function pre_query( $query = [] ) {

		// Maybe parse query.
		if ( ! empty( $query ) ) {
			$this->parse_query( $query );
		}

		// Generate date query SQL if dates have been set.
		if ( ! empty( $this->query_vars['start'] ) || ! empty( $this->query_vars['end'] ) ) {
			$date_query_sql = ' AND ';

			if ( ! empty( $this->query_vars['start'] ) ) {
				$date_query_sql .= "{$this->query_vars['table']}.{$this->query_vars['date_query_column']} ";
				$date_query_sql .= $this->get_db()->prepare( '>= %s', $this->query_vars['start'] );
			}

			// Join dates with `AND` if start and end date set.
			if ( ! empty( $this->query_vars['start'] ) && ! empty( $this->query_vars['end'] ) ) {
				$date_query_sql .= ' AND ';
			}

			if ( ! empty( $this->query_vars['end'] ) ) {
				$date_query_sql .= $this->get_db()->prepare( "{$this->query_vars['table']}.{$this->query_vars['date_query_column']} <= %s", $this->query_vars['end'] );
			}

			$this->query_vars['date_query_sql'] = $date_query_sql;
		}

		// Generate status SQL if statuses have been set.
		if ( ! empty( $this->query_vars['status'] ) ) {
			if ( 'any' === $this->query_vars['status'] ) {
				$this->query_vars['status_sql'] = '';
			} else {
				$this->query_vars['status'] = array_map( 'sanitize_text_field', $this->query_vars['status'] );

				$placeholders = implode( ', ', array_fill( 0, count( $this->query_vars['status'] ), '%s' ) );

				$this->query_vars['status_sql'] = $this->get_db()->prepare( "AND {$this->query_vars['table']}.status IN ({$placeholders})", $this->query_vars['status'] );
			}
		}

		if ( ! empty( $this->query_vars['type'] ) ) {

			// We always want to format this as an array, so account for a possible string.
			if ( ! is_array( $this->query_vars['type'] ) ) {
				$this->query_vars['type'] = array( $this->query_vars['type'] );
			}

			$this->query_vars['type'] = array_map( 'sanitize_text_field', $this->query_vars['type'] );

			$placeholders = implode( ', ', array_fill( 0, count( $this->query_vars['type'] ), '%s' ) );

			$this->query_vars['type_sql'] = $this->get_db()->prepare( "AND {$this->query_vars['table']}.type IN ({$placeholders})", $this->query_vars['type'] );
		}
	}

	/**
	 * Runs after a query. Resets query vars back to the originals passed in via the constructor.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function post_query() {
		$this->query_vars = $this->query_var_originals;
	}

	/**
	 * Format the data if requested via the query parameter.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param mixed $data Data to format.
	 *
	 * @return mixed Raw or formatted data depending on query parameter.
	 */
	private function maybe_format( $data = null ) {

		// Bail if nothing was passed.
		if ( null === $data ) {
			return $data;
		}

		$allowed_output_formats = array( 'raw', 'formatted' );

		// Output format. Default raw.
		$output = isset( $this->query_vars['output'] ) && in_array( $this->query_vars['output'], $allowed_output_formats, true )
			? $this->query_vars['output']
			: 'raw';

		// Return data as is if the format is raw.
		if ( 'raw' === $output ) {
			return $data;
		}

		return $data;
	}

	/**
	 * Generate date query SQL for relative time periods.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return string Date query SQL.
	 */
	private function generate_relative_date_query_sql() {

		// Bail if relative calculation not requested.
		if ( false === $this->query_vars['relative'] ) {
			return '';
		}

		// Generate date query SQL if dates have been set.
		if ( ! empty( $this->query_vars['relative_start'] ) || ! empty( $this->query_vars['relative_end'] ) ) {
			$date_query_sql = "AND {$this->query_vars['table']}.{$this->query_vars['date_query_column']} ";

			if ( ! empty( $this->query_vars['relative_start'] ) ) {
				$date_query_sql .= $this->get_db()->prepare( '>= %s', $this->query_vars['relative_start'] );
			}

			// Join dates with `AND` if start and end date set.
			if ( ! empty( $this->query_vars['relative_start'] ) && ! empty( $this->query_vars['relative_end'] ) ) {
				$date_query_sql .= ' AND ';
			}

			if ( ! empty( $this->query_vars['relative_end'] ) ) {
				$date_query_sql .= $this->get_db()->prepare( "{$this->query_vars['table']}.{$this->query_vars['date_query_column']} <= %s", $this->query_vars['relative_end'] );
			}

			return $date_query_sql;
		}
	}

	/** Private Getters *******************************************************/

	/**
	 * Return the global database interface.
	 *
	 * @since 1.0.0
	 * @access private
	 * @static
	 *
	 * @return \wpdb|\stdClass
	 */
	private static function get_db() {
		return isset( $GLOBALS['wpdb'] )
			? $GLOBALS['wpdb']
			: new \stdClass();
	}

	/** Private Setters ******************************************************/

	/**
	 * Set up the date ranges available.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function set_date_ranges() {

		// Retrieve the time in UTC for the date ranges to be correctly parsed.
		$date = intercessor_date_i18n( time(), 'mysql' );

		$date_filters = $this->get_dates_filter_options();

		foreach ( $date_filters as $range => $label ) {
			$this->date_ranges[ $range ] = \intercessor_get_report_dates();

			switch ( $range ) {
				case 'this_month':
					$dates = array(
						'start' => $date->copy()->subMonth( 1 )->startOfMonth(),
						'end'   => $date->copy()->subMonth( 1 )->endOfMonth(),
					);
					break;
				case 'last_month':
					$dates = array(
						'start' => $date->copy()->subMonth( 2 )->startOfMonth(),
						'end'   => $date->copy()->subMonth( 2 )->endOfMonth(),
					);
					break;
				case 'today':
					$dates = array(
						'start' => $date->copy()->subDay( 1 )->startOfDay(),
						'end'   => $date->copy()->subDay( 1 )->endOfDay(),
					);
					break;
				case 'yesterday':
					$dates = array(
						'start' => $date->copy()->subDay( 2 )->startOfDay(),
						'end'   => $date->copy()->subDay( 2 )->endOfDay(),
					);
					break;
				case 'this_week':
					$dates = array(
						'start' => $date->copy()->subWeek( 1 )->startOfWeek(),
						'end'   => $date->copy()->subWeek( 1 )->endOfWeek(),
					);
					break;
				case 'last_week':
					$dates = array(
						'start' => $date->copy()->subWeek( 2 )->startOfWeek(),
						'end'   => $date->copy()->subWeek( 2 )->endOfWeek(),
					);
					break;
				case 'last_30_days':
					$dates = array(
						'start' => $date->copy()->subDay( 60 )->startOfDay(),
						'end'   => $date->copy()->subDay( 30 )->endOfDay(),
					);
					break;
				case 'this_quarter':
					$dates = array(
						'start' => $date->copy()->subQuarter( 1 )->startOfQuarter(),
						'end'   => $date->copy()->subQuarter( 1 )->endOfQuarter(),
					);
					break;
				case 'last_quarter':
					$dates = array(
						'start' => $date->copy()->subQuarter( 2 )->startOfQuarter(),
						'end'   => $date->copy()->subQuarter( 2 )->endOfQuarter(),
					);
					break;
				case 'this_year':
					$dates = array(
						'start' => $date->copy()->subYear( 1 )->startOfYear(),
						'end'   => $date->copy()->subYear( 1 )->endOfYear(),
					);
					break;
				case 'last_year':
					$dates = array(
						'start' => $date->copy()->subYear( 2 )->startOfYear(),
						'end'   => $date->copy()->subYear( 2 )->endOfYear(),
					);
					break;
			}

			$dates['range'] = $range;

			$this->relative_date_ranges[ $range ] = $dates;
		}
	}

	/**
	 * Retrieves the start and end date filters for use with the Reports API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $values   Optional. What format to retrieve dates in the resulting array in.
	 *                         Accepts 'strings' or 'objects'. Default 'strings'.
	 * @param string $timezone Optional. Timezone to force for filter dates. Primarily used for
	 *                         legacy testing purposes. Default empty.
	 * @return array|\EDD\Utils\Date[] {
	 *     Query date range for the current graph filter request.
	 *
	 *     @type string|\EDD\Utils\Date $start Start day and time (based on the beginning of the given day).
	 *                                         If `$values` is 'objects', a Carbon object, otherwise a date
	 *                                         time string.
	 *     @type string|\EDD\Utils\Date $end   End day and time (based on the end of the given day). If `$values`
	 *                                         is 'objects', a Carbon object, otherwise a date time string.
	 * }
	 */
	public function get_dates_filter( $values = 'strings', $timezone = null ) {
		$dates = \intercessor_get_report_dates();

		if ( 'strings' === $values ) {
			if ( ! empty( $dates['start'] ) ) {
				$dates['start'] = $dates['start']->toDateTimeString();
			}
			if ( ! empty( $dates['end'] ) ) {
				$dates['end'] = $dates['end']->toDateTimeString();
			}
		}

		/**
		 * Filters the start and end date filters for use with the Graphs API.
		 *
		 * @since 1.0.0
		 *
		 * @param array|\EDD\Utils\Date[] $dates {
		 *     Query date range for the current graph filter request.
		 *
		 *     @type string|\EDD\Utils\Date $start Start day and time (based on the beginning of the given day).
		 *                                         If `$values` is 'objects', a Date object, otherwise a date
		 *                                         time string.
		 *     @type string|\EDD\Utils\Date $end   End day and time (based on the end of the given day). If `$values`
		 *                                         is 'objects', a Date object, otherwise a date time string.
		 * }
		 */
		return apply_filters( 'intercessor_get_dates_filter', $dates );
	}

	/**
	 * Parses start and end dates for the given range.
	 *
	 * @since 1.0.0
	 *
	 * @param string          $range Optional. Range value to generate start and end dates for against `$date`.
	 *                               Default is the current range as derived from the session.
	 * @param string          $date  Date string converted to `\EDD\Utils\Date` to anchor calculations to.
	 * @return  Array of start and end date objects.
	 */
	public function parse_dates_for_range( $range = null ) {

		// Set the time ranges in the user's timezone, so they ultimately see them in their own timezone.
		$date = intercessor_date_i18n( time(), 'mysql' );


		if ( null === $range || ! array_key_exists( $range, $this->get_dates_filter_options() ) ) {
			$range = $this->get_dates_filter_range();
		}

		switch ( $range ) {

			case 'this_month':
				$dates = array(
					'start' => $date->copy()->startOfMonth(),
					'end'   => $date->copy()->endOfMonth(),
				);
				break;

			case 'last_month':
				$dates = array(
					'start' => $date->copy()->subMonthNoOverflow( 1 )->startOfMonth(),
					'end'   => $date->copy()->subMonthNoOverflow( 1 )->endOfMonth(),
				);
				break;

			case 'today':
				$dates = array(
					'start' => $date->copy()->startOfDay(),
					'end'   => $date->copy()->endOfDay(),
				);
				break;

			case 'yesterday':
				$dates = array(
					'start' => $date->copy()->subDay( 1 )->startOfDay(),
					'end'   => $date->copy()->subDay( 1 )->endOfDay(),
				);
				break;

			case 'this_week':
				$dates = array(
					'start' => $date->copy()->startOfWeek(),
					'end'   => $date->copy()->endOfWeek(),
				);
				break;

			case 'last_week':
				$dates = array(
					'start' => $date->copy()->subWeek( 1 )->startOfWeek(),
					'end'   => $date->copy()->subWeek( 1 )->endOfWeek(),
				);
				break;

			case 'last_30_days':
				$dates = array(
					'start' => $date->copy()->subDay( 30 )->startOfDay(),
					'end'   => $date->copy()->endOfDay(),
				);
				break;

			case 'this_quarter':
				$dates = array(
					'start' => $date->copy()->startOfQuarter(),
					'end'   => $date->copy()->endOfQuarter(),
				);
				break;

			case 'last_quarter':
				$dates = array(
					'start' => $date->copy()->subQuarter( 1 )->startOfQuarter(),
					'end'   => $date->copy()->subQuarter( 1 )->endOfQuarter(),
				);
				break;

			case 'this_year':
				$dates = array(
					'start' => $date->copy()->startOfYear(),
					'end'   => $date->copy()->endOfYear(),
				);
				break;

			case 'last_year':
				$dates = array(
					'start' => $date->copy()->subYear( 1 )->startOfYear(),
					'end'   => $date->copy()->subYear( 1 )->endOfYear(),
				);
				break;

			case 'other':
			default:
				$dates_from_report = get_filter_value( 'dates' );

				if ( ! empty( $dates_from_report ) ) {
					$start = $dates_from_report['from'];
					$end   = $dates_from_report['to'];
				} else {
					$start = $end = 'now';
				}

				$dates = array(
					'start' => intercessor()->utils->date( $start, intercessor_get_timezone_id(), false )->startOfDay(),
					'end'   => intercessor()->utils->date( $end, intercessor_get_timezone_id(), false )->endOfDay(),
				);
				break;
		}

		// Convert the values to the UTC equivalent so that we can query the database using UTC.
		$dates['start'] = intercessor_get_utc_equivalent_date( $dates['start'] );
		$dates['end']   = intercessor_get_utc_equivalent_date( $dates['end'] );

		$dates['range'] = $range;

		return $dates;
	}

	/**
	 * Retrieves the date filter range.
	 *
	 * @since 1.0.0
	 *
	 * @return string Date filter range.
	 */
	public function get_dates_filter_range() {

		$dates = get_filter_value( 'dates' );

		if ( isset( $dates['range'] ) ) {
			$range = sanitize_key( $dates['range'] );

		} else {

			/**
			 * Filters the report dates default range.
			 *
			 * @since 1.3
			 *
			 * @param string $range Date range as derived from the session. Default 'last_30_days'
			 * @param array  $dates Dates filter data array.
			 */
			$range = apply_filters( 'intercessor_get_report_dates_default_range', 'last_30_days', $dates );
		}

		/**
		 * Filters the dates filter range.
		 *
		 * @since 1.0.0
		 *
		 * @param string $range Dates filter range.
		 * @param array  $dates Dates filter data array.
		 */
		return apply_filters( 'intercessor_get_dates_filter_range', $range, $dates );
	}

	/**
	 * Determines whether results should be displayed hour by hour, or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if results should use hour by hour, otherwise false.
	 */
	function get_dates_filter_hour_by_hour() {
		// Retrieve the queried dates
		$dates = $this->get_dates_filter( 'objects' );

		// Determine graph options
		switch ( $dates['range'] ) {
			case 'today':
			case 'yesterday':
				$hour_by_hour = true;
				break;
			default:
				$hour_by_hour = false;
				break;
		}

		return $hour_by_hour;
	}

	/**
	 * Determines whether results should be displayed day by day or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if results should use day by day, otherwise false.
	 */
	function get_dates_filter_day_by_day() {
		// Retrieve the queried dates
		$dates = $this->get_dates_filter( 'objects' );

		// Determine graph options
		switch ( $dates['range'] ) {
			case 'today':
			case 'yesterday':
			case 'last_quarter':
			case 'this_quarter':
			case 'this_year':
			case 'last_year':
				$day_by_day = false;
				break;
			case 'other':
				$difference = ( $dates['end']->getTimestamp() - $dates['start']->getTimestamp() );

				if ( $difference >= ( YEAR_IN_SECONDS / 4 ) ) {
					$day_by_day = false;
				} else {
					$day_by_day = true;
				}
				break;
			default:
				$day_by_day = true;
				break;
		}

		return $day_by_day;
	}


	/**
	 * Retrieves key/label pairs of date filter options for use in a drop-down.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Key/label pairs of date filter options.
	 */
	public function get_dates_filter_options() {
		static $options = null;

		if ( is_null( $options ) ) {
			$options = [
				'other'        => esc_html__( 'Custom', 'intercessor' ),
				'today'        => esc_html__( 'Today', 'intercessor' ),
				'yesterday'    => esc_html__( 'Yesterday', 'intercessor' ),
				'this_week'    => esc_html__( 'This Week', 'intercessor' ),
				'last_week'    => esc_html__( 'Last Week', 'intercessor' ),
				'last_30_days' => esc_html__( 'Last 30 Days', 'intercessor' ),
				'this_month'   => esc_html__( 'This Month', 'intercessor' ),
				'last_month'   => esc_html__( 'Last Month', 'intercessor' ),
				'this_quarter' => esc_html__( 'This Quarter', 'intercessor' ),
				'last_quarter' => esc_html__( 'Last Quarter', 'intercessor' ),
				'this_year'    => esc_html__( 'This Year', 'intercessor' ),
				'last_year'    => esc_html__( 'Last Year', 'intercessor' ),
			];
		}

		/**
		 * Filters the list of key/label pairs of date filter options.
		 *
		 * @since 1.0.0
		 *
		 * @param array $options Date filter options.
		 */
		return apply_filters( 'intercessor_report_date_options', $options );
	}
}
