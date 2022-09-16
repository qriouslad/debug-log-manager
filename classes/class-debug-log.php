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
	        $dlm_debug_log_file_path 	= get_option( 'debug_log_manager_file_path' );

			$date_time 				= wp_date( 'j-M-Y - H:i:s' ); // Localized according to WP timezone settings
			$date_time_for_option 	= date( 'j-M-Y H:i:s' ); // in UTC

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
				if ( $this->wp_config->exists( 'constant', 'WP_DEBUG_LOG' ) && is_bool( $wp_debug_log_const ) ) {
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

				echo '<strong>Status</strong>: Logging was enabled on ' . esc_html( $date_time );

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

				echo '<strong>Status</strong>: Logging was disabled on ' . esc_html( $date_time );

			} else {}

		}

	}

	/**
	 * Get debug log in data table format
	 *
	 * @since 1.0.0
	 */
	public function get_entries() {

		?>

		<div class="dlm-log-management"><div class="dlm-log-status-toggle"><input type="checkbox" id="debug-log-checkbox" class="inset-3 debug-log-checkbox"><label for="debug-log-checkbox" class="green debug-log-switcher"></label></div><?php echo $this->get_status(); ?></div>

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

}