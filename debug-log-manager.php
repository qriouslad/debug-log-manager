<?php

/**
 * Plugin Name:       Debug Log Manager
 * Plugin URI:        https://wordpress.org/plugins/debug-log-manager/
 * Description:       Log errors via WP_DEBUG. Create, view and clear debug.log file.
 * Version:           1.6.4
 * Author:            Bowo
 * Author URI:        https://bowo.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       debug-log-manager
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DLM_VERSION', '1.6.4' );
define( 'DLM_SLUG', 'debug-log-manager' );
define( 'DLM_URL', plugins_url( '/', __FILE__ ) );
define( 'DLM_PATH', plugin_dir_path( __FILE__ ) );
// define( 'DLM_BASE', plugin_basename( __FILE__ ) );
// define( 'DLM_FILE', __FILE__ );

// Register autoloading classes
spl_autoload_register( 'dlm_autoloader' );

/**
 * Autoload classes defined by this plugin
 * 
 * @param  string $class_name e.g. \DLM\Classes\The_Name
 * @since 1.2.0
 */
function dlm_autoloader( $class_name ) {

    $namespace = 'DLM';

    if ( false !== strpos( $class_name, $namespace ) ) {

        // Assemble file path for the class
        $path = str_replace( $namespace, "", $class_name ); // \DLM\Classes\The_Name => \Classes\The_Name
        $path = str_replace( "\\", DIRECTORY_SEPARATOR, strtolower( $path ) ); // => /classes/the_name
        $path = str_replace( "_", "-" , $path ) . '.php'; // => /classes/the-name.php
        $path = str_replace( "classes" . DIRECTORY_SEPARATOR, "classes" . DIRECTORY_SEPARATOR . "class-", $path ); // => /classes/class-the-name.php
        $path = DLM_PATH . $path; // => /plugin-path/classes/class-the-name.php

        if ( file_exists( $path ) ) {
            require_once( $path );
        }

    }

}

/**
 * Code that runs on plugin activation
 * 
 * @since 1.0.0
 */
function dlm_on_activation() {
	$activation = new DLM\Classes\Activation;
    $activation->activate();
}

/**
 * Code that runs on plugin deactivation
 * 
 * @since 1.0.0
 */
function dlm_on_deactivation() {
    $deactivation = new DLM\Classes\Deactivation;
    $deactivation->deactivate();
}

// Register code that runs on plugin activation
register_activation_hook( __FILE__, 'dlm_on_activation');

// Register code that runs on plugin deactivation
register_deactivation_hook( __FILE__, 'dlm_on_deactivation' );

// Bootstrap the core functionalities of this plugin
require DLM_PATH . 'bootstrap.php';