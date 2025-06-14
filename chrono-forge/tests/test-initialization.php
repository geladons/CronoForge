<?php
/**
 * ChronoForge Initialization Test
 * 
 * This file tests the plugin initialization process
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test ChronoForge initialization
 */
function chrono_forge_test_initialization() {
    echo '<div class="wrap">';
    echo '<h1>ChronoForge Initialization Test</h1>';
    
    // Test 1: Check constants
    echo '<h2>1. Constants Check</h2>';
    $constants = array(
        'CHRONO_FORGE_VERSION',
        'CHRONO_FORGE_PLUGIN_FILE',
        'CHRONO_FORGE_PLUGIN_DIR',
        'CHRONO_FORGE_PLUGIN_URL',
        'CHRONO_FORGE_PLUGIN_BASENAME'
    );
    
    echo '<table class="widefat">';
    foreach ($constants as $constant) {
        $defined = defined($constant);
        $value = $defined ? constant($constant) : 'Not defined';
        echo '<tr>';
        echo '<td><strong>' . $constant . '</strong></td>';
        echo '<td>' . ($defined ? '✓' : '✗') . '</td>';
        echo '<td>' . esc_html($value) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    // Test 2: Check file existence
    echo '<h2>2. Critical Files Check</h2>';
    $files = array(
        'includes/class-chrono-forge-core.php',
        'includes/class-chrono-forge-db-manager.php',
        'admin/class-chrono-forge-admin-menu.php',
        'includes/utils/functions.php',
        'admin/views/view-settings.php'
    );
    
    echo '<table class="widefat">';
    foreach ($files as $file) {
        $full_path = CHRONO_FORGE_PLUGIN_DIR . $file;
        $exists = file_exists($full_path);
        echo '<tr>';
        echo '<td><strong>' . $file . '</strong></td>';
        echo '<td>' . ($exists ? '✓' : '✗') . '</td>';
        echo '<td>' . ($exists ? 'Exists' : 'Missing') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    // Test 3: Check class loading
    echo '<h2>3. Class Loading Test</h2>';
    
    // Load utility functions
    $utils_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/utils/functions.php';
    if (file_exists($utils_file)) {
        require_once $utils_file;
        echo '<p>✓ Utility functions loaded</p>';
    } else {
        echo '<p>✗ Utility functions not found</p>';
    }
    
    // Load core class
    $core_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-core.php';
    if (file_exists($core_file)) {
        require_once $core_file;
        echo '<p>✓ Core class file loaded</p>';
        
        if (class_exists('ChronoForge_Core')) {
            echo '<p>✓ ChronoForge_Core class exists</p>';
            
            try {
                $instance = ChronoForge_Core::instance();
                echo '<p>✓ Core instance created successfully</p>';
                
                // Check components
                echo '<h3>Component Status:</h3>';
                echo '<ul>';
                echo '<li>DB Manager: ' . (isset($instance->db_manager) && $instance->db_manager ? '✓' : '✗') . '</li>';
                echo '<li>Admin Menu: ' . (isset($instance->admin_menu) && $instance->admin_menu ? '✓' : '✗') . '</li>';
                echo '<li>AJAX Handler: ' . (isset($instance->ajax_handler) && $instance->ajax_handler ? '✓' : '✗') . '</li>';
                echo '<li>Shortcodes: ' . (isset($instance->shortcodes) && $instance->shortcodes ? '✓' : '✗') . '</li>';
                echo '</ul>';
                
            } catch (Exception $e) {
                echo '<p>✗ Error creating core instance: ' . esc_html($e->getMessage()) . '</p>';
            }
        } else {
            echo '<p>✗ ChronoForge_Core class not found</p>';
        }
    } else {
        echo '<p>✗ Core class file not found</p>';
    }
    
    // Test 4: Database connection
    echo '<h2>4. Database Test</h2>';
    global $wpdb;
    
    try {
        $result = $wpdb->get_var("SELECT 1");
        echo '<p>✓ Database connection working</p>';
        
        // Check if tables exist
        $tables = array(
            'chrono_forge_services',
            'chrono_forge_employees',
            'chrono_forge_customers',
            'chrono_forge_appointments',
            'chrono_forge_schedules'
        );
        
        echo '<h3>Database Tables:</h3>';
        echo '<ul>';
        foreach ($tables as $table) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") === $full_table;
            echo '<li>' . $table . ': ' . ($exists ? '✓' : '✗') . '</li>';
        }
        echo '</ul>';
        
    } catch (Exception $e) {
        echo '<p>✗ Database error: ' . esc_html($e->getMessage()) . '</p>';
    }
    
    // Test 5: WordPress admin menu
    echo '<h2>5. Admin Menu Test</h2>';
    global $menu, $submenu;
    
    $chrono_menu_found = false;
    if (is_array($menu)) {
        foreach ($menu as $menu_item) {
            if (isset($menu_item[2]) && strpos($menu_item[2], 'chrono-forge') === 0) {
                $chrono_menu_found = true;
                echo '<p>✓ ChronoForge menu found: ' . esc_html($menu_item[1]) . ' (' . esc_html($menu_item[2]) . ')</p>';
                break;
            }
        }
    }
    
    if (!$chrono_menu_found) {
        echo '<p>✗ ChronoForge menu not found in WordPress admin menu</p>';
    }
    
    // Test 6: User permissions
    echo '<h2>6. User Permissions Test</h2>';
    $can_manage = current_user_can('manage_options');
    echo '<p>Current user can manage_options: ' . ($can_manage ? '✓ YES' : '✗ NO') . '</p>';
    
    if ($can_manage) {
        echo '<p>✓ User has sufficient permissions to access ChronoForge settings</p>';
    } else {
        echo '<p>✗ User does not have sufficient permissions</p>';
    }
    
    // Test 7: Settings page access
    echo '<h2>7. Settings Page Test</h2>';
    $settings_url = admin_url('admin.php?page=chrono-forge-settings');
    echo '<p><a href="' . esc_url($settings_url) . '" class="button button-primary">Test Settings Page Access</a></p>';
    
    echo '</div>';
}

// Add admin menu for testing
add_action('admin_menu', function() {
    add_submenu_page(
        null, // No parent menu (hidden)
        'ChronoForge Test',
        'ChronoForge Test',
        'manage_options',
        'chrono-forge-test',
        'chrono_forge_test_initialization'
    );
});

// Add direct access link
add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        $test_url = admin_url('admin.php?page=chrono-forge-test');
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>ChronoForge:</strong> ';
        echo '<a href="' . esc_url($test_url) . '">Run Initialization Test</a> ';
        echo 'to diagnose plugin issues.</p>';
        echo '</div>';
    }
});
