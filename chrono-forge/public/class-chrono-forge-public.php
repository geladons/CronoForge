<?php
/**
 * ChronoForge Public Class
 *
 * Handles all public-facing functionality of the plugin
 *
 * @package ChronoForge
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ChronoForge Public Class
 *
 * @since 1.0.0
 */
class ChronoForge_Public {

    /**
     * Singleton instance
     *
     * @since 1.0.0
     * @var ChronoForge_Public
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @since 1.0.0
     * @return ChronoForge_Public
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
        // Public AJAX actions (for logged-in and non-logged-in users)
        add_action('wp_ajax_chrono_forge_book_appointment', array($this, 'book_appointment'));
        add_action('wp_ajax_nopriv_chrono_forge_book_appointment', array($this, 'book_appointment'));
        add_action('wp_ajax_chrono_forge_get_available_times', array($this, 'get_available_times'));
        add_action('wp_ajax_nopriv_chrono_forge_get_available_times', array($this, 'get_available_times'));
        add_action('wp_ajax_chrono_forge_get_public_services', array($this, 'get_public_services'));
        add_action('wp_ajax_nopriv_chrono_forge_get_public_services', array($this, 'get_public_services'));
        add_action('wp_ajax_chrono_forge_get_public_employees', array($this, 'get_public_employees'));
        add_action('wp_ajax_nopriv_chrono_forge_get_public_employees', array($this, 'get_public_employees'));
        add_action('wp_ajax_chrono_forge_cancel_appointment', array($this, 'cancel_appointment'));
        add_action('wp_ajax_nopriv_chrono_forge_cancel_appointment', array($this, 'cancel_appointment'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue public scripts and styles
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueue_scripts() {
        // Only enqueue on pages that use ChronoForge shortcodes
        global $post;
        if (!is_object($post) || !has_shortcode($post->post_content, 'chrono_forge_booking')) {
            return;
        }

        wp_enqueue_script(
            'chrono-forge-public',
            CHRONO_FORGE_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            CHRONO_FORGE_VERSION,
            true
        );

        wp_enqueue_style(
            'chrono-forge-public',
            CHRONO_FORGE_PLUGIN_URL . 'assets/css/public.css',
            array(),
            CHRONO_FORGE_VERSION
        );

        // Localize script for AJAX
        wp_localize_script('chrono-forge-public', 'chronoForgePublic', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chrono_forge_public'),
            'strings' => array(
                'booking_success' => __('Запись успешно создана!', 'chrono-forge'),
                'booking_error' => __('Ошибка при создании записи.', 'chrono-forge'),
                'loading' => __('Загрузка...', 'chrono-forge'),
                'select_service' => __('Выберите услугу', 'chrono-forge'),
                'select_employee' => __('Выберите специалиста', 'chrono-forge'),
                'select_date' => __('Выберите дату', 'chrono-forge'),
                'select_time' => __('Выберите время', 'chrono-forge'),
                'no_times_available' => __('На выбранную дату нет свободного времени', 'chrono-forge'),
                'fill_required_fields' => __('Заполните все обязательные поля', 'chrono-forge')
            )
        ));
    }

    /**
     * Verify nonce for public requests
     *
     * @since 1.0.0
     * @param string $action Nonce action
     * @return bool
     */
    private function verify_public_request($action = 'chrono_forge_public') {
        if (!wp_verify_nonce($_POST['nonce'], $action)) {
            wp_send_json_error(__('Неверный токен безопасности.', 'chrono-forge'));
            return false;
        }
        return true;
    }

