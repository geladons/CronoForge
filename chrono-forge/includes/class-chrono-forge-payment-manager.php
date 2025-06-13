<?php
/**
 * Payment Manager Class
 * 
 * Handles payment processing for different gateways
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ChronoForge_Payment_Manager {
    
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
        add_action('wp_ajax_chrono_forge_process_payment', array($this, 'process_payment'));
        add_action('wp_ajax_nopriv_chrono_forge_process_payment', array($this, 'process_payment'));
        
        // Payment webhook handlers
        add_action('wp_ajax_chrono_forge_stripe_webhook', array($this, 'handle_stripe_webhook'));
        add_action('wp_ajax_nopriv_chrono_forge_stripe_webhook', array($this, 'handle_stripe_webhook'));
        
        add_action('wp_ajax_chrono_forge_paypal_webhook', array($this, 'handle_paypal_webhook'));
        add_action('wp_ajax_nopriv_chrono_forge_paypal_webhook', array($this, 'handle_paypal_webhook'));
    }
    
    /**
     * Process payment based on selected method
     */
    public function process_payment() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'chrono_forge_nonce')) {
            wp_die(__('Ошибка безопасности', 'chrono-forge'));
        }
        
        $appointment_id = intval($_POST['appointment_id']);
        $payment_method = sanitize_text_field($_POST['payment_method']);
        $amount = floatval($_POST['amount']);
        
        $appointment = $this->db_manager->get_appointment($appointment_id);
        if (!$appointment) {
            wp_send_json_error(__('Запись не найдена', 'chrono-forge'));
        }
        
        $result = false;
        
        switch ($payment_method) {
            case 'stripe':
                $result = $this->process_stripe_payment($appointment, $amount);
                break;
            case 'paypal':
                $result = $this->process_paypal_payment($appointment, $amount);
                break;
            case 'square':
                $result = $this->process_square_payment($appointment, $amount);
                break;
            case 'woocommerce':
                $result = $this->process_woocommerce_payment($appointment, $amount);
                break;
            case 'cash':
                $result = $this->process_cash_payment($appointment, $amount);
                break;
            default:
                wp_send_json_error(__('Неподдерживаемый метод оплаты', 'chrono-forge'));
        }
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('Ошибка обработки платежа', 'chrono-forge'));
        }
    }
    
    /**
     * Process Stripe payment
     */
    private function process_stripe_payment($appointment, $amount) {
        $stripe_settings = $this->settings['stripe'] ?? array();
        
        if (empty($stripe_settings['secret_key'])) {
            return false;
        }
        
        try {
            // Initialize Stripe (would require Stripe PHP SDK)
            // \Stripe\Stripe::setApiKey($stripe_settings['secret_key']);
            
            // Create payment intent
            $payment_data = array(
                'appointment_id' => $appointment->id,
                'amount' => $amount,
                'currency' => $this->settings['currency'] ?? 'USD',
                'payment_method' => 'stripe',
                'status' => 'pending'
            );
            
            $payment_id = $this->db_manager->insert_payment($payment_data);
            
            // For demo purposes, simulate successful payment
            if ($payment_id) {
                $this->db_manager->update_payment($payment_id, array(
                    'status' => 'completed',
                    'transaction_id' => 'stripe_' . time(),
                    'gateway_response' => json_encode(array('status' => 'succeeded'))
                ));
                
                return array(
                    'payment_id' => $payment_id,
                    'status' => 'completed',
                    'message' => __('Платеж успешно обработан', 'chrono-forge')
                );
            }
            
        } catch (Exception $e) {
            error_log('Stripe payment error: ' . $e->getMessage());
            return false;
        }
        
        return false;
    }
    
    /**
     * Process PayPal payment
     */
    private function process_paypal_payment($appointment, $amount) {
        $paypal_settings = $this->settings['paypal'] ?? array();
        
        if (empty($paypal_settings['client_id'])) {
            return false;
        }
        
        // Create payment record
        $payment_data = array(
            'appointment_id' => $appointment->id,
            'amount' => $amount,
            'currency' => $this->settings['currency'] ?? 'USD',
            'payment_method' => 'paypal',
            'status' => 'pending'
        );
        
        $payment_id = $this->db_manager->insert_payment($payment_data);
        
        if ($payment_id) {
            // Return PayPal payment URL (would integrate with PayPal SDK)
            return array(
                'payment_id' => $payment_id,
                'redirect_url' => $this->get_paypal_payment_url($payment_id, $amount),
                'status' => 'pending',
                'message' => __('Перенаправление на PayPal', 'chrono-forge')
            );
        }
        
        return false;
    }
    
    /**
     * Process Square payment
     */
    private function process_square_payment($appointment, $amount) {
        $square_settings = $this->settings['square'] ?? array();
        
        if (empty($square_settings['access_token'])) {
            return false;
        }
        
        // Create payment record
        $payment_data = array(
            'appointment_id' => $appointment->id,
            'amount' => $amount,
            'currency' => $this->settings['currency'] ?? 'USD',
            'payment_method' => 'square',
            'status' => 'pending'
        );
        
        $payment_id = $this->db_manager->insert_payment($payment_data);
        
        // For demo purposes
        if ($payment_id) {
            $this->db_manager->update_payment($payment_id, array(
                'status' => 'completed',
                'transaction_id' => 'square_' . time()
            ));
            
            return array(
                'payment_id' => $payment_id,
                'status' => 'completed',
                'message' => __('Платеж через Square обработан', 'chrono-forge')
            );
        }
        
        return false;
    }
    
    /**
     * Process WooCommerce payment
     */
    private function process_woocommerce_payment($appointment, $amount) {
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        // Create WooCommerce order for the appointment
        $order = wc_create_order();
        
        if (!$order) {
            return false;
        }
        
        // Add appointment as product
        $product_name = sprintf(
            __('Запись: %s - %s', 'chrono-forge'),
            $appointment->service_name,
            date('d.m.Y H:i', strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time))
        );
        
        $order->add_product(null, 1, array(
            'name' => $product_name,
            'total' => $amount
        ));
        
        $order->set_address(array(
            'first_name' => $appointment->customer_first_name,
            'last_name' => $appointment->customer_last_name,
            'email' => $appointment->customer_email
        ), 'billing');
        
        $order->calculate_totals();
        $order->update_status('pending');
        
        // Create payment record
        $payment_data = array(
            'appointment_id' => $appointment->id,
            'amount' => $amount,
            'currency' => $this->settings['currency'] ?? 'USD',
            'payment_method' => 'woocommerce',
            'status' => 'pending',
            'transaction_id' => 'wc_order_' . $order->get_id()
        );
        
        $payment_id = $this->db_manager->insert_payment($payment_data);
        
        if ($payment_id) {
            return array(
                'payment_id' => $payment_id,
                'redirect_url' => $order->get_checkout_payment_url(),
                'status' => 'pending',
                'message' => __('Перенаправление на оплату WooCommerce', 'chrono-forge')
            );
        }
        
        return false;
    }
    
    /**
     * Process cash payment
     */
    private function process_cash_payment($appointment, $amount) {
        $payment_data = array(
            'appointment_id' => $appointment->id,
            'amount' => $amount,
            'currency' => $this->settings['currency'] ?? 'USD',
            'payment_method' => 'cash',
            'status' => 'pending',
            'transaction_id' => 'cash_' . time()
        );
        
        $payment_id = $this->db_manager->insert_payment($payment_data);
        
        if ($payment_id) {
            return array(
                'payment_id' => $payment_id,
                'status' => 'pending',
                'message' => __('Оплата наличными при посещении', 'chrono-forge')
            );
        }
        
        return false;
    }
    
    /**
     * Get PayPal payment URL
     */
    private function get_paypal_payment_url($payment_id, $amount) {
        $paypal_settings = $this->settings['paypal'] ?? array();
        $is_sandbox = !empty($paypal_settings['sandbox']);
        
        $base_url = $is_sandbox ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com';
        
        $params = array(
            'cmd' => '_xclick',
            'business' => $paypal_settings['email'] ?? '',
            'item_name' => __('Оплата записи ChronoForge', 'chrono-forge'),
            'amount' => $amount,
            'currency_code' => $this->settings['currency'] ?? 'USD',
            'return' => add_query_arg('payment_id', $payment_id, home_url('/payment-success')),
            'cancel_return' => add_query_arg('payment_id', $payment_id, home_url('/payment-cancel')),
            'notify_url' => admin_url('admin-ajax.php?action=chrono_forge_paypal_webhook'),
            'custom' => $payment_id
        );
        
        return $base_url . '/cgi-bin/webscr?' . http_build_query($params);
    }
    
    /**
     * Handle Stripe webhook
     */
    public function handle_stripe_webhook() {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        
        // Verify webhook signature and process
        // Implementation would depend on Stripe SDK
        
        wp_send_json_success();
    }
    
    /**
     * Handle PayPal webhook
     */
    public function handle_paypal_webhook() {
        $payment_id = intval($_POST['custom'] ?? 0);
        $payment_status = sanitize_text_field($_POST['payment_status'] ?? '');
        
        if ($payment_id && $payment_status === 'Completed') {
            $this->db_manager->update_payment($payment_id, array(
                'status' => 'completed',
                'transaction_id' => sanitize_text_field($_POST['txn_id'] ?? ''),
                'gateway_response' => json_encode($_POST)
            ));
        }
        
        wp_send_json_success();
    }
}
