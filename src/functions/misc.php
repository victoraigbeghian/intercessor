<?php
/**
 * Intercessor Miscellaneous Functions
 *
 * @package     Intercessor
 * @subpackage  Functions/Misc
 * @copyright   Copyright (c) 2020, Your Name
 * @license     http://opensource.org/licenses/GPL-2.0.php GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'intercessor_setup_db_version' ) ) {
    /**
     * Configures the database version
     *
     * @param string $db_version Database version to configure.
     *
     * @return string
     * @since 1.0.0
     *
     */
    function intercessor_setup_db_version( string $db_version = '' ) : string {
        return preg_replace( '/[^0-9.].*/', '', $db_version );
    }
}

if ( ! function_exists( 'intercessor_get_db_version' ) ) {
    /**
     * Retrieves the current database version
     *
     * @since 1.0.0
     *
     * @return string
     */
    function intercessor_get_db_version() {
        $db_version = get_option( 'intercessor_version' );
        return ! empty( $db_version )
            ? intercessor_setup_db_version( $db_version )
            : false;
    }
}

if ( ! function_exists( 'intercessor_update_db_version' ) ) {
    /**
     * Updates the database version
     *
     * @since 1.0.0
     */
    function intercessor_update_db_version() {
        if ( defined( 'INTERCESSOR_VERSION' ) ) {
            $value = intercessor_setup_db_version( INTERCESSOR_VERSION );
            update_option( 'intercessor_version', $value );
        }
    }
}

if ( ! function_exists( 'intercessor_format_db_version' ) ) {
	/**
	 * Format the database version.
	 *
	 * @param string $version Database version.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	function intercessor_format_db_version( string $version = '' ) : string {
		return preg_replace( '/[^0-9.].*/', '', $version );
	}
}

if ( ! function_exists( 'intercessor_version' ) ) {
	/**
	 * Gets the version of our plugin.
	 *
	 * @since 1.1.1
	 * @return string $version The version of our plugin
	 */
	function intercessor_version() {
		// Set up variables.
		if ( defined( 'INTERCESSOR_VERSION' ) ) {
            $value = INTERCESSOR_VERSION;
		} else {
			$class = new Intercessor\Loader();
			$value = $class->version;
		}

		// Return version number.
		return intercessor_setup_db_version( $value );
	}
}

/**
 * Processes all Intercessor actions sent via POST and GET by looking for
 * the 'intercessor-action' request and running do_action() to call the function
 *
 * @since 0.9.5
 * @return void
 */
function intercessor_process_actions() {
	$keys = ! empty( $_POST['intercessor_action'] ) ? sanitize_key( $_POST['intercessor_action'] ) : false;
	if ( ! empty( $key ) ) {
		do_action( "intercessor_{$keys}", $_POST );
	}

	$key = ! empty( $_GET['intercessor_action'] ) ? sanitize_key( $_GET['intercessor_action'] ) : false;
	if ( ! empty( $key ) ) {
		do_action( "intercessor_{$key}" , $_GET );
	}
}

if ( ! function_exists( 'intercessor_get_option' ) ) {

    /**
     * Get an option
     *
     * Looks to see if the specified setting exists, returns default if not
     *
     * @since 0.9.5
     *
     * @param string $key     Option Key.
     * @param bool   $default Default value.
     *
     * @return mixed
     * @global array $intercessor_options Array of all the IPR Options.
     */
    function intercessor_get_option( $key = '', $default = false ) {
        global $intercessor_options;

        $value = ! empty( $intercessor_options[ $key ] ) ? $intercessor_options[ $key ] : $default;
        $value = apply_filters( 'intercessor_get_option', $value, $key, $default );
        return apply_filters( 'intercessor_get_option_' . $key, $value, $key, $default );
    }
}

if ( ! function_exists( 'intercessor_update_option' ) ) {
	/**
	 * Update an option
	 *
	 * Updates an ipr setting value in both the db and the global variable.
	 * Warning: Passing in an empty, false or null string value will remove
	 *          the key from the intercessor_options array.
	 *
	 * @param string          $key   The Key to update.
	 * @param string|bool|int $value The value to set the key to.
	 *
	 * @since 0.9.5
	 * @global array $intercessor_options Array of all the IPR Options.
	 * @return boolean True if updated, false if not.
	 */
	function intercessor_update_option( $key = '', $value = false ) {

		// Bail, if no key.
		if ( empty( $key ) ){
			return false;
		}

		if ( empty( $value ) ) {
			$remove_option = intercessor_delete_option( $key );
			return $remove_option;
		}

		// Get the current settings.
		$options = get_option( 'intercessor_settings' );

		/**
		 * Filter before options are updated.
		 *
		 * @param string $value Option value.
		 * @param string $key   Option key.
		 *
		 * @since 0.9.5
		 */
		$value = apply_filters( 'intercessor_update_option', $value, $key );

		// Ttry to update the value.
		$options[ $key ] = $value;
		$did_update      = update_option( 'intercessor_settings', $options );

		// If it updated, let's update the global variable.
		if ( $did_update ) {
			global $intercessor_options;
			$intercessor_options[ $key ] = $value;

		}

		return $did_update;
	}
}

