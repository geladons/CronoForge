<?php
/**
 * ChronoForge Diagnostics View
 *
 * @package ChronoForge
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$admin_diagnostics = ChronoForge_Admin_Diagnostics::instance();
?>

<div class="wrap chrono-forge-diagnostics">
    <h1><?php _e('ChronoForge Diagnostics', 'chrono-forge'); ?></h1>
    
    <!-- Overall Status -->
    <div class="diagnostic-status-overview <?php echo esc_attr($admin_diagnostics->get_overall_status_class($results)); ?>">
        <div class="status-icon">
            <?php if ($results['overall_status'] === 'healthy'): ?>
                <span class="dashicons dashicons-yes-alt"></span>
            <?php elseif ($results['overall_status'] === 'warning'): ?>
                <span class="dashicons dashicons-flag"></span>
            <?php else: ?>
                <span class="dashicons dashicons-warning"></span>
            <?php endif; ?>
        </div>
        <div class="status-content">
            <h2><?php _e('System Status', 'chrono-forge'); ?></h2>
            <p><?php echo esc_html($admin_diagnostics->get_overall_status_message($results)); ?></p>
            <div class="status-summary">
                <?php if ($results['summary']['critical'] > 0): ?>
                    <span class="summary-item critical"><?php printf(__('%d Critical', 'chrono-forge'), $results['summary']['critical']); ?></span>
                <?php endif; ?>
                <?php if ($results['summary']['error'] > 0): ?>
                    <span class="summary-item error"><?php printf(__('%d Errors', 'chrono-forge'), $results['summary']['error']); ?></span>
                <?php endif; ?>
                <?php if ($results['summary']['warning'] > 0): ?>
                    <span class="summary-item warning"><?php printf(__('%d Warnings', 'chrono-forge'), $results['summary']['warning']); ?></span>
                <?php endif; ?>
                <span class="summary-item info"><?php printf(__('%d Total Tests', 'chrono-forge'), $results['summary']['total']); ?></span>
            </div>
        </div>
        <div class="status-actions">
            <button type="button" class="button button-primary" id="run-diagnostics">
                <?php _e('Run Diagnostics', 'chrono-forge'); ?>
            </button>
            <?php if ($system_info['safe_mode_enabled']): ?>
                <button type="button" class="button" id="toggle-safe-mode" data-action="disable">
                    <?php _e('Disable Safe Mode', 'chrono-forge'); ?>
                </button>
            <?php else: ?>
                <button type="button" class="button" id="toggle-safe-mode" data-action="enable">
                    <?php _e('Enable Safe Mode', 'chrono-forge'); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Diagnostic Tests Results -->
    <div class="diagnostic-tests">
        <h2><?php _e('Diagnostic Tests', 'chrono-forge'); ?></h2>
        <div class="diagnostic-tests-grid">
            <?php foreach ($results['tests'] as $test_name => $test_result): ?>
                <div class="diagnostic-test-card <?php echo esc_attr($admin_diagnostics->get_status_class($test_result)); ?>">
                    <div class="test-header">
                        <div class="test-icon">
                            <?php echo $admin_diagnostics->get_status_icon($test_result); ?>
                        </div>
                        <div class="test-title">
                            <h3><?php echo esc_html($admin_diagnostics->format_test_name($test_name)); ?></h3>
                            <p class="test-message"><?php echo esc_html($test_result['message']); ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($test_result['details'])): ?>
                        <div class="test-details">
                            <h4><?php _e('Details:', 'chrono-forge'); ?></h4>
                            <ul>
                                <?php foreach ($test_result['details'] as $detail): ?>
                                    <li><?php echo esc_html($detail); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($test_result['suggestions'])): ?>
                        <div class="test-suggestions">
                            <h4><?php _e('Suggestions:', 'chrono-forge'); ?></h4>
                            <ul>
                                <?php foreach ($test_result['suggestions'] as $suggestion): ?>
                                    <li><?php echo esc_html($suggestion); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- System Information -->
    <div class="system-information">
        <h2><?php _e('System Information', 'chrono-forge'); ?></h2>
        <div class="system-info-grid">
            <div class="info-section">
                <h3><?php _e('WordPress', 'chrono-forge'); ?></h3>
                <table class="widefat">
                    <tr>
                        <td><?php _e('Version', 'chrono-forge'); ?></td>
                        <td><?php echo esc_html($system_info['wordpress_version']); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Multisite', 'chrono-forge'); ?></td>
                        <td><?php echo $system_info['multisite'] ? __('Yes', 'chrono-forge') : __('No', 'chrono-forge'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Debug Mode', 'chrono-forge'); ?></td>
                        <td><?php echo $system_info['debug_mode'] ? __('Enabled', 'chrono-forge') : __('Disabled', 'chrono-forge'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Active Theme', 'chrono-forge'); ?></td>
                        <td><?php echo esc_html($system_info['active_theme']); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="info-section">
                <h3><?php _e('PHP', 'chrono-forge'); ?></h3>
                <table class="widefat">
                    <tr>
                        <td><?php _e('Version', 'chrono-forge'); ?></td>
                        <td><?php echo esc_html($system_info['php_version']); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Memory Limit', 'chrono-forge'); ?></td>
                        <td><?php echo esc_html($system_info['memory_limit']); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Max Execution Time', 'chrono-forge'); ?></td>
                        <td><?php echo esc_html($system_info['max_execution_time']); ?>s</td>
                    </tr>
                    <tr>
                        <td><?php _e('Upload Max Filesize', 'chrono-forge'); ?></td>
                        <td><?php echo esc_html($system_info['upload_max_filesize']); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="info-section">
                <h3><?php _e('Database', 'chrono-forge'); ?></h3>
                <table class="widefat">
                    <tr>
                        <td><?php _e('MySQL Version', 'chrono-forge'); ?></td>
                        <td><?php echo esc_html($system_info['mysql_version']); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="info-section">
                <h3><?php _e('ChronoForge', 'chrono-forge'); ?></h3>
                <table class="widefat">
                    <tr>
                        <td><?php _e('Plugin Version', 'chrono-forge'); ?></td>
                        <td><?php echo esc_html($system_info['plugin_version']); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Safe Mode', 'chrono-forge'); ?></td>
                        <td><?php echo $system_info['safe_mode_enabled'] ? __('Enabled', 'chrono-forge') : __('Disabled', 'chrono-forge'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Error Logs -->
    <div class="error-logs">
        <div class="error-logs-header">
            <h2><?php _e('Recent Error Logs', 'chrono-forge'); ?></h2>
            <button type="button" class="button" id="clear-error-log">
                <?php _e('Clear Error Log', 'chrono-forge'); ?>
            </button>
        </div>
        
        <?php if (!empty($recent_logs)): ?>
            <div class="error-logs-table">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Time', 'chrono-forge'); ?></th>
                            <th><?php _e('Level', 'chrono-forge'); ?></th>
                            <th><?php _e('Message', 'chrono-forge'); ?></th>
                            <th><?php _e('User', 'chrono-forge'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $log->created_at)); ?></td>
                                <td><?php echo $admin_diagnostics->format_log_level('error'); ?></td>
                                <td><?php echo esc_html($log->message); ?></td>
                                <td>
                                    <?php 
                                    if ($log->user_id) {
                                        $user = get_user_by('id', $log->user_id);
                                        echo $user ? esc_html($user->user_login) : __('Unknown', 'chrono-forge');
                                    } else {
                                        echo __('System', 'chrono-forge');
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p><?php _e('No recent error logs found.', 'chrono-forge'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Last Updated -->
    <div class="diagnostic-footer">
        <p class="description">
            <?php printf(__('Last updated: %s', 'chrono-forge'), esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $results['timestamp']))); ?>
        </p>
    </div>
</div>

<!-- Loading overlay -->
<div id="diagnostic-loading" class="diagnostic-loading" style="display: none;">
    <div class="loading-content">
        <div class="spinner is-active"></div>
        <p id="loading-message"><?php _e('Running diagnostics...', 'chrono-forge'); ?></p>
    </div>
</div>
