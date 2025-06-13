<?php
/**
 * Шаблон управления записями
 * 
 * @var array $appointments
 * @var array $employees
 * @var array $services
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="chrono-forge-admin">
    <div class="cf-page-title">
        <h1><?php _e('Записи', 'chrono-forge'); ?></h1>
        <div>
            <a href="<?php echo chrono_forge_get_admin_url('calendar'); ?>" class="cf-btn">
                <?php _e('Календарь', 'chrono-forge'); ?>
            </a>
            <a href="#" class="cf-btn cf-btn-primary" data-modal="cf-new-appointment-modal">
                <?php _e('Новая запись', 'chrono-forge'); ?>
            </a>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="cf-filters">
        <form method="get">
            <input type="hidden" name="page" value="chrono-forge-appointments">
            <div class="cf-filters-row">
                <div class="cf-form-group">
                    <label for="filter_date_from"><?php _e('Дата с', 'chrono-forge'); ?></label>
                    <input type="date" id="filter_date_from" name="date_from" 
                           value="<?php echo esc_attr($_GET['date_from'] ?? ''); ?>" class="cf-filter">
                </div>
                <div class="cf-form-group">
                    <label for="filter_date_to"><?php _e('Дата по', 'chrono-forge'); ?></label>
                    <input type="date" id="filter_date_to" name="date_to" 
                           value="<?php echo esc_attr($_GET['date_to'] ?? ''); ?>" class="cf-filter">
                </div>
                <div class="cf-form-group">
                    <label for="filter_employee"><?php _e('Специалист', 'chrono-forge'); ?></label>
                    <select id="filter_employee" name="employee" class="cf-filter">
                        <option value=""><?php _e('Все специалисты', 'chrono-forge'); ?></option>
                        <?php foreach ($employees as $employee): ?>
                        <option value="<?php echo esc_attr($employee->id); ?>" 
                                <?php selected($_GET['employee'] ?? '', $employee->id); ?>>
                            <?php echo esc_html($employee->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cf-form-group">
                    <label for="filter_service"><?php _e('Услуга', 'chrono-forge'); ?></label>
                    <select id="filter_service" name="service" class="cf-filter">
                        <option value=""><?php _e('Все услуги', 'chrono-forge'); ?></option>
                        <?php foreach ($services as $service): ?>
                        <option value="<?php echo esc_attr($service->id); ?>" 
                                <?php selected($_GET['service'] ?? '', $service->id); ?>>
                            <?php echo esc_html($service->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cf-form-group">
                    <label for="filter_status"><?php _e('Статус', 'chrono-forge'); ?></label>
                    <select id="filter_status" name="status" class="cf-filter">
                        <option value=""><?php _e('Все статусы', 'chrono-forge'); ?></option>
                        <?php foreach (chrono_forge_get_appointment_statuses() as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" 
                                <?php selected($_GET['status'] ?? '', $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cf-form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="cf-btn"><?php _e('Применить', 'chrono-forge'); ?></button>
                </div>
            </div>
        </form>
    </div>

    <!-- Таблица записей -->
    <div class="cf-table-container">
        <?php if (!empty($appointments)): ?>
        <table class="cf-table">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" class="cf-select-all">
                    </th>
                    <th><?php _e('Клиент', 'chrono-forge'); ?></th>
                    <th><?php _e('Услуга', 'chrono-forge'); ?></th>
                    <th><?php _e('Специалист', 'chrono-forge'); ?></th>
                    <th><?php _e('Дата', 'chrono-forge'); ?></th>
                    <th><?php _e('Время', 'chrono-forge'); ?></th>
                    <th><?php _e('Статус', 'chrono-forge'); ?></th>
                    <th><?php _e('Стоимость', 'chrono-forge'); ?></th>
                    <th><?php _e('Действия', 'chrono-forge'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td>
                        <input type="checkbox" class="cf-item-checkbox" value="<?php echo esc_attr($appointment->id); ?>">
                    </td>
                    <td>
                        <div>
                            <strong><?php echo esc_html($appointment->customer_name); ?></strong>
                            <br><small><?php echo esc_html($appointment->customer_email); ?></small>
                            <?php if (!empty($appointment->customer_phone)): ?>
                            <br><small><?php echo esc_html($appointment->customer_phone); ?></small>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="cf-color-indicator" style="background-color: <?php echo esc_attr($appointment->service_color); ?>;"></span>
                            <div>
                                <?php echo esc_html($appointment->service_name); ?>
                                <?php if (!empty($appointment->service_duration)): ?>
                                <br><small><?php echo esc_html($appointment->service_duration); ?> <?php _e('мин.', 'chrono-forge'); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="cf-color-indicator" style="background-color: <?php echo esc_attr($appointment->employee_color); ?>;"></span>
                            <?php echo esc_html($appointment->employee_name); ?>
                        </div>
                    </td>
                    <td>
                        <?php echo chrono_forge_format_date($appointment->appointment_date, 'd.m.Y'); ?>
                        <br><small><?php echo date('l', strtotime($appointment->appointment_date)); ?></small>
                    </td>
                    <td>
                        <strong><?php echo chrono_forge_format_time($appointment->appointment_time); ?></strong>
                        <?php if (!empty($appointment->end_time)): ?>
                        <br><small><?php _e('до', 'chrono-forge'); ?> <?php echo chrono_forge_format_time($appointment->end_time); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="cf-status <?php echo esc_attr($appointment->status); ?>">
                            <?php echo esc_html(chrono_forge_get_appointment_statuses()[$appointment->status] ?? $appointment->status); ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($appointment->total_price) && $appointment->total_price > 0): ?>
                            <?php echo chrono_forge_format_price($appointment->total_price); ?>
                        <?php else: ?>
                            <span style="color: #999;">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="cf-actions">
                        <a href="#" class="cf-btn" data-modal="cf-edit-appointment-modal" 
                           data-id="<?php echo esc_attr($appointment->id); ?>" data-type="appointment">
                            <?php _e('Редактировать', 'chrono-forge'); ?>
                        </a>
                        
                        <?php if ($appointment->status === 'pending'): ?>
                        <a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'confirm_appointment', 'id' => $appointment->id]), 'confirm_appointment'); ?>" 
                           class="cf-btn cf-btn-success">
                            <?php _e('Подтвердить', 'chrono-forge'); ?>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (in_array($appointment->status, ['pending', 'confirmed'])): ?>
                        <a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'cancel_appointment', 'id' => $appointment->id]), 'cancel_appointment'); ?>" 
                           class="cf-btn cf-btn-secondary">
                            <?php _e('Отменить', 'chrono-forge'); ?>
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
        
        <!-- Массовые действия -->
        <div style="padding: 15px; border-top: 1px solid #eee; background: #f8f9fa;">
            <strong><?php _e('Массовые действия:', 'chrono-forge'); ?></strong>
            <button type="button" class="cf-btn cf-bulk-action" data-action="confirm">
                <?php _e('Подтвердить', 'chrono-forge'); ?>
            </button>
            <button type="button" class="cf-btn cf-bulk-action" data-action="cancel">
                <?php _e('Отменить', 'chrono-forge'); ?>
            </button>
            <button type="button" class="cf-btn cf-bulk-action" data-action="complete">
                <?php _e('Завершить', 'chrono-forge'); ?>
            </button>
            <button type="button" class="cf-btn cf-btn-danger cf-bulk-action" data-action="delete">
                <?php _e('Удалить', 'chrono-forge'); ?>
            </button>
        </div>
        <?php else: ?>
        <div style="padding: 40px; text-align: center; color: #666;">
            <p><?php _e('Записи не найдены.', 'chrono-forge'); ?></p>
            <a href="#" class="cf-btn cf-btn-primary" data-modal="cf-new-appointment-modal">
                <?php _e('Создать первую запись', 'chrono-forge'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Статистика -->
    <div style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
            <div style="font-size: 24px; font-weight: 600; color: #f39c12; margin-bottom: 5px;">
                <?php echo count(array_filter($appointments, function($a) { return $a->status === 'pending'; })); ?>
            </div>
            <div style="color: #666; font-size: 13px;"><?php _e('Ожидают подтверждения', 'chrono-forge'); ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
            <div style="font-size: 24px; font-weight: 600; color: #27ae60; margin-bottom: 5px;">
                <?php echo count(array_filter($appointments, function($a) { return $a->status === 'confirmed'; })); ?>
            </div>
            <div style="color: #666; font-size: 13px;"><?php _e('Подтверждены', 'chrono-forge'); ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
            <div style="font-size: 24px; font-weight: 600; color: #3498db; margin-bottom: 5px;">
                <?php echo count(array_filter($appointments, function($a) { return $a->status === 'completed'; })); ?>
            </div>
            <div style="color: #666; font-size: 13px;"><?php _e('Завершены', 'chrono-forge'); ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
            <div style="font-size: 24px; font-weight: 600; color: #e74c3c; margin-bottom: 5px;">
                <?php echo count(array_filter($appointments, function($a) { return $a->status === 'cancelled'; })); ?>
            </div>
            <div style="color: #666; font-size: 13px;"><?php _e('Отменены', 'chrono-forge'); ?></div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования записи -->
<div id="cf-edit-appointment-modal" class="cf-modal" style="display: none;">
    <div class="cf-modal-content">
        <div class="cf-modal-header">
            <h3 class="cf-modal-title"><?php _e('Редактировать запись', 'chrono-forge'); ?></h3>
            <button type="button" class="cf-modal-close">&times;</button>
        </div>
        
        <form class="cf-admin-form" method="post" action="">
            <?php wp_nonce_field('chrono_forge_admin_action'); ?>
            <input type="hidden" name="action" value="save_appointment">
            <input type="hidden" name="appointment_id" id="edit_appointment_id">
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="edit_appointment_date"><?php _e('Дата', 'chrono-forge'); ?> *</label>
                    <input type="date" id="edit_appointment_date" name="appointment_date" required>
                </div>
                <div class="cf-form-group">
                    <label for="edit_appointment_time"><?php _e('Время', 'chrono-forge'); ?> *</label>
                    <input type="time" id="edit_appointment_time" name="appointment_time" required>
                </div>
            </div>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="edit_status"><?php _e('Статус', 'chrono-forge'); ?></label>
                    <select id="edit_status" name="status">
                        <?php foreach (chrono_forge_get_appointment_statuses() as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cf-form-group">
                    <label for="edit_total_price"><?php _e('Стоимость', 'chrono-forge'); ?></label>
                    <input type="number" id="edit_total_price" name="total_price" min="0" step="0.01">
                </div>
            </div>
            
            <div class="cf-form-group">
                <label for="edit_notes"><?php _e('Комментарий клиента', 'chrono-forge'); ?></label>
                <textarea id="edit_notes" name="notes" rows="3"></textarea>
            </div>
            
            <div class="cf-form-group">
                <label for="edit_internal_notes"><?php _e('Внутренние заметки', 'chrono-forge'); ?></label>
                <textarea id="edit_internal_notes" name="internal_notes" rows="3"></textarea>
            </div>
            
            <div class="cf-modal-footer">
                <button type="button" class="cf-btn cf-btn-secondary cf-modal-close">
                    <?php _e('Отмена', 'chrono-forge'); ?>
                </button>
                <button type="submit" class="cf-btn cf-btn-primary">
                    <?php _e('Сохранить изменения', 'chrono-forge'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
