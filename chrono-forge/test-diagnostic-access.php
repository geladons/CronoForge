<?php
/**
 * ChronoForge Diagnostic Access Test
 *
 * This script tests if the diagnostic system is accessible
 * Run this by accessing: /wp-content/plugins/chrono-forge/test-diagnostic-access.php
 *
 * @package ChronoForge
 * @since 1.0.0
 */

// Load WordPress
$wp_load_paths = array(
    '../../../wp-load.php',
    '../../../../wp-load.php',
    '../../../../../wp-load.php'
);

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('WordPress not found. Please run this script from the plugin directory.');
}

// Check if user is logged in and has permissions
if (!is_user_logged_in()) {
    wp_die('You must be logged in to access this diagnostic test.');
}

if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this diagnostic test.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>ChronoForge Diagnostic Access Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .status-ok { background-color: #d4edda; }
        .status-warning { background-color: #fff3cd; }
        .status-error { background-color: #f8d7da; }
    </style>
</head>
<body>
    <h1>ChronoForge Diagnostic Access Test</h1>
    
    <h2>User Information</h2>
    <table>
        <tr><td><strong>Current User</strong></td><td><?php echo wp_get_current_user()->user_login; ?> (ID: <?php echo get_current_user_id(); ?>)</td></tr>
        <tr><td><strong>Has manage_options</strong></td><td class="<?php echo current_user_can('manage_options') ? 'success' : 'error'; ?>"><?php echo current_user_can('manage_options') ? 'Yes' : 'No'; ?></td></tr>
        <tr><td><strong>Is Admin</strong></td><td class="<?php echo is_admin() ? 'success' : 'warning'; ?>"><?php echo is_admin() ? 'Yes' : 'No'; ?></td></tr>
    </table>

    <h2>Plugin Status</h2>
    <table>
        <tr><td><strong>Plugin Active</strong></td><td class="<?php echo is_plugin_active('chrono-forge/chrono-forge.php') ? 'success' : 'error'; ?>"><?php echo is_plugin_active('chrono-forge/chrono-forge.php') ? 'Yes' : 'No'; ?></td></tr>
        <tr><td><strong>Plugin Directory</strong></td><td><?php echo defined('CHRONO_FORGE_PLUGIN_DIR') ? CHRONO_FORGE_PLUGIN_DIR : 'Not defined'; ?></td></tr>
        <tr><td><strong>Plugin Version</strong></td><td><?php echo defined('CHRONO_FORGE_VERSION') ? CHRONO_FORGE_VERSION : 'Not defined'; ?></td></tr>
    </table>

    <h2>Class Availability</h2>
    <table>
        <tr><td><strong>ChronoForge_Core</strong></td><td class="<?php echo class_exists('ChronoForge_Core') ? 'success' : 'error'; ?>"><?php echo class_exists('ChronoForge_Core') ? 'Available' : 'Not Available'; ?></td></tr>
        <tr><td><strong>ChronoForge_Diagnostics</strong></td><td class="<?php echo class_exists('ChronoForge_Diagnostics') ? 'success' : 'error'; ?>"><?php echo class_exists('ChronoForge_Diagnostics') ? 'Available' : 'Not Available'; ?></td></tr>
        <tr><td><strong>ChronoForge_Admin_Diagnostics</strong></td><td class="<?php echo class_exists('ChronoForge_Admin_Diagnostics') ? 'success' : 'error'; ?>"><?php echo class_exists('ChronoForge_Admin_Diagnostics') ? 'Available' : 'Not Available'; ?></td></tr>
        <tr><td><strong>ChronoForge_Admin_Menu</strong></td><td class="<?php echo class_exists('ChronoForge_Admin_Menu') ? 'success' : 'error'; ?>"><?php echo class_exists('ChronoForge_Admin_Menu') ? 'Available' : 'Not Available'; ?></td></tr>
    </table>

    <h2>Function Availability</h2>
    <table>
        <tr><td><strong>chrono_forge_standalone_diagnostics_page</strong></td><td class="<?php echo function_exists('chrono_forge_standalone_diagnostics_page') ? 'success' : 'error'; ?>"><?php echo function_exists('chrono_forge_standalone_diagnostics_page') ? 'Available' : 'Not Available'; ?></td></tr>
        <tr><td><strong>chrono_forge_render_basic_diagnostics_page</strong></td><td class="<?php echo function_exists('chrono_forge_render_basic_diagnostics_page') ? 'success' : 'error'; ?>"><?php echo function_exists('chrono_forge_render_basic_diagnostics_page') ? 'Available' : 'Not Available'; ?></td></tr>
    </table>

    <h2>Menu Registration Test</h2>
    <?php
    global $menu, $submenu;
    $chrono_forge_menu_found = false;
    $diagnostics_menu_found = false;
    
    if (is_array($menu)) {
        foreach ($menu as $menu_item) {
            if (isset($menu_item[2]) && (strpos($menu_item[2], 'chrono-forge') !== false)) {
                $chrono_forge_menu_found = true;
                echo '<p class="success">✓ ChronoForge menu found: ' . esc_html($menu_item[0]) . ' (' . esc_html($menu_item[2]) . ')</p>';
            }
        }
    }
    
    if (is_array($submenu)) {
        foreach ($submenu as $parent => $items) {
            if (strpos($parent, 'chrono-forge') !== false) {
                foreach ($items as $item) {
                    if (isset($item[2]) && strpos($item[2], 'diagnostics') !== false) {
                        $diagnostics_menu_found = true;
                        echo '<p class="success">✓ Diagnostics submenu found: ' . esc_html($item[0]) . ' (' . esc_html($item[2]) . ')</p>';
                    }
                }
            }
        }
    }
    
    if (!$chrono_forge_menu_found) {
        echo '<p class="error">✗ ChronoForge menu not found</p>';
    }
    
    if (!$diagnostics_menu_found) {
        echo '<p class="warning">⚠ Diagnostics submenu not found</p>';
    }
    ?>

    <h2>Direct Access Test</h2>
    <p>Try accessing the diagnostic pages directly:</p>
    <ul>
        <li><a href="<?php echo admin_url('admin.php?page=chrono-forge-diagnostics'); ?>" target="_blank">Main Diagnostics Page</a></li>
        <li><a href="<?php echo admin_url('admin.php?page=chrono-forge-emergency'); ?>" target="_blank">Emergency Diagnostics Page</a></li>
    </ul>

    <h2>Test Standalone Function</h2>
    <?php if (function_exists('chrono_forge_standalone_diagnostics_page')): ?>
        <p class="success">✓ Standalone diagnostics function is available</p>
        <p><strong>Testing function execution:</strong></p>
        <div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: #f9f9f9;">
            <?php
            try {
                ob_start();
                chrono_forge_standalone_diagnostics_page();
                $output = ob_get_clean();
                echo '<p class="success">✓ Function executed successfully</p>';
                echo '<details><summary>Function Output (click to expand)</summary>';
                echo '<div style="max-height: 300px; overflow: auto; border: 1px solid #ddd; padding: 10px; background: white;">';
                echo htmlspecialchars($output);
                echo '</div></details>';
            } catch (Exception $e) {
                echo '<p class="error">✗ Function execution failed: ' . esc_html($e->getMessage()) . '</p>';
            }
            ?>
        </div>
    <?php else: ?>
        <p class="error">✗ Standalone diagnostics function is not available</p>
    <?php endif; ?>

    <h2>Recommendations</h2>
    <div style="background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; margin: 20px 0;">
        <h3>Next Steps:</h3>
        <ol>
            <li>If all tests show green (success), the diagnostic system should be working</li>
            <li>If you see red (error) items, those need to be fixed first</li>
            <li>Try accessing the diagnostic pages using the links above</li>
            <li>If pages show "access denied", check user permissions</li>
            <li>If pages show blank or errors, check WordPress error logs</li>
        </ol>
    </div>

    <p><a href="<?php echo admin_url(); ?>">← Back to WordPress Admin</a></p>
</body>
</html>
