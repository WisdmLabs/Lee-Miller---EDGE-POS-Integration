<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.wisdmlabs.com
 * @since      1.0.0
 *
 * @package    Wdm_Edge_Integration
 * @subpackage Wdm_Edge_Integration/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wdm_Edge_Integration
 * @subpackage Wdm_Edge_Integration/admin
 * @author     WisdmLabs <info@wisdmlabs.com>
 */
class Wdm_Edge_Integration_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The connection handler instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Wdm_Edge_Connection_Handler    $connection_handler    The connection handler instance.
	 */
	private $connection_handler;

	/**
	 * The customer manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Wdm_Edge_Customer_Manager    $customer_manager    The customer manager instance.
	 */
	private $customer_manager;

	/**
	 * The product manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Wdm_Edge_Product_Manager    $product_manager    The product manager instance.
	 */
	private $product_manager;

	/**
	 * The order manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Wdm_Edge_Order_Manager    $order_manager    The order manager instance.
	 */
	private $order_manager;

	/**
	 * The settings manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Wdm_Edge_Settings_Manager    $settings_manager    The settings manager instance.
	 */
	private $settings_manager;

	/**
	 * The admin UI manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Wdm_Edge_Admin_UI_Manager    $admin_ui_manager    The admin UI manager instance.
	 */
	private $admin_ui_manager;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Include and initialize the connection handler
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-wdm-edge-connection-handler.php';
		$this->connection_handler = new Wdm_Edge_Connection_Handler();

		// Include and initialize the customer manager
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-wdm-edge-customer-manager.php';
		$this->customer_manager = new Wdm_Edge_Customer_Manager( $this->connection_handler );

		// Include and initialize the product manager
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-wdm-edge-product-manager.php';
		$this->product_manager = new Wdm_Edge_Product_Manager( $this->connection_handler );

		// Include and initialize the order manager
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-wdm-edge-order-manager.php';
		$this->order_manager = new Wdm_Edge_Order_Manager( $this->connection_handler, $this->customer_manager );

		// Include and initialize the settings manager
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-wdm-edge-settings-manager.php';
		$this->settings_manager = new Wdm_Edge_Settings_Manager();

		// Include and initialize the admin UI manager
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-wdm-edge-admin-ui-manager.php';
		$this->admin_ui_manager = new Wdm_Edge_Admin_UI_Manager( $this->version );

		add_action( 'admin_menu', array( $this->admin_ui_manager, 'add_sftp_settings_page' ) );
		add_action( 'admin_init', array( $this->settings_manager, 'register_sftp_settings' ) );
		add_action( 'wp_ajax_edge_sftp_test_connection', array( $this->connection_handler, 'ajax_test_sftp_connection' ) );
		add_action( 'wp_ajax_edge_sftp_list_folders', array( $this->connection_handler, 'ajax_list_sftp_folders' ) );
		add_action( 'admin_menu', array( $this->admin_ui_manager, 'add_edt_sync_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Customer-related AJAX and cron hooks - now using Customer Manager
		add_action( 'wp_ajax_edt_sync_import_customers', array( $this->customer_manager, 'ajax_import_customers' ) );
		add_action( 'wp_ajax_edge_sync_existing_users', array( $this->customer_manager, 'ajax_sync_existing_users' ) );
		
		// Add cron hook for automated importing - now using Customer Manager
		add_action( 'edge_scheduled_import', array( $this->customer_manager, 'cron_import_customers' ) );
		add_action( 'edge_scheduled_product_import', array( $this->product_manager, 'cron_import_products' ) );
		
		// Add hook for processing next chunk in cron - now using managers
		add_action( 'edge_process_next_chunk', array( $this->customer_manager, 'process_next_chunk' ) );
		add_action( 'edge_process_next_product_chunk', array( $this->product_manager, 'process_next_product_chunk' ) );

		// Product-related AJAX hooks - now using Product Manager
		add_action( 'wp_ajax_edge_import_products', array( $this->product_manager, 'ajax_import_products' ) );
		
		// Check for cron settings changes - now using Settings Manager
		add_action( 'update_option_edge_customer_enable_cron', array( $this->settings_manager, 'handle_customer_cron_setting_change' ), 10, 2 );
		add_action( 'update_option_edge_product_enable_cron', array( $this->settings_manager, 'handle_product_cron_setting_change' ), 10, 2 );
        
        // Add custom cron intervals - now using Settings Manager
        add_filter( 'cron_schedules', array( $this->settings_manager, 'add_custom_cron_intervals' ) );
        
        // Add hook for WooCommerce order sync - now using Order Manager
        add_action('woocommerce_order_status_completed', array($this->order_manager, 'sync_order_to_edge'), 10, 1);
        add_action('woocommerce_order_status_processing', array($this->order_manager, 'sync_order_to_edge'), 10, 1);

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wdm_Edge_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wdm_Edge_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wdm-edge-integration-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wdm_Edge_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wdm_Edge_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wdm-edge-integration-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Register activation hook for the plugin.
	 * This is called statically from the main plugin file.
	 */
	public static function activate() {
		// Schedule the cron job if it's enabled in settings
		if (get_option('edge_enable_cron', 0)) {
			$interval = get_option('edge_cron_interval', 'daily');
			
			// Clear any existing scheduled events
			$timestamp = wp_next_scheduled('edge_scheduled_import');
			if ($timestamp) {
				wp_unschedule_event($timestamp, 'edge_scheduled_import');
			}
			
			// Schedule a new event
			wp_schedule_event(time(), $interval, 'edge_scheduled_import');
		}
	}
	
	/**
	 * Register deactivation hook for the plugin.
	 * This is called statically from the main plugin file.
	 */
	public static function deactivate() {
		// Clear any scheduled events
		$timestamp = wp_next_scheduled('edge_scheduled_import');
		if ($timestamp) {
			wp_unschedule_event($timestamp, 'edge_scheduled_import');
		}
	}

}
