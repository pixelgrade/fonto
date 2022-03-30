jQuery(document).ready(function ($) {

	$( '#fonto_font_files-status' ).bind( 'DOMSubtreeModified', function() {
		if( $( this ).is( ':empty' ) ) {
			$( this ).parent().removeClass( 'is--populated' );
		} else {
			$( this ).parent().addClass( 'is--populated' );
		}
	});

	$( window ).on('load', function() {
		$( 'body' ).addClass( 'is--loaded' );
	});

	//$( '#fonto_font_files-status' ).on( 'click', function() {
	//	$( '.cmb2-id-fonto-font-files .cmb2-upload-button' ).trigger( 'click' );
	//});

	//( '#fonto_font_files-status' ).after( '<i class="edit-fonts-icon  icon  dashicons  dashicons-images-alt2"></i>');

	// Require post title when adding/editing Fonts.
	// inspired by http://kellenmace.com/require-post-title-for-custom-post-type/
	$( 'body' ).on( 'submit.edit-post', '#post', function () {

		// If the title isn't set
		if ( $( "#title" ).val().replace( / /g, '' ).length === 0 ) {

			// Show the alert
			window.alert( 'A title is required.' );

			// Hide the spinner
			$( '#major-publishing-actions .spinner' ).hide();

			// The buttons get "disabled" added to them on submit. Remove that class.
			$( '#major-publishing-actions' ).find( ':button, :submit, a.submitdelete, #post-preview' ).removeClass( 'disabled' );

			// Focus on the title field.
			$( "#title" ).focus();

			return false;
		}
	});


	// Update the URL Path input.
	$(document).on( 'before-autosave.update-post-slug', function() {
		titleHasFocus = document.activeElement && document.activeElement.id === 'title';
	}).on( 'after-autosave.update-post-slug', function() {
		// Create slug area only if not already there
		// and the title field was not focused (user was not typing a title) when autosave ran.
		if ( ! titleHasFocus ) {
			$.post( ajaxurl, {
					action: 'sample_font_url_path',
					post_id: $('#post_ID').val(),
					new_title: $('#title').val(),
					samplepermalinknonce: $('#samplepermalinknonce').val()
				},
				function( data ) {
					if ( data != '' ) {
						$('#fonto_url_path').val(data);
					}
				}
			);
		}
	});

	// Set the CMB field object ID in wp.media post id so that uploaded files get attached to the current post.
	$(document).on( 'cmb_media_modal_init', function( ev, media ) {
		wp.media.view.settings.post.id = media.fieldData.objectid || 0;
	} )
});
