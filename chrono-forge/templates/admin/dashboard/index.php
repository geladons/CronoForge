<?php
/**
 * Dashboard Index Template
 * 
 * @package ChronoForge
 * @var array $stats
 * @var array $recent_appointments
 * @var array $upcoming_appointments
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap chrono-forge-dashboard">
    <h1><?php _e('ChronoForge Dashboard', 'chrono-forge'); ?></h1>

    <?php if (isset($message)): ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="chrono-forge-stats-grid">
        <div class="chrono-forge-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_appointments']); ?></h3>
                <p><?php _e('Total Appointments', 'chrono-forge'); ?></p>
            </div>
        </div>

        <div class="chrono-forge-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['today_appointments']); ?></h3>
                <p><?php _e('Today\'s Appointments', 'chrono-forge'); ?></p>
            </div>
        </div>

        <div class="chrono-forge-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['pending_appointments']); ?></h3>
                <p><?php _e('Pending Appointments', 'chrono-forge'); ?></p>
            </div>
        </div>

        <div class="chrono-forge-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_customers']); ?></h3>
                <p><?php _e('Total Customers', 'chrono-forge'); ?></p>
            </div>
        </div>

        <div class="chrono-forge-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-tools"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_services']); ?></h3>
                <p><?php _e('Active Services', 'chrono-forge'); ?></p>
            </div>
        </div>

        <div class="chrono-forge-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_employees']); ?></h3>
                <p><?php _e('Active Employees', 'chrono-forge'); ?></p>
            </div>
        </div>

        <div class="chrono-forge-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo \ChronoForge\get_option('currency_symbol', '$') . number_format($stats['revenue_today'], 2); ?></h3>
                <p><?php _e('Today\'s Revenue', 'chrono-forge'); ?></p>
            </div>
        </div>

        <div class="chrono-forge-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo \ChronoForge\get_option('currency_symbol', '$') . number_format($stats['revenue_month'], 2); ?></h3>
                <p><?php _e('This Month\'s Revenue', 'chrono-forge'); ?></p>
            </div>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="chrono-forge-dashboard-content">
        <!-- Upcoming Appointments -->
        <div class="chrono-forge-dashboard-section">
            <div class="section-header">
                <h2><?php _e('Upcoming Appointments', 'chrono-forge'); ?></h2>
                <a href="<?php echo \ChronoForge\admin_url('appointments'); ?>" class="button">
                    <?php _e('View All', 'chrono-forge'); ?>
                </a>
            </div>

            <?php if (!empty($upcoming_appointments)): ?>
                <div class="chrono-forge-appointments-list">
                    <?php foreach ($upcoming_appointments as $appointment): ?>
                        <div class="appointment-item">
                            <div class="appointment-time">
                                <strong><?php echo \ChronoForge\format_date($appointment->start_datetime); ?></strong><br>
                                <span><?php echo \ChronoForge\format_time($appointment->start_datetime); ?></span>
                            </div>
                            <div class="appointment-details">
                                <h4><?php echo esc_html($appointment->service_name); ?></h4>
                                <p>
                                    <strong><?php _e('Customer:', 'chrono-forge'); ?></strong> 
                                    <?php echo esc_html($appointment->customer_name); ?>
                                </p>
                                <p>
                                    <strong><?php _e('Employee:', 'chrono-forge'); ?></strong> 
                                    <?php echo esc_html($appointment->employee_name); ?>
                                </p>
                            </div>
                            <div class="appointment-status">
                                <span class="status-badge status-<?php echo esc_attr($appointment->status); ?>">
                                    <?php echo esc_html(ucfirst($appointment->status)); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-items">
                    <p><?php _e('No upcoming appointments found.', 'chrono-forge'); ?></p>
                    <a href="<?php echo \ChronoForge\admin_url('appointments'); ?>" class="button button-primary">
                        <?php _e('Create New Appointment', 'chrono-forge'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="chrono-forge-dashboard-section">
            <div class="section-header">
                <h2><?php _e('Recent Activity', 'chrono-forge'); ?></h2>
            </div>

            <?php if (!empty($recent_appointments)): ?>
                <div class="chrono-forge-activity-list">
                    <?php foreach (array_slice($recent_appointments, 0, 5) as $appointment): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <span class="dashicons dashicons-calendar-alt"></span>
                            </div>
                            <div class="activity-content">
                                <p>
                                    <strong><?php echo esc_html($appointment->customer_name); ?></strong>
                                    <?php _e('booked', 'chrono-forge'); ?>
                                    <strong><?php echo esc_html($appointment->service_name); ?></strong>
                                    <?php _e('with', 'chrono-forge'); ?>
                                    <strong><?php echo esc_html($appointment->employee_name); ?></strong>
                                </p>
                                <small class="activity-time">
                                    <?php echo human_time_diff(strtotime($appointment->created_at), current_time('timestamp')); ?>
                                    <?php _e('ago', 'chrono-forge'); ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-items">
                    <p><?php _e('No recent activity found.', 'chrono-forge'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="chrono-forge-quick-actions">
        <h2><?php _e('Quick Actions', 'chrono-forge'); ?></h2>
        <div class="quick-actions-grid">
            <a href="<?php echo \ChronoForge\admin_url('appointments'); ?>" class="quick-action-card">
                <span class="dashicons dashicons-plus-alt"></span>
                <span><?php _e('New Appointment', 'chrono-forge'); ?></span>
            </a>
            <a href="<?php echo \ChronoForge\admin_url('customers'); ?>" class="quick-action-card">
                <span class="dashicons dashicons-admin-users"></span>
                <span><?php _e('Add Customer', 'chrono-forge'); ?></span>
            </a>
            <a href="<?php echo \ChronoForge\admin_url('services'); ?>" class="quick-action-card">
                <span class="dashicons dashicons-admin-tools"></span>
                <span><?php _e('Manage Services', 'chrono-forge'); ?></span>
            </a>
            <a href="<?php echo \ChronoForge\admin_url('calendar'); ?>" class="quick-action-card">
                <span class="dashicons dashicons-calendar-alt"></span>
                <span><?php _e('View Calendar', 'chrono-forge'); ?></span>
            </a>
        </div>
    </div>
</div>
