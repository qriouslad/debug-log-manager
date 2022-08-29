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
	public static function deactivate() {

		delete_option( 'debug_log_manager' );

	}

}