(function( $ ) {
	'use strict';

	// if WP_DEBUG is enabled
	
	if ( dlmVars.logStatus == 'enabled' ) {

		// Log javascript errors in the front end via XHR https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest
		// Code source: https://plugins.svn.wordpress.org/javascript-error-reporting-client/tags/1.0.3/public/js/jerc.js
		
		window.onerror = function(msg, url, lineNo, columnNo, error) {

			var data = {
				nonce: dlmVars.jsErrorLogging.nonce,
				message: msg,
				script: url,
				lineNo: lineNo,
				columnNo: columnNo,
				pageUrl: window.location.pathname + window.location.search,
				type: 'front end'
			}

			var xhr = new XMLHttpRequest();
			xhr.open("POST", dlmVars.jsErrorLogging.url + "?action=" + dlmVars.jsErrorLogging.action );
			xhr.setRequestHeader('Content-type', 'application/json');
			xhr.send(encodeURI(JSON.stringify(data)));
			return false;

		}

	}

})( jQuery );