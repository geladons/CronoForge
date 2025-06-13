<?php
/**
 * Вспомогательные функции для плагина ChronoForge
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Safe logging function that works during activation
 *
 * @since 1.0.0
 * @param string $message Message to log
 * @param string $level Log level
 * @return void
 */
if (!function_exists('chrono_forge_safe_log')) {
    function chrono_forge_safe_log($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $timestamp = date('Y-m-d H:i:s');
            error_log("[{$timestamp}] [ChronoForge {$level}] {$message}");
        }
    }
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
    // Avoid circular dependency by checking if plugin is loaded
    $plugin_instance = function_exists('chrono_forge') ? chrono_forge() : null;
    if (!$plugin_instance || !$plugin_instance->db_manager) {
        return false; // Safe fallback
    }

    $db_manager = $plugin_instance->db_manager;
    $day_of_week = date('w', strtotime($date));

    try {
        $schedule = $db_manager->get_employee_schedule($employee_id);

        if (!is_array($schedule)) {
            return false;
        }

        foreach ($schedule as $day_schedule) {
            if (isset($day_schedule->day_of_week) && isset($day_schedule->is_working) &&
                $day_schedule->day_of_week == $day_of_week && $day_schedule->is_working) {
                return true;
            }
        }
    } catch (Exception $e) {
        chrono_forge_safe_log("Error checking working day: " . $e->getMessage(), 'error');
        return false;
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
 * Улучшенное логирование ошибок плагина
 *
 * @since 1.0.0
 * @param string $message Сообщение для логирования
 * @param string $level Уровень логирования (error, warning, info, debug)
 * @param array $context Дополнительный контекст
 * @return void
 */
function chrono_forge_log($message, $level = 'info', $context = array()) {
    // Проверяем, включено ли логирование
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    try {
        // Валидация уровня логирования
        $valid_levels = array('error', 'warning', 'info', 'debug');
        if (!in_array($level, $valid_levels)) {
            $level = 'info';
        }

        // Форматируем сообщение с проверкой функций
        $timestamp = function_exists('current_time') ? current_time('Y-m-d H:i:s') : date('Y-m-d H:i:s');
        $formatted_message = "[{$timestamp}] [ChronoForge {$level}] {$message}";

        // Добавляем контекст если есть
        if (!empty($context) && function_exists('wp_json_encode')) {
            $formatted_message .= ' Context: ' . wp_json_encode($context);
        }

        // Добавляем информацию о пользователе если доступна
        if (function_exists('is_user_logged_in') && is_user_logged_in() && function_exists('wp_get_current_user')) {
            $user = wp_get_current_user();
            if ($user && isset($user->user_login) && isset($user->ID)) {
                $formatted_message .= " User: {$user->user_login} (ID: {$user->ID})";
            }
        }

        // Добавляем IP адрес
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $formatted_message .= " IP: {$ip}";

        // Логируем в файл WordPress
        error_log($formatted_message);

        // Для критических ошибок также сохраняем в базу данных
        if ($level === 'error' && function_exists('chrono_forge_save_error_to_db')) {
            chrono_forge_save_error_to_db($message, $context);
        }

        // Отправляем уведомление администратору при критических ошибках
        if ($level === 'error' && function_exists('chrono_forge_get_setting') &&
            chrono_forge_get_setting('notify_admin_on_errors', false) &&
            function_exists('chrono_forge_notify_admin_error')) {
            chrono_forge_notify_admin_error($message, $context);
        }

    } catch (Exception $e) {
        // Fallback to basic error logging
        error_log("[ChronoForge] Logging error: " . $e->getMessage());
        error_log("[ChronoForge] Original message: {$message}");
    }
}

/**
 * Сохранить критическую ошибку в базу данных
 *
 * @since 1.0.0
 * @param string $message Сообщение об ошибке
 * @param array $context Контекст ошибки
 * @return void
 */
function chrono_forge_save_error_to_db($message, $context = array()) {
    global $wpdb;

    try {
        $table_name = $wpdb->prefix . 'chrono_forge_error_log';

        // Создаем таблицу если не существует
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            message text NOT NULL,
            context longtext,
            user_id int(11),
            ip_address varchar(45),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Вставляем запись об ошибке
        $wpdb->insert(
            $table_name,
            array(
                'message' => $message,
                'context' => wp_json_encode($context),
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s', '%s')
        );

        // Очищаем старые записи (старше 30 дней)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE created_at < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));

    } catch (Exception $e) {
        // Если не можем записать в БД, логируем в файл
        error_log("[ChronoForge] Failed to save error to database: " . $e->getMessage());
    }
}

/**
 * Уведомить администратора о критической ошибке
 *
 * @since 1.0.0
 * @param string $message Сообщение об ошибке
 * @param array $context Контекст ошибки
 * @return void
 */
function chrono_forge_notify_admin_error($message, $context = array()) {
    // Проверяем, не отправляли ли уже уведомление недавно
    $transient_key = 'chrono_forge_error_notification_' . md5($message);
    if (get_transient($transient_key)) {
        return; // Уже отправляли уведомление в последний час
    }

    $admin_email = get_option('admin_email');
    if (!$admin_email) {
        return;
    }

    $subject = __('ChronoForge: Критическая ошибка на сайте', 'chrono-forge') . ' ' . get_bloginfo('name');

    $body = __('На вашем сайте произошла критическая ошибка в плагине ChronoForge:', 'chrono-forge') . "\n\n";
    $body .= __('Сообщение об ошибке:', 'chrono-forge') . " {$message}\n\n";

    if (!empty($context)) {
        $body .= __('Дополнительная информация:', 'chrono-forge') . "\n" . print_r($context, true) . "\n\n";
    }

    $body .= __('Время:', 'chrono-forge') . ' ' . current_time('Y-m-d H:i:s') . "\n";
    $body .= __('URL:', 'chrono-forge') . ' ' . home_url() . "\n";

    wp_mail($admin_email, $subject, $body);

    // Устанавливаем транзиент на 1 час, чтобы не спамить
    set_transient($transient_key, true, HOUR_IN_SECONDS);
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
    // Avoid circular dependency by checking if plugin is loaded
    $plugin_instance = function_exists('chrono_forge') ? chrono_forge() : null;
    if (!$plugin_instance || !$plugin_instance->db_manager) {
        return null; // Safe fallback
    }

    try {
        if (empty($start_date)) {
            $start_date = chrono_forge_get_min_booking_date();
        }

        $max_date = chrono_forge_get_max_booking_date();
        $current_date = $start_date;

        // Avoid creating new AJAX handler to prevent circular dependency
        $max_iterations = 30; // Prevent infinite loops
        $iterations = 0;

        while ($current_date <= $max_date && $iterations < $max_iterations) {
            if (chrono_forge_is_working_day($current_date, $employee_id)) {
                // Simple slot availability check
                return array(
                    'date' => $current_date,
                    'time' => '09:00'
                );
            }

            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
            $iterations++;
        }
    } catch (Exception $e) {
        chrono_forge_safe_log("Error getting next available slot: " . $e->getMessage(), 'error');
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

/**
 * Безопасное выполнение операции с повторными попытками
 *
 * @since 1.0.0
 * @param callable $callback Функция для выполнения
 * @param int $max_attempts Максимальное количество попыток
 * @param int $delay Задержка между попытками в секундах
 * @return mixed Результат выполнения функции или false при неудаче
 */
function chrono_forge_retry_operation($callback, $max_attempts = 3, $delay = 1) {
    $attempts = 0;

    while ($attempts < $max_attempts) {
        try {
            $result = call_user_func($callback);
            if ($result !== false) {
                return $result;
            }
        } catch (Exception $e) {
            chrono_forge_log("Attempt " . ($attempts + 1) . " failed: " . $e->getMessage(), 'warning');
        }

        $attempts++;
        if ($attempts < $max_attempts) {
            sleep($delay);
        }
    }

    chrono_forge_log("Operation failed after {$max_attempts} attempts", 'error');
    return false;
}

/**
 * Валидация и санитизация пользовательского ввода
 *
 * @since 1.0.0
 * @param mixed $input Входные данные
 * @param string $type Тип валидации (email, phone, date, time, int, string)
 * @param array $options Дополнительные опции валидации
 * @return mixed Санитизированные данные или false при ошибке
 */
function chrono_forge_validate_input($input, $type, $options = array()) {
    if ($input === null || $input === '') {
        return isset($options['allow_empty']) && $options['allow_empty'] ? '' : false;
    }

    switch ($type) {
        case 'email':
            $sanitized = sanitize_email($input);
            return is_email($sanitized) ? $sanitized : false;

        case 'phone':
            $sanitized = preg_replace('/[^0-9+\-\(\)\s]/', '', $input);
            $min_length = isset($options['min_length']) ? $options['min_length'] : 10;
            return strlen($sanitized) >= $min_length ? $sanitized : false;

        case 'date':
            $sanitized = sanitize_text_field($input);
            $d = DateTime::createFromFormat('Y-m-d', $sanitized);
            return ($d && $d->format('Y-m-d') === $sanitized) ? $sanitized : false;

        case 'time':
            $sanitized = sanitize_text_field($input);
            $t = DateTime::createFromFormat('H:i:s', $sanitized);
            if (!$t) {
                $t = DateTime::createFromFormat('H:i', $sanitized);
                if ($t) {
                    $sanitized = $t->format('H:i:s');
                }
            }
            return $t ? $sanitized : false;

        case 'int':
            $sanitized = intval($input);
            $min = isset($options['min']) ? $options['min'] : 0;
            $max = isset($options['max']) ? $options['max'] : PHP_INT_MAX;
            return ($sanitized >= $min && $sanitized <= $max) ? $sanitized : false;

        case 'string':
            $sanitized = sanitize_text_field($input);
            $max_length = isset($options['max_length']) ? $options['max_length'] : 255;
            return strlen($sanitized) <= $max_length ? $sanitized : false;

        case 'textarea':
            $sanitized = sanitize_textarea_field($input);
            $max_length = isset($options['max_length']) ? $options['max_length'] : 1000;
            return strlen($sanitized) <= $max_length ? $sanitized : false;

        default:
            return sanitize_text_field($input);
    }
}

/**
 * Получить информацию о производительности системы
 *
 * @since 1.0.0
 * @return array Информация о производительности
 */
function chrono_forge_get_performance_info() {
    // Safe execution time calculation
    $request_time = isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(true);
    $execution_time = microtime(true) - $request_time;

    return array(
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'memory_limit' => ini_get('memory_limit'),
        'execution_time' => $execution_time,
        'db_queries' => function_exists('get_num_queries') ? get_num_queries() : 0,
        'cache_hits' => function_exists('wp_cache_get_stats') ? wp_cache_get_stats() : array(),
    );
}

/**
 * Проверка лимитов системы
 *
 * @since 1.0.0
 * @return array Результаты проверки
 */
function chrono_forge_check_system_limits() {
    $checks = array();

    try {
        // Проверка памяти
        $memory_usage = memory_get_usage(true);
        $memory_limit_str = ini_get('memory_limit');
        $memory_limit = function_exists('wp_convert_hr_to_bytes') ?
            wp_convert_hr_to_bytes($memory_limit_str) :
            (int)$memory_limit_str * 1024 * 1024; // Fallback conversion

        $memory_percent = $memory_limit > 0 ? ($memory_usage / $memory_limit) * 100 : 0;

        $checks['memory'] = array(
            'status' => $memory_percent < 80 ? 'ok' : ($memory_percent < 95 ? 'warning' : 'critical'),
            'usage' => $memory_usage,
            'limit' => $memory_limit,
            'percent' => $memory_percent
        );

        // Проверка времени выполнения с безопасной проверкой
        $max_execution_time = ini_get('max_execution_time');
        $request_time = isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(true);
        $current_time = microtime(true) - $request_time;
        $time_percent = $max_execution_time > 0 ? ($current_time / $max_execution_time) * 100 : 0;

        $checks['execution_time'] = array(
            'status' => $time_percent < 70 ? 'ok' : ($time_percent < 90 ? 'warning' : 'critical'),
            'current' => $current_time,
            'limit' => $max_execution_time,
            'percent' => $time_percent
        );

        // Проверка количества запросов к БД
        $db_queries = function_exists('get_num_queries') ? get_num_queries() : 0;
        $checks['db_queries'] = array(
            'status' => $db_queries < 50 ? 'ok' : ($db_queries < 100 ? 'warning' : 'critical'),
            'count' => $db_queries
        );

    } catch (Exception $e) {
        chrono_forge_safe_log("Error in system limits check: " . $e->getMessage(), 'error');
        // Return safe defaults
        $checks = array(
            'memory' => array('status' => 'unknown', 'usage' => 0, 'limit' => 0, 'percent' => 0),
            'execution_time' => array('status' => 'unknown', 'current' => 0, 'limit' => 0, 'percent' => 0),
            'db_queries' => array('status' => 'unknown', 'count' => 0)
        );
    }

    return $checks;
}

/**
 * Очистка всех кэшей плагина
 *
 * @since 1.0.0
 * @return bool Успешность операции
 */
function chrono_forge_clear_all_caches() {
    try {
        // Очищаем WordPress кэш
        wp_cache_flush();

        // Очищаем транзиенты плагина
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} " .
            "WHERE option_name LIKE '_transient_chrono_forge_%' " .
            "OR option_name LIKE '_transient_timeout_chrono_forge_%'"
        );

        // Очищаем кэш объектов
        wp_cache_delete_group('chrono_forge');

        chrono_forge_log('All caches cleared successfully', 'info');
        return true;

    } catch (Exception $e) {
        chrono_forge_log('Failed to clear caches: ' . $e->getMessage(), 'error');
        return false;
    }
}
