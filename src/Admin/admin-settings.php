<?php
/**
 * Intercessor Admin Setting Functions
 *
 * @package     Intercessor
 * @copyright   Copyright (c) 2020, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Settings Tabs
 *
 * @param  array $tabs The settings tabs.
 *
 * @since  1.0.0
 * @return array $tabs Settings tabs.
 */
function intercessor_setup_settings_tabs( $tabs ) {
	return [
        'general'  => esc_html__( 'General', 'intercessor' ),
        'frontend' => esc_html__( 'Frontend', 'intercessor' ),
        'emails'   => esc_html__( 'Emails', 'intercessor' ),
        'styles'   => esc_html__( 'Styles', 'intercessor' ),
	];
}
add_filter( 'intercessor_settings_tabs', 'intercessor_setup_settings_tabs' );

/**
 * Setup the settings sections
 *
 * @param array $sections The default settings sections.
 *
 * @since  1.0.0
 * @return array $sections Plugin settings sections
 */
function intercessor_setup_settings_sections( $sections ) {
	return [
		'general' => apply_filters(
			'intercessor_settings_sections_general',
			[
				'main'        => esc_html__( 'General Settings', 'intercessor' ),
				'prayer_user' => esc_html__( 'User Settings', 'intercessor' ),
				'site_terms'  => esc_html__( 'Terms of Agreement', 'intercessor' ),
				'security'    => esc_html__( 'Security Settings', 'intercessor' ),
				'misc'        => esc_html__( 'Advanced Settings', 'intercessor' ),
			]
		),
		'frontend' => apply_filters(
			'intercessor_settings_sections_display',
			[
				'main'          => esc_html__( 'Prayer Form', 'intercessor' ),
				'listing'      => esc_html__( 'Prayer Listing', 'intercessor' ),
				'prayer_tweet' => esc_html__( 'Prayer Tweet', 'intercessor' ),
			]
		),
		'emails'  => apply_filters(
			'intercessor_settings_sections_emails',
			[
				'main'                 => esc_html__( 'Email Settings', 'intercessor' ),
				'prayer_notifications' => esc_html__( 'Prayer Notifications', 'intercessor' ),
				'prayed_notice'        => esc_html__( 'Prayed For Notices', 'intercessor' ),
			]
		),
		'styles'  => apply_filters(
			'intercessor_settings_sections_styles',
			[
				'main' => esc_html__( 'Style Settings', 'intercessor' ),
			]
		),
	];
}
add_filter( 'intercessor_main_sections', 'intercessor_setup_settings_sections' );

/**
 * Add all settings sections and fields
 *
 * @param array $settings Settings fields.
 *

 * @return array
 *@since 1.0.0
 */
