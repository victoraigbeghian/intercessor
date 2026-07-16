<?php
/**
 * Prayer requests CSV exporter.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Tools;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\Admin\Settings;
use Intercessor\Database\Query\Prayer_Request_Query;
use Intercessor\Database\Query\Requester_Query;

/**
 * Exports all prayer requests to a timestamped CSV file.
 *
 * Column set: ID, Subject, Status, [Prayer Content], Requester Name,
 * Requester Email, Anonymous, Public, Moderator Note, Date Submitted,
 * Date Modified. The Prayer Content column is included or omitted based
 * on the 'export_include_content' plugin setting. The 'export_status_filter'
 * setting restricts the export to a single status when set.
 *
 * Anonymous requests display '[Anonymous]' in the name column and an
 * empty string in the email column regardless of what is stored.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Prayer_Requests_Exporter extends Abstract_Exporter {

	/**
	 * Return the timestamped CSV download filename.
	 *
	 * @since  1.0.0
	 * @return string Filename in the format 'intercessor-prayer-requests-YYYY-MM-DD.csv'.
	 */
	protected function get_filename(): string {
		return sprintf( 'intercessor-prayer-requests-%s.csv', gmdate( 'Y-m-d' ) );
	}

	/**
	 * Return the ordered column header labels for the CSV.
	 *
	 * When the 'export_include_content' setting is enabled, a 'Prayer Content'
	 * column is spliced in at position 3 (after Status, before Requester Name).
	 *
	 * @since  1.0.0
	 * @return string[] Ordered list of translated column header labels.
	 */
	protected function get_headers(): array {
		$headers = array(
			__( 'ID',             'intercessor' ),
			__( 'Subject',        'intercessor' ),
			__( 'Status',         'intercessor' ),
			__( 'Requester Name', 'intercessor' ),
			__( 'Requester Email','intercessor' ),
			__( 'Anonymous',      'intercessor' ),
			__( 'Public',         'intercessor' ),
			__( 'Moderator Note', 'intercessor' ),
			__( 'Date Submitted', 'intercessor' ),
			__( 'Date Modified',  'intercessor' ),
		);

		if ( Settings::get( 'export_include_content', true ) ) {
			array_splice( $headers, 3, 0, array( __( 'Prayer Content', 'intercessor' ) ) );
		}

		return $headers;
	}

	/**
	 * Fetch all prayer requests matching the configured status filter and build CSV rows.
	 *
	 * Respects both the 'export_status_filter' and 'export_include_content'
	 * settings. Resolves each requester's name and email via a secondary query,
	 * replacing both with appropriate values for anonymous submissions.
	 * All requests are fetched without a row limit (number = 0).
	 *
	 * @since  1.0.0
	 * @return array<int, array<int, scalar>> Indexed list of CSV row value arrays.
	 */
	protected function get_rows(): array {
		$prayerQuery    = new Prayer_Request_Query();
		$requesterQuery = new Requester_Query();

		$statusFilter = Settings::get( 'export_status_filter', '' );

		$args = array( 'number' => 0, 'orderby' => 'id', 'order' => 'ASC' );

		if ( $statusFilter !== '' && $statusFilter !== 'all' ) {
			$args['status'] = $statusFilter;
		}

		$requests       = $prayerQuery->get_items( $args );
		$includeContent = Settings::get( 'export_include_content', true );
		$rows           = array();

		foreach ( $requests as $request ) {
			$requesterName  = '';
			$requesterEmail = '';

			if ( ! $request->is_anonymous() && $request->requester_id > 0 ) {
				$requester = $requesterQuery->get_item( $request->requester_id );
				if ( $requester ) {
					$requesterName  = $requester->get_display_name();
					$requesterEmail = $requester->email;
				}
			} else {
				$requesterName = __( '[Anonymous]', 'intercessor' );
			}

			$row = array(
				$request->id,
				$request->subject,
				$request->status,
			);

			if ( $includeContent ) {
				$row[] = wp_strip_all_tags( $request->content );
			}

			$row[] = $requesterName;
			$row[] = $requesterEmail;
			$row[] = $request->is_anonymous() ? __( 'Yes', 'intercessor' ) : __( 'No', 'intercessor' );
			$row[] = $request->is_public()    ? __( 'Yes', 'intercessor' ) : __( 'No', 'intercessor' );
			$row[] = $request->moderator_note;
			$row[] = $request->date_created;
			$row[] = $request->date_modified;

			$rows[] = $row;
		}

		return $rows;
	}
}
