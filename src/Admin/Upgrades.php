<?php
/**
 * Intercessor Upgrades
 *
 * @package     Intercessor
 * @subpackage  Admin/Upgrades
 * @author      Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0.php GNU Public License
 * @copyright   Copyright (c) 2021 Victor Aigbeghian
 * @version     1.0.0
 */

namespace Intercessor\Admin;

use Intercessor\Requester;
use function intercessor_get_db_version;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Admin Upgrader class.
 * Handles all the functions and actions related to plugin upgrades.
 *
 * @since 1.1.0
 */
class Upgrades {

	/**
	 * Check if plugin already upgraded.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var bool
	 */
	private $upgraded = false;

	/**
	 * Get things going
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_notices', [ $this, 'notices' ] );
	}

    /**
     * Output upgrade screen
     *
     * @since 1.0.0
     * @access public
     *
     * @return void
     */
	public function screen() {
		// Setup actions and limits.
		$action = isset( $_GET['intercessor-upgrade'] ) ? sanitize_key( $_GET['intercessor-upgrade'] ) : '';
		$number = isset( $_GET['number'] ) ? absint( $_GET['number'] ) : 100;
		$total  = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : false;
		$step   = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
		$steps  = round( ( $total / $number ), 0 );

		// Bump steps.
		if ( ( $number * $steps ) < $total ) {
			$steps++;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Intercessor Upgrades', 'intercessor' ); ?></h1>
			<hr class="wp-header-end">

			<div id="intercessor-upgrade-status">
				<p><?php esc_html_e( 'Please be patient, the upgrade process has begun. You will be redirected when it is finished.', 'intercessor' ); ?></p>

				<?php if ( ! empty( $total ) ) : ?>
					<p><strong><?php printf( __( 'Step %1$d of %2$d...', 'intercessor' ), $step, $steps ); ?></strong></p>
				<?php endif; ?>
			</div>

			<script type="text/javascript">
			<?php
                $step++;

                // Redirect to the main intercessor prayers page.
                if ( $step > $steps ) {
                    $url = intercessor_get_admin_base_url();

                    // Mark as complete before we redirect.
                    $this->done( $action );

                    // Redirect to the next step in the upgrader
                } else {
                    $url_args = [
	                    'upgrade' => $action,
	                    'step'    => $step,
	                    'total'   => $total,
	                    'steps'   => $steps,
                    ];

                    $url = $this->url( $url_args );
                }
				?>

                setTimeout( function() {
                    document.location.href = '<?php echo \esc_url( $url ); ?>';
                }, 250 );
			</script>
		</div>
		<?php
        $this->process();
	}

	/**
	 * Upgrades completed.
	 *
	 * @param string $action New upgrade action.
	 *
	 * @since 1.1.0
	 * @access private
	 * @return bool True if upgrades added, otherwise void.
	 */
	private function done( string $action ) : bool {
		// Bail if no new upgrade action available.
		if ( empty( $action ) ) {
			return false;
		}

		// Get completed upgrades.
		$existing = $this->completed();

		// Turn upgrades to array.
		$upgrades = (array) $action;

		// Merge new upgrades with existing ones.
		$completed = array_merge( $existing, $upgrades );

		// Remove any duplicates and blanks.
		$completed_upgrades = array_unique( array_values( $completed ) );

		// Return the results of upgrading the option.
		return \update_option( 'intercessor_completed_upgrades', $completed_upgrades );
	}

	/**
     * Setup page url.
     *
	 * @param array $url_args
	 *
     * @since 1.1.0
     * @access private
	 * @return array|string|string[]
	 */
	private function url( array $url_args ) {
		// Setup and parse arguments.
        $args   = [
	        'page'    => 'intercessor-upgrades',
	        'upgrade' => '',
	        'step'    => null,
	        'total'   => null,
	        'steps'   => null,
        ];
		$parsed = \wp_parse_args( $url_args, $args );

		// verify nonce.
		$url = \wp_nonce_url(
            add_query_arg( $parsed, admin_url( 'index.php' )
            ),
            'intercessor-upgrade-nonce'
        );

		// Return.
		return str_replace( '&amp;', '&', $url );
	}

	/**
     * Get completed upgrades.
     *
     * @since 1.1.0
     * @access protected
	 * @return array
	 */
	protected function completed() : array {
		// Get option
		$upgraded = \get_option( 'intercessor_completed_upgrades', [] );

		// Make sure empty value is an array.
		if ( empty( $upgraded ) ) {
			$upgraded = [];
		}

		// Return array of upgraded.
		return (array) $upgraded;
	}

	/**
	 * Show upgrade notice on the admins screen.
	 *
	 * @return void
	 * @since 1.1.0
	 * @access private
	 */
	public function notices() {
        // Setup variables.
        $page      = $this->upgrades_page();
        $upgrading = $this->upgrading();
		$version   = intercessor_get_db_version();
        $current   = \intercessor_format_db_version( INTERCESSOR_VERSION );

        // Bail if no new version.
        if ( ! intercessor_should_upgrade() ) {
            return;
        }

		// Show no notice on the upgrades page.
		if ( $page || $upgrading ) {
			return;
		}

        // Display notice for version 1.1.0 upgrade
		if ( ! $this->upgraded( 'v_110' ) && '1.1.0' === $current ) {
			printf(
				'<div class="updated"><p>' . __( 'Intercessor needs to upgrade the prayed for database. Click <a href="%s">here</a> to start.', 'intercessor' ) . '</p></div>',
				$this->url( [ 'upgrade' => 'v_110' ] )
			);

			// Display notice for version 1.1.0 upgrade
		} elseif ( ! $this->upgraded( 'v_111' ) && '1.1.1' === $current ) {
			printf(
				'<div class="updated"><p>' . __( 'Intercessor needs to upgrade the prayed for database and send requester emails. Click <a href="%s">here</a> to start.', 'intercessor' ) . '</p></div>',
				$this->url( [ 'upgrade' => 'v_111' ] )
			);
		}

	}

	/**
     * Get upgrades page
     *
     * @since 1.1.0
     * @access private
	 * @return bool Upgrades page
	 */
	private function upgrades_page() : bool {
		return isset( $_GET['page'] ) && ( 'intercessor-upgrades' === $_GET['page'] );
	}

	/**
     * Check if plugin is upgrading.
     *
     * @access private
     * @since 1.1.0
	 * @return bool True if upgrading, otherwise false.
	 */
	private function upgrading() : bool {
		return ! empty( $_GET['upgrade'] );
	}

	/**
     * Check upgraded versions.
     *
	 * @param string $action New upgrade action.
	 *
     * @since 1.1.0
     * @access private
	 * @return bool True if version is already upgraded, otherwise false.
	 */
	private function upgraded( string $action ): bool {
		// Bail if no new upgrade action to set
		if ( empty( $action ) ) {
			return false;
		}

        // Get completed upgrades.
		$completed_upgrades = $this->completed();

        // Return array of completed upgrades.
		return in_array( $action, $completed_upgrades, true );
	}

	/**
	 * Process the upgrade
     *
     * @since 1.1.0
     * @access public
     * @return void
	 */
	public function process() {

		// Bail if not admin or is doing ajax.
		if ( ! \is_admin() || \wp_doing_ajax() ) {
			return;
		}

		// Bail if current user cannot manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Bail if nonce verification fails or no upgrade action specified.
		if ( ! $this->verify_nonce() || ! $this->upgrading() ) {
			return;
		}

		// Sanitize the step.
		$step = $this->current_upgrade();
		//$func = "Intercessor\\Admin\\Upgrades\\do_{$step}";
        $func = $this->do_ . "{$step}";

		// Process the step.
		if ( function_exists( $func ) ) {
			call_user_func( $func );
		}

        // Update database version if upgrade completed.
        if ( $this->upgraded ) {
	        \intercessor_update_db_version();
        }
	}

	/**
     * Get current upgrade.
     *
     * @since 1.1.0
     * @access protected
	 * @return false|string
	 */
	protected function current_upgrade() {
		return $this->upgrades_page() && $this->upgrading()
			? sanitize_key( $_GET['upgrade'] )
			: false;
	}

	/**
     * Verify nonce
     *
     * @since 1.1.0
     * @access protected
	 * @return false|int
	 */
	protected function verify_nonce() {
		return ! empty( $_REQUEST['_wpnonce'] )
			? wp_verify_nonce( $_REQUEST['_wpnonce'], 'intercessor-upgrade-nonce' )
			: false;
	}

	/**
	 * Version 1.1.0 upgrade
	 *
	 * @access public
	 * @since 1.1.0
	 */
    public function do_v_110() {
		// Set up variables.
		$prayers = $this->get_prayers();

	    // Get prayed counts of prayers already prayed for.
	    if ( $prayers ) {
		    foreach ( $prayers as $prayer ) {
			    $prayer_id = $prayer->id;
			    $counts    = \intercessor_get_prayed_requests( $prayer_id );

			    // Process prayed counts.
			    if ( $counts ) {
				    $data   = [
					    'prayer_id'    => $prayer_id,
					    'prayed_for'   => $counts,
					    'date_created' => \intercessor_get_current_time(),
				    ];

				    // Migrate prayed counts from meta table to new prayed counts table.
				    $added = \intercessor_add_item( 'prayed', $data );

                    // Delete prayed counts from prayer meta if updated.
                    if ( $added ) {
                        $new_counts = \intercessor_get_prayed_for_counts( $prayer_id );

                        // Remove old counts from prayer meta database.
                        if ( $new_counts === $counts ) {
                            \intercessor_delete_item_meta( 'prayer', $prayer_id, 'prayed_counts', '', true );
                        }
                    }
			    }

                // Check requester prayer count tallies with database count.
                $email        = \intercessor_get_prayer_email( $prayer_id );
                $requester    = new Requester( $email, false );
                $data_count   = $requester->prayer_count;
                $prayer_count = \intercessor_count_prayers_of_requester( $email );

                // Check if database count is the same as the real requester prayer count.
                if ( $prayer_count !== $data_count ) {
                    // Setup prayer count to update.
                    $update_args = [
                        'prayer_count' => $prayer_count,
                    ];

                    // Update database prayer count.
                    $requester->update( $update_args );
                }
		    }

            // Upgrade has happened.
            $this->upgraded = true;
	    }
    }

	/**
	 * Upgrade to version v1.1.1
	 *
	 * @access public
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function do_v_111() {
		// Set up variables.
		$prayers = $this->get_prayers();

		// Process actions if prayer found.
		if ( $prayers ) {

			// Send requester emails.
		//	do_action( 'intercessor_send_requesters_reports' );

			// Remove prayed counts, if any still exist in prayer meta.
			$done = \intercessor_remove_prayed_counts_meta();

			// Mark upgrade as complete if above actions carried out.
			if ( $done ) {
				$this->upgraded = true;
			}

			// Set up action to send requester prayer reports if plugin updated.
			if ( $this->upgraded ) {
				\update_option( 'intercessor_requester_reports', true );
			}
		}
	}

	/**
	 * Get available prayer requests.
	 *
	 * @since 1.1.0
	 * @access public
	 *
	 * @return array Array of prayer requests.
	 */
	protected function get_prayers() {
		// Array of arguments to retrieve prayers.
	    $args    = [
		    'number' => 100000000,
	    ];

	    // Get all prayer requests.
	    return \intercessor_get_prayers( $args );
	}
}
