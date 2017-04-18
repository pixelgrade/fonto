(function (tinymce) {
	tinymce.PluginManager.add('raw_head_code', function (editor) {
		editor.on('preinit', function (args) {
			// find the iframe which relates to this instance of the editor
			var iframe = jQuery("#" + args.target.id + "_ifr");

			if ( typeof tinymce_raw_head_code !== "undefined" ) {
				// get the head element of the iframe's document and inject the code
				jQuery(iframe[0].contentWindow.document)
					.children('html')
					.children('head')
					.append(tinymce_raw_head_code.code); //this one is available globally through script localization; see class-fonto-output.php@237
			}
		});
	});
}(window.tinymce));
