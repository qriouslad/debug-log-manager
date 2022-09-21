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

		// Get WP_DEBUG logging status toggle/switcher position on page load

		var logStatus = dlmVars.logStatus;
		$('.debug-log-switcher').attr('data-status',logStatus);

		if ( logStatus == 'enabled' ) {
			$('.debug-log-checkbox').prop('checked', true);
		} else {
			$('.debug-log-checkbox').prop('checked', false);					
		}

		// Get auto-refresh feature status on page load

		var autorefreshStatus = dlmVars.autorefreshStatus;
		let autoRefreshIntervalId; // variable to store auto refresh interval ID

		$('.debug-autorefresh-switcher').attr('data-status',autorefreshStatus);

		if ( autorefreshStatus == 'enabled' ) {
			$('.debug-autorefresh-checkbox').prop('checked', true);
			const autoRefresh = setInterval(getLatestEntries, 5000);
		} else {
			$('.debug-autorefresh-checkbox').prop('checked', false);					
		}

		// Toggle WP_DEBUG logging status on click

		$('.debug-log-switcher').click( function() {

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

					if ( dataObject.status == 'enabled' ) {
						$('.debug-log-switcher').attr('data-status','enabled');
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
					} else if ( dataObject.status == 'disabled' ) {
						$('.debug-log-switcher').attr('data-status','disabled');								
					} else {}

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

		// Toggle auto-refresh feature on click

		$('.debug-autorefresh-switcher').click( function() {

			$.ajax({
				url: ajaxurl,
				data: {
					'action': 'toggle_autorefresh'
				},
				success:function(data) {
					var data = data.slice(0,-1); // remove strange trailing zero in string returned by AJAX call
					const dataObject = JSON.parse(data); // create an object
					$('#debug-autorefresh-status').empty();
					$('#debug-autorefresh-status').prepend(dataObject.message);
					if ( dataObject.status == 'enabled' ) {
						$('.debug-autorefresh-switcher').attr('data-status','enabled');
						autoRefreshIntervalId = setInterval(getLatestEntries, 5000); // every 5 seconds
					} else if ( dataObject.status == 'disabled' ) {
						$('.debug-autorefresh-switcher').attr('data-status','disabled');
						clearInterval(autoRefreshIntervalId);
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

		// Auto reload page / refresh table

		function getLatestEntries() {
			$.ajax({
				url: ajaxurl,
				data: {
					'action': 'get_latest_entries'
				},
				success:function(data) {
					var data = data.slice(0,-1); // remove strange trailing zero in string returned by AJAX call
					const dataObject = JSON.parse(data); // create an object
					var table = $("#debug-log").DataTable();
					table.clear().rows.add(dataObject.entries); 
					table.columns.adjust().draw();
					$("#debug-log").css("width","100%"); // prevent strange table width shrinkage issue
				},
				error:function(errorThrown) {
					console.log(errorThrown);
				}
			});	
		}

	}); // end of (document).ready();

	// if WP_DEBUG is enabled

	if ( dlmVars.logStatus == 'enabled' ) {

		// Log javascript errors in wp-admin via XHR https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest
		// Code source: https://plugins.svn.wordpress.org/javascript-error-reporting-client/tags/1.0.3/public/js/jerc.js
		
		window.onerror = function(msg, url, lineNo, columnNo, error) {

			var data = {
				nonce: dlmVars.jsErrorLogging.nonce,
				message: msg,
				script: url,
				lineNo: lineNo,
				columnNo: columnNo,
				pageUrl: window.location.pathname + window.location.search,
				type: 'wp-admin'
			}

			var xhr = new XMLHttpRequest();
			xhr.open("POST", dlmVars.jsErrorLogging.url + "?action=" + dlmVars.jsErrorLogging.action );
			xhr.setRequestHeader('Content-type', 'application/json');
			xhr.send(encodeURI(JSON.stringify(data)));
			return false;

		}

	}

})( jQuery );