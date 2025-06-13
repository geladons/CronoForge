<?php
/**
 * Обработчик AJAX-запросов для плагина ChronoForge
 * 
 * Этот класс управляет всеми AJAX-запросами от фронтенда
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}

class ChronoForge_Ajax_Handler {

    /**
     * Менеджер базы данных
     * 
     * @var ChronoForge_DB_Manager
     */
    private $db_manager;

    /**
     * Конструктор класса
     * 
     * @param ChronoForge_DB_Manager $db_manager
     */
    public function __construct($db_manager) {
        $this->db_manager = $db_manager;
        $this->init_hooks();
    }

    /**
     * Initialize AJAX hooks
     */
    private function init_hooks() {
        // Public AJAX handlers
        add_action('wp_ajax_chrono_forge_get_available_slots', array($this, 'get_available_slots'));
        add_action('wp_ajax_nopriv_chrono_forge_get_available_slots', array($this, 'get_available_slots'));

        add_action('wp_ajax_chrono_forge_get_available_slots_any', array($this, 'get_available_slots_any'));
        add_action('wp_ajax_nopriv_chrono_forge_get_available_slots_any', array($this, 'get_available_slots_any'));

        add_action('wp_ajax_chrono_forge_create_appointment', array($this, 'create_appointment'));
        add_action('wp_ajax_nopriv_chrono_forge_create_appointment', array($this, 'create_appointment'));

        add_action('wp_ajax_chrono_forge_get_services', array($this, 'get_services'));
        add_action('wp_ajax_nopriv_chrono_forge_get_services', array($this, 'get_services'));

        add_action('wp_ajax_chrono_forge_get_employees', array($this, 'get_employees'));
        add_action('wp_ajax_nopriv_chrono_forge_get_employees', array($this, 'get_employees'));

        add_action('wp_ajax_chrono_forge_cancel_appointment', array($this, 'cancel_appointment'));
        add_action('wp_ajax_nopriv_chrono_forge_cancel_appointment', array($this, 'cancel_appointment'));

        add_action('wp_ajax_chrono_forge_search_availability', array($this, 'search_availability'));
        add_action('wp_ajax_nopriv_chrono_forge_search_availability', array($this, 'search_availability'));

        // Admin AJAX handlers
        add_action('wp_ajax_chrono_forge_get_employee', array($this, 'get_employee_data'));
        add_action('wp_ajax_chrono_forge_get_employee_schedule', array($this, 'get_employee_schedule'));
        add_action('wp_ajax_chrono_forge_get_calendar_appointments', array($this, 'get_calendar_appointments'));
        add_action('wp_ajax_chrono_forge_disable_emergency_mode', array($this, 'disable_emergency_mode'));
    }

    /**
     * Получить доступные временные слоты
     */
    public function get_available_slots() {
        try {
            // Проверка nonce для безопасности
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'chrono_forge_nonce')) {
                chrono_forge_log('Security error: Invalid nonce in get_available_slots', 'error');
                wp_send_json_error(__('Ошибка безопасности', 'chrono-forge'));
            }

            // Валидация и санитизация входных данных
            $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
            $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
            $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';

            // Валидация входных данных
            if (!$service_id || !$employee_id || !$date) {
                chrono_forge_log("Invalid parameters in get_available_slots: service_id={$service_id}, employee_id={$employee_id}, date={$date}", 'warning');
                wp_send_json_error(__('Неверные параметры', 'chrono-forge'));
            }

            // Проверка корректности даты
            if (!$this->is_valid_date($date)) {
                chrono_forge_log("Invalid date format in get_available_slots: {$date}", 'warning');
                wp_send_json_error(__('Неверный формат даты', 'chrono-forge'));
            }

            // Проверка существования услуги и сотрудника
            $service = $this->db_manager->get_service($service_id);
            if (!$service) {
                chrono_forge_log("Service not found: {$service_id}", 'warning');
                wp_send_json_error(__('Услуга не найдена', 'chrono-forge'));
            }

            $employee = $this->db_manager->get_employee($employee_id);
            if (!$employee) {
                chrono_forge_log("Employee not found: {$employee_id}", 'warning');
                wp_send_json_error(__('Сотрудник не найден', 'chrono-forge'));
            }

        // Получение информации об услуге
        $service = $this->db_manager->get_service($service_id);
        if (!$service) {
            wp_send_json_error(__('Услуга не найдена', 'chrono-forge'));
        }

        // Получение информации о сотруднике
        $employee = $this->db_manager->get_employee($employee_id);
        if (!$employee) {
            wp_send_json_error(__('Сотрудник не найден', 'chrono-forge'));
        }

        // Получение графика работы сотрудника
        $schedule = $this->get_employee_schedule_for_date($employee_id, $date);
        if (!$schedule) {
            wp_send_json_error(__('Сотрудник не работает в выбранную дату', 'chrono-forge'));
        }

        // Генерация доступных слотов
        $available_slots = $this->generate_available_slots($employee_id, $date, $service, $schedule);

        wp_send_json_success($available_slots);
    }

    /**
     * Создать новую запись
     */
    public function create_appointment() {
        try {
            // Проверка nonce для безопасности
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'chrono_forge_nonce')) {
                chrono_forge_log('Security error: Invalid nonce in create_appointment', 'error');
                wp_send_json_error(__('Ошибка безопасности', 'chrono-forge'));
            }

            // Получение и валидация данных с дополнительной санитизацией
            $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
            $employee_id_raw = isset($_POST['employee_id']) ? sanitize_text_field($_POST['employee_id']) : '';
            $employee_id = ($employee_id_raw === 'any') ? 'any' : intval($employee_id_raw);
            $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
            $time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '';

            // Санитизация данных клиента
            $customer_data = array(
                'first_name' => isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '',
                'last_name' => isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '',
                'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
                'phone' => isset($_POST['phone']) ? preg_replace('/[^0-9+\-\(\)\s]/', '', sanitize_text_field($_POST['phone'])) : '',
            );
            $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

            // Валидация обязательных полей
            if (!$service_id || !$employee_id || !$date || !$time ||
                !$customer_data['first_name'] || !$customer_data['last_name'] || !$customer_data['email']) {
                chrono_forge_log('Missing required fields in create_appointment', 'warning');
                wp_send_json_error(__('Заполните все обязательные поля', 'chrono-forge'));
            }

            // Валидация email
            if (!is_email($customer_data['email'])) {
                chrono_forge_log("Invalid email format: {$customer_data['email']}", 'warning');
                wp_send_json_error(__('Неверный формат email', 'chrono-forge'));
            }

            // Валидация даты и времени
            if (!$this->is_valid_date($date)) {
                chrono_forge_log("Invalid date format: {$date}", 'warning');
                wp_send_json_error(__('Неверный формат даты', 'chrono-forge'));
            }

            if (!$this->is_valid_time($time)) {
                chrono_forge_log("Invalid time format: {$time}", 'warning');
                wp_send_json_error(__('Неверный формат времени', 'chrono-forge'));
            }

        // Получение информации об услуге
        $service = $this->db_manager->get_service($service_id);
        if (!$service) {
            wp_send_json_error(__('Услуга не найдена', 'chrono-forge'));
        }

        // Вычисление времени окончания
        $end_time = date('H:i:s', strtotime($time) + ($service->duration * 60));

        // Если выбран "любой доступный специалист", найти подходящего
        if ($employee_id === 'any') {
            $available_employees = $this->db_manager->get_employees_by_service($service_id);
            $selected_employee_id = null;

            foreach ($available_employees as $emp) {
                if ($this->db_manager->is_slot_available($emp->id, $date, $time, $end_time)) {
                    $selected_employee_id = $emp->id;
                    break;
                }
            }

            if (!$selected_employee_id) {
                wp_send_json_error(__('Нет доступных специалистов на выбранное время', 'chrono-forge'));
            }

            $employee_id = $selected_employee_id;
        } else {
            // Проверка доступности слота для конкретного сотрудника
            if (!$this->db_manager->is_slot_available($employee_id, $date, $time, $end_time)) {
                wp_send_json_error(__('Выбранное время уже занято', 'chrono-forge'));
            }
        }

        // Поиск или создание клиента
        $customer = $this->db_manager->get_customer_by_email($customer_data['email']);
        if (!$customer) {
            $customer_id = $this->db_manager->insert_customer($customer_data);
            if (!$customer_id) {
                wp_send_json_error(__('Ошибка при создании клиента', 'chrono-forge'));
            }
        } else {
            $customer_id = $customer->id;
            // Обновляем данные клиента
            $this->db_manager->update_customer($customer_id, $customer_data);
        }

        // Создание записи
        $appointment_data = array(
            'service_id' => $service_id,
            'employee_id' => $employee_id,
            'customer_id' => $customer_id,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'end_time' => $end_time,
            'status' => 'pending',
            'notes' => $notes,
            'total_price' => $service->price
        );

        $appointment_id = $this->db_manager->insert_appointment($appointment_data);

        if (!$appointment_id) {
            wp_send_json_error(__('Ошибка при создании записи', 'chrono-forge'));
        }

        // Отправка уведомлений (если включены)
        $this->send_appointment_notifications($appointment_id, 'created');

        wp_send_json_success(array(
            'appointment_id' => $appointment_id,
            'message' => __('Запись успешно создана', 'chrono-forge')
        ));
    }

    /**
     * Проверка корректности даты
     * 
     * @param string $date
     * @return bool
     */
    private function is_valid_date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Получение графика работы сотрудника для конкретной даты
     * 
     * @param int $employee_id
     * @param string $date
     * @return object|null
     */
    private function get_employee_schedule_for_date($employee_id, $date) {
        $day_of_week = date('w', strtotime($date)); // 0 = Sunday, 1 = Monday, etc.
        
        $schedule = $this->db_manager->get_employee_schedule($employee_id);
        
        foreach ($schedule as $day_schedule) {
            if ($day_schedule->day_of_week == $day_of_week && $day_schedule->is_working) {
                return $day_schedule;
            }
        }
        
        return null;
    }

    /**
     * Генерация доступных временных слотов
     * 
     * @param int $employee_id
     * @param string $date
     * @param object $service
     * @param object $schedule
     * @return array
     */
    private function generate_available_slots($employee_id, $date, $service, $schedule) {
        $slots = array();
        
        $start_time = strtotime($schedule->start_time);
        $end_time = strtotime($schedule->end_time);
        $service_duration = $service->duration * 60; // в секундах
        $buffer_time = $service->buffer_time * 60; // в секундах
        $slot_duration = $service_duration + $buffer_time;
        
        // Получение существующих записей на эту дату
        $existing_appointments = $this->db_manager->get_all_appointments(array(
            'employee_id' => $employee_id,
            'date_from' => $date,
            'date_to' => $date
        ));
        
        // Генерация слотов с интервалом в 15 минут
        $slot_interval = 15 * 60; // 15 минут в секундах
        
        for ($current_time = $start_time; $current_time + $service_duration <= $end_time; $current_time += $slot_interval) {
            $slot_start = date('H:i:s', $current_time);
            $slot_end = date('H:i:s', $current_time + $service_duration);
            
            // Проверка на перерыв
            if ($schedule->break_start && $schedule->break_end) {
                $break_start = strtotime($schedule->break_start);
                $break_end = strtotime($schedule->break_end);
                
                if ($current_time < $break_end && $current_time + $service_duration > $break_start) {
                    continue; // Слот пересекается с перерывом
                }
            }
            
            // Проверка на конфликт с существующими записями
            $is_available = true;
            foreach ($existing_appointments as $appointment) {
                if ($appointment->status == 'cancelled' || $appointment->status == 'no_show') {
                    continue;
                }
                
                $app_start = strtotime($appointment->appointment_time);
                $app_end = strtotime($appointment->end_time);
                
                if ($current_time < $app_end && $current_time + $service_duration > $app_start) {
                    $is_available = false;
                    break;
                }
            }
            
            if ($is_available) {
                $slots[] = array(
                    'time' => $slot_start,
                    'display_time' => date('H:i', $current_time)
                );
            }
        }
        
        return $slots;
    }

    /**
     * Отправка уведомлений о записи
     * 
     * @param int $appointment_id
     * @param string $action
     */
    private function send_appointment_notifications($appointment_id, $action) {
        $settings = get_option('chrono_forge_settings', array());
        
        if (empty($settings['enable_notifications'])) {
            return;
        }
        
        $appointment = $this->db_manager->get_appointment($appointment_id);
        if (!$appointment) {
            return;
        }
        
        // Отправка уведомления клиенту
        if (!empty($settings['customer_email_notifications'])) {
            $this->send_customer_notification($appointment, $action);
        }
        
        // Отправка уведомления администратору
        if (!empty($settings['admin_email_notifications'])) {
            $this->send_admin_notification($appointment, $action);
        }
    }

    /**
     * Отправка уведомления клиенту
     * 
     * @param object $appointment
     * @param string $action
     */
    private function send_customer_notification($appointment, $action) {
        $subject = '';
        $message = '';
        
        switch ($action) {
            case 'created':
                $subject = __('Подтверждение записи', 'chrono-forge');
                $message = sprintf(
                    __('Здравствуйте, %s!\n\nВаша запись подтверждена:\n\nУслуга: %s\nСпециалист: %s\nДата: %s\nВремя: %s\n\nСпасибо за выбор наших услуг!', 'chrono-forge'),
                    $appointment->customer_name,
                    $appointment->service_name,
                    $appointment->employee_name,
                    date('d.m.Y', strtotime($appointment->appointment_date)),
                    date('H:i', strtotime($appointment->appointment_time))
                );
                break;
        }
        
        if ($subject && $message) {
            wp_mail($appointment->customer_email, $subject, $message);
        }
    }

    /**
     * Отправка уведомления администратору
     * 
     * @param object $appointment
     * @param string $action
     */
    private function send_admin_notification($appointment, $action) {
        $admin_email = get_option('admin_email');
        $subject = '';
        $message = '';
        
        switch ($action) {
            case 'created':
                $subject = __('Новая запись', 'chrono-forge');
                $message = sprintf(
                    __('Создана новая запись:\n\nКлиент: %s\nEmail: %s\nТелефон: %s\nУслуга: %s\nСпециалист: %s\nДата: %s\nВремя: %s', 'chrono-forge'),
                    $appointment->customer_name,
                    $appointment->customer_email,
                    $appointment->customer_phone,
                    $appointment->service_name,
                    $appointment->employee_name,
                    date('d.m.Y', strtotime($appointment->appointment_date)),
                    date('H:i', strtotime($appointment->appointment_time))
                );
                break;
        }
        
        if ($subject && $message) {
            wp_mail($admin_email, $subject, $message);
        }
    }

    /**
     * Получить услуги для AJAX
     */
    public function get_services() {
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_nonce')) {
            wp_die(__('Ошибка безопасности', 'chrono-forge'));
        }

        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $employee_id = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : null;

        $args = array();
        if ($category_id) {
            $args['category_id'] = $category_id;
        }
        if ($employee_id) {
            $args['employee_id'] = $employee_id;
        }

        $services = $this->db_manager->get_all_services($args);

        $html = '';
        if (!empty($services)) {
            $html .= '<div class="cf-services-list">';
            foreach ($services as $service) {
                $price_html = $service->price > 0 ? '<span class="cf-service-price">' . chrono_forge_format_price($service->price) . '</span>' : '';

                $html .= sprintf(
                    '<div class="cf-service-item" data-service-id="%d" data-duration="%d" data-price="%.2f">
                        <div class="cf-service-info">
                            <h4>%s</h4>
                            <p>%s</p>
                            <div class="cf-service-meta">
                                <span class="cf-service-duration">%d мин.</span>
                                %s
                            </div>
                        </div>
                    </div>',
                    $service->id,
                    $service->duration,
                    $service->price,
                    esc_html($service->name),
                    esc_html($service->description),
                    $service->duration,
                    $price_html
                );
            }
            $html .= '</div>';
        } else {
            $html = '<p>' . __('Услуги не найдены.', 'chrono-forge') . '</p>';
        }

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Получить сотрудников для AJAX
     */
    public function get_employees() {
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_nonce')) {
            wp_die(__('Ошибка безопасности', 'chrono-forge'));
        }

        $service_id = !empty($_POST['service_id']) ? intval($_POST['service_id']) : null;

        $args = array();
        if ($service_id) {
            $args['service_id'] = $service_id;
        }

        $employees = $this->db_manager->get_all_employees($args);

        $html = '';
        if (!empty($employees)) {
            $html .= '<div class="cf-employees-grid">';
            foreach ($employees as $employee) {
                $photo_html = !empty($employee->photo) ?
                    '<img src="' . esc_url($employee->photo) . '" alt="' . esc_attr($employee->name) . '">' :
                    '<div class="cf-employee-avatar" style="background-color: ' . esc_attr($employee->color) . ';">' . esc_html(mb_substr($employee->name, 0, 1)) . '</div>';

                $html .= sprintf(
                    '<div class="cf-employee-item" data-employee-id="%d">
                        <div class="cf-employee-photo">%s</div>
                        <div class="cf-employee-info">
                            <h4>%s</h4>
                            <p>%s</p>
                        </div>
                    </div>',
                    $employee->id,
                    $photo_html,
                    esc_html($employee->name),
                    esc_html($employee->description)
                );
            }
            $html .= '</div>';
        } else {
            $html = '<p>' . __('Сотрудники не найдены.', 'chrono-forge') . '</p>';
        }

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Отменить запись
     */
    public function cancel_appointment() {
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_nonce')) {
            wp_die(__('Ошибка безопасности', 'chrono-forge'));
        }

        $appointment_id = intval($_POST['appointment_id']);

        if (!$appointment_id) {
            wp_send_json_error(__('Неверный ID записи', 'chrono-forge'));
        }

        // Получаем запись
        $appointment = $this->db_manager->get_appointment($appointment_id);
        if (!$appointment) {
            wp_send_json_error(__('Запись не найдена', 'chrono-forge'));
        }

        // Проверяем права доступа (если пользователь авторизован)
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $customer = $this->db_manager->get_customer_by_email($current_user->user_email);

            if (!$customer || $customer->id != $appointment->customer_id) {
                wp_send_json_error(__('У вас нет прав для отмены этой записи', 'chrono-forge'));
            }
        }

        // Обновляем статус записи
        $result = $this->db_manager->update_appointment_status($appointment_id, 'cancelled');

        if ($result) {
            // Отправляем уведомления об отмене
            $this->send_appointment_notifications($appointment_id, 'cancelled');

            wp_send_json_success(__('Запись успешно отменена', 'chrono-forge'));
        } else {
            wp_send_json_error(__('Ошибка при отмене записи', 'chrono-forge'));
        }
    }

    /**
     * Поиск доступности
     */
    public function search_availability() {
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_nonce')) {
            wp_die(__('Ошибка безопасности', 'chrono-forge'));
        }

        $category_id = !empty($_POST['category']) ? intval($_POST['category']) : null;
        $service_id = !empty($_POST['service']) ? intval($_POST['service']) : null;
        $employee_id = !empty($_POST['employee']) ? sanitize_text_field($_POST['employee']) : null;
        $date_from = !empty($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : date('Y-m-d');
        $date_to = !empty($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : date('Y-m-d', strtotime('+7 days'));
        $time_preference = !empty($_POST['time_preference']) ? sanitize_text_field($_POST['time_preference']) : null;

        $results = array();

        // Получаем услуги для поиска
        $services = array();
        if ($service_id) {
            $service = $this->db_manager->get_service($service_id);
            if ($service) {
                $services = array($service);
            }
        } else {
            $args = array();
            if ($category_id) {
                $args['category_id'] = $category_id;
            }
            $services = $this->db_manager->get_all_services($args);
        }

        // Получаем сотрудников для поиска
        $employees = array();
        if ($employee_id && $employee_id !== 'any') {
            $employee = $this->db_manager->get_employee($employee_id);
            if ($employee) {
                $employees = array($employee);
            }
        } else {
            $employees = $this->db_manager->get_all_employees();
        }

        // Поиск доступных слотов
        foreach ($services as $service) {
            foreach ($employees as $employee) {
                // Проверяем, может ли сотрудник выполнять эту услугу
                $employee_services = $this->db_manager->get_employee_services($employee->id);
                $can_perform = false;
                foreach ($employee_services as $emp_service) {
                    if ($emp_service->id == $service->id) {
                        $can_perform = true;
                        break;
                    }
                }

                if (!$can_perform) continue;

                // Поиск слотов в диапазоне дат
                $current_date = $date_from;
                while (strtotime($current_date) <= strtotime($date_to)) {
                    $slots = $this->get_available_slots_for_date($service->id, $employee->id, $current_date);

                    foreach ($slots as $slot) {
                        // Фильтр по времени
                        if ($time_preference) {
                            $hour = intval(substr($slot['time'], 0, 2));
                            $skip = false;

                            switch ($time_preference) {
                                case 'morning':
                                    if ($hour < 9 || $hour >= 12) $skip = true;
                                    break;
                                case 'afternoon':
                                    if ($hour < 12 || $hour >= 17) $skip = true;
                                    break;
                                case 'evening':
                                    if ($hour < 17 || $hour >= 21) $skip = true;
                                    break;
                            }

                            if ($skip) continue;
                        }

                        $results[] = array(
                            'service_id' => $service->id,
                            'service_name' => $service->name,
                            'employee_id' => $employee->id,
                            'employee_name' => $employee->name,
                            'date' => $current_date,
                            'time' => $slot['time'],
                            'display_time' => $slot['display_time'],
                            'price' => $service->price > 0 ? chrono_forge_format_price($service->price) : null
                        );
                    }

                    $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
                }
            }
        }

        // Ограничиваем количество результатов
        $results = array_slice($results, 0, 20);

        wp_send_json_success($results);
    }

    /**
     * Получить доступные слоты для конкретной даты
     */
    private function get_available_slots_for_date($service_id, $employee_id, $date) {
        // Получаем график работы сотрудника
        $schedule = $this->db_manager->get_employee_schedule($employee_id);
        $day_of_week = date('w', strtotime($date));

        $working_day = null;
        foreach ($schedule as $day) {
            if ($day->day_of_week == $day_of_week && $day->is_working) {
                $working_day = $day;
                break;
            }
        }

        if (!$working_day) {
            return array();
        }

        // Получаем информацию об услуге
        $service = $this->db_manager->get_service($service_id);
        if (!$service) {
            return array();
        }

        // Получаем существующие записи на этот день
        $existing_appointments = $this->db_manager->get_all_appointments(array(
            'employee_id' => $employee_id,
            'date_from' => $date,
            'date_to' => $date
        ));

        // Генерируем слоты
        $slots = array();
        $start_time = strtotime($date . ' ' . $working_day->start_time);
        $end_time = strtotime($date . ' ' . $working_day->end_time);
        $slot_duration = 30 * 60; // 30 минут

        for ($time = $start_time; $time < $end_time; $time += $slot_duration) {
            $slot_time = date('H:i:s', $time);
            $slot_end = date('H:i:s', $time + ($service->duration * 60));

            // Проверяем, не занят ли слот
            $is_available = true;
            foreach ($existing_appointments as $appointment) {
                $app_start = strtotime($appointment->appointment_time);
                $app_end = strtotime($appointment->end_time);

                if (($time >= $app_start && $time < $app_end) ||
                    ($time + ($service->duration * 60) > $app_start && $time + ($service->duration * 60) <= $app_end)) {
                    $is_available = false;
                    break;
                }
            }

            if ($is_available) {
                $slots[] = array(
                    'time' => $slot_time,
                    'display_time' => date('H:i', $time)
                );
            }
        }

        return $slots;
    }

    /**
     * Get employee data for editing
     *
     * @since 1.0.0
     * @return void Sends JSON response
     */
    public function get_employee_data() {
        try {
            // Security checks
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'chrono_forge_nonce')) {
                chrono_forge_log('Security error: Invalid nonce in get_employee_data', 'error');
                wp_send_json_error(__('Ошибка безопасности', 'chrono-forge'));
            }

            if (!current_user_can('manage_options')) {
                chrono_forge_log('Access denied: User lacks manage_options capability in get_employee_data', 'warning');
                wp_send_json_error(__('Недостаточно прав доступа', 'chrono-forge'));
            }

            // Validate and sanitize input
            $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;

            if (!$employee_id || $employee_id <= 0) {
                chrono_forge_log("Invalid employee ID: {$employee_id}", 'warning');
                wp_send_json_error(__('Неверный ID сотрудника', 'chrono-forge'));
            }

            // Get employee data with error handling
            $employee = $this->db_manager->get_employee($employee_id);
            if (!$employee) {
                chrono_forge_log("Employee not found: {$employee_id}", 'warning');
                wp_send_json_error(__('Сотрудник не найден', 'chrono-forge'));
            }

            // Get employee services with error handling
            $employee_services = $this->db_manager->get_employee_services($employee_id);
            $service_ids = array();

            if (is_array($employee_services)) {
                foreach ($employee_services as $service) {
                    if (isset($service->id)) {
                        $service_ids[] = intval($service->id);
                    }
                }
            }

            chrono_forge_log("Successfully retrieved employee data for ID: {$employee_id}", 'info');

            wp_send_json_success(array(
                'employee' => $employee,
                'services' => $service_ids
            ));

        } catch (Exception $e) {
            chrono_forge_log("Exception in get_employee_data: " . $e->getMessage(), 'error');
            wp_send_json_error(__('Произошла ошибка при получении данных сотрудника', 'chrono-forge'));
        }
    }

    /**
     * Get employee schedule for editing
     */
    public function get_employee_schedule() {
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_nonce')) {
            wp_die(__('Ошибка безопасности', 'chrono-forge'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('Недостаточно прав доступа', 'chrono-forge'));
        }

        $employee_id = intval($_POST['employee_id']);

        if (!$employee_id) {
            wp_send_json_error(__('Неверный ID сотрудника', 'chrono-forge'));
        }

        $schedule = $this->db_manager->get_employee_schedule($employee_id);

        wp_send_json_success($schedule);
    }

    /**
     * Get calendar appointments for FullCalendar
     *
     * @since 1.0.0
     * @return void Sends JSON response
     */
    public function get_calendar_appointments() {
        try {
            // Security checks
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'chrono_forge_nonce')) {
                chrono_forge_log('Security error: Invalid nonce in get_calendar_appointments', 'error');
                wp_send_json_error(__('Ошибка безопасности', 'chrono-forge'));
            }

            if (!current_user_can('manage_options')) {
                chrono_forge_log('Access denied: User lacks manage_options capability in get_calendar_appointments', 'warning');
                wp_send_json_error(__('Недостаточно прав доступа', 'chrono-forge'));
            }

            // Sanitize and validate filters
            $filters = isset($_POST['filters']) && is_array($_POST['filters']) ? $_POST['filters'] : array();
            $args = array();

            // Validate and sanitize each filter
            if (!empty($filters['employee_id'])) {
                $employee_id = intval($filters['employee_id']);
                if ($employee_id > 0) {
                    $args['employee_id'] = $employee_id;
                }
            }

            if (!empty($filters['service_id'])) {
                $service_id = intval($filters['service_id']);
                if ($service_id > 0) {
                    $args['service_id'] = $service_id;
                }
            }

            if (!empty($filters['status'])) {
                $status = sanitize_text_field($filters['status']);
                $valid_statuses = array_keys(chrono_forge_get_appointment_statuses());
                if (in_array($status, $valid_statuses)) {
                    $args['status'] = $status;
                }
            }

            if (!empty($filters['start'])) {
                $start_date = sanitize_text_field($filters['start']);
                if ($this->is_valid_date($start_date)) {
                    $args['date_from'] = $start_date;
                }
            }

            if (!empty($filters['end'])) {
                $end_date = sanitize_text_field($filters['end']);
                if ($this->is_valid_date($end_date)) {
                    $args['date_to'] = $end_date;
                }
            }

            // Add limit to prevent excessive data loading
            $args['limit'] = 1000;

            $appointments = $this->db_manager->get_all_appointments($args);

            if (!is_array($appointments)) {
                chrono_forge_log('Failed to retrieve appointments from database', 'error');
                wp_send_json_error(__('Ошибка при получении записей', 'chrono-forge'));
            }

            // Format appointments for FullCalendar with proper sanitization
            $events = array();
            foreach ($appointments as $appointment) {
                if (!is_object($appointment)) {
                    continue;
                }

                $events[] = array(
                    'id' => intval($appointment->id ?? 0),
                    'service_name' => esc_html($appointment->service_name ?? ''),
                    'customer_name' => esc_html(trim(($appointment->customer_first_name ?? '') . ' ' . ($appointment->customer_last_name ?? ''))),
                    'customer_email' => sanitize_email($appointment->customer_email ?? ''),
                    'customer_phone' => esc_html($appointment->customer_phone ?? ''),
                    'employee_name' => esc_html($appointment->employee_name ?? ''),
                    'appointment_date' => sanitize_text_field($appointment->appointment_date ?? ''),
                    'appointment_time' => sanitize_text_field($appointment->appointment_time ?? ''),
                    'end_time' => sanitize_text_field($appointment->end_time ?? ''),
                    'status' => sanitize_text_field($appointment->status ?? ''),
                    'total_price' => floatval($appointment->total_price ?? 0),
                    'notes' => esc_html($appointment->notes ?? '')
                );
            }

            chrono_forge_log("Successfully retrieved " . count($events) . " calendar appointments", 'info');
            wp_send_json_success($events);

        } catch (Exception $e) {
            chrono_forge_log("Exception in get_calendar_appointments: " . $e->getMessage(), 'error');
            wp_send_json_error(__('Произошла ошибка при получении календарных записей', 'chrono-forge'));
        }
    }

    /**
     * Get available slots for any employee
     */
    public function get_available_slots_any() {
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_nonce')) {
            wp_die(__('Ошибка безопасности', 'chrono-forge'));
        }

        $service_id = intval($_POST['service_id']);
        $date = sanitize_text_field($_POST['date']);

        if (!$service_id || !$date) {
            wp_send_json_error(__('Неверные параметры', 'chrono-forge'));
        }

        // Get all employees who can perform this service
        $employees = $this->db_manager->get_employees_by_service($service_id);

        if (empty($employees)) {
            wp_send_json_error(__('Нет доступных специалистов для данной услуги', 'chrono-forge'));
        }

        $all_slots = array();

        // Get slots for each employee and merge them
        foreach ($employees as $employee) {
            $employee_slots = $this->get_employee_available_slots($service_id, $employee->id, $date);
            $all_slots = array_merge($all_slots, $employee_slots);
        }

        // Remove duplicates and sort by time
        $unique_slots = array();
        $seen_times = array();

        foreach ($all_slots as $slot) {
            if (!in_array($slot['time'], $seen_times)) {
                $unique_slots[] = $slot;
                $seen_times[] = $slot['time'];
            }
        }

        // Sort by time
        usort($unique_slots, function($a, $b) {
            return strcmp($a['time'], $b['time']);
        });

        wp_send_json_success($unique_slots);
    }

    /**
     * Get available slots for a specific employee
     */
    private function get_employee_available_slots($service_id, $employee_id, $date) {
        // Validate date format
        if (!$this->is_valid_date($date)) {
            return array();
        }

        // Get service information
        $service = $this->db_manager->get_service($service_id);
        if (!$service) {
            return array();
        }

        // Get employee schedule for the day
        $schedule = $this->db_manager->get_employee_schedule($employee_id);
        $day_of_week = date('w', strtotime($date));

        $working_day = null;
        foreach ($schedule as $day) {
            if ($day->day_of_week == $day_of_week && $day->is_working) {
                $working_day = $day;
                break;
            }
        }

        if (!$working_day) {
            return array();
        }

        // Get existing appointments for this employee on this date
        $existing_appointments = $this->db_manager->get_all_appointments(array(
            'employee_id' => $employee_id,
            'date_from' => $date,
            'date_to' => $date,
            'status' => array('confirmed', 'pending')
        ));

        // Generate time slots
        $slots = array();
        $start_time = strtotime($date . ' ' . $working_day->start_time);
        $end_time = strtotime($date . ' ' . $working_day->end_time);
        $slot_duration = 30 * 60; // 30 minutes

        for ($time = $start_time; $time < $end_time; $time += $slot_duration) {
            $slot_time = date('H:i:s', $time);
            $slot_end_time = $time + ($service->duration * 60);

            // Check if slot is available
            $is_available = true;
            foreach ($existing_appointments as $appointment) {
                $app_start = strtotime($date . ' ' . $appointment->appointment_time);
                $app_end = strtotime($date . ' ' . $appointment->end_time);

                // Check for overlap
                if (($time >= $app_start && $time < $app_end) ||
                    ($slot_end_time > $app_start && $slot_end_time <= $app_end) ||
                    ($time <= $app_start && $slot_end_time >= $app_end)) {
                    $is_available = false;
                    break;
                }
            }

            if ($is_available && $slot_end_time <= $end_time) {
                $slots[] = array(
                    'time' => $slot_time,
                    'display_time' => date('H:i', $time),
                    'employee_id' => $employee_id
                );
            }
        }

        return $slots;
    }

    /**
     * Validate date format
     *
     * @since 1.0.0
     * @param string $date Date string to validate
     * @return bool True if valid date format
     */
    private function is_valid_date($date) {
        if (empty($date)) {
            return false;
        }

        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Validate time format
     *
     * @since 1.0.0
     * @param string $time Time string to validate
     * @return bool True if valid time format
     */
    private function is_valid_time($time) {
        if (empty($time)) {
            return false;
        }

        $t = DateTime::createFromFormat('H:i:s', $time);
        if (!$t) {
            $t = DateTime::createFromFormat('H:i', $time);
        }

        return $t !== false;
    }

    /**
     * Sanitize and validate appointment status
     *
     * @since 1.0.0
     * @param string $status Status to validate
     * @return string|false Valid status or false if invalid
     */
    private function validate_appointment_status($status) {
        $valid_statuses = array_keys(chrono_forge_get_appointment_statuses());
        $status = sanitize_text_field($status);

        return in_array($status, $valid_statuses) ? $status : false;
    }

    /**
     * Rate limiting check for AJAX requests
     *
     * @since 1.0.0
     * @param string $action Action name
     * @param int $limit Number of requests allowed per minute
     * @return bool True if within rate limit
     */
    private function check_rate_limit($action, $limit = 60) {
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $transient_key = 'chrono_forge_rate_limit_' . md5($user_ip . $action);

        $current_count = get_transient($transient_key);

        if ($current_count === false) {
            set_transient($transient_key, 1, 60); // 1 minute
            return true;
        }

        if ($current_count >= $limit) {
            chrono_forge_log("Rate limit exceeded for action {$action} from IP {$user_ip}", 'warning');
            return false;
        }

        set_transient($transient_key, $current_count + 1, 60);
        return true;
    }

    /**
     * Disable emergency mode via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function disable_emergency_mode() {
        try {
            // Security checks
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'chrono_forge_emergency')) {
                chrono_forge_safe_log('Security error: Invalid nonce in disable_emergency_mode', 'error');
                wp_send_json_error(__('Ошибка безопасности', 'chrono-forge'));
            }

            if (!current_user_can('manage_options')) {
                chrono_forge_safe_log('Access denied: User lacks manage_options capability in disable_emergency_mode', 'warning');
                wp_send_json_error(__('Недостаточно прав доступа', 'chrono-forge'));
            }

            // Clear emergency mode options directly
            delete_option('chrono_forge_emergency_mode');
            delete_option('chrono_forge_emergency_error');
            delete_option('chrono_forge_emergency_time');

            chrono_forge_safe_log('Emergency mode disabled via AJAX', 'info');
            wp_send_json_success(__('Аварийный режим отключен. Обновите страницу для применения изменений.', 'chrono-forge'));

        } catch (Exception $e) {
            chrono_forge_safe_log("Exception in disable_emergency_mode: " . $e->getMessage(), 'error');
            wp_send_json_error(__('Произошла ошибка при отключении аварийного режима', 'chrono-forge'));
        }
    }
}
