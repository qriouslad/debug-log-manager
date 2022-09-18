(function( $ ) {
	'use strict';

	$(document).ready( function() {

		// Make page header sticky on scroll. Using https://github.com/AndrewHenderson/jSticky
		
		$('#dlm-header').sticky({
			topSpacing: 8, // Space between element and top of the viewport (in pixels)
			zIndex: 100, // z-index
			stopper: '', // Id, class, or number value
			stickyClass: 'dlm-sticky' // Class applied to element when it's stuck. Class name or false.
		})

		// Set WP_DEBUG logging status toggle/switcher position on page load

		var log_status = dlmvars.logStatus;
		$('.debug-log-switcher').attr('data-status',log_status);

		if ( log_status == 'enabled' ) {
			$('.debug-log-checkbox').prop('checked', true);
		} else {
			$('.debug-log-checkbox').prop('checked', false);					
		}

		// Toggle WP_DEBUG logging status on click

		$('.debug-log-switcher').click( function() {

			var status = this.dataset.status;

			$.ajax({
				url: ajaxurl,
				data: {
					'action': 'toggle_debugging'
				},
				success:function(data) {
					var data = data.slice(0,-1); // remove strange trailing zero in string returned by AJAX call
					const dataObject = JSON.parse(data); // create an object
					// console.log(dataObject);
					$('#debug-log-status').empty();
					$('#debug-log-status').prepend(dataObject.message);
					if ( status == 'disabled' ) {
						$('.debug-log-switcher').attr('data-status','enabled');
					} else if ( status == 'enabled' ) {
						$('.debug-log-switcher').attr('data-status','disabled');								
					}
					if ( dataObject.status == 'enabled' ) {
						// Redraw table with new data: https://stackoverflow.com/a/25929434
						var table = $("#debug-log").DataTable();
						table.clear().rows.add(dataObject.entries); 
						table.columns.adjust().draw();
						if ( dataObject.copy == false ) {
							$.toast({
								// heading: 'Success!',
								text: 'Error logging has been enabled and latest entries have been loaded.',
								showHideTransition: 'slide',
								icon: 'success',
								allowToastClose: true,
								hideAfter: 7500, // true, false or number (miliseconds)
								position: 'bottom-right',
								bgColor: '#52a552',
								textColor: '#ffffff'
							});
						}
					}
					if ( dataObject.copy == true ) {
						// When entries are copied from an existing debug.log file
						$.toast({
							// heading: 'Success!',
							text: 'Entries have been copied from existing debug.log file.',
							showHideTransition: 'slide',
							icon: 'success',
							allowToastClose: true,
							hideAfter: 7500, // true, false or number (miliseconds)
							position: 'bottom-right',
							bgColor: '#52a552',
							textColor: '#ffffff'
						});
						$('#dlm-log-file-size').empty();
						$('#dlm-log-file-size').prepend(dataObject.size); // fill in debug.log file size
					}
				},
				error:function(errorThrown) {
					console.log(errorThrown);
				}
			});

		});

		// Clear log file
		
		$('#dlm-log-clear').click( function() {

			$.ajax({
				url: ajaxurl,
				data: {
					'action': 'clear_log'
				},
				success:function() {
					var table = $("#debug-log").DataTable();
					table.clear().draw();
					$('#dlm-log-file-size').empty();
					$('#dlm-log-file-size').prepend('0 B')
					$.toast({
						// heading: 'Success!',
						text: 'Log file has been cleared.',
						showHideTransition: 'slide',
						icon: 'success',
						allowToastClose: true,
						hideAfter: 7500, // true, false or number (miliseconds)
						position: 'bottom-right',
						bgColor: '#52a552',
						textColor: '#ffffff'
					});
				},
				error:function(errorThrown) {
					console.log(errorThrown);
				}
			});
		});

		// Initialize log entries dataTable

		$("#debug-log").DataTable({
			pageLength: 10,
			order: [ 0, "asc" ]
		});

	});

})( jQuery );