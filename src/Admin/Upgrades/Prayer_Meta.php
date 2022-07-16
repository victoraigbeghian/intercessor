<?php
/**
 * Intercessor Prayer Meta Upgrade
 *
 * @package     Intercessor
 * @subpackage  Admin/Upgrades/Prayer_Meta
 * @copyright   Copyright (c) 2021, Victor Aigbeghian
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.0.0
 */

namespace Intercessor\Admin\Upgrades;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Prayer_Meta Upgrades Class.
 *
 * @since 1.1.0
 */
class Prayer_Meta extends Base {

	/**
	 * Constructor.
	 *
	 * @param int $step Step.
     *
     * @since 1.1.0
     * @access public
	 */
	public function __construct( $step = 1 ) {
		parent::__construct( $step );

		$this->completed_message = esc_html__( 'Prayer meta migration completed successfully.', 'intercessor' );
		$this->upgrade           = 'prayer_meta';
	}

	/**
	 * Retrieve the data pertaining to the current step and migrate as necessary.
	 *
	 * @since 1.1.0
	 *
	 * @return bool True if data was migrated, false otherwise.
	 */
	public function get_data() : bool {
		$offset = ( $this->step - 1 ) * $this->per_step;

		$results = $this->get_db()->get_results( $this->get_db()->prepare(
			"SELECT *
			 FROM {$this->get_db()->ipr_prayermeta}
			 WHERE meta_key = %s
			 ORDER BY ipr_prayer_id ASC
			 LIMIT %d, %d",
			esc_sql( 'prayed_counts' ), $offset, $this->per_step
		) );

		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {

				// Check if prayed counts has not been migrated.
				if ( intercessor_process_item( 'prayed', 'get', $result->ipr_prayer_id, false ) ) {
					continue;
				}

				Migrator::prayer_meta( $result );
			}

			return true;
		}

		return false;
	}

	/**
	 * Calculate the percentage completed.
	 *
	 * @since 1.1.0
	 *
	 * @return float Percentage.
	 */
	public function get_percentage_complete() {
		$total = $this->get_db()->get_var( $this->get_db()->prepare( "SELECT COUNT(meta_id) AS count FROM {$this->get_db()->ipr_prayer_meta} WHERE meta_key = %s", esc_sql( 'prayed_counts' ) ) );

		// Set total to 0 if nothing available.
		if ( empty( $total ) ) {
			$total = 0;
		}

		// Set up percentage values.
		$percentage = 100;

		if ( $total > 0 ) {
			$percentage = ( ( $this->per_step * $this->step ) / $total ) * 100;
		}

		// Make sure percentage is not greater than 100.
		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		// Return percentage value.
		return $percentage;
	}
}
