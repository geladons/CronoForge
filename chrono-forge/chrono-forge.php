<?php
/**
 * Plugin Name: ChronoForge
 * Plugin URI: https://chronoforge.com
 * Description: Comprehensive WordPress booking and appointment management plugin for service-based businesses.
 * Version: 1.0.0
 * Author: ChronoForge Team
 * Author URI: https://chronoforge.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: chrono-forge
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CHRONO_FORGE_VERSION', '1.0.0');
define('CHRONO_FORGE_PLUGIN_FILE', __FILE__);
define('CHRONO_FORGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CHRONO_FORGE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CHRONO_FORGE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin activation function
 */
function activate_chrono_forge() {
    try {
        // Load utility functions first
        require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/utils/functions.php';

        // Load activator class
        require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-activator.php';

        // Check if class exists before calling
        if (class_exists('ChronoForge_Activator')) {
            ChronoForge_Activator::activate();
        } else {
            error_log('ChronoForge: Activator class not found during activation');
        }
    } catch (Exception $e) {
        error_log('ChronoForge Activation Error: ' . $e->getMessage());
        // Don't throw the exception to prevent fatal error
    }
}

/**
 * Plugin deactivation function
 */
function deactivate_chrono_forge() {
    try {
        require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-deactivator.php';
        if (class_exists('ChronoForge_Deactivator')) {
            ChronoForge_Deactivator::deactivate();
        }
    } catch (Exception $e) {
        error_log('ChronoForge Deactivation Error: ' . $e->getMessage());
    }
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'activate_chrono_forge');
register_deactivation_hook(__FILE__, 'deactivate_chrono_forge');

/**
 * Check for syntax errors in critical files
 */
function chrono_forge_check_syntax() {
    $critical_files = array(
        'includes/utils/functions.php',
        'includes/class-chrono-forge-core.php',
        'includes/class-chrono-forge-db-manager.php',
        'includes/class-chrono-forge-ajax-handler.php',
        'includes/class-chrono-forge-shortcodes.php',
        'admin/class-chrono-forge-admin-menu.php'
    );

    foreach ($critical_files as $file) {
        $file_path = CHRONO_FORGE_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);

            // Basic syntax checks
            if (substr_count($content, '{') !== substr_count($content, '}')) {
                error_log("ChronoForge Syntax Error: Mismatched braces in {$file}");
                return false;
            }

            // Check for common syntax issues
            if (preg_match('/\bpublic\s+function\s+\w+\([^)]*\)\s*[^{]/', $content)) {
                error_log("ChronoForge Syntax Error: Possible missing opening brace in {$file}");
                return false;
            }
        }
    }

    return true;
}

/**
 * Initialize the plugin safely
 */
function chrono_forge_init_plugin() {
    try {
        // Check if WordPress is properly loaded
        if (!function_exists('add_action')) {
            error_log('ChronoForge: WordPress not properly loaded');
            return null;
        }

        // Perform syntax check first
        if (!chrono_forge_check_syntax()) {
            error_log('ChronoForge: Syntax errors detected, aborting initialization');
            add_action('admin_notices', 'chrono_forge_syntax_error_notice');
            return null;
        }

        // Load utility functions first
        $utils_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/utils/functions.php';
        if (!file_exists($utils_file)) {
            error_log('ChronoForge: Utility functions file not found');
            return null;
        }
        require_once $utils_file;

        // Load main plugin class
        $core_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-core.php';
        if (!file_exists($core_file)) {
            error_log('ChronoForge: Core class file not found');
            return null;
        }
        require_once $core_file;

        // Check if class exists before instantiating
        if (class_exists('ChronoForge_Core')) {
            $instance = ChronoForge_Core::instance();
            if ($instance) {
                return $instance;
            } else {
                error_log('ChronoForge: Failed to create core instance');
                add_action('admin_notices', 'chrono_forge_critical_error_notice');
                return null;
            }
        } else {
            error_log('ChronoForge: Core class not found after loading');
            add_action('admin_notices', 'chrono_forge_critical_error_notice');
            return null;
        }
    } catch (ParseError $e) {
        error_log('ChronoForge Parse Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        add_action('admin_notices', 'chrono_forge_syntax_error_notice');
        return null;
    } catch (Exception $e) {
        error_log('ChronoForge Initialization Error: ' . $e->getMessage());
        add_action('admin_notices', 'chrono_forge_critical_error_notice');
        return null;
    } catch (Error $e) {
        error_log('ChronoForge Fatal Error: ' . $e->getMessage());
        add_action('admin_notices', 'chrono_forge_critical_error_notice');
        return null;
    }
}

/**
 * Main function to run the plugin (singleton)
 *
 * @return ChronoForge_Core|null
 */
function chrono_forge() {
    static $instance = null;
    if ($instance === null) {
        $instance = chrono_forge_init_plugin();

        // Debug: Log successful initialization
        if ($instance) {
            error_log('ChronoForge: Plugin initialized successfully');
        } else {
            error_log('ChronoForge: Plugin initialization failed');
        }
    }
    return $instance;
}

/**
 * Debug function to check plugin status
 */
function chrono_forge_debug_status() {
    $plugin = chrono_forge();
    if ($plugin) {
        $status = $plugin->get_plugin_status();
        error_log('ChronoForge Debug Status: ' . print_r($status, true));
        return $status;
    } else {
        error_log('ChronoForge Debug: Plugin instance not available');
        return false;
    }
}

/**
 * Critical error notice for admin
 */
function chrono_forge_critical_error_notice() {
    echo '<div class="notice notice-error"><p>';
    echo '<strong>ChronoForge Plugin Error:</strong> ';
    echo __('Плагин не может быть загружен из-за критической ошибки. Проверьте логи сервера для получения подробной информации.', 'chrono-forge');
    echo '</p></div>';
}

/**
 * Syntax error notice for admin
 */
function chrono_forge_syntax_error_notice() {
    echo '<div class="notice notice-error"><p>';
    echo '<strong>ChronoForge Syntax Error:</strong> ';
    echo __('Обнаружены синтаксические ошибки в файлах плагина. Проверьте логи сервера для получения подробной информации.', 'chrono-forge');
    echo ' <a href="' . admin_url('plugins.php') . '">' . __('Деактивировать плагин', 'chrono-forge') . '</a>';
    echo '</p></div>';
}

/**
 * Emergency deactivation function
 */
function chrono_forge_emergency_deactivate() {
    if (function_exists('deactivate_plugins')) {
        deactivate_plugins(plugin_basename(__FILE__));
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning"><p>';
            echo __('ChronoForge был автоматически деактивирован из-за критической ошибки.', 'chrono-forge');
            echo '</p></div>';
        });
    }
}

// Initialize the plugin after WordPress is fully loaded
add_action('plugins_loaded', 'chrono_forge', 10);