if ( ! function_exists( 'intercessor_delete_option' ) ) {
	/**
	 * Remove an option
	 *
	 * Removes an ipr setting value in both the db and the global variable.
	 *
	 * @param string $key The Key to delete.
	 *
	 * @since 0.9.5
	 *
	 * @global $intercessor_options
	 * @return boolean True if removed, false if not.
	 */
	function intercessor_delete_option( $key = '' ) {
		global $intercessor_options;

		// If no key, exit.
		if ( empty( $key ) ) {
			return false;
		}

		// Get the current settings.
		$options = get_option( 'intercessor_settings' );

		// Try to update the value.
		if ( isset( $options[ $key ] ) ) {

			unset( $options[ $key ] );

		}

		// Remove this option from the global IPR settings to the array_merge in intercessor_settings_sanitize() doesn't re-add it.
		if ( isset( $intercessor_options[ $key ] ) ) {

			unset( $intercessor_options[ $key ] );

		}

		$did_update = update_option( 'intercessor_settings', $options );

		// If it updated, let's update the global variable.
		if ( $did_update ) {
			global $intercessor_options;
			$intercessor_options = $options;
		}

		return $did_update;
	}
}

if ( ! function_exists( 'intercessor_navigation_tabs' ) ) {
	/**
	 * Setup the navigation tabs markup.
	 *
	 * @since 0.9.5
	 *
	 * @param array  $tabs       Navigation tabs.
	 * @param string $active_tab Active tab slug.
	 * @param array  $query_args Optional. Query arguments used to build the tab URLs. Default empty array.
	 */
	function intercessor_navigation_tabs( $tabs, $active_tab, $query_args = [] ) {
		$tabs = (array) $tabs;

		if ( empty( $tabs ) ) {
			return;
		}

		/**
		 * Filters the navigation tabs immediately prior to output.
		 *
		 * @since 0.9.5
		 *
		 * @param array  $tabs Tabs array.
		 * @param string $active_tab Active tab slug.
		 * @param array  $query_args Query arguments used to build the tab URLs.
		 */
		$tabs = apply_filters( 'intercessor_navigation_tabs', $tabs, $active_tab, $query_args );

		foreach ( $tabs as $tab_id => $tab_name ) {
			$query_args = array_merge( $query_args, [ 'tab' => $tab_id ] );
			$tab_url    = add_query_arg( $query_args );

			printf(
				'<a href="%1$s" alt="%2$s" class="%3$s">%4$s</a>',
				esc_url( $tab_url ),
				esc_attr( $tab_name ),
				$active_tab === $tab_id ? 'nav-tab nav-tab-active' : 'nav-tab',
				esc_html( $tab_name )
			);
		}

		/**
		 * Fires immediately after the navigation tabs output.
		 *
		 * @since 0.9.5
		 *
		 * @param array  $tabs Tabs array.
		 * @param string $active_tab Active tab slug.
		 * @param array  $query_args Query arguments used to build the tab URLs.
		 */
		do_action( 'intercessor_after_navigation_tabs', $tabs, $active_tab, $query_args );
	}
}

if ( ! function_exists( 'intercessor_month_num_to_name' ) ) {
	/**
	 * Month Num To Name
	 *
	 * Takes a month number and returns the name three letter name of it.
	 *
	 * @since 0.9.5
	 *
	 * @param string $n name.
	 * @return string Short month name
	 */
	function intercessor_month_num_to_name( $n ) {
		$timestamp = mktime( 0, 0, 0, $n, 1, 2005 );

		return date_i18n( 'M', $timestamp );
	}
}

if ( ! function_exists( 'intercessor_get_pages' ) ) {
	/**
	 * Retrieve a list of all published pages
	 *
	 * On large sites this can be expensive, so only load if on the settings page or $force is set to true.
	 *
	 * @param bool $force Force the pages to be loaded even if not on settings.
	 *
	 * @since 0.9.5
	 * @return array $pages_options An array of the pages
	 */
	function intercessor_get_pages( $force = false ) {

		$pages_options = [ '' => '' ]; // Blank option.

		if ( ( ! isset( $_GET['page'] ) || 'intercessor-settings' !== $_GET['page'] ) && ! $force ) {
			return $pages_options;
		}

		$pages = get_pages();
		if ( $pages ) {
			foreach ( $pages as $page ) {
				$pages_options[ $page->ID ] = $page->post_title;
			}
		}

		return $pages_options;
	}
}

if ( ! function_exists( 'intercessor_sanitize_html_class' ) ) {
	/**
	 * Sanitize HTML Class Names
	 *
	 * @param string|array $class HTML Class Name(s).
	 *
	 * @since 0.9.5
	 * @return string $class
	 */
	function intercessor_sanitize_html_class( $class = '' ) {

		if ( is_string( $class ) ) {
			$class = sanitize_html_class( $class );
		} elseif ( is_array( $class ) ) {
			$class = array_values( array_map( 'sanitize_html_class', $class ) );
			$class = implode( ' ', array_unique( $class ) );
		}

		return $class;
	}
}

if ( ! function_exists( 'intercessor_clean' ) ) {
	/**
	 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
	 *
	 * Non-scalar values are ignored.
	 *
	 * @param string|array $var variable value.
	 *
	 * @return string|array
	 */
	function intercessor_clean( $var ) {
		if ( is_array( $var ) ) {
			return array_map( 'intercessor_clean', $var );
		} else {
			return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
		}
	}
}

