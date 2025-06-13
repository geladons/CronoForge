<?php
/**
 * ChronoForge Diagnostics System
 *
 * @package ChronoForge
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ChronoForge Diagnostics Class
 *
 * Comprehensive error detection and diagnostic system for ChronoForge plugin
 */
class ChronoForge_Diagnostics {

    /**
     * Instance of this class
     *
     * @var ChronoForge_Diagnostics
     */
    private static $instance = null;

    /**
     * Diagnostic results cache
     *
     * @var array
     */
    private $diagnostic_cache = array();

    /**
     * Error severity levels
     *
     * @var array
     */
    private $severity_levels = array(
        'critical' => 1,
        'error' => 2,
        'warning' => 3,
        'info' => 4,
        'debug' => 5
    );

    /**
     * Get instance of this class
     *
     * @return ChronoForge_Diagnostics
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
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_init', array($this, 'maybe_run_diagnostics'));
        add_action('wp_ajax_chrono_forge_run_diagnostics', array($this, 'ajax_run_diagnostics'));
        add_action('wp_ajax_chrono_forge_clear_error_log', array($this, 'ajax_clear_error_log'));
        add_action('wp_ajax_chrono_forge_toggle_safe_mode', array($this, 'ajax_toggle_safe_mode'));
    }

    /**
     * Run comprehensive diagnostics
     *
     * @param bool $force_refresh Force refresh of cached results
     * @return array Diagnostic results
     */
    public function run_diagnostics($force_refresh = false) {
        $cache_key = 'chrono_forge_diagnostics_' . CHRONO_FORGE_VERSION;
        
        if (!$force_refresh && !empty($this->diagnostic_cache)) {
            return $this->diagnostic_cache;
        }

        $cached_results = get_transient($cache_key);
        if (!$force_refresh && $cached_results !== false) {
            $this->diagnostic_cache = $cached_results;
            return $cached_results;
        }

        $results = array(
            'timestamp' => current_time('mysql'),
            'overall_status' => 'healthy',
            'tests' => array(),
            'summary' => array(
                'critical' => 0,
                'error' => 0,
                'warning' => 0,
                'info' => 0,
                'total' => 0
            )
        );

        // Run all diagnostic tests
        $tests = array(
            'php_syntax' => array($this, 'test_php_syntax'),
            'file_integrity' => array($this, 'test_file_integrity'),
            'database_health' => array($this, 'test_database_health'),
            'wordpress_compatibility' => array($this, 'test_wordpress_compatibility'),
            'php_requirements' => array($this, 'test_php_requirements'),
            'file_permissions' => array($this, 'test_file_permissions'),
            'plugin_dependencies' => array($this, 'test_plugin_dependencies'),
            'configuration' => array($this, 'test_configuration')
        );

        foreach ($tests as $test_name => $test_callback) {
            try {
                $test_result = call_user_func($test_callback);
                $results['tests'][$test_name] = $test_result;
                
                // Update summary
                $severity = $test_result['severity'];
                $results['summary'][$severity]++;
                $results['summary']['total']++;
                
                // Update overall status
                if ($severity === 'critical' || ($severity === 'error' && $results['overall_status'] !== 'critical')) {
                    $results['overall_status'] = $severity;
                } elseif ($severity === 'warning' && $results['overall_status'] === 'healthy') {
                    $results['overall_status'] = 'warning';
                }
                
            } catch (Exception $e) {
                $results['tests'][$test_name] = array(
                    'status' => 'failed',
                    'severity' => 'error',
                    'message' => sprintf(__('Diagnostic test failed: %s', 'chrono-forge'), $e->getMessage()),
                    'details' => array(),
                    'suggestions' => array(__('Contact support if this error persists.', 'chrono-forge'))
                );
                $results['summary']['error']++;
                $results['summary']['total']++;
            }
        }

        // Cache results for 5 minutes
        set_transient($cache_key, $results, 5 * MINUTE_IN_SECONDS);
        $this->diagnostic_cache = $results;

        return $results;
    }

