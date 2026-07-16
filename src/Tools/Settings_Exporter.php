<?php
/**
 * Settings CSV exporter.
 *
 * @package Intercessor
 * @since   1.0.0
 */
declare(strict_types=1);

namespace Intercessor\Tools;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Exports all Intercessor plugin settings to a timestamped CSV file.
 *
 * Produces a three-column CSV — Setting Key, Value, Section — giving
 * administrators a full audit snapshot of the current plugin configuration.
 * Sensitive values such as email addresses are included deliberately so the
 * file can be used as a reference when migrating the plugin to a new site.
 *
 * Each known setting key is mapped to a human-readable section label that
 * mirrors the tab structure in the Settings admin page. Unknown keys that
 * exist in the database option but are absent from the known-key map are
 * appended under the 'Other' section to ensure nothing is silently omitted.
 *
 * Boolean values stored as '1', 1, '0', 0, or false are normalised to the
 * localised 'Yes' / 'No' strings for readability in spreadsheet applications.
 *
 * @since   1.0.0
 * @package Intercessor
 */
final class Settings_Exporter extends Abstract_Exporter {

	/**
	 * Return the timestamped CSV download filename.
	 *
	 * @since  1.0.0
	 * @return string Filename in the format 'intercessor-settings-YYYY-MM-DD.csv'.
	 */
	protected function get_filename(): string {
		return sprintf( 'intercessor-settings-%s.csv', gmdate( 'Y-m-d' ) );
	}

	/**
	 * Return the ordered column header labels for the CSV.
	 *
	 * @since  1.0.0
	 * @return string[] Three-element array: Setting Key, Value, Section.
	 */
	protected function get_headers(): array {
		return array(
			__( 'Setting Key', 'intercessor' ),
			__( 'Value',       'intercessor' ),
			__( 'Section',     'intercessor' ),
		);
	}

	/**
	 * Build CSV rows from all registered settings organised by section.
	 *
	 * Reads the live 'intercessor_settings' WordPress option directly to
	 * reflect the currently saved state rather than class-level defaults.
	 * Iterates the known-key map first (preserving section grouping), then
	 * appends any extra keys present in the database under 'Other'.
	 *
	 * @since  1.0.0
	 * @return array<int, array<int, scalar>> Indexed list of three-column row value arrays.
	 */
	protected function get_rows(): array {
		$optionName = 'intercessor_settings';
		$saved      = (array) get_option( $optionName, array() );

		/**
		 * Map of every known setting key to its human-readable section label.
		 *
		 * This mirrors the tab/section structure defined in Settings::buildTabs().
		 * Update this map whenever a new setting field is added.
		 *
		 * @var array<string, string>
		 */
		$sectionMap = array(
			// General tab — Approval Rules section.
			'auto_approve'                  => __( 'General / Approval Rules', 'intercessor' ),
			'require_login'                 => __( 'General / Approval Rules', 'intercessor' ),
			'allow_anonymous'               => __( 'General / Approval Rules', 'intercessor' ),
			'max_requests_per_day'          => __( 'General / Approval Rules', 'intercessor' ),
			// Moderation tab.
			'profanity_filter'              => __( 'Moderation', 'intercessor' ),
			'profanity_words'               => __( 'Moderation', 'intercessor' ),
			'moderation_role'               => __( 'Moderation', 'intercessor' ),
			// Notifications tab.
			'notify_admin_new_request'      => __( 'Notifications', 'intercessor' ),
			'notify_requester_received'     => __( 'Notifications', 'intercessor' ),
			'notify_requester_status_change'=> __( 'Notifications', 'intercessor' ),
			'admin_email'                   => __( 'Notifications', 'intercessor' ),
			'email_from_name'               => __( 'Notifications', 'intercessor' ),
			'email_from_address'            => __( 'Notifications', 'intercessor' ),
			// Display tab.
			'requests_per_page'             => __( 'Display', 'intercessor' ),
			'show_date'                     => __( 'Display', 'intercessor' ),
			'show_requester_name'           => __( 'Display', 'intercessor' ),
			'date_format'                   => __( 'Display', 'intercessor' ),
			// reCAPTCHA tab.
			'recaptcha_site_key'            => __( 'reCAPTCHA / API Keys', 'intercessor' ),
			'recaptcha_secret_key'          => __( 'reCAPTCHA / API Keys', 'intercessor' ),
			'recaptcha_version'             => __( 'reCAPTCHA / Configuration', 'intercessor' ),
			'recaptcha_v3_threshold'        => __( 'reCAPTCHA / Configuration', 'intercessor' ),
			'recaptcha_enable_form'         => __( 'reCAPTCHA / Enable on Pages', 'intercessor' ),
			'recaptcha_enable_history'      => __( 'reCAPTCHA / Enable on Pages', 'intercessor' ),
			// Export tab.
			'export_include_content'        => __( 'Export', 'intercessor' ),
			'export_status_filter'          => __( 'Export', 'intercessor' ),
			'export_prayed_mode'            => __( 'Export', 'intercessor' ),
			// Advanced tab.
			'delete_data_on_uninstall'      => __( 'Advanced', 'intercessor' ),
		);

		$rows = array();

		foreach ( $sectionMap as $key => $section ) {
			$value = $saved[ $key ] ?? '';

			// Normalise boolean-like values to localised Yes / No strings.
			// Only values that are genuinely boolean or the checkbox storage
			// values '1' / '0' are converted. Plain empty strings (e.g. an
			// unsaved text field like admin_email) are left as-is so they
			// do not appear as "No" in the CSV.
			if ( is_bool( $value ) ) {
				$value = $value ? __( 'Yes', 'intercessor' ) : __( 'No', 'intercessor' );
			} elseif ( $value === '1' || $value === 1 ) {
				$value = __( 'Yes', 'intercessor' );
			} elseif ( $value === '0' || $value === 0 ) {
				$value = __( 'No', 'intercessor' );
			}

			$rows[] = array( $key, (string) $value, $section );
		}

		// Append any keys that exist in the DB but are absent from the known map.
		foreach ( $saved as $key => $value ) {
			if ( ! array_key_exists( $key, $sectionMap ) ) {
				$rows[] = array( $key, (string) $value, __( 'Other', 'intercessor' ) );
			}
		}

		return $rows;
	}
}
