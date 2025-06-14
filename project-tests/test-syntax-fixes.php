<?php
/**
 * Syntax Fixes Tester for ChronoForge Plugin
 *
 * This script tests specific syntax fixes that were applied to the ChronoForge plugin
 * to ensure they resolve the original issues without introducing new problems.
 *
 * @package ChronoForge
 * @version 1.0.0
 */

// Prevent direct access in WordPress context
if (defined('ABSPATH')) {
    wp_die('This script should not be run in WordPress context');
}

echo "=== ChronoForge Syntax Fixes Tester ===\n\n";

// Get the plugin directory
$plugin_dir = dirname(__DIR__) . '/chrono-forge';

if (!is_dir($plugin_dir)) {
    echo "❌ ChronoForge plugin directory not found at: {$plugin_dir}\n";
    exit(1);
}

echo "🔍 Testing syntax fixes in: {$plugin_dir}\n\n";

/**
 * Test specific file for syntax errors
 */
function test_file_syntax($file_path, $file_name) {
    echo "Testing {$file_name}:\n";
    
    if (!file_exists($file_path)) {
        echo "   ❌ File not found\n";
        return false;
    }
    
    // Test syntax with php -l
    $output = [];
    $return_code = 0;
    exec("php -l " . escapeshellarg($file_path) . " 2>&1", $output, $return_code);
    
    if ($return_code === 0) {
        echo "   ✅ Syntax OK\n";
        return true;
    } else {
        echo "   ❌ Syntax Error: " . implode("\n", $output) . "\n";
        return false;
    }
}

/**
 * Test specific fixes in Container.php
 */
function test_container_fixes($plugin_dir) {
    echo "1. Testing Container.php fixes:\n";
    
    $container_file = $plugin_dir . '/src/Core/Container.php';
    if (!file_exists($container_file)) {
        echo "   ❌ Container.php not found\n";
        return false;
    }
    
    $content = file_get_contents($container_file);
    
    // Check for ArrayAccess implementation
    if (strpos($content, 'implements \ArrayAccess') !== false) {
        echo "   ✅ ArrayAccess interface implemented\n";
    } else {
        echo "   ❌ ArrayAccess interface missing\n";
        return false;
    }
    
    // Check for required ArrayAccess methods
    $required_methods = ['offsetGet', 'offsetSet', 'offsetExists', 'offsetUnset'];
    foreach ($required_methods as $method) {
        if (strpos($content, "function {$method}") !== false) {
            echo "   ✅ Method {$method} implemented\n";
        } else {
            echo "   ❌ Method {$method} missing\n";
            return false;
        }
    }
    
    return test_file_syntax($container_file, 'Container.php');
}

/**
 * Test specific fixes in Plugin.php
 */
function test_plugin_fixes($plugin_dir) {
    echo "\n2. Testing Plugin.php fixes:\n";
    
    $plugin_file = $plugin_dir . '/src/Core/Plugin.php';
    if (!file_exists($plugin_file)) {
        echo "   ❌ Plugin.php not found\n";
        return false;
    }
    
    $content = file_get_contents($plugin_file);
    
    // Check for WordPress function existence checks
    if (strpos($content, 'function_exists(\'add_action\')') !== false) {
        echo "   ✅ WordPress function checks added\n";
    } else {
        echo "   ❌ WordPress function checks missing\n";
        return false;
    }
    
    // Check for improved error handling
    if (strpos($content, 'function_exists(\'chrono_forge_safe_log\')') !== false) {
        echo "   ✅ Safe logging checks added\n";
    } else {
        echo "   ❌ Safe logging checks missing\n";
        return false;
    }
    
    return test_file_syntax($plugin_file, 'Plugin.php');
}

/**
 * Test specific fixes in main plugin file
 */
function test_main_file_fixes($plugin_dir) {
    echo "\n3. Testing chrono-forge.php fixes:\n";
    
    $main_file = $plugin_dir . '/chrono-forge.php';
    if (!file_exists($main_file)) {
        echo "   ❌ chrono-forge.php not found\n";
        return false;
    }
    
    $content = file_get_contents($main_file);
    
    // Check for global variable fixes
    if (strpos($content, '$GLOBALS[\'chrono_forge_modern_architecture\']') !== false) {
        echo "   ✅ Global variable fixes applied\n";
    } else {
        echo "   ❌ Global variable fixes missing\n";
        return false;
    }
    
    // Check for improved error handling
    if (strpos($content, 'isset($GLOBALS[\'chrono_forge_modern_architecture\'])') !== false) {
        echo "   ✅ Global variable existence checks added\n";
    } else {
        echo "   ❌ Global variable existence checks missing\n";
        return false;
    }
    
    return test_file_syntax($main_file, 'chrono-forge.php');
}