function intercessor_plugin_settings_fields( array $settings ): array {
	$intercessor_settings = [
		/** General Settings */
		'general' => apply_filters(
			'intercessor_settings_general',
			[
				'main' => [
					'page_settings' => [
						'id'   => 'page_settings',
						'name' => '<h3>' . esc_html__( 'Page Settings', 'intercessor' ) . '</h3>',
						'desc' => '',
						'type' => 'header',
						'tooltip_title' => esc_html__( 'Page Settings', 'intercessor' ),
						'tooltip_desc'  => esc_html__( 'Intercessor uses the pages below for handling the display of request form, prayer confirmation, prayer history, and prayer failures. If pages are deleted or removed in some way, they can be recreated manually from the Pages menu. When re-creating the pages, enter the shortcode shown in the page content area.', 'intercessor' ),
					],
					'form_page' => [
						'id'          => 'form_page',
						'name'        => esc_html__( 'Request Page', 'intercessor' ),
						'desc'        => esc_html__( 'This is the request form page where users will submit their prayer requests. The [intercessor_form] shortcode must be on this page.', 'intercessor' ),
						'type'        => 'select',
						'options'     => intercessor_get_pages(),
						'chosen'      => true,
						'placeholder' => esc_html__( 'Select a page', 'intercessor' ),
					],
					'prayers_page' => [
						'id'          => 'prayers_page',
						'name'        => esc_html__( 'Prayer Listing Page', 'intercessor' ),
						'desc'        => esc_html__( 'This is the page where all prayer requests are displayed. The [intercessor_prayers] shortcode must be on this page', 'intercessor' ),
						'type'        => 'select',
						'options'     => intercessor_get_pages(),
						'chosen'      => true,
						'placeholder' => esc_html__( 'Select a page', 'intercessor' ),
					],
					'history_page' => [
						'id'          => 'history_page',
						'name'        => esc_html__( 'Prayer  Request History Page', 'intercessor' ),
						'desc'        => esc_html__( 'This page shows a complete prayer history for the current user. The [intercessor_history] shortcode should be on this page.', 'intercessor' ),
						'type'        => 'select',
						'options'     => intercessor_get_pages(),
						'chosen'      => true,
						'placeholder' => esc_html__( 'Select a page', 'intercessor' ),
					],
					'hold_prayers' => [
						'id'    => 'hold_prayers',
						'name'  => esc_html__( 'Hold Prayers', 'intercessor' ),
						'check' => esc_html__( 'Enable this to hold prayer requests until an admin allow them.', 'intercessor' ),
						'desc'  => esc_html__( 'If enabled, all prayer requests will be in the pending state and must be activated by an admin before they are published on the front end.', 'intercessor' ),
						'type'  => 'descriptive_checkbox',
						'std'   => '1',
					],
					'notify_period' => [
						'id'            => 'notify_period',
						'name'          => esc_html__( 'Prayer Notify Interval', 'intercessor' ),
						'desc'          => esc_html__( 'This option affects how often you want to inform users when prayed for.', 'intercessor' ),
						'type'          => 'radio',
						'std'           => 'weekly',
						'options'       => [
							'daily'   => esc_html__( 'Once Daily', 'intercessor' ),
							'weekly'  => esc_html__( 'Once Weekly', 'intercessor' ),
							'monthly' => esc_html__( 'Once Monthly', 'intercessor' ),
						],
						'tooltip_title' => __( 'Prayed Notification Period', 'intercessor' ),
						'tooltip_desc'  => __( 'Specify how often you want to inform users when prayed for. This only works for Requesters who selected the option to be informed during prayer request submission.', 'intercessor' ),
					],
					'send_email_time' => [
						'id'            => 'send_email_time',
						'name'          => esc_html__( 'Time To Send Email', 'intercessor' ),
						'desc'          => esc_html__( 'Specify the time the email should be sent to requesters who wished to be notified.', 'intercessor' ),
						'type'          => 'radio',
						'std'           => '1300',
						'options'       => [
							'1000' => esc_html__( '10:00 AM', 'intercessor' ),
							'1100' => esc_html__( '11:00 AM', 'intercessor' ),
							'1200' => esc_html__( '12:00 PM', 'intercessor' ),
							'1300' => esc_html__( '1:00 PM', 'intercessor' ),
							'1400' => esc_html__( '2:00 PM', 'intercessor' ),
							'1500' => esc_html__( '3:00 PM', 'intercessor' ),
							'1600' => esc_html__( '4:00 PM', 'intercessor' ),
							'1700' => esc_html__( '5:00 PM', 'intercessor' ),
							'1800' => esc_html__( '6:00 PM', 'intercessor' ),
							'1900' => esc_html__( '7:00 PM', 'intercessor' ),
						],
						'tooltip_title' => __( 'Prayed Notification Period', 'intercessor' ),
						'tooltip_desc'  => __( 'Specify how often you want to inform users when prayed for. This only works for Requesters who selected the option to be informed during prayer request submission.', 'intercessor' ),
					],
				],
				'prayer_user' => [
					'user_settings' => [
						'id'            => 'user_settings',
						'name'          => '<h3>' . esc_html__( 'User Settings', 'intercessor' ) . '</h3>',
						'desc'          => '',
						'type'          => 'header',
						'tooltip_title' => esc_html__( 'User Settings', 'intercessor' ),
						'tooltip_desc'  => esc_html__( 'Configure the options for the users who submit prayer request on your website.', 'intercessor' ),
					],
					'enable_registration' => [
						'id'   => 'enable_registration',
						'name' => esc_html__( 'Enable Registration', 'intercessor' ),
						'desc' => esc_html__( 'If enabled new users can create an account while submitting a prayer request.', 'intercessor' ),
						'type' => 'checkbox',
						'std'  => 1,
					],
					'logged_in_only' => [
						'id'            => 'logged_in_only',
						'name'          => esc_html__( 'Disable Guest Prayer', 'intercessor' ),
						'desc'          => esc_html__( 'Require that users be logged-in in order to submit prayer.', 'intercessor' ),
						'type'          => 'checkbox',
						'tooltip_title' => esc_html__( 'Disabling Guest Submission', 'intercessor' ),
						'tooltip_desc'  => esc_html__( 'You can require that Requesters create and login to user accounts prior to submitting prayer request by enabling this option. When unchecked, users can submit prayer without being logged in by using their name and email address.', 'intercessor' ),
                        'std'  => 0,
					],
					'generate_username' => [
						'id'   => 'generate_username',
						'name' => esc_html__( 'Generate Username', 'intercessor' ),
						'desc' => esc_html__( 'Automatically generate username from user email.', 'intercessor' ),
						'type' => 'checkbox',
						'std'  => 1,
					],
					'generate_password' => [
						'id'   => 'generate_password',
						'name' => esc_html__( 'Generate Password', 'intercessor' ),
						'desc' => esc_html__( 'Automatically generate password for user.', 'intercessor' ),
						'type' => 'checkbox',
						'std'  => 0,
					],
				],
				'site_terms'     => [
					'terms_settings' => [
						'id'   => 'terms_settings',
						'name' => '<h3>' . esc_html__( 'Agreement Settings', 'intercessor' ) . '</h3>',
						'type' => 'header',
						'tooltip_title' => esc_html__( 'Terms and Privacy Policy Settings', 'intercessor' ),
						'tooltip_desc'  => esc_html__( 'Depending on legal and regulatory requirements, it may be necessary for your site to show checkboxes for Terms of Agreement and/or Privacy Policy.', 'intercessor' ),
					],
					'show_agree_to_terms' => [
						'id'   => 'show_agree_to_terms',
						'name' => esc_html__( 'Agree to Terms', 'intercessor' ),
						'desc' => esc_html__( 'Check this to show an agree to terms on the request form that users must agree to before submission.', 'intercessor' ),
						'type' => 'checkbox',
						'std'  => 1,
					],
					'agree_label' => [
						'id'   => 'agree_label',
						'name' => esc_html__( 'Agree to Terms Label', 'intercessor' ),
						'desc' => esc_html__( 'Label shown next to the agree to terms check box.', 'intercessor' ),
						'type' => 'text',
						'size' => 'regular',
						'std'  => esc_html__( 'Agree to Terms', 'intercessor' ),
					],
					'agree_text' => [
						'id'   => 'agree_text',
						'name' => esc_html__( 'Agreement Text', 'intercessor' ),
						'desc' => esc_html__( 'If Agree to Terms is checked, enter the agreement terms here.', 'intercessor' ),
						'type' => 'rich_editor',
						'std'  => intercessor_get_default_terms(),
					],
					'show_privacy_policy' => [
						'id'   => 'show_privacy_policy',
						'name' => esc_html__( 'Privacy Policy', 'intercessor' ),
						'desc' => esc_html__( 'Check this to show an agree to privacy policy on request form that users must agree to before purchasing.', 'intercessor' ),
						'type' => 'checkbox',
					],
					'agree_privacy_label' => [
						'id'   => 'agree_privacy_label',
						'name' => esc_html__( 'Agree to Privacy Policy Label', 'intercessor' ),
						'desc' => esc_html__( 'Label shown next to the agree to privacy policy check box.', 'intercessor' ),
						'type' => 'text',
						'size' => 'regular',
						'std'  => esc_html__( 'Agree to Privacy Policy', 'intercessor' ),
					],
					'show_on_submission' => [
						'id'   => 'show_on_submission',
						'name' => esc_html__( 'Show privacy policy', 'intercessor' ),
						'desc' => esc_html__( 'Display your privacy policy on request form.', 'intercessor' ),
						'type' => 'checkbox',
					],
					'agree_privacy_page' => [
						'id'   => 'agree_privacy_page',
						'name' => esc_html__( 'Privacy Agreement Page', 'intercessor' ),
						'desc' => esc_html__( 'If Agree to Privacy Policy is checked, select a page for the Privacy Agreement here.', 'intercessor' ),
						'type'        => 'select',
						'options'     => intercessor_get_pages(),
						'chosen'      => true,
						'placeholder' => esc_html__( 'Select a page', 'intercessor' ),
					],
				],
				'security' => [
					'captcha_settings' => [
						'id'            => 'captcha_settings',
						'name'          => '<h3>' . esc_html__( 'Security Settings', 'intercessor' ) . '</h3>',
						'desc'          => '',
						'type'          => 'header',
						'tooltip_title' => esc_html__( 'Security Settings', 'intercessor' ),
						'tooltip_desc'  => esc_html__( 'Configure the security settings for the prayer request form and history page.', 'intercessor' ),
					],
					'captcha_type' => [
						'id'      => 'captcha_type',
						'name'    => esc_html__( 'Captcha Type', 'intercessor' ),
						'desc'    => esc_html__( 'Choose which type of captcha to use on prayer history page.', 'intercessor' ),
						'type'    => 'select',
						'std'     => 'simple',
						'options' => [
							'simple'    => esc_html__( 'Simple captcha', 'intercessor' ),
							'recaptcha' => esc_html__( 'Google reCaptcha', 'intercessor' ),
						],
					],
					'use_captcha' => [
						'id'   => 'use_captcha',
						'name' => esc_html__( 'Enable Captcha', 'intercessor' ),
						'desc' => esc_html__( 'Enable the use of Google reCaptcha on the prayer request submission form.', 'intercessor' ),
						'type' => 'checkbox',
						'std'  => 0,
					],
					'captcha_help' => [
						'id'   => 'captcha_help',
						'desc' => sprintf(
							// Translators: to prevent spamming, navigate to the Google reCAPTCHA website and sign up for an API key.
							esc_html__( 'To prevent spam on the email access form navigate to %1$sthe Google reCAPTCHA website%2$s and sign up for an API key. The reCAPTCHA uses Google\'s user-friendly single click verification method.', 'intercessor' ),
							'<a href="https://www.google.com/recaptcha/" target="_blank">',
							'</a>' ),
						'type' => 'descriptive_text',
					],
					'recaptcha_key' => [
						'id'   => 'recaptcha_key',
						'name' => esc_html__( 'reCAPTCHA Site Key', 'intercessor' ),
						'desc' => esc_html__( 'Please paste your reCaptcha site Key here from your manage reCAPTCHA API Keys panel.', 'intercessor' ),
						'type' => 'text',
						'std'  => ''
					],
					'recaptcha_secret' => [
						'id'   => 'recaptcha_secret',
						'name' => esc_html__( 'reCAPTCHA Secret Key', 'intercessor' ),
						'desc' => esc_html__( 'Please paste the reCaptcha secret key here.', 'intercessor' ),
						'type' => 'text',
						'std'  => ''
					],
				],
				'misc' => [
					'advanced_settings' => [
						'id'   => 'advanced_settings',
						'name' => '<h3>' . esc_html__( 'Advanced Settings', 'intercessor' ) . '</h3>',
						'type' => 'header',
					],
					'footer_scripts'    => [
						'id'    => 'footer_scripts',
						'name'  => esc_html__( 'Scripts in Footer', 'intercessor' ),
						'check' => esc_html__( 'Enable this option to load scripts in the footer.', 'intercessor' ),
						'desc'  => esc_html__( 'By default, Intercessor scripts are loaded in the header. Enabling this option could improve page speed load.', 'intercessor' ),
						'type'  => 'descriptive_checkbox',
						'std'   => 0,
					],
					'enforce_ssl' => [
						'id'   => 'enforce_ssl',
						'name' => esc_html__( 'Enforce SSL', 'intercessor' ),
						'desc' => esc_html__( 'Check this to force users to be redirected to the secure request form page. You must have an SSL certificate installed to use this option.', 'intercessor' ),
						'type' => 'checkbox',
					],
					'banned_emails' => [
						'id'   => 'banned_emails',
						'name' => esc_html__( 'Banned Emails', 'intercessor' ),
						'desc' => esc_html__( 'Enter here the emails that should not be allowed to submit prayer request.', 'intercessor' ),
						'type' => 'rich_editor',
					],
					'uninstall_on_delete' => [
						'id'   => 'uninstall_on_delete',
						'name' => esc_html__( 'Remove Data on Uninstall?', 'intercessor' ),
						'desc' => esc_html__( 'Check this box if you would like Intercessor to completely remove all of its data when the plugin is deleted.', 'intercessor' ),
						'type' => 'checkbox',
					],
				],
			]
		),

		'frontend' => apply_filters(
			'intercessor_settings_display',
			array(
				'main'         => array(
					'request_form_settings' => array(
						'id'            => 'request_form_settings',
						'name'          => '<h3>' . esc_html__( 'Request Form', 'intercessor' ) . '</h3>',
						'desc'          => '',
						'type'          => 'header',
						'tooltip_title' => esc_html__( 'Request Form', 'intercessor' ),
						'tooltip_desc'  => esc_html__( 'Specify the configuration settings for the prayer request form.', 'intercessor' ),
					),
					'request_title'         => array(
						'id'   => 'request_title',
						'name' => esc_html__( 'Request Form Title', 'intercessor' ),
						'desc' => esc_html__( 'Enter the title for the prayer request form', 'intercessor' ),
						'type' => 'text',
						'std'  => esc_html__( 'Prayer Request Submission Form', 'intercessor' ),
					),
					'request_subtitle'      => array(
						'id'   => 'request_subtitle',
						'name' => esc_html__( 'Request Form Subtitle', 'intercessor' ),
						'desc' => esc_html__( 'Enter the subtitle for the prayer request form.', 'intercessor' ),
						'type' => 'textarea',
						'std'  => esc_html__( 'Use the prayer form below to send us your prayer request. Our growing and powerful community of intercessors check our prayer house daily to specifically pray for your request.', 'intercessor' ),
					),
					'bible_passage'         => array(
						'id'   => 'bible_passage',
						'name' => esc_html__( 'Bible Passage Subtitle', 'intercessor' ),
						'desc' => esc_html__( 'Enter a Bible passage subtitle for the prayer request form.', 'intercessor' ),
						'type' => 'rich_editor',
						'std'  => esc_html__( 'Again I say to you, if two of you agree on earth about anything they ask, it will be done for them by my Father in heaven. Matthew 18 verse 19.', 'intercessor' ),
					),
                    'registration_form' => array(
                        'id'      => 'registration_form',
                        'name'    => esc_html__( 'Show Register / Login Form', 'intercessor' ),
                        'desc'    => esc_html__( 'Display the registration and login forms on the checkout page for non-logged-in users.', 'intercessor' ),
                        'type'    => 'select',
                        'std'     => 'none',
                        'options' => [
                            'both'         => esc_html__( 'Registration and Login Forms', 'intercessor' ),
                            'registration' => esc_html__( 'Registration Form Only', 'intercessor' ),
                            'login'        => esc_html__( 'Login Form Only', 'intercessor' ),
                            'none'         => esc_html__( 'None', 'intercessor' ),
                        ],
                    ),
					'submit_prayer_label'   => array(
						'id'   => 'submit_prayer_label',
						'name' => esc_html__( 'Submit Prayer Label', 'intercessor' ),
						'desc' => esc_html__( 'Enter the label for the submit prayer request button.', 'intercessor' ),
						'type' => 'text',
						'std'  => esc_html__( 'Submit Prayer', 'intercessor' ),
					),
				),
				'listing' => array(
					'prayer_list_settings'  => array(
						'id'            => 'prayer_list_settings',
						'name'          => '<h3>' . esc_html__( 'Prayer Listing', 'intercessor' ) . '</h3>',
						'desc'          => '',
						'type'          => 'header',
						'tooltip_title' => esc_html__( 'Prayer Listing', 'intercessor' ),
						'tooltip_desc'  => esc_html__( 'Specify how prayer request are displayed on the listing page.', 'intercessor' ),
					),
					'enable_prayer_count'  => array(
						'id'    => 'enable_prayer_count',
						'name' 	=> esc_html__( 'Enable Prayer Count', 'intercessor' ),
						'check' => esc_html__( 'Enable prayer request counts. The count shows how many times a prayer request has been prayed for.', 'intercessor' ),
						'desc' 	=> esc_html__( 'If enabled, the number of times a request has been prayed for is displayed close to the pray for request button.', 'intercessor' ),
						'type'  => 'descriptive_checkbox',
						'std'  	=> 1,
					),
					'prayer_list_title'     => array(
						'id'   => 'prayer_list_title',
						'name' => esc_html__( 'Prayer List Title', 'intercessor' ),
						'desc' => esc_html__( 'Specify the title of the prayer requests listing page.', 'intercessor' ),
						'type' => 'text',
						'std'  => esc_html__( 'Prayer Requests', 'intercessor' ),
					),
					'prayer_list_message'   => array(
						'id'   => 'prayer_list_message',
						'name' => esc_html__( 'Prayer List Message', 'intercessor' ),
						'desc' => esc_html__( 'This message is displayed above the list of prayer requests.', 'intercessor' ),
						'type' => 'textarea',
						//	'size' => 'large',
						'std'  => esc_html__( 'Pray for any of these requests and click the "I Prayed" button to inform the user know that somebody prayed.', 'intercessor' ),
					),
					'prayer_number'         => array(
						'id'   => 'prayer_number',
						'name' => esc_html__( 'Prayers Number' , 'intercessor' ),
						'desc' => esc_html__( 'Specify the number of prayer requests to display on the prayer listing page', 'intercessor' ),
						'type' => 'number',
						'size' => 'small',
						'std'  => 20,
					),					
					'prayer_id_format'     => array(
						'id'   => 'prayer_id_format',
						'name' => esc_html__( 'Prayer Number', 'intercessor' ),
						'desc' => esc_html__( 'Format the numbering of the prayer request. If empty no numbering will be displayed', 'intercessor' ),
						'type' => 'text',
						'std'  => '',
					),
					'number_position' => array(
						'id'      => 'number_position',
						'name'    => esc_html__( 'Number Position', 'intercessor' ),
						'desc'    => esc_html__( 'Choose how the prayer request numbering can be displayed.', 'intercessor' ),
						'type'    => 'select',
						'std'     => 'left',
						'options' => array(
							'left'  => esc_html__( 'On the left', 'intercessor' ),
							'right' => esc_html__( 'On the right', 'intercessor' ),
						),
					),
					'prayer_display_period' => array(
						'id'      => 'prayer_display_period',
						'name'    => esc_html__( 'Prayer Display Period', 'intercessor' ),
						'desc'    => esc_html__( 'Choose how long a prayer request can be displayed.', 'intercessor' ),
						'type'    => 'select',
						'std'     => '90',
						'options' => array(
							'30'  => esc_html__( 'One Month', 'intercessor' ),
							'60'  => esc_html__( 'Two Months', 'intercessor' ),
							'90'  => esc_html__( 'Three Months', 'intercessor' ),
							'180' => esc_html__( 'Six Months', 'intercessor' ),
							'365' => esc_html__( 'One Year', 'intercessor' ),
							'730' => esc_html__( 'Two Years', 'intercessor' ),
						),
					),
					'prayed_for_label'      => array(
						'id'   => 'prayed_for_label',
						'name' => esc_html__( 'Pray For Label', 'intercessor' ),
						'desc' => esc_html__( 'Specify the button text for the "Pray Now" for this request button.', 'intercessor' ),
						'type' => 'text',
						'std'  => esc_html__( 'I Prayed', 'intercessor' ),
					),
					'top_pagination'        => array(
						'id'   	=> 'top_pagination',
						'name'  => esc_html__( 'Enable Top Pagination', 'intercessor' ),
						'check' => esc_html__( 'Enable pagination above the prayer list', 'intercessor' ),
						'desc' 	=> esc_html__( 'By default, pagination is only enabled at the bottom of the prayer list. If enabled, prayers pagination will also be displayed above the prayer list.', 'intercessor' ),
						'type' 	=> 'descriptive_checkbox',
						'std'  	=> 0,
					),
				),
				// Tweet prayer.		
				'prayer_tweet' => array(
					'prayer_tweet_settings' => array(
						'id'   => 'prayer_tweet_settings',
						'name' => '<h3>' . esc_html__( 'Prayer Tweet', 'intercessor' ) . '</h3>',
						'desc' => '',
						'type' => 'header',
						'tooltip_title' => esc_html__( 'Prayer Tweet', 'intercessor' ),
						'tooltip_desc'  => esc_html__( 'intercessor can also tweet the prayer request submitted on your website. Specify how these tweets are handled.','intercessor' ),
					),
					'enable_prayer_tweet' => array(
						'id'   => 'enable_prayer_tweet',
						'name' => esc_html__( 'Enable Prayer Tweet', 'intercessor' ),
						'desc' => esc_html__( 'Disable this option if you do not want to allow users submitting a request to be able to tweet the request.', 'intercessor' ),
						'type' => 'checkbox',
						'std'  => '1'
					),
					'prayer_tweet_via' => array(
						'id'   => 'prayer_tweet_via',
						'name' => esc_html__( 'Twitter Username', 'intercessor' ),
						'desc' => esc_html__( 'Enter the Twitter username to share Prayer requests. Please, do not include the @.', 'intercessor' ),
						'type' => 'text',
						'std'  => ''
					),
					'prayer_tweet_hashtag' => array(
						'id'   => 'prayer_tweet_hashtag',
						'name' => esc_html__( 'Twitter Hashtag', 'intercessor' ),
						'desc' => esc_html__( 'Enter the Twitter hashtag to share Prayer requests. Please, do not include the hashtag.', 'intercessor' ),
						'type' => 'text',
						'std'  => ''
					),
					'prayer_custom_id' => array(
						'id'   => 'prayer_custom_id',
						'name' => esc_html__( 'Parameter ID Name', 'intercessor' ),
						'desc' => esc_html__( 'Enter the name to use as ID Parameter shown in URLs.', 'intercessor' ),
						'type' => 'text',
						'std'  => ''
					),
					'prayer_tweet_name' => array(
						'id'   => 'prayer_tweet_name',
						'name' => esc_html__( 'Add Display Name', 'intercessor' ),
						'desc' => esc_html__( 'Attach display name to tweet.', 'intercessor' ),
						'type' => 'checkbox',
						'std'  => ''
					),
					'show_hashtag' => array(
						'id'   => 'show_hashtag',
						'name' => esc_html__( 'Show Hashtag', 'intercessor' ),
						'desc' => esc_html__( 'Show hashtag in prayer tweet button?', 'intercessor' ),
						'type' => 'checkbox',
						'std'  => ''
					),
					'prayer_tweet_button' => array(
						'id'   => 'prayer_tweet_button',
						'name' => esc_html__( 'Tweet Button', 'intercessor' ),
						'desc' => esc_html__( 'Select prayer tweet button type.', 'intercessor' ),
						'type' => 'select',
						'options' 		=> array(
							'only'  	=> esc_html__( 'Only Tweet, may include hashtag', 'intercessor' ),
							'both' 		=> esc_html__( 'With Counter', 'intercessor' ),
						),
						'std' 		=> ''
					),
				),
			)
		),

		/** Emails Settings */
		'emails' => apply_filters( 'intercessor_settings_emails',
			[
				'main'  => [
					'email_settings_header' => [
						'id'   => 'email_settings_header',
						'name' => '<h3>' . esc_html__( 'Email Settings', 'intercessor' ) . '</h3>',
						'type' => 'header',
					],
					'email_template'        => [
						'id'      => 'email_template',
						'name'    => esc_html__( 'Email Template', 'intercessor' ),
						'desc'    => esc_html__( 'Choose a template. Click "Save Changes" then "Preview Prayer Request" to see the new template.', 'intercessor' ),
						'type'    => 'select',
						'options' => intercessor_get_email_templates(),
					],
					'email_logo'            => [
						'id'   => 'email_logo',
						'name' => esc_html__( 'Logo', 'intercessor' ),
						'desc' => esc_html__( 'Upload or choose a logo to be displayed at the top of the prayer notification emails. Displayed on HTML emails only.', 'intercessor' ),
						'type' => 'upload',
					],
					'from_name'              => [
						'id'   => 'from_name',
						'name' => esc_html__( 'From Name', 'intercessor' ),
						'desc' => esc_html__( 'The name prayer notifications are said to come from. Site prayer group name.', 'intercessor' ),
						'type' => 'text',
						'std'  => get_bloginfo( 'name' ),
					],
					'from_email'             => [
						'id'   => 'from_email',
						'name' => esc_html__( 'From Email', 'intercessor' ),
						'desc' => esc_html__( 'Email to send prayer notifications from. This will act as the "from" and "reply-to" address.', 'intercessor' ),
						'type' => 'email',
						'std'  => get_bloginfo( 'admin_email' ),
					],
					'prayer_subject'         => [
						'id'   => 'prayer_subject',
						'name' => esc_html__( 'Prayer  Request Email Subject', 'intercessor' ),
						'desc' => esc_html__( 'Enter the subject line for the prayer notification email', 'intercessor' ),
						'type' => 'text',
						'std'  => esc_html__( 'Prayer Request Received', 'intercessor' ),
					],
					'prayer_heading'         => [
						'id'   => 'prayer_heading',
						'name' => esc_html__( 'Prayer  Request Email Heading', 'intercessor' ),
						'desc' => esc_html__( 'Enter the heading for the prayer notification email', 'intercessor' ),
						'type' => 'text',
						'std'  => esc_html__( 'We are praying for you', 'intercessor' ),
					],
					'preview_email_settings' => [
						'id'   => 'preview_email_settings',
						'name' => '',
						'desc' => '',
						'type' => 'hook',
					],
				],
				'prayer_notifications' => [
					'prayer_notification_settings' => [
						'id'   => 'prayer_notification_settings',
						'name' => '<h3>' . esc_html__( 'Prayer  Notifications', 'intercessor' ) . '</h3>',
						'type' => 'header',
					],
					'prayer_notification_email_settings' => [
						'id'   => 'prayer_notification_email_settings',
						'name' => '',
						'desc' => '',
						'type' => 'hook',
					],
					'prayer_received_email' => [
						'id'   => 'prayer_received_email',
						'name' => esc_html__( 'Prayer Request', 'intercessor' ),
						'desc' => esc_html__( 'Enter the text that is sent as prayer notification email to users after completion of a successful prayer. HTML is accepted. Available template tags:', 'intercessor' ) . '<br/>' . intercessor_get_emails_tags_list(),
						'type' => 'rich_editor',
						'std'  => esc_html__( 'Dear', 'intercessor' ) . ' {name},\n\n' . esc_html__( 'Thank you for your prayer. Please click on the link(s) below to edit your prayer request or add a praise report.', 'intercessor' ) . '\n\n{intercessor_list}\n\n{sitename}',
					],
					'prayer_notification_heading' => [
						'id'   => 'prayer_notification_heading',
						'name' => esc_html__( 'Prayer  Notification Heading', 'intercessor' ),
						'desc' => esc_html__( 'Enter the heading for the prayer notification email', 'intercessor' ),
						'type' => 'text',
						'std'  => esc_html__( 'New Request submitted!', 'intercessor' ),
					],
					'prayer_notification_subject' => [
						'id'   => 'prayer_notification_subject',
						'name' => esc_html__( 'Prayer  Notification Subject', 'intercessor' ),
						'desc' => esc_html__( 'Enter the subject line for the prayer notification email', 'intercessor' ),
						'type' => 'text',
						'std'  => esc_html__( 'New Prayer - Request #{prayer_id}', 'intercessor' ),
					],
					'prayer_notification' => [
						'id'   => 'prayer_notification',
						'name' => esc_html__( 'Prayer  Notification', 'intercessor' ),
						'desc' => esc_html__( 'Enter the text that is sent as prayer notification email after submission of a prayer. HTML is accepted. Available template tags:', 'intercessor' ) . '<br/>' . intercessor_get_emails_tags_list(),
						'type' => 'rich_editor',
						'std'  => intercessor_get_default_prayer_notification_email(),
					],
					'admin_notice_emails' => [
						'id'   => 'admin_notice_emails',
						'name' => esc_html__( 'Prayer  Notification Emails', 'intercessor' ),
						'desc' => esc_html__( 'Enter the email address(es) that should receive a notification anytime a prayer is made, one per line', 'intercessor' ),
						'type' => 'textarea',
						'std'  => get_bloginfo( 'admin_email' ),
					],
					'disable_admin_notices' => [
						'id'   => 'disable_admin_notices',
						'name' => esc_html__( 'Disable Admin Notifications', 'intercessor' ),
						'desc' => esc_html__( 'Check this box if you do not want to receive prayers notification emails.', 'intercessor' ),
						'type' => 'checkbox',
					],
				],
				'prayed_notice' => [
					'prayed_notice_settings' => [
						'id'   => 'prayed_notice_settings',
						'name' => '<h3>' . esc_html__( 'Prayed For Notices', 'intercessor' ) . '</h3>',
						'type' => 'header',
					],
					'prayed_notice_subject' => [
						'id'   => 'prayed_notice_subject',
						'name' => esc_html__( 'Prayed For Subject', 'intercessor' ),
						'desc' => esc_html__( 'Enter the subject line for the prayed for notification email', 'intercessor' ),
						'type' => 'text',
						'std'  => esc_html__( 'You have been prayed for - Request #{prayer_id}', 'intercessor' ),
					],
					'prayed_notice_text' => [
						'id'   => 'prayed_notice_text',
						'name' => esc_html__( 'Prayed  Notice', 'intercessor' ),
						'desc' => esc_html__( 'Enter the text that is sent as prayed notification email anyday a prayer request is lifted or prayed for. HTML is accepted. Available template tags:', 'intercessor' ) . '<br/>' . intercessor_get_emails_tags_list(),
						'type' => 'rich_editor',
						'std'  => intercessor_get_default_prayed_notice_email(),
					],
					'disable_prayed_notices' => [
						'id'   => 'disable_prayed_notices',
						'name' => esc_html__( 'Disable Prayed Notifications', 'intercessor' ),
						'desc' => esc_html__( 'Check this box if you want to disable the notification emails sent to Requesters when prayed for.', 'intercessor' ),
						'type' => 'checkbox',
					],
				],
			]
		),

		/** Styles Settings */
		'styles' => apply_filters(
			'intercessor_settings_styles',
			array(
				'main'                  => array(
					'style_settings'          => array(
						'id'   => 'style_settings',
						'name' => '<h3>' . esc_html__( 'Style Settings', 'intercessor' ) . '</h3>',
						'type' => 'header',
						'tooltip_title' => esc_html__( 'Page Style Configuration', 'intercessor' ),
						'tooltip_desc'  => esc_html__( 'You can choose the button and page background color of the prayer request page in this section.', 'intercessor' ),
					),
					'disable_styles' => array(
						'id'            => 'disable_styles',
						'name'          => esc_html__( 'Disable Styles', 'intercessor' ),
						'desc'          => esc_html__( 'Check this to disable all included styling of buttons, request form fields, and all other elements.', 'intercessor' ),
						'type'          => 'checkbox',
						'tooltip_title' => esc_html__( 'Disabling Styles', 'intercessor' ),
						'tooltip_desc'  => esc_html__( 'If your theme has a complete custom CSS file for Intercessor, you may wish to disable our default styles. This is not recommended unless your are convinced that your theme has a complete custom CSS.', 'intercessor' ),
					),
					'button_background_color' => array(
						'id'    => 'button_background_color',
						'name' => esc_html__( 'Button Background Color', 'intercessor' ),
						'desc' => esc_html__( 'Choose the color you want to use for the buttons background.', 'intercessor' ),
						'type' => 'color',
						'std'  => '#00bfef',
					),
					'button_border_color' => array(
						'id'    => 'button_border_color',
						'name' => esc_html__( 'Button Border Color', 'intercessor' ),
						'desc' => esc_html__( 'Choose the color you want to use for the buttons border.', 'intercessor' ),
						'type' => 'color',
						'std'  => '#0094d3',
					),
					'button_font_color' => array(
						'id'    => 'button_font_color',
						'name' => esc_html__( 'Button Text Color', 'intercessor' ),
						'desc' => esc_html__( 'Choose the color you want to use for the buttons text.', 'intercessor' ),
						'type' => 'color',
						'std'  => '#ffffff',
					),
				),
			)
		),
	];

	return array_merge( $settings, $intercessor_settings );
}
add_filter( 'intercessor_defined_settings', 'intercessor_plugin_settings_fields' );
