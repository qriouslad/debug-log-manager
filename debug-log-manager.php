<?php

/**
 * Plugin Name:       Debug Log Manager
 * Plugin URI:        https://wordpress.org/plugins/debug-log-manager/
 * Description:       Log errors via WP_DEBUG. Create, view and clear debug.log file.
 * Version:           1.0.1
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

define( 'DLM_VERSION', '1.0.1' );
define( 'DLM_SLUG', 'debug-log-manager' );
define( 'DLM_URL', plugins_url( '/', __FILE__ ) );
define( 'DLM_PATH', plugin_dir_path( __FILE__ ) );
define( 'DLM_BASE', plugin_basename( __FILE__ ) );
define( 'DLM_FILE', __FILE__ );

// Code that runs on plugin activation
function on_activation() {
	require_once DLM_PATH . 'classes/activation.php';
	DLM\Classes\Activation::activate();
}

// Code that runs on plugin deactivation
function on_deactivation() {
	require_once DLM_PATH . 'classes/deactivation.php';
	DLM\Classes\Deactivation::deactivate();
}

// Register code that runs on plugin activation
register_activation_hook( __FILE__, 'on_activation');

// Register code that runs on plugin deactivation
register_deactivation_hook( __FILE__, 'on_deactivation' );

// Bootstrap the core functionalities of this plugin
require DLM_PATH . 'bootstrap.php';