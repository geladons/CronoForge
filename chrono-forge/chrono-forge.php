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

    if (current_user_can('manage_options')) {
        $emergency_url = admin_url('admin.php?page=chrono-forge-emergency');
        echo ' <a href="' . $emergency_url . '">' . __('Открыть диагностику', 'chrono-forge') . '</a>';
    }

    echo ' | <a href="#" onclick="chronoForgeShowEmergencyDiagnostics(); return false;">' . __('Экстренная диагностика', 'chrono-forge') . '</a>';
    echo '</p></div>';

    chrono_forge_add_emergency_diagnostics_script();
}

/**
 * Standalone diagnostics page function
 */
function chrono_forge_standalone_diagnostics_page() {
    // Debug: Log that function was called
    error_log('ChronoForge: chrono_forge_standalone_diagnostics_page() called');

    // Check permissions with detailed logging
    if (!current_user_can('manage_options')) {
        $current_user = wp_get_current_user();
        error_log('ChronoForge: Access denied for user ' . $current_user->user_login . ' (ID: ' . $current_user->ID . ')');
        wp_die(__('У вас недостаточно прав для доступа к этой странице.', 'chrono-forge') . '<br><br>Debug: User ID ' . $current_user->ID . ', Login: ' . $current_user->user_login);
    }

    error_log('ChronoForge: Permission check passed, proceeding with diagnostics');

    try {
        // Try to load the full diagnostic system
        if (class_exists('ChronoForge_Admin_Diagnostics')) {
            error_log('ChronoForge: ChronoForge_Admin_Diagnostics class exists, using full system');
            $admin_diagnostics = ChronoForge_Admin_Diagnostics::instance();
            $admin_diagnostics->render_diagnostics_page();
            return;
        }

        error_log('ChronoForge: ChronoForge_Admin_Diagnostics class not found, trying to load files');

        // Try to load diagnostic classes
        $diagnostics_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-diagnostics.php';
        $admin_diagnostics_file = CHRONO_FORGE_PLUGIN_DIR . 'admin/class-chrono-forge-admin-diagnostics.php';

        error_log('ChronoForge: Checking files - Diagnostics: ' . ($diagnostics_file ? 'exists' : 'missing') . ', Admin: ' . ($admin_diagnostics_file ? 'exists' : 'missing'));

        if (file_exists($diagnostics_file) && file_exists($admin_diagnostics_file)) {
            require_once $diagnostics_file;
            require_once $admin_diagnostics_file;

            if (class_exists('ChronoForge_Admin_Diagnostics')) {
                error_log('ChronoForge: Successfully loaded ChronoForge_Admin_Diagnostics class');
                $admin_diagnostics = ChronoForge_Admin_Diagnostics::instance();
                $admin_diagnostics->render_diagnostics_page();
                return;
            } else {
                error_log('ChronoForge: Failed to load ChronoForge_Admin_Diagnostics class after requiring files');
            }
        } else {
            error_log('ChronoForge: Diagnostic files not found - using basic diagnostics');
        }

        // Fallback to basic diagnostics
        error_log('ChronoForge: Using basic diagnostics fallback');
        chrono_forge_render_basic_diagnostics_page();

    } catch (Exception $e) {
        error_log('ChronoForge: Exception in standalone diagnostics: ' . $e->getMessage());
        // Ultimate fallback
        chrono_forge_render_emergency_diagnostics_page($e);
    } catch (Error $e) {
        error_log('ChronoForge: Fatal error in standalone diagnostics: ' . $e->getMessage());
        // Ultimate fallback
        chrono_forge_render_emergency_diagnostics_page($e);
    }
}

/**
 * Render basic diagnostics page
 */
