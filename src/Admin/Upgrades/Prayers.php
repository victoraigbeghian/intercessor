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
 * Prayers Class.
 *
 * @since 1.1.0
 */
class Prayers extends Base {

	/**
	 * Constructor.
	 *
	 * @param int $step Step.
	 */
	public function __construct( $step = 1 ) {
		parent::__construct( $step );

		$this->completed_message = __( 'Prayers migration completed successfully.', 'intercessor' );
		$this->upgrade           = 'migrate_Prayers';
	}

	/**
	 * Retrieve the data pertaining to the current step and migrate as necessary.
	 *
	 * @since 1.1.0
	 *
	 * @return bool True if data was migrated, false otherwise.
	 */
	public function get_data() {
		$offset = ( $this->step - 1 ) * $this->per_step;

		$results = $this->get_db()->get_results( $this->get_db()->prepare(
			"SELECT *
			 FROM {$this->get_db()->ipr_prayers}
			 WHERE post_type = %s
			 ORDER BY ID ASC
			 LIMIT %d, %d",
			esc_sql( 'edd_payment' ), $offset, $this->per_step
		) );

		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {

				// Check if prayer has already been migrated.
				if ( intercessor_process_item( 'prayer', 'get', $result->ipr_prayer_id, false ) ) {
					continue;
				}

			//	Migrator::prayers( $result );
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
		$total = $this->get_db()->get_var( $this->get_db()->prepare( "SELECT COUNT(id) AS count FROM {$this->get_db()->posts} WHERE post_type = %s", esc_sql( 'ipr_prayer' ) ) );

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
