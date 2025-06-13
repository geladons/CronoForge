<?php
/**
 * Шаблон календаря
 * 
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
        <h1><?php _e('Календарь записей', 'chrono-forge'); ?></h1>
        <div>
            <a href="#" class="cf-btn cf-btn-primary" data-modal="cf-new-appointment-modal">
                <?php _e('Новая запись', 'chrono-forge'); ?>
            </a>
        </div>
    </div>

    <!-- Фильтры календаря -->
    <div class="cf-calendar-container">
        <div class="cf-calendar-toolbar">
            <div class="cf-calendar-filters">
                <select id="cf-calendar-employee-filter">
                    <option value=""><?php _e('Все сотрудники', 'chrono-forge'); ?></option>
                    <?php foreach ($employees as $employee): ?>
                    <option value="<?php echo esc_attr($employee->id); ?>">
                        <?php echo esc_html($employee->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <select id="cf-calendar-service-filter">
                    <option value=""><?php _e('Все услуги', 'chrono-forge'); ?></option>
                    <?php foreach ($services as $service): ?>
                    <option value="<?php echo esc_attr($service->id); ?>">
                        <?php echo esc_html($service->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <select id="cf-calendar-status-filter">
                    <option value=""><?php _e('Все статусы', 'chrono-forge'); ?></option>
                    <?php foreach (chrono_forge_get_appointment_statuses() as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>">
                        <?php echo esc_html($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <button type="button" class="cf-btn" id="cf-calendar-today">
                    <?php _e('Сегодня', 'chrono-forge'); ?>
                </button>
                <button type="button" class="cf-btn" id="cf-calendar-prev">‹</button>
                <button type="button" class="cf-btn" id="cf-calendar-next">›</button>
            </div>
        </div>
        
        <!-- Календарь -->
        <div id="cf-calendar" style="min-height: 600px;"></div>

        <!-- FullCalendar CSS and JS -->
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    </div>

    <!-- Легенда -->
    <div style="margin-top: 20px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3><?php _e('Легенда', 'chrono-forge'); ?></h3>
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            <?php foreach (chrono_forge_get_appointment_statuses() as $key => $label): ?>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="cf-status <?php echo esc_attr($key); ?>" style="padding: 4px 8px; border-radius: 4px; font-size: 11px;">
                    <?php echo esc_html($label); ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Модальное окно деталей записи -->
<div id="cf-appointment-details-modal" class="cf-modal" style="display: none;">
    <div class="cf-modal-content">
        <div class="cf-modal-header">
            <h3 class="cf-modal-title"><?php _e('Детали записи', 'chrono-forge'); ?></h3>
            <button type="button" class="cf-modal-close">&times;</button>
        </div>
        
        <div class="cf-modal-body">
            <div class="cf-appointment-details">
                <div class="cf-detail-row">
                    <strong><?php _e('Клиент:', 'chrono-forge'); ?></strong>
                    <span class="cf-detail-customer"></span>
                </div>
                <div class="cf-detail-row">
                    <strong><?php _e('Услуга:', 'chrono-forge'); ?></strong>
                    <span class="cf-detail-service"></span>
                </div>
                <div class="cf-detail-row">
                    <strong><?php _e('Специалист:', 'chrono-forge'); ?></strong>
                    <span class="cf-detail-employee"></span>
                </div>
                <div class="cf-detail-row">
                    <strong><?php _e('Дата и время:', 'chrono-forge'); ?></strong>
                    <span class="cf-detail-datetime"></span>
                </div>
                <div class="cf-detail-row">
                    <strong><?php _e('Статус:', 'chrono-forge'); ?></strong>
                    <span class="cf-detail-status"></span>
                </div>
                <div class="cf-detail-row">
                    <strong><?php _e('Стоимость:', 'chrono-forge'); ?></strong>
                    <span class="cf-detail-price"></span>
                </div>
                <div class="cf-detail-row">
                    <strong><?php _e('Комментарий:', 'chrono-forge'); ?></strong>
                    <span class="cf-detail-notes"></span>
                </div>
            </div>
        </div>
        
        <div class="cf-modal-footer">
            <button type="button" class="cf-btn cf-btn-secondary cf-modal-close">
                <?php _e('Закрыть', 'chrono-forge'); ?>
            </button>
            <button type="button" class="cf-btn cf-btn-primary cf-edit-appointment">
                <?php _e('Редактировать', 'chrono-forge'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Простой календарь без FullCalendar (для демонстрации) -->
<style>
.cf-simple-calendar {
    background: white;
    border-radius: 8px;
    overflow: hidden;
}

.cf-calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.cf-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.cf-calendar-day-header {
    padding: 15px 10px;
    text-align: center;
    font-weight: 600;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
    border-right: 1px solid #eee;
}

.cf-calendar-day {
    min-height: 120px;
    padding: 8px;
    border-bottom: 1px solid #eee;
    border-right: 1px solid #eee;
    position: relative;
}

.cf-calendar-day.other-month {
    background: #f8f9fa;
    color: #999;
}

.cf-calendar-day.today {
    background: #ebf3fd;
}

.cf-calendar-day-number {
    font-weight: 600;
    margin-bottom: 5px;
}

.cf-calendar-appointment {
    background: #3498db;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    margin-bottom: 2px;
    cursor: pointer;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cf-calendar-appointment.pending {
    background: #f39c12;
}

.cf-calendar-appointment.confirmed {
    background: #27ae60;
}

.cf-calendar-appointment.completed {
    background: #3498db;
}

.cf-calendar-appointment.cancelled {
    background: #e74c3c;
}

.cf-detail-row {
    display: flex;
    margin-bottom: 10px;
    gap: 10px;
}

.cf-detail-row strong {
    min-width: 120px;
}

/* FullCalendar custom styles */
.fc-event.cf-event-pending {
    background-color: #f39c12 !important;
    border-color: #f39c12 !important;
}