if ( ! function_exists( 'intercessor_sanitize_textarea' ) ) {
	/**
	 * Run intercessor_clean over posted textarea but maintain line breaks.
	 *
	 * @param string $var variable value.
	 *
	 * @return string
	 */
	function intercessor_sanitize_textarea( string $var ) : string {
		return implode( "\n", array_map( 'intercessor_clean', explode( "\n", $var ) ) );
	}
}

if ( ! function_exists( 'intercessor_get_date_format' ) ) {
	/**
	 * Intercessor get date format.
	 *
	 * @param string $format
	 *
	 * @return mixed|string|void
	 *@since 1.0.0
	 */
	function intercessor_get_date_format( string $format = 'date' ) {

		// Default to 'date' if empty.
		if ( empty( $format ) ) {
			$format = 'date';
		}

		// Bail if format is not known.
		if ( ! in_array( $format, array( 'date', 'time', 'datetime', 'mysql', 'date-attribute', 'date-js', 'date-mysql', 'time-mysql' ), true ) ) {
			return $format;
		}

		// What known format are we getting?
		switch ( $format ) {

			// jQuery UI Datepicker fields, placeholders, etc...
			case 'date-attribute':
				$retval = 'yyyy-mm-dd';
				break;

			// jQuery UI Datepicker JS variable.
			case 'date-js':
				$retval = 'yy-mm-dd';
				break;

			// Date in MySQL format.
			case 'date-mysql':
				$retval = 'Y-m-d';
				break;

			// Time in MySQL format.
			case 'time-mysql':
				$retval = 'H:i:s';
				break;

			// MySQL datetime columns.
			case 'mysql':
				$retval = 'Y-m-d H:i:s';
				break;

			// WordPress date_format + time_format.
			case 'datetime':
				$retval = get_option( 'date_format', 'M j, Y' ) . ' ' . get_option( 'time_format', 'g:i a' );
				break;

			// WordPress time_format only.
			case 'time':
				$retval = get_option( 'time_format', 'g:i a' );
				break;

			// WordPress date_format only.
			case 'date':
			default:
				$retval = get_option( 'date_format', 'M j, Y' );
				break;
		}

		return $retval;
	}
}

if ( ! function_exists( 'intercessor_date_i18n' ) ) {
	/**
	 * Retrieves a localized, formatted date based on the WP timezone rather than UTC.
	 *
	 * @param int    $timestamp Timestamp. Can either be based on UTC or WP settings.
	 * @param string $format    Optional. Accepts shorthand 'date', 'time', or 'datetime'
	 *                          date formats, as well as any valid date_format() string.
	 *                          Default 'date' represents the value of the 'date_format' option.
	 *
	 * @return string The formatted date, translated if locale specifies it.
	 * @since 1.0.0
	 *
	 */
	function intercessor_date_i18n( int $timestamp, string $format = 'date' ): string {
		$format = intercessor_get_date_format( $format );

		// If timestamp is a string, attempt to turn it into a timestamp.
		if ( ! is_numeric( $timestamp ) ) {
			$timestamp = strtotime( $timestamp );
		}

		return wp_date( $format, (int) $timestamp );
	}
}

if ( ! function_exists( 'intercessor_get_timezone_id' ) ) {
	/**
	 * Get the timezone.
	 *
	 * @since 1.1.0
	 * @return mixed|string|void
	 */
	function intercessor_get_timezone_id() {
		// Default return value.
		$retval = 'UTC';

		// Get some useful values.
		$timezone   = get_option( 'timezone_string' );
		$gmt_offset = get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS;

		// Use timezone string if it's available
		if ( ! empty( $timezone ) ) {
			$retval = $timezone;

			// Use GMT offset to calculate.
		} elseif ( is_numeric( $gmt_offset ) ) {

			$hours   = abs( floor( $gmt_offset / HOUR_IN_SECONDS ) );
			$minutes = abs( floor( ( $gmt_offset / MINUTE_IN_SECONDS ) % MINUTE_IN_SECONDS ) );
			$math    = ( $gmt_offset >= 0 ) ? '+' : '-';
			$value   = ! empty( $minutes )  ? "{$hours}:{$minutes}" : $hours;
			$retval  = "GMT{$math}{$value}";
		}

		// Set.
		return $retval;
	}
}
if ( ! function_exists('intercessor_doing_ajax') ) {

/**
 * Used for WordPress AJAX checking.
 *
 * @since 1.0.0
 *
 * @return boolean
 */
function intercessor_doing_ajax() {

	// Bail if doing WordPress AJAX.
	if ( wp_doing_ajax() ) {
		return true;
	}

	// Default to false.
	return false;
}
}

/**
 * Checks if a password should be auto-generated for new users.
 *
 * @since 0.9.5
 *
 * @param string $password Password to validate.
 * @return bool True if password meets rules.
 */
function intercessor_validate_new_password( $password ) {
	// Password must be at least 8 characters long.
	$is_valid_password = strlen( trim( $password ) ) >= 8;
	/**
	 * Allows overriding default password validation rules.
	 *
	 * @since 0.9.5
	 *
	 * @param bool   $is_valid_password True if new password is validated.
	 * @param string $password          Password to validate.
	 */
	return apply_filters( 'intercessor_validate_new_password', $is_valid_password, $password );
}

