<?php
/**
 * ChronoForge Admin Diagnostics
 *
 * @package ChronoForge
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ChronoForge Admin Diagnostics Class
 *
 * Handles the admin interface for the diagnostics system
 */
class ChronoForge_Admin_Diagnostics {

    /**
     * Instance of this class
     *
     * @var ChronoForge_Admin_Diagnostics
     */
    private static $instance = null;

    /**
     * Diagnostics engine instance
     *
     * @var ChronoForge_Diagnostics
     */
    private $diagnostics;

    /**
     * Get instance of this class
     *
     * @return ChronoForge_Admin_Diagnostics
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
        $this->diagnostics = ChronoForge_Diagnostics::instance();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'show_diagnostic_notices'));
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_scripts($hook) {
        // Only load on ChronoForge pages
        if (strpos($hook, 'chrono-forge') === false) {
            return;
        }

        wp_enqueue_script(
            'chrono-forge-diagnostics',
            CHRONO_FORGE_PLUGIN_URL . 'assets/js/admin-diagnostics.js',
            array('jquery'),
            CHRONO_FORGE_VERSION,
            true
        );

        wp_enqueue_style(
            'chrono-forge-diagnostics',
            CHRONO_FORGE_PLUGIN_URL . 'assets/css/admin-diagnostics.css',
            array(),
            CHRONO_FORGE_VERSION
        );

        // Localize script
        wp_localize_script('chrono-forge-diagnostics', 'chronoForgeDiagnostics', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chrono_forge_diagnostics'),
            'strings' => array(
                'runningDiagnostics' => __('Running diagnostics...', 'chrono-forge'),
                'diagnosticsComplete' => __('Diagnostics complete', 'chrono-forge'),
                'clearingLog' => __('Clearing error log...', 'chrono-forge'),
                'logCleared' => __('Error log cleared', 'chrono-forge'),
                'toggleSafeMode' => __('Toggling safe mode...', 'chrono-forge'),
                'safeModeToggled' => __('Safe mode toggled', 'chrono-forge'),
                'error' => __('Error', 'chrono-forge'),
                'confirmClearLog' => __('Are you sure you want to clear the error log?', 'chrono-forge'),
                'confirmToggleSafeMode' => __('Are you sure you want to toggle safe mode?', 'chrono-forge')
            )
        ));
    }

    /**
     * Show diagnostic notices in admin
     */
    public function show_diagnostic_notices() {
        // Only show on ChronoForge pages
        if (!isset($_GET['page']) || strpos($_GET['page'], 'chrono-forge') !== 0) {
            return;
        }

        // Check if there are critical issues
        $results = $this->diagnostics->run_diagnostics();
        
        if ($results['overall_status'] === 'critical') {
            $critical_count = $results['summary']['critical'];
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>' . __('ChronoForge Critical Issues Detected', 'chrono-forge') . '</strong></p>';
            echo '<p>' . sprintf(_n('Found %d critical issue that needs immediate attention.', 'Found %d critical issues that need immediate attention.', $critical_count, 'chrono-forge'), $critical_count) . '</p>';
            echo '<p><a href="' . admin_url('admin.php?page=chrono-forge-diagnostics') . '" class="button button-primary">' . __('View Diagnostics', 'chrono-forge') . '</a></p>';
            echo '</div>';
        } elseif ($results['overall_status'] === 'error') {
            $error_count = $results['summary']['error'];
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . __('ChronoForge Issues Detected', 'chrono-forge') . '</strong></p>';
            echo '<p>' . sprintf(_n('Found %d error that should be addressed.', 'Found %d errors that should be addressed.', $error_count, 'chrono-forge'), $error_count) . '</p>';
            echo '<p><a href="' . admin_url('admin.php?page=chrono-forge-diagnostics') . '" class="button">' . __('View Diagnostics', 'chrono-forge') . '</a></p>';
            echo '</div>';
        }

        // Show safe mode notice
        if ($this->diagnostics->is_safe_mode_enabled()) {
            echo '<div class="notice notice-info">';
            echo '<p><strong>' . __('ChronoForge Safe Mode Active', 'chrono-forge') . '</strong></p>';
            echo '<p>' . __('The plugin is running in safe mode. Some features may be disabled.', 'chrono-forge') . '</p>';
            echo '<p><a href="' . admin_url('admin.php?page=chrono-forge-diagnostics') . '" class="button">' . __('Manage Safe Mode', 'chrono-forge') . '</a></p>';
            echo '</div>';
        }
    }

