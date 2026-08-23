/**
 * Intercessor admin JavaScript.
 *
 * Handles progressive-enhancement behaviours in the Intercessor admin pages.
 * All critical functionality (form submission, moderation) works without JS;
 * this file adds convenience UX only.
 *
 * @package Intercessor
 * @since   1.0.0
 */

/* global jQuery */
( function ( $ ) {
	'use strict';

	/**
	 * Auto-dismiss WordPress admin notices after 5 seconds.
	 * Standard dismissible notices already have a close button; this just
	 * removes them automatically for a cleaner workflow.
	 */
	function autoDismissNotices() {
		setTimeout( function () {
			$( '.intercessor-dashboard .notice.is-dismissible, ' +
			   '.wrap .notice.notice-success.is-dismissible' ).fadeOut( 400 );
		}, 5000 );
	}

	/**
	 * Confirm destructive bulk-delete action before the form is submitted.
	 * Intercepts the bulk action form on the Prayer Requests list table.
	 */
	function confirmBulkDelete() {
		$( 'body' ).on( 'submit', 'form[action*="admin-post.php"]', function ( e ) {
			var $form   = $( this );
			var action  = $form.find( '[name="bulk_action"]' ).val();
			var checked = $form.find( 'input[name="bulk_ids[]"]:checked' ).length;

			if ( action !== 'bulk_delete' || checked === 0 ) {
				return;
			}

			var message = window.intercessorAdmin && window.intercessorAdmin.i18n
				? window.intercessorAdmin.i18n.confirmDelete
				: 'Permanently delete the selected prayer requests? This cannot be undone.';

			if ( ! window.confirm( message ) ) {
				e.preventDefault();
			}
		} );
	}

	/**
	 * Add Prayer Request modal — open, close, and "for type" toggle.
	 */
	function initAddRequestModal() {
		var $modal      = $( '#intercessor-add-modal' );
		var $backdrop   = $( '#intercessor-modal-backdrop' );
		var $openBtn    = $( '#intercessor-add-request-btn' );
		var $closeBtn   = $( '#intercessor-modal-close' );
		var $cancelBtn  = $( '#intercessor-modal-cancel' );
		var $forRadios  = $modal.find( '[name="for_type"]' );
		var $otherFlds  = $( '#intercessor-modal-other-fields' );
		var $emailFld   = $( '#ipr-add-email' );
		var $firstFld   = $( '#ipr-add-first-name' );
		var $lastFld    = $( '#ipr-add-last-name' );

		if ( ! $modal.length ) {
			return;
		}

		function openModal() {
			$modal.removeAttr( 'hidden' );
			$modal.find( '#ipr-add-subject' ).trigger( 'focus' );
			$( 'body' ).addClass( 'intercessor-modal-open' );
		}

		function closeModal() {
			$modal.attr( 'hidden', true );
			$( 'body' ).removeClass( 'intercessor-modal-open' );
			$modal[0].querySelector( 'form' ).reset();
			$otherFlds.attr( 'hidden', true );
		}

		function applyForType() {
			var type = $forRadios.filter( ':checked' ).val();
			var user = window.intercessorAdmin && window.intercessorAdmin.currentUser
				? window.intercessorAdmin.currentUser : {};

			if ( type === 'self' ) {
				$otherFlds.attr( 'hidden', true );
				$emailFld.removeAttr( 'required' );
				$firstFld.removeAttr( 'required' );
			} else {
				$otherFlds.removeAttr( 'hidden' );
				$emailFld.attr( 'required', true );
				$firstFld.attr( 'required', true );
				// Pre-clear if switching back from self
				if ( $emailFld.val() === ( user.email || '' ) ) {
					$emailFld.val( '' );
					$firstFld.val( '' );
					$lastFld.val( '' );
				}
				$emailFld.trigger( 'focus' );
			}
		}

		$openBtn.on( 'click', openModal );
		$closeBtn.on( 'click', closeModal );
		$cancelBtn.on( 'click', closeModal );
		$backdrop.on( 'click', closeModal );
		$forRadios.on( 'change', applyForType );

		// Close on Escape key.
		$( document ).on( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && ! $modal.attr( 'hidden' ) ) {
				closeModal();
			}
		} );
	}

	/**
	 * Highlight the currently active status filter tab link.
	 */
	function highlightActiveTab() {
		var params = new URLSearchParams( window.location.search );
		var filter = params.get( 'status_filter' ) || '';

		$( '.subsubsub a' ).each( function () {
			var href   = $( this ).attr( 'href' ) || '';
			var hParam = new URLSearchParams( href.split( '?' )[ 1 ] || '' );
			if ( ( hParam.get( 'status_filter' ) || '' ) === filter ) {
				$( this ).addClass( 'current' );
			}
		} );
	}

	/**
	 * Wire up the admin "I prayed for this" buttons (list table row actions
	 * and the single-request detail view). Records the interaction via AJAX
	 * against intercessor_admin_record_prayer and updates the visible count
	 * in place — no page reload. Works for requests in any status, including
	 * 'private' ones that never appear on the public Prayer Wall.
	 */
	function bindAdminPrayButtons() {
		var config = window.intercessorAdmin && window.intercessorAdmin.adminPray
			? window.intercessorAdmin.adminPray
			: {};
		var i18n = config.i18n || {};

		$( 'body' ).on( 'click', '.intercessor-admin-pray-btn', function () {
			var $btn   = $( this );
			var $label = $btn.find( '.intercessor-admin-pray-label' );
			var $count = $btn.find( '.intercessor-admin-pray-count' );
			var orig   = $label.text();

			if ( $btn.prop( 'disabled' ) ) {
				return;
			}

			$btn.prop( 'disabled', true ).addClass( 'intercessor-admin-pray-btn--loading' );
			$label.text( i18n.praying || 'Recording\u2026' );

			$.post(
				config.ajaxUrl || ajaxurl,
				{
					action:     config.action || 'intercessor_admin_record_prayer',
					nonce:      config.nonce || '',
					request_id: $btn.data( 'requestId' ),
				}
			).done( function ( response ) {
				if ( response && response.success ) {
					$label.text( i18n.prayed || 'Prayed for' );
					$count.text( response.data.total );
					$btn.addClass( 'intercessor-admin-pray-btn--prayed' );
					$btn.prop( 'disabled', false );
				} else {
					$label.text( orig );
					$btn.prop( 'disabled', false );
					window.alert( // eslint-disable-line no-alert
						( response && response.data && response.data.message ) || i18n.error || 'Could not record your prayer. Please try again.'
					);
				}
			} ).fail( function () {
				$label.text( orig );
				$btn.prop( 'disabled', false );
				window.alert( i18n.error || 'Could not record your prayer. Please try again.' ); // eslint-disable-line no-alert
			} ).always( function () {
				$btn.removeClass( 'intercessor-admin-pray-btn--loading' );
			} );
		} );
	}

	$( function () {
		autoDismissNotices();
		confirmBulkDelete();
		highlightActiveTab();
		initAddRequestModal();
		bindAdminPrayButtons();
	} );

} )( jQuery );
