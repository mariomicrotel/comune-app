/**
 * Comune App Manager — Admin JavaScript
 *
 * Uses jQuery (bundled with WordPress) plus vanilla DOM APIs.
 * All localised data is available via window.camAdmin (wp_localize_script).
 *
 * @package Comune_App_Manager
 * @since   1.0.0
 */

/* global camAdmin, wp */

( function ( $ ) {
	'use strict';

	/**
	 * Safely get a localised string, falling back to the key name.
	 *
	 * @param {string} key
	 * @returns {string}
	 */
	function str( key ) {
		return ( camAdmin && camAdmin.strings && camAdmin.strings[ key ] ) ? camAdmin.strings[ key ] : key;
	}

	// =========================================================================
	// Push notification form
	// =========================================================================

	function initPushForm() {
		var form = document.getElementById( 'cam-push-form' );
		if ( ! form ) { return; }

		var radios     = form.querySelectorAll( 'input[name="target_type"]' );
		var platformRow = document.getElementById( 'target-platform-row' );
		var zonaRow     = document.getElementById( 'target-zona-row' );
		var titleInput  = document.getElementById( 'push_title' );
		var bodyInput   = document.getElementById( 'push_body' );
		var titleCount  = document.getElementById( 'title-count' );
		var bodyCount   = document.getElementById( 'body-count' );
		var titleCounter = document.getElementById( 'title-counter' );
		var bodyCounter  = document.getElementById( 'body-counter' );
		var previewTitle = document.getElementById( 'preview-title' );
		var previewBody  = document.getElementById( 'preview-body' );
		var sendBtn      = document.getElementById( 'cam-send-push-btn' );
		var spinner      = document.getElementById( 'cam-push-spinner' );

		/**
		 * Show / hide target value rows based on selected radio.
		 */
		function updateTargetRows() {
			var val = form.querySelector( 'input[name="target_type"]:checked' );
			val = val ? val.value : 'tutti';

			if ( platformRow ) { platformRow.style.display = 'platform'      === val ? '' : 'none'; }
			if ( zonaRow )     { zonaRow.style.display     = 'zona_rifiuti'  === val ? '' : 'none'; }
		}

		radios.forEach( function ( r ) {
			r.addEventListener( 'change', updateTargetRows );
		} );
		updateTargetRows();

		/**
		 * Character counters.
		 */
		function updateCounter( input, countEl, counterEl, max ) {
			if ( ! input || ! countEl ) { return; }
			var len = input.value.length;
			countEl.textContent = len;
			if ( counterEl ) {
				counterEl.classList.toggle( 'cam-over-limit', len > max );
			}
		}

		if ( titleInput ) {
			titleInput.addEventListener( 'input', function () {
				updateCounter( titleInput, titleCount, titleCounter, 50 );
				if ( previewTitle ) { previewTitle.textContent = titleInput.value || str( 'previewTitle' ); }
			} );
			updateCounter( titleInput, titleCount, titleCounter, 50 );
		}

		if ( bodyInput ) {
			bodyInput.addEventListener( 'input', function () {
				updateCounter( bodyInput, bodyCount, bodyCounter, 200 );
				if ( previewBody ) { previewBody.textContent = bodyInput.value || str( 'previewBody' ); }
			} );
			updateCounter( bodyInput, bodyCount, bodyCounter, 200 );
		}

		// Show spinner on submit.
		if ( sendBtn && spinner ) {
			form.addEventListener( 'submit', function () {
				spinner.style.display = 'inline-block';
				sendBtn.disabled = true;
			} );
		}
	}

	// =========================================================================
	// Report detail — AJAX status update
	// =========================================================================

	function initReportDetail() {
		var form    = document.getElementById( 'cam-report-edit-form' );
		var spinner = document.getElementById( 'cam-update-spinner' );

		if ( ! form ) { return; }

		if ( spinner ) {
			form.addEventListener( 'submit', function () {
				spinner.style.display = 'inline-block';
			} );
		}

		// AJAX quick-status change (from list via data attributes, optional).
		$( document ).on( 'change', '.cam-quick-status-select', function () {
			var $sel    = $( this );
			var reportId = $sel.data( 'report-id' );
			var newStatus = $sel.val();

			if ( ! reportId || ! newStatus ) { return; }

			var $spinner = $( '<span class="spinner is-active" style="float:none;vertical-align:middle;"></span>' );
			$sel.after( $spinner );
			$sel.prop( 'disabled', true );

			$.ajax( {
				url:    ( camAdmin && camAdmin.ajaxUrl ) || ajaxurl,
				method: 'POST',
				data:   {
					action:    'cam_update_segnalazione_status',
					nonce:     ( camAdmin && camAdmin.nonce ) || '',
					id:        reportId,
					status:    newStatus,
				},
				success: function ( response ) {
					$spinner.remove();
					$sel.prop( 'disabled', false );

					if ( response.success ) {
						// Update badge in same row if present.
						var $badge = $sel.closest( 'tr' ).find( '.cam-status-badge' );
						if ( $badge.length ) {
							$badge.attr( 'class', 'cam-status-badge status-' + newStatus );
							$badge.text( response.data.label || newStatus );
						}
					} else {
						alert( response.data.message || str( 'errorGeneric' ) );
					}
				},
				error: function () {
					$spinner.remove();
					$sel.prop( 'disabled', false );
					alert( str( 'errorGeneric' ) );
				},
			} );
		} );
	}

	// =========================================================================
	// Waste calendar — AJAX (optional progressive enhancement)
	// =========================================================================

	function initWasteCalendar() {
		// Confirm on delete links (non-AJAX fallback already in PHP href).
		$( document ).on( 'click', '.cam-waste-delete', function ( e ) {
			if ( ! confirm( str( 'confirmDelete' ) ) ) {
				e.preventDefault();
			}
		} );
	}

	// =========================================================================
	// Survey results — animate bar chart fills on load
	// =========================================================================

	function initSurveyBars() {
		var fills = document.querySelectorAll( '.cam-bar-fill[data-width]' );
		if ( ! fills.length ) { return; }

		// Use rAF + small delay so CSS transitions fire after paint.
		requestAnimationFrame( function () {
			setTimeout( function () {
				fills.forEach( function ( el ) {
					var pct = parseFloat( el.getAttribute( 'data-width' ) ) || 0;
					el.style.width = pct + '%';
				} );
			}, 80 );
		} );
	}

	// =========================================================================
	// Dashboard — auto-refresh stat counts every 60 s
	// =========================================================================

	function initDashboardRefresh() {
		var statsGrid = document.querySelector( '.cam-dashboard-stats' );
		if ( ! statsGrid ) { return; }
		if ( ! camAdmin || ! camAdmin.ajaxUrl ) { return; }

		setInterval( function () {
			$.ajax( {
				url:    camAdmin.ajaxUrl,
				method: 'POST',
				data:   {
					action: 'cam_get_dashboard_stats',
					nonce:  camAdmin.nonce || '',
				},
				success: function ( response ) {
					if ( ! response.success || ! response.data ) { return; }

					var data = response.data;

					var cards = statsGrid.querySelectorAll( '.cam-stat-card' );

					var keys = [ 'avvisi', 'reports', 'tokens', 'surveys' ];
					keys.forEach( function ( key, i ) {
						if ( cards[ i ] && data[ key ] !== undefined ) {
							var countEl = cards[ i ].querySelector( '.count' );
							if ( countEl ) {
								countEl.textContent = data[ key ];
							}
						}
					} );
				},
			} );
		}, 60000 );
	}

	// =========================================================================
	// Firebase test button
	// =========================================================================

	function initFirebaseTest() {
		var btn    = document.getElementById( 'cam-test-firebase' );
		var result = document.getElementById( 'cam-firebase-test-result' );

		if ( ! btn ) { return; }

		btn.addEventListener( 'click', function () {
			if ( ! camAdmin || ! camAdmin.ajaxUrl ) {
				alert( str( 'errorGeneric' ) );
				return;
			}

			btn.disabled = true;
			btn.textContent = str( 'testing' ) || 'Testing…';
			if ( result ) {
				result.style.display = 'none';
				result.textContent   = '';
				result.className     = 'cam-inline-result';
			}

			$.ajax( {
				url:    camAdmin.ajaxUrl,
				method: 'POST',
				data:   {
					action: 'cam_test_firebase',
					nonce:  camAdmin.nonce || '',
				},
				success: function ( response ) {
					btn.disabled = false;
					btn.textContent = str( 'testConnection' ) || 'Testa connessione';

					if ( result ) {
						result.style.display = 'inline';
						result.textContent   = response.data ? ( response.data.message || '' ) : str( 'errorGeneric' );
						result.classList.add( response.success ? 'cam-inline-result--ok' : 'cam-inline-result--error' );
					}
				},
				error: function () {
					btn.disabled = false;
					btn.textContent = str( 'testConnection' ) || 'Testa connessione';

					if ( result ) {
						result.style.display = 'inline';
						result.textContent   = str( 'errorGeneric' );
						result.classList.add( 'cam-inline-result--error' );
					}
				},
			} );
		} );
	}

	// =========================================================================
	// Push form: live preview update on deep-link type change
	// =========================================================================

	function initPushPreviewDeepLink() {
		var deepLinkSel = document.getElementById( 'deep_link_type' );
		if ( ! deepLinkSel ) { return; }

		deepLinkSel.addEventListener( 'change', function () {
			// Nothing visual changes in preview for deep link — handled by server.
		} );
	}

	// =========================================================================
	// Bootstrap
	// =========================================================================

	$( function () {
		initPushForm();
		initReportDetail();
		initWasteCalendar();
		initSurveyBars();
		initDashboardRefresh();
		initFirebaseTest();
		initPushPreviewDeepLink();
	} );

}( typeof jQuery !== 'undefined' ? jQuery : { fn: {}, ajax: function () {}, on: function () {} } ) );
