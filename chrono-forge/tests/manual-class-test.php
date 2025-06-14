<?php
/**
 * Manual class loading test for ChronoForge
 * This script tests class loading outside of WordPress to identify issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ChronoForge Manual Class Loading Test ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Current Directory: " . __DIR__ . "\n\n";

// Define basic constants that the plugin expects
if (!defined('ABSPATH')) {
    define('ABSPATH', '/var/www/html/wordpress/');
}

if (!defined('CHRONO_FORGE_PLUGIN_DIR')) {
    define('CHRONO_FORGE_PLUGIN_DIR', __DIR__ . '/');
}

if (!defined('CHRONO_FORGE_VERSION')) {
    define('CHRONO_FORGE_VERSION', '1.0.0');
}

// Mock WordPress functions that might be needed
if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12, $special_chars = true) {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('is_email')) {
    function is_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = array()) {
        if (is_object($args)) {
            $parsed_args = get_object_vars($args);
        } elseif (is_array($args)) {
            $parsed_args = &$args;
        } else {
            parse_str($args, $parsed_args);
        }
        
        if (is_array($defaults) && $defaults) {
            return array_merge($defaults, $parsed_args);
        }
        return $parsed_args;
    }
}

if (!function_exists('wp_cache_get')) {
    function wp_cache_get($key, $group = '') {
        return false;
    }
}

if (!function_exists('wp_cache_set')) {
    function wp_cache_set($key, $data, $group = '', $expire = 0) {
        return true;
    }
}

if (!function_exists('wp_cache_flush')) {
    function wp_cache_flush() {
        return true;
    }
}

if (!function_exists('wp_cache_delete_group')) {
    function wp_cache_delete_group($group) {
        return true;
    }
}

if (!function_exists('get_num_queries')) {
    function get_num_queries() {
        return 0;
    }
}

if (!function_exists('wp_cache_get_stats')) {
    function wp_cache_get_stats() {
        return array();
    }
}

if (!function_exists('wp_convert_hr_to_bytes')) {
    function wp_convert_hr_to_bytes($value) {
        $value = strtolower(trim($value));
        $bytes = (int) $value;
        
        if (false !== strpos($value, 'g')) {
            $bytes *= 1024 * 1024 * 1024;
        } elseif (false !== strpos($value, 'm')) {
            $bytes *= 1024 * 1024;
        } elseif (false !== strpos($value, 'k')) {
            $bytes *= 1024;
        }
        
        return $bytes;
    }
}

// Test 1: Load utility functions
echo "=== Test 1: Loading Utility Functions ===\n";
$utils_file = __DIR__ . '/includes/utils/functions.php';
echo "Utils file: $utils_file\n";
echo "File exists: " . (file_exists($utils_file) ? 'YES' : 'NO') . "\n";

if (file_exists($utils_file)) {
    try {
        require_once $utils_file;
        echo "✓ Utils file loaded successfully\n";
        
        // Test if key functions exist
        $functions_to_test = [
            'chrono_forge_safe_log',
            'chrono_forge_log',
            'chrono_forge_get_appointment_statuses',
            'chrono_forge_get_setting'
        ];
        
        foreach ($functions_to_test as $func) {
            if (function_exists($func)) {
                echo "✓ Function $func exists\n";
            } else {
                echo "✗ Function $func missing\n";
            }
        }
        
    } catch (Exception $e) {
        echo "✗ Exception loading utils: " . $e->getMessage() . "\n";
    } catch (Error $e) {
        echo "✗ Fatal error loading utils: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Utils file not found\n";
}

echo "\n";

// Test 2: Load DB Manager
echo "=== Test 2: Loading DB Manager ===\n";
$db_file = __DIR__ . '/includes/class-chrono-forge-db-manager.php';
echo "DB Manager file: $db_file\n";
echo "File exists: " . (file_exists($db_file) ? 'YES' : 'NO') . "\n";

if (file_exists($db_file)) {
    try {
        require_once $db_file;
        echo "✓ DB Manager file loaded successfully\n";
        
        if (class_exists('ChronoForge_DB_Manager')) {
            echo "✓ ChronoForge_DB_Manager class exists\n";
        } else {
            echo "✗ ChronoForge_DB_Manager class not found\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Exception loading DB Manager: " . $e->getMessage() . "\n";
    } catch (Error $e) {
        echo "✗ Fatal error loading DB Manager: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ DB Manager file not found\n";
}

echo "\n";

// Test 3: Load Admin Menu
echo "=== Test 3: Loading Admin Menu ===\n";
$admin_file = __DIR__ . '/admin/class-chrono-forge-admin-menu.php';
echo "Admin Menu file: $admin_file\n";
echo "File exists: " . (file_exists($admin_file) ? 'YES' : 'NO') . "\n";

if (file_exists($admin_file)) {
    try {
        require_once $admin_file;
        echo "✓ Admin Menu file loaded successfully\n";
        
        if (class_exists('ChronoForge_Admin_Menu')) {
            echo "✓ ChronoForge_Admin_Menu class exists\n";
        } else {
            echo "✗ ChronoForge_Admin_Menu class not found\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Exception loading Admin Menu: " . $e->getMessage() . "\n";
    } catch (Error $e) {
        echo "✗ Fatal error loading Admin Menu: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Admin Menu file not found\n";
}

echo "\n";

// Test 4: Load Core
echo "=== Test 4: Loading Core ===\n";
$core_file = __DIR__ . '/includes/class-chrono-forge-core.php';
echo "Core file: $core_file\n";
echo "File exists: " . (file_exists($core_file) ? 'YES' : 'NO') . "\n";

if (file_exists($core_file)) {
    try {
        require_once $core_file;
        echo "✓ Core file loaded successfully\n";
        
        if (class_exists('ChronoForge_Core')) {
            echo "✓ ChronoForge_Core class exists\n";
        } else {
            echo "✗ ChronoForge_Core class not found\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Exception loading Core: " . $e->getMessage() . "\n";
    } catch (Error $e) {
        echo "✗ Fatal error loading Core: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Core file not found\n";
}

echo "\n=== Test Complete ===\n";
