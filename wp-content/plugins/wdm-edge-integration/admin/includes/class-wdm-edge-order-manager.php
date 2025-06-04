<?php

/**
 * Order management functionality for EDGE integration.
 *
 * @link       https://www.wisdmlabs.com
 * @since      1.0.0
 *
 * @package    Wdm_Edge_Integration
 * @subpackage Wdm_Edge_Integration/admin/includes
 */

/**
 * Order management functionality for EDGE integration.
 *
 * Handles WooCommerce order syncing to EDGE POS system.
 *
 * @package    Wdm_Edge_Integration
 * @subpackage Wdm_Edge_Integration/admin/includes
 * @author     WisdmLabs <info@wisdmlabs.com>
 */
class Wdm_Edge_Order_Manager {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      Wdm_Edge_Connection_Handler    $connection_handler    The connection handler instance.
	 * @param      Wdm_Edge_Customer_Manager      $customer_manager      The customer manager instance.
	 */
	public function __construct( $connection_handler, $customer_manager ) {
		$this->connection_handler = $connection_handler;
		$this->customer_manager = $customer_manager;
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
			$connection = $this->connection_handler->create_connection();
			
			// Only create and upload customer JSON if user is not already synced to EDGE
			if (!$is_edge_synced) {
				error_log('Customer not synced with EDGE, creating customer JSON for customer ID: ' . $customer_id);
				$this->customer_manager->create_customer_json_for_edge($customer, $connection, $outbox_path);
			} else {
				error_log('Customer already synced with EDGE, skipping customer JSON creation for customer ID: ' . $customer_id);
			}
			
			// Always create and upload websale JSON for the order
			$this->create_websale_json_for_edge($order, $connection, $outbox_path);
			
			// Close connection
			$this->connection_handler->close_connection($connection);
			
			error_log('Order sync completed for order ID: ' . $order_id);
			
		} catch (\Exception $e) {
			error_log('Error during order sync: ' . $e->getMessage());
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
			$result = $this->connection_handler->upload_file($connection, $remote_path, $temp_file);
			
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

} 