if ( ! function_exists( 'intercessor_get_password_hint' ) ) {
	/**
	 * Returns the password hint.
	 *
	 * @return string
	 */
	function intercessor_get_password_hint() {
		/**
		 * Allows overriding the hint shown below the new password input field.
		 *
		 * Describes rules set in `intercessor_validate_new_password`.
		 *
		 * @since 0.9.5
		 *
		 * @param string $password_rules Password rules description.
		 */
		return apply_filters( 'intercessor_password_rules_hint', esc_html__( 'At least 8 characters long.', 'intercessor' ) );
	}
}

if ( ! function_exists( 'intercessor_get_password2_hint' ) ) {
	/**
	 * Retrieve hint text for password confirmation.
	 *
	 * @since 0.9.5
	 *
	 * @return string
	 */
	function intercessor_get_password2_hint() {
		/**
		 * Allows overriding the hint shown below the new password input field.
		 *
		 * Describes rules set in `intercessor_validate_new_password`.
		 *
		 * @since 0.9.5
		 */
		return apply_filters( 'intercessor_password2_rules_hint', esc_html__( 'Must be the same as the previous password field.', 'intercessor' ) );
	}
}

if ( ! function_exists( 'intercessor_get_current_page_number' ) ) {
	/**
	 * Retrieves the current page number.
	 *
	 * @since 0.9.5
	 *
	 * @return int The current page number.
	 */
	function intercessor_get_current_page_number() {
		if ( is_front_page() ) {
			$page = get_query_var( 'page', 1 );
		} else {
			$page = get_query_var( 'paged', 1 );
		}

		return max( $page, 1 );
	}
}

if ( ! function_exists( 'intercessor_recaptcha_is_enabled' ) ) {
	/**
	 * Verify if reCAPTCHA is enabled in options.
	 *
	 * @since 0.9.5
	 * @return boolean True if it is enabled, otherwise false
	 */
	function intercessor_recaptcha_is_enabled() {
		$use_captcha = intercessor_get_option( 'captcha_type' );
		$site_key    = intercessor_get_option( 'recaptcha_key' );
		$site_secret = intercessor_get_option( 'recaptcha_secret' );
		$is_enabled  = false;

		// Verify if captcha is enabled.
		if ( 'recaptcha' === $use_captcha && ! empty( $site_key ) && ! empty( $site_secret ) ) {
			$is_enabled = true;
		}

		return apply_filters( 'intercessor_recaptcha_enabled', $is_enabled );
	}
}

if ( ! function_exists( 'intercessor_is_valid_recaptcha_response' ) ) {
	/**
	 * Verify if reCAPTCHA response is valid.
	 *
	 * @param array $data The submitted recaptcha values.
	 *
	 * @since  0.9.5
	 *
	 * @return boolean  True if the response is valid, otherwise false
	 */
	function intercessor_is_valid_recaptcha_response( $data ) {
		if ( ! intercessor_recaptcha_is_enabled()
			|| empty( $data['g-recaptcha-response'] )
			|| empty( $data['g-recaptcha-remoteip'] ) ) {

			return false;
		}

		$request = wp_safe_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			[
				'body' => [
					'secret'   => intercessor_get_option( 'recaptcha_secret' ),
					'response' => $data['g-recaptcha-response'],
					'remoteip' => $data['g-recaptcha-remoteip'],
				],
			]
		);

		$verify = json_decode( wp_remote_retrieve_body( $request ) );

		return ( ! empty( $verify->success ) && true === $verify->success );

	}
}

if ( ! function_exists( 'intercessor_is_valid_simple_captcha_response' ) ) {
	/**
	 * Validates if the submitted values are correct for simple captcha.
	 *
	 * @param array $data Submitted data array.
	 *
	 * @since 0.9.5
	 * @return bool True on success, otherwise false.
	 */
	function intercessor_is_valid_simple_captcha_response( $data = [] ) {
		$number1 = intval( intercessor_clean( $data['captcha1'] ) );
		$number2 = intval( intercessor_clean( $data['captcha2'] ) );
		$result  = $number1 + $number2;
		$total   = intval( intercessor_clean( $data['total'] ) );
		$message = intercessor_get_option( 'intercessor_captcha_message' );

		// Bail if no value is submitted.
		if ( empty( $total ) ) {
			intercessor_set_error( 'intercessor_empty_guest_email', $message );
			return false;
		}

		// Check if captcha correctly solved.
		if ( $total === $result ) {
			$is_valid = true;
		} else {
			$is_valid = false;
			intercessor_set_error( 'intercessor_empty_guest_email', $message );
		}

		return $is_valid;
	}
}

if ( ! function_exists( 'intercessor_get_counts_format' ) ) {

	/**
	 * Get the counts format for objects keyed by 'groupby'.
	 *
	 * @param object  $counts  Object counts.
	 * @param string $groupby Groupby key.
	 *
	 * @since  0.9.5
	 * @return array
	 */
	function intercessor_get_counts_format( $counts = [], $groupby = '' ) {
		// Default array values.
		$default = [
			'total' => 0,
		];

		$items = $counts->items;

		// Get counts value.
		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				$default[ $item[ $groupby ] ] = absint( $item['count'] );

				if ( ! isset( $item['status'] ) ) {
					$default['total'] += $item['count'];
				}
			}

			// Get total.
			$default['total'] = array_sum( $default );
		}

		return $default;
	}
}

