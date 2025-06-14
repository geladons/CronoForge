<?php
/**
 * Debug class loading for ChronoForge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Debug class loading
 */
function chrono_forge_debug_class_loading() {
    echo '<div class="wrap">';
    echo '<h1>ChronoForge Class Loading Debug</h1>';
    
    // Step 1: Load utility functions
    echo '<h2>Step 1: Loading Utility Functions</h2>';
    $utils_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/utils/functions.php';
    echo '<p>Utils file path: ' . $utils_file . '</p>';
    echo '<p>File exists: ' . (file_exists($utils_file) ? 'YES' : 'NO') . '</p>';
    
    if (file_exists($utils_file)) {
        try {
            require_once $utils_file;
            echo '<p>✓ Utils file loaded successfully</p>';
            
            // Test if function exists
            if (function_exists('chrono_forge_safe_log')) {
                echo '<p>✓ chrono_forge_safe_log function available</p>';
            } else {
                echo '<p>✗ chrono_forge_safe_log function NOT available</p>';
            }
            
            if (function_exists('chrono_forge_get_appointment_statuses')) {
                echo '<p>✓ chrono_forge_get_appointment_statuses function available</p>';
            } else {
                echo '<p>✗ chrono_forge_get_appointment_statuses function NOT available</p>';
            }
            
        } catch (Exception $e) {
            echo '<p>✗ Error loading utils: ' . esc_html($e->getMessage()) . '</p>';
        } catch (Error $e) {
            echo '<p>✗ Fatal error loading utils: ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    // Step 2: Load DB Manager
    echo '<h2>Step 2: Loading DB Manager</h2>';
    $db_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-db-manager.php';
    echo '<p>DB Manager file path: ' . $db_file . '</p>';
    echo '<p>File exists: ' . (file_exists($db_file) ? 'YES' : 'NO') . '</p>';
    
    if (file_exists($db_file)) {
        try {
            require_once $db_file;
            echo '<p>✓ DB Manager file loaded successfully</p>';
            
            if (class_exists('ChronoForge_DB_Manager')) {
                echo '<p>✓ ChronoForge_DB_Manager class available</p>';
                
                // Try to instantiate
                try {
                    $db_manager = new ChronoForge_DB_Manager();
                    echo '<p>✓ DB Manager instance created successfully</p>';
                } catch (Exception $e) {
                    echo '<p>✗ Error creating DB Manager instance: ' . esc_html($e->getMessage()) . '</p>';
                } catch (Error $e) {
                    echo '<p>✗ Fatal error creating DB Manager instance: ' . esc_html($e->getMessage()) . '</p>';
                }
            } else {
                echo '<p>✗ ChronoForge_DB_Manager class NOT available</p>';
            }
            
        } catch (Exception $e) {
            echo '<p>✗ Error loading DB Manager: ' . esc_html($e->getMessage()) . '</p>';
        } catch (Error $e) {
            echo '<p>✗ Fatal error loading DB Manager: ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    // Step 3: Load Admin Menu
    echo '<h2>Step 3: Loading Admin Menu</h2>';
    $admin_file = CHRONO_FORGE_PLUGIN_DIR . 'admin/class-chrono-forge-admin-menu.php';
    echo '<p>Admin Menu file path: ' . $admin_file . '</p>';
    echo '<p>File exists: ' . (file_exists($admin_file) ? 'YES' : 'NO') . '</p>';
    
    if (file_exists($admin_file)) {
        try {
            require_once $admin_file;
            echo '<p>✓ Admin Menu file loaded successfully</p>';
            
            if (class_exists('ChronoForge_Admin_Menu')) {
                echo '<p>✓ ChronoForge_Admin_Menu class available</p>';
            } else {
                echo '<p>✗ ChronoForge_Admin_Menu class NOT available</p>';
            }
            
        } catch (Exception $e) {
            echo '<p>✗ Error loading Admin Menu: ' . esc_html($e->getMessage()) . '</p>';
        } catch (Error $e) {
            echo '<p>✗ Fatal error loading Admin Menu: ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    // Step 4: Load Core
    echo '<h2>Step 4: Loading Core</h2>';
    $core_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-core.php';
    echo '<p>Core file path: ' . $core_file . '</p>';
    echo '<p>File exists: ' . (file_exists($core_file) ? 'YES' : 'NO') . '</p>';
    
    if (file_exists($core_file)) {
        try {
            require_once $core_file;
            echo '<p>✓ Core file loaded successfully</p>';
            
            if (class_exists('ChronoForge_Core')) {
                echo '<p>✓ ChronoForge_Core class available</p>';
                
                // Try to instantiate
                try {
                    $core = ChronoForge_Core::instance();
                    echo '<p>✓ Core instance created successfully</p>';
                    
                    // Check components
                    echo '<h3>Component Status:</h3>';
                    echo '<ul>';
                    echo '<li>DB Manager: ' . (isset($core->db_manager) && $core->db_manager ? '✓' : '✗') . '</li>';
                    echo '<li>Admin Menu: ' . (isset($core->admin_menu) && $core->admin_menu ? '✓' : '✗') . '</li>';
                    echo '<li>AJAX Handler: ' . (isset($core->ajax_handler) && $core->ajax_handler ? '✓' : '✗') . '</li>';
                    echo '<li>Shortcodes: ' . (isset($core->shortcodes) && $core->shortcodes ? '✓' : '✗') . '</li>';
                    echo '</ul>';
                    
                } catch (Exception $e) {
                    echo '<p>✗ Error creating Core instance: ' . esc_html($e->getMessage()) . '</p>';
                } catch (Error $e) {
                    echo '<p>✗ Fatal error creating Core instance: ' . esc_html($e->getMessage()) . '</p>';
                }
            } else {
                echo '<p>✗ ChronoForge_Core class NOT available</p>';
            }
            
        } catch (Exception $e) {
            echo '<p>✗ Error loading Core: ' . esc_html($e->getMessage()) . '</p>';
        } catch (Error $e) {
            echo '<p>✗ Fatal error loading Core: ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    // Step 5: Check WordPress environment
    echo '<h2>Step 5: WordPress Environment</h2>';
    echo '<p>WordPress loaded: ' . (function_exists('add_action') ? 'YES' : 'NO') . '</p>';
    echo '<p>Database available: ' . (isset($GLOBALS['wpdb']) && $GLOBALS['wpdb'] ? 'YES' : 'NO') . '</p>';
    echo '<p>Admin area: ' . (is_admin() ? 'YES' : 'NO') . '</p>';
    echo '<p>User can manage options: ' . (current_user_can('manage_options') ? 'YES' : 'NO') . '</p>';
    
    // Step 6: Check error reporting
    echo '<h2>Step 6: Error Reporting</h2>';
    echo '<p>Error reporting level: ' . error_reporting() . '</p>';
    echo '<p>Display errors: ' . (ini_get('display_errors') ? 'ON' : 'OFF') . '</p>';
    echo '<p>Log errors: ' . (ini_get('log_errors') ? 'ON' : 'OFF') . '</p>';
    echo '<p>WP_DEBUG: ' . (defined('WP_DEBUG') && WP_DEBUG ? 'ON' : 'OFF') . '</p>';
    echo '<p>WP_DEBUG_LOG: ' . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'ON' : 'OFF') . '</p>';
    
    echo '</div>';
}

// Add admin menu for debugging
add_action('admin_menu', function() {
    add_submenu_page(
        null, // No parent menu (hidden)
        'ChronoForge Debug',
        'ChronoForge Debug',
        'manage_options',
        'chrono-forge-debug',
        'chrono_forge_debug_class_loading'
    );
});

// Add direct access link
add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        $debug_url = admin_url('admin.php?page=chrono-forge-debug');
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>ChronoForge Debug:</strong> ';
        echo '<a href="' . esc_url($debug_url) . '">Run Class Loading Debug</a> ';
        echo 'to see detailed loading process.</p>';
        echo '</div>';
    }
});
