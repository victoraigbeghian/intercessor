<?php
/**
 * Settings schema registry.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Admin\Settings;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Defines the schema for tabs, sections, and fields.
 *
 * Acts as the single source of truth for the settings structure. The schema
 * shape is: $schema[ tab ][ section ] = [ 'title' => '...', 'fields' => [...] ]
 *
 * This matches the structure previously used in Settings::buildTabs() so that
 * Renderer, Sanitizer, and SettingsExporter all consume a consistent format.
 *
 * @since   1.0.0
 * @package Intercessor
 */
class Registry {

	/**
	 * Full settings schema indexed by tab then section.
	 *
	 * @since 1.0.0
	 * @var   array<string, array<string, array{title: string, fields: array<int, array<string, mixed>>>>>
	 */
	private array $schema;

	/**
	 * Construct the registry with a pre-built schema array.
	 *
	 * @since 1.0.0
	 * @param array $schema Full settings schema (tab → section → {title, fields}).
	 */
	public function __construct( array $schema = array() ) {
		$this->schema = $schema;
	}

	/**
	 * Return the entire schema.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function all(): array {
		return $this->schema;
	}

	/**
	 * Return all tab slugs.
	 *
	 * @since  1.0.0
	 * @return string[]
	 */
	public function get_tabs(): array {
		return array_keys( $this->schema );
	}

	/**
	 * Return all sections for a given tab.
	 *
	 * Each value is an associative array with 'title' and 'fields' keys.
	 *
	 * @since  1.0.0
	 * @param  string $tab Tab slug.
	 * @return array<string, array{title: string, fields: array}>
	 */
	public function get_sections( string $tab ): array {
		return $this->schema[ $tab ] ?? array();
	}

	/**
	 * Return the fields array for a specific tab and section.
	 *
	 * Reads the 'fields' key from the section definition so callers receive
	 * the flat list of field arrays without needing to unwrap the wrapper.
	 *
	 * @since  1.0.0
	 * @param  string                          $tab     Tab slug.
	 * @param  string                          $section Section slug.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_fields( string $tab, string $section ): array {
		return $this->schema[ $tab ][ $section ]['fields'] ?? array();
	}

	/**
	 * Return the human-readable title for a given section.
	 *
	 * Falls back to a ucfirst-ed section slug when no title is declared.
	 *
	 * @since  1.0.0
	 * @param  string $tab     Tab slug.
	 * @param  string $section Section slug.
	 * @return string
	 */
	public function get_section_title( string $tab, string $section ): string {
		return $this->schema[ $tab ][ $section ]['title'] ?? ucfirst( $section );
	}

	/**
	 * Return field types indexed by field ID.
	 *
	 * Reads the 'fields' sub-array from each section wrapper. Optionally
	 * filtered to a specific tab and/or section.
	 *
	 * @since  1.0.0
	 * @param  string|null $tab     Limit to a specific tab slug, or null for all tabs.
	 * @param  string|null $section Limit to a specific section slug, or null for all sections.
	 * @return array<string, string> Map of field ID → field type string.
	 */
	public function get_field_types( ?string $tab = null, ?string $section = null ): array {
		$types = array();

		foreach ( $this->schema as $tabId => $sections ) {
			if ( null !== $tab && $tab !== $tabId ) {
				continue;
			}

			foreach ( $sections as $sectionId => $sectionDef ) {
				if ( null !== $section && $section !== $sectionId ) {
					continue;
				}

				$fields = $sectionDef['fields'] ?? array();

				foreach ( $fields as $field ) {
					if (
						isset( $field['id'], $field['type'] ) &&
						'' !== $field['id'] &&
						'' !== $field['type']
					) {
						$types[ $field['id'] ] = $field['type'];
					}
				}
			}
		}

		return $types;
	}
}
