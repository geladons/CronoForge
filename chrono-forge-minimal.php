<?php
/**
 * Plugin Name: ChronoForge Minimal Test
 * Plugin URI: https://example.com/chrono-forge
 * Description: Minimal test version of ChronoForge booking plugin
 * Version: 1.0.0-test
 * Author: ChronoForge Team
 * License: GPL v2 or later
 * Text Domain: chrono-forge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CHRONO_FORGE_VERSION', '1.0.0-test');
define('CHRONO_FORGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CHRONO_FORGE_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Simple logging function
 */
if (!function_exists('chrono_forge_safe_log')) {
    function chrono_forge_safe_log($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $timestamp = date('Y-m-d H:i:s');
            error_log("[{$timestamp}] [ChronoForge {$level}] {$message}");
        }
    }
}

/**
 * Plugin activation hook
 */
function chrono_forge_minimal_activate() {
    chrono_forge_safe_log('ChronoForge Minimal activated successfully', 'info');
    add_option('chrono_forge_minimal_activated', true);
}
register_activation_hook(__FILE__, 'chrono_forge_minimal_activate');

/**
 * Plugin deactivation hook
 */
function chrono_forge_minimal_deactivate() {
    chrono_forge_safe_log('ChronoForge Minimal deactivated', 'info');
    delete_option('chrono_forge_minimal_activated');
}
register_deactivation_hook(__FILE__, 'chrono_forge_minimal_deactivate');

/**
 * Initialize the minimal plugin
 */
function chrono_forge_minimal_init() {
    chrono_forge_safe_log('ChronoForge Minimal initializing', 'info');
    
    // Add admin menu
    add_action('admin_menu', 'chrono_forge_minimal_admin_menu');
    
    // Add admin notice
    add_action('admin_notices', 'chrono_forge_minimal_admin_notice');
    
    chrono_forge_safe_log('ChronoForge Minimal initialized successfully', 'info');
}
add_action('plugins_loaded', 'chrono_forge_minimal_init');

/**
 * Add admin menu
 */
function chrono_forge_minimal_admin_menu() {
    add_menu_page(
        'ChronoForge Test',
        'ChronoForge Test',
        'manage_options',
        'chrono-forge-test',
        'chrono_forge_minimal_admin_page',
        'dashicons-calendar-alt',
        30
    );
}

/**
 * Admin page content
 */
function chrono_forge_minimal_admin_page() {
    ?>
    <div class="wrap">
        <h1>ChronoForge Minimal Test</h1>
        <div class="notice notice-success">
            <p><strong>Success!</strong> ChronoForge minimal version is working correctly.</p>
        </div>
        
        <h2>System Information</h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong>Plugin Version</strong></td>
                    <td><?php echo CHRONO_FORGE_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong>WordPress Version</strong></td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td><strong>PHP Version</strong></td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong>Plugin Directory</strong></td>
                    <td><?php echo CHRONO_FORGE_PLUGIN_DIR; ?></td>
                </tr>
                <tr>
                    <td><strong>Plugin URL</strong></td>
                    <td><?php echo CHRONO_FORGE_PLUGIN_URL; ?></td>
                </tr>
            </tbody>
        </table>
        
        <h2>Next Steps</h2>
        <p>If you can see this page, the basic WordPress plugin structure is working correctly.</p>
        <p>The issue with the full ChronoForge plugin is likely in one of the included class files or dependencies.</p>
        
        <h2>Troubleshooting</h2>
        <ol>
            <li>Check WordPress error logs for specific error messages</li>
            <li>Enable WP_DEBUG in wp-config.php</li>
            <li>Test individual class files for syntax errors</li>
            <li>Check file permissions on the plugin directory</li>
        </ol>
    </div>
    <?php
}

/**
 * Admin notice
 */
function chrono_forge_minimal_admin_notice() {
    if (get_option('chrono_forge_minimal_activated')) {
        ?>
        <div class="notice notice-info is-dismissible">
            <p><strong>ChronoForge Minimal Test:</strong> Plugin is active and working. Check the <a href="<?php echo admin_url('admin.php?page=chrono-forge-test'); ?>">test page</a>.</p>
        </div>
        <?php
    }
}

chrono_forge_safe_log('ChronoForge Minimal plugin file loaded', 'info');
?>
