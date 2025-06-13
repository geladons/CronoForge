<?php
/**
 * ChronoForge Diagnostic Utility Functions
 *
 * @package ChronoForge
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced error logging with diagnostic context
 *
 * @param string $message Error message
 * @param string $level Error level (critical, error, warning, info, debug)
 * @param array $context Additional context information
 * @return void
 */
function chrono_forge_safe_log($message, $level = 'info', $context = array()) {
    try {
        // Ensure we have the basic logging function
        if (function_exists('chrono_forge_log')) {
            chrono_forge_log($message, $level, $context);
            return;
        }

        // Fallback logging if main function is not available
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $formatted_message = sprintf(
                "[ChronoForge %s] %s",
                strtoupper($level),
                $message
            );
            
            if (!empty($context)) {
                $formatted_message .= " Context: " . wp_json_encode($context);
            }
            
            error_log($formatted_message);
        }
    } catch (Exception $e) {
        // Ultimate fallback
        error_log("[ChronoForge] Logging error: " . $e->getMessage());
    }
}

/**
 * Check if ChronoForge is in safe mode
 *
 * @return bool True if safe mode is enabled
 */
function chrono_forge_is_safe_mode() {
    return get_option('chrono_forge_safe_mode', false);
}

/**
 * Enhanced syntax checking with detailed error reporting
 *
 * @param string $file_path Path to PHP file to check
 * @return array Array with 'valid' boolean and 'errors' array
 */
function chrono_forge_check_file_syntax($file_path) {
    $result = array(
        'valid' => true,
        'errors' => array()
    );

    if (!file_exists($file_path)) {
        $result['valid'] = false;
        $result['errors'][] = sprintf(__('File not found: %s', 'chrono-forge'), $file_path);
        return $result;
    }

    if (!is_readable($file_path)) {
        $result['valid'] = false;
        $result['errors'][] = sprintf(__('File not readable: %s', 'chrono-forge'), $file_path);
        return $result;
    }

    // Use PHP's built-in syntax checker
    $output = array();
    $return_var = 0;
    $command = sprintf('php -l %s 2>&1', escapeshellarg($file_path));
    exec($command, $output, $return_var);

    if ($return_var !== 0) {
        $result['valid'] = false;
        $result['errors'] = $output;
    }

    return $result;
}

/**
 * Check database table health
 *
 * @param string $table_name Table name (without prefix)
 * @return array Health check results
 */
function chrono_forge_check_table_health($table_name) {
    global $wpdb;
    
    $full_table_name = $wpdb->prefix . $table_name;
    $result = array(
        'exists' => false,
        'accessible' => false,
        'row_count' => 0,
        'errors' => array()
    );

    try {
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $full_table_name));
        
        if ($table_exists) {
            $result['exists'] = true;
            
            // Try to access the table
            $row_count = $wpdb->get_var("SELECT COUNT(*) FROM {$full_table_name}");
            
            if ($row_count !== null) {
                $result['accessible'] = true;
                $result['row_count'] = (int) $row_count;
            } else {
                $result['errors'][] = sprintf(__('Cannot access table: %s', 'chrono-forge'), $full_table_name);
            }
        } else {
            $result['errors'][] = sprintf(__('Table does not exist: %s', 'chrono-forge'), $full_table_name);
        }
    } catch (Exception $e) {
        $result['errors'][] = sprintf(__('Database error for table %s: %s', 'chrono-forge'), $full_table_name, $e->getMessage());
    }

    return $result;
}

/**
 * Get detailed PHP environment information
 *
 * @return array PHP environment details
 */
function chrono_forge_get_php_environment() {
    return array(
        'version' => PHP_VERSION,
        'sapi' => php_sapi_name(),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_input_vars' => ini_get('max_input_vars'),
        'extensions' => get_loaded_extensions(),
        'timezone' => date_default_timezone_get(),
        'error_reporting' => error_reporting(),
        'display_errors' => ini_get('display_errors'),
        'log_errors' => ini_get('log_errors')
    );
}

/**
 * Check WordPress environment health
 *
 * @return array WordPress environment details
 */
function chrono_forge_get_wordpress_environment() {
    global $wp_version, $wpdb;
    
    return array(
        'version' => $wp_version,
        'multisite' => is_multisite(),
        'debug' => defined('WP_DEBUG') && WP_DEBUG,
        'debug_log' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
        'script_debug' => defined('SCRIPT_DEBUG') && SCRIPT_DEBUG,
        'memory_limit' => WP_MEMORY_LIMIT,
        'max_memory_limit' => WP_MAX_MEMORY_LIMIT,
        'language' => get_locale(),
        'timezone' => get_option('timezone_string'),
        'active_theme' => get_template(),
        'active_plugins' => get_option('active_plugins', array()),
        'mysql_version' => $wpdb->db_version(),
        'wp_upload_dir' => wp_upload_dir(),
        'home_url' => home_url(),
        'site_url' => site_url()
    );
}

