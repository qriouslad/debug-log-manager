<?php

namespace DLM\Classes;

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
		$date_time 	= wp_date( 'M j, Y - H:i:s', strtotime( $value['on'] ) );

		return '<div id="debug-log-status" class="dlm-log-status"><strong>Error Logging</strong>:  '. esc_html( ucfirst( $status ) ) .' on '. esc_html( $date_time ) .'<div id="dlm-log-toggle-hint"></div></div>';

	}

	/**
	 * Get log auto-refresh status
	 *
	 * @since 1.3.0
	 */
	public function get_autorefresh_status() {

		if ( false !== get_option( 'debug_log_manager_autorefresh' ) ) {

			$autorefresh_status = get_option( 'debug_log_manager_autorefresh' );

		} else {

			$autorefresh_status = 'disabled';
	        update_option( 'debug_log_manager_autorefresh', $autorefresh_status, false );

		}

		return '<div id="debug-autorefresh-status" class="dlm-autorefresh-status"><strong>Auto-Refresh</strong>: ' . ucfirst( $autorefresh_status ) . '</div>';

	}

	/**
	 * Enable / disable WP_DEBUG
	 *
	 * @since 1.0.0
	 */
	public function toggle_debugging() {

		if ( isset( $_REQUEST ) ) {

			$log_info 				= get_option( 'debug_log_manager' );
	        $dlm_debug_log_file_path 	= get_option( 'debug_log_manager_file_path' );

			$date_time 				= wp_date( 'M j, Y - H:i:s' ); // Localized according to WP timezone settings
			$date_time_for_option 	= date( 'M j, Y H:i:s' ); // in UTC

			if ( 'disabled' == $log_info['status']  ) {

				$option_value = array(
					'status'	=> 'enabled',
					'on'		=> $date_time_for_option,
				);

				update_option( 'debug_log_manager', $option_value, false );

				// If WP_DEBUG_LOG is defined, copy content of existing debug.log file into the log file created by this plugin

				if ( $this->wp_config->exists( 'constant', 'WP_DEBUG_LOG' ) ) {

					$wp_debug_log_const = $this->wp_config->get_value( 'constant', 'WP_DEBUG_LOG' );

					if ( in_array( $wp_debug_log_const, array( 'true', 'false' ), true ) ) {
						$wp_debug_log_const = (bool) $wp_debug_log_const;
					}

					if ( is_bool( $wp_debug_log_const ) ) {
						// WP_DEBUG_LOG is true or false. Log file is in default location of /wp-content/debug.log. 

						if ( is_file( WP_CONTENT_DIR . '/debug.log' ) ) {
							// Copy existing debug log content to this plugin's debug log.
							$default_debug_log_content = file_get_contents( WP_CONTENT_DIR . '/debug.log' );
							file_put_contents( $dlm_debug_log_file_path, $default_debug_log_content );
							unlink( realpath( WP_CONTENT_DIR . '/debug.log' ) ); // delete existing debug log
						}

					} elseif ( is_string( $wp_debug_log_const ) ) {
						// WP_DEBUG_LOG is custom path to log file. Copy existing debug log content to this plugin's debug log.

						if ( is_file( $wp_debug_log_const ) && ( $wp_debug_log_const != $dlm_debug_log_file_path ) ) {
							$custom_debug_log_content = file_get_contents( $wp_debug_log_const );
							file_put_contents( $dlm_debug_log_file_path, $custom_debug_log_content );
							unlink( $wp_debug_log_const ); // delete existing debug log
						}

					}

					$copy = true; // existing debug.log file's entries are copied to this plugin's debug.log file

				} else {

					$copy = false; // existing debug.log file's entries are NOT copied to this plugin's debug.log file

				}

				// Prepare entries from the debug log for the data table

				$errors_master_list = json_decode( $this->get_processed_entries(), true );

				$n = 1;
				$entries = array();

				foreach ( $errors_master_list as $error ) {

					$localized_timestamp 	= wp_date( 'M j, Y - H:i:s', strtotime( $error['occurrences'][0] ) ); // last occurrence
					$occurrence_count 		= count( $error['occurrences'] );

					$entry = array( 
							$n, 
							$error['type'], 
							$error['details'], 
							$localized_timestamp . '<br /><span class="dlm-faint">(' . $occurrence_count . ' occurrences logged)<span>',
					);

					$entries[] = $entry;

					$n++;

				}

				// Define Debug constants in wp-config.php

				$options = array(
					'add'       => true, // Add the config if missing.
					'raw'       => true, // Display value in raw format without quotes.
					'normalize' => false, // Normalize config output using WP Coding Standards.
				);

				$this->wp_config->update( 'constant', 'WP_DEBUG', 'true', $options );

				$options = array(
					'add'       => true, // Add the config if missing.
					'raw'       => false, // Display value in raw format without quotes.
					'normalize' => false, // Normalize config output using WP Coding Standards.
				);

				$this->wp_config->update( 'constant', 'WP_DEBUG_LOG', get_option( 'debug_log_manager_file_path' ), $options );

				$options = array(
					'add'       => true, // Add the config if missing.
					'raw'       => true, // Display value in raw format without quotes.
					'normalize' => false, // Normalize config output using WP Coding Standards.
				);

				$this->wp_config->update( 'constant', 'WP_DEBUG_DISPLAY', 'false', $options );

				// Get the debug.log file size

				$log_file_path 		= get_option( 'debug_log_manager_file_path' );
				$log_file_shortpath = str_replace( sanitize_text_field( $_SERVER['DOCUMENT_ROOT'] ), "", $log_file_path );
				$file_size 			= size_format( (int) filesize( $log_file_path ) );

				// Assemble data to return

				$data = array(
					'status'	=> 'enabled',
					'copy'		=> $copy,
					'message' 	=> '<strong>Error Logging</strong>: Enabled on ' . esc_html( $date_time ),
					'entries'	=> $entries,
					'size'		=> $file_size,
				);

				echo json_encode( $data );

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

				// Assemble data to return

				$data = array(
					'status'	=> 'disabled',
					'copy'		=> false,
					'message' 	=> '<strong>Error Logging</strong>: Disabled on ' . esc_html( $date_time ),
					'entries'	=> '',
					'size'		=> '',
				);

				echo json_encode( $data );

			} else {}

		}

	}

	/**
	 * Toggle auto-refresh of entries table
	 *
	 * @since 1.3.0
	 */
	public function toggle_autorefresh() {

		if ( isset( $_REQUEST ) ) {

			$autorefresh_status = get_option( 'debug_log_manager_autorefresh' );

			if ( $autorefresh_status == 'disabled' ) {

		        update_option( 'debug_log_manager_autorefresh', 'enabled', false );

				$data = array(
					'status'	=> 'enabled',
					'message' 	=> '<strong>Auto-Refresh</strong>: Enabled',
				);

				echo json_encode( $data );

			} elseif ( $autorefresh_status == 'enabled' ) {

		        update_option( 'debug_log_manager_autorefresh', 'disabled', false );

				$data = array(
					'status'	=> 'disabled',
					'message' 	=> '<strong>Auto-Refresh</strong>: Disabled',
				);

				echo json_encode( $data );

			} else {}

		}

	}

	/**
	 * Get the processed debug log data
	 *
	 * @return string $errors_master_list The processed error log entries
	 * @since 1.2.0
	 */
	public function get_processed_entries() {

        $debug_log_file_path = get_option( 'debug_log_manager_file_path' );

        // Read the erros log file, reverse the order of the entries, prune to the latest 5000 entries
        $log 	= file_get_contents( $debug_log_file_path );
        $log 	= str_replace( "[\\", "^\\", $log ); // certain error message contains the '[\' string, which will make the following split via explode() to split lines at places in the message it's not supposed to. So, we temporarily replace those with '^\'
        $log = str_replace( "[internal function]", "^internal function^", $log );
        $lines 	= explode("[", $log);

        // Put back the missing '[' after explode operation
        $prepended_lines = array();
        foreach ( $lines as $line ) {
        	if ( !empty($line) ) {
        		$line 				= str_replace( "UTC]", "UTC]@@@", $line ); // add '@@@' as marker/separator after time stamp
        		$line 				= str_replace( "Stack trace:", "<hr />Stack trace:", $line ); // add line break for stack trace section
				if ( strpos( $line, 'PHP Fatal' ) !== false ) {
	        		$line 			= str_replace( "#", "<hr />#", $line ); // add line break on PHP Fatal error's stack trace lines
	        	}
        		$line 			= str_replace( "Argument <hr />#", "Argument #", $line ); // remove hr on certain error message
        		$line 			= str_replace( "parameter <hr />#", "parameter #", $line ); // remove hr on certain error message
        		$line 			= str_replace( "the <hr />#", "the #", $line ); // remove hr on certain error message
        		$line 			= str_replace( "^\\", "[\\", $line ); // reverse the temporary replacement of '[\' with '^\'
        		$line = str_replace( "^internal function^", "[internal function]", $line );
	        	$prepended_line 	= '[' . $line;
	        	$prepended_lines[] 	= $prepended_line;
        	}
        }

        $lines_newest_first 	= array_reverse( $prepended_lines );
        $latest_lines 			= array_slice( $lines_newest_first, 0, 50000 );

        // Will hold error details types
        $errors_master_list = array();

		foreach( $latest_lines as $line ) {

			$line = explode("@@@ ", $line); // split the line using the '@@@' marker/separator defined earlier

			$timestamp = str_replace( [ "[", "]" ], "", $line[0] );
			if ( array_key_exists('1', $line) ) {
				$error = $line[1];
			} else {
				$error = 'No error message specified...';
			}

			if ( strpos( $error, 'PHP Fatal' ) !== false ) {
				$error_type 	= 'PHP Fatal';
				$error_details 	= str_replace( "PHP Fatal error: ", "", $error );
				$error_details 	= str_replace( "PHP Fatal: ", "", $error_details );
			} elseif ( strpos( $error, 'PHP Warning' ) !== false ) {
				$error_type 	= 'PHP Warning';
				$error_details 	= str_replace( "PHP Warning: ", "", $error );
			} elseif ( strpos( $error, 'PHP Notice' ) !== false ) {
				$error_type 	= 'PHP Notice';
				$error_details 	= str_replace( "PHP Notice: ", "", $error );
			} elseif ( strpos( $error, 'PHP Deprecated' ) !== false ) {
				$error_type 	= 'PHP Deprecated';
				$error_details 	= str_replace( "PHP Deprecated: ", "", $error );
			} elseif ( strpos( $error, 'PHP Parse' ) !== false ) {
				$error_type 	= 'PHP Parse';
				$error_details 	= str_replace( "PHP Parse error: ", "", $error );
			} elseif ( strpos( $error, 'WordPress database error' ) !== false ) {
				$error_type 	= 'Database';
				$error_details 	= str_replace( "WordPress database error ", "", $error );
			} elseif ( strpos( $error, 'JavaScript Error' ) !== false ) {
				$error_type 	= 'JavaScript';
				$error_details 	= str_replace( "JavaScript Error: ", "", $error );
			} else {
				$error_type 	= 'Other';
				$error_details 	= $error;
			}

			// https://www.php.net/manual/en/function.array-search.php#120784
			if ( array_search( trim( $error_details ), array_column( $errors_master_list, 'details' ) ) === false ) {

				$errors_master_list[] = array(
					'type'			=> $error_type,
					'details'		=> trim( $error_details ),
					'occurrences'	=> array( $timestamp ),
				);

			} else {

				$error_position = array_search( trim( $error_details ), array_column( $errors_master_list, 'details' ) ); // integer

				array_push( $errors_master_list[$error_position]['occurrences'], $timestamp );

			}

		}

		return json_encode( $errors_master_list );

	}

	/**
	 * Get the latest entries for auto-refresh feature
	 *
	 * @since 1.0.0
	 */
	public function get_latest_entries() {

		$errors_master_list = json_decode( $this->get_processed_entries(), true );

		$n = 1;
		$entries = array();

		foreach ( $errors_master_list as $error ) {

			$localized_timestamp 	= wp_date( 'M j, Y - H:i:s', strtotime( $error['occurrences'][0] ) ); // last occurrence
			$occurrence_count 		= count( $error['occurrences'] );

			$entry = array( 
					$n, 
					$error['type'], 
					$error['details'], 
					$localized_timestamp . '<br /><span class="dlm-faint">(' . $occurrence_count . ' occurrences logged)<span>',
			);

			$entries[] = $entry;

			$n++;

		}

		$data = array(
			'entries'	=> $entries,
		);

		echo json_encode( $data );

	}

	/**
	 * Get debug log in data table format
	 *
	 * @since 1.0.0
	 */
	public function get_entries_datatable() {

		?>
		<div>
			<select id="errorTypeFilter" class="dlm-error-type-filter">
				<option value="">All Error Types</option>
				<option value="PHP Fatal">PHP Fatal</option>
				<option value="PHP Warning">PHP Warning</option>
				<option value="PHP Notice">PHP Notice</option>
				<option value="PHP Deprecated">PHP Deprecated</option>
				<option value="PHP Parse">PHP Parse</option>
				<option value="Database">Database</option>
				<option value="JavaScript">JavaScript</option>
				<option value="Other">Other</option>				
			</select>
		</div>
		<table id="debug-log" class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th class="debug-log-number">#</th>
					<th class="debug-log-error-type">Error Type</th>
					<th class="debug-log-error-details">Details</th>
					<th class="debug-log-timestamp">Last Occurrence</th>
				</tr>
			</thead>
			<tbody>
		<?php

		$errors_master_list = json_decode( $this->get_processed_entries(), true );

		$n = 1;

		foreach ( $errors_master_list as $error ) {

			$localized_timestamp 	= wp_date( 'M j, Y - H:i:s', strtotime( $error['occurrences'][0] ) ); // last occurrence
			$occurrence_count 		= count( $error['occurrences'] );
			?>

			<tr>
				<td><?php echo esc_html( $n ); ?></td>
				<td><?php echo esc_html( $error['type'] ); ?></td>
				<td><?php echo wp_kses( $error['details'], 'post' ); ?></td>
				<td><?php echo esc_html( $localized_timestamp ); ?><br /><span class="dlm-faint">(<?php echo esc_html( $occurrence_count ); ?> occurrences logged)<span></td>
			</tr>

			<?php
			$n++;

		}

		?>
			</tbody>
		</table>
		<?php

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

	/**
	 * Log javascript errors
	 *
	 * @since 1.4.0
	 */
	public function log_js_errors() {

		// Since we are using XHR for the js error logging, JSON data comes in via php://input
		$request = json_decode(urldecode(file_get_contents('php://input')), true); // an array

		// Verify error content and nonce and then log the JS error
		// Source: https://plugins.svn.wordpress.org/lh-javascript-error-log/trunk/lh-javascript-error-log.php
		if ( isset( $request['message'] ) && isset( $request['script'] ) && isset( $request['lineNo'] ) && isset( $request['columnNo'] ) && ! empty( $request['nonce'] ) && wp_verify_nonce( $request['nonce'], DLM_SLUG ) ) {

				error_log( 'JavaScript Error: ' . $request['message'] . ' in ' . $request['script'] . ' on line ' . $request['lineNo'] . ' column ' . $request['columnNo'] . ' at ' . get_site_url() . $request['pageUrl'] );

		} else {

			wp_die();

		}

	}

}