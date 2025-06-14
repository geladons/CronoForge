<?php
/**
 * Base Admin Controller
 * 
 * @package ChronoForge\Admin\Controllers
 */

namespace ChronoForge\Admin\Controllers;

use ChronoForge\Infrastructure\Container;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base Controller class
 */
abstract class BaseController
{
    /**
     * Container instance
     * 
     * @var Container
     */
    protected $container;

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
     * Check if user has required capability
     * 
     * @param string $capability
     * @return bool
     */
    protected function userCan($capability = 'manage_options')
    {
        return current_user_can($capability);
    }

    /**
     * Verify nonce
     * 
     * @param string $action
     * @param string $nonce_field
     * @return bool
     */
    protected function verifyNonce($action, $nonce_field = '_wpnonce')
    {
        $nonce = $_POST[$nonce_field] ?? $_GET[$nonce_field] ?? '';
        return wp_verify_nonce($nonce, 'chrono_forge_' . $action);
    }

    /**
     * Redirect with message
     * 
     * @param string $url
     * @param string $message
     * @param string $type
     */
    protected function redirectWithMessage($url, $message, $type = 'success')
    {
        $url = add_query_arg([
            'message' => urlencode($message),
            'type' => $type
        ], $url);

        wp_redirect($url);
        exit;
    }

    /**
     * Render template
     * 
     * @param string $template
     * @param array $data
     */
    protected function render($template, $data = [])
    {
        // Add common data
        $data['container'] = $this->container;
        $data['current_user'] = wp_get_current_user();
        
        // Check for messages
        if (isset($_GET['message']) && isset($_GET['type'])) {
            $data['message'] = sanitize_text_field($_GET['message']);
            $data['message_type'] = sanitize_text_field($_GET['type']);
        }

        \ChronoForge\render_template('admin/' . $template, $data);
    }

    /**
     * Get sanitized input
     * 
     * @param string $key
     * @param string $type
     * @param mixed $default
     * @return mixed
     */
    protected function getInput($key, $type = 'text', $default = null)
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        
        if ($value === null) {
            return $default;
        }

        return \ChronoForge\sanitize_input($value, $type);
    }

    /**
     * Get pagination data
     * 
     * @param int $total_items
     * @param int $per_page
     * @param int $current_page
     * @return array
     */
    protected function getPagination($total_items, $per_page = 20, $current_page = 1)
    {
        $total_pages = ceil($total_items / $per_page);
        $current_page = max(1, min($current_page, $total_pages));
        $offset = ($current_page - 1) * $per_page;

        return [
            'total_items' => $total_items,
            'per_page' => $per_page,
            'current_page' => $current_page,
            'total_pages' => $total_pages,
            'offset' => $offset,
            'has_prev' => $current_page > 1,
            'has_next' => $current_page < $total_pages,
            'prev_page' => $current_page - 1,
            'next_page' => $current_page + 1
        ];
    }

    /**
     * Send JSON response
     * 
     * @param mixed $data
     * @param bool $success
     */
    protected function sendJson($data, $success = true)
    {
        if ($success) {
            wp_send_json_success($data);
        } else {
            wp_send_json_error($data);
        }
    }

    /**
     * Validate required fields
     * 
     * @param array $fields
     * @param array $data
     * @return array
     */
    protected function validateRequired($fields, $data)
    {
        $errors = [];

        foreach ($fields as $field => $label) {
            if (empty($data[$field])) {
                $errors[$field] = sprintf(__('%s is required.', 'chrono-forge'), $label);
            }
        }

        return $errors;
    }

    /**
     * Format validation errors for display
     * 
     * @param array $errors
     * @return string
     */
    protected function formatErrors($errors)
    {
        if (empty($errors)) {
            return '';
        }

        $html = '<div class="notice notice-error"><ul>';
        foreach ($errors as $error) {
            $html .= '<li>' . esc_html($error) . '</li>';
        }
        $html .= '</ul></div>';

        return $html;
    }

    /**
     * Get current page URL
     * 
     * @param array $args
     * @return string
     */
    protected function getCurrentUrl($args = [])
    {
        $page = $this->getInput('page', 'text', '');
        $url = admin_url('admin.php?page=' . $page);

        if (!empty($args)) {
            $url = add_query_arg($args, $url);
        }

        return $url;
    }

    /**
     * Enqueue admin scripts and styles
     * 
     * @param array $scripts
     * @param array $styles
     */
    protected function enqueueAssets($scripts = [], $styles = [])
    {
        foreach ($styles as $handle => $src) {
            \ChronoForge\enqueue_style($handle, $src);
        }

        foreach ($scripts as $handle => $config) {
            $src = $config['src'] ?? $config;
            $deps = $config['deps'] ?? ['jquery'];
            \ChronoForge\enqueue_script($handle, $src, $deps);
        }

        // Always enqueue common admin assets
        \ChronoForge\enqueue_style('admin', 'admin.css');
        \ChronoForge\enqueue_script('admin', 'admin.js', ['jquery']);

        // Localize script with common data
        wp_localize_script('chrono-forge-admin', 'chronoForge', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chrono_forge_ajax'),
            'strings' => [
                'confirm_delete' => __('Are you sure you want to delete this item?', 'chrono-forge'),
                'error' => __('An error occurred. Please try again.', 'chrono-forge'),
                'success' => __('Operation completed successfully.', 'chrono-forge')
            ]
        ]);
    }
}
