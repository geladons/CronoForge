# ChronoForge - WordPress Booking Plugin

Professional appointment and booking management plugin for WordPress.

## Features

### 🗓️ Appointment Management
- Create, edit, and manage appointments
- Multiple appointment statuses (pending, confirmed, cancelled, completed)
- Conflict detection and prevention
- Recurring appointments support

### 👥 Customer Management
- Customer database with contact information
- Booking history tracking
- Customer notes and preferences
- WordPress user integration

### 🛠️ Service Management
- Unlimited services with custom pricing
- Service categories and descriptions
- Duration and capacity settings
- Color-coded service identification

### 👨‍💼 Employee Management
- Staff member profiles and schedules
- WordPress user integration
- Employee-specific services
- Working hours management

### 📊 Dashboard & Analytics
- Real-time statistics and KPIs
- Revenue tracking
- Appointment overview
- Quick action shortcuts

### ⚙️ Settings & Configuration
- Comprehensive settings panel
- Multi-language support (English/Russian)
- Working hours configuration
- Notification preferences

## Installation

1. Upload the `chrono-forge` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings in **ChronoForge** → **Settings**
4. Start creating services and accepting bookings!

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher

## Quick Start

### 1. Create Your First Service
1. Go to **ChronoForge** → **Services**
2. Click **Add New Service**
3. Fill in service details (name, duration, price)
4. Save the service

### 2. Add an Employee
1. Go to **ChronoForge** → **Employees**
2. Click **Add New Employee**
3. Enter employee information
4. Assign services to the employee

### 3. Configure Working Hours
1. Go to **ChronoForge** → **Settings** → **Working Hours**
2. Set your business hours
3. Select working days
4. Save settings

### 4. Start Booking
1. Go to **ChronoForge** → **Appointments**
2. Click **Add New Appointment**
3. Select service, employee, customer, and time
4. Save the appointment

## Plugin Structure

```
chrono-forge/
├── assets/                 # CSS and JavaScript files
├── includes/              # Core plugin files
│   ├── Admin/            # Admin interface controllers
│   ├── Application/      # Application services
│   ├── Infrastructure/   # Database and core infrastructure
│   ├── container.php     # Dependency injection container
│   └── functions.php     # Core functions
├── templates/            # Template files
├── languages/           # Translation files
├── tests/              # Test files
└── chrono-forge.php    # Main plugin file
```

## Database Tables

The plugin creates the following tables:
- `wp_chrono_forge_services` - Service definitions
- `wp_chrono_forge_employees` - Staff members
- `wp_chrono_forge_customers` - Customer database
- `wp_chrono_forge_appointments` - Booking records
- `wp_chrono_forge_payments` - Payment tracking

## Hooks & Filters

### Actions
- `chrono_forge_init` - Plugin initialization
- `chrono_forge_service_created` - After service creation
- `chrono_forge_appointment_created` - After appointment creation
- `chrono_forge_settings_saved` - After settings update

### Filters
- `chrono_forge_service_data` - Modify service data before saving
- `chrono_forge_appointment_data` - Modify appointment data
- `chrono_forge_admin_menu_capability` - Change required capability

## API Endpoints

The plugin provides AJAX endpoints for:
- `/wp-admin/admin-ajax.php?action=chrono_forge_api&route=services`
- `/wp-admin/admin-ajax.php?action=chrono_forge_api&route=appointments`
- `/wp-admin/admin-ajax.php?action=chrono_forge_api&route=customers`
- `/wp-admin/admin-ajax.php?action=chrono_forge_api&route=employees`

## Customization

### Adding Custom Fields
Use WordPress hooks to extend functionality:

```php
// Add custom service field
add_action('chrono_forge_service_form', function($service) {
    echo '<input type="text" name="custom_field" value="' . esc_attr($service->custom_field ?? '') . '">';
});

// Save custom field
add_action('chrono_forge_service_saved', function($service_id, $data) {
    if (isset($data['custom_field'])) {
        update_post_meta($service_id, 'custom_field', sanitize_text_field($data['custom_field']));
    }
}, 10, 2);
```

### Custom Styling
Override default styles by adding CSS to your theme:

```css
.chrono-forge-dashboard {
    /* Your custom styles */
}
```

## Troubleshooting

### Common Issues

**Plugin not activating:**
- Check PHP version (7.4+ required)
- Verify WordPress version (5.0+ required)
- Check for plugin conflicts

**Database errors:**
- Ensure proper MySQL permissions
- Check database connection
- Verify table creation

**Missing menu:**
- Check user capabilities
- Clear cache if using caching plugins
- Deactivate and reactivate plugin

### Debug Mode
Enable WordPress debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `/wp-content/debug.log` for error messages.

## Support

For support and documentation:
- Check the plugin settings for built-in help
- Review error logs for specific issues
- Ensure all requirements are met

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- Complete booking system
- Admin dashboard
- Multi-language support
- Modern architecture with dependency injection
- Comprehensive API system
- Professional admin interface

---

**ChronoForge** - Professional appointment booking made simple.
