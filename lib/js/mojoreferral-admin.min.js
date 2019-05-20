//********************************************************************************************************************************
// reusable message function
//********************************************************************************************************************************
function mojoreferralMessage( msgType, msgText ) {
	jQuery( 'div#wpbody h2:first' ).after( '<div id="message" class="' + msgType + ' below-h2"><p>' + msgText + '</p></div>' );
}

//********************************************************************************************************************************
// clear any admin messages
//********************************************************************************************************************************
function mojoreferralClearAdmin() {
	jQuery( 'div#wpbody div#message' ).remove();
	jQuery( 'div#wpbody div#setting-error-settings_updated' ).remove();
}

//********************************************************************************************************************************
// button action when disabled
//********************************************************************************************************************************
function mojoreferralBtnDisable( btnDiv, btnSpin, btnItem ) {
	jQuery( btnDiv ).find( btnSpin ).css( 'visibility', 'visible' );
	jQuery( btnDiv ).find( btnItem ).attr( 'disabled', 'disabled' );
}

//********************************************************************************************************************************
// button action when enabled
//********************************************************************************************************************************
function mojoreferralBtnEnable( btnDiv, btnSpin, btnItem ) {
	jQuery( btnDiv ).find( btnSpin ).css( 'visibility', 'hidden' );
	jQuery( btnDiv ).find( btnItem ).removeAttr( 'disabled' );
}

