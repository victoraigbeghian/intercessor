<?php
/**
 * Plugin Name: Intercessor
 * Plugin URI:  https://github.com/victoraigbeghian/intercessor
 * Description: A creative approach to handle prayer requests and requesters.
 * Author:      Victor Aigbeghian
 * Author URI:  https://github.com/victoraigbeghian
 * Version:     1.1.1
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: intercessor
 * Domain Path: /languages
 *
 * @package   Intercessor
 * @author    Victor Aigbeghian
 * @copyright 2020 Victor Aigbeghian
 * @license   GPL-2.0-or-later http://www.gnu.org/licenses/gpl-2.0.txt
 */

use Intercessor\Loader;
use Intercessor\Install;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define Intercessor file.
if ( ! defined( 'INTERCESSOR_FILE' ) ) {
	define( 'INTERCESSOR_FILE', __FILE__ );
}

/**
 * The main plugin requirements checker
 *
 * @since 1.0.0
 */
final class Intercessor_Requirements {

    
    /**
     * Plugin basename
     *
     * @since 1.0.0
     * @var   string
     */
    private $base = '';

    /**
     * Requirements array
     *
     * @var   array
     * @since 1.0.0
     */
    private $requirements;

    /**
     * Setup plugin requirements
     *
     * @param object $loader  Loader class object.
     * @param object $install Install class object.
     *
     * @access public
     * @since  1.0.0
     *
     * @return void
     */
    public function __construct(/* Loader $loader, Install $install */) {
        // Set up variables.
    /*  $this->loader       = $loader;
        $this->install      = $install;
		*/
        $this->base         = plugin_basename( INTERCESSOR_FILE );
        $this->requirements = [
            'wp'  => [
                'minimum' => '5.0',
                'name'    => 'WordPress',
                'exists'  => true,
                'current' => false,
                'checked' => false,
                'met'     => false,
            ],
            'php' => [
                'minimum' => '7.0.0',
                'name'    => 'PHP',
                'exists'  => true,
                'current' => false,
                'checked' => false,
                'met'     => false,
            ],
        ];

        // Load translations.
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );

