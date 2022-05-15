<?php
/**
 * Prayer Query class.
 *
 * @package     Intercessor
 * @subpackage  Database/Queries/Prayers
 * @copyright   Copyright (c) 2020, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       1.0.0
 */

namespace Intercessor\Database\Queries;

use Intercessor\Database\Query;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class used for querying prayers.
 *
 * @since 1.0.0
 *
 * @see \Intercessor\Database\Query::__construct() for accepted arguments.
 */
class Prayer extends Query {

	/** Table Properties ******************************************************/

	/**
	 * Name of the database table to query.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	protected $table_name = 'prayers';

	/**
	 * String used to alias the database table in MySQL statement.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	protected $table_alias = 'p';

	/**
	 * Name of class used to setup the database schema
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	protected $table_schema = '\\Intercessor\\Database\\Schemas\\Prayers';

	/** Item ******************************************************************/

	/**
	 * Name for a single item
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	protected $item_name = 'prayer';

	/**
	 * Plural version for a group of items.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	protected $item_name_plural = 'prayers';

	/**
	 * Callback function for turning IDs into objects
	 *
	 * @since 1.0.0
	 * @access public
	 * @var mixed
	 */
	protected $item_shape = '\\Intercessor\\Prayer';

	/** Cache *****************************************************************/

	/**
	 * Group to cache queries and queried items in.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	protected $cache_group = 'prayers';

	/** Methods ***************************************************************/

	/**
	 * Sets up the prayer query, based on the query vars passed.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string|array $query {
	 *     Optional. Array or query string of prayer query parameters. Default empty.
	 *
	 *     @type int          $id                   An prayer ID to only return that prayer. Default empty.
	 *     @type array        $id__in               Array of prayer IDs to include. Default empty.
	 *     @type array        $id__not_in           Array of prayer IDs to exclude. Default empty.
	 *     @type int          $requester_id          A requester ID to only return that object. Default empty.
	 *     @type array        $requester_id__in      Array of requester IDs to include. Default empty.
	 *     @type array        $requester_id__not_in  Array of requester IDs to exclude. Default empty.
	 *     @type int          $user_id              A user ID to only return that object. Default empty.
	 *     @type array        $user_id__in          Array of user IDs to include. Default empty.
	 *     @type array        $user_id__not_in      Array of user IDs to exclude. Default empty.
	 *     @type string       $email                Limit results to those affiliated with a given email. Default empty.
	 *     @type array        $email__in            Array of email to include affiliated prayers for. Default empty.
	 *     @type array        $email__not_in        Array of email to exclude affiliated prayers for. Default empty.
	 *     @type string       $title                An prayer title to only return that prayer. Default empty.
	 *     @type array        $title__in            Array of prayer titles to include. Default empty.
	 *     @type array        $title__not_in        Array of prayer titles to exclude. Default empty.
	 *     @type string       $message             Limit results to those affiliated with a given message. Default empty.
	 *     @type array        $message__in          Array of messages to include affiliated prayers for. Default empty.
	 *     @type array        $message__not_in      Array of messages to exclude affiliated prayers for. Default empty.
	 *     @type string       $status               A prayer status to only return that prayer. Default empty.
	 *     @type array        $status__in           Array of prayer statuses to include. Default empty.
	 *     @type array        $status__not_in       Array of prayer statuses to exclude. Default empty.
	 *     @type string       $prayer_key          Limit results to those affiliated with a given prayer key. Default empty.
	 *     @type array        $prayer_key__in      Array of prayer keys to include affiliated prayers for. Default empty.
	 *     @type array        $prayer_key__not_in  Array of prayer keys to exclude affiliated prayers for. Default empty.
	 *     @type string       $share                 Limit results to those affiliated with a given share. Default empty.
	 *     @type array        $share__in             Array of shares to include affiliated prayers for. Default empty.
	 *     @type array        $share__not_in         Array of shares to exclude affiliated prayers for. Default empty.
	 *     @type string       $notify             Limit results to those affiliated with a given notify. Default empty.
	 *     @type array        $notify__in         Array of currencies to include affiliated prayers for. Default empty.
	 *     @type array        $notify__not_in     Array of currencies to exclude affiliated prayers for. Default empty.
	 *     @type array        $date_query           Query all datetime columns together. See WP_Date_Query.
	 *     @type array        $date_created_query   Date query clauses to limit prayers by. See WP_Date_Query.
	 *                                              Default null.
	 *     @type array        $date_active_query    Date query clauses to limit prayers by. See WP_Date_Query.
	 *                                              Default null.
	 *     @type string       $fields               Item fields to return. Accepts any column known names
	 *                                              or empty (returns an array of complete prayer objects). Default empty.
	 *     @type int          $number               Limit number of prayers to retrieve. Default 100.
	 *     @type int          $offset               Number of prayers to offset the query. Used to build LIMIT clause.
	 *                                              Default 0.
	 *     @type bool         $no_found_rows        Whether to disable the `SQL_CALC_FOUND_ROWS` query. Default true.
	 *     @type string|array $orderby              Accepts 'id', 'number', 'status', 'user_id', 'requester_id', 'email', 'message'
	 *                                              'prayer_key', 'date_created', 'date_active', 'user_id__in', 'requester_id__in'.
	 *                                              'email__in', 'message__in', 'prayer_key__in'.
	 *                                              Also accepts false, an empty array, or 'none' to disable `ORDER BY` clause.
	 *                                              Default 'id'.
	 *     @type string       $order                How to order retrieved prayers. Accepts 'ASC', 'DESC'. Default 'DESC'.
	 *     @type string       $search               Search term(s) to retrieve matching prayers for. Default empty.
	 *     @type bool         $update_cache         Whether to prime the cache for found prayers. Default false.
	 * }
	 */
	public function __construct( $query = [] ) {

		// Check for prayers with 'publish' status and convert to active.
		if ( isset( $query['status'] ) ) {
			if ( is_array( $query['status'] ) && in_array( 'publish', $query['status'], true ) ) {
				foreach ( $query['status'] as $key => $status ) {
					if ( 'publish' === $status ) {
						unset( $query['status'][ $key ] );
					}
				}

				$query['status'][] = 'active';
			} elseif ( 'publish' === $query['status'] ) {
				$query['status'] = 'active';
			}
		}

		parent::__construct( $query );
	}

