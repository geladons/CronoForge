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

        // Включаем отображение ошибок для отладки
        if (defined('WP_DEBUG') && WP_DEBUG) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        }

        chrono_forge_safe_log("Starting plugin activation", 'info');

        // Проверка минимальных требований
        if (!self::check_requirements()) {
            chrono_forge_safe_log("Requirements check failed", 'error');
            return;
        }

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

        // Выполнение SQL-запросов для создания таблиц с улучшенной обработкой ошибок
        try {
            $tables_created = array();

            $tables = array(
                'categories' => $categories_sql,
                'services' => $services_sql,
                'employees' => $employees_sql,
                'schedules' => $schedules_sql,
                'appointments' => $appointments_sql,
                'customers' => $customers_sql,
                'payments' => $payments_sql,
                'employee_services' => $employee_services_sql
            );

            foreach ($tables as $table_name => $sql) {
                $result = dbDelta($sql);
                if (!empty($wpdb->last_error)) {
                    chrono_forge_safe_log("Error creating table {$table_name}: " . $wpdb->last_error, 'error');
                } else {
                    $tables_created[] = $table_name;
                    chrono_forge_safe_log("Successfully created/updated table {$table_name}", 'info');
                }
            }

            // Создание дополнительных индексов для производительности
            self::create_performance_indexes();

            // Сохранение версии плагина в опциях WordPress
            add_option('chrono_forge_version', CHRONO_FORGE_VERSION);
            add_option('chrono_forge_tables_created', $tables_created);
            add_option('chrono_forge_activation_date', current_time('mysql'));

            // Создание базовых настроек плагина
            self::create_default_options();

            // Создание базовых данных
            self::create_sample_data();

            chrono_forge_safe_log("Plugin activation completed successfully", 'info');

        } catch (Exception $e) {
            chrono_forge_safe_log("Activation error: " . $e->getMessage(), 'error');
            // Не прерываем активацию, но логируем ошибку
        }
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

    /**
     * Создание дополнительных индексов для производительности
     *
     * @since 1.0.0
     * @return void
     */
    private static function create_performance_indexes() {
        global $wpdb;

        $table_prefix = $wpdb->prefix . 'chrono_forge_';

        // Индексы для таблицы записей (appointments)
        $indexes = array(
            "CREATE INDEX IF NOT EXISTS idx_appointment_date ON {$table_prefix}appointments (appointment_date)",
            "CREATE INDEX IF NOT EXISTS idx_appointment_employee_date ON {$table_prefix}appointments (employee_id, appointment_date)",
            "CREATE INDEX IF NOT EXISTS idx_appointment_status ON {$table_prefix}appointments (status)",
            "CREATE INDEX IF NOT EXISTS idx_appointment_customer ON {$table_prefix}appointments (customer_id)",
            "CREATE INDEX IF NOT EXISTS idx_appointment_service ON {$table_prefix}appointments (service_id)",

            // Индексы для таблицы клиентов
            "CREATE INDEX IF NOT EXISTS idx_customer_email ON {$table_prefix}customers (email)",
            "CREATE INDEX IF NOT EXISTS idx_customer_phone ON {$table_prefix}customers (phone)",
            "CREATE INDEX IF NOT EXISTS idx_customer_name ON {$table_prefix}customers (first_name, last_name)",

            // Индексы для таблицы платежей
            "CREATE INDEX IF NOT EXISTS idx_payment_appointment ON {$table_prefix}payments (appointment_id)",
            "CREATE INDEX IF NOT EXISTS idx_payment_status ON {$table_prefix}payments (status)",
            "CREATE INDEX IF NOT EXISTS idx_payment_created ON {$table_prefix}payments (created_at)",

            // Индексы для таблицы услуг
            "CREATE INDEX IF NOT EXISTS idx_service_category ON {$table_prefix}services (category_id)",
            "CREATE INDEX IF NOT EXISTS idx_service_status ON {$table_prefix}services (status)",

            // Индексы для таблицы сотрудников
            "CREATE INDEX IF NOT EXISTS idx_employee_status ON {$table_prefix}employees (status)",
            "CREATE INDEX IF NOT EXISTS idx_employee_wp_user ON {$table_prefix}employees (wp_user_id)",

            // Индексы для таблицы графиков
            "CREATE INDEX IF NOT EXISTS idx_schedule_employee_day ON {$table_prefix}schedules (employee_id, day_of_week)",
        );

        foreach ($indexes as $index_sql) {
            $wpdb->query($index_sql);
            if (!empty($wpdb->last_error)) {
                chrono_forge_safe_log("Error creating index: " . $wpdb->last_error, 'error');
            }
        }
    }

    /**
     * Создание примерных данных для демонстрации
     *
     * @since 1.0.0
     * @return void
     */
    private static function create_sample_data() {
        global $wpdb;

        $table_prefix = $wpdb->prefix . 'chrono_forge_';

        // Проверяем, есть ли уже данные
        $existing_categories = $wpdb->get_var("SELECT COUNT(*) FROM {$table_prefix}categories");
        if ($existing_categories > 0) {
            return; // Данные уже есть
        }

        try {
            // Создаем примерные категории
            $categories = array(
                array('name' => __('Красота и здоровье', 'chrono-forge'), 'description' => __('Услуги красоты и здоровья', 'chrono-forge'), 'color' => '#e74c3c'),
                array('name' => __('Консультации', 'chrono-forge'), 'description' => __('Консультационные услуги', 'chrono-forge'), 'color' => '#3498db'),
                array('name' => __('Обучение', 'chrono-forge'), 'description' => __('Образовательные услуги', 'chrono-forge'), 'color' => '#2ecc71'),
            );

            foreach ($categories as $category) {
                $wpdb->insert($table_prefix . 'categories', $category);
            }

            // Создаем примерные услуги
            $services = array(
                array(
                    'name' => __('Стрижка', 'chrono-forge'),
                    'description' => __('Профессиональная стрижка волос', 'chrono-forge'),
                    'category_id' => 1,
                    'duration' => 60,
                    'price' => 50.00,
                    'color' => '#e74c3c'
                ),
                array(
                    'name' => __('Консультация специалиста', 'chrono-forge'),
                    'description' => __('Индивидуальная консультация', 'chrono-forge'),
                    'category_id' => 2,
                    'duration' => 30,
                    'price' => 100.00,
                    'color' => '#3498db'
                ),
            );

            foreach ($services as $service) {
                $wpdb->insert($table_prefix . 'services', $service);
            }

            chrono_forge_safe_log("Sample data created successfully", 'info');

        } catch (Exception $e) {
            chrono_forge_safe_log("Error creating sample data: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Проверка минимальных требований системы
     *
     * @since 1.0.0
     * @return bool True если все требования выполнены
     */
    private static function check_requirements() {
        // Проверка версии PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            chrono_forge_safe_log("PHP version " . PHP_VERSION . " is too old. Required: 7.4+", 'error');
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo sprintf(__('ChronoForge требует PHP версии 7.4 или выше. Ваша версия: %s', 'chrono-forge'), PHP_VERSION);
                echo '</p></div>';
            });
            return false;
        }

        // Проверка версии WordPress
        global $wp_version;
        if (version_compare($wp_version, '5.0', '<')) {
            chrono_forge_safe_log("WordPress version " . $wp_version . " is too old. Required: 5.0+", 'error');
            add_action('admin_notices', function() use ($wp_version) {
                echo '<div class="notice notice-error"><p>';
                echo sprintf(__('ChronoForge требует WordPress версии 5.0 или выше. Ваша версия: %s', 'chrono-forge'), $wp_version);
                echo '</p></div>';
            });
            return false;
        }

        // Проверка доступности MySQL
        global $wpdb;
        if (!$wpdb || !$wpdb->db_connect()) {
            chrono_forge_safe_log("Database connection failed", 'error');
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo __('ChronoForge: Не удается подключиться к базе данных', 'chrono-forge');
                echo '</p></div>';
            });
            return false;
        }

        // Проверка прав на создание таблиц
        $test_table = $wpdb->prefix . 'chrono_forge_test_' . time();
        $result = $wpdb->query("CREATE TABLE {$test_table} (id INT AUTO_INCREMENT PRIMARY KEY)");
        if ($result === false) {
            chrono_forge_safe_log("Cannot create database tables. Check permissions.", 'error');
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo __('ChronoForge: Недостаточно прав для создания таблиц в базе данных', 'chrono-forge');
                echo '</p></div>';
            });
            return false;
        } else {
            // Удаляем тестовую таблицу
            $wpdb->query("DROP TABLE IF EXISTS {$test_table}");
        }

        chrono_forge_safe_log("All system requirements met", 'info');
        return true;
    }
}
