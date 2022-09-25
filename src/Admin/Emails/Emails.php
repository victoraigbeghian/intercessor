<?php
/**
 * Intercessor Requester Emails
 *
 * @package     Intercessor
 * @subpackage  Admin/Requesters
 * @copyright   Copyright (c) 2021, Victor Aigbeghian
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.1.0
 */

namespace Intercessor\Admin\Requesters;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Emails Class.
 *
 * @since 1.1.0
 */
class Emails extends Base {

    /**
     * Gets the total number of requesters.
     *
     * @since 1.1.0
     * @var   int
     */
    public $total = 0;

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

		$this->completed_message = esc_html__( 'Requester batch emails sent successfully.', 'intercessor' );
		$this->sending           = (string) \wp_date( 'm_d_Y' );
	}

	/**
	 * Retrieve the data pertaining to the current step and migrate as necessary.
	 *
	 * @since 1.1.0
	 *
	 * @return bool True if data was migrated, false otherwise.
	 */
	public function get_data() : bool {
		// Set up variables.
		$offset  = ( $this->step - 1 ) * $this->per_step;
		$results = $this->get_db()->get_results(
			$this->get_db()->prepare(
				"SELECT *
				FROM {$this->get_db()->ipr_requesters}
				WHERE status = %s
				ORDER BY requester_id ASC
				LIMIT %d, %d",
				esc_sql( 'active' ),
				$offset,
				$this->per_step
			)
		);
		$prayers = $this->get_db()->get_results(
			$this->get_db()->prepare(
				"SELECT *
				FROM {$this->get_db()->ipr_prayers}"
			)
		);

		// Send email if requester and prayer found.
		if ( ! empty( $results ) && ! empty( $prayers ) ) {
			foreach ( $results as $result ) {
				$this->send( $result );
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
		$total = $this->get_db()->get_var(
			$this->get_db()->prepare(
				"SELECT COUNT(requester_id) AS count
				FROM {$this->get_db()->ipr_requesters}
				WHERE status = %s",
				esc_sql( 'active' )
			)
		);

		// Set total to 0 if nothing available.
		if ( empty( $total ) ) {
			$total = 0;
		}

		// Set up percentage values.
		$percentage = 100;

		if ( $total > 0 ) {
			$this->total = $total;
			$percentage  = ( ( $this->per_step * $this->step ) / $total ) * 100;
		}

		// Make sure percentage is not greater than 100.
		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		// Return percentage value.
		return $percentage;
	}

	/**
	 * Send batch emails.
	 *
	 * @since 1.1.0
	 *
	 * @param mixed $result Result values.
	 */
	public function send( $result ) {
		// Set up variables.
		$requester_id = absint( $result->id );

		// Send prayer reports email.
		\intercessor_email_reports_notification( $requester_id );
	}
}
