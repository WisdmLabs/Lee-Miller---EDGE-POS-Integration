# Order Manager - Module Documentation

## Module Overview
The Order Manager module handles synchronization of order data between the EDGE system and WooCommerce, including order import, export, status updates, and order mapping operations.

## Purpose
Manages all order-related synchronization operations including importing orders from EDGE to WooCommerce, exporting orders from WooCommerce to EDGE, order status synchronization, and order data mapping.

## Priority
High - Essential for order processing and fulfillment

## Status
Complete - Fully implemented with comprehensive order sync functionality

## Features
- [x] Order import from EDGE to WooCommerce
- [x] Order export from WooCommerce to EDGE
- [x] Order status synchronization
- [x] Order data mapping and transformation
- [x] Order line item handling
- [x] Customer order association
- [x] Payment status tracking
- [x] Shipping information management
- [x] Chunked processing for large datasets
- [x] Error handling and logging
- [x] Scheduled synchronization via cron
- [x] Manual sync operations

## Technical Requirements
- **WordPress Hooks Used:**
  - `edge_scheduled_import` - Scheduled order import
  - `wp_ajax_*` - AJAX handlers for manual operations
  - `woocommerce_order_status_changed` - Handle order status changes
  - `woocommerce_new_order` - Handle new order creation

- **Database Changes:**
  - WooCommerce order data (existing tables)
  - Custom meta fields for EDGE integration
  - Sync status tracking

- **File Dependencies:**
  - `class-wdm-edge-order-manager.php`
  - WooCommerce order functions
  - WordPress user management functions

## Implementation Details
### Core Functions
```php
// Main order manager class
class Wdm_Edge_Order_Manager {
    // Import orders from EDGE
    public function import_orders_from_edge($chunk_size = 25) {
        // Implementation details
    }
    
    // Export orders to EDGE
    public function export_orders_to_edge($chunk_size = 25) {
        // Implementation details
    }
    
    // Process order data
    public function process_order_data($order_data) {
        // Implementation details
    }
    
    // Update order status
    public function update_order_status($order_id, $status) {
        // Implementation details
    }
    
    // Handle order line items
    public function process_order_line_items($order_id, $line_items) {
        // Implementation details
    }
}
```

### Data Mapping
- **EDGE → WooCommerce:**
  - Order ID mapping
  - Customer association
  - Order line items and products
  - Pricing and totals
  - Shipping information
  - Payment status

- **WooCommerce → EDGE:**
  - Order ID and meta data
  - Order status and history
  - Customer information
  - Payment and shipping details

### Hooks and Filters
- **Actions:**
  - `edge_scheduled_import` → `import_orders_from_edge()`
  - `wp_ajax_manual_order_import` → Manual import handler
  - `wp_ajax_manual_order_export` → Manual export handler

- **Filters:**
  - `edge_order_data_mapping` → Customize data mapping
  - `edge_order_validation` → Custom validation rules

## User Interface
- **Admin Pages:** Order sync management interface
- **Frontend Elements:** None (admin-only functionality)
- **Settings:** Order sync configuration and scheduling

## Testing Requirements
- [x] Unit tests for order operations
- [x] Integration tests with WooCommerce
- [x] Data mapping tests
- [x] Order status synchronization tests
- [x] Line item handling tests
- [x] Performance tests for large datasets

## Security Considerations
- [x] Input sanitization for order data
- [x] Output escaping for display
- [x] Capability checks for operations
- [x] Secure handling of payment information

## Performance Considerations
- [x] Chunked processing for large imports
- [x] Efficient database queries
- [x] Memory usage optimization
- [x] Background processing for large operations

## Dependencies
- **WordPress Core:** 6.0+
- **WooCommerce:** Required for order functionality
- **PHP Extensions:** JSON, cURL

## Notes
- Handles complex order structures with line items
- Maintains order status synchronization
- Associates orders with existing customers
- Implements payment status tracking
- Comprehensive error handling for order processing 