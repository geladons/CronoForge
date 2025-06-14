<?php
/**
 * Comprehensive Syntax Test for ChronoForge Plugin
 *
 * This script performs comprehensive syntax and basic functionality testing
 * on all ChronoForge plugin files.
 *
 * @package ChronoForge
 * @version 1.0.0
 */

// Prevent direct access in WordPress context
if (defined('ABSPATH')) {
    wp_die('This script should not be run in WordPress context');
}

echo "=== ChronoForge Comprehensive Syntax Test ===\n\n";

// Get the plugin directory
$plugin_dir = dirname(__DIR__) . '/chrono-forge';

if (!is_dir($plugin_dir)) {
    echo "❌ ChronoForge plugin directory not found at: {$plugin_dir}\n";
    exit(1);
}

echo "📁 Testing plugin directory: {$plugin_dir}\n\n";

/**
 * Test file syntax using php -l
 */
function test_syntax($file_path) {
    $output = [];
    $return_code = 0;
    
    exec("php -l " . escapeshellarg($file_path) . " 2>&1", $output, $return_code);
    
    return [
        'valid' => $return_code === 0,
        'output' => implode("\n", $output)
    ];
}

/**
 * Test file for common issues
 */
function test_common_issues($file_path) {
    $content = file_get_contents($file_path);
    $issues = [];
    
    // Check for PHP opening tag
    if (strpos($content, '<?php') === false) {
        $issues[] = 'Missing PHP opening tag';
    }
    
    // Check for unmatched braces (basic check)
    $open_braces = substr_count($content, '{');
    $close_braces = substr_count($content, '}');
    if (abs($open_braces - $close_braces) > 1) {
        $issues[] = 'Possible unmatched braces';
    }
    
    // Check for unmatched parentheses (basic check)
    $open_parens = substr_count($content, '(');
    $close_parens = substr_count($content, ')');
    if (abs($open_parens - $close_parens) > 1) {
        $issues[] = 'Possible unmatched parentheses';
    }
    
    // Check for common syntax patterns
    if (preg_match('/\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*[^;]*$/', $content)) {
        $issues[] = 'Possible missing semicolon';
    }
    
    return $issues;
}

/**
 * Get all PHP files recursively
 */
function get_all_php_files($directory) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    
    sort($files);
    return $files;
}

// Get all PHP files
$php_files = get_all_php_files($plugin_dir);
$total_files = count($php_files);

echo "Found {$total_files} PHP files to test\n\n";

// Test results
$results = [
    'total' => $total_files,
    'syntax_valid' => 0,
    'syntax_invalid' => 0,
    'has_issues' => 0,
    'clean' => 0,
    'errors' => []
];

// Test each file
foreach ($php_files as $file) {
    $relative_path = str_replace($plugin_dir . DIRECTORY_SEPARATOR, '', $file);
    echo "Testing: {$relative_path}\n";
    
    // Test syntax
    $syntax_result = test_syntax($file);
    if ($syntax_result['valid']) {
        echo "  ✅ Syntax: OK\n";
        $results['syntax_valid']++;
    } else {
        echo "  ❌ Syntax: ERROR\n";
        echo "     " . trim($syntax_result['output']) . "\n";
        $results['syntax_invalid']++;
        $results['errors'][] = [
            'file' => $relative_path,
            'type' => 'syntax',
            'message' => trim($syntax_result['output'])
        ];
    }
    
    // Test common issues (only if syntax is valid)
    if ($syntax_result['valid']) {
        $issues = test_common_issues($file);
        if (empty($issues)) {
            echo "  ✅ Issues: None\n";
            $results['clean']++;
        } else {
            echo "  ⚠️  Issues: " . implode(', ', $issues) . "\n";
            $results['has_issues']++;
            foreach ($issues as $issue) {
                $results['errors'][] = [
                    'file' => $relative_path,
                    'type' => 'warning',
                    'message' => $issue
                ];
            }
        }
    }
    
    echo "\n";
}

// Summary
echo "=== Test Summary ===\n";
echo "Total files tested: {$results['total']}\n";
echo "Syntax valid: {$results['syntax_valid']}\n";
echo "Syntax errors: {$results['syntax_invalid']}\n";
echo "Files with warnings: {$results['has_issues']}\n";
echo "Clean files: {$results['clean']}\n\n";

// Detailed errors
if (!empty($results['errors'])) {
    echo "=== Detailed Issues ===\n";
    foreach ($results['errors'] as $error) {
        $type_icon = $error['type'] === 'syntax' ? '❌' : '⚠️';
        echo "{$type_icon} {$error['file']}: {$error['message']}\n";
    }
    echo "\n";
}

// Final status
if ($results['syntax_invalid'] === 0) {
    echo "✅ All files have valid PHP syntax!\n";
    if ($results['has_issues'] === 0) {
        echo "✅ No issues found - plugin is ready for testing!\n";
        exit(0);
    } else {
        echo "⚠️  Some warnings found, but plugin should work.\n";
        exit(0);
    }
} else {
    echo "❌ Found {$results['syntax_invalid']} files with syntax errors.\n";
    echo "Please fix syntax errors before using the plugin.\n";
    exit(1);
}
