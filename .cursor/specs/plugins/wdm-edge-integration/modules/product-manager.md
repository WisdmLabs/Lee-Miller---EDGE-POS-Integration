# Product Manager - Module Documentation

## Module Overview
The Product Manager module handles synchronization of product data between the EDGE system and WooCommerce, including import, export, inventory management, and product mapping operations.

## Purpose
Manages all product-related synchronization operations including importing products from EDGE to WooCommerce, exporting products from WooCommerce to EDGE, inventory updates, pricing synchronization, and product data mapping.

## Priority
High - Essential for product catalog and inventory management

## Status
Complete - Fully implemented with comprehensive product sync functionality

## Features
- [x] Product import from EDGE to WooCommerce
- [x] Product export from WooCommerce to EDGE
- [x] Inventory synchronization
- [x] Price synchronization
- [x] Product data mapping and transformation
- [x] Product category management
- [x] Product image handling
- [x] Variant product support
- [x] Chunked processing for large datasets
- [x] Error handling and logging
- [x] Scheduled synchronization via cron
- [x] Manual sync operations

## Technical Requirements
- **WordPress Hooks Used:**
  - `edge_scheduled_import` - Scheduled product import
  - `wp_ajax_*` - AJAX handlers for manual operations
  - `woocommerce_product_set_stock` - Handle stock updates
  - `woocommerce_product_set_price` - Handle price updates

- **Database Changes:**
  - WooCommerce product data (existing tables)
  - Custom meta fields for EDGE integration
  - Sync status tracking

- **File Dependencies:**
  - `class-wdm-edge-product-manager.php`
  - WooCommerce product functions
  - WordPress media handling functions

## Implementation Details
### Core Functions
```php
// Main product manager class
class Wdm_Edge_Product_Manager {
    // Import products from EDGE
    public function import_products_from_edge($chunk_size = 50) {
        // Implementation details
    }
    
    // Export products to EDGE
    public function export_products_to_edge($chunk_size = 50) {
        // Implementation details
    }
    
    // Process product data
    public function process_product_data($product_data) {
        // Implementation details
    }
    
    // Update product inventory
    public function update_product_inventory($product_id, $stock_quantity) {
        // Implementation details
    }
    
    // Handle product images
    public function handle_product_images($product_id, $image_urls) {
        // Implementation details
    }
}
```

### Data Mapping
- **EDGE → WooCommerce:**
  - Product ID mapping
  - Name, description, and attributes
  - Pricing information
  - Inventory levels
  - Category assignments
  - Image URLs

- **WooCommerce → EDGE:**
  - Product ID and meta data
  - Sales data and analytics
  - Inventory status
  - Product performance metrics

### Hooks and Filters
- **Actions:**
  - `edge_scheduled_import` → `import_products_from_edge()`
  - `wp_ajax_manual_product_import` → Manual import handler
  - `wp_ajax_manual_product_export` → Manual export handler

- **Filters:**
  - `edge_product_data_mapping` → Customize data mapping
  - `edge_product_validation` → Custom validation rules

## User Interface
- **Admin Pages:** Product sync management interface
- **Frontend Elements:** None (admin-only functionality)
- **Settings:** Product sync configuration and scheduling

## Testing Requirements
- [x] Unit tests for product operations
- [x] Integration tests with WooCommerce
- [x] Data mapping tests
- [x] Inventory synchronization tests
- [x] Image handling tests
- [x] Performance tests for large datasets

## Security Considerations
- [x] Input sanitization for product data
- [x] Output escaping for display
- [x] Capability checks for operations
- [x] Secure image handling

## Performance Considerations
- [x] Chunked processing for large imports
- [x] Efficient database queries
- [x] Memory usage optimization
- [x] Background processing for large operations
- [x] Image optimization and caching

## Dependencies
- **WordPress Core:** 6.0+
- **WooCommerce:** Required for product functionality
- **PHP Extensions:** JSON, cURL, GD/Imagick for images

## Notes
- Supports both simple and variable products
- Handles product images and media files
- Implements inventory tracking and updates
- Maintains product relationships and categories
- Comprehensive error handling for data inconsistencies 