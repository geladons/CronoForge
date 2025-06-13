<?php
/**
 * Класс для управления шорткодами плагина ChronoForge
 * 
 * Этот класс регистрирует и обрабатывает все шорткоды плагина
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}

class ChronoForge_Shortcodes {

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
        $this->init_shortcodes();
    }

    /**
     * Инициализация шорткодов
     */
    private function init_shortcodes() {
        add_shortcode('chrono-forge-booking', array($this, 'booking_form_shortcode'));
        add_shortcode('chrono-forge-customer-panel', array($this, 'customer_panel_shortcode'));
        add_shortcode('chrono-forge-services', array($this, 'services_list_shortcode'));
        add_shortcode('chrono-forge-employees', array($this, 'employees_list_shortcode'));
        add_shortcode('chrono-forge-search', array($this, 'search_shortcode'));
        add_shortcode('chrono-forge-catalog', array($this, 'catalog_shortcode'));
    }

    /**
     * Шорткод формы бронирования
     * 
     * @param array $atts Атрибуты шорткода
     * @return string HTML-код формы
     */
    public function booking_form_shortcode($atts) {
        // Параметры по умолчанию
        $atts = shortcode_atts(array(
            'service' => '',
            'employee' => '',
            'category' => '',
            'show_categories' => 'true'
        ), $atts, 'chrono-forge-booking');

        // Начинаем буферизацию вывода
        ob_start();

        // Подключаем шаблон формы бронирования
        $this->load_booking_form_template($atts);

        // Возвращаем содержимое буфера
        return ob_get_clean();
    }

    /**
     * Шорткод личного кабинета клиента
     * 
     * @param array $atts Атрибуты шорткода
     * @return string HTML-код личного кабинета
     */
    public function customer_panel_shortcode($atts) {
        // Проверяем, авторизован ли пользователь
        if (!is_user_logged_in()) {
            return '<p>' . __('Для доступа к личному кабинету необходимо войти в систему.', 'chrono-forge') . '</p>';
        }

        // Параметры по умолчанию
        $atts = shortcode_atts(array(
            'show_upcoming' => 'true',
            'show_past' => 'true',
            'limit' => '10'
        ), $atts, 'chrono-forge-customer-panel');

        // Начинаем буферизацию вывода
        ob_start();

        // Подключаем шаблон личного кабинета
        $this->load_customer_panel_template($atts);

        // Возвращаем содержимое буфера
        return ob_get_clean();
    }

    /**
     * Загрузка шаблона формы бронирования
     * 
     * @param array $atts Атрибуты шорткода
     */
    private function load_booking_form_template($atts) {
        // Получаем данные для формы
        $categories = array();
        $services = array();
        $employees = array();

        // Если указана конкретная категория
        if (!empty($atts['category'])) {
            $category_id = intval($atts['category']);
            $services = $this->db_manager->get_all_services(array('category_id' => $category_id));
        }
        // Если указана конкретная услуга
        elseif (!empty($atts['service'])) {
            $service_id = intval($atts['service']);
            $service = $this->db_manager->get_service($service_id);
            if ($service) {
                $services = array($service);
                $employees = $this->db_manager->get_all_employees(array('service_id' => $service_id));
            }
        }
        // Если указан конкретный сотрудник
        elseif (!empty($atts['employee'])) {
            $employee_id = intval($atts['employee']);
            $employee = $this->db_manager->get_employee($employee_id);
            if ($employee) {
                $employees = array($employee);
                $services = $this->db_manager->get_employee_services($employee_id);
            }
        }
        // Загружаем все данные
        else {
            if ($atts['show_categories'] === 'true') {
                $categories = $this->db_manager->get_all_categories();
            }
            $services = $this->db_manager->get_all_services();
            $employees = $this->db_manager->get_all_employees();
        }

        // Подключаем шаблон
        include CHRONO_FORGE_PLUGIN_DIR . 'public/views/view-booking-form.php';
    }

    /**
     * Загрузка шаблона личного кабинета клиента
     * 
     * @param array $atts Атрибуты шорткода
     */
    private function load_customer_panel_template($atts) {
        // Получаем текущего пользователя
        $current_user = wp_get_current_user();
        
        // Ищем клиента по email пользователя
        $customer = $this->db_manager->get_customer_by_email($current_user->user_email);
        
        if (!$customer) {
            echo '<p>' . __('Клиент не найден в системе.', 'chrono-forge') . '</p>';
            return;
        }

        // Получаем записи клиента
        $upcoming_appointments = array();
        $past_appointments = array();

        if ($atts['show_upcoming'] === 'true') {
            $upcoming_appointments = $this->db_manager->get_all_appointments(array(
                'customer_id' => $customer->id,
                'date_from' => date('Y-m-d'),
                'status' => 'confirmed'
            ));
        }

        if ($atts['show_past'] === 'true') {
            $past_appointments = $this->db_manager->get_all_appointments(array(
                'customer_id' => $customer->id,
                'date_to' => date('Y-m-d', strtotime('-1 day'))
            ));
        }

        // Подключаем шаблон
        include CHRONO_FORGE_PLUGIN_DIR . 'public/views/view-customer-panel.php';
    }

    /**
     * Получить HTML для выбора категории
     * 
     * @param array $categories
     * @param string $selected_id
     * @return string
     */
    public function get_categories_html($categories, $selected_id = '') {
        if (empty($categories)) {
            return '';
        }

        $html = '<div class="cf-step cf-step-category">';
        $html .= '<h3>' . __('Выберите категорию', 'chrono-forge') . '</h3>';
        $html .= '<div class="cf-categories-grid">';

        foreach ($categories as $category) {
            $selected_class = ($selected_id == $category->id) ? ' selected' : '';
            $html .= sprintf(
                '<div class="cf-category-item%s" data-category-id="%d" style="border-color: %s;">
                    <h4>%s</h4>
                    <p>%s</p>
                </div>',
                $selected_class,
                $category->id,
                $category->color,
                esc_html($category->name),
                esc_html($category->description)
            );
        }

        $html .= '</div></div>';

        return $html;
    }

    /**
     * Получить HTML для выбора услуги
     * 
     * @param array $services
     * @param string $selected_id
     * @return string
     */
    public function get_services_html($services, $selected_id = '') {
        if (empty($services)) {
            return '<p>' . __('Услуги не найдены.', 'chrono-forge') . '</p>';
        }

        $html = '<div class="cf-step cf-step-service">';
        $html .= '<h3>' . __('Выберите услугу', 'chrono-forge') . '</h3>';
        $html .= '<div class="cf-services-list">';

        foreach ($services as $service) {
            $selected_class = ($selected_id == $service->id) ? ' selected' : '';
            $price_html = $service->price > 0 ? '<span class="cf-service-price">' . number_format($service->price, 2) . ' ₽</span>' : '';
            
            $html .= sprintf(
                '<div class="cf-service-item%s" data-service-id="%d" data-duration="%d" data-price="%.2f">
                    <div class="cf-service-info">
                        <h4>%s</h4>
                        <p>%s</p>
                        <div class="cf-service-meta">
                            <span class="cf-service-duration">%d мин.</span>
                            %s
                        </div>
                    </div>
                </div>',
                $selected_class,
                $service->id,
                $service->duration,
                $service->price,
                esc_html($service->name),
                esc_html($service->description),
                $service->duration,
                $price_html
            );
        }

        $html .= '</div></div>';

        return $html;
    }

    /**
     * Получить HTML для выбора сотрудника
     * 
     * @param array $employees
     * @param string $selected_id
     * @return string
     */
    public function get_employees_html($employees, $selected_id = '') {
        if (empty($employees)) {
            return '<p>' . __('Сотрудники не найдены.', 'chrono-forge') . '</p>';
        }

        $html = '<div class="cf-step cf-step-employee">';
        $html .= '<h3>' . __('Выберите специалиста', 'chrono-forge') . '</h3>';
        $html .= '<div class="cf-employees-grid">';

        foreach ($employees as $employee) {
            $selected_class = ($selected_id == $employee->id) ? ' selected' : '';
            $photo_html = !empty($employee->photo) ? 
                '<img src="' . esc_url($employee->photo) . '" alt="' . esc_attr($employee->name) . '">' :
                '<div class="cf-employee-avatar">' . substr($employee->name, 0, 1) . '</div>';
            
            $html .= sprintf(
                '<div class="cf-employee-item%s" data-employee-id="%d">
                    <div class="cf-employee-photo">%s</div>
                    <div class="cf-employee-info">
                        <h4>%s</h4>
                        <p>%s</p>
                    </div>
                </div>',
                $selected_class,
                $employee->id,
                $photo_html,
                esc_html($employee->name),
                esc_html($employee->description)
            );
        }

        $html .= '</div></div>';

        return $html;
    }

    /**
     * Шорткод списка услуг
     *
     * @param array $atts Атрибуты шорткода
     * @return string HTML-код списка услуг
     */
    public function services_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'columns' => '3',
            'show_price' => 'true',
            'show_duration' => 'true',
            'show_description' => 'true',
            'show_book_button' => 'true'
        ), $atts, 'chrono-forge-services');

        $args = array();
        if (!empty($atts['category'])) {
            $args['category_id'] = intval($atts['category']);
        }

        $services = $this->db_manager->get_all_services($args);

        ob_start();
        include CHRONO_FORGE_PLUGIN_DIR . 'public/views/view-services-list.php';
        return ob_get_clean();
    }

    /**
     * Шорткод списка сотрудников
     *
     * @param array $atts Атрибуты шорткода
     * @return string HTML-код списка сотрудников
     */
    public function employees_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'service' => '',
            'columns' => '3',
            'show_description' => 'true',
            'show_services' => 'true',
            'show_book_button' => 'true'
        ), $atts, 'chrono-forge-employees');

        $args = array();
        if (!empty($atts['service'])) {
            $args['service_id'] = intval($atts['service']);
        }

        $employees = $this->db_manager->get_all_employees($args);

        ob_start();
        include CHRONO_FORGE_PLUGIN_DIR . 'public/views/view-employees-list.php';
        return ob_get_clean();
    }

    /**
     * Шорткод поиска
     *
     * @param array $atts Атрибуты шорткода
     * @return string HTML-код формы поиска
     */
    public function search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_filters' => 'true',
            'show_date_range' => 'true',
            'show_any_employee' => 'true'
        ), $atts, 'chrono-forge-search');

        $categories = $this->db_manager->get_all_categories();
        $services = $this->db_manager->get_all_services();
        $employees = $this->db_manager->get_all_employees();

        ob_start();
        include CHRONO_FORGE_PLUGIN_DIR . 'public/views/view-search-form.php';
        return ob_get_clean();
    }

    /**
     * Шорткод каталога услуг
     *
     * @param array $atts Атрибуты шорткода
     * @return string HTML-код каталога
     */
    public function catalog_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_categories' => 'true',
            'show_filters' => 'true',
            'layout' => 'grid'
        ), $atts, 'chrono-forge-catalog');

        $categories = $this->db_manager->get_all_categories();
        $services = $this->db_manager->get_all_services();

        ob_start();
        include CHRONO_FORGE_PLUGIN_DIR . 'public/views/view-catalog.php';
        return ob_get_clean();
    }
}