/**
 * Test specific fixes in functions.php
 */
function test_functions_fixes($plugin_dir) {
    echo "\n4. Testing functions.php fixes:\n";
    
    $functions_file = $plugin_dir . '/src/functions.php';
    if (!file_exists($functions_file)) {
        echo "   ❌ functions.php not found\n";
        return false;
    }
    
    $content = file_get_contents($functions_file);
    
    // Check for function_exists checks
    if (strpos($content, 'if (!function_exists(\'chrono_forge_plugin\'))') !== false) {
        echo "   ✅ Function existence checks added\n";
    } else {
        echo "   ❌ Function existence checks missing\n";
        return false;
    }
    
    // Check for improved error handling
    if (strpos($content, 'try {') !== false && strpos($content, 'catch (\Exception $e)') !== false) {
        echo "   ✅ Exception handling added\n";
    } else {
        echo "   ❌ Exception handling missing\n";
        return false;
    }
    
    return test_file_syntax($functions_file, 'functions.php');
}

/**
 * Test autoloader fixes
 */
function test_autoloader_fixes($plugin_dir) {
    echo "\n5. Testing autoloader fixes:\n";
    
    $autoloader_file = $plugin_dir . '/vendor/autoload.php';
    if (!file_exists($autoloader_file)) {
        echo "   ❌ autoload.php not found\n";
        return false;
    }
    
    $content = file_get_contents($autoloader_file);
    
    // Check for error handling in autoloader
    if (strpos($content, 'try {') !== false && strpos($content, 'catch (\Exception $e)') !== false) {
        echo "   ✅ Exception handling added to autoloader\n";
    } else {
        echo "   ❌ Exception handling missing in autoloader\n";
        return false;
    }
    
    return test_file_syntax($autoloader_file, 'autoload.php');
}

/**
 * Test that all core files have valid syntax
 */
function test_all_core_syntax($plugin_dir) {
    echo "\n6. Testing all core files syntax:\n";
    
    $core_files = [
        'src/Core/Plugin.php',
        'src/Core/Container.php',
        'src/Core/ServiceProvider.php',
        'src/Core/Activator.php',
        'src/Core/Deactivator.php',
        'src/functions.php',
        'vendor/autoload.php',
        'chrono-forge.php'
    ];
    
    $all_valid = true;
    
    foreach ($core_files as $file) {
        $file_path = $plugin_dir . '/' . $file;
        if (file_exists($file_path)) {
            if (!test_file_syntax($file_path, basename($file))) {
                $all_valid = false;
            }
        } else {
            echo "   ❌ File {$file} not found\n";
            $all_valid = false;
        }
    }
    
    return $all_valid;
}

// Run all syntax fix tests
$tests = [
    'container_fixes' => test_container_fixes($plugin_dir),
    'plugin_fixes' => test_plugin_fixes($plugin_dir),
    'main_file_fixes' => test_main_file_fixes($plugin_dir),
    'functions_fixes' => test_functions_fixes($plugin_dir),
    'autoloader_fixes' => test_autoloader_fixes($plugin_dir),
    'all_core_syntax' => test_all_core_syntax($plugin_dir)
];

// Summary
echo "\n=== Syntax Fixes Test Results ===\n";
$passed = 0;
$total = count($tests);

foreach ($tests as $test_name => $result) {
    $status = $result ? '✅ PASS' : '❌ FAIL';
    echo "{$status} {$test_name}\n";
    if ($result) $passed++;
}

echo "\nPassed: {$passed}/{$total}\n";

if ($passed === $total) {
    echo "\n🎉 All syntax fixes are working correctly!\n";
    echo "The plugin should now load without critical errors.\n";
    exit(0);
} else {
    echo "\n⚠️  Some syntax fixes failed. Please review the issues above.\n";
    exit(1);
}
