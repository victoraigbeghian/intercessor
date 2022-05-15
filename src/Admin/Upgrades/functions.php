<?php
/**
 * Intercessor Upgrades Functions
 *
 * @package     Intercessor
 * @subpackage  Admin/Upgrades/functions
 * @author      Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-1.1.0.php GNU Public License
 * @copyright   Copyright (c) 2021 Victor Aigbeghian
 * @version     1.1.0
 */

use Intercessor\Admin\Tools\Export\Batch;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'intercessor_should_upgrade' ) ) {

    /**
     * Checks if upgrade is needed.
     *
     * @since 1.0.0
     * @return bool
     */
    function intercessor_should_upgrade() : bool {
	    // Set up variables.
        $database_version = intercessor_get_db_version();
	    $current_version  = intercessor_format_db_version( INTERCESSOR_VERSION );
        $should_update    = false;

	    // New version available.
        if ( version_compare( $database_version, $current_version, '<' ) ) {
            $should_update = true;
        }

		// Return the product of version comparison.
		return $should_update;
    }
}

if ( ! function_exists( 'intercessor_upgrades_screen' ) ) {
    /**
     * Render Upgrades Screen
     *
     * @since 1.1.0
     * @return void
     */
    function intercessor_upgrades_screen() {

        // Get the upgrade being performed
        $action = isset( $_GET['intercessor-upgrade'] )
            ? sanitize_text_field( $_GET['intercessor-upgrade'] )
            : ''; ?>

        <div class="wrap">
            <h1><?php esc_html_e( 'Upgrades', 'intercessor' ); ?></h1>
            <hr class="wp-header-end">

            <?php if ( is_callable( 'intercessor_upgrade_render_' . $action ) ) {

                // Until we have fully migrated all upgrade scripts to this new system,
                // we will selectively enqueue the necessary scripts.
                add_filter( 'intercessor_load_admin_scripts', '__return_true' );
                wp_enqueue_script( 'intercessor-upgrades' );

                // This is the new method to register an upgrade routine, so we can use
                // an ajax and progress bar to display any needed upgrades.
                call_user_func( 'intercessor_upgrade_render_' . $action );

                // Remove the above filter
                remove_filter( 'intercessor_load_admin_scripts', '__return_true' );

            } else {

                // This is the legacy upgrade method, which requires a page refresh
                // at each step.
                $step   = isset( $_GET['step']   ) ? absint( $_GET['step']   ) : 1;
                $total  = isset( $_GET['total']  ) ? absint( $_GET['total']  ) : false;
                $custom = isset( $_GET['custom'] ) ? absint( $_GET['custom'] ) : 0;
                $number = isset( $_GET['number'] ) ? absint( $_GET['number'] ) : 100;
                $steps  = round( ( $total / $number ), 0 );

                // Bump step
                if ( ( $steps * $number ) < $total ) {
                    $steps++;
                }

                // Update step option.
                $upgrade_args = [
                    'page'                => 'intercessor-upgrades',
                    'intercessor-upgrade' => $action,
                    'step'                => $step,
                    'total'               => $total,
                    'custom'              => $custom,
                    'steps'               => $steps,
                ];

                update_option( 'intercessor_doing_upgrade', $upgrade_args );

                // Prevent step estimate from going over.
                if ( $step > $steps ) {
                    $steps = $step;
                }

                if ( ! empty( $action ) ) :

                    // Redirect URL.
                    $url_args = [
                        'intercessor_action' => $action,
                        'step'       => $step,
                        'total'      => $total,
                        'custom'     => $custom,
                    ];

                    $redirect = add_query_arg(
                            $url_args,
                            admin_url( 'index.php' )
                    ); ?>

                    <div id="intercessor-upgrade-status">
                        <p><?php esc_html_e( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished.', 'intercessor' ); ?></p>

                        <?php if ( ! empty( $total ) ) : ?>
                            <p><strong><?php printf( __( 'Step %d of approximately %d running', 'intercessor' ), $step, $steps ); ?></strong></p>
                        <?php endif; ?>
                    </div>
                    <script type="text/javascript">
                        setTimeout( function() {
                            document.location.href = '<?php echo esc_url_raw( $redirect ); ?>';
                        }, 250 );
                    </script>

                <?php else :

                // Redirect URL.
                $redirect = admin_url( 'index.php' ); ?>

                    <div id="intercessor-upgrade-status">
                        <p>
                            <?php _e( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished.', 'intercessor' ); ?>
                            <img src="<?php echo INTERCESSOR_URL . 'assets/images/ajax-loader.gif'; ?>" id="intercessor-upgrade-loader"/>
                        </p>
                    </div>

                    <script type="text/javascript">
                        jQuery( document ).ready( function() {

                            // Trigger upgrades on page load.
                            var data = {
                                action: 'intercessor_trigger_upgrades'
                            };

                            jQuery.post( ajaxurl, data, function (response) {
                                if ( 'complete' !== response ) {
                                    return;
                                }

                                jQuery( '#intercessor-upgrade-loader' ).hide();

                                setTimeout( function() {
                                    document.location.href = '<?php echo esc_url_raw( $redirect ); ?>';
                                }, 250 );
                            });
                        });
                    </script>

                <?php endif;
            } ?>

        </div>

        <?php
    }
}

if ( ! function_exists( 'intercessor_set_upgrade_complete' ) ) {
    /**
     * Adds an upgrade action to the completed upgrades array
     *
     * @param  string $upgrade_action The action to add to the completed upgrades array
     *
     * @since  1.1.0
     * @return bool If the function was successfully added
     */
    function intercessor_set_upgrade_complete( string $upgrade_action = '' ): bool {

        if ( empty( $upgrade_action ) ) {
            return false;
        }

        $completed_upgrades   = intercessor_get_completed_upgrades();
        $completed_upgrades[] = $upgrade_action;

        // Remove any blanks, and only show uniques
        $completed_upgrades = array_unique( array_values( $completed_upgrades ) );

        return update_option( 'intercessor_completed_upgrades', $completed_upgrades );
    }
}

if ( ! function_exists( 'intercessor_maybe_resume_upgrade' ) ) {
    /**
     * For use when doing 'stepped' upgrade routines, to see if we need to start somewhere in the middle.
     *
     * @since 1.1.0
     * @return mixed When nothing to resume returns false, otherwise starts the upgrade where it left off
     */
    function intercessor_maybe_resume_upgrade() {

        $doing_upgrade = get_option( 'intercessor_doing_upgrade', false );

        if ( empty( $doing_upgrade ) ) {
            return false;
        }

        return $doing_upgrade;
    }
}

if ( ! function_exists( 'intercessor_trigger_upgrades' ) ) {

    /**
     * Triggers all upgrade functions
     *
     * This function is usually triggered via AJAX
     *
     * @since 1.1.0
     * @return void
     */
    function intercessor_trigger_upgrades() {
       // TODO: check if this function is needed.
        // Bail if user is not capable
        if ( ! current_user_can( 'manage_prayer_settings' ) ) {
            wp_die( __( 'You do not have permission to do prayer upgrades', 'intercessor' ), __( 'Error', 'intercessor' ), array( 'response' => 403 ) );
        }

        // Get the current version from the database
        $intercessor_version = intercessor_get_db_version();

        // Get the current version
        $current_version = intercessor_format_db_version( INTERCESSOR_VERSION );

        if ( version_compare( $current_version, $intercessor_version, '>' ) ) {
            intercessor_v110_upgrades();
        }

        intercessor_update_db_version();

        // Let AJAX know that the upgrade is complete
        if ( intercessor_doing_ajax() ) {
            die( 'complete' );
        }
    }
}
add_action( 'wp_ajax_intercessor_trigger_upgrades', 'intercessor_trigger_upgrades' );


/**
 * Display Upgrade Notices
 *
 * @since 1.1.0
 * @return void
 */
function intercessor_show_upgrade_notices() {

    // Don't show notices on the upgrades page.
    if ( ! empty( $_GET['page'] ) && ( 'intercessor-upgrades' === $_GET['page'] ) ) {
        return;
    }

    // Bail if no upgrades needed.
   if ( ! intercessor_should_upgrade() ) {
        return;
    }

    // Bail if already upgraded to v1.1.0
    $current_version  = intercessor_format_db_version( INTERCESSOR_VERSION );
    if ( version_compare( '1.1.0', $current_version, '!=' ) ) {
        return;
    }

    // Possible upgrades.
    $upgrade_args = [
        'prayed_counts' => 'prayed_counts',
        'prayer_meta'   => 'prayer_meta',
        'requesters'    => 'requesters',
    ];

    $upgrades = array_map( 'intercessor_has_upgrade_completed', $upgrade_args );

    // Check if we need to do any upgrades.
    if ( count( $upgrades ) !== count( array_filter( $upgrades ) ) ) {

        // Check if any prayers exist.
        $prayer_args = [
                'number' => 1000000,
        ];
        $prayers = intercessor_get_items( 'prayer', $prayer_args );

        if ( ! empty( $prayers ) ) {
            require_once INTERCESSOR_DIR . 'src/Admin/views/upgrade-view.php';
        }
    }
}
add_action( 'admin_notices', 'intercessor_show_upgrade_notices' );

if ( ! function_exists( 'intercessor_get_v110_upgrade' ) ) {

    /**
     * Returns an array of upgrades for 1.1.0
     *
     * The key is the name of the upgrade, which can be used in `intercessor_has_upgrade_completed()` functions.
     * The value is the name of the associated batch processor class for that upgrade.
     *
     * @since 1.1.0
     * @return array Array of available upgrades.
     */
    function intercessor_get_v110_upgrade(): array {
        return [
            'prayed_counts' => [
                'name'  => __( 'Prayed Counts', 'intercessor' ),
                'class' => 'Intercessor\\Admin\\Upgrades\\Prayed_Counts'
            ],
            'requesters'    => [
                'name'  => __( 'Requester', 'intercessor' ),
                'class' => 'Intercessor\\Admin\\Upgrades\\Requesters'
            ],
        ];
    }

}

if ( ! function_exists( 'intercessor_upgrade_render_v110_upgrade' ) ) {

    /**
     * Render 1.1.0 upgrade page.
     *
     * @since 1.1.0
     */
    function intercessor_upgrade_render_v110_upgrade() {

        $upgrades         = intercessor_get_v110_upgrade();
        $upgrade_statuses = array_fill_keys( array_keys( $upgrades ), false );
        $number_complete  = 0;

        foreach ( $upgrade_statuses as $upgrade_key => $status ) {
            if ( intercessor_has_upgrade_completed( $upgrade_key ) ) {
                $upgrade_statuses[ $upgrade_key ] = true;
                $number_complete++;

                continue;
            }

            // Let's see if we have a step in progress.
            $current_step = get_option( 'intercessor_v110_upgrade_step_' . $upgrade_key );
            if ( ! empty( $current_step ) ) {
                $upgrade_statuses[ $upgrade_key ] = absint( $current_step );
            }
        }

        $migration_complete = $number_complete === count( $upgrades );

        /*
         * Determine if legacy data can be removed.
         * It can be if all upgrades except legacy data have been completed.
         */
        $can_remove_legacy_data = array_key_exists( 'v100_legacy_data_removed', $upgrade_statuses ) && $upgrade_statuses[ 'v100_legacy_data_removed' ] !== true;
        if ( $can_remove_legacy_data ) {
            foreach( $upgrade_statuses as $upgrade_key => $status ) {
                if ( 'v100_legacy_data_removed' === $upgrade_key ) {
                    continue;
                }

                // If there's at least one upgrade still unfinished, we can't remove legacy data.
                if ( true !== $status ) {
                    $can_remove_legacy_data = false;
                    break;
                }
            }
        }

        if ( $migration_complete ) {
            ?>
            <div id="intercessor-migration-ready" class="notice notice-success">
                <p>
                    <?php echo wp_kses( __( '<strong>Database Upgrade Complete:</strong> All database upgrades have been completed.', 'intercessor' ), [ 'strong' => [] ] ); ?>
                    <br /><br />
                    <?php esc_html_e( 'You may now leave this page.', 'intercessor' ); ?>
                </p>
            </div>

            <p>
                <a href="<?php echo esc_url( admin_url() ); ?>" class="button button-primary"><?php esc_html_e( 'Return to the dashboard', 'intercessor' ); ?></a>
            </p>
            <?php
            return;
        }
        ?>
        <div id="intercessor-migration-nav-warn" class="notice notice-warning">
            <p><?php echo wp_kses( __( '<strong>Important:</strong> Do not navigate away from this page until all upgrades have completed.', 'intercessor' ), array( 'strong' => [] ) ); ?></p>
        </div>

        <p>
            <?php esc_html_e( 'Intercessor needs to perform upgrades to your WordPress database. Your store data will be migrated to custom database tables to improve performance and efficiency. This process may take a while.', 'intercessor' ); ?>
            <strong><?php esc_html_e( 'Please create a full backup of your website before proceeding.', 'intercessor' ); ?></strong>
        </p>

        <p>
            <?php printf( esc_html__( 'This migration can also be run via WP-CLI with the following command: %s. This is the recommended method for large sites.', 'intercessor' ), '<code>wp intercessor v110_upgrade</code>' ); ?>
        </p>

        <?php
        // Only show the migration form if there are still upgrades to do.
        if ( ! $can_remove_legacy_data ) : ?>
            <form id="intercessor-v110-upgrade" class="intercessor-v110-upgrade" method="POST">
                <p>
                    <label for="intercessor-v110-upgrade-confirmation">
                        <input type="checkbox" id="intercessor-v110-upgrade-confirmation" class="intercessor-v110-upgrade-confirmation" name="backup_confirmation" value="1">
                        <?php esc_html_e( 'I have secured a backup of my website data.', 'intercessor' ); ?>
                    </label>
                </p>
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'intercessor_process_v110_upgrade' ) ); ?>">
                <button type="submit" id="intercessor-v110-upgrade-button" class="button button-primary disabled" disabled>
                    <?php esc_html_e( 'Upgrade Intercessor', 'intercessor' ); ?>
                </button>
                <div class="intercessor-v1-upgrade-error intercessor-hidden"></div>
            </form>
        <?php endif

        /*
         * Progress is only shown immediately if the upgrade is in progress. Otherwise it's hidden by default
         * and only revealed via JavaScript after the process has started.
         */
        ?>
        <div id="intercessor-migration-progress" <?php echo count( array_filter( $upgrade_statuses ) ) ? '' : 'class="intercessor-hidden"'; ?>>
            <ul>
                <?php foreach ( $upgrades as $upgrade_key => $upgrade_details ) :
                    // We skip the one to remove legacy data. We'll handle that separately later.
                    if ( 'v100_legacy_data_removed' === $upgrade_key ) {
                        continue;
                    }
                    ?>
                    <li id="intercessor-v1-upgrade-<?php echo esc_attr( sanitize_html_class( $upgrade_key ) ); ?>" <?php echo true === $upgrade_statuses[ $upgrade_key ] ? 'class="intercessor-upgrade-complete"' : ''; ?> data-upgrade="<?php echo esc_attr( $upgrade_key ); ?>">
					<span class="intercessor-migration-status">
						<?php
                        if ( true === $upgrade_statuses[ $upgrade_key ] ) {
                            ?>
                            <span class="dashicons dashicons-yes"></span>
                            <span class="screen-reader-text"><?php esc_html_e( 'Migration complete', 'intercessor' ); ?></span>
                            <?php
                        } else {
                            ?>
                            <span class="dashicons dashicons-minus"></span>
                            <span class="screen-reader-text"><?php esc_html_e( 'Migration pending', 'intercessor' ); ?></span>
                            <?php
                        }
                        ?>
					</span>
                        <span class="intercessor-migration-name">
						<?php echo esc_html( $upgrade_details['name'] ); ?>
					</span>
                        <span class="intercessor-migration-percentage intercessor-hidden">
						&ndash;
						<span class="intercessor-migration-percentage-value">0</span>%
					</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div id="intercessor-v1-upgrade-complete" class="notice inline notice-success<?php echo ! $can_remove_legacy_data ? ' intercessor-hidden' : ''; ?>">
            <p>
                <?php esc_html_e( 'The data migration has been successfully completed. You may now leave this page or proceed to remove legacy data below.', 'intercessor' ); ?>
            </p>
        </div>

        <?php
    }
}

