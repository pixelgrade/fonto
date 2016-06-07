jQuery(document).ready(function ($) {

	$('#fonto_font_files-status').bind('DOMSubtreeModified', function() {
		if( $(this).is(':empty') ) {
			$(this).parent().removeClass('is--populated');
		} else {
			$(this).parent().addClass('is--populated');
		}
	});

	$('#fonto_font_files-status').on('click', function() {
		$('.cmb2-id-fonto-font-files .cmb2-upload-button').trigger('click');
	});

	$('#fonto_font_files-status').after('<i class="edit-fonts-icon  icon  dashicons  dashicons-images-alt2"></i>');
});