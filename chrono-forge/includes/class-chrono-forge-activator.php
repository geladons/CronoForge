<?php
/**
 * Класс активации плагина ChronoForge
 * 
 * Этот класс определяет весь код, который выполняется при активации плагина.
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}

class ChronoForge_Activator {

    /**
     * Метод активации плагина
     * 
     * Создает необходимые таблицы в базе данных
     */
    public static function activate() {
        global $wpdb;

        // Получаем префикс таблиц WordPress
        $table_prefix = $wpdb->prefix;

        // Подключаем функцию dbDelta для безопасного создания таблиц
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Создание таблицы услуг
        $services_table = $table_prefix . 'chrono_forge_services';
        $services_sql = "CREATE TABLE $services_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            category_id int(11) DEFAULT NULL,
            duration int(11) NOT NULL DEFAULT 60,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            buffer_time int(11) NOT NULL DEFAULT 0,
            color varchar(7) DEFAULT '#3498db',
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category_id (category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        // Создание таблицы категорий услуг
        $categories_table = $table_prefix . 'chrono_forge_categories';
        $categories_sql = "CREATE TABLE $categories_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            color varchar(7) DEFAULT '#34495e',
            sort_order int(11) DEFAULT 0,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        // Создание таблицы сотрудников
        $employees_table = $table_prefix . 'chrono_forge_employees';
        $employees_sql = "CREATE TABLE $employees_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            wp_user_id int(11) DEFAULT NULL,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50),
            photo varchar(255),
            description text,
            color varchar(7) DEFAULT '#e74c3c',
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY wp_user_id (wp_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        // Создание таблицы графиков работы
        $schedules_table = $table_prefix . 'chrono_forge_schedules';
        $schedules_sql = "CREATE TABLE $schedules_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            employee_id int(11) NOT NULL,
            day_of_week tinyint(1) NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
            start_time time NOT NULL,
            end_time time NOT NULL,
            break_start time DEFAULT NULL,
            break_end time DEFAULT NULL,
            is_working tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY employee_id (employee_id),
            UNIQUE KEY unique_employee_day (employee_id, day_of_week)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        // Создание таблицы записей
        $appointments_table = $table_prefix . 'chrono_forge_appointments';
        $appointments_sql = "CREATE TABLE $appointments_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            service_id int(11) NOT NULL,
            employee_id int(11) NOT NULL,
            customer_id int(11) NOT NULL,
            appointment_date date NOT NULL,
            appointment_time time NOT NULL,
            end_time time NOT NULL,
            status enum('pending','confirmed','completed','cancelled','no_show') DEFAULT 'pending',
            notes text,
            internal_notes text,
            total_price decimal(10,2) NOT NULL DEFAULT 0.00,
            google_event_id varchar(255) DEFAULT NULL,
            outlook_event_id varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY service_id (service_id),
            KEY employee_id (employee_id),
            KEY customer_id (customer_id),
            KEY appointment_date (appointment_date),
            KEY status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        // Создание таблицы клиентов
        $customers_table = $table_prefix . 'chrono_forge_customers';
        $customers_sql = "CREATE TABLE $customers_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            wp_user_id int(11) DEFAULT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50),
            date_of_birth date DEFAULT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY wp_user_id (wp_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        // Создание таблицы платежей
        $payments_table = $table_prefix . 'chrono_forge_payments';
        $payments_sql = "CREATE TABLE $payments_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            appointment_id int(11) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            payment_method enum('stripe','paypal','square','cash','woocommerce') NOT NULL,
            transaction_id varchar(255),
            status enum('pending','completed','failed','refunded') DEFAULT 'pending',
            gateway_response text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY appointment_id (appointment_id),
            KEY transaction_id (transaction_id),
            KEY status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        // Создание таблицы связи сотрудников и услуг
        $employee_services_table = $table_prefix . 'chrono_forge_employee_services';
        $employee_services_sql = "CREATE TABLE $employee_services_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            employee_id int(11) NOT NULL,
            service_id int(11) NOT NULL,
            custom_price decimal(10,2) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_employee_service (employee_id, service_id),
            KEY employee_id (employee_id),
            KEY service_id (service_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        // Выполнение SQL-запросов для создания таблиц
        dbDelta($categories_sql);
        dbDelta($services_sql);
        dbDelta($employees_sql);
        dbDelta($schedules_sql);
        dbDelta($appointments_sql);
        dbDelta($customers_sql);
        dbDelta($payments_sql);
        dbDelta($employee_services_sql);

        // Сохранение версии плагина в опциях WordPress
        add_option('chrono_forge_version', CHRONO_FORGE_VERSION);

        // Создание базовых настроек плагина
        self::create_default_options();
    }

    /**
     * Создание настроек по умолчанию
     */
    private static function create_default_options() {
        $default_options = array(
            'plugin_language' => 'auto',
            'currency' => 'USD',
            'currency_symbol' => '$',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'booking_form_style' => 'default',
            'primary_color' => '#3498db',
            'secondary_color' => '#2c3e50',
            'enable_payments' => false,
            'payment_required' => false,
            'min_booking_time' => 60, // минут до записи
            'max_booking_time' => 30, // дней вперед
            'default_appointment_status' => 'pending',
            'enable_notifications' => true,
            'admin_email_notifications' => true,
            'customer_email_notifications' => true,
            'enable_sms_notifications' => false,
        );

        add_option('chrono_forge_settings', $default_options);
    }
}
