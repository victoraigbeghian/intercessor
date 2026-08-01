<?php
/**
 * Settings page renderer.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Admin\Settings;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Intercessor\Http\Request;

/**
 * Registers settings with the WordPress Settings API and renders field HTML.
 *
 * Reads the schema from Registry (tab → section → {title, fields}) to call
 * register_setting(), add_settings_section(), and add_settings_field() for
 * each declared field. Also provides tab navigation and field HTML rendering.
 *
 * @since   1.0.0
 * @package Intercessor
 */
class Renderer {

	/**
	 * Registry instance providing the settings schema.
	 *
	 * @since 1.0.0
	 * @var   Registry
	 */
	private Registry $registry;

	/**
	 * Repository instance for reading saved values.
	 *
	 * @since 1.0.0
	 * @var   Repository
	 */
	private Repository $repository;

	/**
	 * Sanitizer instance used as the register_setting() sanitize callback.
	 *
	 * @since 1.0.0
	 * @var   Sanitizer
	 */
	private Sanitizer $sanitizer;

	/**
	 * Construct the renderer with its collaborators.
	 *
	 * @since 1.0.0
	 * @param Registry   $registry   Schema registry.
	 * @param Repository $repository Settings persistence.
	 * @param Sanitizer  $sanitizer  Input sanitizer.
	 */
	public function __construct(
		Registry $registry,
		Repository $repository,
		Sanitizer $sanitizer
	) {
		$this->registry   = $registry;
		$this->repository = $repository;
		$this->sanitizer  = $sanitizer;
	}

	/**
	 * Attach the admin_init hook that registers all settings.
	 *
	 * Called from Display_Page::register_settings() early in the request so
	 * the Settings API is wired before any admin page renders.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_init', array( $this, 'register' ) );
	}

	/**
	 * Register the option, sections, and fields with the WordPress Settings API.
	 *
	 * Iterates tab → section → fields from the Registry and calls the
	 * corresponding Settings API functions for each entry. Section titles come
	 * from Registry::get_section_title() so human-readable labels are used.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		register_setting(
			'intercessor_settings',
			'intercessor_settings',
			array( $this, 'sanitize' )
		);

		foreach ( $this->registry->all() as $tab => $sections ) {
			foreach ( $sections as $sectionId => $sectionDef ) {
				$page         = $this->get_page_id( $tab );
				$sectionTitle = $this->registry->get_section_title( $tab, $sectionId );
				$fields       = $sectionDef['fields'] ?? array();

				add_settings_section(
					$sectionId,
					$sectionTitle,
					'__return_false',
					$page
				);

				foreach ( $fields as $field ) {
					if ( empty( $field['id'] ) ) {
						continue;
					}

					add_settings_field(
						$field['id'],
						$field['label'] ?? $field['name'] ?? '',
						array( $this, 'render_field' ),
						$page,
						$sectionId,
						$field
					);
				}
			}
		}
	}

	/**
	 * Sanitize settings input before saving.
	 *
	 * Verifies the nonce WordPress adds to the options.php form, determines
	 * the active tab from $_POST, and merges the sanitized tab values with
	 * the existing stored settings so non-active tabs are never wiped.
	 *
	 * @since  1.0.0
	 * @param  array $input Raw $_POST values passed by the Settings API.
	 * @return array<string, mixed> Sanitized settings ready for storage.
	 */
	public function sanitize( $input ): array {
		$req = Request::capture();

		$tab   = $req->get_key( 'tab' ) ?: 'general';
		$clean = $this->sanitizer->sanitize( (array) $input, $tab );

		// Preserve all existing keys from other tabs so a save on one tab
		// never erases settings from a different tab.
		$merged = array_merge( $this->repository->all(), $clean );

		// Add the "Settings saved." notice that settings_errors() will render.
		// options.php already verified the nonce before invoking this callback,
		// so a second nonce check here is redundant and was silently preventing
		// the notice from ever appearing.
		add_settings_error(
			'intercessor_settings',
			'intercessor_settings_saved',
			__( 'Settings saved.', 'intercessor' ),
			'updated'
		);

		return $merged;
	}

	/**
	 * Render the tab navigation bar.
	 *
	 * @since  1.0.0
	 * @param  string $currentTab The currently active tab slug.
	 * @return void
	 */
	public function render_tabs( string $currentTab ): void {
		$tabs = $this->registry->get_tabs();

		echo '<h2 class="nav-tab-wrapper">';

		foreach ( $tabs as $tab ) {
			$active = ( $tab === $currentTab ) ? ' nav-tab-active' : '';
			printf(
				'<a href="?page=intercessor-settings&tab=%s" class="nav-tab%s">%s</a>',
				esc_attr( $tab ),
				esc_attr( $active ),
				esc_html( ucfirst( str_replace( '_', ' ', $tab ) ) )
			);
		}

		echo '</h2>';
	}