//********************************************************************************************************************************
// start the engine
//********************************************************************************************************************************
jQuery(document).ready( function($) {

//********************************************************************************************************************************
// quick helper to check for an existance of an element
//********************************************************************************************************************************
	$.fn.divExists = function(callback) {
		// slice some args
		var args = [].slice.call( arguments, 1 );
		// check for length
		if ( this.length ) {
			callback.call( this, args );
		}
		// return it
		return this;
	};

//********************************************************************************************************************************
// set some vars
//********************************************************************************************************************************
	var yrlSocial   = '';
	var yrlAdminBox = '';
	var yrlClickBox = '';
	var yrlClickRow = '';
	var yrlKeyword  = '';
	var yrlPostID   = '';
	var yrlNonce    = '';

//********************************************************************************************************************************
// social link in a new window because FANCY
//********************************************************************************************************************************
	$( 'div.mojoreferral-sidebox' ).on( 'click', 'a.admin-twitter-link', function() {
		// only do this on larger screens
		if ( $( window ).width() > 765 ) {
			// get our link
			yrlSocial = $( this ).attr( 'href' );
			// open our fancy window
			window.open( yrlSocial, 'social-share-dialog', 'width=626,height=436' );
			// and finish
			return false;
		}
	});

//********************************************************************************************************************************
// do the password magic
//********************************************************************************************************************************
	$( 'td.apikey-field-wrapper' ).divExists( function() {

		// hide it on load
		$( 'input#mojoreferral-api' ).hidePassword( false );

		// now check for clicks
		$( 'td.apikey-field-wrapper' ).on( 'click', 'span.password-toggle', function () {

			// if our password is not visible
			if ( ! $( this ).hasClass( 'password-visible' ) ) {
				$( this ).addClass( 'password-visible' );
				$( 'input#mojoreferral-api' ).showPassword( false );
			} else {
				$( this ).removeClass( 'password-visible' );
				$( 'input#mojoreferral-api' ).hidePassword( false );
			}

		});
	});

//********************************************************************************************************************************
// other external links in new tab
//********************************************************************************************************************************
	$( 'div.mojoreferral-sidebox' ).find( 'a.external' ).attr( 'target', '_blank' );

//********************************************************************************************************************************
// show / hide post types on admin
//********************************************************************************************************************************
	$( 'tr.setting-item-types' ).divExists( function() {

		// see if our box is checked
		yrlAdminBox = $( this ).find( 'input#mojoreferral-cpt' ).is( ':checked' );

		// if it is, show it
		if ( yrlAdminBox === true ) {
			$( 'tr.secondary' ).show();
		}

		// if not, hide it and make sure boxes are not checked
		if ( yrlAdminBox === false ) {
			$( 'tr.secondary' ).hide();
			$( 'tr.secondary' ).find( 'input:checkbox' ).prop( 'checked', false );
		}

		// now the check for clicking
		$( 'tr.setting-item-types' ).on( 'change', 'input#mojoreferral-cpt', function() {

			// check the box (again)
			yrlAdminBox = $( this ).is( ':checked' );

			// if it is, show it
			if ( yrlAdminBox === true ) {
				$( 'tr.secondary' ).fadeIn( 700 );
			}

			// if not, hide it and make sure boxes are not checked
			if ( yrlAdminBox === false ) {
				$( 'tr.secondary' ).fadeOut( 700 );
				$( 'tr.secondary' ).find( 'input:checkbox' ).prop( 'checked', false );
			}
		});

	});

//********************************************************************************************************************************
// create mojoreferral on call
//********************************************************************************************************************************
	$( 'div#mojoreferral-post-display').on( 'click', 'input.mojoreferral-api', function () {

		// get my post ID and my nonce
		yrlPostID   = $( this ).data( 'post-id' );
		yrlNonce    = $( this ).data( 'nonce' );

		// bail without post ID or nonce
		if ( yrlPostID === '' || yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		$( 'div#wpbody div#message' ).remove();
		$( 'div#wpbody div#setting-error-settings_updated' ).remove();

		// adjust buttons
		$( 'div#mojoreferral-post-display' ).find( 'span.mojoreferral-spinner' ).css( 'visibility', 'visible' );
		$( 'div#mojoreferral-post-display' ).find( 'input.mojoreferral-api').attr( 'disabled', 'disabled' );

		// get my optional keyword
		yrlKeyword  = $( 'div#mojoreferral-post-display' ).find( 'input.mojoreferral-keyw' ).val();

		// set my data array
		var data = {
			action:  'create_mojoreferral',
			keyword: yrlKeyword,
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			$( 'div#mojoreferral-post-display' ).find( 'span.mojoreferral-spinner' ).css( 'visibility', 'hidden' );
			$( 'div#mojoreferral-post-display' ).find( 'input.mojoreferral-api').removeAttr( 'disabled' );

			var obj;

			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				mojoreferralMessage( 'error', mojoreferralAdmin.defaultError );
			}

			if( obj.success === true ) {
				mojoreferralMessage( 'updated', obj.message );
			}

			else if( obj.success === false && obj.message !== null ) {
				mojoreferralMessage( 'error', obj.message );
			}

			else {
				mojoreferralMessage( 'error', mojoreferralAdmin.defaultError );
			}

			// add in the new mojoreferral box if it comes back
			if( obj.success === true && obj.linkbox !== null ) {

				// remove the submit box
				$( 'div#mojoreferral-post-display' ).find( 'p.mojoreferral-submit-block' ).remove();

				// swap out our boxes
				$( 'div#mojoreferral-post-display' ).find( 'p.mojoreferral-input-block' ).replaceWith( obj.linkbox );

				// add our shortlink button
				$( 'div#edit-slug-box' ).append( '<input type="hidden" value="' + obj.linkurl + '" id="shortlink">' );
				$( 'div#edit-slug-box' ).append( mojoreferralAdmin.shortSubmit );
			}
		});

	});

//********************************************************************************************************************************
// delete mojoreferral on call
//********************************************************************************************************************************
	$( 'div#mojoreferral-post-display' ).on( 'click', 'span.mojoreferral-delete', function () {

		// get my post ID and nonce
		yrlPostID   = $( this ).data( 'post-id' );
		yrlNonce    = $( this ).data( 'nonce' );

		// bail without post ID or nonce
		if ( yrlPostID === '' || yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		$( 'div#wpbody div#message' ).remove();
		$( 'div#wpbody div#setting-error-settings_updated' ).remove();

		// set my data array
		var data = {
			action:  'delete_mojoreferral',
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				mojoreferralMessage( 'error', mojoreferralAdmin.defaultError );
			}

			if( obj.success === true ) {
				mojoreferralMessage( 'updated', obj.message );
			}

			else if( obj.success === false && obj.message !== null ) {
				mojoreferralMessage( 'error', obj.message );
			}
			else {
				mojoreferralMessage( 'error', mojoreferralAdmin.defaultError );
			}

			// add in the new mojoreferral box if it comes back
			if( obj.success === true && obj.mojoreferralbox !== null ) {

				$( 'div#mojoreferral-post-display' ).find( 'p.howto' ).remove();
				$( 'div#mojoreferral-post-display' ).find( 'p.mojoreferral-exist-block' ).replaceWith( obj.linkbox );

				$( 'div#edit-slug-box' ).find( 'input#shortlink' ).remove();
				$( 'div#edit-slug-box' ).find( 'a:contains("Get Shortlink")' ).remove();
			}
		});

	});

