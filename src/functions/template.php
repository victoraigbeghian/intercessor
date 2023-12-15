<?php
/**
 * Template Functions
 *
 * @package     Intercessor
 * @subpackage  Functions/Template
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Returns the path to the intercessor templates directory
 *
 * @since 1.0.0
 * @return string
 */
function intercessor_get_templates_dir() {
    return INTERCESSOR_DIR . 'templates';
}

/**
 * Returns the URL to the intercessor templates directory
 *
 * @since 1.0.0
 * @return string
 */
function intercessor_get_templates_url() {
    return INTERCESSOR_URL . 'templates';
}

/**
 * Get other templates, passing attributes and including the file.
 *
 * @since 1.0.0
 *
 * @param string $template_name Template file name.
 * @param array  $args          Passed arguments. Default is empty array().
 * @param string $template_path Template file path. Default is empty.
 * @param string $default_path  Default path. Default is empty.
 */
function intercessor_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
    if ( ! empty( $args ) && is_array( $args ) ) {
        extract( $args );
    }

    $template_names = "{$template_name}.php";

    $located = intercessor_get_locate_template( $template_names, $template_path, $default_path );

    if ( ! file_exists( $located ) ) {
        /* translators: %s: the template */
        intercessor_display_frontend_notice( sprintf( __( 'The %s template was not found.', 'intercessor' ), $located ), true );

        return;
    }

    // Allow 3rd party plugin filter template file from their plugin.
    $located = apply_filters( 'intercessor_get_template', $located, $template_name, $args, $template_path, $default_path );

    /**
     * Fires in intercessor template, before the file is included.
     *
     * Allows you to execute code before the file is included.
     *
     * @since 1.0.0
     *
     * @param string $template_name Template file name.
     * @param string $template_path Template file path.
     * @param string $located       Template file filter by 3rd party plugin.
     * @param array  $args          Passed arguments.
     */
    do_action( 'intercessor_before_template_part', $template_name, $template_path, $located, $args );

    include $located;

    /**
     * Fires in intercessor template, after the file is included.
     *
     * Allows you to execute code after the file is included.
     *
     * @since 1.0.0
     *
     * @param string $template_name Template file name.
     * @param string $template_path Template file path.
     * @param string $located       Template file filter by 3rd party plugin.
     * @param array  $args          Passed arguments.
     */
    do_action( 'intercessor_after_template_part', $template_name, $template_path, $located, $args );
}

/**
 * Retrieves a template part
 *
 * Taken from bbPress.
 *
 * @since 1.0.0
 *
 * @param string $slug Template part file slug {slug}.php.
 * @param string $name Optional. Template part file name {slug}-{name}.php. Default is null.
 * @param bool   $load If true the template file will be loaded, if it is found.
 *
 * @return string
 */
function intercessor_get_template_part( $slug, $name = null, $load = true ) {

    /**
     * Fires in intercessor template part, before the template part is retrieved.
     *
     * Allows you to execute code before retrieving the template part.
     *
     * @since 1.0.0
     *
     * @param string $slug Template part file slug {slug}.php.
     * @param string $name Template part file name {slug}-{name}.php.
     */
    do_action( "get_template_part_{$slug}", $slug, $name );

    // Setup possible parts
    $templates = array();
    if ( isset( $name ) ) {
        $templates[] = $slug . '-' . $name . '.php';
    }
    $templates[] = $slug . '.php';

    // Allow template parts to be filtered
    $templates = apply_filters( 'intercessor_get_template_part', $templates, $slug, $name );

    // Return the part that is found
    return intercessor_locate_template( $templates, $load, false );
}

/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
 * inherit from a parent theme can just overload one file. If the template is
 * not found in either of those, it looks in the theme-compat folder last.
 *
 * Forked from bbPress
 *
 * @since 1.0.0
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool         $load           If true the template file will be loaded if it is found.
 * @param bool         $require_once   Whether to require_once or require. Default true.
 *                                     Has no effect if $load is false.
 *
 * @return string The template filename if one is located.
 */
function intercessor_locate_template( $template_names, $load = false, $require_once = true ) {
    // No file found yet
    $located = false;

    $theme_template_paths = intercessor_get_theme_template_paths();

    // Try to find a template file
    foreach ( (array) $template_names as $template_name ) {

        // Continue if template is empty
        if ( empty( $template_name ) ) {
            continue;
        }

        // Trim off any slashes from the template name
        $template_name = ltrim( $template_name, '/' );

        // try locating this template file by looping through the template paths
        foreach ( $theme_template_paths as $template_path ) {

            if ( file_exists( $template_path . $template_name ) ) {
                $located = $template_path . $template_name;
                break;
            }
        }

        if ( $located ) {
            break;
        }
    }

    if ( ( true == $load ) && ! empty( $located ) ) {
        load_template( $located, $require_once );
    }

    return $located;
}

/**
 * Locate a template and return the path for inclusion.
 *
 * @since  1.0.0
 * @access public
 *
 * @param string $template_name
 * @param string $template_path (default: '')
 * @param string $default_path  (default: '')
 *
 * @return string
 */
function intercessor_get_locate_template( $template_name, $template_path = '', $default_path = '' ) {
    if ( ! $template_path ) {
        $template_path = intercessor_get_theme_template_dir_name() . '/';
    }

    if ( ! $default_path ) {
        $default_path = INTERCESSOR_DIR . 'templates/';
    }

    // Look within passed path within the theme - this is priority.
    $template = locate_template(
        array(
            trailingslashit( $template_path ) . $template_name,
            $template_name,
        )
    );

    // Get default template/
    if ( ! $template ) {
        $template = $default_path . $template_name;
    }

    /**
     * Filter the template
     *
     * @since 1.0.0
     */
    return apply_filters( 'intercessor_get_locate_template', $template, $template_name, $template_path );
}

/**
 * Returns the template directory name.
 *
 * Themes can filter this by using the intercessor_templates_dir filter.
 *
 * @since 1.0.0
 * @return string
 */
function intercessor_get_theme_template_dir_name() {
    return trailingslashit( apply_filters( 'intercessor_templates_dir', 'intercessor' ) );
}

/**
 * Returns a list of paths to check for template locations
 *
 * @since 1.0.0
 * @return array
 */
function intercessor_get_theme_template_paths() {

    $template_dir = intercessor_get_theme_template_dir_name();

    $file_paths = array(
        1   => trailingslashit( get_stylesheet_directory() ) . $template_dir,
        10  => trailingslashit( get_template_directory() ) . $template_dir,
        100 => intercessor_get_templates_dir(),
    );

    $file_paths = apply_filters( 'intercessor_template_paths', $file_paths );

    // sort the file paths based on priority
    ksort( $file_paths, SORT_NUMERIC );

    return array_map( 'trailingslashit', $file_paths );
}
