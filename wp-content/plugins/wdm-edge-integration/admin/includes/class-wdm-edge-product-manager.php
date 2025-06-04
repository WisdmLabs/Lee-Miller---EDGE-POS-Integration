<?php

/**
 * Product management functionality for EDGE integration.
 *
 * @link       https://www.wisdmlabs.com
 * @since      1.0.0
 *
 * @package    Wdm_Edge_Integration
 * @subpackage Wdm_Edge_Integration/admin/includes
 */

/**
 * Product management functionality for EDGE integration.
 *
 * Handles product importing, syncing, and management operations.
 *
 * @package    Wdm_Edge_Integration
 * @subpackage Wdm_Edge_Integration/admin/includes
 * @author     WisdmLabs <info@wisdmlabs.com>
 */
class Wdm_Edge_Product_Manager {

	/**
	 * The connection handler instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Wdm_Edge_Connection_Handler    $connection_handler    The connection handler instance.
	 */
	private $connection_handler;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      Wdm_Edge_Connection_Handler    $connection_handler    The connection handler instance.
	 */
	public function __construct( $connection_handler ) {
		$this->connection_handler = $connection_handler;
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
			$connection = $this->connection_handler->create_connection();
			
			// Step 2: List files in the Inbox directory
			$files = $this->connection_handler->list_directory($connection, $inbox_path);
			if ($files === false) {
				error_log('Failed to list files in Inbox during product import');
				$this->connection_handler->close_connection($connection);
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
				$this->connection_handler->close_connection($connection);
				return false;
			}

			// Step 4: Download and read the JSON file
			$json_content = $this->connection_handler->get_file($connection, $inbox_path . '/' . $latest_file);
			if ($json_content === false) {
				error_log('Failed to download ItemList.json file');
				$this->connection_handler->close_connection($connection);
				return false;
			}
			
			// Step 5: Decode JSON and check for errors
			$items_data = json_decode($json_content, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				error_log('JSON decode error during product import: ' . json_last_error_msg());
				$this->connection_handler->close_connection($connection);
				return false;
			}
			
			// Step 6: Process the items
			$total_items = count($items_data['Items']);
			
			error_log('Processing ' . $total_items . ' products');
			
			// Check if WooCommerce is active
			if (!class_exists('WooCommerce')) {
				error_log('WooCommerce is not active, cannot import products');
				$this->connection_handler->close_connection($connection);
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
			$this->connection_handler->close_connection($connection);
			return true;
			
		} catch (\Exception $e) {
			error_log('Error during product import: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * AJAX handler for importing products with chunked processing.
	 */
	public function ajax_import_products() {
		// Verify nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'edt_sync_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
		}
		
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
				$connection = $this->connection_handler->create_connection();
				
				// Step 2: List files in the Inbox directory
				$files = $this->connection_handler->list_directory($connection, $inbox_path);
				if ($files === false) {
					error_log('Failed to list files in Inbox during product import');
					$this->connection_handler->close_connection($connection);
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
					$this->connection_handler->close_connection($connection);
					wp_send_json_error('No ItemList.json file found');
				}

				// Step 4: Download and read the JSON file
				$json_content = $this->connection_handler->get_file($connection, $inbox_path . '/' . $latest_file);
				if ($json_content === false) {
					error_log('Failed to download ItemList.json file');
					$this->connection_handler->close_connection($connection);
					wp_send_json_error('Failed to download ItemList.json file');
				}
      
				// Close connection after getting the file
				$this->connection_handler->close_connection($connection);
				
				error_log('JSON content length: ' . strlen($json_content)); // Log length instead of content

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
			$connection = $this->connection_handler->create_connection();
			
			error_log('Processing product chunk ' . ($current_chunk + 1) . ' of ' . get_option('edge_ajax_products_import_total_chunks'));
			
			// Check if WooCommerce is active
			if (!class_exists('WooCommerce')) {
				error_log('WooCommerce is not active, cannot import products');
				$this->connection_handler->close_connection($connection);
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
			$this->connection_handler->close_connection($connection);
			
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
	 * Cron callback for automatic product import with chunked processing.
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
		
		// Check if we're in the middle of processing a chunked import
		$import_in_progress = get_option('edge_product_import_in_progress', false);
		$current_chunk = get_option('edge_product_import_current_chunk', 0);
		$chunk_size = get_option('edge_product_chunk_size', 100); // Get from settings
		
		// Retrieve the base path from settings
		$base_path = get_option('edge_sftp_folder', '/');
		$inbox_path = rtrim($base_path, '/') . '/Inbox';

		error_log('Using Inbox path for products: ' . $inbox_path);

		try {
			// Step 1: Connect using factory
			$connection = $this->connection_handler->create_connection();
			
			// If we're not continuing an existing import, start a new one
			if (!$import_in_progress) {
				// Step 2: List files in the Inbox directory
				$files = $this->connection_handler->list_directory($connection, $inbox_path);
				if ($files === false) {
					error_log('Failed to list files in Inbox during scheduled product import');
					$this->connection_handler->close_connection($connection);
					return;
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
					error_log('No ItemList.json file found during scheduled product import');
					$this->connection_handler->close_connection($connection);
					return;
				}

				// Step 4: Download and read the JSON file
				$json_content = $this->connection_handler->get_file($connection, $inbox_path . '/' . $latest_file);
				if ($json_content === false) {
					error_log('Failed to download ItemList.json file during scheduled import');
					$this->connection_handler->close_connection($connection);
					return;
				}
				
				// Step 5: Decode JSON and check for errors
				$items_data = json_decode($json_content, true);
				if (json_last_error() !== JSON_ERROR_NONE) {
					error_log('JSON decode error during scheduled product import: ' . json_last_error_msg());
					$this->connection_handler->close_connection($connection);
					return;
				}
				
				// Store the data in chunks for processing
				$total_products = count($items_data['Items']);
				error_log('Starting chunked import of ' . $total_products . ' products');
				
				// Initialize import progress
				update_option('edge_product_import_in_progress', true);
				update_option('edge_product_import_current_chunk', 0);
				update_option('edge_product_import_total_chunks', ceil($total_products / $chunk_size));
				update_option('edge_product_import_processed', 0);
				update_option('edge_product_import_created', 0);
				update_option('edge_product_import_updated', 0);
				update_option('edge_product_import_skipped', 0);
				
				// Store the products data in chunks to avoid memory issues
				$chunks = array_chunk($items_data['Items'], $chunk_size);
				foreach ($chunks as $index => $chunk) {
					set_transient('edge_product_import_chunk_' . $index, $chunk, DAY_IN_SECONDS);
				}
				
				// Free up memory
				unset($items_data);
				unset($json_content);
				unset($chunks);
			}
			
			// Get the current chunk data
			$current_chunk_data = get_transient('edge_product_import_chunk_' . $current_chunk);
			if (!$current_chunk_data) {
				error_log('Failed to retrieve product chunk ' . $current_chunk . ' data');
				$this->cleanup_cron_product_import();
				$this->connection_handler->close_connection($connection);
				return;
			}
			
			// Process the current chunk
			$processed = get_option('edge_product_import_processed', 0);
			$created = get_option('edge_product_import_created', 0);
			$updated = get_option('edge_product_import_updated', 0);
			$skipped = get_option('edge_product_import_skipped', 0);
			
			error_log('Processing product chunk ' . ($current_chunk + 1) . ' of ' . get_option('edge_product_import_total_chunks'));
			
			// Check if WooCommerce is active
			if (!class_exists('WooCommerce')) {
				error_log('WooCommerce is not active, cannot import products');
				$this->cleanup_cron_product_import();
				$this->connection_handler->close_connection($connection);
				return;
			}
			
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
			
			// Update progress
			update_option('edge_product_import_processed', $processed);
			update_option('edge_product_import_created', $created);
			update_option('edge_product_import_updated', $updated);
			update_option('edge_product_import_skipped', $skipped);
			
			// Clean up the current chunk transient
			delete_transient('edge_product_import_chunk_' . $current_chunk);
			
			// Move to the next chunk
			$current_chunk++;
			update_option('edge_product_import_current_chunk', $current_chunk);
			
			// Check if we've processed all chunks
			if ($current_chunk >= get_option('edge_product_import_total_chunks')) {
				// All chunks processed, finalize import
				$this->finalize_cron_product_import();
				
				error_log('Scheduled chunked product import completed: created ' . $created . ', updated ' . $updated . ', skipped ' . $skipped);
			} else {
				// Schedule next chunk processing
				wp_schedule_single_event(time() + 30, 'edge_process_next_product_chunk');
				error_log('Scheduled next product chunk processing');
			}
			
			// Close connection
			$this->connection_handler->close_connection($connection);
			
		} catch (\Exception $e) {
			error_log('Error during scheduled product import: ' . $e->getMessage());
			$this->cleanup_cron_product_import();
			if (isset($connection)) {
				$this->connection_handler->close_connection($connection);
			}
		}
	}

	/**
	 * Process the next chunk of product imports
	 */
	public function process_next_product_chunk() {
		// Call the main cron import function to continue processing
		$this->cron_import_products();
	}

	/**
	 * Finalize the cron product import
	 */
	private function finalize_cron_product_import() {
		error_log('Finalizing cron product import');
		
		try {
			// Store final statistics
			$created = get_option('edge_product_import_created', 0);
			$updated = get_option('edge_product_import_updated', 0);
			$skipped = get_option('edge_product_import_skipped', 0);
			
			// Update the main product statistics options
			update_option('edge_products_created', $created);
			update_option('edge_products_updated', $updated);
			update_option('edge_products_skipped', $skipped);
			
			// Clean up all import progress data
			$this->cleanup_cron_product_import();
			
		} catch (\Exception $e) {
			error_log('Error finalizing cron product import: ' . $e->getMessage());
		}
	}
	
	/**
	 * Clean up all cron product import progress data
	 */
	private function cleanup_cron_product_import() {
		// Remove all transients and options related to the cron product import
		$total_chunks = get_option('edge_product_import_total_chunks', 0);
		for ($i = 0; $i < $total_chunks; $i++) {
			delete_transient('edge_product_import_chunk_' . $i);
		}
		
		delete_option('edge_product_import_in_progress');
		delete_option('edge_product_import_current_chunk');
		delete_option('edge_product_import_total_chunks');
		delete_option('edge_product_import_processed');
		delete_option('edge_product_import_created');
		delete_option('edge_product_import_updated');
		delete_option('edge_product_import_skipped');
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
			
			if ($this->connection_handler->file_exists($connection, $remote_image_path)) {
				$image_content = $this->connection_handler->get_file($connection, $remote_image_path);
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

} 