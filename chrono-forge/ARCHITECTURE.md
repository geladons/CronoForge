# ChronoForge Modern Architecture

## Overview

ChronoForge has been refactored to implement a modern WordPress plugin architecture while maintaining full backward compatibility with the existing codebase. This document outlines the new architecture and how it integrates with the legacy code.

## Architecture Components

### 1. Core Foundation (`src/Core/`)

#### Plugin.php
- **Purpose**: Main plugin class implementing singleton pattern
- **Features**: 
  - Dependency injection container integration
  - Service provider management
  - Backward compatibility with legacy core
  - WordPress hooks integration
  - Plugin lifecycle management

#### Container.php
- **Purpose**: Lightweight dependency injection container
- **Features**:
  - Service binding and resolution
  - Singleton management
  - Constructor dependency injection
  - Reflection-based instantiation
  - Array access interface

#### ServiceProvider.php
- **Purpose**: Base class for all service providers
- **Features**:
  - Service registration and bootstrapping
  - WordPress hooks integration helpers
  - Container access methods
  - Plugin utility methods

#### Activator.php
- **Purpose**: Modern plugin activation handler
- **Features**:
  - Database table creation
  - Default settings initialization
  - User capabilities setup
  - Cron event scheduling
  - System requirements checking

#### Deactivator.php
- **Purpose**: Modern plugin deactivation handler
- **Features**:
  - Cleanup operations
  - Cron event removal
  - Temporary data clearing
  - Optional complete data removal

### 2. Global Functions (`src/functions.php`)

Provides backward compatibility and modern architecture access:
- `chrono_forge_plugin()` - Get main plugin instance
- `chrono_forge_container()` - Access DI container
- `chrono_forge_service()` - Resolve services
- Enhanced logging and settings functions
- Legacy function wrappers

### 3. Autoloading (`vendor/autoload.php`)

Custom PSR-4 autoloader that:
- Loads classes automatically based on namespace
- Supports multiple namespace prefixes
- Provides Composer-like functionality
- Includes global functions file

## Directory Structure

```
chrono-forge/
├── src/                          # Modern architecture source
│   ├── Core/                     # Core foundation classes
│   │   ├── Plugin.php           # Main plugin class
│   │   ├── Container.php        # DI container
│   │   ├── ServiceProvider.php  # Base service provider
│   │   ├── Activator.php        # Plugin activator
│   │   └── Deactivator.php      # Plugin deactivator
│   └── functions.php            # Global helper functions
├── vendor/                      # Autoloader
│   └── autoload.php            # PSR-4 autoloader
├── includes/                    # Legacy classes (preserved)
├── admin/                       # Legacy admin classes (preserved)
├── public/                      # Legacy public classes (preserved)
├── assets/                      # Static assets
├── languages/                   # Localization files
├── templates/                   # Template files (future)
├── composer.json               # Composer configuration
└── chrono-forge.php            # Main plugin file (updated)
```

## Backward Compatibility

### Dual Architecture Support

The plugin now supports both architectures simultaneously:

1. **Modern Architecture**: Used when autoloader is available
2. **Legacy Architecture**: Fallback when modern architecture fails

### Legacy Function Preservation

All existing functions continue to work:
- `chrono_forge()` - Returns legacy core or modern plugin
- `chrono_forge_get_setting()` - Enhanced with service support
- `chrono_forge_log()` - Improved logging capabilities
- All utility functions preserved

### Gradual Migration Strategy

- New features use modern architecture
- Legacy code continues to function
- Components migrated incrementally
- No breaking changes for existing users

## Usage Examples

### Accessing the Modern Plugin

```php
// Get the main plugin instance
$plugin = chrono_forge_plugin();

// Access the container
$container = $plugin->container();

// Get plugin information
$version = $plugin->version();
$dir = $plugin->plugin_dir();
$url = $plugin->plugin_url();
```

### Using the Container

```php
// Get container instance
$container = chrono_forge_container();

// Bind a service
$container->singleton('my.service', function() {
    return new MyService();
});

// Resolve a service
$service = $container->make('my.service');
```

### Creating Service Providers

```php
use ChronoForge\Core\ServiceProvider;

class MyServiceProvider extends ServiceProvider {
    public function register() {
        $this->singleton('my.service', MyService::class);
    }
    
    public function boot() {
        $this->addAction('init', [$this, 'initialize']);
    }
}
```

## Benefits

### 1. Maintainability
- Smaller, focused classes
- Clear separation of concerns
- Dependency injection for testing
- Modern PHP practices

### 2. Extensibility
- Service provider pattern
- Event-driven architecture
- Plugin hooks and filters
- Modular design

### 3. Performance
- Autoloading reduces memory usage
- Lazy loading of services
- Optimized class loading
- Efficient dependency resolution

### 4. Developer Experience
- Modern IDE support
- Better debugging
- Clear architecture patterns
- Comprehensive documentation

## Migration Phases

### Phase 1: Foundation ✅ COMPLETED
- [x] Core architecture classes
- [x] Dependency injection container
- [x] Service provider base
- [x] Autoloading system
- [x] Backward compatibility

### Phase 2: Database Layer (Next)
- [ ] Repository pattern implementation
- [ ] Database migration system
- [ ] Model classes
- [ ] Query builders

### Phase 3: Service Layer
- [ ] Business logic services
- [ ] Event system
- [ ] Validation services
- [ ] Cache management

### Phase 4: Controllers
- [ ] AJAX controllers
- [ ] Shortcode controllers
- [ ] REST API controllers
- [ ] Admin controllers

### Phase 5: Frontend/Admin
- [ ] Asset management
- [ ] Template system
- [ ] Admin interface
- [ ] Frontend components

## Testing

Run the verification script to check architecture status:

```bash
php verify-architecture.php
```

## Configuration

### Composer Configuration

The `composer.json` file defines:
- PSR-4 autoloading for `ChronoForge\` namespace
- Development dependencies
- Scripts for testing and code standards

### Autoloader Configuration

The custom autoloader supports:
- Multiple namespace prefixes
- Directory mapping
- Automatic class loading
- Global functions inclusion

## Best Practices

### 1. Service Registration
- Use service providers for registration
- Prefer constructor injection
- Use interfaces for contracts
- Register services as singletons when appropriate

### 2. Backward Compatibility
- Always check for legacy function existence
- Provide fallbacks for legacy code
- Maintain existing API contracts
- Document breaking changes

### 3. Error Handling
- Use try-catch blocks for initialization
- Log errors appropriately
- Provide graceful degradation
- Show user-friendly error messages

## Troubleshooting

### Common Issues

1. **Autoloader not found**: Ensure `vendor/autoload.php` exists
2. **Class not found**: Check namespace and file naming
3. **Legacy conflicts**: Verify backward compatibility functions
4. **Container errors**: Check service bindings and dependencies

### Debug Mode

Enable WordPress debug mode for detailed logging:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

For questions about the new architecture:
1. Check this documentation
2. Review the code comments
3. Run the verification script
4. Check WordPress error logs
5. Contact the development team
