<?php
/**
 * Вспомогательные функции для плагина ChronoForge
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Получить настройки плагина
 * 
 * @param string $key Ключ настройки (опционально)
 * @param mixed $default Значение по умолчанию
 * @return mixed
 */
function chrono_forge_get_setting($key = '', $default = null) {
    $settings = get_option('chrono_forge_settings', array());
    
    if (empty($key)) {
        return $settings;
    }
    
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Обновить настройку плагина
 * 
 * @param string $key Ключ настройки
 * @param mixed $value Значение
 * @return bool
 */
function chrono_forge_update_setting($key, $value) {
    $settings = get_option('chrono_forge_settings', array());
    $settings[$key] = $value;
    return update_option('chrono_forge_settings', $settings);
}

/**
 * Форматировать цену
 * 
 * @param float $price
 * @return string
 */
function chrono_forge_format_price($price) {
    $currency_symbol = chrono_forge_get_setting('currency_symbol', '$');
    return $currency_symbol . number_format($price, 2);
}

/**
 * Форматировать дату
 * 
 * @param string $date
 * @param string $format
 * @return string
 */
function chrono_forge_format_date($date, $format = '') {
    if (empty($format)) {
        $format = chrono_forge_get_setting('date_format', 'Y-m-d');
    }
    
    return date($format, strtotime($date));
}

/**
 * Форматировать время
 * 
 * @param string $time
 * @param string $format
 * @return string
 */
function chrono_forge_format_time($time, $format = '') {
    if (empty($format)) {
        $format = chrono_forge_get_setting('time_format', 'H:i');
    }
    
    return date($format, strtotime($time));
}

/**
 * Получить статусы записей
 * 
 * @return array
 */
function chrono_forge_get_appointment_statuses() {
    return array(
        'pending' => __('Ожидает подтверждения', 'chrono-forge'),
        'confirmed' => __('Подтверждена', 'chrono-forge'),
        'completed' => __('Завершена', 'chrono-forge'),
        'cancelled' => __('Отменена', 'chrono-forge'),
        'no_show' => __('Не явился', 'chrono-forge')
    );
}

/**
 * Получить статусы платежей
 * 
 * @return array
 */
function chrono_forge_get_payment_statuses() {
    return array(
        'pending' => __('Ожидает оплаты', 'chrono-forge'),
        'completed' => __('Оплачено', 'chrono-forge'),
        'failed' => __('Ошибка оплаты', 'chrono-forge'),
        'refunded' => __('Возврат', 'chrono-forge')
    );
}

/**
 * Получить методы оплаты
 * 
 * @return array
 */
function chrono_forge_get_payment_methods() {
    return array(
        'cash' => __('Наличные', 'chrono-forge'),
        'stripe' => __('Stripe', 'chrono-forge'),
        'paypal' => __('PayPal', 'chrono-forge'),
        'square' => __('Square', 'chrono-forge'),
        'woocommerce' => __('WooCommerce', 'chrono-forge')
    );
}

/**
 * Получить дни недели
 * 
 * @return array
 */
function chrono_forge_get_weekdays() {
    return array(
        0 => __('Воскресенье', 'chrono-forge'),
        1 => __('Понедельник', 'chrono-forge'),
        2 => __('Вторник', 'chrono-forge'),
        3 => __('Среда', 'chrono-forge'),
        4 => __('Четверг', 'chrono-forge'),
        5 => __('Пятница', 'chrono-forge'),
        6 => __('Суббота', 'chrono-forge')
    );
}

/**
 * Проверить, является ли дата рабочим днем
 * 
 * @param string $date
 * @param int $employee_id
 * @return bool
 */
function chrono_forge_is_working_day($date, $employee_id) {
    $db_manager = chrono_forge()->db_manager;
    $day_of_week = date('w', strtotime($date));
    
    $schedule = $db_manager->get_employee_schedule($employee_id);
    
    foreach ($schedule as $day_schedule) {
        if ($day_schedule->day_of_week == $day_of_week && $day_schedule->is_working) {
            return true;
        }
    }
    
    return false;
}

/**
 * Получить минимальную дату для бронирования
 * 
 * @return string
 */
function chrono_forge_get_min_booking_date() {
    $min_time = chrono_forge_get_setting('min_booking_time', 60); // минут
    return date('Y-m-d', strtotime("+{$min_time} minutes"));
}

/**
 * Получить максимальную дату для бронирования
 * 
 * @return string
 */
function chrono_forge_get_max_booking_date() {
    $max_days = chrono_forge_get_setting('max_booking_time', 30); // дней
    return date('Y-m-d', strtotime("+{$max_days} days"));
}

/**
 * Логирование ошибок плагина
 * 
 * @param string $message
 * @param string $level
 */
function chrono_forge_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[ChronoForge {$level}] " . $message);
    }
}

