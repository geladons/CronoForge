# ChronoForge - WordPress Booking Plugin

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.0.0-orange.svg)](CHANGELOG.md)

A comprehensive WordPress booking and appointment management plugin designed to provide Amelia-like functionality with modern UI/UX and powerful features for service-based businesses.

## 🚀 Features

### Core Functionality
- **Multi-step Booking Wizard** - Intuitive step-by-step booking process
- **Service Management** - Organize services by categories with custom colors and pricing
- **Employee Management** - Manage staff with individual schedules and service assignments
- **Advanced Scheduling** - Flexible work schedules with breaks and time-off management
- **Customer Management** - Complete customer database with booking history
- **Appointment Management** - Full CRUD operations with status tracking

### Enhanced Booking Experience
- **"Any Available" Option** - Let customers book with any available staff member
- **Smart Search** - Advanced search with date ranges and time preferences
- **URL Pre-filling** - Direct links with pre-selected services/staff
- **Mobile Responsive** - Optimized for all devices
- **Real-time Availability** - Dynamic slot checking and booking

### Admin Dashboard
- **Comprehensive Dashboard** - Key metrics and recent activity overview
- **Interactive Calendar** - Drag-and-drop appointment management
- **Bulk Operations** - Efficient management of multiple records
- **Advanced Filters** - Quick filtering and search across all data
- **Settings Management** - Extensive customization options

### Multiple Display Options
- **Booking Form** - `[chrono-forge-booking]`
- **Services Catalog** - `[chrono-forge-catalog]`
- **Staff Directory** - `[chrono-forge-employees]`
- **Services List** - `[chrono-forge-services]`
- **Advanced Search** - `[chrono-forge-search]`
- **Customer Panel** - `[chrono-forge-customer-panel]`

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **Memory**: 128MB minimum (256MB recommended)

## 🔧 Installation

### Automatic Installation
1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate"

### Manual Installation
1. Download and extract the plugin files
2. Upload the `chrono-forge` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin panel

### Post-Installation Setup
1. Navigate to **ChronoForge** in your WordPress admin menu
2. Configure your basic settings (language, currency, time format, etc.)
3. Add your first service category and services
4. Add employees and set their work schedules
5. Place booking shortcodes on your pages

### Language Support
The plugin supports multiple languages:
- **English** (en_US) - Default
- **Russian** (ru_RU) - Full translation included
- **Auto-detection** - Uses WordPress site language

To compile language files (if needed):
```bash
# Navigate to languages directory
cd chrono-forge/languages/

# Compile .po files to .mo files
msgfmt chrono-forge-en_US.po -o chrono-forge-en_US.mo
msgfmt chrono-forge-ru_RU.po -o chrono-forge-ru_RU.mo
```

## 📖 Usage Examples

### Basic Booking Form
```shortcode
[chrono-forge-booking]
```

### Service-Specific Booking
```shortcode
[chrono-forge-booking service="5" show_categories="false"]
```

### Services Catalog with Categories
```shortcode
[chrono-forge-catalog show_categories="true" show_filters="true"]
```

### Staff Directory
```shortcode
[chrono-forge-employees columns="3" show_services="true" show_book_button="true"]
```

### Services List
```shortcode
[chrono-forge-services columns="2" show_price="true" show_duration="true"]
```

### Advanced Search Form
```shortcode
[chrono-forge-search show_date_range="true" show_any_employee="true"]
```

### Customer Panel (for logged-in users)
```shortcode
[chrono-forge-customer-panel show_upcoming="true" show_past="true"]
```

## ⚙️ Configuration

### Basic Settings
- **Currency & Formatting**: Set your currency symbol and date/time formats
- **Booking Rules**: Configure minimum/maximum booking times
- **Notifications**: Enable/disable email notifications
- **Styling**: Customize colors and appearance

### Service Management
1. **Categories**: Organize services with colors and descriptions
2. **Services**: Set duration, pricing, and buffer times
3. **Employee Assignment**: Link services to specific staff members

### Employee Scheduling
- **Work Hours**: Set daily schedules with start/end times
- **Breaks**: Configure lunch breaks and rest periods
- **Time Off**: Mark vacation days and holidays
- **Quick Presets**: Use predefined schedule templates

### Advanced Features
- **Payment Integration**: Ready for Stripe, PayPal, and WooCommerce
- **Calendar Sync**: Prepared for Google Calendar integration
- **Email Templates**: Customizable notification templates
- **Multi-language**: Translation-ready with .pot file

## 🎨 Customization

### Shortcode Attributes

#### Booking Form
- `service="ID"` - Pre-select specific service
- `employee="ID"` - Pre-select specific employee
- `category="ID"` - Filter by category
- `show_categories="true/false"` - Show/hide category selection

#### Services Display
- `columns="1-4"` - Number of columns in grid
- `show_price="true/false"` - Display pricing
- `show_duration="true/false"` - Show service duration
- `show_description="true/false"` - Include descriptions

#### Employee Directory
- `columns="1-4"` - Grid layout columns
- `show_services="true/false"` - List employee services
- `show_book_button="true/false"` - Include booking buttons

### CSS Customization
All components use CSS classes prefixed with `cf-` for easy customization:
```css
.chrono-forge-booking-form { /* Main booking form */ }
.cf-service-card { /* Individual service cards */ }
.cf-employee-item { /* Employee directory items */ }
```

## 🔄 Database Structure

The plugin creates the following tables:
- `wp_chrono_forge_services` - Services and categories
- `wp_chrono_forge_employees` - Staff information
- `wp_chrono_forge_schedules` - Work schedules
- `wp_chrono_forge_appointments` - Booking records
- `wp_chrono_forge_customers` - Customer database
- `wp_chrono_forge_payments` - Payment tracking

## 🐛 Troubleshooting

### Common Issues

**Booking form not displaying**
- Check if shortcode is correctly placed
- Verify plugin is activated
- Check for JavaScript errors in browser console

**Appointments not saving**
- Ensure proper file permissions
- Check database connection
- Verify nonce security tokens

**Schedule conflicts**
- Review employee work hours
- Check for overlapping appointments
- Verify service duration settings

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## 📝 License

This project is licensed under the GPL v2 License - see the [LICENSE](LICENSE) file for details.

## 🔗 Links

- [Documentation](https://github.com/your-username/chrono-forge/wiki)
- [Issue Tracker](https://github.com/your-username/chrono-forge/issues)
- [Changelog](CHANGELOG.md)

## 💬 Support

- **GitHub Issues**: [Report bugs or request features](https://github.com/your-username/chrono-forge/issues)
- **Documentation**: Check our [Wiki](https://github.com/your-username/chrono-forge/wiki)
- **Community**: Join discussions in [GitHub Discussions](https://github.com/your-username/chrono-forge/discussions)

---

**Made with ❤️ for the WordPress community**
