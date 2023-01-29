/**
 * Intercessor Admin Settings JS
 *
 * @package:     Intercessor
 * @copyright:   Copyright (c) 2020, Victor Aigbeghian
 * @license:     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 */

jQuery( document ).ready( function( $ ) {
    const Intercessor_Settings = {
        init: function() {
            this.tooltips();
            this.main();
            this.emails();
        },

        tooltips: function() {
            var tooltips = $( '.intercessor-help-tip' );
            tooltips.tooltip( {
                content: function() {
                    return $( this ).prop( 'title' );
                },
                tooltipClass: 'intercessor-ui-tooltip',
                position: {
                    my: 'center top',
                    at: 'center bottom+10',
                    collision: 'flipfit'
                },
                hide: {
                    duration: 220
                },
                show: {
                    duration: 220
                }
            } );
        },

        main: function() {
            // Initilize color picker.
            const intercessor_color_picker = $( '.intercessor-color-picker' );

            if ( intercessor_color_picker.length ) {
                intercessor_color_picker.wpColorPicker();
            }

            // WP 3.5+ uploader
            var file_frame;
            window.formfield = '';

            $( document.body ).on( 'click', '.intercessor_settings_upload_button', function( e ) {

                e.preventDefault();

                const button = $( this );

                window.formfield = $( this ).parent().prev();

                // If the media frame already exists, reopen it.
                if ( file_frame ) {
                    file_frame.open();
                    return;
                }

                // Create the media frame.
                file_frame = wp.media.frames.file_frame = wp.media( {
					title: button.data( 'uploader_title' ),
					library: { type: 'image' },
					button: { text: button.data( 'uploader_button_text' ) },
					multiple: false,
				} );

                file_frame.on( 'menu:render:default', function( view ) {
					// Store our views in an object.
					const views = {};

					// Unset default menu items.
					view.unset( 'library-separator' );
					view.unset( 'gallery' );
					view.unset( 'featured-image' );
					view.unset( 'embed' );

					// Initialize the views in our view object.
					view.set( views );
				} );

				// When an image is selected, run a callback.
				file_frame.on( 'select', function() {
					const selection = file_frame.state().get( 'selection' );
					selection.each( function( attachment, index ) {
						attachment = attachment.toJSON();
						window.formfield.val( attachment.url );
					} );
				} );

                // Finally, open the modal
                file_frame.open();
            } );

			// WP 3.5+ uploader.
			var file_frame;
			window.formfield = '';
        },

        emails: function() {
            // Show the email template previews.
            var email_preview_wrap = $( '#email-preview-wrap' );
            if ( email_preview_wrap.length ) {
                var emailPreview = $( '#email-preview' );
                email_preview_wrap.colorbox( {
                    inline: true,
                    href: emailPreview,
                    width: '80%',
                    height: 'auto'
                } );
            }
        }
    };
    Intercessor_Settings.init();
} );