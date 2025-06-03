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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action( 'admin_menu', array( $this, 'add_sftp_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_sftp_settings' ) );
		add_action( 'wp_ajax_edge_sftp_test_connection', array( $this, 'ajax_test_sftp_connection' ) );
		add_action( 'wp_ajax_edge_sftp_list_folders', array( $this, 'ajax_list_sftp_folders' ) );
		add_action( 'admin_menu', array( $this, 'add_edt_sync_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wp_ajax_edt_sync_import_customers', array( $this, 'ajax_import_customers' ) );
		add_action( 'wp_ajax_edge_import_products', array( $this, 'ajax_import_products' ) );
		add_action( 'wp_ajax_edge_sync_existing_users', array( $this, 'ajax_sync_existing_users' ) );
		
		// Add cron hook for automated importing
		add_action( 'edge_scheduled_import', array( $this, 'cron_import_customers' ) );
		add_action( 'edge_scheduled_product_import', array( $this, 'cron_import_products' ) );
		
		// Add hook for processing next chunk in cron
		add_action( 'edge_process_next_chunk', array( $this, 'process_next_chunk' ) );
		add_action( 'edge_process_next_product_chunk', array( $this, 'process_next_product_chunk' ) );
		
		// Check for cron settings changes
		add_action( 'update_option_edge_customer_enable_cron', array( $this, 'handle_customer_cron_setting_change' ), 10, 2 );
		add_action( 'update_option_edge_product_enable_cron', array( $this, 'handle_product_cron_setting_change' ), 10, 2 );
        
        // Add custom cron intervals
        add_filter( 'cron_schedules', array( $this, 'add_custom_cron_intervals' ) );
        
        // Add hook for WooCommerce order sync
        add_action('woocommerce_order_status_completed', array($this, 'sync_order_to_edge'), 10, 1);
        add_action('woocommerce_order_status_processing', array($this, 'sync_order_to_edge'), 10, 1);

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
		
		// Add custom styles for the EDT Sync page
		if (isset($_GET['page']) && ($_GET['page'] === 'edt-sync' || $_GET['page'] === 'edt-sync-products' || $_GET['page'] === 'edge-sftp-setup')) {
			$custom_css = "
				/* Main container styling */
				.edt-sync-container {
					max-width: 1200px;
				}
				
				/* Dashboard card styling */
				.edt-card {
					background: #fff;
					border-radius: 8px;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
					margin-bottom: 20px;
					padding: 25px;
					position: relative;
					border-left: 5px solid #0073aa;
					transition: all 0.3s ease;
				}
				
				.edt-card:hover {
					box-shadow: 0 3px 8px rgba(0,0,0,0.15);
				}
				
				/* Product-specific styling */
				.edt-sync-products-page .edt-card {
					border-left-color: #0073aa;
				}
				
				/* Customer-specific styling */
				.edt-sync-customers-page .edt-card {
					border-left-color: #0073aa;
				}
				
				/* SFTP-specific styling */
				.edt-sftp-setup-page .edt-card {
					border-left-color: #0073aa;
				}
				
				/* SFTP card header icon */
				.edt-sftp-setup-page .edt-card h2:before {
					background-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 20 20\"><path fill=\"%230073aa\" d=\"M10 2c-4.42 0-8 3.58-8 8s3.58 8 8 8 8-3.58 8-8-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6zm-.71-5.29c.07.05.14.1.23.15l-.02.02L14 13l-3.03-3.19L10 9c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1c0 .1-.03.19-.06.28l2.54 2.25c.01-.17.06-.34.06-.53 0-1.66-1.34-3-3-3S7.34 7.34 7.34 9c0 1.66 1.34 3 3 3 .1 0 .2-.03.29-.05l.91.95z\"/></svg>');
				}
				
				/* SFTP button styling */
				.edt-sftp-setup-page .edt-button {
					background-color: #0073aa;
					color: white !important;
				}
				
				.edt-sftp-setup-page .edt-button:hover {
					background-color: #005177;
				}
				
				/* Page title action button */
				.page-title-action {
					margin-left: 10px;
					background: #f7f7f7;
					border: 1px solid #ccc;
					border-radius: 3px;
					box-shadow: 0 1px 0 #ccc;
					color: #555;
					display: inline-block;
					font-size: 13px;
					line-height: 26px;
					height: 28px;
					margin: 0;
					padding: 0 10px 1px;
					text-decoration: none;
					white-space: nowrap;
					transition: all 0.2s ease-in-out;
				}
				
				.page-title-action:hover {
					background: #fafafa;
					border-color: #999;
					color: #23282d;
				}
				
				.edt-card h2 {
					margin-top: 0;
					padding-bottom: 12px;
					border-bottom: 1px solid #eee;
					color: #23282d;
					font-size: 18px;
					display: flex;
					align-items: center;
				}
				
				.edt-card h2:before {
					content: '';
					display: inline-block;
					width: 18px;
					height: 18px;
					margin-right: 8px;
					background-repeat: no-repeat;
					background-size: contain;
					opacity: 0.7;
				}
				
				.edt-card h2:before {
					background-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 20 20\"><path fill=\"%230073aa\" d=\"M10 2c-4.42 0-8 3.58-8 8s3.58 8 8 8 8-3.58 8-8-3.58-8-8-8zm1 12H9v-2h2v2zm0-4H9V6h2v4z\"/></svg>');
				}
				
				/* Product card header icon */
				.edt-sync-products-page .edt-card h2:before {
					background-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 20 20\"><path fill=\"%230073aa\" d=\"M11 7H9V5h2v2zm0 0h2v6h-2V7zM7 7h2v6H7V7zm8-2h-1V3H6v2H5c-1.1 0-2 .9-2 2v7c0 1.1.9 2 2 2h1v2h8v-2h1c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2z\"/></svg>');
				}
				
				/* Customer card header icon */
				.edt-sync-customers-page .edt-card h2:before {
					background-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 20 20\"><path fill=\"%230073aa\" d=\"M10 9.25c-2.27 0-2.73-3.44-2.73-3.44C7 4.02 7.82 2 9.97 2c2.16 0 2.98 2.02 2.71 3.81 0 0-.41 3.44-2.68 3.44zm0 2.57L12.72 10c2.39 0 4.52 2.33 4.52 4.53v2.49s-3.65 1.13-7.24 1.13c-3.65 0-7.24-1.13-7.24-1.13v-2.49c0-2.25 1.94-4.48 4.47-4.48z\"/></svg>');
				}
				
				.edt-card-content {
					padding: 15px 0;
				}
				
				/* Button styling */
				.edt-button {
					background-color: #0073aa;
					border: none;
					border-radius: 4px;
					color: white !important;
					padding: 10px 20px;
					text-align: center;
					text-decoration: none;
					display: inline-block;
					font-size: 14px;
					margin: 10px 0;
					cursor: pointer;
					transition: background 0.3s;
				}
				
				.edt-button:hover {
					background-color: #005177;
				}
				
				/* Product-specific button */
				.edt-sync-products-page .edt-button {
					background-color: #0073aa;
				}
				
				.edt-sync-products-page .edt-button:hover {
					background-color: #005177;
				}
				
				/* Progress bar styling */
				#progress-container {
					margin-top: 20px;
				}
				
				.progress-bar-wrapper {
					background-color: #f1f1f1;
					border-radius: 5px;
					height: 25px;
					width: 100%;
					margin-top: 10px;
					overflow: hidden;
				}
				
				#progress-bar {
					background-color: #0073aa;
					height: 25px;
					width: 0%;
					border-radius: 5px;
					text-align: center;
					line-height: 25px;
					color: white;
					transition: width 0.3s;
					font-size: 12px;
				}
				
				.edt-sync-products-page #progress-bar {
					background-color: #0073aa;
				}
				
				#progress-text {
					text-align: center;
					margin-top: 5px;
					font-size: 13px;
					color: #555;
				}
				
				/* Form styling */
				.edt-form-row {
					margin-bottom: 15px;
				}
				
				.edt-form-row label {
					display: block;
					margin-bottom: 5px;
					font-weight: 500;
				}
				
				.edt-form-row input[type='text'],
				.edt-form-row input[type='number'],
				.edt-form-row select {
					width: 100%;
					max-width: 400px;
					padding: 8px;
					border-radius: 4px;
					border: 1px solid #ddd;
				}
				
				.edt-form-row .description {
					color: #666;
					font-style: italic;
					margin-top: 5px;
					font-size: 13px;
				}
				
				/* Custom minutes field */
				.custom-minutes-container {
					display: none;
					margin-top: 10px;
					padding-left: 20px;
				}
				
				/* Stats box */
				.import-stats {
					margin-top: 15px;
					background: #f8f8f8;
					padding: 15px;
					border-left: 4px solid #0073aa;
					border-radius: 4px;
				}
				
				.edt-sync-products-page .import-stats {
					border-left-color: #0073aa;
				}
				
				.import-stats h3 {
					margin-top: 0;
					margin-bottom: 10px;
					color: #23282d;
					font-size: 16px;
				}
				
				.import-stats ul {
					margin: 0;
					padding-left: 20px;
				}
				
				.import-stats li {
					margin-bottom: 5px;
				}
				
				/* Animation for import status */
				@keyframes fadeIn {
					from { opacity: 0; transform: translateY(-10px); }
					to { opacity: 1; transform: translateY(0); }
				}
				
				#import-status, #product-import-status {
					animation: fadeIn 0.3s ease-in-out;
				}
				
				/* Responsive adjustments */
				@media screen and (max-width: 782px) {
					.edt-card {
						padding: 15px;
					}
					
					.edt-form-row input[type='text'],
					.edt-form-row input[type='number'],
					.edt-form-row select {
						max-width: 100%;
					}
				}
				
				/* Customer-specific styling */
				.edt-sync-customers-page .edt-card {
					border-left-color: #0073aa;
				}
				
				/* Sync existing users specific styling */
				.edt-sync-existing-card {
					border-left-color: #0073aa !important;
				}
				
				.edt-sync-existing-card h2:before {
					background-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 20 20\"><path fill=\"%230073aa\" d=\"M10 2c-4.42 0-8 3.58-8 8s3.58 8 8 8 8-3.58 8-8-3.58-8-8-8zm-1 12.59L5.41 11 4 12.41l5 5 9-9L16.59 7 9 14.59z\"/></svg>') !important;
				}
				
				.edt-sync-customers-page .edt-card:has(#sync-existing-users) {
					border-left-color: #0073aa;
				}
				
				.edt-sync-customers-page .edt-card:has(#sync-existing-users) h2:before {
					background-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 20 20\"><path fill=\"%230073aa\" d=\"M10 2c-4.42 0-8 3.58-8 8s3.58 8 8 8 8-3.58 8-8-3.58-8-8-8zm-1 12.59L5.41 11 4 12.41l5 5 9-9L16.59 7 9 14.59z\"/></svg>');
				}
				
				.edt-sync-customers-page #sync-existing-users {
					background-color: #0073aa;
				}
				
				.edt-sync-customers-page #sync-existing-users:hover {
					background-color: #005177;
				}
				
				/* Sync progress bar styling */
				#sync-progress-container {
					margin-top: 20px;
				}
				
				#sync-progress-bar {
					background-color: #0073aa;
					height: 25px;
					width: 0%;
					border-radius: 5px;
					text-align: center;
					line-height: 25px;
					color: white;
					transition: width 0.3s;
					font-size: 12px;
				}
				
				/* Sync existing users settings form styling */
				.edt-sync-existing-card form {
					background: #f8f9fa;
					padding: 15px;
					border-radius: 5px;
					border: 1px solid #e1e5e9;
					margin-bottom: 20px;
				}
				
				.edt-sync-existing-card form .edt-form-row {
					margin-bottom: 15px;
				}
				
				.edt-sync-existing-card form .edt-button {
					background-color: #0073aa !important;
					margin-top: 10px;
				}
				
				.edt-sync-existing-card form .edt-button:hover {
					background-color: #005177 !important;
				}
				
				/* SFTP Setup Page Specific Styling */
				.edt-sftp-setup-page .current-folder-display {
					background: #f8f9fa;
					border: 1px solid #e1e5e9;
					border-radius: 5px;
					padding: 15px;
					margin-bottom: 20px;
					display: flex;
					justify-content: space-between;
					align-items: center;
				}
				
				.edt-sftp-setup-page .folder-info {
					display: flex;
					align-items: center;
					gap: 8px;
				}
				
				.edt-sftp-setup-page .folder-info code {
					background: #fff;
					padding: 4px 8px;
					border-radius: 3px;
					border: 1px solid #ddd;
					font-family: monospace;
				}
				
				.edt-sftp-setup-page .folder-browser {
					border: 1px solid #e1e5e9;
					border-radius: 5px;
					background: #fff;
					margin-bottom: 20px;
				}
				
				.edt-sftp-setup-page .folder-browser-header {
					background: #f8f9fa;
					padding: 15px;
					border-bottom: 1px solid #e1e5e9;
					border-radius: 5px 5px 0 0;
				}
				
				.edt-sftp-setup-page .folder-browser-header h3 {
					margin: 0 0 5px 0;
					color: #23282d;
				}
				
				.edt-sftp-setup-page .breadcrumbs {
					padding: 10px 15px;
					background: #f1f1f1;
					border-bottom: 1px solid #e1e5e9;
					font-size: 13px;
				}
				
				.edt-sftp-setup-page .breadcrumb-trail {
					display: flex;
					align-items: center;
					flex-wrap: wrap;
				}
				
				.edt-sftp-setup-page .breadcrumb-item a {
					color: #0073aa;
					text-decoration: none;
					padding: 2px 4px;
					border-radius: 3px;
					display: flex;
					align-items: center;
					gap: 4px;
				}
				
				.edt-sftp-setup-page .breadcrumb-item a:hover {
					background: #e1e5e9;
				}
				
				.edt-sftp-setup-page .breadcrumb-separator {
					margin: 0 5px;
					color: #666;
				}
				
				.edt-sftp-setup-page .folder-list {
					padding: 15px;
					min-height: 200px;
				}
				
				.edt-sftp-setup-page .folder-items {
					display: flex;
					flex-direction: column;
					gap: 5px;
				}
				
				.edt-sftp-setup-page .folder-item {
					display: flex;
					align-items: center;
					padding: 8px 12px;
					border-radius: 4px;
					transition: background-color 0.2s;
				}
				
				.edt-sftp-setup-page .folder-item:hover {
					background: #f8f9fa;
				}
				
				.edt-sftp-setup-page .folder-item.folder-up {
					background: #e8f4f8;
					border: 1px solid #b8dce8;
				}
				
				.edt-sftp-setup-page .folder-item.no-folders {
					background: #fff3cd;
					border: 1px solid #ffeaa7;
					color: #856404;
				}
				
				.edt-sftp-setup-page .folder-icon {
					margin-right: 8px;
					color: #0073aa;
				}
				
				.edt-sftp-setup-page .folder-link {
					color: #0073aa;
					text-decoration: none;
					font-weight: 500;
				}
				
				.edt-sftp-setup-page .folder-link:hover {
					text-decoration: underline;
				}
				
				.edt-sftp-setup-page .folder-loading,
				.edt-sftp-setup-page .folder-error {
					text-align: center;
					padding: 40px 20px;
					color: #666;
				}
				
				.edt-sftp-setup-page .folder-error {
					color: #d63638;
				}
				
				.edt-sftp-setup-page .folder-selection-actions {
					padding: 15px;
					border-top: 1px solid #e1e5e9;
					background: #f8f9fa;
					border-radius: 0 0 5px 5px;
				}
				
				.edt-sftp-setup-page .selected-folder {
					margin-top: 10px;
					padding: 10px;
					background: #d1edff;
					border: 1px solid #a7d0e4;
					border-radius: 4px;
					color: #0073aa;
				}
				
				.edt-sftp-setup-page .test-result {
					padding: 10px 12px;
					border-radius: 4px;
					display: flex;
					align-items: center;
					gap: 8px;
					font-weight: 500;
				}
				
				.edt-sftp-setup-page .test-result.success {
					background: #d1edff;
					border: 1px solid #a7d0e4;
					color: #0073aa;
				}
				
				.edt-sftp-setup-page .test-result.error {
					background: #fcf2f2;
					border: 1px solid #f1a7a7;
					color: #d63638;
				}
				
				.edt-sftp-setup-page .test-result.testing {
					background: #fff3cd;
					border: 1px solid #ffeaa7;
					color: #856404;
				}
				
				.edt-sftp-setup-page .status-item {
					display: flex;
					align-items: center;
					gap: 8px;
					margin-bottom: 15px;
					padding: 10px;
					border-radius: 4px;
				}
				
				.edt-sftp-setup-page .status-configured {
					background: #d1edff;
					border: 1px solid #a7d0e4;
					color: #0073aa;
				}
				
				.edt-sftp-setup-page .status-incomplete {
					background: #fff3cd;
					border: 1px solid #ffeaa7;
					color: #856404;
				}
				
				.edt-sftp-setup-page .status-details {
					margin-left: 25px;
					color: #666;
				}
				
				.edt-sftp-setup-page .status-details p {
					margin: 5px 0;
				}
				
				/* Spinning animation for loading states */
				@keyframes spin {
					from { transform: rotate(0deg); }
					to { transform: rotate(360deg); }
				}
				
				.edt-sftp-setup-page .spin {
					animation: spin 1s linear infinite;
				}
				
				/* Responsive adjustments for SFTP page */
				@media screen and (max-width: 782px) {
					.edt-sftp-setup-page .current-folder-display {
						flex-direction: column;
						align-items: flex-start;
						gap: 10px;
					}
					
					.edt-sftp-setup-page .breadcrumb-trail {
						flex-direction: column;
						align-items: flex-start;
					}
				}
			";
			wp_add_inline_style($this->plugin_name, $custom_css);
		}
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
	 * Add the EDGE Connection Setup page to the admin menu.
	 */
	public function add_sftp_settings_page() {
		add_options_page(
			'EDGE Connection Setup',
			'EDGE Connection Setup',
			'manage_options',
			'edge-sftp-setup',
			array( $this, 'render_sftp_settings_page' )
		);
	}

	/**
	 * Render the connection settings page.
	 */
	public function render_sftp_settings_page() {
		echo '<div class="wrap edt-sync-container edt-sftp-setup-page">';
		echo '<h1 class="wp-heading-inline">EDGE Connection Setup</h1>';
		echo '<hr class="wp-header-end">';
		include plugin_dir_path( __FILE__ ) . 'partials/wdm-edge-sftp-setup.php';
		echo '</div>';
	}

	/**
	 * Register settings for SFTP setup.
	 */
	public function register_sftp_settings() {
		// Connection settings group (renamed from SFTP-only)
		register_setting( 'edge_connection_options', 'edge_connection_type' );
		register_setting( 'edge_connection_options', 'edge_sftp_host' );
		register_setting( 'edge_connection_options', 'edge_sftp_username' );
		register_setting( 'edge_connection_options', 'edge_sftp_password' );
		register_setting( 'edge_connection_options', 'edge_sftp_port' );
		register_setting( 'edge_connection_options', 'edge_sftp_folder' );
		register_setting( 'edge_connection_options', 'edge_sftp_base_path' );
		
		// Keep backward compatibility with old group name
		register_setting( 'edge_sftp_options', 'edge_connection_type' );
		register_setting( 'edge_sftp_options', 'edge_sftp_host' );
		register_setting( 'edge_sftp_options', 'edge_sftp_username' );
		register_setting( 'edge_sftp_options', 'edge_sftp_password' );
		register_setting( 'edge_sftp_options', 'edge_sftp_port' );
		register_setting( 'edge_sftp_options', 'edge_sftp_folder' );
		register_setting( 'edge_sftp_options', 'edge_sftp_base_path' );
		
		// Customer cron settings group
		register_setting( 'edge_customer_cron_options', 'edge_customer_enable_cron', array(
			'sanitize_callback' => array($this, 'handle_customer_cron_toggle'),
		));
		register_setting( 'edge_customer_cron_options', 'edge_customer_cron_interval', array(
			'sanitize_callback' => array($this, 'handle_customer_cron_interval_change'),
		));
        register_setting( 'edge_customer_cron_options', 'edge_customer_cron_custom_minutes' );
        register_setting( 'edge_customer_cron_options', 'edge_customer_chunk_size' );
        
        // Product cron settings group
		register_setting( 'edge_product_cron_options', 'edge_product_enable_cron', array(
			'sanitize_callback' => array($this, 'handle_product_cron_toggle'),
		));
		register_setting( 'edge_product_cron_options', 'edge_product_cron_interval', array(
			'sanitize_callback' => array($this, 'handle_product_cron_interval_change'),
		));
        register_setting( 'edge_product_cron_options', 'edge_product_cron_custom_minutes' );
        register_setting( 'edge_product_cron_options', 'edge_product_chunk_size' );
        
        // Sync existing users settings
        register_setting( 'edge_sync_existing_options', 'edge_sync_existing_chunk_size' );
	}

	/**
	 * Handle toggling the customer cron setting during option save.
	 *
	 * @param mixed $value The new option value.
	 * @return mixed The sanitized option value.
	 */
	public function handle_customer_cron_toggle($value) {
		$old_value = get_option('edge_customer_enable_cron', 0);
		$value = (bool) $value ? 1 : 0;
		
		// Only process if the value actually changed
		if ($old_value != $value) {
			// If cron was disabled, remove any scheduled events
			if ($old_value && !$value) {
				$timestamp = wp_next_scheduled('edge_scheduled_import');
				if ($timestamp) {
					wp_unschedule_event($timestamp, 'edge_scheduled_import');
					error_log('Edge customer import cron job disabled and removed via settings');
				}
			} 
			// If cron was enabled, schedule the event
			else if ($value) {
				// Clear any existing scheduled events
				$timestamp = wp_next_scheduled('edge_scheduled_import');
				if ($timestamp) {
					wp_unschedule_event($timestamp, 'edge_scheduled_import');
				}
				
				// Schedule a new event
				$interval = get_option('edge_customer_cron_interval', 'daily');
				$result = wp_schedule_event(time(), $interval, 'edge_scheduled_import');
				if ($result === false) {
					error_log('Failed to schedule Edge customer cron job with interval: ' . $interval);
				} else {
					error_log('Edge customer import cron job scheduled with interval: ' . $interval . ' via settings');
				}
			}
		}
		
		return $value;
	}
	
	/**
	 * Handle changing the customer cron interval during option save.
	 *
	 * @param mixed $value The new option value.
	 * @return mixed The sanitized option value.
	 */
	public function handle_customer_cron_interval_change($value) {
		$old_value = get_option('edge_customer_cron_interval', 'daily');
		
		// Only process if the value actually changed and cron is enabled
		if ($old_value != $value && get_option('edge_customer_enable_cron', 0)) {
			// Clear any existing scheduled events
			$timestamp = wp_next_scheduled('edge_scheduled_import');
			if ($timestamp) {
				wp_unschedule_event($timestamp, 'edge_scheduled_import');
			}
			
			// If using custom minutes, verify the value
			if ($value === 'edge_customer_custom_minutes') {
				$custom_minutes = intval(get_option('edge_customer_cron_custom_minutes', 30));
				if ($custom_minutes < 5) {
					$custom_minutes = 5; // Minimum 5 minutes
					update_option('edge_customer_cron_custom_minutes', $custom_minutes);
				} elseif ($custom_minutes > 1440) {
					$custom_minutes = 1440; // Maximum 24 hours
					update_option('edge_customer_cron_custom_minutes', $custom_minutes);
				}
			}
			
			// Schedule a new event
			$result = wp_schedule_event(time(), $value, 'edge_scheduled_import');
			if ($result === false) {
				error_log('Failed to schedule Edge customer cron job with new interval: ' . $value);
			} else {
				error_log('Edge customer import cron job rescheduled with new interval: ' . $value);
			}
		}
		
		return $value;
	}
	
	/**
	 * Handle toggling the product cron setting during option save.
	 *
	 * @param mixed $value The new option value.
	 * @return mixed The sanitized option value.
	 */
	public function handle_product_cron_toggle($value) {
		$old_value = get_option('edge_product_enable_cron', 0);
		$value = (bool) $value ? 1 : 0;
		
		// Only process if the value actually changed
		if ($old_value != $value) {
			// If cron was disabled, remove any scheduled events
			if ($old_value && !$value) {
				$timestamp = wp_next_scheduled('edge_scheduled_product_import');
				if ($timestamp) {
					wp_unschedule_event($timestamp, 'edge_scheduled_product_import');
					error_log('Edge product import cron job disabled and removed via settings');
				}
			} 
			// If cron was enabled, schedule the event
			else if ($value) {
				// Clear any existing scheduled events
				$timestamp = wp_next_scheduled('edge_scheduled_product_import');
				if ($timestamp) {
					wp_unschedule_event($timestamp, 'edge_scheduled_product_import');
				}
				
				// Schedule a new event
				$interval = get_option('edge_product_cron_interval', 'daily');
				$result = wp_schedule_event(time(), $interval, 'edge_scheduled_product_import');
				if ($result === false) {
					error_log('Failed to schedule Edge product cron job with interval: ' . $interval);
				} else {
					error_log('Edge product import cron job scheduled with interval: ' . $interval . ' via settings');
				}
			}
		}
		
		return $value;
	}
	
	/**
	 * Handle changing the product cron interval during option save.
	 *
	 * @param mixed $value The new option value.
	 * @return mixed The sanitized option value.
	 */
	public function handle_product_cron_interval_change($value) {
		$old_value = get_option('edge_product_cron_interval', 'daily');
		
		// Only process if the value actually changed and cron is enabled
		if ($old_value != $value && get_option('edge_product_enable_cron', 0)) {
			// Clear any existing scheduled events
			$timestamp = wp_next_scheduled('edge_scheduled_product_import');
			if ($timestamp) {
				wp_unschedule_event($timestamp, 'edge_scheduled_product_import');
			}
			
			// If using custom minutes, verify the value
			if ($value === 'edge_product_custom_minutes') {
				$custom_minutes = intval(get_option('edge_product_cron_custom_minutes', 30));
				if ($custom_minutes < 5) {
					$custom_minutes = 5; // Minimum 5 minutes
					update_option('edge_product_cron_custom_minutes', $custom_minutes);
				} elseif ($custom_minutes > 1440) {
					$custom_minutes = 1440; // Maximum 24 hours
					update_option('edge_product_cron_custom_minutes', $custom_minutes);
				}
			}
			
			// Schedule a new event
			$result = wp_schedule_event(time(), $value, 'edge_scheduled_product_import');
			if ($result === false) {
				error_log('Failed to schedule Edge product cron job with new interval: ' . $value);
			} else {
				error_log('Edge product import cron job rescheduled with new interval: ' . $value);
			}
		}
		
		return $value;
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

	/**
	 * AJAX handler to test SFTP/FTP connection.
	 */
	public function ajax_test_sftp_connection() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}
		$host = $_POST['host'] ?? '';
		$username = $_POST['username'] ?? '';
		$password = $_POST['password'] ?? '';
		$port = intval($_POST['port'] ?? 22);
		$connection_type = $_POST['connection_type'] ?? 'sftp';
		
		if ( empty($host) || empty($username) || empty($password) ) {
			wp_send_json_error( 'Missing credentials' );
		}
		
		// Temporarily set the connection options for testing
		$old_host = get_option('edge_sftp_host');
		$old_username = get_option('edge_sftp_username');
		$old_password = get_option('edge_sftp_password');
		$old_port = get_option('edge_sftp_port');
		$old_connection_type = get_option('edge_connection_type');
		
		update_option('edge_sftp_host', $host);
		update_option('edge_sftp_username', $username);
		update_option('edge_sftp_password', $password);
		update_option('edge_sftp_port', $port);
		update_option('edge_connection_type', $connection_type);
		
		try {
			$connection = $this->create_connection();
			wp_send_json_success( ucfirst($connection_type) . ' connection successful' );
		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		} finally {
			// Restore original settings
			update_option('edge_sftp_host', $old_host);
			update_option('edge_sftp_username', $old_username);
			update_option('edge_sftp_password', $old_password);
			update_option('edge_sftp_port', $old_port);
			update_option('edge_connection_type', $old_connection_type);
		}
	}

	/**
	 * AJAX handler to list SFTP/FTP folders.
	 */
	public function ajax_list_sftp_folders() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}
		$host = $_POST['host'] ?? '';
		$username = $_POST['username'] ?? '';
		$password = $_POST['password'] ?? '';
		$port = intval($_POST['port'] ?? 22);
		$path = $_POST['path'] ?? '/';
		$connection_type = $_POST['connection_type'] ?? 'sftp';
		
		if ( empty($host) || empty($username) || empty($password) ) {
			wp_send_json_error( 'Missing credentials' );
		}
		
		// Temporarily set the connection options for testing
		$old_host = get_option('edge_sftp_host');
		$old_username = get_option('edge_sftp_username');
		$old_password = get_option('edge_sftp_password');
		$old_port = get_option('edge_sftp_port');
		$old_connection_type = get_option('edge_connection_type');
		
		update_option('edge_sftp_host', $host);
		update_option('edge_sftp_username', $username);
		update_option('edge_sftp_password', $password);
		update_option('edge_sftp_port', $port);
		update_option('edge_connection_type', $connection_type);
		
		try {
			$connection = $this->create_connection();
			$items = $this->list_directory($connection, $path);
			if ( $items === false ) {
				wp_send_json_error( 'Failed to list directory' );
			}
			$folders = array();
			foreach ( $items as $name => $info ) {
				if ( $name === '.' || $name === '..' ) continue;
				if ( $info['type'] === 2 ) { // 2 = directory
					$folders[] = rtrim($path, '/') . '/' . $name;
				}
			}
			$this->close_connection($connection);
			wp_send_json_success( $folders );
		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		} finally {
			// Restore original settings
			update_option('edge_sftp_host', $old_host);
			update_option('edge_sftp_username', $old_username);
			update_option('edge_sftp_password', $old_password);
			update_option('edge_sftp_port', $old_port);
			update_option('edge_connection_type', $old_connection_type);
		}
	}

	/**
	 * Add the EDT Sync page to the admin menu.
	 */
	public function add_edt_sync_page() {
		add_menu_page(
			'EDT Sync',
			'EDT Sync',
			'manage_options',
			'edt-sync',
			array( $this, 'render_edt_sync_page' ),
			'dashicons-update',
			6
		);
		
		// Add submenu for customers (same as parent menu)
		add_submenu_page(
			'edt-sync',
			'EDT Sync - Customers',
			'Customers',
			'manage_options',
			'edt-sync',
			array( $this, 'render_edt_sync_page' )
		);
		
		// Add submenu for products
		add_submenu_page(
			'edt-sync',
			'EDT Sync - Products',
			'Products',
			'manage_options',
			'edt-sync-products',
			array( $this, 'render_edt_sync_products_page' )
		);
	}

	/**
	 * Render the EDT Sync page with an import button.
	 */
	public function render_edt_sync_page() {
		echo '<div class="wrap edt-sync-container edt-sync-customers-page">';
		echo '<h1 class="wp-heading-inline">EDT Sync Dashboard - Customers</h1>';
		
		// Add link to view customers in WordPress Users admin
		$users_url = admin_url('users.php');
		echo '<a href="' . esc_url($users_url) . '" class="page-title-action">View Customers in WordPress</a>';
		
		echo '<hr class="wp-header-end">';
        
        // Manual import section
        echo '<div class="edt-card">';
        echo '<h2>Manual Import</h2>';
        echo '<div class="edt-card-content">';
        echo '<p>Manually import customers from the EDGE system by clicking the button below.</p>';
        echo '<button id="import-customers" class="edt-button">Import Customers</button>';
        echo '<div id="import-status"></div>';
        echo '<div id="progress-container" style="display: none;">';
        echo '<div class="progress-bar-wrapper">';
        echo '<div id="progress-bar"></div>';
        echo '</div>';
        echo '<p id="progress-text">0%</p>';
        echo '</div>';
        echo '</div>'; // End card content
        echo '</div>'; // End card
        
        // Customer statistics section
        echo '<div class="edt-card">';
        echo '<h2>Customer Statistics</h2>';
        echo '<div class="edt-card-content">';
        
        // Get customer count with EDGE sync
        global $wpdb;
        $customer_count = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->usermeta}
            WHERE meta_key = '_edge_sync'
            AND meta_value = '1'
        ");
        
        // Get last import stats
        $created = get_option('edge_ajax_import_created', 0);
        $updated = get_option('edge_ajax_import_updated', 0);
        $skipped = get_option('edge_ajax_import_skipped', 0);
        
        echo '<div class="import-stats">';
        echo '<h3>Current Status</h3>';
        echo '<ul>';
        echo '<li><strong>Total Customers from EDGE:</strong> ' . intval($customer_count) . '</li>';
        echo '</ul>';
        echo '</div>';
        
        echo '<div class="import-stats">';
        echo '<h3>Last Import</h3>';
        echo '<ul>';
        echo '<li><strong>Created:</strong> ' . $created . '</li>';
        echo '<li><strong>Updated:</strong> ' . $updated . '</li>';
        echo '<li><strong>Skipped:</strong> ' . $skipped . '</li>';
        echo '</ul>';
        echo '</div>';
        
        echo '</div>'; // End card content
        echo '</div>'; // End card
		
		// Add cron settings section
		$this->render_cron_settings('customer');
		
		// Sync existing users section
		echo '<div class="edt-card edt-sync-existing-card">';
		echo '<h2>Sync Existing WordPress Users</h2>';
		echo '<div class="edt-card-content">';
		echo '<p>Check all existing WordPress users and sync them to EDGE if they haven\'t been synced yet.</p>';
		
		// Add settings form for sync existing users
		$sync_chunk_size = get_option('edge_sync_existing_chunk_size', 25);
		
		echo '<form method="post" action="options.php" style="margin-bottom: 20px;">';
		settings_fields('edge_sync_existing_options');
		
		echo '<div class="edt-form-row">';
		echo '<label for="edge_sync_existing_chunk_size">Sync Chunk Size</label>';
		echo '<input type="number" id="edge_sync_existing_chunk_size" name="edge_sync_existing_chunk_size" value="' . esc_attr($sync_chunk_size) . '" min="5" max="100">';
		echo '<p class="description">Number of users to process per batch (minimum: 5, maximum: 100). Lower values reduce memory usage but take longer.</p>';
		echo '</div>';
		
		echo '<button type="submit" class="edt-button" style="background-color: #0073aa;">Save Sync Settings</button>';
		echo '</form>';
		
		echo '<button id="sync-existing-users" class="edt-button">Sync Existing Users to EDGE</button>';
		echo '<div id="sync-existing-status"></div>';
		echo '<div id="sync-progress-container" style="display: none;">';
		echo '<div class="progress-bar-wrapper">';
		echo '<div id="sync-progress-bar"></div>';
		echo '</div>';
		echo '<p id="sync-progress-text">0%</p>';
		echo '</div>';
		echo '</div>'; // End card content
		echo '</div>'; // End card
		
		echo '</div>'; // Close wrap container
		$this->enqueue_import_script();
	}
	
	/**
	 * Render the EDT Sync Products page.
	 */
	public function render_edt_sync_products_page() {
		echo '<div class="wrap edt-sync-container edt-sync-products-page">';
		echo '<h1 class="wp-heading-inline">EDT Sync Dashboard - Products</h1>';
		
		// Add link to view products in WooCommerce
		$products_url = admin_url('edit.php?post_type=product');
		echo '<a href="' . esc_url($products_url) . '" class="page-title-action">View Products in WooCommerce</a>';
		
		echo '<hr class="wp-header-end">';
		
		// Manual product import section
		echo '<div class="edt-card">';
		echo '<h2>Product Import</h2>';
		echo '<div class="edt-card-content">';
		echo '<p>Manually import products from the EDGE system by clicking the button below.</p>';
		echo '<button id="import-products" class="edt-button">Import Products</button>';
		echo '<div id="product-import-status"></div>';
		echo '<div id="progress-container" style="display: none;">';
		echo '<div class="progress-bar-wrapper">';
		echo '<div id="progress-bar"></div>';
		echo '</div>';
		echo '<p id="progress-text">0%</p>';
		echo '</div>';
		echo '</div>'; // End card content
		echo '</div>'; // End card
		
		// Product statistics section
		echo '<div class="edt-card">';
		echo '<h2>Product Statistics</h2>';
		echo '<div class="edt-card-content">';
		
		// Get product count with EDGE ID
		global $wpdb;
		$product_count = $wpdb->get_var("
			SELECT COUNT(*)
			FROM {$wpdb->postmeta} pm
			JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '_edge_id'
			AND p.post_type IN ('product', 'product_variation')
			AND p.post_status = 'publish'
		");
		
		// Get last import stats
		$created = get_option('edge_products_created', 0);
		$updated = get_option('edge_products_updated', 0);
		$skipped = get_option('edge_products_skipped', 0);
		
		echo '<div class="import-stats">';
		echo '<h3>Current Status</h3>';
		echo '<ul>';
		echo '<li><strong>Total Products from EDGE:</strong> ' . intval($product_count) . '</li>';
		echo '</ul>';
		echo '</div>';
		
		echo '<div class="import-stats">';
		echo '<h3>Last Import</h3>';
		echo '<ul>';
		echo '<li><strong>Created:</strong> ' . $created . '</li>';
		echo '<li><strong>Updated:</strong> ' . $updated . '</li>';
		echo '<li><strong>Skipped:</strong> ' . $skipped . '</li>';
		echo '</ul>';
		echo '</div>';
		
		echo '</div>'; // End card content
		echo '</div>'; // End card
		
		// Add cron settings section
		$this->render_cron_settings('product');
		
		echo '</div>'; // Close wrap container
		$this->enqueue_import_script();
	}
	
	/**
	 * Render the cron settings section.
	 * 
	 * @param string $type Either 'customer' or 'product'
	 */
	private function render_cron_settings($type = 'customer') {
		$option_prefix = 'edge_' . $type;
		$settings_group = $option_prefix . '_cron_options';
		
		$enable_cron = get_option($option_prefix . '_enable_cron', 0);
		$cron_interval = get_option($option_prefix . '_cron_interval', 'daily');
        $custom_minutes = get_option($option_prefix . '_cron_custom_minutes', 30);
        $chunk_size = get_option($option_prefix . '_chunk_size', $type === 'customer' ? 50 : 100);
		
        echo '<div class="edt-card">';
        echo '<h2>Automated Import Schedule</h2>';
        echo '<div class="edt-card-content">';
        echo '<p>Configure automatic imports of ' . $type . 's from the EDGE system on a schedule.</p>';
        
		echo '<form method="post" action="options.php">';
		
		// Include the appropriate settings group
		settings_fields($settings_group);
		
        echo '<div class="edt-form-row">';
        echo '<label for="' . $option_prefix . '_enable_cron">';
        echo '<input type="checkbox" id="' . $option_prefix . '_enable_cron" name="' . $option_prefix . '_enable_cron" value="1" ' . checked(1, $enable_cron, false) . '>';
        echo ' Enable Scheduled Import</label>';
        echo '<p class="description">When enabled, the system will automatically import ' . $type . ' data based on the frequency below.</p>';
        echo '</div>';
		
        echo '<div class="edt-form-row">';
        echo '<label for="' . $option_prefix . '_cron_interval">Import Frequency</label>';
        echo '<select id="' . $option_prefix . '_cron_interval" name="' . $option_prefix . '_cron_interval">';
        echo '<option value="hourly" ' . selected('hourly', $cron_interval, false) . '>Hourly</option>';
        echo '<option value="twicedaily" ' . selected('twicedaily', $cron_interval, false) . '>Twice Daily</option>';
        echo '<option value="daily" ' . selected('daily', $cron_interval, false) . '>Daily</option>';
        echo '<option value="weekly" ' . selected('weekly', $cron_interval, false) . '>Weekly</option>';
        echo '<option value="' . $option_prefix . '_custom_minutes" ' . selected($option_prefix . '_custom_minutes', $cron_interval, false) . '>Custom Minutes</option>';
        echo '</select>';
        echo '</div>';
        
        // Custom minutes field
        echo '<div class="edt-form-row custom-minutes-container" id="' . $type . '-custom-minutes-container">';
        echo '<label for="' . $option_prefix . '_cron_custom_minutes">Custom Minutes Interval</label>';
        echo '<input type="number" id="' . $option_prefix . '_cron_custom_minutes" name="' . $option_prefix . '_cron_custom_minutes" value="' . esc_attr($custom_minutes) . '" min="5" max="1440">';
        echo '<p class="description">Enter the number of minutes between imports (minimum: 5, maximum: 1440).</p>';
        echo '</div>';
        
        // Chunk size field
        echo '<div class="edt-form-row">';
        echo '<label for="' . $option_prefix . '_chunk_size">Chunk Size</label>';
        echo '<input type="number" id="' . $option_prefix . '_chunk_size" name="' . $option_prefix . '_chunk_size" value="' . esc_attr($chunk_size) . '" min="10" max="500">';
        echo '<p class="description">Number of ' . $type . 's to process per batch (minimum: 10, maximum: 500). Lower values reduce memory usage but take longer.</p>';
        echo '</div>';
		
		if ($enable_cron) {
			$hook_name = $type === 'customer' ? 'edge_scheduled_import' : 'edge_scheduled_product_import';
			$next_run = wp_next_scheduled($hook_name);
			if ($next_run) {
                echo '<div class="edt-form-row">';
                echo '<label>Next Scheduled Run</label>';
                echo '<div><strong>' . date_i18n('F j, Y @ g:i a', $next_run) . '</strong></div>';
                echo '</div>';
			}
		}
        
        echo '<button type="submit" class="edt-button">Save Schedule Settings</button>';
		
		echo '</form>';
        echo '</div>'; // End card content
        echo '</div>'; // End card
	}
	
	/**
	 * Handle changes to the cron setting.
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $new_value The new option value.
	 */
	public function handle_cron_setting_change($old_value, $new_value) {
		// If cron was disabled, remove any scheduled events
		if ($old_value && !$new_value) {
			$timestamp = wp_next_scheduled('edge_scheduled_import');
			if ($timestamp) {
				wp_unschedule_event($timestamp, 'edge_scheduled_import');
				error_log('Edge import cron job disabled and removed');
			}
		} 
		// If cron was enabled or interval changed, schedule the event
		else if ($new_value) {
			// Clear any existing scheduled events
			$timestamp = wp_next_scheduled('edge_scheduled_import');
			if ($timestamp) {
				wp_unschedule_event($timestamp, 'edge_scheduled_import');
				error_log('Removed existing Edge cron job');
			}
			
			// Schedule a new event
			$interval = get_option('edge_cron_interval', 'daily');
            
            // If using custom minutes, verify the value
            if ($interval === 'edge_custom_minutes') {
                $custom_minutes = intval(get_option('edge_cron_custom_minutes', 30));
                if ($custom_minutes < 5) {
                    $custom_minutes = 5; // Minimum 5 minutes
                    update_option('edge_cron_custom_minutes', $custom_minutes);
                } elseif ($custom_minutes > 1440) {
                    $custom_minutes = 1440; // Maximum 24 hours
                    update_option('edge_cron_custom_minutes', $custom_minutes);
                }
            }
            
			$result = wp_schedule_event(time(), $interval, 'edge_scheduled_import');
			if ($result === false) {
				error_log('Failed to schedule Edge cron job with interval: ' . $interval);
			} else {
				error_log('Edge import cron job scheduled with interval: ' . $interval);
			}
		}
	}
	
	/**
	 * Add custom cron intervals
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified cron schedules.
	 */
	public function add_custom_cron_intervals($schedules) {
		// Add customer custom minutes interval
		$customer_custom_minutes = intval(get_option('edge_customer_cron_custom_minutes', 30));
		if ($customer_custom_minutes < 1) {
			$customer_custom_minutes = 30; // Default to 30 minutes if invalid
		}
		
		$schedules['edge_customer_custom_minutes'] = array(
			'interval' => $customer_custom_minutes * 60,
			'display'  => sprintf(__('Every %d minutes (Customers)'), $customer_custom_minutes)
		);
		
		// Add product custom minutes interval
		$product_custom_minutes = intval(get_option('edge_product_cron_custom_minutes', 30));
		if ($product_custom_minutes < 1) {
			$product_custom_minutes = 30; // Default to 30 minutes if invalid
		}
		
		$schedules['edge_product_custom_minutes'] = array(
			'interval' => $product_custom_minutes * 60,
			'display'  => sprintf(__('Every %d minutes (Products)'), $product_custom_minutes)
		);
		
		return $schedules;
	}

	/**
	 * Cron callback for automatic customer import.
	 */
	public function cron_import_customers() {
		// Skip if cron is disabled
		if (!get_option('edge_customer_enable_cron', 0)) {
			return;
		}
		
		error_log('Starting scheduled Edge customer import');
		
		// Set higher PHP limits for the cron job
		@set_time_limit(600); // 10 minutes
		@ini_set('memory_limit', '256M');
		
		// Check if we're in the middle of processing a chunked import
		$import_in_progress = get_option('edge_import_in_progress', false);
		$current_chunk = get_option('edge_import_current_chunk', 0);
		$chunk_size = get_option('edge_customer_chunk_size', 50); // Get from settings
		$customers_data = null;
		
		// Retrieve the base path from SFTP settings
		$base_path = get_option('edge_sftp_folder', '/');

		// Construct the Inbox and Outbox paths
		$inbox_path = rtrim($base_path, '/') . '/Inbox';
		$outbox_path = rtrim($base_path, '/') . '/Outbox';

		error_log('Using Inbox path: ' . $inbox_path);
		error_log('Using Outbox path: ' . $outbox_path);

		// SFTP connection details
		$host = get_option('edge_sftp_host');
		$username = get_option('edge_sftp_username');
		$password = get_option('edge_sftp_password');
		$port = intval(get_option('edge_sftp_port', 22));

		require_once ABSPATH . 'vendor/autoload.php';

		try {
			// Step 1: Connect to SFTP/FTP
			$connection = $this->create_connection();
			
			// If we're not continuing an existing import, start a new one
			if (!$import_in_progress) {
				// Step 2: List files in the Inbox directory
				$files = $this->list_directory($connection, $inbox_path);
				if ($files === false) {
					error_log('Failed to list files in Inbox during scheduled import');
					$this->close_connection($connection);
					return;
				}

				$file_names = array_keys($files);
				
				// Step 3: Find the most recent FullCustomerList.json file
				$latest_file = '';
				$latest_time = 0;
				foreach ($file_names as $file) {
					if (substr($file, -strlen('FullCustomerList.json')) === 'FullCustomerList.json') {
						$file_time = $files[$file]['mtime'];
						if ($file_time > $latest_time) {
							$latest_time = $file_time;
							$latest_file = $file;
						}
					}
				}

				if (empty($latest_file)) {
					error_log('No FullCustomerList.json file found during scheduled import');
					$this->close_connection($connection);
					return;
				}

				// Step 4: Download and read the JSON file
				$json_content = $this->get_file($connection, $inbox_path . '/' . $latest_file);
				if ($json_content === false) {
					error_log('Failed to download file during scheduled import: ' . $latest_file);
					$this->close_connection($connection);
					return;
				}
				
				// Step 5: Decode JSON and check for errors
				$customers_data = json_decode($json_content, true);
				if (json_last_error() !== JSON_ERROR_NONE) {
					error_log('JSON decode error during scheduled import: ' . json_last_error_msg());
					$this->close_connection($connection);
					return;
				}
				
				// Store the data in a transient for chunked processing
				$total_customers = count($customers_data['Customers']);
				error_log('Starting chunked import of ' . $total_customers . ' customers');
				
				// Initialize import progress
				update_option('edge_import_in_progress', true);
				update_option('edge_import_current_chunk', 0);
				update_option('edge_import_total_chunks', ceil($total_customers / $chunk_size));
				update_option('edge_import_processed', 0);
				update_option('edge_import_created', 0);
				update_option('edge_import_updated', 0);
				update_option('edge_import_skipped', 0);
				update_option('edge_import_new_customers', array());
				update_option('edge_import_max_addresses', $customers_data['MaxAddresses'] ?? 0);
				update_option('edge_import_max_emails', $customers_data['MaxEmails'] ?? 0);
				update_option('edge_import_max_phones', $customers_data['MaxPhones'] ?? 0);
				
				// Store the customers data in chunks to avoid memory issues
				$chunks = array_chunk($customers_data['Customers'], $chunk_size);
				foreach ($chunks as $index => $chunk) {
					set_transient('edge_import_chunk_' . $index, $chunk, DAY_IN_SECONDS);
				}
				
				// Free up memory
				unset($customers_data);
				unset($json_content);
				unset($chunks);
				
				// // Only import products if product cron is enabled (respects separate product import schedule)
				// if (get_option('edge_product_enable_cron', 0)) {
				// 	error_log('Product cron is enabled, importing products during customer import');
				// 	$this->import_products();
				// } else {
				// 	error_log('Product cron is disabled, skipping product import during customer import');
				// }
			}
			
			// Get the current chunk data
			$current_chunk_data = get_transient('edge_import_chunk_' . $current_chunk);
			if (!$current_chunk_data) {
				error_log('Failed to retrieve chunk ' . $current_chunk . ' data');
				$this->cleanup_import_progress();
				return;
			}
			
			// Process the current chunk
			$processed = get_option('edge_import_processed', 0);
			$created = get_option('edge_import_created', 0);
			$updated = get_option('edge_import_updated', 0);
			$skipped = get_option('edge_import_skipped', 0);
			$new_customers = get_option('edge_import_new_customers', array());
			
			error_log('Processing chunk ' . ($current_chunk + 1) . ' of ' . get_option('edge_import_total_chunks'));
			
			foreach ($current_chunk_data as $customer) {
				$processed++;
				
				$email = $customer['PairValue']['Emails'][0]['PairValue']['EmailEmail'] ?? '';
				if (empty($email)) {
					$skipped++;
					continue;
				}

				$user = get_user_by('email', $email);
				$is_new_user = false;
				
				if (!$user) {
					// Create a new user
					$user_id = wp_insert_user([
						'user_login' => $email,
						'user_email' => $email,
						'first_name' => $customer['PairValue']['CustomerFirstName'],
						'last_name' => $customer['PairValue']['CustomerLastName'],
						'user_pass' => wp_generate_password(),
					]);

					if (is_wp_error($user_id)) {
						error_log('User creation error during scheduled import: ' . $user_id->get_error_message());
						$skipped++;
						continue;
					}

					$user = get_user_by('id', $user_id);
					$is_new_user = true;
					$created++;
					
					// Send account creation email
					$this->send_new_user_notification($user_id, $customer['PairValue']['CustomerFirstName'], $customer['PairValue']['CustomerLastName']);
				} else {
					$updated++;
				}

				// Update user meta
				update_user_meta($user->ID, '_edge_sync', true);
				update_user_meta($user->ID, '_edge_key', $customer['Key']);
				update_user_meta($user->ID, '_edge_id', $customer['PairValue']['Customerid']);

				// Check if the user was not synched before
				$is_new_sync = !get_user_meta($user->ID, '_edge_synced_before', true);
				if ($is_new_sync) {
					// Mark user as synced
					update_user_meta($user->ID, '_edge_synced_before', true);
					
					// Copy the entire customer structure and only update the WebTransferWebID
					$customer_copy = $customer;
					$customer_copy['PairValue']['CustomerTransfer']['WebTransferWebID'] = $user->ID;
					$new_customers[] = $customer_copy;
				}
			}
			
			// Update progress
			update_option('edge_import_processed', $processed);
			update_option('edge_import_created', $created);
			update_option('edge_import_updated', $updated);
			update_option('edge_import_skipped', $skipped);
			update_option('edge_import_new_customers', $new_customers);
			
			// Clean up the current chunk transient
			delete_transient('edge_import_chunk_' . $current_chunk);
			
			// Move to the next chunk
			$current_chunk++;
			update_option('edge_import_current_chunk', $current_chunk);
			
			// Check if we've processed all chunks
			if ($current_chunk >= get_option('edge_import_total_chunks')) {
				// All chunks processed, create the output file
				$this->finalize_chunked_import($connection, $outbox_path);
			} else {
				// Schedule the next chunk to be processed immediately
				wp_schedule_single_event(time() + 10, 'edge_process_next_chunk');
			}
			
		} catch (\Exception $e) {
			error_log('Error during scheduled import: ' . $e->getMessage());
			$this->cleanup_import_progress();
		}
	}
	
	/**
	 * Process the next chunk of customer imports
	 */
	public function process_next_chunk() {
		// This will be called by the single event scheduled in cron_import_customers
		$this->cron_import_customers();
	}
	
	/**
	 * Finalize the chunked import by creating and uploading the output file
	 */
	private function finalize_chunked_import($connection, $outbox_path) {
		error_log('Finalizing chunked import');
		
		try {
			$prefix_counter = get_option('edge_prefix_counter', 1);
			$new_customers = get_option('edge_import_new_customers', array());
			
			// Create a new JSON file with the same structure as the original
			$new_file_name = $prefix_counter . '-CustomerList.json';
			$new_json_data = [
				'Customers' => $new_customers,
				'MaxAddresses' => get_option('edge_import_max_addresses', 0),
				'MaxEmails' => get_option('edge_import_max_emails', 0),
				'MaxPhones' => get_option('edge_import_max_phones', 0)
			];
			
			$new_json_content = json_encode($new_json_data, JSON_PRETTY_PRINT);
			if (json_last_error() !== JSON_ERROR_NONE) {
				error_log('JSON encode error during scheduled import: ' . json_last_error_msg());
				$this->cleanup_import_progress();
				return;
			}
			
			$temp_file = plugin_dir_path(__FILE__) . $new_file_name;
			file_put_contents($temp_file, $new_json_content);

			// Upload the new JSON file to the Outbox
			$this->upload_file($connection, $outbox_path . '/' . $new_file_name, $temp_file);
			
			// Close connection properly
			$this->close_connection($connection);
			
			// Clean up temporary file
			@unlink($temp_file);
			error_log('Deleted local temporary file: ' . $temp_file);

			// Increment the prefix counter
			update_option('edge_prefix_counter', $prefix_counter + 1);
			
			$processed = get_option('edge_import_processed', 0);
			$created = get_option('edge_import_created', 0);
			$updated = get_option('edge_import_updated', 0);
			$skipped = get_option('edge_import_skipped', 0);
			
			error_log('Scheduled import completed: processed ' . $processed . ', created ' . $created . 
				', updated ' . $updated . ', skipped ' . $skipped . ', exported ' . count($new_customers));
				
			// Clean up all import progress data
			$this->cleanup_import_progress();
			
		} catch (\Exception $e) {
			error_log('Error finalizing import: ' . $e->getMessage());
			$this->cleanup_import_progress();
		}
	}
	
	/**
	 * Clean up all import progress data
	 */
	private function cleanup_import_progress() {
		// Remove all transients and options related to the import
		$total_chunks = get_option('edge_import_total_chunks', 0);
		for ($i = 0; $i < $total_chunks; $i++) {
			delete_transient('edge_import_chunk_' . $i);
		}
		
		delete_option('edge_import_in_progress');
		delete_option('edge_import_current_chunk');
		delete_option('edge_import_total_chunks');
		delete_option('edge_import_processed');
		delete_option('edge_import_created');
		delete_option('edge_import_updated');
		delete_option('edge_import_skipped');
		delete_option('edge_import_new_customers');
		delete_option('edge_import_max_addresses');
		delete_option('edge_import_max_emails');
		delete_option('edge_import_max_phones');
	}

	/**
	 * Enqueue the script for handling the import button click.
	 */
	public function enqueue_import_script() {
		wp_enqueue_script( 'edt-sync-import', plugin_dir_url( __FILE__ ) . 'js/edt-sync-import.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( 'edt-sync-import', 'edtSyncAjax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		
        // Add inline JS for handling custom minutes visibility and product import
        $custom_js = "
            jQuery(document).ready(function($) {
                // Function to update product statistics dynamically
                function updateProductStatistics(stats) {
                    // Update the statistics in the Product Statistics section
                    var statsContainer = $('.edt-sync-products-page .import-stats').last();
                    if (statsContainer.length) {
                        var statsHtml = '<h3>Last Import</h3>' +
                            '<ul>' +
                            '<li><strong>Created:</strong> ' + stats.created + '</li>' +
                            '<li><strong>Updated:</strong> ' + stats.updated + '</li>' +
                            '<li><strong>Skipped:</strong> ' + stats.skipped + '</li>' +
                            '</ul>';
                        statsContainer.html(statsHtml);
                    }
                }
                
                // Function to toggle custom minutes field for customers
                function toggleCustomerCustomMinutes() {
                    var selected = $('#edge_customer_cron_interval').val();
                    if (selected === 'edge_customer_custom_minutes') {
                        $('#customer-custom-minutes-container').show();
                    } else {
                        $('#customer-custom-minutes-container').hide();
                    }
                }
                
                // Function to toggle custom minutes field for products
                function toggleProductCustomMinutes() {
                    var selected = $('#edge_product_cron_interval').val();
                    if (selected === 'edge_product_custom_minutes') {
                        $('#product-custom-minutes-container').show();
                    } else {
                        $('#product-custom-minutes-container').hide();
                    }
                }
                
                // Initialize form visibility on page load
                toggleCustomerCustomMinutes();
                toggleProductCustomMinutes();
                
                // Handle changes to select fields
                $('#edge_customer_cron_interval').on('change', toggleCustomerCustomMinutes);
                $('#edge_product_cron_interval').on('change', toggleProductCustomMinutes);
                
                // Handle product import button click with chunked processing
                $('#import-products').on('click', function() {
                    var button = $(this);
                    button.prop('disabled', true);
                    $('#product-import-status').html('<p>Importing products... This may take a few minutes.</p>');
                    
                    // Show progress container
                    $('#progress-container').show();
                    $('#progress-bar').css('width', '0%').text('0%');
                    $('#progress-text').text('Starting import...');
                    
                    // Start chunked import process
                    function processProductChunk(chunk = 0) {
                        $.ajax({
                            url: edtSyncAjax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'edge_import_products',
                                chunk: chunk
                            },
                            success: function(response) {
                                if (response.success) {
                                    if (response.data.isComplete) {
                                        // Import completed
                                        $('#progress-bar').css('width', '100%').text('100%');
                                        $('#progress-text').text('Import completed!');
                                        $('#product-import-status').html('<p>Product import completed successfully.</p><ul>' +
                                            '<li>Total: ' + response.data.stats.total + '</li>' +
                                            '<li>Created: ' + response.data.stats.created + '</li>' +
                                            '<li>Updated: ' + response.data.stats.updated + '</li>' +
                                            '<li>Skipped: ' + response.data.stats.skipped + '</li>' +
                                        '</ul>');
                                        button.prop('disabled', false);
                                        
                                        // Update statistics dynamically instead of page reload
                                        updateProductStatistics(response.data.stats);
                                        
                                        // Hide progress container with smooth fade after showing completion
                                        setTimeout(function() {
                                            $('#progress-container').fadeOut(500);
                                        }, 2000);
                                    } else {
                                        // Continue with next chunk
                                        $('#progress-bar').css('width', response.data.progress + '%').text(response.data.progress + '%');
                                        $('#progress-text').text(response.data.message + ' (' + response.data.stats.processed + ' processed)');
                                        
                                        // Process next chunk after a short delay
                                        setTimeout(function() {
                                            processProductChunk(response.data.nextChunk);
                                        }, 100);
                                    }
                                } else {
                                    $('#product-import-status').html('<p>Error: ' + response.data + '</p>');
                                    $('#progress-container').hide();
                                    button.prop('disabled', false);
                                }
                            },
                            error: function() {
                                $('#product-import-status').html('<p>Error: Could not complete the import. Please check the logs.</p>');
                                $('#progress-container').hide();
                                button.prop('disabled', false);
                            }
                        });
                    }
                    
                    // Start the import
                    processProductChunk(0);
                });
                
                // Handle sync existing users button click
                $('#sync-existing-users').on('click', function() {
                    var button = $(this);
                    button.prop('disabled', true);
                    $('#sync-existing-status').html('<p>Checking existing WordPress users... This may take a few minutes.</p>');
                    
                    // Show progress container
                    $('#sync-progress-container').show();
                    $('#sync-progress-bar').css('width', '0%').text('0%');
                    $('#sync-progress-text').text('Starting sync...');
                    
                    // Start chunked sync process
                    function processSyncChunk(chunk = 0) {
                        $.ajax({
                            url: edtSyncAjax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'edge_sync_existing_users',
                                chunk: chunk
                            },
                            success: function(response) {
                                if (response.success) {
                                    if (response.data.isComplete) {
                                        // Sync completed
                                        $('#sync-progress-bar').css('width', '100%').text('100%');
                                        $('#sync-progress-text').text('Sync completed!');
                                        $('#sync-existing-status').html('<p>Existing users sync completed successfully.</p><ul>' +
                                            '<li>Total Users Checked: ' + response.data.stats.total + '</li>' +
                                            '<li>Already Synced: ' + response.data.stats.already_synced + '</li>' +
                                            '<li>Newly Synced to EDGE: ' + response.data.stats.synced + '</li>' +
                                            '<li>Skipped: ' + response.data.stats.skipped + '</li>' +
                                        '</ul>');
                                        button.prop('disabled', false);
                                        
                                        // Hide progress container with smooth fade after showing completion
                                        setTimeout(function() {
                                            $('#sync-progress-container').fadeOut(500);
                                        }, 2000);
                                    } else {
                                        // Continue with next chunk
                                        $('#sync-progress-bar').css('width', response.data.progress + '%').text(response.data.progress + '%');
                                        $('#sync-progress-text').text(response.data.message + ' (' + response.data.stats.processed + ' processed)');
                                        
                                        // Process next chunk after a short delay
                                        setTimeout(function() {
                                            processSyncChunk(response.data.nextChunk);
                                        }, 100);
                                    }
                                } else {
                                    $('#sync-existing-status').html('<p>Error: ' + response.data + '</p>');
                                    $('#sync-progress-container').hide();
                                    button.prop('disabled', false);
                                }
                            },
                            error: function() {
                                $('#sync-existing-status').html('<p>Error: Could not complete the sync. Please check the logs.</p>');
                                $('#sync-progress-container').hide();
                                button.prop('disabled', false);
                            }
                        });
                    }
                    
                    // Start the sync
                    processSyncChunk(0);
                });
            });
        ";
        wp_add_inline_script('edt-sync-import', $custom_js);
	}

	/**
	 * AJAX handler for importing customers.
	 */
	public function ajax_import_customers() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		// Set timeout to prevent script termination
		set_time_limit(300); // 5 minutes
		@ini_set('memory_limit', '256M'); // Increase memory limit for large imports

		// Get the current chunk from the request
		$current_chunk = isset($_POST['chunk']) ? intval($_POST['chunk']) : 0;
		$chunk_size = get_option('edge_customer_chunk_size', 50); // Get from settings
		
		// Check if this is the first chunk (start of import)
		$is_first_chunk = ($current_chunk === 0);
		
		// For the first chunk, we need to fetch the data from SFTP
		if ($is_first_chunk) {
			// Retrieve the base path from SFTP settings
			$base_path = get_option('edge_sftp_folder', '/');

			// Construct the Inbox and Outbox paths
			$inbox_path = rtrim($base_path, '/') . '/Inbox';
			$outbox_path = rtrim($base_path, '/') . '/Outbox';

			error_log('Using Inbox path: ' . $inbox_path);
			error_log('Using Outbox path: ' . $outbox_path);

			// SFTP connection details
			$host = get_option('edge_sftp_host');
			$username = get_option('edge_sftp_username');
			$password = get_option('edge_sftp_password');
			$port = intval(get_option('edge_sftp_port', 22));

			require_once ABSPATH . 'vendor/autoload.php';

			try {
				// Step 1: Connect to SFTP
				$connection = $this->create_connection();
				
				// Step 2: List files in the Inbox directory
				$files = $this->list_directory($connection, $inbox_path);
				if ($files === false) {
					error_log('Failed to list files in Inbox');
					$this->close_connection($connection);
					wp_send_json_error('Failed to list files in Inbox');
				}

				$file_names = array_keys($files);
				error_log('Files in Inbox: ' . implode(', ', $file_names));

				// Step 3: Find the most recent FullCustomerList.json file
				$latest_file = '';
				$latest_time = 0;
				foreach ($file_names as $file) {
					error_log('Checking file: ' . $file);
					if (substr($file, -strlen('FullCustomerList.json')) === 'FullCustomerList.json') {
						$file_time = $files[$file]['mtime'];
						if ($file_time > $latest_time) {
							$latest_time = $file_time;
							$latest_file = $file;
						}
					}
				}

				if (empty($latest_file)) {
					error_log('No FullCustomerList.json file found');
					$this->close_connection($connection);
					wp_send_json_error('No FullCustomerList.json file found');
				}

				// Step 4: Download and read the JSON file
				$json_content = $this->get_file($connection, $inbox_path . '/' . $latest_file);
				if ($json_content === false) {
					error_log('Failed to download file: ' . $latest_file);
					$this->close_connection($connection);
					wp_send_json_error('Failed to download file: ' . $latest_file);
				}
				
				// Close connection after getting the file
				$this->close_connection($connection);
				
				error_log('JSON content length: ' . strlen($json_content)); // Log length instead of content

				// Step 5: Decode JSON and check for errors
				$customers_data = json_decode($json_content, true);
				if (json_last_error() !== JSON_ERROR_NONE) {
					error_log('JSON decode error: ' . json_last_error_msg());
					wp_send_json_error('JSON decode error: ' . json_last_error_msg());
				}

				$total_customers = count($customers_data['Customers']);
				error_log('Number of customers: ' . $total_customers);
				
				// Store total customers and other metadata for the import process
				update_option('edge_ajax_import_total_customers', $total_customers);
				update_option('edge_ajax_import_total_chunks', ceil($total_customers / $chunk_size));
				update_option('edge_ajax_import_processed', 0);
				update_option('edge_ajax_import_created', 0);
				update_option('edge_ajax_import_updated', 0);
				update_option('edge_ajax_import_skipped', 0);
				update_option('edge_ajax_import_new_customers', array());
				update_option('edge_ajax_import_max_addresses', $customers_data['MaxAddresses'] ?? 0);
				update_option('edge_ajax_import_max_emails', $customers_data['MaxEmails'] ?? 0);
				update_option('edge_ajax_import_max_phones', $customers_data['MaxPhones'] ?? 0);
				
				// Store the SFTP connection details for later chunks
				update_option('edge_ajax_import_sftp_host', $host);
				update_option('edge_ajax_import_sftp_username', $username);
				update_option('edge_ajax_import_sftp_password', $password);
				update_option('edge_ajax_import_sftp_port', $port);
				update_option('edge_ajax_import_outbox_path', $outbox_path);
				
				// Store the customers data in chunks to avoid memory issues
				$chunks = array_chunk($customers_data['Customers'], $chunk_size);
				foreach ($chunks as $index => $chunk) {
					set_transient('edge_ajax_import_chunk_' . $index, $chunk, HOUR_IN_SECONDS);
				}
				
				// Free up memory
				unset($customers_data);
				unset($json_content);
				unset($chunks);
			} catch (\Exception $e) {
				error_log('SFTP error: ' . $e->getMessage());
				wp_send_json_error($e->getMessage());
				return;
			}
		}
		
		// Process the current chunk
		try {
			// Get the current chunk data
			$current_chunk_data = get_transient('edge_ajax_import_chunk_' . $current_chunk);
			if (!$current_chunk_data) {
				error_log('Failed to retrieve chunk ' . $current_chunk . ' data');
				wp_send_json_error('Failed to retrieve chunk data. The import process may have timed out.');
				return;
			}
			
			// Get the current progress
			$processed = get_option('edge_ajax_import_processed', 0);
			$created = get_option('edge_ajax_import_created', 0);
			$updated = get_option('edge_ajax_import_updated', 0);
			$skipped = get_option('edge_ajax_import_skipped', 0);
			$new_customers = get_option('edge_ajax_import_new_customers', array());
			
			error_log('Processing chunk ' . ($current_chunk + 1) . ' of ' . get_option('edge_ajax_import_total_chunks'));
			
			// Process each customer in the current chunk
			foreach ($current_chunk_data as $customer) {
				$processed++;
				
				$email = $customer['PairValue']['Emails'][0]['PairValue']['EmailEmail'] ?? '';
				if (empty($email)) {
					$skipped++;
					continue;
				}

				$user = get_user_by('email', $email);
				$is_new_user = false;
				
				if (!$user) {
					// Create a new user
					$user_id = wp_insert_user([
						'user_login' => $email,
						'user_email' => $email,
						'first_name' => $customer['PairValue']['CustomerFirstName'],
						'last_name' => $customer['PairValue']['CustomerLastName'],
						'user_pass' => wp_generate_password(),
					]);

					if (is_wp_error($user_id)) {
						error_log('User creation error: ' . $user_id->get_error_message());
						$skipped++;
						continue;
					}

					$user = get_user_by('id', $user_id);
					$is_new_user = true;
					$created++;
					
					// Send account creation email
					$this->send_new_user_notification($user_id, $customer['PairValue']['CustomerFirstName'], $customer['PairValue']['CustomerLastName']);
				} else {
					$updated++;
				}

				// Update user meta
				update_user_meta($user->ID, '_edge_sync', true);
				update_user_meta($user->ID, '_edge_key', $customer['Key']);
				update_user_meta($user->ID, '_edge_id', $customer['PairValue']['Customerid']);

				// Check if the user was not synched before
				$is_new_sync = !get_user_meta($user->ID, '_edge_synced_before', true);
				if ($is_new_sync) {
					// Mark user as synced
					update_user_meta($user->ID, '_edge_synced_before', true);
					
					// Copy the entire customer structure and only update the WebTransferWebID
					$customer_copy = $customer;
					$customer_copy['PairValue']['CustomerTransfer']['WebTransferWebID'] = $user->ID;
					$new_customers[] = $customer_copy;
				}
			}
			
			// Update progress
			update_option('edge_ajax_import_processed', $processed);
			update_option('edge_ajax_import_created', $created);
			update_option('edge_ajax_import_updated', $updated);
			update_option('edge_ajax_import_skipped', $skipped);
			update_option('edge_ajax_import_new_customers', $new_customers);
			
			// Clean up the current chunk transient
			delete_transient('edge_ajax_import_chunk_' . $current_chunk);
			
			// Calculate progress percentage
			$total_chunks = get_option('edge_ajax_import_total_chunks', 1);
			$progress_percent = min(100, round(($current_chunk + 1) / $total_chunks * 100));
			
			// Check if this is the last chunk
			if ($current_chunk + 1 >= $total_chunks) {
				// This is the last chunk, finalize the import
				$this->finalize_ajax_import();
				
				// Return final statistics
				wp_send_json_success([
					'message' => 'Import completed successfully',
					'progress' => 100,
					'isComplete' => true,
					'stats' => [
						'total' => get_option('edge_ajax_import_total_customers', 0),
						'processed' => $processed,
						'created' => $created,
						'updated' => $updated,
						'skipped' => $skipped,
						'exported' => count($new_customers)
					]
				]);
			} else {
				// Return progress for the next chunk
				wp_send_json_success([
					'message' => 'Processing chunk ' . ($current_chunk + 1) . ' of ' . $total_chunks,
					'progress' => $progress_percent,
					'nextChunk' => $current_chunk + 1,
					'isComplete' => false,
					'stats' => [
						'processed' => $processed,
						'created' => $created,
						'updated' => $updated,
						'skipped' => $skipped
					]
				]);
			}
			
		} catch (\Exception $e) {
			error_log('Error processing chunk: ' . $e->getMessage());
			wp_send_json_error('Error processing chunk: ' . $e->getMessage());
			$this->cleanup_ajax_import();
		}
	}
	
	/**
	 * Finalize the AJAX import by creating and uploading the output file
	 */
	private function finalize_ajax_import() {
		error_log('Finalizing AJAX import');
		
		try {
			// Get connection details stored during the first chunk
			$host = get_option('edge_ajax_import_sftp_host');
			$username = get_option('edge_ajax_import_sftp_username');
			$password = get_option('edge_ajax_import_sftp_password');
			$port = get_option('edge_ajax_import_sftp_port', 22);
			$outbox_path = get_option('edge_ajax_import_outbox_path');
			
			// Temporarily set connection details to use the factory
			$old_host = get_option('edge_sftp_host');
			$old_username = get_option('edge_sftp_username');
			$old_password = get_option('edge_sftp_password');
			$old_port = get_option('edge_sftp_port');
			
			update_option('edge_sftp_host', $host);
			update_option('edge_sftp_username', $username);
			update_option('edge_sftp_password', $password);
			update_option('edge_sftp_port', $port);
			
			// Connect using factory
			$connection = $this->create_connection();
			
			$prefix_counter = get_option('edge_prefix_counter', 1);
			$new_customers = get_option('edge_ajax_import_new_customers', array());
			
			// Create a new JSON file with the same structure as the original
			$new_file_name = $prefix_counter . '-CustomerList.json';
			$new_json_data = [
				'Customers' => $new_customers,
				'MaxAddresses' => get_option('edge_ajax_import_max_addresses', 0),
				'MaxEmails' => get_option('edge_ajax_import_max_emails', 0),
				'MaxPhones' => get_option('edge_ajax_import_max_phones', 0)
			];
			
			$new_json_content = json_encode($new_json_data, JSON_PRETTY_PRINT);
			if (json_last_error() !== JSON_ERROR_NONE) {
				error_log('JSON encode error during AJAX import: ' . json_last_error_msg());
				return;
			}
			
			$temp_file = plugin_dir_path(__FILE__) . $new_file_name;
			file_put_contents($temp_file, $new_json_content);

			// Upload the new JSON file to the Outbox
			$this->upload_file($connection, $outbox_path . '/' . $new_file_name, $temp_file);
			
			// Close connection properly
			$this->close_connection($connection);
			
			// Clean up temporary file
			@unlink($temp_file);
			error_log('Deleted local temporary file: ' . $temp_file);

			// Increment the prefix counter
			update_option('edge_prefix_counter', $prefix_counter + 1);
			
			// Restore original connection settings
			update_option('edge_sftp_host', $old_host);
			update_option('edge_sftp_username', $old_username);
			update_option('edge_sftp_password', $old_password);
			update_option('edge_sftp_port', $old_port);
			
			// Clean up all import data
			$this->cleanup_ajax_import();
			
		} catch (\Exception $e) {
			error_log('Error finalizing AJAX import: ' . $e->getMessage());
		}
	}
	
	/**
	 * Clean up all AJAX import progress data
	 */
	private function cleanup_ajax_import() {
		// Remove all transients and options related to the import
		$total_chunks = get_option('edge_ajax_import_total_chunks', 0);
		for ($i = 0; $i < $total_chunks; $i++) {
			delete_transient('edge_ajax_import_chunk_' . $i);
		}
		
		delete_option('edge_ajax_import_total_customers');
		delete_option('edge_ajax_import_total_chunks');
		delete_option('edge_ajax_import_processed');
		delete_option('edge_ajax_import_created');
		delete_option('edge_ajax_import_updated');
		delete_option('edge_ajax_import_skipped');
		delete_option('edge_ajax_import_new_customers');
		delete_option('edge_ajax_import_max_addresses');
		delete_option('edge_ajax_import_max_emails');
		delete_option('edge_ajax_import_max_phones');
		delete_option('edge_ajax_import_sftp_host');
		delete_option('edge_ajax_import_sftp_username');
		delete_option('edge_ajax_import_sftp_password');
		delete_option('edge_ajax_import_sftp_port');
		delete_option('edge_ajax_import_outbox_path');
	}

	/**
	 * Send new user notification email
	 *
	 * @param int    $user_id    The ID of the newly created user
	 * @param string $first_name User's first name
	 * @param string $last_name  User's last name
	 */
	private function send_new_user_notification($user_id, $first_name, $last_name) {
		// Get user data
		$user = get_userdata($user_id);
		if (!$user) {
			error_log('Cannot send notification - user not found: ' . $user_id);
			return;
		}
		
		// Generate password reset key
		$key = get_password_reset_key($user);
		if (is_wp_error($key)) {
			error_log('Error generating password reset key: ' . $key->get_error_message());
			return;
		}
		
		// Build password reset URL
		$reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');
		
		// Email subject
		$subject = sprintf(__('Welcome to %s - Set Up Your Account'), get_bloginfo('name'));
		
		// Email message
		$message = sprintf(__('Hello %s,'), $first_name . ' ' . $last_name) . "\r\n\r\n";
		$message .= sprintf(__('Welcome to %s! An account has been created for you.'), get_bloginfo('name')) . "\r\n\r\n";
		$message .= __('To set up your password and login to your account, visit the following address:') . "\r\n\r\n";
		$message .= $reset_url . "\r\n\r\n";
		$message .= __('This link will expire in 24 hours for security reasons.') . "\r\n\r\n";
		$message .= sprintf(__('Your username: %s'), $user->user_email) . "\r\n\r\n";
		$message .= __('Thanks!') . "\r\n\r\n";
		$message .= get_bloginfo('name');
		
		// Email headers
		$headers = ['Content-Type: text/plain; charset=UTF-8'];
		
		// Send email
		$sent = wp_mail($user->user_email, $subject, $message, $headers);
		
		if (!$sent) {
			error_log('Failed to send welcome email to: ' . $user->user_email);
		} else {
			error_log('Welcome email sent to: ' . $user->user_email);
		}
	}

	/**
	 * Import products from ItemList.json file.
	 * This can be called manually or via cron.
	 */
	public function import_products() {
		error_log('Starting product import from EDGE');
		
		// Set higher PHP limits for the import
		@set_time_limit(600); // 10 minutes
		@ini_set('memory_limit', '256M');
		
		// Initialize statistics
		$created = 0;
		$updated = 0;
		$skipped = 0;
		
		// Retrieve the base path from settings
		$base_path = get_option('edge_sftp_folder', '/');

		// Construct the Inbox path
		$inbox_path = rtrim($base_path, '/') . '/Inbox';

		error_log('Using Inbox path for products: ' . $inbox_path);

		try {
			// Step 1: Connect using factory
			$connection = $this->create_connection();
			
			// Step 2: List files in the Inbox directory
			$files = $this->list_directory($connection, $inbox_path);
			if ($files === false) {
				error_log('Failed to list files in Inbox during product import');
				$this->close_connection($connection);
				return false;
			}

			$file_names = array_keys($files);
			
			// Step 3: Find the most recent ItemList.json file
			$latest_file = '';
			$latest_time = 0;
			foreach ($file_names as $file) {
				if (substr($file, -strlen('ItemList.json')) === 'ItemList.json') {
					$file_time = $files[$file]['mtime'];
					if ($file_time > $latest_time) {
						$latest_time = $file_time;
						$latest_file = $file;
					}
				}
			}

			if (empty($latest_file)) {
				error_log('No ItemList.json file found during product import');
				$this->close_connection($connection);
				return false;
			}

			// Step 4: Download and read the JSON file
			$json_content = $this->get_file($connection, $inbox_path . '/' . $latest_file);
			if ($json_content === false) {
				error_log('Failed to download ItemList.json file');
				$this->close_connection($connection);
				return false;
			}
			
			// Step 5: Decode JSON and check for errors
			$items_data = json_decode($json_content, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				error_log('JSON decode error during product import: ' . json_last_error_msg());
				$this->close_connection($connection);
				return false;
			}
			
			// Step 6: Process the items
			$total_items = count($items_data['Items']);
			
			error_log('Processing ' . $total_items . ' products');
			
			// Check if WooCommerce is active
			if (!class_exists('WooCommerce')) {
				error_log('WooCommerce is not active, cannot import products');
				$this->close_connection($connection);
				return false;
			}
			
			foreach ($items_data['Items'] as $item) {
				// Extract product data
				$product_id = $item['Key']; // Use the Key as product ID
				$product_name = $item['PairValue']['ItemDesc']; // Use ItemDesc for product name
				$product_price = $item['PairValue']['ItemRetailPrice']; // Use ItemRetailPrice for product price
				$product_image = $item['PairValue']['ItemImage']; // Use ItemImage for product image
				
				// Check if product already exists by meta value
				$existing_product_id = $this->get_product_by_edge_id($product_id);
				
				if ($existing_product_id) {
					// Update existing product
					$product = wc_get_product($existing_product_id);
					if ($product) {
						$product->set_name($product_name);
						$product->set_regular_price($product_price);
						$product->save();
						
						// Update product image if it exists
						if (!empty($product_image)) {
							$this->set_product_image($existing_product_id, $product_image, $connection, $inbox_path);
						}
						
						$updated++;
					} else {
						$skipped++;
					}
				} else {
					// Create new product
					$product = new WC_Product_Simple();
					$product->set_name($product_name);
					$product->set_regular_price($product_price);
					$product->set_status('publish');
					$new_product_id = $product->save();
					
					if ($new_product_id) {
						// Save EDGE ID as product meta
						update_post_meta($new_product_id, '_edge_id', $product_id);
						
						// Set product image if it exists
						if (!empty($product_image)) {
							$this->set_product_image($new_product_id, $product_image, $connection, $inbox_path);
						}
						
						$created++;
					} else {
						$skipped++;
					}
				}
			}
			
			// Store statistics
			update_option('edge_products_created', $created);
			update_option('edge_products_updated', $updated);
			update_option('edge_products_skipped', $skipped);
			
			error_log('Product import completed: created ' . $created . ', updated ' . $updated . ', skipped ' . $skipped);
			
			// Close connection
			$this->close_connection($connection);
			return true;
			
		} catch (\Exception $e) {
			error_log('Error during product import: ' . $e->getMessage());
			return false;
		}
	}
	
	/**
	 * Get product by EDGE ID.
	 *
	 * @param string $edge_id The EDGE product ID.
	 * @return int|false The product ID if found, false otherwise.
	 */
	private function get_product_by_edge_id($edge_id) {
		global $wpdb;
		
		$product_id = $wpdb->get_var($wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_edge_id' AND meta_value = %s LIMIT 1",
			$edge_id
		));
		
		return $product_id ? (int) $product_id : false;
	}
	
	/**
	 * Set product image from remote connection.
	 *
	 * @param int $product_id The product ID.
	 * @param string $image_name The image file name.
	 * @param mixed $connection The connection object (SFTP) or resource (FTP).
	 * @param string $inbox_path The remote inbox path.
	 */
	private function set_product_image($product_id, $image_name, $connection, $inbox_path) {
		try {
			// Create uploads directory if it doesn't exist
			$upload_dir = wp_upload_dir();
			$product_images_dir = $upload_dir['basedir'] . '/edge-products';
			
			if (!file_exists($product_images_dir)) {
				wp_mkdir_p($product_images_dir);
			}
			
			// Download the image from remote
			$local_image_path = $product_images_dir . '/' . $image_name;
			$remote_image_path = $inbox_path . '/' . $image_name;
			
			if ($this->file_exists($connection, $remote_image_path)) {
				$image_content = $this->get_file($connection, $remote_image_path);
				if ($image_content !== false) {
					file_put_contents($local_image_path, $image_content);
				
				// Check if file was downloaded successfully
				if (file_exists($local_image_path)) {
					// Prepare image for WordPress media library
					$filetype = wp_check_filetype($image_name, null);
					$attachment = array(
						'post_mime_type' => $filetype['type'],
						'post_title' => sanitize_file_name($image_name),
						'post_content' => '',
						'post_status' => 'inherit'
					);
					
					// Insert attachment
					$attachment_id = wp_insert_attachment($attachment, $local_image_path, $product_id);
					
					if (!is_wp_error($attachment_id)) {
						// Generate metadata for the attachment
						require_once(ABSPATH . 'wp-admin/includes/image.php');
						$attachment_data = wp_generate_attachment_metadata($attachment_id, $local_image_path);
						wp_update_attachment_metadata($attachment_id, $attachment_data);
						
						// Set as product image
						set_post_thumbnail($product_id, $attachment_id);
						
						error_log('Product image set successfully for product ID: ' . $product_id);
					} else {
						error_log('Error creating attachment: ' . $attachment_id->get_error_message());
					}
				} else {
						error_log('Failed to save image to local path: ' . $local_image_path);
				}
			} else {
					error_log('Failed to download image from remote: ' . $remote_image_path);
				}
			} else {
				error_log('Image file does not exist on remote: ' . $remote_image_path);
			}
		} catch (\Exception $e) {
			error_log('Error setting product image: ' . $e->getMessage());
		}
	}

	/**
	 * AJAX handler for importing products with chunked processing.
	 */
	public function ajax_import_products() {
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized');
		}
		
		// Set timeout to prevent script termination
		set_time_limit(300); // 5 minutes
		@ini_set('memory_limit', '256M'); // Increase memory limit for large imports

		// Get the current chunk from the request
		$current_chunk = isset($_POST['chunk']) ? intval($_POST['chunk']) : 0;
		$chunk_size = get_option('edge_product_chunk_size', 100); // Get from settings
		
		// Check if this is the first chunk (start of import)
		$is_first_chunk = ($current_chunk === 0);
		
		// For the first chunk, we need to fetch the data from connection
		if ($is_first_chunk) {
			// Retrieve the base path from settings
			$base_path = get_option('edge_sftp_folder', '/');
			$inbox_path = rtrim($base_path, '/') . '/Inbox';

			error_log('Using Inbox path for products: ' . $inbox_path);

			// Connection details
			$host = get_option('edge_sftp_host');
			$username = get_option('edge_sftp_username');
			$password = get_option('edge_sftp_password');
			$port = intval(get_option('edge_sftp_port', 22));

			require_once ABSPATH . 'vendor/autoload.php';

			try {
				// Step 1: Connect using factory method (supports both SFTP and FTP)
				$connection = $this->create_connection();
				
				// Step 2: List files in the Inbox directory
				$files = $this->list_directory($connection, $inbox_path);
				if ($files === false) {
					error_log('Failed to list files in Inbox during product import');
					$this->close_connection($connection);
					wp_send_json_error('Failed to list files in Inbox');
				}

				$file_names = array_keys($files);
				
				// Step 3: Find the most recent ItemList.json file
				$latest_file = '';
				$latest_time = 0;
				foreach ($file_names as $file) {
					if (substr($file, -strlen('ItemList.json')) === 'ItemList.json') {
						$file_time = $files[$file]['mtime'];
						if ($file_time > $latest_time) {
							$latest_time = $file_time;
							$latest_file = $file;
						}
					}
				}

				if (empty($latest_file)) {
					error_log('No ItemList.json file found during product import');
					$this->close_connection($connection);
					wp_send_json_error('No ItemList.json file found');
				}

				// Step 4: Download and read the JSON file
				$json_content = $this->get_file($connection, $inbox_path . '/' . $latest_file);
				if ($json_content === false) {
					error_log('Failed to download ItemList.json file');
					$this->close_connection($connection);
					wp_send_json_error('Failed to download ItemList.json file');
				}
    
				// Close connection after getting the file
				$this->close_connection($connection);
				
				// Step 5: Decode JSON and check for errors
				$items_data = json_decode($json_content, true);
				if (json_last_error() !== JSON_ERROR_NONE) {
					error_log('JSON decode error during product import: ' . json_last_error_msg());
					wp_send_json_error('JSON decode error: ' . json_last_error_msg());
				}
				
				$total_products = count($items_data['Items']);
				error_log('Number of products: ' . $total_products);
				
				// Store total products and other metadata for the import process
				update_option('edge_ajax_products_import_total_products', $total_products);
				update_option('edge_ajax_products_import_total_chunks', ceil($total_products / $chunk_size));
				update_option('edge_ajax_products_import_processed', 0);
				update_option('edge_ajax_products_import_created', 0);
				update_option('edge_ajax_products_import_updated', 0);
				update_option('edge_ajax_products_import_skipped', 0);
				update_option('edge_ajax_products_import_inbox_path', $inbox_path);
				
				// Store the connection details for later chunks (same as ajax_import_customers)
				update_option('edge_ajax_products_import_sftp_host', $host);
				update_option('edge_ajax_products_import_sftp_username', $username);
				update_option('edge_ajax_products_import_sftp_password', $password);
				update_option('edge_ajax_products_import_sftp_port', $port);
				
				// Store the products data in chunks to avoid memory issues
				$chunks = array_chunk($items_data['Items'], $chunk_size);
				foreach ($chunks as $index => $chunk) {
					set_transient('edge_ajax_products_import_chunk_' . $index, $chunk, HOUR_IN_SECONDS);
				}
				
				// Free up memory
				unset($items_data);
				unset($json_content);
				unset($chunks);
			} catch (\Exception $e) {
				error_log('Connection error during product import: ' . $e->getMessage());
				wp_send_json_error($e->getMessage());
				return;
			}
		}
		
		// Process the current chunk
		try {
			// Get the current chunk data
			$current_chunk_data = get_transient('edge_ajax_products_import_chunk_' . $current_chunk);
			if (!$current_chunk_data) {
				error_log('Failed to retrieve product chunk ' . $current_chunk . ' data');
				wp_send_json_error('Failed to retrieve chunk data. The import process may have timed out.');
				return;
			}
			
			// Get the current progress
			$processed = get_option('edge_ajax_products_import_processed', 0);
			$created = get_option('edge_ajax_products_import_created', 0);
			$updated = get_option('edge_ajax_products_import_updated', 0);
			$skipped = get_option('edge_ajax_products_import_skipped', 0);
			
			// Get connection details stored during the first chunk
			$host = get_option('edge_ajax_products_import_sftp_host');
			$username = get_option('edge_ajax_products_import_sftp_username');
			$password = get_option('edge_ajax_products_import_sftp_password');
			$port = get_option('edge_ajax_products_import_sftp_port', 22);
			$inbox_path = get_option('edge_ajax_products_import_inbox_path');
			
			// Temporarily set connection details to use the factory (same as ajax_import_customers)
			$old_host = get_option('edge_sftp_host');
			$old_username = get_option('edge_sftp_username');
			$old_password = get_option('edge_sftp_password');
			$old_port = get_option('edge_sftp_port');
			
			update_option('edge_sftp_host', $host);
			update_option('edge_sftp_username', $username);
			update_option('edge_sftp_password', $password);
			update_option('edge_sftp_port', $port);
			
			// Connect using factory method (supports both SFTP and FTP)
			$connection = $this->create_connection();
			
			error_log('Processing product chunk ' . ($current_chunk + 1) . ' of ' . get_option('edge_ajax_products_import_total_chunks'));
			
			// Check if WooCommerce is active
			if (!class_exists('WooCommerce')) {
				error_log('WooCommerce is not active, cannot import products');
				$this->close_connection($connection);
				// Restore original connection settings
				update_option('edge_sftp_host', $old_host);
				update_option('edge_sftp_username', $old_username);
				update_option('edge_sftp_password', $old_password);
				update_option('edge_sftp_port', $old_port);
				wp_send_json_error('WooCommerce is not active');
				return;
			}
			
			// Process each product in the current chunk
			foreach ($current_chunk_data as $item) {
				$processed++;
				
				// Extract product data
				$product_id = $item['Key']; // Use the Key as product ID
				$product_name = $item['PairValue']['ItemDesc']; // Use ItemDesc for product name
				$product_price = $item['PairValue']['ItemRetailPrice']; // Use ItemRetailPrice for product price
				$product_image = $item['PairValue']['ItemImage']; // Use ItemImage for product image
				
				// Check if product already exists by meta value
				$existing_product_id = $this->get_product_by_edge_id($product_id);
				
				if ($existing_product_id) {
					// Update existing product
					$product = wc_get_product($existing_product_id);
					if ($product) {
						$product->set_name($product_name);
						$product->set_regular_price($product_price);
						$product->save();
						
						// Update product image if it exists
						if (!empty($product_image)) {
							$this->set_product_image($existing_product_id, $product_image, $connection, $inbox_path);
						}
						
						$updated++;
					} else {
						$skipped++;
					}
				} else {
					// Create new product
					$product = new WC_Product_Simple();
					$product->set_name($product_name);
					$product->set_regular_price($product_price);
					$product->set_status('publish');
					$new_product_id = $product->save();
					
					if ($new_product_id) {
						// Save EDGE ID as product meta
						update_post_meta($new_product_id, '_edge_id', $product_id);
						
						// Set product image if it exists
						if (!empty($product_image)) {
							$this->set_product_image($new_product_id, $product_image, $connection, $inbox_path);
						}
						
						$created++;
					} else {
						$skipped++;
					}
				}
			}
			
			// Close connection properly using helper method
			$this->close_connection($connection);
			
			// Restore original connection settings
			update_option('edge_sftp_host', $old_host);
			update_option('edge_sftp_username', $old_username);
			update_option('edge_sftp_password', $old_password);
			update_option('edge_sftp_port', $old_port);
			
			// Update progress
			update_option('edge_ajax_products_import_processed', $processed);
			update_option('edge_ajax_products_import_created', $created);
			update_option('edge_ajax_products_import_updated', $updated);
			update_option('edge_ajax_products_import_skipped', $skipped);
			
			// Clean up the current chunk transient
			delete_transient('edge_ajax_products_import_chunk_' . $current_chunk);
			
			// Calculate progress percentage
			$total_chunks = get_option('edge_ajax_products_import_total_chunks', 1);
			$progress_percent = min(100, round(($current_chunk + 1) / $total_chunks * 100));
			
			// Check if this is the last chunk
			if ($current_chunk + 1 >= $total_chunks) {
				// This is the last chunk, finalize the import
				$this->finalize_ajax_products_import();
				
				// Return final statistics
				wp_send_json_success([
					'message' => 'Product import completed successfully',
					'progress' => 100,
					'isComplete' => true,
					'stats' => [
						'total' => get_option('edge_ajax_products_import_total_products', 0),
						'processed' => $processed,
						'created' => $created,
						'updated' => $updated,
						'skipped' => $skipped
					]
				]);
			} else {
				// Return progress for the next chunk
				
				wp_send_json_success([
					'message' => 'Processing chunk ' . ($current_chunk + 1) . ' of ' . $total_chunks,
					'progress' => $progress_percent,
					'nextChunk' => $current_chunk + 1,
					'isComplete' => false,
					'stats' => [
						'processed' => $processed,
						'created' => $created,
						'updated' => $updated,
						'skipped' => $skipped
					]
				]);
			}
			
		} catch (\Exception $e) {
			error_log('Error processing product chunk: ' . $e->getMessage());
			wp_send_json_error('Error processing chunk: ' . $e->getMessage());
			$this->cleanup_ajax_products_import();
		}
	}

	/**
	 * Finalize the AJAX product import
	 */
	private function finalize_ajax_products_import() {
		error_log('Finalizing AJAX product import');
		
		try {
			// Store final statistics for display
			$created = get_option('edge_ajax_products_import_created', 0);
			$updated = get_option('edge_ajax_products_import_updated', 0);
			$skipped = get_option('edge_ajax_products_import_skipped', 0);
			
			// Update the main product statistics options
			update_option('edge_products_created', $created);
			update_option('edge_products_updated', $updated);
			update_option('edge_products_skipped', $skipped);
			
			// Clean up all import data
			$this->cleanup_ajax_products_import();
			
		} catch (\Exception $e) {
			error_log('Error finalizing AJAX product import: ' . $e->getMessage());
		}
	}
	
	/**
	 * Clean up all AJAX product import progress data
	 */
	private function cleanup_ajax_products_import() {
		// Remove all transients and options related to the product import
		$total_chunks = get_option('edge_ajax_products_import_total_chunks', 0);
		for ($i = 0; $i < $total_chunks; $i++) {
			delete_transient('edge_ajax_products_import_chunk_' . $i);
		}
		
		delete_option('edge_ajax_products_import_total_products');
		delete_option('edge_ajax_products_import_total_chunks');
		delete_option('edge_ajax_products_import_processed');
		delete_option('edge_ajax_products_import_created');
		delete_option('edge_ajax_products_import_updated');
		delete_option('edge_ajax_products_import_skipped');
		delete_option('edge_ajax_products_import_inbox_path');
		delete_option('edge_ajax_products_import_sftp_host');
		delete_option('edge_ajax_products_import_sftp_username');
		delete_option('edge_ajax_products_import_sftp_password');
		delete_option('edge_ajax_products_import_sftp_port');
	}

    /**
     * Sync WooCommerce order to EDGE POS system
     *
     * @param int $order_id The WooCommerce order ID
     */
    public function sync_order_to_edge($order_id) {
        error_log('Starting order sync to EDGE for order ID: ' . $order_id);
        
        // Get order object
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log('Order not found: ' . $order_id);
            return;
        }
        
        // Get customer ID
        $customer_id = $order->get_customer_id();
        if (!$customer_id) {
            error_log('No customer ID found for order: ' . $order_id);
            return;
        }
            
        // Get customer object
        $customer = get_user_by('id', $customer_id);
        if (!$customer) {
            error_log('Customer not found: ' . $customer_id);
            return;
        }
        
        // Check if customer is already synced with EDGE
        $is_edge_synced = get_user_meta($customer_id, '_edge_sync', true);
        
        // Get base path from settings
        $base_path = get_option('edge_sftp_folder', '/');
        
        // Construct the Outbox path
        $outbox_path = rtrim($base_path, '/') . '/Outbox';
        
        try {
            // Connect using factory
            $connection = $this->create_connection();
            
            // Only create and upload customer JSON if user is not already synced to EDGE
            if (!$is_edge_synced) {
                error_log('Customer not synced with EDGE, creating customer JSON for customer ID: ' . $customer_id);
                $this->create_customer_json_for_edge($customer, $connection, $outbox_path);
            } else {
                error_log('Customer already synced with EDGE, skipping customer JSON creation for customer ID: ' . $customer_id);
            }
            
            // Always create and upload websale JSON for the order
            $this->create_websale_json_for_edge($order, $connection, $outbox_path);
            
            // Close connection
            $this->close_connection($connection);
            
            error_log('Order sync completed for order ID: ' . $order_id);
            
        } catch (\Exception $e) {
            error_log('Error during order sync: ' . $e->getMessage());
        }
    }
    
    /**
     * Create customer JSON file for EDGE
     * 
     * @param WP_User $customer WordPress user object
     * @param mixed $connection Connection object (SFTP object or FTP resource)
     * @param string $outbox_path Remote outbox path
     */
    private function create_customer_json_for_edge($customer, $connection, $outbox_path) {
        try {
            // Get customer data
            $customer_id = $customer->ID;
            $first_name = $customer->first_name;
            $last_name = $customer->last_name;
            $email = $customer->user_email;
            
            // Get billing info
            $billing_phone = get_user_meta($customer_id, 'billing_phone', true);
            $billing_address_1 = get_user_meta($customer_id, 'billing_address_1', true);
            $billing_address_2 = get_user_meta($customer_id, 'billing_address_2', true);
            $billing_city = get_user_meta($customer_id, 'billing_city', true);
            $billing_state = get_user_meta($customer_id, 'billing_state', true);
            $billing_postcode = get_user_meta($customer_id, 'billing_postcode', true);
            $billing_country = get_user_meta($customer_id, 'billing_country', true);
            $billing_company = get_user_meta($customer_id, 'billing_company', true);
            
            // Get EDGE-specific meta data
            $edge_key = get_user_meta($customer_id, '_edge_key', true);
            $edge_id = get_user_meta($customer_id, '_edge_id', true);
            
            // Build addresses array
            $addresses = array();
            if (!empty($billing_address_1) || !empty($billing_city)) {
                $addresses[] = array(
                    "Key" => "Home",
                    "PairValue" => array(
                        "AddressCustKey" => $edge_key ?: "",
                        "AddressType" => "Home",
                        "AddressCompany" => $billing_company ?: "",
                        "AddressStreet" => $billing_address_1 ?: "",
                        "AddressStreet2" => $billing_address_2 ?: "",
                        "AddressCity" => $billing_city ?: "",
                        "AddressState" => $billing_state ?: "",
                        "AddressZip" => $billing_postcode ?: "",
                        "AddressCountry" => $billing_country ?: "USA",
                        "AddressCustAccountKey" => ""
                    )
                );
            }
            
            // Build emails array
            $emails = array();
            if (!empty($email)) {
                $emails[] = array(
                    "Key" => "Work",
                    "PairValue" => array(
                        "EmailCustKey" => $edge_key ?: "",
                        "EmailType" => "Work",
                        "EmailEmail" => $email,
                        "EmailSendPromo" => true,
                        "EmailCustAccountKey" => ""
                    )
                );
            }
            
            // Build phones array
            $phones = array();
            if (!empty($billing_phone)) {
                $phones[] = array(
                    "Key" => "Cell",
                    "PairValue" => array(
                        "PhoneCustKey" => $edge_key ?: "",
                        "PhoneType" => "Cell",
                        "PhonePhone" => preg_replace('/[^0-9]/', '', $billing_phone), // Remove non-numeric characters
                        "PhoneDoNotContact" => false,
                        "PhonePhoneExt" => "",
                        "PhoneCustAccountKey" => ""
                    )
                );
            }
            
            // Create customer data array matching the sample format exactly
            $customer_data = array(
                "Customers" => array(
                    array(
                        "Key" => $edge_key ?: "",
                        "PairValue" => array(
                            "CustomerStoreId" => 1,
                            "Customerid" => $edge_id ?: null,
                            "CustomerKey" => $edge_key ?: "",
                            "CustomerTitle" => "",
                            "CustomerFirstName" => $first_name ?: "",
                            "CustomerMiddleName" => "",
                            "CustomerLastName" => $last_name ?: "",
                            "CustomerSuffix" => "",
                            "CustomerGender" => "",
                            "CustomerBirthDate" => "",
                            "CustomerSpouseBirthDate" => "",
                            "CustomerWeddingAnniv" => "",
                            "CustomerDateEntered" => "",
                            "CustomerImage" => "",
                            "CustomerAcquisition" => "",
                            "CustomerPrefMail" => "Home",
                            "CustomerPrefPhone" => "Cell",
                            "CustomerSSNO" => "",
                            "CustomerNotes" => "",
                            "CustomerPrefEMail" => "Work",
                            "CustomerType" => "",
                            "CustomerIsCompany" => false,
                            "CustomerCompany" => $billing_company ?: "",
                            "CustomerKey1" => "",
                            "CustomerKey2" => "",
                            "CustomerKey3" => "",
                            "CustomerKey4" => "",
                            "CustomerKey5" => "",
                            "CustomerKey6" => "",
                            "CustomerKey7" => "",
                            "CustomerKey8" => "",
                            "CustomerUpdateSeq" => null,
                            "CustomerUpdateDate" => null,
                            "CustomerUpdateStore" => null,
                            "CustomerUpdateStation" => null,
                            "CustomerUpdateUser" => null,
                            "CustomerXfer" => null,
                            "CustomerInactive" => false,
                            "CustomerWholesale" => false,
                            "CustomerDiscount" => null,
                            "CustomerMinMarkup" => null,
                            "CustomerQuickbooks" => "",
                            "CustomerYnStoreCredit" => false,
                            "CustomerCreditLimit" => null,
                            "CustomerTerms" => "",
                            "CustomerNotes255" => "",
                            "CustomerNoStatements" => false,
                            "CustomerTaxExempt" => false,
                            "CustomerDateVerified" => null,
                            "CustomerCoupleName" => "",
                            "CustomerMarried" => false,
                            "CustomerSpouseKey" => "",
                            "CustomerInterestRate" => null,
                            "CustomerSwapped" => false,
                            "CustomerOldCustKey" => "",
                            "CustomerAsKey" => 0,
                            "CustomerReferredBy" => "",
                            "CustomerLicenseNo" => "",
                            "CustomerMinPayment" => null,
                            "CustomerTaxId" => "",
                            "CustomerAccountKey" => "",
                            "Addresses" => $addresses,
                            "Emails" => $emails,
                            "Phones" => $phones,
                            "CustomerTransfer" => array(
                                "WebTransferKey" => "",
                                "WebTransferEdgeID" => $edge_id ?: "",
                                "WebTransferWebID" => $customer_id, // Set to WordPress user ID
                                "WebTransferLastModified" => null,
                                "WebTransferRequiresWebUpload" => false,
                                "WebTransferRequiresEdgeAttention" => false,
                                "WebTransferType" => 0,
                                "WebTransferLinkedWebID" => "",
                                "WebTransferVendorID" => 0
                            )
                        )
                    )
                )
            );
            
            // Create JSON file name with incrementing number format
            $prefix_counter = get_option('edge_prefix_counter', 1);
            $json_filename = $prefix_counter . '_NEW-CustomerList.json';
            
            // Create temporary file
            $temp_file = tempnam(sys_get_temp_dir(), 'edge_customer_');
            file_put_contents($temp_file, json_encode($customer_data, JSON_PRETTY_PRINT));
            
            // Upload to remote
            $remote_path = $outbox_path . '/' . $json_filename;
            $result = $this->upload_file($connection, $remote_path, $temp_file);
            
            // Clean up temp file
            unlink($temp_file);
            
            if ($result) {
                error_log('Customer JSON uploaded successfully: ' . $json_filename);
                
                // Increment the prefix counter for next use
                update_option('edge_prefix_counter', $prefix_counter + 1);
                
                // Mark customer as synced and set EDGE meta (same as import process)
                update_user_meta($customer_id, '_edge_sync', true);
                update_user_meta($customer_id, '_edge_synced_before', true);
                update_user_meta($customer_id, '_edge_last_sync', current_time('mysql'));
                
                // Set EDGE key and ID if not already set
                if (empty($edge_key)) {
                    update_user_meta($customer_id, '_edge_key', 'WEB-CUSTOMER-' . $customer_id);
                }
                if (empty($edge_id)) {
                    update_user_meta($customer_id, '_edge_id', $customer_id);
                }
            } else {
                error_log('Failed to upload customer JSON: ' . $json_filename);
            }
            
        } catch (\Exception $e) {
            error_log('Error creating customer JSON: ' . $e->getMessage());
        }
    }
    
    /**
     * Create WebSale JSON file for EDGE
     * 
     * @param WC_Order $order WooCommerce order
     * @param mixed $connection Connection object (SFTP object or FTP resource)
     * @param string $outbox_path Remote outbox path
     */
    private function create_websale_json_for_edge($order, $connection, $outbox_path) {
        try {
            // Get order data
            $order_id = $order->get_id();
            $order_total = $order->get_total();
            $order_shipping = $order->get_shipping_total();
            
            // Get customer data
            $customer_id = $order->get_customer_id();
            $billing_email = $order->get_billing_email();
            $billing_phone = $order->get_billing_phone();
            $billing_first_name = $order->get_billing_first_name();
            $billing_last_name = $order->get_billing_last_name();
            
            // Get billing address
            $billing_address_1 = $order->get_billing_address_1();
            $billing_address_2 = $order->get_billing_address_2();
            $billing_city = $order->get_billing_city();
            $billing_state = $order->get_billing_state();
            $billing_postcode = $order->get_billing_postcode();
            $billing_country = $order->get_billing_country();
            
            // Get shipping address
            $shipping_address_1 = $order->get_shipping_address_1() ?: $billing_address_1;
            $shipping_address_2 = $order->get_shipping_address_2() ?: $billing_address_2;
            $shipping_city = $order->get_shipping_city() ?: $billing_city;
            $shipping_state = $order->get_shipping_state() ?: $billing_state;
            $shipping_postcode = $order->get_shipping_postcode() ?: $billing_postcode;
            $shipping_country = $order->get_shipping_country() ?: $billing_country;
            
            // Build sold items array - add each item individually based on quantity
            $sold_items = array();
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product) {
                    $edge_id = get_post_meta($product->get_id(), '_edge_id', true);
                    if ($edge_id) {
                        $item_price = $item->get_total() / $item->get_quantity(); // Price per individual item
                        $quantity = $item->get_quantity();
                        
                        // Add each item individually based on quantity
                        for ($i = 0; $i < $quantity; $i++) {
                            $sold_items[] = array(
                                "ItemSku" => $edge_id,
                                "SalePrice" => round($item_price, 2)
                            );
                        }
                    }
                }
            }
            
            // Build payments array
            $payments = array();
            $payment_method = $order->get_payment_method();
            $payment_method_title = $order->get_payment_method_title();
            
            // Get payment meta data if available
            $last4 = "";
            $expires = "";
            
            // Try to get card details from various payment gateway meta
            $card_last4_meta_keys = array('_stripe_card_last4', '_paypal_card_last4', '_square_card_last4', '_card_last4');
            $card_expires_meta_keys = array('_stripe_card_expiry', '_paypal_card_expiry', '_square_card_expiry', '_card_expiry');
            
            foreach ($card_last4_meta_keys as $meta_key) {
                $last4 = get_post_meta($order_id, $meta_key, true);
                if (!empty($last4)) break;
            }
            
            foreach ($card_expires_meta_keys as $meta_key) {
                $expires = get_post_meta($order_id, $meta_key, true);
                if (!empty($expires)) {
                    // Format expires to MMYY if needed
                    $expires = preg_replace('/[^0-9]/', '', $expires);
                    if (strlen($expires) == 4) {
                        // Already in MMYY format
                    } elseif (strlen($expires) == 6) {
                        // MMYYYY format, convert to MMYY
                        $expires = substr($expires, 0, 2) . substr($expires, 4, 2);
                    }
                    break;
                }
            }
            
            // Set defaults if not found
            if (empty($last4)) $last4 = "1234";
            if (empty($expires)) $expires = "1299";
            
            $payments[] = array(
                "PaymentType" => "CC",
                "PaymentAmount" => round($order_total, 2),
                "PaymentSubType" => $payment_method_title ?: "Visa",
                "Last4" => $last4,
                "Expires" => $expires,
                "FirstName" => $billing_first_name ?: "",
                "LastName" => $billing_last_name ?: ""
            );
            
            // Create websale data array matching the sample format exactly
            $websale_data = array(
                "CustomerWebId" => $customer_id ? (string)$customer_id : "",
                "WebSaleId" => "wdm-" . $order_id,
                "SaleEmail" => $billing_email ?: "",
                "SalePhone" => preg_replace('/[^0-9]/', '', $billing_phone ?: ""), // Clean phone number
                "TotalWithTax" => round($order_total, 2),
                "BillingAddress" => array(
                    "Street1" => $billing_address_1 ?: "",
                    "Street2" => $billing_address_2 ?: "",
                    "City" => $billing_city ?: "",
                    "State" => $billing_state ?: "",
                    "Zip" => $billing_postcode ?: "",
                    "Country" => $billing_country ?: ""
                ),
                "ShippingAddress" => array(
                    "Street1" => $shipping_address_1 ?: "",
                    "Street2" => $shipping_address_2 ?: "",
                    "City" => $shipping_city ?: "",
                    "State" => $shipping_state ?: "",
                    "Zip" => $shipping_postcode ?: "",
                    "Country" => $shipping_country ?: ""
                ),
                "ShippingAmt" => round($order_shipping, 2),
                "SoldItems" => $sold_items,
                "Payments" => $payments
            );
            
            // Create JSON file name with incrementing number format
            $prefix_counter = get_option('edge_prefix_counter', 1);
            $json_filename = $prefix_counter . '-WebSale.json';
            
            // Create temporary file
            $temp_file = tempnam(sys_get_temp_dir(), 'edge_websale_');
            file_put_contents($temp_file, json_encode($websale_data, JSON_PRETTY_PRINT));
            
            // Upload to remote
            $remote_path = $outbox_path . '/' . $json_filename;
            $result = $this->upload_file($connection, $remote_path, $temp_file);
            
            // Clean up temp file
            unlink($temp_file);
            
            if ($result) {
                error_log('WebSale JSON uploaded successfully: ' . $json_filename);
                
                // Increment the prefix counter for next use
                update_option('edge_prefix_counter', $prefix_counter + 1);
                
                // Mark order as synced
                update_post_meta($order_id, '_edge_sync', true);
                update_post_meta($order_id, '_edge_last_sync', current_time('mysql'));
            } else {
                error_log('Failed to upload WebSale JSON: ' . $json_filename);
            }
            
        } catch (\Exception $e) {
            error_log('Error creating WebSale JSON: ' . $e->getMessage());
        }
    }

	/**
	 * Cron callback for automatic product import.
	 */
	public function cron_import_products() {
		// Skip if cron is disabled
		if (!get_option('edge_product_enable_cron', 0)) {
			return;
		}
		
		error_log('Starting scheduled Edge product import');
		
		// Set higher PHP limits for the cron job
		@set_time_limit(600); // 10 minutes
		@ini_set('memory_limit', '256M');
		
		// Import products using the existing function
		$result = $this->import_products();
		
		if ($result) {
			error_log('Scheduled product import completed successfully');
		} else {
			error_log('Scheduled product import failed');
		}
	}

	/**
	 * Handle changes to the customer cron setting.
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $new_value The new option value.
	 */
	public function handle_customer_cron_setting_change($old_value, $new_value) {
		// If cron was disabled, remove any scheduled events
		if ($old_value && !$new_value) {
			$timestamp = wp_next_scheduled('edge_scheduled_import');
			if ($timestamp) {
				wp_unschedule_event($timestamp, 'edge_scheduled_import');
				error_log('Edge customer import cron job disabled and removed');
			}
		} 
		// If cron was enabled or interval changed, schedule the event
		else if ($new_value) {
			// Clear any existing scheduled events
			$timestamp = wp_next_scheduled('edge_scheduled_import');
			if ($timestamp) {
				wp_unschedule_event($timestamp, 'edge_scheduled_import');
				error_log('Removed existing Edge customer cron job');
			}
			
			// Schedule a new event
			$interval = get_option('edge_customer_cron_interval', 'daily');
            
            // If using custom minutes, verify the value
            if ($interval === 'edge_customer_custom_minutes') {
                $custom_minutes = intval(get_option('edge_customer_cron_custom_minutes', 30));
                if ($custom_minutes < 5) {
                    $custom_minutes = 5; // Minimum 5 minutes
                    update_option('edge_customer_cron_custom_minutes', $custom_minutes);
                } elseif ($custom_minutes > 1440) {
                    $custom_minutes = 1440; // Maximum 24 hours
                    update_option('edge_customer_cron_custom_minutes', $custom_minutes);
                }
            }
            
			$result = wp_schedule_event(time(), $interval, 'edge_scheduled_import');
			if ($result === false) {
				error_log('Failed to schedule Edge customer cron job with interval: ' . $interval);
			} else {
				error_log('Edge customer import cron job scheduled with interval: ' . $interval);
			}
		}
	}
	
	/**
	 * Handle changes to the product cron setting.
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $new_value The new option value.
	 */
	public function handle_product_cron_setting_change($old_value, $new_value) {
		// If cron was disabled, remove any scheduled events
		if ($old_value && !$new_value) {
			$timestamp = wp_next_scheduled('edge_scheduled_product_import');
			if ($timestamp) {
				wp_unschedule_event($timestamp, 'edge_scheduled_product_import');
				error_log('Edge product import cron job disabled and removed');
			}
		} 
		// If cron was enabled or interval changed, schedule the event
		else if ($new_value) {
			// Clear any existing scheduled events
			$timestamp = wp_next_scheduled('edge_scheduled_product_import');
			if ($timestamp) {
				wp_unschedule_event($timestamp, 'edge_scheduled_product_import');
				error_log('Removed existing Edge product cron job');
			}
			
			// Schedule a new event
			$interval = get_option('edge_product_cron_interval', 'daily');
            
            // If using custom minutes, verify the value
            if ($interval === 'edge_product_custom_minutes') {
                $custom_minutes = intval(get_option('edge_product_cron_custom_minutes', 30));
                if ($custom_minutes < 5) {
                    $custom_minutes = 5; // Minimum 5 minutes
                    update_option('edge_product_cron_custom_minutes', $custom_minutes);
                } elseif ($custom_minutes > 1440) {
                    $custom_minutes = 1440; // Maximum 24 hours
                    update_option('edge_product_cron_custom_minutes', $custom_minutes);
                }
            }
            
			$result = wp_schedule_event(time(), $interval, 'edge_scheduled_product_import');
			if ($result === false) {
				error_log('Failed to schedule Edge product cron job with interval: ' . $interval);
			} else {
				error_log('Edge product import cron job scheduled with interval: ' . $interval);
			}
		}
	}

	/**
	 * AJAX handler for syncing existing WordPress users to EDGE.
	 */
	public function ajax_sync_existing_users() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}
		error_log('Starting sync of existing users');
		// Set timeout to prevent script termination
		set_time_limit(300); // 5 minutes
		@ini_set('memory_limit', '256M'); // Increase memory limit

		// Get the current chunk from the request
		$current_chunk = isset($_POST['chunk']) ? intval($_POST['chunk']) : 0;
		$chunk_size = get_option('edge_sync_existing_chunk_size', 25); // Get from settings, default to 25
		
		// Check if this is the first chunk (start of sync)
		$is_first_chunk = ($current_chunk === 0);
		
		// For the first chunk, we need to get the total user count (not all users)
		if ($is_first_chunk) {
			// Get total user count efficiently without loading user data
			$total_users = count_users();
			$total_user_count = $total_users['total_users'];
			
			error_log('Starting sync of ' . $total_user_count . ' existing WordPress users');
			
			// Store total users and other metadata for the sync process
			update_option('edge_sync_existing_total_users', $total_user_count);
			update_option('edge_sync_existing_total_chunks', ceil($total_user_count / $chunk_size));
			update_option('edge_sync_existing_processed', 0);
			update_option('edge_sync_existing_synced', 0);
			update_option('edge_sync_existing_already_synced', 0);
			update_option('edge_sync_existing_skipped', 0);
			update_option('edge_sync_existing_last_user_id', 0); // Track last processed user ID
			
			// Store connection details for later chunks (same as ajax_import_customers)
			$host = get_option('edge_sftp_host');
			$username = get_option('edge_sftp_username');
			$password = get_option('edge_sftp_password');
			$port = intval(get_option('edge_sftp_port', 22));
			$base_path = get_option('edge_sftp_folder', '/');
			$outbox_path = rtrim($base_path, '/') . '/Outbox';
			
			update_option('edge_sync_existing_sftp_host', $host);
			update_option('edge_sync_existing_sftp_username', $username);
			update_option('edge_sync_existing_sftp_password', $password);
			update_option('edge_sync_existing_sftp_port', $port);
			update_option('edge_sync_existing_outbox_path', $outbox_path);
		}
		
		// Process the current chunk
		try {
			// Get users for current chunk using cursor-based pagination (prevents overlaps)
			$last_user_id = get_option('edge_sync_existing_last_user_id', 0);
			
			global $wpdb;
			// Use direct SQL query for better control over pagination
			$user_ids = $wpdb->get_col($wpdb->prepare(
				"SELECT ID FROM {$wpdb->users} 
				 WHERE ID > %d 
				 ORDER BY ID ASC 
				 LIMIT %d",
				$last_user_id,
				$chunk_size
			));
			
			if (empty($user_ids)) {
				error_log('No more users found for chunk ' . $current_chunk);
				// This means we've processed all users, finalize
				$this->finalize_existing_users_sync();
				
				wp_send_json_success([
					'message' => 'Existing users sync completed successfully',
					'progress' => 100,
					'isComplete' => true,
					'stats' => [
						'total' => get_option('edge_sync_existing_total_users', 0),
						'processed' => get_option('edge_sync_existing_processed', 0),
						'synced' => get_option('edge_sync_existing_synced', 0),
						'already_synced' => get_option('edge_sync_existing_already_synced', 0),
						'skipped' => get_option('edge_sync_existing_skipped', 0)
					]
				]);
				return;
			}
			
			// Get the current progress
			$processed = get_option('edge_sync_existing_processed', 0);
			$synced = get_option('edge_sync_existing_synced', 0);
			$already_synced = get_option('edge_sync_existing_already_synced', 0);
			$skipped = get_option('edge_sync_existing_skipped', 0);
			
			// Get connection details stored during the first chunk
			$host = get_option('edge_sync_existing_sftp_host');
			$username = get_option('edge_sync_existing_sftp_username');
			$password = get_option('edge_sync_existing_sftp_password');
			$port = get_option('edge_sync_existing_sftp_port', 22);
			$outbox_path = get_option('edge_sync_existing_outbox_path');
			
			// Temporarily set connection details to use the factory (same as ajax_import_customers)
			$old_host = get_option('edge_sftp_host');
			$old_username = get_option('edge_sftp_username');
			$old_password = get_option('edge_sftp_password');
			$old_port = get_option('edge_sftp_port');
			
			update_option('edge_sftp_host', $host);
			update_option('edge_sftp_username', $username);
			update_option('edge_sftp_password', $password);
			update_option('edge_sftp_port', $port);
			
			// Connect using factory method (supports both SFTP and FTP)
			$connection = $this->create_connection();
			
			error_log('Processing sync chunk ' . ($current_chunk + 1) . ' (' . count($user_ids) . ' users) - User IDs: ' . implode(', ', $user_ids));
			
			// Process each user in the current chunk
			foreach ($user_ids as $user_id) {
				$processed++;
				
				// Get user data
				$user = get_userdata($user_id);
				if (!$user) {
					$skipped++;
					error_log('User not found for ID: ' . $user_id);
					continue;
				}
				
				// Skip users without email
				if (empty($user->user_email)) {
					$skipped++;
					error_log('User has no email: ' . $user_id);
					continue;
				}
				
				// Check if user is already synced to EDGE
				$is_synced = get_user_meta($user_id, '_edge_sync', true);
				if ($is_synced) {
					$already_synced++;
					error_log('User already synced: ' . $user->user_email . ' (ID: ' . $user_id . ')');
					continue;
				}
				
				// Create customer JSON for EDGE (same as WebSale order process)
				$this->create_customer_json_for_edge($user, $connection, $outbox_path);
				
				// Mark user as synced
				update_user_meta($user_id, '_edge_sync', true);
				update_user_meta($user_id, '_edge_synced_before', true);
				
				$synced++;
				
				error_log('Synced existing user to EDGE: ' . $user->user_email . ' (ID: ' . $user_id . ')');
			}
			
			// Update the last processed user ID (cursor for next chunk)
			$last_processed_user_id = max($user_ids);
			update_option('edge_sync_existing_last_user_id', $last_processed_user_id);
			
			// Close connection properly using helper method
			$this->close_connection($connection);
			
			// Restore original connection settings
			update_option('edge_sftp_host', $old_host);
			update_option('edge_sftp_username', $old_username);
			update_option('edge_sftp_password', $old_password);
			update_option('edge_sftp_port', $old_port);
			
			// Update progress
			update_option('edge_sync_existing_processed', $processed);
			update_option('edge_sync_existing_synced', $synced);
			update_option('edge_sync_existing_already_synced', $already_synced);
			update_option('edge_sync_existing_skipped', $skipped);
			
			// Calculate progress percentage based on processed vs total
			$total_users = get_option('edge_sync_existing_total_users', 1);
			$progress_percent = min(100, round($processed / $total_users * 100));
			
			// Check if we've processed all users or if this chunk was smaller than expected
			$is_complete = (count($user_ids) < $chunk_size);
			
			if ($is_complete) {
				// This is the last chunk, finalize the sync
				$this->finalize_existing_users_sync();
				
				// Return final statistics
				wp_send_json_success([
					'message' => 'Existing users sync completed successfully',
					'progress' => 100,
					'isComplete' => true,
					'stats' => [
						'total' => $total_users,
						'processed' => $processed,
						'synced' => $synced,
						'already_synced' => $already_synced,
						'skipped' => $skipped
					]
				]);
			} else {
				// Return progress for the next chunk
				wp_send_json_success([
					'message' => 'Processing users (last ID: ' . $last_processed_user_id . ')',
					'progress' => $progress_percent,
					'nextChunk' => $current_chunk + 1,
					'isComplete' => false,
					'stats' => [
						'processed' => $processed,
						'synced' => $synced,
						'already_synced' => $already_synced,
						'skipped' => $skipped
					]
				]);
			}
			
		} catch (\Exception $e) {
			error_log('Error processing existing users sync chunk: ' . $e->getMessage());
			wp_send_json_error('Error processing chunk: ' . $e->getMessage());
			$this->cleanup_existing_users_sync();
		}
	}
	
	/**
	 * Finalize the existing users sync
	 */
	private function finalize_existing_users_sync() {
		error_log('Finalizing existing users sync');
		
		try {
			// Store final statistics for display
			$synced = get_option('edge_sync_existing_synced', 0);
			$already_synced = get_option('edge_sync_existing_already_synced', 0);
			$skipped = get_option('edge_sync_existing_skipped', 0);
			$processed = get_option('edge_sync_existing_processed', 0);
			
			error_log('Existing users sync completed: processed ' . $processed . ', synced ' . $synced . 
				', already synced ' . $already_synced . ', skipped ' . $skipped);
			
			// Clean up all sync data
			$this->cleanup_existing_users_sync();
			
		} catch (\Exception $e) {
			error_log('Error finalizing existing users sync: ' . $e->getMessage());
		}
	}
	
	/**
	 * Clean up all existing users sync progress data
	 */
	private function cleanup_existing_users_sync() {
		// Remove all options related to the sync (no longer using transients)
		delete_option('edge_sync_existing_total_users');
		delete_option('edge_sync_existing_total_chunks');
		delete_option('edge_sync_existing_processed');
		delete_option('edge_sync_existing_synced');
		delete_option('edge_sync_existing_already_synced');
		delete_option('edge_sync_existing_skipped');
		delete_option('edge_sync_existing_last_user_id');
		delete_option('edge_sync_existing_sftp_host');
		delete_option('edge_sync_existing_sftp_username');
		delete_option('edge_sync_existing_sftp_password');
		delete_option('edge_sync_existing_sftp_port');
		delete_option('edge_sync_existing_outbox_path');
	}

	/**
	 * Create SFTP or FTP connection based on settings.
	 *
	 * @return \phpseclib3\Net\SFTP|resource Connection object or FTP resource
	 * @throws Exception If connection fails
	 */
	private function create_connection() {
		$connection_type = get_option('edge_connection_type', 'sftp');
		$host = get_option('edge_sftp_host');
		$username = get_option('edge_sftp_username');
		$password = get_option('edge_sftp_password');
		
		if (empty($host) || empty($username) || empty($password)) {
			throw new Exception('Missing connection credentials');
		}
		
		if ($connection_type === 'ftp') {
			$port = intval(get_option('edge_sftp_port', 21)); // Default FTP port
			
			// Set up FTP connection
			$conn_id = ftp_connect($host, $port);
			if (!$conn_id) {
				throw new Exception('FTP connection to host failed');
			}
			
			// Login to FTP
			if (!ftp_login($conn_id, $username, $password)) {
				ftp_close($conn_id);
				throw new Exception('FTP Login Failed - Invalid credentials');
			}
			
			// Enable passive mode for better firewall compatibility
			ftp_pasv($conn_id, true);
			
			return $conn_id;
		} else {
			// SFTP connection using phpseclib3
			require_once ABSPATH . 'vendor/autoload.php';
			$port = intval(get_option('edge_sftp_port', 22)); // Default SFTP port
			$sftp = new \phpseclib3\Net\SFTP($host, $port);
			if (!$sftp->login($username, $password)) {
				throw new Exception('SFTP Login Failed');
			}
			return $sftp;
		}
	}

	/**
	 * Get connection type display name.
	 *
	 * @return string
	 */
	private function get_connection_type_name() {
		$connection_type = get_option('edge_connection_type', 'sftp');
		return strtoupper($connection_type);
	}

	/**
	 * Upload file to remote server (SFTP/FTP).
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 * @param string $remote_path Remote file path
	 * @param string $local_path Local file path
	 * @return bool Success status
	 */
	private function upload_file($connection, $remote_path, $local_path) {
		$connection_type = get_option('edge_connection_type', 'sftp');
		
		if ($connection_type === 'ftp') {
			// Use FTP upload with ASCII mode for text files, BINARY for others
			$file_extension = strtolower(pathinfo($local_path, PATHINFO_EXTENSION));
			$mode = in_array($file_extension, ['txt', 'json', 'xml', 'csv']) ? FTP_ASCII : FTP_BINARY;
			
			return ftp_put($connection, $remote_path, $local_path, $mode);
		} else {
			// Use SFTP upload
			return $connection->put($remote_path, $local_path, \phpseclib3\Net\SFTP::SOURCE_LOCAL_FILE);
		}
	}

	/**
	 * Get file content from remote server.
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 * @param string $remote_path Remote file path
	 * @return string|false File content or false on failure
	 */
	private function get_file($connection, $remote_path) {
		$connection_type = get_option('edge_connection_type', 'sftp');
		
		if ($connection_type === 'ftp') {
			// Create temporary file for FTP download
			$temp_file = tempnam(sys_get_temp_dir(), 'ftp_download_');
			
			if (ftp_get($connection, $temp_file, $remote_path, FTP_BINARY)) {
				$content = file_get_contents($temp_file);
				unlink($temp_file);
				return $content;
			} else {
				if (file_exists($temp_file)) {
					unlink($temp_file);
				}
				return false;
			}
		} else {
			// Use SFTP get
			return $connection->get($remote_path);
		}
	}

	/**
	 * List directory contents.
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 * @param string $path Directory path
	 * @return array|false Directory listing or false on failure
	 */
	private function list_directory($connection, $path) {
		$connection_type = get_option('edge_connection_type', 'sftp');
		
		if ($connection_type === 'ftp') {
			// Get raw directory listing for FTP
			$list = ftp_rawlist($connection, $path);
			if ($list === false) {
				return false;
			}
			
			// Parse FTP listing to extract directories and files with metadata
			$items = array();
			foreach ($list as $item) {
				// Parse Unix-style listing (most common)
				if (preg_match('/^([\-ld])([\-rwx]{9})\s+\d+\s+\w+\s+\w+\s+(\d+)\s+(\w{3}\s+\d{1,2}\s+[\d:]+)\s+(.+)$/', $item, $matches)) {
					$name = $matches[5];
					$is_dir = ($matches[1] === 'd');
					$size = intval($matches[3]);
					
					// Skip . and .. entries
					if ($name === '.' || $name === '..') {
						continue;
					}
					
					$items[$name] = array(
						'type' => $is_dir ? 2 : 1, // 2 = directory, 1 = file (to match SFTP format)
						'size' => $size,
						'mtime' => strtotime($matches[4]) ?: time()
					);
				}
			}
			
			return $items;
		} else {
			// Use SFTP rawlist
			return $connection->rawlist($path);
		}
	}

	/**
	 * Check if path exists on remote server.
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 * @param string $path Remote path
	 * @return bool True if path exists
	 */
	private function file_exists($connection, $path) {
		$connection_type = get_option('edge_connection_type', 'sftp');
		
		if ($connection_type === 'ftp') {
			// For FTP, try to get file size (works for files) or change directory (works for directories)
			$size = ftp_size($connection, $path);
			if ($size !== -1) {
				return true; // File exists
			}
			
			// Try as directory
			$current_dir = ftp_pwd($connection);
			if (ftp_chdir($connection, $path)) {
				ftp_chdir($connection, $current_dir); // Restore original directory
				return true;
			}
			
			return false;
		} else {
			// Use SFTP file_exists
			return $connection->file_exists($path);
		}
	}

	/**
	 * Get last connection error message.
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 * @return string Error message
	 */
	private function get_connection_error($connection) {
		$connection_type = get_option('edge_connection_type', 'sftp');
		
		if ($connection_type === 'ftp') {
			// FTP doesn't have specific error messages, return generic
			return 'FTP operation failed';
		} else {
			// Use SFTP error method
			return method_exists($connection, 'getLastSFTPError') ? $connection->getLastSFTPError() : 'SFTP operation failed';
		}
	}

	/**
	 * Close connection properly.
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 */
	private function close_connection($connection) {
		$connection_type = get_option('edge_connection_type', 'sftp');
		
		if ($connection_type === 'ftp') {
			if (is_resource($connection)) {
				ftp_close($connection);
			}
		}
		// SFTP connections are closed automatically when the object is destroyed
	}

}


