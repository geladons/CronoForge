<?php
/**
 * Calendar Integration Manager Class
 * 
 * Handles Google Calendar and Outlook Calendar integrations
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ChronoForge_Calendar_Integration {
    
    private $db_manager;
    private $settings;
    
    public function __construct($db_manager) {
        $this->db_manager = $db_manager;
        $this->settings = get_option('chrono_forge_settings', array());
        
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_chrono_forge_google_auth', array($this, 'handle_google_auth'));
        add_action('wp_ajax_chrono_forge_outlook_auth', array($this, 'handle_outlook_auth'));
        add_action('wp_ajax_chrono_forge_sync_calendar', array($this, 'sync_calendar'));
        
        // Hook into appointment actions
        add_action('chrono_forge_appointment_created', array($this, 'sync_appointment_to_calendars'));
        add_action('chrono_forge_appointment_updated', array($this, 'sync_appointment_to_calendars'));
        add_action('chrono_forge_appointment_cancelled', array($this, 'remove_appointment_from_calendars'));
    }
    
    /**
     * Handle Google Calendar OAuth
     */
    public function handle_google_auth() {
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_nonce')) {
            wp_die(__('Ошибка безопасности', 'chrono-forge'));
        }
        
        $google_settings = $this->settings['google_calendar'] ?? array();
        
        if (empty($google_settings['client_id']) || empty($google_settings['client_secret'])) {
            wp_send_json_error(__('Настройки Google Calendar не заполнены', 'chrono-forge'));
        }
        
        // Generate OAuth URL
        $auth_url = $this->get_google_auth_url();
        
        wp_send_json_success(array(
            'auth_url' => $auth_url,
            'message' => __('Перенаправление на авторизацию Google', 'chrono-forge')
        ));
    }
    
    /**
     * Handle Outlook Calendar OAuth
     */
    public function handle_outlook_auth() {
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_nonce')) {
            wp_die(__('Ошибка безопасности', 'chrono-forge'));
        }
        
        $outlook_settings = $this->settings['outlook_calendar'] ?? array();
        
        if (empty($outlook_settings['client_id']) || empty($outlook_settings['client_secret'])) {
            wp_send_json_error(__('Настройки Outlook Calendar не заполнены', 'chrono-forge'));
        }
        
        // Generate OAuth URL
        $auth_url = $this->get_outlook_auth_url();
        
        wp_send_json_success(array(
            'auth_url' => $auth_url,
            'message' => __('Перенаправление на авторизацию Microsoft', 'chrono-forge')
        ));
    }
    
    /**
     * Sync calendar manually
     */
    public function sync_calendar() {
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_nonce')) {
            wp_die(__('Ошибка безопасности', 'chrono-forge'));
        }
        
        $calendar_type = sanitize_text_field($_POST['calendar_type']);
        
        $result = false;
        
        switch ($calendar_type) {
            case 'google':
                $result = $this->sync_google_calendar();
                break;
            case 'outlook':
                $result = $this->sync_outlook_calendar();
                break;
        }
        
        if ($result) {
            wp_send_json_success(__('Календарь синхронизирован', 'chrono-forge'));
        } else {
            wp_send_json_error(__('Ошибка синхронизации календаря', 'chrono-forge'));
        }
    }
    
    /**
     * Sync appointment to external calendars
     */
    public function sync_appointment_to_calendars($appointment_id) {
        $appointment = $this->db_manager->get_appointment($appointment_id);
        if (!$appointment) return;
        
        // Sync to Google Calendar
        if (!empty($this->settings['google_calendar']['enabled'])) {
            $this->create_google_calendar_event($appointment);
        }
        
        // Sync to Outlook Calendar
        if (!empty($this->settings['outlook_calendar']['enabled'])) {
            $this->create_outlook_calendar_event($appointment);
        }
    }
    
    /**
     * Remove appointment from external calendars
     */
    public function remove_appointment_from_calendars($appointment_id) {
        $appointment = $this->db_manager->get_appointment($appointment_id);
        if (!$appointment) return;
        
        // Remove from Google Calendar
        if (!empty($this->settings['google_calendar']['enabled']) && !empty($appointment->google_event_id)) {
            $this->delete_google_calendar_event($appointment->google_event_id);
        }
        
        // Remove from Outlook Calendar
        if (!empty($this->settings['outlook_calendar']['enabled']) && !empty($appointment->outlook_event_id)) {
            $this->delete_outlook_calendar_event($appointment->outlook_event_id);
        }
    }
    
    /**
     * Get Google OAuth URL
     */
    private function get_google_auth_url() {
        $google_settings = $this->settings['google_calendar'] ?? array();
        
        $params = array(
            'client_id' => $google_settings['client_id'],
            'redirect_uri' => admin_url('admin-ajax.php?action=chrono_forge_google_callback'),
            'scope' => 'https://www.googleapis.com/auth/calendar',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent'
        );
        
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
    
    /**
     * Get Outlook OAuth URL
     */
    private function get_outlook_auth_url() {
        $outlook_settings = $this->settings['outlook_calendar'] ?? array();
        
        $params = array(
            'client_id' => $outlook_settings['client_id'],
            'redirect_uri' => admin_url('admin-ajax.php?action=chrono_forge_outlook_callback'),
            'scope' => 'https://graph.microsoft.com/calendars.readwrite offline_access',
            'response_type' => 'code',
            'response_mode' => 'query'
        );
        
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?' . http_build_query($params);
    }
    
    /**
     * Sync Google Calendar
     */
    private function sync_google_calendar() {
        $google_settings = $this->settings['google_calendar'] ?? array();
        
        if (empty($google_settings['access_token'])) {
            return false;
        }
        
        // Get upcoming appointments
        $appointments = $this->db_manager->get_all_appointments(array(
            'date_from' => date('Y-m-d'),
            'status' => array('confirmed', 'pending')
        ));
        
        foreach ($appointments as $appointment) {
            if (empty($appointment->google_event_id)) {
                $this->create_google_calendar_event($appointment);
            }
        }
        
        return true;
    }
    
    /**
     * Sync Outlook Calendar
     */
    private function sync_outlook_calendar() {
        $outlook_settings = $this->settings['outlook_calendar'] ?? array();
        
        if (empty($outlook_settings['access_token'])) {
            return false;
        }
        
        // Get upcoming appointments
        $appointments = $this->db_manager->get_all_appointments(array(
            'date_from' => date('Y-m-d'),
            'status' => array('confirmed', 'pending')
        ));
        
        foreach ($appointments as $appointment) {
            if (empty($appointment->outlook_event_id)) {
                $this->create_outlook_calendar_event($appointment);
            }
        }
        
        return true;
    }
    
    /**
     * Create Google Calendar event
     */
    private function create_google_calendar_event($appointment) {
        $google_settings = $this->settings['google_calendar'] ?? array();
        
        if (empty($google_settings['access_token'])) {
            return false;
        }
        
        $start_datetime = $appointment->appointment_date . 'T' . $appointment->appointment_time;
        $end_datetime = $appointment->appointment_date . 'T' . $appointment->end_time;
        
        $event_data = array(
            'summary' => $appointment->service_name . ' - ' . $appointment->customer_first_name . ' ' . $appointment->customer_last_name,
            'description' => sprintf(
                "Клиент: %s\nТелефон: %s\nEmail: %s\nКомментарий: %s",
                $appointment->customer_first_name . ' ' . $appointment->customer_last_name,
                $appointment->customer_phone,
                $appointment->customer_email,
                $appointment->notes
            ),
            'start' => array(
                'dateTime' => $start_datetime,
                'timeZone' => wp_timezone_string()
            ),
            'end' => array(
                'dateTime' => $end_datetime,
                'timeZone' => wp_timezone_string()
            ),
            'attendees' => array(
                array(
                    'email' => $appointment->customer_email,
                    'displayName' => $appointment->customer_first_name . ' ' . $appointment->customer_last_name
                )
            )
        );
        
        $response = wp_remote_post('https://www.googleapis.com/calendar/v3/calendars/primary/events', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $google_settings['access_token'],
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($event_data)
        ));
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!empty($data['id'])) {
                // Save Google event ID to appointment
                $this->db_manager->update_appointment($appointment->id, array(
                    'google_event_id' => $data['id']
                ));
                
                return $data['id'];
            }
        }
        
        return false;
    }
    
    /**
     * Create Outlook Calendar event
     */
    private function create_outlook_calendar_event($appointment) {
        $outlook_settings = $this->settings['outlook_calendar'] ?? array();
        
        if (empty($outlook_settings['access_token'])) {
            return false;
        }
        
        $start_datetime = $appointment->appointment_date . 'T' . $appointment->appointment_time . ':00';
        $end_datetime = $appointment->appointment_date . 'T' . $appointment->end_time . ':00';
        
        $event_data = array(
            'subject' => $appointment->service_name . ' - ' . $appointment->customer_first_name . ' ' . $appointment->customer_last_name,
            'body' => array(
                'contentType' => 'text',
                'content' => sprintf(
                    "Клиент: %s\nТелефон: %s\nEmail: %s\nКомментарий: %s",
                    $appointment->customer_first_name . ' ' . $appointment->customer_last_name,
                    $appointment->customer_phone,
                    $appointment->customer_email,
                    $appointment->notes
                )
            ),
            'start' => array(
                'dateTime' => $start_datetime,
                'timeZone' => wp_timezone_string()
            ),
            'end' => array(
                'dateTime' => $end_datetime,
                'timeZone' => wp_timezone_string()
            ),
            'attendees' => array(
                array(
                    'emailAddress' => array(
                        'address' => $appointment->customer_email,
                        'name' => $appointment->customer_first_name . ' ' . $appointment->customer_last_name
                    )
                )
            )
        );
        
        $response = wp_remote_post('https://graph.microsoft.com/v1.0/me/events', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $outlook_settings['access_token'],
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($event_data)
        ));
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!empty($data['id'])) {
                // Save Outlook event ID to appointment
                $this->db_manager->update_appointment($appointment->id, array(
                    'outlook_event_id' => $data['id']
                ));
                
                return $data['id'];
            }
        }
        
        return false;
    }
    
    /**
     * Delete Google Calendar event
     */
    private function delete_google_calendar_event($event_id) {
        $google_settings = $this->settings['google_calendar'] ?? array();
        
        if (empty($google_settings['access_token'])) {
            return false;
        }
        
        $response = wp_remote_request('https://www.googleapis.com/calendar/v3/calendars/primary/events/' . $event_id, array(
            'method' => 'DELETE',
            'headers' => array(
                'Authorization' => 'Bearer ' . $google_settings['access_token']
            )
        ));
        
        return !is_wp_error($response);
    }
    
    /**
     * Delete Outlook Calendar event
     */
    private function delete_outlook_calendar_event($event_id) {
        $outlook_settings = $this->settings['outlook_calendar'] ?? array();
        
        if (empty($outlook_settings['access_token'])) {
            return false;
        }
        
        $response = wp_remote_request('https://graph.microsoft.com/v1.0/me/events/' . $event_id, array(
            'method' => 'DELETE',
            'headers' => array(
                'Authorization' => 'Bearer ' . $outlook_settings['access_token']
            )
        ));
        
        return !is_wp_error($response);
    }
}
