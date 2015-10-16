(function() {

    tinymce.PluginManager.add('pushortcodes', function( editor )
    {
        var shortcodeValues = [];
        jQuery.each(shortcodes_button, function(i, label)
        {
            //console.log(label);
            shortcodeValues.push({text: shortcodes_button[i], value:label});
        });

        editor.addButton('pushortcodes', {
            type: 'listbox',
            text: 'Shortcodes',
            onselect: function(e) {
                var v = e.control._value;
		if( v == 'ac_show_youtube_video' ) {
                	tinyMCE.activeEditor.selection.setContent( '[' + v + ' width="420" height="315"][/' + v + ']' );
		} else {
			tinyMCE.activeEditor.selection.setContent( '[' + v + '][/' + v + ']' );
		}
            },
            values: shortcodeValues
        });
    });
})();
