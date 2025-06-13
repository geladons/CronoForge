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
        // Подключение основных классов
        require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-db-manager.php';
        require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-ajax-handler.php';
        require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-shortcodes.php';
        require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-payment-manager.php';
        require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-notification-manager.php';
        require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/class-chrono-forge-calendar-integration.php';
        require_once CHRONO_FORGE_PLUGIN_DIR . 'includes/utils/functions.php';
        
        // Подключение админ-классов только в админке
        if (is_admin()) {
            require_once CHRONO_FORGE_PLUGIN_DIR . 'admin/class-chrono-forge-admin-menu.php';
        }
    }

    /**
     * Инициализация компонентов плагина
     */
    private function init_components() {
        // Инициализация менеджера БД
        $this->db_manager = new ChronoForge_DB_Manager();
        
        // Инициализация AJAX-обработчика
        $this->ajax_handler = new ChronoForge_Ajax_Handler($this->db_manager);
        
        // Инициализация шорткодов
        $this->shortcodes = new ChronoForge_Shortcodes($this->db_manager);

        // Инициализация менеджера платежей
        $this->payment_manager = new ChronoForge_Payment_Manager($this->db_manager);

        // Инициализация менеджера уведомлений
        $this->notification_manager = new ChronoForge_Notification_Manager($this->db_manager);

        // Инициализация интеграции с календарями
        $this->calendar_integration = new ChronoForge_Calendar_Integration($this->db_manager);
        
        // Инициализация админ-меню только в админке
        if (is_admin()) {
            $this->admin_menu = new ChronoForge_Admin_Menu($this->db_manager);
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

        // If auto, use WordPress locale, otherwise use selected language
        if ($plugin_language !== 'auto') {
            add_filter('plugin_locale', function($locale, $domain) use ($plugin_language) {
                if ($domain === 'chrono-forge') {
                    return $plugin_language;
                }
                return $locale;
            }, 10, 2);
        }

        load_plugin_textdomain(
            'chrono-forge',
            false,
            dirname(CHRONO_FORGE_PLUGIN_BASENAME) . '/languages/'
        );
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
            'nonce' => wp_create_nonce('chrono_forge_admin_nonce'),
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
}
