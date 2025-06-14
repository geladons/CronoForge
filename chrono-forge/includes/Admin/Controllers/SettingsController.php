<?php
/**
 * Settings Controller
 * 
 * @package ChronoForge\Admin\Controllers
 */

namespace ChronoForge\Admin\Controllers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Controller class
 */
class SettingsController extends BaseController
{
    /**
     * Settings index page
     */
    public function index()
    {
        if (!$this->userCan()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'chrono-forge'));
        }

        // Handle form submission
        if ($_POST && $this->verifyNonce('settings')) {
            $this->saveSettings();
        }

        $this->enqueueAssets([
            'settings' => 'settings.js'
        ], [
            'settings' => 'settings.css'
        ]);

        $data = [
            'settings' => $this->getSettings(),
            'tabs' => $this->getSettingsTabs()
        ];

        $this->render('settings/index', $data);
    }

    /**
     * Get settings data
     * 
     * @return array
     */
    public function get()
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        $settings = $this->getSettings();
        $this->sendJson($settings);
    }

    /**
     * Update settings
     */
    public function update()
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        if (!$this->verifyNonce('settings')) {
            $this->sendJson(['message' => 'Security check failed'], false);
        }

        try {
            $this->saveSettings();
            $this->sendJson(['message' => 'Settings saved successfully']);
        } catch (\Exception $e) {
            $this->sendJson(['message' => $e->getMessage()], false);
        }
    }

    /**
     * Get all settings
     * 
     * @return array
     */
    private function getSettings()
    {
        $defaults = [
            'general' => [
                'language' => 'auto',
                'date_format' => get_option('date_format', 'Y-m-d'),
                'time_format' => get_option('time_format', 'H:i'),
                'timezone' => wp_timezone_string(),
                'currency' => 'USD',
                'currency_symbol' => '$',
                'currency_position' => 'before'
            ],
            'booking' => [
                'default_appointment_duration' => 60,
                'booking_window_days' => 30,
                'min_booking_time' => 24,
                'max_booking_time' => 720,
                'allow_cancellation' => true,
                'cancellation_window' => 24,
                'require_approval' => false,
                'allow_payments' => false,
                'payment_required' => false
            ],
            'working_hours' => [
                'start_time' => '09:00',
                'end_time' => '17:00',
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
            ],
            'notifications' => [
                'email_notifications' => true,
                'sms_notifications' => false,
                'admin_email' => get_option('admin_email'),
                'notification_templates' => []
            ],
            'appearance' => [
                'default_service_color' => '#1788FB',
                'calendar_view' => 'month',
                'show_employee_avatars' => true,
                'custom_css' => ''
            ]
        ];

        $settings = [];
        foreach ($defaults as $section => $section_defaults) {
            foreach ($section_defaults as $key => $default) {
                $option_name = $section . '_' . $key;
                $settings[$section][$key] = \ChronoForge\get_option($option_name, $default);
            }
        }

        return $settings;
    }

    /**
     * Save settings
     */
    private function saveSettings()
    {
        $settings = $this->getInput('settings', 'array', []);
        
        if (empty($settings)) {
            throw new \Exception(__('No settings data provided.', 'chrono-forge'));
        }

        $errors = $this->validateSettings($settings);
        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }

        // Save each setting
        foreach ($settings as $section => $section_settings) {
            foreach ($section_settings as $key => $value) {
                $option_name = $section . '_' . $key;
                \ChronoForge\update_option($option_name, $value);
            }
        }

        // Clear any relevant caches
        $this->clearSettingsCache();

        do_action('chrono_forge_settings_saved', $settings);
    }

    /**
     * Validate settings
     * 
     * @param array $settings
     * @return array
     */
    private function validateSettings($settings)
    {
        $errors = [];

        // Validate general settings
        if (isset($settings['general'])) {
            $general = $settings['general'];
            
            if (isset($general['currency']) && !in_array($general['currency'], $this->getSupportedCurrencies())) {
                $errors[] = __('Invalid currency selected.', 'chrono-forge');
            }
            
            if (isset($general['timezone']) && !in_array($general['timezone'], timezone_identifiers_list())) {
                $errors[] = __('Invalid timezone selected.', 'chrono-forge');
            }
        }

        // Validate booking settings
        if (isset($settings['booking'])) {
            $booking = $settings['booking'];
            
            if (isset($booking['default_appointment_duration']) && ($booking['default_appointment_duration'] < 15 || $booking['default_appointment_duration'] > 480)) {
                $errors[] = __('Default appointment duration must be between 15 and 480 minutes.', 'chrono-forge');
            }
            
            if (isset($booking['min_booking_time']) && $booking['min_booking_time'] < 0) {
                $errors[] = __('Minimum booking time cannot be negative.', 'chrono-forge');
            }
        }

        // Validate working hours
        if (isset($settings['working_hours'])) {
            $hours = $settings['working_hours'];
            
            if (isset($hours['start_time'], $hours['end_time'])) {
                $start = strtotime($hours['start_time']);
                $end = strtotime($hours['end_time']);
                
                if ($start >= $end) {
                    $errors[] = __('End time must be after start time.', 'chrono-forge');
                }
            }
        }

        return $errors;
    }

    /**
     * Get settings tabs
     * 
     * @return array
     */
    private function getSettingsTabs()
    {
        return [
            'general' => [
                'title' => __('General', 'chrono-forge'),
                'icon' => 'admin-generic'
            ],
            'booking' => [
                'title' => __('Booking', 'chrono-forge'),
                'icon' => 'calendar-alt'
            ],
            'working_hours' => [
                'title' => __('Working Hours', 'chrono-forge'),
                'icon' => 'clock'
            ],
            'notifications' => [
                'title' => __('Notifications', 'chrono-forge'),
                'icon' => 'email'
            ],
            'appearance' => [
                'title' => __('Appearance', 'chrono-forge'),
                'icon' => 'admin-appearance'
            ]
        ];
    }

    /**
     * Get supported currencies
     * 
     * @return array
     */
    private function getSupportedCurrencies()
    {
        return [
            'USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'SEK', 'NZD',
            'MXN', 'SGD', 'HKD', 'NOK', 'TRY', 'RUB', 'INR', 'BRL', 'ZAR', 'KRW'
        ];
    }

    /**
     * Clear settings cache
     */
    private function clearSettingsCache()
    {
        // Clear any transients or cache related to settings
        delete_transient('chrono_forge_settings_cache');
        
        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
}