/**
 * Проверить права доступа для админ-функций
 * 
 * @return bool
 */
function chrono_forge_check_admin_permissions() {
    return current_user_can('manage_options');
}

/**
 * Получить URL админ-страницы
 * 
 * @param string $page
 * @param array $args
 * @return string
 */
function chrono_forge_get_admin_url($page = '', $args = array()) {
    $base_url = admin_url('admin.php');
    
    if (!empty($page)) {
        $args['page'] = 'chrono-forge-' . $page;
    } else {
        $args['page'] = 'chrono-forge';
    }
    
    return add_query_arg($args, $base_url);
}

/**
 * Получить цвета для календаря
 * 
 * @return array
 */
function chrono_forge_get_calendar_colors() {
    return array(
        '#3498db', // Синий
        '#e74c3c', // Красный
        '#2ecc71', // Зеленый
        '#f39c12', // Оранжевый
        '#9b59b6', // Фиолетовый
        '#1abc9c', // Бирюзовый
        '#34495e', // Темно-серый
        '#e67e22', // Морковный
        '#95a5a6', // Серый
        '#f1c40f'  // Желтый
    );
}

/**
 * Валидация email
 * 
 * @param string $email
 * @return bool
 */
function chrono_forge_validate_email($email) {
    return is_email($email);
}

/**
 * Валидация телефона
 * 
 * @param string $phone
 * @return bool
 */
function chrono_forge_validate_phone($phone) {
    // Простая валидация телефона
    $phone = preg_replace('/[^0-9+\-\(\)\s]/', '', $phone);
    return strlen($phone) >= 10;
}

/**
 * Генерация уникального ID для записи
 * 
 * @return string
 */
function chrono_forge_generate_appointment_id() {
    return 'CF' . date('Ymd') . '-' . wp_generate_password(6, false);
}

/**
 * Получить следующий доступный слот
 * 
 * @param int $employee_id
 * @param int $service_id
 * @param string $start_date
 * @return array|null
 */
function chrono_forge_get_next_available_slot($employee_id, $service_id, $start_date = '') {
    if (empty($start_date)) {
        $start_date = chrono_forge_get_min_booking_date();
    }
    
    $max_date = chrono_forge_get_max_booking_date();
    $current_date = $start_date;
    
    $ajax_handler = new ChronoForge_Ajax_Handler(chrono_forge()->db_manager);
    
    while ($current_date <= $max_date) {
        if (chrono_forge_is_working_day($current_date, $employee_id)) {
            // Здесь можно добавить логику поиска доступных слотов
            // Пока возвращаем первый рабочий день
            return array(
                'date' => $current_date,
                'time' => '09:00'
            );
        }
        
        $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
    }
    
    return null;
}

/**
 * Конвертация времени в минуты
 * 
 * @param string $time Время в формате H:i
 * @return int Минуты с начала дня
 */
function chrono_forge_time_to_minutes($time) {
    $parts = explode(':', $time);
    return intval($parts[0]) * 60 + intval($parts[1]);
}

/**
 * Конвертация минут во время
 * 
 * @param int $minutes Минуты с начала дня
 * @return string Время в формате H:i
 */
function chrono_forge_minutes_to_time($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return sprintf('%02d:%02d', $hours, $mins);
}