if ( ! function_exists( 'intercessor_has_upgrade_completed' ) ) {

    /**
     * Check if the upgrade routine has been run for a specific action
     *
     * @param  string $upgrade_action The upgrade action to check completion for.
     *
     * @since  1.1.0
     * @return bool  If the action has been added to the completed actions array.
     */
    function intercessor_has_upgrade_completed( string $upgrade_action = '' ): bool {

        // Bail if no upgrade action to check
        if ( empty( $upgrade_action ) ) {
            return false;
        }

        // Get completed upgrades.
        $completed_upgrades = intercessor_get_completed_upgrades();

        // Return true if in array, false if not.
        return in_array( $upgrade_action, $completed_upgrades, true );
    }
}

if ( ! function_exists( 'intercessor_get_completed_upgrades' ) ) {
    /**
     * Get's the array of completed upgrade actions
     *
     * @since  1.1.0
     * @return array The array of completed upgrades
     */
    function intercessor_get_completed_upgrades(): array {

        // Get the completed upgrades for this site
        $completed_upgrades = get_option( 'intercessor_completed_upgrades', [] );

        // Return array of completed upgrades.
        return (array) $completed_upgrades;
    }
}

if ( ! function_exists( 'intercessor_process_v110_upgrade' ) ) {
    /**
     * Handles the 1.1.0 upgrade process.
     *
     * This loops through all upgrade that have not yet been completed, and steps through each process.
     *
     * @since 1.1.0
     * @return void
     */
    function intercessor_process_v110_upgrade() {
        check_ajax_referer( 'intercessor_process_v110_upgrade' );

        $all_upgrades = intercessor_get_v110_upgrade();

        // Filter out completed upgrades.
        foreach ( $all_upgrades as $upgrade_key => $upgrade_details ) {
            if ( intercessor_has_upgrade_completed( $upgrade_key ) ) {
                unset( $all_upgrades[ $upgrade_key ] );
            }
        }

        $upgrade_keys = array_keys( $all_upgrades );

        // Use supplied upgrade key if available, otherwise the first item in the list.
        $upgrade_key = ! empty( $_POST['upgrade_key'] ) && 'false' !== $_POST['upgrade_key'] ? $_POST['upgrade_key'] : false;
        if ( empty( $upgrade_key ) ) {
            $upgrade_key = reset( $upgrade_keys );
        }

        // Display error if upgrade key is invalid.
        if ( ! array_key_exists( $upgrade_key, $all_upgrades ) ) {
            wp_send_json_error( sprintf( __( '"%s" is not a valid 1.1.0 upgrade.', 'intercessor' ), $upgrade_key ) );
        }

        $step = ! empty( $_POST['step'] ) ? absint( $_POST['step'] ) : 1;

        // If we have a step already saved, use that instead.
        $saved_step = get_option( sprintf( 'intercessor_v110_migration_%s_step', sanitize_key( $upgrade_key ) ) );
        if ( ! empty( $saved_step ) ) {
            $step = absint( $saved_step );
        }

        $class_name = $all_upgrades[ $upgrade_key ]['class'];

        // Load the required classes.
        require_once INTERCESSOR_DIR . 'src/Admin/Export/Batch.php';

        /**
         * Fires to include the batch export class
         *
         * @param mixed $class_name The class name to include.
         *
         * @since 1.1.0
         */
        do_action( 'intercessor_batch_export_class_include', $class_name );

        // Report error if class does not exist.
        if ( ! class_exists( $class_name ) ) {
            wp_send_json_error( __( 'Error loading migration class.', 'intercessor' ) );
        }

        /** @var Batch $export */
        $export = new $class_name( $step );

        // Bail if user cannot perform this action.
        if ( ! $export->can_export() ) {
            wp_die(
                -1,
                403,
                [ 'response' => 403 ]
            );
        }

        $was_processed       = $export->process_step();
        $percentage_complete = round( $export->get_percentage_complete(), 2 );

        // Build some shared args.
        $response_args = array(
            'upgrade_processed' => $upgrade_key,
            'nonce'             => wp_create_nonce( 'intercessor_process_v110_upgrade' )
        );

        if ( $was_processed ) {

            $processed_args = [
                'upgrade_completed' => false,
                'next_step'         => $step + 1,
                'next_upgrade'      => $upgrade_key,
                'percentage'        => $percentage_complete,
            ];

            // Data was processed, which means we'll want to repeat this upgrade again next time.
            wp_send_json_success(
                    wp_parse_args( $processed_args, $response_args )
            );
        } else {
            // No data was processed, which means it's time to move on to the next upgrade.

            // Figure out which upgrade is next.
            $remaining_upgrades = array_slice( $upgrade_keys, array_search( $upgrade_key, $upgrade_keys ) + 1 );
            $next_upgrade       = ! empty( $remaining_upgrades ) ? reset( $remaining_upgrades ) : false;
            $next_upgrade_args  = [
                'upgrade_completed' => true,
                'next_step'         => 1,
                'next_upgrade'      => $next_upgrade,
                'percentage'        => 0,
            ];

            wp_send_json_success(
                    wp_parse_args( $next_upgrade_args, $response_args )
            );
        }

    }
}
add_action( 'wp_ajax_intercessor_process_v110_upgrade', 'intercessor_process_v110_upgrade' );


