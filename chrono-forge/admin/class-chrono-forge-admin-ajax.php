<?php
/**
 * ChronoForge Admin AJAX Handler Class
 *
 * Handles all AJAX requests from the admin interface
 *
 * @package ChronoForge
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ChronoForge Admin AJAX Handler Class
 *
 * @since 1.0.0
 */
class ChronoForge_Admin_Ajax {

    /**
     * Singleton instance
     *
     * @since 1.0.0
     * @var ChronoForge_Admin_Ajax
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @since 1.0.0
     * @return ChronoForge_Admin_Ajax
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function init_hooks() {
        // Admin AJAX actions
        add_action('wp_ajax_chrono_forge_get_appointments', array($this, 'get_appointments'));
        add_action('wp_ajax_chrono_forge_save_appointment', array($this, 'save_appointment'));
        add_action('wp_ajax_chrono_forge_delete_appointment', array($this, 'delete_appointment'));
        add_action('wp_ajax_chrono_forge_get_services', array($this, 'get_services'));
        add_action('wp_ajax_chrono_forge_save_service', array($this, 'save_service'));
        add_action('wp_ajax_chrono_forge_delete_service', array($this, 'delete_service'));
        add_action('wp_ajax_chrono_forge_get_employees', array($this, 'get_employees'));
        add_action('wp_ajax_chrono_forge_save_employee', array($this, 'save_employee'));
        add_action('wp_ajax_chrono_forge_delete_employee', array($this, 'delete_employee'));
        add_action('wp_ajax_chrono_forge_get_customers', array($this, 'get_customers'));
        add_action('wp_ajax_chrono_forge_save_customer', array($this, 'save_customer'));
        add_action('wp_ajax_chrono_forge_delete_customer', array($this, 'delete_customer'));
        add_action('wp_ajax_chrono_forge_get_calendar_data', array($this, 'get_calendar_data'));
        add_action('wp_ajax_chrono_forge_update_appointment_status', array($this, 'update_appointment_status'));
        add_action('wp_ajax_chrono_forge_disable_emergency_mode', array($this, 'disable_emergency_mode'));
        add_action('wp_ajax_chrono_forge_run_diagnostics', array($this, 'run_diagnostics'));
    }

    /**
     * Verify nonce and user capabilities
     *
     * @since 1.0.0
     * @param string $action Nonce action
     * @return bool
     */
    private function verify_request($action = 'chrono_forge_admin') {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Недостаточно прав доступа.', 'chrono-forge'));
            return false;
        }

        if (!wp_verify_nonce($_POST['nonce'], $action)) {
            wp_send_json_error(__('Неверный токен безопасности.', 'chrono-forge'));
            return false;
        }