if ( ! function_exists( 'intercessor_get_ip' ) ) {
	/**
	 * Get visitor IP address
	 *
	 * Used for reCAPTCHA validation
	 *
	 * @since 0.9.5
	 *
	 * @return string $ip IP address of visitor
	 */
	function intercessor_get_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			// Check ip from share internet.
			$ip = wp_unslash( $_SERVER['HTTP_CLIENT_IP'] );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// Check if ip is passed from proxy.
			$ip = wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		} else {
			if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip = wp_unslash( $_SERVER['REMOTE_ADDR'] );
			}
		}

		return apply_filters( 'intercessor_get_ip', $ip );
	}
}

if ( ! function_exists( 'intercessor_get_hour_values' ) ) {
	/**
	 * Return an array of values used to populate an hour dropdown
	 *
	 * @since 0.9.5
	 *
	 * @return array
	 */
	function intercessor_get_hour_values() {
		$hour_values = [
			'00' => '00',
			'01' => '01',
			'02' => '02',
			'03' => '03',
			'04' => '04',
			'05' => '05',
			'06' => '06',
			'07' => '07',
			'08' => '08',
			'09' => '09',
			'10' => '10',
			'11' => '11',
			'12' => '12',
			'13' => '13',
			'14' => '14',
			'15' => '15',
			'16' => '16',
			'17' => '17',
			'18' => '18',
			'19' => '19',
			'20' => '20',
			'21' => '21',
			'22' => '22',
			'23' => '23',
			'24' => '24',
		];

		return (array) apply_filters( 'intercessor_get_hour_values', $hour_values );
	}
}

if ( ! function_exists( 'intercessor_get_minute_values' ) ) {
	/**
	 * Return an array of values used to populate a minute dropdown
	 *
	 * @since 0.9.5
	 *
	 * @return array
	 */
	function intercessor_get_minute_values() {
		$minute_values = [
			'00' => '00',
			'01' => '01',
			'02' => '02',
			'03' => '03',
			'04' => '04',
			'05' => '05',
			'06' => '06',
			'07' => '07',
			'08' => '08',
			'09' => '09',
			'10' => '10',
			'11' => '11',
			'12' => '12',
			'13' => '13',
			'14' => '14',
			'15' => '15',
			'16' => '16',
			'17' => '17',
			'18' => '18',
			'19' => '19',
			'20' => '20',
			'21' => '21',
			'22' => '22',
			'23' => '23',
			'24' => '24',
			'25' => '25',
			'26' => '26',
			'27' => '27',
			'28' => '28',
			'29' => '29',
			'30' => '30',
			'31' => '31',
			'32' => '32',
			'33' => '33',
			'34' => '34',
			'35' => '35',
			'36' => '36',
			'37' => '37',
			'38' => '38',
			'39' => '39',
			'40' => '40',
			'41' => '41',
			'42' => '42',
			'43' => '43',
			'44' => '44',
			'45' => '45',
			'46' => '46',
			'47' => '47',
			'48' => '48',
			'49' => '49',
			'50' => '50',
			'51' => '51',
			'52' => '52',
			'53' => '53',
			'54' => '54',
			'55' => '55',
			'56' => '56',
			'57' => '57',
			'58' => '58',
			'59' => '59',
		];

		return (array) apply_filters( 'intercessor_get_minute_values', $minute_values );
	}
}

if ( ! function_exists( 'intercessor_get_month_name' ) ) {

	/**
	 * Get a Month's Name
	 *
	 * Takes a month number and returns the name three letter name of it.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $n Name.
	 * @return string Short month name
	 */
	function intercessor_get_month_name( $n ) {
		$timestamp = mktime( 0, 0, 0, $n, 1, 2005 );

		return date_i18n( 'M', $timestamp );
	}
}

if ( ! function_exists( 'intercessor_ajax_requester_search' ) ) {
	/**
	 * Search the Requesters database via AJAX
	 *
	 * @since 0.9.5
	 * @return void
	 */
	function intercessor_ajax_requester_search() {
		global $wpdb;

		$search  = esc_sql( sanitize_text_field( $_GET['s'] ) );
		$results = [];

		// Specify role that can view Requesters in search.
		$requester_view_role = apply_filters( 'intercessor_view_requesters_role', 'view_prayer_reports' );

		if ( ! current_user_can( $requester_view_role ) ) {
			$requesters = [];
		} else {
			$select = "SELECT id, name, email FROM {$wpdb->prefix}.ipr_requesters ";

			if ( is_numeric( $search ) ) {
				$where = "WHERE `id` LIKE '%$search%' OR `user_id` LIKE '%$search%' ";
			} else {
				$where = "WHERE `name` LIKE '%$search%' OR `email` LIKE '%$search%' ";
			}

			$limit = "LIMIT 50";

			$requesters = $wpdb->get_results( $select . $where . $limit );
		}

		if ( $requesters ) {

			foreach ( $requesters as $requester ) {

				$results[] = array(
					'id'   => $requester->id,
					'name' => $requester->name . '(' . esc_attr( $requester->email ) . ')',
				);
			}
		} else {

			$requesters[] = [
				'id'   => 0,
				'name' => esc_html__( 'No results found', 'intercessor' ),
			];

		}

		echo wp_json_encode( $results );

		intercessor_die();
	}
}

