<?php
/**
 * Класс для управления админ-меню плагина ChronoForge
 * 
 * Этот класс создает все пункты меню в админ-панели WordPress
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}

class ChronoForge_Admin_Menu {

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
     * Инициализация хуков
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }

    /**
     * Добавление пунктов меню в админ-панель
     */
    public function add_admin_menu() {
        // Главное меню
        add_menu_page(
            __('ChronoForge', 'chrono-forge'),
            __('ChronoForge', 'chrono-forge'),
            'manage_options',
            'chrono-forge',
            array($this, 'dashboard_page'),
            'dashicons-calendar-alt',
            30
        );

        // Подменю - Дашборд
        add_submenu_page(
            'chrono-forge',
            __('Дашборд', 'chrono-forge'),
            __('Дашборд', 'chrono-forge'),
            'manage_options',
            'chrono-forge',
            array($this, 'dashboard_page')
        );

        // Подменю - Календарь
        add_submenu_page(
            'chrono-forge',
            __('Календарь', 'chrono-forge'),
            __('Календарь', 'chrono-forge'),
            'manage_options',
            'chrono-forge-calendar',
            array($this, 'calendar_page')
        );

        // Подменю - Записи
        add_submenu_page(
            'chrono-forge',
            __('Записи', 'chrono-forge'),
            __('Записи', 'chrono-forge'),
            'manage_options',
            'chrono-forge-appointments',
            array($this, 'appointments_page')
        );

        // Подменю - Услуги
        add_submenu_page(
            'chrono-forge',
            __('Услуги', 'chrono-forge'),
            __('Услуги', 'chrono-forge'),
            'manage_options',
            'chrono-forge-services',
            array($this, 'services_page')
        );

        // Подменю - Сотрудники
        add_submenu_page(
            'chrono-forge',
            __('Сотрудники', 'chrono-forge'),
            __('Сотрудники', 'chrono-forge'),
            'manage_options',
            'chrono-forge-employees',
            array($this, 'employees_page')
        );

        // Подменю - Клиенты
        add_submenu_page(
            'chrono-forge',
            __('Клиенты', 'chrono-forge'),
            __('Клиенты', 'chrono-forge'),
            'manage_options',
            'chrono-forge-customers',
            array($this, 'customers_page')
        );

        // Подменю - Настройки
        add_submenu_page(
            'chrono-forge',
            __('Настройки', 'chrono-forge'),
            __('Настройки', 'chrono-forge'),
            'manage_options',
            'chrono-forge-settings',
            array($this, 'settings_page')
        );

        // Подменю - Диагностика
        add_submenu_page(
            'chrono-forge',
            __('Диагностика', 'chrono-forge'),
            __('Диагностика', 'chrono-forge'),
            'manage_options',
            'chrono-forge-diagnostics',
            array($this, 'diagnostics_page')
        );
    }

    /**
     * Обработка действий в админ-панели
     */
    public function handle_admin_actions() {
        // Only handle actions on our plugin pages
        if (empty($_GET['page']) || strpos($_GET['page'], 'chrono-forge') !== 0) {
            return;
        }

        // Проверяем права доступа
        if (!current_user_can('manage_options')) {
            return;
        }

        // Обработка GET-запросов (удаление)
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['action'])) {
            $action = sanitize_text_field($_GET['action']);

            switch ($action) {
                case 'delete_employee':
                    $this->handle_delete_employee();
                    break;
                case 'delete_service':
                    $this->handle_delete_service();
                    break;
                case 'delete_appointment':
                    $this->handle_delete_appointment();
                    break;
            }
        }

        // Обработка POST-запросов
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
            $action = sanitize_text_field($_POST['action']);

            // Проверка nonce
            if (!wp_verify_nonce($_POST['_wpnonce'], 'chrono_forge_admin_action')) {
                wp_die(__('Ошибка безопасности', 'chrono-forge'));
            }

            switch ($action) {
                case 'save_category':
                    $this->handle_save_category();
                    break;
                case 'save_service':
                    $this->handle_save_service();
                    break;
                case 'save_employee':
                    $this->handle_save_employee();
                    break;
                case 'save_customer':
                    $this->handle_save_customer();
                    break;
                case 'save_appointment':
                    $this->handle_save_appointment();
                    break;
                case 'save_schedule':
                    $this->handle_save_schedule();
                    break;
                case 'save_settings':
                    $this->handle_save_settings();
                    break;
            }
        }

        // Обработка GET-действий
        if (!empty($_GET['action']) && !empty($_GET['page']) && strpos($_GET['page'], 'chrono-forge') === 0) {
            $action = sanitize_text_field($_GET['action']);
            
            switch ($action) {
                case 'delete_category':
                    $this->handle_delete_category();
                    break;
                case 'delete_service':
                    $this->handle_delete_service();
                    break;
                case 'delete_employee':
                    $this->handle_delete_employee();
                    break;
                case 'delete_customer':
                    $this->handle_delete_customer();
                    break;
                case 'delete_appointment':
                    $this->handle_delete_appointment();
                    break;
            }
        }
    }

    /**
     * Страница дашборда
     */
    public function dashboard_page() {
        $stats = $this->db_manager->get_dashboard_stats();
        $recent_appointments = $this->db_manager->get_all_appointments(array(
            'date_from' => date('Y-m-d'),
            'limit' => 10
        ));
        
        include CHRONO_FORGE_PLUGIN_DIR . 'admin/views/view-dashboard.php';
    }

    /**
     * Страница календаря
     */
    public function calendar_page() {
        $employees = $this->db_manager->get_all_employees();
        $services = $this->db_manager->get_all_services();
        
        include CHRONO_FORGE_PLUGIN_DIR . 'admin/views/view-calendar.php';
    }

    /**
     * Страница записей
     */
    public function appointments_page() {
        $appointments = $this->db_manager->get_all_appointments();
        $employees = $this->db_manager->get_all_employees();
        $services = $this->db_manager->get_all_services();
        
        include CHRONO_FORGE_PLUGIN_DIR . 'admin/views/view-appointments.php';
    }

    /**
     * Страница услуг
     */
    public function services_page() {
        $services = $this->db_manager->get_all_services();
        $categories = $this->db_manager->get_all_categories();
        
        include CHRONO_FORGE_PLUGIN_DIR . 'admin/views/view-services.php';
    }

    /**
     * Страница сотрудников
     */
    public function employees_page() {
        $employees = $this->db_manager->get_all_employees();
        $services = $this->db_manager->get_all_services();
        
        include CHRONO_FORGE_PLUGIN_DIR . 'admin/views/view-employees.php';
    }

    /**
     * Страница клиентов
     */
    public function customers_page() {
        $customers = $this->db_manager->get_all_customers();
        
        include CHRONO_FORGE_PLUGIN_DIR . 'admin/views/view-customers.php';
    }

    /**
     * Страница настроек
     */
    public function settings_page() {
        // Check permissions directly here
        if (!current_user_can('manage_options')) {
            wp_die(__('У вас недостаточно прав для доступа к этой странице.', 'chrono-forge'));
        }

        $settings = get_option('chrono_forge_settings', array());

        // Check if settings view file exists
        $settings_view = CHRONO_FORGE_PLUGIN_DIR . 'admin/views/view-settings.php';
        if (file_exists($settings_view)) {
            include $settings_view;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . __('Настройки ChronoForge', 'chrono-forge') . '</h1>';
            echo '<div class="notice notice-error"><p>' . __('Файл настроек не найден.', 'chrono-forge') . '</p></div>';
            echo '</div>';
        }
    }

    /**
     * Обработка сохранения категории
     */
    private function handle_save_category() {
        $category_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'color' => sanitize_hex_color($_POST['color']),
            'sort_order' => intval($_POST['sort_order'])
        );

        if (!empty($_POST['category_id'])) {
            // Обновление существующей категории
            $category_id = intval($_POST['category_id']);
            $result = $this->db_manager->update_category($category_id, $category_data);
            $message = $result ? __('Категория обновлена', 'chrono-forge') : __('Ошибка при обновлении категории', 'chrono-forge');
        } else {
            // Создание новой категории
            $category_id = $this->db_manager->insert_category($category_data);
            $message = $category_id ? __('Категория создана', 'chrono-forge') : __('Ошибка при создании категории', 'chrono-forge');
        }

        $this->add_admin_notice($message, $category_id ? 'success' : 'error');
    }

    /**
     * Обработка сохранения услуги
     */
    private function handle_save_service() {
        $service_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
            'duration' => intval($_POST['duration']),
            'price' => floatval($_POST['price']),
            'buffer_time' => intval($_POST['buffer_time']),
            'color' => sanitize_hex_color($_POST['color']),
            'status' => sanitize_text_field($_POST['status'])
        );

        if (!empty($_POST['service_id'])) {
            // Обновление существующей услуги
            $service_id = intval($_POST['service_id']);
            $result = $this->db_manager->update_service($service_id, $service_data);
            $message = $result ? __('Услуга обновлена', 'chrono-forge') : __('Ошибка при обновлении услуги', 'chrono-forge');
        } else {
            // Создание новой услуги
            $service_id = $this->db_manager->insert_service($service_data);
            $message = $service_id ? __('Услуга создана', 'chrono-forge') : __('Ошибка при создании услуги', 'chrono-forge');
        }

        // Назначение услуги сотрудникам
        if ($service_id && !empty($_POST['employee_ids'])) {
            $employee_ids = array_map('intval', $_POST['employee_ids']);
            foreach ($employee_ids as $employee_id) {
                // Логика назначения услуги сотруднику
            }
        }

        $this->add_admin_notice($message, $service_id ? 'success' : 'error');
    }

    /**
     * Обработка сохранения сотрудника
     */
    private function handle_save_employee() {
        $employee_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'description' => sanitize_textarea_field($_POST['description']),
            'color' => sanitize_hex_color($_POST['color']),
            'status' => sanitize_text_field($_POST['status'])
        );

        if (!empty($_POST['employee_id'])) {
            // Обновление существующего сотрудника
            $employee_id = intval($_POST['employee_id']);
            $result = $this->db_manager->update_employee($employee_id, $employee_data);
            $message = $result ? __('Сотрудник обновлен', 'chrono-forge') : __('Ошибка при обновлении сотрудника', 'chrono-forge');
        } else {
            // Создание нового сотрудника
            $employee_id = $this->db_manager->insert_employee($employee_data);
            $message = $employee_id ? __('Сотрудник создан', 'chrono-forge') : __('Ошибка при создании сотрудника', 'chrono-forge');
        }

        // Сохранение графика работы
        if ($employee_id && !empty($_POST['schedule'])) {
            $this->db_manager->save_employee_schedule($employee_id, $_POST['schedule']);
        }

        // Назначение услуг сотруднику
        if ($employee_id && !empty($_POST['service_ids'])) {
            $service_ids = array_map('intval', $_POST['service_ids']);
            $this->db_manager->assign_services_to_employee($employee_id, $service_ids);
        }

        $this->add_admin_notice($message, $employee_id ? 'success' : 'error');
    }

    /**
     * Обработка сохранения клиента
     */
    private function handle_save_customer() {
        $customer_data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'date_of_birth' => !empty($_POST['date_of_birth']) ? sanitize_text_field($_POST['date_of_birth']) : null,
            'notes' => sanitize_textarea_field($_POST['notes'])
        );

        if (!empty($_POST['customer_id'])) {
            // Обновление существующего клиента
            $customer_id = intval($_POST['customer_id']);
            $result = $this->db_manager->update_customer($customer_id, $customer_data);
            $message = $result ? __('Клиент обновлен', 'chrono-forge') : __('Ошибка при обновлении клиента', 'chrono-forge');
        } else {
            // Создание нового клиента
            $customer_id = $this->db_manager->insert_customer($customer_data);
            $message = $customer_id ? __('Клиент создан', 'chrono-forge') : __('Ошибка при создании клиента', 'chrono-forge');
        }

        $this->add_admin_notice($message, $customer_id ? 'success' : 'error');
    }

    /**
     * Обработка сохранения записи
     */
    private function handle_save_appointment() {
        $appointment_data = array(
            'service_id' => intval($_POST['service_id']),
            'employee_id' => intval($_POST['employee_id']),
            'customer_id' => intval($_POST['customer_id']),
            'appointment_date' => sanitize_text_field($_POST['appointment_date']),
            'appointment_time' => sanitize_text_field($_POST['appointment_time']),
            'status' => sanitize_text_field($_POST['status']),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'internal_notes' => sanitize_textarea_field($_POST['internal_notes']),
            'total_price' => floatval($_POST['total_price'])
        );

        // Вычисляем время окончания
        if (!empty($appointment_data['appointment_time'])) {
            $service = $this->db_manager->get_service($appointment_data['service_id']);
            if ($service) {
                $end_time = date('H:i:s', strtotime($appointment_data['appointment_time']) + ($service->duration * 60));
                $appointment_data['end_time'] = $end_time;
            }
        }

        if (!empty($_POST['appointment_id'])) {
            // Обновление существующей записи
            $appointment_id = intval($_POST['appointment_id']);
            $result = $this->db_manager->update_appointment($appointment_id, $appointment_data);
            $message = $result ? __('Запись обновлена', 'chrono-forge') : __('Ошибка при обновлении записи', 'chrono-forge');
        } else {
            // Создание новой записи
            $appointment_id = $this->db_manager->insert_appointment($appointment_data);
            $message = $appointment_id ? __('Запись создана', 'chrono-forge') : __('Ошибка при создании записи', 'chrono-forge');
        }

        $this->add_admin_notice($message, $appointment_id ? 'success' : 'error');
    }

    /**
     * Обработка сохранения графика работы
     */
    private function handle_save_schedule() {
        $employee_id = intval($_POST['employee_id']);
        $schedule_data = $_POST['schedule'];

        if (!$employee_id) {
            $this->add_admin_notice(__('Ошибка: не указан сотрудник', 'chrono-forge'), 'error');
            return;
        }

        $result = $this->db_manager->save_employee_schedule($employee_id, $schedule_data);
        $message = $result ? __('График работы сохранен', 'chrono-forge') : __('Ошибка при сохранении графика', 'chrono-forge');

        $this->add_admin_notice($message, $result ? 'success' : 'error');
    }

    /**
     * Обработка сохранения настроек
     */
    private function handle_save_settings() {
        $settings = array(
            'plugin_language' => sanitize_text_field($_POST['plugin_language']),
            'currency' => sanitize_text_field($_POST['currency']),
            'currency_symbol' => sanitize_text_field($_POST['currency_symbol']),
            'date_format' => sanitize_text_field($_POST['date_format']),
            'time_format' => sanitize_text_field($_POST['time_format']),
            'primary_color' => sanitize_hex_color($_POST['primary_color']),
            'secondary_color' => sanitize_hex_color($_POST['secondary_color']),
            'enable_payments' => !empty($_POST['enable_payments']),
            'payment_required' => !empty($_POST['payment_required']),
            'min_booking_time' => intval($_POST['min_booking_time']),
            'max_booking_time' => intval($_POST['max_booking_time']),
            'enable_notifications' => !empty($_POST['enable_notifications']),
            'admin_email_notifications' => !empty($_POST['admin_email_notifications']),
            'customer_email_notifications' => !empty($_POST['customer_email_notifications']),
            'enable_sms_notifications' => !empty($_POST['enable_sms_notifications']),
            'company_name' => sanitize_text_field($_POST['company_name'] ?? ''),
            'company_phone' => sanitize_text_field($_POST['company_phone'] ?? ''),
            'company_email' => sanitize_email($_POST['company_email'] ?? ''),
            'company_address' => sanitize_textarea_field($_POST['company_address'] ?? ''),
            'admin_email' => sanitize_email($_POST['admin_email'] ?? '')
        );

        // Payment gateway settings
        if (!empty($_POST['stripe'])) {
            $settings['stripe'] = array(
                'enabled' => !empty($_POST['stripe']['enabled']),
                'publishable_key' => sanitize_text_field($_POST['stripe']['publishable_key'] ?? ''),
                'secret_key' => sanitize_text_field($_POST['stripe']['secret_key'] ?? '')
            );
        }

        if (!empty($_POST['paypal'])) {
            $settings['paypal'] = array(
                'enabled' => !empty($_POST['paypal']['enabled']),
                'client_id' => sanitize_text_field($_POST['paypal']['client_id'] ?? ''),
                'client_secret' => sanitize_text_field($_POST['paypal']['client_secret'] ?? ''),
                'email' => sanitize_email($_POST['paypal']['email'] ?? ''),
                'sandbox' => !empty($_POST['paypal']['sandbox'])
            );
        }

        if (!empty($_POST['square'])) {
            $settings['square'] = array(
                'enabled' => !empty($_POST['square']['enabled']),
                'application_id' => sanitize_text_field($_POST['square']['application_id'] ?? ''),
                'access_token' => sanitize_text_field($_POST['square']['access_token'] ?? ''),
                'sandbox' => !empty($_POST['square']['sandbox'])
            );
        }

        // SMS settings
        if (!empty($_POST['sms'])) {
            $settings['sms'] = array(
                'provider' => sanitize_text_field($_POST['sms']['provider'] ?? ''),
                'api_key' => sanitize_text_field($_POST['sms']['api_key'] ?? ''),
                'api_secret' => sanitize_text_field($_POST['sms']['api_secret'] ?? ''),
                'from_number' => sanitize_text_field($_POST['sms']['from_number'] ?? '')
            );
        }

        // Calendar integration settings
        if (!empty($_POST['google_calendar'])) {
            $settings['google_calendar'] = array(
                'enabled' => !empty($_POST['google_calendar']['enabled']),
                'client_id' => sanitize_text_field($_POST['google_calendar']['client_id'] ?? ''),
                'client_secret' => sanitize_text_field($_POST['google_calendar']['client_secret'] ?? '')
            );
        }

        if (!empty($_POST['outlook_calendar'])) {
            $settings['outlook_calendar'] = array(
                'enabled' => !empty($_POST['outlook_calendar']['enabled']),
                'client_id' => sanitize_text_field($_POST['outlook_calendar']['client_id'] ?? ''),
                'client_secret' => sanitize_text_field($_POST['outlook_calendar']['client_secret'] ?? '')
            );
        }

        $result = update_option('chrono_forge_settings', $settings);
        $message = $result ? __('Настройки сохранены', 'chrono-forge') : __('Ошибка при сохранении настроек', 'chrono-forge');
        
        $this->add_admin_notice($message, $result ? 'success' : 'error');
    }

    /**
     * Обработка удаления категории
     */
    private function handle_delete_category() {
        if (!empty($_GET['id']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_category')) {
            $category_id = intval($_GET['id']);
            $result = $this->db_manager->delete_category($category_id);
            $message = $result ? __('Категория удалена', 'chrono-forge') : __('Ошибка при удалении категории', 'chrono-forge');
            $this->add_admin_notice($message, $result ? 'success' : 'error');
        }
    }

    /**
     * Обработка удаления услуги
     */
    private function handle_delete_service() {
        if (!empty($_GET['id']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_service')) {
            $service_id = intval($_GET['id']);
            $result = $this->db_manager->delete_service($service_id);
            $message = $result ? __('Услуга удалена', 'chrono-forge') : __('Ошибка при удалении услуги', 'chrono-forge');
            $this->add_admin_notice($message, $result ? 'success' : 'error');
        }
    }

    /**
     * Обработка удаления сотрудника
     */
    private function handle_delete_employee() {
        if (!empty($_GET['id']) && !empty($_GET['_wpnonce'])) {
            // Verify nonce
            if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_employee_' . $_GET['id'])) {
                wp_die(__('Ошибка безопасности', 'chrono-forge'));
            }

            $employee_id = intval($_GET['id']);
            $result = $this->db_manager->delete_employee($employee_id);
            $message = $result ? __('Сотрудник удален', 'chrono-forge') : __('Ошибка при удалении сотрудника', 'chrono-forge');
            $this->add_admin_notice($message, $result ? 'success' : 'error');

            // Redirect to avoid resubmission
            wp_redirect(admin_url('admin.php?page=chrono-forge-employees'));
            exit;
        }
    }

    /**
     * Обработка удаления клиента
     */
    private function handle_delete_customer() {
        if (!empty($_GET['id']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_customer')) {
            $customer_id = intval($_GET['id']);
            $result = $this->db_manager->delete_customer($customer_id);
            $message = $result ? __('Клиент удален', 'chrono-forge') : __('Ошибка при удалении клиента', 'chrono-forge');
            $this->add_admin_notice($message, $result ? 'success' : 'error');
        }
    }

    /**
     * Обработка удаления записи
     */
    private function handle_delete_appointment() {
        if (!empty($_GET['id']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_appointment')) {
            $appointment_id = intval($_GET['id']);
            $result = $this->db_manager->delete_appointment($appointment_id);
            $message = $result ? __('Запись удалена', 'chrono-forge') : __('Ошибка при удалении записи', 'chrono-forge');
            $this->add_admin_notice($message, $result ? 'success' : 'error');
        }
    }

    /**
     * Добавление уведомления в админ-панель
     *
     * @param string $message
     * @param string $type
     */
    private function add_admin_notice($message, $type = 'info') {
        add_action('admin_notices', function() use ($message, $type) {
            echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
        });
    }

    /**
     * Страница диагностики
     */
    public function diagnostics_page() {
        // Load diagnostics classes if not already loaded
        if (!class_exists('ChronoForge_Diagnostics')) {
            require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-diagnostics.php';
        }
        if (!class_exists('ChronoForge_Admin_Diagnostics')) {
            require_once CHRONO_FORGE_PLUGIN_DIR . 'admin/class-chrono-forge-admin-diagnostics.php';
        }

        $admin_diagnostics = ChronoForge_Admin_Diagnostics::instance();
        $admin_diagnostics->render_diagnostics_page();
    }
}
