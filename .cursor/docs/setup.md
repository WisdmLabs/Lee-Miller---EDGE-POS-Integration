# Project Setup Guide

## Prerequisites
- WordPress 6.0+ (already installed)
- PHP 8.0+
- WooCommerce plugin installed and activated
- SFTP access to EDGE system
- Local development environment (XAMPP/MAMP/Local by Flywheel/etc.)
- Git repositories already set up and configured

## Initial Setup Steps

### 1. Project Setup
```bash
# Navigate to your existing project directory
cd "C:\Users\User\Downloads\git repos\lee miller"
```

### 2. WordPress Setup
- Ensure WordPress core files are already present
- Verify database and wp-config.php are configured
- Confirm site URLs are properly set
- Activate WooCommerce plugin

### 3. WDM EDGE Integration Plugin Setup
- Plugin is already present in `wp-content/plugins/wdm-edge-integration/`
- Activate the plugin through WordPress admin
- Configure SFTP connection settings
- Set up cron job intervals for synchronization

### 4. Development Environment
- Set up local server environment
- Configure debugging (WP_DEBUG = true)
- Install development tools as needed
- Set up SFTP testing environment

## File Permissions
Set appropriate permissions:
```bash
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 600 wp-config.php
```

## Plugin Configuration
1. **Activate Plugin:** Go to WordPress Admin → Plugins → Activate "WDM EDGE Integration"
2. **Configure SFTP Settings:** 
   - Navigate to plugin settings page
   - Enter EDGE system SFTP credentials
   - Test connection
3. **Set Cron Intervals:**
   - Configure customer sync frequency
   - Configure product sync frequency
   - Set chunk sizes for large imports
4. **Configure Vendor ID:** Set the vendor identifier for EDGE system

## Testing Setup
- Verify WordPress loads without errors
- Confirm WooCommerce is working
- Test SFTP connection to EDGE system
- Verify cron jobs are scheduled correctly
- Test data synchronization manually

## Development Workflow
1. **Local Development:** Make changes to plugin files
2. **Testing:** Test functionality locally
3. **Version Control:** Commit changes to git repository
4. **Deployment:** Deploy to staging/production environment

## Troubleshooting Common Issues
- **White Screen:** Check error logs, enable WP_DEBUG
- **SFTP Connection Failed:** Verify credentials and network access
- **Cron Jobs Not Running:** Check WordPress cron system and server configuration
- **Data Sync Issues:** Check file permissions and data format
- **Memory Issues:** Increase PHP memory limit for large imports
- **Git Issues:** Verify repository access and permissions

## Environment Variables
Consider setting these for development:
```bash
# WordPress Debug
WP_DEBUG=true
WP_DEBUG_LOG=true
WP_DEBUG_DISPLAY=false

# PHP Settings for large imports
memory_limit=512M
max_execution_time=300
``` 