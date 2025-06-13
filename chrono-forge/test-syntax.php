<?php
/**
 * Simple syntax test for ChronoForge files
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // For testing purposes, define ABSPATH
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Define plugin constants for testing
define('CHRONO_FORGE_VERSION', '1.0.0');
define('CHRONO_FORGE_PLUGIN_FILE', __FILE__);
define('CHRONO_FORGE_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CHRONO_FORGE_PLUGIN_URL', 'http://localhost/chrono-forge/');
define('CHRONO_FORGE_PLUGIN_BASENAME', 'chrono-forge/chrono-forge.php');

// Mock WordPress functions for testing
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1) { return true; }
}
if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $args = 1) { return true; }
}
if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) { return true; }
}
if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) { return true; }
}
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) { return dirname($file) . '/'; }
}
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) { return 'http://localhost/chrono-forge/'; }
}
if (!function_exists('plugin_basename')) {
    function plugin_basename($file) { return 'chrono-forge/chrono-forge.php'; }
}
if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}
if (!function_exists('esc_html')) {
    function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('esc_url')) {
    function esc_url($url) { return $url; }
}
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) { return 'test_nonce'; }
}
if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) { return true; }
}
if (!function_exists('current_user_can')) {
    function current_user_can($capability) { return true; }
}
if (!function_exists('admin_url')) {
    function admin_url($path = '') { return 'http://localhost/wp-admin/' . $path; }
}
if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show) { return 'Test Site'; }
}
if (!function_exists('current_time')) {
    function current_time($type) { return date('Y-m-d H:i:s'); }
}
if (!function_exists('error_log')) {
    function error_log($message) { echo "LOG: " . $message . "\n"; }
}

// Mock safe log function
if (!function_exists('chrono_forge_safe_log')) {
    function chrono_forge_safe_log($message, $level = 'info') {
        echo "SAFE_LOG [{$level}]: {$message}\n";
    }
}

// Files to test
$files_to_test = array(
    'chrono-forge.php',
    'includes/class-chrono-forge-database.php',
    'admin/class-chrono-forge-admin-ajax.php',
    'public/class-chrono-forge-public.php',
    'includes/class-chrono-forge-core.php',
    'includes/class-chrono-forge-activator.php',
    'includes/class-chrono-forge-shortcodes.php',
    'includes/utils/functions.php',
    'includes/class-chrono-forge-diagnostics.php'
);

echo "Testing ChronoForge PHP files for syntax errors...\n\n";

$errors_found = 0;
$files_tested = 0;

foreach ($files_to_test as $file) {
    $file_path = __DIR__ . '/' . $file;
    
    if (!file_exists($file_path)) {
        echo "❌ MISSING: {$file}\n";
        $errors_found++;
        continue;
    }
    
    echo "Testing: {$file}... ";
    
    // Basic syntax check by trying to parse the file
    $content = file_get_contents($file_path);
    if ($content === false) {
        echo "❌ CANNOT READ\n";
        $errors_found++;
        continue;
    }
    
    // Check for basic syntax issues
    $syntax_errors = array();
    
    // Check brace matching
    $open_braces = substr_count($content, '{');
    $close_braces = substr_count($content, '}');
    if ($open_braces !== $close_braces) {
        $syntax_errors[] = "Unmatched braces (open: {$open_braces}, close: {$close_braces})";
    }
    
    // Check parentheses matching
    $open_parens = substr_count($content, '(');
    $close_parens = substr_count($content, ')');
    if ($open_parens !== $close_parens) {
        $syntax_errors[] = "Unmatched parentheses (open: {$open_parens}, close: {$close_parens})";
    }
    
    // Check for unclosed strings (basic check)
    $lines = explode("\n", $content);
    foreach ($lines as $line_num => $line) {
        $line_num++; // 1-based
        
        // Skip comments and empty lines
        $trimmed = trim($line);
        if (empty($trimmed) || strpos($trimmed, '//') === 0 || strpos($trimmed, '#') === 0 || strpos($trimmed, '/*') === 0) {
            continue;
        }
        
        // Basic string quote check
        $single_quotes = substr_count($line, "'") - substr_count($line, "\\'");
        $double_quotes = substr_count($line, '"') - substr_count($line, '\\"');
        
        if ($single_quotes % 2 !== 0) {
            $syntax_errors[] = "Possible unclosed single quote at line {$line_num}";
        }
        
        if ($double_quotes % 2 !== 0) {
            $syntax_errors[] = "Possible unclosed double quote at line {$line_num}";
        }
    }
    
    if (empty($syntax_errors)) {
        echo "✅ OK\n";
    } else {
        echo "❌ ERRORS:\n";
        foreach ($syntax_errors as $error) {
            echo "   - {$error}\n";
        }
        $errors_found++;
    }
    
    $files_tested++;
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "SUMMARY:\n";
echo "Files tested: {$files_tested}\n";
echo "Errors found: {$errors_found}\n";

if ($errors_found === 0) {
    echo "✅ All files passed basic syntax checks!\n";
} else {
    echo "❌ {$errors_found} files have syntax issues that need to be fixed.\n";
}

echo "\nTest completed.\n";
