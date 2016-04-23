(function() {

    tinymce.PluginManager.add('pushortcodes', function( editor )
    {
        var shortcodeValues = [];
        jQuery.each(shortcodes_button, function(i, label) {
            shortcodeValues.push({text: shortcodes_button[i], value:label});
        });

        editor.addButton('shortcodes', {
            type: 'listbox',
            text: 'my_own_decription',
            icon: false,
            tooltip: 'my_own_decription',
            fixedWidth: true,
            onselect: function(e) {
                var options = {paragraphs: 1, calldirect: 1};
                var text = this.text();
                var value = this.value();

                console.log("Text choosen:", text);
                console.log("Value choosen:", value);

                // get selection and range
                var selection = editor.selection;
                var rng = selection.getRng();

                editor.focus();
            },
            values: my_options,
            onPostRender: function() {
                ed.my_control = this; // ui control element
            }
        });

  //       editor.addButton('pushortcodes', {
  //           type: 'listbox',
  //           text: 'Shortcodes',
  //           onselect: function(e) {
  //               console.log(e);
  //               var v = e.control._value;
		// if( v == 'ac_show_youtube_video' ) {
  //               	tinyMCE.activeEditor.selection.setContent( '[' + v + ' width="420" height="315"][/' + v + ']' );
		// } else {
		// 	tinyMCE.activeEditor.selection.setContent( '[' + v + '][/' + v + ']' );
		// }
  //           },
  //           values: shortcodeValues
  //       });
    });
})();
