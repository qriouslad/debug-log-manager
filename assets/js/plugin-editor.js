(function( $ ) {
	'use strict';

	$(document).ready( function() {

		enableViewMode();

		$('#toggle-editing').click(function() {
			enableEditMode();
		});

	}); // end of (document).ready();

	function enableViewMode() {

		$('.wrap h1').remove();
		$('.fileedit-title').remove();

		$('.wrap').prepend('<div class="fileedit-title"><h1>View Plugins</h1><button id="toggle-editing" class="button button-small button-secondary">Enable Editing</button></div>');

		$('.fileedit-sub h2').text($(".fileedit-sub h2").text().replace("Editing", "Viewing"));

		// Plugin selector label
		$(".fileedit-sub label#theme-plugin-editor-selector").text($(".fileedit-sub label#theme-plugin-editor-selector").text().replace("edit", "view"));

		$('.editor-notices').hide();
		$('.submit').hide();

	}

	function enableEditMode() {

		$('.fileedit-title').remove();
		$('.wrap').prepend('<div class="fileedit-title"><h1>Edit Plugins</h1></div>');

		$('.fileedit-sub h2').text($(".fileedit-sub h2").text().replace("Viewing", "Editing"));

		$('.editor-notices').show();
		$('.submit').show();
	}

})( jQuery );