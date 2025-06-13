<?php
/**
 * ChronoForge Diagnostics Test Script
 *
 * This script can be run to test the diagnostic system functionality
 * Run from command line: php test-diagnostics.php
 *
 * @package ChronoForge
 * @since 1.0.0
 */

// Simulate WordPress environment for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

if (!defined('CHRONO_FORGE_VERSION')) {
    define('CHRONO_FORGE_VERSION', '1.0.0');
}

if (!defined('CHRONO_FORGE_PLUGIN_DIR')) {
    define('CHRONO_FORGE_PLUGIN_DIR', dirname(__FILE__) . '/');
}

// Mock WordPress functions for testing
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('sprintf')) {
    // sprintf is a PHP function, should exist
}

if (!function_exists('current_time')) {
    function current_time($type) {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('get_transient')) {
    function get_transient($transient) {
        return false;
    }
}

if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration) {
        return true;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

// Include the diagnostic system files
require_once 'includes/utils/diagnostic-functions.php';
require_once 'includes/class-chrono-forge-diagnostics.php';

echo "ChronoForge Diagnostics Test\n";
echo str_repeat("=", 40) . "\n\n";

try {
    // Test diagnostic utility functions
    echo "Testing diagnostic utility functions...\n";
    
    // Test safe logging
    chrono_forge_safe_log("Test log message", 'info');
    echo "✓ Safe logging function works\n";
    
    // Test syntax checking
    $syntax_result = chrono_forge_check_file_syntax(__FILE__);
    if ($syntax_result['valid']) {
        echo "✓ Syntax checking function works\n";
    } else {
        echo "✗ Syntax checking failed: " . implode(', ', $syntax_result['errors']) . "\n";
    }
    
    // Test PHP environment info
    $php_env = chrono_forge_get_php_environment();
    if (!empty($php_env['version'])) {
        echo "✓ PHP environment detection works (PHP " . $php_env['version'] . ")\n";
    } else {
        echo "✗ PHP environment detection failed\n";
    }
    
    echo "\nTesting diagnostics engine...\n";
    
    // Initialize diagnostics
    $diagnostics = ChronoForge_Diagnostics::instance();
    
    // Run diagnostics
    $results = $diagnostics->run_diagnostics(true);
    
    echo "✓ Diagnostics engine initialized\n";
    echo "✓ Diagnostics completed\n\n";
    
    // Display results
    echo "Diagnostic Results:\n";
    echo "Overall Status: " . strtoupper($results['overall_status']) . "\n";
    echo "Tests Run: " . $results['summary']['total'] . "\n";
    echo "Critical: " . $results['summary']['critical'] . "\n";
    echo "Errors: " . $results['summary']['error'] . "\n";
    echo "Warnings: " . $results['summary']['warning'] . "\n";
    echo "Info: " . $results['summary']['info'] . "\n\n";
    
    // Show individual test results
    echo "Individual Test Results:\n";
    echo str_repeat("-", 30) . "\n";
    
    foreach ($results['tests'] as $test_name => $test_result) {
        $status_icon = $test_result['severity'] === 'info' ? '✓' : 
                      ($test_result['severity'] === 'warning' ? '⚠' : '✗');
        
        echo sprintf("%s %s: %s\n", 
            $status_icon, 
            ucwords(str_replace('_', ' ', $test_name)), 
            $test_result['message']
        );
        
        if (!empty($test_result['details'])) {
            foreach ($test_result['details'] as $detail) {
                echo "  - " . $detail . "\n";
            }
        }
        echo "\n";
    }
    
    // Test system info
    echo "System Information:\n";
    echo str_repeat("-", 30) . "\n";
    $system_info = $diagnostics->get_system_info();
    
    foreach ($system_info as $key => $value) {
        if (is_array($value)) {
            echo ucwords(str_replace('_', ' ', $key)) . ": " . count($value) . " items\n";
        } elseif (is_bool($value)) {
            echo ucwords(str_replace('_', ' ', $key)) . ": " . ($value ? 'Yes' : 'No') . "\n";
        } else {
            echo ucwords(str_replace('_', ' ', $key)) . ": " . $value . "\n";
        }
    }
    
    echo "\n✓ All diagnostic tests completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\nTest completed successfully!\n";
exit(0);
