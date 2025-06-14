<?php
/**
 * Admin Menu Manager
 * 
 * @package ChronoForge\Admin
 */

namespace ChronoForge\Admin;

use ChronoForge\Infrastructure\Container;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Menu Manager class
 */
class MenuManager
{
    /**
     * Container instance
     * 
     * @var Container
     */
    private $container;

    /**
     * Constructor
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Initialize admin menu
     */
    public function init()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->addMainMenu();
        $this->addSubMenus();
    }

    /**
     * Add main menu page
     */
    private function addMainMenu()
    {
        add_menu_page(
            __('ChronoForge', 'chrono-forge'),
            __('ChronoForge', 'chrono-forge'),
            'manage_options',
            'chrono-forge',
            [$this, 'renderDashboard'],
            'dashicons-calendar-alt',
            30
        );
    }

    /**
     * Add submenu pages
     */
    private function addSubMenus()
    {
        // Dashboard (rename the first submenu)
        add_submenu_page(
            'chrono-forge',
            __('Dashboard', 'chrono-forge'),
            __('Dashboard', 'chrono-forge'),
            'manage_options',
            'chrono-forge',
            [$this, 'renderDashboard']
        );

        // Calendar
        add_submenu_page(
            'chrono-forge',
            __('Calendar', 'chrono-forge'),
            __('Calendar', 'chrono-forge'),
            'manage_options',
            'chrono-forge-calendar',
            [$this, 'renderCalendar']
        );

        // Appointments
        add_submenu_page(
            'chrono-forge',
            __('Appointments', 'chrono-forge'),
            __('Appointments', 'chrono-forge'),
            'manage_options',
            'chrono-forge-appointments',
            [$this, 'renderAppointments']
        );

        // Services
        add_submenu_page(
            'chrono-forge',
            __('Services', 'chrono-forge'),
            __('Services', 'chrono-forge'),
            'manage_options',
            'chrono-forge-services',
            [$this, 'renderServices']
        );

        // Employees
        add_submenu_page(
            'chrono-forge',
            __('Employees', 'chrono-forge'),
            __('Employees', 'chrono-forge'),
            'manage_options',
            'chrono-forge-employees',
            [$this, 'renderEmployees']
        );

        // Customers
        add_submenu_page(
            'chrono-forge',
            __('Customers', 'chrono-forge'),
            __('Customers', 'chrono-forge'),
            'manage_options',
            'chrono-forge-customers',
            [$this, 'renderCustomers']
        );

        // Settings
        add_submenu_page(
            'chrono-forge',
            __('Settings', 'chrono-forge'),
            __('Settings', 'chrono-forge'),
            'manage_options',
            'chrono-forge-settings',
            [$this, 'renderSettings']
        );
    }

    /**
     * Render dashboard page
     */
    public function renderDashboard()
    {
        $controller = $this->container->get('admin.controller.dashboard');
        $controller->index();
    }

    /**
     * Render calendar page
     */
    public function renderCalendar()
    {
        $controller = $this->container->get('admin.controller.dashboard');
        $controller->calendar();
    }

    /**
     * Render appointments page
     */
    public function renderAppointments()
    {
        $controller = $this->container->get('admin.controller.appointments');
        $controller->index();
    }

    /**
     * Render services page
     */
    public function renderServices()
    {
        $controller = $this->container->get('admin.controller.services');
        $controller->index();
    }

    /**
     * Render employees page
     */
    public function renderEmployees()
    {
        $controller = $this->container->get('admin.controller.employees');
        $controller->index();
    }

    /**
     * Render customers page
     */
    public function renderCustomers()
    {
        $controller = $this->container->get('admin.controller.customers');
        $controller->index();
    }

    /**
     * Render settings page
     */
    public function renderSettings()
    {
        $controller = $this->container->get('admin.controller.settings');
        $controller->index();
    }
}
