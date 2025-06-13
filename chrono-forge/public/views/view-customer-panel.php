<?php
/**
 * Шаблон личного кабинета клиента
 * 
 * @var object $customer
 * @var array $upcoming_appointments
 * @var array $past_appointments
 * @var array $atts
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="chrono-forge-customer-panel">
    <div class="cf-panel-header">
        <h2><?php _e('Личный кабинет', 'chrono-forge'); ?></h2>
        <p><?php printf(__('Добро пожаловать, %s!', 'chrono-forge'), esc_html($customer->first_name . ' ' . $customer->last_name)); ?></p>
    </div>

    <!-- Предстоящие записи -->
    <?php if ($atts['show_upcoming'] === 'true' && !empty($upcoming_appointments)): ?>
    <div class="cf-panel-section">
        <h3><?php _e('Предстоящие записи', 'chrono-forge'); ?></h3>
        
        <div class="cf-appointments-list">
            <?php foreach ($upcoming_appointments as $appointment): ?>
            <div class="cf-appointment-card cf-upcoming" data-appointment-id="<?php echo esc_attr($appointment->id); ?>">
                <div class="cf-appointment-header">
                    <div class="cf-appointment-service">
                        <h4><?php echo esc_html($appointment->service_name); ?></h4>
                        <span class="cf-appointment-employee"><?php echo esc_html($appointment->employee_name); ?></span>
                    </div>
                    <div class="cf-appointment-status">
                        <span class="cf-status <?php echo esc_attr($appointment->status); ?>">
                            <?php echo esc_html(chrono_forge_get_appointment_statuses()[$appointment->status] ?? $appointment->status); ?>
                        </span>
                    </div>
                </div>
                
                <div class="cf-appointment-details">
                    <div class="cf-appointment-datetime">
                        <span class="cf-appointment-date">
                            <i class="dashicons dashicons-calendar-alt"></i>
                            <?php echo chrono_forge_format_date($appointment->appointment_date, 'd.m.Y'); ?>
                        </span>
                        <span class="cf-appointment-time">
                            <i class="dashicons dashicons-clock"></i>
                            <?php echo chrono_forge_format_time($appointment->appointment_time); ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($appointment->total_price) && $appointment->total_price > 0): ?>
                    <div class="cf-appointment-price">
                        <span class="cf-price"><?php echo chrono_forge_format_price($appointment->total_price); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($appointment->notes)): ?>
                <div class="cf-appointment-notes">
                    <p><strong><?php _e('Комментарий:', 'chrono-forge'); ?></strong> <?php echo esc_html($appointment->notes); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="cf-appointment-actions">
                    <?php if ($appointment->status === 'pending' || $appointment->status === 'confirmed'): ?>
                    <button type="button" class="cf-btn cf-btn-danger cf-btn-cancel" 
                            data-appointment-id="<?php echo esc_attr($appointment->id); ?>">
                        <?php _e('Отменить запись', 'chrono-forge'); ?>
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($appointment->status === 'confirmed'): ?>
                    <button type="button" class="cf-btn cf-btn-secondary cf-btn-reschedule" 
                            data-appointment-id="<?php echo esc_attr($appointment->id); ?>">
                        <?php _e('Перенести', 'chrono-forge'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif ($atts['show_upcoming'] === 'true'): ?>
    <div class="cf-panel-section">
        <h3><?php _e('Предстоящие записи', 'chrono-forge'); ?></h3>
        <div class="cf-empty-state">
            <p><?php _e('У вас нет предстоящих записей.', 'chrono-forge'); ?></p>
            <a href="#" class="cf-btn cf-btn-primary"><?php _e('Записаться на услугу', 'chrono-forge'); ?></a>
        </div>
    </div>
    <?php endif; ?>

    <!-- История записей -->
    <?php if ($atts['show_past'] === 'true' && !empty($past_appointments)): ?>
    <div class="cf-panel-section">
        <h3><?php _e('История записей', 'chrono-forge'); ?></h3>
        
        <div class="cf-appointments-history">
            <div class="cf-table-container">
                <table class="cf-table">
                    <thead>
                        <tr>
                            <th><?php _e('Дата', 'chrono-forge'); ?></th>
                            <th><?php _e('Время', 'chrono-forge'); ?></th>
                            <th><?php _e('Услуга', 'chrono-forge'); ?></th>
                            <th><?php _e('Специалист', 'chrono-forge'); ?></th>
                            <th><?php _e('Статус', 'chrono-forge'); ?></th>
                            <th><?php _e('Стоимость', 'chrono-forge'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($past_appointments as $appointment): ?>
                        <tr>
                            <td><?php echo chrono_forge_format_date($appointment->appointment_date, 'd.m.Y'); ?></td>
                            <td><?php echo chrono_forge_format_time($appointment->appointment_time); ?></td>
                            <td><?php echo esc_html($appointment->service_name); ?></td>
                            <td><?php echo esc_html($appointment->employee_name); ?></td>
                            <td>
                                <span class="cf-status <?php echo esc_attr($appointment->status); ?>">
                                    <?php echo esc_html(chrono_forge_get_appointment_statuses()[$appointment->status] ?? $appointment->status); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($appointment->total_price) && $appointment->total_price > 0): ?>
                                    <?php echo chrono_forge_format_price($appointment->total_price); ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php elseif ($atts['show_past'] === 'true'): ?>
    <div class="cf-panel-section">
        <h3><?php _e('История записей', 'chrono-forge'); ?></h3>
        <div class="cf-empty-state">
            <p><?php _e('У вас пока нет записей в истории.', 'chrono-forge'); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Модальное окно подтверждения отмены -->
<div id="cf-cancel-modal" class="cf-modal" style="display: none;">
    <div class="cf-modal-content">
        <div class="cf-modal-header">
            <h3 class="cf-modal-title"><?php _e('Отмена записи', 'chrono-forge'); ?></h3>
            <button type="button" class="cf-modal-close">&times;</button>
        </div>
        
        <div class="cf-modal-body">
            <p><?php _e('Вы уверены, что хотите отменить эту запись?', 'chrono-forge'); ?></p>
            <div class="cf-cancel-details"></div>
        </div>
        
        <div class="cf-modal-footer">
            <button type="button" class="cf-btn cf-btn-secondary cf-modal-close">
                <?php _e('Нет, оставить', 'chrono-forge'); ?>
            </button>
            <button type="button" class="cf-btn cf-btn-danger cf-confirm-cancel">
                <?php _e('Да, отменить', 'chrono-forge'); ?>
            </button>
        </div>
    </div>
</div>

<style>
/* Стили для личного кабинета */
.chrono-forge-customer-panel {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.cf-panel-header {
    text-align: center;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 2px solid #eee;
}

.cf-panel-header h2 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 28px;
    font-weight: 600;
}

