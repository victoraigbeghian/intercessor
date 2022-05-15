<?php
/**
 * Intercessor Upgrades
 *
 * @package     Intercessor
 * @subpackage  Admin/Upgrades
 * @author      Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-1.1.0.php GNU Public License
 * @copyright   Copyright (c) 2021 Victor Aigbeghian
 * @version     1.0.0
 */

namespace Intercessor\Admin\Upgrades;

use Intercessor\Admin\Tools\Export\Batch;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Admin Upgrader class.
 *
 * Handles all the functions and actions related to plugin upgrades.
 *
 * @since 1.1.0
 */
class Base extends Batch {

    /**
     * Orders.
     *
     * @since 1.1.0
     * @var   string
     */
    const PRAYERS = 'prayers';

    /**
     * Discounts.
     *
     * @since 1.1.0
     * @var   string
     */
    const PRAYED = 'prayed';

    /**
     * Our export type. Used for export-type specific filters/actions.
     *
     * @since 1.1.0
     * @var   string
     */
    public $export_type = '';

    /**
     * Allows for a batch processing to be run.
     *
     * @since 1.1.0
     * @var   bool
     */
    public $is_void = true;

    /**
     * Sets the number of items to pull on each step.
     *
     * @since 1.1.0
     * @var   int
     */
    public $per_step = 50;

    /**
     * Is the upgrade done?
     *
     * @since 1.1.0
     * @var   bool
     */
    public $done;

    /**
     * Message.
     *
     * @since 1.1.0
     * @var   string
     */
    public $message;

    /**
     * Completed message.
     *
     * @since 1.1.0
     * @var   string
     */
    public $completed_message;

    /**
     * Upgrade routine.
     *
     * @since 1.1.0
     * @var   string
     */
    public $upgrade;

    /**
     * Retrieve the data pertaining to the current step and migrate as necessary.
     *
     * @since 1.1.0
     *
     * @return bool True if data was migrated, false otherwise.
     */
    public function get_data(): bool
    {
        return false;
    }

    /**
     * Process a step.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function process_step(): bool
    {
        if ( ! $this->can_export() ) {
            wp_die(
                esc_html__( 'You do not have permission to run this upgrade.', 'intercessor' ),
                esc_html__( 'Error', 'intercessor' ),
                array(
                    'response' => 403,
                )
            );
        }

        $had_data = $this->get_data();

        if ( $had_data ) {
            $this->done = false;
            // Save the *next* step to take.
            update_option( sprintf( 'intercessor_v3_migration_%s_step', sanitize_key( $this->upgrade ) ), $this->step + 1 );
            return true;
        } else {
            $this->done    = true;
            $this->message = $this->completed_message;
            intercessor_set_upgrade_complete( $this->upgrade );
            delete_option( sprintf( 'intercessor_v3_migration_%s_step', sanitize_key( $this->upgrade ) ) );
            return false;
        }
    }

    /**
     * Set the headers.
     *
     * @since 1.1.0
     */
    public function headers() {
        intercessor_set_time_limit();
    }

    /**
     * Perform the migration.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function export() {

        // Set headers.
        $this->headers();

        intercessor_die();
    }

    /**
     * Return the global database interface.
     *
     * @since  1.1.0
     * @access protected
     * @static
     *
     * @return \wpdb|\stdClass
     */
    protected static function get_db() {
        return $GLOBALS['wpdb'] ?? new \stdClass();
    }

    /**
     * Set properties specific to the export.
     *
     * @since 1.1.0
     *
     * @param array $request Form data passed into the batch processor.
     */
    public function set_properties( $request ) {
    }

    /**
     * Allow for pre-fetching of data for the remainder of the batch processor.
     *
     * @since 1.1.0
     */
    public function pre_fetch() {
    }
}