if ( ! function_exists( 'intercessor_ajax_user_search' ) ) {
	/**
	 * Search the users database via AJAX
	 *
	 * @since 0.9.5
	 * @return void
	 */
	function intercessor_ajax_user_search() {

		// Default results.
		$results = [
			'id'   => 0,
			'name' => esc_html__( 'No users found', 'intercessor' ),
		];

		// Default user role.
		$user_view_role = apply_filters( 'intercessor_view_users_role', 'view_prayer_reports' );

		// User can view users.
		if ( current_user_can( $user_view_role ) ) {
			$search = esc_sql( sanitize_text_field( $_GET['s'] ) );
			$users  = [];

			// Searching.
			if ( ! empty( $search ) ) {
				$users = get_users(
					array(
						'search' => '*' . $search . '*',
						'number' => 50,
					)
				);
			}

			// Setup results based on users.
			if ( ! empty( $users ) ) {
				$results = [];

				foreach ( $users as $user ) {
					$results[] = array(
						'id'   => $user->ID,
						'name' => $user->display_name,
					);
				}
			}
		}

		echo wp_json_encode( $results );

		intercessor_die();
	}
}

if ( ! function_exists( 'intercessor_ajax_search_users' ) ) {
	/**
	 * Searches for users via ajax and returns a list of results
	 *
	 * @since 0.9.5
	 * @return void
	 */
	function intercessor_ajax_search_users() {

		// Bail if user cannot manage prayer settings.
		if ( ! current_user_can( 'manage_prayer_settings' ) ) {
			die();
		}

		// To search for.
		$search_query = ! empty( $_POST['user_name'] )
			? trim( $_POST['user_name'] )
			: '';

		// To exclude.
		$exclude = ! empty( $_POST['exclude'] )
			? trim( $_POST['exclude'] )
			: '';

		// Default args.
		$defaults = array(
			'number' => 50,
			'search' => $search_query . '*',
		);

		// Maybe exclude users.
		if ( ! empty( $exclude ) ) {
			$exclude_array       = explode( ',', $exclude );
			$defaults['exclude'] = $exclude_array;
		}

		// Filter query args.
		$get_users_args = apply_filters( 'intercessor_search_users_args', $defaults );

		// Maybe get users.
		$users = ! empty( $get_users_args ) && ! empty( $search_query )
			? get_users( $get_users_args )
			: [];

		// Filter users.
		$found_users = apply_filters( 'intercessor_ajax_found_users', $users, $search_query );

		// Put together the results string.
		$user_list = '<ul>';
		if ( ! empty( $found_users ) ) {
			foreach ( $found_users as $user ) {
				$user_list .= '<li><a href="#" data-userid="' . esc_attr( $user->ID ) . '" data-login="' . esc_attr( $user->user_login ) . '">' . esc_html( $user->user_login ) . '</a></li>';
			}
		} else {
			$user_list .= '<li class="no-users">' . esc_html__( 'No user found', 'intercessor' ) . '</li>';
		}
		$user_list .= '</ul>';

		echo wp_json_encode( array( 'results' => $user_list ) );

		intercessor_die();
	}
}

if ( ! function_exists( 'intercessor_time_ago' ) ) {
	/**
	 * Get the time ago prayer request was submitted.
	 *
	 * @param string $date Date prayer submitted.
	 * @param string $gmt_offset GMT offset.
	 *
	 * @return string
	 * @since 0.9.5
	 */
	function intercessor_time_ago( string $date, string $gmt_offset ): string {
		// Set up variables.
		$date  = strtotime( $date ) ? strtotime( $date ) : $date;
		$time  = time() - $date;
		$value = '';

		// Process different time ranges.
		switch ( $time ) {
			// Seconds.
			case $time <= 60:
				$value = esc_html__( 'less than a minute ago', 'intercessor' );
				break;

			// Minutes.
			case $time >= 60 && $time < 3600:
				$value = ( round( $time / 60 ) === 1 )
					? esc_html__( '1 minute ago', 'intercessor' )
					: round( $time / 60 ) . esc_html__( ' minutes ago', 'intercessor' );
				break;

			// Hours.
			case $time >= 3600 && $time < 86400:
				$value = ( round( $time / 3600 ) === 1 )
					? esc_html__( '1 hour ago', 'intercessor' )
					: round( $time / 3600 ) . esc_html__( ' hours ago', 'intercessor' );
				break;

			// Days.
			case $time >= 86400 && $time < 604800:
				$value = ( round( $time / 86400 ) < 2 )
					? esc_html__( '1 day ago', 'intercessor' )
					: round( $time / 86400 ) . esc_html__( ' days ago', 'intercessor' );
				break;

			// Weeks, Months and Years.
			case $time >= 31207680:
            case $time < 31207680:
            case $time >= 604800 && $time < 2600640:
				$value = 'on ' . date( 'F j, Y \a\t h:i A', $date + $gmt_offset * 3600 );
				break;

			// Months and years.

        }

		// Return the date and time values.
		return $value;
	}
}