.cf-panel-header p {
    margin: 0;
    color: #7f8c8d;
    font-size: 16px;
}

.cf-panel-section {
    margin-bottom: 40px;
}

.cf-panel-section h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 22px;
    font-weight: 600;
}

.cf-appointment-card {
    background: white;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.cf-appointment-card.cf-upcoming {
    border-left: 4px solid #3498db;
}

.cf-appointment-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.cf-appointment-service h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 18px;
    font-weight: 600;
}

.cf-appointment-employee {
    color: #7f8c8d;
    font-size: 14px;
}

.cf-appointment-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.cf-appointment-datetime {
    display: flex;
    gap: 20px;
}

.cf-appointment-date,
.cf-appointment-time {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #555;
    font-size: 14px;
}

.cf-appointment-price .cf-price {
    color: #27ae60;
    font-size: 16px;
    font-weight: 600;
}

.cf-appointment-notes {
    margin-bottom: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.cf-appointment-notes p {
    margin: 0;
    font-size: 14px;
    color: #555;
}

.cf-appointment-actions {
    display: flex;
    gap: 10px;
}

.cf-empty-state {
    text-align: center;
    padding: 40px;
    background: #f8f9fa;
    border-radius: 8px;
}

.cf-empty-state p {
    margin: 0 0 20px 0;
    color: #7f8c8d;
    font-size: 16px;
}

/* Адаптивность */
@media (max-width: 768px) {
    .chrono-forge-customer-panel {
        padding: 15px;
    }
    
    .cf-appointment-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .cf-appointment-details {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .cf-appointment-datetime {
        flex-direction: column;
        gap: 5px;
    }
    
    .cf-appointment-actions {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Отмена записи
    $('.cf-btn-cancel').on('click', function() {
        const appointmentId = $(this).data('appointment-id');
        const $card = $(this).closest('.cf-appointment-card');
        const serviceName = $card.find('.cf-appointment-service h4').text();
        const date = $card.find('.cf-appointment-date').text();
        const time = $card.find('.cf-appointment-time').text();
        
        $('#cf-cancel-modal .cf-cancel-details').html(
            '<p><strong>Услуга:</strong> ' + serviceName + '</p>' +
            '<p><strong>Дата:</strong> ' + date + '</p>' +
            '<p><strong>Время:</strong> ' + time + '</p>'
        );
        
        $('#cf-cancel-modal').show();
        
        $('.cf-confirm-cancel').off('click').on('click', function() {
            cancelAppointment(appointmentId);
        });
    });
    
    // Закрытие модального окна
    $('.cf-modal-close').on('click', function() {
        $('.cf-modal').hide();
    });
    
    // Функция отмены записи
    function cancelAppointment(appointmentId) {
        $.ajax({
            url: chronoForgeAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'chrono_forge_cancel_appointment',
                appointment_id: appointmentId,
                nonce: chronoForgeAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.data);
                }
            },
            error: function() {
                alert('Произошла ошибка при отмене записи');
            }
        });
        
        $('.cf-modal').hide();
    }
});
</script>
