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
    }

    /**
     * Получить доступные временные слоты
     */
    public function get_available_slots() {
        // Проверка nonce для безопасности
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_nonce')) {
            wp_die(__('Ошибка безопасности', 'chrono-forge'));
        }

        $service_id = intval($_POST['service_id']);
        $employee_id = intval($_POST['employee_id']);
        $date = sanitize_text_field($_POST['date']);

        // Валидация входных данных
        if (!$service_id || !$employee_id || !$date) {
            wp_send_json_error(__('Неверные параметры', 'chrono-forge'));
        }

        // Проверка корректности даты
        if (!$this->is_valid_date($date)) {
            wp_send_json_error(__('Неверный формат даты', 'chrono-forge'));
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
        // Проверка nonce для безопасности
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_nonce')) {
            wp_die(__('Ошибка безопасности', 'chrono-forge'));
        }

        // Получение и валидация данных
        $service_id = intval($_POST['service_id']);
        $employee_id = intval($_POST['employee_id']);
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        $customer_data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
        );
        $notes = sanitize_textarea_field($_POST['notes']);

        // Валидация обязательных полей
        if (!$service_id || !$employee_id || !$date || !$time || 
            !$customer_data['first_name'] || !$customer_data['last_name'] || !$customer_data['email']) {
            wp_send_json_error(__('Заполните все обязательные поля', 'chrono-forge'));
        }

        // Валидация email
        if (!is_email($customer_data['email'])) {
            wp_send_json_error(__('Неверный формат email', 'chrono-forge'));
        }

        // Получение информации об услуге
        $service = $this->db_manager->get_service($service_id);
        if (!$service) {
            wp_send_json_error(__('Услуга не найдена', 'chrono-forge'));
        }

        // Вычисление времени окончания
        $end_time = date('H:i:s', strtotime($time) + ($service->duration * 60));

        // Проверка доступности слота
        if (!$this->db_manager->is_slot_available($employee_id, $date, $time, $end_time)) {
            wp_send_json_error(__('Выбранное время уже занято', 'chrono-forge'));
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
}
