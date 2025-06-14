<?php
/**
 * ChronoForge Plugin Error Checker
 * 
 * Comprehensive script to check for errors in the plugin
 */

echo "ChronoForge Plugin Error Checker\n";
echo "=================================\n\n";

$plugin_dir = __DIR__ . '/chrono-forge';
$errors_found = false;

if (!is_dir($plugin_dir)) {
    echo "❌ Plugin directory not found: {$plugin_dir}\n";
    exit(1);
}

/**
 * Check PHP syntax of a file
 */
function check_php_syntax($file) {
    $output = [];
    $return_code = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return_code);
    
    return [
        'valid' => $return_code === 0,
        'output' => implode("\n", $output)
    ];
}

/**
 * Get all PHP files recursively
 */
function get_php_files($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

/**
 * Check for common WordPress plugin issues
 */
function check_wordpress_issues($file) {
    $content = file_get_contents($file);
    $issues = [];
    
    // Check for direct access protection
    if (!preg_match('/defined\s*\(\s*[\'"]ABSPATH[\'"]\s*\)/', $content)) {
        $issues[] = "Missing ABSPATH check";
    }
    
    // Check for PHP opening tag
    if (!preg_match('/^<\?php/', $content)) {
        $issues[] = "Missing or incorrect PHP opening tag";
    }
    
    // Check for output before headers (common cause of activation errors)
    if (preg_match('/^[\s\S]*?<\?php\s+(.+?)(?:class|function|namespace|\?>|$)/s', $content, $matches)) {
        $before_code = trim($matches[1] ?? '');
        if ($before_code && !preg_match('/^(\/\*[\s\S]*?\*\/|\/\/.*|#.*|\s)*$/', $before_code)) {
            // Check if there's any output-generating code
            if (preg_match('/echo|print|printf|var_dump|print_r|\?>/', $before_code)) {
                $issues[] = "Potential output before headers";
            }
        }
    }
    
    return $issues;
}

// Test 1: Check main plugin file
echo "1. Checking Main Plugin File:\n";
$main_file = $plugin_dir . '/chrono-forge.php';

if (file_exists($main_file)) {
    echo "   📁 File: " . basename($main_file) . "\n";
    
    $syntax_check = check_php_syntax($main_file);
    if ($syntax_check['valid']) {
        echo "   ✅ PHP syntax: OK\n";
    } else {
        echo "   ❌ PHP syntax: ERROR\n";
        echo "   " . str_replace("\n", "\n   ", $syntax_check['output']) . "\n";
        $errors_found = true;
    }
    
    $wp_issues = check_wordpress_issues($main_file);
    if (empty($wp_issues)) {
        echo "   ✅ WordPress compatibility: OK\n";
    } else {
        echo "   ⚠️  WordPress issues:\n";
        foreach ($wp_issues as $issue) {
            echo "      - {$issue}\n";
        }
    }
    
    // Check plugin header
    $content = file_get_contents($main_file);
    if (preg_match('/Plugin Name:\s*(.+)/i', $content)) {
        echo "   ✅ Plugin header: Found\n";
    } else {
        echo "   ❌ Plugin header: Missing\n";
        $errors_found = true;
    }
    
} else {
    echo "   ❌ Main plugin file not found\n";
    $errors_found = true;
}

echo "\n";

// Test 2: Check all PHP files
echo "2. Checking All PHP Files:\n";

$php_files = get_php_files($plugin_dir);
$total_files = count($php_files);
$valid_files = 0;
$files_with_issues = [];

echo "   Found {$total_files} PHP files\n\n";

foreach ($php_files as $file) {
    $relative_path = str_replace($plugin_dir . '/', '', $file);
    echo "   📁 Checking: {$relative_path}\n";
    
    $syntax_check = check_php_syntax($file);
    if ($syntax_check['valid']) {
        echo "      ✅ Syntax: OK\n";
        $valid_files++;
    } else {
        echo "      ❌ Syntax: ERROR\n";
        echo "      " . str_replace("\n", "\n      ", $syntax_check['output']) . "\n";
        $files_with_issues[] = $relative_path;
        $errors_found = true;
    }
    
    $wp_issues = check_wordpress_issues($file);
    if (!empty($wp_issues)) {
        echo "      ⚠️  Issues:\n";
        foreach ($wp_issues as $issue) {
            echo "         - {$issue}\n";
        }
    }
    
    echo "\n";
}

echo "   Summary: {$valid_files}/{$total_files} files have valid syntax\n\n";

// Test 3: Check for missing dependencies
echo "3. Checking Dependencies:\n";

$required_files = [
    'includes/functions.php',
    'includes/container.php',
    'includes/Infrastructure/Container.php',
    'includes/Infrastructure/Database/DatabaseManager.php',
    'includes/Admin/MenuManager.php'
];

foreach ($required_files as $file) {
    $full_path = $plugin_dir . '/' . $file;
    if (file_exists($full_path)) {
        echo "   ✅ {$file}\n";
    } else {
        echo "   ❌ Missing: {$file}\n";
        $errors_found = true;
    }
}

echo "\n";

// Test 4: Check composer autoloader
echo "4. Checking Composer Setup:\n";

$composer_json = $plugin_dir . '/composer.json';
$autoloader = $plugin_dir . '/vendor/autoload.php';

if (file_exists($composer_json)) {
    echo "   ✅ composer.json exists\n";
    
    $composer_data = json_decode(file_get_contents($composer_json), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "   ✅ composer.json is valid JSON\n";
        
        if (isset($composer_data['autoload']['psr-4']['ChronoForge\\'])) {
            echo "   ✅ PSR-4 autoloading configured\n";
        } else {
            echo "   ❌ PSR-4 autoloading not configured\n";
            $errors_found = true;
        }
    } else {
        echo "   ❌ composer.json has invalid JSON\n";
        $errors_found = true;
    }
} else {
    echo "   ❌ composer.json missing\n";
    $errors_found = true;
}

if (file_exists($autoloader)) {
    echo "   ✅ Composer autoloader exists\n";
} else {
    echo "   ⚠️  Composer autoloader missing (run 'composer install')\n";
}

echo "\n";

// Test 5: Try to load the plugin (simulation)
echo "5. Simulating Plugin Load:\n";

// Mock WordPress functions for testing
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1) {
        echo "   📝 add_action called: {$hook}\n";
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {
        echo "   📝 register_activation_hook called\n";
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {
        echo "   📝 register_deactivation_hook called\n";
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return basename(dirname($file)) . '/' . basename($file);
    }
}

if (!defined('ABSPATH')) {
    define('ABSPATH', '/fake/wordpress/path/');
}

try {
    echo "   🔄 Attempting to load main plugin file...\n";
    
    // Capture any output
    ob_start();
    $error_occurred = false;
    
    try {
        include $main_file;
        echo "   ✅ Plugin loaded successfully\n";
    } catch (ParseError $e) {
        $error_occurred = true;
        echo "   ❌ Parse Error: " . $e->getMessage() . "\n";
        echo "   📍 File: " . $e->getFile() . "\n";
        echo "   📍 Line: " . $e->getLine() . "\n";
        $errors_found = true;
    } catch (Error $e) {
        $error_occurred = true;
        echo "   ❌ Fatal Error: " . $e->getMessage() . "\n";
        echo "   📍 File: " . $e->getFile() . "\n";
        echo "   📍 Line: " . $e->getLine() . "\n";
        $errors_found = true;
    } catch (Exception $e) {
        $error_occurred = true;
        echo "   ❌ Exception: " . $e->getMessage() . "\n";
        echo "   📍 File: " . $e->getFile() . "\n";
        echo "   📍 Line: " . $e->getLine() . "\n";
        $errors_found = true;
    }
    
    $output = ob_get_clean();
    if ($output && !$error_occurred) {
        echo "   ⚠️  Output generated during load:\n";
        echo "   " . str_replace("\n", "\n   ", trim($output)) . "\n";
    }
    
} catch (Throwable $e) {
    echo "   ❌ Critical Error: " . $e->getMessage() . "\n";
    echo "   📍 File: " . $e->getFile() . "\n";
    echo "   📍 Line: " . $e->getLine() . "\n";
    $errors_found = true;
}

echo "\n";

// Test 6: Check for common activation issues
echo "6. Checking Common Activation Issues:\n";

// Check for BOM (Byte Order Mark)
$main_content = file_get_contents($main_file);
if (substr($main_content, 0, 3) === "\xEF\xBB\xBF") {
    echo "   ❌ BOM detected in main file (can cause headers already sent error)\n";
    $errors_found = true;
} else {
    echo "   ✅ No BOM in main file\n";
}

// Check for whitespace before <?php
if (preg_match('/^\s+<\?php/', $main_content)) {
    echo "   ❌ Whitespace before <?php tag\n";
    $errors_found = true;
} else {
    echo "   ✅ No whitespace before <?php tag\n";
}

// Check for closing PHP tags in main file
if (preg_match('/\?>\s*$/', $main_content)) {
    echo "   ⚠️  Closing ?> tag found (not recommended for plugin files)\n";
} else {
    echo "   ✅ No closing ?> tag\n";
}

echo "\n";

// Final Summary
echo "FINAL SUMMARY:\n";
echo "==============\n";

if ($errors_found) {
    echo "❌ ERRORS FOUND - Plugin activation will likely fail\n\n";
    
    echo "Critical Issues to Fix:\n";
    if (!empty($files_with_issues)) {
        echo "• Files with syntax errors:\n";
        foreach ($files_with_issues as $file) {
            echo "  - {$file}\n";
        }
    }
    
    echo "\nRecommended Actions:\n";
    echo "1. Fix all syntax errors in PHP files\n";
    echo "2. Ensure all required files exist\n";
    echo "3. Run 'composer install' if autoloader is missing\n";
    echo "4. Check for BOM and whitespace issues\n";
    echo "5. Test plugin loading in isolated environment\n";
    
} else {
    echo "✅ NO CRITICAL ERRORS FOUND\n\n";
    echo "The plugin should activate successfully.\n";
    echo "If activation still fails, check:\n";
    echo "• WordPress error logs\n";
    echo "• PHP error logs\n";
    echo "• Memory limits\n";
    echo "• Plugin conflicts\n";
}

echo "\nFor detailed WordPress error information:\n";
echo "• Enable WP_DEBUG in wp-config.php\n";
echo "• Check /wp-content/debug.log\n";
echo "• Check server error logs\n";

exit($errors_found ? 1 : 0);
?>
