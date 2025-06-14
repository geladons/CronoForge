<?php
/**
 * Activation test for ChronoForge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test activation process
 */
function chrono_forge_activation_test() {
    echo '<div class="wrap">';
    echo '<h1>ChronoForge Activation Test</h1>';
    
    // Test 1: Check if tables exist
    echo '<h2>Database Tables Check</h2>';
    global $wpdb;
    
    $required_tables = array(
        'chrono_forge_services',
        'chrono_forge_employees',
        'chrono_forge_customers',
        'chrono_forge_appointments',
        'chrono_forge_schedules',
        'chrono_forge_categories',
        'chrono_forge_payments',
        'chrono_forge_employee_services'
    );
    
    echo '<table class="widefat">';
    echo '<thead><tr><th>Table Name</th><th>Exists</th><th>Row Count</th></tr></thead>';
    echo '<tbody>';
    
    $missing_tables = array();
    foreach ($required_tables as $table) {
        $full_table = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") === $full_table;
        
        if ($exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
            echo '<tr><td>' . $table . '</td><td>✓</td><td>' . $count . '</td></tr>';
        } else {
            echo '<tr><td>' . $table . '</td><td>✗</td><td>N/A</td></tr>';
            $missing_tables[] = $table;
        }
    }
    echo '</tbody>';
    echo '</table>';
    
    if (!empty($missing_tables)) {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Missing tables:</strong> ' . implode(', ', $missing_tables);
        echo '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>';
        echo '<strong>All required tables exist!</strong>';
        echo '</p></div>';
    }
    
    // Test 2: Check plugin options
    echo '<h2>Plugin Options Check</h2>';
    $options = array(
        'chrono_forge_version',
        'chrono_forge_tables_created',
        'chrono_forge_activation_date',
        'chrono_forge_settings'
    );
    
    echo '<table class="widefat">';
    echo '<thead><tr><th>Option Name</th><th>Exists</th><th>Value</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($options as $option) {
        $value = get_option($option);
        $exists = $value !== false;
        
        echo '<tr>';
        echo '<td>' . $option . '</td>';
        echo '<td>' . ($exists ? '✓' : '✗') . '</td>';
        echo '<td>';
        if ($exists) {
            if (is_array($value)) {
                echo '<pre>' . esc_html(print_r($value, true)) . '</pre>';
            } else {
                echo esc_html($value);
            }
        } else {
            echo 'Not set';
        }
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    
    // Test 3: Manual activation test
    echo '<h2>Manual Activation Test</h2>';
    echo '<p>Click the button below to manually run the activation process:</p>';
    
    if (isset($_POST['run_activation']) && wp_verify_nonce($_POST['_wpnonce'], 'chrono_forge_manual_activation')) {
        echo '<div class="notice notice-info"><p>Running manual activation...</p></div>';
        
        try {
            // Load activator
            $activator_file = CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-activator.php';
            if (file_exists($activator_file)) {
                require_once $activator_file;
                
                if (class_exists('ChronoForge_Activator')) {
                    ChronoForge_Activator::activate();
                    echo '<div class="notice notice-success"><p>Manual activation completed!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>ChronoForge_Activator class not found</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>Activator file not found</p></div>';
            }
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Activation error: ' . esc_html($e->getMessage()) . '</p></div>';
        }
        
        echo '<p><strong>Refresh this page to see updated results.</strong></p>';
    }
    
    echo '<form method="post">';
    wp_nonce_field('chrono_forge_manual_activation');
    echo '<input type="hidden" name="run_activation" value="1">';
    submit_button('Run Manual Activation', 'primary', 'submit', false);
    echo '</form>';
    
    // Test 4: Check WordPress environment
    echo '<h2>WordPress Environment</h2>';
    echo '<table class="widefat">';
    echo '<tr><td><strong>WordPress Version:</strong></td><td>' . get_bloginfo('version') . '</td></tr>';
    echo '<tr><td><strong>PHP Version:</strong></td><td>' . PHP_VERSION . '</td></tr>';
    echo '<tr><td><strong>MySQL Version:</strong></td><td>' . $wpdb->db_version() . '</td></tr>';
    echo '<tr><td><strong>Database Charset:</strong></td><td>' . $wpdb->charset . '</td></tr>';
    echo '<tr><td><strong>Database Collate:</strong></td><td>' . $wpdb->collate . '</td></tr>';
    echo '<tr><td><strong>Table Prefix:</strong></td><td>' . $wpdb->prefix . '</td></tr>';
    echo '</table>';
    
    // Test 5: Check file permissions
    echo '<h2>File Permissions</h2>';
    $files_to_check = array(
        'chrono-forge.php',
        'includes/class-chrono-forge-activator.php',
        'includes/class-chrono-forge-core.php',
        'includes/class-chrono-forge-db-manager.php',
        'includes/utils/functions.php'
    );
    
    echo '<table class="widefat">';
    echo '<thead><tr><th>File</th><th>Exists</th><th>Readable</th><th>Size</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($files_to_check as $file) {
        $full_path = CHRONO_FORGE_PLUGIN_DIR . $file;
        $exists = file_exists($full_path);
        $readable = $exists ? is_readable($full_path) : false;
        $size = $exists ? filesize($full_path) : 0;
        
        echo '<tr>';
        echo '<td>' . $file . '</td>';
        echo '<td>' . ($exists ? '✓' : '✗') . '</td>';
        echo '<td>' . ($readable ? '✓' : '✗') . '</td>';
        echo '<td>' . ($exists ? number_format($size) . ' bytes' : 'N/A') . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    
    // Test 6: Recent error log entries
    echo '<h2>Recent Error Log Entries</h2>';
    $log_file = ini_get('error_log');
    if ($log_file && file_exists($log_file) && is_readable($log_file)) {
        $log_content = file_get_contents($log_file);
        $lines = explode("\n", $log_content);
        $recent_lines = array_slice($lines, -50); // Last 50 lines
        
        $chrono_lines = array_filter($recent_lines, function($line) {
            return stripos($line, 'chrono') !== false;
        });
        
        if (!empty($chrono_lines)) {
            echo '<pre style="background: #f1f1f1; padding: 10px; max-height: 300px; overflow-y: auto;">';
            echo esc_html(implode("\n", $chrono_lines));
            echo '</pre>';
        } else {
            echo '<p>No recent ChronoForge entries found in error log.</p>';
        }
    } else {
        echo '<p>Error log not available or not readable.</p>';
    }
    
    echo '</div>';
}

// Add admin menu
add_action('admin_menu', function() {
    add_submenu_page(
        null,
        'Activation Test',
        'Activation Test',
        'manage_options',
        'chrono-forge-activation-test',
        'chrono_forge_activation_test'
    );
});

// Add admin notice
add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        $test_url = admin_url('admin.php?page=chrono-forge-activation-test');
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>ChronoForge Activation Test:</strong> ';
        echo '<a href="' . esc_url($test_url) . '">Run Activation Test</a> ';
        echo 'to check database tables and activation status.</p>';
        echo '</div>';
    }
});
