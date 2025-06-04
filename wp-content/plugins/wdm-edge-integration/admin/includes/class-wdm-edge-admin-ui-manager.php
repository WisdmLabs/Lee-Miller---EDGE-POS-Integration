<?php

/**
 * Admin UI management functionality for the EDGE Integration plugin.
 *
 * @link       https://www.wisdmlabs.com
 * @since      1.0.0
 *
 * @package    Wdm_Edge_Integration
 * @subpackage Wdm_Edge_Integration/admin/includes
 */

/**
 * Admin UI management functionality for the EDGE Integration plugin.
 *
 * Handles all admin page rendering, menu creation, and UI elements.
 *
 * @package    Wdm_Edge_Integration
 * @subpackage Wdm_Edge_Integration/admin/includes
 * @author     WisdmLabs <info@wisdmlabs.com>
 */
class Wdm_Edge_Admin_UI_Manager {

	/**
	 * The plugin version.
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
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $version ) {
		$this->version = $version;
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
		include plugin_dir_path( __FILE__ ) . '../partials/wdm-edge-sftp-setup.php';
		echo '</div>';
		
		// Enqueue the SFTP setup script
		$this->enqueue_sftp_setup_script();
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
	 * Enqueue the script for handling the import button click.
	 */
	public function enqueue_import_script() {
		wp_enqueue_script( 'edt-sync-import', plugin_dir_url( dirname(__FILE__) ) . 'js/edt-sync-import.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'edt-sync-additional', plugin_dir_url( dirname(__FILE__) ) . 'js/edt-sync-additional.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( 'edt-sync-import', 'edtSyncAjax', array( 
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'edt_sync_nonce' )
		) );
	}

	/**
	 * Enqueue the script for SFTP setup functionality.
	 */
	public function enqueue_sftp_setup_script() {
		wp_enqueue_script( 'wdm-edge-sftp-setup', plugin_dir_url( dirname(__FILE__) ) . 'js/wdm-edge-sftp-setup.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( 'wdm-edge-sftp-setup', 'edgeSftpAjax', array( 
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'edt_sync_nonce' )
		) );
	}

} 