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

    	// If non-existent, create an empty index to prevent directory browsing and download of the debug log file
    	$uploads_path = wp_upload_dir()['basedir'] . '/debug-log-manager';
        if ( ! is_file( $uploads_path . '/index.php' ) ) {
	        file_put_contents( $uploads_path . '/index.php', '<?php // Nothing to show here' );    	
        }

		$value 		= get_option( 'debug_log_manager' );
		$status 	= $value['status'];

		if ( function_exists( 'wp_date' ) ) {
			$date_time 	= wp_date( 'M j, Y - H:i:s', strtotime( $value['on'] ) );
		} else {
			$date_time 	= date_i18n( 'M j, Y - H:i:s', strtotime( $value['on'] ) );
		}

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

		if ( isset( $_REQUEST ) && current_user_can( 'manage_options' ) ) {			
			if ( wp_verify_nonce( sanitize_text_field( $_REQUEST['nonce'] ), 'dlm-app' . get_current_user_id() ) ) {
				
				$log_info 				= get_option( 'debug_log_manager' );
		        $dlm_debug_log_file_path 	= get_option( 'debug_log_manager_file_path' );

				if ( function_exists( 'wp_date' ) ) {
					$date_time 	= wp_date( 'M j, Y - H:i:s' ); // Localized according to WP timezone settings
				} else {
					$date_time 	= date_i18n( 'M j, Y - H:i:s' );

				}
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

					// Define Debug constants in wp-config.php

					$options = array(
						'add'       => true, // Add the config if missing.
						'raw'       => true, // Display value in raw format without quotes.
						'normalize' => false, // Normalize config output using WP Coding Standards.
					);

					$this->wp_config->update( 'constant', 'WP_DEBUG', 'true', $options );

					$options = array(
						'add'       => true, // Add the config if missing.
						'raw'       => true, // Display value in raw format without quotes.
						'normalize' => false, // Normalize config output using WP Coding Standards.
					);

					$this->wp_config->update( 'constant', 'SCRIPT_DEBUG', 'true', $options );

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

					$options = array(
						'add'       => true, // Add the config if missing.
						'raw'       => true, // Display value in raw format without quotes.
						'normalize' => false, // Normalize config output using WP Coding Standards.
					);

					$this->wp_config->update( 'constant', 'DISALLOW_FILE_EDIT', 'false', $options );

					// Get the debug.log file size

					$log_file_path 		= get_option( 'debug_log_manager_file_path' );
					$log_file_shortpath = str_replace( sanitize_text_field( $_SERVER['DOCUMENT_ROOT'] ), "", $log_file_path );
					$file_size 			= size_format( (int) filesize( $log_file_path ) );

					// Prepare entries from the debug log for the data table

					$errors_master_list = json_decode( $this->get_processed_entries(), true );

					$n = 1;
					$entries = array();

					foreach ( $errors_master_list as $error ) {

						if ( function_exists( 'wp_date' ) ) {
							$localized_timestamp 	= wp_date( 'M j, Y - H:i:s', strtotime( $error['occurrences'][0] ) ); // last occurrence
						} else {
							$localized_timestamp 	= date_i18n( 'M j, Y - H:i:s', strtotime( $error['occurrences'][0] ) );
						}

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
					$this->wp_config->remove( 'constant', 'SCRIPT_DEBUG' );
					$this->wp_config->remove( 'constant', 'WP_DEBUG_LOG' );
					$this->wp_config->remove( 'constant', 'WP_DEBUG_DISPLAY' );
					$this->wp_config->remove( 'constant', 'DISALLOW_FILE_EDIT' );

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

	}

	/**
	 * Toggle auto-refresh of entries table
	 *
	 * @since 1.3.0
	 */
	public function toggle_autorefresh() {

		if ( isset( $_REQUEST ) && current_user_can( 'manage_options' ) ) {			
			if ( wp_verify_nonce( sanitize_text_field( $_REQUEST['nonce'] ), 'dlm-app' . get_current_user_id() ) ) {
				
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

	}

	/**
	 * Get the processed debug log data
	 *
	 * @return string $errors_master_list The processed error log entries
	 * @since 1.2.0
	 */
	public function get_processed_entries() {

        $debug_log_file_path = get_option( 'debug_log_manager_file_path' );

        // Read the errors log file 
        $log 	= file_get_contents( $debug_log_file_path );
        
        // Ignore words between square brackets, 
        // e.g. [DEBUG], [INFO], [WARNING], [ERROR] may come after timestamp [29-Nov-2023 01:30:03 UTC]
        // This regex will extract DEBUG, INFO, WARNING, ERROR
        // Prevents log entry with two bracket sets e.g. [timestamp] [someinfo] Details....
        // from being returned as "No error message specified"
		$log = preg_replace("/\[([a-zA-Z\s\-]+)\]/", "$1", $log);

        $log 	= str_replace( "[\\", "^\\", $log ); // certain error message contains the '[\' string, which will make the following split via explode() to split lines at places in the message it's not supposed to. So, we temporarily replace those with '^\'

        $log 	= str_replace( "[\"", "^\"", $log ); // certain error message contains the '["' string, which will make the following split via explode() to split lines at places in the message it's not supposed to. So, we temporarily replace those with '^"'

        $log = str_replace( "[internal function]", "^internal function^", $log );

        // We are splitting the log file not using PHP_EOL to preserve the stack traces for PHP Fatal Errors among other things
        $lines 	= explode("[", $log);
        $prepended_lines = array();

        // Pluck out the last 100k entries, the newest entry is last
        // To pluck out the first 100000 entries, use array_slice( $lines, 0, 100000 )
        $lines 	= array_slice( $lines, -100000 );

        foreach ( $lines as $line ) {
        	if ( !empty($line) ) {
        		$line 			= str_replace( "UTC]", "UTC]@@@", $line ); // add '@@@' as marker/separator after time stamp
        		$line 			= str_replace( "Stack trace:", "<hr />Stack trace:", $line ); // add line break for stack trace section
				if ( strpos( $line, 'PHP Fatal' ) !== false ) {
	        		$line 		= str_replace( "#", "<hr />#", $line ); // add line break on PHP Fatal error's stack trace lines
	        	}
        		$line 			= str_replace( "Argument <hr />#", "Argument #", $line ); // remove hr on certain error message
        		$line 			= str_replace( "parameter <hr />#", "parameter #", $line ); // remove hr on certain error message
        		$line 			= str_replace( "the <hr />#", "the #", $line ); // remove hr on certain error message
        		$line 			= str_replace( "^\\", "[\\", $line ); // reverse the temporary replacement of '[\' with '^\'
        		$line 			= str_replace( "^\"", "[\"", $line ); // reverse the temporary replacement of '["' with '^"'
        		$line 			= str_replace( "^internal function^", "[internal function]", $line );
	        	$prepended_line 	= '[' . $line; // Put back the missing '[' after explode operation
	        	$prepended_lines[] 	= $prepended_line;
        	}
        }

        // Reverse the order of the entries, so the newest entry is first
        $latest_lines 	= array_reverse( $prepended_lines );

        // Will hold error details types
        $errors_master_list = array();

		foreach( $latest_lines as $line ) {

			$line = explode("@@@ ", trim( $line ) ); // split the line using the '@@@' marker/separator defined earlier. '@@@' will be deleted by explode().

			$timestamp = str_replace( [ "[", "]" ], "", $line[0] );

			// Initialize error-related variables
			$error = '';
			$error_source = '';	
			$error_file = '';
			$error_file_path = '';
			$error_file_line = '';

			if ( array_key_exists('1', $line) ) {
				$error = $line[1];

				// Check if there is a file path to pluck out of the error line
				if ( false !== strpos( $error, ABSPATH ) ) {

					// Separata file path and error line from error message

					// Handling for PHP Fatal errors with stack trace included
					if ( false !== strpos( $error, 'Stack trace:' ) ) {
						$error_parts = explode( 'Stack trace:', $error );
						$error_message = str_replace( '<hr />', '', $error_parts[0] );
						if ( isset( $error_parts[1] ) ) {
							$error_stack_trace = ' ' . $error_parts[1];
						}

						$error_message_parts = explode( ' in /', $error_message );

						// Reconstruct the error details without error file path and line info
						$error = $error_message_parts[0] . '<hr />Stack trace:' . $error_stack_trace;
						// Shorten the file path in the error details
						$error = str_replace( ABSPATH, '/', $error );
						if ( isset( $error_message_parts[1] ) ) {
							$error_file = '/' . $error_message_parts[1];
							$error_file_info = explode ( ':', $error_file );
							$error_file_path = $error_file_info[0];
							if ( array_key_exists('1', $error_file_info) ) {
								$error_file_line = $error_file_info[1];
							}
						}
					} else {
						$error_message_parts = explode( ' in /', $error );

						$error = $error_message_parts[0];
						if ( isset( $error_message_parts[1] ) ) {
							$error_file = '/' . $error_message_parts[1];

							$error_file_info = explode ( ' on line ', $error_file );
							$error_file_path = $error_file_info[0];
							if ( array_key_exists('1', $error_file_info) ) {
								$error_file_line = $error_file_info[1];
							}
						}
					}

					// Shorten the file path where the error occurred
					$error_file_path = str_replace( ABSPATH, '/', $error_file_path );

					// Define whether source of error is WP Core, Theme, Plugin or Other

					if ( ( false !== strpos( $error_file, '/wp-admin/' ) ) || 
						   ( false !== strpos( $error_file, '/wp-includes/' ) ) ) {
						$error_source = __( 'WordPress core', 'debug-log-manager' );
					} elseif ( ( false !== strpos( $error_file, '/wp-content/themes/' ) ) ) {
						$error_source = __( 'Theme', 'debug-log-manager' );
					} elseif ( ( false !== strpos( $error_file, '/wp-content/plugins/' ) ) ) {
						$error_source = __( 'Plugin', 'debug-log-manager' );
					} else {
						$error_source = '';	
					}

					// Get plugin/theme directory name of error file when error source is plugin or theme

					if ( ( 'Plugin' == $error_source ) || ( 'Theme' == $error_source ) ) {
						$error_file_path_parts = explode( '/', $error_file_path );
						$error_file_directory = $error_file_path_parts[3];
					}

					// Get plugin name

					$plugins = get_plugins();

					if ( 'Plugin' == $error_source ) {
						foreach ( $plugins as $plugin_path_file => $plugin_info ) {
							if ( false !== strpos( $plugin_path_file, $error_file_directory ) ) {
								$error_source_plugin_path_file = $plugin_path_file;
								$error_source_plugin_name = $plugin_info['Name'];
								$error_source_plugin_uri = $plugin_info['PluginURI'];
								// $error_source_plugin_version = $plugin_info['Version'];
							}
						}
					}

					// Get theme name

					if ( 'Theme' == $error_source ) {
						$theme = wp_get_theme( $error_file_directory );
						if ( $theme->exists() ) {
							$error_source_theme_dir = $error_file_directory;
							$error_source_theme_name = $theme->get( 'Name' );
							$error_source_theme_uri = $theme->get( 'ThemeURI' );
							// $error_source_theme_version = $theme->get( 'Version' );
						} else {
							$error_source_theme_name = $error_file_directory;
						}
					}

				}

			} else {

				$error = __( 'No error message specified...', 'debug-log-manager' );
	
			}
			
			if ( ( false !== strpos( $error, 'PHP Fatal' )) || ( false !== strpos( $error, 'FATAL' ) ) || ( false !== strpos( $error, 'E_ERROR' ) ) ) {
				$error_type 	= __( 'PHP Fatal', 'debug-log-manager' );
				$error_details 	= str_replace( "PHP Fatal error: ", "", $error );
				$error_details 	= str_replace( "PHP Fatal: ", "", $error_details );
				$error_details 	= str_replace( "FATAL ", "", $error_details );
				$error_details 	= str_replace( "E_ERROR: ", "", $error_details );
			} elseif ( ( false !== strpos( $error, 'PHP Warning' ) ) || (  false !== strpos( $error, 'E_WARNING' ) ) ) {
				$error_type 	= __( 'PHP Warning', 'debug-log-manager' );
				$error_details 	= str_replace( "PHP Warning: ", "", $error );
				$error_details 	= str_replace( "E_WARNING: ", "", $error_details );
			} elseif ( ( false !== strpos( $error, 'PHP Notice' ) ) || ( false !== strpos( $error, 'E_NOTICE' ) ) ) {
				$error_type 	= __( 'PHP Notice', 'debug-log-manager' );
				$error_details 	= str_replace( "PHP Notice: ", "", $error );
				$error_details 	= str_replace( "E_NOTICE: ", "", $error_details );
			} elseif ( false !== strpos( $error, 'PHP Deprecated' ) ) {
				$error_type 	= __( 'PHP Deprecated', 'debug-log-manager' );
				$error_details 	= str_replace( "PHP Deprecated: ", "", $error );
			} elseif ( ( false !== strpos( $error, 'PHP Parse' ) ) || ( false !== strpos( $error, 'E_PARSE' ) ) ) {
				$error_type 	= __( 'PHP Parse', 'debug-log-manager' );
				$error_details 	= str_replace( "PHP Parse error: ", "", $error );
				$error_details 	= str_replace( "E_PARSE: ", "", $error_details );
			} elseif ( false !== strpos( $error, 'EXCEPTION:' ) ) {
				$error_type 	= __( 'PHP Exception', 'debug-log-manager' );
				$error_details 	= str_replace( "EXCEPTION: ", "", $error );
			} elseif ( false !== strpos( $error, 'WordPress database error' ) ) {
				$error_type 	= __( 'Database', 'debug-log-manager' );
				$error_details 	= str_replace( "WordPress database error ", "", $error );
			} elseif ( false !== strpos( $error, 'JavaScript Error' ) ) {
				$error_type 	= __( 'JavaScript', 'debug-log-manager' );
				$error_details 	= str_replace( "JavaScript Error: ", "", $error );
			} else {
				$error_type 	= __( 'Other', 'debug-log-manager' );
				$error_details 	= $error;
				if ( $this->is_json( $error_details ) ) {
					// For JSON string in error message, originally added via error_log( json_encode( $variable ) )
					// This will output said JSON string as well-formated array in the log entries table
					$error_details = '<pre>' . print_r( json_decode( $error_details, true ), true ) . '</pre>';
				}
			}

			// Append error source, file path and line number info to error details. If core plugin/theme editor is not disabled, link file path to the editor view.

			if ( ! empty( $error_source ) ) {
				if ( 'WordPress core' == $error_source ) {
					$wp_version = get_bloginfo( 'version' );
					$file_viewer_url = 'https://github.com/WordPress/wordpress-develop/blob/' . $wp_version . '/src' . $error_file_path;
					$error_details = '<span class="error-details">' . $error_details . '</span><hr />' . $error_source . '<br />' . __( 'File', 'debug-log-manager' ) . ': <a href="' . $file_viewer_url . '" target="_blank" class="error-source-link">' . $error_file_path . '<span class="dashicons dashicons-visibility offset-down"></span></a><br />' . __( 'Line', 'debug-log-manager' ) . ': ' . $error_file_line;
				} elseif ( 'Theme' == $error_source ) {
					if ( ! defined( 'DISALLOW_FILE_EDIT' ) || ( false === constant( 'DISALLOW_FILE_EDIT' ) ) ) {
						$file_viewer_url = get_admin_url() . 'theme-editor.php?file=' . urlencode( str_replace( '/wp-content/themes/', '', $error_file_path ) ) . '&theme=' . $error_source_theme_dir;
						$error_details = '<span class="error-details">' . $error_details . '</span><hr />' . $error_source . ': <a href="' . $error_source_theme_uri . '" target="_blank" class="error-source-link">' . $error_source_theme_name . '<span class="dashicons dashicons-external offset-up"></span></a><br />' . __( 'File', 'debug-log-manager' ) . ': <a href="' . $file_viewer_url . '" target="_blank" class="error-source-link">' . $error_file_path . '<span class="dashicons dashicons-visibility offset-down"></span></a><br />' . __( 'Line', 'debug-log-manager' ) . ': ' . $error_file_line;
					} 
					if ( defined( 'DISALLOW_FILE_EDIT' ) && ( true === constant( 'DISALLOW_FILE_EDIT' ) ) ) {
						$error_details = '<span class="error-details">' . $error_details . '</span><hr />' . $error_source . ': <a href="' . $error_source_theme_uri . '" target="_blank" class="error-source-link">' . $error_source_theme_name . '<span class="dashicons dashicons-external offset-up"></span></a><br />' . __( 'File', 'debug-log-manager' ) . ': ' . $error_file_path . '<br />' . __( 'Line', 'debug-log-manager' ) . ': ' . $error_file_line;
					}
				} elseif ( 'Plugin' == $error_source ) {
					if ( ! defined( 'DISALLOW_FILE_EDIT' ) || ( false === constant( 'DISALLOW_FILE_EDIT' ) ) ) {
						$file_viewer_url = get_admin_url() . 'plugin-editor.php?file=' . urlencode( str_replace( '/wp-content/plugins/', '', $error_file_path ) ) . '&plugin=' . urlencode( $error_source_plugin_path_file );
						$error_details = '<span class="error-details">' . $error_details . '</span><hr />' . $error_source . ': <a href="' . $error_source_plugin_uri . '" target="_blank" class="error-source-link">' . $error_source_plugin_name . '<span class="dashicons dashicons-external offset-up"></span></a><br />' . __( 'File', 'debug-log-manager' ) . ': <a href="' . $file_viewer_url . '" target="_blank" class="error-source-link">' . $error_file_path . '<span class="dashicons dashicons-visibility offset-down"></span></a><br />' . __( 'Line', 'debug-log-manager' ) . ': ' . $error_file_line;
					} 
					if ( defined( 'DISALLOW_FILE_EDIT' ) && ( true === constant( 'DISALLOW_FILE_EDIT' ) ) ) {
						$error_details = '<span class="error-details">' . $error_details . '</span><hr />' . $error_source . ': <a href="' . $error_source_plugin_uri . '" target="_blank" class="error-source-link">' . $error_source_plugin_name . '<span class="dashicons dashicons-external offset-up"></span></a><br />' . __( 'File', 'debug-log-manager' ) . ': ' . $error_file_path . '<br />' . __( 'Line', 'debug-log-manager' ) . ': ' . $error_file_line;
					}
				}
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

		if ( isset( $_REQUEST ) && current_user_can( 'manage_options' ) ) {

			if ( wp_verify_nonce( sanitize_text_field( $_REQUEST['nonce'] ), 'dlm-app' . get_current_user_id() ) ) {
				
				$errors_master_list = json_decode( $this->get_processed_entries(), true );

				$n = 1;
				$entries = array();

				foreach ( $errors_master_list as $error ) {

					if ( function_exists( 'wp_date' ) ) {
						$localized_timestamp 	= wp_date( 'M j, Y - H:i:s', strtotime( $error['occurrences'][0] ) ); // last occurrence
					} else {
						$localized_timestamp 	= date_i18n( 'M j, Y - H:i:s', strtotime( $error['occurrences'][0] ) );
					}

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

			}

		} else {
			
			$data = array();
			
		}

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
				<option value="<?php esc_attr_e( 'PHP Exception', 'debug-log-manager' ); ?>"><?php esc_html_e( 'PHP Exception', 'debug-log-manager' ); ?></option>
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

			if ( function_exists( 'wp_date' ) ) {
				$localized_timestamp 	= wp_date( 'M j, Y - H:i:s', strtotime( $error['occurrences'][0] ) ); // last occurrence
			} else {
				$localized_timestamp 	= date_i18n( 'M j, Y - H:i:s', strtotime( $error['occurrences'][0] ) );
			}

			$occurrence_count 		= count( $error['occurrences'] );
			?>

			<tr>
				<td class="dlm-entry-no"><?php echo esc_html( $n ); ?></td>
				<td class="dlm-entry-type"><?php echo esc_html( $error['type'] ); ?></td>
				<td class="dlm-entry-details"><?php echo wp_kses( $error['details'], 'post' ); ?></td>
				<td class="dlm-entry-datetime"><?php echo esc_html( $localized_timestamp ); ?><br /><span class="dlm-faint">(<?php printf( esc_html( _n( '%s occurrence logged', '%s occurrences logged', $occurrence_count, 'debug-log-manager' ) ), esc_html( number_format_i18n( $occurrence_count ) ) ); ?>)<span></td>
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
		$entries_to_show = 5;

		?>
		<style>

			#debug_log_manager_widget.postbox .inside {
				margin: 0;
				padding: 0;
			}

			.dlm-dashboard-widget-entry {
				padding: 12px;
				border-bottom: 1px solid #e6e7e7;
			    word-wrap:  break-word; /* All browsers since IE 5.5+ */
			    overflow-wrap: break-word; /* Renamed property in CSS3 draft spec */
			}

			.dlm-dashboard-widget-entry:nth-child(odd) {
				background-color: #f6f7f7;
			}

			.dlm-dashboard-widget-entry-message a.error-source-link {
			    color: #50575e;
			    text-decoration: none;
			}

			.dlm-dashboard-widget-entry-message a.error-source-link span {
			    position: relative;
			    margin-left: 2px;
			    color: #777;
			    font-size: 18px;
			    width: 18px;
			    height: 18px;
			    transition: .25s;
			    text-decoration: none;
			}

			.dlm-dashboard-widget-entry-message a.error-source-link span.offset-up {
			    top: -1px;
			}

			.dlm-dashboard-widget-entry-message a.error-source-link span.offset-down {
			    top: 1px;
			}

			.dlm-dashboard-widget-entry-message a.error-source-link:hover {
			    color: #2271b1;
			    text-decoration: underline;
			}

			#debug-log .dlm-entry-details a.error-source-link:hover span {
			    color: #2271b1;
			    text-decoration: none;
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
				width: 100%;
				box-sizing: border-box;
				padding: 12px;
				background-color: #f6f7f7;
			}

		</style>
		<div class="dlm-dashboard-widget-entries">
		<?php

		foreach ( $errors_master_list as $error ) {

			if ( $n <= $entries_to_show ) {

				if ( function_exists( 'wp_date' ) ) {
					$localized_timestamp 	= wp_date( 'M j, Y, H:i:s', strtotime( $error['occurrences'][0] ) ); // last occurrence
				} else {
					$localized_timestamp 	= date_i18n( 'M j, Y - H:i:s', strtotime( $error['occurrences'][0] ) );
				}

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
				<?php echo wp_kses_post( $this->get_status() ); ?>
			</div>
			<a href="<?php echo esc_html( get_dashboard_url() ); ?>tools.php?page=debug-log-manager" class="button"><?php esc_html_e( 'Go to Debug Log Manager', 'debug-log-manager' ); ?></a>
		</div>
		<?php

	}

	/**
	 * Clear log file
	 *
	 * @since 1.0.0
	 */
	public function clear_log() {
		
		if ( isset( $_REQUEST ) && current_user_can( 'manage_options' ) ) {
			
			if ( wp_verify_nonce( sanitize_text_field( $_REQUEST['nonce'] ), 'dlm-app' . get_current_user_id() ) ) {

		        $debug_log_file_path = get_option( 'debug_log_manager_file_path' );

				file_put_contents( $debug_log_file_path, '' );

				echo true;
				
			}

		}

	}

	/**
	 * Disable WP core's plugin/theme editor
	 *
	 * @since 2.0.0
	 */
	public function disable_wp_file_editor() {

		if ( isset( $_REQUEST ) && current_user_can( 'manage_options' ) ) {			
			if ( wp_verify_nonce( sanitize_text_field( $_REQUEST['nonce'] ), 'dlm-app' . get_current_user_id() ) ) {

				$options = array(
					'add'       => true, // Add the config if missing.
					'raw'       => true, // Display value in raw format without quotes.
					'normalize' => false, // Normalize config output using WP Coding Standards.
				);

				$this->wp_config->update( 'constant', 'DISALLOW_FILE_EDIT', 'true', $options );

				// Prepare entries from the debug log for the data table

				$errors_master_list = json_decode( $this->get_processed_entries(), true );

				$n = 1;
				$entries = array();

				foreach ( $errors_master_list as $error ) {

					if ( function_exists( 'wp_date' ) ) {
						$localized_timestamp 	= wp_date( 'M j, Y - H:i:s', strtotime( $error['occurrences'][0] ) ); // last occurrence
					} else {
						$localized_timestamp 	= date_i18n( 'M j, Y - H:i:s', strtotime( $error['occurrences'][0] ) );
					}

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

				// Assemble data to return

				$data = array(
					'status'	=> 'disabled', // Plugin/theme editor
					'entries'	=> $entries, // To parse data table with unlinked plugin/theme file paths
				);

				echo json_encode( $data );
								
			}
		}

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
			
				// Sanitize all input data
				$message = sanitize_text_field( $request['message'] );
				$script = sanitize_text_field( $request['script'] );
				$line_number = sanitize_text_field( $request['lineNo'] );
				$column_number = sanitize_text_field( $request['columnNo'] );
				$page_url = sanitize_text_field( $request['pageUrl'] );

				// The following entry will then be output with wp_kses()
				error_log( 'JavaScript Error: ' . $message . ' in ' . $script . ' on line ' . $line_number . ' column ' . $column_number . ' at ' . get_site_url() . $page_url );

		} else {

			wp_die();

		}

	}

	/**
	 * Check if a string is valid JSON
	 *
	 * @link https://stackoverflow.com/a/6041773
	 * @since 2.1.0
	 */
	public function is_json( $string ) {

		json_decode( $string );
		return json_last_error() === JSON_ERROR_NONE;

	}

}