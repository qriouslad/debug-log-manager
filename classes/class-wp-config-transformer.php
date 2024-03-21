<?php

namespace DLM\Classes;

use Exception;

/**
 * Class with methods to manipulate wp-config.php
 *
 * @since 1.0.0
 * @link https://plugins.svn.wordpress.org/debug-log-config-tool/tags/1.1/src/Classes/vendor/WPConfigTransformer.php
 */
class WP_Config_Transformer {	

	/**
	 * The wp-config.php source file
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $wp_config_src;

	/**
	 * The configs defined in wp-config.php
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $wp_configs;

	/**
	 * Get wp-config.php file path
	 *
	 * @since 1.0.0
	 */
	public function wpconfig_file( $type = 'path' ) {

		// From wp-load.php

		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {

			/** The config file resides in ABSPATH */
			$file = ABSPATH . 'wp-config.php';
			$location = 'WordPress root directory';


		} elseif ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {

			/** The config file resides one level above ABSPATH but is not part of another installation */
			$file = dirname( ABSPATH ) . '/wp-config.php';
			$location = 'parent directory of WordPress root';

		} else {

			$file = 'Undetectable.';
			$location = 'not in WordPress root or it\'s parent directory';

		}

		if ( !is_writable( $file ) ) {
			$writeability = 'not writeable';
        } else {
        	$writeability = 'writeable';
        }

		if ( $type == 'path' ) {
	        return $file;
		} elseif ( $type == 'location' ) {
			return $location;
		} elseif ( $type == 'writeability' ) {
			return $writeability;
		} elseif ( $type == 'status' ) {
        	return '<div class="dlm-wpconfig-status" style="display: none;">The wp-config.php file is located in ' . $location . ' ('. $file . ') and is ' . $writeability .'.</div>';
		}

	}

	/**
	 * Get configs in wp-config.php
	 * 
	 * @since 1.0.0
	 * @link https://plugins.svn.wordpress.org/debug-log-config-tool/tags/1.1/src/Classes/vendor/WPConfigTransformer.php
	 */
	public function configs( $return_type = 'raw' ) {

		$src = file_get_contents( $this->wpconfig_file( 'path' ) );

		$configs             = array();
		$configs['constant'] = array();
		$configs['variable'] = array();		

		// Strip comments.
		foreach ( token_get_all( $src ) as $token ) {
			if ( in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
				$src = str_replace( $token[1], '', $src );
			}
		}

		preg_match_all( '/(?<=^|;|<\?php\s|<\?\s)(\h*define\s*\(\s*[\'"](\w*?)[\'"]\s*)(,\s*(\'\'|""|\'.*?[^\\\\]\'|".*?[^\\\\]"|.*?)\s*)((?:,\s*(?:true|false)\s*)?\)\s*;)/ims', $src, $constants );
		preg_match_all( '/(?<=^|;|<\?php\s|<\?\s)(\h*\$(\w+)\s*=)(\s*(\'\'|""|\'.*?[^\\\\]\'|".*?[^\\\\]"|.*?)\s*;)/ims', $src, $variables );

		if ( ! empty( $constants[0] ) && ! empty( $constants[1] ) && ! empty( $constants[2] ) && ! empty( $constants[3] ) && ! empty( $constants[4] ) && ! empty( $constants[5] ) ) {
			foreach ( $constants[2] as $index => $name ) {
				$configs['constant'][ $name ] = array(
					'src'   => $constants[0][ $index ],
					'value' => $constants[4][ $index ],
					'parts' => array(
						$constants[1][ $index ],
						$constants[3][ $index ],
						$constants[5][ $index ],
					),
				);
			}
		}

		if ( ! empty( $variables[0] ) && ! empty( $variables[1] ) && ! empty( $variables[2] ) && ! empty( $variables[3] ) && ! empty( $variables[4] ) ) {
			// Remove duplicate(s), last definition wins.
			$variables[2] = array_reverse( array_unique( array_reverse( $variables[2], true ) ), true );
			foreach ( $variables[2] as $index => $name ) {
				$configs['variable'][ $name ] = array(
					'src'   => $variables[0][ $index ],
					'value' => $variables[4][ $index ],
					'parts' => array(
						$variables[1][ $index ],
						$variables[3][ $index ],
					),
				);
			}
		}

		$this->wp_configs = $configs;

		if ( $return_type == 'raw' ) {
			return $configs;
		} elseif ( $return_type == 'print_r' ) {
			return '<pre>' . print_r( $configs, true ) . '</pre>';
		}
	}