/**
 * Register batch processors for upgrade routines for 1.0.0.
 *
 * @since 1.1.0
 */
function intercessor_register_batch_processors_for_v110_upgrade() {
    add_action( 'intercessor_batch_export_class_include', 'intercessor_load_batch_processors_for_v110_upgrade', 10, 1 );
}
add_action( 'intercessor_register_batch_exporter', 'intercessor_register_batch_processors_for_v110_upgrade', 10 );

/**
 * Load the batch processor for upgrade routines for Intercessor 1.1.0.
 *
 * @param string $class Class name.
 *
 * @since 1.1.0
 */
function intercessor_load_batch_processors_for_v110_upgrade( string $class ) {
    switch ( $class ) {
        case 'Intercessor\Admin\Upgrades\Requesters':
            require_once  INTERCESSOR_DIR . 'src/Admin/Upgrades/Base.php';
            require_once  INTERCESSOR_DIR . 'src/Admin/Upgrades/Migrator.php';
            require_once  INTERCESSOR_DIR . 'src/Admin/Upgrades/Requesters.php';
            break;
        case 'Intercessor\Admin\Upgrades\Prayed_Counts':
            require_once  INTERCESSOR_DIR . 'src/Admin/Upgrades/Base.php';
            require_once  INTERCESSOR_DIR . 'src/Admin/Upgrades/Migrator.php';
            require_once  INTERCESSOR_DIR . 'src/Admin/Upgrades/Prayed_Counts.php';
            break;
    }
}

if ( ! function_exists( 'intercessor_did_v111_upgrade' ) ) {
	/**
	 * Checks if version 1.1.1 upgrade has been carried out.
	 *
	 * @return bool True if version 1.1.1 upgrade has been done, otherwise false.
	 * @since 1.1.1
	 */
	function intercessor_did_v111_upgrade() : bool {
		return (bool) get_option( 'intercessor_requester_reports' );
	}
}
