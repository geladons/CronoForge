<?php
/**
 * WordPress-specific class loading test for ChronoForge
 * This script tests class loading within WordPress environment
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress class loading test
 */
function chrono_forge_wordpress_class_test() {
    echo '<div class="wrap">';
    echo '<h1>ChronoForge WordPress Class Loading Test</h1>';
    
    // Test WordPress environment
    echo '<h2>WordPress Environment Check</h2>';
    echo '<table class="widefat">';
    echo '<tr><td><strong>ABSPATH defined:</strong></td><td>' . (defined('ABSPATH') ? '✓ YES' : '✗ NO') . '</td></tr>';
    echo '<tr><td><strong>WordPress loaded:</strong></td><td>' . (function_exists('add_action') ? '✓ YES' : '✗ NO') . '</td></tr>';
    echo '<tr><td><strong>Database available:</strong></td><td>' . (isset($GLOBALS['wpdb']) ? '✓ YES' : '✗ NO') . '</td></tr>';
    echo '<tr><td><strong>Admin area:</strong></td><td>' . (is_admin() ? '✓ YES' : '✗ NO') . '</td></tr>';
    echo '<tr><td><strong>Plugin constants:</strong></td><td>';
    
    $constants = ['CHRONO_FORGE_VERSION', 'CHRONO_FORGE_PLUGIN_DIR', 'CHRONO_FORGE_PLUGIN_URL'];
    foreach ($constants as $const) {
        echo $const . ': ' . (defined($const) ? '✓' : '✗') . '<br>';
    }
    echo '</td></tr>';
    echo '</table>';
    
    // Test error reporting
    echo '<h2>Error Reporting Status</h2>';
    echo '<table class="widefat">';
    echo '<tr><td><strong>Error reporting level:</strong></td><td>' . error_reporting() . '</td></tr>';
    echo '<tr><td><strong>Display errors:</strong></td><td>' . (ini_get('display_errors') ? 'ON' : 'OFF') . '</td></tr>';
    echo '<tr><td><strong>Log errors:</strong></td><td>' . (ini_get('log_errors') ? 'ON' : 'OFF') . '</td></tr>';
    echo '<tr><td><strong>WP_DEBUG:</strong></td><td>' . (defined('WP_DEBUG') && WP_DEBUG ? 'ON' : 'OFF') . '</td></tr>';
    echo '<tr><td><strong>WP_DEBUG_LOG:</strong></td><td>' . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'ON' : 'OFF') . '</td></tr>';
    echo '</table>';
    
    // Test class loading step by step
    echo '<h2>Step-by-Step Class Loading Test</h2>';
    
    // Step 1: Test utility functions
    echo '<h3>Step 1: Utility Functions</h3>';
    $utils_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/utils/functions.php';
    echo '<p><strong>File:</strong> ' . $utils_file . '</p>';
    echo '<p><strong>Exists:</strong> ' . (file_exists($utils_file) ? '✓ YES' : '✗ NO') . '</p>';
    
    if (file_exists($utils_file)) {
        try {
            // Capture any output or errors
            ob_start();
            $error_before = error_get_last();
            
            require_once $utils_file;
            
            $output = ob_get_clean();
            $error_after = error_get_last();
            
            if ($output) {
                echo '<p><strong>Output during load:</strong> <pre>' . esc_html($output) . '</pre></p>';
            }
            
            if ($error_after && $error_after !== $error_before) {
                echo '<p><strong>Error during load:</strong> ' . esc_html($error_after['message']) . '</p>';
            }
            
            echo '<p>✓ Utils file loaded successfully</p>';
            
            // Test key functions
            $functions = ['chrono_forge_safe_log', 'chrono_forge_get_appointment_statuses'];
            foreach ($functions as $func) {
                echo '<p>' . $func . ': ' . (function_exists($func) ? '✓' : '✗') . '</p>';
            }
            
        } catch (Exception $e) {
            echo '<p>✗ Exception: ' . esc_html($e->getMessage()) . '</p>';
        } catch (Error $e) {
            echo '<p>✗ Fatal Error: ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    // Step 2: Test DB Manager
    echo '<h3>Step 2: Database Manager</h3>';
    $db_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-db-manager.php';
    echo '<p><strong>File:</strong> ' . $db_file . '</p>';
    echo '<p><strong>Exists:</strong> ' . (file_exists($db_file) ? '✓ YES' : '✗ NO') . '</p>';
    
    if (file_exists($db_file)) {
        try {
            ob_start();
            $error_before = error_get_last();
            
            require_once $db_file;
            
            $output = ob_get_clean();
            $error_after = error_get_last();
            
            if ($output) {
                echo '<p><strong>Output during load:</strong> <pre>' . esc_html($output) . '</pre></p>';
            }
            
            if ($error_after && $error_after !== $error_before) {
                echo '<p><strong>Error during load:</strong> ' . esc_html($error_after['message']) . '</p>';
            }
            
            echo '<p>✓ DB Manager file loaded successfully</p>';
            echo '<p>ChronoForge_DB_Manager class: ' . (class_exists('ChronoForge_DB_Manager') ? '✓' : '✗') . '</p>';
            
            // Try to instantiate
            if (class_exists('ChronoForge_DB_Manager')) {
                try {
                    $db_manager = new ChronoForge_DB_Manager();
                    echo '<p>✓ DB Manager instance created successfully</p>';
                } catch (Exception $e) {
                    echo '<p>✗ DB Manager instantiation error: ' . esc_html($e->getMessage()) . '</p>';
                }
            }
            
        } catch (Exception $e) {
            echo '<p>✗ Exception: ' . esc_html($e->getMessage()) . '</p>';
        } catch (Error $e) {
            echo '<p>✗ Fatal Error: ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    // Step 3: Test Admin Menu
    echo '<h3>Step 3: Admin Menu</h3>';
    $admin_file = CHRONO_FORGE_PLUGIN_DIR . 'admin/class-chrono-forge-admin-menu.php';
    echo '<p><strong>File:</strong> ' . $admin_file . '</p>';
    echo '<p><strong>Exists:</strong> ' . (file_exists($admin_file) ? '✓ YES' : '✗ NO') . '</p>';
    
    if (file_exists($admin_file)) {
        try {
            ob_start();
            $error_before = error_get_last();
            
            require_once $admin_file;
            
            $output = ob_get_clean();
            $error_after = error_get_last();
            
            if ($output) {
                echo '<p><strong>Output during load:</strong> <pre>' . esc_html($output) . '</pre></p>';
            }
            
            if ($error_after && $error_after !== $error_before) {
                echo '<p><strong>Error during load:</strong> ' . esc_html($error_after['message']) . '</p>';
            }
            
            echo '<p>✓ Admin Menu file loaded successfully</p>';
            echo '<p>ChronoForge_Admin_Menu class: ' . (class_exists('ChronoForge_Admin_Menu') ? '✓' : '✗') . '</p>';
            
        } catch (Exception $e) {
            echo '<p>✗ Exception: ' . esc_html($e->getMessage()) . '</p>';
        } catch (Error $e) {
            echo '<p>✗ Fatal Error: ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    // Step 4: Test Core
    echo '<h3>Step 4: Core Class</h3>';
    $core_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-core.php';
    echo '<p><strong>File:</strong> ' . $core_file . '</p>';
    echo '<p><strong>Exists:</strong> ' . (file_exists($core_file) ? '✓ YES' : '✗ NO') . '</p>';
    
    if (file_exists($core_file)) {
        try {
            ob_start();
            $error_before = error_get_last();
            
            require_once $core_file;
            
            $output = ob_get_clean();
            $error_after = error_get_last();
            
            if ($output) {
                echo '<p><strong>Output during load:</strong> <pre>' . esc_html($output) . '</pre></p>';
            }
            
            if ($error_after && $error_after !== $error_before) {
                echo '<p><strong>Error during load:</strong> ' . esc_html($error_after['message']) . '</p>';
            }
            
            echo '<p>✓ Core file loaded successfully</p>';
            echo '<p>ChronoForge_Core class: ' . (class_exists('ChronoForge_Core') ? '✓' : '✗') . '</p>';
            
            // Try to instantiate
            if (class_exists('ChronoForge_Core')) {
                try {
                    $core = ChronoForge_Core::instance();
                    echo '<p>✓ Core instance created successfully</p>';
                    
                    // Check components
                    echo '<h4>Component Status:</h4>';
                    echo '<ul>';
                    echo '<li>DB Manager: ' . (isset($core->db_manager) && $core->db_manager ? '✓' : '✗') . '</li>';
                    echo '<li>Admin Menu: ' . (isset($core->admin_menu) && $core->admin_menu ? '✓' : '✗') . '</li>';
                    echo '<li>AJAX Handler: ' . (isset($core->ajax_handler) && $core->ajax_handler ? '✓' : '✗') . '</li>';
                    echo '</ul>';
                    
                } catch (Exception $e) {
                    echo '<p>✗ Core instantiation error: ' . esc_html($e->getMessage()) . '</p>';
                }
            }
            
        } catch (Exception $e) {
            echo '<p>✗ Exception: ' . esc_html($e->getMessage()) . '</p>';
        } catch (Error $e) {
            echo '<p>✗ Fatal Error: ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    echo '</div>';
}

// Add admin menu for WordPress testing
add_action('admin_menu', function() {
    add_submenu_page(
        null, // No parent menu (hidden)
        'ChronoForge WordPress Test',
        'ChronoForge WordPress Test',
        'manage_options',
        'chrono-forge-wp-test',
        'chrono_forge_wordpress_class_test'
    );
});

// Add direct access link
add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        $test_url = admin_url('admin.php?page=chrono-forge-wp-test');
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>ChronoForge WordPress Test:</strong> ';
        echo '<a href="' . esc_url($test_url) . '">Run WordPress Class Loading Test</a> ';
        echo 'to see detailed loading process within WordPress.</p>';
        echo '</div>';
    }
});
