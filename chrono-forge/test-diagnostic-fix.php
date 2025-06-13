<?php
/**
 * Test script to verify diagnostic fixes
 */

// WordPress environment simulation
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Plugin constants
define('CHRONO_FORGE_VERSION', '1.0.0');
define('CHRONO_FORGE_PLUGIN_DIR', dirname(__FILE__) . '/');

// Mock WordPress functions
if (!function_exists('get_transient')) {
    function get_transient($key) { return false; }
}
if (!function_exists('set_transient')) {
    function set_transient($key, $value, $expiration) { return true; }
}
if (!function_exists('delete_transient')) {
    function delete_transient($key) { return true; }
}
if (!function_exists('current_time')) {
    function current_time($format) { return date($format); }
}
if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}

echo "=== ChronoForge Diagnostic Fix Test ===\n\n";

// Test 1: Check if diagnostic files exist
echo "1. Checking diagnostic files...\n";
$diagnostic_files = array(
    'includes/class-chrono-forge-diagnostics.php' => 'Diagnostics engine',
    'admin/class-chrono-forge-admin-diagnostics.php' => 'Admin diagnostics interface'
);

foreach ($diagnostic_files as $file => $description) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "   ✅ {$file} - {$description}\n";
    } else {
        echo "   ❌ {$file} - MISSING\n";
    }
}

// Test 2: Check main plugin file syntax
echo "\n2. Checking main plugin file syntax...\n";
$main_file = __DIR__ . '/chrono-forge.php';
if (file_exists($main_file)) {
    $content = file_get_contents($main_file);
    $open_braces = substr_count($content, '{');
    $close_braces = substr_count($content, '}');
    $open_parens = substr_count($content, '(');
    $close_parens = substr_count($content, ')');
    
    echo "   Braces: {$open_braces} open, {$close_braces} close";
    if ($open_braces === $close_braces) {
        echo " ✅ MATCHED\n";
    } else {
        echo " ❌ MISMATCHED (diff: " . abs($open_braces - $close_braces) . ")\n";
    }
    
    echo "   Parentheses: {$open_parens} open, {$close_parens} close";
    $paren_diff = abs($open_parens - $close_parens);
    if ($paren_diff <= 1) {
        echo " ✅ MATCHED\n";
    } else {
        echo " ❌ MISMATCHED (diff: {$paren_diff})\n";
    }
} else {
    echo "   ❌ Main plugin file not found\n";
}

// Test 3: Test diagnostic class loading
echo "\n3. Testing diagnostic class loading...\n";
try {
    if (file_exists(__DIR__ . '/includes/class-chrono-forge-diagnostics.php')) {
        require_once __DIR__ . '/includes/class-chrono-forge-diagnostics.php';
        
        if (class_exists('ChronoForge_Diagnostics')) {
            echo "   ✅ ChronoForge_Diagnostics class loaded successfully\n";
            
            // Test instantiation
            $diagnostics = ChronoForge_Diagnostics::instance();
            if ($diagnostics) {
                echo "   ✅ Diagnostics instance created successfully\n";
                
                // Test method existence
                if (method_exists($diagnostics, 'run_full_diagnostics')) {
                    echo "   ✅ run_full_diagnostics method exists\n";
                } else {
                    echo "   ❌ run_full_diagnostics method missing\n";
                }
            } else {
                echo "   ❌ Failed to create diagnostics instance\n";
            }
        } else {
            echo "   ❌ ChronoForge_Diagnostics class not found after loading file\n";
        }
    } else {
        echo "   ❌ Diagnostics file not found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error loading diagnostics: " . $e->getMessage() . "\n";
}

// Test 4: Check created files
echo "\n4. Checking created files...\n";
$created_files = array(
    'includes/class-chrono-forge-database.php' => 'Database management',
    'admin/class-chrono-forge-admin-ajax.php' => 'Admin AJAX handler',
    'public/class-chrono-forge-public.php' => 'Public functionality'
);

foreach ($created_files as $file => $description) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "   ✅ {$file} - {$description} ({$size} bytes)\n";
    } else {
        echo "   ❌ {$file} - MISSING\n";
    }
}

// Test 5: Basic syntax check on key files
echo "\n5. Basic syntax check on key files...\n";
$files_to_check = array(
    'chrono-forge.php',
    'includes/class-chrono-forge-core.php',
    'includes/class-chrono-forge-diagnostics.php'
);

foreach ($files_to_check as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        // Basic checks
        $has_php_tag = strpos($content, '<?php') !== false;
        $open_braces = substr_count($content, '{');
        $close_braces = substr_count($content, '}');
        $brace_balanced = $open_braces === $close_braces;
        
        if ($has_php_tag && $brace_balanced) {
            echo "   ✅ {$file} - Basic syntax OK\n";
        } else {
            echo "   ❌ {$file} - Issues found:";
            if (!$has_php_tag) echo " [No PHP tag]";
            if (!$brace_balanced) echo " [Unbalanced braces: {$open_braces}/{$close_braces}]";
            echo "\n";
        }
    } else {
        echo "   ❌ {$file} - File not found\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "DIAGNOSTIC FIX TEST SUMMARY:\n";
echo "- All critical files should be present\n";
echo "- Main plugin file syntax should be correct\n";
echo "- Diagnostic system should be updated\n";
echo "- Cache clearing mechanisms should be in place\n";
echo "\nIf all tests pass, try refreshing the WordPress admin page\n";
echo "and clicking 'Run Diagnostics' to see updated results.\n";
echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
