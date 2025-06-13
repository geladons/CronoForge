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
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix . 'chrono_forge_';
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
     * Получить все записи
     *
     * @param array $args
     * @return array
     */
    public function get_all_appointments($args = array()) {
        $table = $this->table_prefix . 'appointments';
        $services_table = $this->table_prefix . 'services';
        $employees_table = $this->table_prefix . 'employees';
        $customers_table = $this->table_prefix . 'customers';

        $where_clauses = array('1=1');

        // Фильтр по дате
        if (!empty($args['date_from'])) {
            $where_clauses[] = $this->wpdb->prepare("a.appointment_date >= %s", $args['date_from']);
        }

        if (!empty($args['date_to'])) {
            $where_clauses[] = $this->wpdb->prepare("a.appointment_date <= %s", $args['date_to']);
        }

        // Фильтр по сотруднику
        if (!empty($args['employee_id'])) {
            $where_clauses[] = $this->wpdb->prepare("a.employee_id = %d", $args['employee_id']);
        }

        // Фильтр по услуге
        if (!empty($args['service_id'])) {
            $where_clauses[] = $this->wpdb->prepare("a.service_id = %d", $args['service_id']);
        }

        // Фильтр по статусу
        if (!empty($args['status'])) {
            $where_clauses[] = $this->wpdb->prepare("a.status = %s", $args['status']);
        }

        // Фильтр по клиенту
        if (!empty($args['customer_id'])) {
            $where_clauses[] = $this->wpdb->prepare("a.customer_id = %d", $args['customer_id']);
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

        $sql = "SELECT a.*,
                       s.name as service_name, s.duration as service_duration, s.color as service_color,
                       e.name as employee_name, e.color as employee_color,
                       CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.email as customer_email, c.phone as customer_phone
                FROM {$table} a
                LEFT JOIN {$services_table} s ON a.service_id = s.id
                LEFT JOIN {$employees_table} e ON a.employee_id = e.id
                LEFT JOIN {$customers_table} c ON a.customer_id = c.id
                {$where_sql}
                ORDER BY a.appointment_date DESC, a.appointment_time DESC";

        return $this->wpdb->get_results($sql);
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
     * Создать новую запись
     *
     * @param array $data
     * @return int|false
     */
    public function insert_appointment($data) {
        $table = $this->table_prefix . 'appointments';

        $defaults = array(
            'service_id' => 0,
            'employee_id' => 0,
            'customer_id' => 0,
            'appointment_date' => '',
            'appointment_time' => '',
            'end_time' => '',
            'status' => 'pending',
            'notes' => '',
            'internal_notes' => '',
            'total_price' => 0.00
        );

        $data = wp_parse_args($data, $defaults);

        $result = $this->wpdb->insert($table, $data);

        return $result ? $this->wpdb->insert_id : false;
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
}
