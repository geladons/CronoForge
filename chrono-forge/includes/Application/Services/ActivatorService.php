<?php
/**
 * Plugin Activator Service
 * 
 * @package ChronoForge\Application\Services
 */

namespace ChronoForge\Application\Services;

use ChronoForge\Infrastructure\Database\DatabaseManager;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activator Service class
 */
class ActivatorService
{
    /**
     * Database manager
     * 
     * @var DatabaseManager
     */
    private $database;

    /**
     * Constructor
     * 
     * @param DatabaseManager $database
     */
    public function __construct(DatabaseManager $database)
    {
        $this->database = $database;
    }

    /**
     * Activate the plugin
     */
    public function activate()
    {
        // Check requirements
        $this->checkRequirements();

        // Create database tables
        $this->createTables();

        // Set default options
        $this->setDefaultOptions();

        // Create default data
        $this->createDefaultData();

        // Set activation flag
        update_option('chrono_forge_activated', time());
        update_option('chrono_forge_version', CHRONO_FORGE_VERSION);

        // Log activation
        \ChronoForge\safe_log('Plugin activated successfully');
    }

    /**
     * Check system requirements
     * 
     * @throws \Exception
     */
    private function checkRequirements()
    {
        // Check PHP version
        if (version_compare(PHP_VERSION, CHRONO_FORGE_MIN_PHP_VERSION, '<')) {
            throw new \Exception(
                sprintf(
                    'ChronoForge requires PHP %s or higher. You are running PHP %s.',
                    CHRONO_FORGE_MIN_PHP_VERSION,
                    PHP_VERSION
                )
            );
        }

        // Check WordPress version
        global $wp_version;
        if (version_compare($wp_version, CHRONO_FORGE_MIN_WP_VERSION, '<')) {
            throw new \Exception(
                sprintf(
                    'ChronoForge requires WordPress %s or higher. You are running WordPress %s.',
                    CHRONO_FORGE_MIN_WP_VERSION,
                    $wp_version
                )
            );
        }

        // Check if required functions exist
        $required_functions = ['wp_create_nonce', 'wp_verify_nonce', 'current_user_can'];
        foreach ($required_functions as $function) {
            if (!function_exists($function)) {
                throw new \Exception("Required WordPress function '{$function}' not found.");
            }
        }
    }

    /**
     * Create database tables
     */
    private function createTables()
    {
        try {
            $this->database->createTables();
            \ChronoForge\safe_log('Database tables created successfully');
        } catch (\Exception $e) {
            \ChronoForge\safe_log('Error creating database tables: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Set default plugin options
     */
    private function setDefaultOptions()
    {
        $default_options = [
            'language' => 'auto',
            'date_format' => get_option('date_format', 'Y-m-d'),
            'time_format' => get_option('time_format', 'H:i'),
            'currency' => 'USD',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'default_appointment_duration' => 60,
            'booking_window_days' => 30,
            'min_booking_time' => 24, // hours
            'max_booking_time' => 720, // hours (30 days)
            'allow_cancellation' => true,
            'cancellation_window' => 24, // hours
            'email_notifications' => true,
            'sms_notifications' => false,
            'require_approval' => false,
            'allow_payments' => false,
            'payment_required' => false,
            'default_service_color' => '#1788FB',
            'working_hours_start' => '09:00',
            'working_hours_end' => '17:00',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'timezone' => wp_timezone_string(),
        ];

        foreach ($default_options as $option => $value) {
            if (get_option('chrono_forge_' . $option) === false) {
                update_option('chrono_forge_' . $option, $value);
            }
        }

        \ChronoForge\safe_log('Default options set successfully');
    }

    /**
     * Create default data
     */
    private function createDefaultData()
    {
        $this->createDefaultService();
        $this->createDefaultEmployee();
    }

    /**
     * Create default service
     */
    private function createDefaultService()
    {
        // Check if any services exist
        $existing_services = $this->database->getVar(
            "SELECT COUNT(*) FROM " . $this->database->getTable('services')
        );

        if ($existing_services == 0) {
            $service_data = [
                'name' => __('General Consultation', 'chrono-forge'),
                'description' => __('General consultation service', 'chrono-forge'),
                'duration' => 60,
                'price' => 50.00,
                'category' => __('General', 'chrono-forge'),
                'color' => '#1788FB',
                'capacity' => 1,
                'status' => 'active'
            ];

            $service_id = $this->database->insert('services', $service_data);
            
            if ($service_id) {
                \ChronoForge\safe_log('Default service created with ID: ' . $service_id);
            }
        }
    }

    /**
     * Create default employee
     */
    private function createDefaultEmployee()
    {
        // Check if any employees exist
        $existing_employees = $this->database->getVar(
            "SELECT COUNT(*) FROM " . $this->database->getTable('employees')
        );

        if ($existing_employees == 0) {
            // Get current user info
            $current_user = wp_get_current_user();
            
            if ($current_user->ID) {
                $employee_data = [
                    'wp_user_id' => $current_user->ID,
                    'first_name' => $current_user->first_name ?: 'Admin',
                    'last_name' => $current_user->last_name ?: 'User',
                    'email' => $current_user->user_email,
                    'description' => __('Default employee account', 'chrono-forge'),
                    'status' => 'active'
                ];

                $employee_id = $this->database->insert('employees', $employee_data);
                
                if ($employee_id) {
                    \ChronoForge\safe_log('Default employee created with ID: ' . $employee_id);
                }
            }
        }
    }

    /**
     * Schedule activation tasks
     */
    private function scheduleActivationTasks()
    {
        // Schedule any recurring tasks here
        if (!wp_next_scheduled('chrono_forge_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'chrono_forge_daily_cleanup');
        }
    }
}
