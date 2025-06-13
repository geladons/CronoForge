<?php
/**
 * Шаблон управления клиентами
 * 
 * @var array $customers
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="chrono-forge-admin">
    <div class="cf-page-title">
        <h1><?php _e('Клиенты', 'chrono-forge'); ?></h1>
        <div>
            <a href="#" class="cf-btn cf-btn-primary" data-modal="cf-new-customer-modal">
                <?php _e('Новый клиент', 'chrono-forge'); ?>
            </a>
        </div>
    </div>

    <!-- Поиск -->
    <div class="cf-filters">
        <form method="get">
            <input type="hidden" name="page" value="chrono-forge-customers">
            <div class="cf-filters-row">
                <div class="cf-form-group">
                    <label for="search"><?php _e('Поиск', 'chrono-forge'); ?></label>
                    <input type="text" id="search" name="search" 
                           value="<?php echo esc_attr($_GET['search'] ?? ''); ?>" 
                           placeholder="<?php _e('Имя, фамилия или email', 'chrono-forge'); ?>">
                </div>
                <div class="cf-form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="cf-btn"><?php _e('Найти', 'chrono-forge'); ?></button>
                </div>
            </div>
        </form>
    </div>

    <!-- Таблица клиентов -->
    <div class="cf-table-container">
        <?php if (!empty($customers)): ?>
        <table class="cf-table">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" class="cf-select-all">
                    </th>
                    <th><?php _e('Клиент', 'chrono-forge'); ?></th>
                    <th><?php _e('Контакты', 'chrono-forge'); ?></th>
                    <th><?php _e('Записи', 'chrono-forge'); ?></th>
                    <th><?php _e('Последняя запись', 'chrono-forge'); ?></th>
                    <th><?php _e('Дата регистрации', 'chrono-forge'); ?></th>
                    <th><?php _e('Действия', 'chrono-forge'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                <?php
                // Получаем статистику по клиенту
                $customer_appointments = chrono_forge()->db_manager->get_all_appointments(array(
                    'customer_id' => $customer->id
                ));
                $total_appointments = count($customer_appointments);
                $last_appointment = !empty($customer_appointments) ? $customer_appointments[0] : null;
                ?>
                <tr>
                    <td>
                        <input type="checkbox" class="cf-item-checkbox" value="<?php echo esc_attr($customer->id); ?>">
                    </td>
                    <td>
                        <div>
                            <strong><?php echo esc_html($customer->first_name . ' ' . $customer->last_name); ?></strong>
                            <?php if (!empty($customer->date_of_birth)): ?>
                            <br><small><?php _e('Дата рождения:', 'chrono-forge'); ?> <?php echo chrono_forge_format_date($customer->date_of_birth, 'd.m.Y'); ?></small>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div>
                            <div><?php echo esc_html($customer->email); ?></div>
                            <?php if (!empty($customer->phone)): ?>
                            <small><?php echo esc_html($customer->phone); ?></small>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div style="text-align: center;">
                            <strong style="font-size: 18px; color: #3498db;"><?php echo $total_appointments; ?></strong>
                            <br><small><?php _e('записей', 'chrono-forge'); ?></small>
                        </div>
                    </td>
                    <td>
                        <?php if ($last_appointment): ?>
                        <div>
                            <strong><?php echo chrono_forge_format_date($last_appointment->appointment_date, 'd.m.Y'); ?></strong>
                            <br><small><?php echo esc_html($last_appointment->service_name); ?></small>
                        </div>
                        <?php else: ?>
                        <span style="color: #999;"><?php _e('Нет записей', 'chrono-forge'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo chrono_forge_format_date($customer->created_at, 'd.m.Y'); ?>
                    </td>
                    <td class="cf-actions">
                        <a href="#" class="cf-btn" data-modal="cf-edit-customer-modal" 
                           data-id="<?php echo esc_attr($customer->id); ?>" data-type="customer">
                            <?php _e('Редактировать', 'chrono-forge'); ?>
                        </a>
                        <a href="<?php echo chrono_forge_get_admin_url('appointments', array('customer' => $customer->id)); ?>" 
                           class="cf-btn cf-btn-secondary">
                            <?php _e('Записи', 'chrono-forge'); ?>
                        </a>
                        <a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'delete_customer', 'id' => $customer->id]), 'delete_customer'); ?>" 
                           class="cf-btn cf-btn-danger cf-delete-item" 
                           data-name="<?php echo esc_attr($customer->first_name . ' ' . $customer->last_name); ?>">
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
            <button type="button" class="cf-btn cf-bulk-action" data-action="export">
                <?php _e('Экспорт', 'chrono-forge'); ?>
            </button>
            <button type="button" class="cf-btn cf-btn-danger cf-bulk-action" data-action="delete">
                <?php _e('Удалить', 'chrono-forge'); ?>
            </button>
        </div>
        <?php else: ?>
        <div style="padding: 40px; text-align: center; color: #666;">
            <p><?php _e('Клиенты не найдены.', 'chrono-forge'); ?></p>
            <a href="#" class="cf-btn cf-btn-primary" data-modal="cf-new-customer-modal">
                <?php _e('Добавить первого клиента', 'chrono-forge'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Статистика -->
    <div style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
            <div style="font-size: 24px; font-weight: 600; color: #3498db; margin-bottom: 5px;">
                <?php echo count($customers); ?>
            </div>
            <div style="color: #666; font-size: 13px;"><?php _e('Всего клиентов', 'chrono-forge'); ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
            <div style="font-size: 24px; font-weight: 600; color: #27ae60; margin-bottom: 5px;">
                <?php 
                $new_customers = array_filter($customers, function($c) { 
                    return strtotime($c->created_at) > strtotime('-30 days'); 
                });
                echo count($new_customers);
                ?>
            </div>
            <div style="color: #666; font-size: 13px;"><?php _e('Новых за месяц', 'chrono-forge'); ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
            <div style="font-size: 24px; font-weight: 600; color: #f39c12; margin-bottom: 5px;">
                <?php 
                $active_customers = array_filter($customers, function($c) { 
                    $appointments = chrono_forge()->db_manager->get_all_appointments(array(
                        'customer_id' => $c->id,
                        'date_from' => date('Y-m-d', strtotime('-90 days'))
                    ));
                    return !empty($appointments);
                });
                echo count($active_customers);
                ?>
            </div>
            <div style="color: #666; font-size: 13px;"><?php _e('Активных (90 дней)', 'chrono-forge'); ?></div>
        </div>
    </div>
</div>

<!-- Модальное окно нового/редактирования клиента -->
<div id="cf-new-customer-modal" class="cf-modal" style="display: none;">
    <div class="cf-modal-content">
        <div class="cf-modal-header">
            <h3 class="cf-modal-title"><?php _e('Новый клиент', 'chrono-forge'); ?></h3>
            <button type="button" class="cf-modal-close">&times;</button>
        </div>
        
        <form class="cf-admin-form" method="post" action="">
            <?php wp_nonce_field('chrono_forge_admin_action'); ?>
            <input type="hidden" name="action" value="save_customer">
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="customer_first_name"><?php _e('Имя', 'chrono-forge'); ?> *</label>
                    <input type="text" id="customer_first_name" name="first_name" required>
                </div>
                <div class="cf-form-group">
                    <label for="customer_last_name"><?php _e('Фамилия', 'chrono-forge'); ?> *</label>
                    <input type="text" id="customer_last_name" name="last_name" required>
                </div>
            </div>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="customer_email"><?php _e('Email', 'chrono-forge'); ?> *</label>
                    <input type="email" id="customer_email" name="email" required>
                </div>
                <div class="cf-form-group">
                    <label for="customer_phone"><?php _e('Телефон', 'chrono-forge'); ?></label>
                    <input type="tel" id="customer_phone" name="phone">
                </div>
            </div>
            
            <div class="cf-form-group">
                <label for="customer_date_of_birth"><?php _e('Дата рождения', 'chrono-forge'); ?></label>
                <input type="date" id="customer_date_of_birth" name="date_of_birth" class="cf-datepicker-past">
            </div>
            
            <div class="cf-form-group">
                <label for="customer_notes"><?php _e('Заметки', 'chrono-forge'); ?></label>
                <textarea id="customer_notes" name="notes" rows="4" 
                          placeholder="<?php _e('Дополнительная информация о клиенте', 'chrono-forge'); ?>"></textarea>
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

<!-- Модальное окно редактирования клиента -->
<div id="cf-edit-customer-modal" class="cf-modal" style="display: none;">
    <div class="cf-modal-content">
        <div class="cf-modal-header">
            <h3 class="cf-modal-title"><?php _e('Редактировать клиента', 'chrono-forge'); ?></h3>
            <button type="button" class="cf-modal-close">&times;</button>
        </div>
        
        <form class="cf-admin-form" method="post" action="">
            <?php wp_nonce_field('chrono_forge_admin_action'); ?>
            <input type="hidden" name="action" value="save_customer">
            <input type="hidden" name="customer_id" id="edit_customer_id">
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="edit_customer_first_name"><?php _e('Имя', 'chrono-forge'); ?> *</label>
                    <input type="text" id="edit_customer_first_name" name="first_name" required>
                </div>
                <div class="cf-form-group">
                    <label for="edit_customer_last_name"><?php _e('Фамилия', 'chrono-forge'); ?> *</label>
                    <input type="text" id="edit_customer_last_name" name="last_name" required>
                </div>
            </div>
            
            <div class="cf-form-row">
                <div class="cf-form-group">
                    <label for="edit_customer_email"><?php _e('Email', 'chrono-forge'); ?> *</label>
                    <input type="email" id="edit_customer_email" name="email" required>
                </div>
                <div class="cf-form-group">
                    <label for="edit_customer_phone"><?php _e('Телефон', 'chrono-forge'); ?></label>
                    <input type="tel" id="edit_customer_phone" name="phone">
                </div>
            </div>
            
            <div class="cf-form-group">
                <label for="edit_customer_date_of_birth"><?php _e('Дата рождения', 'chrono-forge'); ?></label>
                <input type="date" id="edit_customer_date_of_birth" name="date_of_birth" class="cf-datepicker-past">
            </div>
            
            <div class="cf-form-group">
                <label for="edit_customer_notes"><?php _e('Заметки', 'chrono-forge'); ?></label>
                <textarea id="edit_customer_notes" name="notes" rows="4"></textarea>
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