	/**
	 * Checks if a config exists in the wp-config.php file.
	 *
	 * @throws Exception If the wp-config.php file is empty.
	 * @throws Exception If the requested config type is invalid.
	 *
	 * @param string $type Config type (constant or variable).
	 * @param string $name Config name.
	 *
	 * @return bool
	 * @since 1.0.0
	 * @link https://plugins.svn.wordpress.org/debug-log-config-tool/tags/1.1/src/Classes/vendor/WPConfigTransformer.php
	 */
	public function exists( $type, $name ) {
		$wp_config_src = file_get_contents( $this->wpconfig_file( 'path' ) );

		if ( ! trim( $wp_config_src ) ) {
			throw new Exception( 'Config file is empty.' );
		}
		// Normalize the newline to prevent an issue coming from OSX.
		$this->wp_config_src = str_replace( array( "\n\r", "\r" ), "\n", $wp_config_src );

		$this->wp_configs = $this->configs( 'raw' );

		if ( ! isset( $this->wp_configs[ $type ] ) ) {
			throw new Exception( esc_html( "Config type '{$type}' does not exist." ) );
		}

		return isset( $this->wp_configs[ $type ][ $name ] );
	}

	/**
	 * Get the value of a config in the wp-config.php file.
	 *
	 * @throws Exception If the wp-config.php file is empty.
	 * @throws Exception If the requested config type is invalid.
	 *
	 * @param string $type Config type (constant or variable).
	 * @param string $name Config name.
	 *
	 * @return array
	 * @link https://plugins.svn.wordpress.org/debug-log-config-tool/tags/1.1/src/Classes/vendor/WPConfigTransformer.php
	 */
	public function get_value( $type, $name ) {
		$wp_config_src = file_get_contents( $this->wpconfig_file( 'path' ) );

		if ( ! trim( $wp_config_src ) ) {
			throw new Exception( 'Config file is empty.' );
		}

		$this->wp_config_src = $wp_config_src;
		$this->wp_configs    = $this->configs( 'raw' );

		if ( ! isset( $this->wp_configs[ $type ] ) ) {
			throw new Exception( esc_html( "Config type '{$type}' does not exist." ) );
		}

		return $this->wp_configs[ $type ][ $name ]['value'];
	}

	/**
	 * Adds a config to the wp-config.php file.
	 *
	 * @throws Exception If the config value provided is not a string.
	 * @throws Exception If the config placement anchor could not be located.
	 *
	 * @param string $type    Config type (constant or variable).
	 * @param string $name    Config name.
	 * @param string $value   Config value.
	 * @param array  $options (optional) Array of special behavior options.
	 *
	 * @return bool
	 * @since 1.0.0
	 * @link https://plugins.svn.wordpress.org/debug-log-config-tool/tags/1.1/src/Classes/vendor/WPConfigTransformer.php
	 */
	public function add( $type, $name, $value, array $options = array() ) {
		if ( ! is_string( $value ) ) {
			throw new Exception( 'Config value must be a string.' );
		}

		if ( $this->exists( $type, $name ) ) {
			return false;
		}

		if ( in_array( $value, array( 'true', 'false' ), true ) ) {
			$raw_input = true;
		} else {
			$raw_input = false;			
		}

		$wp_config_src = file_get_contents( $this->wpconfig_file( 'path' ) );

		if ( false !== strpos( $wp_config_src, "Happy publishing" ) ) {
			$anchor = "/* That's all, stop editing! Happy publishing. */";
		} elseif ( false !== strpos( $wp_config_src, "Happy blogging" ) ) {
			$anchor = "/* That's all, stop editing! Happy blogging. */";
		} else {}

		$defaults = array(
			'raw'       => $raw_input, // Display value in raw format without quotes.
			'anchor'    => $anchor, // Config placement anchor string.
			'separator' => PHP_EOL, // Separator between config definition and anchor string.
			'placement' => 'before', // Config placement direction (insert before or after).
		);

		// list( $raw, $anchor, $separator, $placement ) = array_values( $options );
		list( $raw, $anchor, $separator, $placement ) = array_values( array_merge( $defaults, $options ) );;

		$raw       = (bool) $raw;
		$anchor    = (string) $anchor;
		$separator = (string) $separator;
		$placement = (string) $placement;

		if ( 'EOF' === $anchor ) {
			$contents = $this->wp_config_src . $this->normalize( $type, $name, $this->format_value( $value, $raw ) );
		} else {
			if ( false === strpos( $this->wp_config_src, $anchor ) ) {
				throw new Exception( 'Unable to locate placement anchor.' );
			}

			$new_src  = $this->normalize( $type, $name, $this->format_value( $value, $raw ) );
			$new_src  = ( 'after' === $placement ) ? $anchor . $separator . $new_src : $new_src . $separator . $anchor;
			$contents = str_replace( $anchor, $new_src, $this->wp_config_src );
		}

		return $this->save( $contents );
	}