    /**
     * Book appointment via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function book_appointment() {
        if (!$this->verify_public_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $appointments_table = $database->get_table_name('appointments');
            $customers_table = $database->get_table_name('customers');

            // Sanitize input data
            $service_id = intval($_POST['service_id']);
            $employee_id = intval($_POST['employee_id']);
            $appointment_date = sanitize_text_field($_POST['appointment_date']);
            $appointment_time = sanitize_text_field($_POST['appointment_time']);
            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name = sanitize_text_field($_POST['last_name']);
            $email = sanitize_email($_POST['email']);
            $phone = sanitize_text_field($_POST['phone']);
            $notes = sanitize_textarea_field($_POST['notes']);

            // Validate required fields
            if (empty($service_id) || empty($employee_id) || empty($appointment_date) || 
                empty($appointment_time) || empty($first_name) || empty($last_name) || empty($email)) {
                wp_send_json_error(__('Заполните все обязательные поля.', 'chrono-forge'));
                return;
            }

            // Check if customer exists or create new one
            $existing_customer = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$customers_table} WHERE email = %s",
                $email
            ));

            if ($existing_customer) {
                $customer_id = $existing_customer->id;
                // Update customer info if needed
                $wpdb->update(
                    $customers_table,
                    array(
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'phone' => $phone
                    ),
                    array('id' => $customer_id)
                );
            } else {
                // Create new customer
                $wpdb->insert(
                    $customers_table,
                    array(
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone' => $phone,
                        'status' => 'active'
                    )
                );
                $customer_id = $wpdb->insert_id;
            }

            if (!$customer_id) {
                wp_send_json_error(__('Ошибка при создании клиента.', 'chrono-forge'));
                return;
            }

            // Get service details for pricing
            $services_table = $database->get_table_name('services');
            $service = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$services_table} WHERE id = %d AND status = 'active'",
                $service_id
            ));

            if (!$service) {
                wp_send_json_error(__('Выбранная услуга недоступна.', 'chrono-forge'));
                return;
            }

            // Check if time slot is still available
            $existing_appointment = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$appointments_table} " .
                "WHERE employee_id = %d AND appointment_date = %s AND appointment_time = %s " .
                "AND status NOT IN ('cancelled')",
                $employee_id, $appointment_date, $appointment_time
            ));

            if ($existing_appointment) {
                wp_send_json_error(__('Выбранное время уже занято. Пожалуйста, выберите другое время.', 'chrono-forge'));
                return;
            }

            // Create appointment
            $appointment_data = array(
                'customer_id' => $customer_id,
                'employee_id' => $employee_id,
                'service_id' => $service_id,
                'appointment_date' => $appointment_date,
                'appointment_time' => $appointment_time,
                'duration' => $service->duration,
                'status' => 'pending',
                'notes' => $notes,
                'total_price' => $service->price
            );

            $result = $wpdb->insert($appointments_table, $appointment_data);

            if ($result === false) {
                wp_send_json_error(__('Ошибка при создании записи: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            $appointment_id = $wpdb->insert_id;

            // Send confirmation email (if notification system is available)
            if (class_exists('ChronoForge_Notification_Manager')) {
                $notification_manager = ChronoForge_Notification_Manager::instance();
                if (method_exists($notification_manager, 'send_appointment_confirmation')) {
                    $notification_manager->send_appointment_confirmation($appointment_id);
                }
            }

            wp_send_json_success(array(
                'message' => __('Запись успешно создана! Мы свяжемся с вами для подтверждения.', 'chrono-forge'),
                'appointment_id' => $appointment_id
            ));

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при создании записи: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Get available times for a specific date and employee
     *
     * @since 1.0.0
     * @return void
     */
    public function get_available_times() {
        if (!$this->verify_public_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $appointments_table = $database->get_table_name('appointments');
            $schedules_table = $database->get_table_name('schedules');

            $employee_id = intval($_POST['employee_id']);
            $date = sanitize_text_field($_POST['date']);
            $service_id = intval($_POST['service_id']);

            if (empty($employee_id) || empty($date)) {
                wp_send_json_error(__('Неверные параметры.', 'chrono-forge'));
                return;
            }

            // Get day of week (1 = Monday, 7 = Sunday)
            $day_of_week = date('N', strtotime($date));

            // Get employee schedule for this day
            $schedule = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$schedules_table} " .
                "WHERE employee_id = %d AND day_of_week = %d AND status = 'active'",
                $employee_id, $day_of_week
            ));

            if (!$schedule) {
                wp_send_json_success(array()); // No schedule = no available times
                return;
            }

            // Get service duration
            $services_table = $database->get_table_name('services');
            $service = $wpdb->get_row($wpdb->prepare(
                "SELECT duration FROM {$services_table} WHERE id = %d",
                $service_id
            ));

            $service_duration = $service ? $service->duration : 60; // Default 60 minutes

            // Get existing appointments for this date and employee
            $existing_appointments = $wpdb->get_results($wpdb->prepare(
                "SELECT appointment_time, duration FROM {$appointments_table} " .
                "WHERE employee_id = %d AND appointment_date = %s " .
                "AND status NOT IN ('cancelled')",
                $employee_id, $date
            ));

            // Generate available time slots
            $available_times = $this->generate_available_times(
                $schedule->start_time,
                $schedule->end_time,
                $schedule->break_start,
                $schedule->break_end,
                $service_duration,
                $existing_appointments
            );

            wp_send_json_success($available_times);

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при получении доступного времени: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Generate available time slots
     *
     * @since 1.0.0
     * @param string $start_time Work start time
     * @param string $end_time Work end time
     * @param string $break_start Break start time
     * @param string $break_end Break end time
     * @param int $service_duration Service duration in minutes
     * @param array $existing_appointments Existing appointments
     * @return array Available time slots
     */
    private function generate_available_times($start_time, $end_time, $break_start, $break_end, $service_duration, $existing_appointments) {
        $available_times = array();
        $slot_interval = 30; // 30-minute intervals

        $current_time = strtotime($start_time);
        $end_timestamp = strtotime($end_time);
        $break_start_timestamp = $break_start ? strtotime($break_start) : null;
        $break_end_timestamp = $break_end ? strtotime($break_end) : null;

        while ($current_time < $end_timestamp) {
            $slot_time = date('H:i:s', $current_time);
            $slot_end_time = $current_time + ($service_duration * 60);

            // Check if slot conflicts with break time
            $conflicts_with_break = false;
            if ($break_start_timestamp && $break_end_timestamp) {
                if ($current_time < $break_end_timestamp && $slot_end_time > $break_start_timestamp) {
                    $conflicts_with_break = true;
                }
            }

            // Check if slot conflicts with existing appointments
            $conflicts_with_appointment = false;
            foreach ($existing_appointments as $appointment) {
                $appointment_start = strtotime($appointment->appointment_time);
                $appointment_end = $appointment_start + ($appointment->duration * 60);

                if ($current_time < $appointment_end && $slot_end_time > $appointment_start) {
                    $conflicts_with_appointment = true;
                    break;
                }
            }

            // Check if slot ends before work day ends
            $ends_before_work_end = $slot_end_time <= $end_timestamp;

            if (!$conflicts_with_break && !$conflicts_with_appointment && $ends_before_work_end) {
                $available_times[] = array(
                    'time' => $slot_time,
                    'display' => date('H:i', $current_time)
                );
            }

            $current_time += ($slot_interval * 60);
        }

        return $available_times;
    }

    /**
     * Get public services via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function get_public_services() {
        if (!$this->verify_public_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('services');

            $services = $wpdb->get_results(
                "SELECT id, name, description, duration, price, category " .
                "FROM {$table} " .
                "WHERE status = 'active' " .
                "ORDER BY category ASC, name ASC"
            );

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
     * Get public employees via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function get_public_employees() {
        if (!$this->verify_public_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('employees');

            $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;

            // For now, return all active employees
            // In the future, this could be filtered by service capabilities
            $employees = $wpdb->get_results(
                "SELECT id, name, position " .
                "FROM {$table} " .
                "WHERE status = 'active' " .
                "ORDER BY name ASC"
            );

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
     * Cancel appointment via AJAX
     *
     * @since 1.0.0
     * @return void
     */
    public function cancel_appointment() {
        if (!$this->verify_public_request()) {
            return;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('appointments');

            $appointment_id = intval($_POST['appointment_id']);
            $email = sanitize_email($_POST['email']);

            if (empty($appointment_id) || empty($email)) {
                wp_send_json_error(__('Неверные параметры.', 'chrono-forge'));
                return;
            }

            // Verify that the appointment belongs to the customer with this email
            $customers_table = $database->get_table_name('customers');
            $appointment = $wpdb->get_row($wpdb->prepare(
                "SELECT a.*, c.email " .
                "FROM {$table} a " .
                "JOIN {$customers_table} c ON a.customer_id = c.id " .
                "WHERE a.id = %d AND c.email = %s",
                $appointment_id, $email
            ));

            if (!$appointment) {
                wp_send_json_error(__('Запись не найдена или неверный email.', 'chrono-forge'));
                return;
            }

            if ($appointment->status === 'cancelled') {
                wp_send_json_error(__('Запись уже отменена.', 'chrono-forge'));
                return;
            }

            // Check if appointment can be cancelled (e.g., not too close to appointment time)
            $appointment_datetime = strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time);
            $current_time = current_time('timestamp');
            $hours_until_appointment = ($appointment_datetime - $current_time) / 3600;

            if ($hours_until_appointment < 24) {
                wp_send_json_error(__('Запись можно отменить не позднее чем за 24 часа до назначенного времени.', 'chrono-forge'));
                return;
            }

            // Cancel the appointment
            $result = $wpdb->update(
                $table,
                array('status' => 'cancelled'),
                array('id' => $appointment_id)
            );

            if ($result === false) {
                wp_send_json_error(__('Ошибка при отмене записи: ', 'chrono-forge') . $wpdb->last_error);
                return;
            }

            // Send cancellation notification (if notification system is available)
            if (class_exists('ChronoForge_Notification_Manager')) {
                $notification_manager = ChronoForge_Notification_Manager::instance();
                if (method_exists($notification_manager, 'send_appointment_cancellation')) {
                    $notification_manager->send_appointment_cancellation($appointment_id);
                }
            }

            wp_send_json_success(__('Запись успешно отменена.', 'chrono-forge'));

        } catch (Exception $e) {
            wp_send_json_error(__('Ошибка при отмене записи: ', 'chrono-forge') . $e->getMessage());
        }
    }

    /**
     * Get customer appointments
     *
     * @since 1.0.0
     * @param string $email Customer email
     * @return array|false Customer appointments or false on error
     */
    public function get_customer_appointments($email) {
        if (empty($email)) {
            return false;
        }

        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $appointments_table = $database->get_table_name('appointments');
            $customers_table = $database->get_table_name('customers');
            $services_table = $database->get_table_name('services');
            $employees_table = $database->get_table_name('employees');

            $sql = "SELECT " .
                        "a.*, " .
                        "s.name as service_name, " .
                        "e.name as employee_name " .
                    "FROM {$appointments_table} a " .
                    "JOIN {$customers_table} c ON a.customer_id = c.id " .
                    "LEFT JOIN {$services_table} s ON a.service_id = s.id " .
                    "LEFT JOIN {$employees_table} e ON a.employee_id = e.id " .
                    "WHERE c.email = %s " .
                    "ORDER BY a.appointment_date DESC, a.appointment_time DESC";

            $appointments = $wpdb->get_results($wpdb->prepare($sql, $email));

            return $appointments;

        } catch (Exception $e) {
            error_log('ChronoForge: Error getting customer appointments: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a time slot is available
     *
     * @since 1.0.0
     * @param int $employee_id Employee ID
     * @param string $date Appointment date
     * @param string $time Appointment time
     * @param int $duration Service duration in minutes
     * @return bool True if available, false otherwise
     */
    public function is_time_slot_available($employee_id, $date, $time, $duration = 60) {
        try {
            global $wpdb;
            $database = ChronoForge_Database::instance();
            $table = $database->get_table_name('appointments');

            $appointment_start = strtotime($date . ' ' . $time);
            $appointment_end = $appointment_start + ($duration * 60);

            // Check for conflicting appointments
            $conflicts = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} " .
                "WHERE employee_id = %d " .
                "AND appointment_date = %s " .
                "AND status NOT IN ('cancelled') " .
                "AND (" .
                    "(UNIX_TIMESTAMP(CONCAT(appointment_date, ' ', appointment_time)) < %d " .
                     "AND UNIX_TIMESTAMP(CONCAT(appointment_date, ' ', appointment_time)) + (duration * 60) > %d) " .
                    "OR " .
                    "(UNIX_TIMESTAMP(CONCAT(appointment_date, ' ', appointment_time)) < %d " .
                     "AND UNIX_TIMESTAMP(CONCAT(appointment_date, ' ', appointment_time)) + (duration * 60) > %d) " .
                ")",
                $employee_id, $date, $appointment_end, $appointment_start, $appointment_start, $appointment_end
            ));

            return $conflicts == 0;

        } catch (Exception $e) {
            error_log('ChronoForge: Error checking time slot availability: ' . $e->getMessage());
            return false;
        }
    }
}
