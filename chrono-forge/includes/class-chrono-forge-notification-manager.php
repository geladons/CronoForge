<?php
/**
 * Notification Manager Class
 * 
 * Handles email and SMS notifications with templates
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ChronoForge_Notification_Manager {
    
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
        // Schedule reminder cron job
        add_action('chrono_forge_send_reminders', array($this, 'send_appointment_reminders'));
        
        // Schedule cleanup cron job
        add_action('chrono_forge_cleanup_old_appointments', array($this, 'cleanup_old_appointments'));
        
        // Hook into appointment status changes
        add_action('chrono_forge_appointment_created', array($this, 'send_appointment_created_notifications'));
        add_action('chrono_forge_appointment_confirmed', array($this, 'send_appointment_confirmed_notifications'));
        add_action('chrono_forge_appointment_cancelled', array($this, 'send_appointment_cancelled_notifications'));
        add_action('chrono_forge_appointment_reminder', array($this, 'send_appointment_reminder_notifications'));
    }
    
    /**
     * Send appointment created notifications
     */
    public function send_appointment_created_notifications($appointment_id) {
        $appointment = $this->db_manager->get_appointment($appointment_id);
        if (!$appointment) return;
        
        // Send to customer
        $this->send_email_notification($appointment, 'appointment_created', 'customer');
        
        // Send to admin
        $this->send_email_notification($appointment, 'appointment_created', 'admin');
        
        // Send SMS if enabled
        if (!empty($this->settings['enable_sms_notifications'])) {
            $this->send_sms_notification($appointment, 'appointment_created', 'customer');
        }
    }
    
    /**
     * Send appointment confirmed notifications
     */
    public function send_appointment_confirmed_notifications($appointment_id) {
        $appointment = $this->db_manager->get_appointment($appointment_id);
        if (!$appointment) return;
        
        $this->send_email_notification($appointment, 'appointment_confirmed', 'customer');
        
        if (!empty($this->settings['enable_sms_notifications'])) {
            $this->send_sms_notification($appointment, 'appointment_confirmed', 'customer');
        }
    }
    
    /**
     * Send appointment cancelled notifications
     */
    public function send_appointment_cancelled_notifications($appointment_id) {
        $appointment = $this->db_manager->get_appointment($appointment_id);
        if (!$appointment) return;
        
        $this->send_email_notification($appointment, 'appointment_cancelled', 'customer');
        $this->send_email_notification($appointment, 'appointment_cancelled', 'admin');
        
        if (!empty($this->settings['enable_sms_notifications'])) {
            $this->send_sms_notification($appointment, 'appointment_cancelled', 'customer');
        }
    }
    
    /**
     * Send appointment reminder notifications
     */
    public function send_appointment_reminder_notifications($appointment_id) {
        $appointment = $this->db_manager->get_appointment($appointment_id);
        if (!$appointment) return;
        
        $this->send_email_notification($appointment, 'appointment_reminder', 'customer');
        
        if (!empty($this->settings['enable_sms_notifications'])) {
            $this->send_sms_notification($appointment, 'appointment_reminder', 'customer');
        }
    }
    
    /**
     * Send email notification
     */
    public function send_email_notification($appointment, $template, $recipient_type) {
        if (empty($this->settings['enable_notifications'])) {
            return false;
        }
        
        $template_data = $this->get_email_template($template, $recipient_type);
        if (!$template_data) {
            return false;
        }
        
        $subject = $this->parse_template($template_data['subject'], $appointment);
        $message = $this->parse_template($template_data['message'], $appointment);
        
        // Get recipient email
        $to_email = '';
        if ($recipient_type === 'customer') {
            $to_email = $appointment->customer_email;
        } elseif ($recipient_type === 'admin') {
            $to_email = $this->settings['admin_email'] ?? get_option('admin_email');
        }
        
        if (!$to_email) {
            return false;
        }
        
        // Set email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . ($this->settings['company_name'] ?? get_bloginfo('name')) . ' <' . ($this->settings['company_email'] ?? get_option('admin_email')) . '>'
        );
        
        return wp_mail($to_email, $subject, $message, $headers);
    }
    
    /**
     * Send SMS notification
     */
    public function send_sms_notification($appointment, $template, $recipient_type) {
        if (empty($this->settings['enable_sms_notifications'])) {
            return false;
        }
        
        $sms_settings = $this->settings['sms'] ?? array();
        if (empty($sms_settings['provider']) || empty($sms_settings['api_key'])) {
            return false;
        }
        
        $template_data = $this->get_sms_template($template);
        if (!$template_data) {
            return false;
        }
        
        $message = $this->parse_template($template_data['message'], $appointment);
        
        // Get recipient phone
        $to_phone = '';
        if ($recipient_type === 'customer') {
            $to_phone = $appointment->customer_phone;
        }
        
        if (!$to_phone) {
            return false;
        }
        
        // Send SMS based on provider
        switch ($sms_settings['provider']) {
            case 'twilio':
                return $this->send_twilio_sms($to_phone, $message);
            case 'nexmo':
                return $this->send_nexmo_sms($to_phone, $message);
            default:
                return false;
        }
    }
    
    /**
     * Get email template
     */
    private function get_email_template($template, $recipient_type) {
        $templates = array(
            'appointment_created' => array(
                'customer' => array(
                    'subject' => __('Подтверждение записи - {service_name}', 'chrono-forge'),
                    'message' => $this->get_customer_appointment_created_template()
                ),
                'admin' => array(
                    'subject' => __('Новая запись - {customer_name}', 'chrono-forge'),
                    'message' => $this->get_admin_appointment_created_template()
                )
            ),
            'appointment_confirmed' => array(
                'customer' => array(
                    'subject' => __('Запись подтверждена - {service_name}', 'chrono-forge'),
                    'message' => $this->get_customer_appointment_confirmed_template()
                )
            ),
            'appointment_cancelled' => array(
                'customer' => array(
                    'subject' => __('Запись отменена - {service_name}', 'chrono-forge'),
                    'message' => $this->get_customer_appointment_cancelled_template()
                ),
                'admin' => array(
                    'subject' => __('Запись отменена - {customer_name}', 'chrono-forge'),
                    'message' => $this->get_admin_appointment_cancelled_template()
                )
            ),
            'appointment_reminder' => array(
                'customer' => array(
                    'subject' => __('Напоминание о записи - {service_name}', 'chrono-forge'),
                    'message' => $this->get_customer_appointment_reminder_template()
                )
            )
        );
        
        return $templates[$template][$recipient_type] ?? null;
    }
    
    /**
     * Get SMS template
     */
    private function get_sms_template($template) {
        $templates = array(
            'appointment_created' => array(
                'message' => __('Ваша запись подтверждена: {service_name} {appointment_date} в {appointment_time}. {company_name}', 'chrono-forge')
            ),
            'appointment_confirmed' => array(
                'message' => __('Запись подтверждена: {service_name} {appointment_date} в {appointment_time}. {company_name}', 'chrono-forge')
            ),
            'appointment_cancelled' => array(
                'message' => __('Запись отменена: {service_name} {appointment_date} в {appointment_time}. {company_name}', 'chrono-forge')
            ),
            'appointment_reminder' => array(
                'message' => __('Напоминание: завтра у вас запись {service_name} в {appointment_time}. {company_name}', 'chrono-forge')
            )
        );
        
        return $templates[$template] ?? null;
    }
    
    /**
     * Parse template with appointment data
     */
    private function parse_template($template, $appointment) {
        $replacements = array(
            '{customer_name}' => $appointment->customer_first_name . ' ' . $appointment->customer_last_name,
            '{customer_first_name}' => $appointment->customer_first_name,
            '{customer_last_name}' => $appointment->customer_last_name,
            '{customer_email}' => $appointment->customer_email,
            '{customer_phone}' => $appointment->customer_phone,
            '{service_name}' => $appointment->service_name,
            '{employee_name}' => $appointment->employee_name,
            '{appointment_date}' => date('d.m.Y', strtotime($appointment->appointment_date)),
            '{appointment_time}' => date('H:i', strtotime($appointment->appointment_time)),
            '{appointment_end_time}' => date('H:i', strtotime($appointment->end_time)),
            '{total_price}' => $appointment->total_price,
            '{currency_symbol}' => $this->settings['currency_symbol'] ?? '$',
            '{company_name}' => $this->settings['company_name'] ?? get_bloginfo('name'),
            '{company_phone}' => $this->settings['company_phone'] ?? '',
            '{company_email}' => $this->settings['company_email'] ?? get_option('admin_email'),
            '{company_address}' => $this->settings['company_address'] ?? '',
            '{notes}' => $appointment->notes ?? '',
            '{site_url}' => home_url(),
            '{cancel_url}' => $this->get_cancel_appointment_url($appointment->id)
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    /**
     * Send appointment reminders (cron job)
     */
    public function send_appointment_reminders() {
        // Get appointments for tomorrow
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        $appointments = $this->db_manager->get_all_appointments(array(
            'date' => $tomorrow,
            'status' => 'confirmed'
        ));
        
        foreach ($appointments as $appointment) {
            do_action('chrono_forge_appointment_reminder', $appointment->id);
        }
    }
    
    /**
     * Cleanup old appointments (cron job)
     */
    public function cleanup_old_appointments() {
        // Mark old appointments as completed
        $cutoff_date = date('Y-m-d', strtotime('-1 day'));
        
        global $wpdb;
        $table = $wpdb->prefix . 'chrono_forge_appointments';
        
        $wpdb->update(
            $table,
            array('status' => 'completed'),
            array(
                'appointment_date' => $cutoff_date,
                'status' => 'confirmed'
            ),
            array('%s'),
            array('%s', '%s')
        );
    }
    
    /**
     * Send Twilio SMS
     */
    private function send_twilio_sms($to_phone, $message) {
        $sms_settings = $this->settings['sms'] ?? array();
        
        // Implementation would require Twilio SDK
        // For demo purposes, log the SMS
        error_log("Twilio SMS to {$to_phone}: {$message}");
        
        return true;
    }
    
    /**
     * Send Nexmo SMS
     */
    private function send_nexmo_sms($to_phone, $message) {
        $sms_settings = $this->settings['sms'] ?? array();
        
        // Implementation would require Nexmo SDK
        // For demo purposes, log the SMS
        error_log("Nexmo SMS to {$to_phone}: {$message}");
        
        return true;
    }
    
    /**
     * Get cancel appointment URL
     */
    private function get_cancel_appointment_url($appointment_id) {
        return add_query_arg(array(
            'action' => 'cancel_appointment',
            'appointment_id' => $appointment_id,
            'nonce' => wp_create_nonce('cancel_appointment_' . $appointment_id)
        ), home_url());
    }

    /**
     * Customer appointment created email template
     */
    private function get_customer_appointment_created_template() {
        return '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #3498db;">Подтверждение записи</h2>
                <p>Здравствуйте, {customer_first_name}!</p>
                <p>Ваша запись успешно создана. Детали записи:</p>

                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="margin-top: 0;">Детали записи</h3>
                    <p><strong>Услуга:</strong> {service_name}</p>
                    <p><strong>Специалист:</strong> {employee_name}</p>
                    <p><strong>Дата:</strong> {appointment_date}</p>
                    <p><strong>Время:</strong> {appointment_time} - {appointment_end_time}</p>
                    <p><strong>Стоимость:</strong> {currency_symbol}{total_price}</p>
                </div>

                <p>Если вам необходимо отменить запись, используйте ссылку ниже:</p>
                <p><a href="{cancel_url}" style="color: #e74c3c;">Отменить запись</a></p>

                <hr style="margin: 30px 0;">
                <p style="font-size: 14px; color: #666;">
                    С уважением,<br>
                    {company_name}<br>
                    {company_phone}<br>
                    {company_email}
                </p>
            </div>
        </body>
        </html>';
    }

    /**
     * Admin appointment created email template
     */
    private function get_admin_appointment_created_template() {
        return '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #3498db;">Новая запись</h2>
                <p>Создана новая запись в системе ChronoForge.</p>

                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="margin-top: 0;">Детали записи</h3>
                    <p><strong>Клиент:</strong> {customer_name}</p>
                    <p><strong>Email:</strong> {customer_email}</p>
                    <p><strong>Телефон:</strong> {customer_phone}</p>
                    <p><strong>Услуга:</strong> {service_name}</p>
                    <p><strong>Специалист:</strong> {employee_name}</p>
                    <p><strong>Дата:</strong> {appointment_date}</p>
                    <p><strong>Время:</strong> {appointment_time} - {appointment_end_time}</p>
                    <p><strong>Стоимость:</strong> {currency_symbol}{total_price}</p>
                    <p><strong>Комментарий:</strong> {notes}</p>
                </div>

                <p><a href="{site_url}/wp-admin/admin.php?page=chrono-forge-appointments" style="background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Управление записями</a></p>
            </div>
        </body>
        </html>';
    }

    /**
     * Customer appointment confirmed email template
     */
    private function get_customer_appointment_confirmed_template() {
        return '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #27ae60;">Запись подтверждена</h2>
                <p>Здравствуйте, {customer_first_name}!</p>
                <p>Ваша запись подтверждена и ожидает вас:</p>

                <div style="background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #27ae60;">
                    <h3 style="margin-top: 0; color: #155724;">Подтвержденная запись</h3>
                    <p><strong>Услуга:</strong> {service_name}</p>
                    <p><strong>Специалист:</strong> {employee_name}</p>
                    <p><strong>Дата:</strong> {appointment_date}</p>
                    <p><strong>Время:</strong> {appointment_time} - {appointment_end_time}</p>
                </div>

                <p>Ждем вас в назначенное время!</p>

                <hr style="margin: 30px 0;">
                <p style="font-size: 14px; color: #666;">
                    С уважением,<br>
                    {company_name}<br>
                    {company_phone}<br>
                    {company_email}
                </p>
            </div>
        </body>
        </html>';
    }

    /**
     * Customer appointment cancelled email template
     */
    private function get_customer_appointment_cancelled_template() {
        return '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #e74c3c;">Запись отменена</h2>
                <p>Здравствуйте, {customer_first_name}!</p>
                <p>Ваша запись была отменена:</p>

                <div style="background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #e74c3c;">
                    <h3 style="margin-top: 0; color: #721c24;">Отмененная запись</h3>
                    <p><strong>Услуга:</strong> {service_name}</p>
                    <p><strong>Специалист:</strong> {employee_name}</p>
                    <p><strong>Дата:</strong> {appointment_date}</p>
                    <p><strong>Время:</strong> {appointment_time}</p>
                </div>

                <p>Если у вас есть вопросы, свяжитесь с нами.</p>

                <hr style="margin: 30px 0;">
                <p style="font-size: 14px; color: #666;">
                    С уважением,<br>
                    {company_name}<br>
                    {company_phone}<br>
                    {company_email}
                </p>
            </div>
        </body>
        </html>';
    }

    /**
     * Admin appointment cancelled email template
     */
    private function get_admin_appointment_cancelled_template() {
        return '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #e74c3c;">Запись отменена</h2>
                <p>Запись была отменена клиентом:</p>

                <div style="background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="margin-top: 0;">Детали отмененной записи</h3>
                    <p><strong>Клиент:</strong> {customer_name}</p>
                    <p><strong>Услуга:</strong> {service_name}</p>
                    <p><strong>Специалист:</strong> {employee_name}</p>
                    <p><strong>Дата:</strong> {appointment_date}</p>
                    <p><strong>Время:</strong> {appointment_time}</p>
                </div>
            </div>
        </body>
        </html>';
    }

    /**
     * Customer appointment reminder email template
     */
    private function get_customer_appointment_reminder_template() {
        return '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #f39c12;">Напоминание о записи</h2>
                <p>Здравствуйте, {customer_first_name}!</p>
                <p>Напоминаем, что завтра у вас запись:</p>

                <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f39c12;">
                    <h3 style="margin-top: 0; color: #856404;">Завтрашняя запись</h3>
                    <p><strong>Услуга:</strong> {service_name}</p>
                    <p><strong>Специалист:</strong> {employee_name}</p>
                    <p><strong>Время:</strong> {appointment_time} - {appointment_end_time}</p>
                </div>

                <p>Ждем вас в назначенное время!</p>

                <hr style="margin: 30px 0;">
                <p style="font-size: 14px; color: #666;">
                    С уважением,<br>
                    {company_name}<br>
                    {company_phone}<br>
                    {company_email}
                </p>
            </div>
        </body>
        </html>';
    }
}
