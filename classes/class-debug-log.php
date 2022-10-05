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

		if ( 'enabled' == $status ) {

			return '<div id="debug-log-status" class="dlm-log-status"><strong>' . esc_html__( 'Error Logging', 'debug-log-manager' ) . '</strong>: ' . ucfirst( esc_html__( 'enabled', 'debug-log-manager' ) ) . ' ' . esc_html__( 'on', 'debug-log-manager' ) . ' ' . esc_html( $date_time ) . '</div>';

		} elseif ( 'disabled' == $status ) {

			return '<div id="debug-log-status" class="dlm-log-status"><strong>' . esc_html__( 'Error Logging', 'debug-log-manager' ) . '</strong>: ' . ucfirst( esc_html__( 'disabled', 'debug-log-manager' ) ) . ' ' . esc_html__( 'on', 'debug-log-manager' ) . ' ' . esc_html( $date_time ) . '</div>';

		} else {}

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

		if ( 'enabled' == $autorefresh_status ) {

			return '<div id="debug-autorefresh-status" class="dlm-autorefresh-status"><strong>Auto-Refresh</strong>: ' . ucfirst( esc_html__( 'enabled', 'debug-log-manager' ) ) . '</div>';

		} elseif ( 'disabled' == $autorefresh_status ) {

			return '<div id="debug-autorefresh-status" class="dlm-autorefresh-status"><strong>Auto-Refresh</strong>: ' . ucfirst( esc_html__( 'disabled', 'debug-log-manager' ) ) . '</div>';

		}

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
							$localized_timestamp . '<br /><span class="dlm-faint">(' . sprintf( _n( '%s occurrence logged', '%s occurrences logged', $occurrence_count, 'debug-log-manager' ), number_format_i18n( $occurrence_count ) ) . ')<span>',
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
					'message' 	=> '<strong>' . esc_html__( 'Error Logging', 'debug-log-manager' ) . '</strong>: ' . esc_html__( 'Enabled on', 'debug-log-manager' ) . ' ' . esc_html( $date_time ),
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
					'message' 	=> '<strong>' . esc_html__( 'Error Logging', 'debug-log-manager' ) . '</strong>: ' . esc_html__( 'Disabled on', 'debug-log-manager' ) . ' ' . esc_html( $date_time ),
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
					'message' 	=> '<strong>' . esc_html__( 'Auto-Refresh', 'debug-log-manager' ) . '</strong>: ' . esc_html__( 'Enabled', 'debug-log-manager' ),
				);

				echo json_encode( $data );

			} elseif ( $autorefresh_status == 'enabled' ) {

		        update_option( 'debug_log_manager_autorefresh', 'disabled', false );

				$data = array(
					'status'	=> 'disabled',
					'message' 	=> '<strong>' . esc_html__( 'Auto-Refresh', 'debug-log-manager' ) . '</strong>: ' . esc_html__( 'Disabled', 'debug-log-manager' ),
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

        // We are splitting the log file not using PHP_EOL to preserve the stack traces for PHP Fatal Errors among other things
        $lines 	= explode("[", $log);
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
	        	$prepended_line 	= '[' . $line; // Put back the missing '[' after explode operation
	        	$prepended_lines[] 	= $prepended_line;
        	}
        }

        $lines_newest_first 	= array_reverse( $prepended_lines );
        $latest_lines 			= array_slice( $lines_newest_first, 0, 50000 );

        // Will hold error details types
        $errors_master_list = array();

		foreach( $latest_lines as $line ) {

			$line = explode("@@@ ", $line); // split the line using the '@@@' marker/separator defined earlier. '@@@' will be deleted by explode().

			$timestamp = str_replace( [ "[", "]" ], "", $line[0] );
			if ( array_key_exists('1', $line) ) {
				$error = $line[1];
			} else {
				$error = 'No error message specified...';
			}

			if ( strpos( $error, 'PHP Fatal' ) !== false ) {
				$error_type 	= __( 'PHP Fatal', 'debug-log-manager' );
				$error_details 	= str_replace( "PHP Fatal error: ", "", $error );
				$error_details 	= str_replace( "PHP Fatal: ", "", $error_details );
			} elseif ( strpos( $error, 'PHP Warning' ) !== false ) {
				$error_type 	= __( 'PHP Warning', 'debug-log-manager' );
				$error_details 	= str_replace( "PHP Warning: ", "", $error );
			} elseif ( strpos( $error, 'PHP Notice' ) !== false ) {
				$error_type 	= __( 'PHP Notice', 'debug-log-manager' );
				$error_details 	= str_replace( "PHP Notice: ", "", $error );
			} elseif ( strpos( $error, 'PHP Deprecated' ) !== false ) {
				$error_type 	= __( 'PHP Deprecated', 'debug-log-manager' );
				$error_details 	= str_replace( "PHP Deprecated: ", "", $error );
			} elseif ( strpos( $error, 'PHP Parse' ) !== false ) {
				$error_type 	= __( 'PHP Parse', 'debug-log-manager' );
				$error_details 	= str_replace( "PHP Parse error: ", "", $error );
			} elseif ( strpos( $error, 'WordPress database error' ) !== false ) {
				$error_type 	= __( 'Database', 'debug-log-manager' );
				$error_details 	= str_replace( "WordPress database error ", "", $error );
			} elseif ( strpos( $error, 'JavaScript Error' ) !== false ) {
				$error_type 	= __( 'JavaScript', 'debug-log-manager' );
				$error_details 	= str_replace( "JavaScript Error: ", "", $error );
			} else {
				$error_type 	= __( 'Other', 'debug-log-manager' );
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
					$localized_timestamp . '<br /><span class="dlm-faint">(' . sprintf( _n( '%s occurrence logged', '%s occurrences logged', $occurrence_count, 'debug-log-manager' ), number_format_i18n( $occurrence_count ) ) . ')<span>',
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
				<option value=""><?php esc_html_e( 'All Error Types', 'debug-log-manager' ); ?></option>
				<option value="<?php esc_attr_e( 'PHP Fatal', 'debug-log-manager' ); ?>"><?php esc_html_e( 'PHP Fatal', 'debug-log-manager' ); ?></option>
				<option value="<?php esc_attr_e( 'PHP Warning', 'debug-log-manager' ); ?>"><?php esc_html_e( 'PHP Warning', 'debug-log-manager' ); ?></option>
				<option value="<?php esc_attr_e( 'PHP Notice', 'debug-log-manager' ); ?>"><?php esc_html_e( 'PHP Notice', 'debug-log-manager' ); ?></option>
				<option value="<?php esc_attr_e( 'PHP Deprecated', 'debug-log-manager' ); ?>"><?php esc_html_e( 'PHP Deprecated', 'debug-log-manager' ); ?></option>
				<option value="<?php esc_attr_e( 'PHP Parse', 'debug-log-manager' ); ?>"><?php esc_html_e( 'PHP Parse', 'debug-log-manager' ); ?></option>
				<option value="<?php esc_attr_e( 'Database', 'debug-log-manager' ); ?>"><?php esc_html_e( 'Database', 'debug-log-manager' ); ?></option>
				<option value="<?php esc_attr_e( 'JavaScript', 'debug-log-manager' ); ?>"><?php esc_html_e( 'JavaScript', 'debug-log-manager' ); ?></option>
				<option value="<?php esc_attr_e( 'Other', 'debug-log-manager' ); ?>"><?php esc_html_e( 'Other', 'debug-log-manager' ); ?></option>				
			</select>
		</div>
		<table id="debug-log" class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th class="dlm-entry-no">#</th>
					<th class="dlm-entry-type"><?php esc_html_e( 'Error Type', 'debug-log-manager' ); ?></th>
					<th class="dlm-entry-details"><?php esc_html_e( 'Details', 'debug-log-manager' ); ?></th>
					<th class="dlm-entry-datetime"><?php esc_html_e( 'Last Occurrence', 'debug-log-manager' ); ?></th>
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
				<td class="dlm-entry-no"><?php echo esc_html( $n ); ?></td>
				<td class="dlm-entry-type"><?php echo esc_html( $error['type'] ); ?></td>
				<td class="dlm-entry-details"><?php echo wp_kses( $error['details'], 'post' ); ?></td>
				<td class="dlm-entry-datetime"><?php echo esc_html( $localized_timestamp ); ?><br /><span class="dlm-faint">(<?php printf( _n( '%s occurrence logged', '%s occurrences logged', $occurrence_count, 'debug-log-manager' ), number_format_i18n( $occurrence_count ) ); ?>)<span></td>
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
	 * Get entries for dashboard widget
	 *
	 * @since 1.8.0
	 */
	public function get_dashboard_widget_entries() {

		$errors_master_list = json_decode( $this->get_processed_entries(), true );

		$n = 1;

		?>
		<style>

			#debug_log_manager_widget.postbox .inside {
				margin: 0;
				padding: 0;
			}

			.dlm-dashboard-widget-entry {
				padding: 12px;
				border-bottom: 1px solid #e6e7e7;
			}

			.dlm-dashboard-widget-entry:nth-child(odd) {
				background-color: #f6f7f7;
			}

			.dlm-dashboard-widget-entry-meta {
				display: flex;
			}

			.dlm-dashboard-widget-entry-type {
				margin-right: 4px;
				font-weight: 600;
			}

			.dlm-dashboard-widget-footer {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 12px;
				background-color: #f6f7f7;
			}

		</style>
		<div class="dlm-dashboard-widget-entries">
		<?php

		foreach ( $errors_master_list as $error ) {

			if ( $n <= 10 ) {

				$localized_timestamp 	= wp_date( 'M j, Y, H:i:s', strtotime( $error['occurrences'][0] ) ); // last occurrence
				$occurrence_count 		= count( $error['occurrences'] );

				?>
					<div class="dlm-dashboard-widget-entry">
						<div class="dlm-dashboard-widget-entry-meta">
							<div class="dlm-dashboard-widget-entry-type">
								<?php echo esc_html( $error['type'] ); ?>
							</div>
							<div class="dlm-dashboard-widget-entry-datetime">
								| <?php echo esc_html( $localized_timestamp ); ?>
							</div>
						</div>
						<div class="dlm-dashboard-widget-entry-message">
							<?php echo wp_kses( $error['details'], 'post' ); ?>
						</div>
					</div>
				<?php

			}

			$n++;

		}

		?>
		</div>
		<div class="dlm-dashboard-widget-footer">
			<div class="dlm-dashboard-widget-logging-status">
				<?php echo $this->get_status(); ?>
			</div>
			<a href="<?php echo get_dashboard_url(); ?>tools.php?page=debug-log-manager" class="button"><?php esc_html_e( 'Go to Debug Log Manager', 'debug-log-manager' ); ?></a>
		</div>
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