    /**
     * Render diagnostics page
     */
    public function render_diagnostics_page() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'chrono-forge'));
        }

        $results = $this->diagnostics->run_diagnostics();
        $system_info = $this->diagnostics->get_system_info();
        $recent_logs = $this->diagnostics->get_recent_error_logs(20);

        include CHRONO_FORGE_PLUGIN_DIR . 'admin/views/view-diagnostics.php';
    }

    /**
     * Get status icon for diagnostic result
     *
     * @param array $result Diagnostic result
     * @return string HTML for status icon
     */
    public function get_status_icon($result) {
        $icons = array(
            'critical' => '<span class="dashicons dashicons-warning" style="color: #dc3232;"></span>',
            'error' => '<span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>',
            'warning' => '<span class="dashicons dashicons-flag" style="color: #ffb900;"></span>',
            'info' => '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>'
        );

        return isset($icons[$result['severity']]) ? $icons[$result['severity']] : $icons['info'];
    }

    /**
     * Get status class for diagnostic result
     *
     * @param array $result Diagnostic result
     * @return string CSS class
     */
    public function get_status_class($result) {
        $classes = array(
            'critical' => 'diagnostic-critical',
            'error' => 'diagnostic-error',
            'warning' => 'diagnostic-warning',
            'info' => 'diagnostic-success'
        );

        return isset($classes[$result['severity']]) ? $classes[$result['severity']] : $classes['info'];
    }

    /**
     * Format diagnostic test name for display
     *
     * @param string $test_name Test name
     * @return string Formatted test name
     */
    public function format_test_name($test_name) {
        $names = array(
            'php_syntax' => __('PHP Syntax Check', 'chrono-forge'),
            'file_integrity' => __('File Integrity', 'chrono-forge'),
            'database_health' => __('Database Health', 'chrono-forge'),
            'wordpress_compatibility' => __('WordPress Compatibility', 'chrono-forge'),
            'php_requirements' => __('PHP Requirements', 'chrono-forge'),
            'file_permissions' => __('File Permissions', 'chrono-forge'),
            'plugin_dependencies' => __('Plugin Dependencies', 'chrono-forge'),
            'configuration' => __('Configuration', 'chrono-forge')
        );

        return isset($names[$test_name]) ? $names[$test_name] : ucwords(str_replace('_', ' ', $test_name));
    }

    /**
     * Format log level for display
     *
     * @param string $level Log level
     * @return string Formatted level with styling
     */
    public function format_log_level($level) {
        $levels = array(
            'critical' => '<span class="log-level log-critical">' . __('Critical', 'chrono-forge') . '</span>',
            'error' => '<span class="log-level log-error">' . __('Error', 'chrono-forge') . '</span>',
            'warning' => '<span class="log-level log-warning">' . __('Warning', 'chrono-forge') . '</span>',
            'info' => '<span class="log-level log-info">' . __('Info', 'chrono-forge') . '</span>',
            'debug' => '<span class="log-level log-debug">' . __('Debug', 'chrono-forge') . '</span>'
        );

        return isset($levels[$level]) ? $levels[$level] : $level;
    }

    /**
     * Get overall status message
     *
     * @param array $results Diagnostic results
     * @return string Status message
     */
    public function get_overall_status_message($results) {
        switch ($results['overall_status']) {
            case 'critical':
                return __('Critical issues detected that require immediate attention.', 'chrono-forge');
            case 'error':
                return __('Errors detected that should be addressed.', 'chrono-forge');
            case 'warning':
                return __('Minor issues detected that should be reviewed.', 'chrono-forge');
            default:
                return __('All systems are functioning normally.', 'chrono-forge');
        }
    }

    /**
     * Get overall status class
     *
     * @param array $results Diagnostic results
     * @return string CSS class
     */
    public function get_overall_status_class($results) {
        $classes = array(
            'critical' => 'status-critical',
            'error' => 'status-error',
            'warning' => 'status-warning',
            'healthy' => 'status-healthy'
        );

        return isset($classes[$results['overall_status']]) ? $classes[$results['overall_status']] : $classes['healthy'];
    }
}
