<?php
/**
 * Isolated class test - loads one class at a time to identify the problem
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test individual class loading
 */
function chrono_forge_isolated_class_test() {
    echo '<div class="wrap">';
    echo '<h1>ChronoForge Isolated Class Test</h1>';
    echo '<p>This test loads each class individually to identify the exact failure point.</p>';
    
    // Test environment first
    echo '<h2>Environment Check</h2>';
    echo '<table class="widefat">';
    echo '<tr><td><strong>WordPress Version:</strong></td><td>' . get_bloginfo('version') . '</td></tr>';
    echo '<tr><td><strong>PHP Version:</strong></td><td>' . PHP_VERSION . '</td></tr>';
    echo '<tr><td><strong>Memory Limit:</strong></td><td>' . ini_get('memory_limit') . '</td></tr>';
    echo '<tr><td><strong>Max Execution Time:</strong></td><td>' . ini_get('max_execution_time') . '</td></tr>';
    echo '<tr><td><strong>Error Reporting:</strong></td><td>' . error_reporting() . '</td></tr>';
    echo '<tr><td><strong>Display Errors:</strong></td><td>' . (ini_get('display_errors') ? 'ON' : 'OFF') . '</td></tr>';
    echo '</table>';
    
    // Test 1: Load ONLY utility functions
    echo '<h2>Test 1: Utility Functions Only</h2>';
    
    // Reset any previous includes
    $included_files_before = get_included_files();
    
    $utils_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/utils/functions.php';
    echo '<p><strong>File:</strong> ' . $utils_file . '</p>';
    echo '<p><strong>Exists:</strong> ' . (file_exists($utils_file) ? '✓' : '✗') . '</p>';
    echo '<p><strong>Readable:</strong> ' . (is_readable($utils_file) ? '✓' : '✗') . '</p>';
    echo '<p><strong>Size:</strong> ' . (file_exists($utils_file) ? filesize($utils_file) . ' bytes' : 'N/A') . '</p>';
    
    if (file_exists($utils_file) && is_readable($utils_file)) {
        // Enable maximum error reporting
        $old_error_reporting = error_reporting(E_ALL);
        $old_display_errors = ini_get('display_errors');
        ini_set('display_errors', 1);
        
        // Capture all output and errors
        ob_start();
        $error_before = error_get_last();
        
        try {
            include_once $utils_file;
            $load_success = true;
        } catch (ParseError $e) {
            $load_success = false;
            echo '<p>✗ <strong>Parse Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
            echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
        } catch (Error $e) {
            $load_success = false;
            echo '<p>✗ <strong>Fatal Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
            echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
        } catch (Exception $e) {
            $load_success = false;
            echo '<p>✗ <strong>Exception:</strong> ' . esc_html($e->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
            echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
        }
        
        $output = ob_get_clean();
        $error_after = error_get_last();
        
        // Restore error reporting
        error_reporting($old_error_reporting);
        ini_set('display_errors', $old_display_errors);
        
        if ($output) {
            echo '<p><strong>Output during load:</strong></p>';
            echo '<pre>' . esc_html($output) . '</pre>';
        }
        
        if ($error_after && $error_after !== $error_before) {
            echo '<p><strong>PHP Error:</strong> ' . esc_html($error_after['message']) . '</p>';
            echo '<p><strong>File:</strong> ' . esc_html($error_after['file']) . '</p>';
            echo '<p><strong>Line:</strong> ' . $error_after['line'] . '</p>';
        }
        
        if ($load_success) {
            echo '<p>✓ <strong>File loaded successfully</strong></p>';
            
            // Test specific functions
            $test_functions = [
                'chrono_forge_safe_log',
                'chrono_forge_log', 
                'chrono_forge_get_appointment_statuses',
                'chrono_forge_get_setting'
            ];
            
            echo '<h3>Function Availability:</h3>';
            echo '<ul>';
            foreach ($test_functions as $func) {
                echo '<li>' . $func . ': ' . (function_exists($func) ? '✓' : '✗') . '</li>';
            }
            echo '</ul>';
            
            // Test calling a function
            if (function_exists('chrono_forge_safe_log')) {
                try {
                    chrono_forge_safe_log('Test log message from isolated test', 'info');
                    echo '<p>✓ chrono_forge_safe_log function works</p>';
                } catch (Throwable $e) {
                    echo '<p>✗ Error calling chrono_forge_safe_log: ' . esc_html($e->getMessage()) . '</p>';
                }
            }
        }
    }
    
    // Test 2: Load ONLY DB Manager (after utils)
    echo '<h2>Test 2: Database Manager Only</h2>';
    
    $db_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-db-manager.php';
    echo '<p><strong>File:</strong> ' . $db_file . '</p>';
    echo '<p><strong>Exists:</strong> ' . (file_exists($db_file) ? '✓' : '✗') . '</p>';
    echo '<p><strong>Readable:</strong> ' . (is_readable($db_file) ? '✓' : '✗') . '</p>';
    echo '<p><strong>Size:</strong> ' . (file_exists($db_file) ? filesize($db_file) . ' bytes' : 'N/A') . '</p>';
    
    if (file_exists($db_file) && is_readable($db_file)) {
        $old_error_reporting = error_reporting(E_ALL);
        $old_display_errors = ini_get('display_errors');
        ini_set('display_errors', 1);
        
        ob_start();
        $error_before = error_get_last();
        
        try {
            include_once $db_file;
            $load_success = true;
        } catch (ParseError $e) {
            $load_success = false;
            echo '<p>✗ <strong>Parse Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
            echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
        } catch (Error $e) {
            $load_success = false;
            echo '<p>✗ <strong>Fatal Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
            echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
        } catch (Exception $e) {
            $load_success = false;
            echo '<p>✗ <strong>Exception:</strong> ' . esc_html($e->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
            echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
        }
        
        $output = ob_get_clean();
        $error_after = error_get_last();
        
        error_reporting($old_error_reporting);
        ini_set('display_errors', $old_display_errors);
        
        if ($output) {
            echo '<p><strong>Output during load:</strong></p>';
            echo '<pre>' . esc_html($output) . '</pre>';
        }
        
        if ($error_after && $error_after !== $error_before) {
            echo '<p><strong>PHP Error:</strong> ' . esc_html($error_after['message']) . '</p>';
            echo '<p><strong>File:</strong> ' . esc_html($error_after['file']) . '</p>';
            echo '<p><strong>Line:</strong> ' . $error_after['line'] . '</p>';
        }
        
        if ($load_success) {
            echo '<p>✓ <strong>File loaded successfully</strong></p>';
            echo '<p>ChronoForge_DB_Manager class: ' . (class_exists('ChronoForge_DB_Manager') ? '✓' : '✗') . '</p>';
            
            // Try to instantiate
            if (class_exists('ChronoForge_DB_Manager')) {
                try {
                    $db_manager = new ChronoForge_DB_Manager();
                    echo '<p>✓ DB Manager instantiated successfully</p>';
                } catch (Throwable $e) {
                    echo '<p>✗ Error instantiating DB Manager: ' . esc_html($e->getMessage()) . '</p>';
                    echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
                    echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
                }
            }
        }
    }
    
    // Show currently loaded classes
    echo '<h2>Currently Loaded Classes</h2>';
    $loaded_classes = get_declared_classes();
    $chrono_classes = array_filter($loaded_classes, function($class) {
        return stripos($class, 'chrono') !== false;
    });
    
    if (empty($chrono_classes)) {
        echo '<p>No ChronoForge-related classes found</p>';
    } else {
        echo '<ul>';
        foreach ($chrono_classes as $class) {
            echo '<li>' . esc_html($class) . '</li>';
        }
        echo '</ul>';
    }
    
    echo '</div>';
}

// Add admin menu
add_action('admin_menu', function() {
    add_submenu_page(
        null,
        'Isolated Class Test',
        'Isolated Class Test',
        'manage_options',
        'chrono-forge-isolated-test',
        'chrono_forge_isolated_class_test'
    );
});

// Add admin notice
add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        $test_url = admin_url('admin.php?page=chrono-forge-isolated-test');
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>ChronoForge Isolated Test:</strong> ';
        echo '<a href="' . esc_url($test_url) . '">Run Isolated Class Test</a> ';
        echo 'to test individual class loading.</p>';
        echo '</div>';
    }
});