.fc-event.cf-event-confirmed {
    background-color: #27ae60 !important;
    border-color: #27ae60 !important;
}

.fc-event.cf-event-completed {
    background-color: #3498db !important;
    border-color: #3498db !important;
}

.fc-event.cf-event-cancelled {
    background-color: #e74c3c !important;
    border-color: #e74c3c !important;
}

.fc-event.cf-event-no_show {
    background-color: #95a5a6 !important;
    border-color: #95a5a6 !important;
}

.fc-toolbar {
    margin-bottom: 20px !important;
}

.fc-toolbar-title {
    font-size: 1.5em !important;
    font-weight: 600 !important;
}

.fc-button {
    background-color: #3498db !important;
    border-color: #3498db !important;
}

.fc-button:hover {
    background-color: #2980b9 !important;
    border-color: #2980b9 !important;
}

.fc-button:focus {
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25) !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    // FullCalendar implementation
    let calendar;

    function initFullCalendar() {
        const calendarEl = document.getElementById('cf-calendar');

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            height: 'auto',
            locale: 'ru',
            firstDay: 1, // Monday
            slotMinTime: '08:00:00',
            slotMaxTime: '20:00:00',
            businessHours: {
                daysOfWeek: [1, 2, 3, 4, 5, 6], // Monday - Saturday
                startTime: '09:00',
                endTime: '18:00'
            },
            events: function(fetchInfo, successCallback, failureCallback) {
                loadCalendarEvents(fetchInfo, successCallback, failureCallback);
            },
            eventClick: function(info) {
                showAppointmentDetails(info.event.id);
            },
            dateClick: function(info) {
                // Quick appointment creation
                if (info.view.type !== 'dayGridMonth') {
                    createQuickAppointment(info.dateStr);
                }
            },
            eventDidMount: function(info) {
                // Customize event appearance based on status
                const status = info.event.extendedProps.status;
                info.el.classList.add('cf-event-' + status);
            }
        });

        calendar.render();
    }
        
    function loadCalendarEvents(fetchInfo, successCallback, failureCallback) {
        const filters = {
            employee_id: $('#cf-calendar-employee-filter').val(),
            service_id: $('#cf-calendar-service-filter').val(),
            status: $('#cf-calendar-status-filter').val(),
            start: fetchInfo.startStr,
            end: fetchInfo.endStr
        };

        $.ajax({
            url: chronoForgeAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'chrono_forge_get_calendar_appointments',
                filters: filters,
                nonce: chronoForgeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const events = response.data.map(appointment => ({
                        id: appointment.id,
                        title: appointment.service_name + ' - ' + appointment.customer_name,
                        start: appointment.appointment_date + 'T' + appointment.appointment_time,
                        end: appointment.appointment_date + 'T' + appointment.end_time,
                        backgroundColor: getStatusColor(appointment.status),
                        borderColor: getStatusColor(appointment.status),
                        extendedProps: {
                            status: appointment.status,
                            customer_name: appointment.customer_name,
                            customer_email: appointment.customer_email,
                            customer_phone: appointment.customer_phone,
                            service_name: appointment.service_name,
                            employee_name: appointment.employee_name,
                            price: appointment.total_price,
                            notes: appointment.notes
                        }
                    }));
                    successCallback(events);
                } else {
                    failureCallback(response.data);
                }
            },
            error: function() {
                failureCallback('Error loading calendar events');
            }
        });
    }
        
    function getStatusColor(status) {
        const colors = {
            'pending': '#f39c12',
            'confirmed': '#27ae60',
            'completed': '#3498db',
            'cancelled': '#e74c3c',
            'no_show': '#95a5a6'
        };
        return colors[status] || '#3498db';
    }

    function showAppointmentDetails(appointmentId) {
        // Find the event in calendar
        const event = calendar.getEventById(appointmentId);
        if (!event) return;

        const props = event.extendedProps;

        $('.cf-detail-customer').text(props.customer_name);
        $('.cf-detail-service').text(props.service_name);
        $('.cf-detail-employee').text(props.employee_name);
        $('.cf-detail-datetime').text(event.startStr.replace('T', ' '));
        $('.cf-detail-status').html('<span class="cf-status ' + props.status + '">' + props.status + '</span>');
        $('.cf-detail-price').text(props.price ? '$' + props.price : '—');
        $('.cf-detail-notes').text(props.notes || '—');

        $('#cf-appointment-details-modal').show();
    }
        
    function createQuickAppointment(dateStr) {
        // Open quick appointment modal with pre-filled date
        $('#cf-new-appointment-modal').show();
        $('#appointment-date').val(dateStr);
    }

    // Bind filter events
    $('#cf-calendar-employee-filter, #cf-calendar-service-filter, #cf-calendar-status-filter').on('change', function() {
        if (calendar) {
            calendar.refetchEvents();
        }
    });

    // Bind toolbar events
    $('#cf-calendar-today').on('click', function() {
        if (calendar) {
            calendar.today();
        }
    });

    $('#cf-calendar-prev').on('click', function() {
        if (calendar) {
            calendar.prev();
        }
    });

    $('#cf-calendar-next').on('click', function() {
        if (calendar) {
            calendar.next();
        }
    });
        
    // Initialize FullCalendar
    initFullCalendar();
});
</script>
