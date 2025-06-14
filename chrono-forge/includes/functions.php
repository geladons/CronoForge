<?php
/**
 * Core Functions for ChronoForge
 * 
 * @package ChronoForge
 */

namespace ChronoForge;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin instance
 * 
 * @return Plugin
 */
function chronoforge()
{
    return Plugin::getInstance();
}

/**
 * Get container instance
 * 
 * @return Container
 */
function container()
{
    return chronoforge()->getContainer();
}

/**
 * Safe logging function
 * 
 * @param string $message
 * @param string $level
 */
function safe_log($message, $level = 'info')
{
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf('[ChronoForge][%s] %s', strtoupper($level), $message));
    }
}

/**
 * Get service from container
 * 
 * @param string $service
 * @return mixed
 */
function service($service)
{
    return container()->get($service);
}

/**
 * Check if user has required capability
 * 
 * @param string $capability
 * @return bool
 */
function user_can($capability = 'manage_options')
{
    return current_user_can($capability);
}

/**
 * Sanitize input data
 * 
 * @param mixed $data
 * @param string $type
 * @return mixed
 */
function sanitize_input($data, $type = 'text')
{
    switch ($type) {
        case 'email':
            return sanitize_email($data);
        case 'url':
            return esc_url_raw($data);
        case 'int':
            return intval($data);
        case 'float':
            return floatval($data);
        case 'bool':
            return (bool) $data;
        case 'array':
            return is_array($data) ? array_map('sanitize_text_field', $data) : [];
        case 'text':
        default:
            return sanitize_text_field($data);
    }
}

/**
 * Get plugin option
 * 
 * @param string $option
 * @param mixed $default
 * @return mixed
 */
function get_option($option, $default = null)
{
    return \get_option('chrono_forge_' . $option, $default);
}

/**
 * Update plugin option
 * 
 * @param string $option
 * @param mixed $value
 * @return bool
 */
function update_option($option, $value)
{
    return \update_option('chrono_forge_' . $option, $value);
}

/**
 * Delete plugin option
 * 
 * @param string $option
 * @return bool
 */
function delete_option($option)
{
    return \delete_option('chrono_forge_' . $option);
}

/**
 * Get admin URL for plugin page
 * 
 * @param string $page
 * @param array $args
 * @return string
 */
function admin_url($page = '', $args = [])
{
    $url = \admin_url('admin.php?page=chrono-forge' . ($page ? '-' . $page : ''));
    
    if (!empty($args)) {
        $url = add_query_arg($args, $url);
    }
    
    return $url;
}

/**
 * Render template
 * 
 * @param string $template
 * @param array $data
 * @param bool $return
 * @return string|void
 */
function render_template($template, $data = [], $return = false)
{
    $template_file = CHRONO_FORGE_PLUGIN_DIR . 'templates/' . $template . '.php';
    
    if (!file_exists($template_file)) {
        if ($return) {
            return '';
        }
        return;
    }
    
    if ($return) {
        ob_start();
    }
    
    // Extract data to variables
    if (!empty($data)) {
        extract($data, EXTR_SKIP);
    }
    
    include $template_file;
    
    if ($return) {
        return ob_get_clean();
    }
}

/**
 * Get asset URL
 * 
 * @param string $asset
 * @return string
 */
function asset_url($asset)
{
    return CHRONO_FORGE_PLUGIN_URL . 'assets/' . ltrim($asset, '/');
}

/**
 * Enqueue script
 * 
 * @param string $handle
 * @param string $src
 * @param array $deps
 * @param bool $in_footer
 */
function enqueue_script($handle, $src, $deps = [], $in_footer = true)
{
    wp_enqueue_script(
        'chrono-forge-' . $handle,
        asset_url('js/' . $src),
        $deps,
        CHRONO_FORGE_VERSION,
        $in_footer
    );
}

/**
 * Enqueue style
 * 
 * @param string $handle
 * @param string $src
 * @param array $deps
 */
function enqueue_style($handle, $src, $deps = [])
{
    wp_enqueue_style(
        'chrono-forge-' . $handle,
        asset_url('css/' . $src),
        $deps,
        CHRONO_FORGE_VERSION
    );
}

/**
 * Create nonce
 * 
 * @param string $action
 * @return string
 */
function create_nonce($action)
{
    return wp_create_nonce('chrono_forge_' . $action);
}

/**
 * Verify nonce
 * 
 * @param string $nonce
 * @param string $action
 * @return bool
 */
function verify_nonce($nonce, $action)
{
    return wp_verify_nonce($nonce, 'chrono_forge_' . $action);
}

/**
 * Format date for display
 * 
 * @param string $date
 * @param string $format
 * @return string
 */
function format_date($date, $format = null)
{
    if (!$format) {
        $format = get_option('date_format', 'Y-m-d');
    }
    
    return date($format, strtotime($date));
}

/**
 * Format time for display
 * 
 * @param string $time
 * @param string $format
 * @return string
 */
function format_time($time, $format = null)
{
    if (!$format) {
        $format = get_option('time_format', 'H:i');
    }
    
    return date($format, strtotime($time));
}

/**
 * Get current user ID
 * 
 * @return int
 */
function get_current_user_id()
{
    return \get_current_user_id();
}

/**
 * Check if current request is AJAX
 * 
 * @return bool
 */
function is_ajax()
{
    return wp_doing_ajax();
}

/**
 * Send JSON response
 * 
 * @param mixed $data
 * @param bool $success
 */
function send_json($data, $success = true)
{
    if ($success) {
        wp_send_json_success($data);
    } else {
        wp_send_json_error($data);
    }
}