	/**
	 * Render all registered settings sections for a tab.
	 *
	 * Delegates to WordPress's do_settings_sections() using the page ID
	 * derived from the tab slug.
	 *
	 * @since  1.0.0
	 * @param  string $tab Tab slug to render.
	 * @return void
	 */
	public function render_tab_content( string $tab ): void {
		do_settings_sections( $this->get_page_id( $tab ) );
	}

	/**
	 * Generate the page ID string used when registering sections and fields.
	 *
	 * @since  1.0.0
	 * @param  string $tab Tab slug.
	 * @return string      Page ID, e.g. 'intercessor_general'.
	 */
	private function get_page_id( string $tab ): string {
		return 'intercessor_' . $tab;
	}

	/**
	 * Render a single settings field.
	 *
	 * Hooked to add_settings_field() with the full field definition array
	 * passed as the $args parameter. Reads the current saved value from the
	 * Repository before rendering HTML.
	 *
	 * Supported types: text, email, url, password, number, checkbox, textarea,
	 * select. Unknown types fire an 'intercessor_settings_field_{type}' action.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed> $args {
	 *     Field definition array from the schema.
	 *
	 *     @type string $id      Required. Field identifier / option key.
	 *     @type string $type    Field input type. Default 'text'.
	 *     @type string $label   Column label (used for checkbox inline label).
	 *     @type string $desc    Description shown below the input.
	 *     @type mixed  $default Default value when no saved value exists.
	 *     @type int    $min     Minimum value for number fields.
	 *     @type int    $max     Maximum value for number fields.
	 * }
	 * @return void
	 */
	public function render_field( array $args ): void {
		$id    = $args['id'] ?? '';
		$type  = $args['type'] ?? 'text';
		$value = $this->repository->get( $id, $args['default'] ?? '' );

		echo '<div class="intercessor-field">';

		switch ( $type ) {
			case 'text':
			case 'email':
			case 'url':
			case 'password':
				printf(
					'<input type="%1$s" id="%2$s" name="intercessor_settings[%2$s]" value="%3$s" class="regular-text" />',
					esc_attr( $type ),
					esc_attr( $id ),
					esc_attr( (string) $value )
				);
				break;

			case 'number':
				$min = isset( $args['min'] ) ? ' min="' . (int) $args['min'] . '"' : '';
				$max = isset( $args['max'] ) ? ' max="' . (int) $args['max'] . '"' : '';
				printf(
					'<input type="number" id="%1$s" name="intercessor_settings[%1$s]" value="%2$s" class="small-text"%3$s%4$s />',
					esc_attr( $id ),
					esc_attr( (string) $value ),
					esc_attr( $min ), // Already safe — cast to int above.
					esc_attr( $max )  // Already safe — cast to int above.
				);
				break;

			case 'checkbox':
				printf(
					'<label><input type="checkbox" id="%1$s" name="intercessor_settings[%1$s]" value="1" %2$s /> %3$s</label>',
					esc_attr( $id ),
					checked( '1', $value, false ),
					esc_html( $args['label'] ?? '' )
				);
				break;

			case 'textarea':
				printf(
					'<textarea id="%1$s" name="intercessor_settings[%1$s]" rows="5" class="large-text">%2$s</textarea>',
					esc_attr( $id ),
					esc_textarea( (string) $value )
				);
				break;

			case 'select':
				printf(
					'<select id="%1$s" name="intercessor_settings[%1$s]">',
					esc_attr( $id )
				);
				foreach ( $args['options'] ?? array() as $key => $label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( (string) $key ),
						selected( $value, $key, false ),
						esc_html( (string) $label )
					);
				}
				echo '</select>';
				break;

			default:
				/**
				 * Render a custom field type.
				 *
				 * @param array<string, mixed> $args  Full field definition.
				 * @param mixed                $value Current saved value.
				 */
				do_action( "intercessor_settings_field_{$type}", $args, $value );
				break;
		}

		if ( ! empty( $args['desc'] ) ) {
			echo '<p class="description">' . wp_kses_post( (string) $args['desc'] ) . '</p>';
		}

		echo '</div>';
	}
}
