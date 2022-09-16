<?php

// We're using the singleton design pattern
// https://code.tutsplus.com/articles/design-patterns-in-wordpress-the-singleton-pattern--wp-31621
// https://carlalexander.ca/singletons-in-wordpress/
// https://torquemag.io/2016/11/singletons-wordpress-good-evil/

/**
 * Main class of the plugin used to add functionalities
 *
 * @since 1.0.0
 */
class Debug_Log_Manager {

	// Refers to a single instance of this class
	private static $instance = null;

	// For the debug log object
	private $debug_log;

	/**
	 * Creates or returns a single instance of this class
	 *
	 * @return Debug_Log_Manager a single instance of this class.
	 */
	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

	/**
	 * Initialize plugin functionalities
	 */
	private function __construct() {

		// Register admin menu and subsequently the main admin page
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );

		// Add action links
		add_filter( 'plugin_action_links_'.DLM_SLUG.'/'.DLM_SLUG.'.php', [ $this, 'action_links' ] );

		if ( is_admin() && $this->is_dlm() ) {

			// Update footer text
			add_filter( 'admin_footer_text', [ $this, 'footer_text' ] );

			// Enqueue admin scripts and styles only on the plugin's main page
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );

		}

		// Register ajax calls
		$this->debug_log = new DLM\Classes\Debug_Log;
		add_action( 'wp_ajax_toggle_debugging', [ $this->debug_log, 'toggle_debugging' ] );
		add_action( 'wp_ajax_clear_log', [ $this->debug_log, 'clear_log' ] );

	}

	/**
	 * Check if current screen is this plugin's main page
	 *
	 * @since 1.0.0
	 */
	public function is_dlm() {

		$request_uri = sanitize_text_field( $_SERVER['REQUEST_URI'] ); // e.g. /wp-admin/index.php?page=page-slug

		if ( strpos( $request_uri, 'tools.php?page=' . DLM_SLUG ) !== false ) {
			return true; // Yes, this is the plugin's main page
		} else {
			return false; // Nope, this is NOT the plugin's page
		}

	}

	/**
	 * Register admin menu
	 *
	 * @since 1.0.0
	 */
	public function register_admin_menu() {

		add_submenu_page(
			'tools.php',
			'Debug Log Manager',
			'Debug Log Manager',
			'manage_options',
			'debug-log-manager',
			[ $this, 'create_main_page' ]
		);

	}

	/**
	 * Register action links
	 *
	 * @since 1.0.0
	 */
	public function action_links( $links ) {

		$settings_link = '<a href="tools.php?page='.DLM_SLUG.'">View Debug Log</a>';

		array_unshift($links, $settings_link); 

		return $links; 

	}

	/**
	 * Change admin footer text
	 *
	 * @since 1.0.0
	 */
	public function footer_text() {
		?>
			<a href="https://wordpress.org/plugins/debug-log-manager/" target="_blank">Debug Log Manager</a> (<a href="https://github.com/qriouslad/debug-log-manager" target="_blank">github</a>) is built using <a href="https://datatables.net/" target="_blank">DataTables.js</a> and <a href="https://github.com/AndrewHenderson/jSticky" target="_blank">jSticky</a>.
		<?php
	}

	/**
	 * Create the main admin page of the plugin
	 *
	 * @since 1.0.0
	 */
	public function create_main_page() {

		$log_file_path 		= get_option( 'debug_log_manager_file_path' );
		$log_file_shortpath = str_replace( sanitize_text_field( $_SERVER['DOCUMENT_ROOT'] ), "", $log_file_path );
		$file_size 			= size_format( wp_filesize( $log_file_path ) );

		?>

		<div class="wrap dlm-main-page">
			<div id="dlm-header" class="dlm-header">
				<div class="dlm-header-left">
					<h1 class="dlm-heading">Debug Log Manager <small>by <a href="https://bowo.io" target="_blank">bowo.io</a></small></h1>
				</div>
				<div class="dlm-header-right">
					<a href="https://wordpress.org/plugins/debug-log-manager/" target="_blank" class="dlm-header-action"><span>&#8505;</span> Info</a>
					<a href="https://wordpress.org/plugins/debug-log-manager/#reviews" target="_blank" class="dlm-header-action"><span>★</span> Review</a>
					<a href="https://wordpress.org/support/plugin/debug-log-manager/" target="_blank" class="dlm-header-action">✚ Feedback</a>
					<a href="https://paypal.me/qriouslad" target="_blank" class="dlm-header-action">&#10084; Donate</a>
				</div>
			</div>
			<div class="dlm-body">
				<?php
					$this->debug_log->get_entries();
				?>
			</div>
			<div class="dlm-footer">
				<div class="dlm-log-file"><strong>Log file</strong>: <?php echo esc_html( $log_file_shortpath ); ?> (<span id="dlm-log-file-size"><?php echo esc_html( $file_size ); ?></span>)<span id="dlm-clear-success" style="display:none;">Log file has been cleared...</span></div>
				<button id="dlm-log-clear" class="button button-small button-secondary dlm-log-clear">Clear Log</button>
			</div>
		</div>

		<?php

	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 1.0.0
	 */
	public function admin_scripts() {

		wp_enqueue_style( 'dlm-admin', DLM_URL . 'assets/css/admin.css', array(), DLM_VERSION );
		wp_enqueue_style( 'dlm-datatables', DLM_URL . 'assets/css/datatables.min.css', array(), DLM_VERSION );
		wp_enqueue_script( 'dlm-app', DLM_URL . 'assets/js/app.js', array(), DLM_VERSION, false );
		wp_enqueue_script( 'dlm-jsticky', DLM_URL . 'assets/js/jquery.jsticky.min.js', array( 'jquery' ), DLM_VERSION, false );
		wp_enqueue_script( 'dlm-datatables', DLM_URL . 'assets/js/datatables.min.js', array( 'jquery' ), DLM_VERSION, false );

		// Pass on data from PHP to JS

		$log_info = get_option( 'debug_log_manager' );
		$log_status = $log_info['status'];

		wp_localize_script( 
			'dlm-app', 
			'dlmvars', 
			array(
				'logStatus'	=> $log_status,
			) 
		);

	}

}

Debug_Log_Manager::get_instance();