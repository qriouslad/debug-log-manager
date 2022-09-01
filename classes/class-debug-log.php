<?php

namespace DLM\Classes;

require_once DLM_PATH . 'classes/class-wp-config-transformer.php'; // DLM\Classes\WP_Config

/**
 * Class related to the debug log file and entries
 *
 * @since 1.0.0
 */
class Debug_Log {

	// The wp_config object
	private $wp_config;

	/**
	 * Class constructor
	 */
	public function __construct() {

		$this->wp_config = new WP_Config_Transformer; // already in the DLM\Classes namespace, so, no need to repeat

	}

	/**
	 * Get status of WP_DEBUG
	 *
	 * @since 1.0.0
	 */
	public function get_status() {

		$value 		= get_option( 'debug_log_manager' );
		$status 	= $value['status'];
		$date_time 	= wp_date( 'j-M-Y - H:i:s', strtotime( $value['on'] ) );

		return '<div id="debug-log-status" class="dlm-log-status"><strong>Status</strong>: Logging was '. esc_html( $status ) .' on '. esc_html( $date_time ) .'<div id="dlm-log-toggle-hint"></div></div>';

	}

	/**
	 * Enable / disable WP_DEBUG
	 *
	 * @since 1.0.0
	 */
	public function toggle_debugging() {

		if ( isset( $_REQUEST ) ) {

			$log_info 				= get_option( 'debug_log_manager' );

			$date_time 				= wp_date( 'j-M-Y - H:i:s' ); // Localized according to WP timezone settings
			$date_time_for_option 	= date( 'j-M-Y H:i:s' ); // in UTC

			if ( 'disabled' == $log_info['status']  ) {

				$option_value = array(
					'status'	=> 'enabled',
					'on'		=> $date_time_for_option,
				);

				update_option( 'debug_log_manager', $option_value, false );

				// Define Debug constants in wp-config.php

				$this->wp_config->update( 'constant', 'WP_DEBUG', 'true' );
				$this->wp_config->update( 'constant', 'WP_DEBUG_LOG', get_option( 'debug_log_manager_file_path' ) );
				$this->wp_config->update( 'constant', 'WP_DEBUG_DISPLAY', 'false' );

				$output = '<strong>Status</strong>: Logging was enabled on ' . esc_html( $date_time );

			} elseif ( 'enabled' == $log_info['status'] ) {

				$option_value = array(
					'status'	=> 'disabled',
					'on'		=> $date_time_for_option,
				);

				update_option( 'debug_log_manager', $option_value, false );

				// Remove Debug constants in wp-config.php

				$this->wp_config->remove( 'constant', 'WP_DEBUG' );
				$this->wp_config->remove( 'constant', 'WP_DEBUG_LOG' );
				$this->wp_config->remove( 'constant', 'WP_DEBUG_DISPLAY' );

				$output = '<strong>Status</strong>: Logging was disabled on ' . esc_html( $date_time );

			} else {}

			echo $output;

		}

	}

	/**
	 * Get debug log in data table format
	 *
	 * @since 1.0.0
	 */
	public function get_entries() {

		$output = '';

		$output .= '<div class="dlm-log-management"><div class="dlm-log-status-toggle"><input type="checkbox" id="debug-log-checkbox" class="inset-3 debug-log-checkbox"><label for="debug-log-checkbox" class="green debug-log-switcher"></label></div>' . $this->get_status() . '</div>';

		$output .= '<table id="debug-log" class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th class="debug-log-number">#</th>
							<th class="debug-log-error-type">Error Type</th>
							<th class="debug-log-error-details">Details</th>
							<th class="debug-log-timestamp">Last Occurrence</th>
						</tr>
					</thead>
					<tbody>';

        $debug_log_file_path = get_option( 'debug_log_manager_file_path' );

        // Read the erros log file, reverse the order of the entries, prune to the latest 5000 entries
        $log 	= file_get_contents( $debug_log_file_path );
        $lines 	= explode("[", $log);

        // Put back the missing '[' after explode operation
        $prepended_lines = array();
        foreach ( $lines as $line ) {
        	if ( !empty($line) ) {
        		$line 				= str_replace( "]", "]@@@", $line ); // add line break after time stamp
        		$line 				= str_replace( "Stack trace:", "<hr />Stack trace:", $line ); // add line break for stack trace section
        		$line 				= str_replace( "#", "<hr />#", $line ); // add line break on stack trace lines
        		$line 				= str_replace( "Argument <hr />#", "Argument #", $line ); // add line break on stack trace lines
	        	$prepended_line 	= '[' . $line;
	        	$prepended_lines[] 	= $prepended_line;
        	}
        }

        $lines_newest_first 	= array_reverse( $prepended_lines );
        $latest_lines 			= array_slice( $lines_newest_first, 0, 50000 );

        // Will hold error details types
        $errors_master_list = array();

		foreach( $latest_lines as $line ) {

			$line = explode("@@@ ", $line);

			$timestamp = str_replace( [ "[", "]" ], "", $line[0] );
			$error = $line[1];

			if ( strpos( $error, 'PHP Fatal' ) !==false ) {
				$error_type 	= 'PHP Fatal';
				$error_details 	= str_replace( "PHP Fatal: ", "", $error );
			} elseif ( strpos( $error, 'PHP Warning' ) !==false ) {
				$error_type 	= 'PHP Warning';
				$error_details 	= str_replace( "PHP Warning: ", "", $error );
			} elseif ( strpos( $error, 'PHP Notice' ) !==false ) {
				$error_type 	= 'PHP Notice';
				$error_details 	= str_replace( "PHP Notice: ", "", $error );
			} elseif ( strpos( $error, 'PHP Deprecated' ) !==false ) {
				$error_type 	= 'PHP Deprecated';
				$error_details 	= str_replace( "PHP Deprecated: ", "", $error );
			} elseif ( strpos( $error, 'WordPress database error' ) !==false ) {
				$error_type 	= 'WP DB error';
				$error_details 	= str_replace( "WordPress database error ", "", $error );
			} else {
				$error_type 	= 'Other';
				$error_details 	= $error;
			}

			// https://www.php.net/manual/en/function.array-search.php#120784
			if ( array_search( trim( $error_details ), array_column( $errors_master_list, 'details' ) ) === false ) {

				$errors_master_list[] = array(
					'occurrences'	=> array( $timestamp ),
					'type'			=> $error_type,
					'details'		=> trim( $error_details ),
				);

			} else {

				$error_position = array_search( trim( $error_details ), array_column( $errors_master_list, 'details' ) ); // integer

				array_push( $errors_master_list[$error_position]['occurrences'], $timestamp );

			}

		}

		$n = 1;

		foreach ( $errors_master_list as $error ) {

			$localized_timestamp 	= wp_date( 'j-M-Y - H:i:s', strtotime( $error['occurrences'][0] ) ); // last occurrence
			$occurrence_count 		= count( $error['occurrences'] );

			$output .= '<tr>
							<td>'. esc_html( $n ) .'</td>
							<td>'. esc_html( $error['type'] ) .'</td>
							<td>'. $error['details'] .'</td>
							<td>'. esc_html( $localized_timestamp ) .'<br /><span class="dlm-faint">(' . esc_html( $occurrence_count ) . ' occurrences logged)<span></td>
						</tr>';

			$n++;

		}

		$output .= '</tbody></table>';

		echo $output;

	}

	/**
	 * Clear log file
	 *
	 * @since 1.0.0
	 */
	public function clear_log() {

        $debug_log_file_path = get_option( 'debug_log_manager_file_path' );

		file_put_contents( $debug_log_file_path, '' );

		echo true;

	}

}