<?php
/**
 * Основной класс плагина ChronoForge
 * 
 * Этот класс является ядром плагина и управляет всеми его компонентами
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}

class ChronoForge_Core {

    /**
     * Единственный экземпляр класса (синглтон)
     * 
     * @var ChronoForge_Core
     */
    private static $instance = null;

    /**
     * Менеджер базы данных
     * 
     * @var ChronoForge_DB_Manager
     */
    public $db_manager;

    /**
     * Обработчик AJAX-запросов
     * 
     * @var ChronoForge_Ajax_Handler
     */
    public $ajax_handler;

    /**
     * Менеджер шорткодов
     * 
     * @var ChronoForge_Shortcodes
     */
    public $shortcodes;

    /**
     * Менеджер админ-меню
     *
     * @var ChronoForge_Admin_Menu
     */
    public $admin_menu;

    /**
     * Менеджер платежей
     *
     * @var ChronoForge_Payment_Manager
     */
    public $payment_manager;

    /**
     * Менеджер уведомлений
     *
     * @var ChronoForge_Notification_Manager
     */
    public $notification_manager;

    /**
     * Интеграция с календарями
     *
     * @var ChronoForge_Calendar_Integration
     */
    public $calendar_integration;

    /**
     * Конструктор класса
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
        $this->init_components();
    }

    /**
     * Получение единственного экземпляра класса (синглтон)
     * 
     * @return ChronoForge_Core
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Инициализация хуков WordPress
     */
    private function init_hooks() {
        // Хуки инициализации
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Хуки для подключения скриптов и стилей
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Хуки для AJAX
        add_action('wp_ajax_chrono_forge_get_available_slots', array($this, 'handle_ajax_get_available_slots'));
        add_action('wp_ajax_nopriv_chrono_forge_get_available_slots', array($this, 'handle_ajax_get_available_slots'));
        add_action('wp_ajax_chrono_forge_create_appointment', array($this, 'handle_ajax_create_appointment'));
        add_action('wp_ajax_nopriv_chrono_forge_create_appointment', array($this, 'handle_ajax_create_appointment'));
        add_action('wp_ajax_chrono_forge_get_services', array($this, 'handle_ajax_get_services'));
        add_action('wp_ajax_nopriv_chrono_forge_get_services', array($this, 'handle_ajax_get_services'));
        add_action('wp_ajax_chrono_forge_get_employees', array($this, 'handle_ajax_get_employees'));
        add_action('wp_ajax_nopriv_chrono_forge_get_employees', array($this, 'handle_ajax_get_employees'));
        add_action('wp_ajax_chrono_forge_cancel_appointment', array($this, 'handle_ajax_cancel_appointment'));
        add_action('wp_ajax_nopriv_chrono_forge_cancel_appointment', array($this, 'handle_ajax_cancel_appointment'));
        add_action('wp_ajax_chrono_forge_get_employee_schedule', array($this, 'handle_ajax_get_employee_schedule'));
        add_action('wp_ajax_chrono_forge_search_availability', array($this, 'handle_ajax_search_availability'));
        add_action('wp_ajax_nopriv_chrono_forge_search_availability', array($this, 'handle_ajax_search_availability'));
        
        // Хук для добавления ссылок на страницу плагинов
        add_filter('plugin_action_links_' . CHRONO_FORGE_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
    }

    /**
     * Загрузка зависимостей
     */
    private function load_dependencies() {
        // Load utility functions first (already loaded in main plugin file)
        // Other classes will be loaded in load_required_classes() method
    }

    /**
     * Инициализация компонентов плагина с улучшенной обработкой ошибок
     *
     * @since 1.0.0
     * @return void
     */
    private function init_components() {
        try {
            // Load all required class files first
            $this->load_required_classes();

            // Инициализация менеджера БД
            if (class_exists('ChronoForge_DB_Manager')) {
                $this->db_manager = new ChronoForge_DB_Manager();
                chrono_forge_safe_log('Database manager initialized successfully', 'info');
            } else {
                chrono_forge_safe_log('ChronoForge_DB_Manager class not found after loading', 'error');
                // Don't return here - continue with other components
            }

            // Инициализация AJAX-обработчика
            if (class_exists('ChronoForge_Ajax_Handler') && $this->db_manager) {
                try {
                    $this->ajax_handler = new ChronoForge_Ajax_Handler($this->db_manager);
                    chrono_forge_safe_log('AJAX handler initialized successfully', 'info');
                } catch (Exception $e) {
                    chrono_forge_safe_log('Error initializing AJAX handler: ' . $e->getMessage(), 'error');
                }
            } else {
                chrono_forge_safe_log('ChronoForge_Ajax_Handler class not found or DB manager unavailable', 'warning');
            }

            // Инициализация шорткодов
            if (class_exists('ChronoForge_Shortcodes') && $this->db_manager) {
                try {
                    $this->shortcodes = new ChronoForge_Shortcodes($this->db_manager);
                    chrono_forge_safe_log('Shortcodes initialized successfully', 'info');
                } catch (Exception $e) {
                    chrono_forge_safe_log('Error initializing shortcodes: ' . $e->getMessage(), 'error');
                }
            } else {
                chrono_forge_safe_log('ChronoForge_Shortcodes class not found or DB manager unavailable', 'warning');
            }

            // Инициализация менеджера платежей
            if (class_exists('ChronoForge_Payment_Manager') && $this->db_manager) {
                try {
                    $this->payment_manager = new ChronoForge_Payment_Manager($this->db_manager);
                    chrono_forge_safe_log('Payment manager initialized successfully', 'info');
                } catch (Exception $e) {
                    chrono_forge_safe_log('Error initializing payment manager: ' . $e->getMessage(), 'warning');
                }
            } else {
                chrono_forge_safe_log('ChronoForge_Payment_Manager class not found or DB manager unavailable', 'warning');
            }

            // Инициализация менеджера уведомлений
            if (class_exists('ChronoForge_Notification_Manager') && $this->db_manager) {
                try {
                    $this->notification_manager = new ChronoForge_Notification_Manager($this->db_manager);
                    chrono_forge_safe_log('Notification manager initialized successfully', 'info');
                } catch (Exception $e) {
                    chrono_forge_safe_log('Error initializing notification manager: ' . $e->getMessage(), 'warning');
                }
            } else {
                chrono_forge_safe_log('ChronoForge_Notification_Manager class not found or DB manager unavailable', 'warning');
            }

            // Инициализация интеграции с календарями
            if (class_exists('ChronoForge_Calendar_Integration') && $this->db_manager) {
                try {
                    $this->calendar_integration = new ChronoForge_Calendar_Integration($this->db_manager);
                    chrono_forge_safe_log('Calendar integration initialized successfully', 'info');
                } catch (Exception $e) {
                    chrono_forge_safe_log('Error initializing calendar integration: ' . $e->getMessage(), 'warning');
                }
            } else {
                chrono_forge_safe_log('ChronoForge_Calendar_Integration class not found or DB manager unavailable', 'warning');
            }

            // Инициализация админ-меню только в админке
            if (is_admin()) {
                if (class_exists('ChronoForge_Admin_Menu') && $this->db_manager) {
                    try {
                        $this->admin_menu = new ChronoForge_Admin_Menu($this->db_manager);
                        chrono_forge_safe_log('Admin menu initialized successfully', 'info');
                    } catch (Exception $e) {
                        chrono_forge_safe_log('Error initializing admin menu: ' . $e->getMessage(), 'error');
                    }
                } else {
                    chrono_forge_safe_log('ChronoForge_Admin_Menu class not found or DB manager unavailable', 'warning');
                }
            }

            // Skip system health check during initialization to avoid circular dependencies

        } catch (Exception $e) {
            chrono_forge_safe_log('Exception during component initialization: ' . $e->getMessage(), 'error');
            add_action('admin_notices', array($this, 'component_init_error_notice'));

            // Emergency mode - disable problematic components
            $this->enable_emergency_mode($e->getMessage());
        } catch (Error $e) {
            chrono_forge_safe_log('Fatal error during component initialization: ' . $e->getMessage(), 'error');
            add_action('admin_notices', array($this, 'component_init_error_notice'));

            // Emergency mode - disable problematic components
            $this->enable_emergency_mode($e->getMessage());
        }
    }

    /**
     * Load all required class files
     *
     * @since 1.0.0
     * @return void
     */
    private function load_required_classes() {
        $class_files = array(
            'class-chrono-forge-db-manager.php',
            'class-chrono-forge-ajax-handler.php',
            'class-chrono-forge-shortcodes.php',
            'class-chrono-forge-payment-manager.php',
            'class-chrono-forge-notification-manager.php',
            'class-chrono-forge-calendar-integration.php'
        );

        foreach ($class_files as $file) {
            $file_path = CHRONO_FORGE_PLUGIN_DIR . 'includes/' . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
                chrono_forge_safe_log("Loaded class file: {$file}", 'debug');
            } else {
                chrono_forge_safe_log("Class file not found: {$file}", 'warning');
            }
        }

        // Load admin menu class if in admin
        if (is_admin()) {
            $admin_file = CHRONO_FORGE_PLUGIN_DIR . 'admin/class-chrono-forge-admin-menu.php';
            if (file_exists($admin_file)) {
                require_once $admin_file;
                chrono_forge_safe_log("Loaded admin menu class", 'debug');
            } else {
                chrono_forge_safe_log("Admin menu class file not found", 'warning');
            }
        }
    }

    /**
     * Инициализация плагина
     */
    public function init() {
        // Проверка минимальных требований
        if (!$this->check_requirements()) {
            return;
        }
        
        // Дополнительная инициализация
        do_action('chrono_forge_init');
    }

    /**
     * Проверка минимальных требований
     * 
     * @return bool
     */
    private function check_requirements() {
        // Проверка версии PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }
        
        // Проверка версии WordPress
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            add_action('admin_notices', array($this, 'wp_version_notice'));
            return false;
        }
        
        return true;
    }

    /**
     * Загрузка файлов локализации
     */
    public function load_textdomain() {
        // Get language setting
        $settings = get_option('chrono_forge_settings', array());
        $plugin_language = $settings['plugin_language'] ?? 'auto';

        // Add filter to override locale if needed
        if ($plugin_language !== 'auto') {
            add_filter('locale', function($locale) use ($plugin_language) {
                // Only override for our plugin context
                if (doing_action('plugins_loaded') || is_admin()) {
                    return $plugin_language;
                }
                return $locale;
            }, 10, 1);

            // Also add plugin-specific locale filter
            add_filter('plugin_locale', function($locale, $domain) use ($plugin_language) {
                if ($domain === 'chrono-forge') {
                    return $plugin_language;
                }
                return $locale;
            }, 10, 2);
        }

        // Load the plugin textdomain
        $loaded = load_plugin_textdomain(
            'chrono-forge',
            false,
            dirname(CHRONO_FORGE_PLUGIN_BASENAME) . '/languages/'
        );

        // If the specific locale file doesn't exist, try to load it manually
        if (!$loaded && $plugin_language !== 'auto') {
            $mo_file = dirname(CHRONO_FORGE_PLUGIN_FILE) . '/languages/chrono-forge-' . $plugin_language . '.mo';
            if (file_exists($mo_file)) {
                load_textdomain('chrono-forge', $mo_file);
            }
        }
    }

    /**
     * Подключение скриптов и стилей для публичной части
     */
    public function enqueue_public_scripts() {
        // Стили
        wp_enqueue_style(
            'chrono-forge-public',
            CHRONO_FORGE_PLUGIN_URL . 'assets/css/public.css',
            array(),
            CHRONO_FORGE_VERSION
        );
        
        // Скрипты
        wp_enqueue_script(
            'chrono-forge-public',
            CHRONO_FORGE_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            CHRONO_FORGE_VERSION,
            true
        );
        
        // Локализация для AJAX
        wp_localize_script('chrono-forge-public', 'chronoForgeAjax', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chrono_forge_nonce'),
            'strings' => array(
                'loading' => __('Загрузка...', 'chrono-forge'),
                'error' => __('Произошла ошибка. Попробуйте еще раз.', 'chrono-forge'),
                'selectService' => __('Выберите услугу', 'chrono-forge'),
                'selectEmployee' => __('Выберите специалиста', 'chrono-forge'),
                'selectDate' => __('Выберите дату', 'chrono-forge'),
                'selectTime' => __('Выберите время', 'chrono-forge'),
                'noSlotsAvailable' => __('На выбранную дату нет свободных слотов', 'chrono-forge'),
            )
        ));
    }

    /**
     * Подключение скриптов и стилей для админ-панели
     */
    public function enqueue_admin_scripts($hook) {
        // Подключаем только на страницах плагина
        if (strpos($hook, 'chrono-forge') === false) {
            return;
        }
        
        // Стили
        wp_enqueue_style(
            'chrono-forge-admin',
            CHRONO_FORGE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CHRONO_FORGE_VERSION
        );
        
        // Скрипты
        wp_enqueue_script(
            'chrono-forge-admin',
            CHRONO_FORGE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker'),
            CHRONO_FORGE_VERSION,
            true
        );
        
        // Локализация для админки
        wp_localize_script('chrono-forge-admin', 'chronoForgeAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chrono_forge_nonce'),
        ));
    }

    /**
     * Обработка AJAX-запроса получения доступных слотов
     */
    public function handle_ajax_get_available_slots() {
        if ($this->ajax_handler) {
            $this->ajax_handler->get_available_slots();
        }
    }

    /**
     * Обработка AJAX-запроса создания записи
     */
    public function handle_ajax_create_appointment() {
        if ($this->ajax_handler) {
            $this->ajax_handler->create_appointment();
        }
    }

    /**
     * Обработка AJAX-запроса получения услуг
     */
    public function handle_ajax_get_services() {
        if ($this->ajax_handler) {
            $this->ajax_handler->get_services();
        }
    }

    /**
     * Обработка AJAX-запроса получения сотрудников
     */
    public function handle_ajax_get_employees() {
        if ($this->ajax_handler) {
            $this->ajax_handler->get_employees();
        }
    }

    /**
     * Обработка AJAX-запроса отмены записи
     */
    public function handle_ajax_cancel_appointment() {
        if ($this->ajax_handler) {
            $this->ajax_handler->cancel_appointment();
        }
    }

    /**
     * Обработка AJAX-запроса получения графика сотрудника
     */
    public function handle_ajax_get_employee_schedule() {
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_admin_nonce')) {
            wp_die(__('Ошибка безопасности', 'chrono-forge'));
        }

        $employee_id = intval($_POST['employee_id']);
        if (!$employee_id) {
            wp_send_json_error(__('Неверный ID сотрудника', 'chrono-forge'));
        }

        $schedule = $this->db_manager->get_employee_schedule($employee_id);
        wp_send_json_success($schedule);
    }

    /**
     * Обработка AJAX-запроса поиска доступности
     */
    public function handle_ajax_search_availability() {
        if ($this->ajax_handler) {
            $this->ajax_handler->search_availability();
        }
    }

    /**
     * Добавление ссылок на странице плагинов
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=chrono-forge-settings') . '">' . __('Настройки', 'chrono-forge') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Уведомление о несовместимой версии PHP
     */
    public function php_version_notice() {
        echo '<div class="notice notice-error"><p>';
        echo sprintf(
            __('ChronoForge требует PHP версии 7.4 или выше. Ваша версия: %s', 'chrono-forge'),
            PHP_VERSION
        );
        echo '</p></div>';
    }

    /**
     * Уведомление о несовместимой версии WordPress
     */
    public function wp_version_notice() {
        echo '<div class="notice notice-error"><p>';
        echo sprintf(
            __('ChronoForge требует WordPress версии 5.0 или выше. Ваша версия: %s', 'chrono-forge'),
            get_bloginfo('version')
        );
        echo '</p></div>';
    }

    /**
     * Проверка состояния системы
     *
     * @since 1.0.0
     * @return void
     */
    private function check_system_health() {
        // Only check system health if functions are available
        if (!function_exists('chrono_forge_check_system_limits')) {
            return;
        }

        try {
            $checks = chrono_forge_check_system_limits();

            foreach ($checks as $check_name => $check_data) {
                if ($check_data['status'] === 'critical') {
                    if (function_exists('chrono_forge_log')) {
                        chrono_forge_log("Critical system issue detected: {$check_name}", 'error', $check_data);
                    } else {
                        chrono_forge_safe_log("Critical system issue detected: {$check_name}", 'error');
                    }
                } elseif ($check_data['status'] === 'warning') {
                    if (function_exists('chrono_forge_log')) {
                        chrono_forge_log("System warning: {$check_name}", 'warning', $check_data);
                    } else {
                        chrono_forge_safe_log("System warning: {$check_name}", 'warning');
                    }
                }
            }
        } catch (Exception $e) {
            chrono_forge_safe_log("Error during system health check: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Уведомление об ошибке инициализации компонентов
     *
     * @since 1.0.0
     * @return void
     */
    public function component_init_error_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('ChronoForge: Произошла ошибка при инициализации компонентов плагина. Проверьте логи для получения подробной информации.', 'chrono-forge');
        echo '</p></div>';
    }

    /**
     * Уведомление об успешной инициализации (временное для отладки)
     *
     * @since 1.0.0
     * @return void
     */
    public function initialization_success_notice() {
        // Only show this notice on plugin pages or if there were previous errors
        if (isset($_GET['page']) && strpos($_GET['page'], 'chrono-forge') === 0) {
            $components_status = array(
                'DB Manager' => $this->db_manager !== null ? '✓' : '✗',
                'AJAX Handler' => $this->ajax_handler !== null ? '✓' : '✗',
                'Shortcodes' => $this->shortcodes !== null ? '✓' : '✗',
                'Admin Menu' => $this->admin_menu !== null ? '✓' : '✗'
            );

            echo '<div class="notice notice-success is-dismissible"><p>';
            echo '<strong>ChronoForge:</strong> ' . __('Плагин успешно инициализирован.', 'chrono-forge') . ' ';
            echo __('Компоненты:', 'chrono-forge') . ' ';
            foreach ($components_status as $component => $status) {
                echo $component . ': ' . $status . ' ';
            }
            echo '</p></div>';
        }
    }

    /**
     * Получить информацию о состоянии плагина
     *
     * @since 1.0.0
     * @return array Информация о состоянии
     */
    public function get_plugin_status() {
        try {
            $status = array(
                'version' => CHRONO_FORGE_VERSION,
                'db_manager' => $this->db_manager !== null,
                'ajax_handler' => $this->ajax_handler !== null,
                'shortcodes' => $this->shortcodes !== null,
                'payment_manager' => $this->payment_manager !== null,
                'notification_manager' => $this->notification_manager !== null,
                'calendar_integration' => $this->calendar_integration !== null,
                'admin_menu' => $this->admin_menu !== null,
            );

            // Safely add system checks if function exists
            if (function_exists('chrono_forge_check_system_limits')) {
                $status['system_checks'] = chrono_forge_check_system_limits();
            } else {
                $status['system_checks'] = array('status' => 'unavailable');
            }

            // Safely add performance info if function exists
            if (function_exists('chrono_forge_get_performance_info')) {
                $status['performance'] = chrono_forge_get_performance_info();
            } else {
                $status['performance'] = array('status' => 'unavailable');
            }

            return $status;

        } catch (Exception $e) {
            chrono_forge_safe_log("Error getting plugin status: " . $e->getMessage(), 'error');
            return array(
                'version' => CHRONO_FORGE_VERSION,
                'error' => 'Unable to retrieve full status',
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Безопасное получение компонента
     *
     * @since 1.0.0
     * @param string $component_name Имя компонента
     * @return object|null Компонент или null если не найден
     */
    public function get_component($component_name) {
        $components = array(
            'db_manager' => $this->db_manager,
            'ajax_handler' => $this->ajax_handler,
            'shortcodes' => $this->shortcodes,
            'payment_manager' => $this->payment_manager,
            'notification_manager' => $this->notification_manager,
            'calendar_integration' => $this->calendar_integration,
            'admin_menu' => $this->admin_menu
        );

        return isset($components[$component_name]) ? $components[$component_name] : null;
    }

    /**
     * Обработчик критических ошибок плагина
     *
     * @since 1.0.0
     * @param string $message Сообщение об ошибке
     * @param array $context Контекст ошибки
     * @return void
     */
    public function handle_critical_error($message, $context = array()) {
        chrono_forge_log($message, 'error', $context);

        // Отключаем проблемные компоненты
        if (isset($context['component'])) {
            $component = $context['component'];
            if (property_exists($this, $component)) {
                $this->$component = null;
                chrono_forge_log("Disabled component: {$component}", 'warning');
            }
        }

        // Показываем уведомление администратору
        add_action('admin_notices', function() use ($message) {
            echo '<div class="notice notice-error"><p>';
            echo sprintf(__('ChronoForge: %s', 'chrono-forge'), esc_html($message));
            echo '</p></div>';
        });
    }

    /**
     * Enable emergency mode when critical errors occur
     *
     * @since 1.0.0
     * @param string $error_message Error message
     * @return void
     */
    private function enable_emergency_mode($error_message) {
        // Set emergency mode flag
        update_option('chrono_forge_emergency_mode', true);
        update_option('chrono_forge_emergency_error', $error_message);
        update_option('chrono_forge_emergency_time', current_time('mysql'));

        // Disable problematic components
        $this->payment_manager = null;
        $this->notification_manager = null;
        $this->calendar_integration = null;

        chrono_forge_safe_log("Emergency mode enabled due to: {$error_message}", 'error');

        // Add emergency mode notice
        add_action('admin_notices', array($this, 'emergency_mode_notice'));
    }

    /**
     * Emergency mode admin notice
     *
     * @since 1.0.0
     * @return void
     */
    public function emergency_mode_notice() {
        $error_message = get_option('chrono_forge_emergency_error', 'Unknown error');
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>ChronoForge Emergency Mode:</strong> ';
        echo sprintf(__('Плагин работает в аварийном режиме из-за ошибки: %s', 'chrono-forge'), esc_html($error_message));
        echo ' <a href="#" onclick="chronoForgeDisableEmergencyMode()">' . __('Попробовать восстановить', 'chrono-forge') . '</a>';
        echo '</p></div>';

        // Add JavaScript for recovery
        echo '<script>
        function chronoForgeDisableEmergencyMode() {
            if (confirm("' . __('Попытаться выйти из аварийного режима? Это может вызвать повторную ошибку.', 'chrono-forge') . '")) {
                var data = {
                    action: "chrono_forge_disable_emergency_mode",
                    nonce: "' . wp_create_nonce('chrono_forge_emergency') . '"
                };
                jQuery.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert("' . __('Не удалось выйти из аварийного режима', 'chrono-forge') . '");
                    }
                });
            }
        }
        </script>';
    }

    /**
     * Check if plugin is in emergency mode
     *
     * @since 1.0.0
     * @return bool
     */
    public function is_emergency_mode() {
        return get_option('chrono_forge_emergency_mode', false);
    }

    /**
     * Disable emergency mode
     *
     * @since 1.0.0
     * @return bool
     */
    public function disable_emergency_mode() {
        delete_option('chrono_forge_emergency_mode');
        delete_option('chrono_forge_emergency_error');
        delete_option('chrono_forge_emergency_time');

        chrono_forge_safe_log("Emergency mode disabled", 'info');
        return true;
    }
}