/**
 * Perform emergency recovery actions
 *
 * @param array $actions Array of recovery actions to perform
 * @return array Results of recovery actions
 */
function chrono_forge_emergency_recovery($actions = array()) {
    $results = array();
    
    if (empty($actions)) {
        $actions = array('enable_safe_mode', 'clear_cache', 'reset_options');
    }
    
    foreach ($actions as $action) {
        switch ($action) {
            case 'enable_safe_mode':
                $results[$action] = update_option('chrono_forge_safe_mode', true);
                break;
                
            case 'clear_cache':
                $results[$action] = chrono_forge_clear_all_cache();
                break;
                
            case 'reset_options':
                $results[$action] = chrono_forge_reset_critical_options();
                break;
                
            case 'recreate_tables':
                $results[$action] = chrono_forge_recreate_database_tables();
                break;
                
            default:
                $results[$action] = false;
                break;
        }
    }
    
    return $results;
}

/**
 * Clear all ChronoForge cache
 *
 * @return bool Success status
 */
function chrono_forge_clear_all_cache() {
    try {
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_chrono_forge_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_chrono_forge_%'");
        
        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        return true;
    } catch (Exception $e) {
        chrono_forge_safe_log('Failed to clear cache: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Reset critical plugin options to defaults
 *
 * @return bool Success status
 */
function chrono_forge_reset_critical_options() {
    try {
        $critical_options = array(
            'chrono_forge_safe_mode' => false,
            'chrono_forge_debug_mode' => false,
            'chrono_forge_error_notifications' => true
        );
        
        foreach ($critical_options as $option => $default_value) {
            update_option($option, $default_value);
        }
        
        return true;
    } catch (Exception $e) {
        chrono_forge_safe_log('Failed to reset options: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Recreate database tables
 *
 * @return bool Success status
 */
function chrono_forge_recreate_database_tables() {
    try {
        if (class_exists('ChronoForge_Activator')) {
            // Use the activator to recreate tables
            require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-activator.php';
            ChronoForge_Activator::create_tables();
            return true;
        }
        return false;
    } catch (Exception $e) {
        chrono_forge_safe_log('Failed to recreate tables: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Generate diagnostic report
 *
 * @return string Diagnostic report content
 */
function chrono_forge_generate_diagnostic_report() {
    $diagnostics = ChronoForge_Diagnostics::instance();
    $results = $diagnostics->run_diagnostics(true);
    $system_info = $diagnostics->get_system_info();
    
    $report = "ChronoForge Diagnostic Report\n";
    $report .= "Generated: " . current_time('Y-m-d H:i:s') . "\n";
    $report .= "Plugin Version: " . CHRONO_FORGE_VERSION . "\n";
    $report .= str_repeat("=", 50) . "\n\n";
    
    // Overall status
    $report .= "OVERALL STATUS: " . strtoupper($results['overall_status']) . "\n";
    $report .= "Critical: " . $results['summary']['critical'] . "\n";
    $report .= "Errors: " . $results['summary']['error'] . "\n";
    $report .= "Warnings: " . $results['summary']['warning'] . "\n";
    $report .= "Total Tests: " . $results['summary']['total'] . "\n\n";
    
    // Test results
    $report .= "TEST RESULTS:\n";
    $report .= str_repeat("-", 30) . "\n";
    
    foreach ($results['tests'] as $test_name => $test_result) {
        $report .= strtoupper($test_name) . ": " . strtoupper($test_result['severity']) . "\n";
        $report .= "Message: " . $test_result['message'] . "\n";
        
        if (!empty($test_result['details'])) {
            $report .= "Details:\n";
            foreach ($test_result['details'] as $detail) {
                $report .= "  - " . $detail . "\n";
            }
        }
        $report .= "\n";
    }
    
    // System information
    $report .= "SYSTEM INFORMATION:\n";
    $report .= str_repeat("-", 30) . "\n";
    $report .= "WordPress: " . $system_info['wordpress_version'] . "\n";
    $report .= "PHP: " . $system_info['php_version'] . "\n";
    $report .= "MySQL: " . $system_info['mysql_version'] . "\n";
    $report .= "Memory Limit: " . $system_info['memory_limit'] . "\n";
    $report .= "Safe Mode: " . ($system_info['safe_mode_enabled'] ? 'Enabled' : 'Disabled') . "\n";
    $report .= "Debug Mode: " . ($system_info['debug_mode'] ? 'Enabled' : 'Disabled') . "\n";
    
    return $report;
}
