<?php
/**
 * ChronoForge Emergency Recovery Script
 * 
 * This script can be used to diagnose and recover from critical errors
 * Run this script directly to check plugin status and attempt recovery
 */

// Prevent direct access in normal WordPress context
if (!defined('CHRONO_FORGE_EMERGENCY_MODE')) {
    define('CHRONO_FORGE_EMERGENCY_MODE', true);
}

// Basic error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "ChronoForge Emergency Recovery Script\n";
echo "=====================================\n\n";

// Check if we're in WordPress context
if (defined('ABSPATH')) {
    echo "✓ WordPress environment detected\n";
} else {
    echo "⚠ Not in WordPress environment - limited functionality\n";
}

// Check plugin directory
$plugin_dir = __DIR__;
echo "Plugin directory: {$plugin_dir}\n";

// Check critical files
$critical_files = [
    'chrono-forge.php',
    'includes/class-chrono-forge-core.php',
    'includes/class-chrono-forge-db-manager.php',
    'includes/utils/functions.php',
    'includes/class-chrono-forge-activator.php'
];

echo "\nChecking critical files:\n";
$missing_files = [];
foreach ($critical_files as $file) {
    $file_path = $plugin_dir . '/' . $file;
    if (file_exists($file_path)) {
        echo "✓ {$file}\n";
    } else {
        echo "✗ {$file} - MISSING\n";
        $missing_files[] = $file;
    }
}

if (!empty($missing_files)) {
    echo "\n❌ Critical files are missing. Plugin cannot function.\n";
    echo "Missing files: " . implode(', ', $missing_files) . "\n";
    exit(1);
}

// Check for syntax errors
echo "\nChecking for syntax errors:\n";
foreach ($critical_files as $file) {
    $file_path = $plugin_dir . '/' . $file;
    if (file_exists($file_path)) {
        $output = [];
        $return_var = 0;
        exec("php -l \"{$file_path}\" 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            echo "✓ {$file} - syntax OK\n";
        } else {
            echo "✗ {$file} - SYNTAX ERROR:\n";
            echo "  " . implode("\n  ", $output) . "\n";
        }
    }
}

// Test basic functionality if in WordPress context
if (defined('ABSPATH')) {
    echo "\nTesting WordPress integration:\n";
    
    // Test database connection
    global $wpdb;
    if ($wpdb) {
        $result = $wpdb->get_var("SELECT 1");
        if ($result === '1') {
            echo "✓ Database connection OK\n";
        } else {
            echo "✗ Database connection failed\n";
        }
    } else {
        echo "✗ WordPress database object not available\n";
    }
    
    // Test plugin loading
    try {
        require_once $plugin_dir . '/includes/utils/functions.php';
        echo "✓ Utility functions loaded\n";
        
        require_once $plugin_dir . '/includes/class-chrono-forge-core.php';
        echo "✓ Core class loaded\n";
        
        if (class_exists('ChronoForge_Core')) {
            echo "✓ Core class exists\n";
        } else {
            echo "✗ Core class not found after loading\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Error loading plugin components: " . $e->getMessage() . "\n";
    } catch (Error $e) {
        echo "✗ Fatal error loading plugin components: " . $e->getMessage() . "\n";
    }
}

// Memory usage check
$memory_usage = memory_get_usage(true);
$memory_limit = ini_get('memory_limit');
echo "\nMemory usage: " . round($memory_usage / 1024 / 1024, 2) . "MB\n";
echo "Memory limit: {$memory_limit}\n";

// Recommendations
echo "\nRecovery Recommendations:\n";
echo "========================\n";

if (!empty($missing_files)) {
    echo "1. Restore missing files from backup or re-upload plugin\n";
}

echo "2. Check WordPress error logs for detailed error messages\n";
echo "3. Ensure PHP version is 7.4 or higher\n";
echo "4. Verify database connection and permissions\n";
echo "5. Check for plugin conflicts by deactivating other plugins\n";
echo "6. Increase PHP memory limit if needed\n";

echo "\nIf problems persist, contact support with this diagnostic information.\n";
