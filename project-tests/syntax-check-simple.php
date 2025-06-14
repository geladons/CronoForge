<?php
/**
 * Simple Syntax Checker for ChronoForge Plugin
 *
 * This script performs basic syntax checking on ChronoForge plugin files
 * to identify potential PHP syntax errors.
 *
 * @package ChronoForge
 * @version 1.0.0
 */

// Prevent direct access in WordPress context
if (defined('ABSPATH')) {
    wp_die('This script should not be run in WordPress context');
}

echo "=== ChronoForge Simple Syntax Checker ===\n\n";

// Get the plugin directory
$plugin_dir = dirname(__DIR__) . '/chrono-forge';

if (!is_dir($plugin_dir)) {
    echo "❌ ChronoForge plugin directory not found at: {$plugin_dir}\n";
    exit(1);
}

echo "📁 Checking plugin directory: {$plugin_dir}\n\n";

/**
 * Check syntax of a PHP file
 */
function check_file_syntax($file_path) {
    $output = [];
    $return_code = 0;
    
    // Use php -l to check syntax
    exec("php -l " . escapeshellarg($file_path) . " 2>&1", $output, $return_code);
    
    return [
        'valid' => $return_code === 0,
        'output' => implode("\n", $output)
    ];
}

/**
 * Get all PHP files in directory
 */
function get_php_files($directory) {
    $php_files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $php_files[] = $file->getPathname();
        }
    }
    
    return $php_files;
}

// Get all PHP files
$php_files = get_php_files($plugin_dir);
$total_files = count($php_files);
$valid_files = 0;
$invalid_files = 0;

echo "Found {$total_files} PHP files to check\n\n";

// Check each file
foreach ($php_files as $file) {
    $relative_path = str_replace($plugin_dir . DIRECTORY_SEPARATOR, '', $file);
    echo "Checking: {$relative_path} ... ";
    
    $result = check_file_syntax($file);
    
    if ($result['valid']) {
        echo "✅ OK\n";
        $valid_files++;
    } else {
        echo "❌ SYNTAX ERROR\n";
        echo "   Error: " . trim($result['output']) . "\n";
        $invalid_files++;
    }
}

echo "\n=== Syntax Check Results ===\n";
echo "Total files checked: {$total_files}\n";
echo "Valid files: {$valid_files}\n";
echo "Files with syntax errors: {$invalid_files}\n";

if ($invalid_files === 0) {
    echo "\n✅ All files have valid PHP syntax!\n";
    exit(0);
} else {
    echo "\n❌ Found {$invalid_files} files with syntax errors.\n";
    echo "Please fix the syntax errors before using the plugin.\n";
    exit(1);
}
