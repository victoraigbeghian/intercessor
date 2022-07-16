<?php
/**
 * Intercessor Emails
 *
 * @package     Intercessor
 * @subpackage  Admin/Emails
 * @author      Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-1.1.0.php GNU Public License
 * @copyright   Copyright (c) 2021 Victor Aigbeghian
 * @version     1.0.0
 */

namespace Intercessor\Admin\Requesters;

use Intercessor\Admin\Tools\Export\Batch;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Admin Emails class.
 *
 * Handles all the functions and actions related to batch emails.
 *
 * @since 1.1.0
 */
class Base extends Batch {

    /**
     * Emails.
     *
     * @since 1.1.0
     * @var   string
     */
    const EMAILS = 'emails';

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
    public $per_step = 30;

    /**
     * Batch email done.
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
     * Sending email routine.
     *
     * @since 1.1.0
     * @var   string
     */
    public $sending;

    /**
     * Retrieve the data pertaining to the current step.
     *
     * @since 1.1.0
     *
     * @return bool True if batch email sent, false otherwise.
     */
    public function get_data(): bool {
        return false;
    }

    /**
     * Process a step.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function process_step(): bool {
        if ( ! $this->can_export() ) {
            wp_die(
                esc_html__( 'You do not have permission to send batch emails.', 'intercessor' ),
                esc_html__( 'Error', 'intercessor' ),
                array(
                    'response' => 403,
                )
            );
        }

        $had_data = $this->get_data();

        if ( $had_data ) {
            $this->done = false;
            // Save the *next* step to do.
            \update_option( sprintf( 'intercessor_batch_email_%s_step', sanitize_key( $this->sending ) ), $this->step + 1 );
            return true;
        } else {
            $this->done    = true;
            $this->message = $this->completed_message;
            \intercessor_set_batch_email_sent( $this->sending );
            \delete_option( sprintf( 'intercessor_batch_email_%s_step', sanitize_key( $this->sending ) ) );
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
        return isset( $GLOBALS['wpdb'] )
            ? $GLOBALS['wpdb']
            : new \stdClass();
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