        return true;
    }

    /**
     * Get appointments via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function get_appointments() {
        if (!$this->verify_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $appointments_table = $database->get_table_name('appointments');
            $customers_table = $database->get_table_name('customers');
            $employees_table = $database->get_table_name('employees');
            $services_table = $database->get_table_name('services');

            $sql = "SELECT " .
                        "a.*, " .
                        "CONCAT(c.first_name, ' ', c.last_name) as customer_name, " .
                        "c.email as customer_email, " .
                        "c.phone as customer_phone, " .
                        "e.name as employee_name, " .
                        "s.name as service_name, " .
                        "s.duration as service_duration, " .
                        "s.price as service_price " .
                    "FROM {$appointments_table} a " .
                    "LEFT JOIN {$customers_table} c ON a.customer_id = c.id " .
                    "LEFT JOIN {$employees_table} e ON a.employee_id = e.id " .
                    "LEFT JOIN {$services_table} s ON a.service_id = s.id " .
                    "ORDER BY a.appointment_date DESC, a.appointment_time DESC " .
                    "LIMIT 100";

            $appointments = $wpdb->get_results($sql);

            if ($wpdb->last_error) {
                wp_send_json_error(__('Ошибка базы данных: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            wp_send_json_success($appointments);

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при получении записей: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Save appointment via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function save_appointment() {
        if (!$this->verify_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('appointments');

            $appointment_data = array(
                'customer_id' => intval($_POST['customer_id']),
                'employee_id' => intval($_POST['employee_id']),
                'service_id' => intval($_POST['service_id']),
                'appointment_date' => sanitize_text_field($_POST['appointment_date']),
                'appointment_time' => sanitize_text_field($_POST['appointment_time']),
                'duration' => intval($_POST['duration']),
                'status' => sanitize_text_field($_POST['status']),
                'notes' => sanitize_textarea_field($_POST['notes']),
                'total_price' => floatval($_POST['total_price'])
            );

            $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;

            if ($appointment_id > 0) {
                // Update existing appointment
                $result = $wpdb->update($table, $appointment_data, array('id' => $appointment_id));
                $message = __('Запись успешно обновлена.', 'chrono-forge');
            } else {
                // Create new appointment
                $result = $wpdb->insert($table, $appointment_data);
                $appointment_id = $wpdb->insert_id;
                $message = __('Запись успешно создана.', 'chrono-forge');
            }

            if ($result === false) {
                wp_send_json_error(__('Ошибка при сохранении записи: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            wp_send_json_success(array(
                'message' => $message,
                'appointment_id' => $appointment_id
            ));

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при сохранении записи: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Delete appointment via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function delete_appointment() {
        if (!$this->verify_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('appointments');

            $appointment_id = intval($_POST['appointment_id']);

            if ($appointment_id <= 0) {
                wp_send_json_error(__('Неверный ID записи.', 'chrono-forge'));
                return;
            }

            $result = $wpdb->delete($table, array('id' => $appointment_id));

            if ($result === false) {
                wp_send_json_error(__('Ошибка при удалении записи: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            wp_send_json_success(__('Запись успешно удалена.', 'chrono-forge'));

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при удалении записи: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Get services via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function get_services() {
        if (!$this->verify_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('services');

            $services = $wpdb->get_results("SELECT * FROM {$table} ORDER BY name ASC");

            if ($wpdb->last_error) {
                wp_send_json_error(__('Ошибка базы данных: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            wp_send_json_success($services);

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при получении услуг: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Save service via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function save_service() {
        if (!$this->verify_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('services');

            $service_data = array(
                'name' => sanitize_text_field($_POST['name']),
                'description' => sanitize_textarea_field($_POST['description']),
                'duration' => intval($_POST['duration']),
                'price' => floatval($_POST['price']),
                'category' => sanitize_text_field($_POST['category']),
                'status' => sanitize_text_field($_POST['status'])
            );

            $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;

            if ($service_id > 0) {
                // Update existing service
                $result = $wpdb->update($table, $service_data, array('id' => $service_id));
                $message = __('Услуга успешно обновлена.', 'chrono-forge');
            } else {
                // Create new service
                $result = $wpdb->insert($table, $service_data);
                $service_id = $wpdb->insert_id;
                $message = __('Услуга успешно создана.', 'chrono-forge');
            }

            if ($result === false) {
                wp_send_json_error(__('Ошибка при сохранении услуги: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            wp_send_json_success(array(
                'message' => $message,
                'service_id' => $service_id
            ));

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при сохранении услуги: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Delete service via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function delete_service() {
        if (!$this->verify_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('services');

            $service_id = intval($_POST['service_id']);

            if ($service_id <= 0) {
                wp_send_json_error(__('Неверный ID услуги.', 'chrono-forge'));
                return;
            }

            $result = $wpdb->delete($table, array('id' => $service_id));

            if ($result === false) {
                wp_send_json_error(__('Ошибка при удалении услуги: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            wp_send_json_success(__('Услуга успешно удалена.', 'chrono-forge'));

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при удалении услуги: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Get employees via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function get_employees() {
        if (!$this->verify_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('employees');

            $employees = $wpdb->get_results("SELECT * FROM {$table} ORDER BY name ASC");

            if ($wpdb->last_error) {
                wp_send_json_error(__('Ошибка базы данных: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            wp_send_json_success($employees);

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при получении сотрудников: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Save employee via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function save_employee() {
        if (!$this->verify_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('employees');

            $employee_data = array(
                'user_id' => intval($_POST['user_id']),
                'name' => sanitize_text_field($_POST['name']),
                'email' => sanitize_email($_POST['email']),
                'phone' => sanitize_text_field($_POST['phone']),
                'position' => sanitize_text_field($_POST['position']),
                'status' => sanitize_text_field($_POST['status'])
            );

            $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;

            if ($employee_id > 0) {
                // Update existing employee
                $result = $wpdb->update($table, $employee_data, array('id' => $employee_id));
                $message = __('Сотрудник успешно обновлен.', 'chrono-forge');
            } else {
                // Create new employee
                $result = $wpdb->insert($table, $employee_data);
                $employee_id = $wpdb->insert_id;
                $message = __('Сотрудник успешно создан.', 'chrono-forge');
            }

            if ($result === false) {
                wp_send_json_error(__('Ошибка при сохранении сотрудника: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            wp_send_json_success(array(
                'message' => $message,
                'employee_id' => $employee_id
            ));

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при сохранении сотрудника: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Delete employee via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function delete_employee() {
        if (!$this->verify_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('employees');

            $employee_id = intval($_POST['employee_id']);

            if ($employee_id <= 0) {
                wp_send_json_error(__('Неверный ID сотрудника.', 'chrono-forge'));
                return;
            }

            $result = $wpdb->delete($table, array('id' => $employee_id));

            if ($result === false) {
                wp_send_json_error(__('Ошибка при удалении сотрудника: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            wp_send_json_success(__('Сотрудник успешно удален.', 'chrono-forge'));

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при удалении сотрудника: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Get customers via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function get_customers() {
        if (!$this->verify_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('customers');

            $customers = $wpdb->get_results("SELECT * FROM {$table} ORDER BY last_name ASC, first_name ASC");

            if ($wpdb->last_error) {
                wp_send_json_error(__('Ошибка базы данных: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            wp_send_json_success($customers);

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при получении клиентов: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Save customer via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function save_customer() {
        if (!$this->verify_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('customers');

            $customer_data = array(
                'user_id' => intval($_POST['user_id']),
                'first_name' => sanitize_text_field($_POST['first_name']),
                'last_name' => sanitize_text_field($_POST['last_name']),
                'email' => sanitize_email($_POST['email']),
                'phone' => sanitize_text_field($_POST['phone']),
                'address' => sanitize_textarea_field($_POST['address']),
                'notes' => sanitize_textarea_field($_POST['notes']),
                'status' => sanitize_text_field($_POST['status'])
            );

            $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;

            if ($customer_id > 0) {
                // Update existing customer
                $result = $wpdb->update($table, $customer_data, array('id' => $customer_id));
                $message = __('Клиент успешно обновлен.', 'chrono-forge');
            } else {
                // Create new customer
                $result = $wpdb->insert($table, $customer_data);
                $customer_id = $wpdb->insert_id;
                $message = __('Клиент успешно создан.', 'chrono-forge');
            }

            if ($result === false) {
                wp_send_json_error(__('Ошибка при сохранении клиента: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            wp_send_json_success(array(
                'message' => $message,
                'customer_id' => $customer_id
            ));

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при сохранении клиента: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Delete customer via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function delete_customer() {
        if (!$this->verify_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('customers');

            $customer_id = intval($_POST['customer_id']);

            if ($customer_id <= 0) {
                wp_send_json_error(__('Неверный ID клиента.', 'chrono-forge'));
                return;
            }

            $result = $wpdb->delete($table, array('id' => $customer_id));

            if ($result === false) {
                wp_send_json_error(__('Ошибка при удалении клиента: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            wp_send_json_success(__('Клиент успешно удален.', 'chrono-forge'));

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при удалении клиента: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Get calendar data via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function get_calendar_data() {
        if (!$this->verify_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $appointments_table = $database->get_table_name('appointments');
            $customers_table = $database->get_table_name('customers');
            $employees_table = $database->get_table_name('employees');
            $services_table = $database->get_table_name('services');

            $start_date = sanitize_text_field($_POST['start_date']);
            $end_date = sanitize_text_field($_POST['end_date']);

            $sql = "SELECT " .
                        "a.*, " .
                        "CONCAT(c.first_name, ' ', c.last_name) as customer_name, " .
                        "e.name as employee_name, " .
                        "s.name as service_name " .
                    "FROM {$appointments_table} a " .
                    "LEFT JOIN {$customers_table} c ON a.customer_id = c.id " .
                    "LEFT JOIN {$employees_table} e ON a.employee_id = e.id " .
                    "LEFT JOIN {$services_table} s ON a.service_id = s.id " .
                    "WHERE a.appointment_date BETWEEN %s AND %s " .
                    "ORDER BY a.appointment_date ASC, a.appointment_time ASC";

            $appointments = $wpdb->get_results($wpdb->prepare($sql, $start_date, $end_date));

            if ($wpdb->last_error) {
                wp_send_json_error(__('Ошибка базы данных: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            wp_send_json_success($appointments);

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при получении данных календаря: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Update appointment status via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function update_appointment_status() {
        if (!$this->verify_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('appointments');

            $appointment_id = intval($_POST['appointment_id']);
            $status = sanitize_text_field($_POST['status']);

            if ($appointment_id <= 0) {
                wp_send_json_error(__('Неверный ID записи.', 'chrono-forge'));
                return;
            }

            $result = $wpdb->update($table, array('status' => $status), array('id' => $appointment_id));

            if ($result === false) {
                wp_send_json_error(__('Ошибка при обновлении статуса: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            wp_send_json_success(__('Статус записи успешно обновлен.', 'chrono-forge'));

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при обновлении статуса: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Disable emergency mode via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function disable_emergency_mode() {
        if (!$this->verify_request('chrono_forge_emergency')) {
            return;
        }

        try {
            $core = chrono_forge();
            if ($core && method_exists($core, 'disable_emergency_mode')) {
                $result = $core->disable_emergency_mode();
                if ($result) {
                    wp_send_json_success(__('Аварийный режим отключен.', 'chrono-forge'));
                } else {
                    wp_send_json_error(__('Не удалось отключить аварийный режим.', 'chrono-forge'));
                }
            } else {
                wp_send_json_error(__('Основной класс плагина недоступен.', 'chrono-forge'));
            }

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при отключении аварийного режима: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Run diagnostics via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function run_diagnostics() {
        if (!$this->verify_request()) {
            return;
        }

        try {
            $diagnostics_data = array();

            // Check if diagnostics class is available
            if (class_exists('ChronoForge_Diagnostics')) {
                $diagnostics = ChronoForge_Diagnostics::instance();
                if (method_exists($diagnostics, 'run_full_diagnostics')) {
                    $diagnostics_data = $diagnostics->run_full_diagnostics();
                }
            }

            // Fallback basic diagnostics
            if (empty($diagnostics_data)) {
                $diagnostics_data = array(
                    'plugin_version' => CHRONO_FORGE_VERSION,
                    'wordpress_version' => get_bloginfo('version'),
                    'php_version' => PHP_VERSION,
                    'timestamp' => current_time('mysql')
                );
            }

            wp_send_json_success($diagnostics_data);

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при запуске диагностики: ', 'chrono-forge') . $e->getMessage());
        }
    }
}
