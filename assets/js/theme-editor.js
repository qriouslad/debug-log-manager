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

		$('.wrap').prepend('<div class="fileedit-title"><h1>View Themes</h1><button id="toggle-editing" class="button button-small button-secondary">Enable Editing</button></div>');

		// Theme selector label
		$('.fileedit-sub label#theme-plugin-editor-selector').text($('.fileedit-sub label#theme-plugin-editor-selector').text().replace("edit", "view"));

		$('.submit').hide();

	}

	function enableEditMode() {

		$('.fileedit-title').remove();
		$('.wrap').prepend('<div class="fileedit-title"><h1>Edit Themes</h1></div>');
		$('.submit').show();
	}

})( jQuery );