	/**
	 * Updates an existing config in the wp-config.php file.
	 *
	 * @throws Exception If the config value provided is not a string.
	 *
	 * @param string $type    Config type (constant or variable).
	 * @param string $name    Config name.
	 * @param string $value   Config value.
	 * @param array  $options (optional) Array of special behavior options.
	 *
	 * @return bool
	 * @since 1.0.0
	 * @link https://plugins.svn.wordpress.org/debug-log-config-tool/tags/1.1/src/Classes/vendor/WPConfigTransformer.php
	 */
	public function update( $type, $name, $value, array $options = array() ) {
		if ( ! is_string( $value ) ) {
			throw new Exception( 'Config value must be a string.' );
		}

		// $defaults = array(
		// 	'add'       => true, // Add the config if missing.
		// 	'raw'       => false, // Display value in raw format without quotes.
		// 	'normalize' => false, // Normalize config output using WP Coding Standards.
		// );

		// list( $add, $raw, $normalize ) = array_values( array_merge( $defaults, $options ) );
		list( $add, $raw, $normalize ) = array_values( $options );

		$add       = (bool) $add;
		$raw       = (bool) $raw;
		$normalize = (bool) $normalize;

		if ( ! $this->exists( $type, $name ) ) {
			return ( $add ) ? $this->add( $type, $name, $value ) : false;
		}

		$old_src   = $this->wp_configs[ $type ][ $name ]['src'];
		$old_value = $this->wp_configs[ $type ][ $name ]['value'];
		$new_value = $this->format_value( $value, $raw );

		if ( $normalize ) {
			$new_src = $this->normalize( $type, $name, $new_value );
		} else {
			$new_parts    = $this->wp_configs[ $type ][ $name ]['parts'];
			$new_parts[1] = str_replace( $old_value, $new_value, $new_parts[1] ); // Only edit the value part.
			$new_src      = implode( '', $new_parts );
		}

		$contents = preg_replace(
			sprintf( '/(?<=^|;|<\?php\s|<\?\s)(\s*?)%s/m', preg_quote( trim( $old_src ), '/' ) ),
			'$1' . str_replace( '$', '\$', trim( $new_src ) ),
			$this->wp_config_src
		);

		return $this->save( $contents );
	}

	/**
	 * Removes a config from the wp-config.php file.
	 *
	 * @param string $type Config type (constant or variable).
	 * @param string $name Config name.
	 *
	 * @return bool
	 * @since 1.0.0
	 * @link https://plugins.svn.wordpress.org/debug-log-config-tool/tags/1.1/src/Classes/vendor/WPConfigTransformer.php
	 */
	public function remove( $type, $name ) {
		if ( ! $this->exists( $type, $name ) ) {
			return false;
		}

		$wp_config_src = file_get_contents( $this->wpconfig_file( 'path' ) );
		$this->wp_config_src = str_replace( array( "\n\r", "\r" ), "\n", $wp_config_src );
		$this->wp_configs = $this->configs( 'raw' );

		$pattern  = sprintf( '/(?<=^|;|<\?php\s|<\?\s)%s\s*(\S|$)/m', preg_quote( $this->wp_configs[$type][$name]['src'], '/' ) );
		$contents = preg_replace( $pattern, '$1', $this->wp_config_src );

		return $this->save( $contents );
	}

	/**
	 * Applies formatting to a config value.
	 *
	 * @throws Exception When a raw value is requested for an empty string.
	 *
	 * @param string $value Config value.
	 * @param bool   $raw   Display value in raw format without quotes.
	 *
	 * @return mixed
	 * @since 1.0.0
	 * @link https://plugins.svn.wordpress.org/debug-log-config-tool/tags/1.1/src/Classes/vendor/WPConfigTransformer.php
	 */
	public function format_value( $value, $raw ) {
		if ( $raw && '' === trim( $value ) ) {
			throw new Exception( 'Raw value for empty string not supported.' );
		}

		return ( $raw ) ? $value : var_export( $value, true );
	}

	/**
	 * Normalizes the source output for a name/value pair.
	 *
	 * @throws Exception If the requested config type does not support normalization.
	 *
	 * @param string $type  Config type (constant or variable).
	 * @param string $name  Config name.
	 * @param mixed  $value Config value.
	 *
	 * @return string
	 * @since 1.0.0
	 * @link https://plugins.svn.wordpress.org/debug-log-config-tool/tags/1.1/src/Classes/vendor/WPConfigTransformer.php
	 */
	public function normalize( $type, $name, $value ) {
		if ( 'constant' === $type ) {
			$placeholder = "define( '%s', %s );";
		} elseif ( 'variable' === $type ) {
			$placeholder = '$%s = %s;';
		} else {
			throw new Exception( esc_html( "Unable to normalize config type '{$type}'." ) );
		}

		return sprintf( $placeholder, $name, $value );
	}

	/**
	 * Saves new contents to the wp-config.php file.
	 *
	 * @throws Exception If the config file content provided is empty.
	 * @throws Exception If there is a failure when saving the wp-config.php file.
	 *
	 * @param string $contents New config contents.
	 *
	 * @return bool
	 * @since 1.0.0
	 * @link https://plugins.svn.wordpress.org/debug-log-config-tool/tags/1.1/src/Classes/vendor/WPConfigTransformer.php
	 */
	public function save( $contents ) {
		if ( ! trim( $contents ) ) {
			throw new Exception( 'Cannot save the config file with empty contents.' );
		}

		if ( $contents === $this->wp_config_src ) {
			return false;
		}

		$result = file_put_contents( $this->wpconfig_file( 'path' ), $contents, LOCK_EX );

		if ( false === $result ) {
			throw new Exception( 'Failed to update the config file.' );
		}

		return true;
	}

}