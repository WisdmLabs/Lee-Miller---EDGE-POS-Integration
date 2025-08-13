<?php

/**
 * Settings management functionality for the EDGE Integration plugin.
 *
 * @link       https://www.wisdmlabs.com
 * @since      1.0.0
 *
 * @package    Wdm_Edge_Integration
 * @subpackage Wdm_Edge_Integration/admin/includes
 */

/**
 * Settings management functionality for the EDGE Integration plugin.
 *
 * Handles registration and validation of all plugin settings.
 *
 * @package    Wdm_Edge_Integration
 * @subpackage Wdm_Edge_Integration/admin/includes
 * @author     WisdmLabs <info@wisdmlabs.com>
 */
class Wdm_Edge_Settings_Manager {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// Constructor can be empty for now as this class doesn't need dependencies
	}

	/**
	 * Register settings for SFTP setup and other plugin options.
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
        
        // Vendor settings group
        register_setting( 'edge_vendor_options', 'edge_vendor_id', array(
			'sanitize_callback' => array($this, 'sanitize_vendor_id'),
		));
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

				} else {

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

			} else {

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

				} else {

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

			} else {

			}
		}
		
		return $value;
	}

	/**
	 * Handle changes to the legacy cron setting.
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

			}
		} 
		// If cron was enabled or interval changed, schedule the event
		else if ($new_value) {
			// Clear any existing scheduled events
			$timestamp = wp_next_scheduled('edge_scheduled_import');
			if ($timestamp) {
				wp_unschedule_event($timestamp, 'edge_scheduled_import');

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

			} else {

			}
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

			}
		} 
		// If cron was enabled or interval changed, schedule the event
		else if ($new_value) {
			// Clear any existing scheduled events
			$timestamp = wp_next_scheduled('edge_scheduled_import');
			if ($timestamp) {
				wp_unschedule_event($timestamp, 'edge_scheduled_import');

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

			} else {

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

			}
		} 
		// If cron was enabled or interval changed, schedule the event
		else if ($new_value) {
			// Clear any existing scheduled events
			$timestamp = wp_next_scheduled('edge_scheduled_product_import');
			if ($timestamp) {
				wp_unschedule_event($timestamp, 'edge_scheduled_product_import');

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

			} else {

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
	 * Sanitize the vendor ID setting.
	 *
	 * @param mixed $value The vendor ID value to sanitize.
	 * @return int The sanitized vendor ID value.
	 */
	public function sanitize_vendor_id($value) {
		// Convert to integer and ensure it's not negative
		$vendor_id = intval($value);
		if ($vendor_id < 0) {
			$vendor_id = 0;
		}
		
		return $vendor_id;
	}

} 