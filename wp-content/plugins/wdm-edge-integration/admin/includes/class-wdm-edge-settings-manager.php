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
		$connection_settings = array(
			'edge_connection_type',
			'edge_sftp_host',
			'edge_sftp_username',
			'edge_sftp_password',
			'edge_sftp_port',
			'edge_sftp_folder',
			'edge_sftp_base_path'
		);

		// Register settings for both groups to maintain backward compatibility
		foreach ($connection_settings as $setting) {
			register_setting('edge_connection_options', $setting);
			register_setting('edge_sftp_options', $setting);
		}

		// Register cron settings for customers and products
		$this->register_cron_settings('customer');
		$this->register_cron_settings('product');
        
        // Sync existing users settings
        register_setting('edge_sync_existing_options', 'edge_sync_existing_chunk_size');
	}

	/**
	 * Register cron settings for a specific type (customer or product)
	 * 
	 * @param string $type The type of cron settings (customer or product)
	 */
	private function register_cron_settings($type) {
		$group = "edge_{$type}_cron_options";
		register_setting($group, "edge_{$type}_enable_cron", array(
			'sanitize_callback' => array($this, 'handle_cron_toggle'),
		));
		register_setting($group, "edge_{$type}_cron_interval", array(
			'sanitize_callback' => array($this, 'handle_cron_interval_change'),
		));
		register_setting($group, "edge_{$type}_cron_custom_minutes");
		register_setting($group, "edge_{$type}_chunk_size");
	}

	/**
	 * Generic handler for cron toggle settings
	 *
	 * @param mixed $value The new option value.
	 * @return mixed The sanitized option value.
	 */
	public function handle_cron_toggle($value) {
		$option_name = current_filter();
		$type = strpos($option_name, 'product') !== false ? 'product' : 'customer';
		$event = $type === 'product' ? 'edge_scheduled_product_import' : 'edge_scheduled_import';
		
		$old_value = get_option($option_name, 0);
		$value = (bool) $value ? 1 : 0;
		
		if ($old_value != $value) {
			$timestamp = wp_next_scheduled($event);
			if ($timestamp) {
				wp_unschedule_event($timestamp, $event);
			}
			
			if ($value) {
				$interval = get_option("edge_{$type}_cron_interval", 'daily');
				wp_schedule_event(time(), $interval, $event);
			}
		}
		
		return $value;
	}

	/**
	 * Generic handler for cron interval changes
	 *
	 * @param mixed $value The new option value.
	 * @return mixed The sanitized option value.
	 */
	public function handle_cron_interval_change($value) {
		$option_name = current_filter();
		$type = strpos($option_name, 'product') !== false ? 'product' : 'customer';
		$event = $type === 'product' ? 'edge_scheduled_product_import' : 'edge_scheduled_import';
		
		$old_value = get_option("edge_{$type}_cron_interval", 'daily');
		
		if ($old_value != $value && get_option("edge_{$type}_enable_cron", 0)) {
			$timestamp = wp_next_scheduled($event);
			if ($timestamp) {
				wp_unschedule_event($timestamp, $event);
			}
			
			if ($value === "edge_{$type}_custom_minutes") {
				$this->validate_custom_minutes($type);
			}
			
			wp_schedule_event(time(), $value, $event);
		}
		
		return $value;
	}

	/**
	 * Validate and update custom minutes setting
	 *
	 * @param string $type The type of cron (customer or product)
	 */
	private function validate_custom_minutes($type) {
		$option_name = "edge_{$type}_cron_custom_minutes";
		$custom_minutes = intval(get_option($option_name, 30));
		
		if ($custom_minutes < 5) {
			$custom_minutes = 5; // Minimum 5 minutes
		} elseif ($custom_minutes > 1440) {
			$custom_minutes = 1440; // Maximum 24 hours
		}
		
		update_option($option_name, $custom_minutes);
	}

	/**
	 * Add custom cron intervals
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified cron schedules.
	 */
	public function add_custom_cron_intervals($schedules) {
		$types = array('customer', 'product');
		
		foreach ($types as $type) {
			$minutes = intval(get_option("edge_{$type}_cron_custom_minutes", 30));
			if ($minutes < 1) {
				$minutes = 30;
			}
			
			$schedules["edge_{$type}_custom_minutes"] = array(
				'interval' => $minutes * 60,
				'display'  => sprintf(__('Every %d minutes (%s)'), $minutes, ucfirst($type) . 's')
			);
		}
		
		return $schedules;
	}
} 