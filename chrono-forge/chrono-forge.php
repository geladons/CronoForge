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
 * Initialize the plugin safely
 */
function chrono_forge_init_plugin() {
    try {
        // Load utility functions first
        require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/utils/functions.php';

        // Load main plugin class
        require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-core.php';

        // Check if class exists before instantiating
        if (class_exists('ChronoForge_Core')) {
            return ChronoForge_Core::instance();
        } else {
            error_log('ChronoForge: Core class not found');
            return null;
        }
    } catch (Exception $e) {
        error_log('ChronoForge Initialization Error: ' . $e->getMessage());
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
    }
    return $instance;
}

// Initialize the plugin after WordPress is fully loaded
add_action('plugins_loaded', 'chrono_forge', 10);
