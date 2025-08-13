# Customer Manager - Module Documentation

## Module Overview
The Customer Manager module handles synchronization of customer data between the EDGE system and WooCommerce, including import, export, and data mapping operations.

## Purpose
Manages all customer-related synchronization operations including importing customers from EDGE to WooCommerce, exporting customers from WooCommerce to EDGE, data validation, mapping, and conflict resolution.

## Priority
High - Essential for customer data consistency between systems

## Status
Complete - Fully implemented with comprehensive customer sync functionality

## Features
- [x] Customer import from EDGE to WooCommerce
- [x] Customer export from WooCommerce to EDGE
- [x] Data validation and sanitization
- [x] Customer data mapping and transformation
- [x] Duplicate customer detection and handling
- [x] Chunked processing for large datasets
- [x] Error handling and logging
- [x] Scheduled synchronization via cron
- [x] Manual sync operations
- [x] Customer data conflict resolution

## Technical Requirements
- **WordPress Hooks Used:**
  - `edge_scheduled_import` - Scheduled customer import
  - `wp_ajax_*` - AJAX handlers for manual operations
  - `user_register` - Handle new user registration
  - `profile_update` - Handle user profile updates

- **Database Changes:**
  - WooCommerce customer data (existing tables)
  - Custom meta fields for EDGE integration
  - Sync status tracking

- **File Dependencies:**
  - `class-wdm-edge-customer-manager.php`
  - WooCommerce customer functions
  - WordPress user management functions

## Implementation Details
### Core Functions
```php
// Main customer manager class
class Wdm_Edge_Customer_Manager {
    // Import customers from EDGE
    public function import_customers_from_edge($chunk_size = 100) {
        // Implementation details
    }
    
    // Export customers to EDGE
    public function export_customers_to_edge($chunk_size = 100) {
        // Implementation details
    }
    
    // Process customer data
    public function process_customer_data($customer_data) {
        // Implementation details
    }
    
    // Handle customer conflicts
    public function resolve_customer_conflicts($edge_customer, $wc_customer) {
        // Implementation details
    }
}
```

### Data Mapping
- **EDGE → WooCommerce:**
  - Customer ID mapping
  - Name and contact information
  - Address data
  - Custom fields

- **WooCommerce → EDGE:**
  - User ID and meta data
  - Order history
  - Customer preferences

### Hooks and Filters
- **Actions:**
  - `edge_scheduled_import` → `import_customers_from_edge()`
  - `wp_ajax_manual_customer_import` → Manual import handler
  - `wp_ajax_manual_customer_export` → Manual export handler

- **Filters:**
  - `edge_customer_data_mapping` → Customize data mapping
  - `edge_customer_validation` → Custom validation rules

## User Interface
- **Admin Pages:** Customer sync management interface
- **Frontend Elements:** None (admin-only functionality)
- **Settings:** Customer sync configuration and scheduling

## Testing Requirements
- [x] Unit tests for customer operations
- [x] Integration tests with WooCommerce
- [x] Data mapping tests
- [x] Conflict resolution tests
- [x] Performance tests for large datasets

## Security Considerations
- [x] Input sanitization for customer data
- [x] Output escaping for display
- [x] Capability checks for operations
- [x] Secure handling of sensitive customer information

## Performance Considerations
- [x] Chunked processing for large imports
- [x] Efficient database queries
- [x] Memory usage optimization
- [x] Background processing for large operations

## Dependencies
- **WordPress Core:** 6.0+
- **WooCommerce:** Required for customer functionality
- **PHP Extensions:** JSON, cURL

## Notes
- Supports both automatic and manual synchronization
- Implements conflict resolution strategies
- Handles large customer datasets efficiently
- Maintains data integrity between systems
- Comprehensive logging for debugging and monitoring 