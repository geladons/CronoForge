<?php
/**
 * Emergency Fix Script for ChronoForge Plugin
 *
 * This script provides emergency fixes for common ChronoForge plugin issues.
 * Run this script when the plugin is causing critical errors.
 *
 * @package ChronoForge
 * @version 1.0.0
 */

// Prevent direct access in WordPress context
if (defined('ABSPATH')) {
    wp_die('This script should not be run in WordPress context');
}

echo "=== ChronoForge Emergency Fix Script ===\n\n";

// Get the plugin directory
$plugin_dir = dirname(__DIR__) . '/chrono-forge';

if (!is_dir($plugin_dir)) {
    echo "❌ ChronoForge plugin directory not found at: {$plugin_dir}\n";
    exit(1);
}

echo "🔧 Plugin directory found: {$plugin_dir}\n\n";

/**
 * Fix 1: Disable the plugin by renaming main file
 */
function emergency_disable_plugin($plugin_dir) {
    $main_file = $plugin_dir . '/chrono-forge.php';
    $disabled_file = $plugin_dir . '/chrono-forge.php.disabled';
    
    if (file_exists($main_file) && !file_exists($disabled_file)) {
        if (rename($main_file, $disabled_file)) {
            echo "✅ Plugin disabled by renaming main file\n";
            return true;
        } else {
            echo "❌ Failed to disable plugin\n";
            return false;
        }
    } elseif (file_exists($disabled_file)) {
        echo "ℹ️  Plugin already disabled\n";
        return true;
    } else {
        echo "❌ Main plugin file not found\n";
        return false;
    }
}

/**
 * Fix 2: Enable the plugin by restoring main file
 */
function emergency_enable_plugin($plugin_dir) {
    $main_file = $plugin_dir . '/chrono-forge.php';
    $disabled_file = $plugin_dir . '/chrono-forge.php.disabled';
    
    if (file_exists($disabled_file) && !file_exists($main_file)) {
        if (rename($disabled_file, $main_file)) {
            echo "✅ Plugin enabled by restoring main file\n";
            return true;
        } else {
            echo "❌ Failed to enable plugin\n";
            return false;
        }
    } elseif (file_exists($main_file)) {
        echo "ℹ️  Plugin already enabled\n";
        return true;
    } else {
        echo "❌ Disabled plugin file not found\n";
        return false;
    }
}

/**
 * Fix 3: Create safe mode version
 */
function create_safe_mode($plugin_dir) {
    $safe_content = '<?php
/**
 * ChronoForge Safe Mode
 * Emergency safe mode version
 */

// Prevent direct access
if (!defined("ABSPATH")) {
    exit;
}

// Show admin notice
add_action("admin_notices", function() {
    echo "<div class=\"notice notice-warning\"><p>";
    echo "<strong>ChronoForge Safe Mode:</strong> Plugin is running in emergency safe mode. ";
    echo "Please check the plugin files and configuration.";
    echo "</p></div>";
});

// Add minimal admin menu
add_action("admin_menu", function() {
    add_menu_page(
        "ChronoForge Safe",
        "CF Safe",
        "manage_options",
        "chrono-forge-safe-mode",
        function() {
            echo "<div class=\"wrap\">";
            echo "<h1>ChronoForge Safe Mode</h1>";
            echo "<p>The plugin is running in emergency safe mode.</p>";
            echo "<p>Please contact support or check the plugin documentation.</p>";
            echo "</div>";
        },
        "dashicons-warning",
        30
    );
});
';

    $safe_file = $plugin_dir . '/chrono-forge-safe-mode.php';
    
    if (file_put_contents($safe_file, $safe_content)) {
        echo "✅ Safe mode file created: {$safe_file}\n";
        return true;
    } else {
        echo "❌ Failed to create safe mode file\n";
        return false;
    }
}

/**
 * Fix 4: Check and fix file permissions
 */
function fix_file_permissions($plugin_dir) {
    $files_fixed = 0;
    $dirs_fixed = 0;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($plugin_dir)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $current_perms = fileperms($file->getPathname()) & 0777;
            if ($current_perms !== 0644) {
                if (chmod($file->getPathname(), 0644)) {
                    $files_fixed++;
                }
            }
        } elseif ($file->isDir() && !in_array($file->getFilename(), ['.', '..'])) {
            $current_perms = fileperms($file->getPathname()) & 0777;
            if ($current_perms !== 0755) {
                if (chmod($file->getPathname(), 0755)) {
                    $dirs_fixed++;
                }
            }
        }
    }
    
    echo "✅ Fixed permissions for {$files_fixed} files and {$dirs_fixed} directories\n";
    return true;
}

// Main execution
echo "Available emergency fixes:\n";
echo "1. Disable plugin\n";
echo "2. Enable plugin\n";
echo "3. Create safe mode version\n";
echo "4. Fix file permissions\n";
echo "5. Run all fixes\n";
echo "\n";

// Get user input
echo "Enter fix number (1-5): ";
$handle = fopen("php://stdin", "r");
$choice = trim(fgets($handle));
fclose($handle);

switch ($choice) {
    case '1':
        emergency_disable_plugin($plugin_dir);
        break;
    case '2':
        emergency_enable_plugin($plugin_dir);
        break;
    case '3':
        create_safe_mode($plugin_dir);
        break;
    case '4':
        fix_file_permissions($plugin_dir);
        break;
    case '5':
        echo "Running all fixes...\n\n";
        emergency_disable_plugin($plugin_dir);
        create_safe_mode($plugin_dir);
        fix_file_permissions($plugin_dir);
        echo "\n✅ All emergency fixes completed\n";
        break;
    default:
        echo "❌ Invalid choice\n";
        exit(1);
}

echo "\n=== Emergency Fix Complete ===\n";
echo "Please test the WordPress site and check for any remaining issues.\n";
