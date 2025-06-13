<?php
/**
 * Шаблон управления сотрудниками
 * 
 * @var array $employees
 * @var array $services
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}

$weekdays = chrono_forge_get_weekdays();
?>

<div class="chrono-forge-admin">
    <div class="cf-page-title">
        <h1><?php _e('Сотрудники', 'chrono-forge'); ?></h1>
        <div>
            <a href="#" class="cf-btn cf-btn-primary" data-modal="cf-new-employee-modal">
                <?php _e('Новый сотрудник', 'chrono-forge'); ?>
            </a>
        </div>
    </div>

    <!-- Таблица сотрудников -->
    <div class="cf-table-container">
        <?php if (!empty($employees)): ?>
        <table class="cf-table">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" class="cf-select-all">
                    </th>
                    <th><?php _e('Сотрудник', 'chrono-forge'); ?></th>
                    <th><?php _e('Контакты', 'chrono-forge'); ?></th>
                    <th><?php _e('Услуги', 'chrono-forge'); ?></th>
                    <th><?php _e('Статус', 'chrono-forge'); ?></th>
                    <th><?php _e('Действия', 'chrono-forge'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $employee): ?>
                <tr>
                    <td>
                        <input type="checkbox" class="cf-item-checkbox" value="<?php echo esc_attr($employee->id); ?>">
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php if (!empty($employee->photo)): ?>
                            <img src="<?php echo esc_url($employee->photo); ?>" alt="<?php echo esc_attr($employee->name); ?>" 
                                 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo esc_attr($employee->color); ?>; 
                                        color: white; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                <?php echo esc_html(mb_substr($employee->name, 0, 1)); ?>
                            </div>
                            <?php endif; ?>
                            <div>
                                <strong><?php echo esc_html($employee->name); ?></strong>
                                <?php if (!empty($employee->description)): ?>
                                <br><small><?php echo esc_html(wp_trim_words($employee->description, 8)); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>
                            <div><?php echo esc_html($employee->email); ?></div>
                            <?php if (!empty($employee->phone)): ?>
                            <small><?php echo esc_html($employee->phone); ?></small>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php
                        $employee_services = chrono_forge()->db_manager->get_employee_services($employee->id);
                        if (!empty($employee_services)):
                        ?>
                        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                            <?php foreach (array_slice($employee_services, 0, 3) as $service): ?>
                            <span style="background: <?php echo esc_attr($service->color); ?>; color: white; 
                                         padding: 2px 6px; border-radius: 10px; font-size: 11px;">
                                <?php echo esc_html($service->name); ?>
                            </span>
                            <?php endforeach; ?>
                            <?php if (count($employee_services) > 3): ?>
                            <span style="color: #666; font-size: 11px;">
                                +<?php echo count($employee_services) - 3; ?> <?php _e('еще', 'chrono-forge'); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <span style="color: #999;"><?php _e('Нет услуг', 'chrono-forge'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="cf-status <?php echo esc_attr($employee->status); ?>">
                            <?php echo $employee->status === 'active' ? __('Активен', 'chrono-forge') : __('Неактивен', 'chrono-forge'); ?>
                        </span>
                    </td>
                    <td class="cf-actions">
                        <a href="#" class="cf-btn" data-modal="cf-edit-employee-modal" 
                           data-id="<?php echo esc_attr($employee->id); ?>" data-type="employee">
                            <?php _e('Редактировать', 'chrono-forge'); ?>
                        </a>
                        <a href="#" class="cf-btn cf-btn-secondary" data-modal="cf-schedule-modal" 
                           data-id="<?php echo esc_attr($employee->id); ?>" data-type="employee">
                            <?php _e('График', 'chrono-forge'); ?>
                        </a>
                        <a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'delete_employee', 'id' => $employee->id]), 'delete_employee'); ?>" 
                           class="cf-btn cf-btn-danger cf-delete-item" 
                           data-name="<?php echo esc_attr($employee->name); ?>">
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
            <button type="button" class="cf-btn cf-bulk-action" data-action="activate">
                <?php _e('Активировать', 'chrono-forge'); ?>
            </button>
            <button type="button" class="cf-btn cf-bulk-action" data-action="deactivate">
                <?php _e('Деактивировать', 'chrono-forge'); ?>
            </button>
            <button type="button" class="cf-btn cf-btn-danger cf-bulk-action" data-action="delete">
                <?php _e('Удалить', 'chrono-forge'); ?>
            </button>
        </div>
        <?php else: ?>
        <div style="padding: 40px; text-align: center; color: #666;">
            <p><?php _e('Сотрудники не найдены.', 'chrono-forge'); ?></p>
            <a href="#" class="cf-btn cf-btn-primary" data-modal="cf-new-employee-modal">
                <?php _e('Добавить первого сотрудника', 'chrono-forge'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно нового/редактирования сотрудника -->
<div id="cf-new-employee-modal" class="cf-modal" style="display: none;">
    <div class="cf-modal-content">
        <div class="cf-modal-header">
            <h3 class="cf-modal-title"><?php _e('Новый сотрудник', 'chrono-forge'); ?></h3>
            <button type="button" class="cf-modal-close">&times;</button>
        </div>
        
        <form class="cf-admin-form" method="post" action="">
            <?php wp_nonce_field('chrono_forge_admin_action'); ?>
            <input type="hidden" name="action" value="save_employee">
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="employee_name"><?php _e('Имя', 'chrono-forge'); ?> *</label>
                    <input type="text" id="employee_name" name="name" required>
                </div>
                <div class="cf-form-group">
                    <label for="employee_email"><?php _e('Email', 'chrono-forge'); ?> *</label>
                    <input type="email" id="employee_email" name="email" required>
                </div>
            </div>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="employee_phone"><?php _e('Телефон', 'chrono-forge'); ?></label>
                    <input type="tel" id="employee_phone" name="phone">
                </div>
                <div class="cf-form-group">
                    <label for="employee_color"><?php _e('Цвет', 'chrono-forge'); ?></label>
                    <input type="color" id="employee_color" name="color" value="#e74c3c" class="cf-color-picker">
                </div>
            </div>
            
            <div class="cf-form-group">
                <label for="employee_description"><?php _e('Описание', 'chrono-forge'); ?></label>
                <textarea id="employee_description" name="description" rows="3"></textarea>
            </div>
            
            <div class="cf-form-group">
                <label for="employee_services"><?php _e('Услуги', 'chrono-forge'); ?></label>
                <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                    <?php foreach ($services as $service): ?>
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="checkbox" name="service_ids[]" value="<?php echo esc_attr($service->id); ?>">
                        <?php echo esc_html($service->name); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="cf-form-group">
                <label for="employee_status"><?php _e('Статус', 'chrono-forge'); ?></label>
                <select id="employee_status" name="status">
                    <option value="active"><?php _e('Активен', 'chrono-forge'); ?></option>
                    <option value="inactive"><?php _e('Неактивен', 'chrono-forge'); ?></option>
                </select>
            </div>
            
            <div class="cf-modal-footer">
                <button type="button" class="cf-btn cf-btn-secondary cf-modal-close">
                    <?php _e('Отмена', 'chrono-forge'); ?>
                </button>
                <button type="submit" class="cf-btn cf-btn-primary">
                    <?php _e('Сохранить', 'chrono-forge'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно графика работы -->
<div id="cf-schedule-modal" class="cf-modal" style="display: none;">
    <div class="cf-modal-content" style="max-width: 800px;">
        <div class="cf-modal-header">
            <h3 class="cf-modal-title"><?php _e('График работы', 'chrono-forge'); ?></h3>
            <button type="button" class="cf-modal-close">&times;</button>
        </div>
        
        <form class="cf-admin-form" method="post" action="">
            <?php wp_nonce_field('chrono_forge_admin_action'); ?>
            <input type="hidden" name="action" value="save_schedule">
            <input type="hidden" name="employee_id" id="schedule_employee_id">

            <!-- Быстрые настройки -->
            <div class="cf-schedule-quick-setup" style="margin-bottom: 20px; padding: 15px; background: #f0f8ff; border-radius: 6px;">
                <h4 style="margin: 0 0 10px 0;"><?php _e('Быстрая настройка', 'chrono-forge'); ?></h4>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="cf-btn cf-btn-secondary cf-schedule-preset"
                            data-preset="weekdays" data-start="09:00" data-end="18:00" data-break-start="12:00" data-break-end="13:00">
                        <?php _e('Пн-Пт 9:00-18:00', 'chrono-forge'); ?>
                    </button>
                    <button type="button" class="cf-btn cf-btn-secondary cf-schedule-preset"
                            data-preset="all" data-start="10:00" data-end="19:00" data-break-start="" data-break-end="">
                        <?php _e('Каждый день 10:00-19:00', 'chrono-forge'); ?>
                    </button>
                    <button type="button" class="cf-btn cf-btn-secondary cf-schedule-preset"
                            data-preset="weekend" data-start="11:00" data-end="16:00" data-break-start="" data-break-end="">
                        <?php _e('Только выходные 11:00-16:00', 'chrono-forge'); ?>
                    </button>
                </div>
            </div>

            <div class="cf-schedule-grid">
                <?php foreach ($weekdays as $day => $label): ?>
                <div class="cf-schedule-day" data-day="<?php echo $day; ?>">
                    <div class="cf-schedule-day-header">
                        <label class="cf-schedule-checkbox">
                            <input type="checkbox" name="schedule[<?php echo $day; ?>][is_working]"
                                   value="1" class="cf-schedule-working">
                            <strong><?php echo esc_html($label); ?></strong>
                        </label>
                    </div>

                    <div class="cf-schedule-times">
                        <div class="cf-time-group">
                            <label><?php _e('Рабочее время', 'chrono-forge'); ?></label>
                            <div class="cf-time-inputs">
                                <input type="time" name="schedule[<?php echo $day; ?>][start_time]"
                                       placeholder="09:00" value="09:00" disabled>
                                <span>—</span>
                                <input type="time" name="schedule[<?php echo $day; ?>][end_time]"
                                       placeholder="18:00" value="18:00" disabled>
                            </div>
                        </div>

                        <div class="cf-time-group">
                            <label><?php _e('Перерыв (опционально)', 'chrono-forge'); ?></label>
                            <div class="cf-time-inputs">
                                <input type="time" name="schedule[<?php echo $day; ?>][break_start]"
                                       placeholder="12:00" disabled>
                                <span>—</span>
                                <input type="time" name="schedule[<?php echo $day; ?>][break_end]"
                                       placeholder="13:00" disabled>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="cf-modal-footer">
                <button type="button" class="cf-btn cf-btn-secondary cf-modal-close">
                    <?php _e('Отмена', 'chrono-forge'); ?>
                </button>
                <button type="submit" class="cf-btn cf-btn-primary">
                    <?php _e('Сохранить график', 'chrono-forge'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
