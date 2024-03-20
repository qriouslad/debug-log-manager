<?php

namespace DLM\Classes;

/**
 * Plugin Activation
 *
 * @since 1.0.0
 */
class Activation {

	/**
	 * Code that runs on plugin activation
	 *
	 * @since 1.0.0
	 */
	public function activate() {

		// Create option to store logger status

        $option_value = array(
            'status'    => 'disabled',
            'on'        => date( 'Y-m-d H:i:s' ),
        );

        update_option( 'debug_log_manager', $option_value, false );

        // Create option to store auto-refresh feature status

        $autorefresh_status = 'disabled';

        update_option( 'debug_log_manager_autorefresh', $autorefresh_status, false );

        // Create debug.log file in custom location for use in WP_DEBUG_LOG constant
        
        $uploads_path = wp_upload_dir()['basedir'] . '/debug-log-manager';

        $plain_domain = str_replace( array( ".", "-" ), "", sanitize_text_field( $_SERVER['SERVER_NAME'] ) ); // e.g. wwwgooglecom

        $unique_key = date( 'YmdHi' ) . rand(12345678, 87654321);

        $debug_log_file_path = $uploads_path . '/' . $plain_domain . '_' . $unique_key .'_debug.log';

        $debug_log_file_path_in_option = get_option( 'debug_log_manager_file_path' );

        if ( false === $debug_log_file_path_in_option 
            || false === strpos( $debug_log_file_path_in_option, ABSPATH )
        ) {

	        update_option( 'debug_log_manager_file_path', $debug_log_file_path, false );

	        $debug_log_file_path_in_option = get_option( 'debug_log_manager_file_path' );

        }

        if ( ! is_dir( $uploads_path ) ) {
            mkdir( $uploads_path ); // create directory in /uploads folder
            // create empty index to prevent directory browsing and download of the debug log file
            file_put_contents( $uploads_path . '/index.php', '<?php // Nothing to show here' );           
        }
        
        if ( ! is_file( $debug_log_file_path_in_option ) ) {
            file_put_contents( $debug_log_file_path_in_option, '' ); // create empty log file
        } else {}        

	}

}