	/**
	 * Set up the filter callback to add the country and region from the order addresses table.
	 *
	 * @param string|array $query {
	 *     Optional. Array or query string of prayer query parameters. Default empty.
	 *
	 * @type int    $id                   An prayer ID to only return that prayer. Default empty.
	 * @type array  $id__in               Array of prayer IDs to include. Default empty.
	 * @type array  $id__not_in           Array of prayer IDs to exclude. Default empty.
	 * @type string $status               An prayer status to only return that prayer. Default empty.
	 * @type array  $status__in           Array of prayer statuses to include. Default empty.
	 * @type array  $status__not_in       Array of prayer statuses to exclude. Default empty.
	 * @type string $title                Prayer title. Default empty.
	 * @type array  $title__in            Array of prayer titles to include. Default empty.
	 * @type array  $title__not_in        Array of prayer titles to exclude. Default empty.
	 * @type int    $user_id              A user ID to only return that object. Default empty.
	 * @type array  $user_id__in          Array of user IDs to include. Default empty.
	 * @type array  $user_id__not_in      Array of user IDs to exclude. Default empty.
	 * @type int    $requester_id         A requester ID to only return that object. Default empty.
	 * @type array  $requester_id__in     Array of requester IDs to include. Default empty.
	 * @type array  $requester_id__not_in Array of requester IDs to exclude. Default empty.
	 * @type string $email                Limit results to those affiliated with a given email. Default empty.
	 * @type array  $email__in            Array of email to include affiliated prayers for. Default empty.
	 * @type array  $email__not_in        Array of email to exclude affiliated prayers for. Default empty.
	 * @type string $message              Limit results to those affiliated with a given message. Default empty.
	 * @type array  $message__in          Array of messages to include affiliated prayers for. Default empty.
	 * @type array  $message__not_in      Array of messages to exclude affiliated prayers for. Default empty.
	 * @type string $share                Limit results to those affiliated with a given share. Default empty.
	 * @type array  $share__in            Array of shares to include affiliated prayers for. Default empty.
	 * @type array  $share__not_in        Array of shares to exclude affiliated prayers for. Default empty.
	 * @type string $notify               Limit results to those affiliated with a given notify. Default empty.
	 * @type array  $notify__in           Array of currencies to include affiliated prayers for. Default empty.
	 * @type array  $notify__not_in       Array of currencies to exclude affiliated prayers for. Default empty.
	 * @type string $prayer_key           Limit results to those affiliated with a given prayer key. Default empty.
	 * @type array  $prayer_key__in       Array of prayer keys to include affiliated prayers for. Default empty.
	 * @type array  $prayer_key__not_in   Array of prayer keys to exclude affiliated prayers for. Default empty.
	 * @type array  $date_query           Query all datetime columns together. See WP_Date_Query.
	 * @type array  $date_created_query   Date query clauses to limit prayers by. See WP_Date_Query.
	 *                                              Default null.
	 * @type array  $date_active_query    Date query clauses to limit prayers by. See WP_Date_Query.
	 *                                              Default null.
	 * @type bool   $count                Whether to return a prayer count (true) or array of prayer objects.
	 *                                              Default false.
	 * @type string $fields               Item fields to return. Accepts any column known names
	 *                                              or empty (returns an array of complete prayer objects). Default empty.
	 * @type int    $number               Limit number of prayers to retrieve. Default 100.
	 * @type int    $offset               Number of prayers to offset the query. Used to build LIMIT clause.
	 *                                              Default 0.
	 * @type bool   $no_found_rows        Whether to disable the `SQL_CALC_FOUND_ROWS` query. Default true.
	 * @type string|array $orderby        Accepts 'id', 'number', 'status', 'user_id', 'requester_id', 'email', 'message'
	 *                                              'prayer_key', 'date_created', 'date_active', 'user_id__in', 'requester_id__in'.
	 *                                              'email__in', 'message__in', 'prayer_key__in'.
	 *                                              Also accepts false, an empty array, or 'none' to disable `ORDER BY` clause.
	 *                                              Default 'id'.
	 * @type string $order How to order retrieved prayers. Accepts 'ASC', 'DESC'. Default 'DESC'.
	 * @type string $search Search term(s) to retrieve matching prayers for. Default empty.
	 * @type bool   $update_cache Whether to prime the cache for found prayers. Default false.
	 * }
	 *
	 * @return array|int
	 * @since 1.0.0
	 * @access public
	 *
	 */
	public function query( $query = [] ) {
		$result = parent::query( $query );

		return $result;
	}
}
