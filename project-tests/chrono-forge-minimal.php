<?php
/**
 * ChronoForge Minimal Test Plugin
 *
 * This is a minimal version of the ChronoForge plugin for testing purposes.
 * It contains only the essential components needed to verify basic functionality.
 *
 * @package ChronoForge
 * @version 1.0.0-minimal
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CHRONO_FORGE_MINIMAL_VERSION', '1.0.0-minimal');
define('CHRONO_FORGE_MINIMAL_FILE', __FILE__);
define('CHRONO_FORGE_MINIMAL_DIR', plugin_dir_path(__FILE__));
define('CHRONO_FORGE_MINIMAL_URL', plugin_dir_url(__FILE__));

/**
 * Minimal ChronoForge Test Class
 */
class ChronoForge_Minimal {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_notices', [$this, 'show_status_notice']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'ChronoForge Minimal',
            'CF Minimal',
            'manage_options',
            'chrono-forge-minimal',
            [$this, 'admin_page'],
            'dashicons-calendar-alt',
            30
        );
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        echo '<div class="wrap">';
        echo '<h1>ChronoForge Minimal Test</h1>';
        echo '<p>This is a minimal test version of ChronoForge plugin.</p>';
        echo '<h2>System Information</h2>';
        echo '<ul>';
        echo '<li>PHP Version: ' . PHP_VERSION . '</li>';
        echo '<li>WordPress Version: ' . get_bloginfo('version') . '</li>';
        echo '<li>Plugin Version: ' . CHRONO_FORGE_MINIMAL_VERSION . '</li>';
        echo '<li>Plugin Directory: ' . CHRONO_FORGE_MINIMAL_DIR . '</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * Show status notice
     */
    public function show_status_notice() {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>ChronoForge Minimal:</strong> Plugin loaded successfully!</p>';
        echo '</div>';
    }
}

// Initialize the minimal plugin
add_action('plugins_loaded', function() {
    ChronoForge_Minimal::instance();
});

/**
 * Activation hook
 */
register_activation_hook(__FILE__, function() {
    error_log('ChronoForge Minimal: Plugin activated');
});

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, function() {
    error_log('ChronoForge Minimal: Plugin deactivated');
});
