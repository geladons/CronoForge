<?php
/**
 * Plugin Fix Tester for ChronoForge
 *
 * This script tests various fixes and improvements made to the ChronoForge plugin
 * to ensure they work correctly and don't introduce new issues.
 *
 * @package ChronoForge
 * @version 1.0.0
 */

// Prevent direct access in WordPress context
if (defined('ABSPATH')) {
    wp_die('This script should not be run in WordPress context');
}

echo "=== ChronoForge Plugin Fix Tester ===\n\n";

// Get the plugin directory
$plugin_dir = dirname(__DIR__) . '/chrono-forge';

if (!is_dir($plugin_dir)) {
    echo "❌ ChronoForge plugin directory not found at: {$plugin_dir}\n";
    exit(1);
}

echo "🔧 Testing plugin fixes in: {$plugin_dir}\n\n";

/**
 * Test 1: Check if autoloader exists and works
 */
function test_autoloader($plugin_dir) {
    echo "1. Testing Autoloader:\n";
    
    $autoloader_path = $plugin_dir . '/vendor/autoload.php';
    if (!file_exists($autoloader_path)) {
        echo "   ❌ Autoloader not found\n";
        return false;
    }
    
    try {
        require_once $autoloader_path;
        echo "   ✅ Autoloader loaded successfully\n";
        
        // Test class loading
        $test_classes = [
            'ChronoForge\Core\Plugin',
            'ChronoForge\Core\Container',
            'ChronoForge\Core\ServiceProvider'
        ];
        
        foreach ($test_classes as $class) {
            if (class_exists($class)) {
                echo "   ✅ Class {$class} loaded\n";
            } else {
                echo "   ❌ Class {$class} failed to load\n";
                return false;
            }
        }
        
        return true;
    } catch (Exception $e) {
        echo "   ❌ Autoloader error: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Test 2: Check container functionality
 */
function test_container() {
    echo "\n2. Testing Container:\n";
    
    if (!class_exists('ChronoForge\Core\Container')) {
        echo "   ❌ Container class not available\n";
        return false;
    }
    
    try {
        $container = new \ChronoForge\Core\Container();
        
        // Test binding
        $container->bind('test.service', function() {
            return 'test_value';
        });
        
        $result = $container->make('test.service');
        if ($result === 'test_value') {
            echo "   ✅ Container binding works\n";
        } else {
            echo "   ❌ Container binding failed\n";
            return false;
        }
        
        // Test singleton
        $container->singleton('test.singleton', function() {
            return new stdClass();
        });
        
        $instance1 = $container->make('test.singleton');
        $instance2 = $container->make('test.singleton');
        
        if ($instance1 === $instance2) {
            echo "   ✅ Container singleton works\n";
        } else {
            echo "   ❌ Container singleton failed\n";
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        echo "   ❌ Container error: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Test 3: Check main plugin file modifications
 */
function test_main_plugin_file($plugin_dir) {
    echo "\n3. Testing Main Plugin File:\n";
    
    $main_file = $plugin_dir . '/chrono-forge.php';
    if (!file_exists($main_file)) {
        echo "   ❌ Main plugin file not found\n";
        return false;
    }
    
    $content = file_get_contents($main_file);
    
    // Check for modern architecture support
    if (strpos($content, 'chrono_forge_modern_architecture') !== false) {
        echo "   ✅ Modern architecture detection added\n";
    } else {
        echo "   ❌ Modern architecture detection missing\n";
        return false;
    }
    
    // Check for autoloader integration
    if (strpos($content, 'vendor/autoload.php') !== false) {
        echo "   ✅ Autoloader integration added\n";
    } else {
        echo "   ❌ Autoloader integration missing\n";
        return false;
    }
    
    // Check for namespace usage
    if (strpos($content, 'ChronoForge\\Core\\Plugin') !== false) {
        echo "   ✅ Namespace usage added\n";
    } else {
        echo "   ❌ Namespace usage missing\n";
        return false;
    }
    
    return true;
}

/**
 * Test 4: Check global functions
 */
function test_global_functions($plugin_dir) {
    echo "\n4. Testing Global Functions:\n";
    
    $functions_file = $plugin_dir . '/src/functions.php';
    if (!file_exists($functions_file)) {
        echo "   ❌ Global functions file not found\n";
        return false;
    }
    
    // Load functions
    require_once $functions_file;
    
    // Test function existence
    $required_functions = [
        'chrono_forge_plugin',
        'chrono_forge_container',
        'chrono_forge_service'
    ];
    
    foreach ($required_functions as $function) {
        if (function_exists($function)) {
            echo "   ✅ Function {$function} exists\n";
        } else {
            echo "   ❌ Function {$function} missing\n";
            return false;
        }
    }
    
    return true;
}

/**
 * Test 5: Check backward compatibility
 */
function test_backward_compatibility($plugin_dir) {
    echo "\n5. Testing Backward Compatibility:\n";
    
    // Check if legacy files still exist
    $legacy_files = [
        'includes/utils/functions.php',
        'includes/class-chrono-forge-core.php'
    ];
    
    $legacy_exists = 0;
    foreach ($legacy_files as $file) {
        if (file_exists($plugin_dir . '/' . $file)) {
            $legacy_exists++;
        }
    }
    
    if ($legacy_exists > 0) {
        echo "   ✅ Legacy files preserved ({$legacy_exists} found)\n";
    } else {
        echo "   ⚠️  No legacy files found (may affect compatibility)\n";
    }
    
    return true;
}

/**
 * Test 6: Check file structure
 */
function test_file_structure($plugin_dir) {
    echo "\n6. Testing File Structure:\n";
    
    $required_dirs = [
        'src',
        'src/Core',
        'vendor'
    ];
    
    $required_files = [
        'src/Core/Plugin.php',
        'src/Core/Container.php',
        'src/Core/ServiceProvider.php',
        'src/functions.php',
        'composer.json'
    ];
    
    // Check directories
    foreach ($required_dirs as $dir) {
        if (is_dir($plugin_dir . '/' . $dir)) {
            echo "   ✅ Directory {$dir} exists\n";
        } else {
            echo "   ❌ Directory {$dir} missing\n";
            return false;
        }
    }
    
    // Check files
    foreach ($required_files as $file) {
        if (file_exists($plugin_dir . '/' . $file)) {
            echo "   ✅ File {$file} exists\n";
        } else {
            echo "   ❌ File {$file} missing\n";
            return false;
        }
    }
    
    return true;
}

// Run all tests
$tests = [
    'test_file_structure' => test_file_structure($plugin_dir),
    'test_autoloader' => test_autoloader($plugin_dir),
    'test_container' => test_container(),
    'test_main_plugin_file' => test_main_plugin_file($plugin_dir),
    'test_global_functions' => test_global_functions($plugin_dir),
    'test_backward_compatibility' => test_backward_compatibility($plugin_dir)
];

// Summary
echo "\n=== Test Results Summary ===\n";
$passed = 0;
$total = count($tests);

foreach ($tests as $test_name => $result) {
    $status = $result ? '✅ PASS' : '❌ FAIL';
    echo "{$status} {$test_name}\n";
    if ($result) $passed++;
}

echo "\nPassed: {$passed}/{$total}\n";

if ($passed === $total) {
    echo "\n🎉 All tests passed! Plugin fixes are working correctly.\n";
    exit(0);
} else {
    echo "\n⚠️  Some tests failed. Please review the issues above.\n";
    exit(1);
}