if ( ! function_exists( 'intercessor_get_default_terms' ) ) {
	/**
	 * Get default terms of service text.
	 *
	 * @return mixed|void
	 * @since 0.9.5
	 */
	function intercessor_get_default_terms() {
		$site_name = esc_attr( get_bloginfo( 'name' ) );

		$text = sprintf(
			'<p>%1$s is committed to maintaining your trust and confidence. Prayer requests submitted through this form will be prayed for within a specified period of time. %1$s will send you a confirmation email upon successful submission of the prayer request form.</p>
			<p>%1$s will not send you any other e-mail that you have not agreed to receive. %1$s will respect the intent of the prayer requester relating to private and anonymous prayers.</p>
			<p>%1$s may periodically email you to inform you when you have been prayed for, if you selected the option during prayer request form submission.</p>',
			$site_name
		);

		return apply_filters( 'intercessor_default_terms_of_service', $text, $site_name );
	}
}

if ( ! function_exists( 'intercessor_redirect' ) ) {
	/**
	 * Redirects to a specific page or lacation
	 *
	 * @param string $location Location to redirect to.
	 * @param int $status   Status of the redirect. Default 302.
	 *
	 * @since 1.0.0
	 */
	function intercessor_redirect( string $location = '', int $status = 302 ) {
		// Prevent redirects in unit tests.
		if ( (bool) ( defined( 'WP_TESTS_DIR' ) && WP_TESTS_DIR ) || function_exists( '_manually_load_plugin' ) ) {
			return;
		}

		// Prevent errors from empty $location.
		if ( empty( $location ) ) {
			$location = is_admin()
				? admin_url()
				: home_url();
		}

		// Setup the safe redirect.
		wp_safe_redirect( $location, $status );

		// Exit.
		intercessor_die();
	}
}

if ( ! function_exists( 'intercessor_get_current_time' ) ) {
	/**
	 * Return the current time as a UTC timestamp.
	 *
	 * @since 0.9.5
	 *
	 * @return string
	 */
	function intercessor_get_current_time() : string {
		return gmdate( 'Y-m-d\TH:i:s\Z' );
	}
}

if ( ! function_exists( 'intercessor_gmt_offset' ) ) {
	/**
	 * Retrieve the GMT offset from the database.
	 *
	 * @since 0.9.5
	 */
	function intercessor_gmt_offset() {
		return get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS;
	}
}

if ( ! function_exists( 'intercessor_register_widgets' ) ) {
	/**
	 * Register the widgets with WordPress.
	 */
	function intercessor_register_widgets() {
		register_widget( 'Intercessor\Recent_Prayers' );
	}
}

if ( ! function_exists( 'intercessor_limit_text' ) ) {
	/**
	 * Limit the words displayed by prayer message.
	 *
	 * @param string $text  Text.
	 * @param int $limit Number of word limit.
	 *
	 * @return string
	 *@since 0.9.5
	 */
	function intercessor_limit_text( string $text, int $limit ): string {
		if ( str_word_count( $text, 0 ) > $limit ) {
			$words    = str_word_count( $text, 2 );
			$position = array_keys( $words );
			$text     = substr( $text, 0, $position[ $limit ] )  .  '...';
		}

		// return limited contents.
		return $text;
    }
}

if ( ! function_exists( 'intercessor_get_status_label' ) ) {
	/**
	 * Get the label for a status
	 *
	 * @param string $status
	 *
	 * @return string Label for the status
	 *@since 1.0.0
	 *
	 */
	function intercessor_get_status_label( string $status = '' ): string {
		static $labels = null;

		// Array of status labels
		if ( null === $labels ) {
			$labels = array(

				// Prayers.
				'pending'  => esc_html__( 'Pending', 'intercessor' ),
				'active'   => esc_html__( 'Active', 'intercessor' ),
				'personal' => esc_html__( 'Private', 'intercessor' ),
				'archived' => esc_html__( 'Archived', 'intercessor' ),
			);
		}

		// Return the label if set, or uppercase the first letter if not.
		$retval = $labels[ $status ] ?? ucwords( $status );

		// Filter & return
		return apply_filters( 'intercessor_get_status_label', $retval, $status );
	}
}

if ( ! function_exists( 'intercessor_allowed_tags' ) ) {
	/**
	 * Get allowed HTML tags.
	 *
	 * @since 1.0.0
	 * @return array $allowed_tags Array of allowed tags.
	 */
	function intercessor_allowed_tags() : array {
		$tags = [
			'a' => [
				'href'   => [],
				'target' => [],
				'title'  => [],
				'class'  => [],
				'id'     => [],
			],
			'p' => [
				'class' => [],
				'id'    => [],
			],
			'span' => [
				'class' => [],
				'id'    => [],
			],
			'strong' => [],
			'em' => [],
			'br' => [],
			'img' => [
				'src'   => [],
				'title' => [],
				'alt'   => [],
				'id'    => [],
			],
			'div' => [
				'class' => [],
				'id'    => [],
			],
			'ul' => [
				'class' => [],
				'id'    => [],
			],
			'li' => [
				'class' => [],
				'id'    => [],
			],
		];

		return (array) apply_filters( 'intercessor_allowed_html_tags', $tags );
	}
}

