<?php

/**
 * Customer management functionality for EDGE integration.
 *
 * @link       https://www.wisdmlabs.com
 * @since      1.0.0
 *
 * @package    Wdm_Edge_Integration
 * @subpackage Wdm_Edge_Integration/admin/includes
 */

/**
 * Customer management functionality for EDGE integration.
 *
 * Handles customer importing, syncing, and management operations.
 *
 * @package    Wdm_Edge_Integration
 * @subpackage Wdm_Edge_Integration/admin/includes
 * @author     WisdmLabs <info@wisdmlabs.com>
 */
class Wdm_Edge_Customer_Manager {

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
	public function __construct( $connection_handler ) {
		$this->connection_handler = $connection_handler;
	}

	/**
	 * Cron callback for automatic customer import.
	 */
	public function cron_import_customers() {
		// Skip if cron is disabled
		if (!get_option('edge_customer_enable_cron', 0)) {
			return;
		}
		

		
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




		try {
			// Step 1: Connect to SFTP/FTP
			$connection = $this->connection_handler->create_connection();
			
			// If we're not continuing an existing import, start a new one
			if (!$import_in_progress) {
				// Step 2: List files in the Inbox directory
				$files = $this->connection_handler->list_directory($connection, $inbox_path);
				if ($files === false) {

					$this->connection_handler->close_connection($connection);
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

					$this->connection_handler->close_connection($connection);
					return;
				}

				// Step 4: Download and read the JSON file
				$json_content = $this->connection_handler->get_file($connection, $inbox_path . '/' . $latest_file);
				if ($json_content === false) {

					$this->connection_handler->close_connection($connection);
					return;
				}
				
				// Step 5: Decode JSON and check for errors
				$customers_data = json_decode($json_content, true);
				if (json_last_error() !== JSON_ERROR_NONE) {

					$this->connection_handler->close_connection($connection);
					return;
				}
				
				// Store the data in a transient for chunked processing
				$total_customers = count($customers_data['Customers']);

				
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
			}
			
			// Get the current chunk data
			$current_chunk_data = get_transient('edge_import_chunk_' . $current_chunk);
			if (!$current_chunk_data) {

				$this->cleanup_import_progress();
				return;
			}
			
			// Process the current chunk
			$processed = get_option('edge_import_processed', 0);
			$created = get_option('edge_import_created', 0);
			$updated = get_option('edge_import_updated', 0);
			$skipped = get_option('edge_import_skipped', 0);
			$new_customers = get_option('edge_import_new_customers', array());
			

			
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

				$this->cleanup_import_progress();
				return;
			}
			
			$temp_file = plugin_dir_path(__FILE__) . $new_file_name;
			file_put_contents($temp_file, $new_json_content);

			// Upload the new JSON file to the Outbox
			$this->connection_handler->upload_file($connection, $outbox_path . '/' . $new_file_name, $temp_file);
			
			// Close connection properly
			$this->connection_handler->close_connection($connection);
			
			// Clean up temporary file
			@unlink($temp_file);


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

			return;
		}
		