function chrono_forge_render_basic_diagnostics_page() {
    echo '<div class="wrap">';
    echo '<h1>' . __('ChronoForge Diagnostics (Basic Mode)', 'chrono-forge') . '</h1>';

    echo '<div class="notice notice-info"><p>';
    echo __('Running in basic diagnostic mode. Advanced features may not be available.', 'chrono-forge');
    echo '</p></div>';

    // System Information
    echo '<h2>' . __('System Information', 'chrono-forge') . '</h2>';
    echo '<table class="widefat">';
    echo '<tbody>';
    echo '<tr><td><strong>' . __('Plugin Version', 'chrono-forge') . '</strong></td><td>' . CHRONO_FORGE_VERSION . '</td></tr>';
    echo '<tr><td><strong>' . __('WordPress Version', 'chrono-forge') . '</strong></td><td>' . get_bloginfo('version') . '</td></tr>';
    echo '<tr><td><strong>' . __('PHP Version', 'chrono-forge') . '</strong></td><td>' . PHP_VERSION . '</td></tr>';
    echo '<tr><td><strong>' . __('Current User', 'chrono-forge') . '</strong></td><td>' . wp_get_current_user()->user_login . ' (ID: ' . get_current_user_id() . ')</td></tr>';
    echo '<tr><td><strong>' . __('User Capabilities', 'chrono-forge') . '</strong></td><td>' . (current_user_can('manage_options') ? __('Has manage_options', 'chrono-forge') : __('Missing manage_options', 'chrono-forge')) . '</td></tr>';
    echo '<tr><td><strong>' . __('Plugin Directory', 'chrono-forge') . '</strong></td><td>' . CHRONO_FORGE_PLUGIN_DIR . '</td></tr>';
    echo '<tr><td><strong>' . __('Plugin URL', 'chrono-forge') . '</strong></td><td>' . CHRONO_FORGE_PLUGIN_URL . '</td></tr>';
    echo '</tbody></table>';

    // File Integrity Check
    echo '<h2>' . __('File Integrity Check', 'chrono-forge') . '</h2>';
    echo '<table class="widefat">';
    echo '<thead><tr><th>' . __('File', 'chrono-forge') . '</th><th>' . __('Status', 'chrono-forge') . '</th><th>' . __('Size', 'chrono-forge') . '</th></tr></thead>';
    echo '<tbody>';

    $critical_files = array(
        'chrono-forge.php' => 'Main plugin file',
        'includes/class-chrono-forge-core.php' => 'Core class',
        'includes/utils/functions.php' => 'Utility functions',
        'admin/class-chrono-forge-admin-menu.php' => 'Admin menu',
        'includes/class-chrono-forge-diagnostics.php' => 'Diagnostics system',
        'admin/class-chrono-forge-admin-diagnostics.php' => 'Admin diagnostics',
        'admin/views/view-diagnostics.php' => 'Diagnostics view'
    );

    foreach ($critical_files as $file => $description) {
        $file_path = CHRONO_FORGE_PLUGIN_DIR . $file;
        $exists = file_exists($file_path);
        $readable = $exists && is_readable($file_path);
        $size = $exists ? filesize($file_path) : 0;

        echo '<tr>';
        echo '<td>' . esc_html($file) . '<br><small>' . esc_html($description) . '</small></td>';
        echo '<td>';
        if ($exists && $readable) {
            echo '<span style="color: green;">✓ ' . __('OK', 'chrono-forge') . '</span>';
        } elseif ($exists) {
            echo '<span style="color: orange;">⚠ ' . __('Not readable', 'chrono-forge') . '</span>';
        } else {
            echo '<span style="color: red;">✗ ' . __('Missing', 'chrono-forge') . '</span>';
        }
        echo '</td>';
        echo '<td>' . ($size > 0 ? size_format($size) : '-') . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    // WordPress Environment
    echo '<h2>' . __('WordPress Environment', 'chrono-forge') . '</h2>';
    echo '<table class="widefat">';
    echo '<tbody>';
    echo '<tr><td><strong>' . __('Multisite', 'chrono-forge') . '</strong></td><td>' . (is_multisite() ? __('Yes', 'chrono-forge') : __('No', 'chrono-forge')) . '</td></tr>';
    echo '<tr><td><strong>' . __('Debug Mode', 'chrono-forge') . '</strong></td><td>' . (defined('WP_DEBUG') && WP_DEBUG ? __('Enabled', 'chrono-forge') : __('Disabled', 'chrono-forge')) . '</td></tr>';
    echo '<tr><td><strong>' . __('Memory Limit', 'chrono-forge') . '</strong></td><td>' . ini_get('memory_limit') . '</td></tr>';
    echo '<tr><td><strong>' . __('Max Execution Time', 'chrono-forge') . '</strong></td><td>' . ini_get('max_execution_time') . 's</td></tr>';
    echo '<tr><td><strong>' . __('Active Theme', 'chrono-forge') . '</strong></td><td>' . get_template() . '</td></tr>';
    echo '</tbody></table>';

    // Recovery Actions
    echo '<h2>' . __('Recovery Actions', 'chrono-forge') . '</h2>';
    echo '<div class="notice notice-warning"><p>';
    echo __('If you are experiencing issues, try the following:', 'chrono-forge');
    echo '</p></div>';
    echo '<ul>';
    echo '<li>' . __('Deactivate and reactivate the plugin', 'chrono-forge') . '</li>';
    echo '<li>' . __('Check file permissions on the plugin directory', 'chrono-forge') . '</li>';
    echo '<li>' . __('Verify all plugin files are properly uploaded', 'chrono-forge') . '</li>';
    echo '<li>' . __('Check WordPress error logs for detailed error messages', 'chrono-forge') . '</li>';
    echo '<li>' . __('Contact support if issues persist', 'chrono-forge') . '</li>';
    echo '</ul>';

    echo '</div>';
}

/**
 * Render emergency diagnostics page
 */
function chrono_forge_render_emergency_diagnostics_page($error = null) {
    echo '<div class="wrap">';
    echo '<h1>' . __('ChronoForge Emergency Diagnostics', 'chrono-forge') . '</h1>';

    if ($error) {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>' . __('Critical Error:', 'chrono-forge') . '</strong> ';
        echo esc_html($error->getMessage());
        echo '</p></div>';
    }

    echo '<div class="notice notice-error"><p>';
    echo __('The diagnostic system has encountered a critical error and is running in emergency mode.', 'chrono-forge');
    echo '</p></div>';

    // Minimal system info
    echo '<h2>' . __('Emergency System Check', 'chrono-forge') . '</h2>';
    echo '<table class="widefat">';
    echo '<tbody>';
    echo '<tr><td><strong>' . __('Plugin Version', 'chrono-forge') . '</strong></td><td>' . (defined('CHRONO_FORGE_VERSION') ? CHRONO_FORGE_VERSION : 'Unknown') . '</td></tr>';
    echo '<tr><td><strong>' . __('WordPress Version', 'chrono-forge') . '</strong></td><td>' . get_bloginfo('version') . '</td></tr>';
    echo '<tr><td><strong>' . __('PHP Version', 'chrono-forge') . '</strong></td><td>' . PHP_VERSION . '</td></tr>';
    echo '<tr><td><strong>' . __('Current User ID', 'chrono-forge') . '</strong></td><td>' . get_current_user_id() . '</td></tr>';
    echo '<tr><td><strong>' . __('Plugin Directory Exists', 'chrono-forge') . '</strong></td><td>' . (defined('CHRONO_FORGE_PLUGIN_DIR') && is_dir(CHRONO_FORGE_PLUGIN_DIR) ? __('Yes', 'chrono-forge') : __('No', 'chrono-forge')) . '</td></tr>';
    echo '</tbody></table>';

    echo '<h2>' . __('Emergency Recovery', 'chrono-forge') . '</h2>';
    echo '<p>' . __('Please try the following emergency recovery steps:', 'chrono-forge') . '</p>';
    echo '<ol>';
    echo '<li>' . __('Deactivate the ChronoForge plugin immediately', 'chrono-forge') . '</li>';
    echo '<li>' . __('Check the WordPress error logs', 'chrono-forge') . '</li>';
    echo '<li>' . __('Re-upload all plugin files', 'chrono-forge') . '</li>';
    echo '<li>' . __('Reactivate the plugin', 'chrono-forge') . '</li>';
    echo '<li>' . __('Contact support with error details', 'chrono-forge') . '</li>';
    echo '</ol>';

    echo '<p><a href="' . admin_url('plugins.php') . '" class="button button-primary">' . __('Go to Plugins Page', 'chrono-forge') . '</a></p>';

    echo '</div>';
}

/**
 * Add emergency diagnostics JavaScript
 */
function chrono_forge_add_emergency_diagnostics_script() {
    static $script_added = false;

    if ($script_added) {
        return;
    }

    $script_added = true;

    ?>
    <script type="text/javascript">
    function chronoForgeShowEmergencyDiagnostics() {
        var diagnosticsHtml = '<div id="chrono-forge-emergency-diagnostics" style="position: fixed; top: 50px; left: 50px; right: 50px; bottom: 50px; background: white; border: 2px solid #dc3232; z-index: 999999; padding: 20px; overflow: auto;">';
        diagnosticsHtml += '<h2>ChronoForge Emergency Diagnostics</h2>';
        diagnosticsHtml += '<button onclick="document.getElementById(\'chrono-forge-emergency-diagnostics\').remove();" style="float: right; margin-top: -30px;">Close</button>';

        // Basic system info
        diagnosticsHtml += '<h3>System Information</h3>';
        diagnosticsHtml += '<table border="1" cellpadding="5" style="width: 100%; border-collapse: collapse;">';
        diagnosticsHtml += '<tr><td>Plugin Version</td><td><?php echo CHRONO_FORGE_VERSION; ?></td></tr>';
        diagnosticsHtml += '<tr><td>WordPress Version</td><td><?php echo get_bloginfo('version'); ?></td></tr>';
        diagnosticsHtml += '<tr><td>PHP Version</td><td><?php echo PHP_VERSION; ?></td></tr>';
        diagnosticsHtml += '<tr><td>Current User</td><td><?php echo wp_get_current_user()->user_login; ?> (ID: <?php echo get_current_user_id(); ?>)</td></tr>';
        diagnosticsHtml += '<tr><td>User Can Manage Options</td><td><?php echo current_user_can('manage_options') ? 'Yes' : 'No'; ?></td></tr>';
        diagnosticsHtml += '<tr><td>Plugin Directory</td><td><?php echo CHRONO_FORGE_PLUGIN_DIR; ?></td></tr>';
        diagnosticsHtml += '</table>';

        // File check
        diagnosticsHtml += '<h3>Critical Files Check</h3>';
        diagnosticsHtml += '<table border="1" cellpadding="5" style="width: 100%; border-collapse: collapse;">';

        <?php
        $critical_files = array(
            'chrono-forge.php' => 'Main plugin file',
            'includes/class-chrono-forge-core.php' => 'Core class',
            'includes/utils/functions.php' => 'Utility functions',
            'admin/class-chrono-forge-admin-menu.php' => 'Admin menu',
            'includes/class-chrono-forge-diagnostics.php' => 'Diagnostics system'
        );

        foreach ($critical_files as $file => $description) {
            $file_path = CHRONO_FORGE_PLUGIN_DIR . $file;
            $exists = file_exists($file_path);
            $readable = $exists && is_readable($file_path);
            $status = $exists && $readable ? 'OK' : ($exists ? 'Not readable' : 'Missing');
            $color = $exists && $readable ? 'green' : ($exists ? 'orange' : 'red');

            echo "diagnosticsHtml += '<tr><td>{$file}<br><small>{$description}</small></td><td style=\"color: {$color};\">{$status}</td></tr>';";
        }
        ?>

        diagnosticsHtml += '</table>';

        // Recovery suggestions
        diagnosticsHtml += '<h3>Recovery Suggestions</h3>';
        diagnosticsHtml += '<ul>';
        diagnosticsHtml += '<li>Check that all plugin files are properly uploaded</li>';
        diagnosticsHtml += '<li>Verify file permissions on the plugin directory</li>';
        diagnosticsHtml += '<li>Try deactivating and reactivating the plugin</li>';
        diagnosticsHtml += '<li>Check the WordPress error logs for detailed error messages</li>';
        diagnosticsHtml += '<li>Contact support if the issue persists</li>';
        diagnosticsHtml += '</ul>';

        diagnosticsHtml += '</div>';

        document.body.insertAdjacentHTML('beforeend', diagnosticsHtml);
    }
    </script>
    <?php
}

/**
 * Syntax error notice for admin
 */
function chrono_forge_syntax_error_notice() {
    echo '<div class="notice notice-error"><p>';
    echo '<strong>ChronoForge Syntax Error:</strong> ';
    echo __('Обнаружены синтаксические ошибки в файлах плагина. Проверьте логи сервера для получения подробной информации.', 'chrono-forge');

    // Check if diagnostics page is accessible
    if (current_user_can('manage_options')) {
        $emergency_url = admin_url('admin.php?page=chrono-forge-emergency');
        $diagnostics_url = admin_url('admin.php?page=chrono-forge-diagnostics');
        echo ' <a href="' . $emergency_url . '">' . __('Открыть диагностику', 'chrono-forge') . '</a>';
        echo ' | <a href="' . $diagnostics_url . '">' . __('Основная диагностика', 'chrono-forge') . '</a>';
    }

    echo ' | <a href="' . admin_url('plugins.php') . '">' . __('Деактивировать плагин', 'chrono-forge') . '</a>';
    echo ' | <a href="#" onclick="chronoForgeShowEmergencyDiagnostics(); return false;">' . __('Экстренная диагностика', 'chrono-forge') . '</a>';
    echo '</p></div>';

    // Add emergency diagnostics JavaScript
    chrono_forge_add_emergency_diagnostics_script();
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

// Register emergency diagnostics menu early to ensure it's always available
add_action('admin_menu', 'chrono_forge_register_emergency_diagnostics_menu', 5);

/**
 * Register emergency diagnostics menu (fallback)
 */
function chrono_forge_register_emergency_diagnostics_menu() {
    // Always add the emergency diagnostics page as a fallback
    add_menu_page(
        __('ChronoForge Emergency', 'chrono-forge'),
        __('ChronoForge Emergency', 'chrono-forge'),
        'manage_options',
        'chrono-forge-emergency',
        'chrono_forge_standalone_diagnostics_page',
        'dashicons-warning',
        30
    );

    // Also add as submenu for easier access
    add_submenu_page(
        'chrono-forge-emergency',
        __('Диагностика', 'chrono-forge'),
        __('Диагностика', 'chrono-forge'),
        'manage_options',
        'chrono-forge-emergency-diagnostics',
        'chrono_forge_standalone_diagnostics_page'
    );
}