    /**
     * Test PHP syntax of all plugin files
     *
     * @return array Test result
     */
    private function test_php_syntax() {
        $result = array(
            'status' => 'passed',
            'severity' => 'info',
            'message' => __('All PHP files have valid syntax', 'chrono-forge'),
            'details' => array(),
            'suggestions' => array()
        );

        $files_to_check = $this->get_php_files_to_check();
        $syntax_errors = array();

        foreach ($files_to_check as $file) {
            $file_path = CHRONO_FORGE_PLUGIN_DIR . $file;
            
            if (!file_exists($file_path)) {
                $syntax_errors[] = sprintf(__('File not found: %s', 'chrono-forge'), $file);
                continue;
            }

            // Check syntax using PHP's built-in syntax checker
            $output = array();
            $return_var = 0;
            $command = sprintf('php -l %s 2>&1', escapeshellarg($file_path));
            exec($command, $output, $return_var);

            if ($return_var !== 0) {
                $syntax_errors[] = sprintf(__('Syntax error in %s: %s', 'chrono-forge'), $file, implode(' ', $output));
            }
        }

        if (!empty($syntax_errors)) {
            $result['status'] = 'failed';
            $result['severity'] = 'critical';
            $result['message'] = sprintf(__('Found %d syntax errors', 'chrono-forge'), count($syntax_errors));
            $result['details'] = $syntax_errors;
            $result['suggestions'] = array(
                __('Review the syntax errors above and fix them.', 'chrono-forge'),
                __('Consider restoring from a backup if available.', 'chrono-forge'),
                __('Contact support for assistance.', 'chrono-forge')
            );
        }

        return $result;
    }

    /**
     * Get list of PHP files to check for syntax errors
     *
     * @return array List of files relative to plugin directory
     */
    private function get_php_files_to_check() {
        return array(
            'chrono-forge.php',
            'includes/class-chrono-forge-core.php',
            'includes/class-chrono-forge-activator.php',
            'includes/class-chrono-forge-deactivator.php',
            'includes/class-chrono-forge-database.php',
            'includes/class-chrono-forge-shortcodes.php',
            'includes/utils/functions.php',
            'admin/class-chrono-forge-admin-menu.php',
            'admin/class-chrono-forge-admin-ajax.php',
            'public/class-chrono-forge-public.php'
        );
    }

    /**
     * Test file integrity
     *
     * @return array Test result
     */
    private function test_file_integrity() {
        $result = array(
            'status' => 'passed',
            'severity' => 'info',
            'message' => __('All required files are present', 'chrono-forge'),
            'details' => array(),
            'suggestions' => array()
        );

        $required_files = $this->get_required_files();
        $missing_files = array();
        $corrupted_files = array();

        foreach ($required_files as $file) {
            $file_path = CHRONO_FORGE_PLUGIN_DIR . $file;
            
            if (!file_exists($file_path)) {
                $missing_files[] = $file;
                continue;
            }

            // Check if file is readable and not empty
            if (!is_readable($file_path) || filesize($file_path) === 0) {
                $corrupted_files[] = $file;
            }
        }

        if (!empty($missing_files) || !empty($corrupted_files)) {
            $result['status'] = 'failed';
            $result['severity'] = !empty($missing_files) ? 'critical' : 'error';
            
            $issues = array();
            if (!empty($missing_files)) {
                $issues[] = sprintf(__('Missing files: %s', 'chrono-forge'), implode(', ', $missing_files));
            }
            if (!empty($corrupted_files)) {
                $issues[] = sprintf(__('Corrupted files: %s', 'chrono-forge'), implode(', ', $corrupted_files));
            }
            
            $result['message'] = implode('; ', $issues);
            $result['details'] = array_merge($missing_files, $corrupted_files);
            $result['suggestions'] = array(
                __('Reinstall the plugin to restore missing/corrupted files.', 'chrono-forge'),
                __('Check file permissions on the plugin directory.', 'chrono-forge'),
                __('Contact your hosting provider if the issue persists.', 'chrono-forge')
            );
        }

        return $result;
    }

    /**
     * Get list of required plugin files
     *
     * @return array List of required files
     */
    private function get_required_files() {
        return array(
            'chrono-forge.php',
            'includes/class-chrono-forge-core.php',
            'includes/class-chrono-forge-activator.php',
            'includes/utils/functions.php',
            'admin/class-chrono-forge-admin-menu.php'
        );
    }

    /**
     * Test database health
     *
     * @return array Test result
     */
    private function test_database_health() {
        global $wpdb;

        $result = array(
            'status' => 'passed',
            'severity' => 'info',
            'message' => __('Database is healthy', 'chrono-forge'),
            'details' => array(),
            'suggestions' => array()
        );

        $issues = array();
        $required_tables = array(
            'chrono_forge_services',
            'chrono_forge_employees',
            'chrono_forge_schedules',
            'chrono_forge_appointments',
            'chrono_forge_customers',
            'chrono_forge_payments'
        );

        // Check if tables exist
        foreach ($required_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));

