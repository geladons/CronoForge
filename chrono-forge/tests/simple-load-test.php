<?php
/**
 * Simple load test to identify the exact failure point
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add a simple admin page to test class loading
add_action('admin_menu', function() {
    add_submenu_page(
        null,
        'Simple Load Test',
        'Simple Load Test', 
        'manage_options',
        'chrono-forge-simple-test',
        function() {
            echo '<div class="wrap">';
            echo '<h1>ChronoForge Simple Load Test</h1>';
            
            // Test 1: Basic WordPress environment
            echo '<h2>WordPress Environment</h2>';
            echo '<p>WordPress loaded: ' . (function_exists('add_action') ? '✓' : '✗') . '</p>';
            echo '<p>Database available: ' . (isset($GLOBALS['wpdb']) ? '✓' : '✗') . '</p>';
            echo '<p>Current user can manage options: ' . (current_user_can('manage_options') ? '✓' : '✗') . '</p>';
            
            // Test 2: Plugin constants
            echo '<h2>Plugin Constants</h2>';
            echo '<p>CHRONO_FORGE_PLUGIN_DIR: ' . (defined('CHRONO_FORGE_PLUGIN_DIR') ? CHRONO_FORGE_PLUGIN_DIR : 'NOT DEFINED') . '</p>';
            echo '<p>CHRONO_FORGE_VERSION: ' . (defined('CHRONO_FORGE_VERSION') ? CHRONO_FORGE_VERSION : 'NOT DEFINED') . '</p>';
            
            // Test 3: Try to load utility functions manually
            echo '<h2>Manual Class Loading</h2>';
            
            $utils_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/utils/functions.php';
            echo '<h3>Loading Utils</h3>';
            echo '<p>File path: ' . $utils_file . '</p>';
            echo '<p>File exists: ' . (file_exists($utils_file) ? '✓' : '✗') . '</p>';
            
            if (file_exists($utils_file)) {
                try {
                    // Enable error reporting for this test
                    $old_error_reporting = error_reporting(E_ALL);
                    $old_display_errors = ini_get('display_errors');
                    ini_set('display_errors', 1);
                    
                    // Capture any output
                    ob_start();
                    require_once $utils_file;
                    $output = ob_get_clean();
                    
                    // Restore error reporting
                    error_reporting($old_error_reporting);
                    ini_set('display_errors', $old_display_errors);
                    
                    if ($output) {
                        echo '<p><strong>Output:</strong> <pre>' . esc_html($output) . '</pre></p>';
                    }
                    
                    echo '<p>✓ Utils loaded successfully</p>';
                    echo '<p>chrono_forge_safe_log function: ' . (function_exists('chrono_forge_safe_log') ? '✓' : '✗') . '</p>';
                    
                } catch (Throwable $e) {
                    echo '<p>✗ Error loading utils: ' . esc_html($e->getMessage()) . '</p>';
                    echo '<p>File: ' . esc_html($e->getFile()) . '</p>';
                    echo '<p>Line: ' . $e->getLine() . '</p>';
                }
            }
            
            // Test 4: Try to load DB Manager
            echo '<h3>Loading DB Manager</h3>';
            $db_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-db-manager.php';
            echo '<p>File path: ' . $db_file . '</p>';
            echo '<p>File exists: ' . (file_exists($db_file) ? '✓' : '✗') . '</p>';
            
            if (file_exists($db_file)) {
                try {
                    $old_error_reporting = error_reporting(E_ALL);
                    $old_display_errors = ini_get('display_errors');
                    ini_set('display_errors', 1);
                    
                    ob_start();
                    require_once $db_file;
                    $output = ob_get_clean();
                    
                    error_reporting($old_error_reporting);
                    ini_set('display_errors', $old_display_errors);
                    
                    if ($output) {
                        echo '<p><strong>Output:</strong> <pre>' . esc_html($output) . '</pre></p>';
                    }
                    
                    echo '<p>✓ DB Manager file loaded successfully</p>';
                    echo '<p>ChronoForge_DB_Manager class: ' . (class_exists('ChronoForge_DB_Manager') ? '✓' : '✗') . '</p>';
                    
                    // Try to instantiate
                    if (class_exists('ChronoForge_DB_Manager')) {
                        try {
                            $db_manager = new ChronoForge_DB_Manager();
                            echo '<p>✓ DB Manager instantiated successfully</p>';
                        } catch (Throwable $e) {
                            echo '<p>✗ Error instantiating DB Manager: ' . esc_html($e->getMessage()) . '</p>';
                            echo '<p>File: ' . esc_html($e->getFile()) . '</p>';
                            echo '<p>Line: ' . $e->getLine() . '</p>';
                        }
                    }
                    
                } catch (Throwable $e) {
                    echo '<p>✗ Error loading DB Manager: ' . esc_html($e->getMessage()) . '</p>';
                    echo '<p>File: ' . esc_html($e->getFile()) . '</p>';
                    echo '<p>Line: ' . $e->getLine() . '</p>';
                }
            }
            
            // Test 5: Check what classes are currently loaded
            echo '<h2>Currently Loaded Classes</h2>';
            $loaded_classes = get_declared_classes();
            $chrono_classes = array_filter($loaded_classes, function($class) {
                return strpos($class, 'ChronoForge') !== false;
            });
            
            if (empty($chrono_classes)) {
                echo '<p>No ChronoForge classes found in loaded classes</p>';
            } else {
                echo '<p>Found ChronoForge classes:</p>';
                echo '<ul>';
                foreach ($chrono_classes as $class) {
                    echo '<li>' . esc_html($class) . '</li>';
                }
                echo '</ul>';
            }
            
            // Test 6: Check error log
            echo '<h2>Recent Error Log Entries</h2>';
            $log_file = ini_get('error_log');
            if ($log_file && file_exists($log_file)) {
                echo '<p>Error log file: ' . esc_html($log_file) . '</p>';
                $log_content = file_get_contents($log_file);
                $lines = explode("\n", $log_content);
                $recent_lines = array_slice($lines, -20); // Last 20 lines
                
                $chrono_lines = array_filter($recent_lines, function($line) {
                    return strpos($line, 'ChronoForge') !== false;
                });
                
                if (!empty($chrono_lines)) {
                    echo '<p>Recent ChronoForge log entries:</p>';
                    echo '<pre>' . esc_html(implode("\n", $chrono_lines)) . '</pre>';
                } else {
                    echo '<p>No recent ChronoForge entries in error log</p>';
                }
            } else {
                echo '<p>Error log not available or not configured</p>';
            }
            
            echo '</div>';
        }
    );
});

// Add admin notice for easy access
add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        $test_url = admin_url('admin.php?page=chrono-forge-simple-test');
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>ChronoForge Simple Test:</strong> ';
        echo '<a href="' . esc_url($test_url) . '">Run Simple Load Test</a> ';
        echo 'to identify the exact failure point.</p>';
        echo '</div>';
    }
});
