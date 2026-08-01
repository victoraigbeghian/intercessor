<?php
/**
 * Prayed counts CSV exporter.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Tools;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\Admin\Settings;
use Intercessor\Database\Query\Prayed_Count_Query;
use Intercessor\Database\Query\Prayer_Request_Query;

/**
 * Exports prayed_counts data to a timestamped CSV file in one of two modes.
 *
 * Mode is controlled by the 'export_prayed_mode' plugin setting:
 *
 * - **aggregated** (default): One row per prayer request. Columns are Prayer
 *   Request ID, Subject, Status, Total Prayers. Uses getAggregatedTotals() for
 *   a single GROUP BY query.
 *
 * - **detailed**: One row per individual prayed_count actor record. Columns
 *   include the per-record count, actor user ID, WordPress username, and the
 *   anonymous fingerprint key. Prayer request lookups are cached in a local
 *   array to avoid N+1 queries.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Prayed_Counts_Exporter extends Abstract_Exporter {

	/**
	 * Return the timestamped CSV download filename.
	 *
	 * @since  1.0.0
	 * @return string Filename in the format 'intercessor-prayed-counts-YYYY-MM-DD.csv'.
	 */
	protected function get_filename(): string {
		return sprintf( 'intercessor-prayed-counts-%s.csv', gmdate( 'Y-m-d' ) );
	}

	/**
	 * Return the ordered column header labels appropriate for the current export mode.
	 *
	 * Returns a four-column set in aggregated mode and a nine-column set in
	 * detailed mode to match the differing row structures produced by getRows().
	 *
	 * @since  1.0.0
	 * @return string[] Ordered list of translated column header labels.
	 */
	protected function get_headers(): array {
		if ( $this->is_aggregated_mode() ) {
			return array(
				__( 'Prayer Request ID', 'intercessor' ),
				__( 'Subject',           'intercessor' ),
				__( 'Status',            'intercessor' ),
				__( 'Total Prayers',     'intercessor' ),
			);
		}

		return array(
			__( 'ID',                     'intercessor' ),
			__( 'Prayer Request ID',      'intercessor' ),
			__( 'Prayer Request Subject', 'intercessor' ),
			__( 'User ID',                'intercessor' ),
			__( 'Username',               'intercessor' ),
			__( 'Anonymous Key',          'intercessor' ),
			__( 'Prayer Count',           'intercessor' ),
			__( 'First Prayed',           'intercessor' ),
			__( 'Last Prayed',            'intercessor' ),
		);
	}

	/**
	 * Build and return all CSV data rows, delegating to the mode-specific method.
	 *
	 * @since  1.0.0
	 * @return array<int, array<int, scalar>> Indexed list of CSV row value arrays.
	 */
	protected function get_rows(): array {
		return $this->is_aggregated_mode()
			? $this->get_aggregated_rows()
			: $this->get_detailed_rows();
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Return true when the 'aggregated' export mode is active.
	 *
	 * Any value other than 'detailed' (including the default 'aggregated')
	 * is treated as aggregated mode.
	 *
	 * @since  1.0.0
	 * @return bool True for aggregated mode, false for detailed mode.
	 */
	private function is_aggregated_mode(): bool {
		$mode = Settings::get( 'export_prayed_mode', 'aggregated' );
		return $mode !== 'detailed';
	}

	/**
	 * Build one CSV row per prayer request containing the summed prayer total.
	 *
	 * Uses Prayed_Count_Query::get_aggregated_totals() which executes a single
	 * GROUP BY query, then fetches the matching prayer request row to populate
	 * the subject and status columns.
	 *
	 * @since  1.0.0
	 * @return array<int, array<int, scalar>> Indexed list of four-column row value arrays.
	 */
	private function get_aggregated_rows(): array {
		$countQuery  = new Prayed_Count_Query();
		$prayerQuery = new Prayer_Request_Query();

		$aggregated = $countQuery->get_aggregated_totals();
		$rows       = array();

		foreach ( $aggregated as $agg ) {
			$request = $prayerQuery->get_item( $agg['prayer_request_id'] );

			$rows[] = array(
				$agg['prayer_request_id'],
				$request ? $request->subject : '',
				$request ? $request->status  : '',
				$agg['total'],
			);
		}

		return $rows;
	}

	/**
	 * Build one CSV row per individual prayed_count actor record.
	 *
	 * Fetches all records ordered by prayer_request_id to make the prayer
	 * request lookup cache as effective as possible. The cache stores
	 * PrayerRequest objects keyed by their primary key, preventing duplicate
	 * queries when multiple actor rows share the same request.
	 *
	 * @since  1.0.0
	 * @return array<int, array<int, scalar>> Indexed list of nine-column row value arrays.
	 */
	private function get_detailed_rows(): array {
		$countQuery  = new Prayed_Count_Query();
		$prayerQuery = new Prayer_Request_Query();

		$records = $countQuery->get_items(
			array(
				'number'  => 0,
				'orderby' => 'prayer_request_id',
				'order'   => 'ASC',
			)
		);

		/** @var array<int, \Intercessor\Database\Row\Prayer_Request|false> $requestCache */
		$requestCache = array();
		$rows         = array();

		foreach ( $records as $record ) {
			$reqId = $record->prayer_request_id;

			if ( ! array_key_exists( $reqId, $requestCache ) ) {
				$requestCache[ $reqId ] = $prayerQuery->get_item( $reqId );
			}

			$request = $requestCache[ $reqId ];

			$username = '';
			if ( $record->user_id > 0 ) {
				$wpUser   = get_user_by( 'id', $record->user_id );
				$username = $wpUser ? $wpUser->user_login : __( '[deleted]', 'intercessor' );
			}

			$rows[] = array(
				$record->id,
				$reqId,
				$request ? $request->subject : '',
				$record->user_id > 0 ? $record->user_id : '',
				$username,
				$record->anonymous_key,
				$record->count,
				$record->date_created,
				$record->date_modified,
			);
		}

		return $rows;
	}
}
