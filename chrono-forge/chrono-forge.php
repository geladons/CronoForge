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

// Load main plugin class
require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-core.php';

/**
 * Plugin activation function
 */
function activate_chrono_forge() {
    require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-activator.php';
    ChronoForge_Activator::activate();
}

/**
 * Plugin deactivation function
 */
function deactivate_chrono_forge() {
    require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-deactivator.php';
    ChronoForge_Deactivator::deactivate();
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'activate_chrono_forge');
register_deactivation_hook(__FILE__, 'deactivate_chrono_forge');

/**
 * Main function to run the plugin (singleton)
 *
 * @return ChronoForge_Core
 */
function chrono_forge() {
    return ChronoForge_Core::instance();
}

// Initialize the plugin
chrono_forge();
