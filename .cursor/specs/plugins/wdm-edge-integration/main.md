# WDM EDGE Integration - Main Documentation

## Plugin Overview
The WDM EDGE Integration plugin creates a comprehensive synchronization system between EDGE (Enterprise Data Gateway Environment) and WordPress WooCommerce. It handles automated import/export of customers, products, and orders through secure SFTP connections with configurable scheduling.

## Module Structure

### Core Modules
- **[Connection Handler]** → [modules/connection-handler.md](modules/connection-handler.md)
  - Purpose: Manages SFTP connections to EDGE system
  - Priority: High
  - Status: Complete

- **[Settings Manager]** → [modules/settings-manager.md](modules/settings-manager.md)
  - Purpose: Handles all plugin configuration and settings
  - Priority: High
  - Status: Complete

- **[Customer Manager]** → [modules/customer-manager.md](modules/customer-manager.md)
  - Purpose: Synchronizes customer data between EDGE and WooCommerce
  - Priority: High
  - Status: Complete

- **[Product Manager]** → [modules/product-manager.md](modules/product-manager.md)
  - Purpose: Synchronizes product data between EDGE and WooCommerce
  - Priority: High
  - Status: Complete

- **[Order Manager]** → [modules/order-manager.md](modules/order-manager.md)
  - Purpose: Synchronizes order data between EDGE and WooCommerce
  - Priority: High
  - Status: Complete

- **[Admin UI Manager]** → [modules/admin-ui-manager.md](modules/admin-ui-manager.md)
  - Purpose: Manages the WordPress admin interface and user interactions
  - Priority: High
  - Status: Complete

## Plugin Architecture
The plugin follows a modular architecture with separate manager classes for different functionalities:

```
wdm-edge-integration/
├── admin/
│   ├── class-wdm-edge-integration-admin.php (Main admin class)
│   ├── includes/ (Manager classes)
│   │   ├── class-wdm-edge-connection-handler.php
│   │   ├── class-wdm-edge-settings-manager.php
│   │   ├── class-wdm-edge-customer-manager.php
│   │   ├── class-wdm-edge-product-manager.php
│   │   ├── class-wdm-edge-order-manager.php
│   │   └── class-wdm-edge-admin-ui-manager.php
│   ├── partials/ (Admin templates)
│   ├── css/ (Admin styles)
│   └── js/ (Admin scripts)
├── includes/ (Core plugin classes)
├── public/ (Frontend functionality)
└── languages/ (Internationalization)
```

## Key Functions
- **SFTP Connection Management:** Secure file transfer with EDGE system
- **Data Synchronization:** Bidirectional sync of customers, products, and orders
- **Scheduled Operations:** WordPress cron-based automation
- **Error Handling:** Comprehensive logging and error management
- **Admin Interface:** User-friendly configuration and monitoring

## WordPress Hooks Used
- `init` - Plugin initialization
- `admin_menu` - Admin menu creation
- `wp_enqueue_scripts` - Script and style enqueuing
- `admin_enqueue_scripts` - Admin script enqueuing
- `cron` - Scheduled task execution
- `wp_ajax_*` - AJAX handlers for admin interface

## Database Changes
- Custom options for plugin settings
- WordPress cron events for scheduled tasks
- WooCommerce customer, product, and order data (existing tables)

## Dependencies
- **WordPress Core:** 6.0+
- **WooCommerce:** Required for e-commerce functionality
- **PHP Extensions:** SFTP support, JSON, cURL
- **External:** EDGE system SFTP access

## Security Features
- Input sanitization and validation
- Output escaping
- Capability checks for admin functions
- Nonce verification for forms
- Secure SFTP connections

## Performance Considerations
- Chunked processing for large datasets
- Configurable batch sizes
- Memory usage optimization
- Efficient database queries
- Caching where appropriate 