(function( tinymce ) {
    tinymce.PluginManager.add('raw_head_code', function( editor ) {
        editor.on('preinit', function ( args) {
            // find the iframe which relates to this instance of the editor
            var iframe = jQuery("#" + args.target.id + "_ifr");

            // get the head element of the iframe's document and inject the code
            jQuery(iframe[0].contentWindow.document)
                .children('html')
                .children('head')
                .append( tinymce_raw_head_code.code );

            // Get the iframe.
            // var doc = editor.getDoc();
            //
            // // Create a temporary div to hold the code so we can cycle through it's children DOM nodes
            // var temp = doc.createElement('div');
            // temp.innerHTML = tinymce_raw_head_code.code;
            //
            // //get the <head> node
            // var head = doc.getElementsByTagName('head')[0];
            //
            // // Append each child node to the <head> tag
            // while (temp.firstChild) {
            //     head.appendChild(temp.firstChild);
            // }
        });
    });
}( window.tinymce ));
