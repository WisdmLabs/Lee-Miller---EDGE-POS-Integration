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
	 * @param    Wdm_Edge_Connection_Handler    $connection_handler    The connection handler instance.
	 */
	public function __construct($connection_handler) {
		$this->connection_handler = $connection_handler;
	}

	/**
	 * Get the latest ItemList.json file from the inbox
	 * 
	 * @param mixed $connection The connection object
	 * @param string $inbox_path The inbox path
	 * @return array|false Returns [filename, content] on success, false on failure
	 */
	private function get_latest_item_list($connection, $inbox_path) {
		$files = $this->connection_handler->list_directory($connection, $inbox_path);
		if ($files === false) {
			return false;
		}

		$latest_file = '';
		$latest_time = 0;
		foreach (array_keys($files) as $file) {
			if (substr($file, -strlen('ItemList.json')) === 'ItemList.json') {
				$file_time = $files[$file]['mtime'];
				if ($file_time > $latest_time) {
					$latest_time = $file_time;
					$latest_file = $file;
				}
			}
		}

		if (empty($latest_file)) {
			return false;
		}

		$json_content = $this->connection_handler->get_file($connection, $inbox_path . '/' . $latest_file);
		if ($json_content === false) {
			return false;
		}

		$items_data = json_decode($json_content, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return false;
		}

		return ['filename' => $latest_file, 'content' => $items_data];
	}

	/**
	 * Process a single product
	 * 
	 * @param array $item Product data
	 * @param mixed $connection Connection object
	 * @param string $inbox_path Inbox path
	 * @return array Stats about the operation [created, updated, skipped]
	 */
	private function process_single_product($item, $connection, $inbox_path) {
		$stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];
		
		$sku = $item['Key'];
		$product_id = $item['Key'];
		$product_name = $item['PairValue']['ItemDesc'];
		$product_price = $item['PairValue']['ItemRetailPrice'];
		$product_image = $item['PairValue']['ItemImage'];
		
		$existing_product_id = $this->get_product_by_edge_id($product_id);
		
		if ($existing_product_id) {
			$product = wc_get_product($existing_product_id);
			if ($product) {
				$product->set_name($product_name);
				$product->set_regular_price($product_price);
				$product->set_sku($sku);
				$product->save();
				
				if (!empty($product_image)) {
					$this->set_product_image($existing_product_id, $product_image, $connection, $inbox_path);
				}
				
				$stats['updated']++;
			} else {
				$stats['skipped']++;
			}
		} else {
			$product = new WC_Product_Simple();
			$product->set_name($product_name);
			$product->set_regular_price($product_price);
			$product->set_status('publish');
			$product->set_sku($sku);
			$new_product_id = $product->save();
			
			if ($new_product_id) {
				update_post_meta($new_product_id, '_edge_id', $product_id);
				
				if (!empty($product_image)) {
					$this->set_product_image($new_product_id, $product_image, $connection, $inbox_path);
				}
				
				$stats['created']++;
			} else {
				$stats['skipped']++;
			}
		}
		
		return $stats;
	}

	/**
	 * Import products from ItemList.json file.
	 * This can be called manually or via cron.
	 */
	public function import_products() {
		@set_time_limit(600);
		
		$stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];
		$base_path = get_option('edge_sftp_folder', '/');
		$inbox_path = rtrim($base_path, '/') . '/Inbox';

		try {
			$connection = $this->connection_handler->create_connection();
			
			$item_list = $this->get_latest_item_list($connection, $inbox_path);
			if ($item_list === false) {
				$this->connection_handler->close_connection($connection);
				return false;
			}

			if (!class_exists('WooCommerce')) {
				$this->connection_handler->close_connection($connection);
				return false;
			}
			
			foreach ($item_list['content']['Items'] as $item) {
				$result = $this->process_single_product($item, $connection, $inbox_path);
				$stats['created'] += $result['created'];
				$stats['updated'] += $result['updated'];
				$stats['skipped'] += $result['skipped'];
			}
			
			$this->update_import_stats($stats);
			$this->connection_handler->close_connection($connection);
			return true;
			
		} catch (\Exception $e) {
			if (isset($connection)) {
				$this->connection_handler->close_connection($connection);
			}
			return false;
		}
	}

	/**
	 * AJAX handler for importing products with chunked processing.
	 */
	public function ajax_import_products() {
		if (!$this->validate_ajax_request()) {
			wp_send_json_error('Invalid request');
			return;
		}

		set_time_limit(300);
		$current_chunk = isset($_POST['chunk']) ? intval($_POST['chunk']) : 0;
		$chunk_size = get_option('edge_product_chunk_size', 100);
		
		try {
			if ($current_chunk === 0) {
				if (!$this->initialize_chunked_import('ajax')) {
					wp_send_json_error('Failed to initialize import');
					return;
				}
			}

			$result = $this->process_chunk($current_chunk, 'ajax');
			if ($result === false) {
				wp_send_json_error('Failed to process chunk');
				$this->cleanup_import('ajax');
				return;
			}

			$this->send_ajax_response($current_chunk, $result);
			
		} catch (\Exception $e) {
			wp_send_json_error('Error: ' . $e->getMessage());
			$this->cleanup_import('ajax');
		}
	}

	/**
	 * Cron callback for automatic product import with chunked processing.
	 */
	public function cron_import_products() {
		if (!get_option('edge_product_enable_cron', 0)) {
			return;
		}

		@set_time_limit(600);
		@ini_set('memory_limit', '256M');
		
		$current_chunk = get_option('edge_product_import_current_chunk', 0);
		
		try {
			if ($current_chunk === 0 && !$this->initialize_chunked_import('cron')) {
				return;
			}

			$result = $this->process_chunk($current_chunk, 'cron');
			if ($result === false) {
				$this->cleanup_import('cron');
				return;
			}

			if ($result['is_complete']) {
				$this->finalize_import('cron');
			} else {
				wp_schedule_single_event(time() + 30, 'edge_process_next_product_chunk');
			}
			
		} catch (\Exception $e) {
			$this->cleanup_import('cron');
		}
	}

	/**
	 * Initialize a chunked import process
	 * 
	 * @param string $type Either 'ajax' or 'cron'
	 * @return bool Success status
	 */
	private function initialize_chunked_import($type) {
		$base_path = get_option('edge_sftp_folder', '/');
		$inbox_path = rtrim($base_path, '/') . '/Inbox';
		$chunk_size = get_option('edge_product_chunk_size', 100);

		try {
			$connection = $this->connection_handler->create_connection();
			
			$item_list = $this->get_latest_item_list($connection, $inbox_path);
			if ($item_list === false) {
				$this->connection_handler->close_connection($connection);
				return false;
			}

			$total_products = count($item_list['content']['Items']);
			$total_chunks = ceil($total_products / $chunk_size);

			$this->store_import_settings($type, [
				'total_products' => $total_products,
				'total_chunks' => $total_chunks,
				'processed' => 0,
				'created' => 0,
				'updated' => 0,
				'skipped' => 0,
				'inbox_path' => $inbox_path
			]);

			$chunks = array_chunk($item_list['content']['Items'], $chunk_size);
			foreach ($chunks as $index => $chunk) {
				set_transient("{$type}_product_import_chunk_{$index}", $chunk, DAY_IN_SECONDS);
			}

			$this->connection_handler->close_connection($connection);
			return true;
			
		} catch (\Exception $e) {
			if (isset($connection)) {
				$this->connection_handler->close_connection($connection);
			}
			return false;
		}
	}

	/**
	 * Process a chunk of products
	 * 
	 * @param int $chunk_number The chunk number to process
	 * @param string $type Either 'ajax' or 'cron'
	 * @return array|false Progress data or false on failure
	 */
	private function process_chunk($chunk_number, $type) {
		$chunk_data = get_transient("{$type}_product_import_chunk_{$chunk_number}");
		if (!$chunk_data || !class_exists('WooCommerce')) {
			return false;
		}

		$connection = $this->connection_handler->create_connection();
		$inbox_path = get_option("{$type}_product_import_inbox_path");
		
		$stats = $this->get_import_stats($type);
		
		foreach ($chunk_data as $item) {
			$stats['processed']++;
			$result = $this->process_single_product($item, $connection, $inbox_path);
			$stats['created'] += $result['created'];
			$stats['updated'] += $result['updated'];
			$stats['skipped'] += $result['skipped'];
		}
		
		$this->update_import_stats($type, $stats);
		delete_transient("{$type}_product_import_chunk_{$chunk_number}");
		
		$total_chunks = get_option("{$type}_product_import_total_chunks");
		$is_complete = ($chunk_number + 1) >= $total_chunks;
		
		$this->connection_handler->close_connection($connection);
		
		return array_merge($stats, ['is_complete' => $is_complete]);
	}

	/**
	 * Store import settings
	 * 
	 * @param string $type Either 'ajax' or 'cron'
	 * @param array $settings Settings to store
	 */
	private function store_import_settings($type, $settings) {
		foreach ($settings as $key => $value) {
			update_option("{$type}_product_import_{$key}", $value);
		}
	}

	/**
	 * Get current import statistics
	 * 
	 * @param string $type Either 'ajax' or 'cron'
	 * @return array Current statistics
	 */
	private function get_import_stats($type) {
		return [
			'processed' => get_option("{$type}_product_import_processed", 0),
			'created' => get_option("{$type}_product_import_created", 0),
			'updated' => get_option("{$type}_product_import_updated", 0),
			'skipped' => get_option("{$type}_product_import_skipped", 0)
		];
	}

	/**
	 * Update import statistics
	 * 
	 * @param string $type Either 'ajax' or 'cron'
	 * @param array $stats Statistics to update
	 */
	private function update_import_stats($type, $stats) {
		foreach ($stats as $key => $value) {
			update_option("{$type}_product_import_{$key}", $value);
		}
	}

	/**
	 * Finalize the import process
	 * 
	 * @param string $type Either 'ajax' or 'cron'
	 */
	private function finalize_import($type) {
		try {
			$stats = $this->get_import_stats($type);
			
			update_option('edge_products_created', $stats['created']);
			update_option('edge_products_updated', $stats['updated']);
			update_option('edge_products_skipped', $stats['skipped']);
			
			$this->cleanup_import($type);
		} catch (\Exception $e) {
			// Log error but continue cleanup
			$this->cleanup_import($type);
		}
	}

	/**
	 * Clean up import data
	 * 
	 * @param string $type Either 'ajax' or 'cron'
	 */
	private function cleanup_import($type) {
		$total_chunks = get_option("{$type}_product_import_total_chunks", 0);
		for ($i = 0; $i < $total_chunks; $i++) {
			delete_transient("{$type}_product_import_chunk_{$i}");
		}

		$options_to_delete = [
			'total_products',
			'total_chunks',
			'processed',
			'created',
			'updated',
			'skipped',
			'inbox_path',
			'in_progress',
			'current_chunk'
		];

		foreach ($options_to_delete as $option) {
			delete_option("{$type}_product_import_{$option}");
		}
	}

	/**
	 * Validate AJAX request
	 * 
	 * @return bool Whether the request is valid
	 */
	private function validate_ajax_request() {
		return isset($_POST['nonce']) 
			&& wp_verify_nonce($_POST['nonce'], 'edt_sync_nonce')
			&& current_user_can('manage_options');
	}

	/**
	 * Send AJAX response
	 * 
	 * @param int $current_chunk Current chunk number
	 * @param array $result Processing result
	 */
	private function send_ajax_response($current_chunk, $result) {
		$total_chunks = get_option('edge_ajax_products_import_total_chunks', 1);
		$progress_percent = min(100, round(($current_chunk + 1) / $total_chunks * 100));
		
		if ($result['is_complete']) {
			$this->finalize_import('ajax');
			wp_send_json_success([
				'message' => 'Product import completed successfully',
				'progress' => 100,
				'isComplete' => true,
				'stats' => $result
			]);
		} else {
			wp_send_json_success([
				'message' => 'Processing chunk ' . ($current_chunk + 1) . ' of ' . $total_chunks,
				'progress' => $progress_percent,
				'nextChunk' => $current_chunk + 1,
				'isComplete' => false,
				'stats' => $result
			]);
		}
	}

	/**
	 * Process the next chunk of product imports
	 */
	public function process_next_product_chunk() {
		$this->cron_import_products();
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
			$upload_dir = wp_upload_dir();
			$product_images_dir = $upload_dir['basedir'] . '/edge-products';
			
			if (!file_exists($product_images_dir)) {
				wp_mkdir_p($product_images_dir);
			}
			
			$local_image_path = $product_images_dir . '/' . $image_name;
			$remote_image_path = $inbox_path . '/' . $image_name;
			
			if ($this->connection_handler->file_exists($connection, $remote_image_path)) {
				$image_content = $this->connection_handler->get_file($connection, $remote_image_path);
				if ($image_content !== false && file_put_contents($local_image_path, $image_content)) {
					$filetype = wp_check_filetype($image_name, null);
					$attachment = array(
						'post_mime_type' => $filetype['type'],
						'post_title' => sanitize_file_name($image_name),
						'post_content' => '',
						'post_status' => 'inherit'
					);
					
					$attachment_id = wp_insert_attachment($attachment, $local_image_path, $product_id);
					
					if (!is_wp_error($attachment_id)) {
						require_once(ABSPATH . 'wp-admin/includes/image.php');
						$attachment_data = wp_generate_attachment_metadata($attachment_id, $local_image_path);
						wp_update_attachment_metadata($attachment_id, $attachment_data);
						set_post_thumbnail($product_id, $attachment_id);
					}
				}
			}
		} catch (\Exception $e) {
			// Log error but continue processing
		}
	}
} 