if ( ! function_exists( 'intercessor_get_file_extension' ) ) {
    /**
     * Gets File Extension
     *
     * Returns the file extension of a filename.
     *
     * @param string $str File extension name.
     *
     * @return mixed File extension
     * @since 1.0.0
     *
     */
    function intercessor_get_file_extension( $str ) {
        $parts          = explode('.', $str );
        $file_extension = end($parts );

        if ( false !== strpos( $file_extension, '?' ) ) {
            $file_extension = substr( $file_extension, 0, strpos( $file_extension, '?' ) );
        }

        return $file_extension;
    }
}

if ( ! function_exists( 'intercessor_is_func_disabled' ) ) {

	/**
	 * Checks whether function is disabled.
	 *
	 * @since 1.3.5
	 * @since 3.0.0 String type-checking the `in_[` call
	 *
	 * @param string $function Name of the function.
	 * @return bool Whether or not function is disabled.
	 */
	function intercessor_is_func_disabled( string $function ) : bool {
		$disabled = explode( ',', @ini_get( 'disable_functions' ) );

		return in_array( $function, $disabled, true );
	}

}

if ( ! function_exists( 'intercessor_set_time_limit' ) ) {

	/**
	 * Ignore the time limit set by the server (likely from php.ini.)
	 *
	 * The $time_limit parameter is filterable, but infinite values are not allowed
	 * so any erroneous processes are able to terminate normally.
	 *
	 * @param boolean $ignore_user_abort Whether to call ignore_user_about( true )
	 * @param int     $time_limit        How long to set the time limit to. Cannot be 0. Default 6 hours.
	 *
	 * @since 1.0.0
	 *
	 */
	function intercessor_set_time_limit( bool $ignore_user_abort = true, int $time_limit = 21600 ) {

		// Default time limit is 6 hours
		$default = HOUR_IN_SECONDS * 6;

		// Only abort if true and if function is enabled
		if ( ( true === $ignore_user_abort ) && ! intercessor_is_func_disabled( 'ignore_user_abort' ) ) {
			@ignore_user_abort( true );
		}

		/**
		 * Filter the time limit to set for this request.
		 *
		 * Infinite (0) values are not allowed so any erroneous processes are able
		 * to terminate normally.
		 *
		 * @since 1.0.0
		 *
		 * @param int $time_limit The time limit in nano-seconds. Default 6 hours.
		 *
		 * @return int $time_limit The filtered time limit value. Default 6 hours.
		 */
		$time_limit = (int) apply_filters( 'intercessor_set_time_limit', $time_limit );

		// Disallow infinite values
		if ( empty( $time_limit ) ) {
			$time_limit = $default;
		}

		// Set time limit to non-infinite value if function is enabled
		if ( ! intercessor_is_func_disabled( 'set_time_limit' ) ) {
			@set_time_limit( $time_limit );
		}

		// Attempt to raise the memory limit. See: intercessor_set_batch_memory_limit()
		wp_raise_memory_limit( 'intercessor_batch' );
	}
}

if ( ! function_exists( 'intercessor_get_path' ) ) {
	/**
	 * Get Intercessor directory path.
	 *
	 * @param string $filename The specified file.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	function intercessor_get_path( string $filename = '' ): string {
		return INTERCESSOR_DIR . ltrim( $filename, '/' );
	}
}

if ( ! function_exists( 'intercessor_object_to_array' ) ) {

	/**
	 * Convert object or array of objects to array.
	 *
	 * @param array $object An object or an array of objects
	 *
	 * @return array  An array or array of arrays, converted from the provided object(s)
	 * @since    1.1.0
	 */
	function intercessor_object_to_array( array $object = [] ): array {

		if ( empty( $object ) || ( ! is_object( $object ) && ! is_array( $object ) ) ) {
			return $object;
		}

		if ( is_array( $object ) ) {
			$return = [];
			foreach ( $object as $item ) {
				if ( $object instanceof \Intercessor\Prayer ) {
					$return[] = $object->array_convert();
				} else {
					$return[] = intercessor_object_to_array( $item );
				}
			}
		} else {
			if ( $object instanceof \Intercessor\Prayer ) {
				$return = $object->array_convert();
			} else {
				$return = get_object_vars( $object );

				// Convert to array.
				foreach ( $return as $key => $value ) {
					$value = ( is_array( $value ) || is_object( $value ) ) ? intercessor_object_to_array( $value ) : $value;
					$return[ $key ] = $value;
				}
			}
		}

		// Return convert array.
		return $return;
	}
}

if ( ! function_exists( 'intercessor_date_format' ) ) {

	/**
	 * Get date format string on basis of given context.
	 *
	 * @param string $date_context Date format context name.
	 *
	 * @return string Date format string
	 * @since 1.0.0
	 */
	function intercessor_date_format( string $date_context = '' ): string {
		/**
		 * Filter the date context
		 */
		$date_format_contexts = apply_filters( 'intercessor_date_format_contexts', array() );

		// Set date format to default date format.
		$date_format = get_option( 'date_format' );

		// Update date format.
		if ( $date_context && ! empty( $date_format_contexts )
		     && array_key_exists( $date_context, $date_format_contexts ) ) {
			$date_format = ! empty( $date_format_contexts[ $date_context ] )
				? $date_format_contexts[ $date_context ]
				: $date_format;
		}

		// Return updated date format.
		return apply_filters( 'intercessor_date_format', $date_format );
	}
}