		// Generate password reset key
		$key = get_password_reset_key($user);
		if (is_wp_error($key)) {

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

		} else {

		}
	}

	/**
	 * AJAX handler for importing customers.
	 */
	public function ajax_import_customers() {
		// Verify nonce for security


		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'edt_sync_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
		}
		
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




			// SFTP connection details
			$host = get_option('edge_sftp_host');
			$username = get_option('edge_sftp_username');
			$password = get_option('edge_sftp_password');
			$port = intval(get_option('edge_sftp_port', 22));

			require_once ABSPATH . 'vendor/autoload.php';

			try {
				// Step 1: Connect to SFTP
				$connection = $this->connection_handler->create_connection();
				
				// Step 2: List files in the Inbox directory
				$files = $this->connection_handler->list_directory($connection, $inbox_path);
				if ($files === false) {

					$this->connection_handler->close_connection($connection);
					wp_send_json_error('Failed to list files in Inbox');
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

					$this->connection_handler->close_connection($connection);
					wp_send_json_error('No FullCustomerList.json file found');
				}

				// Step 4: Download and read the JSON file
				$json_content = $this->connection_handler->get_file($connection, $inbox_path . '/' . $latest_file);
				if ($json_content === false) {

					$this->connection_handler->close_connection($connection);
					wp_send_json_error('Failed to download file: ' . $latest_file);
				}
				
				// Close connection after getting the file
				$this->connection_handler->close_connection($connection);
				
				error_log('JSON content length: ' . strlen($json_content)); // Log length instead of content

				// Step 5: Decode JSON and check for errors
				$customers_data = json_decode($json_content, true);
				if (json_last_error() !== JSON_ERROR_NONE) {

					wp_send_json_error('JSON decode error: ' . json_last_error_msg());
				}

				$total_customers = count($customers_data['Customers']);

				
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

				wp_send_json_error($e->getMessage());
				return;
			}
		}
		
		// Process the current chunk
		try {
			// Get the current chunk data
			$current_chunk_data = get_transient('edge_ajax_import_chunk_' . $current_chunk);
			if (!$current_chunk_data) {

				wp_send_json_error('Failed to retrieve chunk data. The import process may have timed out.');
				return;
			}
			
			// Get the current progress
			$processed = get_option('edge_ajax_import_processed', 0);
			$created = get_option('edge_ajax_import_created', 0);
			$updated = get_option('edge_ajax_import_updated', 0);
			$skipped = get_option('edge_ajax_import_skipped', 0);
			$new_customers = get_option('edge_ajax_import_new_customers', array());
			

			
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

			wp_send_json_error('Error processing chunk: ' . $e->getMessage());
			$this->cleanup_ajax_import();
		}
	}

	/**
	 * Finalize the AJAX import by creating and uploading the output file
	 */
	private function finalize_ajax_import() {

		
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
			$connection = $this->connection_handler->create_connection();
			
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

				return;
			}
			
			$temp_file = plugin_dir_path(__FILE__) . $new_file_name;
			file_put_contents($temp_file, $new_json_content);

			// Upload the new JSON file to the Outbox
			$this->connection_handler->upload_file($connection, $outbox_path . '/' . $new_file_name, $temp_file);
			
			// Close connection properly
			$this->connection_handler->close_connection($connection);
			
			// Clean up temporary file
			@unlink($temp_file);


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
	 * AJAX handler for syncing existing WordPress users to EDGE.
	 */
	public function ajax_sync_existing_users() {
		// Verify nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'edt_sync_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
		}
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

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
			$connection = $this->connection_handler->create_connection();
			

			
			// Process each user in the current chunk
			foreach ($user_ids as $user_id) {
				$processed++;
				
				// Get user data
				$user = get_userdata($user_id);
				if (!$user) {
					$skipped++;

					continue;
				}
				
				// Skip users without email
				if (empty($user->user_email)) {
					$skipped++;

					continue;
				}
				
				// Check if user is already synced to EDGE
				$is_synced = get_user_meta($user_id, '_edge_sync', true);
				if ($is_synced) {
					$already_synced++;

					continue;
				}
				
				// Create customer JSON for EDGE (same as WebSale order process)
				$this->create_customer_json_for_edge($user, $connection, $outbox_path);
				
				// Mark user as synced
				update_user_meta($user_id, '_edge_sync', true);
				update_user_meta($user_id, '_edge_synced_before', true);
				
				$synced++;
				

			}
			
			// Update the last processed user ID (cursor for next chunk)
			$last_processed_user_id = max($user_ids);
			update_option('edge_sync_existing_last_user_id', $last_processed_user_id);
			
			// Close connection properly using helper method
			$this->connection_handler->close_connection($connection);
			
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

			wp_send_json_error('Error processing chunk: ' . $e->getMessage());
			$this->cleanup_existing_users_sync();
		}
	}

	/**
	 * Finalize the existing users sync
	 */
	private function finalize_existing_users_sync() {

		
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
	 * Create customer JSON file for EDGE
	 * 
	 * @param WP_User $customer WordPress user object
	 * @param mixed $connection Connection object (SFTP object or FTP resource)
	 * @param string $outbox_path Remote outbox path
	 */
	public function create_customer_json_for_edge($customer, $connection, $outbox_path) {
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
								"WebTransferVendorID" => get_option('edge_vendor_id', 0)
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
			$result = $this->connection_handler->upload_file($connection, $remote_path, $temp_file);
			
			// Clean up temp file
			unlink($temp_file);
			
			if ($result) {

				
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

			}
			
		} catch (\Exception $e) {

		}
	}

} 