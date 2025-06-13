<?php
/**
 * Менеджер базы данных для плагина ChronoForge
 * 
 * Этот класс управляет всеми операциями с базой данных
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}

class ChronoForge_DB_Manager {

    /**
     * Глобальный объект WordPress для работы с БД
     * 
     * @var wpdb
     */
    private $wpdb;

    /**
     * Префикс таблиц
     * 
     * @var string
     */
    private $table_prefix;

    /**
     * Конструктор класса
     */
    public function __construct() {
        global $wpdb;

        // Check if wpdb is available
        if (!$wpdb) {
            throw new Exception('WordPress database object not available');
        }

        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix . 'chrono_forge_';

        // Test database connection
        if (!$this->test_database_connection()) {
            throw new Exception('Database connection test failed');
        }
    }

    /**
     * Test database connection
     *
     * @return bool
     */
    private function test_database_connection() {
        try {
            $result = $this->wpdb->get_var("SELECT 1");
            return $result === '1';
        } catch (Exception $e) {
            chrono_forge_safe_log("Database connection test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    // ========================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С КАТЕГОРИЯМИ
    // ========================================

    /**
     * Получить все категории
     * 
     * @return array
     */
    public function get_all_categories() {
        $table = $this->table_prefix . 'categories';
        return $this->wpdb->get_results(
            "SELECT * FROM {$table} WHERE status = 'active' ORDER BY sort_order ASC, name ASC"
        );
    }

    /**
     * Получить категорию по ID
     * 
     * @param int $id
     * @return object|null
     */
    public function get_category($id) {
        $table = $this->table_prefix . 'categories';
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)
        );
    }

    /**
     * Создать новую категорию
     * 
     * @param array $data
     * @return int|false ID новой категории или false при ошибке
     */
    public function insert_category($data) {
        $table = $this->table_prefix . 'categories';
        
        $defaults = array(
            'name' => '',
            'description' => '',
            'color' => '#34495e',
            'sort_order' => 0,
            'status' => 'active'
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $this->wpdb->insert($table, $data);
        
        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Обновить категорию
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_category($id, $data) {
        $table = $this->table_prefix . 'categories';
        
        return $this->wpdb->update(
            $table,
            $data,
            array('id' => $id)
        ) !== false;
    }

    /**
     * Удалить категорию
     * 
     * @param int $id
     * @return bool
     */
    public function delete_category($id) {
        $table = $this->table_prefix . 'categories';
        
        return $this->wpdb->delete(
            $table,
            array('id' => $id)
        ) !== false;
    }

    // ========================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С УСЛУГАМИ
    // ========================================

    /**
     * Получить все услуги
     * 
     * @param array $args Дополнительные параметры фильтрации
     * @return array
     */
    public function get_all_services($args = array()) {
        $table = $this->table_prefix . 'services';
        $categories_table = $this->table_prefix . 'categories';
        
        $where_clauses = array("s.status = 'active'");
        $join_clauses = array();
        
        // Фильтр по категории
        if (!empty($args['category_id'])) {
            $where_clauses[] = $this->wpdb->prepare("s.category_id = %d", $args['category_id']);
        }
        
        // Фильтр по сотруднику
        if (!empty($args['employee_id'])) {
            $employee_services_table = $this->table_prefix . 'employee_services';
            $join_clauses[] = "INNER JOIN {$employee_services_table} es ON s.id = es.service_id";
            $where_clauses[] = $this->wpdb->prepare("es.employee_id = %d", $args['employee_id']);
        }
        
        $join_sql = implode(' ', $join_clauses);
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        
        $sql = "SELECT s.*, c.name as category_name, c.color as category_color 
                FROM {$table} s 
                LEFT JOIN {$categories_table} c ON s.category_id = c.id 
                {$join_sql}
                {$where_sql}
                ORDER BY s.name ASC";
        
        return $this->wpdb->get_results($sql);
    }

    /**
     * Получить услугу по ID
     * 
     * @param int $id
     * @return object|null
     */
    public function get_service($id) {
        $table = $this->table_prefix . 'services';
        $categories_table = $this->table_prefix . 'categories';
        
        $sql = "SELECT s.*, c.name as category_name 
                FROM {$table} s 
                LEFT JOIN {$categories_table} c ON s.category_id = c.id 
                WHERE s.id = %d";
        
        return $this->wpdb->get_row(
            $this->wpdb->prepare($sql, $id)
        );
    }

    /**
     * Создать новую услугу
     * 
     * @param array $data
     * @return int|false
     */
    public function insert_service($data) {
        $table = $this->table_prefix . 'services';
        
        $defaults = array(
            'name' => '',
            'description' => '',
            'category_id' => null,
            'duration' => 60,
            'price' => 0.00,
            'buffer_time' => 0,
            'color' => '#3498db',
            'status' => 'active'
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $this->wpdb->insert($table, $data);
        
        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Обновить услугу
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_service($id, $data) {
        $table = $this->table_prefix . 'services';
        
        return $this->wpdb->update(
            $table,
            $data,
            array('id' => $id)
        ) !== false;
    }

    /**
     * Удалить услугу
     * 
     * @param int $id
     * @return bool
     */
    public function delete_service($id) {
        $table = $this->table_prefix . 'services';
        
        return $this->wpdb->update(
            $table,
            array('status' => 'inactive'),
            array('id' => $id)
        ) !== false;
    }

    // ========================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С СОТРУДНИКАМИ
    // ========================================

    /**
     * Получить всех сотрудников
     * 
     * @param array $args
     * @return array
     */
    public function get_all_employees($args = array()) {
        $table = $this->table_prefix . 'employees';
        
        $where_clauses = array("status = 'active'");
        
        // Фильтр по услуге
        if (!empty($args['service_id'])) {
            $employee_services_table = $this->table_prefix . 'employee_services';
            $sql = "SELECT e.* FROM {$table} e 
                    INNER JOIN {$employee_services_table} es ON e.id = es.employee_id 
                    WHERE e.status = 'active' AND es.service_id = %d 
                    ORDER BY e.name ASC";
            
            return $this->wpdb->get_results(
                $this->wpdb->prepare($sql, $args['service_id'])
            );
        }
        
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        
        return $this->wpdb->get_results(
            "SELECT * FROM {$table} {$where_sql} ORDER BY name ASC"
        );
    }

    /**
     * Получить сотрудника по ID
     * 
     * @param int $id
     * @return object|null
     */
    public function get_employee($id) {
        $table = $this->table_prefix . 'employees';
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)
        );
    }

    /**
     * Создать нового сотрудника
     * 
     * @param array $data
     * @return int|false
     */
    public function insert_employee($data) {
        $table = $this->table_prefix . 'employees';
        
        $defaults = array(
            'wp_user_id' => null,
            'name' => '',
            'email' => '',
            'phone' => '',
            'photo' => '',
            'description' => '',
            'color' => '#e74c3c',
            'status' => 'active'
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $this->wpdb->insert($table, $data);
        
        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Обновить сотрудника
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_employee($id, $data) {
        $table = $this->table_prefix . 'employees';
        
        return $this->wpdb->update(
            $table,
            $data,
            array('id' => $id)
        ) !== false;
    }

    /**
     * Удалить сотрудника
     * 
     * @param int $id
     * @return bool
     */
    public function delete_employee($id) {
        $table = $this->table_prefix . 'employees';
        
        return $this->wpdb->update(
            $table,
            array('status' => 'inactive'),
            array('id' => $id)
        ) !== false;
    }

    // ========================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ГРАФИКАМИ
    // ========================================

    /**
     * Получить график сотрудника
     * 
     * @param int $employee_id
     * @return array
     */
    public function get_employee_schedule($employee_id) {
        $table = $this->table_prefix . 'schedules';
        
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} WHERE employee_id = %d ORDER BY day_of_week ASC",
                $employee_id
            )
        );
    }

    /**
     * Сохранить график сотрудника
     * 
     * @param int $employee_id
     * @param array $schedule_data
     * @return bool
     */
    public function save_employee_schedule($employee_id, $schedule_data) {
        $table = $this->table_prefix . 'schedules';
        
        // Удаляем старый график
        $this->wpdb->delete($table, array('employee_id' => $employee_id));
        
        // Добавляем новый график
        foreach ($schedule_data as $day => $data) {
            if (!empty($data['is_working'])) {
                $this->wpdb->insert($table, array(
                    'employee_id' => $employee_id,
                    'day_of_week' => $day,
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'break_start' => !empty($data['break_start']) ? $data['break_start'] : null,
                    'break_end' => !empty($data['break_end']) ? $data['break_end'] : null,
                    'is_working' => 1
                ));
            }
        }
        
        return true;
    }

    // ========================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ЗАПИСЯМИ
    // ========================================

    /**
     * Получить все записи с улучшенной безопасностью и производительностью
     *
     * @since 1.0.0
     * @param array $args Параметры фильтрации
     * @return array|false Массив записей или false при ошибке
     */
    public function get_all_appointments($args = array()) {
        try {
            $cache_key = 'chrono_forge_appointments_' . md5(serialize($args));
            $cached_result = wp_cache_get($cache_key, 'chrono_forge');

            if ($cached_result !== false) {
                return $cached_result;
            }

            $table = $this->table_prefix . 'appointments';
            $services_table = $this->table_prefix . 'services';
            $employees_table = $this->table_prefix . 'employees';
            $customers_table = $this->table_prefix . 'customers';

            $where_clauses = array('1=1');
            $prepare_values = array();

            // Фильтр по дате с валидацией
            if (!empty($args['date_from'])) {
                if ($this->is_valid_date($args['date_from'])) {
                    $where_clauses[] = "a.appointment_date >= %s";
                    $prepare_values[] = $args['date_from'];
                }
            }

            if (!empty($args['date_to'])) {
                if ($this->is_valid_date($args['date_to'])) {
                    $where_clauses[] = "a.appointment_date <= %s";
                    $prepare_values[] = $args['date_to'];
                }
            }

            // Фильтр по сотруднику с валидацией
            if (!empty($args['employee_id'])) {
                $employee_id = intval($args['employee_id']);
                if ($employee_id > 0) {
                    $where_clauses[] = "a.employee_id = %d";
                    $prepare_values[] = $employee_id;
                }
            }

            // Фильтр по услуге с валидацией
            if (!empty($args['service_id'])) {
                $service_id = intval($args['service_id']);
                if ($service_id > 0) {
                    $where_clauses[] = "a.service_id = %d";
                    $prepare_values[] = $service_id;
                }
            }

            // Фильтр по статусу с валидацией
            if (!empty($args['status'])) {
                $status = sanitize_text_field($args['status']);
                $valid_statuses = array_keys(chrono_forge_get_appointment_statuses());
                if (in_array($status, $valid_statuses)) {
                    $where_clauses[] = "a.status = %s";
                    $prepare_values[] = $status;
                }
            }

            // Фильтр по клиенту с валидацией
            if (!empty($args['customer_id'])) {
                $customer_id = intval($args['customer_id']);
                if ($customer_id > 0) {
                    $where_clauses[] = "a.customer_id = %d";
                    $prepare_values[] = $customer_id;
                }
            }

            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

            // Добавляем LIMIT для производительности
            $limit = isset($args['limit']) ? intval($args['limit']) : 100;
            $limit = min($limit, 1000); // Максимум 1000 записей
            $offset = isset($args['offset']) ? intval($args['offset']) : 0;

            $sql = "SELECT a.id, a.service_id, a.employee_id, a.customer_id,
                           a.appointment_date, a.appointment_time, a.end_time,
                           a.status, a.notes, a.total_price,
                           s.name as service_name, s.duration as service_duration, s.color as service_color,
                           e.name as employee_name, e.color as employee_color,
                           c.first_name as customer_first_name, c.last_name as customer_last_name,
                           c.email as customer_email, c.phone as customer_phone
                    FROM {$table} a
                    LEFT JOIN {$services_table} s ON a.service_id = s.id
                    LEFT JOIN {$employees_table} e ON a.employee_id = e.id
                    LEFT JOIN {$customers_table} c ON a.customer_id = c.id
                    {$where_sql}
                    ORDER BY a.appointment_date DESC, a.appointment_time DESC
                    LIMIT %d OFFSET %d";

            $prepare_values[] = $limit;
            $prepare_values[] = $offset;

            $prepared_sql = $this->wpdb->prepare($sql, $prepare_values);
            $results = $this->wpdb->get_results($prepared_sql);

            if ($this->wpdb->last_error) {
                chrono_forge_log("Database error in get_all_appointments: " . $this->wpdb->last_error, 'error');
                return false;
            }

            // Кэшируем результат на 5 минут
            wp_cache_set($cache_key, $results, 'chrono_forge', 300);

            return $results;

        } catch (Exception $e) {
            chrono_forge_log("Exception in get_all_appointments: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Получить запись по ID
     *
     * @param int $id
     * @return object|null
     */
    public function get_appointment($id) {
        $table = $this->table_prefix . 'appointments';
        $services_table = $this->table_prefix . 'services';
        $employees_table = $this->table_prefix . 'employees';
        $customers_table = $this->table_prefix . 'customers';

        $sql = "SELECT a.*,
                       s.name as service_name, s.duration as service_duration, s.price as service_price,
                       e.name as employee_name, e.email as employee_email,
                       CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.email as customer_email, c.phone as customer_phone
                FROM {$table} a
                LEFT JOIN {$services_table} s ON a.service_id = s.id
                LEFT JOIN {$employees_table} e ON a.employee_id = e.id
                LEFT JOIN {$customers_table} c ON a.customer_id = c.id
                WHERE a.id = %d";

        return $this->wpdb->get_row(
            $this->wpdb->prepare($sql, $id)
        );
    }

    /**
     * Создать новую запись с улучшенной валидацией
     *
     * @since 1.0.0
     * @param array $data Данные записи
     * @return int|false ID новой записи или false при ошибке
     */
    public function insert_appointment($data) {
        try {
            $table = $this->table_prefix . 'appointments';

            // Валидация обязательных полей
            $required_fields = array('service_id', 'employee_id', 'customer_id', 'appointment_date', 'appointment_time');
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    chrono_forge_log("Missing required field in insert_appointment: {$field}", 'error');
                    return false;
                }
            }

            // Валидация и санитизация данных
            $sanitized_data = array(
                'service_id' => intval($data['service_id']),
                'employee_id' => intval($data['employee_id']),
                'customer_id' => intval($data['customer_id']),
                'appointment_date' => sanitize_text_field($data['appointment_date']),
                'appointment_time' => sanitize_text_field($data['appointment_time']),
                'end_time' => isset($data['end_time']) ? sanitize_text_field($data['end_time']) : '',
                'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'pending',
                'notes' => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '',
                'internal_notes' => isset($data['internal_notes']) ? sanitize_textarea_field($data['internal_notes']) : '',
                'total_price' => isset($data['total_price']) ? floatval($data['total_price']) : 0.00,
                'created_at' => current_time('mysql')
            );

            // Дополнительная валидация
            if (!$this->is_valid_date($sanitized_data['appointment_date'])) {
                chrono_forge_log("Invalid appointment date: " . $sanitized_data['appointment_date'], 'error');
                return false;
            }

            // Проверка существования связанных записей
            if (!$this->get_service($sanitized_data['service_id'])) {
                chrono_forge_log("Service not found: " . $sanitized_data['service_id'], 'error');
                return false;
            }

            if (!$this->get_employee($sanitized_data['employee_id'])) {
                chrono_forge_log("Employee not found: " . $sanitized_data['employee_id'], 'error');
                return false;
            }

            if (!$this->get_customer($sanitized_data['customer_id'])) {
                chrono_forge_log("Customer not found: " . $sanitized_data['customer_id'], 'error');
                return false;
            }

            // Проверка доступности слота
            if (!$this->is_slot_available(
                $sanitized_data['employee_id'],
                $sanitized_data['appointment_date'],
                $sanitized_data['appointment_time'],
                $sanitized_data['end_time']
            )) {
                chrono_forge_log("Time slot not available", 'warning');
                return false;
            }

            $result = $this->wpdb->insert($table, $sanitized_data);

            if ($result === false) {
                chrono_forge_log("Database error in insert_appointment: " . $this->wpdb->last_error, 'error');
                return false;
            }

            $appointment_id = $this->wpdb->insert_id;

            // Очищаем кэш
            $this->clear_appointments_cache();

            chrono_forge_log("Successfully created appointment with ID: {$appointment_id}", 'info');

            return $appointment_id;

        } catch (Exception $e) {
            chrono_forge_log("Exception in insert_appointment: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Обновить запись
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_appointment($id, $data) {
        $table = $this->table_prefix . 'appointments';

        return $this->wpdb->update(
            $table,
            $data,
            array('id' => $id)
        ) !== false;
    }

    /**
     * Обновить статус записи
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function update_appointment_status($id, $status) {
        return $this->update_appointment($id, array('status' => $status));
    }

    /**
     * Удалить запись
     *
     * @param int $id
     * @return bool
     */
    public function delete_appointment($id) {
        $table = $this->table_prefix . 'appointments';

        return $this->wpdb->delete(
            $table,
            array('id' => $id)
        ) !== false;
    }

    /**
     * Проверить доступность временного слота
     *
     * @param int $employee_id
     * @param string $date
     * @param string $start_time
     * @param string $end_time
     * @param int $exclude_appointment_id
     * @return bool
     */
    public function is_slot_available($employee_id, $date, $start_time, $end_time, $exclude_appointment_id = 0) {
        $table = $this->table_prefix . 'appointments';

        $sql = "SELECT COUNT(*) FROM {$table}
                WHERE employee_id = %d
                AND appointment_date = %s
                AND status NOT IN ('cancelled', 'no_show')
                AND (
                    (appointment_time < %s AND end_time > %s) OR
                    (appointment_time < %s AND end_time > %s) OR
                    (appointment_time >= %s AND end_time <= %s)
                )";

        $params = array($employee_id, $date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);

        if ($exclude_appointment_id > 0) {
            $sql .= " AND id != %d";
            $params[] = $exclude_appointment_id;
        }

        $count = $this->wpdb->get_var(
            $this->wpdb->prepare($sql, $params)
        );

        return $count == 0;
    }

    // ========================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С КЛИЕНТАМИ
    // ========================================

    /**
     * Получить всех клиентов
     *
     * @param array $args
     * @return array
     */
    public function get_all_customers($args = array()) {
        $table = $this->table_prefix . 'customers';

        $where_clauses = array('1=1');

        // Поиск по имени или email
        if (!empty($args['search'])) {
            $search = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $where_clauses[] = $this->wpdb->prepare(
                "(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)",
                $search, $search, $search
            );
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

        return $this->wpdb->get_results(
            "SELECT * FROM {$table} {$where_sql} ORDER BY first_name ASC, last_name ASC"
        );
    }

    /**
     * Получить клиента по ID
     *
     * @param int $id
     * @return object|null
     */
    public function get_customer($id) {
        $table = $this->table_prefix . 'customers';
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)
        );
    }

    /**
     * Получить клиента по email
     *
     * @param string $email
     * @return object|null
     */
    public function get_customer_by_email($email) {
        $table = $this->table_prefix . 'customers';
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$table} WHERE email = %s", $email)
        );
    }

    /**
     * Создать нового клиента
     *
     * @param array $data
     * @return int|false
     */
    public function insert_customer($data) {
        $table = $this->table_prefix . 'customers';

        $defaults = array(
            'wp_user_id' => null,
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
            'date_of_birth' => null,
            'notes' => ''
        );

        $data = wp_parse_args($data, $defaults);

        $result = $this->wpdb->insert($table, $data);

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Обновить клиента
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_customer($id, $data) {
        $table = $this->table_prefix . 'customers';

        return $this->wpdb->update(
            $table,
            $data,
            array('id' => $id)
        ) !== false;
    }

    /**
     * Удалить клиента
     *
     * @param int $id
     * @return bool
     */
    public function delete_customer($id) {
        $table = $this->table_prefix . 'customers';

        return $this->wpdb->delete(
            $table,
            array('id' => $id)
        ) !== false;
    }

    // ========================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ПЛАТЕЖАМИ
    // ========================================

    /**
     * Получить все платежи
     *
     * @param array $args
     * @return array
     */
    public function get_all_payments($args = array()) {
        $table = $this->table_prefix . 'payments';
        $appointments_table = $this->table_prefix . 'appointments';
        $customers_table = $this->table_prefix . 'customers';

        $where_clauses = array('1=1');

        // Фильтр по записи
        if (!empty($args['appointment_id'])) {
            $where_clauses[] = $this->wpdb->prepare("p.appointment_id = %d", $args['appointment_id']);
        }

        // Фильтр по статусу
        if (!empty($args['status'])) {
            $where_clauses[] = $this->wpdb->prepare("p.status = %s", $args['status']);
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

        $sql = "SELECT p.*,
                       CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                       a.appointment_date, a.appointment_time
                FROM {$table} p
                LEFT JOIN {$appointments_table} a ON p.appointment_id = a.id
                LEFT JOIN {$customers_table} c ON a.customer_id = c.id
                {$where_sql}
                ORDER BY p.created_at DESC";

        return $this->wpdb->get_results($sql);
    }

    /**
     * Получить платеж по ID
     *
     * @param int $id
     * @return object|null
     */
    public function get_payment($id) {
        $table = $this->table_prefix . 'payments';
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)
        );
    }

    /**
     * Создать новый платеж
     *
     * @param array $data
     * @return int|false
     */
    public function insert_payment($data) {
        $table = $this->table_prefix . 'payments';

        $defaults = array(
            'appointment_id' => 0,
            'amount' => 0.00,
            'currency' => 'USD',
            'payment_method' => 'cash',
            'transaction_id' => '',
            'status' => 'pending',
            'gateway_response' => ''
        );

        $data = wp_parse_args($data, $defaults);

        $result = $this->wpdb->insert($table, $data);

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Обновить платеж
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_payment($id, $data) {
        $table = $this->table_prefix . 'payments';

        return $this->wpdb->update(
            $table,
            $data,
            array('id' => $id)
        ) !== false;
    }

    /**
     * Обновить статус платежа
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function update_payment_status($id, $status) {
        return $this->update_payment($id, array('status' => $status));
    }

    // ========================================
    // МЕТОДЫ ДЛЯ РАБОТЫ СО СВЯЗЯМИ СОТРУДНИК-УСЛУГА
    // ========================================

    /**
     * Получить услуги сотрудника
     *
     * @param int $employee_id
     * @return array
     */
    public function get_employee_services($employee_id) {
        $table = $this->table_prefix . 'employee_services';
        $services_table = $this->table_prefix . 'services';

        $sql = "SELECT s.*, es.custom_price
                FROM {$services_table} s
                INNER JOIN {$table} es ON s.id = es.service_id
                WHERE es.employee_id = %d AND s.status = 'active'
                ORDER BY s.name ASC";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $employee_id)
        );
    }

    /**
     * Назначить услуги сотруднику
     *
     * @param int $employee_id
     * @param array $service_ids
     * @return bool
     */
    public function assign_services_to_employee($employee_id, $service_ids) {
        $table = $this->table_prefix . 'employee_services';

        // Удаляем старые связи
        $this->wpdb->delete($table, array('employee_id' => $employee_id));

        // Добавляем новые связи
        foreach ($service_ids as $service_id) {
            $this->wpdb->insert($table, array(
                'employee_id' => $employee_id,
                'service_id' => $service_id
            ));
        }

        return true;
    }

    // ========================================
    // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // ========================================

    /**
     * Получить статистику для дашборда
     *
     * @return array
     */
    public function get_dashboard_stats() {
        $appointments_table = $this->table_prefix . 'appointments';
        $payments_table = $this->table_prefix . 'payments';
        $customers_table = $this->table_prefix . 'customers';

        $current_month = date('Y-m-01');
        $next_month = date('Y-m-01', strtotime('+1 month'));

        // Доход за текущий месяц
        $monthly_revenue = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT SUM(amount) FROM {$payments_table}
                 WHERE status = 'completed'
                 AND created_at >= %s
                 AND created_at < %s",
                $current_month, $next_month
            )
        );

        // Количество записей за текущий месяц
        $monthly_appointments = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$appointments_table}
                 WHERE appointment_date >= %s
                 AND appointment_date < %s",
                $current_month, $next_month
            )
        );

        // Общее количество клиентов
        $total_customers = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$customers_table}"
        );

        // Записи на сегодня
        $today_appointments = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$appointments_table}
                 WHERE appointment_date = %s
                 AND status NOT IN ('cancelled', 'no_show')",
                date('Y-m-d')
            )
        );

        return array(
            'monthly_revenue' => floatval($monthly_revenue),
            'monthly_appointments' => intval($monthly_appointments),
            'total_customers' => intval($total_customers),
            'today_appointments' => intval($today_appointments)
        );
    }

    /**
     * Получить сотрудников, которые могут выполнять определенную услугу
     *
     * @param int $service_id
     * @return array
     */
    public function get_employees_by_service($service_id) {
        $employees_table = $this->table_prefix . 'employees';
        $employee_services_table = $this->table_prefix . 'employee_services';

        $sql = $this->wpdb->prepare(
            "SELECT e.* FROM {$employees_table} e
             INNER JOIN {$employee_services_table} es ON e.id = es.employee_id
             WHERE es.service_id = %d AND e.status = 'active'
             ORDER BY e.name ASC",
            $service_id
        );

        return $this->wpdb->get_results($sql);
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
     * Clear appointments cache
     *
     * @since 1.0.0
     * @return void
     */
    private function clear_appointments_cache() {
        wp_cache_delete_group('chrono_forge');
    }

    /**
     * Clear all plugin caches
     *
     * @since 1.0.0
     * @return void
     */
    public function clear_all_cache() {
        wp_cache_delete_group('chrono_forge');
        delete_transient('chrono_forge_services_cache');
        delete_transient('chrono_forge_employees_cache');
        delete_transient('chrono_forge_categories_cache');
    }

    /**
     * Get cached services or fetch from database
     *
     * @since 1.0.0
     * @param array $args Query arguments
     * @return array Services data
     */
    public function get_cached_services($args = array()) {
        $cache_key = 'chrono_forge_services_' . md5(serialize($args));
        $cached_result = get_transient($cache_key);

        if ($cached_result !== false) {
            return $cached_result;
        }

        $services = $this->get_all_services($args);
        set_transient($cache_key, $services, 300); // 5 minutes cache

        return $services;
    }

    /**
     * Get cached employees or fetch from database
     *
     * @since 1.0.0
     * @param array $args Query arguments
     * @return array Employees data
     */
    public function get_cached_employees($args = array()) {
        $cache_key = 'chrono_forge_employees_' . md5(serialize($args));
        $cached_result = get_transient($cache_key);

        if ($cached_result !== false) {
            return $cached_result;
        }

        $employees = $this->get_all_employees($args);
        set_transient($cache_key, $employees, 300); // 5 minutes cache

        return $employees;
    }

    /**
     * Sanitize and validate appointment data
     *
     * @since 1.0.0
     * @param array $data Raw appointment data
     * @return array|false Sanitized data or false if invalid
     */
    private function sanitize_appointment_data($data) {
        if (!is_array($data)) {
            return false;
        }

        $sanitized = array();

        // Required fields validation
        $required_fields = array('service_id', 'employee_id', 'customer_id', 'appointment_date', 'appointment_time');
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                chrono_forge_log("Missing required field: {$field}", 'error');
                return false;
            }
        }

        // Sanitize each field
        $sanitized['service_id'] = intval($data['service_id']);
        $sanitized['employee_id'] = intval($data['employee_id']);
        $sanitized['customer_id'] = intval($data['customer_id']);
        $sanitized['appointment_date'] = sanitize_text_field($data['appointment_date']);
        $sanitized['appointment_time'] = sanitize_text_field($data['appointment_time']);
        $sanitized['end_time'] = isset($data['end_time']) ? sanitize_text_field($data['end_time']) : '';
        $sanitized['status'] = isset($data['status']) ? sanitize_text_field($data['status']) : 'pending';
        $sanitized['notes'] = isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '';
        $sanitized['internal_notes'] = isset($data['internal_notes']) ? sanitize_textarea_field($data['internal_notes']) : '';
        $sanitized['total_price'] = isset($data['total_price']) ? floatval($data['total_price']) : 0.00;

        // Validate IDs are positive integers
        if ($sanitized['service_id'] <= 0 || $sanitized['employee_id'] <= 0 || $sanitized['customer_id'] <= 0) {
            chrono_forge_log("Invalid ID values in appointment data", 'error');
            return false;
        }

        // Validate date format
        if (!$this->is_valid_date($sanitized['appointment_date'])) {
            chrono_forge_log("Invalid date format: " . $sanitized['appointment_date'], 'error');
            return false;
        }

        // Validate status
        $valid_statuses = array_keys(chrono_forge_get_appointment_statuses());
        if (!in_array($sanitized['status'], $valid_statuses)) {
            $sanitized['status'] = 'pending';
        }

        return $sanitized;
    }
}
