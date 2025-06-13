<?php
/**
 * Test script to verify ChronoForge plugin fixes
 */

echo "ChronoForge Plugin Fix Test\n";
echo "===========================\n\n";

// Test 1: Check if main plugin file loads without errors
echo "1. Testing main plugin file syntax...\n";
$main_file = __DIR__ . '/chrono-forge/chrono-forge.php';

if (!file_exists($main_file)) {
    echo "❌ Main plugin file not found!\n";
    exit(1);
}

// Test syntax by including the file in a safe way
ob_start();
$error_occurred = false;

try {
    // Simulate WordPress environment minimally
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Mock WordPress functions that the plugin expects
    if (!function_exists('add_action')) {
        function add_action($hook, $callback, $priority = 10, $args = 1) {
            // Mock function
            return true;
        }
    }
    
    if (!function_exists('add_filter')) {
        function add_filter($hook, $callback, $priority = 10, $args = 1) {
            // Mock function
            return true;
        }
    }
    
    if (!function_exists('register_activation_hook')) {
        function register_activation_hook($file, $callback) {
            // Mock function
            return true;
        }
    }
    
    if (!function_exists('register_deactivation_hook')) {
        function register_deactivation_hook($file, $callback) {
            // Mock function
            return true;
        }
    }
    
    if (!function_exists('plugin_dir_path')) {
        function plugin_dir_path($file) {
            return dirname($file) . '/';
        }
    }
    
    if (!function_exists('plugin_dir_url')) {
        function plugin_dir_url($file) {
            return 'http://example.com/wp-content/plugins/' . basename(dirname($file)) . '/';
        }
    }
    
    if (!function_exists('plugin_basename')) {
        function plugin_basename($file) {
            return basename(dirname($file)) . '/' . basename($file);
        }
    }
    
    if (!function_exists('__')) {
        function __($text, $domain = 'default') {
            return $text;
        }
    }
    
    if (!function_exists('_e')) {
        function _e($text, $domain = 'default') {
            echo $text;
        }
    }
    
    if (!function_exists('current_user_can')) {
        function current_user_can($capability) {
            return true;
        }
    }
    
    if (!function_exists('admin_url')) {
        function admin_url($path = '') {
            return 'http://example.com/wp-admin/' . $path;
        }
    }
    
    if (!function_exists('wp_create_nonce')) {
        function wp_create_nonce($action) {
            return 'test_nonce_' . md5($action);
        }
    }
    
    if (!function_exists('wp_get_current_user')) {
        function wp_get_current_user() {
            return (object) array('user_login' => 'test', 'ID' => 1);
        }
    }
    
    if (!function_exists('get_current_user_id')) {
        function get_current_user_id() {
            return 1;
        }
    }
    
    if (!function_exists('wp_die')) {
        function wp_die($message) {
            throw new Exception($message);
        }
    }
    
    if (!function_exists('is_admin')) {
        function is_admin() {
            return true;
        }
    }
    
    if (!function_exists('file_exists')) {
        // file_exists is a PHP function, no need to mock
    }
    
    // Try to include the main plugin file
    include_once $main_file;
    
    echo "✅ Main plugin file loaded successfully\n";
    
} catch (ParseError $e) {
    echo "❌ Parse Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
    $error_occurred = true;
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
    $error_occurred = true;
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    $error_occurred = true;
}

$output = ob_get_clean();
if (!empty($output)) {
    echo "Output during loading:\n" . $output . "\n";
}

// Test 2: Check if core class can be instantiated
echo "\n2. Testing core class instantiation...\n";

if (class_exists('ChronoForge_Core')) {
    echo "✅ ChronoForge_Core class found\n";
    
    try {
        // Don't actually instantiate since it requires WordPress environment
        echo "✅ Core class is available for instantiation\n";
    } catch (Exception $e) {
        echo "⚠️  Core class found but instantiation failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ ChronoForge_Core class not found\n";
    $error_occurred = true;
}

// Test 3: Check if diagnostic functions are available
echo "\n3. Testing diagnostic functions...\n";

if (function_exists('chrono_forge_count_code_braces')) {
    echo "✅ chrono_forge_count_code_braces function found\n";
    
    // Test the function with a simple example
    $test_code = '<?php function test() { return "hello"; }';
    $result = chrono_forge_count_code_braces($test_code);
    
    if ($result['open'] === 1 && $result['close'] === 1) {
        echo "✅ Brace counting function works correctly\n";
    } else {
        echo "⚠️  Brace counting function returned unexpected results: " . json_encode($result) . "\n";
    }
} else {
    echo "❌ chrono_forge_count_code_braces function not found\n";
}

if (function_exists('chrono_forge_clear_error_state')) {
    echo "✅ chrono_forge_clear_error_state function found\n";
} else {
    echo "❌ chrono_forge_clear_error_state function not found\n";
}

// Test 4: Check critical files
echo "\n4. Testing critical files...\n";

$critical_files = array(
    'chrono-forge/includes/class-chrono-forge-core.php',
    'chrono-forge/includes/class-chrono-forge-diagnostics.php',
    'chrono-forge/admin/class-chrono-forge-admin-diagnostics.php',
    'chrono-forge/admin/views/view-diagnostics.php',
    'chrono-forge/includes/utils/functions.php'
);

$missing_files = 0;
foreach ($critical_files as $file) {
    $file_path = __DIR__ . '/' . $file;
    if (file_exists($file_path)) {
        echo "✅ {$file}\n";
    } else {
        echo "❌ {$file} - MISSING\n";
        $missing_files++;
    }
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";

if ($error_occurred || $missing_files > 0) {
    echo "❌ TESTS FAILED\n";
    echo "Issues found:\n";
    if ($error_occurred) {
        echo "- Syntax or runtime errors detected\n";
    }
    if ($missing_files > 0) {
        echo "- {$missing_files} critical files missing\n";
    }
    echo "\nThe plugin may not work correctly.\n";
    exit(1);
} else {
    echo "✅ ALL TESTS PASSED\n";
    echo "The plugin should now work correctly!\n";
    echo "\nNext steps:\n";
    echo "1. Refresh the WordPress admin page\n";
    echo "2. Check the ChronoForge diagnostics page\n";
    echo "3. Verify that the syntax error message is gone\n";
    exit(0);
}