//********************************************************************************************************************************
// update mojoreferral click count
//********************************************************************************************************************************
	$( 'div.row-actions' ).on( 'click', 'a.mojoreferral-admin-update', function (e) {

		// stop the hash
		e.preventDefault();

		// get my nonce
		yrlNonce    = $( this ).data( 'nonce' );

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// get my post ID
		yrlPostID   = $( this ).data( 'post-id' );

		// bail without post ID
		if ( yrlPostID === '' ) {
			return;
		}

		// set my row and box as a variable for later
		yrlClickRow = $( this ).parents( 'div.row-actions' );
		yrlClickBox = $( this ).parents( 'tr.entry' ).find( 'td.mojoreferral-click' );

		// set my data array
		var data = {
			action:  'stats_mojoreferral',
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// hide the row actions
			yrlClickRow.removeClass( 'visible' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				return false;
			}

			// add in the new number box if it comes back
			if( obj.success === true && obj.clicknm !== null ) {
				yrlClickBox.find( 'span' ).text( obj.clicknm );
			}
		});
	});

//********************************************************************************************************************************
// create mojoreferral inline
//********************************************************************************************************************************
	$( 'div.row-actions' ).on( 'click', 'a.mojoreferral-admin-create', function (e) {

		// stop the hash
		e.preventDefault();

		// get my nonce
		yrlNonce    = $( this ).data( 'nonce' );

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// get my post ID
		yrlPostID   = $( this ).data( 'post-id' );

		// bail without post ID
		if ( yrlPostID === '' ) {
			return;
		}

		// set my row and box as a variable for later
		yrlClickRow = $( this ).parents( 'div.row-actions' );
		yrlClickBox = $( this ).parents( 'div.row-actions' ).find( 'span.create-mojoreferral' );

		// set my data array
		var data = {
			action:  'inline_mojoreferral',
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// hide the row actions
			yrlClickRow.removeClass( 'visible' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				return false;
			}

			// add in the new click box if it comes back
			if( obj.success === true && obj.rowactn !== null ) {
				yrlClickBox.replaceWith( obj.rowactn );
			}
		});
	});

//********************************************************************************************************************************
// run API status update update from admin
//********************************************************************************************************************************
	$( 'div#mojoreferral-admin-status' ).on( 'click', 'input.mojoreferral-click-status', function () {

		// get my nonce first
		yrlNonce    = $( 'input#mojoreferral_status' ).val();

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		mojoreferralClearAdmin();

		// adjust buttons
		mojoreferralBtnDisable( 'div#mojoreferral-admin-status', 'span.mojoreferral-status-spinner', 'input.mojoreferral-click-status' );

		// set my data array
		var data = {
			action:  'status_mojoreferral',
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// adjust buttons
			mojoreferralBtnEnable( 'div#mojoreferral-admin-status', 'span.mojoreferral-status-spinner', 'input.mojoreferral-click-status' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				mojoreferralMessage( 'error', mojoreferralAdmin.defaultError );
			}

			// we got a status back
			if( obj.success === true ) {

				// check the icon
				if( obj.baricon !== '' ) {
					$( 'div#mojoreferral-admin-status' ).find( 'span.api-status-icon' ).replaceWith( obj.baricon );
				}

				// check the text return
				if( obj.message !== '' ) {
					$( 'div#mojoreferral-admin-status' ).find( 'p.api-status-text' ).text( obj.message );
				}

				// check the checkmark return
				if( obj.stcheck !== '' ) {
					// add the checkmark
					$( 'div#mojoreferral-admin-status' ).find( 'p.api-status-actions' ).append( obj.stcheck );
					// delay then fade out
					$( 'span.api-status-checkmark' ).delay( 3000 ).fadeOut( 1000 );
				}

			}
			else if( obj.success === false && obj.message !== null ) {
				mojoreferralMessage( 'error', obj.message );
			}
			else {
				mojoreferralMessage( 'error', mojoreferralAdmin.defaultError );
			}
		});
	});