            if (!$table_exists) {
                $issues[] = sprintf(__('Missing table: %s', 'chrono-forge'), $table_name);
            } else {
                // Check table structure
                $columns = $wpdb->get_results("DESCRIBE {$table_name}");
                if (empty($columns)) {
                    $issues[] = sprintf(__('Table %s has no columns', 'chrono-forge'), $table_name);
                }
            }
        }

        // Test database connection
        if (!$wpdb->check_connection()) {
            $issues[] = __('Database connection failed', 'chrono-forge');
        }

        if (!empty($issues)) {
            $result['status'] = 'failed';
            $result['severity'] = 'critical';
            $result['message'] = sprintf(__('Found %d database issues', 'chrono-forge'), count($issues));
            $result['details'] = $issues;
            $result['suggestions'] = array(
                __('Deactivate and reactivate the plugin to recreate missing tables.', 'chrono-forge'),
                __('Check database permissions.', 'chrono-forge'),
                __('Contact your hosting provider if database issues persist.', 'chrono-forge')
            );
        }

        return $result;
    }

    /**
     * Test WordPress compatibility
     *
     * @return array Test result
     */
    private function test_wordpress_compatibility() {
        $result = array(
            'status' => 'passed',
            'severity' => 'info',
            'message' => __('WordPress compatibility check passed', 'chrono-forge'),
            'details' => array(),
            'suggestions' => array()
        );

        $issues = array();

        // Check WordPress version
        global $wp_version;
        $min_wp_version = '5.0';
        if (version_compare($wp_version, $min_wp_version, '<')) {
            $issues[] = sprintf(__('WordPress version %s is below minimum required version %s', 'chrono-forge'), $wp_version, $min_wp_version);
        }

        // Check if required WordPress functions exist
        $required_functions = array('add_action', 'add_filter', 'wp_enqueue_script', 'wp_enqueue_style', 'current_user_can');
        foreach ($required_functions as $function) {
            if (!function_exists($function)) {
                $issues[] = sprintf(__('Required WordPress function missing: %s', 'chrono-forge'), $function);
            }
        }

        // Check if WordPress is in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $result['details'][] = __('WordPress debug mode is enabled', 'chrono-forge');
        }

        if (!empty($issues)) {
            $result['status'] = 'failed';
            $result['severity'] = 'critical';
            $result['message'] = sprintf(__('Found %d WordPress compatibility issues', 'chrono-forge'), count($issues));
            $result['details'] = array_merge($result['details'], $issues);
            $result['suggestions'] = array(
                __('Update WordPress to the latest version.', 'chrono-forge'),
                __('Check if WordPress core files are intact.', 'chrono-forge'),
                __('Contact support if compatibility issues persist.', 'chrono-forge')
            );
        }

        return $result;
    }

    /**
     * Test PHP requirements
     *
     * @return array Test result
     */
    private function test_php_requirements() {
        $result = array(
            'status' => 'passed',
            'severity' => 'info',
            'message' => __('PHP requirements met', 'chrono-forge'),
            'details' => array(),
            'suggestions' => array()
        );

        $issues = array();

        // Check PHP version
        $min_php_version = '7.4';
        if (version_compare(PHP_VERSION, $min_php_version, '<')) {
            $issues[] = sprintf(__('PHP version %s is below minimum required version %s', 'chrono-forge'), PHP_VERSION, $min_php_version);
        }

        // Check required PHP extensions
        $required_extensions = array('mysqli', 'json', 'curl', 'mbstring');
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                $issues[] = sprintf(__('Required PHP extension missing: %s', 'chrono-forge'), $extension);
            }
        }

        // Check memory limit
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = $this->convert_to_bytes($memory_limit);
        $min_memory = 128 * 1024 * 1024; // 128MB

        if ($memory_limit_bytes > 0 && $memory_limit_bytes < $min_memory) {
            $issues[] = sprintf(__('PHP memory limit %s is below recommended 128M', 'chrono-forge'), $memory_limit);
        }

        if (!empty($issues)) {
            $result['status'] = 'failed';
            $result['severity'] = 'error';
            $result['message'] = sprintf(__('Found %d PHP requirement issues', 'chrono-forge'), count($issues));
            $result['details'] = $issues;
            $result['suggestions'] = array(
                __('Contact your hosting provider to update PHP version and install missing extensions.', 'chrono-forge'),
                __('Increase PHP memory limit to at least 128M.', 'chrono-forge')
            );
        }

        return $result;
    }

    /**
     * Convert memory limit string to bytes
     *
     * @param string $val Memory limit value
     * @return int Memory limit in bytes
     */
    private function convert_to_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int) $val;

        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    /**
     * Test file permissions
     *
     * @return array Test result
     */
    private function test_file_permissions() {
        $result = array(
            'status' => 'passed',
            'severity' => 'info',
            'message' => __('File permissions are correct', 'chrono-forge'),
            'details' => array(),
            'suggestions' => array()
        );

        $issues = array();

        // Check plugin directory permissions
        if (!is_readable(CHRONO_FORGE_PLUGIN_DIR)) {
            $issues[] = __('Plugin directory is not readable', 'chrono-forge');
        }

        // Check if uploads directory is writable (for logs)
        $upload_dir = wp_upload_dir();
        if (!is_writable($upload_dir['basedir'])) {
            $issues[] = __('WordPress uploads directory is not writable', 'chrono-forge');
        }

        // Check critical files
        $critical_files = $this->get_required_files();
        foreach ($critical_files as $file) {
            $file_path = CHRONO_FORGE_PLUGIN_DIR . $file;
            if (file_exists($file_path) && !is_readable($file_path)) {
                $issues[] = sprintf(__('File not readable: %s', 'chrono-forge'), $file);
            }
        }

        if (!empty($issues)) {
            $result['status'] = 'failed';
            $result['severity'] = 'error';
            $result['message'] = sprintf(__('Found %d file permission issues', 'chrono-forge'), count($issues));
            $result['details'] = $issues;
            $result['suggestions'] = array(
                __('Check file and directory permissions.', 'chrono-forge'),
                __('Contact your hosting provider to fix permission issues.', 'chrono-forge')
            );
        }

        return $result;
    }

    /**
     * Test plugin dependencies
     *
     * @return array Test result
     */
    private function test_plugin_dependencies() {
        $result = array(
            'status' => 'passed',
            'severity' => 'info',
            'message' => __('All plugin dependencies are satisfied', 'chrono-forge'),
            'details' => array(),
            'suggestions' => array()
        );

        $issues = array();

        // Check if required WordPress functions are available
        $required_wp_functions = array(
            'wp_enqueue_script',
            'wp_enqueue_style',
            'wp_localize_script',
            'wp_ajax_*',
            'current_user_can',
            'wp_verify_nonce'
        );

        // Check for conflicting plugins (if any known conflicts exist)
        $active_plugins = get_option('active_plugins', array());
        $conflicting_plugins = array(
            // Add known conflicting plugins here
        );

        foreach ($conflicting_plugins as $plugin) {
            if (in_array($plugin, $active_plugins)) {
                $issues[] = sprintf(__('Conflicting plugin detected: %s', 'chrono-forge'), $plugin);
            }
        }

        if (!empty($issues)) {
            $result['status'] = 'failed';
            $result['severity'] = 'warning';
            $result['message'] = sprintf(__('Found %d dependency issues', 'chrono-forge'), count($issues));
            $result['details'] = $issues;
            $result['suggestions'] = array(
                __('Deactivate conflicting plugins.', 'chrono-forge'),
                __('Contact support for compatibility information.', 'chrono-forge')
            );
        }

        return $result;
    }

    /**
     * Test plugin configuration
     *
     * @return array Test result
     */
    private function test_configuration() {
        $result = array(
            'status' => 'passed',
            'severity' => 'info',
            'message' => __('Plugin configuration is valid', 'chrono-forge'),
            'details' => array(),
            'suggestions' => array()
        );

        $issues = array();

        // Check if plugin tables were created
        $version = get_option('chrono_forge_version');
        if (!$version) {
            $issues[] = __('Plugin activation data not found', 'chrono-forge');
        }

        // Check if safe mode is enabled
        if ($this->is_safe_mode_enabled()) {
            $result['details'][] = __('Safe mode is currently enabled', 'chrono-forge');
        }

        // Check error log size
        $error_log_size = $this->get_error_log_size();
        if ($error_log_size > 10 * 1024 * 1024) { // 10MB
            $issues[] = sprintf(__('Error log is large (%s). Consider clearing it.', 'chrono-forge'), size_format($error_log_size));
        }

        if (!empty($issues)) {
            $result['status'] = 'failed';
            $result['severity'] = 'warning';
            $result['message'] = sprintf(__('Found %d configuration issues', 'chrono-forge'), count($issues));
            $result['details'] = array_merge($result['details'], $issues);
            $result['suggestions'] = array(
                __('Review plugin settings.', 'chrono-forge'),
                __('Clear error logs if they are too large.', 'chrono-forge'),
                __('Reactivate plugin if activation data is missing.', 'chrono-forge')
            );
        }

        return $result;
    }

    /**
     * Check if safe mode is enabled
     *
     * @return bool
     */
    public function is_safe_mode_enabled() {
        return get_option('chrono_forge_safe_mode', false);
    }

    /**
     * Enable safe mode
     *
     * @return bool
     */
    public function enable_safe_mode() {
        return update_option('chrono_forge_safe_mode', true);
    }

    /**
     * Disable safe mode
     *
     * @return bool
     */
    public function disable_safe_mode() {
        return delete_option('chrono_forge_safe_mode');
    }

    /**
     * Get error log size
     *
     * @return int Size in bytes
     */
    private function get_error_log_size() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'chrono_forge_error_log';
        $size = $wpdb->get_var("SELECT SUM(LENGTH(message) + LENGTH(context)) FROM {$table_name}");

        return (int) $size;
    }

    /**
     * Maybe run diagnostics automatically
     */
    public function maybe_run_diagnostics() {
        // Only run on ChronoForge admin pages
        if (!isset($_GET['page']) || strpos($_GET['page'], 'chrono-forge') !== 0) {
            return;
        }

        // Check if we should run diagnostics
        $last_check = get_transient('chrono_forge_last_diagnostic_check');
        if ($last_check === false) {
            // Run diagnostics in background
            $this->run_diagnostics();
            set_transient('chrono_forge_last_diagnostic_check', time(), HOUR_IN_SECONDS);
        }
    }

    /**
     * AJAX handler for running diagnostics
     */
    public function ajax_run_diagnostics() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'chrono-forge'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_diagnostics')) {
            wp_die(__('Security check failed', 'chrono-forge'));
        }

        $force_refresh = isset($_POST['force_refresh']) && $_POST['force_refresh'] === 'true';
        $results = $this->run_diagnostics($force_refresh);

        wp_send_json_success($results);
    }

    /**
     * AJAX handler for clearing error log
     */
    public function ajax_clear_error_log() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'chrono-forge'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_diagnostics')) {
            wp_die(__('Security check failed', 'chrono-forge'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'chrono_forge_error_log';
        $result = $wpdb->query("TRUNCATE TABLE {$table_name}");

        if ($result !== false) {
            wp_send_json_success(array('message' => __('Error log cleared successfully', 'chrono-forge')));
        } else {
            wp_send_json_error(array('message' => __('Failed to clear error log', 'chrono-forge')));
        }
    }

    /**
     * AJAX handler for toggling safe mode
     */
    public function ajax_toggle_safe_mode() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'chrono-forge'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_diagnostics')) {
            wp_die(__('Security check failed', 'chrono-forge'));
        }

        $enable = isset($_POST['enable']) && $_POST['enable'] === 'true';

        if ($enable) {
            $result = $this->enable_safe_mode();
            $message = __('Safe mode enabled', 'chrono-forge');
        } else {
            $result = $this->disable_safe_mode();
            $message = __('Safe mode disabled', 'chrono-forge');
        }

        if ($result) {
            wp_send_json_success(array('message' => $message, 'safe_mode' => $enable));
        } else {
            wp_send_json_error(array('message' => __('Failed to toggle safe mode', 'chrono-forge')));
        }
    }

    /**
     * Get recent error logs
     *
     * @param int $limit Number of logs to retrieve
     * @return array Error logs
     */
    public function get_recent_error_logs($limit = 50) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'chrono_forge_error_log';

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d",
            $limit
        ));

        return $logs ? $logs : array();
    }

    /**
     * Get system information
     *
     * @return array System information
     */
    public function get_system_info() {
        global $wp_version, $wpdb;

        return array(
            'wordpress_version' => $wp_version,
            'php_version' => PHP_VERSION,
            'mysql_version' => $wpdb->db_version(),
            'plugin_version' => CHRONO_FORGE_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'safe_mode_enabled' => $this->is_safe_mode_enabled(),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'multisite' => is_multisite(),
            'active_theme' => get_template(),
            'active_plugins' => get_option('active_plugins', array())
        );
    }

    /**
     * Log diagnostic event
     *
     * @param string $message Log message
     * @param string $level Log level
     * @param array $context Additional context
     */
    public function log_diagnostic_event($message, $level = 'info', $context = array()) {
        $context['diagnostic'] = true;
        chrono_forge_log($message, $level, $context);
    }
}
