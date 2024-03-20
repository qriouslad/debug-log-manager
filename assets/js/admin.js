(function( $ ) {
	'use strict';

	let autoRefreshIntervalId; // variable to store auto refresh interval ID

	$(document).ready( function() {

		// Make page header sticky on scroll. Using https://github.com/AndrewHenderson/jSticky
		
		$('#dlm-header').sticky({
			topSpacing: 0, // Space between element and top of the viewport (in pixels)
			zIndex: 100, // z-index
			stopper: '', // Id, class, or number value
			stickyClass: 'dlm-sticky' // Class applied to element when it's stuck. Class name or false.
		})

		// Get WP_DEBUG logging status toggle/switcher position on page load

		var logStatus = dlmVars.logStatus;
		$('.debug-log-switcher').attr('data-status',logStatus);

		if ( logStatus == 'enabled' ) {
			$('.debug-log-checkbox').prop('checked', true);
			$('#dlm-disable-wp-file-editor-section').fadeOut();
		} else {
			$('.debug-log-checkbox').prop('checked', false);					
			$('#dlm-disable-wp-file-editor-section').fadeIn();
		}

		// Get auto-refresh feature status on page load

		var autorefreshStatus = dlmVars.autorefreshStatus;

		$('.debug-autorefresh-switcher').attr('data-status',autorefreshStatus);

		if ( autorefreshStatus == 'enabled' ) {
			$('.debug-autorefresh-checkbox').prop('checked', true);
			if ( logStatus == 'enabled' ) {
				autoRefreshIntervalId = setInterval(getLatestEntries, 5000);
			} else {
				clearInterval(autoRefreshIntervalId);
			}
		} else {
			$('.debug-autorefresh-checkbox').prop('checked', false);
			clearInterval(autoRefreshIntervalId);				
		}

		// Toggle WP_DEBUG logging status on click

		$('.debug-log-switcher').click( function() {

			var autorefreshStatus = $('.debug-autorefresh-switcher')[0].dataset.status;

			$.ajax({
				url: ajaxurl,
				data: {
					'action': 'toggle_debugging',
					'nonce': dlmVars.nonce
				},
				success:function(data) {
					var data = data.slice(0,-1); // remove strange trailing zero in string returned by AJAX call
					const dataObject = JSON.parse(data); // create an object
					// console.log(dataObject);
					$('#debug-log-status').empty();
					$('#debug-log-status').prepend(dataObject.message);
					// console.log('WP_DEBUG: ' + dataObject.status)

					if ( dataObject.status == 'enabled' ) {
						$('.debug-log-switcher').attr('data-status','enabled');
						getLatestEntries();
						$('#dlm-disable-wp-file-editor-section').fadeOut();
						if ( dataObject.copy == false ) {
							$.toast({
								// heading: 'Success!',
								text: dlmVars.toastMessage.toggleDebugSuccess,
								showHideTransition: 'slide',
								icon: 'success',
								allowToastClose: true,
								hideAfter: 7500, // true, false or number (miliseconds)
								position: 'bottom-right',
								bgColor: '#52a552',
								textColor: '#ffffff'
							});
						}
						if ( autorefreshStatus == 'enabled' ) {
							autoRefreshIntervalId = setInterval(getLatestEntries, 5000);
						} else {
							clearInterval(autoRefreshIntervalId);
						}
					} else if ( dataObject.status == 'disabled' ) {
						$('.debug-log-switcher').attr('data-status','disabled');
						$('#dlm-disable-wp-file-editor-section').fadeIn();
						clearInterval(autoRefreshIntervalId);
						if ( autorefreshStatus == 'enabled' ) {
							$('.debug-autorefresh-switcher').click();
						}
					} else {}

					if ( dataObject.copy == true ) {
						// When entries are copied from an existing debug.log file
						$.toast({
							// heading: 'Success!',
							text: dlmVars.toastMessage.copySuccess,
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

			var logStatus = $('.debug-log-switcher')[0].dataset.status;

			$.ajax({
				url: ajaxurl,
				data: {
					'action': 'toggle_autorefresh',
					'nonce': dlmVars.nonce
				},
				success:function(data) {
					var data = data.slice(0,-1); // remove strange trailing zero in string returned by AJAX call
					const dataObject = JSON.parse(data); // create an object
					$('#debug-autorefresh-status').empty();
					$('#debug-autorefresh-status').prepend(dataObject.message);
					// console.log('Auto refresh: ' + dataObject.status)
					if ( dataObject.status == 'enabled' ) {
						$('.debug-autorefresh-switcher').attr('data-status','enabled');
						if ( logStatus == 'enabled' ) {
							autoRefreshIntervalId = setInterval(getLatestEntries, 5000); // every 5 seconds
						} else {
							clearInterval(autoRefreshIntervalId);
						}
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
					'action': 'clear_log',
					'nonce': dlmVars.nonce
				},
				success:function() {
					var table = $("#debug-log").DataTable();
					table.clear().draw();
					$('#dlm-log-file-size').empty();
					$('#dlm-log-file-size').prepend('0 B');
					$.toast({
						// heading: 'Success!',
						text: dlmVars.toastMessage.logFileCleared,
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

		// Disable WP core's plugin/theme editor
		
		$('#dlm-disable-wp-file-editor').click( function() {

			$.ajax({
				url: ajaxurl,
				data: {
					'action': 'disable_wp_file_editor',
					'nonce': dlmVars.nonce
				},
				success:function(data) {
					var data = data.slice(0,-1); // remove strange trailing zero in string returned by AJAX call
					const dataObject = JSON.parse(data); // create an object
					if ( dataObject.status == 'disabled' ) { // plugin/theme editor has been disabled
						// Redraw table with new data: https://stackoverflow.com/a/25929434
						getLatestEntries();
						$.toast({
							// heading: 'Success!',
							text: dlmVars.toastMessage.editoDisabled,
							showHideTransition: 'slide',
							icon: 'success',
							allowToastClose: true,
							hideAfter: 7500, // true, false or number (miliseconds)
							position: 'bottom-right',
							bgColor: '#52a552',
							textColor: '#ffffff'
						});
					}
				},
				error:function(errorThrown) {
					console.log(errorThrown);
				}
			});
		});

		// Initialize log entries dataTable with localization enabled
		// https://datatables.net/manual/i18n
		// https://datatables.net/plug-ins/i18n/
		// https://datatables.net/plug-ins/i18n/English.html

		$("#debug-log").DataTable({
			pageLength: 10,
			order: [ 0, "asc" ],
			searching: true,
			language: {
				emptyTable: dlmVars.dataTable.emptyTable,
				info: dlmVars.dataTable.info,
				infoEmpty: dlmVars.dataTable.infoEmpty,
				infoFiltered: dlmVars.dataTable.infoFiltered,
				lengthMenu: dlmVars.dataTable.lengthMenu,
				search: dlmVars.dataTable.search,
				zeroRecords: dlmVars.dataTable.zeroRecords,
				paginate: {
				    first: dlmVars.dataTable.paginate.first,
				    last: dlmVars.dataTable.paginate.last,
				    next: dlmVars.dataTable.paginate.next,
				    previous: dlmVars.dataTable.paginate.previous
				},
			}
		});
		
		// Create Error Type filter drop down
		// https://clintmcmahon.com/add-a-custom-search-filter-to-datatables-header/

		var debugLogTable = $("#debug-log").DataTable();
		$("#debug-log_filter.dataTables_filter").append($("#errorTypeFilter"));

		var errorTypeIndex = 0;

		$("#debug-log th").each(function (i) {
			if ( $($(this)).html() == "Error Type" ) {
				errorTypeIndex = i;
				return false;
			}
		});

		$.fn.dataTable.ext.search.push(
			function (settings, data, dataIndex) {
				var selectedItem = $("#errorTypeFilter").val();
				var errorType = data[errorTypeIndex];
				if (selectedItem === "" || errorType.includes(selectedItem)) {
					return true;
				}
				return false;
			}
		);

		$("#errorTypeFilter").change(function (e) {
			debugLogTable.draw();
		});

		debugLogTable.draw();

		// Disable auto-refresh if pagination button is clicked, otherwise it will cause the refresh to return pagination to page 1
		
		$('#debug-log_paginate').click( function() {

			var autorefreshStatus = $('.debug-autorefresh-switcher')[0].dataset.status;

			if ( autorefreshStatus == 'enabled' ) {
				$('.debug-autorefresh-switcher').click();
				$.toast({
					// heading: 'Success!',
					text: dlmVars.toastMessage.paginationActive,
					showHideTransition: 'slide',
					// icon: 'success',
					allowToastClose: false,
					hideAfter: 5000, // true, false or number (miliseconds)
					position: 'bottom-right',
					// bgColor: '#52a552',
					// textColor: '#ffffff'
				});
			}

		});

		// Auto reload page / refresh table

		function getLatestEntries() {
			$.ajax({
				url: ajaxurl,
				data: {
					'action': 'get_latest_entries',
					'nonce': dlmVars.nonce
				},
				success:function(data) {
					var data = data.slice(0,-1); // remove strange trailing zero in string returned by AJAX call
					const dataObject = JSON.parse(data); // create an object
					// Redraw table with new data: https://stackoverflow.com/a/25929434
					var table = $("#debug-log").DataTable();
					table.clear().rows.add(dataObject.entries); 
					table.columns.adjust().draw();
					$("#debug-log").css("width","100%"); // prevent strange table width shrinkage issue
					$("#debug-log .dlm-entry-no").css("width","16px"); // prevent strange table width shrinkage issue
					$("#debug-log .dlm-entry-type").css("width","96px"); // prevent strange table width shrinkage issue
					$("#debug-log .dlm-entry-datetime").css("width","160px"); // prevent strange table width shrinkage issue
					$("#debug-log .dlm-entry-details").css("width","calc(100% - 16px - 96px - 160px)"); // prevent strange table width shrinkage issue
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