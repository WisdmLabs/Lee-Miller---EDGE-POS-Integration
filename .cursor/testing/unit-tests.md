# Unit Testing Instructions - WDM EDGE Integration Plugin

## Overview
Unit tests for the WDM EDGE Integration plugin should test individual components and functions in isolation. These tests will be executed using Playwright or browser MCP for automated testing.

## Test Environment Setup
- **Testing Framework:** Playwright with PHPUnit integration
- **Browser:** Headless Chrome/Firefox for UI component testing
- **Database:** Test database with sample data
- **WordPress:** Test WordPress installation with WooCommerce
- **Plugin:** WDM EDGE Integration plugin activated

## Module-Specific Test Cases

### 1. Connection Handler Module Tests

#### SFTP Connection Tests
- **Test SFTP Connection Success:** Verify successful connection to EDGE system with valid credentials
- **Test SFTP Connection Failure:** Verify proper error handling with invalid credentials
- **Test Connection Timeout:** Verify timeout handling for slow connections
- **Test Connection Retry Logic:** Verify automatic retry mechanism for failed connections
- **Test File Transfer Success:** Verify successful file upload/download operations
- **Test File Transfer Failure:** Verify error handling for file transfer failures

#### Connection Configuration Tests
- **Test Settings Validation:** Verify SFTP settings validation (host, port, username, password)
- **Test Connection Testing:** Verify admin interface connection test functionality
- **Test Connection Logging:** Verify connection attempts are properly logged

### 2. Settings Manager Module Tests

#### Settings Registration Tests
- **Test Settings Page Creation:** Verify settings page appears in WordPress admin
- **Test Settings Fields:** Verify all settings fields are properly registered and displayed
- **Test Settings Validation:** Verify input validation for all settings fields
- **Test Settings Saving:** Verify settings are properly saved to WordPress options
- **Test Settings Retrieval:** Verify settings are properly retrieved from database

#### Cron Configuration Tests
- **Test Cron Registration:** Verify WordPress cron jobs are properly registered
- **Test Cron Scheduling:** Verify cron intervals are correctly set
- **Test Cron Execution:** Verify scheduled tasks execute at proper intervals
- **Test Cron Deactivation:** Verify cron jobs are properly removed on plugin deactivation

### 3. Customer Manager Module Tests

#### Customer Data Processing Tests
- **Test Customer Import:** Verify customer data import from EDGE system
- **Test Customer Export:** Verify customer data export to EDGE system
- **Test Customer Validation:** Verify customer data validation rules
- **Test Customer Mapping:** Verify field mapping between EDGE and WooCommerce
- **Test Customer Update:** Verify existing customer data updates
- **Test Customer Creation:** Verify new customer creation in WooCommerce

#### Customer Chunking Tests
- **Test Large Dataset Processing:** Verify chunked processing for large customer datasets
- **Test Memory Usage:** Verify memory usage remains within limits during processing
- **Test Progress Tracking:** Verify progress tracking for long-running operations

### 4. Product Manager Module Tests

#### Product Data Processing Tests
- **Test Product Import:** Verify product data import from EDGE system
- **Test Product Export:** Verify product data export to EDGE system
- **Test Product Validation:** Verify product data validation rules
- **Test Product Mapping:** Verify field mapping between EDGE and WooCommerce
- **Test Product Update:** Verify existing product data updates
- **Test Product Creation:** Verify new product creation in WooCommerce
- **Test Inventory Sync:** Verify inventory levels synchronization
- **Test Pricing Sync:** Verify pricing information synchronization

#### Product Variants Tests
- **Test Variant Processing:** Verify product variant handling
- **Test Variant Attributes:** Verify variant attribute mapping
- **Test Variant Pricing:** Verify variant-specific pricing

### 5. Order Manager Module Tests

#### Order Data Processing Tests
- **Test Order Import:** Verify order data import from EDGE system
- **Test Order Export:** Verify order data export to EDGE system
- **Test Order Validation:** Verify order data validation rules
- **Test Order Mapping:** Verify field mapping between EDGE and WooCommerce
- **Test Order Status Sync:** Verify order status synchronization
- **Test Order Line Items:** Verify order line item processing
- **Test Payment Information:** Verify payment data handling

#### Order Status Tests
- **Test Status Mapping:** Verify order status mapping between systems
- **Test Status Updates:** Verify order status update propagation
- **Test Status History:** Verify order status change tracking

### 6. Admin UI Manager Module Tests

#### Admin Interface Tests
- **Test Menu Creation:** Verify admin menu appears in WordPress admin
- **Test Page Rendering:** Verify all admin pages render correctly
- **Test Form Submission:** Verify form submissions work properly
- **Test AJAX Requests:** Verify AJAX functionality for dynamic content
- **Test Notifications:** Verify success/error notifications display correctly

#### User Interface Tests
- **Test Responsive Design:** Verify admin interface works on different screen sizes
- **Test Accessibility:** Verify admin interface meets accessibility standards
- **Test User Permissions:** Verify proper access control for admin functions

## Test Data Requirements

### Sample Data Sets
- **Small Dataset:** 10-50 records for quick testing
- **Medium Dataset:** 100-500 records for performance testing
- **Large Dataset:** 1000+ records for stress testing

### Test Credentials
- **Valid SFTP Credentials:** For successful connection tests
- **Invalid SFTP Credentials:** For error handling tests
- **Test EDGE System:** Mock or test EDGE system for data exchange

## Test Execution Instructions

### Running Unit Tests
1. **Setup Test Environment:** Ensure WordPress test environment is configured
2. **Activate Plugin:** Activate WDM EDGE Integration plugin
3. **Configure Test Settings:** Set up test SFTP credentials and EDGE system
4. **Run Module Tests:** Execute tests for each module individually
5. **Verify Results:** Check test results and fix any failures
6. **Generate Reports:** Create test execution reports

### Test Validation Criteria
- **Functionality:** All functions work as expected
- **Error Handling:** Proper error messages and handling
- **Performance:** Operations complete within acceptable time limits
- **Memory Usage:** Memory usage remains within defined limits
- **Data Integrity:** Data remains consistent between operations

## Expected Test Outcomes

### Success Criteria
- All unit tests pass without errors
- No memory leaks detected
- All error conditions properly handled
- Performance meets defined benchmarks
- Data integrity maintained throughout operations

### Failure Handling
- Clear error messages for debugging
- Graceful degradation for partial failures
- Proper logging of all errors
- Recovery mechanisms for failed operations

## Notes for Test Execution
- Tests should be run in isolation to avoid interference
- Database should be reset between test runs
- Mock external services when possible
- Use realistic test data that matches production scenarios
- Monitor system resources during test execution 