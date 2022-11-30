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

	// For the wp-config object
	private $wp_config;

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

		// Do not display any admin notices while viewing logs.
		add_action( 'admin_notices', [ $this, 'suppress_admin_notices' ], 0 );
		add_action( 'all_admin_notices', [ $this, 'suppress_generic_notices' ], 0 );

		// Add action links
		add_filter( 'plugin_action_links_'.DLM_SLUG.'/'.DLM_SLUG.'.php', [ $this, 'action_links' ] );

		if ( is_admin() ) {

			if ( $this->is_dlm() ) {

				// Update footer text
				add_filter( 'admin_footer_text', [ $this, 'footer_text' ] );

				// Enqueue admin scripts and styles only on the plugin's main page
				add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );

			}

		}

		// Add admin bar icon if error logging is enabled and admin URL is not the plugin's main page. It will show on on the front end too (when logged-in), as we're also logging JavaScript errors.

        $default_value = array(
            'status'    => 'disabled',
            'on'        => date( 'Y-m-d H:i:s' ),
        );

		$logging_info 	= get_option( 'debug_log_manager', $default_value );
		$logging_status = $logging_info['status'];

		if ( ( $logging_status == 'enabled' ) && ! $this->is_dlm() ) {

			// https://developer.wordpress.org/reference/hooks/admin_bar_menu/
			add_action( 'admin_bar_menu', [ $this, 'admin_bar_icon' ] );

		}

		// Add dashboard widget
		
		add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_widget' ] );

		// Add inline CSS for the admin bar icon (menu item)
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_bar_icon_css' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'admin_bar_icon_css' ] );

		// Enqueue public scripts and styles
		add_action( 'wp_enqueue_scripts', [ $this, 'public_scripts' ] );

		// Register ajax calls
		$this->debug_log = new DLM\Classes\Debug_Log;
		$this->wp_config = new DLM\Classes\WP_Config_Transformer;
		add_action( 'wp_ajax_toggle_debugging', [ $this->debug_log, 'toggle_debugging' ] );
		add_action( 'wp_ajax_toggle_autorefresh', [ $this->debug_log, 'toggle_autorefresh' ] );
		add_action( 'wp_ajax_get_latest_entries', [ $this->debug_log, 'get_latest_entries' ] );
		add_action( 'wp_ajax_clear_log', [ $this->debug_log, 'clear_log' ] );
		add_action( 'wp_ajax_log_js_errors', [ $this->debug_log, 'log_js_errors' ] );
		add_action( 'wp_ajax_nopriv_log_js_errors', [ $this->debug_log, 'log_js_errors' ] );

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
			__( 'Debug Log Manager', 'debug-log-manager' ),
			__( 'Debug Log Manager', 'debug-log-manager' ),
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

		$settings_link = '<a href="tools.php?page='.DLM_SLUG.'">' . esc_html__( 'View Debug Log', 'debug-log-manager' ) . '</a>';

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
			<a href="https://wordpress.org/plugins/debug-log-manager/" target="_blank"><?php esc_html_e( 'Debug Log Manager', 'debug-log-manager' ); ?></a> (<a href="https://github.com/qriouslad/debug-log-manager" target="_blank">github</a>) <?php esc_html_e( 'is built using', 'debug-log-manager' ); ?> <a href="https://datatables.net/" target="_blank">DataTables.js</a>, <a href="https://github.com/AndrewHenderson/jSticky" target="_blank">jSticky</a> <?php esc_html_e( 'and', 'debug-log-manager' ); ?> <a href="https://github.com/kamranahmedse/jquery-toast-plugin" target="_blank">jQuery Toast</a>.
		<?php
	}

	/**
	 * Add debug icon in the admin bar
	 *
	 * @since 1.6.0
	 */
	public function admin_bar_icon( WP_Admin_Bar $wp_admin_bar ) {

		// https://developer.wordpress.org/reference/classes/wp_admin_bar/add_menu/
		// https://developer.wordpress.org/reference/classes/wp_admin_bar/add_node/ for more examples
		$wp_admin_bar->add_menu( array(
			'id'		=> DLM_SLUG,
			'parent'	=> 'top-secondary',
			'group'		=> null,
			'title'		=> '<span class="dashicons dashicons-warning"></span>',
			'href'		=> admin_url( 'tools.php?page=' . DLM_SLUG ),
			'meta'		=> array(
				'class'		=> 'dlm-admin-bar-icon',
				'title'		=> esc_attr__( 'Error logging is enabled. Click to access the Debug Log Manager.', 'debug-log-manager' )
			),
		) );

	}

	/**
	 * Create the main admin page of the plugin
	 *
	 * @since 1.0.0
	 */
	public function create_main_page() {

		$log_file_path 		= get_option( 'debug_log_manager_file_path' );
		$log_file_shortpath = str_replace( sanitize_text_field( $_SERVER['DOCUMENT_ROOT'] ), "", $log_file_path );
		$file_size 			= size_format( (int) filesize( $log_file_path ) );

		?>

		<div class="wrap dlm-main-page">
			<div id="dlm-header" class="dlm-header">
				<div class="dlm-header-left">
					<h1 class="dlm-heading"><?php esc_html_e( 'Debug Log Manager', 'debug-log-manager' ); ?> <small><?php esc_html_e( 'by', 'debug-log-manager' ); ?> <a href="https://bowo.io" target="_blank">bowo.io</a></small></h1>
				</div>
				<div class="dlm-header-right">
					<a href="https://wordpress.org/plugins/debug-log-manager/" target="_blank" class="dlm-header-action"><span>&#8505;</span> <?php esc_html_e( 'Info', 'debug-log-manager' ); ?></a>
					<a href="https://wordpress.org/plugins/debug-log-manager/#reviews" target="_blank" class="dlm-header-action"><span>★</span> <?php esc_html_e( 'Review', 'debug-log-manager' ); ?></a>
					<a href="https://wordpress.org/support/plugin/debug-log-manager/" target="_blank" class="dlm-header-action">✚ <?php esc_html_e( 'Feedback', 'debug-log-manager' ); ?></a>
					<a href="https://paypal.me/qriouslad" target="_blank" class="dlm-header-action">&#10084; <?php esc_html_e( 'Donate', 'debug-log-manager' ); ?></a>
				</div>
			</div>
			<div class="dlm-body">
				<div class="dlm-log-management">
					<div class="dlm-logging-status">
						<div class="dlm-log-status-toggle">
							<input type="checkbox" id="debug-log-checkbox" class="inset-3 debug-log-checkbox"><label for="debug-log-checkbox" class="green debug-log-switcher"></label>
						</div>
						<?php echo $this->debug_log->get_status(); ?>
					</div>
					<div class="dlm-autorefresh-status">
						<div class="dlm-log-autorefresh-toggle">
							<input type="checkbox" id="debug-autorefresh-checkbox" class="inset-3 debug-autorefresh-checkbox"><label for="debug-autorefresh-checkbox" class="green debug-autorefresh-switcher"></label>
						</div>
						<?php echo $this->debug_log->get_autorefresh_status(); ?>
					</div>
				</div>
				<?php
					$this->debug_log->get_entries_datatable();
				?>
			</div>
			<div class="dlm-footer">
				<div class="dlm-log-file">
					<div class="dlm-log-file-location"><strong><?php esc_html_e( 'Log file', 'debug-log-manager' ); ?></strong>: <?php echo esc_html( $log_file_shortpath ); ?> (<span id="dlm-log-file-size"><?php echo esc_html( $file_size ); ?></span>)</div>
					<button id="dlm-log-clear" class="button button-small button-secondary dlm-log-clear"><?php esc_html_e( 'Clear Log', 'debug-log-manager' ); ?></button>
				</div>
				<?php
					echo $this->wp_config->wpconfig_file( 'status' );
				?>
			</div>
		</div>

		<?php

	}

	/**
	 * To stop other plugins' admin notices overlaying in the Debug Log Manager UI, remove them.
	 *
	 * @hooked admin_notices
	 *
	 * @since 1.8.7
	 */
	public function suppress_admin_notices() {

		global $plugin_page;

		if ( DLM_SLUG === $plugin_page ) {
			remove_all_actions( 'admin_notices' );
		}

	}

	/**
	 * Suppress all generic notices on the plugin settings page
	 *
	 * @since 1.8.8
	 */
	public function suppress_generic_notices() {

		global $plugin_page;

		// Suppress all notices

		if ( DLM_SLUG === $plugin_page ) {

			remove_all_actions( 'all_admin_notices' );

		}

	}

	/**
	 * Add dashboard widget with latest errors
	 *
	 * @since 1.8.0
	 */
	public function add_dashboard_widget() {

		wp_add_dashboard_widget(
			'debug_log_manager_widget', // widget ID
			__( 'Debug Log | Latest Errors', 'debug-log-manager' ), // widget title
			array( $this, 'get_dashboard_widget_entries' ) // callback #1 to display entries
			// array( $this, 'dashboard_widget_settings' ) // callback #2 for configuration
		);

	}

	/**
	 * Load latest errors for dashboard widget
	 *
	 * @since 1.8.0
	 */
	public function get_dashboard_widget_entries() {

		$this->debug_log->get_dashboard_widget_entries();

	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 1.0.0
	 */
	public function admin_scripts() {

		wp_enqueue_style( 'dlm-admin', DLM_URL . 'assets/css/admin.css', array(), DLM_VERSION );
		wp_enqueue_style( 'dlm-datatables', DLM_URL . 'assets/css/datatables.min.css', array(), DLM_VERSION );
		wp_enqueue_style( 'dlm-toast', DLM_URL . 'assets/css/jquery.toast.min.css', array(), DLM_VERSION );
		wp_enqueue_script( 'dlm-app', DLM_URL . 'assets/js/admin.js', array(), DLM_VERSION, false );
		wp_enqueue_script( 'dlm-jsticky', DLM_URL . 'assets/js/jquery.jsticky.mod.min.js', array( 'jquery' ), DLM_VERSION, false );
		wp_enqueue_script( 'dlm-datatables', DLM_URL . 'assets/js/datatables.min.js', array( 'jquery' ), DLM_VERSION, false );
		wp_enqueue_script( 'dlm-toast', DLM_URL . 'assets/js/jquery.toast.min.js', array( 'jquery' ), DLM_VERSION, false );

		// Pass on data from PHP to JS

        $default_value = array(
            'status'    => 'disabled',
            'on'        => date( 'Y-m-d H:i:s' ),
        );

		$log_info = get_option( 'debug_log_manager', $default_value );
		$log_status = $log_info['status']; // WP_DEBUG log status: enabled / disabled

		if ( false !== get_option( 'debug_log_manager_autorefresh' ) ) {
			$autorefresh_status = get_option( 'debug_log_manager_autorefresh' );
		} else {
			$autorefresh_status = 'disabled';
	        update_option( 'debug_log_manager_autorefresh', $autorefresh_status, false );
		}

		wp_localize_script( 
			'dlm-app', 
			'dlmVars', 
			array(
				'logStatus'			=> $log_status,
				'autorefreshStatus'	=> $autorefresh_status,
				'jsErrorLogging'	=> array(
					'status'	=> '',
					'url'		=> admin_url( 'admin-ajax.php' ),
					'nonce'		=> wp_create_nonce( DLM_SLUG ),
					'action'	=> 'log_js_errors',
				),
				'toastMessage'		=> array(
					'toggleDebugSuccess'	=> __( 'Error logging has been enabled and the latest entries have been loaded.', 'debug-log-manager' ),
					'copySuccess'			=> __( 'Entries have been copied from an existing debug.log file.', 'debug-log-manager' ),
					'logFileCleared'		=> __( 'Log file has been cleared.', 'debug-log-manager' ),
					'paginationActive'		=> __( 'Pagination is active. Auto-refresh has been disabled.', 'debug-log-manager' ),
				),
				'dataTable'			=> array(
					'emptyTable'	=> __( 'No data available in table', 'debug-log-manager' ),
					'info'			=> __( 'Showing _START_ to _END_ of _TOTAL_ entries', 'debug-log-manager' ),
					'infoEmpty'		=> __( 'Showing 0 to 0 of 0 entries', 'debug-log-manager' ),
					'infoFiltered'	=> __( '(filtered from _MAX_ total entries)', 'debug-log-manager' ),
					'lengthMenu'	=> __( 'Show _MENU_ entries', 'debug-log-manager' ),
					'search'		=> __( 'Search:', 'debug-log-manager' ),
					'zeroRecords'	=> __( 'No matching records found', 'debug-log-manager' ),
					'paginate'		=> array(
					    'first'		=> __( 'First', 'debug-log-manager' ),
					    'last'		=> __( 'Last', 'debug-log-manager' ),
					    'next'		=> __( 'Next', 'debug-log-manager' ),
					    'previous'	=> __( 'Previous', 'debug-log-manager' ),
					),
				),
			) 
		);

	}

	/**
	 * Admin bar icon's inline css
	 *
	 * @since 1.6.0
	 */
	public function admin_bar_icon_css() {

		// https://developer.wordpress.org/reference/functions/wp_add_inline_style/
		wp_add_inline_style( 'admin-bar', '

			#wpadminbar .dlm-admin-bar-icon .dashicons { 
				font-family: dashicons; 
				font-size: 20px; 
				width: 20px; 
				height: 20px; 
				line-height: 32px; 
			}

			#wpadminbar .quicklinks ul li.dlm-admin-bar-icon a { 
				background: green;
			}

			#wpadminbar:not(.mobile) .ab-top-menu>li:hover>.ab-item { 
				transition: .25s;
			}

			#wpadminbar:not(.mobile) .ab-top-menu>li.dlm-admin-bar-icon:hover>.ab-item,
			#wpadminbar:not(.mobile) .ab-top-menu>li.dlm-admin-bar-icon>.ab-item:focus { 
				background: #006600; 
				color: #fff; 
			}

		' );

	}

	/**
	 * Enqueue public scripts
	 *
	 * @since 1.4.0
	 */
	public function public_scripts() {

		wp_enqueue_script( 'dlm-public', DLM_URL . 'assets/js/public.js', array( 'jquery' ), DLM_VERSION, false );

        $default_value = array(
            'status'    => 'disabled',
            'on'        => date( 'Y-m-d H:i:s' ),
        );

		$log_info = get_option( 'debug_log_manager', $default_value );
		$log_status = $log_info['status']; // WP_DEBUG log status: enabled / disabled

		wp_localize_script( 
			'dlm-public', 
			'dlmVars', 
			array(
				'logStatus'			=> $log_status,
				'jsErrorLogging'	=> array(
					'status'	=> '',
					'url'		=> admin_url( 'admin-ajax.php' ),
					'nonce'		=> wp_create_nonce( DLM_SLUG ),
					'action'	=> 'log_js_errors',
				),
			) 
		);
	}

}

Debug_Log_Manager::get_instance();