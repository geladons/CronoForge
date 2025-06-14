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
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Network: false
 */

namespace ChronoForge;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('CHRONO_FORGE_VERSION', '1.0.0');
define('CHRONO_FORGE_PLUGIN_FILE', __FILE__);
define('CHRONO_FORGE_PLUGIN_DIR', __DIR__ . '/');
define('CHRONO_FORGE_PLUGIN_URL', function_exists('plugin_dir_url') ? plugin_dir_url(__FILE__) : '');
define('CHRONO_FORGE_PLUGIN_BASENAME', function_exists('plugin_basename') ? plugin_basename(__FILE__) : '');
define('CHRONO_FORGE_MIN_PHP_VERSION', '7.4');
define('CHRONO_FORGE_MIN_WP_VERSION', '5.0');

// Check PHP version compatibility
if (version_compare(PHP_VERSION, CHRONO_FORGE_MIN_PHP_VERSION, '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo sprintf(
            __('ChronoForge requires PHP %s or higher. You are running PHP %s.', 'chrono-forge'),
            CHRONO_FORGE_MIN_PHP_VERSION,
            PHP_VERSION
        );
        echo '</p></div>';
    });
    return;
}

// Check WordPress version compatibility
global $wp_version;
if (!empty($wp_version) && version_compare($wp_version, CHRONO_FORGE_MIN_WP_VERSION, '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo sprintf(
            __('ChronoForge requires WordPress %s or higher. You are running WordPress %s.', 'chrono-forge'),
            CHRONO_FORGE_MIN_WP_VERSION,
            $GLOBALS['wp_version'] ?? 'Unknown'
        );
        echo '</p></div>';
    });
    return;
}

// Load Composer autoloader
$autoloader = CHRONO_FORGE_PLUGIN_DIR . 'vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

// Load core functions
$functions_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/functions.php';
if (!file_exists($functions_file)) {
    wp_die('ChronoForge: Core functions file not found at: ' . $functions_file);
}
require_once $functions_file;

namespace ChronoForge;

use ChronoForge\Infrastructure\Container;

/**
 * Main Plugin Class
 *
 * @package ChronoForge
 */
class Plugin
{
    /**
     * Plugin instance
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Container instance
     *
     * @var Container
     */
    private $container;

    /**
     * Get plugin instance
     *
     * @return Plugin
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->initContainer();
        $this->initHooks();
    }

    /**
     * Initialize dependency injection container
     */
    private function initContainer()
    {
        $container_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/container.php';
        if (!file_exists($container_file)) {
            wp_die('ChronoForge: Container file not found at: ' . $container_file);
        }
        $this->container = require $container_file;
    }

    /**
     * Initialize WordPress hooks
     */
    private function initHooks()
    {
        // Plugin lifecycle hooks
        register_activation_hook(CHRONO_FORGE_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(CHRONO_FORGE_PLUGIN_FILE, [$this, 'deactivate']);

        // WordPress initialization hooks
        add_action('plugins_loaded', [$this, 'init']);
        add_action('admin_menu', [$this, 'initAdminMenu']);
        add_action('wp_ajax_chrono_forge_api', [$this, 'apiCall']);
        add_action('wp_ajax_nopriv_chrono_forge_api', [$this, 'apiCall']);

        // Plugin action links
        add_filter('plugin_action_links_' . CHRONO_FORGE_PLUGIN_BASENAME, [$this, 'addPluginActionLinks']);
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        // Load text domain
        load_plugin_textdomain('chrono-forge', false, dirname(CHRONO_FORGE_PLUGIN_BASENAME) . '/languages');

        // Initialize components
        do_action('chrono_forge_init', $this->container);
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        try {
            $activator = $this->container->get('activator');
            $activator->activate();
        } catch (\Exception $e) {
            wp_die(
                '<h1>' . __('Plugin Activation Failed', 'chrono-forge') . '</h1>' .
                '<p><strong>' . __('Error:', 'chrono-forge') . '</strong> ' . esc_html($e->getMessage()) . '</p>' .
                '<p><a href="' . admin_url('plugins.php') . '">' . __('Back to Plugins', 'chrono-forge') . '</a></p>',
                __('Plugin Activation Failed', 'chrono-forge'),
                ['back_link' => true]
            );
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        $deactivator = $this->container->get('deactivator');
        $deactivator->deactivate();
    }

    /**
     * Initialize admin menu
     */
    public function initAdminMenu()
    {
        if (!is_admin()) {
            return;
        }

        $menuManager = $this->container->get('admin.menu');
        $menuManager->init();
    }

    /**
     * Handle API calls
     */
    public function apiCall()
    {
        try {
            $router = $this->container->get('router');
            $router->handleRequest();
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Add plugin action links
     */
    public function addPluginActionLinks($links)
    {
        $settings_link = '<a href="' . admin_url('admin.php?page=chrono-forge-settings') . '">' . __('Settings', 'chrono-forge') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Get container
     */
    public function getContainer()
    {
        return $this->container;
    }
}

// Initialize the plugin
Plugin::getInstance();
