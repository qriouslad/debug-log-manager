<?php

namespace DLM\Classes;

/**
 * Plugin Deactivation
 *
 * @since 1.0.0
 */
class Deactivation {

	/**
	 * Code that runs on plugin deactivation
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {

		delete_option( 'debug_log_manager' );
		delete_option( 'debug_log_manager_autorefresh' );
		delete_option( 'debug_log_manager_file_path' );
		delete_option( 'debug_log_manager_js_error_logging' );
		delete_option( 'debug_log_manager_modify_script_debug' );
		delete_option( 'debug_log_manager_process_non_utc_timezones' );

	}

}