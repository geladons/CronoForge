# ChronoForge Installation Guide

## Quick Installation

### Method 1: Direct Upload
1. Copy the entire `chrono-forge` folder to `/wp-content/plugins/`
2. Go to WordPress Admin → Plugins
3. Find "ChronoForge" and click "Activate"
4. Configure settings in ChronoForge → Settings

### Method 2: ZIP Upload
1. Create a ZIP archive of the `chrono-forge` folder
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the ZIP file and click "Install Now"
4. Click "Activate Plugin"

## System Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher  
- **MySQL**: 5.6 or higher
- **Memory**: 128MB minimum (256MB recommended)

## Post-Installation Setup

### 1. Initial Configuration
After activation, go to **ChronoForge → Settings**:

- **General Settings**: Set language, timezone, currency
- **Working Hours**: Configure business hours and working days
- **Booking Settings**: Set appointment duration, booking windows
- **Notifications**: Configure email/SMS preferences

### 2. Create Your First Service
1. Go to **ChronoForge → Services**
2. Click **"Add New Service"**
3. Fill in:
   - Service name
   - Description
   - Duration (minutes)
   - Price
   - Category
   - Color for calendar display

### 3. Add Staff Members
1. Go to **ChronoForge → Employees**
2. Click **"Add New Employee"**
3. Enter employee details:
   - Name and contact information
   - Link to WordPress user (optional)
   - Description/bio
   - Assign services they can provide

### 4. Test Booking System
1. Go to **ChronoForge → Appointments**
2. Click **"Add New Appointment"**
3. Select:
   - Service
   - Employee
   - Customer (create new if needed)
   - Date and time
4. Save appointment

## Database Tables

The plugin automatically creates these tables:
- `wp_chrono_forge_services`
- `wp_chrono_forge_employees`
- `wp_chrono_forge_customers`
- `wp_chrono_forge_appointments`
- `wp_chrono_forge_payments`

## Troubleshooting

### Plugin Won't Activate
**Check PHP Version:**
```bash
php -v
```
Must be 7.4 or higher.

**Check WordPress Version:**
Go to Dashboard → Updates to verify WordPress 5.0+

**Check Error Logs:**
Look in `/wp-content/debug.log` for specific errors.

### Missing Admin Menu
1. Check user permissions (must have `manage_options` capability)
2. Clear any caching plugins
3. Deactivate and reactivate the plugin

### Database Errors
1. Verify MySQL version: `SELECT VERSION();`
2. Check database user permissions
3. Ensure WordPress can create tables

### Memory Issues
Add to `wp-config.php`:
```php
ini_set('memory_limit', '256M');
```

## File Permissions

Ensure proper permissions:
```bash
# Plugin directory
chmod 755 chrono-forge/

# PHP files
chmod 644 chrono-forge/*.php
chmod 644 chrono-forge/includes/**/*.php

# Asset files
chmod 644 chrono-forge/assets/**/*

# Make writable for uploads (if needed)
chmod 755 chrono-forge/uploads/
```

## Security Considerations

### 1. User Capabilities
The plugin requires `manage_options` capability for admin access. Create dedicated roles if needed:

```php
// Add custom role for booking manager
add_role('booking_manager', 'Booking Manager', [
    'read' => true,
    'manage_options' => true, // For ChronoForge access
]);
```

### 2. File Access
All PHP files include security checks:
```php
if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}
```

### 3. Database Security
- All queries use prepared statements
- Input is sanitized and validated
- Nonce verification for forms

## Performance Optimization

### 1. Caching
The plugin is compatible with:
- WP Rocket
- W3 Total Cache
- WP Super Cache
- Object caching (Redis/Memcached)

### 2. Database Optimization
- Indexes on frequently queried columns
- Efficient query structure
- Pagination for large datasets

### 3. Asset Loading
- CSS/JS only loaded on plugin pages
- Minified assets in production
- Conditional loading based on page

## Backup Recommendations

Before installation:
1. **Full WordPress Backup**
2. **Database Backup**
3. **Plugin Directory Backup**

Use plugins like:
- UpdraftPlus
- BackWPup
- Duplicator

## Uninstallation

### Safe Removal
1. **Export Data** (if needed)
2. **Deactivate Plugin**
3. **Delete Plugin Files**

### Data Retention
By default, plugin data is preserved. To remove all data:

1. Go to ChronoForge → Settings → Advanced
2. Check "Delete all data on uninstall"
3. Save settings
4. Then uninstall plugin

### Manual Cleanup
If needed, manually remove tables:
```sql
DROP TABLE wp_chrono_forge_services;
DROP TABLE wp_chrono_forge_employees;
DROP TABLE wp_chrono_forge_customers;
DROP TABLE wp_chrono_forge_appointments;
DROP TABLE wp_chrono_forge_payments;
```

## Support

### Self-Help
1. Check this documentation
2. Review error logs
3. Test with default theme
4. Disable other plugins temporarily

### Debug Mode
Enable in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### System Info
The plugin provides system information in:
**ChronoForge → Settings → System Info**

## Migration from Other Plugins

### From Amelia
1. Export Amelia data
2. Install ChronoForge
3. Use import tools (if available)
4. Manually recreate services/employees

### From Bookly
1. Export customer/service data
2. Install ChronoForge
3. Import using CSV tools
4. Reconfigure settings

## Development Setup

For developers:

### 1. Install Dependencies
```bash
cd chrono-forge/
composer install
```

### 2. Development Mode
Add to `wp-config.php`:
```php
define('CHRONO_FORGE_DEBUG', true);
```

### 3. Run Tests
```bash
php tests/final-plugin-test.php
```

---

**ChronoForge is now ready to manage your appointments!** 🎉