//********************************************************************************************************************************
// run click update from admin
//********************************************************************************************************************************
	$( 'div#mojoreferral-data-refresh' ).on( 'click', 'input.mojoreferral-click-updates', function () {

		// get my nonce first
		yrlNonce    = $( 'input#mojoreferral_refresh' ).val();

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		mojoreferralClearAdmin();

		// adjust buttons
		mojoreferralBtnDisable( 'div#mojoreferral-data-refresh', 'span.mojoreferral-refresh-spinner', 'input.mojoreferral-click-updates' );

		// set my data array
		var data = {
			action:  'refresh_mojoreferral',
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// adjust buttons
			mojoreferralBtnEnable( 'div#mojoreferral-data-refresh', 'span.mojoreferral-refresh-spinner', 'input.mojoreferral-click-updates' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				mojoreferralMessage( 'error', mojoreferralAdmin.defaultError );
			}

			if( obj.success === true && obj.message !== '' ) {
				mojoreferralMessage( 'updated', obj.message );
			}
			else if( obj.success === false && obj.message !== null ) {
				mojoreferralMessage( 'error', obj.message );
			}
			else {
				mojoreferralMessage( 'error', mojoreferralAdmin.defaultError );
			}
		});
	});

//********************************************************************************************************************************
// attempt data import
//********************************************************************************************************************************
	$( 'div#mojoreferral-data-refresh' ).on( 'click', 'input.mojoreferral-click-import', function () {

		// get my nonce first
		yrlNonce    = $( 'input#mojoreferral_import' ).val();

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		mojoreferralClearAdmin();

		// adjust buttons
		mojoreferralBtnDisable( 'div#mojoreferral-data-refresh', 'span.mojoreferral-import-spinner', 'input.mojoreferral-click-import' );

		// set my data array
		var data = {
			action:  'import_mojoreferral',
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// adjust buttons
			mojoreferralBtnEnable( 'div#mojoreferral-data-refresh', 'span.mojoreferral-import-spinner', 'input.mojoreferral-click-import' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				mojoreferralMessage( 'error', mojoreferralAdmin.defaultError );
			}

			if( obj.success === true && obj.message !== '' ) {
				mojoreferralMessage( 'updated', obj.message );
			}
			else if( obj.success === false && obj.message !== null ) {
				mojoreferralMessage( 'error', obj.message );
			}
			else {
				mojoreferralMessage( 'error', mojoreferralAdmin.defaultError );
			}
		});
	});

//********************************************************************************************************************************
// change meta key from old plugin
//********************************************************************************************************************************
	$( 'div#mojoreferral-data-refresh' ).on( 'click', 'input.mojoreferral-convert', function () {

		// get my nonce first
		yrlNonce    = $( 'input#mojoreferral_convert' ).val();

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		mojoreferralClearAdmin();

		// adjust buttons
		mojoreferralBtnDisable( 'div#mojoreferral-data-refresh', 'span.mojoreferral-convert-spinner', 'input.mojoreferral-convert' );

		// set my data array
		var data = {
			action:  'convert_mojoreferral',
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// adjust buttons
			mojoreferralBtnEnable( 'div#mojoreferral-data-refresh', 'span.mojoreferral-convert-spinner', 'input.mojoreferral-convert' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				mojoreferralMessage( 'error', mojoreferralAdmin.defaultError );
			}

			if( obj.success === true && obj.message !== '' ) {
				mojoreferralMessage( 'updated', obj.message );
			}
			else if( obj.success === false && obj.message !== null ) {
				mojoreferralMessage( 'error', obj.message );
			}
			else {
				mojoreferralMessage( 'error', mojoreferralAdmin.defaultError );
			}
		});
	});
    jQuery('.color-field').each(function(){
    jQuery(this).wpColorPicker();
    });
//********************************************************************************************************************************
// you're still here? it's over. go home.
//********************************************************************************************************************************
});