        // Run autoload only when requirements are met.
        if ( $this->met()) {
            $this->setup();
        } else {
            $this->quit();
        }
    }

    /**
     * Quit without loading
     *
     * @access private
     * @since  1.0.0
     *
     * @return void
     */
    private function quit() {
        add_action( 'admin_head', [ $this, 'adminHead' ] );
        add_filter( "plugin_action_links_{$this->base}", [ $this, 'plugin_row_links' ] );
        add_action( "after_plugin_row_{$this->base}", [ $this, 'plugin_row_notice' ] );
    }

    /**
     * Normal loading.
     *
     * @access private
     * @since  1.0.0
     *
     * @return void
     */
    private function setup() {

		// Autoload files.
		require_once dirname( INTERCESSOR_FILE ) . '/vendor/autoload.php';

		// Bootstrap to plugins_loaded before priority 10.
		add_action( 'plugins_loaded', [ $this, 'load' ], 4 );

		// Register the activation hook.
		register_activation_hook( INTERCESSOR_FILE, [ $this, 'install' ] );
	}
    
    /**
     * Install on an activation hook
     *
     * @access public
     * @since  1.0.0
     *
     * @return void
     */
    public function install() {

        // Bootstrap to include all of the necessary files.
        $this->load();

        // Check if installation is network wide.
        $network_wide = ! empty( $_GET['networkwide'] )
            ? (bool) $_GET['networkwide']
            : false;

        // Run installer directly during the activation hook.
        Install::activate( $network_wide );
    }

    /**
     * Run the Bootstrapper.
     *
     * @access public
     * @since  1.0.0
     *
     * @return void
     */
    public function load() {
		Loader::setup_instance( $this->base );
	}
    
	/**
	 * Plugin specific URL for an external requirements page.
	 *
     * @access private
	 * @since  1.0.0
     *
	 * @return string
	 */
	private function unmet_requirements_url() {
		// URL of the unmet requirements.
		return 'https://github.com/victoraigbeghian/minimum-requirements';
	}

	/**
	 * Plugin specific text to quickly explain what's wrong.
	 *
     * @access private
	 * @since  1.0.0
     *
	 * @return void
	 */
	private function unmet_requirements_text() {
		esc_html_e( 'This plugin is not fully active.', 'intercessor' );
	}

	/**
	 * Plugin specific text to describe a single unmet requirement.
	 *
     * @access private
	 * @since  1.0.0
     *
	 * @return string
	 */
	private function unmet_requirements_description_text() {
		/* Translators: version required, but other version installed */
		return esc_html__( 'Requires %1$s (%2$s), but (%3$s) is installed.', 'intercessor' );
	}

	/**
	 * Plugin specific text to describe a single missing requirement.
	 *
     * @access private
	 * @since  1.0.0
     *
	 * @return string
	 */
	private function unmet_requirements_missing_text() {
		/* Translators: version required, but missing. */
		return esc_html__( 'Requires %1$s (%2$s), but it appears to be missing.', 'intercessor' );
	}

	/**
	 * Plugin specific text used to link to an external requirements page.
	 *
     * @access private
	 * @since  1.0.0
     *
	 * @return string
	 */
	private function unmet_requirements_link() {
		return esc_html__( 'Requirements', 'intercessor' );
	}

	/**
	 * Plugin specific aria label text to describe the requirements link.
	 *
     * @access private
	 * @since  1.0.0
     *
	 * @return string
	 */
	private function unmet_requirements_label() {
		return esc_html__( 'Intercessor Requirements', 'intercessor' );
	}

	/**
	 * Plugin specific text used in CSS to identify attribute IDs and classes.
	 *
     * @access private
	 * @since  1.0.0
     *
	 * @return string
	 */
	private function unmet_requirements_name() {
		return 'intercessor-requirements';
	}

	/**
	 * Plugin method to output the additional plugin row.
	 *
     * @access public
	 * @since  1.0.0
     *
     * @return void
	 */
	public function plugin_row_notice() {
		?>
		<tr class="active <?php echo esc_attr( $this->unmet_requirements_name() ); ?>-row">
		<th class="check-column">
			<span class="dashicons dashicons-warning"></span>
		</th>
		<td class="column-primary">
			<?php $this->unmet_requirements_text(); ?>
		</td>
		<td class="column-description">
			<?php $this->unmet_requirements_description(); ?>
		</td>
		</tr>
		<?php
	}

	/**
	 * Plugin method used to output all unmet requirement information
	 *
     * @access private
	 * @since  1.0.0
     *
     * @return void
	 */
	private function unmet_requirements_description() {
		foreach ( $this->requirements as $properties ) {
			if ( empty( $properties['met'] ) ) {
				$this->unmet_requirement_description( $properties );
			}
		}
	}

	/**
	 * Plugin method to output specific unmet requirement information
	 *
	 * @param array $requirement Array of requirements.
     *
     * @access private
	 * @since  1.0.0
     *
     * @return void
	 */
	private function unmet_requirement_description( $requirement = [] ) {

		// Requirement exists, but is out of date.
		if ( ! empty( $requirement['exists'] ) ) {
			$text = sprintf(
				$this->unmet_requirements_description_text(),
				'<strong>' . esc_html( $requirement['name'] ) . '</strong>',
				'<strong>' . esc_html( $requirement['minimum'] ) . '</strong>',
				'<strong>' . esc_html( $requirement['current'] ) . '</strong>'
			);

			// Requirement could not be found.
		} else {
			$text = sprintf(
				$this->unmet_requirements_missing_text(),
				'<strong>' . esc_html( $requirement['name'] ) . '</strong>',
				'<strong>' . esc_html( $requirement['minimum'] ) . '</strong>'
			);
		}

		// Output the description.
		echo '<p>' . $text . '</p>';
	}

	/**
	 * Plugin agnostic method to output unmet requirements styling
	 *
     * @access public
	 * @since  1.0.0
     *
     * @return void
	 */
	public function admin_head() {

		// Get the requirements row name.
		$name = $this->unmet_requirements_name();
		?>

		<style id="<?php echo esc_attr( $name ); ?>">
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] th,
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] td,
			.plugins .<?php echo esc_html( $name ); ?>-row th,
			.plugins .<?php echo esc_html( $name ); ?>-row td {
				background: #fff5f5;
			}
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] th {
				box-shadow: none;
			}
			.plugins .<?php echo esc_html( $name ); ?>-row th span {
				margin-left: 6px;
				color: #dc3232;
			}
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] th,
			.plugins .<?php echo esc_html( $name ); ?>-row th.check-column {
				border-left: 4px solid #dc3232 !important;
			}
			.plugins .<?php echo esc_html( $name ); ?>-row .column-description p {
				margin: 0;
				padding: 0;
			}
			.plugins .<?php echo esc_html( $name ); ?>-row .column-description p:not(:last-of-type) {
				margin-bottom: 8px;
			}
		</style>
		<?php
	}

	/**
	 * Plugin agnostic method to add the "Requirements" link to row actions
	 *
	 * @param array $links Links.
     *
     * @access public
	 * @since  1.0.0
     *
	 * @return array
	 */
	public function plugin_row_links( $links = [] ) {

		// Add the Requirements link.
		$links['requirements'] =
			'<a href="' . esc_url( $this->unmet_requirements_url() ) . '" aria-label="' . esc_attr( $this->unmet_requirements_label() ) . '">'
			. esc_html( $this->unmet_requirements_link() )
			. '</a>';

		// Return links with Requirements link.
		return $links;
	}

	/**
	 * Plugin specific requirements checker
	 *
     * @access public
	 * @since 1.0.0
     * 
     * @return void
	 */
	private function check() {

		// Loop through the requirements.
		foreach ( $this->requirements as $dependency => $properties ) {

			// Which dependency are we checking?
			switch ( $dependency ) {

				// PHP version.
				case 'php':
					$version = phpversion();
					break;

				// WordPress version.
				case 'wp':
					$version = get_bloginfo( 'version' );
					break;

				// Unknown.
				default:
					$version = false;
					break;
			}

			// Merge to original array.
			if ( ! empty( $version ) ) {
				$this->requirements[ $dependency ] = array_merge(
					$this->requirements[ $dependency ],
					[
						'current' => $version,
						'checked' => true,
						'met'     => version_compare( $version, $properties['minimum'], '>=' ),
					]
				);
			}
		}
	}

	/**
	 * Checks if all requirements have been met.
	 *
     * @access public
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function met() {

		// Run the checker.
		$this->check();

		// Default to true.
		$retval  = true;
		$to_meet = wp_list_pluck( $this->requirements, 'met' );

		// Look for unmet dependencies, and exit if so.
		foreach ( $to_meet as $met ) {
			if ( empty( $met ) ) {
				$retval = false;
				break;
			}
		}

		// Return.
		return $retval;
	}

	/**
	 * Plugin text-domain loader.
	 *
     * @access public
	 * @since 1.0.0
     *
	 * @return void
	 */
	public function load_textdomain() {
		// Load the default language files.
		load_plugin_textdomain( 'intercessor', false, dirname( INTERCESSOR_FILE ) . '/languages' );

	}
}

// Invoke the requirements checker.
new Intercessor_Requirements();

/**
 * Runs the autoloader
 *
 * @since 1.0.0
 *
 * @return \Intercessor\Loader Returns the Intercessor loader object.
 */
function intercessor() {
	$file = plugin_basename( INTERCESSOR_FILE );
	return Loader::setup_instance( $file );
}
