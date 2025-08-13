# Settings Manager - Module Documentation

## Module Overview
The Settings Manager module handles all plugin configuration, settings registration, validation, and management for the WDM EDGE Integration plugin.

## Purpose
Manages all plugin settings including SFTP connection parameters, cron job configurations, synchronization intervals, chunk sizes, and vendor identification. Provides centralized settings management with proper validation and sanitization.

## Priority
High - Essential for plugin configuration and operation

## Status
Complete - Fully implemented with comprehensive settings management

## Features
- [x] SFTP connection settings registration and validation
- [x] Cron job configuration management
- [x] Customer synchronization settings
- [x] Product synchronization settings
- [x] Order synchronization settings
- [x] Vendor ID configuration
- [x] Settings sanitization and validation
- [x] Custom cron interval support
- [x] Chunk size configuration for large imports

## Technical Requirements
- **WordPress Hooks Used:**
  - `admin_init` - Register settings
  - `cron_schedules` - Add custom cron intervals
  - `update_option_*` - Handle settings changes

- **Database Changes:**
  - Multiple settings groups in WordPress options table
  - Cron event scheduling and management

- **File Dependencies:**
  - `class-wdm-edge-settings-manager.php`
  - WordPress Settings API

## Implementation Details
### Core Functions
```php
// Main settings manager class
class Wdm_Edge_Settings_Manager {
    // Register all plugin settings
    public function register_sftp_settings() {
        // Implementation details
    }
    
    // Handle cron setting changes
    public function handle_customer_cron_toggle($value) {
        // Implementation details
    }
    
    // Add custom cron intervals
    public function add_custom_cron_intervals($schedules) {
        // Implementation details
    }
    
    // Sanitize vendor ID
    public function sanitize_vendor_id($value) {
        // Implementation details
    }
}
```

### Settings Groups
- **edge_connection_options:** SFTP connection settings
- **edge_customer_cron_options:** Customer sync scheduling
- **edge_product_cron_options:** Product sync scheduling
- **edge_sync_existing_options:** Existing data sync settings
- **edge_vendor_options:** Vendor identification

### Hooks and Filters
- **Actions:**
  - `admin_init` → `register_sftp_settings()`
  - `cron_schedules` → `add_custom_cron_intervals()`

- **Filters:**
  - `update_option_edge_customer_enable_cron` → Handle cron toggle
  - `update_option_edge_product_enable_cron` → Handle cron toggle

## User Interface
- **Admin Pages:** Settings page with multiple tabs
- **Frontend Elements:** None (admin-only functionality)
- **Settings:** Comprehensive configuration interface

## Testing Requirements
- [x] Unit tests for settings registration
- [x] Validation and sanitization tests
- [x] Cron scheduling tests
- [x] Settings change handling tests

## Security Considerations
- [x] Input sanitization for all settings
- [x] Capability checks for settings access
- [x] Nonce verification for settings forms
- [x] Secure storage of sensitive credentials

## Performance Considerations
- [x] Efficient settings retrieval
- [x] Minimal database queries
- [x] Optimized cron scheduling
- [x] Memory-efficient settings handling

## Dependencies
- **WordPress Core:** 6.0+
- **WordPress Settings API:** Required
- **WordPress Cron System:** Required

## Notes
- Settings are organized into logical groups for better management
- Custom cron intervals are added for flexible scheduling
- All settings changes trigger appropriate actions (cron scheduling, etc.)
- Backward compatibility maintained for existing settings
- Comprehensive error handling for invalid settings 