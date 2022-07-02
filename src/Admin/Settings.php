<?php
/**
 * Intercessor Settings Class
 *
 * @package     Intercessor
 * @subpackage  Admin/Settings
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       1.0.0
 */

namespace Intercessor\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Intercessor Settings Class
 *
 * @since 1.0.0
 */
class Settings {
	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new static();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Initialize our plugin settings.
	 *
	 * @since 1.0.0
	 */
	private function init() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_filter( 'intercessor_settings_sanitize_text', [ $this, 'text_field_sanitization' ] );
		add_filter( 'intercessor_after_setting_output', [ $this, 'add_tooltip' ], 10, 2 );
	}

	/**
	 * Install default settings.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function install() {
		global $intercessor_options;

		// Default options.
		$options = [];

		// Add default values.
		$all_settings = $this->get_defined_settings();

		if ( ! empty( $all_settings ) ) {
			foreach ( $all_settings as $tab => $sections ) {
				foreach ( $sections as $section => $settings ) {
					$tab_sections = $this->get_tabbed_sections( $tab );
					if ( ! is_array( $tab_sections ) || ! array_key_exists( $section, $tab_sections ) ) {
						$section  = 'main';
						$settings = $sections;
					}

					foreach ( $settings as $option ) {
						if ( ! empty( $option['type'] ) && 'checkbox' === $option['type'] && ! empty( $option['std'] ) ) {
							$options[ $option['id'] ] = '1';
						}
					}
				}
			}
		}

		// Retrieve settings.
		$settings            = \get_option( 'intercessor_settings', [] );
		$merged_settings     = array_merge( $settings, $options );
		$intercessor_options = $merged_settings;

		// Update settings.
		\update_option( 'intercessor_settings', $merged_settings );
	}
	/**
	 * Output the settings page
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function render() {
		$settings_tabs = $this->get_settings_tabs();
		$settings_tabs = empty( $settings_tabs ) ? [] : $settings_tabs;
		$active_tab    = isset( $_GET['tab'] ) ? \sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
		$active_tab    = array_key_exists( $active_tab, $settings_tabs ) ? $active_tab : 'general';
		$sections      = $this->get_tabbed_sections( $active_tab );
		$key           = 'main';

		if ( ! empty( $sections ) ) {
			$key = key( $sections );
		}

		$defined_sections = $this->get_tabbed_sections( $active_tab );
		$section          = isset( $_GET['section'] ) && ! empty( $defined_sections ) && array_key_exists( wp_unslash( $_GET['section'] ), $defined_sections ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : $key;

		// Unset 'main' if it's empty and default to the first non-empty.
		$all_settings = $this->get_defined_settings();

		// Let's verify if a 'main' section exists.
		$has_main_settings = true;
		if ( empty( $all_settings[ $active_tab ]['main'] ) ) {
			$has_main_settings = false;
		}

		if ( ! $has_main_settings ) {
			foreach ( $all_settings[ $active_tab ] as $sid => $stitle ) {
				if ( is_string( $sid ) && ! empty( $sections ) && array_key_exists( $sid, $sections ) ) {
					continue;
				} else {
					$has_main_settings = true;
					break;
				}
			}
		}

		$override = false;
		if ( false === $has_main_settings ) {
			unset( $sections['main'] );

			if ( 'main' === $section ) {
				foreach ( $sections as $section_key => $section_title ) {
					if ( ! empty( $all_settings[ $active_tab ][ $section_key ] ) ) {
						$section  = $section_key;
						$override = true;
						break;
					}
				}
			}
		}

		// Enqueue necessary scripts.
		wp_enqueue_script( 'intercessor-settings' );

        \intercessor_settings_display( $settings_tabs, $active_tab, $sections, $section, $override );
	}

	/**
	 * Retrieve the settings tabs
	 *
	 * @access  private
	 * @since   1.0.0
	 * @return  array $tabs The registered tabs for this plugin
	 */
	private function get_settings_tabs() {
		return apply_filters( 'intercessor_settings_tabs', [] );
	}

	/**
	 * Retrieve settings tab sections
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param bool $tab The current tab.
	 *
	 * @return  array $section The section items
	 */
	public function get_tabbed_sections( $tab = false ) {
		$tabs     = [];
		$sections = $this->get_defined_settings_sections();

		if ( $tab && ! empty( $sections[ $tab ] ) ) {
			$tabs = $sections[ $tab ];
		} elseif ( $tab ) {
			$tabs = [];
		}

		return $tabs;
	}

	/**
	 * Retrieve the plugin settings
	 *
	 * @access public
	 * @since   1.0.0
	 *
	 * @return  array $settings The plugin settings
	 */
	public function get_defined_settings() {
		return apply_filters( 'intercessor_defined_settings', [] );
	}

	/**
	 * Retrieve the plugin settings sections
	 *
	 * @access  private
	 * @since   1.0.0
	 *
	 * @return  array $sections The registered sections
	 */
	private function get_defined_settings_sections() {
		global $intercessor_sections;

		if ( ! empty( $intercessor_sections ) ) {
			return $intercessor_sections;
		}

		$intercessor_sections = apply_filters( 'intercessor_main_sections', [] );

		return $intercessor_sections;
	}

	/**
	 * Retrieve all options
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @return  array $settings The options array
	 */
	public function get_settings() {
		$settings = get_option( 'intercessor_settings', [] );

		if ( empty( $settings ) ) {
			$settings = [];

			\update_option( 'intercessor_settings', $settings );
		}

		return apply_filters( 'intercessor_get_settings', $settings );
	}

	/**
	 * Add settings sections and fields
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function register_settings() {
		if ( false === \get_option( 'intercessor_settings' ) ) {
			\add_option( 'intercessor_settings' );
		}

		foreach ( $this->get_defined_settings() as $tab => $sections ) {
			foreach ( $sections as $section => $settings ) {
				$section_tabs = $this->get_tabbed_sections( $tab );
				if ( ! is_array( $section_tabs )
				     || ! array_key_exists( $section, $section_tabs ) ) {
					$section  = 'main';
					$settings = $sections;
				}

				// Add settings section.
				\add_settings_section(
					'intercessor_settings_' . $tab . '_' . $section,
					__return_null(),
					'__return_false',
					'intercessor_settings_' . $tab . '_' . $section
				);

				foreach ( $settings as $option ) {

					// Parse settings args.
					$args = wp_parse_args(
						$option,
						array(
							'allow_blank'   => true,
							'chosen'        => null,
							'desc'          => '',
							'faux'          => false,
							'field_class'   => '',
							'id'            => null,
							'max'           => null,
							'min'           => null,
							'multiple'      => null,
							'name'          => '',
							'options'       => '',
							'placeholder'   => null,
							'readonly'      => false,
							'section'       => $section,
							'size'          => null,
							'std'           => '',
							'step'          => null,
							'tooltip_desc'  => false,
							'tooltip_title' => false,
						)
					);

					// Add settings field.
					\add_settings_field(
						'intercessor_settings[' . $args['id'] . ']',
						$args['name'],
						function_exists( 'intercessor_' . $option['type'] . '_callback' ) ? 'intercessor_' . $option['type'] . '_callback' : ( method_exists( $this, $args['type'] . '_callback' ) ? array( $this, $args['type'] . '_callback' ) : array( $this, 'missing_callback' ) ),
						'intercessor_settings_' . $tab . '_' . $section,
						'intercessor_settings_' . $tab . '_' . $section,
						$args
					);
				}
			}
		}

		register_setting(
			'intercessor_settings',
			'intercessor_settings',
			array( $this, 'settings_sanitize' )
		);
	}


	/**
	 * Settings sanitization
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param array $input The value entered in the field.
	 *
	 * @return array $input The sanitized value
	 * @global array $intercessor_options The options array
	 */
	public function settings_sanitize( $input = [] ) {
		global $intercessor_options;

		$doing_section = false;

		if ( ! empty( $_POST['_wp_http_referer'] ) ) {
			$doing_section = true;
		}

		$setting_types = $this->get_defined_settings_types();
		$input         = $input ? $input : [];

		if ( $doing_section ) {
			parse_str( $_POST['_wp_http_referer'], $referrer );
			$tab     = isset( $referrer['tab'] ) ? $referrer['tab'] : 'general';
			$section = isset( $referrer['section'] ) ? $referrer['section'] : 'main';

			if ( ! empty( $_POST['intercessor_section_override'] ) ) {
				$section = sanitize_text_field( wp_unslash( $_POST['intercessor_section_override'] ) );
			}

			$setting_types = $this->get_defined_settings_types( $tab, $section );

			// Run a general sanitization for the tabs.
			$input = apply_filters( 'intercessor_settings_' . $tab . '_sanitize', $input );

			// Run a general sanitization for the section.
			$input = apply_filters( 'intercessor_settings_' . $tab . '_' . $section . '_sanitize', $input );

		}

		$output = array_merge( $intercessor_options, $input );

		foreach ( $setting_types as $key => $type ) {
			if ( empty( $type ) ) {
				continue;
			}

			// Skip non-setting settings.
			$non_setting_types = apply_filters(
				'intercessor_non_setting_types',
				[
					'descriptive_text',
					'header',
					'hook',
				]
			);

			if ( in_array( $type, $non_setting_types, true ) ) {
				continue;
			}

			if ( array_key_exists( $key, $output ) ) {
				$output[ $key ] = apply_filters( 'intercessor_settings_sanitize_' . $type, $output[ $key ], $key );
				$output[ $key ] = apply_filters( 'intercessor_settings_sanitize', $output[ $key ], $key );
			}

			if ( $doing_section ) {
				switch ( $type ) {
					case 'checkbox':
					case 'multicheck':
						if ( array_key_exists( $key, $input ) && $output[ $key ] === '-1' ) {
							unset( $output[ $key ] );
						}
						break;
					case 'text':
					default:
						if ( array_key_exists( $key, $input ) && empty( $input[ $key ] ) ) {
							unset( $output[ $key ] );
						}
						break;
				}
			} else {
				if ( empty( $input[ $key ] ) ) {
					unset( $output[ $key ] );
				}
			}
		}

		if ( $doing_section ) {
			add_settings_error(
				'intercessor-notices',
				'',
				esc_html__( 'Settings successfully updated.', 'intercessor' ),
				'updated'
			);
		}

		return $output;
	}


	/**
	 * Get registered settings types for sanitization.
	 *
	 * @since   1.0.0
	 *
	 * @param bool $filtered_tab bool|string     A tab to filter setting types by.
	 * @param bool $filtered_section bool|string A section to filter setting types by.
	 * @return array Key is the setting ID,  value is the type of setting
	 */
	public function get_defined_settings_types( $filtered_tab = false, $filtered_section = false ) {
		$settings      = $this->get_defined_settings();
		$setting_types = [];
		foreach ( $settings as $tab_id => $tab ) {

			if ( false !== $filtered_tab && $filtered_tab !== $tab_id ) {
				continue;
			}

			foreach ( $tab as $section_id => $section_or_setting ) {

				// See if we have a setting registered at the tab level.
				if ( false !== $filtered_section && is_array( $section_or_setting ) && array_key_exists( 'type', $section_or_setting ) ) {
					$setting_types[ $section_or_setting['id'] ] = $section_or_setting['type'];
					continue;
				}

				if ( false !== $filtered_section && $filtered_section !== $section_id ) {
					continue;
				}

				foreach ( $section_or_setting as $section => $section_settings ) {

					if ( ! empty( $section_settings['type'] ) ) {
						$setting_types[ $section_settings['id'] ] = $section_settings['type'];
					}

				}

			}

		}

		return $setting_types;
	}

	/**
	 * Number callback
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param array $args The settings arguments.
	 *
	 * @return  void
	 */
	public function number_callback( $args ) {
		$intercessor_option = $this->get_option( $args['id'] );

		if ( $intercessor_option ) {
			$value = $intercessor_option;
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		if ( isset( $args['faux'] ) && true === $args['faux'] ) {
			$args['readonly'] = true;
			$value            = isset( $args['std'] ) ? $args['std'] : '';
			$name             = '';
		} else {
			$name = 'name="intercessor_settings[' . esc_attr( $args['id'] ) . ']"';
		}

		$class = $this->sanitize_html_class( $args['field_class'] );
		$max   = isset( $args['max'] ) ? $args['max'] : 999999;
		$min   = isset( $args['min'] ) ? $args['min'] : 0;
		$step  = isset( $args['step'] ) ? $args['step'] : 1;
		$size  = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html  = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $class . ' ' . sanitize_html_class( $size ) . '-text" id="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<label for="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

		echo apply_filters( 'intercessor_after_setting_output', $html, $args );
	}
	/**
	 * Sanitizes a string key for IPR Settings
	 *
	 * Keys are used as internal identifiers. Alphanumeric characters, dashes, underscores, stops, colons and slashes are allowed.
	 *
	 * @param  string $key String key.
	 *
	 * @since  1.0.0
	 * @return string Sanitized key
	 */
	public function sanitize_key( $key ) {
		$raw_key = $key;
		$key     = preg_replace( '/[^a-zA-Z0-9_\-\.\:\/]/', '', $key );

		/**
		 * Filter a sanitized key string.
		 *
		 * @since 1.0.0
		 * @param string $key     Sanitized key.
		 * @param string $raw_key The key prior to sanitization.
		 */
		return apply_filters( 'intercessor_sanitize_key', $key, $raw_key );
	}

	/**
	 * Sanitize text fields
	 *
	 * @access public
	 * @since   1.0.0
	 *
	 * @param string $input The value entered in the field.
	 *
	 * @return string|array $input The sanitized value
	 */
	public function text_field_sanitization( string $input = '' ) {
		// Get allowed tags.
		$allowed_tags = \intercessor_allowed_tags();

		// Return filtered content.
		return trim( wp_kses( $input, $allowed_tags ) );
	}

	/**
	 * Header callback
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param array $args The settings arguments.
	 * @return  void
	 */
	public function header_callback( $args ) {
		echo apply_filters( 'intercessor_after_setting_output', '', $args );
	}

	/**
	 * HTML callback
	 *
	 * @since   1.0.0
	 * @access public
	 *
	 * @param array $args The settings arguments.
	 *
	 * @return  void
	 */
	public function html_callback( $args ) {
		global $intercessor_options;

		if ( isset( $intercessor_options[ $args['id'] ] ) ) {
			$value = $intercessor_options[ $args['id'] ];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$html  = '<textarea class="large-text intercessor-html" cols="50" rows="5" id="' . 'intercessor_settings[' . $args['id'] . ']" name="' . 'intercessor_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>&nbsp;';
		$html .= '<span class="description"><label for="' . 'intercessor_settings[' . $args['id'] . ']">' . $args['desc'] . '</label></span>';

		echo apply_filters( 'intercessor_after_setting_output', $html, $args );
	}

	/**
	 * Color callback
	 *
	 * @access  public
	 * @since   1.0.0
	 *
	 * @param array $args The settings argumentss.
	 *
	 * @return void
	 */
	public function color_callback( $args ) {
		$intercessor_option = $this->get_option( $args['id'] );

		if ( $intercessor_option ) {
			$value = $intercessor_option;
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$default = isset( $args['std'] ) ? $args['std'] : '';
		$class   = $this->sanitize_html_class( $args['field_class'] );
		$html    = '<input type="text" class="' . $class . ' intercessor-color-picker" id="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']" name="intercessor_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
		$html   .= '<label for="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

		echo apply_filters( 'intercessor_after_setting_output', $html, $args );
	}

	/**
	 * Descriptive text callback
	 *
	 * @access public
	 * @since   1.0.0
	 *
	 * @param array $args The settings arguments.
	 *
	 * @return  void
	 */
	public function descriptive_text_callback( $args ) {
		$html = wp_kses_post( $args['desc'] );

		echo apply_filters( 'intercessor_after_setting_output', $html, $args );
	}

	/**
	 * Editor callback
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param array $args The settings arguments.
	 *
	 * @return  void
	 */
	public function rich_editor_callback( $args ) {
		$intercessor_option = $this->get_option( $args['id'] );

		if ( $intercessor_option ) {
			$value = $intercessor_option;

			if ( empty( $args['allow_blank'] ) && empty( $intercessor_option ) ) {
				$value = isset( $args['std'] ) ? $args['std'] : '';
			}
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$rows = isset( $args['size'] ) ? $args['size'] : 20;

		$class = $this->sanitize_html_class( $args['field_class'] );

		ob_start();
		wp_editor(
			stripslashes( $value ),
			'intercessor_settings_' . esc_attr( $args['id'] ),
			array(
				'textarea_name' => 'intercessor_settings[' . esc_attr( $args['id'] ) . ']',
				'textarea_rows' => absint( $rows ),
				'editor_class'  => $class,
			)
		);
		$html = ob_get_clean();

		$html .= '<br/><label for="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

		echo apply_filters( 'intercessor_after_setting_output', $html, $args );
	}

	/**
	 * Checkbox callback
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param array $args The settings arguments.
	 * @return void
	 */
	public function checkbox_callback( $args ) {
		$intercessor_option = $this->get_option( $args['id'] );

		if ( isset( $args['faux'] ) && true === $args['faux'] ) {
			$name = '';
		} else {
			$name = 'name="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']"';
		}

		$class = $this->sanitize_html_class( $args['field_class'] );

		$checked = ! empty( $intercessor_option ) ? checked( 1, $intercessor_option, false ) : '';
		$html    = '<input type="hidden"' . $name . ' value="-1" />';
		$html   .= '<div class="intercessor-check-wrapper">';
		$html   .= '<input type="checkbox" id="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']"' . $name . ' value="1" ' . $checked . ' class="' . $class . '"/>';
		$html   .= '<label for="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';
		$html   .= '</div>';
		echo apply_filters( 'intercessor_after_setting_output', $html, $args );
	}

	/**
	 * Checkbox with description Callback
	 *
	 * Renders check-boxes with a description.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments passed by the setting.
	 *
	 * @return void
	 */
	public function descriptive_checkbox_callback( $args ) {
		$intercessor_option = $this->get_option( $args['id'] );

		if ( isset( $args['faux'] ) && true === $args['faux'] ) {
			$name = '';
		} else {
			$name = 'name="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']"';
		}

		$class   = $this->sanitize_html_class( $args['field_class'] );
		$checked = ! empty( $intercessor_option ) ? checked( 1, $intercessor_option, false ) : '';
		$html    = '<input type="hidden"' . $name . ' value="-1" />';
		$html   .= '<div class="intercessor-check-wrapper">';
		$html   .= '<input type="checkbox" id="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']"' . $name . ' value="1" ' . $checked . ' class="' . $class . '"/>';
		$html   .= '<label for="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['check'] ) . '</label>';
		$html   .= '</div>';
		$html   .= '<p class="description">' . wp_kses_post( $args['desc'] ) . '</p>';

		echo apply_filters( 'intercessor_after_setting_output', $html, $args );
	}

	/**
	 * Multicheck callback
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param array $args The settings arguments.
	 *
	 * @return  void
	 */
	public function multicheck_callback( $args ) {
		$intercessor_option = $this->get_option( $args['id'] );

		$class = $this->sanitize_html_class( $args['field_class'] );
		$html  = '';

		if ( ! empty( $args['options'] ) ) {
			$html .= '<input type="hidden" name="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']" value="-1" />';

			foreach ( $args['options'] as $key => $option ):
				if ( isset( $intercessor_option[ $key ] ) ) {
					$enabled = $option;
				} else {
					$enabled = null;
				}
				$html .= '<div class="intercessor-check-wrapper">';
				$html .= '<input name="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . '][' . $this->sanitize_key( $key ) . ']" id="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . '][' . $this->sanitize_key( $key ) . ']" class="' . $class . '" type="checkbox" value="' . esc_attr( $option ) . '" ' . checked( $option, $enabled, false ) . '/>&nbsp;';
				$html .= '<label for="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . '][' . $this->sanitize_key( $key ) . ']">' . wp_kses_post( $option ) . '</label>';
				$html .= '</div>';
			endforeach;
			$html .= '<p class="description">' . $args['desc'] . '</p>';
		}

		echo apply_filters( 'intercessor_after_setting_output', $html, $args );
	}

	/**
	 * Password callback
	 *
	 * @access public
	 * @since   1.0.0
	 *
	 * @param array $args The settings arguments.
	 *
	 * @return  void
	 */
	public function password_callback( $args ) {
		$intercessor_options = $this->get_option( $args['id'] );

		if ( $intercessor_options ) {
			$value = $intercessor_options;
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$class = $this->sanitize_html_class( $args['field_class'] );
		$size  = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html  = '<input type="password" class="' . $class . ' ' . sanitize_html_class( $size ) . '-text" id="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']" name="intercessor_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '"/>';
		$html .= '<label for="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

		echo apply_filters( 'intercessor_after_setting_output', $html, $args );
	}

	/**
	 * Email Callback
	 *
	 * Renders email fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The settings arguments.
	 *
	 * @return void
	 */
	public function email_callback( $args ) {
		$intercessor_option = $this->get_option( $args['id'] );

		if ( $intercessor_option ) {
			$value = $intercessor_option;
		} elseif ( ! empty( $args['allow_blank'] ) && empty( $intercessor_option ) ) {
			$value = '';
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		if ( isset( $args['faux'] ) && true === $args['faux'] ) {
			$args['readonly'] = true;
			$value            = isset( $args['std'] ) ? $args['std'] : '';
			$name             = '';
		} else {
			$name = 'name="intercessor_settings[' . esc_attr( $args['id'] ) . ']"';
		}

		$class = $this->sanitize_html_class( $args['field_class'] );

		$placeholder = isset( $args['placeholder'] )
			? $args['placeholder']
			: '';

		$disabled = ! empty( $args['disabled'] ) ? ' disabled="disabled"' : '';
		$readonly = true === $args['readonly'] ? ' readonly="readonly"' : '';
		$size     = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html     = '<input type="email" class="' . $class . ' ' . sanitize_html_class( $size ) . '-text" id="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '" ' . $readonly . $disabled . ' placeholder="' . esc_attr( $placeholder ) . '" />';
		$html    .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';

		echo apply_filters( 'intercessor_after_setting_output', $html, $args );
	}

	/**
	 * Radio callback
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param array $args The settings arguments.
	 *
	 * @return  void
	 */
	public function radio_callback( $args ) {
		$intercessor_options = $this->get_option( $args['id'] );

		$html = '';

		$class = $this->sanitize_html_class( $args['field_class'] );

		foreach ( $args['options'] as $key => $option ) :
			$checked = false;

			if ( $intercessor_options && $intercessor_options == $key ) {
				$checked = true;
			} elseif ( isset( $args['std'] ) && $args['std'] == $key && ! $intercessor_options ) {
				$checked = true;
			}

			$html .= '<div class="intercessor-check-wrapper">';
			$html .= '<input name="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']" id="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . '][' . $this->sanitize_key( $key ) . ']" class="' . $class . '" type="radio" value="' . $this->sanitize_key( $key ) . '" ' . checked( true, $checked, false ) . '/>&nbsp;';
			$html .= '<label for="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . '][' . $this->sanitize_key( $key ) . ']">' . esc_html( $option ) . '</label>';
			$html .= '</div>';
		endforeach;

		$html .= '<p class="description">' . apply_filters( 'intercessor_after_setting_output', wp_kses_post( $args['desc'] ), $args ) . '</p>';

		echo $html;
	}

	/**
	 * Select callback
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param array $args The settings arguments.
	 *
	 * @return  void
	 */
	public function select_callback( $args ) {
		$intercessor_option = $this->get_option( $args['id'] );

		if ( $intercessor_option ) {
			$value = $intercessor_option;
		} else {

			// Properly set default fallback if the Select Field allows Multiple values.
			if ( empty( $args['multiple'] ) ) {
				$value = isset( $args['std'] ) ? $args['std'] : '';
			} else {
				$value = ! empty( $args['std'] ) ? $args['std'] : [];
			}

		}

		if ( isset( $args['placeholder'] ) ) {
			$placeholder = $args['placeholder'];
		} else {
			$placeholder = '';
		}

		$class = $this->sanitize_html_class( $args['field_class'] );

		if ( isset( $args['chosen'] ) ) {
			$class .= ' intercessor-select-chosen';
		}

		// If the Select Field allows Multiple values, save as an array.
		$name_attr = 'intercessor_settings[' . esc_attr( $args['id'] ) . ']';
		$name_attr = ( $args['multiple'] ) ? $name_attr . '[]' : $name_attr;

		$html = '<select id="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']" name="' . $name_attr . '" class="' . $class . '" data-placeholder="' . esc_html( $placeholder ) . '" ' . ( ( $args['multiple'] ) ? 'multiple="true"' : '' ) . '>';

		foreach ( $args['options'] as $option => $name ) {

			if ( ! $args['multiple'] ) {
				$selected = selected( $option, $value, false );
				$html    .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
			} else {
				// Do an in_[] check to output selected attribute for Multiple.
				$html .= '<option value="' . esc_attr( $option ) . '" ' . ( ( in_array( $option, $value, true ) ) ? 'selected="true"' : '' ) . '>' . esc_html( $name ) . '</option>';
			}

		}

		$html .= '</select>';
		$html .= '<label for="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

		echo apply_filters( 'intercessor_after_setting_output', $html, $args );
	}

	/**
	 * Text callback
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $args The settings arguments.
	 *
	 * @return  void
	 */
	public function text_callback( $args ) {
		$intercessor_option = $this->get_option( $args['id'] );

		if ( $intercessor_option ) {
			$value = $intercessor_option;
		} elseif ( ! empty( $args['allow_blank'] ) && empty( $intercessor_option ) ) {
			$value = '';
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		if ( isset( $args['faux'] ) && true === $args['faux'] ) {
			$args['readonly'] = true;
			$value            = isset( $args['std'] ) ? $args['std'] : '';
			$name             = '';
		} else {
			$name = 'name="intercessor_settings[' . esc_attr( $args['id'] ) . ']"';
		}

		$class = $this->sanitize_html_class( $args['field_class'] );

		$placeholder = ! empty( $args['placeholder'] )
			? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"'
			: '';

		$disabled = ! empty( $args['disabled'] ) ? ' disabled="disabled"' : '';
		$readonly = $args['readonly'] === true ? ' readonly="readonly"' : '';
		$size     = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html     = '<input type="text" class="' . $class . ' ' . sanitize_html_class( $size ) . '-text" id="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"' . $readonly . $disabled . $placeholder . ' />';
		$html    .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';

		echo apply_filters( 'intercessor_after_setting_output', $html, $args );
	}

	/**
	 * Textarea callback
	 *
	 * @since   1.0.0
	 *
	 * @param array $args The settings arguments.
	 *
	 * @return  void
	 */
	public function textarea_callback( $args ) {
		$intercessor_option = $this->get_option( $args['id'] );

		if ( $intercessor_option ) {
			$value = $intercessor_option;
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$class = $this->sanitize_html_class( $args['field_class'] );
		$html  = '<textarea class="' . $class . ' large-text" cols="50" rows="5" id="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']" name="intercessor_settings[' . esc_attr( $args['id'] ) . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		$html .= '<label for="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

		echo apply_filters( 'intercessor_after_setting_output', $html, $args );
	}

	/**
	 * Upload callback
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $args The settings arguments.
	 *
	 * @return  void
	 */
	public function upload_callback( $args ) {
		$intercessor_option = $this->get_option( $args['id'] );

		if ( $intercessor_option ) {
			$value = $intercessor_option;
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$class = $this->sanitize_html_class( $args['field_class'] );
		$size  = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html  = '<input type="text" class="' . sanitize_html_class( $size ) . '-text" id="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']" class="' . $class . '" name="intercessor_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<span>&nbsp;<input type="button" class="intercessor_settings_upload_button button-secondary" value="' . esc_html__( 'Upload File', 'intercessor' ) . '"/></span>';
		$html .= '<label for="intercessor_settings[' . $this->sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

		echo apply_filters( 'intercessor_after_setting_output', $html, $args );
	}

	/**
	 * Hook callback
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $args The settings arguments.
	 *
	 * @return  void
	 */
	public function hook_callback( $args ) {
		do_action( 'intercessor_' . $args['id'] );
	}

	/**
	 * Missing callback
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param array $args The settings arguments.
	 *
	 * @return  void
	 */
	public function missing_callback( $args ) {
		/* Translators: %s: id. */
		printf( __( 'The callback function used for the <strong>%s</strong> setting is missing.', 'intercessor' ), esc_attr( $args['id'] ) );
	}

	/**
	 * Add tooltips
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param string $html The current field HTML.
	 * @param array  $args Arguments passed to the field.
	 *
	 * @return  string $html The updated field HTML
	 */
	public function add_tooltip( $html = '', $args = [] ) {
		// Tooltip has title & description.
		if ( ! empty( $args['tooltip_title'] ) && ! empty( $args['tooltip_desc'] ) ) {
			$tooltip   = '<span alt="f223" class="intercessor-help-tip dashicons dashicons-editor-help" title="<strong>' . esc_html( $args['tooltip_title'] ) . '</strong>: ' . esc_html( $args['tooltip_desc'] ) . '"></span>';
			$has_p_tag = strstr( $html, '</p>' );
			$has_label = strstr( $html, '</label>' );

			// Insert tooltip at end of paragraph.
			if ( false !== $has_p_tag ) {
				$html = str_replace( '</p>', $tooltip . '</p>', $html );

				// Insert tooltip at end of label.
			} elseif ( false !== $has_label ) {
				$html = str_replace( '</label>', $tooltip . '</label>', $html );

				// Append tooltip to end of HTML.
			} else {
				$html .= $tooltip;
			}
		}

		return $html;
	}

	/**
	 * Sanitize HTML Class Names
	 *
	 * @param string|array $class HTML Class Name(s).
	 *
	 * @since 1.0.0
	 * @return string $class
	 */
	public function sanitize_html_class( $class = '' ) {

		if ( is_string( $class ) ) {
			$class = sanitize_html_class( $class );
		} elseif ( is_array( $class ) ) {
			$class = array_values( array_map( 'sanitize_html_class', $class ) );
			$class = implode( ' ', array_unique( $class ) );
		}

		return $class;

	}

	/**
	 * Get an option
	 *
	 * Looks to see if the specified setting exists, returns default if not
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $key     Option Key.
	 * @param bool   $default Default value.
	 *
	 * @return mixed
	 * @global array $intercessor_options Array of all the options.
	 */
	public function get_option( $key = '', $default = false ) {
		global $intercessor_options;

		$value = ! empty( $intercessor_options[ $key ] ) ? $intercessor_options[ $key ] : $default;
		$value = apply_filters( 'intercessor_get_option', $value, $key, $default );
		return apply_filters( 'intercessor_get_option_' . $key, $value, $key, $default );
	}

	/**
	 * Update an option
	 *
	 * Updates an ipr setting value in both the db and the global variable.
	 * Warning: Passing in an empty, false or null string value will remove
	 *          the key from the intercessor_options array.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string          $key   The Key to update.
	 * @param string|bool|int $value The value to set the key to.
	 *
	 * @global array $intercessor_options Array of all the options.
	 * @return boolean True if updated, false if not.
	 */
	public function update_option( $key = '', $value = false ) {

		// Bail, if no key.
		if ( empty( $key ) ) {
			return false;
		}

		if ( empty( $value ) ) {
			$remove_option = $this->delete_option( $key );
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
		 * @since 1.0.0
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

	/**
	 * Remove an option
	 *
	 * Removes an ipr setting value in both the db and the global variable.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $key The Key to delete.
	 *
	 * @global $intercessor_options Array of all the options
	 * @return boolean True if removed, false if not.
	 */
	public function delete_option( $key = '' ) {
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

	/**
	 * Adds a tab to the contextual help menu in the current screen.
	 *
	 * @since 1.0.0
	 */
	public function sidebar() {
		$screen = \get_current_screen();

		$screen->set_help_sidebar(
			'<p><strong>' . sprintf( __( 'For more information:', 'intercessor' ) . '</strong></p>' .
			                         '<p>' . sprintf( __( 'Visit the <a href="%s">documentation</a> on the Intercessor website.', 'intercessor' ), esc_url( 'http://docs.prayerhousewp.com/' ) ) ) . '</p>' .
			'<p>' . sprintf(
				__( '<a href="%s">Post an issue</a> on <a href="%s">GitHub</a>. View <a href="%s">extensions</a>.', 'intercessor' ),
				esc_url( 'https://github.com/victoraigbeghian/intercessor/issues' ),
				esc_url( 'https://github.com/victoraigbeghian/intercessor' ),
				esc_url( 'https://victoraigbeghian.com/intercessor/?utm_source=plugin-settings-page&utm_medium=contextual-help-sidebar&utm_term=extensions&utm_campaign=ContextualHelp' )
			) . '</p>'
		);

		$screen->add_help_tab(
			[
				'id'	  => 'intercessor-settings-general',
				'title'	  => esc_html__( 'General', 'intercessor' ),
				'content' => '<p>' . esc_html__( 'This screen provides the most basic settings for configuring the Intercessor options.', 'intercessor' ) . '</p>',
			]
		);

		$screen->add_help_tab(
			[
				'id'	  => 'intercessor-settings-forms',
				'title'	  => esc_html__( 'Frontend', 'intercessor' ),
				'content' =>
					'<p>' . esc_html__( 'This screen provides configuration for the frontend of the site: prayer form and prayer request listing page.', 'intercessor' ) . '</p>' .
					'<p>' . __( '<strong>Prayer Form</strong> - Configure the options for the prayer request form page.', 'intercessor' ) . '</p>' .
					'<p>' . __( '<strong>Prayer Listing & Tweet</strong> - Specify the options for the prayer listing or display page. Configure the options to enable the possibility to tweet public requests.', 'intercessor' ) . '</p>',
			]
		);

		$screen->add_help_tab(
			[
				'id'	  => 'intercessor-settings-emails',
				'title'	  => esc_html__( 'Emails', 'intercessor' ),
				'content' =>
					'<p>' . esc_html__( "This screen allows you to customize how emails act. You can choose a pre-defined template, set the sender's name, email address, and subject.", 'intercessor' ) . '</p>' .
					'<p>' . esc_html__( 'A set of email tags has also been provided to allow the creation of personalized emails. A tag consists of a keyword surrounded by curly braces: <code>{tag}</code>. A description of each of these tags appears below the editor.', 'intercessor' ) . '</p>',
			]
		);

		$screen->add_help_tab(
			[
				'id'	  => 'intercessor-settings-styles',
				'title'	  => esc_html__( 'Styles', 'intercessor' ),
				'content' => '<p>' . esc_html__( "This screen allows customization of the plugin styles. For complete control, you can completely disable all styles generated.", 'intercessor' ) . '</p>',
			]
		);

		/**
		 * Fires to display the contextual help sidebar.
		 *
		 * @param mixed $screen Current screen.
		 * @since 1.0.0
		 */
		do_action( 'intercessor_settings_contextual_help', $screen );
	}
}
