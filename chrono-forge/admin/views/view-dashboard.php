<?php
/**
 * Шаблон дашборда админ-панели
 * 
 * @var array $stats
 * @var array $recent_appointments
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="chrono-forge-admin">
    <div class="cf-page-title">
        <h1><?php _e('ChronoForge - Дашборд', 'chrono-forge'); ?></h1>
        <div>
            <a href="<?php echo chrono_forge_get_admin_url('calendar'); ?>" class="cf-btn">
                <?php _e('Календарь', 'chrono-forge'); ?>
            </a>
            <a href="#" class="cf-btn cf-btn-success" data-modal="cf-new-appointment-modal">
                <?php _e('Новая запись', 'chrono-forge'); ?>
            </a>
        </div>
    </div>

    <!-- Статистика -->
    <div class="cf-stats-grid">
        <div class="cf-stat-card revenue">
            <div class="cf-stat-value"><?php echo chrono_forge_format_price($stats['monthly_revenue']); ?></div>
            <div class="cf-stat-label"><?php _e('Доход за месяц', 'chrono-forge'); ?></div>
        </div>
        
        <div class="cf-stat-card appointments">
            <div class="cf-stat-value"><?php echo number_format($stats['monthly_appointments']); ?></div>
            <div class="cf-stat-label"><?php _e('Записей за месяц', 'chrono-forge'); ?></div>
        </div>
        
        <div class="cf-stat-card customers">
            <div class="cf-stat-value"><?php echo number_format($stats['total_customers']); ?></div>
            <div class="cf-stat-label"><?php _e('Всего клиентов', 'chrono-forge'); ?></div>
        </div>
        
        <div class="cf-stat-card today">
            <div class="cf-stat-value"><?php echo number_format($stats['today_appointments']); ?></div>
            <div class="cf-stat-label"><?php _e('Записей на сегодня', 'chrono-forge'); ?></div>
        </div>
    </div>

    <!-- Последние записи -->
    <div class="cf-table-container">
        <div style="padding: 20px; border-bottom: 1px solid #eee;">
            <h2 style="margin: 0; font-size: 18px; font-weight: 600;">
                <?php _e('Последние записи', 'chrono-forge'); ?>
            </h2>
        </div>
        
        <?php if (!empty($recent_appointments)): ?>
        <table class="cf-table">
            <thead>
                <tr>
                    <th><?php _e('Клиент', 'chrono-forge'); ?></th>
                    <th><?php _e('Услуга', 'chrono-forge'); ?></th>
                    <th><?php _e('Специалист', 'chrono-forge'); ?></th>
                    <th><?php _e('Дата', 'chrono-forge'); ?></th>
                    <th><?php _e('Время', 'chrono-forge'); ?></th>
                    <th><?php _e('Статус', 'chrono-forge'); ?></th>
                    <th><?php _e('Действия', 'chrono-forge'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_appointments as $appointment): ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($appointment->customer_name); ?></strong><br>
                        <small><?php echo esc_html($appointment->customer_email); ?></small>
                    </td>
                    <td>
                        <?php echo esc_html($appointment->service_name); ?>
                        <?php if (!empty($appointment->service_duration)): ?>
                        <br><small><?php echo esc_html($appointment->service_duration); ?> <?php _e('мин.', 'chrono-forge'); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="cf-color-indicator" style="background-color: <?php echo esc_attr($appointment->employee_color); ?>;"></span>
                        <?php echo esc_html($appointment->employee_name); ?>
                    </td>
                    <td><?php echo chrono_forge_format_date($appointment->appointment_date, 'd.m.Y'); ?></td>
                    <td><?php echo chrono_forge_format_time($appointment->appointment_time); ?></td>
                    <td>
                        <span class="cf-status <?php echo esc_attr($appointment->status); ?>">
                            <?php echo esc_html(chrono_forge_get_appointment_statuses()[$appointment->status] ?? $appointment->status); ?>
                        </span>
                    </td>
                    <td class="cf-actions">
                        <a href="#" class="cf-btn" data-modal="cf-edit-appointment-modal" data-id="<?php echo esc_attr($appointment->id); ?>" data-type="appointment">
                            <?php _e('Редактировать', 'chrono-forge'); ?>
                        </a>
                        
                        <?php if ($appointment->status === 'pending'): ?>
                        <a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'confirm_appointment', 'id' => $appointment->id]), 'confirm_appointment'); ?>" 
                           class="cf-btn cf-btn-success">
                            <?php _e('Подтвердить', 'chrono-forge'); ?>
                        </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'delete_appointment', 'id' => $appointment->id]), 'delete_appointment'); ?>" 
                           class="cf-btn cf-btn-danger cf-delete-item" 
                           data-name="<?php echo esc_attr($appointment->customer_name . ' - ' . $appointment->service_name); ?>">
                            <?php _e('Удалить', 'chrono-forge'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="padding: 40px; text-align: center; color: #666;">
            <p><?php _e('Записей пока нет.', 'chrono-forge'); ?></p>
            <a href="#" class="cf-btn cf-btn-primary" data-modal="cf-new-appointment-modal">
                <?php _e('Создать первую запись', 'chrono-forge'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Быстрые действия -->
    <div style="margin-top: 30px;">
        <h2><?php _e('Быстрые действия', 'chrono-forge'); ?></h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
            <a href="<?php echo chrono_forge_get_admin_url('services'); ?>" class="cf-btn" style="padding: 15px; text-align: center;">
                <div style="font-size: 16px; margin-bottom: 5px;">📋</div>
                <?php _e('Управление услугами', 'chrono-forge'); ?>
            </a>
            
            <a href="<?php echo chrono_forge_get_admin_url('employees'); ?>" class="cf-btn" style="padding: 15px; text-align: center;">
                <div style="font-size: 16px; margin-bottom: 5px;">👥</div>
                <?php _e('Управление сотрудниками', 'chrono-forge'); ?>
            </a>
            
            <a href="<?php echo chrono_forge_get_admin_url('customers'); ?>" class="cf-btn" style="padding: 15px; text-align: center;">
                <div style="font-size: 16px; margin-bottom: 5px;">👤</div>
                <?php _e('База клиентов', 'chrono-forge'); ?>
            </a>
            
            <a href="<?php echo chrono_forge_get_admin_url('settings'); ?>" class="cf-btn" style="padding: 15px; text-align: center;">
                <div style="font-size: 16px; margin-bottom: 5px;">⚙️</div>
                <?php _e('Настройки', 'chrono-forge'); ?>
            </a>
        </div>
    </div>
</div>

<!-- Модальное окно новой записи -->
<div id="cf-new-appointment-modal" class="cf-modal" style="display: none;">
    <div class="cf-modal-content">
        <div class="cf-modal-header">
            <h3 class="cf-modal-title"><?php _e('Новая запись', 'chrono-forge'); ?></h3>
            <button type="button" class="cf-modal-close">&times;</button>
        </div>
        
        <form class="cf-admin-form" method="post" action="">
            <?php wp_nonce_field('chrono_forge_admin_action'); ?>
            <input type="hidden" name="action" value="save_appointment">
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="customer_id"><?php _e('Клиент', 'chrono-forge'); ?> *</label>
                    <select id="customer_id" name="customer_id" required>
                        <option value=""><?php _e('Выберите клиента', 'chrono-forge'); ?></option>
                        <!-- Опции будут загружены через AJAX -->
                    </select>
                </div>
                <div class="cf-form-group">
                    <label for="service_id"><?php _e('Услуга', 'chrono-forge'); ?> *</label>
                    <select id="service_id" name="service_id" required>
                        <option value=""><?php _e('Выберите услугу', 'chrono-forge'); ?></option>
                        <!-- Опции будут загружены через AJAX -->
                    </select>
                </div>
            </div>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="employee_id"><?php _e('Специалист', 'chrono-forge'); ?> *</label>
                    <select id="employee_id" name="employee_id" required>
                        <option value=""><?php _e('Выберите специалиста', 'chrono-forge'); ?></option>
                        <!-- Опции будут загружены через AJAX -->
                    </select>
                </div>
                <div class="cf-form-group">
                    <label for="status"><?php _e('Статус', 'chrono-forge'); ?></label>
                    <select id="status" name="status">
                        <?php foreach (chrono_forge_get_appointment_statuses() as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($key, 'confirmed'); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="appointment_date"><?php _e('Дата', 'chrono-forge'); ?> *</label>
                    <input type="date" id="appointment_date" name="appointment_date" required 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="cf-form-group">
                    <label for="appointment_time"><?php _e('Время', 'chrono-forge'); ?> *</label>
                    <input type="time" id="appointment_time" name="appointment_time" required>
                </div>
            </div>
            
            <div class="cf-form-group">
                <label for="notes"><?php _e('Комментарий', 'chrono-forge'); ?></label>
                <textarea id="notes" name="notes" rows="3"></textarea>
            </div>
            
            <div class="cf-modal-footer">
                <button type="button" class="cf-btn cf-btn-secondary cf-modal-close">
                    <?php _e('Отмена', 'chrono-forge'); ?>
                </button>
                <button type="submit" class="cf-btn cf-btn-primary">
                    <?php _e('Создать запись', 'chrono-forge'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Загрузка данных для селектов при открытии модального окна
    $('#cf-new-appointment-modal').on('show', function() {
        // Загружаем клиентов
        $.ajax({
            url: chronoForgeAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'chrono_forge_get_customers_list',
                nonce: chronoForgeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const $select = $('#customer_id');
                    $select.find('option:not(:first)').remove();
                    response.data.forEach(function(customer) {
                        $select.append('<option value="' + customer.id + '">' + customer.name + ' (' + customer.email + ')</option>');
                    });
                }
            }
        });
        
        // Загружаем услуги
        $.ajax({
            url: chronoForgeAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'chrono_forge_get_services_list',
                nonce: chronoForgeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const $select = $('#service_id');
                    $select.find('option:not(:first)').remove();
                    response.data.forEach(function(service) {
                        $select.append('<option value="' + service.id + '">' + service.name + '</option>');
                    });
                }
            }
        });
        
        // Загружаем сотрудников
        $.ajax({
            url: chronoForgeAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'chrono_forge_get_employees_list',
                nonce: chronoForgeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const $select = $('#employee_id');
                    $select.find('option:not(:first)').remove();
                    response.data.forEach(function(employee) {
                        $select.append('<option value="' + employee.id + '">' + employee.name + '</option>');
                    });
                }
            }
        });
    });
});
</script>
