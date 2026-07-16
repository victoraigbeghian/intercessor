<?php
/**
 * Requesters CSV exporter.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Tools;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\Database\Query\Requester_Query;
use Intercessor\Database\Query\Prayer_Request_Query;

/**
 * Exports the full requester database to a timestamped CSV file.
 *
 * Column set: ID, Name, Email, Status, WP User ID, WP Username,
 * Total Requests, Date Registered, Date Modified.
 *
 * For each requester, a count_items() call determines how many prayer
 * requests are linked to their ID. WordPress usernames are resolved via
 * get_user_by() for accounts still present in the users table; deleted
 * accounts display '[deleted]'.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Requesters_Exporter extends Abstract_Exporter {

	/**
	 * Return the timestamped CSV download filename.
	 *
	 * @since  1.0.0
	 * @return string Filename in the format 'intercessor-requesters-YYYY-MM-DD.csv'.
	 */
	protected function get_filename(): string {
		return sprintf( 'intercessor-requesters-%s.csv', gmdate( 'Y-m-d' ) );
	}

	/**
	 * Return the ordered column header labels for the CSV.
	 *
	 * @since  1.0.0
	 * @return string[] Ordered list of translated column header labels.
	 */
	protected function get_headers(): array {
		return array(
			__( 'ID',               'intercessor' ),
			__( 'First Name',       'intercessor' ),
			__( 'Last Name',        'intercessor' ),
			__( 'Display Name',     'intercessor' ),
			__( 'Email',            'intercessor' ),
			__( 'Status',           'intercessor' ),
			__( 'WP User ID',       'intercessor' ),
			__( 'WP Username',      'intercessor' ),
			__( 'Total Requests',   'intercessor' ),
			__( 'Date Registered',  'intercessor' ),
			__( 'Date Modified',    'intercessor' ),
		);
	}

	/**
	 * Fetch all requesters and build CSV rows.
	 *
	 * Fetches all requester rows without a limit, then for each row performs
	 * a count_items() query on the prayer_requests table and a get_user_by()
	 * lookup for linked WordPress accounts. Both secondary lookups run per
	 * row; export time scales linearly with the number of requesters.
	 *
	 * @since  1.0.0
	 * @return array<int, array<int, scalar>> Indexed list of CSV row value arrays.
	 */
	protected function get_rows(): array {
		$requesterQuery = new Requester_Query();
		$prayerQuery    = new Prayer_Request_Query();

		$requesters = $requesterQuery->get_items(
			array(
				'number'  => 0,
				'orderby' => 'id',
				'order'   => 'ASC',
			)
		);

		$rows = array();

		foreach ( $requesters as $requester ) {
			$wpUsername = '';
			if ( $requester->wp_user_id > 0 ) {
				$wpUser     = get_user_by( 'id', $requester->wp_user_id );
				$wpUsername = $wpUser ? $wpUser->user_login : __( '[deleted]', 'intercessor' );
			}

			$requestCount = $prayerQuery->count_items( array( 'requester_id' => $requester->id ) );

			$rows[] = array(
				$requester->id,
				$requester->get_first_name(),
				$requester->get_last_name(),
				$requester->get_display_name(),
				$requester->email,
				$requester->status,
				$requester->wp_user_id > 0 ? $requester->wp_user_id : '',
				$wpUsername,
				$requestCount,
				$requester->date_created,
				$requester->date_modified,
			);
		}

		return $rows;
	}
}
